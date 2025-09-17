<?php
namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitResumeEducation;
use think\Exception;

class RecruitResumeEducationService
{
    /**
     * 获取教育经历
     */
    public function recruitResumeEducation($uid, $id, $fields='*'){
        if($id > 0){
            $where=[['id','=',$id], ['uid','=',$uid]];
            return (new NewRecruitResumeEducation())->where($where)->find();
        }else{
            $where=[['uid','=',$uid]];
            return (new NewRecruitResumeEducation())->where($where)->select();
        }
    }

    /**
     * 简历教育经历保存
     */
    public function recruitResumeEducationCreate($params, $id)
    {
        $params['update_time'] = time();
        if($id > 0){
            // 修改
            $where = ['id'=>$id];
            $return=(new NewRecruitResumeEducation())->where($where)->update($params);
        }else{
            // 新增
            $data['create_time'] = $params['update_time'];
            $return=(new NewRecruitResumeEducation())->add($params);
        }
        (new RecruitResumeService())->updateRecruitResumeTime($params['uid'] ?? 0);
        return $return;
    }

    /**
     * 简历教育经历删除
     */
    public function recruitResumeEducationDel($id)
    {
        $where = ['id'=>$id];
        $old = (new NewRecruitResumeEducation())->where($where)->find();
        if(empty($old)){
            throw new Exception(L_('教育经历不存在'));
        }
        $return=(new NewRecruitResumeEducation())->where($where)->delete();
        (new RecruitResumeService())->updateRecruitResumeTime($old->uid);
        return $return;
    }

    /**
     * 获取教育经历列表
     */
    public function recruitResumeEducationList($uid){
        $where=[['uid','=',$uid]];
        $list = (new NewRecruitResumeEducation())->where($where)->order('education_start_time desc')->select()->toArray();
        foreach($list as $k=>$v){
            $list[$k]['education'] = $this->educations($v['education']);
            $list[$k]['education_cate'] = $this->education_cates($v['education_cate']);
            if($v['education_type'] == 1){
                $list[$k]['education_type'] = '全日制';
            }else{
                $list[$k]['education_type'] = '非全日制';
            }
        }
       return $list;
    }

    /**
     * 学历
     */
    public function educations($id)
    {
        $data = [
            0 => '',
            1 => '初中及以下',
            2 => '中专/中技',
            3 => '高中',
            4 => '大专',
            5 => '本科',
            6 => '硕士',
            7 => '博士',
        ];
        return $data[$id];
    }

    /**
     * 学历类型
     */
    public function education_cates($id)
    {
        $data = [
            0 => '',
            1 => '统招',
            2 => '委培',
            3 => '自费',
            4 => '成人高考',
            5 => '电大',
        ];
        return $data[$id];
    }
}