<?php
/**
 * Created by PhpStorm.
 * User: win 10
 * Date: 2019/5/18
 * Time: 15:36
 */
class DingdingAction extends BaseAction{


    /**
     * 叮叮接口回调
     */
    public function dingdingCallback()
    {

        $jsonData = file_get_contents('php://input');
        fdump(date('Y-m-d H:i:s',time()),"jsonDataDingDing",true);
        fdump($jsonData,"jsonDataDingDing",true);
        $arr = json_decode($jsonData,true);
        $psy_name = empty($arr['psy_name'])? '':$arr['psy_name'];
        $psy_mobile = empty($arr['psy_mobile'])? '':$arr['psy_mobile'];
        $shopOrderDB = D('Shop_order');
        $orderLogDB = D('Shop_order_log');

        $order = D('Shop_order')->where(array('real_orderid'=>$arr['origin_id']))->find();
        $order_id = $order['order_id'];

        if($arr['status'] == 1){

        }elseif ($arr['status'] == 2) {
            D('Shop_order')->where(array('real_orderid'=>$arr['origin_id']))->save(array('order_status'=>2,'last_time' => $arr['updatetime']));

            $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 3, 'name' => $psy_name, 'phone' =>$psy_mobile));
        }elseif($arr['status'] == 3){//配送中
            D('Shop_order')->where(array('real_orderid'=>$arr['origin_id']))->save(array('order_status'=>3));

            $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 4, 'name' => $psy_name, 'phone' => $psy_mobile));
        }elseif($arr['status'] == 4){//已完成
            $data = array('order_status' => 5, 'status' => 2, 'last_time' => $arr['updatetime']);
            if ($result = $shopOrderDB->where(array('real_orderid'=>$arr['origin_id']))->data($data)->save()) {
                D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->save(array('status' => 4));
                $shopOrderDB->shop_notice($order);
                $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 6, 'name' => $psy_name, 'phone' =>$psy_mobile));
            }
        }elseif($arr['status'] == 5){
            //此取消是我们平台取消,不做处理,
        }

        //$arr['cancel_source'] == 1 为 $arr['status'] == 5 取消处理,不做处理,因为我们平台取消已处理了
        if($arr['cancel_source'] == 5){
            $goodsDB = D('Shop_goods');
            $orderDetailDB = D('Shop_order_detail');

            $shopOrderDB->where(array('order_id' => $order_id))->save(array('status' => 5, 'last_time' => $arr['updatetime'], 'cancel_type' => 6, 'is_rollback' => 1));//取消类型（0:pc店员，1:wap店员，2:andriod店员,3:ios店员，4：打包app店员，5：用户，6：配送员, 7:超时取消）

            $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 10, 'name' => $psy_name, 'phone' => $psy_mobile));
            if (($order['paid'] == 1 || $order['reduce_stock_type'] == 1) && $order['is_rollback'] == 0) {
                $details = $orderDetailDB->field(true)->where(array('order_id' => $order_id))->select();
                foreach ($details as $menu) {
                    $goodsDB->update_stock($menu, 1);//修改库存
                }
            }
            $order['status'] = 5;
            $order['cancel_type'] = 6;
            $order['is_rollback'] = 1;
            $shopOrderDB->check_refund($order);
        }elseif($arr['cancel_source'] == 4){//物流公司取消

            $database_merchant_store = D('Merchant_store');
            $condition_merchant_store['store_id'] = $order['store_id'];
            $store = $database_merchant_store->field(true)->where($condition_merchant_store)->find();
            D('Deliver_supply')->saveOrder($order_id, $store);
        }
    }
}