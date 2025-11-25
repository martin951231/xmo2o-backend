<?php


namespace app\life_tools\controller\api;


use app\life_tools\model\service\LifeToolsCompetitionService;

class CompetitionController extends ApiBaseController
{
    /**
     * @return mixed
     * 赛事活动列表
     */
    public function competList(){
        $out['header']=[
            [
                'title'=>'全部',
                'type'=>0
            ],
            [
                'title'=>'人气排行',
                'type'=>1
            ],
        ];
        $param['type']   = $this->request->param('type', 0, 'intval');
        $param['page']   = $this->request->param('page', 1, 'intval');
        $param['pageSize']   = $this->request->param('pageSize', 10, 'intval');
        $list=(new LifeToolsCompetitionService())->competitionList($param);
            $list['header']=$out['header'];
        return api_output(0, $list, 'success');
    }

    /**
     * 赛事详情
     */
   public function competDetail(){
       $param['competition_id']   = $this->request->param('competition_id', 0, 'intval');
       if(empty($param['competition_id'])){
           return api_output_error(1003, "缺少必要参数");
       }
       $list=(new LifeToolsCompetitionService())->competDetail($param);
       return api_output(0, $list, 'success');
   }

    /**
     * 赛事活动-我的赛事列表
     */
   public function myCompetList(){
       $param['uid']=$this->_uid;
       $param['page']   = $this->request->param('page', 1, 'intval');
       $param['pageSize']   = $this->request->param('pageSize', 10, 'intval');
       if(empty($param['uid'])){
           return api_output_error(1002, "请登录");
       }
       $list=(new LifeToolsCompetitionService())->myCompetList($param);
       return api_output(0, $list, 'success');
   }

    /**
     * @return \json
     * 赛事活动-我的报名详情
     */
   public function orderDetail(){
       $param['pigcms_id']   = $this->request->param('order_id', 0, 'intval');
       if(empty($param['pigcms_id'])){
           return api_output_error(1003, "缺少必要参数");
       }
       $detail=(new LifeToolsCompetitionService())->orderDetail($param);
       return api_output(0, $detail, 'success');
   }

    /**
     * 赛事活动-报名提交订单
     */
   public function saveOrder(){
       $param['competition_id']   = $this->request->param('competition_id', 0, 'intval');
       $param['coupon_id']   = $this->request->param('coupon_id', 0, 'intval');
       $param['uid']=$this->_uid;
       $param['name']   = $this->request->param('name', 0, 'trim');
       $param['phone']   = $this->request->param('phone', 0, 'trim');
       $param['custom_form']   = $this->request->param('custom_form', []);
       $param['userInfo']   = $this->userInfo;
       if(empty($param['uid'])){
           return api_output_error(1002, "请登录");
       }
       if(empty($param['competition_id'])){
           return api_output_error(1003, "缺少赛事信息");
       }
    //    if(empty($param['name']) || empty($param['phone'])){
    //        return api_output_error(1003, "真是姓名或者手机号必填");
    //    }
        try {
            $ret=(new LifeToolsCompetitionService())->saveOrder($param);
            return api_output(1000, $ret, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
   }
}