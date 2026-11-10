<?php
namespace app\community\model\service;

use think\facade\Db;

class WaterAlarmService
{
    private $config;
    public function __construct() { $this->config = config('water_alarm'); }

    public static function deviceName($name)
    {
        if (!is_string($name) || trim($name) === '' || mb_strlen(trim($name), 'UTF-8') > 60 || preg_match('/[<>\x00-\x1f]/u', $name)) {
            throw new \InvalidArgumentException('请输入1至60字的设备名称，不包含特殊控制字符');
        }
        return trim($name);
    }

    public static function volume($volume)
    {
        if ((!is_int($volume) && !is_string($volume)) || !preg_match('/^(?:[1-9]|[12][0-9]|3[01])$/D', (string)$volume)) {
            throw new \InvalidArgumentException('音量必须为1至31的整数，1为最低非静音档');
        }
        return (int)$volume;
    }

    public static function hasMenu($admin, $role, $dismissRoles, $menuId)
    {
        if (!$menuId || empty($admin['village_id'])) { return false; }
        if (empty($admin['normal_admin_id']) && in_array($role, array_merge([6], $dismissRoles))) { return true; }
        $menus = $admin['menus'] ?? [];
        if (!is_array($menus)) { $menus = explode(',', $menus); }
        return in_array((string)$menuId, array_map('strval', $menus), true);
    }

    private function endpoint() { return $this->config['host'] . ':' . $this->config['port'] . ':' . $this->config['unit']; }
    private function device($villageId, $id)
    {
        $device = Db::name('water_alarm_device')->where(['village_id' => $villageId, 'id' => (int)$id])->find();
        if (!$device) { throw new \InvalidArgumentException('设备不存在或无权操作'); }
        if ($device['endpoint'] !== $this->endpoint()) { throw new \InvalidArgumentException('设备连接配置已变更，请联系管理员核对'); }
        return $device;
    }

    private function present($device)
    {
        $device['state'] = !empty($device['state_json']) ? json_decode($device['state_json'], true) : null;
        $device['state_known'] = isset($device['state']['tts_status']) && !$device['last_error'] && time() - $device['last_seen'] <= 20;
        $device['connection'] = $device['state_known'] ? 'online' : ($device['last_error'] ? 'error' : 'unknown');
        unset($device['state_json']);
        $device['host'] = $this->config['host'];
        $device['port'] = $this->config['port'];
        return $device;
    }

    public function devices($villageId)
    {
        $rows = Db::name('water_alarm_device')->where('village_id', $villageId)->order('id desc')->select()->toArray();
        return ['list' => array_map(function ($row) { return $this->present($row); }, $rows),
            'can_add' => !Db::name('water_alarm_device')->where('endpoint', $this->endpoint())->count()];
    }

    public function save($villageId, array $params)
    {
        $name = self::deviceName($params['name'] ?? '');
        $id = (int)($params['id'] ?? 0);
        if ($id) {
            $this->device($villageId, $id);
            Db::name('water_alarm_device')->where(['id' => $id, 'village_id' => $villageId])->update(['name' => $name, 'update_time' => time()]);
        } else {
            if (Db::name('water_alarm_device')->where('endpoint', $this->endpoint())->count()) { throw new \InvalidArgumentException('默认设备已添加，当前演示仅支持一台物理设备'); }
            try {
                Db::name('water_alarm_device')->insert(['village_id' => $villageId, 'name' => $name,
                    'endpoint' => $this->endpoint(), 'volume' => $this->config['default_volume'], 'create_time' => time(), 'update_time' => time()]);
            } catch (\Throwable $e) {
                if (Db::name('water_alarm_device')->where('endpoint', $this->endpoint())->count()) { throw new \InvalidArgumentException('默认设备已添加，请刷新页面'); }
                throw $e;
            }
        }
        return $this->devices($villageId);
    }

    protected function transport()
    {
        if (($this->config['mode'] ?? 'direct') === 'gateway') { return new WaterAlarmGatewayTransport($this->config); }
        if (($this->config['mode'] ?? 'direct') !== 'direct') { throw new \RuntimeException('设备通信模式配置不正确'); }
        return new WaterAlarmTransport($this->config);
    }

    public function texts($villageId, $id)
    {
        $this->device($villageId, $id);
        return ['list' => Db::name('water_alarm_text')->where(['village_id' => $villageId, 'device_id' => $id])->order('id desc')->limit(100)->select()->toArray()];
    }

    public function addText($villageId, $id, $content)
    {
        if (!is_string($content)) { throw new \InvalidArgumentException('请输入播报文字'); }
        $content = trim($content);
        WaterAlarmTransport::ttsPayload($content);
        $this->device($villageId, $id);
        Db::transaction(function () use ($villageId, $id, $content) {
            Db::name('water_alarm_device')->where(['village_id' => $villageId, 'id' => $id])->lock(true)->find();
            $where = ['village_id' => $villageId, 'device_id' => $id];
            if (Db::name('water_alarm_text')->where($where)->where('content', $content)->find()) { throw new \InvalidArgumentException('该文字已添加，请直接点击播报'); }
            if (Db::name('water_alarm_text')->where($where)->count() >= 100) { throw new \InvalidArgumentException('每台设备最多保存100条播报文字'); }
            Db::name('water_alarm_text')->insert($where + ['content' => $content, 'create_time' => time()]);
        });
        return $this->texts($villageId, $id);
    }

    public function deleteText($villageId, $id, $textId)
    {
        $this->device($villageId, $id);
        $deleted = Db::name('water_alarm_text')->where([
            'village_id' => $villageId, 'device_id' => $id, 'id' => (int)$textId,
        ])->delete();
        if (!$deleted) { throw new \InvalidArgumentException('文字不存在、已删除或无权操作，请刷新列表'); }
        return $this->texts($villageId, $id);
    }

    private function text($villageId, $id, $textId)
    {
        $text = Db::name('water_alarm_text')->where(['village_id' => $villageId, 'device_id' => $id, 'id' => (int)$textId])->find();
        if (!$text) { throw new \InvalidArgumentException('播报文字不存在或无权访问'); }
        WaterAlarmTransport::ttsPayload($text['content']);
        return $text;
    }

    public function syncLeakFlash($wet)
    {
        $device = Db::name('water_alarm_device')->where('endpoint', $this->endpoint())->find();
        if (!$device) { throw new \RuntimeException('请先在水浸管理中添加默认喇叭设备'); }
        return $this->operate($device['village_id'], $device['id'], $wet ? 'leak_flash_start' : 'leak_flash_stop', null, 0, 0);
    }

    public function speakLeak($villageId, $id, $content)
    {
        WaterAlarmTransport::ttsPayload($content);
        return $this->operate($villageId, $id, 'tts_start', null, 0, 0, 0, $content);
    }

    public function operate($villageId, $id, $action, $volume, $operatorId, $role, $textId = 0, $leakContent = null)
    {
        if (!in_array($action, ['refresh', 'alarm_start', 'stop', 'flash_start', 'flash_stop', 'volume', 'tts_start', 'leak_flash_start', 'leak_flash_stop'], true)) { throw new \InvalidArgumentException('不支持的设备操作'); }
        $leakSync = in_array($action, ['leak_flash_start', 'leak_flash_stop'], true);
        if ($action === 'volume') { $volume = self::volume($volume); }
        $this->device($villageId, $id);
        $text = $action === 'tts_start' ? ($leakContent !== null ? ['id'=>0, 'content'=>$leakContent] : $this->text($villageId, $id, $textId)) : null;
        if ($text) { WaterAlarmTransport::ttsPayload($text['content']); }
        // MySQL advisory lock serializes the physical endpoint across PHP workers.
        $lock = 'water_alarm_' . sha1($this->endpoint());
        $acquired = Db::query('SELECT GET_LOCK(?, ?) AS acquired', [$lock, $leakSync ? 10 : 0]);
        if (empty($acquired[0]['acquired'])) { throw new \InvalidArgumentException('设备正在执行操作，请稍后重试'); }
        $transport = null;
        $logId = 0;
        try {
            $device = $this->device($villageId, $id);
            if ($action !== 'refresh') {
                // Persist before touching hardware: interrupted requests remain "pending".
                $logId = Db::name('water_alarm_log')->insertGetId(['village_id' => $villageId, 'device_id' => $id,
                    'operator_id' => $operatorId, 'operator_role' => $role, 'action' => $action, 'result' => 'pending',
                    'message' => mb_substr(($text ? '文字#' . $text['id'] . '：' . $text['content'] . '；' : '') . '指令执行中，请刷新设备状态', 0, 255, 'UTF-8'), 'create_time' => time()]);
                Db::name('water_alarm_device')->where('id', $id)->update(['state_json' => null, 'last_error' => '指令执行中，等待设备确认']);
            }
            $ok = false;
            $message = '';
            $state = null;
            try {
                $transport = $this->transport();
                switch ($action) {
                    case 'leak_flash_start':
                    case 'leak_flash_stop':
                        $current = $transport->readState(true);
                        if ($action === 'leak_flash_start') {
                            if (($current['led'] & 255) !== 1) { $transport->writeRegisters(2, [1]); }
                        } else {
                            if ($current['tts_led'] !== 0) { $transport->writeSingle(0x23, 0); }
                            if ($current['led'] !== 0) { $transport->writeRegisters(2, [0]); }
                        }
                        break;
                    case 'alarm_start':
                        $transport->stopTts();
                        $transport->writeRegisters(1, [max(1, (int)$device['volume']), 1, 0xff01]); break;
                    case 'tts_start':
                        $transport->stopTts();
                        $transport->writeRegisters(2, [0, 0]);
                        $transport->speak($text['content'], max(1, (int)$device['volume'])); break;
                    case 'stop':
                        // Still try stopping ordinary sound/light if the TTS stop fails.
                        $stopError = null;
                        try { $transport->stopTts(); } catch (\RuntimeException $e) { $stopError = $e; }
                        $transport->writeRegisters(2, [0, 0]);
                        if ($stopError) { throw $stopError; }
                        break;
                    case 'flash_start':
                        $transport->stopTts();
                        $transport->writeRegisters(2, [1, 0]); break;
                    case 'flash_stop':
                        $transport->writeSingle(0x23, 0);
                        $transport->writeRegisters(2, [0]); break;
                    case 'volume':
                        $transport->writeSingle(0x22, $volume);
                        $transport->setVolume($volume); break;
                }
                $state = $transport->readState(true);
                if ($action === 'tts_start' && $state['tts_status'] === 1) {
                    // Allow the synthesizer a brief startup interval; never resend text.
                    usleep(200000);
                    $state = $transport->readState(true);
                }
                if ($action === 'tts_start' && $state['tts_status'] === 0) { throw new \RuntimeException('TTS模块返回未初始化，未确认播报成功，请检查设备TTS模块状态'); }
                if (($action === 'alarm_start' && ($state['tts_active'] || $state['track'] !== 0xff01 || ($state['led'] & 255) !== 1 || $state['volume'] < 1 || $state['volume'] > max(1, (int)$device['volume']))) ||
                    ($action === 'tts_start' && (!$state['tts_active'] || $state['track'] !== 0 || $state['tts_volume'] < 1 || $state['tts_volume'] > max(1, (int)$device['volume']) || ($state['tts_led'] & 255) !== 1)) ||
                    ($action === 'stop' && ($state['alarm_active'] || $state['flash_active'])) ||
                    ($action === 'flash_start' && (($state['led'] & 255) !== 1 || $state['alarm_active'])) ||
                    ($action === 'flash_stop' && $state['flash_active']) ||
                    ($action === 'leak_flash_start' && ($state['led'] & 255) !== 1) ||
                    ($action === 'leak_flash_stop' && $state['flash_active']) ||
                    ($action === 'volume' && ($state['volume'] === 0 || $state['volume'] > $volume || $state['tts_volume'] === 0 || $state['tts_volume'] > $volume))) {
                    throw new \RuntimeException('设备已回复，但读回状态与操作不一致，请刷新检查');
                }
                $ok = true;
                $message = $action === 'volume' ? '音量已设置，当前实际档位：' . $state['volume'] : ($action === 'tts_start' ? '文字已开始循环播报，请手动停止' : '设备状态已确认');
            } catch (\RuntimeException $e) { $message = $e->getMessage(); }
            $update = ['update_time' => time(), 'last_error' => $ok ? '' : $message, 'state_json' => $ok ? json_encode($state) : null];
            if ($ok) {
                $update['last_seen'] = time();
                // Preserve the lowest demo default until explicitly adjusting volume;
                // a passive refresh must not silently choose the device's old loud setting.
                if ($action === 'volume') { $update['volume'] = max(1, $state['volume']); }
            }
            Db::transaction(function () use ($id, $update, $logId, $ok, $message, $text) {
                Db::name('water_alarm_device')->where('id', $id)->update($update);
                if ($logId) { Db::name('water_alarm_log')->where('id', $logId)->update(['result' => $ok ? 'success' : 'unknown', 'message' => mb_substr(($text ? '文字#' . $text['id'] . '：' . $text['content'] . '；' : '') . $message, 0, 255, 'UTF-8')]); }
            });
            return ['ok' => $ok, 'message' => $message, 'device' => $this->present($this->device($villageId, $id))];
        } finally {
            if ($transport) { $transport->close(); }
            Db::query('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }

    public function logs($villageId, $id)
    {
        $this->device($villageId, $id);
        return ['list' => Db::name('water_alarm_log')->where(['village_id' => $villageId, 'device_id' => $id])->order('id desc')->limit(50)->select()->toArray()];
    }
}
