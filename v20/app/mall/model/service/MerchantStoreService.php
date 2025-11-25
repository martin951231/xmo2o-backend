<?php
/**
 * MerchantStoreService.php
 * 店铺
 * Create on 2020/9/11 11:18
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\mall\model\db\MerchantStore;
use map\Coordinate;

class MerchantStoreService
{
    public function __construct()
    {
        $this->storeModel = new MerchantStore();
    }

    /**
     * 根据商家id获得店铺
     * @param $mer_id
     * @return array
     */
    public function getStoreByMerId($mer_id)
    {
        $where = ['mer_id' => $mer_id, 'status' => 1];
        $arr = $this->storeModel->getStoreByCondition($where);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 通过店铺id获得店铺信息
     * @param $store_id
     * @return array
     */
    public function getStoreByStoreId($store_id)
    {
        $where = ['store_id' => $store_id, 'status' => 1];
        $arr = $this->storeModel->getStoreByCondition($where);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }
    public function getOne($store_id)
    {
        $where = ['store_id' => $store_id, 'status' => 1];
        $arr = $this->storeModel->getOne($where);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }
    public function getStoreList($where,$page,$pageSize)
    {
        $field = 'name,store_id';
        $arr = $this->storeModel->getStoreList($where,$field,$page,$pageSize);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }
    public function getStoreList1($where)
    {
        $field = 'name,store_id';
        $arr = $this->storeModel->getStoreList1($where,$field);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    public function merchantStoreList(array $params)
    {
        $merchantStoreList = MerchantStore::where('have_mall', 1)
            ->where('status', 1)
            ->with(['merchant' => function ($query) {
                $query->field('mer_id, name, phone');
            }, 'mall_delivery' => function ($query) {
                $query->field('store_id, delivery_range_type, custom_id, delivery_radius, delivery_range_polygon,status');
            }])
            ->withSearch(['store_name'], $params)
            ->field('store_id, mer_id, name, adress, phone, add_time, long, lat')
            ->order('store_id asc')
            ->paginate(min($params['page_size'], 100))
            ->each(function ($item) {
                $item['merchant_name']  = $item->merchant->name;
                $item['merchant_phone'] = $item->merchant->phone;
                $item['add_time']       = date('Y-m-d H:i:s', $item->add_time);
                if (cfg('tianditu_web_key')) {
                    list($item->long,$item->lat) = Coordinate::bd09ToWgs84($item->long,$item->lat);
                }
                
                if(!empty($item->mall_delivery)){
                    $deliveryRangePolygon = trim(trim($item->mall_delivery->delivery_range_polygon,"POLYGON(("),"))");
                    if(!empty($deliveryRangePolygon)){
                        $deliveryRangePolygon = explode(',',$deliveryRangePolygon);
                        foreach ($deliveryRangePolygon as &$v){
                            $range = explode(' ',$v);
                            $v = [
                                'lat' => $range[1]*1,
                                'lng' => $range[0]*1
                            ];
                            if (cfg('tianditu_web_key')) {
                                list($v['lng'],$v['lat']) = Coordinate::bd09ToWgs84($v['lng'],$v['lat']);
                            }
                        }
                        $item->mall_delivery['delivery_range_polygon'] = $deliveryRangePolygon;
                    }
                }
            });

        return $merchantStoreList;
    }
}