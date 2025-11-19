<?php
class HomeAction  extends BaseAction{
	public function index_version_two(){
        
        $isLouyuConn = I('isLouyuConn','');
        if ($isLouyuConn){
            $return_arr['index_guess_like_arr']  = D('Config_data')->get_index_guess_like();
            $return_arr['wxapp_appid'] = C('config.pay_wxapp_appid');
            $return_arr['wxapp_username'] = C('config.pay_wxapp_username');
            $this->returnCodeOk($return_arr);
        }
        
        $app_type   =   I('app_type');
		//模板类型
		$return_arr['mobile_index_tpl'] = $this->config['mobile_index_tpl'];
		
		//导航栏“更多”图标按钮导航列表
		$return_arr['plat_index_more_slider'] = D('Slider')->get_slider_by_key('plat_index_more',5);
		
		//《标准版模板》四个大按钮导航
		$return_arr['app_index_top_btn_slider'] = D('Slider')->get_slider_by_key('app_index_top_btn',4);
		
		//《标准版模板》轮播图
		$return_arr['app_index_top_adver_small'] = D('Adver')->get_adver_by_key('wap_index_top',5);
		foreach($return_arr['app_index_top_adver_small'] as $key=>$value){
			$return_arr['app_index_top_adver_small'][$key]['pic'] = thumb_img($value['pic'], 900, 500);
		}

		//《清新版模板》轮播图
		$return_arr['app_index_top_adver_big'] = D('Adver')->get_adver_by_key('app_index_top',5);
		foreach($return_arr['app_index_top_adver_big'] as $key=>$value){
            $return_arr['app_index_top_adver_big'][$key]['pic'] = thumb_img($value['pic'], 900, 500);
            if(C('customization.life_tools')){
                if(empty($this->_uid) && !empty($value['assigns_staff'])){
                   unset($return_arr['app_index_top_adver_big'][$key]);
                }elseif (!empty($this->_uid) && !empty($value['assigns_staff'])){
                    if(!empty($value['assigns_staff'])){
                        $employee_list=[];
                        $staffArr=unserialize($value['assigns_staff']);
                        $staff_mer=[];
                        $is_show = false;
                        foreach ($staffArr as $staff){
                            $staff_mer[]=$staff['mer_id'];
                            $where=[];
                            $where['mer_id']=array('in',$staff_mer);
                            $where['uid']=$this->_uid;
                            $where['_string'] = '';
                            $where['_string'] .= '(';
                            foreach ($staff['ids'] as $lk => $lv) {
                                if ($lk == 0) {
                                    $where['_string'] .= 'FIND_IN_SET("' . $lv . '", lable_ids)';
                                } else {
                                    $where['_string'] .= ' OR FIND_IN_SET("' . $lv . '", lable_ids)';
                                }
                            }
                            $where['_string'] .= ')';
                            $employee_list=M('Employee_card_user')->where($where)->select();
                            //fdump(["staffArr"=>$staffArr,"ids"=>$staff['ids'],"assigns_staff"=>$value['assigns_staff'],"sql"=>M('Employee_card_user')->getLastSql(),"employee_list"=>$employee_list],"uid4444444444444444444",1);
                            if($employee_list){
                            	$is_show = true;
                                continue;
                            }
                        }
                        if($is_show == false){
                            unset($return_arr['app_index_top_adver_big'][$key]);
                        }
                    }
                }
            }
		}
        $return_arr['app_index_top_adver_big']=array_values($return_arr['app_index_top_adver_big']);
		//导航栏导航列表
		$return_arr['slider_row'] = $this->config['mobile_index_slider_row'] ? : 2;	//导航行数
        $tmp_wap_index_slider = D('Slider')->get_slider_by_key('wap_slider',0);
		$return_arr['silder_limit'] = count($tmp_wap_index_slider) > 8 ? 10 : 8;
		$return_arr['slider_num'] = 5;
        $wap_index_slider = array();
        foreach($tmp_wap_index_slider as $key=>$value){
            if(!stristr($value['url'],'Weidian') && (!stristr($value['url'],'Invitation') || $this->DEVICE_ID == 'wxapp' || $this->DEVICE_ID == 'packapp' || $this->DEVICE_ID == '') && (!stristr($value['url'],'City_car') || $this->DEVICE_ID == 'wxapp' || $this->DEVICE_ID == 'packapp' || $this->DEVICE_ID == '') ){
                $wap_index_slider[] = $value;
            }
        }
		$return_arr['slider'] = array();
        foreach($wap_index_slider as $v){
			$v['url']  =   htmlspecialchars_decode($v['url']);
            $v['wxapp_page'] = htmlspecialchars_decode($v['wxapp_page']);
			// 小程序跳转到别的小程序
			if ($this->DEVICE_ID == 'wxapp' &&$v['wxapp_id']) {
				$v['url'] = 'wxapp://'.$v['wxapp_id'];
				if ($v['wxapp_page']) {
					// 小程序页面
					$v['url'] .= '://' . $v['wxapp_page'];
				}
				unset($v['wxapp_id'],$v['wxapp_page']);
			}
			//压缩图片
			$v['pic'] = thumb_img($v['pic'], 200, 200);
			$return_arr['slider'][]  =   $v; 
        }

		// 小程序跳转到别的小程序
		if ($this->DEVICE_ID == 'wxapp') {
			$return_arr['plat_index_more_slider'] = D('Adver')->replace_url_to_wxapp_page($return_arr['plat_index_more_slider']);
			$return_arr['app_index_top_btn_slider'] = D('Adver')->replace_url_to_wxapp_page($return_arr['app_index_top_btn_slider']);
			$return_arr['app_index_top_adver_small'] = D('Adver')->replace_url_to_wxapp_page($return_arr['app_index_top_adver_small']);
			$return_arr['app_index_top_adver_big'] = D('Adver')->replace_url_to_wxapp_page($return_arr['app_index_top_adver_big']);
		}

		//平铺4张小图广告列表
		$return_arr['app_index_center_four_adver'] = D('Adver')->get_adver_by_key('app_index_center_four',4);

		// 开启一大三小模式
		$return_arr['app_index_center_four_show_type'] = C('config.app_index_center_four_show_type');
		if(C('config.app_index_center_four_show_type')==1){
			$return_arr['app_index_center_four_adver'] = D('Adver')->get_adver_by_key('app_index_center_four_big_and_small',4);
		}elseif (C('config.app_index_center_four_show_type')==2){
            $return_arr['app_index_center_four_adver'] = D('Adver')->get_adver_by_key('app_index_center_nine_grid',9);
        }
		
		//中间栏轮播广告图
		$return_arr['app_index_center_adver'] = D('Adver')->get_adver_by_key('app_index_center_adver',5);


		// 小图广告、中间栏轮播小程序跳转到别的小程序
		if ($this->DEVICE_ID == 'wxapp') {
			$return_arr['app_index_center_four_adver'] = D('Adver')->replace_url_to_wxapp_page($return_arr['app_index_center_four_adver']);
			$return_arr['app_index_center_adver'] = D('Adver')->replace_url_to_wxapp_page($return_arr['app_index_center_adver']);
		}
		

		// app跳转到别的app
		if ($this->DEVICE_ID != 'wxapp') {
			$return_arr['slider'] = D('Adver')->replace_url_to_app_page($return_arr['slider'], $app_type);
			$return_arr['plat_index_more_slider'] = D('Adver')->replace_url_to_app_page($return_arr['plat_index_more_slider'], $app_type);
			$return_arr['app_index_top_btn_slider'] = D('Adver')->replace_url_to_app_page($return_arr['app_index_top_btn_slider'], $app_type);
			$return_arr['app_index_top_adver_small'] = D('Adver')->replace_url_to_app_page($return_arr['app_index_top_adver_small'], $app_type);
			$return_arr['app_index_top_adver_big'] = D('Adver')->replace_url_to_app_page($return_arr['app_index_top_adver_big'], $app_type);
			$return_arr['app_index_center_four_adver'] = D('Adver')->replace_url_to_app_page($return_arr['app_index_center_four_adver'], $app_type);
			$return_arr['app_index_center_adver'] = D('Adver')->replace_url_to_app_page($return_arr['app_index_center_adver'], $app_type);
		}
		

		//平台滚动播报信息
		if($this->config['show_scroll_msg']){
			$return_arr['scroll_msg'] = D('Scroll_msg')->get_scroll_msg_user();
		}else{
			$return_arr['scroll_msg'] = array();
		}

		//按钮
		$entrance = M('Entrance')->where(array('key'=>'banner_ticket_entrance'))->find();
		$entrance['img'] = empty($entrance['img']) ? '' : replace_file_domain($entrance['img']);
		$entrance['url'] = htmlspecialchars_decode($entrance['url']);
		$return_arr['entrance'] = replace_domain($entrance);
		
		
		//展示 平台快报
		$return_arr['mobile_index_plat_news'] = $this->config['mobile_index_plat_news'] ? true : false;
		// 快报
		if($return_arr['mobile_index_plat_news']){
            $return_arr['news_list'] = S('plat_news/index_list_' . $this->config['now_city']);
            if(empty($return_arr['news_list'])){
                $news_info = M('')->field('`n`.`id`,`n`.`title`,`c`.`name`')->table(array(C('DB_PREFIX').'system_news'=>'n',C('DB_PREFIX').'system_news_category'=>'c'))->where("`c`.`status`='1' AND `c`.`id`=`n`.`category_id` AND `n`.`status`='1' AND (`n`.`city_id`='{$this->config['now_city']}' OR `n`.`city_id`='0')")->order('`n`.`sort` DESC,`n`.`id` DESC')->limit(8)->select();
                if($news_info){
                    foreach($news_info as $value){
                        $return_arr['news_list'][] = array('cat_name'=>$value['name'],'title'=>$value['title']);
                    }
                }
                S('plat_news/index_list_' . $this->config['now_city'], $return_arr['news_list'], 3600);
            }
            if(empty($return_arr['news_list'])){
                $return_arr['mobile_index_plat_news'] = false;
            }
		}
		
		$now_city_id = $this->config['now_city'];
		
		//超值抢购
		$plat_buy_active_info_key = 'plat_buy_active/info_'.$now_city_id;
		$return_arr['plat_buy_active_info'] = S($plat_buy_active_info_key);
		if(empty($return_arr['plat_buy_active_info'])){
			$return_arr['plat_buy_active_info'] = array('have'=>false,'active_list'=>[]);
            if(C('customization.open_view_multi_city')==1){
                //开启多城市配置
                $now_activity = D('Plat_buy_active_group')->where(array('begin_time' => array('lt', $_SERVER['REQUEST_TIME']), 'end_time' => array('gt', $_SERVER['REQUEST_TIME']), 'status' => '1', 'city_id' => array('neq',0)))->order('`pigcms_id` ASC')->select();
                //获取当前城市下面区县
                $chirldren_arr = D('Area')->field('area_id')->where('is_open = 1 and area_pid = '.$now_city_id)->select();
                $chirldren_ids = [];
                foreach ($chirldren_arr as $val){
                    $chirldren_ids[] = $val['area_id'];
                }
                $chirldren_ids[] = $now_city_id;
                //区域管理员只查看属于本区域的信息
                foreach($now_activity as $k=>$v){
                    $city_ids = $v['city_ids']?explode(',',$v['city_ids']):[];
                    if($v['city_id']!=0&&!array_intersect($city_ids,$chirldren_ids)){
                        unset($now_activity[$k]);
                    }
                }
                $now_activity = array_values($now_activity);
                $now_activity = $now_activity?$now_activity[0]:[];
            }else {
                $now_activity = D('Plat_buy_active_group')->where(array('begin_time' => array('lt', $_SERVER['REQUEST_TIME']), 'end_time' => array('gt', $_SERVER['REQUEST_TIME']), 'status' => '1', 'city_id' => $now_city_id))->order('`pigcms_id` ASC')->find();
            }
			if(empty($now_activity)){
				// 查询通用的
				$now_activity = D('Plat_buy_active_group')->where(array('begin_time'=>array('lt',$_SERVER['REQUEST_TIME']),'end_time'=>array('gt',$_SERVER['REQUEST_TIME']),'status'=>'1','city_id'=>0))->order('`pigcms_id` ASC')->find();
			}
			if($now_activity){			
				$database_plat_buy_active = D('Plat_buy_active');

				// 显示数量
				$limit = 3;
				if ($now_activity['column_type']) {
					// 一行两个模式
					$limit = $now_activity['row_num']*2;
				}

				$active_list = $database_plat_buy_active->alias('a')
                ->join('INNER JOIN '.C('DB_PREFIX').'merchant_store AS s ON a.store_id=s.store_id')
                ->field('a.id,a.active_type,a.other_id,a.store_id,a.active_name,a.active_desc,a.active_label,a.active_price,a.old_price,a.active_img,a.active_info')
                ->where(array('a.group_id'=>$now_activity['pigcms_id'],'a.status'=>'1'))
                ->order('a.`sort` DESC,a.`id` DESC')
                ->select();
				if ($active_list) {
					$return_arr['plat_buy_active_info'] = array(
						'have' => true,	//有活动
						'name' => $now_activity['name'],	//超值抢购名称
						'end_time_old' => $now_activity['end_time'],	//结束时间
						'end_time' => $now_activity['end_time'] - time(),	//结束时间戳
						'column_num' => !$now_activity['column_type'] ? '3' : '2',	//超值抢购活动布局形式
						'row_num' => $now_activity['row_num'],	//超值抢购最多展示几行
					);
				}
				$goods_image_class = new goods_image();
				$group_image_class = new group_image();
				foreach($active_list as $active_value){
                    //下架或者不存在的商品不展示
                    if (in_array($active_value['active_type'], ['shop', 'mall'])) {
                        $shop_good = D('Shop_goods')->where(['goods_id' => $active_value['other_id'], 'status' => 1])->find();
                        if (empty($shop_good)) {
                            continue;
                        }
                        $active_value['active_img'] = $shop_good['image'];
                    } else if ($active_value['active_type'] == 'group') {
                        $group_goods = D('Group')->where(['group_id' => $active_value['other_id'], 'status' => 1])->find();
                        if (empty($group_goods)) {
                            continue;
                        }
                    }
                    if ($active_value['store_id']) {
                        $store_data = D('Merchant_store')->where(['store_id' => $active_value['store_id']])->find();
                        if (!$store_data || $store_data['status'] != 1) {
                            continue;
                        }
                    }

                    if($active_value['active_type'] == 'mall' || $active_value['active_type'] == 'shop'){
						$tmp_pic_arr = explode(';', $active_value['active_img']);
						if('http' === substr($tmp_pic_arr[0],0,4)){
							$active_img = $tmp_pic_arr[0];
						}else{
							$active_img = $goods_image_class->get_image_by_path($tmp_pic_arr[0], 's');
						}
					}else{
						$tmp_pic_arr = explode(';',$active_value['active_img']);
						$active_img = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
					}
					
					if($active_value['active_type'] == 'mall'){
						$active_value['active_link'] = $this->config['site_url'].'/wap.php?c=Mall&a=detail&goods_id='.$active_value['other_id'];
					} else if ($active_value['active_type'] == 'new_mall' || $active_value['active_type'] == 'newmall') {
                        $active_value['active_link'] = $this->config['site_url'] . D('House_village')->base_url  . 'pages/shopmall_third/commodity_details?goods_id=' . $active_value['other_id'];
                    }else if($active_value['active_type'] == 'shop'){
						// $active_value['active_link'] = $this->config['site_url'].'/wap.php?c=Shop&a=index#good-'.$active_value['store_id'].'-'.$active_value['other_id'];
                        //$active_value['active_link'] = $this->config['site_url'].'/wap.php?c=Shop&a=classic_good&shop_id='.$active_value['store_id'].'&good_id='.$active_value['other_id'];
                        $backgroundColor = D('Merchant_store_shop')->where(['store_id'=>$active_value['store_id']])->getField('shop_fitment_color');
                        $sort_id = D('Shop_goods')->where(['goods_id'=>$active_value['other_id']])->getField('sort_id');
                        $active_value['active_link'] = $this->config['site_url'].'/packapp/plat/pages/shop_new/shopDetail/shopDetail?store_id='.$active_value['store_id'].'&backgroundColor='.($backgroundColor?:'113533').'&frontColor=ffffff&sort_id='.$sort_id.'&goods_id='.$active_value['other_id'];
					}else if($active_value['active_type'] == 'group'){
						$active_value['active_link'] = $this->config['site_url'].'/wap.php?c=Group&a=detail&group_id='.$active_value['other_id'];
					}else if($active_value['active_type'] == 'newmall'){
                        $active_value['active_link'] =get_base_url('pages/shopmall_third/commodity_details?goods_id=' . $active_value['other_id']);
                    }
					
                    $active_value['active_img'] = replace_file_domain($active_img);
                    isset($active_value['active_price']) && $active_value['active_price'] = getFormatNumber($active_value['active_price']);
                    isset($active_value['old_price']) && $active_value['old_price'] = getFormatNumber($active_value['old_price']);
					$return_arr['plat_buy_active_info']['active_list'][] = $active_value;

                    if(count($return_arr['plat_buy_active_info']['active_list']) == $limit){
                        break;
                    }
				}

				if(empty($return_arr['plat_buy_active_info']['active_list'])){
                    $return_arr['plat_buy_active_info']['have'] = false;
                }
				$cache_time = 300;
			}else{
				$cache_time = 60;
			}
			S($plat_buy_active_info_key,$return_arr['plat_buy_active_info'],$cache_time);
		}else{
			$return_arr['plat_buy_active_info']['end_time'] = $return_arr['plat_buy_active_info']['end_time_old'] - time();
		}
		
		//五图广告位
		$plat_five_adver_info_key = 'plat_five_adver/info_'.$now_city_id;
		$return_arr['plat_five_adver_info'] = [];// S($plat_five_adver_info_key);
		if(empty($return_arr['plat_five_adver_info'])){
		    if(C('customization.open_view_multi_city')==1){
		        //开启多城市配置
                $plat_five_adver_list = M()->query("SELECT g.city_id,g.city_ids,g.name AS group_name,g.from AS group_from,g.pic AS group_pic,g.link AS group_link,g.pic_link AS group_pic_link,a.* FROM ".C('DB_PREFIX')."plat_five_adver as a INNER JOIN (SELECT * FROM `".C('DB_PREFIX')."plat_five_adver_group` WHERE `status`='1' AND `city_id`!=0 ORDER BY `sort` DESC, `pigcms_id` DESC) AS g ON a.group_id=g.pigcms_id WHERE a.`status` = 1 ORDER BY g.sort desc,a.sort desc");
                //获取当前城市下面区县
                $chirldren_arr = D('Area')->field('area_id')->where('is_open = 1 and area_pid = '.$now_city_id)->select();
                $chirldren_ids = [];
                foreach ($chirldren_arr as $val){
                    $chirldren_ids[] = $val['area_id'];
                }
                $chirldren_ids[] = $now_city_id;
                //只查看属于本区域的信息
                foreach($plat_five_adver_list as $k=>$v){
                    $city_ids = $v['city_ids']?explode(',',$v['city_ids']):[];
                    if($v['city_id']!=0&&!array_intersect($city_ids,$chirldren_ids)){
                        unset($plat_five_adver_list[$k]);
                    }
                }
                $plat_five_adver_list = array_values($plat_five_adver_list);
            }else{
                $plat_five_adver_list = M()->query("SELECT g.name AS group_name,g.from AS group_from,g.pic AS group_pic,g.link AS group_link,g.pic_link AS group_pic_link,a.* FROM ".C('DB_PREFIX')."plat_five_adver as a INNER JOIN (SELECT * FROM `".C('DB_PREFIX')."plat_five_adver_group` WHERE `status`='1' AND `city_id`='$now_city_id' ORDER BY `sort` DESC, `pigcms_id` DESC) AS g ON a.group_id=g.pigcms_id WHERE a.`status` = 1 ORDER BY g.sort desc,a.sort desc");
            }
		    //通用
		    $plat_five_adver_list_all = M()->query("SELECT g.name AS group_name,g.from AS group_from,g.pic AS group_pic,g.link AS group_link,g.pic_link AS group_pic_link,g.city_id,g.city_ids,a.* FROM ".C('DB_PREFIX')."plat_five_adver as a INNER JOIN (SELECT * FROM `".C('DB_PREFIX')."plat_five_adver_group` WHERE `status`='1' AND `city_id` in (0) ORDER BY `sort` DESC, `pigcms_id` DESC) AS g ON a.group_id=g.pigcms_id WHERE a.`status` = 1 ORDER BY g.sort desc,a.sort desc");
            $plat_five_adver_list = array_merge($plat_five_adver_list,$plat_five_adver_list_all);
			if($plat_five_adver_list){
				$return_arr['plat_five_adver_info'] = array();
				$goods_image_class = new goods_image();
				$group_image_class = new group_image();
                if(C('customization.open_view_multi_city')==1){
                    //获取当前城市下面区县
                    $chirldren_arr = D('Area')->field('area_id')->where('is_open = 1 and area_pid = '.$now_city_id)->select();
                    $chirldren_ids = [];
                    foreach ($chirldren_arr as $val){
                        $chirldren_ids[] = $val['area_id'];
                    }
                    $chirldren_ids[] = $now_city_id;
                }
				foreach($plat_five_adver_list as $active_value){
					// 最多显示3个分组
					if (count($return_arr['plat_five_adver_info'])>=30 && empty($return_arr['plat_five_adver_info'][$active_value['group_id']])) {
						continue;
					}
					
					$active_value['group_pic'] = file_domain().'/upload/system/'.$active_value['group_pic'];
					$active_value['active_desc'] = htmlspecialchars_decode($active_value['active_desc']);
					if($active_value['group_from'] == '0'){
						if($active_value['active_type'] == 'mall' || $active_value['active_type'] == 'shop'){
							$tmp_pic_arr = explode(';', $active_value['active_img']);
							if('http' === substr($tmp_pic_arr[0],0,4)){
								$active_img = $tmp_pic_arr[0];
							}else{
								$active_img = $goods_image_class->get_image_by_path($tmp_pic_arr[0], 's');
							}
						}else{
							$tmp_pic_arr = explode(';',$active_value['active_img']);
							$active_img = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
						}
						
						if($active_value['active_type'] == 'mall'){
							$active_value['active_link'] = $this->config['site_url'].'/wap.php?c=Mall&a=detail&goods_id='.$active_value['other_id'];
						} else if ($active_value['active_type'] == 'new_mall') {
                            $active_value['active_link'] = $this->config['site_url'] . D('House_village')->base_url .'pages/shopmall_third/commodity_details?goods_id=' . $active_value['other_id'];
                        }else if($active_value['active_type'] == 'shop'){
                            $backgroundColor = D('Merchant_store_shop')->where(['store_id'=>$active_value['store_id']])->getField('shop_fitment_color');
                            $sort_id = D('Shop_goods')->where(['goods_id'=>$active_value['other_id']])->getField('sort_id');
                            $active_value['active_link'] = $this->config['site_url'].'/packapp/plat/pages/shop_new/shopDetail/shopDetail?store_id='.$active_value['store_id'].'&backgroundColor='.($backgroundColor?:'113533').'&frontColor=ffffff&sort_id='.$sort_id.'&goods_id='.$active_value['other_id'];
						}else if($active_value['active_type'] == 'group'){
							$active_value['active_link'] = $this->config['site_url'].'/wap.php?c=Group&a=detail&group_id='.$active_value['other_id'];
						}
						
						$active_value['active_img'] = replace_file_domain($active_img);
					}else{
						$active_value['active_img'] = file_domain().'/upload/system/'.$active_value['active_img'];
						$active_value['active_link'] = htmlspecialchars_decode($active_value['active_link']);
					}
					
					
					if(empty($return_arr['plat_five_adver_info'][$active_value['group_id']])){
						$tmp_value =  array(
							'group_id' => $active_value['group_id'],
							'group_from' => $active_value['group_from'],
							'group_name' => $active_value['group_name'],
							'group_pic' => $active_value['group_pic'],
							'group_pic_link' => htmlspecialchars_decode($active_value['group_pic_link']),
							'group_link' => htmlspecialchars_decode($active_value['group_link']),
							'adver_list' => array()
						);
						$return_arr['plat_five_adver_info'][$active_value['group_id']] = $tmp_value;
					}else{
						if(count($return_arr['plat_five_adver_info'][$active_value['group_id']]['adver_list']) > 4){
							continue;
						}
					}
					
					unset($active_value['group_from'],$active_value['group_name'],$active_value['group_pic'],$active_value['group_link'],$active_value['group_pic_link'],$active_value['active_info'],$active_value['hits'],$active_value['add_time'],$active_value['sort'],$active_value['status'],$active_value['lng'],$active_value['lat']);
					
					if(count($return_arr['plat_five_adver_info'][$active_value['group_id']]['adver_list']) < 4){
						$return_arr['plat_five_adver_info'][$active_value['group_id']]['adver_list'][] = $active_value;
					}
				}
				$return_arr['plat_five_adver_info'] = array_values($return_arr['plat_five_adver_info']);
			}else{
				$plat_five_adver_list = array();
			}
			
			S($plat_five_adver_info_key,$return_arr['plat_five_adver_info'],3600);
		}
		if ($this->DEVICE_ID == 'wxapp'){
			$return_arr['plat_five_adver_info'] = replace_domain($return_arr['plat_five_adver_info']);
		}
		
		//分类信息分类
		$return_arr['zcategorys'] = [];
        if($this->config['wap_home_show_classify']){
            $Zcategorys = D('Classify_category')->field('`cid`,`cat_name`,`cat_pic`')->where(array('subdir' => 1, 'cat_status' => 1))->order('`cat_sort` DESC,`cid` ASC')->select();
            if (!empty($Zcategorys)) {
                $newtmp = [];
                foreach ($Zcategorys as $key=>$vv) {
                    if(!empty($vv['cat_pic'])){
                        $Zcategorys[$key]['cat_pic'] = file_domain() . '/upload/system/' . $vv['cat_pic'];
						$Zcategorys[$key]['cat_url'] = $this->config['site_url'] . '/wap.php?c=Classifynew&a=category&cat_id=' . $vv['cid'];
                    }else{
                        unset($Zcategorys[$key]);
                    }
                }
				$return_arr['zcategorys']  =   array_values($Zcategorys);
				
				$return_arr['classify_name'] = $this->config['classify_name'];		//分类信息别名
				$return_arr['classify_name_desc'] = $this->config['classify_name_desc'];		//分类信息描述
				$return_arr['classify_url'] = $this->config['site_url'] . '/wap.php?c=Classifynew&a=index';		//分类信息链接
            }
        }
		
		//猜你喜欢
		$config_data = D('Config_data')->get_config_data();
		$return_arr['index_guess_like_title'] = $config_data['index_guess_like_title'] ?  L_($config_data['index_guess_like_title']) : L_('猜你喜欢');
		$return_arr['index_guess_like_desc'] = $config_data['index_guess_like_desc'] ? L_($config_data['index_guess_like_desc']) : '';
		//猜你喜欢 新版多业务
		$return_arr['index_guess_like_arr']  = D('Config_data')->get_index_guess_like();
		$this->save_user_city();

		// 小程序流量广告
		if($config_data['wxapp_adver_index_status'] == 1 && $config_data['wxapp_adver_index_unit_id']){
			$return_arr['wxapp_adver'] = [
				'type' => 'banner',
				'id' => $config_data['wxapp_adver_index_unit_id'],

			];
		}
		
		//猜你喜欢 发现好菜分类列表
		/**
		$where = [];
        $where['business_type'] = 'plat';
   		$where['city_id'] = $now_city_id;
		$sort_list = D('Shop_goods')->get_sort_by_guess_like($where);
		**/
		$sort_list = D('Diy_category')->get_normal_category(1,$this->config['now_city']);
		$return_arr['shop_goods_sort_list'] = $sort_list;

		//分享参数
		$return_arr['share_data'] = [
			'h5_img' => $config_data['index_share_wx_img'] ? replace_file_domain($config_data['index_share_wx_img']): ($this->config['wechat_share_img'] ? $this->config['wechat_share_img'] : $this->config['site_logo']),
			'h5_title' => $config_data['index_share_title'] ?: L_('X1首页',$this->config['site_name']),
			'h5_content' => $config_data['index_share_desc'] ?: L_('点击查看更多优惠信息'),
			'wxapp_title' => $config_data['index_share_title'] ?: L_('X1首页',$this->config['site_name']),
			'wxapp_img' => $config_data['index_share_wxapp_img'] ? replace_file_domain($config_data['index_share_wxapp_img']) : '',
		];
		// 清除
		cookie('visit_village_id',0);


        
        //查询语音助手配置
        $return_arr['voice_asr_identify'] = D('Voice_robot_hotword')->getSetInfo();
        
        
		
        if(C('customization.life_tools')){
            $return_arr['recommend_hot_list']=$this->recommend_hot_list();
        }
        //平台首页配置
        //---------------------------------------------------------------------------
        $config = D('Config')
            ->where(['name' => ['IN', [
                'index_background_pic',
                'index_adver_pic',
                'index_adver_pic_jump_url',
                'index_view_pic_status',
                'index_adver_status',
                'index_slider_opacity',
            ]]])
            ->where(['status' => 1])
            ->field('name, value')
            ->select();
        $config = array_column($config, 'value', 'name');

        $config['index_background_pic'] && $config['index_background_pic'] = replace_file_domain($config['index_background_pic']);
        $config['index_adver_pic'] && $config['index_adver_pic'] = replace_file_domain($config['index_adver_pic']);
        $indexConfig = [
            'index_bg_img' => $config['index_background_pic'],
            'app_index_top_adver_big_show' => $config['index_view_pic_status'],// 0 不显示 1 显示 2 不显示占位
            'app_index_single_adver' => [// 轮播图与导航之间的广告
                'img' => $config['index_adver_pic'],
                'url' => $config['index_adver_pic_jump_url'],
                'show' => $config['index_adver_status'],// 0 不显示 1 显示 2 不显示占位
            ],
            'slider_opacity' => $config['index_slider_opacity'],
        ];
        $return_arr = array_merge($return_arr,$indexConfig);
        //---------------------------------------------------------------------------
        
		$this->returnCodeOk($return_arr);
	}
		
	// 猜你喜欢 推荐
	public function plat_recommend(){
		$config_data = D('Config_data')->get_index_guess_like();
		if(!$config_data){
			$this->returnCodeOk(['count'=>0, 'page'=>'1', 'list'=>[]]);
		}
		
		$page = max(1,intval($_POST['page']));
        $lat = isset($_POST['lat']) ? floatval($_POST['lat']) : 0;
        $lng = isset($_POST['lng']) ? floatval($_POST['lng']) : 0;
		$city_id = intval($_POST['now_city']);

        $isLouyuConn = I('isLouyuConn','');
        if ($isLouyuConn){//楼宇过来的，直接使用 经纬度转换获取城市区域ID
            $md5CacheKey = md5($lat.$lng);
            $city_id = S($md5CacheKey);
            if (empty($city_id)){
                $nowLatLngCity = D('Area')->cityMatching($lat,$lng,true);
                if($nowLatLngCity['area_info']['city_id']){
                    $city_id = $nowLatLngCity['area_info']['city_id'];
                    $city_id && S($md5CacheKey,$city_id,7200);
                }
            }
        }
        //now_city可能是区,兼容一下吧
        $now_city = D('Area')->where(['area_id' => $city_id])->find();
        if ($now_city) {
            if ($now_city['area_type'] == 3) {
                $where['city_id'] = $now_city['area_pid'];
                $where['area_id'] = $now_city['area_id'];
            } else if ($now_city['area_type'] == 2) {
                $where['city_id'] = $now_city['area_id'];
            }
        }

		$_POST['sort_id'] && $where['sort_id'] = $_POST['sort_id'];
		$_POST['idstr'] && $where['id'] = array('not in',trim($_POST['idstr'],','));
		($_POST['re_type'] && $_POST['re_type']!='all') && $where['re_type'] = trim($_POST['re_type']);

        $uid = $this->_uid ?: 0;
        $where['store_status'] = 1;
		$list = D('Plat_recommend')->get_limit_list_page_wap($where,$lat,$lng,$page,10, $uid);

        //当前用户会员等级
        $shopMod = new Merchant_store_shopModel();
        foreach ($list['list'] as $k => $v) {
        	$list['list'][$k]['old_age_pension'] = '';
        	if(strpos($v['business_label'], '团') !== false){
	        	$old_age_pension_per = D("Percent_rate")->getOldAgePension($v['mer_id'], 'group', 0, true);
	        	if($old_age_pension_per > 0){
	                $list['list'][$k]['old_age_pension'] = C('config.open_yanglao_and_sande') == 1 ? '消费100可得养老金' . C('config.Currency_symbol') . getFormatNumber($old_age_pension_per) : '';
	            }
	        }
	        elseif($v['re_type'] == 'group'){
	        	$old_age_pension = D("Percent_rate")->getOldAgePension($v['mer_id'], 'group', $v['price']);
	        	if($old_age_pension > 0){
                    $list['list'][$k]['old_age_pension'] = C('config.open_yanglao_and_sande') == 1 ? '可得养老金' . C('config.Currency_symbol') . getFormatNumber($old_age_pension) : '';
	            }
	        }
            $levelDiscount = $shopMod->getVipLevelDiscount($this->_uid, $v['store_id'], 'shop');
            $list['list'][$k]['vip_show'] = $levelDiscount['show_level_price'] ? true : false;
            $list['list'][$k]['vip_tag'] = M('User_level')->where(['level'=>$levelDiscount['level']])->getField('lname');
            $list['list'][$k]['vip_price'] = getFormatNumber($v['price'] * $levelDiscount['discount']);
        }
		$this->returnCodeOk($list);
	}

    /**
     * 城投定制--热门推荐
     */
	public function recommend_hot_list(){
        $out=[];
        $cat_list=M('index_recommend')->where(['is_show'=>1])->order('sort desc')->select();
	    if(!empty($cat_list)){
            foreach ($cat_list as $k=>$v) {
                $ret['title']=$v['title'];
                $ret['column_num']=$v['column_num'];
                $ret['goods_list']=$this->recommend_hot_goods_list(['rg.recommend_id'=>$v['id'],'m.status'=>1],'rg.*','rg.sort desc',$v['goods_type']);
                $out[]=$ret;
            }
        }
	    return $out;
    }

    /**
     * 城投定制--热门推荐商品列表
     */
    public function recommend_hot_goods_list($where,$field,$order,$goods_type){
        $arr = M('index_recommend_goods')->alias('rg')
            ->order($order);
        $url="";
        switch ($goods_type){
            case 'ticket':
                $url=get_base_url('pages/lifeTools/tools/detail?id=');
                array_push($where,['l.status'=>1,'l.is_del'=>0,'lt.status'=>1,'lt.is_del'=>0]);
                $field=$field.',lt.price as goods_price,l.images as image,l.title as goods_name,lt.label,lt.ticket_id,l.tools_id';
                $arr=$arr->field($field)->where($where)->join('INNER JOIN ' . C('DB_PREFIX') .'index_recommend as r on rg.recommend_id = r.id')
                    ->join('INNER JOIN ' . C('DB_PREFIX') . 'life_tools_ticket as lt on lt.ticket_id = rg.goods_id')
                ->join('INNER JOIN ' . C('DB_PREFIX') . 'life_tools as l on l.tools_id = lt.tools_id');
                break;
            case 'sport':
                $url=get_base_url('pages/lifeTools/tools/detail?id=');
                array_push($where,['l.status'=>1,'l.is_del'=>0]);
                $field=$field.',l.money as goods_price,l.images as image,l.title as goods_name,l.label';
                $arr=$arr->field($field)->where($where)->join('INNER JOIN ' . C('DB_PREFIX') .'index_recommend as r on rg.recommend_id = r.id')
                    ->join('INNER JOIN ' . C('DB_PREFIX') . 'life_tools as l on l.tools_id = rg.goods_id');
                break;
            case 'shop':
                $url=get_base_url('pages/shop_new/goodsDetail/goodsDetail?product_id=');
                array_push($where,['l.status'=>1,'l.goods_type'=>0,'ms.status'=>1]);
                $field=$field.',l.price as goods_price,l.image,l.name as goods_name';

                $where['_string'] = 'ms.mer_id NOT IN (' . C('config.set_employee_card_mer_id') . ') OR l.open_employee_card = 0'; //只显示未开启员工专卖商品
//                $where['_string'] = 'ms.mer_id <> ' . C('config.set_employee_card_mer_id') . ' OR l.open_employee_card = 0'; //只显示未开启员工专卖商品
                if ($this->_uid) {
                    $sql1 = "(select ecu.lable_ids from pigcms_employee_card_user as ecu where ecu.uid = " . $this->_uid . " AND ecu.mer_id = l.employee_mer_id AND ecu.status = 1)";
                    $where['_string'] .= " OR (concat(',',l.employee_lables,',') regexp concat(',(',replace(" . $sql1 . ",',','|'),'),'))";
//                    $employee_card_user = D('Employee_card_user')->where(['uid' => $this->_uid, 'mer_id' => C('config.set_employee_card_mer_id'), 'status' => 1])->field('lable_ids')->find();
//                    if (!empty($employee_card_user['lable_ids'])) {
//                        $lable_ids = explode(',', $employee_card_user['lable_ids']);
//                        $where['_string'] .= ' OR (';
//                        foreach ($lable_ids as $lk => $lv) {
//                            if ($lk == 0) {
//                                $where['_string'] .= 'FIND_IN_SET("' . $lv . '", l.employee_lables)';
//                            } else {
//                                $where['_string'] .= ' OR FIND_IN_SET("' . $lv . '", l.employee_lables)';
//                            }
//                        }
//                        $where['_string'] .= ')';
//                    }
                }

                $arr=$arr->field($field)->where($where)->join('INNER JOIN ' . C('DB_PREFIX') .'index_recommend as r on rg.recommend_id = r.id')
                    ->join('INNER JOIN ' . C('DB_PREFIX') . 'shop_goods as l on l.goods_id = rg.goods_id')
                    ->join('INNER JOIN ' . C('DB_PREFIX') . 'merchant_store as ms on ms.store_id = rg.store_id')
                    ->join('INNER JOIN ' . C('DB_PREFIX') . 'merchant_store_shop as msh on msh.store_id = rg.store_id');
                break;
            case 'mall':
                $url=get_base_url('pages/shopmall_third/commodity_details?goods_id=');
                array_push($where,['l.status'=>1,'l.is_del'=>0,'ms.status'=>1]);
                $field=$field.',l.price as goods_price,l.image,l.name as goods_name';
                $arr=$arr->field($field)->where($where)->join('INNER JOIN ' . C('DB_PREFIX') .'index_recommend as r on rg.recommend_id = r.id')
                    ->join('INNER JOIN ' . C('DB_PREFIX') . 'mall_goods as l on l.goods_id = rg.goods_id')
                    ->join('INNER JOIN ' . C('DB_PREFIX') . 'merchant_store as ms on ms.store_id = rg.store_id');
                break;
        }
        $arr=$arr->join('INNER JOIN ' . C('DB_PREFIX') . 'merchant as m on m.mer_id = rg.mer_id')->select();
        if(!empty($arr)){
             foreach ($arr as $k=>$v){
                 $arr[$k]['goods_type']=$goods_type;
                 if(!isset($v['label'])){
                     $arr[$k]['label']="";
                 }else{
                     $arr[$k]['label']=strtr($v['label'],array(" " => ' | '));
                 }
                 $arr[$k]['act_type']="normal";
                 if(isset($v['ticket_id'])){
                     $arr[$k]['url']=$url.$v['tools_id'];
                 }else{
                     $arr[$k]['url']=$url.$v['goods_id'];
                 }

                 if(!empty($v['store_id'])){
                     $arr[$k]['url']=$arr[$k]['url'].'&store_id='.$v['store_id'];
                 }

                 if(!empty($v['image'])){
                     if (strpos($v['image'], ',') !== false) {
                         $arr[$k]['image']=replace_file_domain(explode(",",$v['image'])[0]);
                     }elseif (strpos($v['image'], ';') !== false) {
                         $arr[$k]['image']=replace_file_domain(explode(";",$v['image'])[0]);
                     }else{
                         $arr[$k]['image']=replace_file_domain($v['image']);
                     }
                 }
             }
        }
        return $arr;
    }
	//平台活动
	public function plat_buy_active(){
		$now_city_id = $this->config['now_city'];
        if(C('customization.open_view_multi_city')==1){
            //开启多城市配置
            $now_activity = D('Plat_buy_active_group')->where(array('begin_time' => array('lt', $_SERVER['REQUEST_TIME']), 'end_time' => array('gt', $_SERVER['REQUEST_TIME']), 'status' => '1', 'city_id' => array('neq',0)))->order('`pigcms_id` ASC')->select();
            //获取当前城市下面区县
            $chirldren_ids = D('Area')->where('is_open = 1 and area_pid = '.$now_city_id)->getField('area_id',true);
            $chirldren_ids[] = $now_city_id;
            //只查看属于本区域的信息
            foreach($now_activity as $k=>$v){
                $city_ids = $v['city_ids']?explode(',',$v['city_ids']):[];
                if($v['city_id']!=0&&!array_intersect($city_ids,$chirldren_ids)){
                    unset($now_activity[$k]);
                }
            }
            $now_activity = array_values($now_activity);
            $now_activity = $now_activity?$now_activity[0]:[];
        }else {
            $now_activity = D('Plat_buy_active_group')->where(array('begin_time'=>array('lt',$_SERVER['REQUEST_TIME']),'end_time'=>array('gt',$_SERVER['REQUEST_TIME']),'status'=>'1','city_id'=>$now_city_id))->order('`pigcms_id` ASC')->find();
        }
		
		if(empty($now_activity)){
			$now_activity = D('Plat_buy_active_group')->where(array('begin_time'=>array('lt',$_SERVER['REQUEST_TIME']),'end_time'=>array('gt',$_SERVER['REQUEST_TIME']),'status'=>'1','city_id'=>0))->order('`pigcms_id` ASC')->find();
		}
		if($now_activity){
			$return_arr = array(
				'have' => true,	//有活动
				'name' => $now_activity['name'],	//超值抢购名称
				'end_time' => $now_activity['end_time'] - time(),	//结束时间戳
			);

			$lat = $_POST['lat'];
			$lng = $_POST['lng'];
			if ( $lat && $lng ) {
	        	$order = "`sort` DESC , ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat} * PI() / 180- `lat` * PI()/180)/2),2)+COS({$lat} *PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$lng} *PI()/180- `lng`*PI()/180)/2),2)))*1000) ASC";
	        }else{
	        	$order = '`sort` DESC,`id` DESC';
	        }
			$database_plat_buy_active = D('Plat_buy_active');
			$active_list = $database_plat_buy_active->field(true)->where(array('group_id'=>$now_activity['pigcms_id'],'status'=>'1'))->order($order)->select();
			$goods_image_class = new goods_image();
			$group_image_class = new group_image();
			foreach($active_list as $active_value){
                //下架或者不存在的商品不展示
                if (in_array($active_value['active_type'], ['shop', 'mall'])) {
                    $shop_good = D('Shop_goods')->where(['goods_id' => $active_value['other_id'], 'status' => 1])->find();
                    if (empty($shop_good)) {
                        continue;
                    }
                    $active_value['active_img'] = $shop_good['image'] ?: $active_value['active_img'];
                }else if ($active_value['active_type'] == 'group') {
                    $group_goods = D('Group')->where(['group_id' => $active_value['other_id'], 'status' => 1])->find();
                    if (empty($group_goods)) {
                        continue;
                    }
                }
                if ($active_value['store_id']) {
                    $store_data = D('Merchant_store')->where(['store_id' => $active_value['store_id']])->find();
                    if (!$store_data || $store_data['status'] != 1) {
                        continue;
                    }
                }

				if($lat && $lng){
					$active_value['range'] = getRange(getDistance($active_value['lat'],$active_value['lng'],$lat,$lng));
				}
				$tmp_pic_arr = explode(';', $active_value['active_img']);
				if($active_value['active_type'] == 'mall' || $active_value['active_type'] == 'shop'){
					$tmp_pic_arr = explode(';', $active_value['active_img']);
					if('http' === substr($tmp_pic_arr[0],0,4)){
						$active_img = $tmp_pic_arr[0];
					}else{
						$active_img = $goods_image_class->get_image_by_path($tmp_pic_arr[0], 's');
					}
				}else{
					$tmp_pic_arr = explode(';',$active_value['active_img']);
					$active_img = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				}
				$active_value['active_img'] = thumb($active_img, 170, 170, 'fill');

				if($active_value['active_type'] == 'mall'){
					$active_value['active_link'] = $this->config['site_url'].'/wap.php?c=Mall&a=detail&goods_id='.$active_value['other_id'];
                } else if ($active_value['active_type'] == 'new_mall') {
                    $active_value['active_link'] = $this->config['site_url'] . D('House_village')->base_url  . 'pages/shopmall_third/commodity_details?goods_id=' . $active_value['other_id'];
                }else if($active_value['active_type'] == 'shop'){
					// $active_value['active_link'] = $this->config['site_url'].'/wap.php?c=Shop&a=index#good-'.$active_value['store_id'].'-'.$active_value['other_id'];
					$active_value['active_link'] = $this->config['site_url'].'/wap.php?c=Shop&a=classic_good&shop_id='.$active_value['store_id'].'&good_id='.$active_value['other_id'];
				}else if($active_value['active_type'] == 'group'){
					$active_value['active_link'] = $this->config['site_url'].'/wap.php?c=Group&a=detail&group_id='.$active_value['other_id'];
				}else if($active_value['active_type'] == 'newmall'){
                    $active_value['active_link'] = get_base_url('pages/shopmall_third/commodity_details?goods_id=' . $active_value['other_id']);
                }

                isset($active_value['active_price']) && $active_value['active_price'] = getFormatNumber($active_value['active_price']);
                isset($active_value['old_price']) && $active_value['old_price'] = getFormatNumber($active_value['old_price']);
				
				$return_arr['active_list'][] = $active_value;
			}
			
			$this->returnCodeOk($return_arr);
		}else{
			$this->returnCodeError('暂时没有可参与的活动');
		}
	}
	
	//平台活动上报点击数
	public function plat_buy_active_up(){
		if($_POST['id']){
			$database_plat_buy_active = D('Plat_buy_active');
			$database_plat_buy_active->where(array('id'=>intval($_POST['id'])))->setInc('hits');
		}
		$this->returnCodeOk(array());
	}
	
	
    public function index(){
        $app_type   =   I('app_type');
        $user_long_lat  =   $this->user_long_lat;
		
        $arr['new_group_list']    =   array();
        $arr['user_long_lat']    =   !empty($user_long_lat)?$user_long_lat:null;
		
        //顶部广告 小程序读WAP端
		if($_POST['Device-Id'] != 'wxapp'){
			$head_adver = D('Adver')->get_adver_by_key('app_index_top',5);
		}
		if(empty($head_adver)){
			$head_adver = D('Adver')->get_adver_by_key('wap_index_top',5);
		}
		if(!empty($head_adver)){
			foreach($head_adver as &$head_adver_value){
				unset($head_adver_value['id'],$head_adver_value['bg_color'],$head_adver_value['cat_id'],$head_adver_value['status'],$head_adver_value['last_time'],$head_adver_value['sub_name']);
			}

            // 小程序跳转到别的小程序
            if ($_POST['Device-Id'] == 'wxapp'){
                $head_adver = D('Adver')->replace_url_to_wxapp_page($head_adver);
            }

			// app跳转到别的app
			if ($this->DEVICE_ID != 'wxapp') {
				$head_adver = D('Adver')->replace_url_to_app_page($head_adver, $app_type);
			}
            

			$arr['head_adver'] = $head_adver;
		}else{
			$arr['head_adver'] = array();
		}
		
		//首页附近模块
		if($this->config['wap_around_show_type']){
			$_condition['city_id'] = array('in', array(0, C("config.now_city")));
			$index_around = M('Wap_around')->where($_condition)->order('sort DESC')->limit(3)->select();
			foreach($index_around as &$around_value){
				if($around_value['name'] != 'merchant'){
					$around_value['name'] = L_('附近').$this->config[$around_value['name'].'_alias_name'];
				}else{
					$around_value['name'] = L_('附近商家');
				}
				$around_value['url'] = htmlspecialchars_decode($around_value['url']);
				$around_value['pic'] = $around_value['pic'] ? file_domain().'/upload/wap/'.$around_value['pic'] : '';
			}
		}
		if(empty($index_around)){
			$index_around = array();
		}

        if($this->config['wap_around_show_type'] == 1){
            $arr['index_around'] = $index_around;
        }else{
    		$arr['index_around'] = array();
        }

		//按钮
		$entrance = M('Entrance')->where(array('key'=>'banner_ticket_entrance'))->find();
		$entrance['img'] = empty($entrance['img']) ? '' : replace_file_domain($entrance['img']);
		$entrance['url'] = htmlspecialchars_decode($entrance['url']);
		$arr['entrance'] = $entrance;
		
        //我的社区
        if($this->config['house_open'] == 1){
            $house_alias_name = isset($this->config['house_name']) && $this->config['house_name'] ? $this->config['house_name'] : L_('社区');
            $arr['community']['house_open'] =   $this->config['house_open'];
            $arr['community']['img']        =   $this->config['wechat_share_img'];
            $arr['community']['name']       =    L_('我的X1服务',$house_alias_name);
            $arr['community']['url']        =   htmlspecialchars_decode($this->config['site_url'].'/wap.php?g=Wap&c=House&a=village_list');
        }else{
            $arr['community']   =   (object)array();
        }
        //导航条
        $arr['silder_limit']= $this->config['wap_slider_number'];
        $arr['slider_num'] = 5;
        $tmp_wap_index_slider = D('Slider')->get_slider_by_key('wap_slider',0);
        $wap_index_slider = array();
        foreach($tmp_wap_index_slider as $key=>$value){
            $tmp_i = floor($key/8);
            if(!stristr($value['url'],'Weidian') && (!stristr($value['url'],'Invitation') || $this->DEVICE_ID == 'wxapp') && (!stristr($value['url'],'City_car') || $this->DEVICE_ID == 'wxapp') ){
                $wap_index_slider[$tmp_i][] = $value;
            }
        }
		
        foreach($wap_index_slider as $v){
            foreach($v as $vv){
                $vv['url']  =   htmlspecialchars_decode($vv['url']);
                // 小程序跳转到别的小程序
                if ($this->DEVICE_ID == 'wxapp' &&$vv['wxapp_id']) {
                    $vv['url'] = 'wxapp://'.$vv['wxapp_id'];
                    if ($vv['wxapp_page']) {
                        // 小程序页面
                        $vv['url'] .= '://' . $vv['wxapp_page'];
                    }
                    unset($vv['wxapp_id'],$vv['wxapp_page']);
                }
                $arr['slider'][]  =   $vv; 
            }
        }

		// app跳转到别的app
		if ($this->DEVICE_ID != 'wxapp') {
			$arr['slider'] = D('Adver')->replace_url_to_app_page($arr['slider'], $app_type);
		}

        if(empty($arr['slider'])){
            $arr['slider']  =   array();
        }
       
		// 快报
        $news_info = M('')->field('`n`.`id`,`n`.`title`,`c`.`name`')->table(array(C('DB_PREFIX').'system_news'=>'n',C('DB_PREFIX').'system_news_category'=>'c'))->where("`n`.`status`='1' AND `c`.`id`=`n`.`category_id` AND `c`.`status`='1' AND `n`.`city_id` in (0,".$this->config['now_city'].")")->order('`n`.`sort` DESC,`n`.`id` DESC')->limit(8)->select();
		
        $news_count = count($news_info);

		if($news_count){
			for($i=0;$i<$news_count; $i++){
				$arr['news_list'][]=array('cat_name'=>$news_info[$i]['name'],'title'=>$news_info[$i]['title']);
			}
		}else{
			$arr['news_list']=array();
		}

        //中间广告
        $arr['Adver'] = D('Adver')->get_adver_by_key('wap_index_center',4);
        if(empty($arr['Adver'])){
            $arr['Adver']   =   array();
        }

        // 小程序跳转到别的小程序
        if ($_POST['Device-Id'] == 'wxapp'){
            $arr['Adver'] = D('Adver')->replace_url_to_wxapp_page($arr['Adver']);
        }

		// app跳转到别的app
		if ($this->DEVICE_ID != 'wxapp') {
			$arr['Adver'] = D('Adver')->replace_url_to_app_page($arr['Adver'], $app_type);
		}

        //活动列表
        if($this->config['activity_open']){
            $now_activity = D('Extension_activity')->where(array('begin_time'=>array('lt',$_SERVER['REQUEST_TIME']),'end_time'=>array('gt',$_SERVER['REQUEST_TIME']),'city_id'=>array('in',array(0,C("config.now_city")))))->order('`activity_id` ASC')->find();
            if($now_activity){
                $time = $now_activity['end_time'] - $_SERVER['REQUEST_TIME'];
                $time_array['d'] = isset($time)?floor($time/86400):0;
                $time_array['h'] = isset($time)?floor($time%86400/3600):0;
                $time_array['m'] = isset($time)?floor($time%86400%3600/60):0;
                $time_array['s'] = isset($time)?floor($time%86400%60):0;

                $activity_list = D('')->field('`eac`.`pigcms_id`,`eac`.`name`,`eac`.`title`,`eac`.`pic`,`eac`.`all_count`,`eac`.`part_count`,`eac`.`price`,`eac`.`mer_score`,`eac`.`type`')->table(array(C('DB_PREFIX').'extension_activity_list'=>'eac',C('DB_PREFIX').'merchant'=>'m'))->where("`eac`.activity_term='{$now_activity['activity_id']}' AND `eac`.`status`='1' AND `eac`.`is_finish`='0' AND `eac`.`index_sort`>0 AND `eac`.`mer_id`=`m`.`mer_id` AND `m`.`city_id`='{$this->config['now_city']}'")->order('`eac`.`index_sort` DESC,`eac`.`pigcms_id` DESC')->limit(3)->select();

                if(empty($activity_list)){
                    unset($now_activity);
                }
                $extension_image_class = new extension_image();
                foreach($activity_list as &$activity_value){
                    $activity_value['list_pic'] = $extension_image_class->get_image_by_path(array_shift(explode(';',$activity_value['pic'])),'s');
                    $activity_value['url']   =   htmlspecialchars_decode($this->config['site_url'].U('Wapactivity/detail',array('id'=>$activity_value['pigcms_id'])));
                }
            }
        }
        if(empty($time)){
            $time_array['d'] = 0;
            $time_array['h'] = 0;
            $time_array['m'] = 0;
            $time_array['s'] = 0;
        }
        $arr['sActivity']   =   isset($activity_list)?$activity_list:array();
        $arr['activity_time']   =   isset($time_array)?$time_array:array();
        $arr['activity']  =   isset($now_activity)?$now_activity:array();
        //分类信息分类
        if($this->config['wap_home_show_classify']){
            $database_Classify_category = D('Classify_category');
            $Zcategorys = $database_Classify_category->field('`cid`,`cat_name`,`cat_pic`')->where(array('subdir' => 1, 'cat_status' => 1))->order('`cat_sort` DESC,`cid` ASC')->select();
            if (!empty($Zcategorys)) {
                $newtmp = array();
                foreach ($Zcategorys as $key=>$vv) {
                    if(!empty($vv['cat_pic'])){
                        $Zcategorys[$key]['cat_pic'] = file_domain().'/upload/system/'.$vv['cat_pic'];
                        $Zcategorys[$key]['cat_url']  =  str_replace('appapi.php','wap.php',htmlspecialchars_decode($this->config['site_url'].U('Classify/index',array('cid'=>$vv['cid'],'ctname'=>urlencode($vv['cat_name']))).'#ct_item_'.$vv['cid']));
                    }else{
                        unset($Zcategorys[$key]);
                    }
                }
            }
        }
        
        //请求红包
        $arr['redpack'] = false;
        if($this->_uid){
            $result = D('LotteryMarketing')->check_redpack($this->_uid);
            if(!$result['error_code']){
                $arr['redpack'] = true;
            }
        }

        //wap首页首页中间轮播宽图（头部）
        $wap_index_center_slider_adver_header = D('Adver')->get_adver_by_key('wap_index_center_slider_adver_header',3);
        $arr['wap_index_center_slider_adver_header']  = $wap_index_center_slider_adver_header ? $wap_index_center_slider_adver_header : array();

        // 小程序跳转到别的小程序
		if ($this->DEVICE_ID == 'wxapp') {
			$arr['wap_index_center_slider_adver_header'] = D('Adver')->replace_url_to_wxapp_page($arr['wap_index_center_slider_adver_header']);
		}
		// app跳转到别的app
		if ($this->DEVICE_ID != 'wxapp') {
			$arr['wap_index_center_slider_adver_header'] = D('Adver')->replace_url_to_app_page($arr['wap_index_center_slider_adver_header'], $app_type);
		}

        //wap首页首页中间轮播宽图（中部）
        $wap_index_center_slider_adver_middle = D('Adver')->get_adver_by_key('wap_index_center_slider_adver_middle',3);
        $arr['wap_index_center_slider_adver_middle']  = $wap_index_center_slider_adver_middle ? $wap_index_center_slider_adver_middle : array();

        // 小程序跳转到别的小程序
		if ($this->DEVICE_ID == 'wxapp') {
			$arr['wap_index_center_slider_adver_middle'] = D('Adver')->replace_url_to_wxapp_page($arr['wap_index_center_slider_adver_middle']);
		}
		// app跳转到别的app
		if ($this->DEVICE_ID != 'wxapp') {
			$arr['wap_index_center_slider_adver_middle'] = D('Adver')->replace_url_to_app_page($arr['wap_index_center_slider_adver_middle'], $app_type);
		}

        //wap首页首页中间轮播宽图（尾部）
        $wap_index_center_slider_adver_footer = D('Adver')->get_adver_by_key('wap_index_center_slider_adver_footer',3);
        $arr['wap_index_center_slider_adver_footer']  = $wap_index_center_slider_adver_footer ? $wap_index_center_slider_adver_footer : array();

        // 小程序跳转到别的小程序
		if ($this->DEVICE_ID == 'wxapp') {
			$arr['wap_index_center_slider_adver_footer'] = D('Adver')->replace_url_to_wxapp_page($arr['wap_index_center_slider_adver_footer']);
		}
		// app跳转到别的app
		if ($this->DEVICE_ID != 'wxapp') {
			$arr['wap_index_center_slider_adver_footer'] = D('Adver')->replace_url_to_app_page($arr['wap_index_center_slider_adver_footer'], $app_type);
		}

        if(empty($Zcategorys)){
            $arr['zcategorys']  =   array();
        }else{
            $arr['zcategorys']  =   array_values($Zcategorys);
        }
        
        $arr['location_mode'] = $this->config['location_mode'] ? $this->config['location_mode'] : 0; // 0-定位到城市区县，1-定位到商场
        
		$this->save_user_city();

        $this->returnCode(0,$arr);
    }
    //  猜你喜欢
    public  function    like(){
        $new_group_list = D('Group')->get_group_list('index_sort',8,true);
        $user_long_lat  =   $this->user_long_lat;
        //判断是否微信浏览器，
        if($new_group_list && $user_long_lat){
            $group_store_database = D('Group_store');
            $rangeSort = array();
            foreach($new_group_list as &$storeGroupValue){
                $tmpStoreList = $group_store_database->get_storelist_by_groupId($storeGroupValue['group_id']);
                if($tmpStoreList){
                    foreach($tmpStoreList as &$tmpStore){
                        $tmpStore['Srange'] = getDistance($user_long_lat['lat'],$user_long_lat['long'],$tmpStore['lat'],$tmpStore['long']);
                        $tmpStore['range'] = getRange($tmpStore['Srange'],false);
                        $rangeSort[] = $tmpStore['Srange'];
                    }
                    array_multisort($rangeSort, SORT_ASC, $tmpStoreList);
                    $storeGroupValue['range'] = $tmpStoreList[0]['range'];
                }
                $storeGroupValue['url'] =   $this->config['site_url'].$storeGroupValue['url'];
            }
        }else{
            foreach($new_group_list as &$storeGroupValue){
                $storeGroupValue['url'] =   $this->config['site_url'].$storeGroupValue['url'];
            }
        }
        $new_group_list    =   isset($new_group_list)?$new_group_list:array();
        foreach($new_group_list as $k=>$v){
            $arr[]    =   array(
                'group_id'      =>  $v['group_id'],        //商品ID
                'group_name'    =>  $v['group_name'],      //标题名
                'merchant_name' =>  $v['merchant_name'],   //商店名
                'price'         =>  rtrim(rtrim(number_format($v['price'],2,'.',''),'0'),'.'),   //商品价格
                'wx_cheap'      =>  $v['wx_cheap'],        //微信立减
                'sale_count'    =>  $v['sale_count']+$v['virtual_num'],      //已售数量
                'old_price'    	=>  $v['old_price'],      //已售数量
                'list_pic'      =>  $v['list_pic'],        //图片url
                'url'           =>  $v['url'],             //跳转url
                'range'         =>  isset($v['range'])?$v['range']:'',           //离我距离
                'pin_num'         =>  $v['pin_num'],           //离我距离
            );
            if($v['tuan_type'] == 2){
				$arr[$k]['s_name']	=	$v['s_name'];
            }else{
				$arr[$k]['s_name']	=	$v['name'];
            }
        }
        $arr	=	isset($arr)?$arr:array();
        $this->returnCode(0,$arr);
    }
    //顶部广告
    public  function    head_adver(){
        $head_adver         = D('Adver')->get_adver_by_key('wap_index_top',5);
        $arr['head_adver']    =   isset($head_adver)?$head_adver:array();
        $this->returnCode(0,$arr);
    }
    //我的社区
    public  function    community(){
        if($this->config['house_open'] == 1){
            $arr['community']['house_open'] =   $this->config['house_open'];
            $arr['community']['img']        =   $this->config['wechat_share_img'];
            $arr['community']['name']       =   L_('我的小区服务');
            $arr['community']['url']        =   $this->config['site_url'].'/wap.php?g=Wap&c=House&a=village_list';
        }else{
            $arr['community']   =   null;
        }
        $this->returnCode(0,$arr);
    }
    //导航条
    public  function    slider(){
        $tmp_wap_index_slider = D('Slider')->get_slider_by_key('wap_slider',0);
        $wap_index_slider = array();
        foreach($tmp_wap_index_slider as $key=>$value){
            $tmp_i = floor($key/8);
            if(!stristr($value['url'],'Weidian') && !stristr($value['url'],'Invitation')){
                $wap_index_slider[$tmp_i][] = $value;
            }
        }
        foreach($wap_index_slider as $v){
            foreach($v as $vv){
                $vv['url']  =   htmlspecialchars_decode($vv['url']);
                $arr['slider'][]  =   $vv;
            }
        }
        if(empty($arr['slider'])){
            $arr['slider']  =   array();
        }
        $this->returnCode(0,$arr);
    }
    //中间广告
    public  function    adver(){
        $arr['Adver'] = D('Adver')->get_adver_by_key('wap_index_center',4);
        if(empty($arr['Adver'])){
            $arr['Adver']   =   array();
        }
        $this->returnCode(0,$arr);
    }
    //活动列表
    public  function   activity(){
        if($this->config['activity_open']){
            $now_activity = D('Extension_activity')->where(array('begin_time'=>array('lt',$_SERVER['REQUEST_TIME']),'end_time'=>array('gt',$_SERVER['REQUEST_TIME']),'city_id'=>array('in',array(0,C("config.now_city")))))->order('`activity_id` ASC')->find();
            if($now_activity){
                $time = $now_activity['end_time'] - $_SERVER['REQUEST_TIME'];
                $time_array['d'] = floor($time/86400);
                $time_array['h'] = floor($time%86400/3600);
                $time_array['m'] = floor($time%86400%3600/60);
                $time_array['s'] = floor($time%86400%60);

                $activity_list = D('')->field('`eac`.`pigcms_id`,`eac`.`name`,`eac`.`title`,`eac`.`pic`,`eac`.`all_count`,`eac`.`part_count`,`eac`.`price`,`eac`.`mer_score`,`eac`.`type`')->table(array(C('DB_PREFIX').'extension_activity_list'=>'eac',C('DB_PREFIX').'merchant'=>'m'))->where("`eac`.activity_term='{$now_activity['activity_id']}' AND `eac`.`status`='1' AND `eac`.`is_finish`='0' AND `eac`.`index_sort`>0 AND `m`.`city_id`='{$this->config['now_city']}'")->order('`eac`.`index_sort` DESC,`eac`.`pigcms_id` DESC')->limit(3)->select();

                if(empty($activity_list)){
                    unset($now_activity);
                }
                $extension_image_class = new extension_image();
                foreach($activity_list as &$activity_value){
                    $activity_value['list_pic'] = $extension_image_class->get_image_by_path(array_shift(explode(';',$activity_value['pic'])),'s');
                    $activity_value['url']   =   htmlspecialchars_decode($this->config['site_url'].U('Wapactivity/detail',array('id'=>$activity_value['pigcms_id'])));
                }
            }
        }
        $arr['sActivity']   =   isset($activity_list)?$activity_list:array();
        $arr['activity_time']   =   isset($time_array)?$time_array:0;
        $arr['activity']  =   isset($now_activity)?$now_activity:null;
        $this->returnCode(0,$arr);
    }
    //分类信息分类
    public  function    zcategorys(){
        if($this->config['wap_home_show_classify']){
            $database_Classify_category = D('Classify_category');
            $Zcategorys = $database_Classify_category->field('`cid`,`cat_name`,`cat_pic`')->where(array('subdir' => 1, 'cat_status' => 1))->order('`cat_sort` DESC,`cid` ASC')->select();
            if (!empty($Zcategorys)) {
                $newtmp = array();
                foreach ($Zcategorys as $key=>$vv) {
                    if(!empty($vv['cat_pic'])){
                        $Zcategorys[$key]['cat_pic'] = file_domain().'/upload/system/'.$vv['cat_pic'];
                        $Zcategorys[$key]['cat_url']  =   str_replace('appapi.php','wap.php',htmlspecialchars_decode($this->config['site_url'].U('Classify/Subdirectory',array('cid'=>$vv['cid'],'ctname'=>urlencode($vv['cat_name'])))));
                    }else{
                        unset($Zcategorys[$key]);
                    }
                }
            }
        }
        if(empty($Zcategorys)){
            $arr['zcategorys']  =   array();
        }else{
            $arr['zcategorys']  =   array_values($Zcategorys);
        }
        $this->returnCode(0,$arr);
    }

    public function rand_coupon(){
        if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
        }else{
            $this->returnCode('20046009');
        }

        if($this->config['open_rand_send']){
			$where = array('time'=>$_SERVER['REQUEST_TIME'],'uid'=>$now_user['uid'],'from'=>'app','city_id'=>$now_user['city_id']);
			isset($_POST['cate_name']) && $where['cate_name'] = safeValue($_POST['cate_name']);
            $coupon_html = D('System_coupon')->rand_send_coupon_get($where);
            if(empty($coupon_html)){
                $this->returnCode(0,array(
                    'coupon'=>array(),
                    'coupon_count'=>0,
                ));

            }else{
                $this->returnCode(0,$coupon_html);
            }

        }else{
            $this->returnCode(0,array(
                'coupon'=>array(),
                'coupon_count'=>0,
            ));
        }

    }

    public function get_city_id_by_name(){
        if(!empty($_POST['lat']) && !empty($_POST['lng'])) {
            $lat = $_POST['lat'];
            $lng = $_POST['lng'];
            $url = 'http://api.map.baidu.com/geocoder/v2/?output=json&ak=' . $this->config['baidu_map_ak'] . '&location=' . $lat . ',' . $lng;
            import('ORG.Net.Http');
            $http = new Http();
            $result = $http->curlGet($url);
            $result = json_decode($result, true);
            $city_name = $result['result']['addressComponent']['city'];
        }else if(!empty($_POST['city_name'])){
            $city_name = $_POST['city_name'];
        }else if(!empty($_POST['now_city'])){
            $city_name = $_POST['now_city'];
        }else{
            $this->returnCode('40000029');
        }

        //优先读缓存
        $old_city_name = $city_name;
		$now_city = S('get_city_id_by_name' . $old_city_name);
		if($now_city){
			$this->returnCode(0,array('city_id'=>$now_city['area_id'],'city_name'=>$now_city['area_name']));
		}
		
		$full_city_name = trim($city_name);
        $long = strlen($city_name);
        if ($long >= 7) {
            $city_name = str_replace(L_('省'), '', $city_name);
            $city_name = str_replace(L_('市'), '', $city_name);
            $city_name = str_replace(L_('地区'), '', $city_name);
            $city_name = str_replace(L_('特别行政区'), '', $city_name);
            $city_name = str_replace(L_('特別行政區'), '', $city_name);
            $city_name = str_replace(L_('蒙古自治州'), '', $city_name);
            $city_name = str_replace(L_('回族自治州'), '', $city_name);
            $city_name = str_replace(L_('柯尔克孜自治州'), '', $city_name);
            $city_name = str_replace(L_('哈萨克自治州'), '', $city_name);
            $city_name = str_replace(L_('土家族苗族自治州'), '', $city_name);
            $city_name = str_replace(L_('藏族羌族自治州'), '', $city_name);
            $city_name = str_replace(L_('傣族自治州'), '', $city_name);
            $city_name = str_replace(L_('布依族苗族自治州'), '', $city_name);
            $city_name = str_replace(L_('苗族侗族自治州'), '', $city_name);
            $city_name = str_replace(L_('壮族苗族自治州'), '', $city_name);
            $city_name = str_replace(L_('澳门'), L_('澳門'), $city_name);
            $city_name = str_replace(L_('朝鲜族自治州'), '', $city_name);
			$city_name = str_replace(L_('哈尼族彝族自治州'),'',$city_name);
			$city_name = str_replace(L_('傣族景颇族自治州'),'',$city_name);
			$city_name = str_replace(L_('藏族自治州'),'',$city_name);
			$city_name = str_replace(L_('彝族自治州'),'',$city_name);
			$city_name = str_replace(L_('白族自治州'),'',$city_name);
			$city_name = str_replace(L_('傈僳族自治州'),'',$city_name);
        }
		$now_city_where['area_name'] = [['eq', $full_city_name],['eq',  $city_name], 'OR'];
        $now_city = M('Area')->where($now_city_where)->find();

        S('get_city_id_by_name' . $old_city_name, $now_city);
        
        $this->returnCode(0,array('city_id'=>$now_city['area_id'],'city_name'=>$now_city['area_name']));
    }

    public function wxapp_openid()
    {
        if ($this->config['close_system_spread'] != 1) {
            $spread_openid = $_POST['spread_openid'];
            $now_user_openid = $_POST['now_user_openid'];
            $now_user = D('User')->get_user($now_user_openid, 'wxapp_openid');
            $spread_user = D('User')->get_user($spread_openid, 'wxapp_openid');
            //小程序授权已经创建用户
            
            if ($this->config['open_user_spread'] && $spread_openid && $now_user_openid && $now_user_openid != $spread_openid && ($now_user['openid'] == '' || !M('Merchant_spread')->where(array('openid' => $now_user['openid'], 'mer_id' => array('gt', 0)))->find())) {
                if (!empty($spread_user)) {
                    if (empty($now_user['openid']) || $this->config['user_spread_replace']) {
                        D('User_spread')->where(array('openid' => $now_user_openid))->delete();
                        $now_user['openid'] && D('User_spread')->where(array('openid' => $now_user['openid']))->delete();
                        // D('User_spread')->data(array('spread_openid' => $spread_openid, 'spread_uid' => $spread_user['uid'], 'openid' => $now_user_openid, 'is_wxapp' => 1))->add();
                        D('User_spread')->data(array('spread_uid' => $spread_user['uid'], 'uid' => $now_user['uid'], 'add_time' => time(), 'is_wxapp' => 1))->add();
                        D('User_spread')->send_spread_msg($now_user_openid, $spread_openid);
                    }
                }
            }
        }
    }

    /**
     * 弹窗优惠券
     * @author 张涛
     * @date 2020/08/04
     */
    public function sendCoupon()
    {
        $uid = 0;
        $this->_uid && $uid = $this->_uid;
        $page = I('post.page','','trim,addslashes');
        $city_id = I('now_city',0,'intval');
        $nowActivity = (new SystemSendCouponActivityModel)->getNowActivity($uid,$page,$city_id,1);
        if (empty($nowActivity)) {
            $this->returnCode(1, array(), L_('没有活动'));
        } else {
            $this->returnCode(0, $nowActivity);
        }
    }

    /**
     * 领取优惠券
     * @author 张涛
     * @date 2020/08/04
     */
    public function getCoupon()
    {
        $id = $_POST['activity_id'] ?? 0;
        $couponId = $_POST['coupon_id'] ?? 0;
        $couponType = $_POST['coupon_type'];
        if (empty($this->_uid)) {
            $this->returnCode(1, array(), L_('用户未登录'));
        }
        $uid = $this->_uid;
        $isNew = D('User')->check_new($uid, 'all');
        $today = date('Y-m-d');
        $where = [
            'is_del' => 0,
            'status' => 1,
            'id' => $id,
            'start_date' => ['elt', $today],
            'end_date' => ['egt', $today],
        ];
        $activity = M('System_send_coupon_activity')->where($where)->find();
        if (empty($activity)) {
            $this->returnCode(0, ['tip' => '活动不存在或者已删除']);
        }
        if (!$isNew && $activity['send_type'] == 1) {
            $this->returnCode(0, ['tip' => '抱歉！您不符合当前新客领取优惠券条件']);
        }
        $where = [['aid' => $id]];
        if($activity['get_way'] == 1){
            if(empty($couponId) || empty($couponType)){
                $this->returnCode(0, ['tip' => '抱歉！当前优惠券需手动领取！']);
            }
            $where[] = ['coupon_id' => $couponId];
            $where[] = ['coupon_type' => $couponType];
        }
        $setting = M('System_send_coupon_activity_setting')->where($where)->select();
       
        $merMod = D('Card_new_coupon');
        $sysMod = D('System_coupon');
        $logMod = M('System_send_coupon_activity_log');
        $logs = [];
        $hasGet = true;
        foreach ($setting as $v) {
            $isget = $logMod->where(['uid' => $uid, 'aid' => $id, 'coupon_id' => $v['coupon_id'], 'coupon_type' => $v['coupon_type']])->find();
            if ($isget) {
                continue;
            }

            $hasGet = false;
            if ($v['coupon_type'] == 'mer') {
                //商家优惠券
                $resp = $merMod->had_pull($v['coupon_id'], $uid, '', false, false, false, 0, 0, true);
            } else {
                //平台优惠券
                $resp = $sysMod->had_pull($v['coupon_id'], $uid, '', 1);
            }
            $hadpullId = 0;
            if ($resp['error_code'] == 0 && $resp['hadpull_id']) {
                $hadpullId = is_array($resp['hadpull_id']) ? $resp['hadpull_id'][0] : $resp['hadpull_id'];
                M('System_send_coupon_activity_setting')->where(['id' => $v['id']])->setInc('had_pull');
            }
            $logs[] = [
                'uid' => $uid,
                'aid' => $id,
                'coupon_id' => $v['coupon_id'],
                'coupon_type' => $v['coupon_type'],
                'hadpull_id' => $hadpullId,
                'resp' => json_encode($resp, JSON_UNESCAPED_UNICODE)
            ];
        }
        $logs && $logMod->addAll($logs);
        if ($hasGet) {
            $this->returnCode(0, ['tip' => '抱歉！您已领取' . ($activity['send_type'] == 1 ? '新客' : '老客') . '优惠券', 'error' => 1]);
        } else {
            $this->returnCode(0, ['tip' => '恭喜您，领取成功']);
        }
    }

    /**
     * 根据商家优惠券获取店铺列表
     * @author 张涛
     * @date 2020/08/03
     */
    public function getStoreByCouponId()
    {
        $couponId = isset($_POST['coupon_id']) ? $_POST['coupon_id'] : 0;
        $couponType = isset($_POST['coupon_type']) ? $_POST['coupon_type'] : '';
        $page = isset($_POST['page']) ? $_POST['page'] : 1;
        $pageSize = 20;

        if ($couponType == 'system') { //平台优惠券
            $store = M('System_coupon_store')->field('store_id')->where(['coupon_id' => $couponId])->select();

            $store_ids = [];
            if (!empty($store)) { // 指定店铺
                foreach ($store as $k => $value) {
                    $store_ids[] = $value['store_id'];
                }
                $storeIds = implode(',', $store_ids);
                $storeInfos = D('Merchant_store')->alias('store')
                    ->join('left join ' . C('DB_PREFIX') . 'merchant_store_shop AS shop ON shop.store_id = store.store_id')
                    ->where(['store.store_id' => ['IN', $storeIds], 'store.status' => 1])
                    ->page($page, $pageSize)
                    ->field('store.*,IFNULL(shop.score_mean,5) AS score_mean')
                    ->select();
            } else { //全部店铺
                $storeInfos = D('Merchant_store')->alias('store')
                    ->join('left join ' . C('DB_PREFIX') . 'merchant_store_shop AS shop ON shop.store_id = store.store_id')
                    ->where(['store.status' => 1, 'shop.is_close' => 0])
                    ->page($page, $pageSize)
                    ->field('store.*,shop.score_mean')
                    ->select();
            }
        } else { //商家优惠券

            if(stripos($couponId, ',')) {  //多个优惠券
                $couponId_arr = explode(',', $couponId);

                $storeIds_str = '';
                foreach ($couponId_arr as $vv) {
                    $this_store = D('Card_new_coupon')->where(['coupon_id' => $vv, 'status' => ['in', [1,3]], 'allow_im_get' => 0])->getField('store_id');
                    $storeIds_str .= $this_store.',';
                }

                $storeIds_combine = trim($storeIds_str, ',');
                $storeIds = trim(implode(',', array_unique(explode(',', $storeIds_combine))), ',');//组装store_id
            } else {
                $storeIds = D('Card_new_coupon')->where(['coupon_id' => $couponId, 'status' => ['in', [1,3]], 'allow_im_get' => 0])->getField('store_id');
            }

            if (empty($storeIds)) {
                $this->returnCode(1, array(), L_('优惠券不存在'));
            }

            $storeInfos = D('Merchant_store')->alias('store')
                ->join('left join ' . C('DB_PREFIX') . 'merchant_store_shop AS shop ON shop.store_id = store.store_id')
                ->where(['store.store_id' => ['IN', $storeIds], 'store.status' => 1])
                ->field('store.*,shop.score_mean')
                ->select();
        }

        $lists = [];
        foreach ($storeInfos as $v) {
            $data = [
                'store_id' => $v['store_id'],
                'store_name' => $v['name'],
                'pic' => replace_file_domain($v['logo']),
                'star' => $v['score_mean'],
            ];
            //业务标签
            $businessLabelArr = [];
            if ($v['have_group']) {
                $businessLabelArr[] = msubstr(C('config.group_alias_name'), 0, 1, false);
            }
            if ($v['have_shop']) {
                $businessLabelArr[] = msubstr(C('config.shop_alias_name'), 0, 1, false);
            }
            if ($v['have_meal']) {
                $businessLabelArr[] = msubstr(C('config.meal_alias_name'), 0, 1, false);
            }
            $data['business_label'] = implode(',', $businessLabelArr);
            $lists[] = $data;
        }
        $this->returnCode(0, $lists);
    }


    /**
     * 私域流量活动
     * @author: 张涛
     * @date: 2021/03/17
     */
    public function crmActivity()
    {
        $page = $_POST['page'] ?? 1;
        $nowCity = $_POST['now_city'] ?? 0;
        $storeId = $_POST['store_id'] ?? 0;
        $rs = ['is_show' => false, 'hover_pic' => '', 'alert_pic' => '', 'jump_url' => ''];

        $storeInfo = D('Merchant_store')->where(['store_id' => $storeId])->find();
        $nowCity = $storeInfo['area_id'] ? $storeInfo['area_id'] : ($storeInfo['city_id'] ?: $storeInfo['province_id']);
        if (!empty($storeInfo)) {
            $where = [
                'act.is_del' => 0,
                'act.status' => 1,
                'act.show_page&' . $page,
                '_string' => '((act.is_point_store = 0 OR store.store_id = ' . $storeId . ') AND (act.is_point_area = 0 OR area.area_id = ' . $nowCity . '))'
            ];

            $activity = M('Private_activity')->alias('act')
                ->join(C('DB_PREFIX') . 'private_activity_area area ON act.id = area.aid')
                ->join(C('DB_PREFIX') . 'private_activity_store store ON act.id = store.aid')
                ->field('act.*')
                ->where($where)
                ->order('act.id DESC')
                ->find();
            if ($activity) {
                $rs['is_show'] = true;
                $rs['hover_pic'] = strpos($activity['hover_pic'], '/static/images') === 0 ? C('config.site_url') . $activity['hover_pic'] : replace_file_domain($activity['hover_pic']);
                $rs['alert_pic'] = replace_file_domain($activity['alert_pic']);
                $rs['jump_url'] = C('config.site_url') . '/wap.php?c=Help&a=showPoster&id=' . $activity['id'];
            }
        }
        $this->returnCode(0, $rs);
    }

    //本地测试计划任务-团购商品过期自动处理
    public function testGroupPlan(){
        import('@.ORG.plan.plan_group_order_out_auto_operate');
        $objExcel = new plan_group_order_out_auto_operate();
        $objExcel->runTask();exit();
    }

}
?>