<?php


namespace app\flipping_event\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\flipping_event\model\service\FlippingEventService;

class FlippingEventController extends AuthBaseController
{
    /**
     * 活动列表
     * @return \think\response\Json
     */
    public function getList(){
        $params['keywords'] = $this->request->param('keywords','','trim,string');
        $params['status'] = $this->request->param('status','-1','trim,string');
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        try {
            $res = (new FlippingEventService())->getList($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 活动详情
     * @return \think\response\Json
     */
    public function getDetail(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new FlippingEventService())->getDetail($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 创建活动
     * @return \think\response\Json
     */
    public function saveData(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['title'] = $this->request->param('title','','trim,string');
        $params['start_time'] = $this->request->param('start_time','','trim,string');
        $params['end_time'] = $this->request->param('end_time','','trim,string');
        $params['img'] = $this->request->param('img','','trim,string');
        $params['per_order_draw_num'] = $this->request->param('per_order_draw_num',0,'trim,intval');
        $params['per_day_draw_num'] = $this->request->param('per_day_draw_num',0,'trim,intval');
        $params['content'] = $this->request->param('content','');
        $params['status'] = $this->request->param('status',1,'trim,intval');
        $params['awards'] = $this->request->param('awards');
        try {
            $res = (new FlippingEventService())->saveData($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 活动删除
     * @return \think\response\Json
     */
    public function delete(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new FlippingEventService())->delete($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}