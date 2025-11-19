<?php


namespace app\common\model\service\plan\file;


use app\mall\model\db\MallNewGroupAct;
use app\mall\model\db\MallNewGroupOrder;
use app\mall\model\db\MallNewGroupTeam;
use app\mall\model\db\MallNewGroupTeamUser;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallRobotList;
use app\mall\model\service\activity\MallNewGroupTeamService;
use app\mall\model\service\MallOrderService;

class MallNewGroupRobotInTeamService
{
    /**
     * 计划任务执行
     * @param  string  $param 参数
     */

    public function runTask()
    {
        $where1[]=['s.is_open_group','=',1];
        $where1[]=['m.start_time','<',time()];
        $where1[]=['m.end_time','>=',time()];
        $where1[]=['t.status','=',0];
        $where1[]=['t.start_time','<',time()];
        $where1[]=['t.end_time','>=',time()];
        $where1[]=['m.type','=','group'];
        $list=(new MallNewGroupAct())->getGroupTeamMasg($where1,$field="*");
        if(empty($list)){//没有符合的团队
            exit;
        }
        else{
            (new MallNewGroupTeamService())->robotJoinTeam($list);
        }
        return true;
    }
}