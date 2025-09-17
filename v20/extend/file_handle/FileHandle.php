<?php

namespace file_handle;

//-------------------------------
// FileHandle，用于各种云存储的事件判断
//------------------------------

use file_handle\ObsFileHandle;
use file_handle\OssFileHandle;
use file_handle\CosClient;

class FileHandle{

    public $ossClient;
	public $cosClient;
	public $obsClient;
	//架构函数
	public function __construct(){
		if(cfg('static_cos_switch')){
			$this->create_cos();
		}else if(cfg('static_oss_switch')){
			$this->create_oss();
		}else if(cfg('static_obs_switch')){
			$this->create_obs();
		}
	}
	public function create_cos(){
		$this->cosClient = new CosClient(cfg("static_cos_key"), cfg("static_cos_secret"), cfg("static_cos_region"), cfg("static_cos_bucket"));
	}
	public function create_obs(){
		$this->obsClient = new ObsFileHandle(cfg('static_obs_access_id'), cfg('static_obs_access_key'), cfg('static_obs_endpoint'), cfg('static_obs_bucket'));
	}
	public function create_oss(){
		$this->ossClient = new OssFileHandle(cfg('static_oss_access_id'), cfg('static_oss_access_key'), 'http://' . cfg('static_oss_endpoint'), cfg('static_oss_bucket'));
	}
	//文件上传
	public function upload($file, $imageslim=true){
		/* if($imageslim){
			$imageslim_class = new imageslim();
			$imageslim_class->slim($file);
		} */
		
		// 判断是否需要上传至云存储
		if(cfg('static_oss_switch') && cfg('static_oss_access_domain_names')){
			$filePath = str_replace('\\','/',realpath($file));
			$allow_arr = array('pem','p12','cer','pfx','zip','xls','xlsx');
			
			$pathinfo = pathinfo($filePath);
			$extension = isset($pathinfo['extension']) ? $pathinfo['extension'] : $pathinfo['filename'];
			
			if((stripos($filePath,'/upload/') !== false || stripos($filePath,'/tpl/') !== false || stripos($filePath,'/static/') !== false) && !in_array($extension,$allow_arr)){
				$uploadResult = $this->ossClient->upload($filePath);				
				return $uploadResult;
			}
		}else if(cfg('static_cos_switch') && cfg('static_cos_region')){
			$filePath = str_replace('\\','/',realpath($file));
			$allow_arr = array('pem','p12','cer','pfx','zip','xls','xlsx');

			$pathinfo = pathinfo($filePath);
            $extension = isset($pathinfo['extension']) ? $pathinfo['extension'] : $pathinfo['filename'];
			if((stripos($filePath,'/upload/') !== false || stripos($filePath,'/tpl/') !== false || stripos($filePath,'/static/') !== false) && !in_array($extension,$allow_arr)){
				$uploadResult = $this->cosClient->upload($filePath);
				return $uploadResult;
			}
		}else if(cfg('static_obs_switch') && cfg('static_obs_access_domain_names')){
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
		if(cfg('static_oss_switch') && cfg('static_oss_access_domain_names')){
			return true;
		}else if(cfg('static_cos_switch') && cfg('static_cos_region')){
            return true;
        }else if(cfg('static_obs_switch') && cfg('static_obs_access_domain_names')){
			return true;
		}
		return false;
	}
	//文件查找是否存在
	public function file_exists($file){
		// 判断是否需要上传至云存储
		if(cfg('static_oss_switch') && cfg('static_oss_access_domain_names')){
			return $this->ossClient->file_exists($file);
		}else if(cfg('static_obs_switch') && cfg('static_obs_access_domain_names')){
			return $this->obsClient->file_exists($file);
		}else{
			return file_exists($file);
		}
	}
	//文件去除域名后的相对路径
	public function get_path($file){
		if(cfg('static_oss_switch')){
			$file = str_replace(['http://' . cfg('static_oss_access_domain_names'), 'https://' . cfg('static_oss_access_domain_names')],'',$file);
		}else if(cfg('static_cos_switch')){
            $file = str_replace(['http://' . cfg('static_cos_access_domain_names'), 'https://' . cfg('static_cos_access_domain_names')], '', $file);
        }else if(cfg('static_obs_switch')) {
            $file = str_replace(['http://' . cfg('static_obs_access_domain_names'), 'https://' . cfg('static_obs_access_domain_names')], '', $file);
        }
		$file = str_replace(cfg('site_url'),'',$file);
		$file = explode('?', $file)[0];
		return $file;
	}
	//文件下载至服务器
	public function download($file){
        $file = str_replace(cfg('site_url'),'',$file);
        // 判断是否需要上传至云存储
        if(cfg('static_oss_switch') && cfg('static_oss_access_domain_names')){
            $file = str_replace(['http://' . cfg('static_oss_access_domain_names'), 'https://' . cfg('static_oss_access_domain_names')], '', $file);
            $filePath = WEB_PATH . $file;
            $filePath = str_replace('//','/',$filePath);
            if(stripos($filePath,'/upload/') !== false){
                $uploadResult = $this->ossClient->download($filePath);
                return $uploadResult;
            }
        } else if(cfg('static_cos_switch') && cfg('static_cos_region')) {
            $file = str_replace(['http://' . cfg('static_cos_access_domain_names'), 'https://' . cfg('static_cos_access_domain_names')], '', $file);
            $filePath = WEB_PATH . $file;
            $filePath = str_replace('//','/',$filePath);
            if(stripos($filePath,'/upload/') !== false){
                $uploadResult = $this->cosClient->download($filePath);
                return $uploadResult;
            }
        } else if(cfg('static_obs_switch') && cfg('static_obs_access_domain_names')) {
            $file = str_replace(['http://' . cfg('static_obs_access_domain_names'), 'https://' . cfg('static_obs_access_domain_names')], '', $file);
            $filePath = WEB_PATH . $file;
            $filePath = str_replace('//','/',$filePath);
            if(stripos($filePath,'/upload/') !== false){
                $uploadResult = $this->obsClient->download($filePath);
                return $uploadResult;
            }
        } else {
            return array('error'=>false,'msg'=>'success');
        }
	}
	//文件删除
	public function unlink($file){
		$filePath = WEB_PATH . $file;
		if(stripos($filePath, '/upload/') === false){
			return array('error'=>false,'msg'=>'success');
		}
		// 删除文件
		if(cfg('static_oss_switch') && cfg('static_oss_access_domain_names')){
			$uploadResult = $this->ossClient->unlink($filePath);	
			return $uploadResult;
		}else if(cfg('static_obs_switch') && cfg('static_obs_access_domain_names')) {
			$uploadResult = $this->obsClient->unlink($filePath);
			
			return $uploadResult;
		} else {
			@unlink($file);
			return array('error'=>false,'msg'=>'success');
		}
	}

}