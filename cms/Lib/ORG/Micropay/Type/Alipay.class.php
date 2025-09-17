<?php

namespace Micropay\Type;

use Micropay\Base;

class Alipay extends Base
{
    public $config = [];

    public function __construct($params = [], $orderInfo = [])
    {
        parent::__construct($params, $orderInfo);
        $keys = [
            'cash_pay_qrcode',
            'arrival_alipay_open',
            'arrival_alipay_app_prikey',
            'arrival_alipay_app_id',
            'alipay_arrival_encrypt_type'
        ];
        $config = M('Config')->where(['name' => ['IN', $keys]])->select();
        $this->config = array_combine(array_column($config, 'name'), array_column($config, 'value'));

        if ($this->config['arrival_alipay_open'] != 1) {
            throw new \Exception('支付宝店内收银未开启');
        }
        if (empty($this->config['arrival_alipay_app_id']) || empty($this->config['arrival_alipay_app_prikey'])) {
            throw new \Exception('支付宝支付缺少配置信息！');
        }
    }

    public function micropay()
    {
        import("@.ORG.pay.AlipayNew.aop.AopClient", '', ".php");
        import("@.ORG.pay.AlipayNew.aop.SignData", '', ".php");
        $c = new \AopClient;
        $c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $c->appId = $this->config['arrival_alipay_app_id'];
        $c->rsaPrivateKey = $this->config['arrival_alipay_app_prikey'];
        $c->format = "json";
        $c->charset = "GBK";
        $c->signType = "RSA2";
        $c->alipayrsaPublicKey = $this->config['pay_alipay_public_key'];
        ## 实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.open.public.template.message.industry.modify
        import("@.ORG.pay.AlipayNew.aop.request.AlipayTradePayRequest", '', ".php");
        $request = new \AlipayTradePayRequest();

        $data = [
            'out_trade_no' => $this->orderInfo['orderid'],
            'scene' => 'bar_code',
            'auth_code' => $this->params['auth_code'],
            'store_id' => $this->params['mcode'],
            'timeout_express' => '2m',
            'total_amount' => $this->orderInfo['total_fee'],
            'subject' => $this->orderInfo['body']
        ];
        $request->setBizContent(json_encode($data));
        $result = $c->execute($request);
        $rs = (Array)$result->alipay_trade_pay_response;
        if ($rs['code'] == '10000') {
            //成功
            $return = ['error_code' => 0, 'error_msg' => '支付成功', 'retval' => $rs];
        } else {
            //失败
            if (isset($rs['sub_code'])) {
                $return = ['error_code' => 1001, 'error_msg' => '【支付宝支付】错误码：' . $rs['sub_code'] . '，错误描述：' . $rs['sub_msg']];
            } else {
                $return = ['error_code' => 1001, 'error_msg' => '【支付宝支付】错误码：' . $rs['code'] . '，错误描述：' . $rs['msg']];
            }
            return $return;
        }
    }

    public function queryOrder()
    {
        import("@.ORG.pay.AlipayNew.aop.AopClient", '', ".php");
        import("@.ORG.pay.AlipayNew.aop.SignData", '', ".php");
        $c = new \AopClient;
        $c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $c->appId = $this->config['arrival_alipay_app_id'];
        $c->rsaPrivateKey = $this->config['arrival_alipay_app_prikey'];
        $c->format = "json";
        $c->charset = "GBK";
        $c->signType = "RSA2";
        $c->alipayrsaPublicKey = $this->config['pay_alipay_public_key'];
        ## 实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.open.public.template.message.industry.modify
        import("@.ORG.pay.AlipayNew.aop.request.AlipayTradeQueryRequest", '', ".php");
        $request = new \AlipayTradeQueryRequest();
        $data = [
            'out_trade_no' => $this->orderInfo['orderid']
        ];
        $request->setBizContent(json_encode($data));
        $result = $c->execute($request);
        $rs = (Array)$result->alipay_trade_query_response;
        if ($rs['code'] == '10000') {
            //成功
            $return = ['error_code' => 0, 'error_msg' => '查询成功', 'retval' => $rs];
        } else {
            //失败
            if (isset($rs['sub_code'])) {
                $return = ['error_code' => 1001, 'error_msg' => '【支付宝支付】错误码：' . $rs['sub_code'] . '，错误描述：' . $rs['sub_msg']];
            } else {
                $return = ['error_code' => 1001, 'error_msg' => '【支付宝支付】错误码：' . $rs['code'] . '，错误描述：' . $rs['msg']];
            }
        }
        return $return;
    }
}