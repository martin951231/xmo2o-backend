<?php
namespace file_handle;

require_once __DIR__ . '/../aliyuncs/oss-sdk-php/autoload.php';

use OSS\OssClient;
use OSS\Core\OssException;
use OSS\Core\OssUtil;

class OssFileHandle{
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
            fdump($filePath,'oss_upload_move',true);
            $tmpfilePath=realpath($filePath);
            if(!$tmpfilePath){
                //失败了
                $filePath = str_replace('\\','/',$filePath);
                $filePath = app()->getRootPath() . '.' . $filePath;
                fdump([$filePath,WEB_PATH],'oss_upload_move',true);
                $file = str_replace(WEB_PATH, '', $filePath);
            }else{
                $filePath = str_replace('\\','/',$filePath);
                $file = str_replace(WEB_PATH, '', $filePath);
            }
            fdump([app()->getRootPath(),WEB_PATH,$file,$filePath],'oss_upload_move',true);
			$result = $this->api->uploadFile($this->bucket, $file, $filePath);
			fdump($file,'oss_upload_move',true);
			if($result['info']['http_code'] == 200){
				fdump('ok','oss_upload_move',true);
				return array('error'=>false,'msg'=>'success');
			}
		}catch(Exception $e){
			fdump('error====='.$e->getMessage(),'oss_upload_move',true);
			return array('error'=>true,'msg'=>$e->getMessage());
		}
	}
	//文件是否存在
	public function file_exists($filePath){
		try{
			$filePath = str_replace('\\','/',realpath($filePath));
			
			$file = str_replace(WEB_PATH,'',$filePath);
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

            if (request()->server('DOCUMENT_ROOT')) {
                $document_root = request()->server('DOCUMENT_ROOT');
            } else {
                $document_root = root_path();
                $document_root = str_replace('\\','/',$document_root);
                $document_root = str_replace('v20/','',$document_root);
                $filePath = $document_root . $file;
            }
			$dir = $document_root . '/' . dirname($file);
			if(!file_exists($dir)){
				mkdir($dir,0777,true);
			}
			$result = $this->api->getObject($this->bucket, $file, array('fileDownload'=>$filePath));
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
    // 生成签名URL。
    public function  signUrl($bucket, $object, $timeout = 60, $method = 'POST', $options = NULL) {
        try{
            $result = $this->api->signUrl($bucket, $object, $timeout, $method, $options);
            return array('error'=>false,'msg'=>'success', 'result' => $result);
        }catch(Exception $e){
            fdump('error====='.$e->getMessage(),'oss_sign_url',true);
            return array('error'=>true,'msg'=>$e->getMessage());
        }
    }
    // 使用STS临时授权上传文件。
    public function  putObject($bucket, $object, $content, $options = NULL) {
        try{
            $result = $this->api->putObject($bucket, $object, $content, $options);
            return array('error'=>false,'msg'=>'success', 'result' => $result);
        }catch(Exception $e){
            fdump('error====='.$e->getMessage(),'oss_put_object',true);
            return array('error'=>true,'msg'=>$e->getMessage());
        }
    }
}
?>