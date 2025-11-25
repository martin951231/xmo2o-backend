<?php
/**
 * @Author: 朱梦群
 * @Date:   2020-09-04 11:17:43
 * @Desc:   商城3.0分类管理controller
 */

namespace app\mall\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\mall\model\service\MallGoodsSortService;

class MallGoodsSortController extends AuthBaseController
{
    /**
     * @param:
     * @return :  array
     * @Desc:   获取分类列表
     */
    public function getSortList()
    {
        //店铺id
        $store_id = $this->request->param('store_id', '', 'intval');
        //商店id
        $mer_id = $this->merId;
        // 每页显示数量
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        // 当前页数
        $param['page'] = request()->param('page', '1', 'intval');
        $mallService = new MallGoodsSortService();
        try {
            $result = $mallService->getSortList($mer_id, $store_id, 0, $param);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * @param:
     * @return :  int
     * @Desc:   编辑或新增分类
     */
    public function addOrEditSort()
    {
        $arr['id'] = $this->request->param('id', '', 'intval');
        $arr['fid'] = $this->request->param('fid', 0, 'intval');
        $arr['mer_id'] = $this->merId;
        $arr['store_id'] = $this->request->param('store_id', 0, 'intval');
        $arr['name'] = $this->request->param('name', '', 'trim');
        $arr['status'] = $this->request->param('status', 0, 'trim');
        $arr['sort'] = $this->request->param('sort', 0, 'trim');
        $sortService = new MallGoodsSortService();
        try {
            $result = $sortService->addOrEditSort($arr);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @param:
     * @return :  int
     * @Desc:   删除分类
     */
    public function delSort()
    {
        $arr['id'] = $this->request->param('id', '', 'intval');
        $arr['store_id'] = $this->request->param('store_id', '', 'intval');
        $arr['type'] = $this->request->param('type', '', 'intval');
        $arr['level'] = $this->request->param('level', '', 'intval');
        $arr['mer_id'] = $this->merId;
        $sortService = new MallGoodsSortService();
        try {
            $result = $sortService->delSort($arr);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @param:
     * @return :  array
     * @Desc:   获取被编辑的分类
     */
    public function getEditSort()
    {
        $arr['id'] = $this->request->param('id', '', 'trim');
        $arr['store_id'] = $this->request->param('store_id', '', 'intval');
        $arr['mer_id'] = $this->merId;
        $sortService = new MallGoodsSortService();
        try {
            $result = $sortService->getEditSort($arr['id']);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * @param:
     * @return :  array
     * @Desc:   编辑排序
     */
    public function saveSort()
    {
        $id = $this->request->param('id', '', 'intval');
        $sort = $this->request->param('sort', 0, 'intval');
        $sortService = new MallGoodsSortService();
        try {
            $result = $sortService->saveSort($id, $sort);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * @param:
     * @return :  array
     * @Desc:   编辑状态
     */
    public function saveStatus()
    {
        $id = $this->request->param('id', '', 'intval');
        $status = $this->request->param('status', 1, 'intval');
        $sortService = new MallGoodsSortService();
        try {
            $result = $sortService->saveStatus($id, $status);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * @param:
     * @return :  array
     * @Desc:   获取被编辑的分类
     */
    public function getSort()
    {
        $store_id = $this->request->param('store_id', 0, 'intval');
        $mer_id = $this->merId;
        $sortService = new MallGoodsSortService();
        try {
            $result = $sortService->getSort($store_id);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }
}

//getSortList() 分类列表展示
//addOrEditSort() 新增或编辑分类
//delSort() 删除分类