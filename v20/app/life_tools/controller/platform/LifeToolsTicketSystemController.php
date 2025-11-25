<?php
/**
 * 票务系统
 */

namespace app\life_tools\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\LifeToolsOrderService;
use app\storestaff\model\service\ScanService;

class LifeToolsTicketSystemController extends AuthBaseController
{

    /**
     * 获取景区票务统计数据
     * @author Nd
     * @date 2022/4/24
     */
    public function getAtatisticsInfo()
    {
        try {
            $params = [];
            $params['type'] = $this->request->post('type', '', 'trim');  //scenic|stadium|course|sports:stadium&course
            $data = (new LifeToolsOrderService())->getAtatisticsInfo($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 获取景区、体育票务订单详情
     * @author Nd
     * @date 2022/4/24
     */
    public function getOrderDetail()
    {
        $param['order_id'] = $this->request->param('order_id', 0, 'intval');

        try {
            $data = (new LifeToolsOrderService())->getOrderDetail($param);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 票务订单核销
     * @author Nd
     * @date 2022/4/24
     */
    public function orderVerification()
    {
        $params = [];
        $params['code'] = $this->request->post('code', '', 'trim');
        $params['mer_id'] = $this->request->post('mer_id', '', 'trim');
        $admin = [];
        $admin['mer_id'] = $this->systemUser['id'];
        $admin['name'] = $this->systemUser['show_account'] . '-' . $this->systemUser['realname'];
        try {
            $data = (new LifeToolsOrderService())->verification($params,$admin);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 票务订单退款
     * @author Nd
     * @date 2022/4/24
     */
    public function orderBack()
    {
        $detail_id = $this->request->param('detail_id', 0, 'intval');
        $reason    = $this->request->param('reason', '', 'trim');
        if (empty($detail_id)) {
            return api_output_error(1003, '参数错误');
        }
        $admin = [];
        $admin['id'] = $this->systemUser['id'];
        $admin['name'] = $this->systemUser['show_account'] . '-' . $this->systemUser['realname'];
        try {
            $arr = (new LifeToolsOrderService())->refundOne($detail_id, $reason, $admin, 'system');
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 订单列表
     */
    public function orderList()
    {
        $params = [];
        $params['type'] = $this->request->post('type', '', 'trim');  //scenic|stadium|course|sports:stadium&course 
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        $params['status'] = $this->request->post('status', -1, 'intval');//-1：全部，10：待付款，20待核销，30：已核销，50：已退款，70：已过期， 60：已取消，45:售后中
        $params['pay_type'] = $this->request->post('pay_type', 'all', 'trim');//all 全部， wechat：微信，alipay：支付宝，offline：现金支付，balance：余额支付
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['search_type'] = $this->request->post('search_type', 0, 'intval');//0：全部，1：订单号，2：景区|场馆|课程名，3：门票名称，4：手机号，5：用户呢称
        $params['ticket_type'] = $this->request->post('ticket_type', 0, 'intval'); //0=全部,1=团体票，2=普通门票，3=票付通订单
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        try {
            $data = (new LifeToolsOrderService())->getTicketSystemOrderList($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
    

    /**
     * 导出
     */
    public function ticketSystemExport()
    {
        $params = [];
        $params['type'] = $this->request->post('type', '', 'trim');  //scenic|stadium|course|sports:stadium&course 
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        $params['status'] = $this->request->post('status', -1, 'intval');//-1：全部，10：待付款，20待核销，30：已核销，50：已退款，70：已过期， 60：已取消，45:售后中
        $params['pay_type'] = $this->request->post('pay_type', '', 'trim');//wechat：微信，alipay：支付宝，offline：现金支付，balance：余额支付
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['search_type'] = $this->request->post('search_type', 0, 'intval');//0：全部，1：订单号，2：景区|场馆|课程名，3：门票名称，4：手机号，5：用户呢称
        $params['ticket_type'] = $this->request->post('ticket_type', 0, 'intval'); //0=全部,1=团体票，2=普通门票
        
        $params['export_type'] = $this->request->post('export_type', 0, 'intval'); //0=按日导出统计，1=按月导出统计，2=按年导出统计，3=导出订单
        $params['rand_number'] = uniqid();
        try {
            if($params['export_type'] == 3){
                //导出订单
                $data = (new LifeToolsOrderService())->orderExport($params);
            }else{
                //导出数据统计
                $data = (new LifeToolsOrderService())->dataExport($params);
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
   
}