<?php

/**
 * packapp应用上传公共接口
 */
class UploadAction extends BaseAction
{
    public function image()
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
            UPLOAD_ERR_FORM_SIZE => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败'
        ];
        if ($_FILES['imgFile']['error'] === UPLOAD_ERR_OK) {
            $image = D('Image')->handle(0, 'im', 2, array('size' => 10), false);
            if ($image['error']) {
                $this->returnCode(1001, [], $image['message']);
            } else {
                $imageUrl = replace_file_domain($image['url']['imgFile']);
                $this->returnCode(0, ['url' => $imageUrl]);
            }
        } else {
            $this->returnCode(1001, [], isset($errors[$_FILES['imgFile']['error']]) ? $errors[$_FILES['imgFile']['error']] : '上传失败');
        }
    }

    /**
     * 根据微信media_id获取文件
     *
     * @return void
     * @date: 2021/08/25
     */
    public function getWxAudio()
    {
        $pic_type = $_POST['picType'] ?? 'audio';
        $uid = sprintf("%09d", $this->user_session['uid']);
        $rand_num = substr($uid, 0, 3) . '/' . substr($uid, 3, 3) . '/' . substr($uid, 6, 3);
        $up_dir = './upload/' . $pic_type . '/' . $rand_num . '/';
        if (!file_exists($up_dir)) {
            mkdir($up_dir, 0755, true);
        }

        $up_name = $up_dir . uniqid();
        //微信授权获得access_token
        $access_token_array = D('Access_token_expires')->get_access_token();
        if ($access_token_array['errcode']) {
            $this->returnCodeError('获取access_token发生错误：错误代码' . $access_token_array['errcode'] . ',微信返回错误信息：' . $access_token_array['errmsg']);
        }
        $access_token = $access_token_array['access_token'];
        import('ORG.Net.Http');
        $http = new Http();
        $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=" . $access_token . "&media_id=" . $_POST['serverId'];
        $http->curlDownload($url, $up_name . '.amr');
        if (filesize($up_name . '.amr') <= 0) {
            file_put_contents($up_name . '.amr', file_get_contents($url));
        }

        if(strtoupper(substr(PHP_OS,0,3)) === 'WIN'){
            if(!file_exists(WEB_PATH . 'conf/swoole-loader/ffmpeg.exe')){
                $this->returnCodeError('conf/swoole-loader/ffmpeg.exe 文件不存在');
            }
            $cmdPrefix = WEB_PATH . 'conf/swoole-loader/ffmpeg.exe';
        }else{
            if(!file_exists('/home/ffmpeg/bin/ffmpeg')){
                $this->returnCodeError('/home/ffmpeg/bin/ffmpeg 文件未安装');
            }
            $cmdPrefix = '/home/ffmpeg/bin/ffmpeg';
        }


        $str = $cmdPrefix . " -i " . $up_name . '.amr ' .  $up_name . '.mp3';
        exec($str, $out);

        $full_name = $up_name . '.mp3';
        //判断是否需要上传至云存储
        $file_handle = new file_handle();
        $file_handle->upload($full_name);
        $this->returnCodeOk(['url' => replace_file_domain($full_name), 'title' => ltrim($full_name, '.')]);
    }

    public function upload()
    {
        $files = $_FILES['file'];
        $path = "./upload/000/000/000";
        $imagesExt = ['jpg', 'png', 'jpeg', 'gif', 'mp4','mov'];
        // 判断错误号
        if (@$files['error'] == 00) {
            // 判断文件类型
            $ext = strtolower(pathinfo(@$files['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $imagesExt)) {
                $this->returnCode(1001,[],"非法文件类型");
            }

            // 判断是否存在上传到的目录
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            // 生成唯一的文件名
            $fileName = md5(uniqid(microtime(true), true)) . '.' . $ext;

            // 将文件名拼接到指定的目录下
            $destName = $path . "/" . $fileName;

            // 进行文件移动
            if (!move_uploaded_file($files['tmp_name'], $destName)) {
                $this->returnCode(1001,[],"上传失败!");
            }
            $first_img_name = md5($destName);
            //缩略图保存路径
            $out_file = "./upload/short_video/$first_img_name.jpg";
            //要执行的 cmd 命令
            $cmd = "ffmpeg -i " . $destName . " -y -f image2 -ss 2 -t 0.001 -s 280x240 " . $out_file;
            $cmd = iconv('UTF-8', 'GB2312', $cmd);
            //执行命令
            $rtn = exec($cmd . ' 2>&1', $arr, $res);

            $file_handle = new file_handle();
            $file_handle->upload($destName);
            $file_handle->upload($out_file);

            $this->returnCode(0,['url'=>replace_file_domain($destName),'thunm'=>replace_file_domain($out_file)]);
        } else {
            // 根据错误号返回提示信息
            $errors = [
                UPLOAD_ERR_INI_SIZE => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
                UPLOAD_ERR_FORM_SIZE => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
                UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
                UPLOAD_ERR_NO_FILE => '没有文件被上传',
                UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
                UPLOAD_ERR_CANT_WRITE => '文件写入失败'
            ];
            $this->returnCode(1001, [], isset($errors[$_FILES['file']['error']]) ? $errors[$_FILES['file']['error']] : '上传失败');
        }

    }

}