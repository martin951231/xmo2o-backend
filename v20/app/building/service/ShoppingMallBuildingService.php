<?php


namespace app\building\service;


use app\building\model\ShoppingMall;
use app\building\model\ShoppingMallBuilding;
use app\building\model\ShoppingMallFloor;
use app\building\model\ShoppingMallRoom;
use app\common\model\db\MerchantStore;

class ShoppingMallBuildingService
{
    /**
     * 楼栋列表
     * @return array
     */
    public function getList($params)
    {
        $mall_id = $params['mall_id'];
        $where[] = ['mall_id', '=', $mall_id];
        $field = 'id,mall_id,name,building_number,area,sort';
        $data = (new ShoppingMallBuilding())->getList($where, $field);
        return ['data' => $data];
    }

    /**
     * 楼栋-保存/编辑
     * @param $params
     * @return bool
     */
    public function saveInfo($params)
    {
        $id = $params['id'];
        $save_data = [
            'name' => $params['name'],
            'building_number' => $params['building_number'],
            'area' => $params['area'],
            'sort' => $params['sort'],
            'mall_id' => $params['mall_id'],
        ];
        if (!$save_data['name']) {
            throw new \think\Exception(L_('名称不能为空！'));
        }
        if (!$save_data['building_number']) {
            throw new \think\Exception(L_('楼栋编号不能为空！'));
        }
        if ($save_data['sort'] < 0) {
            throw new \think\Exception(L_('排序值不能小于0！'));
        }
        try {
            if ($id) {
                $info = (new ShoppingMallBuilding())->where([['id', '=', $id], ['mall_id', '=', $save_data['mall_id']]])->findOrEmpty()->toArray();
                if (!$info) {
                    throw new \think\Exception(L_('修改信息不存在！'));
                }
                $old_name = $info['name'];
                $old_building_number = $info['building_number'];
                $check_number = (new ShoppingMallBuilding())->where([['building_number', '=', $save_data['building_number']], ['id', '<>', $id], ['mall_id', '=', $save_data['mall_id']]])->findOrEmpty()->toArray();
                if ($check_number) {
                    throw new \think\Exception(L_('楼栋编号已存在！'));
                }
                $save_data['update_time'] = time();
                (new ShoppingMallBuilding())->where(['id' => $id])->save($save_data);
                $change_store_info = [];
                if ($old_name != $save_data['name'] || $old_building_number != $save_data['building_number']) {
                    $change_store_info['building'] = $save_data['name'] . $save_data['building_number'];
                }
                if ($change_store_info) {
                    (new MerchantStore())->where(['mall_id' => $info['mall_id'], 'building_id' => $id])->save($change_store_info);
                }
            } else {
                $check_number = (new ShoppingMallBuilding())->where([['building_number', '=', $save_data['building_number']], ['mall_id', '=', $save_data['mall_id']]])->findOrEmpty()->toArray();
                if ($check_number) {
                    throw new \think\Exception(L_('楼栋编号已存在！'));
                }
                $save_data['create_time'] = time();
                (new ShoppingMallBuilding())->save($save_data);
            }
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     *  楼栋-删除
     * @param $params
     * @return bool
     */
    public function delInfo($params)
    {
        $id = $params['id'];
        $check_floor = (new ShoppingMallFloor())->where([['building_id', '=', $id]])->findOrEmpty()->toArray();
        if ($check_floor) {
            throw new \think\Exception(L_('该楼栋下有楼层，请先删除楼层！'));
        }
        try {
            (new ShoppingMallBuilding())->where(['id' => $id])->delete();
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    public function getBuilding($mall_id)
    {
        $mall = (new ShoppingMall())->getDetail($mall_id);
        $building_list = [];
        if ($mall && $mall['is_self']) {
            $building = (new ShoppingMallBuilding())->where(['mall_id' => $mall_id])->column('id,name');
            foreach ($building as &$item) {
                $floor = (new ShoppingMallFloor())->where(['mall_id' => $mall_id, 'building_id' => $item['id']])->column('id,name');
                if($floor){
                    $have_room = 0;
                    foreach ($floor as $key=>$floor_item){
                        $room = (new ShoppingMallRoom())->where(['mall_id' => $mall_id, 'floor_id' => $floor_item['id']])->column('id,room_number as name');
                        if($room){
                            $have_room = 1;
                            $floor[$key]['children'] = $room;
                        }else{
                            unset($floor[$key]);
                        }
                    }
                    if($have_room){
                        $item['children'] = array_values($floor);
                        $building_list[] = $item;
                    }
                }
            }
        }
        return [
            'building' => $building_list,
            'mall_is_self' => $mall['is_self'] ?? 0,
        ];
    }
}