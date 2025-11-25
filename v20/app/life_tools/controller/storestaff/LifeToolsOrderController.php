<?php 

namespace app\life_tools\controller\storestaff;

use app\life_tools\model\service\LifeToolsAppointService;
use app\life_tools\model\service\LifeToolsCardOrderService;
use app\life_tools\model\service\LifeToolsOrderService;
use app\storestaff\controller\storestaff\AuthBaseController;
use app\storestaff\model\service\ScanService;

class LifeToolsOrderController extends AuthBaseController
{
    /**
     * 店员端自主购票-提交订单页数据
     */
    public function confirm()
    {
        $params = [];
        $params['staff_id'] = $this->staffId;
        $params['mer_id'] = $this->merId;
        $params['ticket_id'] = $this->request->post('ticket_id', 0, 'intval');
        $params['num'] = $this->request->post('num', 1, 'intval');
        $params['select_id'] = $this->request->post('select_id', 0, 'intval');
        try {
            $data = (new LifeToolsOrderService())->staffConfirm($params); 
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data);
    } 

    /**
     * 店员端自主购票-确认订单
     */
    public function saveOrder()
    {
        $params = [];
        $params['staff_id'] = $this->staffId;
        $params['staff'] = $this->staffUser;
        $params['mer_id'] = $this->merId;
        $params['ticket_id'] = $this->request->post('ticket_id', 0, 'intval');
        $params['select_id'] = $this->request->post('select_id', 0, 'intval');
        $params['num'] = $this->request->post('num', 1, 'intval');
        $params['phone'] = $this->request->post('phone', '', 'trim');
        $params['name'] = $this->request->post('name', '', 'trim');
        $params['pay_price'] = $this->request->post('pay_price', 0, 'trim');
        $params['sku_ids'] = $this->request->post('sku_ids', '', 'trim');
        $params['activity_type'] = $this->request->post('activity_type', '', 'trim');
        try {
            $data = (new LifeToolsOrderService())->staffSaveOrder($params); 
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data);
    }

     /**
     * 去支付
     */
    public function goPay()
    {
        $params = [];
        $params['staff_id'] = $this->staffId;
        $params['mer_id'] = $this->merId;
        $params['staffUser'] = $this->staffUser;
        $params['order_id'] = $this->request->post('order_id', '', 'trim');
        $params['pay_code'] = $this->request->post('pay_code', 0, 'trim');
        $params['pay_type'] = $this->request->post('pay_type', '', 'trim');
        $params['getPrice'] = $this->request->post('getPrice', 0); //收款
        $params['giveChange'] = $this->request->post('giveChange', 0);//找零

        try {
            $data = (new LifeToolsOrderService())->goPay($params); 
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data);
    }

}