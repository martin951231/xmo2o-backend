<?php

namespace app\api\model\service\building;

use think\facade\Db;

class StoreService
{
    //自动创建店铺（名称、商家ID）
    public function create($params)
    {
        $name = isset($params['name']) ? trim($params['name']) : '';
        $merId = isset($params['mer_id']) ? intval($params['mer_id']) : 0;
        $mallId = isset($params['mall_id']) ? intval($params['mall_id']) : 0;
        $building = isset($params['building']) ? trim($params['building']) : '';
        $floor = isset($params['floor']) ? trim($params['floor']) : '';
        $room = isset($params['room']) ? trim($params['room']) : '';
        $floorNumber = isset($params['floor_number']) ? intval($params['floor_number']) : 0;
        $floorId = isset($params['floor_id']) ? intval($params['floor_id']) : 0;
        $brandName = isset($params['brand_name']) ? trim($params['brand_name']) : '';

        if (empty($name) || empty($merId)) {
            throw new \Exception('参数错误');
        }

        $merchant = Db::name('merchant')->where('mer_id', '=', $merId)->where('status', '<>', 4)->find();
        if (empty($merchant)) {
            throw new \Exception('商家不存在或已删除');
        }

        $store = Db::name('merchant_store')->where('name', '=', $name)->where('mer_id', '=', $merId)->where('status', '<>', 4)->find();
        if ($store) {
            //店铺存在
            $data = [
                'last_time' =>  time(),
                'mall_id' => $mallId,
                'building' => $building,
                'floor' => $floor,
                'room' => $room,
                'floor_number' => $floorNumber,
                'floor_id' => $floorId,
                'brand_name' => $brandName,
            ];
            $storeId = Db::name('merchant_store')->where('store_id', $store['store_id'])->update($data);
            return ['store_id' => $store['store_id'], 'name' => $store['name']];
        } else {
            //创建店铺
            $data = [
                'mer_id' => $merId,
                'name' => $name,
                'status' => 1,
                'add_time' => time(),
                'last_time' =>  time(),
                'mall_id' => $mallId,
                'building' => $building,
                'floor' => $floor,
                'room' => $room,
                'floor_number' => $floorNumber,
                'floor_id' => $floorId,
                'brand_name' => $brandName,
            ];
            $storeId = Db::name('merchant_store')->insertGetId($data);
            return ['store_id' => $storeId, 'name' => $name];
        }
    }


    public function bindShoppingMall($storeId, $mallId)
    {
        $store = Db::name('merchant_store')->where('store_id', '=', $storeId)->where('status', '<>', 4)->find();
        if (empty($store)) {
            throw new \Exception('店铺不存在或已删除');
        }

        if ($mallId > 0) {
            $shoppingMall = Db::name('shopping_mall')->where('id', '=', $mallId)->find();
            if (empty($shoppingMall)) {
                throw new \Exception('商场不存在');
            }
        }
        Db::name('merchant_store')->where('store_id', '=', $storeId)->update(['mall_id' => $mallId]);
        return true;
    }

    public function dailyReport($storeId, $date)
    {
        //线上刷卡支付：第三方在线支付、余额支付、一卡通支付（后续迭代）
        //线下刷卡支付：扫用户付款码
        //现金收入：线下支付
        //满减抵扣总额、优惠券抵扣总额
        $beginTime = strtotime($date);
        $endTime = $beginTime + 86400;
        $where = [
            ['store_id', '=', $storeId],
            ['pay_time', 'between', [$beginTime, $endTime]],
            ['paid', '=', 1]
        ];
        //第三方支付的算线上支付
        $onliePay = Db::name('system_order')->where($where)->where('pay_type', '<>', '')->where('pay_type', '<>', 'offline')->sum('price');
        //余额支付的算线下刷卡
        $offlinePay = Db::name('system_order')->where($where)->where('pay_type', '')->sum('price');
        //线下支付的算现金
        $cash = Db::name('system_order')->where($where)->where('pay_type', 'offline')->sum('price');

        return [
            'online_pay' => getFormatNumber($onliePay),
            'offline_pay' =>    getFormatNumber($offlinePay),
            'cash' =>    getFormatNumber($cash),
        ];
    }
}
