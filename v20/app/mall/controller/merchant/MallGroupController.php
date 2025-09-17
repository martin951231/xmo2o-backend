<?php
/*
 * @User: chenxiang
 * @Date: 2020-09-18 10:40:37
 * @param: 
 * @return: 
 */

/**
 * 商家后台-商城营销活动 -- 拼团
 */
namespace app\mall\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\mall\model\service\activity\MallNewGroupActService;
use rand_name\RandName;

class MallGroupController extends AuthBaseController {

    public $merId;

    public function initialize()
    {
        parent::initialize();
        $this->merId = $this->merchantUser['mer_id'] ?? 0;
    }

    /**
     * 获取拼团列表
     * User: chenxiang
     * Date: 2020/9/18 11:20
     */
    public function getGroupList() {
        //店铺id
        $store_id = $this->request->param('store_id', '', 'intval');
        if ($store_id < 1) {
            return api_output(1001, [], '店铺ID不存在');
        }

        $param = $this->request->param();
        $mallNewGroupActService = new MallNewGroupActService();
        try{
            $result = $mallNewGroupActService->getGroupList($store_id, $param);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 添加活动 和 编辑活动
     * User: chenxiang
     * Date: 2020/9/18 11:25
     */
    public function addGroup() {
        $merId = $this->merchantUser['mer_id'] ?? 0;

        if ($merId < 1) {
            return api_output(1001, [], '商家ID不存在');
        }

        $param = $this->request->param();
        $param['mer_id'] = $merId;
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['sort'] = 10;
        if ($param['store_id'] < 1) {
            return api_output(1001, [], '店铺ID不存在');
        }
        try{
            $res = (new MallNewGroupActService())->addGroupAct($param);

            if ($res['status'] == 0) {
                return api_output(1000, [], $res['msg']);
            } else {
                return api_output(1003, [], $res['msg']);
            }
        } catch(\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }

    }

    /**
     * 查看 编辑页面
     * User: chenxiang
     * Date: 2020/11/2 10:28
     * @return \json
     */
    public function editDetail()
    {
        $id = $this->request->param('id', '', 'intval'); //表id
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        try {
            $mallNewGroupActService = new MallNewGroupActService();
            $result = $mallNewGroupActService->getInfoById($id);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 失效操作
     * User: chenxiang
     * Date: 2020/9/18 11:27
     */
    public function changeState() {
        $id = $this->request->param('id', '', 'intval');
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }

        $param['status'] = $this->request->param('status', 2, 'intval');
        try {
            (new MallNewGroupActService())->changeState($param, $id);
            return api_output(1000, []);
        } catch(\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }

    /**
     * 删除操作
     * User: chenxiang
     * Date: 2020/9/18 11:28
     */
    public function del() {
        $id = $this->request->param('id', '', 'intval');
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        $param['is_del'] = $this->request->param('is_del', 1, 'intval');
        try {
            (new MallNewGroupActService())->del($param, $id);
            return api_output(1000, []);
        } catch(\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }

    /**
     * 获取机器人列表
     * User: chenxiang
     * Date: 2020/9/18 11:29
     */
    public function getRobotList() {
        $merId = $this->merId;
        if ($merId < 1) {
            return api_output(1001, [], '商家ID不存在');
        }

        $pageSize = $this->request->param('pageSize', 20, 'intval');
        $page = $this->request->param('page', 1, 'intval');

        $where = [];
        $where['mer_id'] = $merId;
        $sort = 'id DESC';
        $field = 'id, robot_name, avatar, add_time';

        try{
            $robot_list = (new MallNewGroupActService())->getRobotList($field, $where, $sort, $page, $pageSize);
            return api_output(1000, $robot_list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加机器人
     * User: chenxiang
     * Date: 2020/9/18 11:30
     */
    public function addRobot() {

        $merId = $this->merchantUser['mer_id'] ?? 0;
        if ($merId < 1) {
            return api_output(1001, [], '商家ID不存在');
        }

        $param = [];
        $param['id'] = $this->request->param('id','');
        $param['robot_name'] = $this->request->param('name','阿萨撒');
        $param['avatar'] = $this->request->param('avatar','');
        $param['mer_id'] = $merId;
        $param['add_time'] = $_SERVER['REQUEST_TIME'];

        try{
            if(empty($param['id'])) {
                (new MallNewGroupActService())->addRobot($param, 'add');
            } else {
                (new MallNewGroupActService())->addRobot($param, 'edit');
            }

            return api_output(1000, []);
        } catch(\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }

    /**
     * 删除机器人 可批量删除
     * User: chenxiang
     * Date: 2020/9/19 10:40
     * @return \json
     */
    public function delRobot() {
        $ids = $this->request->param('ids', '');
        if (empty($ids)) {
            return api_output(1003, [], 'ID不存在');
        }

        $arr_ids = explode(',',$ids);
        $param['mer_id'] = $this->merId;
        try {
            if(count($arr_ids) > 1) { //单个删除
                foreach ($arr_ids as $value) {
                    (new MallNewGroupActService())->delRobot($param, $value);
                }
            } else { //批量删除
                (new MallNewGroupActService())->delRobot($param, $ids);
            }

            return api_output(1000, []);
        } catch(\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }

    /**
     * 随机生成机器人姓名
     * User: chenxiang
     * Date: 2020/10/30 15:24
     * @return \json
     */
    public function getRobotName() {
        try {
            $rand_name = (new randName())->getName();
            return api_output(1000, ['name'=>$rand_name]);
        } catch(\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }

}