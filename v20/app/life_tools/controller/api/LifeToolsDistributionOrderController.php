<?php


namespace app\life_tools\controller\api;

use app\life_tools\model\service\distribution\LifeToolsDistributionOrderStatementService;
use app\life_tools\model\service\distribution\LifeToolsDistributionUserService;

class LifeToolsDistributionOrderController extends ApiBaseController
{

    /**
     * 结算单列表
     */
    public function getOrderStatement()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['mer_id'] = $this->request->post('mer_id', 0, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        try {
            $data = (new LifeToolsDistributionOrderStatementService())->getOrderStatement($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }


    /**
     * 结算单详情
     */
    public function getOrderStatementDetail()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['statement_id'] = $this->request->post('statement_id', 0, 'intval');
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        try {
            $data = (new LifeToolsDistributionOrderStatementService())->getOrderStatementDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 确认结算单
     */
    public function confirmStatementOrder()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['statement_id'] = $this->request->post('statement_id', 0, 'intval');
        try {
            $data = (new LifeToolsDistributionOrderStatementService())->confirmStatementOrder($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

}