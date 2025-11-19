<?php

class Merchant_withdrawModel extends Model
{
    public function refuseWithdraw($withdrawId, $failReason): bool
    {
        $failReason = D('Merchant_money_list')->getFailReasonTxt($failReason);
        $merchantWithdraw = M('Merchant_withdraw')->where(['id' => $withdrawId])->find();
        if (empty($merchantWithdraw)) {
            fdump_api(['Merchant_withdraw提现记录不存在', $withdrawId, $failReason], 'withdraw/fail', 1);
            throw_exception('提现记录不存在');
        }
        if ($merchantWithdraw['status'] != 10) {//提现中
            fdump_api(['该提现未在提现中，不能拒绝', $withdrawId, $failReason], 'withdraw/fail', 1);
            throw_exception('该提现未在提现中不能拒绝');
        }
        D('Merchant_money_list')->reject($merchantWithdraw['mer_id'], $withdrawId, $failReason);

        return true;
    }
}