<?php
//
class Deliver_supplyModel extends Model
{
    
    /**
     * @param number $order_id
     * @param array $store merchant_store 表中的数据
     * @param number $item
     * @param string $tableName
     * @return boolean|number[]|string[]|number[]|unknown[]
     */
    public function saveOrder($order_id, $store, $item = 2, $tableName = 'shop_order',$delivery_fee = 0)
    {

        $tableName_info = strtolower($tableName);
        if ($tableName_info == 'shop_order') {
            $shopOrderDB = M('Shop_order');
            $order = $shopOrderDB->field(true)->where(array('order_id' => $order_id))->find();
            $orderDetails = M('Shop_order_detail')->where(['order_id' => $order_id])->field('goods_id,name,price,num,spec,unit')->select();
            $order['order_content'] = '';
            foreach ($orderDetails as $d) {
                $order['order_content'] .=  $d['name'] . ($d['spec'] ? '[规格：' . $d['spec'] . ']' : '') . ' (' . getFormatNumber($d['price']) . 'x' . $d['num'] . '),';
            }

            //如果is_handle=0，写入配送表的时候修复一下这个字段
            if ($order['is_handle'] == 0) {
                $shopOrderDB->where(['order_id' => $order_id])->save(['is_handle' => 1]);
            }

            $shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
            if (empty($shop)) return array('error_code' => 1, 'msg' => L_('订单所对应的店铺不存在'));

            $store = array_merge($store, $shop);
        } elseif ($tableName_info == 'service_user_publish') {
            if ($publish = M('Service_user_publish')->field(true)->where(array('publish_id' => $order_id))->find()) {
                if ($publish['catgory_type'] == 2) {
                    $order = M('Service_user_publish_buy')->field(true)->where(array('publish_id' => $order_id))->find();
                    $order['start_adress_name'] = $order['address'];
                    $order['start_adress_lng'] = $order['address_lng'];
                    $order['start_adress_lat'] = $order['address_lat'];
                    //$order['weight_price'] = 0;这里需要计算重量费用
                } elseif ($publish['catgory_type'] == 3) {
                    $order = M('Service_user_publish_give')->field(true)->where(array('publish_id' => $order_id))->find();
                }
                $order['province_id'] = $publish['province_id'];
                $order['city_id'] = $publish['city_id'];
                $order['area_id'] = $publish['area_id'];
				
				$plat_order = M('Plat_order')->field('`pay_time`')->where(array('business_type'=>'service','business_id'=>$publish['publish_id']))->find();
				$order['pay_time'] = $plat_order ? $plat_order['pay_time'] : 0;
            } else {
                return false;
            }
        } elseif ($tableName_info == 'village_group_order') {
            // 针对社区拼团-配送处理
            $order = M('Village_group_order')->field(true)->where(array('order_id' => $order_id))->find();
            //社区拼团订单会存在address_id但是address却为空的情况，这里修复一下，不然配送地址会显示null
            if ($order['uid'] > 0 && $order['address_id'] && empty($order['address'])) {
                $address = D('User_adress')->where(['uid' => $order['uid'], 'adress_id' => $order['address_id']])->find();
                $address && $order['address'] = $address['adress'];
            }
        }elseif($tableName_info == 'gift_order'){
            $order = M('Gift_order')->field(true)->where(array('order_id' => $order_id))->find();
        }
        $dada_shop_id = 0;
        $city_code = 0;
        // 处理判断条件
        $is_judge=true;
        $judge_info=array(
            'service_user_publish','village_group_order'
        );
        if (in_array($tableName_info, $judge_info)) {
            $is_judge = false;
        }
        if ($order['is_pick_in_store'] == 0 && C('config.dada_is_open') && $is_judge) {
            $dada = new Dada();
            $longlat_class = new longlat();
            
            $areas = M('Area')->field(true)->where(array('area_id' => array('in', array($store['city_id'], $store['area_id']))))->select();
            foreach ($areas as $a) {
                if ($a['area_id'] == $store['city_id']) {
                    $city_name = $a['area_name'];
                } elseif ($a['area_id'] == $store['area_id']) {
                    $area_name = $a['area_name'];
                }
            }
            if (empty($store['dada_shop_id'])) {
                
                $city_list = S('dada_city_list');
                if (empty($city_list)) {
                    $result = $dada->cityList();
                    if ($result['status'] == 'success') {
                        foreach ($result['result'] as $c) {
                            $city_list[$c['cityName']] = $c['cityCode'];
                        }
                    } else {
						if($result['result']['failedList']){
							foreach($result['result']['failedList'] as $faildValue){
								$result['msg'] .= '<br/>【' . $faildValue['shopName'] . '】' . $faildValue['msg'];
							}
						}
                        return array('error_code' => 1, 'msg' => L_('达达返回的错误：') . $result['msg']);
                    }
                    S('dada_city_list', $city_list, 86400);
                }
                if (isset($city_list[$city_name])) {
                    $city_code = $city_list[$city_name];
                    
                    $shop['shop_name'] = str_replace(['\'', '\"'], '', $store['name']);//                必填            门店名称
                    $shop['shop_type'] = 1;//                  必填            业务类型(餐饮-1,商超-9,水果生鲜-13,蛋糕-21,酒品-24,鲜花-3,其他-5)
                    $shop['city_name'] = $city_name;//                  必填             城市名称(如,上海)
                    $shop['area_name'] = $area_name;//                 必填             区域名称(如,浦东新区)
                    $shop['shop_address'] = str_replace(PHP_EOL, '', $store['adress']);//             必填             门店地址
                    $location2 = $longlat_class->baiduToGcj02($store['lat'], $store['long']);
                    $shop['shop_lng'] = $location2['lng'];//                    必填             门店经度
                    $shop['shop_lat'] = $location2['lat'];//                     必填             门店纬度
                    $shop['contact_name'] = str_replace(['\'', '\"'], '', $store['name']);;//            必填              联系人姓名
                    $shop['phone'] = $store['phone'];//                        必填              联系人电话
                    
                    $result = $dada->addShop($shop);
                    if ($result['status'] == 'success') {
                        $dada_shop_id = $result['result']['successList'][0]['originShopId'];
                        M('Merchant_store')->where(array('store_id' => $store['store_id']))->save(array('dada_shop_id' => $dada_shop_id, 'dada_city_code' => $city_code));
                    } else {
						if($result['result']['failedList']){
							foreach($result['result']['failedList'] as $faildValue){
								$result['msg'] .= '<br/>【' . $faildValue['shopName'] . '】' . $faildValue['msg'];
							}
						}
                        return array('error_code' => 1, 'msg' => L_('达达返回的错误：') . $result['msg']);
                    }
                } else {
//                     return array('error_code' => 1, 'msg' => '达达暂没有开启【' . $city_name . '】城市的配送');
                }
            } else {
                $dada_shop_id = $store['dada_shop_id'];
                $city_code = $store['dada_city_code'];
            }
        }
        
        if ($dada_shop_id) {
            $data = array();
            $data['shop_id'] = $dada_shop_id;//达达的门店号
            $data['order_id'] = $order['real_orderid'];//
            $data['city_code'] = $city_code;
            $data['order_price'] = $order['price'];
            $data['is_prepay'] = 0;
            $data['expected_fetch_time'] = time() + 1300;//期望取货时间
            $data['expected_finish_time'] = time() + 1600;//期望送达时间
            $data['user_name'] = $order['username'];//收货人姓名
            $data['user_address'] = str_replace(PHP_EOL, '', $order['address']);//收货人地址
            $data['user_phone'] = $order['userphone'];//收货人手机号（手机号和座机号必填一项）
            $data['user_tel'] = $order['userphone'];//收货人座机号（手机号和座机号必填一项）

            $location2 = $longlat_class->baiduToGcj02($order['lat'], $order['lng']);
            $data['user_lat'] = $location2['lat'];//收货人地址维度（高德坐标系）
            $data['user_lng'] = $location2['lng'];//收货人地址经度（高德坐标系）
            
            $result = $dada->addOrder($data);
            if ($result['status'] == 'success') {
                return array('error_code' => 0, 'msg' => L_('接单成功！'));
            } else {
                return array('error_code' => 1, 'msg' => $result['msg']);
            }
        }elseif($order['is_pick_in_store'] == 0 && C('config.dindin_is_open') && $is_judge){
            //==>郭先森  查询所在店铺
            $merchantStore = D('Merchant_store')->where(array('store_id'=>$order['store_id'],'is_open_dingding'=>1))->find();

//            fdump($merchantStore,"dingding===",true);
//            fdump(C('config.dindin_is_open'),"dingding===",true);
//            fdump($merchantStore['is_open_dingding'],"dingding===",true);

            if(empty($merchantStore)){
                return array('error_code' => 1, 'msg' => L_('本店鋪未开启叮叮配送！'));
            }
            $areas = M('Area')->field(true)->where(array('area_id' => array('in', array($store['city_id'], $store['area_id']))))->select();
            foreach ($areas as $a) {
                if ($a['area_id'] == $store['city_id']) {
                    $city_name = $a['area_name'];
                } elseif ($a['area_id'] == $store['area_id']) {
                    $area_name = $a['area_name'];
                }
            }

            import('@.ORG.Dingding');
            $getShopInfo = new Dingding();
            $postData = array(
                'headquarters_id'=>1,
                'username'=>$merchantStore['dingding_user_name'],
                'per_userid'=>$merchantStore['dingding_per_userid'],
            );
            $res = $getShopInfo->getShopInfo($postData);
            $res = json_decode($res,true);
            if($res['code'] == 0){
                return array('error_code' => 1, 'msg' => L_('店鋪信息不存在,请联系开发者！'));
            }
            $dingDing = new Dingding(C('config.dingding_fromplatform'),C('config.dingding_form_app_key'),C('config.dingding_appid'),$res['result']['code'],$merchantStore['dingding_per_userid'],$merchantStore['dingding_user_name'],$merchantStore['dingding_pass'],$res['result']['phone']);
            $longlat_class = new longlat();
            $location2 = $longlat_class->baiduToGcj02($order['lat'], $order['lng']);
            $orderGoods = D('Shop_order_detail')->where(array('order_id'=>$order['order_id']))->select();
            $goodsList = [];
            if(!empty($orderGoods)){
                foreach($orderGoods as $key=>$value){
                    $arr = array(
                        'productTotalPrice'=>1,
                        'productPrice'=>$value['price'],
                        'productCount'=>$value['num'],
                        'productName'=>$value['name'],
                    );
                    $goodsList[] = $arr;
                }

            }

            $postData = [
                'origin_id'     =>  $order['real_orderid'],  //订单号
                'bigNumb'       =>  $order['order_id'],    //大订单号，平常说的几号单
                'city'          =>  $city_name ,  //所在城市名
                'weight'        =>  '0.02' ,   //货物重量单位kg
                'cargo_price'   =>  $order['price'] ,   //订单金额单位元
                'receiver_name' =>  $order['username'] ,
                'receiver_address' =>  $order['address'] ,
                'receiver_lat'  =>  $location2['lat'],
                'receiver_lng'  =>  $location2['lng'],
                'receiver_phone' =>  $order['userphone'],
                'info'          =>  $order['desc']?$order['desc']:'',
                'callback'      => C('config.site_url').'/wap.php?g=Wap&c=Dingding&a=dingdingCallback',
//                'callback'      => "https://www.csco2o.cn/wap.php?g=Wap&c=Dingding&a=dingdingCallback",
                'door_time'     => 1,  //预约发单时间
                'timestamp'     => time(),
                'goods'         =>  $goodsList,

            ];

            fdump($postData,"dingdingCreateOrder",true);
            $res = $dingDing->createOrder($postData);
            $res = json_decode($res,true);
            fdump($res,"dingdingCreateOrder",true);
            if($res['code'] == 1){
                M('Shop_order')->where(array('order_id' => $order['order_id']))->save(array('dingding_trade_no'=>$res['result']['ordernumber']));

                return array('error_code' => 0, 'msg' => L_('接单成功！'));
            }else{
                return array('error_code' => 1, 'msg' => $res['msg']);
            }

        }elseif($order['is_pick_in_store'] == 0 && C('config.keloop_is_open') && $is_judge){
                $keloop = new Keloop();
                $longlat_class = new longlat();

                $shop_data['shop_id'] = $store['store_id'];
                $shop_data['shop_name'] = $store['name'];
                $shop_data['shop_tel'] = strval(trim($store['phone']));
                $shop_data['shop_address'] = $store['adress'];
                $location1 = $longlat_class->baiduToGcj02($store['lat'], $store['long']);
                $shop_data['shop_tag'] =  $location1['lng'].','.$location1['lat'];

                $shop_data['customer_name'] =  $order['username'];
                $shop_data['customer_tel'] =  strval(trim($order['userphone']));
                $shop_data['customer_address'] =  $order['address'];
                $location2 = $longlat_class->baiduToGcj02($order['lat'], $order['lng']);
                $shop_data['customer_tag'] =  $location2['lng'].','.$location2['lat'];

                $shop_data['order_no'] =  strval($order['real_orderid']);
                $shop_data['pay_status'] =  0; //0 表示已支付、1 表示货到付款
                $shop_data['pre_times'] =  $order['expect_use_time']; //预计送达时间
                $shop_data['order_mark'] = $order['order_from'] == 1 ? '' : $order['fetch_number'];
                $shop_data['order_content'] = $order['order_content']; //订单内容
                $order['desc'] && $shop_data['order_note'] = $order['desc'];//备注
                $shop_data['order_from'] = D('Shop_order')->getOrderFromTag($order['order_from']); //来源
                $shop_data['order_price'] = $order['total_price'];//总价
                $shop_data['order_time'] = date('Y-m-d H:i:s',$order['create_time']);//下单时间
                $shop_data['is_calc_fee'] = '1';//下单时间

                $result = $keloop->createOrder($shop_data);
                if ($result['code'] == '200') {
                    M('Shop_order')->where(array('real_orderid' => $order['real_orderid']))->save(array('keloop_trade_no' => $result['data']['trade_no']));
                    return array('error_code' => 0, 'msg' =>$result['message']);
                } else {
                    return array('error_code' => 1, 'msg' => L_('快跑者返回的错误：') . $result['message']);
                }

        } elseif ($order['is_pick_in_store'] == 0 && C('config.shunfeng_is_open') && $is_judge && $merchantStore = D('Merchant_store')->field('shunfeng_is_open,shunfeng_dev_id,shunfeng_dev_key,shunfeng_shop_id')->where(['store_id' => $order['store_id'], 'shunfeng_is_open' => 1])->find()) {
            //顺丰配送
            $productDetail = M('Shop_order_detail')->where(['order_id' => $order['order_id']])->field('goods_id AS product_id,name AS product_name,num AS product_num')->select();
            $data = [
                'shop_order_id' => $order['real_orderid'],
                'order_source' => $order['order_from'],
                'lbs_type' => 1,
                'pay_type' => 1,
                'order_time' => $order['create_time'],
                'is_appoint' => 0,
                'expect_time' => $order['expect_use_time'],
                'is_insured' => 0,
                'is_person_direct' => 0,
                'remark' => $order['desc'] ?: '',
                'push_time' => time(),
                'receive' => [
                    'user_name' => $order['username'],
                    'user_phone' => $order['userphone'],
                    'user_address' => $order['address'],
                    'user_lng' => $order['lng'],
                    'user_lat' => $order['lat']
                ],
                'order_detail' => [
                    'total_price' => intval($order['price'] * 100),
                    'product_type' => 1,
                    'weight_gram' => 0,
                    'product_num' => $order['num'] ?: 1,
                    'product_type_num' => $order['num'] ?: 1,
                    'product_detail' => $productDetail
                ]
            ];

            $merchantStore['shunfeng_dev_id'] = C('config.shunfeng_dev_id');
            $merchantStore['shunfeng_dev_key'] = C('config.shunfeng_dev_key');
            $result = (new Shunfeng($merchantStore))->createorder($data);
            if ($result['error_code'] == 0) {
                M('Shop_order')->where(array('real_orderid' => $order['real_orderid']))->save(['sf_order_id' => $result['result']['sf_order_id']]);
                return array('error_code' => 0, 'msg' => $result['message']);
            } else {
                return array('error_code' => 1, 'msg' => L_('顺丰配送返回错误：') . $result['error_msg']);
            }
        } elseif ($order['is_pick_in_store'] == 0 && C('config.ele_is_open') && $is_judge && $merchantStore = D('Merchant_store')->field('ele_is_open,ele_app_id,ele_secret_key,ele_transport_name,ele_chain_store_code')->where(['store_id' => $order['store_id'], 'ele_is_open' => 1])->find()) {
            //饿了么蜂鸟配送
            $ele = new EleDeliveryNew();
            $ele->setBusinessData('partner_order_code', $order['real_orderid']);
            $ele->setBusinessData('receiver_primary_phone', $order['userphone']);
            $ele->setBusinessData('goods_count', $order['num']);
            $ele->setBusinessData('chain_store_id', $merchantStore['ele_chain_store_code']);
            $ele->setBusinessData('goods_total_amount_cent', $order['total_price'] * 100);
            $ele->setBusinessData('receiver_name', $order['username']);
            $ele->setBusinessData('receiver_latitude', $order['lat']);
            $ele->setBusinessData('receiver_longitude', $order['lng']);
            $ele->setBusinessData('receiver_address', $order['address']);
            $ele->setBusinessData('position_source', 2);
            $ele->setBusinessData('order_type', 1);
            $ele->setBusinessData('goods_weight', 1);
            $ele->setBusinessData('goods_item_list', [['item_actual_amount_cent' => $order['total_price'] * 100, 'item_amount_cent' => $order['total_price'] * 100, 'item_name' => $order['order_content']]]);
            $ele->setBusinessData('require_receive_time', $order['expect_use_time']);
            $ele->setBusinessData('goods_actual_amount_cent', $order['total_price'] * 100);
            try {
                $result = $ele->createOrder();
                M('Shop_order')->where(array('order_id' => $order['order_id']))->save(array('is_ele_delivery' => 1));
                return array('error_code' => 0, 'msg' => $result['message']);
            } catch (Exception $e) {
                return array('error_code' => 0, 'msg' => $e->getMessage());
                $this->error($e->getMessage());
            }
        }else {

            $old = $this->field(true)->where(array('order_id' => $order_id, 'item' => $item))->find();
            if (empty($old) || $item==2) {
                if ($item==2) {
                    // 订单详情
                    $order =  D("Shop_order")->where(array('order_id' => $order_id))->find();
                    // 周期购
                    if(!$order['periodic_type']&&$old){
                        //不是周期购且已经生成过配送订单直接返回
                        return array('error_code' => 0, 'msg' => L_('接单成功！'));
                    }
                }

                if ($tableName_info == 'shop_order') {
                    // $store = M('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
                    // $shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
                    // if (empty($store)) return array('error_code' => 1, 'msg' => '订单所对应的店铺不存在');
                    // $storeDetail = array_merge($store, $shop);
                    $order_from = 0;
					if($order['order_from']=='1'){
						$order_from = 12;//商城
					}
                    $delivery_fee = $delivery_fee ? $delivery_fee : ($order['freight_charge_original']?$order['freight_charge_original']:$order['freight_charge']);//原始配送费
                    if ($order['is_pick_in_store'] == 5) {//重新计算饿了么和美团的配送费
                        //计算配送距离
                        $distance = 0;
                        if (C('config.is_riding_distance')) {
                            import('@.ORG.longlat');
                            $longlat_class = new longlat();
                            $distance = $longlat_class->getRidingDistance($store['lat'], $store['long'], $order['lat'], $order['lng']);
                        }
                        $distance || $distance = getDistance($store['lat'], $store['long'], $order['lat'], $order['lng']);

                        //获取配送的优惠金额信息
                        $delivery_fee_reduce = 0;
                        $discounts = D('Shop_discount')->getDiscounts($store['mer_id'], $store['store_id']);
                        if ($d_tmp = D('Shop_goods')->getReduce($discounts, 2, $order['goods_price'])) {
                            $delivery_fee_reduce = $d_tmp['reduce_money'];
                        }
                        $order['distance'] = $distance;
                        $expect_use_time_temp = $order['create_time'];
                        
                        //平台配送的相关设置信息的处理
                        $distance = $distance / 1000;
                        $deliverReturn = D('Deliver_set')->getDeliverInfo($store, $order['price']);
                        if ($this->checkTime($expect_use_time_temp, $deliverReturn['delivertime_start'], $deliverReturn['delivertime_stop'], 1)) { //时间段一
                            $pass_distance = $distance > $deliverReturn['basic_distance'] ? floatval($distance - $deliverReturn['basic_distance']) : 0;
                            $deliverReturn['delivery_fee'] += round($pass_distance * $deliverReturn['per_km_price'], 2);
                            //$deliverReturn['delivery_fee'] = $deliverReturn['delivery_fee'] - $delivery_fee_reduce;
                            $deliverReturn['delivery_fee'] = $deliverReturn['delivery_fee'] > 0 ? $deliverReturn['delivery_fee'] : 0;
                            $delivery_fee = $deliverReturn['delivery_fee'];
                        } elseif ($this->checkTime($expect_use_time_temp, $deliverReturn['delivertime_start2'], $deliverReturn['delivertime_stop2'], 2)) { //时间段二
                            $pass_distance = $distance > $deliverReturn['basic_distance2'] ? floatval($distance - $deliverReturn['basic_distance2']) : 0;
                            $deliverReturn['delivery_fee2'] += round($pass_distance * $deliverReturn['per_km_price2'], 2);
                            //$deliverReturn['delivery_fee2'] = $deliverReturn['delivery_fee2'] - $delivery_fee_reduce;
                            $deliverReturn['delivery_fee2'] = $deliverReturn['delivery_fee2'] > 0 ? $deliverReturn['delivery_fee2'] : 0;
                            $delivery_fee = $deliverReturn['delivery_fee2'];
                        } elseif ($this->checkTime($expect_use_time_temp, $deliverReturn['delivertime_start3'], $deliverReturn['delivertime_stop3'], 3)) { //时间段二
                            $pass_distance = $distance > $deliverReturn['basic_distance3'] ? floatval($distance - $deliverReturn['basic_distance3']) : 0;
                            $deliverReturn['delivery_fee3'] += round($pass_distance * $deliverReturn['per_km_price3'], 2);
                            //$deliverReturn['delivery_fee3'] = $deliverReturn['delivery_fee3'] - $delivery_fee_reduce;
                            $deliverReturn['delivery_fee3'] = $deliverReturn['delivery_fee3'] > 0 ? $deliverReturn['delivery_fee3'] : 0;
                            $delivery_fee = $deliverReturn['delivery_fee3'];
                        }
                       
                        switch (intval(C('config.count_freight_charge_method'))) {
                            case 0:
                                break;
                            case 1:
                                $delivery_fee = ceil($delivery_fee * 10) / 10;
                                break;
                            case 2:
                                $delivery_fee = floor($delivery_fee * 10) / 10;
                                break;
                            case 3:
                                $delivery_fee = round($delivery_fee * 10) / 10;
                                break;
                            case 4:
                                $delivery_fee = ceil($delivery_fee);
                                break;
                            case 5:
                                $delivery_fee = floor($delivery_fee);
                                break;
                            case 6:
                                $delivery_fee = round($delivery_fee);
                                break;
                        }
                        //修改原始订单表中的配送费
                        //if ($delivery_fee != $order['freight_charge']) {
                        if ($delivery_fee-$delivery_fee_reduce != $order['freight_charge']) {
                            D('Shop_order')->where(array('order_id' => $order['order_id']))->save(array('freight_charge' => $delivery_fee, 'distance' => $order['distance']));
                        }
                    }
                    $order_from = $order['platform'];
                    if($order['order_from']=='1'){
                        $order_from = 12;//商城
                    }
                    $supply = array();
                    if (empty($order['third_id']) && $order['pay_type'] == 'offline') $order['paid'] = 0;
                    $supply['order_from'] = $order_from;//订单来源(0:快店，1:饿了么，2:美团，3：帮送，4：帮买(指定地址)),5：帮买（未指定地址）
                    $supply['order_id'] = $order_id;
                    $supply['paid'] = $order['paid'];
                    $supply['pay_time'] = intval($order['pay_time']);
                    $supply['real_orderid'] = isset($order['real_orderid']) ? $order['real_orderid'] : '';
                    $supply['pay_type'] = $order['pay_type'];
                    $supply['money'] = $order['price'];
                    if ($order['card_discount'] > 0 && $order['card_discount'] < 10) {
                        $supply['deliver_cash'] = round($order['price'] * $order['card_discount'] / 10 + $order['extra_price'] - round($order['card_price'] + $order['merchant_balance'] + $order['card_give_money'] + $order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'] + $order['qiye_pay'], 2), 2);
                    } else {
                        $supply['deliver_cash'] = round($order['price'] + $order['extra_price'] - round($order['card_price'] + $order['merchant_balance'] + $order['card_give_money'] + $order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'] + $order['qiye_pay'], 2), 2);
                    }
                    
                    $supply['deliver_cash'] = max(0, $supply['deliver_cash']);
                    $supply['store_id'] = $store['store_id'];
                    $supply['store_name'] = $store['name'];
                    $supply['mer_id'] = $store['mer_id'];
                    $supply['from_site'] = $store['adress'];
                    $supply['from_lnt'] = $store['long'];
                    $supply['from_lat'] = $store['lat'];
                    
                    $supply['province_id'] = $store['province_id'];
                    $supply['city_id'] = $store['city_id'];
                    $supply['area_id'] = $store['area_id'];
                    $supply['street_id'] = $store['street_id'];
    
                    //目的地
                    $supply['aim_site'] =  $order['address'];
                    $supply['aim_lnt'] = $order['lng'];
                    $supply['aim_lat'] = $order['lat'];
                    $supply['name']  = $order['username'];
                    $supply['phone'] = str_replace(' ', '', $order['userphone']);
                    $supply['fetch_number'] = isset($order['fetch_number']) ? $order['fetch_number'] : '';
    
                    $supply['status'] =  1;
                    $supply['type'] = $order['is_pick_in_store'] != 5 ? $order['is_pick_in_store'] : 0;
                    $supply['item'] = $item;//0:老快店的外卖，1：外送系统，2：新快店, 3:对接的
                    $supply['create_time'] = time();
                    $supply['order_out_time'] = time() + $shop['work_time'] * 60;//预计出单时间
                    //$supply['start_time'] = $_SERVER['REQUEST_TIME'];
                    $supply['appoint_time'] = $order['expect_use_time'];
                    $supply['note'] = $order['desc'];
                    $supply['is_right_now'] = intval($order['is_right_now']);//是否立即送达时间
                
                    $supply['order_time'] = $order['pay_time'];//订单支付时间
                    $supply['freight_charge'] = $delivery_fee;//配送费
                    
                    if ($order['distance']) {
                        $supply['distance'] = round($order['distance']/1000, 2);//配送距离
                    } else {
                        $supply['distance'] = round(getDistance($order['lat'], $order['lng'], $store['lat'], $store['long'])/1000, 2);//配送距离
                    }

                    // 号码隐私处理
                    $supply['virtual_phone'] = $order['virtual_phone']??'';//虚拟号码可以多个用;分割，主机号与分机号用,号分割
                    $supply['virtual_phone_overtime'] = $order['virtual_phone_overtime']??0;//虚拟号码过期时间
                    fdump_api([
                        '外卖订单',
                        'order_id' => $order_id,
                        'order' => $order,
                        'supply' => $supply
                    ],'shop_order/deliver',1);
                }  elseif ($tableName_info == 'village_group_order') {
                    $supply['order_id'] = $order_id;
                    $supply['paid'] = 1;
                    $supply['real_orderid'] = $order['real_orderid'];
                    $supply['pay_type'] = $order['pay_type'];
                    $supply['money'] = $order['discount_price']+$order['delivery_fee'];
                    $supply['deliver_cash'] = 0;
                    $supply['store_id'] = 0;
                    $supply['store_name'] = '';
                    $supply['mer_id'] = 0;
                    $supply['from_site'] = $order['pick_addr'];
                    $supply['from_lnt'] = $order['pick_long'];
                    $supply['from_lat'] = $order['pick_lat'];
                    $supply['pay_time'] = intval($order['pay_time']);
                    $supply['province_id'] = isset($order['pick_province_id']) ? $order['pick_province_id'] : 0;
                    $supply['city_id'] = isset($order['pick_city_id']) ? $order['pick_city_id'] : 0;
                    $supply['area_id'] = isset($order['pick_area_id']) ? $order['pick_area_id'] : 0;

                    //目的地
                    if($supply['city_id'] > 0 && $supply['area_id'] > 0){
                        if($order['address']){
                            $cityName = D('Area')->where(['area_id'=>$supply['city_id']])->find();
                            $areaName = D('Area')->where(['area_id'=>$supply['area_id']])->find();
                            $address = $cityName['area_name'].$areaName['area_name'].$order['address'];
                        }else{
                            $address = '';
                        }
                    }else{
                        $address = $order['address']?:$order['address'];
                    }
                    $supply['aim_site'] =  $address;
                    $supply['aim_lnt'] = $order['address_long'];
                    $supply['aim_lat'] = $order['address_lat'];
                    $supply['name']  = $order['username'];
                    $supply['phone'] = str_replace(' ', '', $order['userphone']);
                    $supply['fetch_number'] = '';
                    $supply['status'] =  1;
                    $supply['type'] = 0;
                    $supply['item'] = 8;// 8: 社区拼团配送
                    $supply['create_time'] = $_SERVER['REQUEST_TIME'];
                    $supply['appoint_time'] = $order['expect_send_time']??0;
                    $supply['note'] = isset($order['remarks']) && $order['remarks'] ? $order['remarks'] : '';
                    $supply['order_time'] = $order['add_time'];//订单支付时间
                    $supply['freight_charge'] = floatval($order['delivery_fee']);//配送费

                    $supply['distance'] = 0;
                    if ($order && $order['delivery_distance']>0) {
                        $distance = $order['delivery_distance']/ 1000;//配送距离
                        $supply['distance'] = round($distance, 2);//配送距离
                    } elseif ($order['pick_lat'] && $order['pick_long']) {
                        $distance = $this->ajax_distance($order['pick_lat'], $order['pick_long'], $order['address_lat'], $order['address_long']);
                        $supply['distance'] = round($distance, 2);//配送距离
                    }
                    $supply['image'] = isset($order['img']) && !empty($order['img']) ? $order['img'] : '';
                    $supply['order_from'] = 10;//订单来源(0:快店，1:饿了么，2:美团，3：帮送，4：帮买(指定地址)),5：帮买（未指定地址）10 社区拼团
                    if(!$order['username'] || !$order['userphone']){
                        $userFind = M('User')->field(true)->where(['uid'=>$order['uid']])->find();
                        if(!empty($userFind)){
                            $supply['name']  = $userFind['nickname'];
                            $supply['phone'] = $userFind['phone'];
                        }
                    }

                }elseif($tableName_info == 'gift_order'){

                    $condition_user_adress['adress_id'] = $order['address_id'];
                    $user_adress = M('User_adress')->field(true)->where($condition_user_adress)->find();

                    $supply['order_id'] = $order_id;
                    $supply['paid'] = 1;
                    $supply['real_orderid'] = '';
                    $supply['uid'] = $order['uid'];
                    $supply['pay_type'] = '';
                    $supply['money'] = $order['payment_money']+$order['delivery_fee'];
                    $supply['deliver_cash'] = 0;
                    $supply['store_id'] = 0;
                    $supply['store_name'] = '';
                    $supply['mer_id'] = 0;
                    $supply['from_site'] = $store['address'];
                    $supply['from_lnt'] = $store['long'];
                    $supply['from_lat'] = $store['lat'];
                    $supply['pay_time'] = intval($order['pay_time']);

                    $supply['province_id'] = isset($user_adress['province']) ? intval($user_adress['province']) : 0;
                    $supply['city_id'] = isset($user_adress['city']) ? intval($user_adress['city']) : 0;
                    $supply['area_id'] = isset($user_adress['area']) ? intval($user_adress['area']): 0;

                    //目的地
                    $supply['aim_site'] =  $order['adress'];
                    $supply['aim_lnt'] = $user_adress['longitude'];
                    $supply['aim_lat'] = $user_adress['latitude'];
                    $supply['name']  = $order['contact_name'];
                    $supply['phone'] = $order['phone'];
                    $supply['fetch_number'] = '';
                    $supply['status'] =  1;
                    $supply['type'] = 0;
                    $supply['item'] = 0;
                    $supply['create_time'] = $_SERVER['REQUEST_TIME'];
                    $supply['appoint_time'] = 0;
                    $supply['note'] = isset($order['remarks']) && $order['remarks'] ? $order['remarks'] : '';
                    $supply['order_time'] = $order['pay_time'];//订单支付时间
                    $supply['freight_charge'] = floatval($order['delivery_fee']);//配送费

                    $supply['distance'] = 0;
                    if ($user_adress['latitude'] && $user_adress['longitude']) {
                        $distance = $this->ajax_distance($store['lat'], $store['long'],  $user_adress['latitude'], $user_adress['longitude']);
                        $supply['distance'] = round($distance, 2);//配送距离
                    }
                    $supply['image'] = '';
                    $supply['order_from'] = 11;//订单来源(0:快店，1:饿了么，2:美团，3：帮送，4：帮买(指定地址)),5：帮买（未指定地址）10 社区拼团 11积分商城
                }else {

                    $supply['order_id'] = $order_id;
                    $supply['paid'] = 1;
                    $supply['real_orderid'] = '';
                    $supply['pay_type'] = '';
                    $supply['money'] = $order['total_price'];
                    $supply['deliver_cash'] = 0;
                    $supply['store_id'] = 0;
                    $supply['store_name'] = '';
                    $supply['mer_id'] = 0;
                    $supply['from_site'] = $order['start_adress_name'];
                    $supply['from_lnt'] = $order['start_adress_lng'];
                    $supply['from_lat'] = $order['start_adress_lat'];
                    $supply['pay_time'] = intval($order['pay_time']);
                    
                    $supply['province_id'] = isset($order['province_id']) ? $order['province_id'] : 0;
                    $supply['city_id'] = isset($order['city_id']) ? $order['city_id'] : 0;
                    $supply['area_id'] = isset($order['area_id']) ? $order['area_id'] : 0;

                    // 处理记录三方对接
                    if ($publish && $publish['is_third']>0) {
                        $supply['is_third'] = 1;
                        $supply['third_mer_id'] = $publish['mer_id'];
                    }

                    
                    //目的地
                    $supply['aim_site'] =  $order['end_adress_name'];
                    $supply['aim_lnt'] = $order['end_adress_lng'];
                    $supply['aim_lat'] = $order['end_adress_lat'];
                    $supply['name']  = $order['end_adress_nickname'];
                    $supply['phone'] = str_replace(' ', '', $order['end_adress_phone']);
					
                    $supply['fetch_number'] = '';
                    
                    $supply['status'] =  1;
                    $supply['type'] = 0;
                    $supply['item'] = 3;//0:老快店的外卖，1：外送系统，2：新快店, 3:对接的
                    $supply['create_time'] = $_SERVER['REQUEST_TIME'];
                    //$supply['start_time'] = $_SERVER['REQUEST_TIME'];
                    if ($order && $order['arrival_time_info']) {
                        if (strtotime($order['arrival_time_info'])) {
                            $supply['appoint_time'] = strtotime($order['arrival_time_info']);
                        } else {
                            $supply['appoint_time'] = $order['arrival_time_info'];
                        }
                    } else {
                        $supply['appoint_time'] = 0;
                    }
                    $supply['order_out_time'] = $supply['appoint_time'];
                    $supply['note'] = isset($order['remarks']) && $order['remarks'] ? $order['remarks'] : '';
                    $supply['order_time'] = $publish['add_time'];//订单支付时间
                    $supply['freight_charge'] = floatval($order['weight_price'] + $order['distance_price'] + $order['basic_distance_price']);//配送费
                    
                    $supply['distance'] = 0;
                    if ($publish && $publish['destance_sum']>0) {
                        $supply['distance'] = $publish['destance_sum'];//配送距离
                    } elseif ($order['start_adress_lat'] && $order['start_adress_lng']) {
                        $distance = $this->ajax_distance($order['start_adress_lat'], $order['start_adress_lng'], $order['end_adress_lat'], $order['end_adress_lng']);
                        $supply['distance'] = round($distance, 2);//配送距离
                    }
                    $supply['image'] = isset($order['img']) && !empty($order['img']) ? $order['img'] : '';
                    $supply['tip_price'] = $order['tip_price'];//小费
                    if ($publish['catgory_type'] == 2) {//帮我买
                        $supply['goods_name'] = isset($order['goods_remarks']) && $order['goods_remarks']? $order['goods_remarks'] : '';//商品类型或名称
                        $supply['goods_price'] = isset($order['estimate_goods_price']) ? floatval($order['estimate_goods_price']) : 0;//商品的预估费用
                        $supply['server_time'] = $order['arrival_time_info']?$order['arrival_time_info'] : $order['arrival_time'];//送达时间
                        $supply['server_type'] = $order['buy_type'] == 1 ? 3 : 2;//服务类型(1:帮我送，2：帮我买（指定地址），3：帮我买（未指定地址）；
                        $supply['order_from'] = $order['buy_type'] == 1 ? 5 : 4;//订单来源(0:快店，1:饿了么，2:美团，3：帮送，4：帮买(指定地址)),5：帮买（未指定地址）
                        if ($supply['server_type'] == 3) {//帮我买，未指定地址时就已目的地地址为起始地址
                            $supply['from_site'] = $supply['aim_site'];
                            $supply['from_lnt'] = $supply['aim_lnt'];
                            $supply['from_lat'] = $supply['aim_lat'];
                        }
                        $supply['goods_weight'] = $order['weight'];//商品重量
                    } elseif ($publish['catgory_type'] == 3) {//帮我送
                        $supply['order_from'] = 3;//订单来源(0:快店，1:饿了么，2:美团，3：帮送，4：帮买(指定地址)),5：帮买（未指定地址）
                        $supply['goods_name'] = isset($order['goods_catgory']) ? $order['goods_catgory'] : '';//商品类型或名称
                        $supply['goods_price'] = isset($order['price']) ? $order['price'] : 0;//商品的预估费用
                        $supply['goods_weight'] = $order['weight'];//商品重量
                        $supply['server_time'] = $order['fetch_time'] ? $order['fetch_time'] : L_('立刻取货');//取货时间
                        $supply['server_type'] = 1; //服务类型(1:帮我送，2：帮我买（指定地址），3：帮我买（未指定地址）；
                        $supply['mer_id'] = $order['from_mer_id'] ?? 0;
                    }
                }

                if ($old && $order['periodic_type']) {
                    // 商城周期购
					fdump_sql($this->getLastSql(),'supply_add_error_ok');
                    $supply['supply_id'] = $old['supply_id'];
                    $supply['uid'] = 0;
                    $supply['name'] = '';
                    $supply['phone'] = '';
                    $this->save($supply);
                    return array('error_code' => 0, 'msg' => L_('接单成功！'));
                }elseif ($supply_id = $this->add($supply)) {
                    $supply['supply_id'] = $supply_id;
                    //推送消息提示
                    $this->sendMsg($supply);
                    return array('error_code' => 0, 'msg' => L_('接单成功！'));
                } else {
					fdump_sql($this->getLastSql(),'supply_add_error');
                    return array('error_code' => 1, 'msg' => L_('保存订单失败'));
                }
            }
            return array('error_code' => 0, 'msg' => L_('接单成功！'));
        }
        
    }
    
    public function sendMsg($supply, $excludeUid = 0)
    {
		if(!$supply){
			return false;
		}
		/*if($supply['type'] == 0 && M('Area')->where(['area_id'=>$supply['city_id']])->getField('deliver_model')){
			return false;
		}*/

		//城市不允许抢单就不要推送消息了
        if (M('Area')->where(['area_id' => $supply['city_id']])->getField('no_grab_min') <= 0) {
            return false;
        }

        $group = $supply['type'] + 1;
        $where = $group == 1 ? '`group`=1' : '`group`=2 AND (`store_id`=' . $supply['store_id'] . ' OR FIND_IN_SET('.$supply['store_id'].',store_ids))';
        if ($group == 1) {

            //1 按服务半径推送消息
            $new_message_push_type = C('config.new_message_push_type');
            $fromLnt = $supply['from_lnt'];
            $fromLat = $supply['from_lat'];

            if($new_message_push_type == 1){ //设置的半径
                $where .= " AND `is_notice`=0 AND `status`=1 AND 
                ((delivery_range_type = 0 AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$supply['from_lat']}*PI()/180-`lat`*PI()/180)/2),2)+COS({$supply['from_lat']}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$supply['from_lnt']}*PI()/180-`lng`*PI()/180)/2),2))))<`range`) 
                OR 
                (delivery_range_type = 1 AND MBRContains(PolygonFromText(`delivery_range_polygon`),PolygonFromText(CONCAT('Point(',$fromLnt,' ',$fromLat,')')))>0)
                )";
            }else{//实时位置
                $where .= " AND `is_notice`=0 AND `status`=1 AND 
                ((`now_lat` <> 0 AND `now_lng` <> 0 AND delivery_range_type = 0 AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$supply['from_lat']}*PI()/180-`now_lat`*PI()/180)/2),2)+COS({$supply['from_lat']}*PI()/180)*COS(`now_lat`*PI()/180)*POW(SIN(({$supply['from_lnt']}*PI()/180-`now_lng`*PI()/180)/2),2))))<`range`)
                OR 
                (delivery_range_type = 1 AND MBRContains(PolygonFromText(`delivery_range_polygon`),PolygonFromText(CONCAT('Point(',$fromLnt,' ',$fromLat,')')))>0)
                )";
            } 
        } else {
            $where .= " AND `is_notice`=0 AND `status`=1";
        }
        
        $users = D('Deliver_user')->field(true)->where($where)->select();
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $href = C('config.config_site_url') . '/packapp/deliver/index.html?gopage=grab';

        //订单类型
        $checkOrderType = 0;
        if (in_array($supply['item'], [2, 4, 5])) {
            $checkOrderType = 1;
        } else if ($supply['item'] == 3 && in_array($supply['server_type'], [2, 3])) {
            $checkOrderType = 2;
        } else if ($supply['item'] == 3 && $supply['server_type'] == 1) {
            $checkOrderType = 3;
        }

        //判断跑腿单是否来自商家中心，而且商家是否绑定了骑手
        if (C('config.open_paotui_price_template') && $supply['item'] == 3 && $supply['mer_id'] > 0) {
            $poolIds = D('Paotui_grab_pool_merchant')->alias('m')
                ->join('INNER JOIN ' . C('DB_PREFIX') . 'paotui_grab_pool p ON p.id = m.pool_id')
                ->where(['p.status' => 1, 'm.mer_id' => $supply['mer_id']])
                ->getField('`p`.id,`m`.pool_id', true);
            $poolIds = array_values($poolIds);
        }

        foreach ($users as $user) {
            //判断配送员是否能接该类型订单
            $orderTypes = explode(',', $user['order_type']);
            if ($checkOrderType > 0 && (empty($orderTypes) || !in_array($checkOrderType, $orderTypes))) {
                continue;
            }

            if ($excludeUid && $excludeUid == $user['uid']) {
                continue;
            }
			if($user['last_time'] == 0){
				continue;
			}

            if (isset($poolIds) && $poolIds) {
                //商家绑定了配送池，则只能配送池里的骑手才能抢单，其他骑手不允许抢单
                $isBind = D('Paotui_grab_pool_deliver')->where(['pool_id' => ['IN', $poolIds], 'deliver_uid' => $user['uid']])->find();
                if (empty($isBind)) {
                    continue;
                }
            }

            if ($user['client'] == 0) {
                if ($user['openid']) {
                    $model->sendTempMsg('TM00017',
                        array('href' => $href,
                            'wecha_id' => $user['openid'],
                            'first' => $user['name'] . L_('您好！您有新的待抢配送订单!'),
                            'OrderSn' => $supply['order_id'],
                            'OrderStatus' => L_('待处理'),
                            'remark' => L_('请您及时处理！')));
                }
            } else {
                $this->deliver($user);
            }
        }
    }

    /**
     * 新订单推送
     * @param $user
     * @author: 张涛
     * @date: 2020/9/22
     */
    public function newOrder($user)
    {
        $this->deliver($user);
    }

    
    private function deliver($user)
    {
        $client = intval($user['client']);	//1 IOS 2安卓
        $device_id = str_replace('-', '', $user['device_id']);
        $audience = array('tag' => array($device_id));
        if ($user['jpush_registrationId']) {
            $audience['registration_id'] = [$user['jpush_registrationId']];
        }

        if ($device_id == 'packapp') {
            return false;
        }
    
        $title = L_('订单提醒');
        $msg = L_('您有新的待处理订单');

        $voice_mp3 = C('config.deliver_new_order');
        if (substr($voice_mp3, -3) == 'mp3') {
            $voice_mp3 = file_domain() . $voice_mp3;
        } else {
            //$voice_return = json_decode($this->voic_baidu(), true);
            //$voice_access_token = $voice_return['access_token'];
            //$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . ($voice_mp3 ?: $msg) . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
            $voice_mp3 = text2audio($msg);
        }

		/*if(empty($voice_mp3)){
			$voice_return = json_decode($this->voic_baidu(), true);
			$voice_access_token = $voice_return['access_token'];
			$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msg . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
		}else{
			$voice_mp3 = file_domain().$voice_mp3;
		}*/
		
        $url = C('config.config_site_url') . '/packapp/deliver/index.html?gopage=grab';
        $js_url = C('config.config_site_url') . '/packapp/deliver/grab.html';
		
		$url = str_replace('http://','https://',$url);
		$js_url = str_replace('http://','https://',$js_url);
					
        $voice_second = C('config.deliver_app_voice_time');
    
        $extra = array(
			'voice_mp3' => $voice_mp3, 
			'voice_second' => $voice_second, 
			'url' => $url,
			'js_url' => $js_url,
			'mp3_label' => 'new_deliver_order'.substr(md5(C('config.config_site_url') . (C('config.deliver_new_order') ? C('config.deliver_new_order') : '')),0,16)
		);
        
        import('@.ORG.Jpush');
        $jpush = new Jpush(C('config.deliver_jpush_appkey'), C('config.deliver_jpush_secret'));
        $notification = $jpush->createBody($client, $title, $msg, $extra, 'group_sound.caf');
        $message = $jpush->createMsg($title, $msg, $extra);
        
        $columns = array();
        $columns['platform'] = $client == 1 ? array('ios') : array('android');
        $columns['audience'] = $audience;
        $columns['notification'] = $notification;
        $columns['message'] = $message;
        $columns['from'] = 'delivery';
        $columns['business_type'] = 'new_order';
		import('@.ORG.plan.plan_msg');
        $plan_msg = new plan_msg();
        $plan_msg->addTask(array('type' => '4', 'content' => array($columns)));
    }
	
	public function deliver_off_work($user){
		$href = C('config.config_site_url') . '/packapp/deliver/index.html?gopage=setting';
		
		if($user['last_time'] == 0){
			return false;
		}
		if ($user['client'] == 0) {
			if ($user['openid']) {
				$model->sendTempMsg('TM00017',
					array('href' => $href,
						'wecha_id' => $user['openid'],
						'first' => $user['name'] . '，' . L_('您当前的接单状态已由调度人员从接单调整为不接单。'),
						'OrderSn' => $supply['order_id'],
						'OrderStatus' => L_('下线提醒'),
						'remark' => L_('请您及时处理！')));
			}
		} else {
			$this->deliver_off_work_app($user);
		}
	}
	public function deliver_off_work_app($user){
		$client = intval($user['client']);	//1 IOS 2安卓
        $device_id = str_replace('-', '', $user['device_id']);
        $audience = array('tag' => array($device_id));
        if ($user['jpush_registrationId']) {
            $audience['registration_id'] = [$user['jpush_registrationId']];
        }
    
        $title = L_('下线提醒');
        $msg = L_('您当前的接单状态已由调度人员从接单调整为不接单。');
    
        
		$voice_mp3 = C('config.deliver_off_work_order');
		if(empty($voice_mp3)){
			//$voice_return = json_decode($this->voic_baidu(), true);
			//$voice_access_token = $voice_return['access_token'];
			//$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msg . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
            $voice_mp3 = text2audio($msg);
		}else{
			$voice_mp3 = file_domain().$voice_mp3;
		}
		
        $url = C('config.config_site_url') . '/packapp/deliver/index.html?gopage=setting';
        $js_url = C('config.config_site_url') . '/packapp/deliver/setting.html';
		
		$url = str_replace('http://','https://',$url);
		$js_url = str_replace('http://','https://',$js_url);
					
        $voice_second = C('config.deliver_app_voice_time');
    
        $extra = array(
			'voice_mp3' => $voice_mp3, 
			'voice_second' => $voice_second, 
			'url' => $url,
			'js_url' => $js_url,
			'mp3_label' => 'deliver_off_work_order'.substr(md5(C('config.config_site_url')),0,16)
		);
        
        import('@.ORG.Jpush');
        $jpush = new Jpush(C('config.deliver_jpush_appkey'), C('config.deliver_jpush_secret'));
        $notification = $jpush->createBody($client, $title, $msg, $extra, 'group_sound.caf');
        $message = $jpush->createMsg($title, $msg, $extra);
        
        $columns = array();
        $columns['platform'] = $client == 1 ? array('ios') : array('android');
        $columns['audience'] = $audience;
        $columns['notification'] = $notification;
        $columns['message'] = $message;
        $columns['from'] = 'delivery';
		import('@.ORG.plan.plan_msg');
        $plan_msg = new plan_msg();
        $plan_msg->addTask(array('type' => '4', 'content' => array($columns)));
	}
	
    private function voic_baidu()
    {
        static $return;

        if (empty($return)) {
            $voic_baidu = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=XU7nTWQzS9vn32bckmrhHbTu&client_secret=1375720f23eed9b7b787e728de5dd1c2";
            import('ORG.Net.Http');
            $return = Http::curlGet($voic_baidu);
        }
        return $return;
    }
    
    public function sendNotice($user, $supply)
    {
        if($user['client'] == 0){
            if($user['openid']){
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $href = C('config.config_site_url') . '/packapp/deliver/index.html?gopage=pick';
				
				$model->sendTempMsg('OPENTM405462911', 
					array(
						'href' => $href, 
						'wecha_id' => $spread_openid, 
						'first' => L_('系统分配一个配送订单给您，请及时处理。订单号：').$supply['real_orderid'].'\n', 
						'keyword1' => L_('待办工作提醒'), 
						'keyword2' =>L_('已发送'),
						'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
						'keyword4' => $user['name'], 
						'remark' => '\n请您及时处理！'
					)
				);
            }
        }else if($user['client'] > 0){
            $client = intval($user['client']);	//1 IOS 2安卓
            $device_id = str_replace('-', '', $user['device_id']);
            $audience = array('tag' => array($device_id));
            if ($user['jpush_registrationId']) {
                $audience['registration_id'] = [$user['jpush_registrationId']];
            }
            
            $title = L_('订单提醒');
            $msg = L_('系统分配了新订单，等待您的处理');

            $voice_mp3 = C('config.deliver_system_order');
            if (substr($voice_mp3, -3) == 'mp3') {
                $voice_mp3 = file_domain() . $voice_mp3;
            } else {
                //$voice_return = json_decode($this->voic_baidu(), true);
                //$voice_access_token = $voice_return['access_token'];
                //$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msg . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
                $voice_mp3 = text2audio($msg);
            }

			
            $url = C('config.config_site_url') . '/packapp/deliver/index.html?gopage=pick';
            $js_url = C('config.config_site_url') . '/packapp/deliver/pick.html';
            
			$url = str_replace('http://','https://',$url);
			$js_url = str_replace('http://','https://',$js_url);
			
            $voice_second = C('config.deliver_app_voice_time');
            
            $extra = array(
				'voice_mp3' => $voice_mp3, 
				'voice_second' => $voice_second, 
				'url' => $url,
				'js_url' => $js_url,
				'mp3_label' => 'new_system_deliver_order'.substr(md5(C('config.config_site_url') . (C('config.deliver_system_order') ? C('config.deliver_system_order') : '')),0,16)
			);
            
            import('@.ORG.Jpush');
            $jpush = new Jpush(C('config.deliver_jpush_appkey'), C('config.deliver_jpush_secret'));
            $notification = $jpush->createBody($client, $title, $msg, $extra, 'group_sound.caf');
            $message = $jpush->createMsg($title, $msg, $extra);
            
            $columns = array();
            $columns['platform'] = $client == 1 ? array('ios') : array('android');
            $columns['audience'] = $audience;
            $columns['notification'] = $notification;
            $columns['message'] = $message;
            $columns['from'] = 'delivery';
            $columns['business_type'] = 'new_order';
			import('@.ORG.plan.plan_msg');
            $plan_msg = new plan_msg();
            $plan_msg->addTask(array('type' => '4', 'content' => array($columns)));
        }
    }

    public function sendReminderNotice($user, $supply)
    {
        if($user['client'] == 0){
            if($user['openid']){
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $href = C('config.config_site_url') . '/packapp/deliver/index.html?gopage=detail&supply='.$supply['supply_id'];

                $model->sendTempMsg('OPENTM405462911', 
                    array(
                        'href' => $href, 
                        'wecha_id' => $spread_openid, 
                        'first' => L_('有用户催单啦，请您及时查看。订单号：').$supply['real_orderid'].'\n', 
                        'keyword1' => L_('待办工作提醒'), 
                        'keyword2' =>L_('已发送'),
                        'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
                        'keyword4' => $user['name'], 
                        'remark' => '\n请您及时处理！'
                    )
                );
            } 
        }else if($user['client'] > 0){
            $client = intval($user['client']);  //1 IOS 2安卓
            $device_id = str_replace('-', '', $user['device_id']);
            $audience = array('tag' => array($device_id));

            $title = L_('订单提醒');
            $msg = L_('有用户催单啦，请您及时查看');
            
            $voice_mp3 = C('config.deliver_reminder_mp3');
            if(empty($voice_mp3)){
                //$voice_return = json_decode($this->voic_baidu(), true);
                //$voice_access_token = $voice_return['access_token'];
                //$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msg . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
                $voice_mp3 = text2audio($msg);
            }else{
                $voice_mp3 = file_domain().$voice_mp3;
            }
            
            $url = C('config.config_site_url') . '/packapp/deliver/index.html?gopage=detail&supply_id='.$supply['supply_id'];
            $js_url = C('config.config_site_url') . '/packapp/deliver/detail.html?supply_id='.$supply['supply_id'];
            
            $url = str_replace('http://','https://',$url);
            $js_url = str_replace('http://','https://',$js_url);
            
            $voice_second = C('config.deliver_app_voice_time');
            
            $extra = array(
                'voice_mp3' => $voice_mp3, 
                'voice_second' => $voice_second, 
                'supply_id' => $supply['supply_id'],
                'url' => $url,
                'js_url' => $js_url,
                'mp3_label' => 'new_system_deliver_order'.substr(md5(C('config.config_site_url') . (C('config.deliver_reminder_mp3') ? C('config.deliver_reminder_mp3') : '')),0,16)
            );
            
            import('@.ORG.Jpush');
            $jpush = new Jpush(C('config.deliver_jpush_appkey'), C('config.deliver_jpush_secret'));
            $notification = $jpush->createBody($client, $title, $msg, $extra, 'group_sound.caf');
            $message = $jpush->createMsg($title, $msg, $extra);
            
            $columns = array();
            $columns['platform'] = $client == 1 ? array('ios') : array('android');
            $columns['audience'] = $audience;
            $columns['notification'] = $notification;
            $columns['message'] = $message;
            $columns['business_type'] = 'reminder_order';
            $columns['from'] = 'delivery';
            import('@.ORG.plan.plan_msg');
            $plan_msg = new plan_msg();
            $plan_msg->addTask(array('type' => '4', 'content' => array($columns)));
        }
    }
    
    public function updateStatusToZero($id)
    {
        $this->where(array('item' => 3, 'order_id' => $id))->save(array('status' => 0));
    }

    private function checkTime($time, $start_time, $stop_time, $select)
    {
        $stime = strtotime(date('Y-m-d ' . $start_time));
        $etime = strtotime(date('Y-m-d ' . $stop_time));
        $next_stime = 0;
        $next_etime = 0;
        if ($etime < $stime) {
            $etime = strtotime(date('Y-m-d 23:59:59'));
            $next_stime = strtotime(date('Y-m-d'));
            $next_etime = strtotime(date('Y-m-d ' . $stop_time));
        }
        
        if ($stime <= $time && $time <= $etime) {
            return $select;
        }
        if ($next_stime <= $time && $time <= $next_etime) {
            return $select;
        }
        return 0;
    }
    
//     private function getReduce($discounts, $type, $price, $store_id = 0, $is_discount = 1)
//     {
//         $reduce_money = 0;
//         $return = null;
//         if (isset($discounts[$store_id])) {
//             foreach ($discounts[$store_id] as $row) {
//                 if ($row['type'] == $type && ($row['is_share'] || $is_discount == 0)) {
//                     if ($price >= $row['full_money']) {
//                         if ($reduce_money < $row['reduce_money']) {
//                             $reduce_money = $row['reduce_money'];
//                             $return = $row;
//                         }
//                     }
//                 }
//             }
//         }
//         return $return;
//     }


    // 计算两个地址的距离（直线/骑行)
    public function ajax_distance($start_lat, $start_lng, $end_lat, $end_lng){
        //计算配送距离
        $distance = 0;
        if (C('config.service_is_riding_distance') && C('config.baidu_map_ak')) {
            import('@.ORG.longlat');
            $longlat_class = new longlat();
            $distance = $longlat_class->getRidingDistance($start_lat,$start_lng,$end_lat,$end_lng);
            if ($distance == -1) {
                return 0;
            }
        }
        $distance || $distance = getDistance($start_lat,$start_lng,$end_lat,$end_lng);
        $distance = $distance / 1000;
        return $distance;
    }

    //验证兼职配送员是否可以抢单
    public function checkGrab()
    {
        $return = true;
        $condition['s.uid'] = array('gt',0);
        $condition['u.group'] = 1; // 系统配送员
        $condition['u.user_type'] = 0; // 自有配送员
        $condition['s.status'] = array('in',array(1,2,3,4));
        $deliver_list = D('Deliver_supply')->field('s.uid,count(*) as count,u.max_num')->join(' as s left join '.C('DB_PREFIX').'deliver_user as u on s.uid=u.uid')->where($condition)->group('s.uid')->select();
        if ($deliver_list) {
           foreach ($deliver_list as $value) {
               if ($value['max_num']==0 || $value['count']<$value['max_num']) {
                   $return = false;
                   break;
               }
           }
        }
        return $return;
    }

    /**
     * 店铺配送员指定店铺配送总量
     */
    public function getDeliverCount($uid, $storeId, $extWhere = [])
    {
        if ($uid < 1 || $storeId < 1) {
            return 0;
        }
        $where = [
            'uid' => $uid,
            'status' => ['gt', 4],
            'store_id' => $storeId
        ];
        if ($extWhere) {
            $where = array_merge($where, $extWhere);
        }
        $count = D('Deliver_supply')->where($where)->count();
        return $count;
    }

    // 系统后台营业分析获取数据
    public function getRevenueStatistics($mer_id,$date,$param=array()){
        // 开始时间
        $start_time = $date;
        // 结束时间
        if($param['end_time']){
            $end_time = $param['end_time'];
        }else{
            $end_time = $start_time+86400-1;
        }
        $condition = [];
        // 平台配送
        $condition['type'] = 0;

        // 创建时间
        $condition['create_time'] = array('between',[$start_time,$end_time]);
        $m_condition['o.create_time'] = array('between',[$start_time,$end_time]);
        
        // 是否可以修改order_from
        $order_from_change = 1;
        
        if (!$mer_id) {
            if ($param['area_id'] && $param['area_id'] != 'undefined') {
                $where_mer['area_id'] = $param['area_id'];
                $condition['area_id'] = $param['area_id'];
            } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
                $where_mer['city_id'] = $param['city_idss'];
                $condition['city_id'] = $param['city_idss'];
            } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
                $where_mer['province_id'] = $param['province_idss'];
                $condition['province_id'] = $param['province_idss'];
            }

            if(C('customization.life_tools') && $param['system_session']['level'] != 2){// 城投生活通定制 商家绑定管理员
                $adminIdArr = D('Admin')->getAdminByFid($param['system_session']['id']);
                $where_mer['admin_id'] = ['in', $adminIdArr];               
            }
            
            if ($where_mer) {
                $mer_ids = M('Merchant')->where($where_mer)->select();

                foreach ($mer_ids as $st) {
                    $mer_id_arr[] = $st['mer_id'];
                }
                if (!$mer_id_arr) {
                    return array();
                }
                $m_condition['o.mer_id'] = array('in',$mer_id_arr);
            }
        }else{
            // 商家id
            if ($mer_id==-1) {// 跑腿
                $condition['order_from'] = ['in','3,4,5'];
                $m_condition['d.order_from'] = ['in','3,4,5'];
                $order_from_change = 0;
            }elseif ($mer_id==-2) { // 社区拼团
                $condition['order_from'] = 10;
                $m_condition['d.order_from'] = 10;
                $order_from_change = 0;
            }else{
                $condition['mer_id'] = $mer_id;
                $m_condition['o.mer_id'] = $mer_id;
            }
        }

        /* 平台总配送费 0点到现在 统计时间内所有有效配送订单中平台配送费的总额；包含外卖、跑腿、商城、第三方订单（美团与饿了么）、社区拼团业务中的配送费。注意：旁边加个向下的箭头，点击后展开各个统计明细。*/
        //订单来源(0:快店，1:饿了么，2:美团，3：帮送，4：帮买(指定地址)),5：帮买（未指定地址）10 社区拼团
        $condition['status'] = ['neq',0];
        $total_price = $this->field('sum(freight_charge) as money, sum(deliver_user_fee) as deliver_cash')->where($condition)->find();
        $returnArr['total_money'] = getFormatNumber($total_price['money']);

        /* 平台配送费抽成 */
        $returnArr['plat_freight_charge'] = getFormatNumber($total_price['money']-$total_price['deliver_cash']);

        if(in_array($mer_id, [-1, -2])){
            $returnArr['deliver_money_shop'] = 0;
            $returnArr['plat_freight_charge_shop'] = 0;
        }else {
            // 外卖
            $m_condition['d.item'] = 2;
            $order_from_change && $m_condition['d.order_from'] = 0;
            $m_condition['o.order_from']     = ['neq', 1];
            $total_price                     = $this->field('sum(d.freight_charge) as money, sum(d.deliver_user_fee) as deliver_cash')->join(' as d left join ' . C('DB_PREFIX') . 'shop_order as o on d.order_id=o.order_id')->where($m_condition)->find();
            $returnArr['deliver_money_shop'] = getFormatNumber($total_price['money']);

            /* 平台配送费抽成 */
            $returnArr['plat_freight_charge_shop'] = getFormatNumber($total_price['money'] - $total_price['deliver_cash']);
        }
        // 商城
        if(in_array($mer_id, [-1, -2])){
            $returnArr['deliver_money_mall'] = 0;
            $returnArr['plat_freight_charge_mall'] = 0;
        }else {
            $m_condition['d.item'] = 2;
            $order_from_change && $m_condition['d.order_from'] = 0;
            $m_condition['o.order_from']     = 1;
            $total_price                     = $this->field('sum(d.freight_charge) as money, sum(d.deliver_user_fee) as deliver_cash')->join(' as d left join ' . C('DB_PREFIX') . 'shop_order as o on d.order_id=o.order_id')->where($m_condition)->find();
            $returnArr['deliver_money_mall'] = getFormatNumber($total_price['money']);
            unset($m_condition['d.item']);
            if ($order_from_change) {
                unset($m_condition['d.order_from']);
            }

            unset($m_condition['o.order_from']);

            /* 平台配送费抽成 */
            $returnArr['plat_freight_charge_mall'] = getFormatNumber($total_price['money'] - $total_price['deliver_cash']);
        }
        if(in_array($mer_id, [-2])){
            $returnArr['deliver_money_village_group'] = 0;
            $returnArr['plat_freight_charge_village_group'] = 0;
        }else {
            // 跑腿
            $order_from_change && $condition['order_from'] = ['in', '3,4,5'];
            $total_price                       = $this->field('sum(freight_charge) as money, sum(deliver_user_fee) as deliver_cash')->where($condition)->find();
            $returnArr['deliver_money_paotui'] = getFormatNumber($total_price['money']);
            if ($order_from_change) {
                unset($condition['order_from']);
            }
            /* 平台配送费抽成 */
            $returnArr['plat_freight_charge_paotui'] = getFormatNumber($total_price['money'] - $total_price['deliver_cash']);
        }
        if(in_array($mer_id, [-1, -2])){
            $returnArr['deliver_money_third'] = 0;
            $returnArr['plat_freight_charge_third'] = 0;
        }else {
            // 第三方
            $order_from_change && $condition['order_from'] = ['in', '1,2'];
            $total_price                      = $this->field('sum(freight_charge) as money, sum(deliver_user_fee) as deliver_cash')->where($condition)->find();
            $returnArr['deliver_money_third'] = getFormatNumber($total_price['money']);
            if ($order_from_change) {
                unset($condition['order_from']);
            }
            /* 平台配送费抽成 */
            $returnArr['plat_freight_charge_third'] = getFormatNumber($total_price['money'] - $total_price['deliver_cash']);
        }
        // 社区拼团
        if(in_array($mer_id, [-1])){
            $returnArr['deliver_money_village_group'] = 0;
            $returnArr['plat_freight_charge_village_group'] = 0;
        }else {
            $order_from_change && $condition['order_from'] = 10;
            $total_price                              = $this->field('sum(freight_charge) as money, sum(deliver_user_fee) as deliver_cash')->where($condition)->find();
            $returnArr['deliver_money_village_group'] = getFormatNumber($total_price['money']);
            if ($order_from_change) {
                unset($condition['order_from']);
            }

            /* 平台配送费抽成 */
            $returnArr['plat_freight_charge_village_group'] = getFormatNumber($total_price['money'] - $total_price['deliver_cash']);
        }
        /* 有效订单：未被取消的订单数；取消包括平台、店员操作的取消。*/
        $condition['status'] = ['neq',0];
        $returnArr['effective_count'] = $this->where($condition)->count();

        /* 无效配送单数：被取消的订单数；取消包括平台、店员操作的取消。*/
        $condition['status'] = 0;  // 取消
        $returnArr['invalid_count'] = $this->where($condition)->count();

        /* 损失：平台、店员主动取消订单的退款配送费金额。 */
        $condition['status'] = 0;  // 取消
        $price_today = $this->field('sum(freight_charge) as money')->where($condition)->find();
        $returnArr['loss_money'] = getFormatNumber($price_today['price']);

        /* 配送超时订单数：统计时间内所有有效订单中超过预计送达时间后完成配送的订单。 */
        $condition['status'] = ['neq',0];
        $condition['_string'] = 'end_time>appoint_time';  // 到达时间大于期望送达时间
        $returnArr['overtime_count'] = $this->where($condition)->count();
        unset($condition['_string']);

        /* 配送超时超时率：统计时间内配送超时订单量除以有效配送单量。 */
        $returnArr['overtime_rate'] = $returnArr['effective_count'] ? getFormatNumber($returnArr['overtime_count']/$returnArr['effective_count']*100) : 0;

        /* 差评订单数：统计时间内所有有效订单中配送员评价0<x<=2分的订单数。 */
        $condition['status'] = ['neq',0];
        $condition['score'] = ['in',[1,2]];
        $returnArr['bad_count'] = $this->where($condition)->count();

        /* 差评率：统计时间内配送员差评单量除以配送员评价总单量。 */
        $returnArr['bad_rate'] = $returnArr['effective_count'] ? getFormatNumber($returnArr['bad_count']/$returnArr['effective_count']*100) : 0;

        /* 催单数：统计时间内所有有效订单中平台配送员被催单订单数。 */
        $m_condition['o.user_reminder'] = ['gt',0];
        $returnArr['reminder_count'] = $this->join(' as d left join '.C('DB_PREFIX').'shop_order as o on d.order_id=o.order_id AND d.item=2')->where($m_condition)->count();
        unset($m_condition['o.user_reminder']);

        /* 催单率：统计时间内催单量除以有效配送单量。 */
        $returnArr['reminder_rate'] = $returnArr['effective_count'] ? getFormatNumber($returnArr['reminder_count']/$returnArr['effective_count']*100) : 0;

        /* 活动补贴：：统计时间内所有有效配送订单的补贴配送费总额；只包含外卖业务下的平台满减配送优惠类型下的平台与商家配送费补贴金额、平台配送的打条件免配送费的配送费补贴金额。注意：旁边加个向下的箭头，点击后展开各个业务的统计明细。 */
        // 目前只有外卖有
        $m_condition['d.status'] = ['neq',0];  // 未取消
        $m_condition['d.item'] = 2;
        $order_from_change && $m_condition['d.order_from'] = 0;
        $m_condition['o.order_from'] = ['neq',1];
        $discount_money =  $this->join(' as d left join '.C('DB_PREFIX').'shop_order as o on d.order_id=o.order_id')->field('freight_charge,freight_charge_original,discount_detail')->where($m_condition)->select();
        // 平台活动补贴
        $returnArr['plat_total_money'] = '0.00';
        // 商家活动补贴
        $returnArr['mer_total_money'] = '0.00';
        if ($discount_money) {
            // 总配送费
            $freight_charge_original = 0;
            // 优惠后配送费
            $freight_charge = 0;
            // 平台优惠金额
            $plat_discount_freight_charge = 0;
            // 商家优惠金额
            $mer_discount_freight_charge = 0;
            // 1满减优惠
            foreach ($discount_money as $key => $value) {
                $freight_charge += $value['freight_charge'];
                $freight_charge_original += $value['freight_charge_original'];
                $discount_detail = unserialize($value['discount_detail']);
                foreach ($discount_detail as $_detail) {
                    if ($_detail['discount_type']==5) {
                        if ($_detail['percentage_status']==1) {
                            // 配送费折扣
                            $plat_discount_freight_charge += $_detail['minus2']*$_detail['plat_money'];
                            $mer_discount_freight_charge += $_detail['minus2']*$_detail['merchant_money'];
                        }else{
                            // 配送费满减
                            $plat_discount_freight_charge += $_detail['plat_money'];
                            $mer_discount_freight_charge += $_detail['merchant_money'];
                        }
                    }
                }
            }

            // 优惠总额 = 总配送费 - 优惠后配送费
            $discount_freight_charge = $freight_charge_original - $freight_charge;

            // 2平台免配送费优惠 = 优惠总额-满减优惠
            $plat_free_freight_charge = $discount_freight_charge-$plat_discount_freight_charge-$mer_discount_freight_charge;

            // 平台活动补贴
            $returnArr['plat_total_money'] = getFormatNumber($plat_free_freight_charge+$plat_discount_freight_charge);
            // 商家活动补贴
            $returnArr['mer_total_money'] = getFormatNumber($mer_discount_freight_charge);
        }
        return $returnArr;
    }


    /**
     * 同步配送员3.0新增字段deliver_status
     * @param $data
     * @param $options
     * @author: 张涛
     * @date: 2020/10/23
     */
    protected function _before_insert(&$data, $options)
    {
        if (isset($data['status'])) {
            $data['deliver_status'] = $data['status'];
        }
        return true;
    }

    /**
     * 同步配送员3.0新增字段deliver_status
     * @param $data
     * @param $options
     * @author: 张涛
     * @date: 2020/10/23
     */
    protected function _before_update(&$data, $options)
    {
        if (isset($data['status'])) {
            $data['deliver_status'] = $data['status'];
        }
        return true;
    }


    /**
     * 订单取消通知配送员
     * @author: 张涛
     * @date: 2020/10/29
     */
    public function order_cancel_notice($orderId, $businessType = 'shop')
    {
        $supply = [];
        if ($businessType == 'shop') {
            $supply = $this->where(['order_id' => $orderId, 'item' => 2])->find();
        }
        if (empty($supply) || $supply['uid'] == 0) {
            return false;
        }
        $uid = $supply['uid'];
        $user = D('Deliver_user')->where(['uid' => $uid, 'status' => 1])->find();
        if (empty($user)) {
            return false;
        }

        $client = intval($user['client']);    //1 IOS 2安卓
        $device_id = str_replace('-', '', $user['device_id']);
        $audience = array('tag' => array($device_id));
        $title = L_('订单提醒');
        $msg = L_('您有新的取消订单，请及时查看');

        if (!in_array($client, [1, 2])) {
            return false;
        }

        $url = C('config.config_site_url') . '/packapp/deliver/index.html';
        $js_url = C('config.config_site_url') . '/packapp/deliver/index.html';
        $url = str_replace('http://', 'https://', $url);
        $js_url = str_replace('http://', 'https://', $js_url);
        $voice_mp3 = C('config.deliver_cancel_order');
        if (substr($voice_mp3, -3) == 'mp3') {
            $voice_mp3 = file_domain() . $voice_mp3;
        } else {
            //$voice_return = json_decode($this->voic_baidu(), true);
            //$voice_access_token = $voice_return['access_token'];
            //$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . ($voice_mp3 ?: $msg) . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
            $voice_mp3 = text2audio($msg);
        }

        $voice_second = C('config.deliver_app_voice_time');
        $extra = array(
            'voice_mp3' => $voice_mp3,
            'voice_second' => $voice_second,
            'url' => $url,
            'js_url' => $js_url,
            'mp3_label' => 'new_deliver_order' . substr(md5(C('config.config_site_url') . ($voice_mp3 ?: $msg)), 0, 16)
        );

        import('@.ORG.Jpush');
        $jpush = new Jpush(C('config.deliver_jpush_appkey'), C('config.deliver_jpush_secret'));
        $notification = $jpush->createBody($client, $title, $msg, $extra, 'group_sound.caf');
        $message = $jpush->createMsg($title, $msg, $extra);

        $columns = array();
        $columns['platform'] = $client == 1 ? array('ios') : array('android');
        $columns['audience'] = $audience;
        $columns['notification'] = $notification;
        $columns['message'] = $message;
        $columns['from'] = 'delivery';
        $columns['business_type'] = 'cancel_order';

        import('@.ORG.plan.plan_msg');
        $plan_msg = new plan_msg();
        $plan_msg->addTask(array('type' => '4', 'content' => array($columns)));
    }

    public function getDeliverStatusDesc($status, $isFetchOrder = 1)
    {
        $desc = '';
        switch ($status) {
            case 0:
                $desc = L_('配送失败');
                break;
            case 1:
                $desc = L_('等待接单');
                break;
            case 2:
                $desc = $isFetchOrder ? L_('已接单') : L_('等待接单');
                break;
            case 3:
                $desc = L_('我已到店');
                break;
            case 4:
                $desc = L_('我已取货');
                break;
            case 5:
                $desc = L_('我已送达');
                break;
            case 6:
                $desc = L_('已评价');
                break;
            default:
                $desc = L_('未知状态') . ':' . $status;
                break;
        }
        return $desc;
    }
}
?>