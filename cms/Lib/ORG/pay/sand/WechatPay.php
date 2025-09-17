<?php

class WechatPay extends Common
{
    // 微信小程序支付：00002021
    // 微信公众号支付：00002020
    public $productId = '00002021'; //根据不同场景使用不同产品号

    // 参数映射
    protected function apiMap()
    {
        return array(
            'orderPay'          => array(
                'method' => 'sandpay.trade.pay',
                'url'    => '/gateway/api/order/pay',
            ),
            'orderQuery'        => array(
                'method' => 'sandpay.trade.query',
                'url'    => '/gateway/api/order/query',
            ),
            'orderRefund'       => array(
                'method' => 'sandpay.trade.refund',
                'url'    => '/gateway/api/order/refund',
            ),
            'clearfileDownload' => array(
                'method' => 'sandpay.trade.download',
                'url'    => '/gateway/api/clearfile/download',
            ),
        );
    }
}
