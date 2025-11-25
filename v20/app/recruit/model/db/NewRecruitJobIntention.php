<?php


namespace app\recruit\model\db;


use think\Model;
use think\model\relation\BelongsTo;

class NewRecruitJobIntention extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getIntentionList($where,$field=true,$order=true,$page,$pageSize)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix . 'new_recruit_job_category' . ' m', 's.job_id = m.cat_id')
            ->field($field)
            ->where($where);
        $assign['count']=$result->count();
        $assign['list']=$result->order('s.create_time desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $assign;
    }

    public function recruitResumeIntentionList($where,$field=true,$order=true)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('g')
            ->join($prefix . 'new_recruit_job_category' . ' a', 'g.job_id = a.cat_id')
            ->field($field)
            ->where($where);
        $assign=$result->order($order)
            ->select()
            ->toArray();
        return $assign;
    }
}