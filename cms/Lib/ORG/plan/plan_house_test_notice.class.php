<?php
/*社区欠费自动发送微信通知提醒 一天一次

 * */
class plan_house_test_notice extends plan_base{
	public function runTask(){

        $return_msg = $this->get_house_unpaid_list();
        $list = $return_msg['list'];
        if ($list) {

        }

        // 处理欠费预存款问题//以及未交费订单问题
        $special_arr = $list['special_arr'];
        if ($special_arr) {
            foreach ($special_arr as $val) {
               // D('House_deposit_order')->special_info($val);

                D('Village_unpaid_pay')->unpaid_pay($val);//处理物业费以及自定义缴费===>郭先森

            }
        }

        return true;
    }

    /*得到未缴列表*/
    public function get_house_unpaid_list(){
        // 水电燃气停车费
        $condition = '`v`.`village_id` = `b`.`village_id`  AND (`b`.`water_price`>0 OR `b`.`electric_price`>0 OR `b`.`gas_price`>0 OR `b`.`park_price`>0) AND b.type in (0,3) AND `v`.`send_date` ="'.intval(date('d')).'"';
        $condition_table  = array(C('DB_PREFIX').'house_village'=>'v',C('DB_PREFIX').'house_village_user_bind'=>'b');
        $field = '`b`.`pigcms_id`';

        $list = D('')->field($field)->table($condition_table)->where($condition)->select();
        $ids = array();
        foreach ($list as $key => $value) {
            $ids[] = $value['pigcms_id'];
        }
        $this->keepThread();

        // 物业费
        $condition = '( ((property_endtime <> "" OR property_endtime <> null) AND property_endtime <="'.strtotime(date('Y-m-d')).'")) AND parent_id=0 AND type in (0,3)';
        $list = M('House_village_user_bind')->field('pigcms_id,property_endtime')->where($condition)->select();
        foreach ($list as $key => $value) {
            if (date('d',$value['property_endtime']) == date('d')) {
                $ids[] = $value['pigcms_id'];
            }
        }
        $this->keepThread();

        // 自定义缴费
        $condition = '`psb`.`pigcms_id` = `b`.`pigcms_id`  AND `psb`.`standard_id` = `ps`.`standard_id` AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND `psb`.`start_time` <"'.time().'"';
        $condition_table  = array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',C('DB_PREFIX').'house_village_user_bind'=>'b',C('DB_PREFIX').'house_village_payment_standard'=>'ps');
        $field = '`psb`.*,`ps`.`cycle_type`,`ps`.`pay_cycle`,`b`.`pigcms_id`';

        $list1 = D('')->field($field)->table($condition_table)->where($condition)->select();
        $list1 = $list1 ? $list1 : array();
        $this->keepThread();

        // 车位自定义缴费
        $condition = '`bp`.`user_id` = `b`.`pigcms_id`  AND `bp`.`position_id` = `psb`.`position_id`  AND `psb`.`standard_id` = `ps`.`standard_id` AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND `psb`.`start_time` <"'.time().'"';
        $condition_table  = array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',C('DB_PREFIX').'house_village_user_bind'=>'b',C('DB_PREFIX').'house_village_payment_standard'=>'ps',C('DB_PREFIX').'house_village_bind_position'=>'bp');
        $field = '`psb`.*,`ps`.`cycle_type`,`ps`.`pay_cycle`,`b`.`pigcms_id`';

        $list2 = D('')->field($field)->table($condition_table)->where($condition)->select();
        $list2 = $list2 ? $list2 : array();

        $this->keepThread();

        $custom_list = array_merge($list1,$list2);

        $custom_ids = array();
        if ($custom_list) {
            foreach ($custom_list as $key => $value) {
                switch ($value['cycle_type']) {
                    case 'Y': // 年
                        // 计算到期时间 = 开始时间 + 已缴时间（收费周期*已缴周期）
                        $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*365;
                        break;
                    case 'M': //月
                        $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*30;
                        break;
                    case 'D': // 日
                        $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400;
                        break;
                }
                if ($end_time<time() && date("d",$end_time) == date("d")) {
                    $custom_ids[] = $value['pigcms_id'];;
                }
            }
        }

        $this->keepThread();

        //欠费业主id集
        $ids = array_merge($ids,$custom_ids);
        $condition = 'f.floor_id=b.floor_id AND b.pigcms_id in('.implode(',',$ids).') AND b.village_id=v.village_id AND b.uid=u.uid';

        $condition_table  = array(C('DB_PREFIX').'house_village_floor'=>'f',C('DB_PREFIX').'house_village_user_bind'=>'b',C('DB_PREFIX').'user'=>'u',C('DB_PREFIX').'house_village'=>'v');
        $field = 'b.village_id,b.name,b.property_endtime,b.pigcms_id,b.water_price,b.electric_price,b.gas_price,b.park_price,f.floor_name,f.floor_layer,f.property_fee,v.property_name,v.send_user_type,v.send_date,v.property_price,u.openid,u.uid';
        $order = ' `b`.`pigcms_id` DESC';

        $list = D('')->field($field)->table($condition_table)->where($condition)->order($order)->select();

        $this->keepThread();

        // 整合物业欠费/自定义缴费欠费数组
        $special_arr = array();
        if($list){
            foreach ($list as &$v){
                //物业欠费
                $property_price = 0;
                if ($v['property_endtime'] && $v['property_endtime'] < strtotime(date("Y-m-d"))) {
                    $num = $this->getTimeNum($v['property_endtime'],strtotime(date("Y-m-d")),'M');
                    if (($v['property_fee'] != '0.00') && isset($v['property_fee'])) {
                        $property_price = $v['property_fee'] * $v['housesize'] * $num;
                    } else {
                        $property_price = $v['property_price'] * $v['housesize'] * $num;
                    }
                    if ($property_price > 0) {
                        $property_msg = array(
                            'money' => $property_price,
                            'property_month_num' => $num,
                            'uid' => $v['uid'],
                            'bind_id' => $v['pigcms_id'],
                            'village_id' => $v['village_id'],
                            'pay_type' => 'property',
                            'payment_id' => '-2'
                        );
                        $special_arr[] = $property_msg;
                    }
                }

                //自定义项欠费
                $payment_list = D('')->table(array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',
                    C('DB_PREFIX').'house_village_payment_standard'=>'ps',
                    C('DB_PREFIX').'house_village_payment'=>'p'))
                    ->where("psb.pigcms_id= '".$v['pigcms_id']."' AND p.payment_id = psb.payment_id AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND ps.standard_id = psb.standard_id AND `psb`.`start_time` <".time())->select();
                $payment_list = $payment_list ? $payment_list : array();

                $this->keepThread();

                // 车位缴费
                $position_payment_list = D('House_village_bind_position')->get_user_position_payment_list(array('pigcms_id'=>$v['pigcms_id']));
                $payment_list = array_merge($payment_list, $position_payment_list);
                $cunstom_money = 0;
                if ($payment_list) {
                    foreach ($payment_list as $key => $value) {
                        switch ($value['cycle_type']) {
                            case 'Y':
                                $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*365;
                                break;
                            case 'M':
                                $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*30;
                                break;
                            case 'D':
                                $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400;
                                break;
                        }
                        if ($end_time<time()) {
                            $num = ceil($num/$value['pay_cycle']);
                            $num = $this->getTimeNum($end_time,time(),$value['cycle_type']);
                            $num = min($num,$value['cycle_sum']);
                            if ($value['pay_type']==1) {
                                $cunstom_money += $value['pay_money'] * $num;
                            }else{
                                $cunstom_money += $value['pay_money'] * $num * $value['metering_mode_val'];
                            }
                        }
                        if ($cunstom_money > 0) {
                            $cunstom_msg = array(
                                'money' => $cunstom_money,
                                'payment_bind_id' => $value['bind_id'],
                                'payment_paid_cycle' => $num,
                                'uid' => $v['uid'],
                                'bind_id' => $v['pigcms_id'],
                                'village_id' => $v['village_id'],
                                'pay_type' => 'custom_payment',
                                'payment_id' => $value['payment_id']
                            );
                            $special_arr[] = $cunstom_msg;
                        }
                    }
                }

                $this->keepThread();

                $v['total'] =  $v['water_price']+$v['electric_price']+$v['gas_price']+$v['park_price'] + $property_price + $cunstom_money;
            }
        }
        $return_msg = array(
            'list' => $list,
            'special_arr' => $special_arr
        );

        return $return_msg;
    }

    //获得相差时间 $date2>$date1
    function getTimeNum($date1,$date2,$type){
        switch ($type) {
            case 'Y': //相差年份
                $date_1['y'] = date('Y',$date1);
                $date_2['y'] = date('Y',$date2);
                $num = $date_2['y']-$date_1['y'];
                if (strtotime(date('m-d',$date2))-strtotime(date('m-d',$date1))>=0) {
                    $num += 1;
                }
                break;
            case 'M': //相差月份
                list($date_1['y'],$date_1['m'],$date_1['d']) = explode("-",date('Y-m-d',$date1));
                list($date_2['y'],$date_2['m'],$date_2['d']) = explode("-",date('Y-m-d',$date2));
                $num = ($date_2['y']-$date_1['y'])*12 +$date_2['m']-$date_1['m'];
                if ($date_2['d']- $date_1['d'] >= 0) { //多相差1天则加一个月
                    $num += 1;
                }
                break;
            case 'D': //相差天数
                $num = abs( ceil(($date2-$date1)/86400));
                break;
        }
        return $num;
    }

}
?>