<?php


namespace app\community\model\service;

use app\community\model\db\HouseVillagePaymentStandardBind;

class HouseVillagePaymentService
{
    private $db_house_village_payment_standard_bind = '';

     private $cycle_type_china = array(
                    'Y'=>'年',
                    'M'=>'月',
                    'D'=>'日',
                    );
     private $cycle_type = array('Y'=>'year', 'M'=>'month', 'D'=>'day');

    /**
     * HouseVillagePaymentService constructor.
     * 初始化数据
     */
    public function __construct()
    {
        $this->db_house_village_payment_standard_bind = new HouseVillagePaymentStandardBind();
    }

    /**
     * 用户自定义缴费项
     * @author lijie
     * @date_time 2020/11/10
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getUserPaymentLists($where,$field=true)
    {
        $data = $this->db_house_village_payment_standard_bind->paymentList($where,$field)->toArray();
        if($data){
            foreach ($data as $k => $v) {
                if ($v['cycle_sum'] - $v['paid_cycle'] <= 0) {
                    unset($data[$k]);
                    continue;
                }
                if(empty($v['cycle_type'])){
                    unset($data[$k]);
                    continue;
                }
                $end_time = strtotime(date('Y-m-d',$v['start_time']).'+'.$v['pay_cycle']*$v['paid_cycle'].' '.$this->cycle_type[$v['cycle_type']]);
                if ($end_time<time()) {
                    $num = $this->getTimeNum($end_time,time(),$v['cycle_type']);
                    $num = ceil($num/$v['pay_cycle']);
                    $num = min($num,$v['cycle_sum']);
                }else{
                    unset($data[$k]);
                    continue;
                }
                if($v['pay_type'] == 2){
                    $data[$k]['pay_type_html'] = '按金额*数量';
                    $data[$k]['price'] = number_format($v['metering_mode_val'] * $v['pay_money']*$num,2,'.','').'元';
                    $data[$k]['pay_money'] = number_format($v['metering_mode_val']*$v['pay_money']*$num,2,'.','');
                }else{
                    $data[$k]['pay_type_html'] = '固定模式';
                    $data[$k]['price'] = number_format($v['pay_money']*$num,2,'.','').'元';
                    $data[$k]['pay_money'] = number_format($v['pay_money']*$num,2,'.','');
                }
                $data[$k]['zhouqi_fee'] = $v['paid_cycle'].$v['cycle_type'].'/周期';
                $data[$k]['service_time'] = date('Y-m-d',$v['start_time']).'至'.date('Y-m-d',$v['end_time']).'('.$v['cycle_sum'].'周期)';
                $data[$k]['start_time'] = date('Y-m-d',$v['start_time']);
                $data[$k]['paid_cycle_html'] = $v['paid_cycle'].'个';
                $data[$k]['max_cycle_sum'] = $v['cycle_sum'] - $v['paid_cycle'];
                // 计算结束时间
                $v['end_time'] = strtotime(date('Y-m-d',$v['start_time']).'+'.$v['cycle_sum']*$v['pay_cycle'].$this->cycle_type[$v['cycle_type']]);
                $data[$k]['end_time'] = date('Y-m-d',$v['end_time']);
                $data[$k]['cycle_type'] = $this->cycle_type_china[$v['cycle_type']];
            }
        }
        $data = array_values($data);
        return $data;
    }

    public function CashierPaymentList($where,$field=true)
    {
        $data = $this->db_house_village_payment_standard_bind->paymentList($where,$field)->toArray();
        return $data;
    }

    /**
     * 获得相差时间 $date2>$date1
     * @author lijie
     * @date_time 2020/11/23
     * @param $date1
     * @param $date2
     * @param $type
     * @return false|float|int|mixed|string
     */
    public function getTimeNum($date1,$date2,$type){
        // var_dump(date('Y-m-d',$date1),date('Y-m-d',$date2));
        switch ($type) {
            case 'Y': //相差年份
                $date_1['y'] = date('Y',$date1);
                $date_2['y'] = date('Y',$date2);
                $num = $date_2['y']-$date_1['y'];
                if (strtotime(date('m-d',$date2))-strtotime(date('m-d',$date1))>=0) {
                    $num += 1;
                }
                # code...
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