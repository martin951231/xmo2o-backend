<?php

/**
 * 景区团体票首页控制器
 */

namespace app\life_tools\controller\api;

use app\life_tools\model\service\group\LifeToolsGroupTicketService;

class LifeToolsGroupIndexController extends ApiBaseController
{

    /**
     * 景区列表
     */
    public function getList()
    {
        $params['uid'] = $this->_uid;
        $params['page'] = $this->request->post('page', 1, 'intval');
        $params['pageSize'] = $this->request->post('pageSize', 10, 'intval');
        $params['keyword'] = $this->request->post('keyword', '', 'trim');
        $params['cat_id'] = $this->request->post('cat_id', '', 'intval');
        try {
            $data = (new LifeToolsGroupTicketService())->getUserToolsList($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }
}
