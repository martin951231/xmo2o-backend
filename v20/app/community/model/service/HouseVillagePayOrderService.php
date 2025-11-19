<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/11/9
 * Time: 14:14
 *======================================================
 */

namespace app\community\model\service;


use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillagePayOrder;

class HouseVillagePayOrderService
{
    /**
     * 获取用户缴费记录
     * @param $uid
     * @param $page
     * @return array
     */
    public function getLivingPaymentLog($uid,$village_id,$page,$limit = 10){
        $db_house_new_pay_order = new HouseNewPayOrder();
        if($uid<1 || $village_id<1){
            $data=array('list'=>[],'count'=>0,'next_page'=>false);
            return $data;
        }
        $where = [];
        $where[] = ['o.uid', '=', $uid];
        $where[] = ['o.village_id', '=', $village_id];
        $where[] = ['o.is_paid', '=', 1];
        $where[] = ['o.pay_time', '>', 1];
        // 获取缴费总条数
        $count = $db_house_new_pay_order->getCount($where);
        // 获取缴费记录
        $data = [];
        $temp = [];
        $house_new_pay_order_data = $db_house_new_pay_order->getList($where, 'o.order_name,o.pay_time,o.pay_money',$page,$limit,'o.pay_time DESC,o.order_id DESC');
        if($house_new_pay_order_data && !$house_new_pay_order_data->isEmpty()){
            foreach ($house_new_pay_order_data as &$value){
                // 拆解时间
                // 年月日
                $ymd = date('Y-m-d',$value['pay_time']);
                // 时分
                $hi = date('H:i:s',$value['pay_time']);
                $value['ymd'] = $ymd;
                $value['hi'] = $hi;
                $value['pay_time'] = date('Y-m-d H:i:s',$value['pay_time']);
                $temp[] = $value;
            }
        }
        $data['list'] = $temp;
        $data['count'] = $count;
        $data['next_page'] = true;
        if($limit > $house_new_pay_order_data->count()){
            $data['next_page'] = false;
        }
        return $data;
    }
}