<?php
class Village_group_orderModel extends Model{
	public function get_order($order_id){
		return $this->where(array('order_id'=>$order_id))->find();
	}
	public function get_pay_order($order_id){
		$now_order = $this->get_order($order_id);
        if ($now_order['discount_price']) {
            if ($now_order['coupon_price']) {
                $now_order['order_total_money'] = floatval($now_order['discount_price'] + $now_order['coupon_price']);
            } else {
                $now_order['order_total_money']	=	floatval($now_order['discount_price']);
            }
        } elseif($now_order) {
            $now_order['order_total_money']	=	floatval($now_order['total_money']);
        }
		$order_info = array(
			'pay_offline' 			=> false,		  //线下支付
			'pay_merchant_balance' 	=> false,		  //商家余额
			'pay_merchant_coupon' 	=> false,		  //商家优惠券
			'pay_merchant_ownpay' 	=> false,		  //商家自有支付
			'pay_system_balance' 	=> true,		  //平台余额
			'pay_system_coupon' 	=> false,		  //平台优惠券
			'pay_system_score' 		=> $now_order['score_used_count'] > 0 ? true : false,		  //平台积分抵现
			'order_info' 			=> $now_order,	  //平台积分抵现
            'order_total_money'	=>	floatval($now_order['discount_price']),
		);


		return $order_info;
	}

    protected function get_goods_category($goods_id){
        if(empty($goods_id)) return array();
        $where = array();
        $where['g.goods_id'] = $goods_id;
        $field = 'c.*,g.goods_id';
        $left_join = 'as g LEFT JOIN '.C('DB_PREFIX').'village_group_category c ON g.cat_fid = c.cat_id';
        return D('Village_group_goods')->field($field)->join($left_join)->where($where)->find();
    }

	public function order_detail($order_id,$leader_id=''){

		$condition['order_id'] = $order_id;
		$now_order = $this->where($condition)->find();
		$plat_order = PD('Plat_order')->get_order_by_business_id(array('business_type'=>'village_group','business_id'=>$order_id));
        $condition['date_num'] = 1;
        $condition['is_delay'] = 0;
		$now_order['order_detail'] = M('Village_group_order_detail')->where($condition)->select();

		$goods = PD('Village_group')->get_order_goods($order_id, $now_order['start_uid'],$leader_id);
        //var_dump($goods);die;
		$now_order['status_txt']  = PD('Village_group')->order_status[$now_order['status']];
		$now_order['goods'] = $goods['goods'];
		// var_dump($now_order['goods']);
		$now_order['plat_order'] = $plat_order;
		$now_order['now_user'] =   M('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
        if (empty($now_order['now_user'])) {
            $now_order['now_user'] = array();
        }
        if (empty($now_order['now_user']['avatar'])) {
            $now_order['now_user']['avatar'] = C('config.site_url') . '/tpl/Wap/pure/static/images/new_my/pic-default.png?t=001';
        }
		$now_order['order_info'] = array();
		foreach ($now_order['order_detail'] as &$item) {
			$item['image'] =$goods['goods'][$item['goods_id']]['image'] ;
			$item['label'] =$goods['goods'][$item['goods_id']]['label'] ;
			$item['increase_price'] =$goods['goods'][$item['goods_id']]['increase_price'] ;
			$item['goods_bind_area'] =$goods['goods'][$item['goods_id']]['goods_bind_area'] ;
            $now_order['order_info'][$item['goods_id']] = array();
            $now_order['order_info'][$item['goods_id']]['num'] = $item['num'];
            $item['mer_id'] =$goods['goods'][$item['goods_id']]['mer_id'] ;
		}
		return $now_order;
    }
	public function get_order_url($order_id,$is_mobile){
		$now_order = $this->get_order($order_id);

		return C('config.site_url').'/wap.php?c=Village_group&a=orderDetail&order_id='.$order_id;
	}
	public function after_pay($order_id,$plat_order_info){

        fdump_sql(func_get_args(), 'villageGroupOrderAfterPay');
        $uniqueKey = md5($order_id.'villageGroupOrderAfterPay');
        $lockRs = setLock($uniqueKey, 10);
        if ($lockRs['error_no']) {
            fdump_api([$order_id,$lockRs],'village_group_order_after_pay',1);
            return array('error' => 1, 'msg' => $lockRs['error_msg']);
        }

		$now_order = $this->get_order($order_id);
        $now_user = D('User')->get_user($now_order['uid']);

        if (empty($now_order)) {
            fdump_api([$order_id,'当前订单不存在'],'village_group_order_after_pay',1);
            return array('error' => 1, 'msg' => L_('当前订单不存在！'));
        } elseif ($now_order['pay_time'] > 0) {
            fdump_api([$order_id,'该订单已付款'],'village_group_order_after_pay',1);
            return array('error' => 1, 'msg' => L_('该订单已付款！'));
        } 

        //判断平台优惠券
        if($now_order['coupon_id']){
            $now_coupon = D('System_coupon')->get_coupon_by_id($now_order['coupon_id']);
            if(empty($now_coupon)){
                fdump_api([$order_id,'您选择的优惠券不存在'],'village_group_order_after_pay',1);
                return $this->wap_after_pay_error($now_order,$plat_order_info,'您选择的优惠券不存在！');
            }
        }
        //如果使用了平台优惠券
        if($now_order['coupon_id']){
            if (!$plat_order_info['business_type']) {
                $plat_order_info['business_type'] = 'village_group';
            }
            $use_result = D('System_coupon')->user_coupon($now_order['coupon_id'],$plat_order_info['business_id'],$plat_order_info['business_type'],0,$now_order['uid']);
            if($use_result['error_code']){
                fdump_api([$order_id,$use_result],'village_group_order_after_pay',1);
                return array('error'=>1,'msg'=>$use_result['msg']);
            }
        }

        //如果用户使用了积分抵扣，则扣除相应的积分
        $score_used_count=$now_order['score_used_count'];
        if($now_user['score_count']<$score_used_count){
            fdump_api([$now_user,$now_order],'village_group_order_after_pay',1);
            return array('error'=>1,'msg'=>'保存订单失败！请重试或联系管理员。');
        }
        if($score_used_count>0){
            $desc = '购买 '.$now_order['order_name'].'商品 扣除'.C('config.score_name');
            if ($now_order['real_orderid']) {
                $desc .= '，订单编号' . $now_order['real_orderid'];
            }elseif($now_order['order_id']){
                $desc .= '，订单编号' . $now_order['order_id'];
            }elseif($now_order['cashier_id']){
                $desc .= '，订单编号' . $now_order['cashier_id'];
            }
            $use_result = D('User')->user_score($now_order['uid'],$score_used_count,$desc);
            if($use_result['error_code']){
                fdump_api([$order_id,$use_result],'village_group_order_after_pay',1);
                return array('error'=>1,'msg'=>$use_result['msg']);
            }
        }


        //更新order状态

		$data_user_recharge_order['pay_time'] = $_SERVER['REQUEST_TIME'];
		$data_user_recharge_order['last_time'] = $_SERVER['REQUEST_TIME'];
		$data_user_recharge_order['pay_type'] = $plat_order_info['pay_type'];
		$data_user_recharge_order['status'] = 1;//0 未支付 1已支付
        // 处理团长自提地址-记录
        $address = M('Village_group_pick_address')->where(array('header_uid'=>$now_order['start_uid']))->find();
        if (!empty($address)) {
            if ($address['pick_addr']) {
                $data_user_recharge_order['pick_addr'] = $address['pick_addr'];
            }
            if ($address['province_id']) {
                $data_user_recharge_order['pick_province_id'] = $address['province_id'];
            }
            if ($address['city_id']) {
                $data_user_recharge_order['pick_city_id'] = $address['city_id'];
            }
            if ($address['area_id']) {
                $data_user_recharge_order['pick_area_id'] = $address['area_id'];
            }
            if ($address['lat']) {
                $data_user_recharge_order['pick_lat'] = $address['lat'];
            }
            if ($address['long']) {
                $data_user_recharge_order['pick_long'] = $address['long'];
            }
        }
		$where['order_id'] = $order_id;
        fdump_api([$order_id,$data_user_recharge_order],'village_group_order_after_pay',1);
		if($this->where($where)->save($data_user_recharge_order)){
			//更新发起团购的销量等
            D('System_order')->save_reg_order_info('village_group',$order_id,$data_user_recharge_order);
			$order_detail = $this->order_detail($order_id);
			$stock_reduce_type = $order_detail['stock_reduce_type']??0;
			foreach ($order_detail['order_detail'] as $d) {
			    //判断是否是锁定库存，是的话需要减去锁定库存
                if($stock_reduce_type==1){
                    M('Village_group_goods')->where(array('goods_id'=>$d['goods_id']))->setDec('locking_num',$d['num']);
                }
				M('Village_group_goods')->where(array('goods_id'=>$d['goods_id']))->setInc('sell_count',$d['num']);
				// 记录每个商品的分佣比例
				$leader_money = $order_detail['goods'][$d['goods_id']]['leader_money'];
				if ($leader_money) {
					M('Village_group_order_detail')->where(array('id'=>$d['id']))->setField('leader_money',$leader_money);
				}
                M('Village_group_order_detail')->where(array('id'=>$d['id']))->setField('mer_id',$d['mer_id']);
			}


			//清除购物车缓存
			$key = 'cart_cookie'.$now_order['start_uid'];
			$condition_delete['cart_cookie'] = $_COOKIE[$key];

			M('Village_group_order_cart')->where($condition_delete)->delete();

			$now_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
			$param['order_id'] = $order_id;
			$param['status'] = 1;
			$param['from_type'] = 0;
			$param['note'] = '用户完成支付';
			$param['name'] =$now_user['nickname'];
			$param['phone'] =$now_user['phone'];
			D('Village_group')->add_log($param);

			$this->create_delivery_periods_order($order_id);


            //新增消息通知
            $url = '\app\common\model\service\user\UserNoticeService/addNotice';
            $params['type'] = 0;
            $params['business'] = 'village_group';
            $params['order_id'] = $param['order_id'];
            $params['title'] = '商品下单成功';
            $params['content'] = '下单成功，商品将尽快为你送达';
            invoke_v20_service($url, [$params]);

            //记录下用户行为
            foreach ($order_detail['order_detail'] as $val) {
                D('User_behavior_goods')->add_record($val['goods_id'],$val['name'],$now_order['uid'],4,5,$now_order['order_id'],$val['num']);
            }

            //记录直播/短视频销量
            $goods = $order_detail['order_detail'] ?? [];
            if ($now_order['live_type'] == '1' && $now_order['live_id'] > 0 && $goods) {
                //直播添加销量
                foreach ($goods as $gvalue) {
                    D("Live_show_goods")->add_sale_nums($gvalue['goods_id'], $now_order['live_id'], $gvalue['num'], 'village_group');
                }
            }
            if ($now_order['live_type'] == '2' && $now_order['live_id'] > 0 && $goods) {
                //短视频
                foreach ($goods as $gvalue) {
                    D("Live_video_goods")->add_sale_nums($gvalue['goods_id'], $now_order['live_id'], $gvalue['num'], 'village_group');
                }
            }
        }else{
            fdump_api($this->getLastSql(),'village_group_order_after_pay',1);
        }
	}

	// 清空购物车
    public function clear_cart($order_id, $ext_where = []){
        $now_order = $this->get_order($order_id);
        $order_detail = array_column(M("Village_group_order_detail")->where(['order_id'=> $order_id])->field("goods_id")->select(),'goods_id');
        //清除购物车缓存
        $key = 'cart_cookie'.$now_order['start_uid'];
        $map['cart_cookie'] = $_COOKIE[$key];
        $map['uid'] = $now_order['uid'];
        $map['_logic'] = 'or';
        $condition_delete['_complex'] = $map;
        if($ext_where){
            $condition_delete = array_merge($condition_delete, $ext_where);
        }
        if($order_detail){
            $condition_delete['goods_id'] = ['in', $order_detail];
        }
        M('Village_group_order_cart')->where($condition_delete)->delete();
    }

    //支付时，金额不够，记录到帐号
    public function wap_after_pay_error($now_order,$order_param,$error_tips){

        //记录充值的金额，因为 Pay/return_url 处没有返回order的具体信息，故在此调用。
        $user_result['error_code'] = 0;
        if ($order_param['total_money']>0) {
            $alreadyMoney = M("User_money_list")
                ->where(['money'=>$order_param['total_money'],'uid'=>$now_order['uid'],'ask'=> 9, 'ask_id'=>$now_order['order_id']])//9 village_group_order
                ->order('pigcms_id desc')
                ->find();
            if(empty($alreadyMoney)){
                //$user_result = D('User')->add_money($now_order['uid'],$order_param['total_money'],'支付订单：'.$now_order['order_id'].'发生错误！原因是：'.$error_tips,9,$now_order['order_id']);
            }
        }
        $this->where(array('order_id'=>$now_order['order_id']))->setField('status',0);
        // 同步处理下plat_order表格状态
         M('Plat_order')->where(array('business_type'=>'village_group','business_id' => $now_order['order_id']))->setField('paid', 0);
        if($order_param['pay_type']=='weixin'&&ACTION_NAME=='return_url'){
            exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
        }
        if($user_result['error_code']){
            return array('error'=>1,'msg'=>$user_result['msg']);
        }else{
            if($order_param['is_mobile']){
                $return_url = str_replace('/source/','/',U('Village_group/orderDetail',array('order_id'=>$now_order['order_id'])));
            }else{
                $return_url = U('Wap/Village_group/orderDetail',array('order_id'=>$now_order['order_id']));
            }
            return array('error'=>1,'msg'=>$error_tips.'，已将您充值的金额添加到您的余额内。','url'=>$return_url);
        }
    }

    // 实时计算团长销售数量 只计算已消费的
    public function sum_header_sale_num($header_id,$is_show_sale=0) {
	    $where = array(
	        'start_uid' => $header_id,
            'status' => 2,
            'discount_price' => array('gt',0)
        );
	    // $sale_num = M('village_group_order')
     //        ->join('AS vgo JOIN '.C('DB_PREFIX').'village_group_order_detail vd ON vd.order_id = vgo.order_id')
     //        ->where($where)->sum('vd.num');

        $sale_num_price = M('village_group_order')->where($where)->sum('discount_price');
        $where = array(
            'start_uid' => $header_id,
            'status' => 2,
            //'discount_price' => array('elt',0)
        );
        if($is_show_sale==1){  //只统计商品原价金额
            $where['discount_price'] = array('gt',0);
        }else{
            $where['discount_price'] = array('elt',0);
        }
        $sale_num_total = M('village_group_order')->where($where)->sum('total_money');

        if($is_show_sale==1){
            $sale_price = $sale_num_total;
        }else{
            $sale_price = $sale_num_price+$sale_num_total;
        }
	    return $sale_price;
	}

	// 添加计划任务
    public function add_plan($param){
        import('@.ORG.plan');
        $plan_class = new plan();
        $params = array(
            'file'=>'village_group',
            'plan_time'=>time(),
            'param'=>$param,
        );
        $task_id = $plan_class->addTask($params);
        return array('task_id'=>$task_id);
    }
    /*
     * 執行计划任务
     * $param  参数数组
     * */
    public function village_group_runtask($param){
        import('@.ORG.plan');
        $plan_class = new plan();
        switch ($param['type']) {
            case 'village_group_cycle_order': //生成周期购的配送相关单子
                $order_id = $param['order_id'];
                $this->create_delivery_periods_order($order_id);
                return 0;
        }
        return 0;
    }
    private function create_delivery_periods_order($order_id) {
        $d_village_group=D('Village_group');
        $order_info = M('Village_group_order')->where(array('order_id' => $order_id))->find();
        $delivery_periods = $order_info['delivery_periods'];
        // 查询现在已有的订单详情
        $order_detail = M('Village_group_order_detail')->where(array('order_id' => $order_id))->find();
        $create_data = $order_detail;
        $send_time = $d_village_group->lately_cycle_time($order_detail['goods_id'],$order_info['delivery_date'],true);
        M('Village_group_order_detail')->where(array('order_id' => $order_id))->setField('send_time', $send_time);
        unset($create_data['id']);
        $create =  array();
        for($i=2;$i<=intval($delivery_periods);$i++) {
            $create_data['send_time'] = $d_village_group->lately_cycle_time($order_detail['goods_id'],$order_info['delivery_date'],true,$i,0,$send_time);
            $create_data['date_num'] = $i;
            $create[] = $create_data;
            $send_time = $create_data['send_time'];
        }
        M('Village_group_order_detail')->addAll($create);
        return $create_data;
    }  
    
    /*
        1. 总成交额：实际支付的订单总额，含已退款金额
        2. 团长总数：当前筛选条件下的团长总数合计
        3. 总订单数：实际支付的订单总数，含已退款订单数
        4. 总佣金： 当前筛选条件下所有团长获得总佣金合计
        5. 总退款金额：实际退款的订单总金额
     */

    public function sell_order_date($is_system = true, $param)
    {
        // 社区
        $village_list = $param['village_list'];  
        if ($village_list && $village_list != -1) {
            $where['a.id'] = array('in', $village_list);
        }

        // 社区所在城市
        if ($param['area_id'] && $param['area_id'] != 'undefined') {
            $where['a.area_id'] = $param['area_id'];
        } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
            $where['a.city_id'] = $param['city_idss'];
        } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
            $where['a.province_id'] = $param['province_idss'];
        }

        // 筛选时间
        if ($param['selectTimeType'] > 0) {
            $start_time = strtotime(date('Y-m-d', time() - ($param['selectTimeType'] - 1) * 86400));
            $end_time = time();

        }

        if ($param['begin_time'] && $param['end_time']) {
            $start_time = strtotime($param['begin_time']);
            $end_time = strtotime($param['end_time']) + 86400;
            unset($_GET['selectTimeType']);
        }

        if ($end_time < $start_time) {
            return array('error_code' => 1, 'msg' => L_('结束时间不能比开始时间小'));
        }
        if ($start_time) {
            $where['_string'] = "o.pay_time <= {$end_time} AND o.pay_time>={$start_time} ";
        }

        // 支付方式
        $pay_type = $param['pay_type'];
        $pay_type = $pay_type ? $pay_type : 'all';
        if ($pay_type != 'all') {
            if ($pay_type == 'balance') {
                $where['pay_type'] = '';
                $where_pay = " ='' ";
            } elseif ($pay_type == 'alipay') {
                $where['pay_type'] = array('like', 'alipay%');
                $where_pay = " like 'alipay%' ";
            } else {
                $where['pay_type'] = $param['pay_type'];
                $where_pay = " ='" . $param['pay_type'] . "'";
            }
        }

        // 已支付
        $where['o.status'] = array('not in',array(0,10));

        //总成交额
        $total_money_sum = M('Village_group_order')->field('sum(o.total_money) as total_money,sum(o.spread_money) as spread_money,sum(o.refund_money) as refund_money')->join('as o left join '.C('DB_PREFIX').'village_group_pick_address as a on a.header_uid=o.start_uid')->where($where)->select();
        $arr['total_money_sum'] = $total_money_sum[0]['total_money'];

        //团长总数
        $counts = M('Village_group_order')->field('count(DISTINCT(o.start_uid)) as count')->join('as o left join '.C('DB_PREFIX').'village_group_pick_address as a on a.header_uid=o.start_uid')->where($where)->select();
        $arr['header_count'] = $counts[0]['count'];

        //总成交数
        $counts = M('Village_group_order')->join('as o left join '.C('DB_PREFIX').'village_group_pick_address as a on a.header_uid=o.start_uid')->where($where)->count();
        $arr['total_money_count'] = $counts;

        //总佣金
        $arr['spread_money_sum'] = $total_money_sum[0]['spread_money'];

        //退款金额
        $arr['refund_money_sum'] = $total_money_sum[0]['refund_money'];

        // 每天交易明细
        if ($is_system) {
            import('@.ORG.system_page');
        } else {
            import('@.ORG.merchant_page');
        }
        $count = M('Village_group_order')->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id,count(DISTINCT(o.start_uid)) as count")->join('as o left join '.C('DB_PREFIX').'village_group_pick_address as a on a.header_uid=o.start_uid' )->where($where)->select();
        $p = new Page($count[0]['count'], 10);
      
        // 社区 总成交额 总订单数 总佣金 总退款金额
        $sale_date = M('Village_group_order')->field("gh.name AS header_name,FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id,sum(o.total_money) as total_money,count(order_id) as counts,a.name,sum(o.refund_money) as refund_money,sum(o.spread_money) as spread_money")->join('as o left join '.C('DB_PREFIX').'village_group_pick_address as a on a.header_uid=o.start_uid left join '.C('DB_PREFIX').'village_group_header as gh on gh.id=o.start_uid' )->order("FROM_UNIXTIME(pay_time, '%Y-%m-%d') DESC")->group('o.start_uid')->where($where)->limit($p->firstRow, $p->listRows)->select();
        $arr['sale_date'] = $sale_date;
        $arr['pagebar'] = $p->show();
        $arr['total_money_sum'] = round($arr['total_money_sum'], 2);
        $arr['consume_money_sum'] = round($arr['consume_money_sum'], 2);
        $arr['refund_money_sum'] = round($arr['refund_money_sum'], 2);

        return $arr;
    }

    /*获取订单的商品详情*/
    public function get_good_details($order_id=null){
        if(empty($order_id)){
            return array();
        }
        $where=array();
        $where['a.order_id']=$order_id;
        $list=M('Village_group_order_detail')->alias("a")->join("left join ".C("DB_PREFIX")."village_group_goods as b on a.goods_id=b.goods_id")->where($where)->field('a.id,a.goods_id,b.name,a.num,b.image as goods_image,a.price')->select();
        $goods_list=array();
        $arr=array();
        $image_obj=new goods_image();
        foreach ($list as $k => $v) {
            $goods_id=$v['goods_id'];
            if(in_array($goods_id, $arr)){
                continue;
            }else{
                $arr[]=$goods_id;
                if(empty($v['goods_image'])){
                    $v['goods_image'] = $v['image'];
                }
                $v['goods_image']=$this->get_order_goods_img($v,$image_obj);
                $goods_list[]=$v;
            }
        }
        return (array)$goods_list;
    }

    //获取订单详情商品图片
    public function get_order_goods_img($info,$img_obj){
        $image = explode(';',$info['goods_image']);
        $info['goods_image'] = $image[0];
        if(!empty($info['goods_image']) && $info['goods_image'] != 'h'){
            if(stripos($info['goods_image'],'?')){
                $info['goods_image'] = $info['goods_image'];
            }
            if(stripos($info['goods_image'],',')===false){
                return thumb($info['goods_image'], 200, 200);
            }else{
                $img_str=$info['goods_image'];
            }
        }else{
            $img_str=D('Shop_goods')->where(array('goods_id'=>$info['goods_id']))->getfield('image');
            
            $data=array();
            $data['id']=$info['id'];
            $data['goods_image']=$goods_image;
            M('shop_order_detail')->data($data)->save();
        }
        if(empty($img_obj)){
            return '';
        }
        $img_arr=D('Shop_goods')->reg_good_img($img_str,$img_obj);
        //$goods_image=$img_arr['0']['url']['s_image']; 
        $goods_image=$img_arr['0']['url'];      
        
        return $goods_image;
    }

    /**
     * 根据订单id回滚库存
     * 2022-10-28
     */
    public function rollbackStockByOrderId($orderId)
    {
        $order = M('Village_group_order')->where(['order_id' => $orderId])->find();
        if (empty($order) || $order['status'] !=0) {
            return;
        }

        //下单后减库存，未支付订单回滚库存
        $goodsMod = M('Village_group_goods');
        $orderDetailMod = M('Village_group_order_detail');
        if ($order['stock_reduce_type'] == 1) {
            $thisOrderGoods = $orderDetailMod->where(['order_id' => $orderId])->field('goods_id,num')->select();
            foreach ($thisOrderGoods as $g) {
                $goodsMod->where(['goods_id' => $g['goods_id'], 'locking_num' => ['EGT', $g['num']]])->setDec('locking_num', $g['num']);
            }
        }
    }
}
?>