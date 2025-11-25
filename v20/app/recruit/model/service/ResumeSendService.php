<?php
namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitResumeEducation;
use app\recruit\model\db\NewRecruitResumeSend;
use app\recruit\model\db\NewRecruitResumeLog;
use app\recruit\model\db\NewRecruitHr;
use app\recruit\model\db\NewRecruitJob;
use app\recruit\model\db\NewRecruitResumeInvitation;

class ResumeSendService
{
    /**
     * 候选人简历列表
     */
    public function recruitCandidateResumeList($params, $page, $pageSize){
        $where = [];
        // 当前HR发布的职位
        $job_list = (new NewRecruitJob())->where(['author'=> $params['uid'],'is_del'=>0,'status'=>1,'add_type'=>0])->select()->toArray();
        if(empty($job_list)){
            return [];
        }
        $job_ids = [];
        foreach($job_list as $v){
            $job_ids[] = $v['job_id'];
        }
        $where[] = ['g.position_id','in',$job_ids];
        if($params['type'] == 0){
            $where[] = ['g.status','in',[0,4]];
            $status = '未读';
        }elseif($params['type'] == 1){
            $where[] = ['g.status','=',1];
            $status = '沟通中';
        }elseif($params['type'] == 2){
            $where[] = ['g.status','=',2];
            $status = '约面试';
        }elseif($params['type'] == 3){
            $where[] = ['g.status','=',3];
            $status = '归档';
        }
        if($params['position_id'] > 0){
            $where[] = ['g.position_id','=',$params['position_id']];
        }
        $order = 'g.add_time desc';
        $fields = 'g.id as send_id, g.position_name, g.add_time, g.status, g.inter_status, g.filed_status, g.resume_id, g.position_id, g.uid, g.id as send_id, a.name, a.sex, a.work_time, a.birthday, a.portrait, b.user_logo, c.city_id, c.job_id, c.area_id, d.area_name, e.area_name as city_name,c.uid AS seekers_id';
        if($params['position_id'] > 0){
            // 未读数量
            $res['unread'] = (new NewRecruitResumeSend())->recruitCandidateResumeCount([['g.position_id','=',$params['position_id']],['g.status','in',[0,4]]]);
            // 沟通中数量
            $res['communicate'] = (new NewRecruitResumeSend())->recruitCandidateResumeCount([['g.position_id','=',$params['position_id']],['g.status','=',1]]);
            // 约面试数量
            $res['interview'] = (new NewRecruitResumeSend())->recruitCandidateResumeCount([['g.position_id','=',$params['position_id']],['g.status','=',2]]);
            // 归档数量
            $res['file'] = (new NewRecruitResumeSend())->recruitCandidateResumeCount([['g.position_id','=',$params['position_id']],['g.status','=',3]]);
        }else{
            // 未读数量
            $res['unread'] = (new NewRecruitResumeSend())->recruitCandidateResumeCount([['g.position_id','in',$job_ids],['g.status','in',[0,4]]]);
            // 沟通中数量
            $res['communicate'] = (new NewRecruitResumeSend())->recruitCandidateResumeCount([['g.position_id','in',$job_ids],['g.status','=',1]]);
            // 约面试数量
            $res['interview'] = (new NewRecruitResumeSend())->recruitCandidateResumeCount([['g.position_id','in',$job_ids],['g.status','=',2]]);
            // 归档数量
            $res['file'] = (new NewRecruitResumeSend())->recruitCandidateResumeCount([['g.position_id','in',$job_ids],['g.status','=',3]]);
        }
        $list = (new NewRecruitResumeSend())->recruitCandidateResumeList($where, $order, $fields, $page, $pageSize);
        $educationMod = new NewRecruitResumeEducation();
        foreach($list as $k=>$v){
            $education = $educationMod->where('uid', $v['seekers_id'])->order('education', 'desc')->findOrEmpty()->toArray();
            $list[$k]['education'] = $education ? $educationMod->getEducation($education['education']) : '';
            $list[$k]['city_name'] = empty($v['city_name']) ? '' : $v['city_name'];
            $list[$k]['area_name'] = empty($v['area_name']) ? '' : $v['area_name'];
            $list[$k]['area_city'] = $v['city_name'].$v['area_name'];
            if($v['status'] == 0){
                $status = '未读';
            }elseif($v['status'] == 1){
                $status = '沟通中';
            }elseif($v['status'] == 2){
                $status = '约面试';
            }elseif($v['status'] == 3){
                $status = '归档';
            }else{
                $status = '已读';
            }
            $list[$k]['status_txt'] = $status;
            $invitation_one = (new ResumeInvitationService())->recruitResumeInvitationOneFind($v['resume_id'], $v['uid'], $v['position_id']);
            // 约面试状态
            $inter_status = '无状态';
            $list[$k]['invitation_time'] = '';
            if($invitation_one){
                // 面试时间
                $invitation_times = strtotime(date('Y-m-d',time()).' 23:59:59');
                if($invitation_one['invitation_time'] < $invitation_times){
                    $invitation_time = '今天'.date(' H:i',$invitation_one['invitation_time']);
                }elseif($invitation_one['invitation_time'] < ($invitation_times + 86400)){
                    $invitation_time = '明天'.date(' H:i',$invitation_one['invitation_time']);
                }else{
                    $invitation_time = date('m-d H:i',$invitation_one['invitation_time']);
                }
                $list[$k]['invitation_time'] = $invitation_one['invitation_time'] ? $invitation_time : '';
                if($v['inter_status']==1){
                    $inter_status = '待确认';
                }elseif($v['inter_status']==2){
                    $inter_status = '接受面试';
                }elseif($v['inter_status']==3){
                    $inter_status = '拒绝面试';
                }elseif($v['inter_status']==4){
                    $inter_status = '待归档';
                }
                // 判断超时
                if($v['inter_status']==1 ||$v['inter_status']==2){
                    if($invitation_one['invitation_time'] && $invitation_one['invitation_time'] < time()){
                        // 改变状态为待归档
                        $list[$k]['inter_status'] = 4;
                        $inter_status = '待归档';
                        (new NewRecruitResumeSend())->where(['id'=>$v['send_id']])->update(['inter_status'=>4]);
                    }
                }
            }
            $list[$k]['inter_status_txt'] = $inter_status;
            // 归档状态
            $filed_status = '无状态';
            if($v['filed_status']==1){
                $filed_status = '已到面';
            }elseif($v['filed_status']==2){
                $filed_status = '未面试';
            }elseif($v['filed_status']==3){
                $filed_status = '不合适';
            }elseif($v['filed_status']==4){
                $filed_status = '被拒绝';
            }
            $list[$k]['filed_status_txt'] = $filed_status;

            /*if($v['sex']==1){
                $sex = '男';
            }elseif($v['sex'] ==2){
                $sex = '女';
            }
            $list[$k]['sex'] = $sex;*/
            if($list[$k]['portrait']){
                $list[$k]['user_logo'] = replace_file_domain($v['portrait']);
            }else{
                $list[$k]['user_logo'] = replace_file_domain('/static/avatar.jpg');
            }
            // 年龄
            if($v['birthday']){
                $age = date('Y',time()) - date('Y',$v['birthday']);
                $age = $age.'岁';
            }else{
                $age = '未知';
            }
            $list[$k]['age'] = $age;

            // 工作年限
            if($v['work_time']){
                $work_time = date('Y',time()) - date('Y',$v['work_time']) + 1;
                if($work_time == 1){
                    $work_time = $work_time.'年以内';
                }else{
                    $work_time = $work_time.'年';
                }
            }else{
                $work_time = '无经验';
            }
            $list[$k]['work_time'] = $work_time;

            $add_times = strtotime(date('Y-m-d',time()).' 00:00:00');
            if($v['add_time'] > $add_times){
                $add_time = '今天';
            }elseif($v['add_time'] > ($add_times - 86400)){
                $add_time = '昨天';
            }else{
                $add_time = date('m-d',$v['add_time']);
            }
            $list[$k]['add_time'] = $add_time;
        }
        $res['list'] = $list;
        return $res;
    }

    /**
     * 面试邀请操作
     */
    public function recruitResumeInvitationOperation($params){
        // 公司ID
        $company = (new NewRecruitHr())->where(['uid'=> $params['uid']])->find();
        if($company){
            $company_id = $company['mer_id'];
        }else{
            $company_id = $params['company_id'];
        }
        $where = ['resume_id'=>$params['resume_id'], 'company_id'=>$company_id, 'position_id'=>$params['position_id']];
        $data = ['filed_status'=>$params['filed_status'], 'filed_remarks'=>$params['filed_remarks'], 'status'=>3];
        $return = (new NewRecruitResumeSend())->recruitResumeInvitationOperation($where, $data);
        // 改变简历状态
        $data2 = ['status' => 3];
        if($params['filed_status'] == 1){
            $data2['filed_status'] = 1;
        }elseif($params['filed_status'] == 2){
            $data2['filed_status'] = 2;
        }elseif($params['filed_status'] == 3){
            $data2['filed_status'] = 3;
        }
        (new NewRecruitResumeSend())->where($where)->update($data2);
        // 写入操作记录
        if($params['filed_status'] == 1){
            $filed_status = "标记'已到面'";
        }elseif($params['filed_status'] == 2){
            $filed_status = "标记'未面试'";
        }elseif($params['filed_status'] == 3){
            $filed_status = "标记'不合适'";
        }
        $logData = [
            'resume_id'=>$params['resume_id'],
            'uid'=>$params['uid'],
            'send_id'=>$params['send_id'],
            'name'=>$filed_status,
            'remark'=>$params['filed_remarks'],
        ];
        $logData['log_time'] = $logData['add_time'] = time();
        (new NewRecruitResumeLog())->recruitResumeLogAdd($logData);
        return $return;
    }

    /**
     * 面试拒绝、接受操作
     */
    public function recruitResumeInvitationRefuse($params){
        // 公司ID
        $company = (new NewRecruitHr())->where(['uid'=> $params['hr_uid']])->find();
        if($company){
            $company_id = $company['mer_id'];
        }else{
            $company_id = $params['company_id'];
        }
        $where = ['resume_id'=>$params['resume_id'], 'company_id'=>$company_id, 'position_id'=>$params['position_id'], 'uid'=>$params['hr_uid']];
        $data = ['status'=>$params['status']];
        $return = (new NewRecruitResumeInvitation())->where($where)->update($data);
        if($params['send_id'] == 0){
            $where2 = array(
                'resume_id'=>$params['resume_id'],
                'company_id'=>$company_id,
                'position_id'=>$params['position_id'],
                'uid'=>$params['uid']
            );
        }else{
            $where2 = ['id'=>$params['send_id']];
        }
        $data2 = ['status' => 2];
        if($params['status'] == 1){
            $data2['status'] = 3;
            $data2['filed_status'] = 4;
        }elseif($params['status'] == 2){
            $data2['inter_status'] = 2;
        }
        (new NewRecruitResumeSend())->where($where2)->update($data2);
        // 写入操作记录
        if($params['status'] == 0){
            $status = "待接受";
        }elseif($params['status'] == 1){
            $status = "已拒绝";
        }elseif($params['status'] == 2){
            $status = "待面试";
        }elseif($params['status'] == 3){
            $status = "已超时";
        }elseif($params['status'] == 4){
            $status = "已结束";
        }
        $logData = [
            'resume_id'=>$params['resume_id'],
            'uid'=>$params['hr_uid'],
            'send_id'=>$params['send_id'],
            'name'=>$status,
        ];
        $logData['log_time'] = $logData['add_time'] = time();
        (new NewRecruitResumeLog())->recruitResumeLogAdd($logData);
        return $return;
    }

    /**
     * 获取应聘职位列表
     */
    public function recruitCandidateResumeHrList($company_id, $uid){
        $where = ['company_id'=>$company_id, 'uid'=>$uid];
        $return = (new NewRecruitResumeSend())->recruitCandidateResumeHrList($where);
        return $return;
    }

    /**
     * 更改简历状态
     */
    public function recruitSendUpdate($mer_id, $id, $position_id, $uid, $status){
        $where = ['company_id'=>$mer_id, 'resume_id'=>$id, 'position_id'=>$position_id, 'uid'=>$uid];
        $count = (new NewRecruitResumeSend())->where($where)->count();
        if($count > 0){
            $return = (new NewRecruitResumeSend())->where($where)->update(['status'=>$status]);
        }else{
            $return = [];
        }
        return $return;
    }

    /**
     * 招聘者发起聊天更新简历状态
     */
    public function recruitResumeStatus($resume_id,$position_id){
        $where = ['resume_id'=>$resume_id,'position_id'=>$position_id];
        $send_find = (new NewRecruitResumeSend())->where($where)->find();
        if(!empty($send_find)){
            if($send_find['status'] == 0 || $send_find['status'] == 4){
                $return = (new NewRecruitResumeSend())->where($where)->update(['status'=>1]);
            }else{
                $return = [];
            }
        }else{
            $return = [];
        }
        return $return;
    }

    /**
     * 获取状态
     */
    public function recruitResumeSendFind($mer_id, $id, $position_id, $uid){
        $where = ['company_id'=>$mer_id, 'resume_id'=>$id,  'position_id'=>$position_id, 'uid'=>$uid];
        $count = (new NewRecruitResumeSend())->where($where)->count();
        if($count > 0){
            $return = (new NewRecruitResumeSend())->where($where)->find();
        }else{
            $return = [];
        }
        return $return;
    }

    /**
     * 聊天邀请面试添加申请面试
     */
    public function recruitInvitationSend($data){
        $return = (new NewRecruitResumeSend())->add($data);
        return $return;
    }
}