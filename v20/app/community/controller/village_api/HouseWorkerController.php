<?php
/**
 * 工作人员相关
 **/

namespace app\community\controller\village_api;

use app\common\model\service\export\ExportService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\PropertyGroup;
use app\community\model\service\HouseNewParkingService;
use app\community\model\service\HousePropertyService;
use app\community\model\service\HouseVillageLabelService;
use app\community\model\service\HouseWorkerService;
use app\community\model\service\RecognitionService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageRepairListService;
use app\community\model\service\HouseAdminGroupService;
use app\community\model\service\HouseMenuNewService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\PrivilegePackageService;
use app\community\model\service\HouseAdminService;
use app\community\model\service\PowerService;
use app\community\model\service\PropertyFrameworkService;
use app\community\model\service\HousePropertyVillageWorkerService;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Cache;
use think\facade\Db;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;
class HouseWorkerController extends CommunityBaseController
{
    use CommonLogTraits;
    use AdminLoginTraits;
    /**
     * 获取工作人员
     */
    public function getWorkerList()
    {
        $village_id = $this->adminUser['village_id'];
        $serviceHouseWorker = new HouseWorkerService();
        $page = $this->request->param('page', '1', 'int');
        $phone = $this->request->post('phone', '', 'trim');
        $xname = $this->request->post('xname', '', 'trim');
        $limit = 30;
        $where=array();
        $where[] = array('village_id' ,'=', $village_id);
        $where[] = array('is_del' ,'=', 0);
        if($phone){
            $where[] = array('phone' ,'like','%'.$phone.'%' );
        }
        if($xname){
            $where[] = array('name' ,'like', '%'.$xname.'%');
        }
        try {
            $fieldStr = '*';
            $list = $serviceHouseWorker->get_worker_list($where, $fieldStr, $page, $limit);
            //549 工作人员-绑定和取绑微信 7工作人员-禁用
            $list['role_bindwx'] = $this->checkPermissionMenu(549);
            $list['role_bindwx'] = $list['role_bindwx'] === true ? 1 : 0;
            $list['role_disable'] = $this->checkPermissionMenu(7);
            $list['role_disable'] = $list['role_disable'] === true ? 1 : 0;
            $list['role_del']= $this->checkPermissionMenu(296);
            $list['role_del'] = $list['role_del'] === true ? 1 : 0;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }

    public function getCurrentLoginInfo()
    {
        $village_id = $this->adminUser['village_id'];
        $logInfo = array('village_id' => $village_id, 'login_role' => $this->login_role);
        $logInfo['account'] = '';
        $logInfo['account_str'] = '';
        if (isset($this->adminUser['account'])) {
            $logInfo['account'] = $this->adminUser['account'];
            $logInfo['account_str'] = $this->adminUser['account'];
        }
        $logInfo['realname'] = '';
        if (isset($this->adminUser['realname'])) {
            $logInfo['realname'] = $this->adminUser['realname'];
            if ($logInfo['account_str']) {
                $logInfo['account_str'] = $logInfo['account_str'] . '（' . $logInfo['realname'] . '）';
            }
        }
        $logInfo['wid'] = 0;
        if (isset($this->adminUser['wid'])) {
            $logInfo['wid'] = $this->adminUser['wid'];
        }
        $logInfo['phone'] = '';
        if (isset($this->adminUser['phone'])) {
            $logInfo['phone'] = $this->adminUser['phone'];
        }
        $logInfo['property_id'] = 0;
        if (isset($this->adminUser['property_id'])) {
            $logInfo['property_id'] = $this->adminUser['property_id'];
        }

        return api_output(0, $logInfo);
    }

    public function changeHouseWorkerPwd()
    {
        $village_id = $this->adminUser['village_id'];
        $wid = $this->request->post('wid', 0, 'int');
        $confirm_password = $this->request->post('confirm_password', '', 'trim');
        $old_password = $this->request->post('old_password', '', 'trim');
        $password = $this->request->post('password', '', 'trim');
        if (!isset($this->adminUser['wid']) || empty($this->adminUser['wid']) || ($this->adminUser['wid'] != $wid)) {
            return api_output_error(1001, '登录账号异常，请刷新页面重试！');
        }
        if (empty($old_password)) {
            return api_output_error(1001, '原密码不能为空！');
        }
        if (empty($password)) {
            return api_output_error(1001, '新密码不能为空！');
        }
        if ($password != $confirm_password) {
            return api_output_error(1001, '新密码与确认密码不一致！');
        }
        try {
            $serviceHouseWorker = new HouseWorkerService();
            $whereArr = array('wid' => $wid, 'village_id' => $village_id);
            $now_worker = $serviceHouseWorker->getOneWorker($whereArr);
            if (empty($now_worker)) {
                return api_output_error(1001, '未找到该工作人员数据！');
            }
            $old_password_md5 = md5($old_password);
            if ($now_worker['password'] != $old_password_md5) {
                return api_output_error(1001, '原密码错误，请重新输入原密码！');
            }
            $whereArr = array('wid' => $wid, 'village_id' => $village_id);
            $new_password = md5($password);
            $saveArr = array('password' => $new_password, 'account' => $now_worker['account'], 'name' => $now_worker['name']);
            $ret = $serviceHouseWorker->updateHouseWorker($whereArr, $saveArr, $now_worker);
            $housePropertyVillageWorkerService=new HousePropertyVillageWorkerService();
            if( isset($now_worker['hw_id']) && $now_worker['hw_id']>0){
                $housePropertyVillageWorkerService->synEditPropertyVillageWorker($now_worker['hw_id'],array('pwd'=>$new_password));
            }
            return api_output(0, ['wid' => $wid, 'ret' => $ret]);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

    }

    public function getOneWorker()
    {
        $village_id = $this->adminUser['village_id'];
        $serviceHouseWorker = new HouseWorkerService();
        $wid = $this->request->post('wid', 0, 'int');
        if ($wid < 1) {
            return api_output_error(1001, '参数Id错误！');
        }
        $whereArr = array('wid' => $wid, 'village_id' => $village_id);
        try {
            $now_worker = $serviceHouseWorker->getOneWorker($whereArr);
            if (empty($now_worker)) {
                return api_output_error(1001, '未找到该工作人员数据！');
            }
            return api_output(0, $now_worker);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getAllWorkerList()
    {
        $village_id = $this->adminUser['village_id'];
        $serviceHouseWorker = new HouseWorkerService();
        $whereArr = array(['village_id', '=', $village_id], ['is_del', '=', 0]);
        $whereArr[] = ['status', 'in', array(0, 1)];
        try {
            $fieldStr = 'wid,type,phone,name,openid,nickname';
            $list = $serviceHouseWorker->get_all_worker_list($whereArr, $fieldStr, 0);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }

    //检查是否有相关权限
    function checkPermissionMenu($pid = 0)
    {
        $logomenus = array();
        if (isset($this->adminUser['menus']) && !empty($this->adminUser['menus'])) {
            if (is_array($this->adminUser['menus'])) {
                $logomenus = $this->adminUser['menus'];
            } else {
                $logomenus = explode(',', $this->adminUser['menus']);
            }
        }

        if (in_array($this->login_role, array(3, 7, 105, 303))) {

            return true;
        }
        if (empty($logomenus)) {
            return 0;
        } else if (!empty($logomenus) && in_array($pid, $logomenus)) {
            return true;
        } else {
            return false;
        }
    }

    public function getRecognition()
    {
        $village_id = $this->adminUser['village_id'];
        $qrcode_id = $this->request->param('qrcode_id', '0', 'trim');
        if ($qrcode_id && $qrcode_id < 3900000000) {
            return api_output_error(1000, '二维码场景id错误！');
        }
        try {
            /*
             * $param=array('qrcode_id'=>$qrcode_id);
            $recognition = invoke_cms_model('Recognition/get_tmp_qrcode', $param);
            if($recognition && is_array($recognition)){
                return api_output(0,$recognition);
            }else{
                return api_output_error(1000,'生成二维码失败！');
            }
            */
            $service_recognition = new RecognitionService();
            $data = $service_recognition->getTmpQrcode($qrcode_id);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

    }

    public function disableWorkerAccount()
    {
        $village_id = $this->adminUser['village_id'];
        $wid = $this->request->post('wid', 0, 'int');
        $serviceHouseWorker = new HouseWorkerService();
        if ($wid < 1) {
            return api_output_error(1001, '参数Id错误！');
        }
        $whereArr = array('wid' => $wid, 'village_id' => $village_id);
        $now_worker = $serviceHouseWorker->getOneWorker($whereArr);
        if (empty($now_worker)) {
            return api_output_error(1001, '未找到该工作人员数据！');
        }
        try {
            $propertyFrameworkService = new PropertyFrameworkService();
            $propertyFrameworkService->checkWorkRelation($wid, $village_id, 1);
            $updateArr = array('status' => 4, 'openid' => '', 'avatar' => '', 'nickname' => '', 'phone' => $now_worker['phone'] . '~nouse');
            $ret = $serviceHouseWorker->updateHouseWorker($whereArr, $updateArr);
            if ($ret) {
                $serviceHouseAdmin = new HouseAdminService();
                $houseAdminWhere = array();
                if ($now_worker['xtype'] == 2 && $now_worker['relation_id'] > 0) {
                    $houseAdminWhere['id'] = $now_worker['relation_id'];
                } else {
                    $houseAdminWhere['wid'] = $wid;
                }
                $serviceHouseAdmin->editData($houseAdminWhere, ['status' => 2]);

                $houseVillageUserBindService = new HouseVillageUserBindService();
                $whereArr = ['phone' => $now_worker['phone']];
                $whereArr['village_id'] = $now_worker['village_id'];
                $whereArr['type'] = 4;
                $houseVillageUserBindService->saveUserBind($whereArr, ['status' => 4]);
                return api_output(0, $wid);
            } else {
                return api_output_error(1001, '禁用操作失败！');
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function cancelWorkerAccount()
    {
        $village_id = $this->adminUser['village_id'];
        $wid = $this->request->post('wid', 0, 'int');
        $serviceHouseWorker = new HouseWorkerService();
        if ($wid < 1) {
            return api_output_error(1001, '参数Id错误！');
        }
        $whereArr = array('wid' => $wid, 'village_id' => $village_id);
        $now_worker = $serviceHouseWorker->getOneWorker($whereArr);
        if (empty($now_worker)) {
            return api_output_error(1001, '未找到该工作人员数据！');
        }
        try {
            $updateArr = array('openid' => '', 'avatar' => '', 'nickname' => '');
            $now_worker['cancel_wx_openid']=1;
            $ret = $serviceHouseWorker->updateHouseWorker($whereArr, $updateArr,$now_worker);
            if ($ret) {
                return api_output(0, $wid);
            } else {
                return api_output_error(1001, '禁用操作失败！');
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getWorkerOrderList()
    {
        $wid = $this->request->param('wid', '0', 'int');
        $page = $this->request->param('page', '1', 'int');
        $village_id = $this->adminUser['village_id'];
        $begin_time = $this->request->param('begin_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');
        $status = $this->request->param('status', '0', 'int');
        if ($wid < 1) {
            return api_output_error(1001, '参数Id错误！');
        }
        $serviceHouseWorker = new HouseWorkerService();
        $whereArr = array('wid' => $wid, 'village_id' => $village_id);
        $now_worker = $serviceHouseWorker->getOneWorker($whereArr);
        if (empty($now_worker)) {
            return api_output_error(1001, '未找到该工作人员数据！');
        }
        $whereArr = array();
        $whereArr[] = array('r.wid', '=', $wid);
        $whereArr[] = array('r.village_id', '=', $village_id);
        if (!empty($begin_time) && !empty($end_time)) {
            $begin_time = strtotime($begin_time . '00:00:00');
            $end_time = strtotime($end_time . '23:59:59');
            $whereArr[] = array('r.time', 'between', array($begin_time, $end_time));
        } elseif (!empty($begin_time)) {
            $begin_time = strtotime($begin_time . '00:00:00');
            $whereArr[] = array('r.time', '>=', $begin_time);
        } elseif (!empty($end_time)) {
            $end_time = strtotime($end_time . '23:59:59');
            $whereArr[] = array('r.time', '<=', $end_time);
        }
        if ($status > 0) {
            $status = $status - 1;
            $whereArr[] = array('r.status', '=', $status);
        }
        $fieldStr = 'r.*,r.pigcms_id as repair_id,r.phone as repair_phone,u.name,u.usernum,u.phone,u.housesize,u.address,u.layer_num,u.room_addrss,u.floor_id,u.type as b_type,u.vacancy_id,u.user_number,u.room_number,u.single_id,u.layer_id';
        try {
            $houseVillageRepairListService = new HouseVillageRepairListService();
            $limit = 20;
            $page = $page > 0 ? $page : 0;
            $offset = ($page - 1) * $limit;
            $repair_list = $houseVillageRepairListService->getRepairList($whereArr, $offset, $limit, $fieldStr);
            return api_output(0, $repair_list);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getRepairInfo()
    {

        $village_id = $this->adminUser['village_id'];
        $wid = $this->request->param('wid', '0', 'int');
        $bind_id = $this->request->param('bind_id', '0', 'int');
        $repair_id = $this->request->param('repair_id', '0', 'int');
        if ($bind_id > 0 && $repair_id > 0) {
            $whereArr = array();
            $whereArr[] = array('r.pigcms_id', '=', $repair_id);
            $whereArr[] = array('r.bind_id', '=', $bind_id);
            $whereArr[] = array('r.village_id', '=', $village_id);
            $fieldStr = 'r.*,r.pigcms_id as repair_id,r.phone as repair_phone,u.name,u.usernum,u.phone,u.housesize,u.address,u.layer_num,u.room_addrss,u.floor_id,u.type as b_type,u.vacancy_id,u.user_number,u.room_number,u.single_id,u.layer_id';
            try {
                $houseVillageRepairListService = new HouseVillageRepairListService();
                $repair_list = $houseVillageRepairListService->getRepairList($whereArr, 0, 1, $fieldStr);
                $repair = $repair_list['list'] ? $repair_list['list']['0'] : array();
                if (empty($repair)) {
                    return api_output_error(1001, '未查到详情数据！');
                }
                $repair['worker'] = array('is_have_data' => 0);
                if ($repair['wid'] > 0) {
                    $serviceHouseWorker = new HouseWorkerService();
                    $whereArr = array('wid' => $repair['wid'], 'village_id' => $village_id);
                    $field_str = 'wid,village_id,phone,name,openid,nickname,avatar,status,create_time,weixin_num';
                    $worker = $serviceHouseWorker->getOneWorker($whereArr, $field_str);
                    if (!empty($worker)) {
                        $repair['worker'] = $worker;
                        $repair['worker']['is_have_data'] = 1;
                    }
                }
                return api_output(0, $repair);
            } catch (\Exception $e) {
                return api_output_error(-1, $e->getMessage());
            }
        } else {
            return api_output_error(1001, '参数数据错误！');
        }
    }

    //保存编辑数据
    public function saveWorkerEdit()
    {
        $village_id = $this->adminUser['village_id'];
        $wid = $this->request->post('wid', '0', 'int');
        $account = $this->request->post('account', '', 'trim');
        $account = htmlspecialchars($account, ENT_QUOTES);
        $password = $this->request->post('password', '', 'trim');
        $phone = $this->request->post('phone', '', 'trim');
        $phone = htmlspecialchars($phone, ENT_QUOTES);
        $name = $this->request->post('name', '', 'trim');
        $name = htmlspecialchars($name, ENT_QUOTES);
        $remarks = $this->request->post('remarks', '', 'trim');
        $remarks = htmlspecialchars($remarks, ENT_QUOTES);
        $xtype = $this->request->post('xtype', '', 'int');  //0账号编辑 1权限编辑
        $group_id = $this->request->post('group_id', '', 'int');
        $menus = $this->request->post('menus', '', 'trim');

        if ($wid < 1) {
            return api_output_error(1001, '参数Id错误！');
        }
        try {
            $serviceHouseWorker = new HouseWorkerService();
            $whereArr = array('wid' => $wid, 'village_id' => $village_id);
            $now_worker = $serviceHouseWorker->getOneWorker($whereArr);
            if (empty($now_worker)) {
                return api_output_error(1001, '未找到该工作人员数据！');
            }
            if ($xtype != 1) {
                if (empty($account)) {
                    return api_output_error(1001, '登录账号不能为空！');
                }

                $whereArr = array();
                $whereArr[] = array('wid', '<>', $wid);
                $whereArr[] = array('village_id', '=', $village_id);
                $whereArr[] = array('account', '=', $account);
                $whereArr[] = array('is_del', '=', 0);
                $tmp_worker = $serviceHouseWorker->getOneWorker($whereArr);
                if ($tmp_worker && $tmp_worker['wid'] > 0) {
                    unset($tmp_worker['password']);
                    $tmp_worker['is_haved_account'] = 1;
                    return api_output(0, $tmp_worker);
                }
                if ($phone) {
                    $tmpWhere = array();
                    $tmpWhere[] = array('wid', '<>', $wid);
                    $tmpWhere[] = array('village_id', '=', $village_id);
                    $tmpWhere[] = array('phone', '=', $phone);
                    $tmpWhere[] = array('is_del', '=', 0);
                    $tmp_worker = $serviceHouseWorker->getOneWorker($tmpWhere);
                    if ($tmp_worker && $tmp_worker['wid'] > 0) {
                        unset($tmp_worker['password']);
                        $tmp_worker['is_haved_phone'] = 1;
                        return api_output(0, $tmp_worker);
                    }
                }
                if ($now_worker['phone'] != $phone) {
                    $houseVillageUserBindService = new HouseVillageUserBindService();
                    $whereArr = ['phone' => $now_worker['phone']];
                    $whereArr['village_id'] = $now_worker['village_id'];
                    $whereArr['type'] = 4;
                    $houseVillageUserBindService->saveUserBind($whereArr, ['phone' => $phone, 'status' => 1]);
                }
                $saveArr = array('account' => $account, 'phone' => $phone);
                $saveArr['name'] = $name;
                $saveArr['remarks'] = $remarks;
                if (!empty($password)) {
                    $saveArr['password'] = md5($password);
                }
            } else {
                $saveArr = array('group_id' => $group_id);
                $saveArr['menus'] = htmlspecialchars($menus, ENT_QUOTES);
            }
            $whereArr = array('wid' => $wid, 'village_id' => $village_id);
            $ret = $serviceHouseWorker->updateHouseWorker($whereArr, $saveArr, $now_worker);
            $now_worker = array_merge($now_worker, $saveArr);
            $now_worker['is_haved_account'] = 0;
            $now_worker['is_haved_phone'] = 0;
            unset($now_worker['password']);

            $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);

            $queuData = [
                'logData' => [
                    'tbname' => '工作员工-编辑',
                    'table'  => 'house_worker',
                    'client' => '小区后台',
                    'trigger_path' => '组织架构管理->工作员工',
                    'trigger_type' => $this->getUpdateNmae(),
                    'addtime'      => time(),
                    'village_id'   => $this->adminUser['village_id'],
                    'property_id' =>0,
                    'op_id'   => $parseInfo['op_id'],
                    'op_type'   => $parseInfo['op_type'],
                    'op_name'   => $parseInfo['op_name'],
                ],
                'newData' => $saveArr,
                'oldData' =>$now_worker
            ];
            $this->laterLogInQueue($queuData);
            
            return api_output(0, $now_worker);

        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    //编辑老权限里人员的数据
    public function saveHouseAdminEdit()
    {
        $village_id = $this->adminUser['village_id'];
        $id = $this->request->post('id', '0', 'int');
        $account = $this->request->post('account', '', 'trim');
        $account = htmlspecialchars($account, ENT_QUOTES);
        $pwd = $this->request->post('pwd', '', 'trim');
        $phone = $this->request->post('phone', '', 'trim');
        $phone = htmlspecialchars($phone, ENT_QUOTES);
        $name = $this->request->post('realname', '', 'trim');
        $name = htmlspecialchars($name, ENT_QUOTES);
        $remarks = $this->request->post('remarks', '', 'trim');
        $remarks = htmlspecialchars($remarks, ENT_QUOTES);
        if ($id < 1) {
            return api_output_error(1001, '参数Id错误！');
        }
        try {
            $houseAdminService = new HouseAdminService();
            $whereArr = array('id' => $id, 'village_id' => $village_id);
            $now_admin = $houseAdminService->getAdminInfo($whereArr);

            if (empty($now_admin) || $now_admin->isEmpty()) {
                return api_output_error(1001, '未找到该工作人员数据！');
            }
            $now_admin = $now_admin->toArray();
            if (empty($account)) {
                return api_output_error(1001, '登录账号不能为空！');
            }
            $whereArr = array();
            $whereArr[] = array('id', '<>', $id);
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = array('account', '=', $account);
            $tmp_admin = $houseAdminService->getAdminInfo($whereArr);
            if ($tmp_admin && $tmp_admin['id'] > 0) {
                unset($tmp_admin['pwd']);
                $tmp_admin['is_haved_account'] = 1;
                return api_output(0, $tmp_admin);
            }
            if ($phone) {
                $tmpWhere = array();
                $tmpWhere[] = array('id', '<>', $id);
                $tmpWhere[] = array('village_id', '=', $village_id);
                $tmpWhere[] = array('phone', '=', $phone);
                $tmp_admin = $houseAdminService->getAdminInfo($tmpWhere);
                if ($tmp_admin && $tmp_admin['id'] > 0) {
                    unset($tmp_admin['pwd']);
                    $tmp_admin['is_haved_phone'] = 1;
                    return api_output(0, $tmp_admin);
                }
            }
            $saveArr = array('account' => $account, 'phone' => $phone);
            $saveArr['realname'] = $name;
            $saveArr['remarks'] = $remarks;
            if (!empty($pwd)) {
                $saveArr['pwd'] = md5($pwd);
            }

            $whereArr = array('id' => $id, 'village_id' => $village_id);
            $ret = $houseAdminService->editData($whereArr, $saveArr, $now_admin);

            $tmp_admin = array_merge($now_admin, $saveArr);
            $tmp_admin['is_haved_account'] = 0;
            $tmp_admin['is_haved_phone'] = 0;
            unset($tmp_admin['pwd']);
            return api_output(0, $tmp_admin);

        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

    }

    public function getRolePermissionMenus()
    {
        $village_id = $this->adminUser['village_id'];
        $wid = $this->request->post('wid', '0', 'int');
        try {
            $role_menus = array();
            if ($wid > 0) {
                $serviceHouseWorker = new HouseWorkerService();
                $whereArr = array('wid' => $wid, 'village_id' => $village_id);
                $now_worker = $serviceHouseWorker->getOneWorker($whereArr);
                if (!empty($now_worker) && !empty($now_worker['menus'])) {
                    $role_menus = explode(',', $now_worker['menus']);
                }
            }

            $houseAdminGroupService = new HouseAdminGroupService();
            $gfield = 'group_id,village_id,name,group_menus,remarks';
            $groupList = $houseAdminGroupService->getGroupList(array('village_id' => $village_id, 'status' => 1), $gfield);
            $whereMenuArr = array(array('status', '=', 1));

            $logomenus = array();
            if (isset($this->adminUser['menus']) && !empty($this->adminUser['menus'])) {
                if (is_array($this->adminUser['menus'])) {
                    $logomenus = $this->adminUser['menus'];
                } else {
                    $logomenus = explode(',', $this->adminUser['menus']);
                }
            }
            if (!empty($logomenus)) {
                $whereMenuArr[] = array('id', 'in', $logomenus);
            }
            /*
            $flage = 0;
            if (!empty($role_menus)) {
                $diff_menu = array_diff($role_menus, $logomenus);
                if ((!isset($diff_menu[0]) || $diff_menu[0]) && $diff_menu) {  // 大于当前管理员权限
                    $flage = 1;
                }
            }
            if ($flage == 0) {
                if (!empty($logomenus)) {
                    $whereMenuArr[] = array('id', 'in', $logomenus);
                }
                foreach ($groupList as $key => $value) {
                    // 过滤权限高于当前管理员的权限分组
                    $group_menus = explode(',', $value['group_menus']);
                    $group_diff_menu = array_diff($group_menus, $logomenus);
                    if ((!isset($group_diff_menu[0]) || $group_diff_menu[0]) && $group_diff_menu) { // 大于当前管理员权限
                        unset($groupList[$key]);
                    }
                }
                sort($groupList);
            }
            */
            $houseMenuNewService = new HouseMenuNewService();
            $mfield = 'id,fid,name,module,action,application_id,application_txt,sort,show,status,type';
            $menus = $houseMenuNewService->getMenuList($whereMenuArr, $mfield);
            $houseVillageService = new HouseVillageService();
            $now_village = $houseVillageService->getHouseVillageInfo(array('village_id' => $village_id));
            $property_id = 0;
            if ($now_village && !$now_village->isEmpty()) {
                $now_village = $now_village->toArray();
                $property_id = $now_village['property_id'];
            }
            $privilegePackageService = new PrivilegePackageService();
            $villageInfoExtend = $houseVillageService->getHouseVillageInfoExtend(array('village_id' => $village_id), 'village_id,works_order_switch,park_new_switch');
            $package_order_info = $privilegePackageService->disposeAuth($menus, $property_id);
            $menus = $package_order_info['list'];
            $application_id = $package_order_info['application_id'];
            //处理套餐购买功能应用 end
            $powerService = new PowerService();
            $removeIds = array();
            if ($villageInfoExtend && $villageInfoExtend['works_order_switch'] > 0) {
                //开启新版工单后 屏蔽掉老板工单相关
                $removeIds = array(219, 434, 529, 530, 531, 221, 220, 436, 222, 223, 532, 224, 435, 257, 533, 225);
            }
            if ($villageInfoExtend && $villageInfoExtend['park_new_switch'] == 1) {
                //启用新版停车后 屏蔽掉老板的
                $removeTmpeIds = array(45, 55, 49, 488, 253, 254, 489, 416, 1000, 1001, 1002, 1003, 1113, 111160, 111162, 111175);
                $removeIds = array_merge($removeIds, $removeTmpeIds);
            }
            $removeIds[] = 112163;
            $list = $powerService->getTrees($menus, $application_id, $role_menus, $removeIds,$village_id);
            $returnArr = array();
            $returnArr['menus'] = $list['tree'];
            $returnArr['mckeyArr'] = $list['ckeyArr'];
            $returnArr['group_list'] = $groupList;
            $returnArr['wid'] = $wid;
            $returnArr['login_role'] = $this->login_role;
            $returnArr['uid'] = $this->_uid;
            $returnArr['role_menus'] = $role_menus;

            return api_output(0, $returnArr);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    //角色列表
    public function getRoleList()
    {
        //角色管理-查看 权限
        $village_id = $this->adminUser['village_id'];
        $logomenus = array();
        if (isset($this->adminUser['menus']) && !empty($this->adminUser['menus'])) {
            if (is_array($this->adminUser['menus'])) {
                $logomenus = $this->adminUser['menus'];
            } else {
                $logomenus = explode(',', $this->adminUser['menus']);
            }
        }
        /*
        if (!in_array(8, $logomenus)) {
            return api_output_error(1001, '对不起，您没有权限执行此操作！');
        }
        */
        $houseAdminService = new HouseAdminService();
        $page = $this->request->param('page', '1', 'int');
        $limit = 30;
        $where = array('village_id' => $village_id, 'status' => 1, 'wid' => 0);
        try {
            $fieldStr = '*';
            $list = $houseAdminService->get_house_admin_list($where, $fieldStr, $page, $limit);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }

    //权限分组列表
    public function getGroupList()
    {
        //角色管理-查看 权限
        $village_id = $this->adminUser['village_id'];
        $logomenus = array();
        if (isset($this->adminUser['menus']) && !empty($this->adminUser['menus'])) {
            if (is_array($this->adminUser['menus'])) {
                $logomenus = $this->adminUser['menus'];
            } else {
                $logomenus = explode(',', $this->adminUser['menus']);
            }
        }
        /*
        if (!in_array(271, $logomenus)) {
            return api_output_error(1001, '对不起，您没有权限执行此操作！');
        }
        */
        $now_village = session('house');
        try {
            $houseAdminGroupService = new HouseAdminGroupService();
            $gfield = '*';
            $groupList = $houseAdminGroupService->getGroupPageList(array('village_id' => $village_id), $gfield);
            //549 工作人员-绑定和取绑微信 7工作人员-禁用
            $role_check = array();
            $role_check['role_add'] = $this->checkPermissionMenu(272);
            $role_check['role_add'] = $role_check['role_add'] === true ? 1 : 0;
            $role_check['role_edit'] = $this->checkPermissionMenu(273);
            $role_check['role_edit'] = $role_check['role_edit'] === true ? 1 : 0;
            $role_check['role_olddata'] = $this->checkPermissionMenu(10);
            $role_check['role_olddata'] = $role_check['role_olddata'] === true ? 1 : 0;
            $role_check['role_del'] = $this->checkPermissionMenu(274);
            $role_check['role_del'] = $role_check['role_del'] === true ? 1 : 0;
            $role_check['role_explod'] = $this->checkPermissionMenu(275);
            $role_check['role_explod'] = $role_check['role_explod'] === true ? 1 : 0;
            if ($this->login_role == 3 || $this->login_role == 7) {
                $role_check['role_add'] = 1;
                $role_check['role_edit'] = 1;
                $role_check['role_olddata'] = 1;
                $role_check['role_del'] = 1;
                $role_check['role_explod'] = 1;
            }
            $houseAdminService = new HouseAdminService();
            $where = array('village_id' => $village_id, 'status' => 1, 'wid' => 0);
            $adminInfoObj = $houseAdminService->getAdminInfo($where);
            if (empty($adminInfoObj) || $adminInfoObj->isEmpty()) {
                $role_check['role_olddata'] = 0;
            }
            $role_check['login_role'] = $this->login_role;
            $groupList['role_check'] = $role_check;
            return api_output(0, $groupList);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    //权限组权限
    public function getGroupPermissionMenus()
    {
        $village_id = $this->adminUser['village_id'];
        $group_id = $this->request->post('group_id', '0', 'int');
        try {
            $role_menus = array();
            $houseAdminGroupService = new HouseAdminGroupService();
            if ($group_id > 0) {
                $whereArr = array('group_id' => $group_id, 'village_id' => $village_id);
                $now_group = $houseAdminGroupService->getOneGroup($whereArr);
                if (!empty($now_group) && !empty($now_group['group_menus'])) {
                    $role_menus = explode(',', $now_group['group_menus']);
                }
            }

            $whereMenuArr = array(array('status', '=', 1));

            $logomenus = array();
            if (isset($this->adminUser['menus']) && !empty($this->adminUser['menus'])) {
                if (is_array($this->adminUser['menus'])) {
                    $logomenus = $this->adminUser['menus'];
                } else {
                    $logomenus = explode(',', $this->adminUser['menus']);
                }
            }
            $logomenus = $houseAdminGroupService->repairMenusInfo($logomenus);

            $is_system = isset($this->adminUser['is_system']) ? $this->adminUser['is_system'] : 0;
            $flage = 0;
            if (!empty($role_menus)) {
                $diff_menu = array_diff($role_menus, $logomenus);
                if ((!isset($diff_menu[0]) || $diff_menu[0]) && $diff_menu && $is_system <= 0) {  // 大于当前管理员权限
                    $flage = 1;
                }
            }
            if ($flage == 0 && !empty($logomenus)) {
                $whereMenuArr[] = array('id', 'in', $logomenus);
            }

            $houseMenuNewService = new HouseMenuNewService();
            $mfield = 'id,fid,name,module,action,application_id,application_txt,sort,show,status,type';
            $menus = $houseMenuNewService->getMenuList($whereMenuArr, $mfield);
            $houseVillageService = new HouseVillageService();

            $now_village = $houseVillageService->getHouseVillageInfo(array('village_id' => $village_id));
            $property_id = 0;
            if ($now_village && !$now_village->isEmpty()) {
                $now_village = $now_village->toArray();
                $property_id = $now_village['property_id'];
            }
            $privilegePackageService = new PrivilegePackageService();
            $package_order_info = $privilegePackageService->disposeAuth($menus, $property_id);
            $menus = $package_order_info['list'];
            $application_id = $package_order_info['application_id'];
            //处理套餐购买功能应用 end

            //$application_id=false;
            $removeIds = array();
            $villageInfoExtend = $houseVillageService->getHouseVillageInfoExtend(array('village_id' => $village_id), 'village_id,works_order_switch,park_new_switch');

            if ($villageInfoExtend && $villageInfoExtend['works_order_switch'] > 0) {
                //开启新版工单后 屏蔽掉老板工单相关
                $removeIds = array(219, 434, 529, 530, 531, 221, 220, 436, 222, 223, 532, 224, 435, 257, 533, 225);
            }
            if ($villageInfoExtend && $villageInfoExtend['park_new_switch'] == 1) {
                //启用新版停车后 屏蔽掉老板的
                $removeTmpeIds = array(45, 55, 49, 488, 253, 254, 489, 416, 1000, 1001, 1002, 1003, 1113, 111160, 111162, 111175);
                $removeIds = array_merge($removeIds, $removeTmpeIds);
            }
            $powerService = new PowerService();
            $list = $powerService->getTrees($menus, $application_id, $role_menus, $removeIds,$village_id);
            $returnArr = array();
            $returnArr['menus'] = $list['tree'];
            $returnArr['mckeyArr'] = $list['ckeyArr'];
            $returnArr['group_id'] = $group_id;
            $returnArr['login_role'] = $this->login_role;
            $returnArr['uid'] = $this->_uid;
            $returnArr['role_menus'] = $role_menus;

            return api_output(0, $returnArr);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    //权限分组保存
    public function saveHouseGroupEdit()
    {
        $village_id = $this->adminUser['village_id'];
        $group_id = $this->request->post('group_id', '0', 'int');
        $name = $this->request->post('name', '', 'trim');
        $name = htmlspecialchars($name, ENT_QUOTES);
        $remarks = $this->request->post('remarks', '', 'trim');
        $remarks = htmlspecialchars($remarks, ENT_QUOTES);
        $xtype = $this->request->post('xtype', '', 'int');  //0账号编辑 1权限编辑
        $menus = $this->request->post('menus', '', 'trim');
        $label_all = $this->request->post('label_all', '');

        try {
            $houseAdminGroupService = new HouseAdminGroupService();
            $saveArr = array();
            $saveArr['group_menus'] = '';
            if ($xtype != 1) {
                if (empty($name)) {
                    return api_output_error(1001, '分组名称不能为空！');
                }
                $saveArr = array('name' => $name, 'remarks' => $remarks, 'group_menus' => '');
                $saveArr['name'] = $name;
                $saveArr['remarks'] = $remarks;
            } else {
                $saveArr['group_menus'] = htmlspecialchars($menus, ENT_QUOTES);
            }
            if ($group_id > 0) {
                $whereArr = array('group_id' => $group_id, 'village_id' => $village_id);
                $now_group = $houseAdminGroupService->getOneGroup($whereArr);
                if (empty($now_group)) {
                    return api_output_error(1001, '未找到该权限分组数据！');
                }
                $ret = $houseAdminGroupService->updateGroup($whereArr, $saveArr);
                $now_group = array_merge($now_group, $saveArr);
                (new HouseVillageLabelService())->groupLabelRelation($village_id, $group_id, $label_all);
                return api_output(0, $now_group);
            } elseif ($xtype == 1) {
                return api_output_error(1001, '请先去基本设置中保存设置一个分组信息！');
            } else {
                $saveArr['village_id'] = $village_id;
                $saveArr['time'] = time();
                $group_id = $houseAdminGroupService->addGroup($saveArr);
                (new HouseVillageLabelService())->groupLabelRelation($village_id, $group_id, $label_all);
                $saveArr['group_id'] = $group_id;
                return api_output(0, $saveArr);
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    //删除分组
    public function delHouseGroup()
    {
        //角色管理-查看 权限
        $village_id = $this->adminUser['village_id'];
        $logomenus = array();
        if (isset($this->adminUser['menus']) && !empty($this->adminUser['menus'])) {
            if (is_array($this->adminUser['menus'])) {
                $logomenus = $this->adminUser['menus'];
            } else {
                $logomenus = explode(',', $this->adminUser['menus']);
            }
        }
        /*
        if (!in_array(274, $logomenus)) {
            return api_output_error(1001, '对不起，您没有权限执行此操作！');
        }
        */

        $group_id = $this->request->post('group_id', '0', 'int');
        if ($group_id < 1) {
            return api_output_error(1001, '参数Id错误！');
        }
        try {
            $houseAdminGroupService = new HouseAdminGroupService();
            //角色信息
            $whereArr = array('group_id' => $group_id, 'village_id' => $village_id);
            $now_group = $houseAdminGroupService->getOneGroup($whereArr);
            if (empty($now_group)) {
                return api_output_error(1001, '未找到该权限分组数据！');
            }
            $now_group['menus'] = explode(',', $now_group['group_menus']);
            
            /*
             * 判断角色权限是否小于当前管理员权限
            $is_system = $this->adminUser['is_system'] ? $this->adminUser['is_system'] : 0;
            $diff_menu = array_diff($now_group['menus'], $logomenus);
            if ((!isset($diff_menu[0]) || $diff_menu[0]) && $diff_menu && $is_system <= 0) { // 大于当前管理员权限
                return api_output_error(1001, '对不起，您没有权限删除此账号！');
            }
            */
            $result = $houseAdminGroupService->deleteGroup($whereArr);
            if ($result) {
                //删除权限分组 同步删除权限分组标签
                (new HouseVillageLabelService())->AdminGroupLabelDel([['village_id', '=', $village_id], ['group_id', '=', $group_id]]);
                return api_output(0, array('msg' => '删除成功'));
            } else {
                return api_output_error(1001, '删除失败！');
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }


    /**
     * 获取权限分组
     * @return \json
     * @author: liukezhu
     * @date : 2022/3/24
     */
    public function getPowerLabelAll()
    {
        try {
            $list = (new HouseVillageLabelService())->getPowerLabelAll($this->adminUser['village_id']);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 获取权限绑定的权限
     * @return \json
     * @author: liukezhu
     * @date : 2022/3/24
     */
    public function getPowerLabel()
    {
        $group_id = $this->request->post('group_id', '0', 'int');
        try {
            $list = (new HouseVillageLabelService())->getPowerLabel($this->adminUser['village_id'], $group_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /*
    校验工作人员是否绑定微信
    * @author: liukezhu
    * @date : 2022/6/29
    * @return \json
    */
    public function checkWorker()
    {
        $wid = $this->request->post('wid', '0', 'int');
        if (!$wid) {
            return api_output_error(1001, '缺少必要参数！');
        }
        try {
            $res = (new HouseWorkerService())->checkWorker($this->adminUser['village_id'], $wid);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }

    /**
     * 组织架构
     * @return \json
     */
    public function getOrganizationTree()
    {
        $propertyId = isset($this->adminUser['property_id']) ? $this->adminUser['property_id'] : 0;
        $villageId = isset($this->adminUser['village_id']) ? $this->adminUser['village_id'] : 0;
        try {
            $propertyFrameworkService = new PropertyFrameworkService();
            $where = array();
            $where[] = ['village_id', '=', $villageId];
            $res = $propertyFrameworkService->businessNav(2, $propertyId, $villageId, [], $where);

        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }

    /****
     *同步house_admin表数据到 house_worker下
     */
    public function synAdminToWorker()
    {
        $property_id = isset($this->adminUser['property_id']) ? $this->adminUser['property_id'] : 0;
        $village_id = isset($this->adminUser['village_id']) ? $this->adminUser['village_id'] : 0;
        $admin_id = $this->request->post('admin_id', 0, 'int');
        $account = $this->request->post('account', '', 'trim');
        $department_id = $this->request->post('department_id', 0, 'int');
        if ($department_id < 1) {
            return api_output_error(1001, '请选择一个组织架构部门！');
        }
        if ($admin_id < 1) {
            return api_output_error(1001, '参数错误！');
        }
        try {
            $propertyFrameworkService = new PropertyFrameworkService();
            $res = $propertyFrameworkService->synAdminToWorker($village_id, $admin_id, $department_id, $property_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }

    public function exportAllWorkers()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $village_name = $this->adminUser['village_name'];
        $filePath = root_path() . '../static/file/village/staff_import.xlsx';
        Calculation::getInstance()->setCalculationCacheEnabled(false);// 禁用公式的预计算
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $startRow = 2;
        $rooms = Db::table('pigcms_house_worker')
            ->pk('wid')
            ->where('village_id',$village_id)
            ->where('status',1)
            ->where('is_del',0)
            ->field('wid,phone,name,nickname,type,gender,department_id,job_create_time,job_number')
            ->cursor();

        $serviceHouseWorker = new HouseWorkerService();
        
        foreach($rooms as $row=>$room){
            $currentRow = $row + $startRow;
            $column = 'A';


            $type_name = '';
            if(isset($serviceHouseWorker->worker_name[$room['type']])){
                $type_name = $serviceHouseWorker->worker_name[$room['type']];
            }
            $gender    = $room['gender'] == 1 ? '男':'女';
            $job_create_time = $room['job_create_time'] > 1 ? date('Y-m-d',$room['job_create_time']) : '';

            $department_id = $room['department_id'];
            $hierarchyPath = Cache::get('department_id:'.$department_id);
            if (empty($singleName)){
                $hierarchyPath = $this->getHierarchyPath($room['department_id']);
                Cache::set('department_id:'.$department_id,$hierarchyPath,3600);
            }

            $data = [
                'village_name'=>$room['name'] ? $room['name'] : $room['nickname'], //姓名
                'single_name' =>$room['phone'], //手机号
                'layer'=>'', //邮箱
                'room'=>$hierarchyPath, //部门（上下级部门间使用“/”隔开）
                'room_number'=>$gender, //性别（男/女）
                'housesize'=>  $room['phone'], //工作电话
                'housesize2'=>  $room['job_number'], //工号
                'housesize3'=>$job_create_time, //入职日期
                'lougao'=>$type_name, //职位
            ];

            foreach ($data as  $value){
                $cell = $worksheet->getCell($column++ . $currentRow);
                $cell->setValue($value);
            }
            //手动释放
            $worksheet->getCell($column.$currentRow)->setValue(null);
        }

        $filename =   'staff_import-'.date('YmdHi').'.xlsx';
        (new ExportService())->phpSpreadsheet($filename, $spreadsheet);

        $arr = (new HouseNewParkingService())->downloadExportFile($filename);
        return api_output(0, $arr, 'success');
    }

    function getHierarchyPath($groupId)
    {
        $group =  Db::query("SELECT id, fid, name FROM pigcms_property_group WHERE id = :groupId  LIMIT 1;", ['groupId' => $groupId]);
        if (!$group) {
            return "";
        }
        $path = $group[0]['name']; // 当前层级名称
        if ($group[0]['fid'] != 0) {
            $parentPath = $this->getHierarchyPath($group[0]['fid']); // 递归查询上级层级路径
            $path = $parentPath . '/' . $path;
        }

        return $path;
    }
    public function getSentryWatchWorkerList()
    {
        $village_id = $this->adminUser['village_id'];
        $serviceHouseWorker = new HouseWorkerService();
        $where=array();
        $where[] = array('village_id' ,'=', $village_id);
        $where[] = array('is_del' ,'=', 0);
        $where[] = array('status' ,'<', 4);
        $where[] = array('is_sentry' ,'=', 1);
        $where[] = array('xtype' ,'=', 2);
        $where[] = array('relation_id' ,'>', 0);

        try {
            $fieldStr = 'wid,phone,name,openid,relation_id,type';
            $list = $serviceHouseWorker->get_all_worker_list($where, $fieldStr);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }
}