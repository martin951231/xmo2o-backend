<?php

namespace app\roundabout\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\roundabout\model\service\ActivityService;

class ActivityController extends AuthBaseController
{
    /**
     * 活动列表
     * @return \think\response\Json
     */
    public function getList(){
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        try {
            $res = (new ActivityService())->getLsit($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 修改状态
     * @return \think\response\Json
     */
    public function changeStatus(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['status'] = $this->request->param('status',0,'trim,intval');
        $params['type'] = 1;
        try {
            $res = (new ActivityService())->updataActivity($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 单个/批量删除
     * @return \think\response\Json
     */
    public function delActivity(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['type'] = 2;
        try {
            $res = (new ActivityService())->updataActivity($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 添加
     * @return \think\response\Json\
     */
    public function addActivity(){
        $params['name'] = $this->request->param('name','','trim,string');
        $params['start_time'] = $this->request->param('start_time','','trim,string');
        $params['end_time'] = $this->request->param('end_time','','trim,string');
        $params['num'] = $this->request->param('num',0,'trim,intval');
        $params['bg_pic'] = $this->request->param('bg_pic','','trim,string');
        $params['user_type'] = $this->request->param('user_type',0,'trim,intval');
        $params['user_register_day'] = $this->request->param('user_register_day',0,'trim,intval');
        $params['business_type'] = $this->request->param('business_type',[]);
        $params['amount'] = $this->request->param('amount','','trim,string');
        $params['kefu_pic'] = $this->request->param('kefu_pic','','trim,string');
        $params['bg_audio'] = $this->request->param('bg_audio','','trim,string');
        $params['win_audio'] = $this->request->param('win_audio','','trim,string');
        $params['not_win_audio'] = $this->request->param('not_win_audio','','trim,string');
        $params['rule_des'] = $this->request->param('rule_des','','trim,string');
        $params['name'] = $this->request->param('name','','trim,string');
        $params['prize_info'] = $this->request->param('prize_info',[]);
        $params['type'] = $this->request->param('type',0,'trim,intval');
        $params['show_coupon_name'] = $this->request->param('show_coupon_name',0,'trim,intval');
        try {
            $res = (new ActivityService())->saveActivity($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 编辑
     * @return \think\response\Json
     */
    public function editActivity(){
        $params['name'] = $this->request->param('name','','trim,string');
        $params['start_time'] = $this->request->param('start_time','','trim,string');
        $params['end_time'] = $this->request->param('end_time','','trim,string');
        $params['num'] = $this->request->param('num',0,'trim,intval');
        $params['bg_pic'] = $this->request->param('bg_pic','','trim,string');
        $params['user_type'] = $this->request->param('user_type',0,'trim,intval');
        $params['user_register_day'] = $this->request->param('user_register_day',0,'trim,intval');
        $params['business_type'] = $this->request->param('business_type',[]);
        $params['amount'] = $this->request->param('amount','','trim,string');
        $params['kefu_pic'] = $this->request->param('kefu_pic','','trim,string');
        $params['bg_audio'] = $this->request->param('bg_audio','','trim,string');
        $params['win_audio'] = $this->request->param('win_audio','','trim,string');
        $params['not_win_audio'] = $this->request->param('not_win_audio','','trim,string');
        $params['rule_des'] = $this->request->param('rule_des','','trim,string');
        $params['name'] = $this->request->param('name','','trim,string');
        $params['prize_info'] = $this->request->param('prize_info',[]);
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['type'] = $this->request->param('type',0,'trim,intval');
        $params['show_coupon_name'] = $this->request->param('show_coupon_name',0,'trim,intval');
        try {
            $res = (new ActivityService())->saveActivity($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 详情
     * @return \think\response\Json
     */
    public function detailActivity(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new ActivityService())->detailActivity($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 中奖人员列表
     * @return \think\response\Json
     */
    public function getUserList(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        $params['search_type'] = $this->request->param('search_type',0,'trim,intval');
        $params['keywords'] = $this->request->param('keywords','','trim,string');
        $params['start_time'] = $this->request->param('start_time','','trim,string');
        $params['end_time'] = $this->request->param('end_time','','trim,string');
        try {
            $res = (new ActivityService())->getUserList($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 中奖人员列表
     * @return \think\response\Json
     */
    public function exportUserList(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['start_time'] = $this->request->param('start_time','','trim,string');
        $params['end_time'] = $this->request->param('end_time','','trim,string');
        $params['search_type'] = $this->request->param('search_type',0,'trim,intval');
        $params['keywords'] = $this->request->param('keywords','','trim,string');
        $params['rand_number'] = time();
        try {
            $res = (new ActivityService())->exportUserList($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 优惠券列表
     * @return \think\response\Json
     */
    public function getCouponList(){
        $params['keywords'] = $this->request->param('keywords','','trim,string');
        try {
            $res = (new ActivityService())->getCouponList($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 商家优惠券列表
     * @return \think\response\Json
     */
    public function getMerCouponList(){
        $params['keywords'] = $this->request->param('keywords','','trim,string');
        try {
            $res = (new ActivityService())->getMerCouponList($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}