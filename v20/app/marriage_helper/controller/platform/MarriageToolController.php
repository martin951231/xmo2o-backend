<?php
/**
 * 结婚controller
 * @author mrdeng
 * Date Time: 2021/05/31
 */

namespace app\marriage_helper\controller\platform;

use app\BaseController;
use app\marriage_helper\model\service\MarriageToolService;

class MarriageToolController extends BaseController
{
    /**
     * 结婚攻略列表
     */
    public function toolList()
    {
        try {
            $list = (new MarriageToolService())->toolList();
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
        $param['cat_fid'] = $this->request->param("cat_fid", "0", "intval");
        try {
            if ($param['cat_fid']) {
                $list = (new MarriageToolService())->childList($param);
                return api_output(0, $list);
            } else {
                return api_output_error(1001, L_('参数缺失'));
            }
        } catch (\Exception $e) {
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
            (new MarriageToolService())->changeSort($sortList);
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
                $ret = (new MarriageToolService())->childChangeSort($param);
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
        $param['cat_description'] = $this->request->param("cat_description", "", "trim");
        $param['cat_url'] = $this->request->param("cat_url", "", "trim");
        $param['logo_title'] = $this->request->param("logo_title", "", "trim");
        $param['add_time'] = time();
        try {
            if (empty($param['cat_title'])) {
                return api_output_error(1001, L_('请填写子攻略名称'));
            }

            if ($param['cat_fid']) {
                if (empty($param['cat_description'])) {
                    return api_output_error(1001, L_('请填写描述文案'));
                }
            }

            $ret = (new MarriageToolService())->addCategory($param);
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
        $param['cat_fid'] = $this->request->param("cat_fid", 0, "intval");
        $param['cat_title'] = $this->request->param("cat_title", "", "trim");
        $param['cat_description'] = $this->request->param("cat_description", "", "trim");
        $param['cat_url'] = $this->request->param("cat_url", "", "trim");
        $param['logo_title'] = $this->request->param("logo_title", "", "trim");
        $param['last_time'] = time();
        try {
            if (empty($param['cat_title'])) {
                return api_output_error(1001, L_('请填写子攻略名称'));
            }
            if ($param['cat_fid'] > 0) {
                if (empty($param['cat_description'])) {
                    return api_output_error(1001, L_('请填写描述文案'));
                }
            }
            $ret = (new MarriageToolService())->updateCategory($param);
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
                $ret = (new MarriageToolService())->delCategory($param);
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
                $ret = (new MarriageToolService())->editCategory($param);
                if ($ret) {
                    return api_output(0, $ret);
                } else {
                    return api_output_error(1003, L_('获取失败'));
                }
            } else {
                return api_output_error(1001, L_('参数缺失'));
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }
}