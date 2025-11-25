<?php

/**
 * 寻人求助
 */
namespace app\life_tools\controller\api;


use app\life_tools\model\service\LifeToolsHelpService;

class LifeToolsHelpController extends ApiBaseController
{
    /**
     * 寻人求助列表
     */
  public function helpList(){
      $param['uid']=$this->_uid;
      $param['page']   = $this->request->param('page', 1, 'intval');
      $param['pageSize']   = $this->request->param('pageSize', 10, 'intval');
      if(empty($param['uid'])){
          return api_output_error(1002, "请登录");
      }
      $list=(new LifeToolsHelpService())->helpList($param);
      return api_output(0, $list, 'success');
  }

    /**
     * 求助详情
     */
  public function helpDetail(){
      $param['pigcms_id']   = $this->request->param('pigcms_id', 0, 'intval');
      $param['uid']=$this->_uid;
      if(empty($param['pigcms_id'])){
          return api_output_error(1003, "缺少必要参数");
      }
      $ret=(new LifeToolsHelpService())->helpDetail($param);
      return api_output(0, $ret, 'success');
  }

    /**
     * 求助是否解决
     */
    public function helpUpdate(){
        $param['pigcms_id']   = $this->request->param('pigcms_id', 0, 'intval');
        $param['is_solve']   = $this->request->param('is_solve', 0, 'intval');
        if(empty($param['pigcms_id'])){
            return api_output_error(1003, "缺少必要参数");
        }
        $ret=(new LifeToolsHelpService())->helpUpdate($param);
        if($ret){
            return api_output(0, [], 'success');
        }else{
            return api_output_error(1003, "状态修改失败");
        }
    }
    /**
     * 发布求助
     */
  public function addHelp(){
      $param['lng']   = $this->request->param('lng', '', 'trim');
      $param['lat']   = $this->request->param('lat', '', 'trim');
      $param['name']   = $this->request->param('name', '', 'trim');
      $param['phone']   = $this->request->param('phone', '', 'trim');
      $param['content']   = $this->request->param('content', '', 'trim');
      $param['add_time']   = time();
      $param['images']   = $this->request->param('images', '', 'trim');
      $param['uid']=$this->_uid;

      if(empty($param['name'])){
          return api_output_error(1003, "求助人必填");
      }

      if(empty($param['phone'])){
          return api_output_error(1003, "求助人联系方式格式不正确");
      }

      if(!preg_match("/^1[34578]\d{9}$/", $param['phone'])) {
          return api_output_error(1003, "求助人联系方式不合理");
      }
      if(empty($param['content'])){
          return api_output_error(1003, "求助内容必填");
      }

      if(!($param['lng'] && $param['lat'])){
          return api_output_error(1003, "获取定位信息失败");
      }

      if(!empty($param['images'])){
          $param['images']=serialize($param['images']);
      }
      $ret=(new LifeToolsHelpService())->addHelp($param);
      if($ret){
          return api_output(0, [], 'success');
      }else{
          return api_output_error(1003, "发布失败");
      }
  }
}