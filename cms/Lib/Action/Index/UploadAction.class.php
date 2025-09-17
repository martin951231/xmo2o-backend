<?php
/*
 * 图片上传
 *
 */

class UploadAction extends BaseAction{
    public function editor_ajax_upload(){
        ini_set('memory_limit', '512M');
		if(!in_array($_GET['upload_dir'],array('house/notice','GoodsLibrary/content','street/news','shop/content','group/content','merchant/news','merchant/activity','activity/content','system/image','activity/index_pic','appoint/content','system/news','system/intro','bbs/category','system/gift','fc/school','portal/activity','Circle/editor','activity_offline','recruit','merchant/contract','merchant/contract','app/privacy_policy','app/user_agreement'))){


			$this->editor_alert('非法的目录！');
		}

		if($_FILES['imgFile']['error'] != 4){
			$uid = $_SESSION['merchant']['mer_id'] ? $_SESSION['merchant']['mer_id'] : ($_SESSION['system']['mer_id'] ? $_SESSION['system']['mer_id'] : mt_rand(10000,99999));
			$param = array('size' => $this->config['group_pic_size']);
			$image = D('Image')->handle($uid, $_GET['upload_dir'], 0, $param);
			if (!$image['error']) {
				if($_GET['wx'] == '1'){
					//同步到微信
					$data              = [
	                    'mch_id'     => C("config.pay_weixin_sp_mchid"),
	                    'media_hash' => $this->hashMedia('.'.$image['url']['imgFile'])
	                ];
	                $data['sign_type'] = 'HMAC-SHA256';
                	$data['sign']      = $this->makeSign($data, $data['sign_type']);
                	$data['media'] = new CURLFile('.'.$image['url']['imgFile']);
	                $header        = [
	                    "content-type:multipart/form-data",
	                ];
	                $url = "https://api.mch.weixin.qq.com/secapi/mch/uploadmedia";
	                $res           = $this->httpsRequest($url, $data, $header, true);
	                $rt  = $this->disposeReturn($res, ['media_id']);
				}
                $imgUrl = $image['url']['imgFile'] ? $image['url']['imgFile'] : $image['url']['file'];
                $imgUrl = replace_file_domain($imgUrl);
				if($_GET['wx'] == '1'){
                    $imgUrl .= "|||".$rt['media_id'];
				}
				exit(json_encode(array('error' => 0, 'url' => $imgUrl)));
			} else {
				$this->editor_alert($image['msg'] ? $image['msg'] : $image['message']);
			}



// 			if(!is_dir($upload_dir)){
// 				mkdir($upload_dir,0777,true);
// 			}
// 			import('ORG.Net.Upload File');
// 			$upload = new Upload File();
// 			// $upload->maxSize = $this->config['group_pic_size']*1024*1024;
// 			$upload->allowExts = array('jpg','jpeg','png','gif');
// 			$upload->allowTypes = array('image/png','image/jpg','image/jpeg','image/gif');
// 			$upload->savePath = $upload_dir;
// 			$upload->thumb=false;
// 			$upload->saveRule = 'uniqid';
// 			if($upload->upload()){
// 				$uploadList = $upload->getUpload FileInfo();
// 				$url = $upload_dir.$uploadList[0]['savename'];
// 				exit(json_encode(array('error' => 0, 'url' => $url)));
// 			}else{
// 				$this->editor_alert($upload->getErrorMsg());
// 			}
		}else{
			$this->editor_alert('没有选择图片！');
		}
    }

    /**
     * hashMedia 设置上传图片hash值
     * @param $media_addr
     * @param string $type
     * @return string
     */
    protected function hashMedia($media_addr, $type = 'md5')
    {
        return hash_file($type, $media_addr);
    }

	public function ajax_upload_file(){
		if(empty($_GET['name'])){
			exit(json_encode(array('error' => 1,'message' =>'不知道您要上传到哪个配置项，请重试。')));
		}
		$now_config = D('Config')->field('`name`,`type`')->where(array('name'=>$_GET['name']))->find();
		if(empty($now_config)){
			exit(json_encode(array('error' => 1,'message' =>'您正在上传的配置项不存在，请重试。')));
		}
		$tmp_type_arr = explode('&',$now_config['type']);
		$type_arr = array();
		foreach($tmp_type_arr as $k=>$v){
			$tmp_value = explode('=',$v);
			$type_arr[$tmp_value[0]] = $tmp_value[1];
		}
		$allowExts = array_key_exists('file',$type_arr) ? explode(',',$type_arr['file']) : array();
		if($_FILES['imgFile']['error'] != 4){

// 			$image = D('Image')->handle($this->system_session['id'], 'files');
// 			if (!$image['error']) {
// 				exit(json_encode(array('error' => 0,'url' => $image['url']['imgFile'],'title' => $image['title']['imgFile'])));
// 			} else {
// 				exit(json_encode(array('error' => 1,'message' => $image['msg'])));
// 			}

			$img_admin_id = sprintf("%09d", $this->system_session['id']);
			$rand_num = substr($img_admin_id,0,3) . '/' . substr($img_admin_id,3,3) . '/' . substr($img_admin_id,6,3);
			$upload_dir = "./upload/files/{$rand_num}/";
			if(!is_dir($upload_dir)){
				mkdir($upload_dir,0777,true);
			}
			import('ORG.Net.UploadFile');
			$upload = new UploadFile();
			$upload->maxSize = 10*1024*1024;
			$upload->allowExts = $allowExts;
			$upload->savePath = $upload_dir;
			$upload->saveRule = 'uniqid';
			if($upload->upload()){
				$uploadList = $upload->getUploadFileInfo();
				$title = $rand_num.','.$uploadList[0]['savename'];
				exit(json_encode(array('error' => 0,'url' =>'./upload/files/'.$rand_num.'/'.$uploadList[0]['savename'],'title'=>$title)));
			}else{
				exit(json_encode(array('error' => 1,'message' =>$upload->getErrorMsg())));
			}
		}else{
			exit(json_encode(array('error' => 1,'message' =>'没有选择文件')));
		}
	}

	/**
     * MakeSign 生成签名
     * @param $data
     * @param string $signType
     * @return string
     */
    protected function makeSign(array $data, $signType = 'HMAC-SHA256')
    {

        //签名步骤一：按字典序排序参数
        ksort($data);

        $string = $this->toUrlParams($data);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . C("config.pay_weixin_sp_key");

        //签名步骤三：MD5加密或者HMAC-SHA256
        if ($signType == 'md5') {
            //如果签名小于等于32个,则使用md5验证
            $string = md5($string);
        } else {
            //是用sha256校验
            $string = hash_hmac("sha256", $string, C("config.pay_weixin_sp_key"));
        }
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * ToUrlParams     格式化参数格式化成url参数
     * @param $data
     * @return string
     */
    protected function toUrlParams(array $data)
    {
        $buff = "";
        foreach ($data as $k => $v) {
            if ($k != "sign" && $v !== "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * httpsRequest  https请求（支持GET和POST）
     * @param $url
     * @param null $data
     * @return mixed
     */
    protected function httpsRequest($url, $data = '', array $headers = [], $userCert = false, $timeout = 30)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        //设置超时
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        if ($userCert) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);//严格校验
            curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM');
            $sslCertPath = '.'.C('config.pay_weixin_sp_client_cert');
            $sslKeyPath = '.'.C('config.pay_weixin_sp_client_key');
            curl_setopt($curl, CURLOPT_SSLCERT, $sslCertPath);
            curl_setopt($curl, CURLOPT_SSLKEY, $sslKeyPath);
        } else {
            if (substr($url, 0, 5) == 'https') {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名
            }
        }
        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            // curl_setopt($curl, CURLINFO_HEADER_OUT, true); //TRUE 时追踪句柄的请求字符串，从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header
            // $headers = curl_getinfo($curl, CURLINFO_HEADER_OUT); //官方文档描述是“发送请求的字符串”，其实就是请求的header。这个就是直接查看请求header，因为上面允许查看
        }
        curl_setopt($curl, CURLOPT_HEADER, true);    // 是否需要响应 header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output          = curl_exec($curl);
        $header_size     = curl_getinfo($curl, CURLINFO_HEADER_SIZE);    // 获得响应结果里的：头大小
        $response_header = substr($output, 0, $header_size);    // 根据头大小去获取头信息内容
        $http_code       = curl_getinfo($curl, CURLINFO_HTTP_CODE);    // 获取响应状态码
        $response_body   = substr($output, $header_size);
        $error           = curl_error($curl);
        curl_close($curl);

        return [$response_body, $http_code, $response_header, $error];
    }

    /**
     * disposeReturn 处理微信小微商户接口返回值
     * @param $res  httpsRequest 方法调用接口返回的数组
     * @param array $need_fields 需要接口返回的字段（当return_code 、result_code都为SUCCESS时返回的字段的key）
     * @param array $arr 自定义的参数返回出去，例如入驻接口生成的业务编号
     * @return array 微信返回系统级错误不暴露出去，直接返回操作失败，业务级别错误返回具体错误消息
     */
    protected function disposeReturn($res, array $need_fields = [], array $arr = [])
    {
        if ($res[1] == 200) {
            $rt = $this->fromXml($res[0]);
            if ($rt['return_code'] != 'SUCCESS') {
                return [];
            }
            if ($rt['result_code'] != 'SUCCESS') {
                return [];
            }
            if ($this->checkSign($rt)) {
                if (!empty($need_fields)) {
                    $need = [];
                    array_map(function($v) use ($rt, &$need) {
                        $need[$v] = $rt[$v] ?? '';
                    }, $need_fields);
                    return array_merge($need, $arr);
                }
                return $arr;
            }
            return [];
        }
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
    protected function fromXml($xml)
    {
        if (!$xml) {
            throw new WxException(30000);
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xml_parser = xml_parser_create();
        if (!xml_parse($xml_parser, $xml, true)) {
            xml_parser_free($xml_parser);
            throw new WxException(30000);
        } else {
            $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        }
        return $arr;
    }

    /**
     * @param WxPayConfigInterface $config 配置对象
     * 检测签名
     */
    protected function checkSign($data)
    {
        strlen($data['sign']) <= 32 && $sign_type = 'md5';
        if ($this->makeSign($data, $sign_type ?? '') == $data['sign']) {
            return true;
        }
    }
}