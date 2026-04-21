<?php

namespace app\community\model\service\Device;

use app\community\model\db\Device\HouseSmartAccessDevice;
use app\community\model\db\Device\HouseSmartAccessRecords;

class HouseAccessDeviceService
{
    /**
     * @var HouseSmartAccessDevice
     */
    protected $deviceModel;

    public function __construct()
    {
        $this->deviceModel = new HouseSmartAccessDevice();
    }

    public function deviceIndex($village_id, $params = [])
    {
        $page = max(1, intval($params['page'] ?? 1));
        $pageSize = max(1, min(500, intval($params['pageSize'] ?? 10)));
        $where = [
            ['village_id', '=', intval($village_id)],
        ];

        $serial_num = trim((string)($params['serial_num'] ?? ''));
        if ($serial_num !== '') {
            $where[] = ['serial_num', 'like', '%' . $serial_num . '%'];
        }

        $address = trim((string)($params['address'] ?? ''));
        if ($address !== '') {
            $where[] = ['address', 'like', '%' . $address . '%'];
        }

        if ($this->hasValue($params, 'enable')) {
            $where[] = ['enable', '=', intval($params['enable'])];
        }

        $list = $this->deviceModel->where($where)->order('id DESC')->page($page, $pageSize)->select();
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
            'count' => $this->deviceModel->getCount($where),
            'pageSize' => $pageSize,
        ];
    }

    public function deviceAdd($village_id, $params = [])
    {
        $data = $this->buildSaveData($village_id, $params);
        $now = date('Y-m-d H:i:s');
        $data['createdAt'] = $now;
        $data['updatedAt'] = $now;
        $id = $this->deviceModel->add($data);
        return ['id' => $id];
    }

    public function deviceEdit($village_id, $params = [])
    {
        $id = intval($params['id'] ?? 0);
        if ($id <= 0) {
            throw new \Exception('设备ID不能为空');
        }

        $where = [
            ['id', '=', $id],
            ['village_id', '=', intval($village_id)],
        ];
        $detail = $this->deviceModel->getOne($where);
        if ($detail && !is_array($detail)) {
            $detail = $detail->toArray();
        }
        if (empty($detail)) {
            throw new \Exception('设备信息不存在');
        }

        $data = $this->buildSaveData($village_id, $params, $id);
        $data['updatedAt'] = date('Y-m-d H:i:s');
        $this->deviceModel->updateThis($where, $data);

        return ['id' => $id];
    }

    public function deviceDel($village_id, $id)
    {
        $id = intval($id);
        if ($id <= 0) {
            throw new \Exception('设备ID不能为空');
        }

        $where = [
            ['id', '=', $id],
            ['village_id', '=', intval($village_id)],
        ];
        $detail = $this->deviceModel->getOne($where);
        if ($detail && !is_array($detail)) {
            $detail = $detail->toArray();
        }
        if (empty($detail)) {
            throw new \Exception('设备信息不存在');
        }

        $recordCount = (new HouseSmartAccessRecords())->getCount([['serial_id', '=', $id]]);
        if ($recordCount > 0) {
            throw new \Exception('当前设备已有关联出入记录，暂不支持删除');
        }

        $this->deviceModel->where($where)->delete();
        return true;
    }

    public function getDeviceBySerial($serial_num, $village_id = 0)
    {
        $serial_num = trim((string)$serial_num);
        if ($serial_num === '') {
            return [];
        }

        $where = [
            ['serial_num', '=', $serial_num],
        ];
        if ($village_id > 0) {
            $where[] = ['village_id', '=', intval($village_id)];
        }

        $detail = $this->deviceModel->getOne($where);
        if ($detail && !is_array($detail)) {
            $detail = $detail->toArray();
        }

        return $detail ?: [];
    }

    public function deviceOptions($village_id, $pageSize = 500)
    {
        $pageSize = max(1, min(500, intval($pageSize)));
        $list = $this->deviceModel
            ->where([['village_id', '=', intval($village_id)]])
            ->field('id,serial_num,address,enable')
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
                'serial_num' => (string)($item['serial_num'] ?? ''),
                'address' => (string)($item['address'] ?? ''),
                'enable' => intval($item['enable'] ?? 0),
            ];
        }

        return [
            'list' => $list,
        ];
    }

    protected function buildSaveData($village_id, $params = [], $exclude_id = 0)
    {
        $serial_num = trim((string)($params['serial_num'] ?? ''));
        if ($serial_num === '') {
            throw new \Exception('设备序列号不能为空');
        }
        if (strlen($serial_num) > 10) {
            throw new \Exception('设备序列号长度不能超过10位');
        }

        $address = trim((string)($params['address'] ?? ''));
        if ($address === '') {
            throw new \Exception('设备位置不能为空');
        }
        if (strlen($address) > 255) {
            throw new \Exception('设备位置长度不能超过255位');
        }

        $enable = intval($params['enable'] ?? 1);
        if (!in_array($enable, [0, 1], true)) {
            throw new \Exception('启用状态参数错误');
        }

        $repeatWhere = [
            ['village_id', '=', intval($village_id)],
            ['serial_num', '=', $serial_num],
        ];
        if ($exclude_id > 0) {
            $repeatWhere[] = ['id', '<>', intval($exclude_id)];
        }
        $repeat = $this->deviceModel->getOne($repeatWhere, 'id');
        if ($repeat && !is_array($repeat)) {
            $repeat = $repeat->toArray();
        }
        if (!empty($repeat)) {
            throw new \Exception('该设备序列号已存在');
        }

        return [
            'village_id' => intval($village_id),
            'serial_num' => $serial_num,
            'address' => $address,
            'enable' => $enable,
        ];
    }

    protected function formatItem($item)
    {
        $item['id'] = intval($item['id'] ?? 0);
        $item['enable'] = intval($item['enable'] ?? 0);
        $item['serial_num'] = (string)($item['serial_num'] ?? '');
        $item['address'] = (string)($item['address'] ?? '');
        $item['createdAt'] = (string)($item['createdAt'] ?? '');
        $item['updatedAt'] = (string)($item['updatedAt'] ?? '');
        return $item;
    }

    protected function hasValue($params, $key)
    {
        return isset($params[$key]) && $params[$key] !== '' && $params[$key] !== null;
    }
}
