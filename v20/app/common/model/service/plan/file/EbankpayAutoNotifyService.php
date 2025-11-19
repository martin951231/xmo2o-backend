<?php
/**
 * 日照银行支付自动异步通知
 */

namespace app\common\model\service\plan\file;

use app\pay\model\db\PayOrderInfo;
use app\pay\model\service\PayService;

class EbankpayAutoNotifyService
{

    public function runTask($order_no = '')
    {
        if (!empty($order_no)) { //防止二次支付
            $data = (new PayOrderInfo())->where([['orderid', '=',  $order_no], ['pay_type', '=', 'ebankpay'], ['paid', '=', 0]])->find();
            if(!empty($data)){
                $value = $data->toArray();
                fdump($value['orderid'], 'EbankpayAutoNotifyService', 1);
                try {
                    $pay_service = new PayService();
                    request()->orderid = $value['orderid'];
                    $notice = $pay_service->notice($value['orderid']);
                } catch (\Exception $e) {
                    fdump($e->getMessage(), 'EbankpayAutoNotifyService', 1);
                }
                //调用业务方service  after_pay
                if ($notice['after_pay']) {
                    $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
                }
            }
        } else {
            //1、获取order_no（本系统的支付单号）
            $data = (new PayOrderInfo())->order("id desc")->where([['add_time', '>=',  time() - 600], ['pay_type', '=', 'ebankpay'], ['paid', '=', 0]])->limit(20)->select();
            if(!empty($data)){
                $data = $data->toArray();
                foreach ($data as $key => $value) {
                    fdump($value['orderid'], 'EbankpayAutoNotifyService', 1);
                    try {
                        $pay_service = new PayService();
                        request()->orderid = $value['orderid'];
                        $notice = $pay_service->notice($value['orderid']);
                    } catch (\Exception $e) {
                        fdump($e->getMessage(), 'EbankpayAutoNotifyService', 1);
                    }

                    //调用业务方service  after_pay
                    if ($notice['after_pay']) {
                        $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
                    }
                }
            }

            file_get_contents(cfg('site_url') . '/source/wap_ebank.php');
        }
    }

}