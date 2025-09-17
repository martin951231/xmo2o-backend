<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/12/25 10:36
 */
if (is_file(__DIR__ . '/Qiniu/autoload.php')) {
    require_once __DIR__ . '/Qiniu/autoload.php';
}

// 引入上传类
use Qiniu\Storage\UploadManager;

class d1_qiniu {

    public $token;
    //架构函数
    public function __construct($token){
        // 生成上传 Token
        $this->token = $token;

        $this->uploadMgr = new UploadManager();
    }
    //文件上传
    public function upload($filePath, $key){
        try{
            $filePath = str_replace('\\','/',realpath($filePath));
            $file = str_replace(WEB_PATH,'',$filePath);
            fdump('>>>'.__LINE__,'d1_qiniu',1);
            fdump($filePath,'d1_qiniu',1);
            fdump($file,'d1_qiniu',1);
            list($ret, $err) = $this->uploadMgr->putFile($this->token, $key, $file);
            fdump($this->token,'d1_qiniu',1);
            fdump($ret,'d1_qiniu',1);
            fdump($err,'d1_qiniu',1);
            if($err !== null){
                return array('error'=>true,'msg'=>$err->message());
            }else{
                return array('error'=>false,'msg'=>'success');
            }
        }catch(Exception $e){
            return array('error'=>true,'msg'=>$e->getMessage());
        }
    }
}