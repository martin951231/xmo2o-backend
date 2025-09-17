<?php
/**
 * 体育健身课程接口控制器
 */

namespace app\life_tools\controller\api;

use app\life_tools\model\service\LifeToolsService;

class CourseController extends ApiBaseController
{
    /**
     * 课程列表
     */
    public function courseList()
    {
        $param['cat_id']   = $this->request->param('cate_id', 0, 'intval');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        try {
            $arr = (new LifeToolsService())->getToolsList($param, 'course');
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}
