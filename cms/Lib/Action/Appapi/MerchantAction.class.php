<?php
class MerchantAction extends BaseAction{
    public function around(){
        $list = D('Merchant')->get_merchants_by_long_lat($_POST['lat'], $_POST['lng'],2000);
        $this->returnCode(0,$list);
    }

    //增加商家推广关系
    public function add_merchant_user_relation(){
    	if(!empty($this->_uid)){
    		$store_id = $_POST['store_id'];
    		if($store_id > 0){
    			$now_store = D('Merchant_store')->get_store_by_storeId($_POST['store_id']);
    			$now_user = D("User")->get_user($this->_uid);
    			$find1 = M("Merchant_spread")->where(['uid'=>$this->_uid])->find();
    			$find2 = M("Merchant_spread")->where(['openid'=>$now_user['wxapp_openid']])->find();
    			$find3 = M("Merchant_spread")->where(['openid'=>$now_user['openid']])->find();
    			$find4 = M("User_spread")->where(['uid'=>$this->_uid])->find();
    			if(empty($find1) && empty($find2) && empty($find3) && empty($find4)){//不存在推广记录的情况下才可以推广成功哦
    				$data = [
    					'mer_id' => $now_store['mer_id'],
    					'openid' => $now_user['wxapp_openid'] ? $now_user['wxapp_openid'] : '',
    					'uid' => $this->_uid,
    					'spread_time' => time(),
    					'store_id' => $store_id
    				];
    				M("Merchant_spread")->add($data);
    			}
    		}
    	}
    	$this->returnCode(0,[]);
    }

    //	店铺详情
    public function shop(){
        $now_store = D('Merchant_store')->get_store_by_storeId($_POST['store_id']);
        if(empty($now_store)){
            $this->returnCode('20046001');
        }
        //得到当前店铺的评分
        $store_score = D('Merchant_score')->field('`score_all`,`reply_count`')->where(array('parent_id'=>$now_store['store_id'],'type'=>'2'))->find();

        $arr['now_store']['store_score'] = $store_score['reply_count'] ? number_format($store_score['score_all']/$store_score['reply_count'],1) : 5.0;
        $arr['now_store']['reply_count'] = number_format($store_score['reply_count']);
		
		//收藏
		$arr['now_store']['is_collect'] = false;
        if(!empty($this->_uid)){
        	$collection_info = D('Merchant_user_relation')->get_store_collect($this->_uid,$now_store['store_id']);
			if ($collection_info) {
				$arr['now_store']['is_collect'] = true;
			}
        }
		
		$lat = getFormatNumber($_POST['lat']);
		$lng = getFormatNumber($_POST['lng']);

        $arr['now_store']['mer_id'] = $now_store['mer_id'];
        $arr['now_store']['name'] = html_entity_decode($now_store['name']);
        $arr['now_store']['merchant_name'] = $now_store['merchant_name'];
        $arr['now_store']['phone'] = $now_store['phone'];
        $arr['now_store']['adress'] = html_entity_decode($now_store['adress']);
        $arr['now_store']['long'] = $now_store['long'];
		$arr['now_store']['lng'] = $now_store['long'];
        $arr['now_store']['lat'] = $now_store['lat'];
        $arr['now_store']['have_shop'] = $now_store['have_shop'] ? true : false;
        $arr['now_store']['have_meal'] = $now_store['have_meal'] ? true : false;
        $arr['now_store']['pay_in_store'] = $this->config['pay_in_store'] ? true : false;
        $arr['now_store']['all_pic'] = $now_store['all_pic'];
        $arr['now_store']['isverify'] = $now_store['isverify'] ? true : false;
        $arr['now_store']['permoney'] = $now_store['permoney'];
		
		
        $arr['now_store']['open_status'] = $now_store['is_business_open'] ? L_('营业中') : L_('不营业');
        
		if($lat && $lng){
			$distance = getDistance($lat, $lng, $now_store['lat'], $now_store['long']);
			$arr['now_store']['user_distance'] = L_('店铺距离您X1公里',getFormatNumber($distance/1000));
			if($this->config['baidu_map_ak']){
				$lnglatClass = new longlat();
				if($distance < 1.5){
					$walk_distance = $lnglatClass->getWalkInfo($lat, $lng, $now_store['lat'], $now_store['long']);
					if(isset($walk_distance['distance'])){
						$arr['now_store']['user_distance'] = L_('店铺距离您X1公里，步行约X2分钟',['X1'=>getFormatNumber($walk_distance['distance']/1000),'X2'=>intval($walk_distance['duration']/60)]);
					}
				}else{
					$driver_distance = $lnglatClass->getDriveInfo($lat, $lng, $now_store['lat'], $now_store['long']);
					if(isset($driver_distance['distance'])){
						$arr['now_store']['user_distance'] =  L_('店铺距离您X1公里，打车约X2分钟',['X1'=>getFormatNumber($driver_distance['distance']/1000),'X2'=>intval($driver_distance['duration']/60)]);
					}
				}
			}
		}
		
        $arr['now_store']['permoney'] = $now_store['permoney'];
		
		$arr['store_list_url'] = $this->config['site_url'] . '/wap.php?c=My_card&a=merchant_store&mer_id=' . $now_store['mer_id'];
		
		//餐饮链接
		if($arr['now_store']['have_meal']){
			$arr['now_store']['meal_url'] = $this->config['site_url'] . '/wap.php?c=Foodshop&a=shop&store_id=' . $now_store['store_id'];
		}
		
		//快店
		$arr['now_store']['shop_url'] = get_base_url('pages/store/v1/home/index?store_id=',true) . $now_store['store_id'];
		if($now_store['have_shop']){
			$shop_info = M(_view('Merchant_store_shop'))->where(['store_id'=> $now_store['store_id']])->find();
			if($shop_info){
				$arr['now_store']['have_shop_takeself'] = in_array($shop_info['deliver_type'],[2,3,4]) ? true : false;
				$arr['now_store']['have_shop_deliver'] = in_array($shop_info['deliver_type'],[0,1,3,4]) ? true : false;
			}
		}
		
		//到店付
		if($arr['now_store']['pay_in_store']){
			$arr['now_store']['pay_url'] = $this->config['site_url'] . '/wap.php?c=My&a=pay&store_id=' . $now_store['store_id'];
		}
		
		//分类名称
        $store_cat = M(_view('merchant_category'))->field(array('cat_id','cat_fid','cat_name'))->where(['cat_id'=> $now_store['cat_id']])->find();
        $arr['now_store']['cat_name'] = $store_cat['cat_name'] ?? '';
		
		//会员
		$store_card = D('Card_new')->get_card_by_mer_id($now_store['mer_id']);
		$arr['now_store']['have_card'] = ($store_card && $store_card['self_get'] == 1 && $store_card['status'] == 1) ? true : false;
		if($arr['now_store']['have_card']){
			$arr['now_store']['card_url'] = $this->config['site_url'] . '/wap.php?c=My_card&a=merchant_card&mer_id=' . $now_store['mer_id'];
			$arr['now_store']['card_info']['have_card'] = false;
			if($this->_uid){
				$card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->_uid, $now_store['mer_id']);
				if($card_info){
					$arr['now_store']['card_info'] = [
						'have_card'=> true,
						'nickname'=> $card_info['nickname'],
						'score'=> $card_info['card_score'],
						'money'=> $card_info['card_money'] + $card_info['card_money_give'],
					];
				}
			}
		}

		//营业时间
		$week = array(L_('周日'),L_('周一'),L_('周二'),L_('周三'),L_('周四'),L_('周五'),L_('周六'));
		$time_arr = D('Merchant_store_open_time')->get_one_time_by_store($now_store['store_id']);
		$arr['now_store']['time_arr'] = empty($time_arr) ? array() : $time_arr;	
		$arr['now_store']['time'] = '';
		
		//如果每天营业时间一样，则返回前端一个时间
		$open_time_same = true;
		$last_open_time = '';
		foreach($arr['now_store']['time_arr'] as $key => $value){
			if($last_open_time != '' && $value['time_show'] != $last_open_time){
				$open_time_same = false;
				break;
			}
			$last_open_time = $value['time_show'];
		}
		if($open_time_same){
            $arr['now_store']['time'] = $now_store['is_business_open'] ? $last_open_time : '';
			$arr['now_store']['time_arr'] = [L_('每天') . ' ' . $last_open_time];
		}else{
			foreach($arr['now_store']['time_arr'] as $key => $value){
				if(date("w") == $value['week']){
					$arr['now_store']['time'] = $value['week_show'] . ' ' . $value['time_show'];
				}
				$arr['now_store']['time_arr'][$key] = $value['week_show'] . ' ' . $value['time_show'];
			}
		}
		
		//团购列表
		$arr['store_group_list'] = [];
		if($now_store['have_group']){
			$store_group_list = D('Group')->get_store_group_list($now_store['store_id'],0,true);
			$arr['store_group_list_name'] = '本店'.$this->config['group_alias_name'];
			
			if($store_group_list){
                $group_list = [];
				foreach ($store_group_list as $v) {
					if($v['trade_type'] != 'hotel'){
						$tmp['group_id'] =$v['group_id'] ;
						$tmp['group_name'] =$v['group_name'] ;
						$tmp['price'] =$v['price'] ;
						$tmp['wx_cheap'] =$v['wx_cheap'] ;
						$tmp['old_price'] =$v['old_price'] ;
						$tmp['pin_num'] =$v['pin_num'] ;
						$tmp['sale_count'] =$v['sale_count']+$v['virtual_num'] ;
						$tmp['list_pic'] =$v['list_pic'];
						$tmp['intro'] =empty($v['intro'])?'':html_entity_decode($v['intro']);
						$tmp['url'] = $this->config['site_url'] . '/wap.php?c=Groupnew&a=detail&group_id=' . $v['group_id'];
                        $group_list[] = $tmp;
					}
				}
			}
        }
		if (!empty($group_list)) {
		    foreach ($group_list as $_key =>  $_group) {
                $group_store_where = array(
                    'group_id' => array('eq', $_group['group_id']),
                    'store_id' => array('eq', $now_store['store_id']),
                    'package_id' => array('neq', 0),
                    'on_sale'=>array('eq',1)
                );
                $group_store = D('Group_store')->field('`package_id`')->where($group_store_where)->find();
                if (!empty($group_store)) {
                    $foodshop_goods_package = D('Foodshop_goods_package')->field('`name`,`image`')->where(['id' => $group_store['package_id']])->find();
                    if (!empty($foodshop_goods_package)) {
                        $group_list[$_key]['group_name'] = $foodshop_goods_package['name'];
                        $group_list[$_key]['intro'] =empty($foodshop_goods_package['note'])?'':$foodshop_goods_package['note'];
                        $group_list[$_key]['list_pic'] = empty($_group['list_pic'])?replace_file_domain($foodshop_goods_package['image']):$_group['list_pic'];
                    }
                    $group_list[$_key]['is_package'] = 1;//团购卷绑定套餐
                } else {
                    $group_list[$_key]['is_package'] = 0;//团购卷没有绑定套餐
                }
            }
        }
		if (!empty($group_list)) {
            $arr['store_group_list'] = $group_list;
        }
        // 预约
        $database_appoint = D('Appoint');
        $appointList = $database_appoint->get_store_appoint_list($now_store['store_id'], 30, true);
        $arr['store_appoint_list_name'] = '本店'.$this->config['appoint_alias_name'];
		$arr['store_appoint_list'] = [];
        if($appointList){
            foreach ($appointList as $val) {
                if ($val['payment_status']==1) {
                    // 显示应支付金额
                    $pay_price = getFormatNumber($val['payment_money']);
                    // 显示
                    $txt_tip = '预付金';
                } else {
                    // 显示应支付金额
                    $pay_price = getFormatNumber($val['appoint_price']);
                    // 显示
                    if (0==$val['is_appoint_price']) {
                        if (floatval($pay_price)<=0) {
                            $pay_price = '面议';
                        }
                        $txt_tip = '一口价';
                    } else {
                        $txt_tip = '一口价';
                    }
                }
                $appoint = [
                    'appoint_name' => $val['appoint_name'],
                    'list_pic' => $val['list_pic'],
                    'appoint_content' => $val['appoint_content'],
                    'appoint_type' => $val['appoint_type'],
                    'pay_price' => $pay_price,
                    'txt_tip' => $txt_tip,
                    'appoint_sum' => $val['appoint_sum'],
                    'url' => $val['url'],
                ];
                $arr['store_appoint_list'][] = $appoint ;
            }
        }


        //餐饮
		$arr['store_meal_recommend_list'] = [];
		if($arr['now_store']['have_meal']){
		    if(!C('config.open_meal_v2')){
				$tmp_goods_list = M('Foodshop_goods')->field(true)->where(array('store_id' => $now_store['store_id'], 'status' => 1, 'is_hot' => 1))->limit(10)->select();
				if($tmp_goods_list){
                 	$goods_image_class = new foodshop_goods_image();
                 	foreach ($tmp_goods_list as $row) {
                 		$tmp_pic_arr = explode(';', $row['image']);
                 		foreach ($tmp_pic_arr as $key => $value) {
                 			$row['pic_arr'][$key]['title'] = $value;
                 			$row['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value);
                 		}
                 		$goods_list[] = [
                 			'name' => $row['name'],
                 			'image' => thumb_img($row['pic_arr'][0]['url']['image'],240,240,'fill'),
                 		];
                 	}
				}else{
                 	$goods_list = array();
				}
				$arr['store_meal_recommend_url'] = $this->config['site_url'] . '/wap.php?c=Foodshop&a=show_menu&store_id=' . $now_store['store_id'];
            }else{
                $goods_list = D('Foodshop_goods_library')->getRecommendListByStoreId($now_store['store_id']);
                $arr['store_meal_recommend_list'] = $goods_list;
            }
		}
		
		//评论
		$arr['reply_count'] = D('Reply')->get_store_reply_list_count($now_store['store_id']);
        $arr['now_store']['reply_count'] = $arr['reply_count'];
		if($arr['reply_count'] > 0){
			$arr['reply_list'] = D('Reply')->get_store_reply_list($now_store['store_id'],3);
			foreach($arr['reply_list'] as $key=>$value){
				$arr['reply_list'][$key]['pic'] = array_column($value['pics'],'s_image');
			}
		}
		
		//商家信息
		$arr['mer_store_count'] = M(_view('merchant_store'))->where(array('mer_id'=>$now_store['mer_id'], 'status' => 1))->count();	//店铺数量
		
		
		//猜你喜欢
		if($lat && $lng){
			$city_id = intval($_POST['now_city']) ? : $now_store['city_id'];
			$city_id && $where['city_id'] = $city_id;
			$where['store_id'] = ['neq', $now_store['store_id']];
			$where['store_cat_id'] = $now_store['cat_id'];
			$_POST['idstr'] && $where['id'] = array('not in',trim($_POST['idstr'],','));
			
			//商家小程序入口
			if($_POST['wxapp_own_mer_id']){
				$where['mer_id'] = $_POST['wxapp_own_mer_id'];
			}
			
			$arr['guess_list'] = D('Plat_recommend')->get_limit_list_page_wap($where,$lat,$lng,1,10,0,1)['list'];
		}else{
			$arr['guess_list'] = [];
		}
		
		
		//组合成数组
		$arr['btn_arr'] = [];
		if($arr['now_store']['pay_in_store']){
			$arr['btn_arr'][] = [
				'name' => L_('买单'),
				'desc' => '',
				'pic' => $this->config['site_url'] . '/static/images/store/pay_in_store.png',
				'url' => $arr['now_store']['pay_url'],
			];
		}
		if($arr['now_store']['have_shop_deliver']){
			$arr['btn_arr'][] = [
				'name' => $this->config['shop_alias_name'],
				'desc' => $arr['now_store']['open_status'],
				'pic' => $this->config['site_url'] . '/static/images/store/shop_deliver.png',
				'url' => $arr['now_store']['shop_url'],
			];
		}
		if($arr['now_store']['have_shop_takeself']){
			$arr['btn_arr'][] = [
				'name' => L_('自取'),
				'desc' => '',
				'pic' => $this->config['site_url'] . '/static/images/store/shop_takeself.png',
				'url' => $arr['now_store']['shop_url'],
			];
		}
		// if($arr['now_store']['have_meal']){
		// 	$foodshop_info = M(_view('Merchant_store_shop'))->where(['store_id'=> $now_store['store_id']])->find();
		// 	if($foodshop_info){
		// 		$arr['btn_arr'][] = [
		// 			'name' => $this->config['meal_alias_name'],
		// 			'desc' => '',
		// 			'pic' => $this->config['site_url'] . '/static/images/store/shop_meal.png',
		// 			'url' => $arr['now_store']['meal_url'],
		// 		];
		// 	}
		// }
		

		// 餐饮预订
		if($arr['now_store']['have_meal']&&C('config.open_meal_v2')){
			$foodshop_info = M('Merchant_store_foodshop')->where(['store_id'=> $now_store['store_id']])->find();
			if($foodshop_info['is_book']){
				if($foodshop_info['book_type']==1){
					$name = L_('提前选桌');
					$url = $this->config['site_url'] . D('House_village')->base_url .'pages/foodshop/order/onLineBookSeat?store_id='.$now_store['store_id'] . '&store_name=' . $now_store['name'];
				}else{
					$name = L_('提前选菜');
					$url = $this->config['site_url'] . D('House_village')->base_url .'pages/foodshop/store/storeMenu?order_from=3&scan=0&store_id='.$now_store['store_id'] . '&store_name=' . $now_store['name'];
				}
				$arr['btn_arr'][] = [
					'name' => $name,
					'desc' => '',
					'pic' => $this->config['site_url'] . '/static/images/store/shop_meal.png',
					'url' => $url,
				];
				
			}
		}

		if($arr['now_store']['have_card']){
			$arr['btn_arr'][] = [
				'name' => L_('会员卡'),
				'desc' => '',
				'pic' => $this->config['site_url'] . '/static/images/store/mer_card.png',
				'url' => $arr['now_store']['card_url'],
			];
		}

		$store_slider_list = M('Merchant_store_slider')->where(['store_id'=>$now_store['store_id'], 'status'=>'1'])->order('sort DESC')->select();
		foreach($store_slider_list as $value){
			$arr['btn_arr'][] = [
				'name' => $value['name'],
				'desc' => '',
				'pic' => strpos($value['pic'], '/upload') === false  ? replace_file_domain('/upload/slider/' . $value['pic']) : replace_file_domain($value['pic']),
				'url' => $value['url'],
			];
		}
		

        /* 粉丝行为分析 */
        D('Merchant_request')->add_request($now_store['mer_id'],array('group_hits'=>1));
        /*人气自增*/
        M('Merchant_store')->where(array('store_id'=>$now_store['store_id']))->setInc('hits');
        /* 粉丝行为分析 */
        $this->behavior(array('mer_id'=>$now_store['mer_id'],'store_id'=>$now_store['store_id']));

        if ($_SESSION['openid'] && $services = D('Customer_service')->where(array('mer_id' => $now_store['mer_id']))->select()) {
            $key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' => $_SESSION['openid']), $this->config['im_appkey']);
            $kf_url = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . $_SESSION['openid'] . '&key=' . $key . '#serviceList_' . $now_store['mer_id'];
        }

        // 分享信息
        $arr['share_info']['title'] = $arr['now_store']['name'];
        $arr['share_info']['desc'] = L_('点击查看更多店铺精彩推荐');
//        if(!empty($now_store['logo'])){
//        	$arr['share_info']['image'] = $now_store['logo'];
//		}elseif(!empty($now_store['pic_info'])){
        	$arr['share_info']['image'] = thumb_img(array_shift($now_store['all_pic']),'200','200');
//		}
		$this->returnCode(0,$arr);
    }
	
	//店铺评论
	public function store_reply(){
		if($_POST['page'] < 1){
			$_POST['page'] = 1;
		}
		$reply_list = D('Reply')->get_store_reply_list($_POST['store_id'], (($_POST['page'] - 1) * $_POST['pagesize']) . ',' . $_POST['pagesize']);
		if(!$reply_list){
			$reply_list = [];
		}
		$this->returnCode(0,$reply_list);
	}
	
	//店铺收藏
	public function store_collection(){
        $info = D('Merchant_user_relation')->get_store_collect($this->_uid,$_POST['store_id']);
        if($info){
        	if(D('Merchant_user_relation')->where(array('store_id'=>intval($_POST['store_id']),'uid'=>$this->_uid,'type'=>'store'))->delete()){
        		$this->returnCodeOk(L_('取消收藏成功'));
        	}
        }
        $res = D('Merchant_user_relation')->data(array('store_id'=>$_POST['store_id'],'uid'=>$this->_uid,'type'=>'store'))->add();
        if($res){
			$this->returnCodeOk(L_('收藏成功'));
        }else{
			fdump([D(),$this]);
			$this->returnCodeError(L_('收藏失败请重试'));
        }
	}

    //店铺列表 参照团购列表做
    public function store_list(){
        //判断排序信息
        $sort_id = 'juli';
        $long_lat['lat'] = $_POST['lat'];
        $long_lat['long'] = $_POST['lng'];
        if(empty($long_lat)) {
            $sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
        }
        $_GET['page'] = $_POST['page'];
        $store_name = I('store_name',false);
        //根据分类信息获取分类
        $return = D('Group')->wap_get_storeList_by_catid('','','',$sort_id, $long_lat['lat'], $long_lat['long'], 0,$store_name);
        foreach($return['store_list'] as &$storeValue){
			$storeValue['juli_wx'] = $storeValue['juli'];
            $storeValue['range_txt'] = $this->wapFriendRange($storeValue['juli']);
            $group_list = S('wap_store_group_wxapi_'.$_POST['page'].$storeValue['store_id']);
            if(empty($group_list)){
                $group_list = D('Group')->get_single_store_group_list($storeValue['store_id'],0,true,true);
				foreach($group_list as $groupKey=>$groupValue){
					if($groupValue['pin_num'] > 0){
						unset($group_list[$groupKey]);
					}
					if($_POST['Device-Id'] == 'wxapp' && $groupValue['trade_type'] == 'hotel'){
						unset($group_list[$groupKey]);
					}
                }
                S('wap_store_group_wxapi_'.$_POST['page'].$storeValue['store_id'],$group_list,360);
            }else{
                foreach($group_list as $groupKey=>$groupValue){
					if($groupValue['pin_num'] > 0){
						unset($group_list[$groupKey]);
					}
                    if($groupValue['end_time'] < $_SERVER['REQUEST_TIME']){
                        unset($group_list[$groupKey]);
                    }
					if($_POST['Device-Id'] == 'wxapp' && $groupValue['trade_type'] == 'hotel'){
						unset($group_list[$groupKey]);
					}
                }
            }
            $storeValue['group_list'] = array_values($group_list);
            $storeValue['group_count'] = count($group_list);
            if(empty($storeValue['group_count'])){
                unset($storeValue);
            }
        }
        $return['style'] = 'store';
        $return['now_page'] = $_POST['page']?$_POST['page']:1;

        $arr = $return;
        /* 粉丝行为分析 */
        $this->behavior(array('model'=>'Group_index'));

        $this->returnCode(0,$arr);
    }
	
	public function merchant_store(){
		//店铺列表
        $tmp_store_list = D('Merchant_store')->get_store_list_by_merId($_REQUEST['mer_id']);
		$store_list = [];
		if($tmp_store_list){
	        foreach($tmp_store_list as $k=>&$v){
				$store_list[] = [
					'store_id' => $v['store_id'],
					'name' => $v['name'],
					'shop_address' => $v['area_name'] . $v['adress'],
					'phone' => $v['phone'],
				];
	        }
        }
        $this->returnCode(0, $store_list);
    }

    /*粉丝行为分析、统计*/
    public function behavior($param=array(),$extra_param=false){
        $openid = $_SESSION['openid'];

        if(empty($param) || empty($openid)){
            return false;
        }

        if(empty($param['model'])){
            $param['model'] = MODULE_NAME.'_'.ACTION_NAME;
        }

        $database_behavior = M('Behavior');

        $data_behavior = $param;
        $data_behavior['openid'] = $openid;
        $data_behavior['date'] = $data_behavior['last_date'] = $_SERVER['REQUEST_TIME'];
        $database_behavior->data($data_behavior)->add();
    }
}
?>