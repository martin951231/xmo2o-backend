<?php
/**
 * 高手管理controller
 * @author wangchen
 * Date Time: 2021/6/7
 */

namespace app\marriage_helper\controller\platform;

use app\BaseController;
use app\marriage_helper\model\service\JobPersonService;

class JobPersonController extends BaseController
{
    /**
     * 高手列表
     * @return \json
     */
    public function getPersonList()
    {
        $category = $this->request->param('category', 0, 'intval');
        $position = $this->request->param('position', 0, 'intval');
        $type_id = $this->request->param('type_id', 0, 'intval');
        $name = $this->request->param('name', '', 'trim');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        try {
            $arr = (new JobPersonService())->getPersonList($category, $position, $type_id, $name, $page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 高手详情
     * @return \json
     */
    public function getPersonView()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $arr = (new JobPersonService())->getPersonView($id);
            return api_output(0, $arr, 'success');

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 高手拉黑
     * @return \json
     */
    public function getPersonDel()
    {
        $id = $this->request->param('id', '', 'intval');
        try {
            $result = (new JobPersonService())->getPersonDel($id);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}