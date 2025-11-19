<?php
/**
 * 【【12.15交付】www.yilvbao.cn 环球数科（交付：12.15）- 对接服务机构支付接口，解决跨区域支付结算问题】 https://www.tapd.cn/47145533/prong/stories/view/1147145533001009799
 */

namespace app\pay\model\service\channel;

use app\pay\model\db\PayOrderInfo;
use app\merchant\model\service\MerchantService;
use app\pay\model\service\PayService;
use net\Http;
use think\facade\Db;
use think\facade\Cache;

class HqpayService{
	private $agent  = '';
	private $config = [];//配置参数
    protected $base_url;
    protected $redi_url;
    protected $return_url;

	public function __construct($agent = '', $config = []) {
		$this->agent      = $agent;
		$this->config     = $config;
        $this->base_url   = 'https://jicheng.upaypal.cn/business-platform.web.services.merchant-api-v3/rest/api/v3/';
        $this->redi_url   = 'https://jicheng.upaypal.cn/business-platform.payment-channel.web/hqpay/apay/';
        $this->return_url = cfg('site_url') . '/packapp/plat/pages/pay/result?orderid=';//返回通知地址
	}

	public function pay($order_no, $money, $extra_cfg) {
		if (empty($order_no) || empty($money)) {
			throw new \think\Exception("订单ID或支付金额必填！");	
		}
        return $this->goPay($order_no, $money, $extra_cfg);
	}

    public function goPay($order_no, $money, $extra_cfg) {
        $offer = [
            'product_id'   => '"t00123"',
            'product_name' => '"' . $extra_cfg['title'] . '"' ?? '"订单号:' . $extra_cfg['business_order_sn'] ?: $order_no . '"',
            'quantity'     => 1,
            'unit_price'   => $money
        ];
        $post = [[
            'order_type'   => 'trade',
            'out_trade_no' => $order_no,
            'amount'       => $money,
            'subject'      => $extra_cfg['title'] ?? '订单号:' . $extra_cfg['business_order_sn'] ?: $order_no,
            'time_expire'  => date('Y-m-d H:i:s', time() + 600),
            'offer'        => $offer,
            'notify_url'   => (string)url('hqpay_notify', [], false, true),
        ]];
        $res = $this->doRequest('createTrade', $post);
        fdump($res, 'hqpay_pay', 1);
        $res = json_decode($res, true);
        if (empty($res['return_code']) || $res['return_code'] != 'SUCCESS' || empty($res['data']['trade_no'])) {
            return ['error' => 1, 'msg' => $res['return_msg'] ?? '请求失败'];
        }
        $url = '';
        switch ($this->agent) {
            case 'iosapp':
            case 'androidapp':
                if ($extra_cfg['pay_type'] == 'hqpay_wx') {
                    $url = $this->redi_url . 'app?channel_pay_type=WX_APP&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url . $order_no);
                    $urlRes = file_get_contents($url);
                    $urlRes = json_decode($urlRes, true);
                    if (!empty($urlRes['return_code']) && $urlRes['return_code'] == 'SUCCESS' && !empty($urlRes['data']['get_appstring_url'])) {
                        $qrRes = file_get_contents($urlRes['data']['get_appstring_url']);
                        $qrRes = json_decode($qrRes, true);
                        if (!empty($qrRes['return_code']) && $qrRes['return_code'] == 'SUCCESS' && !empty($qrRes['content'])) {
                            if ($extra_cfg['pay_type'] == 'hqpay_wx') {
                                return array('error' => 0, 'type' => 'sdk', 'env' => $this->agent, 'weixin_param' => $qrRes['content']['app_string']);
                            } else {
                                return array('error' => 0, 'type' => 'sdk', 'env' => $this->agent, 'alipay_param' => $qrRes['content']['app_string']);
                            }
                        }
                    }
                } else { //支付宝app支付暂时走H5支付
//                    $url = $this->redi_url . 'app?channel_pay_type=ALIPAY_APP&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url . $order_no);
                    $url = $this->redi_url . 'wap?channel_pay_type=ALIPAY_WAP&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url . $order_no);
                }
                break;
            case 'wechat_h5':
                $url = $this->redi_url . 'pub?channel_pay_type=WX_PUB&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url . $order_no) . '&openid=' . $this->config['openid'] ?? '';
                break;
            case 'wechat_mini':
                $url = $this->redi_url . 'lite?channel_pay_type=WX_LITE&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url . $order_no) . '&openid=' . $this->config['wxapp_openid'] ?? '';
                $urlRes = file_get_contents($url);
                $urlRes = json_decode($urlRes, true);
                return [
                    'type' => 'jssdk',
                    'info' => $urlRes
                ];
                break;
            case 'alipay':
                $url = $this->redi_url . 'wap?channel_pay_type=ALIPAY_WAP&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url . $order_no);
                break;
            case 'h5':
                if ($extra_cfg['pay_type'] == 'hqpay_wx') {
                    $url = $this->redi_url . 'wap?channel_pay_type=WX_WAP&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url . $order_no) . '&openid=' . $this->config['openid'] ?? '';
                } else {
                    $url = $this->redi_url . 'wap?channel_pay_type=ALIPAY_WAP&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url . $order_no);
                }
                break;
            case 'pc':
                require_once '../extend/phpqrcode/phpqrcode.php';
                if ($extra_cfg['pay_type'] == 'hqpay_wx') {
                    $urlRes = $this->redi_url . 'qr?channel_pay_type=WX_QR&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url . $order_no);
                } else {
                    $urlRes = $this->redi_url . 'qr?channel_pay_type=ALIPAY_QR&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url . $order_no);
                }
                $urlRes = file_get_contents($urlRes);
                $urlRes = json_decode($urlRes, true);
                if (!empty($urlRes['return_code']) && $urlRes['return_code'] == 'SUCCESS' && !empty($urlRes['data']['get_qrcode_url'])) {
                    $qrRes = file_get_contents($urlRes['data']['get_qrcode_url']);
                    $qrRes = json_decode($qrRes, true);
                    if (!empty($qrRes['resultCode']) && $qrRes['resultCode'] == 'SUCCESS' && !empty($qrRes['qrCode'])) {
                        $url = cfg('site_url') . $this->createQrcode($qrRes['qrCode']);
                        return [
                            'type' => 'redirect',
                            'info' => $url
                        ];
                    }
                }
                if (empty($url)) {
                    throw new \think\Exception("二维码生成失败，请稍后重试");
                }
                break;
        }
	fdump($url, 'hqpay_pay', 1);
        return ['url' => $url, 'type' => 'redirect', 'env' => $this->agent];
    }

    public function doRequest($api, $params) {
        $url  = $this->base_url . $api;
        $curl = curl_init($url);
        $payload = json_encode($params, true);
        $headers = array(
            "Content-Type: application/json",
            "Accept: application/json",
            "AppId: " . $this->config['pay_hqpay_appid'],
            "Authorization: " . $this->config['pay_hqpay_secretkey']
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $curl_return = curl_exec($curl);
        curl_close($curl);
        return $curl_return;
    }

    /**
     * 异步通知
     * @return [type] [description]
     */
    public function notice() {
        fdump(request()->orderid, "hqpay_notice", 1);
        $res = $this->searchPay(request()->orderid);
        fdump($res, "hqpay_notice", 1);
        return $res;
    }

    //支付查询
    public function searchPay($out_trade_no) {
        $arr = [
            'out_trade_no' => $out_trade_no
        ];
        $res = $this->doRequest('getTrade', $arr);
        fdump($res, 'hqpay_searchPay', 1);
        $res = json_decode($res,true);
        if ((!empty($res['return_code']) && $res['return_code'] == 'SUCCESS') && (!empty($res['data']['trade_status']) && $res['data']['trade_status'] == 'PAID'))  {
            return [
                'paid_money' => $res['data']["amount"],
                'transaction_no' => $res['data']["trade_no"],
                'paid_time' => time()
            ];
        }
        return false;
    }

    //支付查询
    public function query($out_trade_no) {
        $arr = [
            'out_trade_no' => $out_trade_no
        ];
        $res = $this->doRequest('getTrade', $arr);
        fdump($res, 'hqpay_searchPay', 1);
        $res = json_decode($res,true);
        if ((!empty($res['return_code']) && $res['return_code'] == 'SUCCESS') && (!empty($res['data']['trade_status']) && $res['data']['trade_status'] == 'PAID'))  {
            if ($res['data']['trade_status'] == 'PAID') {
                return [
                    'status' => 1,
                    'paid_money' => $res['data']["amount"],
                    'transaction_no' => $res['data']["trade_no"],
                    'paid_time' => time(),
                ];
            } else if ($res['data']['trade_status'] == 'PENDING') { // 支付中
                return [
                    'status' => 2,
                ];
            }
        }
        return false;
    }

    /**
     * 退款
     * $orderNo 订单号
     * $refundMoney 退款金额
     */
	public function refund($orderNo = '', $payMoney = 0, $refundMoney = 0, $extra = []){
		//找到本系统中的支付订单
		$pay_db = new PayOrderInfo();
		$order = $pay_db->getByOrderNo($orderNo);
		if(empty($order)){
			throw new \think\Exception("没有找到支付订单");			
		}
        $refund_no = date("YmdHis") . rand(1000,9999);
		$post = [
            'out_refund_no' => $refund_no,
            'out_trade_no'  => $orderNo,
            'refund_amount' => $refundMoney,
            'refund_reason' => '退款单号:' . $refund_no,
            'refund_notify_url' => (string)url('hqpay_refund', [], false, true),
        ];
        $res = $this->doRequest('createRefundBill', $post);
        fdump($res, 'hqpay_refund', 1);
		$res = json_decode($res, true);
        if (empty($res['return_code']) || $res['return_code'] != 'SUCCESS') {
            throw new \think\Exception($res['return_msg'] ?? '请求失败');
        }
        return ['refund_no' => $refund_no];
	}

    //退款单查询，未使用
    public function searchRefundPay($out_refund_no) {
        $arr = [
            'out_refund_no' => $out_refund_no
        ];
        $res = $this->doRequest('getRefundBill', $arr);
        fdump($res, 'hqpay_searchRefundPay', 1);
        $res = json_decode($res,true);
        if ((!empty($res['return_code']) && $res['return_code'] == 'SUCCESS') && (!empty($res['content']['refund_status']) && $res['content']['refund_status'] == 'SUCCESS'))  {
            return true;
        }
        return false;
    }

    /*
      * 扫用户付款码
      * @param string $orderType 订单类型
      * @param string $order_no 订单号
      * @param string $money 订单金额
      * @param string $code 用户付款码
      * @return array
    */
    public function scanPay($auth_code, $order_no, $money, $pay_type){//
        $offer = [
            'product_id'   => '"t00123"',
            'product_name' => '"订单号:' . $order_no . '"',
            'quantity'     => 1,
            'unit_price'   => $money
        ];
        $post = [[
            'order_type'   => 'trade',
            'out_trade_no' => $order_no,
            'amount'       => $money,
            'subject'      => '订单号:' . $order_no,
            'time_expire'  => date('Y-m-d H:i:s', time() + 600),
            'offer'        => $offer,
            'notify_url'   => (string)url('hqpay_notify', [], false, true),
        ]];
        $res = $this->doRequest('createTrade', $post);
        fdump($res, 'hqpay_scanPay', 1);
        $res = json_decode($res, true);
        if (empty($res['return_code']) || $res['return_code'] != 'SUCCESS' || empty($res['data']['trade_no'])) {
            return ['error' => 1, 'msg' => $res['return_msg'] ?? '请求失败'];
        }
        $urlRes = $this->redi_url . 'micro?trade_no=' . $res['data']['trade_no'] . '&auth_code=' . $auth_code;
        $urlRes = file_get_contents($urlRes);
        $urlRes = json_decode($urlRes, true);
        fdump($urlRes, 'hqpay_scanPay', 1);
        if (empty($urlRes['resultCode']) || $urlRes['resultCode'] != 'SUCCESS') {
            return ['status' => 0];
//            throw new \think\Exception($res['returnMsg'] ?? '支付失败');
        }
        return ['status' => 1];
        // 调用查询接口
//        $res = $this->searchPay($order_no);
//        if ($res) {
//            return ['status' => 1];
//        } else {
//            return ['status' => 2];
//        }
    }

    /**
     * 生成二维码
     */
    public function createQrcode($codeUrl)
    {
        $dir = '/upload/qrcode/hqpay/' . date('Ymd');
        $path = '../..' . $dir;
        $filename = time().rand(1000,9999) . '.png';
        if (file_exists($path . '/' . $filename)) {
            return $dir . '/' . $filename;
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $qrCon = $codeUrl;
        $qrcode = new \QRcode();
        $qrcode->png($qrCon, $path . '/' . $filename, 'L', '9');
        return $dir . '/' . $filename;
    }

}