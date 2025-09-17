<?php
/*
 *
 * OSS上传辅助类
 *
 */
class file_handle{
	public $ossClient;
	public $cosClient;
	public $obsClient;
	//架构函数
	public function __construct(){
		if(C('config.static_cos_switch')){
			$this->create_cos();
		}else if(C('config.static_oss_switch')){
			$this->create_oss();
		}else if(C('config.static_obs_switch')){
			$this->create_obs();
		}
	}
	public function create_cos(){
		import('@.ORG.Cos_client');
		$this->cosClient = new Cos_client(C("config.static_cos_key"), C("config.static_cos_secret"), C("config.static_cos_region"), C("config.static_cos_bucket"));
	}
	public function create_obs(){
		import('@.ORG.Obs_client');
		$this->obsClient = new Obs_client(C('config.static_obs_access_id'), C('config.static_obs_access_key'), C('config.static_obs_endpoint'), C('config.static_obs_bucket'));
	}
	public function create_oss(){
		import('@.ORG.Oss_client');
		$this->ossClient = new Oss_client(C('config.static_oss_access_id'), C('config.static_oss_access_key'), 'http://' . C('config.static_oss_endpoint'), C('config.static_oss_bucket'));
	}
	//文件上传
	public function upload($file, $imageslim=true){
		if($imageslim){
			$imageslim_class = new imageslim();
			$imageslim_class->slim($file);
		}
		
		// 判断是否需要上传至云存储
		if(C('config.static_cos_switch') && C('config.static_cos_region')){
			$filePath = str_replace('\\','/',realpath($file));
			$allow_arr = array('pem','p12','cer','pfx','zip','xls','xlsx');
			
			$pathinfo = pathinfo($filePath);
			$extension = $pathinfo['extension'] ? $pathinfo['extension'] : $pathinfo['filename'];
			
			if((stripos($filePath,'/upload/') !== false || stripos($filePath,'/tpl/') !== false || stripos($filePath,'/static/') !== false) && !in_array($extension,$allow_arr)){			
				$uploadResult = $this->cosClient->upload($filePath);				
				return $uploadResult;
			}
		}
		if(C('config.static_oss_switch') && C('config.static_oss_access_domain_names')){
			$filePath = str_replace('\\','/',realpath($file));
			$allow_arr = array('pem','p12','cer','pfx','zip','xls','xlsx');
			
			$pathinfo = pathinfo($filePath);
			$extension = $pathinfo['extension'] ? $pathinfo['extension'] : $pathinfo['filename'];
			
			if((stripos($filePath,'/upload/') !== false || stripos($filePath,'/tpl/') !== false || stripos($filePath,'/static/') !== false) && !in_array($extension,$allow_arr)){				
				$uploadResult = $this->ossClient->upload($filePath);				
				return $uploadResult;
			}
		}else if(C('config.static_obs_switch') && C('config.static_obs_access_domain_names')){
			$filePath = str_replace('\\','/',realpath($file));
			$allow_arr = array('pem','p12','cer','pfx','zip','xls','xlsx');

			$pathinfo = pathinfo($filePath);
			$extension = $pathinfo['extension'] ? $pathinfo['extension'] : $pathinfo['filename'];
			if((stripos($filePath,'/upload/') !== false || stripos($filePath,'/tpl/') !== false || stripos($filePath,'/static/') !== false) && !in_array($extension,$allow_arr)){
				$uploadResult = $this->obsClient->upload($filePath);
				return $uploadResult;
			}
		}else{
			return array('error'=>false,'msg'=>'success');
		}
	}
	//系统是否有开启云存储
	public function check_open_oss(){
		if(C('config.static_oss_switch') && C('config.static_oss_access_domain_names')){
			return true;
		}else if(C('config.static_cos_switch') && C('config.static_cos_region')){
			return true;
		}else if(C('config.static_obs_switch') && C('config.static_obs_access_domain_names')){
			return true;
		}
		return false;
	}
	//文件查找是否存在
	public function file_exists($file){
		// 判断是否需要上传至云存储
		if(C('config.static_oss_switch') && C('config.static_oss_access_domain_names')){
			return $this->ossClient->file_exists($file);
		}else if(C('config.static_obs_switch') && C('config.static_obs_access_domain_names')){
            return $this->obsClient->file_exists($file);
        }else{
			return file_exists($file);
		}
	}
	//文件去除域名后的相对路径
	public function get_path($file){
		if(C('config.static_oss_switch')){
			$file = str_replace($_SERVER['REQUEST_SCHEME'].'://'.C('config.static_oss_access_domain_names'),'',$file);
		}else if(C('config.static_cos_switch')){
			$file = str_replace($_SERVER['REQUEST_SCHEME'].'://'.C('config.static_cos_access_domain_names'),'',$file);
		}else if(C('config.static_obs_switch')){
			$file = str_replace($_SERVER['REQUEST_SCHEME'].'://'.C('config.static_obs_access_domain_names'),'',$file);
		}	
		$file = str_replace(C('config.site_url'),'',$file);
		$file = explode('?', $file)[0];
		return $file;
	}
	//文件下载至服务器
	public function download($file){
		// 判断是否需要上传至云存储
		if(C('config.static_oss_switch') && C('config.static_oss_access_domain_names')){
            //检测图片是否已经包含压缩，若压缩则去除
            $tmp_img_arr = explode('?x-oss-process=', $file);
            if(count($tmp_img_arr) >= 2){
                $file = $tmp_img_arr[0];
            }
            $file = str_replace($_SERVER['REQUEST_SCHEME'].'://'.C('config.static_oss_access_domain_names'),'',$file);
            $file = str_replace(C('config.site_url'),'',$file);
			$filePath = WEB_PATH.$file;
			$filePath = str_replace('//','/',$filePath);
			if(stripos($filePath,'/upload/') !== false){
				$uploadResult = $this->ossClient->download($filePath);
				return $uploadResult;
			}
		} else if(C('config.static_obs_switch') && C('config.static_obs_access_domain_names')) {
            //检测图片是否已经包含压缩，若压缩则去除
            $tmp_img_arr = explode('?x-image-process=', $file);
            if(count($tmp_img_arr) >= 2){
                $file = $tmp_img_arr[0];
            }
            $file = str_replace($_SERVER['REQUEST_SCHEME'].'://'.C('config.static_obs_access_domain_names'),'',$file);
            $file = str_replace(C('config.site_url'),'',$file);
			$filePath = WEB_PATH.$file;
			$filePath = str_replace('//','/',$filePath);
			if(stripos($filePath,'/upload/') !== false){
				$uploadResult = $this->obsClient->download($filePath);
				return $uploadResult;
			}
		} else if(C('config.static_cos_switch') && C('config.static_cos_access_domain_names')) {
            //检测图片是否已经包含压缩，若压缩则去除
            $tmp_img_arr = explode('?imageView2', $file);
            if(count($tmp_img_arr) >= 2){
                $file = $tmp_img_arr[0];
            }
            $file = str_replace($_SERVER['REQUEST_SCHEME'].'://'.C('config.static_cos_access_domain_names'),'',$file);
            $file = str_replace(C('config.site_url'),'',$file);
			$filePath = WEB_PATH.$file;
			$filePath = str_replace('//','/',$filePath);
			if(stripos($filePath,'/upload/') !== false){
				$uploadResult = $this->cosClient->download($filePath);
				return $uploadResult;
			}
		} else {
			return array('error'=>false,'msg'=>'success');
		}
	}
	//文件删除
	public function unlink($file){
		$filePath = WEB_PATH.$file;
		if(stripos($filePath,'/upload/') === false){
			return array('error'=>false,'msg'=>'success');
		}
		// 删除文件
		if(C('config.static_oss_switch') && C('config.static_oss_access_domain_names')){
			$uploadResult = $this->ossClient->unlink($filePath);	
			return $uploadResult;
		}else if(C('config.static_obs_switch') && C('config.static_obs_access_domain_names')) {
			$uploadResult = $this->obsClient->unlink($filePath);
			
			return $uploadResult;
		} else {
			@unlink($file);
			return array('error'=>false,'msg'=>'success');
		}
	}
}
?>