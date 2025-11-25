<?php

namespace Micropay\Type;

use Micropay\Base;

class Upay extends Base
{
    public $config = [];

    public function __construct($params = [], $orderInfo = [])
    {
        parent::__construct($params, $orderInfo);
    }

    public function micropay()
    {
        $merId = $this->orderInfo['mer_id'];
        $authCode = $this->orderInfo['auth_code'];
        $checkRs = D('Qiye')->checkPayAuthCode($authCode);
        if ($checkRs['error_no']) {
            throw new \Exception($checkRs['error_msg']);
        }
        $payCode = $checkRs['retval'];
        $uid = $payCode['uid'];
        M('Micropay_order')->where(['orderid' => $this->orderInfo['orderid']])->save(['uid' => $uid]);

        $qiyeMoney = 0;
        $balanceMoney = 0;
        if (!(D('Qiye')->checkTheSameQiye($uid, $merId))) {
            //使用余额支付
            $nowUser = D('User')->get_user($uid, 'uid');
            if ($nowUser['now_money'] < $this->orderInfo['total_fee']) {
                throw new \Exception('余额不足');
            }
            $res = D('User')->user_money($uid, $this->orderInfo['total_fee'], L_('扫码支付使用平台余额'));
            $balanceMoney = $this->orderInfo['total_fee'];
        } else {
            //优先使用企业预存款，企业预存款不足平台余额补上
            $res = D('User')->use_qiye_money($uid, $this->orderInfo['total_fee'], $this->orderInfo['id'], L_('扫码付使用企业预存款余额'), 'micro', true);
            $qiyeMoney = isset($res['retval']['qiye_money']) ? $res['retval']['qiye_money'] : 0;
            $balanceMoney = isset($res['retval']['balance_money']) ? $res['retval']['balance_money'] : 0;
        }

        if (!$res['error_code']) {
            $payData = ['status' => 1, 'pay_time' => time(), 'qiye_money' => $qiyeMoney, 'balance_money' => $balanceMoney,'uid'=>$uid];
            M('Micropay_order')->where(['orderid' => $this->orderInfo['orderid']])->save($payData);
            M('Qiye_pay_code_record')->where(['id' => $payCode['id']])->save(['status' => 1, 'order_id' => $this->orderInfo['orderid'], 'type' => 'micro']);
            D('Micropay_order')->settlementToMerchantByOrderid($this->orderInfo['orderid']);
            return ['error_code' => 0, 'error_msg' => '支付成功'];
        } else {
            return ['error_code' => 1001, 'error_msg' => '【有饭有菜支付】错误描述：' . $res['msg']];
        }
    }

    public function queryOrder()
    {
        $order = M('Micropay_order')->where(['id' => $this->orderInfo['id']])->find();
        return ['error_code' => 0, 'error_msg' => '查询成功', 'retval' => $order];
    }
}