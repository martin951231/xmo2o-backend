<?php
/*
 * 选择城市
 *
 */
class ChangecityAction extends BaseAction{
	
	//根据热门展现城市区县的版本
	public function new_index(){
		$return = [];
		$database_area = D('Area');
		
		//当前城市 优先查区
		if($_POST['now_area'] && $_POST['now_area'] != $_POST['now_city'] && $now_area = $database_area->where(['area_id' => $_POST['now_area'], 'is_open' => '1'])->find()){
			$return['now_city'] = [
				'area_id' => $now_area['area_id'],
				'city_id' => $now_area['area_pid'],
				'area_name' => $now_area['area_name'],
				'area_url' => $now_area['area_url'],

			];
		}else{
			$city_id = $_POST['now_city'] ? : $this->config['now_city'];
			$now_city = $database_area->where(['area_id' => $city_id, 'is_open' => '1'])->find();
			$return['now_city'] = [
				'area_id' => '0',
				'city_id' => $now_city['area_id'],
				'area_name' => $now_city['area_name'],
				'area_url' => $now_city['area_url'],
				'is_city'  => true,
			];
		}
		
		//获得城市列表
		$database_field = '`area_id`,`area_pid`,`area_name`,`area_type`,`area_url`,`first_pinyin`,`is_hot`';
		$condition_all_city['is_show_change'] = 1;
		$condition_all_city['is_open'] = 1;
		if($_POST['from_type'] == 'not_area'){
			$condition_all_city['area_type'] = 2;
		}
		$all_city_old = $database_area->field($database_field)->where($condition_all_city)->order('`first_pinyin` ASC,`area_sort` DESC,`area_id` ASC')->select();
		
		foreach($all_city_old as $key => $value){
			//首拼转成大写
			if(!empty($value['first_pinyin'])){
				$first_pinyin = strtoupper($value['first_pinyin']);
				
				if($value['area_type'] == '2'){
					$tmp_data = [
						'area_id' => '0',
						'city_id' => $value['area_id'],
						'area_name' => $value['area_name'],
						'area_url' => $value['area_url'],
					];
				}else{
					$tmp_data = [
						'area_id' => $value['area_id'],
						'city_id' => $value['area_pid'],
						'area_name' => $value['area_name'],
						'area_url' => $value['area_url'],
					];
				}
				// $tmp_data['first_pinyin'] = $first_pinyin;
				
				$return['all_city'][$first_pinyin][] = $tmp_data;
			}
		}
		
		if($_POST['app_type'] != 'wxapp' && $_POST['app_type'] != 'packapp'){
			$return['all_city_app'] = [];
			foreach($return['all_city'] as $key=>$value){
				$return['all_city_app'][] = [
					'short_key' => $key,
					'city_list' => $value
				];
			}
			unset($return['all_city']);
		}
		
		
		$this->returnCodeOk($return);
	}
	
    public function index(){
        $database_area = D('Area');
        $city_id    =   I('area_name');
        $version    =   I('app_version');
        if($city_id){
            $now_city    =   $this->cityMatching($city_id);
        }else{
            $now_city    =   $this->nowCity($this->config['now_city']);
        }

        if ($this->config['location_mode']) {
            // 获取商场（单独客户需求）
            $area_city = $database_area->get_tranding_by_areaPid($now_city['top_city']['area_id'],true);
            $now_city['child'] = $area_city ? $area_city : array();
            $arr['select_name'] = L_('商场');
        }else{
            // 获取区县
			if(!$this->config['open_multilingual']){
				$area_city = $database_area->get_arealist_by_areaPid($now_city['top_city']['area_id'],true);
				$now_city['child'] = $area_city ? $area_city : array();
				
				foreach($area_city as $area_k => $area_v){
					unset($area_city[$area_k]['area_ip_desc']);
					unset($area_city[$area_k]['url']);
					unset($area_city[$area_k]['shop_main_page_center_type']);
					unset($area_city[$area_k]['shop_main_page_show_ad']);
					unset($area_city[$area_k]['circle_category_id']);
					unset($area_city[$area_k]['area_lat']);
					unset($area_city[$area_k]['area_lng']);
					unset($area_city[$area_k]['area_url']);
					unset($area_city[$area_k]['is_deliver_server']);
					unset($area_city[$area_k]['service_phone']);
					unset($area_city[$area_k]['deliver_model']);
					unset($area_city[$area_k]['zip_code']);
					unset($area_city[$area_k]['timezone']);
					unset($area_city[$area_k]['first_pinyin']);
					unset($area_city[$area_k]['is_hot']);
					unset($area_city[$area_k]['is_deliver']);
					unset($area_city[$area_k]['area_type']);
					unset($area_city[$area_k]['area_sort']);
					unset($area_city[$area_k]['is_open']);
				}
				
			}else{
				$now_city['child'] = array();
			}
            $arr['select_name'] = L_('区县');
        }

        $arr['now_city'][]  =   isset($now_city)?$now_city:array();

        //得到所有的省份
        $all_province = S('all_province');
        if(empty($all_province)){
            $condition_all_area['area_type'] = 1;
            $condition_all_area['is_open'] = 1;
            $database_field = '`area_id`,`area_name`';
            $all_province = $database_area->field($database_field)->where($condition_all_area)->order('`area_sort` ASC,`area_id` ASC')->select();
            S('all_province',$all_province);
        }
        $arr['all_province']  =   isset($all_province)?$all_province:array();
        //得到推荐的城市
        // $hot_city = S('hot_city');
        if(empty($hot_city)){
            $database_field = '`area_name`,`area_url`,`area_id`,`area_pid`';
            $condition_tuijian_city['area_type'] = 2;
            $condition_tuijian_city['is_open'] = 1;
            $condition_tuijian_city['is_hot'] = 1;
            $hot_city = $database_area->field($database_field)->where($condition_tuijian_city)->order('`area_id` ASC')->select();
            S('hot_city',$hot_city);
        }else{
            $database_field = '`area_id`';
            foreach($hot_city as $k=>$v){
                $tmpCity = $database_area->field($database_field)->where(array('area_name' => $v['area_name'],'area_type'=>2))->select();
                $hot_city[$k]['area_id'] =  $tmpCity[0]['area_id'];
            }
        }
        $arr['hot_city']  =   isset($hot_city)?$hot_city:array();
        //得到所有城市并以城市首拼排序
        // $all_city = S('all_city');
        if(empty($all_city)){
            $database_field = '`area_id`,`area_name`,`area_url`,`first_pinyin`,`is_hot`';
            $condition_all_city['area_type'] = 2;
            $condition_all_city['is_open'] = 1;
            $all_city_old = $database_area->field($database_field)->where($condition_all_city)->order('`first_pinyin` ASC,`area_id` ASC')->select();
            foreach($all_city_old as $key=>$value){
                if ($this->config['location_mode']) {
                    // 获取商场（单独客户需求）
                    $area_city = $database_area->get_tranding_by_areaPid($value['area_id'],true);
                    if ($area_city) {
                        foreach ($area_city as $key => $value) {
                            $area_city[$key]['area_id'] = $value['market_id'];
                        }
                    }
                    $value['child'] = $area_city ? $area_city : array();
                }else {
                    // 获取区县
					if(!$this->config['open_multilingual']){
						$area_city = $database_area->get_arealist_by_areaPid($value['area_id'],true);
						
						foreach($area_city as $area_k => $area_v){
                            unset($area_city[$area_k]['area_ip_desc']);
                            unset($area_city[$area_k]['url']);
                            unset($area_city[$area_k]['shop_main_page_center_type']);
                            unset($area_city[$area_k]['shop_main_page_show_ad']);
                            unset($area_city[$area_k]['circle_category_id']);
                            unset($area_city[$area_k]['area_lat']);
                            unset($area_city[$area_k]['area_lng']);
                            unset($area_city[$area_k]['area_url']);
                            unset($area_city[$area_k]['is_deliver_server']);
                            unset($area_city[$area_k]['service_phone']);
                            unset($area_city[$area_k]['deliver_model']);
                            unset($area_city[$area_k]['zip_code']);
                            unset($area_city[$area_k]['timezone']);
                            unset($area_city[$area_k]['first_pinyin']);
                            unset($area_city[$area_k]['is_hot']);
                            unset($area_city[$area_k]['is_deliver']);
                            unset($area_city[$area_k]['area_type']);
                            unset($area_city[$area_k]['area_sort']);
                            unset($area_city[$area_k]['is_open']);
						}
						
						$value['child'] = $area_city ? $area_city : array();
					}else{
						$value['child'] = array();
					}
                }

                //首拼转成大写
                if(!empty($value['first_pinyin'])){
                    $first_pinyin = strtoupper($value['first_pinyin']);
                    $all_city[$first_pinyin][] = $value;
                }
            }
            S('all_city',$all_city);
        }

        foreach($all_city as $k=>$v){
            $tmp[]    =   $k;
        }
        $arr['aCity']  =    isset($tmp)?$tmp:array();
        $arr['all_city']  =   isset($all_city)?$all_city:array();
        $this->returnCode(0,$arr);
    } 
    
    //根据城市ID得到符合的区县或商城
    public function get_area(){
        $area_id = intval($_POST['area_id']);
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
        $this->returnCode(0,$area_city);
    }

    //城市搜索
    public function search_area(){
        $search = $_POST['search'];
        if (!$search) {
            $area_city = array();
            echo json_encode($area_city);
        }

        $database_area = D('Area');
        $condition = array();
        $all_city = D('Area')->get_all_city(1,2);
        if($this->config['location_mode']){
            // 获取商城（单独客户需求）
            $area_city = $database_area->get_tranding_by_areaPid($area_id,true);
        }else{
            $condition['_complex'] = array(
                    'area_name' => array('like','%'.$search.'%'),
                    'to_pinyin(area_name)' => array('like','%'.$search.'%'),
                    '_logic' => 'or',
                );
            $condition['area_type'] = array('in',array(2,3));
            $condition['is_open'] = 1;
            $area_list = $database_area->get_limit_list_page($condition);
            $area_city = array();
            foreach ($area_list['list'] as $key => $value) {
                $_temp = array(
                    'now_area_id' => $value['area_id'],
                    'now_area_name' => $value['area_name']
                );
                if ($value['area_type'] == 3) { // 区县
                    foreach ($all_city as $_city) {
                        if ($_city['area_id'] == $value['area_pid']) {
                            $_temp['area_id'] = $_city['area_id'];
                            $_temp['area_name'] = $_city['area_name'];
                            break;
                        }
                    }
                }else{
                    $_temp['area_id'] = $value['area_id'];
                    $_temp['area_name'] = $value['area_name'];
                }

                if ($_temp['area_id']) {
                    $area_city[] = $_temp;
                }
            }
        }
    
        if(!$area_city){
            $area_city = array();
        }
        $this->returnCode(0,$area_city);
    }
}
