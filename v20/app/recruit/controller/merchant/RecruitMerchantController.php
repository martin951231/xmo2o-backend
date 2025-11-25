<?php

namespace app\recruit\controller\merchant;

use app\common\model\service\MerchantService;
use app\recruit\model\service\JobService;
use app\merchant\controller\merchant\AuthBaseController;

class RecruitMerchantController extends AuthBaseController
{
    public $merId;

    public function initialize()
    {
        parent::initialize();
        $this->merId = $this->merchantUser['mer_id'] ?? 0;
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
       $author = $this->request->param('author');
       $condition = [
        'mer_id' => $this->merId
       ];
       if($education >= 0){
          $condition['education'] = $education;
       }
       if($job_age !== ''){
          $condition['job_age'] = $job_age;
       }
       if($status >= 0){
          $condition['status'] = $status;
       }
       if($author > 0){
          $condition['author'] = $author;
       }
       if(!empty($keywords)){
          $condition['keyword'] = $keywords;
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
    * 更新职位状态
    * @return [type] [description]
    */
  public function updateJob(){
      $job_id = $this->request->param('job_id', 0, 'intval');
      $status = $this->request->param('status');
      $JobService = new JobService();
      $r = $JobService->updateJob($job_id, ['status' => $status]);
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
      'hrlist' => [],
    ];

    foreach ($JobService->education as $key => $value) {
      $output['education'][] = [
        'education_id' => $key,
        'education' => $value,
      ];
    }
    $JobService = new JobService();
    $output['cates'] = $JobService->getAllCates($this->merId);
    $output['hrlist'] = $JobService->getAllHr($this->merId);
    return api_output(0, $output);
  }
}