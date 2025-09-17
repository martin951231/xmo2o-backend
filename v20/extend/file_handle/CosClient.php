<?php
namespace file_handle;
if (is_file(__DIR__ . '/../COS/cos-php-sdk-v5/vendor/autoload.php')) {
    require_once __DIR__ . '/../COS/cos-php-sdk-v5/vendor/autoload.php';
}


class CosClient{
    public $api;
	public $accessKeyId;
	public $accessKeySecret;
	public $region;
    public $bucket;
    public $message;
	
	//架构函数
	public function __construct($accessKeyId, $accessKeySecret, $region, $bucket){
		//设置 SDK 需要的参数
		$this->accessKeyId   = $accessKeyId;
		$this->accessKeySecret = $accessKeySecret;
        $this->bucket = $bucket;
        $this->region = $region;
		
        // true为开启CNAME。CNAME是指将自定义域名绑定到存储空间上。
        $this->api = new \Qcloud\Cos\Client(
        	[
        		'region' => $this->region,
        		'schema' => 'http',
        		'credentials' => [
        			'secretId' => $this->accessKeyId,
        			'secretKey' => $this->accessKeySecret
        		]
        	]
        );
	}
	//文件上传
	public function upload($filePath){
		try{
			$filePath = str_replace('\\','/',realpath($filePath));
			$key = str_replace(WEB_PATH,'',$filePath);

			$bucket = $this->bucket; //存储桶名称 格式：BucketName-APPID	
		    $file = fopen($filePath, "rb");
		    if ($file) {
		        $result = $this->api->putObject(array(
		            'Bucket' => $bucket,
		            'Key' => $key,
		            'Body' => $file));
		        if (isset($result['Location'])&&!empty($result['Location'])){
		            return $result['Location'];
                }
		   }
		}catch(Exception $e){
			fdump('error====='.$e->getMessage(),'cos_upload_move',true);
			return array('error'=>true,'msg'=>$e->getMessage());
		}
	}
	//文件下载至服务器
	public function download($filePath){
        try{
            $file = str_replace(WEB_PATH,'',$filePath);
            $key = str_replace(WEB_PATH,'',$filePath);

            $file_dir = dirname($file);
            if (request()->server('DOCUMENT_ROOT')) {
                $document_root = request()->server('DOCUMENT_ROOT');
            } else {
                $document_root = root_path();
                $document_root = str_replace('\\','/',$document_root);
                $document_root = str_replace('v20/','',$document_root);
                $filePath = $document_root . $file;
            }
            $dir = $document_root . '/' . $file_dir;
            if(!file_exists($dir)){
                mkdir($dir,0777,true);
            }
            $bucket = $this->bucket; //存储桶名称 格式：BucketName-APPID
            $result = $this->api->getObject(array(
                'Bucket' => $bucket, //格式：BucketName-APPID
                'Key' => $key,
                'SaveAs' => $filePath,
            ));
            return array('error'=>false,'msg'=>'success');
        }catch(Exception $e){
            fdump('error====='.$e->getMessage(),'cos_upload_move',true);
            return array('error'=>true,'msg'=>$e->getMessage());
        }
	}
}
?>