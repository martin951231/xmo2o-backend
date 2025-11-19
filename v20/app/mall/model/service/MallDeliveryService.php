<?php
declare (strict_types=1);

namespace app\mall\model\service;


use app\http\exceptions\ParametersException;
use app\mall\model\db\DeliverCustom;
use app\mall\model\db\MallDelivery;
use app\mall\model\db\MallGoodsArea;
use app\mall\model\db\MerchantStore;
use map\Coordinate;

class MallDeliveryService
{
    public function deliverySetting(array $params)
    {
        $this->checkDeliveryRangePolygon($params);
        $mallDelivery = MallDelivery::where('store_id', $params['store_id'])->find();
        empty($mallDelivery) && $mallDelivery = new MallDelivery();

        $mallDelivery->setAttrs($params);
        return  $mallDelivery->save();
    }
    
    public function checkDeliveryRangePolygon(&$params)
    {
        if ($params['delivery_range_type'] == 2) {
            if ($params['delivery_range_polygon']) {
                $latLngArray = explode('|', $params['delivery_range_polygon']);
                if (count($latLngArray) < 3) {
                    throw_exception('请绘制一个合理的服务范围！', ParametersException::class);
                } else {
                    $latLngData = [];
                    foreach ($latLngArray as $row) {
                        $latLng = explode('-', $row);
                        if (cfg('tianditu_web_key')) {
                            list($latLng[0], $latLng[1]) = Coordinate::wgs84ToBd09($latLng[0], $latLng[1]);
                        }
                        $latLngData[] = $latLng[0] . ' ' . $latLng[1];
                    }
                    $params['delivery_range_polygon'] = 'POLYGON((' . implode(',', $latLngData) . '))';
                }
            } elseif ($params['custom_id']) {
                $custom = DeliverCustom::where('id' , $params['custom_id'])->field('id')->find();
                if (empty($custom)) {
                    throw_exception('请选择正确的服务范围！', ParametersException::class);
                }
                $params['delivery_range_polygon'] = $custom['delivery_range_polygon'];
            } else {
                throw_exception('请绘制您的服务范围！', ParametersException::class);
            }
        }
    }
    
    public function deliverCustom()
    {
        return DeliverCustom::select()
            ->each(function ($item){
                $deliveryRangePolygon = trim(trim($item->delivery_range_polygon,"POLYGON(("),"))");
                if(!empty($deliveryRangePolygon)){
                    $deliveryRangePolygon = explode(',',$deliveryRangePolygon);
                    foreach ($deliveryRangePolygon as &$v){
                        $range = explode(' ',$v);
                        $v = [
                            'lat' => $range[1]*1,
                            'lng' => $range[0]*1
                        ];
                    }
                    $item['delivery_range_polygon'] = $deliveryRangePolygon;
                }
            });
    }
    
    public function checkRange($user_address,$store_ids){
        $is_out_range = 1;
        $is_in_range = 1;
        if($store_ids){
            $is_out_range = 0;
            $is_in_range = 0;
            foreach ($store_ids as $val){
                $mall_delivery = (new MallDelivery())->where(['status'=>1,'store_id'=>$val['store_id']])->findOrEmpty()->toArray();
                if($mall_delivery){
                    if($mall_delivery['delivery_range_type']==1){
                        $distance = floatval((get_distance($user_address['lat'],$user_address['lng'],$val['lat'],$val['long'])/1000));
                        if($distance>$mall_delivery['delivery_radius']&&empty($val['is_zt'])){
                            $is_out_range = 1;
                        }else{
                            $is_in_range = 1;
                        }
                    }else{
                        $range_polygon = substr($mall_delivery['delivery_range_polygon'], 9, strlen($mall_delivery['delivery_range_polygon']) - 11);
                        $range_arr = explode(',',$range_polygon);
                        $m_deliver_area = [];
                        //区域多边形数组
                        foreach ($range_arr as $vvv){
                            $lng_lat = explode(' ',$vvv);
                            $m_deliver_area[0][] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
                        }
                        if(!isPtInPolyV20($user_address['lng'],$user_address['lat'],$m_deliver_area)&&empty($val['is_zt'])){
                            $is_out_range = 1;
                        }else{
                            $is_in_range = 1;
                        }
                    }
                }else{
                    $is_in_range = 1; 
                }
            }
        }
        return ['is_out_range'=>$is_out_range,'is_in_range'=>$is_in_range];
    }

    /**
     * 验证定位是否在配送范围
     * @param $params
     */
    public function checkLatLngRangeOut($params){
        $lat = $params['lat']?:'';
        $lng = $params['lng']?:'';
        if(!$lat||!$lng){
            throw new \think\Exception(L_('经纬度不能为空'));
        }
        $is_out_range = 0;
        $note = '';
        if(cfg('open_mall_order_area')==1){
            $now_area = (new \app\common\model\service\address\AutoAddressService())->cityMatching($lat, $lng);
            $goods_area_id = [];
            $goods_area_id[] = 0;
            if(!empty($now_area['area_info']['area_id'])){
                $goods_area_id[] = $now_area['area_info']['area_id'];
            }
            if(!empty($now_area['area_info']['city_id'])){
                $goods_area_id[] = $now_area['area_info']['city_id'];
            }
            if(!empty($now_area['area_info']['province_id'])){
                $goods_area_id[] = $now_area['area_info']['province_id'];
            }
            //获取商品服务范围
            $goods_area = (new MallGoodsArea())->where([['area_id','in',$goods_area_id]])->findOrEmpty()->toArray();
            if(!$goods_area){
                $is_out_range = 1;
                $note = '超出可服务范围';
            }
        }elseif(cfg('open_mall_order_area')==2){
            $store_ids = (new MerchantStore())->where([['status','=',1]])->column('store_id,long,lat');
            $range_res = $this->checkRange($params,$store_ids);

            if($range_res['is_in_range']!=1){
                $is_out_range = 1;
                $note = '超出可服务范围';
            }
        }
        return ['is_out_range'=>$is_out_range,'note'=>$note];
    }
    
    public function getStoreIds(){
        $store_ids = (new MerchantStore())->alias('s')
            ->join('merchant_store_mall ms','s.store_id = ms.store_id')
            ->where([['s.status','=',1]])->column('s.store_id,s.long,s.lat,ms.is_zt,ms.is_delivery');
        return $store_ids;
    }
    
    public function getRangeStoreIds($lng,$lat){
        $store_ids = $this->getStoreIds();
        foreach ($store_ids as $key=>$val) {
            $mall_delivery = (new MallDelivery())->where(['status'=>1,'store_id'=>$val['store_id']])->findOrEmpty()->toArray();
            if($mall_delivery){
                if($mall_delivery['delivery_range_type']==1){
                    $distance = floatval((get_distance($lat,$lng,$val['lat'],$val['long'])/1000));
                    if($distance>$mall_delivery['delivery_radius']&&empty($val['is_zt'])){
                        unset($store_ids[$key]);
                    }
                }else{
                    $range_polygon = substr($mall_delivery['delivery_range_polygon'], 9, strlen($mall_delivery['delivery_range_polygon']) - 11);
                    $range_arr = explode(',',$range_polygon);
                    $m_deliver_area = [];
                    //区域多边形数组
                    foreach ($range_arr as $vvv){
                        $lng_lat = explode(' ',$vvv);
                        $m_deliver_area[0][] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
                    }
                    if(!isPtInPolyV20($lng,$lat,$m_deliver_area)&&empty($val['is_zt'])){
                        unset($store_ids[$key]);
                    }
                }
            }
        }
        $store_arr = array_column($store_ids,'store_id');
        return $store_arr;
    }
}