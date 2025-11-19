<?php

/**
 * 社区管理-控制器基础类
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/25 13:28
 */
namespace app\community\controller\manage_api;

use app\BaseController as CommonBaseController;
use app\community\model\service\ManageAppLoginService;
use app\community\model\service\AreaStreetService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\OrganizationStreetService;
use app\consts\HouseWorkConst;

class BaseController extends CommonBaseController{

    /**
     * 控制器登录用户信息
     * @var array
     */
    public $login_info;

    /**
     * 控制器登录用户uid
     * @var int
     */
    public $_uid;

    /**
     * 控制器登录用户角色
     * @var int
     */
    public $login_role;

    /**
     * 控制器登录用户权限
     * @var int
     */
    public $auth=[];


    /**
     * 配置项
     * @var int
     */
    public $config;

    /**
     * 获取登录信息
     * @access public
     * @author: wanziyang
     * @date_time: 2020/4/25 14:30
     * @param bool $is_footer 是否给予底部权限
     * @return \json
     */
    public function getLoginInfo($is_footer=true)
    {
        $log_uid = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $login_role = isset($this->request->log_extends) ? $this->request->log_extends : 0;
//        $log_uid = 21;
//        $login_role = 5;
        if (0==$log_uid || 0==$login_role) {
            $msg = [
                'status' => 1002,
                'msg' => '登录失效'
            ];
            return $msg;
        }
        $this->login_role = $login_role;
        $this->_uid = $log_uid;
        // 初始化 社区管理App 业务层
        $service_login = new ManageAppLoginService();
        //  初始化 社区 业务层
        $service_house_village = new HouseVillageService();

        $power = [];
        $footer = [];
        $now_login = [];
        if (HouseWorkConst::MANAGE_APP_STREET_WORK == $login_role) {
            //  初始化 社区 业务层
            $serviceOrganizationStreet = new OrganizationStreetService();
            $now_login = $serviceOrganizationStreet->getMemberInfo($log_uid);
            if(empty($now_login['info'])){
                $msg = [
                    'status' => 1003,
                    'msg' => '账号或密码错误'
                ];
                return $msg;
            }
            $now_login = $now_login['info'];
            unset($now_login['work_passwd']);
            // 给予登录用户名
            $work_name = isset($now_login['work_name']) && $now_login['work_name'] ? $now_login['work_name'] : '';
            $work_account = isset($now_login['work_account']) && $now_login['work_account'] ? $now_login['work_account'] : '';
            $now_login['login_name'] = $work_name ? $work_name : $work_account;
        } elseif (HouseWorkConst::MANAGE_APP_STREET_ADMIN == $login_role || HouseWorkConst::MANAGE_APP_COMMUNITY_ADMIN == $login_role) {
            //  初始化 社区 业务层
            $service_area_street = new AreaStreetService();
            $where['area_id'] = $log_uid;
            $login_info = $service_area_street->getAreaStreet($where);
            if(empty($login_info)){
                $msg = [
                    'status' => 1003,
                    'msg' => '账号或密码错误'
                ];
                return $msg;
            }
            unset($login_info['pwd']);
            // 给予登录用户名
            $login_info['login_name'] = $login_info['account'] ? $login_info['account'] : $login_info['area_name'];
        } elseif(HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN == $login_role) {
            // 获取到的是 物业总管理员
            $now_login = $service_house_village->get_house_property($log_uid);
            if (empty($now_login)) {
                $msg = [
                    'status' => 1002,
                    'msg' => '登录失效'
                ];
                return $msg;
            }
            $now_login['login_name'] = $now_login['account'] ? $now_login['account'] : $now_login['property_name'];
            $now_login['login_phone'] = $now_login['phone'];
            $now_login['property_id'] = $now_login['id'];
            unset($now_login['pwd']);
            if ($is_footer) {
                // 权限相关处理  给予全部权限
                $admin_menus = $service_house_village->getHouseMenuNew(array('status'=>1),'id');
                $power = array();
                foreach ($admin_menus as $value) {
                    $power[] = $value['id'];
                }
            }
        } elseif(HouseWorkConst::MANAGE_APP_PROPERTY_USER == $login_role) {
            // 获取到的是 物业普通管理员
            $role = $service_login->get_property_admin($log_uid);
            if (empty($role)) {
                $msg = [
                    'status' => 1002,
                    'msg' => '登录失效'
                ];
                return $msg;
            }
            $now_login = $service_house_village->get_house_property($role['property_id']);
            unset($now_login['password']);
            $now_login['village_ids']=$role['menus'];
            $now_login['login_name'] = $role['realname'] ? $role['realname'] : $now_login['account'];
            $now_login['login_phone'] = $role['phone'];
            $now_login['property_id'] = $role['property_id'];
            $now_login['wid'] = 0;
            $now_login['menus_village'] = array();   //分配的小区id
            if(isset($role['menus']) && !empty($role['menus'])){
                $menus_village=explode(',',$role['menus']);
                if(!empty($menus_village)){
                    $now_login['menus_village']=$menus_village;
                }
            }
            if(isset($role['wid'])){
                $now_login['wid'] = $role['wid'];
            }
            if ($is_footer) {
                // 权限相关处理  给予全部权限
                $admin_menus = $service_house_village->getHouseMenuNew(array('status'=>1),'id');
                $power = array();
                foreach ($admin_menus as $value) {
                    $power[] = $value['id'];
                }
            }
        } elseif(HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN == $login_role) {
            // 获取到的是 小区物业工作人员身份登录
            $role = $service_login->get_house_admin($log_uid);
            if (empty($role)) {
                $msg = [
                    'status' => 1002,
                    'msg' => '登录失效'
                ];
                return $msg;
            }
            $now_login = $service_house_village->getHouseVillage($role['village_id']);
            unset($now_login['pwd']);
            $now_login['login_name'] = $role['realname'] ? $role['realname'] : $role['account'];
            $now_login['login_phone'] = $role['phone'];
            if ($is_footer) {
                //权限
                if ($role['menus']) {
                    $power = explode(',',$role['menus']);
                } else {
                    $power = [];
                }
            }
        } elseif (HouseWorkConst::MANAGE_APP_VILLAGE_WORK == $login_role){//小区工作人员 2020/10/22 start
            $role = $service_login->getHouseWorkerInfo($log_uid);
            if (empty($role)) {
                $msg = [
                    'status' => 1002,
                    'msg' => '登录失效'
                ];
                return $msg;
            } elseif (isset($role['status']) && ($role['status']!=1 || $role['is_del']==1)) {
                $msg = [
                    'status' => 1002,
                    'msg' => '登录账号异常'
                ];
                return $msg;
            }
            $now_login = $service_house_village->getHouseVillage($role['village_id']);
            unset($now_login['pwd']);
            $now_login['login_name'] = $role['name'] ? $role['name'] : $role['account'];
            $now_login['login_phone'] = $role['phone'];
            //2020/10/22 end
        }else {
            $msg = [
                'status' => 1002,
                'msg' => '登录失效'
            ];
            return $msg;
        }
        if ($is_footer) {
            //底部菜单
            $footer = $service_house_village->getFooter($power);
        }
        $this->auth = $power;
        // 获得门密码
        $lockPwd = $service_house_village->get_lock_pwd($now_login);
        $this->login_info = $now_login;
        $arr = array(
            'login_role'	=>	$login_role,
            'user'	=>	$now_login,
            'footer'      => $footer,
            'ticket'	=>	$this->request->ticket,
            'lockPwd'    => $lockPwd,
        );
        return $arr;

    }
}