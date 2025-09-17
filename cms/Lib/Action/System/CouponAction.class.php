<?php 
/*
 *2016年2月24日09:04:46
 *平台优惠券功能
 */
class CouponAction extends BaseAction {
		
		public function index(){
			//dump($this->system_session);exit;
			if (!empty($_GET['keyword'])) {
				if ($_GET['searchtype'] == 'coupon_id') {
					$condition_coupon['coupon_id'] = $_GET['keyword'];
				} else if ($_GET['searchtype'] == 'name') {
					$condition_coupon['name'] = array('like', '%' . $_GET['keyword'] . '%');
				}
			}
			$condition_coupon['delete'] = 0;
			$condition_coupon['status'] = array('neq',4);
			isset($_GET['status']) && $_GET['status']>=0 && $condition_coupon['status'] = $_GET['status'];
			//排序 /*/
			$order_string = '`coupon_id` DESC';
			if($_GET['sort']){
				switch($_GET['sort']){
					case 'uid':
						$order_string = '`uid` DESC';
						break;
					case 'lastTime':
						$order_string = '`last_time` DESC';
						break;
					case 'money':
						$order_string = '`now_money` DESC';
						break;
					case 'score':
						$order_string = '`score_count` DESC';
						break;
				}
			}
			
			if ($this->system_session['area_id'] && $this->config['many_city']) {
				$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
				if($now_area['area_type']==3){
					die('区域管理员无法管理优惠券');
				}elseif($now_area['area_type']==2){
					$area_index = 'city_id';
				}elseif($now_area['area_type']==1){
					$area_index = 'province_id';
				}
				$this->assign('admin_area',$now_area['area_type']);
				$condition_coupon[$area_index] = $this->system_session['area_id'];
			}
			if($_GET['province_idss'] && $this->config['many_city']){
				$condition_coupon['province_id'] =$_GET['province_idss'];
			}
			if($_GET['city_idss']){
				$condition_coupon['city_id'] =$where['city_id']= $_GET['city_idss'];
			}
			$condition_coupon['is_partition'] = 0;
			$coupon = M('System_coupon');
			$count_count = $coupon->where($condition_coupon)->count();
			import('@.ORG.system_page');
			$p = new Page($count_count, 15);
			$coupon_list = $coupon->where($condition_coupon)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
			
			$city_id_arr = array();
			foreach($coupon_list as $key=>&$v){
				$v['platform']=unserialize($v['platform']);
				if($v['cate_name']!='all'&&!empty($v['cate_id'])){
					$tmp = unserialize($v['cate_id']);
					$v['cate_id'] = $tmp['cat_name'];
				}

				if($v['end_time']<$_SERVER['REQUEST_TIME']){
					D('System_coupon')->where(array('coupon_id'=>$v['coupon_id']))->setField('status',2);
					$v['status']=2;
				}
				$v['use_count'] = M('System_coupon_hadpull')->where(array('coupon_id'=>$v['coupon_id'],'is_use'=>1))->count();
				if(!empty($v['city_id']) && !in_array($v['city_id'],$city_id_arr)){
					$city_id_arr[] = $v['city_id'];
				}
				//编辑权限
				if($this->system_session['level']==0 ||$this->system_session['level']==2){
					$coupon_list[$key]['edit'] = 1;
				}
			}
			if(!empty($city_id_arr)){
				$city_arr = M('Area')->field('`area_id`,`area_name`')->where(['area_id'=>['in',$city_id_arr]])->select();
				$city_key_arr = [];
				foreach($city_arr as $c_k=>$c_v){
					$city_key_arr[$c_v['area_id']] = $c_v['area_name'];
				}
				foreach($coupon_list as $key=>&$v){
					if($v['city_id'] && $city_key_arr[$v['city_id']]){
						$v['city_name'] = $city_key_arr[$v['city_id']];
					}
					//编辑权限
					if($this->system_session['level']==0 ||$this->system_session['level']==2 || $this->system_session['city_id']==$v['area_id']){
						$coupon_list[$key]['edit'] = 1;
					}

				}
			}
			
			$return =  D('System_coupon')->cate_platform();
			$this->assign("category",$return['category']);
			$this->assign("platform",$return['platform']);
			$this->assign('coupon_list',$coupon_list);
			$pagebar = $p->show();
			$this->assign('pagebar', $pagebar);
			$this->assign('level', $this->system_session['level']);
			$this->assign('admin_area_id', $this->system_session['area_id']);
			$this->display();
		}

		public function add(){
			if(IS_POST){
				
				if($_POST['currency'] == 1 || empty($_POST['province_idss'])){
					$_POST['province_id']	=	0;
					$_POST['city_id']	=	0;
				}else{
					$_POST['province_id']	=	$_POST['province_idss'];
					$_POST['city_id']	=	$_POST['city_idss'];
					unset($_POST['province_idss'],$_POST['city_idss']);
				}
				
//				if(strtotime($_POST['end_time'])<strtotime($_POST['start_time'])||strtotime($_POST['end_time'])<time()||strtotime($_POST['start_time'])<strtotime(date('Y-m-d'))){
//					$this->error('起始时间设置有误！');
//				}
//				if($_POST['discount']>$_POST['order_money']){
//					$this->error('优惠金额不能大于最小订单金额！');
//				}
				if($_POST['limit']>$_POST['num']){
					$this->error('领取限制不能大于数量！');
				}

				if($_POST['use_limit']>$_POST['num']){
					$this->error('使用限制设置错误，不能大于数量！');
				}
				if($_POST['use_limit']>$_POST['limit']&&!$_POST['allow_gift']){
					$this->error('使用限制设置错误，不能大于领取限制数量！');
				}
				if($_POST['cate_name']!='all'){
					if($_POST['cate_id']!=0){
						if($_POST['cate_name']=='meal') {
							$cate_id = D(ucfirst($_POST['cate_name']) . '_store_category')->field('cat_id,cat_name')->where(array('cat_id' => $_POST['cate_id']))->find();
						}else if($_POST['cate_name']=='mall'){
							$cate_id = D('Mall_category')->field('cat_id,cat_name')->where(array('cat_id' => $_POST['cate_id']))->order('`sort` DESC, `cat_id` ASC')->find();
						}else {
							$cate_id = D(ucfirst($_POST['cate_name']) . '_category')->field('cat_id,cat_name')->where(array('cat_id' => $_POST['cate_id']))->find();
						}
						$_POST['cate_id']=serialize($cate_id);
					}
				}else{
					$_POST['cate_id']=0;
				}
				if(empty($_POST['platform'])){
                    $this->error('请选择使用平台');
                }
				$data['platform']=serialize($_POST['platform']);
				unset($_POST['dosubmit']);
				unset($_POST['platform']);
				
				//设置了仅签到领取，其他失效(积分、新用户和随机派发)
				if($_POST['allow_sign']){
					$_POST['allow_gift'] && $_POST['allow_gift']=0;
					$_POST['allow_new'] && $_POST['allow_new']=0;
					$_POST['rand_send'] && $_POST['rand_send']=0;
				}

				$data = array_merge ($data,$_POST);
				$data['start_time']=strtotime($data['start_time']);
				$data['end_time']=strtotime($data['end_time']);
				if (!$data['start_time'] && !$data['start_time']) {
					$data['get_start_time']=$data['start_time'];
					$data['get_end_time']=$data['end_time'];
				}
				if($data['end_time']<=$data['start_time']){
					$this->error('起始时间设置有误');
				}
				
				// 领取起始时间设置
				$data['get_start_time']=strtotime($data['get_start_time']);
				$data['get_end_time']=strtotime($data['get_end_time']);
				if($data['get_start_time'] && $data['get_end_time'] && $data['get_end_time']<=$data['get_start_time']){
					$this->error('领取起始时间设置有误');
				}
				if($data['get_start_time']&&!$data['get_start_time']){
					$this->error('请设置领取结束时间');
				}
                if ($_POST['cate_name'] == 'shop' || $_POST['cate_name'] == 'all') {
                    $data['is_share_with_discount'] = $_POST['is_share_with_discount'];
                } else {
                    $data['is_share_with_discount'] = 1;
                }

				$data['allow_im_get'] = $_POST['allow_im_get'];
				$data['add_time']=$data['last_time']=time();
				$data['rand_send']=$_POST['rand_send']??0;
				$data['rand_send_num']=$_POST['rand_send_num']??0;
				$data['rand_send_start_time']=isset($data['rand_send_start_time'])?strtotime($data['rand_send_start_time']):0;
				$data['rand_send_end_time']=isset($data['rand_send_end_time'])?strtotime($data['rand_send_end_time']):0;
				if($_POST['rand_send'] && $data['rand_send_start_time']==$data['rand_send_end_time']){
					$this->error('随机派送开始结束时间不能相同');
				}

				$data['is_privileged'] = $_POST['is_privileged'];
				$data['is_vip_level'] = $_POST['is_vip_level'];//会员等级领取

                //平台、商家补贴、使用商家。使用类别仅外卖
                if ($data['cate_name'] == 'shop' ||$data['cate_name'] == 'mall') {
                    if ($data['plat_money'] < 0 || $data['merchant_money'] < 0) {
                        $this->error('商家补贴和平台补贴必须大于0');
                    }
                    if ($data['plat_money'] == 0 && $data['merchant_money'] == 0) {
                        $data['plat_money'] = $data['discount'];
                        $data['merchant_money'] = 0;
                    }
                    if ($data['is_discount'] == 1 && ($data['plat_money'] + $data['merchant_money'] != 1)) {
                        $this->error('折扣券商家补贴和平台补贴之和必须等于1');
                    }
                    if ($data['is_discount'] != 1 && $data['plat_money'] + $data['merchant_money'] != $data['discount']) {
                        $this->error('商家补贴与平台补贴之和必须等于优惠金额');
                    }
                } else if($data['cate_name'] == 'mall' || $data['cate_name'] == 'store' || $data['cate_name'] == 'cash'){
                	$data['use_type'] = intval($_POST['use_type']);
                } else {
                    $data['use_type'] = 0;
                    $data['plat_money'] = 0;
                    $data['merchant_money'] = 0;
                }
				if($_POST['sync_wx']){
					import('@.ORG.weixincard');
					import('ORG.Net.Http');
					$http = new Http();
					$mode = D('Access_token_expires');
					$res = $mode->get_access_token();
					$param['logo_url'] = $this->config['wechat_share_img'];
					$param['brand_name'] = mb_substr($this->config['site_name'], 0, 12,'utf-8');
					$param['title'] = mb_substr($_POST['name'],0,9,'utf-8');
					$param['color'] = $_POST['color'];
					$param['notice'] = mb_substr($_POST['notice'],0,16,'utf-8');
					$param['phone'] = $this->config['site_phone'];
					$param['description'] = $_POST['des'];
					$param['begin_time'] = $data['start_time'];
					$param['end_time'] = $data['end_time'];
					$param['num'] = $_POST['num'];
					$param['limit'] = $_POST['limit'];
					$param['center_title'] = '立即使用';
					$param['center_sub_title'] = mb_substr($_POST['center_sub_title'],0,6,'utf-8');
					$param['center_url'] = html_entity_decode($_POST['center_url']);
	                $param['center_app_brand_user_name'] = $this->config['pay_wxapp_username'].'@app';
	                $param['center_app_brand_pass'] = $_POST['center_url'] ? D('House_village')->home_url . '?redirect=webview&webview_url=' . urlencode(htmlspecialchars_decode($_POST['center_url'])) : D('House_village')->home_url;
					$param['custom_url_name'] = mb_substr($_POST['custom_url_name'],0,5,'utf-8');
					$param['custom_url'] = html_entity_decode($_POST['custom_url']);
	                $param['custom_app_brand_user_name'] = $this->config['pay_wxapp_username'].'@app';
	                $param['custom_app_brand_pass'] = $_POST['custom_url'] ? D('House_village')->home_url . '?redirect=webview&webview_url=' . urlencode(htmlspecialchars_decode($_POST['custom_url'])) : D('House_village')->home_url;
					$param['custom_url_sub_title'] = mb_substr($_POST['custom_url_sub_title'],0,6,'utf-8');
					$param['promotion_url'] = html_entity_decode($_POST['promotion_url']);
	                $param['promotion_app_brand_user_name'] = $this->config['pay_wxapp_username'].'@app';
	                $param['promotion_app_brand_pass'] = $_POST['promotion_url'] ? D('House_village')->home_url . '?redirect=webview&webview_url=' . urlencode(htmlspecialchars_decode($_POST['promotion_url'])) : D('House_village')->home_url;
					$param['promotion_url_name'] = '更多优惠';
					$param['icon_url_list'] = $_POST['icon_url_list']; //封面图片
					$param['abstract'] = $_POST['abstract']; //封面图片
	                if(!$this->config['pay_wxapp_username']){
	                    $this->error('同步微信卡券出错，小程序原始ID未设置');
	                }
					$param['share_friends'] = $_POST['share_friends'];

					$param['card_type'] = $_POST['is_discount']==1?'discount':'cash';
					$param['discount'] = 100 - $_POST['discount_value']*10;
					foreach ($_POST['image_url'] as $k => $v) {
						$text_image_list[] = array(
							'image_url' => $v,
							'text' => $_POST['text'][$k],
						);
					}
					$param['text_image_list'] = $text_image_list;
					$param['business_service'] = $_POST['business_service'];
					$param['least_cost'] = $_POST['order_money'] * 100;
					$param['reduce_cost'] = $_POST['discount'] * 100;
					$param['res'] = $res;
					$card = new Create_card($param);
					$cardinfo = $card->create();
					$ticket = $cardinfo['ticket'];
					$qrcode_url = $cardinfo['qrcode_url'];
					$return = $cardinfo['return'];
					if($return['errcode']){
						$this->error('同步微信卡券出错，请检查您配置的数据是否正确，微信返回信息：'.$return['errmsg']);
					}
				} 
				$data['coupon_url'] = isset($_POST['coupon_url']) ? htmlspecialchars_decode($_POST['coupon_url']) : '';

				if($id = D('System_coupon')->add($data)){
					D("System_log")->add_log("增加了平台优惠券，优惠券名称：".$_POST['name'],'平台优惠券',$id);
					if($_POST['sync_wx']) {
						$wx_data['sync_wx'] = $_POST['sync_wx'];
						unset($param['res']);
						$wx_data['wx_param'] = serialize($param);
						$errormsg = '';
						if ($return['errcode'] == 0) {
							$wx_data['wx_cardid'] = $return['card_id'];
							$wx_data['jsapi_ticket'] = $ticket['ticket'];
							$wx_data['expires_in'] = $ticket['expires_in'];
							$wx_data['wx_qrcode'] = $qrcode_url['show_qrcode_url'];
							$wx_data['wx_ticket_addtime'] = $_SERVER['REQUEST_TIME'];
							$wx_data['is_wx_card'] = 1;
							$wx_data['cardsign'] = sha1($wx_data['wx_ticket_addtime'] . $ticket['ticket'] . $return['card_id']);

							M('System_coupon')->where(array('coupon_id' => $id))->save($wx_data);
							$errormsg = $return['errmsg'];

						} else {
							$wx_data['is_wx_card'] = 0;
							$errormsg = $return['errmsg'];
							$wx_data['weixin_err'] = serialize($errormsg);
							M('System_coupon')->where(array('coupon_id' => $id))->save($wx_data);

						}
					}
					// 多语言
                    $lang_model = D('Lang');
                    $data['coupon_id'] = $id;
                    $lang_model->add_lang_data('System_coupon',$data);
					$this->success('添加优惠券成功！'.$errormsg);
				}else{
					$this->error('添加失败！');
				}
			}else {
				$return =  D('System_coupon')->cate_platform();
				$color_list =  D('System_coupon')->color_list();
				$this->assign("color_list",$color_list);
				$this->assign("category",$return['category']);
				$this->assign("platform",$return['platform']);
				$this->display();
			}
		}

		public function edit(){
			if(IS_POST){
				if($_POST['currency'] == 1 || empty($_POST['province_idss'])){
					$_POST['province_id']	=	0;
					$_POST['city_id']	=	0;
				}else{
					$_POST['province_id']	=	$_POST['province_idss'];
					$_POST['city_id']	=	$_POST['city_idss'];
					unset($_POST['province_idss'],$_POST['city_idss']);
				}
				
				$add = pow(-1,(int)$_POST['add']);
				$_POST['num']+=$add*(int)$_POST['num_add'];//数量增减
				if((int)$_POST['num']<(int)$_POST['had_pull']){
					$this->error('更新优惠券数量有误，不能小于已领取的数量！');
				}
				$now_coupon = D('System_coupon')->where(array('coupon_id'=>$_POST['coupon_id']))->find();

				if ((int)$_POST['num'] > (int)$_POST['had_pull'] && (int)$_POST['status'] == 3) {
					if ($now_coupon['end_time'] > time()) {
						$_POST['status'] = 1;
					}
				}

				if ((int)$_POST['num'] <= (int)$_POST['had_pull']) {
					$_POST['status'] = 3;
				}

				unset($_POST['dosubmit']);
				$data = $_POST;
                $data['is_share_with_discount'] = $_POST['is_share_with_discount'];
                $data['allow_im_get'] = $_POST['allow_im_get'];
				$data['rand_send']=$_POST['rand_send']??0;
				$data['rand_send_num']=$_POST['rand_send_num']??0;
				$data['rand_send_start_time']=isset($data['rand_send_start_time'])?strtotime($data['rand_send_start_time']):0;
				$data['rand_send_end_time']=isset($data['rand_send_end_time'])?strtotime($data['rand_send_end_time']):0;

				$data['last_time']=time();
				$before = D('System_coupon')->where(array('coupon_id'=>$_POST['coupon_id']))->find();
				
                //平台、商家补贴、使用商家。使用类别仅外卖
                if ($before['cate_name'] == 'shop' || $before['cate_name'] == 'mall') {
                    if ($data['plat_money'] < 0 || $data['merchant_money'] < 0) {
                        $this->error('商家补贴和平台补贴必须大于0');
                    }
                    if ($data['plat_money'] == 0 && $data['merchant_money'] == 0) {
                        $data['plat_money'] = $before['discount'];
                        $data['merchant_money'] = 0;
                    }
                    if ($before['is_discount'] == 1 && ($data['plat_money'] + $data['merchant_money'] != 1)) {
                        $this->error('折扣券商家补贴和平台补贴之和必须等于1');
                    }
                    if ($before['is_discount'] != 1 && $data['plat_money'] + $data['merchant_money'] != $before['discount']) {
                        $this->error('商家补贴与平台补贴之和必须等于优惠金额');
                    }
                    if ($before['use_type'] == 1 && $data['use_type'] == 0) {
                        M('System_coupon_store')->where(['coupon_id' => $before['coupon_id']])->delete();
                    }
                } else if($before['cate_name'] == 'mall' || $before['cate_name'] == 'store' || $before['cate_name'] == 'cash'){
                	$data['use_type'] = intval($_POST['use_type']);
                } else {
					$data['use_type'] = 0;
                    $data['plat_money'] = 0;
                    $data['merchant_money'] = 0;
                }

				$data['coupon_url'] = isset($_POST['coupon_url']) ? htmlspecialchars_decode($_POST['coupon_url']) : '';
				if(D('System_coupon')->where(array('coupon_id'=>$_POST['coupon_id']))->save($data)){
					$after = D('System_coupon')->where(array('coupon_id'=>$_POST['coupon_id']))->find();
					D("System_log")->add_log("编辑了优惠券信息，优惠券ID：".$_POST['coupon_id'],'平台优惠券',$_POST['coupon_id'], $before, $after);
					$num_add = $add>0?$_POST['num_add']:0;
					$num_less= $add<0?$_POST['num_add']:0;
					$errorms = D('System_coupon')->decrease_sku($num_add,$num_less,$_POST['coupon_id']);

					if($_POST['num_add']>0){
						$date_log['type'] = 0;
						$date_log['add'] = $add>0?1:0;
						$date_log['add_time'] = time();
						$date_log['num'] = $_POST['num_add'];
						$date_log['coupon_id'] = $_POST['coupon_id'];
						M('Coupon_add_log')->add($date_log);
					}

                    // 多语言
                    $lang_model = D('Lang');
                    $data['coupon_id']=$_POST['coupon_id'];
                    fdump($data,'$data');
                    $lang_model->edit_lang_data('System_coupon',$data);
					$this->success('保存成功！'.$errorms);
				}else{
					$this->error('保存失败！');
				}
			}else {
				$return =  D('System_coupon')->cate_platform(); //模板中定义相关中文名字
				$this->assign("category",$return['category']);
				$coupon = D('System_coupon')->where(array('coupon_id'=>$_GET['coupon_id']))->find();
				$coupon['now_num'] = $coupon['num'];
				$coupon['platform'] = unserialize($coupon['platform']);
				$wx_param = unserialize($coupon['wx_param']);
				if($wx_param){
					unset($wx_param['discount']);
					$coupon = array_merge($coupon,$wx_param);
				}

				foreach($coupon['platform'] as &$vv){
					$vv = $return['platform'][$vv];
				}
				$coupon['platform'] = implode(',',$coupon['platform']);
				$coupon['cate_name_origin'] = $coupon['cate_name'];
				$coupon['cate_name'] = $coupon['cate_name']=='all'?'全部类别':$return['category'][$coupon['cate_name']];
				if(empty($coupon['cate_id'])) {
					$coupon['cate_id'] = '全部分类';
				}else{
					$coupon['cate_id'] = unserialize($coupon['cate_id']);
					$coupon['cate_id'] = $coupon['cate_id']['cat_name'];
				}
				$color_list =  D('System_coupon')->color_list();
				$coupon['color'] = $color_list[$coupon['color']];
				$this->assign("coupon",$coupon);
				//echo $coupon['cate_name_origin'];exit;
				$this->display();
			}
		}
		
		public function ajax_ordertype_cateid(){

			if($_POST['order_type']=='meal'){
				$cate_id = D(ucfirst($_POST['order_type']) . '_store_category')->field('cat_id,cat_name')->where(array('cat_status' => 1,'cat_fid'=>0))->select();
			} else if($_POST['order_type']=='village_group'){
				$cate_id = array();
			}
			else {
				$cate_id = D(ucfirst($_POST['order_type']) . '_category')->field('cat_id,cat_name')->where(array('cat_status' => 1,'cat_fid'=>0,'is_del' => 0))->select();
			}
			echo json_encode($cate_id);
		}
		
		public  function  had_pull(){
			$order_string = 'h.receive_time DESC ,h.id DESC';
			$where['h.uid']=array('neq','0');
			if(!empty($_GET['keyword'])){
				if ($_GET['searchtype'] == 'name') {
					$where['c.name'] =  array('like', "%".$_GET['keyword']."%");
				} elseif ($_GET['searchtype'] == 'phone') {
					$where['u.phone'] = $_GET['keyword'];
				}
			}
			$coupon = M('System_coupon_hadpull');
			$count_count = $coupon->join('as h left join '.C('DB_PREFIX').'system_coupon c ON h.coupon_id = c.coupon_id')->join(C('DB_PREFIX').'user u ON h.uid = u.uid')->where($where)->count();
			import('@.ORG.system_page');
			$p = new Page($count_count, 15);
			$coupon_list = $coupon->join('as h left join '.C('DB_PREFIX').'system_coupon c ON h.coupon_id = c.coupon_id')->join(C('DB_PREFIX').'user u ON h.uid = u.uid')->field('h.id,c.name,c.is_discount,c.order_money,c.discount,c.discount_value,u.uid,u.nickname,h.num,h.receive_time,h.is_use,h.phone,c.num as total_num')->where($where)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();

			$this->assign('coupon_list',$coupon_list);
			$pagebar = $p->show();
			$this->assign('pagebar', $pagebar);

			$this->display();
		}

		public function del(){
			if(IS_POST){
				if(!empty($_POST['coupon_id'])){
					if(D('System_coupon')->where(array('coupon_id'=>$_POST['coupon_id']))->setField('delete',1)){
						$this->success('删除成功');
					}else{
						$this->error('删除失败！');
					}
				}

			}
		}

	public function show(){
		$this->display();
	}

	public function see_qrcode(){
		$coupon = M('System_coupon')->where(array('coupon_id'=>$_GET['id']))->find();
		echo '<html><head><style>*{margin:0;padding:0;}</style></head><body><img src="'.$coupon['wx_qrcode'].'"/></body></html>';
	}


	//根据用户等级获得用户列表
	public function get_user_list_by_level(){
		$level = $_GET['level'];
		$user_list = M('User')->field('uid,phone,nickname')->where(array('level'=>$level))->select();
		$this->assign('user_list',$user_list);
		$this->display();
	}

	//派发优惠券
	public function send_coupon(){

		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				die('区域管理员无法管理优惠券');
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$condition_coupon[$area_index] = $this->system_session['area_id'];
		}
		$coupon_list = D('System_coupon')->get_coupon_list($condition_coupon);
		if(empty($coupon_list)){
			$this->error('没有可分配的优惠券!');
		}
		$return =  D('System_coupon')->cate_platform();
		$coupon_list = array_map(function($v) use ($return){
			$v['cate_name_txt'] = $v['cate_name'] == 'all'?'全部类别':$return['category'][$v['cate_name']];
			return $v;
		},$coupon_list);
		$this->assign('coupon_list',$coupon_list);
		$User_level_list = M(_view('User_level'))->select();
		//foreach ($card_group_list as &$v) {
		//	$v['user_count'] = M('Card_userlist')->where(array('mer_id'=>$this->merchant_session['mer_id'],'gid'=>$v['id']))->count();
		//}
		$this->assign('User_level_list',$User_level_list);
		$this->display();
	}

	//派发标签优惠券
	public function send_label_coupon(){
		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				die('区域管理员无法管理优惠券');
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$condition_coupon[$area_index] = $this->system_session['area_id'];
		}
		$coupon_list = D('System_coupon')->get_coupon_list($condition_coupon);
		if(empty($coupon_list)){
			$this->error('没有可分配的优惠券!');
		}
		$return =  D('System_coupon')->cate_platform();
        $coupon_list = array_map(function($v) use ($return){
            $v['cate_name_txt'] = $v['cate_name'] == 'all'?'全部类别':$return['category'][$v['cate_name']];
            return $v;
        },$coupon_list);
		$this->assign('coupon_list',$coupon_list);
		/*用户标签列表*/
		$user_label_list = M('User_label')->where(array('is_del'=>0))->select();
		$this->assign('user_label_list',$user_label_list);
		$this->display();	
	}

	//派发所有
	public function send_all(){
		if(IS_POST) {
			$res = D('Card_new')->add_send_log('', '', $_POST['coupon_id']);
			// $this->assign('user_list',$res);
			if ($res['error_code']) {
				$this->error($res['msg']);
			} else {
				$this->template_plan_msg($res['id']);
				$this->success($res['msg']);
			}
			die;
		}else{
			$where['status'] = 1;
			$user_count = M('User')->where($where)->count();
			$condition['num']=array('egt',$user_count);
			if ($this->system_session['area_id']) {
				$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
				if($now_area['area_type']==3){
					die('区域管理员无法管理优惠券');
				}elseif($now_area['area_type']==2){
					$area_index = 'city_id';
				}elseif($now_area['area_type']==1){
					$area_index = 'province_id';
				}
				$condition[$area_index] = $this->system_session['area_id'];
			}
			$coupon_list = D('System_coupon')->get_coupon_list($condition);
			if(empty($coupon_list)){
				$this->error('没有可分配的优惠券！优惠券剩余数量要大于平台会员总数');
			}
			$return =  D('System_coupon')->cate_platform();
			$coupon_list = array_map(function($v) use ($return){
				$v['cate_name_txt'] = $v['cate_name'] == 'all'?'全部类别':$return['category'][$v['cate_name']];
				return $v;
			},$coupon_list);
			$this->assign('coupon_list',$coupon_list);
			$this->display();
		}

	}

	// 添加临时计划任务
	public function template_plan_msg($send_id){
		import('@.ORG.plan');
		$plan_class = new plan();
		$param = array(
				'file'=>'send_coupon',
				'plan_time'=>time(),
				'param'=>array(
						'id'=>$send_id,
				),
		);
		$plan_class->addTask($param);
	}

	public function send_person(){

		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				die('区域管理员无法管理优惠券');
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$condition_coupon[$area_index] = $this->system_session['area_id'];
		}
		$coupon_list = D('System_coupon')->get_coupon_list($condition_coupon);
		if(empty($coupon_list)){
			$this->error('没有可分配的优惠券!');
		}
		$return =  D('System_coupon')->cate_platform();
		$coupon_list = array_map(function($v) use ($return){
			$v['cate_name_txt'] = $v['cate_name'] == 'all'?'全部类别':$return['category'][$v['cate_name']];
			return $v;
		},$coupon_list);
		$this->assign('coupon_list',$coupon_list);
		$User_level_list = M(_view('User_level'))->select();
		//foreach ($card_group_list as &$v) {
		//	$v['user_count'] = M('Card_userlist')->where(array('mer_id'=>$this->merchant_session['mer_id'],'gid'=>$v['id']))->count();
		//}
		$this->assign('User_level_list',$User_level_list);
		$this->display();
	}

	public function send_city(){
		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				die('区域管理员无法管理优惠券');
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$condition_coupon[$area_index] = $this->system_session['area_id'];
		}
		$coupon_list = D('System_coupon')->get_coupon_list($condition_coupon);
		$citys = array_filter(array_column($coupon_list, 'city_id'));
		$relation = [];
		if($citys){
			$citys_info = D("Area")->where(['area_id'=>['in',$citys]])->field("area_name,area_id")->select();
			$relation = array_column($citys_info, 'area_name', 'area_id');
		}
		foreach ($coupon_list as $key => $value) {
			$coupon_list[$key]['city_name'] = isset($relation[$value['city_id']]) ? $relation[$value['city_id']] : "通用";
		}

		if(empty($coupon_list)){
			$this->error('没有可分配的优惠券!');
		}
		$return =  D('System_coupon')->cate_platform();
		$coupon_list = array_map(function($v) use ($return){
			$v['cate_name_txt'] = $v['cate_name'] == 'all'?'全部类别':$return['category'][$v['cate_name']];
			return $v;
		},$coupon_list);
		$this->assign('coupon_list',$coupon_list);
		$this->display();
	}	

	public function send_multiple(){
		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				die('区域管理员无法管理优惠券');
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$condition_coupon[$area_index] = $this->system_session['area_id'];
		}
		$coupon_list = D('System_coupon')->get_coupon_list($condition_coupon);
		if(empty($coupon_list)){
			$this->error('没有可分配的优惠券!');
		}
		$return =  D('System_coupon')->cate_platform();
		$coupon_list = array_map(function($v) use ($return){
			$v['cate_name_txt'] = $v['cate_name'] == 'all'?'全部类别':$return['category'][$v['cate_name']];
			return $v;
		},$coupon_list);

		$this->assign('coupon_list',$coupon_list);
		$this->display();
	}

	//派发记录
	public function send_history(){

		import('@.ORG.system_page');
		$count = M('System_coupon_send_history')->count();
		$p = new Page($count, 15);
		$res = M('System_coupon_send_history')->join('as h left join '.C('DB_PREFIX').'user u ON h.uid = u.uid')
				->join(C('DB_PREFIX').'system_coupon c ON h.coupon_id = c.coupon_id')->field('h.*,u.nickname,u.phone,c.name as coupon_name')->order('add_time DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);
		$this->assign('history',$res);

		$this->display();
	}

	public function ajax_send(){
		$coupon_list = explode(',',$_POST['coupon_list']);
		$uid = $_POST['uid'];
		$model = D('System_coupon');
		foreach ($coupon_list as $item) {
			$tmp = $model->had_pull($item,$uid,'',0,1);
			$model->decrease_sku(0,1,$item);//网页领取完，微信卡券库存需要同步减少
			$tmp['msg'] = $this->coupon_error($tmp['error_code']);
			$data['uid'] = $uid;
			$data['coupon_id'] = $item;
			$data['error_code']  =$tmp['error_code'];
			$data['msg']  =$tmp['msg'];
			$data['add_time']  =time();
			M('System_coupon_send_history')->add($data);
			$return[$tmp['coupon']['coupon_id']]['error_msg'] = $tmp['msg'];
			$return[$tmp['coupon']['coupon_id']]['coupon_name'] = $tmp['coupon']['name'];
			$return[$tmp['coupon']['coupon_id']]['send_code'] = $tmp['error_code'];
		}
		$this->ajaxReturn(array('error_code'=>0,'return'=>$return));
	}

	public function ajax_send_new(){
		$where=array();
		if($_POST['lastuid']){
			$where['uid'] = array('gt' , $_POST['lastuid']);
		}
		$where['status']=1;
		if($_POST['type'] == 1){
			$bind_list = D('User_label_bind')->where(array('label_id'=>array('in',$_POST['condition'])))->select();
			if ($bind_list) {
				$uid_arr = array();
				foreach ($bind_list as $key => $value) {
					$uid_arr[] =  $value['uid'];
				}
				$where['_string'] = '(uid in ('.implode(',',array_unique($uid_arr)).') OR label_id in ('.$_POST['condition'].'))';
			}else{
				$where['label_id']=array('in',$_POST['condition']);
			}

		}else if($_POST['type'] == 2){
			$where['level']=array('in',$_POST['condition']);
		}else if($_POST['type'] == 3){
			$area = explode('_', $_POST['condition']);
			$where['province_id'] = array('eq',$area[0]);
			$where['city_id'] = array('eq',$area[1]);
		}else if($_POST['type'] == 4){
			$_POST['condition'] = urldecode($_POST['condition']);
			$_POST['condition'] = str_replace('，', ',', $_POST['condition']);
			$_POST['condition'] = str_replace("\n", ',', $_POST['condition']);
			$telphones = explode(',', $_POST['condition']);
			$telphones = array_filter(array_map('trim', $telphones));
			$where['phone'] = array('in',$telphones);

		}else if($_POST['type'] == 5){
			$_POST['condition'] = urldecode($_POST['condition']);
			$_POST['condition'] = str_replace('，', ',', $_POST['condition']);
			$_POST['condition'] = str_replace("\n", ',', $_POST['condition']);
			$uids = explode(',', $_POST['condition']);
			$uids = array_filter(array_map('trim', $uids));
			if(isset($_POST['uids_type']) && $_POST['uids_type'] == 'phone'){
				$where['_string'] = 'phone in ('.implode(',',$uids).')';
			}else{
				$where['_string'] = 'uid in ('.implode(',',$uids).')';
			}
		}else if($_POST['type'] == 6){

		}else if($_POST['type'] == 7){
			$where['_string'] = 'uid='.$_POST['condition'];
		}

		$list=M('user')->where($where)->field('uid,nickname,phone')->order('uid asc')->find();

		if(empty($list)){
			$this->ajaxReturn(array('status'=>2,'msg'=>'优惠券派发结束'));
			exit;
		}

		$coupon_list = explode(',',$_POST['coupon_list']);
		$send_num = explode(',',$_POST['send_num']);
		$uid = $list['uid'];
		$model = D('System_coupon');
		$return = array();
		foreach ($coupon_list as $key=>$item) {
			$tmp = $model->had_pull($item,$uid,'',0,1,$send_num[$key]??1);
			$model->decrease_sku(0,1,$item);//网页领取完，微信卡券库存需要同步减少
			$tmp['msg'] = $this->coupon_error($tmp['error_code']);
			$data['uid'] = $uid;
			$data['coupon_id'] = $item;
			$data['error_code']  =$tmp['error_code'];
			$data['msg']  =$tmp['msg'];
			$data['add_time']  =time();
			M('System_coupon_send_history')->add($data);
			$temp = array(
				'error_msg' => $tmp['msg'],
				'coupon_name' => $tmp['coupon']['name'],
				'send_code' => $tmp['error_code']
			);
			$return[] = $temp;
		}

		$list['account'] = $list['nickname'] ? $list['nickname'] : $list['phone'];
		$this->ajaxReturn(array('status'=>1, 'uinfo' => $list, 'data'=> $return));
	}

	public function coupon_error($error_code){
		switch($error_code) {
			case '0':
				$error_msg = '领取成功';
				break;
			case '1':
				$error_msg = '领取发生错误';
				break;
			case '2':
				$error_msg = '优惠券已过期';
				break;
			case '3':
				$error_msg = '优惠券已经领完了';
				break;
			case '4':
				$error_msg = '只允许新用户领取';
				break;
			case '5':
				$error_msg = '不能再领取了';
				break;
			case '6':
				$error_msg = '当前用户与当前优惠券不在同一城市';
				break;
		}
		return $error_msg;
	}

	public function send(){
		if($_GET['label_str']){
			$where=array();
			$where['u.label_id']=array('in',$_GET['label_str']);
			$where['u.status']=1;
			$list=M('user')->alias('u')->join('left join '.C('DB_PREFIX').'user_label lab on u.label_id=lab.id')->where($where)->field('lab.id,u.uid,u.nickname,u.phone,lab.name')->select();
			$user_list=array();
			foreach($list as $k=>$v){
				$user_list[$v['id']]['label_name']=$v['name'];
				$user_list[$v['id']]['userlist'][]=$v;
			}
			$this->assign('user_list',$user_list);
			$this->assign('type',2);
		}else if($_GET['level']){
			$where['u.level']=array('in',$_GET['level']);
			//$where['u.uid'] = array('neq',0);
			$where['u.status'] = 1;
			$res = M('User')->join(' u left join '.C('DB_PREFIX').'user_level l on u.level = l.level')->where($where)->field('l.id,u.uid,u.nickname,u.phone,l.level,l.lname as group_name')->select();
			foreach ($res as $re) {
				$tmp[$re['id']]['group_name'] = $re['group_name'];
				$tmp[$re['id']]['userlist'][] = $re;
			}
			$this->assign('user_list',$tmp);
		}else if($_GET['city_id'] && $_GET['province_id']){
			$where['province_id'] = array('eq',$_GET['province_id']);
			$where['city_id'] = array('eq',$_GET['city_id']);
			$where['status'] = 1;
			$res = M('User')->where($where)->field('uid,nickname,phone,province_id,city_id')->select();
			$city_ids = array_merge(array_column($res, 'province_id'), array_column($res, 'city_id'));
			$city_info = D("Area")->where(['area_id'=>['in', $city_ids]])->field("area_id,area_name")->select();
			$city_column = array_column($city_info, 'area_name', 'area_id');
			foreach ($res as $key => $re) {
				$res[$key]['city_name'] = $city_column[$re['city_id']];
			}
			$this->assign('type',3);
			$this->assign('user_list',$res);
		}else if($_GET['telphones']){
			$_GET['telphones'] = urldecode($_GET['telphones']);
			$_GET['telphones'] = str_replace('，', ',', $_GET['telphones']);
			$_GET['telphones'] = str_replace("\n", ',', $_GET['telphones']);
			$telphones = explode(',', $_GET['telphones']);
			$telphones = array_filter(array_map('trim', $telphones));
			$where['phone'] = array('in',$telphones);
			$where['status'] = 1;
			$user_list = M('User')->where($where)->field('uid,nickname,phone')->select();
			$this->assign('type',4);
			$this->assign('user_list',$user_list);

		}else if($_GET['uids']){
			$_GET['uids'] = urldecode($_GET['uids']);
			$_GET['uids'] = str_replace('，', ',', $_GET['uids']);
			$_GET['uids'] = str_replace("\n", ',', $_GET['uids']);
			$uids = explode(',', $_GET['uids']);
			$uids = array_filter(array_map('trim', $uids));
			$where['uid'] = array('in',$uids);
			$where['status'] = 1;
			$user_list = M('User')->where($where)->field('uid,nickname,phone')->select();
			$this->assign('type',4);
			$this->assign('user_list',$user_list);

		}else if($_GET['all']){
			$where['status'] = 1;
			$user_list = M('User')->where($where)->field('uid,nickname,phone')->select();
			$this->assign('user_list',$user_list);

		}else if($_GET['uid']){
			$user = M('User')->where(array('uid'=>$_GET['uid']))->field('uid,nickname,phone')->find();
			$this->assign('user',$user);
		}

		$this->assign('coupon_list',$_GET['coupon_id']);
		$this->display();
	}

	//新写的派发优惠券逻辑
	public function send_new(){
		$condition = null;
		$type = null;
		if($_GET['label_str']){
			$condition = $_GET['label_str'];
			$type = 1;
		}else if($_GET['level']){
			$condition = $_GET['level'];
			$type = 2;
		}else if($_GET['city_id'] && $_GET['province_id']){
			$condition = $_GET['province_id']."_".$_GET['city_id'];
			$type = 3;
		}else if($_GET['telphones']){
			$condition = urlencode($_GET['telphones']);
			$type = 4;
		}else if($_GET['uids']){
			$condition = urlencode($_GET['uids']);
			$type = 5;
		}else if($_GET['all']){
			$condition = '';
			$type = 6;
		}else if($_GET['uid']){
			$condition = $_GET['uid'];
			$type = 7;
		}

		$this->assign("condition", $condition);
		$this->assign("type", $type);
		$this->assign('coupon_list',$_GET['coupon_id']);
		$this->assign('uids_type',$_GET['uids_type']??'');
		$this->assign('send_num',$_GET['send_num']??'');
		$this->display();
	}

	//获取可以派发的优惠券列表
	public function ajax_get_send_coupon(){
		if(!empty($_POST['level'])){
			$count = M('User')->where(array('level'=>array('in',$_POST['level'])))->count();
			$coupon_list = D('System_coupon')->get_coupon_list();
			foreach ($coupon_list as &$v) {
				if($count>$v['num']-$v['had_pull']){
					$v['disable'] = true;
				}else{
					$v['disable'] = false;
				}
			}
		}else{
			$coupon_list = D('System_coupon')->get_coupon_list();
		}
		$this->ajaxReturn($coupon_list);
	}

	public function ajax_get_user(){
		if($_POST['keyword'] == 'uid'){
			$where['uid'] = $_POST['search_val'];
		}
		else{
			$where[$_POST['keyword']] = array('like',"%".$_POST['search_val']."%");
		}
		$res = M('User')->field('uid,nickname,phone')->where($where)->select();
		$this->ajaxReturn($res);
	}

	#微信购买派发设置
	public function weixin_send(){

		$coupon_list = D('System_coupon')->get_coupon_list();
		if(empty($coupon_list)){
			//$this->ajaxReturn(array('error_code'=>1,'msg'=>'没有可分配的优惠券!'));
			$this->error_tips('没有可分配的优惠券!');
		}
		$return =  D('System_coupon')->cate_platform();
		$coupon_list = array_map(function($v) use ($return){
			$v['cate_name_txt'] = $v['cate_name'] == 'all'?'全部类别':$return['category'][$v['cate_name']];
			return $v;
		},$coupon_list);
		$this->assign('coupon_list',$coupon_list);
		//['weixin_send_couponlist'] = explode(',',$now_card['weixin_send_couponlist']);
		$this->assign('coupon_list',$coupon_list);
		//$this->assign('now_card',$now_card);
		if(IS_POST){
			if(empty($_POST['coupon_id'])){
				$this->error_tips('没有勾选优惠券！');	
			}
			if(!is_numeric($_POST['money'])||$_POST['money']<0){
				$this->error_tips('金额设置错误!');
			}

			M('Config')->where(array('name'=>'weixin_send_money'))->setField('value',$_POST['money']);
			M('Config')->where(array('name'=>'weixin_send_coupon_list'))->setField('value', implode(',',$_POST['coupon_id']));
			$this->frame_main_ok_tips('保存成功');
		}else{
			$this->display();
		}
	}


	public function recharge_list(){
		$mer_id  =$this->merchant_session['mer_id'];
		import('@.ORG.merchant_page');
		$where['u.mer_id'] = $mer_id;
		$count = M(_view('Card_new_record'))->join('as c left join '.C('DB_PREFIX').'card_userlist as u ON c.card_id = u.id')->where($where)->count();
		$p = new Page($count,20);
		$result =  M(_view('Card_new_record'))->join('as c left join '.C('DB_PREFIX').'card_userlist as u ON c.card_id = u.id')->where($where)->order('c.time DESC')->limit($p->firstRow,$p->listRows)->select();
		$pagebar=$p->show();
		$this->assign('record',$result);
		$this->assign('pagebar',$pagebar);
		$this->display();
	}

	public function share_coupon(){
		$this->display();
	}

	public  function delete_coupon(){
		$coupon_id = $_POST['coupon_id'];
		$coupon = M('System_coupon')->where(array('coupon_id'=>$coupon_id))->find();
		if($coupon['wx_cardid']!=''){

			import('@.ORG.plan');
			$plan_class = new plan();
			$param = array(
					'file'=>'delete_wx_coupon',
					'plan_time'=>time(),
					'param'=>array(
							'id'=>$coupon_id,
							'type'=>0,//
					),
			);
			$plan_class->addTask($param);
		}
		M('System_coupon')->where(array('coupon_id'=>$coupon_id))->setField('status',4);
		$this->success('删除成功');
	}

	// 选择优惠券
	public  function select_coupon(){
		if (!empty($_POST['search'])) {
            $condition['_string'] = "name like '%".$_POST['search']."%'";
        }
        $condition['allow_gift'] = 1;
        $condition['status'] = 1;
        $condition['delete'] = 0;
        $condition['end_time'] = array('gt',time());
		
		$coupon_list = D('System_coupon')->get_coupon_list($condition,true);
		$return =  D('System_coupon')->cate_platform(); //模板中定义相关中文名字

		
		if($wx_param){
			unset($wx_param['discount']);
			$coupon = array_merge($coupon,$wx_param);
		}

		
		foreach ($coupon_list as &$value) {
			$value['platform'] = unserialize($value['platform']);
			$value['wx_param']= unserialize($value['wx_param']);
			foreach($value['platform'] as &$vv){
				$vv = $return['platform'][$vv];
			}
			$value['platform'] = implode(',',$value['platform']);

			$value['cate_name'] = $value['cate_name']=='all'?'全部类别':$return['category'][$value['cate_name']];
			if(empty($value['cate_id'])) {
				$value['cate_id'] = '全部分类';
			}else{
				$value['cate_id'] = unserialize($value['cate_id']);
				$value['cate_id'] = $value['cate_id']['cat_name'];
			}

			$value['show_name'] = $value['name'].' -- '.date('Y年m月d日',$value['start_time']).'到'.date('Y年m月d日',$value['end_time']).' -- '.'满'.$value['order_money'].'减'.$value['discount'].C('config.Currency_txt').' -- '.$value['platform'].' '.$value['cate_name'].' '.$value['cate_id'].' '.$value['num'];
		}

		$this->assign('list',$coupon_list);
		$this->display();
	}

	//选择只可签到使用优惠券
	public  function select_sign_coupon(){
		if (!empty($_POST['search'])) {
            $condition['_string'] = "name like '%".$_POST['search']."%'";
        }
        $condition['allow_sign'] = 1;
        $condition['status'] = 1;
        $condition['delete'] = 0;
        $condition['end_time'] = array('gt',time());
		
		$coupon_list = D('System_coupon')->get_coupon_list($condition,false,true);

		$return =  D('System_coupon')->cate_platform(); //模板中定义相关中文名字
		if($wx_param){
			unset($wx_param['discount']);
			$coupon = array_merge($coupon,$wx_param);
		}
		foreach ($coupon_list as &$value) {
			$value['platform'] = unserialize($value['platform']);
			$value['wx_param']= unserialize($value['wx_param']);
			foreach($value['platform'] as &$vv){
				$vv = $return['platform'][$vv];
			}
			$value['platform'] = implode(',',$value['platform']);

			$value['cate_name'] = $value['cate_name']=='all'?'全部类别':$return['category'][$value['cate_name']];
			if(empty($value['cate_id'])) {
				$value['cate_id'] = '全部分类';
			}else{
				$value['cate_id'] = unserialize($value['cate_id']);
				$value['cate_id'] = $value['cate_id']['cat_name'];
			}

			if($value['is_discount']){
				$value['show_name'] = $value['name'].' -- '.date('Y年m月d日',$value['start_time']).'到'.date('Y年m月d日',$value['end_time']).' -- '.'满'.$value['order_money'].'打'.floatval($value['discount_value']).'折'.' -- '.$value['platform'].' '.$value['cate_name'].' '.$value['cate_id'].' '.$value['num'];
			}else{
				$value['show_name'] = $value['name'].' -- '.date('Y年m月d日',$value['start_time']).'到'.date('Y年m月d日',$value['end_time']).' -- '.'满'.$value['order_money'].'减'.$value['discount'].C('config.Currency_txt').' -- '.$value['platform'].' '.$value['cate_name'].' '.$value['cate_id'].' '.$value['num'];	
			}
		}
		$this->assign('list',$coupon_list);
		$this->display('select_coupon');
	}

	public function coupon_add_log(){
		$count = M('Coupon_add_log')->where(array('coupon_id'=>$_GET['coupon_id'],'type'=>0))->count();
		import('@.ORG.system_page');
		$p = new Page($count, 15);
		$res = M('Coupon_add_log')->where(array('coupon_id'=>$_GET['coupon_id']))->limit($p->firstRow,$p->listRows)->select();
		$this->assign('list',$res);
		$this->assign('pagebar',$p->show());
		$this->display();
	}

    /**
     * 店铺列表
     */
    public function store_list()
    {
        $coupon_id = isset($_GET['coupon_id']) ? intval($_GET['coupon_id']) : 0;
        if ($coupon = D('System_coupon')->where(array('coupon_id' => $coupon_id))->find()) {
            if ($coupon['use_type'] == 0) {
                $this->error_tips('此优惠券无需指定店铺');
            }
            $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
            $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
            $merchant_where = ['m.status' => 1, 's.status' => 1];

            if ($keyword) {
                if ($_GET['searchtype'] == 'mer_id') {
                    $merchant_where['m.mer_id'] = $keyword;
                } elseif ($_GET['searchtype'] == 'account') {
                    $merchant_where['m.account'] = ['LIKE', '%' . $keyword . '%'];
                } elseif ($_GET['searchtype'] == 'name') {
                    $merchant_where['m.name'] = ['LIKE', '%' . $keyword . '%'];
                } elseif ($_GET['searchtype'] == 'phone') {
                    $merchant_where['m.phone'] = ['LIKE', '%' . $keyword . '%'];
                } elseif ($_GET['searchtype'] == 'store_name') {
                    $merchant_where['s.name'] = ['LIKE', '%' . $keyword . '%'];
                } elseif ($_GET['searchtype'] == 'store_id') {
                    $merchant_where['s.store_id'] = $keyword;
                }
            }
            $store_ids = D('System_coupon_store')->where(array('coupon_id' => $coupon_id))->getField('store_id', true);
            if ($store_ids) {
                if ($status == 1) {
                    $merchant_where['s.store_id'] = ['IN', $store_ids];
                } else if($status){
                    $merchant_where['s.store_id'] = ['NOT IN', $store_ids];
                }
            }

            $merchant_store_mond = D('Merchant_store');
            $join = ' LEFT JOIN ' . C('DB_PREFIX') . 'merchant AS m on s.mer_id=m.mer_id';
            $count = $merchant_store_mond->alias('s')->join($join)->where($merchant_where)->count();
            $count = intval($count);

            import('@.ORG.system_page');
            $p = new Page($count, 15);
            $storeList = $merchant_store_mond->field('s.*,m.name as merchant_name, "' . $coupon_id . '" AS did')->alias('s')->join($join)->where($merchant_where)->order('m.mer_id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
            //dump($store_ids);exit;
            //查找有没有指定店铺
            if ($storeList) {
                foreach ($storeList as $key => $value) {
                    $storeList[$key]['is_bind'] = $store_ids && in_array($value['store_id'], $store_ids) ? 1 : 0;
                }
            }

            $this->assign('storeList', $storeList);
            $this->assign('pagebar', $p->show());
            $this->assign('coupon_id', $coupon_id);
            $this->assign('status', $status);
            $this->display();
        } else {
            $this->error_tips('此优惠券不存在');
        }
    }

    /**
     * 指定店铺
     */
    public function change()
    {
        $coupon_id = isset($_POST['coupon_id']) ? intval($_POST['coupon_id']) : 0;
        $store_ids = isset($_POST['store_ids']) ? $_POST['store_ids'] : [];
        $type = isset($_POST['type']) ? intval($_POST['type']) : 0;

        if (empty($store_ids) || $coupon_id < 1) {
            $this->error_tips('参数有误');
        }
        $coupon = D('System_coupon')->where(array('coupon_id' => $coupon_id))->find();
        if ($coupon['use_type'] == 0) {
            $this->error_tips('此优惠券无需指定店铺');
        }
        $mod = M('System_coupon_store');
        if ($type == 0) {
            //删除
            M('System_coupon_store')->where(['coupon_id' => $coupon_id, 'store_id' => ['IN', $store_ids]])->delete();
            $this->success('解除指定成功');
        } else {
            //指定
            M('System_coupon_store')->where(['coupon_id' => $coupon_id, 'store_id' => ['IN', $store_ids]])->delete();
            $data = [];
            foreach ($store_ids as $sid) {
                $data[] = [
                    'store_id' => $sid,
                    'coupon_id' => $coupon_id
                ];
            }
            M('System_coupon_store')->addAll($data);
            $this->success('指定成功');
        }
    }


    /**
     * 领券中心装修
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function center_decoration()
    {
        $names = ['coupon_main_color', 'coupon_main_title', 'coupon_main_sub_title', 'coupon_main_system_coupon_alias'];
        $config = D('config')->where(['name' => ['IN', $names]])->getField('name,value');
        if (IS_AJAX) {
            $this->ajaxReturn(['config' => $config]);
        }
        $this->assign('config', $config);
        $this->display();
    }

    /**
     * 保存领券中心分享配置
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function set_center_decoration_share()
    {
        $fields = ['coupon_main_title', 'coupon_main_sub_title', 'coupon_main_share_pic'];
        if (IS_POST) {
            if ($_FILES && $_FILES['coupon_main_share_pic']['size'] > 0) {
                $image = D('Image')->handle($this->system_session['id'], 'coupon_share', 0, array('size' => 10), false);
                if ($image['error']) {
                    $this->error($image['msg'] ? $image['msg'] : $image['message']);
                }
                $_POST = array_merge($_POST, $image['url']);
            }
            foreach ($fields as $k) {
                if(isset($this->config[$k])){
					D('Config')->where(['name' => $k])->save(['value' => $_POST[$k]]);
				}else{
					D('Config')->data(['name' => $k, 'value' => $_POST[$k]])->add();
				}
            }
            //删除缓存
            D('Config')->clearDataCacheDir('system_config');
            $this->success('保存成功！');
        } else {
            $config = D('Config')->where(['name' => ['IN', $fields]])->select();
            $config = array_combine(array_column($config, 'name'), array_column($config, 'value'));
            $this->assign('config', $config);
            $this->display();
        }
    }

    /**
     * 修改平台优惠券中心平台券名称
     * @author: 张涛
     * @date: 2020/11/26
     */
    public function edit_system_coupon_alias()
    {
        if (IS_POST) {
			if(isset($this->config['coupon_main_system_coupon_alias'])){
				D('Config')->where(['name' => 'coupon_main_system_coupon_alias'])->save(['value' => $_POST['coupon_main_system_coupon_alias']]);
			}else{
				D('Config')->data(['name' => 'coupon_main_system_coupon_alias', 'value' =>$_POST['coupon_main_system_coupon_alias']])->add();
			}
			
            //删除缓存
            D('Config')->clearDataCacheDir('system_config');
            $this->success('保存成功！');
        } else {
            $value = D('Config')->where(['name' => 'coupon_main_system_coupon_alias'])->getField('value');
            $this->assign('coupon_main_system_coupon_alias', $value);
            $this->display();
        }
    }
}
