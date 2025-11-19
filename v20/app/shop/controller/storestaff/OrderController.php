<?php

namespace app\shop\controller\storestaff;

use app\shop\model\service\order\ShopOrderService;
use app\storestaff\controller\storestaff\AuthBaseController;
use app\storestaff\model\service\StoreStaffService;

/**
 * 外卖店员
 * @author: 张涛
 * @date: 2020/11/9
 * @package app\shop\controller\storestaff
 */
class OrderController extends AuthBaseController
{
    /**
     * 订单搜索
     * @author: 张涛
     * @date: 2020/11/13
     */
    public function search()
    {
        try {
            $params = [];
            $params['keyword'] = $this->request->param('keyword', '', 'trim');
            $params['store_id'] = $this->staffUser['store_id'] ?? 0;
            $params['page'] = $this->request->param('page', 1, 'intval');
            $params['pageSize'] = $this->request->param('pageSize', 20, 'intval');
            $params['pick_addr_ids'] = $this->staffUser['pick_addr_ids'] ?? 0;
            $rs = (new ShopOrderService())->getLists($params);
            return api_output(0, $rs, '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 待处理状态标签
     * @author: 张涛
     * @date: 2020/11/4
     */
    public function pendingStatus()
    {
        $orderService = new ShopOrderService();
        $storeId = $this->staffUser['store_id'] ?? 0;
        $pick_addr_ids = $this->staffUser['pick_addr_ids']??'';
        $mer_id = $this->merId;
        $progressCount = $orderService->getCount(['store_id' => $storeId, 'status' => 'progress', 'pick_addr_ids' => $pick_addr_ids,'mer_id'=>$mer_id]);
        $newCount =  $orderService->getCount(['store_id' => $storeId, 'status' => 'new', 'pick_addr_ids' => $pick_addr_ids,'mer_id'=>$mer_id]);
        $refundCount =  $orderService->getCount(['store_id' => $storeId, 'status' => 'refund', 'pick_addr_ids' => $pick_addr_ids,'mer_id'=>$mer_id]);
        $rs = [
            ['title' => '进行中', 'count' => $progressCount, 'status' => 'progress'],
            ['title' => '新订单', 'count' => $newCount, 'status' => 'new'],
            ['title' => '退款单', 'count' => $refundCount, 'status' => 'refund'],
        ];
        return api_output(0, $rs, '成功');
    }

    /**
     * 订单列表
     *
     * @author: 张涛
     * @date: 2020/11/03
     */
    public function pendingLists()
    {
        try {
            $params = [];
            $params['status'] = $this->request->param('status', '', 'trim');
            $params['page'] = $this->request->param('page', 1, 'intval');
            $params['pageSize'] = $this->request->param('pageSize', 20, 'intval');
            $params['store_id'] = $this->staffUser['store_id'] ?? 0;
            $params['pick_addr_ids'] = $this->staffUser['pick_addr_ids'] ?? '';
            $params['mer_id'] = $this->merId ?? '';
            $rs = (new ShopOrderService())->getPendingLists($params);
            return api_output(0, $rs, '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 接单
     *
     * @author: 张涛
     * @date: 2020/11/09
     */
    public function take()
    {
        try {
            $orderId = $this->request->param('order_id', 0, 'intval');
            $rs = (new StoreStaffService())->take($orderId, 'shop', $this->staffId, $this->staffUser['pick_addr_ids']??'');
            return api_output(0, $rs, '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 取消订单
     *
     * @author: 张涛
     * @date: 2020/11/09
     */
    public function cancel()
    {
        try {
            $orderId = $this->request->param('order_id', 0, 'intval');
            $rs = (new StoreStaffService())->cancel($orderId, 'shop', $this->staffId, $this->staffUser['pick_addr_ids']??'');
            return api_output(0, $rs, '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }


    /**
     * 订单管理-订单状态
     * @author: 张涛
     * @date: 2020/11/4
     */
    public function status()
    {
        $rs = [
            ['title' => '全部', 'status' => 'all'],
            ['title' => '完成单', 'status' => 'complete'],
            ['title' => '取消单', 'status' => 'cancal'],
            ['title' => '配送中', 'status' => 'delivery']
        ];
        return api_output(0, $rs, '成功');
    }


    /**
     * 订单管理订单列表
     * @author: 张涛
     * @date: 2020/11/11
     */
    public function lists()
    {
        try {
            $params = [];
            $params['status'] = $this->request->param('status', '', 'trim');
            $params['date'] = $this->request->param('date', '', 'trim');
            $params['keyword'] = $this->request->param('keyword', '', 'trim');
            $params['page'] = $this->request->param('page', 1, 'intval');
            $params['pageSize'] = $this->request->param('pageSize', 20, 'intval');
            $params['store_id'] = $this->staffUser['store_id'] ?? 0;
            $params['pick_addr_ids'] = $this->staffUser['pick_addr_ids'] ?? '';
            $rs = (new ShopOrderService())->getLists($params);
            return api_output(0, $rs, '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 预订单列表
     * @author: 张涛
     * @date: 2020/11/11
     */
    public function bookOrdersLists()
    {
        try {
            $params = [];
            $params['date'] = $this->request->param('date', '', 'trim');
            $params['page'] = $this->request->param('page', 1, 'intval');
            $params['pageSize'] = $this->request->param('pageSize', 20, 'intval');
            $params['store_id'] = $this->staffUser['store_id'] ?? 0;
            $params['pick_addr_ids'] = $this->staffUser['pick_addr_ids'] ?? 0;
            $rs = (new ShopOrderService())->getBookOrderLists($params);
            return api_output(0, $rs, '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 打印小票
     * @author: 张涛
     * @date: 2020/11/11
     */
    public function print()
    {
        try {
            $orderId = $this->request->param('order_id', 0, 'intval');
            invoke_cms_model('Shop_order/printTicket', [$orderId, -1, 'shop_order', '', 1]);
            return api_output(0, [], '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 发货
     * @author: 张涛
     * @date: 2020/11/12
     */
    public function delivery()
    {
        try {
            $orderId = $this->request->param('order_id', 0, 'intval');
            $expressId = $this->request->param('express_id', 0, 'intval');
            $expressNumber = $this->request->param('express_number', '', 'trim');
            $rs = (new StoreStaffService())->delivery($orderId, 'shop', $this->staffId, ['express_id' => $expressId, 'express_number' => $expressNumber]);
            return api_output(0, $rs, '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 录入重量
     * @author: 张涛
     * @date: 2020/11/12
     */
    public function writeWeight()
    {
        try {
            $orderId = $this->request->param('order_id', 0, 'intval');
            $detailIds = $this->request->param('detail_id', []);
            $realWeight = $this->request->param('real_weight', []);
            $rs = (new StoreStaffService())->writeWeight($orderId, 'shop', $this->staffId, ['detail_id' => $detailIds, 'real_weight' => $realWeight]);
            return api_output(0, $rs, '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 申请售后
     * @author: 张涛
     * @date: 2020/11/12
     */
    public function replyRefund()
    {
        try {
            $orderId = $this->request->param('order_id', 0, 'intval');
            $param['refund_id'] = $this->request->param('refund_id', 0, 'intval');
            $param['reply_content'] = $this->request->param('reply_content', '', 'trim');
            $param['type'] = $this->request->param('type', '', 'trim');  //disagree:拒绝  agree：同意
            $param['ticket'] = $this->request->param('ticket', '', 'trim');
            $param['Device-Id'] = $this->request->param('Device-Id', '', 'trim');
            $param['pick_addr_ids'] = $this->staffUser['pick_addr_ids']??'';
            $rs = (new StoreStaffService())->replyRefund($orderId, 'shop', $this->staffId, $param);
            return api_output(0, $rs, '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 预订单处理订单
     * @author: 张涛
     * @date: 2020/11/13
     */
    public function handleBookOrder()
    {
        try {
            $orderId = $this->request->param('order_id', 0, 'intval');
            $rs = (new StoreStaffService())->handleBookOrder($orderId, 'shop', $this->staffId, $this->staffUser['pick_addr_ids']??'');
            return api_output(0, $rs, '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 确认消费
     * @author: 张涛
     * @date: 2020/11/13
     */
    public function confirmConsume()
    {
        try {
            $orderId = $this->request->param('order_id', 0, 'intval');
            $rs = (new StoreStaffService())->confirmConsume($orderId, 'shop', $this->staffId, $this->staffUser['pick_addr_ids']??'');
            return api_output(0, $rs, '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     *
     * @author: 张涛
     * @date: 2020/11/13
     */
    public function expressInfo()
    {
        try {
            $orderId = $this->request->param('order_id', 0, 'intval');
            $expressNumber = $this->request->param('express_number', '', 'trim');
            $expressCode = $this->request->param('express_code', '', 'trim');

            if (empty($orderId) || empty($expressNumber) || empty($expressCode)) {
                return api_output(1003, [], '参数有误');
            }
            $order = (new ShopOrderService())->getOrderInfo(['order_id' => $orderId]);
            if (empty($order) || $order['is_pick_in_store'] != 3 || empty($order['express_number'])) {
                return api_output(1003, [], '订单未发货');
            }
            $rs = (new ShopOrderService())->expressInfo($expressCode, $expressNumber, $order['userphone']);
            return api_output(0, $rs, '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 新订单提醒
     * @author: 张涛
     * @date: 2020/11/16
     */
    public function newOrder()
    {
        try {
            $rs = (new StoreStaffService())->getNewOrderCount('shop', $this->staffId);
            return api_output(0, ['count' => $rs], '成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    //店员端订单地址变更审核
    public function checkChangeAddress()
    {
        $orderId = input('order_id');
        $status  = input('status');
        $type    = input('type');

        if(empty($orderId) || empty($status) || empty($type)){
            return api_output_error(1001, '参数不正确！');
        }
        $result = invoke_cms_model('System_order/checkChangeAddress',[$orderId, $status, $type]);

        return api_output(0, $result, '');
    }
}
