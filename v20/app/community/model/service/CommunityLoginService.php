<?php
namespace app\community\model\service;

use app\community\model\db\HouseProperty;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseWorker;
use app\community\model\db\workweixin\WorkWeixinLoginSign;
use app\community\model\db\workweixin\WorkWeixinPhone;
use app\community\model\service\AreaStreetService;
use app\community\model\service\HouseNewPorpertyService;
use app\community\model\service\HousePropertyService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\ManageAppLoginService;
use app\community\model\service\OrganizationStreetService;
use app\community\model\service\PackageOrderService;
use app\community\model\service\PropertyAdminService;
use app\consts\HouseWorkConst;
use app\consts\WorkWeiXinConst;
use token\Token;

/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/7/8 14:30
 */
class CommunityLoginService {

    /**
     * 校验登录
     * @author:zhubaodi
     * @date_time: 2022/7/8 14:24
     */
    public function login($login_account,$pwd,$village_id,$login_role,$takeEffectTimeJudge,$ip,$device_id){
        // 初始化 社区管理App 业务层
        $service_login = new ManageAppLoginService();
        //  初始化 社区 业务层
        $service_house_village = new HouseVillageService();
        //$login_role=1
        $serviceOrganizationStreet = new OrganizationStreetService();
        $role_list=[];
        $power = [];
        $dbHouseVillage = new HouseVillage();
        $dbHouseProperty = new HouseProperty();

        $config = [
            'set_house_alias'=>(customization('set_house_alias')==1 ? 1 : 0),
            'house_name'=>cfg('house_name')?:L_('小区'),
            'single_name'=>cfg('single_name')?:L_('楼栋'),
            'floor_name'=>cfg('floor_name')?:L_('单元'),
            'room_name'=>cfg('room_name')?:L_('房间'),
        ];
        if(!$village_id && $village_id && customization('set_house_alias')==1){
            $villageConfigData = $dbHouseVillage->getOne($village_id,'single_name,floor_name,room_name');
            $villageConfigData = $villageConfigData ? $villageConfigData->toArray() : [];
            if($villageConfigData){
                $config['single_name'] = $villageConfigData['single_name'] ?: $config['single_name'];
                $config['floor_name'] = $villageConfigData['floor_name'] ?: $config['floor_name'];
                $config['room_name'] = $villageConfigData['room_name'] ?: $config['room_name'];
            }
        }
        
        $where = [];
        $where[] = ['work_account','=',$login_account];
        $where[] = ['work_status','<>',4];
        $login_info_Street = $serviceOrganizationStreet->getMemberDetail($where);
        /*
        if (!empty($login_info_Street)){
            $role_list[]= ['type' => 1, 'name' => '街道工作人员'];
        }
        */
        //$login_role=3
        $condition_house_property['account'] = $login_account;
        $condition_house_property['status'] = 1;
        $house_property_info = $service_house_village->get_house_property_where($condition_house_property);
        /*
       if (!empty($house_property_info) && (!isset($house_property_info['mobile_forbid_time']) || $house_property_info['mobile_forbid_time']<1)){
           $role_list[]=  ['type' => 3, 'name' => '物业总管理员'];
       }
        */
       //$login_role=4
       $house_property_admin = $service_login->get_property_admin_where($condition_house_property);
       /*
       if (!empty($house_property_admin)){
           $check_property_id = isset($house_property_admin['property_id']) ? intval($house_property_admin['property_id']) : 0;
           if ($check_property_id) {
               $whereCheck = [];
               $whereCheck[] = ['id', '=', $check_property_id];
               $whereCheck[] = ['status', '=', 1];
               $checkInfo = $dbHouseProperty->get_one($whereCheck, 'id');
               if (isset($checkInfo['id']) && $checkInfo['id']) {
                   $role_list[]= ['type' => 4, 'name' => '物业普通管理员'];
               }
           }
       }
       */
       //$login_role=5
       $condition_house = [
           'status' => 1
       ];
       $condition_house['account'] = $login_account;
       if($village_id>0){
           $condition_house['village_id'] = $village_id;
       }
       $now_house_info = $service_house_village->getList($condition_house);
       if($now_house_info && !$now_house_info->isEmpty()){
           $now_house_info=$now_house_info->toArray();
       }else{
           $now_house_info=array();
       }
        $role_info = $service_login->get_house_admin_where($condition_house);
        $condition_admin = [];
        $condition_admin[] = ['a.status', '=', 1];
        $condition_admin[] = ['hv.status', '=', 1];
        $condition_admin[] = ['a.account', '=', $login_account];
        if($village_id>0){
            $condition_admin[] = ['a.village_id', '=', $village_id];
        }
        $field = 'a.*,hv.village_name';
        $login_admin_info = $service_login->get_admin_village_list($condition_admin,$field);
       /*
       if (!empty($now_house_info)) {
           $role_info = $service_login->get_house_admin_where($condition_house);
           if (!empty($role_info)){
               $check_village_id = isset($role_info['village_id']) ? intval($role_info['village_id']) : 0;
               if ($check_village_id) {
                   $whereCheck = [];
                   $whereCheck[] = ['village_id', '=', $check_village_id];
                   $whereCheck[] = ['status', '=', 1];
                   $checkInfo = $dbHouseVillage->getInfo($whereCheck, 'village_id');
                   if (isset($checkInfo['village_id']) && $checkInfo['village_id']) {
                       $role_list[]= ['type' => 5, 'name' => '小区物业管理人员'];
                   }
               }
           }
       }
       else{
           $condition_admin = [];
           $condition_admin[] = ['a.status', '=', 1];
           $condition_admin[] = ['hv.status', '=', 1];
           $condition_admin[] = ['a.account', '=', $login_account];
           if($village_id>0){
               $condition_admin[] = ['a.village_id', '=', $village_id];
           }
           $field = 'a.*,hv.village_name';
           $login_admin_info = $service_login->get_admin_village_list($condition_admin,$field);
           if (!empty($login_admin_info)){
               $role_list[]= ['type' => 5, 'name' => '小区物业管理人员'];
           }
       }
       */
       //$login_role=6
       $data = [
           'village_id'=>$village_id,
           'account'=>$login_account,
           'status'=>1,
       ];
       $now_house_worker_info = $service_house_village->getHouseWorker($data);
       /*
       if (!empty($now_house_worker_info)){
           $check_village_id = isset($now_house_worker_info['village_id']) ? intval($now_house_worker_info['village_id']) : 0;
           if ($check_village_id) {
               $whereCheck = [];
               $whereCheck[] = ['village_id', '=', $check_village_id];
               $whereCheck[] = ['status', '=', 1];
               $checkInfo = $dbHouseVillage->getInfo($whereCheck, 'village_id');
               if (isset($checkInfo['village_id']) && $checkInfo['village_id']) {
                   $role_list[]= ['type' => 6, 'name' => '小区物业工作人员'];
               }
           }
       }
       */
        $role_list=$this->getRoleList($login_account);
        if (1== $login_role) {
            $login_info=$login_info_Street;
            if (empty($login_info)) {
                $res=[];
                $res['error']=1002;
                $res['msg']='账号或密码错误';
                return $res;
            }
            if($pwd != $login_info['work_passwd']){
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误';
                return $res;
            }
            unset($login_info['work_passwd']);
            if($login_info['work_status'] != 1){
                $res=[];
                $res['error']=1003;
                $res['msg']='当前账号被禁止';
                return $res;
            }
            $ticket = Token::createToken($login_info['worker_id'],$login_role);
            // 获得门密码
            $lockPwd = '';
            //底部菜单
            $footer = [];
            // 给予登录用户名
            $login_info['login_name'] = $login_info['work_name'] ? $login_info['work_name'] : $login_info['work_account'];
            $arr = array(
                'role_list'=>$role_list,
                'login_role'	=>	$login_role,
                'role_type_index'=>'1_work',
                'user'	=>	$login_info,
                'footer'      => $footer,
                'ticket'	=>	$ticket,
                'lockPwd'    => $lockPwd,
                'newVersionCharge'    => $takeEffectTimeJudge,
                'config'    => $config,
            );
            // 处理登录记录和返回
            $data = [];
            $data['worker_id'] = $login_info['worker_id'];
            $data['add_time'] = $_SERVER['REQUEST_TIME'];
            $data['ip'] = $ip;
            $data['device_id'] = $device_id;
            $add_id = $serviceOrganizationStreet->addLoginLog($data);
            if($add_id){
                // 更新设备号
                $service_login->up_device($login_role, $login_info['worker_id'],$device_id);
                $res=[];
                $res['error']=0;
                $res['data']=$arr;
                return $res;
            } else{
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误';
                return $res;
            }

        }
        elseif (11== $login_role || 21 == $login_role) {
            // 街道和社区登录保留 值往后延
            // 街道登录
            //  初始化 社区 业务层
            $service_area_street = new AreaStreetService();
            $where['account'] = $login_account;
            $login_info = $service_area_street->getAreaStreet($where);
            if(empty($login_info)){
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误';
                return $res;
               //  return api_output(1003,[],'账号或密码错误！');
            }
            if($pwd != $login_info['pwd']){
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误';
                return $res;
               //  return api_output(1003,[],'账号或密码错误！');
            }
            unset($login_info['pwd']);
            if($login_info['is_open'] == 0){
                $res=[];
                $res['error']=1003;
                $res['msg']='当前账号被禁止';
                return $res;
               //  return api_output(1003,[],'当前账号被禁止！');
            }

            $ticket = Token::createToken($login_info['area_id'],$login_role);
            // 获得门密码
            $lockPwd = $service_house_village->get_lock_pwd($login_info);

            // 权限相关处理  给予全部权限
            $admin_menus = $service_house_village->getHouseMenuNew(array('status'=>1),'id');
            foreach ($admin_menus as $value) {
                $power[] = $value['id'];
            }
            //底部菜单
            $footer = $service_house_village->getFooter($power);
            // 给予登录用户名
            $login_info['login_name'] = $login_info['area_name'] ? $login_info['area_name'] : $login_info['account'];
            $arr = array(
                'role_list'=>$role_list,
                'login_role'	=>	$login_role,
                'user'	=>	$login_info,
                'footer'      => $footer,
                'ticket'	=>	$ticket,
                'lockPwd'    => $lockPwd,
                'newVersionCharge'    => $takeEffectTimeJudge,
                'config'    => $config,
            );
            // 处理登录记录和返回
            $data = [];
            $data['area_street_id'] = $login_info['area_id'];
            $data['add_time'] = $_SERVER['REQUEST_TIME'];
            $data['ip'] = $ip;
            $add_id = $service_area_street->addLoginLog($data);
            if($add_id){
                // 更新设备号
                $service_login->up_device($login_role, $login_info['area_id'],$device_id);
                $res=[];
                $res['error']=0;
                $res['data']=$arr;
                return $res;
               //  return api_output(0,$arr);
            } else{
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误';
                return $res;
                // return api_output(1003,[],'账号或密码错误！');
            }
        }
        elseif (3 == $login_role) {
            $house_property=$house_property_info;
            if (empty($house_property)) {
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误';
                return $res;
               //  return api_output(1003, [], '账号或密码错误！');
            }
            if($pwd != $house_property['password']){
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误';
                return $res;
               //  return api_output(1003,[],'账号或密码错误！');
            }
            if ($house_property['status'] != 1) {
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误';
                return $res;
               //  return api_output(1003, [], '当前账号被禁止！');
            }
            //套餐过滤 2020/11/7 start
            $servicePackageOrder = new PackageOrderService();
            $package_msg = $servicePackageOrder->judgeOrderEmploy($house_property['id']);
            if ($package_msg && array_key_exists('code', $package_msg) && $package_msg['code'] != 1) {
                $res=[];
                $res['error']=1003;
                $res['msg']=$package_msg['msg'];
                return $res;
               // return api_output(1003, [], $package_msg['msg']);
            }
            //套餐过滤 2020/11/7 end
            // 权限相关处理  给予全部权限
            $admin_menus = $service_house_village->getHouseMenuNew(array('status'=>1),'id');
            foreach ($admin_menus as $value) {
                $power[] = $value['id'];
            }
            //底部菜单
            $footer = $service_house_village->getFooter($power);
            $login_info = $house_property;
            $login_info['property_id'] = $house_property['id'];
            $ticket = Token::createToken($house_property['id'],$login_role);
            // 获得门密码
            $lockPwd = '';
            // 给予登录用户名
            $login_info['village_name'] = $house_property['property_name'] ? $house_property['property_name'] : $house_property['account'];
            $login_info['login_name'] = $house_property['property_name'] ? $house_property['property_name'] : $house_property['account'];
            if (isset($login_info['property_id']) && $login_info['property_id']) {
                $serviceHouseNewPorperty = new HouseNewPorpertyService();
                $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($login_info['property_id']);
            }
            $arr = array(
                'role_list'=>$role_list,
                'login_role'	=>	$login_role,
                'role_type_index'=>'3_property',
                'user'	=>	$login_info,
                'footer'      => $footer,
                'ticket'	=>	$ticket,
                'lockPwd'    => $lockPwd,
                'newVersionCharge'    => $takeEffectTimeJudge,
                'config'    => $config,
            );
            // 处理登录记录和返回
            $serviceHouseProperty = new HousePropertyService();
            $data = [];
            $data['last_time'] = $_SERVER['REQUEST_TIME'];
            $add_id = $serviceHouseProperty->editData(['id'=>$house_property['id']],$data);
            if($add_id){
                // 更新设备号
                $service_login->up_device($login_role, $login_info['village_id'],$device_id);
                $res=[];
                $res['error']=0;
                $res['data']=$arr;
                return $res;
               //  return api_output(0,$arr);
            } else{
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误！';
                return $res;
               //  return api_output(1003,[],'账号或密码错误！');
            }
        }
        elseif (4 == $login_role) {
            $condition_house_property['account'] = $login_account;
            $house_property=$house_property_admin;
            if(empty($house_property)){
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误！';
                return $res;
                // return api_output(1003,[],'账号或密码错误！');
            }
            if($pwd != $house_property['pwd']){
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误！';
                return $res;
                // return api_output(1003,[],'账号或密码错误！');
            }
            if($house_property['status'] != 1){
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误！';
                return $res;
               //  return api_output(1003,[],'当前账号被禁止！');
            }
            $check_property_id = isset($house_property['property_id']) ? intval($house_property['property_id']) : 0;
            if ($check_property_id) {
                $whereCheck = [];
                $whereCheck[] = ['id', '=', $check_property_id];
                $whereCheck[] = ['status', '=', 1];
                $checkInfo = $dbHouseProperty->get_one($whereCheck, 'id');
                if (!isset($checkInfo['id']) || empty($checkInfo['id'])) {
                    $res=[];
                    $res['error']=1003;
                    $res['msg']='账号或密码错误！';
                    return $res;
                }
            }
            //套餐过滤 2020/11/7 start
            $servicePackageOrder = new PackageOrderService();
            $package_msg = $servicePackageOrder->judgeOrderEmploy($house_property['property_id']);
            if($package_msg && $package_msg['code'] != 1)
            {
                $res=[];
                $res['error']=1003;
                $res['msg']=$package_msg['msg'];
                return $res;
               //  return api_output(1003,[],$package_msg['msg']);
            }
            //套餐过滤 2020/11/7 end

            // 权限相关处理  给予全部权限
            $admin_menus = $service_house_village->getHouseMenuNew(array('status'=>1),'id');
            foreach ($admin_menus as $value) {
                $power[] = $value['id'];
            }
            //底部菜单
            $footer = $service_house_village->getFooter($power);
            $login_info = $house_property;
//            $login_info['property_id'] = $house_property['id'];
            $ticket = Token::createToken($house_property['id'],$login_role);
            // 获得门密码
            $lockPwd = '';
            // 给予登录用户名
            $login_info['village_name'] = $house_property['property_name'] ? $house_property['property_name'] : $house_property['account'];
            $login_info['login_name'] = $house_property['property_name'] ? $house_property['property_name'] : $house_property['account'];
            if (isset($login_info['property_id']) && $login_info['property_id']) {
                $serviceHouseNewPorperty = new HouseNewPorpertyService();
                $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($login_info['property_id']);
            }
            $arr = array(
                'role_list'=>$role_list,
                'role_type_index'=>'4_property_admin',
                'login_role'	=>	$login_role,
                'user'	=>	$login_info,
                'footer'      => $footer,
                'ticket'	=>	$ticket,
                'lockPwd'    => $lockPwd,
                'newVersionCharge'    => $takeEffectTimeJudge,
                'config'    => $config,
            );
            // 处理登录记录和返回
            $servicePropertyAdminService = new PropertyAdminService();
            $data = [];
            $data['last_time'] = time();
            $add_id = $servicePropertyAdminService->editData(['id'=>$house_property['id']],$data);
            if($add_id){
                // 更新设备号
                $service_login->up_device($login_role, $login_info['village_id'],$device_id);
                $res=[];
                $res['error']=0;
                $res['data']=$arr;
                return $res;
               // return api_output(0,$arr);
            } else{
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误！';
                return $res;
               // return api_output(1003,[],'账号或密码错误！');
            }
        }
        elseif (5 == $login_role) {
            $now_house=$now_house_info;
//            $now_house = $now_house->toArray();
            if (!empty($now_house)) {
                // 获取到的是 小区工作人员
                $role = isset($role_info)?$role_info:[];
                if(empty($role)){
                    $res=[];
                    $res['error']=1003;
                    $res['msg']='账号或密码错误！';
                    return $res;
                    // return api_output(1003,[],'账号或密码错误！');
                }
                $login_info = $role;
                if($pwd != $role['pwd']){
                    $res=[];
                    $res['error']=1003;
                    $res['msg']='账号或密码错误！';
                    return $res;
                   //  return api_output(1003,[],'账号或密码错误！');
                }
                $village_where[] = ['village_id','=',$role['village_id']];
                $village_info = $service_house_village->getHouseVillageInfo($village_where,'property_id, status');

                if (isset($village_info['status']) && $village_info['status'] != 1) {
                    $res=[];
                    $res['error']=1003;
                    $res['msg']='账号或密码错误！';
                    return $res;
                }
                //套餐过滤 2020/11/7 start
                $servicePackageOrder = new PackageOrderService();
                $package_msg = $servicePackageOrder->judgeOrderEmploy($village_info['property_id']);
                if($package_msg && $package_msg['code'] != 1)
                {
                    $res=[];
                    $res['error']=1003;
                    $res['msg']=$package_msg['msg'];
                    return $res;
                   // return api_output(1003,[],$package_msg['msg']);
                }
                //套餐过滤 2020/11/7 end
                unset($login_info['pwd']);
                if($login_info['status'] != 1){
                    $msg = [
                        0 => '当前账号信息未完善!',
                        2 => '当前账号被禁止!',
                        3 => '当前账号被禁止!',
                        4 => '当前账号被禁止!',
                    ];
                    $res=[];
                    $res['error']=1003;
                    $res['msg']=$msg[$login_info['status']];
                    return $res;
                   //  return api_output(1003,[],$msg[$login_info['status']]);
                }
                //权限
                $power = explode(',',$role['menus']);
                //底部菜单
                $footer = $service_house_village->getFooter($power);

                $ticket = Token::createToken($role['id'],$login_role);
                // 获得门密码
                $lockPwd = $service_house_village->get_lock_pwd($login_info);
                // 处理登录记录和返回
                $data = [];
                $data['last_time'] = $_SERVER['REQUEST_TIME'];
                $add_id = $service_house_village->edit_house_village(['village_id' => $login_info['village_id']],$data);
                $login_info['login_name'] = $village_info['village_name'] ? $village_info['village_name'] : $role['account'];
                $login_info['property_id'] = $village_info['property_id'];
                if (isset($login_info['property_id']) && $login_info['property_id']) {
                    $serviceHouseNewPorperty = new HouseNewPorpertyService();
                    $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($login_info['property_id']);
                }
                if(!$village_id && $login_info['village_id'] > 0 && customization('set_house_alias')==1){
                    $villageConfigData = (new HouseVillage())->getOne($village_id,'single_name,floor_name,room_name');
                    $villageConfigData = $villageConfigData ? $villageConfigData->toArray() : [];
                    if($villageConfigData){
                        $config['single_name'] = $villageConfigData['single_name'] ?: $config['single_name'];
                        $config['floor_name'] = $villageConfigData['floor_name'] ?: $config['floor_name'];
                        $config['room_name'] = $villageConfigData['room_name'] ?: $config['room_name'];
                    }
                }
                $arr = array(
                    'role_list'=>$role_list,
                    'login_role'	=>	$login_role,
                    'role_type_index'=>'5_admin',
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                    'config'    => $config,
                );
                if($add_id){
                    // 更新设备号
                    $service_login->up_device($login_role, $login_info['village_id'],$device_id);
                    $res=[];
                    $res['error']=0;
                    $res['data']=$arr;
                    return $res;
                  //  return api_output(0,$arr);
                } else{
                    $res=[];
                    $res['error']=1003;
                    $res['msg']='账号或密码错误！';
                    return $res;
                  //   return api_output(1003,[],'账号或密码错误！');
                }
            }
            else {
                // 角色登录
//                $village_id = $this->request->param('village_id', '', 'intval');
//                $condition_house = [
//                    'status' => 1
//                ];
//                $condition_house['account'] = $login_account;
//
//                $login_admin = $service_login->get_house_admin_where($condition_house);
                $login_admin=isset($login_admin_info)?$login_admin_info:[];
                if(count($login_admin)==1){
                    $login_admin =  $login_admin[0];
                } else {
                    $now_house_arr = [];
                    foreach($login_admin as $key=>$val) {
                        if($pwd == $val['pwd']){
                            $now_house_arr[$val['id']]=$val;
                        }
                    }
                    if(empty($now_house_arr)){
                        $res=[];
                        $res['error']=1003;
                        $res['msg']='账号或密码错误！';
                        return $res;
                       // return api_output(1003,[],'账号或密码错误！');
                    }
                    ksort($now_house_arr,SORT_NUMERIC);
                    $login_admin=array_shift($now_house_arr);
                    /*
                    $arr = [
                        'village_list' => $now_house_arr,
                        'config'    => $config,
                    ];
                    $res=[];
                    $res['error']=0;
                    $res['data']=$arr;
                    return $res;
                   */
                    
                }

                if(empty($login_admin)){
                    $res=[];
                    $res['error']=1003;
                    $res['msg']='账号或密码错误！';
                    return $res;
                  //   return api_output(1003,[],'账号或密码错误！');
                }
                if($pwd != $login_admin['pwd']){
                    $res=[];
                    $res['error']=1003;
                    $res['msg']='账号或密码错误！';
                    return $res;
                  //  return api_output(1003,[],'账号或密码错误！');
                }
                if($login_admin['status'] == 2){
                    $res=[];
                    $res['error']=1003;
                    $res['msg']='当前账号被禁止！';
                    return $res;
                   // return api_output(1003,[],'当前账号被禁止！');
                }
                $login_info = $service_house_village->getHouseVillage($login_admin['village_id']);
                unset($login_info['pwd']);
                //套餐过滤 2020/11/7 start
                $servicePackageOrder = new PackageOrderService();
                $package_msg = $servicePackageOrder->judgeOrderEmploy($login_info['property_id']);
                if($package_msg && $package_msg['code'] != 1)
                {
                    $res=[];
                    $res['error']=1003;
                    $res['msg']=$package_msg['msg'];
                    return $res;
                   //  return api_output(1003,[],$package_msg['msg']);
                }
                //套餐过滤 2020/11/7 end
                //权限
                $power = explode(',',$login_admin['menus']);
                //底部菜单
                $footer = $service_house_village->getFooter($power);

                $ticket = Token::createToken($login_admin['id'],$login_role);
                // 获得门密码
                $lockPwd = $service_house_village->get_lock_pwd($login_info);
                // 处理登录记录和返回
                $data = [];
                $data['last_time'] = $_SERVER['REQUEST_TIME'];
                $add_id = $service_login->edit_house_admin(['id' => $login_admin['id']],$data);
                $login_info['login_name'] = $login_info['village_name'] ? $login_info['village_name'] : $login_admin['account'];
                if (isset($login_info['property_id']) && $login_info['property_id']) {
                    $serviceHouseNewPorperty = new HouseNewPorpertyService();
                    $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($login_info['property_id']);
                }
                if(!$village_id && $login_info['village_id'] > 0 && customization('set_house_alias')==1){
                    $villageConfigData = (new HouseVillage())->getOne($village_id,'single_name,floor_name,room_name');
                    $villageConfigData = $villageConfigData ? $villageConfigData->toArray() : [];
                    if($villageConfigData){
                        $config['single_name'] = $villageConfigData['single_name'] ?: $config['single_name'];
                        $config['floor_name'] = $villageConfigData['floor_name'] ?: $config['floor_name'];
                        $config['room_name'] = $villageConfigData['room_name'] ?: $config['room_name'];
                    }
                }
                $arr = array(
                    'role_list'=>$role_list,
                    'role_type_index'=>'5_admin',
                    'login_role'	=>	$login_role,
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                    'config'    => $config,
                );
                if($add_id){
                    // 更新设备号
                    $service_login->up_device($login_role, $login_info['village_id'],$device_id);
                    $res=[];
                    $res['error']=0;
                    $res['data']=$arr;
                    return $res;
                   //  return api_output(0,$arr);
                } else{
                    $res=[];
                    $res['error']=1003;
                    $res['msg']='账号或密码错误！';
                    return $res;
                   // return api_output(1003,[],'账号或密码错误！');
                }
            }
        }
        elseif (6 == $login_role){//小区物业工作人员 2020/10/20 start  新增
            $now_house_worker=$now_house_worker_info;
            if (!empty($now_house_worker)) {
                if($pwd != $now_house_worker['password']){
                    $res=[];
                    $res['error']=1003;
                    $res['msg']='账号或密码错误！';
                    return $res;
                   // return api_output(1003,[],'账号或密码错误！');
                }
                $check_village_id = isset($now_house_worker['village_id']) ? intval($now_house_worker['village_id']) : 0;
                if ($check_village_id) {
                    $whereCheck = [];
                    $whereCheck[] = ['village_id', '=', $check_village_id];
                    $whereCheck[] = ['status', '=', 1];
                    $checkInfo = $dbHouseVillage->getInfo($whereCheck, 'village_id');
                    if (!isset($checkInfo['village_id']) || !$checkInfo['village_id']) {
                        $res=[];
                        $res['error']=1003;
                        $res['msg']='账号或密码错误！';
                        return $res;
                    }
                }

                //套餐过滤 2020/11/7 start
                $servicePackageOrder = new PackageOrderService();
                $package_msg = $servicePackageOrder->judgeOrderEmploy($now_house_worker['property_id']);
                if($package_msg && $package_msg['code'] != 1)
                {
                    $res=[];
                    $res['error']=1003;
                    $res['msg']=$package_msg['msg'];
                    return $res;
                    //return api_output(1003,[],$package_msg['msg']);
                }
                //套餐过滤 2020/11/7 end

                unset($now_house_worker['password']);
                if($now_house_worker['status'] != 1){
                    $msg = [
                        0 => '当前账号信息未完善!',
                        2 => '当前账号被禁止!',
                        3 => '当前账号被禁止!',
                        4 => '当前账号被禁止!',
                    ];
                    $res=[];
                    $res['error']=1003;
                    $res['msg']=$msg[$now_house_worker['status']];
                    return $res;
                   // return api_output(1003,[],$msg[$now_house_worker['status']]);
                }
                $ticket = Token::createToken($now_house_worker['wid'],$login_role);
                // 给予登录用户名
                $now_house_worker['login_name'] = $now_house_worker['name'] ? $now_house_worker['name'] : $now_house_worker['nickname'];
                if (isset($now_house_worker['property_id']) && $now_house_worker['property_id']) {
                    $serviceHouseNewPorperty = new HouseNewPorpertyService();
                    $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($now_house_worker['property_id']);
                }
                if(!$village_id && $now_house_worker['village_id'] > 0 && customization('set_house_alias')==1){
                    $villageConfigData = (new HouseVillage())->getOne($village_id,'single_name,floor_name,room_name');
                    $villageConfigData = $villageConfigData ? $villageConfigData->toArray() : [];
                    if($villageConfigData){
                        $config['single_name'] = $villageConfigData['single_name'] ?: $config['single_name'];
                        $config['floor_name'] = $villageConfigData['floor_name'] ?: $config['floor_name'];
                        $config['room_name'] = $villageConfigData['room_name'] ?: $config['room_name'];
                    }
                }
                $arr = array(
                    'role_list'=>$role_list,
                    'role_type_index'=>'6_worker',
                    'login_role'	=>	$login_role,
                    'user'	=>	$now_house_worker,
                    'ticket'	=>	$ticket,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                    'config'    => $config,
                );
                // 处理登录记录和返回
                $data = [];
                $data['login_time'] = time();
                $res = $service_house_village->saveHouseWorker($now_house_worker['wid'],$data);
                if($res){
                    $res=[];
                    $res['error']=0;
                    $res['data']=$arr;
                    return $res;
                   //  return api_output(0,$arr);
                }else{
                    $res=[];
                    $res['error']=1003;
                    $res['msg']='账号或密码错误！';
                    return $res;
                   // return api_output(1003,[],'账号或密码错误！');
                }
            } else {
                $res=[];
                $res['error']=1003;
                $res['msg']='账号或密码错误！';
                return $res;
               // return api_output(1003,[],'账号或密码错误！');
            }
        }else {
            $res=[];
            $res['error']=1001;
            $res['msg']='请选择身份！';
            return $res;
           //  return api_output(1001,[],'请选择身份！');
        }
    }

    public function check($uid,$login_role,$login_role_new,$ip,$device_id,$village_id=0){
        // 初始化 社区管理App 业务层
        $service_login = new ManageAppLoginService();
        //  初始化 社区 业务层
        $service_house_village = new HouseVillageService();
        $login_account='';
        $pwd='';
        fdump_api([$uid,$login_role,$login_role_new,$ip,$device_id,$village_id],'check_login_0709',1);
        //查询当前登录账号
        if (1== $login_role) {
            // 街道登录
            //  初始化 社区 业务层
            $serviceOrganizationStreet = new OrganizationStreetService();
            $login_info = $serviceOrganizationStreet->getMemberInfo($uid);
            if (empty($login_info['info'])) {
                $res=[];
                $res['error']=1002;
                $res['msg']='未登陆！';
                return $res;
            }
            $login_info = $login_info['info'];
            // 给予登录用户名
            $login_account=$login_info['work_account'];
        }
        elseif (3 == $login_role) {
            $condition_house_property = [
                'id' => $uid
            ];
            $house_property = $service_house_village->get_house_property_where($condition_house_property);
            if (empty($house_property)) {
                $res=[];
                $res['error']=1002;
                $res['msg']='未登陆！';
                return $res;
            }
            $login_account=$house_property['account'];
        }
        elseif (4 == $login_role) {
            $condition_house_property = [
                'id' => $uid
            ];
            $house_property = $service_login->get_property_admin_where($condition_house_property);
            if (empty($house_property)) {
                $res=[];
                $res['error']=1002;
                $res['msg']='未登陆！';
                return $res;
            }
            $login_account=$house_property['account'];
        }
        elseif (5 == $login_role) {
            $condition_house = [
                'id' => $uid
            ];
            $role = $service_login->get_house_admin_where($condition_house);
            if(empty($role)){
                $res=[];
                $res['error']=1002;
                $res['msg']='未登陆！';
                return $res;
               //  return api_output(1002,[],'未登陆！');
            }
            $login_account=$role['account'];
            $village_id=$role['village_id'];
        }
        elseif (6 == $login_role) {
            $where = [
                'wid'=>$uid,
            ];
            $now_house_worker = $service_house_village->getHouseWorker($where);
            if ($now_house_worker) {
                $login_account=$now_house_worker['account'];
                $village_id=$now_house_worker['village_id'];
            } else{
                $res=[];
                $res['error']=1002;
                $res['msg']='未登陆！';
                return $res;
            }
        }
        fdump_api([$login_account,'village_id'=>$village_id],'check_login_0709',1);
        //查询需要登录的角色密码
        if (1== $login_role_new) {
            // 街道登录
            //  初始化 社区 业务层
            $where = [];
            $where[] = ['work_account','=',$login_account];
            $where[] = ['work_status','<>',4];
            $serviceOrganizationStreet = new OrganizationStreetService();
            $login_info = $serviceOrganizationStreet->getMemberDetail($where);
            if (empty($login_info)) {
                $res=[];
                $res['error']=1002;
                $res['msg']='账号或密码错误！';
                return $res;
            }
            // 给予登录密码
            $pwd=$login_info['work_passwd'];
        }
        elseif (3 == $login_role_new) {
            $condition_house_property = [
                'account' => $login_account,
                'status'=>1
            ];
            $house_property = $service_house_village->get_house_property_where($condition_house_property);
            if (empty($house_property)) {
                $res=[];
                $res['error']=1002;
                $res['msg']='账号或密码错误！';
                return $res;
            }
            $pwd=$house_property['password'];
        }
        elseif (4 == $login_role_new) {
            $condition_house_property = [
                'account' => $login_account,
                'status'=>1
            ];
            $house_property = $service_login->get_property_admin_where($condition_house_property);
            if (empty($house_property)) {
                $res=[];
                $res['error']=1002;
                $res['msg']='账号或密码错误！';
                return $res;
            }
            $pwd=$house_property['pwd'];
        }
        elseif (5 == $login_role_new) {
            $condition_house = [
                'account' => $login_account,
                'status'=>1
            ];
            if($village_id>0){
                $condition_house['village_id']=$village_id;
            }
            $role = $service_login->get_house_admin_where($condition_house);
            if(empty($role)){
                $res=[];
                $res['error']=1002;
                $res['msg']='账号或密码错误！';
                return $res;
                //  return api_output(1002,[],'未登陆！');
            }
            $pwd=$role['pwd'];
            $village_id=$role['village_id'];
        }
        elseif (6 == $login_role_new) {
            $where = [
                'account'=>$login_account,
                'status'=>1
            ];
            if($village_id>0){
                $where['village_id']=$village_id;
            }
            $now_house_worker = $service_house_village->getHouseWorker($where);
            if ($now_house_worker) {
                $pwd=$now_house_worker['password'];
                $village_id=$now_house_worker['village_id'];
            } else{
                $res=[];
                $res['error']=1002;
                $res['msg']='账号或密码错误！';
                return $res;
            }
        }
        fdump_api([$login_account,$pwd],'check_login_0709',1);
        $takeEffectTimeJudge=false;
        if (empty($login_account)||empty($pwd)||empty($login_role_new)){
            $res=[];
            $res['error']=1002;
            $res['msg']='未登陆！';
            return $res;
        }
        fdump_api([$login_account,$pwd,$village_id,$login_role_new,$takeEffectTimeJudge,$ip,$device_id],'check_login_0709',1);
        $res=$this->login($login_account,$pwd,$village_id,$login_role_new,$takeEffectTimeJudge,$ip,$device_id);
        fdump_api([$res],'check_login_0709',1);
        return $res;
    }

    public function getRoleList($login_account,$village_id=0){
        // 初始化 社区管理App 业务层
        $service_login = new ManageAppLoginService();
        //  初始化 社区 业务层
        $service_house_village = new HouseVillageService();
        //$login_role=1
        $serviceOrganizationStreet = new OrganizationStreetService();
        $role_list=[];
        $power = [];
        $where = [];
        $where[] = ['work_account','=',$login_account];
        $where[] = ['work_status','<>',4];
        $login_info_Street = $serviceOrganizationStreet->getMemberDetail($where);
        if (!empty($login_info_Street)){
            $role_list[]= ['type' => 1, 'role_type_index'=>'1_work','name' => '街道工作人员','village_id' => 0, 'attr_name' => '', 'property_id' => 0];
        }
        //$login_role=3
        $condition_house_property['account'] = $login_account;
        $condition_house_property['status'] = 1;
        $house_property_info = $service_house_village->get_house_property_where($condition_house_property);
        if (!empty($house_property_info) && (!isset($house_property_info['mobile_forbid_time']) || $house_property_info['mobile_forbid_time']<1)){
            $role_list[]=  ['type' => 3,'role_type_index'=>'3_property', 'name' => '物业总管理员', 'attr_name' => '', 'property_id' => $house_property_info['id']];
        }
        //$login_role=4
        $house_property_admin = $service_login->get_property_admin_where($condition_house_property);
        if (!empty($house_property_admin)) {
            $role_list[] = ['type' => 4,'role_type_index'=>'4_property_admin', 'name' => '物业普通管理员', 'attr_name' => '', 'property_id' => $house_property_admin['property_id']];
        }
        //$login_role=5
        $condition_admin = [];
        $condition_admin[] = ['a.status', '=', 1];
        $condition_admin[] = ['a.account', '=', $login_account];
        if ($village_id > 0) {
            $condition_admin[] = ['a.village_id', '=', $village_id];
        }
        $field = 'a.*,hv.village_name,hv.property_id';
        $login_admin_info = $service_login->get_admin_village_list($condition_admin, $field);
        $village_list = array();
        if (!empty($login_admin_info)) {
            foreach ($login_admin_info as $vv) {
                if (!isset($village_list[$vv['village_id']])) {
                    $village_list[$vv['village_id']] = array('village_id' => $vv['village_id'], 'attr_name' => $vv['village_name'], 'property_id' => $vv['property_id']);
                }
            }
            $village_list=array_values($village_list);
            $role_list[]= ['type' => 5, 'name' => '小区物业管理人员','role_type_index'=>'5_admin','village_list'=>$village_list];
        }
        
        //$login_role=6
        $data = [
            'account'=>$login_account,
            'status'=>1,
        ];
        $now_house_worker_info = $service_house_village->getHouseWorkerList($data);
        if (!empty($now_house_worker_info) && !$now_house_worker_info->isEmpty()){
            $house_workers=$now_house_worker_info->toArray();
            $village_list=array();
            foreach ($house_workers as $wv){
                if(!isset($village_list[$wv['village_id']])){
                    $villageInfo=$service_house_village->getHouseVillageInfo(['village_id'=>$wv['village_id']],'village_id,village_name,property_id');
                    $village_name='';
                    $property_id=$wv['property_id'];
                    if($villageInfo && isset($villageInfo['village_name'])){
                        $village_name=$villageInfo['village_name'];
                        $property_id=$villageInfo['property_id'];
                    }
                    $village_list[$wv['village_id']]=array('village_id'=>$wv['village_id'],'attr_name'=>$village_name,'property_id'=>$property_id);
                }
            }
            $village_list=array_values($village_list);
            $role_list[]= ['type' => 6, 'role_type_index'=>'6_worker','name' => '小区物业工作人员','village_list'=>$village_list];
        }
        return $role_list;
    }

    /**
     * 移动管理端手机号匹配登录
     * @param $mobile
     * @param int $property_id
     * @return array|string[]
     */
    public function qyLoginByMobile($mobile, $property_id = 0) {
        $sManageAppLoginService = new ManageAppLoginService();
        $service_house_village  = new HouseVillageService();
        $dbWorkWeixinPhone      = new WorkWeixinPhone();

        /********************* 匹配 手机号登录凭证 匹配上就以此身份进行登录 */
        $wherePhone = [];
        $wherePhone[] = ['type',  '=', WorkWeiXinConst::WORK_WEI_XIN_PHONE_LOGIN_TYPE];
        $wherePhone[] = ['phone', '=', $mobile];
        if ($property_id && intval($property_id) > 0) {
            // todo 目前由于和物业相关的只记录的这个 所以查询条件 直接以这个为准 后期涉及其他对应查询再做变更
            $wherePhone[] = ['from_table', '=', WorkWeiXinConst::WORK_WEI_XIN_PHONE_FROM_PROPERTY];
            $wherePhone[] = ['from_id',    '=', $property_id];
        }
        $aboutPhoneInfo = $dbWorkWeixinPhone->getSome($wherePhone);
        if ($aboutPhoneInfo && !is_array($aboutPhoneInfo)) {
            $aboutPhoneInfo = $aboutPhoneInfo->toArray();
        }
        $extends    = '';
        $memberId   = 0;
        if (!empty($aboutPhoneInfo)) {
            foreach ($aboutPhoneInfo as $item) {
                switch ($item['from_table']) {
                    case WorkWeiXinConst::WORK_WEI_XIN_PHONE_FROM_PROPERTY:
                        $memberId = $item['from_id'];
                        $extends  = HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN;
                        break;
                }
                if ($memberId && $extends) {
                    break;
                }
            }
        }
        if ($memberId && $extends) {
            // 如果账号匹配到了 对应生成
            return $this->returnTicket($memberId, $extends);
        }
        /********************* 匹配 物业总管理员 */
        $condition_house_property = [];
        $condition_house_property[] = ['account', '=', $mobile];
        $condition_house_property[] = ['status', '=', 1];
        if ($property_id && intval($property_id) > 0) {
            $condition_house_property[] = ['id', '=', $property_id];
        }
        $house_property = $service_house_village->get_house_property_where($condition_house_property);
        if ($house_property && !is_array($house_property)){
            $house_property = $house_property->toArray();
        }
        if ($house_property && isset($house_property['id'])) {
            // 如果账号匹配到了 物业超级管理员 以物业超级管理员身份登录
            return $this->returnTicket($house_property['id'], HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN);
        }
        /********************* 匹配 物业普通管理员 */
        $condition_property_admin = [];
        $condition_property_admin[] = ['account|phone', '=', $mobile];
        $condition_property_admin[] = ['status', '=', 1];
        if ($property_id && intval($property_id) > 0) {
            $condition_property_admin[] = ['property_id', '=', $property_id];
        }
        $house_property_admin = $sManageAppLoginService->get_property_admin_where($condition_property_admin);
        if ($house_property_admin && isset($house_property_admin['id'])) {
            // 如果账号匹配到了 物业管理员 以物业管理员身份登录
            return $this->returnTicket($house_property_admin['id'], HouseWorkConst::MANAGE_APP_PROPERTY_USER);
        }
        if ($property_id && intval($property_id) > 0) {
            // 如果有物业 去除相关的小区id 集合 作为下面查询条件
            $dbHouseVillage = new HouseVillage();
            $whereVillage = [];
            $whereVillage[] = ['property_id', '=', $property_id];
            $whereVillage[] = ['status',      '=', 1];
            $villageIdArr = $dbHouseVillage->getColumn($whereVillage, 'village_id');
        }
        /********************* 匹配 小区物业管理人员 */
        $sHouseAdminService = new HouseAdminService();
        $condition_admin = [];
        $condition_admin[] = ['status', '=', 1];
        $condition_admin[] = ['account|phone', '=', $mobile];
        if (isset($villageIdArr)) {
            $condition_admin[] = ['village_id', 'in', $villageIdArr];
        }
        $login_admin_info = $sHouseAdminService->getAdminInfo($condition_admin, 'id');
        if (!empty($login_admin_info)){
            // 如果账号匹配到了 小区管理员 以小区管理员身份登录
            return $this->returnTicket($login_admin_info['id'], HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN);
        }
        /********************* 匹配 小区物业工作人员 */
        $dbHouseWorker = new HouseWorker();
        $condition_work = [];
        $condition_work[] = ['status', '=', 1];
        $condition_work[] = ['is_del', '=', 0];
        $condition_work[] = ['account|phone', '=', $mobile];
        if (isset($villageIdArr)) {
            $condition_work[] = ['village_id', 'in', $villageIdArr];
        }
        $house_worker_info = $dbHouseWorker->getOne($condition_work, 'wid');
        if (!empty($house_worker_info)){
            // 如果账号匹配到了 小区工作人员 以小区工作人员身份登录
            return $this->returnTicket($house_worker_info['wid'], HouseWorkConst::MANAGE_APP_VILLAGE_WORK);
        }
        /********************* 匹配 街道工作人员 */
        if (!$property_id && !isset($villageIdArr)) {
            $serviceOrganizationStreet = new OrganizationStreetService();
            $where = [];
            $where[] = ['work_account|work_phone', '=', $mobile];
            $where[] = ['work_status', '<>', 4];
            $login_info_Street = $serviceOrganizationStreet->getMemberDetail($where, 'worker_id');
        }
        if (!empty($login_info_Street)){
            // 如果账号匹配到了 街道工作人员 以街道工作人员身份登录
            return $this->returnTicket($login_admin_info['worker_id'], HouseWorkConst::MANAGE_APP_STREET_WORK);
        }
        return [
            'login_role' => '',
            'ticket'     => '',
        ];
    }
    
    protected $expires_in = 600;// 十分钟失效
    /** 
     * 匹配返回登录tiket
     * @param $memberId
     * @param $extends
     * @return array
     */
    protected function returnTicket($memberId, $extends) {
        $ticket    = Token::createToken($memberId, $extends);
        $md5Ticket = md5($ticket);
        $dbWorkWeixinLoginSign = new WorkWeixinLoginSign();
        $now_time = time();
        $addParam = [
            'sign_md5_tiket' => $md5Ticket,
            'ticket'         => $ticket,
            'extends'        => $extends,
            'member_id'      => $memberId,
            'add_time'       => $now_time,
            'expires_time'   => $now_time + $this->expires_in,
        ];
        $dbWorkWeixinLoginSign->add($addParam);
        return [
            'login_role' => $extends,
            'md5Ticket'  => $md5Ticket,
            'ticket'  => $ticket,
        ];
    }

    /**
     * 获取登录相关信息
     * @param $md5Ticket
     * @return array
     */
    public function getLoginSignInfo($md5Ticket) {
        $dbWorkWeixinLoginSign = new WorkWeixinLoginSign();
        $now_time = time();
        $where = [];
        $where[] = ['sign_md5_tiket', '=',  $md5Ticket];
        $where[] = ['expires_time',   '>=', $now_time];
        $loginInfo = $dbWorkWeixinLoginSign->getOne($where);
        if ($loginInfo && !is_array($loginInfo)) {
            $loginInfo = $loginInfo->toArray();
        }
        $ticket    = '';
        $extends   = '';
        $member_id = '';
        if ($loginInfo && isset($loginInfo['ticket'])) {
            $ticket = $loginInfo['ticket'];
        }
        if ($loginInfo && isset($loginInfo['extends'])) {
            $extends = $loginInfo['extends'];
        }
        if ($loginInfo && isset($loginInfo['member_id'])) {
            $member_id = $loginInfo['member_id'];
        }
        if ($ticket && (!$extends || !$member_id)) {
            $token     = Token::checkToken($ticket);
            $member_id = $token['memberId'];
            $extends   = $token['extends'];
        }
        // 返回后即删除
        $whereDel = [];
        $whereDel[] = ['sign_md5_tiket', '=',  $md5Ticket];
        $dbWorkWeixinLoginSign->deleteInfo($whereDel);
        return [
            'ticket'    => $ticket,
            'member_id' => $member_id,
            'extends'   => $extends,
        ];
    }
}