<?php


namespace app\life_tools\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\LifeToolsCompetitionService;
use error_msg\GetErrorMsg;

class LifeToolsCompetitionController extends AuthBaseController
{
    /**
     * 赛事列表
     */
  public function getList(){
      $param['title']=$this->request->param('title', '', 'trim');//活动标题
      $param['start_time']=$this->request->param('start_time', '', 'trim');//活动开始时间
      $param['end_time']=$this->request->param('end_time', '', 'trim');//活动结束时间
      $param['page']=$this->request->param('page', 1, 'intval');//页码
      $param['pageSize']=$this->request->param('pageSize', 10, 'intval');//每页展示数量
      try {
          $list=(new LifeToolsCompetitionService())->getList($param);
      } catch (\Exception $e) {
          return api_output_error(1003, $e->getMessage());
      }
      return api_output(0, $list);
  }

    /**
     * 关闭赛事
     */
  public function closeCompetition(){
      $param['status']=$this->request->param('status', 0, 'intval');//状态
      $param['competition_id']=$this->request->param('competition_id', 0, 'intval');//id
      try {
          if(empty($param['competition_id'])){
              return api_output_error(1003, "缺少必要参数");
          }
          $list=(new LifeToolsCompetitionService())->closeCompetition($param);
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
     * 删除赛事
     */
  public function delSport(){
      $param['competition_id']=$this->request->param('competition_id', 0, 'intval');//id
      try {
          if(empty($param['competition_id'])){
              return api_output_error(1003, "缺少必要参数");
          }
          $list=(new LifeToolsCompetitionService())->delSport($param);
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
    public function lookCompetitionUser()
    {
        $param = [];
        $param['competition_id'] = $this->request->param('competition_id', 0, 'intval');//id
        $param['page_size'] = $this->request->post('page_size', 10, 'intval');
        $param['search_type'] = $this->request->post('search_type', null, 'intval');
        $param['keywords'] = $this->request->post('keywords', '', 'trim');
        $param['status'] = $this->request->post('status', null, 'intval');
        $param['audit_status'] = $this->request->post('audit_status', null, 'intval');
        try {
            if(empty($param['competition_id'])){
                throw new \think\Exception("缺少必要参数");
            }
            $list=(new LifeToolsCompetitionService())->lookCompetitionUser($param);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 导出赛事报名信息
     * @return \json
     */
    public function exportUserOrder()
    {
        $param['type'] = 'pc';
        $param['competition_id'] = $this->request->param('competition_id', 0, 'intval');//id
        $param['page_size'] = $this->request->post('page_size', 10, 'intval');
        $param['search_type'] = $this->request->post('search_type', null, 'intval');
        $param['keywords'] = $this->request->post('keywords', '', 'trim');
        $param['status'] = $this->request->post('status', null, 'intval');
        $param['audit_status'] = $this->request->post('audit_status', null, 'intval');
        // $param['content'] = $this->request->param('content', '', 'trim');
        // $param['begin_time'] = $this->request->param('begin_time', '', 'trim');
        // $param['end_time'] = $this->request->param('end_time', '', 'trim');
        // $param['express_type'] = $this->request->param('express_type', '', 'intval');
        // $param['act'] = $this->request->param('act', '', 'trim');
        // $param['pay'] = $this->request->param('pay', '', 'trim');
        // $param['source'] = $this->request->param('source', '', 'trim');
        // $param['mer_id'] = $this->request->param('mer_id', '', 'intval');
        // $param['store_id'] = $this->request->param('store_id', '', 'intval');
        // $areaList = $this->request->param('areaList');
        // $param['province_id'] = isset($areaList[0]) ? $areaList[0] : 0;
        // $param['city_id'] = isset($areaList[1]) ? $areaList[1] : 0;
        // $param['area_id'] = isset($areaList[2]) ? $areaList[2] : 0;
        // $param['status'] = $this->request->param('status', '1', 'intval');
        $param['re_type'] = 'platform';
        $param['use_type'] = 2; //1=列表使用  2= 导出使用
        $orderService = new LifeToolsCompetitionService();
        try {
            $arr = $orderService->addOrderExport($param, $this->systemUser, []);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 获取赛事信息
     */
    public function getToolCompetitionMsg(){
        $param['competition_id']=$this->request->param('competition_id', 0, 'intval');//id
        $orderService = new LifeToolsCompetitionService();
        try {
            $arr = $orderService->getToolCompetitionMsg($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            dd($e);
            //return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 保存赛事活动
     */
    public function saveToolCompetition(){
        $param['competition_id']=$this->request->param('competition_id', 0, 'intval');//id
        $param['title'] = $this->request->param('title', '', 'trim');
        $param['content'] = $this->request->param('content', '', 'trim');
        $param['label'] = $this->request->param('label', '', 'trim');
        $param['phone'] = $this->request->param('phone', '', 'trim');
        $param['member_type'] = $this->request->param('member_type', '', 'trim');
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

        $param['is_custom'] = $this->request->param('is_custom',0, 'intval');
        $param['custom_form'] = $this->request->param('custom_form');
        $param['is_audit'] = $this->request->param('is_audit',0, 'intval');
        $param['audit_user'] = $this->request->post('audit_user', []);
        $param['certificate_bgimg'] = $this->request->param('certificate_bgimg', '', 'trim');
        $param['is_invoice'] = $this->request->param('is_invoice',0, 'intval');
        $orderService = new LifeToolsCompetitionService();
        try {
            $arr = $orderService->saveToolCompetition($param);
            if(empty($arr)){
                return api_output_error('1003', "编辑失败");
            }else{
                return api_output(0, $arr, 'success');
            }
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 获取管理员列表
     */
    public function getAuditAdminList()
    {
        $orderService = new LifeToolsCompetitionService();
        try {
            $arr = $orderService->getAuditAdminList();
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 我的审核
     */
    public function getMyAuditList()
    {
        $params = [];
        $params['search_type'] = $this->request->post('search_type', 0, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['status'] = $this->request->post('status', null, 'intval');
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        $params['admin_id'] = $this->_uid;
        $orderService = new LifeToolsCompetitionService();
        try {
            $arr = $orderService->getMyAuditList($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            dd($e);
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 获取审核数量统计
     */
    public function getMyAuditCount()
    {
        $orderService = new LifeToolsCompetitionService();
        try {
            $arr = $orderService->getMyAuditCount($this->_uid);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 我的审核详情
     */
    public function getMyAuditInfo()
    {
        $params = [];
        $params['id'] = $this->request->post('id', 0, 'intval');
        $params['admin_id'] = $this->_uid;
        $orderService = new LifeToolsCompetitionService();
        try {
            $arr = $orderService->getMyAuditInfo($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 管理员审核
     */
    public function audit()
    {
        $params = [];
        $params['id'] = $this->request->post('id', 0, 'intval');
        $params['status'] = $this->request->post('status', 0, 'intval');
        $params['remark'] = $this->request->post('remark', '', 'trim');
        $params['admin_id'] = $this->_uid;
        $orderService = new LifeToolsCompetitionService();
        try {
            $arr = $orderService->audit($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }
}