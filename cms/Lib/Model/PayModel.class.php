<?php
class PayModel extends Model{
	public function __construct() {}
	/*根据 支付平台的英文 和 是否移动端支付 得到中文名称*/
	public function get_pay_name($pay_type,$is_mobile_pay=1, $paid = 1, $order_id = '', $order_info = [],$order_type=''){
	    fdump($pay_type, 'pay_name', 1);
        $pay_type_txt = '';
        switch($pay_type){
			case 'alipay':
				$pay_type_txt = L_('支付宝WAP支付');
				break;
			case 'alipayh5':
				$pay_type_txt = L_('支付宝WAP支付');
				break;
			case 'tenpay':
				$pay_type_txt = L_('财付通');
				break;
			case 'yeepay':
				$pay_type_txt = L_('易宝支付');
				break;
			case 'allinpay':
				$pay_type_txt = L_('通联支付');
				break;
			case 'chinabank':
				$pay_type_txt = L_('网银在线');
				break;
            case 'weixin':
            case 'wechat';
                $pay_type_txt = L_('微信支付');
                break;
            case 'weixinh5':
                $pay_type_txt = L_('微信WAP支付');
                break;
			case 'baidu':
				$pay_type_txt = L_('百度钱包');
				break;
			case 'unionpay':
				$pay_type_txt = L_('银联支付');
				break;
			case 'weifutong':
				$pay_type_txt = C('config.pay_weifutong_alias_name');
				break;
			case 'offline':
				$pay_type_txt = L_('线下付款');
				break;
			case 'ccb':
				$pay_type_txt = L_('建设银行');
				break;
			case 'yzfpay':
				$pay_type_txt = L_('翼支付');
				break;
            case 'yzfpay_offline':
                $pay_type_txt = L_('翼支付线下');
                break;
			case 'wftpay':
				$pay_type_txt = L_('网付通');
				break;
            case 'allinpay_mer':
                $pay_type_txt = L_('通联支付');
                break;
			case 'merchantwarrior':
                $pay_type_txt = L_('信用卡支付');
                break;
            case 'nmgpay':
                $pay_type_txt = L_('在线支付');
                break;
            case 'qiye':
                $pay_type_txt = L_('企业预付款支付');
                break;
            case 'hqpay':
                $pay_type_txt = L_('环球汇通支付');
                break;
            case 'hqpay_wx':
                $pay_type_txt = L_('环球汇通微信支付');
                break;
            case 'hqpay_al':
                $pay_type_txt = L_('环球汇通支付宝支付');
                break;
            case 'douyin':
                $pay_type_txt = L_('抖音支付');
                break;
            case 'scrcu_cash':
                $pay_type_txt = L_('四川农信');
                break;
            case 'farmersbankpay':
                $pay_type_txt = L_('仪征农商行');
                break;
            case 'icbc':
                $pay_type_txt = L_('聚合支付');
                break;
            case 'ecard':
                $pay_type_txt = L_('一卡通支付');
                break;
			default:
				if (!$paid) {
                    return L_('未支付');
                }
				break;
		}

        if ($pay_type != 'ecard' && isset($order_info['internal_card_pay']) && $order_info['internal_card_pay'] && $order_info['internal_card_pay'] != '[]') {
            $pay_type_txt .= ' ' . L_('一卡通支付');
        }

		if (isset($order_info['balance_pay']) && $order_info['balance_pay'] > 0) {
            $pay_type_txt .= ' ' . L_('余额支付');
        }

        if (isset($order_info['card_give_money']) && $order_info['card_give_money'] > 0) {
            $pay_type_txt .= ' ' . L_('商家会员卡支付');
        }

        if (isset($order_info['coupon_price']) && $order_info['coupon_price'] > 0) {
            $pay_type_txt .= ' ' . L_('平台优惠券');
        }

        if (isset($order_info['card_price']) && $order_info['card_price'] > 0) {
            $pay_type_txt .= ' ' . L_('商家优惠券');
        }
        if($order_info['employee_score_pay'] > 0){
            $pay_type_txt .= ' ' . L_('员工卡积分支付');
        }
        if($order_info['employee_balance_pay'] > 0){
            $pay_type_txt .= ' ' . L_('员工卡余额支付');
        }
		if($is_mobile_pay == 1 && $pay_type != 'alipayh5'){
			$pay_type_txt .= '('.L_('微信端').')';
		} elseif ($is_mobile_pay == 2) {
			$pay_type_txt .= '(App)';
		}elseif ($is_mobile_pay == 3) {
			$pay_type_txt .= '('.L_('小程序').')';
		}
        if ($order_id) {
            if($order_type){
                $pay_method = D('Payment_money_order')->where(['order_id'=>$order_id,'order_type'=>$order_type,'paid_money'=>['gt',0]])->getField('pay_method');
                $pay_method && $pay_type_txt .= '(' . $pay_method . ')';
            }else{
                $harkOrder = M('Hake_credit_order')->where(['order_id' => $order_id])->find();
                if ($harkOrder) {
                    $pay_type_txt .= ($harkOrder['is_redirect'] == 1 ? '(3DS支付)' : '(Token支付)');
                }
            }
        }
		return $pay_type_txt;
	}

    /**
     * 订单支付明细
     * @param $order
     * @return array
     */
    public function payTypeMoneyDetail($order): array
    {
        $payType = [
            'weixin' => ['type' => 'weixin', 'text' => '微信支付', 'pay_money' => 0],
            'alipay' => ['type' => 'alipay', 'text' => '支付宝支付', 'pay_money' => 0],
            'baidu' => ['type' => 'baidu', 'text' => '百度钱包支付', 'pay_money' => 0],
            'unionpay' => ['type' => 'unionpay', 'text' => '银联支付', 'pay_money' => 0],
            'ecard' => ['type' => 'ecard', 'text' => '一卡通支付', 'pay_money' => 0],
            'balance_pay' => ['type' => 'balance_pay', 'text' => '平台余额支付', 'pay_money' => 0],
            'card_give_money' => ['type' => 'card_give_money', 'text' => '会员卡赠送余额支付', 'pay_money' => 0],
        ];
        switch ($order['pay_type']){
            case 'weixin':
            case 'wechat':
            case 'weixinh5':
                $payType['weixin']['pay_money'] = $order['payment_money'];
                break;
            case 'alipay':
            case 'alipayh5':
                $payType['alipay']['pay_money'] = $order['payment_money'];
                break;
            case 'baidu':
                $payType['baidu']['pay_money'] = $order['payment_money'];
        }
        if (!empty($order['internal_card_pay'])) {
            $internalCardPayMoney = 0;
            $internalCardPay = @json_decode($order['internal_card_pay'], true);
            if (is_array($internalCardPay)) {
                $internalCardPayMoney = array_sum($internalCardPay);
            }
            $payType['ecard']['pay_money'] = $internalCardPayMoney;
        }

        foreach ($payType as $key => $value) {
            if (empty($value['pay_money'])) {
                unset($payType[$key]);
            }
        }
        return array_values($payType);
    }
	public function getPayName($label){
		$payName = array(
				'weixin' => L_('微信支付'),
				'tenpay' => L_('财付通支付'),
				'yeepay' => L_('银行卡支付(易宝支付)'),
				'allinpay' => L_('银行卡支付(通联支付)'),
				'chinabank' => L_('银行卡支付(网银在线)'),
				'weifutong' => C('config.pay_weifutong_alias_name'),
				'yzfpay' => L_("翼支付"),
				'allinpay_mer' => L_("通联支付"),
				'merchantwarrior' => 'MW',
		);
		return $payName[$label];
	}
	//跳转链接
    public function back_url($order_type,$now_order,$is_wap=1){
        switch($order_type){
            case 'group':
                $now_order =$this->get_orderid('Group_order',$now_order['order_id']);
                break;
            case 'meal':
                $now_order =$this->get_orderid('Meal_order',$now_order['order_id']);
                break;
            case 'takeout':
            case 'food':
            case 'foodPad':
                $now_order =$this->get_orderid('Meal_order',$now_order['order_id']);
                break;
            case 'weidian':
                $now_order = D('Weidian_order')->where(array('orderid'=>$now_order['order_id']))->find();
                $now_order =$this->get_orderid('Weidian_order',$now_order['order_id']);
                break;
            case 'recharge':
                $now_order =$this->get_orderid('User_recharge_order',$now_order['order_id']);
                break;
            case 'appoint':
                $now_order =$this->get_orderid('Appoint_order',$now_order['order_id']);
                break;
            case 'waimai':
                $now_order =$this->get_orderid('Waimai_order',$now_order['order_id']);
                break;
            case 'wxapp':
                $now_order =$this->get_orderid('Wxapp_order',$now_order['order_id']);
                break;
            case 'store':
                $now_order =$this->get_orderid('Store_order',$now_order['order_id']);
                break;
            case 'shop':
            case 'mall':
                $now_order = $this->get_orderid('Shop_order', $now_order['order_id']);
                break;
            case 'plat':
                $now_order = $this->get_orderid('Plat_order', $now_order['order_id']);
                break;
            case 'balance-appoint':
                $now_order = $this->get_orderid('Appoint_order', $now_order['order_id']);
                break;
            default:
                exit(L_('非法的订单'));
        }


        if(empty($now_order)){
            exit(L_('该订单不存在'));
        }
        if($is_wap){
            switch($order_type){
                case 'group':
                    $redirctUrl = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$now_order['order_id'];
                    break;
                case 'meal':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'takeout':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'food':
                case 'foodPad':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'weidian':
                    $redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$now_order['third_id'],'payment_method'=>$now_order['pay_type']));
                    break;
                case 'appoint':
                    $redirctUrl = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$now_order['order_id'];
                    break;
                case 'waimai':
                    $redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$now_order['order_id'];
                    break;
                case 'recharge':
                    $redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
                    break;
                case 'wxapp':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
                    break;
                case 'store':
                    $redirctUrl = C('config.site_url').'/wap.php?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
                    break;
                case 'shop':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Shop&a=status&order_id=' . $now_order['order_id'];
                    break;
                case 'mall':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Mall&a=status&order_id=' . $now_order['order_id'];
                    break;
                case 'plat':
                    $redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
                    break;
                case 'balance-appoint':
                    $redirctUrl = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$now_order['order_id'];
                    break;
                case 'spread':
                    $redirctUrl = C('config.site_url') . '/wap.php?c=My&a=my_spread';
                    break;
            }

        }else{
            switch($order_type){
                case 'group':
                    $redirctUrl = C('config.site_url').'/index.php?g=User&c=Index&a=group_order_view&order_id='.$now_order['order_id'];
                    break;
                case 'meal':
                case 'takeout':
                case 'food':
                    $redirctUrl = C('config.site_url').'/index.php?g=User&c=Index&a=meal_order_view&order_id='.$now_order['order_id'];
                    break;
                case 'recharge':
                    $redirctUrl = C('config.site_url').'/index.php?g=User&c=Credit&a=index';
                    break;
                case 'appoint':
                    $redirctUrl = C('config.site_url').'/index.php?g=User&c=Index&a=appoint_order_view&order_id='.$now_order['order_id'];
                    break;
                case 'waimai':
                    $redirctUrl = C('config.site_url').'/index.php?g=Waimai&c=Order&a=detail&order_id='.$now_order['order_id'];
                    break;
                case 'shop':
                    $redirctUrl = C('config.site_url').'/index.php?g=User&c=Index&a=shop_order_view&order_id='.$now_order['order_id'];
                    break;
            }

        }
        return  $redirctUrl;
    }
    public function get_orderid($table,$orderid,$offline=0){
        $order =  D($table);
        $tmp_orderid = D('Tmp_orderid');
        if($offline){
            $now_order = $order->where(array('orderid'=>$orderid))->find();
        }else{
            $now_order = $order->where(array('orderid'=>$orderid))->find();
            if(empty($now_order)){
                $res = $tmp_orderid->where(array('orderid'=>$orderid))->find();
                $now_order = $order->where(array('order_id'=>$res['order_id']))->find();
                $order->where(array('order_id'=>$res['order_id']))->setField('orderid',$orderid);
                $now_order['orderid']=$orderid;
            }
        }
        if(empty($now_order)){
            return false;
        }
        return $now_order;
    }


    //v20使用
    public function go_pay_v20($name)
    {
        // fdump($name, 'go_pay_v20', 1);
        $pay_method = D('Config')->get_pay_method();
        if(empty($pay_method)){
            return ['error' => 1, 'mag' => '系统管理员没开启任一一种支付方式'];
        }
        if(empty($pay_method[$name['pay_check']])){
            return ['error' => 1, 'mag' => '您选择的支付方式不存在，请更新支付方式！'];
        }
        $pay_class_name = ucfirst($name['pay_check']);
        $import_result = import('@.ORG.pay.' . $pay_class_name);
        if (empty($import_result)) {
            return ['error' => 1, 'mag' => '支付方式有误'];
        }
        $name['orderData']['is_ajax'] = 1;
        $pay_class = new $pay_class_name($name['orderData'], $name['pay_money'], $name['pay_check'], $pay_method[$name['pay_check']]['config'], $name['merchantUser'], $name['is_mobile']);
        $go_pay_param = $pay_class->pay(); 

        $go_pay_param['is_own'] = 0;

        if (!empty($go_pay_param['weixin_param'])) {// 手机端微信支付
            $go_pay_param['is_own'] = $pay_method['weixin']['config']['is_own'];

            if ($pay_method['weixin']['config']['is_own']) {
                C('open_authorize_wxpay', true);
                $share = new WechatShare(C('config'), $_SESSION['openid']);
                $hideScript = $share->gethideOptionMenu($name['merchantUser']['mer_id']);
                $go_pay_param['hidScript'] = $hideScript;
            } 
            $go_pay_param['weixin_param'] = json_decode($go_pay_param['weixin_param']);
        }

        fdump($go_pay_param, 'go_pay_v20', 1);
        return $go_pay_param;
    }


    //跳转链接
    public function getBackUrl($order_type,$now_order,$is_wap=1){
        if($is_wap){
            switch($order_type){
                case 'group':
                    $redirctUrl = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$now_order['order_id'];
                    break;
                case 'meal':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'takeout':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'food':
                case 'foodPad':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
                    break;
                case 'weidian':
                    $redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$now_order['third_id'],'payment_method'=>$now_order['pay_type']));
                    break;
                case 'appoint':
                    $redirctUrl = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$now_order['order_id'];
                    break;
                case 'waimai':
                    $redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$now_order['order_id'];
                    break;
                case 'recharge':
                    $redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
                    break;
                case 'wxapp':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
                    break;
                case 'store':
                    $redirctUrl = C('config.site_url').'/wap.php?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
                    break;
                case 'shop':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Shop&a=status&order_id=' . $now_order['order_id'];
                    break;
                case 'mall':
                    $redirctUrl = C('config.site_url').'/wap.php?c=Mall&a=status&order_id=' . $now_order['order_id'];
                    break;
                case 'plat':
                    $redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
                    break;
                case 'balance-appoint':
                    $redirctUrl = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$now_order['order_id'];
                    break;
            }

        }else{
            switch($order_type){
                case 'group':
                    $redirctUrl = C('config.site_url').'/index.php?g=User&c=Index&a=group_order_view&order_id='.$now_order['order_id'];
                    break;
                case 'meal':
                case 'takeout':
                case 'food':
                    $redirctUrl = C('config.site_url').'/index.php?g=User&c=Index&a=meal_order_view&order_id='.$now_order['order_id'];
                    break;
                case 'recharge':
                    $redirctUrl = C('config.site_url').'/index.php?g=User&c=Credit&a=index';
                    break;
                case 'appoint':
                    $redirctUrl = C('config.site_url').'/index.php?g=User&c=Index&a=appoint_order_view&order_id='.$now_order['order_id'];
                    break;
                case 'waimai':
                    $redirctUrl = C('config.site_url').'/index.php?g=Waimai&c=Order&a=detail&order_id='.$now_order['order_id'];
                    break;
                case 'shop':
                    $redirctUrl = C('config.site_url').'/index.php?g=User&c=Index&a=shop_order_view&order_id='.$now_order['order_id'];
                    break;
            }

        }
        return  $redirctUrl;
    }
    
	//获得订单详情
    public function getOnderInfo($order_type,$order_id){
        switch($order_type){
            case 'group':
                $now_order = D('Group_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'meal':
                $now_order = D('Meal_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'takeout':
            case 'food':
            case 'foodPad':
                $now_order = D('Meal_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'weidian':
                $now_order = D('Weidian_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'recharge':
                $now_order = D('User_recharge_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'appoint':
                $now_order = D('Appoint_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'balance-appoint':
                $now_order = D('Appoint_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'waimai':
                $now_order = D('Waimai_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'wxapp':
                $now_order = D('Wxapp_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'store':
                $now_order = D('Store_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'shop':
            case 'mall':
                $now_order = D('Shop_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'plat':
                $now_order = D('Plat_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'integral_mall':
                $now_order = D('Merchant_integral_mall_goods_order')->where(array('orderid'=>$order_id))->find();
                break;
        }
        return  $now_order;
    }

    public function after_pay($order_type, $go_query_param, $offline) {
        $pay_info = [];
        if($order_type == 'group'){
            $now_order = $this->get_orderid('Group_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('Group_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'meal' || $order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad'){
            $now_order = $this->get_orderid('Meal_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('Meal_order')->after_pay($go_query_param['order_param'], $order_type);
        }else if($order_type == 'weidian'){
            $now_order = $this->get_orderid('Weidian_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('Weidian_order')->after_pay($go_query_param['order_param']);
            if(($pay_info['error']==0) && isset($pay_info['url']) && ($go_query_param['order_param']['pay_type'] == 'weixin')){  /***异步通知***/
                $this->NoticeWDAsyn($go_query_param['order_param']['order_id']);
            }
        }else if($order_type == 'recharge'){
            $now_order = $this->get_orderid('User_recharge_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('User_recharge_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'appoint'){
            $now_order = $this->get_orderid('Appoint_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            D('Appoint_order')->after_pay($go_query_param['order_param']);
            // $pay_info = D('Appoint_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'balance-appoint'){
            $now_order = $this->get_orderid('Appoint_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            D('Appoint_order')->balance_after_pay($go_query_param['order_param']);
        }else if($order_type == 'waimai'){
            $now_order = $this->get_orderid('Waimai_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('Waimai_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'wxapp'){
            $now_order = $this->get_orderid('Wxapp_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('Wxapp_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'store'){
            $now_order = $this->get_orderid('Store_order',$go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id']=$now_order['orderid'];
            $pay_info = D('Store_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'shop' || $order_type == 'mall'){
            $now_order = $this->get_orderid('Shop_order', $go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id'] = $now_order['orderid'];
            $pay_info = D('Shop_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'plat'){
            $now_order = $this->get_orderid('Plat_order', $go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id'] = $now_order['orderid'];
            $pay_info = D('Plat_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'integral_mall'){
            $now_order = $this->get_orderid('Merchant_integral_mall_goods_order', $go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id'] = $now_order['orderid'];
            $pay_info = D('Merchant_integral_mall_goods_order')->after_pay($go_query_param['order_param']);
        }else if($order_type == 'mcard'){
            $now_order = $this->get_orderid('Membership_card_order', $go_query_param['order_param']['order_id'],$offline);
            $go_query_param['order_param']['order_id'] = $now_order['orderid'];
            $pay_info = D('Membership_card_order')->after_pay($go_query_param['order_param']);
        }
        return $pay_info;
    }
    
    public function weixinV3Pay($param)
    {
        //开启了V3支付并且设置了V3秘钥
        if(empty(C('config.withdraw_open_apiv3')) || empty(C('config.withdraw_weixin_apiv3'))){
            throw_exception('未开启V3支付或者未设置V3秘钥');
        }
        import('@.ORG.withdraw.WeixinPayV3');
        
        $result = (new WeixinPayV3())->withdraw($param);
        if($result['error']){
            throw_exception($result['result']['err_code_des']);
        }
        return $result;
    }
}

?>