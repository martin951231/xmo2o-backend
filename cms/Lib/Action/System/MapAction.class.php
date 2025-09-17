<?php
/*
 * 地图处理
 *
 * @  Writers    Jaty
 * @  BuildTime  2014/11/06 15:07
 * 
 */
class MapAction extends BaseAction{
	public function frame_map(){
		$long_lat = $_GET['long_lat'];
		if(!$long_lat){
			if($this->config['map_config'] == 'google'){
				if($_GET['city_id']){
					$now_city = M('Area')->where(['area_id'=>$_GET['city_id']])->find();
					$long_lat = $now_city['area_lng'] . ',' . $now_city['area_lat'];
				}
				if(empty($now_city) || getFormatNumber($now_city['area_lat']) == '0'){
					$now_city = M('Area')->where(['area_id'=>$this->config['now_city']])->find();
					$long_lat = $now_city['area_lng'] . ',' . $now_city['area_lat'];
				}
			}else{
				$long_lat = '116.331398,39.897445';
			}
		}
		$this->assign('long_lat',$long_lat);
		$this->display();
	}
}