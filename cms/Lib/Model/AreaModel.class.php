<?php

class AreaModel extends Model
{
    public $nameCache = array();

    /*得到所有区域*/
    public function get_area_list($limit = '', $cat_url = '', $type = 'category')
    {
        $condition_area['area_pid'] = C('config.now_city');
        $condition_area['is_open'] = '1';
        $area_list = $this->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->limit($limit)->select();
        if (is_array($area_list)) {
            foreach ($area_list as $key => $value) {
                $area_list[$key]['url'] = $this->get_area_url($value, $cat_url, $type);
            }
        }
        return $area_list;
    }

    public function get_area_list_hot($limit = '', $cat_url = '', $type = 'category')
    {
        $now_area_select = C('config.now_select_city');
        if (!$now_area_select) {
            return array();
        }
        $area_pids = $this->where(array('area_pid' => $now_area_select['area_id']))->select();
        foreach ($area_pids as $item) {
            $area_arr[] = $item['area_id'];
        }
        $condition_area['area_pid'] = array('in', $area_arr);
        $condition_area['is_open'] = '1';
        $condition_area['is_hot'] = '1';
        $condition_area['circle_category_id'] = array('gt', 0);

        $area_list = $this->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->limit($limit)->select();
        return $area_list;
    }

    public function get_circle_by_keyword($keyword)
    {
        $now_area_select = C('config.now_select_city');
        $area_pids = $this->where(array('area_pid' => $now_area_select['area_id']))->select();
        foreach ($area_pids as $item) {
            $area_arr[] = $item['area_id'];
        }
        $condition_area['area_pid'] = array('in', $area_arr);
        $condition_area['is_open'] = '1';
        $condition_area['is_hot'] = '1';
        $condition_area['circle_category_id'] = array('gt', 0);
        $condition_area['area_name'] = array('like', '%' . $keyword . '%');
        $area_list = $this->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();

        return $area_list;
    }

    /*通过父级ID得到子区域*/
    public function get_arealist_by_areaPid($area_pid = 0, $is_open = false, $cat_url = '', $type = 'category')
    {
        $cacha_name = 'area_pid_' . $area_pid . '_' . strval($is_open) . '_' . $cat_url;
        $area_list = S($cacha_name);
        if (empty($area_list)) {
            $condition_area['area_pid'] = $area_pid;
            if ($is_open) {
                $condition_area['is_open'] = '1';
            }
            $area_list = $this->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->limit($limit)->select();
            if (is_array($area_list)) {
                foreach ($area_list as $key => $value) {
                    $area_list[$key]['url'] = $this->get_area_url($value, $cat_url, $type);
                }
                S($cacha_name, $area_list, 86400);
            }
        }
        return $area_list;
    }

    /*通过城市ID得到商城*/
    public function get_tranding_by_areaPid($area_pid = 0, $is_open = false, $cat_url = '', $type = 'category')
    {
        // area_type = 5 商城
        $cacha_name = 'area_pid_' . $area_pid . '_' . strval($is_open) . '_tranding_' . $cat_url;
        $area_list = S($cacha_name);
        if (empty($area_list)) {
            $condition_area['area_pid'] = array('in', $area_pid);
            if ($is_open) {
                $condition_area['is_open'] = '1';
            }
            // 城市
            $area_list = $this->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
            $area_city_ids = array();
            if ($area_list) {
                foreach ($area_list as $city) {
                    $area_city_ids[] = $city['area_id'];
                    $area_type = $city['area_type'];
                }
            }

            // 商圈
            $condition_area['area_pid'] = array('in', $area_city_ids);
            $area_list = $this->field(true)->where($condition_area)->order('`area_sort` ASC,`area_id` ASC')->select();
            $area_city_ids = array();
            if ($area_list) {
                foreach ($area_list as $city) {
                    $area_city_ids[] = $city['area_id'];
                }
            }

            // 商城
            $condition_market['area_id'] = array('in', $area_city_ids);
            if ($is_open) {
                $condition_market['is_open'] = '1';
            }
            $area_list = D('Area_market')->field(true)->where($condition_market)->order('`market_sort` ASC,`market_id` ASC')->select();

            if (is_array($area_list)) {
                foreach ($area_list as &$value) {
                    $value['area_url'] = $value['market_id'];
                    $value['area_name'] = $value['market_name'];
                }
                S($cacha_name, $area_list, 86400);
            }
        }
        return $area_list;
    }


    /*通过区县ID得到商城*/
    public function get_tranding_by_countyPid($area_pid = 0, $is_open = false, $cat_url = '', $type = 'category')
    {
        // area_type = 5 商城
        $cacha_name = 'area_pid_' . $area_pid . '_' . strval($is_open) . '_tranding_' . $cat_url;
        $area_list = S($cacha_name);
        if (empty($area_list)) {
            $condition_area['area_pid'] = array('in', $area_pid);
            if ($is_open) {
                $condition_area['is_open'] = '1';
            }

            // 商圈
            $area_list = $this->field(true)->where($condition_area)->order('`area_sort` ASC,`area_id` ASC')->select();
            $area_city_ids = array();
            if ($area_list) {
                foreach ($area_list as $city) {
                    $area_city_ids[] = $city['area_id'];
                }
            }

            // 商城
            $condition_market['area_id'] = array('in', $area_city_ids);
            if ($is_open) {
                $condition_market['is_open'] = '1';
            }
            $area_list = D('Area_market')->field(true)->where($condition_market)->order('`market_sort` ASC,`market_id` ASC')->select();

            if (is_array($area_list)) {
                foreach ($area_list as &$value) {
                    $value['area_url'] = $value['market_id'];
                    $value['area_name'] = $value['market_name'];
                }
                S($cacha_name, $area_list, 86400);
            }
        }
        return $area_list;
    }


    public function get_area_by_areaUrl($area_url, $cat_url = '', $type = 'category')
    {
        $condition_area['area_url'] = $area_url;
        $condition_area['is_open'] = '1';
        //$condition_area['area_pid'] = C('config.now_city');
        $now_area = $this->field(true)->where($condition_area)->find();
        if (!empty($now_area)) {
            $now_area['url'] = $this->get_area_url($now_area, $cat_url, $type);
        }
        return $now_area;
    }

    public function get_area_by_areaId($area_id, $is_open = true, $cat_url = '', $type = 'category')
    {
        $condition_area['area_id'] = $area_id;
        if ($is_open) {
            $condition_area['is_open'] = '1';
        }
        $now_area = $this->field(true)->where($condition_area)->find();
        if (!empty($now_area)) {
            $now_area['url'] = $this->get_area_url($now_area, $cat_url, $type);
        }
        return $now_area;
    }

    public function get_circle_list($limit = '12', $cat_url = '', $type = 'category')
    {
        $area_list = $this->get_area_list($limit, $cat_url, $type);
        if (empty($area_list)) {
            return array();
        }
        $area_pid_arr = array();
        foreach ($area_list as $key => $value) {
            array_push($area_pid_arr, $value['area_id']);
        }
        $condition_area['area_pid'] = array('in', implode(',', $area_pid_arr));
        $condition_area['is_open'] = 1;
        $circle_list = $this->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->limit($limit)->select();
        if (is_array($circle_list)) {
            foreach ($circle_list as $key => $value) {
                $circle_list[$key]['url'] = $this->get_area_url($value, $cat_url, $type);
            }
        }
        return $circle_list;
    }

    public function get_hot_circle_list($limit = '12', $cat_url = '', $type = 'category')
    {
        $area_list = $this->get_area_list(0, $cat_url, $type);
        if (empty($area_list)) {
            return array();
        }
        $area_pid_arr = array();
        foreach ($area_list as $key => $value) {
            array_push($area_pid_arr, $value['area_id']);
        }
        $condition_area['area_pid'] = array('in', implode(',', $area_pid_arr));
        $condition_area['is_open'] = 1;
        $condition_area['is_hot'] = 1;
        $circle_list = $this->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->limit($limit)->select();
        if (is_array($circle_list)) {
            foreach ($circle_list as $key => $value) {
                $circle_list[$key]['url'] = $this->get_area_url($value, $cat_url, $type);
            }
        }
        return $circle_list;
    }

    /*通过area_id 向上找父集地区*/
    public function get_parents_area_by_areaid($area_id, $return = array())
    {
        $where['area_id'] = $area_id;
        $area = $this->where($where)->find();
        $return[$area['area_type']] = $area_id;

        if ($area['area_pid'] == 0) {
            return $return;
        } else {
            return $this->get_parents_area_by_areaid($area['area_pid'], $return);
        }
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
        } else if ($type == 'category_new') {
            $appoint_base_url = D('Appoint')->appoint_base_url;
            if (empty($cat_url)) {
                return C('config.site_url').$appoint_base_url.'pages/reserve_menu/index?cat_url=all';
            } else {
                return C('config.site_url').$appoint_base_url.'pages/reserve_menu/index?cat_url='.$cat_url;
            }
        }
    }

    public function get_all_area_list($limit = '', $cat_url = '', $type = 'category',$field=true, $is_completion = false)
    {
        $condition_area['area_pid'] = C('config.now_city');
        $condition_area['is_open'] = '1';
        $area_list = $this->field(true)->where($condition_area)->field($field)->order('`area_sort` DESC,`area_id` ASC')->select();
        $result = $area_pid = array();

        if ($is_completion){
            $result[0] = array(
                'area_id' => 0,
                'name' => '全城',
                'area_name' => '全城',
                'area_url' => 'all'
            );
        }
        foreach ($area_list as $value) {
            $value['url'] = $this->get_area_url($value, $cat_url, $type);
            $area_pid[] = $value['area_id'];
            $value['area_list'] = array();
            $value['area_count'] = 0;
            $result[$value['area_id']] = $value;
        }

        if ($area_pid) {
            unset($condition_area['area_pid']);
            $condition_area['area_pid'] = array('in', $area_pid);
            $area_list = $this->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
            foreach ($area_list as $row) {
                $row['url'] = $this->get_area_url($row, $cat_url, $type);
                if (isset($result[$row['area_pid']])) {
                    if ($is_completion && empty($result[$row['area_pid']]['area_list'])){
                        $result[$row['area_pid']]['area_list'][] = array(
                            'area_id' => 0,
                            'name' => '全区',
                            'area_name' => '全区',
                            'area_url' => $result[$row['area_pid']]['area_url']
                        );
                    }
                    $result[$row['area_pid']]['area_list'][] = $row;
                    $result[$row['area_pid']]['area_count']++;
                }
            }
        }
        return $result;
    }

    /**
     * 获取 area_name
     * @param int $id
     * @return string $area_name
     */
    public function name($id)
    {
        if (isset($this->nameCache[$id])) {
            return $this->nameCache[$id];
        }

        $where = array('area_id' => intval($id));

        return $this->nameCache[$id] = $this->where($where)->getField('area_name');
    }

    # 景区得到所有区域
    public function scenic_get_area_list($where = '', $limit = '')
    {
        $area_list = $this->field(true)->where($where)->order('`area_sort` DESC,`area_id` ASC')->limit($limit)->select();
        foreach ($area_list as &$v) {
            $v['son'] = $this->scenic_get_arealist_by_areaPid($v['area_id'], 1);
        }
        return $area_list;
    }

    # 景区得到所有区域
    public function scenic_get_area_list_pc($where = '', $limit = '')
    {
        $area_list = $this->field(true)->where($where)->order('`area_sort` DESC,`area_id` ASC')->limit($limit)->select();
        foreach ($area_list as $k => &$v) {
            $v['son'] = $this->scenic_get_arealist_by_areaPid($v['area_id'], 1);
            if (empty($v['son'])) {
                unset($area_list[$k]);
            }
        }
        return $area_list;
    }

    # 景区得到所有省市
    public function scenic_get_area_list_pcs($where = '')
    {
        $area_list['province'] = $this->field(true)->where($where)->order('`area_sort` DESC,`area_id` ASC')->select();
        foreach ($area_list['province'] as $k => $v) {
            $area_list['city'][$k] = $this->scenic_get_arealist_by_areaPid($v['area_id'], 1);
        }
        return $area_list;
    }

    # 景区通过父级ID得到子区域
    public function scenic_get_arealist_by_areaPid($area_pid = 0, $is_open = false)
    {
        $where['area_pid'] = $area_pid;
        $cacha_name = 'scenic_area_pid_' . $area_pid . '_' . strval($is_open);
        $area_list = S($cacha_name);
        if (empty($area_list)) {
            $condition_area['area_pid'] = $area_pid;
            if ($is_open) {
                $condition_area['is_open'] = '1';
            }
            $area_list = $this->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->limit($limit)->select();
            S($cacha_name, $area_list, 86400);
        }
        return $area_list;
    }

    # 景区通过当前城市获取当前省市
    public function scenic_get_area_by_areaId($area_id, $is_open = true, $return = 0)
    {
        $condition_area['area_id'] = $area_id;
        if ($is_open) {
            $condition_area['is_open'] = '1';
        }
        $area = $this->field(true)->where($condition_area)->find();
        $now_area = $this->field(true)->where(array('area_id' => $area['area_pid'], 'is_open' => 1))->find();
        if ($now_area) {
            $now_area['city'] = $this->field(true)->where(array('area_pid' => $area['area_pid'], 'is_open' => 1))->select();
        } else {
            $now_area = array();
        }
        if ($return == 1) {
            return $now_area['city'];
        }
        return $now_area;
    }

    # 景区用城市ID换取对应的名称
    public function scenic_get_one_city($area_id, $is_open = true)
    {
        $condition_area['area_id'] = $area_id;
        if ($is_open) {
            $condition_area['is_open'] = '1';
        }
        $area = $this->field(true)->where($condition_area)->find();
        if ($area) {
            return $area;
        } else {
            return array();
        }
    }

    # 景区PC得到一级城市
    public function scenic_get_area_all($where = '')
    {
        $area_list = $this->field(true)->where($where)->select();
        if ($area_list) {
            $arr = array(
                'ABCDE' => array(),
                'FGHJ' => array(),
                'KLMNP' => array(),
                'QRSTW' => array(),
                'XYZ' => array(),
            );
            foreach ($area_list as &$v) {
                $v['first_pinyin'] = strtoupper($v['first_pinyin']);
                if (strstr("ABCDE", $v['first_pinyin'])) {
                    $arr['ABCDE'][$v['first_pinyin']][] = $v;
                } else if (strstr("FGHJ", $v['first_pinyin'])) {
                    $arr['FGHJ'][$v['first_pinyin']][] = $v;
                } else if (strstr("KLMNP", $v['first_pinyin'])) {
                    $arr['KLMNP'][$v['first_pinyin']][] = $v;
                } else if (strstr("QRSTW", $v['first_pinyin'])) {
                    $arr['QRSTW'][$v['first_pinyin']][] = $v;
                } else if (strstr("XYZ", $v['first_pinyin'])) {
                    $arr['XYZ'][$v['first_pinyin']][] = $v;
                }
            }
        }
        return $arr;
    }

    # 景区PC得到热门城市
    public function scenic_get_hot()
    {
        $where = array(
            'is_hot' => 1,
            'is_open' => 1,
            'area_type' => 2,
        );
        $area_list = $this->field(true)->where($where)->order('`area_sort` DESC,`area_id` ASC')->select();
        return $area_list;
    }

    public function getIndexByAreaID($areaId)
    {
        $now_area = D('Area')->field(true)->where(array('area_id' => $areaId))->find();
        if ($now_area['area_type'] == 3) {
            $area_index = 'area_id';
        } elseif ($now_area['area_type'] == 2) {
            $area_index = 'city_id';
        } elseif ($now_area['area_type'] == 1) {
            $area_index = 'province_id';
        }
        return $area_index;
    }

	// 经纬度转化为地址
	public static function getBaiduMapInfo($long, $lat) {
		$baidu_map_ak = C('config.baidu_map_ak');
		if(empty($baidu_map_ak)){
            $baidu_map_ak='DekekvlfIdTbMW6o5jG7aepA5EivW5xh';
        }
		$location = $lat . ',' . $long;
        import('ORG.Net.Http');
        $http = new Http();
        $url ="http://api.map.baidu.com/reverse_geocoding/v3/?ak=". $baidu_map_ak ."&output=json&coordtype=wgs84ll&location=". $location;
		$result = $http->curlGet($url);
		$result = json_decode($result, true);
        $mapInfo=array();
        if (!empty($result) && isset($result['result']) && !empty($result['result'])) {
            $mapInfo=$result['result'];
        }
		return $mapInfo;
	}

	// 生成sn
	public static function getMapSn($ak, $sk, $url, $querystring_arrays, $method = 'GET') {
		if ($method === 'POST'){
			ksort($querystring_arrays);
		}
		
		$querystring = http_build_query($querystring_arrays);
		$querystring = str_replace('%2C', ',', $querystring);
		
		return md5(urlencode($url . '?' . $querystring . $sk));
	}

    public function cityMatching($lat, $lng, $returnPois = false)
    {
		import('ORG.Net.Http');
		$http = new Http();
			
		$map_config = C('config.map_config');
//        fdump_api(['cityMatching489' => $map_config],'address/cityMatching',true);
		if(!$map_config || $map_config == 'baidu'){
			$url = 'http://api.map.baidu.com/reverse_geocoding/v3/?output=json&extensions_poi=1&ak=' . C('config.baidu_map_ak') . '&location=' . $lat . ',' . $lng;
			
			$result = $http->curlGet($url);
			$result = json_decode($result, true);
//            fdump_api([$url,$result],'address/areaModelCityMatching',true);
			if (isset($result['status']) && $result['status'] == 210 && !empty($result['message'])) {
			    return ['error' => 1, 'message' => $result['message']];
            }
            if(empty($result['result']) && C('config.tianditu_key')){
                //百度地图没解析到，如果配置了天地图，那么按照天地图解析
                list($wgs84_lng, $wgs84_lat) = Coordinate::bd09ToWgs84($lng, $lat);
                $result = Tianditu::reverseGeocoder($wgs84_lng, $wgs84_lat, C('config.tianditu_key'));
                $area['area_name'] = $result['result']['addressComponent']['county']?:$result['result']['addressComponent']['town'];
                $full_city_name = $area['city_name'] = $result['result']['addressComponent']['city'];
                $area['province_name'] = $result['result']['addressComponent']['province'];
            }else{
                $area['area_name'] = $result['result']['addressComponent']['district']?:$result['result']['addressComponent']['town'];
                $full_city_name = $area['city_name'] = $result['result']['addressComponent']['city'] ?: $result['result']['addressComponent']['district'];
			    $area['province_name'] = $result['result']['addressComponent']['province'];
            }
			
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
                    $v = str_replace(L_('蒙古族藏族自治州'), '', $v);
					$v = str_replace(L_('藏族自治州'), '', $v);
					$v = str_replace(L_('彝族自治州'), '', $v);
					$v = str_replace(L_('白族自治州'), '', $v);
					$v = str_replace(L_('傈僳族自治州'), '', $v);
				}
			}
			
			if($area['city_name']){
				$now_city = M('Area')->where(array('area_name' => ['in',[$area['city_name'],$full_city_name]],'area_type'=>2))->order('is_open ASC')->find();
				$now_city['area_info']['city_name'] = $now_city['area_name'];
				$now_city['area_info']['city_id'] = $now_city['area_id'];
			}
			
			if($area['area_name']){
				$find_area_where = ['area_name' => ['like', '%' . $area['area_name'] . '%']];
				if($now_city['area_info']['city_id']){
					$find_area_where['area_pid'] = $now_city['area_info']['city_id'];
				}
				$area_info = M('Area')->where($find_area_where)->order('is_open ASC')->find();
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
				$now_city['address_name'] = $result['result']['addressComponent']['street']??$result['result']['addressComponent']['town'];
				$now_city['address_addr'] = $result['result']['addressComponent']['street']??$result['result']['addressComponent']['town'];
			}
        }else if($map_config == 'google'){
			// $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $lng.'&location_type=ROOFTOP&result_type=street_address&key='.C('config.google_map_ak').'&language=en';
			// $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $lng.'&location_type=ROOFTOP&result_type=point_of_interest&key='.C('config.google_map_ak').'&language=en';
			$url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $lng.'&key='.C('config.google_map_ak').'&language=en';
			$geocode_result = $http->curlGet($url);
			$geocode_result = json_decode($geocode_result, true);
			
//			fdump($url,'cityMatching_result',true);
//			fdump($geocode_result,'cityMatching_result',true);
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
					$now_city = M('Area')->where(array('area_name' => $area['city_name'],'area_type' => 2))->order('is_open ASC')->find();
					if($now_city){
						$now_city['area_info']['city_name'] = $now_city['area_name'];
						$now_city['area_info']['city_id'] = $now_city['area_id'];
					}
				}
				
				$now_area = array();
				
				//判断区域邮编和区域名称
				if($result['address_info']['post_code'] && $area['area_name']){
					$find_area_where = ['zip_code' => $result['address_info']['post_code'],'area_name' => array('like', '%' . $area['area_name'] . '%'),'area_type' => 3];
					$now_area = M('Area')->where($find_area_where)->order('is_open ASC')->find();
					if($now_area){
						$now_city['area_info']['area_id'] = $now_area['area_id'];
						$now_city['area_info']['area_name'] = $now_area['area_name'];
						$now_city['area_info']['area_is_open'] = $now_area['is_open'];
					}
				}
				
				//判断区域邮编
				if(empty($now_city['area_info']['area_id']) && $result['address_info']['post_code']){
					$find_area_where = ['zip_code' => $result['address_info']['post_code'],'area_type' => 3];
					$now_area = M('Area')->where($find_area_where)->order('is_open ASC')->find();
					if($now_area){
						$now_city['area_info']['area_id'] = $now_area['area_id'];
						$now_city['area_info']['area_name'] = $now_area['area_name'];
						$now_city['area_info']['area_is_open'] = $now_area['is_open'];
					}
				}
				
				//判断区域名称
				if(empty($now_city['area_info']['area_id']) && $area['area_name']){
					$find_area_where = ['area_name' => array('like', '%' . $area['area_name'] . '%'),'area_type' => 3];
					if($now_city['area_info']['city_id']){
						$find_area_where['area_pid'] = $now_city['area_info']['city_id'];
					}
					$now_area = M('Area')->where($find_area_where)->order('is_open ASC')->find();
					if($now_area){
						$now_city['area_info']['area_id'] = $now_area['area_id'];
						$now_city['area_info']['area_name'] = $now_area['area_name'];
						$now_city['area_info']['area_is_open'] = $now_area['is_open'];
					}
				}

				//反查城市名是否为空
				if(empty($now_city['area_info']['city_id']) && $now_area){
					$now_city1 = M('Area')->where(array('area_id' => $now_area['area_pid']))->find();
					$now_city['area_id'] = $now_city1['area_id'];
					$now_city['area_name'] = $now_city1['area_name'];
					$now_city['area_pid'] = $now_city1['area_pid'];
					$now_city['is_open'] = $now_city1['is_open'];
					$now_city['area_info']['city_id'] = $now_city1['area_id'];
					$now_city['area_info']['city_name'] = $now_city1['area_name'];
				}
				
				//省份
				$now_city['area_info']['province_id'] = $now_city['area_pid'];
				$now_city['area_info']['province_name'] = $area['province_name'];
				$now_city['address_name'] = $result['address_info']['short_address'];
                
                // 除新西兰，地址后面拼接省份
				if($result['address_info']['country'] != 'NZ'){
					if($result['address_info']['province']){
						$now_city['address_name'] .= ' ' . $result['address_info']['province'];
					}
				}else{
					if($result['address_info']['city']){
						$now_city['address_name'] .= ' ' . $result['address_info']['city'];
					}
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
//			fdump($now_city,'cityMatching_result',true);
		}
		if ($now_city['area_info'] && '贵州' == $now_city['area_info']['province_name'] && '六盘水' == $now_city['area_info']['city_name'] && '盘县' == $now_city['area_info']['area_name']) {
            // 贵州六盘水盘县 替换成  贵州六盘水盘州
		    $now_city['area_info']['area_name'] = '盘州';
        }

        return $now_city;
    }

    //通过商圈分类id，获取对应的商圈数组信息
    public function get_circle_data_by_menu($circle_category_id = 0, $lat = 0, $long = 0, $only_id = 0)
    {
        $where = array();
        $where['is_open'] = 1;
        $where['area_type'] = 4;
        if ($circle_category_id > 0) {
            $where['circle_category_id'] = $circle_category_id;
        }
        if ($only_id == 1) {
            $data = $this->where($where)->getField('area_id', true);
        } else {
            //获取城市下的区域
            $where2 = array();
            $where2['is_open'] = 1;
            $where2['area_type'] = 3;
            $where2['area_pid'] = C('config.now_city');
            $area_pid_arr = $this->where($where2)->getField('area_id', true);
            $where['area_pid'] = array('in', $area_pid_arr);

            import('@.ORG.wap_group_page');
            $count = $this->where($where)->count(1);
            $Page = new Page($count, 10, 'page');
            $field = '*';
            $field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`area_lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`area_lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`area_lng`*PI()/180)/2),2)))*1000) AS juli";
            $list = $this->where($where)->field($field)->limit($Page->firstRow, $Page->listRows)->order('juli asc')->select();
            $data = array();
            $data['totalPage'] = ceil($count / 10);
            $data['list'] = $list;
            $data['count'] = $count;
        }
        return $data;
    }

    // 地区搜索
    public function get_limit_list_page($condition = array(), $pageSize = 20)
    {
        $count = $this->where($condition)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, $pageSize, 'page');
        $list = $this->field(true)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();

        foreach ($list as $Key => $val) {

        }

        if ($list) {
            $return['totalPage'] = ceil($count / $pageSize);
            $return['count'] = count($list);
            $return['pagebar'] = $p->show();
            $return['list'] = $list;
        }
        return $return;
    }

    /*通过所优城市*/
    public function get_all_city($is_open = 0, $area_type = 1)
    {
        $cacha_name = 'area_pid_all_city_' . $is_open . '_' . $area_type;
        $area_list = S($cacha_name);
        if (empty($area_list)) {
            $condition['area_type'] = $area_type;
            $condition['is_open'] = $is_open;
            $area_list = $this->field(true)->where($condition)->select();
            if (is_array($area_list)) {
                S($cacha_name, $area_list, 86400);
            }
        }
        return $area_list;
    }
	
	/* 通过地名获取经纬度 */
	public function address_info($address){
		if(C('config.map_config') == 'google'){
			$address_info =  $this->google_address_info($address);
		}else{
			$address_info =  $this->baidu_address_info($address);
		}
		
		return $address_info;
	}
	
	/* 百度地图 - 通过地名获取经纬度 */
	public function baidu_address_info($address){
		$url = 'http://api.map.baidu.com/geocoding/v3/?address='.urlencode($address).'&output=json&ak='.C('config.baidu_map_ak');
		import('ORG.Net.Http');
		$http = new Http();
		$result = $http->curlGet($url);
		$result = json_decode($result, true);
		return $result;
	}
	
	/* 谷歌地图 - 通过地名获取经纬度 - 转百度地图数据格式，方便后续处理 */
	public function google_address_info($address){
		$url = 'https://maps.googleapis.com/maps/api/place/queryautocomplete/json?input='.urlencode($address).'&key='.C('config.google_map_ak').'&components=country:'.C('config.country_code').'&language=en';
		import('ORG.Net.Http');
		$http = new Http();
		$result = $http->curlGet($url);
		// dump($result);
		$result = json_decode($result, true);
		// dump($result);
		if($result['status'] == 'ok' || $result['status'] == 'OK'){
			$detail_url = 'https://maps.googleapis.com/maps/api/place/details/json?placeid='.$result['predictions'][0]['place_id'].'&key='.C('config.google_map_ak').'&language=en';
			$detail_result = $http->curlGet($detail_url);
			$detail_result = json_decode($detail_result, true);
			return array(
				'status' => 0,
				'result' => array(
					'location' => array(
						'lat' => $detail_result['result']['geometry']['location']['lat'],
						'lng' => $detail_result['result']['geometry']['location']['lng'],
					)
				)
			);
		}else{
			return array(
				'status' => 1,
				'msg' => 'Internal Service Error：'.$result['status'],
				'results' => array (),
			);
		}
	}

    /**
     * 根据区域id获取所有城市
     * @param int $areaid
     */
    public function getCityBuyAreaId($areaId = 0)
    {
        if ($areaId <= 0) {
            return [];
        }
        $areaDetail = M('Area')->where(['area_id' => $areaId])->find();
        if ($areaDetail) {
            switch ($areaDetail['area_type']) {
                case 1:
                    //省份
                    $cityIds = M('Area')->where(['area_pid' => $areaId])->getField('area_id', true);
                    break;
                case 2:
                    //市
                    $cityIds = [$areaId];
                    break;
                case 3:
                    //区
                    $cityIds = [$areaDetail['area_pid']];
                    break;
            }
        }
        return $cityIds;
    }

    public function get_area_key_value(){
        $return = [];
        $list = $this->select();
        foreach ($list as $key => $value) {
            $return[$value['area_id']] = $value['area_name'];
        }
        return $return;
    }

   /**
     * 区域缩略名拼接
     * @param array $areaIds
     */
    public function concatCityShortName($areaIds = [])
    {
        if (empty($areaIds)) {
            return '';
        }
        $urls = M('Area')->where(['area_id' => ['IN', $areaIds], 'area_url' => ['NEQ', '']])->order('area_type ASC')->getField('area_url', true);
        return implode('', $urls);
    }

}

?>