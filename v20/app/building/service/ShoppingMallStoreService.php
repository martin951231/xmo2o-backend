<?php


namespace app\building\service;


use app\building\model\ShoppingMallBuilding;
use app\building\model\ShoppingMallFloor;
use app\building\model\ShoppingMallRoom;
use app\common\model\db\MerchantStore;

class ShoppingMallStoreService
{
    /**
     * 店铺列表
     * @param $params
     * @return array
     */
    public function getStoreList($params)
    {
        $store_list = $this->getStoreLists($params);
        return $store_list;
    }

    /**
     * 批量绑定店铺
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function mallBindStore($params)
    {
        $mall_id = $params['mall_id'];
        $store_ids = $params['store_ids'];
        $store_ids = !is_array($store_ids) ? [$store_ids] : $store_ids;
        if (!$store_ids) {
            throw new \think\Exception(L_('请选择要绑定的店铺'));
        }
        $where = [
            ['s.status', '=', 1],
            ['m.status', '=', 1],
            ['s.mall_id', '=', 0],
            ['s.store_id', 'in', $store_ids]
        ];
        $store_list = (new MerchantStore())->alias('s')->field('s.store_id')
            ->join('merchant m', 's.mer_id = m.mer_id')
            ->where($where)
            ->select()->toArray();
        if (!$store_list) {
            throw new \think\Exception(L_('选择店铺不存在/关闭'));
        }
        $store_ids = array_column($store_list, 'store_id');
        try {
            foreach ($store_ids as $store_id) {
                $this->changeStoreInfo([
                    'mall_id' => $mall_id,
                    'store_id' => $store_id,
                    'note' => '商场绑定店铺',
                ]);
            }
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 店铺绑定楼层
     */
    public function storeBindFloor($params)
    {
        $mall_id = $params['mall_id'];
        $room_id = $params['room_id'];
        $store_ids = $params['store_ids'];
        $store_ids = !is_array($store_ids) ? [$store_ids] : $store_ids;
        if (!$store_ids) {
            throw new \think\Exception(L_('请选择店铺'));
        }
        if (!$room_id) { //解绑房间
            try {
                foreach ($store_ids as $store_id) {
                    $this->changeStoreInfo([
                        'is_del' => 1,
                        'mall_id' => $mall_id,
                        'store_id' => $store_id,
                        'note' => '解绑房间',
                    ]);
                }
            } catch (\Exception $e) {
                throw new \think\Exception($e->getMessage());
            }
            return true;
        }
        $room = (new ShoppingMallRoom())->where(['mall_id' => $mall_id, 'id' => $room_id])->findOrEmpty()->toArray();
        if (!$room) {
            throw new \think\Exception(L_('房间不存在！'));
        }
        $floor = (new ShoppingMallFloor())->where(['id' => $room['floor_id']])->findOrEmpty()->toArray();
        if (!$floor) {
            throw new \think\Exception(L_('楼层不存在！'));
        }
        $building = (new ShoppingMallBuilding())->where(['id' => $floor['building_id']])->findOrEmpty()->toArray();
        if (!$building) {
            throw new \think\Exception(L_('楼栋不存在！'));
        }
        $where = [
            ['s.status', '=', 1],
            ['m.status', '=', 1],
            ['s.mall_id', '=', $mall_id],
            ['s.store_id', 'in', $store_ids]
        ];
        $store_list = (new MerchantStore())->alias('s')->field('s.store_id')
            ->join('merchant m', 's.mer_id = m.mer_id')
            ->where($where)
            ->select()->toArray();
        if (!$store_list) {
            throw new \think\Exception(L_('选择店铺不存在/关闭'));
        }
        $store_ids = array_column($store_list, 'store_id');
        $save_data = [
            'floor_id' => $floor['id'],
            'building_id' => $building['id'],
            'floor_number' => $floor['floor_number'],
            'floor' => $floor['name'],
            'building' => $building['name'] . $building['building_number'],
            'room_id' => $room['id'],
            'room' => $room['room_number'],
            'brand_name' => $room['brand_name'],
            'mall_id' => $mall_id,
            'note' => '绑定房间',
        ];
        try {
            foreach ($store_ids as $store_id) {
                $save_data['store_id'] = $store_id;
                $this->changeStoreInfo($save_data);
            }
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 导入店铺信息
     * @param $params
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \think\Exception
     */
    public function importStore($params)
    {
        $mall_id = $params['mall_id'];
        $file_url = $params['file_url'];
        if (!$file_url) {
            throw new \think\Exception(L_('请上传兑换码！'), 1003);
        }
        foreach (getExcelDataForUrl($file_url, [
            'A' => 'store_id',
            'B' => 'building_number',
            'C' => 'floor_number',
            'D' => 'room_number',
        ]) as $data) {
            foreach ($data as $item) {
                //查询店铺是否存在
                $store = (new MerchantStore())->where(['store_id' => $item['store_id'], 'status' => 1])->findOrEmpty()->toArray();
                if (!$store) {
                    throw new \think\Exception(L_('店铺ID为：X1状态未开启！', ['X1' => $item['store_id']]));
                }
                if ($store['mall_id'] && $store['mall_id'] != $mall_id) {
                    throw new \think\Exception(L_('店铺ID为：X1已绑定其他商场！', ['X1' => $item['store_id']]));
                }
                $building = (new ShoppingMallBuilding())->where(['building_number' => $item['building_number'], 'mall_id' => $mall_id])->findOrEmpty()->toArray();
                if (!$building) {
                    throw new \think\Exception(L_('店铺ID为：X1所属楼栋编号不存在！', ['X1' => $item['store_id']]));
                }
                $floor = (new ShoppingMallFloor())->where(['floor_number' => $item['floor_number'], 'building_id' => $building['id'], 'mall_id' => $mall_id])->findOrEmpty()->toArray();
                if (!$floor) {
                    throw new \think\Exception(L_('店铺ID为：X1所属楼层编号不存在！', ['X1' => $item['store_id']]));
                }
                $room = (new ShoppingMallRoom())->where(['room_number' => $item['room_number'], 'floor_id' => $floor['id'], 'building_id' => $building['id'], 'mall_id' => $mall_id])->findOrEmpty()->toArray();
                if (!$room) {
                    throw new \think\Exception(L_('店铺ID为：X1所属房间编号不存在！', ['X1' => $item['store_id']]));
                }
                try {
                    $this->changeStoreInfo([
                        'store_id' => $item['store_id'],
                        'mall_id' => $mall_id,
                        'floor_id' => $floor['id'],
                        'building_id' => $building['id'],
                        'floor_number' => $floor['floor_number'],
                        'floor' => $floor['name'],
                        'building' => $building['name'] . $building['building_number'],
                        'room_id' => $room['id'],
                        'room_number' => $room['room_number'],
                        'note' => '批量导入绑定店铺信息',
                    ]);
                } catch (\Exception $e) {
                    throw new \think\Exception($e->getMessage());
                }
            }
        }
        return true;
    }

    /**
     * 店铺列表
     * @param $params
     * @return array
     */
    public function getMallBindStore($params)
    {
        if (!$params['floor_id']) {
            throw new \think\Exception(L_('请选择要操作的楼层'));
        }
        $store_list = $this->getStoreLists(['mall_id' => $params['mall_id'], 'floor_id' => $params['floor_id']]);
        return ['store_list' => $store_list];
    }

    public function getStoreLists($params)
    {
        $mall_id = $params['mall_id'] ?? 0;
        $page_size = $params['page_size'] ?? 10;
        $page = $params['page'] ?? 0;
        $search_type = $params['search_type'] ?? '';
        $keywords = $params['keywords'] ?? '';
        $floor_id = $params['floor_id'] ?? 0;
        $where = [
            ['s.status', '=', 1],
            ['m.status', '=', 1],
        ];
        if ($mall_id) {
            $where[] = ['s.mall_id', '=', $mall_id];
        } else {
            $where[] = ['s.mall_id', '=', 0];
        }
        if ($floor_id) {
            $where[] = ['s.floor_id', '=', $floor_id];
        }
        if ($keywords) {
            if ($search_type == 'store_name') {
                $where[] = ['s.name', 'like', '%' . $keywords . '%'];
            } elseif ($search_type == 'mer_name') {
                $where[] = ['m.name', 'like', '%' . $keywords . '%'];
            }
        }
        $query = (new MerchantStore())->alias('s')
            ->field('s.store_id,s.name,s.building_id,s.floor_id,s.room_id,m.name AS mer_name')
            ->join('merchant m', 's.mer_id = m.mer_id')
            ->where($where)
            ->order('s.sort desc,s.store_id asc');
        if ($page > 0) {
            $store_list = $query->paginate($page_size);
        } else {
            $store_list = $query->select();
        }
        return $store_list->toArray();
    }

    public function changeStoreInfo($params)
    {
        $mall_id = $params['mall_id'];
        $building_id = $params['building_id'] ?? 0;
        $floor_id = $params['floor_id'] ?? 0;
        $room_id = $params['room_id'] ?? 0;
        $building = $params['building'] ?? '';
        $floor = $params['floor'] ?? '';
        $floor_number = $params['floor_number'] ?? 0;
        $room = $params['room'] ?? '';
        $store_id = $params['store_id'] ?? 0;
        $brand_name = $params['brand_name'] ?? 0;
        $is_del = $params['is_del'] ?? 0; //1-解绑楼栋楼层房间，2-解绑店铺
        $note = $params['note'] ?? ''; //备注
        //解绑
        if ($is_del) {
            $change_store_info = [
                'room_id' => 0,
                'room' => '',
                'floor_id' => 0,
                'floor' => '',
                'building_id' => 0,
                'building' => '',
                'floor_number' => 0,
                'brand_name' => '',
            ];
            if ($is_del == 2) {
                $change_store_info['mall_id'] = 0;
            }
            if ($room_id) {
                $where = ['room_id' => $room_id, 'mall_id' => $mall_id];
            } else {
                $where = ['store_id' => $store_id, 'mall_id' => $mall_id];
            }
        } else {
            $change_store_info = [
                'brand_name' => $brand_name,
                'building_id' => $building_id,
                'floor_id' => $floor_id,
                'mall_id' => $mall_id,
                'building' => $building,
                'floor' => $floor,
                'floor_number' => $floor_number,
                'room' => $room,
                'room_id' => $room_id,
            ];
            $where = ['store_id' => $store_id];
            //先解绑当前房间
            if ($bind_store_list = (new MerchantStore())->where([
                'mall_id' => $mall_id,
                'room_id' => $room_id,
                'building_id' => $building_id,
                'floor_id' => $floor_id,
            ])->select()->toArray()) {
                foreach ($bind_store_list as $item) {
                    if ($item['store_id'] != $store_id) {
                        $this->changeStoreInfo(['is_del' => 1, 'store_id' => $item['store_id'], 'mall_id' => $mall_id, 'note' => '绑定房间前先解绑该房间已绑定店铺']);
                    }
                }
            }
        }
        $info = (new MerchantStore())->where($where)->findOrEmpty()->toArray();
        (new MerchantStore())->where($where)->save($change_store_info);
        if ($info) {
            $store_record = [
                'mall_id' => $mall_id,
                'store_id' => $store_id,
                'data' => json_encode($change_store_info),
                'note' => $note,
            ];
            (new ShoppingMallStoreRecordService())->addRecord($store_record);
        }

        return true;
    }

    /**
     * 解绑店铺
     */
    public function unbindStore($params)
    {
        $store_id = $params['store_id'];
        $mall_id = $params['mall_id'];

        try {
            $this->changeStoreInfo(['is_del' => 2, 'store_id' => $store_id, 'mall_id' => $mall_id, 'note' => '商场解绑店铺']);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }
}