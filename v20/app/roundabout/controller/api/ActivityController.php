<?php


namespace app\roundabout\controller\api;


use app\common\controller\api\ApiBaseController;
use app\roundabout\model\service\ActivityService;

class ActivityController extends ApiBaseController
{
    /**
     * 活动详情
     * @return \think\response\Json
     */
    public function getActivity(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['uid'] = $this->uid;
        try {
            $res = (new ActivityService())->getActivity($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }


    /**
     * 中奖记录
     * @return \think\response\Json
     */
    public function getActivityUser(){
        $this->checkLogin();
        $params['uid'] = $this->uid;
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new ActivityService())->getActivityUser($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 用户抽奖
     * @return \think\response\Json
     */
    public function userDraw(){
        $this->checkLogin();
        $params['uid'] = $this->uid;
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new ActivityService())->userDraw($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}