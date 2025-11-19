<?php

namespace app\api\model\service\building;

use think\facade\Db;

class ShoppingMallService
{
    //创建商场
    public function create($params)
    {
        $name = isset($params['name']) ? trim($params['name']) : '';
        $shortName = isset($params['short_name']) ? trim($params['short_name']) : '';
        $lng = isset($params['lng']) ? trim($params['lng']) : '';
        $lat = isset($params['lat']) ? trim($params['lat']) : '';
        $address = isset($params['address']) ? trim($params['address']) : '';
        $phone = isset($params['phone']) ? trim($params['phone']) : '';
        $area = isset($params['area']) ? floatval($params['area']) : 0;
        $director = isset($params['director']) ? trim($params['director']) : '';
        $introduction = isset($params['introduction']) ? trim($params['introduction']) : '';


        if (empty($name)) {
            throw new \Exception('商场名称不能为空');
        }
        if (empty($shortName)) {
            throw new \Exception('商场简称不能为空');
        }
        if (abs($lng) == 0 || abs($lat) == 0) {
            throw new \Exception('经纬度不能为空');
        }
        if (empty($address)) {
            throw new \Exception('地址不能为空');
        }
        if ($area <= 0) {
            throw new \Exception('商场面积不能小于0');
        }

        //商城名称+地址唯一判断
        $mall = Db::name('shopping_mall')->where('name', '=', $name)->where('address', '=', $address)->find();
        if ($mall) {
            return ['mall_id' => $mall['id'], 'name' => $mall['name']];
        } else {
            $data = [
                'name' => $name,
                'short_name' => $shortName,
                'lng' => $lng,
                'lat' => $lat,
                'address' => $address,
                'phone' => $phone,
                'area' => $area,
                'director' => $director,
                'introduction' => $introduction,
            ];
            $mallId = Db::name('shopping_mall')->insertGetId($data);
            return ['mall_id' => $mallId, 'name' => $name];
        }
    }
}
