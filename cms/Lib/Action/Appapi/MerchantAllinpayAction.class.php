<?php

/**
 * 商家通联微信小程序收银台回调及状态查询。
 * 当前业务范围仅包含预约服务。
 */
class MerchantAllinpayAction extends BaseAction
{
    const CODE_VERSION = 'merchant_allinpay_status_20260907_1';

    public function notify()
    {
        $params = array_merge($_GET, $_POST);
        $input = file_get_contents('php://input');
        if ($input) {
            $inputParams = array();
            $contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower((string)$_SERVER['CONTENT_TYPE']) : '';
            if (strpos($contentType, 'application/json') !== false || in_array(substr(ltrim($input), 0, 1), array('{', '['), true)) {
                $inputParams = json_decode($input, true) ?: array();
            } else {
                parse_str($input, $inputParams);
            }
            $params = array_merge($params, $inputParams);
        }
        fdump_sql(array('params' => $params, 'input' => $input), 'merchant_allinpay_notify');

        import('@.ORG.pay.MerchantAllinpayWxapp');
        if ((string)($params['trxstatus'] ?? '') !== '0000') {
            $this->notifyResult(false, 'trxstatus failed');
        }

        $orderNo = $params['cusorderid'] ?? ($params['reqsn'] ?? ($params['out_trade_no'] ?? ''));
        if (!$orderNo) {
            $this->notifyResult(false, 'order no empty');
        }

        $paymentModel = M('Payment_money_order');
        $payment = $paymentModel->where(array(
            'orderid' => $orderNo,
            'order_type' => array('in', array('appoint', 'balance-appoint')),
            'pay_type' => array('in', MerchantAllinpayWxapp::getPaymentPayTypes()),
        ))->find();
        if (!$payment) {
            $this->notifyResult(false, 'payment order not found');
        }

        // 与收费缴费支付中心一致，回调使用创建支付单时保存的通道配置，
        // 不再受付款后总后台开关或配置变化影响。
        $paymentConfig = json_decode($payment['pay_config'], true) ?: array();
        $expectedCusid = trim((string)($paymentConfig['cusid'] ?? ''));
        if ($expectedCusid === '' || trim((string)($params['cusid'] ?? '')) !== $expectedCusid) {
            $this->notifyResult(false, 'cusid mismatch');
        }

        try {
            $this->completePayment($payment, $params);
        } catch (Throwable $e) {
            fdump_sql(array('params' => $params, 'message' => $e->getMessage()), 'merchant_allinpay_notify_error');
            $this->notifyResult(false, $e->getMessage());
        }
        $this->notifyResult(true);
    }

    public function status()
    {
        import('@.ORG.pay.MerchantAllinpayWxapp');
        $orderId = intval(I('order_id'));
        $businessType = I('order_type', 'appoint', 'trim');
        if (!MerchantAllinpayWxapp::supportsBusiness($businessType)) {
            $businessType = 'appoint';
        }
        if (!$orderId || !$this->_uid) {
            $this->returnCode('20044010');
        }
        $order = M('Appoint_order')->where(array(
            'order_id' => $orderId,
            'uid' => $this->_uid,
        ))->field('order_id,appoint_id,mer_id,store_id,paid,service_status,is_del,product_pay_type')->find();
        if (!$order) {
            $this->returnCode('20046002');
        }
        // 以实际生成的商家通联支付单为准识别预约定金/余款类型，
        // 避免前端参数缺失或传错时把 balance-appoint 当成已支付的 appoint。
        $payment = M('Payment_money_order')->where(array(
            'order_id' => $orderId,
            'order_type' => array('in', array('appoint', 'balance-appoint')),
            'pay_type' => array('in', MerchantAllinpayWxapp::getPaymentPayTypes()),
        ))->order('id DESC')->find();
        if ($payment && MerchantAllinpayWxapp::supportsBusiness($payment['order_type'])) {
            $businessType = $payment['order_type'];
        }
        $paid = $this->isBusinessOrderPaid($order, $businessType);
        $queryStatus = '';
        $queryRetcode = '';
        $queryMessage = '';
        $paymentFound = $payment ? 1 : 0;
        $queried = 0;
        $syncAttempted = 0;
        $syncSuccess = 0;
        $syncStage = $paid ? 'already_paid' : ($payment ? 'payment_found' : 'payment_not_found');
        if (!$paid && intval(I('query_allinpay')) === 1) {
            if ($payment && $payment['orderid']) {
                $queried = 1;
                $syncStage = 'querying';
                try {
                    // 查单同样优先使用创建支付单时保存的 appid/cusid，避免后台配置变化后查错商户。
                    $savedConfig = json_decode($payment['pay_config'], true) ?: array();
                    $configStoreId = intval($savedConfig['store_id'] ?? $order['store_id']);
                    $currentConfig = MerchantAllinpayWxapp::getConfig($configStoreId);
                    $queryConfig = array_merge($currentConfig, array_filter(array(
                        'appid' => isset($savedConfig['appid']) ? $savedConfig['appid'] : '',
                        'cusid' => isset($savedConfig['cusid']) ? $savedConfig['cusid'] : '',
                    )));
                    $allinpay = new MerchantAllinpayWxapp($queryConfig);
                    $queryResult = $allinpay->query($payment['orderid']);
                    $queryRetcode = (string)($queryResult['retcode'] ?? '');
                    $queryStatus = (string)($queryResult['trxstatus'] ?? '');
                    $queryMessage = (string)($queryResult['retmsg'] ?? ($queryResult['errmsg'] ?? ''));
                    if ($queryRetcode === 'SUCCESS' && $queryStatus === '0000') {
                        $syncAttempted = 1;
                        $syncStage = 'updating_business_order';
                        $this->completePayment($payment, $queryResult);
                        $order = M('Appoint_order')->where(array(
                            'order_id' => $orderId,
                            'uid' => $this->_uid,
                        ))->field('order_id,appoint_id,mer_id,store_id,paid,service_status,is_del,product_pay_type')->find();
                        $paid = $this->isBusinessOrderPaid($order, $businessType);
                        $syncSuccess = $paid ? 1 : 0;
                        $syncStage = $syncSuccess ? 'business_order_updated' : 'business_order_not_updated';
                    } else {
                        $syncStage = $queryRetcode !== 'SUCCESS' ? 'query_failed' : 'transaction_not_paid';
                    }
                } catch (Throwable $e) {
                    $queryMessage = $e->getMessage();
                    $syncStage = $syncAttempted ? 'business_order_update_failed' : 'query_exception';
                    fdump_sql(array(
                        'order_id' => $orderId,
                        'payment_orderid' => $payment['orderid'],
                        'message' => $e->getMessage(),
                    ), 'merchant_allinpay_status_query_error');
                }
            }
        }

        fdump_sql(array(
            'code_version' => self::CODE_VERSION,
            'uid' => intval($this->_uid),
            'order_id' => $orderId,
            'payment_id' => $payment ? intval($payment['id']) : 0,
            'payment_orderid' => $payment ? (string)$payment['orderid'] : '',
            'business_type' => $businessType,
            'paid' => $paid ? 1 : 0,
            'queried' => $queried,
            'sync_attempted' => $syncAttempted,
            'sync_success' => $syncSuccess,
            'sync_stage' => $syncStage,
            'query_retcode' => $queryRetcode,
            'query_status' => $queryStatus,
            'query_message' => $queryMessage,
        ), 'merchant_allinpay_status');

        $this->returnCode(0, array(
            'code_version' => self::CODE_VERSION,
            'paid' => $paid ? 1 : 0,
            'appoint_id' => intval($order['appoint_id']),
            'order_type' => $businessType,
            'service_status' => intval($order['service_status']),
            'is_del' => intval($order['is_del']),
            'payment_found' => $paymentFound,
            'payment_paid_money' => $payment ? (string)$payment['paid_money'] : '',
            'queried' => $queried,
            'sync_attempted' => $syncAttempted,
            'sync_success' => $syncSuccess,
            'sync_stage' => $syncStage,
            'query_retcode' => $queryRetcode,
            'query_status' => $queryStatus,
            'query_message' => $queryMessage,
        ));
    }

    private function isBusinessOrderPaid($order, $businessType)
    {
        if (!$order) {
            return false;
        }
        return $businessType == 'balance-appoint'
            ? trim((string)$order['product_pay_type']) !== ''
            : intval($order['paid']) === 1;
    }

    /**
     * 通联异步通知和主动查单共用同一套商家预约落单逻辑。
     */
    private function completePayment($payment, $params)
    {
        $paidFen = intval($params['trxamt'] ?? 0);
        if ($paidFen <= 0 || abs(intval(round($payment['payment_money'] * 100)) - $paidFen) > 1) {
            throw new Exception('支付金额不一致');
        }

        $paymentModel = M('Payment_money_order');
        $paymentModel->startTrans();
        try {
            $payment = $paymentModel->where(array('id' => $payment['id']))->lock(true)->find();
            if (!$payment) {
                throw new Exception('支付订单不存在');
            }
            $businessType = $payment['order_type'];
            $appointOrder = M('Appoint_order')->where(array(
                'order_id' => $payment['order_id'],
            ))->field('order_id,orderid,orderids,paid,product_pay_type')->find();
            if (!$appointOrder) {
                throw new Exception('预约订单不存在');
            }
            if ($this->isBusinessOrderPaid($appointOrder, $businessType)) {
                if ((float)$payment['paid_money'] <= 0) {
                    $paymentModel->where(array('id' => $payment['id']))->setField('paid_money', $paidFen / 100);
                }
                $paymentModel->commit();
                return true;
            }

            $orderNo = $payment['orderid'];
            // 旧预约支付完成逻辑按 orderid 查找订单，因此主动查单与异步通知均先同步该字段。
            M('Appoint_order')->where(array('order_id' => $appointOrder['order_id']))->setField('orderid', $orderNo);
            $paymentModel->where(array('id' => $payment['id']))->setField('paid_money', $paidFen / 100);
            $orderParam = array(
                'order_id' => $orderNo,
                'order_type' => $businessType,
                'pay_type' => MerchantAllinpayWxapp::PAY_TYPE,
                'pay_money' => $paidFen / 100,
                'third_id' => $params['trxid'] ?? ($params['chnltrxid'] ?? ''),
                'pay_time' => $params['paytime'] ?? ($params['fintime'] ?? ''),
                'is_mobile' => 3,
                'is_own' => 0,
                'return' => 1,
            );
            $result = $businessType == 'balance-appoint'
                ? D('Appoint_order')->balance_after_pay($orderParam)
                : D('Appoint_order')->after_pay($orderParam);
            if (is_array($result) && !empty($result['error'])) {
                throw new Exception($result['msg'] ?: '预约订单支付完成处理失败');
            }
            $completedOrder = M('Appoint_order')->where(array(
                'order_id' => $appointOrder['order_id'],
            ))->field('order_id,appoint_id,paid,product_pay_type,service_status')->find();
            if (!$this->isBusinessOrderPaid($completedOrder, $businessType)) {
                throw new Exception('预约订单支付完成方法已执行，但订单支付状态未更新');
            }
            fdump_sql(array(
                'payment_id' => $payment['id'],
                'payment_orderid' => $orderNo,
                'business_type' => $businessType,
                'business_order' => $completedOrder,
            ), 'merchant_allinpay_complete');
            $paymentModel->commit();
            return true;
        } catch (Throwable $e) {
            $paymentModel->rollback();
            throw $e;
        }
    }

    private function notifyResult($success, $message = '')
    {
        if (!$success) {
            fdump_sql(array('message' => $message), 'merchant_allinpay_notify_fail');
        }
        exit($success ? 'success' : 'fail');
    }
}
