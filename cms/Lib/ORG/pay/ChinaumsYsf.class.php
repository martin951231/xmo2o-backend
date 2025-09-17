<?php
/**
 * 云闪付小程序支付
 * author lumin
 * 2021-01-16
 */

class ChinaumsYsf{
	public $key;
	public $chinaums_msgsrcid;
	public function __construct(){
		$this->key = '4XXfFherZ2i2W2HZibMe4pibzPrmS6kje7ryWt42sypnm76t';
		$this->chinaums_msgsrcid = '10Y9QHD';
	}

	public function pay($order, $sub_mer_no = ''){
		$order_id_arr = explode('_',$order['orderNo']);
		$merOrderId = $this->createOrderNo($order['orderNo']);
		$money = getFormatNumber($order['payment_money']*100);
		$goods = [];
		if($sub_mer_no){
			foreach ($order['order_content'] as $key => $value) {
				$temp = [
					'goodsId' => $value['goods_id'],
					'goodsName' => $value['name'],
					'quantity' => $value['num'],
					'price' => getFormatNumber($value['price']*100),
					'subMerchantId' => $sub_mer_no,
					'subOrderAmount' => $money
				];
				$goods[] = $temp;
			}
		}
		$subOrders = [];
		if(!empty($sub_mer_no)){
			$subOrders[] = [
				'mid' => $sub_mer_no,
				'merOrderId' => $merOrderId,
				'totalAmount' => $money
			];
		}

		$params = [
			'msgId' => $this->chinaums_msgsrcid,
			'msgSrc' => 'WWW.GZDMDWLKJ.COM',
			'msgType' => 'uac.miniOrder',
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => C("config.chinaums_mid"),
			'tid' => C("config.chinaums_tid"),
			// 'mid' => '89852274816010A',
			// 'tid' => '27135733',
			'instMid' => 'MINIDEFAULT',
			'totalAmount' => $money,
			'signType' => 'SHA256',
			'tradeType' => 'MINI',
			'goods' => $goods,
			'subOrders' => $subOrders,
			'notifyUrl' => C('config.site_url').'/source/chinaumsysf_page_notice.php',
		];
		if(!empty($sub_mer_no)){
			$params['divisionFlag'] = true;
			$params['platformAmount'] = 0;
		}
		$params['sign'] = $this->generateSign($params);
		fdump('https://qr.chinaums.com/netpay-route-server/api/', "lumin0520", 1);
		fdump(json_encode($params), "lumin0520", 1);
		//模拟请求
        $resp = $this->curl('https://qr.chinaums.com/netpay-route-server/api/', $params);
        $result = json_decode($resp, true);
        fdump($result, "lumin0520", 1);
        if($result['errCode'] != 'SUCCESS'){
        	throw new Exception($result['errMsg']);        	
        }
        return [
			'error' => 0,
			'ysfmini_param' => json_encode($result['miniPayRequest']),
			'redirctUrl' => C('config.site_url').'/index.php?c=LowFeePayNotice&a=ysfmini_query&pay_type=unionpay&order_type='.$order_id_arr[0].'&order_id='.$order_id_arr[1].'&source=1',
		];
	}

	public function check($order){
		$merOrderId = $this->createOrderNo($order['orderNo']);
		$order_id_arr = explode('_',$order['orderNo']);
		$params = [
			'msgId' => $this->chinaums_msgsrcid,
			'msgSrc' => 'WWW.GZDMDWLKJ.COM',
			'msgType' => 'query',
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => C("config.chinaums_mid"),
			'tid' => C("config.chinaums_tid"),
			// 'mid' => '89852274816010A',
			// 'tid' => '27135733',
			'instMid' => 'MINIDEFAULT',
			'signType' => 'SHA256',
		];
		$params['sign'] = $this->generateSign($params);
		fdump('https://qr.chinaums.com/netpay-route-server/api/', "lumin0520", 1);
		fdump(json_encode($params), "lumin0520", 1);
		//模拟请求
        $resp = $this->curl('https://qr.chinaums.com/netpay-route-server/api/', $params);
        $result = json_decode($resp, true);
        fdump($result, "lumin0520", 1);
        if($result['errCode'] != 'SUCCESS'){
        	throw new Exception($result['errMsg']);        	
        }

        M('Payment_money_order')->where(['orderid'=>$order_id_arr[1]])->data(array('paid_money'=>$result['totalAmount']/100))->save();
			
		$go_pay_param = array(
			'third_id'		=>	$result['targetOrderId'],
			'pay_money'		=>	$result['totalAmount']/100,
			'order_id'		=>	$order_id_arr[1],
			'order_type'	=>	$order_id_arr[0],
			'pay_type'		=>	'unionpay',
			'error'			=>	0,
			'is_mobile'		=>	1,
			'errorMsg'		=>	'',
			'pay_time'		=>	strtotime($result['payTime']),
			'is_own'		=>	0,
		);
		return $go_pay_param;
	}

	//生成银联账单编号（因为他们有特别的要求，必须用他们的）
	public function createOrderNo($order_no, $ret = false){
		if(!$ret){
			if(strpos($order_no, '_')){
				$arr = explode('_', $order_no);
				$order_no = $arr[1];
			}
			$ori_order_no = $this->chinaums_msgsrcid.$order_no;
		}
		else{
			$ori_order_no = str_replace($this->chinaums_msgsrcid, '', $order_no);
		}
		return $ori_order_no;
	}

	/**
     * 根绝类型生成sign
     * @param $params
     * @param string $signType
     * @return string
     */
    public function generateSign($params) {
        return $this->getSignContent($params);
    }

    /**
     * 生成signString
     * @param $params
     * @return string
     */
    function getSignContent($params) {
        //sign不参与计算
        $params['sign'] = '';

        //排序
        ksort($params);

        $paramsToBeSigned = [];
        foreach ($params as $k=>$v) {
    		if(is_array($params[$k])){
    			$v = json_encode($v,JSON_UNESCAPED_UNICODE);
    		}else if(trim($v) == ""){
				continue;
			}else if(is_bool($v)){
				$v = $v ? 'true' : 'false';
			}
    		$paramsToBeSigned[] = $k.'='. $v;
        }
        unset ($k, $v);
        //签名字符串
        $stringToBeSigned = (implode('&', $paramsToBeSigned));
		//str_replace('¬','&not',$stringToBeSigned);
        $stringToBeSigned .= $this->key;
        return hash("sha256", $stringToBeSigned);
    }

    /**
     * 模拟POST请求
     * @param $url
     * @param null $postFields
     * @return mixed
     * @throws Exception
     */
    protected function curl($url, $postFields = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        //有post参数-设置
        if (is_array($postFields) && 0 < count($postFields)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
        }

        $header[] = "Content-type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

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
}