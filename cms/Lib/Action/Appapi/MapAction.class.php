<?php
/*
 * 地图处理
 *
 */
class MapAction extends BaseAction{
	public function geocoder(){
		$lng = $_POST['lng'];
		$lat = $_POST['lat'];
		$url = 'http://api.map.baidu.com/geocoder/v2/?location='.$lat.','.$lng.'&output=json&pois=1&ak=' . $this->config['baidu_map_ak'];
		import('ORG.Net.Http');
		$http = new Http();
		$result = $http->curlGet($url);
		if($result){
			$result = json_decode($result,true);
			if($result['status'] == 0){
				$return = array();
				if($result['result']['pois']){
					foreach($result['result']['pois'] as $value){
						if(empty($value['point']['x'])){
							continue;
						}
						$return[] = array(
							'name'=>$value['name'],
							'lat'=>$value['point']['y'],
							'long'=>$value['point']['x'],
							'lng'=>$value['point']['x'],
							'adress'=>$value['addr'],
							'address'=>$value['addr']
						);
					}
				}
				$this->returnCode(0,$return);
			}else{
				$this->returnCode('20000002');
			}
		}else{
			$this->returnCode('20000002');
		}
	}
	
	public function geocoder_google(){
		$lng = $_POST['lng'];
		$lat = $_POST['lat'];
	    $key =C('config.google_map_ak');
        $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?location='.$lat.','.$lng.'&radius=1000&key='.$key;
		
        import('ORG.Net.Http');
        $http = new Http();
        $result = $http->curlGet($url);
		$return = [];
        if($result) {
			$result = json_decode($result, true);
			foreach($result['results'] as $value){
				$tmpAddress = array(
					'place_id' => $value['place_id'],
					'name' => $value['name'],
					'address_name' => $value['vicinity'],
					'lng' => $value['geometry']['location']['lng'],
					'lat' => $value['geometry']['location']['lat'],
				);
				$tmpAddress['range'] = getDistance($lat,$lng,$tmpAddress['lat'],$tmpAddress['lng']);
				
				$return[] = $tmpAddress;
			}
        }
		$return = sortArray($return, 'range');
		
		$this->returnCode(0,$return);
    }
	
	public function getAddressByPlaceId(){
		$detail_url = 'https://maps.googleapis.com/maps/api/place/details/json?placeid='.$_POST['place_id'].'&key='.C('config.google_map_ak').'&language=en';
		import('ORG.Net.Http');
		$http = new Http();
		$detail_result = $http->curlGet($detail_url);
		$detail_result = json_decode($detail_result, true);
		$result = $detail_result['result'];
		
		import('@.ORG.longlat');
		$longlat_class = new longlat();
		$address_info = $longlat_class->format_google_address($result['address_components']);
		$tmpInfo = array(
			'name'=>$address_info['street_address'],
			'address_name'=>$address_info['street_address'],
			'lat'=>$result['geometry']['location']['lat'],
			'long'=>$result['geometry']['location']['lng'],
			'lng'=>$result['geometry']['location']['lng'],
			'adress'=>$address_info['short_address'],
			'address'=>$address_info['short_address'],
			'district'=>$address_info['area'] ? $address_info['area'] : '',
		);
		$this->returnCodeOk($tmpInfo);
	}
	
	public function suggestion(){
		$query = $_POST['query'] ? $_POST['query'] : $_GET['query'];
		
		//经纬度处理
		if($_POST['longitude']){
			$_POST['lng'] = $_POST['longitude'];
		}
		if($_POST['latitude']){
			$_POST['lat'] = $_POST['latitude'];
		}
		
		if($this->config['map_config'] == 'google'){
			$language = D('Lang')->get_alias_by_lang();
			if(C('config.country_code') == 'au'){
				$url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?input='.urlencode($query).'&key='.C('config.google_map_ak').'&types=address&components=country:au|country:nz&language=en';
			}else if(C('config.country_code') == 'kh'){
				$url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?input='.urlencode($query).'&key='.C('config.google_map_ak').'&components=country:'.C('config.country_code');
			}else{
				$url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?input='.urlencode($query).'&key='.C('config.google_map_ak').'&types=address&components=country:'.C('config.country_code').'&language=en';
			}
			import('ORG.Net.Http');
			$http = new Http();
			$near_result = $http->curlGet($url);
			fdump($url,'google_url');
			fdump($near_result,'google_url',true);
			$near_result = json_decode($near_result, true);
			$return = array();
			foreach($near_result['predictions'] as $value){
				$tmpInfo = array(
					'name' 		=> $value['structured_formatting']['main_text'],
					'address_name' 		=> $value['description'],
					'place_id' 		=> $value['place_id'],
				);
				$tmpInfoName = str_replace($query,'&^&'.$query.'&^&',$tmpInfo['name']);
				$tmpNameArr = explode('&^&',$tmpInfoName);
				$tmpInfo['name_arr'] = array();
				foreach($tmpNameArr as $tmpValue){
					if(!empty($tmpValue)){
						$tmpInfo['name_arr'][] = array(
							'value'   => $tmpValue,
							'is_high' => $tmpValue == $query ? true : false
						);
					}
				}
				$return[] = $tmpInfo;
			}
			$this->returnCode(0,$return);
		}
		
		
		if($_POST['area_name']){
			$area_name = $_POST['area_name'];
		}else{
			$area_name = L_('中国');
		}
		
		$city_limit = 'true';
		if($area_name == L_('中国')){
			$city_limit = 'false';
		}else if($area_name == L_('黔东南')){
			$area_name = L_('黔东南苗族侗族自治州');
		}else if($area_name == L_('西双版纳')){
			$area_name = L_('西双版纳傣族自治州');
		}

		if ($_POST['coordinate_type'] == 'wgs84' && C('config.tianditu_key')) {
			//先走天地图解析
			$result = Tianditu::searchAround($query, $_POST['lng'] . ',' . $_POST['lat'], C('config.tianditu_key'), 10000, 0, 20);
			$return = array();
			if (!empty($result['pois'])) {
				foreach ($result['pois'] as $value) {
					$lonlatArr = explode(',', $value['lonlat']);
					$return[] = array(
						'name' => $value['name'],
						'address_name'=>$value['name'],
						'lat' => $lonlatArr[1],
						'long' => $lonlatArr[0],
						'lng' => $lonlatArr[0],
						'address' => $value['name'],
						'adress'=> $value['name'],
						'district'=>'',
						'place_id'=>'',
					);
				}
			}
			$this->returnCode(0, $return);
		}
		
		$hasLocation = false;
		if($_POST['lng'] && $_POST['lat']){
            $hasLocation = true;
        }
        $city_limit = $_POST['city_limit'] ? $_POST['city_limit'] : true;
		if($hasLocation){
            $url = 'http://api.map.baidu.com/place/v2/suggestion?query='.urlencode($query).'&region='.urlencode($area_name).'&ak='.$this->config['baidu_map_ak'].'&city_limit='.$city_limit.'&output=json&page_size=20&location='.$_POST['lat'].','.$_POST['lng'];
		}else{
            $url = 'http://api.map.baidu.com/place/v2/search?query='.urlencode($query).'&region='.urlencode($area_name).'&ak='.$this->config['baidu_map_ak'].'&city_limit='.$city_limit.'&output=json&page_size=20&scope=1';
		}
		import('ORG.Net.Http');
		$http = new Http();
		$result = $http->curlGet($url);
		if($result){
			$result = json_decode($result,true);
			if($result['status'] == 0){
                if($hasLocation){
                    $result['results'] = $result['result'];
                }
				$return = array();
				if($result['results']){
					foreach($result['results'] as $value){
						if(empty($value['location']['lng'])){
							continue;
						}
						$tmpInfo = array(
							'name'=>$value['name'],
							'address_name'=>$value['name'],
							'lat'=>$value['location']['lat'],
							'long'=>$value['location']['lng'],
							'lng'=>$value['location']['lng'],
							'adress'=>$hasLocation ? $value['city'].$value['district'] : $value['address'],
							'address'=>$hasLocation ? $value['city'].$value['district'] : $value['address'],
							'district'=>$hasLocation ? $value['district'] : $value['area'],
							'place_id'=>'',
						);
                        is_null($tmpInfo['adress']) && $tmpInfo['adress'] = $tmpInfo['address_name'];
                        is_null($tmpInfo['address']) && $tmpInfo['address'] = $tmpInfo['address_name'];
                        is_null($tmpInfo['district']) && $tmpInfo['district'] = '';
						$tmpInfoName = str_replace($query,'&^&'.$query.'&^&',$tmpInfo['name']);
						$tmpNameArr = explode('&^&',$tmpInfoName);
						$tmpInfo['name_arr'] = array();
						foreach($tmpNameArr as $tmpValue){
							if(!empty($tmpValue)){
								$tmpInfo['name_arr'][] = array(
									'value'   => $tmpValue,
									'is_high' => $tmpValue == $query ? true : false
								);
							}
						}
						$return[] = $tmpInfo;
					}
					if($_POST['lng'] && $_POST['lat']){
                        foreach($return as $key=>$value){
                            $return[$key]['distance'] = getDistance($_POST['lat'],$_POST['lng'],$value['lat'],$value['lng']);
                            $return[$key]['distance'] = getFormatNumber($return[$key]['distance']/1000);
                        }
                        $return = sortArray($return,'distance');
                    }
				}
				$this->returnCode(0,$return);
			}else{
				//百度地图解析失败，则判断是否配置了天地图
				if (C('config.tianditu_key')) {
					//先走天地图解析
					if($hasLocation){
						list($_POST['lng'], $_POST['lat']) = Coordinate::bd09ToWgs84($_POST['lng'], $_POST['lat']);
						$result = Tianditu::searchAround($query, $_POST['lng'] . ',' . $_POST['lat'], C('config.tianditu_key'), 10000, 0, 20);
					}else{
						$result = Tianditu::searchAroundResultWithArea($query, $area_name, C('config.tianditu_key'), 0, 20);
					}
					
					$return = array();
					if (!empty($result['pois'])) {
						foreach ($result['pois'] as $value) {
							$lonlatArr = explode(',', $value['lonlat']);
							list($lonlatArr[0], $lonlatArr[1]) = Coordinate::wgs84ToBd09($lonlatArr[0], $lonlatArr[1]);
							$return[] = array(
								'name' => $value['name'],
								'address_name'=>$value['name'],
								'lat' => $lonlatArr[1],
								'long' => $lonlatArr[0],
								'lng' => $lonlatArr[0],
								'address' => $value['name'],
								'adress'=> $value['name'],
								'district'=>'',
								'place_id'=>'',
							);
						}
					}
					$this->returnCode(0, $return);
				}

				$this->returnCodeError($result['message']);
			}
		}else{
			$this->returnCode(0,array());
		}
	}
	/*百度经纬度转火星经纬度*/
	public function baiduToGcj02(){
		import('@.ORG.longlat');
		$longlat_class = new longlat();
		$location2 = $longlat_class->baiduToGcj02($_POST['baidu_lat'], $_POST['baidu_lng']);
		if($location2){
			$this->returnCode(0,$location2);
		}else{
			$this->returnCode('20000002');
		}
	}
}
?>