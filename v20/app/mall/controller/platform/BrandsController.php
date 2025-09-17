<?php

/**
 * 品牌管理
 */

namespace app\mall\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\mall\model\service\BrandsService;

class BrandsController extends AuthBaseController
{
    public function getLists()
    {
        $param['page_size'] = $this->request->param('page_size', 10, 'intval');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $lists = (new BrandsService())->getLists($param);
        return api_output(0, $lists, 'success');
    }


    public function save()
    {
        $params = $this->request->param();
        try {
            (new BrandsService())->save($params);
            return api_output();
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            (new BrandsService())->delete($id);
            return api_output();
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    public function changeStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            (new BrandsService())->changeStatus($id);
            return api_output(0, [], '操作成功');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
}
