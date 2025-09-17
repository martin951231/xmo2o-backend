<?php
//新版团购
class GroupnewAction extends BaseAction{
	public function index(){
//        redirect($this->config['site_url'] . D('House_village')->base_url . 'pages/group/store_index');
		//所有一级分类
		$all_category_list = D('Group_category')->get_category(false);
		$this->assign('all_category_list',$all_category_list);
		$d_group = D('Group');
        
        $_POST['group_cate'] = 'normal';
		// 获取人气推荐团购
        $pop_list = $d_group->group_list_index('pop', 6);
        $pop_list['num'] = count($pop_list['group_list']);
        $this->assign('pop_list',$pop_list);
        // 48H人气TOP10
        $hot_list = $d_group->group_list_index('sales', 10);
        $this->assign('hot_list',$hot_list);
        // 近期新品
        $new_list = $d_group->group_list_index('new_time', 4);
        $this->assign('new_list',$new_list);
        if ($all_category_list) {
            $all_category_group = array();
            $all_key = 1;
            foreach ($all_category_list as $key => $val) {
                if ($val) {
                    $info = array(
                        'cat_name' => $val['cat_name'],
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'cat_des' => $val['cat_des']
                    );
                    if ($all_key%3 == 1) {
                        $page_size = 6;
                        $slipping = true;
                    } else {
                        $page_size = 4;
                        $slipping = false;
                    }
                    $cat_list = $d_group->group_list_index('default', $page_size, $val['cat_id']);
                    if (!empty($cat_list['group_list'])) {
                        // 左右滑动
                        $info['slipping'] = $slipping;
                        $info['more'] = $cat_list['totalPage']>1?true:false;
                        $info['group_list'] = $cat_list['group_list'];
                        $info['group_count'] = count($cat_list['group_list']);
                        $all_category_group[] = $info;
                        $all_key++;
                    }
                }
            }
            $this->assign('all_category_group',$all_category_group);
        }
		
		$this->display();
	}
	public function find(){
		
        // 发现团购
        $d_group = D('Group');
		$page_size = 10;
        $find_list = $d_group->group_list_index('hot', $page_size, 0, 0, true, 1);
        $this->assign('find_list',$find_list);
		$this->display();
	}
	public function my(){
		
		//联系客服
		if($this->config['open_kefu_url'] && $services = M('Customer_service')->where(array('mer_id' => $this->config['kefu_mer_id']))->select()) {
            $kefu_url = $this->config['site_url'].'/wap.php?c=My&a=concact_kefu&mer_id='.$this->config['kefu_mer_id'];
			$this->assign('kefu_url',$kefu_url);
		}
		
		$this->display();
	}
	
	public function category(){
		//所有一级分类
        $d_group = D('Group');
        $cat_id = I('cat_id');
        $all_category_list = D('Group_category')->get_category(false);
        $this->assign('all_category_list',$all_category_list);
        $all_url = $d_group->group_page_num($_GET['page_num']);
        $this->assign('all_url',$all_url);
        if ($cat_id) {
            $category_info = M('Group_category')->where(array('cat_id' => $cat_id))->find();
            if(!empty($category_info['cat_ad_pic'])){
                if (strpos($category_info['cat_ad_pic'], '/upload/category') !== false) {
                    $category_info['cat_ad_pic'] = file_domain().$category_info['cat_ad_pic'];
                } else {
                    $category_info['cat_ad_pic'] = file_domain().'/upload/system/'.$category_info['cat_ad_pic'];
                }
            }
            $this->assign('category_info',$category_info);
            $all_two_category = D('Group_category')->get_category(false, $cat_id);
        } else {
            $condition_group_category['cat_status'] = '1';
            $condition_group_category['cat_fid'] = array('neq', 0);
            $all_two_category = M('Group_category')->field(true)->where($condition_group_category)->order('`cat_sort` DESC')->select();
        }
        if ($all_two_category) {
            $all_two_category_group = array();
			$page_size = 4;
            foreach ($all_two_category as $key => $val) {
                if ($val) {
                    $info = array(
                        'cat_name' => $val['cat_name'],
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'cat_ad_pic' => $val['cat_ad_pic'],
                        'cat_des' => $val['cat_des'],
                    );
                    if ($_GET['page_num']>0) {
                        $page_num = $_GET['page_num'] + 1;
                        $info['more_url'] = C('config.site_url').'/wap.php?g=Wap&c=Groupnew&a=two_category&cat_id='.$val['cat_id'].'&page_num='.$page_num;
                    } else {
                        $info['more_url'] = C('config.site_url').'/wap.php?g=Wap&c=Groupnew&a=two_category&cat_id='.$val['cat_id'];
                    }
                    $info['more_url'] = $d_group->group_page_num($_GET['page_num'], $val['cat_id'], 'two_category');
                    $cat_list = $d_group->group_list_index('default', $page_size, 0, $val['cat_id'], false, 0, 0, 0, 0, 0, 0, 1);
                    if (!empty($cat_list['group_list'])) {
                        // 左右滑动
                        $info['more'] = $cat_list['totalPage']>1?true:false;
                        $info['group_list'] = $cat_list['group_list'];
                        $info['group_count'] = count($cat_list['group_list']);
                        $all_two_category_group[] = $info;
                    }
                }
            }
            $this->assign('all_two_category_group',$all_two_category_group);
        }
		
		$this->display();
	}
	public function two_category(){
		//二级分类页面
        $d_group = D('Group');
		$cat_id = I('cat_id');
        $page_size = 10;
        if ($cat_id) {
            $category_info = M('Group_category')->where(array('cat_id' => $cat_id))->find();
            if(!empty($category_info['cat_ad_pic'])){
                $category_info['cat_ad_pic'] = file_domain().'/upload/system/'.$category_info['cat_ad_pic'];
            }
            $this->assign('category_info',$category_info);
        }
		$cat_list = $d_group->group_list_index('default', $page_size, 0, $cat_id);
		$this->assign('cat_list',$cat_list);
		
		$this->display();
	}
    public function two_category_ajax(){
        //二级分类页面
        $d_group = D('Group');
        $cat_id = I('cat_id');
        $page_size = 10;
        $cat_list = $d_group->group_list_index('default', $page_size, 0, $cat_id);
        echo json_encode($cat_list);
    }


	public function detail(){
		
		//跳转到新版
		redirect(get_base_url('pages/group/v1/groupDetail/index?group_id=' . $_GET['group_id'], true));
		
	    $d_group = D('Group');
		$now_group = $d_group->get_group_by_groupId($_GET['group_id'],'hits-setInc');
		if(empty($now_group) && $_GET['source'] != 'group_combine'){
            $this->error_tips('当前'.$this->config['group_alias_name'].'已结束！', U('index'));
		}
		$store_info = $d_group->get_store_info($_GET['group_id']);
		$on_sale = false;//
		foreach($store_info as $val){
			if($val['group_on_sale']==1){
				$on_sale = true;
			}
		}
		if(!$on_sale){
			$this->error_tips('当前'.$this->config['group_alias_name'].'店铺已被下架，请联系系统管理员处理！');
		}
        $uid = $this->user_session['uid'];
		$d_group->group_record($_GET['group_id'], $uid, $now_group['mer_id']);
		if($now_group['end_time']<$_SERVER['REQUEST_TIME'] && $_GET['source'] != 'group_combine'){
			$this->error_tips('当前'.$this->config['group_alias_name'].'已结束！', U('index'));
		}
        if (!empty($now_group['trade_type'])) {
            redirect(U('Group/detail',array('group_id'=>$now_group['group_id'],'source'=>$_GET['source'])));
        }
		if($now_group['cue']){
			$now_group['cue_arr'] = unserialize($now_group['cue']);
		}
		//判断是否微信浏览器，
		$long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);

		if($long_lat){
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
                        $now_time =time();
                        $group_share_info['surplus_time'] = $end_time-$now_time;
						$this->assign('group_share_info', $group_share_info);
					} else {
						$group_start->update_start_group($_GET['gid'], 2); //2 团购小组超时
					}
				}
			}else{
				$can_join = D('Group_start')->check_join_pin($now_group['group_id'],$this->user_session['uid'],$now_group['pin_effective_time']);
				$this->assign('can_join',$can_join);
			}
            // 查询下用户的参团
            $user_join = D('Group_start')->check_user_pin($now_group['group_id'],$this->user_session['uid'],$now_group['pin_effective_time']);
            $user_join = reset($user_join);
            $this->assign('user_join',$user_join);

            //【ID1031543】 新增
            $user_join_pin = M('Group_order')->where(array('group_id' => $user_join['group_id'],'uid' => $user_join['uid'],'is_head' => $user_join['id']))->find();
            $this->assign('user_join_pin',$user_join_pin);


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
//        $database_group_category = D('Group_category');
//        $now_group['field_info'] = $database_group_category->get_cat_fid_show($now_group['cat_fid'], $now_group);

        // 处理 营业时间显示问题
        $store_ids = [];
        if ($now_group['store_list']) {
            foreach ($now_group['store_list'] as &$val) {
                $store_ids[] = $val['store_id'];
                if ($val['office_time']) {
                    $time = unserialize($val['office_time']);
                    $val['work_time'] .= '<span>' . $time['open'] . '-' . $time['close_time'] . '</span>';
                } else {
                    if ($val['open_1'] == '00:00:00' && $val['close_1'] == '00:00:00') {
                        $val['work_time'] .= '<span>24小时营业</span>';
                    } else {
                        $val['work_time'] .= '<span>' . substr($val['open_1'], 0, 5) . '-' . substr($val['close_1'],0,5) . '</span>';
                        if ($val['open_2'] != '00:00:00' && $val['close_2'] != '00:00:00') {
                            $val['work_time'] .= '<span>  ' . substr($val['open_2'],0,5) . '-' . substr($val['close_2'],0,5) . '</span>';
                        }
                        if ($val['open_3'] != '00:00:00' && $val['close_3'] != '00:00:00') {
                            $val['work_time'] .= '<span>  ' . substr($val['open_3'],0,5) . '-' . substr($val['close_3'],0,5) . '</span>';
                        }
                    }
                }
            }
        }
        if ($now_group['count_num'] && $now_group['count_num']>0 && $now_group['count_num'] <= $now_group['sale_count']) {
            $now_group['sale_out'] = true;
        } else {
            $now_group['sale_out'] = false;
        }

        $now_group['old_age_pension'] = D("Percent_rate")->getOldAgePension($now_group['mer_id'], 'group', $now_group['price']);
		$this->assign('now_group',$now_group);

		// 获取团购浏览信息和购买信息
        $group_record_num = $d_group->group_record_num($_GET['group_id'],2);
        if ($group_record_num['type'] == 1 && $now_group['virtual_num']>1) {
            $group_record_num['look_num'] += intval($now_group['virtual_num']);
        }elseif ($group_record_num['buy_times']>0 && $now_group['virtual_num']>1) {
            $group_record_num['buy_times'] += intval($now_group['virtual_num']);
        }

        $this->assign('group_record_num',$group_record_num);

        // 获取团购商家信息
        $merchant_info = M('Merchant')->where(array('mer_id' => $now_group['mer_id']))->field('name, txt_info, pic_info, s_txt, logo, province_id, city_id')->find();
        $tmp_pic_arr = explode(';', $merchant_info['pic_info']);
        $merchant_image_class = new merchant_image();
        $list_pic = $merchant_image_class->get_image_by_path($tmp_pic_arr[0]);
        $merchant_info['list_pic'] = $list_pic;
        if (!$merchant_info['logo']) {
            $merchant_info['logo'] = $list_pic;
        }
        $this->assign('merchant_info',$merchant_info);
		
		
		//保存用户表地址为当前商户地址
		if($this->user_session['uid']){
			$now_user = D('User')->get_user($this->user_session['uid']);
			if($now_user && !$now_user['city_id']){
				$data_user['province_id'] = $merchant_info['province_id'];
				$data_user['city_id'] = $merchant_info['city_id'];
				$data_user['use_lang'] = $this->config['system_lang'];
				M('User')->where(array('uid'=>$this->user_session['uid']))->data($data_user)->save();
			}
		}


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

		$data_time = $now_group['begin_time'] - $_SERVER['REQUEST_TIME'];
		$this->assign('data_time',$data_time);

		if($now_group['reply_count']){
			$reply_list = D('Reply')->get_reply_list($now_group['group_id'],0,count($now_group['store_list']),3);
			$this->assign('reply_list',$reply_list);
		}


		$merchant_group_list = $d_group->get_grouplist_by_MerchantId($now_group['mer_id'],3,true,$now_group['group_id']);
        if ($merchant_group_list) {
            // 根据开关处理售出数量是否按照规格计算
            foreach ($merchant_group_list as &$val) {
                $get_spec_sale_count = $d_group_order->get_spec_sale_count($val['group_id']);
                if ($get_spec_sale_count > 0) {
                    $val['sale_count'] = $get_spec_sale_count + intval($val['sale_count']);
                    $val['sale_txt'] = '已售' . $val['sale_count'];
                }
            }
        }

		$this->assign('merchant_group_list',$merchant_group_list);

		//分类下其他团购
		$category_group_list = $d_group->get_grouplist_by_catId($now_group['cat_id'],$now_group['cat_fid'],6,true);
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

        //调整客服判断
        $mainStoreId = count($now_group['store_list']) == 1 ? $now_group['store_list'][0]['store_id'] : 0;
        foreach ($now_group['store_list'] as $g) {
            if ($g['ismain'] == 1) {
                $mainStoreId = $g['store_id'];
                break;
            }
        }

        if ($_SESSION['openid']) {
            $user_openid = $_SESSION['openid'];
        } elseif ($_SESSION['user']['openid']) {
            $user_openid = $_SESSION['user']['openid'];
        } elseif ($this->user_session['openid']) {
            $user_openid = $this->user_session['openid'];
        } else {
            $user_openid = '';
        }
        if (C('IM_JMESSAGE_OPEN')) {
            $kf = M('Merchant_store_kefu')->where(['store_id' => $mainStoreId])->find();
            $uid = $this->user_session['uid'];
            if ($kf && $uid > 0 && $mainStoreId > 0) {
                $kfUrl = build_im_chat_url('user_' . $uid, $kf['username'], 'user2store', ['from' => 'group']);
                $this->assign('kf_url', $kfUrl);
            }
        } else {
            if ($user_openid && $services = D('Customer_service')->where(array('mer_id' => $now_group['mer_id']))->select()) {
                $key = $this->get_encrypt_key(array('app_id' => $this->config['im_appid'], 'openid' => $user_openid), $this->config['im_appkey']);
                $kf_url = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com') . '/?app_id=' . $this->config['im_appid'] . '&openid=' . $user_openid . '&key=' . $key . '#serviceList_' . $now_group['mer_id'];

                $this->assign('kf_url', $kf_url);
            }
        }
		
		if($this->config['coupon_spread_open']){
			$merchant_card_spread_info = D('Card_new')->get_card_by_mer_id_and_uid($now_group['mer_id'],$this->user_session['uid']);
			$this->assign('merchant_card_spread_info',$merchant_card_spread_info);
		}
        // 获取商品 各个详情
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
        $this->assign('group_content', $group_content);

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
                $today_can_buy = D('Group_order')->get_spec_buy_num_new($val['group_id'], $val, $this->user_session['uid']);

                $val['today_can_buy'] = $today_can_buy['num'];
                $val['num_desc'] = $today_can_buy['desc'];
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

        if ($uid && $uid>0) {
            //  判断是否登录
            if ($this->is_weixin_browser && !$this->is_wxapp_browser && 1==$this->config['open_single_weixin_spread_user']) {
                // 暂时只针对微信公众号，且开启了开关
                $openid = D('User')->where(['uid' => $uid])->getField('openid');
                if ($openid) {
                    // 必须实际存在openid的值才有效
                    $this->assign('share_openid',$openid);
                } else {
                    fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/open_single_weixin_spread_user_err',true);
                    fdump($uid,'api/log/'.date('Ymd').'/open_single_weixin_spread_user_err',true);
                    fdump($now_group['group_id'],'api/log/'.date('Ymd').'/open_single_weixin_spread_user_err',true);
                }
            }

            $this->assign('is_login',true);
        } else {
            $this->assign('is_login',false);
        }

        // 查看是否参与优惠组合套餐
        $where = [
            'group_id' => $now_group['group_id']
        ];
        $groupCombine = D('Group_combine_activity')->getActivityByGroup($where);
        $this->assign('groupCombine',$groupCombine);
        //店铺套餐
        $package_list = [];
        if (!empty($store_ids)) {
            $store_ids_str = implode(",", $store_ids);
//            $package_info = D('')->field('`p`.`id`,p.`name`,`p`.`price`,`p`.`image`,`p`.`note`')
//                ->table(array(C('DB_PREFIX').'foodshop_goods_package'=>'p',C('DB_PREFIX').'foodshop_goods_package_detail'=>'d'))
//                ->where("`p`.`id`=`d`.`pid` AND `p`.`store_id` in (".$store_ids_str.") AND `p`.`status`='1' AND `p`.`is_order`='1' AND `d`.`goods_detail` <> ''")
//                ->order("p.price desc")
//                ->limit(1)
//                ->select();
            $package_info = D('')->field('`p`.`id`,p.`name`,`p`.`price`,`p`.`image`,`p`.`note`')
                ->table(array(C('DB_PREFIX').'foodshop_goods_package'=>'p',C('DB_PREFIX').'foodshop_goods_package_detail'=>'d',C('DB_PREFIX').'group_store'=>'s'))
                ->where("`p`.`id`=`d`.`pid` AND `p`.`id`=`s`.`package_id` AND `s`.`store_id` = `p`.`store_id` AND `s`.`group_id` = ".$now_group['group_id']." AND `p`.`status`='1' AND `p`.`is_order`='1' AND `d`.`goods_detail` <> ''")
                ->limit(1)
                ->select();
            if (!empty($package_info)) {
                $package_id = $package_info[0]['id'];
                $package_list = D('')->field('`d`.`goods_detail`,`d`.`num`,`d`.`package_name`')
                    ->table(array(C('DB_PREFIX').'foodshop_goods_package'=>'p',C('DB_PREFIX').'foodshop_goods_package_detail'=>'d'))
                    ->where("`p`.`id`=`d`.`pid` AND `p`.`id`=".$package_id." AND `p`.`status`='1' AND `p`.`is_order`='1' AND `d`.`goods_detail` <> ''")
                    ->select();
                foreach ($package_list as $_key => $value) {
                    unset($package_list[$_key]['goods_detail']);
                    $goods_ids = json_decode($value['goods_detail'], true);
                    $_where = array(
                        'goods_id' => array('in', $goods_ids),
                    );
                    $goods_list = D('Shop_goods')->where($_where)->select();
                    $goods = [];
                    foreach ($goods_list as $good) {
                        $arr['price'] = $good['price'];
                        $arr['name'] = $good['name'];
                        $goods[] = $arr;
                    }
                    $package_list[$_key]['goods'] = $goods;
                }
            }
        }
        $this->assign('package_list',$package_list);

		$this->display();
	}


    private function check_group_status($groupids=array()){
        if(!empty($groupids)){
            $tmpids=M('Group')->where('group_id in('.implode(',',$groupids).') and status="1" AND pin_num=0  AND end_time>'.$_SERVER['REQUEST_TIME'])->field('group_id')->select();
            return $tmpids;
        }
        return false;
    }




	public function merchant(){
        $mer_id = I('mer_id');
        // 获取团购商家信息
        $merchant_info = M('Merchant')->where(array('mer_id' => $mer_id))->field('name, txt_info, pic_info, s_txt, logo')->find();
        $merchant_image_class = new merchant_image();
        $list_pic = $merchant_image_class->get_allImage_by_path($merchant_info['pic_info']);
        $merchant_info['list_pic'] = $list_pic;
        $merchant_info['pic_num'] = count($list_pic);
        if (!$merchant_info['logo']) {
            $merchant_info['logo'] = $list_pic[0];
        }
        $this->assign('merchant_info',$merchant_info);

        $d_group = D('Group');
        // 获取浏览数和头像
        $merchant_record_num = $d_group->merchant_record_num($mer_id, 1);
        $this->assign('merchant_record_num',$merchant_record_num);


	    // 获取商家下所有店铺信息
        $merchant_store_list = D('Merchant_store')->get_storelist_by_merId($mer_id,'group');
        foreach ($merchant_store_list as &$val) {
            if ($val['office_time']) {
                $time = unserialize($val['office_time']);
                $val['work_time'] .= '<span>' . $time['open'] . '-' . $time['close_time'] . '</span>';
            } else {
                if ($val['open_1'] == '00:00:00' && $val['close_1'] == '00:00:00') {
                    $val['work_time'] .= '<span>24小时营业</span>';
                } else {
                    $val['work_time'] .= '<span>' . substr($val['open_1'], 0, 5) . '-' . substr($val['close_1'],0,5) . '</span>';
                    if ($val['open_2'] != '00:00:00' && $val['close_2'] != '00:00:00') {
                        $val['work_time'] .= '<span>  ' . substr($val['open_2'],0,5) . '-' . substr($val['close_2'],0,5) . '</span>';
                    }
                    if ($val['open_3'] != '00:00:00' && $val['close_3'] != '00:00:00') {
                        $val['work_time'] .= '<span>  ' . substr($val['open_3'],0,5) . '-' . substr($val['close_3'],0,5) . '</span>';
                    }
                }
            }
        }


        $this->assign('merchant_store_list',$merchant_store_list);


        //为粉丝推荐
        $index_sort_group_list = $d_group->get_group_list('index_sort',10,true,$mer_id);
        //判断是否微信浏览器，
        if($_SESSION['openid'] && $index_sort_group_list){
            $long_lat = D('User_long_lat')->field('long,lat')->where(array('open_id' => $_SESSION['openid']))->find();
            if($long_lat){
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
                    //$storeGroupValue['detail_url'] = $d_group->group_page_num($_GET['page_num'], $storeGroupValue['group_id'], 'group_detail');
                }
            }
            foreach($index_sort_group_list as &$storeGroupValue){
                $storeGroupValue['detail_url'] = $d_group->group_page_num($_GET['page_num'], $storeGroupValue['group_id'], 'group_detail');
            }
        } elseif($index_sort_group_list) {
            foreach($index_sort_group_list as &$storeGroupValue){
                $storeGroupValue['detail_url'] = $d_group->group_page_num($_GET['page_num'], $storeGroupValue['group_id'], 'group_detail');
            }
        }
        $this->assign('index_sort_group_list',$index_sort_group_list);
		$this->display();
	}
	
	public function share(){
		$group_id = intval($_GET['group_id']);

		if(!$this->config['group_index_name']){
			$this->config['group_index_name'] = '同城优选';
		}

		$now_group = D('Group')->get_group_by_groupId($group_id,'hits-setInc');

		if(empty($now_group)){
			$this->error_tips('当前团购不存在');
		}

//        if(empty($this->user_session)){
//            if($this->is_app_browser){
//                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
//                $this->error_tips('请先进行登录！',U('Login/index',$location_param));
//            }else{
//                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
//                redirect(U('Login/index',$location_param));
//            }
//        }
		
		$uid = $this->user_session['uid'];
		D('Group')->group_record($group_id, $uid, $now_group['mer_id'], 3);

		$group_id = $now_group['group_id'];
		$group_name = $now_group['group_name'];
		$group_price = floatval($now_group['price']);
		$group_old_price = floatval($now_group['old_price']);
        $goodMainPic = $now_group['all_pic'][0]['image'];

		//将oss上的图不做处理直接使用
		$file_handle = new file_handle();
		if(!$file_handle->check_open_oss()) {
            $goodMainPic = '.'.str_replace($this->config['site_url'],'',$goodMainPic);
        }



        $img_rand_path = sprintf("%09d", $group_id);
		$rand_num = substr($img_rand_path, 0, 3) . '/' . substr($img_rand_path, 3, 3) . '/' . substr($img_rand_path, 6, 3);
		//商家小程序加前缀
		if($_SESSION['wxapp_own_mer_id']){
			$rand_num = 'merchant_wxapp/' . $rand_num;
		}
		$imgFriendPath = 'runtime/wxapp_group/'.$rand_num.'/friend_'.$group_id.'.png';
		$imgGroupPath = 'runtime/wxapp_group/'.$rand_num.'/group_'.$group_id.'.png';
		$imgGroupPathShare = 'runtime/wxapp_group/'.$rand_num.'/group_'.$group_id.'_'.$this->user_session['uid'].'.png';

		if(!file_exists(dirname($imgGroupPathShare))){
			mkdir(dirname($imgGroupPathShare),0777,true);
		}
        if(!file_exists(dirname($imgFriendPath))){
            mkdir(dirname($imgFriendPath),0777,true);
        }
        if(!file_exists(dirname($imgGroupPath))){
            mkdir(dirname($imgGroupPath),0777,true);
        }

        $wxapp_path = 'pages/webview/webview?webview_url='.urlencode($this->config['site_url'] . '/wap.php?c=Groupnew&a=detail&s=1&group_id='.$group_id);
		if ($_GET['order_id']>0) {
            $now_order = D('Group_order')->get_order_detail_by_id($this->user_session['uid'],intval($_GET['order_id']),true);
            if($now_group['pin_num']>0 && $now_order['single_buy']==0 && $now_order['status']<3 && $now_order['paid']){
                $my_group_join = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
                if(empty($my_group_join)&&$now_order['paid']==1){
                    $this->error_tips('当前订单出错');
                }
                if ($my_group_join && $my_group_join['id']>0) {
                    $wxapp_path = 'pages/webview/webview?webview_url=' . urlencode($this->config['site_url'] . '/wap.php?c=Groupnew&a=detail&s=1&group_id='.$group_id.'&gid='.$my_group_join['id']);
                }
            }
        }

			
		/*
		 *   分享朋友圈 600*822 图片
		 */
		if(!file_exists($imgGroupPath)){
			if(!file_exists(dirname($imgGroupPath))){
				mkdir(dirname($imgGroupPath),0777,true);
			}
		
			$img = imagecreatetruecolor(600,822);
			
			$white  =  imagecolorallocate ( $img ,  255 ,  255 ,  255 );
			imagefill ( $img ,  0 ,   0 ,  $white );
			
			//背景图片绘制
				$src_im = imagecreatefrompng('static/wxapp/wxapp_group_bg.png');
				//裁剪
//				 imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 600,822,600,822);
//				 imagedestroy($src_im); //销毁原图
				imagecopy($img,$src_im,0,0,0,0,600,822);

			//商品图片绘制
				$info   = getimagesize($goodMainPic);
				$fun    = 'imagecreatefrom'.image_type_to_extension($info[2], false);
				$src_im =  call_user_func_array($fun,array($goodMainPic));

			//创建新图像
				$newimg = imagecreatetruecolor(510,284);
				// 调整默认颜色
				$color = imagecolorallocate($newimg, 255, 255, 255);
				imagefill($newimg, 0, 0, $color);
				//裁剪
				imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 510,284,$info[0],$info[1]);
				imagedestroy($src_im); //销毁原图
				imagecopy($img,$newimg,45,125,0,0,510,284);

			//商品标题
//				$font = './static/fonts/apple_lihei.otf';
                $font = realpath('./static/fonts/PingFang Regular.ttf');
				$tmpGoodName = $group_name;
				$goodNameArr = array();
				$goodNameArr[] = mb_strimwidth($tmpGoodName,0,41,'','utf-8');
				if($group_name != $goodNameArr[0]){
					$tmpGoodName = str_replace($goodNameArr[0],'',$tmpGoodName);
					$goodNameArr[] = mb_strimwidth($tmpGoodName,0,41,'...','utf-8');
				}
				$good_name = implode($goodNameArr,"\n");
				$fontSize = 18;//像素字体
				$fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
				imagettftext( $img, $fontSize, 0, 45, 460, $fontColor, $font, $good_name);


			//实际价格
				$fontSize = 24;//像素字体
				$fontColor = imagecolorallocate ($img, 238, 0, 0 );//字的RGB颜色
				imagettftext( $img, $fontSize, 0, 40, 560, $fontColor, $font, C('config.Currency_symbol').$group_price);

			//原价格
				$fontBox = imagettfbbox($fontSize, 0, $font,C('config.Currency_symbol').$group_price);//文字水平居中实质
				$groupPriceWidth = $fontBox[2];
				$fontSize = 14;//像素字体
				$fontColor = imagecolorallocate ($img, 153, 153, 153 );//字的RGB颜色
				imagettftext($img, $fontSize, 0, 70 + $groupPriceWidth, 557, $fontColor, $font, C('config.Currency_symbol').$group_old_price);
				
				$fontBox = imagettfbbox($fontSize, 0, $font,C('config.Currency_symbol').$group_old_price);//文字水平居中实质
				$groupOldPriceWidth = $fontBox[2];
				$groupOldPriceBegin = 70 + $groupPriceWidth;
				imageline($img,$groupOldPriceBegin,550,$groupOldPriceBegin + $groupOldPriceWidth + 5,550,$fontColor);


				if($_GET['origin'] == 'web') { //  $origin  web 网页二维码   mini 小程序二维码
                    //生成网页二维码
                    $openid = D('User')->where(['uid' => $uid])->getField('openid');
                    if($this->config['open_single_weixin_spread_user'] == 1) {  //分享获得佣金
                        $qrCon =  C('config.site_url')."/wap.php?g=Wap&c=Groupnew&a=detail&group_id=".$group_id."&page_num=1&share_openid=".$openid;
                    } else {   // 三级分佣
                        $qrCon =  C('config.site_url')."/wap.php?g=Wap&c=Groupnew&a=detail&group_id=".$group_id."&page_num=1&openid=".$openid;
                    }

                    if(!file_exists('./runtime/tempQrcode')){
                        mkdir('./runtime/tempQrcode',0777,true);
                    }
                    $filePath = './runtime/tempQrcode/'.uniqid().'.png';

                    $this->get_own_qrcode($qrCon, 7,true, $filePath);

                    $src_im = imagecreatefrompng($filePath);
                } else {
                    //小程序二维码
                    //换短链
                    $url='/wap.php?c=Groupnew&a=detail&s=1&group_id='.$group_id;
                    $link_id = M('Short_link')->data(['link_url'=>$url])->add();
                    if(!$link_id){
                        $this->error('小程序码生成失败');
                    }

                    //小程序路径
                    $wxapp_path = D('House_village')->home_url . '?redirect=webview&webview_url='.urlencode('/short_'.$link_id);

                    $qrcode = $this->get_wxapp_qrcode($wxapp_path,280);
                    if(strpos($qrcode,'{"') === 0){
                        $qrcodeArr = json_decode($qrcode,true);
                        if($qrcodeArr && $qrcodeArr['errcode']){
                            $wxapp_access_token = D('Access_token_wxapp_expires')->get_access_token();
                            if($wxapp_access_token['errcode']){
                                $this->error_tips('小程序二维码生成失败。'.$wxapp_access_token['errmsg']);
                            }else{
                                $wxapp_access_token = $wxapp_access_token['access_token'];
                            }

                            $tmpUrlArr = parse_url($wxapp_path);
                            $urlParam = convertUrlQuery($tmpUrlArr['query']);
                            if($urlParam['redirect'] != 'webview'){
                                return [];
                            }
                            $page = 'pages/webview/webview';
                            $url = urldecode($urlParam['webview_url']);
                            $link_id = M('Short_link')->data(['link_url'=>$url])->add();
                            if(!$link_id){
                                $this->error('小程序码生成失败');
                            }
                            $scene = '/short_'.$link_id;
                            $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$wxapp_access_token;
                            $postData = array(
                                'scene'=> $scene,
                                'page'=> $page,
                                'width'=> 150
                            );
                            import('ORG.Net.Http');
                            $http = new Http();
                            $qrcode = $http->curlPostOwn($url, json_encode($postData));
                        }
                    }
                    if(!file_exists('./runtime/tempQrcode')){
                        mkdir('./runtime/tempQrcode',0777,true);
                    }
                    $filePath = './runtime/tempQrcode/'.uniqid().'.jpg';
                    file_put_contents($filePath,$qrcode);
                    $src_im = imagecreatefromjpeg($filePath);
                }

				//创建新图像
				$newimg = imagecreatetruecolor(120,120);

				// 调整默认颜色
				$color = imagecolorallocate($newimg, 255, 255, 255);
				imagefill($newimg, 0, 0, $color);

				//裁剪
				imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 120,120,280,280);
				imagedestroy($src_im); //销毁原图

				imagecopy($img,$newimg,422,630,0,0,120,120);

			//分享语句
				$fontSize = 14;//像素字体
				$fontColor = imagecolorallocate ($img, 153,153,153 );//字的RGB颜色
				imagettftext($img, $fontSize, 0, 120, 100, $fontColor, $font, '发现了一个好东西，想跟你分享 ~');
			
			//平台名称
				$fontSize = 18;//像素字体
				$fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
				imagettftext($img, $fontSize, 0, 45, 670, $fontColor, $font, $this->config['group_index_name']);
			
			//平台分享语句
                if ($this->config['group_index_share']) {
                    $tmpGoodName = $this->config['group_index_share'];
                } else {
                    $tmpGoodName = '专注团购优选的本地生活服务平台！';
                }
				$goodNameArr = array();
				$goodNameArr[] = mb_strimwidth($tmpGoodName,0,34,'','utf-8');
				if($group_name != $goodNameArr[0]){
					$tmpGoodName = str_replace($goodNameArr[0],'',$tmpGoodName);
					$goodNameArr[] = mb_strimwidth($tmpGoodName,0,34,'...','utf-8');
				}
				$good_name = implode($goodNameArr,"\n");
				$fontSize = 14;//像素字体
				$fontColor = imagecolorallocate ($img, 153,153,153 );//字的RGB颜色
				imagettftext( $img, $fontSize, 0, 45, 710, $fontColor, $font, $good_name);
				
			//引导识别二维码
				$fontSize = 12;//像素字体
				$fontColor = imagecolorallocate($img, 153,153,153 );//字的RGB颜色
				imagettftext($img, $fontSize, 0, 432, 770, $fontColor, $font, '长按立即购买');

			//保存主图
				imagepng($img,$imgGroupPath);

		}

		$img = imagecreatetruecolor(600,822);
			
		$white  =  imagecolorallocate ( $img ,  255 ,  255 ,  255 );
		imagefill ( $img ,  0 ,   0 ,  $white );
		
		//背景图片绘制
		$src_im = imagecreatefrompng($imgGroupPath);
		//裁剪
		// imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 600,822,600,822);
		// imagedestroy($src_im); //销毁原图
		imagecopy($img,$src_im,0,0,0,0,600,822);
		
//		$font = './static/fonts/apple_lihei.otf';
        $font = realpath('./static/fonts/PingFang Regular.ttf');
		//用户头像
            if (empty($this->user_session['avatar'])) {
                $this->user_session['avatar'] = M('User')->where(array('uid'=>$this->user_session['uid']))->getField('avatar');
            }
            if (empty($this->user_session['avatar'])) {
                $this->user_session['avatar'] = C('config.site_url'). '/static/img/images/nohead.png';
            }


			import('ORG.Net.Http');
			$http = new Http();
			$avatar = $http->curlGet($this->user_session['avatar']);
			
			if(!file_exists('./runtime/tempQrcode')){
				mkdir('./runtime/tempQrcode',0777,true);
			}
			$filePath = './runtime/tempQrcode/'.uniqid().'.jpg';
			file_put_contents($filePath,$avatar);
			
			$src_im =  imagecreatefromjpeg($filePath);
			
			//创建新图像
			$newimg = imagecreatetruecolor(60,60);
			// 调整默认颜色
			$color = imagecolorallocate($newimg, 255, 255, 255);
			imagefill($newimg, 0, 0, $color);
			//裁剪
			imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 60,60,132,132);
			imagedestroy($src_im); //销毁原图
			
			imagecopy($img,$newimg,45,45,0,0,60,60);

		//用户昵称
			$fontSize = 14;//像素字体
			$fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
			imagettftext($img, $fontSize, 0, 120, 72, $fontColor, $font, '@ '.$this->user_session['nickname']);
			
		//浏览人次
            $d_group = D('Group');
            $hitsTxt = '';
            $group_record_num = $d_group->group_record_num($_GET['group_id'],2);
            $now_group['hits'] = $group_record_num['look_num'];
            if ($now_group['hits'] > 0) {
                if($now_group['hits'] > 100000){
                    $now_group['hits'] = round($now_group['hits']/10000,1).'万';
                }else if($now_group['hits'] > 10000){
                    $now_group['hits'] = round($now_group['hits']/10000,2).'万';
                }

                $hitsTxt = $now_group['hits'].'人感兴趣';
            }
			$fontBox = imagettfbbox($fontSize, 0, $font,$hitsTxt);//文字水平居中实质
			$groupPriceWidth = $fontBox[2];
			$fontSize = 14;//像素字体
			$fontColor = imagecolorallocate ($img, 153, 153, 153 );//字的RGB颜色
			imagettftext ($img, $fontSize, 0, 550 - $groupPriceWidth, 557, $fontColor, $font, $hitsTxt);

			imagepng($img,$imgGroupPathShare);

		/* 朋友圈图片结束 */
		
		/*
		 *   分享好友 500*400 图片
		 */
		if(!file_exists($imgFriendPath)){
			if(!file_exists(dirname($imgFriendPath))){
				mkdir(dirname($imgFriendPath),0777,true);
			}

			$img = imagecreatetruecolor(500,400);
			
			$white  =  imagecolorallocate ( $img ,  255 ,  255 ,  255 );
			imagefill ( $img ,  0 ,   0 ,  $white );
			
			//背景图片绘制
				$src_im = imagecreatefrompng('static/wxapp/wxapp_friend_bg.png');
				//裁剪
				imagecopy($img,$src_im,0,0,0,0,500,400);
			
			//商品图片绘制
				$info   = getimagesize($goodMainPic);
				$fun    = 'imagecreatefrom'.image_type_to_extension($info[2], false);
				$src_im =  call_user_func_array($fun,array($goodMainPic));
			
			
			//创建新图像
				$newimg = imagecreatetruecolor(554,308);
				// 调整默认颜色
				$color = imagecolorallocate($newimg, 255, 255, 255);
				imagefill($newimg, 0, 0, $color);
				//裁剪
				imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 554,308,$info[0],$info[1]);
				imagedestroy($src_im); //销毁原图
				imagecopy($img,$newimg,-27,0,0,0,554,308);
			
			//实际价格
				$fontSize = 20;//像素字体
				$fontColor = imagecolorallocate($img, 238, 0, 0);//字的RGB颜色
				imagettftext ($img, $fontSize, 0, 10, 366, $fontColor, $font, C('config.Currency_symbol').$group_price);
			
			//原价格
				$fontBox = imagettfbbox($fontSize, 0, $font,C('config.Currency_symbol').$group_price);//文字水平居中实质
				$groupPriceWidth = $fontBox[2];
				$fontSize = 12;//像素字体
				$fontColor = imagecolorallocate($img, 153, 153, 153);//字的RGB颜色
				imagettftext ($img, $fontSize, 0, 30 + $groupPriceWidth, 363, $fontColor, $font, C('config.Currency_symbol').$group_old_price);
				
				$fontBox = imagettfbbox($fontSize, 0, $font,C('config.Currency_symbol').$group_old_price);//文字水平居中实质
				$groupOldPriceWidth = $fontBox[2];
				$groupOldPriceBegin = 26 + $groupPriceWidth;
				imageline($img,$groupOldPriceBegin,357,$groupOldPriceBegin + $groupOldPriceWidth + 10,357,$fontColor);
			
			//保存主图
				imagepng($img,$imgFriendPath);
		}
		$share_img_arr = array(
			'share_title' 	=> 	'我推荐'.$now_group['group_name'],
			'good_img' 		=> 	$now_group['all_pic'][0]['image'] . '?' . date('YmdHi'),
			'friend_img'	=>	C('config.site_url').'/'.$imgFriendPath . '?' . date('YmdHi'),
			'group_img'		=>	C('config.site_url').'/'.$imgGroupPathShare . '?' . date('YmdHi'),
			'wxapp_path'	=>	$wxapp_path,
			'title'			=>	'邀请好友一起抢',
		);


        $this->success($share_img_arr);

//		 header('Content-type: image/png');
//		 imagepng($img);
//		 die;
	}
	
	
	protected function get_wxapp_qrcode($path,$width){
		fdump($_SESSION, 'group_get_wxapp_qrcode');
		//兼容商家小程序
		if($_SESSION['wxapp_own_mer_id']){
			$wxapp_access_token = D('Access_token_wxapp_merchant_expires')->get_access_token($_SESSION['wxapp_own_mer_id']);
		}else{
			$wxapp_access_token = D('Access_token_wxapp_expires')->get_access_token();
		}
		$wxapp_access_token = $wxapp_access_token['access_token'];
		
		import('ORG.Net.Http');
		$http = new Http();
		$url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$wxapp_access_token;
		$postData = array(
			'path'=>$path,
			'width'=>$width
		);

        $qrcode = $http->curlPostOwn($url,json_encode($postData));
        if(is_null(json_decode($qrcode))){
            return $qrcode;
        }

        //若返回报错，则尝试生成无数量限制的
        $tmpUrlArr = parse_url($path);
        $urlParam = convertUrlQuery($tmpUrlArr['query']);
        if($urlParam['redirect'] != 'webview'){
            return [];
        }

        $page = 'pages/webview/webview';
        $scene = urldecode($urlParam['webview_url']);
        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$wxapp_access_token;
        $postData = array(
            'scene'=> $scene,
            'page'=> $page,
            'width'=> $width
        );
        $qrcode = $http->curlPostOwn($url, json_encode($postData));
		return $qrcode;
	}

    public function get_own_qrcode($qrCon, $size = 8,$is_down = true, $outfile = false){
        header('Content-Type: image/png');
        ob_clean();
//        $qrCon = $_GET['qrCon'];
        import('@.ORG.phpqrcode');
//        $size = $_GET['size'] ? $_GET['size']: 10;
//        $is_down = $_GET['down'] ? true: false;
        QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),$outfile,0,$size,1,$is_down);
    }

	public function order_list(){
        redirect(C('site_url').'/packapp/plat/pages/my/my_order?state=0');
        if(empty($this->user_session)){
			if($this->is_app_browser){
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				$this->error_tips('请先进行登录！',U('Login/index',$location_param));
			}else{
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				redirect(U('Login/index',$location_param));
			}
        }
        $status = $this->type_status($_GET['type']);

        $order_list = D('Group')->get_new_order_list($this->user_session['uid'],$status);
		
		foreach($order_list['order_list'] as &$order_value){
			$order_value['merchant_logo'] = replace_file_domain($order_value['merchant_logo']);
		}
        $this->assign('order_list',$order_list);

		
		$page_num = $_GET['page_num'] ? $_GET['page_num']+1 : 0;
		$this->assign('page_num',$page_num);
		
		$this->display();
	}

    public function ajax_order_list(){
        if(IS_AJAX){
            $status = $this->type_status($_GET['type']);
            $order_list = D('Group')->get_new_order_list($this->user_session['uid'],$status);
            echo json_encode($order_list);

        }else{
            $this->error('访问页面不存在！~~');
        }
    }

    /**
     * 处理状态
     * @param $type
     * @return int|mixed
     */
    private function type_status($type) {
        $type_status = array(
            'topay' => -1,
            'topin' => 4,
            'touse' => 1,
            'isover' => 5,
            'isuse' => 2
        );
        $status = $type_status[$type] ? $type_status[$type] : 0;
        return $status;
    }

    /*拼团邀请详情*/
    public function invitePin(){
        if(empty($this->user_session)){
            if($this->is_app_browser){
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                $this->error_tips('请先进行登录！',U('Login/index',$location_param));
            }else{
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                redirect(U('Login/index',$location_param));
            }
        }
        $otherrm = isset($_GET['otherrm']) ? intval($_GET['otherrm']) : 0;
        $otherrm && $_SESSION['otherwc'] = null;
        $now_order = D('Group_order')->get_order_detail_by_id($this->user_session['uid'],$_GET['order_id'],true);

        $now_order['order_type'] = 'group';
        $laste_order_info=D('Tmp_orderid')->get_laste_order_info($now_order['order_type'],$now_order['order_id']);
        if(!$now_order['paid'] && !empty($laste_order_info)) {
            if ($laste_order_info['pay_type']=='weixin') {
                $redirctUrl = C('config.site_url') . '/wap.php?g=Wap&c=Pay&a=weixin_back&order_type='.$now_order['order_type'].'&order_id=' . $laste_order_info['orderid'];
                file_get_contents($redirctUrl);
                $now_order = D('Group_order')->get_order_detail_by_id($this->user_session['uid'], $_GET['order_id'], true);
            }
        }
        $now_group = D('Group')->where(array('group_id'=>$now_order['group_id']))->find();

        $database_merchant = D('Merchant');
        $now_merchant = $database_merchant->get_info($now_group['mer_id']);
        $now_group['merchant_name'] = $now_merchant['name'];

        if(empty($now_order)){
            $this->error_tips('当前订单不存在');
        }
        if ($now_order['status'] == '2' || $now_order['status'] == '3') {
            $this->error_tips('拼团已经结束');
        }

        $my_group_join = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);

        if ($my_group_join['status'] == '2') {
            $this->error_tips('拼团已经结束');
        }

        if(empty($now_order['paid'])){
            $now_order['status_txt'] = '未付款';
        }else if(empty($now_order['third_id']) && $now_order['pay_type'] == 'offline'){
            $now_order['status_txt'] = '线下未付款';
        }else if(empty($now_order['status'])){
            if($now_order['tuan_type'] != 2){
                $now_order['status_txt'] = '未消费';
            }else{
                $now_order['status_txt'] = '未发货';
            }
        }else if($now_order['status'] == '1'){
            $now_order['status_txt'] = '待评价';
        }else if($now_order['status'] == '2'){
            $now_order['status_txt'] = '已完成';
        }else if($now_order['status'] == '3'){
            $now_order['status_txt'] = '已退款';
            $now_order['group_pass_txt'] = '退款订单无法查看';
        }else if($now_order['status'] == '7'){
            $now_order['status_txt'] = '未消费';
        }


        $uid = $this->user_session['uid'];
        $group_share_num = D('Group_share_relation')->get_share_num($uid,$now_order['order_id']);
        $is_shared = D('Group_share_relation')->check_share($uid,$now_order['order_id']);
        $pic = explode(';',$now_group['pic']);
        foreach($pic as &$v){
            $v = preg_replace('/,/','/',$v);
        }
        $now_group['pic'] = $pic;
        if($now_group['pin_num']>0 && $now_order['single_buy']==0 && $now_order['status']<3 && $now_order['paid']){
            $my_group_join = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
            if(empty($my_group_join)&&$now_order['paid']==1){
                $this->error_tips('当前订单出错');
            }
            if ($my_group_join['status'] == '2') {
                $this->error_tips('拼团已经结束');
            }
			if($my_group_join['complete_num'] - $my_group_join['num'] <= 0){
				redirect(U('order_list'));
			}
            $buyer = D('Group_start')->get_buyerer_by_order_id($now_order['order_id']);
            $robot_list = M('Robot_list')->where(array('mer_id'=>$now_order['mer_id']))->getField('id,robot_name,avatar');

            foreach ($buyer as &$v) {
                if($v['type']==1||$v['uid']!=$now_order['uid']){
                    if(empty($v['pay_time'])){
                        $v['pay_time'] = $buyer['last_time'];
                    }
                    $tmp_name = $v['nickname'];
                    if($v['type']==1){
                        $tmp_name = $robot_list[$v['uid']]['robot_name'];
                        $v['avatar'] = $robot_list[$v['uid']]['avatar'];
                    }
                    if(preg_match('/\d{3}\*{4}\d{4}/',$v['nickname'],$m)){
                        $v['nickname'] = '游客';
                    }
                    $strlen     = mb_strlen($tmp_name, 'utf-8');
                    $firstStr     = mb_substr($tmp_name, 0, 1, 'utf-8');
                    $lastStr     = mb_substr($tmp_name, -1, 1, 'utf-8');
                    $v['nickname'] =  $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($tmp_name, 'utf-8') - 1) : $firstStr . str_repeat("*", $strlen - 1) ;
                    //$v['avatar'] = $robot_list[$v['uid']]['avatar'];
                }
            }

            //$group_share_info = D('Group_start')->get_group_start_user_by_gid($my_group_join['id']);
            $end_time = $my_group_join['start_time'] + $now_group['pin_effective_time'] * 3600;
            $effective_time = $end_time - $_SERVER['REQUEST_TIME'];

            if ($effective_time > 0) {
                $this->assign('effective_time', $effective_time);
            }
            if($effective_time<=0 && $my_group_join['status']==0){
                D('Group_start')->timeout($now_order['order_id']);
                $my_group_join['status'] = 2;
            }
            //$this->assign('effective_time',$effective_time);
            $this->assign('buyer',$buyer);
            $this->assign('my_group_join',$my_group_join);
        }else {
            if ($now_group['group_share_num'] == 0 && $now_group['open_now_num'] == 0 && $now_group['open_num'] == 0) {
                M('Group_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_share_group' => 2));
                $now_order['is_share_group'] = 2;
            } else if ($now_group['group_share_num'] != 0 && $now_group['group_share_num'] <= $group_share_num) {
                M('Group_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_share_group' => 2));
                $now_order['is_share_group'] = 2;
            } else if ($now_group['open_now_num'] <= $now_group['sale_count'] && $now_group['open_now_num'] != 0 && $now_group['group_share_num'] == 0) {
                M('Group_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_share_group' => 2));
                $now_order['is_share_group'] = 2;
            } else if ($now_group['open_num'] <= $now_group['sale_count'] && $now_group['open_num'] != 0 && $now_group['open_now_num'] == 0 && $now_group['group_share_num'] == 0) {
                M('Group_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_share_group' => 2));
                $now_order['is_share_group'] = 2;
            }
            if ($now_group['group_share_num'] > 0) {
                $share_user = D('Group_share_relation')->get_share_user($this->user_session['uid'], $now_order['order_id']);
                $this->assign('share_user', $share_user);
            }
        }
        //$now_order['coupon_price'] = D('Group_order')->get_coupon_info($now_order['order_id']);
        $has_pay = $now_order['wx_cheap']+$now_order['merchant_balance']+$now_order['balance_pay']+$now_order['score_deducte']+$now_order['coupon_price']+$now_order['payment_money'];

        if($now_order['pass_array']){
            $pass_array = D('Group_pass_relation')->get_pass_array($now_order['order_id']);
            $consume_num = D('Group_pass_relation')->get_pass_num($now_order['order_id'],1);
            $unconsume_pass_num = $now_order['num']-$consume_num;
            $this->assign('pass_array',$pass_array);
        }
        if($now_order['status']==6){
            //$total_pay = $now_order['balance_pay']+$now_order['payment_money']+$now_order['merchant_balance'];
            if($now_order['num']!=$unconsume_pass_num){
                $refund_money = $now_order['refund_money'];
            }else{
                $refund_money = $now_order['merchant_balance']+$now_order['balance_pay']+$now_order['payment_money'];
            }
            $now_order['refund_total'] = $refund_money;
        }else{
            $now_order['refund_total'] = $now_order['balance_pay']+$now_order['payment_money']+$now_order['merchant_balance'];
        }

        if($now_order['trade_info']){
            $trade_info_arr = unserialize($now_order['trade_info']);
            if($trade_info_arr['type'] == 'hotel'){
                $trade_hotel_info = D('Trade_hotel_category')->format_order_trade_info($now_order['trade_info']);
                $has_refund = $trade_hotel_info['has_refund'];
                $trade_refund = true;
                if($has_refund==1){
                    $trade_refund = false;
                }elseif($has_refund==2&&$now_order['add_time']+3600*$trade_hotel_info['refund_hour']>time()){
                    $trade_refund = false;
                }
                $trade_hotel_info['refund'] = $trade_refund;
                $this->assign('trade_hotel_info',$trade_hotel_info);
            }
        }

        // 获取填写的用户自定义字段信息

        $write_info = unserialize($now_order['write_field']);
        if ($write_info) {
            $write_list = array();
            foreach($write_info as $key=>$val){
                if(!empty($write_info[$key]['value'])){
                    $write_list[$key]['name'] = $val['name'];
                    $write_list[$key]['value'] = $val['value'];
                    $write_list[$key]['type'] = $val['type'];
                    if($write_info[$key]['type'] == 2){
                        $write_list[$key]['long'] = $val['long'];
                        $write_list[$key]['lat'] = $val['lat'];
                        $write_list[$key]['address'] = $val['address'];
                        $write_list[$key]['value'] = $val['value'].' '.$val['value-desc'];
                    }
                }
            }
            $this->assign('write_list', $write_list);
        }
        // 如果存在 获取对应规格
        if ($now_order['specifications_id']) {
            $specifications_info = M('Group_specifications')->where(array('specifications_id' => $now_order['specifications_id']))->find();
            if ($specifications_info) {
                $now_order['specifications_info'] = $specifications_info;
                $now_order['s_name'] = $now_order['s_name'] . '('.$specifications_info['specifications_name'].')';
            } else {
                $now_order['specifications_info'] = array();
            }
        }
        //分类下其他团购
        $category_group_list = D('Group')->get_grouplist_by_catId($now_group['cat_id'],$now_group['cat_fid'],6,true);
        foreach($category_group_list as $key=>$value){
            if($value['group_id'] == $now_group['group_id']){
                unset($category_group_list[$key]);
            } else {
                $get_spec_sale_count = D('Group_order')->get_spec_sale_count($value['group_id']);
                if ($get_spec_sale_count > 0) {
                    $category_group_list[$key]['sale_count'] = $get_spec_sale_count + intval($value['sale_count']);
                    $category_group_list[$key]['sale_txt'] = '已售' . $category_group_list[$key]['sale_count'];
                }
            }
        }
        $this->assign('category_group_list',$category_group_list);

        $lng_lat = D('User_long_lat')->getLocation($_SESSION['openid'], 0);
        $this->assign('lat',$lng_lat['lat']);
        $this->assign('lng',$lng_lat['long']);
        $this->assign('now_order',$now_order);
        $this->assign('now_group',$now_group);
        $this->assign('group_share_num',$group_share_num);
        $this->assign('is_shared',$is_shared);
        $this->assign('now_merchant',$now_merchant);

        $share = new WechatShare($this->config,$_SESSION['openid']);
        $this->sign_data = $share->get_wx_config();
        $this->assign('sign_data',$this->sign_data);

        $this->display();
    }
	
	public function search(){
        $keywords = safeValue($_REQUEST['key']);
        $nowCity = intval($_GET['now_city']);
        if ($nowCity > 0) {
            C('config.now_city', $nowCity);
        }
        
        if (!empty($keywords)) {
            //得到搜索的团购列表
            $group_return = D('Group')->get_group_list_by_keywords($keywords,'default', true,'',0,0,1);
            if ($group_return) {
                $group_return['group_total_page'] = $group_return['totalPage'];
                unset($group_return['totalPage']);
                unset($group_return['pagebar']);
            }
            // 搜索店铺列表
            $store_return = D('Merchant')->wap_search_storeList($keywords,'group');
        } else {
            $group_return = array();
            $store_return = array();
        }
        $this->assign('key', $keywords);
        $this->assign($group_return);
        $this->assign($store_return);
		$this->display();
	}
	public function eticket(){
        if(empty($this->user_session)){
            $this->error_tips('请先进行登录！');
        }
        if($_GET['path']=='coupon'){
            if($_GET['own_id']){
                $own_uid = $_GET['own_id'];
            }else{
                $own_uid = $this->user_session['uid'];
            }
        }else{
            $own_uid = $this->user_session['uid'];
        }
        $type = $_GET['type']??'';
        $now_order = D('Group_order')->get_order_detail_by_id($own_uid, intval($_GET['order_id']), true,false);

        $now_order['order_type'] = 'group';
        $laste_order_info=D('Tmp_orderid')->get_laste_order_info($now_order['order_type'],$now_order['order_id']);
        if(!$now_order['paid'] && !empty($laste_order_info)) {
            if ($laste_order_info['pay_type']=='weixin') {
                $redirctUrl = C('config.site_url') . '/wap.php?g=Wap&c=Pay&a=weixin_back&order_type='.$now_order['order_type'].'&order_id=' . $laste_order_info['orderid'];
                file_get_contents($redirctUrl);
                $now_order = D('Group_order')->get_order_detail_by_id($own_uid, intval($_GET['order_id']), true);
            }
        }
        $now_group = D('Group')->get_group_by_groupId($now_order['group_id'],'hits-setInc');
        if($now_order['is_group_combine']){
            //获取商品id
            $goods_ids = D('Group_combine_activity_order_goods')->where(['order_id'=>$now_order['order_id']])->getField('group_id',true);
            foreach ($goods_ids as $val){
                $group_store_list = D('Group_store')->get_store_list_by_groupId($val);
                $now_group['store_list'] = $now_group['store_list']?array_merge($now_group['store_list'],$group_store_list):$group_store_list;
            }
        }
        
        //店铺 列表
        $d_group = D('Group');
        $now_group_store = $d_group->get_group_by_groupId($now_order['group_id'], 'hits-setInc');
        //判断是否微信浏览器，
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);

        if ($long_lat) {
            $rangeSort = array();
            foreach ($now_group_store['store_list'] as &$storeValue) {
                $storeValue['Srange'] = getDistance($long_lat['lat'], $long_lat['long'], $storeValue['lat'], $storeValue['long']);
                $storeValue['range'] = getRange($storeValue['Srange'], false);
                $rangeSort[] = $storeValue['Srange'];
            }
            array_multisort($rangeSort, SORT_ASC, $now_group['store_list']);
            $this->assign('store_list', $now_group_store['store_list']);
        }
        if (empty($now_order)) {
            $this->error_tips('当前订单不存在');
        }
        if($now_group['pin_num']>0 && $now_order['single_buy']==0 && $now_order['status']<3 && $now_order['paid']){
            $my_group_join = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
            if(empty($my_group_join)&&$now_order['paid']==1){
                $this->error_tips('当前订单出错');
            }
        }
        if ($now_group['logo']) {
            $now_group['title_pic'] = $now_group['logo'];
        } elseif ($now_group['all_pic']) {
            $now_group['title_pic'] = $now_group['all_pic'][0]['image'];
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

        if($now_order['is_group_combine']){
            $no_use_count = $now_order['can_use_count'] - $now_order['combine_used_count']; 
        }

        if(($now_order['pass_array'] || ($now_order['pass_num'] && $now_order['pass_num']>1) || (C('config.baodi_ticket_url') && $now_order['custom_product_id']))&&!in_array($type,array(1,2))){
            $pass_ids = 1;
            $pass_array = D('Group_pass_relation')->get_pass_array($now_order['order_id']);
            //$pass_array = sortArray($pass_array, 'status');
        } else if ($now_order['group_pass']) {
            $pass_ids = 2;
            $pass_array = array(
                array(
                    'id' => -1,
                    'order_id' => $now_order['order_id'],
                    'group_pass' => isset($_GET['group_pass'])&&$_GET['group_pass']?$_GET['group_pass']:$now_order['group_pass'],
                    'status' => $now_order['status'] == 0 ? 0 : 1,
                    'staff_name' => $now_order['staff_name'] ? $now_order['staff_name'] : '',
                    'verify_time' => $now_order['verify_time'] ? $now_order['verify_time'] : '',
                    'used_count' => $now_order['combine_used_count'],
                    'no_use_count' => $no_use_count,
                )
            );
        }
        $give_submit_show = []; //1-显示转赠按钮，0-不显示
        foreach ($pass_array as $k=>$v){
            if($v['id']==-1){
                $pass_r=M('group_pass_relation')->where(['order_id'=>$now_order['order_id'],'group_pass'=>$v['group_pass']])->find();
                if($pass_r){
                    $pass_array[$k]['id'] = $pass_r['id'];
                    $pass_array[$k]['status'] = $pass_r['status'];
                }
            }
            $pass_array[$k]['give_status'] = 1;//可赠送
            if(C('customization.group_order_giving')==1) {
                if($now_order['tuna_type']==0&&$type == 1&&(($now_order['effective_type']==0&&$now_order['deadline_time']<time())||($now_order['effective_type']==1&&strtotime('+'.$now_order['deadline_time'].' day',$now_order['pay_time'])<time()))){
                    $pass_array[$k]['status'] = 3; //3-已过期
                }
                if ($now_order['num'] == 1 && $now_order['pass_array'] == 0) {
                    $pass = M('card_new_coupon_give')->where(['order_id' => $now_order['order_id']])->find();
                } else {
                    $pass = M('card_new_coupon_give')->where(['order_id' => $now_order['order_id'], 'pass_id' => $v['id']])->find();
                }
                $pass_array[$k]['give_status'] = 1;//可赠送
                if ($type == 1 && $_GET['own_id']) {
                    $pass_array[$k]['give_status'] = 1;//已领优惠不显示已赠送
                } elseif (!empty($pass) && $type == 2 && $_GET['own_id']) {
                    $pass_array[$k]['give_status'] = 0;//已赠送不可赠
                } elseif (!$_GET['own_id'] && !empty($pass)) {
                    $pass_array[$k]['give_status'] = 0;//已赠送不可赠
                } elseif (!$_GET['own_id'] && empty($pass)) {
                    $give_submit_show[$k] = 1;
                    $pass_array[$k]['give_status'] = 1;//可赠送
                } else {
                    $give_submit_show[$k] = 0;
                    $pass_array[$k]['give_status'] = 0;//已赠送不可赠
                }
                if ($v['status'] == 1) {
                    $give_submit_show[$k] = 0;
                }
            }
        }
        if($give_submit_show&&in_array(1,$give_submit_show)){
            $give_submit_show = 1;
        }
        $this->assign('give_submit_show', $give_submit_show);
        if($pass_ids == 1){
            $pass_id = $pass_array[$_GET['key_id']]['id'];
        }else{
            if($_GET['path'] == 'coupon'){
                $relation_data = array(
                    'order_id'=>$pass_array[0]['order_id'],
                    'group_pass'=>$pass_array[0]['group_pass'],
                    'status'=>$pass_array[0]['status'],
                );
                $relation_find = D('Group_pass_relation')->where($relation_data)->find();
                if($relation_find < 1){
                    $pass_id = D('Group_pass_relation')->add($relation_data);
                }else{
                    $pass_id = $relation_find['id'];
                }
            }else{
                $pass_id = 0;
            }
        }

        // 如果存在 获取对应规格
        if ($now_order['specifications_id']) {
            $specifications_info = M('Group_specifications')->where(array('specifications_id' => $now_order['specifications_id']))->find();
            if ($specifications_info) {
                $now_order['specifications_info'] = $specifications_info;
                $now_order['s_name'] = $now_order['s_name'] . '('.$specifications_info['specifications_name'].')';
            } else {
                $now_order['specifications_info'] = array();
            }
        }
        if($_GET['group_pass']){
            $group_pass = $_GET['group_pass'];
        }else{
            $group_pass = $now_order['group_pass'];
        }
        if(isset($_GET['path']) && $_GET['path']=="coupon" && $now_order['group_pass']){
            //$group_pass = $_GET['key_id']?$pass_array[$_GET['key_id']]['group_pass']:$group_pass;
            $now_order['real_orderid']= $now_order['real_orderid'].'_'.$this->user_session['uid']."_".$group_pass."_".$now_order['order_id']."_".$_GET['own_id'];
        }

        // 写入分享
        $share_data = array(
            'order_id' => $_GET['order_id'],
            'pass_id' => $pass_id,
            'uid' => $own_uid,
        );
        $find_one = D('Card_new_coupon_share')->where($share_data)->find();
        if(!empty($find_one)){
            $share_update['share_time'] = time();
            D('Card_new_coupon_share')->where($share_data)->save($share_update);
            $share_id = $find_one['id'];
        }else{
            $share_data['share_time'] = time();
            $share_id = D('Card_new_coupon_share')->add($share_data);
        }
        $user_one = D('User')->where(['uid'=>$own_uid])->find();
        $now_order['user_name'] = $user_one['nickname'];
        $now_order['share_id'] = $share_id;
        //分享用
        foreach ($pass_array as $key=>$item){
            $pass_array[$key]['share_id'] = $share_id;
        }
        $this->assign('group_pass',$group_pass);
        $this->assign('pass_array', $pass_array);
        $this->assign('now_order', $now_order);
        $this->assign('now_group', $now_group);
        $this->assign('owner',$this->user_session['uid']);

        $this->display();
	}
    public function order_detail(){
        if(empty($this->user_session)){
            $this->error_tips('请先进行登录！');
        }
        $is_gift = isset($_GET['is_gift']) ? intval($_GET['is_gift']) : 0;
        $otherrm = isset($_GET['otherrm']) ? intval($_GET['otherrm']) : 0;
        $otherrm && $_SESSION['otherwc'] = null;
        if($is_gift==1){
            $now_order = D('Group_order')->get_order_detail_by_id($this->user_session['uid'],intval($_GET['order_id']),true,false);
        }else{
            $now_order = D('Group_order')->get_order_detail_by_id($this->user_session['uid'],intval($_GET['order_id']),true,false);
        }
        $now_order['order_type'] = 'group';
        $laste_order_info=D('Tmp_orderid')->get_laste_order_info($now_order['order_type'],$now_order['order_id']);
        if(!$now_order['paid'] && !empty($laste_order_info)) {
            if ($laste_order_info['pay_type']=='weixin') {
                $redirctUrl = C('config.site_url') . '/wap.php?g=Wap&c=Pay&a=weixin_back&order_type='.$now_order['order_type'].'&order_id=' . $laste_order_info['orderid'];
                file_get_contents($redirctUrl);
                $now_order = D('Group_order')->get_order_detail_by_id($this->user_session['uid'], intval($_GET['order_id']), true);
            }
        }
        $now_group = D('Group')->where(array('group_id'=>$now_order['group_id']))->find();
        $database_merchant = D('Merchant');
        $now_merchant = $database_merchant->get_info($now_group['mer_id']);
        if ($now_merchant) {
            $merchant_image_class = new merchant_image();
            $images = explode(";", $now_merchant['pic_info']);
            $images = explode(";", $images[0]);
            $now_merchant['img'] = $merchant_image_class->get_image_by_path($images[0]);
        }
        $now_group['merchant_name'] = $now_merchant['name'];

        if(empty($now_order)){
            $this->error_tips('当前订单不存在');
        }
        if(empty($now_order['paid'])){
            $now_order['status_txt'] = '未付款';
        }else if(empty($now_order['third_id']) && $now_order['pay_type'] == 'offline'){
            $now_order['status_txt'] = '线下未付款';
        }else if(empty($now_order['status'])){
            if($now_order['tuan_type'] != 2){
                $now_order['status_txt'] = '未消费';
            }else{
                $now_order['status_txt'] = '未发货';
            }
        }else if($now_order['status'] == '1'){
            $now_order['status_txt'] = '待评价';
        }else if($now_order['status'] == '2'){
            $now_order['status_txt'] = '已完成';
        }else if($now_order['status'] == '3'){
            $now_order['status_txt'] = '已退款';
            $now_order['group_pass_txt'] = '退款订单无法查看';
        }else if($now_order['status'] == '7'){
            $now_order['status_txt'] = '未消费';
        }


        $uid = $this->user_session['uid'];
        $group_share_num = D('Group_share_relation')->get_share_num($uid,$now_order['order_id']);
        $is_shared = D('Group_share_relation')->check_share($uid,$now_order['order_id']);
        $pic = explode(';',$now_group['pic']);
        foreach($pic as &$v){
            $v = preg_replace('/,/','/',$v);
        }
        $now_group['pic'] = $pic;
        if($now_group['pin_num']>0 && $now_order['single_buy']==0 && $now_order['status']<3 && $now_order['paid']){
            $my_group_join = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
            if(empty($my_group_join)&&$now_order['paid']==1){
                $this->error_tips('当前订单出错');
            }
            $buyer = D('Group_start')->get_buyerer_by_order_id($now_order['order_id']);
            $robot_list = M('Robot_list')->where(array('mer_id'=>$now_order['mer_id']))->getField('id,robot_name,avatar');

            foreach ($buyer as &$v) {
                if($v['type']==1||$v['uid']!=$now_order['uid']){
                    if(empty($v['pay_time'])){
                        $v['pay_time'] = $buyer['last_time'];
                    }
                    $tmp_name = $v['nickname'];
                    if($v['type']==1){
                        $tmp_name = $robot_list[$v['uid']]['robot_name'];
                        $v['avatar'] = $robot_list[$v['uid']]['avatar'];
                    }
                    if(preg_match('/\d{3}\*{4}\d{4}/',$v['nickname'],$m)){
                        $v['nickname'] = '游客';
                    }
                    $strlen     = mb_strlen($tmp_name, 'utf-8');
                    $firstStr     = mb_substr($tmp_name, 0, 1, 'utf-8');
                    $lastStr     = mb_substr($tmp_name, -1, 1, 'utf-8');
                    $v['nickname'] =  $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($tmp_name, 'utf-8') - 1) : $firstStr . str_repeat("*", $strlen - 1) ;
                    //$v['avatar'] = $robot_list[$v['uid']]['avatar'];
                }
            }

            //$group_share_info = D('Group_start')->get_group_start_user_by_gid($my_group_join['id']);
            $end_time = $my_group_join['start_time'] + $now_group['pin_effective_time'] * 3600;
            $effective_time = $end_time - $_SERVER['REQUEST_TIME'];
            $efftime['h'] = floor($effective_time / 3600);
            $efftime['m'] = floor(($effective_time - $efftime['h'] * 3600) / 60);
            $efftime['s'] = $effective_time - $efftime['h'] * 3600 - $efftime['m'] * 60;

            if ($effective_time > 0) {
                $this->assign('effective_time', $efftime);
            }
            $end_time = $my_group_join['start_time']+$now_group['pin_effective_time']*3600;
            $effective_time= $end_time-$_SERVER['REQUEST_TIME'];
            if($effective_time<=0 && $my_group_join['status']==0){
                D('Group_start')->timeout($now_order['order_id']);
                $my_group_join['status'] = 2;
            }
            //$this->assign('effective_time',$effective_time);
            $this->assign('buyer',$buyer);
            $this->assign('my_group_join',$my_group_join);
        }else {
            if ($now_group['group_share_num'] == 0 && $now_group['open_now_num'] == 0 && $now_group['open_num'] == 0) {
                M('Group_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_share_group' => 2));
                $now_order['is_share_group'] = 2;
            } else if ($now_group['group_share_num'] != 0 && $now_group['group_share_num'] <= $group_share_num) {
                M('Group_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_share_group' => 2));
                $now_order['is_share_group'] = 2;
            } else if ($now_group['open_now_num'] <= $now_group['sale_count'] && $now_group['open_now_num'] != 0 && $now_group['group_share_num'] == 0) {
                M('Group_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_share_group' => 2));
                $now_order['is_share_group'] = 2;
            } else if ($now_group['open_num'] <= $now_group['sale_count'] && $now_group['open_num'] != 0 && $now_group['open_now_num'] == 0 && $now_group['group_share_num'] == 0) {
                M('Group_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_share_group' => 2));
                $now_order['is_share_group'] = 2;
            }
            if ($now_group['group_share_num'] > 0) {
                $share_user = D('Group_share_relation')->get_share_user($this->user_session['uid'], $now_order['order_id']);
                $this->assign('share_user', $share_user);
            }
        }
        //$now_order['coupon_price'] = D('Group_order')->get_coupon_info($now_order['order_id']);
        //$has_pay = $now_order['wx_cheap']+$now_order['merchant_balance']+$now_order['balance_pay']+$now_order['score_deducte']+$now_order['coupon_price']+$now_order['payment_money'];

        $pass_num = $now_order['pass_num'] > 1 ? intval($now_order['pass_num']) : 1; // 一份团购 对应生成核销券 份数
        $total_pass_num = $now_order['num'] * $pass_num; // 改订单产生的总 核销券 份数
        $pass=M('Group_pass_get')->where(['order_id'=>$now_order['order_id']])->count();
        if($pass<$now_order['num']){
            $now_order['give_status']=1;//可以赠送
        }else{
            $now_order['give_status']=0;//不可以赠送，已经赠完
        }
        if ($now_order['pass_array']) {
            $pass_array = D('Group_pass_relation')->get_pass_array($now_order['order_id']);
            $consume_num = D('Group_pass_relation')->get_pass_num($now_order['order_id'],1);
            $unconsume_pass_num = $total_pass_num-$consume_num;
            $this->assign('pass_array',$pass_array);
        }elseif($now_order['tuan_type']==2){
            $unconsume_pass_num = $total_pass_num;
        }else{
            $unconsume_pass_num = 1;
        }
        if($now_order['status']==6 || $now_order['status']==3){
            if($total_pass_num!=$unconsume_pass_num){
                $refund_money = $now_order['refund_money'];
            }else{
                $refund_money = $now_order['merchant_balance']+$now_order['balance_pay']+$now_order['payment_money'];
            }
            $now_order['refund_total'] = $refund_money;
        }else{
            $now_order['refund_total'] = $now_order['balance_pay']+$now_order['payment_money']+$now_order['merchant_balance'];
        }
        $pass_info = array(
            'total_pass_num' => $total_pass_num,
            'unconsume_pass_num' => $unconsume_pass_num,
        );
        $this->assign('pass_info',$pass_info);

        if($now_order['trade_info']){
            $trade_info_arr = unserialize($now_order['trade_info']);
            if($trade_info_arr['type'] == 'hotel'){
                $trade_hotel_info = D('Trade_hotel_category')->format_order_trade_info($now_order['trade_info']);
                $has_refund = $trade_hotel_info['has_refund'];
                $trade_refund = true;
                if($has_refund==1){
                    $trade_refund = false;
                }elseif($has_refund==2&&$now_order['add_time']+3600*$trade_hotel_info['refund_hour']>time()){
                    $trade_refund = false;
                }
                $trade_hotel_info['refund'] = $trade_refund;
                $this->assign('trade_hotel_info',$trade_hotel_info);
            }
        }

        // 获取填写的用户自定义字段信息

        $write_info = unserialize($now_order['write_field']);
        if ($write_info) {
            $write_list = array();
            foreach($write_info as $key=>$val){
                if(!empty($write_info[$key]['value'])){
                    $write_list[$key]['name'] = $val['name'];
                    $write_list[$key]['value'] = $val['value'];
                    $write_list[$key]['type'] = $val['type'];
                    if($write_info[$key]['type'] == 2){
                        $write_list[$key]['long'] = $val['long'];
                        $write_list[$key]['lat'] = $val['lat'];
                        $write_list[$key]['address'] = $val['address']??'';
                        $write_list[$key]['value'] = $val['value'].' '.$val['value-desc']??'';
                    }
                }
            }
            $this->assign('write_list', $write_list);
        }
        // 如果存在 获取对应规格
        if ($now_order['specifications_id']) {
            $specifications_info = M('Group_specifications')->where(array('specifications_id' => $now_order['specifications_id']))->find();
            if ($specifications_info) {
                $now_order['specifications_info'] = $specifications_info;
                $now_order['s_name'] = $now_order['s_name'] . '('.$specifications_info['specifications_name'].')';
            } else {
                $now_order['specifications_info'] = array();
            }
        }
        // 退款详情
        if ($now_order['refund_detail']) {
            $refund_detail = unserialize($now_order['refund_detail']);
            $this->assign($refund_detail);
        }

        $lng_lat = D('User_long_lat')->getLocation($_SESSION['openid'], 0);
        if($now_group['effective_type'] == 1){// 团购有效期 购买后多少天
            if($now_order['pay_time']){
                $now_group['deadline_time'] = $now_order['pay_time'] + $now_group['deadline_time']*86400;
            }else{
                $now_group['deadline_time'] = $now_order['add_time'] + $now_group['deadline_time']*86400;
            }
        }
        // 场次预约结束
        if($combine = M('Group_booking_appoint_order')->where(['order_id'=>$now_order['order_id']])->find()){
            $now_group['deadline_time'] = $combine['book_end_time'];
        }

        

        // 查看是否可取消
        $canCancel = true;
        if($now_group['cancel_type'] == 0){// 不可取消
            $canCancel = false;
        }elseif($now_group['cancel_type'] == 1){
            if($now_group['deadline_time'] - $now_group['cancel_hours']*3600  < time() ){
                $canCancel = false;
            }
        }elseif($now_group['cancel_type'] == 3){
            if($now_group['deadline_time'] < time() ){
                $canCancel = false;
            }
        }
        
        $this->assign('is_gift',$is_gift);
        $this->assign('canCancel',$canCancel);
        $this->assign('lat',$lng_lat['lat']);
        $this->assign('lng',$lng_lat['long']);
        $this->assign('now_order',$now_order);
        $this->assign('now_group',$now_group);
        $this->assign('group_share_num',$group_share_num);
        $this->assign('is_shared',$is_shared);
        $this->assign('now_merchant',$now_merchant);
		
		$page_num = $_GET['page_num'] ? $_GET['page_num']+1 : 0;
		$this->assign('page_num',$page_num);

        // 跳转新版详情
        if(in_array($now_group['group_cate'],['normal','course_appoint'])){
            $url = C('config.site_url').'/packapp/platn/pages/group/v1/groupDetail/index?group_id='.$now_group['group_id'];
        }elseif($now_order['store_id']){
            $url = C('config.site_url').'/packapp/platn/pages/store/v1/home/index?store_id='.$now_order['store_id'];
        }
        $this->assign('detail_url',$url);

        $give_submit_show = 0;   //1-显示转赠，0-不显示
        if(C('customization.group_order_giving')==1) {
            $give_submit_show = 1;
        }
		$this->assign('give_submit_show',$give_submit_show);
        
        $redemption_code_list = [];
        if($now_order['tuan_type']==3){
            $redemption_code_list = D('Group_redemption_code')->where(['order_id'=>$now_order['order_id']])->getField('code',true);
        }
        $this->assign('redemption_code_list',$redemption_code_list);
        $this->display();
    }
    /**
     * 获取团购浏览购买分享信息
     */
    public function group_record(){
        $group_id = $_GET['group_id'];
        $record = D('Group')->group_record_list($group_id);
        echo json_encode($record);
    }

    public function get_route() {
        if ($this->config['map_config'] == 'google' && $this->config['google_map_ak']) {
            $url = 'https://www.google.com/maps/embed/v1/directions?key='.$this->config['google_map_ak'].'&origin='.$_GET['origin'].'&destination='.$_GET['destination'];
        } else {
            $origin = urlencode('当前位置');
            $destination = $_GET['adress'];
            $url = "http://api.map.baidu.com/direction?origin=latlng:".$_GET['origin']."|name:".$origin."&destination=latlng:".$_GET['destination']."|name:".$destination."&mode=driving&region=100010000&src=baidu|jsapi&output=html";
			
			$url = 'https://map.baidu.com/mobile/webapp/search/search/qt=nav&sn=' . urlencode('1$$$$' . $_GET['origin'] . '$$当前位置$$$$$$') .'&en=' . urlencode('1$$$$' . $_GET['destination'] .'$$终点$$$$$$') . '&sc=&ec=/vt=map/?third_party=uri_api';
        }
        $this->assign('url',$url);
        $this->display();
    }
}
?>