<?php
namespace app\recruit\controller\platform;

use app\BaseController;
use app\recruit\model\service\RecruitWelfareService;

class RecruitWelfareController extends BaseController
{
    /**
     * 列表
     * @author deng
     */
   public function getRecruitWelfareList(){
       $page = $this->request->param('page', 0, 'intval');
       $pageSize = $this->request->param('pageSize',10,'intval');
       $list=(new RecruitWelfareService())->getRecruitWelfareList($page, $pageSize);

       return api_output(0, $list);
   }

    /**
     * 保存
     */
    public function getRecruitWelfareCreate(){
        $id = $this->request->param('id', 0, 'trim');
        $params['name'] = $this->request->param('name', '', 'trim');
        $result = (new RecruitWelfareService())->getRecruitWelfareCreate($id, $params);
        return api_output(1000, $result);
    }

    /**
     * 单条
     */
    public function getRecruitWelfareInfo(){
        $id = $this->request->param('id', 0, 'trim');
        $result = (new RecruitWelfareService())->getRecruitWelfareInfo($id);
        return api_output(1000, $result);
    }

    /**
     * 删除
     */
    public function getRecruitWelfareDel(){
        $id = $this->request->param('id', 0, 'intval');
        $list=(new RecruitWelfareService())->getRecruitWelfareDel($id);
 
        return api_output(0, $list);
    }

 }