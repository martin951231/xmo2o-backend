<?php

namespace app\community\model\service\Device;

use app\community\model\db\Device\HouseSmartAccessDevice;
use app\community\model\db\Device\HouseSmartAccessRecords;
use app\community\model\db\Device\HouseSmartAccessSensors;
use think\facade\Db;

class HouseAccessRecordsService
{
    /**
     * @var HouseSmartAccessRecords
     */
    protected $recordModel;

    public function __construct()
    {
        $this->recordModel = new HouseSmartAccessRecords();
    }

    public function recordsIndex($village_id, $params = [])
    {
        $page = max(1, intval($params['page'] ?? 1));
        $pageSize = max(1, min(500, intval($params['pageSize'] ?? 10)));

        $countQuery = $this->buildQuery($village_id, $params);
        $count = $countQuery->count();

        $listQuery = $this->buildQuery($village_id, $params);
        $list = $listQuery
            ->field('r.id,r.type,r.reader,r.status,r.card_id,r.MAC as MAC,r.serial_id,r.access_time,s.code as sensor_code,d.serial_num as device_serial_num')
            ->order('r.id DESC')
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        if (empty($list)) {
            $list = [];
        }

        foreach ($list as &$item) {
            $item = $this->formatItem($item);
        }

        return [
            'list' => $list,
            'count' => $count,
            'pageSize' => $pageSize,
        ];
    }

    public function recordsAdd($village_id, $params = [])
    {
        $data = $this->buildSaveData($village_id, $params);
        $id = $this->recordModel->add($data);
        return ['id' => $id];
    }

    public function recordsEdit($village_id, $params = [])
    {
        $id = intval($params['id'] ?? 0);
        if ($id <= 0) {
            throw new \Exception('记录ID不能为空');
        }

        $detail = $this->getRecordDetail($village_id, $id);
        if (empty($detail)) {
            throw new \Exception('记录信息不存在');
        }

        $data = $this->buildSaveData($village_id, $params);
        $this->recordModel->updateThis([['id', '=', $id]], $data);

        return ['id' => $id];
    }

    public function recordsDel($village_id, $id)
    {
        $id = intval($id);
        if ($id <= 0) {
            throw new \Exception('记录ID不能为空');
        }

        $detail = $this->getRecordDetail($village_id, $id);
        if (empty($detail)) {
            throw new \Exception('记录信息不存在');
        }

        $this->recordModel->where([['id', '=', $id]])->delete();
        return true;
    }

    protected function buildQuery($village_id, $params = [])
    {
        $query = Db::name('house_smart_access_records')->alias('r')
            ->leftJoin('house_smart_access_sensors s', 's.id = r.card_id')
            ->leftJoin('house_smart_access_device d', 'd.id = r.serial_id')
            ->where(function ($subQuery) use ($village_id) {
                $subQuery->where('s.village_id', '=', intval($village_id))
                    ->whereOr('d.village_id', '=', intval($village_id));
            });

        if ($this->hasValue($params, 'type')) {
            $query->where('r.type', '=', intval($params['type']));
        }
        if ($this->hasValue($params, 'reader')) {
            $query->where('r.reader', '=', intval($params['reader']));
        }
        if ($this->hasValue($params, 'status')) {
            $query->where('r.status', '=', intval($params['status']));
        }
        if ($this->hasValue($params, 'card_id')) {
            $query->where('r.card_id', '=', intval($params['card_id']));
        }
        if ($this->hasValue($params, 'serial_id')) {
            $query->where('r.serial_id', '=', intval($params['serial_id']));
        }
        if ($this->hasValue($params, 'MAC')) {
            $query->where('r.MAC', 'like', '%' . trim((string)$params['MAC']) . '%');
        }

        return $query;
    }

    protected function buildSaveData($village_id, $params = [])
    {
        $allowTypeList = [0, 1, 2, 3, 6, 9, 10, 11, 12, 13, 23, 26, 28, 30, 100, 101];
        $type = intval($params['type'] ?? 0);
        if (!in_array($type, $allowTypeList, true)) {
            throw new \Exception('数据类型参数错误');
        }

        $reader = intval($params['reader'] ?? 0);
        if (!in_array($reader, [0, 1], true)) {
            throw new \Exception('进出方向参数错误');
        }

        $status = intval($params['status'] ?? 0);
        if (!in_array($status, [0, 1], true)) {
            throw new \Exception('开门状态参数错误');
        }

        $card_id = intval($params['card_id'] ?? $params['sensors_id'] ?? 0);
        $serial_id = intval($params['serial_id'] ?? $params['device_id'] ?? 0);
        $mac = strtoupper(trim((string)($params['MAC'] ?? $params['mac'] ?? '')));
        $mac = preg_replace('/\s+/', '', $mac);
        if (strlen($mac) > 50) {
            throw new \Exception('设备MAC长度不能超过50位');
        }

        $access_time = trim((string)($params['access_time'] ?? ''));
        if ($access_time === '') {
            throw new \Exception('开门时间不能为空');
        }
        if (strlen($access_time) > 50) {
            throw new \Exception('开门时间长度不能超过50位');
        }

        if ($card_id > 0) {
            $sensorWhere = [
                ['id', '=', $card_id],
            ];
            if ($village_id > 0) {
                $sensorWhere[] = ['village_id', '=', intval($village_id)];
            }
            $sensorInfo = (new HouseSmartAccessSensors())->getOne($sensorWhere, 'id');
            if ($sensorInfo && !is_array($sensorInfo)) {
                $sensorInfo = $sensorInfo->toArray();
            }
            if (empty($sensorInfo)) {
                throw new \Exception('所选感应器不存在');
            }
        }

        if ($serial_id > 0) {
            $deviceWhere = [
                ['id', '=', $serial_id],
            ];
            if ($village_id > 0) {
                $deviceWhere[] = ['village_id', '=', intval($village_id)];
            }
            $deviceInfo = (new HouseSmartAccessDevice())->getOne($deviceWhere, 'id');
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
            if (empty($deviceInfo)) {
                throw new \Exception('所选门禁设备不存在');
            }
        }

        return [
            'type' => $type,
            'reader' => $reader,
            'status' => $status,
            'card_id' => $card_id > 0 ? $card_id : null,
            'MAC' => $mac,
            'serial_id' => $serial_id > 0 ? $serial_id : null,
            'access_time' => $access_time,
        ];
    }

    protected function getRecordDetail($village_id, $id)
    {
        $detail = Db::name('house_smart_access_records')->alias('r')
            ->leftJoin('house_smart_access_sensors s', 's.id = r.card_id')
            ->leftJoin('house_smart_access_device d', 'd.id = r.serial_id')
            ->where('r.id', '=', intval($id))
            ->where(function ($subQuery) use ($village_id) {
                $subQuery->where('s.village_id', '=', intval($village_id))
                    ->whereOr('d.village_id', '=', intval($village_id));
            })
            ->field('r.id,r.type,r.reader,r.status,r.card_id,r.MAC as MAC,r.serial_id,r.access_time')
            ->find();

        if ($detail && !is_array($detail)) {
            $detail = $detail->toArray();
        }

        return $detail ?: [];
    }

    protected function formatItem($item)
    {
        $item['id'] = intval($item['id'] ?? 0);
        $item['type'] = intval($item['type'] ?? 0);
        $item['reader'] = intval($item['reader'] ?? 0);
        $item['status'] = intval($item['status'] ?? 0);
        $item['card_id'] = intval($item['card_id'] ?? 0);
        $item['serial_id'] = intval($item['serial_id'] ?? 0);
        $item['sensor_code'] = (string)($item['sensor_code'] ?? '');
        $item['device_serial_num'] = (string)($item['device_serial_num'] ?? '');
        $item['MAC'] = (string)($item['MAC'] ?? '');
        $item['access_time'] = (string)($item['access_time'] ?? '');
        return $item;
    }

    protected function hasValue($params, $key)
    {
        return isset($params[$key]) && $params[$key] !== '' && $params[$key] !== null;
    }
}