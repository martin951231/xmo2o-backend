<?php
/**
 * 智能充电桩
 * Created by PhpStorm.
 * User: jaty
 * DateTime: 2021/3/30 9:24
 */
class HouseParkAction extends BaseAction{

    /**
     * Notes: 店铺中转页面
     * User: jaty
     * DateTime: 2021/3/30 9:24
     */
    public function store() {
		$store_id = intval($_GET['store_id']);
		$store_list = M('Park_coupons_shop')->alias('pcs')->join('left join '.C('DB_PREFIX').'house_village as `hv` on `hv`.`village_id`=`pcs`.`village_id`')->field('pcs.*,hv.village_name')->where(['pcs.bind_m_id' => $store_id, 'pcs.status' => '0'])->order('`m_id` ASC')->select();
		$this->assign('store_list', $store_list);
		$this->display();
    }
}