<?php


namespace app\community\model\service;

use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\service\PlatOrderService;
use app\pay\model\service\channel\TianqueService;
use app\community\model\service\HousePaidOrderRecordService;
class VillagePayOrderService
{
    /**
     * 支付之前调用
     * @author lijie
     * @date_time 2020/09/27
     * @param $order_id
     * @return mixed
     */
    public function getOrderPayInfo($order_id)
    {
        $service_house_village_order = new PlatOrderService();
        $service_house_village_pay_order = new HouseVillagePayOrder();
        $order_info = $service_house_village_order->getPlatOrder(['order_id'=>$order_id]);
        $info = $service_house_village_pay_order->get_one(['order_id'=>$order_info['business_id']],'village_id,order_name,order_type,car_type,car_number');
        $data['paid'] = 0;
        $data['mer_id'] = 0;
        $data['city_id'] = 0;//城市id
        $data['store_id'] = 0;
        $data['order_money'] = $order_info['total_money'];//订单支付金额
        $data['uid'] = $order_info['uid'];//用户id
        $data['order_no'] = $order_id;//订单号
        $house_order_name=cfg('house_name');
        if(empty($house_order_name)){
            $house_order_name='社区';
        }
        $house_order_name=trim($house_order_name);
        $data['title'] = $house_order_name.'缴费';
        $data['time_remaining'] = $order_info['add_time']+15*60-time();//倒计时（15分钟），订单可付款的剩余时间，超出不可支付
        $data['is_cancel'] = 0;//1表示取消订单
        $data['village_id'] = $info['village_id'];
        $data['goods_desc']='';
        if($info['order_type']=='park'){
            $data['goods_desc']='停车费用';
            if($info['car_type'] && $info['car_number']){
                $data['goods_desc']='停车费用-'.$info['car_number'];
            }
        }
        return $data;
    }

    /**
     * 支付成功返回地址
     * @author lijie
     * @date_time 2020/09/28
     * @param $order_id
     * @param int $is_cancel
     * @return string
     */
    public function getPayResultUrl($order_id,$is_cancel=0)
    {
        $service_house_village = new HouseVillageService();
        $service_house_village_order = new HouseVillageOrderService();
        if($is_cancel)
        {
            //取消订单
        }
        $base_url = $service_house_village->base_url;
        $page_url = cfg('site_url') . $base_url;
        $url = $page_url;
        return $url;
    }

    public function afterPay($order_id,$post){
        $service_plat_order = new PlatOrderService();
        $service_house_village_pay_order = new HouseVillagePayOrder();
        $service_house_village = new HouseVillage();
        $service_house_property = new HousePropertyService();
        $service_config = new ConfigService();
        $order_info = $service_plat_order->getPlatOrder(['order_id'=>$order_id]);
        $village_order_info = $service_house_village_pay_order->get_one(['order_id'=>$order_info['business_id']],'*');
        if($village_order_info && !$village_order_info->isEmpty()){
            $village_order_info=$village_order_info->toArray();
        }
        $village_info = $service_house_village->getOne($village_order_info['village_id']);
        $property_info = $service_house_property->getFind(['id'=>$village_info['property_id']]);
        $housePaidOrderRecordService=new HousePaidOrderRecordService();
        $housePaidOrderRecordService->addHouseVillagePayOrderRecord($village_order_info,$post);
        if($property_info['app_secret']){
            if($village_info['percent'] > 0){
                $fan_rate = $village_info['percent'];
            }else{
                $shequ_info = $service_config->get_config('platform_get_village_percent','value');
                if($shequ_info['value'] > 0){
                    $fan_rate = $shequ_info['value'];
                }else{
                    //用系统设置的
                    $system_rate = $service_config->get_config('platform_get_merchant_percent','value');
                    if($system_rate['value'] > 0){
                        $fan_rate = $system_rate['value'];
                    }else{
                        $res = invoke_cms_model('House_village_pay_order/after_pay',[$order_info['business_id'],$order_info]);
                        exit;
                    }
                }
            }
            $service_tianque = new TianqueService();
            $rtn = $service_tianque->launchLedger(0,$post['paid_orderid'],$order_info['money']*$fan_rate*0.01,$property_info['app_secret']);
            if($rtn){
                $res = invoke_cms_model('House_village_pay_order/after_pay',[$order_info['business_id'],$order_info,1]);
            }else{
                $res = invoke_cms_model('House_village_pay_order/after_pay',[$order_info['business_id'],$order_info]);
            }
        }
        $res = invoke_cms_model('House_village_pay_order/after_pay',[$order_info['business_id'],$order_info]);
    }
}