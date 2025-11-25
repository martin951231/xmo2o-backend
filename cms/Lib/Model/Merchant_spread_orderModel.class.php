<?php

class Merchant_spread_orderModel extends Model
{
    public function get_pay_order($uid, $orderId)
    {
        $nowOrder = $this->where(['order_id' => $orderId, 'uid' => $uid])->find();
        if (empty($nowOrder)) {
            return ['error' => 1, 'msg' => L_('当前订单不存在！'), 'url' => C('config.site_url') . '/wap.php?c=My&a=my_spread'];
        }
        if ($nowOrder['paid'] == 1) {
            return ['error' => 1, 'msg' => L_('您已经支付过此订单！'), 'url' => C('config.site_url') . '/wap.php?c=My&a=my_spread'];
        }

        $orderInfo = array(
            'order_id' => $nowOrder['order_id'],
            'real_orderid' => $nowOrder['order_sn'],
            'order_type' => 'spread',
            'order_name' => $nowOrder['type'] == 1 ? '开通vip推广商' : '开通城市推广商',
            'order_num' => 1,
            'order_price' => floatval($nowOrder['price']),
            'add_time' => $nowOrder['create_time'],
            'paid' => $nowOrder['paid'],
            'order_total_money' => $nowOrder['price']
        );
        return ['error' => 0, 'order_info' => $orderInfo];
    }

    public function wap_befor_pay($order_info, $now_user)
    {
        $pay_money = $order_info['order_total_money'];
        //判断帐户余额
        if (!empty($now_user['now_money']) && $order_info['use_balance']) {
            $now_user['now_money'] = round($now_user['now_money'] * 100) / 100;
            if ($now_user['now_money'] >= $pay_money) {
                $data_shop_order['balance_pay'] = $pay_money;
                $order_info['balance_pay'] = $pay_money;
                $order_result = $this->wap_pay_save_order($order_info, $data_shop_order);
                if ($order_result['error_code']) {
                    return $order_result;
                }
                $order_info['pay_money'] = $pay_money;
                return $this->wap_after_pay_before($order_info);
            } else {
                $data_shop_order['balance_pay'] = $now_user['now_money'];
            }
            $pay_money -= $now_user['now_money'];
            $pay_money = round($pay_money * 100) / 100;
        }

        $pay_money < 0 && $pay_money = 0;
        $order_result = $this->wap_pay_save_order($order_info, $data_shop_order);
        if ($order_result['error_code']) {
            return $order_result;
        }
        return array('error_code' => false, 'pay_money' => $pay_money);
    }

    public function wap_pay_save_order($order_info, $data_shop_order)
    {
        $data_weidian_order['balance_pay'] = $data_shop_order['balance_pay'] > 0 ? floatval($data_shop_order['balance_pay']) : 0;
        $data_weidian_order['last_time'] = $_SERVER['REQUEST_TIME'];
        $condition_weidian_order['order_id'] = $order_info['order_id'];
        if ($this->where($condition_weidian_order)->data($data_weidian_order)->save()) {
            return array('error_code' => false, 'msg' => L_('保存订单成功！'));
        } else {
            return array('error_code' => true, 'msg' => L_('保存订单失败！请重试或联系管理员。'));
        }
    }

    public function wap_after_pay_before($order_info)
    {
        $order_param = array(
            'order_id' => $order_info['order_id'],
            'pay_type' => '',
            'third_id' => '',
        );
        $result_after_pay = $this->after_pay($order_param);
        if ($result_after_pay['error']) {
            return array('error_code' => true, 'msg' => $result_after_pay['msg']);
        } else {
            return array('error_code' => false, 'msg' => L_('支付成功'), 'url' => $result_after_pay['url']);
        }
    }

    public function after_pay($order_param)
    {
        $tm = time();
        if ($order_param['pay_type'] != '') {
            $where['orderid'] = $order_param['order_id'];
        } else {
            $where['order_id'] = $order_param['order_id'];
        }
        $now_order = $this->where($where)->find();
        if (empty($now_order)) {
            return array('error' => 1, 'msg' => L_('当前订单不存在'));
        } else if ($now_order['paid'] == 1) {
            return array('error' => 1, 'msg' => L_('该订单已付款！'), 'url' => '');
        } else {
            //得到当前用户信息，不将session作为调用值，因为可能会失效或错误。
            $now_user = D('User')->get_user($now_order['uid']);
            if (empty($now_user)) {
                return array('error' => 1, 'msg' => L_('没有查找到此订单归属的用户，请联系管理员！'));
            }
            //判断帐户余额
            $balance_pay = floatval($now_order['balance_pay']);
            if ($balance_pay > 0 && $now_user['now_money'] < $balance_pay) {
                return array('error' => 1, 'msg' => L_('您的帐户余额不够此次支付！'));
            }

            $updateData = array();
            if (isset($order_param['is_mobile'])) {
                $updateData['is_mobile_pay'] = $order_param['is_mobile'];
            }
            $updateData['pay_time'] = $_SERVER['REQUEST_TIME'];
            $updateData['payment_money'] = floatval($order_param['pay_money']);
            $updateData['pay_type'] = $order_param['pay_type'];
            $updateData['third_id'] = $order_param['third_id'];
            $updateData['paid'] = 1;
            if ($this->where($where)->save($updateData)) {
                if ($balance_pay > 0) {
                    $use_result = D('User')->user_money($now_order['uid'], $balance_pay, L_("升级推广商等级，扣除余额，订单编号X2", array("X2" => $now_order['order_sn'])));
                    $fdumpData = [
                        'uid' => $now_order['uid'],
                        'balance_pay' => $balance_pay,
                        'desc' => L_("升级推广商等级，扣除余额，订单编号X2", array("X2" => $now_order['order_sn'])),
                        'result' => $use_result
                    ];
                    fdump_sql($fdumpData, 'ID1016266');
                    if ($use_result['error_code']) {
                        return array('error' => 1, 'msg' => $use_result['msg']);
                    }
                }

                $udata = [];
                $now_order['type'] == 1 && $udata['is_vip_spread'] = 1;
                $now_order['type'] == 2 && $udata['is_city_spread'] = 1;
                //D('User')->where(['uid' => $now_order['uid']])->save($udata);

                $applyData = [
                    'order_id' => $now_order['order_id'],
                    'uid' => $now_order['uid'],
                    'type' => $now_order['type'],
                    'price' => $now_order['price'],
                    'form_data' => $now_order['form_data'],
                ];
                M('Merchant_spread_apply')->add($applyData);

                return ['error' => 0, 'url' => C('config.site_url') . '/wap.php?c=My&a=my_spread'];
            } else {
                return array('error' => 1, 'msg' => L_('修改订单状态失败，请联系系统管理员！'));
            }
        }
    }

    public function check_refund($order_id)
    {
        $refund_rate = 1;
        $now_order = $this->where(['order_id' => $order_id])->find();
        $refundData = ['is_refund' => 1, 'refund_time' => time()];
        $rs = ['error_no' => 0, 'error_msg' => ''];
        try {
            if ($now_order['payment_money'] > 0 && $now_order['pay_type']) {
                //第三方支付
                $payment_money_order = M('Payment_money_order')->where(array('order_id' => $order_id, 'order_type' => 'spread', 'paid_money' => ['gt', 0]))->find();
                if ($now_order['payment_money'] == $payment_money_order['payment_money']) {
                    //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
                    $nowOrder['payment_money'] = $payment_money_order['paid_money'];
                    if ($payment_money_order['pay_rate'] > 0) {
                        $now_order['payment_money'] = $now_order['payment_money'] * $payment_money_order['pay_rate'];
                    }
                }

                $is_wap = $now_order['pay_type'] == 'alipayh5' ? 1 : 0;
                $pay_method = D('Config')->get_pay_method(0, 0, $is_wap, 0, false, true);
                if (empty($pay_method[$now_order['pay_type']])) {
                    throw new  Exception('退款失败，支付方式未开启');
                }

                if ($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'xiaoma' || $payment_money_order['pay_method'] == 'nong')) {
                    if ($payment_money_order['pay_method'] == 'wft') {
                        $pay_method = 'wftpay';
                    }
                    if ($payment_money_order['pay_method'] == 'paylinx') {
                        $pay_method = 'paylinx';
                    }
                    if ($payment_money_order['pay_method'] == 'weikebaba') {
                        $pay_method = 'weikebaba';
                    }
                    if ($payment_money_order['pay_method'] == 'wepayez') {
                        $pay_method = 'wepayez';
                    }
                    if ($payment_money_order['pay_method'] == 'xiaoma') {
                        $pay_method = 'xiaoma';
                    }
                    if ($payment_money_order['pay_method'] == 'nong') {
                        $pay_method = 'nong';
                    }
                    import('@.ORG.LowFeePay');
                    $lowfeepay = new LowFeePay($pay_method);
                    $now_order['order_type'] = $payment_money_order['order_type'];
                    $now_order['orderNo'] = $payment_money_order['orderNo'];
                    if ($now_order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba') {
                        $mer_juhe = M('Weikebaba')->where(array('mer_id' => $now_order['mer_id']))->find();
                        if (!empty($mer_juhe)) {
                            $lowfeepay->user_id = $mer_juhe['user_id'];
                            $lowfeepay->key = $mer_juhe['key'];
                            $now_order['is_own'] = 1;
                            $now_order['orderNo'] = $now_order['orderNo'];
                        }

                    }
                    $go_refund_param = $lowfeepay->refund($now_order);
                } else {
                    $pay_class_name = ucfirst($now_order['pay_type']);
                    $import_result = import('@.ORG.pay.' . $pay_class_name);
                    if (empty($import_result)) {
                        throw new  Exception('退款失败，支付方式不存在');
                    }

                    $now_order['order_type'] = $payment_money_order['order_type'];
                    $now_order['order_id'] = $now_order['orderid'];
                    if ($now_order['order_from'] == 4 && $now_order['is_own'] != 2) {
                        $pay_method['weixin']['config'] = array(
                            'pay_weixin_appid' => C('config.pay_wxapp_appid'),
                            'pay_weixin_key' => C('config.pay_wxapp_key'),
                            'pay_weixin_mchid' => C('config.pay_wxapp_mchid'),
                            'pay_weixin_appsecret' => C('config.pay_wxapp_appsecret')
                        );
                        if ($now_order['is_own'] == 1) {
                            $wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
                            $pay_method['weixin']['config'] = array(
                                'pay_weixin_appid' => $wxapp_own_pay['appid'],
                                'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
                                'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
                                'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
                                'is_own' => 1
                            );
                        }
                    }

                    if ($pay_class_name == 'Alipay' && $now_order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid') != '' && C('config.new_pay_alipay_app_private_key') != '') {
                        $pay_class_name = 'AlipayApp';
                        $pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
                        $pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
                        $pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key');
                    }
                    import('@.ORG.pay.' . $pay_class_name);
                    $pay_class = new $pay_class_name($now_order, getFormatNumber($now_order['payment_money'] * $refund_rate), $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $my_user, 1);
                    $go_refund_param = $pay_class->refund();
                }

                $refundData['refund_detail'] = serialize($go_refund_param);
                if (empty($go_refund_param['error'])) {
                    //退款成功
                } else {
                    fdump_sql([$go_refund_param, $now_order], 'spread_order_refund_fail');
                    throw new  Exception($go_refund_param['msg'] ?? '退款失败');
                }
            }

            //退还平台余额支付金额
            if ($now_order['balance_pay'] > 0) {
                D('User')->add_money($now_order['uid'], $now_order['balance_pay'], L_('驳回推广商申请，退还余额'));
            }
            $this->where(['order_id' => $order_id])->save($refundData);
        } catch (\Exception $e) {
            fdump_sql([$e->getMessage(), $e->getFile(), $e->getLine()], 'spread_order_refund_fail');
            $rs = ['error_no' => 1, 'error_msg' => $e->getMessage()];
        }
        return $rs;
    }
}