<?php

/**
 * 企业
 */
class QiyeModel extends Model
{
    /**
     * 获取员工
     */
    public function getStaff($uid)
    {
        $rs = M('Qiye_staff')->where(['job_number' => $uid])->find();
        return $rs ?: [];
    }

    /**
     * 判断商户和用户是否同一企业
     */
    public function checkTheSameQiye($uid, $merId)
    {
        $staff = $this->getStaff($uid);
        $merchant = M('Qiye_merchant')->where(['mer_id' => $merId, 'qiye_id' => $staff['qiye_id']])->find();
        if (!$staff || !$merchant) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 验证消费后增加企业已消费待结算金额
     */
    public function settlementOrder($orderType, $orderid)
    {
        if (!$orderType || !$orderid) {
            return false;
        }
        if (in_array($orderType, ['shop', 'mall'])) {
            //快店订单
            $orderInfo = M('Shop_order')->where(['real_orderid' => $orderid])->find();
        } else if (in_array($orderType, ['store'])) {
            //到店付
            $orderInfo = M('Store_order')->where(['order_id' => $orderid])->find();
        }
        if (empty($orderInfo)) {
            fdump_sql(func_get_args(), 'qiye_settlementOrder');
            return false;
        }
        if ($orderInfo['qiye_pay'] <= 0) {
            return true;
        }
        $buyerId = $orderInfo['uid'];
        $qiyePayMoney = $orderInfo['qiye_pay'];
        $staff = M('Qiye_staff')->alias('s')->join(C('DB_PREFIX') . 'qiye AS q ON s.qiye_id = q.qiye_id')->field('s.*,q.qiye_id,q.settlement_type,q.settlement_ratio')->where(['s.job_number' => $buyerId])->find();
        if (!$staff) {
            return false;
        }
        $settleMoney = round($qiyePayMoney * (1 + $staff['settlement_ratio'] / 100), 2);
        if (!$this->where(['qiye_id' => $staff['qiye_id']])->setInc('unsettlement_money', $settleMoney)) {
            fdump_sql($this->getError(), 'qiye_settlementOrder');
            return false;
        }
    }


    public function sendMoney2Staff($qiyeId, $deadline)
    {
        if ($qiyeId < 1 || $deadline < 1) {
            return false;
        }
        $tm = time();
        $ip = get_client_ip();
        $applyMod = M('Qiye_apply_money');
        $staffMod = M('Qiye_staff');
        $logMod = M('Qiye_staff_money_list');
        $lists = $applyMod->alias('a')
            ->join('INNER JOIN ' . C('DB_PREFIX') . 'qiye_staff AS s ON s.staff_id = a.staff_id')
            ->join('INNER JOIN ' . C('DB_PREFIX') . 'qiye AS q ON q.qiye_id = s.qiye_id')
            ->where(['q.qiye_id' => $qiyeId, 'a.send_time' => 0, 'a.status' => 1, 'a.operate' => 1,'time'=>['ELT',$deadline]])
            ->field('a.*,s.job_number')
            ->select();
        foreach ($lists as $v) {
            if ($staffMod->where(['staff_id' => $v['staff_id']])->setInc('money', $v['money'])) {
                $staffMod->where(['staff_id' => $v['staff_id'],'unpaid_money'=>['egt',$v['money']]])->setDec('unpaid_money', $v['money']);
                $afterMoney = $staffMod->where(['staff_id' => $v['staff_id']])->getField('money');
                $applyMod->where(['id' => $v['id']])->save(['send_time' => $tm, 'after_money' => $afterMoney]);
                $log = [
                    'staff_id' => $v['staff_id'],
                    'uid' => $v['job_number'],
                    'type' => 1,
                    'money' => $v['money'],
                    'time' => $tm,
                    'ip' => $ip,
                    'desc' => '企业结算完成，预存款充值到账',
                    'now_money' => $afterMoney
                ];
                $logMod->add($log);
            } else {
                fdump_sql(['item' => $v, 'error' => $staffMod->getError()], 'sendMoney2StaffError');
            }
        }
        return true;
    }

    /**
     * 扫码关注绑定企业员工
     * @param $staffid
     * @param $uid
     * @return bool|mixed
     */
    public function bindUid($staffid, $uid)
    {
        if ($staffid < 1 || $uid < 1) {
            return false;
        } else {
            $mod = M('Qiye_staff');
            $staff = $mod->where(['staff_id' => $staffid])->find();
            $user = M('User')->where(['uid' => $uid])->find();
            if (empty($staff['job_number'])) {
                if ($mod->where(['job_number' => $uid])->find()) {
                    return false;
                } else if ($user['phone'] && $mod->where(['phone' => $user['phone']])->find()) {
                    return false;
                } else {
                    $staff = $mod->where(['staff_id' => $staffid])->save(['job_number' => $uid, 'phone' => $user['phone']]);
                    return $staff;
                }
            } else {
                return $staff['job_number'] == $uid;
            }
        }
    }

    /**
     * 付款码检查
     */
    public function checkPayAuthCode($authCode)
    {
        $rs = ['error_no' => 0, 'error_msg' => '', 'retval' => []];
        if (empty($authCode)) {
            $rs['error_no'] = 1;
            $rs['error_msg'] = L_('付款码不能为空');
        } else {
            $payCode = M('Qiye_pay_code_record')->where(['code' => $authCode])->find();
            if (!$payCode) {
                $rs['error_no'] = 1;
                $rs['error_msg'] = L_('请扫描用户最新的付款码');
            } else if ($payCode['status'] != 0 && $payCode['expire_time'] < time()) {
                $rs['error_no'] = 1;
                $rs['error_msg'] = L_('该付款码已失效，请刷新付款码');
            } else {
                $rs['retval'] = $payCode;
            }
        }
        return $rs;
    }

    /**
     *根据用户id获取企业员工信息
     * @author 张涛
     * @date 2020/04/28
     */
    public function getQiyeStaffByUid($uid)
    {
        $staff = M('Qiye_staff')->where(array('job_number' => $uid, 'status' => 1))->find();
        if ($staff) {
            $qiyeInfo = M('Qiye')->where(array('qiye_id' => $staff['qiye_id']))->find();
            $qiye = [
                'qiye_name' => $qiyeInfo['name'],
                'qiye_money' => $staff['money']
            ];
            return $qiye;
        } else {
            return [];
        }
    }
}
