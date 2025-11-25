<?php
namespace miaomai;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * 文档地址：http://dev.erp.miaomaiyun.com/apidoc_v2/#apidoc-home-3
 */
class Miaomaiyun
{
    private $appSecret = '';

    public $params = [];

    private $gateway = 'http://erp.tceasemall.com';
    
    //电商订单上传相关参数
    const ORDER_UPD_INDEX = '/api_v2/mall_order_up/index';
    
    //退货申请原因列表
    const ORDER_REFUND_REASON = '/api_v2/mall_base/refund_reason';
    
    const ORDER_REFUND_APPLY = '/api_v2/mall_order/refund_apply';
    
    //订单上传
    const ORDER_UPD = '/api_v2/mall_order_up/upd';
    
    const CANCEL_ORDER = '/api_v2/mall_order/cancel_order';

    public function __construct()
    {
        cfg('miaomaiyun_erp_gateway') && $this->gateway = cfg('miaomaiyun_erp_gateway');
    }

    public function setConfig($appId, $appSecret, $gateway = '')
    {
        $this->appSecret = $appSecret;
        $this->gateway = $gateway;
        $this->params['appid'] = $appId;
        $this->params['pos_num'] = 'Om7ZM9nw';//终端编号
       
        return $this;
    }

    public function setSecret($appSecret)
    {
        $this->appSecret = $appSecret;
        return $this;
    }

    /**
     * 清空其他参数防止影响签名
     * @author: 张涛
     * @date: 2021/04/02
     */
    public function clearParam()
    {
        $params = $this->params;
        foreach ($params as $k => $v) {
            if ($k !== 'appid') unset($params[$k]);
        }
        $this->params = $params;
        return $this;
    }

    public function setParams($params)
    {
        foreach ($params as $key => $value){
            $this->params[$key] = $value;
        }
        return $this;
    }

    /**
     * 调用接口
     * @param $api string  接口地址
     * @throws GuzzleException
     */
    public function run(string $api)
    {
        $this->params['sign'] = $this->getSign($this->params);
        $client = new Client([
            'base_uri' => $this->gateway,
            'timeout'  => 3.0,
        ]);
        $response = $client->request('POST', $api, [
            'form_params' => $this->params
        ]);
        $contents = $response->getBody()->getContents();
        //dd($this->params, json_decode($contents, true));
        return json_decode($contents, true);
    }

    /**
     * 获取签名
     * @author: 张涛
     * @date: 2021/03/31
     */
    public function getSign($params)
    {
        $parameters = [];
        foreach ($params as $k => $v) {
            $parameters[$k] = $v;
        }
        unset($parameters['sign']);
        unset($parameters['sign_v2']);
        //签名步骤一：按字典序排序参数
        $string = $this->formatBizQueryParaMap($parameters);
        //签名步骤二：在string后加入KEY
        $string = $string . $this->appSecret;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        return strtoupper($string);
    }

    public function formatBizQueryParaMap($paraMap)
    {
        ksort($paraMap);
        return urldecode(http_build_query($paraMap));
    }

}