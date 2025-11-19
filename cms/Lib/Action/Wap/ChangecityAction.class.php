<?php
/*
 * 选择城市
 *
 */
class ChangecityAction extends BaseAction{
    public function index(){
		$database_area = D('Area');
		
		// 当前所选城市
		$now_select_city = $this->config['now_select_city'];
		if($this->config['location_mode']){
			// 获取商圈（单独客户需求）
			$area_city = $database_area->get_tranding_by_areaPid($now_select_city['area_id'],true);
			$now_select_city['child'] = $area_city;
			$this->assign('type_name','商场');
		}else{ // 城市区县
			// 获取区县
			$area_city = $database_area->get_arealist_by_areaPid($now_select_city['area_id'],true);
			$now_select_city['child'] = $area_city;
			$this->assign('type_name','区县');

		}
		$this->assign('now_select_city',$now_select_city);

		if ($this->config['many_city']) { //多城市
			//得到推荐的城市
			// $hot_city = S('hot_city');
			// if(empty($hot_city)){
			// 	$database_field = '`area_name`,`area_url`,`area_id`,`area_pid`';
			// 	$condition_tuijian_city['area_type'] = 2;
			// 	$condition_tuijian_city['is_open'] = 1;
			// 	$condition_tuijian_city['is_hot'] = 1;
			// 	$hot_city = $database_area->field($database_field)->where($condition_tuijian_city)->order('`area_id` ASC')->select();
			// 	S('hot_city',$hot_city);
			// }
			// $this->assign('hot_city',$hot_city);

			//得到所有城市并以城市首拼排序
			$all_city = S('all_city');
			if(empty($all_city)){
				$database_field = '`area_id`,`area_name`,`area_url`,`first_pinyin`,`is_hot`';
				$condition_all_city['area_type'] = 2;
				$condition_all_city['is_open'] = 1;
				$all_city_old = $database_area->field($database_field)->where($condition_all_city)->order('`first_pinyin` ASC,`area_id` ASC')->select();
				foreach($all_city_old as $key=>$value){
					//首拼转成大写
					if(!empty($value['first_pinyin'])){
						$first_pinyin = strtoupper($value['first_pinyin']);
						$all_city[$first_pinyin][] = $value;
					}
				}
				S('all_city',$all_city);
			}
			$this->assign('all_city',$all_city);
		}
		
		if($_GET['hotel']==1){
			$this->assign('url',$this->config['site_url'].'/wap.php?c=Hotel&a=index');
		}

		if($_GET['village_group']==1){
			$this->assign('url',$this->config['site_url'].'/wap.php?c=Village_group&a=selectAddress');
		}

		if($_GET['appoint']==1){
			$this->assign('url',$this->config['site_url'].'/wap.php?c=Appoint&a=index');
		}
		
		if($_GET['index_new']==1){
			$this->assign('url',$this->config['site_url'].'/wap.php?c=Home&a=index_new');
		}
		
		$this->display();
	}
	//根据省份的ID得到符合的所有城市
	public function ajaxGetCitysByPro(){
		$area_id = $this->_get('pid','intval');
		$area_city = S('change_area_city_'.$area_id);
		if(empty($area_city) && !empty($area_id)){
			$database_area = D('Area');
			$condition_area_city['area_pid'] = $area_id;
			$condition_area_city['is_open'] = 1;
			$database_field = '`area_name` `cityname`,`area_url` `encity`';
			$area_city = $database_area->field($database_field)->where($condition_area_city)->order('`area_sort` ASC,`area_id` ASC')->select();
			if(is_array($area_city)){
				S('change_area_city_'.$area_id,$area_city);
			}
		}
		echo json_encode($area_city);
	}

	//根据城市ID得到符合的区县或商城
	public function ajax_get_area(){
		$area_id = intval($_GET['area_id']);
		$database_area = D('Area');
		if(!empty($area_id)){
			if($this->config['location_mode']){
				// 获取商城（单独客户需求）
				$area_city = $database_area->get_tranding_by_areaPid($area_id,true);
			}else{
				// 获取区县
				$area_city = $database_area->get_arealist_by_areaPid($area_id,true);
			}
		}
		if(!$area_city){
			$area_city = array();
		}
		echo json_encode($area_city);
	}
}