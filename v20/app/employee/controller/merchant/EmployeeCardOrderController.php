<?php

namespace app\employee\controller\merchant;

use app\employee\model\service\EmployeeCardOrderService;
use app\merchant\controller\merchant\AuthBaseController;

class EmployeeCardOrderController extends AuthBaseController
{

    /**
     * 员工卡-充值记录-列表
     * @return \json
     */
    public function getEmployeeOrderList()
    {
        $param['mer_id']      = $this->merId;
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['start_time']  = $this->request->param('start_time', '', 'trim');
        $param['end_time']    = $this->request->param('end_time', '', 'trim');
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['pay_type']    = $this->request->param('pay_type', 'all', 'trim');
        $param['status']      = $this->request->param('status', 0, 'intval');
        try {
            $arr = (new EmployeeCardOrderService())->getEmployeeOrderList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 员工卡-充值记录-获取支付方式
     * @return \json
     */
    public function getPayType()
    {
        try {
            $arr = (new EmployeeCardOrderService())->getPayType();
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 员工卡-充值记录-列表导出
     * @return \json
     */
    public function getEmployeeOrderExport()
    {
        $param['mer_id']      = $this->merId;
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['start_time']  = $this->request->param('start_time', '', 'trim');
        $param['end_time']    = $this->request->param('end_time', '', 'trim');
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['pay_type']    = $this->request->param('type', '', 'trim');
        $param['status']      = $this->request->param('status', 0, 'intval');
        try {
            $arr = (new EmployeeCardOrderService())->getEmployeeOrderExport($param, [], $this->merchantUser);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 员工卡-充值记录-统计数据
     * @return \json
     */
    public function getEmployeeOrderStatistics()
    {
        $param['mer_id']     = $this->merId;
        $param['start_time'] = $this->request->param('begin_time', '', 'trim');
        $param['end_time']   = $this->request->param('end_time', '', 'trim');
        try {
            $arr = (new EmployeeCardOrderService())->getEmployeeOrderStatistics($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 员工卡-充值记录-统计数据导出
     * @return \json
     */
    public function getEmployeeOrderStatisticsExport()
    {
        $param['mer_id']      = $this->merId;
        $param['export_type'] = $this->request->param('export_type', 'day', 'trim');
        $param['start_time']  = $this->request->param('begin_time', '', 'trim');
        $param['end_time']    = $this->request->param('end_time', '', 'trim');
        try {
            $arr = (new EmployeeCardOrderService())->getEmployeeOrderStatisticsExport($param, [], $this->merchantUser);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 员工卡-充值记录-退款
     * @return \json
     */
    public function employeeOrderRefund()
    {
        $param['mer_id']   = $this->merId;
        $param['order_id'] = $this->request->param('order_id', 0, 'intval');
        if (empty($param['order_id'])) {
            return api_output_error(1003, '订单ID不存在');
        }
        try {
            $arr = (new EmployeeCardOrderService())->employeeOrderRefund($param, $this->merchantUser);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}