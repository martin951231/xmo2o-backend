<?php
/* 
 * 经纬度转换
 * 
 */
namespace map;
use net\Http;

class longLat{
	private $PI = 3.14159265358979324;
	private $x_pi = 0;
	private $a = 6378245.0;
    private $ee = 0.00669342162296594323;

	public function __construct()
	{
		$this->x_pi = 3.14159265358979324 * 3000.0 / 180.0;
	}
	
	/**
    * 判断是否在国内，不在国内则不做偏移
    * @param $lng
    * @param $lat
    * @returns {boolean}
    */
    private function out_of_china($lng, $lat) { 
        return ($lng < 72.004 || $lng > 137.8347) || (($lat < 0.8293 || $lat > 55.8271) || false);
    }
	
	private function transformlatwgs($lng, $lat) {
        $ret = -100.0 + 2.0 * $lng + 3.0 * $lat + 0.2 * $lat * $lat + 0.1 * $lng * $lat + 0.2 * sqrt(abs($lng));
        $ret += (20.0 * sin(6.0 * $lng * $this->PI) + 20.0 * sin(2.0 * $lng * $this->PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($lat * $this->PI) + 40.0 * sin($lat / 3.0 * $this->PI)) * 2.0 / 3.0;
        $ret += (160.0 * sin($lat / 12.0 * $this->PI) + 320 * sin($lat * $this->PI / 30.0)) * 2.0 / 3.0;
        return $ret;
    }
    private function transformlngwgs($lng, $lat) {
        $ret = 300.0 + $lng + 2.0 * $lat + 0.1 * $lng * $lng + 0.1 * $lng * $lat + 0.1 * sqrt(abs($lng));
        $ret += (20.0 * sin(6.0 * $lng * $this->PI) + 20.0 * sin(2.0 * $lng * $this->PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($lng * $this->PI) + 40.0 * sin($lng / 3.0 * $this->PI)) * 2.0 / 3.0;
        $ret += (150.0 * sin($lng / 12.0 * $this->PI) + 300.0 * sin($lng / 30.0 * $this->PI)) * 2.0 / 3.0;
        return $ret;
	}
	
	 /**
     * GCJ02 转换为 WGS84 (高德转北斗)
     * @param lng
     * @param lat
     * @return array(lng, lat);
     */
    public function gcj02towgs84($lng, $lat) {
        if ($this->out_of_china($lng, $lat)) {
            return array($lng, $lat);
        } else {
            $dlat = $this->transformlatwgs($lng - 105.0, $lat - 35.0);
            $dlng = $this->transformlngwgs($lng - 105.0, $lat - 35.0);
            $radlat = $lat / 180.0 * $this->PI;
            $magic = sin($radlat);
            $magic = 1 - $this->ee * $magic * $magic;
            $sqrtmagic = sqrt($magic);
            $dlat = ($dlat * 180.0) / (($this->a * (1 - $this->ee)) / ($magic * $sqrtmagic) * $this->PI);
            $dlng = ($dlng * 180.0) / ($this->a / $sqrtmagic * cos($radlat) * $this->PI);
            $mglat = $lat + $dlat;
            $mglng = $lng + $dlng;
            return array($lng * 2 - $mglng, $lat * 2 - $mglat);
        }
    }
	
	/**
	   * http://lbsyun.baidu.com/index.php?title=webapi/guide/changeposition
	   *
       * @param [String] $lat 坐标的纬度
       * @param [String] $lng 坐标的经度
       * @param [Int]    $fromType 坐标来源   仅百度地图正常调用时正常
	   *	源坐标类型：
	   *	1：GPS设备获取的角度坐标，wgs84坐标;
	   *	2：GPS获取的米制坐标、sogou地图所用坐标;
	   *	3：google地图、soso地图、aliyun地图、mapabc地图和amap地图所用坐标，国测局（gcj02）坐标;
	   *	4：3中列表地图坐标对应的米制坐标;
	   *	5：百度地图采用的经纬度坐标;
	   *	6：百度地图采用的米制坐标;
	   *	7：mapbar地图坐标;
	   *	8：51地图坐标
       * @param [Int]    $toType 5：表示目标坐标类型为百度经纬度坐标（BD-09LL）,1: 代表GPS设备获取的坐标，即WGS-84坐标系
       * @return [Array] 返回记录纬度经度的数组
	*/
	function toBaidu($wgsLat, $wgsLon, $fromType = 3, $toType = 5){
		if(cfg('map_config') == 'google'){
			return array('lat' => $wgsLat,'lng' => $wgsLon);
			/* if($fromType == 3){
				$lnglat = $this->gcj02towgs84($wgsLon,$wgsLat);
				return array('lat' => $lnglat[1],'lng' => $lnglat[0]);
			}else{
				return array('lat' => $wgsLat,'lng' => $wgsLon);
			} */
		}
		if($fromType == 5 && $toType != 1){
			return array('lat' => $wgsLat,'lng' => $wgsLon);
		}
	    $url = 'http://api.map.baidu.com/geoconv/v1/?coords=' . $wgsLon. ',' . $wgsLat. '&from='.$fromType.'&to=5&ak=' . cfg('baidu_map_ak') . '&output=json';
	    $http = new Http();
	    $result = $http->curlGet($url);
	    if ($result) {
	        $result = json_decode($result, true);
	        if ($result['status'] == 0) {
	            return array('lat' => $result['result'][0]['y'],'lng' => $result['result'][0]['x']);
	        }else{
				//fromType 1:wgs84   3:gcj02
				if ($fromType == 1) {
					list($lng, $lat) = Coordinate::wgs84ToBd09($wgsLon, $wgsLat);
					return array('lat' => $lat, 'lng' => $lng);
				} else if ($fromType == 3) {
					list($lng, $lat) = Coordinate::gcj02ToBd09($wgsLon, $wgsLat);
					return array('lat' => $lat, 'lng' => $lng);
				}
			}
	    }
	    
		return false;
	}

	/**
	 * 高德坐标(GCJ-02)转百度坐标(BD-09)
	 * @param float $lng 高德经度
	 * @param float $lat 高德纬度
	 * @return array 百度坐标系 [lng, lat]
	 */
	function gcj02ToBd09($lng, $lat)
	{
		// 坐标系转换参数
		$z = sqrt($lng * $lng + $lat * $lat) + 0.00002 * sin($lat * $this->x_pi);
		$theta = atan2($lat, $lng) + 0.000003 * cos($lng * $this->x_pi);

		// 计算百度坐标
		$bd_lng = $z * cos($theta) + 0.0065;
		$bd_lat = $z * sin($theta) + 0.006;

		return [$bd_lng, $bd_lat];
	}
	
	/**
       * 腾讯地图坐标转百度地图坐标
       * @param [String] $lat 腾讯地图坐标的纬度
       * @param [String] $lng 腾讯地图坐标的经度
       * @return [Array] 返回记录纬度经度的数组
	*/
	function gpsToBaidu($wgsLat, $wgsLon)
	{
		if(cfg('map_config') == 'google'){
			return array('lat' => $wgsLat,'lng' => $wgsLon);
		}
	    $url = 'http://api.map.baidu.com/geoconv/v1/?coords=' . $wgsLon. ',' . $wgsLat. '&from=3&to=5&ak=' . cfg('baidu_map_ak') . '&output=json';
	    $http = new Http();
	    $result = $http->curlGet($url);
	    if ($result) {
	        $result = json_decode($result, true);
	        if ($result['status'] == 0) {
	            return array('lat' => $result['result'][0]['y'],'lng' => $result['result'][0]['x']);
	        }
	    }
	    
		if ($this->outOfChina($wgsLat, $wgsLon)) {
			return $this->bd_encrypt($wgsLat, $wgsLon);
		}
		$d = $this->delta($wgsLat, $wgsLon);
		return $this->bd_encrypt($wgsLat + $d['lat'], $wgsLon + $d['lng']);
		
		
		
		$x_pi = 3.14159265358979324 * 3000.0 / 180.0;
		$x = $lng;
		$y = $lat;
		$z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $x_pi);
		$theta = atan2($y, $x) + 0.000003 * cos($x * $x_pi);
		$lng = $z * cos($theta) + 0.0065;
		$lat = $z * sin($theta) + 0.006;
		return array('lng'=>$lng,'lat'=>$lat);
	}
	
	private function outOfChina($lat, $lon)
	{
		if ($lon < 72.004 || $lon > 137.8347)
			return TRUE;
		if ($lat < 0.8293 || $lat > 55.8271)
			return TRUE;
		return FALSE;
	}

	private function delta($lat, $lon)
	{
		// Krasovsky 1940
		//
		// a = 6378245.0, 1/f = 298.3
		// b = a * (1 - f)
		// ee = (a^2 - b^2) / a^2;
		$a = 6378245.0;//  a: 卫星椭球坐标投影到平面地图坐标系的投影因子。
		$ee = 0.00669342162296594323;//  ee: 椭球的偏心率。
		$dLat = $this->transformLat($lon - 105.0, $lat - 35.0);
		$dLon = $this->transformLon($lon - 105.0, $lat - 35.0);
		$radLat = $lat / 180.0 * $this->PI;
		$magic = sin($radLat);
		$magic = 1 - $ee * $magic * $magic;
		$sqrtMagic = sqrt($magic);
		$dLat = ($dLat * 180.0) / (($a * (1 - $ee)) / ($magic * $sqrtMagic) * $this->PI);
		$dLon = ($dLon * 180.0) / ($a / $sqrtMagic * cos($radLat) * $this->PI);
		return array('lat' => $dLat, 'lng' => $dLon);
	}

	private function transformLat($x, $y) {
		$ret = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y + 0.2 * sqrt(abs($x));
		$ret += (20.0 * sin(6.0 * $x * $this->PI) + 20.0 * sin(2.0 * $x * $this->PI)) * 2.0 / 3.0;
		$ret += (20.0 * sin($y * $this->PI) + 40.0 * sin($y / 3.0 * $this->PI)) * 2.0 / 3.0;
		$ret += (160.0 * sin($y / 12.0 * $this->PI) + 320 * sin($y * $this->PI / 30.0)) * 2.0 / 3.0;
		return $ret;
	}

	private function transformLon($x, $y) {
		$ret = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1 * sqrt(abs($x));
		$ret += (20.0 * sin(6.0 * $x * $this->PI) + 20.0 * sin(2.0 * $x * $this->PI)) * 2.0 / 3.0;
		$ret += (20.0 * sin($x * $this->PI) + 40.0 * sin($x / 3.0 * $this->PI)) * 2.0 / 3.0;
		$ret += (150.0 * sin($x / 12.0 * $this->PI) + 300.0 * sin($x / 30.0 * $this->PI)) * 2.0 / 3.0;
		return $ret;
	}
	//GCJ-02 to BD-09
	public function bd_encrypt($gcjLat, $gcjLon) {
		$x = $gcjLon; $y = $gcjLat;
		$z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $this->x_pi);
		$theta = atan2($y, $x) + 0.000003 * cos($x * $this->x_pi);
		$bdLon = $z * cos($theta) + 0.0065;
		$bdLat = $z * sin($theta) + 0.006;
		return array('lat' => $bdLat,'lng' => $bdLon);
	}
	
	
	/**
       * 百度地图坐标转腾讯地图等火星坐标
       * @param [String] $lat 百度地图坐标的纬度
       * @param [String] $lng 百度地图坐标的经度
       * @return [Array] 返回记录纬度经度的数组
	*/
	function baiduToGcj02($lat,$lng){
		if(cfg('map_config') == 'google'){
			return array('lng'=>$lng,'lat'=>$lat);
		}
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lng - 0.0065;
        $y = $lat - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
        $lng = $z * cos($theta);
        $lat = $z * sin($theta);
        return array('lng'=>$lng,'lat'=>$lat);
	}
	/* function baiduToGcj02($lat,$lng){
		$qqMapSecret = 'Y75BZ-N3KA5-CJQIF-QJ7QQ-UGDBE-FRBJM';
		import('ORG.Net.Http');
		$http = new Http();
		$return = Http::curlGet('http://apis.map.qq.com/ws/coord/v1/translate?locations='.$lat.','.$lng.'&type=3&key='.$qqMapSecret);
		$returnArr = json_decode($return,true);
		file_put_contents('./runtime/baidu.php',var_export($returnArr,true));
		if($returnArr && $returnArr['status'] == 0){
			return array('lng'=>$returnArr['locations'][0]['lng'],'lat'=>$returnArr['locations'][0]['lat']);
		}else{
			return false;
		}
	} */
	//百度地图坐标计算
	function rad($d){  
		   return $d * 3.1415926535898 / 180.0;  
	}
	/**
       * 百度地图坐标计算两点之间的距离
       * @param [String] $lat1 A点的纬度
       * @param [String] $lng1 A点的经度
       * @param [String] $lat2 B点的纬度
       * @param [String] $lng2 B点的经度
       * @return [String] 两点坐标间的距离，输出单位为米
	*/
	function GetDistance($lat1,$lng1,$lat2,$lng2){
	   $EARTH_RADIUS = 6378.137;//地球的半径
	   $radLat1 = $this->rad($lat1);   
	   $radLat2 = $this->rad($lat2);  
	   $a = $radLat1 - $radLat2;  
	   $b = $this->rad($lng1) - $this->rad($lng2);  
	   $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));  
	   $s = $s *$EARTH_RADIUS;  
	   $s = round($s * 10000) / 10000;
	   $s=$s*1000;
	   return ceil($s);  
	}
	
	/**
       * 标记大概的距离，做出友好的距离提示
       * @param [$number] 距离数量
       * @return[String] 距离提示
	*/
	function mToKm($range){
		$return = array();
		if($range < 100){
			$return['num'] = $range;
			$return['unit'] = 'm';
			$return['cunit'] = '米';
		}elseif($range < 1000){
			$return['num'] = round($range/1000,1);
			$return['unit'] = 'km';
			$return['cunit'] = '千米';
		}else if($range<5000){
			$return['num'] = round($range/1000,2);
			$return['unit'] = 'km';
			$return['cunit'] = '千米';
		}else if($range<10000){
			$return['num'] = round($range/1000,1);
			$return['unit'] = 'km';
			$return['cunit'] = '千米';
		}else{
			$return['num'] = floor($range/1000);
			$return['unit'] = 'km';
			$return['cunit'] = '千米';
		}
		return $return;
	}
	function GetDistanceMToKm($lat1,$lng1,$lat2,$lng2,$is_chinese = false){
		$mToKm = $this->mToKm($this->GetDistance($lat1,$lng1,$lat2,$lng2));
		return $is_chinese ? $mToKm['num'].$mToKm['cunit'] : $mToKm['num'].$mToKm['unit'];
	}
	
	public function getRidingDistance($frmLat, $frmLng, $orgLat, $orgLng)
	{
		$http = new Http();
		if(cfg('map_config') == 'google'){
			$url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . $frmLat. ',' . $frmLng. '&destinations=' . $orgLat . ',' . $orgLng . '&mode=driving&key='.cfg('google_map_ak').'&language=en';
			$result = $http->curlGet($url);
			$newLatLong = -1;
			if ($result) {
				$result = json_decode($result, true);
				fdump($result,'getRidingDistance_google_result');
				if ($result['status'] == 'OK' && count($result['rows']) > 0) {
					$newLatLong = floatval($result['rows'][0]['elements'][0]['distance']['value']);
				}
			}
		}else{
			$url = 'http://api.map.baidu.com/routematrix/v2/riding?origins=' . $frmLat. ',' . $frmLng. '&destinations=' . $orgLat . ',' . $orgLng . '&ak=' . cfg('baidu_map_ak') . '&output=json';
			$result = $http->curlGet($url);
			$newLatLong = -1;
			if ($result) {
				$result = json_decode($result, true);
				fdump([$url,$result],'getRidingDistance_result');
				if ($result['status'] == 0) {
					$newLatLong = floatval($result['result'][0]['distance']['value']);
				}
			}
			//如果百度地图没获取到距离，则看看天地图可配置了,天地图配置则按照直线距离算
			if ($newLatLong == -1 && cfg('tianditu_key')) {
				list($frmLng, $frmLat) = Coordinate::bd09ToWgs84($frmLng, $frmLat);
				list($orgLng, $orgLat) = Coordinate::bd09ToWgs84($orgLng, $orgLat);
				$result = Tianditu::getRidingDistance($frmLng . ',' . $frmLat, $orgLng . ',' . $orgLat, cfg('tianditu_key'));
				$newLatLong =  isset($result['distance']) ? intval($result['distance'] * 1000) : -1;
			}
		}
	    
	    return $newLatLong;
	}
	
	public function getWalkInfo($frmLat, $frmLng, $orgLat, $orgLng){
		$http = new Http();
		
		$url = 'http://api.map.baidu.com/directionlite/v1/walking?origin=' . $frmLat. ',' . $frmLng. '&destination=' . $orgLat . ',' . $orgLng . '&ak=' . cfg('baidu_map_ak') . '&output=json';
		$result = $http->curlGet($url);
		$infoArr = [];
		if ($result) {
			$result = json_decode($result, true);
			fdump_api([$url,$result],'longLat/getWalkInfo');
			if ($result['status'] == 0) {
				$infoArr = ['distance'=>floatval($result['result']['routes'][0]['distance']), 'duration'=>floatval($result['result']['routes'][0]['duration'])];
			} else if (cfg('tianditu_key')) {
				//配置了天地图，读取天地图骑行距离和时间
				list($frmLng, $frmLat) = Coordinate::bd09ToWgs84($frmLng, $frmLat);
				list($orgLng, $orgLat) = Coordinate::bd09ToWgs84($orgLng, $orgLat);
				$result = Tianditu::getRidingDistance($frmLng . ',' . $frmLat, $orgLng . ',' . $orgLat, cfg('tianditu_key'));
				$infoArr = ['distance' => $result['distance'] * 1000, 'duration' => $result['duration']];
			}
		}
		
		return $infoArr;
	}
	
	public function getDriveInfo($frmLat, $frmLng, $orgLat, $orgLng){
		$http = new Http();
		
		$url = 'http://api.map.baidu.com/directionlite/v1/driving?origin=' . $frmLat. ',' . $frmLng. '&destination=' . $orgLat . ',' . $orgLng . '&ak=' . cfg('baidu_map_ak') . '&output=json';
		$result = $http->curlGet($url);
		$infoArr = [];
		if ($result) {
			$result = json_decode($result, true);
			fdump_api([$url,$result],'longLat/getDriveInfo');
			if ($result['status'] == 0) {
				$infoArr = ['distance'=>floatval($result['result']['routes'][0]['distance']), 'duration'=>floatval($result['result']['routes'][0]['duration'])];
			} else if (cfg('tianditu_key')) {
				//配置了天地图，读取天地图驾车距离和时间
				list($frmLng, $frmLat) = Coordinate::bd09ToWgs84($frmLng, $frmLat);
				list($orgLng, $orgLat) = Coordinate::bd09ToWgs84($orgLng, $orgLat);
				$result = Tianditu::getDriveDistance($frmLng . ',' . $frmLat, $orgLng . ',' . $orgLat, cfg('tianditu_key'));
				$infoArr = ['distance' => $result['distance'] * 1000, 'duration' => $result['duration']];
			}
		}
		
		return $infoArr;
	}
	
	public function getAddressByPlaceId($place_id){
		$detail_url = 'https://maps.googleapis.com/maps/api/place/details/json?placeid='.$place_id.'&key='.C('config.google_map_ak').'&language=en';
		$http = new Http();
		$detail_result = $http->curlGet($detail_url);
		$detail_result = json_decode($detail_result, true);
		$result = $detail_result['result'];
		
		$result['address_info'] = $this->format_google_address($result['address_components']);
		return $result;
	}
	
	public function format_google_address($address_components){
		$address_info = array();
		
		//先循环得到国家，一些国家的地址需要特殊处理才能符合省市区的概念
		foreach($address_components as $value){
			if($value['types'][0] == 'country'){
				$address_info['country'] = $value['short_name'];
			}
		}
		
		if($address_info['country'] == 'NZ'){	//新西兰
			foreach($address_components as $value){
				if($value['types'][0] == 'street_number' || $value['types'][0] == 'st_number'){
					$address_info['street_number'] = $value['short_name'];
				}else if(in_array('route', $value['types'])){
					$address_info['street'] = $value['short_name'];
				}else if(in_array('sublocality', $value['types'])){
					$address_info['area'] = $value['short_name'];
				}else if(in_array('locality', $value['types'])){
					$address_info['city'] = $value['short_name'];
				}else if(in_array('administrative_area_level_1', $value['types'])){
					$address_info['province'] = $value['short_name'];
				}else if(in_array('postal_code', $value['types'])){
					$address_info['post_code'] = $value['short_name'];
				}
			}
		}else{
			foreach($address_components as $value){
				if($value['types'][0] == 'street_number' || $value['types'][0] == 'st_number'){
					$address_info['street_number'] = $value['short_name'];
				}else if($value['types'][0] == 'route'){
					$address_info['street'] = $value['short_name'];
				}else if($value['types'][0] == 'locality'){
					$address_info['area'] = $value['short_name'];
				}else if($value['types'][0] == 'administrative_area_level_2'){
					$address_info['city'] = $value['short_name'];
				}else if($value['types'][0] == 'administrative_area_level_1'){
					$address_info['province'] = $value['short_name'];
				}else if($value['types'][0] == 'postal_code'){
					$address_info['post_code'] = $value['short_name'];
				}
			}
		}
		
		//香港如果解析成国家，则特殊处理
        if (isset($address_info['country']) && $address_info['country'] == 'HK') {
            isset($address_info['province']) && $address_info['area'] = $address_info['province'];
            $address_info['city'] = $address_info['country'];
            $address_info['province'] = $address_info['country'];
            unset($address_info['country']);
		}
		
		//意大利国家的米兰缩写处理
		if($address_info['country'] == 'IT' && $address_info['city'] == 'MI'){
			$address_info['city'] = 'Milano';
		}
		
		$address_info['street_address'] = trim($address_info['street_number'] . ' ' . $address_info['street']);
		$address_info['short_address'] = trim(ltrim(trim($address_info['street_number'] . ' ' . $address_info['street'] .', '. $address_info['area']),','));
		return $address_info;
	}
}
?>