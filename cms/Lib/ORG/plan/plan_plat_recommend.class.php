<?php
/*平台推荐店铺*/
class plan_plat_recommend extends plan_base{
	public function runTask(){
		$now_time=time();
		
		//查找店铺列表
		$store_list = M('Merchant_store')->field('`store_id`,`plat_recommend_type`,`plat_recommend_info`,`status`')->select();
		
		// 店鋪在數據庫直接刪除 刪除推薦表
		$recommend_store_ids = array_column(M('Plat_recommend')->field('`store_id`')->select(), 'store_id') ;
		$store_ids = array_column($store_list,'store_id');
		$delete_store = array_diff($recommend_store_ids, $store_ids);
		if ($delete_store) {
			M('Plat_recommend')->where(array('store_id'=>array('in',$delete_store)))->delete();
		}
		
		$sub_plan_data = array();
		
		foreach($store_list as $value){
			$this->keepThread();
			$store_id = $value['store_id'];
			
			if($value['status'] != 1){
				M('Plat_recommend')->where(array('store_id'=>$store_id))->delete();
				continue;
			}
			
			//如果没有设置过，自动找到
			if(empty($value['plat_recommend_type'])){
				$save_data = array();
				//判断有没有开通外卖
                $shop = M('Merchant_store_shop')->where(array('store_id'=>$store_id))->find();
				if($shop && !$shop['is_close']){
					fdump($store_id,'store_id',1);
					$save_data['plat_recommend_type'] = 'shop';
					if (!$foodshop = M('Merchant_store_foodshop')->where(array('store_id'=>$store_id))->find()) {
						$save_data['plat_recommend_info'] = '';
					}
					M('Merchant_store')->where(array('store_id'=>$store_id))->data($save_data)->save();
				}else if(M('')->field('`g`.`group_id`,`g`.`name` AS `group_name`')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gs'))->where("`g`.`group_id`=`gs`.`group_id` AND `gs`.`store_id`='$store_id' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time'")->order('`g`.`sort` DESC,`g`.`group_id` DESC')->select()){	//判断有没有团购
					$save_data['plat_recommend_type'] = 'store';
					$save_data['plat_recommend_info'] = 'group_0';
					M('Merchant_store')->where(array('store_id'=>$store_id))->data($save_data)->save();
				}else{
					$save_data['plat_recommend_type'] = 'store';
					$save_data['plat_recommend_info'] = 'cash';
					M('Merchant_store')->where(array('store_id'=>$store_id))->data($save_data)->save();
				}
			}
			
			$arr= array();
			$arr['param'] = serialize(array('store_id'=>$store_id));
			$arr['plan_time'] = -100;
			$arr['space_time'] = 0;
			$arr['add_time'] = $now_time;
			$arr['file'] = 'sub_plat_recommend';
			$arr['time_type'] = 1;
			$arr['unique_id'] = 'plat_recommend_'.$store_id;
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
			M('process_sub_plan')->data($arr)->add();
		}
		return true;
	}
}
?>