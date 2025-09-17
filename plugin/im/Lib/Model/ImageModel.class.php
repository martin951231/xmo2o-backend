<?php
class ImageModel extends Model
{
    /**
     * @param int $oid 用户ID
     * @param string $path 文件路劲
     * @param int $otype 用户类型 0：系统后台管理员，1：商家后台管理员，2：用户，3：社区后台管理员
     * @param array $param  各种属性 其中size 图片最大尺寸
     * @param bool $ismark  是否打水印
     * @param bool $is_group_check  是否进行社群小程序图片安全检测
     * @return array
     */
	public function handle($oid, $path, $otype = 0, $param = array('size' => 5), $ismark = true, $is_group_check = false)
	{
// 		array('size' => 5, 'path', 'thumbMaxWidth', 'thumbMaxHeight', 'thumb' => true, 'imageClassPath' => 'ORG.Util.Image', 'thumbPrefix' => 'm_,s_', 'thumbRemoveOrigin' => false);

		import("ORG.Net.UploadFile");
		$upload = new UploadFile();
		if($param['size'] === null || (isset($param['size']) && $param['size'] == 0)){
			$param['size'] = 10;
		}
		
		$upload->maxSize = $param['size'] * 1024 * 1024 ;
		$upload->allowExts = array('jpg', 'jpeg', 'png', 'gif', 'mp3', 'ico');
		$upload->allowTypes = array('image/png', 'image/x-png', 'image/jpg', 'image/jpeg', 'image/pjpeg', 'image/gif', 'audio/mp3', 'image/x-icon', 'application/octet-stream');

		if($path == 'video'){
            $upload->allowExts = array('jpg', 'jpeg', 'png', 'gif', 'mp3','mp4', 'ico');
            $upload->allowTypes = array('image/png', 'image/x-png', 'image/jpg', 'image/jpeg', 'image/pjpeg', 'image/gif', 'audio/mp3', 'image/x-icon', 'application/octet-stream','video/mp4',);
        }

		$upload->saveRule = 'uniqid_rand';
		isset($param['thumb']) && $upload->thumb = $param['thumb'];
		isset($param['imageClassPath']) && $upload->imageClassPath = $param['imageClassPath'];
		isset($param['thumbPrefix']) && $upload->thumbPrefix = $param['thumbPrefix'];
		isset($param['thumbMaxWidth']) && $upload->thumbMaxWidth = $param['thumbMaxWidth'];
		isset($param['thumbMaxHeight']) && $upload->thumbMaxHeight = $param['thumbMaxHeight'];
		isset($param['thumbRemoveOrigin']) && $upload->thumbRemoveOrigin = $param['thumbRemoveOrigin'];


		$img_mer_id = sprintf("%09d", $oid);
		if ($path == 'sysgoods') {
		    $rand_num = 'sysgoods_' . substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
		} else {
		    $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
		}
		$upload_dir = "./upload/{$path}/{$rand_num}/";
		
		if(!is_dir($upload_dir)){
			mkdir($upload_dir, 0777, true);
		}

		$upload->savePath = $upload_dir;// 设置附件上传目录

		if (!$upload->upload()) {// 上传错误提示错误信息
			return array('error' => 1, 'message' => $upload->getErrorMsg());
		} else {// 上传成功 获取上传文件信息
			$watermarkfile =  C('config.site_water_mark');//'./upload/watermark/home.png';

			//将oss上的图片现在下来
			$file_handle = new file_handle();
            if($file_handle->check_open_oss()) {
                $file_handle->download($watermarkfile);
				$watermarkfile = $file_handle->get_path($watermarkfile);
            }
			$flag = false;
			if ($ismark && $watermarkfile) {
				$pt = pathinfo($watermarkfile);
				$pu = parse_url($watermarkfile);
				if (isset($pu['path']) && isset($pt['extension'])) {
					$watermarkfile = '.' . $pu['path'];
					$watermarksize = @getimagesize($watermarkfile);
					$watermark = array();
					$watermark['watermarkstatus'] = C('config.site_water_mark_pos');
					$watermark['watermarktype'] = isset($pt['extension']) ? $pt['extension'] : 'png';//'png';
					$watermark['watermarkfile'] = $watermarkfile;
					$watermark['watermarkminwidth'] = $watermarksize[0];
					$watermark['watermarkminheight'] = $watermarksize[1];
					$watermark['watermarkquality'] = 90;
					$watermark['watermarktrans'] = 100;
					$image_water_mark = new image_water_mark();
					$flag = true;
				}
			}
			$images = array();
			$files = $upload->getUploadFileInfo();
			$d_community_file = D('Community_file');
            $site = C('config.site_url');
			
			import('ORG.Util.Image');
			foreach ($files as $file) {
				$file_path = $file['savepath'].$file['savename'];
				
				/*压缩图片*/
				/* if($file['extension'] <> 'gif' && $file['extension'] <> 'GIF'){
					Image::thumb($file_path,$file_path,'',1300,10000);
				} */
				
				//多图上传，并且key相同。例如同一POST KEY上传多张图片
				if(count($files) >= 2 && count(array_keys($files)) != count($files)){	
					$images['url'][$file['key']][] = substr($file['savepath'] . $file['savename'], 1);
				}else{
					$images['url'][$file['key']] = substr($file['savepath'] . $file['savename'], 1);
				}
				
				// 判断是否需要进行图片内容安全检测
                if ($is_group_check) {
                    $url = $site . $images['url'][$file['key']];
                    $check = $d_community_file->imgSecCheck($url);
                    if ($check['errcode'] != 0) {
                        $real_url = "{$_SERVER['DOCUMENT_ROOT']}{$images['url'][$file['key']]}";
                        // 不合规的不安全内容进行本地清除
                        unlink($real_url);
                        return array('error' => 1, 'message' => L_('图片内容含有违法违规内容'));
                    }
                }
				//店铺图片全路径返回
				if($path == 'store' || $path == 'goods' || $path == 'sysgoods'){
					$images['title'][$file['key']] = $images['url'][$file['key']];
				}else{
					$images['title'][$file['key']] = $rand_num . ',' . $file['savename'];
				}
				
				$imageSizeArr = getimagesize($file['savepath'].$file['savename']);
				$str = '';
				if(count($files) >= 2){//多图添加
						$str = substr($file['savepath'] . $file['savename'], 1);
						$pigcms_id = $this->add(array('oid' => $oid, 'otype' => $otype, 'ip' => get_client_ip(), 'dateline' => time(), 'pic' => $str, 'pic_md5' => md5($str),'img_remark'=>$file['name'],'img_width'=>intval($imageSizeArr[0]),'img_height'=>intval($imageSizeArr[1])));
                		$images['pigcms_id'][] = $pigcms_id;
				}else{
					$pigcms_id = $this->add(array('oid' => $oid, 'otype' => $otype, 'ip' => get_client_ip(), 'dateline' => time(), 'pic' => $images['url'][$file['key']], 'pic_md5' => md5($images['url'][$file['key']]),'img_remark'=>$file['name'],'img_width'=>intval($imageSizeArr[0]),'img_height'=>intval($imageSizeArr[1])));
					$images['pigcms_id'] = $pigcms_id;
				}
				if ($flag) {
					$image_water_mark->Watermark($file['savepath'] . $file['savename'], $watermark);
					$file_handle->upload($file['savepath'] . $file['savename']);
					if ($upload->thumbPrefix) {
						$thumbPrefix	=	explode(',', $upload->thumbPrefix);
						foreach ($thumbPrefix as $pre) {
							if(is_file($file['savepath'] . $pre . $file['savename'])){
								$image_water_mark->Watermark($file['savepath'] . $pre . $file['savename'], $watermark);
								$file_handle->upload($file['savepath'] . $pre . $file['savename']);
							}
						}
					}
				}
			}
			$images['error'] = 0;
			return $images;
		}
	}

	/**
	 * @param int $tableid 表中的主键
	 * @param string $path 图片地址
	 * @param string $tablename 表名
	 *
	 * path = '/upload/...'
	 */
	public function update_table_id($path, $tableid, $tableName)
	{
		if ($image = $this->field(true)->where(array('pic_md5' => md5($path)))->find()) {
			$this->where(array('pigcms_id' => $image['pigcms_id']))->save(array('tableid' => $tableid, 'tablename' => $tableName));
		}
	}

    //v20判断是否需要上传至云储存/是否需要瘦身
    public function oss_upload_image($param){
        $file_handle = new file_handle();
        $res = $file_handle->upload('.'.$param,true);
        return $res;
    }
    //v20判断是否需要上传至云储存
    public function oss_upload_video($param){
        $file_handle = new file_handle();
        $res = $file_handle->upload('.'.$param,false);
        return $res;
	}

    public function thumb($url, $width, $height, $resize){
        if(empty($url) || empty($width) || empty($height) ){
            return $url;
        }
        $site_url = C('config.site_url');
        if(strpos($url, $site_url.'/upload/') === 0){
            $filePath = str_replace($site_url.'/upload/','',$url);
            $filePathInfo = pathinfo($filePath);
            $path = '/upload/thumbImg/'.$width.'_'.$height.($resize ? '_'.$resize : '').'/'.$filePath;
            $thumbFile = '.'.$path;
            if (function_exists('exif_read_data') && function_exists('imagecreatefromstring') && function_exists('imagerotate')) {
                // 处理图片旋转问题
                $image = imagecreatefromstring(file_get_contents('./upload/'.$filePath));
                $exif = exif_read_data('./upload/'.$filePath);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 8:
                            $image = imagerotate($image, 90, 0);
                            break;
                        case 3:
                            $image = imagerotate($image, 180, 0);
                            break;
                        case 6:
                            $image = imagerotate($image, -90, 0);
                            break;
                    }
                    imagejpeg($image, './upload/'.$filePath, 100);
                    imagedestroy($image);
                }
            }
            $thumbFilePathInfo =  pathinfo($thumbFile);
            if(file_exists($thumbFile) && filesize($thumbFile) > 300){
                return  $site_url . $path;
            }
            if(!file_exists($thumbFilePathInfo['dirname'].'/') && !mkdir($thumbFilePathInfo['dirname'].'/',0777,true)){
                return $url;
            }
            import('ORG.Util.Image');
            $ImageObj = new Image();
            if($resize == 'fill'){
                $thumb = $ImageObj->thumb2('./upload/'.$filePath,$thumbFile,'',$width,$height);
            }else{
                $thumb = $ImageObj->thumb('./upload/'.$filePath,$thumbFile,'',$width,$height);
            }
            if($thumb){
                return  $site_url . $path;
            }else{
                return $url;
            }
        }else{
            return $url;
        }
    }



    /**
     * Notes: 图片格式转换
     * @param string $image_path 文件路径或url
     * @param string $to_ext 待转格式，支持png,gif,jpeg
     * @param integer $quality jpg图片质量
     * @return string
     * @throws Exception
     * @author: wanzy
     * @date_time: 2020/11/11 11:04
     */
    public function transform_image($image_path, $to_ext = 'jpg', $quality=85)
    {
        if($to_ext == 'jpg'){
            $to_ext = 'jpeg';
        }
        if (! in_array($to_ext, ['png', 'gif', 'jpeg'])) {
            throw new \Exception('unsupport transform image to ' . $to_ext);
        }

        //同步OSS
        import('@.ORG.file_handle');
        $file_handle = new file_handle();
        if($file_handle->check_open_oss()) {
            $file_handle->download($image_path);
        }
        $image_path = $file_handle->get_path($image_path);
        if(strpos($image_path, '/') === 0){
            $image_path = '.' . $image_path;
        }

        $pathinfo = pathinfo($image_path);


        //由于图片后缀可能和图片格式不一样，例如后缀jpg，其实是png。所以一切都需要格式化。
        $ext = '';
        if ($pathinfo['dirname']) {
            if ($to_ext == 'jpeg') {
                $ext = 'jpg';
            } else {
                $ext = $to_ext;
            }
            $save_path = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.' . $ext;
        }else{
            return replace_file_domain($image_path);
        }


        if (function_exists('exif_imagetype')) {
            $img_key = exif_imagetype($image_path);
            if (!in_array($img_key,[2,10])) {
                switch ($img_key) {
                    case 1 :
                        $pathinfo['extension'] = 'gif';
                        $save_path = $pathinfo['dirname'] .'/'.$pathinfo['filename'].'_new.'.$ext;
                        break;
                    case 3:
                        $pathinfo['extension'] = 'png';
                        $save_path = $pathinfo['dirname'] .'/'.$pathinfo['filename'].'_new.'.$ext;
                        break;
                }
            }
        }

        //如果生成文件已存在，直接反馈
        if (file_exists($save_path)) {
            return replace_file_domain($save_path);
        }

        //开始转换
        if (function_exists('exif_imagetype')) {
            switch (exif_imagetype($image_path)) {
                case IMAGETYPE_GIF :
                    $img = imagecreatefromgif($image_path);
                    break;
                case IMAGETYPE_JPEG :
                case IMAGETYPE_JPEG2000:
                    $img = imagecreatefromjpeg($image_path);
                    break;
                case IMAGETYPE_PNG:
                    $img = imagecreatefrompng($image_path);
                    break;
                case 1 :
                    $img = imagecreatefromgif($image_path);
                    break;
                case 2 :
                case 10:
                    $img = imagecreatefromjpeg($image_path);
                    break;
                case 3:
                    $img = imagecreatefrompng($image_path);
                    break;
                default :
                    throw new \Exception($image_path . '：Invalid image type');
            }

            //处理图片旋转问题
            $exif = exif_read_data($image_path);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 8:
                        $img = imagerotate($img, 90, 0);
                        break;
                    case 3:
                        $img = imagerotate($img, 180, 0);
                        break;
                    case 6:
                        $img = imagerotate($img, -90, 0);
                        break;
                    case 5:		//一般不用处理5的，发生特殊情况处理下
                        $img = imagerotate($img, -90, 0);
                        break;
                }
            }

        }  else {
            $type = $pathinfo['extension'];
            $createFun = 'ImageCreateFrom' . ($type == 'jpg' ? 'jpeg' : $type);
            $srcImg = $createFun($image_path);

            $info = $this->getImageInfo($image_path);
            $width = $srcWidth = $info['width'];
            $height = $srcHeight = $info['height'];

            //创建缩略图
            if ($type != 'gif' && function_exists('imagecreatetruecolor')){
                $img = imagecreatetruecolor($width, $height);
            }else{
                $img = imagecreate($width, $height);
            }

            //防止用户上传图片过小，背景会变黑。设置背景颜色
            $bg =imagecolorallocate($img, 255, 255,255);
            imagefill($img, 0, 0, $bg);
            // 复制图片
            if (function_exists("ImageCopyResampled"))
                imagecopyresampled($img, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
            else
                imagecopyresized($img, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
            if ('gif' == $type || 'png' == $type) {
                imagealphablending($img, false);//取消默认的混色模式
                imagesavealpha($img,true);//设定保存完整的 alpha 通道信息
            }
            // 对jpeg图形设置隔行扫描
            if ('jpg' == $type || 'jpeg' == $type)
                imageinterlace($img, true);
        }

        $function = 'image'.$to_ext;
        if($function == 'imagejpeg'){
            $function($img, $save_path, $quality);
        }else{
            $function($img, $save_path);
        }

        $file_handle->upload($save_path);

        return replace_file_domain($save_path);
    }
    public function getImageInfo($img) {
        $imageInfo = getimagesize($img);
        if ($imageInfo !== false) {
            $imageType = strtolower(substr(image_type_to_extension($imageInfo[2]), 1));
            $imageSize = filesize($img);
            $info = array(
                "width" => $imageInfo[0],
                "height" => $imageInfo[1],
                "type" => $imageType,
                "size" => $imageSize,
                "mime" => $imageInfo['mime']
            );
            return $info;
        } else {
            return false;
        }
    }


    public function wx_image_download($url, $path, $file_ext='jpg'){
        $url = urldecode($url);
        $upload_dir = './upload/wx_img/'.$path;
        $image_name = md5($url).'.'.$file_ext;
        if(!is_dir($upload_dir)){

            mkdir($upload_dir, 0777, true);
        }
        $file_handle = new file_handle();
        if($file_handle->file_exists($upload_dir.'/'.$image_name)){
            return file_domain().str_replace('./','/',$upload_dir).'/'.$image_name;
        }else{
            $image_file = file_get_contents($url);
            file_put_contents($upload_dir.'/'.$image_name,$image_file);

            //判断是否需要上传至云存储
            $file_handle = new file_handle();
            $file_handle->upload($upload_dir.'/'.$image_name);
            return file_domain().str_replace('./','/',$upload_dir).'/'.$image_name;
        }
    }
}
