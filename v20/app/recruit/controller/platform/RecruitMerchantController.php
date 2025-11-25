<?php

namespace app\recruit\controller\platform;
use app\BaseController;
use app\common\model\service\MerchantService;
use app\recruit\model\db\NewRecruitCompany;
use app\recruit\model\service\JobService;
use app\merchant\model\service\MerchantService as MerchantServiceOwn;

class RecruitMerchantController extends BaseController
{
    /**
     * 获取商家列表
     * @author deng
     */
   public function getMerchantList(){
       $page = $this->request->param('page', '', 'intval');
       $pageSize = $this->request->param('pageSize',10,'intval');
       $recruit_status = $this->request->param('recruit_status','','trim');
       $name = $this->request->param('name','','trim');
       $where=array();
       $where[]=['s.status','=',1];
       if($recruit_status!=3){
           $where[]=['s.recruit_status','=',$recruit_status];
       }
       if(!empty($name)) {
           $where[]=['s.name','like','%'.$name.'%'];
       }
       $list=(new MerchantService())->getSome1($where,'s.mer_id,s.name,s.recruit_status,s.recruit_sort,s.recruit_publish_nums','s.recruit_sort desc,s.mer_id desc',0,0);

       return api_output(0, $list);
   }

    /**
     * @return \json
     * 更新
     */
   public function updateMerchant(){
       $data['mer_id'] = $this->request->param('mer_id', '', 'intval');
       $data['recruit_sort'] = $this->request->param('recruit_sort', '', 'trim');
       $data['recruit_status'] = $this->request->param('recruit_status', '', 'trim');
       if(empty($data['mer_id']) || empty($data)){
           return api_output_error(1003, L_('参数缺失'));
       }
       $ret=(new MerchantService())->updateThis($data);
       if(!$ret){
           return api_output_error(1003, L_('更新失败'));
       }else{
           return api_output(0, []);
       }
   }

   /**
     * 获取职位列表
     */
   public function getJobList(){
       $page = $this->request->param('page', 1, 'intval');
       $pageSize = $this->request->param('pageSize',10,'intval');
       $education = $this->request->param('education',-1,'intval');
       $job_age = $this->request->param('job_age');
       $status = $this->request->param('status');
       $keywords = trim($this->request->param('keywords'));
       $cates = $this->request->param('cates');
       $mer_id = $this->request->param('mer_id', 0, 'intval');
       $condition = [];
       if($education >= 0){
          $condition['education'] = $education;
       }
       if($job_age !== ''){
          $condition['job_age'] = $job_age;
       }
       if($status >= 0){
          $condition['status'] = $status;
       }
       if(!empty($keywords)){
          $condition['keyword'] = $keywords;
       }
       if(!empty($mer_id)){
          $condition['mer_id'] = $mer_id;
       }
       if(!empty($cates)){
        if(isset($cates[0])){
          $condition['first_cate'] = $cates[0];
        }
        if(isset($cates[1])){
          $condition['second_cate'] = $cates[1];
        }
        if(isset($cates[2])){
          $condition['third_cate'] = $cates[2];
        }
       }

       $JobService = new JobService();
       $list = $JobService->getJobList($condition, $page, 0);

       return api_output(0, $list);
   }

   public function getJobDetail(){
    $job_id = $this->request->param('job_id');
    if(empty($job_id)){
      return api_output_error(1003, L_('参数缺失'));
    }
    $JobService = new JobService();
    $detail = $JobService->getJobDetail($job_id);
    return api_output(0, $detail);
   }

   /**
    * 更新职位推荐
    * @return [type] [description]
    */
  public function updateJob(){
      $job_id = $this->request->param('job_id', 0, 'intval');
      $status = $this->request->param('status');
      $JobService = new JobService();
      $r = $JobService->updateJob($job_id, ['is_recom' => $status]);
      if($r !== false){
        return api_output(0, []);
      }
      else{
        return api_output(1003, L_('更新失败'));
      }
  }

  /**
    * 删除职位
    * @return [type] [description]
    */
  public function delJob(){
      $job_id = $this->request->param('job_id', 0, 'intval');
      $JobService = new JobService();
      $r = $JobService->updateJob($job_id, ['is_del' => 1]);
      if($r !== false){
        return api_output(0, []);
      }
      else{
        return api_output(1003, L_('删除失败'));
      }
  }

  public function getJobSearch(){
    $JobService = new JobService();
    $output = [
      'education' => [
        [
          'education_id' => -1,
          'education' => '全部学历'
        ]
      ],
      'cates' => [],
      'merlist' => [],
    ];

    foreach ($JobService->education as $key => $value) {
      $output['education'][] = [
        'education_id' => $key,
        'education' => $value,
      ];
    }
    $JobService = new JobService();
    $output['cates'] = $JobService->getAllCates();
    $arr  = (new NewRecruitCompany())->getCompanyByMer([['s.recruit_status', '=', 1]], 's.mer_id, s.name', 's.mer_id asc')->toarray();

    array_unshift($arr, ['mer_id' => 0, 'name' => "全部商家"]);
    $output['merlist']= $arr;

    return api_output(0, $output);
  }
}