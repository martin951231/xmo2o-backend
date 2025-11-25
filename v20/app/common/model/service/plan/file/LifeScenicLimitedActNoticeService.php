<?php


namespace app\common\model\service\plan\file;

use app\common\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\life_tools\model\db\LifeScenicLimitedActNotice;
use app\life_tools\model\service\LifeToolsService;
use app\life_tools\model\service\LifeToolsTicketService;
use app\mall\model\service\SendTemplateMsgService;

class LifeScenicLimitedActNoticeService
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
        $infos = (new LifeScenicLimitedActNotice())->getSome($where, '*');
        if (!empty($infos)) {
            foreach ($infos as $val) {
                //提醒
                $ticket= (new LifeToolsTicketService())->getOne(['ticket_id'=>$val['ticket_id']]);
                $tools= (new LifeToolsService())->getOne(['tools_id'=>$ticket['tools_id']]);
                $user = (new UserService())->getUser($val['uid']);
                if($user['openid']){
                    $msgDataWx = [
                        'href' => get_base_url().'pages/lifeTools/tools/detail?id='.$tools['tools_id'],
                        'wecha_id' => $user['openid'],
                        'first' => L_('您预约的门票开始秒杀了！点击查看详情哦~'),
                        'keyword1' => $tools['title'].'-'.$ticket['title'],
                        'keyword2' => L_('秒杀开始'),
                        'keyword3' => date("Y-m-d H:i"),
                        'remark' => L_('点击查看详情'),
                    ];
                    
                    $res = (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
                }
                
                //更改状态
                (new LifeScenicLimitedActNotice())->updateThis(['uid' => $val['uid'], 'act_id' => $val['act_id']], ['push_status' => 1]);
            }
        }
    }
}