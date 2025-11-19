<?php
class KeloopAction extends BaseAction
{
    public function index()
    {
        $param = $_POST;
        fdump_sql($param, 'Keloop_callback');
        $sdk = new Keloop();
        $result = $sdk->checkSign($param);
        if (false === $result) {
            exit('验签失败');
        } else {
            $order_status = $param['state'];
            $trade_no = $param['trade_no'];
            $dm_name = $param['courier'];
            $dm_mobile = $param['tel'];
            $last_time = strtotime($param['update_time']);

            $shopOrderDB = D('Shop_order');
            $orderLogDB = D('Shop_order_log');

            if ($order = $shopOrderDB->field(true)->where(array('keloop_trade_no' => $trade_no))->find()) {
                $order_id = $order['order_id'];
                if (in_array($order['status'], array(2, 3, 4, 5))) {
                    return false;
                }
                if ($order['order_status'] == 3 && ($order_status == 4 || $order_status == 5)) {
                    return false;
                }
            }else{
                exit;
            }
            switch ($order_status) {
                case 4://取单中（已接单/已抢单）
                    $deliver_info = serialize(array('name' => $dm_name, 'phone' => $dm_mobile));
                    $shopOrderDB->where(array('order_id' => $order_id))->data(array('order_status' => 2, 'last_time' => $last_time, 'deliver_info' => $deliver_info))->save();
                    $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 3, 'name' => $dm_name, 'phone' => $dm_mobile));
                    break;
                case 5://送单中（已取单）
                    $shopOrderDB->where(array('order_id' => $order_id))->data(array('order_status' => 3 ,'last_time' => $last_time))->save();
                    $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 4, 'name' => $dm_name, 'phone' => $dm_mobile));
                    $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 5, 'name' => $dm_name, 'phone' => $dm_mobile));
                    break;
                case 6://已完成（已送达）
                        $data = array('order_status' => 5, 'status' => 2, 'last_time' => $last_time, 'use_time' => $last_time);
                        if ($result = $shopOrderDB->where(array('order_id' => $order_id))->data($data)->save()) {
                            D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->save(array('status' => 4));
                            $shopOrderDB->shop_notice($order);
                            $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 6, 'name' => $dm_name, 'phone' => $dm_mobile));
                        }
                    break;
                case 7://（已撤销）
                    $goodsDB = D('Shop_goods');
                    $orderDetailDB = D('Shop_order_detail');
                    $shopOrderDB->where(array('order_id' => $order_id))->save(array('status' => 5, 'last_time' => $last_time, 'cancel_type' => 6, 'is_rollback' => 1));//取消类型（0:pc店员，1:wap店员，2:andriod店员,3:ios店员，4：打包app店员，5：用户，6：配送员, 7:超时取消）
                    $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 10, 'name' => $dm_name, 'phone' => $dm_mobile));
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
                    break;
            }
            exit('success');
        }
    }
}