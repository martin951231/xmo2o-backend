<?php

namespace file_handle;

require_once __DIR__ . '/../huaweicloud/Obs/autoload.php';

use Obs\ObsClient;

class ObsFileHandle{
	//SDK API类
    public $api;
	//华为云AccessKeyId：
	public $accessKeyId;
	//华为云AccessKeySecret：
	public $accessKeySecret;
    //Bucket 域名：Bucket 域名，即用于用户访问该文件的域名。在Obs Bucket概览里可以查看。可以使用阿里云默认域名，也可以自行绑定域名。（自行绑定请注意需要上传HTTPS证书）
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
        $this->api = new ObsClient([
           'key' => $accessKeyId,
           'secret' => $accessKeySecret,
           'endpoint' => $endpoint,
           'socket_timeout' => 30,
           'connect_timeout' => 10
        ]);
	}
	//文件上传
	public function upload($filePath){
		try{
			$filePath = str_replace('\\','/',realpath($filePath));
			
			$file = str_replace(WEB_PATH,'', $filePath);

			if (request()->server('DOCUMENT_ROOT')) {
				$document_root = request()->server('DOCUMENT_ROOT');
			} else {
				$document_root = root_path();
				$document_root = str_replace('\\','/',$document_root);
				$document_root = str_replace('v20/','',$document_root);
				$filePath = $document_root . $file;
			}
			$result = $this->api->putObject([
                'Bucket' => $this->bucket,
                'Key' => $file,
                'SourceFile' => $filePath
            ]);
			fdump($file,'obs_upload_move',true);
			if($result['HttpStatusCode'] == 200){
				fdump('ok','obs_upload_move',true);
				return array('error'=>false,'msg'=>'success');
			}
		}catch(Exception $e){
			fdump('error====='.$e->getMessage(),'obs_upload_move',true);
            return array('error'=>true,'msg'=>$e->getMessage());
		}
	}
	//文件是否存在
	public function file_exists($filePath){
		try{
			$filePath = str_replace('\\','/',realpath($filePath));
			
			$file = str_replace(WEB_PATH, '', $filePath);
			$result = $this->api->getObjectMetadata([
			    'Bucket' => $this->bucket,
                'Key' => $file
            ]);
			return true;	//返回的布尔值
		}catch(Exception $e){
			return false;
		}
	}
	//文件下载至服务器
	public function download($filePath){
		try{
			$file = str_replace(WEB_PATH, '', $filePath);
			
			$dir = dirname($file);
			if(!file_exists($dir)){
				mkdir($dir,0777,true);
			}
			$result = $this->api->getObject([
			    'Bucket' => $this->bucket,
                'Key' => $file,
				'SaveAsFile' => $filePath,
            ]);
			return array('error'=>false,'msg'=>'success');
		}catch(Exception $e){
			fdump('error====='.$e->getMessage(),'obs_upload_move',true);
			return array('error'=>true,'msg'=>$e->getMessage());
		}
	}
	//文件删除
	public function unlink($filePath){
		try{
			$file = str_replace(WEB_PATH,'',$filePath);
			$result = $this->api->deleteObject([
			    'Bucket' => $this->bucket,
                'Key' => $file
            ]);
			return array('error'=>false,'msg'=>'success');
		}catch(Exception $e){
			fdump('error====='.$e->getMessage(),'obs_upload_unlink',true);
			return array('error'=>true,'msg'=>$e->getMessage());
		}
	}
}
?>
