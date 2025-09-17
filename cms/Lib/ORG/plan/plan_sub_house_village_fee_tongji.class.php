<?php


class plan_sub_house_village_fee_tongji extends plan_base
{
    public function runTask()
    {
        $where['type'] = 0;
        $where['status'] = array('in','1,3');
        $where['is_del'] = 0;
        $vacancy_list = M('House_village_user_vacancy')
            ->where($where)
            ->select();
        $year = date('Y');
        $month = date('m');
        $start_time = mktime(0,0,0,date('m'),1,date('Y'));
        $end_time = mktime(23,59,59,date('m'),date('t'),date('Y'));
        foreach ($vacancy_list as $key=>$val){
            $house_village_user = M('House_village_user_bind')->where(['vacancy_id'=>$val['pigcms_id'],'type'=>0,'status'=>1])->field('pigcms_id')->find();
            if(!$house_village_user['pigcms_id'])
                continue;
            $data['usernum'] = $val['usernum'];
            $data['name'] = $val['name'];
            $data['housesize'] = $val['housesize'];
            $data['village_id'] = $val['village_id'];
            $data['ydate'] = $year;
            $data['mdate'] = $month;
            $data['update_time'] = time();
            $con['village_id'] = $val['village_id'];
            $con['bind_id'] = $house_village_user['pigcms_id'];
            $con['paid'] = 1;
            $con['pay_time'] = array('egt',$start_time);
            $water_fee = M('House_village_pay_order')
                ->where($con)
                ->where(['order_type'=>'water'])
                ->sum('money');//水费
            $gas_fee = M('House_village_pay_order')
                ->where($con)
                ->where(['order_type'=>'gas'])
                ->sum('money');//燃气费
            $electric_fee = M('House_village_pay_order')
                ->where($con)
                ->where(['order_type'=>'electric'])
                ->sum('money');//电费
            $where_con['order_type'] =array('in','custom,custom_payment');
            $custom_fee = M('House_village_pay_order')
                ->where($con)
                ->where($where_con)
                ->sum('money');//自定义缴费

            $property_fee = M('House_village_pay_order')
                ->where($con)
                ->where(['order_type'=>'property'])
                ->sum('money');//物业费
            $data['water_fee'] = $water_fee?$water_fee:0;
            $data['ele_fee'] = $electric_fee?$electric_fee:0;
            $data['gas_fee'] = $gas_fee?$gas_fee:0;
            $data['custom_fee'] = $custom_fee?$custom_fee:0;
            $data['property_fee'] = $property_fee?$property_fee:0;
            $property_endtime = M('House_village_user_bind')
                ->where(['type'=>0,'vacancy_id'=>$val['pigcms_id']])
                ->field('property_endtime')
                ->find();
            $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$val['pigcms_id'],'is_del'=>1])->field('standard_id')->find();
            if($property_endtime['property_endtime'] > time()){
                if(!$standard_id['standard_id']){
                    $data['property_carry'] = 0;
                    $data['property_remain'] = 0;
                }else{
                    $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
                    $dateMonth = $this->dateMonths(date('Y-m',strtotime('next month')),date('Y-m-d H:i:s',$property_endtime['property_endtime']));
                    $data['property_remain'] = $this->property_price($dateMonth,unserialize($date['date_month']),$data['housesize']);//物业费剩余
                    $now_property_fee = $this->get_now_property_fee(unserialize($date['date_month']),time())*$data['housesize'];
                    if(M('House_village_pay_order')->where($con)->where(['order_type'=>'property','is_owe'=>1])->find()){
                        $data['property_carry'] = $property_fee - $data['property_remain'];
                    } else{
                        $data['property_carry'] = $now_property_fee; //物业费结转
                    }
                }
            }else{
                if(M('House_village_pay_order')->where($con)->where(['order_type'=>'property','is_owe'=>1])->find()){
                    $data['property_carry'] = $property_fee?$property_fee:0;
                    $data['property_remain'] = 0;
                }else{
                    $data['property_carry'] = 0;
                    $data['property_remain'] = 0;
                }
            }
            if(!$standard_id['standard_id']){
                $data['all_fee'] = 0;
                $data['total_fee'] = 0;
                $data['property_unit_price'] = 0;
            }else{
                $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
                $data['all_fee'] = $this->get_now_property_fee(unserialize($date['date_month']),time())*$data['housesize'];
                $data['total_fee'] = $property_fee?$property_fee:0;
                $data['property_unit_price'] = $this->get_now_property_fee(unserialize($date['date_month']),time());
            }
            $park_fee = M('House_village_pay_order')
                ->where($con)
                ->where(['order_type'=>'park'])
                ->sum('money');//停车费
            $data['party_fee'] = $park_fee?$park_fee:0;
            $park_config = M('House_village_park_config')->where(array('village_id'=>$val['village_id']))->find();
            if($park_config['is_park_month_type'] == 1 && $park_config['park_versions'] == 2){
                $car_lists = M('House_village_parking_car')->where(['car_user_phone'=>$val['phone']])->select();
                $data['party_remain'] = 0;
                $data['party_carry'] = 0;
                foreach ($car_lists as $k=>$v){
                    if($v['end_time'] < time()){
                        $data['party_remain'] += 0;
                        $data['party_carry'] += M('House_village_pay_order')
                            ->where($con)
                            ->where(['car_id'=>$v['car_id']])
                            ->where(['order_type'=>'park'])
                            ->sum('money');
                    }else{
                        $where_park['month_end_time'] = array('egt',time());
                        $where_park['month_start_time'] = array('elt',time());
                        $res = M('House_village_pay_order')
                            ->where($con)
                            ->where(['order_type'=>'park','car_id'=>$v['car_id']])
                            ->where($where_park)
                            ->find();
                        if(empty($res))
                            continue;
                        $data['party_carry'] += $res['property_month_num']+$res['presented_property_month_num']?$res['money']/($res['property_month_num']+$res['presented_property_month_num']):0;
                        $year1  = date("Y",time());   // 时间1的年份
                        $month1 = date("m",time());   // 时间1的月份
                        $year2  = date("Y",$res['month_end_time']);   // 时间2的年份
                        $month2 = date("m",$res['month_end_time']);   // 时间2的月份
                        $num = ($year2 * 12 + $month2) - ($year1 * 12 + $month1);
                        $data['party_remain'] += ($res['property_month_num']+$res['presented_property_month_num'])*$num?$res['money']/($res['property_month_num']+$res['presented_property_month_num'])*$num:0;
                    }
                }
            }
            $res = M('House_village_fee_tongji')->where(['village_id'=>$val['village_id'],'usernum'=>$val['usernum'],'ydate'=>$year,'mdate'=>$month])->field('id')->find();
            if($res){
                M('House_village_fee_tongji')->where(['id'=>$res['id']])->save($data);
            }else{
                M('House_village_fee_tongji')->add($data);
            }
        }
        echo "SUCCESS";
    }

    /**
     * 计算出两个日期之间的月份
     * @author Eric
     * @param  [type] $start_date [开始日期，如2014-03]
     * @param  [type] $end_date   [结束日期，如2015-12]
     * @param  string $explode    [年份和月份之间分隔符，此例为 - ]
     * @param  boolean $addOne    [算取完之后最后是否加一月，用于算取时间戳用]
     * @return [type]             [返回是两个月份之间所有月份字符串]
     */
    public function dateMonths($start_date,$end_date,$explode='-',$addOne=false){
        //判断两个时间是不是需要调换顺序
        $start_int = strtotime($start_date);
        $end_int = strtotime($end_date);
        if($start_int > $end_int){
            $tmp = $start_date;
            $start_date = $end_date;
            $end_date = $tmp;
        }


        //结束时间月份+1，如果是13则为新年的一月份
        $start_arr = explode($explode,$start_date);
        $start_year = intval($start_arr[0]);
        $start_month = intval($start_arr[1]);


        $end_arr = explode($explode,$end_date);
        $end_year = intval($end_arr[0]);
        $end_month = intval($end_arr[1]);


        $data = array();
        $data[] = strtotime($start_date);


        $tmp_month = $start_month;
        $tmp_year = $start_year;


        //如果起止不相等，一直循环
        while (!(($tmp_month == $end_month) && ($tmp_year == $end_year))) {
            $tmp_month ++;
            //超过十二月份，到新年的一月份
            if($tmp_month > 12){
                $tmp_month = 1;
                $tmp_year++;
            }
            $data[] = strtotime($tmp_year.$explode.str_pad($tmp_month,2,'0',STR_PAD_LEFT));
        }


        if($addOne == true){
            $tmp_month ++;
            //超过十二月份，到新年的一月份
            if($tmp_month > 12){
                $tmp_month = 1;
                $tmp_year++;
            }
            $data[] = $tmp_year.$explode.str_pad($tmp_month,2,'0',STR_PAD_LEFT);
        }


        return $data;
    }

    /**
     * 计算物业费
     * @author lijie
     * @date_time 2020/07/20 15:48
     * @param $dateMonths
     * @param $chargingStandard
     * @param $housesize
     * @return float|int
     */
    public function property_price($dateMonths,$chargingStandard,$housesize)
    {
        $property_price = 0;
        foreach ($dateMonths as $value){
            foreach ($chargingStandard as $v){
                if($value >= strtotime($v['date_month']) && $v['date_month'] != '' && $v['price'] != ''){
                    $price = $v['price'];
                }
            }
            if(isset($price)){
                $property_price += $price*$housesize;
            }
            unset($price);
        }
        return $property_price;
    }

    /**
     * 获取预缴物业费的费用
     * @author lijie
     * @date_time 2020/07/27
     * @param $chargingStandard
     * @param $housesize
     * @param $month
     * @param $num
     * @return float|int
     */
    public function get_all_fee($chargingStandard,$housesize,$month,$num)
    {
        foreach ($chargingStandard as $value){
            if(strtotime($value['date_month']) <= $month && $value['date_month'] != '' && $value['price'] != ''){
                $price = $value['price'];
            }
        }
        $property_price = $price*$housesize*$num;
        return $property_price;
    }

    /**
     * 获取当月物业费收费标准
     * @author lijie
     * @date_time 2020/07/30
     * @param $chargingStandard
     * @param $month
     * @return mixed
     */
    public function get_now_property_fee($chargingStandard,$month)
    {
        foreach ($chargingStandard as $value) {
            if (strtotime($value['date_month']) <= $month && $value['date_month'] != '' && $value['price'] != '') {
                $price = $value['price'];
            }
        }
        return isset($price) ? $price : 0;
    }
}