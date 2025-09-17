<?php
/**
 * 自动返回用户姓名 电话 号码等信息service
 * Author: zhumengqun
 * Date Time: 2020/8/25
 */

namespace app\common\model\service\address;

use app\common\model\db\Area;
use map\Tianditu;
use net\Http;
class AutoAddressService
{
    /**
     * 格式化信息
     * @param $info
     * @return array or null
     */
    public function formatInfo($info)
    {
        //去除多余空格和其他符号处理
        $info = preg_replace('/\s{2,}|,|，|;|；|、|\/|\n/', ' ', $info);
        $info = preg_replace('/\s{2,}|,|，|;|；|、|\/|\n/', ' ', $info);
        $infos = explode(' ', $info);
        if (!empty($info)) {
            //先过滤出电话号码(直接过滤不需要循环调用findWorldPhoneNumbers)
            $number = $this->findWorldPhoneNumbers($info);
            if ($number) {
                //输入的信息包含号码
                $phone = $number->getData()[0];
                //找出号码并剔除
                foreach ($infos as $key => $val) {
                    //stripos()返回值为找到的第一个下标（可能为0）否则返回false（0==false）结果为true
                    if (stripos($val, $phone) !== false) {
                        $phoneKey = $key;
                        array_splice($infos, $phoneKey, 1);
                    }
                }
            } else {
                //输入的信息不包含密码
                $phone = '';
            }
            $infosTmp = $this->findName($infos);
            if (!empty($infosTmp) && $infosTmp->getData()) {
                $name = $infosTmp->getData()['name'];
                $addr = $infosTmp->getData()['addr'];
            } else {
                $name = '';
                $addr = '';
            }
            if (!empty($addr)) {
                $addrAll = $this->findAddressBaidu($addr);
                if ($addrAll) {
                    $addr = $addrAll->getData();
                    foreach ($addr as $key => $val) {
                        $result[$key] = $val;
                    }
                } else {
                    $result['address'] = '';
                    $result['lat'] = '';
                    $result['lng'] = '';
                }
            } else {
                $result['address'] = '';
                $result['lat'] = '';
                $result['lng'] = '';
            }
            $result['phone'] = $phone;
            $result['name'] = $name;
            if (empty(array_filter($result))) {
                throw new \think\Exception('无法查询到相关信息');
            } else {
                return $result;
            }
        } else {
            throw new \think\Exception('输入参数为空');
        }
    }

    /**
     * 格式化信息
     * @param $info
     * @return array or null
     */
    public function formatInfoNew($info)
    { 
        if (empty($info)) {
            throw new \think\Exception('输入参数为空');
        }
        //去除多余空格和其他符号处理
        $info = preg_replace('/\s{2,}|,|，|;|；|、|\/|\n/', ',', $info);
        $info = preg_replace('/\s{2,}|,|，|;|；|、|\/|\n/', ',', $info);
        $infos = explode(',', $info);

        $count = count($infos);
        switch ($count) {
            case 1:
                $data = mb_strpos($infos[0], ' ') !== FALSE ? $this->pddInfo($infos) : $this->noSpace($infos[0]);
                break;
            case 2:
                $data = $this->jdInfo($infos);
                break;
            case 3:
                $data = $this->customInfo($infos);
                break;
            case 4:
                $data = $this->taobaoInfo($infos);
                break;
            case 5:
                $data = $this->weixinInfo($infos);
                break;
            default:
                $data = $this->formatInfo($info);
                break;

        }
        if (!empty($data)) {
            if (!empty($data['address']) || !empty($data['detail'])) {
                $addrAll = $this->findAddressBaidu($data['address']?:$data['detail']);
                if ($addrAll) {
                    $addrAll = $addrAll->getData();
                    $data['lat'] = $addrAll['lat'];
                    $data['lng'] = $addrAll['lng'];
                } else {
                    $data['address'] = '';
                    $data['lat'] = '';
                    $data['lng'] = '';
                }
            } else {
                $data['address'] = '';
                $data['lat'] = '';
                $data['lng'] = '';
            }
            $data['search_ok'] = 1;  //1-搜索成功，0-搜索失败（清楚粘贴板内容）
            return $data;
        } else {
            $data['address'] = '';
            $data['lat'] = '';
            $data['lng'] = '';
            $data['search_ok'] = 0;  //1-搜索成功，0-搜索失败（清楚粘贴板内容）
            return $data;
            //throw new \think\Exception('无法查询到相关信息');
        }
    }

    /**
     * 获取字符串中的电话号码
     * @param $info
     * @return json or null
     */
    public function findWorldPhoneNumbers($info)
    {
        //世界范围的号码正则表达式
        $phonesRegs = $this->_phonesRegs();
        foreach ($phonesRegs as $phoneReg) {
            preg_match($phoneReg, $info, $phone);
            if ($phone) {
                return json($phone);
            }
        }
        return '';
    }

    /**
     * 获取用户名
     * @param $infos
     * @return json or null
     * */
    public function findName($infos)
    {
        $result = array();
        $maxLen = 0;
        $addr = '';
        $name = '';
        foreach ($infos as $val) {
            if (is_numeric($val) || preg_match_all('/^[A-Za-z0-9\x80-\xff]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/', $val)) {
                continue;
            }
            //最长做地址
            $len = strlen($val);
            if ($maxLen < $len) {
                $maxLen = $len;
                if ($maxLen >= 12) {
                    $addr = $val;
                }
            }
        }
        foreach ($infos as $val) {
            //跳过数字和邮件地址等依次做姓名
            if (is_numeric($val) || preg_match_all('/^[A-Za-z0-9\x80-\xff]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/', $val, $r) || $val == $addr || in_array($val,['收货人','收件人','收货人:','收件人:','收货人：','收件人：'])) {
                continue;
            } else {
                if (strlen($val) <= 12) {
                    $name = $val;
                    break;
                }
            }
        }
        $result['addr'] = $addr;
        $result['name'] = $name;
        return json($result);
    }

    /**
     *Baidu地理编码自动获取地址
     * @param $addr
     * @return json or null
     */
    public function findAddressBaidu($addr)
    {
        $ak = cfg('baidu_map_ak');
        $url = "http://api.map.baidu.com/geocoding/v3/";
        $params = [
            'address' => $addr,
            'output' => "json",
            'ak' => $ak,
        ];
        $url .= "?" . http_build_query($params);
        $http = new Http();
        $geocode_result = $http->curlGet($url);
        $json = json_decode($geocode_result, true);
        if ($loc = @$json['result']['location']) {
            if (!is_null($loc)) {
                $url = "http://api.map.baidu.com/reverse_geocoding/v3/";
                $params = [
                    'location' => "{$loc['lat']},{$loc['lng']}",
                    'output' => "json",
                    'ak' => $ak,
                ];
                $url .= "?" . http_build_query($params);
                $geocode_result = $http->curlGet($url);
                $json = json_decode($geocode_result, true);
                $addrInfo = $json['result']['addressComponent'];
                $lng = number_format($json['result']['location']['lng'], 6);
                $lat = number_format($json['result']['location']['lat'], 6);
                if ($lng && $lng) {
                    $addrAll = ['address' => $addr, 'lng' => $lng, 'lat' => $lat];
                }

                return json($addrAll);
            }
        }
        return null;

    }

    /**
     *逆地理编码获取地址
     * @param $addr
     * @return json or null
     */
    public function cityMatching($lat, $lng, $returnPois = false)
    {
        $areas = new Area();
        $http = new Http();
        $map_config = cfg('map_config');
        $area = [];
        if (!$map_config || $map_config == 'baidu') {
            //原则，配置了百度key，优先用百度解析，百度解析失败才会去检查是否配置了天地图
            if (cfg('baidu_map_ak')) {
                $baiduAk = cfg('baidu_map_ak');
                //调取百度接口,其中ak为百度帐号key,注意location纬度在前，经度在后
                $url = "http://api.map.baidu.com/reverse_geocoding/v3/?ak=" . $baiduAk . "&location=" . $lat . "," . $lng . "&output=json&pois=1";

                $geocode_result = $http->curlGet($url);
                $result = json_decode($geocode_result, true);
                fdump_api([$url, $result], 'address/cityMatching', true);
                if ($result['status'] !== 0) {
                    //不报错，继续走天地图解析
                    //throw new \think\Exception('地址解析失败:' . $result['message']);
                } else {
                    $area['province_name'] = isset($result['result']['addressComponent']['province']) ? $result['result']['addressComponent']['province'] : '';
                    $area['city_name'] = isset($result['result']['addressComponent']['city']) ? $result['result']['addressComponent']['city'] : '';
                    $area['area_name'] = isset($result['result']['addressComponent']['district']) ? $result['result']['addressComponent']['district'] : '';
                }
            }

            //天地图解析
            if (empty($area) && cfg('tianditu_key')) {
                list($lng, $lat) = \map\Coordinate::bd09ToWgs84($lng, $lat);
                $result = Tianditu::reverseGeocoder($lng, $lat, cfg('tianditu_key'));
                $addressComponet = $result['result']['addressComponent'] ?? [];
                if (empty($addressComponet)) {
                    throw new \think\Exception('地址解析失败');
                }

                $area['province_name'] = $addressComponet['province'];
                $area['city_name'] = $addressComponet['city'] ?: $addressComponet['province'];
                $area['area_name'] = $addressComponet['county'] ?: $addressComponet['city'];
            }
            
           if(!empty($area)){
                foreach ($area as &$v) {
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
                    $v = str_replace('哈尼族彝族自治州', '', $v);
                    $v = str_replace('傣族景颇族自治州', '', $v);
                    $v = str_replace('蒙古族藏族自治州', '', $v);
                    $v = str_replace('藏族自治州', '', $v);
                    $v = str_replace('彝族自治州', '', $v);
                    $v = str_replace('白族自治州', '', $v);
                    $v = str_replace('傈僳族自治州', '', $v);
                }
                if ($area['city_name']) {
                    $order = 'is_open ASC';
                    $now_city = $areas->where('area_type',  2)->where(function ($query) use($area) {
                        $query->where('area_name', $area['city_name'])
                            ->whereOr('area_name', "{$area['city_name']}市");
                    })->order($order)->select()->toArray();
                    $now_city['area_info']['city_name'] = $now_city[0]['area_name'];
                    $now_city['area_info']['city_id'] = $now_city[0]['area_id'];
                }
                if ($area['area_name']) {
                    $find_area_where[] = ['area_name', 'like', '%' . $area['area_name'] . '%'];
                    if (isset($now_city['area_info']['city_id']) && $now_city['area_info']['city_id']) {
                        $find_area_where[] = ['area_pid', '=', $now_city['area_info']['city_id']];
                    }
                    $area_info = $areas->getAreaListByCondition($find_area_where, 'is_open ASC')->toArray();
                    if ($area_info) {
                        if(empty($area['city_name'])){//有些百度地图定位是三级，但实际咱们系统给的是二级的情况
                            // 查找城市信息
                            $find_area_where[] = ['area_type', '=', 2];
                            $area_info_p = $areas->getAreaListByCondition($find_area_where, 'is_open ASC')->toArray();
                            if($area_info_p){
                                $now_city = $area_info_p;
                                $now_city['area_info']['city_id'] = $area_info_p[0]['area_id'];
                                $now_city['area_info']['city_name'] = $area_info_p[0]['area_name'];
                            }
                        }
                       
                        $now_city['area_info']['area_id'] = $area_info[0]['area_id'];
                        $now_city['area_info']['area_name'] = $area_info[0]['area_name'];
                        $now_city['area_info']['area_is_open'] = $area_info[0]['is_open'];
                        
                    }
                }
                $now_city['area_info']['province_id'] = $now_city[0]['area_pid'];
                $now_city['area_info']['province_name'] = $area['province_name'];
                $now_city['pois'] = array();
                if (isset($result['result']['pois'])) {
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
                    $now_city['address_name'] = $result['result']['addressComponent']['street']??$result['result']['addressComponent']['address'];
                    $now_city['address_addr'] = $result['result']['addressComponent']['street']??$result['result']['addressComponent']['address'];
                }
            }else{
                throw new \think\Exception('没找到相关城市信息');
            }
        } else if ($map_config == 'google') {
            $key = cfg('google_map_ak');
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $lng . '&key=' . $key . '&language=en';
            $geocode_result = $http->curlGet($url);
            $geocode_result = json_decode($geocode_result, true);
            if(empty($geocode_result)){
                return '';
            }
            if ($geocode_result['results']) {
                //用第一个位置检索城市信息
                $result = $geocode_result['results'][0];
                $result['address_info'] = $this->format_google_address($geocode_result['results'][0]['address_components']);
                $area['area_name'] = $result['address_info']['area'];
                $area['city_name'] = $result['address_info']['city'];
                $area['province_name'] = $result['address_info']['province'];
                //判断城市名
                if ($area['city_name']) {
                    $where = ['area_name' => $area['city_name'], 'area_type' => 2];
                    $order = 'is_open ASC';
                    $now_city = $areas->getAreaListByCondition($where, $order)->toArray();
                    if ($now_city) {
                        $now_city['area_info']['city_name'] = $now_city[0]['area_name'];
                        $now_city['area_info']['city_id'] = $now_city[0]['area_id'];
                    }
                }
                $now_area = array();
                //判断区域邮编和区域名称
                if ($result['address_info']['post_code'] && $area['area_name']) {
                    $find_area_where = ['zip_code' => $result['address_info']['post_code'], 'area_name' => array('like', '%' . $area['area_name'] . '%'), 'area_type' => 3];
                    $order = 'is_open ASC';
                    $now_city = $areas->getAreaListByCondition($find_area_where, $order)->toArray();
                    if ($now_area) {
                        $now_city['area_info']['area_id'] = $now_area[0]['area_id'];
                        $now_city['area_info']['area_name'] = $now_area[0]['area_name'];
                        $now_city['area_info']['area_is_open'] = $now_area[0]['is_open'];
                    }
                }
                //判断区域邮编
                if (empty($now_city['area_info']['area_id']) && $result['address_info']['post_code']) {
                    $find_area_where = ['zip_code' => $result['address_info']['post_code'], 'area_type' => 3];
                    $order = 'is_open ASC';
                    $now_area = $areas->getAreaListByCondition($find_area_where, $order)->toArray();
                    if ($now_area) {
                        $now_city['area_info']['area_id'] = $now_area[0]['area_id'];
                        $now_city['area_info']['area_name'] = $now_area[0]['area_name'];
                        $now_city['area_info']['area_is_open'] = $now_area[0]['is_open'];
                    }
                }
                //判断区域名称
                if (empty($now_city['area_info']['area_id']) && $area['area_name']) {
                    $find_area_where = ['area_name' => array('like', '%' . $area['area_name'] . '%'), 'area_type' => 3];
                    if ($now_city['area_info']['city_id']) {
                        $find_area_where['area_pid'] = $now_city['area_info']['city_id'];
                    }
                    $order = 'is_open ASC';
                    $now_area = $areas->getAreaListByCondition($find_area_where, $order)->toArray();
                    if ($now_area) {
                        $now_city['area_info']['area_id'] = $now_area[0]['area_id'];
                        $now_city['area_info']['area_name'] = $now_area[0]['area_name'];
                        $now_city['area_info']['area_is_open'] = $now_area[0]['is_open'];
                    }
                }
                //反查城市名是否为空
                if (empty($now_city['area_info']['city_id']) && $now_area) {
                    $where = ['area_id' => $now_area[0]['area_pid']];
                    $order = 'is_open ASC';
                    $now_city1 = $areas->getAreaListByCondition($where, $order)->toArray();
                    $now_city['area_id'] = $now_city1[0]['area_id'];
                    $now_city['area_name'] = $now_city1[0]['area_name'];
                    $now_city['area_pid'] = $now_city1[0]['area_pid'];
                    $now_city['is_open'] = $now_city1[0]['is_open'];
                    $now_city['area_info']['city_id'] = $now_city1[0]['area_id'];
                    $now_city['area_info']['city_name'] = $now_city1[0]['area_name'];
                }
                //省份
                $now_city['area_info']['province_id'] = $now_city['area_pid'];
                $now_city['area_info']['province_name'] = $area['province_name'];
                $now_city['address_name'] = $result['address_info']['short_address'];
                if ($result['address_info']['province']) {
                    $now_city['address_name'] .= ' ' . $result['address_info']['province'];
                }
                $now_city['address_addr'] = $result['formatted_address'];
                $now_city['pois'] = array();

                $now_city['google_place_id'] = $geocode_result['results'][0]['place_id'];

                if (!$now_city['area_info']['area_id']) {
                    $vicinity = explode(',', $now_city['pois'][0]['addr']);
                    $google_area_name = trim(array_pop($vicinity));
                    if ($google_area_name) {
                        $find_area_where = array('area_name' => array('like', '%' . $google_area_name . '%'));
                        if ($now_city['area_info']['city_id']) {
                            $find_area_where['area_pid'] = $now_city['area_info']['city_id'];
                        }
                        $area_info = $areas->getAreaListByCondition($find_area_where, '')->toArray();
                        if ($area_info) {
                            $now_city['area_info']['area_id'] = $area_info[0]['area_id'];
                            $now_city['area_info']['area_name'] = $area_info[0]['area_name'];
                            $now_city['area_info']['area_is_open'] = $area_info[0]['is_open'];
                        }
                    }
                }
            }
        }
        if ($now_city['area_info'] && '贵州' == $now_city['area_info']['province_name'] && '六盘水' == $now_city['area_info']['city_name'] && '盘县' == $now_city['area_info']['area_name']) {
            // 贵州六盘水盘县 替换成  贵州六盘水盘州
            $now_city['area_info']['area_name'] = '盘州';
        }
        return $now_city;
    }

    public function format_google_address($address_components)
    {
        $address_info = array();

        //先循环得到国家，一些国家的地址需要特殊处理才能符合省市区的概念
        foreach ($address_components as $value) {
            if ($value['types'][0] == 'country') {
                $address_info['country'] = $value['short_name'];
            }
        }

        if ($address_info['country'] == 'NZ') {    //新西兰
            foreach ($address_components as $value) {
                if ($value['types'][0] == 'street_number' || $value['types'][0] == 'st_number') {
                    $address_info['street_number'] = $value['short_name'];
                } else if (in_array('route', $value['types'])) {
                    $address_info['street'] = $value['short_name'];
                } else if (in_array('sublocality', $value['types'])) {
                    $address_info['area'] = $value['short_name'];
                } else if (in_array('locality', $value['types'])) {
                    $address_info['city'] = $value['short_name'];
                } else if (in_array('administrative_area_level_1', $value['types'])) {
                    $address_info['province'] = $value['short_name'];
                } else if (in_array('postal_code', $value['types'])) {
                    $address_info['post_code'] = $value['short_name'];
                }
            }
        } else {
            foreach ($address_components as $value) {
                if ($value['types'][0] == 'street_number' || $value['types'][0] == 'st_number') {
                    $address_info['street_number'] = $value['short_name'];
                } else if ($value['types'][0] == 'route') {
                    $address_info['street'] = $value['short_name'];
                } else if ($value['types'][0] == 'locality') {
                    $address_info['area'] = $value['short_name'];
                } else if ($value['types'][0] == 'administrative_area_level_2') {
                    $address_info['city'] = $value['short_name'];
                } else if ($value['types'][0] == 'administrative_area_level_1') {
                    $address_info['province'] = $value['short_name'];
                } else if ($value['types'][0] == 'postal_code') {
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
        if ($address_info['country'] == 'IT' && $address_info['city'] == 'MI') {
            $address_info['city'] = 'Milano';
        }

        $address_info['street_address'] = trim($address_info['street_number'] . ' ' . $address_info['street']);
        $address_info['short_address'] = trim(ltrim(trim($address_info['street_number'] . ' ' . $address_info['street'] . ', ' . $address_info['area']), ','));
        return $address_info;
    }

    /**
     *解析淘宝地址
     */
    public function taobaoInfo($infos) {
        $data = [
            'name'    => explode(': ', $infos[0])[1],
            'phone'   => explode(': ', $infos[1])[1],
            'address' => explode(': ', $infos[2])[1],
            'detail'  => explode(': ', $infos[3])[1],
        ];
        if (!$data['name'] || !$data['phone'] || !$data['address'] || !$data['detail']) {
            return false;
        }
        return $data;
    }

    /**
     *解析京东地址
     */
    public function jdInfo($infos) {
        $data = [
            'name'    => explode('：', $infos[0])[1] ?? '',
            'phone'   => '',
            'address' => explode(' ', explode('：', $infos[1])[1])[0] ?? '',
            'detail'  => explode(' ', explode('：', $infos[1])[1])[1] ?? '',
        ];
        if (!$data['name'] || !$data['address'] || !$data['detail']) {
            return false;
        }
        return $data;
    }

    /**
     *解析微信地址
     */
    public function weixinInfo($infos) {
        $data = [
            'name'    => explode('：', $infos[0])[1] ?? '',
            'phone'   => explode('：', $infos[1])[1] ?? '',
            'address' => explode('：', $infos[2])[1] ?? '',
            'detail'  => explode('：', $infos[3])[1] ?? '',
        ];
        if (!$data['name'] || !$data['phone'] || !$data['address'] || !$data['detail']) {
            return false;
        }
        return $data;
    }

    /**
     *解析拼多多地址
     */
    public function pddInfo($infos) {
        $infos = explode(' ', $infos[0]);
        if(count($infos) < 4){
            return $this->customInfo($infos);
        }
        $data = [
            'name'    => $infos[0] ?? '',
            'phone'   => $infos[1] ?? '',
            'address' => $infos[2] ?? '',
            'detail'  => $infos[3] ?? '',
        ];
        if (!$data['name'] || !$data['phone'] || !$data['address'] || !$data['detail']) {
            return false;
        }
        return $data;
    }

    /**
     * 无空格的地址串
     */
    private function noSpace($info)
    {
        $phonesRegs = $this->_phonesRegs();
        //取出电话
        $phone = '';
        foreach ($phonesRegs as $phoneReg) {
            if($phone){
                break;
            } 
            preg_match($phoneReg, $info, $phone);
            $infoArr = preg_replace($phoneReg, ',', $info); 
        } 
        //取出地址和姓名
        $infos = explode(',', $infoArr);
        if(empty($infos[0]) || empty($infos[1])){
            return false;
        }
        
        //区分姓名和地址
        $l = mb_strlen($infos[0]) <=> mb_strlen($infos[1]);

         //取出address
         $sub_str = ['县', '区'];
         $sub_text = $infos[$l == -1 ? 1 : 0];
         $sub = false;
         foreach ($sub_str as $val) {
             if(mb_strpos($sub_text, $val) !== false){
                 $sub = mb_strpos($sub_text, $val);
                 break;
             }
         }
         $address = '';
         if($sub !== false){
            $address = mb_substr($sub_text, 0, $sub + 1);
         } 

        return [
            'name'    => $infos[$l == -1 ? 0 : 1] ?? '',
            'phone'   => $phone[0] ?? '',
            'address' => $address,
            'detail'  => $infos[$l == -1 ? 1 : 0] ?? ''
        ];
    }

    //自定义的地址格式三段式
    private function customInfo($info)
    {  
        $phone_sub = $this->_getPhoneSub($info);
        if(false === $phone_sub){
            //无手机号
            throw new \think\Exception('未填写手机号或手机号不正确！');

        }else if(is_numeric($info[$phone_sub])){
            //纯手机号
            $subArr = array(0, 1, 2);
            array_splice($subArr, $phone_sub, 1);
            list($sub1, $sub2) = $subArr;
            
             //区分姓名和地址
            $l = mb_strlen($info[$sub1]) <=> mb_strlen($info[$sub2]);

            //取出address
            $sub_str = ['县', '区'];
            $sub_text = $info[$l == -1 ? $sub2 : $sub1];
            $sub = false;
            foreach ($sub_str as $val) {
                if(mb_strpos($sub_text, $val) !== false){
                    $sub = mb_strpos($sub_text, $val);
                    break;
                }
            }
            $address = '';
            if($sub !== false){
            $address = mb_substr($sub_text, 0, $sub + 1);
            } 

            return [
                'name'    => $info[$l == -1 ? $sub1 : $sub2] ?? '',
                'phone'   => $info[$phone_sub] ?? '',
                'address' => $address,
                'detail'  => $info[$l == -1 ? $sub2 : $sub1] ?? ''
            ];
  
        }else{ 
            //兼容：地址 地址 姓名电话
            $phonesRegs = $this->_phonesRegs();
            //取出电话
            $phone = '';
            foreach ($phonesRegs as $phoneReg) {
                if($phone){
                    break;
                } 
                preg_match($phoneReg, $info[$phone_sub], $phone);
                $str = preg_replace($phoneReg, '', $info[$phone_sub]); 
            } 
            return [
                'name'    => $str ?? '',
                'phone'   => $phone[0] ?? '',
                'address' => $info[0],
                'detail'  => $info[0]   . $info[1]  ,
            ]; 
        }
  
       
    }


    /**
     * 获取手机号所在的下标
     */
    private function _getPhoneSub($info)
    {
        $phonesRegs = $this->_phonesRegs();
        $phone_sub = false;
        foreach($info as $key => $val)
        {
            foreach ($phonesRegs as $phoneReg) {
                if(preg_match($phoneReg, $val))
                {
                    $phone_sub = $key;
                    break 2;
                }
            }  
        }
        return $phone_sub;
    }
 

    /**
     * 手机号正则
     */
    private function _phonesRegs()
    {
        return [
            'zh-CN' => '/(?!=(\+?0?86\-?)?)1[345789]\d{9}/',     //中国
            'zh-TW' => '/(?!=(\+?0?886\-?|0)?)9\d{8}/',           //中国台湾
            'zh-MC' => '/(?!=(\+?0?853\-?)?)[6]([8|6])\d{5,6}/',  //中国澳门
            'en-HK' => '/(?!=(\+?0?852\-?)?)6\d{7}|9[0-8]\d{6}/'   //中国香港
            //'en-US' => '/(?!=(\+?1)?)[2-9]\d{2}[2-9](?!11)\d{6}/', //美国
            //'ar-DZ' => '/(\+?213|0)(5|6|7)\d{8}/',
            //'ar-SY' => '/(?!=(!?(\+?963)|0)?)9\d{8}/',   //叙利亚
            //'ar-SA' => '/(?!=(!?(\+?966)|0)?)5\d{8}/',   //沙特阿拉伯
            //'cs-CZ' => '/(\+?420)? ?[1-9][0-9]{2} ?[0-9]{3} ?[0-9]{3}/',
            //'de-DE' => '/(\+?49[ \.\-])?([\(]{1}[0-9]{1,6}[\)])?([0-9 \.\-\/]{3,20})((x|ext|extension)[ ]?[0-9]{1,4})?/',
            //'da-DK' => '/(?!=(\+?45)?)(\d{8})/',   //丹麦
            //'el-GR' => '/(?!=(\+?30)?)(69\d{8})/',  //希腊
            //'en-AU' => '/(\+?61|0)4\d{8}/',
            //'en-GB' => '/(\+?44|0)7\d{9}/',
            //'en-IN' => '/(?!=(\+?91|0)?)[789]\d{9}/',  //印度
            //'en-NZ' => '/(\+?64|0)2\d{7,9}/',
            //'en-ZA' => '/(\+?27|0)\d{9}/',
            //'en-ZM' => '/(\+?26)?09[567]\d{7}/',
            //'es-ES' => '/(?!=(\+?34)?)(6\d{1}|7[1234])\d{7}/',  //西班牙
            //'fi-FI' => '/(\+?358|0)\s?(4(0|1|2|4|5)?|50)\s?(\d\s?){4,8}\d/',
            //'fr-FR' => '/(\+?33|0)[67]\d{8}/',
            //'he-IL' => '/(\+972|0)([23489]|5[0248]|77)[1-9]\d{6}/',
            //'hu-HU' => '/(\+?36)(20|30|70)\d{7}/',
            //'it-IT' => '/(\+?39)?\s?3\d{2} ?\d{6,7}/',
            //'ja-JP' => '/(\+?81|0)\d{1,4}[ \-]?\d{1,4}[ \-]?\d{4}/',
            //'ms-MY' => '/(\+?6?01){1}(([145]{1}(\-|\s)?\d{7,8})|([236789]{1}(\s|\-)?\d{7}))/',
            //'nb-NO' => '/(\+?47)?[49]\d{7}/',
            //'nl-BE' => '/(\+?32|0)4?\d{8}/',
            //'nn-NO' => '/(\+?47)?[49]\d{7}/',
            //'pl-PL' => '/(\+?48)? ?[5-8]\d ?\d{3} ?\d{2} ?\d{2}/',
            //'pt-BR' => '/(\+?55|0)\-?[1-9]{2}\-?[2-9]{1}\d{3,4}\-?\d{4}/',
            //'pt-PT' => '/(?!=(\+?351)?)9[1236]\d{7}/',  //葡萄牙
            //'ru-RU' => '/(?!=(\+?7|8)?)9\d{9}/',    //俄罗斯
            //'sr-RS' => '/(\+3816|06)[- \d]{5,9}/',
            //'tr-TR' => '/(?!=(\+?90|0)?)5\d{9}/',     //土耳其
            //'vi-VN' => '/(\+?84|0)?((1(2([0-9])|6([2-9])|88|99))|(9((?!5)[0-9])))([0-9]{7})/'
        ];
    }
}
