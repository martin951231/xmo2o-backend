<?php
/*
 * 地图处理
 *
 */
class MapAction extends Action{
	protected function _initialize(){
		$this->config = D('Config')->get_config();
		$this->config['site_url'] = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
		C('config',$this->config);
	}
	protected function sel_area($city_id,$str,$name){
	    // if(empty($str)) return $str;
	    static $data = array();
	    if(empty($data)){
			$data = D('Area')->where('area_pid='.$city_id)->field("area_name,area_ip_desc")->select();
		}
		foreach ($data as $key => $value) {
			if(($str && strpos($str, $value['area_ip_desc']) !== false) || ($name && strpos($name, $value['area_ip_desc']) !== false)){
				return $value['area_name'];
			}
			if(($str && strpos($str, $value['area_name']) !== false) || ($name && strpos($name, $value['area_name']) !== false)){
				return $value['area_name'];
			}
		}
		return "";	
	}
	protected function sel_city($city_id){
	    static $city_data = array();
		$city_name = '';
	    if(empty($city_data)){
			$city_name = M('Area')->where(['area_id'=>$city_id])->getField('area_name');
		}
		return $city_name;	
	}
	public function suggestion(){
		header("Content-type: application/json");
		$city_id = isset($_GET['city_id']) ? intval($_GET['city_id']) : $this->config['now_city'];
		$now_city = D('Area')->field(true)->where(array('area_id' => $city_id))->find();
		$city_limit = $_GET['city_limit'] ? 'true' : 'false';
		if($now_city['area_name'] == '黔东南'){
			$now_city['area_name'] = '黔东南苗族侗族自治州';
		}else if($now_city['area_name'] == '西双版纳'){
			$now_city['area_name'] = '西双版纳傣族自治州';
		}
		
		$_GET['query'] = str_replace('澳門','澳门',$_GET['query']);
		$now_city['area_name'] = str_replace('澳門','澳门',$now_city['area_name']);
		
		if($now_city['area_name']){
			$tmp_query = '<br/>'.$_GET['query'];
			$_GET['query'] = str_replace('<br/>'.$now_city['area_name'],'',$tmp_query);
			$_GET['query'] = str_replace('<br/>','',$_GET['query']);
		}
		
		//经纬度处理
		if($_GET['longitude']){
			$_GET['lng'] = $_GET['longitude'];
		}
		if($_GET['latitude']){
			$_GET['lat'] = $_GET['latitude'];
		}
        $lat = $_GET['lat'];
        $lng = $_GET['lng'];
		
		if($this->config['map_config'] == 'google'){
            $query = $_GET['query'];
			$language = D('Lang')->get_alias_by_lang();
			if(C('config.country_code') != 'kh'){
				$url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?input='.urlencode($query).'&key='.C('config.google_map_ak').'&types=address&components=country:'.C('config.country_code').'&language=en';
			}else{
				$url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?input='.urlencode($query).'&key='.C('config.google_map_ak').'&components=country:'.C('config.country_code');
			}
			// $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=' . $lat . ',' . $lng.'&keyword='.urlencode($_GET['query']).'&rankby=distance&type=restaurant&keyword=&key='.C('config.google_map_ak').'&language='.$language;
			import('ORG.Net.Http'); 
			$http = new Http();
			$near_result = $http->curlGet($url);
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
			
			// foreach($near_result['results'] as $value){
			// 	if (!isset($value['geometry']['location'])) continue; 
			// 	$return[] = array(
			// 		'name' 		=> $value['name'],
			// 		'lat'  		=> $value['geometry']['location']['lat'],
			// 		'long' 		=> $value['geometry']['location']['lng'],
			// 		'lng'  		=> $value['geometry']['location']['lng'],
			// 		'city' 		=> $value['city'] ? $value['city'] : '',
			// 		'district'	=> $value['vicinity'],
			// 		'address'	=> $value['vicinity']
			// 	);
			// }
			// if($_GET['lng'] && $_GET['lat']){
			// 	foreach($return as $key=>$value){
			// 		$return[$key]['distance'] = getDistance($_GET['lat'],$_GET['lng'],$value['lat'],$value['lng']);
			// 		$return[$key]['distance'] = getFormatNumber($return[$key]['distance']/1000);
			// 	}
			// 	$return = sortArray($return,'distance');
			// }
			exit(json_encode(array('status'=>1,'result'=>$return)));
		}
		if ($_GET['coordinate_type'] == 'wgs84' && C('config.tianditu_key')) {
			//先走天地图解析
			$result = Tianditu::searchAround($_GET['query'], $lng . ',' . $lat, C('config.tianditu_key'), 10000, 0, 20);
			$return = array();
			if (!empty($result['pois'])) {
				foreach ($result['pois'] as $value) {
					$lonlatArr = explode(',', $value['lonlat']);
					$return[] = array(
						'name' => $value['name'],
						'lat' => $lonlatArr[1],
						'long' => $lonlatArr[0],
						'lng' => $lonlatArr[0],
						//'city' => $value['city'] ? $value['city'] : $this->sel_city($city_id),
						//'district' => $value['area'] ? $value['area'] : $this->sel_area($city_id, $value['address'], $value['name']),
						'address' => $value['address']
					);
				}
			}
			exit(json_encode(array('status' => 1, 'result' => $return)));
		}
		$url = 'http://api.map.baidu.com/place/v2/search?query='.urlencode($_GET['query']).'&region='.urlencode($now_city['area_name']).'&ak='.$this->config['baidu_map_ak'].'&city_limit='.$city_limit.'&output=json&page_size=20';

		import('ORG.Net.Http');
		$http = new Http();
		$result = $http->curlGet($url);
		if($result){
			$result = json_decode($result,true);
			if($result['status'] == 0 && $result['results']){
				$return = array();
				foreach($result['results'] as $value){
					if (!isset($value['location'])) continue; 
					$return[] = array(
						'name'=>$value['name'],
						'lat'=>$value['location']['lat'],
						'long'=>$value['location']['lng'],
						'lng'=>$value['location']['lng'],
						'city'=>$value['city'] ? $value['city'] : $this->sel_city($city_id),
						'district'=>$value['area'] ? $value['area'] : $this->sel_area($city_id,$value['address'],$value['name']),
						'address'=>$value['address'] ? $value['address'] : $value['area'] ? $value['area'] : $this->sel_area($city_id,$value['address'],$value['name']),
					);
				}
				if($_GET['lng'] && $_GET['lat']){
					foreach($return as $key=>$value){
						$return[$key]['distance'] = getDistance($_GET['lat'],$_GET['lng'],$value['lat'],$value['lng']);
						$return[$key]['distance'] = getFormatNumber($return[$key]['distance']/1000);
					}
					$return = sortArray($return,'distance');
				}
				exit(json_encode(array('status'=>1,'result'=>$return)));
			}else{
				//尝试地点输入提示服务
				$url = 'http://api.map.baidu.com/place/v2/suggestion?query='.urlencode($_GET['query']).'&region='.urlencode($now_city['area_name']).'&ak='.$this->config['baidu_map_ak'].'&city_limit='.$city_limit.'&output=json&page_size=20';
				//echo $url;die;
				$http = new Http();
				$result = $http->curlGet($url);
				if($result){
					$result = json_decode($result,true);
					if($result['status'] == 0 && $result['result']){
						$return = array();
						foreach($result['result'] as $value){
							if (!isset($value['location'])) continue; 
							$return[] = array(
								'name'=>$value['name'],
								'lat'=>$value['location']['lat'],
								'long'=>$value['location']['lng'],
								'lng'=>$value['location']['lng'],
								'city'=>$value['city'],
								'district'=>$value['district'],
								'address'=>$value['district']
							);
						}
						if($_GET['lng'] && $_GET['lat']){
							foreach($return as $key=>$value){
								$return[$key]['distance'] = getDistance($_GET['lat'],$_GET['lng'],$value['lat'],$value['lng']);
								$return[$key]['distance'] = getFormatNumber($return[$key]['distance']/1000);
							}
							$return = sortArray($return,'distance');
						}
						exit(json_encode(array('status'=>1,'result'=>$return)));
					}else{
						exit(json_encode(array('status'=>2,'result'=>'没有查找到内容')));
					}
				}else{
					exit(json_encode(array('status'=>0,'result'=>'获取失败')));
				}
				
				exit(json_encode(array('status'=>2,'result'=>'没有查找到内容')));
			}
		}else{
			exit(json_encode(array('status'=>0,'result'=>'获取失败')));
		}
	}
	public function toBaidu(){
		if(empty($_POST)){
			$input_post = file_get_contents('php://input');
			$_POST = json_decode($input_post,true);
		}
		
		$lbs_type = 1;  //1:wgs84   3:gcj02
		if($_POST['lbs_type'] == 'gcj02'){
			$lbs_type = 3;
		}
		$streetName = $_POST['street_name']?:'';
		$addressName = $_POST['address']?:'';
		
		header("Content-type: application/json");
		import('@.ORG.longlat');
		$longlat_class = new longlat();
		$location2 = $longlat_class->toBaidu($_POST['lat'],$_POST['lng'],$lbs_type);//转换腾讯坐标到百度坐标
        if(empty($location2)){
            $location2=array('lat' => $_POST['lat'],'lng' => $_POST['lng']);
        }
		if($_POST['geocoder']){
			$geocoder = $this->geocoder($location2['lng'],$location2['lat']);
			if (empty($geocoder['name'])) {
				//百度地图没解析到，那就试试天地图
				list($tlng, $tlat) = Coordinate::bd09ToWgs84($location2['lng'], $location2['lat']);
				$geocoder = $this->geocoder_tianditu($tlng, $tlat);
			}
			$location2['name'] = $streetName?:$geocoder['name'];
			$location2['street'] = $geocoder['street'];
            /*$location2['street'] = $geocoder['street'];*/
			$location2['city'] = $this->formart_city_name($geocoder['city']);
            /*$location2['city_id'] =$geocoder['city'];
            $location2['province_id'] =$geocoder['province'];*/
            $location2['district'] =$geocoder['district'];
            $location2['province'] =$geocoder['province'];
		}
		exit(json_encode($location2));
	}

	public function gpsToBaidu(){
		if(empty($_POST)){
			$input_post = file_get_contents('php://input');
			$_POST = json_decode($input_post,true);
		}
		header("Content-type: application/json");
		import('@.ORG.longlat');
		$longlat_class = new longlat();
		$location2 = $longlat_class->gpsToBaidu($_POST['lat'],$_POST['lng']);//转换GPS坐标到百度坐标
		if($_POST['geocoder']){
			$geocoder = $this->geocoder($location2['lng'],$location2['lat']);
			$location2['name'] = $geocoder['name'];
			$location2['city'] = $this->formart_city_name($geocoder['city']);
		}

		exit(json_encode($location2));
	}
	public function formart_city_name($v){
		$long = strlen($v);
		if ($long >= 7) {
			$v = str_replace('省', '', $v);
			$v = str_replace('市', '', $v);
			$v = str_replace('地区', '', $v);
			$v = str_replace('特别行政区', '', $v);
			$v = str_replace('特別行政區', '', $v);
			$v = str_replace('蒙古自治州', '', $v);
			$v = str_replace('回族自治州', '', $v);
			$v = str_replace('柯尔克孜自治州', '', $v);
			$v = str_replace('哈萨克自治州', '', $v);
			$v = str_replace('土家族苗族自治州', '', $v);
			$v = str_replace('藏族羌族自治州', '', $v);
			$v = str_replace('傣族自治州', '', $v);
			$v = str_replace('布依族苗族自治州', '', $v);
			$v = str_replace('苗族侗族自治州', '', $v);
			$v = str_replace('壮族苗族自治州', '', $v);
			$v = str_replace('澳门', '澳門', $v);
			$v = str_replace('朝鲜族自治州', '', $v);
			$v = str_replace('哈尼族彝族自治州','',$v);
			$v = str_replace('傣族景颇族自治州','',$v);
			$v = str_replace('藏族自治州','',$v);
			$v = str_replace('彝族自治州','',$v);
			$v = str_replace('白族自治州','',$v);
			$v = str_replace('傈僳族自治州','',$v);
		}
		
		return $v;
	}
	public function geocoder($lng,$lat){
		
		$url = 'http://api.map.baidu.com/reverse_geocoding/v3/?location='.$lat.','.$lng.'&output=json&extensions_poi=1&ak=' . $this->config['baidu_map_ak'] . (C('config.map_config') == 'google' ? '&coordtype=wgs84ll' : '');
		import('ORG.Net.Http');
		$http = new Http();
		$result = $http->curlGet($url);
		fdump($result,"dengyuanhui009090",1);
		if($result){
			$result = json_decode($result,true);
			if(!empty($result['result']['pois'])){
				return array(
					'name' => $result['result']['pois'][0]['name'],
					'city' => $result['result']['addressComponent']['city'],
					'province' => $result['result']['addressComponent']['province'],
					'district' => $result['result']['addressComponent']['district'],
					'street' => $result['result']['addressComponent']['street'] ? $result['result']['addressComponent']['street'].($result['result']['addressComponent']['street_number'] ? $result['result']['addressComponent']['street_number'] : '') : '',
				);
			}else{
				return array(
					'name' => $result['result']['addressComponent']['street'].$result['result']['addressComponent']['street_number'],
					'city' => $result['result']['addressComponent']['city'],
					'province' => $result['result']['addressComponent']['province'],
					'district' => $result['result']['addressComponent']['district'],
					'street' => $result['result']['addressComponent']['street'] ? $result['result']['addressComponent']['street'].($result['result']['addressComponent']['street_number'] ? $result['result']['addressComponent']['street_number'] : '') : '',
				);
			}
		}else{
			return array();
		}
	}

	//天地图地图地址解析
	public function geocoder_tianditu($lng, $lat)
	{
		if (empty(C('config.tianditu_key'))) {
			return [];
		}

		$result = Tianditu::reverseGeocoder($lng, $lat, C('config.tianditu_key'));
		$addressComponent = $result['result']['addressComponent'] ?? [];
		if ($addressComponent) {
			//解析成功
			return [
				'name' => $addressComponent['address'],
				'city' => $addressComponent['city'],
				'province' => $addressComponent['province'],
				'district' => $addressComponent['county'],
				'street' => $addressComponent['town'],
			];
		} else {
			return [];
		}
	}

    //暂时停用
	public function geocoder_google($lng,$lat){
	    $key =C('config.google_map_ak');
        $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?location='.$lat.','.$lng.'&radius=500&key='.$key;
        echo $url;
        import('ORG.Net.Http');
        $http = new Http();
        $result = $http->curlGet($url);
        if($result) {
            $result = json_decode($result, true);
            return $result;
        }
    }

	public function invokeBaiduLocationForJs()
	{
		$url = htmlspecialchars_decode(urldecode($_GET['url']));
		$referer = $_SERVER['HTTP_REFERER'];
        $site_url = D('Config')->where(['name'=>'site_url'])->getField('value');
		if (empty($url) || empty($referer) || strpos($referer, $site_url) === false) {
			$this->ajaxReturn(['error_no' => 1, 'error_msg' => 'Forbidden']);
		}
		if (strpos($url, 'https://api.map.baidu.com') !== 0) {
			$this->ajaxReturn(['error_no' => 1, 'error_msg' => 'Forbidden']);
		}
		import('ORG.Net.Http');
		$http = new Http();
		$result = $http->curlGet($url);
		header('Content-Type:text/javascript;charset=utf-8');
		exit($result);
	}
}
?>