<?php

namespace internalCard;

import("@.ORG.service.BaseService");

class InternalUnitService
{
    /**
     * author:Luo
     * 生成批次号
     * @return string
     */
    public static function genDeductionBatchNo()
    {
        $rand_str = '123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        do {
            $batch_no = 'YKTD';
            for ($i = 0; $i < 8; $i++) {
                $_rand = rand(0, strlen($rand_str) - 1);
                $batch_no .= $rand_str[$_rand];
            }
        } while (M('internal_unit_deduction')->where(['batch_no' => $batch_no])->find());

        return $batch_no;
    }

    /**
     * author:Luo
     * 生成批次号
     * @return string
     */
    public static function genRechargeBatchNo()
    {
        $rand_str = '123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        do {
            $batch_no = 'YKTR';
            for ($i = 0; $i < 8; $i++) {
                $_rand = rand(0, strlen($rand_str) - 1);
                $batch_no .= $rand_str[$_rand];
            }
        } while (M('internal_unit_recharge')->where(['batch_no' => $batch_no])->find());

        return $batch_no;
    }

    /**
     * author:Luo
     * 归属单位一卡通充值记录
     * @param int $unit_id
     * @param int $card_recharge_money
     * @param int $system_id
     * @return bool|mixed|string
     */
    public static function unitMoneyDeduction($unit_id = 0, $card_recharge_money = 0, $system_id = 0)
    {
        $m_internal_unit_recharge = M('internal_unit_recharge');
        $m_internal_unit_deduction = M('internal_unit_deduction');
        $m_internal_unit_money_log = M('internal_unit_money_log');

        $batch_no = self::genDeductionBatchNo();

        $balance_due = $card_recharge_money;
        //获取归属单位付款记录
        $recharge_con = [];
        $recharge_con['unit_id'] = $unit_id;
        $recharge_con['balance'] = ['gt', 0];
        $recharge_list = $m_internal_unit_recharge->where($recharge_con)->order('id asc')->select();
        $insert_log_all = [];
        foreach ($recharge_list as $_key => $_val) {
            //记录日志数据
            $_insert_log = [];
            $_insert_log['unit_id'] = $unit_id;
            $_insert_log['recharge_id'] = $_val['id'];
            $_insert_log['deduction_batch_no'] = $batch_no;
            $_insert_log['recharge_batch_no'] = $_val['batch_no'];
            $_insert_log['create_system_id'] = $system_id;
            if ($balance_due >= $_val['balance']) {
                $_insert_log['money'] = $_val['balance'];
                $balance_due -= $_val['balance'];
            } else {
                $_insert_log['money'] = $balance_due;
                $balance_due = 0;
            }
            $insert_log_all[] = $_insert_log;

            //更新收款记录余额
            $recharge_ret = $m_internal_unit_recharge->where(['id' => $_val['id']])->save(['balance' => $_val['balance'] - $_insert_log['money']]);
            if ($recharge_ret === false) {
                return false;
            }

            if ($balance_due == 0) {
                break;
            }
        }

        //插入一卡通充值抵扣记录
        $insert_deduction = [];
        $insert_deduction['unit_id'] = $unit_id;
        $insert_deduction['batch_no'] = $batch_no;
        $insert_deduction['total_money'] = $card_recharge_money;
        $insert_deduction['balance_due'] = $balance_due;
        $insert_deduction['create_system_id'] = $system_id;
        $deduction_id = $m_internal_unit_deduction->add($insert_deduction);
        if (!$deduction_id) {
            return false;
        }

        //插入日志明细
        if ($insert_log_all) {
            foreach ($insert_log_all as $_key => $_val) {
                $insert_log_all[$_key]['deduction_id'] = $deduction_id;
            }

            $log_ret = $m_internal_unit_money_log->addAll($insert_log_all);
            if (!$log_ret) {
                return false;
            }
        }

        //更新归属单位余额
        $balance_ret = self::updateUnitBalance($unit_id, $card_recharge_money);
        if (!$balance_ret) {
            return false;
        }

        return ['deduction_id' => $deduction_id, 'batch_no' => $batch_no];
    }

    /**
     * author:Luo
     * 更新归属单位余额
     * @param int $unit_id
     * @param int $use_balance
     * @param int $recharge_money
     * @return bool
     */
    public static function updateUnitBalance($unit_id = 0, $use_balance = 0, $recharge_money = 0)
    {
        $m_internal_unit = M('internal_unit');
        $unit_info = $m_internal_unit->where(['id' => $unit_id])->find();
        if (!$unit_info) {
            return false;
        }

        $update_data = [];
        $update_data['balance'] = $unit_info['balance'] - $use_balance;
        if ($recharge_money > 0) {
            $update_data['total_money'] = $unit_info['total_money'] + $recharge_money;
            $update_data['balance'] += $recharge_money;
            $update_data['used_money'] += $use_balance;
        }

        $ret = $m_internal_unit->where(['id' => $unit_id])->save($update_data);
        if ($ret === false) {
            return false;
        }

        return true;
    }

}