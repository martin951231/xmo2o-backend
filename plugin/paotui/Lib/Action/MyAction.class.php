<?php
/*
 * 个人信息
 *
 */
class MyAction extends BaseAction{
	//	用户地址列表
	public function adress(){
		$store_id = I('store_id', 0);
		if (!$this->_uid) {
			returnCode('20046009');
		}
		$adress_list = plugin_model('user_adress')->get_adress_list($this->_uid);

		$lat = 0;
		$lng = 0;
		$delivery_radius = 0;
		if ($store_id && ($store = D('Merchant_store')->field('long, lat')->where(array('store_id' => $store_id))->find())) {
			$lat = $store['lat'];
			$lng = $store['long'];
			if ($store_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $store_id))->find()) {
				$delivery_radius = $store_shop['delivery_radius'];
				$store_shop['delivery_range_polygon'] = substr($store_shop['delivery_range_polygon'], 9, strlen($store_shop['delivery_range_polygon']) - 11);
				$lngLatData = explode(',', $store_shop['delivery_range_polygon']);
				array_pop($lngLatData);
				$lngLats = array();
				foreach ($lngLatData as $lnglat) {
				    $lng_lat = explode(' ', $lnglat);
				    $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
				}
				$store_shop['delivery_range_polygon'] = $lngLats ? array($lngLats) : '';
			}
		}

		if($adress_list){
			//非百度下的经纬度
			if($_POST['longlatType']){
                loadSystemOrg('longlat');
				$longlatClass = new longlat();
			}
			foreach($adress_list as $v){
				$is_deliver = true;
				$distance = PHP_INT_SIZE;
				if ($store_shop['delivery_range_type'] == 0) {
				    if ($lat != 0 && $lng != 0) {
				        $distance = getDistance($v['latitude'], $v['longitude'], $lat, $lng);
				        $distance = $distance / 1000;
				        if ($distance <= $delivery_radius) {
				            $is_deliver = true;
				        } else {
							$is_deliver = false;
						}
				    }
				} else {
				    if ($store_shop['delivery_range_polygon']) {
				        if (isPtInPoly($v['longitude'], $v['latitude'], $store_shop['delivery_range_polygon'])) {
				            $is_deliver = true;
				        } else {
							$is_deliver = false;
						}
				    }
				}
				$formartLng = 0;
				$formartLat = 0;
				if($_POST['longlatType']){
					$longlat = $longlatClass->baiduToGcj02($v['latitude'],$v['longitude']);
					$formartLng = $longlat['lng'];
					$formartLat = $longlat['lat'];
				}
				if(!$this->config['open_multilingual']){
					$v['show_address'] = $v['area_txt'] . $v['adress'] . $v['detail'];
				}else if($this->config['country_code'] == 'au'){
					$v['show_address'] = trim(($v['detail'] ? $v['detail']  . ', ' : '') . $v['adress']  . ', ' . $v['area_txt'] . ' ' . $v['province_txt'] . ' ' . $v['zip_code']);
				}else{
					$v['show_address'] = trim(($v['detail'] ? $v['detail']  . ', ' : '') . $v['adress']  . ', ' . $v['area_txt'] . ' ' . $v['city_txt']);
				}
				$arr[]	=	array(
					'adress_id'	=>	$v['adress_id'],
					'phone'		=>	$v['phone'],
					'phone_country_type' =>	$v['phone_country_type'],
					'phone_show' =>	$v['phone_country_type'] ? '+'.$v['phone_country_type'].' '.$v['phone'] : $v['phone'],
					'province'	=>	$v['province'],
					'city'		=>	$v['city'],
					'area'		=>	$v['area'],
					'province_txt'	=>	$v['province_txt'],
					'city_txt'	=>	$v['city_txt'],
					'area_txt'	=>	$v['area_txt'],
					'adress'	=>	$v['adress'],
					'address_name'	=>	$v['adress'],
					'detail'	=>	$v['detail'],
					'show_address'	=>	$v['show_address'],
					'defaults'	=>	$v['default'],
					'name'		=>	$v['name'],
					'zipcode'	=>	$v['zipcode'],
					'lng'		=>	$v['longitude'],
					'lat'		=>	$v['latitude'],
					'sex'		=>	$v['sex'],
					'is_deliver' => $is_deliver,
					'distance' => $distance,
					'formartLng' => $formartLng,
					'formartLat' => $formartLat,
				);
				$distances[] = $distance;
				array_multisort($distances, SORT_ASC, $arr);
			}
		}else{
			$arr	=	array();
		}
		returnCode(0,$arr);
	}
}
?>