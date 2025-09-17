<?php


namespace app\employee\controller\storestaff;


use app\employee\model\service\EmployeeCardUserService;
use app\storestaff\controller\storestaff\AuthBaseController;
use app\employee\model\service\EmployeeCardService;
use app\employee\model\service\EmployeeCardLogService;

class EmployeeCardOrderController extends AuthBaseController
{
    /**
     * 消费记录
     */
    public function orderList(){
        $params['user_id'] = $this->request->post('user_id', 0, 'intval');
        $params['card_id'] = $this->request->post('card_id', 0, 'intval');
        $params['mer_id'] =  $this->merId;
        $params['staff'] = $this->staffUser;
        $params['staff_id'] = $this->staffId;
        $params['page'] = $this->request->post('page', 1, 'intval');
        $params['pageSize'] = $this->request->post('pageSize', 10, 'intval');
        $params['start_time'] = $this->request->post('start_time', '', 'trim');
        $params['end_time'] = $this->request->post('end_time', '', 'trim');
        $params['type'] = $this->request->post('type', '', 'trim');
        try {
            $ret=(new EmployeeCardUserService())->orderList($params);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * pc端核销列表
     */
    public function cardLogList()
    {
        $params = [];
        $params['mer_id'] =$this->merId;
        $params['staff'] = $this->staffUser;
        $params['staff_id'] = $this->staffId;
        $params['page_size'] = $this->request->post('pageSize', 10, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['search_by'] = $this->request->post('search_by', 0, 'intval');
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        $params['type'] = $this->request->post('type', 'coupon', 'trim');
        try {
            $data = (new EmployeeCardService)->cardLogList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 员工卡消费券退款
     */
    public function employeeOrderRefund()
    {
        $params['mer_id'] = $this->merId;
        $params['staff_id'] = $this->staffId;
        $params['pigcms_id'] = $this->request->param('pigcms_id', 0, 'intval');
        $params['refund_remark'] = $this->request->param('refund_remark', '', 'trim');
        try {
            $result = (new EmployeeCardLogService())->employeeCouponRefund($params); 
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $result);
    }

    
}