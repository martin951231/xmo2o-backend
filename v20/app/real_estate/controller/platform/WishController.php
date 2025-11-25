<?php


namespace app\real_estate\controller\platform;

use app\real_estate\model\service\WishService;

class WishController extends AuthBaseController
{
    /**
     * 购房意愿-获取信息列表
     * @return \json
     */
    public function getList(){
        $params = [];
        $params['page'] = request()->param('page', 1, 'trim,intval');
        $params['page_size'] = request()->param('page_size', 10, 'trim,intval');
        $params['type'] = request()->param('type', 1, 'trim,intval');
        $params['search_kewords'] = request()->param('search_kewords', '', 'trim,string');
        $params['search_project'] = request()->param('search_project', 0, 'trim,intval');
        $params['search_process'] = request()->param('search_process', 0, 'trim,intval');
        $params['search_type'] = request()->param('search_type', 0, 'trim,intval');
        $params['search_pay_type'] = request()->param('search_pay_type', 0, 'trim,intval');
        $params['search_sdate'] = request()->param('search_sdate', '', 'trim,string');
        $params['search_edate'] = request()->param('search_edate', '', 'trim,string');
        $params['user_id'] = request()->param('user_id', 0, 'trim,intval');
        $params['level'] = $this->systemUser['level'];
        $params['uid'] = $this->_uid;
        try {
            $data = (new WishService())->getList($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购房意愿-添加信息
     * @return \json
     */
    public function add(){
        $params = [];
        $params['project_id'] = request()->param('project_id', 0, 'trim,intval');
        $params['process_id'] = request()->param('process_id', 0, 'trim,intval');
        $params['type_id'] = request()->param('type_id', 0, 'trim,intval');
        $params['pay_type'] = request()->param('pay_type',0,'trim,intval');
        $params['buyer_name'] = request()->param('buyer_name', '', 'trim,string');
        $params['buyer_phone'] = request()->param('buyer_phone', '', 'trim,string');
        $params['referee_name'] = request()->param('referee_name', '', 'trim,string');
        $params['referee_phone'] = request()->param('referee_phone', '', 'trim,string');
        $params['note'] = request()->param('note', '', 'trim,string');
        $params['level'] = $this->systemUser['level'];
        $params['uid'] = $this->_uid;
        try {
            $data = (new WishService())->add($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购房意愿-编辑信息
     * @return \json
     */
    public function edit(){
        $params = [];
        $params['id'] = request()->param('id', 0, 'trim,intval');
        $params['project_id'] = request()->param('project_id', 0, 'trim,intval');
        $params['process_id'] = request()->param('process_id', 0, 'trim,intval');
        $params['type_id'] = request()->param('type_id', 0, 'trim,intval');
        $params['pay_type'] = request()->param('pay_type',0,'trim,intval');
        $params['buyer_name'] = request()->param('buyer_name', '', 'trim,string');
        $params['buyer_phone'] = request()->param('buyer_phone', '', 'trim,string');
        $params['referee_name'] = request()->param('referee_name', '', 'trim,string');
        $params['referee_phone'] = request()->param('referee_phone', '', 'trim,string');
        $params['note'] = request()->param('note', '', 'trim,string');
        $params['level'] = $this->systemUser['level'];
        $params['uid'] = $this->_uid;
        try {
            $data = (new WishService())->edit($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购房意愿-获取单个信息详情
     * @return \json
     */
    public function show(){
        $params = [];
        $params['id'] = request()->param('id', 0, 'trim,intval');
        $params['level'] = $this->systemUser['level'];
        $params['uid'] = $this->_uid;
        try {
            $data = (new WishService())->show($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购房意愿-删除信息
     * @return \json
     */
    public function delete(){
        $params = [];
        $params['id'] = request()->param('id');
        $params['level'] = $this->systemUser['level'];
        $params['uid'] = $this->_uid;
        try {
            $data = (new WishService())->delete($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购房意愿-修改佣金支付状态
     * @return \json
     */
    public function changeStatus(){
        $params = [];
        $params['id'] = request()->param('id', 0, 'trim,intval');
        $params['status'] = request()->param('status', 0, 'trim,intval');
        $params['level'] = $this->systemUser['level'];
        $params['uid'] = $this->_uid;
        try {
            $data = (new WishService())->changeStatus($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购房意愿-获取住宅类型、购房流程、项目列表、付款状态列表
     * @return \json
     */
    public function getOtherList(){
        try {
            $data = (new WishService())->getOtherList();
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购房意愿-单个/批量修改购房状态
     * @return \json
     */
    public function changeProcess(){
        $params = [];
        $params['id'] = request()->param('id', 0, 'trim,intval');
        $params['process_id'] = request()->param('process_id', 0, 'trim,intval');
        $params['level'] = $this->systemUser['level'];
        $params['uid'] = $this->_uid;
        try {
            $data = (new WishService())->changeProcess($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购房意愿-导出数据列表
     * @return \json
     */
    public function exportData(){
        $params = [];
        $params['page'] = 0;
        $params['page_size'] = 0;
        $params['type'] = request()->param('type', 1, 'trim,intval');
        $params['search_kewords'] = request()->param('search_kewords', '', 'trim,string');
        $params['search_project'] = request()->param('search_project', 0, 'trim,intval');
        $params['search_process'] = request()->param('search_process', 0, 'trim,intval');
        $params['search_type'] = request()->param('search_type', 0, 'trim,intval');
        $params['search_pay_type'] = request()->param('search_pay_type', 0, 'trim,intval');
        $params['search_sdate'] = request()->param('search_sdate', '', 'trim,string');
        $params['search_edate'] = request()->param('search_edate', '', 'trim,string');
        $params['user_id'] = request()->param('user_id', 0, 'trim,intval');
        $params['level'] = $this->systemUser['level'];
        $params['uid'] = $this->_uid;
        try {
            $data = (new WishService())->exportData($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 获取用户列表
     * @return \json
     */
    public function getUserList(){
        $params['level'] = $this->systemUser['level'];
        try {
            $data = (new WishService())->getUserList($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
}