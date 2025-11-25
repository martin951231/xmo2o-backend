<?php


namespace app\shop\controller\merchant;


use app\common\model\service\plan\file\ShopRebateUserChangeStatus;
use app\merchant\controller\merchant\AuthBaseController;
use app\shop\model\service\rebate\RebateService;

class RebateController extends AuthBaseController
{
    /**
     * 集点返券列表
     * @return \think\response\Json
     */
    public function getList(){
        $param['store_id'] = $this->request->param('store_id',0,'trim,intval');
        $param['page'] = $this->request->param('page',1,'trim,intval');
        $param['pageSize'] = $this->request->param('pageSize',1,'trim,intval');
        try {
            $data = (new RebateService())->getList($param);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(1003,L_($e->getMessage()));
        }
    }

    /**
     * 修改状态
     * @return \think\response\Json
     */
    public function changeStatus(){
        $param['store_id'] = $this->request->param('store_id',0,'trim,intval');
        $param['id'] = $this->request->param('id',0,'trim,intval');
        $param['status'] = $this->request->param('status',0,'trim,intval');
        try {
            $data = (new RebateService())->changeStatus($param);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(1003,L_($e->getMessage()));
        }
    }

    /**
     * 活动详情
     * @return \think\response\Json
     */
    public function showDetail(){
        $param['store_id'] = $this->request->param('store_id',0,'trim,intval');
        $param['id'] = $this->request->param('id',0,'trim,intval');
        $param['mer_id'] = $this->merId;
        try {
            $data = (new RebateService())->showDetail($param);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(1003,L_($e->getMessage()));
        }
    }

    /**
     * 添加活动
     * @return \think\response\Json
     */
    public function add(){
        $param['store_id'] = $this->request->param('store_id',0,'trim,intval');
        $param['name'] = $this->request->param('name','','trim,string');
        $param['start_time'] = $this->request->param('start_time','','trim,string');
        $param['end_time'] = $this->request->param('end_time','','trim,string');
        $param['note'] = $this->request->param('note','','trim,string');
        $param['reset_day'] = $this->request->param('reset_day',0,'trim,intval');
        $param['total_order'] = $this->request->param('total_order',0,'trim,intval');
        $param['coupon_id'] = $this->request->param('coupon_id',0,'trim,intval');
        $param['goods_ids'] = $this->request->param('goods_ids');
        $param['mer_id'] = $this->merId;
        try {
            $data = (new RebateService())->saveDate($param);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(1003,L_($e->getMessage()));
        }
    }

    /**
     * 编辑活动
     * @return \think\response\Json
     */
    public function edit(){
        $param['store_id'] = $this->request->param('store_id',0,'trim,intval');
        $param['name'] = $this->request->param('name','','trim,string');
        $param['start_time'] = $this->request->param('start_time','','trim,string');
        $param['end_time'] = $this->request->param('end_time','','trim,string');
        $param['note'] = $this->request->param('note','','trim,string');
        $param['reset_day'] = $this->request->param('reset_day',0,'trim,intval');
        $param['total_order'] = $this->request->param('total_order',0,'trim,intval');
        $param['coupon_id'] = $this->request->param('coupon_id',0,'trim,intval');
        $param['goods_ids'] = $this->request->param('goods_ids');
        $param['id'] = $this->request->param('id',0,'trim,intval');
        $param['mer_id'] = $this->merId;
        try {
            $data = (new RebateService())->saveDate($param);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(1003,L_($e->getMessage()));
        }
    }

    /**
     * 选择商品列表
     * @return \think\response\Json
     */
    public function getGoodsList(){
        $param['store_id'] = $this->request->param('store_id',0,'trim,intval');
        $param['page'] = $this->request->param('page',1,'trim,intval');
        $param['pageSize'] = $this->request->param('pageSize',1,'trim,intval');
        $param['keywords'] = $this->request->param('keywords','','trim,string');
        try {
            $data = (new RebateService())->getGoodsList($param);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(1003,L_($e->getMessage()));
        }
    }

    /**
     * 删除活动
     * @return \think\response\Json
     */
    public function delete(){
        $param['store_id'] = $this->request->param('store_id',0,'trim,intval');
        $param['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $data = (new RebateService())->delete($param);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(1003,L_($e->getMessage()));
        }
    }

    /**
     * 获取优惠券列表
     * @return \think\response\Json
     */
    public function getCouponList(){
        $param['store_id'] = $this->request->param('store_id',0,'trim,intval');
        $param['mer_id'] = $this->merId;
        try {
            $data = (new RebateService())->getCouponList($param);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(1003,L_($e->getMessage()));
        }
    }
}