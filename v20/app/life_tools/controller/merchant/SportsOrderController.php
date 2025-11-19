<?php
/**
 * 体育订单控制器
 */

namespace app\life_tools\controller\merchant;

use app\life_tools\model\service\LifeToolsOrderDetailService;
use app\merchant\controller\merchant\AuthBaseController;
use app\life_tools\model\service\LifeToolsOrderService;

class SportsOrderController extends AuthBaseController
{

    /**
     * 获取订单列表
     * @return \json
     */
    public function getOrderList()
    {
        $param['mer_id']      = $this->merId;
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
        $param['order_type']   = $this->request->param('order_type', 0, 'intval');
        try {
            $arr = (new LifeToolsOrderService())->getList($param);
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
        $param['order_type']   = $this->request->param('order_type', 0, 'intval');
        try {
            $arr = (new LifeToolsOrderService())->exportToolsOrder($param, [], $this->merchantUser);
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
        $reason    = $this->request->param('reason', '', 'trim');
        $merUser   = $this->merchantUser;
        if (empty($order_id) && empty($order_ids) && empty($detail_id)) {
            return api_output_error(1003, '参数错误');
        }
        if (empty($order_ids)) {
            $order_ids = [$order_id];
        }
        try {
            if (!empty($detail_id)) {
                $arr = (new LifeToolsOrderService())->agreeOutRefund($detail_id, $reason, $merUser);
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
     * 获取核销列表
     * @return \json
     */
    public function getVerifyList()
    {
        $param['mer_id']     = $this->merId;
        $param['keyword']    = $this->request->param('keyword', '', 'trim');
        $param['page']       = $this->request->param('page', 1, 'intval');
        $param['pageSize']   = $this->request->param('pageSize', 10, 'intval');
        $param['type']       = $this->request->param('type', 0, 'intval');
        $param['begin_time'] = $this->request->param('begin_time', '', 'trim');
        $param['end_time']   = $this->request->param('end_time', '', 'trim');
        try {
            $arr = (new LifeToolsOrderDetailService())->getVerifyList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 导出核销列表
     * @return \json
     */
    public function exportVerifyRecord()
    {
        $param['mer_id']     = $this->merId;
        $param['keyword']    = $this->request->param('keyword', '', 'trim');
        $param['page']       = $this->request->param('page', 1, 'intval');
        $param['pageSize']   = $this->request->param('pageSize', 10, 'intval');
        $param['type']       = $this->request->param('type', 0, 'intval');
        $param['begin_time'] = $this->request->param('begin_time', '', 'trim');
        $param['end_time']   = $this->request->param('end_time', '', 'trim');
        try {
            $arr = (new LifeToolsOrderDetailService())->exportVerifyRecord($param, [], $this->merchantUser);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}