<?php


namespace app\flipping_event\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\flipping_event\model\service\FlippingEventRulesLogService;
use app\flipping_event\model\service\FlippingEventRulesService;

class FlippingEventRulesLogController extends AuthBaseController
{
    /**
     * 兑换列表
     */
    public function getRulesLogList(){
        $params['search_type'] = $this->request->param('search_type','','trim,string');
        $params['keywords'] = $this->request->param('keywords','','trim,string');
        $params['start_time'] = $this->request->param('start_time','','trim,string');
        $params['end_time'] = $this->request->param('end_time','','trim,string');
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new FlippingEventRulesLogService())->getRulesLogList($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 兑换列表-导出
     */
    public function exportRulesLogList(){
        $params['search_type'] = $this->request->param('search_type','','trim,string');
        $params['keywords'] = $this->request->param('keywords','','trim,string');
        $params['start_time'] = $this->request->param('start_time','','trim,string');
        $params['end_time'] = $this->request->param('end_time','','trim,string');
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['rand_number'] = time();
        try {
            $res = (new FlippingEventRulesLogService())->exportRulesLogList($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 兑换实物
     */
    public function changeStatus(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new FlippingEventRulesLogService())->changeStatus($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}