<?php
class UserSpreadMerModel extends Model{
	public function user_add_money($now_order,$money){
		$user_model = D('User');
		$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
		if($now_merchant['spread_code']!=''){
            $spread_code = $now_merchant['spread_code'];
            $now_user = $order_user = $user_model->get_user($spread_code,'spread_code');
        }else{
            return false;
        }
        $arr['first_percent'] = C('config.c2b_first_rate');
        $arr['second_percent'] = C('config.c2b_second_rate');
        $arr['third_percent'] = C('config.c2b_third_rate');

        $spread_users[]=$now_user['uid'];
        $spread_where['_string'] = "uid = '{$now_user['uid']}'";
        $now_user_spread = D('User_spread')->where($spread_where)->find();

        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=index';
        $first_user = [];
        $second_user = [];
        $spread_uid = 0;
        foreach ($arr as $key=>$percent) {
            if($key=='first_percent'){
                $spread_user = $first_user = $now_user;
            }else{
                $spread_user = D('User')->get_user($now_user_spread['spread_uid']);
            }
            if($key=='second_percent'){
                $second_user = $spread_user;
                $spread_uid = $first_user['uid'];
            }
            if($key=='third_percent'){
                $spread_uid = $second_user['uid'];
            }
            $spread_money  = round(($money) * $percent / 100, 2);


            $spread_data = array(
                'uid'           => $spread_user['uid'],
                'spread_uid'    => $spread_uid,
                'get_uid'       => $first_user['uid'],
                'money'         => $spread_money,
                'order_type'    => 'user_s_mer',
                'type'          => $now_order['order_type'],//订单类型
                'order_id'      => $now_order['order_id'],
                'third_id'      => 0,
                'status'        => 1,
                'add_time'      => $_SERVER['REQUEST_TIME'],
                'spread_mer_id' => $now_order['mer_id']
            );

            if($spread_user['spread_change_uid']!=0){
                $spread_data['change_uid'] = 	$spread_user['spread_change_uid'];
            }

            if($key=='first_percent') {
                $spread_relation_txt = L_('您推广的商家').$now_merchant['name'];
            }else if($key=='second_percent'){
                $spread_relation_txt =L_("X1的子用户推广的商家X2",array("X1" => $spread_user['nickname'],"X2" => $now_merchant['name'])); 
            }else{
                $spread_relation_txt = L_("X1的子用户的子用户推广的商家X2",array("X1" => $spread_user['nickname'],"X2" => $now_merchant['name'])) ; 
            }
            $spread_data['desc'] = L_("X1产生交易，您获得佣金。",array("X1" => $spread_relation_txt)); 
            // 处理长订单id
            if ($now_order['real_orderid']) {
                $spread_data['real_orderid'] = $now_order['real_orderid'];
            }
            if (!$spread_data['real_orderid'] && $now_order['orderid']) {
                $spread_data['real_orderid'] = $now_order['orderid'];
            }

            D('User_spread_list')->data($spread_data)->add();
            if($spread_money>0) {
                $ask = $this->userMoneyAsk($now_order['order_type']);
                D('User')->add_money($spread_user['uid'],$spread_money,L_("X1产生交易，您获得佣金。",array("X1" => $spread_relation_txt)), $ask, $now_order['order_id']); 
                $model->sendTempMsg('OPENTM201812627',
                    array('href' => $href,
                        'wecha_id' => $spread_user['openid'],
                        'first' =>L_("X1产生交易，您获得佣金。",array("X1" => $spread_relation_txt)), 
                        'keyword1' => $spread_money,
                        'keyword2' => date("Y年m月d日 H:i"),
                        'remark' => L_('点击查看详情！'))
                );
            }

            if($key=='third_percent'){
                break;
            }

            $now_user = $spread_user;

            $spread_where['_string'] = "uid = '{$spread_user['uid']}'";
            $now_user_spread = D('User_spread')->where($spread_where)->find();
        }

	}
	//  `ask` '去向 1众包 2顺风车  3分类信息 6餐饮订单 7到店付订单  8 store_order 9 village_group_order 10 appoint_order
    // 11 gift_order 12 group_order 13 meal_order 14 merchant_integral_mall_goods_order 15 plat_order
    //16 scenic_order  17 service_user_publish_order 18 shop_order  19 user_recharge_order 20 village_group_order_temp
    // 21 weidian_order 22 wxapp_order 23 scenic_aguide',
	public function userMoneyAsk($type)
    {
        $orderType = [
            'cash'           => 7,
            'store'          => 8,
            'village_group'  => 9,
            'appoint'        => 10,
            'gift'           => 11,
            'group'          => 12,
            'meal'           => 13,
            'shop'           => 18,
            'waimai'         => 18,
            'weidian'        => 21,
            'wxapp'          => 22,
        ];
        
        if(isset($orderType[$type])){
            return $orderType[$type];
        }
        return 0;
    }


	public function get_spread_list($uid,$page=0,$page_count=0,$is_system=0){
		$condition_user_money_list['uid'] = $uid;
		$condition_user_money_list['order_type'] = 'user_s_mer';
		$condition_user_money_list['spread_mer_id'] = array('gt',0);

		$model = M('User_spread_list');
		if($is_system){
			import('@.ORG.system_page');
		}else{
			import('@.ORG.user_page');
		}
		$count = $model->where($condition_user_money_list)->count();
		$p = new Page($count,10);
		if($page){
			$return['money_list'] = $model->field(true)->where($condition_user_money_list)->order('`pigcms_id` DESC')->page($page.','.$page_count)->select();
		}else{
			$return['money_list'] = $model->field(true)->where($condition_user_money_list)->order('`pigcms_id` DESC')->limit($p->firstRow.',10')->select();
		}

		$return['pagebar'] = $p->show();
		return $return;
	}

	public function get_list($uid=0,$page=0,$page_count=0,$is_system=0){
		$now_user = M('User')->where(array('uid'=>$uid))->find();
        $condition_user_money_list['spread_code'] = $now_user['spread_code'];
		$model = M('Merchant');
		if($is_system){
			import('@.ORG.system_page');
		}else{
			import('@.ORG.user_page');
		}
		$count =$model->where($condition_user_money_list)->count();
		$p = new Page($count,$page_count);
		if($page){
			$return['mer_list'] = $model->field(true)->where($condition_user_money_list)->order('`mer_id` DESC')->page($page.','.$page_count)->select();
		}else{
			$return['mer_list'] = $model->field(true)->where($condition_user_money_list)->order('`mer_id` DESC')->limit($p->firstRow.','.$page_count)->select();
		}
		$return['pagebar'] = $p->show();
		return $return;
	}

    public function get_spread_user($now_user_openid,$uid){
        $now_user_spread_user_list =M('User_spread')->join('as us left join '.C('DB_PREFIX').'user u ON us.uid  = u.uid')->where(array('us.spread_uid'=>$uid))->group("us.uid")->select();
        foreach($now_user_spread_user_list as &$v){
            $v['spread_count'] = M('User_spread')->where(array('spread_uid'=>$v['uid'],'uid'=>['neq','null']))->count();
            $v['spread_money'] = M('User_spread_list')->where(array('get_uid'=>$v['uid'],'uid'=>$uid,'order_type'=>'user_s_mer','spread_mer_id'=>array('gt',0)))->sum('money');
            $v['spread_count'] = empty($v['spread_count'])?0:$v['spread_count'];
            $v['spread_money'] = empty($v['spread_money'])?0:$v['spread_money'];
        }
        return array('spread_user_list'=>$now_user_spread_user_list);
    }
}
?>