<?php


namespace app\community\model\service;


use app\common\model\db\PaidOrderRecord;
use app\community\model\db\ConstructionOrderList;
use app\community\model\db\HouseVillageCheckauthApply;
use app\pay\model\db\PayChannel;
use app\pay\model\db\PayOrderInfo;
use app\pay\model\service\PayService;
use think\facade\Db;

class ConstructionPayService
{
    public $config = [];
    /**
     * md5校验
     */
    public function check($param)
    {
        $config = $this->getChannelParam();
        $auth = (new \app\pay\model\service\channel\ConstructionPayService('',$config))->getAuth($param['thirdSysID'],$param['txCode'],$param['data'],$param['transID']);
        if($auth && $auth == $param['auth']){
            return true;
        }
        return false;
    }
    /**
     * 获取订单编号
     */
    public function getOutTradeNo($param)
    {
        $return = [
            'paymentStatus' => 1,
            'transID' => '',
            'out_trade_no' => '',
        ];
        $config = $this->getChannelParam();
        //dd((new \app\pay\model\service\channel\ConstructionPayService('',$config))->desDecrypt('54886311C188D5EC8EC5AF484D418D00'));
//        dd($this->encrypt([
//            'transID'=>'016212023102010491900001',
//            'txCode'=>'IPSPT20009',
//            'projectId'=>'000028161',
//            'paymentFlowNo'=>'111112',
//            'paymentStatus'=>'1',
//            'billSet'=>[
//                [
//                    'billId'=>'111112',
//                    'billId_thirdSys'=>'202310201049199081325335',
//                    'amount'=>'10',
//                    'payStatus'=>'1',
//                    'detailSet'=>[],
//                ]
//            ]
//        ]));
//        if($param['secID'] == '000021'){
            $data = (new \app\pay\model\service\channel\ConstructionPayService('',$config))->sm4Decrypt($param['data']);//解密获取信息
//        }else{
//            $data = (new \app\pay\model\service\channel\ConstructionPayService('',$config))->decrypt($param['data']);//解密获取信息
//        }
        $dataAry = json_decode($data,true);
        if(!$dataAry){
            return $return;
        }
        $return=$dataAry;
        //$return['transID'] = $dataAry['transID'];
        $return['paymentStatus'] = isset($dataAry['paymentStatus']) ? $dataAry['paymentStatus']: 2;
        if(!$dataAry['paymentStatus'] == 2){//缴费失败
            return $return;
        }
        $return['out_trade_no'] = $dataAry['billSet'][0]['billId_thirdSys'] ?? '';
        $paidExtra = $dataAry['paymentFlowNo'];
        if(!$return['transID'] || !$return['out_trade_no'] || !$paidExtra){
            return $return;
        }
        $return['paymentFlowNo'] = $paidExtra;
        
        return $return;
    }
    
    /**
     * 加密参数
     */
    public function encrypt($data)
    {
        $config = $this->getChannelParam();
        $str = (new \app\pay\model\service\channel\ConstructionPayService('',$config))->sm4Encrypt($data);
        return $str;
    }
    
    /**
     * 修改建行订单信息
     */
    public function changeOrder($param)
    {
        $return = [
            'error' => 0,
            'msg' => '',
            'is_paid'=>0
        ];
        
        //查询明细流水号
        $config = $this->getChannelParam();
        $orderDetail = (new \app\pay\model\service\channel\ConstructionPayService('',$config))->queryOrder(['order_no'=>$param['out_trade_no']]);

//        $orderDetail = [
//            'error'=>0,
//            'pay_status'=>1,
//            'detail_paid_extra'=>'111111',
//        ];
        
        if($orderDetail['error']){
            $return['error'] = 1;
            $return['msg'] = $orderDetail['msg'];
            return $return;
        }
        if(!$orderDetail['error'] && isset($orderDetail['pay_status']) && $orderDetail['pay_status'] == 2){
            $return['error'] = 1;
            $return['msg'] = '交易失败';
            return $return;
        }
        if(!$orderDetail['error'] && !$orderDetail['detail_paid_extra']){
            $return['error'] = 1;
            $return['msg'] = '查询明细信息失败';
            return $return;
        }
        $detailPaidExtra = '';//明细编号
        if(isset($orderDetail['detail_paid_extra']) && !empty($orderDetail['detail_paid_extra'])){
            $detailPaidExtra=$orderDetail['detail_paid_extra'];
            $return['is_paid']=1;
        }
        $payment_flow_no=isset($param['paymentFlowNo']) ? $param['paymentFlowNo']:'';
        if(isset($orderDetail['payment_flow_no']) && $orderDetail['payment_flow_no']){
            $payment_flow_no=$orderDetail['payment_flow_no'];
        }
        $update = (new ConstructionOrderList())->updateThis(['orderid'=>$param['out_trade_no']],[
            'paid_extra' => $payment_flow_no,
            'detail_paid_extra' => $detailPaidExtra,
            'is_notify' => 1,
            'update_time' => time(),
        ]);
        if (isset($param['again_to_pay']) && $param['again_to_pay']==1){
            $amount =$orderDetail['payMoney'];
            $noticeArr=array(
                'paid_money' => $amount * 100,
                'paid_time' => time(),
                'transaction_no' => $payment_flow_no,
                'noticeType'=>'constructionPay'
            );
            $pay_service = new PayService;
            $notice = $pay_service->notice($param['out_trade_no'],$noticeArr);
            //调用业务方service  after_pay
            if ($notice['after_pay']) {
                $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
            }
        }
        if(!$update){
            $return['error'] = 1;
            $return['msg'] = '修改建行订单信息失败';
            return $return;
        }
        return $return;
    }
    
    /**
     * 获取支付通道配置信息
     */
    public function getChannelParam()
    {
        if(!$this->config){
            $channelInfo = (new PayChannel())->getOne(['pay_type'=>'constructionPay','channel'=>'constructionPay']);
            $channel_id = $channelInfo['id'];
            if(!$channel_id){
                return [];
            }
            $pay = new PayService;
            $config = $pay->getChannelParam($channel_id);
            $return = [];
            foreach ($config as $v){
                $return[$v['name']] = $v['value'];
            }

            $this->config = $return;
        }
        return $this->config;
    }
    
    /**
     * 获取auth
     */
    public function getAuth($thirdSysID,$txCode,$dataStr,$transID)
    {
        $config = $this->getChannelParam();
        return (new \app\pay\model\service\channel\ConstructionPayService('',$config))->getAuth($thirdSysID,$txCode,$dataStr,$transID);
    }
    
    /**
     * 处理已支付但是1小时还没有回调的订单(每次处理5条)
     */
    public function handleOrderNotice()
    {
        $where = [
            ['is_notify','=',0],
            ['is_refund','=',0],
            ['create_time','<',time()-3600]
        ];
        $field = 'orderid';
        $group = 'orderid';
        $limit = 5;
        $list = (new ConstructionOrderList())->getList($where,$field,$group,$limit);
        $list = $list ? $list->toArray() : [];
        if(count($list)>0){
            foreach ($list as $v){
                $config = $this->getChannelParam();
                $query = (new \app\pay\model\service\channel\ConstructionPayService('',$config))->queryOrder(['order_no'=>$v['orderid']]);
                if(isset($query['pay_status']) && $query['pay_status'] == 1){//交易成功
                    try {
                        $paid_extra = $query['paid_extra'] ?? '';
                        $detailPaidExtra = $query['detail_paid_extra'] ?? '';
                        //更新交易流水号
                        if($paid_extra && $detailPaidExtra){
                            //修改建行订单记录表
                            $update = (new ConstructionOrderList())->updateThis(['orderid'=>$v['orderid']],[
                                'paid_extra' => $paid_extra,
                                'detail_paid_extra' => $detailPaidExtra,
                                'is_notify' => 1,
                                'update_time' => time(),
                            ]);
                            if(!$update){
                                fdump_api(['error'=>'修改建行订单信息失败','sql'=>(new ConstructionOrderList())->getLastSql()],'handleOrderNotice/error',1);
                            }
                            $pay_service = new PayService;
                            $notice = $pay_service->notice($v['orderid']);
                            //调用业务方service  after_pay
                            if($notice['after_pay']){
                                $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
                            }
                        }
                    } catch (\Exception $e) {
                        fdump_api([$e->getMessage(),$e->getLine(),$e->getFile()],'handleOrderNotice/error',1);
                        (new ConstructionOrderList())->updateThis(['orderid'=>$v['orderid']],[
                            'is_notify' => 2,//交易处理失败
                            'update_time' => time(),
                        ]);
                    }
                }else{//处理失败
                    fdump_api($query,'handleOrderNotice/error',1);
                    (new ConstructionOrderList())->updateThis(['orderid'=>$v['orderid']],[
                        'is_notify' => 2,
                        'update_time' => time(),
                    ]);
                }
            }
        }
        return true;
    }
    
    /**
     * 处理已请求退款，但是30分钟后还是未知退款状态的订单(每次处理5条，最久T+1必出结果)
     */
    public function handleOrderRefund()
    {
        $where = [
            ['is_notify','=',1],
            ['is_refund','=',2],
            ['query_refund_time','<',time() - 1800]
        ];
        $field = 'pigcms_id,orderid,paid_extra,pay_back_flow_no,checkauth_apply_data,create_time';
        $list = (new ConstructionOrderList())->getList($where,$field);
        $pay_db = new PayOrderInfo;
        Db::startTrans();
        try{
            $config = $this->getChannelParam();
            foreach ($list as $v){
                $apply_extra_data = $v['checkauth_apply_data'] ? json_decode($v['checkauth_apply_data'], true) : [];
                if($v['paid_extra'] && $v['pay_back_flow_no'] && $apply_extra_data){
                    sleep(0.1);
                    $refundInfo = (new \app\pay\model\service\channel\ConstructionPayService('',$config))->getRefundStatus([
                        'paymentFlowNo'=>$v['paid_extra'],
                        'paybackFlowNo'=>$v['pay_back_flow_no']
                    ]);
                    if($refundInfo['error'] == 0 && $refundInfo['refund_status'] == '01' && isset($refundInfo['refundAmt']) && $refundInfo['refundAmt'] > 0){
//                    $return['refund_no'] = $dataAry['paybackFlowNo'];
                        (new ConstructionOrderList())->updateThis(['pigcms_id'=>$v['pigcms_id']],[
                            'is_refund' => 1,
                            'update_time' => time(),
                        ]);
                        $order = $pay_db->getByOrderNo($v['orderid']);

                        //处理支付数据
                        $save_data = [
                            'refund' => $order['refund_money'] + $refundInfo['refundAmt'] > $order['paid_money'] ? 2 : 1,
                            'refund_money' => $order['refund_money'] + $refundInfo['refundAmt'],
                            'refund_nums' => $order['refund_nums']+1,
                            'refund_last_time' => date('Y-m-d H:i:s')
                        ];
                        if($pay_db->updateById($order['id'], $save_data)){
                            $paidOrderRecordDb = new PaidOrderRecord();
                            $save_record_data=array('refund_status'=>$save_data['refund'],'refund_money'=>$save_data['refund_money']);
                            $save_record_data['last_refund_time']=time();
                            $paidOrderRecordDb->paidOrderRefundHandle(0,$order['id'],$v['orderid'],$save_record_data);
//                            return [
//                                'refund_no' => $v['pay_back_flow_no'],
//                                'refund_money' => $refundInfo['refundAmt']/100
//                            ];
                            //处理业务数据
                            $role_id = $apply_extra_data['role_id'];
                            $order_id = $apply_extra_data['order_id'];
                            $refund_type = $apply_extra_data['refund_type'];
                            $refund_money = $apply_extra_data['refund_money'];
                            $refund_reason = $apply_extra_data['refund_reason'];
                            $extra = [
                                'opt_type' => 'check_pass_refund',
                                'refund_no' => $v['pay_back_flow_no']
                            ];
                            (new HouseNewCashierService())->changeRefundInfo($role_id, $order_id, $refund_type, $refund_money, $refund_reason, $extra);
                        }
                        else{
                            throw new \think\Exception("更新退款信息失败！");
                        }
                    }elseif($refundInfo['error'] == 0 && $refundInfo['refund_status'] == '02'){
                        //退款失败
                        (new ConstructionOrderList())->updateThis(['pigcms_id'=>$v['pigcms_id']],[
                            'is_refund' => 0,
                            'update_time' => time(),
                        ]);
                    }else{//退款状态不明确,30分钟依然没有结果，等30分钟后再查,48小时后没结果的算作失败
                        if(time() - $v['create_time'] > 172800){
                            //退款失败
                            (new ConstructionOrderList())->updateThis(['pigcms_id'=>$v['pigcms_id']],[
                                'is_refund' => 0,
                                'update_time' => time(),
                            ]);
                        }else{
                            (new ConstructionOrderList())->updateThis(['pigcms_id'=>$v['pigcms_id']],[
                                'update_time' => time(),
                                'query_refund_time' => time() + 21600,//下次查询时间在6小时后
                            ]);
                        }
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            fdump_api([$e->getMessage()],'handleOrderRefund/error',1);
            return api_output_error(1003, $e->getMessage());
        }
        return true;
    }
}