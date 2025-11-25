<?php

namespace app\pay\model\service\channel;

use app\pay\model\service\PayService;
use Exception;

class WenzhouBankService
{
    private $agent = '';
    private $config = []; //配置参数

    public function __construct($agent = '', $config = [])
    {
        $this->agent = $agent;
        $this->config = $config;
    }

    /**
     * 去支付
     *
     * @param string $order_no
     * @param float $money
     * @author: zt
     * @date: 2023/07/04
     */
    public function pay($order_no, $money)
    {
        if (empty($order_no) || empty($money)) {
            throw new Exception("订单ID或支付金额必填！");
        }
        switch ($this->agent) {
            case 'wechat_mini':
                return $this->Wechatmini($order_no, $money);
                break;
            default:
                throw new Exception("当前端口未对接，不能使用");
                break;
        }
    }

    /**
     * 小程序微信支付
     *
     * @param string $order_no
     * @param float $money
     * @return void
     * @author: zt
     * @date: 2023/07/04
     */
    public function Wechatmini($order_no, $money)
    {
        if (empty($order_no) || empty($money)) {
            throw new Exception("订单ID或支付金额必填！");
        }
        if (empty($this->config['wxapp_openid'])) {
            throw new Exception("未获取到openid，无法调起支付");
        }
        $url = $this->config['gateway'] . '/v1/netpay/wx/unified-order';
        $post = [
            'requestTimestamp' => date('Y-m-d H:i:s'),
            'merOrderId' => $this->createOrderNo($order_no),
            'mid' => $this->config['mid'],
            'tid' => $this->config['tid'],
            'instMid' => 'MINIDEFAULT',
            'subAppId' => cfg("pay_wxapp_appid"),
            'totalAmount' => get_format_number($money * 100),
            'notifyUrl' => (string) url('wenzhouBank_notify', ['prefix' => $this->config['msgSrcId']], false, true),
            'subOpenId' =>  $this->config['wxapp_openid'],
            'tradeType' => 'MINI',
        ];
        $result = $this->curlpost($url, json_encode($post));
        if ($result['errCode'] != 'SUCCESS') {
            throw new Exception($result['errMsg']);
        }
        return [
            'type' => 'jssdk',
            'info' => json_encode($result['miniPayRequest']),
        ];
    }

    /**
     * 异步通知
     *
     * @author: zt
     * @date: 2023/07/04
     */
    public function notice()
    {
        return [
            'paid_money' => request()->param('totalAmount'),
            'paid_time' => strtotime(request()->param('payTime')),
            'transaction_no' => request()->param('targetOrderId')
        ];
    }

    /**
     * 查询
     *
     * @param string $order_no
     * @author: zt
     * @date: 2023/07/04
     */
    public function query($order_no)
    {
        if (empty($order_no)) {
            throw new Exception("订单ID或支付金额必填！");
        }
        $url = $this->config['gateway'] . '/v1/netpay/query';
        $post = [
            'requestTimestamp' => date('Y-m-d H:i:s'),
            'merOrderId' => $this->createOrderNo($order_no),
            'mid' => $this->config['mid'],
            'tid' => $this->config['tid'],
            'instMid' => 'QRPAYDEFAULT',
        ];
        $result = $this->curlpost($url, json_encode($post));

        if ($result['errCode'] == 'SUCCESS' && $result['status'] == 'TRADE_SUCCESS') {
            return [
                'paid_money' => $result['totalAmount'],
                'paid_time' => strtotime($result['payTime']),
                'transaction_no' => $result['targetOrderId'],
                'status' => 1,
            ];
        } else {
            throw new Exception($result['errMsg']);
        }
    }

    /**
     * 申请退款
     *
     * @author: zt
     * @date: 2023/07/04
     */
    public function refund($order_no, $total_fee, $refund_money, $transaction_no)
    {
        $url = $this->config['gateway'] . '/v1/netpay/refund';
        $merOrderId = $this->createOrderNo($order_no);
        $refundOrderId = $this->createRefundNo();
        $post = [
            'requestTimestamp' => date('Y-m-d H:i:s'),
            'merOrderId' => $merOrderId,
            'mid' => $this->config['mid'],
            'tid' => $this->config['tid'],
            'instMid' => 'YUEDANDEFAULT',
            'targetOrderId' => $transaction_no,
            'refundAmount' => get_format_number($refund_money),
            'refundOrderId' => $refundOrderId,
        ];
        $result = $this->curlpost($url, json_encode($post));
        if ($result['errCode'] == 'SUCCESS' && ($result['refundStatus'] == 'SUCCESS' || $result['refundStatus'] == 'PROCESSING')) {
            $refund_param['refund_no'] = $merOrderId;
            $refund_param['refund_id'] = $result['refundTargetOrderId'];
            $refund_param['refund_time'] = strtotime($result['responseTimestamp']);
            return $refund_param;
        } else {
            throw new Exception($result['errMsg'] ?? $result['errCode']);
        }
    }

    /**
     * 生成银联账单编号
     *
     * @param string $order_no
     * @author: zt
     * @date: 2023/07/04
     */
    public function createOrderNo($order_no)
    {
        return $this->config['msgSrcId'] . $order_no;
    }

    /**
     * 生成银联账单退款编号
     *
     * @param string $order_no
     * @author: zt
     * @date: 2023/07/04
     */
    public function createRefundNo()
    {
        $ori_order_no = $this->config['msgSrcId'] . date('YmdHis') . rand(1000000, 9999999);
        return $ori_order_no;
    }

    /**
     * 签名
     *
     * @param string $body
     * @author: zt
     * @date: 2023/07/04
     */
    protected function getOpenBodySig($body)
    {
        $appid = $this->config['appid'];
        $appkey = $this->config['appkey'];
        $timestamp = date("YmdHis", time());
        $nonce = md5(uniqid(microtime(true), true));
        $str = bin2hex(hash('sha256', $body, true));

        $signature = base64_encode(hash_hmac('sha256', "$appid$timestamp$nonce$str", $appkey, true));

        $authorization = "OPEN-BODY-SIG AppId=\"$appid\", Timestamp=\"$timestamp\", Nonce=\"$nonce\", Signature=\"$signature\"";
        return $authorization;
    }

    /**
     * post请求
     *
     * @param string $url
     * @param string $body
     * @author: zt
     * @date: 2023/07/04
     */
    protected function curlpost($url, $body)
    {

        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $url); //抓取指定网页
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body),
            'Authorization: ' . $this->getOpenBodySig($body)
        ));
        $output = curl_exec($ch); //运行curl
        curl_close($ch);
        fdump_api(['body'=>$body,'result'=>$output],'pay/WenzhouBankCurl');
        return json_decode($output, true);
    }
}
