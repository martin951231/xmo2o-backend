<?php


namespace app\community\model\service;


use app\community\model\db\HouseAbchinaOrderList;
use app\community\model\db\HouseAbchinaSet;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderSummary;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\PlatOrder;
use app\mall\model\db\PayOrderInfo;
use app\pay\model\service\channel\AbChinaNewService;
use app\pay\model\service\PayService;
use think\Config;
use think\Debug;
use think\Env;
use think\Hook;
use think\Request;
use think\response\Redirect as RedirectResponse;
use think\Session;
use think\facade\Cache;
use think\facade\Db;

class AbChinaService
{
    public $data='';
    public $code=200;
    public $header=[];
    /**
     * 查询订单列表
     */
    public function getOrderList($requestContent){
        $abChina = new \app\pay\model\service\channel\AbChinaService();
        $requestMap = $abChina->requestBodyOfBase64($requestContent);//使用base64解析完成后的requestBody
        $requestBodyOfDecoded = $requestMap['requestBodyOfDecoded']??[];
        $propertyId = 0;//物业id
        $result = 0;//请求报文验签结果
        if($requestBodyOfDecoded){
            //解析前的requestBody
            $requestBody = $requestMap['requestBody'];
            //获取缴费中心传送过来的签名
            $signatureString = $requestMap['signatureString'];

            //查询所属物业id
            $propertyId = (new HouseAbchinaSet())->where('merchant_id',$requestBodyOfDecoded->message->info->merchantId)->value('property_id');
        }
        if($requestBodyOfDecoded && $propertyId){
            $abChina = new \app\pay\model\service\channel\AbChinaService($propertyId);
            $result = (string)$abChina->read_cer_and_verify_sign($requestBody,$signatureString);
        }
        fdump_api(date('Y-m-d H:i:s',time())."获取到的报文验签的结果为：".$result,'abchina/getOrderList',1);
        if ('1' !== $result) {
            $responseStr = '缴费中心发送的查账请求报文验签失败！';
        }else{
            $returnCode = '0000';//查询成功
            $returnMessage = '账单查询成功';//查询成功
            if(!$propertyId){
                $returnCode = $orderInfo['error']??'JH02';
                $returnMessage = $orderInfo['msg']??'未查询到物业信息';
                $orderInfo = [
                    'custName'=>'',
                    'custAddress'=>'',
                    'jump_url'=>'',
                    'order'=>[]
                ];
            }else{
                //根据项目账号等信息查询订单
                $orderInfo = $this->getOrderListInfo($requestBodyOfDecoded,$propertyId);
                if(!$orderInfo['order']){
                    //未查询到账单
                    $returnCode = $orderInfo['error']??'JH02';
                    $returnMessage = $orderInfo['msg']??'未查询到账单';
                }
            }
            // 封装info信息
            $info = [
                'epayCode'=>$requestBodyOfDecoded->message->info->epayCode,//缴费项目编号
                'merchantId'=>$requestBodyOfDecoded->message->info->merchantId,//主商户编号
                'traceNo'=>$requestBodyOfDecoded->message->info->traceNo,//缴费中心流水号
                'input1'=>$requestBodyOfDecoded->message->info->input1?? null,
                'input2'=>$requestBodyOfDecoded->message->info->input2?? null,
                'input3'=>$requestBodyOfDecoded->message->info->input3?? null,
                'input4'=>$requestBodyOfDecoded->message->info->input4?? null,
                'input5'=>$requestBodyOfDecoded->message->info->input5?? null,
                'custName'=>$orderInfo['custName'],//户主名称
                'custAddress'=>$orderInfo['custAddress'],//户主地址
                'cacheMem'=>'',//缓存域信息
                'remark'=>'',//备注字段
                'amtRule'=>0,//缴费金额计算规则
                'totalBillCount'=>count($orderInfo['order']),//子账单数量
                'callBackUrl'=>cfg('site_url').'/packapp/village'.$orderInfo['jump_url'],//跳转地址
                'callBackText'=>cfg('site_name'),//跳转提示
            ];


            $respBills = [];//子账单
            foreach ($orderInfo['order'] as $order){
                // 封装info内部类bill，并将其转化为array形式封装给info
                $respBill['billNo'] = $order['billNo'];//账单编号
                $respBill['billName'] = $order['billName'];//账单名称
                $respBill['oweAmt'] = $order['oweAmt'];//欠费金额
                $respBill['feeAmt'] = "0";//手续费
                $respBill['minAmt'] = null;
                $respBill['maxAmt'] = null;
                $respBill['balance'] = null;
                $respBill['expireDate'] = $order['expireDate'];//缴费截止日期'20231001'
                $respBill['rcvMerchantId'] = '';//收款商户号（多商户收款时输送）
                $respBill['rcvAcc'] = '';//收款账号（多商户收款时输送）
                $respBill['tempSplitAcc'] = '';//分账模板号（分账需求时输送）
                // 封装info内部类bill内部的unitDetail
//                $respBill['unitDetail'] = [
//                    'unitName'=>'unitName',
//                    'unitAmount'=>'6.66',
//                    'minUnitNum'=>'1',
//                ];//接口文档没有，未知参数
                $respBill['optionDetails'] = null;//选择套餐（套餐金额缴费时输送）
                $respBill['descDetails'] = $order['respDescDetail'];//账单详情（最多支持 20 对）

//                $respBill['splitSubMerInfos'] = [
//                    [
//                        'splitMerchantId'=>'',
//                        'splitAmount'=>0
//                    ]
//                ];//二级商户的暂时不使用
                $respBill['mustPayFlag'] = null;//子账单是否必选标志（多账单合并支付时输送）
                $respBills[] = $respBill;
            }
            $info['bills'] = $respBills;//账单信息体
            // 有定制电子回单附言信息的，需添加自定义定制附言信息字段
            $info['merchantRemark'] = null;//账单信息体,定制附言信息
            // 封装响应的消息头信息
            $head = [
                'transSeqNum'=>$requestBodyOfDecoded->message->head->transSeqNum,//缴费中心交易序列号
                'transCode'=>$requestBodyOfDecoded->message->head->transCode,//交易码
                'channel'=>$requestBodyOfDecoded->message->head->channel,//渠道编码
                'transFlag'=>'02',
                'timeStamp'=>$this->getTimeStamp('YmdHisu'),//时间戳,格式 yyyyMMddHHmmssSSS，
                'returnCode'=>$returnCode,//返回成功码
                'returnMessage'=>$returnMessage,//返回成功语句
            ];

            $responseBody['format'] = $requestBodyOfDecoded->format;
            $responseBody['message'] = [
                'info'=>array_merge((array)$requestBodyOfDecoded->message->info,(array)$info),
                'head'=>array_merge((array)$requestBodyOfDecoded->message->head,(array)$head),
            ];
            $responseJson = json_encode($responseBody, JSON_UNESCAPED_UNICODE);
            // 加签名
            $signatrue = $abChina->sign_with_sha1_with_rsa($responseJson);
            $responseStr = $signatrue . "||" . (base64_encode($responseJson));
            fdump_api(date('Y-m-d H:i:s',time())."向后台发送的报文加密前为：" . $responseJson,'abchina/getOrderList',1);
        }
        fdump_api(date('Y-m-d H:i:s',time())."向后台发送的报文加密后为：" . $responseStr,'abchina/getOrderList',1);
//      将加签名之后的报文发送给农行
        $this->data = $responseStr;
        $this->header['Content-Type'] = 'text/plain; charset=utf-8';
        return $this->send();
    }

    /**
     * 查询订单
     */
    public function getOrderListInfo($requestBodyOfDecoded,$propertyId)
    {
        $info = $requestBodyOfDecoded->message->info;
        $return = [
            'custName'=>'',
            'custAddress'=>'',
            'jump_url'=>'',
            'order'=>[]
        ];
        $epayCode = $info->epayCode;//缴费项目编号
        $phone = $info->input1;//用户绑定手机号

//        $usernum = '50_50-7-247-1542-9392';
//        $phone = '13095450215';
//        $epayCode= '11111';//测试物业费
//        $info->traceNo= 'JF20230906135725';//测试流水号
        
        
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_new_charge = new HouseNewChargeService();
        $service_house_village = new HouseVillageService();
        $userBindInfo = (new HouseVillageUserBind())->where(['phone'=>$phone])->field('pigcms_id,village_id,name,single_id,floor_id,layer_id')->find();
        $userBindInfo = $userBindInfo ? $userBindInfo->toArray() : [];
        if(!$userBindInfo){
            return $return;
        }
        $villageName = (new HouseVillage())->where('village_id',$userBindInfo['village_id'])->value('village_name');
        
        $return['jump_url'] = '/pages/houseMeter/NewCollectMoney/newLiveExpenses?url_type=village&url_type=village&village_id='.$userBindInfo['village_id'].'&pigcms_id='.$userBindInfo['pigcms_id'];

        //查询缴费类型
        $ePayCodeValue = (new HouseAbchinaSet())->where('property_id',$propertyId)->value('epay_code');
        if(!$ePayCodeValue || !$epayCode || $epayCode != $ePayCodeValue){
            $return['error'] = 'JH18';
            $return['msg'] = '缴费项目配置信息有误，请稍后再试';
            fdump_api(['errMsg'=>'缴费项目配置信息有误，请稍后再试','ePayCodeValue'=>$ePayCodeValue,'epayCode'=>$epayCode],'abchina/err',1);
            return $return;
        }
//        $ePayCodeAry = json_decode($ePayCode,true);
//        $ePayCodeAry = array_column($ePayCodeAry,'key','code');
//        $orderType= $ePayCodeAry[$epayCode]??'';
//        if(!$orderType){
//            $return['error'] = 'JH18';
//            $return['msg'] = '缴费项目配置信息有误，请稍后再试';
//            return $return;
//        }
        
        $return['custName'] = $userBindInfo['name'];
        $return['custAddress'] = $service_house_village->getSingleFloorRoom($userBindInfo['single_id'], $userBindInfo['floor_id'], $userBindInfo['layer_id'], $userBindInfo['pigcms_id'], $userBindInfo['village_id']);
        $return['custAddress'] = $villageName.$return['custAddress'];
        $where = [];
//        $where[] = ['o.pay_bind_id','=',$userBindInfo['pigcms_id']];
        $where[] = ['o.is_discard','=',1];
        $where[] = ['o.is_paid','=',2];
//        $where[] = ['o.order_type','=',$orderType];
//        $where[] = ['o.village_id','=',$userBindInfo['village_id']];
        $where[]=  ['o.check_status','<>',1];
        $where[]=  ['o.phone','=',$phone];
        $field='o.order_id,o.position_id,o.summary_id,o.uid,n.id,n.charge_type,o.late_payment_money,o.modify_money,p.name,r.charge_name,o.total_money,o.service_start_time,o.service_end_time,r.charge_valid_time,o.add_time,o.order_type';
        $list = $db_house_new_pay_order->getList($where, $field, 0,20);
        $list = $list ? $list->toArray() : [];
        $orderSearchAry = [];
        foreach ($list as $k=>$v){
//            $cacheKey  = 'AbChinaOrder:' . $orderType.'_'.$v['order_id'];
            //不展示被锁定的订单
//            $lock = Cache::store('redis')->get($cacheKey);
//            if($lock){
//                unset($list[$k]);
//                continue;
//            }
            
            //组合整理订单返回格式
            $respDescDetail = [
                [
                    'sCpt'=>'收费类别',
                    'sVal'=>$db_house_new_charge->charge_type[$v['charge_type']]
                ],
                [
                    'sCpt'=>'收费项目',
                    'sVal'=>$v['name']
                ],
                [
                    'sCpt'=>'收费标准',
                    'sVal'=>$v['charge_name']
                ],
                [
                    'sCpt'=>'账单开始时间',
                    'sVal'=>($v['service_start_time'] ? date('Y-m-d H:i:s',$v['service_start_time']) : '')
                ],
                [
                    'sCpt'=>'账单结束时间',
                    'sVal'=>($v['service_end_time'] ? date('Y-m-d H:i:s',$v['service_end_time']) : '')
                ],
                [
                    'sCpt'=>'应收费用',
                    'sVal'=>formatNumber($v['total_money'],2,1).'元'
                ],
                [
                    'sCpt'=>'实收费用',
                    'sVal'=>formatNumber(bcadd($v['late_payment_money'],$v['modify_money'],2),2,1).'元'
                ],
                [
                    'sCpt'=>'收费标准生效时间',
                    'sVal'=>($v['charge_valid_time'] ? date('Y-m-d H:i:s',$v['charge_valid_time']) : '')
                ],
                [
                    'sCpt'=>'账单生成时间',
                    'sVal'=>($v['add_time'] ? date('Y-m-d H:i:s',$v['add_time']) : '')
                ],
            ];
            if($v['late_payment_money']>0){
                $respDescDetail[] =[
                        'sCpt'=>'违约金',
                        'sVal'=>formatNumber($v['late_payment_money'],2,1).'元'
                ];
            }
            $list[$k]['billNo'] = $v['order_type'].'_'.$v['order_id'];
            $list[$k]['billName'] = $db_house_new_charge->charge_type[$v['charge_type']];
            $list[$k]['oweAmt'] = round_number(bcadd($v['late_payment_money'],$v['modify_money'],2),2);
//            $list[$k]['expireDate'] = $v['service_end_time'] ? date('Y-m-d H:i:s',$v['service_end_time']) : null;//账单截止日期
            $list[$k]['expireDate'] = '2099-12-31 23:59:59';//账单截止日期
            $list[$k]['respDescDetail'] = $respDescDetail;//暂无账单截止日期
            $orderSearchAry[] = $v['order_type'].'_'.$v['order_id'];
            //其他订单进行锁定
//            Cache::store('redis')->tag('AbChinaOrderTag')->set($cacheKey,1,5);
        }
        //记录本次搜索
        if($orderSearchAry){
            $orderSearchAry = implode(',',$orderSearchAry);
            $addData = [
                'trace_no'=>$info->traceNo,
                'order'=>$orderSearchAry,
                'query_content'=>json_encode($requestBodyOfDecoded),
                'create_time'=>time(),
                'select_time'=>time()+30*60
            ];
            (new HouseAbchinaOrderList())->insert($addData);
        }
        $return['order'] = $list;
        return $return;
    }
    
    /**
     * 订单支付成功后回调
     */
    public function afterPay($requestContent){
        $abChina = new \app\pay\model\service\channel\AbChinaService();
        $requestMap = $abChina->requestBodyOfBase64($requestContent);//使用base64解析完成后的requestBody
        $requestBodyOfDecoded = $requestMap['requestBodyOfDecoded'];
        
//        $requestBodyOfDecoded->message->info->resendTimes = 0;
//        $requestBodyOfDecoded->message->info->amtRule = 0;
//        $requestBodyOfDecoded->message->info->payBillCount = 1;
//        $requestBodyOfDecoded->message->info->payBillAmt = 50;
//        $requestBodyOfDecoded->message->info->payBillNo = 'property_87258';
//        $requestBodyOfDecoded->message->info->payAcc = "1637504602994356";
//        $requestBodyOfDecoded->message->info->payType = "EP004";
//        $requestBodyOfDecoded->message->info->transPaySeq = "6TECEP01141537326509";
//        $requestBodyOfDecoded->message->info->transDate = "20231008";
//        $requestBodyOfDecoded->message->info->transTime = "160421";
//        $requestBodyOfDecoded->message->info->clearType = "REALTIME";
//        $requestBodyOfDecoded->message->info->numOpenMerchantOrder = "10123484984654554646469";
//        $requestBodyOfDecoded->message->info->traceNo = "JF20231008160421";


        $propertyId = 0;
        $result = 0;
        if($requestBodyOfDecoded){
            //解析前的requestBody
            $requestBody = $requestMap['requestBody'];
            //获取缴费中心传送过来的签名
            $signatureString = $requestMap['signatureString'];

            //查询所属物业id
            $propertyId = (new HouseAbchinaSet())->where('merchant_id',$requestBodyOfDecoded->message->info->merchantId)->value('property_id');
        }
        if($propertyId){
            $abChina = new \app\pay\model\service\channel\AbChinaService($propertyId);
            $result = (string)$abChina->read_cer_and_verify_sign($requestBody,$signatureString);
        }
        fdump_api(date('Y-m-d H:i:s',time())."获取到的报文验签的结果为：".$result,'abchina/getOrderList',1);
        if ('1' !== $result) {
            $responseStr = '缴费中心发送的销账请求报文验签失败！';
        }else {
            /** 销账报文重发次数，通过此字段识别销账报文是否为重发的，0表示首次、1表示重发一次，2表示重发2次，最多重发3次*/
            $resendTimes = $requestBodyOfDecoded->message->info->resendTimes ?? 0;
            /** 第三方支付平台商户订单号 */
            $numOpenMerchantOrder = $requestBodyOfDecoded->message->info->numOpenMerchantOrder ?? "";

            $head = [
                'transFlag' => '02',
                'timeStamp' => $this->getTimeStamp('YmdHisu'),//时间戳,格式 yyyyMMddHHmmssSSS，
                'channel' => $requestBodyOfDecoded->message->head->channel ?? "",//渠道编码
                'transCode' => $requestBodyOfDecoded->message->head->transCode ?? "",//交易码
                'transSeqNum' => $requestBodyOfDecoded->message->head->transSeqNum ?? "",//缴费中心交易序列号
            ];
            $info = [
                'traceNo'               => $requestBodyOfDecoded->message->info->traceNo ?? "",
                'epayCode'              => $requestBodyOfDecoded->message->info->epayCode ?? "",
                'numOpenMerchantOrder'  => $numOpenMerchantOrder,
            ];
            /**
             * demo所给，未解释原因，暂时先注释
             * 返回码为0000时不读取本字段；
             * 返回码非0000时，必须返回本标志位信息。返回true标志自动实时退款，返回false标志不做退款
             *
             * 根据账单请求次数确定返回参数，分为四种情况。
             */
//            switch ($resendTimes % 4) {
//                case 0;
//                    $head['returnCode'] = "0000";
//                    $head['returnMessage'] = "账单缴费成功";
//                    $info['refundFlag'] = "false";
//                    break;
//                case 1;
//                    $head['returnCode'] = "1111";
//                    $head['returnMessage'] = "账单缴费失败哦";
//                    $info['refundFlag'] = "true";
//                    break;
//                case 2;
//                    $head['returnCode'] = "1111";
//                    $head['returnMessage'] = "账单缴费失败哦";
//                    $info['refundFlag'] = "false";
//                    break;
//                case 3;
//                    $head['returnCode'] = "JH01";
//                    $head['returnMessage'] = "账单缴费失败哦";
//                    $info['refundFlag'] = "false";
//
//            }
            
            //业务操作
            $orderInfo = [
                'trace_no'=>$requestBodyOfDecoded->message->info->traceNo??'',
                'pay_bill_no'=>$requestBodyOfDecoded->message->info->payBillNo??'',
                'pay_type'=>$requestBodyOfDecoded->message->info->payType??'',
                'pay_bill_amt'=>$requestBodyOfDecoded->message->info->payBillAmt??0,
                'transDate'=>$requestBodyOfDecoded->message->info->transDate??'',
                'transTime'=>$requestBodyOfDecoded->message->info->transTime??'',
                'numOpenMerchantOrder'=>$requestBodyOfDecoded->message->info->numOpenMerchantOrder??'',
                'propertyId'=>$propertyId,
                'pay_json'=>json_encode($requestBodyOfDecoded)
            ];
            $check =  $this->checkParam($orderInfo);
            if($check['error']){
                $handle = $check;
            }else{
                $handle = $this->notify($orderInfo);
            }
            if($handle['error']){
                $head['returnCode'] = $handle['error'];
                $head['returnMessage'] = $handle['msg'];
                $info['refundFlag'] = $handle['refundFlag'];
            }

            $responseBody['format'] = $requestBodyOfDecoded->format;
            $responseBody['message'] = [
                'info'=>array_merge((array)$requestBodyOfDecoded->message->info,(array)$info),
                'head'=>array_merge((array)$requestBodyOfDecoded->message->head,(array)$head),
            ];
            fdump_api(['返回信息数组：'=>$responseBody],'abchina/afterPay',1);
            $responseJson = json_encode($responseBody, JSON_UNESCAPED_UNICODE);
            // 加签名
            $signatrue = $abChina->sign_with_sha1_with_rsa($responseJson);
            $responseStr = $signatrue . "||" . (base64_encode($responseJson));
            fdump_api(date('Y-m-d H:i:s',time())."向后台发送的报文加密前为：" . $responseJson,'abchina/afterPay',1);
        }
        fdump_api(date('Y-m-d H:i:s',time())."向后台发送的报文加密后为：" . $responseStr,'abchina/afterPay',1);
//      将加签名之后的报文发送给农行
        $this->data = $responseStr;
        $this->header['Content-Type'] = 'text/plain; charset=utf-8';
        return $this->send();
    }

    /**
     * 检查参数
     */
    public function checkParam($orderInfo)
    {
        $return = [
            'error'=>'',
            'msg'=>'',
            'refundFlag'=>"false"
        ];
        if(!$orderInfo['trace_no']){
            $return['error'] = 'JH01';
            $return['msg'] = '缴费中心流水号不能为空';
            $return['refundFlag'] = 'true';
            return $return;
        }
        if(!$orderInfo['pay_bill_no']){
            $return['error'] = 'JH01';
            $return['msg'] = '子账单编号不能为空';
            $return['refundFlag'] = 'true';
            return $return;
        }
        if(!$orderInfo['transDate']){
            $return['error'] = 'JH01';
            $return['msg'] = '支付系统交易日期不能为空';
            $return['refundFlag'] = 'true';
            return $return;
        }
        if(!$orderInfo['transTime']){
            $return['error'] = 'JH01';
            $return['msg'] = '支付系统交易时间不能为空';
            $return['refundFlag'] = 'true';
            return $return;
        }
        return $return;
    }

    /**
     * 业务回调处理
     */
    public function notify($orderInfo)
    {
        $return = [
            'error'=>'',
            'msg'=>'',
            'refundFlag'=>"false"
        ];
        $autoQuery = $orderInfo['autoQuery']??0;//1:主动查询，0：支付成功后通知
        $payBillNo = $orderInfo['pay_bill_no'];
        $payJson = $orderInfo['pay_json'];
        $payBillAmt = $orderInfo['pay_bill_amt'];
        $transPaySeq = $orderInfo['numOpenMerchantOrder'];
        $propertyId = $orderInfo['propertyId'];
//        $pay_type = $orderInfo['pay_type'];
        $pay_type = 'abchina';
        $payBillNoAry = explode('|',$payBillNo);
        $payTime = $this->getPayTime($orderInfo['transDate'],$orderInfo['transTime']);
        $pay_db = new \app\pay\model\db\PayOrderInfo();
        $service_house_new_cashier = new HouseNewCashierService();
        $service_plat_order = new PlatOrderService();
        foreach ($payBillNoAry as $v){
            $isRefund = 0;
            $businessType = 'village_new_pay';
            $vBillNoAry = explode('_',$v);
            $orderType = $vBillNoAry[0];
            $orderId = $vBillNoAry[1];
            $pay_db = new \app\pay\model\db\PayOrderInfo();
            $houseNewPayOrder = (new HouseNewPayOrder())->where(['order_id'=>$orderId,'order_type'=>$orderType])->field('is_paid,summary_id,village_id,room_id,uid,pigcms_id,order_name,property_id,position_id,late_payment_money,modify_money,name,phone')->find();
            $houseNewPayOrder = $houseNewPayOrder ? $houseNewPayOrder->toArray() : [];
            if(!$houseNewPayOrder){
                $return['error'] = 'JH01';
                $return['msg'] = '订单信息查询失败';
                $return['refundFlag'] = 'true';
                $isRefund = 1;
                return $return;
            }
            if($houseNewPayOrder['is_paid']==1){//已支付
                $return['error'] = 'JH01';
                $return['msg'] = '订单已支付1';
                $return['refundFlag'] = 'false';
                $isRefund = 0;
                return $return;
            }
            if($houseNewPayOrder['summary_id']){
                $isPay = (new HouseNewPayOrderSummary())->where('summary_id',$houseNewPayOrder['summary_id'])->value('is_paid');
                if($isPay==1){//已支付
                    $return['error'] = 'JH01';
                    $return['msg'] = '订单已支付2';
                    $return['refundFlag'] = 'false';
                    $isRefund = 0;
                    return $return;
                }
                $platOrder = (new PlatOrder())->where(['business_type'=>$businessType,'business_id'=>$houseNewPayOrder['summary_id']])->field('order_id,total_money,paid')->find();
                $platOrder = $platOrder ? $platOrder->toArray() : [];
                if($platOrder && $platOrder['paid']==1){//已支付
                    $return['error'] = 'JH01';
                    $return['msg'] = '订单已支付2';
                    $return['refundFlag'] = 'false';
                    $isRefund = 0;
                    return $return;
                }
            }
            Db::startTrans();
            try {
                if($houseNewPayOrder['summary_id']){
                    //作废之前的总订单记录，防止重复支付
                    (new HouseNewPayOrderSummary())->where('summary_id',$houseNewPayOrder['summary_id'])->update(['is_paid'=>3]);
                }
                //创建新版收费-订单总表记录
                $summaryData = [
                    'village_id'=>$houseNewPayOrder['village_id'],
                    'property_id'=>$houseNewPayOrder['property_id'],
                    'room_id'=>$houseNewPayOrder['room_id'],
                    'position_id'=>$houseNewPayOrder['position_id'],
                    'uid'=>$houseNewPayOrder['uid'],
                    'pigcms_id'=>$houseNewPayOrder['pigcms_id'],
                    'pay_uid'=>$houseNewPayOrder['uid'],
                    'pay_bind_id'=>$houseNewPayOrder['pigcms_id'],
                    'is_paid'=>2,
                    'pay_type'=>4,
                    'is_online'=>1
                ];
                $summary_id = $service_house_new_cashier->addOrderSummary($summaryData);
                if(!$summary_id){
                    throw_exception('订单创建失败');
                    return $return;
                }
//                fdump_api($summary_id,$houseNewPayOrder['order_name'],$houseNewPayOrder['uid'],round_number(modify_money($houseNewPayOrder['late_payment_money'],$houseNewPayOrder['modify_money']),2))
                $platOrder = [
                    'business_type' => 'village_new_pay',
                    'business_id' => $summary_id,
                    'order_name' => $houseNewPayOrder['order_name'],
                    'uid' => $houseNewPayOrder['uid'],
                    'total_money' => round_number(bcadd($houseNewPayOrder['late_payment_money'],$houseNewPayOrder['modify_money']),2),
                    'wx_cheap' => 0,
                    'add_time'=>time(),
                    'is_mobile_pay'=>1
                ];
                $plat_id = $service_plat_order->addPlatOrder($platOrder);

                (new HouseNewPayOrder())->where(['order_id'=>$orderId,'order_type'=>$orderType])->update([
                    'summary_id'=>$summary_id,
                    'pay_bind_id'=>$houseNewPayOrder['pigcms_id'],
                    'pay_bind_name'=>$houseNewPayOrder['name'],
                    'pay_bind_phone'=>$houseNewPayOrder['phone']
                ]);
                //1、生成支付订单号
                $order_no = (new PayService())->createOrderNo($pay_type,$plat_id);
                $data = [
                    'business' 				        => $businessType,
                    'business_order_id' 	        => $plat_id,
                    'orderid' 				        => $order_no,
                    'addtime' 				        => date('Y-m-d H:i:s'),
                    'money' 				        => $platOrder['total_money'],
                    'pay_type' 				        => $pay_type,
                    'env'					        => 'h5',
                    'channel' 				        => $pay_type,
                    'pay_config' 			        => $payJson,
                    'is_own'				        => 1,// todo 暂时算自有支付
                    'paid' 					        => 1,
                    'paid_money' 			        => $payBillAmt*100,//目前只会有一笔订单，所以直接拿支付金额
                    'paid_time' 			        => $payTime,
                    'paid_extra' 			=> $transPaySeq,
                    'current_score_use'		        => 0,
                    'current_score_deducte'	        => 0,
                    'current_system_balance'        => 0,
                    'current_village_balance'       => 0,
                    'current_merchant_balance'	    => 0,
                    'current_merchant_give_balance'	=> 0,
                    'current_qiye_balance'	=> 0,
                    'current_employee_balance'	=> 0,
                    'current_employee_score_deducte'	=> 0,
                    'employee_card_user_id'	=> 0,
                    'rate' 					=> 1,
                    'chinaums_merchant_already_get' => 0,
                    'chinaums_platform_already_get' => 0,
                    'own_from' => 'property_wxapp',
                    'own_from_id' => $propertyId,
                ];
                $insert = $pay_db->add($data);
                if(!$insert){
                    throw_exception('订单支付失败');
                    return $return;
                }
                
                $pay_service = new PayService;
                $notice = $pay_service->notice($order_no);
                //调用业务方service  after_pay
                if($notice['after_pay']){
                    $result = $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
                    if(isset($result['error_code']) && $result['error_code']){
                        $msg = $result['msg']??'支付失败';
                        throw_exception($msg);
                    }
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $return['error'] = 'JH01';
                $return['msg'] = $e->getMessage();
                $return['refundFlag'] = 'true';
                $isRefund = 1;
                return $return;
            }
            if(!$autoQuery){
                //支付成功，将支付的订单信息存入表中
                $updateData = [
                    'pay_order_type'=>$orderType,
                    'pay_order_id'=>$orderId,
                    'pay_time'=>$payTime,
                    'is_refund'=>$isRefund,
                    'notify_content'=>$payJson,
                    'update_time'=>time()
                ];
                if($isRefund){
                    $updateData['refund_time'] = time();
                }
                (new HouseAbchinaOrderList())->where('trace_no',$orderInfo['trace_no'])->update($updateData);
            }
        }
        return $return;
    }
    
    /**
     * 获取毫秒级日期
     */

    public static function getTimeStamp($format = 'u',$utimestamp=null){
        if (is_null($utimestamp)){
            $utimestamp = microtime(true);
        }

        $timestamp = floor($utimestamp);
        $milliseconds = round((1+$utimestamp - $timestamp)* 1000);

        return date(preg_replace('`(?<!\\\\)u`',substr(strval($milliseconds),1,3),$format),$timestamp);
    }

    /**
     * 发送数据到客户端
     * @access public
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function send()
    {
        $data = $this->data;
        if (!headers_sent() && !empty($this->header)) {
            // 发送状态码
            http_response_code($this->code);
            // 发送头部信息
            foreach ($this->header as $name => $val) {
                if (is_null($val)) {
                    header($name);
                } else {
                    header($name . ':' . $val);
                }
            }
        }
        return $data;
    }

    /**
     * 查询缴费单笔流水
     */
    public function getAbChinaOrderInfo()
    {
        if(customization('abchina') != 1){
            return true;
        }
        $time = time()-30*60;
        $orderList = (new HouseAbchinaOrderList())->where([
            ['is_select','=',0],
            ['pay_time','=',0],//只查询未支付订单
            ['select_time','<',$time],
        ])->order('pigcms_id asc')->limit(10)->select();
        $orderList = $orderList ? $orderList->toArray() : [];
        if(!$orderList){
            return true;
        }
        foreach ($orderList as $v){
            $queryContent = $v['query_content'];
            $queryContent = json_decode($queryContent,true);
            $merchantId = $queryContent['message']['info']['merchantId']??0;
            $traceNo = $v['trace_no']??0;
            $timestamp = $this->getTimeStamp('YmdHisu');
            if(!$merchantId || !$traceNo){
                //查询参数缺失，直接修改为已查询
                (new HouseAbchinaOrderList())->where('pigcms_id',$v['pigcms_id'])->update([
                    'is_select'=>2,
                    'auto_query_content'=>json_encode(['error'=>1,'msg'=>'查询参数缺失']),
                    'update_time'=>time()
                ]);
                continue;
            }
            //查询缴费单笔流水
            $abChina = new \app\pay\model\service\channel\AbChinaService(0,$merchantId);
            $result = $abChina->confirmTrace([
                'merchantId'=>$merchantId,
                'traceNo'=>$traceNo,
                'timeStamp'=>$timestamp
            ]);
            if(isset($result['error']) && $result['error']){
                //验签失败
                (new HouseAbchinaOrderList())->where('pigcms_id',$v['pigcms_id'])->update([
                    'is_select'=>2,
                    'auto_query_content'=>json_encode(['error'=>1,'msg'=>$result['msg']]),
                    'update_time'=>time()
                ]);
                continue;
            }
            $updateData = [
                'is_select'=>1,
                'auto_query_content'=>json_encode($result),
                'update_time'=>time()
            ];
            //查询订单信息，本地未支付，如果线上已支付，则进行回调操作
            if(isset($result['message']['info']['payStatus'])){
                //payStatus,0-支付中,1-缴费成功,2-支付失败,3-支付状态未知（请稍后查看）,4-已退款,5-支付成功缴费失败,6-支付成功缴费状态不确定，99-已关闭（放弃支付）
                $payStatus = $result['message']['info']['payStatus'];
                if(in_array($payStatus,[0,3,6])){//更新查时间，等待下次查询
                    $updateData = [
                        'select_time' => time()+30*60
                    ];
                }elseif($payStatus==1){
                    //支付成功，需要发起回调
                    $transDate = explode(' ',$result['message']['info']['timeEpay'])[0];
                    $transTime = explode(' ',$result['message']['info']['timeEpay'])[1];
                    $notify = $this->notify([
                        'trace_no'=>$traceNo,
                        'pay_bill_no'=>$result['message']['info']['billNo'],
                        'pay_json'=>json_encode($result),
                        'pay_bill_amt'=>$result['message']['info']['amtEpay'],
                        'numOpenMerchantOrder'=>$result['message']['info']['numOpenMerchantOrder'],
                        'propertyId'=>$abChina->getPropertyId(),
                        'transDate'=>$transDate,
                        'transTime'=>$transTime,
                        'autoQuery'=>1
                    ]);
                    if($notify['error'] && $notify['refundFlag']=='true'){
                        //回调失败，申请退款，暂时不做处理
//                        $returnResult = $abChina->refundTrace([
//                            'merchantId'=>$merchantId,
//                            'traceNo'=>$traceNo,
//                            'timeStamp'=>$timestamp,
//                            'amtRefund'=>$result['message']['info']['amtEpay'],
//                            'branchCode'=>$queryContent['message']['head']['branchCode']??'',
//                        ]);
//                        if(isset($result['error']) && $result['error']){
//                            //验签失败
//                            continue;
//                        }
                    }elseif($notify['error']){
                        //只报错不退款
                    }else{
                        //支付成功
                        $payTime = $this->getPayTime($transDate,$transTime);
                        $billNoAry = explode('_',$result['message']['info']['billNo']);
                        $updateData['pay_order_type'] = $billNoAry[0];
                        $updateData['pay_order_id'] = $billNoAry[1];
                        $updateData['pay_time'] = $payTime;
                    }
                }
            }
            (new HouseAbchinaOrderList())->where('pigcms_id',$v['pigcms_id'])->update($updateData);
        }
        return true;
    }
    
    /**
     * 根据日期获取时间
     */
    public function getPayTime($date,$time)
    {
        $y = substr($date,0,4);
        $m = substr($date,-4,2);
        $d = substr($date,-2,2);
        $h = substr($time,0,2);
        $i = substr($time,-4,2);
        $s = substr($time,-2,2);
        $strToTime = strtotime($y.'-'.$m.'-'.$d.' '.$h.':'.$i.':'.$s);
        return $strToTime;
    }
}