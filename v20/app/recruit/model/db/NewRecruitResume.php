<?php
namespace app\recruit\model\db;

use think\Model;

class NewRecruitResume extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getSum($where,$field){
       return $this->where($where)->sum($field);
    }

    /**
     * 投递记录
     */
    public function getDeliveryByJobList($where,$field,$order,$page,$pageSize){
// 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('r')
            ->join($prefix . 'user u', 'u.uid = r.uid')
            ->leftJoin($prefix . 'new_recruit_resume_send s', 'r.uid = s.uid')
            ->leftJoin($prefix . 'new_recruit_job j', 's.position_id = j.job_id')
            ->leftJoin($prefix . 'new_recruit_company m', 'j.mer_id = m.mer_id')
            ->leftJoin($prefix . 'new_recruit_resume_invitation n', 'n.to_uid = s.uid')
            ->field($field)
            ->where($where)
            ->group('s.id');
        $assign['count']=$result->count();
        $assign['pageSize']=$pageSize;
        $assign['list']=$result->order($order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $assign;
    }

    /**
     * 找人才
     */
    public function RecruitJobPersonnelList($where, $order, $field){
        $result = $this->alias('g')
            ->where($where)
            ->field($field)
            ->leftJoin('new_recruit_job_intention a', 'a.id = g.int_id')
            ->leftJoin('new_recruit_resume_work f', 'f.id = g.work_id')
            ->leftJoin('new_recruit_resume_education b', 'b.uid = g.uid')
            ->leftJoin('user u', 'u.uid = g.uid')
            ->leftJoin('new_recruit_job_category e', 'e.cat_id = a.job_id')
            ->leftJoin('area c', 'c.area_id = a.area_id')
            ->leftJoin('area d', 'd.area_id = a.city_id');
        $assign = $result->order($order)
            ->select()
            ->toArray();
        return $assign;
    }

    /**
     * 简历详情
     */
    public function getResumeMsg($where,$field){
// 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix . 'user' . ' u', 'u.uid = s.uid')
            ->field($field)
            ->where($where)
            ->find();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }
}