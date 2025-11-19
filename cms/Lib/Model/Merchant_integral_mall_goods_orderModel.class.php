<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * Date: 2019/5/15
 * Time: 13:11
 */
class Merchant_integral_mall_goods_orderModel extends Model
{
    // 获取订单信息
    public function getList($data, $is_pc=false, $is_goods_img=false)
    {
        if(!isset($_GET['page'] )){
            $_GET['page'] = $_POST['page'];
        }

        import('@.ORG.merchant_page');

        if ($data['virtual_wholesale_mer_id'] > 0) {
            $where['_string'] = 'o.mer_id='.$data['mer_id'];
        } else {
            $where = array('o.mer_id' => $data['mer_id']);
        }
        if ($data['status'] && $data['status'] != -1) {
            // -1 查询全部  0 查询下单 1: 已支付，2：发货成功，3：收货成功，4:已退款，5：用户自提 6： 订单取消（未支付）
            // 暂时只针对  待付款  待发货  待收货进行查询0
            if (0 == $data['status']) {
                $where['status'] = 0;
            } elseif ($data['status']==1) {
                $where['status'] = 1;
            } elseif ($data['status']==2) {
                $where['status'] = 2;
            } elseif ($data['status']==3) {
                $where['status'] = 3;
            } elseif ($data['status']==5) {
                $where['status'] = 5;
            }
        }

        $count = M('Merchant_integral_mall_goods_order')
                 ->alias('o')
                 ->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant_integral_mall_goods AS g ON g.goods_id=o.goods_id')
                 ->where($where)
                 ->count();
        $p = new Page($count, 20);

        $list = M('Merchant_integral_mall_goods_order')
                ->alias('o')
                ->field('o.*, g.virtual_wholesale_mer_id, g.goods_source_type, g.sku, g.payment_pure_integral as g_payment_pure_integral')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant_integral_mall_goods AS g ON g.goods_id=o.goods_id')
                ->where($where)
                ->order('o.order_id DESC')
                ->limit($p->firstRow, $p->listRows)
                ->select();


        $merchant_image_class = new merchant_image();
        $goods_image_class = new goods_image();
        $m_merchant = M('Merchant');
        foreach ($list as &$val) {
            if ($val['virtual_wholesale_mer_id']>0) {
                $val['look_mer_id'] = $data['mer_id'];
                $val['virtual_merchant'] = $m_merchant->where(array('mer_id' => $val['virtual_wholesale_mer_id']))->field('name, phone, email')->find();
            }

            if(!empty($val['pc_pic']) && $is_pc){
                $pc_pic_arr = explode(';',$val['pc_pic']);
                $pc_pic_list = array();
                foreach($pc_pic_arr as $key=>$pic){
                    if (stripos($pic,'upload/') !== false || $is_goods_img) {
                        $pc_pic_list[$key]['title'] = $pic;
                        $tmp_pic = $goods_image_class->get_image_by_path($pic);
                        $pc_pic_list[$key]['url'] = $tmp_pic['image'];
                        $pc_pic_list[$key]['m_url'] = $tmp_pic['m_image'];
                    } else {
                        $pc_pic_list[$key]['title'] = $pic;
                        $tmp_pic = $merchant_image_class->get_integral_mall_image_by_path($pic);
                        $pc_pic_list[$key]['url'] = $tmp_pic['image'];
                        $pc_pic_list[$key]['m_url'] = $tmp_pic['m_image'];
                    }
                }
                $val['pc_pic_list'] = $pc_pic_list;
                $image_pic = reset($pc_pic_list);
                $val['pic_image'] = $image_pic['url'];
            }
            if(!empty($val['wap_pic'])  && !$is_pc){
                $wap_pic_arr = explode(';',$val['wap_pic']);
                $wap_pic_list = array();
                foreach($wap_pic_arr as $key=>$pic){
                    if (stripos($pic,'upload/') !== false || $is_goods_img) {
                        $wap_pic_list[$key]['title'] = $pic;
                        $tmp_pic = $goods_image_class->get_image_by_path($pic);
                        $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                    } else {
                        $wap_pic_list[$key]['title'] = $pic;
                        $tmp_pic = $merchant_image_class->get_integral_mall_image_by_path($pic);
                        $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                    }
                }
                $val['wap_pic_list'] = $wap_pic_list;
                $image_wap = reset($wap_pic_list);
                $val['wap_image'] = $image_wap['url'];
            }
            switch ($val['status']) {
                case 0:
                    $val['status_txt'] = L_('待支付');
                    break;
                case 1:
                    $val['status_txt'] = L_('已支付');
                    if ($val['is_pick_in_store'] == 1) {
                        $val['status_txt'] .= ' ' . L_('待发货');
                    } else {
                        $val['status_txt'] .= ' ' . L_('待自提');
                    }
                    break;
                case 2:
                    $val['status_txt'] = L_('已发货');
                    break;
                case 3:
                    $val['status_txt'] = L_('成功收货');
                    break;
                case 4:
                    $val['status_txt'] = L_('已退款');
                    break;
                case 5:
                    $val['status_txt'] = L_('已自提');
                    break;
                case 6:
                    $val['status_txt'] = L_('订单取消');
                    break;
            }
        }


        $return = array('orders' => $list, 'totalPage' => $p->totalPage, 'pagebar' => $p->show(), 'first' => $p->firstRow);
        return $return;
    }

    public function save_order($order, $uid, $order_id=0) {

    }

    public function get_order_by_id($uid, $order_id)
    {
        $condition = array();
        $condition['order_id'] = $order_id;
		$uid && $condition['uid'] = $uid;
        return $this->field(true)->where($condition)->find();
    }

    public function get_pay_order($uid,$order_id, $is_web = false){
        $now_order = $this->get_order_by_id($uid, $order_id);

        if(empty($now_order)){
            return array('error'=>1,'msg'=>L_('当前订单不存在！'));
        }
        if($now_order['paid'] == 1){
            if (!$is_web) {
                return array('error' => 1, 'msg' => L_('您已经支付过此订单！'), 'url' => U('Wap/Integral_mall/order_detail_new', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'])));
            } else {
                return array('error' => 1, 'msg' => L_('您已经支付过此订单！'), 'url' => U('User/Index/integral_mall_order_view', array('order_id' => $now_order['order_id'])));
            }
        }

        if($now_order['status'] == 4 || $now_order['status'] == 6){
            if (!$is_web) {
                return array('error' => 1, 'msg' => L_('您的订单已取消，不能付款了！'), 'url' => U('Wap/Integral_mall/order_detail_new', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'])));
            } else {
                return array('error' => 1, 'msg' => L_('您的订单已取消，不能付款了！'), 'url' => U('User/Index/integral_mall_order_view', array('order_id' => $now_order['order_id'])));
            }
        }

        $now_goods = M('Merchant_integral_mall_goods')->where(array('goods_id' => $now_order['goods_id']))->field('exchange_limit_num, spec_val')->find();
        if ($now_order['spec_index'] && $now_goods['spec_val']) {
            // 查询用户兑换数量信息
            $where_buy['goods_id'] = $now_order['goods_id'];
            $where_buy['uid'] = $uid;
            $where_buy['status'] = array('in', array(1,2,3,5));
            if ($now_order['mer_id']>0) {
                $where_buy['mer_id'] = $now_order['mer_id'];
            }
            $msg = M('Merchant_integral_mall_goods_order')->where($where_buy)->group('spec_index')->getField('spec_index, count(1) as count_num');
            $buy_num = $msg[$now_order['spec_index']];
            $spec_val = unserialize($now_goods['spec_val']);
            $spec_list = $spec_val['list'];
            $single = $spec_list[$now_order['spec_index']];
            if (!$buy_num) {
                $buy_num = 0;
            }
            if ($single['exchange_limit_num']>0 && $single['exchange_limit_num'] <= $buy_num) {
                return array('error'=>1,'msg'=>L_('当前商品您已经兑换过了，不能在进行兑换！'));
            }
            $num_t = $single['exchange_limit_num'] - $buy_num;
            if ($num_t>0 && $num_t<$now_order['num']) {
                return array('error'=>1,'msg'=>L_('当前商品您已经兑换过了，不能兑换当前数量！'));
            }
        } else {
            // 查询用户兑换数量信息
            $where_buy['goods_id'] = $now_order['goods_id'];
            $where_buy['uid'] = $uid;
            $where_buy['status'] = array('in', array(1,2,3,5));
            if ($now_order['mer_id']>0) {
                $where_buy['mer_id'] = $now_order['mer_id'];
            }
            $buy_num = M('Merchant_integral_mall_goods_order')->where($where_buy)->count();
            if ($now_goods['exchange_limit_num']>0 && $now_goods['exchange_limit_num'] <= $buy_num) {
                return array('error'=>1,'msg'=>L_('当前商品您已经兑换过了，不能在进行兑换！'));
            }
            if (!$buy_num) {
                $buy_num = 0;
            }
            $num_t = $now_goods['exchange_limit_num'] - $buy_num;
            if ($num_t>0 && $num_t<$now_order['num']) {
                return array('error'=>1,'msg'=>L_('当前商品您已经兑换过了，不能兑换当前数量！'));
            }
        }


        if ($now_order['freight_charges']==1 && $now_order['is_pick_in_store'] == 1 && $now_order['freight_charge'] > 0) {
            $now_order['order_total_money'] = floatval($now_order['total_money']) - floatval($now_order['freight_charge']);
            $now_order['freight_charge'] = 0; // 如果配送费商家承担，用户无需支付运费
        } else {
            $now_order['order_total_money']	=	floatval($now_order['total_money']);
        }
        $now_order['order_type']	=	'integral_mall';
        $str =  $now_order['goods_name'] . ': ' . $now_order['payment_pure_integral'] . '商家积分×' . $now_order['num'];
        $now_order['order_txt_type']	=	$str;
        $order_info = array(
            'pay_offline' 			=> false,		  //线下支付
            'pay_merchant_balance' 	=> false,		  //商家余额
            'pay_merchant_coupon' 	=> false,		  //商家优惠券
            'pay_merchant_ownpay' 	=> false,		  //商家自有支付
            'pay_system_balance' 	=> true,		  //平台余额
            'pay_system_coupon' 	=> false,		  //平台优惠券
            'pay_system_score' 		=> false,		  //平台积分抵现
            'discount_status'       => false,         //是否有折扣
            'order_info' 			=> $now_order,	  //订单信息
            'order_total_money'	=>	floatval($now_order['order_total_money']),
        );


        return $order_info;
    }

    //手机端支付前订单处理
    public function wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user,$ecard_info=null)
    {
        $pay_money = round($order_info['order_total_money'] * 100) / 100; //本次支付的总金额
        if($merchant_balance['card_discount']>0){
            $pay_money = round(round(($pay_money-$order_info['freight_charge'])*$merchant_balance['card_discount']/10,3),2)+$order_info['freight_charge'];
        }
        $merchant_balance['card_money'] = round($merchant_balance['card_money'] * 100) / 100;			//用户在商家所拥有的金额
        $merchant_balance['card_give_money'] = round($merchant_balance['card_give_money'] * 100) / 100;			//用户在商家所拥有的金额
        //判断优惠券
        if(C('config.open_extra_price')==1){
            $user_score_use_percent=(float)C('config.user_score_use_percent');
            $order_info['order_extra_price'] = bcdiv($order_info['extra_price'],$user_score_use_percent,2);
            $pay_money +=$order_info['order_extra_price'];
        }
        if($pay_money<=0){
            $order_result = $this->wap_pay_save_order($order_info, array());
            if ($order_result['error_code']) {
                return $order_result;
            }
            return $this->wap_after_pay_before($order_info);
        }

        //判断商家余额
        if(!empty($merchant_balance['card_money'])&&$order_info['use_merchant_balance']){
            if ($merchant_balance['card_money'] >= $pay_money) {
                $data_mall_order['merchant_balance'] = $pay_money;
                $order_info['merchant_balance'] = $pay_money;
                $order_result = $this->wap_pay_save_order($order_info, $data_mall_order);
                if ($order_result['error_code']) {
                    return $order_result;
                }
                return $this->wap_after_pay_before($order_info);
            }else{
                $data_mall_order['merchant_balance'] = $merchant_balance['card_money'];
            }
            $pay_money -= $merchant_balance['card_money'];
            $pay_money = round($pay_money * 100)/100;
        }

        if(!empty($merchant_balance['card_give_money'])&&$order_info['use_merchant_balance']){
            if($merchant_balance['card_give_money'] >= $pay_money){
                $data_mall_order['card_give_money'] = $pay_money;
                $order_info['merchant_balance_give'] = $pay_money;
                $order_result = $this->wap_pay_save_order($order_info,$data_mall_order);
                if($order_result['error_code']){
                    return $order_result;
                }
                return $this->wap_after_pay_before($order_info);
            }else{
                $data_mall_order['card_give_money'] = $merchant_balance['card_give_money'];
            }
            $pay_money -= $merchant_balance['card_give_money'];
        }

        //判断帐户余额
        if(!empty($now_user['now_money'])&&$order_info['use_balance']){
            $now_user['now_money'] = round($now_user['now_money'] * 100)/100;
            if($now_user['now_money'] >= $pay_money){
                $data_mall_order['balance_pay'] = $pay_money;
                $order_info['balance_pay'] = $pay_money;
                $order_result = $this->wap_pay_save_order($order_info,$data_mall_order);
                if($order_result['error_code']){
                    return $order_result;
                }
                $order_info['pay_money'] = $pay_money;
                return $this->wap_after_pay_before($order_info);
            }else{
                $data_mall_order['balance_pay'] = $now_user['now_money'];
            }
            $pay_money -= $now_user['now_money'];
            $pay_money = round($pay_money * 100)/100;
        }
        //在线支付
        $order_result = $this->wap_pay_save_order($order_info, $data_mall_order);
        if($order_result['error_code']){
            return $order_result;
        }
        return array('error_code' => false, 'pay_money' => $pay_money);
    }

    /**
     * 处理本次使用的余额付款，将余额保存到订单
     */
    public function wap_pay_save_order($order_info, $data_mall_order)
    {
        $condition_mall_order['order_id'] 		= $order_info['order_id'];
        $data_mall_order['merchant_balance_pay'] 	= !empty($data_mall_order['merchant_balance']) ? $data_mall_order['merchant_balance'] : 0;
        $data_mall_order['merchant_balance_give'] 	= !empty($data_mall_order['card_give_money']) ? $data_mall_order['card_give_money'] : 0;
        $data_mall_order['balance_pay'] 		= !empty($data_mall_order['balance_pay']) ? $data_mall_order['balance_pay'] : 0;
        $data_mall_order['last_time'] 			= $_SERVER['REQUEST_TIME'];

        $save_id = $this->where($condition_mall_order)->data($data_mall_order)->save();
        if ($save_id) {
            return array('error_code' => false, 'msg' => L_('保存订单成功！'));
        } else {
            return array('error_code' => true, 'msg' => L_('保存订单失败！请重试或联系管理员。'));
        }
    }

    //如果无需调用在线支付，使用此方法即可。
    public function wap_after_pay_before($order_info)
    {
        $order_param = array(
            'order_id' => $order_info['order_id'],
            'orderid' => $order_info['orderid'],
            'pay_type' => '',
            'third_id' => '',
            'is_mobile' => $order_info['is_mobile'],
            'pay_money' => 0,
            'order_total_money' => $order_info['order_total_money'],
            'balance_pay' => $order_info['balance_pay'],
            'merchant_balance' => !empty($data_mall_order['merchant_balance']) ? $data_mall_order['merchant_balance'] : 0,
            'merchant_balance_give' => !empty($data_mall_order['merchant_balance_give']) ? $data_mall_order['merchant_balance_give'] : 0,
            'order_type' => 'integral_mall',
        );
        $result_after_pay = $this->after_pay($order_param);
        if($result_after_pay['error']){
            return array('error_code' => true,'msg'=>$result_after_pay['msg']);
        }
//        if ($order_info['order_from'] == 1) {
//            $back_url=C('config.site_url').U('Wap/Integral_mall/order_detail_new',array('order_id'=>$order_info['order_id'],'mer_id'=>$order_info['mer_id'],'store_id'=>$order_info['store_id']));
//            $back_url=str_replace('/source/','/',$back_url);
//            return array('error_code'=>false,'msg'=>L_('支付成功'),'url'=>$back_url);
//        } else {
//            return array('error_code'=>false,'msg'=>L_('支付成功！'),'url' => C('config.site_url') . "/wap.php?g=Wap&c=Integral_mall&a=status&order_id=" . $order_info['order_id'] . '&mer_id=' . $order_info['mer_id']);
//        }
        return array('error_code'=>false,'msg'=>L_('支付成功！'),'url' => C('config.site_url') . "/wap.php?g=Wap&c=Integral_mall&a=order_detail_new&order_id=" . $order_info['order_id'] . '&mer_id=' . $order_info['mer_id']);

    }

    public function after_pay($order_param)
    {
        if ($order_param['pay_type'] != '') {
            $where['orderid'] = $order_param['order_id'];
        } else {
            $where['order_id'] = $order_param['order_id'];
        }

        $now_order = $this->field(true)->where($where)->find();
        if (empty($now_order)) {
            return array('error' => 1, 'msg' => L_('当前订单不存在！'));
        } elseif ($now_order['paid'] == 1) {
            if ($order_param['is_mobile']) {
                return array('error' => 1, 'msg' => L_('该订单已付款！'), 'url' => U('Wap/Integral_mall/order_detail_new', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'])));
            } else {
                return array('error' => 1, 'msg' => L_('该订单已付款！'), 'url' => U('User/Index/integral_mall_order_view', array('order_id' => $now_order['order_id'])));
            }
        } else {
            //得到当前用户信息，不将session作为调用值，因为可能会失效或错误。
            $now_user = D('User')->get_user($now_order['uid']);
            if(empty($now_user)){
                return array('error'=>1,'msg'=>L_('没有查找到此订单归属的用户，请联系管理员！'));
            }
            //判断对应商家会员卡积分
            $total_integral = floatval($now_order['total_integral']);
            $is_pay_card = false;
            if($total_integral > 0){
                $uid = $now_order['uid'];
                $mer_id = $now_order['mer_id'];
                $now_user_card = M('Card_userlist')->where(array('uid'=>$uid,'mer_id'=> $mer_id))->find();
                if(!empty($now_user_card) && $now_user_card['status']==0){
                    return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡不能使用！'));
                }
                if ($total_integral > $now_user_card['card_score']) {
                    return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡积分当前不足！') . L_('剩余商家积分') . $now_user_card['card_score']);
                }
                $desc = L_('兑换商家积分商城') . '  ' . $now_order['goods_name'] . '  ' . L_('扣除积分');

                $card = D('Card_new')->user_score($uid, $mer_id, $total_integral, $desc);
                if ($card['error_code']) {
                    return $this->wap_after_pay_error($now_order,$order_param,$card['msg']);
                }
                $is_pay_card = true;
            }
            $order_param['is_pay_card'] = $is_pay_card;
            $order_param['total_integral'] = $total_integral;


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

            //判断是否是商城订单
            $alias_name = '商家积分商城';

            if($merchant_balance){
                $use_result = D('Card_new')->use_money($now_order['uid'],$now_order['mer_id'],$merchant_balance,L_("兑换X1商品，扣除会员卡余额 ，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['orderid'])));
                if($use_result['error_code']){
                    return array('error'=>1,'msg'=>$use_result['msg']);
                }
            }

            if($card_give_money){
                $use_result = D('Card_new')->use_give_money($now_order['uid'],$now_order['mer_id'],$card_give_money,L_("兑换X1商品，扣除会员卡赠送余额 ，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['orderid'])));
                if($use_result['error_code']){
                    return array('error'=>1,'msg'=>$use_result['msg']);
                }
            }


            //如果用户使用了余额支付，则扣除相应的金额。
            if (!empty($balance_pay)) {
                $use_result = D('User')->user_money($now_order['uid'], $balance_pay, L_("兑换X1商品，扣除余额，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['orderid'])));
                if ($use_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }
            // 处理-积分兑换-扣除对应商家积分  并减少对应商品库存

            $d_merchant_integral_mall_goods = D('Merchant_integral_mall_goods');
            $goods_where = array();
            $goods_where['goods_id'] = $now_order['goods_id'];
            $goods_detail = $d_merchant_integral_mall_goods->goods_detail($goods_where);
            $detail = $goods_detail['detail'];
            $sale_count = $detail['sale_count'] > 0 ? $detail['sale_count'] : 0;
            $spec_val = array();
            if ($now_order['spec_index'] && $now_order['spec_value']) {
                $spec_index = $now_order['spec_index'];
                $now_order['spec_val'] = unserialize($now_order['spec_value']);
                $spec_list = $now_order['spec_val']['list'];
                $spec_info = $spec_list[$spec_index];
                if (empty($spec_info)) {
                    return $this->wap_after_pay_error($now_order,$order_param,L_('您所买的商品规格有误！'));
                }
                if ($detail['spec_val']['list'][$spec_index]) {
                    $goods_spec = $detail['spec_val']['list'][$spec_index];
                    $spec_val = $detail['spec_val'];
                    $sell_num = $goods_spec['sell_num'];
                    $stock_num = $goods_spec['stock_num'];
                    if ($now_order['num'] > ($stock_num - $sell_num)) {
                        return $this->wap_after_pay_error($now_order,$order_param,L_('您所买的商品库存不足！'));
                    }
                    $sell_num += $now_order['num'];
                    $spec_val['list'][$spec_index]['sell_num'] = $sell_num;
                }
            } else {
                if ($now_order['num'] > ($detail['sku'] - $detail['sale_count'])) {
                    return $this->wap_after_pay_error($now_order,$order_param,L_('您所买的商品库存不足！'));
                }
            }
            $sale_count += intval($now_order['num']);


            $data_mall_order = array();
            $data_mall_order['pay_time'] = isset($order_param['pay_time']) && $order_param['pay_time'] ? strtotime($order_param['pay_time']) : $_SERVER['REQUEST_TIME'];
            $data_mall_order['payment_money'] = floatval($order_param['pay_money']);//在线支付的钱
            $data_mall_order['pay_type'] = $order_param['pay_type'];
            $data_mall_order['third_id'] = $order_param['third_id'];

            $data_mall_order['is_mobile_pay'] = $order_param['is_mobile'];

            $data_mall_order['paid'] = 1;
            $data_mall_order['status'] = 1;
            $data_mall_order['payment_pure_integral'] = $now_order['payment_pure_integral'];

            $real_orderid = date('ymdhis').substr(microtime(),2,8-strlen($now_order['uid'])).$now_order['uid'];
            $data_mall_order['real_orderid'] = $real_orderid;


            if($this->where($where)->save($data_mall_order)){

                // 处理-积分兑换-扣除对应商家积分  并减少对应商品库存
                $save = array();
                $save['sale_count'] = $sale_count;
                if (!empty($spec_val)) {
                    $save['spec_val'] = serialize($spec_val);
                }
                $d_merchant_integral_mall_goods->where($goods_where)->data($save)->save();

                // 虚拟批发减少实际发货商户批发市场商品库存
                if ($detail['virtual_wholesale_mer_id']>0 && ($detail['virtual_wholesale_goods_id']>0 || $detail['market_goods_id']>0)) {
                    // 同步修改批发市场订单为以支付

                    $order_info = M('Market_order')->where(array('integral_mall_goods_order_id' => $now_order['order_id'],'type' => 1))->find();
                    if ($order_info){
                        $total_money_ = floatval($order_info['actual_money']);
                        if ($now_order['freight_charges'] == 1 && $now_order['freight_charge'] > 0 && $now_order['is_pick_in_store'] == 1) {
                            // 查询下给真实商家加钱
                            $total_money_ += $now_order['freight_charge'];
                        }
                        $desc_use = L_('用户兑换虚拟批发购买商品');
                        D('Merchant_money_list')->use_money($order_info['mer_id'],$total_money_,'integral_mall',$desc_use,$now_order['order_id']);

                        $data_order['pay_time'] = $_SERVER['REQUEST_TIME'];
                        $data_order['payment_money'] = floatval($order_info['actual_money']);
                        $data_order['status'] = 1;
                        M('Market_order')->where(array('integral_mall_goods_order_id' => $now_order['order_id'],'type' => 1))->save($data_order);
                    } else {
                        // 给商户加钱  原价 + 运费  实时计算价格 根据真实商家的价格计算
                        $merchant_info = M('Merchant')->where(array('mer_id'=>$now_order['mer_id']))->find();
                        $is_vip = false;
                        if ($merchant_info['type_id']>0) {
                            $merchant_type = M('Merchant_type')->where(array('type_id'=>$merchant_info['type_id']))->find();
                            $is_vip = (1==$merchant_type['is_vip'])?true:false;
                        }
                        $spec_index = $now_order['spec_index'];
                        $databaseMarketGoods = D('Market_goods');
                        $virtual_goods_id = $detail['virtual_wholesale_goods_id']>0 ? $detail['virtual_wholesale_goods_id'] : $detail['market_goods_id'];
                        $where = array('goods_id' => $virtual_goods_id);
                        $market_goods = $databaseMarketGoods->field(true)->where($where)->find();
                        $num = $now_order['num'];
                        $money = 0;
                        if ($market_goods['spec_value'] && $spec_index) {
                            $spec_ret = $databaseMarketGoods->format_spec_value($market_goods['spec_value'], $market_goods['goods_id']);
                            $spec_list = isset($spec_ret['spec_list']) ? $spec_ret['spec_list'] : '';
                            $list = isset($spec_ret['list']) ? $spec_ret['list'] : '';
                            if ($spec_list && $list && $list[$spec_index]) {
                                $spec_goods = $list[$spec_index];
                                if ($is_vip) {
                                    $price = $spec_goods['vip_wholesale_price'] ? floatval($spec_goods['vip_wholesale_price']) : floatval($spec_goods['price']);
                                    $money = $price * $num;
                                } else {
                                    $price = floatval($spec_goods['price']);
                                    $money = $price * $num;
                                }
                            }
                        } else {
                            if ($is_vip) {
                                $price = $market_goods['vip_wholesale_price'] ? floatval($market_goods['vip_wholesale_price']) : floatval($market_goods['price']);
                                $money = $price * $num;
                            } else {
                                $price = floatval($market_goods['price']);
                                $money = $price * $num;
                            }
                        }
                        $total_money = $money + floatval($now_order['freight_charge']);
                        $desc_use = L_('用户兑换虚拟批发购买商品');
                        D('Merchant_money_list')->use_money($order_info['mer_id'],$total_money,'integral_mall',$desc_use,$now_order['order_id']);
                    }

                    $databaseMarketGoods = D('Market_goods');
                    $virtual_goods_id = $detail['virtual_wholesale_goods_id']>0 ? $detail['virtual_wholesale_goods_id'] : $detail['market_goods_id'];
                    $where = array('goods_id' => $virtual_goods_id);
                    $spec_index = $now_order['spec_index'];
                    $market_goods = $databaseMarketGoods->field(true)->where($where)->find();
                    $num = $now_order['num'];
                    if ($market_goods['spec_value'] && $spec_index) {
                        $spec_array = explode('#', $market_goods['spec_value']);
                        $target_spec_value_array = array();
                        foreach ($spec_array as $str) {
                            $row_array = explode('|', $str);
                            $i = str_replace(':', '_', $row_array[0]);
                            if ($i == $spec_index) {
                                $numArray = explode(':', $row_array[1]);
                                if ($numArray[3]!=-1) {
                                    $numArray[3] = max(0, ($numArray[3] - $num));
                                }
                                $row_array[1] = implode(':', $numArray);
                            }
                            $target_spec_value_array[] = implode('|', $row_array);
                        }
                        $spec_array = $target_spec_value_array;
                        $marketGoodsChageData['spec_value'] = implode('#', $spec_array);
                    }
                    $marketGoodsChageData['stock_num'] = max(0, ($market_goods['stock_num'] - $num));
                    $marketGoodsChageData['sell_count'] = $market_goods['sell_count'] + $num;

                    //更改批发市场中商品的库存
                    $databaseMarketGoods->where(array('goods_id' => $virtual_goods_id))->save($marketGoodsChageData);
                }

                D('Merchant_integral_mall_log')->add_log(array('order_id' => $now_order['order_id'], 'status' => 1, 'note' => '用户支付订单'));

                if ($now_order['is_pick_in_store'] == 0) {
                    /*****************增加取单号**********************/
                    $fetch_number = 'fetch_' . date('ymdhis').substr(microtime(),2,8-strlen($now_order['uid'])).$now_order['uid'];
                    $this->where($where)->save(array('fetch_number' => $fetch_number));
                }
                D('Scroll_msg')->add_msg('integral_mall',$now_user['uid'],L_('用户x1于x2购买x3成功',array('x1'=>str_replace_name($now_user['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'x3'=>$alias_name)));

                //增加粉丝 5 group 6 shop 7 meal 8 appoint 9 store 10 integral_mall
                D('Merchant')->saverelation($now_user['openid'], $now_order['mer_id'], 10);


                //微信模板消息提醒
                if ($now_user['openid']) {
                    /*积分商城订单跳转到积分商城订单详情页*/
                    $keyword2 = $now_order['goods_name'] . ': ' . $now_order['payment_pure_integral'] . '×' . $now_order['num'];
                    $href = C('config.site_url').'/wap.php?c=Integral_mall&a=order_detail_new&order_id='. $now_order['order_id'] . '&mer_id=' . $now_order['mer_id'];
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => $alias_name.L_('提醒'), 'keyword2' => $now_order['real_orderid'], 'keyword1' => $keyword2, 'keyword3' => $now_order['price'], 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_('购买成功!')), $now_order['mer_id']);
                }

                //短信提醒
                $sms_data = array('mer_id' => $now_order['mer_id'], 'type' => 'integral_mall');
                // 获取对应商家信息
                $merchant = M('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
                if (!($now_user['openid'] && $now_user['is_follow']) && (C('config.sms_shop_success_order') == 1 || C('config.sms_shop_success_order') == 3)){
                    if (empty($now_order['phone'])) {
                        $user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
                        $now_order['phone'] = $user['phone'];
                    }
                    $sms_data['uid'] = $now_order['uid'];
                    $sms_data['mobile'] = $now_order['userphone'];
                    $sms_data['sendto'] = 'user';
                    $sms_data['content'] = L_("您在X1中的订单号：X2，已经完成了支付！",array("X1" => $merchant['name'],"X2" => $now_order['orderid']));
                    Sms::sendSms($sms_data);
                }
                if (C('config.sms_shop_success_order') == 2 || C('config.sms_shop_success_order') == 3) {
                    $merchant_phpne = explode(' ',$merchant['phone']);
                    if(count($merchant_phpne)>0){
                        foreach($merchant_phpne as $phone){
                            if(preg_match('/^[0-9]{11}$/',$phone)){
                                $merchant['phone'] = $phone;
                            }
                        }
                    }
                    $sms_data['uid'] = 0;
                    $sms_data['mobile'] = $merchant['phone'];
                    $sms_data['sendto'] = 'merchant';
                    $sms_data['content'] = L_("顾客X1在X2时，将订单号：X3支付成功！",array("X1" => $now_order['username'],"X2" => date("Y-m-d H:i:s"),"X3" => $now_order['orderid']));
                    Sms::sendSms($sms_data);
                }

                /* 粉丝行为分析 */
                if ($order_param['is_mobile']) {
                    return array('error' => 0, 'url' => U('Wap/Integral_mall/order_detail_new', array('mer_id' => $now_order['mer_id'], 'order_id' => $now_order['order_id'])));
                } else {
                    return array('error' => 0, 'url' => U('User/Index/integral_mall_order_view', array('order_id'=>$now_order['order_id'])));
                }
            } else {
                return array('error' => 1, 'msg' => L_('修改订单状态失败，请联系系统管理员！'));
            }
        }
    }

    //支付时，金额不够，记录到帐号
    public function wap_after_pay_error($now_order,$order_param,$error_tips)
    {
        //记录充值的金额，因为 Pay/return_url 处没有返回order的具体信息，故在此调用。
        $user_result['error_code'] = 0;
        if($order_param['pay_money'] > 0){
            $alreadyMoney = M("User_money_list")
                ->where(['money'=>$order_param['pay_money'],'uid'=>$now_order['uid'],'ask'=> 14, 'ask_id'=>$now_order['order_id']])//14 merchant_integral_mall_goods_order
                ->order('pigcms_id desc')
                ->find();
            if(empty($alreadyMoney)){
                $user_result = D('User')->add_money($now_order['uid'],$order_param['pay_money'],L_("支付订单：X1发生错误！原因是：X2",array("X1" => $now_order['order_id'],"X2" => $error_tips)),14,$now_order['order_id']);
            }
        }
        if($order_param['pay_type']=='weixin'&&ACTION_NAME=='return_url'){
            exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
        }
        if ($user_result['error_code']) {
            return array('error'=>1,'msg'=>$user_result['msg']);
        } else {
            // 返还积分
            if ($order_param['is_pay_card']) {
                $total_integral = $order_param['total_integral'];
                $desc = L_('兑换商家积分商城') . '  ' . $now_order['goods_name'] . '  ' . L_('付款失败退还积分');
                D('Card_new')->add_user_money($now_order['mer_id'], $now_order['uid'],0,0, $total_integral,'',$desc);
            }

            if ($order_param['is_mobile']) {
                $return_url = str_replace('/source/', '/', U('Integral_mall/order_detail_new',array('order_id' => $now_order['order_id'])));
            } else {
                $return_url = U('User/Index/integral_mall_order_view', array('order_id' => $now_order['order_id']));
            }
            return array('error' => 1, 'msg' => $error_tips . L_('已将您充值的金额添加到您的余额内。'), 'url' => $return_url);
        }
    }

    public function get_order_detail($where, $is_goods_img=true){
        $order = $this->field(true)->where($where)->find();
        if (empty($order)) return false;
        // 获取下对应商品部分信息
        $goods_info = M('merchant_integral_mall_goods')->where(array('goods_id' => $order['goods_id']))->field('virtual_wholesale_mer_id')->find();

        $order['virtual_wholesale_mer_id'] = $goods_info['virtual_wholesale_mer_id']>0 ? $goods_info['virtual_wholesale_mer_id'] : 0;

        $order['create_date'] = date('Y-m-d H:i:s', $order['create_time']);
        switch ($order['status']) {
            case 0:
                $order['show_status'] = L_('待兑换');
                $order['status_str'] = L_('待支付');
                break;
            case 1:
                if ($order['is_pick_in_store'] == 1) {
                    $order['show_status'] = L_('已支付，待发货');
                    $order['status_str'] = L_('已支付，待发货');
                } else {
                    $order['show_status'] = L_('已支付，待自提');
                    $order['status_str'] = L_('已支付，待自提');
                }
                break;
            case 2:
                $order['show_status'] = L_('已发货');
                $order['status_str'] = L_('已发货');
                break;
            case 3:
                $order['show_status'] = L_('成功收货');
                $order['status_str'] = L_('成功收货');
                break;
            case 4:
                $order['show_status'] = L_('已退款');
                $order['status_str'] = L_('已退款');
                break;
        }
        if ($order['is_pick_in_store'] == 1) {
            $order['deliver_str'] = L_('快递配送');;
            $order['deliverinfo'] = L_('快递配送');
        } elseif ($order['is_pick_in_store'] == 0) {
            $order['deliver_str'] = L_('到店自提');
            $order['deliverinfo'] = L_('到店自提');
        }
        if ($order['is_pick_in_store'] == 1 && $order['express_id'] > 0) {
            $express = D('Express')->get_express($order['express_id']);
            $order['express_name'] = isset($express['name']) ? $express['name'] : '';
            $order['express_code'] = $express['code'];
        }
        if ($order['paid'] == 0) {
            $order['pay_status'] = '<b style="color:red">'.L_('未支付').'</b>';
            $order['pay_status_print'] = L_('未支付');
        } elseif ($order['paid'] == 1) {
            $order['pay_status'] = '<b style="color:green">'.L_('已支付').'</b>';
            $order['pay_status_print'] = L_('已支付');
        }
        $order['pay_type_str'] = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay'], $order['paid']);
        $order['spec_value'] = unserialize($order['spec_value']);

        if ($order['is_pick_in_store'] == 1) {
            $express = D('Express')->get_express($order['express_id']);
            $order['express_name'] = isset($express['name']) ? $express['name'] : '';
            $order['express_code'] = $express['code'];
        }

        $merchant_image_class = new merchant_image();
        $goods_image_class = new goods_image();
        if(!empty($order['pc_pic'])){
            $pc_pic_arr = explode(';',$order['pc_pic']);
            $pc_pic_list = array();
            foreach($pc_pic_arr as $key=>$pic){
                if (stripos($pic,'upload/') !== false || $is_goods_img) {
                    $pc_pic_list[$key]['title'] = $pic;
                    $tmp_pic = $goods_image_class->get_image_by_path($pic);
                    $pc_pic_list[$key]['url'] = $tmp_pic['image'];
                    $pc_pic_list[$key]['m_url'] = $tmp_pic['m_image'];
                } else {
                    $pc_pic_list[$key]['title'] = $pic;
                    $tmp_pic = $merchant_image_class->get_integral_mall_image_by_path($pic);
                    $pc_pic_list[$key]['url'] = $tmp_pic['image'];
                    $pc_pic_list[$key]['m_url'] = $tmp_pic['m_image'];
                }
            }
            $order['pc_pic_list'] = $pc_pic_list;
            $image_pic = reset($pc_pic_list);
            $order['pic_image'] = $image_pic['url'];
        }
        if(!empty($order['wap_pic'])){
            $wap_pic_arr = explode(';',$order['wap_pic']);
            $wap_pic_list = array();
            foreach($wap_pic_arr as $key=>$pic){
                if (stripos($pic,'upload/') !== false || $is_goods_img) {
                    $wap_pic_list[$key]['title'] = $pic;
                    $tmp_pic = $goods_image_class->get_image_by_path($pic);
                    $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                } else {
                    $wap_pic_list[$key]['title'] = $pic;
                    $tmp_pic = $merchant_image_class->get_integral_mall_image_by_path($pic);
                    $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                }
            }
            $order['wap_pic_list'] = $wap_pic_list;
            $image_wap = reset($wap_pic_list);
            $order['wap_image'] = $image_wap['url'];
        }


        return $order;
    }

    // 积分商城-获取地址信息对应的配送费
    public function express_template_fee($user_address=array(), $info = array(), $num = 1, $spec_index='', $is_pick_in_store=1) {
        $express_fee = null;
        $total_express_fee = 0; // 总计配送费
        $where = array(
            'tid' => $info['express_template_id']
        );
        if ($is_pick_in_store==1) {
            $where['type'] = 1;
        } elseif ($is_pick_in_store==2) {
            $where['type'] = 2;
        } else {
            $info['total_express_fee'] = $total_express_fee;
            return $info;
        }
        if ($templates = M('Market_express_template_msg')->field(true)->where($where)->find()) {
            $freight_type = $templates['freight_type'];
            // 模板相关信息
            $return = array();
            if ($templates['id']) {
                $sql = "SELECT v.*, a.area_id FROM " . C('DB_PREFIX') . "market_express_template_value AS v INNER JOIN " . C('DB_PREFIX') . "market_express_template_area AS a ON v.id=a.vid WHERE v.tid = ({$templates['tid']}) AND v.tmid = ({$templates['id']}) ORDER BY v.id DESC";
                $list = $this->query($sql);
                if ($info['virtual_wholesale_store_id']) {
                    $store = D('Merchant_store')->field(true)->where(array('store_id' => $info['virtual_wholesale_store_id']))->find();
                    $this_city = $store['province_id'];
                }
                foreach ($list as $row) {
                    if ($return && $return[$row['tid']] && $return[$row['tid']][$row['area_id']]) {
                        continue;
                    } elseif ($row['area_id'] == 0) {//同城
                        $return[$row['tid']][$this_city] = $row;
                    } else {
                        $return[$row['tid']][$row['area_id']] = $row;
                    }
                }
            }
            foreach ($return as $k => $value) {
                if ($k == $templates['id']) {
                    $value['freight_type'] = $templates['freight_type'];
                }
                $return[$k] = $value;
            }
            // 计算当前运费
            $express_msg = array();
            if ($user_address) {
                $msg = $return[$templates['tid']];
                if ($msg[$user_address['province']]) {
                    $express_msg = $msg[$user_address['province']];
                } else {
                    $express_fee =  $info['express_fee'];
                }
            }
            // 计算各配送费
            if ($express_msg) {
               if ($spec_index) {
                   $spec_list = $info['spec_val']['list'];
                   if (2==$freight_type && $spec_list[$spec_index]) {
                       $parameter = $spec_list[$spec_index]['goods_weight'];
                   } elseif (3==$freight_type && $spec_list[$spec_index]) {
                       $parameter = $spec_list[$spec_index]['goods_volume'];
                   } elseif (1==$freight_type) {
                       $parameter = 1;
                   } elseif (!$spec_list[$spec_index]) {
                       return array('error'=> true, 'err_msg' => '所选的规格信息错误，请重新选择！');
                   }
                   if ($parameter<=0) {
                       $parameter = 1;
                   }
                   $express_fee = $this->express_fee_count($express_msg, $num, $parameter, $freight_type);
               } else {
                   if (2==$freight_type && $info) {
                       $parameter = $info['goods_weight'];
                   } elseif (3==$freight_type && $info) {
                       $parameter = $info['goods_volume'];
                   } elseif (1==$freight_type) {
                       $parameter = 1;
                   }
                   if ($parameter<=0) {
                       $parameter = 1;
                   }
                   $express_fee = $this->express_fee_count($express_msg, $num, $parameter, $freight_type);
               }
            }
        }
        return $express_fee;
    }

    /**
     * 配送费计算
     * @param array $template
     * @param int $num
     * @param int $parameter 单个商品的重量或者体积
     * @param int $freight_type
     * @return float|int
     */
    protected function express_fee_count($template,$num, $parameter=1,$freight_type){
        if(empty($template)||empty($num)) return 0;
        if(empty($template['first_freight'])){//这个是老数据，不支持首件+续件的计费方式。
            return $template['freight'];
        }
        if(1 == $freight_type && $num<=$template['first_weight']){
            return $template['first_freight'];
        } elseif(2 == $freight_type && ($num * $parameter)<=$template['first_weight']){
            return $template['first_freight'];
        } elseif(3 == $freight_type){
            return $num * $parameter * $template['freight'];
        }
        //运费计算公式：首件运费+续件运费（向上取整）
        if(1 == $freight_type && $num<$template['first_weight']){
            $fee = $template['first_freight']+floatval(($num-$template['first_weight'])/$template['add_weight'])*$template['add_freight'];
        } else {
            $fee = $template['first_freight']+floatval((($num * $parameter)-$template['first_weight'])/$template['add_weight'])*$template['add_freight'];
        }
        return $fee;
    }

    // 计算结算个对应商家
    public function order_settlement($order = array(), $order_id=0) {
        if (!$order && !$order_id) {
            return false;
        }
        if (empty($order) && $order_id > 0) {
            $where = array('order_id' => $order_id);
            $order = D('Merchant_integral_mall_goods_order')->field(true)->where($where)->find();
        }

        // 判断如果是商家承担运费，从商家余额中对应扣除
        $merchant_integral_mall_goods = M('Merchant_integral_mall_goods')->where(array('goods_id' => $order['goods_id']))->find();

        // 查询下给真实商家加钱
        if ($merchant_integral_mall_goods['virtual_wholesale_mer_id']>0 && ($merchant_integral_mall_goods['virtual_wholesale_goods_id']>0 || $merchant_integral_mall_goods['market_goods_id']>0)) {
            $order_info = M('Market_order')->where(array('integral_mall_goods_order_id' => $order['order_id'],'type' => 1))->find();
            // 给商户加钱  原价 + 运费  实时计算价格 根据真实商家的价格计算
            $merchant_info = M('Merchant')->where(array('mer_id'=>$order['mer_id']))->find();
            if ($order_info){
                // 给商户加钱  直接取对应批发订单
                $total_money = floatval($order_info['actual_money']);
                if ($order['freight_charge'] > 0) {
                    // 查询下给真实商家加钱
                    $total_money += $order['freight_charge'];
                }
            } else {
                $is_vip = false;
                if ($merchant_info['type_id']>0) {
                    $merchant_type = M('Merchant_type')->where(array('type_id'=>$merchant_info['type_id']))->find();
                    $is_vip = (1==$merchant_type['is_vip'])?true:false;
                }
                $spec_index = $order['spec_index'];
                $databaseMarketGoods = D('Market_goods');
                $virtual_goods_id = $merchant_integral_mall_goods['virtual_wholesale_goods_id']>0 ? $merchant_integral_mall_goods['virtual_wholesale_goods_id'] : $merchant_integral_mall_goods['market_goods_id'];
                $where = array('goods_id' => $virtual_goods_id);
                $market_goods = $databaseMarketGoods->field(true)->where($where)->find();
                $num = $order['num'];
                $money = 0;
                if ($market_goods['spec_value'] && $spec_index) {
                    $spec_ret = $databaseMarketGoods->format_spec_value($market_goods['spec_value'], $market_goods['goods_id']);
                    $spec_list = isset($spec_ret['spec_list']) ? $spec_ret['spec_list'] : '';
                    $list = isset($spec_ret['list']) ? $spec_ret['list'] : '';
                    if ($spec_list && $list && $list[$spec_index]) {
                        $spec_goods = $list[$spec_index];
                        if ($is_vip) {
                            $price = $spec_goods['vip_wholesale_price'] ? floatval($spec_goods['vip_wholesale_price']) : floatval($spec_goods['price']);
                            $money = $price * $num;
                        } else {
                            $price = floatval($spec_goods['price']);
                            $money = $price * $num;
                        }
                    }
                } else {
                    if ($is_vip) {
                        $price = $market_goods['vip_wholesale_price'] ? floatval($market_goods['vip_wholesale_price']) : floatval($market_goods['price']);
                        $money = $price * $num;
                    } else {
                        $price = floatval($market_goods['price']);
                        $money = $price * $num;
                    }
                }
                if ($order['freight_charge'] > 0) {
                    // 查询下给真实商家加钱
                    $total_money = $money + floatval($order['freight_charge']);
                } else {
                    $total_money = $money;
                }
            }

            $desc = L_('商户X1批发-积分商城进货',array('X1'=>$merchant_info['name']));
            $now_order['money'] = $total_money;
            $now_order['order_type'] = 'market_integral_mall';
            $now_order['order_id'] = $order['order_id'];
            $now_order['mer_id']  = $merchant_integral_mall_goods['virtual_wholesale_mer_id'];
            $now_order['desc'] = $desc;
            D('SystemBill')->bill_method(0,$now_order);
        } elseif ($order['freight_charges'] != 1 && $order['freight_charge'] > 0 && $order['is_pick_in_store'] == 1) {
            $now_order['money'] = $order['total_money'];
            $now_order['order_type'] = 'market_integral_mall';
            $now_order['order_id'] = $order['order_id'];
            $now_order['mer_id']  = $order['mer_id'];
            $now_order['desc'] = L_("用户兑换X1商品获得配送费",array("X1" => $order['goods_name']));
            D('SystemBill')->bill_method(0,$now_order);
        }
        return true;
    }



}