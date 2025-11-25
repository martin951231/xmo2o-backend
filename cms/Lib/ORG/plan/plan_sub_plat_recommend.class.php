<?php
/*平台推荐店铺 子计划任务*/
class plan_sub_plat_recommend extends plan_base{
	public function runTask($param = array()){
		$now_time=time();
		
		if(empty($param['store_id'])){
			return true;
		}
		
		$store_id = $param['store_id'];
		
		//店铺信息
		$now_store = M('Merchant_store')->where(['store_id'=>$store_id])->find();
		
		//店铺状态不正常
		if(empty($now_store) || $now_store['status'] != 1){
			M('Plat_recommend')->where(array('store_id'=>$store_id))->delete();
			return true;
		}
		
		$mer_id = $now_store['mer_id'];
		
		//店铺原推荐
		$plat_recommend = M('Plat_recommend')->where(['store_id'=>$store_id])->find();


		
		//商家ID
		$now_merchant = M('Merchant')->where(['mer_id'=>$mer_id])->find();
		if(empty($now_merchant) || $now_merchant['status'] != 1){
			M('Plat_recommend')->where(array('store_id'=>$store_id))->delete();
			return true;
		}
		
		//店铺所在商圈
		$store_address = '';
		if($now_store['circle_id']){
			$now_circle = M('Area')->where(array('area_id'=>$now_store['circle_id']))->find();
			$store_address = '['.$now_circle['area_name'].']'.$now_store['adress'];
		}else if($now_store['area_id']){
			$now_circle = M('Area')->where(array('area_id'=>$now_store['area_id']))->find();
			$store_address = '['.$now_circle['area_name'].']'.$now_store['adress'];
		}
		
		
		$plat_recommend_type = $now_store['plat_recommend_type'];
		$plat_recommend_value = explode('_',$now_store['plat_recommend_info']);
		
		
		$plat_recommend_data = array(
			'province_id' 		=> $now_store['province_id'],
			'city_id' 			=> $now_store['city_id'],
			'area_id' 			=> $now_store['area_id'],
			'store_cat_id' 		=> $now_store['cat_id'],
			'store_cat_fid' 	=> $now_store['cat_fid'],
			'store_id' 			=> $store_id,
			'store_name' 		=> html_entity_decode($now_store['name']),
			'store_sort' 		=> $now_store['sort'],
			'mer_id' 			=> $mer_id,
			're_type' 			=> $plat_recommend_type,
			'is_brand' 			=> $now_merchant['isverify'],
			'address' 			=> $store_address,
			'lng' 				=> $now_store['long'],
			'lat' 				=> $now_store['lat'],
			'last_build_time' 	=> $now_time,
			'pic' 				=> '',
			'star' 				=> '',
			'sale_person' 				=> '',
			'sale_count' 				=> '',
			'business_label' 				=> '',
			'label' 				=> '',
			'price' 				=> '',
			'old_price' 				=> '',
			'price_up' 				=> 0,
			'info' 				=> '',
			'tip' 				=> '',
			'price_1' 				=> 0,
			'price_2' 				=> 0,
			'deliver_time' 				=> '',
		);
		
		//猜你喜欢 新版多业务 没有开启的业务不展示
		$index_guess_like_arr  = D('Config_data')->get_index_guess_like(true);
		$index_guess_like_type = array_column($index_guess_like_arr, 're_type');

		//店铺
		if($plat_recommend_type == 'store'){

			$plat_recommend_data['re_id'] 	= $store_id;
			$plat_recommend_data['name'] 	= html_entity_decode($now_store['name']);
			
			// 图片
			$store_image_class = new store_image();
			$images = $store_image_class->get_allImage_by_path($now_store['pic_info']);
			//fdump($images,'store_images',true);
			$plat_recommend_data['pic'] = $images ? str_replace(file_domain(),'',array_shift($images)) : '';

			if ($now_store['logo']) {
				$plat_recommend_data['pic'] = replace_file_domain($now_store['logo']);
			}
			
			// 店铺评分
			$now_store_score = M('Merchant_score')->where(array('type'=>2,'parent_id'=>$store_id))->find();
			$plat_recommend_data['star']    = $now_store_score && $now_store_score['reply_count'] ? round($now_store_score['score_all'] / $now_store_score['reply_count'],1) : 5.0;
			
			//业务标签
			$business_label_arr = array();
			if($now_store['have_group']){
				$business_label_arr[] = msubstr(C('config.group_alias_name'),0,1,false);
			}
			if($now_store['have_shop']){
				$business_label_arr[] = msubstr(C('config.shop_alias_name'),0,1,false);
			}
			if($now_store['have_meal']){
				$business_label_arr[] = msubstr(C('config.meal_alias_name'),0,1,false);
			}
			$plat_recommend_data['business_label'] = implode(',',$business_label_arr);
			
			
			if($plat_recommend_value[0] == 'cash' && $now_store['discount_txt']){	//推荐优惠买单
				$discount_txt_arr = unserialize($now_store['discount_txt']);
				if($discount_txt_arr['discount_type'] == '1'){
					if($discount_txt_arr['discount_percent'] != 0 && $discount_txt_arr['discount_percent'] != 10){
						$plat_recommend_data['tip'] = '买单'.str_replace('.','',$discount_txt_arr['discount_percent']).'折';
					}
				}else if($discount_txt_arr['discount_type'] == '2'){
					if($discount_txt_arr['condition_price'] != 0 && $discount_txt_arr['condition_price'] != $discount_txt_arr['minus_price']){
						$plat_recommend_data['tip'] = '买单满'.$discount_txt_arr['condition_price'].'元减'.$discount_txt_arr['minus_price'].'元';
					}
				}
			}else if($plat_recommend_value[0] == 'group'){	//推荐团购
				if($plat_recommend_value[1]){
					$plat_recommend_group = D('Group')->field('`group_id`,`name` AS `group_name`,`price` AS `group_price`')->where(array('group_id'=>$plat_recommend_value[1]))->find();
				}else{
					$condition_where_plat_recommend_group = "`g`.`group_id`=`gs`.`group_id` AND `gs`.`store_id`='$store_id' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time'";
					$plat_recommend_group = D('')->field('`g`.`group_id`,`g`.`name` AS `group_name`,`g`.`price` AS `group_price`')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gs'))->where($condition_where_plat_recommend_group)->order('`g`.`sort` DESC,`g`.`group_id` DESC')->find();
				}
				
				if($plat_recommend_group){
					$plat_recommend_data['tip'] = getFormatNumber($plat_recommend_group['group_price']) . '元 | '.html_entity_decode($plat_recommend_group['group_name']);
				}
			}else if($plat_recommend_value[0] == 'description'){	//一句话描述
				if($plat_recommend_value[1]){
					$plat_recommend_data['tip'] = html_entity_decode($plat_recommend_value[1]);
				}
			}

			if(!in_array('store', $index_guess_like_type)){
				M('Plat_recommend')->where(array('store_id'=>$store_id))->delete();
				return true;
			}
		}else if($plat_recommend_type == 'shop'){	//快店
			
			$plat_recommend_data['re_id'] 	= $store_id;
			$plat_recommend_data['name'] 	= html_entity_decode($now_store['name']);
	
	
			$_POST['store_id'] = $store_id;
			$_POST['now_lang'] = 'chinese';
			$_POST['data_is_return'] = true;
			$return_arr['result'] = R('Appapi/Shop_new/ajax_shop');
			
	
			$plat_recommend_data['star'] = $return_arr['result']['star'];	// 店铺评分
			$plat_recommend_data['pic'] 	= str_replace(file_domain(), '', $return_arr['result']['origin_image']);
			$plat_recommend_data['sale_count'] = $return_arr['result']['month_sale_count'];	// 销售总数

			// 配送名称
			$plat_recommend_data['delivery_name'] = L_('客户自提');
			$plat_recommend_data['delivery_type'] = $return_arr['result']['deliver_type'];	// 配送方式

			if($return_arr['result']['delivery']){
	            if (in_array($plat_recommend_data['delivery_type'], [1,4])) {
	                $plat_recommend_data['delivery_name'] = L_('商家配送');
	            }elseif(in_array($plat_recommend_data['delivery_type'], [0,3])){
	                $plat_recommend_data['delivery_name'] = C('config.deliver_name') ?: L_('平台配送');
	            }elseif(in_array($plat_recommend_data['delivery_type'], [5])){
	                $plat_recommend_data['delivery_name'] = L_('快递配送');
	            }

				$plat_recommend_data['price_1'] = $return_arr['result']['delivery_price'];	// 起送价
				$plat_recommend_data['price_2'] = $return_arr['result']['delivery_money'];	// 配送费
				$plat_recommend_data['deliver_time'] = $return_arr['result']['delivery_time'].$return_arr['result']['delivery_time_type'];	//配送时间
			}
			
			if($return_arr['result']['coupon_list_simple']){
				$plat_recommend_data['info'] = json_encode($return_arr['result']['coupon_list_simple']);
			}
			fdump($plat_recommend_data,'11111',1);
			if($return_arr['result']['is_close'] || !in_array('shop', $index_guess_like_type)){
				M('Plat_recommend')->where(array('store_id'=>$store_id))->delete();
				return true;
			}
		}else if($plat_recommend_type == 'group'){	//团购
			if($plat_recommend_value[0] == 'group'&&$plat_recommend_value[1]){
				$plat_recommend_group = D('Group')->field('`group_id`,`name` AS `group_name`,`price` AS `group_price`,`pic` AS `group_pic`,`score_mean`,`old_price`,`sale_count`,`virtual_num`')->where(array('group_id'=>$plat_recommend_value[1],'status'=>1,'group_cate'=>'normal','end_time'=>['gt',$now_time]))->find();
			}else{
				$condition_where_plat_recommend_group = "`g`.`group_id`=`gs`.`group_id` AND `g`.`group_cate`='normal' AND `gs`.`store_id`='$store_id' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time'";
				$plat_recommend_group = D('')->field('`g`.`group_id`,`g`.`virtual_num`,`g`.`name` AS `group_name`,`g`.`price` AS `group_price`,`g`.`pic` AS `group_pic`,`g`.`score_mean`,`g`.`old_price`,`g`.`sale_count`')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gs'))->where($condition_where_plat_recommend_group)->order('`g`.`sort` DESC,`g`.`group_id` DESC')->find();
			}
			if($plat_recommend_group){
			    $d_group_order = D('Group_order');
				//fdump($plat_recommend_group,'plat_recommend_group',true);
				$group_image_class = new group_image();
				
				$plat_recommend_data['re_id'] 	= $plat_recommend_group['group_id'];
				$plat_recommend_data['name'] 	= html_entity_decode($plat_recommend_group['group_name']);
				
				$tmp_pic_arr = explode(';',$plat_recommend_group['group_pic']);
				$plat_recommend_data['pic'] 	= str_replace(file_domain(),'',$group_image_class->get_image_by_path($tmp_pic_arr[0],'s'));
				$plat_recommend_data['star'] 	= $plat_recommend_group['score_mean'];


                // 查询规格售出数量
                $get_spec_sale_count = $d_group_order->get_spec_sale_count($plat_recommend_group['group_id']);
                if (intval($get_spec_sale_count) > 0) {
                    $plat_recommend_group['sale_count'] = intval($get_spec_sale_count) + intval($plat_recommend_group['sale_count']);
                }
                $plat_recommend_group['sale_count'] += intval($plat_recommend_group['virtual_num']);

				$plat_recommend_data['sale_count'] 	= $plat_recommend_group['sale_count'];
				
				$plat_recommend_data['price'] 	= $plat_recommend_group['group_price'];
				$plat_recommend_data['old_price'] 	= $plat_recommend_group['old_price'];
				
				//已拼人数
				$plat_recommend_data['sale_person'] = M('Group_order')->group('uid')->where(array('group_id'=>$plat_recommend_value[1]))->count();
				$plat_recommend_data['sale_person'] = $plat_recommend_data['sale_person'] ? $plat_recommend_data['sale_person'] : '';
				$plat_recommend_data['sale_count'] = $plat_recommend_data['sale_count'] ? $plat_recommend_data['sale_count'] : 0;
			}else{
				fdump(D(''),'group_recommend',true);
				M('Plat_recommend')->where(array('store_id'=>$store_id))->delete();
				return true;
			}

			if(!in_array('group', $index_guess_like_type)){
				M('Plat_recommend')->where(array('store_id'=>$store_id))->delete();
				return true;
			}
		}else if($plat_recommend_type == 'pin_group'){	//拼团
			if($plat_recommend_value[0] == 'group'&&$plat_recommend_value[1]){
				$plat_recommend_group = D('Group')->field('`group_id`,`name` AS `group_name`,`price` AS `group_price`,`pic` AS `group_pic`,`score_mean`,`old_price`,`sale_count`')->where(array('group_id'=>$plat_recommend_value[1]))->find();
			}else{
				$condition_where_plat_recommend_group = "`g`.`group_id`=`gs`.`group_id` AND `gs`.`store_id`='$store_id' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time'";
				$plat_recommend_group = D('')->field('`g`.`group_id`,`g`.`name` AS `group_name`,`g`.`price` AS `group_price`,`g`.`pic` AS `group_pic`,`g`.`score_mean`,`g`.`old_price`,`g`.`sale_count`')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gs'))->where($condition_where_plat_recommend_group)->order('`g`.`sort` DESC,`g`.`group_id` DESC')->find();
			}
			if($plat_recommend_group){
				//fdump($plat_recommend_group,'plat_recommend_pin_group',true);
				$group_image_class = new group_image();
				
				$plat_recommend_data['re_id'] 	= $plat_recommend_group['group_id'];
				$plat_recommend_data['name'] 	= html_entity_decode($plat_recommend_group['group_name']);
				
				$tmp_pic_arr = explode(';',$plat_recommend_group['group_pic']);
				$plat_recommend_data['pic'] 	= str_replace(file_domain(),'',$group_image_class->get_image_by_path($tmp_pic_arr[0],'s'));
				$plat_recommend_data['star'] 	= $plat_recommend_group['score_mean'];
				$plat_recommend_data['sale_count'] 	= $plat_recommend_group['sale_count'];
				
				$plat_recommend_data['price'] 	= $plat_recommend_group['group_price'];
				$plat_recommend_data['old_price'] 	= $plat_recommend_group['old_price'];
				
				//已拼人数
				$plat_recommend_data['sale_person'] = M('Group_order')->group('uid')->where(array('group_id'=>$plat_recommend_value[1]))->count();
				$plat_recommend_data['sale_person'] = $plat_recommend_data['sale_person'] ? $plat_recommend_data['sale_person'] : '';
			}else{
				return true;
			}			

			if(!in_array('pin_group', $index_guess_like_type)){
				M('Plat_recommend')->where(array('store_id'=>$store_id))->delete();
				return true;
			}
		}else if($plat_recommend_type == 'meal'){	//餐饮

			$plat_recommend_data['re_id'] 	= $store_id;
			$plat_recommend_data['name'] 	= html_entity_decode($now_store['name']);
			
			// 图片
			$store_image_class = new store_image();
			$images = $store_image_class->get_allImage_by_path($now_store['pic_info']);
			//fdump($images,'store_images',true);
			$plat_recommend_data['pic'] = $images ? str_replace(file_domain(),'',array_shift($images)) : '';

			if ($now_store['logo']) {
				$plat_recommend_data['pic'] = replace_file_domain($now_store['logo']);
			}
			
			// 店铺评分
			$now_store_score = M('Merchant_score')->where(array('type'=>2,'parent_id'=>$store_id))->find();
			$plat_recommend_data['star']    = $now_store_score && $now_store_score['reply_count'] ? round($now_store_score['score_all'] / $now_store_score['reply_count'],1) : 5.0;
			
			//餐饮信息
			$now_store_foodshop = M('Merchant_store_foodshop')->where(array('store_id'=>$store_id))->find();
			if(empty($now_store_foodshop)){
				M('Plat_recommend')->where(array('store_id'=>$store_id))->delete();
				return true;
			}
			$plat_recommend_data['star'] = $now_store_foodshop['score_mean']>0 ? $now_store_foodshop['score_mean'] : 5.0;
			
			//业务标签
			$label_arr = array();
			if($now_store['wifi_account']){
				$label_arr[] = 'WIFI';
			}
			if($now_store['is_park']){
				$label_arr[] = '停车位';
			}
			if($now_store['is_queue']){
				$label_arr[] = '在线排号';
			}
			$plat_recommend_data['label'] = implode(',',$label_arr);
			
			
			if($plat_recommend_value[0] == 'cash' && $now_store['discount_txt']){	//推荐优惠买单
				$discount_txt_arr = unserialize($now_store['discount_txt']);
				if($discount_txt_arr['discount_type'] == '1'){
					if($discount_txt_arr['discount_percent'] != 0 && $discount_txt_arr['discount_percent'] != 10){
						$plat_recommend_data['tip'] = '买单'.str_replace('.','',$discount_txt_arr['discount_percent']).'折';
					}
				}else if($discount_txt_arr['discount_type'] == '2'){
					if($discount_txt_arr['condition_price'] != 0 && $discount_txt_arr['condition_price'] != $discount_txt_arr['minus_price']){
						$plat_recommend_data['tip'] = '买单满'.$discount_txt_arr['condition_price'].'元减'.$discount_txt_arr['minus_price'].'元';
					}
				}
			}else if($plat_recommend_value[0] == 'group'){	//推荐团购
				if($plat_recommend_value[1]){
					$plat_recommend_group = D('Group')->field('`group_id`,`name` AS `group_name`,`price` AS `group_price`')->where(array('group_id'=>$plat_recommend_value[1]))->find();
				}else{
					$condition_where_plat_recommend_group = "`g`.`group_id`=`gs`.`group_id` AND `gs`.`store_id`='$store_id' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time'";
					$plat_recommend_group = D('')->field('`g`.`group_id`,`g`.`name` AS `group_name`,`g`.`price` AS `group_price`')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gs'))->where($condition_where_plat_recommend_group)->order('`g`.`sort` DESC,`g`.`group_id` DESC')->find();
				}
				
				if($plat_recommend_group){
					$plat_recommend_data['tip'] = getFormatNumber($plat_recommend_group['group_price']) . '元 | '.html_entity_decode($plat_recommend_group['group_name']);
				}
			}else if($plat_recommend_value[0] == 'description'){	//一句话描述
				if($plat_recommend_value[1]){
					$plat_recommend_data['tip'] = html_entity_decode($plat_recommend_value[1]);
				}
			}

			if(!in_array('meal', $index_guess_like_type)){
				M('Plat_recommend')->where(array('store_id'=>$store_id))->delete();
				return true;
			}
		}else if($plat_recommend_type == 'hotel'){	//酒店

			$condition_where_plat_recommend_group = "`g`.`group_id`=`gs`.`group_id` AND `gs`.`store_id`='$store_id' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `g`.`trade_type`='hotel'";
			$plat_recommend_group = D('')->field('`g`.`group_id`,`g`.`name` AS `group_name`,`g`.`price` AS `group_price`,`g`.`pic` AS `group_pic`,`g`.`score_mean`,`g`.`old_price`')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gs'))->where($condition_where_plat_recommend_group)->order('`g`.`sort` DESC,`g`.`group_id` DESC')->find();

			if($plat_recommend_group){
				//fdump($plat_recommend_group_hotel,'plat_recommend_group_hotel',true);
				$group_image_class = new group_image();
				
				$plat_recommend_data['re_id'] 	= $plat_recommend_group['group_id'];
				$plat_recommend_data['name'] 	= html_entity_decode($plat_recommend_group['group_name']);
				
				$tmp_pic_arr = explode(';',$plat_recommend_group['group_pic']);
				$plat_recommend_data['pic'] 	= str_replace(file_domain(),'',$group_image_class->get_image_by_path($tmp_pic_arr[0],'s'));
				$plat_recommend_data['star'] 	= $plat_recommend_group['score_mean'];
				$plat_recommend_data['sale_count'] 	= intval($plat_recommend_group['sale_count']);
				
				$plat_recommend_data['price'] 	= $plat_recommend_group['group_price'];
				$plat_recommend_data['old_price'] 	= $plat_recommend_group['old_price'];
				$plat_recommend_data['price_up'] 	= '1';
				
				//已买人数
				$plat_recommend_data['sale_person'] = intval(M('Group_order')->group('uid')->where(array('group_id'=>$plat_recommend_value[1]))->count());
				$plat_recommend_data['sale_person'] = $plat_recommend_data['sale_person'] ? $plat_recommend_data['sale_person'] : '';
				
				//标签
				$hotel = M('Trade_hotel_category')->where(array('cat_fid'=>$plat_recommend_group['trade_info']))->select();
				$tmp['is_refund'] = 0 ;
				$tmp['discount'] = 0 ;
				foreach ($hotel as $vv) {
					if($vv['has_refund']!=1){
						$tmp['is_refund']  = 1;
					}
					if($vv['discount_room']>0){
						$tmp['discount']  = 1;
					}
					if($tmp['is_refund'] && $tmp['discount']){
						break;
					}
				}
				$label_arr = array();
				if($tmp['is_refund']){
					$label_arr[] = '可退';
				}
				if($now_store['discount']){
					$label_arr[] = '惠';
				}
				$plat_recommend_data['label'] = implode(',',$label_arr);
			}else{
				M('Plat_recommend')->where(array('store_id'=>$store_id))->delete();
				return true;
			}

			if(!in_array('hotel', $index_guess_like_type)){
				M('Plat_recommend')->where(array('store_id'=>$store_id))->delete();
				return true;
			}
		}else{
			return true;
		}
		
		
		if($plat_recommend){
			M('Plat_recommend')->where(array('store_id'=>$store_id))->data($plat_recommend_data)->save();
		}else{
			M('Plat_recommend')->data($plat_recommend_data)->add();
		}
		
		return true;
	}
}
?>