<?php

namespace Micropay;

class Base
{
    //保存用户调用接口参数，便于验签
    public $params = [];

    //保存订单信息
    public $orderInfo = [];

    public $key = '';

    public $mch_id = '';

    public $merchant = [];

    public function __construct($params = [], $orderInfo = [])
    {
        $this->params = $params;
        if (empty($this->params['mch_id'])) {
            throw new \Exception('商户ID不能为空');
        } else if (empty($this->params['mcode'])) {
            throw new \Exception('设备终端参数不能为空');
        }
        $this->mch_id = $this->params['mch_id'];

        $device = $this->getDevice();
        if (empty($device)) {
            throw new \Exception('设备不存在');
        } else if (empty($device['mkey'])) {
            throw new \Exception('设备密钥未设置');
        } else {
            $this->key = $device['mkey'];
        }
        //echo $this->getSign();exit;
        if (!$this->checkSign()) {
            throw new \Exception('签名验证失败');
        }

        $this->orderInfo = $orderInfo;
    }

    public function getDevice()
    {
        $device = M('Newland_pos_device')->where(['mch_id' => $this->params['mch_id'], 'mcode' => $this->params['mcode']])->find();
        return $device;
    }


    public function checkSign()
    {
        return $this->getSign() == $this->params['sign'];
    }

    public function getSign()
    {
        $params = $this->params;
        unset($params['sign']);
        $parameters = [];
        foreach ($params as $k => $v) {
            if ($v === '') {
                continue;
            }
            $parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        $string = $this->formatBizQueryParaMap($parameters, false);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}