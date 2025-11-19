<?php
class Internal_cardModel extends Model
{
    public function send_wx_msg($card_id, $money, $msg='一卡通余额变动'){
        $card = M('internal_membership_card')->where(array('card_id' => $card_id))->find();
        if (!$card) {
            return false;
        }
        if($money!=0){
            $now_user = D('User')->get_user($card['uid']);
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $res = $model->sendTempMsg('OPENTM401833445', array(
                'href' => C('config.site_url') . '/packapp/plat/pages/my/internalCard/myCardLog?card_id=' . $card_id.'&store_id='.$card['store_id'],
                'wecha_id' => $now_user['openid'],
                'first' => L_('一卡通余额变动消息提醒'),
                'keyword1' => date('Y-m-d H:i'),
                'keyword2' => '一卡通'.$card_id.$msg,
                'keyword3' => $money,
                'keyword4' => $card['balance_money'],
                'remark' => $card['card_name'].': '.$card_id),
                0
            );
            return $res;
        }
        return false;

    }

    public function recharge($card_id = 0, $money = 0, $order_id,$is_refund = false){
        $card = M('internal_membership_card')->where(array('card_id' => $card_id))->find();
        if (!$card){
            return array('error_code' => 1, 'msg' => '一卡通号错误');
        }

        if ($money > 0){
            $before_balance = $card['balance_money'];
            $card['balance_money'] = round(($card['balance_money'] + $money) * 100 / 100, 2);
            $res = M('internal_membership_card')->where(array('card_id' => $card_id))->save(array('balance_money' => $card['balance_money']));

            if (!$res){
                return array('error_code' => 1, 'msg' => '保存失败');
            }
            $condition_card_log['card_id'] = $card_id;
            $condition_card_log['operation_type'] = 1;
            $condition_card_log['before_balance'] = $before_balance;
            $condition_card_log['after_balance'] = $card['balance_money'];
            $condition_card_log['money'] = $money;
            if ($is_refund) {
                $condition_card_log['describe'] = '用户一卡通(' . $card_id . ')在线充值退款' . abs($money) . '元,余额' . $card['balance_money'] . '元。订单号：' . $order_id;
            } else {
                $condition_card_log['describe'] = '用户给一卡通(' . $card_id . ')在线充值' . $money . '元,余额' . $card['balance_money'] . '元';
            }
            $condition_card_log['order_id'] = $order_id;
            $condition_card_log['create_time'] = time();
            $res = M('internal_membership_card_log')->add($condition_card_log);
            if (!$res){
                return array('error_code' => 1, 'msg' => '保存失败');
            }
            $this->send_wx_msg($card_id, '+'.$money, '充值余额');
            return array('error_code' => 0, 'msg' => '保存成功');
        }else{
            return array('error_code' => 1, 'msg' => '金额错误');
        }
    }

    public function get_card_pay($internal_card_pay){
        $money = 0;
        $card_list = [];
        if ($internal_card_pay){
            foreach ($internal_card_pay as $card => $pay_money){
                $money += $pay_money;
                array_push($card_list, $card);
            }
        }
        return ['money' => getFormatNumber($money), 'card_list' => implode(',', $card_list)];

    }

    public function use_money($card_id, $money,$business,$now_order) {
        fdump_api(["进入一卡通金额扣除方法",$card_id, $money,$business,$now_order],'pay/store_staff_arrival_pay',true);
        $cacheKey = $card_id.$business.($now_order['real_orderid'] ?? '').($now_order['orderid'] ?? '');
        if(S($cacheKey)){
            throw_exception('请勿重复支付!');
        }
        S($cacheKey, 1, 5);
        $businessType = '';
        $pay_order_id = '';
        $businessTypeText = '';
        switch ($business){
            case 'shop':
                $businessType = 'shop_offline';
                $businessTypeText = '线下零售';
                $pay_order_id = $now_order['real_orderid'];
                break;
            case 'store':
                $businessType = 'store';
                $businessTypeText = '店内收银';
                $pay_order_id = $now_order['orderid'];
                break;
            default:
                break;
        }
        $card =M('internal_membership_card')->where(array('card_id' => $card_id))->find();

        fdump_api(['一卡通信息'=>$card],'pay/store_staff_arrival_pay',true);

        $internal_card_log = M('internal_membership_card_log');
        if (!$card) {
            return array('error' => 1, 'msg' => '一卡通不存在');
        }
        $before_money = $card['balance_money'];
        if ($before_money < $money){
            return array('error' => 1, 'msg' => '一卡通余额不足');
        }
        $after_money = bcsub($before_money, $money, 2);

        $save_data['balance_money'] = $after_money;
        $save_data['last_time'] = time();

        if(M('internal_membership_card')->where(array('card_id' => $card_id))->save($save_data) !== false){

            fdump_api(['扣款成功',$card_id,$save_data],'pay/store_staff_arrival_pay',true);

            $condition_card_log['card_id'] = $card['card_id'];
            $condition_card_log['operation_type'] = 2; //交易
            $condition_card_log['before_balance'] = $before_money;
            $condition_card_log['after_balance'] = $after_money;
            $condition_card_log['money'] = $money;
            $condition_card_log['business_type'] = $businessType;
            if(isset($now_order['from_plat']) && $now_order['from_plat'] == 2){
                $condition_card_log['business_type'] = 'cash';
            }
            $condition_card_log['order_id'] = $pay_order_id;
            $condition_card_log['uid'] = $card['uid'] ?: 0;
            $condition_card_log['describe'] = $businessTypeText.', 扣除余额'. $money .'元, 订单编号'.$pay_order_id;
            $condition_card_log['mer_id'] = $now_order['mer_id'];
            $condition_card_log['store_id'] = $now_order['store_id'];
            if($now_order['staff_id']){
                $storeStaff = D('Merchant_store_staff')
                    ->where(['id'=>$now_order['staff_id']])
                    ->field('username')
                    ->find();
                $condition_card_log['staff_name'] = $storeStaff['username'];
            }
           
            $condition_card_log['create_time'] = time();
            $res = $internal_card_log->add($condition_card_log);

            fdump_api(['添加交易记录成功'=>$condition_card_log, $res],'pay/store_staff_arrival_pay',true);

            $this->send_wx_msg($card_id, '-'.$money);
            return array('error' => 0, 'msg' => '成功');

        }
        return array('error' => 1, 'msg' => '一卡通扣款失败');
    }
}