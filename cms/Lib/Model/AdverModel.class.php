<?php
class AdverModel extends Model
{
    /* 通过分类的KEY得到广告列表 */
    public function get_adver_by_key($cat_key, $limit = 3,$need_formart = false)
    {
        $now_city = C('config.now_city');

        /*$adver_list = S('adver_list_' . $cat_key .'_'. $now_city.'_'.$limit);

        if (!empty($adver_list)) {
			$adver_list = replace_domain($adver_list);
            return $adver_list;
        }*/
        $database_adver_category = D('Adver_category');
        $condition_adver_category['cat_key'] = $cat_key;
        $now_adver_category = $database_adver_category->field('`cat_id`')->where($condition_adver_category)->find();
        if ($now_adver_category) {
            $condition_adver['cat_id'] = $now_adver_category['cat_id'];
            $condition_adver['status'] = '1';
            if(C('config.many_city')&&(C('customization.open_view_multi_city')!=1&&!in_array($cat_key,array('wap_shop_index_center_slider_adver','wap_shop_index_cente','wap_shop_index_top','app_index_top','app_index_center_four','app_index_center_adver','app_index_center_nine_grid')))){
				$condition_adver['city_id'] = $now_city;
			}

            if(C('customization.open_view_multi_city')==1&&in_array($cat_key,array('wap_shop_index_center_slider_adver','wap_shop_index_cente','wap_shop_index_top','app_index_top','app_index_center_four','app_index_center_adver','app_index_center_four_big_and_small','wap_index_top','wap_shop_index_showcase_adver','app_index_center_nine_grid'))){
                $adver_list = $this->field(true)->where($condition_adver)->order('`complete` DESC,`sort` DESC,`id` DESC')->limit(100)->select();
                //获取当前城市下面区县
                $chirldren_arr = D('Area')->field('area_id')->where('is_open = 1 and area_pid = '.$now_city)->select();
                $chirldren_ids = [];
                foreach ($chirldren_arr as $val){
                    $chirldren_ids[] = $val['area_id'];
                }
                $chirldren_ids[] = $now_city;
                $city_arr = $normal_arr = [];
                //只查看属于本区域的信息
                foreach($adver_list as $k=>$v){
                    $city_ids = $v['city_ids']?explode(',',$v['city_ids']):[];
                    if($v['city_id']!=0&&!array_intersect($city_ids,$chirldren_ids)){
                        unset($adver_list[$k]);
                    }else{
                        if($v['city_id']!=0){
                            $city_arr[] = $v;
                        }else{
                            $normal_arr[] = $v;
                        }
                    }
                }
                $adver_list = array_merge($city_arr,$normal_arr);
                $adver_list = count($adver_list)>$limit?array_slice($adver_list,0,$limit):$adver_list;
            } elseif(C('config.many_city')){
                $condition_adver['city_id'] = $now_city;
                $adver_list = $this->field(true)->where($condition_adver)->order('`complete` DESC,`sort` DESC,`id` DESC')->limit($limit)->select();
				$img_count = count($adver_list);
				$enough = $limit - $img_count;
				if (empty($adver_list)) {
					$condition_adver['city_id'] = 0;
					$adver_list = $this->field(true)->where($condition_adver)->order('`sort` DESC,`id` DESC')->limit($limit)->select();
				} elseif ($enough > 0 && $adver_list[0]['complete'] == 1) {
					$condition_adver['city_id'] = 0;
                    $adver_id_ary = array_column($adver_list,'id');
                    if($adver_id_ary){
                        $condition_adver['id'] =  ['not in', $adver_id_ary];
                    }
					$complete = $this->field(true)->where($condition_adver)->order('`sort` DESC,`id` DESC')->limit($enough)->select();
					if ($complete) {
						if ($adver_list) {
							$adver_list = array_merge_recursive($adver_list, $complete);
						} else {
							$adver_list = $complete;
						}
					}
				}
            }else{
                $adver_list = $this->field(true)->where($condition_adver)->order('`complete` DESC,`sort` DESC,`id` DESC')->limit($limit)->select();
            }
            foreach ($adver_list as $key => $value) {
                $adver_list[$key]['pic'] = replace_file_domain('/upload/adver/' . $value['pic']);
                $adver_list[$key]['img_count'] = $img_count;
            }
            // web版导航多城市
            if (C('config.many_city')) {
                foreach ($adver_list as $key => $value) {
                    if (substr($value['url'], - 6) == 'nocity') {
                        $adver_list[$key]['url'] = substr($value['url'], 0, strlen($value['url']) - 6);
                    } else if (strpos($value['url'],'/wap.php') >= 0) {
                        $adver_list[$key]['url'] = $value['url'];
                    } else {
                        $adver_list[$key]['url'] = str_replace(C('config.config_site_url'), C('config.now_site_url'), $value['url']);
                    }
                }
            }
			if(!$adver_list){
				$adver_list = array();
			}elseif($need_formart){
				$adver_list = $this->formatAdver($adver_list);
			}
            S('adver_list_' . $cat_key .'_'. $now_city.'_'.$limit, $adver_list, 3600);
			$adver_list = replace_domain($adver_list);
            return $adver_list;
        } else {
            return array();
        }
    }

    /* 通过分类的KEY得到广告列表 */
    public function get_adver_by_key_city($cat_key, $limit = 3, $city_id = '', $need_formart = false)
    {
        $now_city = empty($city_id)?C('config.now_city'):$city_id;
        $adver_list = S('adver_list_' . $cat_key .'_'. $now_city.'_'.$limit);

        if (!empty($adver_list)) {
            $adver_list = replace_domain($adver_list);
            return $adver_list;
        }
        $database_adver_category = D('Adver_category');
        $condition_adver_category['cat_key'] = $cat_key;
        $now_adver_category = $database_adver_category->field('`cat_id`')->where($condition_adver_category)->find();
        if ($now_adver_category) {
            $condition_adver['cat_id'] = $now_adver_category['cat_id'];
            $condition_adver['status'] = '1';
            if(C('config.many_city')){
                $condition_adver['_string'] = sprintf("city_id=%d OR FIND_IN_SET(%d,city_ids)", $now_city, $now_city);
            }

            $adver_list = $this->field(true)->where($condition_adver)->order('`complete` DESC,`sort` DESC,`id` DESC')->limit($limit)->select();
            if(C('config.many_city')){
                $img_count = count($adver_list);
                $enough = $limit - $img_count;
                if (empty($adver_list)) {
                    unset($condition_adver['_string']);
                    $condition_adver['city_id'] = 0;
                    $adver_list = $this->field(true)->where($condition_adver)->order('`sort` DESC,`id` DESC')->limit($limit)->select();
                } elseif ($enough > 0 && $adver_list[0]['complete'] == 1) {
                    unset($condition_adver['_string']);
                    $condition_adver['city_id'] = 0;
                    $complete = $this->field(true)->where($condition_adver)->order('`sort` DESC,`id` DESC')->limit($enough)->select();
                    if ($complete) {
                        if ($adver_list) {
                            $adver_list = array_merge_recursive($adver_list, $complete);
                        } else {
                            $adver_list = $complete;
                        }
                        $sort = array_column($adver_list, 'sort');
                        array_multisort($sort , SORT_DESC, $adver_list);
                    }
                }
            }
            foreach ($adver_list as $key => $value) {
                $adver_list[$key]['pic'] = file_domain() . '/upload/adver/' . $value['pic'];
                $adver_list[$key]['img_count'] = $img_count;
            }
            // web版导航多城市
            if (C('config.many_city')) {
                foreach ($adver_list as $key => $value) {
                    if (substr($value['url'], - 6) == 'nocity') {
                        $adver_list[$key]['url'] = substr($value['url'], 0, strlen($value['url']) - 6);
                    } else if (strpos($value['url'],'/wap.php') >= 0) {
                        $adver_list[$key]['url'] = $value['url'];
                    } else {
                        $adver_list[$key]['url'] = str_replace(C('config.config_site_url'), C('config.now_site_url'), $value['url']);
                    }
                }
            }
            if(!$adver_list){
                $adver_list = array();
            }elseif($need_formart){
                $adver_list = $this->formatAdver($adver_list);
            }
            S('adver_list_' . $cat_key .'_'. $now_city.'_'.$limit, $adver_list, 3600);
            $adver_list = replace_domain($adver_list);
            return $adver_list;
        } else {
            return array();
        }
    }

    public function get_one_adver($cat_key)
    {
        $adver_list = $this->get_adver_by_key($cat_key, 1);
        if ($adver_list) {
            return $adver_list[0];
        } else {
            return false;
        }
    }
	
	public function formatAdver($array){
		foreach($array as &$adver_value){
			unset($adver_value['id']);
			unset($adver_value['cat_id']);
			unset($adver_value['status']);
			unset($adver_value['last_time']);
			unset($adver_value['sort']);
			unset($adver_value['province_id']);
			unset($adver_value['city_id']);
			unset($adver_value['complete']);
			unset($adver_value['img_count']);
		}
		return $array;
	}

    // 替换url为小程序页面
    public function replace_url_to_wxapp_page($adver){
        foreach ($adver as &$_adv) {
            $_adv['url']  =   htmlspecialchars_decode($_adv['url']);
            $_adv['wxapp_page'] = htmlspecialchars_decode($_adv['wxapp_page']);

            if ($_adv['wxapp_id']) {
                $_adv['url'] = 'wxapp://'.$_adv['wxapp_id'];
                if ($_adv['wxapp_page']) {
                    // 小程序页面
                    $_adv['url'] .= '://' . $_adv['wxapp_page'];
                }
            }
            unset($_adv['wxapp_id'],$_adv['wxapp_page']);
        }
        return $adver;
    }

    // 替换url为app页面
    public function replace_url_to_app_page($adver,$app_type = ''){
         foreach ($adver as &$_adv) {
            if($_adv['app_open_type'] == 2){
                //打开app
                if ($_adv['ios_app_name']&&$_adv['ios_app_url']&&$_adv['android_app_name']&&$_adv['android_app_url']) {
                    $_adv['url'] = 'openApp://'.$_adv['ios_app_name'].'://'.base64_encode($_adv['ios_app_url']).'://'.$_adv['android_app_name'].'://'.base64_encode($_adv['android_app_url']);
                }else{
                   if ($app_type == 1) {
                        // ios
                        if ($_adv['ios_app_name']&&$_adv['ios_app_url']) {
                            $_adv['url'] = 'openApp://'.$_adv['ios_app_name'].'://'.base64_encode($_adv['ios_app_url']).'://://';
                        }
                    }elseif ($app_type == 2) {
                        // 安卓
                        if ($_adv['android_app_name']&&$_adv['android_app_url']) {
                            $_adv['url'] = 'openApp://://://'.$_adv['android_app_name'].'://'.base64_encode($_adv['android_app_url']);
                        }
                    }
                }
                unset($_adv['ios_app_name'],$_adv['ios_app_url'],$_adv['android_app_name'],$_adv['android_app_url']);

            }else{
                // 打开小程序
                if ($_adv['app_wxapp_id'] && $_adv['app_wxapp_username']) {
                    $_adv['url'] = 'wxapp://'.$_adv['app_wxapp_id'].'://' . $_adv['app_wxapp_username'];

                    // 小程序页面
                    $_adv['url'] .= '://' . $_adv['wxapp_page'];
                    
                }
                unset($_adv['wxapp_id'],$_adv['wxapp_page']);
            }
        }
        return $adver;
    }
}
?>