<?php
/**
 * 四川省农村信用社支付接口对接
 * 【四川省农村信用社支付接口对接】https://www.tapd.cn/47145533/prong/stories/view/1147145533001008038
 */

namespace app\pay\model\service\channel;

use app\pay\model\db\PayOrderInfo;
use app\merchant\model\service\MerchantService;
use app\pay\model\service\PayService;
use net\Http;
use think\facade\Db;
use think\facade\Cache;

class ScrcuService{
	private $agent = '';
	private $config = ['scrcu_mid'=>'','scrcu_java_gateway'=>''];//配置参数

	public function __construct($agent = '', $config = []){
		$agent && $this->agent = $agent;
		$config && $this->config = array_merge($this->config, $config);
		if (!$this->config['scrcu_java_gateway']) {
            $this->config['scrcu_java_gateway'] = cfg('scrcu_java_gateway');
        }
	}

	public function pay($order_no, $money, $extra_cfg){
		if(empty($order_no) || empty($money)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'money' => $money,'extra_cfg' => $extra_cfg,'msg' => '订单ID或支付金额必填'],'errPay/ScrcuServicePayLog');
			throw new \think\Exception("订单ID或支付金额必填！");
		}
		switch ($this->agent) {
			case 'wechat_h5'://微信端
			case 'h5'://微信端
				if($this->config['pay_type'] == 'wechat'){
					return $this->wechatPay($order_no, $money, $extra_cfg);
				}
				break;
			case 'wechat_mini':
				return $this->wechatminiPay($order_no, $money, $extra_cfg);
				break;
			case 'iosapp':
			case 'androidapp':
				return $this->h5Pay($order_no, $money, $extra_cfg);
			default:
                fdump_api(['param' => $_POST,'order_no' => $order_no,'agent' => $this->agent,'money' => $money,'extra_cfg' => $extra_cfg,'msg' => '当前端口未对接，不能使用'],'errPay/ScrcuServicePayLog');
				throw new \think\Exception("当前端口未对接，不能使用");
				break;
		}
	}

	/**
	 * 获取当前通道的支付参数
	 */
	public function getParams($mer_id = 0, $village_id = 0){
		$scrcu_mer_no = '';
        $scrcu_mer_no_2='';
		if($village_id){
			//$scrcu_mer_no = (new \app\community\model\service\PayService())->getVillageScrcu($village_id);
            $scrcuMerNoInfo = (new \app\community\model\service\PayService())->getVillageScrcuMerNo($village_id);
            if($scrcuMerNoInfo && isset($scrcuMerNoInfo['scrcu_mer_no']) && $scrcuMerNoInfo['scrcu_mer_no']){
                $scrcu_mer_no=$scrcuMerNoInfo['scrcu_mer_no'];
            }
            if($scrcuMerNoInfo && isset($scrcuMerNoInfo['scrcu_mer_no_2']) && $scrcuMerNoInfo['scrcu_mer_no_2']){
                $scrcu_mer_no_2=$scrcuMerNoInfo['scrcu_mer_no_2'];
            }
		}elseif($mer_id){
			$now_merchant = (new MerchantService)->getMerchantByMerId($mer_id);
			$scrcu_mer_no = $now_merchant['scrcu_mer_no'] ?? '';
		}	
		
		if(empty($scrcu_mer_no)) {
            return false;
        }
		return [
			'channel' => 'scrcu',
			'is_own' => 1,
			'config' => [
				// 'scrcu_mid' => cfg('scrcu_mid'),
				// 'scrcu_java_gateway' => cfg('scrcu_java_gateway'),
				'scrcu_mer_no' => $scrcu_mer_no ?? '',
                'scrcu_mer_no_2' => $scrcu_mer_no_2 ?? ''
			]
		];
	}

	//微信公众号支付(微信支付)
	public function wechatPay($order_no, $money, $extra_cfg){
		$post = [
			'orderNumber' => $order_no,
			'orderSendTime' => date('YmdHis'),
			'orderAmt' => get_format_number($money*100),
			'subject' => $extra_cfg['title'],
			'openId' => $this->config['openid'],
			'merId' => $this->config['scrcu_mid'],
			'frontEndUrl' => (String) url('Query/notice', ['orderid'=>$order_no], false, true),
			'subOrderAmt' => get_format_number($money*100),
			'paymentWay' => '03',
            'autoSettleFlag'=>1,
		];
        $post['subMerIdParent']='';
        if(isset($this->config['scrcu_mer_no_2']) && $this->config['scrcu_mer_no_2']){
            $post['subMerId'] = $this->config['scrcu_mer_no_2'];
            $post['subMerIdParent'] = $this->config['scrcu_mer_no'];
        }else if(isset($this->config['scrcu_mer_no']) && $this->config['scrcu_mer_no']){
			$post['subMerId'] = $this->config['scrcu_mer_no'];
		}else{
			$post['subMerId'] = $this->config['scrcu_mid'];
		}
		$result = Http::curlPostOwn($this->config['scrcu_java_gateway']."/api/prePay", $post);
		$res = json_decode($result, true);
        fdump_api(['post' => $post,'result' => $result,'config'=>$this->config],'scrcu_wechatPay_info',1);
		if(empty($result)){
			//fdump_sql([$post,$res,$this->config], "scrcu_wechatpay_error");
            fdump_api(['param' => $_POST,'result' => $result,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/prePay",'order_no' => $order_no,'money' => $money,'extra_cfg' => $extra_cfg,'msg' => '支付失败'],'errPay/ScrcuServiceWechatPayLog');
			throw new \think\Exception('支付失败');
		}
		
		if($res['error'] == '1'){
			//fdump_sql([$post,$res,$this->config], "scrcu_wechatpay_error");
            fdump_api(['param' => $_POST,'res' => $res,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/prePay",'order_no' => $order_no,'money' => $money,'extra_cfg' => $extra_cfg,'msg' => $res['msg']],'errPay/ScrcuServiceWechatPayLog');
            throw new \think\Exception($res['msg']);
		}

		$record = [
			'order_number' => $order_no,
			'order_type' => 'pay_order_info',
			'limit' => 0,
			'over' => 0,
			'add_time' => time(),
		];
		Db::name('Scrcu_record')->insertGetId($record);//支付记录，保险起见做主动查询支付结果的。
		return [
			'error' => '0',
			'type' => 'redirect',
			'info' => $res['data']
		];
		
	}

	//小程序微信支付
	//微信小程序支付(还没通哦,最好对照着上面的方法重写一下)
	public function wechatminiPay($order_no, $money, $extra_cfg){
		$post = [
			'orderNumber' => $order_no,
			'orderSendTime' => date('YmdHis'),
			'orderAmt' => get_format_number($money*100),
			'subOrderAmt' => get_format_number($money*100),
			'subject' => $extra_cfg['title'], 
            //'miniPath' => "/pages/pay/thirdWeixinPay",//这里替换成小程序的路径地址
            'miniPath' => "/pages/sccba/result",//这里替换成小程序的路径地址 荣杰提供的
			'openId' => $this->config['wxapp_openid'],
			'merId' => $this->config['scrcu_mid'],
			'wxAppId' => $this->config['pay_wxapp_appid'],
			'frontEndUrl' => (String) url('Query/notice', ['orderid'=>$order_no], false, true),
            'autoSettleFlag'=>1,
		];

        $post['subMerIdParent']='';
        if(isset($this->config['scrcu_mer_no_2']) && $this->config['scrcu_mer_no_2']){
            $post['subMerId'] = $this->config['scrcu_mer_no_2'];
            $post['subMerIdParent'] = $this->config['scrcu_mer_no'];
        }else if(isset($this->config['scrcu_mer_no']) && $this->config['scrcu_mer_no']){
			$post['subMerId'] = $this->config['scrcu_mer_no'];
		}else{
			$post['subMerId'] = $this->config['scrcu_mid'];
		}

		$result = Http::curlPostOwn($this->config['scrcu_java_gateway']."/api/wechatMiniPay", $post);
		$res = json_decode($result, true);
        fdump_api(['post' => $post,'result' => $result,'url' => $this->config['scrcu_java_gateway']."/api/wechatMiniPay",'config'=>$this->config],'scrcu_wechatminiPay_info');
		if(empty($result)){
			//fdump_sql([$post,$res,$this->config], "scrcu_wechatminiPay_error");
            fdump_api(['param' => $_POST,'result' => $result,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/wechatMiniPay",'order_no' => $order_no,'money' => $money,'extra_cfg' => $extra_cfg,'msg' => '支付失败'],'errPay/ScrcuServiceWechatminiPayLog');
			throw new \think\Exception('支付失败');
		}
		
		if($res['error'] == '1'){
			//fdump_sql([$post,$res,$this->config], "scrcu_wechatminiPay_error");
            fdump_api(['param' => $_POST,'res' => $res,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/wechatMiniPay",'order_no' => $order_no,'money' => $money,'extra_cfg' => $extra_cfg,'msg' => $res['msg']],'errPay/ScrcuServiceWechatminiPayLog');
            throw new \think\Exception($res['msg']);
		}

		$record = [
			'order_number' => $order_no,
			'order_type' => 'pay_order_info',
			'limit' => 0,
			'over' => 0,
			'add_time' => time(),
		];
		Db::name('Scrcu_record')->insertGetId($record);//支付记录，保险起见做主动查询支付结果的。
		return [
			'error' => '0',
			'type' => 'redirect',
			'info' => $res['data']
		];
	}	

	//app支付(微信支付)
	public function h5Pay($order_no, $money, $extra_cfg){
		$post = [
			'orderNumber' => $order_no,
			'orderSendTime' => date('YmdHis'),
			'orderAmt' => get_format_number($money*100),
			'subject' => $extra_cfg['title'],
			'merId' => $this->config['scrcu_mid'],
			'frontEndUrl' => (String) url('Query/notice', ['orderid'=>$order_no], false, true),
			'subOrderAmt' => get_format_number($money*100),
			'paymentWay' => '01',
			'mobileWay' => '01',
            'autoSettleFlag'=>1,
		];
        $post['subMerIdParent']='';
        if(isset($this->config['scrcu_mer_no_2']) && $this->config['scrcu_mer_no_2']){
            $post['subMerId'] = $this->config['scrcu_mer_no_2'];
            $post['subMerIdParent'] = $this->config['scrcu_mer_no'];
        }else if(isset($this->config['scrcu_mer_no']) && $this->config['scrcu_mer_no']){
			$post['subMerId'] = $this->config['scrcu_mer_no'];
		}else{
			$post['subMerId'] = $this->config['scrcu_mid'];
		}

		$result = Http::curlPostOwn($this->config['scrcu_java_gateway']."/api/appPay", $post);
		$res = json_decode($result, true);
        fdump_api(['post' => $post,'result' => $result,'url' => $this->config['scrcu_java_gateway']."/api/appPay",'config'=>$this->config],'scrcu_h5pay');
		if(empty($result)){
			//fdump_sql([$post,$res,$this->config], "scrcu_h5pay_error");
            fdump_api(['param' => $_POST,'result' => $result,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/wechatMiniPay",'order_no' => $order_no,'money' => $money,'extra_cfg' => $extra_cfg,'msg' => '支付失败'],'errPay/ScrcuServiceH5PayLog');
            throw new \think\Exception('支付失败');
		}
		
		if($res['error'] == '1'){
			//fdump_sql([$post,$res,$this->config], "scrcu_h5pay_error");
            fdump_api(['param' => $_POST,'res' => $res,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/wechatMiniPay",'order_no' => $order_no,'money' => $money,'extra_cfg' => $extra_cfg,'msg' => $res['msg']],'errPay/ScrcuServiceH5PayLog');
            throw new \think\Exception($res['msg']);
		}
		$record = [
			'order_number' => $order_no,
			'order_type' => 'pay_order_info',
			'limit' => 0,
			'over' => 0,
			'add_time' => time(),
		];
		Db::name('Scrcu_record')->insertGetId($record);//支付记录，保险起见做主动查询支付结果的。
		return [
			'error' => '0',
			'type' => 'redirect',
			'info' => $res['data']
		];
			
		
	}

	/**
	 * 异步通知
	 * @return [type] [description]
	 */
	public function notice(){
		$res = $this->query(request()->orderid);
		//fdump_sql([request()->orderid,$res], "scrcu_notice");
		if($res['error'] != 0){
			//return false;
            return $res;
		}
        $transaction_no='';
        if(isset($res['transaction_no']) && !empty($res['transaction_no'])){
            $transaction_no=$res['transaction_no'];
        }
		return [
			'paid_money' => $res["paid_money"],
			'paid_time' => time(),
			'transaction_no' => $transaction_no,
		];
	}

	public function query($orderNo){
		//找到本系统中的支付订单
		$pay_db = new PayOrderInfo();
		$order = $pay_db->getByOrderNo($orderNo);
		if(empty($order)){
            fdump_api(['param' => $_POST,'orderNo' => $orderNo,'order' => $order,'msg' => '没有找到支付订单'],'errPay/ScrcuServiceQueryLog');
			throw new \think\Exception("没有找到支付订单");
		}

		if($order['business'] == 'scanpay'){// 扫付款码支付查询
			$res = $this->scanQuery($orderNo);
			return $res;
		}

		$post = [
			'orderNumber' => $orderNo,
			'merId' => $this->config['scrcu_mid']
		];
		$result = Http::curlPostOwn($this->config['scrcu_java_gateway']."/api/query", $post);
        

		//fdump_sql([$post,$result], "scrcu_query");
		$res = json_decode($result, true);
        fdump_api(['post' => $post,'result' => $result,'res'=>$res],'after_pay/scrcu_query_'.date('H'),1);
        $order_param=array('query_res'=>$res);
		if($res['error'] == '0'){
			$order_param['paid_money'] = $order['money'];
			$order_param['order_id'] = $orderNo;
			$order_param['is_own'] = 1;
			$order_param['error'] = 0;
            $order_param['transaction_no']='';
            if(isset($res['bodyData']) && is_string($res['bodyData'])){
                $res['bodyData']=json_decode($res['bodyData'],true);
            }
            if(isset($res['bodyData']) && is_array($res['bodyData']) && isset($res['bodyData']['tpamTxnSsn']) && !empty($res['bodyData']['tpamTxnSsn'])){
                $order_param['transaction_no']=$res['bodyData']['tpamTxnSsn'];
            }
			return $order_param;			
		}elseif($res['error'] == '04'){
            //交易关闭了 不要再查了
            return array('error'=>1, 'msg' => $res['msg'],'orderStat'=>$res['error']);
        }else{
            fdump_api(['param' => $_POST,'res' => $res,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/query",'orderNo' => $orderNo,'msg' => $res['msg']],'errPay/ScrcuServiceQueryLog');
			return array('error'=>1, 'msg' => $res['msg']);
		}
	}
    public function querytest($orderNo){
        //找到本系统中的支付订单
        $pay_db = new PayOrderInfo();
        $order = $pay_db->getByOrderNo($orderNo);
        if(empty($order)){
            
            throw new \think\Exception("没有找到支付订单");
        }

        if($order['business'] == 'scanpay'){// 扫付款码支付查询
            $res = $this->scanQuery($orderNo);
            return $res;
        }

        $post = [
            'orderNumber' => $orderNo,
            'merId' => $this->config['scrcu_mid']
        ];
        $result = Http::curlPostOwn("http://192.168.1.96:8091/api/query", $post);

        fdump_sql([$post,$result], "scrcu_query_test");
        $res = json_decode($result, true);
        if($res['error'] == '0'){
            $order_param['paid_money'] = $order['money'];
            $order_param['order_id'] = $orderNo;
            $order_param['is_own'] = 1;
            $order_param['error'] = 0;
            return $order_param;
        }
        else{
            return array('error'=>1, 'msg' => $res['msg']);
        }
    }
	public function refund($orderNo, $payMoney, $refundMoney, $extra = []){
		//找到本系统中的支付订单
		$pay_db = new PayOrderInfo();
		$order = $pay_db->getByOrderNo($orderNo);
		if(empty($order)){
            fdump_api(['param' => $_POST,'orderNo' => $orderNo,'order' => $order,'msg' => '没有找到支付订单'],'errPay/ScrcuServiceRefundLog');
			throw new \think\Exception("没有找到支付订单");
		}

		if($order['business'] == 'scanpay'){// 扫付款码支付退款
			$res = $this->scanRefund($orderNo, $refundMoney);
			return $res;
		}

        $refund_no = date("YmdHis").rand(1000,9999);

		$post = [
			'orderNumber' => "refund".$refund_no,
			'oriOrderNumber' => $orderNo,
			'oriSubOrderNumber' => $orderNo.'02',
			'orderSendTime' => date('YmdHis'),
			'orderAmt' => get_format_number($refundMoney),
			'merId' => $this->config['scrcu_mid'],
		];
        if(isset($this->config['scrcu_mer_no_2']) && $this->config['scrcu_mer_no_2']){
            $post['merNo'] = $this->config['scrcu_mer_no_2'];
        }else if(isset($this->config['scrcu_mer_no']) && $this->config['scrcu_mer_no']){
			$post['merNo'] = $this->config['scrcu_mer_no'];
		}else{
			$post['merNo'] = $this->config['scrcu_mid'];
		}
		fdump($extra, "scrcu_refund", 1);
		fdump($post, "scrcu_refund", 1);
		$result = Http::curlPostOwn($this->config['scrcu_java_gateway']."/api/refund", $post);
		fdump($result, "scrcu_refund", 1);
		fdump($this->config['scrcu_java_gateway']."/api/refund", "scrcu_refund", 1);

		$res = json_decode($result, true);
		if($res['error'] == '0'){
			$refund_param['refund_no'] = "refund".$refund_no;
			
			return $refund_param;		
		}
		else{
            fdump_api(['param' => $_POST,'res' => $res,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/refund",'orderNo' => $orderNo,'order' => $order,'msg' => '退款失败'],'errPay/ScrcuServiceRefundLog',1);
            if (in_array($order['business'],['pile','village_new_pay'])){
                return $res;
            }
            throw new \think\Exception('退款失败');
		}
	}
	

	/*
	  * 扫用户付款码
	  * @param string $orderType 订单类型
	  * @param string $order_no 订单号
	  * @param string $money 订单金额
	  * @param string $code 用户付款码
	  * @return array
	*/
	public function scanPay($auth_code, $order_no, $money, $pay_type){

        $key = 'scrcupay_v20_'.$order_no;
		$time = date('YmdHis');
		Cache::set($key, $time);
		$post = [
			'orderNumber' => $order_no,
			'orderSendTime' => date('YmdHis'),
			'orderAmt' => get_format_number($money),
			/* 'merId' => $this->config['scrcu_mer_no'], */
			'code' => $auth_code,
            'autoSettleFlag'=>1,
		];
        if(isset($this->config['scrcu_mer_no']) && $this->config['scrcu_mer_no']){
            $post['merId'] = $this->config['scrcu_mer_no'];
        }else{
            $post['merId'] = $this->config['scrcu_mid'];
        }
        $post['mchtSecId']='';
        if(isset($this->config['scrcu_mer_no_2']) && $this->config['scrcu_mer_no_2']){
            $post['mchtSecId'] = $this->config['scrcu_mer_no_2'];
        }
		fdump($post, "scrcu_scanpay", 1);
		$result = Http::curlPostOwn($this->config['scrcu_java_gateway']."/api/scanCode", $post);
		fdump($result, "scrcu_scanpay", 1);
		$res = json_decode($result, true);
		fdump_sql([$post,$res], "scrcu_scanPay_v20");
		if($res['error'] == '1'){
            fdump_api(['param' => $_POST,'res' => $res,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/scanCode",'auth_code' => $auth_code,'money' => $money,'pay_type' => $pay_type,'order_no' => $order_no,'msg' => $res['msg']],'errPay/ScrcuServiceScanPayLog');
            throw new \think\Exception($res['msg']);
		}
		else{
			// 调用查询接口
			$res = $this->scanQuery($order_no);
			return $res;
		}
	}

	/*
	  * 扫用户付款码退款
	  * @param string $orderType 订单类型
	  * @param string $order_no 订单号
	  * @param string $money 订单金额
	  * @param string $code 用户付款码
	  * @return array
	*/
	public function scanRefund($order_no, $refundMoney){
        $refund_no = date("YmdHis").rand(1000,9999);

		$post = [
			'orderNumber' => "refund".$refund_no,
			'oriOrderNumber' => $order_no,
			'oriSubOrderNumber' => $order_no.'02',
			'orderSendTime' => date('YmdHis'),
			'orderAmt' => get_format_number($refundMoney/100),// 单位：元
			'merId' => $this->config['scrcu_mid'],
		];
		
		if(isset($this->config['scrcu_mer_no']) && $this->config['scrcu_mer_no']){
			$post['merNo'] = $this->config['scrcu_mer_no'];
		}else{
			$post['merNo'] = $this->config['scrcu_mid'];
		}
        $post['mchtSecId']='';
        if(isset($this->config['scrcu_mer_no_2']) && $this->config['scrcu_mer_no_2']){
            $post['mchtSecId'] = $this->config['scrcu_mer_no_2'];
        }
		fdump($post, "scrcu_scanRefund", 1);
		$result = Http::curlPostOwn($this->config['scrcu_java_gateway']."/api/scanRefund", $post);
		fdump($result, "scrcu_scanRefund", 1);

		$res = json_decode($result, true);
		if($res['error'] == '0'){
			$refund_param['refund_no'] = "refund".$refund_no;
			
			return $refund_param;		
		}
		else{
            fdump_api(['param' => $_POST,'res' => $res,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/scanRefund",'order_no' => $order_no,'refundMoney' => $refundMoney,'msg' => '退款失败'],'errPay/ScrcuServiceScanRefundLog');
            throw new \think\Exception(isset($res['msg']) ? '退款失败,失败原因：'.$res['msg'] : '退款失败');
		}
	}

	/*
	  * 扫用户付款码订单查询
	  * @param string $order_no 订单号
	  * @param string $scrcu_mer_no 下挂商户号
	  * @return array
	*/
	public function scanQuery($orderNo){
        $key = 'scrcupay_v20_'.$orderNo;
		$time = Cache::get($key);
		$post = [
			'orderNumber' => date("YmdHis").rand(1000,9999),
			'oriOrderNumber' => $orderNo,// 原始订单id
			'orderSendTime' => date('YmdHis'),
			'oriOrderSendTime' => $time,// 原始订单发送时间
		];
		
		if(isset($this->config['scrcu_mer_no']) && $this->config['scrcu_mer_no']){
			$post['merId'] = $this->config['scrcu_mer_no'];
		}else{
			$post['merId'] = $this->config['scrcu_mid'];
		}
		$result = Http::curlPostOwn($this->config['scrcu_java_gateway']."/api/scanQuery", $post);
		$res = json_decode($result, true);
        fdump([$this->config['scrcu_java_gateway']."/api/scanQuery",$post,$result,'res'=>$res],'scrcu_scanQuery',1);
        $order_param=array('query_res'=>$res);
		if($res['error'] == '0'){
			$order_param['status'] = 1;
            $order_param['transaction_no']='';
            if(isset($res['bodyData']) && is_string($res['bodyData'])){
                $res['bodyData']=json_decode($res['bodyData'],true);
            }
            if(isset($res['bodyData']) && is_array($res['bodyData']) && isset($res['bodyData']['tpamTxnSsn']) && !empty($res['bodyData']['tpamTxnSsn'])){
                $order_param['transaction_no']=$res['bodyData']['tpamTxnSsn'];
            }
			return $order_param;			
		}
		else{
			$order_param['status'] = 2;
            fdump_api(['param' => $_POST,'res' => $res,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/scanQuery",'orderNo' => $orderNo,'order_param' => $order_param,'msg' => '扫用户付款码订单查询失败'],'errPay/ScrcuServiceScanQueryLog');
            return $order_param;
		}
	}
    
    /**
     * 四川农银 异步通知
     * @return string
     */
    public function scrcuNotify($orderId)
    {		
		if(empty($orderId)){
			return 'fail';
		}

		try {
			$pay_service = new PayService();
			request()->orderid = $orderId;
			$notice = $pay_service->notice($orderId);   
			Db::name('Scrcu_record')->where(['order_number'=>$orderId,'order_type'=>'pay_order_info'])->data(['over'=>1])->save();
		} catch (\Exception $e) {
			return 'fail';
		}

		//调用业务方service  after_pay
		if($notice['after_pay']){
			$pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
		}
        return 'ok';
    }
}