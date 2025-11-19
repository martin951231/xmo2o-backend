<?php

//坐标转换 
//‌天地图‌：使用CGCS2000坐标系。CGCS2000与WGS-84坐标系非常接近，两者在大多数情况下可以互换使用‌
//‌百度地图‌：使用BD-09坐标系。BD-09是在GCJ-02（火星坐标系）基础上进行二次加密得到的，具有更高的安全性和隐私保护‌
//‌高德地图‌：使用GCJ-02坐标系。GCJ-02是基于WGS-84坐标系进行加密处理得到的，主要用于国内地图服务‌
//‌腾讯地图‌：使用GCJ-02坐标系。与高德地图相同，腾讯地图也采用GCJ-02坐标系‌
class Coordinate
{
    //天地图转百度地图
    public static function wgs84ToBd09($wgs84_lng, $wgs84_lat)
    {
        $gcj = self::wgs84ToGcj02($wgs84_lng, $wgs84_lat);
        return self::gcj02ToBd09($gcj[0], $gcj[1]);
    }

    //天地图转腾讯/高德地图
    public static function wgs84ToGcj02($wgs84_lng, $wgs84_lat)
    {
        if (self::outOfChina($wgs84_lng, $wgs84_lat)) {
            return array($wgs84_lng, $wgs84_lat);
        }
        $dLat = self::transformLat($wgs84_lng - 105.0, $wgs84_lat - 35.0);
        $dLng = self::transformLng($wgs84_lng - 105.0, $wgs84_lat - 35.0);
        $radLat = $wgs84_lat / 180.0 * M_PI;
        $magic = sin($radLat);
        $magic = 1 - 0.00669342162296594323 * $magic * $magic;
        $sqrtMagic = sqrt($magic);
        $dLat = ($dLat * 180.0) / ((6378245.0 * (1 - 0.00669342162296594323)) / ($magic * $sqrtMagic) * M_PI);
        $dLng = ($dLng * 180.0) / (6378245.0 / $sqrtMagic * cos($radLat) * M_PI);
        $mgLat = $wgs84_lat + $dLat;
        $mgLng = $wgs84_lng + $dLng;
        return array($mgLng, $mgLat);
    }

    //高德/腾讯转百度地图
    public static function gcj02ToBd09($gcj_lng, $gcj_lat)
    {
        $x = $gcj_lng;
        $y = $gcj_lat;
        $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * M_PI * 3000.0 / 180.0);
        $theta = atan2($y, $x) + 0.000003 * cos($x * M_PI * 3000.0 / 180.0);
        $bd_lng = $z * cos($theta) + 0.0065;
        $bd_lat = $z * sin($theta) + 0.006;
        return array($bd_lng, $bd_lat);
    }

    //腾讯/高德转天地图
    public static function gcj02ToWgs84($gcj_lng, $gcj_lat)
    {
        $d = self::transformLat($gcj_lng - 105.0, $gcj_lat - 35.0);
        $e = self::transformLng($gcj_lng - 105.0, $gcj_lat - 35.0);
        $radLat = $gcj_lat / 180.0 * M_PI;
        $magic = sin($radLat);
        $magic = 1 - 0.00669342162296594323 * $magic * $magic;
        $sqrtMagic = sqrt($magic);
        $dLat = ($d * 180.0) / ((6378245.0 * (1 - 0.00669342162296594323)) / ($magic * $sqrtMagic) * M_PI);
        $dLng = ($e * 180.0) / (6378245.0 / $sqrtMagic * cos($radLat) * M_PI);
        $mgLat = $gcj_lat + $dLat;
        $mgLng = $gcj_lng + $dLng;
        return array($gcj_lng * 2 - $mgLng, $gcj_lat * 2 - $mgLat);
    }

    //百度转腾讯/高德
    public static function bd09ToGcj02($bd_lng, $bd_lat)
    {
        $x = $bd_lng - 0.0065;
        $y = $bd_lat - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * M_PI * 3000.0 / 180.0);
        $theta = atan2($y, $x) - 0.000003 * cos($x * M_PI * 3000.0 / 180.0);
        $gg_lng = $z * cos($theta);
        $gg_lat = $z * sin($theta);
        return array($gg_lng, $gg_lat);
    }

    //百度转天地图
    public static function bd09ToWgs84($bd_lng, $bd_lat)
    {
        $gcj = self::bd09ToGcj02($bd_lng, $bd_lat);
        return self::gcj02ToWgs84($gcj[0], $gcj[1]);
    }


    public static function outOfChina($lng, $lat)
    {
        return ($lng < 72.004 || $lng > 137.8347) || ($lat < 0.8293 || $lat > 55.8271);
    }

    public static function transformLat($x, $y)
    {
        $ret = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y + 0.2 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * M_PI) + 20.0 * sin(2.0 * $x * M_PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($y * M_PI) + 40.0 * sin($y / 3.0 * M_PI)) * 2.0 / 3.0;
        $ret += (160.0 * sin($y / 12.0 * M_PI) + 320 * sin($y * M_PI / 30.0)) * 2.0 / 3.0;
        return $ret;
    }

    public static function transformLng($x, $y)
    {
        $ret = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * M_PI) + 20.0 * sin(2.0 * $x * M_PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($x * M_PI) + 40.0 * sin($x / 3.0 * M_PI)) * 2.0 / 3.0;
        $ret += (150.0 * sin($x / 12.0 * M_PI) + 300.0 * sin($x / 30.0 * M_PI)) * 2.0 / 3.0;
        return $ret;
    }
}
