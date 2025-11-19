<?php
/**
 * 结婚预算controller
 * @author wangchen
 * Date Time: 2021/6/3
 */

namespace app\marriage_helper\controller\platform;

use app\BaseController;
use app\marriage_helper\model\service\MarriageBudgetService;

class MarriageBudgetController extends BaseController
{
    /**
     * 预算列表
     */
    public function getBudgetList()
    {
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $where = [];
        $order = 'sort desc,id desc';
        $list = (new MarriageBudgetService())->getBudgetList($where,$order,$page,$pageSize);
        return api_output(0, $list);
    }

    /**
     *预算操作
     */
    public function getBudgetCreate()
    {
        $id = $this->request->param("id", "0", "intval");
        $name = $this->request->param("name", "", "trim");

        try {
            $arr = (new MarriageBudgetService())->getBudgetCreate($id, $name);
            return api_output(0, $arr, 'success');

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *预算详情
     */
    public function getBudgetInfo()
    {
        $id = $this->request->param("id", "0", "intval");
        try {
            $arr = (new MarriageBudgetService())->getBudgetInfo($id);
            return api_output(0, $arr, 'success');

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *预算比例操作
     */
    public function getBudgetScaleCreate()
    {
        $number_list = $this->request->param();
        try {
            $arr = (new MarriageBudgetService())->getBudgetScaleCreate($number_list);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *预算比例详情
     */
    public function getBudgetScaleInfo()
    {
        try {
            $arr = (new MarriageBudgetService())->getBudgetScaleInfo();
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *预算删除
     */
    public function getBudgetDel()
    {
        $id = $this->request->param("id", 1, "intval");
        try {
            $arr = (new MarriageBudgetService())->getBudgetDel($id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}