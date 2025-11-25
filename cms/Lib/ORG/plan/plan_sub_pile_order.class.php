<?php


class plan_sub_pile_order extends plan_base
{
    public function runTask(){
        // 处理现有订单出现问题的
        $where = [
            'a.status'=> 0,
            'a.village_id'=> 10,
            'a.type'=> 2,
            'a.pay_type'=> ['not in', [3,10]],
            'a.is_refund'=> 0,
        ];
        $time = time() - 3700;
        $where['a.pay_time'] = ['between', [1000,$time]];
        $where['_string'] = 'b.refund_id IS NULL';
        $msg = M('House_village_pile_pay_order')
            ->join('as a left join '.C('DB_PREFIX').'house_village_pile_refund_order as b ON b.order_id = a.id')
            ->where($where)
            ->field('a.*')
            ->select();
        if (!empty($msg)) {
            $db_house_village_pile_refund_order = D('House_village_pile_refund_order');
            $db_house_village_pile_pay_order = D('House_village_pile_pay_order');
            foreach ($msg as $item) {
                $refundParam = [
                    'order_id'=>$item['id'],
                    'status'=>2,
                    'refund_money'=>$item['use_money'],
                    'refund_original_money'=>$item['use_money'],
                    'refundType'=>2,
                    'refund_reason'=>'充电订单异常无法结束，走手动审核是否需要退款',
                    'add_time'=>time()
                ];
                $addId = $db_house_village_pile_refund_order->add($refundParam);
                if ($addId) {
                    $db_house_village_pile_pay_order->where(['id' => $item['id']])->save(['is_refund' => 2, 'status' => 1]);
                }
            }
        }
        return true;
    }

    public function repair_pile_order($order_no,$use_time=0,$use_ele=0) {
        $whereOrder = [
            'order_no' => $order_no
        ];
        $return_money = 0;
        $order_info = M('House_village_pile_pay_order')->where($whereOrder)->find();
        if (!$use_time) {
            $use_time = $order_info['continued_time'];
        }
        if (!$use_ele) {
            $use_ele = $order_info['use_ele'];
        }
        $equipmentInfo = M('House_village_pile_equipment')->where(['id' => $order_info['equipment_id']])->find();
        $whereAttribute = ['attribute_id' => 1,'is_del' => 1,'equipment_id' => $order_info['equipment_id']];
        $attribute_standard_id = M('House_village_pile_equipment_attribute')->where($whereAttribute)->getField('attribute_standard_id');
        $attributeInfo = M('House_village_pile_attribute_set')->where(['id' => $attribute_standard_id])->find();
        $price_standard_list = unserialize($attributeInfo['standard_value']);
        $work_type = $equipmentInfo['work_type'];
        if ($work_type==1) {
            $order_info['continued_time'] = $price_standard_list[1][2] * 3600 * ($order_info['use_money']/$price_standard_list[0][2]);
        } elseif ($work_type==2) {
            $order_info['use_ele'] = $price_standard_list[2][2] * ($order_info['use_money']/$price_standard_list[0][2]);
        }
        if($order_info['pay_type'] != 3 && $order_info['pay_type'] != 10){
            $config_info = M('House_village_pile_config')->where(['village_id' => $order_info['village_id']])->find();
            if(!empty($config_info)){
                $device_info = M('House_village_pile_equipment')->where(['equipment_num' => '0003132e6d76762c','is_del' => 1])->find();
                if(!empty($device_info)){
                    if($device_info['work_type'] == 1){         //计时版充电桩
                        if($config_info['low_consume_time']*60 >= $use_time){                //全额退款
                            $return_money = $order_info['use_money'];
                        }elseif($config_info['des_time_level'] >= 0){                                      //部分退款
                            $need_pay_time = $this->get_actual_use_time($config_info['des_time_level'],$use_time);
                            $return_money = sprintf("%.2f", $order_info['use_money'] - $order_info['use_money']/($order_info['continued_time']/$need_pay_time));
                        }
                    }else{                                                                      //计量版充电桩
                        if($config_info['low_consume_power'] >= $use_ele){                     //全额退款
                            $return_money = $order_info['use_money'];
                        }elseif($config_info['des_power_level'] >= 0){                                  //部分退款
                            $need_pay_ele = $this->get_actual_use_ele($config_info['des_power_level'],$use_ele);
                            $return_money = sprintf("%.2f", $order_info['use_money'] - $order_info['use_money']/($order_info['use_ele']/$need_pay_ele));
                        }
                    }
                    $data = [
                        'cmd'=>'refundMoneyCheck',
                        'orderNo'=>$order_no,
                        'refund_money' => $return_money,
                        'business_type'=>'house_village_pile_pay',
                        'type'=>'adopt',
                    ];
                    $data = json_encode($data,true);
                    $time = time();
                    $addParam = [
                        'command' => $data,
                        'type' => 2,
                        'status' => 1,
                        'addtime' => $time,
                    ];
                    M('House_village_pile_command')->add($addParam);
                    $arr = [];
                    $time = time();
                    $unique_id = "pile_notice".$time.rand(1,9999);
                    $arr['plan_time'] = -time();
                    $arr['space_time'] = 0;
                    $arr['add_time'] = time();
                    $arr['file'] = 'sub_pile_notice';
                    $arr['time_type'] = 1;
                    $arr['unique_id'] = $unique_id;
                    M('Process_sub_plan')->data($arr)->add();
                }
            }
        }
    }
}