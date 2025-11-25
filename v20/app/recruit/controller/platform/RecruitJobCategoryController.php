<?php


namespace app\recruit\controller\platform;


use app\BaseController;
use app\recruit\model\service\RecruitJobCategoryService;

class RecruitJobCategoryController extends BaseController
{
    /**
     * 职位分类列表
     */
    public function categoryList()
    {
        try {
            $list = (new RecruitJobCategoryService())->categoryList();
            return api_output(0, $list);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

    }

    /**
     * 子分类列表
     */
    public function childList()
    {
        $param['cat_fid'] = $this->request->param("cat_id", "0", "intval");
        $param['cat_title'] = $this->request->param("cat_title", "", "trim");
        $param['page'] = $this->request->param("page", "0", "intval");
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        try {
            if ($param['cat_fid']) {
                $list = (new RecruitJobCategoryService())->childList($param);
                return api_output(0, $list);
            } else {
                return api_output_error(1001, L_('参数缺失'));
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     *获取分类
     */
    public function getCategory(){
        $param['cat_id'] = $this->request->param("cat_id", "0", "intval");
        try {
            if ($param['cat_id']) {
                $list = (new RecruitJobCategoryService())->getCategory($param);
                return api_output(0, $list);
            } else {
                return api_output_error(1001, L_('参数缺失'));
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 重新绑定给计划分类
     */
    public function byOtherCategory(){
        $param['cat_id'] = $this->request->param("new_id", 0, "trim");
        $param['ids'] = $this->request->param("sel_cat", 0, "trim");
        try {
            if ($param['cat_id']==0) {
                return api_output_error(1003, L_('请选择分类'));
            }
            if ($param['ids']==0) {
                return api_output_error(1003, L_('缺少选择的分类id'));
            }
            $ret = (new RecruitJobCategoryService())->byOtherCategory($param);
            if ($ret) {
                return api_output(0, 0);
            } else {
                return api_output_error(1003, L_('修改失败'));
            }
        }catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }
    /**
     *  分类拖拽排序
     */
    public function changeSort()
    {
        $sortList = $this->request->param("sort_list", "", "");
        try {
            (new RecruitJobCategoryService())->changeSort($sortList);
            return api_output(0, 0);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     *  子分类拖拽排序
     */
    public function childChangeSort()
    {
        $param['cat_id'] = $this->request->param("cat_id", "0", "intval");
        $param['sort'] = $this->request->param("sort", "0", "intval");
        try {
            if ($param['cat_id'] && $param['sort']) {
                $ret = (new RecruitJobCategoryService())->childChangeSort($param);
                if ($ret) {
                    return api_output(0, 0);
                } else {
                    return api_output_error(1003, L_('更新失败'));
                }
            } else {
                return api_output_error(1001, L_('参数缺失'));
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     *新增分类
     */
    public function addCategory()
    {
        $param['cat_fid'] = $this->request->param("cat_fid", "0", "intval");
        $param['cat_title'] = $this->request->param("cat_title", "", "trim");
        $param['level'] = $this->request->param("level", "1", "intval");
        $param['sort'] = $this->request->param("sort",0, "intval");
        $param['add_time'] = time();
        try {
            if (empty($param['cat_title'])) {
                return api_output_error(1001, L_('请填写标题名称'));
            }

            $ret = (new RecruitJobCategoryService())->addCategory($param);
            if ($ret) {
                return api_output(0, 0);
            } else {
                return api_output_error(1003, L_('新增失败'));
            }

        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     *  更新分类
     */
    public function updateCategory()
    {
        $param['cat_id'] = $this->request->param("cat_id", "0", "intval");
        $param['cat_title'] = $this->request->param("cat_title", "", "trim");
        $param['sort'] = $this->request->param("sort");
        if(!isset($param['sort'])){
            unset($param['sort']);
        }
        $param['last_time'] = time();
        try {
            if (empty($param['cat_title'])) {
                return api_output_error(1001, L_('请填写标题名称'));
            }
            $ret = (new RecruitJobCategoryService())->updateCategory($param);
            if ($ret) {
                return api_output(0, 0);
            } else {
                return api_output_error(1003, L_('修改失败'));
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     *删除分类
     */
    public function delCategory()
    {
        $param['cat_id'] = $this->request->param("cat_id", '', "trim");
        $param['is_del'] = $this->request->param("is_del", 1, "intval");
        try {
            if ($param['cat_id']) {
                $ret = (new RecruitJobCategoryService())->delCategory($param);
                if ($ret) {
                    return api_output(0, 0);
                } else {
                    return api_output_error(1003, L_('删除失败'));
                }
            } else {
                return api_output_error(1001, L_('参数缺失'));
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     *批量删除分类
     */
    public function delCategorys()
    {
        $param['cat_id'] = $this->request->param("cat_ids", '', "trim");
        $param['is_del'] = $this->request->param("is_del", 1, "intval");
        try {
            if ($param['cat_id']) {
                $ret = (new RecruitJobCategoryService())->delCategory($param);
                if ($ret) {
                    return api_output(0, 0);
                } else {
                    return api_output_error(1003, L_('删除失败'));
                }
            } else {
                return api_output_error(1001, L_('参数缺失'));
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 编辑分类
     */
    public function editCategory()
    {
        $param['cat_id'] = $this->request->param("cat_id", 0, "intval");
        try {
            if ($param['cat_id']) {
                $ret = (new RecruitJobCategoryService())->editCategory($param);
                if ($ret) {
                    return api_output(0, $ret);
                } else {
                    return api_output_error(1003, L_('该分类有在发布的职位不能编辑'));
                }
            } else {
                return api_output_error(1001, L_('参数缺失'));
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取子分类
     */
    public function getChildCategory(){
        $param['cat_fid'] = $this->request->param("cat_fid", 0, "intval");
        try {
            if ($param['cat_fid']) {
                $ret = (new RecruitJobCategoryService())->getChildCatList($param);
                return api_output(0, $ret);
            } else {
                return api_output_error(1001, L_('参数缺失'));
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     *更新三级分类
     */
    public function updateChildCategory(){
        $param['cat_arr'] = $this->request->param("cat_arr", "", "trim");
        $param['cat_fid'] = $this->request->param("cat_fid", "", "intval");
        try {
            if (empty($param['cat_arr'])) {
                return api_output_error(1001, L_('不能提交空数据'));
            }
            if ($param['cat_fid']) {
                $ret = (new RecruitJobCategoryService())->updateChildCategory($param);
                return api_output(0, $ret);
            } else {
                return api_output_error(1001, L_('参数缺失'));
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }
}