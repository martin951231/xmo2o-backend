<?php
class area{
	public function get_area_by_areaId($area_id, $is_open = true, $cat_url = '', $type = 'category')
    {
        $condition_area['area_id'] = $area_id;
        if ($is_open) {
            $condition_area['is_open'] = '1';
        }
        $now_area = M('Area')->field(true)->where($condition_area)->find();
        if (!empty($now_area)) {
            $now_area['url'] = $this->get_area_url($now_area, $cat_url, $type);
        }
        return $now_area;
    }
	
	/* 得到区域的URL */
    protected function get_area_url($area, $cat_url = '', $type = 'category')
    {
        if (in_array($type, array('category', 'meal'))) {
            if (empty($cat_url)) {
                return C('config.site_url') . '/' . $type . '/all/' . $area['area_url'];
            } else {
                return C('config.site_url') . '/' . $type . '/' . $cat_url . '/' . $area['area_url'];
            }
        } else if ($type == 'activity') {
            if (empty($cat_url)) {
                return C('config.site_url') . '/' . $type . '/all/' . $area['area_url'];
            } else {
                return C('config.site_url') . '/' . $type . '/' . $cat_url . '/' . $area['area_url'];
            }
        } else if ($type == 'appoint') {
            if (empty($cat_url)) {
                return C('config.site_url') . '/' . $type . '/category/all/' . $area['area_url'];
            } else {
                return C('config.site_url') . '/' . $type . '/category/' . $cat_url . '/' . $area['area_url'];
            }
        }
    }


    public function cityMatching($lat, $lng, $returnPois = false)
    {
        import('ORG.Net.Http');
        $http = new Http();

        $map_config = C('config.map_config');
        if(!$map_config || $map_config == 'baidu'){
            $url = 'http://api.map.baidu.com/geocoder/v2/?output=json&pois=1&ak=4c1bb2055e24296bbaef36574877b4e2&location=' . $lat . ',' . $lng;

            $result = $http->curlGet($url);
            $result = json_decode($result, true);
            $area['area_name'] = $result['result']['addressComponent']['district'];
            $area['city_name'] = $result['result']['addressComponent']['city'];
            $area['province_name'] = $result['result']['addressComponent']['province'];
            foreach ($area as &$v) {
                $long = strlen($v);
                if ($long >= 7) {
                    $v = mb_trim($v,L_('省'));
                    $v = mb_trim($v,L_('市'));
                    $v = mb_trim($v,L_('地区'));
                    $v = str_replace(L_('特别行政区'), '', $v);
                    $v = str_replace(L_('特別行政區'), '', $v);
                    $v = str_replace(L_('蒙古自治州'), '', $v);
                    $v = str_replace(L_('回族自治州'), '', $v);
                    $v = str_replace(L_('柯尔克孜自治州'), '', $v);
                    $v = str_replace(L_('哈萨克自治州'), '', $v);
                    $v = str_replace(L_('土家族苗族自治州'), '', $v);
                    $v = str_replace(L_('藏族羌族自治州'), '', $v);
                    $v = str_replace(L_('傣族自治州'), '', $v);
                    $v = str_replace(L_('布依族苗族自治州'), '', $v);
                    $v = str_replace(L_('苗族侗族自治州'), '', $v);
                    $v = str_replace(L_('壮族苗族自治州'), '', $v);
                    $v = str_replace(L_('澳门'), L_('澳門'), $v);
                    $v = str_replace(L_('朝鲜族自治州'), '', $v);
                    $v = str_replace(L_('哈尼族彝族自治州'), '', $v);
                    $v = str_replace(L_('傣族景颇族自治州'), '', $v);
                    $v = str_replace(L_('藏族自治州'), '', $v);
                    $v = str_replace(L_('彝族自治州'), '', $v);
                    $v = str_replace(L_('白族自治州'), '', $v);
                    $v = str_replace(L_('傈僳族自治州'), '', $v);
                }
            }

            if($area['city_name']){
                $now_city = M('Area')->where(array('area_name' => $area['city_name']))->find();
                $now_city['area_info']['city_name'] = $now_city['area_name'];
                $now_city['area_info']['city_id'] = $now_city['area_id'];
            }

            if($area['area_name']){
                $find_area_where = array('area_name' => array('like', '%' . $area['area_name'] . '%'));
                if($now_city['area_info']['city_id']){
                    $find_area_where['area_pid'] = $now_city['area_info']['city_id'];
                }
                $area_info = M('Area')->where($find_area_where)->find();
                if($area_info){
                    $now_city['area_info']['area_id'] = $area_info['area_id'];
                    $now_city['area_info']['area_name'] = $area_info['area_name'];
                    $now_city['area_info']['area_is_open'] = $area_info['is_open'];
                }
            }

            $now_city['area_info']['province_id'] = $now_city['area_pid'];
            $now_city['area_info']['province_name'] = $area['province_name'];

            $now_city['pois'] = array();

            if ($result['result']['pois']){
                $now_city['address_name'] = $result['result']['pois'][0]['name'];
                $now_city['address_addr'] = $result['result']['pois'][0]['addr'];
                if ($returnPois) {
                    foreach ($result['result']['pois'] as $key => $value) {
                        if ($key != 0) {
                            $now_city['pois'][] = array(
                                'name' => $value['name'],
                                'address_name' => $value['name'],
                                'addr' => $value['addr'],
                                'distance' => $value['distance'],
                                'lat' => $value['point']['y'],
                                'lng' => $value['point']['x'],
                            );
                        }
                    }
                }
            } else {
                $now_city['address_name'] = $result['result']['addressComponent']['street'];
                $now_city['address_addr'] = $result['result']['addressComponent']['street'];
            }
        }else if($map_config == 'google'){
            // $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $lng.'&location_type=ROOFTOP&result_type=street_address&key='.C('config.google_map_ak').'&language=en';
            // $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $lng.'&location_type=ROOFTOP&result_type=point_of_interest&key='.C('config.google_map_ak').'&language=en';
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $lng.'&key='.C('config.google_map_ak').'&language=en';
            $geocode_result = $http->curlGet($url);
            $geocode_result = json_decode($geocode_result, true);

            fdump($url,'cityMatching_result',true);
            fdump($geocode_result,'cityMatching_result',true);
            if ($geocode_result['results']) {
                //用第一个位置检索城市信息
                import('@.ORG.longlat');
                $longlat_class = new longlat();
                $result = $geocode_result['results'][0];
                $result['address_info'] = $longlat_class->format_google_address($geocode_result['results'][0]['address_components']);

                $area['area_name'] = $result['address_info']['area'];
                $area['city_name'] = $result['address_info']['city'];
                $area['province_name'] = $result['address_info']['province'];

                //判断城市名
                if($area['city_name']){
                    $now_city = M('Area')->where(array('area_name' => $area['city_name']))->find();
                    $now_city['area_info']['city_name'] = $now_city['area_name'];
                    $now_city['area_info']['city_id'] = $now_city['area_id'];
                }

                $now_area = array();
                //判断区域邮编
                if($result['address_info']['post_code']){
                    $find_area_where = ['zip_code' => $result['address_info']['post_code']];
                    $now_area = M('Area')->where($find_area_where)->find();
                    if($now_area){
                        $now_city['area_info']['area_id'] = $now_area['area_id'];
                        $now_city['area_info']['area_name'] = $now_area['area_name'];
                        $now_city['area_info']['area_is_open'] = $now_area['is_open'];
                    }
                }
                //判断区域名称
                if(empty($now_city['area_info']['area_id']) && $area['area_name']){
                    $find_area_where = array('area_name' => array('like', '%' . $area['area_name'] . '%'));
                    if($now_city['area_info']['city_id']){
                        $find_area_where['area_pid'] = $now_city['area_info']['city_id'];
                    }
                    $now_area = M('Area')->where($find_area_where)->find();
                    if($now_area){
                        $now_city['area_info']['area_id'] = $now_area['area_id'];
                        $now_city['area_info']['area_name'] = $now_area['area_name'];
                        $now_city['area_info']['area_is_open'] = $now_area['is_open'];
                    }
                }
                //反查城市名是否为空
                if(empty($now_city['area_info']['city_id']) && $now_area){
                    $now_city = M('Area')->where(array('area_id' => $now_area['area_pid']))->find();
                    $now_city['area_info']['city_name'] = $now_city['area_name'];
                    $now_city['area_info']['city_id'] = $now_city['area_id'];
                }
                //省份
                $now_city['area_info']['province_id'] = $now_city['area_pid'];
                $now_city['area_info']['province_name'] = $area['province_name'];
                $now_city['address_name'] = $result['address_info']['short_address'];
                if($result['address_info']['province']){
                    $now_city['address_name'] .= ' ' . $result['address_info']['province'];
                }
                $now_city['address_addr'] = $result['formatted_address'];
                $now_city['pois'] = array();

                $now_city['google_place_id'] = $geocode_result['results'][0]['place_id'];

                if(!$now_city['area_info']['area_id']){
                    $vicinity = explode(',',$now_city['pois'][0]['addr']);
                    fdump($vicinity,'vicinity',true);
                    $google_area_name = trim(array_pop($vicinity));
                    if($google_area_name){
                        $find_area_where = array('area_name' => array('like', '%' . $google_area_name . '%'));
                        if($now_city['area_info']['city_id']){
                            $find_area_where['area_pid'] = $now_city['area_info']['city_id'];
                        }
                        $area_info = M('Area')->where($find_area_where)->find();
                        fdump(M('Area')->getLastSql(),'vicinity',true);
                        if($area_info){
                            $now_city['area_info']['area_id'] = $area_info['area_id'];
                            $now_city['area_info']['area_name'] = $area_info['area_name'];
                            $now_city['area_info']['area_is_open'] = $area_info['is_open'];
                        }
                    }
                }
            }
            fdump($now_city,'cityMatching_result',true);
        }
        return $now_city;
    }
}
?>