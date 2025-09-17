<?php


namespace app\community\controller\property_api;


use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\Property\HouseVillageNewOrderService;

class CashierController extends CommunityBaseController
{
    public function propertyOwnVillagePayOrderList() {
        $service_house_new_cashier = new HouseNewCashierService();
        // 获取登录信息
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)){
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $page          = $this->request->post('page', 1);
        $limit         = $this->request->post('limit', 20);
        $village_id    = $this->request->post('village_id');
        $param = [
            'property_id'  => $property_id,
            'village_id'   => $village_id,
            'page'         => $page,
            'limit'        => $limit,
        ];
        try{
            $serviceHouseVillageNewOrder = new HouseVillageNewOrderService();
            $data  = $serviceHouseVillageNewOrder->propertyOwnVillageOrder($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        $data['total_limit'] = $limit;
        return api_output(0,$data);
    }
}