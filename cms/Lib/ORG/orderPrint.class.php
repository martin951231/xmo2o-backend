<?php

use printer\Trenditiot;

class orderPrint
{
	public $serverUrl;
	
	public $key;
	
	public $topdomain;
	
	public function __construct($server_key, $server_topdomain)
	{
		$this->serverUrl = 'http://up.pigcms.cn/';
		$this->key = C('config.print_server_key');
		$this->topdomain = C('config.print_server_topdomain');
		if(!$this->topdomain){
			$this->topdomain = $this->getTopDomain();
		}
	}

	/*
	 *标签打印机打印
	*/
	public function LabelPrint($usePrinter, $content = '')
    {
        fdump_api(func_get_args(),'orderPrint/LabelPrint',true);
        if (empty($content)) return false;
        
		
		if($usePrinter['print_type'] == 1){
			$data['print_type'] = 'feie_label';
			$data['feie_user'] = $usePrinter['feie_user'];
			$data['feie_ukey'] = $usePrinter['feie_ukey'];
			$data['mcode'] = $usePrinter['mcode'];
			$data['img'] = $usePrinter['img'];
			$data['content'] = $content;
			$this->sendMsgToPrint('feie_label',$data,$usePrinter['count']);
			return true;
		}
    }
    public function newPrint($usePrinter, $content = '', $type=0)
    {
		fdump_api(func_get_args(),'orderPrint/newPrint',true);
        if (empty($content)) return false;
        if ($type==0 && $usePrinter['is_big'] == 1) {
            $content = '<FH><FW>' . $content . '</FW></FH>';
        } elseif ($usePrinter['is_big'] == 2) {
            $content = '<FH2><FW2>' . str_replace(['<FH2>','<FW2>','</FW2>','</FH2>'], '', $content) . '</FW2></FH2>';
        }
		
		// 执行打印推送的钩子
		$hook_result = hook::hook_exec('plan.store_print_before',['print'=>$usePrinter,'msg'=>$content]);
		
        if ($usePrinter['mp']) {
            $data = array('content' => $content, 'machine_code' => $usePrinter['mcode'], 'machine_key' => $usePrinter['mkey'], 'own_print_type'=>$usePrinter['print_type']);

            $data['is_o2o'] = 1;

			
			if($usePrinter['print_type'] == 4){
				$data['print_type'] = 'feie';
				$data['feie_user'] = $usePrinter['feie_user'];
				$data['feie_ukey'] = $usePrinter['feie_ukey'];
				$data['language'] = $usePrinter['language'];
				$this->sendMsgToPrint('feie',$data,$usePrinter['count']);
				return true;
			}else if($usePrinter['print_type'] == 5){
				$data['print_type'] = '365yun';
			}
			

            $url = $this->serverUrl . 'server.php?m=server&c=orderPrint&a=printit&size='.($usePrinter['paper'] ? '80' : '58').'&count=' . $usePrinter['count'] . '&key=' . $this->key . '&domain=' . $this->topdomain;
            $rt = $this->api_notice_increment($url, $data);
        } elseif ($usePrinter['username']) {
            $data = array('content' => '|5' . $content, 'machine_code' => $usePrinter['mcode'], 'machine_key' => $usePrinter['mkey'],'own_print_type' => $usePrinter['print_type']);
            if ($qr == '') {
                $qrlink = $usePrinter['qrcode'];
            } else {
                $qrlink = $qr;
            }

            $data['is_o2o'] = 1;

			
			if($usePrinter['print_type'] == 4){
				$data['print_type'] = 'feie';
				$data['feie_user'] = $usePrinter['feie_user'];
				$data['feie_ukey'] = $usePrinter['feie_ukey'];
				$data['language'] = $usePrinter['language'];
				$this->sendMsgToPrint('feie',$data,$usePrinter['count']);
				return true;
			}else if($usePrinter['print_type'] == 5){
				$data['print_type'] = '365yun';
			}
			

            $url = $this->serverUrl.'server.php?m=server&c=orderPrint&a=printit&size='.($usePrinter['paper'] ? '80' : '58').'&productid=3&count=' . $usePrinter['count'] . '&mkey=' . $usePrinter['mkey'] . '&mcode=' . $usePrinter['mcode'] . '&name=' . $usePrinter['username'] . '&qr=' . urlencode($qrlink) . '&domain=' . $this->topdomain;
            $rt = $this->api_notice_increment($url, $data);
        } elseif ($usePrinter['trenditiot_appid']){
			//大趋云打印机
			include_once APP_PATH . '../v20/extend/printer/Trenditiot.php';
			//底部增加平台小程序码显示
			if (C('config.wxapp_qrcode')) {
				$content .= '<BR>';
				$content .= '<C><IMG>0</IMG></C>';
			}
			$data = array(
				"sn" => $usePrinter['mcode'],
				"content" => $content,
				"copies" => $usePrinter['count']
			);
			Trenditiot::print($usePrinter['trenditiot_appid'], $usePrinter['trenditiot_appkey'], $data);

			if (C('config.wxapp_qrcode')) {
				//每次打印都同步下图片
				$qrcode = base64_encode(file_get_contents(replace_file_domain(C('config.wxapp_qrcode'))));
				$imageData = [
					"sn" => $usePrinter['mcode'],
					"index" => 0,
					"imageBase64" => $qrcode
				];
				Trenditiot::setImage($usePrinter['trenditiot_appid'], $usePrinter['trenditiot_appkey'], $imageData);
			}
		}else {
            /***WIFI小票打印机****/
            $data = array('content' => $content, 'machine_code' => $usePrinter['mcode'], 'machine_key' => $usePrinter['mkey'], 'own_print_type'=>$usePrinter['print_type']);
			
            $data['is_o2o'] = 1;
			if($usePrinter['print_type'] == 4){
				$data['print_type'] = 'feie';
				$data['feie_user'] = $usePrinter['feie_user'];
				$data['feie_ukey'] = $usePrinter['feie_ukey'];
				$data['language'] = $usePrinter['language'];
				$this->sendMsgToPrint('feie',$data,$usePrinter['count']);
				return true;
			}else if($usePrinter['print_type'] == 5){
				$data['print_type'] = '365yun';
			}
			
            $url = $this->serverUrl . 'server.php?m=server&c=orderPrint&a=printit&size='.($usePrinter['paper'] ? '80' : '58').'&count=' . $usePrinter['count'] . '&key=' . $this->key . '&domain=' . $this->topdomain;
            $rt = $this->api_notice_increment($url, $data);
        }
		
		$fdumpArr = array(
          'req_url'     =>  $_SERVER['REQUEST_URI'],
		  'url'			=>	$url,
		  'data'		=>	$data,
		  'rt'			=>	$rt,
		  'usePrinter'	=>	$usePrinter,
		);
		fdump_sql($fdumpArr,'printlog');
		
		// 执行打印推送的钩子
		$hook_result = hook::hook_exec('plan.store_print_after',['print'=>$usePrinter,'msg'=>$content]);
    }
    
	public function printit($mer_id, $store_id = 0, $content = '', $paid = 0, $print_id = 0)
	{
		if ($print_id) {
			$usePrinter = D('Orderprinter')->where(array('mer_id' => $mer_id, 'store_id' => $store_id, 'pigcms_id' => $print_id))->find();
			$usePrinters = $usePrinter ? array($usePrinter) : '';
		} else {
			$usePrinters = D('Orderprinter')->where(array('mer_id' => $mer_id, 'store_id' => $store_id, 'is_main' => 1))->select();
			if (empty($usePrinters)) {
				$usePrinters = D('Orderprinter')->where(array('mer_id' => $mer_id, 'store_id' => $store_id, 'is_main' => 0))->order('pigcms_id asc')->select();
				$usePrinter = count($usePrinters) > 0 ? $usePrinters[0] : '';
				$usePrinters = $usePrinter ? array($usePrinter) : '';
			}
		}
		if ($usePrinters) {
			foreach ($usePrinters as $rowset) {
				$rowset['paid'] = explode(',', $rowset['paid']);
				if ($paid == -1 || in_array($paid, $rowset['paid'])) {
					if ($rowset['mp']) {
						$data = array('content' => $content, 'machine_code' => $rowset['mcode'], 'machine_key' => $rowset['mkey'], 'own_print_type'=>$rowset['print_type']);
						$url = $this->serverUrl . 'server.php?m=server&c=orderPrint&a=printit&size='.($rowset['paper'] ? '80' : '58').'&count=' . $rowset['count'] . '&key=' . $this->key . '&domain=' . $this->topdomain;
						$rt = $this->api_notice_increment($url, $data);
					} elseif ($rowset['username'])  {
						$data = array('content' => '|5' . $content, 'own_print_type'=>$rowset['print_type']);
						if ($qr == '') {
							$qrlink = $rowset['qrcode'];
						} else {
							$qrlink = $qr;
						}
						$url = $this->serverUrl.'server.php?m=server&c=orderPrint&a=fcprintit&size='.($rowset['paper'] ? '80' : '58').'&productid=3&count=' . $rowset['count'] . '&mkey=' . $rowset['mkey'] . '&mcode=' . $rowset['mcode'] . '&name=' . $rowset['username'] . '&qr=' . urlencode($qrlink) . '&domain=' . $this->topdomain;
						$rt = $this->api_notice_increment($url, $data);
					}else{
						/***WIFI小票打印机****/
					   	$data = array('content' => $content, 'machine_code' => $rowset['mcode'], 'machine_key' => $rowset['mkey'], 'own_print_type'=>$rowset['print_type']);
						$url = $this->serverUrl . 'server.php?m=server&c=orderPrint&a=printit&size='.($rowset['paper'] ? '80' : '58').'&count=' . $rowset['count'] . '&key=' . $this->key . '&domain=' . $this->topdomain;
						$rt = $this->api_notice_increment($url,$data);
					}
				}
			}
			
		}
	}
	function get_own_printer($mkey, $app_version = 0)
	{
		if(C('config.print_server_own')){
			$record_db = M('Orderprint_list');
			$condition = ['mkey' => $mkey, 'print_time' => 0, 'add_time' => ['egt', time() - 600]];
			$print = $record_db->where($condition)->order('`print_id` ASC')->find();
			if($print){
				$record_db->data(['print_time'=>time()])->where(['print_id'=>$print['print_id']])->save();

				$printArr = explode(PHP_EOL,$print['content']);
				if(IS_WIN){
					$printArr = explode("\n",$print['content']);
				}else{
					$printArr = explode(PHP_EOL,$print['content']);
				}
				
				if(empty($printArr[0])){
					unset($printArr[0]);
				}
				$printArr = array_values($printArr);
				if(empty($app_version) || $app_version < 1400){
					if($printArr[0] == '【尺寸80mm】' || $printArr[0] == '【尺寸58mm】'){
						unset($printArr[0]);
						$printArr = array_values($printArr);
					}
				}
				if(empty($app_version) || $app_version < 1400){
					if($printArr[0] == '【小号字】' || $printArr[0] == '【中号字】' || $printArr[0] == '【大号字】'){
						unset($printArr[0]);
						$printArr = array_values($printArr);
					}
				}
				$printArr[] = L_('打印时间') . '：' . date('m-d H:i');
				$return = implode('<br/>',$printArr);	
			}else{
				$return = '';
			}
			return $return;
		}else{
			$url = 'http://up.pigcms.cn/server.php?m=server&c=orderPrint&domain=pigcms.com&a=getcableprint&utf8=1&mkey='.$mkey;
			import('ORG.Net.Http');
			$http = new Http();
			$return = Http::curlGet($url);
			if($return == '-1'){
				$return = '';
			}else if(strpos($return,'<html>') !== false || strpos($return,'</head>') !== false || strpos($return,'502 Bad Gateway') !== false){
				$return = '';
			}else{
				$return_arr = explode('||&&||',$return);			
				foreach($return_arr as &$value){
					$value = trim($value);
				}
				$return = implode('<br/>',$return_arr);
			}
			return $return;
		}
	}
	function api_notice_increment($url, $data)
	{
		//自有存储，直接存数据库
		if(C('config.print_server_own') && ($data['own_print_type'] == 3 || $data['own_print_type'] == 6)){
			$content = $data['content'];
			$machine_code = $data['machine_code'];
			$machine_key = $data['machine_key'];
			
			$url_param = parse_url($url);
			$get_param = convertUrlQuery($url_param['query']);
			//大中小号字
            if($machine_code == '888888' || ($machine_code == '600002' && stripos($content,'print_data')===false)){
				if(stripos($content,'<FH2>') === 0 || stripos($content,'<FW2>') === 0){
					$content = '【大号字】'.PHP_EOL.$content;
				}else if(stripos($content,'<FH>') === 0 || stripos($content,'<FW>') === 0){
					$content = '【中号字】'.PHP_EOL.$content;
				}
				
				//打印机尺寸
				if($get_param['size'] == '80'){
					$content = '【尺寸80mm】'.PHP_EOL.$content;
				}
			}

			$match_arr = array('<FS>','</FS>','<FS2>','</FS2>','<FH>','</FH>','<FH2>','</FH2>','<FW>','</FW>','<FW2>','</FW2>','<FB>','</FB>','<center>','</center>','<right>','</right>');			$content = str_replace($match_arr,'',$content);
			$content = str_replace('麥','麦',$content);
			$content = str_replace('錄','录',$content);
			$content = str_replace('頂','顶',$content);
			$content = str_replace('記','记',$content);
			
			$content = $this->removeEmoji($content);
		
			$record_db = M('Orderprint_list');
			$count = intval($get_param['count']);
			
			for($i=0;$i<$count;$i++){
				$record_db->data(['mcode'=>$machine_code,'mkey'=>$machine_key,'content'=>$content,'add_time'=>time()])->add();
			}
		}else{
			$ch = curl_init();
			$header = "Accept-Charset: utf-8";
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$tmpInfo = curl_exec($ch);
			$errorno=curl_errno($ch);
			
			fdump($url, 'api/log/' . date('Ymd') . '/printer', true);
			fdump($data, 'api/log/' . date('Ymd') . '/printer', true);
			fdump($tmpInfo, 'api/log/' . date('Ymd') . '/printer', true);
			
			if ($errorno) {
				return $errorno;
			} else {
				return $tmpInfo;
			}
		}
	}
	
	public function removeEmoji($text){
		$clean_text = "";
		
		// Match Unified Word
		//康熙部首
		$regexUnifiedWord = '/[\x{2F00}-\x{2FDF}]/u';
		$clean_text = preg_replace($regexUnifiedWord, '[特殊字符]', $text);
		
		// Match Emoticons emoji表情
		$regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
		$clean_text = preg_replace($regexEmoticons, '[表情]', $clean_text);
		
		// Match Miscellaneous Symbols and Pictographs 杂项符号、象形文字
		$regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
		$clean_text = preg_replace($regexSymbols, '[符号]', $clean_text);
		
		// Match Transport And Map Symbols  传输符号、地图符号
		$regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
		$clean_text = preg_replace($regexTransport, '[符号]', $clean_text);
		
		// Match Miscellaneous Symbols 杂项符号
		$regexMisc = '/[\x{2600}-\x{26FF}]/u';
		$clean_text = preg_replace($regexMisc, '', $clean_text);
		
		// Match Dingbats 装饰符
		$regexDingbats = '/[\x{2700}-\x{27BF}]/u';
		$clean_text = preg_replace($regexDingbats, '', $clean_text);
		
		return $clean_text;
	}
	
	function getTopDomain()
	{
		$host = $_SERVER['HTTP_HOST'];
		$host = strtolower($host);
		if (strpos($host,'/') !== false) {
			$parse = @parse_url($host);
			$host = $parse['host'];
		}
		$topleveldomaindb = array('com','edu','gov','int','mil','net','org','biz','info','pro','name','museum','coop','aero','xxx','idv','mobi','cc','me');
		$str = '';
		foreach ($topleveldomaindb as $v) {
			$str .= ($str ? '|' : '') . $v;
		}
		$matchstr = "[^\.]+\.(?:(".$str.")|\w{2}|((".$str.")\.\w{2}))$";
		if (preg_match("/".$matchstr."/ies", $host, $matchs)) {
			$domain = $matchs['0'];
		} else {
			$domain = $host;
		}
		return $domain;
	}

	/*
	 * 推送打印不走小猪服务器
	 * date 2020-05-08 14:59
	*/
	function sendMsgToPrint($type , $data , $count = 1)
	{
		switch ($type) {
			case 'feie':
				$this->sendMsgToFeie($data,$count);
				break;
			case 'feie_label':
				$this->sendMsgToFeieLabel($data,$count);
				break;
		}
	}

	/*
	 * 推送给飞鹅打印机
	*/
	public function sendMsgToFeie($array,$count = 1){
        $time = time();
        $url = 'http://api.feieyun.cn/Api/Open/';

        $match_arr = array('<FS>','</FS>','<FS2>','</FS2>','<FH>','</FH>','<FW>','</FW>','<FB>','</FB>','<center>','</center>'); 
        file_put_contents('./print_log/feie.txt',$array['content'].PHP_EOL.PHP_EOL);
        $array['content'] = str_replace($match_arr,'',$array['content']);
        $array['content'] = str_replace('<FH2>','<L>',$array['content']);
        $array['content'] = str_replace('</FH2>','</L>',$array['content']);
        $array['content'] = str_replace('<FW2>','<W>',$array['content']);
        $array['content'] = str_replace('</FW2>','</W>',$array['content']);

        $data_arr = array(
            'user'                 => $array['feie_user'],
            'stime'                => $time,
            'sig'                => sha1($array['feie_user'].$array['feie_ukey'].$time),
            'apiname'        => 'Open_printMsg',
            'sn'                => $array['machine_code'],
            'content'        => $array['content'],
        	'times'                => $count        //打印次数
        );
        
        if($array['language']){
                $data_arr['language'] = $array['language'];
        }
        
        $data = $this->buildQueryString_feie($data_arr);
        $rt = $this->https_request($url,$data);
        // $rt_arr = json_decode($rt,true);
        // echo $rt_arr['ret'];        //正常情况下返回值是 0 ，由于现阶段只对接了O2O，然而O2O不根据返回值判断，所以直接返回。
        
        file_put_contents('./print_log/feie_rt.txt',$array['content'] . '-------' . $data.PHP_EOL.PHP_EOL.$rt,FILE_APPEND);
    }

    /*
	 * 推送给飞鹅标签打印机
	*/
	public function sendMsgToFeieLabel($array,$count = 1){
        $time = time();
        $url = 'http://api.feieyun.cn/Api/Open/';

        $data_arr = array(
            'user'                 => $array['feie_user'],
            'stime'                => $time,
            'sig'                => sha1($array['feie_user'].$array['feie_ukey'].$time),
            'apiname'        => 'Open_printLabelMsg',
            'sn'                => $array['mcode'],
            'content'        => $array['content'],
        	'times'                => $count        //打印次数
        );
        if($array['img']){
			$cfile = curl_file_create($array['img']['url'],'image/jpeg','img');
        	$data_arr['img'] = $cfile;
        	$header = 'Content-Type: multipart/form-data';
        	$rt = $this->https_request($url,$data_arr,$header);
        }else{
        	$data = $this->buildQueryString_feie($data_arr);
        	$rt = $this->https_request($url,$data);
        }
    }

    /* 飞鹅打印机对接的拼接字符 */
    function buildQueryString_feie($data) { 
        $querystring = '';
        if (is_array($data)) {
                foreach ($data as $key => $val) {
                        if (is_array($val)) {
                                foreach ($val as $val2) {
                                        $querystring .= urlencode($key).'='.urlencode($val2).'&';
                                }
                        } else {
                                $querystring .= urlencode($key).'='.urlencode($val).'&';
                        }
                }
                $querystring = substr($querystring, 0, -1); // Eliminate unnecessary &
        } else {
            $querystring = $data;
        }
        return $querystring;
    }

    protected function https_request($url, $data = null, $header = ''){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if($header){
    		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}
