<?php

class CompanypayModel extends Model
{
    public function refuseWithdraw($withdrawId, $failReason): bool
    {
        $failReason = D('Merchant_money_list')->getFailReasonTxt($failReason);
        $company = M('Companypay')->where(['pigcms_id' => $withdrawId])->find();
        if (empty($company)) {
            fdump_api(['提现记录不存在', $withdrawId, $failReason], 'withdraw/fail', 1);
            throw_exception('提现记录不存在');
        }
        if ($company['status'] != 10) {//提现中
            fdump_api(['该提现未在提现中，不能拒绝', $withdrawId, $failReason], 'withdraw/fail', 1);
            throw_exception('该提现未在提现中不能拒绝');
        }
        $data['reason'] = $failReason;
        $data['status'] = 2;
        $res = D('Companypay')->where(['pigcms_id' => $withdrawId])->save($data);
        if (!$res) {
            fdump_api(['该提现更新失败', D('Companypay')->_sql(), $withdrawId, $failReason], 'withdraw/fail', 1);
            throw_exception('该提现更新失败');
        }
        if ($company['deliver_withdraw_id'] > 0) {
            $model = '\app\deliver\model\service\DeliverUserService/handleWithDrawAfter';
            $params = [
                'withdrawId' => $company['deliver_withdraw_id'],
                'status' => 2,
                'params' => ['admin_id' => $this->system_session['id'], 'reason' => $data['reason']]
            ];
            invoke_v20_service($model, $params);
        } elseif ($company['worker_withdraw_id'] > 0) {
            $money = ($company['money'] + $company['service_charges']) / 100;
            $worker = D('Merchant_workers')->where(['merchant_worker_id' => $company['pay_id']])->find();
            if ($worker) {
                D('Merchant_workers')->where(['merchant_worker_id' => $company['pay_id']])->setInc('now_money', $money);
                D('Merchant_workers_money')->where(['pigcms_id' => $company['worker_withdraw_id']])->save(['type' => 4, 'desc' => '提现至微信-提现失败，失败原因：' . $failReason]);
            } else {
                fdump_api(['技师不存在', $withdrawId, $failReason], 'withdraw/fail', 1);
            }
        } else {
            // 返还金额
            $money = ($company['money'] + $company['service_charges']) / 100;
            $user = D('User')->get_user($company['openid'], 'openid');
            if ($user) {
                $msg = '提现失败，失败原因：' . $failReason;
                D('User')->add_money($user['uid'], $money, $msg);
            } else {
                fdump_api(['用户不存在', $withdrawId, $failReason], 'withdraw/fail', 1);
            }
        }
        return true;
    }
}

?>