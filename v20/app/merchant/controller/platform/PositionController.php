<?php
/**
 * PositionController.php
 * 店铺岗位管理controller
 * Create on 2021/6/2
 * Created by wangchen
 */

namespace app\merchant\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\merchant\model\service\MerchantPositionService;

class PositionController extends AuthBaseController
{
    /**
     * 岗位列表
     * @return \json
     */
    public function getPositionList()
    {
        $remarks = $this->request->param('remarks', '', 'trim');
        $cat_id = $this->request->param('cat_id', 0, 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        try {
            $arr = (new MerchantPositionService())->getPositionList($remarks, $cat_id, $page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 岗位分类
     * @return \json
     */
    public function getPositionCategoryList()
    {
        try {
            $arr = (new MerchantPositionService())->getPositionCategoryList();
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 岗位操作
     * @return \json
     */
    public function getPositionCreate()
    {
        $id = $this->request->param('id', 0, 'intval');
        $cat_id = $this->request->param('cat_id', 0, 'intval');
        $name = $this->request->param('name', '', 'trim');
        $remarks = $this->request->param('remarks', '', 'trim');
        try {
            $arr = (new MerchantPositionService())->getPositionCreate($id, $cat_id, $name, $remarks);
            return api_output(0, $arr, 'success');

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 岗位详情
     * @return \json
     */
    public function getPositionInfo()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $arr = (new MerchantPositionService())->getPositionInfo($id);
            return api_output(0, $arr, 'success');

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 岗位删除
     * @return \json
     */
    public function getPositionDelAll()
    {
        $id = $this->request->param('ids', '', 'trim');
        try {
            $result = (new MerchantPositionService())->getPositionDelAll($id);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }
}

