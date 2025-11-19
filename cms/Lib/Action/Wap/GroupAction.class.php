<?php
class GroupAction extends BaseAction{
	public function index(){
        $group_store_index = $this->config['group_store_index'];
        if ($group_store_index == 1 && $_GET['key'] == 'store') {
            $this->group_store_list();
            exit;
        }

		//判断分类信息
		$cat_url = !empty($_GET['cat_url']) ? $_GET['cat_url'] : '';
		if(!$cat_url){
			redirect($this->config['site_url'] . D("House_village")->base_url.'pages/group/store_index');//跳转到新版团购首页
		}
		$this->assign('now_cat_url', $cat_url);

		//判断地区信息
		$area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';
		$this->assign('now_area_url',$area_url);

		$circle_id = 0;
		if(!empty($area_url)){
			$tmp_area = D('Area')->get_area_by_areaUrl($area_url);
			if(empty($tmp_area)){
				$this->error('当前区域不存在！');
			}
			$this->assign('now_area', $tmp_area);

			if ($tmp_area['area_type'] == 3) {
				$now_area = $tmp_area;
			} else {
				$now_circle = $tmp_area;
				$this->assign('now_circle', $now_circle);
				$now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
				if (empty($tmp_area)) {
					$this->error('当前区域不存在！');
				}
				$circle_url = $now_circle['area_url'];
				$circle_id = $now_circle['area_id'];
				$area_url = $now_area['area_url'];
			}
			$this->assign('top_area', $now_area);
			$area_id = $now_area['area_id'];
		}else{
			$area_id = 0;
		}

		//判断排序信息
		$sort_id = !empty($_GET['sort_id']) ? $_GET['sort_id'] : 'juli';
		if($this->config['open_group_default_sort']){
			$sort_id = 'defaults';
		}
		$long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
		if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
			$sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
			$sort_array = array(
				array('sort_id'=>'defaults','sort_value'=>'智能排序'),
				array('sort_id'=>'juli','sort_value'=>'离我最近'),
				array('sort_id'=>'rating','sort_value'=>'评价最高'),
				array('sort_id'=>'start','sort_value'=>'最新发布'),
				array('sort_id'=>'solds','sort_value'=>'人气最高'),
				array('sort_id'=>'price','sort_value'=>'价格最低'),
				array('sort_id'=>'priceDesc','sort_value'=>'价格最高'),
			);
		} else {
			$sort_array = array(
				array('sort_id'=>'defaults','sort_value'=>'智能排序'),
				array('sort_id'=>'juli','sort_value'=>'离我最近'),
				array('sort_id'=>'rating','sort_value'=>'评价最高'),
				array('sort_id'=>'start','sort_value'=>'最新发布'),
				array('sort_id'=>'solds','sort_value'=>'人气最高'),
				array('sort_id'=>'price','sort_value'=>'价格最低'),
				array('sort_id'=>'priceDesc','sort_value'=>'价格最高'),
			);
			$this->assign('long_lat',$long_lat);
		}
		foreach($sort_array as $key=>$value){
			if($sort_id == $value['sort_id']){
				$now_sort_array = $value;
				break;
			}
		}

		$this->assign('sort_array',$sort_array);
		$this->assign('now_sort_array',$now_sort_array);


		//所有分类 包含2级分类
		$all_category_list = D('Group_category')->get_all_category();
		$this->assign('all_category_list',$all_category_list);

		//根据分类信息获取分类
		if(!empty($cat_url)){
			$now_category = D('Group_category')->get_category_by_catUrl($cat_url);
			if(empty($now_category)){
				$this->error_tips('此分类不存在！');
			}
			$this->assign('now_category',$now_category);

			if(!empty($now_category['cat_fid'])){
				$f_category = D('Group_category')->get_category_by_id($now_category['cat_fid']);
				$all_category_url = $f_category['cat_url'];
				$category_cat_field = $f_category['cat_field'];

				$top_category = $f_category;
				$this->assign('top_category',$f_category);

				$get_grouplist_catfid = 0;
				$get_grouplist_catid = $now_category['cat_id'];
				redirect($this->config['site_url'] . D("House_village")->base_url.'pages/group/listPage?cat_url='.$cat_url.'&cat_parent_url='.$f_category['cat_url']);//跳转到新版团购二级页面
			}else{
				$all_category_url = $now_category['cat_url'];
				$category_cat_field = $now_category['cat_field'];
				$top_category = $now_category;
				$this->assign('top_category',$now_category);

				$get_grouplist_catfid = $now_category['cat_id'];
				$get_grouplist_catid = 0;
				redirect($this->config['site_url'] . D("House_village")->base_url.'pages/group/store_class_list?cat_url='.$cat_url);//跳转到新版团购一级页面
			}
			
			redirect(U('Groupnew/category', ['cat_id' => $now_category['cat_id']]));
			

// 			if(!empty($category_cat_field)){
// 				$cat_field = unserialize($category_cat_field);
// 				foreach($cat_field as $key=>$value){
					//包含区域
// 					if($value['use_field'] && $value['use_field'] == 'area'){
// 						$all_area_list = D('Area')->get_area_list();
// 						$this->assign('all_area_list',$all_area_list);
// 					}
// 				}
// 			}
		}else{
			//所有区域
// 			$all_area_list = D('Area')->get_all_area_list();
// 			$this->assign('all_area_list',$all_area_list);
		}
		$all_area_list = D('Area')->get_all_area_list();
		$this->assign('all_area_list',$all_area_list);
		$this->assign(D('Group')->wap_get_group_list_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$sort_id, $long_lat['lat'], $long_lat['long'], $circle_id));

		/* 粉丝行为分析 */
		$this->behavior(array('model'=>'Group_index'));
		if( $this->user_session['uid'] && $this->config['open_rand_send']){
			$coupon_html = D('System_coupon')->rand_send_coupon_get(array('time'=>$_SERVER['REQUEST_TIME'],'uid'=>$this->user_session['uid']));
			$coupon_html && $this->assign('coupon_html',$coupon_html);
		}
		if ($group_store_index == 1) {
            $this->display('group_store_index');
        } else {
            redirect(U('Groupnew/index'));
            $this->display();
        }
	}


    public function config(){
        if($this->config['map_config']=='google'){
            $config['google_map_ak'] = $this->config['google_map_ak'];
        }else{
            $config['google_map_ak'] = '-1';
        }
        $this->returnCode('0',$config);
    }


    //	店铺列表
    public function group_store_list(){
        //判断分类信息
        $cat_url = !empty($_GET['cat_url']) ? $_GET['cat_url'] : '';
        $this->assign('now_cat_url', $cat_url);

        //判断地区信息
        $area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';
        $this->assign('now_area_url',$area_url);



        //导航条
        $tmp_wap_index_slider = D('Slider')->get_slider_by_key('wap_store_slider',0);

        $wap_index_slider = array();

        if(count($tmp_wap_index_slider) >= 10 &&$this->config['wap_slider_number'] == 10){
            $wap_slider_number = $this->config['wap_slider_number'];
        }else{
            $wap_slider_number = 8;
        }

        foreach($tmp_wap_index_slider as $key=>$value){
            $tmp_i = floor($key/$wap_slider_number);
            $wap_index_slider[$tmp_i][] = $value;
        }

        $this->assign('wap_index_slider',$wap_index_slider);
        $this->assign('wap_index_slider_count',count($tmp_wap_index_slider));
        $this->assign('wap_index_slider_number',$wap_slider_number);

        $circle_id = 0;
        if(!empty($area_url)){
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            if(empty($tmp_area)){
                $this->error('当前区域不存在！');
            }
            $this->assign('now_area', $tmp_area);

            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $this->assign('now_circle', $now_circle);
                $now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
                if (empty($tmp_area)) {
                    $this->error('当前区域不存在！');
                }
                $circle_url = $now_circle['area_url'];
                $circle_id = $now_circle['area_id'];
                $area_url = $now_area['area_url'];
            }
            $this->assign('top_area', $now_area);
            $area_id = $now_area['area_id'];
        }else{
            $area_id = 0;
        }

        //判断排序信息
        $sort_id = !empty($_GET['sort_id']) ? $_GET['sort_id'] : 'juli';

        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);	//获取用户的经度和纬度
        if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
            $sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
            $sort_array = array(
                array('sort_id'=>'defaults','sort_value'=>'默认排序'),
                array('sort_id'=>'rating','sort_value'=>'评价最高'),
                array('sort_id'=>'start','sort_value'=>'最新发布'),
                array('sort_id'=>'solds','sort_value'=>'人气最高'),
            );
        } else {
            $sort_array = array(
                array('sort_id'=>'juli','sort_value'=>'离我最近'),
                array('sort_id'=>'rating','sort_value'=>'评价最高'),
                array('sort_id'=>'start','sort_value'=>'最新发布'),
                array('sort_id'=>'solds','sort_value'=>'人气最高'),
            );
            $this->assign('long_lat',$long_lat);
        }
        foreach($sort_array as $key=>$value){
            if($sort_id == $value['sort_id']){
                $now_sort_array = $value;
                break;
            }
        }
        $this->assign('sort_array',$sort_array);
        $this->assign('now_sort_array',$now_sort_array);

        //所有分类 包含2级分类
        $all_category_list = D('Merchant_category')->get_all_category();
        $this->assign('all_category_list',$all_category_list);

        //根据分类信息获取分类
        if(!empty($cat_url)){
            $now_category = D('Merchant_category')->get_category_by_catUrl($cat_url);
            if(empty($now_category)){
                $this->error_tips('此分类不存在！');
            }
            $this->assign('now_category',$now_category);

            if(!empty($now_category['cat_fid'])){
                $f_category = D('Merchant_category')->get_category_by_id($now_category['cat_fid']);
                $all_category_url = $f_category['cat_url'];
                $category_cat_field = $f_category['cat_field'];

                $top_category = $f_category;
                $this->assign('top_category',$f_category);

                $get_grouplist_catfid = 0;
                $get_grouplist_catid = $now_category['cat_id'];
            }else{
                $all_category_url = $now_category['cat_url'];
                $category_cat_field = $now_category['cat_field'];
                $top_category = $now_category;
                $this->assign('top_category',$now_category);

                $get_grouplist_catfid = $now_category['cat_id'];
                $get_grouplist_catid = 0;
            }
        }
        $all_area_list = D('Area')->get_all_area_list();
        $this->assign('all_area_list',$all_area_list);
        //$this->assign(D('Merchant')->wap_get_Merchant_list_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$sort_id, $long_lat['lat'], $long_lat['long'], $circle_id,$_GET['w']));

        /* 粉丝行为分析 */
        $this->behavior(array('model'=>'Merchant_store_list'));

        $this->display('group_store_list');
    }


	//团购首页
	public function main_page(){
		redirect(U('Groupnew/index'));
		
		$show_ad = $this->config['group_main_page_show_ad'];
		$center_type = $this->config['group_main_page_center_type'];
		//$show_type = $this->config['group_main_page_show_type'];

		$group_adver = D('Adver')->get_adver_by_key('group_index',5);
		$this->assign('group_adver',$group_adver);
		$adverMiddleImg = D('Adver')->get_adver_by_key('index_middle_group',1);
		$this->assign('adverMiddleImg',$adverMiddleImg);
		if($show_ad){
			$where['type'] =$center_type;
			$where['city_id'] = ['in',[0, C("config.now_city")]];
			$slider_list = M('Group_main_page_pic_slider')->where($where)->order('sort DESC')->select();
			$this->assign('slider_list',$slider_list);
		}

		$tmp_wap_index_slider =D('Slider')->get_slider_by_key('wap_group_slider', 0);

		$wap_index_slider = array();
		foreach ($tmp_wap_index_slider as $key => $value) {
			$tmp_i = floor($key / 8);
			$wap_index_slider[$tmp_i][] = $value;
		}


		$this->assign('wap_group_slider',$wap_index_slider);
        $this->assign('wap_group_slider_num',count($wap_index_slider));

		$this->display();
	}


	public function ajax_hotel_around(){
		$now_category = D('Group_category')->get_category_by_catUrl('jiudian');

		$long_lat['lat']= $_POST['lat'];
		$long_lat['long'] = $_POST['lng'];
		$sort_id = 'juli';
		if(!empty($now_category['cat_fid'])){
			$f_category = D('Group_category')->get_category_by_id($now_category['cat_fid']);
			$all_category_url = $f_category['cat_url'];
			$category_cat_field = $f_category['cat_field'];

			$top_category = $f_category;
			$this->assign('top_category',$f_category);

			$get_grouplist_catfid = 0;
			$get_grouplist_catid = $now_category['cat_id'];
		}else{
			$all_category_url = $now_category['cat_url'];
			$category_cat_field = $now_category['cat_field'];
			$top_category = $now_category;
			$this->assign('top_category',$now_category);

			$get_grouplist_catfid = $now_category['cat_id'];
			$get_grouplist_catid = 0;
		}
		$group_list = D('Group')->wap_get_group_list_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$sort_id, $long_lat['lat'], $long_lat['long'], $circle_id,2000);
		$tmp =array();
		foreach ($group_list['group_list'] as $v) {
			$arr['group_id'] = $v['group_id'];
			$arr['long'] = $v['long'];
			$arr['lat'] = $v['lat'];
			$arr['group_name'] = $v['group_name'];
			$arr['reply_count'] = $v['reply_count'];
			$arr['score_mean'] = $v['score_mean']==0?5:$v['score_mean'];
			$arr['score_all'] = $v['score_all'];
			$arr['sname'] = $v['s_name'];
			$arr['sphone'] = $v['phone'];
			$arr['adress'] = $v['adress'];
			$arr['price'] = $v['price'];
			$tmp[] = $arr;
		}
		echo json_encode($tmp);
	}
	public function ajaxList(){
		$this->header_json();
		//判断分类信息
		$cat_url = !empty($_GET['cat_url']) ? $_GET['cat_url'] : '';

		//判断地区信息
		$area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';

		$circle_id = 0;
		if(!empty($area_url)){
			$tmp_area = D('Area')->get_area_by_areaUrl($area_url);
			if(empty($tmp_area)){
				$this->error('当前区域不存在！');
			}

			if ($tmp_area['area_type'] == 3) {
				$now_area = $tmp_area;
			} else {
				$now_circle = $tmp_area;
				$now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
				if (empty($tmp_area)) {
					$this->error('当前区域不存在！');
				}
				$circle_url = $now_circle['area_url'];
				$circle_id = $now_circle['area_id'];
				$area_url = $now_area['area_url'];
			}
			$area_id = $now_area['area_id'];
		}else{
			$area_id = 0;
		}

		//判断排序信息
		$sort_id = !empty($_GET['sort_id']) ? $_GET['sort_id'] : 'defaults';

		$long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
		if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
			$sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
			$sort_array = array(
					array('sort_id'=>'defaults','sort_value'=>'智能排序'),
					array('sort_id'=>'rating','sort_value'=>'评价最高'),
					array('sort_id'=>'start','sort_value'=>'最新发布'),
					array('sort_id'=>'solds','sort_value'=>'人气最高'),
					array('sort_id'=>'price','sort_value'=>'价格最低'),
					array('sort_id'=>'priceDesc','sort_value'=>'价格最高'),

			);
		}else{
			$sort_array = array(
					array('sort_id'=>'defaults','sort_value'=>'智能排序'),
					array('sort_id'=>'juli','sort_value'=>'离我最近'),
					array('sort_id'=>'rating','sort_value'=>'评价最高'),
					array('sort_id'=>'start','sort_value'=>'最新发布'),
					array('sort_id'=>'solds','sort_value'=>'人气最高'),
					array('sort_id'=>'price','sort_value'=>'价格最低'),
					array('sort_id'=>'priceDesc','sort_value'=>'价格最高'),

			);
		}
		//所有分类 包含2级分类
		$all_category_list = D('Group_category')->get_all_category();

		//根据分类信息获取分类
        $cat_field = array();
		if(!empty($cat_url)){
			$now_category = D('Group_category')->get_category_by_catUrl($cat_url);
			if(empty($now_category)){
				$this->error_tips('此分类不存在！');
			}

			if(!empty($now_category['cat_fid'])){
				$f_category = D('Group_category')->get_category_by_id($now_category['cat_fid']);
				$all_category_url = $f_category['cat_url'];
				$category_cat_field = $f_category['cat_field'];

				$top_category = $f_category;

				$get_grouplist_catfid = 0;
				$get_grouplist_catid = $now_category['cat_id'];
			}else{
				$all_category_url = $now_category['cat_url'];
				$category_cat_field = $now_category['cat_field'];
				$top_category = $now_category;

				$get_grouplist_catfid = $now_category['cat_id'];
				$get_grouplist_catid = 0;
			}
            $cat_field = unserialize($category_cat_field);

		}
        if (!$get_grouplist_catfid) {
            $get_grouplist_catfid = 0;
        }
        if (!$get_grouplist_catid) {
            $get_grouplist_catid = 0;
        }
        // 筛选
        $pigcms_param = $this->get_uri_param();
        if ($pigcms_param['attrs']) {
            $attrs = urldecode($pigcms_param['attrs']);
        } else {
            $attrs = '';
        }
        $d_group_order = D('Group_order');
        $database_group_category = D('Group_category');
        $group_index_store_is_show = $this->config['group_index_store_is_show'];
        if($sort_id == 'juli' && $group_index_store_is_show != 1){
			$return = D('Group')->wap_get_storeList_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$sort_id, $long_lat['lat'], $long_lat['long'], $circle_id, false, $attrs, $category_cat_field);

			foreach($return['store_list'] as &$storeValue){
				if ($storeValue['juli']>0) {
					$storeValue['range_txt'] = $this->wapFriendRange($storeValue['juli']);
				} else {
					$storeValue['range_txt'] = '';
				}
                $group_list = S('wap_store_group_'.($get_grouplist_catfid ? $get_grouplist_catfid : $get_grouplist_catid).'_'.$storeValue['store_id']);
				if(empty($group_list)){
					$group_list = D('Group')->get_single_store_group_list($storeValue['store_id'],0,true);
					
					foreach($group_list as $key=>$value){
						if(($get_grouplist_catid && $value['cat_id'] != $get_grouplist_catid) || ($get_grouplist_catfid && $value['cat_fid'] != $get_grouplist_catfid)){
							unset($group_list[$key]);
						} else {

                            // 根据开关控制团购规格问题
                            $get_spec_sale_count = $d_group_order->get_spec_sale_count($value['group_id']);
                            if ($get_spec_sale_count > 0) {
                                $group_list[$key]['sale_count'] = $get_spec_sale_count + intval($value['sale_count']);
                                $group_list[$key]['sale_txt'] = '已售' . $group_list[$key]['sale_count'];
                            }
                            if($value['trade_type']!='hotel' && $value['pin_num']<=0){
                                // 普通团购 拼团和酒店不支持
                                // 获取最便宜的团购规格价格
                                $get_spec_cheap_info = $d_group_order->get_spec_cheap($value['group_id']);
                                if ($get_spec_cheap_info) {
                                    $group_list[$key]['price'] = $get_spec_cheap_info['price'];
                                    $group_list[$key]['old_price'] = $get_spec_cheap_info['old_price'];
                                }
                            }
                            // 处理  管理商品属性字段 前端显示问题
                            $group_list[$key]['field_info'] = $database_group_category->get_cat_fid_show($value['cat_fid'], $value);
                        }
					}
					S('wap_store_group_'.($get_grouplist_catfid ? $get_grouplist_catfid : $get_grouplist_catid).'_'.$storeValue['store_id'],$group_list,360);
				}else{
					foreach($group_list as &$groupValue){
						if($groupValue['end_time'] < $_SERVER['REQUEST_TIME']){
							unset($groupValue);
						}
					}
				}
                if (!$group_list) $group_list = array();
				$storeValue['group_list'] = array_values($group_list);
				$storeValue['group_count'] = count($group_list);
				if(empty($storeValue['group_count'])){
					unset($storeValue);
				}
			}
			$return['style'] = 'store';
		}else{
			$return = D('Group')->wap_get_group_list_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$sort_id, $long_lat['lat'], $long_lat['long'], $circle_id, 0, $attrs, $category_cat_field);
			if ($return['group_list']) {
                foreach ($return['group_list'] as &$val) {
					$val['list_pic'] = thumb_img($only_group_value['list_pic'],276,168);
                    // 根据开关控制团购规格问题
                    $get_spec_sale_count = $d_group_order->get_spec_sale_count($val['group_id']);
                    if ($get_spec_sale_count > 0) {
                        $val['sale_count'] = $get_spec_sale_count + intval($val['sale_count']);
                        $val['sale_txt'] = '已售' . $val['sale_count'];
                    }
                    if($val['trade_type']!='hotel' && $val['pin_num']<=0){
                        // 普通团购 拼团和酒店不支持
                        // 获取最便宜的团购规格价格
                        $get_spec_cheap_info = $d_group_order->get_spec_cheap($val['group_id']);
                        if ($get_spec_cheap_info) {
                            $val['price'] = $get_spec_cheap_info['price'];
                            $val['old_price'] = $get_spec_cheap_info['old_price'];
                        }
                    }
                    // 处理  管理商品属性字段 前端显示问题
                    $val['field_info'] = $database_group_category->get_cat_fid_show($val['cat_fid'], $val);
                }
            }
			$return['style'] = 'group';
		}
		// 添加定制-分类后字段过滤
        if ($cat_field) {
            $return['cat_field'] = $cat_field;
        }

		echo json_encode($return);
		/* 粉丝行为分析 */
		$this->behavior(array('model'=>'Group_index'));
	}
	public function detail(){
		$now_group = D('Group')->get_group_by_groupId($_GET['group_id'],'hits-setInc');
		if(empty($now_group)){
			$this->error_tips('当前'.$this->config['group_alias_name'].'不存在！');
		}
		if($_GET['current_livevideo_id']){
			$_SESSION['current_livevideo_id'] = $_GET['current_livevideo_id'];
		}
		if($_GET['current_liveshow_id']){
			$_SESSION['current_liveshow_id'] = $_GET['current_liveshow_id'];
		}
		if($now_group['end_time']<$_SERVER['REQUEST_TIME'] && $_GET['source'] != 'group_combine'){
			$this->error_tips('当前'.$this->config['group_alias_name'].'已结束！');
		}
		if($now_group['cue']){
			$now_group['cue_arr'] = unserialize($now_group['cue']);
			// 处理下信息，填写的保留，没有填写的删除
            foreach ($now_group['cue_arr'] as $key => &$item) {
                if (!$item['value']) {
                    unset($now_group['cue_arr'][$key]);
                }
            }
		}
		if(!empty($now_group['pic_info'])){
			$merchant_image_class = new merchant_image();
			$now_group['merchant_pic'] = $merchant_image_class->get_allImage_by_path($now_group['pic_info']);
		}
		//判断是否微信浏览器，
		$long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);

		if($long_lat && $long_lat['long'] && $long_lat['lat']){
			$rangeSort = array();
			foreach($now_group['store_list'] as &$storeValue){
				$storeValue['Srange'] = getDistance($long_lat['lat'],$long_lat['long'],$storeValue['lat'],$storeValue['long']);
				$storeValue['range'] = getRange($storeValue['Srange'],false);
				$rangeSort[] = $storeValue['Srange'];
			}
			array_multisort($rangeSort, SORT_ASC, $now_group['store_list']);
			$this->assign('long_lat',$long_lat);
		}

		if($now_group['packageid']>0&&$now_group['pin_num']==0){

			$packages=M('Group_packages')->where(array('id' => $now_group['packageid'], 'mer_id' => $now_group['mer_id']))->find();
			if(!empty($packages['groupidtext'])){
				$mpackages = unserialize($packages['groupidtext']);
				$packagesgroupid = $this->check_group_status(array_keys($mpackages));
				if(is_array($packagesgroupid)){
					foreach($packagesgroupid as $gvv){
						$tmp_mpackages[$gvv['group_id']]=$mpackages[$gvv['group_id']];
					}
					$mpackages=$tmp_mpackages;
					unset($tmp_mpackages);
				}
			}else{
				$mpackages = false;
			}
			$this->assign('mpackages',$mpackages);
		}
		$is_collect = false;
		if(!empty($this->user_session)){
			if(D('Merchant_user_relation')->get_group_collect($this->user_session['uid'],$now_group['group_id'])){
				$now_group['is_collect'] = true;
			}

			//判断积分抵现
			$user_coupon_use = D('User')->check_score_can_use($this->user_session['uid'],$now_group['price'],'group',$now_group['group_id'],$now_group['mer_id']);
			$this->assign('user_coupon_use',$user_coupon_use);

			if(D('Merchant_user_relation')->get_group_collect($this->user_session['uid'],$now_group['group_id'])){
				$is_collect = true;
			}
		}
		$this->assign('is_collect',$is_collect);
		$now_group['wx_cheap']  =   floatval($now_group['wx_cheap']);

		//拼团团购详情页面 参团价格分割
		if($now_group['pin_num']>0) {
			$now_group['old_price_e'] = explode('.', sprintf('%.2f', $now_group['old_price']));
			$now_group['price_e'] = explode('.', sprintf('%.2f', $now_group['price']));
			$group_start = D('Group_start');
//			$start_num = $group_start->where(array('group_id' => $now_group['group_id'], 'status' => 0))->count();

			$now_start = $group_start->where(array('id'=>$_GET['gid']))->find();
			if (!$now_start['status']&&isset($_GET['gid']) && !empty($_GET['gid'])) {
				$in_group = false;
				$start_user_arr = $group_start->get_buyerer_by_order_id('',$_GET['gid']);

				foreach($start_user_arr as $st){
					if($st['uid']==$_SESSION['user']['uid']){
						$in_group = true;
					}
				}
				if(!$in_group){
                    $group_share_info = $group_start->get_group_start_user_by_gid($_GET['gid']);
                    // 查询最新拼团信息
                    if ($group_share_info && $group_share_info['complete_num'] <= $group_share_info['num']) {
                        $this->error_tips('当前'.$this->config['group_alias_name'].'已成团，请参加其他拼团或开团！', U('Group/detail',array('group_id'=>$now_group['group_id'])));
                    }

					$end_time = $group_share_info['start_time'] + $now_group['pin_effective_time'] * 3600;
					$effective_time = $end_time - $_SERVER['REQUEST_TIME'];
					if ($effective_time > 0) {
						$group_share_info['end_time'] = $end_time;
						$this->assign('group_share_info', $group_share_info);
					} else {
						$group_start->update_start_group($_GET['gid'], 2); //2 团购小组超时
					}
				}
			}else{

				$can_join = D('Group_start')->check_join_pin($now_group['group_id'],$this->user_session['uid'],$now_group['pin_effective_time']);

				$this->assign('can_join',$can_join);
			}


		}
		foreach( $now_group['all_pic'] as $v_img){
			$now_group['img_arr'][] = $v_img['m_image'];
		}
		
		$src	=	'<img src="'.C('config.site_url').'/';
		$now_group['content'] = str_replace('<img src="/',$src,$now_group['content']);
		$now_group['intro'] = str_replace(array("\r\n", "\r", "\n"), "", $now_group['intro']);

        $d_group_order = D('Group_order');
        $get_spec_sale_count = $d_group_order->get_spec_sale_count($now_group['group_id']);
        if ($get_spec_sale_count > 0) {
            $now_group['sale_count'] = $get_spec_sale_count + intval($now_group['sale_count']);
            $now_group['sale_txt'] = '已售' . $now_group['sale_count'];
        }
        if($now_group['trade_type']!='hotel' && $now_group['pin_num']<=0){
            // 获取最便宜的团购规格价格
            $get_spec_cheap_info = $d_group_order->get_spec_cheap($now_group['group_id']);
            if ($get_spec_cheap_info) {
                $now_group['price'] = $get_spec_cheap_info['price'];
                $now_group['old_price'] = $get_spec_cheap_info['old_price'];
            }
        }

        // 处理  管理商品属性字段 前端显示问题
        $database_group_category = D('Group_category');
        $now_group['field_info'] = $database_group_category->get_cat_fid_show($now_group['cat_fid'], $now_group);

		$this->assign('now_group',$now_group);

		//组团购
		if(!empty($_GET['fid'])&&$now_group['group_share_num']>0){
			$_SESSION['fid']=$_GET['fid'];
		}else{
			unset($_SESSION['fid']);
		}
		//新拼团
		unset($_SESSION['gid']);
		if(!empty($_GET['gid'])){
			$this_share_group = M('Group_start')->where(array('id'=>$_GET['gid']))->find();
			if(!$now_start['status']&&$now_group['group_id']==$this_share_group['group_id']){
				$_SESSION['gid']=$_GET['gid'];
			}
		}

		$time = $now_group['begin_time'] - $_SERVER['REQUEST_TIME'];
		$time_array['d'] = floor($time/86400);
		$time_array['h'] = floor($time%86400/3600);
		$time_array['m'] = floor($time%86400%3600/60);
		$time_array['s'] = floor($time%86400%60);
		$this->assign('time_array',$time_array);

		if($now_group['reply_count']){
			$reply_list = D('Reply')->get_reply_list($now_group['group_id'],0,count($now_group['store_list']),3);
			$this->assign('reply_list',$reply_list);
		}


		$merchant_group_list = D('Group')->get_grouplist_by_MerchantId($now_group['mer_id'],3,true,$now_group['group_id']);
        // 根据开关处理售出数量是否按照规格计算
        foreach ($merchant_group_list as &$val) {
            $get_spec_sale_count = $d_group_order->get_spec_sale_count($val['group_id']);
            if ($get_spec_sale_count > 0) {
                $val['sale_count'] = $get_spec_sale_count + intval($val['sale_count']);
                $val['sale_txt'] = '已售' . $val['sale_count'];
            }
        }

		$this->assign('merchant_group_list',$merchant_group_list);

		//分类下其他团购
		$category_group_list = D('Group')->get_grouplist_by_catId($now_group['cat_id'],$now_group['cat_fid'],3,true);
		foreach($category_group_list as $key=>$value){
			if($value['group_id'] == $now_group['group_id']){
				unset($category_group_list[$key]);
			} else {
                $get_spec_sale_count = $d_group_order->get_spec_sale_count($value['group_id']);
                if ($get_spec_sale_count > 0) {
                    $category_group_list[$key]['sale_count'] = $get_spec_sale_count + intval($value['sale_count']);
                    $category_group_list[$key]['sale_txt'] = '已售' . $category_group_list[$key]['sale_count'];
                }
            }
		}
		$this->assign('category_group_list',$category_group_list);

		/* 粉丝行为分析 */
		D('Merchant_request')->add_request($now_group['mer_id'],array('group_hits'=>1));

		/* 粉丝行为分析 */
		$this->behavior(array('mer_id'=>$now_group['mer_id'],'biz_id'=>$now_group['group_id'],'keyword'=>strval($_GET['keywords'])));

		if ($_SESSION['openid'] && $services = D('Customer_service')->where(array('mer_id' => $now_group['mer_id']))->select()) {
			$kf_url = U('My/go_im',array('hash'=>'serviceList_' . $now_group['mer_id'],'title'=>urlencode('联系客服')));
			$this->assign('kf_url', $kf_url);
		}

		if($now_group['trade_type'] == 'hotel'){
			if($_COOKIE['dep_date'] && strtotime(date('Y-m-d'))<strtotime($_COOKIE['end_date'])){
				$trade_hotel['time_dep_time'] = $_COOKIE['dep_date'];
				$trade_hotel['time_end_time'] = $_COOKIE['end_date'];
				$trade_hotel['show_dep_time'] = substr($_COOKIE['dep_date'],5);
				$trade_hotel['show_end_time'] =  substr($_COOKIE['end_date'],5);
				$trade_hotel['show_end_time'] =  substr($_COOKIE['end_date'],5);
				$trade_hotel['dep_time'] =  $_COOKIE['dep_time'];
				$trade_hotel['end_time'] =  $_COOKIE['end_time'];
				$trade_hotel['days'] =  (strtotime($trade_hotel['end_time'])-strtotime( $trade_hotel['dep_time']))/86400;
			}else{
				$trade_hotel['time_dep_time'] = date('Y-m-d');
				$trade_hotel['show_dep_time'] = date('m-d');
				$trade_hotel['dep_time'] = date('Ymd');
				$trade_hotel['time_end_time'] = date('Y-m-d',time()+86400);
				$trade_hotel['show_end_time'] = date('m-d',time()+86400);
				$trade_hotel['end_time'] = date('Ymd',time()+86400);
				$trade_hotel['days'] =   (strtotime($trade_hotel['end_time'])-strtotime( $trade_hotel['dep_time']))/86400;
			}
			$hotel_list_tmp = D('Trade_hotel_category')->get_all_list($now_group['mer_id'],true,$trade_hotel['dep_time'],$trade_hotel['end_time']);

			$hotel_cat_id = explode(',',$now_group['trade_info']);
//			foreach ($hotel_cat_id as $vc) {
//				$hotel_list[$vc] = $hotel_list_tmp[$vc];
//			}
			foreach($hotel_list_tmp as $key=>$v){
				if(in_array($key,$hotel_cat_id)){
					$hotel_list[] = $v;
				}
			}
			$this->assign('trade_hotel',$trade_hotel);
			$this->assign('hotel_list',$hotel_list);
			$finalprice = round($now_group['price'],2);
			$this->assign('finalprice',$finalprice);
//			$this->display('buy_trade_hotel');die;
		}
		
		if($this->config['coupon_spread_open']){
			$merchant_card_spread_info = D('Card_new')->get_card_by_mer_id_and_uid($now_group['mer_id'],$this->user_session['uid']);
			$this->assign('merchant_card_spread_info',$merchant_card_spread_info);
		}
        // 获取商品 各个详情
        if ($this->config['group_content_switch'] == 1) {
            $m_group_content = M('Group_content');
            $condition_group_content['group_id'] = $now_group['group_id'];
            $group_content = $m_group_content->where($condition_group_content)->select();
            if (!$group_content) {
                $group_content = array();
                $arr = array(
                    'name' => '商品详情',
                    'content' => $now_group['content']
                );
                $group_content[] = $arr;
            }
        } else {
            $group_content = array();
            $arr = array(
                'name' => '商品详情',
                'content' => $now_group['content']
            );
            $group_content[] = $arr;
        }
        foreach ($group_content as &$val) {
            $val['content'] = replace_file_domain_content($val['content']);
        }
        $this->assign('group_content', $group_content);

		if ($this->user_session && $this->user_session['uid']>0) {
			// 判断是否登录
			if ($this->is_weixin_browser && !$this->is_wxapp_browser && 1==$this->config['open_single_weixin_spread_user']) {
				// 暂时只针对微信公众号，且开启了开关
				$openid = D('User')->where(['uid' => $this->user_session['uid']])->getField('openid');
				if ($openid) {
					// 必须实际存在openid的值才有效
					$this->assign('share_openid',$openid);
				} else {
					fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/open_single_weixin_spread_user_jiudian_err',true);
					fdump($this->user_session['uid'],'api/log/'.date('Ymd').'/open_single_weixin_spread_user_jiudian_err',true);
					fdump($now_group['group_id'],'api/log/'.date('Ymd').'/open_single_weixin_spread_user_jiudian_err',true);
				}
			}
		}

		if($_GET['village_group']==1) {
			$this->display('village_group_detail');
		}else if($now_group['trade_type']=='hotel'){
                
			$this->display('hotel_detail');
		}elseif($now_group['pin_num']>0){
			if (empty($now_group['trade_type'])) {
                redirect(U('Groupnew/detail',array('group_id'=>$now_group['group_id'],'page_num'=>'1','village_id'=>$_GET['village_id'])));
            }
			$this->display('pin_detail');
		}else{
            // 查询一下团购规格
            $where = array(
                'group_id' => $_GET['group_id'],
                'status' => 1
            );
            $group_specifications = M('Group_specifications')->where($where)->order('price ASC')->select();
            if (!$group_specifications) {
                $group_specifications = array();
            } else {
                $info = array();
                foreach ($group_specifications as &$val) {
                    // 计算一下是否当前客户能够购买该产品
                    //每ID每天限购
                    $val['today_can_buy'] = D('Group_order')->get_spec_buy_num($val['group_id'], $val, $this->user_session['uid']);
                    $info[$val['specifications_id']] = $val;
                }
                $group_specifications = $info;
                $first = reset($group_specifications);
                $first['surplus'] = floatval($first['count_num']) - floatval($first['sale_count']);
                $first['surplus'] = $first['surplus']<0?0:$first['surplus'];
                if ($first['today_can_buy'] > $first['surplus']) {
                    $first['today_can_buy'] = $first['surplus'];
                }
                $this->assign('first',$first);
            }
            $this->assign('group_specifications',$group_specifications);
            $json_info = $group_specifications ? json_encode($group_specifications) : '';
            $this->assign('json_info',$json_info);
            if (empty($now_group['trade_type'])) {
                redirect(U('Groupnew/detail',array('group_id'=>$now_group['group_id'],'page_num'=>'1','village_id'=>$_GET['village_id'])));
            }
            $this->display();
		}
	}

	private function check_group_status($groupids=array()){
		if(!empty($groupids)){
			$tmpids=M('Group')->where('group_id in('.implode(',',$groupids).') and status="1" AND pin_num=0  AND end_time>'.$_SERVER['REQUEST_TIME'])->field('group_id')->select();
			return $tmpids;
		}
		return false;
	}


	public function set(){
		$now_group = D('Group')->get_group_by_groupId($_GET['group_id'],'hits-setInc');
		if(empty($now_group)){
			$this->error_tips('当前'.$this->config['group_alias_name'].'不存在！');
		}
		if($now_group['cue']){
			$now_group['cue_arr'] = unserialize($now_group['cue']);
		}
		if(!empty($now_group['pic_info'])){
			$merchant_image_class = new merchant_image();
			$now_group['merchant_pic'] = $merchant_image_class->get_allImage_by_path($now_group['pic_info']);
		}

		if(!empty($this->user_session)){
			if(D('Merchant_user_relation')->get_group_collect($this->user_session['uid'],$now_group['group_id'])){
				$now_group['is_collect'] = true;
			}
		}

		$this->assign('now_group',$now_group);

		if($now_group['reply_count']){
			$reply_list = D('Reply')->get_reply_list($now_group['group_id'],0,count($now_group['store_list']),3);
			$this->assign('reply_list',$reply_list);
		}

		$merchant_group_list = D('Group')->get_grouplist_by_MerchantId($now_group['mer_id'],3,true,$now_group['group_id']);
		$this->assign('merchant_group_list',$merchant_group_list);

		//分类下其他团购
		$category_group_list = D('Group')->get_grouplist_by_catId($now_group['cat_id'],$now_group['cat_fid'],3,true);
		foreach($category_group_list as $key=>$value){
			if($value['group_id'] == $now_group['group_id']){
				unset($category_group_list[$key]);
			}
		}
		$this->assign('category_group_list',$category_group_list);

		/* 粉丝行为分析 */
		D('Merchant_request')->add_request($now_group['mer_id'],array('group_hits'=>1));

		/* 粉丝行为分析 */
		$this->behavior(array('mer_id'=>$now_group['mer_id'],'biz_id'=>$now_group['group_id'],'keyword'=>strval($_GET['keywords'])));

		$this->display();
	}
	public function feedback(){
		$now_group = D('Group')->get_group_by_groupId($_GET['group_id']);
		if(empty($now_group)&&!isset($_GET['order_type'])){
			$this->error_tips('当前'.$this->config['group_alias_name'].'不存在！');
		}
		$this->assign('now_group',$now_group);

		$_POST['page'] = $_GET['page'];
		if(isset($_GET['order_type'])){
			$reply_return = D('Reply')->get_page_reply_list('',$_GET['order_type'],'','time',count($now_group['store_list']));
		}else{
			$reply_return = D('Reply')->get_page_reply_list($now_group['group_id'],0,'','time',count($now_group['store_list']));
		}

		$reply_return['pagebar'] = '';
		if($$reply_return['total'] > 1){
			if($reply_return['now'] == 1){
				$reply_return['pagebar'] .= '<a class="btn btn-weak btn-disabled">上一页</a>';
			}else{
				$reply_return['pagebar'] .= '<a class="btn btn-weak" href="'.(U('Group/feedback',array('group_id'=>$now_group['group_id'],'page'=>$reply_return['now']-1))).'">上一页</a>';
			}
			$reply_return['pagebar'] .= '<span class="pager-current">'.($reply_return['now']).'</span>';
			if($reply_return['now'] == $reply_return['total']){
				$reply_return['pagebar'] .= '<a class="btn btn-weak btn-disabled">下一页</a>';
			}else{
				$reply_return['pagebar'] .= '<a class="btn btn-weak" href="'.(U('Group/feedback',array('group_id'=>$now_group['group_id'],'page'=>$reply_return['now']+1))).'">下一页</a>';
			}
		}
		$this->assign($reply_return);

		/* 粉丝行为分析 */
		D('Merchant_request')->add_request($now_group['mer_id'],array('group_hits'=>1));

		/* 粉丝行为分析 */
		$this->behavior(array('mer_id'=>$now_group['mer_id'],'biz_id'=>$now_group['group_id']));

		$this->display();
	}
	public function ajaxFeedback(){
		$this->header_json();
		$now_group = D('Group')->get_group_by_groupId($_GET['group_id']);
		if(empty($now_group)&&!isset($_GET['order_type'])){
			exit(json_encode(array('status'=>0,'info'=>'当前'.$this->config['group_alias_name'].'不存在！')));
		}
		if(!isset($_GET['order_type'])){
			$reply_return = D('Reply')->get_page_reply_list($now_group['group_id'],0,'','time',count($now_group['store_list']));
		}else{
			$reply_return = D('Reply')->get_page_reply_list('',$_GET['order_type'],'','time',count($now_group['store_list']));

		}
		$reply_return['status'] = 1;
		exit(json_encode($reply_return));
	}
	public function branch(){
		$now_group = D('Group')->get_group_by_groupId($_GET['group_id']);

		if(empty($now_group)){
			$this->error_tips('当前'.$this->config['group_alias_name'].'不存在！');
		}

		//判断是否微信浏览器，
		if($_SESSION['openid']){
			$long_lat = D('User_long_lat')->field('long,lat')->where(array('open_id' => $_SESSION['openid']))->find();
			if($long_lat && $long_lat['long'] && $long_lat['lat']){
				import('@.ORG.longlat');
				$longlat_class = new longlat();
				$location2 = $longlat_class->gpsToBaidu($long_lat['lat'], $long_lat['long']);//转换腾讯坐标到百度坐标
				$rangeSort = array();
				foreach($now_group['store_list'] as &$storeValue){
					$storeValue['Srange'] = getDistance($location2['lat'],$location2['lng'],$storeValue['lat'],$storeValue['long']);
					$storeValue['range'] = getRange($storeValue['Srange'],false);
					$rangeSort[] = $storeValue['Srange'];
				}
				array_multisort($rangeSort, SORT_ASC, $now_group['store_list']);
			}
		}
		$this->assign('now_group',$now_group);

		/* 粉丝行为分析 */
		D('Merchant_request')->add_request($now_group['mer_id'],array('group_hits'=>1));

		/* 粉丝行为分析 */
		$this->behavior(array('mer_id'=>$now_group['mer_id'],'biz_id'=>$now_group['group_id']));

		$this->display();
	}
	public function map(){
		$now_group = D('Group')->get_group_by_groupId($_GET['group_id']);

		if(empty($now_group)){
			$this->error_tips('当前'.$this->config['group_alias_name'].'不存在！');
		}
        if(!C('config.google_map_ak')){
            //判断是否微信浏览器，
            if($_SESSION['openid']){
                $long_lat = D('User_long_lat')->field('long,lat')->where(array('open_id' => $_SESSION['openid']))->find();
                if($long_lat && $long_lat['long'] && $long_lat['lat']){
                    import('@.ORG.longlat');
                    $longlat_class = new longlat();
                    $location2 = $longlat_class->gpsToBaidu($long_lat['lat'], $long_lat['long']);//转换腾讯坐标到百度坐标
                    foreach($now_group['store_list'] as &$storeValue){
                        $storeValue['range'] = getRange(getDistance($location2['lat'],$location2['lng'],$storeValue['lat'],$storeValue['long']),false);
                    }
                }
            }
        }

		$this->assign('now_group',$now_group);

		/* 粉丝行为分析 */
		D('Merchant_request')->add_request($now_group['mer_id'],array('group_hits'=>1));

		/* 粉丝行为分析 */
		$this->behavior(array('mer_id'=>$now_group['mer_id'],'biz_id'=>$now_group['group_id']));

		$this->display();
	}
	
	public function check_group_stock(){
		D('Group')->check_group_stock(7);
	}
	
	public function buy(){
        if(empty($this->user_session)){
            if($this->is_app_browser){
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                $this->error_tips('请先进行登录！',U('Login/index',$location_param));
            }else{
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                redirect(U('Login/index',$location_param));
            }
        }
		
//		if(IS_POST){
//			/*---------------本地测试需要正常下单请禁用以下----------------------*/
//			$specifications_id = $_GET['specifications_id'] ? $_GET['specifications_id'] : ($_POST['specifications_id'] ? $_POST['specifications_id'] : 0);
//			if(!$specifications_id){		//暂时不支持规格商品
//				$check_db = M('Group_goods_stock_check');
//				$check_data = [];
//				$check_data['group_id'] = $_GET['group_id'];
//				$check_data['goods'] = json_encode(['group_id' => $_GET['group_id'], 'num' => $_POST['quantity']]);
//				$check_data['add_time'] = time();
//				$check_id = $check_db->add($check_data);
//				$arr= array();
//				$arr['param'] = serialize(array());
//				$arr['plan_time'] = -1 * time();
//				$arr['space_time'] = 1;
//				$arr['add_time'] = time();
//				$arr['file'] = 'group_goods_stock_check';
//				$arr['time_type'] = 0;
//				$arr['unique_id'] = 'group_goods_stock_check';
//				$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
//				M('Process_sub_plan')->data($arr)->add();
//
//				$process = true;
//				$i=0;
//				while($process){
//					$check_info = $check_db->where(array('id'=>$check_id))->find();
//					if($check_info['status']==1){//成功
//						$process = false;
//						$check_return = true;
//					}elseif($check_info['status']==2){//失败
//						$process = false;
//						$check_tips = '抱歉，商品库存不足！';
//						$this->error_tips($check_tips);
//					}
//					if($i>=50){
//						$process = false;
//						$this->error_tips('抱歉，系统繁忙，请稍后再试！');
//					}
//					$i++;
//					usleep(300000);
//				}
//			}
//			/*---------------本地测试需要正常下单请禁用以上截止----------------------*/
//		}
		
		

        //【直播+短视频平台】 https://www.tapd.cn/47145533/prong/stories/view/1147145533001002522
	    $live_id = D("Live_show")->preg_liveid();//正则表达式取出usergent中的live_id
	    $video_id = D("Live_show")->preg_videoid();//正则表达式取出usergent中的video_id

		$now_user = D('User')->get_user($this->user_session['uid']);
		if(empty($this->user_session['phone']) && !empty($now_user['phone'])){
			$_SESSION['user']['phone'] = $this->user_session['phone'] = $now_user['phone'];
		}
		$this->assign('now_user',$now_user);
		//获取下单商品的信息
		$now_group = D('Group')->get_group_by_groupId($_GET['group_id']);
		if(empty($now_group)){
			$this->error_tips('当前'.$this->config['group_alias_name'].'不存在！');
		}

		if($now_group['begin_time'] > $_SERVER['REQUEST_TIME']){
			$this->error_tips('此单'.$this->config['group_alias_name'].'还未开始！');
		}
		if($now_group['end_time']<$_SERVER['REQUEST_TIME']){
			$this->error_tips('此单'.$this->config['group_alias_name'].'已结束！');
		}
		if($now_group['type'] == 3 || $now_group['type'] == 4){
			$this->error_tips('此单'.$this->config['group_alias_name'].'已被抢完！');
		}

        // 判断团购是否存在规格数据，存在查询一下
		$specifications_id = $_GET['specifications_id'] ? $_GET['specifications_id'] : ($_POST['specifications_id'] ? $_POST['specifications_id'] : 0);
        if ($_GET['group_id'] && $specifications_id) {
            // 查询规格参数
            $group_specifications_info = M('Group_specifications')->where(array('group_id' => $_GET['group_id'], 'specifications_id' => $specifications_id, 'status' => 1))->find();
            if (!$group_specifications_info) {
                $group_specifications_info = array();
            } else {
                $now_group['price'] = $group_specifications_info['price'];
                $now_group['old_price'] = $group_specifications_info['old_price'];
                $now_group['once_max_day'] = $group_specifications_info['once_max_day'];
                $now_group['once_max'] = $group_specifications_info['once_max'];
                $now_group['once_min'] = $group_specifications_info['once_min'];
                $now_group['specifications_id'] = $specifications_id;
                $now_group['specifications_name'] = $group_specifications_info['specifications_name'];
            }
            $this->assign('group_specifications_info',$group_specifications_info);
        } elseif ($_GET['group_id']) {
            $group_specifications_info = M('Group_specifications')->where(array('group_id' => $_GET['group_id'],'status' => 1))->count();
            if ($group_specifications_info && $group_specifications_info>0) {
                $url = $this->config['site_url'].'/wap.php?c=Groupnew&a=detail&group_id='.$_GET['group_id'];
                $this->error_tips('请选择规格',$url);
            }
        }

		//用户等级 优惠
		$level_off=false;
		$finalprice=0;
		$user_= M('User')->where(array('uid'=>$this->user_session['uid']))->find();
		$this->user_session['level'] = $user_['level'];
		if($_GET['type']==1 || $_POST['group_type']==1){
			$now_group['price'] = $now_group['old_price'];
			$now_group['extra_pay_price']=$now_group['extra_pay_old_price'];
		}elseif($_GET['type']==3 || $_POST['group_type']==3){
			$now_group['price'] = intval(round($now_group['price']*$now_group['start_discount']))/100; //团长按团长折扣计算
            $start_max_num = intval($now_group['start_max_num']) > 1 ? intval($now_group['start_max_num']):1; // 团长每次最多购买次数 默认取1 最小也取1
            if (intval($_POST['quantity']) && intval($_POST['quantity']) > $start_max_num) {
                $this->error_tips('此单团长每次最多购买'.$start_max_num.'份！');
            }
            $now_group['start_max_num'] = $start_max_num;
		}else{
			$now_group['price'] = $now_group['price'];
		}
		$level_discount = 0; // 会员等级优惠金额
		if( $now_group['trade_type'] != 'hotel' && !empty($this->user_level) && !empty($this->user_session) && isset($this->user_session['level'])){
			$leveloff=!empty($now_group['leveloff']) ? unserialize($now_group['leveloff']) :'';

			/****type:0无优惠 1百分比 2立减*******/
			if(!empty($leveloff) && isset($leveloff[$this->user_session['level']]) && isset($this->user_level[$this->user_session['level']])){
				$level_off=$leveloff[$this->user_session['level']];
				if($level_off['type']==1){
				  	//$finalprice=$now_group['price']*($level_off['vv']/100);
					$level_discount = $now_group['price']*(1-$level_off['vv']/100);
				  	$finalprice=$finalprice>0 ? $finalprice : 0;
				  	$level_off['offstr']='单价按原价'.$level_off['vv'].'%来结算';

				}elseif($level_off['type']==2){
					//$finalprice=$now_group['price']-$level_off['vv'];
				 	$level_discount =$level_off['vv'];
				  	$finalprice=$finalprice>0 ? $finalprice : 0;
				  	$level_off['offstr']='单价立减'.$level_off['vv'].C('config.Currency_txt');

				}

			}

		}
		$after_discount_finalprice = -1;
		// 折扣优惠方式  0 折上折 1 折扣最优
		if($now_group['discount']>0){
			$tmp_finalprice = round($now_group['price']*$now_group['discount']/10,2);// 折扣后金额
			$group_discount = $now_group['price']-$tmp_finalprice;// 折扣优惠金额
		}


//		!$finalprice && $finalprice = $now_group['price'];
		if($now_group['vip_discount_type']==1){
			$finalprice = $now_group['price']*$now_group['discount']/10;
			if($group_discount>$level_discount){
				$after_discount_finalprice = $tmp_finalprice;
				$finalprice = 0;
				$level_discount = 0;

			}else{
				$after_discount_finalprice = $now_group['price']-$level_discount;
				$group_discount = 0;
			}
		}else if($now_group['vip_discount_type']==2){

			if($level_off['type']==1){
				$level_discount = round($tmp_finalprice*(1-$level_off['vv']/100),2);
			}elseif($level_off['type']==2){
				$level_discount =$level_off['vv'];
			}
			$after_discount_finalprice = $tmp_finalprice-$level_discount;
		}else{
			$after_discount_finalprice = $now_group['price']-$level_discount;
		}




		is_array($level_off) && $level_off['price']=round($finalprice,2);
		$now_start = D('Group_start')->where(array('id'=>$_GET['gid']))->find();
		if(!empty($_GET['gid'])){
			$this_share_group = M('Group_start')->where(array('id'=>$_GET['gid']))->find();
			if(!$now_start['status']&&$now_group['group_id']==$this_share_group['group_id']){
				$_SESSION['gid']=$_GET['gid'];
			}
		}
		unset($leveloff);



		if(IS_POST){
            if($now_group['pin_num']>0) {
                // 查询最新拼团信息
                $group_start = D('Group_start');
                $group_share_info = $group_start->get_group_start_user_by_gid($_GET['gid']);
                if ($group_share_info && $group_share_info['complete_num'] <= $group_share_info['num']) {
                    $this->error_tips('当前' . $this->config['group_alias_name'] . '已成团，请参加其他拼团或开团！', U('Group/detail', array('group_id' => $now_group['group_id'])));
                }
            }
			$after_discount_finalprice>=0 && $finalprice = $after_discount_finalprice ;
			$level_discount>0 && $now_group['vip_discount_money'] = $level_discount;
			$finalprice > 0 && $now_group['price']=round($finalprice,2);
			$level_discount > 0 && $now_group['level_price']=$after_discount_finalprice;
			if ($_POST['custom_field']) {
			    foreach ($_POST['custom_field'] as &$val) {
			        if ($val['type'] == 8) {
                        $val['value'] = $val['date_value'] . $val['time_value'];
                    }
                }
            }
            $now_group['write_field'] = serialize($_POST['custom_field']);
            // 处理记录下购买
            D('Group')->group_record($_GET['group_id'], $this->user_session['uid'], $now_group['mer_id'], 2);
            
			if (C('config.open_single_spread')==1) {
					// 处理分销人
				if ($_POST['openid']) {
					$distributor_user = D('User')->get_user($_POST['openid'],'openid');
					// 验证是否有分销权限
					$distributor = M('Distributor')->where(array('uid'=>$distributor_user['uid'],'status'=>1))->find();
					if ($distributor) {
						$now_group['distributor_uid'] = intval($distributor_user['uid']);
					}
				}elseif ($_POST['share_uid']) {
					// 验证是否有分销权限
					$distributor = M('Distributor')->where(array('uid'=>$_POST['share_uid'],'status'=>1))->find();
					if ($distributor) {
						$now_group['distributor_uid'] = intval($_POST['share_uid']);
					}
				}
            }
			if (trim($_POST['share_openid']) && 1==$this->config['open_single_weixin_spread_user']) {
				// 处理单个核销问题
				$now_group['share_openid'] = trim($_POST['share_openid']);
			}
            
            //【直播+短视频平台】 https://www.tapd.cn/47145533/prong/stories/view/1147145533001002522
	        if($live_id && $this->config['live_open'] == '1'){
	            $live_goods = D("Live_show_goods")->get_goods_percent_by_live($live_id,'group');
	            if(in_array($now_group['group_id'], $live_goods['goodids'])){
	            	$now_group['is_liveshow'] = 1;
	            	$now_group['liveshow_id'] = $live_id;
	            	$now_group['liveshow_percent'] = $live_goods['percent'][$now_group['group_id']] == -1 ? C('config.live_show_commission_percent') : $live_goods['percent'][$now_group['group_id']];
	            }
	        }
	        elseif($video_id && $this->config['live_open'] == '1'){
	        	$live_goods = D("Live_video_goods")->get_goods_percent_by_live($video_id,'group');
	            if(in_array($now_group['group_id'], $live_goods['goodids'])){
	            	$now_group['is_livevideo'] = 1;
	            	$now_group['livevideo_id'] = $video_id;
	            	$now_group['liveshow_percent'] = $live_goods['percent'][$now_group['group_id']] == -1 ? C('config.live_video_commission_percent') : $live_goods['percent'][$now_group['group_id']];
	            }
	        }

            if (isset($now_group['specifications_id']) && $now_group['specifications_id']) {
                $m_group_specifications = M('Group_specifications');
                $specifications_info = $m_group_specifications->field('sale_count')->where(array('specifications_id' => $now_group['specifications_id']))->find();
                $sale_count = intval($_POST['quantity']) + intval($specifications_info['sale_count']);
                if (intval($specifications_info['count_num'])>0 && $sale_count>$specifications_info['count_num']) {
                    $buy_num = intval($specifications_info['count_num']) - intval($specifications_info['sale_count']);
                    if ($buy_num>0) {
                        $this->error_tips('此单当前最多还可购买'.$buy_num.'份！');
                    } else {
                        $this->error_tips('当前团购已经售罄！');
                    }
                }
            } else {
                $sale_count = intval($_POST['quantity']) + intval($now_group['sale_count']);
                if (intval($now_group['count_num'])>0 && $sale_count>$now_group['count_num']) {
                    $buy_num = intval($now_group['count_num']) - intval($now_group['sale_count']);
                    if ($buy_num>0) {
                        $this->error_tips('此单当前最多还可购买'.$buy_num.'份！');
                    } else {
                        $this->error_tips('当前团购已经售罄！');
                    }
                }
            }

			$result = D('Group_order')->save_post_form($now_group,$this->user_session['uid'],0);

			if($result['error'] == 1){
				$this->error_tips($result['msg']);
			}
			
			if(IS_AJAX){
				$this->success(array('order_id'=>$result['order_id']));
			}else{
				redirect(U('Pay/check',array('order_id'=>$result['order_id'],'type'=>'group')));
			}
		}else{
			if($now_group['tuan_type'] == 2){
				$now_group['user_adress'] = D('User_adress')->get_one_adress($this->user_session['uid'],intval($_GET['adress_id']));

				/*运费计算*/
				if($now_group['user_adress']){
					$express_fee = D('Group')->get_express_fee($now_group['group_id'],$now_group['price'],$now_group['user_adress']);
					$now_group['express_fee'] =$express_fee['freight'];
					$now_group['express_template'] = $express_fee;
					//$now_group['price'] += $now_group['express_fee'];
				}

			}
			$now_group['wx_cheap'] = floatval($now_group['wx_cheap']);
			$pick_lists = D('Pick_address')->get_pick_addr_by_merid($now_group['mer_id']);
			$database_group_store = D('Group_store');
			$condition_group_store['group_id'] = $now_group['group_id'];
			$store_list = $database_group_store->field(true)->where($condition_group_store)->select();
			$store_arr = array();
			foreach ($store_list as $value) {
				$store_arr[] = $value['store_id'];
			}
			foreach($pick_lists as $key=>$vv){
				if(strpos($vv['pick_addr_id'],'s')!==false){
					$tmp_store_id = preg_match('/\d+/',$vv['pick_addr_id'],$r);
					if(!in_array($r[0],$store_arr)){

					}else{
						$pick_list[] = $vv;
					}
				}else{
					$pick_list[] = $vv;
				}
			}


			if(!empty($_GET['pick_addr_id'])){
				foreach($pick_list as $k=>$v){
					if($v['pick_addr_id']==$_GET['pick_addr_id']){
						$pick_address = $v;
						break;
					}
				}
			}else{
				$pick_address =$pick_list[0];
			}
			$this->assign('pick_address',$pick_address);
			$this->assign('leveloff',$level_off);
			$this->assign('finalprice',$finalprice);

			if($now_group['tuan_type']!=2){
				$now_group['express_fee']=0;
			}

			//每ID每天限购
			if($now_group['once_max_day']){
				$now_user_today_count = D('Group_order')->get_once_max_day($now_group['group_id'],$this->user_session['uid'], $specifications_id);
				$today_can_buy = $now_group['once_max_day'] - $now_user_today_count;

				if($today_can_buy <= 0){
					$this->error_tips('该商品限制单人每天只能购买' . $now_group['once_max_day'] . '份，您当天购买的数量已达上限，不能再购买!');
				}

				if(!$now_group['once_max'] && $today_can_buy || $now_group['once_max'] > $today_can_buy){
					$now_group['once_max'] = $today_can_buy;
				}
			}
			// 获取用户位置
            $database_area = D('Area');
            $database_user_long_lat = D('User_long_lat');
            $long_lat = $database_user_long_lat->getLocation($_SESSION['openid']);
            if (!empty($long_lat) && $long_lat['long'] && $long_lat['lat']) {
                $this->assign('long_lat', $long_lat);
            }
            $now_city = $database_area->get_area_by_areaId($this->config['now_city']);
            $this->assign('city_name', $now_city['area_name']);

            // 填写字段自定义
            $database_group_category = D('Group_category');
            $condition_now_group_category['cat_id'] = intval($now_group['cat_fid']);
            $now_category = $database_group_category->field('write_field')->where($condition_now_group_category)->find();

            $write_info = unserialize($now_category['write_field']);
            foreach ($now_category['cue_field'] as $val){
                $sort[] = $val['sort'];
            }
            $sort = array();
            foreach($write_info as $key=>$val){
                $sort[] = $val['sort'];
            }
            array_multisort($sort, SORT_DESC, $write_info);

            $this->assign('write_info', $write_info);

			$this->assign('now_group',$now_group);
			$this->assign('after_discount_finalprice',$after_discount_finalprice);
			$this->assign('group_discount',$group_discount);
			$this->assign('level_discount',$level_discount);
			
			if($this->user_session['phone']){
                $openid = M('User')->where(array('uid'=>$this->user_session['uid']))->getField('openid');
                if(strpos($openid,'~no_use') !== false){
                    $this->error_tips('请先重新进行登录！');
                }
				$this->assign('pigcms_phone',substr($this->user_session['phone'],0,3).'****'.substr($this->user_session['phone'],7));
			}else{
				$this->assign('pigcms_phone','您需要绑定手机号码');
			}
			/* 粉丝行为分析 */
			D('Merchant_request')->add_request($now_group['mer_id'],array('group_hits'=>1));

			/* 粉丝行为分析 */
			$this->behavior(array('mer_id'=>$now_group['mer_id'],'biz_id'=>$now_group['group_id']));

            // 查看是否参与优惠组合套餐
            $where = [
                'group_id' => $now_group['group_id']
            ];
            $groupCombine = D('Group_combine_activity')->getActivityByGroup($where);
            $this->assign('groupCombine',$groupCombine);
			
			//第三方对接 ID1008086 找上一个订单记录的用户信息
			if(C('config.baodi_ticket_url') && $now_group['custom_product_id']){
				$lastGroupOrder = M('Group_order')->where(['uid' => $this->user_session['uid'], 'userinput' => ['neq', '']])->order('`order_id` DESC')->find();
				if($lastGroupOrder){
					$lastUserInput = unserialize($lastGroupOrder['userinput']);
					$this->assign('lastUserInput',$lastUserInput);
				}
			}
			
			if($now_group['trade_type'] == 'hotel'){
				$trade_hotel['time_dep_time'] = date('Y-m-d');
				$trade_hotel['show_dep_time'] = date('m-d');
				$trade_hotel['dep_time'] = date('Ymd');
				$trade_hotel['time_end_time'] = date('Y-m-d',time()+86400);
				$trade_hotel['show_end_time'] = date('m-d',time()+86400);
				$trade_hotel['end_time'] = date('Ymd',time()+86400);
				$hotel_list_tmp = D('Trade_hotel_category')->get_all_list($now_group['mer_id'],true,$trade_hotel['dep_time'],$trade_hotel['end_time']);

				$hotel_cat_id = explode(',',$now_group['trade_info']);
				foreach ($hotel_cat_id as $vc) {
					$hotel_list[$vc] = $hotel_list_tmp[$vc];
				}
				$this->assign('trade_hotel',$trade_hotel);
				$this->assign('hotel_list',$hotel_list);
				$this->display('buy_trade_hotel');
			}else{
				$this->display();
			}
		}
	}
	public function ajax_get_trade_hotel_stock(){
		$now_group = D('Group')->get_group_by_groupId($_POST['group_id']);

		$hotel_list_tmp = D('Trade_hotel_category')->get_all_list($now_group['mer_id'],true,$_POST['dep_time'],$_POST['end_time']);
		$hotel_cat_id = explode(',',$now_group['trade_info']);
//		foreach ($hotel_cat_id as $vc) {
//			$hotel_list[$vc] = $hotel_list_tmp[$vc];
//		}
		foreach($hotel_list_tmp as $key=>$v){
			if(in_array($key,$hotel_cat_id)){
				$hotel_list[] = $v;
			}
		}

		echo json_encode($hotel_list);exit;
	}
	public function ajax_get_trade_hotel_price(){
		$now_group = D('Group')->get_group_by_groupId($_POST['group_id']);
		$hotel_list = D('Trade_hotel_category')->get_cat_price($now_group['mer_id'],$_POST['cat_id'],$_POST['dep_time'],$_POST['end_time']);
		echo json_encode($hotel_list);exit;
	}
	public function shop(){
		$now_store = D('Merchant_store')->get_store_by_storeId($_GET['store_id']);
		if(empty($now_store)){
			$this->error_tips('该店铺不存在！');
		}
		//得到当前店铺的评分
		$store_score = D('Merchant_score')->field('`score_all`,`reply_count`')->where(array('parent_id'=>$now_store['store_id'],'type'=>'2'))->find();
		$this->assign('store_score',$store_score);

		if(!empty($this->user_session)){
			$database_user_collect = D('User_collect');
			$condition_user_collect['type'] = 'group_shop';
			$condition_user_collect['id'] = $now_store['store_id'];
			$condition_user_collect['uid'] = $this->user_session['uid'];
			if($database_user_collect->where($condition_user_collect)->find()){
				$now_store['is_collect'] = true;
			}
		}

		$this->assign('now_store',$now_store);

		$store_group_list = D('Group')->get_store_group_list($now_store['store_id'],0,true);
		$this->assign('store_group_list',$store_group_list);

		//为粉丝推荐
		$index_sort_group_list = D('Group')->get_group_list('index_sort',10,true);
		//判断是否微信浏览器，
		if($_SESSION['openid'] && $index_sort_group_list){
			$long_lat = D('User_long_lat')->field('long,lat')->where(array('open_id' => $_SESSION['openid']))->find();
			if($long_lat && $long_lat['long'] && $long_lat['lat']){
				import('@.ORG.longlat');
				$longlat_class = new longlat();
				$location2 = $longlat_class->gpsToBaidu($long_lat['lat'], $long_lat['long']);//转换腾讯坐标到百度坐标
				$group_store_database = D('Group_store');
				foreach($index_sort_group_list as &$storeGroupValue){
					$tmpStoreList = $group_store_database->get_storelist_by_groupId($storeGroupValue['group_id']);
					if($tmpStoreList){
						foreach($tmpStoreList as &$tmpStore){
							$tmpStore['Srange'] = getDistance($location2['lat'],$location2['lng'],$tmpStore['lat'],$tmpStore['long']);
							$tmpStore['range'] = getRange($tmpStore['Srange'],false);
							$rangeSort[] = $tmpStore['Srange'];
						}
						array_multisort($rangeSort, SORT_ASC, $tmpStoreList);
						$storeGroupValue['store_list'] = $tmpStoreList;
						$storeGroupValue['range'] = $tmpStoreList[0]['range'];
					}
				}
			}
		}
		$this->assign('index_sort_group_list',$index_sort_group_list);


		/* 粉丝行为分析 */
		D('Merchant_request')->add_request($now_store['mer_id'],array('group_hits'=>1));

		/* 粉丝行为分析 */
		$this->behavior(array('mer_id'=>$now_store['mer_id'],'store_id'=>$now_store['store_id']));

		if ($_SESSION['openid'] && $services = D('Customer_service')->where(array('mer_id' => $now_store['mer_id']))->select()) {
			$kf_url = U('My/go_im',array('hash'=>'serviceList_' . $now_store['mer_id'],'title'=>urlencode('联系客服')));
			$this->assign('kf_url', $kf_url);
		}

		$this->display();
	}
	public function addressinfo(){
		$long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
		$this->assign('long_lat',$long_lat);

		$now_store = D('Merchant_store')->get_store_by_storeId($_GET['store_id']);
		if(empty($now_store)){
			$this->error_tips('该店铺不存在！');
		}
		$this->assign('now_store',$now_store);

		/* 粉丝行为分析 */
		D('Merchant_request')->add_request($now_store['mer_id'],array('group_hits'=>1));

		/* 粉丝行为分析 */
		$this->behavior(array('mer_id'=>$now_store['mer_id'],'store_id'=>$now_store['store_id']));

		$this->display();
	}
	public function get_route(){
		$long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
		$this->assign('long_lat',$long_lat);

		$now_store = D('Merchant_store')->get_store_by_storeId($_GET['store_id']);
		if(empty($now_store)){
			$this->error_tips('该店铺不存在！');
		}
		$this->assign('now_store',$now_store);

		$this->assign('no_gotop',true);

		/* 粉丝行为分析 */
		D('Merchant_request')->add_request($now_store['mer_id'],array('group_hits'=>1));

		/* 粉丝行为分析 */
		$this->behavior(array('mer_id'=>$now_store['mer_id'],'store_id'=>$now_store['store_id']));

		$this->display();
	}

	public function group_address_info() {
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
        $this->assign('long_lat',$long_lat);
        $group = D('Group')->get_group_by_groupId($_GET['group_id']);
        if(empty($group)){
            $this->error_tips('该团购不存在！');
        }
        $this->assign('group',$group);

        $this->assign('no_gotop',true);
        /* 粉丝行为分析 */
        D('Merchant_request')->add_request($group['mer_id'],array('group_hits'=>1));

        /* 粉丝行为分析 */
        $this->behavior(array('mer_id'=>$group['mer_id'],'group_id'=>$group['group_id']));

        $this->display();
    }

    public function group_get_route() {
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
        $this->assign('long_lat',$long_lat);

        $group = D('Group')->get_group_by_groupId($_GET['group_id']);
        if(empty($group)){
            $this->error_tips('该团购不存在！');
        }
        $this->assign('group',$group);

        $this->assign('no_gotop',true);

        /* 粉丝行为分析 */
        D('Merchant_request')->add_request($group['mer_id'],array('group_hits'=>1));

        /* 粉丝行为分析 */
        $this->behavior(array('mer_id'=>$group['mer_id'],'group_id'=>$group['store_id']));

        $this->display();
    }

	public function around(){

		$long_lat['lat'] = $_COOKIE['around_lat'];
		$long_lat['long'] = $_COOKIE['around_long'];

		if(empty($long_lat['lat']) || empty($long_lat['long'])){
			$_SESSION['openid'] && $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],120);
		}
// 		$x = $_COOKIE['around_lat'];
// 		$y = $_COOKIE['around_long'];
// 		$adress = $_COOKIE['around_adress'];

		if(empty($long_lat['lat']) || empty($long_lat['long'])){
			redirect(U('Group/around_adress'));
		}

// 		if ($_SESSION['openid'] && ($long_lat = D('User_long_lat')->field('long,lat')->where(array('open_id' => $_SESSION['openid']))->find())) {
// 			$long_lat['lat'] = 31.841217;
// 			$long_lat['long'] = 117.207008;
			$this->assign('lat_long', $long_lat['lat'] . ',' . $long_lat['long']);
			$around_range = $this->config['group_around_range'];
			$stores = D("Merchant_store")->field("`store_id`,ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$long_lat['lat']}*PI()/180-`lat`*PI()/180)/2),2)+COS({$long_lat['lat']}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long_lat['long']}*PI()/180-`long`*PI()/180)/2),2)))*1000) AS juli")->where("`have_group`='1' AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$long_lat['lat']}*PI()/180-`lat`*PI()/180)/2),2)+COS({$long_lat['lat']}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long_lat['long']}*PI()/180-`long`*PI()/180)/2),2)))*1000) < '$around_range'")->select();
			$store_ids = array();
			foreach ($stores as $store){
				$store_ids[] = $store['store_id'];
				$store_around_range[$store['store_id']] = $store['juli'];
			}
			$groupids = array();
			if ($store_ids) {
				$gslist = D('Group_store')->field('`group_id`,`store_id`')->where(array('store_id' => array('in', $store_ids)))->select();
				foreach ($gslist as $gs){
					$groupids[] = $gs['group_id'];
					$group_around_range[$gs['group_id']] = $store_around_range[$gs['store_id']];
				}
			}
			$this->assign('group_around_range',$group_around_range);
// 			$this->assign('adress', $adress);

			//得到附近的团购列表
			$groupids && $group_return = D('Group')->get_group_list_by_group_ids($groupids,'', true);
			$this->assign($group_return);
// 		}

		$this->display();
	}
	public function around_adress(){
		if(!empty($_GET['long']) && !empty($_GET['lat'])){
			$map_center = 'new BMap.Point('.$_GET['long'].','.$_GET['lat'].')';
		}else{
			import('ORG.Net.IpLocation');
			$IpLocation = new IpLocation();
			$last_location = $IpLocation->getlocation();
			$map_center = '"'.iconv('GBK','UTF-8',$last_location['country']).'"';
		}
		$this->assign('map_center',$map_center);
		$this->display();
	}
	public function navigation(){

		//导购热门广告
		$wap_center_adver = D('Adver')->get_adver_by_key('wap_group_navigation',4);
		$this->assign('wap_center_adver',$wap_center_adver);
		// dump($wap_center_adver);
		//所有分类 包含2级分类
		$all_category_list = D('Group_category')->get_all_category();
		$this->assign('all_category_list',$all_category_list);
		$this->assign('no_gotop',true);
		// dump($all_category_list);
		$this->display();
	}

}

?>