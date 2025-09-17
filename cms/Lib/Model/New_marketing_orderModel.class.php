<?php
class New_marketing_orderModel extends Model
{
    //开发中
    public function after_pay($order_param)
    {
        fdump($order_param, 'go_pay_v20_return', 1);
        $where = ['orderid' => $order_param['order_id']];
        $now_order = $this->field(true)->where($where)->find();
        if (empty($now_order)) {
            return array(
                'error' => 1,
                'msg' => L_('当前订单不存在')
            );
        } elseif ($now_order['paid'] != 0) {
            return array(
                'error' => 1,
                'msg' => L_('该订单已付款！'),
            );
        } else {
            fdump($now_order, 'go_pay_v20_return', 1);
            $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
            if (empty($now_merchant)) {
                return array(
                    'error' => 1,
                    'msg' => L_('没有查找到此订单归属的商家，请联系管理员！')
                );
            }
            $data_user_recharge_order = array();
            $data_user_recharge_order['pay_time'] = time();
            $data_user_recharge_order['total_price'] = floatval($order_param['pay_money']);
            $data_user_recharge_order['third_id'] = $order_param['third_id'];
            $data_user_recharge_order['paid'] = 1;
            $data_user_recharge_order['payment_money'] = floatval($order_param['pay_money']);
            if ($this->where($where)->save($data_user_recharge_order)) {
                fdump($data_user_recharge_order, 'go_pay_v20_return', 1);
                if ($now_order['pack_region_id']) {//购买套餐
                    $package_id = M('New_marketing_package_region')->where(['id' => $now_order['pack_region_id']])->getField('package_id');
                    $store_detail = json_decode(M('New_marketing_package')->where(['id' => $package_id])->getField('store_detail'), true);
                    foreach ($store_detail as $k => $v) {
                        $saveData = [
                            'mer_id' => $now_order['mer_id'],
                            'cat_id' => $v['type'][1],
                            'cat_fid' => $v['type'][0],
                            'years_num' => $now_order['pay_years'],
                            'store_count' => $now_order['buy_num'] * $v['num'],
                            'used_count' => 0
                        ];
                        $storeData = M('New_marketing_store')->where(['mer_id' => $saveData['mer_id'], 'cat_id' => $saveData['cat_id'], 'years_num' => $saveData['years_num']])->find();
                        if ($storeData) {
//                            $saveData['years_num'] += $storeData['years_num'];
                            $saveData['store_count'] += $storeData['store_count'];
                            $saveData['used_count'] += $storeData['used_count'];
                            M('New_marketing_store')->where(['id' => $storeData['id']])->data($saveData)->save();
                        } else {
                            M('New_marketing_store')->add($saveData);
                        }
                    }
                } else if ($now_order['class_region_id']) {//购买分类店铺
                    $class_id = M('New_marketing_class_region')->where(['id' => $now_order['class_region_id']])->getField('class_id');
                    $saveData = [
                        'mer_id' => $now_order['mer_id'],
                        'cat_id' => $class_id,
                        'cat_fid' => M('Merchant_category')->where(['cat_id' => $class_id])->getField('cat_fid'),
                        'years_num' => $now_order['pay_years'],
                        'store_count' => $now_order['store_num'],
                        'used_count' => 0
                    ];
                    $storeData = M('New_marketing_store')->where(['mer_id' => $saveData['mer_id'], 'cat_id' => $saveData['cat_id'], 'years_num' => $saveData['years_num']])->find();
                    if ($storeData) {
//                        $saveData['years_num'] += $storeData['years_num'];
                        $saveData['store_count'] += $storeData['store_count'];
                        $saveData['used_count'] += $storeData['used_count'];
                        M('New_marketing_store')->where(['id' => $storeData['id']])->data($saveData)->save();
                    } else {
                        M('New_marketing_store')->add($saveData);
                    }
                }elseif($now_order['store_id']){// 店铺续费
                    $storeWhere = ['store_id' => $now_order['store_id']];
                    $store = M('Merchant_store')->where($storeWhere)->find();
                    $desc =  '店铺'.$store['name'].'续费减少金额';
                    if($store){
                        // 增加到期时间
                        $addTime = $now_order['pay_years']*86400*365;
                        if($store['end_time'] > time()){
                            M('Merchant_store')->where($storeWhere)->setInc('end_time', $addTime);
                        }else{
                            $saveData = [
                                'status' => 1,
                                'end_time' => $addTime + time(),
                            ];
                            M('Merchant_store')->where($storeWhere)->data($saveData)->save();
                        }
                    }
                }
                fdump('ok', 'go_pay_v20_return', 1);
                M('New_marketing_order_type')->add([//记录订单类型
                    'order_type' => 0,
                    'order_id' => $now_order['order_id']
                ]);
//                if($now_order['order_type'] == 0){// 支付完成后业务抽成
                    invoke_v20_service('\app\new_marketing\model\service\MarketingOrderService/sendPersonSpreadMoney',['orderId' => $now_order['order_id']]);
//                }
                return array(
                    'error' => 0,
                    'msg' => L_('支付成功！'),
                );
            } else {
                return array(
                    'error' => 1,
                    'msg' => L_('修改订单状态失败，请联系系统管理员！')
                );
            }
        }
    }
}