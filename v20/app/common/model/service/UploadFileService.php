<?php
/**
 * UploadFileService.php
 * 文件描述
 * Create on 2020/9/10 14:32
 * Created by zhumengqun
 */

namespace app\common\model\service;

use app\common\model\db\ImageDecorate;
use app\mall\model\db\MallImage;
use file_handle\FileHandle;
use think\Exception;

class UploadFileService
{

    /**
     * @param $file  上传的文件对象
     * @param $dir   文件存放的目录
     * @param $isdeal  是否对图片进行裁剪 1=裁剪 0=不裁剪
     * @param $width  裁剪的款 不裁剪时传''
     * @param $height  参见的高 不裁剪时传''
     * @return string  上传后的文件路径
     * @return $name 指定文件名
     * @throws \think\Exception
     */
    public function uploadPictures($file, $upload_dir, $store_id = 0, $is_decorate = 0, $source = '', $source_id = 0,$name='')
    {
        if ($file) {
            try {
                // 验证
                validate(['imgFile' => [
                    'fileSize' => 1024 * 1024 * 10,   //10M
                    'fileExt' => 'jpg,png,jpeg,gif,ico',
                    'fileMime' => 'image/jpeg,image/jpg,image/png,image/gif,image/x-icon', //这个一定要加上，很重要！
                ]])->check(['imgFile' => $file]);
                // 上传图片到本地服务器uniqid
                if($name){
                    $saveName = \think\facade\Filesystem::disk('public_upload')->putFileAs($upload_dir, $file, $name.'.'.$file->extension());
                }else{
                    $saveName = \think\facade\Filesystem::disk('public_upload')->putFile($upload_dir, $file, 'data');
                }
                $saveName = str_replace("\\", '/', $saveName);
                $savepath = '/upload/' . $saveName;
                $params = ['savepath' => '/upload/' . $saveName];
//                $file_handle = new FileHandle();
//                if ($file_handle->check_open_oss()) {
//                    invoke_cms_model('Image/oss_upload_image', $params);
//                }
            } catch (\Exception $e) {
                throw new \think\Exception($e->getMessage());
            }
            if ($is_decorate == 1) {
                //入库
                (new ImageDecorate())->add(['url' => $savepath, 'dateline' => time(), 'source' => $source, 'source_id' => $source_id, 'type' => 'image']);
            } else {
                //入库
                (new MallImage())->addImage(['url' => $savepath, 'dateline' => time(), 'store_id' => $store_id, 'type' => 'image']);
            }
            return $savepath;
        } else {
            return '';
        }

    }

    /**
     * @param $file //文件对象
     * @param $upload_dir //上传存放的目录
     * @return string[]    返回视频缩略图和压缩
     * @throws Exception
     */
    public function uploadVideo($file, $upload_dir, $store_id = 0, $params = [])
    {
        if (!$file) {
            throw new Exception('没有上传的文件');
        }
		
		if(!function_exists('exec')){
            throw new Exception('exec方法不支持');
        }
        
        ## 判断ffmpeg转码有没有安装
        if(strtoupper(substr(PHP_OS,0,3)) === 'WIN'){
            if(!file_exists(WEB_PATH . 'conf/swoole-loader/ffmpeg.exe')){
                throw new Exception('conf/swoole-loader/ffmpeg.exe 文件不存在');
            }
            $cmdPrefix = WEB_PATH . 'conf/swoole-loader/ffmpeg.exe';
        }else{
            if(!file_exists('/home/ffmpeg/bin/ffmpeg')){
                throw new Exception('/home/ffmpeg/bin/ffmpeg 文件未安装');
            }
            $cmdPrefix = '/home/ffmpeg/bin/ffmpeg';
        }
        
        try {
            $fileSize = isset($params['fileSize']) ? $params['fileSize'] : 1024 * 1024 * 10;
            // 验证
            validate(['imgFile' => [
                'fileSize' => $fileSize,   //10M
                'fileExt' => 'mp4,flv,mov,rmvb,avi,wmv',
                'fileMime' => 'video/mp4,video/x-flv,video/quicktime,application/vnd.rn-realmedia-vbr,video/x-msvideo,video/x-ms-wmv,', //这个一定要加上，很重要！
            ]])->check(['imgFile' => $file]);
            // 上传图片到本地服务器
            $saveName = \think\facade\Filesystem::disk('public_upload')->putFile($upload_dir, $file, 'uniqid');
            $savepath = str_replace("\\", '/', $saveName);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }

        $from = request()->server('DOCUMENT_ROOT') . '/upload/' . $savepath;
        invoke_cms_model('Image/oss_upload_video', ['savepath' => $from]);
        $to = $from . '.png';

        //生成缩略图
        $str = $cmdPrefix . " -i " . $from . " -y -f mjpeg -ss 1 -t 0.01 " . $to;
        exec($str, $out);

        //生成视频时间
        $vtime = exec($cmdPrefix . " -i " . $file . " 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//");//总长度

        //按照要求处理时间格式
        $vtime = substr($vtime, 3, 5) ? substr($vtime, 3, 5) : '';

        //生成压缩文件
        $name = substr($savepath, 0, strrpos($savepath, '.'));
        $test = $cmdPrefix . " -i " . $from . " -c:v libx264 -crf 23 -preset veryfast " . request()->server('DOCUMENT_ROOT') . '/upload/' . $name . "_copy.mp4";
        if(strtoupper(substr(PHP_OS,0,3)) !== 'WIN'){
            $test.= ' >> /tmp/tmp.log &';
        }
        exec($test);

        //入库
        $url = '/upload/' . $savepath;
        (new MallImage())->addImage(['url' => $url . ';' . '/upload/' . $savepath . '.png' . ';' . $vtime, 'dateline' => time(), 'store_id' => $store_id, 'type' => 'video']);

        $file_handle = new FileHandle();
        if($file_handle->check_open_oss()) {
            invoke_cms_model('Image/oss_upload_video', ['savepath' => $url]);
            invoke_cms_model('Image/oss_upload_image', ['savepath' => '/upload/' . $savepath . '.png']);
        }
        return ['url' => $url, 'image' => '/upload/' . $savepath . '.png', 'vtime' => $vtime];
    }

    /** 
     * @param $file //文件对象
     * @param $upload_dir //上传存放的目录
     * @return string[]    返回视频缩略图和压缩
     * @throws Exception
     */
    public function uploadVideos($file, $upload_dir, $setting = ['fileSize' => 1024 * 1024 * 10])
    {
        if (!$file) {
            throw new Exception('没有上传的文件');
        }

        ## 判断ffmpeg转码有没有安装
        if(strtoupper(substr(PHP_OS,0,3)) === 'WIN'){
            if(!file_exists(WEB_PATH . 'conf/swoole-loader/ffmpeg.exe')){
                throw new Exception('conf/swoole-loader/ffmpeg.exe 文件不存在');
            }
            $cmdPrefix = WEB_PATH . 'conf/swoole-loader/ffmpeg.exe';
        }else{
            if(!file_exists('/home/ffmpeg/bin/ffmpeg')){
                throw new Exception('/home/ffmpeg/bin/ffmpeg 文件未安装');
            }
            $cmdPrefix = '/home/ffmpeg/bin/ffmpeg';
        }

        if (!is_dir( request()->server('DOCUMENT_ROOT') . '/upload/'.$upload_dir)) {
            mkdir(request()->server('DOCUMENT_ROOT') . '/upload/'.$upload_dir, 0777, true);
        }

        try {
            // 验证
            validate(['imgFile' => [
                'fileSize' => $setting['fileSize'],   //10M
                'fileExt' => 'mp4,flv,mov,rmvb,avi,wmv,quicktime',
                'fileMime' => 'video/mp4,video/x-flv,video/quicktime,application/vnd.rn-realmedia-vbr,video/x-msvideo,video/x-ms-wmv,', //这个一定要加上，很重要！
            ]])->check(['imgFile' => $file]);
            // 上传图片到本地服务器
            $saveName = \think\facade\Filesystem::disk('public_upload')->putFile($upload_dir, $file, 'uniqid',[],true);
            $savepath = str_replace("\\", '/', $saveName);

        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        $from = request()->server('DOCUMENT_ROOT') . '/upload/' . $savepath;
        //invoke_cms_model('Image/oss_upload_video', ['savepath' => $from]);
        $to = request()->server('DOCUMENT_ROOT') . '/upload/' . $savepath . '.png';
        $name = substr($savepath, 0, strrpos($savepath, '.'));

        //$ffmpeg = trim(shell_exec('which ffmpeg')); // or better yet:
        //$ffmpeg2 = trim(shell_exec('type -P ffmpeg'));// 解决which ffmpeg返回为空实际安装了ffmpeg
        $ffmpeg = true;
        

        $vtime = '';
        if (!empty($ffmpeg) || !empty($ffmpeg2))
        {
             //生成缩略图
            $str = "ffmpeg -i " . $from . " -y -f mjpeg -ss 1 -t 0.01 -s 740x500 " . $to;
            exec($str, $out);
            //生成视频时间
            $vtime = exec("ffmpeg -i " . $file . " 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//");//总长度
            //按照要求处理时间格式
            $vtime = substr($vtime, 3, 5) ? substr($vtime, 3, 5) : '';

            //生成压缩文件
            //$test = "ffmpeg -i " . request()->server('DOCUMENT_ROOT') . '/upload/' . $savepath . " -c:v libx264 -crf 23 -preset veryfast " . request()->server('DOCUMENT_ROOT') . '/upload/' . $name . "_copy.mp4 >> /tmp/tmp.log &";
            //exec($test);
        }


        //入库
        $url = '/upload/' . $savepath;
        $file_handle = new FileHandle();
        if($file_handle->check_open_oss()) {
            $params1 = ['savepath' => $url];
            invoke_cms_model('Image/oss_upload_video', $params1);
            $params2 = ['savepath' => '/upload/' . $savepath . '.png'];
            invoke_cms_model('Image/oss_upload_image', $params2);
        }
        return ['video_url' => $url, 'video_image' => '/upload/' . $savepath . '.png', 'vtime' => $vtime];
    }
	
	/**
	 * 文件上传 
	 * @param       $file 上传的文件对象
	 * @param       $upload_dir 文件存放的目录
	 * @param array $valdate 上次文件的大小、格式验证
	 *
	 * @return string 上传后的文件路径
	 * @throws Exception
	 */
    public function uploadFile($file, $upload_dir,$valdate = ['fileSize' => 1024 * 1024 * 10,'fileExt' => 'pem,mp3,xls,xlsx,mp4,m4a,pfx,cer'])
    {
        if ($file) {
            try {
                // 验证

                validate(['file' => $valdate])->check(['file' => $file]);

                // 上传图片到本地服务器

                $saveName = \think\facade\Filesystem::disk('public_upload')->putFileAs($upload_dir, $file, $_FILES['file']['name']);

                $saveName = str_replace("\\", '/', $saveName);
                $savepath = '/upload/' . $saveName;
            } catch (\Exception $e) {
                throw new \think\Exception($e->getMessage());
            }

            return $savepath;
        } else {
            throw new \think\Exception('上传文件不能为空');
            return '';
        }

    }

    /**
     * @param $store_id
     * @return mixed
     * @throws \think\Exception
     * 获取已上传的图片
     */
    public function getUploadImages($type, $dir, $store_id = '')
    {
        if (empty($type)) {
            throw new \think\Exception('type参数缺失');
        }
        if (empty($dir)) {
            throw new \think\Exception('dir参数缺失');
        }
        $where = [['type', '=', $type], ['url', 'like', '%' . $dir . '%']];
        if (!empty($store_id)) {
            $where = [['store_id', '=', $store_id], ['type', '=', $type], ['url', 'like', '%' . $dir . '%']];
        }
        $arr = (new MallImage())->getImageByStoreId($where);
        $list = array();
        if (!empty($arr)) {
            foreach ($arr as $val) {
                if ($type == 'video') {
                    $video = explode(';', $val['url']);
                    $video_addr="";
                    if(strpos($video[1],".png") !== false){
                        $video_addr=str_replace('.png','',$video[1]);
                    }
                    $list[] = ['id'=>$val['id'],'url' => replace_file_domain($video_addr), 'image' => replace_file_domain($video[1]), 'vtime' => isset($video[2]) ?? ''];
                } else {
                    $list[] = replace_file_domain($val['url']);
                }
            }
        }
        return $list;
    }


    //公租房上传文件
    public function uploadPublicRental($file,$upload_dir,$fileExt='jpg,png,jpeg,doc,docx,pdf,xls,xlsx,excel'){
        // 验证
        validate(['file' => [
            'fileSize' => 1024 * 1024 * 50,   //50M
            'fileExt' => $fileExt,
        ]])->check(['file' => $file]);
        $imgName = $file->getOriginalName();
        // 上传到本地服务器
        $savename = \think\facade\Filesystem::disk('public_upload')->putFile($upload_dir, $file);
        if (strpos($savename, "\\") !== false) {
            $savename = str_replace('\\', '/', $savename);
        }
        $imgurl = '/upload/' . $savename;
        $params = ['savepath'=> $imgurl];
        $file_handle = new FileHandle();
        if($file_handle->check_open_oss()) {
            invoke_cms_model('Image/oss_upload_image',$params);
        }
        return ['url'=>$imgurl,'name'=>$imgName,'file'=>$_FILES['file'],'path'=>replace_file_domain($imgurl)];
    }

    /**
     * 通用上传文件方法，限制上传大小可以传参 默认50M 限制文件类型可以传参 默认 图片 word pdf excel
     */
    public function commonUploadFile($file, $upload_dir, $params = [])
    {
        $fileExt = isset($params['fileExt']) && trim($params['fileExt']) ? trim($params['fileExt']) : 'jpg,png,jpeg,doc,docx,pdf,xls,xlsx,excel';
        // 默认限制 50M
        $fileSize = isset($params['fileSize']) && intval($params['fileSize']) ? intval($params['fileSize']) : 52428800;
        // 验证
        validate(['file' => [
            'fileSize' => $fileSize,
            'fileExt' => $fileExt,
        ]])->check(['file' => $file]);
        $imgName = $file->getOriginalName();
        $mimeType = $file->getOriginalMime();
        // 上传到本地服务器
        $savename = \think\facade\Filesystem::disk('public_upload')->putFile($upload_dir, $file);
        if (strpos($savename, "\\") !== false) {
            $savename = str_replace('\\', '/', $savename);
        }
        $imgurl = '/upload/' . $savename;
        $params = ['savepath' => $imgurl];
        $file_handle = new FileHandle();
        if($file_handle->check_open_oss()) {
            invoke_cms_model('Image/oss_upload_image', $params);
        }
        return ['url' => $imgurl, 'name' => $imgName, 'mimeType' => $mimeType, 'file' => $_FILES['file'], 'path' => replace_file_domain($imgurl)];
    }
}