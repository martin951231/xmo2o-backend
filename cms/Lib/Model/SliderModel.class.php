<?php
class SliderModel extends Model{
	/*通过导航分类的KEY获取到导航列表*/
	public function get_slider_by_key($cat_key,$limit=20,$need_formart=false){
        $today = date('Y-m-d');
        $time = date('H:i:s');
        $week = date('w');
		$database_slider_category  = D('Slider_category');
		$condition_slider_category['cat_key'] = $cat_key;
		$now_slider_category = $database_slider_category->field('`cat_id`')->where($condition_slider_category)->find();
		if($now_slider_category){
			$condition_slider['cat_id'] = $now_slider_category['cat_id'];
			$condition_slider['status'] = '1';
			$view_multi_city_cat = ['plat_index_more','wap_slider','wap_shop_slider','app_index_top_btn','wap_classify_slider'];
			if(C('config.many_city') && (C('customization.open_view_multi_city')!=1&&!in_array($cat_key,$view_multi_city_cat))){
				$condition_slider['city_id'] = array('in', [0, C('config.now_city')]);
			}
            $condition_slider['_string'] = sprintf("(effect_date_type = 1 OR (start_date <= '%s' AND end_date >= '%s' AND FIND_IN_SET(%d,week))) AND (effect_time_type = 1 OR (start_time <= '%s' AND end_time >= '%s'))", $today, $today, $week, $time, $time);
			
            if(C('customization.open_view_multi_city')==1&&C('config.now_city')&&in_array($cat_key,$view_multi_city_cat)){
                $slidr_list = M(_view('Slider'))->field(true)->where($condition_slider)->order('`sort` DESC,`id` ASC')->limit(100)->select();
                //获取当前城市下面区县
                $chirldren_arr = D('Area')->field('area_id')->where('is_open = 1 and area_pid = '.C('config.now_city'))->select();
                $chirldren_ids = [];
                foreach ($chirldren_arr as $val){
                    $chirldren_ids[] = $val['area_id'];
                }
                $chirldren_ids[] = C('config.now_city');
                $city_arr = $normal_arr = [];
                //区域管理员只查看属于本区域的信息
                foreach($slidr_list as $k=>$v){
                    $city_ids = $v['city_ids']?explode(',',$v['city_ids']):[];
                    if($v['city_id']!=0&&!array_intersect($city_ids,$chirldren_ids)){
                        unset($slidr_list[$k]);
                    }else{
                        if($v['city_id']!=0){
                            $city_arr[] = $v;
                        }else{
                            $normal_arr[] = $v;
                        }
                    }
                }
                $slidr_list = array_merge($city_arr,$normal_arr);
                $slidr_list = $limit>0&&count($slidr_list)>$limit?array_slice($slidr_list,0,$limit):$slidr_list;
            }elseif (C('config.many_city')){
                $condition_slider['city_id'] = array('in', [0, C('config.now_city')]);
                $slidr_list = $this->field(true)->where($condition_slider)->order('`sort` DESC,`id` ASC')->limit($limit)->select();
            }else{
                $slidr_list = M(_view('Slider'))->field(true)->where($condition_slider)->order('`sort` DESC,`id` ASC')->limit($limit)->select();
            }
	    $db_house_village = D('House_village');
            $app_version = intval($_POST['app_version']);
            $deviceId = trim($_POST['Device-Id']);
			foreach($slidr_list as $key=>$value){
				if($value['pic']){
					$slidr_list[$key]['pic'] = file_domain().'/upload/slider/'.$value['pic'];
				}
                if (strstr($value['url'], 'pages/village/my/')) {
                    $param = [
                        'pagePath' => $value['url'],
                    ];
                    $slidr_list[$key]['url'] = $db_house_village->villagePagePath($app_version,$deviceId,$param);
                }
			}
			//web版导航多城市
			if(C('config.many_city') && ($cat_key == 'web_slider')){
				foreach($slidr_list as $key=>$value){
					if(substr($value['url'],-6) == 'nocity'){
						$slidr_list[$key]['url'] = substr($value['url'],0,strlen($value['url'])-6);
					}else{
						$slidr_list[$key]['url'] = str_replace(C('config.config_site_url'),C('config.now_site_url'),$value['url']);
					}
				}
			}

			$sort = array_column($slidr_list, 'sort');
			array_multisort($sort, SORT_DESC, SORT_NUMERIC, $slidr_list);
			
			$slidr_list = replace_domain($slidr_list);
			if(empty($slidr_list)){
				$slidr_list = array();
			}elseif($need_formart){
				$slidr_list = $this->formatSlider($slidr_list);
			}
			return $slidr_list;
		}else{
			return array();
		}
	}
	
	public function formatSlider($array){
		foreach($array as &$slider_value){
			unset($slider_value['id']);
			unset($slider_value['cat_id']);
			unset($slider_value['sort']);
			unset($slider_value['status']);
			unset($slider_value['last_time']);
		}
		return $array;
	}
}

?>