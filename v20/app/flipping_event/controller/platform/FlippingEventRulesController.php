<?php


namespace app\flipping_event\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\flipping_event\model\service\FlippingEventRulesService;

class FlippingEventRulesController extends AuthBaseController
{
    /**
     * 活动规则列表
     * @return \think\response\Json
     */
    public function getRulesList(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new FlippingEventRulesService())->getRulesList($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 创建、编辑
     * @return \think\response\Json
     */
    public function saveData(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['title'] = $this->request->param('title','','trim,string');
        $params['awards_ids'] = $this->request->param('awards_ids');
        $params['stocks_num'] = $this->request->param('stocks_num',0,'trim,intval');
        $params['type'] = $this->request->param('type',0,'trim,intval');
        $params['awards_val'] = $this->request->param('awards_val','','trim,string');
        $params['event_id'] = $this->request->param('event_id',1,'trim,intval');
        $params['img'] = $this->request->param('img','','trim,string');
        try {
            $res = (new FlippingEventRulesService())->saveData($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 活动规则删除
     * @return \think\response\Json
     */
    public function delete(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new FlippingEventRulesService())->delete($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}