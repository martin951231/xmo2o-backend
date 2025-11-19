<?php
namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitResumeLog;

class ResumeLogService
{
    /**
     * 操作记录保存
     */
    public function recruitResumeLogList($where, $fields)
    {
        $list = (new NewRecruitResumeLog())->recruitResumeLogList($where, $fields);
        foreach($list as $k=>$v){
            $list[$k]['log_time'] = date('m-d H:i',$v['log_time']);
        }
        return $list;
    }

    /**
     * 操作记录保存
     */
    public function recruitResumeLogAdd($logData)
    {
        return (new NewRecruitResumeLog())->recruitResumeLogAdd($logData);
    }
}