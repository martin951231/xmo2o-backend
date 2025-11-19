<?php

class MallbookPayService
{
    public $agent;
    public $config;

    public function __construct($agent = '', $config = [])
    {
        import('@.ORG.pay.Mallbookpay.Mallbook');
        import('@.ORG.pay.Mallbookpay.MallbookPayApi');
        $this->agent  = $agent;
        $this->config = $config;
    }

    protected static function merOrderId($orderNo)
    {
        $merOrderId = $orderNo . rand(000000, 999999);
        $id = M('mallbook_request_log')->where(['merOrderId' => $merOrderId])->field('id')->find();
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
            'config'    => $this->config,
            'agent'     => $this->agent,
            'param'     => $_POST,
            'order_no'  => $order_no,
            'money'     => $money,
            'extra_cfg' => $extra_cfg,
        ], 'mallbookOldPay/pay_log', true);
        if (empty($order_no) || empty($money)) {
            throw_exception("订单ID或支付金额必填！");
        }
        try{
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
                case 'wechat_mini':
                    return $this->wechatMiniPay($order_no, $money, $extra_cfg);
            }
        }catch (\Exception $e){
            fdump_api([
                'trace'     => $e->getTraceAsString(),
                'msg'       => 'mallbook支付'
            ], 'mallbookOldPay/pay_error', true);
        }
        
        fdump_api([
            'param'     => $_POST,
            'order_no'  => $order_no,
            'agent'     => $this->agent,
            'money'     => $money,
            'extra_cfg' => $extra_cfg,
            'msg'       => '当前端口未对接，不能使用'
        ], 'mallbookOldPay/pay_error', true);
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
            'amount'      => intval($money * 100),
            'orderName'   => $extraCfg['title'] ?: $extraCfg['order_type'] . '订单',
            'payType'     => 'WX_JSAPI',
            'appid'       => C('config.pay_weixin_appid'),
            'openid'      => $this->config['openid'],//微信公众号/微信小程序必填
            'terminalIp'  => $_SERVER['REMOTE_ADDR'] ?: '127.0.0.1',
            'goodsDetail' => $goodsDetail,
            'frontUrl'    => C('config.site_url') . '/packapp/plat/pages/my/my_order',
            'notifyUrl'   => C('config.site_url') . '/appapi.php?c=Pay&a=mallbookPayNotify',
        ];
        fdump_api([
            'params'     => $params,
            'msg'       => '支付参数'
        ], 'mallbookOldPay/wechatPay', true);
        
        $mallbookApi = new MallbookPayApi();
        $result      = $mallbookApi->paymentOrder($merOrderId, $params);
      
        M('Payment_money_order')
            ->where(['orderid'=>$orderNo,'pay_type'=>'weixin'])
            ->data(['pay_method'=>'mallbook','orderNo'=>$orderNo])
            ->save();
        $record = [
            'mallbook_order_no' => $merOrderId,
            'mallbook_merchant_no' => C('config.mallbook_merchant_no'),
            'order_type'         => $extraCfg['order_type'],
            'order_no'           => $orderNo,
            'pay_type'           => 'WX_JSAPI',
            'amount'             => $money * 100,
            'goods_detail'       => json_encode($goodsDetail, JSON_UNESCAPED_UNICODE)
        ];
        M('Mallbook_pay_info')->data($record)->add();//支付记录
        
        return $result['payCode'];
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
            'amount'      => intval($money * 100),
            'orderName'   => $extraCfg['title'] ?: $extraCfg['order_type'] . '订单',
            'payType'     => 'WX_MINI',
            'appid'       => C('config.pay_wxapp_appid'),//微信小程序必填
            'openid'      => $this->config['openid'],//微信公众号/微信小程序必填
            'terminalIp'  => $_SERVER['REMOTE_ADDR'] ?: '127.0.0.1',
            'goodsDetail' => $goodsDetail,
            'frontUrl'    => '/pages/my/my_order',
            'notifyUrl'   => C('site_url'). '/v20/public/index.php/pay/mallbook_notify',
        ];

        $mallbookApi = new MallbookPayApi();
        $result      = $mallbookApi->paymentOrder($merOrderId, $params);
        M('Payment_money_order')
            ->where(['orderid'=>$orderNo])
            ->data(['pay_method'=>'mallbook','orderNo'=>$orderNo])
            ->save();
        
        $record = [
            'mallbook_order_no'    => $merOrderId,
            'mallbook_merchant_no' => C('config.mallbook_merchant_no'),
            'order_type'           => $extraCfg['order_type'],
            'order_no'             => $orderNo,
            'pay_type'             => 'WX_MINI',
            'amount'               => $money * 100,
            'goods_detail'         => json_encode($goodsDetail, JSON_UNESCAPED_UNICODE)
        ];
        M('mallbook_pay_info')->data($record)->add();//支付记录
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
            'shop'    => 'ShopOrderDetail',
            'mall3'   => 'MallOrderDetail',
	        'mall'    => 'MallOrderDetail',
            'group'   => 'GroupOrder',
            'appoint' => 'Appoint_order',
            'dining'  => 'DiningOrderDetail'
        ];
        if (!isset($model[$orderType])) {
            return [[
                'goodsName' => '商品名称'
            ]];
        }
        $goodsInfo   = D($model[$orderType])->getGoodsDetail($orderId);
        fdump_api([
            'sql'     => D($model[$orderType])->getLastSql(),
            'msg'       => '商品详情'
        ], 'mallbookOldPay/wechatPay', true);
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
    public function notice($data)
    {
        $result = $data['result'];
        if(empty($result['merOrderId'])){
            throw_exception('merOrderId缺失');
        }
        $payInfo = M('payment_money_order')->where(['orderid' => $result['merOrderId']])->find();
        if(empty($payInfo)){
            throw_exception('merOrderId不存在');
        }
        $payInfo['mallbook_order_no'] = $result['merOrderId'];
        $payInfo['paid_money'] = getFormatNumber($result['amount']/100);
        M('payment_money_order')->where(['orderid' => $result['merOrderId']])->save($payInfo);
        
        return [
            'order_id'   => $payInfo['orderid'],
            'pay_type'   => $payInfo['pay_type'],
            'is_mobile'  => $payInfo['is_mobile_pay'],
            'order_from' => 1,
            'order_type' => $payInfo['order_type'] ?: 'shop',
            'is_own'     => 0,
            'third_id'   => $payInfo['mallbook_order_no'],
            'pay_money'  => $payInfo['paid_money'],
        ];
    }

    public function query($orderNo, $type = 1)
    {
        $merOrderId = self::merOrderId($orderNo);
        $order      = M('payment_money_order')->where(['orderid' => $orderNo])->find();
        fdump_api([
            'order_no' => $orderNo,
            'order'    => $order,
            'msg'      => '查找支付订单'
        ], 'mallbookOldPay/pay_error', true);
        if (empty($order)) {
            throw_exception("没有找到支付订单");
        }
        $params      = [
            'originalMerOrderId' => $order['paid_extra'],
            'queryType'          =>  $type,//1：支付查询 7：异步分账查询
        ];
        $mallbookApi = new MallbookPayApi();
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

    public function refund($orderNo, $refundMoney, $originalMerOrderId)
    {
        try {
            $merOrderId = self::merOrderId($orderNo);
            $order      = M('payment_money_order')->where(['orderid' => $orderNo])->find();

            $payInfo = M('mallbook_pay_info')->where(['mallbook_order_no' => $originalMerOrderId])->find();

            if (empty($order)) {
                fdump_api([
                    'order_no' => $orderNo,
                    'msg'      => '没有找到支付订单'
                ], 'mallbookOldPay/refund_error', true);
                throw_exception("没有找到支付订单");
            }
            $params = [
                'originalMerOrderId'    => $originalMerOrderId,
                'asynMerOrderId'        => $payInfo['mallbook_separate_order_no'],
                'originalRcvMerOrderId' => $payInfo['mallbook_receive_order_no'],
                'refundAmount'          => $refundMoney * 100,
                'notifyUrl'             => C('site_url') . '/v20/public/index.php/pay/mallbook_refund_notify',
            ];

            $splitList = [];
            if ($payInfo['mallbook_separate_order_no']) {
                $payInfo['split_list'] = json_decode($payInfo['split_list'], true);
                foreach ($payInfo['split_list'] as $v) {
                    $refundAmount = bcmul(($params['refundAmount'] / $payInfo['amount']), $v['splitAmount']);
                    array_push($splitList, [
                        'splitUserId'       => $v['splitUserId'],
                        'refundSplitAmount' => $refundAmount
                    ]);
                    if ($payInfo['mallbook_merchant_no'] != $v['splitUserId']) {//商家退款
                        $merRefundAmount = bcadd($payInfo['mer_refund_amount'], $refundAmount);
                    }
                }
                $params['splitList']        = $splitList;
                $payInfo['mer_refund_amount'] = $merRefundAmount;
            }
            $mallbookApi = new MallbookPayApi();
            $data        = $mallbookApi->refund($merOrderId, $params);

            M('mallbook_pay_info')->where(['mallbook_order_no' => $originalMerOrderId])
                ->data([
                    'mer_refund_amount'        => $payInfo['mer_refund_amount'],
                    'refund_amount'            => $params['refundAmount'] + $payInfo['refund_amount'],
                    'mallbook_refund_order_no' => trim($merOrderId . ',' . $payInfo['mallbook_refund_order_no'], ','),
                ])->save();

            fdump_api([
                'data' => $data
            ], 'mallbookOldPay/refund', true);
            return [
                'error'        => 0,
                'type'         => 'ok',
                'msg'          => '退款申请成功！请注意查收退款通知。',
                'refund_param' => [
                    'refund_id'   => $merOrderId,
                    'refund_time' => time()
                ]
            ];
        }catch (\Exception $e){
            return [
                'error'        => 1,
                'type'         => 'error',
                'msg'          => $e->getMessage()
            ];
        }
    }

    /**
     * 异步分账
     */
    public function asyncSeparateMoney($orderNo, $originalMerOrderId, array $splitList, $merMoney)
    {
        $merOrderId = self::merOrderId($orderNo);
        $order = M('payment_money_order')->where(['orderid' => $originalMerOrderId])->find();
        
        if (empty($order)) {
            fdump_api([
                'order_no' => $orderNo,
                'msg'      => '没有找到支付订单'
            ], 'mallbookOldPay/asyncSeparateMoney_error', true);
            throw_exception("没有找到支付订单");
        }
        $params = [
            'originalMerOrderId' => $originalMerOrderId,
            'splitList'          => $splitList,
            'notifyUrl'          => C('site_url').'/v20/public/index.php/pay/async_separate_money_notify',
        ];
        
        $mallbookApi = new MallbookPayApi();
        $result      = $mallbookApi->complete($merOrderId, $params);
        
	    if($result['status'] == 0){
            $this->query($orderNo, 7);
        }
        M('mallbook_pay_info')->where(['mallbook_order_no'=> $originalMerOrderId])
            ->data([
                'mer_amount' => $merMoney * 100,
                'split_list' => json_encode($splitList, JSON_UNESCAPED_UNICODE),
                'mallbook_separate_order_no' => $merOrderId,
            ])->save();
        
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
        $order = M('payment_money_order')->where(['orderid' => $originalMerOrderId])->find();
        if (empty($order)) {
            fdump_api([
                'order_no' => $orderNo,
                'msg'      => '没有找到支付订单'
            ], 'mallbookOldPay/receive_error', true);
            throw_exception("没有找到支付订单");
        }
        $payInfo = M('mallbook_pay_info')->where(['mallbook_order_no' => $originalMerOrderId])->find();
        
        $splitList = json_decode($payInfo['split_list'], true);
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
        $mallbookApi = new MallbookPayApi();
        
        $result = $mallbookApi->receive($merOrderId, $params);

        return $result;
    }

    public function separateMoney($mallbookOrderNo, $merId, $money)
    {
        $payInfo = M('mallbook_pay_info')->where(['mallbook_order_no'=> $mallbookOrderNo])->find();
        $platMoney = $payInfo['amount'] - $money * 100;
	    if(!empty($payInfo['mallbook_separate_order_no'])){
           if(!empty($payInfo['mallbook_receive_order_no'])){
               return true;
           }
           $merOrderId = self::merOrderId($mallbookOrderNo);
           return $this->receive($mallbookOrderNo, $mallbookOrderNo, $merOrderId);
        }
	
        $mallbookMerId = M('merchant_yibao_info')->where(['mer_id' => $merId, 'audit_state'=> 1])->field('user_id')->find();

        if(empty($mallbookMerId['user_id'])){
            throw_exception('当前商家未注册mallbook商户');
        }
        $splitList = [
            ['splitUserId' => $mallbookMerId['user_id'], 'splitAmount' => $money * 100, 'splitType' => "1"],
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