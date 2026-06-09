<?php

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\ApprovalFlowService;

class ApprovalFlowController extends CommunityBaseController
{
    public function flowList()
    {
        try {
            $params = $this->request->post();
            $result = (new ApprovalFlowService())->flowList($this->adminUser, $params);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function flowDetail()
    {
        try {
            $id = $this->request->post('id', 0);
            $result = (new ApprovalFlowService())->flowDetail($this->adminUser, $id);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function flowSave()
    {
        try {
            $params = $this->request->post();
            $result = (new ApprovalFlowService())->flowSave($this->adminUser, $params);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function flowStatus()
    {
        try {
            $params = $this->request->post();
            $result = (new ApprovalFlowService())->flowStatus($this->adminUser, $params);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function businessTypeList()
    {
        try {
            $params = $this->request->post();
            $result = (new ApprovalFlowService())->businessTypeList($this->adminUser, $params);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function businessTypeOptions()
    {
        try {
            $result = (new ApprovalFlowService())->businessTypeOptions($this->adminUser);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function businessTypeSave()
    {
        try {
            $params = $this->request->post();
            $result = (new ApprovalFlowService())->businessTypeSave($this->adminUser, $params);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function businessTypeStatus()
    {
        try {
            $params = $this->request->post();
            $result = (new ApprovalFlowService())->businessTypeStatus($this->adminUser, $params);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function propertyWorkers()
    {
        try {
            $result = (new ApprovalFlowService())->propertyWorkers($this->adminUser);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function createInstance()
    {
        try {
            $params = $this->request->post();
            $result = (new ApprovalFlowService())->createInstance($this->adminUser, $params);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function instanceList()
    {
        try {
            $params = $this->request->post();
            $result = (new ApprovalFlowService())->instanceList($this->adminUser, $params);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function instanceDetail()
    {
        try {
            $id = $this->request->post('id', 0);
            $result = (new ApprovalFlowService())->instanceDetail($this->adminUser, $id);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function approvalAction()
    {
        try {
            $params = $this->request->post();
            $result = (new ApprovalFlowService())->approvalAction($this->adminUser, $params);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}
