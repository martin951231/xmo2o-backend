<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * Date: 2020/4/2
 * Time: 10:00
 */
class plan_market_order_over  extends plan_base{

    public function runTask(){
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/plan_market_order_over',true);
        $merchant_integral_mall_time = intval(C('config.merchant_integral_mall_time'));// 单位：天
        if ($merchant_integral_mall_time<=0) {
            return true;
        }
        $pull_time = time() + $merchant_integral_mall_time*24*3600;


        $where = array(
            'status' => 2
        );
        $where['_string'] = 'send_time>0 AND send_time <'.$pull_time;
        $databaseMarketOrder = D('Market_order');
        $result_list = $databaseMarketOrder->where($where)->select();
        fdump('-----------------'.__LINE__,'api/log/'.date('Ymd').'/plan_market_order_over',true);
        fdump($result_list,'api/log/'.date('Ymd').'/plan_market_order_over',true);
        fdump($databaseMarketOrder->getLastSql(),'api/log/'.date('Ymd').'/plan_market_order_over',true);
        fdump($databaseMarketOrder->getDbError(),'api/log/'.date('Ymd').'/plan_market_order_over',true);
        fdump('line:-----'.__LINE__,'api/log/'.date('Ymd').'/plan_market_order_over',true);
        if ($result_list) {
            foreach($result_list as $val) {
                fdump('line:-----'.__LINE__,'api/log/'.date('Ymd').'/plan_market_order_over',true);
                fdump($val['order_id'],'api/log/'.date('Ymd').'/plan_market_order_over',true);
                $where_order = array('order_id' => $val['order_id']);
                $status = $databaseMarketOrder->field(true)->where($where_order)->getField('status');
                fdump('line:-----'.__LINE__,'api/log/'.date('Ymd').'/plan_market_order_over',true);
                fdump($val,'api/log/'.date('Ymd').'/plan_market_order_over',true);
                fdump($status,'api/log/'.date('Ymd').'/plan_market_order_over',true);
                if (2==$status) {
                    $databaseMarketOrder->where($where_order)->save(array('status' => 3, 'last_time' => time(), 'pull_time' => time()));
                    if ($val['type']==1 && $val['integral_mall_goods_order_id']>0) {
                        $mall_order_id = $val['integral_mall_goods_order_id'];
                        $data = array('status' => 3);
                        $data['arrival_time'] = $_SERVER['REQUEST_TIME'];
                        $data['last_time'] = $_SERVER['REQUEST_TIME'];
                        $order_info = M('Merchant_integral_mall_goods_order')->where(array('order_id' => $mall_order_id))->find();
                        if (D('Merchant_integral_mall_goods_order')->where(array('order_id' => $mall_order_id))->save($data)) {

                            D('Merchant_integral_mall_log')->add_log(array('order_id' => $val['order_id'], 'status' => 3, 'from_uid' => $order_info['uid'], 'note' => '用户确认收货'));

                            // 用户确认收货-计算给予商家结算
                            D('Merchant_integral_mall_goods_order')->order_settlement($order_info);
                        }
                        $sellmerchant = M('Merchant')->where(array('mer_id' => $val['sell_mer_id']))->field('mer_id,phone')->find();
                        $buy_merchant_name = M('Merchant')->where(array('mer_id' => $val['mer_id']))->getField('name');
                        $sms_data = array('mer_id' => $sellmerchant['mer_id'], 'store_id' => 0, 'type' => 'market');
                        $sms_data['uid'] = 0;
                        $sms_data['mobile'] = $sellmerchant['phone'];
                        $sms_data['sendto'] = 'merchant';
                        $sms_data['content'] = L_('您发给X1的X2共X3对方已经收货，订单号：X4',array('X1'=>$buy_merchant_name,'X2'=>$val['name'],'X3'=>$val['num'] . $val['unit'],'X4'=>$val['order_id']));
                        Sms::sendSms($sms_data);
                    } else {

                        //对方增加余额
                        $total_express_fee = $val['total_express_fee'] > 0 ? floatval($val['total_express_fee']) : 0;
                        $money = floatval($val['money']);
                        $total_money = $money + $total_express_fee;
                        $data = array();
                        $data['order_type'] = 'market';
                        $data['desc'] = L_('批发市场的商品').'【'.$val['name'].'.】'.L_('卖出时增加商家余额');
                        $data['mer_id'] = $val['sell_mer_id'];
                        $data['money'] = $val['money'];
                        $data['order_id'] = $val['order_id'];
                        $data['total_money'] = $total_money;
                        D('SystemBill')->bill_method(0,$data);

                        $sellmerchant = M('Merchant')->where(array('mer_id' => $val['sell_mer_id']))->field('mer_id,phone')->find();
                        $buy_merchant_name = M('Merchant')->where(array('mer_id' => $val['mer_id']))->getField('name');
                        $sms_data = array('mer_id' => $sellmerchant['mer_id'], 'store_id' => 0, 'type' => 'market');
                        $sms_data['uid'] = 0;
                        $sms_data['mobile'] = $sellmerchant['phone'];
                        $sms_data['sendto'] = 'merchant';
                        $sms_data['content'] = L_('您发给X1的X2共X3对方已经收货，订单号：X4',array('X1'=>$buy_merchant_name,'X2'=>$val['name'],'X3'=>$val['num'] . $val['unit'],'X4'=>$val['order_id']));
                        Sms::sendSms($sms_data);
                    }
                }
            }
        }

        return true;

    }
}