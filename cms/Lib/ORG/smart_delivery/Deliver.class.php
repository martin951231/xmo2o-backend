<?php

class  Deliver
{
    //配送员id
    public $deliverId;

    //姓名
    public $name;

    //等级
    public $level;

    //状态 0:空闲 1:取餐中 2:送餐中
    public $state;

    //配送范围的类型 0:半径距离 1：自定义
    public $rangeType;

    //自定义范围
    public $polygon;

    //半径
    public $range;

    //交通工具
    public $traffic;

    //速度
    public $speed;

    //当前配送员位置
    public $currentPosition;

    //配送接单订单
    public $acceptOrders = array();

    //已取货订单
    public $pickOrders = array();

    //派送中订单
    public $sendOrders = array();

    //当前订单
    public $currentOrders = [];

    //当前配送员订单量
    public $orderCount = 0;

    //最大订单量
    public $maxOrder;

    //任务列表
    public $schedule;

    //排除已取货的起始位置
    public $exceptPoint = [];

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public static function buildDeliverObjectFromArray($info)
    {
        $deliver = new Deliver();
        $deliver->deliverId = $info['uid'];
        $deliver->name = $info['name'];
        $deliver->state = $info['state'];
        $deliver->speed = $info['speed_per_hour'] > 0 ? $info['speed_per_hour'] : 10;
        $deliver->currentPosition = new Address($info['now_lng'], $info['now_lat'], '配送员：' . $info['name'], 'd_' . $info['uid']);

        $appect = [];
        foreach ($info['accept_orders'] as $v) {
            $appect[] = Order::buildOrderObjectFromArray($v);
        }
        $deliver->acceptOrders = $appect;
        $pick = [];
        foreach ($info['pick_orders'] as $v) {
            $pick[] = Order::buildOrderObjectFromArray($v);
        }
        $deliver->pickOrders = $pick;
        $send = [];
        foreach ($info['send_orders'] as $v) {
            $send[] = Order::buildOrderObjectFromArray($v);
        }
        $deliver->sendOrders = $send;
        $deliver->currentOrders = array_merge($deliver->acceptOrders, $deliver->pickOrders, $deliver->sendOrders);
        $deliver->orderCount = count($deliver->currentOrders);

        return $deliver;
    }
}