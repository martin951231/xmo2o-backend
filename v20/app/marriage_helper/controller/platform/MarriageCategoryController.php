<?php
/**
 * 分类管理controller
 * @author wangchen
 * Date Time: 2021/6/4
 */

namespace app\marriage_helper\controller\platform;

use app\BaseController;
use app\marriage_helper\model\service\MarriageCategoryService;

class MarriageCategoryController extends BaseController
{
    /**
     * 分类列表
     * @return \json
     */
    public function getCategoryList()
    {
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        try {
            $arr = (new MarriageCategoryService())->getCategoryList($page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 分类岗位列表
     * @return \json
     */
    public function getCategoryPositionList()
    {
        try {
            $arr = (new MarriageCategoryService())->getCategoryPositionList();
            foreach($arr as $k=>$v){
                $arr[$k]['pos_id'] = (string)$v['id'];
            }
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 分类操作
     * @return \json
     */
    public function getCategoryCreate()
    {
        $cat_id = $this->request->param('cat_id', 0, 'intval');
        $pos_id = $this->request->param('pos_id', 0, 'intval');
        $cat_name = $this->request->param('cat_name', '', 'trim');
        $sort = $this->request->param('sort', 0, 'intval');
        $status = $this->request->param('status', 0, 'intval');
        try {
            $arr = (new MarriageCategoryService())->getCategoryCreate($cat_id, $pos_id, $cat_name, $sort, $status);
            return api_output(0, $arr, 'success');

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 分类详情
     * @return \json
     */
    public function getCategoryInfo()
    {
        $cat_id = $this->request->param('cat_id', 0, 'intval');
        try {
            $arr = (new MarriageCategoryService())->getCategoryInfo($cat_id);
            return api_output(0, $arr, 'success');

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 分类排序
     * @return \json
     */
    public function getCategorySort()
    {
        $sort = $this->request->param('sort', '0', 'intval');
        $cat_id = $this->request->param('cat_id', '0', 'intval');
        $result = (new MarriageCategoryService())->getCategorySort($cat_id, $sort);
        return api_output(1000, $result);
    }

    /**
     * 分类删除
     * @return \json
     */
    public function getCategoryDelAll()
    {
        $cat_id = $this->request->param('ids', '', 'trim');
        try {
            $result = (new MarriageCategoryService())->getCategoryDelAll($cat_id);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}