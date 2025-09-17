<?php
//商家推广关系

class Merchant_spreadModel extends Model{
	//推广关系增加
	public function spread_add($mer_id,$openid,$from_type,$store_id=0){
		//自动领卡
		$auto_result = D('Card_new')->auto_reg_or_bind($mer_id,$openid);

		//绑定商家推广关系，不能是已绑定商家推广关系和用户推广关系
		$now_user = D("User")->get_user($openid, 'openid');
		if(!$this->where(array('openid'=>$openid))->find() && !M('User_spread')->where(array('_string'=>'uid='.intval($now_user['uid']).' OR openid="'.$openid.'"'))->find()) {
			//如果用户以前是商家的粉丝，则让其成为最早关注的商家的推广关系
			if($user_mer = M('Merchant_user_relation')->where(array('openid'=>$openid,'mer_id'=>array('neq','')))->order('dateline ASC')->find()) {
				$date['mer_id'] = $user_mer['mer_id'];
			}else{
				$date['mer_id'] = $mer_id;
			}


			$date['openid'] = $openid;
			$date['spread_time'] = $_SERVER['REQUEST_TIME'];
			$date['from_type'] = $from_type;
			$date['store_id'] = $store_id;
			$date['uid'] = intval($now_user['uid']);

			$mer_user = D('Merchant')->get_merchant_user($mer_id);
			//通知商家
			if($mer_user['openid']){
				import('ORG.Net.Http');
				$http = new Http();
				$access_token_array = D('Access_token_expires')->get_access_token();
				if (!$access_token_array['errcode']) {
					$return = $http->curlGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token_array['access_token'].'&openid='.$openid.'&lang=zh_CN');
					$userifo = json_decode($return,true);
					$nickname=$userifo['nickname'];
				}
				$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

				$model->sendTempMsg('OPENTM405462911', 
					array(
						'href' => '',
						'wecha_id' => $mer_user['openid'], 
						'first' => L_("您增加了一名推广用户【X1】\n",array("X1" => $nickname)), 
						'keyword1' => L_('用户扫店铺推广二维码'), 
						'keyword2' =>L_('已发送'),
						'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
						'keyword4' => $mer_user['mer_name'], 
						'remark' => L('\n请点击查看详细信息！')
					)
				);	
			}
			$this->add($date);
		}
	}

	//增加推广佣金记录
	public function add_spread_list($order_info,$buyer,$type,$des,$system_take=0){
		$spread_user= M('Merchant_spread')->where(array('openid'=>$buyer['openid']))->find();
		$spread_rate = D('Percent_rate')->get_rate($spread_user['mer_id'],$type,$buyer);
		$date['mer_id'] = $spread_user['mer_id'];
		if(empty($spread_user)||$spread_rate==0||($spread_user['mer_id']==$order_info['mer_id']&&C('config.spread_money_get_type')!=1)||empty($buyer)){
			return true;
		}
		//商家推广用户消费不给佣金
        if ($this->is_merchant_spread_user($order_info['mer_id'], $order_info['uid'])) {
            return true;
        }

		if($order_info['is_own']){
			$order_info['payment_money']=0;
		}

		$date['uid'] = $order_info['uid'];
		$date['openid'] = $buyer['openid'];
        $total_money = 0;
		if (C('config.system_take_spread_percent_mer')>0) { // 开启商家推广佣金占抽成比例
			if ($system_take) {
				$date['money'] = round($system_take*$spread_rate*C('config.system_take_spread_percent_mer')/10000,2);
                $total_money = $system_take;
			}else{
				return true;
			}
		}else{
			if(C('config.platform_get_merchant_rate_type')==1){//商家获得推广佣金计算方式1根据平台抽成计算
				$date['money'] = round(($system_take*$spread_rate)/100,2);
                $total_money = $system_take;
			}else{
				$date['money'] = round((($order_info['balance_pay']+$order_info['payment_money'])*$spread_rate)/100,2);
                $total_money = getFormatNumber($order_info['balance_pay']+$order_info['payment_money']);
			}	
		}


		$date['order_type'] = $type;
		switch($type){
			case 'group':
			case 'shop':
			case 'mall':
			case 'meal':
			case 'appoint':
				$type_name = C('config.'.$type.'_alias_name');
				break;
			case 'store':
				$type_name = C('config.store_alias_name').L_('买单');
				break;
			case 'cash':
				$type_name=C('config.cash_alias_name').L_('支付');
				break;
			case 'wxapp':
				$type_name = L_('微信营销');
				break;
			case 'weidian':
				$type_name = L_('微店');
				break;
			case 'coupon':
				$type_name  = L_('平台活动');
				break;
			case 'yydb':
				$type_name  = L_('平台活动');
				break;
			case 'waimai':
				$type_name  = L_('外卖');
				break;
		}
		$date['order_id'] = $order_info['order_id'];
		$date['verify_mer_id'] = $order_info['mer_id'];
		$date['add_time'] = $_SERVER['REQUEST_TIME'];
		$date['des'] = $des;
		$date['status'] = 0;
		if(C('config.merchant_replace_money')>0 && $date['money']>=C('config.merchant_replace_money') && $order_info['mer_id']){
			M('Merchant_spread')->where(array('openid'=>$buyer['openid']))->setField('mer_id',$order_info['mer_id']);
		}
		$id = M('Merchant_spread_list')->add($date);
		if($id&&$date['money']>0){  //增加商家余额
			$now_order['total_money'] = $total_money;
            $now_order['money'] = $date['money'];
			$now_order['order_type'] = 'spread';
			$now_order['order_id'] = $id;
			$now_order['mer_id']  = $spread_user['mer_id'];

			$now_order['desc']=L_("购买X1商品获得佣金,订单号：".$id,array("X1" => $type_name));
			D('SystemBill')->bill_method(0,$now_order);
		}

		$spread_rate = D('Percent_rate')->get_rate(0,$type,$buyer);


		if(C('config.open_user_award')==1){
			$award_money = round(($order_info['balance_pay']+$order_info['payment_money'])*$spread_rate/100,2);

			$now_order['total_money'] = getFormatNumber($order_info['balance_pay']+$order_info['payment_money']);
            $now_order['money'] = $award_money;
			$now_order['order_type'] = 'award';
			$now_order['order_id'] = '';
			$now_order['mer_id']  = $spread_user['mer_id'];
			$now_order['desc']=L_('推广用户消费获得奖励金');
	
			D('SystemBill')->bill_method(0,$now_order);
		}
	}

	//商家获取推广用户列表（不包括商家粉丝）
	public function mer_spread_list($mer_id){
		$spread_list = $this->where(array('mer_id'=>$mer_id))->select();
		return $spread_list;
	}

	public function get_spread_num($now_user_openid,$uid){
		return M('User_spread')->join('as us left join '.C('DB_PREFIX').'user u ON  us.spread_uid = u.uid left join '.C('DB_PREFIX').'merchant m ON m.uid=u.uid')->where(array('us.spread_uid'=>$uid))->count();
	}

    /**
     * 是否商家推广用户
     * @param $mer_id
     * @param $uid
     */
    public function is_merchant_spread_user($mer_id, $uid)
    {
        $openid = M('User')->where(['uid' => $uid])->getField('openid');
        if (empty($openid)) {
            $where = [
                'mer_id' => $mer_id,
                'uid' => $uid
            ];
        } else {
            $where = [
                'mer_id' => $mer_id,
                '_string' => "`openid`='" . $openid . "' OR `uid`='" . $uid . "'"
            ];
        }
        $rs = $this->where($where)->find();
        return $rs ? true : false;
    }
}
?>