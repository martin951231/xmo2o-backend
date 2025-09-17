<?php
/**
 * @author : liukezhu
 * @date : 2022/1/17
 */
class HardwareBrandModel extends Model{

    public $config;
    public $http;
    public function _initialize() {
        parent::_initialize();
        import('ORG.Net.Http');
        $this->http = new Http();
        $this->config=[
            'base_url'=>'https://o2o-service.pigcms.com/'
        ];
    }

    /**
     * 获取品牌
     * @author: liukezhu
     * @date : 2022/1/17
     * @return array
     */
    public function getBrand($type = 0)
    {
        $data = [];
        $key = md5('getBrandList'.$type);
        $judgeDhBrand           = D('Config_customization')->getDhBrandJudge();
        $judgeTaiShouBrand      = D('Config_customization')->getTaiShouBrandJudge();
        $judgeZghlBrand         = D('Config_customization')->getZghlBrandJudge();
        $judgeCloudWalkBrand    = D('Config_customization')->getCloudWalkBrandJudge();
        $judgeHasxBrand         = D('Config_customization')->getHasxBrandJudge();
        $judgeLanNiuBrand       = D('Config_customization')->getLanNiuBrandJudge();
        $judgeAnakeBrand        = D('Config_customization')->getAnakeBrandJudge();
        $judgeDuoPuBrand        = D('Config_customization')->getDuoPuBrandJudge();
        $judgeHaoBoXinBrand     = D('Config_customization')->getHaoBoXinBrandJudge();
        $judgeFaceCloud260Brand = D('Config_customization')->getFaceCloud260Judge();
        $judgeFaceXiHanDoorSwitch = D('Config_customization')->getFaceXiHanDoorSwitch();
        if (S($key)) {
            $brand_list = json_decode(S($key), true);
        }
        if (empty($brand_list)) {
            $url = $this->config['base_url'] . 'hardwareBrand.php?type=' . $type;
            $return = $this->http->curlGet($url);
            if (!empty($return)) {
                $return = json_decode($return, true);
                $brand_list = $return['data'];
            }
        }
        $isAddCloud260Brand = true;
        if (!$judgeFaceCloud260Brand) {
            $isAddCloud260Brand = false;
        }
        $isAddXiHanDoorBrand = true;
        if (!$judgeFaceXiHanDoorSwitch) {
            $isAddXiHanDoorBrand = false;
        }
        if (!empty($brand_list)) {
            foreach ($brand_list as $v) {
                if ('brand_dahua'     == $v['brand_key'] && !$judgeDhBrand) {
                    continue;         
                }                     
                if ('brand_taishou'   == $v['brand_key'] && !$judgeTaiShouBrand) {
                    continue;         
                }                     
                if ('brand_zghl'      == $v['brand_key'] && !$judgeZghlBrand) {
                    continue;
                }
                if ('brand_cloudwalk' == $v['brand_key'] && !$judgeCloudWalkBrand) {
                    continue;
                }
                if ('brand_hasx'      == $v['brand_key'] && !$judgeHasxBrand) {
                    continue;
                }
                if ('brand_lanniu'    == $v['brand_key'] && !$judgeLanNiuBrand) {
                    continue;
                }
                if ('brand_anake'     == $v['brand_key'] && !$judgeAnakeBrand) {
                    continue;
                }
                if ('brand_duopu'     == $v['brand_key'] && !$judgeDuoPuBrand) {
                    continue;
                }
                if ('brand_haoboxin'  == $v['brand_key'] && !$judgeHaoBoXinBrand) {
                    continue;
                }
                if ('brand_cloud260'  == $v['brand_key'] && !$judgeFaceCloud260Brand) {
                    $isAddCloud260Brand = false;
                    continue;
                }
                if ('brand_xihan'  == $v['brand_key'] && !$judgeFaceXiHanDoorSwitch) {
                    $isAddXiHanDoorBrand = false;
                    continue;
                }
                if ($judgeFaceXiHanDoorSwitch && 'brand_xihan'  == $v['brand_key']) {
                    $isAddXiHanDoorBrand = false;
                }
                $data[] = [
                    'id' => $v['id'],
                    'key' => trim($v['brand_key']),
                    'title' => trim($v['title']),
                    'sub_title' => trim($v['sub_title']),
                ];
            }
            S($key, json_encode($brand_list, JSON_UNESCAPED_UNICODE), 86400);
        }
        if ($isAddCloud260Brand) {
            $data[] = [
                'id' => '17',
                'key' => 'brand_cloud260',
                'title' => '260云',
                'sub_title' => '260云',
            ];
        }
        if ($isAddXiHanDoorBrand) {
            $data[] = [
                'id' => '18',
                'key' => 'brand_xihan',
                'title' => '西涵',
                'sub_title' => '西涵',
            ];
        }
        return $data;
    }

    public function curlGet($url, $timeout = 15)
    {
        $ch = curl_init();
        $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $temp = curl_exec($ch);
        return $temp;
    }

    /**获取品牌下面类型
     * @author: liukezhu
     * @date : 2022/1/17
     * @return array
     */
    public function getType($brand_id = 0, $type = 0)
    {
        $url = $this->config['base_url'] . 'hardwareBrandSeries.php';
        $brand_id = intval($brand_id);
        $type = intval($type);
        $key = md5('getBrandType_'.$brand_id.$type);
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
        if (S($key)) {
            return json_decode(S($key), true);
        }
        $return = $this->http->curlGet($url);
        $data = [];
        $isAddCloud260Brand = true;
        $isAddXiHanDoorBrand = true;
        if (!empty($return)) {
            $judgeAllFace = D('Config_customization')->getAllFaceJudge();
            if (!$judgeAllFace['judgeFaceCloud260Face']) {
                $isAddCloud260Brand = false;
            }
            $return       = json_decode($return, true);
            if (!$judgeAllFace['judgeFaceXiHanDoorSwitch']) {
                $isAddXiHanDoorBrand = false;
            }
            foreach ($return['data'] as $v) {
                if('taishou_a1'        == $v['series_key'] && !$judgeAllFace['judgeA1FaceDevice']){
                    continue;          
                }                      
                if('taishou_a2'        == $v['series_key'] && !$judgeAllFace['judgeA2FaceDevice']){
                    continue;          
                }                      
                if('zghl_d1'           == $v['series_key'] && !$judgeAllFace['judgeD1FaceDevice']){
                    continue;          
                }                      
                if('lanniu_d2'         == $v['series_key'] && !$judgeAllFace['judgeD2FaceDevice']){
                    continue;          
                }                      
                if('hasx_d3'           == $v['series_key'] && !$judgeAllFace['judgeD3FaceDevice']){
                    continue;          
                }                      
                if('lanniu_d4'         == $v['series_key'] && !$judgeAllFace['judgeD4FaceDevice']){
                    continue;          
                }                      
                if('cloudwalk_d5'      == $v['series_key'] && !$judgeAllFace['judgeD5FaceDevice']){
                    continue;          
                }                      
                if('dnake_renlian_d6'  == $v['series_key'] && !$judgeAllFace['judgeAnakeFace']){
                    continue;          
                }                      
                if('duopu_d9'          == $v['series_key'] && !$judgeAllFace['judgeDuoPuFace']){
                    continue;          
                }                      
                if('haoboxin_a8'       == $v['series_key'] && !$judgeAllFace['judgeHaoBoXinFace']){
                    continue;          
                }                      
                if('haoboxin_a8'       == $v['series_key'] && !$judgeAllFace['judgeHaoBoXinFace']){
                    continue;          
                }                      
                if('dahua_renlian_asi' == $v['series_key'] && !$judgeAllFace['judgeDhYunRui'] && !$judgeAllFace['judgeDhH8900']){
                    continue;
                }
                if ('brand_cloud260'  == $v['series_key'] && !$judgeAllFace['judgeFaceCloud260Face']) {
                    continue;
                }
                if ($judgeAllFace['judgeFaceCloud260Face'] && 'brand_cloud260'  == $v['series_key']) {
                    $isAddCloud260Brand = false;
                }
                if ($judgeAllFace['judgeFaceXiHanDoorSwitch'] && $brand_id == 18 && 'brand_xihan'  == $v['series_key']) {
                    $isAddXiHanDoorBrand = true;
                }
                $data[] = [
                    'id' => $v['id'],
                    'brand_id' => $v['brand_id'],
                    'key' => trim($v['series_key']),
                    'title' => trim($v['series_title']),
                    'sub_title' => trim($v['series_title']),
                    'sub_series_type' => trim($v['sub_series_type']),
                    'hardware_config' => $v['hardware_config'],
                    'device_type' => $v['device_type'],
                ];
            }
        }

        if ($isAddCloud260Brand && $brand_id == 17) {
            $data = [
                [
                    "id"=> "32",
                    "brand_id"=> "17",
                    "key"=> "brand_cloud260",
                    "title"=> "260云系列",
                    "sub_title"=> "260云系列",
                    "sub_series_type"=> "1",
                    "hardware_config"=> [],
                    "device_type"=> "30"
                ]
            ];
        }
        if ($isAddXiHanDoorBrand && $brand_id == 18) {
            $data = [
                [
                    "id"=> "33",
                    "brand_id"=> "18",
                    "key"=> "brand_xihan",
                    "title"=> "西涵门禁",
                    "sub_title"=> "西涵门禁",
                    "sub_series_type"=> "1",
                    "hardware_config"=> [],
                    "device_type"=> "31"
                ]
            ];
        }
        if ($data) {
            S($key, json_encode($data, JSON_UNESCAPED_UNICODE), 86400);
        }
        return $data;
    }
}