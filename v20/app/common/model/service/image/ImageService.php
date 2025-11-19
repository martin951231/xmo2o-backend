<?php
/**
 * 图片处理服务
 * Author: hengtingmei
 * Date Time: 2020/12/26
 */

namespace app\common\model\service\image;
use app\traits\ImageHandleTraits;
use file_handle\FileHandle;
use think\Exception;

class ImageService {
	
	use ImageHandleTraits;
	
    /**
     * 生成海报
     * @param $config array 数据
     * @param $filename string 生成的图片地址 项目完整目录
     * @return array
     */
    public function createPoster($config=array(),$filename=""){
        $imageDefault = array(
            'left'=>0,
            'top'=>0,
            'right'=>0,
            'bottom'=>0,
            'width'=>100,
            'height'=>100,
            'opacity'=>100
        );
        $background = $config['background'];//海报最底层得背景

        //处理背景图片
        $backgroundInfo = getimagesize($background);

        $backgroundFun = 'imagecreatefrom'.image_type_to_extension($backgroundInfo[2], false);
        $background = $backgroundFun($background);
        $backgroundWidth = imagesx($background);  //背景宽度
        $backgroundHeight = imagesy($background);  //背景高度
        $imageRes = imageCreatetruecolor($backgroundWidth,$backgroundHeight);
        $color = imagecolorallocate($imageRes, 0, 0, 0);
        imagefill($imageRes, 0, 0, $color);
        imagecopyresampled($imageRes,$background,0,0,0,0,imagesx($background),imagesy($background),imagesx($background),imagesy($background));

        //处理合成图片
        if(!empty($config['image'])){
            foreach ($config['image'] as $key => $val) {
                $val = array_merge($imageDefault,$val);

                //建立画板 ，缩放图片至指定尺寸
                $info = getimagesize($val['url']);
                $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
                if ($val['stream']) {   //如果传的是字符串图像流
                    $info = getimagesizefromstring($val['url']);
                    $function = 'imagecreatefromstring';
                }

                $res = $function($val['url']);

                $resWidth = $info[0];
                $resHeight = $info[1];

                $canvas = imagecreatetruecolor($val['width'], $val['height']);
                if ($val['is_unhyaline']) {
                    $c = imagecolorallocate($canvas, 245 ,245 ,245);
                }else{
                    $c = imagecolorallocate($canvas, 255, 255, 255);
                }
                $color = imagecolortransparent($canvas,$c);
                imagefill($canvas, 0, 0, $color);

                //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
                imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'],$resWidth,$resHeight);
                $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']) - $val['width']:$val['left'];
                $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']) - $val['height']:$val['top'];

                //放置图像
                imagecopymerge($imageRes,$canvas, $val['left'],$val['top'],$val['right'],$val['bottom'],$val['width'],$val['height'],$val['opacity']);//左，上，右，下，宽度，高度，透明度
            }
        }

        //生成图片
        // 项目根目录
        $DOCUMENT_ROOT = request()->server('DOCUMENT_ROOT');
        $res = imagejpeg ($imageRes,$filename,90); //保存到本地
        imagedestroy($imageRes);

        if(!$res){
            return false;
        }
        return cfg('site_url').str_replace( $DOCUMENT_ROOT,'',$filename);
    }

    /**
     * 生成特定尺寸缩略图 解决原版缩略图不能满足特定尺寸的问题 PS：会裁掉图片不符合缩略图比例的部分
     * @static
     * @access public
     * @param string $image  原图
     * @param string $type 图像格式
     * @param string $thumbname 缩略图文件名
     * @param string $maxWidth  宽度
     * @param string $maxHeight  高度
     * @param boolean $interlace 启用隔行扫描
     * @return void
     */
    static function thumb2($image, $thumbname, $type='', $maxWidth=200, $maxHeight=50, $interlace=true) {
        ini_set('memory_limit', '512M');
        // 获取原图信息
        $info = self::getImageInfo($image);
        if ($info !== false) {
            $srcWidth = $info['width'];
            $srcHeight = $info['height'];
            $type = empty($type) ? $info['type'] : $type;
            $type = strtolower($type);
            $interlace = $interlace ? 1 : 0;
            unset($info);
            $scale = max($maxWidth / $srcWidth, $maxHeight / $srcHeight); // 计算缩放比例
            //判断原图和缩略图比例 如原图宽于缩略图则裁掉两边 反之..
            if($maxWidth / $srcWidth > $maxHeight / $srcHeight){
                //高于
                $srcX = 0;
                $srcY = ($srcHeight - $maxHeight / $scale) / 2 ;
                $cutWidth = $srcWidth;
                $cutHeight = $maxHeight / $scale;
            }else{
                //宽于
                $srcX = ($srcWidth - $maxWidth / $scale) / 2;
                $srcY = 0;
                $cutWidth = $maxWidth / $scale;
                $cutHeight = $srcHeight;
            }

            // 载入原图
            $createFun = 'ImageCreateFrom' . ($type == 'jpg' ? 'jpeg' : $type);
            $srcImg = $createFun($image);

            //创建缩略图
            if ($type != 'gif' && function_exists('imagecreatetruecolor'))
                $thumbImg = imagecreatetruecolor($maxWidth, $maxHeight);
            else
                $thumbImg = imagecreate($maxWidth, $maxHeight);

            //png和gif的透明处理
            if ('png' == $type) {
                imagealphablending($thumbImg, false);//取消默认的混色模式（为解决阴影为绿色的问题）
                imagesavealpha($thumbImg, true);//设定保存完整的 alpha 通道信息（为解决阴影为绿色的问题）
            } elseif ('gif' == $type) {
                $trnprt_indx = imagecolortransparent($srcImg);
                if ($trnprt_indx >= 0) {
                    //its transparent
                    $trnprt_color = imagecolorsforindex($srcImg, $trnprt_indx);
                    $trnprt_indx = imagecolorallocate($thumbImg, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
                    imagefill($thumbImg, 0, 0, $trnprt_indx);
                    imagecolortransparent($thumbImg, $trnprt_indx);
                }
            }
            // 复制图片
            if (function_exists("ImageCopyResampled"))
                imagecopyresampled($thumbImg, $srcImg, 0, 0, $srcX, $srcY, $maxWidth, $maxHeight, $cutWidth, $cutHeight);
            else
                imagecopyresized($thumbImg, $srcImg, 0, 0, $srcX, $srcY, $maxWidth, $maxHeight, $cutWidth, $cutHeight);
            if ('gif' == $type || 'png' == $type) {
                $background_color = imagecolorallocate($thumbImg, 0, 255, 0);  //  指派一个绿色
                imagecolortransparent($thumbImg, $background_color);  //  设置为透明色，若注释掉该行则输出绿色的图
            }

            // 对jpeg图形设置隔行扫描
            if ('jpg' == $type || 'jpeg' == $type)
                imageinterlace($thumbImg, $interlace);

            // 生成图片
            $imageFun = 'image' . ($type == 'jpg' ? 'jpeg' : $type);
            if($imageFun == 'imagejpeg'){
                $imageFun($thumbImg, $thumbname, 100);
            }else{
                $imageFun($thumbImg, $thumbname);
            }

            imagedestroy($thumbImg);
            imagedestroy($srcImg);
            return $thumbname;
        }
        return false;
    }


    /**
     * 取得图像信息
     * @static
     * @access public
     * @param string $image 图像文件名
     * @return mixed
     */

    static function getImageInfo($img) {
        $imageInfo = getimagesize($img);
        if ($imageInfo !== false) {
            $imageType = strtolower(substr(image_type_to_extension($imageInfo[2]), 1));
            // $imageSize = filesize($img);
            $info = array(
                "width" => $imageInfo[0],
                "height" => $imageInfo[1],
                "type" => $imageType,
                // "size" => $imageSize,
                "mime" => $imageInfo['mime']
            );
            return $info;
        } else {
            return false;
        }
    }


    /**
     *等比例缩放函数（以保存新图片的方式实现）
     * @param string $picName 被缩放的处理图片源
     * @param string $savePath 保存路径
     * @param int $maxx 缩放后图片的最大宽度
     * @param int $maxy 缩放后图片的最大高度
     * @param string $pre 缩放后图片的前缀名
     * @return $string 返回后的图片名称（） 如a.jpg->s.jpg
     *
     **/
    public function scaleImg($picName,$savePath, $maxx = 800, $maxy = 450, $pre = '')
    {
        $info = getimageSize($picName);//获取图片的基本信息
        $w = $info[0];//获取宽度
        $h = $info[1];//获取高度

        if($w<=$maxx&&$h<=$maxy){
            return $picName;
        }
        //获取图片的类型并为此创建对应图片资源
        switch ($info[2]) {
            case 1://gif
                $im = imagecreatefromgif($picName);
                break;
            case 2://jpg
                $im = imagecreatefromjpeg($picName);
                break;
            case 3://png
                $im = imagecreatefrompng($picName);
                break;
            default:
                die("图像类型错误");
        }

        //计算缩放比例
        if (($maxx / $w) > ($maxy / $h)) {
            $b = $maxy / $h;
        } else {
            $b = $maxx / $w;
        }
        //计算出缩放后的尺寸
        $nw = floor($w * $b);
        $nh = floor($h * $b);
        //创建一个新的图像源（目标图像）
        $nim = imagecreatetruecolor($nw, $nh);

        //透明背景变黑处理
        //2.上色
        $color=imagecolorallocate($nim,255,255,255);
        //3.设置透明
        imagecolortransparent($nim,$color);
        imagefill($nim,0,0,$color);


        //执行等比缩放
        imagecopyresampled($nim, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        //输出图像（根据源图像的类型，输出为对应的类型）
        $picInfo = pathinfo($picName);//解析源图像的名字和路径信息
        if($pre){
            $savePath = $savePath. "/".$pre . $picInfo["basename"];
        }

        $temp = explode(".", $savePath); //判断图片文件后缀名
        $extension = end($temp);
        if(in_array($extension, array('jpg', 'jpeg'))){
            imagejpeg($nim, $savePath);
        } elseif(in_array($extension, array('gif'))) {
            imagegif($nim, $savePath);
        } else {
            imagepng($nim, $savePath);
        }
        // switch ($info[2]) {
        //     case 1:
        //         imagegif($nim, $savePath);
        //         break;
        //     case 2:
        //         break;
        //     case 3:
        //         imagepng($nim, $savePath);
        //         break;

        // }
        //释放图片资源
        imagedestroy($im);
        imagedestroy($nim);
        //返回结果
        return $savePath;
    }


    /**
     * 将图片处理成圆形
     * @author Nd
     * @date 2022/4/11
     * @param string $imgpath 图片本地位置
     * @return false|resource
     */
    public function changeCircularImg($imgpath)
    {
        $src_img = null;
        $src_img = @imagecreatefromstring(file_get_contents($imgpath));
        //如果如果$imgpath为本地图片地址
        $wh = getimagesize($imgpath);
        $w = $wh[0];
        $h = $wh[1];
        $w = min($w, $h);
        $h = $w;
        $img = imagecreatetruecolor($w, $h); //这一句一定要有
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        imagesavealpha($img , true);
        $r = $w / 2;
        //圆半径
        $y_x = $r; //圆心X坐标
        $y_y = $r; //圆心Y坐标
        for ($x = 0; $x < $w;
             $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x,$y);
                //根据数学公式圆的计算方式 算的 (x-r)(x-r)+(y-r)(y-r)=r*r (x,y坐标点 r半径)
                if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
            }
        }
        return $img;
    }
	
	public function encodeImgToDataUrl(array $params)
	{
		$imgPath    = $params['imgPath'];
		$with       = isset($params['with'])   ?  $params['with']   : 460;
		$heiht      = isset($params['heiht'])  ?  $params['heiht']  : 358;
		$format     = isset($params['format']) ?  $params['format'] : 'data-url';
		$quality    = 80;
		try {
			$imgInfo=$this->traitEncodeImgFormat($imgPath, $with, $heiht, $format, $quality);
            if(!empty($imgInfo) && is_array($imgInfo) && isset($imgInfo['err_msg'])){
                fdump_api(['imgInfo'=>$imgInfo], '00encodeImgToDataUrl', 1);
                return '';
            }else{
                return $imgInfo;
            }
		}catch (Exception $e){
            fdump_api(['err_msg'=>$e->getMessage()], '00encodeImgToDataUrl', 1);
			return '';
		}
	}

	public function encodePngToJpg(array $params)
	{
		$imgPath    = $params['imgPath'];
		$savePath   = $params['savePath'];
		$with       = isset($params['with'])   ?  $params['with']   : 460;
		$heiht      = isset($params['heiht'])  ?  $params['heiht']  : 358;
		$format     = isset($params['format']) ?  $params['format'] : 'jpg';
		$quality    = isset($params['quality'])  ?  $params['quality']  : 80;;
		try {
            $imgInfo=$this->traitEncodeImgFormatAndSaveAs($imgPath,$savePath,$with,$heiht, $format , $quality);
            if(!empty($imgInfo) && is_array($imgInfo) && isset($imgInfo['err_msg'])){
                fdump_api(['imgInfo'=>$imgInfo], '00encodePngToJpg', 1);
                return '';
            }else{
                return $imgInfo;
            }
		}catch (Exception $e){
            fdump_api(['err_msg'=>$e->getMessage()], '00encodePngToJpg', 1);
            return '';
			//return $e->getMessage();
		}
	}
    public function compressImage($params){
        $imgPath    = $params['imgPath'];
        $savePath   = $params['savePath'];
        $with       = isset($params['with'])   ?  $params['with']   : 460;
        $heiht      = isset($params['heiht'])  ?  $params['heiht']  : 358;
        $format     = isset($params['format']) ?  $params['format'] : 'jpg';
        $quality    = isset($params['quality'])  ?  $params['quality']  : 80;
        $quality=$quality> 0 ? intval($quality):80; 
        try {
            $imgInfo=$this->compressImageTrait($imgPath,$savePath,$with,$heiht,$quality);
            if(!empty($imgInfo) && is_array($imgInfo) && isset($imgInfo['err_msg'])){
                fdump_api(['imgInfo'=>$imgInfo], '00compressImage', 1);
                return '';
            }else{
                return $imgInfo;
            }
        }catch (Exception $e){
            fdump_api(['err_msg'=>$e->getMessage()], '00compressImage', 1);
            return '';
            //return $e->getMessage();
        }
    }
	/**
     * 绘制圆角矩形
     */
	public function arcRec($imageObj, $arcRec_SX, $arcRec_SY, $arcRec_W, $arcRec_H, $redius, $color)
    {
        imagefilledrectangle($imageObj, $arcRec_SX + $redius, $arcRec_SY, $arcRec_SX + ($arcRec_W - $redius), $arcRec_SY + $redius, $color); //矩形一
        imagefilledrectangle($imageObj, $arcRec_SX, $arcRec_SY + $redius, $arcRec_SX + $arcRec_W, $arcRec_SY + ($arcRec_H - ($redius * 1)), $color);//矩形二
        imagefilledrectangle($imageObj, $arcRec_SX + $redius, $arcRec_SY + ($arcRec_H - ($redius * 1)), $arcRec_SX + ($arcRec_W - ($redius * 1)), $arcRec_SY + $arcRec_H, $color);//矩形三
        imagefilledarc($imageObj, $arcRec_SX + $redius, $arcRec_SY + $redius, $redius * 2, $redius * 2, 180, 270, $color, IMG_ARC_PIE); //四分之一圆 - 左上
        imagefilledarc($imageObj, $arcRec_SX + ($arcRec_W - $redius), $arcRec_SY + $redius, $redius * 2, $redius * 2, 270, 360, $color, IMG_ARC_PIE); //四分之一圆 - 右上
        imagefilledarc($imageObj, $arcRec_SX + $redius, $arcRec_SY + ($arcRec_H - $redius), $redius * 2, $redius * 2, 90, 180, $color, IMG_ARC_PIE); //四分之一圆 - 左下
        imagefilledarc($imageObj, $arcRec_SX + ($arcRec_W - $redius), $arcRec_SY + ($arcRec_H - $redius), $redius * 2, $redius * 2, 0, 90, $color, IMG_ARC_PIE); //四分之一圆 - 右下
    }

    /**
     * Notes: 图片格式转换
     * @param string $image_path 文件路径或url
     * @param string $to_ext 待转格式，支持png,gif,jpeg
     * @param integer $quality jpg图片质量
     * @return string
     * @throws Exception
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
        $file_handle = new FileHandle();
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
            $save_path = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_newChange.' . $ext;
        }else{
            return replace_file_domain($image_path);
        }


        if (function_exists('exif_imagetype')) {
            $img_key = exif_imagetype($image_path);
            if (!in_array($img_key,[2,10])) {
                switch ($img_key) {
                    case 1 :
                        $pathinfo['extension'] = 'gif';
                        $save_path = $pathinfo['dirname'] .'/'.$pathinfo['filename'].'_newChange.'.$ext;
                        break;
                    case 3:
                        $pathinfo['extension'] = 'png';
                        $save_path = $pathinfo['dirname'] .'/'.$pathinfo['filename'].'_newChange.'.$ext;
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
            if ('jpg' == $type || 'jpeg' == $type) {
                imageinterlace($img, true);
            }
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

    /**
     * 压缩图片
     */
    public function thumbImage($url, $width, $height, $resize)
    {
        if (empty($url) || empty($width) || empty($height)) {
            return $url;
        }
        $site_url = cfg('site_url');
        if (strpos($url, $site_url . '/upload/') === 0) {
            $filePath = str_replace($site_url . '/upload/', '', $url);
            $path = '/upload/thumbImg/' . $width . '_' . $height . ($resize ? '_' . $resize : '') . '/' . $filePath;
            $thumbFile = app()->getRootPath() . '../' . $path;
            if (function_exists('exif_read_data') && function_exists('imagecreatefromstring') && function_exists('imagerotate')) {
                $filePathRoot = app()->getRootPath() . '../upload/' . $filePath;
                $image = imagecreatefromstring(file_get_contents($filePathRoot));
                $exif = exif_read_data($filePathRoot);
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
                    imagejpeg($image, $filePathRoot, 100);
                    imagedestroy($image);
                }
                $thumbFilePathInfo =  pathinfo($thumbFile);
                if(file_exists($thumbFile) && filesize($thumbFile) > 300){
                    return  $site_url . $path;
                }
                if(!file_exists($thumbFilePathInfo['dirname'].'/') && !mkdir($thumbFilePathInfo['dirname'].'/',0777,true)){
                    return $url;
                }
            }
            if ($resize == 'fill') {
                $thumb = $this->thumb2($filePathRoot, $thumbFile, '', $width, $height);
            } else {
                $thumb = $this->thumb($filePathRoot, $thumbFile, '', $width, $height);
            }
            if($thumb){
                return  $site_url . $path;
            }else{
                return $url;
            }
        } else {
            return $url;
        }
    }


    /**
    +----------------------------------------------------------
     * 生成缩略图
    +----------------------------------------------------------
     * @static
     * @access public
    +----------------------------------------------------------
     * @param string $image  原图
     * @param string $type 图像格式
     * @param string $thumbname 缩略图文件名
     * @param string $maxWidth  宽度
     * @param string $maxHeight  高度
     * @param string $position 缩略图保存目录
     * @param boolean $interlace 启用隔行扫描
    +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    public function thumb($image, $thumbname, $type = '', $maxWidth = 200, $maxHeight = 50, $interlace = true)
    {
        // 获取原图信息
        $info = self::getImageInfo($image);
        if ($info !== false) {
            $srcWidth = $info['width'];
            $srcHeight = $info['height'];
            $type = empty($type) ? $info['type'] : $type;
            $type = strtolower($type);
            $interlace = $interlace ? 1 : 0;
            unset($info);
            $scale = min($maxWidth / $srcWidth, $maxHeight / $srcHeight); // 计算缩放比例
            if ($scale >= 1) {
                // 超过原图大小不再缩略
                $width = $srcWidth;
                $height = $srcHeight;
            } else {
                // 缩略图尺寸
                $width = (int)($srcWidth * $scale);
                $height = (int)($srcHeight * $scale);
            }
            // 载入原图
            $createFun = 'ImageCreateFrom' . ($type == 'jpg' ? 'jpeg' : $type);
            $srcImg = $createFun($image);

            if ($type == 'png') imagesavealpha($srcImg, true);

            //创建缩略图
            if ($type != 'gif' && function_exists('imagecreatetruecolor'))
                $thumbImg = imagecreatetruecolor($width, $height);
            else
                $thumbImg = imagecreate($width, $height);

            //防止用户上传图片过小，背景会变黑。设置背景颜色
            $bg = imagecolorallocate($thumbImg, 255, 255, 255);
            imagefill($thumbImg, 0, 0, $bg);
            // 复制图片
            if (function_exists("ImageCopyResampled"))
                imagecopyresampled($thumbImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
            else
                imagecopyresized($thumbImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
            if ('gif' == $type || 'png' == $type) {
                imagealphablending($thumbImg, false);//取消默认的混色模式
                imagesavealpha($thumbImg, true);//设定保存完整的 alpha 通道信息
            }
            // 对jpeg图形设置隔行扫描
            if ('jpg' == $type || 'jpeg' == $type)
                imageinterlace($thumbImg, $interlace);

            // 生成图片
            if ($type == 'jpg') {
                imagejpeg($thumbImg, $thumbname, 100);
            } else {
                $imageFun = 'image' . $type;
                $imageFun($thumbImg, $thumbname);
            }

            if (function_exists('exif_read_data') && function_exists('imagecreatefromstring') && function_exists('imagerotate')) {
                // 处理图片旋转问题
                $image_url = imagecreatefromstring(file_get_contents($thumbname));
                $exif = exif_read_data($image);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 8:
                            $image_url = imagerotate($image_url, 90, 0);
                            break;
                        case 3:
                            $image_url = imagerotate($image_url, 180, 0);
                            break;
                        case 6:
                            $image_url = imagerotate($image_url, -90, 0);
                            break;
                    }
                    imagejpeg($image_url, $thumbname);
                    imagedestroy($image_url);
                }
            }
            imagedestroy($thumbImg);
            imagedestroy($srcImg);
            return $thumbname;
        }
        return false;
    }

    /**
     * 文件源目录按照宽高等比压缩 以宽或者高为最大值进行等比
     */
    public function equalThumb($img_url, $config_site_url, $widthCondition = 1920, $heightCondition = 1080, $format = 'jpg', $quality = 90)
    {
        !$config_site_url && $config_site_url = cfg('site_url');
        $stripos_result = stripos($img_url, '/upload/');
        $thumb_url = '';
        if (strpos($img_url, $config_site_url) !== false || $stripos_result === 0) {
            $width = $widthCondition;
            $height = $heightCondition;
            if ($stripos_result === 0) {
                $img_url = $config_site_url . $img_url;
            }
            $pathinfo = pathinfo($img_url);
            $fileSizeArr = getimagesize($img_url);
            if (isset($fileSizeArr[0]) && isset($fileSizeArr[1]) && $fileSizeArr[0] && $fileSizeArr[1]) {
                $imgWidth = $fileSizeArr[0];
                $imgHeight = $fileSizeArr[1];
                if ($imgWidth >= $widthCondition) {
                    $width = $widthCondition;
                    $height = floor(($widthCondition / $imgWidth) * $imgHeight);
                } else {
                    $width = $imgWidth;
                    $height = $imgHeight;
                }
                if ($height >= $heightCondition) {
                    $width = floor(($heightCondition / $height) * $width);
                    $height = $heightCondition;
                }
                $width = intval($width);
                $height = intval($height);
            }
            $save_path = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_plot.' . $format;
            $save_path = str_replace($config_site_url . '/upload/', '/upload/', $save_path);
            $savePath = root_path() . '..' . $save_path;
            $filePath = str_replace($config_site_url . '/upload/', '/upload/', $img_url);
            $imgPath = app()->getRootPath() . '..' . $filePath;
            $filterImgChange = $this->traitEncodeImgFormatAndSaveAs($imgPath,$savePath,$width,$height, $format, $quality);
            if (!empty($filterImgChange) && !is_array($filterImgChange)) {
                $thumb_url = $config_site_url . $save_path;
            }
        }
        return ['img_url' => $img_url, 'thumb_url' => $thumb_url,'thumb_path'=>$save_path,'savePath'=>$savePath,'imgPath'=>$imgPath];
    }
}