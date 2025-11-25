<?php

/**
 * 文件上传
 */
class UploadVideo
{
    protected $tem_path='';
    protected $formal_path='';

    public function __construct(){
        $this->tem_path='./upload/video/'.date('Ymd').'/tem';
        $this->formal_path= './upload/video/'.date('Ymd').'/formal';

    }

    /**
     * 视频上传接口, 前端的  server 填写此接口的地址即可。
     */
    public function video(){
        /**
         * 接收参数
         */
        $post['type'] = $_POST['type'];
        $post['size'] = $_POST['size'];
        $post['md5'] = $_POST['md5'];
        $post['chunks'] = $_POST['chunks'];
        $post['chunk'] = $_POST['chunk'];
        $data = array();
        //前台 file -> input框中的 name。 $mark[0]即后台 $_FILE数组的最外层索引
        $mark = array_keys($_FILES);
        // 上传文件的类型
        $fileType = $this->getType($post['type']);
        // 允许的上传类型, 则继续 //允许的上传视频类型
        if(!in_array($fileType, array('mp4', 'wma', 'avi', 'rm', 'rmvb', 'flv', 'mpg', 'mov', 'mkv'))) {
            return ['error'=>false,'data'=>'不支持的文件类型'];
        }
        // 根据上下文和文件MD5值，获取一个临时的存储路径，用于存放chunks, 7天清理一次
        $tmpPath = $this -> getTmpPath($post['md5']);

        // 实例化文件上传类，并初始化, 主要用于文件切片的暂时存储， 当所有文件切片上传后进行合并以及删除所有的文件切片
        $upload = new Chunk($mark[0], $tmpPath);
        // 上传并接收分片文件
        if(!$post['chunks']){
            $post['path_']=$this->formal_path;
        }
        $data['video_chunk_url'] = $upload -> uploadChunkFile($post);

        // 判断是否每个分片都上传成功
        if(is_array($data['video_chunk_url'])) {
            //如果是数组，则说明是错误， 直接返回错误信息
            return ['error'=>false,'data'=>$data['video_chunk_url']['reason']];
        }else if(!$data['video_chunk_url']) {
            return ['error'=>false,'data'=>'文件'.$post['chunk'].'上传失败'];
        }

        // 判断是否最后一个分片， 如果是， 则合并分片
        if($post['chunks'] && $post['chunk'] == $post['chunks']-1){
            // 根据上下文不同获取文件真正存储的路径
            $path = $this ->getVideoPath();
            // 合并分片文件，存储到真正的存储地址， 并删除无用的分片文件。
            $data['video_url'] = $upload -> mergerChunk($post, $tmpPath, $path);
            if(!$data['video_url'])
            {
                return ['error'=>false,'data'=>'合并文件分片时出错'];
            }
            return ['error'=>true,'data'=>$data['video_url']];
        }
        else if(!$post['chunks'])
        {
            return ['error'=>true,'data'=>$data['video_chunk_url']];
        }
        else{
            return ['error'=>false,'data'=>''];
        }
    }

    /**
     * 从$_FILE中的type中获取文件类型;   比如由 image/jpeg 得到 jpeg
     * @param $type
     * @return bool|string
     */
    private function getType($type){
        return substr($type,strpos($type,'/') + 1);
    }


    /**
     * 根据不同上下文选择不同的存储路径
     */
    private function getVideoPath(){
        $upload_dir = $this->formal_path;
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
            @chmod($upload_dir,0777);
        }
        return $upload_dir;
    }

    /**
     * 根据不同上下文生成临时存放chunks的目录
     * @param $post
     * @return string
     */
    private function getTmpPath($md5){
        $upload_dir = $this->tem_path;
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
            @chmod($upload_dir,0777);
        }
        return $upload_dir.'/'.$md5;
    }

    public function getThumb($path,$with,$height,$_path,$size,$type){
        $jpgResize = new lib_resizeimage($path, $with, $height, false, $_path.'_'.$size.'_'.$with.'_'.$height.'.'.$type);
        return $_path.'_'.$size.'_'.$with.'_'.$height.'.'.$type;
    }

}


/**
 * @todo 文件上传类
 * @version	v1.0.0
 */
class Chunk
{
    protected $fileName;
    protected $maxSize;
    protected $allowMime;
    protected $allowExt;
    protected $uploadPath;
    protected $imgFlag;
    protected $videoFlag;
    protected $fileInfo;
    protected $error;
    protected $ext;
    protected $destination;
    protected $uniName;
    protected $file_handle;

    /**
     * lib_upload constructor.
     * @param string $fileName
     * @param string $tmpPath
     * @param bool $videoFlag
     * @param int $maxSize
     * @param array $allowExt
     * @param array $allowMime
     */
    public function __construct($fileName='file',$tmpPath='resources//chunk',$videoFlag=true,$maxSize=5242880,$allowExt=array('mp4','mpeg','webm'),$allowMime=array('application/octet-stream', 'video/mp4','video/mpeg','video/webm')){
        $this->fileName=$fileName;          // 文件数组的名称
        $this->maxSize=$maxSize;            // 最大文件的尺寸
        $this->allowMime=$allowMime;        // 允许的mime类型
        $this->allowExt=$allowExt;          // 允许的后缀
        $this->uploadPath=$tmpPath;         // 上传路径（临时）
        $this->videoFlag=$videoFlag;            // 某个标识
        $this->fileInfo=$_FILES[$this->fileName]; // 文件信息

        $this->file_handle = new file_handle();

    }
    /**
     * 检测上传文件是否出错
     * @return boolean
     */
    protected function checkError(){
        if(!is_null($this->fileInfo)){
            if($this->fileInfo['error']>0){
                switch($this->fileInfo['error']){
                    case 1:
                        $this->error='超过了PHP配置文件中upload_max_filesize选项的值';
                        break;
                    case 2:
                        $this->error='超过了表单中MAX_FILE_SIZE设置的值';
                        break;
                    case 3:
                        $this->error='文件部分被上传';
                        break;
                    case 4:
                        $this->error='没有选择上传文件';
                        break;
                    case 6:
                        $this->error='没有找到临时目录';
                        break;
                    case 7:
                        $this->error='文件不可写';
                        break;
                    case 8:
                        $this->error='由于PHP的扩展程序中断文件上传';
                        break;

                }
                return false;
            }else{
                return true;
            }
        }else{
            $this->error='文件上传出错';
            return false;
        }
    }
    /**
     * 检测上传文件的大小
     * @return boolean
     */
    protected function checkSize(){

        if($this->fileInfo['size']>$this->maxSize){
            $this->error='上传文件过大';
            return false;
        }
        return true;
    }
    /**
     * 检测扩展名
     * @return boolean
     */
    protected function checkExt(){
        $this->ext=strtolower(pathinfo($this->fileInfo['name'],PATHINFO_EXTENSION));
        if(!in_array($this->ext,$this->allowExt)){
            $this->error='不允许的扩展名';
            return false;
        }
        return true;
    }
    /**
     * 检测文件的类型
     * @return boolean
     */
    protected function checkMime(){
        if(!in_array($this->fileInfo['type'],$this->allowMime)){
            $this->error='不允许的文件类型';
            return false;
        }
        return true;
    }


    /**
     * 检测是否是真实图片
     * @return bool
     */
    protected function checkTrueImg(){
        if($this->imgFlag){
            if(!@getimagesize($this->fileInfo['tmp_name'])){
                $this->error='不是真实图片';
                return false;
            }
            return true;
        }
    }
    /**
     * 检测是否通过HTTP POST方式上传上来的
     * @return boolean
     */
    protected function checkHTTPPost(){
        if(!is_uploaded_file($this->fileInfo['tmp_name'])){
            $this->error='文件不是通过HTTP POST方式上传上来的';
            return false;
        }
        return true;
    }
    /**
     *显示错误
     */
    protected function showError(){
        echo('<span style="color:red">'.$this->error.'</span>');
    }
    /**
     * 检测目录不存在则创建
     */
    protected function checkUploadPath(){
        if(!is_dir($this->uploadPath)){
            @mkdir($this->uploadPath,0777,true);
            @chmod($this->uploadPath,0777);
        }
    }

    /**
     * 获取chunk的序号
     * @param $post
     * @return mixed
     */
    protected function getChunkName($post)
    {
        return $post['chunk'];
    }
    /**
     * 产生唯一字符串
     * @return string
     */
    protected function getUniName(){
        return md5(uniqid(microtime(true),true));
    }

    /**
     * 上传文件
     * @param $post
     * @return array|string
     */
    public function uploadChunkFile($post){
        if($this->checkError()&&$this->checkSize()&&$this->checkExt()&&$this->checkMime()&&$this->checkHTTPPost()){
            $this->checkUploadPath();  //检测目录不存在则创建
            if(isset($post['path_']) && !empty($post['path_'])){
                if(!is_dir($post['path_'])){
                    @mkdir($post['path_'],0777,true);
                    @chmod($post['path_'],0777);
                }
                $this->uniName = md5(date('YmdHis')."_".rand(100,999));
                $this->destination=$post['path_'].'/'.$this->uniName.'.'.$this->ext;
            }else{
                $this->uniName=$this->getChunkName($post);  //产生唯一chunk名称
                $this->destination=$this->uploadPath.'/'.$this->uniName.'.'.$this->ext;
            }
            if(@move_uploaded_file($this->fileInfo['tmp_name'], $this->destination)){
                if(isset($post['path_']) && !empty($post['path_'])){
                    //todo 上传到云存储
                    $this->file_handle->upload($this->destination);
                }
                return  $this->destination;
            }else{
                $this->error='文件移动失败';
                return ['error'=>false,'data'=>$this->getError()];
            }

        }else{
            return ['error'=>false,'data'=>$this->getError()];
        }
    }

    /**
     * 合并视频分片
     * @param $post
     * @param $tmpPath
     * @param $path
     * @return string
     */
    public function mergerChunk($post, $tmpPath, $path)
    {
        $video_url = $path.'/'.md5(date('YmdHis')."_".rand(100,999)).'.'.$this->ext;
        if(!file_exists($path)){
            @mkdir($path,0777,true);
        }
        for ($i=0; $i < $post['chunks'];$i++) {
            fdump([$tmpPath.'/'.$i.'.'.$this->ext],'api/log/'.date('Ymd').'/a185/UploadVideo_log',true);
            file_put_contents($video_url, file_get_contents($tmpPath.'/'.$i.'.'.$this->ext), 8);
        }
        //todo 上传到云存储
        $this->file_handle->upload($video_url);

        if($this->delDirAndFile($tmpPath)) {
            return $video_url;
        }else{
            return false;
        }
    }

    public function getError()
    {
        return $this->error;
    }


    /**
     * 循环删除目录和文件函数
     * @param $dirName
     * @return bool
     */
    public function delDirAndFile( $dirName )
    {
        // 打开目录句柄
        if ( $handle = opendir( "$dirName" ) ) {
            // 循环读取目录
            while ( false !== ( $item = readdir( $handle ) ) ) {
                if ( $item != "." && $item != ".." ) {
                    // 判断是否是目录
                    if ( is_dir("$dirName/$item") ) {
                        // 如果是目录，递归调用本函数
                        delDirAndFile("$dirName/$item");
                    } else {
                        // 删除文件
                        if( unlink( "$dirName/$item" ) ) {
//                            echo "成功删除文件： $dirName/$item \n";
                        }
                    }
                }
            }
            // 关闭目录句柄
            closedir($handle);
            // 删除目录
            if(rmdir($dirName)){
                return true;
            }
        }
        return false;
    }
}