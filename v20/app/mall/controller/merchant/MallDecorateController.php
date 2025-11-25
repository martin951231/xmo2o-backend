<?php
/**
 * MallDecorateController.php
 * 商城装修控制器
 * Create on 2020/9/25 10:48
 * Created by zhumengqun
 */

namespace app\mall\controller\merchant;

use app\BaseController;
use app\mall\model\service\MallDecorateService;
use app\merchant\controller\merchant\AuthBaseController;

class MallDecorateController extends BaseController
{
    /**
     * 获取该店铺下的所有商品
     * @return \json
     */
    public function getGoodsList()
    {
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $param['selectedItems'] = $this->request->param('selectedItems', '', 'trim');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $mallDecorateService = new MallDecorateService();
        try {
            $arr = $mallDecorateService->getGoodsList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取该店铺下的所有分类
     * @return \json
     */
    public function getStoreSort()
    {
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $param['selectedItems'] = $this->request->param('selectedItems', '', 'trim');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $mallDecorateService = new MallDecorateService();
        try {
            $arr = $mallDecorateService->getStoreSort($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取该店铺下的某个活动的所有商品
     * @return \json
     */
    public function getActGoodsList()
    {
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $param['act_type'] = $this->request->param('act_type', '', 'trim');
        $param['selectedItems'] = $this->request->param('selectedItems', '', 'trim');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $mallDecorateService = new MallDecorateService();
        try {
            $arr = $mallDecorateService->getActGoodsList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加店铺装修
     */
    public function addOrEditPage(){
        $param['mer_id'] = $this->request->param('mer_id','','intval');
        $param['store_id'] = $this->request->param('store_id','','intval');
        $param['page_id'] = $this->request->param('page_id','','intval');
        $param['field_id'] = $this->request->param('field_id','','intval');
        $param['page_name'] = $this->request->param('page_name','','trim');
        $param['page_desc'] = $this->request->param('page_desc','','trim');
        $param['bgcolor'] = $this->request->param('bgcolor','','trim');
        $param['custom'] = $this->request->param('custom','','trim');
        $mallDecorateService = new MallDecorateService();
        try {
            $result = $mallDecorateService->addOrEditPage($param);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function createPage(){
        $param['mer_id'] = $this->request->param('mer_id','','intval');
        $param['store_id'] = $this->request->param('store_id','','intval');
        $mallDecorateService = new MallDecorateService();
        try {
            $arr = $mallDecorateService->createPage($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}