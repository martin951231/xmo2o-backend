<?php
namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitJob;
use app\recruit\model\db\NewRecruitResumeInvitation;
use app\recruit\model\db\NewRecruitResumeSend;
use app\recruit\model\db\NewRecruitHr;
use app\recruit\model\db\NewRecruitResume;
use app\recruit\model\db\NewRecruitCompany;
use app\recruit\model\db\NewRecruitInterviewNotice;
use app\common\model\db\Merchant;

class ResumeInvitationService
{
    /**
     * 面试邀请详情
     */
    public function recruitResumeInvitationDetails($id){
        $return = (new NewRecruitResumeInvitation())->where(['id'=>$id])->find();
        if(empty($return)){
            return [];
        }
        // 邀请时间
        $invitation_time = $return['invitation_time'];
        $return['invita_time'] = date('m-d H:i',$return['invitation_time']);
        $return['invitation_time'] = date('Y-m-d H:i',$return['invitation_time']);
        // 时间
        $return['add_time'] = date('Y-m-d H:i:s',$return['add_time']);
        // 薪资
        if($return['wages']){
            $wagesArray = explode(',', $return['wages']);
            if($wagesArray[0] && $wagesArray[1]){
                if((int)$wagesArray[0] >1000){
                    $isWages = (int)$wagesArray[0]%1000;
                    if($isWages > 0){
                        $wages = number_format((int)$wagesArray[0]/1000, 1);
                    }else{
                        $wages = number_format((int)$wagesArray[0]/1000, 0);
                    }
                }
                if((int)$wagesArray[1] >1000){
                    $isWages = (int)$wagesArray[1]%1000;
                    if($isWages > 0){
                        $wages_end = number_format((int)$wagesArray[1]/1000, 1);
                    }else{
                        $wages_end = number_format((int)$wagesArray[1]/1000, 0);
                    }
                }
                $return['wages'] = $wages.'-'.$wages_end.'K';
            }else{
                $return['wages'] = '面议';
            }
        }else{
            $return['wages'] = '面议';
        }
        // 状态
        if($return['status'] == 0){
            $status = '待接受';
        }elseif($return['status'] == 1){
            $status = '已拒绝';
        }elseif($return['status'] == 2){
            $status = '待面试';
        }elseif($return['status'] == 3){
            $status = '已超时';
        }elseif($return['status'] == 4){
            $status = '已结束';
        }
        // 公司信息
        $mer_id = (new NewRecruitHr())->where(['uid'=>$return['uid']])->field('uid,mer_id,first_name,last_name')->find();
        $company_find = (new NewRecruitCompany())->where(['mer_id'=>$mer_id['mer_id']])->find();
        $return['company_name'] = $company_find['name'];
        $return['company_nickname'] = $mer_id['first_name'].$mer_id['last_name'];

        $where=[['mer_id','=',$mer_id['mer_id']]];
        $com=(new Merchant())->getOne($where);
        if(!empty($com)){
            $com=$com->toArray();
            $return['company_images'] = empty($com['logo']) ? "" : replace_file_domain($com['logo']);
        }else{
            $return['company_images'] = "";
        }
        $return['long'] = $company_find['long'];
        $return['lat'] = $company_find['lat'];

        //  判断超时
        if($return['status'] == 0 || $return['status'] == 1){
            if($invitation_time < time()){
                // 超时操作
                $return['status'] = '3';
                $status = '已超时';
                // 更新邀请记录
                (new NewRecruitInterviewNotice())->where(['id'=>$return['id']])->update(['status'=>3]);
                // 更新投递记录
                if($return['send_id'] > 0){
                    $whereSend['id'] = $return['send_id'];
                }else{
                    $author = (new NewRecruitJob())->where(['job_id'=>$return['position_id']])->field('author')->find();
                    $whereSend = ['company_id'=>$return['company_id'],'position_id'=>$return['position_id'],'resume_id'=>$return['resume_id'],'uid'=>$author['author']];
                }
                (new NewRecruitResumeSend())->where($whereSend)->update(['inter_status'=>4]);
            }
        }elseif($return['status'] == 2){
            if($invitation_time < time()){
                // 超时操作
                $return['status'] = '4';
                $status = '已结束';
                // 更新邀请记录
                (new NewRecruitInterviewNotice())->where(['id'=>$return['id']])->update(['status'=>4]);
                // 更新投递记录
                if($return['send_id'] > 0){
                    $whereSend['id'] = $return['send_id'];
                }else{
                    $author = (new NewRecruitJob())->where(['job_id'=>$return['position_id']])->field('author')->find();
                    $whereSend = ['company_id'=>$return['company_id'],'position_id'=>$return['position_id'],'resume_id'=>$return['resume_id'],'uid'=>$author['author']];
                }
                (new NewRecruitResumeSend())->where($whereSend)->update(['inter_status'=>4]);
            }
        }
        $return['status_txt'] = $status;
        return $return;
    }

    /**
     * 面试邀请一条-列表
     */
    public function recruitResumeInvitationOneFind($resume_id, $to_uid, $position_id){
        $return = (new NewRecruitResumeInvitation())->where(['resume_id'=>$resume_id, 'to_uid'=>$to_uid, 'position_id'=>$position_id])->order('id DESC')->find();
        return $return;
    }

    /**
     * 面试邀请一条-详情
     */
    public function recruitResumeInvitationOneFindContent($resume_id, $position_id, $to_uid, $uid){
        $return = (new NewRecruitResumeInvitation())->where(['resume_id'=>$resume_id, 'position_id'=>$position_id, 'to_uid'=>$to_uid, 'uid'=>$uid])->order('id DESC')->find();
        return $return;
    }


    /**
     * 面试邀请保存
     */
    public function recruitResumeInvitationList($id, $params){
        if($params['resume_id'] < 1){
            $resume_id = (new NewRecruitResume())->where(['uid'=>$params['to_uid']])->field('id')->find();
            $params['resume_id'] = $resume_id['id'];
        }
        if($params['send_id']){
            $where = ['position_id'=>$params['position_id'], 'resume_id'=>$params['resume_id'], 'id'=>$params['send_id'], 'uid'=>$params['to_uid']];
        }else{
            $where = ['position_id'=>$params['position_id'], 'resume_id'=>$params['resume_id'], 'uid'=>$params['to_uid']];
        }
        if (empty($params['company_id'])) {
            $thisJob = (new NewRecruitJob())->getOne(['job_id' => $params['position_id']]);
            $thisJob && $params['company_id'] = $thisJob->mer_id;
        }
        $return = (new NewRecruitResumeInvitation())->recruitResumeInvitationList($id, $params);
        if($id < 1){
            // 更改状态-邀面试-等待确认
            (new NewRecruitResumeSend())->where($where)->update(['status'=>2,'inter_status'=>1]);
            // 写入操作记录
            $logData = [
                'resume_id'=>$params['resume_id'],
                'uid'=>$params['uid'],
                'send_id'=>$params['send_id'],
                'name'=>"邀请面试",
            ];
            $logData['log_time'] = $logData['add_time'] = time();
            (new ResumeLogService())->recruitResumeLogAdd($logData);
        }
        return $return;
    }

    /**
     * 面试地点
     */
    public function recruitResumeInvitationOne($uid){
        $return = (new NewRecruitResumeInvitation())->where(['uid'=>$uid])->order('id DESC')->find();
        return $return;
    }

    /**
     * 面试邀请
     */
    public function recruitResumeInvitations($where){
        $return = (new NewRecruitResumeInvitation())->where($where)->order('id DESC')->find();
        return $return;
    }
}