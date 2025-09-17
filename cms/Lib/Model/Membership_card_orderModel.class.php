<?php

/**
 * 平台会员卡
 */
class Membership_card_orderModel extends Model
{

    /**
     * get_pay_order
     * @param $uid
     * @param $orderId
     * @author: 张涛
     * @date: 2020/12/22
     */
    public function get_pay_order($uid, $orderId)
    {
        $nowOrder = $this->where(['order_id' => $orderId, 'uid' => $uid])->find();
        if (empty($nowOrder)) {
            return ['error' => 1, 'msg' => L_('当前订单不存在！')];
        }
        if ($nowOrder['paid'] == 1) {
            return ['error' => 1, 'msg' => L_('您已经支付过此订单！')];
        }

        $orderInfo = array(
            'order_id' => $nowOrder['order_id'],
            'real_orderid' => $nowOrder['order_sn'],
            'order_type' => 'mcard',
            'order_name' => $nowOrder['order_name'].'购买',
            'order_num' => 1,
            'order_price' => floatval($nowOrder['price']),
            'add_time' => $nowOrder['create_time'],
            'paid' => $nowOrder['paid'],
            'order_total_money' => $nowOrder['price'],
        );
        return ['error' => 0, 'order_info' => $orderInfo];
    }

    /**
     * 支付前订单处理
     * @param $order_info
     * @param $now_coupon
     * @param $merchant_balance
     * @param $now_user
     * @param null $ecard_info
     * @return array
     * @author: 张涛
     * @date: 2020/12/22
     */
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
        $order_result = $this->wap_pay_save_order($order_info,$data_shop_order);
        if ($order_result['error_code']) {
            return $order_result;
        }
        return array('error_code' => false, 'pay_money' => $pay_money);
    }

    /**
     * 手机端支付前保存各种支付信息
     * @param $order_info
     * @return array
     * @author: 张涛
     * @date: 2020/12/22
     */
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

    /**
     * 如果无需调用在线支付，使用此方法即可。
     * @param $order_info
     * @return array
     * @author: 张涛
     * @date: 2020/12/22
     */
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

    /**
     * 支付成功处理
     * @param $order_param
     * @return array
     * @author: 张涛
     * @date: 2020/12/22
     */
    public function after_pay($order_param)
    {
        $tm = time();
        $order_id = $order_param['order_id']??$order_param['orderid'];
        if (strlen($order_id) < 12) {
            $orderStr = 'order_id';
        } else {
            $orderStr = 'orderid';
        }
        $where[$orderStr] = $order_param['order_id'];
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
                    $use_result = D('User')->user_money($now_order['uid'], $balance_pay, L_("开通平台会员卡，扣除余额，订单编号X2", array("X2" => $now_order['order_sn'])));
                    $fdumpData = [
                        'uid' => $now_order['uid'],
                        'balance_pay' => $balance_pay,
                        'desc' => L_("开通平台会员卡，扣除余额，订单编号X2", array("X2" => $now_order['order_sn'])),
                        'result' => $use_result
                    ];
                    fdump_sql($fdumpData, 'ID1016266');
                    if ($use_result['error_code']) {
                        return array('error' => 1, 'msg' => $use_result['msg']);
                    }
                }

                //发放平台会员卡
                $sendRs = (new Membership_cardModel())->sendCard($now_order['uid'], $now_order['order_id'], 'mcard');
                if (!$sendRs) {
                    return array('error' => 1, 'msg' => '平台会员卡发放失败');
                }
                //更新订单过期时间
                $this->where($where)->save(['expire_time' => $sendRs['expire_time']]);

                //更新用户过期时间
                D('User')->where(['uid' => $now_order['uid']])->save(['membership_card_time' => $sendRs['expire_time']]);

                $url = C('config.site_url') . D('House_village')->base_url . 'pages/membership/platMembership';
                return array('error' => 0, 'url' => $url);
            } else {
                return array('error' => 1, 'msg' => L_('修改订单状态失败，请联系系统管理员！'));
            }
        }
    }
}