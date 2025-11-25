<?php
/**
 * 城市区域service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/07/03 14:43
 */

namespace app\merchant\model\service;

use app\common\model\service\admin_user\AdminUserService;
use app\common\model\service\AreaService as CommomAreaService;
use token\Token;
use net\IpLocation;

class AreaService
{
    public $commomAreaService = null;

    public function __construct()
    {
        $this->commomAreaService = new CommomAreaService();
    }

    /**
     * 获得当前所在城市区域信息
     * @return array
     */
    public function getLocation()
    {
        $return['many_city'] = false;
        if (cfg('many_city')) {
            //演示站直接使用 合肥
            if (cfg('is_demo_domain')) {
                $city = L_('安徽省合肥市');
            } else {
                //通过IP得到当前IP的地理位置
                $Ip = new IpLocation('UTFWry.dat');
                $area = $Ip->getlocation();
                $city = iconv('gbk', 'utf-8', $area['country']);
            }

            //判断数据库里存不存在当前的城市
            $condition_now_city['area_type'] = '2';
            $condition_now_city['area_ip_desc'] = $city;
            $condition_now_city['is_open'] = '1';
            $nowCity = $this->commomAreaService->getOne($condition_now_city);
            if (!empty($nowCity)) {
                $return['city'] = $nowCity;
            }
            $return['many_city'] = true;
        }

        if (empty($return['city'])) {
            $return['city'] = $this->commomAreaService->getAreaByAreaId(cfg('now_city'));
        }

        $return['province'] = $this->commomAreaService->getAreaByAreaId($return['city']['area_pid']);

        $return = array(
            'city_id' => $return['city']['area_id'],
            'city_name' => $return['city']['area_name'],
            'province_id' => $return['province']['area_id'],
            'province_name' => $return['province']['area_name'],
            'many_city' => $return['many_city'],
        );
        return $return;
    }

    /**
     * 获得所有省份
     * @return array
     */
    public function getProvinceList($param)
    {
        // 搜索条件
        $where = [];

        $openLimit = isset($param['open_limit']) ? $param['open_limit'] : '';
        if ($openLimit) {//不受开启状态的限制（2应该表示已删除）
            $where[] = ['is_open', '<>', 2];
        } else {
            $where[] = ['is_open', '=', 1];
        }

        $where[] = ['area_type', '=', 1];
        $data = $this->commomAreaService->getAreaListByCondition($where);
        $provinceLst = array();
        foreach ($data as $key => $value) {
            $temp = array(
                'id' => $value['area_id'],
                'name' => $value['area_name']
            );
            $provinceLst[] = $temp;
        }
        /* if(count($provinceLst) == 1){
             $return['error'] = 2;
             $return['id'] = $provinceLst[0]['id'];
             $return['name'] = $provinceLst[0]['name'];
         }else */
        if (!empty($provinceLst)) {
            $return['error'] = 0;
            $return['list'] = $provinceLst;
        } else {
            $return['error'] = 1;
            $return['info'] = L_('没有已开启的省份！');
        }
        return $return;
    }

    /**
     * 获得所选省份下所有城市
     * @return array
     */
    public function getCityList($param)
    {
        $id = isset($param['id']) ? $param['id'] : '';
        $name = isset($param['name']) ? $param['name'] : '';
        $type = isset($param['type']) ? $param['type'] : '';
        $where[] = ['area_pid', '=', $id];
        $where[] = ['is_open', '=', 1];
        $data = $this->commomAreaService->getAreaListByCondition($where);

        $cityList = array();
        foreach ($data as $key => $value) {
            $temp = array(
                'id' => $value['area_id'],
                'name' => $value['area_name']
            );
            $cityList[] = $temp;
        }
        if (count($cityList) == 1 && !$type) {
            $return['error'] = 2;
            $return['id'] = $cityList[0]['id'];
            $return['name'] = $cityList[0]['name'];
        } else if (!empty($cityList)) {
            $return['error'] = 0;
            $return['list'] = $cityList;
        } else {
            $return['error'] = 1;
            $return['info'] = $name . ' 省份下没有已开启的城市！';
        }
        return $return;
    }

    /**
     * 获得所选城市下所有区域
     * @return array
     */
    public function getAreaList($param)
    {
        $id = isset($param['id']) ? $param['id'] : '';
        $name = isset($param['name']) ? $param['name'] : '';

        $where = [];
        $where[] = ['area_pid', '=', $id];
        $where[] = ['is_open', '=', 1];
        $data = $this->commomAreaService->getAreaListByCondition($where);

        $areaList = array();
        foreach ($data as $key => $value) {
            $temp = array(
                'id' => $value['area_id'],
                'name' => $value['area_name']
            );
            $areaList[] = $temp;
        }
        if (!empty($areaList)) {
            $return['error'] = 0;
            $return['list'] = $areaList;
        } else {
            $return['error'] = 1;
            $return['info'] = $name . ' 城市下没有开启了的区域！';
        }
        return $return;
    }
}