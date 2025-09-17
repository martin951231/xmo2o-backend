<?php
class baodiTicket{
	
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
	public function bookTicket(array $orderInfo = []){
		$postArr = [
			'RealOperate' => [
				'XType' => 'Gemstar',
				'OpType' => '票券预订',
				'ResQuery' => [
					'OrderOperate' => 'Add',					//必传参数操作类型 Add-新增 Update-更新
					'ReqTime' => date('Y-m-d H:i:s'),			//请求时间
					'AccountID' => $orderInfo['AccountID'],		//账户ID
					'SerialId' => $orderInfo['order_id'],		//网上订单编号
					'PlayDate' => $orderInfo['startDate'],		//游玩起始日期
					'ExpiryDate' => $orderInfo['endDate'],		//游玩截止日期
					'TicketTypeId' => $orderInfo['custom_product_id'],		//票型ID
					'TicketCount' => $orderInfo['num'],						//预订张数
					'TicketPrice' => $orderInfo['total_money'],				//预订价格
					'PayType' => 'onlinepayment',							//支付方式
					'OrderStatus' => 'p',									//订单状态
					'IsTicketForMany' => '0',								//是否一票多人
					'TravelerName' => $orderInfo['username'],				//取票人姓名
					'TravelerMobile' => $orderInfo['userphone'],			//取票人电话号码
					'IdentityCard' => $orderInfo['idcard'],					//取票人身份证
				]
			]
		];
		$postXml = $this->arrToXml($postArr, false);
		$res = httpRequest(C('config.baodi_ticket_url'), 'POST', $postXml, ["Content-Type: text/xml;charset=utf8"]);
		if(isset($res[1]) && $res[1]){
			$res[1] = str_replace('gbk', 'utf-8', $res[1]);
		}
		$resultArr = json_decode(json_encode(simplexml_load_string($res[1], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		fdump_sql([C('config.baodi_ticket_url'), $postArr, $postXml, $res, $resultArr], 'baodi_bookTicket_data');
		return $resultArr;
	}
	
	public function verifyTicket(){
		$input = file_get_contents('php://input');
		if(!$input){
			$input = '<?xml version="1.0" encoding="gbk"?><RealOperate><XType>Gemstar</XType><OpType>核销订单</OpType><VerificationOrder><ReqTime>2019-06-11 11:55:19.140</ReqTime><AccountID>捷信达</AccountID><Rows><Row><OrderNo>W900020005730000516242</OrderNo><CheckTime>2019-05-31 11:23:18</CheckTime><CheckNum>1</CheckNum><OrderAllNum>1</OrderAllNum></Row></Rows></VerificationOrder></RealOperate>';
		}
		$input = str_replace('"gbk"', '"utf-8"', $input);
		$resultArr = json_decode(json_encode(simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		
		$verifyRows = [];
		
		if($resultArr && $resultArr['OpType'] == '核销订单'){
			import('ORG.Net.Http');
			$http = new Http();
			$url = C('config.site_url') . '/appapi.php?c=Storestaff&a=group_array_verify';
			
			//如果返回多条会成两维数据，返回一条会是一维数据。兼容下
			$rowList = is_array($resultArr['VerificationOrder']['Rows']['Row']) && isset($resultArr['VerificationOrder']['Rows']['Row'][0]) ? $resultArr['VerificationOrder']['Rows']['Row'] : $resultArr['VerificationOrder']['Rows'];
			
			foreach($rowList as $value){
				$verifyRow = [
					'OrderNo' => $value['OrderNo'],
					'OrderStatus' => '1',
				];
				if($value && $value['OrderNo']){
					//首先查group_pass_relation
					$now_pass = M('Group_pass_relation')->where(['group_pass' => $value['TicketNo'] ? : $value['OrderNo']])->find();
					if($now_pass){
						if($now_pass['status'] == 0){
							$now_order = M('Group_order')->where(['order_id' => $now_pass['order_id']])->find();
							$now_staff = M('Merchant_store_staff')->where(['store_id' => $now_order['store_id']])->find();
							if($now_staff){
								$ticketArr = ticket::create($now_staff['id'], 'packapp', true);
								$ticket = $ticketArr['ticket'];
								
								$data = [
									'Device-Id' => 'packapp',
									'ticket' => $ticket,
									'order_id' => $now_pass['order_id'],
									'group_pass' => $now_pass['group_pass'],
									'staff_name' => $resultArr['VerificationOrder']['AccountID'],
									'app_type' => 'ios',
								];
								$result = $http::curlPost($url, $data);
								fdump_sql([$url, $data, $result], 'verifyGroupOrderResult');
								if(isset($result['errorCode']) && $result['errorCode'] == 0){
									$verifyRow['OrderStatus'] = '0';
								}
							}
						}
					}
				}
				$verifyRows[]['Row'] = $verifyRow;
			}
		}
		$returnArr = [
			'VerificationResult' => [
				'rspCode' => count($verifyRows) >= 1 ? '0' : '1',
				'rspDesc' => count($verifyRows) >= 1 ? '操作成功' : '操作失败',
				'Rows' => $verifyRows
			]
		];
		echo $this->arrToXml($returnArr, false);
		die;
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