<?php

/**
 * 景区团体票订单控制器
 */

namespace app\life_tools\controller\api;

use app\life_tools\model\service\group\LifeToolsGroupOrderService;

class LifeToolsGroupOrderController extends ApiBaseController
{

    /**
     * 订单状态列表
     */
    public function getGroupOrderStatusList()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        try {
            $data = (new LifeToolsGroupOrderService())->getGroupOrderStatusList($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 订单列表
     */
    public function getGroupOrderList()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['status'] = $this->request->post('status', 0, 'intval');
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        try {
            $data = (new LifeToolsGroupOrderService())->getGroupOrderList($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 提交审核
     */
    public function submitAudit()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['order_id'] = $this->request->post('order_id', 0, 'intval');
        try {
            $data = (new LifeToolsGroupOrderService())->submitAudit($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, '已提交审核');
    }

    
    /**
     * 支付成功二维码页面接口
     */
    public function paySuccess()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['order_id'] = $this->request->post('order_id', 0, 'intval');
        try {
            $data = (new LifeToolsGroupOrderService())->paySuccess($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }
}
