<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/20 14:35
 */

namespace app\community\controller;

use app\BaseController;

use app\community\model\service\HouseNewPorpertyService;
use app\community\model\service\AdminLoginService;
class CommunityBaseController extends BaseController{

    /**
     * 控制器登录用户信息
     * @var array
     */
    public $adminUser;

    /**
     * 小区登录角色集合
     * @var array
     */
    public $villageRole;

    /**
     * 物业登录角色集合
     * @var array
     */
    public $propertyRole;

    /**
     * 街道登录角色集合
     * @var array
     */
    public $streetCommunity;

    /**
     * 控制器登录用户uid
     * @var int
     */
    public $_uid;
    
    public $login_userId;

    /**
     * 控制器登录用户角色
     * @var int
     */
    public $login_role;

    /**
     * 普通物业管理员id
     */
    public $normal_admin_id=0;

    /**
     * @var bool
     */
    public $isHasNewBill = true;

    public $villageOrderCheckRole=array();
    /***
     ** 数据操作 权限免除角色
     **/
    public $dismissPermissionRole=array();
    public function initialize()
    {
        parent::initialize();
        $userId = trim($this->request->log_uid);
        $login_role = trim($this->request->log_extends);
        if ($userId && $login_role) {
            $adminLoginService = new AdminLoginService();
            $this->villageRole = $adminLoginService->villageRoleArr;
            $this->villageOrderCheckRole=$adminLoginService->villageOrderCheckRole;
            $this->dismissPermissionRole=$adminLoginService->dismissPermissionRole;
            $this->propertyRole = $adminLoginService->propertyRoleArr;
            $this->streetCommunity = $adminLoginService->streetCommunityArr;
            $user = $adminLoginService->handleLoginData($userId, $login_role);
            if(isset($user['normal_admin_id'])){
                $this->normal_admin_id=$user['normal_admin_id'];
            }
            // 用户id
            $this->login_userId = $userId;
            $this->_uid = $user['_userId'];
            // 用户角色
            $this->login_role = $login_role;
            // 用户信息
            $this->adminUser = $user;
            // 登录参数刷新
            $this->refresh_ticket = trim($this->request->refresh_ticket);
            if(isset($this->adminUser['property_id']) && !empty($this->adminUser['property_id'])){
                (new HouseNewPorpertyService())->checkNewCharge(strtolower($this->request->controller()),strtolower($this->request->action()),$this->adminUser['property_id']);
            }
        } else {
            $this->villageRole = [];
//            fdump_api(['登录数据缺失调用接口'.__LINE__,$_POST,$_GET,$_SERVER],'login/CommunityBaseControllerLog',1);
        }
    }
}