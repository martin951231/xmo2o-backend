<?php


namespace app\life_tools\controller\api;


use app\life_tools\model\service\appoint\LifeToolsAppointJoinOrderService;
use app\life_tools\model\service\appoint\LifeToolsAppointService;
use app\life_tools\model\service\LifeToolsCompetitionService;

class LifeToolsAppointController extends ApiBaseController
{
    /**
     * 预约详情
     */
    public function appointDetail(){
        $param['appoint_id']   = $this->request->param('appoint_id', 0, 'intval');
        if(empty($param['appoint_id'])){
            return api_output_error(1003, "缺少必要参数");
        }
        $list=(new LifeToolsAppointService())->competDetail($param);
        return api_output(0, $list, 'success');
    }

    /**
     * 预约下单
     */
    public function saveOrder(){
        $this->checkLogin();
        $param['appoint_id'] = $this->request->param('appoint_id', 0, 'intval');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['coupon_id'] = $this->request->param('coupon_id', 0, 'intval');
        $param['phone'] = $this->request->param('phone', '', 'trim');
        $param['remark'] = $this->request->param('remark', '', 'trim');
        $param['uid'] = $this->_uid;
        $param['seat_num'] = $this->request->post('seat_num', []);
        $param['custom_form'] = $this->request->post('custom_form', []);
        $param['sku_id'] = $this->request->post('sku_id', 0, 'intval');
        if(empty($param['appoint_id'])){
            return api_output_error(1003, "缺少必要参数");
        }
        try{
        $ret=(new LifeToolsAppointService())->saveOrder($param);
        return api_output(0, $ret, 'success');
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 退款申请
     */

    public function refundingOrder(){
        $this->checkLogin();
        $param['pigcms_id'] = $this->request->param('order_id', 0, 'intval');
        $param['apply_refund_reason'] = $this->request->param('apply_refund_reason', '', 'trim');
        $param['uid']      =$this->_uid;
        if(empty($param['pigcms_id'])){
            return api_output_error(1003, "缺少必要参数");
        }
        $ret=(new LifeToolsAppointJoinOrderService())->refundingOrder('life_tools_appoint_join',$param);
        if($ret){
            return api_output(0, [], 'success');
        }else{
            return api_output_error(1003, "申请失败");
        }
    }

    /**
     * 撤销退款
     */
    public function cancelRefund(){
        $this->checkLogin();
        $param['pigcms_id']      = $this->request->param('order_id', 0, 'intval');
        $param['uid']      =$this->_uid;
        
        $ret=(new LifeToolsAppointJoinOrderService())->cancelRefund($param);
        return api_output(0, $ret, 'success');
    }

    /**
     * @return \json
     * 赛事活动-我的预约详情
     */
    public function orderDetail(){
        $this->checkLogin();
        $param['pigcms_id']   = $this->request->param('order_id', 0, 'intval');
        if(empty($param['pigcms_id'])){
            return api_output_error(1003, "缺少必要参数");
        }
        $detail=(new LifeToolsAppointJoinOrderService())->orderDetail($param);
        return api_output(0, $detail, 'success');
    }

    /**
     * 订单标题
     */
    public function orderHeader(){
       $out['header']=[
           [
               'status'=>0,
               'title'=>'全部',
           ],
           [
               'status'=>1,
               'title'=>'未开始',
           ],
           [
               'status'=>2,
               'title'=>'进行中',
           ],
           [
               'status'=>3,
               'title'=>'已结束',
           ],
       ];
        return api_output(0, $out, 'success');
    }

    /**
     * 订单列表
     */
    public function orderList(){
        $this->checkLogin();
        $param['status']   = $this->request->param('status', 0, 'intval');
        $param['page']   = $this->request->param('page', 1, 'intval');
        $param['pageSize']   = $this->request->param('pageSize', 10, 'intval');
        $param['uid']      =$this->_uid;
        $list=(new LifeToolsAppointJoinOrderService())->orderList($param);
        return api_output(0, $list, 'success');
    }

    /**
     * 轮询核销状态
     */
    public function verifyStatus(){
        $this->checkLogin();
        $param['order_id']   = $this->request->param('order_id', 0, 'intval');
        $param['now_time']   = $this->request->param('now_time', 0, 'intval');
        $list=(new LifeToolsAppointJoinOrderService())->verifyStatus($param);
        return api_output(0, $list, 'success');
    }

    /**
     * 取消订单
     */
    public function cancelOrder()
    {
        $this->checkLogin();
        $order_id = $this->request->param('order_id', 0, 'intval');
        try{
            $ret = (new LifeToolsAppointService())->cancelOrder($order_id, $this->_uid);
            return api_output(0, $ret, 'success');
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
    }

}