<?php
namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitResumeWork;
use think\Exception;

class RecruitResumeWorkService
{
    /**
     * 获取工作经历
     */
    public function recruitResumeWork($uid, $id, $fields='*'){
        if($id > 0){
            $where=[['id','=',$id], ['uid','=',$uid]];
            return (new NewRecruitResumeWork())->where($where)->find();
        }else{
            $where=[['uid','=',$uid]];
            return (new NewRecruitResumeWork())->where($where)->select();
        }
    }

    /**
     * 简历工作经历保存
     */
    public function recruitResumeWorkCreate($params, $id)
    {
        $params['update_time'] = time();
        if($id > 0){
            // 修改
            $where = ['id'=>$id];
            $return=(new NewRecruitResumeWork())->where($where)->update($params);
        }else{
            // 新增
            $data['create_time'] = $params['update_time'];
            $return=(new NewRecruitResumeWork())->add($params);
        }
        (new RecruitResumeService())->updateRecruitResumeTime($params['uid'] ?? 0);
        return $return;
    }

    /**
     * 简历工作经历删除
     */
    public function recruitResumeWorkDel($id)
    {
        $where = ['id'=>$id];
        $old = (new NewRecruitResumeWork())->where($where)->find();
        if (empty($old)) {
            throw new Exception(L_('工作经历不存在'));
        }
        $return=(new NewRecruitResumeWork())->where($where)->delete();
        (new RecruitResumeService())->updateRecruitResumeTime($old->uid);
        return $return;
    }

    /**
     * 获取工作经历列表
     */
    public function recruitResumeWorkList($uid){
        $where=[['g.uid','=',$uid]];
        $order = 'g.create_time DESC';
        $fields = 'g.*, b.cat_title as cat_name, c.name as ind_name';
        $return = (new NewRecruitResumeWork())->recruitResumeWorkList($where, $fields, $order);
        foreach($return as $k=>$v){
            if($v['branch_number'] > 5){
                $branch_number = '100人以上';
            }elseif($v['branch_number'] < 1){
                $branch_number = '1-10人';
            }else{
                $branch_number = $this->branch_numbers($v['branch_number']);
            }
            $return[$k]['branch_number'] = $branch_number;
        }
        return $return;
    }

    /**
     * 下属人数
     */
    public function branch_numbers($id)
    {
        $data = [
            1 => '1-10人',
            2 => '11-20人',
            3 => '21-50人',
            4 => '51-100人',
            5 => '100人以上',
        ];
        return $data[$id];
    }
}