<?php
/**
 * Created by PigCMS.
 * User: htm.
 * Date: 2019/12/21.
 * Time: 10:43.
 */

/**
 * Class plan_mer_revenue_statistics 商家营业统计->主计划任务
 */
class plan_mer_revenue_statistics extends plan_base{
    //主计划任务 需要跑入的日期
    public function runTask(){
        // 昨天0点时间戳
        $yesterday = strtotime('yesterday');
        // 业务
        $business_type_arr = ['shop','mall','deliver','market','dining'];
        // $business_type_arr = ['market'];
        foreach ($business_type_arr as $business_type) {
            //查找最后一条数据时间
            $last_datetime_data = M('Revenue_statistics')->where(['business_type'=>$business_type])->order('statistics_date desc')->getField('statistics_date');

            // 更新前30天的数据
            $last_datetime = $yesterday-86400*30;

            switch ($business_type) {
                case 'mall'://商城统计
                    // 没有统计数据
                    if (!$last_datetime_data) {
                        //查找最早一条已支付的订单数据时间
                        $last_datetime = M('Shop_order')->where(['order_from'=>1,'pay_time'=>['gt',0]])->order('pay_time ASC')->getField('pay_time');
                        $last_datetime =  $last_datetime > 0 ? strtotime(date('Y-m-d',$last_datetime)) : date('Y-m-d');
                    }
                    break;
                case 'shop': // 外卖统计
                    // 没有统计数据
                    if (!$last_datetime_data) {
                        //查找最早一条已支付的订单数据时间
                        $last_datetime = M('Shop_order')->where(['pay_time'=>['gt',0]])->order('pay_time ASC')->getField('pay_time');
                        $last_datetime =  $last_datetime > 0 ? strtotime(date('Y-m-d',$last_datetime)) : date('Y-m-d');
                    }
                    break;
                case 'deliver': // 配送统计
                    // 没有统计数据
                    if (!$last_datetime_data) {
                        //查找最早一条已支付的订单数据时间
                        $last_datetime = M('Deliver_supply')->order('create_time ASC')->getField('create_time');
                        $last_datetime =  $last_datetime > 0 ? strtotime(date('Y-m-d',$last_datetime)) : date('Y-m-d');
                    }
                    break;
                case 'market': // 营销统计
                    // 没有统计数据
                    if (!$last_datetime_data) {
                        //查找最早一条已支付的订单数据时间
                        $last_datetime = M('Shop_order')->where(['pay_time'=>['gt',0]])->order('pay_time ASC')->getField('pay_time');
                        $last_datetime =  $last_datetime > 0 ? strtotime(date('Y-m-d',$last_datetime)) : date('Y-m-d');
                    }
                    break;
                case 'dining': // 营新版餐饮统计
                    // 没有统计数据
                    if (!$last_datetime_data) {
                        //查找最早一条已支付的订单数据时间
                        $last_datetime = M('Dining_order')->where(['pay_time'=>['gt',0]])->order('pay_time ASC')->getField('pay_time');
                        $last_datetime =  strtotime(date('Y-m-d',$last_datetime));
                    }
                    break;
            }

            $last_datetime = max($last_datetime, $yesterday-86400*365);// 只跑一年内的数据 防止老数据太多跑不起来
            
            // 插入子计划任务表
            $this->insert_sub($yesterday,$last_datetime,$business_type);
        }
        return true;
    }

    private function insert_sub($yesterday,$last_datetime,$business_type){
        if ($last_datetime) {
            // 插入子计划任务表
            $now_time = time();
            while ( $last_datetime <= $yesterday) {
                $this->keepThread();
                $arr= array();
                $arr['param'] = serialize(array('date'=>$last_datetime,'type'=>$business_type));
                $arr['plan_time'] = 1;
                $arr['space_time'] = 0;
                $arr['add_time'] = $now_time;
                $arr['file'] = 'sub_mer_revenue_statistics_date';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_mer_revenue_statistics_date'.$business_type.$last_datetime;
				$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                M('process_sub_plan')->data($arr)->add();
                // 加一天
                $last_datetime += 86400;
            }
        }
    }
}