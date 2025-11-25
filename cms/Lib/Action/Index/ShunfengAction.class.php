<?php

/**
 * 顺丰配送通知回调接口
 * User: 张涛
 * Date: 2020/8/6
 */
class ShunfengAction extends BaseAction
{
    private function orderComplete($callbackData)
    {
        $shopOrderDB = D('Shop_order');
        $orderLogDB = D('Shop_order_log');
        $orderStatus = $callbackData['order_status'];
        $partnerOrderCode = $callbackData['shop_order_id'];
        $pushTime = $callbackData['push_time'];

        if ($order = $shopOrderDB->where(['real_orderid' => $partnerOrderCode])->find()) {
            if ($order['status'] == 2) {
                return false;
            }
            //订单完成
            $orderId = $order['order_id'];
            if ($result = $shopOrderDB->where(['order_id' => $orderId])->save(['order_status' => 5, 'status' => 2, 'last_time' => $pushTime,'use_time'=>$pushTime])) {
                D('Pick_order')->where(['store_id' => $order['store_id'], 'order_id' => $orderId])->save(['status' => 4]);
                $shopOrderDB->shop_notice($order);
                $orderLogDB->add_log(['order_id' => $orderId, 'status' => 6, 'name' => $callbackData['operator_name']]);
                //检查含有称重商品的订单，是否需要部分退款
                if ($this->config['is_open_shop_weigh'] == 1 && $order['is_weight'] == 1) {
                    D('Shop_order')->checkWeightOrder($order);
                }
            }
        }
        echo json_encode(['error_code'=>0,'error_msg'=>'success']);
        exit;
    }

    private function sfCancel($callbackData)
    {
        $shopOrderDB = D('Shop_order');
        $orderLogDB = D('Shop_order_log');
        $orderStatus = $callbackData['order_status'];
        $partnerOrderCode = $callbackData['shop_order_id'];
        $pushTime = $callbackData['push_time'];

        if ($order = $shopOrderDB->where(['real_orderid' => $partnerOrderCode])->find()) {
            if ($order['status'] == 5) {
                return false;
            }
            D('Shop_order_log')->add_log(array('order_id' => $order['order_id'], 'status' => 10, 'name' => $callbackData['operator_name'], 'note' => $callbackData['cancel_reason']));
            D('Shop_order')->check_refund($order, []);
        }
        echo json_encode(['error_code'=>0,'error_msg'=>'success']);
        exit;
    }


    public function callback()
    {
        $jsonData = file_get_contents("php://input");
        $callbackData = json_decode($jsonData, true);
        $urlIndex = $callbackData['url_index'] ?? '';

        fdump_sql($callbackData, 'shunfeng_callback');

        if (empty($urlIndex)) {
            return false;
        }
        if ($urlIndex == 'order_complete') {
            $this->orderComplete($callbackData);
            return false;
        } else if ($urlIndex == 'sf_cancel') {
            $this->sfCancel($callbackData);
            return false;
        }

        $shopOrderDB = D('Shop_order');
        $orderLogDB = D('Shop_order_log');
        $orderStatus = $callbackData['order_status'];
        $partnerOrderCode = $callbackData['shop_order_id'];
        $pushTime = $callbackData['push_time'];

        if ($order = $shopOrderDB->where(['real_orderid' => $partnerOrderCode])->find()) {
            if (in_array($order['status'], array(2, 3, 4, 5))) {
                return false;
            }

            $orderId = $order['order_id'];
            $deliverInfo = ['name' => $callbackData['operator_name'] ?? '', 'phone' => $callbackData['operator_phone'] ?? ''];
            switch ($orderStatus) {
                case 10:
                    //配送员确认
                    $shopOrderDB->where(['order_id' => $orderId])->save(['order_status' => 2, 'last_time' => $pushTime, 'deliver_info' => serialize($deliverInfo)]);
                    $orderLogDB->add_log(['order_id' => $orderId, 'status' => 3, 'name' => $deliverInfo['name'], 'phone' => $deliverInfo['phone']]);
                    break;
                case 12:
                    //配送员到店
                    $orderLogDB->add_log(array('order_id' => $orderId, 'status' => 4, 'name' => $deliverInfo['name'], 'phone' => $deliverInfo['phone']));
                    break;
                case 15:
                    //配送员配送中
                    $shopOrderDB->where(['order_id' => $orderId])->save(['order_status' => 3]);
                    $orderLogDB->add_log(['order_id' => $orderId, 'status' => 5, 'name' => $deliverInfo['name'], 'phone' => $deliverInfo['phone']]);
                    break;
            }
        }
        echo json_encode(['error_code'=>0,'error_msg'=>'success']);
        exit;
    }
}