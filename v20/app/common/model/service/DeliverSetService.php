<?php

namespace app\common\model\service;

use app\common\model\db\DeliverSet;

class DeliverSetService
{
    /**
     * 获取店铺配送信息
     * @param $store
     * @param int $price
     * @author: 张涛
     * @date: 2020/8/25
     */
    public function getDeliverInfo($store, $price = 0)
    {
        $delivery_fee = 0;
        $per_km_price = 0;
        $basic_distance = 0;

        $delivery_fee2 = 0;
        $per_km_price2 = 0;
        $basic_distance2 = 0;

        $delivery_fee3 = 0;
        $per_km_price3 = 0;
        $basic_distance3 = 0;

        if ($store['deliver_type'] == 0 || $store['deliver_type'] == 3) {
            if ($store['s_is_open_own']) {//开启了店铺的独立配送费的设置
                //配送时段一的配置
                if ($store['s_free_type'] == 0) {//免配送费
                    $delivery_free = 1;
                } elseif ($store['s_free_type'] == 1) {//不免
                    $delivery_fee = $store['s_delivery_fee'];
                    $per_km_price = $store['s_per_km_price'];
                    $basic_distance = $store['s_basic_distance'];
                } elseif ($store['s_free_type'] == 2) {//满免
                    $delivery_free = 2;
                    $delivery_free_money = $store['s_full_money'];
                    if ($price < $store['s_full_money']) {
                        $delivery_fee = $store['s_delivery_fee'];
                        $per_km_price = $store['s_per_km_price'];
                        $basic_distance = $store['s_basic_distance'];
                    }
                }
                //配送时段二的配送
                if ($store['s_free_type2'] == 0) {//免配送费
                    $delivery_free2 = 1;
                } elseif ($store['s_free_type2'] == 1) {//不免
                    $delivery_fee2 = $store['s_delivery_fee2'];
                    $per_km_price2 = $store['s_per_km_price2'];
                    $basic_distance2 = $store['s_basic_distance2'];
                } elseif ($store['s_free_type2'] == 2) {//满免
                    $delivery_free2 = 2;
                    $delivery_free_money2 = $store['s_full_money2'];
                    if ($price < $store['s_full_money2']) {
                        $delivery_fee2 = $store['s_delivery_fee2'];
                        $per_km_price2 = $store['s_per_km_price2'];
                        $basic_distance2 = $store['s_basic_distance2'];
                    }
                }
                //配送时段三的配送
                if ($store['s_free_type3'] == 4) {//跳过配置
                    $delivery_fee3 = C('config.delivery_fee3');
                    $per_km_price3 = C('config.per_km_price3');
                    $basic_distance3 = C('config.basic_distance3');
                } elseif ($store['s_free_type3'] == 0) {//免配送费
                    $delivery_free3 = 1;
                } elseif ($store['s_free_type3'] == 1) {//不免
                    $delivery_fee3 = $store['s_delivery_fee3'];
                    $per_km_price3 = $store['s_per_km_price3'];
                    $basic_distance3 = $store['s_basic_distance3'];
                } elseif ($store['s_free_type3'] == 2) {//满免
                    $delivery_free3 = 2;
                    $delivery_free_money3 = $store['s_full_money3'];
                    if ($price < $store['s_full_money3']) {
                        $delivery_fee3 = $store['s_delivery_fee3'];
                        $per_km_price3 = $store['s_per_km_price3'];
                        $basic_distance3 = $store['s_basic_distance3'];
                    }
                }
                $deliverSetMod = new DeliverSet();
                $set = $deliverSetMod->where(['area_id' => $store['area_id'], 'status' => 1])->find();
                if (empty($set)) {
                    $set = $deliverSetMod->where(['area_id' => $store['city_id'], 'status' => 1])->find();
                    if (empty($set)) {
                        $set = $deliverSetMod->where(['area_id' => $store['province_id'], 'status' => 1])->find();
                    }
                }
                $set = $set ? $set->toArray() : [];
                if ($set) {
                    $delivertime_start = $set['delivertime_start'];
                    $delivertime_stop = $set['delivertime_stop'];
                    $delivertime_start2 = $set['delivertime_start2'];
                    $delivertime_stop2 = $set['delivertime_stop2'];
                    $delivertime_start3 = $set['delivertime_start3'];
                    $delivertime_stop3 = $set['delivertime_stop3'];

                    $deliver_speed_hour = getFormatNumber($set['deliver_speed']) ? $set['deliver_speed'] : C('config.deliver_speed_hour');
                    $deliver_delay_time = getFormatNumber($set['deliver_delay_time']) ? $set['deliver_delay_time'] : C('config.deliver_delay_time');
                } else {
                    $delivery_times = explode('-', C('config.delivery_time'));
                    $delivertime_start = $delivery_times[0] . ':00';
                    $delivertime_stop = $delivery_times[1] . ':00';
                    $delivery_times2 = explode('-', C('config.delivery_time2'));
                    $delivertime_start2 = $delivery_times2[0] . ':00';
                    $delivertime_stop2 = $delivery_times2[1] . ':00';

                    $delivery_times3 = explode('-', C('config.delivery_time3'));
                    $delivertime_start3 = $delivery_times3[0] . ':00';
                    $delivertime_stop3 = $delivery_times3[1] . ':00';

                    $deliver_speed_hour = cfg('deliver_speed_hour');
                    $deliver_delay_time = cfg('deliver_delay_time');
                }
            } else {
                $deliverSetMod = new DeliverSet();
                $set = $deliverSetMod->where(['area_id' => $store['area_id'], 'status' => 1])->find();
                if (empty($set)) {
                    $set = $deliverSetMod->where(['area_id' => $store['city_id'], 'status' => 1])->find();
                    if (empty($set)) {
                        $set = $deliverSetMod->where(['area_id' => $store['province_id'], 'status' => 1])->find();
                    }
                }
                if ($set) {
                    if ($set['freetype'] == 0) {//免配送费
                        $delivery_free = 1;
                    } elseif ($set['freetype'] == 1) {//不免
                        $delivery_fee = $set['base_fee'];
                        $per_km_price = $set['per_km_price'];
                        $basic_distance = $set['base_distance'];
                    } elseif ($set['freetype'] == 2) {//满免
                        $delivery_free = 1;
                        $delivery_free_money = $set['full_money'];
                        if ($price < $set['full_money']) {
                            $delivery_fee = $set['base_fee'];
                            $per_km_price = $set['per_km_price'];
                            $basic_distance = $set['base_distance'];
                        }
                    }
                    //配送时段二的配送
                    if ($set['freetype2'] == 0) {//免配送费
                        $delivery_free2 = 1;
                    } elseif ($set['freetype2'] == 1) {//不免
                        $delivery_fee2 = $set['base_fee2'];
                        $per_km_price2 = $set['per_km_price2'];
                        $basic_distance2 = $set['base_distance2'];
                    } elseif ($set['freetype2'] == 2) {//满免
                        $delivery_free2 = 2;
                        $delivery_free_money2 = $set['full_money2'];
                        if ($price < $set['full_money2']) {
                            $delivery_fee2 = $set['base_fee2'];
                            $per_km_price2 = $set['per_km_price2'];
                            $basic_distance2 = $set['base_distance2'];
                        }
                    }
                    if ($set['freetype3'] == 0) {//免配送费
                        $delivery_free3 = 1;
                    } elseif ($set['freetype3'] == 1) {//不免
                        $delivery_fee3 = $set['base_fee3'];
                        $per_km_price3 = $set['per_km_price3'];
                        $basic_distance3 = $set['base_distance3'];
                    } elseif ($set['freetype3'] == 2) {//满免
                        $delivery_free3 = 2;
                        $delivery_free_money3 = $set['full_money3'];
                        if ($price < $set['full_money3']) {
                            $delivery_fee3 = $set['base_fee3'];
                            $per_km_price3 = $set['per_km_price3'];
                            $basic_distance3 = $set['base_distance3'];
                        }
                    }

                    $delivertime_start = $set['delivertime_start'];
                    $delivertime_stop = $set['delivertime_stop'];
                    $delivertime_start2 = $set['delivertime_start2'];
                    $delivertime_stop2 = $set['delivertime_stop2'];
                    $delivertime_start3 = $set['delivertime_start3'];
                    $delivertime_stop3 = $set['delivertime_stop3'];

                    $deliver_speed_hour = getFormatNumber($set['deliver_speed']) ? $set['deliver_speed'] : cfg('deliver_speed_hour');
                    $deliver_delay_time = getFormatNumber($set['deliver_delay_time']) ? $set['deliver_delay_time'] : cfg('deliver_delay_time');
                } else {
                    $delivery_fee = cfg('delivery_fee');
                    $per_km_price = cfg('per_km_price');
                    $basic_distance = cfg('basic_distance');

                    $delivery_fee2 = cfg('delivery_fee2');
                    $per_km_price2 = cfg('per_km_price2');
                    $basic_distance2 = cfg('basic_distance2');

                    $delivery_fee3 = cfg('delivery_fee3');
                    $per_km_price3 = cfg('per_km_price3');
                    $basic_distance3 = cfg('basic_distance3');


                    $delivery_times = explode('-', cfg('delivery_time'));
                    $delivertime_start = $delivery_times[0] . ':00';
                    $delivertime_stop = $delivery_times[1] . ':00';
                    $delivery_times2 = explode('-', cfg('delivery_time2'));
                    $delivertime_start2 = $delivery_times2[0] . ':00';
                    $delivertime_stop2 = $delivery_times2[1] . ':00';
                    $delivery_times3 = explode('-', cfg('delivery_time3'));
                    $delivertime_start3 = $delivery_times3[0] . ':00';
                    $delivertime_stop3 = $delivery_times3[1] . ':00';

                    $deliver_speed_hour = cfg('deliver_speed_hour');
                    $deliver_delay_time = cfg('deliver_delay_time');
                }
            }
        } else {//商家配送|商家或自提|快递配送
            if ($store['reach_delivery_fee_type'] == 0) {

            } elseif ($store['reach_delivery_fee_type'] == 1) {
                $delivery_fee = $store['delivery_fee'];
                $per_km_price = $store['per_km_price'];
                $basic_distance = $store['basic_distance'];

                $delivery_fee2 = $store['delivery_fee2'];
                $per_km_price2 = $store['per_km_price2'];
                $basic_distance2 = $store['basic_distance2'];
            } elseif ($store['reach_delivery_fee_type'] == 2) {
                if ($price < $store['no_delivery_fee_value']) {
                    $delivery_fee = $store['delivery_fee'];
                    $per_km_price = $store['per_km_price'];
                    $basic_distance = $store['basic_distance'];

                    $delivery_fee2 = $store['delivery_fee2'];
                    $per_km_price2 = $store['per_km_price2'];
                    $basic_distance2 = $store['basic_distance2'];
                }
            }
            if ($store['reach_delivery_fee_type2'] == 0) {

            } elseif ($store['reach_delivery_fee_type2'] == 1) {
                $delivery_fee2 = $store['delivery_fee2'];
                $per_km_price2 = $store['per_km_price2'];
                $basic_distance2 = $store['basic_distance2'];
            } elseif ($store['reach_delivery_fee_type2'] == 2) {
                if ($price < $store['no_delivery_fee_value2']) {
                    $delivery_fee2 = $store['delivery_fee2'];
                    $per_km_price2 = $store['per_km_price2'];
                    $basic_distance2 = $store['basic_distance2'];
                }
            }
            if ($store['reach_delivery_fee_type3'] == 0) {

            } elseif ($store['reach_delivery_fee_type3'] == 1) {
                $delivery_fee3 = $store['delivery_fee3'];
                $per_km_price3 = $store['per_km_price3'];
                $basic_distance3 = $store['basic_distance3'];
            } elseif ($store['reach_delivery_fee_type3'] == 2) {
                if ($price < $store['no_delivery_fee_value3']) {
                    $delivery_fee3 = $store['delivery_fee3'];
                    $per_km_price3 = $store['per_km_price3'];
                    $basic_distance3 = $store['basic_distance3'];
                }
            }

            $delivertime_start = $store['delivertime_start'];
            $delivertime_stop = $store['delivertime_stop'];
            $delivertime_start2 = $store['delivertime_start2'];
            $delivertime_stop2 = $store['delivertime_stop2'];
            $delivertime_start3 = $store['delivertime_start3'];
            $delivertime_stop3 = $store['delivertime_stop3'];

            $deliver_speed_hour = cfg('deliver_speed_hour');
            $deliver_delay_time = cfg('deliver_delay_time');
        }

        return array('delivery_fee' => $delivery_fee,
            'basic_distance' => $basic_distance,
            'per_km_price' => $per_km_price,
            'delivertime_start' => $delivertime_start,
            'delivertime_stop' => $delivertime_stop,
            'delivery_fee2' => $delivery_fee2,
            'basic_distance2' => $basic_distance2,
            'per_km_price2' => $per_km_price2,
            'delivertime_start2' => $delivertime_start2,
            'delivertime_stop2' => $delivertime_stop2,
            'delivery_fee3' => $delivery_fee3,
            'basic_distance3' => $basic_distance3,
            'per_km_price3' => $per_km_price3,
            'delivertime_start3' => $delivertime_start3,
            'delivertime_stop3' => $delivertime_stop3,
            'deliver_speed_hour' => $deliver_speed_hour,
            'deliver_delay_time' => $deliver_delay_time,
            'delivery_free' => intval($delivery_free),
            'delivery_free2' => intval($delivery_free2),
            'delivery_free3' => intval($delivery_free3),
            'delivery_free_money' => getFormatNumber($delivery_free_money),
            'delivery_free_money2' => getFormatNumber($delivery_free_money2),
            'delivery_free_money3' => getFormatNumber($delivery_free_money3),
        );
    }

}