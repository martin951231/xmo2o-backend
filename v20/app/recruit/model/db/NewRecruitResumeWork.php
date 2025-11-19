<?php
namespace app\recruit\model\db;

use think\Model;

class NewRecruitResumeWork extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取工作经历列表
     */
    public function recruitResumeWorkList($where,$field=true,$order=true)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('g')
            // ->join($prefix . 'new_recruit_job' . ' a', 'g.job_id = a.job_id')
            ->join($prefix . 'new_recruit_job_category' . ' b', 'g.cat_id = b.cat_id')
            ->join($prefix . 'new_recruit_industry' . ' c', 'g.ind_id = c.id')
            ->field($field)
            ->where($where);
        $assign=$result->order($order)
            ->select()
            ->toArray();
        return $assign;
    }
}