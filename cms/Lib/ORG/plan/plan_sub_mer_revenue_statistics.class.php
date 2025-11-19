<?php
/**
 * Created by PigCMS.
 * User: htm.
 * Date: 2019/12/21.
 * Time: 10:43.
 */

/**
 * Class plan_sub_mer_revenue_statistics 商家营业统计->子计划任务 插入统计数据
 */
class plan_sub_mer_revenue_statistics extends plan_base{
    public function runTask($param = array()){
        if(empty($param['mer_id']) || empty($param['date']) || empty($param['type'])){
            return true;
        }

        $where = array();
        $where['mer_id'] = $param['mer_id'];
        $now_merchant = M('Merchant')->where($where)->find();
        
        switch ($param['type']) {
            case 'shop': // 外卖统计
                if($now_merchant){
                    // 插入统计数据
                    $return = D('Shop_order')->getRevenueStatistics($param['mer_id'],$param['date']);
                }
                break;
            case 'mall': // 商城统计
                if($now_merchant){
                    // 插入统计数据
                    $return = D('Shop_order')->getRevenueStatistics($param['mer_id'],$param['date'],0,1);
                }
                break;
            case 'deliver': // 配送统计
                // 插入统计数据
                $return = D('Deliver_supply')->getRevenueStatistics($param['mer_id'],$param['date']);
                break;
            case 'market': // 营销统计
                // 插入统计数据
                $return = D('Shop_order')->getRevenueStatisticsMarket($param['mer_id'],$param['date']);
                break;
            case 'dining': // 营销统计
                // 插入统计数据
                $return = D('Dining_order')->getRevenueStatistics($param['mer_id'],$param['date']);
                break;
        }

        $return['statistics_date'] = $param['date'];
        $return['mer_id'] = $where['mer_id'];
        $now_merchant['province_id'] && $return['province_id'] = $now_merchant['province_id'];
        $now_merchant['city_id'] && $return['city_id'] = $now_merchant['city_id'];
        $now_merchant['area_id'] && $return['area_id'] = $now_merchant['area_id'];
        $return['business_type'] = $param['type'];

        $where = array();
        $where['mer_id'] = $param['mer_id'];
        $where['statistics_date'] = $param['date'];
        $where['business_type'] = $param['type'];
        if (M('Revenue_statistics')->where($where)->find()) {
            $return['update_time'] = time();
            M('Revenue_statistics')->where($where)->data($return)->save();
        }else{
            $return['create_time'] = time();
            M('Revenue_statistics')->data($return)->add();
        }
        if ($param['date']=='1577635200') {
            fdump($return,'plan_sub_mer_revenue_statistics',1);
            fdump(M('Revenue_statistics')->_sql(),'plan_sub_mer_revenue_statistics',1);
        }
        return true;
    }
}