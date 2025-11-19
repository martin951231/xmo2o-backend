<?php
class DadaAction extends BaseAction
{
    public function index()
    {
        $jsonData = file_get_contents("php://input");
        $result = json_decode($jsonData, true);
        fdump_sql($result,'dada_notify');
        if (isset($result['update_time']) && isset($result['client_id']) && isset($result['order_id'])) {
            $tData = array($result['update_time'], $result['client_id'], $result['order_id']);
            sort($tData, SORT_STRING);
            $str = implode('', $tData);
            if (isset($result['signature']) && md5($str) == $result['signature']) {
                $order_status = $result['order_status'];
                $real_orderid = $result['order_id'];
                $cancel_reason = $result['cancel_reason'];
                $dm_name = $result['dm_name'];
                $dm_mobile = $result['dm_mobile'];
                
                $shopOrderDB = D('Shop_order');
                $orderLogDB = D('Shop_order_log');
                
                if ($order = $shopOrderDB->field(true)->where(array('real_orderid' => $real_orderid))->find()) {
                    $order_id = $order['order_id'];
                    if (in_array($order['status'], array(2, 3, 4, 5))) {
                        return false;
                    }
                    if ($order['order_status'] == 3 && ($order_status == 2 || $order_status == 8)) {
                        return false;
                    }
                } else {
                    exit;
                }
                switch ($order_status) {
                    case 1://待接单
                        break;
                    case 2://待取货
                    case 8://指派单
                        $deliver_info = serialize(array('name' => $dm_name, 'phone' => $dm_mobile));
                        $shopOrderDB->where(array('order_id' => $order_id))->data(array('order_status' => 2, 'last_time' => $result['update_time'], 'deliver_info' => $deliver_info))->save();
                        $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 3, 'name' => $dm_name, 'phone' => $dm_mobile));
                        break;
                    case 100://骑手到店
                        $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 4, 'name' => $dm_name, 'phone' => $dm_mobile));
                        break;
                    case 3://配送中
                        $shopOrderDB->where(array('order_id' => $order_id))->data(array('order_status' => 3))->save();
                        $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 5, 'name' => $dm_name, 'phone' => $dm_mobile));
                        break;
                    case 4://已完成
                            $data = array('order_status' => 5, 'status' => 2, 'last_time' => $result['update_time'], 'use_time' => time());
                            if ($result = $shopOrderDB->where(array('order_id' => $order_id))->data($data)->save()) {
                                D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->save(array('status' => 4));
                                $shopOrderDB->shop_notice($order);
                                $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 6, 'name' => $dm_name, 'phone' => $dm_mobile));
                                //检查含有称重商品的订单，是否需要部分退款
                                if ($this->config['is_open_shop_weigh'] == 1 && $order['is_weight'] == 1) {
                                    D('Shop_order')->checkWeightOrder($order);
                                }
                            }
                        break;
                    case 5://已取消--取消订单这里重新给达达下一笔配送单（需求如此）
                        fdump($order,'dada_cancle_log',1);
						$store = M('Merchant_store')->where(['store_id'=>$order['store_id']])->find();
						//D('Deliver_supply')->saveOrder($order_id,$store);
                        $r = $this->reAddOrder($store,$order);
                        fdump($r,'dada_cancle_log',1);
						break;
                    case 7://已过期
                        $goodsDB = D('Shop_goods');
                        $orderDetailDB = D('Shop_order_detail');
                        
                        $shopOrderDB->where(array('order_id' => $order_id))->save(array('status' => 5, 'last_time' => $result['update_time'], 'cancel_reason' => $cancel_reason, 'cancel_type' => 6, 'is_rollback' => 1));//取消类型（0:pc店员，1:wap店员，2:andriod店员,3:ios店员，4：打包app店员，5：用户，6：配送员, 7:超时取消）
                        
                        $orderLogDB->add_log(array('order_id' => $order_id, 'status' => 10, 'name' => $dm_name, 'phone' => $dm_mobile,'note'=>L_('达达取消')));
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
            }
        }
    }

    public function reAddOrder($store,$order){
        $dada = new Dada();
        $longlat_class = new longlat();
        $data = array();
        $data['shop_id'] = $store['dada_shop_id'];//达达的门店号
        $data['order_id'] = $order['real_orderid'];//
        $data['city_code'] = $store['dada_city_code'];
        $data['order_price'] = $order['price'];
        $data['is_prepay'] = 0;
        $data['expected_fetch_time'] = time() + 1300;//期望取货时间
        $data['expected_finish_time'] = time() + 1600;//期望送达时间
        $data['user_name'] = $order['username'];//收货人姓名
        $data['user_address'] = str_replace(PHP_EOL, '', $order['address']);//收货人地址
        $data['user_phone'] = $order['userphone'];//收货人手机号（手机号和座机号必填一项）
        $data['user_tel'] = $order['userphone'];//收货人座机号（手机号和座机号必填一项）

        $location2 = $longlat_class->baiduToGcj02($order['lat'], $order['lng']);
        $data['user_lat'] = $location2['lat'];//收货人地址维度（高德坐标系）
        $data['user_lng'] = $location2['lng'];//收货人地址经度（高德坐标系）

        fdump($order,'reAddOrderLog',1);
        fdump($store,'reAddOrderLog',1);
        fdump($data,'reAddOrderLog',1);

        $result = $dada->reAddOrder($data);
        fdump($data,'reAddOrderLog',1);
        return $result;
    }

}