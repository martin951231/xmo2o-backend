<?php


namespace app\common\model\service\plan\file;


use app\life_tools\model\db\LifeScenicLimitedActNotice;
use app\mall\model\service\SendTemplateMsgService;

class LifeToolsAppointOverdueService
{
    /**
     * @param $order_id
     * 活动过期核销/退款计划任务
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
        $infos = (new LifeScenicLimitedActNotice())->getSome($where, '*');
        if (!empty($infos)) {
            foreach ($infos as $val) {
                //提醒
                    (new SendTemplateMsgService())->sendWxappMessage(['type' => 'seckill_scenic_remind', 'uid' => $val['uid'], 'ticket_id' => $val['ticket_id'], 'start_time' => $val['start_time']]);
                    //更改状态
                    (new LifeScenicLimitedActNotice())->updateThis(['uid' => $val['uid'], 'act_id' => $val['act_id']], ['push_status' => 1]);
            }
        }
    }
}