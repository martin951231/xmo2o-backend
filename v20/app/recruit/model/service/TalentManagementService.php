<?php


namespace app\recruit\model\service;
use app\recruit\model\db\NewRecruitIndustry;
use app\recruit\model\db\NewRecruitJob;
use app\recruit\model\db\NewRecruitResume;
use app\recruit\model\db\NewRecruitResumeEducation;
use app\recruit\model\db\NewRecruitResumeLog;
use app\recruit\model\db\NewRecruitResumeProject;
use app\recruit\model\db\NewRecruitResumeSend;
use app\recruit\model\db\NewRecruitResumeWork;

class TalentManagementService
{
    public $education = [
        '0'=>'全部学历',
        '1' => '初中及以下',
        '2' => '中专/中技',
        '3' => '高中',
        '4' => '大专',
        '5' => '本科',
        '6' => '硕士',
        '7' => '博士',
    ];

    public $education_cate = [
        '1' => '统招',
        '2' => '委培',
        '3' => '自费',
        '4' => '成人高考',
        '5' => '电大',
    ];

    public $branch_number = [
        '1' => '1-10人',
        '2' => '11-20人',
        '3' => '21-50人',
        '4' => '51-100人',
        '5' => '100人以上',
    ];
//0新简历(人事未处理) 1沟通中 2面试待确认 3不合适 4 已到面 5未到面 6被拒绝
//1等待处理 2接受面试 3拒绝面试 4待归档（超时）
    public $deliver_status = [
        '0' => '未读',
        '1' => '沟通中',
        '2' => '约面试',
        '3' => '归档',
    ];
    //约面试状态
    public $inter_status=[
        '1'=>'等待处理',
        '2'=>'接受面试',
        '3'=>'拒绝面试',
        '4'=>'待归档'
    ];
    //归档状态
    public $filed_status=[
        '1'=>'已到面',
        '2'=>'未面试',
        '3'=>'不合适',
        '4'=>'被拒绝'
    ];
    /**
     * @param $mer_id
     * @param $page
     * @param $pagerSize
     * @return mixed
     * 列表
     */
    public function getList($where,$mer_id, $page, $pagerSize)
    {

        $field = "s.id as deliver_id,s.uid,s.position_id,r.id,r.name,r.age,r.birthday,r.sex,r.work_time,j.education,j.job_name,s.status,s.filed_remarks,s.add_time,s.inter_status,s.filed_status";
        $order = 's.add_time desc';
        $list = (new NewRecruitResumeSend())->getDeliveryByJobList($where, $field, $order, $page, $pagerSize);
        if(!empty($list['list'])){
            foreach ($list['list'] as $key=>$val){
                $list['list'][$key]['job_id']=$val['position_id'];
                $list['list'][$key]['education']= $val['education'] == 0 ? '' : $this->education[$val['education']]??"";
                $list['list'][$key]['sex']=$val['sex'] == 1 ? '男' : '女';
                if($val['status']<=1){
                    $list['list'][$key]['status']=$this->deliver_status[$val['status']]??"";
                }
                if($val['status']==2) {
                    $list['list'][$key]['status']=$this->inter_status[$val['inter_status']]??"";
                }
                if($val['status']==3) {
                    $list['list'][$key]['status']=$this->filed_status[$val['filed_status']]??"";
                }
                $list['list'][$key]['work_time']=$this->years(time(),$val['work_time'])."年";
                $list['list'][$key]['desc']=empty($val['filed_remarks'])?"无":$val['filed_remarks'];
                $list['list'][$key]['update_time']=date('Y-m-d H:i:s',$val['add_time']);
            }
        }
        $assign['list']=$list;
        $assign['education']=$this->education;
        $where = [['mer_id', '=', $mer_id]];
        $assign['job']=(new NewRecruitJob())->getSome($where,'job_id,job_name');
        $assign['job_age']=(new JobService())->job_age;
        $assign['deliver_status']=$this->deliver_status;
        return $assign;
    }

    /**
     * @param $mer_id
     * @param $page
     * @param $pagerSize
     * @return mixed
     * 平台后台列表
     */
    public function getPlatList($where, $page, $pagerSize)
    {
        $field = "s.id as deliver_id,s.uid,s.position_id,r.id,r.name,r.age,r.birthday,r.sex,r.work_time,j.education,j.job_name,s.status,s.filed_remarks,s.add_time,s.inter_status,filed_status";
        $order = 's.add_time desc';
        $list = (new NewRecruitResumeSend())->getDeliveryByJobList($where, $field, $order, $page, $pagerSize);
        if(!empty($list['list'])){
            foreach ($list['list'] as $key=>$val){
                $list['list'][$key]['job_id']=$val['position_id'];
                $education=(new NewRecruitResumeEducation())->getOne(['uid'=>$val['uid']]);
                if(!empty($education)){
                    $education=$education->toArray();
                    $list['list'][$key]['education']=$this->education[$education['education']];
                }else{
                    $list['list'][$key]['education']="";
                }

                $list['list'][$key]['sex']=$val['sex'] == 1 ? '男' : '女';
                if($val['status']<=1){
                    $list['list'][$key]['status']=$this->deliver_status[$val['status']]??"";
                }
                if($val['status']==2) {
                    $list['list'][$key]['status']=$this->inter_status[$val['inter_status']]??"";
                }
                if($val['status']==3) {
                    $list['list'][$key]['status']=$this->filed_status[$val['filed_status']]??"";
                }
                $work_time = $this->years(time(),$val['work_time']);
                if($work_time < 1){
                    $work_time = '应届生';
                }else{
                    $work_time = $work_time.'年';
                }
                $list['list'][$key]['work_time']=$work_time;
                $list['list'][$key]['desc']=empty($val['filed_remarks'])?"无":$val['filed_remarks'];
                $list['list'][$key]['update_time']=date('Y-m-d H:i:s',$val['add_time']);
                //年
                $years = "未知";
                if($val['birthday']<time() && $val['birthday']>0){
                    $remainder_seconds = abs(time() - $val['birthday']);
                    if ($remainder_seconds - 31536000 > 0) {
                        $years = intval($remainder_seconds / (31536000));
                    }
                }
                $list['list'][$key]['age']=$val['age']>0?$val['age']:$years;
            }
        }
        $assign['list']=$list;
        $assign['education']=$this->education;
        $assign['job']=(new NewRecruitJob())->getSome([['is_del','=', 0]],'job_id,job_name');
        $assign['job_age']=(new JobService())->job_age;
        $assign['deliver_status']=$this->deliver_status;
        return $assign;
    }

    public function years($endtime,$starttime){
        if(!empty($endtime) && !empty($starttime)){
            $timediff = $endtime-$starttime;
            $years = intval($timediff/(86400*365));
        }else{
            $years=0;
        }
        return $years;
    }
    /**
     * @param $id
     * @return mixed
     * 简历详情
     */
    public function getResumeMsg($id)
    {
        $where = [['s.id', '=', $id]];
        $field = "s.*,u.avatar,u.email";
        $list = (new NewRecruitResume())->getResumeMsg($where, $field);
        $out = [
            'update_time' => '',
            'base_msg' => [//基本信息
                'name' => '',
                'sex' => 0,
                'age' => 0,
                'work_time' => '',
                'phone' => '',
                'email' => '',
                'avatar' => '',
                'address' => '',
            ],
            'education_history' => [//教育经历
                [
                    'education_start_time' => '',
                    'education_end_time' => '',
                    'school_name' => '',
                    'education' => '',
                    'education_cate' => '',
                    'profession_name' => '',
                ],
            ],
            'work_history' => [//工作经历
                [
                    'job_start_time' => '',
                    'job_end_time' => '',
                    'job_name' => '',
                    'company_name' => '',
                    'ind_name' => '',//行业
                    'branch_number' => 0,//下属
                    'job_desc' => ''
                ]
            ],
            'project_history' => [//项目经历
                [
                    'project_name' => '',
                    'pro_start_time' => '',
                    'pro_end_time' => '',
                    'pro_desc' => '',
                ]
            ],
            'evaluate' => '',//自我评价
        ];
        if (!empty($list)) {
            if (!empty($list['update_time'])) {
                $out['update_time'] = date('Y-m-d H:i:s', $list['update_time']);
            } else {
                $out['update_time'] = "";
            }
            $work_time = $this->years(time(),$list['work_time']);
            if($work_time < 1){
                $work_time = '应届生';
            }else{
                $work_time = $work_time.'年';
            }
            //年
            $years = "未知";
            if($list['birthday']<time() && $list['birthday']>0){
                $remainder_seconds = abs(time() - $list['birthday']);
                if ($remainder_seconds - 31536000 > 0) {
                    $years = intval($remainder_seconds / (31536000));
                }
            }
            $out['base_msg'] = [//基本信息
                'name' => $list['name'],
                'sex' => $list['sex'] == 1 ? '男' : '女',
                'age' => $years,
                'work_time' => $work_time,
                'phone' => $list['phone'],
                'email' => $list['email'],
                'avatar' => empty($list['avatar']) ? '' : replace_file_domain($list['avatar']),
                'address' => '',
            ];
            $out['evaluate'] =$list['evaluate'];
            $where = [['uid', '=', $list['uid']]];
            $list1 = (new NewRecruitResumeEducation())->getSome($where, 'id,education_start_time,education_end_time,school_name,education,education_cate,profession_name','education_start_time desc')->toArray();
            if(!empty($list1)){
                foreach ($list1 as $key=>$val){
                    $list1[$key]['education']=$this->education[$val['education']]??"";
                    $list1[$key]['education_cate']=$this->education_cate[$val['education_cate']]??"";
                    if($val['education_end_time']){
                        $list1[$key]['education_time']=date("Y.m",$val['education_start_time']).'-'.date("Y.m",$val['education_end_time']);
                    }else{
                        $list1[$key]['education_time']=date("Y.m",$val['education_start_time']).'-至今';
                    }
                }
                $out['education_history'] = $list1;
            }else{
                $out['education_history']=[];
            }


            $list2=(new NewRecruitResumeWork())->getSome($where,'id,job_start_time,job_end_time,job_name,ind_id,company_name,branch_number,job_desc','job_start_time desc')->toArray();
            if(!empty($list2)){
                foreach ($list2 as $key1=>$val1){
                    $list2[$key1]['branch_number']=$this->branch_number[$val1['branch_number']]??"";
                    if($val1['job_end_time']){
                        $list2[$key1]['job_time']=date("Y.m",$val1['job_start_time']).'-'.date("Y.m",$val1['job_end_time']);
                    }else{
                        if($val1['job_start_time']){
                            $list2[$key1]['job_time']=date("Y.m",$val1['job_start_time']).'-至今';
                        }else{
                            $list2[$key1]['job_time']="";
                        }
                    }
                    if(!empty($val1['ind_id'])){
                        $hy=(new NewRecruitIndustry())->getOne(['id'=>$val1['ind_id']]);
                        if(!empty($hy)){
                            $hy=$hy->toArray();
                            $h_name1=$hy['name'];
                            if($hy['fid']){
                                $h_name2=(new NewRecruitIndustry())->getColVal('name',['id'=>$hy['fid']]);
                                $list2[$key1]['ind_id']=$h_name2.'|'.$h_name1;
                            }else{
                                $list2[$key1]['ind_id']=$h_name1;
                            }

                        }

                    }
                }
                $out['work_history'] = $list2;
            }else{
                $out['work_history']=[];
            }

            $list3=(new NewRecruitResumeProject())->getSome($where,'project_name,pro_start_time,pro_end_time,pro_desc','pro_start_time desc')->toArray();
            if(!empty($list3)) {
                foreach ($list3 as $key2=>$val2) {
                    if($val2['pro_end_time']){
                        $list3[$key2]['pro_time']=date("Y.m",$val2['pro_start_time']).'-'.date("Y.m",$val2['pro_end_time']);
                    }else{
                        $list3[$key2]['pro_time']=date("Y.m",$val2['pro_start_time']).'-至今';
                    }
                }
            }
            $out['project_history']=$list3;
        }
        return $out;
    }

    /**
     * @param $uid
     * @param $job_id
     * 历史记录
     */
    public function getLibMsg($resume_id,$send_id){
        $where=[['resume_id','=',$resume_id],['send_id','=',$send_id]];
        $list=(new NewRecruitResumeLog())->getSome($where,'id,name,remark,log_time','log_time desc')->toArray();
        if(!empty($list)){
            foreach ($list as $key=>$value){
                if($value['log_time']){
                    $list[$key]['update_time']=date('m-d H:i',$value['log_time']);
                }
            }
        }
        return $list;
    }
}