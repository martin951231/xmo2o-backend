<?php
/**
 * MallSeckillRemindService.php
 * 商城秒杀提醒计划任务
 * Create on 2021/1/26 9:27
 * Created by zhumengqun
 */

namespace app\common\model\service\plan\file;

use app\common\model\service\send_message\AppPushMsgService;
use app\mall\model\db\MallLimitedActNotice;
use app\mall\model\service\SendTemplateMsgService;

class MallSeckillRemindService
{
    /**
     * @param $order_id
     * 秒杀提醒计划任务
     */
    public function runTask()
    {
        $where = [
            ['push_status', '=', 0],
            ['uid', '>', 0],
            ['act_id', '>', 0],
            ['is_del', '=', 0],
            ['start_time', '<=', time() + 300]
        ];
        $infos = (new MallLimitedActNotice())->getSome($where, '*');
        if (!empty($infos)) {
            foreach ($infos as $val) {
                //提醒
                (new SendTemplateMsgService())->sendWxappMessage(['type' => 'seckill_remind', 'uid' => $val['uid'], 'goods_id' => $val['goods_id'], 'start_time' => $val['start_time']]);
                $info=array('uid'=>$val['uid'],'goods_id'=>$val['goods_id']);
                //(new AppPushMsgService())->send($info,'seckill_remind');
                //更改状态
                (new MallLimitedActNotice())->updateOne(['uid' => $val['uid'], 'act_id' => $val['act_id']], ['push_status' => 1]);
            }
        }
    }
}