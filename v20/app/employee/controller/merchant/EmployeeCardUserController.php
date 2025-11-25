<?php


namespace app\employee\controller\merchant;


use app\employee\model\db\EmployeeCardLable;
use app\employee\model\service\EmployeeCardUserService;
use app\merchant\controller\merchant\AuthBaseController;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmployeeCardUserController extends AuthBaseController
{
    /**
     * 会员卡用户列表
     */
    public function getUserCardList(){
        $params['mer_id'] =$this->merId;
        $params['type'] =$this->request->post('type', 0, 'intval');
        $params['content'] =$this->request->post('content', '', 'trim');
        try {
            $list=(new EmployeeCardUserService())->getUserCardList($params);
            $list['mer'] = $this->merchantUser;
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 导出会员卡用户列表
     */
    public function exportUserCardList(){
        $params['mer_id'] =$this->merId;
        $params['type'] =$this->request->post('type', 0, 'intval');
        $params['content'] =$this->request->post('content', '', 'trim');
        try {
            $list=(new EmployeeCardUserService())->exportUserCardList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 用户信息
     */
    public function findUser(){
        $params['phone'] = $this->request->post('phone', '', 'trim');
        $params['card_id'] = $this->request->post('card_id', '', 'trim');
        try {
            $list=(new EmployeeCardUserService())->findUser($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $list);
    }
    /**
     * 员工卡编辑
     */
    public function editCardUser(){
        $params['mer_id'] = $this->merId;
        $params['user_id'] = $this->request->post('user_id', 0, 'intval');
        try {
            if(empty($params['user_id'])){
                return api_output_error(1001, "缺少必要参数");
            }else{
                $list=(new EmployeeCardUserService())->editCardUser($params);
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 导入抄表
     * @return \json
     */
    public function loadExcel()
    {
        $file = $this->request->post('fileUrl');
        $card_id=$this->request->post('card_id', 0, 'intval');
        if(empty($this->merId) || !$card_id){
            return api_output_error(1001,'缺少必传参数');
        }
        try {
            $savenum = (new EmployeeCardUserService())->upload($file,$this->merId,$card_id);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 员工卡保存
     */
    public function saveCardUser(){
        $params['mer_id'] =$this->merId;
        $params['user_id'] = $this->request->post('user_id', 0, 'intval');
        $params['card_id'] = $this->request->post('card_id', 0, 'intval');
        $params['name'] = $this->request->post('name', '', 'trim');
        $params['card_number'] = $this->request->post('card_number', '', 'trim');
        $params['phone'] = $this->request->post('phone', '', 'trim');
        $params['identity'] = $this->request->post('identity', '', 'trim');
        $params['department'] = $this->request->post('department', '', 'trim');
        $params['uid'] = $this->request->post('uid', 0, 'intval');
        $params['card_money'] = $this->request->post('card_money', '', 'trim');
        $params['card_score'] = $this->request->post('card_score', '', 'trim');
        $params['lable_ids'] = $this->request->post('lable_ids', '', 'trim');
        $params['status'] = $this->request->post('status', '', 'trim');
        try {
            $ret=(new EmployeeCardUserService())->saveCardUser($params);
            if(!empty($ret['error'])){
                return api_output_error(1001, $ret['msg']);
            }else{
                return api_output(0, $ret);
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 消费记录
     */
    public function orderList(){
        $params['user_id'] = $this->request->post('user_id', 0, 'intval');
        $params['card_id'] = $this->request->post('card_id', 0, 'intval');
        $params['s_time'] = $this->request->post('begin_time', '', 'trim');
        $params['e_time'] = $this->request->post('end_time', '', 'trim');
        $params['page'] = $this->request->post('page', 0, 'intval');
        $params['pageSize'] = $this->request->post('pageSize', 0, 'intval');
        $params['verify_type'] = $this->request->post('verify_type', 0, 'intval');
        $params['change_type'] = $this->request->post('change_type', 0, 'intval');
        try {
            $ret=(new EmployeeCardUserService())->orderList($params);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
    /**
     * 员工卡删除
     */
    public function delData(){
        $params['user_id'] = $this->request->post('user_id', 0, 'intval');
        try {
            if(empty($params['user_id'])){
                return api_output_error(1001, "缺少必要参数");
            }else{
                $ret=(new EmployeeCardUserService())->delData($params);
                if(!empty($ret)){
                    return api_output(0, []);
                }else{
                    return api_output_error(1001, "删除失败");
                }
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }

    }

     /**
     * 批量删除员工卡
     */
    public function delUserCard()
    {
        $params['user_ids'] = $this->request->post('user_ids');
        $params['pass'] = $this->request->post('pass', '', 'trim');
        $params['mer_id'] = $this->merId;
        if($this->merchantUser['pwd'] != md5($params['pass'])) {
            throw new \think\Exception(L_("密码有误，请重新输入"), 1003);
        }
        try {
            $data = (new EmployeeCardUserService)->delCardUser($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data, '操作成功');
    }

    /**
     * 批量开启员工卡
     */
    public function openUserCard()
    {
        $params['user_ids'] = $this->request->post('user_ids');
        $params['mer_id']   = $this->merId;
        try {
            $data = (new EmployeeCardUserService)->openUserCard($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data, '操作成功');
    }

    /**
     * 批量关闭员工卡
     */
    public function closeUserCard()
    {
        $params['user_ids'] = $this->request->post('user_ids');
        $params['mer_id']   = $this->merId;
        try {
            $data = (new EmployeeCardUserService)->closeUserCard($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data, '操作成功');
    }

    /**
     * 员工卡身份标签列表
     */
    public function employLableList() {
        $params['mer_id']   = $this->merId;
        $params['page']     = $this->request->param('page', 1, 'intval');
        $params['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        try {
            $res = (new EmployeeCardUserService())->employLableList($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加或编辑员工卡身份标签
     */
    public function employLableAddOrEdit() {
        $params['mer_id'] = $this->merId;
        $params['id']     = $this->request->param('id', 0, 'intval');
        $params['name']   = $this->request->param('name', '', 'trim');
        if (empty($params['name'])) {
            return api_output_error(1003, '身份名称必填');
        }
        try {
            $res = (new EmployeeCardUserService())->employLableAddOrEdit($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除员工卡身份标签
     */
    public function employLableDel() {
        $params['mer_id'] = $this->merId;
        $params['id']     = $this->request->param('id', 0, 'intval');
        if (empty($params['id'])) {
            return api_output_error(1003, '参数有误');
        }
        try {
            (new EmployeeCardLable())->updateThis(['id' => $params['id']], ['is_del' => 1]);
            return api_output(0, true);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取标签列表无分页
     */
    public function getLabelList()
    {
        $params['mer_id'] = $this->merId;
        try {
            $data = (new EmployeeCardUserService)->getLabelList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 批量启用/禁用员工
     */
    public function openOrCloseUserCard()
    {
        $params['mer_id'] = $this->merId;
        $params['user_ids'] = $this->request->param('user_ids');
        $params['status'] = $this->request->param('status', 0, 'intval');
        try {
            $data = (new EmployeeCardUserService)->openOrCloseUserCard($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取店铺列表
     */
    public function getStoreList()
    {
        try {
            $data = (new EmployeeCardUserService)->getStoreList($this->merId);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取店铺列表
     */
    public function lableBindStore()
    {
        $params['mer_id'] = $this->merId;
        $params['lable_id'] = $this->request->post('label_id', 0, 'intval');
        $params['store_ids'] = $this->request->post('store_ids', []);
        try {
            $data = (new EmployeeCardUserService)->lableBindStore($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }


}