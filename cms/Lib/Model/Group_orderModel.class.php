<?php
class Group_orderModel extends Model{
	public $status_list = array('-1' => '全部', 0 => '未消费', 1 => '已消费', 2 => '已完成', 3 => '已退款', 4 => '已取消', 6 => '部分退款',8=>'未支付');

	public function _initialize()
	{
		$this->$status_list = array(
				'-1' => L_('全部'),
				0 => L_('未消费'),
				1 => L_('已消费'),
				2 => L_('已完成'),
				3 => L_('已退款'),
				4 => L_('已取消'),
				6 => L_('部分退款'),
				8=>L_('未支付'),
		);
	}

	public function get_status_list(){
		return $this->$status_list;
	}

	/**获取订单分类**/
	public function get_order_cate($order_id,$is_temp_order = false){
		//order表 到 group表 到 category表
		if($is_temp_order){
			$group_id = M('Group_order_temp')->field('group_id')->where(['id' => $order_id])->find();
		}else{
			$group_id = $this->field('group_id')->where(array('order_id'=>$order_id))->find();
		}
		$cat_id = D('Group')->field('cat_fid')->where(array('group_id'=>$group_id['group_id']))->find();
		$group_cate = D('Group_category')->field('cat_id,cat_name')->where(array('cat_id'=>$cat_id['cat_fid']))->find();
		return $group_cate;
	}
	public function get_coupon_info($order_id){
		$now_order = $this->where(array('order'=>$order_id))->find();
		if(!empty($now_order['card_id'])){
			$now_card = D('Member_card_coupon')->get_coupon_info($now_order['card_id']);
			$now_order['coupon_type'] = 'mer';
			$now_order['coupon_price'] = $now_card['price'];
		}
		if(!empty($now_order['coupon_id'])){
			$now_coupon = D('System_coupon')->get_coupon_info($now_order['coupon_id']);
			$now_order['coupon_type'] = 'system';
			$now_order['coupon_price'] = $now_coupon['price'];
		}
		return $now_order['coupon_price'];
	}


	public function save_post_form($group,$uid,$order_id=0){
		$data_group_order['group_id'] = $group['group_id'];
		$data_group_order['mer_id'] = $group['mer_id'];
        $data_group_order['num'] = intval($_POST['quantity']);
        $data_group_order['pass_num'] = intval($group['pass_num']);
        $data_group_order['is_liveshow'] = $group['is_liveshow'] ? : 0;
        $data_group_order['liveshow_id'] = $group['liveshow_id'] ? : 0;
        $data_group_order['is_livevideo'] = $group['is_livevideo'] ? : 0;
        $data_group_order['livevideo_id'] = $group['livevideo_id'] ? : 0;
        $data_group_order['liveshow_percent'] = $group['liveshow_percent'] ? : 0;
        $group['distributor_uid']&&$data_group_order['distributor_uid'] = intval($group['distributor_uid']);
        if (!C('config.house_buy_rebate_open')) {
			if(cookie('visit_village_id')!='' && M('House_village_group')->where(array('village_id'=>cookie('visit_village_id'),'group_id'=>$group['group_id']))->find()){
                if ($_SESSION['now_village_bind']['village_id'] && $_SESSION['now_village_bind']['village_id']>0) {
                    $data_group_order['village_id'] = $_SESSION['now_village_bind']['village_id'];
                } elseif ($_SESSION['house']['village_id'] && $_SESSION['house']['village_id']>0) {
                    $data_group_order['village_id'] = $_SESSION['house']['village_id'];
                } else {
                    $store_arr = M('Group_store')->where(array('group_id' => $group['group_id']))->getField('store_id',true);
                    if ($store_arr) {
                        $count_store = D('Plat_recommend')->where(array('store_id',array('in', $store_arr)))->count();
                        if ($count_store>0) {
                            $data_group_order['village_id'] = cookie('visit_village_id');
                        }
                    }
                }
                if (!$data_group_order['village_id']) {
                    $data_group_order['village_id'] = cookie('visit_village_id');
                }
            } elseif(cookie('visit_village_id')!='' && cookie('visit_village_id')>0) {
                $store_arr = M('Group_store')->where(array('group_id' => $group['group_id']))->getField('store_id',true);
                if ($store_arr) {
                    $count_store = D('Plat_recommend')->where(array('store_id',array('in', $store_arr)))->count();
                    if ($count_store>0) {
                        $data_group_order['village_id'] = cookie('visit_village_id');
                    }
                }
            }
		} else {
			//开关开启情况下-获取用户最新选择的小区
			$select_log = M('House_village_select_log')->where(['uid'=>$uid])->field('village_id')->order('id DESC')->find();
			$data_group_order['village_id'] = isset($select_log['village_id'])?$select_log['village_id']:0;
		}

		if(empty($data_group_order['num'])){
			return array('error'=>1,'msg'=>L_('请输入正确的购买数量！'));
		}else if($data_group_order['num'] < $group['once_min']){
			return array('error'=>1,'msg'=>L_("您最少需要购买X1单！",array("X1" => $group['once_min'])));
		}else if($group['once_max'] != 0 && $data_group_order['num'] > $group['once_max']){
			return array('error'=>1,'msg'=>L_("您最多只能购买X1单！",array("X1" => $group['once_max'])));
		}
		
		//如果下过“下单成功后减库存”的单，则让去个人中心付款
		if($group['stock_reduce_method'] && M('Group_order')->where(['group_id' => $group['group_id'], 'uid' => $uid, 'specifications_id' => intval($group['specifications_id']), 'paid' => '0', 'status' => '0'])->find()){
			return array('error'=>1,'msg'=>L_("该商品您有未支付的订单，请在个人中心订单列表支付！",array("X1" => $group['once_max'])));
		}
		
		//2015-11-19
		$check_result = $this->check_buy_num($uid, $group['group_id'], $data_group_order['num'], $group['specifications_id']);
		if ($check_result['errcode']) {
			return array('error'=>1,'msg'=>$check_result['msg']);
		}
		if ($group['specifications_id']) {
            $data_group_order['order_name'] = $group['s_name'] . L_('【').$group['specifications_name'].L_('】') .'*'.$data_group_order['num'];
            $data_group_order['specifications_id'] = $group['specifications_id'];
		} else {
            $data_group_order['order_name'] = $group['s_name'].'*'.$data_group_order['num'];
		}
		//1 为单独购买 3 为发起团购 2 为参团
		if($_POST['group_type']==1){
			$data_group_order['price'] = $group['level_price']>0 ? $group['level_price']: $group['old_price'];
			$data_group_order['single_buy']=1;

		}elseif($_POST['group_type']==3){
			$data_group_order['price'] = $group['price']; //团长按团长折扣计算

			$start_res = D('Group_start')->start_group($uid,$group);
			$data_group_order['is_head'] = $start_res['msg'];//是否是团长
			if($start_res['error_code']){
				return array('error'=>1,'msg'=>$start_res['msg']);
			}
		}else{
			$data_group_order['price'] = $group['price'];
		}

		if($group['extra_pay_price']>0){
			$data_group_order['extra_price'] =$_POST['group_type']==1?$group['extra_pay_old_price']*$data_group_order['num'] :$group['extra_pay_price']*$data_group_order['num'];
		}
		
		//真实订单号
		$orderid = date('ymdhis').substr(microtime(),2,8-strlen($uid)).$uid;//real_orderid
		$data_group_order['real_orderid'] = $orderid;
		
		
		//用户自定义输入信息
		if($_POST['userinput']){
			$data_group_order['userinput'] = serialize($_POST['userinput']);
		}
		//若有第三方团购ID，则存入
		if($group['custom_product_id']){
			$data_group_order['custom_product_id'] = $group['custom_product_id'];
		}
		
		if($group['trade_type'] == 'hotel'){
			$hotel_list = D('Trade_hotel_category')->get_cat_price($group['mer_id'],$_POST['cat-id'],$_POST['dep-time'],$_POST['end-time']);
			if($hotel_list['err_code']){
				return array('error'=>1,'msg'=>$hotel_list['err_msg']);
			}
			if($hotel_list['stock'] < $data_group_order['num']){
				return array('error'=>1,'msg'=>L_('房间数没达到您选择的房间数，请重新选择'));
			}

			$trade_info_arr = array(
				'type'=>'hotel',
				'dep_time'=>$_POST['dep-time'],
				'end_time'=>$_POST['end-time'],
				'cat_id'=>$_POST['cat-id'],
				'num'=>$data_group_order['num']
			);
			$data_group_order['price'] = 0;
			foreach($hotel_list['stock_list'] as $value){
				if($_POST['quantity']>=$hotel_list['discount_room']&&$hotel_list['discount_room']>0){
					$trade_info_arr['price_list'][$value['day']] = $value['discount_price'];
				}else{
					$trade_info_arr['price_list'][$value['day']] = $value['price'];
				}
				$data_group_order['price'] += $trade_info_arr['price_list'][$value['day']];
			}

			//酒店对接第三方
			if(C('config.baodi_hotel_url') && $hotel_list['cat_code']){
				import('@.ORG.third_custom.baodi.baodiHotel');
				$baodiHotel = new baodiHotel();
				$user = D('User')->get_user($uid);
				$bookData = [
					'HotelCode' => $hotel_list['hotelCode'],
					'RateCode' => $hotel_list['rateCode'],
					'RoomType' => $hotel_list['cat_code'],
					'Mobile' => $user['phone'],
					'ResStatus'  => 'R',
					'confirmationID' => $data_group_order['real_orderid'],
					'CreateDate' => date('Y-m-d H:i'),
					'CutoffDate' => date('Y-m-d H:i', time() + 20 * 60),	//保留20分钟
					'Arrdate' => date('Y-m-d ', strtotime($_POST['dep-time'])) . $hotel_list['enter_time'],
					'DepDate' => date('Y-m-d ', strtotime($_POST['end-time']) + 86400) . $hotel_list['enter_time'],
					'RatePlans' => []
				];
				foreach($hotel_list['stock_list'] as $value){
					$bookData['RatePlans'][]['RatePlan'] = [
						'RateDate' => $value['day'],
						'RateNote' => $value['day'],
						'ExcutiveRate' => strval($value['price']),
					];
				}
				$result = $baodiHotel->bookHotel($bookData);
				
				if(!$result['confirmationID'] || $result['ErrorMsg']){
					return array('error'=>1,'msg'=>L_('下单失败！请重试。'));
				}
				$trade_info_arr['baodi_hotel_info'] = $hotel_list;
			}
			
			$data_group_order['trade_info'] = serialize($trade_info_arr);
			$data_group_order['store_id'] = $group['store_list'][0]['store_id'];
			
		}

        if ($_POST['group_type']) {
            $data_group_order['group_type'] = $_POST['group_type'];
		}
		$group['vip_discount_money'] && $data_group_order['vip_discount_money'] = $group['vip_discount_money'] * $data_group_order['num'];
		$data_group_order['total_money'] = $data_group_order['price'] * $data_group_order['num'];
		$data_group_order['tuan_type'] = $group['tuan_type'];
		$data_group_order['add_time'] = $_SERVER['REQUEST_TIME'];

		//实物
		if($group['tuan_type']==2  && $group['trade_type']!='hotel'){
			if(!empty($_POST['pick_in_store'])){
				if($_POST['pick_addr_id']){
					$pick_list = D('Pick_address')->get_pick_addr_by_merid($group['mer_id']);
					if(!empty($_POST['pick_in_store'])){
						foreach($pick_list as $k=>$v){
							if($v['pick_addr_id']==$_POST['pick_addr_id']){
								$pick_address = $v;
								break;
							}
						}
					}
					$data_group_order['is_pick_in_store'] = 1;
					$data_group_order['phone'] = $pick_address['phone'];
					$data_group_order['adress'] = $pick_address['area_info']['province'].$pick_address['area_info']['city'].$pick_address['area_info']['area'].$pick_address['name'];
                    $data_group_order['pick_lng'] = $pick_address['long'] ? $pick_address['long'] : 0;
                    $data_group_order['pick_lat'] = $pick_address['lat'] ? $pick_address['lat'] : 0;
				}else if(!empty($_POST['pick_address'])){
					$data_group_order['is_pick_in_store'] = 1;
					$data_group_order['adress'] = $_POST['pick_address'];
					$data_group_order['pick_lng'] = $_POST['pick_lng'] ? $_POST['pick_lng'] : 0;
					$data_group_order['pick_lat'] = $_POST['pick_lat'] ? $_POST['pick_lat'] : 0;
					$user = D('User')->get_user($uid);
					$data_group_order['phone'] = $user['phone'];
				}else{
					return array('error'=>1,'msg'=>L_('自取地址为空不能使用，请选择自取地址！'));
				}
			}else{
				$now_adress = D('User_adress')->get_one_adress($uid,$_POST['adress_id']);
				if(empty($now_adress)){
					return array('error'=>1,'msg'=>L_('请先添加收货地址！'));
				}
				$data_group_order['contact_name'] = $now_adress['name'];
				$data_group_order['phone'] = $now_adress['phone'];
				$data_group_order['zipcode'] = $now_adress['zipcode'];
				$data_group_order['adress'] = $now_adress['show_address'];
				$_POST['delivery_type'] && $data_group_order['delivery_type'] = $_POST['delivery_type'];
				$_POST['delivery_comment'] && $data_group_order['delivery_comment'] = $_POST['delivery_comment'];
			}
		}else{
			$now_user = D('User')->get_user($uid);
			if(empty($now_user)){
				return array('error'=>1,'msg'=>L_('未获取到您的帐号信息，请重试！'));
			}
			$data_group_order['phone'] = $now_user['phone'];
			if($_POST['delivery_comment']){
				$data_group_order['delivery_comment'] = $_POST['delivery_comment'];
			}
		}

		if($group['tuan_type'] == 2){
			$group['user_adress'] = D('User_adress')->get_one_adress($uid,intval($_POST['adress_id']));

			/*运费计算*/
			if($group['user_adress'] && $_POST['pick_in_store']==0){
				$express_fee = D('Group')->get_express_fee($group['group_id'],$data_group_order['total_money'],$group['user_adress']);
				$data_group_order['express_fee'] =$express_fee['freight'];
				$group['express_template'] = $express_fee;
				$data_group_order['total_money'] += $data_group_order['express_fee'];
			}

		}
		
		//券票对接第三方
		if(C('config.baodi_ticket_url') && $group['custom_product_id']){
			$user = D('User')->get_user($uid);
			import('@.ORG.third_custom.baodi.baodiTicket');
			$baodiTicket = new baodiTicket();
			$bookData = [
				'AccountID' => 'WEIXIN',
				'order_id' => $data_group_order['real_orderid'],
				'startDate' => date('Y-m-d'),
				'endDate' => date('Y-m-d', $group['end_time']),
				'custom_product_id'  => $data_group_order['custom_product_id'],
				'num' => $data_group_order['num'],
				'total_money' => $data_group_order['total_money'],
				'username' => $_POST['userinput']['name'],
				'userphone' => $user['phone'],
				'idcard' => $_POST['userinput']['idcard'],
			];
			$result = $baodiTicket->bookTicket($bookData);
			dump($bookData);
			dump($result);
			die;
		}

		if($order_id){
			$condition_group_order['order_id'] = $order_id;
			$condition_group_order['uid'] = $uid;
			$save_result = $this->where($condition_group_order)->data($data_group_order)->save();
			if($save_result){
				/*-----记录系统订单信息 19/1/14----*/
				fdump($data_group_order, 'cx_062303',1);
				//增加搜索关键词  店铺名称 和 商品名称
				$group_info = M('Group')->field('name')->where(['group_id' => $data_group_order['group_id']])->find();;
				$store_info = M('Merchant_store')->field('name')->where(['store_id'=>$data_group_order['store_id']])->find();
				$order_data['keywords'] = rtrim($store_info['name'].','.$group_info['name'], ',');

				D("System_order")->add_order('group',$data_group_order,$order_id);

				return array('error'=>0,'msg'=>L_('订单修改成功！'),'order_id'=>$order_id);
			}else{
				return array('error'=>1,'msg'=>L_('订单修改失败！请重试'),'order_id'=>$order_id);
			}
		}
		else{
			$data_group_order['uid'] = $uid;
			$Group_store=D('Group_store')->where(array('group_id'=>$group['group_id']))->select();
			if(!empty($Group_store) && (C('config.group_to_store')==1 || $group['tuan_type']==2)){
				/****当此团购为实物且只指定一个店铺时，将店铺id直接带入保存到订单里  开启虚拟券单店铺绑定*************/
				if (count($Group_store)==1 || $group['tuan_type']==2) {
					$data_group_order['store_id']=$Group_store['0']['store_id'];
				}
			}
			$data_group_order['stock_reduce_method'] = $group['stock_reduce_method'];

			if(!empty($_SESSION['gid'])){
				$data_group_order['pin_fid'] = $_SESSION['gid'];
			}

			if(!empty($_SESSION['fid'])){
				$fid_group_id = M('Group_order')->where(array('order_id'=>$_SESSION['fid']))->find();
				if($group['group_id']!=$fid_group_id['group_id']){
					unset($_SESSION['fid']);
				}
			}
            $data_group_order['new_group'] = $_GET['new_group'] ? $_GET['new_group'] : 0;


			if (isset($group['share_openid'])) {
				// 处理单个分享问题
				$data_group_order['share_openid'] = $group['share_openid'];
			}

			$store_id=$data_group_order['store_id'];
			if(count($Group_store)==1 && C('config.group_to_store')==0 && $group['tuan_type']!=2) {//关闭了直接核销的功能,不是实物，且单店，不保存store_id
				unset($data_group_order['store_id']);
			}

			$order_id = $this->data($data_group_order)->add();
			$data_group_order['store_id']=$store_id;

			if(!empty($_SESSION['fid'])||$group['group_share_num']>0){
				//$res = M('Group_share_relation')->where(array('fid'=>$_SESSION['fid'],'uid'=>$uid))->find();
//				if(empty($res)){
				$date['fid'] = empty($_SESSION['fid'])?$order_id:$_SESSION['fid'];
				$date['uid'] = $uid;
				$date['order_id'] = $order_id;
				unset($_SESSION['fid']);
				if(!M('Group_share_relation')->data($date)->add()){
					return array('error'=>1,'msg'=>L_('参加组团购失败！'),'order_id'=>$order_id);
				}
			}
			if($order_id){
				//下单成功减库存
				if ($group['stock_reduce_method']) {
					// 如果存在规格 改写规格销量
					if ($group['specifications_id']) {
						$m_group_specifications = M('Group_specifications');
						$specifications_info = $m_group_specifications->field('sale_count')->where(array('specifications_id' => $group['specifications_id']))->find();
						
						$m_group_specifications->where(array('group_id' => $group['group_id'], 'specifications_id' => $group['specifications_id']))->setInc('sale_count', $data_group_order['num']);
						
                        // $sale_count = $data_group_order['num'] + $specifications_info['sale_count'];
                        // $update_group_data = array('sale_count' => $sale_count, 'last_time' => time());
                        // $m_group_specifications->where(array('group_id' => $group['group_id'], 'specifications_id' => $group['specifications_id']))->save($update_group_data);
					} else {
                        // $sale_count = $data_group_order['num'] + $group['sale_count'];
                        // $update_group_data = array('sale_count' => $sale_count);
// 					    if ($group['count_num'] > 0 && $sale_count >= $group['count_num']) {//更改结束状态
// 						    $update_group_data['type'] = 3;
// 					    }
 //                     D('Group')->where(['group_id' => $group['group_id'], 'mer_id' => $group['mer_id']])->save('sale_count', $update_group_data);
 
						D('Group')->where(['group_id' => $group['group_id'], 'mer_id' => $group['mer_id']])->setInc('sale_count', $data_group_order['num']);
					}
				}

				if ($_SESSION['user']['openid']) {
					$href = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$order_id;
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					$model->sendTempMsg('OPENTM201682460', array('href' => $href, 'wecha_id' => $_SESSION['user']['openid'], 'first' => L_('您好，您的订单已生成'), 'keyword3' => $orderid, 'keyword1' => date('Y-m-d H:i:s'), 'keyword2' => $data_group_order['order_name'], 'remark' => L_('您的该次X1已生成，点击查看订单详情！',C('config.group_alias_name'))), $data_group_order['mer_id']);
				}
				$sms_data = array('mer_id' => $group['mer_id'], 'store_id' => 0, 'type' => 'group');
				if (C('config.sms_group_place_order') == 1 || C('config.sms_group_place_order') == 3) {
					$sms_data['uid'] = $uid;
					$sms_data['mobile'] = $data_group_order['phone'];
					$sms_data['sendto'] = 'user';
					$sms_data['content'] = L_('您在x1时，购买了x2，已成功生产订单，订单号：x3',array('x1'=>date("Y-m-d H:i:s"),'x2'=>$group['s_name'],'x3'=>$orderid));
					Sms::sendSms($sms_data);
				}
				if (C('config.sms_group_place_order') == 2 || C('config.sms_group_place_order') == 3) {
					$merchant = D('Merchant')->where(array('mer_id' => $group['mer_id']))->find();
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $merchant['phone'];
					$sms_data['sendto'] = 'merchant';
					$sms_data['content'] = L_("有份新的X1被购买，订单号：X2请您注意查看并处理!",array("X1" => $group['s_name'],"X2" => $orderid)); 
					Sms::sendSms($sms_data);
				}
				if($_POST['group_type']==3){
					$notice_status = 8;
				}else if($_POST['group_type']==2){
					$notice_status = 9;
				}else{
					$notice_status = 0;
				}
                // 添加一下填写的参数
				if ($group['write_field']) {
                    $save = array();
                    $save['write_field'] = $group['write_field'];
                    M('Group_order')->where(array('order_id' => $order_id))->data($save)->save();
				}
                // 消息推送
				$this->group_app_notice($order_id,$notice_status);

				/*-----记录系统订单信息 19/1/14----*/

				//增加搜索关键词  店铺名称 和 商品名称
				$group_info = M('Group')->field('name')->where(['group_id' => $data_group_order['group_id']])->find();;
				$store_info = M('Merchant_store')->field('name')->where(['store_id'=>$data_group_order['store_id']])->find();
				$data_group_order['keywords'] = rtrim($store_info['name'].','.$group_info['name'], ',');

				D("System_order")->add_order('group',$data_group_order,$order_id);

				return array('error'=>0,'msg'=>L_('订单产生成功！'),'order_id'=>$order_id);
			}else{
				return array('error'=>1,'msg'=>L_('订单产生失败！请重试'));
			}
		}
	}
	public function get_order_detail_by_longid_and_merId($mer_id,$order_id,$is_wap=false){
		$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_order'=>'o',C('DB_PREFIX').'user'=>'u');
		$condition_where = "(`o`.`real_orderid`='$order_id') AND `o`.`group_id`=`g`.`group_id` AND `g`.`mer_id`='$mer_id' AND `o`.`uid`=`u`.`uid`";
		$now_order = $this->field('`o`.*,`g`.`s_name`,`g`.`deadline_time`,`g`.`effective_type`,`g`.`pic`,`g`.`end_time`,`u`.`nickname`,`u`.`phone` `user_phone`,g.group_cate')->where($condition_where)->table($condition_table)->find();
		if(!empty($now_order)){
			$group_image_class = new group_image();
			$tmp_pic_arr = explode(';',$now_order['pic']);
			$now_order['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
			$now_order['url'] = D('Group')->get_group_url($now_order['group_id'],$is_wap);

			$now_order['price'] = floatval($now_order['price']);
			$now_order['total_money'] = floatval($now_order['total_money']);
			$now_order['pay_type_txt'] = D('Pay')->get_pay_name($now_order['pay_type'],$now_order['is_mobile_pay']);
			if($now_order['group_pass']){
				$now_order['group_pass_txt'] = preg_replace('#(\d{4})#','$1 ',$now_order['group_pass']);
			}
		}
		return $now_order;
	}
	public function get_order_detail_by_id_and_merId($mer_id,$order_id,$is_wap=false){
		$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_order'=>'o',C('DB_PREFIX').'user'=>'u');
		$condition_where = "(`o`.`order_id`='$order_id' OR `o`.`real_orderid`='$order_id') AND `o`.`group_id`=`g`.`group_id` AND `g`.`mer_id`='$mer_id' AND `o`.`uid`=`u`.`uid`";
		$now_order = $this->field('`o`.*,`g`.`s_name`,`g`.`deadline_time`,`g`.`effective_type`,`g`.`pic`,`g`.`end_time`,`u`.`nickname`,`u`.`phone` `user_phone`,g.group_cate')->where($condition_where)->table($condition_table)->find();
		if(!empty($now_order)){
			$group_image_class = new group_image();
			$tmp_pic_arr = explode(';',$now_order['pic']);
			$now_order['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'');
			$now_order['url'] = D('Group')->get_group_url($now_order['group_id'],$is_wap);

			$now_order['price'] = floatval($now_order['price']);
			$now_order['total_money'] = floatval($now_order['total_money']);
			$now_order['pay_type_txt'] = D('Pay')->get_pay_name($now_order['pay_type'],$now_order['is_mobile_pay']);
			if($now_order['group_pass']){
				$now_order['group_pass_txt'] = preg_replace('#(\d{4})#','$1 ',$now_order['group_pass']);
			}
		}
		return $now_order;
	}

	/**
	 * 获取已过期订单列表
	 */
	public function get_out_order_List($ext_where = '') {
		$condition_table = array( C('DB_PREFIX') . 'group' => 'g', C('DB_PREFIX') . 'group_order' => 'o', C('DB_PREFIX') . 'user' => 'u');
		$condition_where = "((g.effective_type <> 1 AND g.deadline_time <= " . time() . ") OR (g.effective_type = 1 AND g.deadline_time * 86400 <= (" . time() . " - o.pay_time))) AND o.paid = 1 AND o.status = 0 AND `o`.`group_id`=`g`.`group_id` AND `o`.`uid`=`u`.`uid`";
		if($ext_where){
			$condition_where .=' AND '.$ext_where;
		}
		$orderList = $this->field('`o`.*,`g`.`s_name`,`g`.`deadline_time`,`g`.`effective_type`,`g`.`pic`,`g`.`end_time`,`u`.`nickname`,`u`.`phone` `user_phone`,g.group_cate')->where($condition_where)->table($condition_table)->order('o.order_id desc')->limit(40)->select();
		return $orderList;
	}

	public function get_order_detail_by_id($uid,$order_id,$is_wap=false,$check_user=true){
		$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_order'=>'o');
		if($check_user){
			$condition_where = "`o`.`uid`='$uid' AND ";
		}else{
			$condition_where = '';
		}
		$condition_where .= "`o`.`order_id`='$order_id' AND `o`.`group_id`=`g`.`group_id`";
		$now_order = $this->field('`o`.*,`g`.`s_name`,`g`.`pic`,`g`.`end_time`,`g`.`deadline_time`,`g`.`effective_type`,`g`.`reply_count`,`g`.`score_all`,`g`.`score_mean`,`g`.`group_cate`')->where($condition_where)->table($condition_table)->find();

		if(!empty($now_order)){
			$group_image_class = new group_image();
			$tmp_pic_arr = explode(';',$now_order['pic']);
			$now_order['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'');
			if($now_order['group_cate'] == 'cashing'){
				$now_order['list_pic'] = C('config.site_url').'/static/images/group/cashing.png';
			}elseif($now_order['group_cate'] == 'booking_appoint'){
				$now_order['list_pic'] = C('config.site_url').'/static/images/group/booking_appoint.png';
			}
			$now_order['url'] = D('Group')->get_group_url($now_order['group_id'],$is_wap);
			$now_order['order_url'] = $this->get_order_url($now_order['group_id'],$is_wap);

			$now_order['price'] = floatval($now_order['price']);
			$now_order['total_money'] = floatval($now_order['total_money']);
			$now_order['pay_type_txt'] = D('Pay')->get_pay_name($now_order['pay_type'],$now_order['is_mobile_pay']);

			if($now_order['pay_type_txt']!='' && $now_order['is_own']>0){
				$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
				if($now_order['is_own']==2){
					$now_order['pay_type_txt'] .= '(平台子商家：'.$now_merchant['name'].')';
				}else{
					$now_order['pay_type_txt'] .= '(商家：'.$now_merchant['name'].')';
				}
			}
			if($now_order['group_pass']){
				$now_order['group_pass_txt'] = preg_replace('#(\d{4})#','$1 ',$now_order['group_pass']);
			}
			if($now_order['express_type']){
				$now_order['express_info'] = D('Express')->get_express($now_order['express_type']);
			}
		}else{
			// 团购优惠组合订单
			$condition_table = array(C('DB_PREFIX').'group_combine_activity'=>'g',C('DB_PREFIX').'group_order'=>'o');
			if($check_user){
				$condition_where = "`o`.`uid`='$uid' AND ";
			}else{
				$condition_where = '';
			}
			$condition_where .= "`o`.`order_id`='$order_id' AND `o`.`combine_id`=`g`.`combine_id`";
			$now_order = $this->field('`o`.*,`g`.`title` as name,`g`.`banner_img`')->where($condition_where)->table($condition_table)->find();
			if($now_order){
				$now_order['image'] = replace_file_domain($now_order['banner_img']);
			}

		}

		return $now_order;
	}
	public function get_order_by_id($uid,$order_id){
		$condition_group_order['order_id'] = $order_id;
		$condition_group_order['uid'] = $uid;
		$condition_group_order['status'] = array('lt', 3);
		return $this->field(true)->where($condition_group_order)->find();
	}
	public function get_pay_order($uid,$order_id,$is_web=false){
		$now_order = $this->get_order_by_id($uid,$order_id);
		// var_dump($now_order);die;

		if(!$now_order['is_group_combine']){
			$now_order_img = $this->get_order_detail_by_id($uid,$order_id);
		}
		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('当前订单已失效或不存在！'));
		}
		if(!empty($now_order['paid'])){
			unset($_SESSION['group_order']);
			if($is_web){
				return array('error'=>1,'msg'=>L_('您已经支付过此订单！'),'url'=>U('User/Index/group_order_view',array('order_id'=>$now_order['order_id'])));
			}else{
				return array('error'=>1,'msg'=>L_('您已经支付过此订单！'),'url'=>str_replace('/source/','/',U('My/group_order',array('order_id'=>$now_order['order_id']))));
			}
		}

		if(!$now_order['is_group_combine']){
			// 非团购优惠组合订单才验证团购商品信息
			$now_group = D('Group')->get_group_by_groupId($now_order['group_id']);
			$check_result = $this->check_buy_num($uid, $now_order['group_id'], $now_order['num'], $now_order['specifications_id']);
			if ($check_result['errcode']) {
				return array('error' => 1,'msg'=> $check_result['msg']);
			}
			
			if(empty($now_group)){
				$condition_group_order['order_id'] = $now_order['order_id'];
				$data_group_order['status'] = 4;
				if(D('Group_order')->where($condition_group_order)->data($data_group_order)->save()){
					/*-----记录系统订单信息 订单未支付取消 改写状态已取消 19/1/18---团购状态-未付款取消订单4-*/
					D("System_order")->save_order_status('group',$now_order['order_id'],5,4);
					return array('error'=>1,'msg'=>L_("当前X1不存在或已过期，此订单已自动取消！",array("X1" => C('config.group_alias_name')))); 
				}
				return array('error'=>1,'msg'=>L_("当前X1不存在或已过期！",array("X1" => C('config.group_alias_name')))); 
			}
		}

		// 支付过期时间
		$validityTime = 86400;
		$validityTimeFormat = '24小时';
		if($now_order['is_group_combine']){
			// 团购优惠组合订单有效期为15分钟
			 $validityTime = 900;
			 $validityTimeFormat = '15分钟';
		}

		if(time() - $now_order['add_time'] > $validityTime){
			$condition_group_order['order_id'] = $now_order['order_id'];
			$data_group_order['status'] = 4;
			$data_group_order['cancel_reason'] = L_('超时未支付');
			if(D('Group_order')->where($condition_group_order)->data($data_group_order)->save()){
				//退款时销量回滚
				if($now_group['stock_reduce_method']){
					D('Group')->where(array('group_id' => $now_order['group_id'], 'sale_count' => array('egt', $now_order['num'])))->setDec('sale_count', $now_order['num']);
				}
				/*-----记录系统订单信息 订单未支付取消 改写状态已取消 19/1/18---团购状态-未付款取消订单4-*/
				D("System_order")->save_order_status('group',$now_order['order_id'],5,4);
				return array('error'=>1,'msg'=>L_('当前订单已超过'.$validityTimeFormat.'未付款，无法继续支付，此订单已自动取消，请重新下单！'));
			}
			return array('error'=>1,'msg'=>L_('当前订单已超过'.$validityTimeFormat.'未付款，无法继续支付！'));
		}
		if ($now_order['mer_id']>0) {
			$merchant_name = M('Merchant')->where(array('mer_id'=>$now_order['mer_id']))->getField('name');
		}


		//2015-11-19 检查数量 TODO
		if($is_web){
			$order_info = array(
					'order_id'			=>	$now_order['order_id'],
					'real_orderid'		=>	$now_order['real_orderid'],
					'orderid'			=>	$now_order['orderid'],
					'mer_id'			=>	$now_order['mer_id'],
					'store_id'			=>	$now_order['store_id'],
					'group_id'			=>	$now_order['group_id'],
					'order_type'		=>	'group',
					'group_share_num' =>  $now_group['group_share_num'],
					'order_name'		=>	$now_group['s_name'],
					'order_num'			=>	$now_order['num'],
					'pin_num'			=>	$now_group['pin_num'],
					'is_head'			=>	$now_order['is_head'],
					'order_total_money'	=>	floatval($now_order['total_money']),
					'extra_price' =>$now_order['extra_price'],
					'extra_pay_price' =>$now_group['extra_pay_price'],
					'order_content'    =>  array(
							0=>array(
									'name'  		=> $now_group['merchant_name'].L_('：').$now_group['group_name'],
									'num'   		=> $now_order['num'],
									'price' 		=> floatval($now_order['price']),
									'money' 	=> floatval($now_order['num']*$now_order['price']),
									'extra_price' =>$now_group['extra_pay_price'],
							)
					)
			);
		}else{
			$order_info = array(
				'order_id'			=>	$now_order['order_id'],
				'real_orderid'		=>	$now_order['real_orderid'],
				'orderid'			=>	$now_order['orderid'],
				'group_id'			=>	$now_order['group_id'],
				'mer_id'			=>	$now_order['mer_id'],
				'store_id'			=>	$now_order['store_id'],
				'order_type'		=>	'group',
				'group_share_num' =>$now_group['group_share_num'],
				//'order_txt_type'		=>	$now_group['s_name'],
				'order_name'		=>	$now_group['s_name'] ?: $now_order['order_name'],
				'order_num'			=>	$now_order['num'],
				'pin_num'			=>	$now_group['pin_num'],
				'is_head'			=>	$now_order['is_head'],
				'order_price'		=>	floatval($now_order['price']),
				'order_total_money'	=>	floatval($now_order['total_money']),
				'img'               => $now_order_img['list_pic'],
				'extra_price' 		=> $now_order['extra_price'],
				'extra_pay_price'	=> $now_group['extra_pay_price'],
				'order_txt_type' 	=> $now_order['num'] . ' x ' . C('config.Currency_symbol') . $now_order['price'],
				'is_mobile'			=> '1',
	            'is_group_combine'=>$now_order['is_group_combine'],
	            'pay_merchant_coupon' => $now_order['is_group_combine'] ? false : true, // 商家优惠券
	            'pay_system_coupon' => $now_order['is_group_combine'] ? false : true, // 平台优惠券
	            'use_merchant_money' => $now_order['is_group_combine'] ? false : true, // 使用商家会员卡
			);
			if($now_group['trade_type'] == 'hotel' && $now_order['trade_info']){
				$trade_info_arr = unserialize($now_order['trade_info']);
				//判断房间数是否超出
				$tradeHotel = invoke_v20_service('app\hotel\model\service\TradeHotelCategoryService/getCatPrice',['mer_id'=>$now_order['mer_id'], $trade_info_arr['cat_id'], $trade_info_arr['dep_time'], $trade_info_arr['end_time']]);
				if($tradeHotel['status']==1000&&$tradeHotel['data']['error_no']==0&&$tradeHotel['data']['retval']){
					if($tradeHotel['data']['retval']['stock']<$trade_info_arr['num']){
						return array('error'=>1,'msg'=>L_('库存不足，剩余'.$tradeHotel['data']['retval']['stock']));
					}
				}
				$tmp_dep_time = date('m月d日',strtotime($trade_info_arr['dep_time']));
				$tmp_end_time = date('m月d日',strtotime($trade_info_arr['end_time']));
				$book_day = ((strtotime($trade_info_arr['end_time'])-strtotime($trade_info_arr['dep_time']))/86400);
				$order_info['extra_pay_price'] = $now_group['extra_pay_price']*$book_day ;
				$order_info['order_name'] = L_("X1 共X2晚",array("X1" => $tmp_dep_time.'-'.$tmp_end_time,"X2" => $book_day)); 
			}
		}
		//实物
		if($now_order['tuan_type'] == 2){
			$order_info['adress'] = $now_order['contact_name'].'，'.$now_order['adress'].'，'.$now_order['zipcode'].'，'.$now_order['phone'];
			switch($now_order['delivery_type']){
				case '1':
					$order_info['delivery_type'] = L_('工作日、双休日与假日均可送货');
					break;
				case '2':
					$order_info['delivery_type'] = L_('只工作日送货');
					break;
				case '3':
					$order_info['delivery_type'] = L_('只双休日、假日送货');
					break;
				default:
					$order_info['delivery_type'] = L_('白天没人，其它时间送货');
					break;
			}
			$order_info['delivery_comment'] = $now_order['delivery_comment'];
		}
        // 判断是否存在规格
        if ($now_order['specifications_id']) {
            $specifications_info = M('Group_specifications')->where(array('specifications_id' => $now_order['specifications_id']))->field('specifications_name')->find();
            if ($specifications_info && $specifications_info['specifications_name']) {
                $order_info['order_name'] = $now_group['s_name'] . '(' . $specifications_info['specifications_name'] .')';
            }
        }
		if ($merchant_name) {
			$order_info['order_name'] .= '_'.$merchant_name;
		}


		return array('error'=>0,'order_info'=>$order_info);
	}

	//电脑站支付前订单处理
	public function web_befor_pay($order_info,$now_user){
		//判断是否需要在线支付
		if(!$order_info['use_balance']){
			$now_user['now_money']=0;
		}
		if(($now_user['now_money']+$order_info['score_deducte'] )< $order_info['order_total_money']){
			$online_pay = true;
		}else{
			$online_pay = false;
		}
		//不使用在线支付，直接使用用户余额。
		if(empty($online_pay)){
			// $money_pay_result = D('User')->user_money($now_user['uid'],$order_info['order_total_money'],'购买 '.$order_info['order_name'].'*'.$order_info['order_num']);
			// if($money_pay_result['error_code']){
			// return $money_pay_result;
			// }
			$order_pay['balance_pay'] = $order_info['order_total_money']-$order_info['score_deducte'];
		}else{
			if(!empty($now_user['now_money'])){
				$order_pay['balance_pay'] = $now_user['now_money'];
			}
		}

		//将已支付用户余额等信息记录到订单信息里
		if(!empty($order_pay['balance_pay'])){
			$data_group_order['balance_pay'] = $order_pay['balance_pay'];
		}
		//扣除积分并保存订单
		if(!empty($order_info['score_deducte'])){
			$data_group_order['score_used_count']	= $order_info['score_used_count'];
			$data_group_order['score_deducte']      = (float)$order_info['score_deducte'];
		}

		if(!empty($data_group_order)){
			$data_group_order['wx_cheap'] 			= 0;
			$data_group_order['card_id'] 			= 0;
			$data_group_order['merchant_balance'] 	= 0;
			$data_group_order['last_time'] = $_SERVER['REQUEST_TIME'];
			$condition_group_order['order_id'] = $order_info['order_id'];
			if(!$this->where($condition_group_order)->data($data_group_order)->save()){

				return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
			}else{
				/*---更新系统订单信息 19/1/14--*/
				D('System_order')->reg_group_pay_order($order_info['order_id'],$data_group_order);
			}
		}
		if($online_pay){
			return array('error_code'=>false,'pay_money'=>$order_info['order_total_money'] - $now_user['now_money']-(float)$order_info['score_deducte']);
		}else{
			$order_param = array(
					'order_id' => $order_info['order_id'],
					'pay_type' => '',
					'third_id' => '',
					'is_mobile' => 0,
			);
			$result_after_pay = $this->after_pay($order_param);

			if($result_after_pay['error']){
				return array('error_code'=>true,'msg'=>$result_after_pay['msg']);
			}
			return array('error_code'=>false,'msg'=>L_('支付成功！'),'url'=>U('User/Index/group_order_view',array('order_id'=>$order_info['order_id'])));
		}
	}
	//手机端支付前订单处理
	public function wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user,$wx_cheap,$ecard_info=null){
		//去除微信优惠的金额
		$pay_money  = $order_info['order_total_money'];
		if($merchant_balance['card_discount']>0){
			$pay_money = sprintf("%.2f",$pay_money*$merchant_balance['card_discount']/10);
			//$pay_money = floor($pay_money*$merchant_balance['card_discount']*10)/100;
		}
		$pay_money = $pay_money - $wx_cheap;

		if(C('config.open_extra_price')==1){
			$user_score_use_percent=(float)C('config.user_score_use_percent');
			$order_info['order_extra_price'] = bcdiv($order_info['extra_price'],$user_score_use_percent,2);
			$pay_money +=$order_info['order_extra_price'];
		}

		//去掉折扣
		if($pay_money<=0){
			$order_result = $this->wap_pay_save_order($order_info);
			if ($order_result['error_code']) {
				return $order_result;
			}
			return $this->wap_after_pay_before($order_info);
		}
		$data_group_order['wx_cheap'] = $wx_cheap;
		$data_group_order['card_discount'] = $merchant_balance['card_discount'];
		//判断优惠券

		if($now_coupon['card_price']>0) {
			$data_group_order['card_id'] = $now_coupon['merc_id'];
			$data_group_order['card_price'] = $now_coupon['card_price'];
			if ($now_coupon['card_price'] >= $pay_money) {
				$order_result = $this->wap_pay_save_order($order_info, $data_group_order);
				if ($order_result['error_code']) {
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money -= $now_coupon['card_price'];
			$data_group_order['pay_money'] = $pay_money;
		}

		//系统优惠券
		if($now_coupon['coupon_price']>0){
			$data_group_order['coupon_id'] = $now_coupon['sysc_id'];
			$data_group_order['coupon_price'] = $now_coupon['coupon_price'];
			if ($now_coupon['coupon_price'] >= $pay_money) {
				$order_result = $this->wap_pay_save_order($order_info, $data_group_order);
				if ($order_result['error_code']) {
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money -= $now_coupon['coupon_price'];
			$data_group_order['pay_money'] = $pay_money;
		}
		//使用平台E卡
		if(!empty($ecard_info) && $ecard_info['now_price']>0){
			$data_group_order['ecard_password'] = $ecard_info['card_password'];
			$data_group_order['use_ecard_price'] = $ecard_info['now_price'];
			if($ecard_info['now_price']>=$pay_money){
				$order_result = $this->wap_pay_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				$order_info['pay_money'] = $pay_money;
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money-=$ecard_info['now_price'];			
		}else{
			$data_group_order['ecard_password'] ='';
			$data_group_order['use_ecard_price'] =0;
		}

		// 使用积分
		if(!empty($order_info['score_deducte'])&&$order_info['use_score']){
			$data_group_order['score_used_count']  = $order_info['score_used_count'];
			$data_group_order['score_deducte']     = (float)$order_info['score_deducte'];
			if($order_info['score_deducte'] >= $pay_money){
				$data_group_order['score_deducte'] = $pay_money;
				//扣除积分
				$order_result = $this->wap_pay_save_order($order_info,$data_group_order);
				fdump($order_result,'order_result',1);
				if($order_result['error_code']){
					return $order_result;
				}
				$order_info['pay_money'] = $pay_money;
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money -= $order_info['score_deducte'];
			//$data_group_order['system_pay'] += $data_group_order['score_deducte'];
		}

		//判断商家余额
		if(!empty($merchant_balance['card_money'])&&$order_info['use_merchant_balance']){
			if($merchant_balance['card_money'] >= $pay_money){
				$data_group_order['merchant_balance'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_group_order['merchant_balance'] = $merchant_balance['card_money'];
			}
			$pay_money -= $merchant_balance['card_money'];
		}

		if(!empty($merchant_balance['card_give_money'])&&$order_info['use_merchant_balance']){
			if($merchant_balance['card_give_money'] >= $pay_money){
				$data_group_order['card_give_money'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_group_order['card_give_money'] = $merchant_balance['card_give_money'];
			}
			$pay_money -= $merchant_balance['card_give_money'];
		}


		//判断帐户余额
		if(!empty($now_user['now_money'])&&$order_info['use_balance']){
			if($now_user['now_money'] >= $pay_money){
				$data_group_order['balance_pay'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_group_order['balance_pay'] = $now_user['now_money'];
			}
			$pay_money -= $now_user['now_money'];
		}
		//在线支付
		$order_result = $this->wap_pay_save_order($order_info,$data_group_order);
		if($order_result['error_code']){
			return $order_result;
		}
		return array('error_code'=>false,'pay_money'=>$pay_money);
	}
	//手机端支付前保存各种支付信息
	public function wap_pay_save_order($order_info,$data_group_order=array()){
		$data_group_order['wx_cheap'] 			= !empty($data_group_order['wx_cheap']) ? $data_group_order['wx_cheap'] : 0;
		$data_group_order['coupon_id'] 			= !empty($data_group_order['coupon_id']) ? $data_group_order['coupon_id'] : 0;
		$data_group_order['coupon_price'] 		= !empty($data_group_order['coupon_price']) ? $data_group_order['coupon_price'] : 0;
		$data_group_order['card_id'] 			= !empty($data_group_order['card_id']) ? $data_group_order['card_id'] : 0;
		$data_group_order['card_price'] 		= !empty($data_group_order['card_price']) ? $data_group_order['card_price'] : 0;
		$data_group_order['merchant_balance'] 	= !empty($data_group_order['merchant_balance']) ? $data_group_order['merchant_balance'] : 0;
		$data_group_order['card_give_money'] 	= !empty($data_group_order['card_give_money']) ? $data_group_order['card_give_money'] : 0;
		$data_group_order['card_discount'] 	= !empty($data_group_order['card_discount']) ? $data_group_order['card_discount'] : 0;
		$data_group_order['balance_pay']	 	= !empty($data_group_order['balance_pay']) ? $data_group_order['balance_pay'] : 0;
		$data_group_order['score_used_count']   = !empty($data_group_order['score_used_count']) ? $data_group_order['score_used_count'] : 0;
		$data_group_order['score_deducte']      = !empty($data_group_order['score_deducte']) ? $data_group_order['score_deducte'] : 0;
		$data_group_order['score_discount_type']      = !empty($order_info['score_discount_type']) ? $order_info['score_discount_type'] : 0;
		$data_group_order['last_time'] = $_SERVER['REQUEST_TIME'];
		$data_group_order['submit_order_time'] = $_SERVER['REQUEST_TIME'];
		$condition_group_order['order_id'] = $order_info['order_id'];
		if($this->where($condition_group_order)->data($data_group_order)->save()){
			/*---更新系统订单信息 19/1/14--*/
			D('System_order')->reg_group_pay_order($order_info['order_id'],$data_group_order);

			return array('error_code'=>false,'msg'=>L_('保存订单成功！'));
		}else{
			return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
		}
	}
	//如果无需调用在线支付，使用此方法即可。
	public function wap_after_pay_before($order_info){
		$order_param = array(
				'order_id' => $order_info['order_id'],
				'pay_type' => '',
				'third_id' => '',
				'is_mobile' => $order_info['is_mobile'],
		);
		$result_after_pay = $this->after_pay($order_param);
		if($result_after_pay['error']){
			return array('error_code'=>true,'msg'=>$result_after_pay['msg']);
		}
		return array('error_code'=>false,'msg'=>L_('支付成功！'),'url'=>str_replace('/source/','/',U('My/group_order',array('order_id'=>$order_info['order_id']))));
	}
	//支付前订单处理
	public function befor_pay($order_info,$now_coupon,$now_user){
		//判断是否需要在线支付
		if($now_coupon['price']+$now_user['now_money'] < $order_info['order_total_money']){
			$online_pay = true;
		}else{
			$online_pay = false;
		}
		//不使用在线支付，直接使用会员卡和用户余额。
		if(empty($online_pay)){
			if(!empty($now_coupon)){
				$coupon_pay_result = D('Member_card_coupon')->user_card($now_coupon['record_id'],$order_info['mer_id'],$now_user['uid']);
				if($coupon_pay_result['error_code']){
					return $coupon_pay_result;
				}
				$order_pay['car_id'] = $now_coupon['record_id'];
			}
			if(!empty($now_user['now_money']) && $now_coupon['price'] < $order_info['order_total_money']){
				$money_pay_result = D('User')->user_money($now_user['uid'],$order_info['order_total_money']-$now_coupon['price']);
				if($money_pay_result['error_code']){
					return $money_pay_result;
				}
				$order_pay['balance_pay'] = $now_user['now_money'];
			}
		}else{
			//校验会员卡
			if(!empty($now_coupon)){
				$coupon_pay_result = D('Member_card_coupon')->check_card($now_coupon['record_id'],$order_info['mer_id'],$now_user['uid']);
				if($coupon_pay_result['error_code']){
					return $coupon_pay_result;
				}
				$order_pay['car_id'] = $now_coupon['record_id'];
			}
			if(!empty($now_user['now_money'])){
				$order_pay['balance_pay'] = $now_user['now_money'];
			}
		}

		//将会员卡ID，已支付用户余额等信息记录到订单信息里
		if(!empty($order_pay['car_id'])){
			$data_group_order['card_id'] = $order_pay['record_id'];
		}
		if(!empty($order_pay['balance_pay'])){
			$data_group_order['balance_pay'] = $order_pay['record_id'];
		}
		if(!empty($data_group_order)){
			$data_group_order['last_time'] = $_SERVER['REQUEST_TIME'];
			$condition_group_order['order_id'] = $now_order['order_id'];
			if(!$this->where($condition_group_order)->data($data_group_order)->save()){
				/*---更新系统订单信息 19/1/14--*/
				D('System_order')->reg_group_pay_order($now_order['order_id'],$data_group_order);

				return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
			}
		}

		if($online_pay){
			return array('error_code'=>false,'pay_money'=>$order_info['order_total_money'] - $now_coupon['price'] - $now_user['now_money']);
		}else{
			$order_param = array(
					'order_id' => $order_info['order_id'],
					'pay_type' => '',
					'third_id' => '',
					'is_mobile' => 0,
					'pay_money' => 0,
					'order_type' => 'group',
			);
			$result_after_pay = $this->after_pay($order_param);
			if($result_after_pay['error']){
				return array('error_code'=>true,'msg'=>$result_after_pay['msg']);
			}
			return array('error_code'=>false,'url'=>U('My/group_order',array('order_id'=>$order_info['order_id'])));
		}
	}


	//支付之后
	public function after_pay($order_param){
		unset($_SESSION['group_order']);
		if($order_param['pay_type']!=''){
			$condition_group_order['orderid'] = $order_param['order_id'];
		}else{
			$condition_group_order['order_id'] = $order_param['order_id'];
		}
		$now_order = $this->field(true)->where($condition_group_order)->find();
        $now_order['order_name'] = str_replace('*', '×', $now_order['order_name']);

		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('当前订单不存在！'));
		}else if($now_order['paid'] == 1){
			if($order_param['is_mobile']){
				return array('error'=>1,'msg'=>L_('该订单已付款！'),'url'=>U('My/group_order',array('order_id'=>$now_order['order_id'])));
			}else{
				return array('error'=>1,'msg'=>L_('该订单已付款！'),'url'=>U('User/Index/group_order_view',array('order_id'=>$now_order['order_id'])));
			}
		}else{
			//得到当前用户信息，不将session作为调用值，因为可能会失效或错误。
			$now_user = D('User')->get_user($now_order['uid']);
			if(empty($now_user)){
				return array('error'=>1,'msg'=>L_('没有查找到此订单归属的用户，请联系管理员！'));
			}
			if($now_order['status']==3){
				return array('error'=>1,'msg'=>L_('订单支付失败'));
			}
            if (empty($now_order['stock_reduce_method'])) {//支付成功后减库存支付的时候验证库存量
                $check_result = $this->check_buy_num($now_order['uid'], $now_order['group_id'], $now_order['num'], $now_order['specifications_id'], $now_order['order_id']);
                if ($check_result['errcode']) {
                    return $this->wap_after_pay_error($now_order, $order_param, $check_result['msg']);
                }
            }
			if($now_order['card_id']){
				$now_coupon = D('Card_new_coupon')->get_coupon_by_id($now_order['card_id']);
				if(empty($now_coupon)){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您选择的优惠券不存在！'));
				}
			}

			//判断平台优惠券
			if($now_order['coupon_id']){
				$now_coupon = D('System_coupon')->get_coupon_by_id($now_order['coupon_id']);
				if(empty($now_coupon)){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您选择的优惠券不存在！'));
				}
			}

			//判断会员卡余额
			$merchant_balance = floatval($now_order['merchant_balance']);
			if($merchant_balance){
				$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['mer_id']);
				if($user_merchant_balance['card_money'] < $merchant_balance){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡余额不够此次支付！'));
				}
			}

			$card_give_money = floatval($now_order['card_give_money']);
			if($card_give_money){
				$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['mer_id']);
				if($user_merchant_balance['card_money_give'] < $card_give_money){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡余额不够此次支付！'));
				}
			}
			//判断帐户余额
			$balance_pay = floatval($now_order['balance_pay']);
			if($balance_pay){
				if($now_user['now_money'] < $balance_pay){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的帐户余额不够此次支付！'));
				}
			}

			if($now_order['card_id']){
				$use_result = D('Card_new_coupon')->user_coupon($now_order['card_id'],$now_order['order_id'],$order_param['order_type'],$now_order['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}
			//如果使用了平台优惠券
			if($now_order['coupon_id']){

				$use_result = D('System_coupon')->user_coupon($now_order['coupon_id'],$now_order['order_id'],$order_param['order_type'],$now_order['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}
			//如果使用了平台E卡
			if(!empty($now_order['ecard_password'])){
				D('Ecard_coupon')->add_ecard_log($now_order,'group');
			}


			//如果用户使用了积分抵扣，则扣除相应的积分
			//判断积分数量是否正确
			$score_used_count=$now_order['score_used_count'];
			if($now_user['score_count']<$score_used_count){
				return array('error'=>1,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
			}
			if($score_used_count>0){
				$use_result = D('User')->user_score($now_order['uid'],$score_used_count,L_("购买 X1 扣除X2",array("X1" => $now_order['order_name'],"X2" => C('config.score_name')))); 
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			//如果使用会员卡余额
			if($merchant_balance){
				$use_result = D('Card_new')->use_money($now_order['uid'],$now_order['mer_id'],$merchant_balance,L_("购买 X1 扣除会员卡余额",array("X1" => $now_order['order_name']))); 
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			if($card_give_money){
				$use_result = D('Card_new')->use_give_money($now_order['uid'],$now_order['mer_id'],$card_give_money,L_("购买 X1 扣除会员卡赠送余额",array("X1" => $now_order['order_name']))); 
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}
			//如果用户使用了余额支付，则扣除相应的金额。
			if(!empty($balance_pay)){
				$use_result = D('User')->user_money($now_order['uid'],$balance_pay,L_("购买 X1 扣除余额",array("X1" => $now_order['order_name']))); 
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			if($now_order['tuan_type'] < 2){
				// 团购组合优惠消费码长度16位 其他14位
				if($now_order['is_group_combine']){
					$min = 1000;
					$max = 9999;
				}else{
					$min = 10;
					$max = 99;
				}

				$group_pass_array = array(
						date('y',$_SERVER['REQUEST_TIME']),
						date('m',$_SERVER['REQUEST_TIME']),
						date('d',$_SERVER['REQUEST_TIME']),
						date('H',$_SERVER['REQUEST_TIME']),
						date('i',$_SERVER['REQUEST_TIME']),
						date('s',$_SERVER['REQUEST_TIME']),
						mt_rand($min,$max),
				);
				shuffle($group_pass_array);

				$data_group_order['group_pass'] = implode('',$group_pass_array);
				$fisrt_group_pass =  $data_group_order['group_pass'];
				$group_pass_arr[$data_group_order['group_pass']] =0;
				$date_group_pass_array[]=array('order_id'=>$now_order['order_id'],'group_pass'=>$data_group_order['group_pass'],'status'=>0);
				$pass_num = $now_order['pass_num']>0?intval($now_order['pass_num']):1;
				if($now_order['num']>1 || $pass_num>1){
					$pass_num = intval($now_order['num']) * $pass_num;
					for($i=0;$i<$pass_num-1;$i++){
						$group_pass_array = array(
								date('y',$_SERVER['REQUEST_TIME']),
								date('m',$_SERVER['REQUEST_TIME']),
								date('d',$_SERVER['REQUEST_TIME']),
								date('H',$_SERVER['REQUEST_TIME']),
								date('i',$_SERVER['REQUEST_TIME']),
								date('s',$_SERVER['REQUEST_TIME']),
								mt_rand($min,$max),
						);
						shuffle($group_pass_array);
						$tmp = implode('',$group_pass_array);
						$date_group_pass_array[]=array('order_id'=>$now_order['order_id'],'group_pass'=>$tmp,'status'=>0);
					}
					//$data_group_order['group_pass_array'] = serialize($group_pass_arr);
					if(!M('Group_pass_relation')->addAll($date_group_pass_array)){
						return array('error'=>1,'msg'=>L_('保存消费码失败！'));
					}
					$data_group_order['pass_array'] = 1;
				}
				
				
				
				//券票对接第三方，不修改原有逻辑，增加存储第三方的券。
				if(C('config.baodi_ticket_url') && $now_order['custom_product_id']){
					$nowGroup = M('Group')->where(['group_id' => $now_order['group_id']])->find();
					$userInputArr = unserialize($now_order['userinput']);
					import('@.ORG.third_custom.baodi.baodiTicket');
					$baodiTicket = new baodiTicket();
					$bookData = [
						'AccountID' => 'admin',
						'order_id' => $now_order['real_orderid'],
						'startDate' => date('Y-m-d'),
						'endDate' => date('Y-m-d', $nowGroup['end_time']),
						'custom_product_id'  => $now_order['custom_product_id'],
						'num' => $now_order['num'],
						'total_money' => $now_order['total_money'],
						'username' => $userInputArr['name'],
						'userphone' => $now_order['phone'],
						'idcard' => $userInputArr['idcard'],
					];
					$result = $baodiTicket->bookTicket($bookData);
					if($result['OrderLinkNo']){
						$data_group_order['group_pass'] = $result['OrderLinkNo'];
						$orderPassContentsArr = explode(',', $result['Contents']);
						if(count($orderPassContentsArr) > 1){
							M('Group_pass_relation')->where(['order_id' => $now_order['order_id']])->delete();
							$date_group_pass_array = [];
							foreach($orderPassContentsArr as $tmp){
								$date_group_pass_array[]=array('order_id'=>$now_order['order_id'],'group_pass'=>$tmp,'status'=>0);
							}
							if(!M('Group_pass_relation')->addAll($date_group_pass_array)){
								return array('error'=>1,'msg'=>L_('保存消费码失败！'));
							}
						}
					}
				}
				
			}

			//file_put_contents('log.txt',var_export($fisrt_group_pass,true));
			$data_group_order['pay_time'] = isset($order_param['pay_time']) && $order_param['pay_time'] ? strtotime($order_param['pay_time']) : $_SERVER['REQUEST_TIME'];
			$data_group_order['payment_money'] = floatval($order_param['pay_money']);
			$data_group_order['pay_type'] = $order_param['pay_type'];
			$data_group_order['third_id'] = $order_param['third_id'];
			$data_group_order['is_mobile_pay'] = $order_param['is_mobile'];
			$data_group_order['is_own'] = $order_param['sub_mch_id'] ? 2 : $order_param['is_own'];
			$data_group_order['paid'] = 1;
			$data_group_order['status'] = 0;

			if($this->where($condition_group_order)->data($data_group_order)->save()){
				/*----保存团购商品订单餐饮套餐信息 20/12/21----*/
				$database_group_store = D('Group_store');
				$condition_group_store = [
					'group_id' => $now_order['group_id'],
					'package_id' => ['gt', 0],
				];
				$group_store = $database_group_store->where($condition_group_store)->select();
				if (!empty($group_store)) {
					$group_foodshop_package = [];
					foreach ($group_store as $value) {
						$group_foodshop['store_id'] = $value['store_id'];
						$group_foodshop['group_id'] = $now_order['group_id'];
						$group_foodshop['order_id'] = $now_order['order_id'];
						$group_foodshop['uid'] = $now_order['uid'];
						$group_foodshop['package_id'] = $value['package_id'];
						$group_foodshop['num'] = $now_order['num'];
						$group_foodshop['create_time'] = time();
						$group_foodshop_package[] = $group_foodshop;
					}
					D('group_foodshop_package')->addAll($group_foodshop_package);
				}

				$condition_group['group_id'] = $now_order['group_id'];

				D('Scroll_msg')->add_msg('group',$now_user['uid'],L_('用户x1于x2购买x3成功',array('x1'=>$now_user['nickname'],'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'x3'=>$now_order['order_name'])));

				/*----更新系统订单信息 19/1/14----*/
				D('System_order')->save_reg_order_info('group',$now_order['order_id'],$data_group_order);
				//2015-11-19     更改结束状态
				//D('Group')->where($condition_group)->setInc('sale_count',$now_order['num']);
				if (empty($now_order['stock_reduce_method'])) {
					if ($now_order['specifications_id']) {
                        $m_group_specifications = M('Group_specifications');
                        $specifications_info = $m_group_specifications->field('sale_count')->where(array('specifications_id' => $now_order['specifications_id']))->find();
						
						$m_group_specifications->where(array('group_id' => $now_order['group_id'], 'specifications_id' => $now_order['specifications_id']))->setInc('sale_count', $now_order['num']);
						
                        // $sale_count = $now_order['num'] + $specifications_info['sale_count'];
                        // $update_group_data = array('sale_count' => $sale_count, 'last_time' => time());
                        // $m_group_specifications->where(array('group_id' => $now_order['group_id'], 'specifications_id' => $now_order['specifications_id']))->save($update_group_data);
					    $is_surplus = $this->get_spec_surplus_count($now_order['group_id']);
                        if (!$is_surplus) {
                        	// 如果团购存在规格且规格全部售空 团购结束
                            M('Group')->where($condition_group)->save(array('type' => 3));
						}
					} else {
                        // $update_group = D('Group')->where($condition_group)->find();
                        // $sale_count = $now_order['num'] + $update_group['sale_count'];
                        // $update_group_data = array('sale_count' => $sale_count);
//                        if ($update_group['count_num'] > 0 && $sale_count >= $update_group['count_num']) {//更改结束状态
//                            $update_group_data['type'] = 3;
//                        }
                        // D('Group')->where($condition_group)->save($update_group_data);
						
						D('Group')->where($condition_group)->setInc('sale_count', $now_order['num']);
					}
				}
				//D('User')->add_score($now_order['uid'],floor($now_order['total_money']*C('config.user_score_get')),'购买 '.$now_order['order_name'].' 消费'.floatval($now_order['total_money']).C('config.Currency_txt').' 获得积分');

				/* 粉丝行为分析 */
				D('Merchant_request')->add_request($now_order['mer_id'],array('group_buy_count'=>$now_order['num'],'group_buy_money'=>$now_order['total_money']));

				$this->group_app_notice($now_order['order_id'],1);

				if(C('config.open_score_pay_back') && C('config.system_take_spread_percent')>0 ){
					$spread_total_money = ($data_group_order['balance_pay']+$data_group_order['payment_money']+$data_group_order['score_deducte']+$data_group_order['coupon_price']+$data_group_order['merchant_balance']);
					$percent = D('Percent_rate')->get_percent($now_order['mer_id'],'group',$spread_total_money,$now_order['group_id'],$now_order['store_id'], true);
					$spread_total_money = $spread_total_money*$percent/100;
				}else{
					$spread_total_money = $balance_pay + $data_group_order['payment_money'];
				}
				
				if(C('config.add_spread_by_system_commission')){// 根据平台抽成获得佣金	
					// 获取平台抽成金额
					$update_order = array_merge($now_order, $data_group_order);							
					$update_order['get_system_take_money'] = true;
					$update_order['order_type'] = 'group';
					$update_order['verify_all'] = 1;
                    $spread_total_money =  D('SystemBill')->bill_method($update_order['is_own'],$update_order);
				}

				$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

				/* 计算用户分享获得佣金 */
				//open_single_spread 开启单个商品分销后，不走三级分佣
				// open_single_weixin_spread_user 开启微信公众号端分享商品获得佣金功能 并且订单中存在分享 走独立第一级分佣
				if ($now_order['share_openid']) {
					//上级分享佣金
					$spread_rate = D('Percent_rate')->get_user_spread_rate($now_order['mer_id'],'group',$now_order['group_id']);
					//上级分享获得积分
					$score_spread_rate = D('Percent_rate')->get_user_score_spread_rate($now_order['mer_id'],'group',$now_order['group_id']);
					$spread_user = M('User')->where(['openid' => $now_order['share_openid']])->find();
					$user_spread_rate = $spread_rate['first_rate'];
					$user_score_spread_rate = $score_spread_rate['first_rate'];

					$open_extra_price = C('config.open_extra_price');
					$href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';

					if($spread_user && ($user_spread_rate || $user_score_spread_rate) &&$spread_user['uid']!=$now_user['uid']){
						// 存在上级分佣佣金或者积分  且不是自己的分享
						if ($user_spread_rate ) {
							$spread_money = round($spread_total_money * $user_spread_rate / 100, 2);

							$spread_data = array('uid'=>$spread_user['uid'],'spread_uid'=>0,'get_uid'=>$now_user['uid'],'money'=>$spread_money,'order_type'=>'group','order_id'=>$now_order['order_id'],'third_id'=>$now_order['group_id'],'add_time'=>$_SERVER['REQUEST_TIME']);
							// 处理长订单id
							if ($now_order['real_orderid']) {
								$spread_data['real_orderid'] = $now_order['real_orderid'];
							}
							D('User_spread_list')->data($spread_data)->add();

							$buy_user = $now_user;
							if($spread_money>0){
								if($open_extra_price){
									$money_name = C('config.extra_price_alias_name');
								}else{
									$money_name = L_('佣金');
								}
								$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => L_("X1通过您的分享购买商品，验证消费后您将获得X2。",array("X1" => $buy_user['nickname'],"X2" => $money_name)) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
							}
						}
						if ($user_score_spread_rate) {
							// 一级分享积分获得
							$spread_score = round($spread_total_money * $user_score_spread_rate / 100, 2);
							$spread_data = array('uid'=>$spread_user['uid'],'spread_uid'=>0,'get_uid'=>$now_user['uid'],'money'=>$spread_score,'order_type'=>'group','spread_type'=>'1','order_id'=>$now_order[' '],'third_id'=>$now_order['group_id'],'add_time'=>$_SERVER['REQUEST_TIME']);
							// 处理长订单id
							if ($now_order['real_orderid']) {
								$spread_data['real_orderid'] = $now_order['real_orderid'];
							}
							D('User_spread_list')->data($spread_data)->add();
						}

					}
				} elseif(!empty($now_user['openid'])&&C('config.open_user_spread') && (!C('config.open_spread_appoint_user') || $now_user['can_spread']==1) && (C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money) && empty(C('config.open_single_spread'))&&empty(C('config.open_spread_rand'))){
					//上级分享佣金
					$spread_rate = D('Percent_rate')->get_user_spread_rate($now_order['mer_id'],'group',$now_order['group_id'],$now_order);
					//上级分享获得积分
					$score_spread_rate = D('Percent_rate')->get_user_score_spread_rate($now_order['mer_id'],'group',$now_order['group_id']);

					$spread_users[]=$now_user['uid'];
					
						$spread_where['_string'] = "uid = '{$now_user['uid']}'";
					
					$now_user_spread = D('User_spread')->where($spread_where)->find();
					$href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
					$open_extra_price = C('config.open_extra_price');
					if($data_group_order['is_own']  && C('config.own_pay_spread')==0){
						$data_group_order['payment_money']=0;
					}
					if(!empty($now_user_spread)){
							$spread_user = D('User')->get_user($now_user_spread['spread_uid']);
						$user_spread_rate = $spread_rate['first_rate'];
						$user_score_spread_rate = $score_spread_rate['first_rate'];
						if($spread_user && ($user_spread_rate || $user_score_spread_rate) &&!in_array($spread_user['uid'],$spread_users)){
							if ($user_spread_rate ) {
								$spread_money = round($spread_total_money * $user_spread_rate / 100, 2);

								$spread_data = array('uid'=>$spread_user['uid'],'spread_uid'=>0,'get_uid'=>$now_user['uid'],'money'=>$spread_money,'order_type'=>'group','order_id'=>$now_order['order_id'],'third_id'=>$now_order['group_id'],'add_time'=>$_SERVER['REQUEST_TIME']);
								if($spread_user['spread_change_uid']!=0){
									$spread_data['change_uid'] = 	$spread_user['spread_change_uid'];
								}
	                            // 处理长订单id
	                            if ($now_order['real_orderid']) {
	                                $spread_data['real_orderid'] = $now_order['real_orderid'];
	                            }
								D('User_spread_list')->data($spread_data)->add();

								$buy_user = D('User')->get_user($now_user_spread['uid'],'uid');
								if($spread_money>0){
									if($open_extra_price){
										$money_name = C('config.extra_price_alias_name');
									}else{
										$money_name = L_('佣金');
									}
									// $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => $buy_user['nickname'] . '通过您的分享购买了【' . $now_order['order_name'] . '】，验证消费后您将获得'.$money_name.L_('。'), 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
									$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => L_("X1通过您的分享购买商品，验证消费后您将获得X2。",array("X1" => $buy_user['nickname'],"X2" => $money_name)) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
								}
							}

							if ($user_score_spread_rate) {
								// 一级分享积分获得
								$spread_score = round($spread_total_money * $user_score_spread_rate / 100, 2);
								$spread_data = array('uid'=>$spread_user['uid'],'spread_uid'=>0,'get_uid'=>$now_user['uid'],'money'=>$spread_score,'order_type'=>'group','spread_type'=>'1','order_id'=>$now_order[' '],'third_id'=>$now_order['group_id'],'add_time'=>$_SERVER['REQUEST_TIME']);
								if($spread_user['spread_change_uid']!=0){
									$spread_data['change_uid'] = 	$spread_user['spread_change_uid'];
								}
	                            // 处理长订单id
	                            if ($now_order['real_orderid']) {
	                                $spread_data['real_orderid'] = $now_order['real_orderid'];
	                            }
								D('User_spread_list')->data($spread_data)->add();
								fdump(D('User_spread_list')->_sql(),'s');
							}

							$spread_users[]=$spread_user['uid'];
						}

						//第二级分享佣金
                        
                        $spread_where['_string'] = "uid = '{$spread_user['uid']}' ";
                        
						$second_user_spread = D('User_spread')->where($spread_where )->find();
						if(!empty($second_user_spread)&&!$open_extra_price) {
							
							$second_user = D('User')->get_user($second_user_spread['spread_uid']);
							
							// $sub_user_spread_rate = $update_group['sub_spread_rate'] > 0 ? $update_group['sub_spread_rate'] : C('config.user_first_spread_rate');
							$sub_user_spread_rate = $spread_rate['second_rate'];
							$sub_user_score_spread_rate = $score_spread_rate['second_rate'];
							if ($second_user && ($sub_user_spread_rate || $sub_user_score_spread_rate)&&!in_array($second_user['uid'],$spread_users)) {
								if ($sub_user_spread_rate) {
									$spread_money = round($spread_total_money * $sub_user_spread_rate / 100, 2);

									$spread_data =array('uid' => $second_user['uid'], 'spread_uid' => $spread_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'group', 'order_id' => $now_order['order_id'], 'third_id' => $now_order['group_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
									if($spread_user['spread_change_uid']!=0){
										$spread_data['change_uid'] = 	$second_user['spread_change_uid'];
									}
	                                // 处理长订单id
	                                if ($now_order['real_orderid']) {
	                                    $spread_data['real_orderid'] = $now_order['real_orderid'];
	                                }
									D('User_spread_list')->data($spread_data)->add();
	                                
                                    $sec_where['_string'] = "uid = '{$second_user_spread['uid']}'";
                                    $sec_user = M('User')->where($sec_where)->find();
	                                
									if($spread_money>0) {
										// $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => $sec_user['nickname'] .L_('的子用户').$buy_user['nickname'] . '通过您的分享购买了【' . $now_order['order_name'] . '】，验证消费后您将获得佣金。', 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
										$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => L_("X1的子用户X2通过您的分享购买商品，验证消费后您将获得佣金。",array("X1" => $sec_user['nickname'],"X2" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
									}
								}

								// 二级分享积分
								if ($sub_user_score_spread_rate) {
									$spread_score = round($spread_total_money * $sub_user_score_spread_rate / 100, 2);

									$spread_data =array('uid' => $second_user['uid'], 'spread_uid' => $spread_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_score, 'order_type' => 'group','spread_type' => '1', 'order_id' => $now_order['order_id'], 'third_id' => $now_order['group_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
									if($spread_user['spread_change_uid']!=0){
										$spread_data['change_uid'] = 	$second_user['spread_change_uid'];
									}
	                                // 处理长订单id
	                                if ($now_order['real_orderid']) {
	                                    $spread_data['real_orderid'] = $now_order['real_orderid'];
	                                }
									D('User_spread_list')->data($spread_data)->add();
	                                if($second_user_spread['openid']!=''){
	                                    $sec_where['_string'] = "openid = '{$second_user_spread['openid']}' OR wxapp_openid = '{$second_user_spread['openid']}' ";
	                                    $sec_user = M('User')->where($sec_where)->find();
	                                }
								}

								$spread_users[]=$second_user['uid'];
							}

							
							//顶级分享佣金
                            
                            $spread_where['_string'] = "uid = '{$second_user['uid']}' ";
                            
							$first_user_spread = D('User_spread')->where(	$spread_where)->find();

							if (!empty($first_user_spread) && C('config.user_third_level_spread')&&!$open_extra_price) {
								
								$first_spread_user = D('User')->get_user($first_user_spread['spread_uid']);
								
								$sub_user_spread_rate = $spread_rate['third_rate'];
								if ($first_spread_user && $sub_user_spread_rate&&!in_array($first_spread_user['uid'],$spread_users)) {
									$spread_money = round($spread_total_money* $sub_user_spread_rate / 100, 2);

									$spread_data =array('uid' => $first_spread_user['uid'], 'spread_uid' => $second_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'group', 'order_id' => $now_order['order_id'], 'third_id' => $now_order['group_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
									if($spread_user['spread_change_uid']!=0){
										$spread_data['change_uid'] = 	$first_spread_user['spread_change_uid'];
									}
                                    // 处理长订单id
                                    if ($now_order['real_orderid']) {
                                        $spread_data['real_orderid'] = $now_order['real_orderid'];
                                    }

									D('User_spread_list')->data($spread_data)->add();

                                
                                    $fir_where['_string'] = "uid = '{$first_user_spread['uid']}'";
                                    $fir_user = M('User')->where($fir_where)->find();
                                   
									if($spread_money>0) {
										// $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' =>$fir_user['nickname'].L_('的子用户的子用户').$buy_user['nickname'] . '通过您的分享购买了【' . $now_order['order_name'] . '】，验证消费后您将获得佣金。', 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
										$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' =>L_("X1的子用户的子用户X2通过您的分享购买了商品，验证消费后您将获得佣金。",array("X1" => $fir_user['nickname'],"X2" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => '点击查看详情！'), $now_order['mer_id']);
									}
								}

								// 三级分享积分
								$third_user_score_spread_rate = $score_spread_rate['third_rate'];
								if ($first_spread_user && $third_user_score_spread_rate&&!in_array($first_spread_user['uid'],$spread_users)) {
									$spread_score = round($spread_total_money* $third_user_score_spread_rate / 100, 2);

									$spread_data =array('uid' => $first_spread_user['uid'], 'spread_uid' => $second_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'group', 'spread_type' => '1', 'order_id' => $now_order['order_id'], 'third_id' => $now_order['group_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
									if($spread_user['spread_change_uid']!=0){
										$spread_data['change_uid'] = 	$first_spread_user['spread_change_uid'];
									}
	                                // 处理长订单id
	                                if ($now_order['real_orderid']) {
	                                    $spread_data['real_orderid'] = $now_order['real_orderid'];
	                                }
									D('User_spread_list')->data($spread_data)->add();
								}
							}

						}
						/*平台三级积分分佣 2018/12/13 客户定制*/
						if($spread_total_money>0 && $spread_user['uid']>0){
							if(C('config.first_jifen_percent')>0 || C('config.second_jifen_percent')>0 || C('config.third_jifen_percent')>0){
								$param=array();
							    $param['get_uid']=$now_user['uid'];
							    $param['first_uid']=$spread_user['uid'];
							    $param['second_uid']=$second_user['uid'];
							    $param['third_uid']=$first_spread_user['uid'];
							    $param['spread_total_money']=$spread_total_money;
							    $param['order_id']=$now_order['order_id'];
							    D('Percent_rate')->add_spread_jifen_log($param,'group',$now_order['store_id']);
							}
						}
					}
				}elseif(!empty($now_user['openid'])&&C('config.open_spread_rand')){
		            /* 开启三级推广随机一级推广奖励功能，唯一次奖励 */
					//一级推广用户
					$spread_res = D('User_spread')->spread_rand_only_once('group',$now_user,$now_order);
		        }elseif(!empty($now_user['openid'])&&C('config.open_spread_appoint_user') && $now_user['can_spread']==1 && C('config.open_spread_only_once')&&(C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money)){
		            /* 开启指定用户享受三级推广功能且开启唯一次奖励 */
					//一级推广用户
					$spread_res = D('User_spread')->spread_user_only_once('group',$spread_total_money,$now_user,$now_order);
		        }

				$now_group = D('Group')->where(array('group_id'=>$now_order['group_id']))->find();

				//【直播+短视频平台】 https://www.tapd.cn/47145533/prong/stories/view/1147145533001002522
				if($now_order['is_liveshow'] == '1' && $now_order['liveshow_id'] > 0){
					//添加销量
					D("Live_show_goods")->add_sale_nums($now_order['group_id'], $now_order['liveshow_id'], $now_order['num'], 'group');
				}
				if($now_order['is_livevideo'] == '1' && $now_order['livevideo_id'] > 0){
					//添加销量
					D("Live_video_goods")->add_sale_nums($now_order['group_id'], $now_order['livevideo_id'], $now_order['num'], 'group');
				}
				
				/* 计算用户分销获得佣金 */
				if (C('config.open_single_spread')==1&&$now_order['distributor_uid']&&$now_group['distributor_percent']) {
					$distributor_spread_data = array();
					$spread_total_money = $balance_pay + $data_group_order['payment_money'];
					$distributor_spread_money = round($spread_total_money*$now_group['distributor_percent']/100,2);
					if ($distributor_spread_money) {
						$distributor_spread_data =array(
							'spread_uid' => $now_order['distributor_uid'], 
							'order_uid' => $now_order['uid'],  
							'money' => $distributor_spread_money, 
							'order_type' => 'group',
							'order_id' => $now_order['order_id'], 
							'third_id' => $now_order['group_id'], 
							'real_orderid' => $now_order['real_orderid'],
							'add_time' => $_SERVER['REQUEST_TIME']
						);
						M('Distributor_spread_list')->data($distributor_spread_data)->add();
					}
				}
				
				$group_share_num = D('Group_share_relation')->get_share_num($now_order['uid'],$now_order['order_id']);

				//参团 单独购买不产生团购小组
				if($now_group['pin_num']>0 && !$now_order['single_buy']){
					if($now_order['is_head']){
						D('Group_start')->add_buyer_list($now_order['is_head'],$now_order['uid'],$now_order['order_id']);
						$date_start['num']=1;
						$date_start['status']=0;
						if($now_group['pin_num']==$date_start['num']){
							$date_start['status']=1;
						}
						M('Group_start')->where(array('id'=>$now_order['is_head']))->save($date_start);

					}elseif(!empty($now_order['pin_fid'])){
						D('Group_start')->add_group($now_order['group_id'],$now_order['uid'],$now_group,$now_order['order_id'],$now_order['pin_fid']);
					}else{
						D('Group_start')->add_group($now_order['group_id'],$now_order['uid'],$now_group,$now_order['order_id']);
					}
					$this->group_app_notice($now_order['order_id'],3);
				}

				//微信派发优惠券 支付到平台 微信支付
				if($data_group_order['is_own']==0 &&  $data_group_order['pay_type']=='weixin' && $data_group_order['payment_money']>=C('config.weixin_send_money')){
					D('System_coupon')->weixin_send( $data_group_order['payment_money'],$now_order['uid']);
				}

				//D('Action_relation')->add_user_action($order_param['order_id'],'group');

				if(C('config.open_extra_price')==1&&$now_order['extra_price']>0){
					$now_order['total_money']=($now_order['balance_pay']+$now_order['merchant_balance']+$order_param['pay_money']).'+'.$now_order['score_used_count'].C('config.score_name');
				}
				$_total = ($now_order['balance_pay']+$now_order['merchant_balance']+$order_param['pay_money']);
				if ($now_order['score_used_count'] && $now_order['score_used_count']>0) {
					$_total .= '+'.$now_order['score_used_count'].C('config.score_name');
				}

				//增加粉丝 5 group 6 shop 7 meal 8 appoint 9 store
				D('Merchant')->saverelation($now_user['openid'], $now_order['mer_id'], 5);

				if ($now_user['openid'] && $order_param['is_mobile']) {
					$href = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$now_order['order_id'];
					if($now_order['tuan_type'] < 2&&$now_group['pin_num']==0){
						if($now_group['open_num']==0&&$now_group['open_now_num']==0&&$now_group['group_share_num']==0){
							$remark = L_("X1成功，您的消费码：X2",array("X1" => C('config.group_alias_name'),"X2" => $data_group_order['group_pass'])); 
						}else{
							if($now_group['group_share_num']!=0&&$now_group['group_share_num']>$group_share_num){
								$remark = L_("X1成功，还差X2份才能成团，快分享给好友吧，成团后消费码将在订单详情中显示",array("X1" => C('config.group_alias_name'),"X2" => ($now_group['group_share_num']-$group_share_num))); 
							}else if($now_group['open_now_num']>$now_group['sale_count']&&$now_group['open_now_num']!=0&&$now_group['group_share_num']==0){
								$remark = L_("X1成功，还差X2份才能成团，快分享给好友吧，成团后消费码将在订单详情中显示",array("X1" => C('config.group_alias_name'),"X2" => ($now_group['open_now_num']-$now_group['sale_count']))); 
							}else if($now_group['open_num']>$now_group['sale_count']&&$now_group['open_num']!=0&&$now_group['open_now_num']==0&&$now_group['group_share_num']==0){
								$remark = L_("X1成功，还差X2份才能成团，快分享给好友吧，成团后消费码将在订单详情中显示",array("X1" => C('config.group_alias_name'),"X2" => ($now_group['open_num']-$now_group['sale_count']))); 
							}else{
								$remark = L_("X1成功，您的消费码：X2",array("X1" => C('config.group_alias_name'),"X2" => $fisrt_group_pass)); 
							}
						}
						$model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => C('config.group_alias_name').L_('提醒'), 'keyword1' => $now_order['order_name'], 'keyword2' => $now_order['real_orderid'], 'keyword3' => $_total, 'keyword4' => date('Y-m-d H:i:s'), 'remark' => $remark), $now_order['mer_id']);
					} else {
						$model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => C('config.group_alias_name').L_('提醒'), 'keyword1' => $now_order['order_name'], 'keyword2' => $now_order['real_orderid'], 'keyword3' => $_total, 'keyword4' => date('Y-m-d H:i:s'), 'remark' => C('config.group_alias_name').L_('成功，感谢您的使用')), $now_order['mer_id']);
					}
				}

				$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => 0, 'type' => 'group');
				if(!($now_user['openid'] && $now_user['is_follow']) && (C('config.sms_group_success_order') == 1 || C('config.sms_group_success_order') == 3)){
					$sms_data['uid'] = $now_order['uid'];
					$sms_data['mobile'] = $now_order['phone'];
					$sms_data['sendto'] = 'user';

					if ($data_group_order['group_pass']&&$now_group['pin_num']==0) {
						if($now_group['open_num']==0&&$now_group['open_now_num']==0&&$now_group['group_share_num']==0&&$now_group['pin_num']==0){
							$remark = L_("您的消费码：X1",array("X1" => $data_group_order['group_pass'])); 
						}else{
							if($now_group['group_share_num']!=0&&$now_group['group_share_num']>$group_share_num){
								$remark = L_("还差X1份才能成团，快分享给好友吧，成团后消费码将在订单详情中显示",array("X1" => ($now_group['group_share_num']-$group_share_num))); 
							}else if($now_group['open_now_num']>$now_group['sale_count']&&$now_group['open_now_num']!=0&&$now_group['group_share_num']==0){
								$remark = L_("还差X1份才能成团，快分享给好友吧，成团后消费码将在订单详情中显示",array("X1" => ($now_group['open_now_num']-$now_group['sale_count']))); 
							}else if($now_group['open_num']>$now_group['sale_count']&&$now_group['open_num']!=0&&$now_group['open_now_num']==0&&$now_group['group_share_num']==0){
								$remark = L_("还差X1份才能成团，快分享给好友吧，成团后消费码将在订单详情中显示",array("X1" => ($now_group['open_num']-$now_group['get_order_detail_by_id_and_merId']))); 
							}else{
								$remark = L_("您的消费码：X1",array("X1" => $data_group_order['group_pass'])); 
							}
						}
						$sms_data['content'] = L_('您购买 x1的订单(订单号：x2)已经完成支付,x3',array('x1'=>$now_order['order_name'],'x2'=>$now_order['real_orderid'],'x3'=>$remark));
					} else {
						$sms_data['content'] = L_('您购买 x1的订单(订单号：x2)已经完成支付!',array('x1'=>$now_order['order_name'],'x2'=>$now_order['real_orderid']));
					}

					Sms::sendSms($sms_data);
				}
				if (C('config.sms_group_success_order') == 2 || C('config.sms_group_success_order') == 3) {
					$merchant = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $merchant['phone'];
					$sms_data['sendto'] = 'merchant';
					$sms_data['content'] = L_('顾客购买的x1的订单(订单号：x2),在x3时已经完成了支付！',array('x1'=>$now_order['order_name'],'x2'=>$now_order['real_orderid'],'x3'=>date('Y-m-d H:i:s')));
					Sms::sendSms($sms_data);
				}
				if($now_order['trade_info']){
					$trade_info_arr = unserialize($now_order['trade_info']);
					if($now_order['stock_reduce_method'] == 0){
						D('Trade_hotel_category')->change_cat_stock($now_order['mer_id'],$trade_info_arr['cat_id'],$trade_info_arr['dep_time'],$trade_info_arr['end_time'],$trade_info_arr['num']);
					}
					
					//第三方酒店对接支付确定
					if($trade_info_arr['type'] == 'hotel'){
						import('@.ORG.third_custom.baodi.baodiHotel');
						$baodiHotel = new baodiHotel();
						$paid_data = [
							'HotelCode' => $trade_info_arr['baodi_hotel_info']['hotelCode'],
							'confirmationID' => $now_order['real_orderid'],
							'ResConfirm' => 1,
						];
						$result = $baodiHotel->bookHotelPaid($paid_data);
						fdump_sql([$paid_data, $result], 'baodiHotelPaid');
					}
					
				}

				//店员APP推送
				if($now_order['store_id']){
					D('Merchant_store_staff')->sendMsgGroupOrder($now_order);
				}

				$orderprint = D('Orderprinter')->field(true)->where(array('store_id' => $now_order['store_id']))->find();

				if(false!==strpos($orderprint['paid'],'1')){
					$printHaddle = new PrintHaddle();
					$printHaddle->printit($now_order['order_id'], 'group_order', 2);
				}
				
				/*----自动核销功能 19/10/21----*/
				$this->auto_check($now_order['order_id']);

				// 优惠组合订单添加一条支付记录
				if($now_order['is_group_combine']){
					$now_order['nickname'] = $now_user['nickname'];
					$now_order['avatar'] = $now_user['avatar'];
					$now_order['price'] = $now_order['price'];
					$now_order['pay_time'] = $data_group_order['pay_time'];
					D('Group_combine_activity')->addBuyLog($now_order);
					
					// 获得佣金
					if($now_order['share_uid'] && $now_order['spread_money']>0){
						$combineDetail = D('Group_combine_activity')->sendSpreadMoney($now_order);
					}
				}

				if($order_param['is_mobile']){
					return array('error'=>0,'url'=>str_replace('/source/','/',U('My/group_order',array('order_id'=>$now_order['order_id']))));
				}else{
					return array('error'=>0,'url'=>U('User/Index/group_order_view',array('order_id'=>$now_order['order_id'])));
				}
			}else{
				return array('error'=>1,'msg'=>L_('修改订单状态失败，请联系系统管理员！'));
			}
		}
	}
	
	//自动核销
	protected function auto_check($order_id){
		$now_order = $this->where(array('order_id'=>$order_id))->find();//这里必须重新获取一下订单详情
		$group_info = M('Group')->where(array('group_id'=>$now_order['group_id']))->find();
		if(empty($group_info['auto_check'])){//不支持自动核销
			return true;
		}else{
			$this->group_verify($now_order);
			return true;
		}
	}
	
	public function group_verify($now_order){
		if($now_order['is_head'] && $now_order['pin_fid']>0){//拼团订单
            $group_start = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
			$group_start_status  = $group_start['status'];
			if ($group_start_status == 0) {
				$lastsql = M()->getLastSql();
				fdump($lastsql,'group_auto_check_error',1);
				return false;
				//$this->error(L_('此订单尚未成团！'));
			} elseif ($group_start_status == 2) {
				$lastsql = M()->getLastSql();
				fdump($lastsql,'group_auto_check_error',1);
				return false;
				//$this->error(L_('此订单超过有效期，团购作废！'));
			}
        }else{
			$this->auto_check_inner($now_order);
			return true;
		}
		
		$order_list = $this->where(array('pin_fid'=>$now_order['pin_fid']))->field(true)->select();
		$store_id   = $this->get_store_id($now_order['group_id']);
		foreach($order_list as $key => $order){
			$this->auto_check_inner($order);		
		}
		return true;
	}
	
	private function auto_check_inner($order){
		if($order['status']!=0 && $order['status']!=7){
			$lastsql = M()->getLastSql();
			fdump($lastsql,'group_auto_check_error',1);
			return false;
		}
		$store_id = intval($order['store_id']);
		if($order['paid'] && ($order['status'] == 0 || $order['status']==7)){
			$condition_group_order['order_id'] = $order['order_id'];
			if (empty($order['third_id']) && $order['pay_type'] == 'offline') {
				$data_group_order['third_id'] = $order['order_id'];
			}
			$data_group_order['status'] = '1';
			$data_group_order['store_id'] = $order['store_id'];
			$data_group_order['use_time'] = $_SERVER['REQUEST_TIME'];
			$data_group_order['last_staff'] = '系统自动核销';
			$data_group_order['staff_name'] = '系统自动核销';
			$data_group_order['verify_time'] = $_SERVER['REQUEST_TIME'];
			$data_group_order['last_random'] = mt_rand(100000000,999999999);
			if($this->where($condition_group_order)->data($data_group_order)->save()){
				
				// 计算未验证的验证码数量
				$pass_num = $order['pass_num']>1?intval($order['pass_num']):1; // 一份团购 对应生成核销券 份数
				$consume_num = D('Group_pass_relation')->get_pass_num($order['order_id'],1);
				$unconsume_pass_num = $order['num']*$pass_num-$consume_num;
				$order['unconsume_pass_num'] = $unconsume_pass_num; // 未验证核销码数量

				$staff_info = array(
					'staff_name' => '系统自动核销',
					'verify_time' => $_SERVER['REQUEST_TIME'],
				);
				D('Group_pass_relation')->change_refund_status($order['order_id'], 1, $staff_info);
				//验证增加商家余额
				$order['order_type'] = 'group';
				$order['verify_all'] = 1;
				$order['store_id']   = $order['store_id'];;

				//D('Merchant_money_list')->add_money($this->store['mer_id'],L_('用户购买').$now_order['order_name'].L_('记入收入'),$now_order);
				$order['desc']=L_('用户购买X1记入收入',$order['order_name']);
				D('SystemBill')->bill_method($order['is_own'],$order);
				D("System_order")->save_reg_order_info('group',$order['order_id'],array(),2);
				$this->group_notice($order,1);
			}else{
				fdump(__LINE__.'------------'.$order['order_id'],'group_auto_check_error',1);
				fdump($this->getLastSql(),'group_auto_check_error',1);
			}
		}else{
			fdump(__LINE__.'------------'.$order['order_id'],'group_auto_check_error',1);
			fdump($order,'group_auto_check_error',1);
		}
	}
	
	public function group_notice($order,$verify_all)
	{
		//积分
		$now_user = M('User')->where(array('uid'=>$order['uid']))->find();
		if($verify_all){
			if(C('config.open_extra_price')==1){
				$order['order_type'] = 'group';
				$score = D('Percent_rate')->get_extra_money($order);
				if($score>0){
					D('User')->add_score($order['uid'], floor($score),L_('购买 X1 消费X2 获得X3',array('X1'=>$order['order_name'],'X2'=>floatval($order['total_money']) . C('config.Currency_txt'),'X3'=>C('config.extra_price_alias_name'))));
				}

			}else {
				if($this->config['add_score_by_system_commission']==0 && C('config.add_score_by_percent')==0 && (C('config.open_score_discount')==0 || $order['score_discount_type']!=2)&& (C('config.score_get_times')==0 || !C('config.score_get_times'))){
					if($order['is_own'] && C('config.user_own_pay_get_score')!=1 ){
						$order['payment_money'] = 0;
					}
					$score_get = D('Percent_rate')->get_user_add_score_times(C('config.score_get'),$order['uid']);
					D('User')->add_score($order['uid'], ($order['payment_money'] + $order['balance_pay']) * $score_get, L_('购买 X1 消费X2 获得X3',array('X1'=>$order['order_name'],'X2'=>floatval($order['total_money']) . C('config.Currency_txt'),'X3'=>C('config.score_name'))));
					D('Scroll_msg')->add_msg('group',$now_user['uid'],L_('用户X1于X2购买 X3成功并消费获得X4',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'X3'=>$order['order_name'],'X4'=>C('config.score_name'))));
				}
				D('Userinfo')->add_score($order['uid'], $order['mer_id'], $order['total_money'], L_('购买 X1 消费X2 获得积分',array('X1'=>$order['order_name'],'X2'=>floatval($order['total_money']) . C('config.Currency_txt'))));
			}
			//商家推广分佣

			D('Merchant_spread')->add_spread_list($order,$now_user,'group',L_('X1购买X2获得佣金',array('X1'=>$now_user['nickname'],'X2'=>C('config.group_alias_name'))));

		}
		D('Group_order')->group_app_notice($order['order_id'],7);
		//短信
		$sms_data = array('mer_id' => $order['mer_id'], 'store_id' => $order['store_id'], 'type' => 'group');
		if($now_user['openid']){

			$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
			$href = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$order['order_id'];
			$model->sendTempMsg('TM00017', array('href' => $href,
					'wecha_id' => $now_user['openid'],
					'first' => C('config.group_alias_name').L_('团购提醒'),
					'OrderSn' => $order['real_orderid'],
					'OrderStatus' =>L_("您购买的团购已于X1核销成功",date('Y-m-d H:i:s',time())),
					'remark' =>L_('点击查看详情')),
				$order['mer_id']);
		}

		if (C('config.sms_group_finish_order') == 1 || C('config.sms_group_finish_order') == 3) {
			$sms_data['uid'] = $order['uid'];
			$sms_data['mobile'] = $order['phone'];
			$sms_data['sendto'] = 'user';
			if(empty($order['res'])){
				$sms_data['content'] = L_('您购买 X1的订单(订单号：X2)已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！',array('X1'=>$order['order_name'],'X2'=>$order['real_orderid']));
			}else{
				$sms_data['content'] = L_('您购买 X1的订单(消费码：X2)已经完成了消费，如有任何疑意，请您及时联系我们！',array('X1'=>$order['order_name'],'X2'=>$order['res']['group_pass']));
			}
			Sms::sendSms($sms_data);
		}
		if (C('config.sms_group_finish_order') == 2 || C('config.sms_group_finish_order') == 3) {
			$phone = M('Merchant_store')->where(['store_id' => $order['store_id']])->getField('phone');
			if ($phone) {
				$sms_data['uid'] = 0;
				$sms_data['mobile'] = $phone;
				$sms_data['sendto'] = 'merchant';
				$sms_data['content'] = L_('顾客购买的X1的订单(订单号：X2),已经完成了消费！',array('X1'=>$order['order_name'],'X2'=>$order['real_orderid']));
				Sms::sendSms($sms_data);
			}
		}

		//打印
		$printHaddle = new PrintHaddle();
		$printHaddle->printit($order['order_id'], 'group_order', 2);
	}
	
	
	public function get_store_id($group_id){
		$info = M('Group_store')->where(array('group_id'=>$group_id))->field('store_id')->find();
		return $info['store_id'];
	}
	
	//支付时，金额不够，记录到帐号
	public function wap_after_pay_error($now_order,$order_param,$error_tips){
		//记录充值的金额，因为 Pay/return_url 处没有返回order的具体信息，故在此调用。
		$user_result['error_code'] = 0;
		if($order_param['pay_money'] > 0){
			$alreadyMoney = M("User_money_list")
				->where(['money'=>$order_param['pay_money'],'uid'=>$now_order['uid'],'ask'=> 12, 'ask_id'=>$now_order['order_id']])//12 group_order
				->order('pigcms_id desc')
				->find();
			if(empty($alreadyMoney)){
				//$user_result = D('User')->add_money($now_order['uid'],$order_param['pay_money'],L_("支付订单：X1发生错误！原因是：X2",array("X1" => $now_order['order_id'],"X2" => $error_tips)),12,$now_order['order_id']);
			}
		}
		$this->where(array('order_id'=>$now_order['order_id']))->setField('status',3);
		if($order_param['pay_type']=='weixin'&&ACTION_NAME=='return_url'){
			exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
		}
		if($user_result['error_code']){
			return array('error'=>1,'msg'=>$user_result['msg']);
		}else{
			if($order_param['is_mobile']){
				$return_url = str_replace('/source/','/',U('My/group_order',array('order_id'=>$now_order['order_id'])));
			}else{
				$return_url = U('User/Index/group_order_view',array('order_id'=>$now_order['order_id']));
			}
			return array('error'=>1,'msg'=>$error_tips.L_('，已将您充值的金额添加到您的余额内。'),'url'=>$return_url);
		}
	}
	//修改订单状态
	public function change_status($order_id,$status){
		$condition_group_order['order_id'] = $order_id;
		$data_group_order['status'] = $status;
		if($this->where($condition_group_order)->data($data_group_order)->save()){
			return true;
		}else{
			return false;
		}
	}
	/*获得订单链接*/
	public function get_order_url($order_id,$is_wap=false){
		if($is_wap){
			return U('My/group_order',array('order_id'=>$order_id));
		}else{
			return U('User/Index/group_order_view',array('order_id'=>$order_id));
		}
	}
	//获取某个时间段的订单总数
	public function get_all_oreder_count($type = 'day'){
		$stime = $etime = 0;
		switch ($type) {
			case 'day' :
				$stime = strtotime(date("Y-m-d") . " 00:00:00");
				$etime = strtotime(date("Y-m-d") . " 23:59:59");
				break;
			case 'week' :
				$d = date("w");
				$stime = strtotime(date("Y-m-d") . " 00:00:00") - $d * 86400;
				$etime = strtotime(date("Y-m-d") . " 23:59:59") + (6 - $d) * 86400;
				break;
			case 'month' :
				$stime = mktime(0, 0, 0, date("m"), 1, date("Y"));
				$etime = mktime(0, 0, 0, date("m") + 1, 1, date("Y"));
				break;
			case 'year' :
				$stime = mktime(0, 0, 0, 1, 1, date("Y"));
				$etime = mktime(0, 0, 0, 1, 1, date("Y")+1);
				break;
			default :;
		}
		$total = $this->where("`paid`=1 AND `add_time`>'$stime' AND `add_time`<'$etime'")->count();
		return $total;
	}


	/**
	 * @param int $uid
	 * @param int $group_id
     * @param int $num
     * @param int $specifications_id
     * @param int $order_id
	 * @return array
	 */
	public function check_buy_num($uid, $group_id, $num, $specifications_id = 0, $order_id=0)
	{
        if ($specifications_id && $specifications_id > 0) {
            $where_sql = "group_id='{$group_id}' AND uid='{$uid}' AND paid=1 AND status<3 AND specifications_id='{$specifications_id}'";
            if ($order_id>0) {
                $where_sql .= " AND order_id != {$order_id}";
            }
            $count = $this->where($where_sql)->sum('num');
            $group = M('Group_specifications')->where(array('specifications_id' => $specifications_id))->find();
        } else {
            $where_sql = "group_id='{$group_id}' AND uid='{$uid}' AND paid=1 AND status<3";
            if ($order_id>0) {
                $where_sql .= " AND order_id != {$order_id}";
            }
            $count = $this->where($where_sql)->sum('num');
            $group = D('Group')->get_group_by_groupId($group_id);
        }

		$max_num = $group['once_max'];//一次购买最多的数量

		$min_num = $group['once_min'];//一次最少数量

		// 当前团购商品
		$groupGoods = D('Group')->get_group_by_groupId($group_id);

		//商品有库存限制的时候
		if ($group['count_num'] > 0) {
			$k_num = $group['count_num'] - $group['sale_count']; //实际库存
			
			if ($k_num < $min_num) {//库存等于销售量的时候不能买
                fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                fdump('---$uid-----'.$uid,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                fdump('---$group_id-----'.$group_id,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                fdump('---$num-----'.$num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                fdump('---$specifications_id-----'.$specifications_id,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                fdump('---$order_id-----'.$order_id,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                fdump('---$max_num-----'.$max_num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                fdump('---$min_num-----'.$min_num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                fdump('---count_num-----'.$group['count_num'],'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                fdump('---sale_count-----'.$group['sale_count'],'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                fdump('---$k_num-----'.$k_num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                fdump('---行数-----'.__LINE__,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
				return array('errcode' => 1, 'msg' => L_('该商品已售空'));
			} else {
				if ($max_num > 0) {
					$my_num = $max_num - $count;//我的剩余得到份数
					$my_num = $my_num > $k_num ? $k_num : $my_num;
					$my_num = $my_num > $max_num ? $max_num : $my_num;

					if ($my_num < $num) {
						if ($my_num > 0) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$uid-----'.$uid,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$group_id-----'.$group_id,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$num-----'.$num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$specifications_id-----'.$specifications_id,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$order_id-----'.$order_id,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$max_num-----'.$max_num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$min_num-----'.$min_num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$count-----'.$count,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$my_num-----'.$my_num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$k_num-----'.$k_num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---行数-----'.__LINE__,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
							return array('errcode' => 1, 'msg' => L_("您最多能购买X1份",array("X1" => $my_num))); 
						} else {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$uid-----'.$uid,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$group_id-----'.$group_id,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$num-----'.$num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$specifications_id-----'.$specifications_id,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$order_id-----'.$order_id,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$max_num-----'.$max_num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$min_num-----'.$min_num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$count-----'.$count,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$my_num-----'.$my_num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---$k_num-----'.$k_num,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
                            fdump('---行数-----'.__LINE__,'api/log/'.date('Ymd').'/check_buy_num_err_log',true);
							return array('errcode' => 1, 'msg' => L_("该商品限制单人只能购买X1份，您购买的数量已达上限，不能再购买!",array("X1" => $max_num))); 
						}
					} elseif ($num < $min_num) {
						return array('errcode' => 1, 'msg' => L_("您一次最少要购买X1份",array("X1" => $min_num))); 
					}
				} else {
					if ($num > $k_num) {
						return array('errcode' => 1, 'msg' => L_("您最多能购买X1份",array("X1" => $k_num))); 
					}
				}
			}
		} else {
			if ($max_num > 0) {
				$my_num = $max_num - $count;//我的剩余得到份数
				$my_num = $my_num > $max_num ? $max_num : $my_num;
				if ($my_num < $num) {
					if ($my_num > 0) {
						return array('errcode' => 1, 'msg' => L_("您最多能购买X1份",array("X1" => $my_num))); 
					} else {
						return array('errcode' => 1, 'msg' => L_("此商品每个用户只能购买X1份",array("X1" => $max_num))); 
					}
				} elseif ($num < $min_num) {
					return array('errcode' => 1, 'msg' => L_("您一次最少要购买X1份",array("X1" => $min_num)));
				}
			}
		}

		if ($num < $min_num) {
			return array('errcode' => 1, 'msg' => L_("您一次最少要购买X1份",array("X1" => $min_num))); 
		}
		
		//每ID每天限购
		if($group['once_max_day']){
			$now_user_today_count = $this->get_once_max_day($group_id,$uid,$specifications_id);
			$today_can_buy = $group['once_max_day'] - $now_user_today_count;
			if($today_can_buy <= 0 || $today_can_buy < $num){
				return array('errcode' => 1, 'msg' => L_("该商品限制单人每天只能购买X1份，您当天购买的数量已达上限，不能再购买!",array("X1" => $group['once_max_day']))); 
			}
		}
		
		return array('errcode' => 0);
	}
	/*
    * @param  string  $client       推送平台设置  1苹果 2安卓 3苹果、安卓
    * @param  array   $audience     推送设备指定  all全部用户 "android", "ios", "winphone"。
    * @param  array   $notification 通知内容体   “通知”对象，是一条推送的实体内容对象之一（另一个是“消息”），是会作为“通知”推送到客户端的。
    * @param  array   $message      消息内容体   应用内消息。或者称作：自定义消息，透传消息。
    * @return boolean $audience     推送的用户   all全部用户
    */
	public  function    AuroraMass($client=3, $title, $msg, $extra=array(),$audience='all'){
		switch($client){
			case 1:
				$platform   =   array('ios');break;
			case 2:
				$platform   =   array('android');break;
			default:
				$platform   =   'all';
		}
		import('@.ORG.Jpush');
		$jpush = new Jpush(C('config.weixin_push_jpush_appkey'), C('config.weixin_push_jpush_secret'));
		$notification = $jpush->createBody($client, $title, $msg, $extra);
		$message      = $jpush->createMsg($title, $msg, $extra);
		return($jpush->send($platform, $audience, $notification,$message));
	}

	/**
	 * 回滚团购的销量
	 */
	public function stock_rollback()
	{
		//20分钟前的订单回滚
		$nowtime = time() - 1200;
		$orders = $this->field('group_id, order_id, num, specifications_id')->where("`stock_reduce_method`=1 AND `paid`=0 AND `status`=0 AND `add_time`<'{$nowtime}'")->order('order_id ASC')->limit('0, 30')->select();
		$groups = array();
		$specifications_info = array();
		$m_group_specifications = M('Group_specifications');
		foreach ($orders as $order) {
			if ($order['specifications_id'] && isset($specifications_info[$order['specifications_id']])) {
				// 如果是团购规格 且已存在回滚信息 累加
                $specifications_info[$order['specifications_id']] += $order['num'];
			} elseif($order['specifications_id']) {
                // 如果是团购规格 查询赋值
                $specifications_info[$order['specifications_id']] = $order['num'];
			} elseif (isset($groups[$order['group_id']])) {
                $groups[$order['group_id']] += $order['num'];
            } else {
                $groups[$order['group_id']] = $order['num'];
            }
			// $this->where(array('order_id' => $order['order_id']))->save(array('status' => 5));

			// 改为已取消
			$this->where(array('order_id' => $order['order_id']))->save(array('status' => 4));

			/*-----记录系统订单信息 订单未支付取消 改写状态已取消 2021/1/6---团购状态-未付款取消订单4-*/
			D("System_order")->save_order_status('group',$order['order_id'],5,4);

//     		D('Group')->where(array('group_id' => $order['group_id']))->setDec('sale_count', $order['num']);
		}
		foreach ($groups as $group_id => $num) {
			D('Group')->where(array('group_id' => $group_id, 'sale_count' => array('egt', $num)))->setDec('sale_count', $num);
		}
		// 如果存在规格信息 销量回滚
        foreach ($specifications_info as $specifications_id => $num) {
            $m_group_specifications->where(array('specifications_id' => $specifications_id, 'sale_count' => array('egt', $num)))->setDec('sale_count', $num);
        }
		return;
	}
	//app 极光推送 支付成功
	public function  group_app_notice($order_id ,$status=0){
		import('@.ORG.Apppush');
		$now_order = M('Group_order')->where(array('order_id'=>$order_id))->find();
		$now_order['status'] = $status;
		$apppush = new Apppush();
		$apppush->send($now_order, 'group');
	}
	//得到一个用户今天买了多少份同一个团购
	public function get_once_max_day($group_id,$uid, $specifications_id = 0, $time = true){
		$condition_once_max_day['group_id'] = $group_id;
        if ($specifications_id && $specifications_id > 0) {
            $condition_once_max_day['specifications_id'] = $specifications_id;
        }
		$condition_once_max_day['uid'] = $uid;
		$condition_once_max_day['paid'] = '1';
		$condition_once_max_day['status'] = array('lt',3);
        if ($time) {
            $today_zero = strtotime(date('Y-m-d', time()));
            $condition_once_max_day['pay_time'] = array('between',array($today_zero,$today_zero+86400));
        }
		$once_max_day = M('Group_order')->where($condition_once_max_day)->sum('num');
		return $once_max_day;
	}
    // 计算出用户团购规格下当前可购买数量
    public function get_spec_buy_num($group_id, $specifications_info, $uid) {
        //每ID每天限购
        $surplus = $specifications_info['count_num'] - $specifications_info['sale_count'];
        if($specifications_info['once_max_day']){
            // 获取今日 用户已经购买的数量
            $now_user_today_count = $this->get_once_max_day($group_id, $uid, $specifications_info['specifications_id'], true);
            $today_can_buy = $specifications_info['once_max_day'] - $now_user_today_count;
            if($today_can_buy <= 0){
                // 将今日可够该规格数量固定为0
				return 0;
            }
            if ($specifications_info['once_max'] && $specifications_info['once_max'] > 0) {
                // 获取用户已经购买的总数量
                $now_user_count = $this->get_once_max_day($group_id, $uid, $specifications_info['specifications_id'], false);
                $can_buy_total = $specifications_info['once_max'] - $now_user_count;
                if($can_buy_total <= 0){
                    // 将今日可够该规格数量固定为0
                    return 0;
                }
                $today_can_buy = min($today_can_buy, $can_buy_total);
            }
            if ($specifications_info['count_num'] > 0) {
                if ($surplus <= 0){
                    // 将今日可够该规格数量固定为0
                    return 0;
                }
                $today_can_buy = min($today_can_buy, $surplus);
            }
        } else {
            if ($specifications_info['count_num'] > 0 && $surplus <= 0){
                // 将今日可够该规格数量固定为0
                return 0;
            } elseif ($specifications_info['count_num'] > 0) {
                $today_can_buy = $surplus;
            } else {
                $today_can_buy = -1;
            }
        }
        return $today_can_buy;
    }

	// 计算出用户团购规格下当前可购买数量
	public function get_spec_buy_num_new($group_id, $specifications_info, $uid) {

		//当前剩余库存
		if ($specifications_info['count_num']>0) {
			$surplus = $specifications_info['count_num'] - $specifications_info['sale_count'];
			$returnArr = [];
			if($surplus<=0) {
				$returnArr['num'] = 0;
				$surplus = 0;
			}
		} else {
			$surplus = -1;
			$returnArr['num'] = 0;
		}

		//每ID每天限购
		if($specifications_info['once_max_day']) {
			// 获取今日 用户已经购买的数量
			$now_user_today_count = $this->get_once_max_day($group_id, $uid, $specifications_info['specifications_id'], true);
			$today_can_buy = intval($specifications_info['once_max_day']) - intval($now_user_today_count);
			if ($today_can_buy <= 0) {
				// 将今日可够该规格数量固定为0
				$returnArr['num'] = 0;
				$returnArr['desc'] = L_('每个用户每天最多购买X1份', $specifications_info['once_max_day']);
				// 每次最少购买数量
				if ($specifications_info['once_min'] > 1) {
					$returnArr['desc'] .= '，' . L_('每个用户每次最少购买X1份', $specifications_info['once_min']);
				}
				return $returnArr;

			} else {
				$returnArr['desc'] = L_('每个用户每天最多购买X1份', $specifications_info['once_max_day']);
			}
		}

		//每ID限购
		if ($specifications_info['once_max'] && $specifications_info['once_max'] > 0) {
			// 获取用户已经购买的总数量
			$now_user_count = $this->get_once_max_day($group_id, $uid, $specifications_info['specifications_id'], false);
			$can_buy_total = $specifications_info['once_max'] - $now_user_count;
			if($can_buy_total <= 0){
				// 将今日可够该规格数量固定为0
				$returnArr['num'] = 0;
				$returnArr['desc'] = L_('每个用户最多购买X1份',$specifications_info['once_max']);
				return $returnArr;
			}else{
				$returnArr['desc'] = ($returnArr['desc'] ? $returnArr['desc'].'，' : '').L_('每个用户最多购买X1份',$specifications_info['once_max']);
			}
			$today_can_buy = min($today_can_buy, $can_buy_total);
		}

		if(isset($today_can_buy)){
			// 今日可购买数量
			if ($surplus>=0) {
				$today_can_buy = min($today_can_buy, $surplus);
			}
		} else {
			if ($specifications_info['count_num'] > 0 && $surplus <= 0){
				// 将今日可够该规格数量固定为0
				$returnArr['num'] = 0;
				return $returnArr;
			} elseif ($specifications_info['count_num'] > 0) {
				$today_can_buy = $surplus;
			} else {
				$today_can_buy = -1;
			}
		}
		if($specifications_info['once_min']>1){
			$returnArr['desc'] = ($returnArr['desc'] ? $returnArr['desc'].'，' : '').L_('每个用户每次最少购买X1份',$specifications_info['once_min']);
		}
		$returnArr['num'] = $today_can_buy;
		return $returnArr;

	}

    // 计算团购的规格是否所有均售空
	public function get_spec_surplus_count($group_id) {
		$spec_info = M('Group_specifications')->where(array('group_id' => $group_id, 'status' => 1))->select();
		// 是否还有剩余未售空
		$is_surplus = false;
		if ($spec_info) {
			foreach ($spec_info as $val) {
                if ($val['count_num'] <= 0) {
                    $is_surplus = true;
                    break;
                }
				if (intval($val['count_num']) > intval($val['sale_count'])) {
                    $is_surplus = true;
                }
			}
		}
		return $is_surplus;
	}
	// 计算团购规格的所有销量
	public function get_spec_sale_count($group_id)
    {
        $spec_info = M('Group_specifications')->where(array('group_id' => $group_id, 'status' => 1))->select();
        // 计算总销量
        $sale_count_total = 0;
        if ($spec_info) {
            foreach ($spec_info as $val) {
                if (intval($val['sale_count']) > 0) {
                    $sale_count_total += intval($val['sale_count']);
                }
            }
        }
        return $sale_count_total;
    }
    // 查询团购规格中最便宜的价格
	public function get_spec_cheap($group_id) {
        $spec_info = M('Group_specifications')->where(array('group_id' => $group_id, 'status' => 1))->order('price ASC')->find();
        return $spec_info;
	}
	// 计算团购规格的所有库存
    public function get_spec_count_num($group_id)
    {
        $spec_info = M('Group_specifications')->where(array('group_id' => $group_id, 'status' => 1))->select();
        // 计算总库存
        $count_num_total = 0;
        if ($spec_info) {
            foreach ($spec_info as $val) {
                if (intval($val['count_num']) > 0) {
                    $count_num_total += intval($val['count_num']);
                }
            }
        }
        $spec_num = count($spec_info);
        $arr = array(
            'spec_num' => $spec_num,
            'count_num_total' => $count_num_total,
		);
        return $arr;
    }


    // 恢复核销异常数据
	public function re_write_group_verify() {
        $d_group_pass_relation = M('Group_pass_relation');
        $d_merchant_money_list = M('Merchant_money_list');
        $verify_where = array(
            'gp.verify_time' => array('egt', '1574784000'), // 检查 日期2019/11/27日之后出现问题的核销并恢复
            'gp.status' => 1
        );
        $field = 'gp.id,gp.status as pass_status, gp.order_id, gp.group_pass, go.mer_id,go.status, go.store_id, go.real_orderid';
        $info = $d_group_pass_relation->join('as gp left join ' . C('DB_PREFIX') . 'group_order as go ON gp.order_id = go.order_id')->field($field)->where($verify_where)->order('gp.id ASC')->select();
        $no_verify_group = array();
        foreach ($info as $val) {
            $val['desc'] = L_('验证团购订单X1的消费码</br>X2记入收入',array('X1'=>$val['real_orderid'],'X2'=>$val['group_pass']));
            $msg = $d_merchant_money_list->where(array('order_id' => $val['real_orderid'], 'desc' => $val['desc']))->find();
            if (!$msg) {
                $no_verify_group[] = $val;
            }
        }
        $database_group_order = D('Group_order');
        if ($no_verify_group) {
            foreach ($no_verify_group as $item) {
                $now_order = $database_group_order->get_order_detail_by_id_and_merId($item['mer_id'],$item['order_id'],false);
                //验证增加商家余额
                $now_order['order_type'] = 'group';
                $now_order['verify_all'] = 0;
                $now_order['store_id'] = $item['store_id'];
                if ($item['desc']) {
                    $now_order['desc'] = $item['desc'];
                } else {
                    $now_order['desc'] = L_('验证团购订单X1的消费码</br>X2记入收入',array('X1'=>$item['real_orderid'],'X2'=>$item['group_pass']));
                }
                fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/re_write_verify_log',true);
                fdump($now_order,'api/log/'.date('Ymd').'/re_write_verify_log',true);
                D('SystemBill')->bill_method($now_order['is_own'],$now_order);
                sleep(2);
            }
        }
    }
    /**
	 * 团购商品生成消费码
	 * author 衡婷妹
	 * date 20201120
	*/
    public function createGroupPass($now_order){

		if($now_order['tuan_type'] < 2){
			// 团购组合优惠消费码长度16位 其他14位
			if($now_order['is_group_combine']){
				$min = 1000;
				$max = 9999;
			}else{
				$min = 10;
				$max = 99;
			}

			$group_pass_array = array(
				date('y',$_SERVER['REQUEST_TIME']),
				date('m',$_SERVER['REQUEST_TIME']),
				date('d',$_SERVER['REQUEST_TIME']),
				date('H',$_SERVER['REQUEST_TIME']),
				date('i',$_SERVER['REQUEST_TIME']),
				date('s',$_SERVER['REQUEST_TIME']),
				mt_rand($min,$max),
			);
			shuffle($group_pass_array);

			$returnArr['group_pass'] = implode('',$group_pass_array);
			$fisrt_group_pass =  $returnArr['group_pass'];
			$group_pass_arr[$returnArr['group_pass']] =0;
			$date_group_pass_array[]=array('order_id'=>$now_order['order_id'],'group_pass'=>$returnArr['group_pass'],'status'=>0);
			$pass_num = $now_order['pass_num']>0?intval($now_order['pass_num']):1;
			if($now_order['num']>1 || $pass_num>1){
				$pass_num = intval($now_order['num']) * $pass_num;
				for($i=0;$i<$pass_num-1;$i++){
					$group_pass_array = array(
						date('y',$_SERVER['REQUEST_TIME']),
						date('m',$_SERVER['REQUEST_TIME']),
						date('d',$_SERVER['REQUEST_TIME']),
						date('H',$_SERVER['REQUEST_TIME']),
						date('i',$_SERVER['REQUEST_TIME']),
						date('s',$_SERVER['REQUEST_TIME']),
						mt_rand($min,$max),
					);
					shuffle($group_pass_array);
					$tmp = implode('',$group_pass_array);
					$date_group_pass_array[]=array('order_id'=>$now_order['order_id'],'group_pass'=>$tmp,'status'=>0);
				}
				M('Group_pass_relation')->addAll($date_group_pass_array);
				$returnArr['pass_array'] = 1;
			}
		}
		return $returnArr;
	}
}
?>
