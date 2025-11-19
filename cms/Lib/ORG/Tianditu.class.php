<?php

//天地图服务

final class Tianditu
{

    //地理编码
    public static function geocoder($keyWord, $key)
    {
        import('ORG.Net.Http');
        $url = sprintf('http://api.tianditu.gov.cn/geocoder?ds={"keyWord":"%s"}&tk=%s', $keyWord, $key);
        $result = Http::curlGet($url);
        return json_decode($result, true);
    }

    //逆地理编码查询
    public static function reverseGeocoder($lon, $lat, $key)
    {
        import('ORG.Net.Http');
        $url = sprintf("http://api.tianditu.gov.cn/geocoder?postStr={'lon':%s,'lat':%s,'ver':1}&type=geocode&tk=%s", $lon, $lat, $key);
        $result = Http::curlGet($url);
        return json_decode($result, true);
    }

    //周边搜索服务
    public static function searchAround($keyWord, $pointLonlat, $key, $queryRadius = 10000, $start = 0, $count = 100)
    {
        import('ORG.Net.Http');
        $url = sprintf('http://api.tianditu.gov.cn/v2/search?postStr={"keyWord":"%s","level":12,"queryRadius":%s,"pointLonlat":"%s","queryType":3,"start":%s,"count":%s}&type=query&tk=%s', $keyWord, $queryRadius, $pointLonlat, $start, $count, $key);
        $result = Http::curlGet($url);
        return json_decode($result, true);
    }

    //行政区划区域搜索服务
    public static function searchAroundResultWithArea($keyWord, $specify, $key, $start = 0, $count = 100)
    {
        import('ORG.Net.Http');
        $url = sprintf('http://api.tianditu.gov.cn/v2/search?postStr={"keyWord":"%s","queryType":12,"start":%s,"count":%s,"specify":"%s"}&type=query&tk=%s', $keyWord, $start, $count, $specify, $key);
        $result = Http::curlGet($url);
        return json_decode($result, true);
    }

    //获取骑行距离（按照步行来算，一般步行和骑行路线是一致的）
    public static function getRidingDistance($origLonLat, $destLonLat, $key)
    {
        import('ORG.Net.Http');
        $url = sprintf('http://api.tianditu.gov.cn/drive?postStr={"orig":"%s","dest":"%s","style":"3"}&type=search&tk=%s', $origLonLat, $destLonLat, $key);
        $result = Http::curlGet($url);
        //xml转换成数组
        $result = json_decode(json_encode(simplexml_load_string($result)), true);
        return $result;
    }

    //获取驾车距离
    public static function getDriveDistance($origLonLat, $destLonLat, $key)
    {
        import('ORG.Net.Http');
        $url = sprintf('http://api.tianditu.gov.cn/drive?postStr={"orig":"%s","dest":"%s","style":"0"}&type=search&tk=%s', $origLonLat, $destLonLat, $key);
        $result = Http::curlGet($url);
        //xml转换成数组
        $result = json_decode(json_encode(simplexml_load_string($result)), true);
        return $result;
    }
}
