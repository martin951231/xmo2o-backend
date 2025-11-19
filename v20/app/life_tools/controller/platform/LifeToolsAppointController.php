<?php


namespace app\life_tools\controller\platform;
use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\appoint\LifeToolsAppointService;

class LifeToolsAppointController extends AuthBaseController
{
    /**
     * 预约列表
     */
  public function getList(){
      $param['title']=$this->request->param('title', '', 'trim');//活动标题
      $param['mer_name']=$this->request->param('mer_name', '', 'trim');//商家标题
      $param['start_time']=$this->request->param('start_time', '', 'trim');//活动开始时间
      $param['end_time']=$this->request->param('end_time', '', 'trim');//活动结束时间
      $param['page']=$this->request->param('page', 1, 'intval');//页码
      $param['pageSize']=$this->request->param('pageSize', 10, 'intval');//每页展示数量
      try {
          $list=(new LifeToolsAppointService())->getList($param);
      } catch (\Exception $e) {
          return api_output_error(1003, $e->getMessage());
      }
      return api_output(0, $list);
  }

    /**
     * 关闭预约
     */
  public function closeAppoint(){
      $param['status']=$this->request->param('status', 0, 'intval');//状态
      $param['appoint_id']=$this->request->param('appoint_id', 0, 'intval');//id
      try {
          if(empty($param['appoint_id'])){
              return api_output_error(1003, "缺少必要参数");
          }
          $list=(new LifeToolsAppointService())->closeAppoint($param);
          if($list){
              return api_output(0, []);
          }else{
              return api_output_error(1003, "状态改变失败");
          }
      } catch (\Exception $e) {
          return api_output_error(1003, $e->getMessage());
      }

  }

    /**
     * @return \json
     * 删除预约
     */
  public function delSport(){
      $param['appoint_id']=$this->request->param('appoint_id', 0, 'intval');//id
      try {
          if(empty($param['appoint_id'])){
              return api_output_error(1003, "缺少必要参数");
          }
          $list=(new LifeToolsAppointService())->delSport($param);
          if($list){
              return api_output(0, []);
          }else{
              return api_output_error(1003, "删除失败");
          }
      } catch (\Exception $e) {
          return api_output_error(1003, $e->getMessage());
      }
  }
    /**
     * 用户报名信息
     */
  public function lookAppointUser(){
      $param['appoint_id']=$this->request->param('appoint_id', 0, 'intval');//id
      $param['page_size']=$this->request->param('page_size', 10, 'intval');//id
      try {
          if(empty($param['appoint_id'])){
              return api_output_error(1003, "缺少必要参数");
          }
          $list=(new LifeToolsAppointService())->lookAppointUser($param);
      } catch (\Exception $e) {
          return api_output_error(1003, $e->getMessage());
      }
      return api_output(0, $list);
  }

    /**
     * 导出预约报名信息
     * @return \json
     */
    public function exportUserOrder()
    {
        $param['type'] = 'pc';
        $param['appoint_id']=$this->request->param('appoint_id', 0, 'intval');//id
        $param['re_type'] = 'platform';
        $param['use_type'] = 2; //1=列表使用  2= 导出使用
        $orderService = new LifeToolsAppointService();
        try {
            $arr = $orderService->addOrderExport($param, $this->systemUser, []);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 获取预约信息
     */
    public function getToolAppointMsg(){
        $param['appoint_id']=$this->request->param('appoint_id', 0, 'intval');//id
        $orderService = new LifeToolsAppointService();
        try {
            $arr = $orderService->getToolAppointMsg($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            dd($e);
            //return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 保存预约活动
     */
    public function saveToolAppoint(){
        $param['appoint_id']=$this->request->param('appoint_id', 0, 'intval');//id
        $param['title'] = $this->request->param('title', '', 'trim');
        $param['content'] = $this->request->param('content', '', 'trim');
        $param['label'] = $this->request->param('label', '', 'trim');
        $param['phone'] = $this->request->param('phone', '', 'trim');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['price'] = $this->request->param('price', 0, 'trim');
        $param['send_notice_days'] = $this->request->param('send_notice_days', 0, 'intval');
        $param['address'] = $this->request->param('address','', 'trim');
        $param['long'] = $this->request->param('long','', 'trim');
        $param['lat'] = $this->request->param('lat','', 'trim');
        $param['province_id'] = $this->request->param('province_id',0, 'intval');
        $param['city_id'] = $this->request->param('city_id',0, 'intval');
        $param['area_id'] = $this->request->param('area_id',0, 'intval');
        $param['image_big'] = $this->request->param('image_big','', 'trim');
        $param['image_small'] = $this->request->param('image_small','', 'trim');
        $param['limit_type'] = $this->request->param('limit_type',0, 'intval');
        $param['limit_num'] = $this->request->param('limit_num',0, 'intval');
        $param['need_verify'] = $this->request->param('need_verify',0, 'intval');
        $param['people_type'] = $this->request->param('people_type',0, 'intval');
        $param['can_refund'] = $this->request->param('can_refund',0, 'intval');
        $param['refund_hours'] = $this->request->param('refund_hours',0, 'intval');
        $param['appoint_start_time'] = $this->request->post('appoint_start_time') ?? '';
        $param['appoint_end_time'] = $this->request->post('appoint_end_time') ?? '';
        $param['appoint_btn_txt'] = $this->request->post('appoint_btn_txt') ?? '';
        $orderService = new LifeToolsAppointService();
        try {
            $arr = $orderService->saveToolAppoint($param);
            if(empty($arr)){
                return api_output_error('1003', "编辑失败");
            }else{
                return api_output(0, $arr, 'success');
            }
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }
}