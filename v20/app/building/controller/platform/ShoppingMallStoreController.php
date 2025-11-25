<?php


namespace app\building\controller\platform;


use app\building\service\ShoppingMallStoreService;

class ShoppingMallStoreController extends AuthBaseController
{
    /**
     * 店铺列表
     * @return \think\response\Json
     */
    public function getStoreList()
    {
        $params = [
            'page' => input('page', 1, 'trim,intval'),
            'page_size' => input('page_size', 10, 'trim,intval'),
            'search_type' => input('search_type', '', 'trim,string'),
            'keywords' => input('keywords', '', 'trim,string'),
        ];
        try {
            $res = (new ShoppingMallStoreService())->getStoreList($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 批量绑定店铺
     * @return \think\response\Json
     */
    public function mallBindStore()
    {
        $params = [
            'mall_id' => $this->mall_id,
            'store_ids' => input('store_ids', []),
        ];
        try {
            $res = (new ShoppingMallStoreService())->mallBindStore($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 批量绑定楼层
     * @return \think\response\Json
     */
    public function storeBindFloor()
    {
        $params = [
            'mall_id' => $this->mall_id,
            'store_ids' => input('store_ids', []),
            'room_id' => input('room_id', 0, 'trim,intval'),
        ];
        try {
            $res = (new ShoppingMallStoreService())->storeBindFloor($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 导入店铺信息
     * @return \think\response\Json
     */
    public function importStore()
    {
        $params = [
            'mall_id' => $this->mall_id,
            'file_url' => input('file_url', '', 'trim,string'),
        ];
        try {
            $res = (new ShoppingMallStoreService())->importStore($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 店铺列表
     * @return \think\response\Json
     */
    public function getMallBindStore()
    {
        $params = [
            'mall_id' => $this->mall_id,
            'floor_id' => input('floor_id', 0, 'trim,intval'),
        ];
        try {
            $res = (new ShoppingMallStoreService())->getMallBindStore($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function unbindStore()
    {
        $params = [
            'mall_id' => $this->mall_id,
            'store_id' => input('store_id', 0, 'trim,intval'),
        ];
        try {
            $res = (new ShoppingMallStoreService())->unbindStore($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}