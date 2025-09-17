<?php

namespace app\warn\controller\merchant;

use app\warn\model\service\WarnService;
use app\merchant\controller\merchant\AuthBaseController;
use think\Exception;

class WarnController extends AuthBaseController
{
    /**
     * 新建运营
     */
    public function addUser()
    {
        $params = [];
        $params['name'] = $this->request->post('name', '', 'trim');
        $params['phone'] = $this->request->post('phone', '', 'trim');
        $params['work_time_arr'] = $this->request->post('workCheckedList', []);
        $params['business'] = $this->request->post('businessCheckedList', []);
        $params['status'] = $this->request->post('status', 1, 'intval');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new WarnService())->addUser($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 编辑运营
     */
    public function editUser()
    {
        $params = [];
        $params['id'] = $this->request->post('id', 0, 'intval');
        $params['name'] = $this->request->post('name', '', 'trim');
        $params['phone'] = $this->request->post('phone', '', 'trim');
        $params['work_time_arr'] = $this->request->post('workCheckedList', []);
        $params['business'] = $this->request->post('businessCheckedList', []);
        $params['status'] = $this->request->post('status', 1, 'intval');
        try {
            $data = (new WarnService())->editUser($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 删除运营
     */
    public function delUser()
    {
        $params = [];
        $params['id'] = $this->request->post('id', 0, 'intval');
        try {
            $data = (new WarnService())->delUser($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 运营列表
     */
    public function getList()
    {
        $params = [];
        $params['pageSize'] = $this->request->post('pageSize', 10, 'trim,intval');
        $params['page'] = $this->request->post('page', 1, 'trim,intval');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new WarnService())->getList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);

    }

    /**
     * 获取配置信息
     */
    public function getConfig(){
        $param['mer_id'] = $this->merId;
        try {
            $data = (new WarnService())->getConfig($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 保存配置信息
     */
    public function saveConfig(){
        $param['mer_id'] = $this->merId;
        $param['mall'] = $this->request->param('mall');
        $param['shop'] = $this->request->param('shop');
        $param['group'] = $this->request->param('group');
        $param['appoint'] = $this->request->param('appoint');
        $param['scenic'] = $this->request->param('scenic');
        try {
            $data = (new WarnService())->saveConfig($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 获取详情
     */
    public function getUserDetail()
    {
        $param['id'] = $this->request->param('id');
        try {
            $data = (new WarnService())->getUserDetail($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}

