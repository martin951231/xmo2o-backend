<?php


namespace app\appoint\controller\worker;


use app\appoint\model\service\WorkWalletService;

class WorkWalletController extends AuthBaseController
{
    /**
     * 钱包首页
     */
    public function index(){
        $params['work'] = $this->workUser;
        try {
            $result = (new WorkWalletService())->index($params);
            return api_output(0,$result);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 帐单列表
     * @return \think\response\Json
     */
    public function billList(){
        $params['work'] = $this->workUser;
        $params['start_date'] = $this->request->param('start_date','','trim,string');
        $params['end_date'] = $this->request->param('end_date','','trim,string');
        $params['from_type'] = $this->request->param('from_type',0,'trim,intval');
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        try {
            $result = (new WorkWalletService())->billList($params);
            return api_output(0,$result);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 提现申请
     */
    public function cashOut(){
        $params['merchant_worker_id'] = $this->workUser['merchant_worker_id'];
        $params['truename'] = $this->request->param('truename','','trim,string');
        $params['money'] = $this->request->param('money',0,'trim');
        $params['type'] = $this->request->param('type',2,'trim,intval');
        try {
            $result = (new WorkWalletService())->cashOut($params);
            return api_output(0,$result);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}