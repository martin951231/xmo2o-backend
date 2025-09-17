<?php

namespace app\mall\model\service\third_order_deliver;

use app\mall\model\db\MallOrderRefund;
use app\mall\model\db\MerchantStore;
use map\longLat;
use net\Http;
use think\facade\Cache;

class DeliverApiService
{
    public $link_list = []; //接口链接地址
    public $url = '';
    public $token_key = ''; //缓存token的键名
    public $access_token_key = ''; //缓存access_token的键名

    public function __construct()
    {
        $this->url = cfg('mall_third_deliver_api');
        $this->link_list = [
            'getToken' => '/login', //登录获取token
            'countPrice' => '/system/order/countPrice', //计算订单配送费价格
            'assigned' => '/system/order/assigned', //已付款订单信息开始执行配送任务
            'cancel' => '/system/order/cancel', //取消订单（取消配送）（备货已完成不允许取消订单）
            'realTimeLocation' => '/system/order/realTimeLocation', //位置信息调用
        ];
        $this->token_key = 'xz_weihubao_token';
        $this->access_token_key = 'xz_weihubao_access_token';
    }

    /**
     * 发送请求
     * @param $url_type
     * @param $postData
     * @return mixed
     * @throws \think\Exception
     */
    public function sendPost($url_type, $postData)
    {
        $url = $this->url . $this->link_list[$url_type];
        $headers[] = 'Content-type: application/json';
        if ($url_type != 'getToken') {//除了登录header都需要传token和accesstoken
            $token = Cache::get($this->token_key);
            $access_token = Cache::get($this->access_token_key);
            if (!$token) {
                $res_token = $this->getToken();
                $token = $res_token['token'];
                $access_token = $res_token['accessToken'];
            }
            $headers[] = 'token: ' . $token;
            $headers[] = 'Authorization: Bearer ' . $access_token;
        }
        $postData = is_array($postData) ? json_encode($postData) : $postData;
        $res = Http::curlPostOwnWithHeader($url, $postData, $headers);
        fdump_api([$url, $postData, $headers, $res], 'mall_third_deliver/' . $url_type, 1);
        if (!$res) {
            throw new \think\Exception(L_('请求失败！'));
        }
        $res = is_array($res) ? $res : json_decode($res, true);
        if (!$res) {
            throw new \think\Exception(L_('返回值为空！'));
        }
        if($res['code']==401){
            $xz_weihubao_request_token_time = Cache::get('xz_weihubao_request_token_time')?:0;
            if($xz_weihubao_request_token_time < 5){
                //token失效重新获取token
                Cache::delete($this->token_key);
                Cache::set('xz_weihubao_request_token_time',($xz_weihubao_request_token_time+1));
                return $this->sendPost($url_type, $postData);
            }
        }
        if ($res['code'] != 200) {
            throw new \think\Exception(L_($res['msg']));
        }
        if ($url_type == 'getToken') {
            $res['data']['token'] = $res['token'] ?? '';
        }
        return $res['data'] ?? true;
    }

    /**
     * 获取token
     * @throws \think\Exception
     */
    public function getToken()
    {
        $xz_username = cfg('mall_third_deliver_username'); //用户名
        $xz_password = cfg('mall_third_deliver_password'); //密码
        if (!$xz_username || !$xz_password) {
            throw new \think\Exception(L_('配置信息错误！'));
        }
        $postData = [
            'username' => $xz_username,
            'password' => $xz_password,
        ];
        $res = $this->sendPost('getToken', $postData);
        if (empty($res['token']) || empty($res['accessToken'])) {
            throw new \think\Exception(L_('获取token失败！'));
        }
        Cache::set($this->token_key, $res['token'] ?? '', (8 * 3600 - 60));
        Cache::set($this->access_token_key, $res['accessToken'] ?? '', (8 * 3600 - 60));
        return $res;
    }

    /**
     * 计算订单配送费价格
     * @param $params
     * @return mixed
     * @throws \think\Exception
     */
    public function countPrice($params)
    {
        $longlat = new longLat();
        //用户百度坐标转成WGS84坐标
        $customer_lnglat = $longlat->toBaidu($params['user_lat'], $params['user_lng'], 5, 1);
        if (!$customer_lnglat) {
            throw new \think\Exception(L_('用户坐标转换失败！'));
        }
        //店铺百度坐标转成WGS84坐标
        $store_lnglat = $longlat->toBaidu($params['store_lat'], $params['store_lng'], 5, 1);
        if (!$store_lnglat) {
            throw new \think\Exception(L_('坐标转换失败！'));
        }
        $postData = [
            'customerLnt' => (string)$customer_lnglat['lng'], //顾客地址的经度（WGS-84坐标）
            'customerLat' => (string)$customer_lnglat['lat'], //顾客地址纬度（WGS-84WGS-84坐标）
            'receivingAddress' => $params['user_address'], //收货地址
            'deliveryTime' => $params['user_deliver_time'], //配送时间
            'storeLng' => (string)$store_lnglat['lng'], //店铺地址经度（WGS-84坐标）
            'storeLat' => (string)$store_lnglat['lat'], //店铺地址纬度（WGS-84坐标）
            'storeName' => $params['store_name'], //店铺名称
            'storeAddress' => $params['store_address'], //店铺地址
        ];
        $res = $this->sendPost('countPrice', $postData);
        return $res['deliveryCost'] ?? 0;
    }

    /**
     * 已付款订单信息开始执行配送任务
     * @param $params
     * @return mixed
     * @throws \think\Exception
     */
    public function assigned($order, $details)
    {
        $longlat = new longLat();
        //用户百度坐标转成WGS84坐标
        $customer_lnglat = $longlat->toBaidu($order['address_lat'], $order['address_lng'], 5, 1);
        if (!$customer_lnglat) {
            $customer_lnglat = [
                'lng' => $order['address_lng'],
                'lat' => $order['address_lat'],
            ];
        }
        if(!empty($order['business_type'])&&$order['business_type']=='service'){
            $store = [
                'lat' => $order['from_lat'],
                'long' => $order['from_lng'],
                'name' => $order['from_name'],
                'adress' => $order['from_adress'],
                'phone' => $order['from_phone'],
            ];
        }else{
            $store = (new MerchantStore())->field('store_id,phone,name,long,lat,adress')->where(['store_id' => $order['store_id']])->findOrEmpty()->toArray();
        }
        //店铺百度坐标转成WGS84坐标
        $store_lnglat = $longlat->toBaidu($store['lat'], $store['long'], 5, 1);
        if (!$store_lnglat) {
            $store_lnglat = [
                'lng' => $order['address_lng'],
                'lat' => $order['address_lat'],
            ];
        }
        $goods_list = [];
        foreach ($details as $item) {
            $goods_list[] = [
                'productName' => $item['name'], //商品名称
                'quantity' => $item['num'], //数量
                'price' => $item['price'], //单价价格
            ];
        }
        $express_send_time = explode(',', $order['express_send_time']);
        $postData = [
            'orderId' => $order['order_no'], //订单编号
            'goodsList' => $goods_list, //商品列表
            'customerLnt' => $customer_lnglat['lng'], //顾客地址的经度（WGS-84坐标）
            'customerLat' => $customer_lnglat['lat'], //顾客地址纬度（WGS-84坐标）
            'customerName' => $order['username'], //顾客姓名
            'customerContact' => $order['phone'], //顾客联系方式
            'receivingAddress' => $order['address'], //收货地址
            'orderTime' => $order['create_time'] ? date('Y-m-d H:i:s', $order['create_time']) : '', //下单时间
            'paymentMethod' => '0', //支付方式（0在线支付 1货到付款）
            'paymentStatus' => 0, //支付状态（0已支付 1未支付）
            'deliveryCost' => $order['money_freight'], //配送费用
            'deliveryStatus' => 0, //配送状态（0待抢单 1待取货 2配送中 3配送成功 4配送失败）
            'totalAmount' => $order['money_total'], //订单总金额
            'deliveryTime' => $express_send_time ? date('Y-m-d H:i:s', $express_send_time[0]) : '', //配送时间
            'storeLng' => $store_lnglat['lng'], //店铺地址经度（WGS-84坐标）
            'storeLat' => $store_lnglat['lat'], //店铺地址纬度（WGS-84坐标）
            'storeName' => $store['name'], //店铺名称
            'storeAddress' => $store['adress'], //店铺地址
            'merchantContact' => $store['phone'], //商家联系方式
            'orderRemarks' => $order['remark'], //订单备注
            'businessType' => $order['business_type'] ?? 'mall', //mall-商城，service-跑腿（服务快派）
        ];
        $res = $this->sendPost('assigned', $postData);
        return $res;
    }

    /**
     * 取消订单（取消配送）（备货已完成不允许取消订单）
     * @param $params
     * @param $status 70-用户申请退款有退款原因，其他的无退款原因
     * @return mixed
     * @throws \think\Exception
     */
    public function cancel($params, $status = 0)
    {
        $business_type = $params['business_type'] ?? 'mall';
        if ($status == 70 && $business_type == 'mall') {
            $refund_msg = (new MallOrderRefund())->where('order_id', $params['order_id'])->where('status', 0)->value('reason');
        } else {
            $refund_msg = '用户取消';
        }

        $postData = [
            'orderId' => $params['order_no'], //订单编号
            'remark' => $refund_msg ?: '', //取消原因
            'businessType' => $business_type,
        ];
        $res = $this->sendPost('cancel', $postData);
        return $res;
    }

    /**
     * 位置信息调用
     * @param $params
     * @return mixed
     * @throws \think\Exception
     */
    public function realTimeLocation($params)
    {
        $business_type = $params['business_type'] ?? 'mall';
        $postData = [
            'orderId' => $params['order_no'], //订单编号
            'businessType' => $business_type,
        ];
        $res = $this->sendPost('realTimeLocation', $postData);
        $baiduLngLat = [
            'lng' => 0,
            'lat' => 0,
        ];
        if (!empty($res['longitude']) && !empty($res['latitude']) && $res['longitude'] && $res['latitude']) {
            $longlat = new longLat();
            //WGS-84转百度地图坐标
            $baiduLngLat = $longlat->toBaidu($res['latitude'], $res['longitude'], 1);
            if (!$baiduLngLat) {
                throw new \think\Exception(L_('坐标转换错误！'));
            }
        }
        return [
            'lng' => $baiduLngLat['lng'],
            'lat' => $baiduLngLat['lat'],
        ];
    }
}