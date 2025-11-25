<?php


namespace app\recruit\model\db;


use think\Model;

class NewRecruitInterviewNotice extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $name = 'new_recruit_resume_invitation';

    /**
     * 面试通知记录
     */
    public function interviewNoticeList($where,$field,$order,$page,$pageSize){
// 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix . 'new_recruit_job' . ' j', 's.position_id = j.job_id')
            ->join($prefix . 'new_recruit_company' . ' m', 'j.mer_id = m.mer_id')
            ->field($field)
            ->where($where);
        $assign['count']=$result->count();
        $assign['pageSize']=$pageSize;
        $assign['list']=$result->order($order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $assign;
    }
}