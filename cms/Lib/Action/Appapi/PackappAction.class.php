<?php
class PackappAction extends BaseAction {
	public function index(){
		$pack_type = trim($_POST['pack_type']);
		$pack_version = trim($_POST['pack_version']);	//version 从压缩包目录下version.txt中读取，不一致则下载新包。
		
		//错误请求不存在
		$file_pack_version_dir = './packapp/'.$pack_type.'/';
		$file_pack_version_name = $file_pack_version_dir.'version.txt';
		if(!file_exists($file_pack_version_name)){
			$returnArr = ['status'=>'error','msg'=>'文件不存在'];
			if($_POST['pack_all_return']){
				return $returnArr;
			}else{
				$this->returnCodeOk($returnArr);
			}
		}
		$file_pack_version = trim(file_get_contents($file_pack_version_name));
		
		//版本号一致
		if($pack_version == $file_pack_version){
			$returnArr = ['status'=>'ok'];
			if($_POST['pack_all_return']){
				return $returnArr;
			}else{
				$this->returnCodeOk($returnArr);
			}
		}
		
		//版本不一致，处理下载
		$zip_file_name_dir = './upload/tmp_file/'.$pack_type.'/';
		$zip_file_name = $zip_file_name_dir.$file_pack_version.'.zig';
		$download_zip_file = file_domain().str_replace('./','/',$zip_file_name);
		
		//文件存在
		$file_handle = new file_handle();
		if($file_handle->file_exists($zip_file_name)){
			$returnArr = ['status'=>'download','url'=>$download_zip_file,'version'=>$file_pack_version];
			if($_POST['pack_all_return']){
				return $returnArr;
			}else{
				$this->returnCodeOk($returnArr);
			}
		}
		
		if(!file_exists($zip_file_name_dir)){
			mkdir($zip_file_name_dir,0777,true);
		}
		
		//删除以前该 pack_type 所有缓存的包
		import('ORG.Util.Dir');
        Dir::delDirnotself($zip_file_name_dir);
		
		//重新生成文件
		if(!class_exists('ZipArchive')){
			$returnArr = ['status'=>'error','msg'=>'ZipArchive不支持，linux需开启zlib，windows需取消php_zip.dll前的注释'];
			if($_POST['pack_all_return']){
				return $returnArr;
			}else{
				$this->returnCodeOk($returnArr);
			}
		}
		$zip = new ZipArchive();//使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
		if ($zip->open($zip_file_name, ZIPARCHIVE::CREATE) !== TRUE) {
			$returnArr = ['status'=>'error','msg'=>'无法打开文件，或者文件创建失败'];
			if($_POST['pack_all_return']){
				return $returnArr;
			}else{
				$this->returnCodeOk($returnArr);
			}
		}
		$datalist = $this->list_dir($file_pack_version_dir.'/');
		
		
		//替换图片
		$pic_dir = [];
		foreach($datalist as $val){
			$tmp_info = pathinfo($val);
			if(!file_exists('./runtime/pack_tmp/' . $tmp_info['dirname'])){
				mkdir('./runtime/pack_tmp/' . $tmp_info['dirname'], 0777, true);
			}
			if(in_array($tmp_info['extension'], ['png', 'jpg', 'gif', 'jpeg', 'ico'])){
				copy($val, './runtime/pack_tmp/' . $val . 'g');
				$pic_dir[] = $tmp_info['basename'];
			}
			if(in_array($tmp_info['extension'], ['wgt'])){
				copy($val, './runtime/pack_tmp/' . $val);
			}
		}
		foreach($datalist as $val){
			$tmp_info = pathinfo($val);
			if(!in_array($tmp_info['extension'], ['png', 'jpg', 'gif', 'jpeg', 'ico', 'wgt'])){
				$page_content = file_get_contents($val);
				foreach($pic_dir as $v){
					$page_content = str_replace($v, $v . 'g', $page_content);
					$page_content = str_replace($v . 'gg', $v . 'g', $page_content);
				}
				file_put_contents('./runtime/pack_tmp/' . $val, $page_content);
			}
		}
		$datalist = $this->list_dir('./runtime/pack_tmp/' . $file_pack_version_dir . '/');

		
		foreach($datalist as $val){
			if(file_exists($val)){
				$zip->addFile($val, str_replace('./runtime/pack_tmp/' . $file_pack_version_dir . '/', '', $val));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
			}
		}
		$zip->close();//关闭
		
		$file_handle = new file_handle();
		$file_handle->upload($zip_file_name);
		
		$returnArr = ['status'=>'download','url'=>$download_zip_file,'version'=>$file_pack_version];
		if($_POST['pack_all_return']){
			return $returnArr;
		}else{
			$this->returnCodeOk($returnArr);
		}
	}
	
	public function pack_all(){
		
		if($_SERVER['original_post']['all_pack_version']){
			$all_pack_version = json_decode($_SERVER['original_post']['all_pack_version'],true);
		}
		
		$pack_list = [];
		
		$dir = './packapp/';
		$file_dir = scandir($dir);
		foreach($file_dir as $file){
			if ($file == '.' || $file == '..'){
				continue;
			} elseif(is_dir($dir.$file)){
				if(strpos($file, '_dev') !== false){
					continue;
				}
				if(in_array($file,['deliver','storestaff','merchant','deliver_new','storestaff_web'])){
					continue;
				}
				$_POST['pack_type'] = $file;
				$_POST['pack_version'] = intval($all_pack_version[$file]);
				$_POST['pack_all_return'] = true;
				$pack_result = $this->index();
				if($pack_result['status'] == 'download'){
					$pack_list[] = [
						'name' => $file,
						'url' => $pack_result['url'],
					];
				}
			}
		}
		$this->returnCodeOk($pack_list);
	}
	
	//递归列目录
	public function list_dir($dir){
		$result = array();
		if (is_dir($dir)){
			$file_dir = scandir($dir);
			foreach($file_dir as $file){
				if ($file == '.' || $file == '..'){
					continue;
				}
				elseif (is_dir($dir.$file)){
					$result = array_merge($result, $this->list_dir($dir.$file.'/'));
				}
				else{
					array_push($result, $dir.$file);
				}
			}
		}
		return $result;
	}
}
?>