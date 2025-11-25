<?php
/*
 * 图片处理控制器
 *
 */
class ImageAction extends Action{
	protected $config;
	protected function _initialize(){
		$this->config = D('Config')->get_config();
		//$this->config['site_url'] = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
		C('config',$this->config);
	}
	public $thumbWidthHeight = array(
		'750_1000',
		'750_750',
		'900_500',
		'450_250',
		'276_168',
		'228_140',
		'240_240',
		'396_240',
		'198_120',
		'180_120',
		'120_120',
		'60_60',
		'90_60',
		'180_180',
		'500_400',
		'500_300',
		'150_100',
		'320_320',
		'200_200',
		'96_96',
        '148_148',
        '400_400',
        '600_600',
        '1000_1000',
        '900_900',
        '200_200',
        '230_230',
		'450_260',
		'500_1000',
        '700_700',
        '480_720',
        '480_640',
        '640_480',
		'640_640',
        '640_240',
        '750_490',
		'500_500',
        '320_205'
	);
    public function thumb(){
    	ini_set ("memory_limit","-1");
		// header('HTTP/1.1 304 Not Modified');
		$url = $_GET['url'];
		$width = intval($_GET['width']);
		$height = intval($_GET['height']);
        $check_url_res = (bool) preg_match('/' .
            implode('|', array_map(function ($v) {
                return preg_quote(trim($v, '/'), '/');
            }, array_filter(array_unique([
                C('config.site_url'),
                C('config.static_oss_access_domain_names'),
                C('config.static_obs_access_domain_names'),
                C('config.static_cos_access_domain_names'),
            ])))) . '/i', $url);
		if(empty($url) || !$check_url_res){
            $this->display();exit();
        }
		if(empty($url) || empty($width) || empty($height) || !in_array($width.'_'.$height,$this->thumbWidthHeight)){
			redirect($url);
		}

		if (strpos($url, C('config.site_url')) !== false) {
			$url = replace_file_domain($url);
		}

		if(strpos($url, !empty($this->config['config_site_url']) ? $this->config['config_site_url'].'/upload/' : $this->config['site_url'].'/upload/') === 0){
			$filePath = str_replace(!empty($this->config['config_site_url']) ? $this->config['config_site_url'].'/upload/' : $this->config['site_url'].'/upload/','',$url);
			$filePathInfo = pathinfo($filePath);
			$thumbFile = './upload/thumbImg/'.$width.'_'.$height.($_GET['resize'] ? '_'.$_GET['resize'] : '').'/'.$filePath;

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
					imagejpeg($image, './upload/'.$filePath);
					imagedestroy($image);
				}
			}

			$thumbFilePathInfo =  pathinfo($thumbFile);
			
			switch($thumbFilePathInfo['extension']){
				case 'png':
					$headerType = 'image/png';
					break;
				case 'gif':
					$headerType = 'image/gif';
					break;
				default:
					$headerType = 'image/jpeg';
			}
			if(file_exists($thumbFile) && filesize($thumbFile) > 300){
				$this->_addEtag($thumbFile);
				header('Content-type: '.$headerType);
				echo file_get_contents($thumbFile);exit;
			}
			
			
			if(!file_exists($thumbFilePathInfo['dirname'].'/') && !mkdir($thumbFilePathInfo['dirname'].'/',0777,true)){
				redirect($url);
			}
			import('ORG.Util.Image');
			$ImageObj = new Image();
			if($_GET['resize'] == 'fill'){
				$thumb = $ImageObj->thumb2('./upload/'.$filePath,$thumbFile,'',$width,$height);
			}else{
				$thumb = $ImageObj->thumb('./upload/'.$filePath,$thumbFile,'',$width,$height);
			}
			
			//强制图片文件大小，再次压缩图片尺寸。例如海康人脸设备要求200kb以下的图片，像素又要尽量保存640。
			if($_GET['maxfilesize']){
				$filesize = filesize($thumbFile);
				if($filesize > $_GET['maxfilesize'] * 1000){
					$this->scal_image_size($filePath, $thumbFile, $width, $height, $_GET['resize'], $_GET['maxfilesize']);
				}
			}
			
			if($thumb){
				$this->_addEtag($thumbFile);
				header('Content-type: '.$headerType);
				echo file_get_contents($thumbFile);exit;
			}else{
				redirect($url);
			}
		}else{
			redirect($url);
		}
    }
	
	//压缩图片文件大小
	private function scal_image_size($filePath, $thumbFile, $width, $height, $resize, $maxfilesize){
		import('ORG.Util.Image');
		$ImageObj = new Image();
		$new_width = $width - 30;
		$new_height = $height - 30;
		if($resize == 'fill'){
			$thumb = $ImageObj->thumb2('./upload/'.$filePath, $thumbFile, '', $new_width, $new_height);
		}else{
			$thumb = $ImageObj->thumb('./upload/'.$filePath, $thumbFile, '', $new_width, $new_height);
		}
		$filesize = filesize($thumbFile);
		
		if($filesize > $maxfilesize * 1000){
			$this->scal_image_size($filePath, $thumbFile, $new_width, $new_height, $resize, $maxfilesize);
		}
	}

	public function wx_image_download(){
		$url = urldecode($_GET['url']);
		$path = $_GET['path'];
		$file_ext = $_GET['file_ext']?$_GET['file_ext']:'jpg';

		$upload_dir = './upload/wx_img/'.$path;

		$image_name = md5($url).'.'.$file_ext;
		if(!is_dir($upload_dir)){

			mkdir($upload_dir, 0777, true);
		}
		
		$file_handle = new file_handle();
		if($file_handle->file_exists($upload_dir.'/'.$image_name)){
			redirect(file_domain().str_replace('./','/',$upload_dir).'/'.$image_name);
		}else{
			$image_file = file_get_contents($url);

			file_put_contents($upload_dir.'/'.$image_name,$image_file);
			
			
			//判断是否需要上传至云存储
			$file_handle = new file_handle();
			$file_handle->upload($upload_dir.'/'.$image_name);
			redirect(file_domain().str_replace('./','/',$upload_dir).'/'.$image_name);
		}
	}


	private function _addEtag($file){
		header("Cache-Control: private, max-age=10800, pre-check=10800");
		header("Pragma: private");
		header("Expires: " . date(DATE_RFC822,strtotime(" 2 day")));
	}



	public function show_share_image(){
        ini_set('memory_limit', '512M');
		$img_url = $_GET['img_url'];

		$image_arr = explode('_',$img_url);
		$type=$image_arr[0];
		$leader_id=$image_arr[1];
		$goods_id=$image_arr[2];
		$image_type=$image_arr[3];
		$shareUid=$image_arr[4];

		$image_name = $leader_id.'_'.$goods_id;
		
		if(!empty($shareUid)){
            $image_name = $image_name.'_'.$shareUid;
        }
		$filename = './runtime/tmpshareImage/goods_'.$image_name.'.jpg';
		if($type=='goodswx'){
			$filename = './runtime/tmpshareImage/goodswx_'.$image_name.'.jpg';
		}else if($type=="first"){
			if ($image_type) {
				$filename = './runtime/tmpshareImage/first_'.$leader_id.'_'.$image_type.'_'.date('ymd').'.jpg';
			}else{				
				$filename = './runtime/tmpshareImage/first_'.$leader_id.'_'.date('ymd').'.jpg';
			}
		}
		if(is_file($filename)){
			redirect($this->config['site_url'].substr($filename,1));die;
		}


		if($type=='goodswx') {

			$imgs = D('Village_group')->get_share_wx_goods($goods_id,$leader_id);
		}else if($type=="first"){
            $where['g.status']  = 1;
            if($leader_id){
                $address = M('Village_group_pick_address')->where(array('header_uid'=>$leader_id))->find();
                $where['_string'] = "(b.city_id IS NUll OR (ga.area_id = {$address['province_id']}) OR ga.area_id = {$address['city_id']} OR ga.area_id = {$address['area_id']}) AND (b.buy_user_type IS NUll OR b.buy_user_type=0 OR (b.buy_user_type=1 AND locate({$address['id']} , b.address_list)) OR  (b.buy_user_type=2 AND locate({$address['id']} , b.address_list)=0))";
            }
            $left_join = 'as g LEFT JOIN '.C('DB_PREFIX').'village_group_goods_bind_area b ON g.goods_id = b.goods_id LEFT JOIN '.C('DB_PREFIX').'village_group_area ga ON ga.pigcms_id = b.city_id  LEFT JOIN '.C('DB_PREFIX').'area a ON a.area_id = ga.area_id LEFT JOIN '.C('DB_PREFIX').'village_group_goods_increase_price i ON i.goods_id = g.goods_id AND i.header_uid='.$leader_id;

            $count = D('Village_group_goods')->join($left_join)->where($where)->count();

			$header = D('Village_group')->get_header_info(0,$leader_id);

			$header  = $header['msg'];
			
			if ($image_type == 4) {
				// 第四种只有一种图
				$imgs = D('Village_group')->get_share_image(4,$header,4);
			}else{
				if($count>8) {
					$count = 10;
					$imgs = D('Village_group')->get_share_image($count,$header,$image_type);
				}else if($count>6){
					$count = 8;
					$imgs = D('Village_group')->get_share_image($count,$header,$image_type);
				}else{
					$count = 6;
					$imgs= D('Village_group')->get_share_image($count,$header,$image_type);
				}
			}
		}else{
			$header = D('Village_group')->get_header_info(0,$leader_id);
			$header  = $header['msg'];
			$imgs = D('Village_group')->get_share_goods($goods_id,$header,$shareUid);
		}

		redirect($this->config['site_url'].substr($imgs,1));
	}
	
	public function upload_oss(){
		$file = ltrim($_GET['file'], '/');
		
		$file_handle = new file_handle();
		if(!$file_handle->check_open_oss()){
			exit('云存储未开启，无法使用');
		}
		$upload_result = $file_handle->upload($file);
		dump($upload_result);
	}
}
