<?php


namespace app\building\controller\platform;


use app\building\service\ShoppingMallFloorService;

class ShoppingMallFloorController extends AuthBaseController
{
    /**
     * 楼层列表
     * @return \think\response\Json
     */
    public function getList()
    {
        $params = [
            'mall_id' => $this->mall_id,
            'building_id' => input('building_id', 0, 'trim,intval'), // 楼栋id
        ];
        try {
            $res = (new ShoppingMallFloorService())->getList($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 楼层-保存/编辑
     * @return \think\response\Json
     */
    public function saveInfo()
    {
        $params = [
            'name' => input('name', '', 'trim,string'), // 楼层名称
            'floor_number' => input('floor_number', '', 'trim,string'), // 楼层编号
            'bg_img' => input('bg_img', '', 'trim,string'), // 楼层背景图
            'coor_list' => input('coor_list', ''), // 楼层坐标
            'building_id' => input('building_id', 0, 'trim,intval'), // 楼栋id
            'id' => input('id', 0, 'trim,intval'), // 楼层id
            'mall_id' => $this->mall_id,
        ];
        try {
            $res = (new ShoppingMallFloorService())->saveInfo($params);
            return api_output_error(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 楼层-删除
     */
    public function delInfo()
    {
        $params = [
            'id' => input('id', 0, 'trim,intval'), // 楼层id
            'building_id' => input('building_id', 0, 'trim,intval'), // 楼栋id
            'mall_id' => input('mall_id', 0, 'trim,intval'),
        ];
        try {
            $res = (new ShoppingMallFloorService())->delInfo($params);
            return api_output_error(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 房间-保存/编辑
     */
    public function saveRoomInfo()
    {
        $params = [
            'room_number' => input('room_number', '', 'trim,string'), // 房间编号
            'floor_id' => input('floor_id', 0, 'trim,intval'), // 楼层id
            'building_id' => input('building_id', 0, 'trim,intval'), // 楼栋id
            'brand_name' => input('brand_name', '', 'trim,string'), // 品牌名称
            'store_id' => input('store_id', 0, 'trim,intval'), // 店铺id
            'id' => input('id', 0, 'trim,intval'), // 房间id
            'mall_id' => $this->mall_id,
        ];
        try {
            $res = (new ShoppingMallFloorService())->saveRoomInfo($params);
            return api_output_error(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 房间-删除
     */
    public function delRoomInfo()
    {
        $params = [
            'id' => input('id', 0, 'trim,intval'),
            'mall_id' => input('mall_id', 0, 'trim,intval'),
        ];
        try {
            $res = (new ShoppingMallFloorService())->delRoomInfo($params);
            return api_output_error(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取楼层下的房间列表
     * @return \think\response\Json
     */
    public function getRoomList()
    {
        $params = [
            'floor_id' => input('floor_id', 0, 'trim,intval'), // 楼层id
            'mall_id' => $this->mall_id,
        ];
        try {
            $res = (new ShoppingMallFloorService())->getRoomList($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}