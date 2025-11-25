<?php


namespace app\new_marketing\controller\api;


use app\new_marketing\model\service\PersonDetailService;

class PersonDetailController extends ApiBaseController
{
    /**
     * 个人详情
     */
  public function getPersonDetail(){
      $param['identity'] = $this->request->param('identity', 0, 'intval');//0业务人员 1业务经理 3技术员 4技术主管
      $param['person_id'] = $this->request->param('person_id', 0, 'intval');//人员id
      if (empty($param['person_id'])) {
          return api_output_error(1002, "缺少人员id");
      }
      $arr=(new PersonDetailService())->getPersonDetail($param);
      if($arr['is_error']){
          return api_output_error(1003, $arr['msg']);
      }else{
          return api_output(0, $arr, '获取成功');
      }
  }
}