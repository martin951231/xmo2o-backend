<?php


namespace app\recruit\model\service;


use app\common\model\db\Area;
use app\common\model\db\Merchant;
use app\recruit\model\db\NewRecruitInterviewNotice;
use app\recruit\model\db\NewRecruitResumeLog;
use app\recruit\model\db\NewRecruitResumeSend;
use app\recruit\model\db\NewRecruitJob;

class RecruitInterviewNoticeService
{
    public $interview_status = [
        '0' => '待接受',
        '1' => '已拒绝',
        '2' => '待面试',
        '3' => '已超时',
        '4' => '已结束',
    ];
     public function interviewNoticeList($uid, $page, $pageSize){
         $where = [['s.to_uid', '=', $uid]];
         $field = 's.id,s.resume_id,s.company_id,s.position_id,s.send_id,s.invitation_time AS update_time,s.invitation_time,s.position_id AS job_id,j.job_name,j.job_age,j.age,j.wages,m.name,s.status AS interview_status,m.mer_id';
         $order = 's.invitation_time desc';
         $list=(new NewRecruitInterviewNotice())->interviewNoticeList($where,$field,$order,$page,$pageSize);
         $msg=array();
         if(!empty($list['list'])){
             foreach ($list['list'] as $k => $v) {
                 if (empty($v['wages'])) {
                     $list['list'][$k]['wages'] = L_('面议');
                 } else {
                     $arr = explode(',', $v['wages']);
                     $wage_s = intval($arr[0] / 1000);
                     $wage_e = intval($arr[1] / 1000) . 'K';
                     if(empty($arr[1])){
                         $list['list'][$k]['wages'] = L_('面议');
                     }else{
                         $list['list'][$k]['wages'] = $wage_s . '-' . $wage_e;
                     }
                 }
                 $list['list'][$k]['interview_status_txt']=$this->interview_status[$v['interview_status']];
                //  判断超时
                if($v['interview_status'] == 0 || $v['interview_status'] == 1){
                    if($v['invitation_time'] < time()){
                        // 超时操作
                        $list['list'][$k]['interview_status'] = '3';
                        $list['list'][$k]['interview_status_txt'] = '已超时';
                        // 更新邀请记录
                        (new NewRecruitInterviewNotice())->where(['id'=>$v['id']])->update(['status'=>3]);
                        // 更新投递记录
                        if($v['send_id'] > 0){
                            $whereSend['id'] = $v['send_id'];
                        }else{
                            $author = (new NewRecruitJob())->where(['job_id'=>$v['position_id']])->field('author')->find();
                            $whereSend = ['company_id'=>$v['company_id'],'position_id'=>$v['position_id'],'resume_id'=>$v['resume_id'],'uid'=>$author['author']];
                        }
                        (new NewRecruitResumeSend())->where($whereSend)->update(['inter_status'=>4]);
                    }
                }elseif($v['interview_status'] == 2){
                    if($v['invitation_time'] < time()){
                        // 超时操作
                        $list['list'][$k]['interview_status'] = '4';
                        $list['list'][$k]['interview_status_txt'] = '已结束';
                        // 更新邀请记录
                        (new NewRecruitInterviewNotice())->where(['id'=>$v['id']])->update(['status'=>4]);
                        // 更新投递记录
                        if($v['send_id'] > 0){
                            $whereSend['id'] = $v['send_id'];
                        }else{
                            $author = (new NewRecruitJob())->where(['job_id'=>$v['position_id']])->field('author')->find();
                            $whereSend = ['company_id'=>$v['company_id'],'position_id'=>$v['position_id'],'resume_id'=>$v['resume_id'],'uid'=>$author['author']];
                        }
                        (new NewRecruitResumeSend())->where($whereSend)->update(['inter_status'=>4]);
                    }
                }

                 if(!empty($v['update_time'])){
                     $list['list'][$k]['date']=date('m-d',$v['update_time']);
                     $list['list'][$k]['time']=date('H:i',$v['update_time']);
                 }else{
                     $list['list'][$k]['date'] =date('m-d',time());
                     $list['list'][$k]['time']=date('H:i',time());
                 }

                 $list['list'][$k]['job_age'] = (new JobService())->job_age[$v['job_age']];
                 if(empty($v['mer_id'])){
                     $list['list'][$k]['name'] = "";
                     $list['list'][$k]['logo'] = "";
                 }else{
                     $where=[['mer_id','=',$v['mer_id']]];
                     $com=(new Merchant())->getOne($where);
                     if(!empty($com)){
                         $com=$com->toArray();
                         $list['list'][$k]['logo'] = empty($com['logo'])?"":replace_file_domain($com['logo']);
                     }
                 }
             }

             $group_list=array();
             foreach ($list['list'] as $k1 => $v1){
                 $group_list[$v1['date']][]=$v1;
             }
             if(!empty($group_list)){
                foreach ($group_list as $k2=>$v2){
                    $item['date']=$k2;
                    $item['data']=$v2;
                    $msg[]=$item;
                }
             }
         }
         $assign['list']=$msg;
         $assign['pageSize']=$list['pageSize'];
         $assign['count']=$list['count'];
         return $assign;
     }
}