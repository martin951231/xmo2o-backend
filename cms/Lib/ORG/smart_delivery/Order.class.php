<?php

class Order
{
    public $supplyId;

    //订单号
    public $orderId;

    //长订单号
    public $realOrderId;

    //店铺ID
    public $storeId;

    //商户ID
    public $merId;

    //有效骑行配送距离  单位：m
    public $effectiveRidingDistance;

    //有效直线配送距离 单位：m
    public $effectiveLinearDistance;

    //送达地址
    public $desAddress;

    //取餐地址
    public $pickAddress;

    //期望送达时间
    public $appointTime;

    public $status;

    //接单(2) 取货(3) 开始配送(4)
    const ACCEPT_STATUS = 2;

    const PICK_STATUS = 3;

    const SEND_STATUS = 4;

    public function __construct($orderId, $storeId, $merId, $distance, $pickAddress, $desAddress)
    {
        $this->orderId = $orderId;
        $this->storeId = $storeId;
        $this->merId = $merId;
        $this->effectiveRidingDistance = $distance;
        $this->desAddress = $desAddress;
        $this->pickAddress = $pickAddress;
        $this->effectiveLinearDistance = getDistance($desAddress->lat, $desAddress->lng, $pickAddress->lat, $pickAddress->lng);
    }

    public static function buildOrderObjectFromArray($orderinfo, $isNew = false)
    {
        $orderid = isset($orderinfo['order_id']) ? $orderinfo['order_id'] : 0;
        $storeid = isset($orderinfo['store_id']) ? $orderinfo['store_id'] : 0;
        $merid = isset($orderinfo['mer_id']) ? $orderinfo['mer_id'] : 0;
        $distance = isset($orderinfo['distance']) ? $orderinfo['distance'] * 1000 : 0;
        $fromAddress = new Address($orderinfo['from_lnt'], $orderinfo['from_lat'], sprintf(($isNew ? '新' : '') . '订单%d取', $orderinfo['order_id']), 'q_' . $orderinfo['order_id']);
        $desAddress = new Address($orderinfo['aim_lnt'], $orderinfo['aim_lat'], sprintf(($isNew ? '新' : '') . '订单%d收', $orderinfo['order_id']), 's_' . $orderinfo['order_id'], 'q_' . $orderinfo['order_id']);
        $appointTime = isset($orderinfo['appoint_time']) ? $orderinfo['appoint_time'] : 0;
        $obj = new Order($orderid, $storeid, $merid, $distance, $fromAddress, $desAddress);
        $obj->status = $orderinfo['status'];
        $obj->appointTime = $appointTime;
        $obj->supplyId = isset($orderinfo['supply_id']) ? $orderinfo['supply_id'] : 0;
        $obj->realOrderId = isset($orderinfo['real_orderid']) ? $orderinfo['real_orderid'] : 0;
        return $obj;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }
}