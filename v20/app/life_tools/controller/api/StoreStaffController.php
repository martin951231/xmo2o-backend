<?php
/**
 * 闸机接口控制器
 */

namespace app\life_tools\controller\api;

use app\employee\model\service\EmployeeCardLogService;
use app\employee\model\service\EmployeePayCodeService;
use app\life_tools\model\service\LifeToolsCardOrderService;
use app\life_tools\model\service\LifeToolsOrderDetailService;
use app\life_tools\model\service\LifeToolsService;
use app\life_tools\model\service\StoreStaffService;
use app\storestaff\controller\storestaff\AuthBaseController AS BaseStoreStaffController;
use app\storestaff\model\service\ScanService;

class StoreStaffController extends StaffBaseController
{

    /**
     * 登录
     */
    public function login()
    { 
        $params = [];
        $params['username'] = $this->request->post('username', '', 'trim');
        $params['password'] = $this->request->post('password', '', 'trim');
        try {
            $data = (new StoreStaffService)->login($params);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $data, '登录成功');
    }


    /**
     * 核销饭卡
     */
    public function cardConsume()
    {
        $this->checkLogin();
        $params = [];
        $params['device_id'] = $this->request->post('device_id', 0, 'trim,intval');
        $params['device_name'] = $this->request->post('device_name', '', 'trim');
        $params['operate_client'] = $this->request->post('operate_client', 0, 'trim,intval');
//        $params['tools_id'] = $this->request->post('tools_id', 0, 'trim,intval');
        $params['code'] = $this->request->post('code', '', 'trim');
        $params['remark'] = $this->request->post('remark', '', 'trim'); 
        $params['score'] = $this->request->post('score', 0, 'trim'); 
        $params['card_type'] = $this->request->post('card_type', 'coupon', 'trim'); 
        $params['operate_type'] = 'brake_machine';
        $params['mer_id'] =  $this->merId;
        $params['staff'] = $this->staffUser;
        $params['staff_id'] = $this->staffId;
        //支持自由支付
        $params['pay_sort'] = $this->request->post('pay_sort', 0, 'intval'); //支付顺序 0=先积分后余额，1=先余额后积分
        $params['money'] = $this->request->post('money', 0, 'trim'); 
        try {
            if (strlen($params['code']) == 12 || strlen($params['code']) == 13) { //场馆核销、景区次卡核销
                (new ScanService())->indexScan($params, $this->staffUser);
                $data = true;
            } else {
                $data = (new EmployeePayCodeService)->deductions($params);
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        if(!$data['status']){
            return api_output_error(3001, $data['title']??'核销失败');
        }
        return api_output(0, $data, '核销成功');
    }

    /**
     * 核销记录
     */
    public function consumeRecords()
    {    
        $this->checkLogin();
        $params = [];
        $params['mer_id'] =  $this->merId;
        $params['staff'] = $this->staffUser;
        $params['staff_id'] = $this->staffId; 

        $params['page_size'] = $this->request->post('page_size', 10, 'trim,intval');
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim'); 
        $params['kw_card_name'] = $this->request->post('kw_card_name', '', 'trim');
        $params['kw_user_name'] = $this->request->post('kw_user_name', '', 'trim');
        $params['kw_user_phone'] = $this->request->post('kw_user_phone', '', 'trim');
        $params['kw_coupon_name'] = $this->request->post('kw_coupon_name', '', 'trim');
        $params['type'] = $this->request->post('type', 'coupon', 'trim');
        try {
            $data = (new EmployeePayCodeService)->consumeRecords($params);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 仪表盘
     */
    public function dashboard()
    {
        $this->checkLogin();
        try {
            $data = (new EmployeePayCodeService)->dashboard($this->staffId);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 场馆/课程/景区核销记录
     */
    public function toolsRecords()
    {
        $this->checkLogin();
        $param = [];
        $param['staff']      = $this->staffUser;
        $param['staffId']    = $this->staffId;
        $param['type']       = 'all';
        $param['page']       = $this->request->post('page', 1, 'trim,intval');
        $param['pageSize']   = $this->request->post('page_size', 10, 'trim,intval');
        $param['begin_time'] = $this->request->post('start_date', '', 'trim');
        $param['end_time']   = $this->request->post('end_date', '', 'trim');
        $param['search_by']  = $this->request->post('search_by', 0, 'intval');
        $param['keywords']   = $this->request->post('keywords', '', 'trim');
        try {
            $data = (new LifeToolsOrderDetailService())->getVerifyList($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $data['data'] ?? []);
    }

    /**
     * 景区次卡核销记录
     */
    public function toolsCardRecords()
    {
        $this->checkLogin();
        $param = [];
        $param['staff']      = $this->staffUser;
        $param['staffId']    = $this->staffId;
        $param['type']       = 'scenic';
        $param['page']       = $this->request->post('page', 1, 'trim,intval');
        $param['pageSize']   = $this->request->post('page_size', 10, 'trim,intval');
        $param['begin_time'] = $this->request->post('start_date', '', 'trim');
        $param['end_time']   = $this->request->post('end_date', '', 'trim');
        try {
            $data = (new LifeToolsService())->getToolsCardRecord($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $data['data'] ?? []);
    }

    /**
     * 员工卡消费券退款
     */
    public function employeeCardConsumeRefund()
    {
        $params['mer_id'] = $this->merId;
        $params['staff_id'] = $this->staffId;
        $params['pigcms_id'] = $this->request->param('log_id', 0, 'intval');
        $params['refund_remark'] = $this->request->param('refund_remark', '', 'trim');
        try {
            $result = (new EmployeeCardLogService())->employeeCouponRefund($params); 
        } catch (\Exception $e) {
            return api_output_error(3001, $e->getMessage());
        }
        return api_output(0, $result);
    }

}