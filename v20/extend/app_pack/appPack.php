<?php
namespace app_pack;
use net\Http;
/* 打包 */
class appPack{
	public function getNewVersion(){
		$parse_url = parse_url(cfg('site_url'));
		$return = Http::curlGet('https://o2o-service.pigcms.com/workorder/appPackVersion.php?domain=' . $parse_url['host']);
		$returnArr = json_decode($return, true);

		return $returnArr;
	}
	
	public function packApp($type){
		$parse_url = parse_url(cfg('site_url'));
		$postData = [
			'type' => $type,
			'domain' => $parse_url['host'],
		];
		$return = Http::curlPostOwn('https://o2o-service.pigcms.com/workorder/appPack.php', $postData);
		$returnArr = json_decode($return, true);
		
		return $returnArr;
	}
	
	public function getPackInfo($pack_id){
		$postData = [
			'pack_id' => $pack_id,
		];
		$return = Http::curlPostOwn('https://o2o-service.pigcms.com/workorder/appPackGet.php?type=get_pack', $postData);
		$returnArr = json_decode($return, true);
		
		return $returnArr;
	}
}
?>