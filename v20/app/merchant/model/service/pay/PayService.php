<?php

/**
 * 用户支付码
 * Author: hengtingmei
 * Date Time: 2020/12/10 10:45
 */
namespace app\merchant\model\service\pay;

use app\merchant\model\service\MerchantService;
use app\merchant\model\service\MerchantStoreService;
use token\Token;

class PayService {
    public function __construct()
    {
    }

    /**
     * 获取支付方式
     */
    public function getPayType()
    {
        $payTypeArr = [
            [
                'label' => '商家余额',
                'value' => 'balance'
            ],
            [
                'label' => '微信支付',
                'value' => 'weixin'
            ],
            [
                'label' => '支付宝支付',
                'value' => 'alipay'
            ]
        ];
        return $payTypeArr;
    }

    /**
     * 商家后台去支付
     */
    public function goPay($param){
        $payType = $param['pay_type'] ?? '';
        $orderId = $param['order_id'] ?? '';
        $isMobine = $param['is_mobine'] ?? '0';

        // 调用老版支付
        switch($param['order_type']){
            case 'newmarket':
                $payModel = new \app\new_marketing\model\service\MarketingOrderService;
                break;
        }

        // 订单详情
        $nowOrder = $payModel->getPayOrder($orderId);
        $nowOrder['order_type'] = $param['order_type'];
        $nowOrder['order_name'] = $nowOrder['order_name'] ?? '付款';

        // 商家信息
        $nowMerchant = (new MerchantService)->getMerchantByMerId($nowOrder['mer_id']);

        // 返回数据
        $returnArr = [
            'status' => 1,
            'info' => '',
            'orderid' => $nowOrder['orderid'],
        ];
        if($payType == 'balance'){// 余额支付
            $data = [
                'orderData' => $nowOrder,
                'type' => 3
            ];
            if($nowOrder['pay_money']>$nowMerchant['money']){
                throw new \think\Exception(L_("商家余额不足"), 1003);
            }
            $payModel->after_pay($data);
        }else{// 在线支付
            
            $orderid = build_real_orderid($nowOrder['mer_id']);// 生成新的订单号
            $nowOrder['order_id'] = $orderid;

            // 更新订单号
            $payModel->updateThis(['order_id' => $orderId], ['orderid' => $orderid]);

            $param = [
                'orderData' => $nowOrder,
                'pay_money' => $nowOrder['total_price'],
                'pay_check' => $payType,
                'merchantUser' => $nowMerchant,
                'is_mobile' => $isMobine
            ];
            $go_pay_param = invoke_cms_model('Pay/go_pay_v20', ['name' => $param]);
            $go_pay_param = $go_pay_param['retval'];
            fdump($go_pay_param,'go_pay_param',1);

            if($go_pay_param['error']){
                throw new \think\Exception($go_pay_param['msg'], 1003);
            }elseif(isset($go_pay_param['result_code']) && $go_pay_param['result_code'] == 'FAIL'){
                throw new \think\Exception($go_pay_param['err_code_des'], 1003);
            }
            
            if ($payType == 'weixin') {
                if(isset($go_pay_param['qrcode']) && $go_pay_param['qrcode']){
                    $returnArr['info'] = cfg('site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . $go_pay_param['qrcode'];
                }elseif (isset($go_pay_param['weixin_param']) && !empty($go_pay_param['weixin_param'])) {
                    if ($go_pay_param['is_own']) {
                        $returnArr['hidScript'] = $go_pay_param['hideScript'];
                    }
                    $returnArr['weixin_param'] = $go_pay_param['weixin_param'];
                }
            }elseif ($payType == 'weixinh5') {
                $returnArr['jump'] = 'mweb_url';
                $returnArr['url'] =$go_pay_param['mweb_url'];
            } else if ($payType == 'alipay') {
                if(empty($go_pay_param['pay_url'])){
                    throw new \think\Exception(L_("支付请求失败"), 1003);
                }

                $returnArr['status'] = 2;
                $returnArr['info'] = $go_pay_param['pay_url'];
            }
            
        }
        
        return $returnArr;
    }
    
}