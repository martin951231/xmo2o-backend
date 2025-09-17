<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/12/19.
 * Time: 15:40.
 */

class plan_shop_goods_stock_check_info extends plan_base{

    public function runTask($param = array()){

        if(empty($param['id'])){
            return true;
        }
        $id = $param['id'];
		D('Shop_goods')->check_shop_goods_stock($id);

        return true;
    }
}