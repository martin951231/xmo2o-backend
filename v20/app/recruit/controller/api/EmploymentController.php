<?php


namespace app\recruit\controller\api;


use app\recruit\model\service\EmploymentService;
use app\recruit\model\service\JobIntentionService;
use app\recruit\model\service\JobService;
use app\recruit\model\service\NewRecruitHrService;
use app\recruit\model\service\CompanyService;

class EmploymentController extends ApiBaseController
{
    /**
     * @return \json
     * 职位分类
     */
    public function getJobCategoryList()
    {
        /*$param['uid'] = $this->_uid;
        if ($param['uid']) {*/
        $list = (new JobIntentionService())->getJobList();
        return api_output(0, $list);
        /*} else {
            return api_output_error(1002, '请登录');
        }*/
    }


    /**
     * 职位性质
     *
     */
    public function getJobProperties()
    {
        $list = (new JobIntentionService())->getJobProperties();
        return api_output(0, $list);
    }

    /**
     * 工作区域选择列表
     *
     */
    public function getAreaList()
    {
        $list = (new EmploymentService())->getAreaList();
        return api_output(0, $list);
    }

    /**
     * @return \json
     * 学历选择
     */
    public function educationList()
    {
        $list = (new JobService())->education;
        return api_output(0, $list);
    }

    /**
     * @return \json
     * 工作时间
     */
    public function jobTime()
    {
        $list = (new EmploymentService())->job_time;
        return api_output(0, $list);
    }

    /**
     * @return \json
     * 工作年限选择
     */
    public function jobSelList()
    {
        $list['job_age'] = (new JobService())->job_age;
        $list['job_time'] = (new EmploymentService())->job_time;
        $list['education'] = (new JobService())->education;
        $list['properties'] = (new EmploymentService())->job_properties;
        return api_output(0, $list);
    }

    /**
     * 福利选择
     */
    public function welfareList()
    {
        if ($this->_uid) {
            $list = (new EmploymentService())->welfareList($this->_uid);
        } else {
            return api_output_error(1003, '请登录');
        }
        return api_output(0, $list);
    }

    /**
     * @return \json
     * 发布职位
     */
    public function publishJob()
    {
        $param['uid'] = $this->_uid;
        if (!$param['uid']) {
            return api_output_error(1002, '请登录');
        }
        else {
            $param['job_id'] = $this->request->param("job_id");
            $hr = (new NewRecruitHrService())->getRecruitHrOne($param['uid']);
            if (!empty($hr)) {
                $param['mer_id'] = $hr['mer_id'];
            } else {
                return api_output_error(1003, '用户状态不对，暂时无法发布职位');
            }

            $param['author'] = $param['uid'];
            $param['job_name'] = $this->request->param("job_name");
            if (!isset($param['job_name'])) {
                return api_output_error(1003, '缺少职位名称');
            }
            $param['first_cate'] = $this->request->param("first_cate");
            $param['second_cate'] = $this->request->param("second_cate");
            $param['third_cate'] = $this->request->param("third_cate");
            if (!isset($param['first_cate']) || !isset($param['second_cate']) || !isset($param['third_cate'])) {
                return api_output_error(1003, '缺少职位类别');
            }
            $param['wages'] = $this->request->param("wages");
            if (!isset($param['wages'])) {
                return api_output_error(1003, '缺少薪资');
            }
            if (!empty($param['wages'])) {
                $arr = explode(',', $param['wages']);
                $param['wages_start'] = $arr[0];
                $param['wages_end'] = $arr[1];
            }
            $param['area_id'] = $this->request->param("area_id")>0?$this->request->param("area_id", "", "intval"):0;
            if (!isset($param['area_id'])) {
                return api_output_error(1003, '缺少工作地区');
            }
            $param['type'] = $this->request->param("type", "", "intval");
            $param['address'] = $this->request->param("address");
            if (!isset($param['address'])) {
                return api_output_error(1003, '缺少详细地址');
            }
            $param['recruit_nums'] = $this->request->param("recruit_nums");
            if (!isset($param['recruit_nums'])) {
                return api_output_error(1003, '缺少招聘人数');
            }
            $param['education'] = $this->request->param("education", "", "intval");
            $param['job_age'] = $this->request->param("job_age", "", "intval");
            $param['age'] = $this->request->param("age", "", "trim");

            $param['desc'] = $this->request->param("desc");
            if (!isset($param['desc'])) {
                return api_output_error(1003, '缺少职位描述');
            }
            $param['fuli'] = $this->request->param("fuli", "", "trim");
            $param['end_time'] = $this->request->param("end_time");
            if (!isset($param['end_time'])) {
                return api_output_error(1003, '缺少职位时效');
            }
            unset($param['uid']);
            $param['add_type'] = $this->request->param("add_type");
            if(isset($param['job_id']) && !empty($param['job_id'])){
                if($param['add_type']==0){
                    $where=[['job_id','=',$param['job_id']]];
                    $arrs=(new EmploymentService)->getOne($where);
                    if(!empty($arrs)){
                        $s = $arrs['uptime'];
                        if ($arrs['end_time'] == 0) {
                            $d = strtotime("+1 month", $s);
                        } elseif ($arrs['end_time'] == 1) {
                            $d = strtotime("+3 month", $s);
                        } else{
                            $d = strtotime("+6 month", $s);
                        }
                        if(!($d>time() && $param['end_time']==$arrs['end_time'])){
                            $param['uptime'] = time();
                        }
                        if($arrs['add_type']==1){//预览转发布
                            // 公司在招职位增加
                           (new CompanyService())->getInfoInc($param['mer_id']);
                        }
                    }
                }
                $param['add_type'] != 1 && $param['status'] = 1;
                $param['update_time'] = time();
                $ret = (new EmploymentService)->updateJob($param);
                if ($ret!==false) {
                    return api_output(0, $param['job_id']);
                } else {
                    return api_output_error(1003, '失败');
                }
            }else{
                    $param['uptime'] = time();
                    $param['update_time'] = time();
                    $param['create_time'] = time();
                    $param['add_type'] != 1 && $param['status'] = 1;
                    if ($param['end_time'] == 0) {
                        $param['end_time'] = strtotime("+1 month", $param['uptime']);
                    } elseif ($param['end_time'] == 1) {
                        $param['end_time'] = strtotime("+3 month", $param['uptime']);
                    } else{
                        $param['end_time'] = strtotime("+6 month", $param['uptime']);
                    }
                    $ret = (new EmploymentService)->publishJob($param);
                    if ($ret) {
                        // 公司在招职位增加
                        if($param['add_type']==0) {
                            (new CompanyService())->getInfoInc($param['mer_id']);
                        }
                        return api_output(0, $ret);
                    } else {
                        return api_output_error(1003, '失败');
                    }

            }

        }
    }

    /**
     * 上线下线职位
     */
    public function pushJob()
    {
        $param['uid'] = $this->_uid;
        if (!$param['uid']) {
            return api_output_error(1002, '请登录');
        } else {
            $param['job_id'] = $this->request->param("job_id");
            if (!isset($param['job_id'])) {
                return api_output_error(1003, '缺少职位id');
            } else {
                $param['status'] = $this->request->param("status");
                if (!isset($param['status'])) {
                    return api_output_error(1003, '缺少状态');
                }
                $hr = (new NewRecruitHrService())->getRecruitHrOne($param['uid']);
                if (!empty($hr)) {
                    $param['mer_id'] = $hr['mer_id'];
                } else {
                    return api_output_error(1003, '用户状态不对，暂时无法上下线职位');
                }

                if ($param['status'] == 1) {
                    //$param['uptime'] = time();
                    $where=[['job_id','=',$param['job_id']]];
                    $arrs=(new EmploymentService)->getOne($where);
                    if(!empty($arrs)){
                        $s = $arrs['uptime'];
                        if ($arrs['end_time'] == 0) {
                            $d = strtotime("+1 month", $s);
                        } elseif ($arrs['end_time'] == 1) {
                            $d = strtotime("+3 month", $s);
                        } else{
                            $d = strtotime("+6 month", $s);
                        }

                        $param['status'] =1;
                        if(isset($param['end_time']) && !($d>time() && $param['end_time']==$arrs['end_time'])){
                            $param['uptime'] = time();
                        }

                        if(!isset($param['end_time'])){
                            $param['uptime'] = time();
                        }
                    }
                    // 公司在招职位增加
                    (new CompanyService())->getInfoInc($param['mer_id']);
                }
                if($param['status'] == 0){
                    // 公司在招职位减少
                    $com=(new CompanyService())->getCompanyInfo($param['mer_id']);
                    if($com['jobs']>0){
                        (new CompanyService())->getInfoDec($param['mer_id']);
                    }
                }
                
                unset($param['uid']);
                $ret = (new EmploymentService)->updateJob($param);
                if ($ret) {
                    return api_output(0, '发布成功');
                } else {
                    return api_output_error(1003, '发布失败');
                }
            }
        }
    }

    /**
     * 删除职位
     */
    public function delJob()
    {
        $param['uid'] = $this->_uid;
        if (!$param['uid']) {
            return api_output_error(1002, '请登录');
        } else {
            $param['job_id'] = $this->request->param("job_id");
            if (!isset($param['job_id'])) {
                return api_output_error(1003, '缺少职位id');
            } else {
                unset($param['uid']);
                $param['is_del'] = 1;
                $ret = (new EmploymentService)->updateJob($param);
                if ($ret) {
                    return api_output(0, '删除成功');
                } else {
                    return api_output_error(1003, '删除失败');
                }
            }
        }
    }

    /**
     * 编辑职位
     */
    public function getJobMsg()
    {
        $param['uid'] = $this->_uid;
        if (!$param['uid']) {
            return api_output_error(1002, '请登录');
        } else {
            $param['job_id'] = $this->request->param("job_id");
            if (!isset($param['job_id'])) {
                return api_output_error(1003, '缺少职位id');
            } else {
                $ret = (new EmploymentService())->getJobDetail($param['job_id']);
                if ($ret) {
                    return api_output(0, $ret);
                } else {
                    return api_output_error(1003, '获取失败');
                }
            }
        }
    }

    /**
     * @return \json
     * 编辑发布职位
     */
    public function updateJob()
    {
        $param['uid'] = $this->_uid;
        if (!$param['uid']) {
            return api_output_error(1002, '请登录');
        } else {
            $hr = (new NewRecruitHrService())->getRecruitHrOne($param['uid']);
            if (!empty($hr)) {
                $param['mer_id'] = $hr['mer_id'];
            } else {
                return api_output_error(1003, '用户状态不对，暂时无法发布职位');
            }
            $param['job_id'] = $this->request->param("job_id");
            if (!isset($param['job_id'])) {
                return api_output_error(1003, '缺少职位id');
            }
            $param['end_time'] = $this->request->param("end_time");
            if (!isset($param['end_time'])) {
                return api_output_error(1003, '缺少时效');
            }
            $param['job_name'] = $this->request->param("job_name");
            if (!isset($param['job_name'])) {
                return api_output_error(1003, '缺少职位名称');
            }
            $param['first_cate'] = $this->request->param("first_cate");
            $param['second_cate'] = $this->request->param("second_cate");
            $param['third_cate'] = $this->request->param("third_cate");
            if (!isset($param['first_cate']) || !isset($param['second_cate']) || !isset($param['third_cate'])) {
                return api_output_error(1003, '缺少职位类别');
            }
            $param['wages'] = $this->request->param("wages");
            if (!isset($param['wages'])) {
                return api_output_error(1003, '缺少薪资');
            }
            if (!isset($param['wages'])) {
                $arr = explode(',', $param['wages']);
                $param['wages_start'] = $arr[0];
                $param['wages_end'] = $arr[1];
            }
            $param['province_id'] = $this->request->param("province_id");
            $param['city_id'] = $this->request->param("city_id");
            $param['area_id'] = $this->request->param("area_id");
            if (!isset($param['province_id']) || !isset($param['city_id']) || !isset($param['area_id'])) {
                return api_output_error(1003, '缺少工作地区');
            }

            $param['address'] = $this->request->param("address");
            if (!isset($param['address'])) {
                return api_output_error(1003, '缺少详细地址');
            }
            $param['recruit_nums'] = $this->request->param("recruit_nums");
            if (!isset($param['recruit_nums'])) {
                return api_output_error(1003, '缺少招聘人数');
            }
            $param['education'] = $this->request->param("education", "", "intval");
            $param['job_age'] = $this->request->param("job_age", "", "intval");
            $param['age'] = $this->request->param("age", "", "trim");

            $param['desc'] = $this->request->param("desc");
            if (!isset($param['desc'])) {
                return api_output_error(1003, '缺少职位描述');
            }
            $param['fuli'] = $this->request->param("fuli");
            if (!isset($param['end_time'])) {
                return api_output_error(1003, '缺少职位时效');
            }
            $where=[['job_id','=',$param['job_id']]];
            $arrs=(new EmploymentService)->getOne($where);
            if(!empty($arrs)){
                $s = $arrs['uptime'];
                if ($arrs['end_time'] == 0) {
                    $d = strtotime("+1 month", $s);
                } elseif ($arrs['end_time'] == 1) {
                    $d = strtotime("+3 month", $s);
                } else{
                    $d = strtotime("+6 month", $s);
                }

                $param['status'] =1;
                if(!($d>time() && $param['end_time']==$arrs['end_time'])){
                    $param['uptime'] = time();
                }
            }
            unset($param['uid']);
            $ret = (new EmploymentService)->updateJob($param);
            if ($ret) {
                return api_output(0, '编辑成功');
            } else {
                return api_output_error(1003, '编辑失败');
            }
        }
    }

}