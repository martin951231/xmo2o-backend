<?php
/**
 * 四川省农村信用社支付接口对接
 * 【四川省农村信用社支付接口对接】https://www.tapd.cn/47145533/prong/stories/view/1147145533001008038
 */

namespace app\pay\model\service\channel;

use app\pay\model\db\PayOrderInfo;
use app\merchant\model\service\MerchantService;
use net\Http;
use think\facade\Db;
use think\facade\Cache;

class ScrcuCashService{
	private $agent = '';
	private $config = [];//配置参数
	private $mobileWay = '03';

	public function __construct($agent = '', $config = []){
		$this->agent = $agent;
		$this->config = $config;
		switch ($this->agent) {
			case 'wechat_h5'://微信端
			case 'h5'://微信端
				$this->mobileWay = '03';
				break;
			case 'wechat_mini':
				$this->mobileWay = '04';
				break;
			case 'iosapp':
			case 'androidapp':
				$this->mobileWay = '01';
				break;
		}
	}

	public function pay($order_no, $money, $extra_cfg){
		if(empty($order_no) || empty($money)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'money' => $money,'extra_cfg' => $extra_cfg,'msg' => '订单ID或支付金额必填'],'errPay/ScrcuCashServicePayLog',1);
			throw new \think\Exception("订单ID或支付金额必填！");
		}
		$post = [
			'orderNumber' => $order_no,
			'orderSendTime' => date('YmdHis'),
			'orderAmt' => get_format_number($money*100),
			'subject' => $extra_cfg['title'],
			'openId' => $this->agent == 'wechat_mini' ? $this->config['wxapp_openid'] : $this->config['openid'],
			'merId' => $this->config['scrcu_mid'],
			'frontEndUrl' => (String) url('Query/notice', ['orderid'=>$order_no], false, true),
			'subOrderAmt' => get_format_number($money*100),
			'paymentWay' => '01',
			'mobileWay' => $this->mobileWay,
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
		if($this->agent == 'wechat_mini'){// 小程序

			$post['wxAppId'] = $this->config['pay_wxapp_appid'];
			//$post['wxAppUrl'] = '/pages/pay/thirdWeixinPay';
            $post['wxAppUrl']=  "/pages/sccba/result";//这里替换成小程序的路径地址 荣杰提供的
		}

		$result = Http::curlPostOwn($this->config['scrcu_java_gateway']."/api/prePay", $post);
        fdump_api(['post'=>$post,'agent_env'=>$this->agent,'url' => $this->config['scrcu_java_gateway']."/api/prePay",'result'=>$result], "scrcuCash_pay", 1);
		$res = json_decode($result, true);
		if(empty($result)){
            fdump_api(['param' => $_POST,'result' => $result,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/prePay",'order_no' => $order_no,'money' => $money,'extra_cfg' => $extra_cfg,'msg' => '支付失败'],'errPay/ScrcuCashServicePayLog',1);
			throw new \think\Exception('支付失败');
		}
		
		if($res['error'] == '1'){
            fdump_api(['param' => $_POST,'res' => $res,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/prePay",'order_no' => $order_no,'money' => $money,'extra_cfg' => $extra_cfg,'msg' => $res['msg']],'errPay/ScrcuCashServicePayLog',1);
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
		fdump(request()->orderid, "scrcu_notice1", 1);
		$res = $this->query(request()->orderid);
		fdump($res, "scrcu_notice1", 1);
		if($res['error'] != 0){
			return false;
		}
		return [
			'paid_money' => $res["paid_money"],
			'paid_time' => time(),
			// 'transaction_no' => $_POST['targetOrderId']
		];
	}

	public function query($orderNo){
		
		fdump('query', "scrcuCash_query", 1);
		//找到本系统中的支付订单
		$pay_db = new PayOrderInfo();
		$order = $pay_db->getByOrderNo($orderNo);
		fdump($order, "scrcuCash_query", 1);
		if(empty($order)){
//            fdump_api(['param' => $_POST,'orderNo' => $orderNo,'order' => $order,'msg' => '没有找到支付订单'],'errPay/ScrcuCashServiceQueryLog',1);
			throw new \think\Exception("没有找到支付订单");
		}

		$post = [
			'orderNumber' => $orderNo,
			'merId' => $this->config['scrcu_mid']
		];
		fdump($post, "scrcuCash_query", 1);
		$result = Http::curlPostOwn($this->config['scrcu_java_gateway']."/api/query", $post);

		fdump($result, "scrcuCash_query", 1);
		$res = json_decode($result, true);
		if($res['error'] == '0'){
			$order_param['paid_money'] = $order['money'];
			$order_param['order_id'] = $orderNo;
			$order_param['is_own'] = 1;
			$order_param['error'] = 0;
			return $order_param;			
		}
		else{
//            fdump_api(['param' => $_POST,'res' => $res,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/query",'orderNo' => $orderNo,'order' => $order,'msg' => $res['msg']],'errPay/ScrcuCashServiceQueryLog',1);
			return array('error'=>1, 'msg' => $res['msg']);
		}
	}

	public function refund($orderNo, $payMoney, $refundMoney, $extra = []){
		//找到本系统中的支付订单
		$pay_db = new PayOrderInfo();
		$order = $pay_db->getByOrderNo($orderNo);
		if(empty($order)){
            fdump_api(['param' => $_POST,'orderNo' => $orderNo,'payMoney' => $payMoney,'refundMoney' => $refundMoney,'extra' => $extra,'msg' => '没有找到支付订单'],'errPay/ScrcuCashServiceRefundLog',1);
			throw new \think\Exception("没有找到支付订单");
		}

        $refund_no = date("YmdHis").rand(1000,9999);

		$post = [
			'orderNumber' => "refund".$refund_no,
			'oriOrderNumber' => $orderNo,
			'oriSubOrderNumber' => $orderNo.'02',
			'orderSendTime' => date('YmdHis'),
			'orderAmt' => get_format_number($refundMoney),
			'merId' => $this->config['scrcu_mid'],
			'merNo' => $this->config['scrcu_mer_no']
		];
        if(isset($this->config['scrcu_mer_no_2']) && $this->config['scrcu_mer_no_2']){
            $post['merNo'] = $this->config['scrcu_mer_no_2'];
        }else if($this->config['scrcu_mer_no']){
			$post['merNo'] = $this->config['scrcu_mer_no'];
		}else{
			$post['merNo'] = $this->config['scrcu_mid'];
		}
		fdump($extra, "scrcuCash_refund", 1);
		fdump($post, "scrcuCash_refund", 1);
		$result = Http::curlPostOwn($this->config['scrcu_java_gateway']."/api/refund", $post);
		fdump($result, "scrcuCash_refund", 1);
		fdump($this->config['scrcu_java_gateway']."/api/refund", "scrcuCash_refund", 1);

		$res = json_decode($result, true);
		if($res['error'] == '0'){
			$refund_param['refund_no'] = "refund".$refund_no;
			
			return $refund_param;		
		}
		else{
            fdump_api(['param' => $_POST,'res' => $res,'post' => $post,'url' => $this->config['scrcu_java_gateway']."/api/refund",'orderNo' => $orderNo,'order' => $order,'msg' => '退款失败'],'errPay/ScrcuCashServiceRefundLog',1);
            throw new \think\Exception('退款失败');
		}
	}
}