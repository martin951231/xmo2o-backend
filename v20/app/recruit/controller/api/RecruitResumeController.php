<?php
namespace app\recruit\controller\api;

use app\recruit\model\service\RecruitResumeService;
use app\recruit\model\service\JobIntentionService;
use app\recruit\model\service\RecruitResumeEducationService;
use app\recruit\model\service\RecruitResumeProjectService;
use app\recruit\model\service\RecruitResumeWorkService;
use app\recruit\model\service\RecruitJobCategoryService;
use app\recruit\model\service\JobService;
use app\recruit\model\service\NewRecruitHrService;
use app\recruit\model\service\RecruitIndustryService;
use app\common\model\service\AreaService;

class RecruitResumeController extends ApiBaseController
{
    /**
     * 简历基本信息
     */
    public function recruitResumeBasic()
    {
        $param['uid'] = $this->_uid;
        try {
            if (!$param['uid']) {
                return api_output_error(1002, L_('请登录'));
            } else {
                $where = [['uid', '=', $param['uid']]];
                $fields = 'id, uid, portrait, name, sex, phone, on_job, birthday, work_time';
                $list = (new RecruitResumeService())->recruitResumeBasic($where, $fields);
                if(empty($list)){
                    return api_output(0, []);
                }
                $list['birthday'] = $list['birthday'] ? date('Y-m',$list['birthday']) : '';
                $list['work_time'] = $list['work_time'] ? date('Y-m',$list['work_time']) : '';
                return api_output(0, $list);
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }

    /**
     * 简历基本信息保存
     */
    public function recruitResumeBasicCreate()
    {
        $id = $this->request->param("id", 0, "intval");
        $params['uid'] = $this->_uid;
        $params['name'] = $this->request->param("name", '', "trim");
        $params['sex'] = $this->request->param("sex", 0, "intval");
        $params['phone'] = $this->request->param("phone", '', "trim");
        $params['portrait'] = $this->request->param("portrait", '', "trim");
        $params['on_job'] = $this->request->param("on_job", '', "trim");
        $params['birthday'] = $this->request->param("birthday", '', "trim");
        $params['work_time'] = $this->request->param("work_time", '', "trim");
        $params['birthday'] = str_replace('.','-',$params['birthday']);
        $params['work_time'] = str_replace('.','-',$params['work_time']);
        $params['birthday'] = $params['birthday'] ? strtotime($params['birthday']) : '';
        $params['work_time'] = $params['work_time'] ? strtotime($params['work_time']) : '';
        try {
            if (!$params['uid']) {
                return api_output_error(1002, L_('请登录'));
            } else {
                $list = (new RecruitResumeService())->recruitResumeBasicCreate($params, $id);
                return api_output(0, $list);
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }

    /**
     * 简历自我评价
     */
    public function recruitResumeEvaluate()
    {
        $param['uid'] = $this->_uid;
        try {
            if (!$param['uid']) {
                return api_output_error(1002, L_('请登录'));
            } else {
                $where = [['uid', '=', $param['uid']]];
                $fields = 'id, uid, evaluate';
                $list = (new RecruitResumeService())->recruitResumeEvaluate($where, $fields);
                if(empty($list)){
                    return api_output(0, []);
                }
                return api_output(0, $list);
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }

    /**
     * 简历自我评价保存
     */
    public function recruitResumeEvaluateCreate()
    {
        $id = $this->request->param("id", 0, "intval");
        $params['uid'] = $this->_uid;
        $params['evaluate'] = $this->request->param("evaluate", '', "trim");
        try {
            if (!$params['uid']) {
                return api_output_error(1002, L_('请登录'));
            } else {
                $list = (new RecruitResumeService())->recruitResumeEvaluateCreate($params, $id);
                return api_output(0, $list);
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }

    /**
     * 简历求职意向
     */
    public function recruitResumeIntention()
    {
        $id = $this->request->param("id", 0, "intval");
        $uid = $this->_uid;
        if (!$uid) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $list = (new JobIntentionService())->recruitResumeIntention($uid, $id);
            if(empty($list)){
                return api_output(0, []);
            }
            foreach($list as $k=>$v){
                // 求职意向行业列表
                if($v['industry_ids']){
                    $industry_array = explode(',', $v['industry_ids']);
                    $industry_list = (new RecruitIndustryService())->resumeIntentionList($industry_array);
                    $list[$k]['industry_ids'] = $industry_list;
                }
                // 全职兼职
                if($v['job_properties']==1){
                    $job_properties = '全职';
                }elseif($v['job_properties']==2){
                    $job_properties = '兼职';
                }elseif($v['job_properties']==3){
                    $job_properties = '实习';
                }else{
                    $job_properties = '';
                }
                $list[$k]['job_properties_name'] = $job_properties;
                // 职位类型
                $posiont_list = '';
                if($v['job_id']){
                    $posiont_list = (new RecruitJobCategoryService())->resumePosiontList($v['job_id']);
                }
                $list[$k]['job_name'] = $posiont_list['cat_title'];
            }
            return api_output(0, $list);
        }
    }

    /**
     * 简历求职意向保存
     */
    public function recruitResumeIntentionCreate()
    {
        $id = $this->request->param("id", 0, "intval");
        $params['uid'] = $this->_uid;
        $params['job_id'] = $this->request->param("job_id", 0, "intval");
        $params['city_id'] = $this->request->param("city_id", 0, "intval");
        $params['industry_ids'] = $this->request->param("industry_ids", '', "trim");
        $params['area_id'] = $this->request->param("area_id", '', "trim");
        $params['circle_id'] = $this->request->param("circle_id", '', "trim");
        $params['salary'] = $this->request->param("salary", '', "trim");
        $params['job_properties'] = $this->request->param("job_properties", '', "trim");
        if (!$params['uid']) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $list = (new JobIntentionService())->recruitResumeIntentionCreate($params, $id);
            return api_output(0, $list);
        }
    }

    /**
     * 简历求职意向删除
     */
    public function recruitResumeIntentionDel()
    {
        $id = $this->request->param("id", 0, "intval");
        if (!$id) {
            return api_output_error(1002, L_('缺少参数'));
        } else {
            $list = (new JobIntentionService())->recruitResumeIntentionDel($id);
            return api_output(0, $list);
        }
    }

    /**
     * 简历教育经历
     */
    public function recruitResumeEducation()
    {
        $id = $this->request->param("id", 0, "intval");
        $uid = $this->_uid;
        if (!$uid) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $list = (new RecruitResumeEducationService())->recruitResumeEducation($uid,$id);
            if(empty($list)){
                return api_output(0, []);
            }
            $list['education_start_time'] = $list['education_start_time'] ? date('Y.m',$list['education_start_time']) : '';
            if($list['education_start_time']){
                $list['education_end_time'] = $list['education_end_time'] ? date('Y.m',$list['education_end_time']) : '至今';
            }else{
                $list['education_end_time'] = $list['education_end_time'] ? date('Y.m',$list['education_end_time']) : '';
            }
            return api_output(0, $list);
        }
    }

    /**
     * 简历教育经历保存
     */
    public function recruitResumeEducationCreate()
    {
        $id = $this->request->param("id", 0, "intval");
        $params['uid'] = $this->_uid;
        $params['school_name'] = $this->request->param("school_name", '', "trim");
        $params['education'] = $this->request->param("education", 0, "intval");
        $params['education_type'] = $this->request->param("education_type", 0, "intval");
        $params['education_start_time'] = $this->request->param("education_start_time", '', "trim");
        $params['education_end_time'] = $this->request->param("education_end_time", '', "trim");
        $params['profession_name'] = $this->request->param("profession_name", '', "trim");
        $params['education_cate'] = $this->request->param("education_cate", 0, "trim");
        $params['education_start_time'] = str_replace('.','-',$params['education_start_time']);
        $params['education_end_time'] = str_replace('.','-',$params['education_end_time']);
        $params['education_start_time'] =  $params['education_start_time'] ? strtotime($params['education_start_time']) : '';
        $params['education_end_time'] =  $params['education_end_time'] ? strtotime($params['education_end_time']) : '';
        if (!$params['uid']) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $list = (new RecruitResumeEducationService())->recruitResumeEducationCreate($params, $id);
            return api_output(0, $list);
        }
    }

    /**
     * 简历教育经历删除
     */
    public function recruitResumeEducationDel()
    {
        $id = $this->request->param("id", 0, "intval");
        if (!$id) {
            return api_output_error(1002, L_('缺少参数'));
        } else {
            $list = (new RecruitResumeEducationService())->recruitResumeEducationDel($id);
            return api_output(0, $list);
        }
    }

    /**
     * 简历工作经历
     */
    public function recruitResumeWork()
    {
        $id = $this->request->param("id", 0, "intval");
        $uid = $this->_uid;
        if (!$uid) {
            return api_output_error(1002, L_('请登录'));
        } else {
            if($id < 1){
                return api_output_error(1002, L_('缺少参数'));
            }
            $list = (new RecruitResumeWorkService())->recruitResumeWork($uid,$id);
            if(empty($list)){
                return api_output(0, []);
            }
            if($list){
                $list['job_start_time'] = $list['job_start_time'] ? date('Y.m',$list['job_start_time']) : '';
                if($list['job_start_time']){
                    $list['job_end_time'] = $list['job_end_time'] ? date('Y.m',$list['job_end_time']) : '至今';
                }else{
                    $list['job_end_time'] = $list['job_end_time'] ? date('Y.m',$list['job_end_time']) : '';
                }
                // 行业列表
                if($list['ind_id']){
                    $industry_array = explode(',', $list['ind_id']);
                    $industry_list = (new RecruitIndustryService())->resumeIntentionList($industry_array);
                    $list['industry_list'] = $industry_list;
                }
                // 职位类型
                $posiont_list = '';
                if($list['cat_id']){
                    $posiont_list = (new RecruitJobCategoryService())->resumePosiontList($list['cat_id']);
                }
                $list['cat_name'] = $posiont_list['cat_title'];
            }
            return api_output(0, $list);
        }
    }

    /**
     * 简历工作经历保存
     */
    public function recruitResumeWorkCreate()
    {
        $id = $this->request->param("id", 0, "intval");
        $params['uid'] = $this->_uid;
        $params['job_id'] = $this->request->param("job_id", 0, "intval");
        $params['job_name'] = $this->request->param("job_name", '', "trim");
        $params['cat_id'] = $this->request->param("cat_id", 0, "intval");
        $params['ind_id'] = $this->request->param("ind_id", 0, "intval");
        $params['company_name'] = $this->request->param("company_name", '', "trim");
        $params['job_start_time'] = $this->request->param("job_start_time", '', "trim");
        $params['job_end_time'] = $this->request->param("job_end_time", '', "trim");
        $params['branch_number'] = $this->request->param("branch_number", 0, "intval");
        $params['job_desc'] = $this->request->param("job_desc", '', "trim");
        $params['is_shield'] = $this->request->param("is_shield", 0, "trim");
        $params['job_start_time'] = str_replace('.','-',$params['job_start_time']);
        $params['job_end_time'] = str_replace('.','-',$params['job_end_time']);
        $params['job_start_time'] =  $params['job_start_time'] ? strtotime($params['job_start_time']) : '';
        $params['job_end_time'] =  $params['job_end_time'] ? strtotime($params['job_end_time']) : '';
        if (!$params['uid']) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $list = (new RecruitResumeWorkService())->recruitResumeWorkCreate($params, $id);
            return api_output(0, $list);
        }
    }

    /**
     * 简历工作经历删除
     */
    public function recruitResumeWorkDel()
    {
        $id = $this->request->param("id", 0, "intval");
        if (!$id) {
            return api_output_error(1002, L_('缺少参数'));
        } else {
            $list = (new RecruitResumeWorkService())->recruitResumeWorkDel($id);
            return api_output(0, $list);
        }
    }

    /**
     * 简历项目经历
     */
    public function recruitResumeProject()
    {
        $id = $this->request->param("id", 0, "intval");
        $uid = $this->_uid;
        if (!$uid) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $list = (new RecruitResumeProjectService())->recruitResumeProject($uid,$id);
            if(empty($list)){
                return api_output(0, []);
            }
            if($list){
                $list['pro_start_time'] = $list['pro_start_time'] ? date('Y.m',$list['pro_start_time']) : '';
                $list['pro_end_time'] = $list['pro_end_time'] ? date('Y.m',$list['pro_end_time']) : '至今';
            }
            return api_output(0, $list);
        }
    }

    /**
     * 简历项目经历保存
     */
    public function RecruitResumeProjectCreate()
    {
        $id = $this->request->param("id", 0, "intval");
        $params['uid'] = $this->_uid;
        $params['project_name'] = $this->request->param("project_name", '', "trim");
        $params['pro_start_time'] = $this->request->param("pro_start_time", '', "trim");
        $params['pro_end_time'] = $this->request->param("pro_end_time", '', "trim");
        $params['pro_desc'] = $this->request->param("pro_desc", '', "trim");
        $params['pro_start_time'] = str_replace('.','-',$params['pro_start_time']);
        $params['pro_end_time'] = str_replace('.','-',$params['pro_end_time']);
        $params['pro_start_time'] =  $params['pro_start_time'] ? strtotime($params['pro_start_time']) : '';
        $params['pro_end_time'] =  $params['pro_end_time'] ? strtotime($params['pro_end_time']) : '';
        if (!$params['uid']) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $list = (new RecruitResumeProjectService())->RecruitResumeProjectCreate($params, $id);
            return api_output(0, $list);
        }
    }

    /**
     * 简历项目经历删除
     */
    public function RecruitResumeProjectDel()
    {
        $id = $this->request->param("id", 0, "intval");
        if (!$id) {
            return api_output_error(1002, L_('缺少参数'));
        } else {
            $list = (new RecruitResumeProjectService())->RecruitResumeProjectDel($id);
            return api_output(0, $list);
        }
    }

    /**
     * 简历预览
     */
    public function recruitResumePreview()
    {
        $uid = $this->_uid;
        if (!$uid) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $return = (new RecruitResumeService())->recruitResumePreview($uid);
            if(!$return){
                return api_output(0, []);
            }
            // 时间
            $return['update_time'] = $return['update_time'] ? date('Y-m-d H:i:s',$return['update_time']) : '';
            // 性别
            if($return['sex']==1){
                $return['sex'] = '男';
            }else{
                $return['sex'] = '女';
            }
            // 年龄
            if($return['birthday']){
                $age = date('Y',time()) - date('Y',$return['birthday']);
                $age = $age.'岁';
            }else{
                $age = '未知';
            }
            $return['age'] = $age;
            // 在职状态
            $return['on_job'] = $this->on_jobs($return['on_job']);
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
            }else{
                $work_time = '无工作经验';
            }
            $return['work_time'] =  $work_time;
            $return['birthday'] =  $return['birthday'] ? date('Y-m',$return['birthday']) : '';
            // 求职意向
            $int_list = (new JobIntentionService())->recruitResumeIntentionList($uid);
            foreach($int_list as $k=>$v){
                // 求职意向行业列表
                if($v['industry_ids']){
                    $industry_array = explode(',', $v['industry_ids']);
                    $industry_list = (new RecruitIndustryService())->resumeIntentionList($industry_array);
                    $int_list[$k]['industry_ids'] = $industry_list;
                }
                // 商圈
                if ($v['area_id'] > 0) {
                    $area_txt = (new AreaService())->getAreaAndOneCircle($v['area_id'], 'area_id, area_name');
                    $int_list[$k]['area_txt'] = $area_txt['area_name'];
                } else {
                    $area_txt = (new AreaService())->getAreaAndOneCircle($v['city_id'], 'area_id, area_name');
                    $int_list[$k]['area_txt'] = '全' . $area_txt['area_name'];
                }

                // 全职兼职实习
                if($v['job_properties']==1){
                    $job_properties = ['id'=>1, 'name'=>'全职'];
                }elseif($v['job_properties']==2){
                    $job_properties = ['id'=>2, 'name'=>'兼职'];
                }elseif($v['job_properties']==3){
                    $job_properties = ['id'=>3, 'name'=>'实习'];
                }else{
                    $job_properties = ['id'=>0, 'name'=>'不限'];
                }
                $int_list[$k]['job_properties'] = $job_properties;
                // 期望薪资
                if($v['salary']){
                    $wagesArray = explode(',', $v['salary']);
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
                         $int_list[$k]['salary'] = $wages.'-'.$wages_end.'K';
                    }else{
                         $int_list[$k]['salary'] = '面议';
                    }
                }else{
                     $int_list[$k]['salary'] = '面议';
                }
            }
            $return['int_list'] = $int_list;
            // 教育经历
            $edu_list = (new RecruitResumeEducationService())->recruitResumeEducationList($uid);
            $return['max_education'] = $edu_list[0]['education']??'';
            foreach($edu_list as $k=>$v){
                $edu_list[$k]['education_start_time'] =  $v['education_start_time'] ? date('Y.m', $v['education_start_time']) : '';
                if($edu_list[$k]['education_start_time']){
                    $edu_list[$k]['education_end_time'] =  $v['education_end_time'] ? date('Y.m', $v['education_end_time']) : '至今';
                }else{
                    $edu_list[$k]['education_end_time'] =  $v['education_end_time'] ? date('Y.m', $v['education_end_time']) : '';
                }
            }
            $return['edu_list'] = $edu_list;
            // 工作经历
            $work_list = (new RecruitResumeWorkService())->recruitResumeWorkList($uid);
            foreach($work_list as $k=>$v){
                $work_list[$k]['job_start_time'] =  $v['job_start_time'] ? date('Y.m', $v['job_start_time']) : '';
                if($work_list[$k]['job_start_time']){
                    $work_list[$k]['job_end_time'] =  $v['job_end_time'] ? date('Y.m', $v['job_end_time']) : '至今';
                }else{
                    $work_list[$k]['job_end_time'] =  $v['job_end_time'] ? date('Y.m', $v['job_end_time']) : '';
                }
            }
            $return['work_list'] = $work_list;
            // 项目经历
            $pro_list = (new RecruitResumeProjectService())->recruitResumeProjectList($uid);
            foreach($pro_list as $k=>$v){
                $pro_list[$k]['pro_start_time'] =  $v['pro_start_time'] ? date('Y.m', $v['pro_start_time']) : '';
                if($pro_list[$k]['pro_start_time']){
                    $pro_list[$k]['pro_end_time'] =  $v['pro_end_time'] ? date('Y.m', $v['pro_end_time']) : '至今';
                }else{
                    $pro_list[$k]['pro_end_time'] =  $v['pro_end_time'] ? date('Y.m', $v['pro_end_time']) : '';
                }
            }
            $return['pro_list'] = $pro_list;
            return api_output(0, $return);
        }
    }

    /**
     * 全部职位
     */
    public function RecruitJobPersonnelAll()
    {
        
        $param['uid'] = $this->_uid;
        // $param['uid'] = 112358776;
        try {
            if (!$param['uid']) {
                return api_output_error(1002, L_('请登录'));
            } else {
                $mer = (new NewRecruitHrService())->getRecruitHrOneInfo($param['uid']);
                // 职位
                $all[] = array(
                    'job_id'=>'0',
                    'job_name'=>'全部职位'
                );
                $list = (new JobService())->recruitJobHrList(['mer_id' => $mer['mer_id'], 'author' => $param['uid'], 'add_type' => 0, 'status'=>1, 'is_del'=>0], 'job_id,job_name');
                $return = array_merge($all,$list);
                return api_output(0, $return);
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }

    /**
     * 找人才筛选条件
     */
    public function RecruitJobPersonnelScreen()
    {
        $param['uid'] = $this->_uid;
        //$params['area_name'] = $this->request->param("area_name", '北京', "trim");
        $nowCity = $this->request->param('now_city', 0, 'intval');
        // $param['uid'] = 112358755;
        try {
            if (!$param['uid']) {
                return api_output_error(1002, L_('请登录'));
            } else {
                // 职位
                $position_list = (new JobService())->recruitJobHrList(['author'=>$param['uid'],'status'=>1,'is_del'=>0,'add_type'=>0], 'job_id,job_name');
                $list['position_list'] = $position_list ? $position_list : [];
                // 年限
                $list['years_list'] = [['id'=>-1,'name'=>'不限'], ['id'=>0,'name'=>'应届毕业生'], ['id'=>1,'name'=>'1-3年'], ['id'=>2,'name'=>'3-5年'], ['id'=>3,'name'=>'5-10年'], ['id'=>4,'name'=>'10年级以上']];
                // 学历
                $list['education_list'] = [['id'=>1,'name'=>'初中及以下'], ['id'=>2,'name'=>'中专/中技'], ['id'=>3,'name'=>'高中'], ['id'=>4,'name'=>'大专'], ['id'=>5,'name'=>'本科'], ['id'=>6,'name'=>'硕士'], ['id'=>7,'name'=>'博士']];
                // 地区
                //$area_name = (new RecruitResumeService())->getNameCityId($params['area_name']);
                $area_list = [];
                if($nowCity){
                    $area_list = (new RecruitResumeService())->getAreaAndOneCircles($nowCity, 'area_id, area_pid, area_name');
                }
                $list['area_list'] = $area_list;

                return api_output(0, $list);
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }

    /**
     * 找人才
     */
    public function RecruitJobPersonnelList()
    {
        $params['position_id'] = $this->request->param("position_id", 0, "trim");
        $params['area_id'] = $this->request->param("area_id", 0, "trim");
        $params['age'] = $this->request->param("age", [], "trim");
        $params['salary'] = $this->request->param("salary", [], "trim");
        $params['years'] = $this->request->param("years", [], "trim");
        $params['education'] = $this->request->param("education", 0, "trim");
        $params['is_face'] = $this->request->param("is_face", 0, "trim");
        $params['search'] = $this->request->param("search", '', "trim");
        $page = $this->request->param("page", 1, "trim");
        $pageSize = $this->request->param("pageSize", 10, "trim");
        // 职位
        $list = (new RecruitResumeService())->RecruitJobPersonnelList($params, $page, $pageSize);
        return api_output(0, $list);
    }

    /**
     * 当前状态
     */
    public function on_jobs($id)
    {
        $data = [
            0 => '',
            1 => '离职，不考虑机会',
            2 => '离职，考虑机会',
            3 => '在职，不考虑机会',
            4 => '在职，考虑机会',
        ];
        return $data[$id];
    }
}