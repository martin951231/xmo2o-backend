<?php
spl_autoload_register('autoLoad');

function autoLoad($className)
{
    $fileName = APP_PATH . 'Lib/ORG/smart_delivery/' . $className . '.class.php';
    if (file_exists($fileName)) {
        require_once $fileName;
    }
}

function smart_debug($message)
{
    fdump_api($message, 'smart_deliver_log/' . date('H'), 1);
}

ini_set('memory_limit', '2048M');

class Start
{
    public static function run()
    {
        smart_debug('执行....');

        $config = new Config();
        $api = C('config.site_url') . '/appapi.php?c=Api&a=orders';
        //下次执行间隔
        $interval = 60;
        $tm = time();
        //防止并发执行
        $lockRs = setLock('plan_smart_deliver_run', 45);
        if ($lockRs['error_no']) {
            return true;
        }


        try {
            set_time_limit(45);
            while (true) {
                $rs = httpRequest($api);
                $result = json_decode($rs[1], true);
                $result = $result['retval'];

                if (empty($result['orders'])) {
                    smart_debug("没有待分配订单");
                    break;
                }

                $config->setDelay($result['config']['delay'])->setLoadBalance($result['config']['load_balance'])->setDistanceCalculationType($result['config']['distance_calculation_type'])->setOpenPredict($result['config']['open_predict']);
                foreach ($result['orders'] as $v) {
                    smart_debug("长订单号：" . $v['real_orderid']);

                    $delivers = $v['right_deliver'];
                    //待分配订单
                    $waitForAssignOrder = Order::buildOrderObjectFromArray($v, true);

                    $result = [];
                    foreach ($delivers as $dv) {
                        smart_debug("配送员：" . $dv['name']);
                        $deliver = Deliver::buildDeliverObjectFromArray($dv);
                        //4、执行算法
                        $rs = (new Algorithm())->run($waitForAssignOrder, $deliver, $config);
                        if ($rs['error_no'] == 1) {
                            smart_debug("结果：" . $rs['message']);
                            continue;
                        }
                        smart_debug("结果：合适");
                        $result[] = $rs['retval'];
                    }

                    $supplyId = $waitForAssignOrder->supplyId;
                    $uids = array_column($result, 'deliverId');
                    $mod = M('Smart_delivery_record');
                    $deliverSupplyMod = D('Deliver_supply');
                    $deliverUserMod = M('Deliver_user');
                    $areaMod = M('Area');

                    //按照权重筛选出最优配送员作为预指派订单
                    if (empty($result)) {
                        //这里要增加调度时长判断
                        $area = $areaMod->where(['area_id' => $v['city_id']])->find();
                        if ($area && $area['smart_deliver_min'] > 0 && $tm > $v['create_time'] + $area['smart_deliver_min'] * 60
                        ) {
                            $deliverSupplyMod->where(['supply_id' => $supplyId])->save(['is_smart' => 2]);
                            continue;
                        } else {
                            $deliverSupplyMod->where(['supply_id' => $supplyId])->save(['next_time' => time() + $interval]);
                            smart_debug("未找到适合的配送员");
                            continue;
                        }
                    }

                    $record = [
                        'supply_id' => $supplyId,
                        'uids' => implode(',',$uids)
                    ];
                    $deliverSupplyMod->where(['supply_id' => $supplyId])->save(['is_smart' => 1]);
                    $old = $mod->where(['supply_id' => $supplyId])->find();
                    if ($old) {
                        $mod->where(['supply_id' => $supplyId])->save($record);
                    } else {
                        $mod->add($record);
                    }

                    $supply = $deliverSupplyMod->where(['supply_id' => $supplyId])->find();
                    $bestDeliver = [];
                    $bestScore = 0;

                    //空闲优先
                    $freeBaseScore = 2;
                    $orderCountArr = array_column($result, 'orderCount');
                    $minCount = min($orderCountArr);
                    //额外路程最少优先
                    $extraBaseScore = 1;
                    $extraDistanceArr = array_column($result, 'extraDistancePerOrder');
                    $minExtraDistance = min($extraDistanceArr);
                    //耗时最短
                    $timeBaseScore = 1;
                    $advanceTimeArr = array_column($result, 'advanceTimePerOrder');
                    $maxAdvanceTime = max($advanceTimeArr);

                    $log = "";
                    foreach ($result as $v) {
                        $thisFreeScore = ($minCount / $v['orderCount']) * $freeBaseScore;
                        //$thisExtraScore = ($minExtraDistance / $v['extraDistancePerOrder']) * $extraBaseScore;
                        $thisExtraScore = $extraBaseScore;
                        $thisAdvanceScore = ($v['advanceTimePerOrder'] / $maxAdvanceTime) * $timeBaseScore;
                        $thisScore = $thisFreeScore + $thisExtraScore + $thisAdvanceScore;
                        if ($log) {
                            $log .= "\n";
                        }
                        $log .= sprintf("配送员:%s，订单量:%d，总额外距离:%s，总提前送达时间：%s", $v['deliver']->name, $v['orderCount'], $v['extraDistance'], $v['advanceTotalTime']);
                        $log .= sprintf("\n每单额外距离:%s，每单提前送达时间：%s", $v['extraDistancePerOrder'], $v['advanceTimePerOrder']);
                        $log .= sprintf("\n空闲得分：%s,额外路程得分：%s,提前送达得分:%s，总得分：%s", $thisFreeScore, $thisExtraScore, $thisAdvanceScore, $thisScore);
                        if ($thisScore > $bestScore) {
                            $bestScore = $thisScore;
                            $bestDeliver = $v;
                        }
                    }
                    $log && smart_debug($log);
                    if ($bestDeliver) {
                        $user = $deliverUserMod->where(['uid' => $bestDeliver['deliverId']])->find();
                        if ($supply && $user) {
                            $_POST = [
                                'supply_ids' => $supplyId,
                                'uid' => $bestDeliver['deliverId']
                            ];
                            R('System/Deliver/appointDeliver');
                            D('Deliver_supply')->sendNotice($user, $supply);
                        }
                    }
                }
                sleep(1);
            }
        } catch (Exception $e) {
            //不处理
        }

    }
}
