<?php
/**
 * 润亚定制
 * 对接商盈通
 * add by lumin
 * 2019.05.13
 */
class UserShangyingtongModel extends Model
{
	protected $runya_to_shangyingtong ;//是否开启对接商盈通
	protected $shangyingtong_url ;//对接商盈通接口地址
	protected $shangyingtong_appid ;//商盈通应用ID
	protected $syt_jgbh ;
	protected $cardSubCls ;//卡子类型号
	protected $cert_path ;//证书
	protected $cert_pwd ;//证书密码
	protected $shanghuhao ;//商家号

	public function _initialize(){
		$this->runya_to_shangyingtong 	= C("config.runya_to_shangyingtong");
		$this->shangyingtong_url 		= C("config.shangyingtong_url");
		$this->shangyingtong_appid 		= C("config.shangyingtong_appid");
		$this->syt_jgbh					= C("config.shangyingtong_jgbh");//机构编号
		$this->cardSubCls				= C("config.shangyingtong_cardSubCls");
		$this->cert_path				= '.'.C("config.shangyingtong_cert");
		$this->cert_pwd					= C("config.shangyingtong_cert_pwd");
		$this->shanghuhao				= C("config.shangyingtong_shanghuhao");
	}

	/**
	 * 注册开卡
	 * @param  [type] $phone 手机号
	 * @param  [type] $uid 小猪平台id
	 * @return  bool 
	 */
	public function reg($phone, $uid){
		if(empty($phone) || empty($uid) || $this->runya_to_shangyingtong == '0' || !$this->shangyingtong_appid) return false;
		//业务参数
		$json = json_encode(array(
			'head' => array(
				'Ver' => '1.0',
				'App' => 'CARD',
				'SndCode' => $this->syt_jgbh,
				'RecCode' => '00000000',
				'MsgId' => $this->syt_jgbh.date('YmdHis').$this->milliseconds().rand(1000000,9999999),
				'TimeStamp' => date('YmdHis').$this->milliseconds()
			),
			'body' => array(
				'issNo' => $this->syt_jgbh,
				'cardSubCls' => $this->cardSubCls,
				'phoneNum'   => $phone,
				'cardSelAmt' => 0.00,
				'cardMakeFee' => 0.00
			)
		));

		$param = array(
			'app_id' => $this->shangyingtong_appid,
			'method' => 'gnete.sytbc.trade.OpenAcctTradeRpcService.openCard',//请求接口 接口服务.方法名
			'timestamp' => date('Y-m-d H:i:s'),
			'v' => '1.0.1',
			'sign_alg' => '0',
			'biz_content' => $json
		);

		$data = $this->getSignStr($param);
		fdump_sql($data, "shangyingtong_reg_head");
		import('ORG.Net.Http');
		$http = new Http();
		$return = Http::curlPost($this->shangyingtong_url, $data);
		fdump_sql($return, "shangyingtong_reg_return");
		$cardId = '';
		$errcode= '';
		$errmsg = '';
		$transSn = '';//交易流水
		if($return['code'] == '00000'){
			if($return['response']['body']['rspCode'] == '00'){
				$cardId = $return['response']['body']['cardId'];
				$transSn = $return['response']['body']['transSn'];
			}
			else{
				$errcode = $return['response']['body']['rspCode'];
				$errmsg  = $return['response']['body']['postScript'];
				$transSn = $return['response']['body']['transSn'];
			}
		}
		else{
			$errcode = $return['code'];
			$errmsg = $return['msg'];
		}
		$insert = array(
			'uid' => $uid,
			'phone' => $phone,
			'card_id' => $cardId,
			'add_time' => time(),
			'errcode' => $errcode,
			'errmsg' => $errmsg,
			'trans_sn' => $transSn,
		);
		if($this->data($insert)->add()){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * 充值
	 * @param  [type] $uid 	   小猪平台用户ID
	 * @param  [type] $money   充值金额
	 * @return [type]          [description]
	 */
	public function recharge($uid, $money){
		if(empty($uid) || empty($money) || $this->runya_to_shangyingtong == '0' || !$this->shangyingtong_appid) return false;
		$where = array(
			'uid' => $uid,
			'card_id' => array('neq', ''),
			'errcode' => array('eq', '')
		);
		$now_user = $this->field("card_id")->where($where)->order('add_time asc')->find();
		if(empty($now_user) || empty($now_user['card_id'])) return false;
		//业务参数
		$json = json_encode(array(
			'head' => array(
				'Ver' => '1.0',
				'App' => 'CARD',
				'SndCode' => $this->syt_jgbh,
				'RecCode' => '00000000',
				'MsgId' => $this->syt_jgbh.date('YmdHis').$this->milliseconds().rand(1000000,9999999),
				'TimeStamp' => date('YmdHis').$this->milliseconds()
			),
			'body' => array(
				'cardId' => $now_user['card_id'],
				'depPtVal' => $money,
				'realPtVal' => $money,
				'depType'   => '02',
				'entryMode' => '012',
				'condCode' => '65',
				'currency' => '156'
			)
		));

		$param = array(
			'app_id' => $this->shangyingtong_appid,
			'method' => 'gnete.sytbc.trade.TransTradeRpcService.recharge',//请求接口 接口服务.方法名
			'timestamp' => date('Y-m-d H:i:s'),
			'v' => '1.0.1',
			'sign_alg' => '0',
			'biz_content' => $json
		);

		$data = $this->getSignStr($param);
		fdump_sql($data, "shangyingtong_recharge_head");
		import('ORG.Net.Http');
		$http = new Http();
		$return = Http::curlPost($this->shangyingtong_url, $data);
		fdump_sql($return, "shangyingtong_recharge_return");
	}

	/**
	 * 消费
	 * @param  [type] $uid   小猪平台用户ID
	 * @param  [type] $money 消费金额
	 * @return [type]        [description]
	 */
	public function consume($uid, $money){
		if(empty($uid) || empty($money) || $this->runya_to_shangyingtong == '0' || !$this->shangyingtong_appid) return false;
		$where = array(
			'uid' => $uid,
			'card_id' => array('neq', ''),
			'errcode' => array('eq', '')
		);
		$now_user = $this->field("card_id")->where($where)->order('add_time asc')->find();
		if(empty($now_user) || empty($now_user['card_id'])) return false;
		//业务参数
		$json = json_encode(array(
			'head' => array(
				'Ver' => '1.0',
				'App' => 'CARD',
				'SndCode' => $this->syt_jgbh,
				'RecCode' => '00000000',
				'MsgId' => $this->syt_jgbh.date('YmdHis').$this->milliseconds().rand(1000000,9999999),
				'TimeStamp' => date('YmdHis').$this->milliseconds()
			),
			'body' => array(
				'cardId' => $now_user['card_id'],
				'transAmt' => $money,
				'transType' => '10',
				'entryMode'   => '012',
				'condCode' => '65',
				'acpter' => $this->shanghuhao,
				'currency' => '156'
			)
		));

		$param = array(
			'app_id' => $this->shangyingtong_appid,
			'method' => 'gnete.sytbc.trade.TransTradeRpcService.consume',//请求接口 接口服务.方法名
			'timestamp' => date('Y-m-d H:i:s'),
			'v' => '1.0.1',
			'sign_alg' => '0',
			'biz_content' => $json
		);

		$data = $this->getSignStr($param);
		fdump_sql($data, "shangyingtong_consume_head");
		import('ORG.Net.Http');
		$http = new Http();
		$return = Http::curlPost($this->shangyingtong_url, $data);
		fdump_sql($return, "shangyingtong_consume_return");
	}

	/**
	 * 余额查询
	 * @param  [type] $uid 小猪平台用户ID
	 * @return [type]      [description]
	 */
	public function yue($uid){
		if(empty($uid) || $this->runya_to_shangyingtong == '0' || !$this->shangyingtong_appid) return false;
		$where = array(
			'uid' => $uid,
			'card_id' => array('neq', ''),
			'errcode' => array('eq', '')
		);
		$now_user = $this->field("card_id")->where($where)->order('add_time asc')->find();
		if(empty($now_user) || empty($now_user['card_id'])) return false;
		//业务参数
		$json = json_encode(array(
			'head' => array(
				'Ver' => '1.0',
				'App' => 'CARD',
				'SndCode' => $this->syt_jgbh,
				'RecCode' => '00000000',
				'MsgId' => $this->syt_jgbh.date('YmdHis').$this->milliseconds().rand(1000000,9999999),
				'TimeStamp' => date('YmdHis').$this->milliseconds()
			),
			'body' => array(
				'cardId' => $now_user['card_id'],
				'subCls' => '01',
				'entryMode'   => '012',
				'condCode' => '65',
				'acpter' => $this->shanghuhao,
				'currency' => '156'
			)
		));

		$param = array(
			'app_id' => $this->shangyingtong_appid,
			'method' => 'gnete.sytbc.query.CardInfoQryRpcService.queryBalance',//请求接口 接口服务.方法名
			'timestamp' => date('Y-m-d H:i:s'),
			'v' => '1.0.1',
			'sign_alg' => '0',
			'biz_content' => $json
		);

		$data = $this->getSignStr($param);
		fdump_sql($data, "shangyingtong_consume_head");
		import('ORG.Net.Http');
		$http = new Http();
		$return = Http::curlPost($this->shangyingtong_url, $data);
		fdump_sql($return, "shangyingtong_consume_return");
		dump($return);exit;
	}

	public function milliseconds($format = 'u', $utimestamp = null)
	{
	    if (is_null($utimestamp)){
	        $utimestamp = microtime(true);
	    }
	    $timestamp = floor($utimestamp);
	    $milliseconds = round(($utimestamp - $timestamp) * 1000);//改这里的数值控制毫秒位数
	    return $milliseconds;
	}

	public function getSignStr($param){
		if(empty($param)) return '';
		$str = [];
		foreach ($param as $key => $value) {
			$str[] = $key."=".$value;
		}
		$sign = $this->SHA1withRSA(implode('&', $str));
		$return = implode('&', $str)."&sign=".$sign;
		return $return;
	}

	/**
     * 秘钥加密
     *
     * @param string $data 之前生成好的需加密内容
     * @param $key 私钥证书位置
     * @param string $pwd 证书密码
     *
     * @return string
     */
	public function SHA1withRSA($data)
    {
        openssl_pkcs12_read(file_get_contents($this->cert_path), $certs, $this->cert_pwd); 
        if (!$certs) return;
        $signature = '';
        openssl_sign($data, $signature, $certs['pkey']);
        return bin2hex($signature);
    }
}
?>