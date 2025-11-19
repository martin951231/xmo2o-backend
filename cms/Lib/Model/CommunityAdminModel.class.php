<?php

/**
 * v20 登录兼容原来的登录
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/21 17:42
 */
class CommunityAdminModel extends Model
{
    //**************  注意  \v20\app\community\model\service\AdminLoginService.php 也有一份 如果修改请同步>>>>//
    /**
     * @var integer 系统后台通过列表直接进入街道后台  存储id为:系统管理ID_街道ID
     */
    const SYSTEM_ADMIN_TO_AREA_STREET = 101;
    /**
     * @var integer 系统后台通过列表直接进入社区后台  存储id为:系统管理ID_社区ID
     */
    const SYSTEM_ADMIN_TO_AREA_COMMUNITY = 102;
    /**
     * @var integer 系统后台通过列表直接进入物业后台  存储id为:系统管理ID_物业ID
     */
    const SYSTEM_ADMIN_TO_PROPERTY = 103;
    /**
     * @var integer 系统后台通过列表直接进入小区后台  存储id为:系统管理ID_小区ID
     */
    const SYSTEM_ADMIN_TO_VILLAGE = 104;
    /**
     * @var integer 系统后台通过列表进入物业再进入小区  存储id为:系统管理ID_物业ID_小区ID
     */
    const SYSTEM_ADMIN_GO_PROPERTY_TO_VILLAGE = 105;
    /**
     * @var integer 物业管理登录 存储id为:物业管理ID
     */
    const PROPERTY_ADMIN_LOGIN = 201;
    /**
     * @var integer 物业工作人员登录 存储id为:物业工作人员角色ID
     */
    const PROPERTY_USER_LOGIN = 202;
    /**
     * @var integer 物业管理人员通过列表进入小区 存储id为:物业管理ID_小区ID
     */
    const PROPERTY_ADMIN_TO_VILLAGE = 303;
    /**
     * @var integer 物业工作人员同通过表进入小区 存储id为:物业工作人员角色ID_小区ID
     */
    const PROPERTY_USER_TO_VILLAGE = 304;
    /**
     * @var integer 小区人员登录 存储id为:小区人员ID
     */
    const VILLAGE_ADMIN_LOGIN = 401;
    /**
     * @var integer 街道社区后台登录  存储id为:街道ID -目前用这个
     */
    const STREET_COMMUNITY_ADMIN_LOGIN = 559;
    /**
     * @var integer 街道社区后台登录  存储id为:工作人员ID -目前用这个
     */
    const STREET_COMMUNITY_USER_LOGIN = 549;
    /**
     * @var integer 街道管理这页面登录进入 存储id为:街道管理ID（目前就是街道ID）
     */
    const STREET_ADMIN_LOGIN = 501;
    /**
     * @var integer  街道用户页面登录进入 这个目前还不支持 如果后面分开了 可以直接使用  存储id为:街道用户ID
     */
    const STREET_USER_LOGIN = 502;
    /**
     * @var integer  社区管理页面登录进入 目前由于使用一张表 页面登录和街道相同  存储id为:社区管理ID（目前就是社区ID）
     */
    const COMMUNITY_ADMIN_LOGIN = 601;
    /**
     * @var integer  社区用户页面登录进入 目前由于使用一张表 页面登录和街道相同  存储id为:社区用户ID
     */
    const COMMUNITY_USER_LOGIN = 602;

    /**
     * @var array|object  小区角色集合
     */
    public $villageRoleArr = [5,6,7,12,self::SYSTEM_ADMIN_TO_VILLAGE,self::SYSTEM_ADMIN_GO_PROPERTY_TO_VILLAGE,self::PROPERTY_ADMIN_TO_VILLAGE,self::PROPERTY_USER_TO_VILLAGE,self::VILLAGE_ADMIN_LOGIN];
    /**
     * @var array|object  物业角色集合
     */
    public $propertyRoleArr = [3,4,8,self::SYSTEM_ADMIN_TO_PROPERTY,self::PROPERTY_ADMIN_LOGIN,self::PROPERTY_USER_LOGIN];
    /**
     * @var array|object 物业工作人员登录
     */
    public $propertyUserArr = [4,self::PROPERTY_USER_LOGIN];
    /**
     * @var array|object  街道社区角色集合
     */
    public $streetCommunityArr = [1,9,11,self::SYSTEM_ADMIN_TO_AREA_STREET,self::SYSTEM_ADMIN_TO_AREA_COMMUNITY,self::STREET_ADMIN_LOGIN,self::STREET_COMMUNITY_ADMIN_LOGIN,self::STREET_USER_LOGIN,self::COMMUNITY_ADMIN_LOGIN,self::COMMUNITY_USER_LOGIN];
    /**
     * @var array|object 登录角色(通过登录页进入而不是点击进入的)
     */
    public $loginRoleArr = [1,3,4,5,self::PROPERTY_ADMIN_LOGIN,self::PROPERTY_USER_LOGIN,self::VILLAGE_ADMIN_LOGIN,self::STREET_COMMUNITY_ADMIN_LOGIN,self::STREET_ADMIN_LOGIN,self::STREET_USER_LOGIN,self::COMMUNITY_ADMIN_LOGIN,self::COMMUNITY_USER_LOGIN];
    /**
     * @var array|object 具备快捷进入物业和小区后台的
     */
    public $showTabRoleArr = [
        3,self::PROPERTY_ADMIN_LOGIN,self::SYSTEM_ADMIN_TO_PROPERTY,
        6,self::SYSTEM_ADMIN_TO_VILLAGE,self::SYSTEM_ADMIN_GO_PROPERTY_TO_VILLAGE,7,self::PROPERTY_ADMIN_TO_VILLAGE,
    ];
    /**
     * @var array|object 小区之间互相切换 目前是超管 否则要按照权限做区分
     */
    public $changeVillageRoleArr = [
        6,self::SYSTEM_ADMIN_TO_VILLAGE,self::SYSTEM_ADMIN_GO_PROPERTY_TO_VILLAGE,7,self::PROPERTY_ADMIN_TO_VILLAGE,
    ];
    /**
     * @var array|object 具备快捷进入的物业
     */
    public $showTabRolePropertyArr = [3,self::PROPERTY_ADMIN_LOGIN,self::SYSTEM_ADMIN_TO_PROPERTY];

    //<<<<  注意  \v20\app\community\model\service\AdminLoginService.php 也有一份 如果修改请同步**************//


    /**
     * @author: wanziyang
     * @date_time: 2020/5/21 17:43
     * @param integer $id 登录id
     * @param integer $login_role 登录者身份
     * @return bool
     */
    public function v20Login($id,$login_role=1,$session_data = [],$is_return=false)
    {
        fdump_api([$id,$login_role], 'v20LoginLog',1);
        switch($login_role){
            case self::SYSTEM_ADMIN_TO_VILLAGE:
                $info = explode('_',$id);
                $village_id = intval($info[0]);
                $admin_id = intval($info[1]);
                /***系统账号信息*/
                $systemAdmin = D('Admin')->where(['id' => $admin_id])->field('realname,account')->find();
                $database_house = D('House_village');
                $now_house = $database_house->field(true)->where(array('village_id'=>$village_id))->find();
                $admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
                $menus = array();
                foreach ($admin_menus as $value) {
                    $menus[] = $value['id'];
                }
                if (isset($systemAdmin['realname'])) {
                    $user_name = $systemAdmin['realname']?$systemAdmin['realname']:$systemAdmin['account'];
                    $user_name .= '【系统管理员】';
                } else {
                    $user_name = $now_house['village_name'] ? $now_house['village_name'] : $now_house['account'];
                }
                $now_house['menus'] = $menus;
                $now_house['user_name'] = $user_name;
                $now_house['role_id'] = 0;
                $now_house['is_system'] = 1;
                // 存储角色和id
                $now_house['sLoginRole'] = $login_role;
                $now_house['sLoginUid'] = $id;
                $now_house['show_account'] = $user_name;
                $now_house['sLoginType'] = 'system_to_house_admin';
                if ($is_return) {
                    return $now_house;
                }
                session('house',$now_house);
                // 系统后台通过列表直接进入小区后台
                break;
            case self::SYSTEM_ADMIN_TO_PROPERTY:
                $info = explode('_',$id);
                $property_id = intval($info[0]);
                $admin_id = intval($info[1]);
                /***系统账号信息*/
                $systemAdmin = D('Admin')->where(['id' => $admin_id])->field('realname,account')->find();
                $house_property = M('House_property')->field(true)->where(array('id'=>$property_id))->find();
                $house_property['login_identify'] = 1;
                if (isset($systemAdmin['realname'])) {
                    $user_name = $systemAdmin['realname']?$systemAdmin['realname']:$systemAdmin['account'];
                    $user_name .= '【系统管理员】';
                } else {
                    $user_name = $house_property['property_name']?$house_property['property_name']:'物业总管理员';
                }
                $house_property['user_name'] = $user_name;
                // 存储角色和id
                $house_property['sLoginRole'] = $login_role;
                $house_property['sLoginUid'] = $id;
                $house_property['show_account'] = $user_name;
                $now_house['sLoginType'] = 'system_to_property_admin';
                if ($is_return) {
                    return $house_property;
                }
                session('property',$house_property);
                $data_property['id'] = $house_property['id'];
                $data_property['last_time'] = $_SERVER['REQUEST_TIME'];
                M('House_property')->data($data_property)->save();
                // 系统后台通过列表直接进入物业后台
                break;
            case self::SYSTEM_ADMIN_TO_AREA_STREET:
            case self::SYSTEM_ADMIN_TO_AREA_COMMUNITY:
                // 街道/社区
                $info = explode('_',$id);
                $area_id = intval($info[0]);
                $admin_id = intval($info[1]);
                /***系统账号信息*/
                $systemAdmin = D('Admin')->where(['id' => $admin_id])->field('realname,account')->find();
                // 用户信息
                $house_area_street = M('Area_street')->where(['area_id'=> $area_id])->find();
                if (isset($systemAdmin['realname'])) {
                    $user_name = $systemAdmin['realname']?$systemAdmin['realname']:$systemAdmin['account'];
                    $user_name .= '【系统管理员】';
                } else {
                    $user_name = $house_area_street['area_name']?$house_area_street['area_name']:'管理员';
                }
                $house_property['user_name'] = $user_name;
                $house_area_street['show_account'] = $user_name;
                // 存储角色和id
                $house_area_street['sLoginRole'] = $login_role;
                $house_area_street['sLoginUid'] = $id;
                $house_area_street['sLoginType'] = 'system_to_community_admin';
                if ($is_return) {
                    return $house_area_street;
                }
                session('house_street',$house_area_street);
                break;
            case self::SYSTEM_ADMIN_GO_PROPERTY_TO_VILLAGE:
                $info = explode('_',$id);
                $village_id = intval($info[0]);
                $property_id = intval($info[1]);
                $admin_id = intval($info[2]);
                /***系统账号信息*/
                $systemAdmin = D('Admin')->where(['id' => $admin_id])->field('realname,account')->find();
                /***物业账号信息*/
                $house_property = M('House_property')->field(true)->where(array('id'=>$property_id))->find();

                $database_house = D('House_village');
                $now_house = $database_house->field(true)->where(array('village_id'=>$village_id))->find();
                $admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
                $menus = array();
                foreach ($admin_menus as $value) {
                    $menus[] = $value['id'];
                }
                if (isset($systemAdmin['realname'])) {
                    $user_name = $systemAdmin['realname']?$systemAdmin['realname']:$systemAdmin['account'];
                    /***展示登录账号*/
                    $user_name .= '【系统管理员】';
//                    $user_name .= '【系统管理员】从【'.$house_property['property_name'].'】物业进入';
                } else {
                    $user_name = $now_house['village_name'] ? $now_house['village_name'] : $now_house['account'];
                }
                $now_house['menus'] = $menus;
                $now_house['user_name'] = $user_name;
                $now_house['role_id'] = 0;
                $now_house['is_system'] = 2;
                // 存储角色和id
                $now_house['sLoginRole'] = $login_role;
                $now_house['sLoginUid'] = $id;
                $now_house['show_account'] = $user_name;
                $now_house['sLoginType'] = 'system_property_to_village_admin';
                if ($is_return) {
                    return $now_house;
                }
                session('house',$now_house);
                // 小区工作人员
                break;
            case self::PROPERTY_ADMIN_TO_VILLAGE:
                $info = explode('_',$id);
                $village_id = intval($info[0]);
                $property_id = intval($info[1]);
                /***物业账号信息*/
                $house_property = M('House_property')->field(true)->where(array('id'=>$property_id))->find();
                $database_house = D('House_village');
                $now_house = $database_house->field(true)->where(array('village_id'=>$village_id))->find();
                $admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
                $menus = array();
                foreach ($admin_menus as $value) {
                    $menus[] = $value['id'];
                }
                $now_house['menus'] = $menus;
                if (isset($house_property['property_name'])) {
                    $user_name = $house_property['property_name']?$house_property['property_name']:'物业总管理员';
                    $user_name .= '【物业管理员】';
                } else {
                    $user_name = $now_house['village_name'] ? $now_house['village_name'] : $now_house['account'];
                }
                $now_house['user_name'] = $user_name;
                $now_house['role_id'] = 0;
                $now_house['is_system'] = 3;
                // 存储角色和id
                $now_house['sLoginRole'] = $login_role;
                $now_house['sLoginUid'] = $id;
                $now_house['show_account'] = $user_name;
                $now_house['sLoginType'] = 'property_to_village_admin';
                if ($is_return) {
                    return $now_house;
                }
                session('house',$now_house);
                break;
            case self::PROPERTY_USER_TO_VILLAGE:
                $info = explode('_',$id);
                $village_id = intval($info[0]);
                $admin_id = intval($info[1]);
                /***物业用户账号信息*/
                $database_property = D('Property_admin');
                $condition_house['id'] = $admin_id;
                $now_role = $database_property->where($condition_house)->find();
                $database_house = D('House_village');
                $now_house = $database_house->field(true)->where(array('village_id'=>$village_id))->find();
                $admin_auth_where=array('admin_id'=>$admin_id,'property_id'=>0,'village_id'=>$village_id);
                $admin_auth=M('Property_admin_auth')->where($admin_auth_where)->find();
                $menus = array();
                if($admin_auth && !empty($admin_auth['menus'])){
                    $menus=explode(',',$admin_auth['menus']);
                    $menus[]= 400;  //首页id
                    $menus=array_unique($menus);
                }else{
                $admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
                    foreach ($admin_menus as $value) {
                        $menus[] = $value['id'];
                    }
                }
                $now_house['menus'] = $menus;
                if (isset($now_role['realname'])) {
                    $user_name = $now_role['property_name']?$now_role['property_name']:'物业总管理员';
                    $user_name .= '【物业管理员】';
                } else {
                    $user_name = $now_house['village_name'] ? $now_house['village_name'] : $now_house['account'];
                }
                $now_house['user_name'] = $user_name;
                $now_house['role_id'] = 0;
                $now_house['is_system'] = 3;
                // 存储角色和id
                $now_house['sLoginRole'] = $login_role;
                $now_house['sLoginUid'] = $id;
                $now_house['show_account'] = $user_name;
                $now_house['sLoginType'] = 'property_user_to_village_admin';
                if ($is_return) {
                    return $now_house;
                }
                session('house',$now_house);
                break;
            case self::PROPERTY_ADMIN_LOGIN:
            case '3':
                // 物业总管理员
                // 用户信息
                $house_property = M('House_property')->field(true)->where(array('id'=>$id))->find();
                $house_property['login_identify'] = 1;
                // 存储角色和id
                $house_property['sLoginRole'] = $login_role;
                $house_property['sLoginUid'] = $id;
                $house_property['show_account'] = $house_property['property_name']?$house_property['property_name']:'物业超级管理员';
                $house_property['sLoginType'] = 'property_admin';
                if ($is_return) {
                    return $house_property;
                }
                session('property',$house_property);
                $data_property['id'] = $house_property['id'];
                $data_property['last_time'] = $_SERVER['REQUEST_TIME'];
                M('House_property')->data($data_property)->save();
                break;
            case self::PROPERTY_USER_LOGIN:
            case '4':
                // 物业普通管理员
                // 用户信息
                $database_property = D('Property_admin');
                $condition_house['id'] = $id;
                $now_role = $database_property->where($condition_house)->find();
                $data_house = array();
                $data_house['id'] = $now_role['id'];
                $data_house['login_count'] = $now_role['login_count'] + 1;
                $data_house['last_ip'] = get_client_ip(0);;
                $data_house['last_time'] = $_SERVER['REQUEST_TIME'];
                $database_property->data($data_house)->save();
                $house_property = M('House_property')->field(true)->where(array('id'=>$now_role['property_id']))->find();
                $house_property['login_identify'] = 2;
                $house_property['menus'] = explode(',',$now_role['menus']);
                $house_property['role_id'] = 4;
                $house_property['admin_id'] = $id;
                // 存储角色和id
                $house_property['sLoginRole'] = $login_role;
                $house_property['sLoginUid'] = $id;
                $house_property['show_account'] = $now_role['realname']?$now_role['realname']:$now_role['account'];
                $house_property['sLoginType'] = 'property_user_admin';
                if ($is_return) {
                    return $house_property;
                }
                session('property',$house_property);
                break;
            case self::VILLAGE_ADMIN_LOGIN:
            case '5':
                $database_house = D('House_village');
                $database_house_admin = D('House_admin');
                $condition_house['id'] = $id;
                $now_role = $database_house_admin->get_info_one($condition_house);
                $now_house = $database_house->field(true)->where(array('village_id'=>$now_role['village_id']))->find();
                $now_house['menus'] = explode(',', $now_role['menus']);
                $now_house['user_name'] = $now_role['realname'] ? $now_role['realname'] : $now_role['account'];
                $now_house['role_id'] = $now_role['id'];
                $now_house['login_village'] = 3;
                // 存储角色和id
                $now_house['sLoginRole'] = $login_role;
                $now_house['sLoginUid'] = $id;
                $now_house['show_account'] = $now_role['realname']?$now_role['realname']:$now_role['account'];
                $now_house['sLoginType'] = 'house_admin';
                if ($is_return) {
                    return $now_house;
                }
                $now_house['house_admin_wid']=$now_role['wid'];
                session('house',$now_house);
                // 小区工作人员
                break;
            case self::STREET_COMMUNITY_ADMIN_LOGIN:
            case self::STREET_ADMIN_LOGIN:
            case self::STREET_USER_LOGIN:
            case self::COMMUNITY_ADMIN_LOGIN:
            case self::COMMUNITY_USER_LOGIN:
            case '1':
            case '9':
            case '11':
                // 街道/社区
                // 用户信息
                $area_id=$id;
                $worker_id=0;
                if(strpos($id,'_')){
                    $info = explode('_',$id);
                    $area_id = intval($info[0]);
                    $worker_id = intval($info[1]);
                }
                $house_area_street = M('Area_street')->where(['area_id'=> $area_id])->find();

                $house_area_street['show_account'] = '超级管理员';

                // 存储角色和id
                $house_area_street['sLoginRole'] = $login_role;
                $house_area_street['sLoginUid'] = $id;
                $house_area_street['sLoginType'] = 'community';
                $house_area_street['street_worker_id'] = $worker_id;
                if ($is_return) {
                    return $house_area_street;
                }
                session('house_street',$house_area_street);
                break;
            case self::STREET_COMMUNITY_USER_LOGIN:
                $area_id=$id;
                $worker_id=0;
                if(strpos($id,'_')){
                    $info = explode('_',$id);
                    $area_id = intval($info[0]);
                    $worker_id = intval($info[1]);
                }
                $house_area_street = M('Area_street')->where(['area_id'=> $area_id])->find();

                $house_area_street['show_account'] = '超级管理员';

                // 存储角色和id
                $house_area_street['sLoginRole'] = $login_role;
                $house_area_street['sLoginUid'] = $id;
                $house_area_street['sLoginType'] = 'community';
                $house_area_street['street_worker_id'] = $worker_id;
                if ($is_return) {
                    return $house_area_street;
                }
                session('house_street',$house_area_street);
                break;
            case '6':
                $database_house = D('House_village');
                $now_house = $database_house->field(true)->where(array('village_id'=>$id))->find();
                $admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
                $menus = array();
                foreach ($admin_menus as $value) {
                    $menus[] = $value['id'];
                }
                $now_house['menus'] = $menus;
                $now_house['user_name'] = $now_house['village_name'] ? $now_house['village_name'] : $now_house['account'];
                $now_house['role_id'] = 0;
                $now_house['is_system'] = 1;
                // 存储角色和id
                $now_house['sLoginRole'] = $login_role;
                $now_house['sLoginUid'] = $id;
                $now_house['show_account'] = $now_house['village_name']?$now_house['village_name']:'小区超级管理员';
                $now_house['sLoginType'] = 'house_admin';
                if ($is_return) {
                    return $now_house;
                }
                session('house',$now_house);
                // 小区工作人员
                break;
            case '7':
                $database_house = D('House_village');
                $now_house = $database_house->field(true)->where(array('village_id'=>$id))->find();
                $admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
                $menus = array();
                foreach ($admin_menus as $value) {
                    $menus[] = $value['id'];
                }
                $now_house['menus'] = $menus;
                $now_house['user_name'] = $now_house['village_name'] ? $now_house['village_name'] : $now_house['account'];
                $now_house['role_id'] = 0;
                $now_house['is_system'] = 2;
                // 存储角色和id
                $now_house['sLoginRole'] = $login_role;
                $now_house['sLoginUid'] = $id;
                $now_house['show_account'] = $now_house['village_name']?$now_house['village_name']:'小区超级管理员';
                $now_house['sLoginType'] = 'house_admin';
                if ($is_return) {
                    return $now_house;
                }
                session('house',$now_house);
                // 小区工作人员
                break;
            case '12':
                $info = explode('_',$id);
                $id = intval($info[0]);
                $admin_id = intval($info[1]);
                $database_house = D('House_village');
                $now_house = $database_house->field(true)->where(array('village_id'=>$id))->find();
                $menus = array();
                if($admin_id>0){
                    $admin_auth_where=array('admin_id'=>$admin_id,'property_id'=>0,'village_id'=>$id);
                    $admin_auth=M('Property_admin_auth')->where($admin_auth_where)->find();
                    if($admin_auth && !empty($admin_auth['menus'])){
                        $menus=explode(',',$admin_auth['menus']);
                        $menus[]= 400;  //首页id
                        $menus=array_unique($menus);
                    }
                }
                if(empty($menus)){
                    $admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
                    $menus = array();
                    foreach ($admin_menus as $value) {
                        $menus[] = $value['id'];
                    }
                }
                $now_house['menus'] = $menus;
                $now_house['user_name'] = $now_house['village_name'] ? $now_house['village_name'] : $now_house['account'];
                $now_house['role_id'] = 0;
                $now_house['is_system'] = 3;
                // 存储角色和id
                $now_house['sLoginRole'] = $login_role;
                $now_house['sLoginUid'] = $id;
                $now_house['show_account'] = $now_house['user_name'];
                $now_house['sLoginType'] = 'house_property';
                if ($is_return) {
                    return $now_house;
                }
                session('house',$now_house);
                // 物业普通管理员-》小区工作人员
                break;
            case '8':
                // 物业总管理员 系统后台点击进入
                // 用户信息
                $house_property = M('House_property')->field(true)->where(array('id'=>$id))->find();
                $house_property['login_identify'] = 1;
                // 存储角色和id
                $house_property['sLoginRole'] = $login_role;
                $house_property['sLoginUid'] = $id;
                $house_property['show_account'] = $house_property['property_name']?$house_property['property_name']:'物业总管理员';
                $now_house['sLoginType'] = 'house_property';
                if ($is_return) {
                    return $house_property;
                }
                session('property',$house_property);
                $data_property['id'] = $house_property['id'];
                $data_property['last_time'] = $_SERVER['REQUEST_TIME'];
                M('House_property')->data($data_property)->save();
                break;
            case '61':
                // 物业总管理员 物业授权进入
                // 用户信息
                $house_property = M('House_property')->field(true)->where(array('id'=>$id))->find();
                $house_property['login_identify'] = 1;
                // 标记企业微信登陆
                $house_property['isQywx'] = 1;
                // 存储角色和id
                $house_property['sLoginRole'] = $login_role;
                $house_property['sLoginUid'] = $id;
                $house_property['show_account'] = $house_property['property_name']?$house_property['property_name']:'物业总管理员';
                $now_house['sLoginType'] = 'house_property';
                if ($is_return) {
                    return $house_property;
                }
                session('property',$house_property);
                $data_property['id'] = $house_property['id'];
                $data_property['last_time'] = $_SERVER['REQUEST_TIME'];
                M('House_property')->data($data_property)->save();
                break;
            default:
                return true;
                break;
        }
        return true;
    }
}