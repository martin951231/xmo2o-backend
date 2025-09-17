<?php
/**
 * @author : liukezhu
 * @date : 2022/2/25
 */

namespace app\community\controller\property_api;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseMenuNewService;
use app\community\model\service\PowerService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\PrivilegePackageService;
use app\community\model\service\AreaService;
use app\community\model\service\AreaStreetService;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;

class PowerController extends CommunityBaseController
{
    use CommonLogTraits;
    use AdminLoginTraits;
    /**
     * 权限管理
     * @return \json
     * @author: liukezhu
     * @date : 2022/2/25
     */
    public function getRoleList()
    {
        $page = $this->request->param('page', 0, 'int');
        $phone = $this->request->post('phone', '', 'trim');
        $xname = $this->request->post('xname', '', 'trim');
        try {
            $where=array();
            $where[] = ['property_id', '=', $this->adminUser['property_id']];
            if($phone){
                $where[] = array('phone' ,'like','%'.$phone.'%' );
            }
            if($xname){
                $where[] = array('realname' ,'like', '%'.$xname.'%');
            }
            $field = '*';
            $res = (new PowerService())->getRoleList($where, $field, $page, 10);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }


    //获取物业小区
    public function getPropertyVillages()
    {

        $page = $this->request->param('page', 0, 'int');
        $id = $this->request->param('id', 0, 'int');
        //$property_id = $this->request->param('property_id',0,'int');
        $property_id =$this->adminUser ? $this->adminUser['property_id']:0;
        $village_name = $this->request->param('keyword', '', 'trim');
        $village_name = htmlspecialchars($village_name, ENT_QUOTES);
        $province_id = $this->request->param('province_id', '', 'intval');
        $city_id = $this->request->param('city_id', 0, 'intval');
        $area_id = $this->request->param('area_id', 0, 'intval');
        $street_id = $this->request->param('street_id', 0, 'intval');
        $community_id = $this->request->param('community_id', 0, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $comefrom = $this->request->param('comefrom', '', 'trim');
        if($comefrom=='thirdRobot'){
            $property_id=0;
        }
        $condition_where = array();
        if (!empty($village_name)) {
            $condition_where[] = array('village_name', 'like', '%' . $village_name . '%');
        }
        if (!empty($province_id) && $province_id>0) {
            $condition_where[] = array('province_id', '=', $province_id);
        }
        if (!empty($city_id) && $city_id>0) {
            $condition_where[] = array('city_id', '=', $city_id);
        }
        if (!empty($area_id)&& $area_id>0) {
            $condition_where[] = array('area_id', '=', $area_id);
        }
        if (!empty($street_id)&& $street_id>0) {
            $condition_where[] = array('street_id', '=', $street_id);
        }
        if (!empty($community_id)&& $community_id>0) {
            $condition_where[] = array('community_id', '=', $community_id);
        }
        if($property_id>0){
            $condition_where[] = array('property_id', '=', $property_id);
        }
        $condition_where[] = array('status', '<>', 5);
        try {
            $houseVillageService = new HouseVillageService();
            $limit = $pageSize>0 ? $pageSize:10;
            $page = $page > 0 ? $page : 1;
            $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
            $fieldStr='village_id,property_id,account,area_id,city_id,street_id,community_id,property_address,property_name,property_phone,village_address,village_name,village_logo';
            $list = $houseVillageService->getList($condition_where, $fieldStr, $page, $limit, 'village_id DESC',1);
            if ($list && !$list->isEmpty()) {
                $list = $list->toArray();
                foreach ($list as $kk => $vv) {
                    $list[$kk]['key'] = $vv['village_id'];
                }
                $dataArr['list'] = $list;
                $count = $houseVillageService->getVillageNum($condition_where);
                $dataArr['count'] = $count;
            }
            return api_output(0, $dataArr);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function savePropertyEdit()
    {
        $property_id = $this->adminUser['property_id'];
        $menus_village = $this->request->post('menus_village', '', 'trim');
        $menus_village = htmlspecialchars($menus_village, ENT_QUOTES);
        $realname = $this->request->post('realname', '', 'trim');
        $realname = htmlspecialchars($realname, ENT_QUOTES);
        $account = $this->request->post('account', '', 'trim');
        $account = htmlspecialchars($account, ENT_QUOTES);
        $password = $this->request->post('password', '', 'trim');
        $phone = $this->request->post('phone', '', 'trim');
        $phone = htmlspecialchars($phone, ENT_QUOTES);
        $remarks = $this->request->post('remarks', '', 'trim');
        $id = $this->request->param('id', 0, 'int');
        $remarks = htmlspecialchars($remarks, ENT_QUOTES);
        $xtype = $this->request->post('xtype', '', 'int');  //0账号编辑 1小区权限编辑 2物业权限 3物业小区权限
        if ($id < 1) {
            return api_output_error(1001, '参数Id错误！');
        }
        try {
            $propertyAdminService = new PowerService();
            $whereArr = array('id' => $id);
            $now_admin = $propertyAdminService->getOneData($whereArr);
            if (empty($now_admin)) {
                return api_output_error(1001, '未找到此条数据！');
            }
            $property_id=$now_admin['property_id'];
            if ($xtype == 2) {
                $menus_property = $this->request->post('menus_property', '', 'trim');
                $menus_property = htmlspecialchars($menus_property, ENT_QUOTES);
                $whereArr=array('admin_id'=>$id,'property_id'=>$property_id);
                $saveData=array('menus'=>$menus_property);
                $propertyAdminService->savePropertyAdminAuth($whereArr,$saveData);
                
                $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
                $queuData = [
                    'logData' => [
                        'tbname' => '物业设置-权限管理',
                        'table'  => 'property_admin_auth',
                        'client' => '物业后台',
                        'trigger_path' => '编辑->物业权限设置',
                        'trigger_type' => $this->getUpdateNmae(),
                        'addtime'      => time(),
                        'village_id'   => 0,
                        'property_id' =>$property_id,
                        'op_id'   => $parseInfo['op_id'],
                        'op_type'   => $parseInfo['op_type'],
                        'op_name'   => $parseInfo['op_name'],
                    ],
                    'newData' => $saveData,
                    'oldData' =>''
                ];
                $this->laterLogInQueue($queuData);
                return api_output(0, $id);
            }elseif($xtype == 3){
                $menus_property = $this->request->post('menus_property', '', 'trim');
                $menus_property = htmlspecialchars($menus_property, ENT_QUOTES);
                $village_id = $this->request->param('village_id', '', 'trim');
                if(empty($village_id)){
                    return api_output_error(1001, '小区参数Id错误！');
                }
                if(strpos($village_id,',')){
                    $village_id_arr=explode(',',$village_id);
                    foreach ($village_id_arr as $item){
                        $whereArr=array('admin_id'=>$id,'village_id'=>$item);
                        $saveData=array('menus'=>$menus_property);
                        $propertyAdminService->savePropertyAdminAuth($whereArr,$saveData);
                    }
                }else{
                    $whereArr=array('admin_id'=>$id,'village_id'=>$village_id);
                    $saveData=array('menus'=>$menus_property);
                    $propertyAdminService->savePropertyAdminAuth($whereArr,$saveData);
                }
                $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
                $queuData = [
                    'logData' => [
                        'tbname' => '物业设置-权限管理',
                        'table'  => 'property_admin_auth',
                        'client' => '物业后台',
                        'trigger_path' => '编辑->小区权限设置->分配权限',
                        'trigger_type' => $this->getUpdateNmae(),
                        'addtime'      => time(),
                        'village_id'   => 0,
                        'property_id' =>$property_id,
                        'op_id'   => $parseInfo['op_id'],
                        'op_type'   => $parseInfo['op_type'],
                        'op_name'   => $parseInfo['op_name'],
                    ],
                    'newData' => $saveData,
                    'oldData' =>''
                ];
                $this->laterLogInQueue($queuData);
                
                return api_output(0, $id);
            } else {
                if ($xtype != 1) {
                    if (empty($account)) {
                        return api_output_error(1001, '登录账号不能为空！');
                    }
                    $whereArr = array();
                    $whereArr[] = array('id', '<>', $id);
                    $whereArr[] = array('property_id', '=', $property_id);
                    $whereArr[] = array('account', '=', $account);
                    $tmp_admin = $propertyAdminService->getOneData($whereArr);
                    if ($tmp_admin && $tmp_admin['id'] > 0) {
                        unset($tmp_admin['pwd']);
                        $tmp_admin['is_haved_account'] = 1;
                        return api_output(0, $tmp_admin);
                    }
                    if($phone){
                        $tmpWhere = array();
                        $tmpWhere[] = array('id', '<>', $id);
                        $tmpWhere[] = array('property_id', '=', $property_id);
                        $tmpWhere[] = array('phone', '=', $phone);
                        $tmp_admin=$propertyAdminService->getOneData($tmpWhere);
                        if ($tmp_admin && $tmp_admin['id'] > 0) {
                            unset($tmp_admin['pwd']);
                            $tmp_admin['is_haved_phone'] = 1;
                            return api_output(0, $tmp_admin);
                        }
                    }
                    $saveArr = array('account' => $account, 'phone' => $phone);
                    $saveArr['realname'] = $realname;
                    $saveArr['remarks'] = $remarks;
                    if (!empty($password)) {
                        $saveArr['pwd'] = md5($password);
                    }
                } else {
                    if(!empty($menus_village)){
                        $menus_village=explode(',',$menus_village);
                        $menus_village=array_unique($menus_village);

                        $menus_village=implode(',',$menus_village);
                    }
                    $saveArr['menus'] = htmlspecialchars($menus_village, ENT_QUOTES);
                }
                $whereArr = array('id' => $id, 'property_id' => $property_id);
                $ret = $propertyAdminService->updatePropertyAdmin($whereArr, $saveArr, $now_admin);
                $now_worker = array_merge($now_admin, $saveArr);
                $now_worker['is_haved_account'] = 0;
                unset($now_worker['pwd']);

                $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
                $queuData = [
                    'logData' => [
                        'tbname' => '物业设置-权限管理',
                        'table'  => 'property_admin_auth',
                        'client' => '物业后台',
                        'trigger_path' => '编辑->小区权限设置->基本设置',
                        'trigger_type' => $this->getUpdateNmae(),
                        'addtime'      => time(),
                        'village_id'   => 0,
                        'property_id' =>$property_id,
                        'op_id'   => $parseInfo['op_id'],
                        'op_type'   => $parseInfo['op_type'],
                        'op_name'   => $parseInfo['op_name'],
                    ],
                    'newData' => $saveArr,
                    'oldData' =>''
                ];
                $this->laterLogInQueue($queuData);
                
                return api_output(0, $now_worker);
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }


    /**
     * 删除角色
     * @return \json
     * @author: liukezhu
     * @date : 2022/2/28
     */
    public function roleDel()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!$id) {
            return api_output(1002, [], '缺少必要参数！');
        }
        try {
            $res = (new PowerService())->roleDel($this->adminUser['property_id'], $id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }

    public function getPropertyRolePermission()
    {
        $property_id = $this->adminUser['property_id'];
        $id = $this->request->param('id', 0, 'int');
        try {
            $propertyAdminService = new PowerService();
            $menus = $propertyAdminService->getPropertyRolePermissionData();
            $role_menus = array();
            if ($id > 0) {
                $whereArr = ['property_id' => $property_id, 'admin_id' => $id];
                $propertyAdminAuth = $propertyAdminService->propertyAdminAuth($whereArr, 'auth_id,menus');
                if (!empty($propertyAdminAuth) && !empty($propertyAdminAuth['menus'])) {
                    $role_menus = explode(',', $propertyAdminAuth['menus']);
                }
            }
            $list = $propertyAdminService->getTrees($menus, false, $role_menus);
            $returnArr = array();
            $returnArr['role_menus'] = $role_menus;
            $returnArr['menus'] = $list['tree'];
            $returnArr['mckeyArr'] = $list['ckeyArr'];
            return api_output(0, $returnArr);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    //获取物业小区权限
    public function  getPropertyVillageRolePermission(){
        $property_id = $this->adminUser['property_id'];
        $admin_id = $this->request->param('admin_id', 0, 'int');
        $village_id = $this->request->param('village_id', 0, 'int');
        $property_id_tmp = $this->request->param('property_id', 0, 'int');
        if($property_id<1 && $property_id_tmp>0){
            $property_id=$property_id_tmp;
        }
        if($village_id<1){
           // return api_output_error(1001, '小区参数Id错误！');
        }
        try {
            $whereAuthArr = ['village_id' => $village_id, 'admin_id' => 0];
            $role_menus = array();
            if($admin_id){
                $propertyAdminService = new PowerService();
                $whereArr = array('id' => $admin_id, 'property_id' => $property_id);
                $now_admin = $propertyAdminService->getOneData($whereArr);
                if (empty($now_admin)) {
                    return api_output_error(1001, '未找到账号信息数据！');
                }
                $whereAuthArr['admin_id']=$admin_id;
            }
            $removeIds=array();
            if($village_id>0) {
                $propertyAdminAuth = $propertyAdminService->propertyAdminAuth($whereAuthArr, '*');
                if (!empty($propertyAdminAuth) && $propertyAdminAuth['menus']) {
                    $role_menus = explode(',', $propertyAdminAuth['menus']);
                }
                $houseVillageService = new HouseVillageService();
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
            }
            $removeIds[]=112163;
            $houseMenuNewService = new HouseMenuNewService();
            $mfield='id,fid,name,module,action,application_id,application_txt,sort,show,status,type';
            $whereMenuArr = array(array('status', '=', 1));
            $menus = $houseMenuNewService->getMenuList($whereMenuArr,$mfield);

            $privilegePackageService = new PrivilegePackageService();
            $package_order_info = $privilegePackageService->disposeAuth($menus, $property_id);
            $menus = $package_order_info['list'];
            $application_id = !empty($package_order_info['application_id']) ? $package_order_info['application_id']:array();
            //处理套餐购买功能应用 end
            $list = $propertyAdminService->getTrees($menus, $application_id,$role_menus,$removeIds);
            $returnArr = array();
            $returnArr['menus'] = $list['tree'];
            $returnArr['mckeyArr'] = $list['ckeyArr'];
            $returnArr['login_role'] = $this->login_role;
            $returnArr['uid'] = $this->_uid;
            return api_output(0, $returnArr);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    //获取省市区信息
    public function getProvinceCityAreas(){

        $pid = $this->request->post('pid', 0, 'int');
        $xtype = $this->request->post('xtype', 0, 'int');   //0 省 1市 2区县
        $areaService=new AreaService();
        $whereArr=array('is_open'=>1);
        if($xtype==0){
            $whereArr['area_type']=1;
        }elseif($xtype==1 && $pid<1){
            return api_output_error(1001, '您选择的省信息错误');
        }elseif($xtype==2 && $pid<1){
            return api_output_error(1001, '您选择的市信息错误');
        }else{
            $whereArr['area_pid']=$pid;
        }
        try {
            $fieldStr='area_id as id,area_name as name';
            $tmpData=$areaService->getAreaList($whereArr,$fieldStr);
            return api_output(0, $tmpData);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getStreetCommunityList(){
        $pid = $this->request->post('pid', 0, 'int');
        $xtype = $this->request->post('xtype', 0, 'int');   //0街道 1社区
        $areaStreetService=new AreaStreetService();
        $whereArr=array('is_open'=>1);
        if($xtype==0){
            $whereArr['area_type']=0;
        }elseif($xtype==1 && $pid<1){
            return api_output_error(1001, '您选择的街道信息错误');
        }else{
            $whereArr['area_pid']=$pid;
        }
        try {
            $fieldStr='area_id as id,area_name as name';
            $tmpData=$areaStreetService->getList($whereArr,$fieldStr);
            return api_output(0, $tmpData);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}