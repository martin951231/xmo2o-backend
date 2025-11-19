<?php
class GiftpickAction extends BaseAction 
{
	protected $_pick_address;
	
	public function _initialize()
	{
		parent::_initialize();
		
		$this->_pick_address = session('giftpick');
		
		if (ACTION_NAME != 'login') {
			if (empty($this->_pick_address)) {
				redirect(U('login'));
				exit();
			} else {
				$this->_pick_address = D('Gift_pick_address')->field(true)->where(array('pick_id' => $this->_pick_address['pick_id']))->find();
				if(empty($this->_pick_address)){
					$this->error('自提点不存在！');
				}
				$this->assign('pick_address', $this->_pick_address);
			}
		} elseif ($this->_pick_address && ACTION_NAME != 'logout') {
			redirect(U('index'));
		}
	}
	public function login()
	{
		if (IS_POST) {
			if(md5($_POST['verify']) != $_SESSION['admin_verify']){
				exit(json_encode(array('error'=>'1','msg'=>'验证码不正确！','dom_id'=>'verify')));
			}
			$pwd = htmlspecialchars($_POST['pwd']);
			$now_pick = D('Gift_pick_address')->field(true)->where(array('pwd' => $pwd))->find();
			if (empty($now_pick)) {
				exit(json_encode(array('error'=>'2','msg'=>'不存在的登录秘钥！','dom_id'=>'account')));
			}

			$data['pick_id'] = $now_pick['pick_id'];
			$data['last_time'] = $_SERVER['REQUEST_TIME'];
			if (D('Gift_pick_address')->data($data)->save()) {
				session('giftpick', $now_pick);
				exit(json_encode(array('error'=>'0','msg'=>'登录成功,现在跳转~','dom_id'=>'pwd')));
			} else {
				exit(json_encode(array('error'=>'6','msg'=>'登录信息保存失败,请重试！','dom_id'=>'pwd')));
			}
		} else {
			$this->display();
		}
	}

	public function logout()
	{
		session('giftpick',null);
		redirect(U('login'));
	}
	
    public function index()
    {
		$where= array('pick_id' => $this->_pick_address['pick_id'], 'type' => 0);
		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'order_id') {
				$where['order_id'] = htmlspecialchars($_GET['keyword']);
			}  elseif ($_GET['searchtype'] == 'name') {
				$where['nickname'] = htmlspecialchars($_GET['keyword']);
			} elseif ($_GET['searchtype'] == 'phone') {
				$where['phone'] = htmlspecialchars($_GET['keyword']);
			} elseif ($_GET['searchtype'] == 'order_name') {
				$where['order_name'] =$_GET['keyword'];
			}
		}
		if (!empty($_GET['begin_time'])) {
			$where['begin_time'] = strtotime($_GET['begin_time']." 00:00:00");
		}

		if (!empty($_GET['end_time'])) {
			$where['end_time'] = strtotime($_GET['end_time']." 23:59:59");
		}

		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips("结束时间应大于开始时间");
			}
		}

		$res = D('Gift_pick_order')->get_limit_list_page($where);

        $this->assign('pagebar', $res['pagebar']);
        $this->assign('order_list', $res['list']);
        $this->display();
    }
	
	public function order_detail()
	{
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$order = D('Gift_order')->get_order_detail_by_id_and_merId($order_id);
		$this->assign('now_order', $order);
		$this->display();
	}
	
	//接货
	public function apick()
	{
		$order_id = $condition['order_id'] = intval($_POST['order_id']);
		if (!$this->check_order($order_id)) {
			exit(json_encode(array('status' => 0, 'info' => '不存在的订单！')));
		}
		$database = D('Gift_order');
		$order = $database->field(true)->where($condition)->find();
		if(empty($order)){
			exit(json_encode(array('status' => 0, 'info' => '订单不存在！')));
		}
		if ($order['status'] == 4 || $order['status'] == 5) exit(json_encode(array('status' => 0, 'info' => '订单已取消，不能接货！')));
		if ($order['paid'] == 0) exit(json_encode(array('status' => 0, 'info' => '订单未支付，不能接货！')));
		
		if (D('Gift_order')->where(array('order_id' => $order_id))->save(array('status' => 3))) {//接货
			D('Gift_pick_order')->where(array('order_id' => $order['order_id']))->save(array('status' => 2));
			exit(json_encode(array('status' => 1, 'info' => '已接货', 'type' => $order['is_pick_in_store'])));
		} else {
			exit(json_encode(array('status' => 0, 'info' => '接货失败，稍后重试！')));
		}
	}
	
/*	//发货
	public function spick()
	{
		$order_id = $condition['order_id'] = intval($_REQUEST['order_id']);
		if (!$this->check_order($order_id)) {
			$this->error('不存在的订单！');
		}

		$database = D('Shop_order');
		$order = $database->field(true)->where($condition)->find();
		if(empty($order)){
			$this->error('订单不存在！');
		}
		if ($order['status'] == 4 || $order['status'] == 5) $this->error('订单已取消，不能发货！');
		if ($order['paid'] == 0) $this->error('订单未支付，不能发货！');
		if ($order['status'] != 9) $this->error('还未接货，不能发货！');
		$uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
		
		if ($user = D('Deliver_user')->where(array('uid' => $uid))->find()) {
			if (empty($user['status'])) {
				$this->error("账号已禁止，不能分配");
			}
		}
		$data['status'] = 10;
		$data['order_status'] = 1;
		if($database->where($condition)->save($data)){
			D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->save(array('status' => 3));
			D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 14, 'name' => $this->_pick_address['pick_addr'], 'phone' => $this->_pick_address['phone']));
			if ($order['is_pick_in_store'] != 2) {
			    
			    $store = M('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
			    $store['adress'] = $this->_pick_address['pick_addr'];
			    $store['long'] = $this->_pick_address['long'];
			    $store['lat'] = $this->_pick_address['lat'];
			    
			    $result = D('Deliver_supply')->saveOrder($order_id, $store);
			    if ($result['error_code']) {
			        D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save(array('status' => 0, 'last_time' => time()));
			        $this->error($result['msg']);
			        exit;
			    }
			}
		} else {
			$this->error('发货失败');
		}
		$this->success('已发货', U('Pick/deliver_user', array('uid' => $uid, 'order_id' => $order_id)));
	}*/

	private function check_order($order_id)
	{
		$order = D('Gift_pick_order')->where(array('order_id' => $order_id, 'pick_id' => $this->_pick_address['pick_id'], 'type' => 0))->find();
		if (empty($order)) return false;
		return $order;
	}
	
	public function deliver_user()
	{
		$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$order = $this->check_order($order_id);
		if (!$order) $this->error('不存在的订单！');
		
		$database = D('Shop_order');
		$shop_order = $database->field(true)->where(array('order_id' => $order['order_id']))->find();
		if(empty($shop_order)){
			$this->error('订单不存在！');
		}
		if ($shop_order['is_pick_in_store'] == 1) {
			$users = D('Deliver_user')->where(array('store_id' => $shop_order['store_id'], 'status' => 1))->select();
			$user = D('User_adress')->where(array('adress_id' => $shop_order['address_id'], 'uid' => $shop_order['uid']))->find();
			foreach ($users as &$v) {
				$v['range'] = getRange(getDistance($v['lat'], $v['lng'], $user['latitude'], $user['longitude']));
			}
		}
		$this->assign('uid', $uid);
		$this->assign('order', $order);
		$this->assign('user_list', $users);
		$this->display();
	}
	
	//确认收货
	public function check()
	{
		$order_id = $condition['order_id'] = intval($_POST['order_id']);
		if (!$this->check_order($order_id)) {
			$this->error('不存在的订单！');
		}
		
		$database_gift_order = D('Gift_order');
		$order = $database_gift_order->field(true)->where($condition)->find();
		if(empty($order)){
			$this->error('订单不存在！');
		}
		if ($order['paid'] == 0) $this->error('订单未支付，不能确认收货！');
		if ($order['status'] != 3) $this->error('还未接货，不能确认收货！');
		
		
		
		//配送状态更改成已完成
		$data = array('status' => 2);
		if ($result = $database_gift_order->where(array('order_id' => $order_id))->data($data)->save()) {
            D("System_order")->save_order_status('gift', $order_id, 6,2);
			D('Gift_pick_order')->where(array('order_id' => $order['order_id']))->save(array('status' => 3));
			// $this->shop_notice($order);
			$this->success('已完成');
		} else {
			$this->error("失败");
		}
		
	}


	private function shop_notice($order)
	{
		if ($store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find()) {
			//验证增加商家余额
			$order['order_type']='shop';
			$order['desc'] = L_('用户购买X1订单记入收入',['X1'=>$this->config['shop_alias_name']]);
			D('SystemBill')->bill_method($order['is_own'],$order);

			//商家推广分佣
			$now_user = M('User')->where(array('uid' => $order['uid']))->find();
			D('Merchant_spread')->add_spread_list($order, $now_user, 'shop', $now_user['nickname'] . L_('用户购买X1商品获得佣金',['X1'=>$this->config['shop_alias_name']]));

			//积分
			if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $order['score_discount_type']!=2)){
				 $now_merchant = D('Merchant')->get_info($store['mer_id']);
	            if($now_merchant['score_get_percent']>=0){
	                $this->config['score_get'] = $now_merchant['score_get_percent']/100;
	            }

	            if($order['is_own'] && C('config.user_own_pay_get_score')!=1){
					$order['payment_money']= 0;
				}	

				if($this->config['shop_goods_score_edit'] == 1){
					$score_get = D('Percent_rate')->shop_get_score($order);
				}else{
					
					$score_get  = round(($order['payment_money'] + $order['balance_pay']) * $this->config['score_get']);
				}
				if ($score_get>0) {
                    D('User')->add_score($order['uid'], $score_get, '在 ' . $store['name'] . ' 中消费' . floatval($order['price']) . C('config.Currency_txt').' 获得'.$this->config['score_name']);
                    D('Scroll_msg')->add_msg('shop',$now_user['uid'],'用户'.str_replace_name($now_user['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']). $store['name'] . ' 中消费获得'.$this->config['score_name']);
                }
			}
			D('Userinfo')->add_score($order['uid'], $order['mer_id'], $order['price'], '在 ' . $store['name'] . ' 中消费' . floatval($order['price']) . C('config.Currency_txt').' 获得'.$this->config['score_name'].'');
		
			//短信
			$sms_data = array('mer_id' => $store['mer_id'], 'store_id' => $store['store_id'], 'type' => 'shop');
			if ($this->config['sms_finish_order'] == 1 || $this->config['sms_finish_order'] == 3) {
				if (empty($order['phone'])) {
					$user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();
					$order['phone'] = $user['phone'];
				}
				$sms_data['uid'] = $order['uid'];
				$sms_data['mobile'] = $order['userphone'];
				$sms_data['sendto'] = 'user';
				$sms_data['content'] = '您在 ' . $store['name'] . '店中下的订单(订单号：' . $order['order_id'] . '),已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！';
				Sms::sendSms($sms_data);
			}
			if ($this->config['sms_finish_order'] == 2 || $this->config['sms_finish_order'] == 3) {
				$sms_data['uid'] = 0;
				$sms_data['mobile'] = $store['phone'];
				$sms_data['sendto'] = 'merchant';
				$sms_data['content'] = '顾客购买的' . $order['name'] . '的订单(订单号：' . $order['order_id'] . '),已经完成了消费！';
				Sms::sendSms($sms_data);
			}
		
			//小票打印 主打印
			$printHaddle = new PrintHaddle();
			$printHaddle->printit($order['order_id'], 'shop_order', 2);
			
// 			$msg = ArrayToStr::array_to_str($order['order_id'], 'shop_order');
// 			$op = new orderPrint($this->config['print_server_key'], $this->config['print_server_topdomain']);
// 			$op->printit($store['mer_id'], $order['store_id'], $msg, 2);
		
// 			//分单打印
// 			$str_format = ArrayToStr::print_format($order['order_id'], 'shop_order');
// 			foreach ($str_format as $print_id => $print_msg) {
// 				$print_id && $op->printit($store['mer_id'], $order['store_id'], $print_msg, 2, $print_id);
// 			}
		}
	}
}