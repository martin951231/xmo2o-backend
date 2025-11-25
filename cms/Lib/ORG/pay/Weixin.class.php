<?php
class Weixin{
	protected $order_info;
	protected $pay_money;
	protected $pay_type;
	protected $is_mobile;
	protected $pay_config;
	protected $user_info;
	
	public function __construct($order_info,$pay_money,$pay_type,$pay_config,$user_info,$is_mobile=0){
		$this->order_info = $order_info;
		$this->pay_money  = $pay_money;
		$this->pay_type   = $pay_type;
		$this->is_mobile   = $is_mobile;
		$this->pay_config = $pay_config;
		$this->user_info  = $user_info;
	}
	public function pay($ticket=null, $deviceId=null){
		if(empty($this->pay_config['pay_weixin_appid']) || empty($this->pay_config['pay_weixin_mchid']) || empty($this->pay_config['pay_weixin_key']) || empty($this->pay_config['pay_weixin_appsecret'])){
			return array('error'=>1,'msg'=>'微信支付缺少配置信息！请联系管理员处理或选择其他支付方式。');
		}
		if($this->is_mobile == 2){
			return $this->app_pay($ticket, $deviceId);
		}elseif($this->is_mobile){
			return $this->mobile_pay();
		}else{
			return $this->web_pay();
		}
	}
	public function web_pay(){
		import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');
		//使用jsapi接口
		$jsApi = new JsApi_pub($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);
		//使用统一支付接口
		$unifiedOrder = new UnifiedOrder_pub($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);
		$bodyStr = mb_substr(str_replace(' ', '',($this->pay_config['is_own'] ? '' : '').'订单号：'.$this->order_info['order_type'].'_'.$this->order_info['order_id']), 0, 50);
		$unifiedOrder->setParameter("body", $bodyStr);//商品描述
		//自定义订单号，此处仅作举例
		$unifiedOrder->setParameter("out_trade_no",$this->order_info['order_type'].'_'.$this->order_info['order_id'].($this->pay_config['is_own'] ? '_1' : ''));//商户订单号 
		$unifiedOrder->setParameter("total_fee",floatval($this->pay_money*100));//总金额
		if($this->pay_config['sub_mch_id']){
			$unifiedOrder->setParameter("sub_mch_id",$this->pay_config['sub_mch_id']);
			$unifiedOrder->setParameter("openid",$_SESSION['openid']);
		}else{
			//if($this->order_info['order_type']!='merrecharge') {
			//	$unifiedOrder->setParameter("openid", ($this->pay_config['is_own'] ? $_SESSION['open_authorize_openid'] : $_SESSION['openid']));//用户微信唯一标识
			//}
		}
		
		if($this->pay_config['open_weixin_profit_sharing']){// 开启分账
			$unifiedOrder->setParameter("profit_sharing", 'Y');
		}

		if($this->order_info['order_type']=='merrecharge' || $this->order_info['order_type']=='market' || $this->order_info['order_type']=='markets' || $this->order_info['order_type']=='market_total' || $this->order_info['order_type']=='auth' || $this->order_info['order_type']=='newmarket'){
			$unifiedOrder->setParameter("notify_url",C('config.site_url').'/source/web_weixin_mer_notice.php');//通知地址
		}elseif($this->order_info['order_type']=='sqrecharge'){
			$unifiedOrder->setParameter("notify_url",C('config.site_url').'/source/web_weixin_sq_notice.php');//通知地址
		}elseif($this->order_info['order_type']=='strecharge'){
			$unifiedOrder->setParameter("notify_url",C('config.site_url').'/source/web_weixin_st_notice.php');//通知地址
		}else if($this->order_info['order_type']=='ticket' || $this->order_info['order_type']=='guide'){
			$unifiedOrder->setParameter("notify_url",C('config.site_url').'/source/web_weixin_jq_notice.php');//通知地址
		}else{
			$unifiedOrder->setParameter("notify_url",C('config.site_url').'/source/web_weixin_notice.php');//通知地址
		}
		$unifiedOrder->setParameter("trade_type","NATIVE");//交易类型
		$unifiedOrder->setParameter("attach",'weixin');//附加数据
		
		//充值余额不允许使用信用卡，防止套现
		if($this->order_info['order_type'] == 'recharge'){
			$unifiedOrder->setParameter("limit_pay", 'no_credit');
		}
		
		$prepay_result = $unifiedOrder->getPrepayId();
		if($prepay_result['return_code'] == 'FAIL'){
			return array('error'=>1,'msg'=>'没有获取微信支付的预支付ID，请重新发起支付！<br/><br/>微信支付错误返回：'.$prepay_result['return_msg']);
		}
		if($prepay_result['err_code']){
			return array('error'=>1,'msg'=>'没有获取微信支付的预支付ID，请重新发起支付！<br/><br/>微信支付错误返回：'.$prepay_result['err_code_des']);
		}
		//=========步骤3：得到微信的二维码============
		$jsApi->setPrepayId($prepay_result['prepay_id']);
		
		return array('error'=>0,'qrcode'=>$prepay_result['code_url']);
	}
	public function mobile_pay(){
		import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');        
		// $_SESSION['openid'] = 'oG77RjuxJoRFB6nEXOto9Cj383d0';
		//使用jsapi接口
		$jsApi = new JsApi_pub($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);
		//使用统一支付接口
		$unifiedOrder = new UnifiedOrder_pub($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);	
		$unifiedOrder->setParameter("openid",($this->pay_config['is_own'] ? $_SESSION['open_authorize_openid'] : $_SESSION['openid']));//用户微信唯一标识
		$bodyStr = mb_substr(str_replace(' ', '',($this->pay_config['is_own'] ? '' : '').$this->order_info['order_name'].($this->order_info['order_num'] > 0 ? '_'.$this->order_info['order_num'] : '')), 0, 50);
		$unifiedOrder->setParameter("body", $bodyStr);//商品描述
		//服务商子商户支付
		if($this->pay_config['sub_mch_id']){
		    $openid = $_SESSION['openid'];
			$unifiedOrder->setParameter("sub_mch_id",$this->pay_config['sub_mch_id']);
			$unifiedOrder->setParameter("openid",$openid);
		}else{
            $openid = $this->pay_config['is_own'] ? $_SESSION['open_authorize_openid'] : $_SESSION['openid'];
			$unifiedOrder->setParameter("openid",$openid);//用户微信唯一标识
		}

		if($this->pay_config['open_weixin_profit_sharing']){// 开启分账
			$unifiedOrder->setParameter("profit_sharing", 'Y');
		}
		
        if (empty($openid)) {
            return array('error' => 1, 'msg' => '微信openid获取失败，请重新登录');
        }
		//自定义订单号，此处仅作举例
		$unifiedOrder->setParameter("out_trade_no",$this->order_info['order_type'].'_'.$this->order_info['order_id'].($this->pay_config['is_own'] ? '_'.$this->pay_config['is_own'] : ''));//商户订单号
		$unifiedOrder->setParameter("total_fee",floatval($this->pay_money*100));//总金额
		if($this->order_info['DEVICE_ID']=='wxcapp'){
			$unifiedOrder->setParameter("notify_url",C('config.site_url').'/source/wxcapp_weixin_notice.php');//通知地址
		}else if($this->order_info['DEVICE_ID']=='wxapp_paotui'){
			$unifiedOrder->setParameter("notify_url",C('config.site_url').'/source/wxapp_paotui_weixin_notice.php');//通知地址
		}else if($this->is_mobile==3){
			$unifiedOrder->setParameter("notify_url",C('config.site_url').'/source/wxapp_weixin_notice.php');//通知地址
		}else{
			$unifiedOrder->setParameter("notify_url",C('config.site_url').'/source/wap_weixin_notice.php');//通知地址
		}
		$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
		
		//充值余额不允许使用信用卡，防止套现
		if($this->order_info['order_type'] == 'recharge'){
			$unifiedOrder->setParameter("limit_pay", 'no_credit');
		}
		if($this->pay_config['is_own'] && $this->order_info['mer_id']){
			$unifiedOrder->setParameter("attach",$this->order_info['mer_id']);//附加数据
		}elseif($this->pay_config['is_own'] && $this->order_info['order_info']['village_id']){
		    $attach=$this->order_info['order_info']['village_id'];
            $house_village=D('House_village')->where(array('village_id'=>$this->order_info['order_info']['village_id']))->field('village_id,village_name')->find();
            if(!empty($house_village)){
                $attach=htmlspecialchars($house_village['village_name'],ENT_QUOTES);
            }
            $unifiedOrder->setParameter("attach",$attach);//附加数据
        }else{
			$unifiedOrder->setParameter("attach",'weixin');//附加数据
		}
		
		$prepay_result = $unifiedOrder->getPrepayId();
		if($prepay_result['return_code'] == 'FAIL'){
			fdump_sql([$unifiedOrder, $prepay_result, $_SERVER, $_POST, $_SESSION], 'weixin_get_prepayid_fail');
			return array('error'=>1,'msg'=>'没有获取微信支付的预支付ID，请重新发起支付！微信支付错误返回：'.$prepay_result['return_msg']);
		}
		if($prepay_result['err_code']){
			fdump_sql([$unifiedOrder, $prepay_result, $_SERVER, $_POST, $_SESSION], 'weixin_get_prepayid_fail');
			return array('error'=>1,'msg'=>'没有获取微信支付的预支付ID，请重新发起支付！<br/><br/>微信支付错误返回：'.$prepay_result['err_code_des']);
		}
		//=========步骤3：使用jsapi调起支付============
		$jsApi->setPrepayId($prepay_result['prepay_id']);
		
		return array('error'=>0,'weixin_param'=>$jsApi->getParameters());
	}

	public function app_pay($ticket, $deviceId){
		import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');
		//使用jsapi接口
		$jsApi = new JsApi_pub($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);
		//使用统一支付接口
		$unifiedOrder = new UnifiedOrder_pub($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);
		$bodyStr = mb_substr(str_replace(' ', '',($this->pay_config['is_own'] ? '' : '').$this->order_info['order_name'].($this->order_info['order_num'] > 0 ? '_'.$this->order_info['order_num'] : '')), 0, 50);
		$unifiedOrder->setParameter("body", $bodyStr);//商品描述
		//自定义订单号，此处仅作举例
		$unifiedOrder->setParameter("out_trade_no",$this->order_info['order_type'].'_'.$this->order_info['order_id'].($this->pay_config['is_own'] ? '_1' : ''));//商户订单号
		$unifiedOrder->setParameter("total_fee",floatval($this->pay_money*100));//总金额
		$unifiedOrder->setParameter("notify_url",C('config.site_url').'/source/appapi_weixin_notice.php');//通知地址
		$unifiedOrder->setParameter("trade_type","APP");//交易类型
		
		//充值余额不允许使用信用卡，防止套现
		if($this->order_info['order_type'] == 'recharge'){
			$unifiedOrder->setParameter("limit_pay", 'no_credit');
		}
		if($this->pay_config['is_own'] && $this->order_info['mer_id']){
			$unifiedOrder->setParameter("attach",$this->order_info['mer_id']);//附加数据
		}elseif($this->pay_config['is_own'] && $this->order_info['order_info']['village_id']){
            $attach=$this->order_info['order_info']['village_id'];
            $house_village=D('House_village')->where(array('village_id'=>$this->order_info['order_info']['village_id']))->field('village_id,village_name')->find();
            if(!empty($house_village)){
                $attach=htmlspecialchars($house_village['village_name'],ENT_QUOTES);
            }
            $unifiedOrder->setParameter("attach",$attach);//附加数据
        }else{
			$unifiedOrder->setParameter("attach",'weixin');//附加数据
		}
		
		$prepay_result = $unifiedOrder->getPrepayId();
		if($prepay_result['return_code'] == 'FAIL'){
			return array('error'=>1,'msg'=>'没有获取微信支付的预支付ID，请重新发起支付！微信支付错误返回：'.$prepay_result['return_msg']);
		}
		if($prepay_result['err_code']){
			return array('error'=>1,'msg'=>'没有获取微信支付的预支付ID，请重新发起支付！<br/><br/>微信支付错误返回：'.$prepay_result['err_code_des']);
		}
		$prepay_result['timestamp'] = time();
		$prepay_result['package'] = "Sign=WXPay";
		//=========步骤3：重新生成app签名============
		$sign = $unifiedOrder->getPrepayAppSign($prepay_result);
		$return = [
			'appid' => $prepay_result['appid'],
			'partnerid' => $prepay_result['mch_id'],
			'prepayid' => $prepay_result['prepay_id'],
			'noncestr' => $prepay_result['nonce_str'],
			'timestamp' => $prepay_result['timestamp'],
			'package' => $prepay_result['package'],
			'sign' => $sign
		];
		return array('error'=>0,'sign'=>$return);
	}

	public function notice_url(){
		if(empty($this->pay_config['pay_weixin_appid']) || empty($this->pay_config['pay_weixin_mchid']) || empty($this->pay_config['pay_weixin_key']) || empty($this->pay_config['pay_weixin_appsecret'])){
			return array('error'=>1,'msg'=>'微信支付缺少配置信息！请联系管理员处理或选择其他支付方式。');
		}
		if($this->is_mobile){
			return $this->mobile_notice();
		}else{
			return $this->web_notice();
		}
	}
	public function mobile_notice(){
		exit('success');
	}
	public function web_notice(){
		exit('success');
	}
	public function return_url(){
		@libxml_disable_entity_loader(true);
		if(empty($this->pay_config['pay_weixin_appid']) || empty($this->pay_config['pay_weixin_mchid']) || empty($this->pay_config['pay_weixin_key']) || empty($this->pay_config['pay_weixin_appsecret'])){
			return array('error'=>1,'msg'=>'微信支付缺少配置信息！请联系管理员处理或选择其他支付方式。');
		}
		
		import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');
		//使用通用通知接口
		$notify = new Notify_pub($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);
		//存储微信的回调
		$xml = file_get_contents("php://input");
        fdump_api($xml,'000weixinreturn_url',1);
		$notify->saveData($xml);
		D('Pay_async_record')->weixin_record($notify->data,$xml);
		//验证签名，并回应微信。
		if($notify->checkSign() == FALSE){
			$notify->setReturnParameter("return_code","FAIL");//返回状态码
			$notify->setReturnParameter("return_msg","签名失败");//返回信息
			return array('error'=>1,'msg'=>$notify->returnXml());
		}else{
			$notify->setReturnParameter("return_code","SUCCESS");//设置返回码
			
			if($notify->data['return_code']=='SUCCESS' && $notify->data['result_code']=='SUCCESS'){
				$order_id_arr = explode('_',$notify->data['out_trade_no']);
				$order_param['pay_type'] = 'weixin';
				$order_param['is_mobile'] = $this->is_mobile;
				$order_param['order_type'] = $order_id_arr[0];
				$order_param['order_id'] = $order_id_arr[1];
				$order_param['is_own'] = intval($order_id_arr[2]);
                $order_param['openid'] = isset($notify->data['openid'])? $notify->data['openid']:'';
				$order_param['third_id'] = $notify->data['transaction_id'];
				$order_param['pay_money'] = $notify->data['total_fee']/100;
				$order_param['sub_mch_id'] = isset($notify->data['sub_mch_id']) ? $notify->data['sub_mch_id']:'';
                $order_param['mch_id'] = isset($notify->data['mch_id']) ? $notify->data['mch_id']:'';
                $order_param['appid'] = isset($notify->data['appid']) ? $notify->data['appid']:'';
                $order_param['attach'] = isset($notify->data['attach']) ? $notify->data['attach']:'';
				return array('error'=>0,'order_param'=>$order_param);
			}else{
				return array('error'=>1,'msg'=>'支付时发生错误！<br/>错误提示：'.$e->GetMessage().'<br/>错误代码：'.$e->Getcode());
			}
		}
	}
	/*查找微信订单信息*/
	public function query_order(){
		if(empty($this->pay_config['pay_weixin_appid']) || empty($this->pay_config['pay_weixin_mchid']) || empty($this->pay_config['pay_weixin_key']) || empty($this->pay_config['pay_weixin_appsecret'])){
			return array('error'=>1,'msg'=>'微信支付缺少配置信息！请联系管理员处理或选择其他支付方式。');
		}
		import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');
		//使用订单查询接口
		$orderQuery = new OrderQuery_pub($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);
        //设置必填参数
		$orderQuery->setParameter('out_trade_no',$this->order_info['order_type'].'_'.$this->order_info['order_id'].($this->pay_config['is_own'] ?'_'.$this->pay_config['is_own'] : ''));//商户订单号
		//获取订单查询结果
		if($this->pay_config['sub_mch_id']){
			$orderQuery->setParameter("sub_mch_id",$this->pay_config['sub_mch_id']);
		}
		$orderQueryResult = $orderQuery->getResult();
        fdump_api($orderQueryResult,'weixin_query_order',1);
		if($orderQueryResult['return_code'] == 'FAIL') {
			return array('error'=>1,'msg'=>'通信出错：'.$orderQueryResult['return_msg']);
		}else if($orderQueryResult['result_code'] == 'FAIL') {
			return array('error'=>1,'msg'=>'错误代码描述：'.$orderQueryResult['err_code_des']);
		}else if($orderQueryResult['trade_state'] != 'SUCCESS') {
			return array('error'=>1,'msg'=>'交易状态：'.$orderQueryResult['trade_state_desc']);
		}else{
			$order_param['pay_type'] = 'weixin';
			$order_param['is_mobile'] = $this->is_mobile;
			$order_param['order_type'] = $this->order_info['order_type'];
			$order_param['order_id'] = $this->order_info['order_id'];
			$order_param['is_own'] = intval($this->pay_config['is_own']);
			$order_param['third_id'] = $orderQueryResult['transaction_id'];
			$order_param['pay_money'] = $orderQueryResult['total_fee']/100;
			$order_param['sub_mch_id'] = $orderQueryResult['sub_mch_id'];
			return array('error'=>0,'order_param'=>$order_param);
		}
	}

	/* 退款 */
    public function refund()
    {
		//退款入口太多了，统一在微信退款这里拦截
		$payment_money_order = M("Payment_money_order")->where(['orderid' => $this->order_info['order_id'], 'order_type' => $this->order_info['order_type']])->find();
		if ($payment_money_order && $payment_money_order['pay_method'] == 'lakala' && $payment_money_order['paid_money'] > 0) {
			//拉卡拉钱账通渠道退款
			import('@.ORG.pay.Lakala');
			$pay_config = json_decode($payment_money_order['pay_config'], true);
			$pay = new Lakala([], $pay_config);
			$payOrderid = $this->order_info['order_type'] . '_' . $this->order_info['order_id'] . ($this->order_info['is_own'] ? '_' . $this->order_info['is_own'] : '');
			$go_refund_param = $pay->refund($payOrderid, $payment_money_order['paid_money'], $this->pay_money, $payment_money_order['third_plat_data']);
			return $go_refund_param;
		}


        if ($this->order_info['is_mobile_pay'] == 2) {
            $this->pay_config['pay_weixin_appid'] = C('config.pay_weixinapp_appid');
            $this->pay_config['pay_weixin_mchid'] = C('config.pay_weixinapp_mchid');
            $this->pay_config['pay_weixin_key'] = C('config.pay_weixinapp_key');
            $this->pay_config['pay_weixin_appsecret'] = C('config.pay_weixinapp_appsecret');
            C('config.pay_weixin_client_cert', C('config.pay_weixinapp_cert'));
            C('config.pay_weixin_client_key', C('config.pay_weixinapp_cert_key'));
        } elseif ($this->order_info['is_mobile_pay'] == 3) {
            if ($this->order_info['business_type'] == 'service') {
                $service_type = M('Service_user_publish')->where(array('publish_id' => $this->order_info['business_id']))->getField('service_type');
                if (!$service_type) {
                    $service_type = 0;
                }
            } else {
                $service_type = 0;
            }
			if($this->order_info['business_type'] == 'service' &&  C('config.pay_wxapp_paotui_appid') && C('config.pay_wxapp_paotui_mchid') && $service_type == 1){
			    $platOrder = M('Payment_money_order')
                    ->where([
                        'order_type' => 'plat', 
                        'orderid' => $this->order_info['orderid']
                    ])
                    ->field('pay_config')
                    ->find();
			    if(!empty($platOrder['pay_config'])){
			        $payConfig = json_decode($platOrder['pay_config'], true);
                    $this->pay_config['pay_weixin_appid'] = $payConfig['pay_weixin_appid'];
                    $this->pay_config['pay_weixin_mchid'] = $payConfig['pay_weixin_mchid'];
                    $this->pay_config['pay_weixin_key'] = $payConfig['pay_weixin_key'];
                    $this->pay_config['pay_weixin_appsecret'] = $payConfig['pay_weixin_appsecret'];
                    C('config.pay_weixin_client_cert',$payConfig['pay_weixin_client_cert']);
                    C('config.pay_weixin_client_key',$payConfig['pay_weixin_client_key']);
                }else{
				$this->pay_config['pay_weixin_appid'] = C('config.pay_wxapp_paotui_appid');
				$this->pay_config['pay_weixin_mchid'] = C('config.pay_wxapp_paotui_mchid');
				$this->pay_config['pay_weixin_key'] = C('config.pay_wxapp_paotui_key');
				$this->pay_config['pay_weixin_appsecret'] = C('config.pay_wxapp_paotui_appsecret');
				C('config.pay_weixin_client_cert',C('config.pay_wxapp_paotui_cert'));
				C('config.pay_weixin_client_key',C('config.pay_wxapp_paotui_cert_key'));
			    }
			} else if(C('config.pay_wxapp_appid') && !$this->pay_config['sub_mch_id'] && !$this->pay_config['is_own']){
				$this->pay_config['pay_weixin_appid'] = C('config.pay_wxapp_appid');
				$this->pay_config['pay_weixin_mchid'] = C('config.pay_wxapp_mchid');
				$this->pay_config['pay_weixin_key'] = C('config.pay_wxapp_key');
				$this->pay_config['pay_weixin_appsecret'] = C('config.pay_wxapp_appsecret');
				C('config.pay_weixin_client_cert', C('config.pay_wxapp_cert'));
				C('config.pay_weixin_client_key', C('config.pay_wxapp_cert_key'));
			}
        }
		if($this->pay_config['is_own']){
			C('config.pay_weixin_client_cert',$this->pay_config['pay_weixin_client_cert']);
			C('config.pay_weixin_client_key',$this->pay_config['pay_weixin_client_key']);
		}
		if(empty($this->pay_config['pay_weixin_appid']) || empty($this->pay_config['pay_weixin_mchid']) || empty($this->pay_config['pay_weixin_key']) || empty($this->pay_config['pay_weixin_appsecret'])){
			return array('error'=>1,'msg'=>'微信支付缺少配置信息！请联系管理员处理或选择其他支付方式。');
		}
		$weixin_cert =  C('config.pay_weixin_client_cert');
		$weixin_key =  C('config.pay_weixin_client_key');

		if(empty($weixin_cert) || empty($weixin_key)){
			return array('error'=>1,'msg'=>'管理员在后台支付配置中必须上传 微信支付证书和微信支付证书密钥！');
		}
		
		if($this->order_info['order_type']=='mall'){
			 $this->order_info['order_type']='shop';
		}

		$payment_money_order = M("Payment_money_order")->where(['orderid' => $this->order_info['order_id'],'order_type'=>$this->order_info['order_type']])->find();
        if($payment_money_order && $payment_money_order['pay_rate'] > 0 ){
            if (getFormatNumber($this->order_info['payment_money']) != getFormatNumber($payment_money_order['payment_money'] * $payment_money_order['pay_rate'])) {
                fdump_sql($this,'weixin_refund_rate');
                $this->pay_money = getFormatNumber($payment_money_order['pay_rate'] * $this->pay_money);
                $this->order_info['payment_money'] = getFormatNumber($payment_money_order['payment_money'] * $payment_money_order['pay_rate']);
            }
        }

        //金额四舍五入，解决遇到汇率相乘导致退款金额校验失败问题
        $this->order_info['payment_money'] = round($this->order_info['payment_money'], 2);
        $this->pay_money = round($this->pay_money, 2);

		import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');
		$refund = new Refund_pub($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);
		if(!empty($this->order_info['third_id'])){
            $refund->setParameter("transaction_id",$this->order_info['third_id']);
        }else{
            $refund->setParameter("out_trade_no",$this->order_info['order_type'].'_'.$this->order_info['order_id'].($this->order_info['is_own'] ? '_'.$this->order_info['is_own'] : ''));//商户订单号
        }
		$refund->setParameter("out_refund_no",$this->order_info['order_type'].'_'.$this->order_info['order_id'].'_'.$_SERVER['REQUEST_TIME']);//商户退款单号
		$refund->setParameter("total_fee",intval(bcmul($payment_money_order['paid_money'] > 0 ? $payment_money_order['paid_money'] : $this->order_info['payment_money'],100)));//总金额
		$refund->setParameter("refund_fee",intval(bcmul($this->pay_money,100)));//退款金额
		$refund->setParameter("op_user_id",$this->pay_config['pay_weixin_mchid']);//操作员
		if(C('config.weixin_refund_use_balance')){
			$refund->setParameter("refund_account",'REFUND_SOURCE_RECHARGE_FUNDS');//操作员
		}
		if($this->pay_config['sub_mch_id']){
			$refund->setParameter("sub_mch_id",$this->pay_config['sub_mch_id']);
			$refund->setParameter("transaction_id",$this->order_info['third_id']);
			$refund->setParameter("out_trade_no",$this->order_info['order_type'].'_'.$this->order_info['order_id']. '_'. ($this->order_info['is_own'] ? $this->order_info['is_own'] : 2) );//商户订单号
		}
		$refundResult = $refund->getResult();
		fdump_sql(array('refund' => $refund, 'refundResult'=>$refundResult,'orderInfo'=>$this->order_info,'pay_config'=>$this->pay_config,'time'=>date('Y-m-d H:i:s')),'weixin_refund');
		if(($refundResult['result_code'] == 'FAIL' && $refundResult['err_code'] != 'REFUND_FEE_INVALID')  || $refundResult['return_code'] == 'FAIL' || ($refundResult['return_code']=='SUCCESS' && $refundResult['result_code'] == 'FAIL') || !$refundResult|| !$refundResult['refund_id'] ){
			$refund_param['err_msg'] = $refundResult['return_msg'];
			if($refundResult['err_code_des']){
				$refund_param['err_msg'] .= $refundResult['err_code_des'];
			}
			$refund_param['refund_time'] = time();
			return array('error'=>1,'type'=>'fail','msg'=>L_('退款申请失败！如果重试多次还是失败请联系系统管理员。').($refund_param['err_msg']?'<br>原因：'.$refund_param['err_msg']:''),'refund_param'=>$refund_param);
		}else{
			$refund_param['refund_id'] = $refundResult['refund_id'];
			$refund_param['refund_time'] = time();
			return array('error'=>0,'type'=>'ok','msg'=>L_('退款申请成功！请注意查收“微信支付”给您发的退款通知。'),'refund_param'=>$refund_param);
		}
		
	}

	public function sendredpack(){

		if(empty($this->pay_config['pay_weixin_appid']) || empty($this->pay_config['pay_weixin_mchid']) || empty($this->pay_config['pay_weixin_key']) || empty($this->pay_config['pay_weixin_appsecret'])){
			return array('error'=>1,'msg'=>'微信支付缺少配置信息！请联系管理员处理或选择其他支付方式。');
		}
		$weixin_cert =  C('config.pay_weixin_client_cert');
		$weixin_key =  C('config.pay_weixin_client_key');


		if(empty($weixin_cert) || empty($weixin_key)){
			return array('error'=>1,'msg'=>'管理员在后台支付配置中必须上传 微信支付证书和微信支付证书密钥！');
		}

		import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');
		$refund = new RedPack($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);
		$refund->setParameter("mch_billno",'red'.time());//红包订单id
		$refund->setParameter("total_amount",$this->order_info['payment_money']*100);//总金额
		$refund->setParameter("total_num",1);//退款金额
		$refund->setParameter("send_name",C('config.site_name'));
		$refund->setParameter("re_openid",$this->user_info['openid']);
		$refund->setParameter("wishing",'恭喜您抽中'.$this->order_info['payment_money'].C('config.Currency_txt').'红包');

		$refund->setParameter("remark",$this->order_info['red_desc']);


		$refundResult = $refund->getResult();
        fdump_sql([$refund, $refundResult], 'weixin_sendredpack');
		if(($refundResult['result_code'] == 'FAIL' && $refundResult['err_code'] != 'REFUND_FEE_INVALID')  || $refundResult['return_code'] == 'FAIL' || ($refundResult['return_code']=='SUCCESS' && $refundResult['result_code'] == 'FAIL') || !$refundResult ){
            $refund_param['err_msg'] = $refundResult['return_msg'] ?: $refundResult['err_code_des'];
			$refund_param['refund_time'] = time();
			return array('error'=>1,'type'=>'fail','msg'=>'红包发送失败！如果重试多次还是失败请联系系统管理员。','refund_param'=>$refund_param);
		}else{
			$refund_param['refund_id'] = $refundResult['refund_id'];
			$refund_param['refund_time'] = time();
			return array('error'=>0,'type'=>'ok','msg'=>'红包发送成功！','refund_param'=>$refund_param);
		}
	}

	// 请求单次分账
	public function profitsharing(){
		import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');

		//使用分账接口
		$profitSharing = new ProfitSharing($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);	

		//服务商子商户支付
		if($this->pay_config['sub_mch_id']){
			$profitSharing->setParameter("sub_mch_id",$this->pay_config['sub_mch_id']);
		}

		// 微信支付订单号
		$profitSharing->setParameter("transaction_id",$this->order_info['third_id']);

		//自定义订单号
		$profitSharing->setParameter("out_order_no",$this->order_info['order_type'].'_'.$this->order_info['order_id'].($this->pay_config['is_own'] ? '_'.$this->pay_config['is_own'] : '').'_share');//商户订单号

		// 分账接收方列表
		$profitSharing->setParameter("receivers",$this->order_info['receivers']);
		
		// 证书
		C('config.pay_weixin_client_cert',$this->pay_config['pay_weixin_client_cert']);
		C('config.pay_weixin_client_key',$this->pay_config['pay_weixin_client_key']);
		$weixin_cert =  C('config.pay_weixin_client_cert');
		$weixin_key =  C('config.pay_weixin_client_key');

		if(empty($weixin_cert) || empty($weixin_key)){
			return array('error'=>1,'msg'=>'管理员在后台支付配置中必须上传 微信支付证书和微信支付证书密钥！');
		}

		$result = $profitSharing->getResult();

		$where = [
			'order_id' => $this->order_info['order_id'],
			'order_type'=>$this->order_info['order_type'],
			'order_num'=>$this->order_info['order_num']
		];
		M('Weixin_profit_sharing_record')->where($where)->data(['result' => json_encode([$profitSharing, $result],JSON_UNESCAPED_UNICODE )])->save();

		if(empty($result) || $result['return_code'] == 'FAIL'){
			return array('error'=>1,'err_code'=>$result['err_code'],'msg'=>'分账失败！微信支付错误返回：'.$result['err_code_des']);
		}
		if($result['err_code']){
			if($result['err_code'] != 'ORDER_NOT_READY'){
        	 	M('Weixin_profit_sharing_record')->where($where)->setInc('count', 1);
			}
			return array('error'=>1,'err_code'=>$result['err_code'],'msg'=>'分账失败！微信支付错误返回：'.$result['err_code_des']);
		}


		return array('error'=>0,'data'=>$result);
	}

	

	// 请求多次分账
	public function multiprofitsharing(){
		import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');

		//使用分账接口
		$profitSharing = new Multiprofitsharing($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);	

		//服务商子商户支付
		if($this->pay_config['sub_mch_id']){
			$profitSharing->setParameter("sub_mch_id",$this->pay_config['sub_mch_id']);
		}

		// 微信支付订单号
		$profitSharing->setParameter("transaction_id",$this->order_info['third_id']);

		//自定义订单号
		$profitSharing->setParameter("out_order_no",$this->order_info['order_type'].'_'.$this->order_info['order_id'].($this->pay_config['is_own'] ? '_'.$this->pay_config['is_own'] : '').'_share');//商户订单号

		// 分账接收方列表
		$profitSharing->setParameter("receivers",$this->order_info['receivers']);
		
		// 证书
		C('config.pay_weixin_client_cert',$this->pay_config['pay_weixin_client_cert']);
		C('config.pay_weixin_client_key',$this->pay_config['pay_weixin_client_key']);
		$weixin_cert =  C('config.pay_weixin_client_cert');
		$weixin_key =  C('config.pay_weixin_client_key');

		if(empty($weixin_cert) || empty($weixin_key)){
			return array('error'=>1,'msg'=>'管理员在后台支付配置中必须上传 微信支付证书和微信支付证书密钥！');
		}

		$result = $profitSharing->getResult();

		$where = [
			'order_id' => $this->order_info['order_id'],
			'order_type'=>$this->order_info['order_type'],
			'order_num'=>$this->order_info['order_num']
		];
		M('Weixin_profit_sharing_record')->where($where)->data(['result' => json_encode([$profitSharing, $result],JSON_UNESCAPED_UNICODE )])->save();

		if(empty($result) || $result['return_code'] == 'FAIL'){
			return array('error'=>1,'err_code'=>$result['err_code'],'msg'=>'分账失败！微信支付错误返回：'.$result['err_code_des']);
		}
		if($result['err_code']){
			if($result['err_code'] != 'ORDER_NOT_READY'){
        	 	M('Weixin_profit_sharing_record')->where($where)->setInc('count', 1);
			}
			return array('error'=>1,'err_code'=>$result['err_code'],'msg'=>'分账失败！微信支付错误返回：'.$result['err_code_des']);
		}


		return array('error'=>0,'data'=>$result);
	}


	// 添加分账接收方
	public function profitsharingaddreceiver(){
		import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');

		$receivers = $this->order_info['receivers'] ? json_decode($this->order_info['receivers'], true) : [];
		foreach($receivers as $_receiver){
			$_receiver['relation_type'] = $_receiver['type'] == 'MERCHANT_ID' ? 'SERVICE_PROVIDER' : 'USER';
			if($_receiver['type'] == 'MERCHANT_ID'){
				$_receiver['name'] = $this->pay_config['pay_weixin_mch_name'];
			}
			unset($_receiver['amount'],$_receiver['description']);

			//使用分账接口
			$profitsharingaddreceiver = new Profitsharingaddreceiver($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);	

			//服务商子商户支付
			if($this->pay_config['sub_mch_id']){
				$profitsharingaddreceiver->setParameter("sub_mch_id",$this->pay_config['sub_mch_id']);
			}

			// 分账接收方列表
			$profitsharingaddreceiver->setParameter("receiver", json_encode($_receiver,JSON_UNESCAPED_UNICODE));

			$result = $profitsharingaddreceiver->getResult();		

			$where = [
				'order_id' => $this->order_info['order_id'],
				'order_type'=>$this->order_info['order_type'],
				'order_num'=>$this->order_info['order_num']
			];
			M('Weixin_profit_sharing_record')->where($where)->data(['result' => json_encode([$profitsharingaddreceiver, $result],JSON_UNESCAPED_UNICODE )])->save();


			if($result['return_code'] == 'FAIL'){
				return array('error'=>1,'err_code'=>$result['err_code'],'msg'=>'分账失败！微信支付错误返回：'.$result['err_code_des']);
			}

			if($result['err_code']){
				return array('error'=>1,'err_code'=>$result['err_code'],'msg'=>'分账失败！微信支付错误返回：'.$result['err_code_des']);
			}				
		}
		
		return array('error'=>0,'data'=>$result);
	}

	// 查询最大分账比例API
	public function profitsharingmerchantratioquery(){
		import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');

		//使用分账接口
		$profitsharingaddreceiver = new Profitsharingmerchantratioquery($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);	

		//服务商子商户支付
		if($this->pay_config['sub_mch_id']){
			$profitsharingaddreceiver->setParameter("sub_mch_id",$this->pay_config['sub_mch_id']);
		}

		// 证书
		C('config.pay_weixin_client_cert',$this->pay_config['pay_weixin_client_cert']);
		C('config.pay_weixin_client_key',$this->pay_config['pay_weixin_client_key']);
		$weixin_cert =  C('config.pay_weixin_client_cert');
		$weixin_key =  C('config.pay_weixin_client_key');

		$result = $profitsharingaddreceiver->getResult();
		if($result['return_code'] == 'FAIL'){
			fdump_sql([$profitsharingaddreceiver, $result, $this->order_info, $this->pay_config], 'weixin_profit_sharing_merchantratioquery_faild');
			return array('error'=>1,'msg'=>'查询最大分账比例失败！微信支付错误返回：'.$result['return_msg']);
		}
		if($result['err_code']){
			fdump_sql([$profitsharingaddreceiver, $result, $this->order_info, $this->pay_config], 'weixin_profit_sharing_merchantratioquery_faild');
			return array('error'=>1,'msg'=>'查询最大分账比例失败！微信支付错误返回：'.$result['err_code_des']);
		}
			
		return array('error'=>0,'data'=>$result);
	}

	// 完结分账
	public function profitsharingfinish(){
		import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');

		//使用分账接口
		$profitsharingfinish = new Profitsharingfinish($this->pay_config['pay_weixin_appid'],$this->pay_config['pay_weixin_mchid'],$this->pay_config['pay_weixin_key'],$this->pay_config['pay_weixin_appsecret']);	

		//服务商子商户支付
		if($this->pay_config['sub_mch_id']){
			$profitsharingfinish->setParameter("sub_mch_id",$this->pay_config['sub_mch_id']);
		}

		// 微信支付订单号
		$profitsharingfinish->setParameter("transaction_id",$this->order_info['third_id']);

		//自定义订单号
		$profitsharingfinish->setParameter("out_order_no",$this->order_info['order_type'].'_'.$this->order_info['order_id'].($this->pay_config['is_own'] ? '_'.$this->pay_config['is_own'] : '').'_finish');//商户订单号

		// 分账接收方列表
		$profitsharingfinish->setParameter("description",'完成分账');

	
		C('config.pay_weixin_client_cert',$this->pay_config['pay_weixin_client_cert']);
		C('config.pay_weixin_client_key',$this->pay_config['pay_weixin_client_key']);

		$weixin_cert =  C('config.pay_weixin_client_cert');
		$weixin_key =  C('config.pay_weixin_client_key');

		if(empty($weixin_cert) || empty($weixin_key)){
			return array('error'=>1,'msg'=>'管理员在后台支付配置中必须上传 微信支付证书和微信支付证书密钥！');
		}

		$result = $profitsharingfinish->getResult();		

		$where = [
			'order_id' => $this->order_info['order_id'],
			'order_type'=>$this->order_info['order_type'],
			'order_num'=>$this->order_info['order_num']
		];
		M('Weixin_profit_sharing_record')->where($where)->data(['result' => json_encode([$profitsharingfinish, $result],JSON_UNESCAPED_UNICODE )])->save();

		if(empty($result) || $result['return_code'] == 'FAIL'){
			return array('error'=>1,'err_code'=>$result['err_code'],'msg'=>'分账失败！微信支付错误返回：'.$result['err_code_des']);
		}

		if($result['err_code']){
			if($result['err_code'] != 'ORDER_NOT_READY'){
        	 	M('Weixin_profit_sharing_record')->where($where)->setInc('count', 1);
			}
			return array('error'=>1,'err_code'=>$result['err_code'],'msg'=>'分账失败！微信支付错误返回：'.$result['err_code_des']);
		}
		
		return array('error'=>0,'data'=>$result);
	}
}
?>