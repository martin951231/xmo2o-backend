<?php


namespace app\building\service;


use app\building\model\ShoppingMallBuilding;
use app\building\model\ShoppingMallFloor;
use app\building\model\ShoppingMallRoom;
use app\common\model\db\MerchantStore;
use think\facade\Db;

class ShoppingMallFloorService
{
    /**
     * 楼层列表
     * @return array
     */
    public function getList($params)
    {
        $mall_id = $params['mall_id'];
        $building_id = $params['building_id'];
        $where[] = ['mall_id', '=', $mall_id];
        $where[] = ['building_id', '=', $building_id];
        $field = '*';
        $data = (new ShoppingMallFloor())->getList($where, $field);
        $store_list = (new ShoppingMallStoreService())->getStoreLists(['mall_id' => $mall_id]);
        foreach ($data as &$item) {
            $room_list = (new ShoppingMallRoom())->where([
                'floor_id' => $item['id'],
                'mall_id' => $item['mall_id'],
                'building_id' => $item['building_id']
            ])->column('id,room_number,room_number as name,floor_id,building_id,mall_id,brand_name');
            $room_ids = array_column($store_list, 'store_id', 'room_id');
            $room_list = array_map(function ($v) use ($room_ids) {
                $v['store_id'] = isset($room_ids[$v['id']]) ? $room_ids[$v['id']] : 0;
                return $v;
            }, $room_list);
            $item['children'] = $room_list;
            $item['store_list'] = $store_list;
        }
        return ['data' => $data];
    }

    /**
     * 楼层-保存/编辑
     * @param $params
     * @return bool
     */
    public function saveInfo($params)
    {
        $id = $params['id'];
        $save_data = [
            'name' => $params['name'],
            'floor_number' => $params['floor_number'],
            'bg_img' => $params['bg_img'],
            'coor_list' => $params['coor_list'] ? json_encode($params['coor_list']) : '',
            'mall_id' => $params['mall_id'],
            'building_id' => $params['building_id'],
        ];
        if (!$save_data['name']) {
            throw new \think\Exception(L_('名称不能为空！'));
        }
        if (!$save_data['floor_number']) {
            throw new \think\Exception(L_('楼层编号不能为空！'));
        }
        $common_where = [
            ['mall_id', '=', $save_data['mall_id']],
            ['building_id', '=', $save_data['building_id']],
        ];
        Db::startTrans();
        try {
            if ($id) {
                $info = (new ShoppingMallFloor())->where(array_merge($common_where, [['id', '=', $id]]))->findOrEmpty()->toArray();
                if (!$info) {
                    throw new \think\Exception(L_('修改信息不存在！'));
                }
                $old_name = $info['name'];
                $old_floor_number = $info['floor_number'];
                $check_number = (new ShoppingMallFloor())->where(array_merge($common_where, [['floor_number', '=', $save_data['floor_number']], ['id', '<>', $id]]))->findOrEmpty()->toArray();
                if ($check_number) {
                    throw new \think\Exception(L_('楼层编号已存在！'));
                }
                $save_data['update_time'] = time();
                (new ShoppingMallFloor())->where(['id' => $id])->save($save_data);
                $change_store_info = [];
                if ($old_name != $save_data['name']) {
                    $change_store_info['floor'] = $save_data['name'];
                }
                if ($old_floor_number != $save_data['floor_number']) {
                    $change_store_info['floor_number'] = $save_data['floor_number'];
                }
                if ($change_store_info) {
                    (new MerchantStore())->where(['mall_id' => $info['mall_id'], 'building_id' => $info['building_id'], 'floor_id' => $info['id']])->save($change_store_info);
                }
            } else {
                $check_number = (new ShoppingMallFloor())->where(array_merge($common_where, [['floor_number', '=', $save_data['floor_number']]]))->findOrEmpty()->toArray();
                if ($check_number) {
                    throw new \think\Exception(L_('楼层编号已存在！'));
                }
                $save_data['create_time'] = time();
                (new ShoppingMallFloor())->save($save_data);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     *  楼层-删除
     * @param $params
     * @return bool
     */
    public function delInfo($params)
    {
        $id = $params['id'];
        $mall_id = $params['mall_id'];
        $building_id = $params['building_id'];
        $floor = (new ShoppingMallFloor())->where(['id' => $id, 'mall_id' => $mall_id, 'building_id' => $building_id])->findOrEmpty()->toArray();
        if (!$floor) {
            throw new \think\Exception(L_('删除信息不存在/已删除！'));
        }
        $store_where = [
            ['mall_id', '=', $mall_id],
            ['floor_id', '=', $id],
            ['building_id', '=', $building_id],
        ];
        $check_store = (new ShoppingMallRoom())->where($store_where)->findOrEmpty()->toArray();
        if ($check_store) {
            throw new \think\Exception(L_('该楼层下有房间，请先删除房间！'));
        }
        try {
            (new ShoppingMallFloor())->where(['id' => $id])->delete();
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 添加/编辑房间信息
     */
    public function saveRoomInfo($params)
    {
        $id = $params['id'];
        $store_id = $params['store_id'];
        $save_data = [
            'room_number' => $params['room_number'],
            'brand_name' => $params['brand_name'],
            'floor_id' => $params['floor_id'],
            'mall_id' => $params['mall_id'],
            'building_id' => $params['building_id'],
        ];
        if (!$save_data['room_number']) {
            throw new \think\Exception(L_('房间编号不能为空！'));
        }
        $building_info = (new ShoppingMallBuilding())->where([['id', '=', $save_data['building_id']], ['mall_id', '=', $save_data['mall_id']]])->findOrEmpty()->toArray();
        if (!$building_info) {
            throw new \think\Exception(L_('所属楼栋不存在！'));
        }
        $floor_info = (new ShoppingMallFloor())->where([['id', '=', $save_data['floor_id']], ['mall_id', '=', $save_data['mall_id']], ['building_id', '=', $save_data['building_id']]])->findOrEmpty()->toArray();
        if (!$floor_info) {
            throw new \think\Exception(L_('所属楼层不存在！'));
        }
        $change_store_info = [];
        if ($store_id) {
            $store_info = (new MerchantStore())->where(['store_id' => $store_id])->findOrEmpty()->toArray();
            if (!$store_info) {
                throw new \think\Exception(L_('店铺不存在！'));
            }
            if ($store_info['status'] != 1) {
                throw new \think\Exception(L_('店铺未开启！'));
            }
            if ($store_info['mall_id'] && $store_info['mall_id'] != $save_data['mall_id']) {
                throw new \think\Exception(L_('该店铺已绑定商场，请先解绑该店铺！'));
            }
            if ($store_info['building_id'] && $store_info['building_id'] != $save_data['building_id']) {
                throw new \think\Exception(L_('该店铺已绑定楼栋，请先解绑该店铺！'));
            }
            if ($store_info['floor_id'] && $store_info['floor_id'] != $save_data['floor_id']) {
                throw new \think\Exception(L_('该店铺已绑定楼层，请先解绑该店铺！'));
            }
            if ($store_info['room_id'] && $store_info['room_id'] && $store_info['room_id'] != $id) {
                throw new \think\Exception(L_('该店铺已绑定房间，请先解绑该店铺！'));
            }
            $change_store_info = [
                'brand_name' => $save_data['brand_name'],
                'building_id' => $save_data['building_id'],
                'floor_id' => $save_data['floor_id'],
                'mall_id' => $save_data['mall_id'],
                'building' => $building_info['name'],
                'floor' => $floor_info['name'],
                'floor_number' => $floor_info['floor_number'],
                'room' => $save_data['room_number'],
            ];
        }
        $common_where = [
            ['mall_id', '=', $save_data['mall_id']],
            ['building_id', '=', $save_data['building_id']],
            ['floor_id', '=', $save_data['floor_id']],
        ];
        Db::startTrans();
        try {
            if ($id) {
                $info = (new ShoppingMallRoom())->where(array_merge($common_where, [['id', '=', $id]]))->findOrEmpty()->toArray();
                if (!$info) {
                    throw new \think\Exception(L_('修改信息不存在！'));
                }
                $check_number = (new ShoppingMallRoom())->where(array_merge($common_where, [['room_number', '=', $save_data['room_number']], ['id', '<>', $id]]))->findOrEmpty()->toArray();
                if ($check_number) {
                    throw new \think\Exception(L_('房间编号已存在！'));
                }
                $save_data['update_time'] = time();
                (new ShoppingMallRoom())->where(['id' => $id])->save($save_data);
                if(!$change_store_info) {
                    $change_store_info = [
                        'mall_id' => $save_data['mall_id'],
                        'room_id' => $id,
                        'is_del' => 1,
                        'note' => '编辑房间解绑店铺'
                    ];
                }
            } else {
                $check_number = (new ShoppingMallRoom())->where(array_merge($common_where, [['room_number', '=', $save_data['room_number']]]))->findOrEmpty()->toArray();
                if ($check_number) {
                    throw new \think\Exception(L_('房间编号已存在！'));
                }
                $save_data['create_time'] = time();
                $id = (new ShoppingMallRoom())->insertGetId($save_data);
            }
            if ($change_store_info) {
                $change_store_info['room_id'] = $id;
                $change_store_info['store_id'] = $store_id;
                $change_store_info['note'] = '添加/编辑房间绑定店铺';
            }
            (new ShoppingMallStoreService())->changeStoreInfo($change_store_info);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 房间-删除
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function delRoomInfo($params)
    {
        $id = $params['id'];
        $mall_id = $params['mall_id'];

        if (!$id) {
            throw new \think\Exception(L_('参数错误！'));
        }
        $bind_store_info = (new MerchantStore())->where(['room_id' => $id, 'mall_id' => $mall_id])->findOrEmpty()->toArray();
        try {
            (new ShoppingMallRoom())->where(['id' => $id, 'mall_id' => $mall_id])->delete();
            if($bind_store_info){
                (new ShoppingMallStoreService())->changeStoreInfo([
                    'mall_id' => $mall_id,
                    'room_id' => $id,
                    'store_id' => $bind_store_info['store_id'],
                    'is_del' => 1,
                    'note' => '删除房间解绑定店铺',
                ]);
            }
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 获取楼层下的房间列表
     * @param $params
     */
    public function getRoomList($params){
        $floor_id = $params['floor_id'];
        $mall_id = $params['mall_id'];

        if (!$floor_id) {
            throw new \think\Exception(L_('参数错误！'));
        }
        $room_list = (new ShoppingMallRoom())->field('id,room_number,mall_id,building_id,floor_id')->where(['mall_id' => $mall_id, 'floor_id' => $floor_id])->select()->toArray();
        foreach ($room_list as &$item){
            $store_info = (new MerchantStore())->field('store_id,name as store_name')->where(['room_id' => $item['id'], 'mall_id' => $mall_id])->findOrEmpty()->toArray();
            $item['store_name'] = $store_info['store_name'] ?? '';
            $item['store_id'] = $store_info['store_id'] ?? '';
        }
        return $room_list;
    }
}