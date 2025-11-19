<?php
/**
 * 付呗
 * 文档  https://docs.51fubei.com
 * author by lumin  2019-08-12 
 */
class FubeiPay{
	private 		$mch_id ;// 商户ID
	private 		$secret ;// 商户secret
	private 		$notify_url ;//异步通知url
	public function __construct(){
		$this->mch_id 			= C('config.pay_fubei_mchid');
		$this->secret 			= C('config.pay_fubei_secret');
		// $this->notify_url = C('config.site_url').'/source/paylinx_page_notice.php';
		$this->notify_url = '';
	}

	//调起支付
	//$repay = 1的时候，表示为第一次失败了（原因是用户端需要输入密码）,所以请求第二次
	public function pay($order_info,$pay_info){
		if($pay_info['repay']){
			$data = [
			    "app_id"=>$this->mch_id,
			    "method"=>"openapi.payment.order.query",
			    "format"=>"json",
			    "sign_method"=>"md5",
			    "nonce"=>"1234567"
			];
			$content = [
			    "merchant_order_sn"=>$order_info['order_id']
			];
		}
		else{
			$data = [
			    "app_id"=>$this->mch_id,
			    "method"=>"openapi.payment.order.swipe",
			    "format"=>"json",
			    "sign_method"=>"md5",
			    "nonce"=>"1234567"
			];
			$content = [
			    "type"=>$order_info['pay_type'] == 'alipay' ? 2 : 1,
			    "merchant_order_sn"=>$order_info['order_id'],
			    "auth_code"=>$pay_info['auth_code'],
			    "total_fee"=>$order_info['order_total_money']
			];
		}
		$key = $this->secret;
		$data['biz_content'] = json_encode($content);
		$result = json_decode(Curl::execute($data,$key),true);
		if($result['result_code'] == '200'){
			if($result['data']['trade_state'] == 'USERPAYING'){
				$returnArr = array(
					'error'=>1,
					'repay' => 1,
					'msg'=>'用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”',
				);
			}
			elseif($result['data']['trade_state'] == 'REVOKED' || $result['data']['trade_state'] == 'REVOKING' || $result['data']['trade_state'] == 'REVOKED_ERROR'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'用户撤销支付'
				);
			}
			elseif($result['data']['trade_state'] == 'SUCCESS'){
				$returnArr = array(
					'error'=>0,
					'msg'=>'交易成功',
					'thirdid' => $result['data']['order_sn']
				);
			}
			else{
				$returnArr = array(
					'error'=>1,
					'msg'=>'未知错误:'.$result['data']['trade_state'],
				);
			}
		}
		else{
			$returnArr = array(
				'error'=>1,
				'msg'=>$result['result_message'],
			);
		}
		return $returnArr;
	}

}

class Curl
{
    const GATEWAY = "https://shq-api.51fubei.com/gateway";

    public static function execute($content,$key)
    {
        $content['sign'] = static::generateSign($content,$key);
        $result = static::mycurl(static::GATEWAY,$content);
        return $result;

    }
    private static function mycurl($url,$params = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);

        if (!empty($params)) {

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

        }
        $header = array("content-type: application/json");
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        $reponse = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($reponse, $httpStatusCode);
            }
        }
        curl_close($ch);
        return $reponse;

    }
    private static function generateSign($content,$key)
    {
        return strtoupper(static::sign(static::getSignContent($content).$key));

    }
    private static function getSignContent($content)
    {
        ksort($content);
        $signString = "";
        foreach ($content as $key=>$val){
             if(!empty($val)){
                 $signString .= $key."=".$val."&";
             }
        }
        $signString = rtrim($signString,"&");
        return $signString;

    }
    private static function sign($data)
    {
        return md5($data);
    }

}
