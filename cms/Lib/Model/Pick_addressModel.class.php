<?php

class Pick_addressModel extends Model
{

    public function get_pick_addr_by_merid($mer_id, $is_system = false, $store_id = 0, $is_pick_msg = false, $pick_info = array(), $is_open = 0, $pick_addr_ids = '')
    {
        $lng_lat['long'] = cookie('userLocationLong');
        $lng_lat['lat']  = cookie('userLocationLat');
        if (empty($lng_lat['long']) || empty($lng_lat['lat'])) {
            $lng_lat = D('User_long_lat')->getLocation($_SESSION['openid'], 0);
        }

        if (!$is_system) {
            $store_list = D('Merchant_store')->get_storelist_by_merId($mer_id);
            foreach ($store_list as $key => $vs) {
                $area[] = $vs['province_id'];
                $area[] = $vs['city_id'];
                $area[] = $vs['area_id'];
                if ($lng_lat['lat']) {
                    $distance = getDistance($lng_lat['lat'], $lng_lat['long'], $vs['lat'], $vs['long']);
                } else {
                    $distance = 0;
                }
                $pick_addr[] = array('name' => $vs['adress'], 'area_info' => array('province' => $vs['province_id'], 'city' => $vs['city_id'], 'area' => $vs['area_id']), 'pick_addr_id' => 's' . $vs['store_id'], 'phone' => $vs['phone'], 'long' => $vs['long'], 'lat' => $vs['lat'], 'addr_type' => 1, 'distance' => $distance);
            }
        } elseif ($store_id) {
            $store  = D('Merchant_store')->field(true)->where(array('mer_id' => $mer_id, 'store_id' => $store_id))->find();
            $area[] = $store['province_id'];
            $area[] = $store['city_id'];
            $area[] = $store['area_id'];
            if ($lng_lat['lat']) {
                $distance = getDistance($lng_lat['lat'], $lng_lat['long'], $store['lat'], $store['long']);
            } else {
                $distance = 0;
            }
            $pick_addr[] = array('name' => $store['adress'], 'area_info' => array('province' => $store['province_id'], 'city' => $store['city_id'], 'area' => $store['area_id']), 'pick_addr_id' => 's' . $store['store_id'], 'phone' => $store['phone'], 'long' => $store['long'], 'lat' => $store['lat'], 'addr_type' => 1, 'distance' => $distance);
        }
        $pick_condition['mer_id'] = $mer_id;
        if ($is_open) {
            $pick_condition['is_open'] = 1;
        }
        if ($pick_addr_ids) {
            $pick_condition['_string'] = "FIND_IN_SET(`id`,'" . $pick_addr_ids . "')";
        }
        $pick_addr_list = M('Pick_address')->where($pick_condition)->select();
        
        foreach ($pick_addr_list as $k => $v) {
            $area[] = $v['province_id'];
            $area[] = $v['city_id'];
            $area[] = $v['area_id'];
            if ($lng_lat['lat']) {
                $distance = getDistance($lng_lat['lat'], $lng_lat['long'], $v['lat'], $v['long']);
            } else {
                $distance = 0;
            }
            $pick_addr[] = [
                'name'         => $v['pick_addr'],
                'province'     => $v['province_id'],
                'city'         => $v['city_id'],
                'area'         => $v['area_id'],
                'area_info'    => [
                    'province' => $v['province_id'],
                    'city'     => $v['city_id'],
                    'area'     => $v['area_id']
                ],
                'pick_addr_id' => 'p' . $v['id'],
                'phone'        => $v['phone'],
                'long'         => $v['long'],
                'lat'          => $v['lat'],
                'is_open'      => $v['is_open'],
                'addr_type'    => 2,
                'distance'     => $distance,
                'image'        => replace_file_domain($v['image'])?: C('config.site_url').'/static/appapi/img/pick_address_default.png',
            ];
        }
        $where['area_id'] = array('in', implode(',', $area));
        $area_name        = M('Area')->where($where)->getField('area_id,area_name,zip_code');
        $pick_msg         = array();
        foreach ($pick_addr as &$v) {
            $v['area_info']['province'] = $area_name[$v['area_info']['province']]['area_name'];
            $v['area_info']['city']     = $area_name[$v['area_info']['city']]['area_name'];
            $v['area_info']['zip_code'] = $area_name[$v['area_info']['area']]['zip_code'];
            $v['area_info']['area']     = $area_name[$v['area_info']['area']]['area_name'];

            if ($is_pick_msg) {
                if ($pick_info && in_array($v['pick_addr_id'], $pick_info)) {
                    $pick_msg[$v['pick_addr_id']] = $v;
                } elseif (empty($pick_info)) {
                    $pick_msg[$v['pick_addr_id']] = $v;
                }
            }
        }
        if ($is_pick_msg) {
            return $pick_msg;
        }
        $pick_addr = sortArrayAsc($pick_addr, 'distance');
        return $pick_addr;
    }

}