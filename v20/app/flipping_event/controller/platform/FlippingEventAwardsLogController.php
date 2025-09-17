<?php


namespace app\flipping_event\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\flipping_event\model\service\FlippingEventAwardsLogService;

class FlippingEventAwardsLogController extends AuthBaseController
{
    /**
     * 中奖列表
     */
    public function getAwardLogList(){
        $params['search_type'] = $this->request->param('search_type','','trim,string');
        $params['keywords'] = $this->request->param('keywords','','trim,string');
        $params['start_time'] = $this->request->param('start_time','','trim,string');
        $params['end_time'] = $this->request->param('end_time','','trim,string');
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new FlippingEventAwardsLogService())->getAwardLogList($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 中奖列表-导出
     */
    public function exportAwardLogList(){
        $params['search_type'] = $this->request->param('search_type','','trim,string');
        $params['keywords'] = $this->request->param('keywords','','trim,string');
        $params['start_time'] = $this->request->param('start_time','','trim,string');
        $params['end_time'] = $this->request->param('end_time','','trim,string');
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['rand_number'] = time();
        try {
            $res = (new FlippingEventAwardsLogService())->exportAwardLogList($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}