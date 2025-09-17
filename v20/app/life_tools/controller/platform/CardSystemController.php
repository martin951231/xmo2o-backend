<?php
/**
 * 票务系统
 */

namespace app\life_tools\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\LifeToolsCardOrderService;

class CardSystemController extends AuthBaseController
{
    /**
     * 获取景区次卡列表
     * @author Nd
     * @date 2022/5/13
     */
    public function getCardOrderList()
    {
//        $param['mer_id']      = $this->merId;
        $param['keyword']     = $this->request->param('keywords', '', 'trim');
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['type']        = $this->request->param('type', '', 'trim');
        $param['status']      = $this->request->param('status', -1, 'intval');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['begin_time']  = $this->request->param('start_date', '', 'trim');
        $param['end_time']    = $this->request->param('end_date', '', 'trim');
        $param['time_type']   = $this->request->param('time_type', 1, 'intval');
        try {
            $arr = (new LifeToolsCardOrderService())->getList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取次卡订单详情
     * @return \json
     */
    public function getCardOrderDetail()
    {
        $order_id = $this->request->param('order_id', 0, 'intval');
        if (empty($order_id)) {
            return api_output_error(1003, '参数错误');
        }
        try {
            $arr = (new LifeToolsCardOrderService())->getDetail($order_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 次卡订单退款
     * @return \json
     */
    public function CardOrderBack()
    {
        $param['order_id'] = $this->request->param('order_id', 0, 'intval');
        $param['reason']    = '平台退款';
        $admin = [];
        $admin['id'] = $this->systemUser['id'];
        $admin['name'] = $this->systemUser['show_account'] . '-' . $this->systemUser['realname'];
        try {
            $arr = (new LifeToolsCardOrderService())->CardOrderBack($param,$admin);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 导出
     */
    public function cardSystemExport()
    {
        $params = [];
        $params['keyword']     = $this->request->param('keyword', '', 'trim');
        $params['type']        = $this->request->param('type', '', 'trim');
        $params['status']      = $this->request->param('status', -1, 'intval');
        $params['search_type'] = $this->request->param('search_type', 1, 'intval');
        $params['begin_time']  = $this->request->param('start_date', '', 'trim');
        $params['end_time']    = $this->request->param('end_date', '', 'trim');
        $params['time_type']   = $this->request->param('time_type', 2, 'intval');

        $params['export_type'] = $this->request->post('export_type', 0, 'intval'); //0=按日导出统计，1=按月导出统计，2=按年导出统计，3=导出订单
        $params['rand_number'] = uniqid();
        try {
            if($params['export_type'] == 3){
                //导出订单
                $data = (new LifeToolsCardOrderService())->orderExport($params);
            }else{
                //导出数据统计
                $data = (new LifeToolsCardOrderService())->dataExport($params);
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
}