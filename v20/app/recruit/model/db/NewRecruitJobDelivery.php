<?php


namespace app\recruit\model\db;


use think\Model;

class NewRecruitJobDelivery extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 投递记录
     */
    public function deliveryList($where,$field,$order,$page,$pageSize){
// 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix . 'new_recruit_job' . ' j', 's.job_id = j.job_id')
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


    /**
     * 职位投递者
     */
    public function getDeliverers($where,$field,$order){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix.'user u', 'u.uid = s.uid')
            ->where($where)->field('u.uid,u.avatar');
        $list['count']= $result->count();
        $list['list']=$result
            ->order($order)
            ->select()
            ->toArray();
        return $list;
    }

    /**
     * 投递记录
     */
    public function getDeliveryByJobList($where,$field,$order,$page,$pageSize){
// 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix . 'user u', 'u.uid = s.uid')
            ->join($prefix . 'new_recruit_job j', 's.job_id = j.job_id')
            ->join($prefix . 'new_recruit_company m', 'j.mer_id = m.mer_id')
            ->join($prefix . 'new_recruit_resume r', 'r.uid = s.uid')
            ->leftJoin($prefix . 'new_recruit_resume_invitation n', 'n.to_uid = s.uid')
            ->field($field)
            ->where($where)
            ->group('deliver_id');
        $assign['count']=$result->count();
        $assign['pageSize']=$pageSize;
        $assign['list']=$result->order($order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $assign;
    }
}