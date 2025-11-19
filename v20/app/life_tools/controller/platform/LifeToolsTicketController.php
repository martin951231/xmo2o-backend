<?php


namespace app\life_tools\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\LifeToolsTicketService;

class LifeToolsTicketController extends AuthBaseController
{
    /**
     * 获得门票详情
     * @return \json
     */
    public function getDetail()
    {
        $param['ticket_id'] = $this->request->param('ticket_id', '', 'intval');
        $param['mer_id'] = $this->merId;// 商家ID
        $service = new LifeToolsTicketService();
        $arr = $service->getDetail($param);
        return api_output(0, $arr, 'success');
    }
    /**
     * 门票审核列表
     */
    public function getAuditTicketList()
    {
        $params = [];
        $params['tools_type'] = $this->request->post('tools_type', '', 'trim');
        $params['audit_status'] = $this->request->post('audit_status', null, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        try {
            $data = (new LifeToolsTicketService)->lifeToolsAudit($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 门票审核
     */
    public function auditTicket()
    {
        $params = [];
        $params['ticket_ids'] = $this->request->post('ticket_ids', []);
        $params['audit_status'] = $this->request->post('audit_status', 1, 'intval');
        $params['audit_msg'] = $this->request->post('audit_msg','', 'trim');
        $params['admin_id'] = $this->systemUser['id'];
        try {
            $data = (new LifeToolsTicketService)->auditTicket($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 查询未审核数量
     */
    public function getNotAuditNum()
    {
        $params = [];
        $params['tools_type'] = $this->request->post('tools_type', 'sports');
        try {
            $data = (new LifeToolsTicketService)->getNotAuditNum($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}