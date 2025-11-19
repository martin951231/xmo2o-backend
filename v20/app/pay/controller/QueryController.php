<?php
/**
 * 同步查询页面/接口
 */
declare (strict_types = 1);

namespace app\pay\controller;

use app\pay\model\service\PayService;
use app\common\model\service\AdverService;

class QueryController
{
    /*
     * 支付结果页信息
     */
    public function index(){
        $orderid = request()->param('orderid');//支付单号
        if(empty($orderid)){
            return api_output_error(1003, '支付单号orderid未传递！');
        }
        $return = [
            'adv' => [],
            'order' => [
                'money' => 0,
                'symbol' => cfg('Currency_symbol') ?? '￥'
            ]
        ];
        $adv = (new AdverService)->getAdverByCatKey('pay_result_adv');
        foreach ($adv as $item) {
            $temp = [
                'img' => replace_file_domain('/upload/adver/' . $item['pic']),
                'url' => $item['url']
            ];
            $return['adv'][] = $temp;
        }
        $order = (new PayService())->getPayOrderInfo($orderid);
        $return['order']['money'] = $order['money']/100;
        return api_output(0, $return);
    }

	/**
	 * 查询订单支付状态接口
	 * 本接口会查询订单状态并返回给前端
	 */
	public function status(){
		$orderid = request()->param('orderid');//支付单号
		if(empty($orderid)){
			return api_output_error(1003, '支付单号orderid未传递！');
		}
		try {
            $pay_service = new PayService;
            $query = $pay_service->query($orderid);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
		fdump_api([$query,$orderid],'status_0728',1);
        if($query['after_pay']){
        	try{
                fdump_api([$query,$orderid],'status_0728',1);
	        	$pay_service->afterPay($query['business'], $query['business_order_id'], $query['extra']);
	        } catch (\Exception $e){
	        	return api_output_error(1005, $e->getMessage());
	        }
        }

        //得到跳转url
        $redirect_url = $pay_service->getPayResultUrl($query['business'], $query['business_order_id'], false);
        $redirect_home_url = $redirect_url['redirect_home_url'] ?? get_base_url();
        if(is_array($redirect_url) && isset($redirect_url['direct'])){
            return api_output(0, ['redirect_url' => $redirect_url['redirect_url'], 'direct' => $redirect_url['direct'], 'redirect_home_url'=>$redirect_home_url]);
        }
        else{
            return api_output(0, ['redirect_url' => is_array($redirect_url) ? $redirect_url['redirect_url'] : $redirect_url , 'direct' => 0, 'redirect_home_url'=>$redirect_home_url]);  
        }
        
	}

	/**
	 * 查询订单支付状态接口
	 * 第三方同步回调接口
	 */
	public function notice(){
		$orderid = request()->param('orderid');//支付单号
        fdump($orderid, 'Query_notice', 1);
		if(empty($orderid)){
			return api_output_error(1003, '支付单号orderid未传递！');
		}
		try {
            $pay_service = new PayService;
            $query = $pay_service->query($orderid);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        } 
        if($query['after_pay']){
        	try{
	        	$pay_service->afterPay($query['business'], $query['business_order_id'], $query['extra']);
	        } catch (\Exception $e){
	        	return api_output_error(1005, $e->getMessage());
	        }
        }

        //得到跳转url
        $redirect_url = $pay_service->getPayResultUrl($query['business'], $query['business_order_id'], false);

        if(is_array($redirect_url) && isset($redirect_url['direct'])){
            redirect($redirect_url['redirect_url'])->send();
        }
        else{
            redirect(is_array($redirect_url) ? $redirect_url['redirect_url'] : $redirect_url)->send();
        }
        
	}

    /**
     * 测试退款
     */
    public function refund_test(){
        $orderid = request()->param('orderid');//支付单号
        fdump($orderid, 'Query_refund', 1);
        if(empty($orderid)){
            return api_output_error(1003, '支付单号orderid未传递！');
        }
        try {
            $pay_service = new PayService;
            $query = $pay_service->refund_test($orderid, 0.01);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $query);
    }

}