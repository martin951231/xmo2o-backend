<?php


namespace app\employee\controller\merchant;

use app\employee\model\service\EmployeeCardLogService;
use app\employee\model\service\EmployeeCardService;
use app\employee\model\service\ExportService;
use app\merchant\controller\merchant\AuthBaseController;

class EmployeeCardLogController extends AuthBaseController
{
    /**
     * 财务报表数据统计
     */
    public function dataStatistics()
    {
        $params['mer_id'] = $this->merId;
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        try {
            $data = (new EmployeeCardLogService)->dataStatistics($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 店铺消费列表
     */
    public function getStoreConsumerList()
    {
        $params['mer_id'] = $this->merId;
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['page'] = $this->request->post('page', 1, 'intval');
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        try {
            $data = (new EmployeeCardLogService)->getStoreConsumerList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 财务报表导出
     */
    public function exportBillData()
    {
        $params = [];
        $params['mer_id'] =$this->merId;
        $params['export_type'] = $this->request->post('exportType', 0, 'intval');
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        
        try {
            $result = (new ExportService())->addBillDataExport($params); 
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }


    /**
     * 店铺列表导出
     */
    public function exportStoreList()
    {
        $params['mer_id'] = $this->merId;
        $params['request_type'] = 'export';
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        try {
            $result = (new ExportService())->addStoreListExport($params); 
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 员工卡消费券退款
     */
    public function employeeCouponRefund()
    {
        $params['mer_id'] = $this->merId;
        $params['pigcms_id'] = $this->request->param('pigcms_id', 0, 'intval');
        $params['refund_remark'] = $this->request->param('refund_remark', '', 'trim');
        try {
            $result = (new EmployeeCardLogService())->employeeCouponRefund($params); 
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }
}