<?php
/**
 * 景区团体票旅行社
 */

namespace app\life_tools\controller\merchant;

use app\life_tools\model\service\group\LifeToolsGroupOrderService;
use app\merchant\controller\merchant\AuthBaseController;

class LifeToolsGroupOrderController extends AuthBaseController
{
    /**
     * 获取团体票购买审核列表
     * @author nidan
     * @date 2022/3/24
     */
    public function getAuditGroupOrderList()
    {
        $params = [];
        $params['keyword_scenic_name'] = $this->request->post('keyword_scenic_name', '', 'trim');//查询景区名称
        $params['keyword_ticket_name'] = $this->request->post('keyword_ticket_name', '', 'trim');//查询套餐名称
        $params['status'] = $this->request->post('status', 'all', 'trim');//查询状态
        $params['start_time'] = $this->request->post('start_time', '', 'trim');//查询开始时间
        $params['end_time'] = $this->request->post('end_time', '', 'trim');//查询结束时间
        $params['page_size'] = $this->request->post('page_size', 10, 'trim,intval');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsGroupOrderService())->getAuditGroupOrderList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 审核团体票订单
     * @author nidan
     * @date 2022/3/24
     */
    public function audit()
    {
        $params = [];
        $params['group_order_id'] = $this->request->post('group_order_id', '', 'trim');//团体票订单id
        $params['status'] = $this->request->post('status', '', 'trim');//审核状态
        $params['note'] = $this->request->post('note', '', 'trim');//审核说明
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsGroupOrderService())->audit($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 团体票统计数据
     */
    public function getStatisticsData()
    {
        try {
            $data = (new LifeToolsGroupOrderService())->getStatisticsData($this->merId);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 团体票订单列表
     */
    public function getOrderList()
    {
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['search_type'] = $this->request->post('search_type', 0, 'intval');
        $params['search'] = $this->request->post('search', '', 'trim');
        $params['status'] = $this->request->post('status', 0, 'intval');
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        try {
            $data = (new LifeToolsGroupOrderService())->getOrderList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }


    /**
     * 退款
     */
    public function groupOrderRefand()
    {
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['order_id'] = $this->request->post('order_id', 0, 'intval');
        $params['num'] = $this->request->post('num', 0, 'intval');
        try {
            $data = (new LifeToolsGroupOrderService())->groupOrderRefand($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
}
