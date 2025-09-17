<?php
declare (strict_types = 1);

namespace app\pay\controller;

use app\pay\model\service\PayService;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\pay\model\service\channel\TianqueService;

class IndexController
{
    public function test(){
        dump((new MerchantCouponService)->getAvailableCoupon(1358606, 0, ['can_coupon_money'=>100, 'store_id'=>426]));
        exit;
    }

    public function index()
    {
//        dump((new MerchantCouponService)->getCouponInfo(1)->toArray());exit;
//        echo url('wechat_notify', [], false, true);exit;
    	$pay_service = new PayService(0, 0, 0);
//    	 $return = $pay_service->payTypeList();
        $order_id = rand(10000, 99999);
        try{
            $return = $pay_service->pay('shop', $order_id, 'wechat', 1, [], ['openid'=>'os-L50NgLiCqiONysvLEALY7meXk']);
        } catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }

    public function refund(){
        $order_no = '202006021525094229861628';
        $pay_service = new PayService();
        try{
            $refund = $pay_service->refund($order_no);
        } catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $refund);
    }

    public function getTianqueUrl(){
        $mno    = request()->param('mno');
        if(empty($mno)) die('no mno');
        $TianqueService = new TianqueService;
        $res = $TianqueService->getUrl($mno);
        if(isset($res['retUrl']) && $res['retUrl'] != ''){
            redirect($res['retUrl'])->send();
            // echo json_encode(['url'=>$res['retUrl']]);
        }
        elseif ($res['bizMsg'] == '该商户已签约, 请勿重复签约') {
            die('<p style="font-size:24px;line-height:40px;font-bold:weight;text-align:center;">我们查到您的状态已签约，请勿重复签约！</p>');
            // echo json_encode(['url'=>'', 'already' =>1]);
        }
        else{
            die('<p style="font-size:24px;line-height:40px;font-bold:weight;text-align:center;">获取地址异常！可能是您的商户号填写有误</p>');
            // echo json_encode(['url'=>'']);  
        }
            exit;
    }
}
