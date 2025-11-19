<?php

if (is_file(__DIR__ . '/Qiniu/autoload.php')) {
    require_once __DIR__ . '/Qiniu/autoload.php';
}



// 引入鉴权类
use Qiniu\Auth;
// 引入上传类
use Qiniu\Storage\UploadManager;

class Qiniu_client{
	public $domain;
	public $accessKey;
	public $secretKey;
    public $bucket;
	
	public $auth;
	public $token;
	public $uploadMgr;
	
    public $message;
	
	//架构函数
	public function __construct($domain, $accessKey, $secretKey, $bucket){
		//设置 SDK 需要的参数
		$this->domain   = $domain;
		$this->accessKey   = $accessKey;
		$this->secretKey = $secretKey;
        $this->bucket = $bucket;
		
		// 构建鉴权对象
		$this->auth = new Auth($accessKey, $secretKey);
		
		// 生成上传 Token
		$this->token = $this->auth->uploadToken($bucket);
		
		$this->uploadMgr = new UploadManager();
	}
	//文件上传
	public function upload($filePath){
		try{
			$filePath = str_replace('\\','/',realpath($filePath));
			$file = str_replace(WEB_PATH,'',$filePath);
			
			// echo $file;die;
			
			list($ret, $err) = $this->uploadMgr->putFile($this->token, $file, $file);
			
			if($err !== null){
				return array('error'=>true,'msg'=>$err->message());
			}else{
				return array('error'=>false,'msg'=>'success');
			}
		}catch(Exception $e){
			return array('error'=>true,'msg'=>$e->getMessage());
		}
	}
	//文件是否存在
	public function file_exists($filePath){
		try{
			$filePath = str_replace('\\','/',realpath($filePath));
			$file = str_replace(WEB_PATH,'',$filePath);
			
			$result = file_get_contents("http://" . $this->domain . "/" . $file);
			return $result ? true : false;	//返回的布尔值
		}catch(Exception $e){
			return false;
		}
	}
	//文件下载至服务器
	public function download($filePath, $imageslim=false){
		try{
			$filePath = str_replace('\\','/',realpath($filePath));
			$file = str_replace(WEB_PATH,'',$filePath);
			
			$dir = dirname($file);
			if(!file_exists($dir)){
				mkdir($dir,0777,true);
			}
			
			$return = $this->curlDownload("http://" . $this->domain . "/" . $file . ($imageslim ? "?imageslim" : ''), $file);
			
			return array('error'=>false,'msg'=>'success');
		}catch(Exception $e){
			return array('error'=>true,'msg'=>$e->getMessage());
		}
	}
	//文件删除
	public function unlink($filePath){
		try{
			$filePath = str_replace('\\','/',realpath($filePath));
			$file = str_replace(WEB_PATH,'',$filePath);
			
			$config = new \Qiniu\Config();
			$bucketManager = new \Qiniu\Storage\BucketManager($this->auth, $config);
			
			$error = $bucketManager->delete($this->bucket, $file);
			
			return array('error'=>false,'msg'=>'success');
		}catch(Exception $e){
			return array('error'=>true,'msg'=>$e->getMessage());
		}
	}
	
	//单独写个下载方法，对状态码不是200的过滤掉。避免产生破图
	protected function curlDownload($remote, $local) {
		
		$tmp_file = RUNTIME_PATH . uniqid() . mt_rand(0, 100000) . '.tmp';
        $cp = curl_init($remote);
        $fp = fopen($tmp_file, "w");
        curl_setopt($cp, CURLOPT_FILE, $fp);
        curl_setopt($cp, CURLOPT_HEADER, 0);
		curl_setopt($cp, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($cp, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        curl_exec($cp);
		
		$curl_info = curl_getinfo($cp);
		
		curl_close($cp);
        fclose($fp);
		
		fdump(['curlDownload', $remote, $local, $tmp_file, $curl_info], 'imageslim', true);
		
		if($curl_info['http_code'] == 200){
			copy($tmp_file, $local);
			unlink($tmp_file);
			return true;
		}else{
			unlink($tmp_file);
			return false;
		}
    }
}
?>