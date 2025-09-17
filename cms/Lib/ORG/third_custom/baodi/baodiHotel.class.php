<?php
class baodiHotel{
	/**
	 * 获取日数据，包含 库存、价格
	 *
	 * @Description
	 * @Author Jaty
	 * @DateTime 2021-06-21
	 *
	 * @param string $beginDay 开始时间
	 * @param string $endDay  截止时间
	 * @param string $roomType 房类代码
	 * @param string $hotelCode 酒店代码
	 * @param string $rateCode 房价代码
	 * @param boolean $joinDay 是获取每一天数据还是统一获取
	 * @return array
	 */
	public function getDayData(string $beginDay, string $endDay, string $roomType, string $hotelCode, string $rateCode, bool $joinDay=false){
		$tmpBeginTime = str_replace('-', '', $beginDay);
		$tmpEndTime = str_replace('-', '', $endDay);
		$dayData = [];
		
		//循环获取每一天
		if(!$joinDay){
			for($i = $tmpBeginTime; $i <= $tmpEndTime; $i++){
				$tmpDay = date('Y-m-d', strtotime($i));

				//获取日库存
				$tmpStockArr = $this->getDayStock($tmpDay, $tmpDay, $roomType, $hotelCode);
				$dayData[$i]['stock'] = $tmpStockArr['Rows']['Row']['Available'];

				//获取日价格
				$tmpStockArr = $this->getDayPrice($tmpDay, $tmpDay, $roomType, $hotelCode, $rateCode);
				$dayData[$i]['price'] = $tmpStockArr[$tmpDay];
			}
		}else{
			$tmpBeginTime = date('Y-m-d', strtotime($tmpBeginTime));
			$tmpEndTime = date('Y-m-d', strtotime($tmpEndTime));

			//获取日库存
			$tmpStockArr = $this->getDayStock($tmpBeginTime, $tmpEndTime, $roomType, $hotelCode);

			//获取日价格
			$tmpPriceArr = $this->getDayPrice($tmpBeginTime, $tmpEndTime, $roomType, $hotelCode, $rateCode);
			
			foreach($tmpPriceArr as $key => $value){
				$dayData[$key]['stock'] = $tmpStockArr['Rows']['Row']['Available'];
				$dayData[$key]['price'] = $value;
			}
		}
		
		return $dayData;
	}
	
	/**
	 * 获取日数据，包含 库存
	 *
	 * @Description
	 * @Author Jaty
	 * @DateTime 2021-06-21
	 *
	 * @param string $beginDay 开始时间
	 * @param string $endDay  截止时间
	 * @param string $roomType 房类代码
	 * @param string $hotelCode 酒店代码
	 * @return array
	 */
	public function getDayStock(string $beginDay, string $endDay, string $roomType, string $hotelCode){
		$postArr = [
			'RealOperate' => [
				'XType' => 'Gemstar',
				'OpType' => '客房房量查询',
				'HotelCode' => $hotelCode,
				'BeginDate' => $beginDay,
				'EndDate' => $endDay,
				'RoomType' => $roomType
			]
		];
		$postXml = $this->arrToXml($postArr, false);
		$res = httpRequest(C('config.baodi_hotel_url'), 'POST', $postXml, ["Content-Type: text/xml;charset=utf8"]);
		$resultArr = json_decode(json_encode(simplexml_load_string($res[1], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $resultArr;
	}
	
	/**
	 * 获取日数据，包含 价格
	 *
	 * @Description
	 * @Author Jaty
	 * @DateTime 2021-06-21
	 *
	 * @param string $beginDay 开始时间
	 * @param string $endDay  截止时间
	 * @param string $roomType 房类代码，不传返回所有
	 * @param string $hotelCode 酒店代码
	 * @param string $rateCode 房价代码
	 * @return array
	 */
	public function getDayPrice(string $beginDay, string $endDay, string $roomType = '', string $hotelCode, string $rateCode){
		$postArr = [
			'RealOperate' => [
				'XType' => 'Gemstar',
				'OpType' => '客房房价明细查询',
				'HotelCode' => $hotelCode,
				'BeginDate' => $beginDay,
				'EndDate' => $endDay,
				'RateCode' => $rateCode
			]
		];
		$postXml = $this->arrToXml($postArr, false);
		$res = httpRequest(C('config.baodi_hotel_url'), 'POST', $postXml, ["Content-Type: text/xml;charset=utf8"]);
		$resultArr = json_decode(json_encode(simplexml_load_string($res[1], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		
		if($roomType){
			$resultOneRoomType = [];
			foreach($resultArr['Rows']['Row'] as $value){
				if($value['RoomType'] == $roomType){
					$resultOneRoomType[$value['BeginDate']] = $value['Rate'];
				}
			}
			return $resultOneRoomType;
		}else{
			return $resultArr;
		}
	}
	
	/**
	 * 预定酒店
	 *
	 * @Description
	 * @Author Jaty
	 * @DateTime 2021-07-22
	 *
	 * @param array $bookData 预定数据
	 * @return array
	 */
	public function bookHotel(array $bookData){
		$postArr = [
			'RealOperate' => [
				'XType' => 'Gemstar',
				'OpType' => '客房预订'
			]
		];
		$postArr['RealOperate']['Reservation'] = $bookData;
		$postXml = $this->arrToXml($postArr, false);
		$res = httpRequest(C('config.baodi_hotel_url'), 'POST', $postXml, ["Content-Type: text/xml;charset=utf8"]);
		$resultArr = json_decode(json_encode(simplexml_load_string($res[1], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		fdump_sql([C('config.baodi_hotel_url'), $postArr, $postXml, $res, $resultArr], 'baodi_bookHotel_data');
		return $resultArr;
	}
	
	/**
	 * 预定酒店提交
	 *
	 * @Description
	 * @Author Jaty
	 * @DateTime 2021-07-22
	 *
	 * @param array $bookData 预定数据
	 * @return array
	 */
	public function bookHotelPaid(array $paid_data){
		$postArr = [
			'RealOperate' => [
				'XType' => 'Gemstar',
				'OpType' => '客房预订确认'
			]
		];
		$postArr['RealOperate']['Reservation'] = $paid_data;
		$postXml = $this->arrToXml($postArr, false);
		$res = httpRequest(C('config.baodi_hotel_url'), 'POST', $postXml, ["Content-Type: text/xml;charset=utf8"]);
		$resultArr = json_decode(json_encode(simplexml_load_string($res[1], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		fdump_sql([C('config.baodi_hotel_url'), $postArr, $postXml, $res, $resultArr], 'baodi_bookHotelPaid_data');
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
				if(is_numeric($key)){
					$str .= "$child";
				}else{
					$str .= "<$key>$child</$key>";
				}
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