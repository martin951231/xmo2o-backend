<?php

/**
 * 服务分类控制器
 */

namespace app\life_tools\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\LifeToolsCategoryService;

class LifeToolsCategoryController extends AuthBaseController
{    
    
    /**
    * 获取列表
    * @return \json
    */
   public function getList()
   {
      
       $service = new LifeToolsCategoryService();
      
       $param['type'] = $this->request->param('type', '', 'trim');
       $arr = $service->getCategoryList($param);
       return api_output(0, $arr, 'success');
   }
    /**
     * 新增或编辑
     * @return \json
     */
    public function addOrEdit()
    {
       
        $param['sort'] = $this->request->param('sort', '0', 'intval');
        $param['cat_name'] = $this->request->param('cat_name', '', 'trim');
        $param['cat_id'] = $this->request->param('cat_id', '0', 'intval');
        $param['type'] = $this->request->param('type', '', 'trim');
        $service = new LifeToolsCategoryService();
       
        $res = $service->addOrEdit($param);
        return api_output(0, $res, 'success');       
    }

    
    /**
     * 编辑排序值
     * @return \json
     */
    public function editSort()
    {
       
        $param['sort'] = $this->request->param('sort', '0', 'intval');
        $param['cat_id'] = $this->request->param('cat_id', '0', 'intval');
        $service = new LifeToolsCategoryService();
       
        $res = $service->editSort($param);
        return api_output(0, $res, 'success');       
    }

    /**
     * 获得详情
     * @return \json
     */
    public function getDetail()
    {
        $param['cat_id'] = $this->request->param('cat_id', '', 'intval');
        $service = new LifeToolsCategoryService();
        $arr = $service->getDetail($param);
        return api_output(0, $arr, 'success');
    }    

    /**
     * 删除
     * @return \json
     */
    public function del()
    {
        $param['cat_id'] = $this->request->param('cat_id', '', 'intval');
        $service = new LifeToolsCategoryService();
        $res = $service->del($param);
        return api_output(0, $res, 'success');
    }
}