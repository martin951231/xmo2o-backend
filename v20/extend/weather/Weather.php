<?php

namespace weather;

use net\Http;
use think\Exception;

class Weather
{
    const WEATHER_EXPIRE_TIME = 60*10;
    private $url = 'https://devapi.qweather.com/v7/weather/now?location=%s&key=%s';
    private $weatherKey;

    public function __construct()
    {
        $this->weatherKey = cfg('weather_key');
    }

    public function getWeatherInfo($area, $location)
    {
        if(cache($area)){
            return json_decode(cache($area), true);
        }

        if(empty($this->weatherKey)){
            throw new Exception('天气参数未配置', 1009);
        }

        //获取
        $url = sprintf($this->url, $location, $this->weatherKey);
        
        $response = Http::curlGet($url);
        $result = json_decode($response, true);
        
        if ($result['code'] != 200) {
            $errorMsg = $this->codeInfo($result['code']);
            if(empty($errorMsg)){
                $errorMsg = '获取天气信息失败，请重试！';
            }
            throw new Exception($errorMsg, 1009);
        } else if (isset($result['now']) && $result['now']) {
            $icon = app()->getRootPath() . '../static/images/weather/icons/' . $result['now']['icon'] . '.png';
            if (file_exists($icon)) {
                $result['now']['weather_icon'] = cfg('site_url') . '/static/images/weather/icons/' . $result['now']['icon'] . '.png';
            }
        }
        
        cache($area,json_encode($result['now'],JSON_UNESCAPED_UNICODE), self::WEATHER_EXPIRE_TIME);
                    
        return $result['now'];
    }
    
    protected function codeInfo($code)
    {
        $codeInfo = [
            200 => '请求成功',
            204 => '请求成功，但你查询的地区暂时没有你需要的数据。',
            400 => '请求错误，可能包含错误的请求参数或缺少必选的请求参数。',
            401 => '认证失败，可能使用了错误的KEY、数字签名错误、KEY的类型错误（如使用SDK的KEY去访问Web API）。',
            402 => '超过访问次数或余额不足以支持继续访问服务，你可以充值、升级访问量或等待访问量重置。',
            403 => '无访问权限，可能是绑定的PackageName、BundleID、域名IP地址不一致，或者是需要额外付费的数据。',
            404 => '查询的数据或地区不存在。',
            429 => '超过限定的QPM（每分钟访问次数）',
        ];
        
        return $codeInfo[$code] ?? '';
    }
}