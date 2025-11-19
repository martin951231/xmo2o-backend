<?php


namespace app\recruit\model\service;


use app\common\model\db\User;
use app\recruit\model\db\NewRecruitCompanyUserCollect;
use app\recruit\model\db\NewRecruitHr;
use app\recruit\model\db\NewRecruitInterviewNotice;
use app\recruit\model\db\NewRecruitJobCollect;
use app\recruit\model\db\NewRecruitJobDelivery;
use app\recruit\model\db\NewRecruitResume;
use app\recruit\model\db\NewRecruitResumeSend;

class JobPersonCenterService
{
//0离职 1在职,考虑机会 2在职,不考虑机会 3应届生
    public $job_status = [
        '0' => '离职,即可上岗',
        '1' => '在职,考虑机会',
        '2' => '在职,不考虑机会',
        '3' => '应届毕业生',
    ];
    /**
     * 个人中心
     */
    public function center($where, $uid = 0)
    {
        $out = [
            'name' => '',
            'job_status' => 0,
            'job_status_select' => $this->job_status,
            'image' => '',
            'list' => [
                [
                    'num' => 0,
                    'name' => '已投递',
                    'url'=>cfg('site_url').'/packapp/project_employment/pages/my/submitResumeRecord/index'
                ],
                [
                    'num' => 0,
                    'name' => '面试通知',
                    'url'=>cfg('site_url').'/packapp/project_employment/pages/my/interviewNoticeList/index'
                ],
                [
                    'num' => 0,
                    'name' => '已收藏',
                    'url'=>cfg('site_url').'/packapp/project_employment/pages/my/collectList/index'
                ]
            ],
            'view_num'=>0,
            'update_time'=>"",
        ];
        $user=(new User())->getOne($where);
        $resume=(new NewRecruitResume())->where($where)->field('uid,on_job')->find();
        $userResume = (new NewRecruitResume())->getOne(['uid' => $uid, 'is_del' => 0]);
        if(!empty($user)){
            $user=$user->toArray();
            if ($userResume) {
                $userResume->name && $user['nickname'] = $userResume->name;
                $userResume->portrait && $user['avatar'] = replace_file_domain($userResume->portrait);
            }
            $out['name']=$user['nickname'];
            $out['job_status']=$resume['on_job'];
            $out['image']=empty($user['avatar'])?cfg('site_url').'/static/avatar.jpg':replace_file_domain($user['avatar']);
            $notice_num = (new NewRecruitInterviewNotice())->getCount([[['to_uid', '=', $uid]]]);
            $out['list'][1]['num']=$notice_num;

            $count=(new NewRecruitResumeSend())->getCount($where);
            $out['list'][0]['num']=$count;

            $collect_num = (new NewRecruitJobCollect())->getCollectCountByUid($uid);
            $companyCollectNum = (new NewRecruitCompanyUserCollect())->getCount(['uid' => $uid]);
            $out['list'][2]['num'] = $collect_num + $companyCollectNum;
            $msg=(new NewRecruitResume())->getOne($where);
            if(!empty($msg)){
                $msg=$msg->toArray();
                $out['update_time']=date('m-d',$msg['update_time']);
                $out['view_num']=$msg['view_nums'];
            }
        }
       return $out;
    }

    /**
     * @param $where
     * @param $data
     * 用户职位状态切换更新
     */

    public function updateStatus($where,$data){
       return (new NewRecruitResume())->where($where)->update(['on_job'=>$data['job_status']]);
    }

    /**
     * 判断是不是hr，切换招聘者
     */
    public function resHr($where)
    {
        $ret=(new NewRecruitHr())->resHr($where);
        if(empty($ret)){
           $assign['status']=0;
        }else{
            $assign['status']=1;
        }
        return $assign;
    }
}