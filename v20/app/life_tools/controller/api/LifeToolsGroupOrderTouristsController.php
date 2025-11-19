<?php

/**
 * 景区团体票订单绑定用户控制器
 */

namespace app\life_tools\controller\api;

use app\life_tools\model\service\group\LifeToolsGroupOrderTouristsService;

class LifeToolsGroupOrderTouristsController extends ApiBaseController
{

    /**
     * 游客列表
     */
    public function getTouristsList()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['order_id'] = $this->request->post('order_id', 0, 'intval');
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        try {
            $data = (new LifeToolsGroupOrderTouristsService())->getTouristsList($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 获取游客信息详情
     */
    public function getTouristsDetail()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['tourists_id'] = $this->request->post('tourists_id', 0, 'intval');
        $params['order_id'] = $this->request->post('order_id', 0, 'intval');
        try {
            $data = (new LifeToolsGroupOrderTouristsService())->getTouristsDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }


    /**
     * 添加/修改游客信息
     */
    public function addOrEditTourists()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['tourists_id'] = $this->request->post('tourists_id', 0, 'intval');
        $params['order_id'] = $this->request->post('order_id', 0, 'intval');
        $params['tourists_custom_form'] = $this->request->post('tourists_custom_form');
        try {
            $data = (new LifeToolsGroupOrderTouristsService())->addOrEditTourists($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 删除游客信息
     */
    public function delTourists()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['tourists_id'] = $this->request->post('tourists_id', 0, 'intval');
        try {
            $data = (new LifeToolsGroupOrderTouristsService())->delTourists($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }


}