<?php


class UserNoticeModel extends Model
{
    public function addNotice(array $param)
    {
        fdump_api([
            'msg' => '添加消息',
            'params' => $param
        ],'userNotice',1);
        //copy from v20/app/common/model/service/user/UserNoticeService.php
        if ($param['type'] != 0) {
            return false;
        }
        $addData['business']    = $param['business'];
        $addData['order_id']    = $param['order_id'];
        $addData['mer_id']      = $param['mer_id'] ?? 0;
        $addData['store_id']    = $param['store_id'] ?? 0;
        $addData['tools_id']    = $param['tools_id'] ?? 0;
        $addData['village_id']  = $param['village_id'] ?? 0;
        $addData['title']       = $param['title'] ?? '';
        $addData['content']     = $param['content'] ?? '';
        $addData['create_time'] = time();
        if (!$addData['uid']) {
            $orderInfo = $this->getOrderInfo($param['business'], $param['order_id']);
            if (!$orderInfo) {
                return false;
            }
            $addData['uid']        = $orderInfo['uid'];
            $addData['mer_id']     = $addData['mer_id'] ?: ($orderInfo['mer_id'] ?? 0);
            $addData['store_id']   = $addData['store_id'] ?: ($orderInfo['store_id'] ?? 0);
            $addData['tools_id']   = $addData['tools_id'] ?: ($orderInfo['tools_id'] ?? 0);
            $addData['village_id'] = $addData['village_id'] ?: ($orderInfo['village_id'] ?? 0);
        }

        //查询消息板块
        $where['uid']     = $addData['uid'];
        $where['type']    = $param['type'];
        $userNoticeTypeId = M('User_notice_type')->where($where)->field('id')->find();

        M()->startTrans();
        try {
            //写入消息通知列表
            $add = $this->add($addData);
            if (!$add) {
                fdump_api([$addData, $add, $this->getLastSql()], 'userNoticeError', 1);
            }
            if (!$userNoticeTypeId) {//创建消息板块
                $isTop  = $param['type'] == 0 ? 1 : 0;
                $update = M('User_notice_type')->add([
                    'type'        => $param['type'],
                    'store_id'    => $param['store_id'] ?? 0,
                    'update_time' => time(),
                    'new_time'    => time(),
                    'content'     => $param['content'],
                    'is_del'      => 0,
                    'is_top'      => $isTop,
                    'uid'         => $param['uid']
                ]);
            } else {//编辑消息最新更新时间
                $update = M('User_notice_type')->where('id', $userNoticeTypeId)->save([
                    'update_time' => time(),
                    'new_time'    => time(),
                    'content'     => $param['content'],
                    'is_del'      => 0
                ]);
            }
            if (!$update) {
                fdump_api(M('User_notice_type')->getLastSql(), 'userNoticeError', 1);
            }
            M()->commit();
            fdump_api([$param, $addData, '成功'], 'userNoticeError', 1);
        } catch (\Exception $e) {
            M()->rollback();
            fdump_api([$param, $e->getMessage()], 'userNoticeError', 1);
        }
        return true;
    }

    /**
     * 获取订单信息
     */
    public function getOrderInfo($business, $orderId)
    {
        $orderInfo = [];
        switch ($business) {
            case 'shop':
                $orderInfo = M('Shop_order')
                    ->where(['order_id' => $orderId])
                    ->field('mer_id,store_id,uid')
                    ->find();
                break;
        }
        return $orderInfo;
    }
}