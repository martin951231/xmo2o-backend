<?php

namespace app\community\model\service\Device;

use app\community\model\db\Device\HouseSmartAccessRecords;
use app\community\model\db\Device\HouseSmartAccessSensors;

class HouseAccessSensorsService
{
    /**
     * @var HouseSmartAccessSensors
     */
    protected $sensorModel;

    public function __construct()
    {
        $this->sensorModel = new HouseSmartAccessSensors();
    }

    public function sensorsList($village_id, $params = [])
    {
        $page = max(1, intval($params['page'] ?? 1));
        $pageSize = max(1, min(500, intval($params['pageSize'] ?? 10)));
        $where = [
            ['village_id', '=', intval($village_id)],
        ];

        $code = trim((string)($params['code'] ?? ''));
        if ($code !== '') {
            $where[] = ['code', 'like', '%' . $code . '%'];
        }

        $parking_num = trim((string)($params['parking_num'] ?? ''));
        if ($parking_num !== '') {
            $where[] = ['parking_num', 'like', '%' . $parking_num . '%'];
        }

        if ($this->hasValue($params, 'enable')) {
            $where[] = ['enable', '=', intval($params['enable'])];
        }

        $list = $this->sensorModel->where($where)->order('id DESC')->page($page, $pageSize)->select();
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        if (empty($list)) {
            $list = [];
        }

        foreach ($list as &$item) {
            $item = $this->formatItem($item);
        }

        return [
            'list' => $list,
            'count' => $this->sensorModel->getCount($where),
            'pageSize' => $pageSize,
        ];
    }

    public function sensorsAdd($village_id, $params = [])
    {
        $data = $this->buildSaveData($village_id, $params);
        $now = date('Y-m-d H:i:s');
        $data['createdAt'] = $now;
        $data['updatedAt'] = $now;
        $id = $this->sensorModel->add($data);
        return ['id' => $id];
    }

    public function sensorsEdit($village_id, $params = [])
    {
        $id = intval($params['id'] ?? 0);
        if ($id <= 0) {
            throw new \Exception('感应器ID不能为空');
        }

        $where = [
            ['id', '=', $id],
            ['village_id', '=', intval($village_id)],
        ];
        $detail = $this->sensorModel->getOne($where);
        if ($detail && !is_array($detail)) {
            $detail = $detail->toArray();
        }
        if (empty($detail)) {
            throw new \Exception('感应器信息不存在');
        }

        $data = $this->buildSaveData($village_id, $params, $id);
        $data['updatedAt'] = date('Y-m-d H:i:s');
        $this->sensorModel->updateThis($where, $data);

        return ['id' => $id];
    }

    public function sensorsDel($village_id, $id)
    {
        $id = intval($id);
        if ($id <= 0) {
            throw new \Exception('感应器ID不能为空');
        }

        $where = [
            ['id', '=', $id],
            ['village_id', '=', intval($village_id)],
        ];
        $detail = $this->sensorModel->getOne($where);
        if ($detail && !is_array($detail)) {
            $detail = $detail->toArray();
        }
        if (empty($detail)) {
            throw new \Exception('感应器信息不存在');
        }

        $recordCount = (new HouseSmartAccessRecords())->getCount([['card_id', '=', $id]]);
        if ($recordCount > 0) {
            throw new \Exception('当前感应器已有关联出入记录，暂不支持删除');
        }

        $this->sensorModel->where($where)->delete();
        return true;
    }

    public function getEnabledSensorByCode($code, $village_id = 0)
    {
        $code = trim((string)$code);
        if ($code === '') {
            return [];
        }

        $where = [
            ['code', '=', $code],
            ['enable', '=', 1],
        ];
        if ($village_id > 0) {
            $where[] = ['village_id', '=', intval($village_id)];
        }
        $detail = $this->sensorModel->getOne($where);
        if ($detail && !is_array($detail)) {
            $detail = $detail->toArray();
        }

        return $detail ?: [];
    }

    public function sensorsOptions($village_id, $pageSize = 500)
    {
        $pageSize = max(1, min(500, intval($pageSize)));
        $list = $this->sensorModel
            ->where([['village_id', '=', intval($village_id)]])
            ->field('id,code,parking_num,enable')
            ->order('id DESC')
            ->limit($pageSize)
            ->select();

        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        if (empty($list)) {
            $list = [];
        }

        foreach ($list as &$item) {
            $item = [
                'id' => intval($item['id'] ?? 0),
                'code' => (string)($item['code'] ?? ''),
                'parking_num' => (string)($item['parking_num'] ?? ''),
                'enable' => intval($item['enable'] ?? 0),
            ];
        }

        return [
            'list' => $list,
        ];
    }

    protected function buildSaveData($village_id, $params = [], $exclude_id = 0)
    {
        $code = trim((string)($params['code'] ?? ''));
        if ($code === '') {
            throw new \Exception('感应器编码不能为空');
        }
        if (strlen($code) > 50) {
            throw new \Exception('感应器编码长度不能超过50位');
        }

        $parking_num = trim((string)($params['parking_num'] ?? ''));
        if ($parking_num === '') {
            throw new \Exception('车牌号不能为空');
        }
        if (strlen($parking_num) > 50) {
            throw new \Exception('车牌号长度不能超过50位');
        }

        $enable = intval($params['enable'] ?? 1);
        if (!in_array($enable, [0, 1], true)) {
            throw new \Exception('启用状态参数错误');
        }

        $repeatWhere = [
            ['village_id', '=', intval($village_id)],
            ['code', '=', $code],
        ];
        if ($exclude_id > 0) {
            $repeatWhere[] = ['id', '<>', intval($exclude_id)];
        }
        $repeat = $this->sensorModel->getOne($repeatWhere, 'id');
        if ($repeat && !is_array($repeat)) {
            $repeat = $repeat->toArray();
        }
        if (!empty($repeat)) {
            throw new \Exception('该感应器编码已存在');
        }

        return [
            'village_id' => intval($village_id),
            'code' => $code,
            'parking_num' => $parking_num,
            'enable' => $enable,
        ];
    }

    protected function formatItem($item)
    {
        $item['id'] = intval($item['id'] ?? 0);
        $item['enable'] = intval($item['enable'] ?? 0);
        $item['code'] = (string)($item['code'] ?? '');
        $item['parking_num'] = (string)($item['parking_num'] ?? '');
        $item['createdAt'] = (string)($item['createdAt'] ?? '');
        $item['updatedAt'] = (string)($item['updatedAt'] ?? '');
        return $item;
    }

    protected function hasValue($params, $key)
    {
        return isset($params[$key]) && $params[$key] !== '' && $params[$key] !== null;
    }
}
