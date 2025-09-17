<?php
namespace app\recruit\controller\platform;

use app\BaseController;
use app\recruit\model\service\RecruitIndustryService;

class RecruitIndustryController extends BaseController
{
    /**
     * 列表
     * @author deng
     */
   public function getRecruitIndustryList(){
       $page = $this->request->param('page', 1, 'intval');
       $pageSize = $this->request->param('pageSize',10,'intval');
       $list=(new RecruitIndustryService())->getRecruitIndustryList($page, $pageSize);

       return api_output(0, $list);
   }

    /**
     * 保存
     */
    public function getRecruitIndustryCreate(){
        $id = $this->request->param('id', 0, 'trim');
        $params['fid'] = $this->request->param('fid', 0, 'trim');
        $params['name'] = $this->request->param('name', '', 'trim');
        $params['sort'] = $this->request->param('sort', 0, 'trim');
        if(empty($params['name'])){
            return api_output_error(1001, L_('行业名称不能为空'));
        }
        $result = (new RecruitIndustryService())->getRecruitIndustryCreate($id, $params);
        return api_output(1000, $result);
    }

    /**
     * 单条
     */
    public function getRecruitIndustryInfo(){
        $id = $this->request->param('id', 0, 'trim');
        $result = (new RecruitIndustryService())->getRecruitIndustryInfo($id);
        return api_output(1000, $result);
    }

    /**
     * 排序
     */
    public function getRecruitIndustrySort(){
        $id = $this->request->param('id', 0, 'intval');
        $sort = $this->request->param('sort', 0, 'intval');
        $list=(new RecruitIndustryService())->getRecruitIndustrySort($id, $sort);
 
        return api_output(0, $list);
    }

    /**
     * 删除
     */
    public function getRecruitIndustryDel(){
        $id = $this->request->param('id', 0, 'intval');
        $list=(new RecruitIndustryService())->getRecruitIndustryDel($id);
 
        return api_output(0, $list);
    }

    /**
     * 二级列表
     * @author deng
     */
    public function getRecruitIndustryLevelList(){
        $fid = $this->request->param('fid', 0, 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize',10,'intval');
        $list=(new RecruitIndustryService())->getRecruitIndustryLevelList($fid, $page, $pageSize);
 
        return api_output(0, $list);
    }
 
 }