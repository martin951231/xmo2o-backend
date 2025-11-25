<?php


namespace app\life_tools\model\service;

use app\common\model\db\User;
use app\life_tools\model\db\LifeToolsMember;
use app\life_tools\model\db\LifeToolsOrder;
use app\life_tools\model\db\LifeToolsOrderDetail;
use app\life_tools\model\db\LifeToolsTicket;
use app\life_tools\model\db\LifeToolsTicketSaleDay;

/**
 * 票付通景区
 */
class ScenicOpenPftService
{
    const PFT_APPID = '363';
    const PFT_APPKEY = 'life_tools';
    const PFT_CALLBACK_URL = 'http://ota-supplier.12301dev.com/open';


    public $userModel = null;
    public $lifeToolsMember = null;
    public $lifeToolsTicketSaleDay = null;
    public $lifeToolsTicket = null;
    public $lifeToolsOrder = null;
    public $lifeToolsOrderDetail = null;

    public function __construct()
    { 
        $this->userModel = new User();
        $this->lifeToolsMember = new LifeToolsMember();
        $this->lifeToolsTicketSaleDay = new LifeToolsTicketSaleDay();
        $this->lifeToolsTicket = new LifeToolsTicket();
        $this->lifeToolsOrder = new LifeToolsOrder();
        $this->lifeToolsOrderDetail = new LifeToolsOrderDetail();
    }

    
    /**
     * 生成签名
     * @return string sign 根据数据生成的签名
     */
    public function getSignStr($params)
    {
        $createSignParamArr = [
            'method'    =>  $params['method'],
            'version'   =>  $params['version'],
            'timeStamp' =>  $params['timeStamp'],
            'data'      =>  $params['data'],
            'appid'     =>  self::PFT_APPID,
            'appkey'    =>  self::PFT_APPKEY,
        ];

        ksort($createSignParamArr);
        $paramsStrExceptSign = '';
        foreach ($createSignParamArr as $val) {
            $paramsStrExceptSign .= $val;
        }
        return md5($paramsStrExceptSign);
    }


    /**
     * 格式化data数据
     * @param array data 需要格式化的数据
     * @return string data 格式化后的字符串
     */
    public function encodeData($data)
    {
        if($data){
            $data = base64_encode(json_encode($data));
        }
        return $data;
    }

    /**
     * 解密格式化data数据
     * @param string data 需要解密的数据
     * @return array data 解密后的数据
     */
    public function decodeData($data)
    {
        if($data){
            $data = json_decode(base64_decode($data), true);
        }
        return $data;
    }

    /**
     * 发起请求
     * @param string method 请求方法
     * @param array data 请求的数据
     * @param int requestNum 请求失败重试次数
     * @return array res 返回请求的结果
     */
    public function request($method, $data, $insertRequestId = false, $requestNum = 1)
    {
        $time = time();
        if($insertRequestId){
            $data['apiRequestId'] = $this->getLogId();
        }
        //格式化数据
        $beas64Data = $this->encodeData($data);
        $commonData = [
            'method'    =>  $method,
            'version'   =>  'V1',
            'timeStamp' =>  $time,
            'data'      =>  $beas64Data,
            'appid'     =>  self::PFT_APPID,
            'appkey'    =>  self::PFT_APPKEY
        ];

        //生成签名
        $sign = $this->getSignStr($commonData);
        $commonData['sign'] = $sign;
        $commonData = json_encode($commonData);
        $retry = true;
        $retryNum = 0;
        while($retry && $retryNum < $requestNum){
            $retryNum ++;
            $retry = false;
            //发送请求
            $result = \net\Http::curlQyWxPost(self::PFT_CALLBACK_URL, $commonData);
            if(!$result){
                $retry = true;
            }

            if($result && is_array($result) && isset($result['data']['code']) && in_array($result['data']['code'], [200, 1000])){
                fdump_sql(['request_data'=>$commonData, 'data'=> $data,'response'=>$result], 'pft_request_success');
                return $result;
            }else{
                $retry = true;
            }
            fdump_sql(['request_data'=>$commonData, 'data'=> $data, 'response'=>$result], 'pft_request_error');
        }
    }

    /**
     * 生成唯一ID
     */
    private function getLogId()
    { 
        $chars = md5(uniqid(mt_rand(), true));  
        return substr ( $chars, 0, 8 ) . '-'
                . substr ( $chars, 8, 4 ) . '-' 
                . substr ( $chars, 12, 4 ) . '-'
                . substr ( $chars, 16, 4 ) . '-'
                . substr ( $chars, 20, 12 );
    }

    /**
     * 获取二维码
     */
    private function getQrCode($code)
    {
        require_once '../extend/phpqrcode/phpqrcode.php';
        $date = date('Y-m-d');
        $time = date('Hi');
        $qrcode = new \QRcode();
        $errorLevel = "L";
        $size = "9";
        $dir = '../../runtime/qrcode/employee/'.$date. '/' .$time;
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
        $filename_url = '../../runtime/qrcode/employee/'.$date.'/'.$time . '/' . $code.'.png';
        $qrcode->png($code, $filename_url, $errorLevel, $size);
        $QR = 'runtime/qrcode/employee/'.$date.'/'.$time . '/' . $code.'.png';      //已经生成的原始二维码图片文件
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        return cfg('site_url').'/'.$QR;
    }


    /**
     * 创建订单
     */
    public function orderCreate($params)
    {
        $ticket_id = (int)$params['goodsNo'];
        $ticket = $this->lifeToolsTicket->where('ticket_id', $ticket_id)->find();
        if(!$ticket){
            throw new \think\Exception('门票不存在！');
        }
        if($ticket->is_del != 0){
            throw new \think\Exception('门票已被删除！');
        }
        if($ticket->status != 1){
            throw new \think\Exception('门票已关闭！');
        }
        if($ticket->audit_status != 1){
            throw new \think\Exception('门票未审核通过！');
        }

        $userModel = $this->userModel;
        $user = $userModel->where('phone', $params['contactPhone'])->find();
        $time = time();
        if(!$user){
            $userModel->phone = $params['contactPhone'];
            $userModel->nickname = $params['contactName'] ?? '';
            $userModel->status = 1;
            $userModel->add_time = $time;
            $re = $userModel->save();
            if(!$re){
                throw new \think\Exception('创建临时用户信息失败！');
            }
            $user = $userModel->where('phone', $params['contactPhone'])->find();
        }

        //联系人
        $condition = [];
        $condition[] = ['uid', '=', $user->uid];
        $condition[] = ['phone', '=', $params['contactPhone']];
        $condition[] = ['name', '=', $params['contactName']];
        $memberModel = $this->lifeToolsMember;
        $member = $memberModel->where($condition)->find();
        if(!$member){
            $memberModel->uid = $user->uid;
            $memberModel->phone = $params['contactPhone'];
            $memberModel->name = $params['contactName'];
            $memberModel->is_del = 0;
            $memberModel->add_time = $time;
            $re = $memberModel->save();
            if(!$re){
                throw new \think\Exception('创建联系人信息失败！');
            }
            $member = $memberModel->where($condition)->find();
        }
        

        //使用日期
        $useDate = date('Y-m-d', $params['playTime']);

        $nowDate = date('Y-m-d');
        if($useDate < $nowDate){
            throw new \think\Exception('不可预定过期门票');
        }

        $condition = [];
        $condition[] = ['ticket_id', '=', $ticket_id];
        $condition[] = ['day', '=', $useDate];
        $select = $this->lifeToolsTicketSaleDay->where($condition)->find();
        if(!$select){
            throw new \think\Exception( '请设置价格日历！');
        }


        $paramData = [];
        $paramData['ticket_id'] = $ticket_id;
        $paramData['num'] = $params['ticketNum'];
        $paramData['sku_id'] = 0;
        $paramData['mer_hadpull_id'] = -1;
        $paramData['sys_hadpull_id'] = -1;
        $paramData['member_id'] = $member->member_id;
        $paramData['select_id'] = $select->pigcms_id;
        
        $lifeToolsOrderService = new LifeToolsOrderService();
        $confirm = $lifeToolsOrderService->confirm($paramData, $user->uid);
 

        // if ($pay_price != $confirm['base_info']['pay_price']) {
        //     throw new \think\Exception('支付价格有误');
        // }
        
        if ($confirm['base_info']['limit_num'] < $params['ticketNum']) {
            throw new \think\Exception('库存不足');
        }
        
        $confirm['base_info']['activity_id'] = 0;
        $confirm['base_info']['activity_title'] = '';
        $confirm['base_info']['people_num'] = 0;
        $confirm['base_info']['group_type'] = 1;
        $confirm['base_info']['is_public'] = 1;
        $confirm['custom_form'] = '';
        $confirm['tour_guide_custom_form'] = '';
        $confirm['activity_type'] = '';
        $confirm['order_source'] = 'pft';
        $confirm['source_extra'] = json_encode($params, 256);
        $confirm['third_order_no'] = $params['orderNum'];
        $confirm['third_client_id'] = $params['clientId'];

        $res = $lifeToolsOrderService->saveOrder($confirm, $user->toArray());
        if($res){
            return 'success';
        }else{
            throw new \think\Exception('下单失败！');
        }
    }


    /**
     * 订单支付
     */
    public function orderPay($orderNo, $clientId)
    {
        $condition = [];
        $condition[] = ['third_order_no', '=', $orderNo];
        $condition[] = ['third_client_id', '=', $clientId];
        $order = $this->lifeToolsOrder->where($condition)->find();
        if(!$order){
            throw new \think\Exception('订单不存在！');
        }
        if($order['paid'] == 2){
            return "success";
        }
        if($order['order_status'] != 10){
            throw new \think\Exception('订单状态不支持支付！');
        }
 
        $time = time();
        $order_status = 20;
        $status_note  = '订单支付成功';
        $source = 'pft';
        $source_extra = [];
        $source_extra = json_encode($source_extra);

        $save_data    = [
            'paid'         => 2,
            'pay_time'     => $time,
            'last_time'    => $time,
            'source'       =>   $source,
            'source_extra' =>   $source_extra,
        ];
        $this->lifeToolsOrder->updateThis(['order_id' => $order['order_id']], $save_data);

        (new LifeToolsOrderService())->changeOrderStatus($order['order_id'], $order_status, $status_note);

        //通知出票
        $this->pftApiTicket($order);
        return "success";
    }

    
    /**
     * 取消订单
     */
    public function orderCancel($params)
    {
        $condition = [];
        $condition[] = ['third_order_no', '=', $params['orderNum']];
        $condition[] = ['third_client_id', '=', $params['clientId']];
        $order = $this->lifeToolsOrder->where($condition)->find();
        if(!$order){
            throw new \think\Exception('订单不存在！');
        }

        //取消订单
        if($order['order_status'] == 10){

            (new LifeToolsOrderService())->changeOrderStatus($order['order_id'], 60, '用户取消订单（票付通）');

        }else if($order['order_status'] == 60){
        //已经取消了的订单不做处理


        //申请退款
        }else if($order['order_status'] == 20){

            $condition = [];
            $condition[] = ['status', '=', 1];
            $condition[] = ['order_id', '=', $order['order_id']];
            $detailList = $this->lifeToolsOrderDetail->where($condition)->limit($params['cancelNum'])->select()->toArray();
            if(!$detailList){
                throw new \think\Exception('未找到可退的订单！');
            }
            if(count($detailList) < $params['cancelNum']){
                throw new \think\Exception('可退订单数量不足！可退'. count($detailList) . '单');
            }
            $refund_money = 0;
            $detailIds = [];
            foreach($detailList as $key => $val){
                $refund_money += $val['price'];
                $detailIds[] = $val['detail_id'];
            }
            if($refund_money > $order['price']){
                $refund_money = $order['price'];
            }
            $condition[] = ['detail_id', 'in', $detailIds];
            $this->lifeToolsOrderDetail->where($condition)->update([
                'refund_status' =>  1
            ]);
    
            $reason_msg = '用户申请退款（票付通）';
    
            $refund = [
                'reply_refund_reason' => $reason_msg,
                'reply_refund_time'   => time(),
                'refund_money'        => $refund_money,
                'third_refund_order_sn' => $params['pftSerialNum']
            ];
            $this->lifeToolsOrder->where('order_id', $order['order_id'])->update($refund);
    
            (new LifeToolsOrderService())->changeOrderStatus($order['order_id'], 45, $reason_msg);
    

        }else{
            throw new \think\Exception('该订单状态不支持申请退款');
        }
       
     
        return 'success';
    }


    /**
     * 订单详情
     */
    public function orderDetail($params)
    {
        $condition = [];
        $condition[] = ['third_order_no', '=', $params['orderNum']];
        $condition[] = ['third_client_id', '=', $params['clientId']];
        $order = $this->lifeToolsOrder->where($condition)->find();
        if(!$order){
            throw new \think\Exception('订单不存在！');
        }

        $orderDetail = $this->lifeToolsOrderDetail->where('order_id', $order['order_id'])->cursor();

        $codeStr = '';
        $ticketNum = $cancelNum = $verifiedNum = 0;
        foreach($orderDetail as $item)
        {
            $ticketNum ++;
            $codeStr .= $item->code . ',';
            switch($item->status){
                case 2: //核销数量
                    $verifiedNum ++;
                    break;
                case 3: //退款数量
                    $cancelNum ++;
                    break;
            }
        }
        $codeStr = rtrim($codeStr, ',');

        $orderStatus = 1;
        //已使用
        if($ticketNum == $verifiedNum){
            $orderStatus = 2;
        }
        //已取消
        else if($ticketNum ==  $cancelNum){
            $orderStatus = 3;
        }
        //部分使用
        else if($verifiedNum && $verifiedNum < $ticketNum){
            $orderStatus = 4;
        }

        #    返回参数
        #    orderNum	string	订单号	是
        #    apiOrder	string	三方系统订单号	是
        #    apiCode	string	三方系统凭证码(多个逗号分隔)	是
        #    ticketNum	string	门票数量	是
        #    cancelNum	string	已经取消数量	是
        #    verifiedNum	string	已经验证数量	是
        #    orderStatus	string	订单状态 1：未使用 2：已使用 3：已取消 4：部分使用	是
        #    payStatus	string	支付状态 1：未支付 2：已支付	是
        
        $data = [
            'orderNum'      => $order['third_order_no'],
            'apiOrder'      => $order['real_orderid'],
            'apiCode'       => $codeStr,
            'ticketNum'     => $ticketNum,
            'cancelNum'     => $cancelNum,
            'verifiedNum'   => $verifiedNum,
            'orderStatus'   => $orderStatus,
            'payStatus'     => $order['paid']
        ]; 
       
        return $data;
    }



    /**
     * 请求票付通接口：票付通出票结果通知
     * 
     */
    public function pftApiTicket($order)
    {
        #    请求参数
        #    orderNum	string	票付通订单号	是
        #    apiOrder	string	三方系统订单号	是
        #    apiCode	string	三方系统凭证码(多个逗号分隔)	是
        #    apiQrcode	string	三方系统二维码图片链接(多个逗号分隔)–图片需要可下载	否
        #    touristIDCard	string	一一对应凭证码顺序身份证(多个逗号分隔) –实名制的情况下	否
        

        $condition = [];
        $condition[] = ['order_id', '=', $order['order_id']];
        $condition[] = ['status', '=', 1];
        $codeArr = $this->lifeToolsOrderDetail->where($condition)->column('code');
        $qrcodeArr = [];
        foreach ($codeArr as $key => $code) {
            $qrcodeArr[] = $this->getQrCode($code);
        }
        $codeStr = implode(',', $codeArr);
        $qrcodeStr = implode(',', $qrcodeArr);

        $requestData = [
            'orderNum' => $order['third_order_no'],
            'apiOrder' => $order['real_orderid'],
            'apiCode' => $codeStr,
            'apiQrcode' =>   $qrcodeStr
        ]; 
        $method = 'Order.ApiTicket';

        $this->request($method, $requestData);
    }


    /**
     * 请求票付通接口：票付通取消订单结果通知
     * 
     * @param array order 订单
     * @param int status 操作状态，true=审核通过，false=审核失败
     * @param int num 取消的数量
     * @param int canceledNum 已取消数量
     * 
     */
    public function pftOrderCancel($order, $status, $num, $canceledNum)
    {
        
        #    请求参数
        #    orderNum	string	票付通订单号	是
        #    apiOrder	string	三方系统订单号	是
        #    auditRes	boolen	true:同意 false:拒绝	是
        #    cancelNum	string	本次取消数量;拒绝的情况下值为0	是
        #    canceledNum	string	已经取消数量(不包括本次)	是
        #    cancelMemo	string	备注信息	是
        #    pftSerialNum	string	票付通退票申请流水号	是
        

        $requestData = [
            'orderNum' => $order['third_order_no'],
            'apiOrder' => $order['real_orderid'],
            'auditRes' => $status ? true : false,
            'cancelNum' =>  $num,
            'canceledNum' =>  $canceledNum,
            'cancelMemo' =>  $order['refund_refuse_reason'],
            'pftSerialNum' =>  $order['third_refund_order_sn']
        ];
       
        $method = 'Order.ApiCancel';
        
        $this->request($method, $requestData);
    }

    /**
     * 请求票付通接口：核验结果通知
     */
    public function pftOrderVerify($order, $params)
    {

        #    orderNum	string	票付通订单号	是
        #    apiOrder	string	三方系统订单号	是
        #    apiRequestId	string	本次验证的唯一请求id	是
        #    verifyNum	string	本次验证数量	是
        #    verifiedNum	string	不包括本次已经验证数量	是
        #    verifyIDCard	string	本次验证的身份证号; 没有的时候传空(实名制的情况下必须传);多个逗号分隔	否
        #    verifyCode	string	本次验证的凭证码; 没有的时候传空（一票一码的情况下必须传;多个逗号分隔）	否
        
        $requestData = [
            'orderNum' => $order['third_order_no'],
            'apiOrder' => $order['real_orderid'],
            'apiRequestId'  =>  $params['verifyCode'],
            'verifyNum'     =>  $params['verifyNum'],
            'verifiedNum'   =>  $params['verifiedNum'],
            'verifyIDCard'  =>  $params['verifyIDCard'],
            'verifyCode'    =>  $params['verifyCode']
        ];
        
        $method = 'Order.ApiVerify';
        
        $this->request($method, $requestData);
    }

    /**
     * 请求票付通接口：查询订单
     */
    public function pftOrderDetail($order)
    {
        #   orderNum	string	票付通订单号	是

        $requestData = [
            'orderNum' => $order['third_order_no']
        ];
        
        $method = 'Order.ApiGetOrderDetail';
        $this->request($method, $requestData);
    }
}