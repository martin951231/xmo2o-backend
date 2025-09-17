<?php
/*定时文件资源上传*/
class plan_upload_file extends plan_base{
	public function runTask($param=null){
		fdump($param, 'upload_file_param', true);
		$now_time=time();
		if(empty($param['path'])){
			return true;
		}
		$file_arr=array();
		if(is_dir($param['path'])){
			$file_arr=$this->get_file($param['path'],array(),array('php','html'));
		}else{
			$file_arr=$param['path'];
		}
		
		fdump($file_arr, 'upload_file_param', true);
		
		foreach ($file_arr as $k => $v){
			$this->keepThread();
			
			if(is_dir($v)){
				$sub_file_arr=$this->get_file($v,array(),array('php','html'));
				foreach ($sub_file_arr as $kk => $vv){
					$arr=array();
					$arr['param']=serialize(array('path'=>$vv));
					$arr['plan_time']=0;
					$arr['space_time']=0;
					$arr['add_time']=$now_time;
					$arr['file']='sub_upload_file';
					$arr['time_type'] = 1;
					$arr['unique_id'] = md5($vv);
					$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
					M('process_sub_plan')->add($arr);
					fdump(M('process_sub_plan')->getLastSql(), 'upload_file_param', true);
				}
			}else{
				$arr=array();
				$arr['param']=serialize(array('path'=>$v));
				$arr['plan_time']=0;
				$arr['space_time']=0;
				$arr['add_time']=$now_time;
				$arr['file']='sub_upload_file';
				$arr['time_type'] = 1;
				$arr['unique_id'] = md5($v);
				$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
				M('process_sub_plan')->add($arr);
				fdump(M('process_sub_plan')->getLastSql(), 'upload_file_param', true);
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
                $file_arr[]=$temp;
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