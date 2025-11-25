<?php

namespace app\pay\model\service\channel;

use app\common\model\db\AppointOrder;
use app\common\model\db\SystemOrder;
use app\foodshop\model\db\DiningOrderDetail;
use app\group\model\db\GroupOrder;
use app\mall\model\db\MallOrderDetail;
use app\merchant\model\service\yibao\MerchantYibaoService;
use app\pay\model\db\MallbookPayInfo;
use app\pay\model\db\MallbookRequestLog;
use app\pay\model\db\PayOrderInfo;
use app\shop\model\db\ShopOrderDetail;
use mallbook\MallbookApi;
use think\facade\Db;

class MallbookService
{
    private $agent;
    private $config;

    public function __construct($agent = '', $config = [])
    {
        $this->agent  = $agent;
        $this->config = $config;
    }

    protected static function merOrderId($orderNo)
    {
        $merOrderId = $orderNo . rand(000000, 999999);
        $id         = MallbookRequestLog::where('merOrderId', $merOrderId)->value('id');
        if ($id) {
            return self::merOrderId($orderNo);
        }
        return $merOrderId;
    }

    /**
     * @param $order_no
     * @param $money
     * @param array $extra_cfg
     * @return array
     * @throws \think\Exception
     */
    public function pay($order_no, $money, $extra_cfg = [])
    {
        fdump_api([
            'param'     => $_POST,
            'order_no'  => $order_no,
            'money'     => $money,
            'extra_cfg' => $extra_cfg,
        ], 'mallbook/pay_log', true);
        if (empty($order_no) || empty($money)) {
            throw_exception("订单ID或支付金额必填！");
        }
        switch ($this->agent) {
            case 'wechat_h5'://微信端
                return $this->wechatPay($order_no, $money, $extra_cfg);
            case 'h5'://微信端
                if ($this->config['pay_type'] == 'wechat') {
                    return $this->wechatPay($order_no, $money, $extra_cfg);
                }
                break;
            case 'iosapp':
            case 'androidapp':
                return [
                    'redirect_mini' => 1,
                    'userName' => cfg('pay_wxapp_username'),
                ];
                break;
            case 'wechat_mini':
                return $this->wechatMiniPay($order_no, $money, $extra_cfg);
        }
        fdump_api([
            'param'     => $_POST,
            'order_no'  => $order_no,
            'agent'     => $this->agent,
            'money'     => $money,
            'extra_cfg' => $extra_cfg,
            'msg'       => '当前端口未对接，不能使用'
        ], 'mallbook/pay_error', true);
        throw_exception("当前端口未对接，不能使用");
    }

    //微信公众号支付(微信支付)
    public function wechatPay($orderNo, $money, $extraCfg)
    {
        $merOrderId = $orderNo;

        $goodsDetail = $this->getGoodsDetail($extraCfg['order_type'], $extraCfg['order_id']);
        fdump_api([
            'params' => $goodsDetail,
            'msg'   => '商品详情'
        ], 'mallbook/pay_log', true);
        if(!empty($extraCfg['business_order_sn'])){
            $bizOrderId = $extraCfg['business_order_sn'];
        }else{
            $bizOrderId = $orderNo;
        }
        $params      = [
            'bizOrderId'  => $extraCfg['order_type'] .$bizOrderId,
            'amount'      => $money * 100,
            'orderName'   => $extraCfg['title'] ?: $extraCfg['order_type'] . '订单',
            'payType'     => 'WX_JSAPI',
            'appid'       => $this->config['mallbook_wxapp_appid'],//微信小程序必填
            'openid'      => $extraCfg['openid'],//微信公众号/微信小程序必填
            'terminalIp'  => getRealIp(),
            'goodsDetail' => $goodsDetail,
            'frontUrl'    => cfg('site_url') . '/packapp/plat/pages/my/my_order',
            'notifyUrl'   => cfg('site_url'). '/v20/public/index.php/pay/mallbook_notify',
        ];

        fdump_api([
            'params' => $params,
            'config' => $this->config,
            'agent' => $this->agent, 
            'msg'   => '支付参数'
        ], 'mallbook/pay_log', true);
        
        $mallbookApi = new MallbookApi($this->config['mallbook_merchant_no']);
        $result      = $mallbookApi->paymentOrder($merOrderId, $params);

        $record = [
            'mallbook_merchant_no' => $this->config['mallbook_merchant_no'],
            'mallbook_order_no'  => $merOrderId,
            'order_type'         => $extraCfg['order_type'],
            'order_no'           => $orderNo,
            'pay_type'           => 'WX_JSAPI',
            'amount'             => $money * 100,
            'goods_detail'       => $goodsDetail
        ];
        MallbookPayInfo::create($record);//支付记录
        return [
            'type'       => 'jssdk',
            'info'       => $result['payCode'],
            'outTradeNo' => $merOrderId,
        ];
    }

    //小程序微信支付
    public function wechatMiniPay($orderNo, $money, $extraCfg)
    {
        $merOrderId = $orderNo;

        $goodsDetail = $this->getGoodsDetail($extraCfg['order_type'], $extraCfg['order_id']);
        fdump_api([
            'params' => $goodsDetail,
            'msg'   => '商品详情'
        ], 'mallbook/pay_log', true);
        if(!empty($extraCfg['business_order_sn'])){
            $bizOrderId = $extraCfg['business_order_sn'];
        }else{
            $bizOrderId = $orderNo;
        }
        $params      = [
            'bizOrderId'  => $extraCfg['order_type'] .$bizOrderId,
            'amount'      => $money * 100,
            'orderName'   => $extraCfg['title'] ?: $extraCfg['order_type'] . '订单',
            'payType'     => 'WX_MINI',
            'appid'       => $this->config['mallbook_wxapp_appid'],//微信小程序必填
            'openid'      => $extraCfg['wxapp_openid'],//微信公众号/微信小程序必填
            'terminalIp'  => getRealIp(),
            'goodsDetail' => $goodsDetail,
            'frontUrl'    => 'pages/my/my_order',
            'notifyUrl'   => cfg('site_url'). '/v20/public/index.php/pay/mallbook_notify',
        ];

        fdump_api([
            'params' => $params,
            'config' => $this->config,
            'agent' => $this->agent,
            'msg'   => '支付参数'
        ], 'mallbook/pay_log', true);
        
        $mallbookApi = new MallbookApi($this->config['mallbook_merchant_no']);
        $result      = $mallbookApi->paymentOrder($merOrderId, $params);

        $record = [
            'mallbook_order_no'    => $merOrderId,
            'mallbook_merchant_no' => $this->config['mallbook_merchant_no'],
            'order_type'           => $extraCfg['order_type'],
            'order_no'             => $orderNo,
            'pay_type'             => 'WX_MINI',
            'amount'               => $money * 100,
            'goods_detail'         => $goodsDetail
        ];
        MallbookPayInfo::create($record);//支付记录
        return [
            'type'       => 'jssdk',
            'info'       => $result['payCode'],
            'outTradeNo' => $merOrderId,
        ];
    }

    /**
     * 订单的商品信息
     * @param $orderType
     * @param $orderNo
     * @return array
     */
    public function getGoodsDetail($orderType, $orderId)
    {
        $model = [
            'shop'    => ShopOrderDetail::class,
            'mall3'   => MallOrderDetail::class,
            'mall'    => MallOrderDetail::class,
            'group'   => GroupOrder::class,
            'appoint' => AppointOrder::class,
            'dining'  => DiningOrderDetail::class
        ];
        if (!isset($model[$orderType])) {
            return [[
                'goodsName' => '商品名称'
            ]];
        }
        $goodsInfo   = app($model[$orderType])->getGoodsDetail($orderId);
        $goodsDetail = [];
        if (!empty($goodsInfo)) {
            foreach ($goodsInfo as $goods) {
                array_push($goodsDetail, [
                    'goodsId'   => $goods['goods_id'] ?? '',
                    'goodsName' => $goods['goods_name'],
                    "quantity"  => $goods['goods_num'] ?? '',
                    "price"     => $goods['price'] ?? '',
                ]);
            }
        }
        return $goodsDetail;
    }

    /**
     * 异步通知
     * @return [type] [description]
     */
    public function notice()
    {
        return [
            'status'         => 1,
            'paid_money'     => request()->amount,
            'paid_time'      => time(),
            'transaction_no' => request()->merOrderId
        ];
    }

    public function query($orderNo, $type = 1)
    {
        $merOrderId = self::merOrderId($orderNo);
        $order      = app(PayOrderInfo::class)->getByOrderNo($orderNo);
        fdump_api([
            'order_no' => $orderNo,
            'order'    => $order->toArray(),
            'msg'      => '查找支付订单'
        ], 'mallbook/pay_query', true);
        if (empty($order)) {
            throw_exception("没有找到支付订单");
        }
        $params      = [
            'originalMerOrderId' => $order['orderid'],
            'queryType'          => $type,//1：支付查询 7：异步分账查询
        ];
        $payConfig = json_decode($order['pay_config'], true);
        $merchantNo  = $payConfig['mallbook_merchant_no'];
        $mallbookApi = new MallbookApi($merchantNo);
        $data        = $mallbookApi->queryOrder($merOrderId, $params);
        if($type == 7){//7：异步分账查询
            if($data['status'] == 2){//2：失败
                throw_exception("分账失败");
            }
            if($data['status'] == 0){//0：待处理
                sleep(1);
                return $this->query($orderNo, $type);
            }
            return [
                'status'     => $data['status'],
                'query_time' => time(),
                'merOrderId' => $data['merOrderId']
            ];
        }
        return [
            'status'         => $data['status'],
            'paid_money'     => $data['amount'],
            'paid_time'      => time(),
            'transaction_no' => $data['merOrderId']
        ];
    }

    public function refund($orderNo, $payMoney, $refundMoney, $originalMerOrderId)
    {
        $merOrderId = self::merOrderId($orderNo);
        $order      = app(PayOrderInfo::class)->getByOrderNo($orderNo);
        
        $payInfo = MallbookPayInfo::where('mallbook_order_no', $originalMerOrderId)->find();

        if (empty($order)) {
            fdump_api([
                'order_no' => $orderNo,
                'msg'      => '没有找到支付订单'
            ], 'mallbook/refund_error', true);
            throw_exception("没有找到支付订单");
        }
        $params = [
            'originalMerOrderId'    => $originalMerOrderId,
            'asynMerOrderId'        => $payInfo['mallbook_separate_order_no'],
            'originalRcvMerOrderId' => $payInfo['mallbook_receive_order_no'],
            'refundAmount'          => $refundMoney,
            'notifyUrl'             => cfg('site_url'). '/v20/public/index.php/pay/mallbook_refund_notify',
        ];
        
        $splitList = [];
        if($payInfo['mallbook_separate_order_no']){
            foreach ($payInfo['split_list'] as $v){
                array_push($splitList, [
                        'splitUserId' => $v['splitUserId'],
                        'refundSplitAmount' => bcmul(($params['refundAmount']/$payInfo['amount']), $v['splitAmount'])
                ]);
            }
            $params['splitList'] = $splitList;
        }
        fdump_api([
            'originalMerOrderId' => $originalMerOrderId,
            'params'             => $params,
            'msg'                => '退款请求参数'
        ], 'mallbook/refund_params', true);
        $payConfig = json_decode($order['pay_config'], true);
        cfg('mallbook_my_private_rsa_key', $payConfig['mallbook_my_private_rsa_key']);
        $mallbookApi = new MallbookApi($payConfig['mallbook_merchant_no']);
        $data        = $mallbookApi->refund($merOrderId, $params);
        if($data['status'] == 2){
            throw_exception("退款失败");
        }
        $payInfo->refund_amount = $params['refundAmount'] + $payInfo->refund_amount;
        $payInfo->mallbook_refund_order_no = trim($merOrderId.','.$payInfo->mallbook_refund_order_no, ',');
        $payInfo->save();
        
        fdump_api([
            'data' => $data
        ], 'mallbook/refund', true);
        return [
            'refund_no' => $merOrderId
        ];
    }

    /**
     * 异步分账
     */
    public function asyncSeparateMoney($orderNo, $originalMerOrderId, array $splitList, $merMoney)
    {
        $merOrderId = self::merOrderId($orderNo);
        $order = PayOrderInfo::where(['orderid' => $originalMerOrderId])->find();

        fdump_api([
            'originalMerOrderId' => $originalMerOrderId,
            'order_no' => $orderNo,
            'split_list' => $splitList,
            'mer_money' => $merMoney,
            'msg'      => '分账订单'
        ], 'mallbook/separateMoney', true);
        if (empty($order)) {
            throw_exception("没有找到分账支付订单");
        }
        $params = [
            'originalMerOrderId' => $originalMerOrderId,
            'splitList'          => $splitList,
            'notifyUrl'          => cfg('site_url'). '/v20/public/index.php/pay/async_separate_money_notify',
        ];
        $payConfig = json_decode($order['pay_config'], true);
        cfg('mallbook_my_private_rsa_key', $payConfig['mallbook_my_private_rsa_key']);
        $mallbookApi = new MallbookApi($payConfig['mallbook_merchant_no']);
        $result      = $mallbookApi->complete($merOrderId, $params);
        if($result['status'] == 2){//0：待处理 1：成功 2：失败
            throw_exception("分账失败");
        }
        if($result['status'] == 0){
            $this->query($orderNo, 7);
        }
        Db::transaction(function ()use($merOrderId, $originalMerOrderId, $splitList, $merMoney){
            $payInfo = MallbookPayInfo::where('mallbook_order_no', $originalMerOrderId)->find();
            $payInfo->split_list = $splitList;
            $payInfo->mallbook_separate_order_no = $merOrderId;
            $payInfo->mer_amount = $merMoney * 100;
            $payInfo->save();
        });
        $this->receive($orderNo, $originalMerOrderId, $merOrderId);
        
        return $result;
    }

    /**
     * 确认收货
     * @return array|bool|string
     */
    public function receive($orderNo, $originalMerOrderId, $separateMerOrderId)
    {
        $merOrderId = self::merOrderId($orderNo);
        $order = PayOrderInfo::where(['orderid' => $originalMerOrderId])->find();
        if (empty($order)) {
            fdump_api([
                'order_no' => $orderNo,
                'msg'      => '没有找到支付订单'
            ], 'mallbook/receive_error', true);
            throw_exception("没有找到支付订单");
        }
        $payInfo = MallbookPayInfo::where('mallbook_order_no', $originalMerOrderId)->find();
        
        $splitList = $payInfo['split_list'];
        $receiveSplitList = [];
        foreach ($splitList as $v){
            array_push($receiveSplitList,[
                'splitUserId' => $v['splitUserId'], 
                'rcvSplitAmount' => $v['splitAmount'],
            ]);
        }
            
        $params = [
            'originalMerOrderId' => $originalMerOrderId,
            'asynMerOrderId'     => $separateMerOrderId,
            'rcvAmount'          => $payInfo['amount'],
            'splitList'          => $receiveSplitList
        ];

        $payConfig = json_decode($order['pay_config'], true);
        cfg('mallbook_my_private_rsa_key', $payConfig['mallbook_my_private_rsa_key']);
        $result = Db::transaction(function ()use($payInfo, $payConfig, $merOrderId, $params){
            $mallbookApi = new MallbookApi($payConfig['mallbook_merchant_no']);

            $result = $mallbookApi->receive($merOrderId, $params);

            $payInfo->mallbook_receive_order_no = $merOrderId;
            $payInfo->save();
            
            return $result;
        });
        
        return $result;
    }

    public function separateMoney($mallbookOrderNo, $merId, $money)
    {
        $payInfo = MallbookPayInfo::where('mallbook_order_no', $mallbookOrderNo)->find();
        $platMoney = $payInfo['amount'] - $money * 100;
        if(!empty($payInfo['mallbook_separate_order_no'])){
           if(!empty($payInfo['mallbook_receive_order_no'])){
               return true;
           }
           $merOrderId = self::merOrderId($mallbookOrderNo);
           return $this->receive($mallbookOrderNo, $mallbookOrderNo, $merOrderId);
        }
        $mallbookMerId = MerchantYibaoService::getMallbookMerUid($merId);
        fdump_api([
            'mallbook_order_no' => $mallbookOrderNo,
            'mer_id' => $merId,
            'money' => $money,
            ],'mallbook/separateMoney', true);
        if(empty($mallbookMerId)){
            throw_exception('商家子商户账号不可用');
        }
        $splitList = [
            ['splitUserId' => $mallbookMerId, 'splitAmount' => $money * 100, 'splitType' => "1"],
        ];
        if($platMoney){
            array_push($splitList,[
                'splitUserId' => $payInfo['mallbook_merchant_no'],
                'splitAmount' => $platMoney,
                'splitType' => "1"
	    ]);
        }
            
       return $this->asyncSeparateMoney($mallbookOrderNo, $mallbookOrderNo, $splitList, $money);
    }
}