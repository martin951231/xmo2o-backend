<?php
namespace app\community\model\service;

use think\facade\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class VisitorRegisterService
{
    private $wechat;
    private $siteUrl;
    public function __construct($wechat = null, $siteUrl = null) { $this->wechat = $wechat ?: new VisitorRegisterWechatService(); $this->siteUrl = $siteUrl; }

    private function definition($type)
    {
        VisitorRegisterValidation::type($type);
        $definition = config('visitor_register.forms.' . $type);
        if (!$definition || empty($definition['page'])) { throw new \InvalidArgumentException('固定登记页面尚未配置'); }
        return $definition;
    }

    private function initialize($villageId)
    {
        if ($villageId <= 0) { throw new \InvalidArgumentException('小区参数无效'); }
        $table = Db::name('visitor_register_config')->getTable();
        Db::execute('INSERT IGNORE INTO `' . $table . '` (village_id,appid,secret_encrypted,version,update_time) VALUES (?,\'\',\'\',1,?)', [$villageId, time()]);
        $table = Db::name('visitor_register_form')->getTable();
        foreach (['visitor', 'vehicle'] as $type) {
            Db::execute('INSERT IGNORE INTO `' . $table . '` (village_id,form_type) VALUES (?,?)', [$villageId, $type]);
        }
    }

    public function configInfo($villageId)
    {
        $this->initialize($villageId);
        $config = Db::name('visitor_register_config')->where('village_id', $villageId)->find();
        return ['appid' => $config['appid'], 'has_secret' => $config['secret_encrypted'] !== ''];
    }

    public function saveConfig($villageId, $params)
    {
        $this->initialize($villageId);
        $appid = $params['appid'] ?? '';
        $secret = $params['appsecret'] ?? '';
        if (!is_string($appid) || !preg_match('/^wx[a-fA-F0-9]{16}$/D', $appid) || !is_string($secret)) { throw new \InvalidArgumentException('小程序配置格式不正确'); }
        if ($secret !== '' && !preg_match('/^[a-zA-Z0-9]{32}$/D', $secret)) { throw new \InvalidArgumentException('AppSecret格式不正确'); }
        Db::transaction(function () use ($villageId, $appid, $secret) {
            $old = Db::name('visitor_register_config')->where('village_id', $villageId)->lock(true)->find();
            if ($secret === '' && ($old['appid'] !== $appid || !$old['secret_encrypted'])) { throw new \InvalidArgumentException('请填写AppSecret'); }
            $encrypted = $secret === '' ? $old['secret_encrypted'] : $this->wechat->encrypt($secret);
            Db::name('visitor_register_config')->where('village_id', $villageId)->update(['appid' => $appid, 'secret_encrypted' => $encrypted, 'version' => $old['version'] + 1, 'update_time' => time()]);
            if ($old['appid'] !== $appid) {
                Db::name('visitor_register_form')->where('village_id', $villageId)->update(['status' => 'draft', 'qrcode_path' => '', 'qrcode_appid' => '', 'qrcode_generated_at' => 0, 'published_at' => 0]);
            }
            $this->wechat->clear($old);
        });
        return $this->configInfo($villageId);
    }

    private function configured($villageId)
    {
        $config = Db::name('visitor_register_config')->where('village_id', $villageId)->find();
        if (!$config || !$config['appid'] || !$config['secret_encrypted']) { throw new \InvalidArgumentException('请先配置微信小程序'); }
        return $config;
    }

    public function testConnection($villageId)
    {
        $this->wechat->token($this->configured($villageId), true);
        return ['message' => '连接成功'];
    }

    private function root() { return dirname(__DIR__, 5); }
    private function qrImage($path)
    {
        if (!preg_match('#^/upload/visitor_register/[a-f0-9]{32}\.(png|jpg)$#D', $path)) { return ''; }
        $encoded = Db::name('visitor_register_qrcode')->where('qrcode_path', $path)->value('image_base64');
        if (!$encoded && is_file($this->root() . $path)) {
            $raw = file_get_contents($this->root() . $path);
            $info = @getimagesizefromstring($raw);
            if (!$info || !in_array($info['mime'], ['image/png', 'image/jpeg'], true)) { return ''; }
            $encoded = base64_encode($raw);
            // Backfill existing images while they are still available locally.
            $table = Db::name('visitor_register_qrcode')->getTable();
            Db::execute('INSERT IGNORE INTO `' . $table . '` (qrcode_path,image_base64) VALUES (?,?)', [$path, $encoded]);
        }
        if (!$encoded) { return ''; }
        $mime = substr($path, -4) === '.png' ? 'image/png' : 'image/jpeg';
        return 'data:' . $mime . ';base64,' . $encoded;
    }

    private function validQr($form, $config)
    {
        return $form && $config && $config['appid'] && $form['qrcode_appid'] === $config['appid'] &&
            $this->qrImage($form['qrcode_path']) !== '';
    }

    public function forms($villageId)
    {
        $this->initialize($villageId);
        $config = Db::name('visitor_register_config')->where('village_id', $villageId)->find();
        $rows = Db::name('visitor_register_form')->where('village_id', $villageId)->order('id')->select()->toArray();
        foreach ($rows as &$row) {
            $row = array_merge($row, $this->definition($row['form_type']));
            $row['qrcode_valid'] = (bool)$this->validQr($row, $config);
            $row['qrcode_url'] = $row['qrcode_valid'] ? $this->qrImage($row['qrcode_path']) : '';
            unset($row['qrcode_appid'], $row['qrcode_path']);
        }
        return $rows;
    }

    public function generateQr($villageId, $type, $regenerate = false)
    {
        $definition = $this->definition($type);
        $this->initialize($villageId);
        $config = $this->configured($villageId);
        $form = Db::name('visitor_register_form')->where(['village_id' => $villageId, 'form_type' => $type])->find();
        if (!$regenerate && $this->validQr($form, $config)) { return $this->forms($villageId); }
        $image = $this->wechat->qrcode($config, $definition['page'], 'v=' . $villageId . '&t=' . $type);
        $directory = $this->root() . '/upload/visitor_register';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) { throw new \RuntimeException('二维码目录创建失败'); }
        $path = '/upload/visitor_register/' . bin2hex(random_bytes(16)) . '.' . $image['extension'];
        if (file_put_contents($this->root() . $path, $image['bytes'], LOCK_EX) !== strlen($image['bytes'])) { throw new \RuntimeException('二维码保存失败'); }
        try {
            Db::transaction(function () use ($villageId, $type, $config, $path, $image) {
                $current = Db::name('visitor_register_config')->where('village_id', $villageId)->lock(true)->find();
                if ($current['version'] !== $config['version']) { throw new \InvalidArgumentException('小程序配置已变更，请重新生成二维码'); }
                Db::name('visitor_register_qrcode')->insert(['qrcode_path' => $path, 'image_base64' => base64_encode($image['bytes'])]);
                Db::name('visitor_register_form')->where(['village_id' => $villageId, 'form_type' => $type])->update(['qrcode_path' => $path, 'qrcode_appid' => $config['appid'], 'qrcode_generated_at' => time()]);
            });
        } catch (\Throwable $e) { @unlink($this->root() . $path); throw $e; }
        return $this->forms($villageId);
    }

    public function changeStatus($villageId, $type, $status)
    {
        $this->definition($type);
        $this->initialize($villageId);
        if (!in_array($status, ['published', 'disabled'], true)) { throw new \InvalidArgumentException('状态无效'); }
        Db::transaction(function () use ($villageId, $type, $status) {
            $config = Db::name('visitor_register_config')->where('village_id', $villageId)->lock(true)->find();
            $where = ['village_id' => $villageId, 'form_type' => $type];
            $form = Db::name('visitor_register_form')->where($where)->lock(true)->find();
            if ($status === 'published') {
                if (!$this->validQr($form, $config)) { throw new \InvalidArgumentException('当前表单还没有二维码，需要先生成二维码。'); }
                $this->wechat->decrypt($this->configured($villageId)['secret_encrypted']);
            }
            $update = ['status' => $status];
            if ($status === 'published') { $update['published_at'] = time(); }
            Db::name('visitor_register_form')->where($where)->update($update);
        });
        return $this->forms($villageId);
    }

    public function publicStatus($villageId, $type)
    {
        $definition = $this->definition($type);
        $form = Db::name('visitor_register_form')->where(['village_id' => $villageId, 'form_type' => $type])->find();
        $status = $form ? $form['status'] : 'draft';
        return ['name' => $definition['name'], 'code' => $definition['code'], 'status' => $status, 'theme' => $this->registrationTheme($villageId), 'message' => $status === 'published' ? '' : ($status === 'disabled' ? '当前登记功能已停止使用' : '当前登记功能尚未发布')];
    }

    private function registrationTheme($villageId)
    {
        // Same fields and precedence as HouseApi's homepage; always use the scanned village.
        // Read fresh so homepage style edits are visible without a stale village cache.
        $info = Db::name('house_village_info')->where('village_id', $villageId)
            ->field('index_header_bg_color,index_header_bg_image,index_title_color')->find() ?: [];
        $background = $info['index_header_bg_color'] ?? '';
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/D', $background)) {
            $background = Db::name('config')->where('name', 'mobile_head_color')->value('value') ?: '';
        }
        $title = $info['index_title_color'] ?? '';
        return [
            'background_color' => preg_match('/^#[0-9A-Fa-f]{6}$/D', $background) ? strtoupper($background) : '#FFFFFF',
            'background_image' => !empty($info['index_header_bg_image']) ? replace_file_domain($info['index_header_bg_image']) : '',
            'title_color' => preg_match('/^#[0-9A-Fa-f]{6}$/D', $title) ? strtoupper($title) : '#000000',
        ];
    }

    public function rateLimit($ip, $submit, $bucket = '')
    {
        $now = time();
        $key = hash('sha256', $bucket . ':' . ($submit ? 'submit:' : 'status:') . $ip . ':' . floor($now / 60));
        $table = Db::name('visitor_register_rate')->getTable();
        Db::execute('INSERT INTO `' . $table . '` (rate_key,hits,expires_at) VALUES (?,1,?) ON DUPLICATE KEY UPDATE hits=hits+1', [$key, $now + 120]);
        $hits = Db::name('visitor_register_rate')->where('rate_key', $key)->value('hits');
        if (intval($hits) > ($submit ? 10 : 120)) { throw new \InvalidArgumentException('提交或访问过于频繁，请稍后重试'); }
        Db::name('visitor_register_rate')->where('expires_at', '<', $now)->limit(100)->delete();
    }

    private function table($type) { VisitorRegisterValidation::type($type); return $type === 'visitor' ? 'visitor_register_person' : 'visitor_register_vehicle'; }

    private function today()
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Shanghai')))->format('Y-m-d');
    }

    private function identityWhere($villageId, $type, $subject)
    {
        return ['village_id' => $villageId, 'form_type' => $type, 'subject_hash' => $subject, 'record_date' => $this->today()];
    }

    private function identityConfig($villageId, $type)
    {
        $this->definition($type);
        $config = $this->configured($villageId);
        $status = Db::name('visitor_register_form')->where(['village_id' => $villageId, 'form_type' => $type])->value('status');
        if ($status !== 'published') { throw new \InvalidArgumentException('当前登记功能未启用或已停止使用'); }
        return $config;
    }

    private function identity($config, $type, $token)
    {
        if (!is_string($token) || strlen($token) > 4096 || $token === '') { throw new \InvalidArgumentException('请重新进入页面完成微信授权'); }
        try { $data = json_decode($this->wechat->decrypt($token), true); }
        catch (\Throwable $e) { throw new \InvalidArgumentException('微信登录已失效，请重新进入登记页面'); }
        if (!is_array($data) || ($data['purpose'] ?? '') !== 'visitor-registration' ||
            intval($data['village_id'] ?? 0) !== intval($config['village_id']) || ($data['type'] ?? '') !== $type ||
            ($data['appid'] ?? '') !== $config['appid'] || intval($data['version'] ?? 0) !== intval($config['version']) ||
            ($data['day'] ?? '') !== $this->today() || intval($data['expires'] ?? 0) <= time() ||
            !preg_match('/^[a-f0-9]{64}$/D', $data['subject'] ?? '')) {
            throw new \InvalidArgumentException('微信登录已失效，请重新进入登记页面');
        }
        return $data;
    }

    public function login($villageId, $type, $params)
    {
        $config = $this->identityConfig($villageId, $type);
        $code = $params['code'] ?? '';
        if (!is_string($code) || $code === '' || strlen($code) > 256) { throw new \InvalidArgumentException('微信登录凭证无效'); }
        $subject = hash('sha256', $config['appid'] . ':' . $this->wechat->openid($config, $code));
        $saved = Db::name('visitor_register_identity')->where($this->identityWhere($villageId, $type, $subject))->find();
        $prefill = [];
        if ($saved) {
            $row = Db::name($this->table($type))->where(['village_id' => $villageId, 'registration_no' => $saved['registration_no']])->find();
            if ($row) {
                // Each scan is a new visit. Never copy entry/exit times or internal identifiers.
                $fields = $type === 'visitor' ? ['visitor_name', 'organization_or_id', 'visit_reason', 'host_name_room', 'remark'] : ['plate_number', 'destination'];
                foreach ($fields as $field) { $prefill[$field] = $row[$field]; }
            }
        }
        $identity = ['purpose' => 'visitor-registration', 'village_id' => intval($villageId), 'type' => $type,
            'appid' => $config['appid'], 'version' => intval($config['version']), 'subject' => $subject,
            'day' => $this->today(), 'expires' => time() + 7200, 'phone' => $saved['verified_phone'] ?? ''];
        return ['auth_token' => $this->wechat->encrypt(json_encode($identity)), 'phone' => $identity['phone'],
            'nickname' => $saved['nickname'] ?? '', 'prefill' => $prefill];
    }

    public function authorizePhone($villageId, $type, $params)
    {
        $config = $this->identityConfig($villageId, $type);
        $identity = $this->identity($config, $type, $params['auth_token'] ?? '');
        if (intval($params['privacy_consent'] ?? 0) !== 1) { throw new \InvalidArgumentException('请阅读并同意个人信息收集告知'); }
        $code = $params['code'] ?? '';
        if (!is_string($code) || $code === '' || strlen($code) > 256) { throw new \InvalidArgumentException('手机号授权凭证无效，请重试'); }
        $identity['phone'] = $this->wechat->phone($config, $code);
        return ['auth_token' => $this->wechat->encrypt(json_encode($identity)), 'phone' => $identity['phone']];
    }

    public function submit($villageId, $type, $params)
    {
        $identity = $this->identity($this->identityConfig($villageId, $type), $type, $params['auth_token'] ?? '');
        if (!preg_match('/^1[3-9][0-9]{9}$/D', $identity['phone'] ?? '')) { throw new \InvalidArgumentException('请先授权获取微信手机号'); }
        $nickname = $params['nickname'] ?? '';
        if (!is_string($nickname) || mb_strlen($nickname, 'UTF-8') > 60 || preg_match('/[<>\x00-\x1F]/u', $nickname)) {
            throw new \InvalidArgumentException('请填写有效的微信昵称');
        }
        $nickname = trim($nickname);
        if (!isset($params['data']) || !is_array($params['data'])) { throw new \InvalidArgumentException('登记数据格式不正确'); }
        $params['data']['phone'] = $identity['phone'];
        $row = VisitorRegisterValidation::data($type, $params['data'] ?? null);
        $requestId = $params['request_id'] ?? '';
        if (!is_string($requestId) || !preg_match('/^[a-zA-Z0-9_-]{16,64}$/D', $requestId)) { throw new \InvalidArgumentException('提交标识无效，请刷新页面'); }
        if (intval($params['privacy_consent'] ?? 0) !== 1) { throw new \InvalidArgumentException('请阅读并同意个人信息收集告知'); }
        $hash = hash('sha256', $identity['subject'] . ':' . $nickname . ':' . json_encode($row, JSON_UNESCAPED_UNICODE));
        return Db::transaction(function () use ($villageId, $type, $row, $requestId, $hash, $params, $identity, $nickname) {
            // Same lock order as disabling and changing AppID prevents submissions after disable.
            $config = Db::name('visitor_register_config')->where('village_id', $villageId)->lock(true)->find();
            $form = Db::name('visitor_register_form')->where(['village_id' => $villageId, 'form_type' => $type])->lock(true)->find();
            if (!$config || !$form || $form['status'] !== 'published') { throw new \InvalidArgumentException('当前登记功能未启用或已停止使用'); }
            $this->identity($config, $type, $params['auth_token']);
            $table = $this->table($type);
            $existing = Db::name($table)->where(['village_id' => $villageId, 'request_id' => $requestId])->find();
            if ($existing) {
                if ($existing['payload_hash'] !== $hash) { throw new \InvalidArgumentException('提交标识已使用，请重新打开登记页面'); }
                return ['registration_no' => $existing['registration_no']];
            }
            $duplicate = Db::name($table)->where(['village_id' => $villageId, 'payload_hash' => $hash])->where('create_time', '>', time() - 60)->find();
            if ($duplicate) { return ['registration_no' => $duplicate['registration_no']]; }
            $row['village_id'] = $villageId;
            $row['request_id'] = $requestId;
            $row['payload_hash'] = $hash;
            $row['registration_no'] = ($type === 'visitor' ? 'VR' : 'VC') . date('YmdHis') . strtoupper(bin2hex(random_bytes(8)));
            $row['create_time'] = time();
            if ($type === 'vehicle') { $row['update_time'] = time(); }
            Db::name($table)->insert($row);
            Db::name('visitor_register_record_profile')->insert([
                'registration_no' => $row['registration_no'], 'village_id' => $villageId, 'nickname' => $nickname,
            ]);
            $where = $this->identityWhere($villageId, $type, $identity['subject']);
            $profile = ['registration_no' => $row['registration_no'], 'nickname' => $nickname, 'verified_phone' => $identity['phone']];
            $saved = Db::name('visitor_register_identity')->where($where)->find();
            if ($saved) { Db::name('visitor_register_identity')->where($where)->update($profile); }
            else { Db::name('visitor_register_identity')->insert(array_merge($where, $profile)); }
            return ['registration_no' => $row['registration_no']];
        });
    }

    public function mobileVisitorDetail($villageId, $registrationNo)
    {
        if (intval($villageId) <= 0 || !is_string($registrationNo) || !preg_match('/^VR[0-9]{14}[A-F0-9]{16}$/D', $registrationNo)) {
            throw new \InvalidArgumentException('登记记录不存在');
        }
        $row = Db::name('visitor_register_person')->where(['village_id' => intval($villageId), 'registration_no' => $registrationNo])
            ->field('registration_no,visit_time,visitor_name,organization_or_id,phone,visit_reason,host_name_room,remark,create_time')->find();
        if (!$row) { throw new \InvalidArgumentException('登记记录不存在'); }
        $row['registration_time'] = (new \DateTimeImmutable('@' . $row['create_time']))->setTimezone(new \DateTimeZone('Asia/Shanghai'))->format('Y-m-d H:i:s');
        unset($row['create_time']);
        return $row;
    }

    public function mobileVisitorRecords($villageId, $params)
    {
        if (intval($villageId) <= 0) { throw new \InvalidArgumentException('请先登录小区'); }
        $page = max(1, intval($params['page'] ?? 1));
        $size = min(50, max(1, intval($params['page_size'] ?? 20)));
        $query = Db::name('visitor_register_person')->alias('r')->where('r.village_id', intval($villageId));
        $date = $params['date'] ?? '';
        if (!is_string($date)) { throw new \InvalidArgumentException('筛选日期格式不正确'); }
        if ($date !== '') {
            VisitorRegisterValidation::date($date . ' 00:00:00', '筛选日期');
            $start = new \DateTimeImmutable($date . ' 00:00:00', new \DateTimeZone('Asia/Shanghai'));
            $query->where('r.create_time', '>=', $start->getTimestamp())->where('r.create_time', '<', $start->modify('+1 day')->getTimestamp());
        }
        $total = (clone $query)->count();
        $profileTable = Db::name('visitor_register_record_profile')->getTable();
        $rows = $query->leftJoin($profileTable . ' p', 'p.registration_no = r.registration_no AND p.village_id = r.village_id')
            ->field('r.registration_no,r.phone,r.create_time,p.nickname,r.visitor_name')
            ->order('r.create_time desc,r.id desc')->page($page, $size)->select()->toArray();
        foreach ($rows as &$row) {
            $visitorName = trim((string)($row['visitor_name'] ?? ''));
            $row['nickname'] = $visitorName !== '' ? $visitorName : ($row['nickname'] ?: '未提供昵称');
            $row['registration_time'] = (new \DateTimeImmutable('@' . $row['create_time']))->setTimezone(new \DateTimeZone('Asia/Shanghai'))->format('Y-m-d H:i:s');
            unset($row['create_time'], $row['visitor_name']);
        }
        unset($row);
        return ['list' => $rows, 'total' => $total, 'has_more' => $page * $size < $total];
    }

    private function query($villageId, $type, $params)
    {
        $query = Db::name($this->table($type))->where('village_id', $villageId);
        foreach (['phone', $type === 'visitor' ? 'visitor_name' : 'plate_number'] as $field) {
            $value = $params[$field] ?? '';
            if (!is_string($value) || mb_strlen($value) > 60) { throw new \InvalidArgumentException('查询条件无效'); }
            if (trim($value) !== '') { $query->where($field, 'like', '%' . addcslashes(trim($value), '%_\\') . '%'); }
        }
        $timeField = $type === 'visitor' ? 'visit_time' : 'entry_time';
        foreach (['date_from' => ['>=', ' 00:00:00'], 'date_to' => ['<=', ' 23:59:59']] as $key => $condition) {
            if (!empty($params[$key])) { $query->where($timeField, $condition[0], VisitorRegisterValidation::date($params[$key] . $condition[1], '筛选日期')); }
        }
        if ($type === 'vehicle' && !empty($params['status'])) {
            if (!in_array($params['status'], ['entered', 'exited'], true)) { throw new \InvalidArgumentException('离场状态无效'); }
            $query->where('status', $params['status']);
        }
        return $query;
    }

    public function records($villageId, $type, $params)
    {
        $query = $this->query($villageId, $type, $params);
        $total = (clone $query)->count();
        $rows = $query->order('id desc')->page(max(1, intval($params['page'] ?? 1)), min(100, max(1, intval($params['page_size'] ?? 20))))->select()->toArray();
        foreach ($rows as &$row) {
            unset($row['payload_hash'], $row['request_id']);
            $row['phone'] = substr($row['phone'], 0, 3) . '****' . substr($row['phone'], -4);
            if (isset($row['organization_or_id'])) {
                $value = $row['organization_or_id'];
                $row['organization_or_id'] = mb_substr($value, 0, 1) . '****' . (mb_strlen($value) > 2 ? mb_substr($value, -1) : '');
            }
        }
        return ['list' => $rows, 'total' => $total];
    }

    public function detail($villageId, $type, $id)
    {
        $row = Db::name($this->table($type))->where(['village_id' => $villageId, 'id' => intval($id)])->find();
        if (!$row) { throw new \InvalidArgumentException('登记记录不存在'); }
        unset($row['payload_hash'], $row['request_id']);
        return $row;
    }

    public function registerExit($villageId, $id, $exit)
    {
        $exit = VisitorRegisterValidation::date($exit, '离开时间');
        return Db::transaction(function () use ($villageId, $id, $exit) {
            $where = ['village_id' => $villageId, 'id' => intval($id)];
            $row = Db::name('visitor_register_vehicle')->where($where)->lock(true)->find();
            if (!$row) { throw new \InvalidArgumentException('登记记录不存在'); }
            if ($row['status'] !== 'entered') { throw new \InvalidArgumentException('该车辆已经离场，不能重复登记'); }
            if ($exit < $row['entry_time']) { throw new \InvalidArgumentException('离开时间不能早于进入时间'); }
            Db::name('visitor_register_vehicle')->where($where)->update(['exit_time' => $exit, 'status' => 'exited', 'update_time' => time()]);
            return ['message' => '登记离场成功'];
        });
    }

    public function export($villageId, $type, $params)
    {
        $query = $this->query($villageId, $type, $params);
        if ((clone $query)->count() > 5000) { throw new \InvalidArgumentException('单次最多导出5000条，请缩小日期范围'); }
        $fields = $type === 'visitor'
            ? ['registration_no' => '登记编号', 'visit_time' => '来访时间', 'visitor_name' => '访客姓名', 'organization_or_id' => '单位或有效证件号码', 'phone' => '联系电话', 'visit_reason' => '事由', 'host_name_room' => '被访者姓名、房号', 'remark' => '备注', 'create_time' => '提交时间']
            : ['registration_no' => '登记编号', 'plate_number' => '车牌号码', 'entry_time' => '进入时间', 'exit_time' => '离开时间', 'destination' => '车辆去向', 'phone' => '联系电话', 'status' => '当前状态', 'create_time' => '提交时间'];
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->fromArray(array_values($fields));
        $index = 2;
        foreach ($query->order('id desc')->limit(5000)->select()->toArray() as $row) {
            $column = 1;
            foreach ($fields as $key => $label) {
                $value = $row[$key];
                if ($key === 'create_time') { $value = date('Y-m-d H:i:s', $value); }
                if ($key === 'status') { $value = $value === 'entered' ? '未离场' : '已离场'; }
                // Explicit strings prevent spreadsheet formula injection and ID truncation.
                $sheet->setCellValueExplicitByColumnAndRow($column++, $index, (string)$value, DataType::TYPE_STRING);
            }
            $index++;
        }
        // This project's PhpSpreadsheet release accepts filenames, not stream resources.
        $temporaryFile = tempnam(sys_get_temp_dir(), 'visitor_export_');
        if ($temporaryFile === false) { throw new \RuntimeException('导出临时文件创建失败'); }
        try {
            (new Xlsx($book))->save($temporaryFile);
            return ['filename' => $type . '-' . date('YmdHis') . '.xlsx', 'content' => base64_encode(file_get_contents($temporaryFile))];
        } finally { @unlink($temporaryFile); $book->disconnectWorksheets(); }
    }
}
