<?php
/*
 * 提现到微信余额
 * author 衡婷妹
 * date 20200817
 * */
namespace withdraw;
final class Weixin
{
    // 商户id
    public $mchid;
    public $appid;
    public $appkey;
    public $base_url;
    public $wechat_name;

    public function __construct()
    {
        $this->mchid = cfg('withdraw_weixin_mchid'); // 商户号
        $this->appid = cfg('withdraw_weixin_appid'); // 商户账号appid
        $this->appkey = cfg('withdraw_weixin_appkey'); // 商户账号appkey
        $this->base_url = 'https://api.mch.weixin.qq.com';
        $this->wechat_name = cfg('wechat_name');//公众号名称
    }

    /*
     * 签名
     * 1、签名算法
        （签名校验工具）
        签名生成的通用步骤如下：

        第一步，设所有发送或者接收到的数据为集合M，将集合M内非空参数值的参数按照参数名ASCII码从小到大排序（字典序），使用URL键值对的格式（即key1=value1&key2=value2…）拼接成字符串stringA。

        特别注意以下重要规则：

        ◆ 参数名ASCII码从小到大排序（字典序）；
        ◆ 如果参数的值为空不参与签名；
        ◆ 参数名区分大小写；
        ◆ 验证调用返回或微信主动通知签名时，传送的sign参数不参与签名，将生成的签名与该sign值作校验。
        ◆ 微信接口可能增加字段，验证签名时必须支持增加的扩展字段
        第二步，在stringA最后拼接上key得到stringSignTemp字符串，并对stringSignTemp进行MD5运算，再将得到的字符串所有字符转换为大写，得到sign值signValue。
    */
    private  function getSign($data){
        ksort($data);

        $msg = '';//密钥
        foreach ($data as $key => $value){
            if($value){
                $msg .= $key.'='.$value.'&';
            }
        }
        $msg .= 'key='.$this->appkey;
        $msg = strtoupper(md5($msg));
        return $msg;
    }

    public function curlPost($url,$data){

        // 项目根目录
        $DOCUMENT_ROOT = request()->server('DOCUMENT_ROOT');

        $ch = curl_init();
        $params[CURLOPT_URL] = $url; //请求url地址
        $params[CURLOPT_HEADER] = false; //是否返回响应头信息
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
        $params[CURLOPT_POST] = true;
        $params[CURLOPT_POSTFIELDS] = $data;
        $params[CURLOPT_SSL_VERIFYPEER] = false;
        $params[CURLOPT_SSL_VERIFYHOST] = false;
        //以下是证书相关代码
        $params[CURLOPT_SSLCERTTYPE] = 'PEM';
        $params[CURLOPT_SSLCERT] = $DOCUMENT_ROOT.str_replace(file_domain(),'',cfg('withdraw_weixin_client_cert'));//绝对路径
        $params[CURLOPT_SSLKEYTYPE] = 'PEM';
        $params[CURLOPT_SSLKEY] = $DOCUMENT_ROOT.str_replace(file_domain(),'',cfg('withdraw_weixin_client_key'));//绝对路径
        curl_setopt_array($ch, $params); //传入curl参数
        $result = curl_exec($ch); //执行

        $meta = curl_getinfo($ch);
        fdump('---------------start-----------------', 'curlPost' ,1);
        fdump($data, 'curlPost' ,1);
        fdump($result, 'curlPost' ,1);
        fdump('---------------end-------------------', 'curlPost' ,1);
        //关闭curl
        curl_close($ch);
        return $result;
    }

    function ArrToXml($arr)
    {
        if(!is_array($arr) || count($arr) == 0) return '';
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /*
    * 提现到微信余额
    * */
    public function withdraw($data){
        $url = $this->base_url.'/mmpaymkttransfers/promotion/transfers';

        if(empty($this->appid) || empty($this->mchid) || empty($this->appkey) || empty(cfg('withdraw_weixin_client_cert')) || empty(cfg('withdraw_weixin_client_key'))){

            $result['result']['err_code_des'] = '暂未配置微信提现支付信息，请至系统设置--付款管理--微信提现tab页中配置';
            fdump_api(['msg' => '暂未配置微信提现支付信息，请至系统设置--付款管理--微信提现tab页中配置','$data' => $data],'withdraw/errWithdrawLog',1);
            return $result;
        }

        $post_data['mch_appid'] = $this->appid;
        $post_data['mchid'] = $this->mchid;
        $post_data['nonce_str'] = md5(rand(0,999999)); // 随机字符串，不长于32位
        $post_data['partner_trade_no'] = $data['partner_trade_no']; // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有其它字符)，不长于32位
        $post_data['openid'] = $data['openid']; // 用户openid
        $post_data['check_name'] = 'FORCE_CHECK'; // 强校验真实姓名
        $post_data['re_user_name'] = $data['name']; // 收款用户姓名
        $post_data['amount'] = intval($data['money']); // 企业付款金额，单位为分
        $post_data['desc'] = '微信提现'.($data['money']/100).'元（'.$this->wechat_name.'）'; // 企业付款备注，必填。注意：备注中的敏感词会被转成字符*
        $post_data['spbill_create_ip'] = get_client_ip(); // 该IP同在商户平台设置的IP白名单中的IP没有关联，该IP可传用户端或者服务端的IP。
        $sign = $this->getSign($post_data); // 签名
        $post_data['sign'] = $sign;

        $xml_data = $this->ArrToXml($post_data);
        $result = $this->curlPost($url,$xml_data);
        fdump($post_data, '11withdraw' ,1);
        fdump($result, '11withdraw' ,1);
        if(empty($result)){
            $result = false;
        }else{
            $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        }
        $result['result'] = $result;
        $result['post_data'] = $post_data;

        $result['error'] = false; 
        if(empty($result['result'])){
            $result['msg'] = L_('付款失败,接口错误：请检查证书是否正确');
            $result['error'] = true;
            fdump_api(['msg' => '付款失败,接口错误：接口无返回内容','$url' => $url,'$data' => $data,'$post_data' => $post_data,'$result' => $result],'withdraw/errWithdrawLog',1);
        }elseif(!isset($result['result_code']) || $result['result_code'] != 'SUCCESS'){
            $result['error'] = true; 
            if($result['result']['err_code'] == 'NAME_MISMATCH'){
                $result['msg'] = L_('姓名与微信绑定实名认证姓名不一致，无法提现');
            }else{
                $result['msg'] = L_('付款失败,接口错误：'.$result['result']['err_code_des']);
            }
            fdump_api(['msg' => '付款失败,接口错误：接口无返回内容','$url' => $url,'$data' => $data,'$post_data' => $post_data,'$result' => $result],'withdraw/errWithdrawLog',1);
        }
        fdump_api(['msg' => '提现结果','$url' => $url,'$data' => $data,'$post_data' => $post_data,'$result' => $result],'withdraw/withdrawLog',1);
        return $result;
    }

    /*
    * 提现到银行卡
    * */
    public function withdrawToBank($data){
        $url = $this->base_url.'/mmpaysptrans/pay_bank';

        if(empty($this->appid) || empty($this->mchid) || empty($this->appkey) || empty(cfg('withdraw_weixin_client_cert')) || empty(cfg('withdraw_weixin_client_key'))){
            $result['error'] = true;
            $result['msg'] = $result['result']['err_code_des'] = '暂未配置微信提现支付信息，请至系统设置--付款管理--微信提现tab页中配置';
            fdump_api(['msg' => '暂未配置微信提现支付信息，请至系统设置--付款管理--微信提现tab页中配置','$data' => $data],'withdraw/errWithdrawToBankLog',1);
            return $result;
        }
		
		//生成密钥，加密姓名和银行卡号
		if(!$this->RasKey()){
			$result['msg'] = L_('获取微信密钥失败，请联系技术处理');
            $result['error'] = true;
            fdump_api(['msg' => '获取微信密钥失败，请联系技术处理','$data' => $data],'withdraw/errWithdrawToBankLog',1);
			return $result;
		}

        $post_data['mch_id'] = $this->mchid;
        $post_data['nonce_str'] = md5(rand(0,999999)); // 随机字符串，不长于32位
        $post_data['partner_trade_no'] = $data['partner_trade_no']; // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有其它字符)，不长于32位
        $post_data['amount'] = intval($data['money']); // 企业付款金额，单位为分
        $post_data['desc'] = '银行卡提现'.($data['money']/100).'元（'.$this->wechat_name.'）'; // 企业付款备注，必填。注意：备注中的敏感词会被转成字符*
        $post_data['enc_bank_no'] = $this->publicEncrypt($data['account']); // 收款方银行卡号	RSA加密
        $post_data['enc_true_name'] = $this->publicEncrypt($data['account_name']); // 收款方用户名	RSA加密
        $post_data['bank_code'] = $this->getBankCode($data['bank_name']); // 收款方开户行
		if(!$post_data['bank_code']){
			$result['msg'] = L_('您选择的银行不支持，请更换银行卡。支持银行请联系客服获取。');
            $result['error'] = true;
            fdump_api(['msg' => '您选择的银行不支持，请更换银行卡，支持银行请联系客服获取','$data' => $data,'$post_data' => $post_data],'withdraw/errWithdrawToBankLog',1);
			return $result;
		}
		
        $sign = $this->getSign($post_data); // 签名
        $post_data['sign'] = $sign;

        $xml_data = $this->ArrToXml($post_data);

        $result = $this->curlPost($url,$xml_data);
        if(empty($result)){
            $result = false;
        }else{
            $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        }
        $result['result'] = $result;
        $result['post_data'] = $post_data;

        $result['error'] = false; 
        if(empty($result)){
            $result['msg'] = L_('付款失败,接口错误：接口无返回内容');
            $result['error'] = true;
            fdump_api(['msg' => '付款失败,接口错误：接口无返回内容','$url' => $url,'$post_data' => $post_data,'$result' => $result],'withdraw/errWithdrawToBankLog',1);
        }elseif($result['result_code'] != 'SUCCESS'){
            $result['error'] = true; 
            $result['msg'] = L('付款失败,接口错误：'.$result['result']['err_code_des']);
            fdump_api(['msg' => '付款失败,接口错误：接口无返回内容','$url' => $url,'$post_data' => $post_data,'$result' => $result],'withdraw/errWithdrawToBankLog',1);
            
        }
        fdump_api(['msg' => '提现结果','$url' => $url,'$data' => $data,'$post_data' => $post_data,'$result' => $result],'withdraw/withdrawToBankLog',1);
        return $result;
    }
	
	private function publicEncrypt($data){
		// 进行加密
		$pubkey = openssl_pkey_get_public(file_get_contents(realpath('./upload/files/wxWithdrawToBankPubKey.pem')));
		$encrypt_data = '';
		$encrypted = '';
		$r = openssl_public_encrypt($data, $encrypt_data, $pubkey, OPENSSL_PKCS1_OAEP_PADDING);
		if($r){//加密成功，返回base64编码的字符串
			return base64_encode($encrypted.$encrypt_data);
		}else{
			return false;
		}
	}
	
	public function RasKey($datainfo=""){
		$pemFile = './upload/files/wxWithdrawToBankPubKey.pem';
		if(file_exists($pemFile)){
			return true;
		}
        $data=[
            //商户号
            "mch_id" 	=> $this->mchid,
           //随机字符串
            "nonce_str" => md5(rand(0,999999)), // 随机字符串，不长于32位
            //加密方式我是MD5
            "sign_type"	=> "MD5",
        ];
        //微信签名
        $data["sign"] = $this->getSign($data);
		
        //提交到的URL
        $url = "https://fraud.mch.weixin.qq.com/risk/getpublickey";
		
        //转换成XML格式POST到服务器
        $backxml = $this->curlPost($url, $this->ArrToXml($data));
		
        //将获取到的内容解析成对象
        $backarr = simplexml_load_string($backxml, 'SimpleXMLElement', LIBXML_NOCDATA);
		
		if($backarr->return_code != 'SUCCESS'){
            fdump_api(['msg' => '1获取微信密钥失败','$url' => $url,'$data' => $data,'$backarr' => $backarr],'withdraw/errRasKeyLog',1);
			return false;
		}
		
		if($backarr->result_code != 'SUCCESS'){
            fdump_api(['msg' => '2获取微信密钥失败','$url' => $url,'$data' => $data,'$backarr' => $backarr],'withdraw/errRasKeyLog',1);
			return false;
		}
		
		if(strtoupper(substr(PHP_OS,0,3)) == 'WIN'){
            fdump_api(['msg' => '3 不支持win系统','$url' => $url,'$data' => $data,'PHP_OS' => PHP_OS],'withdraw/errRasKeyLog',1);
			return false;
		}
		
		if(!function_exists('shell_exec')){
            fdump_api(['msg' => '4 不支持 shell_exec','$url' => $url,'$data' => $data,'$backarr' => $backarr],'withdraw/errRasKeyLog',1);
			return false;
		}
		

		$tmpPemFile = './upload/files/wxWithdrawToBankPubKeyTmp.pem';
        //保存成PEM文件
        file_put_contents($tmpPemFile, $backarr->pub_key);
		
		
		$output = shell_exec('openssl rsa -RSAPublicKey_in -in ' . realpath($tmpPemFile) . ' -pubout');
		if($output){
			file_put_contents($pemFile, $output);
		}
		unlink($tmpPemFile);
		
		return true;
	}

    public function getBankCode($bankName){
        $bankArr = [
            '工商银行' => '1002',
            '中国工商银行' => '1002',
            '农业银行' => '1005',
            '中国农业银行' => '1005',
            '建设银行' => '1003',
            '中国建设银行' => '1003',
            '中国银行' => '1026',
            '交通银行' => '1020',
            '招商银行' => '1001',
            '邮储银行' => '1066',
            '民生银行' => '1006',
            '平安银行' => '1010',
            '中信银行' => '1021',
            '浦发银行' => '1004',
            '兴业银行' => '1009',
            '光大银行' => '1022',
            '广发银行' => '1027',
            '华夏银行' => '1025',
            '宁波银行' => '1056',
            '北京银行' => '4836',
            '上海银行' => '1024',
            '南京银行' => '1054',
            '长子县融汇村镇银行' => '4755',
            '长沙银行' => '4216',
            '浙江泰隆商业银行' => '4051',
            '中原银行' => '4753',
            '企业银行（中国）' => '4761',
            '顺德农商银行' => '4036',
            '衡水银行' => '4752',
            '长治银行' => '4756',
            '大同银行' => '4767',
            '河南省农村信用社' => '4115',
            '宁夏黄河农村商业银行' => '4150',
            '山西省农村信用社' => '4156',
            '安徽省农村信用社' => '4166',
            '甘肃省农村信用社' => '4157',
            '天津农村商业银行' => '4153',
            '广西壮族自治区农村信用社' => '4113',
            '陕西省农村信用社' => '4108',
            '深圳农村商业银行' => '4076',
            '宁波鄞州农村商业银行' => '4052',
            '浙江省农村信用社联合社' => '4764',
            '江苏省农村信用社联合社' => '4217',
            '江苏紫金农村商业银行股份有限公司' => '4072',
            '北京中关村银行股份有限公司' => '4769',
            '星展银行（中国）有限公司' => '4778',
            '枣庄银行股份有限公司' => '4766',
            '海口联合农村商业银行股份有限公司' => '4758',
            '南洋商业银行（中国）有限公司' => '4763',
        ];

        return $bankArr[$bankName];
    }
}
?>