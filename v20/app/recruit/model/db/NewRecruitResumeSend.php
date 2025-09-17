<?php
namespace app\recruit\model\db;

use think\Model;

class NewRecruitResumeSend extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 候选人简历列表
     */
    public function recruitCandidateResumeList($where, $order, $field, $page, $pageSize){
        $result = $this->alias('g')
            ->leftJoin('new_recruit_resume a', 'a.id = g.resume_id')
            ->leftJoin('user b', 'b.uid = a.uid')
            ->leftJoin('new_recruit_job_intention c', 'c.id = a.int_id')
            ->leftJoin('area d', 'd.area_id = c.area_id')
            ->leftJoin('area e', 'e.area_id = c.city_id')
            ->where($where)
            ->field($field);
        $assign = $result->order($order)
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
            ->leftJoin('new_recruit_resume r','r.uid = u.uid')
            ->where($where)->field('u.uid,r.portrait,u.avatar');
        $list['count']= $result->count();
        $list['list']=$result
            ->order($order)
            ->select()
            ->toArray();
        foreach ($list['list'] as $k=>$v){
            $list['list'][$k]['avatar'] = replace_file_domain($v['portrait'] ?: $v['avatar']);
        }
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
            ->join($prefix . 'new_recruit_job j', 's.position_id = j.job_id')
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
    /**
     * 投递记录
     */
    public function deliveryList($where,$field,$order,$page,$pageSize){
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

    /**
     * 候选人简历数量
     */
    public function recruitCandidateResumeCount($where){
        $result = $this->alias('g')
            ->where($where)
            ->leftJoin('new_recruit_resume a', 'a.id = g.resume_id')
            ->leftJoin('user b', 'b.uid = a.uid')
            ->leftJoin('new_recruit_job_intention c', 'c.id = a.int_id')
            ->leftJoin('area d', 'd.area_id = c.area_id')
            ->leftJoin('area e', 'e.area_id = c.city_id');
        $assign = $result->Count();
        return $assign;
    }

    /**
     * 面试邀请保存
     */
    public function recruitResumeInvitationOperation($where, $data){
        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * 获取应聘职位列表
     */
    public function recruitCandidateResumeHrList($where){
        $result = $this->where($where)->select()->toArray();
        return $result;
    }
}