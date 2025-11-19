<?php
/**
 * 体育订单控制器
 */

namespace app\life_tools\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\LifeToolsOrderService;
use app\merchant\model\service\LoginService;

class SportsOrderController extends AuthBaseController
{

    /**
     * 获取列表
     * @return \json
     */
    public function getOrderList()
    {
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['type']        = $this->request->param('type', 0, 'intval');
        $param['status']      = $this->request->param('status', -1, 'intval');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['begin_time']  = $this->request->param('begin_time', '', 'trim');
        $param['end_time']    = $this->request->param('end_time', '', 'trim');
        $param['time_type']   = $this->request->param('time_type', 2, 'intval');
        $param['pay_type']    = $this->request->param('pay_type', '', 'trim');
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
            $arr = (new LifeToolsOrderService())->exportToolsOrder($param, $this->systemUser, []);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage().$e->getLine().$e->getFile());
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
     * 商家登录
     * @return \json
     */
    public function loginMer()
    {
        $param['mer_id'] = $this->request->param("mer_id", "0", "intval");
        try {
            $arr = (new LoginService())->autoLogin($param, $this->systemUser);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}