<?php


namespace app\building\controller\platform;


use app\building\service\ShoppingMallBuildingService;

class ShoppingMallBuildingController extends AuthBaseController
{
    /**
     * 楼栋列表
     * @return \think\response\Json
     */
    public function getList()
    {
        $params = [
            'mall_id' => $this->mall_id,
        ];
        try {
            $res = (new ShoppingMallBuildingService())->getList($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 楼栋-保存/编辑
     * @return \think\response\Json
     */
    public function saveInfo()
    {
        $params = [
            'name' => input('name', '', 'trim,string'),
            'building_number' => input('building_number', '', 'trim,string'),
            'area' => input('area', 0, 'trim,floatval'),
            'sort' => input('sort', 0, 'trim,intval'),
            'id' => input('id', 0, 'trim,intval'),
            'mall_id' => $this->mall_id,
        ];
        try {
            $res = (new ShoppingMallBuildingService())->saveInfo($params);
            return api_output_error(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 楼栋-删除
     */
    public function delInfo()
    {
        $params = [
            'id' => input('id', 0, 'trim,intval'),
        ];
        try {
            $res = (new ShoppingMallBuildingService())->delInfo($params);
            return api_output_error(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}