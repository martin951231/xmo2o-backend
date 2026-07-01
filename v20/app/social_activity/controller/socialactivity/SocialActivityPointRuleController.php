<?php

namespace app\social_activity\controller\socialactivity;

use app\social_activity\controller\socialactivity\AuthBaseController as BaseController;
use app\social_activity\model\service\SocialActivityPointRuleService;
use app\social_activity\model\service\SocialActivityPointDetailService;
use app\social_activity\validate\SocialActivityPointRuleValidate;
use app\social_activity\validate\SocialActivityPointDetailValidate;

class SocialActivityPointRuleController extends BaseController
{
    protected $validateClass = SocialActivityPointRuleValidate::class;

    /**
     * 获取积分规则列表
     */
    public function getPointRuleList()
    {
        $param['page'] = $this->request->param('page', 1, 'int');
        $param['pageSize'] = $this->request->param('pageSize', 20, 'int');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['status'] = $this->request->param('status', '', 'trim');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');

        $pointRuleService = new SocialActivityPointRuleService();
        try {
            $data = $pointRuleService->getPointRuleList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }

    /**
     * 获取积分规则详情
     */
    public function getPointRuleDetail()
    {
        $param = $this->validateParameter([], 'detail');
        $pointRuleService = new SocialActivityPointRuleService();
        try {
            $data = $pointRuleService->getPointRuleDetail($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }

    /**
     * 保存积分规则（新增/编辑）
     */
    public function savePointRule()
    {
        $param['id'] = $this->request->param('id', 0, 'int');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['sign_point'] = $this->request->param('sign_point', 0, 'int');
        $param['participate_point'] = $this->request->param('participate_point', 0, 'int');
        $param['share_point'] = $this->request->param('share_point', 0, 'int');
        $param['pay_ratio'] = $this->request->param('pay_ratio', 0, 'float');
        $param['status'] = $this->request->param('status', 1, 'int');
        $scene = $param['id'] > 0 ? 'edit' : 'add';
        $param = $this->validateParameter($param, $scene);
        $pointRuleService = new SocialActivityPointRuleService();
        try {
            $pointRuleService->savePointRule($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        $msg = $param['id'] > 0 ? "编辑成功" : "新增成功";
        return api_output(0, [], $msg);
    }

    /**
     * 删除积分规则
     */
    public function deletePointRule()
    {
        $param = $this->validateParameter([], 'delete');
        $pointRuleService = new SocialActivityPointRuleService();
        try {
            $pointRuleService->deletePointRule($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "删除成功");
    }

    /**
     * 修改积分规则状态
     */
    public function changeStatus()
    {
        $param = $this->validateParameter([], 'status');
        $param['id'] = $this->request->param('id', 0, 'int');
        $pointRuleService = new SocialActivityPointRuleService();
        try {
            $pointRuleService->changeStatus($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "操作成功");
    }

    /**
     * 获取积分明细列表
     */
    public function getPointDetailList()
    {
        $param['page'] = $this->request->param('page', 1, 'int');
        $param['pageSize'] = $this->request->param('pageSize', 20, 'int');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['phone'] = $this->request->param('phone', '', 'trim');
        $param['activity_name'] = $this->request->param('activity_name', '', 'trim');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
                
        $pointDetailService = new SocialActivityPointDetailService();
        try {
            $data = $pointDetailService->getPointDetailList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }
}
