<?php

class Address
{
    //地址ID
    public $addressId;

    //前置id，即该地址必须排在前置地址后面
    public $beforeAddressId;

    //地名
    public $name;

    //经度
    public $lng;

    //纬度
    public $lat;

    public function __construct($lng, $lat, $name = '', $addressId = '', $beforeAddressId = '')
    {
        $this->lng = $lng;
        $this->lat = $lat;
        $this->name = $name;
        $this->addressId = $addressId;
        $this->beforeAddressId = $beforeAddressId;
    }
}