<?php


namespace app\common\model\service;

use map\Coordinate;
use map\Tianditu;

class BaiduMapService
{
    public function reverseGeocoding($params){
        $lat = $params['lat'];
        $lng = $params['lng'];
        if(!$lat||!$lng){
            throw new \think\Exception(L_('经纬度不能为空！'));
        }
        $param['ak'] = cfg('baidu_map_ak');
        $param['location'] = $lat.','.$lng;
        $param['output']   = 'json';
        $param['extensions_poi']   = '1';
        $url = 'https://api.map.baidu.com/reverse_geocoding/v3';
        $getUrl = $url . "?" . http_build_query($param);
        $curl = curl_init(); // 初始化curl
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查   
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_URL, $getUrl); // 抓取指定网页
        curl_setopt($curl, CURLOPT_TIMEOUT, 1000); // 设置超时时间1秒
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // curl不直接输出到屏幕
        curl_setopt($curl, CURLOPT_HEADER, 0); // 设置header
        $data = curl_exec($curl); // 运行curl
        curl_close($curl);
        $data = !is_array($data)?json_decode($data,true):$data;
        if($data['status']!=0){
            fdump_api($data,'baidu/result',1);
            if (cfg('tianditu_key')) {
                list($tlng, $tlat) = Coordinate::bd09ToWgs84($lng, $lat);
                $data = Tianditu::reverseGeocoder($tlng, $tlat, cfg('tianditu_key'));
                if($data['status']==0){
                    $data['result']['formatted_address_poi'] = $data['result']['formatted_address'];
                }else{
                    throw new \think\Exception(L_('请求失败！'));
                }
            }else{
                throw new \think\Exception(L_('请求失败！'));
            }
        }
        return $data;
    }
}