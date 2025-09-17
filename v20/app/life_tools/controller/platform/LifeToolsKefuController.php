<?php


namespace app\life_tools\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\LifeToolsKefuService;

class LifeToolsKefuController extends AuthBaseController
{

    /**
     * 获取客服列表
     */
    public function getKefuList()
    {
        $params = [];
        $params['page_size'] = $this->request->post('pageSize', 10, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        try {
            $data = (new LifeToolsKefuService)->getKefuList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 添加编辑客服
     */
    public function addEditKefu()
    {
        $params = [];
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval');
        $params['name'] = $this->request->post('name', '', 'trim');
        $params['phone'] = $this->request->post('phone', '', 'trim');
        $params['work'] = $this->request->post('work', []);
        $params['work_date'] = $this->request->post('work_date', []);
        try {
            $data = (new LifeToolsKefuService)->addEditKefu($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取客服详情
     */
    public function getKefuDetail()
    {
        $params = [];
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval');
        try {
            $data = (new LifeToolsKefuService)->getKefuDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取客服详情
     */
    public function delKefu()
    {
        $params = [];
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval');
        try {
            $data = (new LifeToolsKefuService)->delKefu($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

}