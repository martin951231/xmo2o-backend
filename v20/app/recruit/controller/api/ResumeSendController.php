<?php
namespace app\recruit\controller\api;

use app\recruit\model\service\RecruitJobDeliveryService;
use app\recruit\model\service\ResumeSendService;
use app\recruit\model\service\JobIntentionService;
use app\recruit\model\service\RecruitResumeEducationService;
use app\recruit\model\service\RecruitResumeProjectService;
use app\recruit\model\service\RecruitResumeWorkService;
use app\recruit\model\service\RecruitResumeService;
use app\recruit\model\service\ResumeInvitationService;
use app\recruit\model\service\ResumeLogService;
use app\recruit\model\service\NewRecruitHrService;
use app\recruit\model\service\JobService;
use app\recruit\model\service\CompanyService;
use app\recruit\model\service\RecruitIndustryService;
use app\common\model\service\AreaService;

class ResumeSendController extends ApiBaseController
{
    /**
     * 候选人简历列表
     */
    public function recruitCandidateResumeList(){
        $params['uid'] = $this->_uid;
        // $params['uid'] = 112358750;
        if ($params['uid'] < 1) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $params['position_id'] = $this->request->param("position_id", 0, "trim");
            $params['type'] = $this->request->param("type", 0, "trim");
            $page = $this->request->param("page", 1, "trim");
            $pageSize = $this->request->param("pageSize", 10, "trim");
            // 列表
            $list = (new ResumeSendService())->recruitCandidateResumeList($params, $page, $pageSize);
            return api_output(0, $list);
        }
    }

    /**
     * 简历详情
     */
    public function recruitResumeDetails()
    {
        $id = $this->request->param("id", 0, "trim");
        $position_id = $this->request->param("position_id", 0, "trim");
        $send_id = $this->request->param("send_id", 0, "trim");
        $uid = $this->_uid;
        // $uid = 112359164;
        if (!$uid) {
            return api_output_error(1002, L_('请登录'));
        } else {
            if($id < 1){
                return api_output_error(1003, L_('缺少参数'));
            }
            $return = (new RecruitResumeService())->recruitResumeDetails($id);
            if(!$return){
                return api_output(0, []);
            }
            // 时间
            $return['update_time'] = $return['update_time'] ? date('Y-m-d H:i:s',$return['update_time']) : '';
            // 性别
            $return['sex_id'] = $return['sex'];
            if($return['sex']==1){
                $return['sex'] = '男';
            }elseif($return['sex']==2){
                $return['sex'] = '女';
            }else{
                $return['sex'] = '未知';
            }
            // 年龄
            $age = date('Y',time()) - date('Y',$return['birthday']);
            $age = $age.'岁';
            $return['age'] = $return['birthday'] ? $age : '未知';
            // 面试时间
            $invitation_one = (new ResumeInvitationService())->recruitResumeInvitationOneFindContent($id, $position_id, $return['uid'], $uid);
            if($invitation_one){
                $invitation_time = $invitation_one['invitation_time'];
                $invitation_one['invitation_time']  = date('m-d H:i',$invitation_one['invitation_time']);
                $return['invitation_time'] = $invitation_one['invitation_time'];
            }else{
                $return['invitation_time'] = '';
                $invitation_time = 0;
            }
            $return['invitation_list'] = $invitation_one;
            $mer = (new NewRecruitHrService())->getRecruitHrOneInfo($uid);
            // 应聘职位列表
            if($position_id > 0){
                $myApplyJob = (new RecruitJobDeliveryService())->getMyJobDeliveryRecords($return['uid'],'position_id',[['status','<>',3],['inter_status','<',3]]);
                $myApplyJobIds = array_column($myApplyJob,'position_id');
                $jobSearchWhere = [
                    ['author', '=', $uid],
                    ['job_id', '=', $position_id],
                    ['status', '=', 1],
                    ['is_del', '=', 0],
                    ['add_type', '=', 0]
                ];
                $myApplyJobIds && $jobSearchWhere[]=['job_id','IN',$myApplyJobIds];
                $applied_position = (new JobService())->recruitJobHrList($jobSearchWhere, '*');
                foreach($applied_position as $k=>$v){
                    $applied_position[$k]['add_time'] = $v['uptime'] ? date('m-d H:i',$v['uptime']) : '';
                    $applied_position[$k]['id'] = $v['job_id'];
                    $applied_position[$k]['position_name'] = $v['job_name'];
                }
            }else{
                $applied_position = array();
            }
            $return['applied_position'] = $applied_position;
            // 状态
            $status_find = (new ResumeSendService())->recruitResumeSendFind($mer['mer_id'], $id, $position_id, $return['uid']);
            $status = ['id'=>-1, 'name'=>'无状态'];
            if($status_find){
                if($status_find['status']==0){
                    $status = ['id'=>0, 'name'=>'未读'];
                    $return['invitation_time'] = '';
                }elseif($status_find['status']==1){
                    $status = ['id'=>1, 'name'=>'沟通中'];
                    $return['invitation_time'] = '';
                }elseif($status_find['status']==2){
                    $status = ['id'=>2, 'name'=>'约面试'];
                }elseif($status_find['status']==3){
                    $status = ['id'=>3, 'name'=>'归档'];
                }
            }
            $return['status'] = $status;
            // 约面试状态
            if(!empty($invitation_time)){
                if($invitation_time < time()){
                    $status_find['inter_status'] = 4;
                }
            }
            $inter_status = ['id'=>-1, 'name'=>'无状态'];
            if($status_find){
                if($status_find['inter_status']==1){
                    $inter_status = ['id'=>1, 'name'=>'等待处理'];
                }elseif($status_find['inter_status']==2){
                    $inter_status = ['id'=>2, 'name'=>'接受面试'];
                }elseif($status_find['inter_status']==3){
                    $return['invitation_time'] = '';
                    $inter_status = ['id'=>3, 'name'=>'拒绝面试'];
                }elseif($status_find['inter_status']==4){
                    $return['invitation_time'] = '';
                    $inter_status = ['id'=>4, 'name'=>'待归档'];
                }
            }
            $return['inter_status'] = $inter_status;
            // 归档状态
            $filed_status = ['id'=>-1, 'name'=>'无状态'];
            if($status_find){
                $return['invitation_time'] = '';
                if($status_find['filed_status']==1){
                    $filed_status = ['id'=>1, 'name'=>'已到面'];
                }elseif($status_find['filed_status']==2){
                    $filed_status = ['id'=>2, 'name'=>'未面试'];
                }elseif($status_find['filed_status']==3){
                    $filed_status = ['id'=>3, 'name'=>'不合适'];
                }elseif($status_find['filed_status']==4){
                    $filed_status = ['id'=>4, 'name'=>'被拒绝'];
                }
            }
            $return['filed_status'] = $filed_status;
            // 工作年限
            if($return['work_time']){
                $time = time();
                $work_nian = date('Y',$time) - date('Y',$return['work_time']);
                $work_yue = date('m',$time) - date('m',$return['work_time']);
                $work_nian_yue = $work_nian * 12 + $work_yue;
                if($work_nian_yue < 12){
                    $work_time = '1年以内';
                }else{
                    if($work_yue > 6){
                        $work_time = ($work_nian + 1).'年';
                    }else{
                        $work_time = ($work_nian + 0.5).'年';
                    }
                }
                $is_work_time = 1;
            }else{
                $work_time = '无工作经验';
                $is_work_time = 0;
            }
            $return['is_work_time'] = $is_work_time;
            $return['work_time'] = $work_time;
            // 在职状态
            $return['on_job'] = $this->on_jobs($return['on_job']);
            // 邀请职位列表
            $return['job_position'] = (new JobService())->recruitJobList($mer['mer_id'], 'job_id,job_name');
            // 面试邀请页面面试地点
            $invitation_add = (new ResumeInvitationService())->recruitResumeInvitationOne($uid);
            if(!$invitation_add){
                $company = (new CompanyService())->recruitResumeCompanyOne($uid);
                $invitation_address = $company ? $company['address'] : '';
            }else{
                $invitation_address = $invitation_add['address'];
            }
            $return['invitation_address'] = $invitation_address;

            // 求职意向
            $int_list = (new JobIntentionService())->recruitResumeIntentionList($return['uid']);
            $area_city = '';
            $job_name = '';
            $salary = '';
            $salary_start = [];
            $salary_end = [];
            foreach($int_list as $k=>$v){
                // 求职意向行业列表
                if($v['industry_ids']){
                    $industry_array = explode(',', $v['industry_ids']);
                    $industry_list = (new RecruitIndustryService())->resumeIntentionList($industry_array);
                    $int_list[$k]['industry_ids'] = $industry_list;
                }
                // 期望地点
                $area_txt = (new AreaService())->getAreaAndOneCircle($v['area_id'],'area_id, area_name');
                $city_txt = (new AreaService())->getAreaAndOneCircle($v['city_id'],'area_id, area_name');
                if($k==0){
                    if(empty($area_txt['area_name'])){
                        $area_city = '全'.$city_txt['area_name'];
                    }else{
                        $area_city = $city_txt['area_name'].'-'.$area_txt['area_name'];
                    }
                }else{
                    if(empty($area_txt['area_name'])){
                        $area_city = $area_city.'|'.'全'.$city_txt['area_name'];
                    }else{
                        $area_city = $area_city.'|'.$city_txt['area_name'].'-'.$area_txt['area_name'];
                    }
                }
                // 期望岗位
                if($k==0){
                    $job_name = $v['job_name'];
                }else{
                    $job_name = $job_name.'|'.$v['job_name'];
                }
                // 期望薪资
                //$salary = $v['salary'];
                if (!empty($v['salary'])) {
                    $arr_wage = explode(',', $v['salary']);
                    $salary_start[] = $arr_wage[0];
                    $salary_end[] = $arr_wage[1];
                    $w['wages_start'] = intval($arr_wage[0]/1000);
                    $w['wages_end'] = isset($arr_wage[1])?intval($arr_wage[1]/1000):0;
                    if($w['wages_end'] && $w['wages_start']){
                        $salarys=$w['wages_start'].'-'.$w['wages_end']."K";
                    }else{
                        $salarys="面议";
                    }
                }else{
                    $salarys="面议";
                    $salary_all="面议";
                }
                if($salary){
                    $salary = $salary.'|'.$salarys;
                }else{
                    $salary = $salarys;
                }
            }
            // 期望薪资
            if($salary_start && $salary_end){
                $start = 999999999;
                foreach($salary_start as $v){
                    if($start > $v){
                        $start = $v;
                    }
                }
                $end = 0;
                foreach($salary_end as $v){
                    if($end < $v){
                        $end = $v;
                    }
                }
                $start = intval($start/1000);
                $end = isset($end)?intval($end/1000):0;
                if($end < 1){
                    $salary_all="面议";
                }else{
                    if($start < 1){
                        $salary_all="面议";
                    }else{
                        $salary_all = $start.'-'.$end.'K';
                    }
                }
            }else{
                $salary_all="面议";
            }
            $return['salary'] = $salary_all;
            $int_lists = array(
                'on_job' => $return['on_job'],
                'area_city' => $area_city,
                'job_name' => $job_name,
                'salary' => $salary
            );
            $return['int_list'] = $int_lists;
            // 教育经历
            $edu_list = (new RecruitResumeEducationService())->recruitResumeEducationList($return['uid']);
            $return['max_education'] = '';
            if($edu_list){
                $return['max_education'] = $edu_list[0]['education'];
                foreach($edu_list as $k=>$v){
                    $edu_list[$k]['education_start_time'] =  $v['education_start_time'] ? date('Y.m', $v['education_start_time']) : '';
                    if($v['education_start_time']){
                        $edu_list[$k]['education_end_time'] =  $v['education_end_time'] ? date('Y.m', $v['education_end_time']) : '至今';
                    }else{
                        $edu_list[$k]['education_end_time'] =  $v['education_end_time'] ? date('Y.m', $v['education_end_time']) : '';
                    }
                }
            }
            $return['edu_list'] = $edu_list;
            // 工作经历
            $work_list = (new RecruitResumeWorkService())->recruitResumeWorkList($return['uid']);
            foreach($work_list as $k=>$v){
                $work_list[$k]['job_start_time'] =  $v['job_start_time'] ? date('Y.m', $v['job_start_time']) : '';
                if($v['job_start_time']){
                    $work_list[$k]['job_end_time'] =  $v['job_end_time'] ? date('Y.m', $v['job_end_time']) : '至今';
                }else{
                    $work_list[$k]['job_end_time'] =  $v['job_end_time'] ? date('Y.m', $v['job_end_time']) : '';
                }
            }
            $return['work_list'] = $work_list;
            // 项目经历
            $pro_list = (new RecruitResumeProjectService())->recruitResumeProjectList($return['uid']);
            foreach($pro_list as $k=>$v){
                $pro_list[$k]['pro_start_time'] =  $v['pro_start_time'] ? date('Y.m', $v['pro_start_time']) : '';
                $pro_list[$k]['pro_end_time'] =  $v['pro_end_time'] ? date('Y.m', $v['pro_end_time']) : '';
            }
            $return['send_id'] = $send_id;
            $return['pro_list'] = $pro_list;
            // 记录浏览次数
            (new RecruitResumeService())->recruitResumeInc($id);
            // 写入操作记录
            if($send_id > 0){
                $logData = [
                    'resume_id'=>$return['id'],
                    'uid'=>$return['uid'],
                    'send_id'=>$send_id,
                    'name'=>"查看简历",
                ];
                $logData['log_time'] = $logData['add_time'] = time();
                (new ResumeLogService())->recruitResumeLogAdd($logData);
            }
            // 未读改为已读
            if($status_find){
                if($status_find['status']==0){
                    (new ResumeSendService())->recruitSendUpdate($mer['mer_id'], $id, $position_id, $return['uid'], 4);
                }
            }
            return api_output(0, $return);
        }
    }

    /**
     * 招聘者发起聊天更新简历状态
     */
    public function recruitResumeStatus()
    {
        $resume_id = $this->request->param("resume_id", 0, "trim");
        $position_id = $this->request->param("position_id", 0, "trim");
        if($resume_id < 1 || $position_id < 1){
            return api_output_error(1003, L_('缺少参数'));
        }
        // 未读、已读改为沟通中
        $return = (new ResumeSendService())->recruitResumeStatus($resume_id,$position_id);
        return api_output(0, $return);
    }

    /**
     * 当前状态
     */
    public function on_jobs($id)
    {
        $data = [
            0 => '离职，即刻上岗',
            1 => '在职，考虑机会',
            2 => '在职，不考虑机会',
            3 => '应届毕业生',
        ];
        return $data[$id];
    }
}