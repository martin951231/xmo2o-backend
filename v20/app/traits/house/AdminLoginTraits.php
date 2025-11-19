<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      登录相关
 *            注意，为了避免重复名称污染，该文件的方法名统一使用 trait 开头
 */


namespace app\traits\house;


use app\community\model\service\AreaStreetService;
use app\community\model\service\HouseAdminService;
use app\community\model\service\HousePropertyService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\OrganizationStreetService;
use app\community\model\service\PropertyAdminService;
use app\common\model\service\admin_user\AdminUserService;
use app\community\model\service\AdminLoginService;
use token\Token;

trait AdminLoginTraits
{
    protected  $traitsAdminLoginService;
    
    public function traitPaseTokenRequestWhoim($log_uid, $log_extends, $ticket = '') {
        if (!$log_uid && !$log_extends && $ticket) {
            $token = Token::checkToken($ticket);
            $log_uid = $token['memberId'];
            $log_extends = $token['extends'];
        }
        if (!$this->traitsAdminLoginService) {
            $this->traitsAdminLoginService = new AdminLoginService();
        }
        $login = [];
        $login['op_id']   = 0;
        $login['op_name']='';
        $login['op_type']='';
        switch($log_extends){
            case $this->traitsAdminLoginService::SYSTEM_ADMIN_TO_VILLAGE:
            case $this->traitsAdminLoginService::SYSTEM_ADMIN_TO_PROPERTY:
            case $this->traitsAdminLoginService::SYSTEM_ADMIN_TO_AREA_STREET:
            case $this->traitsAdminLoginService::SYSTEM_ADMIN_TO_AREA_COMMUNITY:
                $info    = explode('_',$log_uid);
                $adminId = isset($info[1]) ? intval($info[1]) : 0;
                $login   = $this->filterSystemAdmin($adminId, $login);
                // 系统后台登录的小区超级管理员账号
                break;
            case $this->traitsAdminLoginService::SYSTEM_ADMIN_GO_PROPERTY_TO_VILLAGE:
                $info    = explode('_',$log_uid);
                $adminId = isset($info[2]) ? intval($info[2]) : 0;
                $login   = $this->filterSystemAdmin($adminId, $login);
                // 物业后台普通管理员身份登录的小区超级管理员账号
                break;
            case $this->traitsAdminLoginService::PROPERTY_ADMIN_TO_VILLAGE:
                $info       = explode('_',$log_uid);
                $propertyId = isset($info[1]) ? intval($info[1]) : 0;
                $login      = $this->filterPropertyAdmin($propertyId, $login);
                break;
            case $this->traitsAdminLoginService::PROPERTY_ADMIN_LOGIN:
            case '3':
            case '8':
                // 物业总管理员
                $propertyId = $log_uid;
                $login      = $this->filterPropertyAdmin($propertyId, $login);
                break;
            case $this->traitsAdminLoginService::PROPERTY_USER_TO_VILLAGE:
                $info       = explode('_',$log_uid);
                $adminId    = isset($info[1]) ? intval($info[1]) : 0;
                $login      = $this->filterPropertyAdmin($adminId, $login, false);
                break;
            case $this->traitsAdminLoginService::PROPERTY_USER_LOGIN:
            case '4':
                // 物业普通管理员
                $adminId    = $log_uid;
                $login      = $this->filterPropertyAdmin($adminId, $login, false);
                break;
            case $this->traitsAdminLoginService::VILLAGE_ADMIN_LOGIN:
            case '5':
                // 小区管理员
                $adminId    = $log_uid;
                $login      = $this->filterVillageAdmin($adminId, $login);
                break;
            case $this->traitsAdminLoginService::STREET_COMMUNITY_ADMIN_LOGIN:
            case $this->traitsAdminLoginService::STREET_ADMIN_LOGIN:
            case $this->traitsAdminLoginService::STREET_USER_LOGIN:
            case $this->traitsAdminLoginService::COMMUNITY_ADMIN_LOGIN:
            case $this->traitsAdminLoginService::COMMUNITY_USER_LOGIN:
                // 获得用户信息
                $adminId    = $log_uid;
                $login      = $this->filterVillageAdmin($adminId, $login);
                break;
            case $this->traitsAdminLoginService::STREET_COMMUNITY_USER_LOGIN:
                $info       = explode('_',$log_uid);
                $adminId    = isset($info[1]) ? intval($info[1]) : 0;
                $login      = $this->filterAreaStreetAdmin($adminId, $login, false);
                break;
            case '1':
            case '9':
            case '11':
                $info       = explode('_',$log_uid);
                $adminId    = isset($info[0]) ? intval($info[0]) : 0;
                $login      = $this->filterAreaStreetAdmin($adminId, $login);
                break;
            case '6':
            case '7':
            case '12':
                $adminId    = $log_uid;
                $login      = $this->filterVillageAdmin($adminId, $login);
                break;
            default:
                //$login = [];
                break;
        }
        return $login;
    }

    public function traitGetOpTypeName($op_type) {
        $op_type_name = '';
        if (!$this->traitsAdminLoginService) {
            $this->traitsAdminLoginService = new AdminLoginService();
        }
        switch ($op_type) {
            case $this->traitsAdminLoginService::SYS_NORMAL_ADMIN:
                $op_type_name = '系统后台普通管理员';
                break;
            case $this->traitsAdminLoginService::SYS_AREA_ADMIN:
                $op_type_name = '系统后台区域管理员';
                break;
            case $this->traitsAdminLoginService::SYS_SUPER_ADMIN:
                $op_type_name = '系统后台超级管理员';
                break;
            case $this->traitsAdminLoginService::PROPERTY_NORMAL_ADMIN:
                $op_type_name = '物业普通管理员';
                break;
            case $this->traitsAdminLoginService::PROPERTY_SUPER_ADMIN:
                $op_type_name = '物业超级管理员';
                break;
            case $this->traitsAdminLoginService::VIllAGE_NORMAL_ADMIN:
                $op_type_name = '小区普通管理员';
                break;
            case $this->traitsAdminLoginService::VIllAGE_SUPER_ADMIN:
                $op_type_name = '小区超级管理员';
                break;
            case $this->traitsAdminLoginService::COMMUNITY_WORK:
                $op_type_name = '社区工作人员';
                break;
            case $this->traitsAdminLoginService::STREET_WORK:
                $op_type_name = '街道工作人员';
                break;
            case $this->traitsAdminLoginService::COMMUNITY_ADMIN:
                $op_type_name = '社区超级管理员';
                break;
            case $this->traitsAdminLoginService::STREET_ADMIN:
                $op_type_name = '街道超级管理员';
                break;
        }
        return $op_type_name;
    }

    /**
     * 统一过滤处理 系统后台登录角色信息 涉及表 pigcms_admin
     * @param int $adminId 登录ID
     * @param array $login 要返回的信息数组
     * @return array|mixed
     */
    protected  function filterSystemAdmin($adminId, $login = []) {
        if (!$this->traitsAdminLoginService) {
            $this->traitsAdminLoginService = new AdminLoginService();
        }
        $systemAdmin = (new AdminUserService())->getNormalUserById($adminId);
        $login['op_id']   = $adminId;
        $login['op_name']='';
        $login['op_type']='';
        if ($systemAdmin && isset($systemAdmin['id'])) {
            $user_name = $systemAdmin['account'] ? $systemAdmin['account'] : $systemAdmin['realname'];
            $login['op_name'] = $user_name;
            if (isset($systemAdmin['level']) && $systemAdmin['level']==2) {
                $login['op_type'] = $this->traitsAdminLoginService::SYS_SUPER_ADMIN;
            } elseif (isset($systemAdmin['level']) && $systemAdmin['level']==1) {
                $login['op_type'] = $this->traitsAdminLoginService::SYS_AREA_ADMIN;
            } else {
                $login['op_type'] = $this->traitsAdminLoginService::SYS_NORMAL_ADMIN;
            }
        }
        return $login;
    }

    /**
     * 统一过滤物业管理员登录
     * @param integer $adminId 物业ID
     * @param array $login 要返回的信息数组
     * @param bool $isSuper 是否是超级管理员 true 时候 表 pigcms_house_property false 时候 表 pigcms_property_admin
     * @return array|mixed
     */
    protected  function filterPropertyAdmin($adminId, $login = [], $isSuper = true) {
        if (!$this->traitsAdminLoginService) {
            $this->traitsAdminLoginService = new AdminLoginService();
        }
        if ($isSuper) {
            $property = (new HousePropertyService)->getFind(['id'=>$adminId]);
            $login['op_id']   = $adminId;
            if ($property && isset($property['id'])) {
                $user_name = $property['account'] ? $property['account'] : $property['property_name'];
                $login['op_name'] = $user_name;
                $login['op_type'] = $this->traitsAdminLoginService::PROPERTY_SUPER_ADMIN;
            }
        } else {
            $propertyAdmin = (new PropertyAdminService)->getFind(['id'=>$adminId]);
            $login['op_id']   = $adminId;
            if ($propertyAdmin && isset($propertyAdmin['id'])) {
                $user_name = $propertyAdmin['account'] ? $propertyAdmin['account'] : $propertyAdmin['realname'];
                $login['op_name'] = $user_name;
                $login['op_type'] = $this->traitsAdminLoginService::PROPERTY_NORMAL_ADMIN;
            }
        }
        return $login;
    }

    /**
     * 统一过滤小区管理员登录 
     * @param integer $adminId 登录角色id
     * @param array $login 要返回的信息数组
     * @param bool $isSuper 是否是超级管理员 true 时候 表 pigcms_house_village false 时候 表 pigcms_house_admin
     * @return array|mixed
     */
    protected  function filterVillageAdmin($adminId, $login = [], $isSuper = false) {
        if (!$this->traitsAdminLoginService) {
            $this->traitsAdminLoginService = new AdminLoginService();
        }
        if (!$isSuper) {
            $villageAdmin = (new HouseAdminService)->getFind(['id'=>$adminId]);
            $login['op_id']   = $adminId;
            if ($villageAdmin && isset($villageAdmin['id'])) {
                $user_name = $villageAdmin['account'] ? $villageAdmin['account'] : $villageAdmin['realname'];
                $login['op_name'] = $user_name;
                $login['op_type'] = $this->traitsAdminLoginService::VIllAGE_NORMAL_ADMIN;
            }
        } else {
            $village = (new HouseVillageService)->getHouseVillage($adminId);
            $login['op_id']   = $adminId;
            if ($village && isset($village['id'])) {
                $user_name = $village['village_name'] ? $village['village_name'] : $village['account'];
                $login['op_name'] = $user_name;
                $login['op_type'] = $this->traitsAdminLoginService::VIllAGE_SUPER_ADMIN;
            }
        }
        return $login;
    }
    
    /**
     * 统一过滤街道管理员登录
     * @param integer $adminId 登录角色id
     * @param array $login 要返回的信息数组
     * @param bool $isSuper 是否是超级管理员 true 时候 表 pigcms_house_village false 时候 表 pigcms_house_admin
     * @return array|mixed
     */
    protected  function filterAreaStreetAdmin($adminId, $login = [], $isSuper = true) {
        if (!$this->traitsAdminLoginService) {
            $this->traitsAdminLoginService = new AdminLoginService();
        }
        if ($isSuper) {
            $areaStreet = (new AreaStreetService)->getAreaStreet(['area_id'=>$adminId]);
            $login['op_id']   = $adminId;
            if ($areaStreet && isset($areaStreet['area_id'])) {
                $user_name = $areaStreet['account'] ? $areaStreet['account'] : $areaStreet['area_name'];
                $login['op_name'] = $user_name;
                if ($areaStreet['area_type']==1) {
                    $login['op_type'] = $this->traitsAdminLoginService::COMMUNITY_ADMIN;
                } else {
                    $login['op_type'] = $this->traitsAdminLoginService::STREET_ADMIN;
                }
            }
        } else {
            $whereArr = array('worker_id'=>$adminId,'area_type'=>0);
            $fieldStr = 'worker_id,work_account,work_name,area_type';
            $streetWorkers = (new OrganizationStreetService())->getMemberDetail($whereArr,$fieldStr);
            $login['op_id']   = $adminId;
            if ($streetWorkers && isset($streetWorkers['worker_id'])) {
                $user_name = $streetWorkers['work_account'] ? $streetWorkers['work_account'] : $streetWorkers['work_name'];
                $login['op_name'] = $user_name;
                if ($streetWorkers['area_type']==1) {
                    $login['op_type'] = $this->traitsAdminLoginService::COMMUNITY_WORK;
                } else {
                    $login['op_type'] = $this->traitsAdminLoginService::STREET_WORK;
                }
            }
        }
        return $login;
    }
}