<?php

class Config
{
    //订单可接收延误时间。单位：分
    public $delay = 0;

    //负载
    public $loadBalance = 1;

    //距离计算方式  1、直线距离（目前）  2、骑行距离
    public $distanceCalculationType = 1;

    //是否开启与指派
    public $openPredict = 0;


    public function setDelay($delay)
    {
        $this->delay = $delay;
        return $this;
    }

    public function setLoadBalance($loadBalance)
    {
        $this->loadBalance = $loadBalance;
        return $this;
    }

    public function setDistanceCalculationType($distanceCalculationType)
    {
        $this->distanceCalculationType = $distanceCalculationType;
        return $this;
    }

    public function setOpenPredict($openPredict)
    {
        $this->openPredict = $openPredict;
        return $this;
    }

}