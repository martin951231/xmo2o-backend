<?php

namespace Micropay\Type;

use Micropay\Base;

class Wechat extends Base
{
    public $config = [];

    public function __construct($params = [], $orderInfo = [])
    {
        parent::__construct($params, $orderInfo);
        $payConfig = D('Config')->get_pay_method(0, 0, false);
        if (!isset($payConfig['weixin'])) {
            throw new \Exception('微信支付未配置');
        }
        $this->config = $payConfig['weixin']['config'];

        if (empty($this->config['pay_weixin_appid'])) {
            throw new \Exception('微信支付Appid未配置！');
        }
        if (empty($this->config['pay_weixin_mchid'])) {
            throw new \Exception('微信支付商户ID未配置！');
        }
        if (empty($this->config['pay_weixin_key'])) {
            throw new \Exception('微信支付密钥未配置！');
        }
        if (empty($this->config['pay_weixin_appsecret'])) {
            throw new \Exception('微信支付Appsecret未配置！');
        }
    }


    public function micropay()
    {
        import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');
        $obj = new \Micropay($this->config['pay_weixin_appid'], $this->config['pay_weixin_mchid'], $this->config['pay_weixin_key'], $this->config['pay_weixin_appsecret']);

        $obj->setParameter("total_fee", $this->params['total_fee']);
        $obj->setParameter("auth_code", $this->params['auth_code']);
        $obj->setParameter("body", $this->orderInfo['body']);
        $obj->setParameter("out_trade_no", $this->orderInfo['orderid']);
        $rs = $obj->getResult();
        if ($rs['return_code'] == 'SUCCESS' && ($rs['result_code'] == 'SUCCESS' || $rs['err_code'] == 'USERPAYING')) {
            //成功
            $return = ['error_code' => 0, 'error_msg' => '发起支付成功', 'retval' => $rs];
        } else {
            //失败
            if (isset($rs['err_code_des'])) {
                $return = ['error_code' => 1001, 'error_msg' => '【微信支付】错误码：' . $rs['err_code'] . '，错误描述：' . $rs['err_code_des']];
            } else {
                $return = ['error_code' => 1001, 'error_msg' => '【微信支付】错误码：' . $rs['return_code'] . '，错误描述：' . $rs['return_msg']];
            }
        }
        return $return;
    }

    public function queryOrder()
    {
        import('@.ORG.pay.Weixinnewpay.WxPayPubHelper');
        //使用订单查询接口
        $payConfig = D('Config')->get_pay_method(0, 0, false);
        if (!isset($payConfig['weixin'])) {
            throw new \Exception('微信支付未配置');
        }
        $config = $payConfig['weixin']['config'];
        $orderQuery = new \OrderQuery_pub($config['pay_weixin_appid'], $config['pay_weixin_mchid'], $config['pay_weixin_key'], $config['pay_weixin_appsecret']);
        $orderQuery->setParameter('out_trade_no', $this->orderInfo['orderid']);
        $rs = $orderQuery->getResult();
        if ($rs['return_code'] == 'SUCCESS' && $rs['result_code'] == 'SUCCESS') {
            //成功
            $return = ['error_code' => 0, 'error_msg' => '查询成功', 'retval' => $rs];
        } else {
            //失败
            if (isset($rs['err_code_des'])) {
                $return = ['error_code' => 1001, 'error_msg' => '【微信支付】错误码：' . $rs['err_code'] . '，错误描述：' . $rs['err_code_des']];
            } else {
                $return = ['error_code' => 1001, 'error_msg' => '【微信支付】错误码：' . $rs['return_code'] . '，错误描述：' . $rs['return_msg']];
            }
        }
        return $return;
    }
}