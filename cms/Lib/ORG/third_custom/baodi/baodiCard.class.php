<?php
class baodiCard{
	/**
	 * 获取用户会员卡列表
	 *
	 * @Description
	 * @Author Jaty
	 * @DateTime 2021-06-21
	 *
	 * @param string $phone 用户手机号
	 * @return array
	 */
	public function getCardList(string $phone){
		$postArr = [
			'RealOperate' => [
				'XType' => 'Gemstar',
				'OpType' => '会员查询',
				'MbrQuery' => [
					'Mobile' => $phone
				],
			]
		];
		$postXml = $this->arrToXml($postArr, false);
		$res = httpRequest(C('config.baodi_hotel_url'), 'POST', $postXml, ["Content-Type: text/xml;charset=utf8"]);
		$res[1] = str_replace('encoding="gbk"', 'encoding="utf-8"', $res[1]);
		$resultArr = json_decode(json_encode(simplexml_load_string($res[1], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $resultArr;
	}
	
	/**
	 * 获取用户会员卡
	 *
	 * @Description
	 * @Author Jaty
	 * @DateTime 2021-06-21
	 *
	 * @param string $phone 用户卡号
	 * @return array
	 */
	public function getCardById(string $ProfileId){
		$postArr = [
			'RealOperate' => [
				'XType' => 'Gemstar',
				'OpType' => '会员查询',
				'MbrQuery' => [
					'ProfileID' => $ProfileId
				],
			]
		];
		$postXml = $this->arrToXml($postArr, false);
		$res = httpRequest(C('config.baodi_hotel_url'), 'POST', $postXml, ["Content-Type: text/xml;charset=utf8"]);
		$res[1] = str_replace('encoding="gbk"', 'encoding="utf-8"', $res[1]);
		$resultArr = json_decode(json_encode(simplexml_load_string($res[1], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		
		if(is_string($resultArr['Rows'][0]) && empty(trim($resultArr['Rows'][0]))){
			$resultArr = [
				'MessageNo' => '-1',
				'Message' => '会员卡不存在',
			];
		}
		return $resultArr;
	}
	
	/**
	 * 用户会员卡余额交易
	 *
	 * @Description
	 * @Author Jaty
	 * @DateTime 2021-06-21
	 *
	 * @param string $phone 用户卡号
	 * @param string $amount 余额增减，正数为加，负数为减
	 * @param string $remark 余额增减，正数为加，负数为减
	 * @return array
	 */
	public function transCardBalance(string $ProfileId, float $amount, string $remark)
	{
		$postArr = [
			'RealOperate' => [
				'XType' => 'Gemstar',
				'OpType' => '会员交易',
				'ProfileCa' => [
					'ProfileID' => $ProfileId,
					'OutletCode' => '0',
					'PaymentDesc' => '12',
					'Amount' => $amount,
					'Remark' => $remark,
					'HotelCode' => '20',
				],
			]
		];
		$postXml = $this->arrToXml($postArr, false);
		$res = httpRequest(C('config.baodi_hotel_url'), 'POST', $postXml, ["Content-Type: text/xml;charset=utf8"]);
		$res[1] = str_replace('encoding="gbk"', 'encoding="utf-8"', $res[1]);
		$resultArr = json_decode(json_encode(simplexml_load_string($res[1], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		
		return $resultArr;
	}
	
	
	/**
	 * 数组转XML
	 *
	 * @Description
	 * @Author Jaty
	 * @DateTime 2021-06-21
	 *
	 * @param array $data  数组
	 * @param boolean $root   是否需要 xml 根节点
	 * @param boolean $xmlVersion   是否返回xml version
	 * @param boolean $cdata   是否包裹 CDATA
	 * @return string
	 */
	private function arrToXml(array $data, bool $root=true, bool $xmlVersion=true, bool $cdata=false)
	{
		$str = "";
		
		if($xmlVersion){
			$str.= '<?xml version="1.0" encoding="utf-8"?>';
		}

		if ($root) {
			$str .= "<xml>";
		}
		
		foreach ($data as $key => $val) {
			if (is_array($val)) {
				$child = $this->arrToXml($val, false, false);
				$str .= "<$key>$child</$key>";
			} else {
				if($cdata){
					$str .= "<$key><![CDATA[$val]]></$key>";
				}else{
					$str .= "<$key>$val</$key>";
				}
			}
		}
		if ($root) {
			$str .= "</xml>";
		}
		return $str;
	}
}
?>