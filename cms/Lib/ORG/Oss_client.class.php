<?php

if (is_file(__DIR__ . '/OSS/autoload.php')) {
    require_once __DIR__ . '/OSS/autoload.php';
}

use OSS\OssClient;
use OSS\Core\OssException;
use OSS\Core\OssUtil;

class Oss_client{
	//SDK API类
    public $api;
	//阿里云AccessKeyId：
	public $accessKeyId;
	//阿里云AccessKeySecret：
	public $accessKeySecret;
    //Bucket 域名：Bucket 域名，即用于用户访问该文件的域名。在OSS Bucket概览里可以查看。可以使用阿里云默认域名，也可以自行绑定域名。（自行绑定请注意需要上传HTTPS证书）
    public $endpoint;
    // 运行示例程序所使用的存储空间。示例程序会在这个存储空间中创建一些文件。
    public $bucket;
    //错误信息
    public $message;
	
	//架构函数
	public function __construct($accessKeyId, $accessKeySecret, $endpoint, $bucket){
		//设置 SDK 需要的参数
		$this->accessKeyId   = $accessKeyId;
		$this->accessKeySecret = $accessKeySecret;
        $this->endpoint = $endpoint;
        $this->bucket = $bucket;
		
        // true为开启CNAME。CNAME是指将自定义域名绑定到存储空间上。
        $this->api = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint, false);
	}
	//文件上传
	public function upload($filePath){
		try{
			$filePath = str_replace('\\','/',realpath($filePath));
			
			$file = str_replace(WEB_PATH,'',$filePath);
			$result = $this->api->uploadFile($this->bucket, $file, $file);
			fdump_api($file,'upload/oss_upload_move',true);
			if($result['info']['http_code'] == 200){
				fdump_api('ok','upload/oss_upload_move',true);
				$url = 'https://'.$oss_access_domain_names.'/'.$file;
				return array('error'=>false,'msg'=>'success');
			}
		}catch(Exception $e){
			fdump_api('error====='.$e->getMessage(),'upload/oss_upload_move',true);
			return array('error'=>true,'msg'=>$e->getMessage());
		}
	}
	//文件是否存在
	public function file_exists($filePath){
		try{
			//判断时不能用 realpath ，因为这时文件本地不存在，则会返回空字符串
			$filePath = str_replace('\\', '/', $filePath);
			$filePath = str_replace('./', '', $filePath);
			
			$file = str_replace(WEB_PATH, '', $filePath);
			
			$result = $this->api->doesObjectExist($this->bucket,$file);
			
			return $result;	//返回的布尔值
		}catch(Exception $e){
			return false;
		}
	}
	//文件下载至服务器
	public function download($filePath){
		try{
			$file = str_replace(WEB_PATH,'',$filePath);
			
			$dir = dirname($file);
			if(!file_exists($dir)){
				mkdir($dir,0777,true);
			}
			$result = $this->api->getObject($this->bucket, $file, array('fileDownload'=>$file));
			return array('error'=>false,'msg'=>'success');
		}catch(Exception $e){
			fdump('error====='.$e->getMessage(),'oss_upload_move',true);
			return array('error'=>true,'msg'=>$e->getMessage());
		}
	}
	//文件删除
	public function unlink($filePath){
		try{
			$file = str_replace(WEB_PATH,'',$filePath);
			$result = $this->api->deleteObject($this->bucket, $file);
			return array('error'=>false,'msg'=>'success');
		}catch(Exception $e){
			fdump('error====='.$e->getMessage(),'oss_upload_unlink',true);
			return array('error'=>true,'msg'=>$e->getMessage());
		}
	}
}
?>