<?php
/*
*@create_date 2019-01-04
*@desc 平台订单信息模型
*/
class System_orderModel extends Model{
	public function add_order($type,$now_order=null,$order_id=null,$step=1){
		if(!is_array($now_order) || empty($order_id)){
			return false;
		}
		$data=array();
		$detail_data=array();
		$model=M('System_order_detail');
		if($type=='shop' || $type=='mall'){		//快店、商城
			$has = $this->where(['type'=>$type, 'order_id'=>$order_id])->find();
			if($has){
				$data=$now_order;
				$data['type']=$type;
				$data['order_id']=$order_id;
				$data['extends']=json_encode($now_order);
                $data['keywords'] = $now_order['keywords']; //搜索条件
				$system_id=$this->data($data)->where('id='.$has['id'])->save();
			}
			else{
				$data=$now_order;
				$data['type']=$type;
				$data['order_id']=$order_id;
				$data['extends']=json_encode($now_order);
				$system_id=$this->data($data)->add();
				if($system_id>0){
					$where=array();
					$where['order_id']=$order_id;
					$list=M('shop_order_detail')->where($where)->select();
					foreach ($list as $k => $v) {
						$arr=$v;
						unset($arr['id']);
						$arr['system_id']=$system_id;
						$arr['from_id']=$v['id'];
						$arr['mer_id']=$now_order['mer_id'];
						$arr['user_id']=$now_order['uid'];
						$arr['desc']=$v['spec'];
						$arr['keywords'] = $now_order['keywords']; //搜索条件
						$detail_data[]=$arr;
					}
					$model->addAll($detail_data);
				}
			}
		}elseif($type=='meal'){		//餐饮--ok
			$this->reg_meal_order($now_order,$order_id,'meal',$step);
		}elseif($type=='appoint'){	//预约--ok
			$this->reg_appoint_order($now_order,$order_id,'appoint');
		}elseif($type=='store' || $type=='cash'){	//到店业务
			$this->reg_store_order($now_order,$order_id,$type,$step);
		}elseif($type=='wxapp'){	//微信营销--ok
			$this->reg_wxapp_order($now_order,$order_id,$type,$step);
		}elseif($type=='group'){	//团购业务
			$this->reg_group_order($now_order,$order_id);
		}elseif($type=='village_group'){	//社区团购业务
			$this->reg_village_group_order($now_order,$order_id);
		}elseif($type=='service'){	//服务快派业务
            $this->reg_service_order($now_order,$order_id);
        }elseif($type=='merchant_user_recharge'){	//平台用户给其他用户充值商家会员卡赠送余额
            $this->reg_merchant_user_recharge_order($now_order,$order_id);
        }elseif($type=='mobile_recharge'){	//平台用户给其他用户充值商家会员卡赠送余额
            $this->reg_mobile_recharge_order($now_order,$order_id);
        }elseif($type=='gift'){
            $this->reg_gift_order($now_order,$order_id,$type);
        }

        $this->sendAppPushMessage($order_id,$type);
	}
    //格式化服务快派业务订单
    protected function reg_service_order($info,$order_id,$type='service'){
        $system_id=$this->get_system_id($type,$order_id);
        $data=array();
        $data['type']=$type;
        $data['order_id']=$order_id;
        $data['mer_id']=0;
        $data['keywords'] = $info['order_name']; //订单搜索关键词

        if(isset($info['uid'])){
            $data['uid']=$info['uid'];
        }
        $data['total_price']=$info['total_money'];
        $data['goods_price']=$info['total_money'];
        $data['num']=1;

        if(isset($info['status'])){
            $data['status']=$info['status'];
        }
        if(empty($system_id)){
            $data['create_time']=$_SERVER['REQUEST_TIME'];
            $data['last_time']=$_SERVER['REQUEST_TIME'];
            $data['extends']=json_encode($info);
            $system_id = $this->data($data)->add();
            // 添加团购商品详情

            $service_info = M('Service_user_publish')->where(array('publish_id' => $info['business_id']))->field('catgory_type,cid')->find();
            $service_desc = '';
            $service_detail = array();
            if ($service_info['catgory_type'] == 1) {
                // 服务快派-普通类型
                // 暂不处理
            } elseif ($service_info['catgory_type'] == 2) {
                // 服务快派-帮我买
                $service_detail = M('Service_user_publish_buy')->where(array('publish_id' => $info['business_id']))->find();
                $service_desc = $service_detail['goods_remarks'] . ', ';
            } elseif ($service_info['catgory_type'] == 3) {
                // 服务快派-帮我送
                $service_detail = M('Service_user_publish_give')->where(array('publish_id' => $info['business_id']))->find();
                $service_desc = L_("X1，物品重量(单位千克)：X2，物品价值（单位X3）：X4。",array("X1" => $service_detail['goods_catgory'],"X2" => $service_detail['weight'],"X3" => C('config.Currency_txt'),"X4" => $service_detail['price']));  

            }
            $service_name = M('service_category')->where(array('cid' => $service_info['cid']))->getField('cat_name');


            $system_order_detail_model=M('System_order_detail');
            $service_pic = explode(';',$service_detail['img']);
            $service_pic = $service_pic['0'];
            $detail = array(
                'system_id' => $system_id,
                'order_id' => $order_id,
                'mer_id' => 0,
                'goods_id' => $info['business_id'],
                'name' => $service_name?$service_name:'',
                'desc' => $service_desc?$service_desc:'',
                'goods_image' =>$service_pic?$service_pic:'',
                'price' =>$service_detail['total_price'],
                'num' =>1,
                'pay_price' =>$info['total_money'],
            );
            if(isset($info['store_id'])){
                $detail['store_id']=$info['store_id'];
            }
            if(isset($info['uid'])){
                $detail['user_id']=$info['uid'];
            }
            $system_order_detail_model->data($detail)->add();
        }else{
            $data['id']=$system_id;
            $data['last_time']=$_SERVER['REQUEST_TIME'];

            $arr=$this->where(array('id'=>$system_id))->getfield('extends',true);
            $arr=(array)json_decode($arr,true);
            $arr=array_merge($arr,$info);
            $data['extends']=json_decode($arr);
            $this->data($data)->save();
        }
    }

	//格式化团购业务订单
	protected function reg_group_order($info,$order_id,$type='group'){
		$system_id=$this->get_system_id($type,$order_id);
		$data=array();
		$data['type']=$type;
		$data['order_id']=$order_id;
		$data['real_orderid']=$info['real_orderid'];
		$data['mer_id']=$info['mer_id'];
		if(isset($info['store_id'])){
			$data['store_id']=$info['store_id'];	
		}
		if(isset($info['uid'])){
			$data['uid']=$info['uid'];
		}
		$data['total_price']=$info['total_money'];
		$data['goods_price']=intval($info['num'])*floatval($info['price']);
		$data['num']=$info['num'];
		// 商家余额支付
        if(isset($info['merchant_balance'])){
            $data['merchant_balance']=$info['merchant_balance'];
        }
        // 商家优惠券优惠金额
        if(isset($info['card_price'])){
            $data['card_price']=$info['card_price'];
        }
        // 	会员卡折扣
        if(isset($info['card_discount'])){
            $data['card_discount']=$info['card_discount'];
        }
        //  会员卡赠送余额
        if(isset($info['card_give_money'])){
            $data['card_give_money']=$info['card_give_money'];
        }
        //  平台余额支付
        if(isset($info['balance_pay'])){
            $data['balance_pay']=$info['balance_pay'];
        }
        //  平台优惠券优惠金额
        if(isset($info['coupon_price'])){
            $data['coupon_price']=$info['coupon_price'];
        }
        //  在线支付的钱
        if(isset($info['payment_money'])){
            $data['payment_money']=$info['payment_money'];
        }
        //  积分使用数量
        if(isset($info['score_used_count'])){
            $data['score_used_count']=$info['score_used_count'];
        }
        // 	积分兑现的金额
        if(isset($info['score_deducte'])){
            $data['score_deducte']=$info['score_deducte'];
        }
        // vip 优惠价格
        if(isset($info['vip_discount_money'])){
            $data['vip_discount_money']=$info['vip_discount_money'];
        }

		if(isset($info['status'])){
			$data['status']=$info['status'];
		}
		if(empty($system_id)){
			$data['create_time']=$_SERVER['REQUEST_TIME'];
			$data['last_time']=$_SERVER['REQUEST_TIME'];
			$data['extends']=json_encode($info);
			$data['keywords'] = $info['keywords']; //订单搜索关键词
            $system_id = $this->data($data)->add();
            // 添加团购商品详情
            $system_order_detail_model=M('System_order_detail');
            $group = M('Group')->where(array('group_id' => $info['group_id']))->field('name')->find();
            $tmp_pic = explode(';',$group['pic']);
            $image_tmp = explode(',',$tmp_pic[0]);
            $list_pic = '/upload/group/'.$image_tmp[0].'/s_'.$image_tmp['1'];
            $detail = array(
                'system_id' => $system_id,
                'order_id' => $order_id,
                'mer_id' => $info['mer_id'],
                'goods_id' => $info['group_id'],
                'name' => $group['name'],
                'desc' => $group['intro'],
                'goods_image' =>$list_pic,
                'price' =>$group['price'],
                'num' =>$info['num'],
                'pay_price' =>$info['price'],
            );
            if(isset($info['store_id'])){
                $detail['store_id']=$info['store_id'];
            }
            if(isset($info['uid'])){
                $detail['user_id']=$info['uid'];
            }
            $system_order_detail_model->data($detail)->add();
		}else{
			$data['id']=$system_id;
			$data['last_time']=$_SERVER['REQUEST_TIME'];
            $data['keywords'] = $info['keywords']; //订单搜索关键词
			
			$arr=$this->where(array('id'=>$system_id))->getfield('extends',true);
			$arr=(array)json_decode($arr,true);
			$arr=array_merge($arr,$info);
			$data['extends']=json_decode($arr);
			$this->data($data)->save();
		}
	}

	//团购支付前信息记录
	public function reg_group_pay_order($order_id,$info,$type='group'){
		$system_id=$this->get_system_id($type,$order_id);
		if(empty($system_id)){
			return false;
		}
		$data=array();
		$data['id']=$system_id;
        // 商家余额支付
        if(isset($info['merchant_balance'])){
            $data['merchant_balance']=$info['merchant_balance'];
        }
        // 商家优惠券优惠金额
        if(isset($info['card_price'])){
            $data['card_price']=$info['card_price'];
        }
        // 	会员卡折扣
        if(isset($info['card_discount'])){
            $data['card_discount']=$info['card_discount'];
        }
        //  会员卡赠送余额
        if(isset($info['card_give_money'])){
            $data['card_give_money']=$info['card_give_money'];
        }
        //  平台余额支付
        if(isset($info['balance_pay'])){
            $data['balance_pay']=$info['balance_pay'];
        }
        //  平台优惠券优惠金额
        if(isset($info['coupon_price'])){
            $data['coupon_price']=$info['coupon_price'];
        }
        //  在线支付的钱
        if(isset($info['payment_money'])){
            $data['payment_money']=$info['payment_money'];
        }
        //  积分使用数量
        if(isset($info['score_used_count'])){
            $data['score_used_count']=$info['score_used_count'];
        }
        // 	积分兑现的金额
        if(isset($info['score_deducte'])){
            $data['score_deducte']=$info['score_deducte'];
        }
        // vip 优惠价格
        if(isset($info['vip_discount_money'])){
            $data['vip_discount_money']=$info['vip_discount_money'];
        }
		if(isset($info['status'])){
			$data['status']=$info['status'];
		}
		$data['last_time']=$_SERVER['REQUEST_TIME'];
		$extends=$this->where(array('id'=>$system_id))->getfield('extends',true);
		$arr=(array)json_decode($extends,true);
		$arr=array_merge($arr,$info);
		$data['extends']=json_encode($arr);
		$this->data($data)->save();
	}	

	//格式化微信营销订单信息
	protected function reg_wxapp_order($info,$order_id,$type='wxapp',$step=1){
		$system_id=$this->get_system_id($type,$order_id);
		if($step!=1 && empty($system_id)){
			return false;
		}
		$data=array();
		if($step==1){
			$data['type']=$type;
			$data['order_id']=$order_id;
			$data['real_orderid']=$info['wxapp_order_id'];
			$data['mer_id']=$info['mer_id'];
			$data['num']=$info['order_num'];
			$data['uid']=$info['uid'];
			$data['total_price']=$info['money'];
			$data['create_time']=$info['add_time'];
		}elseif($step==2){
			$data['merchant_balance']=$info['merchant_balance'];
			$data['card_give_money']=$info['card_give_money'];
			$data['card_discount']=$info['card_discount'];
			$data['balance_pay']=$info['balance_pay'];
			$data['card_price']=$info['card_price'];
		}
		if(isset($info['status'])){
			$data['status']=$info['status'];
		}
		$data['last_time']=$_SERVER['REQUEST_TIME'];
		if($system_id>0){
			$extends=$this->where(array('id'=>$system_id))->getfield('extends');
			$arr=(array)json_decode($extends,true);
			$arr=array_merge($arr,$info);
			$data['extends']=json_encode($arr);
			$data['id']=$system_id;
			$this->data($data)->save();	
		}else{
			$data['extends']=json_encode($info);
			$this->data($data)->add();
		}
	}

	//格式化餐饮订单信息 step 1-添加，2-编辑
	protected function reg_meal_order($info,$order_id,$type = 'meal',$step = 1){
		$system_id = $this->get_system_id($type,$order_id);
		if($step != 1 && empty($system_id)){
			return false;
		}

		$data=array();
		$data['type'] = $type;
		$data['order_id'] = $order_id;
		$data['real_orderid'] = $info['real_orderid'];
		$data['mer_id'] = $info['mer_id'];
		$data['store_id'] = $info['store_id'];
		$data['uid'] = $info['uid']?$info['uid']:0;
		$data['total_price'] = $info['total_price']; // 总价
		$data['goods_price'] = $info['total_price']; // 商品总价
		$data['discount_price'] = $info['price']; // 折扣后的总价
		$data['price'] = $info['price']; // 实际要支付的金额

		$data['num'] = $info['goods_num']; // 商品总数

        $data['keywords'] = $info['keywords'];  //搜索条件

		$data['status'] = $info['status']; // 订单状态（当前业务下的订单状态）
		switch ($data['status']) {
			case '0': // 未支付
				$data['paid'] = 0; // 支付状态（0：未支付，1：已支付）
				break;
			case '1': // 预约成功
				$data['paid'] = 1; // 支付状态（0：未支付，1：已支付）
				$data['pay_time'] = $info['pay_time'];
				break;
			case '2': // 店员确认
				break;
			case '3': // 买单完成
				$data['paid'] = 1; // 支付状态（0：未支付，1：已支付）
				$data['system_status'] = 2; // 系统状态，1-已发货，2-已完成，3-已评价，4-已退款，5-已取消,6-已完成（不用评价）',
				$data['use_time'] = $info['last_time'];
				$data['pay_time'] = $info['pay_time'];
				break;
			case '4': // 已评价
				$data['system_status'] = 3; // 系统状态，1-已发货，2-已完成，3-已评价，4-已退款，5-已取消
				break;
			case '5': // 已取消
				$data['system_status'] = 5; // 系统状态，1-已发货，2-已完成，3-已评价，4-已退款，5-已取消
				break;
		}

		$info['merchant_balance'] && $data['merchant_balance'] = $info['merchant_balance'];//商家余额
		$info['merchant_reduce'] && $data['merchant_reduce'] = $info['merchant_reduce'];//店铺优惠 商家优惠的金额
		$info['card_price'] && $data['card_price'] = $info['card_price'];//店铺优惠券金额
		$info['card_discount'] && $data['card_discount'] = $info['card_discount'];//会员卡折扣
		$info['merchant_balance_give'] && $data['card_give_money'] = $info['merchant_balance_give'];//商家赠送余额 会员卡赠送余额
		$info['balance_pay'] && $data['balance_pay'] = $info['balance_pay'];//平台余额
		$info['balance_reduce'] && $data['balance_reduce'] = $info['balance_reduce'];//平台优惠
		$info['coupon_price'] && $data['coupon_price'] = $info['coupon_price'];//平台优惠券金额
		$info['pay_money'] && $data['payment_money'] = $info['pay_money'];//在线支付的金额
		$info['score_used_count'] && $data['score_used_count'] = $info['score_used_count'];//使用积分数
		$info['score_deducte'] && $data['score_deducte'] = $info['score_deducte'];//积分抵用的金额
		$info['online_pay'] && $data['pay_type'] = $info['online_pay'];//支付类型
		$info['offline_pay'] && $data['offline_pay'] = $info['offline_pay'];//线下支付方式
		$info['plat_discount'] && $data['plat_discount'] = $info['plat_discount'];//平台的优惠比例
		$info['plat_discount_price'] && $data['plat_discount_price'] = $info['plat_discount_price'];//平台折扣优惠的金额
		$info['vip_discount_money'] && $data['vip_discount_money'] = $data['vip_level_reduce_money'] = $info['vip_discount_money'];//会员等级优惠金额
		$info['no_discount_money'] && $data['no_discount_money'] =  $info['no_discount_money'];//不可优惠金额



	  // `platform_merchant` decimal(10,2) NOT NULL COMMENT '平台优惠中商家补贴的总和统计',
	  // `platform_plat` decimal(10,2) NOT NULL COMMENT '平台优惠中平台补贴的总和统计',
	  // `can_discount_money` decimal(10,2) NOT NULL COMMENT '可用商家优惠券的总金额',
	  // `merchant_reduce_total` decimal(8,2) NOT NULL COMMENT '商家所有优惠的金额',
	  // `merchant_discount_price` decimal(8,2) NOT NULL COMMENT '商家优惠金额',
	  // `use_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '使用时间',


		$data['last_time'] = $_SERVER['REQUEST_TIME'];
		$data['extends'] = json_encode($info);
		if($system_id){
			$this->where(array('id'=>$system_id))->data($data)->save();
		}else{
			$data['create_time'] = $_SERVER['REQUEST_TIME'];
			$system_id = $this->data($data)->add();
		}

		if ($info['info']) { // 已点菜品
			$this->add_meal_goods($order_id,$info['mer_id']);
		}
		return true;
	}

	//记录餐饮的商品信息
	public function add_meal_goods($order_id,$mer_id){
		$system_id=$this->get_system_id('meal',$order_id);
		if(empty($system_id)){
			return false;
		}
		$where=array();
		$where['order_id']=$order_id;
		$list=M('foodshop_order_detail')->where($where)->select();
		$count=0;		
		foreach ($list as $k => $v) {
			$count+=$v['num'];
		}
		$data=array();
		$data['id']=$system_id;
		$data['num']=$count;
		$data['last_time']=$_SERVER['REQUEST_TIME'];
		$res=$this->data($data)->save();
		$model=M('System_order_detail');
		$old_arr=$model->where(array('system_id'=>$system_id))->getfield('from_id,id',true);
		$old_arr=(array)$old_arr;
		if(!empty($old_arr)){
			$old_from_arr=array_keys($old_arr);
		}
		if($res){
			$data_all=array();
			$new_ids=array();
			foreach ($list as $k => $v) {
				$new_ids[] = $v['id'];
				$arr=array();
				$arr['system_id']=$system_id;
				$arr['from_id']=$v['id'];
				$arr['order_id']=$v['order_id'];
				$arr['mer_id']=$mer_id;
				$arr['store_id']=$v['store_id'];
				$arr['user_id']=$v['uid'];
				$arr['goods_id']=$v['goods_id'];
				$arr['name']=$v['name'];
				$arr['desc']=$v['spec'];
				$arr['price']=$v['price'];
				$arr['num']=$v['num'];
				if(!empty($old_from_arr) && in_array($v['id'], $old_from_arr)){
					$arr['id']=$old_arr[$v['id']];
					$model->data($arr)->save();
				}else{
					$data_all[]=$arr;	
				}
			}
			if(!empty($data_all)){
				$model->addAll($data_all);
			}
			$delete_ids = array_diff($old_from_arr,$new_ids);
			if ($delete_ids) {
				$model->where(array('from_id'=>array('in',$delete_ids)))->delete();
			}
		}
	}

	//格式化预约订单信息
	protected function reg_appoint_order($info,$order_id,$type,$step=1){
		$system_id=$this->get_system_id($type,$order_id);
		$data=array();
		$data['type']=$type;
		$data['order_id']=$order_id;
		$data['mer_id']=$info['mer_id'];
		$data['store_id']=$info['store_id']?$info['store_id']:0;
		$data['uid']=$info['uid'];
		$data['keywords'] = $info['keywords'] ? $info['keywords'] : '';
		if($info['product_id']>0){
			$data['total_price']=$info['product_price'];
		}else{
			$data['total_price']=$info['appoint_price'];
		}
		$data['total_price']+=$info['extra_price'];

        if($info['product_id']>0){
            $data['goods_price']=$info['product_payment_price'];
        }else{
            $data['goods_price']=$info['payment_money'];
        }
        if(empty($data['total_price'])){
            $data['total_price'] = $data['goods_price'];
        }
		if(isset($info['pay_money']) || isset($info['user_pay_money'])){
			$data['price'] = intval($info['pay_money']) + intval($info['user_pay_money']);
		}
		if(isset($info['status'])){
			$data['status']=$info['status'];
		}
        if($info['product_id']>0){
            // 	积分使用数量
            if(isset($info['product_score_used_count'])){
                $data['score_used_count']=$info['product_score_used_count'];
            }
            // 	积分抵扣金钱
            if(isset($info['product_score_deducte'])){
                $data['score_deducte']=$info['product_score_deducte'];
            }
            // 	使用商家余额
            if(isset($info['product_merchant_balance'])){
                $data['merchant_balance']=$info['product_merchant_balance'];
            }
            // 	商家优惠券金额
            if(isset($info['product_card_price'])){
                $data['card_price']=$info['product_card_price'];
            }
            // 	平台优惠券金额
            if(isset($info['product_coupon_price'])){
                $data['coupon_price']=$info['product_coupon_price'];
            }
            // 	平台余额支付
            if(isset($info['product_balance_pay'])){
                $data['balance_pay']=$info['product_balance_pay'];
            }
            // 	实际支付金额
            if(isset($info['pay_money'])){
                $data['price']=$info['pay_money'];
            }
            // 	会员卡赠送余额
            if(isset($info['product_card_give_money'])){
                $data['card_give_money']=$info['product_card_give_money'];
            }
            // 	会员卡折扣
            if(isset($info['product_card_discount'])){
                $data['card_discount']=$info['product_card_discount'];
            }
        }else{
            // 	积分使用数量
            if(isset($info['score_used_count'])){
                $data['score_used_count']=$info['score_used_count'];
            }
            // 	积分抵扣金钱
            if(isset($info['score_deducte'])){
                $data['score_deducte']=$info['score_deducte'];
            }
            // 	使用商家余额
            if(isset($info['merchant_balance'])){
                $data['merchant_balance']=$info['merchant_balance'];
            }
            // 	商家优惠券金额
            if(isset($info['card_price'])){
                $data['card_price']=$info['card_price'];
            }
            // 	平台优惠券金额
            if(isset($info['coupon_price'])){
                $data['coupon_price']=$info['coupon_price'];
            }
            // 	平台余额支付
            if(isset($info['balance_pay'])){
                $data['balance_pay']=$info['balance_pay'];
            }
            // 	实际支付金额
            if(isset($info['pay_money'])){
                $data['price']=$info['pay_money'];
            }
            // 	会员卡赠送余额
            if(isset($info['card_give_money'])){
                $data['card_give_money']=$info['card_give_money'];
            }
            // 	会员卡折扣
            if(isset($info['card_discount'])){
                $data['card_discount']=$info['card_discount'];
            }
        }
		$data['num']=1;
		$data['last_time']=$_SERVER['REQUEST_TIME'];		
		if(empty($system_id)){
			$data['extends']=json_encode($info);

			$data['create_time']=$_SERVER['REQUEST_TIME'];
			$system_id = $this->data($data)->add();
            // 添加预约商品详情
            $system_order_detail_model=M('System_order_detail');
            $appoint = M('Appoint')->where(array('appoint_id' => $info['appoint_id']))->field('appoint_name,appoint_pic_content,pic,appoint_price')->find();
            $tmp_pic = explode(';',$appoint['pic']);
            $image_tmp = explode(',',$tmp_pic[0]);
            $list_pic = '/upload/appoint/'.$image_tmp[0].'/s_'.$image_tmp['1'];
            $detail = array(
                'system_id' => $system_id,
                'order_id' => $order_id,
                'mer_id' => $info['mer_id']?$info['mer_id']:0,
                'goods_id' => $info['appoint_id']?$info['appoint_id']:0,
                'name' => $appoint['appoint_name']?$appoint['appoint_name']:'',
                'desc' => $appoint['appoint_pic_content']?$appoint['appoint_pic_content']:'',
                'goods_image' =>$list_pic?$list_pic:'',
                'price' =>$appoint['appoint_price']?$appoint['appoint_price']:0,
                'num' =>$info['num']?$info['num']:1,
                'pay_price' =>$info['price']?$info['price']:0,
            );
            if(isset($info['store_id'])){
                $detail['store_id']=$info['store_id'];
            }
            if(isset($info['uid'])){
                $detail['user_id']=$info['uid'];
            }
            $system_order_detail_model->data($detail)->add();
		}else{
			$data['extends']=json_encode($info);
			$data['id']=$system_id;
			$this->data($data)->save();
		}
	}

    //格式化到店业务订单信息
	protected function reg_store_order($info,$order_id,$type,$step=1){
		$system_id=$this->get_system_id($type,$order_id);
		if($step==2 && empty($system_id)){
			return false;
		}
		$data=array();
		$info['status'] && $data['status']=$info['status'];
		$info['merchant_balance'] && $data['merchant_balance'] = $info['merchant_balance'];
		$info['card_give_money'] && $data['card_give_money'] = $info['card_give_money'];
		$info['card_discount'] && $data['card_discount'] = $info['card_discount'];
		$info['balance_pay'] && $data['balance_pay'] = $info['balance_pay'];
		$info['score_used_count'] && $data['score_used_count'] = $info['score_used_count'];
		$info['score_deducte'] && $data['score_deducte'] = $info['score_deducte'];
		$info['card_price'] && $data['card_price'] = $info['card_price'];
		$info['payment_money'] && $data['payment_money'] = $info['payment_money'];
		$info['discount_price'] && $data['merchant_reduce'] = $info['discount_price'];
		$info['no_discount_money'] && $data['no_discount_money'] = $info['no_discount_money'];
		$info['offline_pay'] && $data['offline_pay'] = $info['offline_pay'];
		$info['vip_discount_money'] && $data['vip_level_reduce_money'] = $data['vip_discount_money'] = $info['vip_discount_money'];
		$info['pay_type'] && $data['pay_type'] = $info['pay_type'];
		$info['pay_time'] && $data['pay_time'] = $info['pay_time'];

		$data['discount_price'] = $info['price'];
		$data['uid'] = intval($info['uid']);
		$data['total_price'] = $info['total_price'];
		$data['price'] = $info['price'];
		$data['paid'] = $info['paid'];
		$data['last_time']=$_SERVER['REQUEST_TIME'];

		$store_info = M('Merchant_store')->field('name')->where(['store_id'=>$info['store_id']])->find();
		$data['keywords'] = trim($store_info['name'].','.$info['name'], ','); // 订单搜索关键字

		if ($data['paid'] == 1) {
			$data['system_status']=6;
			$data['use_time']=$_SERVER['REQUEST_TIME'];
		}

		if($step==1&&!$system_id){
			$data['type']=$type;
			$data['order_id']=$order_id;
			$data['real_orderid']=$info['orderid'];
			$data['mer_id']=$info['mer_id'];
			$data['store_id']=$info['store_id'];
			$data['num']=1;
			
			$data['create_time']=$_SERVER['REQUEST_TIME'];
			$data['extends']=json_encode($info);
			$this->data($data)->add();
		}elseif($step==2){
			$extends=$this->where(array('id'=>$system_id))->getfield('extends');
			$arr=(array)json_decode($extends,true);	
			$arr=array_merge($arr,$extends);
			$data['extends']=json_encode($arr);
			$data['id']=$system_id;

			$this->data($data)->save();
		}
	}

	//更新订单信息
	//@param $status 1-已支付；2-已完成
	public function save_reg_order_info($type='',$order_id='',$info='',$status=1){
		if(empty($order_id)){
			return false;
		}
		$where=array();
		$where['order_id']=$order_id;
		$data=array();
		$system_id=$this->get_system_id($type,$order_id);
		if(empty($system_id)){
			return false;
		}
		$data['id']=$system_id;
		if($status==1){
			$data['paid']=1;
			if($type=='store' || $type=='cash'){	//到店
				$data['pay_time']=$info['pay_time'];
				$data['payment_money']=$info['payment_money'];
				$data['pay_type']=$info['pay_type'];
			}elseif($type=='meal'){
				$data['pay_time']=$info['pay_time'];
				$data['price']=$info['price'];
				$data['pay_type']=$info['pay_type'];
				$data['total_price']=$info['total_price'];
				if(isset($info['plat_discount'])){
					$data['plat_discount']=$info['plat_discount'];
				}
				if($info['plat_discount_price']>0){
					$data['plat_discount_price']=$info['plat_discount_price'];
				}
			}elseif($type=='wxapp'){
				$data['pay_time']=$info['pay_time'];
				$data['pay_type']=$info['pay_type'];
				$data['payment_money']=$info['payment_money'];
			}elseif($type=='appoint'){
				$data['price'] = intval($info['pay_money']) + intval($info['user_pay_money']);
				$data['pay_time']=$_SERVER['REQUEST_TIME'];
				if(isset($info['pay_type'])){
					$data['pay_type']=$info['pay_type'];
				}
			}elseif($type=='group'){
                $data['pay_time']=$info['pay_time'];
                $data['pay_money']=$info['pay_money'];
                $data['pay_type']=$info['pay_type'];
            }elseif($type=='service'){
                $data['pay_time']=$info['pay_time'];
                $data['pay_money']=$info['pay_money'];
                $data['pay_type']=$info['pay_type'];
            }
		}elseif($status==2){
			$data['system_status']=2;
			$data['use_time']=$_SERVER['REQUEST_TIME'];
		}
		if(isset($info['status'])){
			$data['status']=$info['status'];
		}
		$data['last_time']=$_SERVER['REQUEST_TIME'];
		/*获取之前的信息*/	
		$extends=$this->where(array('id'=>$system_id))->getfield('extends');
		$arr=(array)json_decode($extends,true);
		$arr=array_merge($arr,$info);
		$data['extends']=json_encode($arr);
		$this->data($data)->save();
		$this->sendAppPushMessage($order_id,$type);
	}

	//更新订单状态
	public function save_order_info($type,$order_id,$data=null,$add_sales=0){
		if(!is_array($data)){
			return false;
		}
		$system_id=$this->get_system_id($type,$order_id);
		$data['id']=$system_id;
		if(isset($data['status'])){
			if($type=='shop' || $type=='mall'){
				$data['system_status']=$data['status'];
			}
		}
		if(isset($data['system_status']) && in_array($data['system_status'],[4,5])){
		    //退积分
            D('User_score_list')->back_score($type,$order_id);
        }
		$res=$this->data($data)->save();
		if($res){
            $this->sendAppPushMessage($order_id,$type);
			return array('error'=>0,'msg'=>L_('更新成功！'));
		}else{
			return array('error'=>1,'msg'=>L_('更新失败！'));
		}
	}

	/*更新订单状态*/
	public function save_order_status($type,$order_id,$status=0, $order_status=''){
		if(empty($type) || empty($order_id)){
			return false;
		}
		$system_id=$this->get_system_id($type,$order_id);
		if(empty($system_id)){
			return false;
		}
		$data=array();
		$data['id']=$system_id;
		$data['last_time']=$_SERVER['REQUEST_TIME'];
		$data['system_status']=$status;
        if ($order_status!='') {
            $data['status']=$order_status;
        }
		if($status==2){
			$data['use_time']=$_SERVER['REQUEST_TIME'];
		}

		if($type == 'group' && $status == 4){
			// 团购退款 回滚库存
			invoke_v20_service('\app\group\model\service\order\GroupOrderService/rollBackStock',['orderId'=>$order_id]);
		}
        if(in_array($order_status,[4,5])){
            //退积分
            D('User_score_list')->back_score($type,$order_id);
        }
		$res=$this->data($data)->save();

        //记录保存赠品订单日志
        if ($res!==false) {
            $this->sendAppPushMessage($order_id,$type);
            $fdumpArr = array(
                'order_id' => $order_id,
                'detail_data' => $data,
            );
            fdump_sql($fdumpArr, 'system_gift_order_save');
        }
		return $res;
	}

	/*获取不同类型订单的平台订单号*/
	private function get_system_id($type,$order_id){
		$where=array();
		$where['type']=$type;
		$where['order_id']=$order_id;
        //兼容一下type=shop有可能是商城订单。所以当type=shop的时候筛选shop和mall两种
        if ($type == 'shop') {
            $where['type'] = ['IN', ['shop', 'mall']];
        }
		$system_id=$this->where($where)->getfield('id');
		return $system_id;
	}

	/*
	 * 社区团购订单
	 * */
	protected function reg_village_group_order($info,$order_id,$type='village_group'){
		$system_id=$this->get_system_id($type,$order_id);
		$data=array();
		$data['type']=$type;
		$data['order_id']=$order_id;
		$data['real_orderid']=$info['real_orderid'];
		$data['mer_id']=0;

		if(isset($info['uid'])){
			$data['uid']=$info['uid'];
		}
		$data['total_price']=$info['total_money'];
		$data['goods_price']=$info['total_money'];
		$data['price']=$info['total_money'];
		$data['num']=$info['num'];
		$data['keywords'] =  $info['keywords'];

		if(isset($info['status'])){
			$data['status']=$info['status'];
		}
		if(empty($system_id)){
			$data['create_time']=$_SERVER['REQUEST_TIME'];
			$data['last_time']=$_SERVER['REQUEST_TIME'];
			$data['extends']=json_encode($info);
			$this->data($data)->add();
		}else{
			$data['id']=$system_id;
			$data['last_time']=$_SERVER['REQUEST_TIME'];

			$arr=$this->where(array('id'=>$system_id))->getfield('extends',true);
			$arr=(array)json_decode($arr,true);
			$arr=array_merge($arr,$info);
			$data['extends']=json_decode($arr);
			$this->data($data)->save();
		}
	}

	/*
	 * 社区保存订单详情
	 * */
	public function add_village_group_goods($order_id){
		$system_id=$this->get_system_id('village_group',$order_id);

		if(empty($system_id)){
			return false;
		}
		$where=array();
		$where['order_id']=$order_id;
		$now_order=D('Village_group')->get_order_detail($order_id);
		$list = $now_order['goods_detail'];

		$count=0;
		foreach ($list as $k => $v) {
			$count+=$v['num'];
		}
		$data=array();
		$data['id']=$system_id;
		$data['num']=$count;
		$data['last_time']=$_SERVER['REQUEST_TIME'];
		$res=$this->data($data)->save();
		$model=M('System_order_detail');
		$old_arr=$model->where(array('system_id'=>$system_id))->getField('from_id,id',true);
		$old_arr=(array)$old_arr;

		if(!empty($old_arr)){
			$old_from_arr=array_keys($old_arr);
		}

		$data_all=array();
		foreach ($list as $k => $v) {
			$arr=array();
			$arr['system_id']=$system_id;
			$arr['from_id']=$v['id'];
			$arr['order_id']=$order_id;
			$arr['mer_id']=0;
			$arr['store_id']=0;
			$arr['user_id']=$now_order['uid'];
			$arr['goods_id']=$v['goods_id'];
			$arr['name']=$v['name'];
			$arr['desc']=$v['des'];
			$arr['price']=$v['price'];
			$arr['pay_price']=$v['price'];
			$arr['num']=$v['num'];
			if(!empty($old_from_arr) && in_array($v['id'], $old_from_arr)){
				$arr['id']=$old_arr[$v['id']];
				$model->data($arr)->save();
			}else{
				$data_all[]=$arr;
			}
		}

		if(!empty($data_all)){
			$model->addAll($data_all);
		}
	}

	/*
	 * 平台用户给其他用户充值商家会员卡赠送余额
	 * */
	protected function reg_merchant_user_recharge_order($info,$order_id,$type='merchant_user_recharge'){
		$system_id=$this->get_system_id($type,$order_id);
		$data=array();
		$data['type']=$type;
		$data['order_id']=$order_id;
		$data['real_orderid']=$info['orderid'];
		$data['mer_id']=$info['mer_id'];

		if(isset($info['uid'])){
			$data['uid']=$info['uid'];
		}
		$data['price']=$info['total_price'];

		if(empty($system_id)){
			$data['create_time']=$_SERVER['REQUEST_TIME'];
			$data['last_time']=$_SERVER['REQUEST_TIME'];
			$data['extends']=json_encode($info);
			$this->data($data)->add();
		}else{
			$data['id']=$system_id;
			$data['last_time']=$_SERVER['REQUEST_TIME'];

			$arr=$this->where(array('id'=>$system_id))->getfield('extends',true);
			$arr=(array)json_decode($arr,true);
			$arr=array_merge($arr,$info);
			$data['extends']=json_decode($arr);
			$this->data($data)->save();
		}
	}

	/**
	 * [reg_mobile_recharge_order 用户充值订单]
	 * @return [type] [description]
	 */
	protected function reg_mobile_recharge_order($info,$order_id,$type='mobile_recharge'){
		$system_id=$this->get_system_id($type,$order_id);
		$data=array();
		$data['type']=$type;
		$data['order_id']=$order_id;
		$data['real_orderid']='';
		$data['mer_id']=0;
		$data['keywords'] = $info['order_name'];

		if(isset($info['uid'])){
			$data['uid']=$info['uid'];
		}
		$data['price']=$info['total_money'];

		if(empty($system_id)){
			$data['create_time']=$_SERVER['REQUEST_TIME'];
			$data['last_time']=$_SERVER['REQUEST_TIME'];
			$data['extends']=json_encode($info);
			$this->data($data)->add();
		}else{
			$data['id']=$system_id;
			$data['last_time']=$_SERVER['REQUEST_TIME'];

			$arr=$this->where(array('id'=>$system_id))->getfield('extends',true);
			$arr=(array)json_decode($arr,true);
			$arr=array_merge($arr,$info);
			$data['extends']=json_decode($arr);
			$this->data($data)->save();
		}
	}

	public function reg_gift_order($info,$order_id,$type){
		$data=array();
		$data['type']=$type;
		$data['order_id']=$order_id;
		$data['real_orderid']='';
		$data['mer_id']=$info['mer_id']?:0;
		$data['store_id']=0;
		$data['uid']=$info['uid'];
		$data['total_price']=$info['total_price']?:0;
		$data['goods_price']=$info['total_price']?:0;
		$data['discount_price']=$info['total_price']?:0;
		$data['price']=$info['payment_money']?:0;
		$data['num']=$info['num']??1;
		$data['status']=$info['status']?:0;
		$data['paid']=1;
		$data['system_status']=0;
		$data['pay_type']=$info['pay_type']?:'';
		$data['balance_pay']=$info['balance_pay']?:0;
		$data['score_used_count']=$info['score_used_count']?:0;
		$data['score_deducte']=$info['score_deducte']?:0;
		$data['create_time']=$_SERVER['REQUEST_TIME'];
		$data['pay_time']=$info['pay_time']?:0;
		$data['last_time']=$info['last_time']?:0;
		$data['extends']=json_encode($info);

		$data['keywords'] = $info['order_name'];

		$this->data($data)->add();
	}

    /**
     * 发送极光推送
     * @param $orderId
     * @param $type
     * @date: 2021/07/09
     */
    public function sendAppPushMessage($orderId, $type)
    {
        if ($type == 'dining') {
            $systemOrder['type'] = $type;
        } else {
            $systemId = $this->get_system_id($type, $orderId);
            if ($systemId < 1) {
                return false;
            }
            $systemOrder = $this->where(['id' => $systemId])->find();
        }

        import('@.ORG.Apppush');
        $apppush = new Apppush();

        $isSend = false;
        if ($systemOrder['type'] == 'appoint') {
            //预约
            $orderInfo = D('Appoint_order')->where(['order_id' => $orderId])->find();
            if ($orderInfo['paid'] > 0) {
                $isSend = true;
            }
        } else if ($systemOrder['type'] == 'village_group') {
            //社区团购
            $orderInfo = D('Village_group_order')->where(['order_id' => $orderId])->find();
            if ($orderInfo['pay_time'] > 0 && $orderInfo['status'] > 0) {
                $isSend = true;
            }
        } else if ($systemOrder['type'] == 'gift') {
            //积分商城
            $orderInfo = D('Gift_order')->where(['order_id' => $orderId])->find();
            if ($orderInfo['paid'] > 0) {
                $isSend = true;
            }
        } else if ($systemOrder['type'] == 'store') {
            //线下零售
            $orderInfo = D('Store_order')->where(['order_id' => $orderId])->find();
            if ($orderInfo['paid'] > 0) {
                $isSend = true;
            }
        } else if ($systemOrder['type'] == 'mall3') {
            //新版商城
            $orderInfo = D('Mall_order')->where(['order_id' => $orderId])->find();
            if ($orderInfo['pay_time'] > 0) {
                $isSend = true;
            }
        } else if ($systemOrder['type'] == 'dining') {
            //餐饮2.0
            $orderInfo = D('Dining_order')->where(['order_id' => $orderId])->find();
            if ($orderInfo['pay_time'] > 0) {
                $isSend = true;
            }
        }

        if ($isSend) {
            $apppush->send($orderInfo, $systemOrder['type']);
        }
    }

    // 导出商家对账订单信息
    public function exportBillOrder(&$objProps, &$objExcel, $param){
        set_time_limit(0);
        $param['type'] = 'merchant_bill';
        $title = L_('商家对账');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);


        // 查询数据
        $param['pageSize'] = 1000;
		$data = $this->getMerchantBillList($param);
		fdump($data,'eeeeeeeee',1);

        $length = ceil($data['count'] / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千条订单信息', ($i + 1)));
            $objActSheet = $objExcel->getActiveSheet();

            // 设置表头
            $title_arr = array(
                array(40, L_('店铺名称')),
                array(40, L_('订单号')),
                array(20, L_('订单类型')),
                array(20, L_('在线支付金额')),
                array(20, L_('平台支付金额')),
                array(20, L_('商家会员卡余额支付金额')),
                array(20, L_('平台优惠券抵现')),
                array(20, L_('商家优惠券抵现')),
                array(20, C('config.score_name').L_('抵现')),
                array(20, L_('店员收取金额')),
                array(20, L_('下单时间')),
                array(20, L_('支付时间')),
                array(20, L_('订单状态')),
                array(20, L_('对账状态')),
            );

            $lastColumn = D('Order')->setPHPExcelHeader($objExcel, $title_arr, 1, 15);

            $index = 1;
            if($i != 0){
                $_GET['page'] = $i;
                $data = $this->getMerchantBillList($param);
				fdump($data,'eeeeeeeee',1);
            }

            foreach ($data['order_list'] as $key  => $value) {   
                $index++;

            	$column = 0;
            	$objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['store_name']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['real_orderid']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['business_name']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['payment_money']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['balance_pay']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['merchant_balance']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['coupon_price']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['card_price']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['score_deducte']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['total_price']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, date('Y-m-d H:i:s', $value['create_time']));
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');

                $status = '';
                if($value['paid'] == 0){
                	$status = '未支付';
                }elseif($value['system_status'] == 4){
                	$status = '已退款';
                }else{
                	$status = '已支付';
                }
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $status);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, ($value['is_bill'] == '0' ? '未对账' : '已对账'));
            }
        }
        sleep(2);
    }

    // 对账订单列表
    public function getMerchantBillList($param){
    	$return = [
    		'order_list' => [],
    		'pagebar' => ''
    	];

		// 查询时间
		if($param['search_month']){
			$month = $_GET['search_month'];
		}

		if($param['mer_id']){
			$mer_id = $_GET['mer_id'];
		}

		if(empty($month) || !$mer_id){
			return $return;
		}

		// 商家id
		$condition['o.mer_id'] = $mer_id;

		// 时间
		$firstday = $month.'-01';
   		$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
		$condition['o.create_time'] = ['between',[strtotime($firstday),strtotime($lastday)]];
		$condition['o.type'] = ['not in',['gift']];

		$order = 'id desc';

		$pageSize = $param['pageSize'] ?? 15;
    	import('@.ORG.system_page');
		$count = D('System_order')->join(' as o left join '.C('DB_PREFIX').'merchant_store as s on o.store_id=s.store_id')->where($condition)->count();

		$p = new Page($count,$pageSize);
		$order_list = D('System_order')->field('o.*, s.name as store_name')->join(' as o left join '.C('DB_PREFIX').'merchant_store as s on o.store_id=s.store_id')->where($condition)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		$order_list = $this->fomartOrderList($order_list);

		$return['order_list'] = $order_list;
		$return['count'] = $count;
		$return['pagebar'] = $p->show();
		return $return;

    }

    public function fomartOrderList($order_list){
    	$businessNameArr = [
			'mall3' => C('config.mall_alias_name_new') ? : L_('商城'),
			'mall' => C('config.mall_alias_name_new') ? : L_('商城'),
			'shop' => C('config.shop_alias_name_new') ? : L_('快店'),
			'village_group' => L_('社区团购'),
			'dining' => C('config.meal_alias_name_new') ? : L_('餐饮'),
			'meal' => C('config.meal_alias_name_new') ? : L_('餐饮'),
			'group' => C('config.group_alias_name_new') ? : L_('团购'),
			'appoint' => C('config.appoint_alias_name_new') ? : L_('预约'),
			'service' => C('config.service_alias_name'),
			'store' => L_('店内收银'),
			'cash' => C('config.cash_alias_name') ? : '到店付',
			'mobile_recharge' => '在线充值',
		];
		foreach($order_list as &$order){
			$order['business_name'] = $businessNameArr[$order['type']];
			$order['cash'] = 0;
			switch ($order['type']) {
                case 'mall3'://新版商城3.0版本
                   	$nowOrder = M('Mall_order')->where(['order_id'=>$order['order_id']])->find();
                   	$order['payment_money'] = $nowOrder['money_online_pay'];
                   	$order['balance_pay'] = $nowOrder['money_system_balance'];
                   	$order['merchant_balance'] = $nowOrder['money_merchant_balance'] + $nowOrder['money_merchant_give_balance'];
                   	$order['coupon_price'] = $nowOrder['discount_system_coupon'];
                   	$order['card_price'] = $nowOrder['discount_merchant_coupon'];
                   	$order['score_deducte'] = $nowOrder['money_score'];
                   	$order['price'] = $nowOrder['money_real'];
                   	$order['real_orderid'] = $nowOrder['order_no'];
                    break;
                case 'mall':
                case 'shop':
                    $nowOrder = M("Shop_order")->where(['order_id'=>$order['order_id']])->find();

                   	$order['payment_money'] = $nowOrder['payment_money'];
                   	$order['balance_pay'] = $nowOrder['balance_pay'];
                   	$order['merchant_balance'] = getFormatNumber($nowOrder['merchant_balance'] + $nowOrder['card_give_money']);
                   	$order['coupon_price'] = $nowOrder['coupon_price'];
                   	$order['card_price'] = $nowOrder['card_price'];
                   	$order['score_deducte'] = $nowOrder['score_deducte'];

                   	// 商家收取金额
                   	$order['price'] = $nowOrder['price'];
                   
                    if($nowOrder['pay_type'] == 'offline'){
			           	// 商家收取金额
			           	$order['cash'] = getFormatNumber($order['price'] - $order['payment_money'] - $order['balance_pay'] - $order['merchant_balance'] - $order['coupon_price'] - $order['card_price']  - $order['score_deducte']);
                    }
                    
                    break;
                case 'village_group':
                    $nowOrder = M('Plat_order')->where(['business_type'=>'village_group','business_id'=>$order['order_id']])->find();

                   	$order['payment_money'] = $nowOrder['pay_money'];
                   	$order['balance_pay'] = $nowOrder['system_balance'];
                   	$order['merchant_balance'] = $nowOrder['merchant_balance_pay'] + $nowOrder['merchant_balance_give'];
                   	$order['coupon_price'] = $nowOrder['system_coupon_price'];
                   	$order['card_price'] = $nowOrder['merchant_coupon_price'];
                   	$order['score_deducte'] = $nowOrder['system_score_money'];
                   	$order['price'] = $nowOrder['total_money'];
                    break;
                case 'dining':

                    $nowOrder = D('Dining_order')->where(['order_id'=>$order['order_id']])->find();
                   	$order['payment_money'] = $nowOrder['pay_money'];
                   	$order['balance_pay'] = $nowOrder['system_balance'];
                   	$order['merchant_balance'] = $nowOrder['merchant_balance_pay'] + $nowOrder['merchant_balance_give'];
                   	$order['score_deducte'] = $nowOrder['system_score_money'];
                   	$order['price'] = $nowOrder['price'];
                    
                    break;
                case 'meal':
                    $nowOrder = D('Foodshop_order')->where(['order_id'=>$order['order_id']])->find();
                   	$order['price'] = $nowOrder['price'];
                   
                    $order['payment_money'] = 0;
                   	$order['balance_pay'] = 0;
                   	$order['merchant_balance'] = 0;
                   	$order['coupon_price'] = 0;
                   	$order['card_price'] = 0;
                   	$order['score_deducte'] = 0;
                    $orderList = M('Plat_order')->where(['business_type'=>'foodshop','business_id'=>$value['order_id'],'paid'=>1])->select();
                    foreach($orderList as $nowOrder){
                    	$order['payment_money'] += $nowOrder['pay_money'];
	                   	$order['balance_pay'] += $nowOrder['system_balance'];
	                   	$order['merchant_balance'] += $nowOrder['merchant_balance_pay'] + $nowOrder['merchant_balance_give'];
	                   	$order['coupon_price'] += $nowOrder['system_coupon_price'];
	                   	$order['card_price'] += $nowOrder['merchant_coupon_price'];
	                   	$order['score_deducte'] += $nowOrder['system_score_money'];
                    }

                    if($nowOrder['pay_type'] == 'offline'){
			           	// 商家收取金额
			           	$order['cash'] = getFormatNumber($order['price'] - $order['payment_money'] - $order['balance_pay'] - $order['merchant_balance'] - $order['coupon_price'] - $order['card_price']  - $order['score_deducte']);
                    }
                   	
                    break;
                case 'group':
                   
                    $nowOrder = D('Group_order')->where(['order_id'=>$order['order_id']])->find();
                    $order['price'] = getFormatNumber($nowOrder['payment_money']+$nowOrder['balance_pay']+$nowOrder['merchant_balance']+$nowOrder['score_deducte']+$nowOrder['card_give_money']);

                   	$order['payment_money'] = $nowOrder['payment_money'];
                   	$order['balance_pay'] = $nowOrder['balance_pay'];
                   	$order['merchant_balance'] = getFormatNumber($nowOrder['merchant_balance'] + $nowOrder['card_give_money']);
                   	$order['coupon_price'] = $nowOrder['coupon_price'];
                   	$order['card_price'] = $nowOrder['card_price'];
                   	$order['score_deducte'] = $nowOrder['score_deducte'];

                    if($nowOrder['pay_type'] == 'offline'){
			           	// 商家收取金额
			           	$order['cash'] = getFormatNumber($order['price'] - $order['payment_money'] - $order['balance_pay'] - $order['merchant_balance'] - $order['coupon_price'] - $order['card_price']  - $order['score_deducte']);
                    }
                    break;
                    
                case 'appoint':

                    $nowOrder = M('Appoint_order')->where(['order_id' => $order['order_id']])->find();
                   	$order['real_orderid'] = $nowOrder['real_orderid'];
                   	$order['payment_money'] = $nowOrder['pay_money'];
                   	$order['balance_pay'] = $nowOrder['balance_pay'];
                   	$order['merchant_balance'] = getFormatNumber($nowOrder['merchant_balance'] + $nowOrder['card_give_money']);
                   	$order['coupon_price'] = $nowOrder['coupon_price'];
                   	$order['card_price'] = $nowOrder['card_price'];
                   	$order['score_deducte'] = $nowOrder['score_deducte'];

                   	$order['price'] = $nowOrder['price'];

                    break;
                case 'service':
                    $nowOrder = M("Plat_order")->where(['order_id' => $order['order_id']])->find();
                   	$order['payment_money'] = $nowOrder['pay_money'];
                   	$order['balance_pay'] = $nowOrder['system_balance'];
                   	$order['merchant_balance'] = $nowOrder['merchant_balance_pay'] + $nowOrder['merchant_balance_give'];
                   	$order['coupon_price'] = $nowOrder['system_coupon_price'];
                   	$order['card_price'] = $nowOrder['merchant_coupon_price'];
                   	$order['score_deducte'] = $nowOrder['system_score_money'];
                   	$order['price'] = $nowOrder['total_money'];
                    
                    break;
                case 'cash':
                case 'store':
                    $nowOrder = M("Store_order")->where(['order_id'=>$order['order_id']])->find();
                   	$order['payment_money'] = $nowOrder['payment_money'];
                   	$order['balance_pay'] = $nowOrder['balance_pay'];
                   	$order['merchant_balance'] = getFormatNumber($nowOrder['merchant_balance'] + $nowOrder['card_give_money']);
                   	$order['coupon_price'] = $nowOrder['coupon_price'];
                   	$order['card_price'] = $nowOrder['card_price'];
                   	$order['score_deducte'] = $nowOrder['score_deducte'];
                   	$order['price'] = $nowOrder['discount_price'];
                    
                    break;
                case 'mobile_recharge':
                    $order['business_name'] = L_('在线充值');
                    $nowOrder = M("Plat_order")->where(['order_id' => $order['order_id']])->find();
                   	$order['payment_money'] = $nowOrder['pay_money'];
                   	$order['balance_pay'] = $nowOrder['system_balance'];
                   	$order['merchant_balance'] = $nowOrder['merchant_balance_pay'] + $nowOrder['merchant_balance_give'];
                   	$order['coupon_price'] = $nowOrder['system_coupon_price'];
                   	$order['card_price'] = $nowOrder['merchant_coupon_price'];
                   	$order['score_deducte'] = $nowOrder['system_score_money'];
                   	$order['price'] = $nowOrder['total_money'];
                    break;
            }

		}

		return $order_list;
    }


    public function getRefundMoney($merId, $firstday, $lastday){
    	$refundMoney = 0;

    	// 老版商城、外卖
    	$condition['o.status'] = ['neq', 4];
    	$condition['o.mer_id'] = $merId;
		$condition['o.create_time'] = ['between',[strtotime($firstday),strtotime($lastday)]];

        $refund = D('Shop_order_refund')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.order_id=m.order_id')->where($condition)->sum('price');
        $refundMoney += $refund;

        $refund = D('Shop_order_system_refund')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.order_id=m.order_id')->where($condition)->sum('price');
        $refundMoney += $refund;

        $condition = [];
    	$condition['status'] = 4;
    	$condition['mer_id'] = $merId;
		$condition['create_time'] = ['between',[strtotime($firstday),strtotime($lastday)]];
        $refund = D('Shop_order')->where($condition)->sum('price');
        $refundMoney += $refund;


        // 新版商城3.0版本
		$where = [
			'mer_id' => $merId,
			'create_time' => ['between',[strtotime($firstday),strtotime($lastday)]],
		];
        $refund = M('Mall_order')->where($where)->sum('money_refund');
        $refundMoney += $refund; 
               
        // 新版餐饮
        $where = [
			'mer_id' => $merId,
			'create_time' => ['between',[strtotime($firstday),strtotime($lastday)]],
		];
        $refund = M('Dining_order')->where($where)->sum('refund_money');
        $refundMoney += $refund; 
               
                
        // 餐饮
        $where = [
			'o.mer_id' => $merId,
			'o.create_time' => ['between',[strtotime($firstday),strtotime($lastday)]],
			'o.status' => 5,
			'p.paid' => 1,
			'p.refund_detail' => ['neq', ''],

		];
        $refund = M('Plat_order')->join(' as p left join '.C('DB_PREFIX').'foodshop_order as o on p.business_id=o.order_id AND p.business_type="foodshop"')->where($where)->sum('total_money');

        $refundMoney += $refund; 

         // 团购
        $where = [
			'mer_id' => $merId,
			'add_time' => ['between',[strtotime($firstday),strtotime($lastday)]],
			'status' => 3,
		];
        $refund = M('Group_order')->where($where)->sum('refund_money');
        $refundMoney += $refund;           

         // 预约
        $where = [
			'o.mer_id' => $merId,
			'o.order_time' => ['between',[strtotime($firstday),strtotime($lastday)]],
			'p.status' => 1,

		];
        $refund = M('Appoint_order_refund')->join(' as p left join '.C('DB_PREFIX').'appoint_order as o on p.order_id=o.order_id')->where($where)->sum('price');
        $refundMoney += $refund;           
                    
        
         // 预约
        $where = [
			'o.mer_id' => $merId,
			'o.add_time' => ['between',[strtotime($firstday),strtotime($lastday)]],
			'p.paid' => 1,
			'p.refund_detail' => ['neq', ''],


		];
        $refund = M('Plat_order')->join(' as p left join '.C('DB_PREFIX').'service_user_publish as o on p.business_id=o.order_id AND p.business_type="service')->where($where)->sum('total_money');
        $refundMoney += $refund; 

		return getFormatNumber($refundMoney);
    }


    public function getRefundOrderList($merId, $firstday, $lastday){
    	$refundMoney = 0;

    	// 老版商城、外卖
    	$condition['o.status'] = ['neq', 4];
    	$condition['o.mer_id'] = $merId;
		$condition['o.create_time'] = ['between',[strtotime($firstday),strtotime($lastday)]];


 		$refundList = D('Shop_order_refund')->field('sum(m.price) as refund_money, o.order_id, o.real_orderid, o.create_time, o.pay_time, o.order_from,o.store_id')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.order_id=m.order_id')->where($condition)->group('o.order_id')->select();
        $refundList = $refundList ?: [];

        $refundList1 = D('Shop_order_system_refund')->field('sum(m.price) as refund_money, o.order_id, o.real_orderid, o.create_time, o.pay_time, o.order_from,o.store_id')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.order_id=m.order_id')->where($condition)->group('o.order_id')->select();
        $refundList1 = $refundList1 ?: [];
        if($refundList1){
        	$refundList = $refundList ? array_merge($refundList, $refundList1) : $refundList1;
        }

        $condition = [];
    	$condition['status'] = 4;
    	$condition['mer_id'] = $merId;
		$condition['create_time'] = ['between',[strtotime($firstday),strtotime($lastday)]];
        $refundList2 = D('Shop_order')->field('price as refund_money, order_id, real_orderid, create_time, pay_time, order_from,store_id')->where($condition)->select();

        $refundList2 = $refundList2 ?: [];
        if($refundList2){
        	$refundList = !empty($refundList) ? array_merge($refundList, $refundList2) : $refundList2;
        }

        $shopRefundList = [];
        if($refundList){
        	foreach($refundList as $key => $order){
        		$order['business_name'] = $order['order_from'] == 1 ? 'mall' : 'shop';
        		if(isset($shopRefundList[$order['order_id']])){
        			$shopRefundList[$order['order_id']]['refund_money'] += $order['refund_money'];
        		}else{
        			$shopRefundList[$order['order_id']] = $order;
        		}
        	}
        }
        $return['list'] = array_values($shopRefundList);

        // 新版商城3.0版本
		$where = [
			'mer_id' => $merId,
			'create_time' => ['between',[strtotime($firstday),strtotime($lastday)]],
			'money_refund' => ['gt',0],
		];
        $mallRefund = M('Mall_order')->field('order_id, order_no as real_orderid, create_time, money_refund as refund_money, store_id, "mall" as business_name')->where($where)->select();
        $mallRefund = $mallRefund ?: [];

        $return['list'] = array_merge($return['list'], $mallRefund);
               
        // 新版餐饮
        $where = [
			'mer_id' => $merId,
			'create_time' => ['between',[strtotime($firstday),strtotime($lastday)]],
			'refund_money' => ['gt',0],
		];
        $diningRefund = M('Dining_order')->field('order_id, real_orderid, create_time, refund_money, store_id, "foodshop" as business_name')->where($where)->select();
        $diningRefund = $diningRefund ?: [];
        $return['list'] = array_merge($return['list'], $diningRefund);
               
                
        // 餐饮
        $where = [
			'o.mer_id' => $merId,
			'o.create_time' => ['between',[strtotime($firstday),strtotime($lastday)]],
			'o.status' => 5,
			'p.paid' => 1,
			'p.refund_detail' => ['neq', ''],

		];
        $refund = M('Plat_order')->join(' as p left join '.C('DB_PREFIX').'foodshop_order as o on p.business_id=o.order_id AND p.business_type="foodshop"')->where($where)->field('p.total_money as refund_money, o.order_id, o.real_orderid, o.create_time,o.store_id, "foodshop" as business_name' )->select();
        $refund = $refund ?: [];
        $return['list'] = array_merge($return['list'], $refund);


         // 团购
        $where = [
			'mer_id' => $merId,
			'add_time' => ['between',[strtotime($firstday),strtotime($lastday)]],
			'status' => 3,
		];
        $refund = M('Group_order')->field('order_id, real_orderid, add_time as create_time,store_id, refund_money, "group" as business_name')->where($where)->select();   
        $refund = $refund ?: [];
        $return['list'] = array_merge($return['list'], $refund);
     

         // 预约
        $where = [
			'o.mer_id' => $merId,
			'o.order_time' => ['between',[strtotime($firstday),strtotime($lastday)]],
			'p.status' => 1,

		];
        $refund = M('Appoint_order_refund')->join(' as p left join '.C('DB_PREFIX').'appoint_order as o on p.order_id=o.order_id')->where($where)->field('o.order_id, o.real_orderid, o.order_time as create_time,o.store_id, p.price as refund_money,  "appoint" as business_name')->select();

        $refund = $refund ?: [];
        $return['list'] = array_merge($return['list'], $refund);                    
        
         // 跑腿
        $where = [
			'o.mer_id' => $merId,
			'o.add_time' => ['between',[strtotime($firstday),strtotime($lastday)]],
			'p.paid' => 1,
			'p.refund_detail' => ['neq', ''],


		];
        $refund = M('Plat_order')->join(' as p left join '.C('DB_PREFIX').'service_user_publish as o on p.business_id=o.order_id AND p.business_type="service')->where($where)->field('o.order_id, o.publish_id real_orderid, o.add_time as create_time, p.total_money as refund_money,  "service" as business_name')->select();;

        $refund = $refund ?: [];
        $return['list'] = array_merge($return['list'], $refund);

        $storeIds = array_column($return['list'], 'store_id');
        if($storeIds){
        	$storeName = array_column(M('Merchant_store')->where(['store_id'=>['in', $storeIds]])->select(), 'name', 'store_id');
        }

        foreach($return['list'] as &$_order){
        	$_order['store_name'] = '';
        	$_order['business_name'] = $this->getBusinessName($_order['business_name']);
        	if(isset($storeName[$_order['store_id']]) && $storeName[$_order['store_id']]){
        		$_order['store_name'] = $storeName[$_order['store_id']];
        	}
        }
		return $return;
    }

    public function getBusinessName($type){
    	  $businessNameArr = [
			'mall3' => C('config.mall_alias_name_new') ? : L_('商城'),
			'mall' => C('config.mall_alias_name_new') ? : L_('商城'),
			'shop' => C('config.shop_alias_name_new') ? : L_('快店'),
			'village_group' => L_('社区团购'),
			'dining' => C('config.meal_alias_name_new') ? : L_('餐饮'),
			'meal' => C('config.meal_alias_name_new') ? : L_('餐饮'),
			'group' => C('config.group_alias_name_new') ? : L_('团购'),
			'appoint' => C('config.appoint_alias_name_new') ? : L_('预约'),
			'service' => C('config.service_alias_name'),
			'store' => L_('店内收银'),
			'cash' => C('config.cash_alias_name') ? : '到店付',
			'mobile_recharge' => '在线充值',
		];

		return $businessNameArr[$type] ?? '';
    }
    
    public function checkChangeAddress($orderId, $status, $type)
    {
       $order = M('System_order')->where(['type' => $type, 'order_id' => $orderId])->find();
       
       if(empty($order)){
           throw_exception(L_('当前订单不存在！'));
       }
       if($order['change_address_status'] > 1){
           throw_exception(L_('当前订单地址已审核，不可重复审核！'));
       }
       //修改地址状态  0 未修改  1 等待商家审核 2 商家拒绝修改 3 骑手接单，修改地址自动撤销 4 同意修改 5 自动审核
       if($status == 2){
          return M('System_order')
              ->where(['type' => $type, 'order_id' => $orderId])
              ->save(['change_address_status' => $status]);
       }else if(in_array($status, [4, 5])){
           M()->startTrans();
           try {
               M('System_order')
                   ->where(['type' => $type, 'order_id' => $orderId])
                   ->save(['change_address_status' => $status]);
              
               switch ($type){
                   case 'shop':
                       $addressRecord = M('Order_address_change_record')
                           ->where(['type' => $type, 'order_id' => $orderId])
                           ->find();

                       M('Shop_order')
                           ->where(['order_id' => $orderId])
                           ->save([
                               'username'   => $addressRecord['user_name'],
                               'userphone'  => $addressRecord['user_phone'],
                               'address'    => $addressRecord['change_address'],
                               'address_id' => $addressRecord['change_address_id'],
                               'lat'        => $addressRecord['change_lat'],
                               'lng'        => $addressRecord['change_lng'],
                           ]);
                       
                       (new PrintHaddle())->printit($orderId, 'shop_order', 1);
               }
               
               M()->commit();
           }catch (\Exception $e){
               M()->rollback();
               throw_exception(L_($e->getMessage()));
           }
       }else{
           throw_exception(L_('审核状态异常！'));
       }
       return true;
    }
}

?>