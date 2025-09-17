<?php

/**
 * 服务保障
 */

namespace app\mall\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\mall\model\service\ProductGuaranteeService;

class ProductGuaranteeController extends AuthBaseController
{
    public function getLists()
    {
        $param['page_size'] = $this->request->param('page_size', 10, 'intval');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $lists = (new ProductGuaranteeService())->getLists($param);
        return api_output(0, $lists, 'success');
    }


    public function save()
    {
        $params = $this->request->param();
        try {
            (new ProductGuaranteeService())->save($params);
            return api_output();
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            (new ProductGuaranteeService())->delete($id);
            return api_output();
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    public function changeStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            (new ProductGuaranteeService())->changeStatus($id);
            return api_output(0, [], '操作成功');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
}
