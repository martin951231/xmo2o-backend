<?php
/**
 * 店铺技师认证管理controller
 * @author wangchen
 * Date Time: 2021/6/8
 */

namespace app\merchant\controller\platform;

use app\BaseController;
use app\merchant\model\service\TechnicianService;

class TechnicianController extends BaseController
{
    /**
     * 店铺技师认证列表
     * @return \json
     */
    public function getTechnicianList()
    {
        $type = $this->request->param('type', 0, 'intval');
        $category = $this->request->param('category', 0, 'intval');
        $position = $this->request->param('position', 0, 'intval');
        $type_id = $this->request->param('type_id', 0, 'intval');
        $name = $this->request->param('name', '', 'trim');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        try {
            $arr = (new TechnicianService())->getTechnicianList($type, $category, $position, $type_id, $name, $page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 店铺技师认证详情
     * @return \json
     */
    public function getTechnicianView()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $arr = (new TechnicianService())->getTechnicianView($id);
            return api_output(0, $arr, 'success');

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 店铺技师认证审核
     * @return \json
     */
    public function getTechnicianExamine()
    {
        $id = $this->request->param('id', 0, 'intval');
        $type = $this->request->param('type', 0, 'intval');
        try {
            $result = (new TechnicianService())->getTechnicianExamine($id, $type);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 店铺技师认证拉黑
     * @return \json
     */
    public function getTechnicianDel()
    {
        $id = $this->request->param('id', 0, 'intval');
        $type = $this->request->param('type', 0, 'intval');
        try {
            $result = (new TechnicianService())->getTechnicianDel($id, $type);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}