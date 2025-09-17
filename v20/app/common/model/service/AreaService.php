<?php
/**
 * 系统后台用户登录权限服务
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 09:01
 */

namespace app\common\model\service;

use app\common\model\db\Area as AreaModel;
use app\common\model\db\AreaStreet;
use net\Http;

class AreaService
{
    public $areaObj = null;

    public function __construct()
    {
        $this->areaObj = new AreaModel();
    }

    /**
     * 获得省份和城市信息
     * @param $areaId
     * @return array
     */
    public function getSelectProvinceAndCity($systemUser) {
        
        $conditionArea['area_type'] = 1;
        $conditionArea['is_open'] = 1;

        $provinceId = 0;
        // var_dump($systemUser);
        if ($systemUser['area_id']>0) {
            $nowArea = $this->getAreaByAreaId($systemUser['area_id']);
            if ($nowArea['area_type'] == 3) {//区域
                $nowArea = $this->getAreaByAreaId($nowArea['area_pid']);
                $provinceId = $nowArea['area_pid'];
            } elseif ($nowArea['area_type'] == 2) {//城市
                $provinceId = $nowArea['area_pid'];
            } elseif ($nowArea['area_type'] == 1) {// 省份
                $provinceId = $systemUser['area_id'];
            }
        } 

        if ($provinceId) {
            $conditionArea['area_id'] = $provinceId;
        }

        // 列表
        $provinceList = $this->getAreaListByCondition($conditionArea);

        // 获得省份下所有城市信息
        $provinceIdArr = array_column($provinceList,'area_id');
        if($provinceIdArr){
            $conditionArea = [
                ['area_type' , '=' , 2],
                ['area_pid' , 'in' , implode(',',$provinceIdArr)],
            ];
            $cityIdList = $this->getAreaListByCondition($conditionArea);

            $returnArr = [];
            foreach ($provinceList as $_provice){
                $tempProvice = [
                    'title' => $_provice['area_name'],
                    'value' => $_provice['area_id'],
                    'key' => $_provice['area_id'],
                    'disabled' => true,
                    'children' => []
                ];
                foreach ($cityIdList as $_city){
                    if($_city['area_pid'] == $_provice['area_id']){
                        $tempCity = [
                            'title' => $_city['area_name'],
                            'value' => $_provice['area_id'].'-'.$_city['area_id'],
                            'key' => $_provice['area_id'].'-'.$_city['area_id'],
                        ];
                        $tempProvice['children'][] = $tempCity;
                    }
                }
                if($tempProvice['children']){
                    $returnArr[] = $tempProvice;
                }
            }
        }
        return $returnArr;
    }


	/**
     * 获得城市区域筛选的省份信息
     * @param $areaId
     * @return array
     */
    public function getSelectProvince($systemUser = []) {

        $conditionArea['area_type'] = 1;
        $conditionArea['is_open'] = 1;

        $provinceId = 0;
        // var_dump($systemUser);
        if (isset($systemUser['area_id'] ) && $systemUser['area_id'] > 0) {
            $nowArea = $this->getAreaByAreaId($systemUser['area_id']);
            if ($nowArea['area_type'] == 3) {//区域
                $nowArea = $this->getAreaByAreaId($nowArea['area_pid']);
                $provinceId = $nowArea['area_pid'];
            } elseif ($nowArea['area_type'] == 2) {//城市
                $provinceId = $nowArea['area_pid'];
            } elseif ($nowArea['area_type'] == 1) {
                $provinceId = $systemUser['area_id'];
            }
        }

        if ($provinceId) {
            $conditionArea['area_id'] = $provinceId;
        }

        // 列表
        $provinceList = $this->getAreaListByCondition($conditionArea);

        if (isset($systemUser['area_type']) && $systemUser['area_type'] >= 1) {
            $return['error'] = 2;
            $return['id'] = $provinceList[0]['id'];
            $return['name'] = $provinceList[0]['name'];
        } else if (!empty($provinceList)) {
            $return['error'] = 0;
            $return['list'] = $provinceList;
        } else {
            $return['error'] = 1;
            $return['info'] = '没有开启了的省份！请先开启。';
        }
        return $return;
    }


    /**
     * 获得城市区域筛选的城市信息
     * @param $areaId
     * @return array
     */
    public function getSelecCity($param, $systemUser = [])
    {
        $id = isset($param['id']) ? $param['id'] : 0;
        $type = isset($param['type']) ? $param['type'] : 0;
        $name = isset($param['name']) ? $param['name'] : '';

        // 省份管理员只显示该省的城市
        if (isset($systemUser['area_type']) && $systemUser['area_type'] == 1) {
            $id = $systemUser['area_id'];
        }

        if ($id == 0) {//没有省份
            $return['error'] = 0;
            $return['list'] = array();
            return $return;
        }

        // 搜索条件
        $conditionArea['area_pid'] = intval($id);
        $conditionArea['is_open'] = 1;

        // 城市ID
        $cityId = 0;
        if (isset($systemUser['area_id']) && $systemUser['area_id']) {
            $nowArea = $this->getAreaByAreaId($systemUser['area_id']);
            if ($nowArea['area_type'] == 3) {
                $cityId = $nowArea['area_pid'];
            } elseif ($nowArea['area_type'] == 2) {
                $cityId = $systemUser['area_id'];
            }
        }

        if ($cityId && isset($systemUser['area_id']) && $systemUser['area_id'] != $id) {
            $conditionArea['area_id'] = $cityId;
        }

        // 列表
        $cityList = $this->getAreaListByCondition($conditionArea);

        //城市缺少经纬度，调用接口补上
        foreach ($cityList as $k => $c) {
            if (empty($c['lat'])) {
                $result = $this->addressInfo($c['area_name']);
                if (isset($result['result'])) {
                    $lng = $result['result']['location']['lng'];
                    $lat = $result['result']['location']['lat'];
                    $cityList[$k]['lat'] = $lat;
                    $cityList[$k]['lng'] = $lng;
                    // 更新数据
                    $this->updateThis(['area_id' => $c['area_id']], ['area_lng' => $lng, 'area_lat' => $lat]);
                }
            }
        }

        if (!$type && isset($systemUser['area_type']) && $systemUser['area_type'] > 1) {
            $return['error'] = 2;
            $return['id'] = $cityList[0]['id'];
            $return['name'] = $cityList[0]['name'];
            $return['shop_main_page_show_ad'] = $cityList[0]['shop_main_page_show_ad'];
            $return['shop_main_page_center_type'] = $cityList[0]['shop_main_page_center_type'];
        } else if (!empty($cityList)) {
            $return['error'] = 0;
            $return['list'] = $cityList;
        } else {
            $return['error'] = 1;
            $return['info'] = '［ <b>' . $name . '</b> ］ 省份下没有已开启的城市！请先开启城市或删除此省份';
        }
        return $return;
    }


    /**
     * 获得城市区域筛选的区域信息
     * @param $param
     * @param $systemUser
     * @return array
     */
    public function getSelectArea($param, $systemUser =[])
    {
        $id = isset($param['id']) ? $param['id'] : 0;
        $name = isset($param['name']) ? $param['name'] : '';

        if (isset($systemUser['area_type']) && $systemUser['area_type'] == 2) {
            $id = $systemUser['area_id'];
        } elseif (isset($systemUser['area_type']) && $systemUser['area_type'] == 3) {
            $nowArea = $this->getAreaByAreaId($systemUser['area_id']);
            $return['error'] = 2;
            $return['id'] = $nowArea['area_id'];
            $return['name'] = $nowArea['area_name'];
            return $return;
        }

        if ($id == 0) {
            $return['error'] = 0;
            $return['list'] = array();
            return $return;
        }

        $conditionArea['area_pid'] = intval($id);
        $conditionArea['is_open'] = 1;

        // 列表
        $areaList = $this->getAreaListByCondition($conditionArea);

        if (!empty($areaList)) {
            $return['error'] = 0;
            $return['list'] = $areaList;
        } else {
            $return['error'] = 1;
            $return['info'] = '［ <b>' . $name . '</b> ］ 城市下没有已开启的区域！请先开启区域或删除此城市';
        }
        return $return;
    }

    /* 通过地名获取经纬度 */
    public function addressInfo($address)
    {
        if (cfg('map_config') == 'google') {
            $address_info = $this->googleAddressInfo($address);
        } else {
            $address_info = $this->baiduAddressInfo($address);
        }

        return $address_info;
    }

    /* 百度地图 - 通过地名获取经纬度 */
    public function baiduAddressInfo($address)
    {
        $url = 'http://api.map.baidu.com/geocoding/v3/?address=' . urlencode($address) . '&output=json&ak=' . cfg('baidu_map_ak');
        $http = new Http();
        $result = $http->curlGet($url);
        $result = json_decode($result, true);
        return $result;
    }

    /* 谷歌地图 - 通过地名获取经纬度 - 转百度地图数据格式，方便后续处理 */
    public function googleAddressInfo($address)
    {
        $url = 'https://maps.googleapis.com/maps/api/place/queryautocomplete/json?input=' . urlencode($address) . '&key=' . cfg('google_map_ak') . '&components=country:' . cfg('country_code') . '&language=en';
        $http = new Http();
        $result = $http->curlGet($url);
        // dump($result);
        $result = json_decode($result, true);
        // dump($result);
        if ($result['status'] == 'ok' || $result['status'] == 'OK') {
            $detail_url = 'https://maps.googleapis.com/maps/api/place/details/json?placeid=' . $result['predictions'][0]['place_id'] . '&key=' . cfg('google_map_ak') . '&language=en';
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
        } else {
            return array(
                'status' => 1,
                'msg' => 'Internal Service Error：' . $result['status'],
                'results' => array(),
            );
        }
    }

    /**
     * 设置当前城市
     * @param $areaId
     * @return array
     */
    public function setNowCity($config)
    {
        if (cfg('many_city') || cfg('location_mode')) {
            $nowCity = request()->param('now_city');
            $nowArea = request()->param('now_area');
            $nowShopCity = request()->param('now_shop_city', '0', 'intval');
            if ($nowCity) {
                $config['now_city'] = $nowCity;
            }
            if ($nowArea) {
                $config['now_area'] = $nowArea;
            }

            $config['now_shop_city'] = $nowShopCity;
        }

        //设置当前城市的时区
        if (!empty($nowCity) || !empty($nowArea)) {
            if (!empty($nowCity)) {
                $cityInfo = $this->getAreaByAreaId($nowCity);
            }
            if (!empty($nowArea)) {
                $areaInfo = $this->getAreaByAreaId($nowArea);
            }

            //area的时区优先于城市的时区
            if (!empty($areaInfo['timezone']) || !empty($cityInfo['timezone'])) {
                date_default_timezone_set($areaInfo['timezone'] ? $areaInfo['timezone'] : $cityInfo['timezone']);
            }
        }

        $scenicNowCity = request()->param('scenic_now_city');
        if ($scenicNowCity) {
            $config['scenic_now_city'] = $scenicNowCity;
        }
        return $config;
    }


    /**
     * 根据当前选中的城市id获取城市及商圈信息
     * @param $areaId
     * @return array
     */
    public function getAreaListByAreaPid($now_city = 0)
    {
        $where['area_pid'] = $now_city > 0 ? $now_city : cfg('now_city');
        $where['is_open'] = '1';
        $areaList = $this->getAreaListByCondition($where);

        $retuenArr = $areaPidArr = [];

        foreach ($areaList as $value) {
            $tmpArea = [];
            $tmpArea['area_id'] = $value['area_id'];
            $tmpArea['area_pid'] = $value['area_pid'];
            $tmpArea['area_name'] = $value['area_name'];
            $tmpArea['area_count'] = 0;
            $tmpArea['area_list'] = [];

            $areaPidArr[] = $value['area_id'];
            $retuenArr[$value['area_id']] = $tmpArea;
        }

        if ($areaPidArr) {
            $where = [];
            $where[] = ['is_open', '=', '1'];
            $where[] = ['area_pid', 'in', $areaPidArr];
            $areaList = $this->getAreaListByCondition($where);
            foreach ($areaList as $value) {
                $tmpArea = [];
                $tmpArea['area_id'] = $value['area_id'];
                $tmpArea['area_pid'] = $value['area_pid'];
                $tmpArea['area_name'] = $value['area_name'];

                if (isset($retuenArr[$value['area_pid']])) {
                    $retuenArr[$value['area_pid']]['area_list'][] = $tmpArea;
                    $retuenArr[$value['area_pid']]['area_count']++;
                }
            }
        }

        $retuenArr = array_values($retuenArr);
        return $retuenArr;
    }

    /**
     * @param int $now_city
     * @return array
     * 店铺装修分类调用---切合接口
     */
    public function getAreaListByAreaPid1($now_city = 0)
    {
        $where['area_pid'] = $now_city > 0 ? $now_city : cfg('now_city');
        $where['is_open'] = '1';
        $areaList = $this->getAreaListByCondition($where);

        $retuenArr = $areaPidArr = [];

        foreach ($areaList as $value) {
            $tmpArea = [];
            $tmpArea['id'] = strval($value['area_id']);
            $tmpArea['area_pid'] = $value['area_pid'];
            $tmpArea['name'] = $value['area_name'];
            $tmpArea['child'] = [];

            $areaPidArr[] = $value['area_id'];
            $retuenArr[$value['area_id']] = $tmpArea;
        }

        if ($areaPidArr) {
            $where = [];
            $where[] = ['is_open', '=', '1'];
            $where[] = ['area_pid', 'in', $areaPidArr];
            $areaList = $this->getAreaListByCondition($where);
            foreach ($areaList as $value) {
                $tmpArea = [];
                $tmpArea['id'] = strval($value['area_id']);
                $tmpArea['area_pid'] = $value['area_pid'];
                $tmpArea['name'] = $value['area_name'];

                if (isset($retuenArr[$value['area_pid']])) {
                    $retuenArr[$value['area_pid']]['child'][] = $tmpArea;
                    //$retuenArr[$value['area_pid']]['area_count']++;
                }
            }
        }

        $retuenArr = array_values($retuenArr);
        return $retuenArr;
    }
    /**
     * 更新
     * @param $where
     * @param $data
     * @return array
     */
    public function updateThis($where, $data)
    {
        if (empty($where) || empty($data)) {
            return false;
        }

        $result = $this->areaObj->where($where)->update($data);
        if (!$result) {
            return false;
        }

        return $result;
    }

    /**
     * 返回区域数据
     * @param $areaId
     * @return array
     */
    public function getAreaByAreaId($areaId)
    {
        $area = $this->areaObj->getAreaByAreaId($areaId);
        if (!$area) {
            return [];
        }
        return $area->toArray();
    }

    /**
     * 返回区域数据
     * @param $where
     * @return array
     */
    public function getOne($where)
    {
        $area = $this->areaObj->getOne($where);
        if (!$area) {
            return [];
        }
        return $area->toArray();
    }

    /**
     * 根据条件返回城市列表
     * @param $where
     * @param $order 排序
     * @return array
     */
    public function getAreaListByCondition($where, $order = [], $field = true)
    {
        if (empty($order)) {
            $order['area_sort'] = 'DESC';
            $order['area_id'] = 'ASC';
        }

        $areaList = $this->areaObj->getAreaListByCondition($where, $order, $field);
        if (!$areaList) {
            return [];
        }

        return $areaList->toArray();
    }

    /**
     * 搜索当前选中的城市id的商圈
     * @param $areaId
     * @return array
     */
    public function getAreaListByKeyword($keyword)
    {
        // 当前城市
        $where['is_open'] = '1';
        $now_area = $this->getAreaByAreaId(cfg('now_city'));
        $areaPid = [];
        // 查找区域
        $where['area_pid'] = cfg('now_city');
        $where['is_open'] = '1';
        $areaList = $this->getAreaListByCondition($where);
        $areaPidArr = array_column($areaList, 'area_id');

        // 查找商圈
        $where = [];
        $where[] = ['is_open', '=', '1'];
        $where[] = ['area_pid', 'in', $areaPidArr];
        $where[] = ['area_name', 'like', '%' . $keyword . '%'];
        $areaList = $this->getAreaListByCondition($where);

        $retuenArr = [];
        foreach ($areaList as $value) {
            $tmpArea = [];
            $tmpArea['area_id'] = $value['area_id'];
            $tmpArea['area_pid'] = $value['area_pid'];
            $tmpArea['area_name'] = $value['area_name'];
            $tmpArea['area_lng'] = $value['area_lng'];
            $tmpArea['area_lat'] = $value['area_lat'];
            $retuenArr[] = $tmpArea;
        }
        return $retuenArr;
    }

    /**
     * 获得当前城市的中心位置信息
     * Author: hengtingmei
     * @param $param
     * @return array
     */
    public function getCityCenter($param)
    {
        $now_city = $param['now_city'] ? $param['now_city'] : cfg('now_city');
        //获取当前省市区
        $tmpNowCity = $this->getAreaByAreaId($now_city);
        $area_lng = floatval($tmpNowCity['area_lng']);
        if (empty($area_lng)) {
            $result = invoke_cms_model('Area/address_info', ['address' => $tmpNowCity['area_name']]);
            $lng = $result['result']['location']['lng'];
            $lat = $result['result']['location']['lat'];
            $this->updateThis(['area_id' => cfg('now_city')], ['area_lng' => $lng, 'area_lat' => $lat]);
        } else {
            $lng = $tmpNowCity['area_lng'];
            $lat = $tmpNowCity['area_lat'];
        }
        $params = [
            'lat' => $lat,
            'lng' => $lng,
        ];
        $return = [];
        $nowCity = invoke_cms_model('Area/cityMatching', $params);
        if ($nowCity['error_no'] == 0) {
            $nowCity = $nowCity['retval'];
            $nowCity['address_name'] = $tmpNowCity['area_name'];
            $nowCity['address_addr'] = $tmpNowCity['area_name'];
            if (empty($nowCity['area_id'])) {
                $nowCity['area_id'] = $tmpNowCity['area_id'];
            }
            unset($nowCity['google_place_id']);

            $return['city_info'] = array(
                'lng' => $lng,
                'lat' => $lat,
                'province_id' => $nowCity['area_info']['province_id'] ?? '',
                'province_name' => $nowCity['area_info']['province_name'] ?? '',
                'city_id' => $nowCity['area_id'] ?? 0,
                'city_name' => $nowCity['area_name'] ?? '',
                'area_id' => $nowCity['area_info']['area_id'] ?? '',
                'area_name' => $nowCity['area_info']['area_name'] ?? '',
                'address_name' => $nowCity['address_name'] ?? '',
                'address_addr' => $nowCity['address_addr'] ?? '',
            );
        }
        return $return;
    }

    //通过ID数组，获取对应的地区名称(ID映射名称)
    public function getNameByIds($ids)
    {
        if (empty($ids)) return [];
        $data = $this->areaObj->getSome([['area_id', 'in', $ids]], 'area_id,area_name')->toArray();
        return array_column($data, 'area_name', 'area_id');
    }


    /**
     * 获取乡镇/街道
     * @param $param
     * @param $systemUser
     * @return mixed
     * @author: 张涛
     * @date: 2021/02/04
     */
    public function getSelectStreet($param, $systemUser) {
        $id = isset($param['id']) ? $param['id'] : 0;
        $name = isset($param['name']) ? $param['name'] : '';



        // 列表
        $areaList = $this->getAreaListByCondition($conditionArea);

        if (!empty($areaList)) {
            $return['error'] = 0;
            $return['list'] = $areaList;
        } else {
            $return['error'] = 1;
            $return['info'] = '［ <b>' . $name . '</b> ］ 城市下没有已开启的区域！请先开启区域或删除此城市';
        }
        return $return;
    }
    /**
     * 获取街道
     */
    public function getStreet($param)
    {
        $list=(new AreaStreet())->getSome(['area_pid'=>$param['area_id'],'is_open'=>1],'area_id,area_name','area_sort desc,area_id desc')->toArray();
        return $list;
    }
    /**
     * 获取省市区的三级联动
     * @param string $childName 下级键名
     * @param string $indexName 返回的地区id的别名
     * @param string $textName 返回的地区名称别名
     * @param bool $withSelect  市区第一个是否显示请选择
     * @author 朱梦群
     */
    public function getAllArea($type,$fields='*', $childName = 'children',$is_circle=false,$indexName='area_id', $textName = 'area_name',$withSelect = false)
    {
        if($indexName != 'area_id' || $textName != 'area_name'){// 自定义返回数据的键值名称
            $fields = 'area_id as '.$indexName.', area_name as '.$textName.', area_pid, area_type';
        }
        $arr = $this->areaObj->getAreaListByCondition([['is_open', '=', 1], ['area_type', '<>', 4]], ['area_sort' => 'DESC', 'area_id' => 'ASC'],$fields);
        if($type == 1){//返回省市
            if (!empty($arr)) {
                $arr = $arr->toArray();
                foreach ($arr as $val) {
                    if ($val['area_type'] == 1) {
                        $farr[] = $val;
                    } else if ($val['area_type'] == 2) {
                        $carr[] = $val;
                    }
                }
                if(!empty($farr)){
                    foreach ($farr as $key => $val1) {
                        if(!empty(!empty($carr))){
                            foreach ($carr as $val2) {
                                if ($val1['area_id'] == $val2['area_pid']) {
                                    $farr[$key]['children'][] = $val2;
                                }
                            }
                        }
                    }
                }

            }
        }else{
            if(!$is_circle){
                if (!empty($arr)) {//返回省市区
                    $arr = $arr->toArray();
                    foreach ($arr as $val) {
                        if ($val['area_type'] == 1) {
                            $farr[] = $val;
                        } else if ($val['area_type'] == 2) {
                            $carr[] = $val;
                        } else if ($val['area_type'] == 3){
                            $garr[] = $val;
                        }
                    }
                    if(!empty($carr)){
                        foreach ($carr as $key => $val1) {
                            if(!empty($garr)){
                                foreach ($garr as $val2) {
                                    if ($val1[$indexName] == $val2['area_pid']) {
                                        if (!isset($carr[$key][$childName]) && $withSelect) {
                                            $carr[$key][$childName][] = [
                                                $indexName => 0,
                                                $textName => "全部区"
                                            ];
                                        }
                                        $carr[$key][$childName][] = $val2;
                                    }
                                }
                            }
                        }
                    }
                    if(!empty($farr)){
                        foreach ($farr as $key => $val1) {
                            if(!empty(!empty($carr))){
                                foreach ($carr as $val2) {
                                    if ($val1[$indexName] == $val2['area_pid']) {
                                        if (!isset($farr[$key][$childName]) && $withSelect) {
                                            $farr[$key][$childName][] = [
                                                $indexName => 0,
                                                $textName => "全部市"
                                            ];
                                        }
                                        $farr[$key][$childName][] = $val2;
                                    }
                                }
                            }
                        }
                    }

                }
            }else{
                $arr = $this->areaObj->getAreaListByCondition([['is_open', '=', 1], ['area_type', '<=', 4]], ['area_sort' => 'DESC', 'area_id' => 'ASC'],$fields);
                if (!empty($arr)) {//返回省市区
                    $arr = $arr->toArray();
                    foreach ($arr as $val) {
                        if ($val['area_type'] == 1) {
                            $farr[] = $val;
                        } else if ($val['area_type'] == 2) {
                            $carr[] = $val;
                        } else if ($val['area_type'] == 3){
                            $garr[] = $val;
                        }else if ($val['area_type'] == 4){
                            $garr4[] = $val;
                        }
                    }
                    if(!empty($garr)){
                        foreach ($garr as $key => $val1) {
                            if(!empty($garr4)){
                                foreach ($garr4 as $val2) {
                                    if ($val1[$indexName] == $val2['area_pid']) {
                                        if (!isset($garr[$key][$childName]) && $withSelect) {
                                            $garr[$key][$childName][] = [
                                                $indexName => 0,
                                                $textName => "全部街道"
                                            ];
                                        }
                                        $garr[$key][$childName][] = $val2;
                                    }
                                }
                            }
                        }
                    }

                    if(!empty($carr)){
                        foreach ($carr as $key => $val1) {
                            if(!empty($garr)){
                                foreach ($garr as $val2) {
                                    if ($val1[$indexName] == $val2['area_pid']) {
                                        if (!isset($carr[$key][$childName]) && $withSelect) {
                                            $carr[$key][$childName][] = [
                                                $indexName => 0,
                                                $textName => "全部区"
                                            ];
                                        }
                                        $carr[$key][$childName][] = $val2;
                                    }
                                }
                            }
                        }
                    }
                    if(!empty($farr)){
                        foreach ($farr as $key => $val1) {
                            if(!empty(!empty($carr))){
                                foreach ($carr as $val2) {
                                    if ($val1[$indexName] == $val2['area_pid']) {
                                        if (!isset($farr[$key][$childName]) && $withSelect) {
                                            $farr[$key][$childName][] = [
                                                $indexName => 0,
                                                $textName => "全部市"
                                            ];
                                        }
                                        $farr[$key][$childName][] = $val2;
                                    }
                                }
                            }
                        }
                    }

                }
            }
        }


        return $farr;
    }

    /**
     * 获取省市区的四级联动
     * @param string $childName 下级键名
     */
    public function getAllAreaAndStreet($fields='*', $childName = 'children')
    {
        $arr = $this->areaObj->getAreaListByCondition([['is_open', '=', 1], ['area_type', '<=', 3]], ['area_sort' => 'DESC', 'area_id' => 'ASC'],$fields);
        if (!empty($arr)) {//返回省市区商圈
            $arr = $arr->toArray();
            foreach ($arr as $val) {
                if ($val['area_type'] == 1) {
                    $farr[] = $val;
                } else if ($val['area_type'] == 2) {
                    $carr[] = $val;
                } else if ($val['area_type'] == 3){
                    $garr[] = $val;
                }else if ($val['area_type'] == 4){
                    $garr4[] = $val;
                }
            }
            if(!empty($garr)){
                foreach ($garr as $key => $val1) {
                    $streetArr = (new AreaStreet())->getSome(['area_pid'=>$val1['area_id'],'is_open'=>1],'area_pid,area_id,area_name,area_id as value,area_name as label')->toArray();
                    if(empty($streetArr)){
                        if(!empty($garr4)){
                            foreach ($garr4 as $val2) {
                                if ($val1['area_id'] == $val2['area_pid']) {
                                    $garr[$key][$childName][] = $val2;
                                }
                            }
                        }
                    }else{
                        foreach ($streetArr as  $key3 => $val3) {
                            $streetArr[$key3]['area_type']=5;
                            if(!empty($garr4)) {
                                foreach ($garr4 as $val2) {
                                    if ($val1['area_id'] == $val2['area_pid']) {
                                        $streetArr[$key3][$childName][] = $val2;
                                    }
                                }
                            }
                        }

                        $garr[$key][$childName] = $streetArr;
                    }
                }
            }

            if(!empty($carr)){
                foreach ($carr as $key => $val1) {
                    if(!empty($garr)){
                        foreach ($garr as $val2) {
                            if ($val1['area_id'] == $val2['area_pid']) {
                                $carr[$key][$childName][] = $val2;
                            }
                        }
                    }
                }
            }
            if(!empty($farr)){
                foreach ($farr as $key => $val1) {
                    if(!empty(!empty($carr))){
                        foreach ($carr as $val2) {
                            if ($val1['area_id'] == $val2['area_pid']) {
                                $farr[$key][$childName][] = $val2;
                            }
                        }
                    }
                }
            }

        }
        return $farr;
    }

    //获取城市下属的区域和商圈tree
    public function getAreaAndCircle($city_id = 0){
        if(empty($city_id)) $city_id = cfg("now_city");
        if(empty($city_id)){
            throw new \Exception("未设置默认城市");            
        }
        $now_city = $this->getAreaByAreaId($city_id);

        $area = $this->areaObj->getAreaListByCondition([['is_open', '=', 1], ['area_pid', '=', $city_id]], ['area_sort' => 'DESC', 'area_id' => 'ASC'],'area_id, area_name');
        if($area){
            foreach ($area as $key => $value) {
                $area[$key]['sons'] = $this->areaObj->getAreaListByCondition([['is_open', '=', 1], ['area_pid', '=', $value['area_id']]], ['area_sort' => 'DESC', 'area_id' => 'ASC'],'area_id, area_name');
                if($area[$key]['sons']){
                    $area[$key]['sons'] = $area[$key]['sons']->toArray();
                }
                else{
                    $area[$key]['sons'] = [];
                }
                $area[$key]['sons'] = array_merge([['area_id'=>0, 'area_name'=>"全部"]], $area[$key]['sons']);
            }

            $area = array_merge([['area_id'=>0, 'area_name' => "全".$now_city['area_name'], 'sons'=>[['area_id'=>0, 'area_name'=>"全部"]]]], $area->toArray());
            return $area;
        }
        return [];
    }

    //获取区域
    public function getAreaAndOneCircle($area_id,$fields){
        $now_city = (new AreaModel())->where(['area_id'=>$area_id])->field($fields)->find();
        return $now_city;
    }

    // 获得省市区
    public function getAreaString($where,$fields){
        $now_city = (new AreaModel())->where($where)->field($fields)->select();
        return $now_city;
    }

    /**
     * 获取省市二级树状图数据，后续开发人员可根据情况拓展为三级作为公用
     *
     * @return void
     * @author: zt
     * @date: 2023/12/06
     */
    public function getProvinceCity()
    {
        $areMod = new AreaModel();
        $allAreas = $areMod->where('is_open', '=', 1)->where('area_type', '<', 3)->field('area_id AS value,area_pid,area_name AS text')->order('area_sort DESC')->select()->toArray();

        //获取省份
        $lv1 = array_values(array_filter($allAreas, function ($r) {
            return $r['area_pid'] == 0;
        }));

        foreach ($lv1 as $k => $v) {
            //获取城市
            $lv1[$k]['children'] = array_values(array_filter($allAreas, function ($r) use ($v) {
                return $r['area_pid'] == $v['value'];
            }));
        }
        return $lv1;
    }
}