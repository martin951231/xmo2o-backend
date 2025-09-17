<?php

namespace app\common\model\service\plan\file;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallOrderDetail;
use app\mall\model\db\MallOrderLog;
use app\mall\model\service\activity\MallActivityService;
use app\mall\model\service\GoodsReplyService;
use app\store_marketing\model\service\StoreMarketingRecordService;
use think\facade\Db;

/**
 * MallOrderAutoConfirmReply.php
 * 自动确认完成
 * Create on 2021/1/22 11:00
 * Created by zhumengqun
 */
class MallOrderAutoConfirmReply
{
    public function runTask()
    {
        $autoTime = cfg('mall_order_service_comment_time') * 24 * 60 * 60;
        $nowTime = time();
        $distenceTiem = $nowTime - $autoTime;
        //确认收货后算起 超出后台配置的自动确认完成时间后计划任务自动确认完成
        $where = [['complete_time', '>=', $distenceTiem - 30 * 86400], ['complete_time', '<=', $distenceTiem], ['status', '>=', 30], ['status', '<', 40]];
        $arr = (new MallOrder())->getSome('order_id,uid,goods_activity_type', $where);
        if (!empty($arr)) {
            //启动事务
            Db::startTrans();
            $replyDatas = [];
            try {
                foreach ($arr as $val) {
                    $updateOrder = ['status' => 40, 'last_uptime' => time()];
                    //更新主订单
                    (new MallOrder())->updateThis(['order_id' => $val['order_id']], $updateOrder);
                    //更新子订单
                    $update = ['status' => 40, 'last_uptime' => time()];
                    (new MallOrderDetail())->updateThis(['order_id' => $val['order_id']], $update);
                    //更新周期购
                    if ($val['goods_activity_type'] == 'periodic') {
                        (new MallActivityService())->updateSurePeriodic($val['order_id']);
                    }
                    //更新订单日志
                    $data = ['order_id' => $val['order_id'], 'note' => '计划任务自动完成', 'status' => 40, 'addtime' => time()];
                    (new MallOrderLog())->addOne($data);
                    //自动好评
                    $orderDetails = (new MallOrderDetail())->getByOrderId('id as order_detail_id', $val['order_id']);
                    if (!empty($orderDetails)) {
                        foreach ($orderDetails as $vv) {
                            $replyDatas[] = [
                                'uid' => $val['uid'],
                                'reply_mv' => '',
                                'reply_pic' => '',
                                'order_id' => $val['order_id'],
                                'order_detail_id' => $vv['order_detail_id'],
                                'comment' => '此用户没有填写评价。',
                                'logistics_score' => 5,
                                'service_score' => 5,
                                'goods_score' => 5
                            ];
                        }
                    }
                    //分销员分享抽成到账(团购分销到账走merchantMoneyList->addMoney接口)
                    (new StoreMarketingRecordService())->doArrival([
                        'order_id' => $val['order_id'],
                        'goods_type' => 1
                    ]);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
            }

            //自动评价要求没那么严格放到最后执行，避免事务未完成导致读取的订单状态不一致
            if ($replyDatas) {
                $replyService = new GoodsReplyService();
                foreach ($replyDatas as $rd) {
                    $replyService->addGoodsComment($rd);
                }
            }

            return true;
        }
    }
}