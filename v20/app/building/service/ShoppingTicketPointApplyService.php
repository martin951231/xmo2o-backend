<?php

namespace app\building\service;

use app\building\model\ShoppingTicketPointApply;
use think\facade\Db;

class ShoppingTicketPointApplyService
{

    public function getTicketPointDetail($id)
    {
        if (empty($id)) {
            throw new \Exception('参数错误');
        }

        $detail = (new ShoppingTicketPointApply())->getTicketPointDetail($id);
        if (empty($detail)) {
            throw new \Exception('申请记录不存在');
        }
        return $detail;
    }

    //根据金额计算预计获得积分数量
    public function calTicketGetScoreCount($money)
    {
        $where = [
            ['name', 'IN', ['open_ticket_point', 'ticket_point_type', 'ticket_point_score_number', 'ticket_point_rule']]
        ];
        $result = (new \app\common\model\db\ConfigData())
            ->where($where)
            ->column('value', 'name');
        $count = 0;
        if ($result['ticket_point_type'] == 1) {
            //按消费金额
            $count = getFormatNumber($result['ticket_point_score_number'] * $money);
        } else if ($result['ticket_point_type'] == 2) {
            //按固定积分
            $count = $result['ticket_point_score_number'];
        }
        return intval($count);
    }


    public function handleTicketPoint($id, $status, $reason, $money, $adminAccount)
    {
        if (empty($id) || empty($status) || !in_array($status, [1, 2]) || empty($adminAccount)) {
            throw new \Exception('参数错误');
        }

        $apply = (new ShoppingTicketPointApply())->where('id', $id)->find();
        if (empty($apply)) {
            throw new \Exception('申请记录不存在');
        }
        if ($apply->status == $status) {
            throw new \Exception('审核结果不变，请勿重复审核');
        }

        $detail = $this->getTicketPointDetail($id);
        $update = [
            'review_time' => time(),
            'status' => $status,
            'admin_account' => $adminAccount,
            'reason' => $reason,
            'admin_money' => $money,
            'get_score' => 0
        ];
        if ($status == 1) {
            if ($money < 0.01) {
                throw new \Exception('金额不能小于0.01元');
            }
            //审核通过
            $update['get_score'] = $this->calTicketGetScoreCount($money);
            //把订单更改下单用户uid
            //Db::name('store_order')->where('orderid', $detail['real_orderid'])->where('uid', 0)->update(['uid' => $detail['uid']]);
            //给用户加积分
            (new \app\common\model\service\UserService())->addScore($detail['uid'], $update['get_score'], '线下小票拍照积分审核通过获得' . cfg('score_name'));
        } else if ($status == 2) {
            //审核驳回,如果之前有审核通过，则需要扣除积分
            if (empty($reason)) {
                throw new \Exception('请填写驳回原因');
            }
            if ($apply['get_score'] > 0) {
                //给用户加积分
                (new \app\common\model\service\UserService())->userScore($detail['uid'], $apply['get_score'], '线下小票拍照积分审核驳回扣除' . cfg('score_name'));
                $update['get_score'] = 0;
            }
        }
        (new ShoppingTicketPointApply())->where('id', $id)->update($update);


        //记录审核日志
        $log = [
            'apply_id' => $id,
            'status' => $status,
            'reason' => $reason,
            'money' => $money,
            'get_score' => $status == 1 ? $update['get_score'] : $apply['get_score'],
            'admin_account' => $adminAccount,
        ];
        Db::name('shopping_ticket_point_handle_logs')->insert($log);
        return true;
    }

    //获取审核日志
    public function handleTicketPointLogs($applyId)
    {
        if (empty($applyId)) {
            return [];
        }
        return  Db::name('shopping_ticket_point_handle_logs')
            ->where(['apply_id' => $applyId])
            ->select()
            ->each(function ($item, $key) {
                $item['get_score_txt'] = $item['status'] == 1 ? $item['get_score'] : $item['get_score'] * -1;
                $item['status_txt'] = $item['status'] == 1 ? '通过' : '驳回';
                return $item;
            })
            ->toArray();
    }
}
