<?php


namespace app\recruit\controller\api;

use app\common\model\service\AreaService;
use app\recruit\model\service\JobIntentionService;

class JobIntentionController extends ApiBaseController
{
    /**
     * 个人求职意向列表
     */
    public function getJobIntentionList()
    {
        try {
            $param['uid'] = $this->_uid;
            //$param['uid'] = 112358755;
            $param['page'] = $this->request->param("page", "1", "intval");
            $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
            if ($param['uid']) {
                $where = [['s.uid', '=', $param['uid']]];
                $list = (new JobIntentionService())->getJobIntentionList($where, $param['page'], $param['pageSize'],1);
                return api_output(0, $list);
            } else {
                return api_output_error(1002, '', '请登录');
            }
        } catch (\Exception $e) {
           dd($e);
        }
    }

    /**
     * 个人求职意向
     */
    public function getJobIntention()
    {
        $param['uid'] = $this->_uid;
        $param['id'] = $this->request->param("id", "0", "intval");
        if ($param['uid']) {
            $where = [['s.uid', '=', $param['uid']], ['s.id', '=', $param['id']]];
            $list = (new JobIntentionService())->getJobIntentionList($where, 1, 10, 0);
            if (empty($list['list'])) {
                return api_output(0, []);
            } else {
                $my['list']=$list['list'][0];
                $my['other_info']=$list['other_info'];
                return api_output(0,$my);
            }
        } else {
            return api_output_error(1002, '请登录');
        }
    }

    /**
     * 岗位列表
     *
     */
    public function getJobList()
    {
        $list = (new JobIntentionService())->getJobList();
        return api_output(0, $list);
    }

    /**
     * 搜索职位分类名称
     *
     */
    public function searchJobCategory()
    {
        $param['name'] = $this->request->param("name", "", "trim");
        if (!empty($param['name'])) {
            $where = [['cat_title', 'like', '%' . $param['name'] . '%']];
            $list = (new JobIntentionService())->searchJobCategory($where);
            return api_output(0, $list);
        }
    }

    /**
     * 行业列表
     *
     */
    public function getIndustryList()
    {

        $list = (new JobIntentionService())->getIndustryList();
        return api_output(0, $list);
    }

    /**
     * 工作地点列表
     *
     */
    public function getAreaList()
    {
        $param['city_id'] = $this->request->param("city_id", "", "intval");
        $param['now_city'] = $this->request->param("now_city", "", "intval");
        if(!empty($param['city_id'])){
            $city=$param['city_id'];
        }else{
            $city=$param['now_city'];
        }
        // $list = (new JobIntentionService())->getAreaList($city);
        $list = (new AreaService)->getAllArea(0,'*','child');
        return api_output(0, $list);
    }

    /**
     * 工作性质
     *
     */
    public function getJobProperties()
    {
        $list = (new JobIntentionService())->getJobProperties();
        return api_output(0, $list);
    }

    /**
     * 添加
     */
    public function addJobIntention()
    {
        $param['uid'] = $this->_uid;
        $param['job_id'] = $this->request->param('job_id', '', 'intval');
        $param['industry_ids'] = $this->request->param('industry_ids', '', 'trim');//id,id
        $city=$this->request->param('city_id', '', 'intval');
        $param['city_id'] =$city>0?$city:$this->request->param('now_city');
        $area_id=$this->request->param('area_id');
        $circle_id=$this->request->param('circle_id');
        $param['province_id'] = $this->request->param('province_id', '0', 'intval');// 省份id
        $param['area_id'] = $area_id>0?$area_id:0;
        $param['circle_id'] =$circle_id>0?$circle_id:0;//id,id
        $param['salary'] = $this->request->param('salary', '', 'trim');
        $param['job_properties'] = $this->request->param('job_properties', '', 'intval');
        $param['create_time'] = time();
        if (empty($param['job_id'])) {
            return api_output_error(1003, '缺少岗位');
        }

        if (empty($param['area_id'])) {
            // return api_output_error(1003, '缺少区域');
        }

        if (empty($param['circle_id'])) {
            // return api_output_error(1003, '缺少商圈');
        }

        if (empty($param['salary'])) {
            return api_output_error(1003, '缺少工资');
        }

        if (empty($param['job_properties'])) {
            return api_output_error(1003, '缺少工作性质');
        }
        $ret = (new JobIntentionService())->addJobIntention($param);
        if($ret){
            $list['other_info']=$this->request->param('other_info', '', 'trim');
            return api_output(0, $list);
        }else{
            return api_output_error(1003, "新增失败");
        }

    }

    /**
     * 修改删除
     */
    public function updateJobIntention()
    {
        $param['uid'] = $this->_uid;
        $param['job_id'] = $this->request->param('job_id', '', 'intval');
        $param['id'] = $this->request->param('id', '', 'intval');
        $param['industry_ids'] = $this->request->param('industry_ids', '', 'trim');
        /*$param['city_id'] = $this->request->param('now_city');
        $param['area_id'] = $this->request->param('area_id', '', 'intval');
        $param['circle_id'] = $this->request->param('circle_id', '', 'trim');
        */
        $city=$this->request->param('city_id', '', 'intval');
        $param['city_id'] =$city>0?$city:$this->request->param('now_city');
        $area_id=$this->request->param('area_id');
        $circle_id=$this->request->param('circle_id');
        $param['area_id'] = $area_id>0?$area_id:0;
        $param['circle_id'] =$circle_id>0?$circle_id:0;//id,id

        $param['salary'] = $this->request->param('salary', '', 'trim');
        $param['job_properties'] = $this->request->param('job_properties', '', 'intval');
        $param['is_del'] = $this->request->param('is_del', '0', 'intval');
        $param['update_time'] = time();
        if (!$param['uid']) {
            return api_output_error(1002, '请登录');
        }
        if (empty($param['id'])) {
            return api_output_error(1003, '缺少必要参数');
        }
        $ret = (new JobIntentionService())->updateJobIntention($param);
        if($ret){
            $list['other_info']=$this->request->param('other_info', '', 'trim');
            return api_output(0, $list);
        }else{
            return api_output_error(1003, "更新失败");
        }
    }
}