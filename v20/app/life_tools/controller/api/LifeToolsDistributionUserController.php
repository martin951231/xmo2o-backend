<?php


namespace app\life_tools\controller\api;

use app\life_tools\model\service\distribution\LifeToolsDistributionUserService;

class LifeToolsDistributionUserController extends ApiBaseController
{

    /**
     * 分销员申请表单信息
     */
    public function getCustomFormData()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['mer_id'] = $this->request->post('mer_id', 0, 'intval');
        $params['type'] = $this->request->post('type', 0, 'intval');
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval');
        try {
            $data = (new LifeToolsDistributionUserService())->getCustomFormData($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 提交审核
     */
    public function submitAudit()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['type'] = $this->request->post('type', 0, 'intval');
        $params['mer_id'] = $this->request->post('mer_id', 0, 'intval');
        $params['custom_form'] = $this->request->post('custom_form', '', 'trim');
        $params['invite_id'] = $this->request->post('invite_id', 0, 'intval');
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval');
        try {
            $data = (new LifeToolsDistributionUserService())->submitAudit($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 分销中心
     */
    public function distributionCenter()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        $params['is_cert'] = $this->request->post('is_cert', 1, 'intval');
        try {
            $data = (new LifeToolsDistributionUserService())->distributionCenter2($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 分享景区列表
     */
    public function shareScenicList()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['cat_id'] = $this->request->post('cat_id', 0, 'intval');
        try {
            $data = (new LifeToolsDistributionUserService())->shareScenicList($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 我的认证
     */
    public function myAuthentication()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        try {
            $data = (new LifeToolsDistributionUserService())->myAuthentication($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }
}