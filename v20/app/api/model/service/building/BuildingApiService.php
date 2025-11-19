<?php

namespace app\api\model\service\building;

use net\Http;

//O2O调用楼宇API服务类
class BuildingApiService
{
    //默认POST请求调用
    public function invoke($path, $businessData)
    {
        $buildingDomain = cfg('building_domain');
        $buildingAppkey = cfg('building_appkey');
        if (empty($buildingDomain) || empty($buildingAppkey)) {
            throw new \Exception('接口配置错误');
        }
        $url = $buildingDomain . $path;
        $param = [
            'appkey' => $buildingAppkey,
        ];
        $param = array_merge($param, $businessData);
        $header = ['Content-Type: application/json'];
        $res = Http::curlPostOwnWithHeader($url, json_encode($param), $header);
        $res = $res ? json_decode($res, true) : [];

        if (empty($res)) {
            throw_exception('请求异常');
        }
        if ($res['code'] != 1000) {
            throw_exception($res['msg']);
        }
        return $res['data'] ?? [];
    }

    //GET请求调用
    public function invokeGet($path, $businessData)
    {
        $buildingDomain = cfg('building_domain');
        $buildingAppkey = cfg('building_appkey');
        if (empty($buildingDomain) || empty($buildingAppkey)) {
            throw_exception('接口配置错误');
        }
        $url = $buildingDomain . $path;
        $param = [
            'appkey' => $buildingAppkey,
        ];
        $param = array_merge($param, $businessData);
        $res = Http::curlGet($url . '?' . http_build_query($param));
        $res = $res ? json_decode($res, true) : [];

        if (empty($res)) {
            throw_exception('请求异常');
        }
        if ($res['code'] != 1000) {
            throw_exception($res['msg']);
        }
        return $res['data'] ?? [];
    }
}
