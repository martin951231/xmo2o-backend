<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/4/17.
 * Time: 17:01.
 */

/**
 * Class plan_store_time_store 店铺营业时间->店铺级计划任务
 */
class plan_store_time_store extends plan_base{
    //店铺级计划任务 更新店铺营业时间
    public function runTask($param = array()){

        if(empty($param['store_id'])){
            return true;
        }
        $store_id = $param['store_id'];
		D('Merchant_store_open_time')->checkBusinessTime($store_id);

        return true;
    }
}