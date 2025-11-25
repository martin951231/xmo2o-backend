<?php


namespace app\marriage_helper\controller\platform;


use app\BaseController;
use app\marriage_helper\model\service\MarriagePlanService;

class MarriagePlanController extends BaseController
{
    /**
     * 结婚计划分类列表
     */
    public function planCategoryList()
    {
        try {
            $list = (new MarriagePlanService())->planCategoryList();
            return api_output(0, $list);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 子分类列表
     */
    public function childPlanList()
    {
        $param['cat_id'] = $this->request->param("cat_id", "0", "intval");
        $param['plan_title'] = $this->request->param("plan_title", "", "trim");
        try {
//            if ($param['cat_id']) {
                $list = (new MarriagePlanService())->childPlanList($param);
                return api_output(0, $list);
//            } else {
//                return api_output_error(1001, L_('参数缺失'));
//            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 拖拽分类排序
     */
    public function changeSort()
    {
        $sortList = $this->request->param("sort_list", "", "");
        try {
            (new MarriagePlanService())->changeSort($sortList);
            return api_output(0, 0);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     *新增分类
     */
    public function addCategory()
    {
        $param['cat_name'] = $this->request->param("cat_name", "", "trim");
        $param['times'] = $this->request->param("times", "", "intval");
        $param['times_type'] = $this->request->param("times_type", "", "intval");
        $param['sort'] = $this->request->param("sort", "", "intval");
        $param['add_time'] = time();
        try {
            if (empty($param['cat_name'])) {
                return api_output_error(1001, L_('请填写分类名称'));
            }
            if (empty($param['times'])) {
                return api_output_error(1001, L_('请填写准备时长'));
            }
            $ret = (new MarriagePlanService())->addCategory($param);
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
     *新增计划
     */
    public function addPlan()
    {
        $param['plan_title'] = $this->request->param("plan_title", "", "trim");
        $param['cat_id'] = $this->request->param("cat_id", "", "intval");
        $param['link_txt'] = $this->request->param("link_txt", "", "trim");
        $param['link'] = $this->request->param("link", "", "trim");
        $param['is_system'] = $this->request->param("is_system", 0, "trim");
        $param['uid'] = $this->request->param("uid", 0, "intval");
        $param['add_time'] = time();
        try {
            if (empty($param['cat_id'])) {
                return api_output_error(1001, L_('分类id缺失'));
            }
            if (empty($param['plan_title'])) {
                return api_output_error(1001, L_('请填计划名称'));
            }
            $ret = (new MarriagePlanService())->addPlan($param);
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
        $param['cat_name'] = $this->request->param("cat_name", "", "trim");
        $param['times'] = $this->request->param("times", "", "intval");
        $param['times_type'] = $this->request->param("times_type", "", "intval");
        $param['cat_id'] = $this->request->param("cat_id", "", "intval");
        try {
            if (empty($param['cat_id'])) {
                return api_output_error(1001, L_('分类id缺失'));
            }
            if (empty($param['cat_name'])) {
                return api_output_error(1001, L_('请填写分类名称'));
            }
            if (empty($param['times'])) {
                return api_output_error(1001, L_('请填写准备时长'));
            }
            $ret = (new MarriagePlanService())->updateCategory($param);
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
     *  更新计划
     */
    public function updatePlan()
    {
        $param['plan_id'] = $this->request->param("plan_id", 0, "intval");
        $param['plan_title'] = $this->request->param("plan_title", "", "trim");
        $param['link_txt'] = $this->request->param("link_txt", "", "trim");
        $param['link'] = $this->request->param("link", "", "trim");
        try {
            if ($param['plan_id'] == 0) {
                return api_output_error(1001, L_('缺少必要参数'));
            }
            $ret = (new MarriagePlanService())->updatePlan($param);
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
        $param['cat_id'] = $this->request->param("cat_id", 0, "trim");
        $param['is_del'] = 1;
        try {
            if ($param['cat_id'] == 0) {
                return api_output_error(1001, L_('缺少必要参数'));
            } else {
                $ret = (new MarriagePlanService())->delCategory($param);
                if ($ret) {
                    return api_output(0, 0);
                } else {
                    return api_output_error(1003, L_('删除失败'));
                }
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     *删除计划
     */
    public function delPlan()
    {
        $param['plan_id'] = $this->request->param("plan_id", 0, "trim");
        $param['is_del'] = 1;
        try {
            if ($param['plan_id'] == 0) {
                return api_output_error(1001, L_('缺少必要参数'));
            } else {
                $ret = (new MarriagePlanService())->delPlan($param);
                if ($ret) {
                    return api_output(0, 0);
                } else {
                    return api_output_error(1003, L_('删除失败'));
                }
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
            if ($param['cat_id'] == 0) {
                return api_output_error(1001, L_('缺少必要参数'));
            } else {
                $ret = (new MarriagePlanService())->editCategory($param);
                if ($ret) {
                    return api_output(0, $ret);
                } else {
                    return api_output_error(1003, L_('获取失败'));
                }
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     *获取分类
     */
    public function getSelCategory(){
        $param['cat_id'] = $this->request->param("cat_id", 0, "trim");
        try {
            $ret = (new MarriagePlanService())->getSelCategory($param);
            if (!empty($ret)) {
                return api_output(0, $ret);
            } else {
                return api_output_error(1003, L_('获取失败'));
            }
        }catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 重新绑定给计划分类
     */
    public function byOtherCategory(){
        $param['cat_id'] = $this->request->param("cat_id", 0, "trim");
        $param['plan_ids'] = $this->request->param("sel_cat", 0, "trim");
        try {
            if ($param['cat_id']==0) {
                return api_output_error(1003, L_('分类id缺失'));
            }
            if ($param['plan_ids']==0) {
                return api_output_error(1003, L_('选择的计划id缺失'));
            }
            $ret = (new MarriagePlanService())->byOtherCategory($param);
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
     * 编辑计划
     */
    public function editPlan()
    {
        $param['plan_id'] = $this->request->param("plan_id", 0, "intval");
        try {
            if ($param['plan_id'] == 0) {
                return api_output_error(1001, L_('缺少必要参数'));
            } else {
                $ret = (new MarriagePlanService())->editPlan($param);
                if ($ret) {
                    return api_output(0, $ret);
                } else {
                    return api_output_error(1003, L_('获取失败'));
                }
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }
}