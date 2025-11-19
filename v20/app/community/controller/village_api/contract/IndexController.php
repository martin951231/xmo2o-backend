<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      小区后台合同
 */

namespace app\community\controller\village_api\contract;

use app\community\controller\CommunityBaseController;
use app\community\model\service\contract\ContractHandleService;
use app\community\model\service\HouseVillageService;
use app\consts\ContractConst;

class IndexController extends CommunityBaseController
{
    /**
     * 获取合同类别[列表筛选/添加选择类别/编辑选择类别]
     */
    public function getClassifyList()
    {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        try {
            $result = [];
            $where = [];
            $where[] = ['status', '=', 1];
            $where[] = ['delete_time', '=', 0];
            $param = [
                'where' => $where, 'field' => 'id, name, status', 'order' => 'add_time DESC', 'page' => 0, 'pageSize' => 0, 'isHandle' => 0, 'from' => 'village',
            ];
            $list = (new ContractHandleService())->getContractClassifyList($param);
            $selectList = [];
            foreach ($list as $item) {
                $selectList[] = [
                    'label' => $item['name'],
                    'value' => $item['id'],
                ];
            }
            $result['selectList'] = $selectList;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }


    /**
     * 获取合同列表.
     */
    public function getList() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $page     = $this->request->param('page',    '0','intval');
        $pageSize = $this->request->param('pageSize','15','intval');
        $contract_number = $this->request->param('contract_number','','trim');
        $contract_name = $this->request->param('contract_name','','trim');
        $status = $this->request->param('status','','trim');
        $classify_id = $this->request->param('classify_id',0,'intval');
        $result = [];
        $where = [];
        $where[] = ['village_id', '=', $village_id];
        try {
            $nowTime = time();
            $classify_id && $where[] = ['classify_id', '=', $classify_id];
            switch ($status) {
                case ContractConst::CONTRACT_NOT_EFFECTIVE:
                    $where[] = ['contract_start_time', '>', $nowTime];
                    break;
                case ContractConst::CONTRACT_IN_EFFECTIVE:
                    $where[] = ['contract_start_time', '<=', $nowTime];
                    $where[] = ['contract_end_time', '>', $nowTime];
                    break;
                case ContractConst::CONTRACT_LOSE_EFFICACY:
                    $where[] = ['contract_end_time', '<', $nowTime];
                    break;
            }
            if ($contract_number) {
                $where[] = ['contract_number', 'like', "%" . $contract_number . "%"];
            }
            if ($contract_name) {
                $where[] = ['contract_name', 'like', "%" . $contract_name . "%"];
            }
            $where[] = ['delete_time', '=', 0];
            $result['count'] = (new ContractHandleService())->getContractCount($where);
            $param = [
                'where' => $where, 'order' => 'add_time DESC', 'page' => $page, 'pageSize' => $pageSize, 'isHandle' => 1, 'from' => 'village',
            ];
            $list = (new ContractHandleService())->getContractList($param);
            
            $houseVillageService=new HouseVillageService();
            $role_auth_add = $houseVillageService->checkPermissionMenu(1012, $this->adminUser, $this->login_role, $this->dismissPermissionRole);
            $role_auth_edit = $houseVillageService->checkPermissionMenu(1013, $this->adminUser, $this->login_role, $this->dismissPermissionRole);
            $role_auth_delete = $houseVillageService->checkPermissionMenu(1014, $this->adminUser, $this->login_role, $this->dismissPermissionRole);
            if (!empty($list)) {
                foreach ($list as &$item) {
                    $item['role_auth_edit'] = $role_auth_edit;
                    $item['role_auth_delete'] = $role_auth_delete;
                }
            }
            $result['role_auth_add'] = $role_auth_add;
            $result['list'] = $list;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        $result['statusSelect'] = [
            ['label' => '全部', 'value' => ContractConst::CONTRACT_ALl],
            ['label' => '待生效', 'value' => ContractConst::CONTRACT_NOT_EFFECTIVE],
            ['label' => '生效中', 'value' => ContractConst::CONTRACT_IN_EFFECTIVE],
            ['label' => '已失效', 'value' => ContractConst::CONTRACT_LOSE_EFFICACY],
        ];
        return api_output(0, $result);
    }
    
    /**
     * 添加/更新
     */
    public function setContractInfo() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $id = $this->request->param('id', 0, 'intval');
        $houseVillageService=new HouseVillageService();
        if (!$id) {
            $role_auth_add = $houseVillageService->checkPermissionMenu(1012, $this->adminUser, $this->login_role, $this->dismissPermissionRole);
            if ($role_auth_add != 1) {
                return api_output_error(1001, '当前您无权对合同进行添加操作');
            }
        } else {
            $role_auth_edit = $houseVillageService->checkPermissionMenu(1013, $this->adminUser, $this->login_role, $this->dismissPermissionRole);
            if ($role_auth_edit != 1) {
                return api_output_error(1001, '当前您无权对合同进行编辑操作');
            }
        }
        $contract_number = $this->request->param('contract_number', '', 'trim');
        $contract_name = $this->request->param('contract_name', '', 'trim');
        $classify_id = $this->request->param('classify_id', 0, 'intval');
        $contract_start_time = $this->request->param('contract_start_time', 0, 'trim');
        $contract_end_time = $this->request->param('contract_end_time', 0, 'trim');
        $contract_content = $this->request->param('contract_content', '', 'trim');
        $work_id = $this->request->param('work_id', 0, 'intval');
        $phone = $this->request->param('phone', '', 'trim');
        $contract_appendix = $this->request->param('contract_appendix');
        try {
            $from_user = ['type' => 'village_admin', 'admin_id' => $this->adminUser['village_id'], 'account' => $this->adminUser['village_name']];
            if (isset($this->adminUser['user_name']) && $this->adminUser['user_name']) {
                $from_user['name'] = $this->adminUser['user_name'];
            } elseif (isset($this->adminUser['adminName']) && $this->adminUser['adminName']) {
                $from_user['name'] = $this->adminUser['adminName'];
            }
            if (isset($this->adminUser['phone']) && $this->adminUser['phone']) {
                $from_user['phone'] = $this->adminUser['phone'];
            }
            if ($this->_uid) {
                $from_user['_userId'] = $this->_uid;
            }
            if ($this->login_userId) {
                $from_user['login_userId'] = $this->login_userId;
            }
            if ($this->login_role) {
                $from_user['login_role'] = $this->login_role;
            }
            $result = [];
            $param = [
                'contract_number' => $contract_number,'contract_name' => $contract_name, 'from_user' => $from_user, 'id' => $id,
                'classify_id' => $classify_id, 'contract_start_time' => $contract_start_time, 'contract_end_time' => $contract_end_time,
                'contract_content' => $contract_content, 'village_id' => $village_id, 'work_id' => $work_id, 'phone' => $phone,
                'contract_appendix' => $contract_appendix, 'from' => 'village',
            ];
            $result['result'] = (new ContractHandleService())->setContract($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 添加和更新都要请求下.
     */
    public function getAddUpdateContractConfig() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $id = $this->request->param('id', 0, 'intval');
        try {
            $result = [];
            $param = [
                'id' => $id,
                'village_id' => $village_id,
            ];
            $list = (new ContractHandleService())->getContractConfig($param);
            $result['result'] = $list;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 详情 合同
     */
    public function getContractInfo() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $id = $this->request->param('id',0,'intval');
        try {
            $where = [];
            $where[] = ['id', '=', $id];
            $where[] = ['village_id', '=', $village_id];
            $result['result'] = (new ContractHandleService())->getContractInfo($where, '', 1);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 删除 合同
     */
    public function deleteContractInfo() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $houseVillageService=new HouseVillageService();
        $role_auth_delete = $houseVillageService->checkPermissionMenu(1014, $this->adminUser, $this->login_role, $this->dismissPermissionRole);
        if ($role_auth_delete != 1) {
            return api_output_error(1001, '当前您无权对合同进行删除操作');
        }
        $id = $this->request->param('id',0,'intval');
        try {
            $from_user = ['type' => 'village_admin', 'admin_id' => $this->adminUser['village_id'], 'account' => $this->adminUser['village_name']];
            if (isset($this->adminUser['user_name']) && $this->adminUser['user_name']) {
                $from_user['name'] = $this->adminUser['user_name'];
            } elseif (isset($this->adminUser['adminName']) && $this->adminUser['adminName']) {
                $from_user['name'] = $this->adminUser['adminName'];
            }
            if (isset($this->adminUser['phone']) && $this->adminUser['phone']) {
                $from_user['phone'] = $this->adminUser['phone'];
            }
            if ($this->_uid) {
                $from_user['_userId'] = $this->_uid;
            }
            if ($this->login_userId) {
                $from_user['login_userId'] = $this->login_userId;
            }
            if ($this->login_role) {
                $from_user['login_role'] = $this->login_role;
            }
            $result = [];
            $param = [
                'is_del' => 1, 'from_user' => $from_user, 'id' => $id, 'from' => 'village', 'village_id' => $village_id
            ];
            $result['result'] = (new ContractHandleService())->setContract($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
}