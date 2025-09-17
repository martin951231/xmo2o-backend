<?php
/*定时文件资源上传*/
class plan_sub_upload_file extends plan_base{
	public function runTask($param=null,$last_plan_time){
		$now_time=time();
		if(empty($param['path'])){
			return true;
		}
		$file_arr=array();
		if(is_dir($param['path'])){
			$file_arr=$this->get_file($param['path'],array(),array('php','html','otf','ttf','pem','p12','cer','pfx','zip','xls','xlsx'));
		}else{
			$file_arr=$param['path'];
		}
		fdump($file_arr, 'upload_file_sub_param', true);

		$file_handle=new file_handle();
		foreach ($file_arr as $k => $v){
			$this->keepThread();
			//上次修改的时间
			$edit_time=filemtime($v);
			if($edit_time<$last_plan_time && $file_handle->file_exists($v)){
				continue;
			}else{
				$res=$file_handle->upload($v);
				if(strpos($v, 'upload') !== false && $res['error'] === false){
					fdump($res, 'upload_file_sub_param', true);
					fdump($v, 'upload_file_sub_param', true);
					//fdump(unlink($v), 'upload_file_sub_param', true);
				}
			}
		}
		return true;
	}

	//遍历文件下的文件
	public function get_file($root_path,$file_arr=array(),$no_ext=null){
		$handle=opendir($root_path);
		if(!$handle){
			return $file_arr;
		}
		while (($fl=readdir($handle))!==false) {
			$temp = iconv('GBK','utf-8',$root_path.DIRECTORY_SEPARATOR.$fl);//转换成utf-8格式
            //如果不加  $fl!='.' && $fl != '..'  则会造成把$dir的父级目录也读取出来
            if(is_dir($temp) && $fl!='.' && $fl != '..'){
                $file_arr=$this->get_file($temp,$file_arr,$no_ext);
            }else{
                if($fl!='.' && $fl != '..'){
                	//去除后需要的后缀的文件
                	if(!empty($no_ext)){
                		$ext=array_pop(explode('.',$temp));
                		if(is_array($no_ext) && in_array($ext, $no_ext)){
                			continue;
                		}elseif($ext==$no_ext){
                			continue;
                		}
                	}
					$file_arr[]=$temp;
                }
            }
		}
		return $file_arr;
	}
}
?>