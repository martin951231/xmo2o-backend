<?php


namespace app\recruit\controller\api;


use app\recruit\model\service\RecruitInterviewNoticeService;

class RecruitInterviewNoticeController extends ApiBaseController
{
  // 面试通知列表
  public function interviewNoticeList(){
      $param['uid'] = $this->_uid;
      if (!$param['uid']) {
          return api_output_error(1002, L_('请登录'));
      }
      $data['page']=$this->request->param('page', '1', 'intval');
      $data['pageSize']=$this->request->param('pageSize', '100', 'intval');
      $list=(new RecruitInterviewNoticeService())->interviewNoticeList($param['uid'],$data['page'],$data['pageSize']);
      return api_output(0, $list);
  }
}