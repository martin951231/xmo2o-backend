<?php


namespace app\appoint\controller\api;


use app\appoint\model\service\AppointRewardOrderService;

class AppointRewardOrderController extends ApiBaseController
{
    /**
     * 生成打赏订单
     * @return \think\response\Json
     */
    public function getWorkerInfo(){
        $this->checkLogin();
        $params['order_id'] = $this->request->param('order_id',0,'trim,intval');
        try {
            $res = (new AppointRewardOrderService())->getWorkerInfo($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
    
    /**
     * 生成打赏订单
     * @return \think\response\Json
     */
    public function saveWorkerReward(){
        $this->checkLogin();
        $params['money'] = $this->request->param('money',0,'trim');
        $params['order_id'] = $this->request->param('order_id',0,'trim,intval');
        $params['uid'] = $this->_uid;
        try {
            $res = (new AppointRewardOrderService())->saveWorkerReward($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}