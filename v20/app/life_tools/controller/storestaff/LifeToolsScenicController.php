<?php 

namespace app\life_tools\controller\storestaff;

use app\life_tools\model\service\LifeToolsAppointService;
use app\life_tools\model\service\LifeToolsCardOrderService;
use app\life_tools\model\service\LifeToolsOrderService;
use app\life_tools\model\service\LifeToolsService;
use app\life_tools\model\service\LifeToolsTicketService;
use app\storestaff\controller\storestaff\AuthBaseController;
use app\storestaff\model\service\ScanService;

class LifeToolsScenicController extends AuthBaseController
{
 
    /**
     * 景区核销
     */
    public function verification()
    {
        $params = [];
        $params['code'] = $this->request->post('code', '', 'trim');
        try {
            $data = (new ScanService())->indexScan($params, $this->staffUser);
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
        $params['order_type'] = 'scenic';
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        $params['page'] = $this->request->post('page', 1, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['search_by'] = $this->request->post('search_by', 1, 'intval');
        $params['date_by'] = $this->request->post('date_by', 1, 'intval');
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        $params['type'] = $this->request->post('type', '', 'trim');
        $params['tools_type'] = 'scenic';//$this->request->post('tools_type', 'scenic', 'trim');
        $params['staffUser'] = $this->staffUser;
        try {
            if($params['type'] == 'ticket'){
                $data = (new LifeToolsOrderService())->verifyList($params);
            }else{
                $data = (new LifeToolsCardOrderService())->getVerifyList($params);
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        } 
        return api_output(0, $data);
        
    }

    /**
     * 获取次卡订单详情
     * @return \json
     */
    public function getCardOrderDetail()
    {
        $order_id = $this->request->param('order_id', 0, 'intval');
        $type = $this->request->param('type', 'card', 'trim');
        if (empty($order_id)) {
            return api_output_error(1003, '参数错误');
        }
        try {
            if($type == 'card'){
                $arr = (new LifeToolsCardOrderService())->getDetail($order_id);
            }else{
                $arr = (new LifeToolsOrderService)->getDetail($order_id);
            }
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取订单列表
     * @return \json
     */
    public function getOrderList()
    {
        $param['mer_id']      = $this->merId;
        $param['staff_id']      = $this->staffId;
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['type']        = $this->request->param('type', 0, 'intval');
        $param['status']      = $this->request->param('status', -1, 'intval');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['begin_time']  = $this->request->param('begin_time', '', 'trim');
        $param['end_time']    = $this->request->param('end_time', '', 'trim');
        $param['time_type']   = $this->request->param('time_type', 2, 'intval');
        $param['pay_type']   = $this->request->param('pay_type', '', 'trim');
        try {
            $arr = (new LifeToolsOrderService())->getList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取指定商家的全部景区
     * @author nidan
     * @date 2022/4/1
     */
    public function getScenic()
    {
        $param['mer_id'] = $this->merId;
        try {
            $arr = (new LifeToolsService())->getScenic($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 导出订单列表
     * @return \json
     */
    public function exportToolsOrder()
    {
        $param['mer_id']      = $this->merId;
        $param['staff_id']      = $this->staffId;
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['type']        = $this->request->param('type', 0, 'intval');
        $param['status']      = $this->request->param('status', -1, 'intval');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['begin_time']  = $this->request->param('begin_time', '', 'trim');
        $param['end_time']    = $this->request->param('end_time', '', 'trim');
        $param['time_type']   = $this->request->param('time_type', 2, 'intval');
        try {
            $arr = (new LifeToolsOrderService())->exportToolsOrder($param, [], []);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取订单详情
     * @return \json
     */
    public function getOrderDetail()
    {
        $order_id = $this->request->param('order_id', 0, 'intval');
        if (empty($order_id)) {
            return api_output_error(1003, '参数错误');
        }
        try {
            $arr = (new LifeToolsOrderService())->getDetail($order_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 同意退款
     * @return \json
     */
    public function agreeRefund()
    {
        $order_id  = $this->request->param('order_id', 0, 'intval');
        $order_ids = $this->request->param('order_ids', '', 'trim');
        $detail_id = $this->request->param('detail_id', 0, 'intval');
        if (empty($order_id) && empty($order_ids) && empty($detail_id)) {
            return api_output_error(1003, '参数错误');
        }
        if (empty($order_ids)) {
            $order_ids = [$order_id];
        }
        try {
            if (!empty($detail_id)) {
                $arr = (new LifeToolsOrderService())->agreeOutRefund($detail_id);
            } else {
                $arr = (new LifeToolsOrderService())->agreeRefund($order_ids);
            }
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 拒绝退款
     * @return \json
     */
    public function refuseRefund()
    {
        $order_id  = $this->request->param('order_id', 0, 'intval');
        $order_ids = $this->request->param('order_ids', '', 'trim');
        $reason    = $this->request->param('reason', '', 'trim');
        if ((empty($order_id) && empty($order_ids)) || empty($reason)) {
            return api_output_error(1003, '参数错误');
        }
        if (empty($order_ids)) {
            $order_ids = [$order_id];
        }
        try {
            $arr = (new LifeToolsOrderService())->refuseRefund($order_ids, $reason);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取门票
     * @author nidan
     * @date 2022/4/1
     */
    public function getTicket()
    {
        $params['keyWords'] = $this->request->post('keywords', '', 'trim');
        $params['select_date'] = $this->request->post('select_date', date('Y-m-d'), 'trim');
        $params['tools_id'] = $this->request->post('tools_id', 0, 'trim');
        $params['page'] = $this->request->post('page', 1, 'trim');
        $params['page_size'] = $this->request->post('page_size', 10, 'trim');
        $params['mer_id'] = $this->merId;
        try {
            $arr = (new LifeToolsTicketService())->getTicketList($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}
