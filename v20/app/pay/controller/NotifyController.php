<?php
	/**
	 * 支付异步通知地址
	 */
	declare (strict_types = 1);

	namespace app\pay\controller;
    
    use app\community\model\service\ConstructionPayService;
    use app\pay\model\db\MallbookPayInfo;
    use app\community\model\service\order\ScrcuService;
    use app\community\model\service\AbChinaService;
    use app\pay\model\db\PayOrderInfo;
    use app\pay\model\service\channel\FarmersbankpayService;
    use app\pay\model\service\PayService;
    use mallbook\Mallbook;
    use think\facade\Db;


	class NotifyController
	{
		/**
		 * 微信官方接口 异步通知
		 * @return [type] [description]
		 */
		public function wechat()
		{
			//1、获取order_no（本系统的支付单号）
			$input = file_get_contents("php://input");
			$array_data = json_decode(json_encode(simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            fdump_sql([$input,$array_data],'notify_wechat');
			if($array_data && $array_data['out_trade_no']){
				try {
					$pay_service = new PayService;
					$notice = $pay_service->notice($array_data['out_trade_no']);
				} catch (\Exception $e) {
                    fdump_sql(['array_data'=>$array_data,'msg'=>$e->getMessage()],'wechat_notify_error');
					exit('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA['.$e->getMessage().']]></return_msg></xml>');
				}
				//调用业务方service  after_pay
				if($notice['after_pay']){
					$pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
				}

				exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
			}
			else{
				exit('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[未获取到支付单号]]></return_msg></xml>');
			}
		}

        public function allinpay()
        {
            $params = request()->param();
            $input = file_get_contents("php://input");
            if (empty($params) && $input) {
                parse_str($input, $params);
                if (empty($params)) {
                    $params = json_decode($input, true) ?: [];
                }
            }
            fdump_sql([$params, $input], 'notify_allinpay_controller');
            $orderNo = $params['cusorderid'] ?? ($params['reqsn'] ?? ($params['out_trade_no'] ?? ''));
            if (!$orderNo) {
                exit('fail');
            }
            try {
                $pay_service = new PayService;
                $notice = $pay_service->notice($orderNo);
                if ($notice['after_pay']) {
                    $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
                }
            } catch (\Exception $e) {
                fdump_sql(['params' => $params, 'msg' => $e->getMessage()], 'allinpay_notify_error');
                exit('fail');
            }
            exit('success');
        }

		/**
		 * 支付宝官方接口 异步通知
		 * @return [type] [description]
		 */
		public function alipay()
		{
			//1、获取order_no（本系统的支付单号）
			$out_trade_no = request()->param('out_trade_no');
            fdump_sql(request()->param(),'notify_alipay');
			if($out_trade_no){
				try {
					$pay_service = new PayService;
					$notice = $pay_service->notice($out_trade_no);
				} catch (\Exception $e) {
					exit($e->getMessage());
				}
				//调用业务方service  after_pay
				if($notice['after_pay']){
					$pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
				}

				exit('success');
			}
		}

		/**
		 * 随行付 异步通知
		 * @return [type] [description]
		 */
		public function tianque()
		{
			//1、获取order_no（本系统的支付单号）
			$data = file_get_contents("php://input");
			$param = $data ? json_decode($data, true) : [];
			$out_trade_no = $param['ordNo'] ?? '';
			if($out_trade_no){
				try {
					$pay_service = new PayService;
					$notice = $pay_service->notice($out_trade_no);
				} catch (\Exception $e) {
					exit($e->getMessage());
				}
				//调用业务方service  after_pay
				if($notice['after_pay']){
					$pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
				}

				echo json_encode(['code' => "success", 'msg' => '成功']);
				exit;
			}
		}

		/**
		 * 银联 异步通知
		 * @return [type] [description]
		 */
		public function chinaums()
		{
			//1、获取order_no（本系统的支付单号）
			$out_trade_no = request()->param('merOrderId');
			$out_trade_no = str_replace(cfg('chinaums_msgsrcid'), '', $out_trade_no);
			fdump_sql(request()->param(), 'chinaums_notice');
			if($out_trade_no){
				try {
					$pay_service = new PayService;
					$notice = $pay_service->notice($out_trade_no);
				} catch (\Exception $e) {
					exit($e->getMessage());
				}
				//调用业务方service  after_pay
				if($notice['after_pay']){
					$pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
				}

				echo json_encode(['code' => "success", 'msg' => '成功']);
				exit;
			}
		}

		/**
		 * 平台低费率异步通知
		 * @return [type] [description]
		 */
		public function wftpay(){
			//1、获取order_no（本系统的支付单号）
			$notice_data = file_get_contents("php://input");
			$array_data = json_decode(json_encode(simplexml_load_string($notice_data, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
			$out_trade_no = $array_data['out_trade_no'] ?? '';
			if($out_trade_no){
				try {
					$pay_service = new PayService;
					$notice = $pay_service->notice($out_trade_no);
				} catch (\Exception $e) {
					exit($e->getMessage());
				}
				//调用业务方service  after_pay
				if($notice['after_pay']){
					$pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
				}

				echo json_encode(['code' => "success", 'msg' => '成功']);
				exit;
			}
		}

		/**
		 * 四川农银 异步通知
		 * @return [type] [description]
		 */
        public function scrcu($iscron=false)
        {
            //1、获取order_no（本系统的支付单号）
            $limit=30;
            $hour=date('H');
            if($iscron){
                $limit = 80;
                $where = [['add_time', '>=',  time()-1000], ['over', '=', 0], ['limit', '<', 150],['order_type', '=', 'pay_order_info']];
                $data = Db::name('Scrcu_record')->order("limit asc,id asc")->where($where)->limit($limit)->select();
            }else{
                $where = [['add_time', '>=',  time()-300], ['over', '=', 0], ['limit', '<', 110],['order_type', '=', 'pay_order_info']];
                $data = Db::name('Scrcu_record')->order("limit asc,id asc")->where($where)->limit($limit)->select();
            }
            if ($data && !is_array($data)) {
                $data = $data->toArray();
            } else {
                $data = [];
            }
            if (count($data)<$limit) {
                // 首先查询再时间范围内的一次没有查询过的订单不足20条， 查询没有查询过的订单补足20条
                $limit1 = $limit - count($data);
                $q_add_time= time()-90*24*3600; //90天
                $whereQArr=array(['limit', '<', 130], ['over', '=', 0],['order_type', '=', 'pay_order_info'],['add_time', '>=',$q_add_time]);
                
                $data1 = Db::name('Scrcu_record')->order("limit asc,id asc")->where($whereQArr)->limit($limit1)->select();
                if ($data1 && !is_array($data1)) {
                    $data1 = $data1->toArray();
                    fdump_api(['msg' => '1订单查询','iscron'=>$iscron, '$data' => $data, '$limit1' => $limit1, '$data1' => $data1],'$scrcu_log_0728',1);
                    $data = array_merge($data, $data1);
                } else {
                    $data1 = [];
                }
            }
            if(!empty($data)){
                $noticeArr = [];
                foreach ($data as $key => $value) {
                    if (isset($noticeArr[$value['id']]) && $noticeArr[$value['id']]) {
                        continue;
                    }
                    try {
                        $pay_service = new PayService;
                        request()->orderid = $value['order_number'];
                        $notice = $pay_service->notice($value['order_number']);
                        fdump_api(['iscron'=>$iscron,$notice,$value['order_number']],'scrcu_log_0728',1);
                    } catch (\Exception $e) {
                        $code=$e->getCode();
                        $e_msg=$e->getMessage();
                        fdump_api(['iscron'=>$iscron,'value'=>$value,'ee'=>$e->getMessage(),'code'=>$code],'scrcu_log_0728',1);
                        if($e_msg && strpos($e_msg,'SQLSTATE')!==false && strpos($e_msg,'Lock wait timeout exceeded')!==false){
                            //系统mysql报错，不查了
                            Db::name('Scrcu_record')->where(['id'=>$value['id']])->delete();
                            $noticeArr[$value['id']] = $value['order_number'];
                            continue;
                        }
                        Db::name('Scrcu_record')->where(['id'=>$value['id']])->data(['limit'=>$value['limit']+1])->save();
                        if($code==22204){
                            //交易关闭了 不要再查了
                            Db::name('Scrcu_record')->where(['id'=>$value['id']])->delete();
                        }
                        continue;
                    }
                    fdump_api(['iscron'=>$iscron,$notice,$value['order_number']],'scrcu_log_0728',1);
                    //调用业务方service  after_pay
                    if($notice['after_pay']){
                        fdump_api(['iscron'=>$iscron,$notice,$value['order_number']],'scrcu_log_0728',1);
                        $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
                    }
                    // 已经拿到了支付结果并记录 删除轮询记录
                    Db::name('Scrcu_record')->where(['id'=>$value['id']])->delete();
                    $noticeArr[$value['id']] = $value['order_number'];
                }
            }
            $limit=3;
            if($iscron){
                $limit=15;
            }
            $otherData = Db::name('Scrcu_record')->order("id asc")->where([['limit', '=', 0], ['over', '=', 0],['order_type', '=', 'pay_order_info']])->limit($limit)->select();
            if(!empty($otherData)){
                foreach ($otherData as $key => $value) {
                    try {
                        $pay_service = new PayService;
                        request()->orderid = $value['order_number'];
                        $notice = $pay_service->notice($value['order_number']);
                        fdump_api(['iscron'=>$iscron,$notice,$value['order_number']],'scrcu_log_0728',1);
                        Db::name('Scrcu_record')->where(['id'=>$value['id']])->data(['over'=>1,'over_time' => time()])->save();
                    } catch (\Exception $e) {
                        $code=$e->getCode();
                        fdump_api(['iscron'=>$iscron,'value'=>$value,'ee'=>$e->getMessage(),'code'=>$code],'scrcu_log_0728',1);
                        Db::name('Scrcu_record')->where(['id'=>$value['id']])->data(['limit'=>$value['limit']+1])->save();
                        if($code==22204){
                            //交易关闭了 不要再查了
                            Db::name('Scrcu_record')->where(['id'=>$value['id']])->delete();
                        }
                    }
                    fdump_api(['iscron'=>$iscron,$notice,$value['order_number']],'scrcu_log_0728',1);
                    //调用业务方service  after_pay
                    if($notice['after_pay']){
                        fdump_api(['iscron'=>$iscron,$notice,$value['order_number']],'scrcu_log_0728',1);
                        $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
                    }
                }
            }
            if ($iscron) {
                fdump_api(['走下队列'], 'scrcuService/hasScrcuLog');
                (new ScrcuService())->hasScrcu();
            } else {
                fdump_api(['不走下队列'], 'scrcuService/hasScrcuLog');
            }
            echo json_encode(['code' => "success", 'msg' => '成功']);

        }

		/**
		 * 环球汇通聚合支付 异步通知
		 * @return [type] [description]
		 */
		public function hqpay()
		{
			//1、获取order_no（本系统的支付单号）
			$data  = file_get_contents("php://input");
			$param = $data ? json_decode($data, true) : [];
			$out_trade_no = $param['outTradeNo'] ?? '';
			if ($out_trade_no) {
				try {
					$pay_service = new PayService;
					request()->orderid = $out_trade_no;
					$notice = $pay_service->notice($out_trade_no);
				} catch (\Exception $e) {
					exit($e->getMessage());
				}
				//调用业务方service  after_pay
				if ($notice['after_pay']) {
					$notice['extra']['fundBills'] = !empty($param['fundBills']) ? $param['fundBills'] : 'hqpay';
					$pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
				}
				echo json_encode(['code' => "success", 'msg' => '成功']);
				exit;
			}
		}

		/**
		 * 环球汇通聚合支付 退款异步通知
		 * @return [type] [description]
		 */
		public function hqpay_refund()
		{
			$data  = file_get_contents("php://input");
			$param = $data ? json_decode($data, true) : [];
			fdump($param, 'hqpay_refund', 1);
		}

/**
     * @return bool|string|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function farmersBankPay()
    {
        $params = request()->post();
        fdump_sql($params, 'farmersBankPayCallBack');
        //1、获取order_no（本系统的支付单号）
        if(empty($params['tpOrderId'])){
            return json([
                'code' => 400,
                'msg' => '平台订单号缺失！'
            ]);
        }
        if($params['status'] != '00'){
            return json([
                'code' => 400,
                'msg' => '未支付成功！'
            ]);
        }
        
        $order = PayOrderInfo::where('orderid', $params['tpOrderId'])->field('id')->find();
        if(!empty($order)){
            try {
                $pay_service = new PayService;
                $notice = $pay_service->notice($params['tpOrderId']);
            } catch (\Exception $e) {
                return json([
                    'code' => 400,
                    'msg' => $e->getMessage()
                ]);
            }
            //调用业务方service  after_pay
            if($notice['after_pay']){
                $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
            }
            return json([
                'code' => 200,
                'msg' => '支付成功！'
            ]);
        }
        
        $url = request()->domain().'/source/web_farmersbankpay_return.php';
        
        return curlPost($url,json_encode($params, JSON_UNESCAPED_UNICODE));
    }
    
		public function douyinNotify()
		{
			fdump_sql([file_get_contents("php://input"), $_REQUEST], 'douyinNotify');
			$data = file_get_contents("php://input");
			$data = json_decode($data, true);
			$notifyData = json_decode($data['msg'], true);
			$out_trade_no = $notifyData['cp_orderno'] ?? '';
			if ($out_trade_no) {
				try {
					request()->douyin_notify_data = $notifyData;
					$pay_service = new PayService;
					$notice = $pay_service->notice($out_trade_no);
				} catch (\Exception $e) {
					exit($e->getMessage());
				}
				//调用业务方service  after_pay
				if ($notice['after_pay']) {
					$pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);

					(new \app\common\model\service\order\SystemOrderService)->douyinOrderPush($out_trade_no, '已支付');
				}
				echo json_encode(['err_no' => 0, 'err_tips' => 'success']);
				exit;
			}
		}

		public function douyinRefundNotify(){
			fdump_sql([file_get_contents("php://input"), $_REQUEST], 'douyinRefundNotify');
			echo json_encode(['err_no' => 0, 'err_tips' => 'success']);
			exit;
		}

		public function icbc_apply_notify()
		{
			$input = file_get_contents("php://input");
			fdump_sql([$_REQUEST, $_POST, $_GET, $input], 'icbc_apply_notify');
			$return = [
				'return_code' => 0,
				'return_msg' => 'success',
				'megId' => date('YmdHis')
			];
			echo json_encode($return);
			exit;
		}

		public function icbc_refund_notify()
		{
			$input = file_get_contents("php://input");
			fdump_sql([$_REQUEST, $_POST, $_GET, $input], 'icbc_refund_notify');
			$return = [
				'return_code' => 0,
				'return_msg' => 'success',
				'megId' => date('YmdHis')
			];
			echo json_encode($return);
			exit;
		}


	/**
     * 日照银行支付 异步通知
     * @return [type] [description]
     */
    public function ebankpay()
    {
        //1、获取order_no（本系统的支付单号）
        $data = (new PayOrderInfo())->order("id desc")->where([['add_time', '>=',  time() - 600], ['pay_type', '=', 'ebankpay'], ['paid', '=', 0]])->limit(10)->select();
        if(!empty($data)){
            $data = $data->toArray();
            foreach ($data as $key => $value) {
                try {
                    $pay_service = new PayService;
                    request()->orderid = $value['orderid'];
                    $notice = $pay_service->notice($value['orderid']);
                } catch (\Exception $e) {
                    exit($e->getMessage());
                }

                //调用业务方service  after_pay
                if ($notice['after_pay']) {
                    $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
                }
            }
        }

        echo json_encode(['uop_code' => "UC000000", 'code' => "success", 'uop_msg' => '成功']);
    }
    
    /**
     * 建行v2.2支付回调
     */
    public function constructionPay()
    {
        $data = file_get_contents("php://input");
        $param = $data ? json_decode($data, true) : [];
        fdump_api(['data'=>$data, 'param'=>$param],'construction/notify_construction',1);
        fdump_sql([$data, $param], 'notify_construction');
        $return = [
            'transID' => '',
            'txCode' => 'IPSPT20009',
            'status' => '1',
        ];
        if ($param) {
            try {
                //md5检验
                $check = (new ConstructionPayService())->check($param);
                if (!$check) {
                    fdump_api(['param'=>$param,'md5检验失败'],'construction/notify_construction',1);
                    throw_exception('md5检验失败');
                }
                //解密信息，获取订单编号
                $orderInfo = (new ConstructionPayService())->getOutTradeNo($param);
                fdump_api(['param'=>$param,'orderInfo'=>$orderInfo],'construction/notify_construction',1);
                $return['transID'] = $orderInfo['transID'] ?? '';
                if ($orderInfo['paymentStatus'] == 1) {//缴费成功
                    if (!$orderInfo['out_trade_no']) {
                        throw_exception('获取缴费信息失败');
                    }

                    //修改建行订单记录表
                    $changeOrder = (new ConstructionPayService())->changeOrder($orderInfo);
                    if ($changeOrder['error']) {
                        fdump_api(['param'=>$param,'changeOrder'=>$changeOrder],'construction/notify_construction',1);
                        throw_exception($changeOrder['msg']);
                    }
                    $amount =isset($orderInfo['billSet']) &&  $orderInfo['billSet'][0]['amount'] ? $orderInfo['billSet'][0]['amount']: 0;
                    $paymentFlowNo=isset($orderInfo['paymentFlowNo']) &&  $orderInfo['paymentFlowNo'] ? $orderInfo['paymentFlowNo']: '';
                    $noticeArr=array(
                        'paid_money' => $amount * 100,
                        'paid_time' => time(),
                        'transaction_no' => $paymentFlowNo,
                        'noticeType'=>'constructionPay'
                    );
                    $pay_service = new PayService;
                    $notice = $pay_service->notice($orderInfo['out_trade_no'],$noticeArr);
                    //调用业务方service  after_pay
                    if ($notice['after_pay']) {
                        $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
                    }
                }
            } catch (\Exception $e) {
                dd($e->getMessage());
                $return['transID'] = $orderInfo['transID'] ?? '';
                $return['status'] = '2';
                fdump_api([$e->getMessage(), $e->getLine(), $e->getFile()], 'construction/notify_construction', 1);
            }
            $thirdSysID = $param['thirdSysID'] ?? '';
            fdump_api($return, 'construction/notify_construction', 1);
            $dataStr = (new ConstructionPayService())->encrypt($return);
            $transID = $orderInfo['transID'] ?? '';
            $auth = (new ConstructionPayService())->getAuth($thirdSysID, 'IPSPT20009', $dataStr, $transID);
             $returnjson=json_encode([
                'thirdSysID' => $thirdSysID,
                'txCode' => "IPSPT20009",
                'data' => $dataStr,
                'auth' => $auth,
                'secID' => "000021",
                'transID' => $transID
            ]);
            echo $returnjson;
            fdump_api(['param'=>$param,'returnjson'=>$returnjson],'construction/notify_construction',1);
            exit;
        }
    }
    
    public function mallbookPayCallback()
    {
        $data = input();
        fdump_api([
            'callback_data' => $data
        ], "mallBook/mallbookPayCallback", true);
        fdump_sql([$data, $_REQUEST], 'mallbookPayCallback');
        
        $result = json_decode($data['result'], true);
        if(empty($result['merOrderId'])){
            return json([
                'msg' 	 => 'merOrderId缺失',
            ])->code(400);
        }
        request()->amount = $result['amount'];
        request()->merOrderId = $result['merOrderId'];
        $payInfo = PayOrderInfo::where('orderid', $result['merOrderId'])
            ->where(['channel' => 'mallbook'])
            ->find();
        if(empty($payInfo)){
            return json([
                'msg' 	 => 'merOrderId不存在',
            ])->code(400);
        }
//        $payConfig = json_decode($payInfo['pay_config'], true);
        $veritySign = $data['sign'] ?? '';
        if (!Mallbook::verify($data, $veritySign)) {
            throw_exception('返回值验签失败');
        }
        fdump_api([
            'pay_info' => $payInfo,
            'msg' => '验签成功'
        ], "mallBook/mallbookPayCallback", true);
        try {
            $payInfo->mallbook_order_no = $result['merOrderId'];
            $payInfo->save();
            $pay_service = new PayService;
            $notice = $pay_service->notice($payInfo['orderid']);
        } catch (\Exception $e) {
            fdump_api([
                'callback_data' => $data,
                'msg'=>$e->getMessage()
            ], "mallBook/mallbookPayCallbackError", true);
            return json([
                'msg' 	 => $e->getMessage(),
            ])->code(400);
        }
        //调用业务方service  after_pay
        if($notice['after_pay']){
            $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
        }
        return json([
            'msg' 	 => '请求成功',
        ]);
    }

    public function mallbookRefundCallback()
    {
        $data = input();
        fdump_sql([$data, $_REQUEST], 'mallbookRefundCallback');
        fdump_api([
            'callback_data' => $data
        ], "mallBook/mallbookRefundCallback", true);
        
        return json([
            'status' => true
        ]);
    }

    public function asyncSeparateMoneyCallback()
    {
        $data = input();
        fdump_sql([$data, $_REQUEST], 'asyncSeparateMoneyCallback');
        fdump_api([
            'callback_data' => $data
        ], "mallBook/asyncSeparateMoneyCallback", true);

        return json([
            'status' => true
        ]);
    }

    /**
     * 农行缴费通知
     */
    public function abchinapay()
    {
        $AbChinaService = new AbChinaService();
        $requestContent = \request()->getInput();
        $info = $AbChinaService->afterPay($requestContent);
        exit($info);
    }

    /**
     * 温州银行异步通知
     *
     * @return void
     * @author: zt
     * @date: 2023/07/04
     */
    public function wenzhouBankNotify()
    {
        $prefix = request()->param('prefix','');
        $out_trade_no = request()->param('merOrderId');
        $out_trade_no = str_replace($prefix, '', $out_trade_no);
        fdump_sql(request()->param(), 'wenzhouBankNotify');
        if ($out_trade_no) {
            try {
                $pay_service = new PayService;
                $notice = $pay_service->notice($out_trade_no);
            } catch (\Exception $e) {
                exit($e->getMessage());
            }
            //调用业务方service  after_pay
            if ($notice['after_pay']) {
                $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
            }

            echo json_encode(['code' => "success", 'msg' => '成功']);
            exit;
        }
    }

    //拉卡拉支付异步通知
    public function lakalaNotify()
    {
        fdump_api($_POST, 'lakalaNotify',true);
        // $_POST =   array(
        //     'event_type' => 'order.consume.pay',
        //     'sign' => 'MCwCFGNUVchoooJM4giVa+nml34MuV8+AhQmTUc+0uAoNTRMIAMa33GU3d2atA==',
        //     'event' => '{"order_no":"7309052934814547968","out_order_no":"202503221126569486321723","out_request_no":"202503221126569486321723","seller_member_no":"6806043394058620928","amount":1,"pay_amount":1,"pay_method":{"GATEWAY":{"amount":1,"merchant_no":"7194643510113705984","member_no":"6806043394058620928","product_name":"订单编号：202503221124485521960333","terminal_ip":"127.0.0.1"}},"order_name":"订单编号：202503221124485521960333","order_status":1004,"pay_status":"1004","pay_seq_no":"7309052934906822656","channel_seq_no":"66200819202350","channel_trade_no":"2025032266200819202350","pay_channel_trade_no":"2025032222001425901458711962","pay_time":"2025-03-22 11:28:06","status":"OK","third_party_payment":"ALIPAY","card_type":"04","refernumber":"200819202350","pay_request_no":"7309052935431110656","is_ecomm_trans":"0"}',
        //     'app_id' => '6633965341557002240',
        //     'version' => '3.0',
        //     'timestamp' => '1742614088',
        // );
        $eventType = $_POST['event_type'] ?? '';
        if($eventType !== 'order.consume.pay'){
            exit('SUCCESS');
        }
        $eventData = $_POST['event'] ? json_decode($_POST['event'], true) : [];
        $outTradeNo = $eventData['out_order_no'] ?? '';
        if (empty($outTradeNo)) {
            exit('FAIL');
        } else {
            try {
                $payService = new PayService;
                $notice = $payService->notice($outTradeNo);
            } catch (\Exception $e) {
                exit('FAIL');
            }
            //调用业务方service  after_pay
            if ($notice['after_pay']) {
                $payService->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
            }
        }
    }
}

