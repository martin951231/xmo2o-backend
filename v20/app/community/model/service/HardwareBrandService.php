<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/1/20 13:20
 */
namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;

/**
 * @author : liukezhu
 * @date : 2022/1/17
 */
class HardwareBrandService
{

    public $config_url=['base_url' => 'https://o2o-service.pigcms.com/'];
    public $http;

    public function _initialize()
    {
    }

    /**
     * 获取品牌
     * @return array
     * @author: liukezhu
     * @date : 2022/1/17
     */
    public function getBrand($type=0)
    {
        $ConfigCustomizationService=new ConfigCustomizationService();
        $judgeAnakeBrand        = $ConfigCustomizationService->getAnakeBrandJudge();
        
        $return = http_request($this->config_url['base_url'] . 'hardwareBrand.php?type='.$type);
        $data = [];
        $key = md5('getBrand');
        if (!$type && cache($key)) {
            return json_decode(cache($key), true);
        }
        if (!empty($return) && isset($return[1])) {
            $return = json_decode($return[1], true);
            if (isset($return['data'])) {
                foreach ($return['data'] as $v){
                    if ('brand_anake'     == $v['brand_key'] && !$judgeAnakeBrand) {
                        continue;
                    }
//                    if ('brand_haikang' == $v['brand_key']) {
//                        // 先去除海康厂商
//                        continue;
//                    }
                    $data[] = [
                        'id'              => $v['id'],
                        'brand_id'        => $v['id'],
                        'name'            => $v['title'],
                        'brand_key'       => $v['brand_key'],
                        'sub_title'       => $v['sub_title'],
                        'logo'            => $v['logo'],
                    ];
                }
                cache($key,json_encode($data,JSON_UNESCAPED_UNICODE),86400);
            }
        }
        return $data;
    }


    /**获取品牌下面类型
     * @return array
     * @author: liukezhu
     * @date : 2022/1/17
     */
    public function getType($brand_id = 0, $type = 0)
    {
        $url = $this->config_url['base_url'] . 'hardwareBrandSeries.php';
        $brand_id = intval($brand_id);
        $type = intval($type);
        $key = md5('getBrandType');
        $params = [];
        if ($brand_id) {
            $params['brand_id'] = $brand_id;
        }
        if ($type) {
            $params['sub_series_type'] = $type;
        }
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        if (!$brand_id) {
            if (cache($key)) {
                return json_decode(cache($key), true);
            }
        }
        $return = http_request($url);
        $data = [];
        if (!empty($return)) {
            $return = json_decode($return[1], true);
            foreach ($return['data'] as $v) {
                $data[] = $v;
            }
        }
        if (!$brand_id && $data) {
            cache($key, json_encode($data, JSON_UNESCAPED_UNICODE), 86400);
        }
        return $data;
    }
}