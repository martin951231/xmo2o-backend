<?php
namespace app\community\model\service;

use think\facade\Db;

class WaterAlarmSensorService
{
    public function sensors($villageId)
    {
        return ['list' => Db::name('water_alarm_sensor')->where(['village_id'=>$villageId, 'enabled'=>1])->order('id desc')->select()->toArray()];
    }

    public function generate($villageId)
    {
        // Reserve IDs on the server: clients cannot save an arbitrary manual ID.
        $id = strtoupper(bin2hex(random_bytes(12)));
        Db::name('water_alarm_sensor')->insert(['village_id'=>$villageId, 'sensor_id'=>$id, 'create_time'=>time()]);
        return ['sensor_id'=>$id];
    }

    public function save($villageId, $sensorId, $text)
    {
        if (!is_string($text)) { throw new \InvalidArgumentException('请输入报警词'); }
        $text = trim($text);
        WaterAlarmTransport::ttsPayload($text);
        $where = ['village_id'=>$villageId, 'sensor_id'=>(string)$sensorId];
        if (!Db::name('water_alarm_sensor')->where($where)->find()) { throw new \InvalidArgumentException('请先点击生成随机ID'); }
        Db::name('water_alarm_sensor')->where($where)->update(['alarm_text'=>$text, 'enabled'=>1]);
        return $this->sensors($villageId);
    }

    protected function alarmService() { return new WaterAlarmService(); }

    public function report($raw)
    {
        $data = WaterLeakReport::decode($raw);
        if (!is_string($data['ID'] ?? null)) { throw new \InvalidArgumentException('ID必须为已添加的传感器字符串ID'); }
        $sensor = Db::name('water_alarm_sensor')->where(['sensor_id'=>$data['ID'], 'enabled'=>1])->find();
        if (!$sensor) { throw new \InvalidArgumentException('传感器未登记'); }
        $villageId = (int)$sensor['village_id'];
        if (isset($data['village_id']) && (string)$data['village_id'] !== (string)$villageId) { throw new \InvalidArgumentException('传感器不属于该小区'); }
        $state = WaterLeakReport::parse($raw, $sensor['sensor_id']);
        $lock = 'water_leak_village_' . $villageId;
        $locked = Db::query('SELECT GET_LOCK(?, 10) AS acquired', [$lock]);
        if (empty($locked[0]['acquired'])) { throw new \RuntimeException('联动处理中，请重试'); }
        try {
            Db::name('water_alarm_sensor')->where(['sensor_id'=>$sensor['sensor_id'], 'village_id'=>$villageId])->update(['last_state'=>$state, 'last_seen'=>time()]);
            // Keep the oldest registered wet sensor playing until it recovers;
            // a dry report from another sensor must never silence this alarm.
            $wet = Db::name('water_alarm_sensor')->where(['village_id'=>$villageId, 'enabled'=>1, 'last_state'=>1])->order('id asc')->find();
            $text = $wet ? $wet['alarm_text'] : '';
            $signature = $wet ? hash('sha256', $wet['sensor_id'] . ':' . $text) : 'dry';
            $link = Db::name('water_alarm_linkage')->where('village_id', $villageId)->find();
            $device = Db::name('water_alarm_device')->where('village_id', $villageId)->find();
            if (!$device) { throw new \RuntimeException('该小区尚未添加喇叭'); }
            $service = $this->alarmService();
            $result = $service->operate($villageId, $device['id'], 'refresh', null, 0, 0);
            if (!$result['ok']) { return $result; }
            $current = $result['device']['state'];
            $matches = $wet ? ($current['tts_active'] && ($current['tts_led'] & 255) === 1) : (!$current['alarm_active'] && !$current['flash_active']);
            if (!$link || $link['signature'] !== $signature || !$matches) {
                // Clear confirmation before I/O so failure is retried on next report.
                if (!$link) { Db::name('water_alarm_linkage')->insert(['village_id'=>$villageId]); }
                else { Db::name('water_alarm_linkage')->where('village_id', $villageId)->update(['signature'=>'']); }
                $result = $wet ? $service->speakLeak($villageId, $device['id'], $text) : $service->operate($villageId, $device['id'], 'stop', null, 0, 0);
                if ($result['ok']) { Db::name('water_alarm_linkage')->where('village_id', $villageId)->update(['signature'=>$signature]); }
            }
            return $result;
        } finally { Db::query('SELECT RELEASE_LOCK(?)', [$lock]); }
    }
}
