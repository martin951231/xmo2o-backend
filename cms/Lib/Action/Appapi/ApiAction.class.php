<?php

class ApiAction extends Action
{
    public function returnCode($errno = 0, $errmsg = '', $retval = array())
    {
        $rt = array(
            'err_no' => $errno,
            'err_msg' => $errmsg,
            'retval' => $retval
        );
        $this->ajaxReturn($rt);
    }



    public function orders()
    {
        $supplyFields = 's.supply_id,s.order_id,s.mer_id,s.store_id,s.from_site,s.from_lnt,s.from_lat,s.aim_site,
                s.aim_lnt,s.aim_lat,s.uid,s.status,s.is_fetch_order,s.appoint_time,s.real_orderid,s.change_log,s.back_log,
                s.get_type,s.province_id,s.city_id,s.area_id,s.distance,s.start_time,s.create_time,s.item,s.server_type';
        //待接单订单
        $supplyMod = D('Deliver_supply');
        $tm = time();
        $supplyWhere = [
            's.is_smart' => 0,
            's.type' => 0,
            's.status' => 1,
            's.uid' => 0,
            's.item' => ['IN', [1, 2, 3]],
            's.next_time' => ['lt', $tm],
            '_string' => 'a.smart_deliver_min > 0 AND a.no_grab_min >= 0 AND s.create_time + a.no_grab_min*60 < ' . $tm
        ];
        $orders = $supplyMod->alias('s')
            ->join(C('DB_PREFIX') . 'shop_order AS o ON s.order_id = o.order_id AND s.item=2')
            ->join(C('DB_PREFIX') . 'service_user_publish AS p ON s.order_id = p.publish_id AND s.item=3')
            ->join(C('DB_PREFIX') . 'area AS a ON a.area_id = s.city_id')
            ->where($supplyWhere)
            ->field($supplyFields)
            ->order('s.next_time ASC,s.supply_id DESC')
            ->limit(1)
            ->select();

        //在线可接单平台配送员
        $deliverUserMod = D('Deliver_user');
        $delivers = $deliverUserMod
            ->field('uid,name,phone,store_id,mer_id,city_id,province_id,circle_id,area_id,site,lng,lat,status,group,range,sort,now_lng,now_lat,max_num,delivery_range_type,delivery_range_polygon,traffic,speed_per_hour,order_type')
            ->where(array('status' => 1, 'store_id' => 0, 'is_notice' => 0, 'group' => 1, 'last_time' => array('GT', 0)))
            ->select();

        $config = M('Config')
            ->where(['name' => ['IN', ['load_balance', 'distance_calculation_type', 'open_predict','delay']]])
            ->getField('name,value');

        $load = $config['load_balance'] ?? 1;
        $distanceCalculationType = $config['distance_calculation_type'] ?? 1;
        $openPredict = $config['open_predict'] ?? 0;
        $delay = $config['delay'] ?? 0;

        foreach ($delivers as $dk => $d) {
            //过滤掉超出最大接单量的，记录配送员当前身上订单
            $currentOrder = $supplyMod->alias('s')
                ->field($supplyFields)
                ->where(array('s.uid' => $d['uid'], 's.type' => 0, 's.item' => array('IN', array(1, 2)), 's.status' => array(array('GT', 0), array('LT', 5))))->select();
            //$d['max_num'] = $d['max_num'] > 0 ? $d['max_num'] : 3;
            if ($d['max_num'] > 0 && ceil($d['max_num'] * $load) <= count($currentOrder)) {
                unset($delivers[$dk]);
                continue;
            }
            //区分接单(2) 取货(3) 开始配送(4)
            $accept = array();
            $pick = array();
            $send = array();
            foreach ($currentOrder as $c) {
                if ($c['status'] == 2) {
                    $accept[] = $c;
                } else if ($c['status'] == 3) {
                    $pick[] = $c;
                } else if ($c['status'] == 4) {
                    $send[] = $c;
                }
            }

            $delivers[$dk]['accept_orders'] = $accept;
            $delivers[$dk]['pick_orders'] = $pick;
            $delivers[$dk]['send_orders'] = $send;
            if (count($send)) {
                $delivers[$dk]['state'] = 2;
            } else if (count($pick)) {
                $delivers[$dk]['state'] = 1;
            } else {
                $delivers[$dk]['state'] = 0;
            }

            unset($currentOrder);
        }
        $delivers = array_values($delivers);

        //为每个订单筛选在配送范围内的配送员
        foreach ($orders as $k => $o) {
            $rightDeliver = array();
            $backUids = $o['back_log'] ? explode(',', $o['back_log']) : array();
            foreach ($delivers as $user) {
                $orderTypes = explode(',', $user['order_type']);
                //配送员可接单过滤
                if (in_array($o['item'], [2, 4, 5]) && !in_array(1, $orderTypes)) {
                    //商家单
                    continue;
                } else if ($o['item'] == 3 && in_array($o['server_type'], [2, 3]) && !in_array(2, $orderTypes)) {
                    //帮我买
                    continue;
                } else if ($o['item'] == 3 && $o['server_type'] == 1 && !in_array(3, $orderTypes)) {
                    //帮我送
                    continue;
                }

                //扔回的配送员不再分配
                if (in_array($user['uid'], $backUids)) {
                    continue;
                }
                if ($user['delivery_range_type'] == 0) {
                    //半径
                    $distance = getDistance($user['lat'], $user['lng'], $o['from_lat'], $o['from_lnt']);
                    $distance <= $user['range'] * 1000 && $rightDeliver[] = $user;
                } else if ($user['delivery_range_type'] == 1) {
                    //自定义多边形
                    $user['delivery_range_polygon'] = substr($user['delivery_range_polygon'], 9, strlen($user['delivery_range_polygon']) - 11);
                    $lngLatData = explode(',', $user['delivery_range_polygon']);
                    array_pop($lngLatData);
                    $lngLats = array();
                    foreach ($lngLatData as $lnglat) {
                        $lng_lat = explode(' ', $lnglat);
                        $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
                    }
                    $user['delivery_range_polygon'] = $lngLats ? array($lngLats) : '';
                    isPtInPoly($o['from_lnt'], $o['from_lat'], $user['delivery_range_polygon']) && $rightDeliver[] = $user;
                }
            }
            if (empty($rightDeliver)) {
                unset($orders[$k]);
                continue;
            }
            $orders[$k]['right_deliver'] = $rightDeliver;
        }

        $rt = array(
            'orders' => array_values($orders),
            'config' => [
                'load_balance' => $load,
                'distance_calculation_type' => $distanceCalculationType,
                'open_predict' => $openPredict,
                'delay' => $delay
            ]
        );
        $this->returnCode(0, '', $rt);
    }
}
