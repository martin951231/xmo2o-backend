<?php

/**
 * 针对威富通漏掉线下采用主动查询订单支付状态来适配。目前仅支持线下零售业务
 */
class plan_wft_query_order extends plan_base
{
    protected $store;

    public function runTask()
    {
        $tm = time();
        $maxQueryCount = 5;
        $mod = M('Wft_query_order');
        $shopOrderMod = M('Shop_order');

        $where = [
            'status' => 0,
            'query_count' => ['lt', $maxQueryCount],
            'create_time' => ['LT', $tm - 60]
        ];
        $record = $mod->where($where)->order('query_count DESC,id DESC')->limit(10)->select();
        if (empty($record)) {
            return true;
        }

        import('ORG.Net.Http');
        $http = new Http();
        $lowfeepay = new LowFeePay('wftpay');
        foreach ($record as $r) {
            $split = explode('_', $r['out_trade_no']);
            if ($split[0] == 'shop') {
                $thisOrder = $shopOrderMod->where(['order_id' => $split[1], 'order_from' => 6])->find();
                if (empty($thisOrder) || $thisOrder['paid']) {
                    $mod->where(['id' => $r['id']])->save(['status' => 1]);
                    continue;
                }

				$this->store = D('Merchant_store')->where(['store_id'=>$thisOrder['store_id']])->find();
                
                $query = [
                    'out_trade_no' => $r['out_trade_no'],
                    're_call' => true
                ];
                $result = $lowfeepay->scan_pay($query);
                $rawResult = $result['raw_result'];

                $update = [
                    'last_query_time' => $tm,
                    'last_query_result' => json_encode($result, JSON_UNESCAPED_UNICODE),
                    'query_count' => $r['query_count'] + 1
                ];
                if ($result['trade_state'] && $result['trade_state'] == 'SUCCESS') {
                    //支付成功
                    $data['third_id'] = $rawResult['transaction_id'];
                    $data['payment_money'] = $rawResult['total_fee'] / 100;
                    $data['pay_type'] = $result['pay_type'] ? $result['pay_type'] : 'weixin';

                    if ($data['pay_type'] == 'weixin' && $result['openid']) {
                        $nowUser = D('User')->get_user($result['openid'], 'openid');
                    } else if ($data['pay_type'] == 'alipay' && $result['alipay_uid']) {
                        $nowUser = D('User')->get_user($result['alipay_uid'], 'alipay_uid');
                    }
                    $payTime = isset($rawResult['time_end']) ? strtotime($rawResult['time_end']) : $tm;
                    $data['uid'] = $nowUser['uid'] ?? 0;
                    $data['paid'] = 1;
                    $data['status'] = 2;
                    $data['order_status'] = 5;
                    $data['from_plat'] = 3;
                    $data['is_own'] = 0;
                    $data['pay_time'] = $payTime;
                    $data['use_time'] = $payTime;

                    if ($shopOrderMod->where(['order_id' => $split[1], 'order_from' => 6, 'paid' => 0])->save($data)) {
                        $nowOrder = $shopOrderMod->where(['order_id' => $split[1]])->find();
                        $this->shop_notice($nowOrder, true);
                        D('Shop_order')->shop_score_get($nowOrder);
                        $update['status'] = 1;
                    }
                }
                $mod->where(['id' => $r['id']])->save($update);
            } else {
                $mod->where(['id' => $r['id']])->save(['status' => 1]);
            }
        }
    }


    private function shop_notice($order, $is_staff = false)
    {
        //验证增加商家余额
        $order['order_type'] = 'shop';
        if ($is_staff) {
            if ($order['card_id']) {
                $use_result = D('Card_new_coupon')->user_coupon($order['card_id'], $order['order_id'], 'shop', $order['mer_id'], $order['uid']);
                if ($use_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

            if ($order['coupon_id']) {
                $use_result = D('System_coupon')->user_coupon($order['coupon_id'], $order['order_id'], 'shop', $order['mer_id'], $order['uid']);
                if ($use_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

            // 使用积分
            if ($order['score_used_count'] > 0) {
                $use_result = D('User')->user_score($order['uid'], $order['score_used_count'], L_('购买 X1 扣除X1', array('X1' => $order['name'], 'X2' => C('config.score_name'))));
                if ($use_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

            if (floatval($order['merchant_balance']) > 0) {
                $use_result = D('Card_new')->use_money($order['uid'], $order['mer_id'], $order['merchant_balance'], L_('购买 X1 扣除会员卡余额', $order['real_orderid']));
                if ($use_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

            if (floatval($order['card_give_money']) > 0) {
                $use_result = D('Card_new')->use_give_money($order['uid'], $order['mer_id'], $order['card_give_money'], L_('购买 X1 扣除会员卡赠送余额', $order['real_orderid']));
                if ($use_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

            $goods = D('Shop_order_detail')->field(true)->where(array('order_id' => $order['order_id']))->select();
            $goods_obj = D("Shop_goods");
            foreach ($goods as $gr) {
                $goods_obj->update_stock($gr); //修改库存
            }

            $order['system_status'] = 2;
            D('System_order')->add_order('shop', $order, $order['order_id']);

            $order['order_type'] = 'shop_offline';
        }
        //D('Merchant_money_list')->add_money($this->store['mer_id'],L_('用户在 ') . $this->store['name'] . ' 中消费' . floatval($order['price']) . C('config.Currency_txt').L_('记入收入'),$order);
        if ($order['union_mer_id']) {
            $pre_desc = L_('商家会员卡联盟----');
        }
        $order['desc'] = $pre_desc . L_('用户在 X1 中消费X2记入收入', array('X1' => $this->store['name'], 'X2' => floatval($order['price']) . C('config.Currency_txt')));
        D('SystemBill')->bill_method($order['is_own'], $order);

        //商家推广分佣
        $now_user = M('User')->where(array('uid' => $order['uid']))->find();
        D('Merchant_spread')->add_spread_list($order, $now_user, 'shop', $now_user['nickname'] . L_('用户购买X1商品获得佣金', ['X1' => $this->config['shop_alias_name']]));

        //积分
        if (C('config.open_extra_price') == 1) {
            $score = D('Percent_rate')->get_extra_money($order);
            if ($score > 0) {
                D('User')->add_score($order['uid'], floor($score), L_('在 X1 中消费X2 获得X3', array('X1' => $this->store['name'], 'X2' => floatval($order['price']) . C('config.Currency_txt'), 'X3' => C('config.extra_price_alias_name'))));
            }
        } else {
            if ($this->config['add_score_by_system_commission'] == 0 && $this->config['add_score_by_percent'] == 0 && ($this->config['open_score_discount'] == 0 || $order['score_discount_type'] != 2) && ($this->config['score_get_times'] == 0 || !isset($this->config['score_get_times']))) {
                if ($order['is_own'] && $this->config['user_own_pay_get_score'] != 1) {
                    $order['payment_money'] = 0;
                }
                if ($order['order_from'] == 1) {
                    $score_get = D('Percent_rate')->shop_get_score($order);
                } elseif ($this->config['shop_goods_score_edit'] == 1) {
                    $score_get = D('Percent_rate')->shop_get_score($order);
                    $score_get = D('Percent_rate')->get_user_add_score_times($score_get, $order['uid']);
                } else {
                    $this->config['score_get'] = D('Percent_rate')->get_user_add_score_times($this->config['score_get'], $order['uid']);
                    $score_get  = ($order['payment_money'] + $order['balance_pay']) * $this->config['score_get'];
                }

                if ($score_get > 0) {
                    $param['order_type'] = 'shop';
                    $param['order_id'] = $order['order_id'];
                    D('User')->add_score($order['uid'], $score_get, L_('在 X1 中消费X2 获得X3', array('X1' => $this->store['name'], 'X2' => floatval($order['price']) . C('config.Currency_txt'), 'X3' => $this->config['score_name'])), 0, $param);
                    D('Scroll_msg')->add_msg('shop', $now_user['uid'], L_('用户X1于X2 中消费获得X3', array('X1' => str_replace_name($now_user['nickname']), 'X2' => date('Y-m-d H:i', $_SERVER['REQUEST_TIME']) . $this->store['name'], 'X3' => $this->config['score_name'])));
                }
            }
            D('Userinfo')->add_score($order['uid'], $order['mer_id'], $order['price'], L_('在 X1 中消费X2 获得X3', array('X1' => $this->store['name'], 'X2' => floatval($order['price']) . C('config.Currency_txt'), 'X3' => $this->config['score_name'])));
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
            $sms_data['content'] = L_('您在 X1店中下的订单(订单号：X2),已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！', array('X1' => $this->store['name'], 'X2' => $order['real_orderid']));
            Sms::sendSms($sms_data);
        }
        if ($this->config['sms_shop_finish_order'] == 2 || $this->config['sms_shop_finish_order'] == 3) {
            $sms_data['uid'] = 0;
            $sms_data['mobile'] = $this->store['phone'];
            $sms_data['sendto'] = 'merchant';
            $sms_data['content'] = L_('顾客购买的X1的订单(订单号：X2),已经完成了消费！', array('X1' => $order['name'], 'X2' => $order['real_orderid']));
            Sms::sendSms($sms_data);
        }

        //微信小程序订阅消息提醒 
        $wxapp_message_send = false;
        if ($now_user['wxapp_openid'] && $order['order_from'] != 1) {
            $param['page'] = '/pages/shop_new/orderDetail/orderDetail?order_id=' . $order['order_id'];
            $param['wxapp_openid'] = $now_user['wxapp_openid'];
            $param['type'] = 'order_status_change';
            // 获得商品信息
            $goods_name = D('Shop_order_detail')->where(['order_id' => $order['order_id']])->order('id ASC')->find()['name'];
            $goodsCount = D('Shop_order_detail')->field('sum(num) as total')->where(['order_id' => $order['order_id']])->find()['total'];
            $order['goods_desc'] = $goodsCount > 1 ? L_('X1等X2个商品', ['X1' => $goods_name, 'X2' => $goodsCount]) : $goods_name;

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
