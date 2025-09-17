<?php

/*
 * 外卖订单接单后超时自动送达功能
 * */

class plan_waimai_overtime_deliver extends plan_base {

    protected $config;
    protected $store;

	public function runTask() {
        $status = 2;
        $list = D('Shop_order')
            ->join('as s LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store_shop m ON m.store_id = s.store_id')
            ->where([
                's.status' => 1,
                's.order_status' => 1,
                's.create_time' => ['EGT', time() - 86400*30],
                'm.auto_overtime_deliver' => ['GT', 0],
                '_string' => '(s.expect_use_time <= ' . time() . ' - m.auto_overtime_deliver * 3600 )'
            ])
            ->field('s.*')
            ->limit(20)
            ->select();
        if ($list) {
            foreach ($list as $order) {
//                $store_shop = D(_view('Merchant_store_shop'))->field(true)->where(['store_id' => $order['store_id']])->find();
//                if ($store_shop && $store_shop['auto_overtime_deliver'] > 0) {
//                    $time = time() - 3600 * $store_shop['auto_overtime_deliver'];
//                    if ($order['last_time'] <= $time) {
                        $order_detail = M('Shop_order_detail')->where(array('order_id' => $order['order_id']))->select();
                        if ($order_detail) {
                            foreach ($order_detail as $ov) {
                                $data = [];
                                if ($ov['give_coupon_id']) {
                                    $data['coupon_id'] = $ov['give_coupon_id'];
                                    $data['uid'] = $order['uid'];
                                    $data['num'] = 1;
                                    $data['receive_time'] = time();
                                    $data['is_use'] = 0;
                                    M('Card_new_coupon_hadpull')->data($data)->add();
                                }
                            }
                        }
                        if ($order['group_id'] > 0) {
                            $team_info = M('mall_group_team')->where(array('id' => $order['team_id']))->find();
                            if (isset($team_info)) {
                                if ($team_info['status'] == 0 || $team_info['status'] == 3) {
                                    continue;
                                }
                            }
                        }
                        if ($order['is_refund']) {
                            continue;
                        }
                        $data = [];
                        $data['status'] = $status;
                        $data['post_time'] = $_SERVER['REQUEST_TIME'];
                        $condition = array('mer_id' => $order['mer_id'], 'order_id' => $order['order_id'], 'store_id' => $order['store_id']);
                        $database = D('Shop_order');
                        if ($order['platform'] == 1) {//eleme
                            $eleme = D('Eleme_shop')->getElemeObj($order['store_id']);
                            if ($eleme != false) {
                                if ($status == 1) {
                                    $result = $eleme->getDataByApi("eleme.order.confirmOrderLite", array('orderId' => $order['platform_id']));
                                } elseif ($status == 2 || $status == 3) {
                                    $result = $eleme->getDataByApi("eleme.order.receivedOrderLite", array('orderId' => $order['platform_id']));
                                } elseif ($status == 4) {
                                    $result = $eleme->getDataByApi("eleme.order.agreeRefundLite", array('orderId' => $order['platform_id']));
                                } elseif ($status == 5) {
                                    $result = $eleme->getDataByApi("eleme.order.cancelOrderLite", array('orderId' => $order['platform_id'], 'type' => 'fakeOrder', 'remark' => L_('无法取得联系')));
                                }
                                if ($result['error']){
                                    fdump_sql($result,'elem_storestaff_result_pc');
                                }
                                if ($result['error'] && $result['error']['message'] != L_('操作失败，订单已确认')) {
                                    continue;//饿了么返回的错误
                                } else {
                                    $data['use_time']  = time();
                                    $data['last_time'] = time();
                                    $database->where($condition)->save($data);
                                    continue;
                                }
                            } else {
                                continue;//店铺对接饿了么有问题，请联系商家处理！
                            }
                        } elseif ($order['platform'] == 2) {//meituan
                            continue;//美团不支持确认消费
                        }
                        $supply = D('Deliver_supply')->field(true)->where(array('order_id' => $order['order_id'], 'item' => 2))->find();
                        if ($order['is_pick_in_store'] == 0 && $status == 2 && $supply && $supply['uid']) {//平台配送，当配送员接单后店员就不能把订单修改成已消费状态
                            continue;//您不能将该订单改成已消费状态
                        }
                        if (empty($order['third_id']) && $order['pay_type'] == 'offline') {
                            $order['paid'] = 0;
                        }
                        if ($order['paid'] == 0 && $status != 5 && $status != 4) {
                            $notOffline = 1;
                            if (C('config.pay_offline_open') == 1) {
                                $now_merchant = D('Merchant')->get_info($order['mer_id']);
                                if ($now_merchant) {
                                    $notOffline =($now_merchant['is_close_offline'] == 0 && $now_merchant['is_offline'] == 1) ? 0 : 1;
                                }
                            }
                            if ($notOffline) {
                                continue;//不支持线下支付
                            }
                        }
                        if ($status == 2 && $order['paid'] == 0) {//将未支付的订单，由店员改成已消费，其订单状态则修改成线下已支付！
                            $data['third_id'] = $order['order_id'];
                            $order['pay_type'] = 'offline';
                            $data['paid'] = 1;
                            $order['pay_time'] = $_SERVER['REQUEST_TIME'];
                        }

                        if ($status == 2) {
                            $data['order_status'] = 6;//配送完成
                            $supply = D('Deliver_supply')->field(true)->where(array('order_id' => $order['order_id'], 'item' => 2))->find();
                            if ($supply) {
                                if ($supply['status'] < 2) {
                                    D('Deliver_supply')->where(array('order_id' => $order['order_id'], 'item' => 2))->delete();
                                } else {
                                    D('Deliver_supply')->where(array('order_id' => $order['order_id'], 'item' => 2))->save(array('status' => 5));
                                }
                            }
                            //只有完成才有送达时间
                            $data['use_time'] = $_SERVER['REQUEST_TIME'];

                            //检查含有称重商品的订单，是否需要部分退款
                            if(C('config.is_open_shop_weigh') == 1 && $order['is_weight'] == 1){
                                D('Shop_order')->checkWeightOrder($order);
                            }
                        }
                        $data['last_time'] = $_SERVER['REQUEST_TIME'];

                        // 周期购信息 已消费
                        if($status == 2 && $order['periodic_type'] && $order['is_pick_in_store'] == 2){//周期购自提
                            // 状态改为已自提
                            $p_data = array();
                            $p_data['status'] = 2;
                            $p_data['update_time'] = $_SERVER['REQUEST_TIME'];
                            $p_data['accept_time'] = $_SERVER['REQUEST_TIME'];
                            $p_data['arrive_time'] = $_SERVER['REQUEST_TIME'];
                            $p_data['deliver_uid'] = 0;
                            $p_where = array();
                            //获得最近需要配送的周期
                            $periodic_deliver =M('Mall_periodic_deliver')->where(array('order_id'=>$order['order_id'],'status'=>0))->order('deliver_num asc')->find();
                            $p_where['id'] = $periodic_deliver['id'];
                            if(M('Mall_periodic_deliver')->where($p_where)->data($p_data)->save()){
                                unset($data['status']);
                            }
                        }

                        if (D("Shop_order")->where(array('mer_id' => $order['mer_id'], 'order_id' => $order['order_id'], 'store_id' => $order['store_id']))->save($data)) {
                            if ($status == 2 && $order['status'] != 2 && $order['status'] != 3) {//当订单由未消费修改成已消费时做的通知
                                D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->save(array('status' => 4));
                                D('Shop_order_log')->add_log(array('order_id' => $order['order_id'], 'status' => 7, 'name' => '超时自动消费', 'phone' => ''));
                                $this->shop_notice($order);
                            }
                            /*更新记录的平台订单信息 2019/01/04 */
                            $from_type = 'shop';
                            D('System_order')->save_order_info($from_type,$order['order_id'],$data);
                        }
                        continue;
//                    }
//                }
            }
        }
		return true;
	}


    private function shop_notice($order, $is_staff = false)
    {
        $this->config = C('config');
        $database_merchant_store = D('Merchant_store');
        $condition_merchant_store['store_id'] = $order['store_id'];
        $this->store = $database_merchant_store->field(true)->where($condition_merchant_store)->find();
        if (empty($this->store)) {
            return true;
        }

        //验证增加商家余额
        $order['order_type'] = 'shop';
        if ($is_staff) {
            if($order['card_id']){
                $use_result = D('Card_new_coupon')->user_coupon($order['card_id'], $order['order_id'], 'shop', $order['mer_id'], $order['uid']);
                if($use_result['error_code']){
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

            if($order['coupon_id']){
                $use_result = D('System_coupon')->user_coupon($order['coupon_id'], $order['order_id'], 'shop', $order['mer_id'], $order['uid']);
                if($use_result['error_code']){
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

            // 使用积分
            if ($order['score_used_count'] > 0) {
                $use_result = D('User')->user_score($order['uid'], $order['score_used_count'], L_('购买 X1 扣除X1',array('X1'=>$order['name'],'X2'=>C('config.score_name'))));
                if ($use_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

            if(floatval($order['merchant_balance']) > 0){
                $use_result = D('Card_new')->use_money($order['uid'], $order['mer_id'], $order['merchant_balance'], L_('购买 X1 扣除会员卡余额',$order['real_orderid']));
                if($use_result['error_code']){
                    return array('error'=>1,'msg'=>$use_result['msg']);
                }
            }

            if(floatval($order['card_give_money']) > 0){
                $use_result = D('Card_new')->use_give_money($order['uid'], $order['mer_id'], $order['card_give_money'], L_('购买 X1 扣除会员卡赠送余额',$order['real_orderid']));
                if ($use_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

            $goods = D('Shop_order_detail')->field(true)->where(array('order_id' => $order['order_id']))->select();
            $goods_obj = D("Shop_goods");
            foreach ($goods as $gr) {
                $goods_obj->update_stock($gr);//修改库存
            }

            $order['order_type'] = 'shop_offline';
        }
        //D('Merchant_money_list')->add_money($this->store['mer_id'],L_('用户在 ') . $this->store['name'] . ' 中消费' . floatval($order['price']) . C('config.Currency_txt').L_('记入收入'),$order);
        if ($order['union_mer_id']) {
            $pre_desc = L_('商家会员卡联盟----');
        }
        $order['desc']=$pre_desc.L_('用户在 X1 中消费X2记入收入',array('X1'=>$this->store['name'],'X2'=>floatval($order['price']) . C('config.Currency_txt')));
        D('SystemBill')->bill_method($order['is_own'],$order);

        //商家推广分佣
        $now_user = M('User')->where(array('uid'=>$order['uid']))->find();
        D('Merchant_spread')->add_spread_list($order,$now_user,'shop',$now_user['nickname'].L_('用户购买X1商品获得佣金',['X1'=>$this->config['shop_alias_name']]));

        //积分
        if(C('config.open_extra_price')==1){
            $score = D('Percent_rate')->get_extra_money($order);
            if($score>0){
                D('User')->add_score($order['uid'], floor($score),L_('在 X1 中消费X2 获得X3',array('X1'=>$this->store['name'],'X2'=>floatval($order['price']) . C('config.Currency_txt'),'X3'=>C('config.extra_price_alias_name'))));
            }

        }else{
            if($this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $order['score_discount_type']!=2)&& ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
                if($order['is_own'] && $this->config['user_own_pay_get_score']!=1 ){
                    $order['payment_money'] = 0;
                }
                if($order['order_from']==1){
                    $score_get = D('Percent_rate')->shop_get_score($order);
                } elseif($this->config['shop_goods_score_edit'] == 1){
                    $score_get = D('Percent_rate')->shop_get_score($order);
                    $score_get = D('Percent_rate')->get_user_add_score_times($score_get,$order['uid']);
                }else{
                    $this->config['score_get'] = D('Percent_rate')->get_user_add_score_times($this->config['score_get'],$order['uid']);
                    $score_get  = ($order['payment_money'] + $order['balance_pay']) * $this->config['score_get'];
                }

                if ($score_get>0) {
                    $param['order_type'] = 'shop';
                    $param['order_id'] = $order['order_id'];
                    D('User')->add_score($order['uid'], $score_get, L_('在 X1 中消费X2 获得X3',array('X1'=>$this->store['name'],'X2'=>floatval($order['price']) . C('config.Currency_txt'),'X3'=>$this->config['score_name'])),0,$param);
                    D('Scroll_msg')->add_msg('shop',$now_user['uid'],L_('用户X1于X2 中消费获得X3',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']). $this->store['name'],'X3'=>$this->config['score_name'])));
                }
            }
            D('Userinfo')->add_score($order['uid'], $order['mer_id'], $order['price'], L_('在 X1 中消费X2 获得X3',array('X1'=>$this->store['name'],'X2'=>floatval($order['price']) . C('config.Currency_txt'),'X3'=>$this->config['score_name'])));
        }
        //短信
        $sms_data = array('mer_id' => $this->store['mer_id'], 'store_id' => $this->store['store_id'], 'type' => 'shop');
        if ($this->config['sms_shop_finish_order'] == 1 || $this->config['sms_shop_finish_order'] == 3) {
            if (empty($order['phone'])) {
                $user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();
                $order['phone'] = $user['phone'];
            }
            $sms_data['uid'] = $order['uid'];
            $sms_data['mobile'] = $order['userphone'];
            $sms_data['sendto'] = 'user';
            $sms_data['content'] = L_('您在 X1店中下的订单(订单号：X2),已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！',array('X1'=>$this->store['name'],'X2'=>$order['real_orderid']));
            Sms::sendSms($sms_data);
        }
        if ($this->config['sms_shop_finish_order'] == 2 || $this->config['sms_shop_finish_order'] == 3) {
            $sms_data['uid'] = 0;
            $sms_data['mobile'] = $this->store['phone'];
            $sms_data['sendto'] = 'merchant';
            $sms_data['content'] = L_('顾客购买的X1的订单(订单号：X2),已经完成了消费！',array('X1'=>$order['name'],'X2'=>$order['real_orderid']));
            Sms::sendSms($sms_data);
        }

        //微信小程序订阅消息提醒
        $wxapp_message_send = false;
        if($now_user['wxapp_openid'] && $order['order_from']!=1){
            $param['page'] = '/pages/shop_new/orderDetail/orderDetail?order_id='.$order['order_id'];
            $param['wxapp_openid'] = $now_user['wxapp_openid'];
            $param['type'] = 'order_status_change';
            // 获得商品信息
            $goods_name = D('Shop_order_detail')->where(['order_id'=>$order['order_id']])->order('id ASC')->find()['name'];
            $goodsCount = D('Shop_order_detail')->field('sum(num) as total')->where(['order_id'=>$order['order_id']])->find()['total'];
            $order['goods_desc'] = $goodsCount > 1 ? L_('X1等X2个商品',['X1'=>$goods_name,'X2'=>$goodsCount]) : $goods_name;

            $order['status_str'] = L_('已送达');
            $order['note'] = L_('您的订单已送达，请及时关注~');
            $param['data_info'] = $order;
            $wxapp_message_send = D('Wxapp_template')->sendWxappMessage($param);
        }

        //打印
        $printHaddle = new PrintHaddle();
        $printType = $is_staff == true ? 1 : 2;
        $printHaddle->printit($order['order_id'], 'shop_order', $printType);
    }

}

?>