<?php
namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitResumeProject;

class RecruitResumeProjectService
{
    /**
     * 获取项目经历
     */
    public function RecruitResumeProject($uid, $id, $fields='*'){
        if($id > 0){
            $where=[['id','=',$id], ['uid','=',$uid]];
            return (new NewRecruitResumeProject())->where($where)->find();
        }else{
            $where=[['uid','=',$uid]];
            return (new NewRecruitResumeProject())->where($where)->select()->toArray();
        }
    }

    /**
     * 简历项目经历保存
     */
    public function RecruitResumeProjectCreate($params, $id)
    {
        $params['update_time'] = time();
        if($id > 0){
            // 修改
            $where = ['id'=>$id];
            $return=(new NewRecruitResumeProject())->where($where)->update($params);
        }else{
            // 新增
            $data['create_time'] = $params['update_time'];
            $return=(new NewRecruitResumeProject())->add($params);
        }
        (new RecruitResumeService())->updateRecruitResumeTime($params['uid'] ?? 0);
        return $return;
    }

    /**
     * 简历项目经历删除
     */
    public function RecruitResumeProjectDel($id)
    {
        $where = ['id'=>$id];
        $old = (new NewRecruitResumeProject())->where($where)->find();
        if (empty($old)) {
            throw new Exception(L_('项目经历不存在'));
        }
        $return=(new NewRecruitResumeProject())->where($where)->delete();
        (new RecruitResumeService())->updateRecruitResumeTime($old->uid);
        return $return;
    }

    /**
     * 获取项目经历列表
     */
    public function recruitResumeProjectList($uid){
        $where=[['uid','=',$uid]];
        $list = (new NewRecruitResumeProject())->where($where)->select();
        return $list;
    }
}