<?php
/*
*@create_date 2019-01-04
*@desc 平台订单信息模型
*/
class system_order{
	public function add_order($type,$now_order=null,$order_id=null,$step=1){
		if(!is_array($now_order) || empty($order_id)){
			return false;
		}
		$data=array();
		$detail_data=array();
		$model=M('System_order_detail');
		if($type=='shop' || $type=='mall'){		//快店、商城
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
					$detail_data[]=$arr;
				}
				$model->addAll($detail_data);
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
        }
	}
    //格式化服务快派业务订单
    protected function reg_service_order($info,$order_id,$type='service'){
        $system_id=$this->get_system_id($type,$order_id);
        $data=array();
        $data['type']=$type;
        $data['order_id']=$order_id;
        $data['mer_id']=0;
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
            $system_id = $this->data($data)->add();
            // 添加团购商品详情
            $system_order_detail_model=M('System_order_detail');
            $group = M('Group')->where(array('group_id' => $info['group_id']))->field('name,intro,pic,price')->find();
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
				$data['system_status'] = 2; // 系统状态，1-已发货，2-已完成，3-已评价，4-已退款，5-已取消
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
		$data['store_id']=$info['store_id'];
		$data['uid']=$info['uid'];
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

		if(isset($info['pay_money'])){
			$data['price']=$info['pay_money'];
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
            // 	使用商户余额
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
            // 	使用商户余额
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
			$arr=$this->where(array('id'=>$system_id))->getfield('extends');
			$arr=(array)json_decode($arr,true);
			$arr=array_merge($arr,$info);
			$data['extends']=json_encode($info);

			$data['create_time']=$_SERVER['REQUEST_TIME'];
			$this->data($data)->add();
            // 添加预约商品详情
            $system_order_detail_model=M('System_order_detail');
            $appoint = M('Appoint')->where(array('appoint_id' => $info['appoint_id']))->field('appoint_name,appoint_pic_content,pic,appoint_price')->find();
            $tmp_pic = explode(';',$appoint['pic']);
            $image_tmp = explode(',',$tmp_pic[0]);
            $list_pic = '/upload/appoint/'.$image_tmp[0].'/s_'.$image_tmp['1'];
            $detail = array(
                'system_id' => $system_id,
                'order_id' => $order_id,
                'mer_id' => $info['mer_id'],
                'goods_id' => $info['appoint_id'],
                'name' => $appoint['appoint_name'],
                'desc' => $appoint['appoint_pic_content'],
                'goods_image' =>$list_pic,
                'price' =>$appoint['appoint_price'],
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

		if ($data['paid'] == 1) {
			$data['system_status']=2;
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
				$data['price']=$info['pay_money'];
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
		$res=$this->data($data)->save();
		if($res){
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
		$res=$this->data($data)->save();
		return $res;
	}

	/*获取不同类型订单的平台订单号*/
	private function get_system_id($type,$order_id){
		$where=array();
		$where['type']=$type;
		$where['order_id']=$order_id;
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

}

?>