<?php 

namespace app\life_tools\controller\storestaff;

use app\life_tools\model\service\LifeToolsAppointService;
use app\storestaff\controller\storestaff\AuthBaseController;
use app\life_tools\model\service\appoint\LifeToolsAppointJoinOrderService;

class LifeToolsAppointController extends AuthBaseController
{
 
    /**
     * 活动预约订单核销
     */
    public function verification()
    {
        $params = [];
        $params['staff_id'] = $this->staffId;
        $params['mer_id'] = $this->merId;
        $params['code'] = $this->request->post('code', '', 'trim');
        $params['staff'] = $this->staffUser;
        try {
            $data = (new LifeToolsAppointService())->verification($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        } 
        return api_output(0, $data);
        
    }
 
    /**
     * 核销列表
     */
    public function verifyList()
    {
        $params = [];
        $params['staff_id'] = $this->staffId;
        $params['mer_id'] = $this->merId;
        $params['from'] = 'v20';
        $params['page_size'] = $this->request->post('page_size', 10, 'trim');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['search_by'] = $this->request->post('search_by', 1, 'intval');
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        $params['verify_type'] = $this->request->post('verify_type', '', 'intval'); //状态：0=全部，1=未核销，2=已核销
        try {
            $data = (new LifeToolsAppointService())->verifyList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        } 
        return api_output(0, $data);
        
    }

    /**
     * 活动列表
     */
    public function getAppointList()
    {
        $params = [];
        $params['staff_id'] = $this->staffId;
        $params['mer_id'] = $this->merId;
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['status'] = $this->request->post('status', 0, 'intval');
        $params['page'] = $this->request->post('page', 1, 'intval');
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        try {
            $data = (new LifeToolsAppointService())->getAppointList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        } 
        return api_output(0, $data);
    }

    /**
     * 报名列表
     */
    public function getAppointOrderList()
    {
        $params = [];
        $params['staff_id'] = $this->staffId;
        $params['mer_id'] = $this->merId;
        $params['appoint_id'] = $this->request->post('appoint_id', 0, 'intval');
        $params['page'] = $this->request->post('page', 1, 'intval');
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['search_type'] = $this->request->post('search_type', 0, 'intval');
        try {
            $data = (new \app\life_tools\model\service\appoint\LifeToolsAppointService())->lookAppointUser($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        } 
        return api_output(0, $data);
    }


    /**
     * 退款
     */
    public function refund()
    {
        $params = [];
        $params['staff_id'] = $this->staffId;
        $params['mer_id'] = $this->merId;
        $params['pigcms_id'] = $this->request->post('order_id', 0, 'intval');
        $params['apply_refund_reason'] = $this->request->post('reason', '', 'trim');
        try {
            $LifeToolsAppointJoinOrderService = new LifeToolsAppointJoinOrderService();
            $LifeToolsAppointJoinOrderService->refundingOrder('life_tools_appoint', $params);
            $params['type'] = 1;
            $data = $LifeToolsAppointJoinOrderService->auditRefund($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        } 
        return api_output(0, $data);
    }
}
