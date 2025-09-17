<?php

/**
 * 饿了么蜂鸟配送订单状态变更回调
 * @author 张涛
 * @date 2020/07/01
 */
class EleDeliveryAction extends BaseAction
{

    //授权回调地址
    public function auth()
    {
        $jsonData = file_get_contents("php://input");
        fdump_api(file_get_contents("php://input"), 'EleDelivery/auth', true);
        $arr = json_decode($jsonData, true);
        $code = $arr['code'];
        $merchantId = $arr['merchant_id'];

        //记录蜂鸟商户id
        D('Config')->where(array('name' => 'ele_merchant_id'))->save(array('value' => $merchantId));

        //删除缓存
        D('Config')->clearDataCacheDir('system_config');

        // 清除v20缓存
        invoke_v20_service('\app\common\model\service\CacheSqlService/deleteCache', ['param' => ['name' => 'o2oconfig']]);

        (new EleDeliveryNew())->getAccessToken($code);
    }

    public function notify()
    {
        $jsonData = file_get_contents("php://input");
        fdump_api(file_get_contents("php://input"),'EleDelivery/notify',true);
        $result = json_decode($jsonData, true);
        $callbackData = isset($result['business_data']) ? json_decode(urldecode($result['business_data']), true) : [];

        if($callbackData['callback_business_type'] != 'orderStatusNotify'){
			return ;
		}else{
			$callbackData = $callbackData['param'];
		}

        $shopOrderDB = D('Shop_order');
        $orderLogDB = D('Shop_order_log');
        $orderStatus = $callbackData['order_status'];
        $partnerOrderCode = $callbackData['partner_order_code'];
        $pushTime = $callbackData['push_time'];

        if ($order = $shopOrderDB->where(['real_orderid' => $partnerOrderCode])->find()) {
            if (in_array($order['status'], array(2, 3, 4, 5))) {
                return false;
            }
            $orderId = $order['order_id'];
            $deliverInfo = ['name' => $callbackData['carrier_driver_name'] ?? '', 'phone' => $callbackData['carrier_driver_phone'] ?? ''];
            switch ($orderStatus) {
                case 1:
                    //系统已接单
                    break;
                case 20:
                    //已分配骑手
                    $shopOrderDB->where(['order_id' => $orderId])->save(['order_status' => 2, 'last_time' => $pushTime, 'deliver_info' => serialize($deliverInfo)]);
                    $orderLogDB->add_log(['order_id' => $orderId, 'status' => 3, 'name' => $deliverInfo['name'], 'phone' => $deliverInfo['phone']]);
                    break;
                case 80:
                    //骑手已到店
                    $shopOrderDB->where(['order_id' => $orderId])->save(['order_status' => 3]);
                    $orderLogDB->add_log(['order_id' => $orderId, 'status' => 4, 'name' => $deliverInfo['name'], 'phone' => $deliverInfo['phone']]);
                    break;
                case 2:
                    //配送中
                    $orderLogDB->add_log(['order_id' => $orderId, 'status' => 5, 'name' => $deliverInfo['name'], 'phone' => $deliverInfo['phone']]);
                    break;
                case 3:
                    //已送达
                    if ($result = $shopOrderDB->where(['order_id' => $orderId])->save(['order_status' => 5, 'status' => 2, 'last_time' => $pushTime])) {
                        D('Pick_order')->where(['store_id' => $order['store_id'], 'order_id' => $orderId])->save(['status' => 4]);
                        $shopOrderDB->shop_notice($order);
                        $orderLogDB->add_log(['order_id' => $orderId, 'status' => 6, 'name' => $deliverInfo['name'], 'phone' => $deliverInfo['phone']]);
                        //检查含有称重商品的订单，是否需要部分退款
                        if ($this->config['is_open_shop_weigh'] == 1 && $order['is_weight'] == 1) {
                            D('Shop_order')->checkWeightOrder($order);
                        }
                    }
                    break;
                case 5:
                    //系统拒单/配送异常
                    break;
            }
        }
    }
}