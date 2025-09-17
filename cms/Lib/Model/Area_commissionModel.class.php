<?php
/**
 * @desc Created by PhpStorm.
 * @author: lumin
 * since: 2020/8/4 11:46 上午
 */
class Area_commissionModel extends Model{
    /**
     * 增加区域代理商佣金
     * @param $money
     * @param $mer_id
     */
    public function addCommission($money, $mer_id, $store_id = 0, $order_type, $order_id){
        if($money > 0){
            $now_merchant = M("Merchant")->where(['mer_id' => $mer_id])->find();
            if($now_merchant){
                //走新版区域代理商佣金
                $data = [
                    'area_id' => 0,
                    'mer_id' => $mer_id,
                    'store_id' => $store_id,
                    'order_type' => $order_type,
                    'order_id' => $order_id,
                    'order_money' => $money,
                    'rate' => 0,
                    'addtime' => date('Y-m-d H:i:s'),
                    'commission' => 0,
                    'is_del' => 0
                ];
                $street_id = $now_merchant['street_id'];
                $province_id = $now_merchant['province_id'];
                $city_id = $now_merchant['city_id'];
                $area_id = $now_merchant['area_id'];

                //商家有单独配置城市区域抽成则读取对应商家配置的
                $area_percent = [];
                if ($mer_id && $order_type) {
                    $setting = $now_merchant['area_business_commission_setting'] ? json_decode($now_merchant['area_business_commission_setting'], true) : [];
                    if (isset($setting[$order_type . '_area_percent_open']) && $setting[$order_type . '_area_percent_open'] == 1) {
                        //开启了单独业务
                        $area_percent = [
                            'province_percent' => $setting[$order_type . '_province_percent'] ?? 0,
                            'city_percent' => $setting[$order_type . '_city_percent'] ?? 0,
                            'area_percent' => $setting[$order_type . '_area_percent'] ?? 0,
                        ];
                    } else if (isset($setting['merchant_area_percent_open']) && $setting['merchant_area_percent_open'] == 1) {
                        //开启了商家总配置
                        $area_percent = [
                            'province_percent' => $setting['merchant_province_percent'] ?? 0,
                            'city_percent' => $setting['merchant_city_percent'] ?? 0,
                            'area_percent' => $setting['merchant_area_percent'] ?? 0,
                        ];
                    }
                } 

                
                $province = M('Area')->where(['area_id' => $province_id])->find();
                $city = M('Area')->where(['area_id' => $city_id])->find();
                $area = M('Area')->where(['area_id' => $area_id])->find();
                $street = M('Area_street')->where(['area_id' => $street_id])->find();

                $province_settlement = function () use ($province, $data, $money,$area_percent) {
                    if (isset($area_percent['province_percent']) && $area_percent['province_percent'] >= 0) {
                        $province['commission_rate'] = $area_percent['province_percent'];
                    }

                    if ($province['commission_rate'] > 0) {
                        $data['area_id'] = $province['area_id'];
                        $data['rate'] = $province['commission_rate'];
                        $data['commission'] = round($money * $data['rate'] / 100, 2);
                        D('Area_commission')->data($data)->add();
                    }
                };
                $city_settlement = function () use ($city, $data, $money,$area_percent) {
                    if (isset($area_percent['city_percent']) && $area_percent['city_percent'] >= 0) {
                        $city['commission_rate'] = $area_percent['city_percent'];
                    }

                    if ($city['commission_rate'] > 0) {
                        $data['area_id'] = $city['area_id'];
                        $data['rate'] = $city['commission_rate'];
                        $data['commission'] = round($money * $data['rate'] / 100, 2);
                        D('Area_commission')->data($data)->add();
                    }
                };
                $area_settlement = function () use ($area, $data, $money,$area_percent) {
                    if (isset($area_percent['area_percent']) && $area_percent['area_percent'] >= 0) {
                        $area['commission_rate'] = $area_percent['area_percent'];
                    }

                    if ($area['commission_rate'] > 0) {
                        $data['area_id'] = $area['area_id'];
                        $data['rate'] = $area['commission_rate'];
                        $data['commission'] = round($money * $data['rate'] / 100, 2);
                        D('Area_commission')->data($data)->add();
                    }
                };
                $street_settlement = function () use ($street, $data, $money) {
                    if ($street['commission_rate'] > 0) {
                        $data['street_id'] = $street['area_id'];
                        $data['rate'] = $street['commission_rate'];
                        $data['commission'] = round($money * $data['rate'] / 100, 2);
                        $this->data($data)->add();
                    }
                };

                //商家单独配置了城市区域佣金
                if ($area_percent) {
                    $area_settlement();
                    $city_settlement();
                    $province_settlement();
                    return true;
                }

                if ($street) {
                    $street_settlement();

                    if ($street['commission_relation'] == 1) {
                        //省级
                        $province_settlement();
                    } else if ($street['commission_relation'] == 2) {
                        //市级
                        $city_settlement();
                        if ($city['commission_relation'] == 1) {
                            $province_settlement();
                        }
                    } else if ($street['commission_relation'] == 3) {
                        //区级
                        $area_settlement();
                        if ($area['commission_relation'] == 1) {
                            //省级
                            $province_settlement();
                        } else if ($area['commission_relation'] == 2) {
                            //市级别
                            $city_settlement();
                            if ($city['commission_relation'] == 1) {
                                $province_settlement();
                            }
                        }
                    }
                } else if ($area) {
                    $area_settlement();
                    if ($area['commission_relation'] == 1) {
                        //省级
                        $province_settlement();
                    } else if ($area['commission_relation'] == 2) {
                        //市级别
                        $city_settlement();
                        if ($city['commission_relation'] == 1) {
                            $province_settlement();
                        }
                    }
                } else if ($city) {
                    $city_settlement();
                    if ($city['commission_relation'] == 1) {
                        $province_settlement();
                    }
                } else if ($province) {
                    $province_settlement();
                }
                return true;




                $province_id = 0;
                $city_id = $now_merchant['city_id'];
                $area_id = $now_merchant['area_id'];
                if($city_id){
                    $city = M('Area')->where(['area_id' =>$city_id])->find();
                    $province_id = $city['area_pid'] ?? 0;
                }
                $data = [
                    'area_id' => 0,
                    'mer_id' => $mer_id,
                    'store_id' => $store_id,
                    'order_type' => $order_type,
                    'order_id' => $order_id,
                    'order_money' => $money,
                    'rate' => 0,
                    'addtime' => date('Y-m-d H:i:s'),
                    'commission' => 0,
                    'is_del' => 0
                ];
                if($province_id){
                    //为省份添加分佣
                    $province = M('Area')->where(['area_id' =>$province_id])->find();
                    if($province['commission_rate'] > 0){
                        $data['area_id'] = $province_id;
                        $data['rate'] = $province['commission_rate'];
                        $data['commission'] = round($money*$data['rate']/100,2);
                        $this->data($data)->add();
                    }
                }
                if($city && $city['commission_rate'] > 0){
                    $data['area_id'] = $city_id;
                    $data['rate'] = $city['commission_rate'];
                    $data['commission'] = round($money*$data['rate']/100,2);
                    $this->data($data)->add();
                }
                if($area_id){
                    //为区域添加分佣
                    $area = M('Area')->where(['area_id' =>$area_id])->find();
                    if($area['commission_rate'] > 0){
                        $data['area_id'] = $area_id;
                        $data['rate'] = $area['commission_rate'];
                        $data['commission'] = round($money*$data['rate']/100,2);
                        $this->data($data)->add();
                    }
                }
            }
        }
        return true;
    }

    public function getAllMoney($area_id,$is_street = false){
        if ($area_id <= 0) {
            return 0;
        }

        $where = ['is_del' => 0];
        $draw_where = ['status' => ['in', [0, 1]], 'is_del' => 0];
        if ($is_street) {
            $where['street_id'] = $area_id;
            $draw_where['street_id'] = $area_id;
        } else {
            $where['area_id'] = $area_id;
            $draw_where['area_id'] = $area_id;
        }

        $all_money = M('Area_commission')->where($where)->sum('commission');//总佣金
        $all_withdraw = M('Area_commission_withdraw')->where($draw_where)->sum('money');//已提现+待审核提现
        return $all_money - $all_withdraw;
    }
}