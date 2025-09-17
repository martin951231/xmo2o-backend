<?php
namespace app\recruit\model\db;

use think\Model;

class NewRecruitJobCollect extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 职位收藏列表
     */
    public function recruitJobCollectList($where, $order, $field, $page = 0, $pageSize = 20){
        $result = $this->alias('g')
            ->where($where)
            ->field($field)
            ->leftJoin('new_recruit_job a', 'a.job_id = g.job_id')
            ->leftJoin('area b', 'a.area_id = b.area_id')
            ->leftJoin('new_recruit_company c', 'a.mer_id = c.mer_id');
        if ($page > 0 && $pageSize > 0) {
            $result->page($page, $pageSize);
        }
        $assign = $result->order($order)
            ->select()
            ->toArray();
        return $assign;
    }

    public function getCollectCountByUid($uid)
    {
        return $this->alias('c')
            ->join('new_recruit_job j', 'c.job_id=j.job_id')
            ->where([['c.is_del', '=', 0], ['c.uid', '=', $uid]])
            ->count();
    }
}