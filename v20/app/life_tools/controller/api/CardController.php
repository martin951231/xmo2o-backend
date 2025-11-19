<?php
/**
 * 次卡接口控制器
 */

namespace app\life_tools\controller\api;

use app\life_tools\model\service\LifeToolsCardOrderService;
use app\life_tools\model\service\LifeToolsService;

class CardController extends ApiBaseController
{
    /**
     * 次卡列表
     */
    public function cardList()
    {
        $param['tools_id'] = $this->request->param('tools_id', 0, 'intval');
        $param['type']     = $this->request->param('type', 'all', 'trim');
        try {
            $arr = (new LifeToolsService())->getCardList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 购买次卡提交订单
     */
    public function saveOrder()
    {
        $this->checkLogin();
        $card_id = $this->request->param('card_id', 0, 'intval');
        try {
            $res = (new LifeToolsCardOrderService())->saveOrder($card_id, $this->userInfo);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 次卡订单详情
     */
    public function orderDetail()
    {
        $this->checkLogin();
        $param['order_id'] = $this->request->param('order_id', 0, 'intval');
        $param['uid']      = $this->_uid;
        try {
            $arr = (new LifeToolsCardOrderService())->getUserDetail($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 次卡订单申请退款
     */
    public function supplyRefund()
    {
        $this->checkLogin();
        $param['order_id'] = $this->request->param('order_id', 0, 'intval');
        $param['reason']   = $this->request->param('reason', '', 'trim');
        try {
            (new LifeToolsCardOrderService())->supplyRefund($param);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 次卡订单撤销申请
     */
    public function revokeRefund()
    {
        $this->checkLogin();
        $order_id = $this->request->param('order_id', 0, 'intval');
        try {
            (new LifeToolsCardOrderService())->revokeRefund($order_id);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 删除次卡
     */
    public function delCard()
    {
        $this->checkLogin();
        $params = [];
        $params['uid'] = $this->_uid;
        $params['order_id'] = $this->request->post('order_id', 0, 'intval');
        try {
            (new LifeToolsCardOrderService())->delCard($params);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}