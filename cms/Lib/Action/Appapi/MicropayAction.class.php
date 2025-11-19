<?php

class MicropayAction extends Action
{
    public function reqParams()
    {
        $input = empty($_POST) ? json_decode(file_get_contents("php://input"), true) : $_POST;
        return $input;
    }

    public function getDevice()
    {
        $params = $this->reqParams();
        $mcode = isset($params['mcode']) ? trim($params['mcode']) : '';
        $rs = ['error_code' => 0, 'error_msg' => '成功'];
        if (empty($mcode)) {
            $rs['error_code'] = 10001;
            $rs['error_msg'] = '设备终端号不能为空';
        } else {
            $device = M('Newland_pos_device')->where(['mcode' => $mcode])->find();
            if (empty($device)) {
                $rs['error_code'] = 10002;
                $rs['error_msg'] = '设备不存在';
            } else {
                $rs['retval'] = [
                    'mch_id' => $device['mer_id'],
                    'mkey' => $device['mkey']
                ];
            }
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 订单状态转换成整数
     */
    public static function tradeState2int($state, $payType)
    {
        if ($payType == 'Wechat') {
            $arr = [
                'SUCCESS' => 1,
                'REFUND' => 3,
                'NOTPAY' => 0,
                'CLOSED' => 2,
                'REVOKED' => 2,
                'USERPAYING' => 0,
                'PAYERROR' => 0
            ];
            return isset($arr[$state]) ? $arr[$state] : 0;
        } else if ($payType == 'Alipay') {
            $arr = [
                'TRADE_SUCCESS' => 1,
                'TRADE_CLOSED' => 2,
                'TRADE_FINISHED' => 1,
                'WAIT_BUYER_PAY' => 0
            ];
            return isset($arr[$state]) ? $arr[$state] : 0;
        } else {
            return $state;
        }
    }

    public function queryOrder()
    {
        $params = $this->reqParams();
        //auth_code  或 orderid  订单号优先
        $where = [];
        $rs = ['error_no' => 0, 'error_msg' => '查询成功'];
        try {
            if (empty($params['orderid']) && empty($params['auth_code'])) {
                throw new \Exception('orderid或auth_code必须传递一个，且不能为空', 30001);
            }
            if (!empty($params['orderid'])) {
                $where['orderid'] = $params['orderid'];
            }
            if (!empty($params['auth_code'])) {
                $where['auth_code'] = $params['auth_code'];
            }
            $order = M('Micropay_order')->where($where)->find();
            if (empty($order)) {
                throw new \Exception('查询订单不存在', 30002);
            }
            $config = D('Config')->get_config();
            C('config',$config);
            if(C("config.open_wftpay") == '1' && ($order['pay_type'] == 'Wechat' || $order['pay_type'] == 'Alipay')){
                import('@.ORG.LowFeePay');
                $lowfeepay = new LowFeePay('wftpay');
                $order_info['auth_code'] = $params['auth_code'];
                $order_info['re_call'] = 1;
                $order_info['is_own'] = 0;
                $order_info['out_trade_no'] = $params['orderid'];
                $return = $lowfeepay->scan_pay($order_info);
                if ($return['return_code'] == 'FAIL') {
                    //失败
                    $rs['error_no'] = 30003;
                    $rs['error_msg'] = '支付失败！支付返回：'. $return['return_msg'];
                }
                elseif ($return['result_code'] == 'FAIL') {
                    $rs['error_no'] = 30003;
                    $rs['error_msg'] = '支付失败！支付返回：'. $return['err_code_des'];
                }
                elseif ($return['err_code'] == 'USERPAYING') {
                    $rs['error_no'] = 30003;
                    $rs['error_msg'] = '支付失败！支付返回：正在支付！';
                }
                elseif ($return['trade_state'] == 'USERPAYING') {
                    $rs['error_no'] = 30003;
                    $rs['error_msg'] = '支付失败！支付返回：正在支付！';
                }
                else{
                    //成功
                    $payData = [];
                    if (isset($return['transaction_id'])) {
                        $payData['transaction_id'] = $return['transaction_id'];
                    }
                    if($return['return_code'] != 'FAIL' && $return['result_code'] != 'FAIL' && $return['err_code'] != 'USERPAYING' && $return['trade_state'] != 'USERPAYING' && $return['result_code'] == 'TRUE'){
                        $payData['status'] = 1;   
                    }
                    $payData['pay_time'] = time();
                    if ($payData['status'] == '1') {
                        M('Micropay_order')->where(['id' => $order['id']])->save($payData);
                        if ($payData['status'] == 1) {
                            D('Micropay_order')->settlementToMerchantByOrderid($order['orderid']);
                        }
                    }
                    $rs['retval'] = [
                        'orderid' => $order['orderid'],
                        'total_fee' => $order['total_fee'],
                        'pay_type' => $order['pay_type'],
                        'body' => $order['body'],
                        'status' => isset($payData['status']) ? $payData['status'] : $order['status']];
                    if ($payData['status'] == 1) {
                        $rs['retval']['pay_time'] = $payData['pay_time'];
                    }
                }
            }
            else{
                import("@.ORG.Micropay.Factory");
                $queryRs = \Micropay\Factory::getInstance($order['pay_type'], $params, $order)->queryOrder();
                if ($queryRs['error_code'] == 0) {
                    //成功
                    $payData = [];
                    if ($order['pay_type'] == 'Wechat') {
                        if (isset($queryRs['retval']['transaction_id'])) {
                            $payData['transaction_id'] = $queryRs['retval']['transaction_id'];
                        }
                        if (isset($queryRs['retval']['trade_state'])) {
                            $payData['status'] = self::tradeState2int($queryRs['retval']['trade_state'], $order['pay_type']);
                        }
                        if (isset($queryRs['retval']['time_end'])) {
                            $payData['pay_time'] = strtotime($queryRs['retval']['time_end']);
                        }
                    } else if ($order['pay_type'] == 'Alipay') {
                        if (isset($queryRs['retval']['trade_no'])) {
                            $payData['transaction_id'] = $queryRs['retval']['trade_no'];
                        }
                        if (isset($queryRs['retval']['trade_status'])) {
                            $payData['status'] = self::tradeState2int($queryRs['retval']['trade_status'], $order['pay_type']);
                        }
                        if (isset($queryRs['retval']['send_pay_date'])) {
                            $payData['pay_time'] = strtotime($queryRs['retval']['send_pay_date']);
                        }
                    } else {
                        if (isset($queryRs['retval']['status'])) {
                            $payData['status'] = $queryRs['retval']['status'];
                            if ($queryRs['retval']['pay_time'] > 0) {
                                $payData['pay_time'] = $queryRs['retval']['pay_time'];
                            }
                        }
                    }
                    if ($payData) {
                        M('Micropay_order')->where(['id' => $order['id']])->save($payData);
                        if ($payData['status'] == 1) {
                            D('Micropay_order')->settlementToMerchantByOrderid($order['orderid']);
                        }
                    }
                    $rs['retval'] = [
                        'orderid' => $order['orderid'],
                        'total_fee' => $order['total_fee'],
                        'pay_type' => $order['pay_type'],
                        'body' => $order['body'],
                        'status' => isset($payData['status']) ? $payData['status'] : $order['status']];
                    if ($payData['status'] == 1) {
                        $rs['retval']['pay_time'] = $payData['pay_time'];
                    }
                } else {
                    //失败
                    $rs['error_no'] = 30003;
                    $rs['error_msg'] = $queryRs['error_msg'];
                }
            }
        } catch (\Exception $e) {
            $rs['error_no'] = $e->getCode() > 30000 ? $e->getCode() : 30004;
            $rs['error_msg'] = $e->getMessage();
        }
        $this->ajaxReturn($rs);
    }

    public function pay()
    {
        import("@.ORG.Micropay.Factory");
        $data = $this->reqParams();
        $orderMod = M('Micropay_order');
        $rs = ['error_no' => 0, 'error_msg' => '发起支付成功'];
        try {
            if (empty($data['auth_code'])) {
                throw new \Exception('二维码或条码值参数不能为空', 20001);
            }
            if ($orderMod->where(['auth_code' => $data['auth_code']])->find()) {
                throw new \Exception('每个二维码仅限使用一次，请刷新再试', 20002);
            }
            $device = M('Newland_pos_device')->where(['mer_id' => $data['mch_id'], 'mcode' => $data['mcode']])->find();
            if (empty($device)) {
                throw new \Exception('设备不存在', 20004);
            }
            $order = [
                'mer_id' => $data['mch_id'],
                'orderid' => 'micro_' . build_real_orderid(0),
                'mcode' => $data['mcode'],
                'device_name' => $device['name'],
                'total_fee' => $data['total_fee'] / 100,
                'auth_code' => $data['auth_code'],
                'body' => $data['body'] ?: "付款码支付",
                'create_time' => $data['create_time'],
                'pay_type' => $data['pay_type']
            ];
            $orderid = $orderMod->add($order);
            $order['id'] = $orderid;
            $config = D('Config')->get_config();
            C('config',$config);
            if(C("config.open_wftpay") == '1' && ($data['pay_type'] == 'Wechat' || $data['pay_type'] == 'Alipay')){
                import('@.ORG.LowFeePay');
                $lowfeepay = new LowFeePay('wftpay');
                $order_info['auth_code'] = $data['auth_code'];
                $order_info['is_own'] = 0;
                $order_info['out_trade_no'] = $order['orderid'];
                $order_info['total_fee'] = $order['total_fee'];
                $order_info['order_name'] = $order['body'];
                $return = $lowfeepay->scan_pay($order_info);
                if ($return['return_code'] == 'FAIL') {
                    //失败
                    $rs['error_no'] = 20003;
                    $rs['error_msg'] = '支付失败！支付返回：'. $return['return_msg'];
                }
                elseif ($return['result_code'] == 'FAIL') {
                    $rs['error_no'] = 20003;
                    $rs['error_msg'] = '支付失败！支付返回：'. $return['err_code_des'];
                }
                else{
                    //成功
                    $rs['retval'] = [
                        'orderid'=>$order['orderid']
                    ];
                }
            }
            else{
                $payRs = (\Micropay\Factory::getInstance($data['pay_type'], $data, $order))->micropay();
                if ($payRs['error_code'] == 0) {
                    //成功
                    $rs['retval'] = [
                        'orderid'=>$order['orderid']
                    ];
                } else {
                    //失败
                    $rs['error_no'] = 20003;
                    $rs['error_msg'] = $payRs['error_msg'];
                }
            }
        } catch (\Exception $e) {
            $rs['error_no'] = $e->getCode() > 20000 ? $e->getCode() : 20004;
            $rs['error_msg'] = $e->getMessage();
        }
        $this->ajaxReturn($rs);
    }
}
