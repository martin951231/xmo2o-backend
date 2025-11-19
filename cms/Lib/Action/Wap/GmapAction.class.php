<?php

//谷歌地图
class GmapAction extends BaseAction{
	public function index(){
		//得到当前城市的经纬度，用于地图初始化定位
		$database_area = D('Area');
		$now_city = $database_area->get_area_by_areaId($this->config['now_city']);
		if($now_city && $now_city['area_lng'] == 0){
            $result = D('Area')->address_info($now_city['area_name']);
            $lng = $result['result']['location']['lng'];
            $lat = $result['result']['location']['lat'];
            M('Area')->where(array('area_id'=>$this->config['now_city']))->data(array('area_lng'=>$lng,'area_lat'=>$lat))->save();
            $now_city = $database_area->get_area_by_areaId($this->config['now_city']);
		}
		
		if($now_city['area_lng'] == 0){
			$now_city['area_lng'] = '116.403847';
		}
		if($now_city['area_lat'] == 0){
			$now_city['area_lat'] = '39.915526';
		}
		$this->assign('now_city',$now_city);
		
		$this->display();
	}
}

?>