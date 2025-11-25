<?php
/**
 * 人才管理
 */

namespace app\recruit\controller\platform;


use app\BaseController;
use app\recruit\model\service\TalentManagementService;

class TalentManagementController extends BaseController
{
    /**
     * @return \json
     * 人才管理列表
     */
   public function getList(){

           $params['page'] = $this->request->param('page', '1', 'intval');
           $params['pageSize'] = $this->request->param('pageSize', '10', 'intval');

           $params['education'] = $this->request->param('education');
           $params['user_name'] = $this->request->param('user_name');
           $params['job_age'] = $this->request->param('job_age');
           $params['sex'] = $this->request->param('sex');
           $params['job_id'] = $this->request->param('job_id');
           $params['status'] = $this->request->param('status');
           $where = [];
           if(isset($params['education']) && $params['education']!=""){
               array_push($where,['j.education','=',$params['education']]);
           }
           if(isset($params['user_name']) && $params['user_name']!=""){
               array_push($where,['r.name','like','%'.$params['user_name'].'%']);
           }
           if(isset($params['job_age']) && $params['job_age']!=0){
               array_push($where,['j.job_age','=',$params['job_age']]);
           }
           if(isset($params['sex']) && $params['sex']!=0){
               array_push($where,['r.sex','=',$params['sex']]);
           }
           if(isset($params['job_id']) && $params['job_id']!=0){
               array_push($where,['j.job_id','=',$params['job_id']]);
           }
           if(isset($params['status']) && $params['status']!=""){
               array_push($where,['s.inter_status','=',$params['status']]);
           }
           $list=(new TalentManagementService())->getPlatList($where,$params['page'],$params['pageSize']);
           return api_output(1000, $list);
   }

  /**
   * 简历详情
   */
  public function getResumeMsg(){
          $params['id'] = $this->request->param('id', '', 'intval');
          $list=(new TalentManagementService())->getResumeMsg($params['id']);
          return api_output(1000, $list);
  }

    /**
     * 历史记录
     */
  public function getLibMsgLIst(){
          $params['resume_id'] = $this->request->param('resume_id', '', 'intval');
          $params['deliver_id'] = $this->request->param('deliver_id', '', 'intval');
          /*$params['uid'] = 112358755;
          $params['job_id'] = 1;*/
          $list=(new TalentManagementService())->getLibMsg($params['resume_id'],$params['deliver_id']);
          return api_output(1000, $list);
  }
}