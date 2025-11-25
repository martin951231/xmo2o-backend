<?php
/* 
 * 图片瘦身
 * 
 */
class imageslim{
	/*图片瘦身*/
	public function slim($file){
		
		$pathinfo = pathinfo($file);
		if(!$pathinfo || !in_array(strtolower($pathinfo['extension']), ['png', 'jpg', 'jpeg'])){
			return false;
		}
		
		if( !C('config.open_imageslim') || !C('config.qiniu_bucket') || !C('config.qiniu_domain') || !C('config.qiniu_accesskey') || !C('config.qiniu_secretkey') ){
			return false;
		}
		
		// $imageslim = new imageslim();
		
		// $file = './upload/20200215151517_5e479a85deac3.jpeg';
		// $imageslim->slim($file);
		
		fdump($file . ' old ' . filesize($file), 'imageslim', true);
		
		//10KB以下的图片不压缩
		if(filesize($file) < 10240){
			return true;
		}
		
		import('@.ORG.Qiniu_client');
		$Qiniu_client = new Qiniu_client(C('config.qiniu_domain'), C('config.qiniu_accesskey'), C('config.qiniu_secretkey'), C('config.qiniu_bucket'));
		
		$result = $Qiniu_client->upload($file);
		// dump($result);
		
		$result = $Qiniu_client->download($file, true);
        // dump($result);
		
		$result = $Qiniu_client->unlink($file);
		// dump($result);
		
		fdump($file . ' new ' . filesize($file), 'imageslim', true);
		
		return true;
	}
}
?>