<?php

class Algorithm
{
    //订单对象
    public $order = [];

    //配送员对象
    public $deliver = [];

    //计算起始时间
    public $startTime;

    //全局配置
    public $config;

    //有效距离
    public $effectiveDistance = 0;

    //额外距离
    public $extraDistance = 0;

    //所有订单提前送达时间总和
    public $advanceTotalTime = 0;

    //最优路线
    public $bestRoute;

    //不参与线路规划的坐标
    public $notInPlanRoutesAddress = [];

    //可以作为线路规划起点的坐标
    public $canBeStartAddress = [];

    //错误日志
    public $errorMessage = '';

    public function run(Order $order, Deliver $deliver, Config $config)
    {
        $this->order = $order;
        $this->deliver = $deliver;
        $this->config = $config;
        $this->startTime = time();

        //获取订单的有效距离之和
        $this->getEffectiveDistance();
        //获取可以作为起点的地址
        $this->getCanBeStartAddress();
        //获取不参与线路规划的坐标
        $this->getNotInPlanRoutesAddress();
        //规划路线
        //$planRoutes = $this->buildPlanRoutes();
        $planRoutes = $this->buildPlanRoutesNew();
        //获取最优路线
        $this->getTheBestRoute($planRoutes);
        //获取结果
        return $this->getResult();
    }

    public function buildPlanRoutesNew()
    {
        $ordersAddress = [$this->order->desAddress];
        $ordersPickAddress = [$this->order->pickAddress];

        $currentOrdersAddress = array_column($this->deliver->currentOrders, 'desAddress');
        $currentOrdersPickAddress = array_column($this->deliver->currentOrders, 'pickAddress');

        $point = array_merge($ordersAddress, $ordersPickAddress, $currentOrdersAddress, $currentOrdersPickAddress);

        //计算距离当前配送员最近的出发点
        $firstPoint = $this->getStartAddress();
        if (!$firstPoint) {
            $firstPoint = $this->deliver->currentPosition;
        }

        $exceptAddressIds = array_column($this->notInPlanRoutesAddress, 'addressId');
        array_push($exceptAddressIds, $firstPoint->addressId);

        foreach ($point as $k => $v) {
            if (in_array($v->addressId, $exceptAddressIds)) {
                unset($point[$k]);
            }
        }

        $point = array_values($point);
        $path = [$firstPoint];
        $currentPoint = $firstPoint;
        $pointLen = count($point);
        while ($pointLen > 0) {
            $juli = null;
            $nextPoint = null;
            $nextPointKey = 0;
            foreach ($point as $k2 => $v2) {
                if ($v2->beforeAddressId && !in_array($v2->beforeAddressId, $exceptAddressIds)) {
                    continue;
                }
                $distance = getDistance($currentPoint->lat, $currentPoint->lng, $v2->lat, $v2->lng);
                if (is_null($juli) || $distance < $juli) {
                    $nextPoint = $v2;
                    $nextPointKey = $k2;
                    $juli = $distance;
                }
            }
            if ($nextPoint) {
                $path[] = $nextPoint;
                $currentPoint = $nextPoint;
                $exceptAddressIds[] = $nextPoint->addressId;
                unset($point[$nextPointKey]);
            }
            $pointLen--;
        }
        return [$path];
    }

    public function getResult()
    {
        if (empty($this->bestRoute)) {
            $rs = [
                'error_no' => 1,
                'message' => $this->errorMessage,
                'retval' => [
                    'deliver' => $this->deliver,
                    'deliverId' => $this->deliver->deliverId
                ]
            ];
        } else {
            $rs = [
                'error_no' => 0,
                'message' => $this->errorMessage,
                'retval' => [
                    'deliver' => $this->deliver,
                    'deliverId' => $this->deliver->deliverId,
                    'extraDistance' => $this->extraDistance,
                    'extraDistancePerOrder' => $this->extraDistance / ($this->deliver->orderCount + 1),
                    'advanceTotalTime' => $this->advanceTotalTime,
                    'advanceTimePerOrder' => $this->advanceTotalTime / ($this->deliver->orderCount + 1),
                    'orderCount' => $this->deliver->orderCount + 1,
                    'bestRoute' => $this->bestRoute]
            ];
        }
        return $rs;
    }

    //获取有效距离
    public function getEffectiveDistance()
    {
        if ($this->config->distanceCalculationType == 1) {
            $baseDistance = $this->order->effectiveLinearDistance;
            $currentDistance = array_sum(array_column($this->deliver->currentOrders, 'effectiveLinearDistance'));
        } else {
            $baseDistance = $this->order->effectiveRidingDistance;
            $currentDistance = array_sum(array_column($this->deliver->currentOrders, 'effectiveRidingDistance'));
        }
        $this->effectiveDistance = $baseDistance + $currentDistance;
        smart_debug('总有效距离:' . $this->effectiveDistance . 'm');

    }

    public function getCanBeStartAddress()
    {
        foreach ($this->deliver->currentOrders as $v) {
            if ($v->status == Order::ACCEPT_STATUS || $v->status == Order::PICK_STATUS) {
                array_push($this->canBeStartAddress, $v->pickAddress);
            } else if ($v->status == Order::SEND_STATUS) {
                array_push($this->canBeStartAddress, $v->desAddress);
            }
        }
    }

    public function getNotInPlanRoutesAddress()
    {
        foreach ($this->deliver->currentOrders as $v) {
            if ($v->status == Order::SEND_STATUS) {
                array_push($this->notInPlanRoutesAddress, $v->pickAddress);
            }
        }
    }

    //根据配送员位置获取最近的第一个地点
    public function getStartAddress()
    {
        $deliverLng = $this->deliver->currentPosition->lng;
        $deliverLat = $this->deliver->currentPosition->lat;
        //获取可作为起点的
        $firstAddress = null;
        $minDistance = 0;
        if ($this->config->distanceCalculationType == 1) {
            foreach ($this->canBeStartAddress as $v) {
                $distance = getDistance($v->lat, $v->lng, $deliverLat, $deliverLng);
                if (is_null($firstAddress) || $distance < $minDistance) {
                    $firstAddress = $v;
                    $minDistance = $distance;
                }
            }
        }
        return $firstAddress;
    }

    /**
     * 规划路线
     */
    public function buildPlanRoutes()
    {
        $ordersAddress = [$this->order->desAddress];
        $ordersPickAddress = [$this->order->pickAddress];

        $currentOrdersAddress = array_column($this->deliver->currentOrders, 'desAddress');
        $currentOrdersPickAddress = array_column($this->deliver->currentOrders, 'pickAddress');

        $point = array_merge($ordersAddress, $ordersPickAddress, $currentOrdersAddress, $currentOrdersPickAddress);

        //计算距离当前配送员最近的出发点
        $firstPoint = $this->getStartAddress();
        $exceptAddressIds = array_column($this->notInPlanRoutesAddress, 'addressId');
        if ($firstPoint) {
            array_push($exceptAddressIds, $firstPoint->addressId);
        }

        foreach ($point as $k => $v) {
            if (in_array($v->addressId, $exceptAddressIds)) {
                unset($point[$k]);
            }
        }
        $point = array_values($point);
        $paths = Path::combine($point, count($point));
        foreach ($paths as $k => $v) {
            if ($firstPoint) {
                $paths[$k] = array_merge([$firstPoint], $v);
            }
            //删除掉不符合先取后送逻辑的路线
            $addressIds = $exceptAddressIds;
            foreach ($paths[$k] as $vv) {
                array_push($addressIds, $vv->addressId);
                if ($vv->beforeAddressId && !in_array($vv->beforeAddressId, $addressIds)) {
                    unset($paths[$k]);
                    break;
                }
            }
        }
        return array_values($paths);
    }

    public function getTheBestRoute($paths)
    {
        $orderIds = array_column($this->deliver->currentOrders, 'orderId');
        $appointTimes = array_column($this->deliver->currentOrders, 'appointTime');
        $orderAppointTimes = array_combine($orderIds, $appointTimes);
        $orderAppointTimes[$this->order->orderId] = $this->order->appointTime;

        $best = [];
        $index = 0;
        $tm = $this->startTime;
        foreach ($paths as $k => $v) {
            $pos = array($this->deliver->currentPosition->lat, $this->deliver->currentPosition->lng);
            $distance = 0;
            $flag = true;
            $route = '';
            foreach ($v as $r) {
                $route .= '=>' . $r->name . '(' . $r->lat . ',' . $r->lng . ')';
            }
            smart_debug("路线：" . $route);
            $delay = $this->config->delay;
            $delaySec = $delay > 0 ? $delay * 60 : 0;
            foreach ($v as $vv) {
                $pathDistance = getDistance($pos[0], $pos[1], $vv->lat, $vv->lng);
                $distance += $pathDistance;
                $pos = array($vv->lat, $vv->lng);
                $tm += ($pathDistance / 1000) * 3600 / ($this->deliver->speed);
                if (substr($vv->addressId, 0, 2) == 's_') {
                    $orderid = substr($vv->addressId, 2);
                    $appoint = isset($orderAppointTimes[$orderid]) ? $orderAppointTimes[$orderid] : 0;
                    if ($appoint > 0 && $tm > $appoint + $delaySec) {
                        smart_debug(sprintf("订单ID：%s，期望送达时间：%s，实际送达时间：%s,超时路线丢弃", $orderid, date('Y-m-d H:i:s', $appoint), date('Y-m-d H:i:s', $tm)));
                        $flag = false;
                        break;
                    }
                    $this->advanceTotalTime += $appoint - $tm;
                }
            }
            if (!$flag) {
                continue;
            }
            smart_debug(sprintf("订单ID：%s，期望送达时间：%s，实际送达时间：%s,路线合适", $orderid, date('Y-m-d H:i:s', $appoint), date('Y-m-d H:i:s', $tm)));
            if (empty($best) || $distance < $best['distance']) {
                $best = [
                    'path' => $v,
                    'distance' => $distance
                ];
            }
        }
        if ($best) {
            $this->extraDistance = $best['distance'] - $this->effectiveDistance;
            $this->bestRoute = $best['path'];
        } else {
            $this->errorMessage .= '未规划出合适路线';
        }
    }
}