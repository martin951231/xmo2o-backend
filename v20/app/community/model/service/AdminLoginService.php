<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/20 17:02
 */

namespace app\community\model\service;

use app\common\model\service\admin_user\AdminUserService;
use app\community\model\db\AccessTokenCommonExpires;
use app\community\model\db\Config;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\HouseProperty;
use app\community\model\db\HousePropertyGuide;
use app\community\model\db\VillageQywxGuideAbout;
use app\community\model\service\OrganizationStreetService;
use app\community\model\service\PackageOrderService;//套餐订单
use app\community\model\service\workweixin\WorkWeiXinSuiteService;
use app\traits\CommonLogTraits;
use token\Token;
use think\facade\Request;
use think\facade\Cache;
use app\traits\house\AdminLoginTraits;
class AdminLoginService
{

    use CommonLogTraits;
    use AdminLoginTraits;

    //**************  注意  \cms\Lib\Model\CommunityAdminModel.class.php 也有一份 如果修改请同步>>>>//
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
	 * ----------------------------------------------------------------------------------------
	 *  社区 各个角色集合 开始
	 * ----------------------------------------------------------------------------------------
	 */

    /**
     * @var array|object  小区角色集合
     */
    public $villageRoleArr = [5,6,7,12,self::SYSTEM_ADMIN_TO_VILLAGE,self::SYSTEM_ADMIN_GO_PROPERTY_TO_VILLAGE,self::PROPERTY_ADMIN_TO_VILLAGE,self::PROPERTY_USER_TO_VILLAGE,self::VILLAGE_ADMIN_LOGIN];
    /**
    * 订单作废或者退款审核
     **/
    public $villageOrderCheckRole = [5,self::VILLAGE_ADMIN_LOGIN];
    /***
    ** 数据操作 权限免除角色
     **/
    public $dismissPermissionRole= [3,7,self::SYSTEM_ADMIN_TO_VILLAGE,self::SYSTEM_ADMIN_GO_PROPERTY_TO_VILLAGE,self::PROPERTY_ADMIN_TO_VILLAGE];
    /**
     * @var array|object  物业角色集合 -- 物业创始人
     */
    public $propertyRoleArr = [3,4,8,self::SYSTEM_ADMIN_TO_PROPERTY,self::PROPERTY_ADMIN_LOGIN,self::PROPERTY_USER_LOGIN];

    /**
     * @var array|object 物业工作人员登录
     */
    public $propertyUserArr = [4,self::PROPERTY_USER_LOGIN];
    /**
     * @var array|object 小区工作人员登录
     */
    public $villageUserArr = [5,self::VILLAGE_ADMIN_LOGIN];
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
        6,self::SYSTEM_ADMIN_TO_VILLAGE,self::SYSTEM_ADMIN_GO_PROPERTY_TO_VILLAGE,self::PROPERTY_ADMIN_TO_VILLAGE,
    ];
    /**
     * @var array|object 具备快捷进入的物业
     */
    public $showTabRolePropertyArr = [3,self::PROPERTY_ADMIN_LOGIN,self::SYSTEM_ADMIN_TO_PROPERTY];

    
	/**
	 * ----------------------------------------------------------------------------------------
	 *  社区 各个角色集合 结束
	 * ----------------------------------------------------------------------------------------
	 */

	//<<<<  注意  \cms\Lib\Model\CommunityAdminModel.class.php 也有一份 如果修改请同步**************//
    /**
     * @var string  企业微信请求网址
     */
    public $post_url = 'https://shequ-demo.fastwhale.com.cn/';

    /** @var int 系统后台普通管理员 */
    const SYS_NORMAL_ADMIN = 0;
    /** @var int 系统后台区域管理员 */
    const SYS_AREA_ADMIN   = 1;
    /** @var int 系统后台超级管理员 */
    const SYS_SUPER_ADMIN  = 2;

    /** @var int 物业普通管理员 */
    const PROPERTY_NORMAL_ADMIN  = 11;
    /** @var int 物业超级管理员 */
    const PROPERTY_SUPER_ADMIN  = 12;

    /** @var int 小区普通管理员 */
    const VIllAGE_NORMAL_ADMIN  = 21;
    /** @var int 小区超级管理员 */
    const VIllAGE_SUPER_ADMIN  = 22;

    /** @var int 社区工作人员 */
    const COMMUNITY_WORK  = 31;
    /** @var int 街道工作人员 */
    const STREET_WORK  = 32;

    /** @var int 社区超级管理员 */
    const COMMUNITY_ADMIN  = 33;
    /** @var int 街道超级管理员 */
    const STREET_ADMIN  = 34;

	/**
	 * 获取小区的登录角色ID集合
	 * PC 端，暂时不包含移动管理端(TODO 后期需要补上)
	 * @return int[]
	 */
	public function getVillageLoginRoleNumberArr()
	{
		return $this->villageRoleArr;
	}

	/**
	 * 获取物业登录角色ID集合
	 * @return array|int[]|object
	 */
	public function getPropertyLoginRoleNumberArr()
	{
		return $this->propertyRoleArr;
	}
	
    /**
     * 获取后台登录角色
     * @author: wanziyang
     * @date_time: 2020/4/23 11:35
     * @return array
     */
    public function login_role($xtype='') {
        /**
         * @var array|object 登录的角色 注意： 数组中登录角色键所处的先后顺序影响 前端展示顺序
         *
         */
        $roleArr = [
            ['type' => self::PROPERTY_ADMIN_LOGIN, 'name' => '物业总管理员'],
            ['type' => self::PROPERTY_USER_LOGIN, 'name' => '物业普通管理员'],
            ['type' => self::VILLAGE_ADMIN_LOGIN, 'name' => '小区物业管理员'],
            ['type' => self::STREET_COMMUNITY_ADMIN_LOGIN, 'name' => '街道/社区管理员'],
            ['type' => self::STREET_COMMUNITY_USER_LOGIN, 'name' => '街道/社区工作人员'],
//            ['type' => self::STREET_ADMIN_LOGIN, 'name' => '街道管理员'],
//            ['type' => self::COMMUNITY_ADMIN_LOGIN, 'name' => '社区管理员'],
//            ['type' => self::STREET_USER_LOGIN, 'name' => '街道工作人员'],
//            ['type' => self::COMMUNITY_USER_LOGIN, 'name' => '社区工作人员'],
        ];
        /**
         * @var array|object 登录的角色对应存储的token
         */
        $tokenArr = [
            self::PROPERTY_ADMIN_LOGIN            => 'property_access_token',
            self::PROPERTY_USER_LOGIN             => 'property_access_token',
            self::VILLAGE_ADMIN_LOGIN             => 'village_access_token',
            self::STREET_COMMUNITY_ADMIN_LOGIN    => 'community_access_token',
            self::STREET_COMMUNITY_USER_LOGIN     => 'community_access_token',
            self::STREET_ADMIN_LOGIN              => 'community_access_token',
            self::COMMUNITY_ADMIN_LOGIN           => 'community_access_token',
            self::STREET_USER_LOGIN               => 'community_access_token',
            self::COMMUNITY_USER_LOGIN            => 'community_access_token',
        ];
        /**
         * @var array|object 登录的角色对应存储的token
         */
        $loginUrlArr = [
            self::PROPERTY_ADMIN_LOGIN            => '/property/property.iframe/property_index',
            self::PROPERTY_USER_LOGIN             => '/property/property.iframe/property_index',
            self::VILLAGE_ADMIN_LOGIN             => '/village/village.iframe/house_index_index',
            self::STREET_COMMUNITY_ADMIN_LOGIN    => '/community/street_community.iframe/street_index',
            self::STREET_COMMUNITY_USER_LOGIN     => '/community/street_community.iframe/street_index',
            self::STREET_ADMIN_LOGIN              => '/community/street_community.iframe/street_index',
            self::COMMUNITY_ADMIN_LOGIN           => '/community/street_community.iframe/street_index',
            self::STREET_USER_LOGIN               => '/community/street_community.iframe/street_index',
            self::COMMUNITY_USER_LOGIN            => '/community/street_community.iframe/street_index',
        ];
        if($xtype=='sentryWatch'){
            //岗亭
            $roleArr = [
                ['type' => self::PROPERTY_ADMIN_LOGIN, 'name' => '物业总管理员'],
                ['type' => self::PROPERTY_USER_LOGIN, 'name' => '物业普通管理员'],
                ['type' => self::VILLAGE_ADMIN_LOGIN, 'name' => '小区物业管理员'],
            ];
        }
        return ['login_role' => $roleArr,'login_token' => $tokenArr,'login_url' => $loginUrlArr];
    }
     public function update_house_sms_record($sms_record_id=0,$updateArr=array()) {
        if($sms_record_id<1){
            return false;
        }
        $houseSmsRecordService= new  \app\community\model\service\HouseSmsRecordService();
        $ret=$houseSmsRecordService->saveSmsRecord(array('id'=>$sms_record_id),$updateArr);
        return $ret;
     }
    /**
     * 登录接口
     * @author: wanziyang
     * @date_time: 2020/5/21 14:32
     * @param array $data
     * @return \json
     * @throws \think\Exception
     */
    public function login($data) {

		if (isset($data['account']) && $data['account']){
			$data['account'] = trim($data['account']);
		}
	    if (isset($data['pwd']) && $data['pwd']){
		    $data['pwd'] = trim($data['pwd']);
	    }
        $xtype='';
        if(isset($data['xtype']) && $data['xtype']){
            $xtype=trim($data['xtype']);
        }
        $nowTime = isset($_SERVER['REQUEST_TIME']) && $_SERVER['REQUEST_TIME'] ? $_SERVER['REQUEST_TIME'] : time();
        $login_role_id=0;
        $data_id_table='';
        $sms_record_id=0;
        if(isset($data['login_type']) && $data['login_type']=='phone_vcode' && isset($data['phone_code']) && $data['phone_code'] && isset($data['user_phone']) ){
             //短信验证码登录
            $houseSmsRecordService= new  \app\community\model\service\HouseSmsRecordService();
            $send_time_tmp=time()-1200;
            $whereArr=array(['phone','=',$data['user_phone']],['send_type','=',1],['login_role','=',$data['login_role']],['vcode','=',$data['phone_code']],['use_time','<',10],['send_time','>',$send_time_tmp]);
            $smsRecordInfo=$houseSmsRecordService->getOneSmsRecord($whereArr,'*','id desc');
            if(empty($smsRecordInfo)){
                throw new \think\Exception("短信验证码不存在！");
            }
            $expire_time_tmp=time()-300;
            if($smsRecordInfo['expire_time']<$expire_time_tmp){
                throw new \think\Exception("此短信验证码已过期！");
            }
            $extra_data_arr=$smsRecordInfo['extra_data'] ? json_decode($smsRecordInfo['extra_data'],true) :'';
            if($extra_data_arr && isset($extra_data_arr['data_id']) && $extra_data_arr['data_id']){
                $login_role_id=$extra_data_arr['data_id'];  //发短信验证码时 通过手机号定位到的表数据的id
                $data_id_table=$extra_data_arr['data_id_table'];
            }
            if($login_role_id<1){
                throw new \think\Exception("登录失败，请重试！");
            }
            $sms_record_id=$smsRecordInfo['id'];
        }
        switch($data['login_role']){
            case self::STREET_ADMIN_LOGIN:
            case self::STREET_USER_LOGIN:
            case self::COMMUNITY_ADMIN_LOGIN:
            case self::COMMUNITY_USER_LOGIN:
            case self::STREET_COMMUNITY_ADMIN_LOGIN:
                // 街道/社区
                // 用户信息
                $service_area_street = new AreaStreetService();
                if($login_role_id>0){
                    $house_area_street = $service_area_street->getAreaStreet(['area_id'=>$login_role_id,'phone' => $data['user_phone']], '*');
                }else{
                    if(empty($data['account'])){
                        throw new \think\Exception("账号不能为空，请输入！");
                    }
                    if(empty($data['pwd'])){
                        throw new \think\Exception("密码不能为空，请输入！");
                    }
                    $house_area_street = $service_area_street->getAreaStreet(['account'=>$data['account']],'*');
                }
                if($house_area_street){
                    if($house_area_street['is_open'] == 0){
                        throw new \think\Exception("您被禁止登录！请联系工作人员获得详细帮助！");
                    }
                    if($login_role_id<1){
                        $pwd = md5($data['pwd']);
                        if($pwd != $house_area_street['pwd']){
                            throw new \think\Exception("账号或密码错误！");
                        }
                    }
                    $log['area_street_id'] = $house_area_street['area_id'];
                    $log['add_time'] = $nowTime;
                    $ip = Request::ip();
                    if ($ip) {
                        $log['ip'] = $ip;
                    }
                    $extend_info=array('xtype'=>'street','account'=>$data['account']);
                    $log['extend_info']= json_encode($extend_info,JSON_UNESCAPED_UNICODE);
                    $service_area_street->addLoginLog($log);
                    // 生成ticket
                    $ticket = Token::createToken($house_area_street['area_id'],$data['login_role']);
                    $data['ticket'] = $ticket;
                    $data['v20_path'] = "/community/street_community.iframe/street_index";
                    $this->update_house_sms_record($sms_record_id,array('use_time'=>time()));
                    return $data;
                } else {
                    throw new \think\Exception("账号不存在！");
                }
                break;
            case self::STREET_COMMUNITY_USER_LOGIN:
                // 街道/社区
                // 用户信息
                $service_area_street = new AreaStreetService();
                $organizationStreetService=new OrganizationStreetService();
                if($login_role_id>0){
                    $whereArr = array('worker_id'=>$login_role_id,'work_phone' => $data['user_phone'], 'area_type' => 0);
                    $street_workers=$organizationStreetService->getMemberDetail($whereArr,'*');
                }else{
                    if(empty($data['account'])){
                        throw new \think\Exception("账号不能为空，请输入！");
                    }
                    if(empty($data['pwd'])){
                        throw new \think\Exception("密码不能为空，请输入！");
                    }
                    $whereArr=array('work_account'=>$data['account'],'area_type'=>0);
                    $street_workers=$organizationStreetService->getMemberDetail($whereArr,'*'); 
                }
                if(!empty($street_workers)){
                    if($street_workers['work_status']!=1){
                        throw new \think\Exception("账号状态存在异常！");
                    }
                    if($login_role_id<1) {
                        $pwd = md5($data['pwd']);
                        if ($street_workers['work_passwd'] != $pwd) {
                            throw new \think\Exception("账号或密码错误！");
                        }
                    }
                    $log['area_street_id'] = $street_workers['area_id'];
                    $log['add_time'] = $nowTime;
                    $ip = Request::ip();
                    if ($ip) {
                        $log['ip'] = $ip;
                    }
                    $extend_info=array('xtype'=>'street_worker','account'=>$data['account'],'worker_id'=>$street_workers['worker_id']);
                    $log['extend_info']=json_encode($extend_info,JSON_UNESCAPED_UNICODE);
                    $service_area_street->addLoginLog($log);
                    // 生成ticket
                    $iddstr=$street_workers['area_id'].'_'.$street_workers['worker_id'];
                    $ticket = Token::createToken($iddstr,$data['login_role']);
                    $data['ticket'] = $ticket;
                    $data['v20_path'] = "/community/street_community.iframe/street_index";
                    $this->update_house_sms_record($sms_record_id,array('use_time'=>time()));
                    return $data;
                }else{
                    throw new \think\Exception("账号不存在！");
                }
                break;
            case '1':
            case '9':
            case '11':
                // 街道/社区
                // 用户信息
                $service_area_street = new AreaStreetService();
                $organizationStreetService=new OrganizationStreetService();
                $house_area_street=array();
                $street_workers=array();
                if($login_role_id>0 && $data_id_table=='area_street'){
                    $house_area_street = $service_area_street->getAreaStreet(['area_id'=>$login_role_id,'phone' => $data['user_phone']], '*');
                }else if($login_role_id>0 && $data_id_table=='area_street_workers'){
                    $house_area_street=array();
                }else{
                    if(empty($data['account'])){
                        throw new \think\Exception("账号不能为空，请输入！");
                    }
                    if(empty($data['pwd'])){
                        throw new \think\Exception("密码不能为空，请输入！");
                    }
                    $house_area_street = $service_area_street->getAreaStreet(['account'=>$data['account']]);
                }
                if($house_area_street){
                    if($house_area_street['is_open'] == 0){
						$errormsg = "您被禁止登录！请联系工作人员获得详细帮助！";
                        $logData = [];
	                    $logData['account']     = $data['account'];
	                    $logData['login_id']    = $house_area_street['area_id'];
	                    $logData['login_type']  = $data['login_role'];
	                    $logData['login_client']= '街道/社区';
	                    $logData['login_status']= 0;
	                    $logData['reson']       = $errormsg;
	                    $this->laterUserLoginLogQueue($logData);
                        throw new \think\Exception($errormsg);
                    }
                    if($login_role_id<1) {
                        $pwd = md5($data['pwd']);
                        if ($pwd != $house_area_street['pwd']) {
                            $errormsg = "账号或密码错误！";
                            $logData = [];
                            $logData['account'] = $data['account'];
                            $logData['login_id'] = $house_area_street['area_id'];
                            $logData['login_type'] = $data['login_role'];
                            $logData['login_client'] = '街道/社区';
                            $logData['login_status'] = 0;
                            $logData['reson'] = $errormsg;
                            $this->laterUserLoginLogQueue($logData);
                            throw new \think\Exception($errormsg);
                        }
                    }
                    $log['area_street_id'] = $house_area_street['area_id'];
                    $log['add_time'] = $nowTime;
                    $ip = Request::ip();
                    if ($ip) {
                        $log['ip'] = $ip;
                    }
                    $extend_info=array('xtype'=>'street','account'=>$data['account']);
                    $log['extend_info']=json_encode($extend_info,JSON_UNESCAPED_UNICODE);
                    $service_area_street->addLoginLog($log);

                    $logData = [];
					$logData['account']     = $data['account'];
					$logData['login_id']    = $house_area_street['area_id'];
					$logData['login_type']  = $data['login_role'];
					$logData['login_client']= '街道/社区-管理人员';
					$this->laterUserLoginLogQueue($logData);
                    // 生成ticket
                    $ticket = Token::createToken($house_area_street['area_id'],$data['login_role']);
                    $data['ticket'] = $ticket;
                    $data['v20_path'] = "/community/street_community.iframe/street_index";
                    $this->update_house_sms_record($sms_record_id,array('use_time'=>time()));
                    return $data;
                }else{
                    if($login_role_id>0 && $data_id_table=='area_street_workers'){
                        $whereArr = array('worker_id'=>$login_role_id,'work_phone' => $data['user_phone'], 'area_type' => 0);
                        $street_workers=$organizationStreetService->getMemberDetail($whereArr,'*');
                    }else{
                        $whereArr=array('work_account'=>$data['account'],'area_type'=>0);
                        $street_workers=$organizationStreetService->getMemberDetail($whereArr);
                    }
                    if(!empty($street_workers)){
                        if($street_workers['work_status']!=1){
                            throw new \think\Exception("账号状态存在异常！");
                        }
                        if($login_role_id<1) {
                            $pwd = md5($data['pwd']);
                            if ($street_workers['work_passwd'] != $pwd) {
                                throw new \think\Exception("账号或密码错误！");
                            }
                        }
                        $log['area_street_id'] = $street_workers['area_id'];
                        $log['add_time'] = $nowTime;
                        $ip = Request::ip();
                        if ($ip) {
                            $log['ip'] = $ip;
                        }
                        $extend_info=array('xtype'=>'street_worker','account'=>$data['account'],'worker_id'=>$street_workers['worker_id']);
                        $log['extend_info']=json_encode($extend_info,JSON_UNESCAPED_UNICODE);
                        $service_area_street->addLoginLog($log);
                        // 生成ticket
                        $iddstr=$street_workers['area_id'].'_'.$street_workers['worker_id'];
                        $ticket = Token::createToken($iddstr,$data['login_role']);
                        $data['ticket'] = $ticket;
                        $data['v20_path'] = "/community/street_community.iframe/street_index";

                        $logData = [];
	                    $logData['account']     = $data['account'];
	                    $logData['login_id']    = $street_workers['area_id'];
	                    $logData['login_type']  = $data['login_role'];
	                    $logData['realname']  = $street_workers['work_name'];
	                    $logData['login_client']= '街道/社区-工作人员';
	                    $this->laterUserLoginLogQueue($logData);
                        $this->update_house_sms_record($sms_record_id,array('use_time'=>time()));
                        return $data;
                    }else{
                        throw new \think\Exception("账号不存在！请联系工作人员添加！");
                    }
                }
                break;
            case self::PROPERTY_ADMIN_TO_VILLAGE:
            case self::PROPERTY_ADMIN_LOGIN:
            case '3':
                // 物业总管理员
                // 用户信息
                $serviceHouseProperty = new HousePropertyService();
                if ($login_role_id > 0) {
                    $infoHouseProperty = $serviceHouseProperty->getFind(['id' => $login_role_id, 'property_phone' => $data['user_phone']], '*');
                } else {
                    $infoHouseProperty = $serviceHouseProperty->getFind(['account' => $data['account']], '*');
                }
                if (!isset($infoHouseProperty['id'])) {
                    throw new \think\Exception("账号或密码错误");
                } else {
                    $infoHouseProperty['login_identify'] = 1;
                    if ($infoHouseProperty['status'] == 2) {
                        throw new \think\Exception("您被禁止登录！请联系工作人员获得详细帮助！");
                    } elseif ($infoHouseProperty['status'] == 3) {
                        throw new \think\Exception("您的账号正在审核中！请联系工作人员获得详细帮助！");
                    } elseif ($infoHouseProperty['status'] == 4) {
                        throw new \think\Exception("您的账号审核未通过！请联系工作人员获得详细帮助！");
                    }
                    if($login_role_id<1) {
                        $pwd = md5($data['pwd']);
                        if ($pwd != $infoHouseProperty['password']) {
                            throw new \think\Exception("账号或密码错误！");
                        }
                    }
                    //判断套餐是否过期 start  2020/8/18
                    $servicePackageOrder = new PackageOrderService();
                    $returnInfo = $servicePackageOrder->judgeOrderEmploy($infoHouseProperty['id']);
                    if ($returnInfo['code'] == 2) {
                        $data['is_err'] = 1;
                        $data['err_msg'] = $returnInfo['msg'];
                    } elseif ($returnInfo['code'] == 3) {
                        $data['is_err'] = 1;
                        $data['err_msg'] = $returnInfo['msg'];
                    } else {
                        $data['is_err'] = 0;
                        $data['err_msg'] = '';
                    }
                    //判断套餐是否过期 end 2020/8/18
                    $dataProperty = [];
                    $dataProperty['last_time'] = $nowTime;
                    $serviceHouseProperty->editData(['id' => $infoHouseProperty['id']], $dataProperty);
                    //生成token
                    $ticket = Token::createToken($infoHouseProperty['id'], $data['login_role']);
                    $data['ticket'] = $ticket;
                    $where_property_guide = [];
                    $where_property_guide[] = ['property_id', '=', $infoHouseProperty['id']];
                    $db_house_property_guide = new HousePropertyGuide();
                    $property_guide = $db_house_property_guide->getOne($where_property_guide);
                    if ($property_guide && !is_array($property_guide)) {
                        $property_guide = $property_guide->toArray();
                    }
                    if (empty($property_guide) || !isset($property_guide['complete_config']) || $property_guide['complete_config'] != 1) {
                        // 没有引导页内容或者引导未完成 均跳转
                        $jump_url = cfg('site_url') . '/v20/public/platform/#/property/property/Step';
                        $jump_path = "/property/property/Step";
                        $data['jump_url'] = $jump_url;
                        $data['jump_path'] = $jump_path;
                    }
                    if (isset($data['is_err']) && $data['is_err']) {
                        $data['v20_path'] = "/property/property/package/LoginPackagesBuy";
                    } else {
                        $data['v20_path'] = "/property/property.iframe/property_index";
                    }
                    if ($xtype == 'sentryWatch') {
                        $data['v20_path'] = "/community/platform.WatchBox/WatchBox";
                    }
                    $logData = [];
                    $logData['account'] = $data['account'];
                    $logData['login_id'] = $infoHouseProperty['id'];
                    $logData['login_type'] = $data['login_role'];
                    $logData['realname'] = $infoHouseProperty['property_name'];
                    $logData['login_client'] = '物业总管理员';
                    $this->laterUserLoginLogQueue($logData);
                    $this->update_house_sms_record($sms_record_id,array('use_time'=>time()));
                    return $data;
                }
                break;
            case self::PROPERTY_USER_LOGIN:
            case self::PROPERTY_USER_TO_VILLAGE:
            case '4':
                // 物业普通管理员
                // 用户信息
                $servicePropertyAdmin = new PropertyAdminService();
                $serviceHouseProperty = new HousePropertyService();
                if ($login_role_id > 0) {
                    $infoPropertyAdmin = $servicePropertyAdmin->getFind(['id' => $login_role_id, 'phone' => $data['user_phone']], '*');
                } else {
                    $infoPropertyAdmin = $servicePropertyAdmin->getFind(['account' => $data['account']]);
                }
                if (isset($infoPropertyAdmin['property_id'])) {
                    if($login_role_id<1) {
                        $pwd = md5($data['pwd']);
                        if ($pwd != $infoPropertyAdmin['pwd']) {
                            throw new \think\Exception("账号或密码错误");
                        }
                    }
                    //判断套餐是否过期 start  2020/8/18
                    $servicePackageOrder = new PackageOrderService();
                    $returnInfo = $servicePackageOrder->judgeOrderEmploy($infoPropertyAdmin['property_id']);
                    if ($returnInfo['code'] == 2) {
                        $data['is_err'] = 1;
                        $data['err_msg'] = $returnInfo['msg'];
                    } elseif ($returnInfo['code'] == 3) {
                        $data['is_err'] = 1;
                        $data['err_msg'] = $returnInfo['msg'];
                    } else {
                        $data['is_err'] = 0;
                        $data['err_msg'] = '';
                    }
                    //判断套餐是否过期 end 2020/8/18

                    $dataHouse = [];
                    $where[] = ['id', '=', $infoPropertyAdmin['id']];
                    $dataHouse['login_count'] = $infoPropertyAdmin['login_count'] + 1;
                    $ip = Request::ip();
                    if ($ip) {
                        $dataHouse['last_ip'] = $ip;
                    }
                    $dataHouse['last_time'] = $nowTime;
                    //登录后存相关数据
                    $result = $servicePropertyAdmin->editData($where, $dataHouse);
                    if ($result) {
                        //权限
                        $houseProperty = $serviceHouseProperty->getFind(['id' => $infoPropertyAdmin['property_id']]);

                        $houseProperty['login_identify'] = 2;
                        //生成token
                        $ticket = Token::createToken($infoPropertyAdmin['id'], $data['login_role']);
                        $data['ticket'] = $ticket;
                        $where_property_guide = [];
                        $where_property_guide[] = ['property_id', '=', $infoPropertyAdmin['property_id']];
                        $db_house_property_guide = new HousePropertyGuide();
                        $property_guide = $db_house_property_guide->getOne($where_property_guide);
                        if ($property_guide && !is_array($property_guide)) {
                            $property_guide = $property_guide->toArray();
                        }
                        if (empty($property_guide) || !isset($property_guide['complete_config']) || $property_guide['complete_config'] != 1) {
                            // 没有引导页内容或者引导未完成 均跳转
                            $jump_url = cfg('site_url') . '/v20/public/platform/#/property/property/Step';
                            $jump_path = "/property/property/Step";
                            $data['jump_url'] = $jump_url;
                            $data['jump_path'] = $jump_path;
                        }
                        if (isset($data['is_err']) && $data['is_err']) {
                            $data['v20_path'] = "/property/property/package/LoginPackagesBuy";
                        } else {
                            $data['v20_path'] = "/property/property.iframe/property_index";
                        }
                        if ($xtype == 'sentryWatch') {
                            $data['v20_path'] = "/community/platform.WatchBox/WatchBox";
                        }
                        $logData = [];
                        $logData['account'] = $data['account'];
                        $logData['login_id'] = $infoPropertyAdmin['property_id'];
                        $logData['login_type'] = $data['login_role'];
                        $logData['realname'] = $infoPropertyAdmin['realname'];
                        $logData['login_client'] = '物业普通管理员';
                        $this->laterUserLoginLogQueue($logData);
                        $this->update_house_sms_record($sms_record_id,array('use_time'=>time()));
                        return $data;
                    } else {
                        throw new \think\Exception("登录信息保存失败,请重试！");
                    }

                }else{
                    throw new \think\Exception("账号不存在");
                }
                //throw new \think\Exception("暂不支持该身份登录");
                break;
            case self::VILLAGE_ADMIN_LOGIN:
            case '5':
                // 小区工作人员
                // 用户信息
                $serviceHouseVillage = new HouseVillageService();
                $serviceHouseAdmin = new HouseAdminService();
                $serviceHouseProperty = new HousePropertyService();
                if ($login_role_id > 0) {
                    $whereLoginArr = array('id' => $login_role_id, 'phone' => $data['user_phone']);
                } else {
                    $whereLoginArr = array('account' => $data['account']);
                }
                if (isset($data['village_id']) && $data['village_id'] > 0) {
                    $whereLoginArr['village_id'] = $data['village_id'];
                }
                $infoHouseAdmin = $serviceHouseAdmin->getFind($whereLoginArr, '*', 'status ASC,last_time desc');
                if (!$infoHouseAdmin) {
                    throw new \think\Exception("账号或密码错误");
                }
                if ($infoHouseAdmin['status'] != 1) {
                    throw new \think\Exception("用户已禁止！");
                }
                if ($login_role_id < 1) {
                    $pwd = md5($data['pwd']);
                    if ($pwd != $infoHouseAdmin['pwd']) {
                        throw new \think\Exception("账号或密码错误！");
                    }
                }
                $nowHouse = $serviceHouseVillage->getHouseVillage($infoHouseAdmin['village_id']);
                if ($nowHouse['status'] == 2 || $nowHouse['status'] == 5) {
                    if ($login_role_id >0) {
                        throw new \think\Exception("小区已被禁止,请联系工作人员！");
                    }
                    $whereArr = array(['a.account', '=', $data['account']]);
                    $whereArr[] = array('a.status', '=', 1);
                    $whereArr[] = array('hv.status', '=', 1);
                    $whereArr[] = array('a.pwd', '=', $pwd);
                    $oneAdmin = $serviceHouseAdmin->get_one_normal_house_admin($whereArr, 'a.*');
                    if (!empty($oneAdmin) && isset($oneAdmin['village_id'])) {
                        $infoHouseAdmin = $oneAdmin;
                        $nowHouse = $serviceHouseVillage->getHouseVillage($oneAdmin['village_id']);
                    }
                    if (empty($oneAdmin) || !isset($oneAdmin['village_id']) || $nowHouse['status'] == 2 || $nowHouse['status'] == 5) {
                        throw new \think\Exception("该小区已被禁止,请联系工作人员！");
                    }
                }
                if ($xtype == 'sentryWatch') {
                    if (!$infoHouseAdmin['wid'] || $infoHouseAdmin['wid'] < 1) {
                        throw new \think\Exception("账号没有管理岗亭的权限！");
                    }
                    $whereArr = array('wid' => $infoHouseAdmin['wid'], 'village_id' => $infoHouseAdmin['village_id']);
                    $houseWorkerObj = $serviceHouseAdmin->getHouseWorker($whereArr, '*', 'is_sentry desc');
                    if ($houseWorkerObj && !$houseWorkerObj->isEmpty()) {
                        if ($houseWorkerObj['is_del'] == 1 || $houseWorkerObj['status'] == 4) {
                            throw new \think\Exception("此账号不存在！");
                        }
                        if ($houseWorkerObj['status'] == 0) {
                            throw new \think\Exception("此账号已禁止！");
                        }
                        if ($houseWorkerObj['is_sentry'] < 1) {
                            throw new \think\Exception("此账号没有管理岗亭的权限！");
                        }
                    } else {
                        throw new \think\Exception("账号或者密码错误!");
                    }
                }
                //判断套餐是否过期 start  2020/8/18
                $servicePackageOrder = new PackageOrderService();
                $returnInfo = $servicePackageOrder->judgeOrderEmploy($nowHouse['property_id']);
                if ($returnInfo['code'] == 2) {
                    $data['is_err'] = 1;
                    $data['err_msg'] = $returnInfo['msg'];
                } elseif ($returnInfo['code'] == 3) {
                    $data['is_err'] = 1;
                    $data['err_msg'] = $returnInfo['msg'];
                } else {
                    $data['is_err'] = 0;
                    $data['err_msg'] = '';
                }
                $infoHouseAdmin['property_id'] = $nowHouse['property_id'];
                //判断套餐是否过期 end 2020/8/18
                $dataHouse = [];
                $where[] = ['id', '=', $infoHouseAdmin['id']];
                $dataHouse['login_count'] = $infoHouseAdmin['login_count'] + 1;
                $ip = Request::ip();
                if ($ip) {
                    $dataHouse['last_ip'] = $ip;
                }
                $dataHouse['last_time'] = $nowTime;
                if (isset($infoHouseAdmin['menus']) && $infoHouseAdmin['menus']) {
                    $nowHouse['menus'] = explode(',', strval($infoHouseAdmin['menus']));
                }
                $nowHouse['user_name'] = $infoHouseAdmin['realname'] ? $infoHouseAdmin['realname'] : $infoHouseAdmin['account'];
                $nowHouse['role_id'] = $infoHouseAdmin['id'];

                //判断社区是否到期
//                    if (isset($nowHouse['expiration_time']) && $nowHouse['expiration_time'] && $nowHouse['expiration_time']<time()) {
//                        throw new \think\Exception("该小区已到期，请联系管理员！");
//                    }
                if ($serviceHouseAdmin->editData($where, $dataHouse)) {
                    $nowHouse['login_village'] = 3;
                    $ticket = Token::createToken($infoHouseAdmin['id'], $data['login_role']);
                    $data['ticket'] = $ticket;
                    $systemMenuService = new VillageMenuService();
                    $systemMenu = $systemMenuService->formartMenuList([], $infoHouseAdmin);
                    if (!empty($systemMenu)) {
                        $pid = 0;
                        foreach ($systemMenu as $menu) {
                            if (isset($menu['path']) && $menu['path'] && isset($menu['parentId']) && 0 == $menu['parentId'] && isset($menu['meta']) && isset($menu['meta']['title']) && '首页' == $menu['meta']['title']) {
                                $data['v20_path'] = $menu['path'];
                                break;
                            } elseif (isset($menu['component']) && 'RouteView' == $menu['component']) {
                                if (!$pid) {
                                    $pid = $menu['id'];
                                } elseif (isset($menu['parentId']) && $menu['parentId'] && $pid && $menu['parentId'] == $pid) {
                                    $pid = $menu['id'];
                                }
                                continue;
                            } elseif (isset($menu['parentId']) && $menu['parentId'] && $menu['parentId'] == $pid && isset($menu['path']) && $menu['path']) {
                                $data['v20_path'] = $menu['path'];
                                break;
                            }
                        }
                    } else {
                        throw new \think\Exception("当前登录身份没有任何目录查看权限！");
                    }
                    if ($xtype == 'sentryWatch') {
                        $data['v20_path'] = "/community/platform.WatchBox/WatchBox";
                    }
                    $logData = [];
                    $logData['account'] = $data['account'];
                    $logData['login_id'] = $infoHouseAdmin['village_id'];
                    $logData['login_type'] = $data['login_role'];
                    $logData['realname'] = $nowHouse['user_name'];
                    $logData['login_client'] = '小区工作人员';
                    $this->laterUserLoginLogQueue($logData);
                    $this->update_house_sms_record($sms_record_id,array('use_time'=>time()));
                    return $data;
                }else{
                    throw new \think\Exception("登录信息保存失败,请重试！");
                }
                break;
            default:
                throw new \think\Exception("请选择正确身份登录");
                break;
        }
    }
    /***
    ** 检查好等等角色手机号
     ***/
    public function checkLoginRolePhone($login_role = 0, $xphone = '', $pdata = array())
    {
        if (empty($login_role)) {
            throw new \think\Exception("登录身份错误！");
        }
        if (empty($xphone)) {
            throw new \think\Exception("手机号不能为空！");
        }
        $returnArr = array('login_role' => $login_role, 'xphone' => $xphone, 'data_id' => 0,'data_id_table'=>'');
        switch ($login_role) {
            case self::STREET_ADMIN_LOGIN:
            case self::STREET_USER_LOGIN:
            case self::COMMUNITY_ADMIN_LOGIN:
            case self::COMMUNITY_USER_LOGIN:
            case self::STREET_COMMUNITY_ADMIN_LOGIN:
                // 街道/社区
                // 用户信息
                $service_area_street = new AreaStreetService();
                $house_area_street = $service_area_street->getAreaStreet(['phone' => $xphone], '*');
                if (!empty($house_area_street) && isset($house_area_street['area_id'])) {
                    if ($house_area_street['is_open'] == 0) {
                        throw new \think\Exception("您被禁止登录！请联系工作人员获得详细帮助！");
                    }
                    $returnArr['data_id'] = $house_area_street['area_id'];
                    $returnArr['data_id_table']='area_street';
                    return $returnArr;
                } else {
                    throw new \think\Exception("手机号不存在！");
                }
                break;
            case self::STREET_COMMUNITY_USER_LOGIN:
                // 街道/社区
                // 用户信息
                $service_area_street = new AreaStreetService();
                $organizationStreetService = new OrganizationStreetService();
                $whereArr = array('work_phone' => $xphone, 'area_type' => 0);
                $street_workers = $organizationStreetService->getMemberDetail($whereArr, '*');
                if (!empty($street_workers)) {
                    if ($street_workers['work_status'] != 1) {
                        throw new \think\Exception("账号状态存在异常！");
                    }
                    $returnArr['data_id'] = $street_workers['worker_id'];
                    $returnArr['data_id_table']='area_street_workers';
                    return $returnArr;
                } else {
                    throw new \think\Exception("手机号不存在！");
                }
                break;
            case '1':
            case '9':
            case '11':
                // 街道/社区
                // 用户信息
                $service_area_street = new AreaStreetService();
                $house_area_street = $service_area_street->getAreaStreet(['phone' => $xphone], '*');
                if ($house_area_street) {
                    if ($house_area_street['is_open'] == 0) {
                        throw new \think\Exception("您被禁止登录！请联系工作人员获得详细帮助！");
                    }
                    $returnArr['data_id'] = $house_area_street['area_id'];
                    $returnArr['data_id_table']='area_street';
                    return $returnArr;
                } else {
                    $organizationStreetService = new OrganizationStreetService();
                    $whereArr = array('work_phone' => $xphone, 'area_type' => 0);
                    $street_workers = $organizationStreetService->getMemberDetail($whereArr, '*');
                    if (!empty($street_workers)) {
                        if ($street_workers['work_status'] != 1) {
                            throw new \think\Exception("账号状态存在异常！");
                        }
                        $returnArr['data_id'] = $street_workers['worker_id'];
                        $returnArr['data_id_table']='area_street_workers';
                        return $returnArr;
                    } else {
                        throw new \think\Exception("手机号不存在！");
                    }
                }
                break;
            case self::PROPERTY_ADMIN_TO_VILLAGE:
            case self::PROPERTY_ADMIN_LOGIN:
            case '3':
                // 物业总管理员
                // 用户信息
                $serviceHouseProperty = new HousePropertyService();
                $infoHouseProperty = $serviceHouseProperty->getFind(['property_phone' => $xphone], '*');

                if (empty($infoHouseProperty) || !isset($infoHouseProperty['id'])) {
                    throw new \think\Exception("手机号不存在");
                } else {
                    if ($infoHouseProperty['status'] == 2) {
                        throw new \think\Exception("您被禁止登录！请联系工作人员获得详细帮助！");
                    } elseif ($infoHouseProperty['status'] == 3) {
                        throw new \think\Exception("您的账号正在审核中！请联系工作人员获得详细帮助！");
                    } elseif ($infoHouseProperty['status'] == 4) {
                        throw new \think\Exception("您的账号审核未通过！请联系工作人员获得详细帮助！");
                    }
                    $returnArr['data_id'] = $infoHouseProperty['id'];
                    $returnArr['data_id_table']='house_property';
                    return $returnArr;
                }
                break;
            case self::PROPERTY_USER_LOGIN:
            case self::PROPERTY_USER_TO_VILLAGE:
            case '4':
                // 物业普通管理员
                // 用户信息
                $servicePropertyAdmin = new PropertyAdminService();
                $serviceHouseProperty = new HousePropertyService();
                $infoPropertyAdmin = $servicePropertyAdmin->getFind(['phone' => $xphone], '*');
                if (!empty($infoPropertyAdmin) && isset($infoPropertyAdmin['property_id'])) {
                    if ($infoPropertyAdmin['status'] == 2) {
                        throw new \think\Exception("您被禁止登录！请联系工作人员获得详细帮助！");
                    }
                    $returnArr['data_id'] = $infoPropertyAdmin['id'];
                    $returnArr['data_id_table']='property_admin';
                    return $returnArr;
                } else {
                    throw new \think\Exception("手机号不存在");
                }
                break;
            case self::VILLAGE_ADMIN_LOGIN:
            case '5':
                // 小区工作人员
                // 用户信息
                $serviceHouseAdmin = new HouseAdminService();
                $whereLoginArr = array('phone' => $xphone);
                if (isset($pdata['village_id']) && $pdata['village_id'] > 0) {
                    $whereLoginArr['village_id'] = $pdata['village_id'];
                }
                $infoHouseAdmin = $serviceHouseAdmin->getFind($whereLoginArr, '*', 'status ASC,last_time desc');
                if (empty($infoHouseAdmin) || !isset($infoHouseAdmin['id'])) {
                    throw new \think\Exception("手机号不存在");
                }
                if ($infoHouseAdmin['status'] != 1) {
                    throw new \think\Exception("用户已禁止登录！");
                }
                $serviceHouseVillage = new HouseVillageService();
                $nowHouse = $serviceHouseVillage->getHouseVillage($infoHouseAdmin['village_id']);
                if ($nowHouse['status'] == 2 || $nowHouse['status'] == 5) {
                    $whereArr = array(['a.phone', '=', $xphone]);
                    $whereArr[] = array('a.status', '=', 1);
                    $whereArr[] = array('hv.status', '=', 1);
                    $oneAdmin = $serviceHouseAdmin->get_one_normal_house_admin($whereArr, 'a.*');
                    if (!empty($oneAdmin) && isset($oneAdmin['village_id'])) {
                        $infoHouseAdmin = $oneAdmin;
                        $nowHouse = $serviceHouseVillage->getHouseVillage($oneAdmin['village_id']);
                    }
                    if (empty($oneAdmin) || !isset($oneAdmin['village_id']) || $nowHouse['status'] == 2 || $nowHouse['status'] == 5) {
                        throw new \think\Exception("该小区已被禁止,请联系工作人员！");
                    }
                }
                $returnArr['data_id'] = $infoHouseAdmin['id'];
                $returnArr['data_id_table'] = 'house_admin';
                return $returnArr;
                break;
            default:
                throw new \think\Exception("请选择正确身份登录");
                break;
        }
    }
    /**
     * 组装数据
     * @author: wanziyang
     * @date_time: 2020/5/21 14:08
     * @param array $adminUser
     * @param integer $login_role
     * @return array
     */
    public function formatUserData($adminUser,$login_role=1) {
        fdump_api([$adminUser,$login_role],'formatUserData');
        if (!$adminUser) {
            return [];
        }
        switch($login_role){
            case self::SYSTEM_ADMIN_TO_VILLAGE:
                $name = $adminUser['user_name'];
                $id = 'systemToHouseAdmin';
                $role_name = '小区超级管理员';
                break;
            case self::SYSTEM_ADMIN_TO_PROPERTY:
                $name = $adminUser['user_name'];
                $id = 'systemToPropertyAdmin';
                $role_name = '物业总管理员';
                break;
            case self::SYSTEM_ADMIN_GO_PROPERTY_TO_VILLAGE:
                $name = $adminUser['user_name'];
                $id = 'systemGoPropertyToVillageAdmin';
                $role_name = '物业总管理员';
                break;
            case self::SYSTEM_ADMIN_TO_AREA_STREET:
            case self::SYSTEM_ADMIN_TO_AREA_COMMUNITY:
                $name = $adminUser['user_name'];
                $id = 'street';
                $role_name = '街道/社区';
                break;
            case self::PROPERTY_ADMIN_TO_VILLAGE:
                $name = $adminUser['user_name'];
                $id = 'propertyAdminToVillageAdmin';
                $role_name = '物业总管理员';
                break;
            case self::PROPERTY_ADMIN_LOGIN:
                $name = $adminUser['user_name'];
                $id = 'propertyAdmin';
                $role_name = '物业总管理员';
                break;
            case self::PROPERTY_USER_LOGIN:
                $name = $adminUser['user_name'];
                $id = 'propertyUser';
                $role_name = '物业管理员';
                break;
            case self::VILLAGE_ADMIN_LOGIN:
                $name = $adminUser['user_name'];
                if(isset($adminUser['village_name'])){
                    $name='【'.$adminUser['village_name'].'】'.$name;
                }
                $id = 'villageAdmin';
                $role_name = '小区工作人员';
                break;
            case self::STREET_COMMUNITY_ADMIN_LOGIN:
                $name = $adminUser['user_name'];
                $id = 'streetCommunityAdmin';
                $role_name = '街道/社区管理员';
                break;
            case self::STREET_COMMUNITY_USER_LOGIN:
                $name = $adminUser['user_name'];
                $id = 'streetCommunityUser';
                $role_name = '街道/社区工作人员';
                break;
            case self::STREET_ADMIN_LOGIN:
                $name = $adminUser['user_name'];
                $id = 'streetAdmin';
                $role_name = '街道管理员';
                break;
            case self::COMMUNITY_ADMIN_LOGIN:
                $name = $adminUser['user_name'];
                $id = 'communityAdmin';
                $role_name = '社区管理员';
                break;
            case self::STREET_USER_LOGIN:
                $name = $adminUser['user_name'];
                $id = 'streetUser';
                $role_name = '街道工作人员';
                break;
            case self::COMMUNITY_USER_LOGIN:
                $name = $adminUser['user_name'];
                $id = 'communityUser';
                $role_name = '社区工作人员';
                break;
            case self::PROPERTY_USER_TO_VILLAGE:
                $name = $adminUser['user_name'];
                $id = 'propertyUserToVillageAdmin';
                $role_name = '物业工作人员';
                break;
            case '1':
            case '9':
            case '11':
                // 获得用户信息
                $name = $adminUser['area_name'];
                $id = 'street';
                $role_name = '街道/社区';
                break;
            case '3':
                // 物业总管理员
                $name = $adminUser['property_name'];
                $id = 'houseProperty';
                $role_name = '物业总管理员';
                break;
            case '4':
                // 物业普通管理员
                $name = $adminUser['realname'] ? $adminUser['realname'] : $adminUser['account'];
                $id = 'propertyAdmin';
                $role_name = '物业普通管理员';
                break;
            case '5':
                // 小区工作人员
                // 用户信息
                $name = $adminUser['realname'] ? $adminUser['realname'] : $adminUser['account'];
                if(isset($adminUser['village_name'])){
                    $name='【'.$adminUser['village_name'].'】'.$name;
                }
                $id = 'houseAdmin';
                $role_name = '小区工作人员';
                break;
            case '6':
                // 小区超级管理员-系统后台直接进入
                $name = $adminUser['village_name'].'【系统进入】';
                $id = 'houseAdmin';
                $role_name = '小区超级管理员';
                break;
            case '7':
                // 小区超级管理员-物业后台直接进入
                $name = $adminUser['village_name'].'【物业进入】';
                $id = 'houseAdmin';
                $role_name = '小区超级管理员';
                break;
            case '12':
                // 小区工作人员-物业普通管理員后台直接进入
                $name = $adminUser['village_name'].'【物业进入】';
                $id = 'houseAdmin';
                $role_name = '小区超级管理员';
                break;
            case '8':
                // 物业总管理员-系统后台直接进入
                $name = $adminUser['property_name'].'【系统进入】';
                $id = 'houseProperty';
                $role_name = '物业总管理员';
                break;
            case '61':
                // 物业总管理员-系统后台直接进入
                $name = $adminUser['property_name'].'【企业微信授权】';
                $id = 'houseProperty';
                $role_name = '物业总管理员';
                break;
            default:
                $user = [];
                break;
        }
        $login_name = $name;
        if(in_array($login_role,$this->loginRoleArr)){
            $login_name='您好，'.$login_name;
        }
        $returnArr = [];
        $returnArr['name'] = $name;
        $returnArr['loginTitle'] = isset($adminUser['loginTitle'])&&$adminUser['loginTitle']?$adminUser['loginTitle']:'';
        $returnArr['login_name'] = $login_name;
        $returnArr['role'] = [];
        $returnArr['role']['id'] = $id;
        $returnArr['role']['login_role'] = $login_role;
        $returnArr['role']['name'] = $role_name;
        $returnArr['role']['permissions'][] = [
            'permissionId' => 'system', // 菜单的权限标识
            // 'actions' => null, // 该菜单下的所有按钮集合,可不传
            'actionEntitySet' => [ // 本用户能看见和操作的按钮
                [
                    'action' => 'query',
                    'describe' => '查询',
                    'defaultCheck' => true
                ],
                [
                    'action' => 'add',
                    'describe' => '新增',
                    'defaultCheck' => false
                ],
                [
                    'action' => 'delete',
                    'describe' => '删除',
                    'defaultCheck' => false
                ],
                [
                    'action' => 'edit',
                    'describe' => '修改',
                    'defaultCheck' => false
                ],
                [
                    'action' => 'enable',
                    'describe' => '是否禁用',
                    'defaultCheck' => false
                ],
            ]
        ];

        return $returnArr;
    }

    public function handleLoginData($userId='', $login_role='', $ticket='') {
       if (!$userId && !$login_role && $ticket) {
           $token = Token::checkToken($ticket);
           $userId = $token['memberId'];
           $login_role = $token['extends'];
       }
       if (!$userId || !$login_role) {
           throw new \think\Exception("登录身份有误");
       }
       switch($login_role){
            case self::SYSTEM_ADMIN_TO_VILLAGE:
                $info = explode('_',$userId);
                $userId = intval($info[0]);
                $adminId = intval($info[1]);
                $systemAdmin = (new AdminUserService())->getNormalUserById($adminId);
                $house_admin = new HouseVillageService();
                $service_house_village = new HouseVillageService();
                $user = $house_admin->getHouseVillage($userId);
                $admin_menus = $service_house_village->getHouseMenuNew(array('status'=>1),'id');
                $power = array();
                foreach ($admin_menus as $value) {
                    $power[] = $value['id'];
                }
                $user['loginTitle'] = isset($user['village_name'])&&$user['village_name']?$user['village_name'].'【小区】':'小区后台';
                if ($systemAdmin && isset($systemAdmin['realname'])) {
                    $user_name = $systemAdmin['realname']?$systemAdmin['realname']:$systemAdmin['account'];
                    $user['adminName'] = $user_name;
                    $user['user_name'] = $user_name .'【系统管理员】';
                    $user['phone'] =$systemAdmin['phone'];
                } else {
                    $user['user_name']='admin-';
                    $user['adminName'] = '';
                    $user['phone'] ='';
                    if(!empty($user) && isset($user['village_name'])){
                        $user['user_name']= 'admin-'.$user['village_name'];
                    }
                }
                $user['adminId']=$adminId;
                $user['loginType']='systemAdmin';
                $user['menus'] = $power;
                unset($user['pwd']);
                // 系统后台登录的小区超级管理员账号
                break;
            case self::SYSTEM_ADMIN_TO_PROPERTY:
                // 物业总管理员
                // 用户信息
                $info = explode('_',$userId);
                $userId = intval($info[0]);
                $adminId = intval($info[1]);
                $systemAdmin = (new AdminUserService())->getNormalUserById($adminId);
                $serviceHouseProperty = new HousePropertyService();
                $user = $serviceHouseProperty->getFind(['id'=>$userId]);
                $user['property_id'] = $userId;
                $user['village_id'] = 0;
                $user['loginTitle'] = isset($user['property_name'])&&$user['property_name']?$user['property_name'].'【物业】':'物业后台';
                if ($systemAdmin && isset($systemAdmin['realname'])) {
                    $user_name = $systemAdmin['realname']?$systemAdmin['realname']:$systemAdmin['account'];
                    $user['adminName'] = $user_name;
                    $user['user_name'] = $user_name .'【系统管理员】';
                } else {
                    $user['user_name']='admin-';
                    $user['adminName'] = '';
                    if(!empty($user) && isset($user['property_name'])){
                        $user['user_name']= 'admin-'.$user['property_name'];
                    }
                }
                $user['adminId']=$adminId;
                $user['loginType']='systemAdmin';
                unset($user['password']);
                // 系统后台登录物业账号
                break;
            case self::SYSTEM_ADMIN_TO_AREA_STREET:
            case self::SYSTEM_ADMIN_TO_AREA_COMMUNITY:
                // 获得用户信息
                // 用户信息
                $info = explode('_',$userId);
                $userId = intval($info[0]);
                $adminId = intval($info[1]);
                $systemAdmin = (new AdminUserService())->getNormalUserById($adminId);
                $service_area_street = new AreaStreetService();
                $user = $service_area_street->getAreaStreet(['area_id'=>$userId]);
                $loginTitle = isset($user['area_name'])&&$user['area_name']?$user['area_name']:'';
                if (!$loginTitle && $user['area_type']==0) {
                    $loginTitle = '街道后台';
                } elseif(!$loginTitle) {
                    $loginTitle = '社区后台';
                } elseif ($user['area_type']==0) {
                    $loginTitle .= '【街道】';
                } elseif ($user['area_type']==1) {
                    $loginTitle .= '【社区】';
                }
                $user['loginTitle'] = $loginTitle;
                if ($systemAdmin && isset($systemAdmin['realname'])) {
                    $user_name = $systemAdmin['realname']?$systemAdmin['realname']:$systemAdmin['account'];
                    $user['adminName'] = $user_name;
                    $user['user_name'] = $user_name .'【系统管理员】';
                } else {
                    $user['user_name']='admin-';
                    $user['adminName'] = '';
                    if(!empty($user) && isset($user['area_name'])){
                        $user['user_name']= 'admin-'.$user['area_name'];
                    }
                }
                $user['property_id'] = 0;
                $user['village_id'] = 0;
                $user['street_worker']=array();
                $user['worker_id']= 0;
                $user['log_uid']=$userId;
                $user['adminId']=$adminId;
                $user['loginType']='systemAdmin';
                unset($user['pwd']);
                break;
            case self::SYSTEM_ADMIN_GO_PROPERTY_TO_VILLAGE:
                $info = explode('_',$userId);
                $userId = intval($info[0]);
                $propertyId = intval($info[1]);
                $adminId = intval($info[2]);
                /***系统账号信息*/
                $systemAdmin = (new AdminUserService())->getNormalUserById($adminId);
                /***物业账号信息*/
//                    $serviceHouseProperty = new HousePropertyService();
//                    $property = $serviceHouseProperty->getFind(['id'=>$propertyId]);
                $house_admin = new HouseVillageService();
                $user = $house_admin->getHouseVillage($userId);
                $loginTitle = isset($user['village_name'])&&$user['village_name']?$user['village_name'].'【小区】':'小区后台';
                $user['loginTitle'] = $loginTitle;
                if ($systemAdmin && isset($systemAdmin['realname'])) {
                    $user_name = $systemAdmin['realname']?$systemAdmin['realname']:$systemAdmin['account'];
                    $user['adminName'] = $user_name;
                    $user['user_name'] = $user_name . '【系统管理员】';
//                        $user['user_name'] = $user_name . '【系统管理员】从【'.$property['property_name'].'】物业进入';
                } else {
                    $user['user_name']='admin-';
                    $user['adminName'] = '';
                    if(!empty($user) && isset($user['village_name'])){
                        $user['user_name']= 'admin-'.$user['village_name'];
                    }
                }
                unset($user['pwd']);
                // 物业后台普通管理员身份登录的小区超级管理员账号
                break;
            case self::PROPERTY_ADMIN_TO_VILLAGE:
                $info = explode('_',$userId);
                $userId = intval($info[0]);
                $propertyId = intval($info[1]);
                /***物业账号信息*/
                $serviceHouseProperty = new HousePropertyService();
                $property = $serviceHouseProperty->getFind(['id'=>$propertyId]);
                $house_admin = new HouseVillageService();
                $user = $house_admin->getHouseVillage($userId);
                $loginTitle = isset($user['village_name'])&&$user['village_name']?$user['village_name'].'【小区】':'小区后台';
                $user['loginTitle'] = $loginTitle;
                if ($property && isset($property['property_name'])) {
                    $user_name = $property['property_name']?$property['property_name']:'物业总管理员';
                    $user['adminName'] = $user_name;
                    $user['user_name'] = $user_name . '【物业总管理员】';
//                        $user['user_name'] = $user_name . '【系统管理员】从【'.$property['property_name'].'】物业进入';
                } else {
                    $user['user_name']='admin-';
                    $user['adminName'] = '';
                    if(!empty($user) && isset($user['village_name'])){
                        $user['user_name']= 'admin-'.$user['village_name'];
                    }
                }
                unset($user['pwd']);
                break;
            case self::PROPERTY_USER_TO_VILLAGE:
                $info = explode('_',$userId);
                $village_id = intval($info[0]);
                $admin_id = intval($info[1]);
                $userId = $village_id;
                /***物业账号信息*/
                $servicePropertyAdmin = new PropertyAdminService();
                $propertyAdmin = $servicePropertyAdmin->getFind(['id'=>$admin_id]);
                $house_admin = new HouseVillageService();
                $user = $house_admin->getHouseVillage($village_id);
                $loginTitle = isset($user['village_name'])&&$user['village_name']?$user['village_name'].'【小区】':'小区后台';
                $user['loginTitle'] = $loginTitle;
                if ($propertyAdmin && isset($propertyAdmin['realname'])) {
                    $user_name = $propertyAdmin['realname']?$propertyAdmin['realname']:$propertyAdmin['account'];
                    $user['adminName'] = $user_name;
                    $user['user_name'] = $user_name . '【物业普通管理】';
//                        $user['user_name'] = $user_name . '【系统管理员】从【'.$property['property_name'].'】物业进入';
                } else {
                    $user['user_name']='admin-';
                    $user['adminName'] = '';
                    if(!empty($user) && isset($user['village_name'])){
                        $user['user_name']= 'admin-'.$user['village_name'];
                    }
                }
                $user['normal_admin_id']=$admin_id;
                $user['property_menus']='';
                if($admin_id>0){
                    $adminAuthWhere=array();
                    $adminAuthWhere[]=array('admin_id','=',$admin_id);
                    $adminAuthWhere[]=array('property_id','=',0);
                    $adminAuthWhere[]=array('village_id','=',$village_id);
                    $propertyAdminAuth= $servicePropertyAdmin->getOnePropertyAdminAuth($adminAuthWhere);
                    if($propertyAdminAuth && !empty($propertyAdminAuth['menus'])){
                        $menus=explode(',',$propertyAdminAuth['menus']);
                        if($menus){
                            $user['menus']=$menus;
                        }
                    }
                    
                    //物业权限
                    $adminAuthWhere=array();
                    $adminAuthWhere[]=array('admin_id','=',$admin_id);
                    $adminAuthWhere[]=array('property_id','=',$user['property_id']);
                    $adminAuthWhere[]=array('village_id','=',0);
                    $propertyAdminAuth= $servicePropertyAdmin->getOnePropertyAdminAuth($adminAuthWhere);
                    if($propertyAdminAuth && !empty($propertyAdminAuth['menus'])){
                        $property_menus=explode(',',$propertyAdminAuth['menus']);
                        if($property_menus){
                            $user['property_menus']=$property_menus;
                        }
                    }
                }
                unset($user['pwd']);
                break;
            case self::PROPERTY_ADMIN_LOGIN:
            case '3':
                // 物业总管理员
                // 用户信息
                Cache::set('property_login_role', $login_role);
                Cache::set('property_uid', $userId);
                $serviceHouseProperty = new HousePropertyService();
                $user = $serviceHouseProperty->getFind(['id'=>$userId]);
                $user['property_id'] = $userId;
                $user['village_id'] = 0;
                $loginTitle = isset($user['property_name'])&&$user['property_name']?$user['property_name'].'【物业】':'物业后台';
                $user['loginTitle'] = $loginTitle;
                $user['user_name']=$user['property_name'];
                $user['user_name'].="【物业总管理员】";
                unset($user['password']);
                break;
            case self::PROPERTY_USER_LOGIN:
            case '4':
                // 物业普通管理员
                // 用户信息
                Cache::set('property_login_role', $login_role);
                Cache::set('property_uid', $userId);
                $servicePropertyAdmin = new PropertyAdminService();
                $user = $servicePropertyAdmin->getFind(['id'=>$userId]);
                //$this->normal_admin_id = $userId;
                $user['normal_admin_id']=$userId;
                $user['village_id'] = 0;
                $serviceHouseProperty = new HousePropertyService();
                $propertyUser = $serviceHouseProperty->getFind(['id'=>$user['property_id']],'id,property_name');
                $loginTitle = isset($propertyUser['property_name'])&&$propertyUser['property_name']?$propertyUser['property_name'].'【物业】':'物业后台';
                $user['loginTitle'] = $loginTitle;
                $user['user_name']= $user['realname'] ? $user['realname'] : $user['account'];
                $user['user_name'].="【普通管理员】";
                $user['property_menus']='';
               if($user['id']>0){
                   //物业权限
                   $adminAuthWhere=array();
                   $adminAuthWhere[]=array('admin_id','=',$user['id']);
                   $adminAuthWhere[]=array('property_id','=',$user['property_id']);
                   $adminAuthWhere[]=array('village_id','=',0);
                   $propertyAdminAuth= $servicePropertyAdmin->getOnePropertyAdminAuth($adminAuthWhere);
                   if($propertyAdminAuth && !empty($propertyAdminAuth['menus'])){
                       $property_menus=explode(',',$propertyAdminAuth['menus']);
                       if($property_menus){
                           $user['property_menus']=$property_menus;
                       }
                   }
               }
                unset($user['pwd']);
                break;
            case self::VILLAGE_ADMIN_LOGIN:
            case '5':
                $house_admin = new HouseAdminService();
                $where['id'] = $userId;
                $user = $house_admin->getFind($where);
                if (!empty($user)) {
                    $user_info = $user->toArray();
                    $village_info=array();
                    if ($user_info && isset($user_info['village_id']) && $user_info['village_id']) {
                        $house_admin = new HouseVillageService();
                        $village_info = $house_admin->getHouseVillage($user_info['village_id'],'village_name,property_id');
                        if ($village_info && isset($village_info['property_id']) && $village_info['property_id']) {
                            $user['property_id'] = $village_info['property_id'];
                        }
                    }
                }
                $loginTitle = isset($village_info['village_name'])&&$village_info['village_name']?$village_info['village_name'].'【小区】':'小区后台';
                $user['loginTitle'] = $loginTitle;
                $user['village_name']=isset($village_info['village_name']) && $village_info['village_name'] ? $village_info['village_name']:'';
                $user['user_name']= $user['realname'] ? $user['realname'] : $user['account'];
                $user['user_name'].="【普通管理员】";
                unset($user['pwd']);
                // 小区工作人员
                break;
            case self::STREET_COMMUNITY_ADMIN_LOGIN:
            case self::STREET_ADMIN_LOGIN:
            case self::STREET_USER_LOGIN:
            case self::COMMUNITY_ADMIN_LOGIN:
            case self::COMMUNITY_USER_LOGIN:
                // 获得用户信息
                $worker_id=0;
                $service_area_street = new AreaStreetService();
                $user = $service_area_street->getAreaStreet(['area_id'=>$userId]);
                $user['property_id'] = 0;
                $user['village_id'] = 0;
                $loginTitle = isset($user['area_name'])&&$user['area_name']?$user['area_name']:'';
                if (!$loginTitle && $user['area_type']==0) {
                    $loginTitle = '街道后台';
                } elseif(!$loginTitle) {
                    $loginTitle = '社区后台';
                } elseif ($user['area_type']==0) {
                    $loginTitle .= '【街道】';
                } elseif ($user['area_type']==1) {
                    $loginTitle .= '【社区】';
                }
                $user['loginTitle'] = $loginTitle;
                $user['user_name']=$user['area_name'];
                $user['user_name'].="【总管理员】";
                $user['street_worker']=array();
                $user['worker_id']=$worker_id;
                $user['log_uid']=$userId;
                unset($user['pwd']);
                break;
            case self::STREET_COMMUNITY_USER_LOGIN:
                $info = explode('_',$userId);
                $area_id = intval($info[0]);
                $worker_id = intval($info[1]);
                $userId = $area_id;
                // 获得用户信息
                $organizationStreetService=new OrganizationStreetService();
                $whereArr=array('worker_id'=>$worker_id,'area_type'=>0);
                $fieldStr='worker_id,work_num,work_name,work_phone,work_head,work_status,area_id,area_type,menus,village_ids';
                $street_workers = $organizationStreetService->getMemberDetail($whereArr,$fieldStr);
                $user = [];
                if (isset($street_workers['area_id'])&&$street_workers['area_id']) {
                    $service_area_street = new AreaStreetService();
                    $user = $service_area_street->getAreaStreet(['area_id'=>$street_workers['area_id']]);
                    if ($street_workers&&isset($street_workers['work_name'])) {
                        $user['user_name'] = $street_workers['work_name'];
                    } elseif ($user&&isset($user['area_name'])) {
                        $user['user_name']=$user['area_name'];
                    }
                    $user['user_name'] .= "【工作人员】";
                } elseif ($area_id) {
                    $service_area_street = new AreaStreetService();
                    $user = $service_area_street->getAreaStreet(['area_id'=>$area_id]);
                    if ($street_workers&&isset($street_workers['work_name'])) {
                        $user['user_name'] = $street_workers['work_name'];
                    } elseif ($user&&isset($user['area_name'])) {
                        $user['user_name']=$user['area_name'];
                    }
                    $user['user_name'] .= "【工作人员】";
                }
                $user['property_id'] = 0;
                $user['village_id'] = 0;
                $user['street_worker']=array();
                $user['worker_id']= $userId;
                $user['log_uid']= $userId;
                $loginTitle = isset($user['area_name'])&&$user['area_name']?$user['area_name']:'';
                if (!$loginTitle && $user['area_type']==0) {
                    $loginTitle = '街道后台';
                } elseif(!$loginTitle) {
                    $loginTitle = '社区后台';
                } elseif ($user['area_type']==0) {
                    $loginTitle .= '【街道】';
                } elseif ($user['area_type']==1) {
                    $loginTitle .= '【社区】';
                }
                $user['loginTitle'] = $loginTitle;
                if(!empty($street_workers)){
                    $user['street_worker']=$street_workers;
                }
                unset($user['pwd']);
                break;
            case '1':
            case '9':
            case '11':
                // 获得用户信息
                $worker_id=0;
                //兼容街道员工表登录
                if(request()->log_uid && strpos(request()->log_uid,'_')){
                    $userIdArr=explode('_',request()->log_uid);
                    $userId=$userIdArr['0'];
                    $worker_id=$userIdArr['1'];
                }
                $service_area_street = new AreaStreetService();
                $user = $service_area_street->getAreaStreet(['area_id'=>$userId]);
                $user['property_id'] = 0;
                $user['village_id'] = 0;
                $user['user_name']=$user['area_name'];
                $user['street_worker']=array();
                $user['worker_id']=$worker_id;
                $user['log_uid']=request()->log_uid;
                if(!empty($worker_id)){
                    $organizationStreetService=new OrganizationStreetService();
                    $whereArr=array('worker_id'=>$worker_id,'area_type'=>0);
                    $fieldStr='worker_id,work_num,work_name,work_phone,work_head,work_status,area_id,area_type,menus,village_ids';
                    $street_workers=$organizationStreetService->getMemberDetail($whereArr,$fieldStr);
                    if(!empty($street_workers)){
                        $user['street_worker']=$street_workers;
                    }
                }
                $loginTitle = isset($user['area_name'])&&$user['area_name']?$user['area_name']:'';
                if (!$loginTitle && $user['area_type']==0) {
                    $loginTitle = '街道后台';
                } elseif(!$loginTitle) {
                   $loginTitle = '社区后台';
                } elseif ($user['area_type']==0) {
                    $loginTitle .= '【街道】';
                } elseif ($user['area_type']==1) {
                    $loginTitle .= '【社区】';
                }
                $user['loginTitle'] = $loginTitle;
                unset($user['pwd']);
                break;
            case '6':
                $house_admin = new HouseVillageService();
                $service_house_village = new HouseVillageService();
                $user = $house_admin->getHouseVillage($userId);
                $admin_menus = $service_house_village->getHouseMenuNew(array('status'=>1),'id');
                $power = array();
                foreach ($admin_menus as $value) {
                    $power[] = $value['id'];
                }
                $user['user_name']='admin-';
                if(!empty($user) && isset($user['village_name'])){
                    $user['user_name']= 'admin-'.$user['village_name'];
                }
                $user['menus'] = $power;
                unset($user['pwd']);
                // 系统后台登录的小区超级管理员账号
                $loginTitle = isset($user['village_name'])&&$user['village_name']?$user['village_name'].'【小区】':'小区后台';
                $user['loginTitle'] = $loginTitle;
                break;
            case '7':
                $admin_id=0;
                if(strpos($userId,'_')!==false){
                    $tmpArr = explode('_',$userId);
                    $village_id = intval($tmpArr[0]);
                    $admin_id = intval($tmpArr[1]);
                    $userId = $village_id; 
                }
                $house_admin = new HouseVillageService();
                $user = $house_admin->getHouseVillage($userId);
                $user['user_name']= 'admin-'.$user['village_name'];
                unset($user['pwd']);
                $loginTitle = isset($user['village_name'])&&$user['village_name']?$user['village_name'].'【小区】':'小区后台';
                $user['loginTitle'] = $loginTitle;
                if($admin_id>0){
                    $servicePropertyAdmin = new PropertyAdminService();
                    $adminAuthWhere=array();
                    $adminAuthWhere[]=array('admin_id','=',$admin_id);
                    $adminAuthWhere[]=array('property_id','=',0);
                    $adminAuthWhere[]=array('village_id','=',$userId);
                    $propertyAdminAuth= $servicePropertyAdmin->getOnePropertyAdminAuth($adminAuthWhere);
                    if($propertyAdminAuth && !empty($propertyAdminAuth['menus'])){
                        $menus=explode(',',$propertyAdminAuth['menus']);
                        if($menus){
                            $user['menus']=$menus;
                        }
                    }
                }
                // 物业后台登录的小区超级管理员账号
                break;
            case '12':
                $userId = request()->log_uid;
                $info = explode('_',$userId);
                $userId = intval($info[0]);
                //$this->normal_admin_id = intval($info[1]);
                
                $house_admin = new HouseVillageService();
                $user = $house_admin->getHouseVillage($userId);
                $user['user_name']= 'admin-'.$user['village_name'];
                unset($user['pwd']);
                // 物业后台普通管理员身份登录的小区超级管理员账号
                $loginTitle = isset($user['village_name'])&&$user['village_name']?$user['village_name'].'【小区】':'小区后台';
                $user['loginTitle'] = $loginTitle;
                if($info[1]>0){
                    $user['normal_admin_id']=$info[1];
                    $servicePropertyAdmin = new PropertyAdminService();
                    $adminAuthWhere=array();
                    $adminAuthWhere[]=array('admin_id','=',$info[1]);
                    $adminAuthWhere[]=array('property_id','=',0);
                    $adminAuthWhere[]=array('village_id','=',$userId);
                    $propertyAdminAuth= $servicePropertyAdmin->getOnePropertyAdminAuth($adminAuthWhere);
                    if($propertyAdminAuth && !empty($propertyAdminAuth['menus'])){
                        $menus=explode(',',$propertyAdminAuth['menus']);
                        if($menus){
                            $user['menus']=$menus;
                        }
                    }
                }
                break;
            case '8':
                // 物业总管理员
                // 用户信息
                $serviceHouseProperty = new HousePropertyService();
                $user = $serviceHouseProperty->getFind(['id'=>$userId]);
                $user['property_id'] = $userId;
                $user['village_id'] = 0;

                $user['user_name']= 'admin-'.$user['property_name'];

                unset($user['password']);
                // 系统后台登录物业账号
                $loginTitle = isset($user['property_name'])&&$user['property_name']?$user['property_name'].'【物业】':'物业后台';
                $user['loginTitle'] = $loginTitle;
                break;
            default:
                $user = [];
                break;
        }
       $user['_userId']=$userId;
       return $user;
    }


    /**
     * 企业微信注册
     * @author:zhubaodi
     * @date_time: 2021/7/29 10:30
     */
    public function qyRegister($property_id=0,$qyRegisterId=0,$business_type='property'){
//        $service_provider_common_set = cfg('service_provider_common_set');
        $service_provider_common_set = 1;
        $db_village_qywx_guide_about = new VillageQywxGuideAbout();
        if (1==$service_provider_common_set) {
            $provider_secret = cfg('service_provider_secret');
            $corpid = cfg('enterprise_wx_corpid');
            $template_id = cfg('service_template_id');
            if (empty($corpid)) {
                fdump_api(['qyRegister-line请配置企业微信ID:'.__LINE__, $property_id,$qyRegisterId,$business_type],'qyweixin/qyRegisterErrLog',1);
                return array('errcode' => 1001, 'errmsg' => '请配置企业微信ID');
            }
            if (empty($provider_secret)) {
                fdump_api(['qyRegister-line请配置企业ProviderSecret:'.__LINE__, $property_id,$qyRegisterId,$business_type,$corpid],'qyweixin/qyRegisterErrLog',1);
                return array('errcode' => 1001, 'errmsg' => '请前往“服务商管理端-应用管理-通用开发参数中复制ProviderSecret配置到企业微信配置中');
            }
            if (empty($template_id)) {
                fdump_api(['qyRegister-line请配置企业推广码:'.__LINE__, $property_id,$qyRegisterId,$business_type,$provider_secret],'qyweixin/qyRegisterErrLog',1);
                return array('errcode' => 0, 'errmsg' => '请前往“服务商管理端-应用管理-推广二维码”，创建的推广码详情复制到企业微信配置中');
            }
            $params = [
                'corpid' => $corpid,
                'provider_secret' => $provider_secret,
            ];
            $provider_access_msg = invoke_cms_model('Access_token_common_expires/get_provider_token', $params);
            $provider_access_msg=$provider_access_msg['retval'];
            if (!$provider_access_msg['errcode']) {
                $provider_access_token = $provider_access_msg['access_token'];
                if ($property_id) {
                    $where = [
                        'is_del' => 0,
                        'from' => 0,
                        'business_type' => $business_type,
                        'business_id' => $property_id,
                        'type' => 'qyRegisterSelf',
                        'wx_bind_id' => 0
                    ];
                } elseif ($qyRegisterId) {
                    $property_id = 0;
                    $where = [
                        'is_del' => 0,
                        'from' => 0,
                        'business_type' => $business_type,
                        'randomNumber' => $qyRegisterId,
                        'type' => 'qyRegisterSelf',
                        'wx_bind_id' => 0
                    ];
                }
                $qywx_guide_about = $db_village_qywx_guide_about->getOne($where);
                if ($qywx_guide_about) {
                    $guide_about_data = [];
                    $type = $qywx_guide_about['type'];
                    if (!$qyRegisterId && !$qywx_guide_about['randomNumber']) {
                        $qyRegisterId = $type.date('YmdHi') . createRandomStr(5, true).$qywx_guide_about['from'];
                        $guide_about_data['randomNumber'] = $qyRegisterId;
                    } elseif ($qyRegisterId && !$qywx_guide_about['randomNumber']) {
                        $guide_about_data['randomNumber'] = $qyRegisterId;
                    }
                    if (!$qyRegisterId && $qywx_guide_about['randomNumber']) {
                        $qyRegisterId = $qywx_guide_about['randomNumber'];
                    }
                    if ($property_id && !$qywx_guide_about['business_id']) {
                        $guide_about_data['business_id'] = $property_id;
                    }
                    if (!empty($guide_about_data)) {
                        $guide_about_data['update_time'] = time();
                        $save_id= $db_village_qywx_guide_about->saveOne($where,$guide_about_data);
                        if ($save_id!==false) {
                        } else {
                            fdump_api(['qyRegister-line添加记录失败:'.__LINE__, $property_id,$qyRegisterId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyRegisterErrLog',1);
                            return array('errcode' => 0, 'errmsg' => '添加记录失败');
                        }
                    }
                    $add_id = $qywx_guide_about['id'];
                } else {
                    $type = 'qyRegisterSelf';
                    $guide_about_data = [
                        'is_del' => 0,
                        'business_type' =>$business_type,
                        'type' => $type,
                        'from' => 0,
                        'add_time' => time()
                    ];
                    if (!$qyRegisterId) {
                        // 长串保持为20位
                        $qyRegisterId = $type.date('YmdHi') . createRandomStr(5, true).$guide_about_data['from'];
                    }
                    if ($qyRegisterId) {
                        $guide_about_data['randomNumber'] = $qyRegisterId;
                    }
                    if ($property_id) {
                        $guide_about_data['business_id'] = $property_id;
                    }
                    $add_id= $db_village_qywx_guide_about->addOne($guide_about_data);
                    if (!$add_id) {
                        fdump_api(['qyRegister-line添加记录失败:'.__LINE__, $property_id,$qyRegisterId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyRegisterErrLog',1);
                        return array('errcode' => 0, 'errmsg' => '添加记录失败');
                    }
                }
                $state = "{$qyRegisterId}";
               //  $state = "property#{$property_id}|{$type}#{$qyRegisterId}";
                $params = [
                    'provider_access_token' => $provider_access_token,
                    'template_id' => $template_id,
                    'state'=>$state
                ];
                $register_code_msg = invoke_cms_model('Access_token_common_expires/get_register_code', $params);
                $register_code_msg=$register_code_msg['retval'];
               if ($register_code_msg['errcode']) {
                   fdump_api(['qyRegister-line错误:'.__LINE__, $property_id,$qyRegisterId,$business_type,$provider_access_token, $template_id,$state,$register_code_msg],'qyweixin/qyRegisterErrLog',1);
                   return $register_code_msg;
                }
                $register_code = $register_code_msg['register_code'];
                fdump_api(['register_code-line:'.__LINE__, $property_id,$qyRegisterId,$business_type,$register_code,$register_code_msg],'qyweixin/qyRegisterLog',1);
            } else {
                fdump_api(['qyRegister-line错误:'.__LINE__, $property_id,$qyRegisterId,$business_type,$corpid, $provider_secret,$provider_access_msg],'qyweixin/qyRegisterErrLog',1);
                return $provider_access_msg;
            }
            $trusted_ip = $_SERVER['SERVER_ADDR'];
            $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
            if ($siteIp=='127.0.0.1' && $trusted_ip) {
                $siteIp = $trusted_ip;
            }
            $site_url = cfg('site_url');
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
            $ip = get_client_ip(0);
            $data = array(
                'site_url' => cfg('site_url'),
                'siteIp' => $siteIp,
                'domainName' => $domainName,
                'ip' => $ip,
                'qyRegisterId' => $qyRegisterId,
                'property_id' => $property_id,
            );
            $qyRegisterUrl = '';
            $SuiteID = cfg('enterprise_wx_provider_suiteid');
            $provider_config = [
                'corpid' => $corpid,
                'SuiteID' => $SuiteID,
                'provider_secret' => $provider_secret,
                'template_id' => $template_id,
                'provider_access_token' => $provider_access_token,
            ];
            $arr['provider_config'] = $provider_config;
        } else {
            $trusted_ip = $_SERVER['SERVER_ADDR'];
            $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
            if ($siteIp=='127.0.0.1' && $trusted_ip) {
                $siteIp = $trusted_ip;
            }
            $site_url = cfg('site_url');
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
            $ip = get_client_ip(0);
            $data = array(
                'site_url' => cfg('site_url'),
                'siteIp' => $siteIp,
                'domainName' => $domainName,
                'ip' => $ip,
                'qyRegisterId' => $qyRegisterId,
                'property_id' => $property_id,
            );

            $qyRegisterUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyRegister";
            $register_code_msg =http_request($qyRegisterUrl,'POST',http_build_query($data));
            if ($register_code_msg[0]==200){
                $register_code_msg1=json_decode($register_code_msg[1],true);
                if ($register_code_msg1['url']) {
                    $qyRegisterId = $register_code_msg1['qyRegisterId'];
                    // 成功返回了 连接 做记录
                    if ($property_id) {
                        $where = [
                            'is_del' => 0,
                            'from' => 0,
                            'business_type' => $business_type,
                            'business_id' => $property_id,
                            'type' => 'qyRegisterPost',
                            'site_url' => $site_url,
                            'qyResult' => '',
                        ];
                    } elseif ($qyRegisterId) {
                        $property_id = 0;
                        $where = [
                            'is_del' => 0,
                            'from' => 0,
                            'business_type' => $business_type,
                            'randomNumber' => $qyRegisterId,
                            'type' => 'qyRegisterPost',
                            'site_url' => $site_url,
                            'qyResult' => '',
                        ];
                    }
                    $qywx_guide_about = $db_village_qywx_guide_about->getOne($where);
                    if ($qywx_guide_about) {
                        $guide_about_data = [];
                        if ($qyRegisterId && !$qywx_guide_about['randomNumber']) {
                            $guide_about_data['randomNumber'] = $qyRegisterId;
                        }
                        if ($property_id && !$qywx_guide_about['business_id']) {
                            $guide_about_data['business_id'] = $property_id;
                        }
                        if ($siteIp && $siteIp!=$qywx_guide_about['siteIp']) {
                            $guide_about_data['siteIp'] = $siteIp;
                        }
                        if ($ip && $ip!=$qywx_guide_about['ip']) {
                            $guide_about_data['ip'] = $ip;
                        }
                        if ($domainName && $domainName!=$qywx_guide_about['domainName']) {
                            $guide_about_data['domainName'] = $domainName;
                        }
                    $param = serialize($data);
                    if ($param && $param!=$qywx_guide_about['param']) {
                        $guide_about_data['param'] = $param;
                    }
                    if ($qyRegisterUrl && $qyRegisterUrl!=$qywx_guide_about['param_url']) {
                        $guide_about_data['param_url'] = $qyRegisterUrl;
                    }
                    $param_result = serialize($register_code_msg1);
                    if ($param_result && $param_result!=$qywx_guide_about['param_result']) {
                        $guide_about_data['param_result'] = $param_result;
                    }
                    if (!empty($guide_about_data)) {
                            $guide_about_data['update_time'] = time();
                            $save_id = $db_village_qywx_guide_about->saveOne($where,$guide_about_data);
                            if ($save_id!==false) {
                            } else {
                              //   fdump_api(['qyRegister-linePost添加记录失败:'.__LINE__, $property_id,$qyRegisterId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyRegisterErrLog',1);
                                return array('errcode' => 0, 'errmsg' => '添加记录失败');
                            }
                        }
                    } else {
                        $type = 'qyRegisterPost';
                        $guide_about_data = [
                            'is_del' => 0,
                            'business_type' => $business_type,
                            'type' => $type,
                            'from' => 0,
                            'business_id' => $property_id,
                        'site_url' => $site_url?$site_url:'',
                            'siteIp' => $siteIp?$siteIp:'',
                            'ip' => $ip?$ip:'',
                            'domainName' => $domainName?$domainName:'',
                            'add_time' => time()
                        ];
                        if ($qyRegisterId) {
                            $guide_about_data['randomNumber'] = $qyRegisterId;
                        }
                        if ($property_id) {
                            $guide_about_data['business_id'] = $property_id;
                        }
                        $guide_about_data['param'] = serialize($data);
                        $guide_about_data['param_url'] = $qyRegisterUrl;
                        $guide_about_data['param_result'] = serialize($register_code_msg1);
                        $add_id = $db_village_qywx_guide_about->addOne($guide_about_data);
                        if (!$add_id) {
                           //  fdump_api(['qyRegister-linePost添加记录失败:'.__LINE__, $property_id,$qyRegisterId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyRegisterErrLog',1);
                            return array('errcode' => 0, 'errmsg' => '添加记录失败');
                        }
                    }
                } else {
                 //    fdump_api(['qyRegister-linePost请求返回异常:'.__LINE__, $property_id,$qyRegisterId,$business_type,$qyRegisterUrl,$data,$register_code_msg1],'qyweixin/qyRegisterErrLog',1);
                    return array('errcode' => 0, 'errmsg' => '添加记录失败');
                }
            }
           //  fdump_api(['qyRegister-linePost请求返回:'.__LINE__, $property_id,$qyRegisterId,$business_type,$qyRegisterUrl,$data,$register_code_msg1],'qyweixin/qyRegisterLog',1);

            $register_code_msg1['randomNumber'] = $register_code_msg1['qyRegisterId'];
            return $register_code_msg1;
        }
        $arr = [
            'errcode' => 0,
            'errmsg' => '',
        ];
        // 注册按钮链接的生成和嵌入
        $url = "https://open.work.weixin.qq.com/3rdservice/wework/register?register_code=$register_code";
        $arr['url'] = $url;
        $arr['randomNumber'] = $qyRegisterId;
        if ($add_id) {
            $guide_about_data = [];
            $guide_about_data['param'] = serialize($data);
            $guide_about_data['param_url'] = $qyRegisterUrl;
            $guide_about_data['param_result'] = serialize($register_code_msg);
            $guide_about_data['update_time'] = time();
            $where = [
                'id' => $add_id
            ];
            $save_id = $db_village_qywx_guide_about->saveOne($where,$guide_about_data);
            if ($save_id!==false) {
            } else {
                fdump_api(['qyRegister-line添加记录失败:'.__LINE__, $property_id,$qyRegisterId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyRegisterErrLog',1);
                return array('errcode' => 0, 'errmsg' => '添加记录失败');
            }
        }
        return $arr;
    }

    /**
     * 安装企业微信
     * @author:zhubaodi
     * @date_time: 2021/7/29 10:31
     */
    public function qyInstall($property_id=0,$qyInstallId=0,$business_type='property') {
//        $service_provider_common_set = cfg('service_provider_common_set');
        $service_provider_common_set = 1;
        $site_url = cfg('site_url');
        $db_village_qywx_guide_about = new VillageQywxGuideAbout();
        $db_config = new Config();
        if (1 == $service_provider_common_set) {
            $where_suite_ticket = [
                'name' => 'enterprise_wx_suite_ticket'
            ];
            $SuiteTicket = $db_config->getOne($where_suite_ticket,'value');
            $SuiteTicket = trim($SuiteTicket['value']);
            $where_enterprise_wx_corpid = [
                'name' => 'enterprise_wx_corpid'
            ];
            $corpid =$db_config->getOne($where_enterprise_wx_corpid,'value');
            $corpid = trim($corpid['value']);
            $where_enterprise_wx_provider_secret = [
                'name' => 'enterprise_wx_provider_secret'
            ];
            $Secret = $db_config->getOne($where_enterprise_wx_provider_secret,'value');
            $Secret = trim($Secret['value']);
            $where_enterprise_wx_provider_suiteid = [
                'name' => 'enterprise_wx_provider_suiteid'
            ];
            $SuiteID = $db_config->getOne($where_enterprise_wx_provider_suiteid,'value');
            $SuiteID = trim($SuiteID['value']);
            $where_enterprise_wx_provider_token = [
                'name' => 'enterprise_wx_provider_token'
            ];
            $Token = $db_config->getOne($where_enterprise_wx_provider_token,'value');
            $Token = trim($Token['value']);
            if (empty($corpid)) {
                fdump_api(['qyInstall-line请配置企业微信ID:'.__LINE__, $property_id,$qyInstallId,$business_type],'qyweixin/qyInstallErrLog',1);
                return array('errcode' => 1001, 'errmsg' => '请配置企业微信ID');
            }
            if (empty($Secret)) {
                fdump_api(['qyInstall-line请配置企业服务商应用Secret:'.__LINE__, $property_id,$qyInstallId,$business_type,$corpid],'qyweixin/qyInstallErrLog',1);
                return array('errcode' => 1001, 'errmsg' => '请前往“服务商管理端-应用管理-网页应用-对应创建的普通应用”中复制Secret到企业微信配置中');
            }
            if (empty($SuiteID)) {
                fdump_api(['qyInstall-line请配置企业服务商应用SuiteID:'.__LINE__, $property_id,$qyInstallId,$business_type,$corpid,$Secret],'qyweixin/qyInstallErrLog',1);
                return array('errcode' => 0, 'errmsg' => '请前往“服务商管理端-应用管理-网页应用-对应创建的普通应用”中复制SuiteID到企业微信配置中');
            }
            if (empty($Token)) {
                fdump_api(['qyInstall-line请配置企业服务商应用Token:'.__LINE__, $property_id,$qyInstallId,$business_type,$corpid,$SuiteID,$Secret],'qyweixin/qyInstallErrLog',1);
                return array('errcode' => 0, 'errmsg' => '请前往“服务商管理端-应用管理-网页应用-对应创建的普通应用”中复制Token到企业微信配置中');
            }
            if (empty($SuiteTicket)) {
                fdump_api(['qyInstall-line请配置企业服务商应用SuiteTicket:'.__LINE__, $property_id,$qyInstallId,$business_type,$corpid,$SuiteID,$Secret,$Token],'qyweixin/qyInstallErrLog',1);
                return array('errcode' => 0, 'errmsg' => '请前往“服务商管理端-应用管理-网页应用-对应创建的普通应用”中正确完成回调配置');
            }
            $provider_config = [
                'corpid'      => $corpid,
                'Secret'      => $Secret,
                'SuiteID'     => $SuiteID,
                'Token'       => $Token,
                'SuiteTicket' => $SuiteTicket,
            ];
            $setConfig = [
                'suite_secret'  => $Secret,
                'suite_id'      => $SuiteID,
                'suite_ticket'  => $SuiteTicket,
            ];
            $serviceWorkWeiXinSuite = new WorkWeiXinSuiteService();
            $serviceWorkWeiXinSuite->setConfig($setConfig);
            $pre_auth_code_arr = $serviceWorkWeiXinSuite->setSessionInfo();
            $pre_auth_code = '';
            if (isset($pre_auth_code_arr['pre_auth_code']) && $pre_auth_code_arr['pre_auth_code']) {
                $pre_auth_code      = $pre_auth_code_arr['pre_auth_code'];
                $suite_access_token = $pre_auth_code_arr['suite_access_token'];
            } elseif (isset($pre_auth_code_arr['code']) && !empty($pre_auth_code_arr['code'])) {
                fdump_api(['qyInstall-line设置授权配置失败:'.__LINE__, 'property_id' => $property_id,'qyInstallId' =>$qyInstallId,'business_type' => $business_type,'pre_auth_code_arr' =>$pre_auth_code_arr],'qyweixin/qyInstallErrLog',1);
                return ['errcode' => $pre_auth_code_arr['code'], 'errmsg' => $pre_auth_code_arr['message']];
            }
            if ($property_id) {
                $where = [
                    'is_del'        => 0,
                    'from'          => 0,
                    'business_type' => $business_type,
                    'business_id'   => $property_id,
                    'type'          => 'qyInstallSelf',
                    'wx_bind_id'    => 0
                ];
            } elseif ($qyInstallId) {
                $property_id = 0;
                $where = [
                    'is_del'        => 0,
                    'from'          => 0,
                    'business_type' => $business_type,
                    'randomNumber'  => $qyInstallId,
                    'type'          => 'qyInstallSelf',
                    'wx_bind_id'    => 0
                ];
            }
            if (empty($where)) {
                $qywx_guide_about = [];
            } else {
                $qywx_guide_about = $db_village_qywx_guide_about->getOne($where);
            }
            if ($qywx_guide_about) {
                $guide_about_data = [];
                $type = $qywx_guide_about['type'];
                if (!$qyInstallId && !$qywx_guide_about['randomNumber']) {
                    $qyInstallId = $type.date('YmdHi') . createRandomStr(5, true).$qywx_guide_about['from'];
                    $guide_about_data['randomNumber'] = $qyInstallId;
                } elseif ($qyInstallId && !$qywx_guide_about['randomNumber']) {
                    $guide_about_data['randomNumber'] = $qyInstallId;
                }
                if (!$qyInstallId && $qywx_guide_about['randomNumber']) {
                    $qyInstallId = $guide_about_data['randomNumber'];
                }
                if ($property_id && !$qywx_guide_about['business_id']) {
                    $guide_about_data['business_id'] = $property_id;
                }
                $type = $qywx_guide_about['type'];
                if (!empty($guide_about_data)) {
                    $guide_about_data['update_time'] = time();
                    $save_id = $db_village_qywx_guide_about->saveOne($where,$guide_about_data);
                    if ($save_id!==false) {
                    } else {
                        fdump_api([
                            'qyInstall-lineSelf添加记录失败:'.__LINE__, 
                            'property_id'      => $property_id,
                            'qyInstallId'      => $qyInstallId,
                            'business_type'    => $business_type,
                            'guide_about_data' => $guide_about_data,
                            'sql'              => $db_village_qywx_guide_about->_sql(),
                            'errSql'           => $db_village_qywx_guide_about->getDbError(),
                            ],'qyweixin/qyInstallErrLog',1);
                        return array('errcode' => 0, 'errmsg' => '添加记录失败');
                    }
                }
                $add_id = $qywx_guide_about['id'];
            } else {
                $type = 'qyInstallSelf';
                $guide_about_data = [
                    'is_del'        => 0,
                    'business_type' => $business_type,
                    'type'          => $type,
                    'from'          => 0,
                    'add_time'      => time()
                ];
                if (!$qyInstallId) {
                    // 长串保持为20位
                    $qyInstallId = $type.date('YmdHi') . createRandomStr(5, true).$guide_about_data['from'];
                }
                if ($qyInstallId) {
                    $guide_about_data['randomNumber'] = $qyInstallId;
                }
                if ($property_id) {
                    $guide_about_data['business_id'] = $property_id;
                }
                $add_id = $db_village_qywx_guide_about->addOne($guide_about_data);
                if (empty($add_id)) {
                    fdump_api(['qyInstall-lineSelf添加记录失败:'.__LINE__, $property_id,$qyInstallId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyInstallErrLog',1);
                    return array('errcode' => 0, 'errmsg' => '添加记录失败');
                }
            }
            $SuiteID = cfg('enterprise_wx_provider_suiteid');
            $state = "{$qyInstallId}";
            $redirect_url = $site_url . "/index.php?g=Index&c=Qywx&a=auth_back&suite_id={$SuiteID}&state={$state}";
            $redirect_url = urlencode($redirect_url);
            // 第三方服务商在自己的网站中放置“企业微信应用授权”的入口，引导企业微信管理员进入应用授权页。授权页网址为:
            $bind_url = $serviceWorkWeiXinSuite->get3rdappInstallUrl($pre_auth_code, $redirect_url, $state);
            $arr = [
                'errcode' => 0,
                'errmsg'  => '',
            ];
            $arr['url']             = $bind_url;
            $arr['randomNumber']    = $qyInstallId;
            $arr['provider_config'] = $provider_config;
            if ($add_id) {
                $trusted_ip = $_SERVER['SERVER_ADDR'];
                $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
                if ($siteIp == '127.0.0.1' && $trusted_ip) {
                    $siteIp = $trusted_ip;
                }
                $site_url   = cfg('site_url');
                $domainName = str_replace('http://','',$site_url);
                $domainName = str_replace('https://','',$domainName);
                $ip = get_client_ip(0);
                $data = array(
                    'site_url'    => cfg('site_url'),
                    'siteIp'      => $siteIp,
                    'domainName'  => $domainName,
                    'ip'          => $ip,
                    'qyInstallId' => $qyInstallId,
                    'property_id' => $property_id,
                );
                $guide_about_data = [];
                $guide_about_data['param']        = serialize($data);
                $guide_about_data['param_url']    = '';
                $guide_about_data['param_result'] = serialize($arr);
                $guide_about_data['update_time']  = time();
                $where = [
                    'id' => $add_id
                ];
                $save_id = $db_village_qywx_guide_about->saveOne($where,$guide_about_data);
                if ($save_id!==false) {
                } else {
                    fdump_api(['qyRegister-lineSelf添加记录失败:'.__LINE__, $property_id,$qyInstallId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyRegisterErrLog',1);
                    return array('errcode' => 0, 'errmsg' => '添加记录失败');
                }
            }
            unset($arr['provider_config']);
            return $arr;
        } else {
            $trusted_ip = $_SERVER['SERVER_ADDR'];
            $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
            if ($siteIp=='127.0.0.1' && $trusted_ip) {
                $siteIp = $trusted_ip;
            }
            $site_url = cfg('site_url');
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
            $ip = get_client_ip(0);
            $data = array(
                'site_url' => cfg('site_url'),
                'siteIp' => $siteIp,
                'domainName' => $domainName,
                'ip' => $ip,
                'qyInstallId' => $qyInstallId,
                'property_id' => $property_id,
            );
            $qyInstallUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyInstall";
            $install_msg = http_request($qyInstallUrl,'POST', http_build_query($data));
            if ($install_msg[0]==200){
                $install_msg1=json_decode($install_msg[1],true);
                if ($install_msg1['url']) {
                    $qyInstallId = $install_msg1['qyInstallId'];
                    // 成功返回了 连接 做记录
                    if ($property_id) {
                        $where = [
                            'is_del' => 0,
                            'from' => 0,
                            'business_type' => $business_type,
                            'business_id' => $property_id,
                        'type' => 'qyInstallPost',
                            'site_url' => $site_url,
                            'qyResult' => '',
                        ];
                    } elseif ($qyInstallId) {
                        $property_id = 0;
                        $where = [
                            'is_del' => 0,
                            'from' => 0,
                            'business_type' => $business_type,
                            'randomNumber' => $qyInstallId,
                        'type' => 'qyInstallPost',
                            'site_url' => $site_url,
                            'qyResult' => '',
                        ];
                    }
                    $qywx_guide_about = $db_village_qywx_guide_about->getOne($where);
                    if ($qywx_guide_about) {
                        $guide_about_data = [];
                        if ($qyInstallId && !$qywx_guide_about['randomNumber']) {
                            $guide_about_data['randomNumber'] = $qyInstallId;
                        }
                        if ($property_id && !$qywx_guide_about['business_id']) {
                            $guide_about_data['business_id'] = $property_id;
                        }
                        if ($siteIp && $siteIp!=$qywx_guide_about['siteIp']) {
                            $guide_about_data['siteIp'] = $siteIp;
                        }
                        if ($ip && $ip!=$qywx_guide_about['ip']) {
                            $guide_about_data['ip'] = $ip;
                        }
                        if ($domainName && $domainName!=$qywx_guide_about['domainName']) {
                            $guide_about_data['domainName'] = $domainName;
                        }
                    $param = serialize($data);
                    if ($param && $param!=$qywx_guide_about['param']) {
                        $guide_about_data['param'] = $param;
                    }
                    if ($qyInstallUrl && $qyInstallUrl!=$qywx_guide_about['param_url']) {
                            $guide_about_data['param_url'] = $qyInstallUrl;
                    }
                    $param_result = serialize($install_msg);
                    if ($param_result && $param_result!=$qywx_guide_about['param_result']) {
                        $guide_about_data['param_result'] = $param_result;
                    }
                    if (!empty($guide_about_data)) {
                            $guide_about_data['update_time'] = time();
                            $save_id = $db_village_qywx_guide_about->saveOne($where,$guide_about_data);
                            if ($save_id!==false) {
                            } else {
                                fdump_api(['qyInstallSelf添加记录失败:'.__LINE__, $property_id,$qyInstallId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyRegisterErrLog',1);
                                return array('errcode' => 0, 'errmsg' => '添加记录失败');
                            }
                        }
                    } else {
                    $type = 'qyInstallPost';
                        $guide_about_data = [
                            'is_del' => 0,
                            'business_type' => $business_type,
                            'type' => $type,
                            'from' => 0,
                            'business_id' => $property_id,
                        'site_url' => $site_url?$site_url:'',
                            'siteIp' => $siteIp?$siteIp:'',
                            'ip' => $ip?$ip:'',
                            'domainName' => $domainName?$domainName:'',
                            'add_time' => time()
                        ];
                        if ($qyInstallId) {
                            $guide_about_data['randomNumber'] = $qyInstallId;
                        }
                        if ($property_id) {
                            $guide_about_data['business_id'] = $property_id;
                        }
                        $guide_about_data['param'] = serialize($data);
                        $guide_about_data['param_url'] = $qyInstallUrl;
                        $guide_about_data['param_result'] = serialize($install_msg1);
                        $add_id = $db_village_qywx_guide_about->addOne($guide_about_data);
                        if (!$add_id) {
                            fdump_api(['qyInstallSelf添加记录失败:'.__LINE__, $property_id,$qyInstallId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyRegisterErrLog',1);
                            return array('errcode' => 0, 'errmsg' => '添加记录失败');
                        }
                    }
                } else {
                    fdump_api(['qyInstallSelf请求返回异常:'.__LINE__, $property_id,$qyInstallId,$business_type,$qyInstallUrl,$data,$install_msg1],'qyweixin/qyRegisterErrLog',1);
                }
            }
            fdump_api(['qyInstallSelf请求返回:'.__LINE__, $property_id,$qyInstallId,$business_type,$qyInstallUrl,$data,$install_msg1],'qyweixin/qyRegisterLog',1);
            $install_msg1['randomNumber'] = $install_msg1['qyInstallId'];
            return $install_msg1;
        }

    }

    /**
     * 查询安装/注册结果
     * @author:zhubaodi
     * @date_time: 2021/8/3 13:23
     */
    public function getResult($randomNumber,$type='login',$property_id=0,$business_type='property'){
            fdump_api(['getResult-line查询数据:'.__LINE__, $randomNumber,$type,$business_type],'qyweixin/v20Admin/getResultLog',1);
            $db_village_qywx_guide_about=new VillageQywxGuideAbout();
            $qywx_guide_about=$db_village_qywx_guide_about->getOne(['randomNumber'=>$randomNumber, 'business_type' => $business_type]);
            if (empty($qywx_guide_about)){
                fdump_api(['getResult-line查询数据:'.__LINE__, $randomNumber,$type,$business_type],'qyweixin/v20Admin/getResultErrLog',1);
                return array('errcode' => 1001, 'errmsg' => '查询对象不存在');
            }else{
                $login_type_arr = ['qyLoginSelf', 'qyLoginPost'];
                $install_type_arr = ['qyInstallSelf', 'qyInstallPost'];
                $register_type_arr = ['qyRegisterSelf', 'qyRegisterPost'];
                $type_arr = ['login', 'install','register'];
                if ('login'==$type && !in_array($qywx_guide_about['type'], $login_type_arr)) {
                    fdump_api(['qyGuideAbout-line查询数据和对应类型不符:'.__LINE__, $qywx_guide_about,$randomNumber,$type,$business_type,$_POST,$login_type_arr],'qyweixin/v20Admin/getResultErrLog',1);
                    return array('errcode' => 1001, 'errmsg' => '查询对象不存在');
                } elseif ('install'==$type && !in_array($qywx_guide_about['type'], $install_type_arr)) {
                    fdump_api(['qyGuideAbout-line查询数据和对应类型不符:'.__LINE__, $qywx_guide_about,$randomNumber,$type,$business_type,$_POST,$install_type_arr],'qyweixin/v20Admin/getResultErrLog',1);
                    return array('errcode' => 1001, 'errmsg' => '查询对象不存在');
                } elseif ('register'==$type && !in_array($qywx_guide_about['type'], $register_type_arr)) {
                    fdump_api(['qyGuideAbout-line查询数据和对应类型不符:'.__LINE__, $qywx_guide_about,$randomNumber,$type,$business_type,$_POST,$register_type_arr],'qyweixin/v20Admin/getResultErrLog',1);
                    return array('errcode' => 1001, 'errmsg' => '查询对象不存在');
                }elseif(!in_array($type,$type_arr)) {
                    fdump_api(['qyGuideAbout-line查询数据和对应类型不符:'.__LINE__, $qywx_guide_about,$randomNumber,$type,$business_type,$_POST,$register_type_arr],'qyweixin/v20Admin/getResultErrLog',1);
                    return array('errcode' => 1001, 'errmsg' => '查询对象不存在');
                }
                if (!$property_id && $qywx_guide_about['business_id']) {
                    $property_id = $qywx_guide_about['business_id'];
                }
                // 远程类型
                $post_type_arr = ['qyLoginPost', 'qyInstallPost', 'qyRegisterPost'];
                // 如果是登陆 查询下获取到的值 然后进行处理
                $qyResult = [];
                if (isset($qywx_guide_about['qyResult']) && $qywx_guide_about['qyResult']) {
                    $qyResult = unserialize($qywx_guide_about['qyResult']);
                }
                if ($qywx_guide_about['authCorpInfo']) {
                    $authCorpInfo = unserialize($qywx_guide_about['authCorpInfo']);
                }
                if ($qywx_guide_about['param_result']) {
                    $param_result = unserialize($qywx_guide_about['param_result']);
                }
                if (isset($qyResult) && empty($qyResult) && in_array($qywx_guide_about['type'], $post_type_arr)) {
                    $trusted_ip = $_SERVER['SERVER_ADDR'];
                    $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
                    if ($siteIp=='127.0.0.1' && $trusted_ip) {
                        $siteIp = $trusted_ip;
                    }
                    $site_url = cfg('site_url');
                    $domainName = str_replace('http://','',$site_url);
                    $domainName = str_replace('https://','',$domainName);
                    $ip = get_client_ip(0);
                    $data = array(
                        'site_url' =>cfg('site_url'),
                        'siteIp' => $siteIp,
                        'domainName' => $domainName,
                        'ip' => $ip,
                        'randomNumber' => $randomNumber,
                        'property_id' => $property_id,
                    );
                    $qyGuideAboutUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyGuideAbout";
                    $guide_about_msg = http_request($qyGuideAboutUrl,'POST', $data);
                    fdump_api(['getResult-line查询数据:'.__LINE__, $qyGuideAboutUrl,$data,$guide_about_msg],'qyweixin/v20Admin/getResultLog',1);
                    if ($guide_about_msg[0]==200){
                        $guide_about_msg=json_decode($guide_about_msg[1],true);
                        if ($guide_about_msg['errcode']) {
                            return $guide_about_msg;
                        }
                        $other_qywx_guide_about = $guide_about_msg['info'];
                        if ($other_qywx_guide_about['qyResult']) {
                            $qyResult = unserialize($other_qywx_guide_about['qyResult']);
                        }
                        if ($other_qywx_guide_about['authCorpInfo']) {
                            $authCorpInfo = unserialize($other_qywx_guide_about['authCorpInfo']);
                        }
                        if ($other_qywx_guide_about['param_result']) {
                            $param_result = unserialize($other_qywx_guide_about['param_result']);
                        }
                        if (!$property_id && $other_qywx_guide_about['business_id']) {
                            $property_id = $other_qywx_guide_about['business_id'];
                        }
                        fdump_api(['getResult-line查询数据:'.__LINE__,$property_id, $qyResult,$authCorpInfo,$param_result],'qyweixin/v20Admin/getResultLog',1);
                    }

                }
                $arr = array('errcode' => 0, 'errmsg' => '');
                $arr['type'] = $type;
                if (empty($qyResult) && 'login'==$type) {
                    $arr['login'] = false;
                    $arr['jump_url'] = '';// 登陆成功后跳转链接
                    $arr['randomNumber'] = $randomNumber;
                } 
                elseif (empty($qyResult) && 'install'==$type) {
                    // 极速安装快鲸到企业微信——用快鲸，连接客户
                    $arr['install'] = false; // 返回下载信息失败
                    $arr['jump_url'] = ''; // 下载成功后跳转链接（注册页面暂时不需要跳转 用户物业后台）
                    $arr['randomNumber'] = $randomNumber;
                } 
                elseif (empty($qyResult) && 'register'==$type) {
                    // 注册包含快鲸的企业微信——用快鲸，连接客户
                    $arr['register'] = false; // 返回注册信息失败
                    $arr['jump_url'] = ''; // 注册成功后跳转链接（注册页面暂时不需要跳转 用户物业后台）
                    $arr['randomNumber'] = $randomNumber;
                } 
                else {
                    $where_qywx_guide_about = [
                        'id'=>$qywx_guide_about['id']
                    ];
                    $save_data = [];
                    if (!empty($qyResult)) {
                        $save_data['qyResult'] = serialize($qyResult);
                    }
                    if ($business_type=='property' && $property_id && $property_id != $qywx_guide_about['property_id']) {
                        $save_data['business_id'] = $property_id;
                    }
                    $db_house_enterprise_wx_bind = new HouseEnterpriseWxBind();
                    $db_house_property = new HouseProperty();

                    if (!empty($save_data) && 'login'==$type) {
                        $arr['login'] = false;
                        $arr['jump_url'] = '';// 登陆成功后跳转链接
                        $arr['randomNumber'] = $randomNumber;

                        $save_data['update_time'] = time();
                        $where_qywx_guide_about = [
                            'id'=>$qywx_guide_about['id']
                        ];
                        if (isset($qyResult['corpid'])) {
                            $login_corpid = $qyResult['corpid'];
                        } else {
                            $auth_corp_info = $qyResult['corp_info'];
                            $login_corpid = $auth_corp_info['corpid'];
                        }
                        if (isset($login_corpid) && $login_corpid) {
                            $where_bind = [
                                'bind_type' => 0,
                                'corpid' => $login_corpid
                            ];
                            $bind_info = $db_house_enterprise_wx_bind->getOne($where_bind);
                            if ($bind_info['bind_id']) {
                                $house_property = $db_house_property->get_one(['id' => $bind_info['bind_id']]);
                                $house_property['login_identify'] = 1;
                                $house_property['isQywx'] = 1;
                                session('property', $house_property);
                                // 给予物业管理员身份
                                $ticket = Token::createToken($bind_info['bind_id'], 8);
                                setcookie('property_access_token', $ticket, -1, '/');
                                $arr['login'] = true;
                                $arr['jump_url'] = cfg('site_url') . '/v20/public/platform/#/property/property.iframe/property_index';
                                $arr['ticket'] = $ticket;
                                $save_data['wx_bind_id'] = $bind_info['pigcms_id'];
                            }
                        }
                    }
                    elseif (!empty($qyResult) && 'install'==$type) {
                        $arr['install'] = true; // 返回下载信息失败
                        $arr['jump_url'] = ''; // 下载成功后跳转链接（注册页面暂时不需要跳转 用户物业后台）
                        $arr['randomNumber'] = $randomNumber;
                        // 直接记录即可 暂无处理
                        fdump_api(['getResult-line查询数据:'.__LINE__,$business_type, $property_id],'qyweixin/v20Admin/getResultLog',1);
                        if ($business_type=='property' && $property_id) {
                            $data = $qyResult;
                            $now_time = time();
                            $providerConfig = $data['providerConfig'];
                            if (empty($providerConfig)) {
                                $data_config = $this->getDataConfig();
                                $providerConfig['SuiteID'] = $data_config['enterprise_wx_provider_suiteid'];
                            }
                            unset($data['errcode']);
                            unset($data['errmsg']);
                            unset($data['authConfig']);
                            $data['expires_in'] = $data['expires_in'] + $now_time;
                            $data['bind_type'] = 0;
                            $data['bind_id'] = $property_id;
                            $data['wx_provider_suiteid'] = $providerConfig['SuiteID'];
                            $auth_corp_info = isset($data['auth_corp_info']) && $data['auth_corp_info'] ? $data['auth_corp_info'] : [];
                            $auth_info = $data['auth_info'];
                            if ($auth_info && !empty($auth_info['agent'])) {
                                $agent = reset($auth_info['agent']);
                                $data['agentid'] = $agent['agentid'];
                                $data['corp_square_logo_url'] = $agent['square_logo_url']?$agent['square_logo_url']:'';
                            }
                            if (isset($login_corpid) && $login_corpid) {
                                $data['corpid'] = $login_corpid;
                            }
                            if ($auth_corp_info) {
                                $data['corp_name'] = $auth_corp_info['corp_name']? $auth_corp_info['corp_name']:'';
                                $data['corp_type'] = $auth_corp_info['corp_type']?$auth_corp_info['corp_type']:'';
                                $data['subject_type'] = $auth_corp_info['subject_type']?$auth_corp_info['subject_type']:'';
                                $data['verified_end_time'] = $auth_corp_info['verified_end_time']?$auth_corp_info['verified_end_time']:'';
                                $data['corp_wxqrcode'] = $auth_corp_info['corp_wxqrcode']?$auth_corp_info['corp_wxqrcode']:'';
                                $data['auth_corp_info'] = serialize($auth_corp_info);
                                $data['corp_user_max'] = $auth_corp_info['corp_user_max']?$auth_corp_info['corp_user_max']:0;
                                $data['corp_agent_max'] = $auth_corp_info['corp_agent_max']?$auth_corp_info['corp_agent_max']:0;
                                $data['corp_full_name'] = $auth_corp_info['corp_full_name']?$auth_corp_info['corp_full_name']:0;
                            }

                            $data['auth_info'] = serialize($auth_info);

                            $data['userid'] = $data['auth_user_info']['userid'];
                            $data['name'] = $data['auth_user_info']['name'];
                            $data['avatar'] = $data['auth_user_info']['avatar'];
                            $data['auth_user_info'] = serialize($data['auth_user_info']);
                            $data['add_time'] = time();
                            $err=false;
                            if ($is_bind = $db_house_enterprise_wx_bind->getOne(array('corpid' => $login_corpid, 'bind_type' => 0))) {
                                if ($is_bind['bind_id'] != $property_id) {
                                    fdump_api(['getResult-line查询数据:'.__LINE__,$is_bind,$property_id],'qyweixin/v20Admin/getResultLog',1);
                                    $arr['msg'] = '该企业公众号已在其他物业完成绑定，无法绑定到当前物业!';
                                    $err=true;
                                }
                            }
                            fdump_api(['getResult-line查询数据:'.__LINE__,$data],'qyweixin/v20Admin/getResultLog',1);
                            if (isset($data['register_code_info'])) {
                                unset($data['register_code_info']);
                            }
                            if (!$err &&$is_bind = $db_house_enterprise_wx_bind->getOne(array('bind_id' => $property_id, 'bind_type' => 0))) {
                                $db_house_enterprise_wx_bind->save_one(array('bind_id' => $property_id, 'bind_type' => 0),$data);
                                $wx_bind_id = $is_bind['pigcms_id'];
                            }elseif (!$err) {
                                $wx_bind_id = $db_house_enterprise_wx_bind->add($data);
                            } elseif ($is_bind['bind_id'] == $property_id) {
                                $db_house_enterprise_wx_bind->save_one(array('pigcms_id' => $is_bind['pigcms_id']),$data);
                                $wx_bind_id = $is_bind['pigcms_id'];
                            }
                            fdump_api(['getResult-line查询数据:'.__LINE__,$wx_bind_id,$db_house_enterprise_wx_bind->getLastSql()],'qyweixin/v20Admin/getResultLog',1);
                            if ($wx_bind_id) {
                                $save_data['wx_bind_id'] = $wx_bind_id;
                                if ($qywx_guide_about['type']=='qyInstallPost') {
                                    $dataSet = array(
                                        'site_url' => cfg('site_url'),
                                        'siteIp' => $siteIp,
                                        'domainName' => $domainName,
                                        'ip' => $ip,
                                        'randomNumber' => $randomNumber,
                                        'property_id' => $property_id,
                                        'wx_bind_id' => $wx_bind_id,
                                    );
                                    $qyGuideSetUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyGuideSet";
                                    http_request($qyGuideSetUrl,'POST', $dataSet);
                                }
                            }
                            $arr['install'] = true;
                            // 跳转配置首页
                            $arr['jump_url'] = cfg('site_url') . '/v20/public/platform/#/property/property.iframe/property_enterprise_weixin';
                        }
                    } elseif (!empty($qyResult) &&!empty($authCorpInfo)&& 'register'==$type) {
                        // 直接记录即可 暂无处理
                        // 注册包含快鲸的企业微信——用快鲸，连接客户
                        $arr['register'] = true; // 返回注册信息失败
                        $arr['jump_url'] = ''; // 注册成功后跳转链接（注册页面暂时不需要跳转 用户物业后台）
                        $arr['randomNumber'] = $randomNumber;
                        if ($business_type=='property' && $property_id) {
                            $now_time = time();
                            $data = $authCorpInfo;
                            $providerConfig = $param_result['provider_config'];
                            if (empty($providerConfig)) {
                                $data_config = $this->getDataConfig();
                                $providerConfig['SuiteID'] = $data_config['enterprise_wx_provider_suiteid'];
                            }
                            unset($data['errcode']);
                            unset($data['errmsg']);
                            $data['bind_type'] = 0;
                            $data['bind_id'] = $property_id;
                            if ($providerConfig && $providerConfig['SuiteID']) {
                                $data['wx_provider_suiteid'] = $providerConfig['SuiteID'];
                            }
                            if ($qyResult['TimeStamp'] && $qyResult['TimeStamp']) {
                                $data['add_time'] = $qyResult['TimeStamp'];
                            } else {
                                $data['add_time'] = time();
                            }
                            $now_time=time();
                            $data['expires_in'] = $authCorpInfo['expires_in'] + $now_time;
                            $data['bind_type'] = 0;
                            $data['bind_id'] = $property_id;
                            $auth_corp_info = isset($data['auth_corp_info']) && $data['auth_corp_info'] ? $data['auth_corp_info'] : [];
                            $auth_info = $authCorpInfo['auth_info'];
                            if ($auth_info && !empty($auth_info['agent'])) {
                                $agent = reset($auth_info['agent']);
                                $data['agentid'] = $agent['agentid'];
                                $data['corp_square_logo_url'] = $agent['square_logo_url']?$agent['square_logo_url']:'';
                            }
                            if (isset($login_corpid) && $login_corpid) {
                                $data['corpid'] = $login_corpid;
                            }
                            if ($auth_corp_info) {
                                $data['corp_name'] = $auth_corp_info['corp_name']? $auth_corp_info['corp_name']:'';
                                $data['corp_type'] = $auth_corp_info['corp_type']?$auth_corp_info['corp_type']:'';
                                $data['subject_type'] = $auth_corp_info['subject_type']?$auth_corp_info['subject_type']:'';
                                $data['verified_end_time'] = $auth_corp_info['verified_end_time']?$auth_corp_info['verified_end_time']:'';
                                $data['corp_wxqrcode'] = $auth_corp_info['corp_wxqrcode']?$auth_corp_info['corp_wxqrcode']:'';
                                $data['auth_corp_info'] = serialize($auth_corp_info);
                                $data['corp_user_max'] = $auth_corp_info['corp_user_max']?$auth_corp_info['corp_user_max']:0;
                                $data['corp_agent_max'] = $auth_corp_info['corp_agent_max']?$auth_corp_info['corp_agent_max']:0;
                                $data['corp_full_name'] = $auth_corp_info['corp_full_name']?$auth_corp_info['corp_full_name']:0;
                            }
                            $data['auth_info'] = serialize($auth_info);
                            $data['userid'] = $authCorpInfo['auth_user_info']['userid']?$authCorpInfo['auth_user_info']['userid']:'';
                            $data['name'] = $authCorpInfo['auth_user_info']['name']?$authCorpInfo['auth_user_info']['name']:'';
                            $data['avatar'] = $authCorpInfo['auth_user_info']['avatar']?$authCorpInfo['auth_user_info']['avatar']:'';
                            $data['auth_user_info'] = serialize($authCorpInfo['auth_user_info']);
                            $data['add_time'] = time();
                            $data['last_time'] = time();
                            $err = false;
                            if ($qyResult['AuthCorpId'] && $qyResult['AuthCorpId']) {
                                $data['corpid'] = $qyResult['AuthCorpId'];
                                if ($is_bind = $db_house_enterprise_wx_bind->getOne(array('corpid' => $data['corpid'], 'bind_type' => 0))) {
                                    if ($is_bind['bind_id'] != $property_id) {
                                        fdump_api(['qyGuideAbout-line查询数据和对应类型不符:'.__LINE__, $qywx_guide_about,$randomNumber,$type,$business_type,$_POST,$data,$is_bind],'qyweixinConfig/qyGuideAboutErrLog',1);
                                        $arr['msg'] = '该企业微信已在其他物业完成绑定，无法绑定到当前物业!';
                                        $err = true;
                                    }
                                }
                            }
                            if (isset($data['register_code_info'])) {
                                unset($data['register_code_info']);
                            }
                            if (!$err &&$is_bind = $db_house_enterprise_wx_bind->getOne(array('bind_id' => $property_id, 'bind_type' => 0))) {
                                $db_house_enterprise_wx_bind->save_one(array('bind_id' => $property_id, 'bind_type' => 0),$data);
                                $wx_bind_id = $is_bind['pigcms_id'];
                            }elseif (!$err) {
                                $wx_bind_id = $db_house_enterprise_wx_bind->add($data);
                            } elseif ($is_bind['bind_id'] == $property_id) {
                                $db_house_enterprise_wx_bind->save_one(array('pigcms_id' => $is_bind['pigcms_id']),$data);
                                $wx_bind_id = $is_bind['pigcms_id'];
                            }
                            if ($wx_bind_id) {
                                $save_data['wx_bind_id'] = $wx_bind_id;
                                if ($qywx_guide_about['type']=='qyRegisterPost') {
                                    $dataSet = array(
                                        'site_url' => cfg('site_url'),
                                        'siteIp' => $siteIp,
                                        'domainName' => $domainName,
                                        'ip' => $ip,
                                        'randomNumber' => $randomNumber,
                                        'property_id' => $property_id,
                                        'wx_bind_id' => $wx_bind_id,
                                    );
                                    $qyGuideSetUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyGuideSet";
                                    http_request($qyGuideSetUrl,'POST', $dataSet);
                                }
                            }
                            // 跳转配置首页
                            $arr['jump_url'] = cfg('site_url') . '/v20/public/platform/#/property/property.iframe/property_enterprise_weixin';
                        }
                    }
                    $db_village_qywx_guide_about->saveOne($where_qywx_guide_about,$save_data);
                }
                return $arr;
            }
    }

    public function saveProperty($data){
        $db_village_qywx_guide_about=new VillageQywxGuideAbout();
        $qywx_guide_about=$db_village_qywx_guide_about->getOne(['randomNumber'=>$data['randomNumber'], 'business_type' => 'property']);
        if (empty($qywx_guide_about)){
            return array('errcode' => 1001, 'errmsg' => '查询对象不存在');
        }else{
           $res= $db_village_qywx_guide_about->saveOne(['randomNumber'=>$data['randomNumber'], 'business_type' => 'property'],['business_id'=>$data['propertyId']]);
          if ($res){
              return array('errcode' => 0, 'errmsg' => '');
          }else{
              return array('errcode' => 1001, 'errmsg' => '物业id更新失败');
          }
        }
    }

    /**
     * 企业微信登录
     * @author:zhubaodi
     * @date_time: 2021/8/4 14:16
     */
    public function qyLogin($property_id=0,$qyLoginId=0,$business_type='property') {
//        $service_provider_common_set = cfg('service_provider_common_set');
        $service_provider_common_set = 1;
        $site_url = cfg('site_url');
        $db_config=new Config();
        $db_village_qywx_guide_about = new VillageQywxGuideAbout();
        fdump_api(['获取企业微信配置'.__LINE__,$service_provider_common_set],'qyLogin111',1);
        if (1==$service_provider_common_set) {
            $where_enterprise_wx_corpid = [
                'name' => 'enterprise_wx_corpid'
            ];
            $corpid = $db_config->getOne($where_enterprise_wx_corpid,'value');
            $corpid = trim($corpid['value']);
            fdump_api(['获取企业微信配置'.__LINE__,$corpid],'qyLogin111',1);
            if (empty($corpid)) {
                fdump_api(['qyLogin-line请配置企业微信ID:'.__LINE__, $property_id,$qyLoginId,$business_type],'qyweixin/qyLoginErrLog',1);
                return array('errcode' => 1001, 'errmsg' => '请配置企业微信ID');
            }

            //$db_village_qywx_guide_about = M('Village_qywx_guide_about');
           //  $db_village_qywx_guide_about = new VillageQywxGuideAbout();
            if ($property_id) {
                $where = [
                    'is_del' => 0,
                    'from' => 0,
                    'business_type' => $business_type,
                    'business_id' => $property_id,
                    'type' => 'qyLoginSelf',
                    'wx_bind_id' => 0
                ];
            } elseif ($qyLoginId) {
                $property_id = 0;
                $where = [
                    'is_del' => 0,
                    'from' => 0,
                    'business_type' => $business_type,
                    'randomNumber' => $qyLoginId,
                    'type' => 'qyLoginSelf',
                    'wx_bind_id' => 0
                ];
            }
            if (empty($where)) {
                $qywx_guide_about = [];
            } else {
                $qywx_guide_about = $db_village_qywx_guide_about->getOne($where);
            }
            fdump_api(['获取企业微信配置'.__LINE__,$qywx_guide_about],'qyLogin111',1);
            if ($qywx_guide_about) {
                $guide_about_data = [];
                $type = $qywx_guide_about['type'];
                if (!$qyLoginId && !$qywx_guide_about['randomNumber']) {
                    $qyLoginId = $type . date('YmdHi') . createRandomStr(5).$qywx_guide_about['from'];
                    $guide_about_data['randomNumber'] = $qyLoginId;
                } elseif ($qyLoginId && !$qywx_guide_about['randomNumber']) {
                    $guide_about_data['randomNumber'] = $qyLoginId;
                }
                if (!$qyLoginId && $qywx_guide_about['randomNumber']) {
                    $qyLoginId = $guide_about_data['randomNumber'];
                }

                if ($property_id && !$qywx_guide_about['business_id']) {
                    $guide_about_data['business_id'] = $property_id;
                }
                if (!empty($guide_about_data)) {
                    $guide_about_data['update_time'] = time();
                    $save_id = $db_village_qywx_guide_about->saveOne($where,$guide_about_data);
                    if ($save_id!==false) {
                    } else {
                        fdump_api(['qyLoginId-lineSelf添加记录失败:'.__LINE__, $property_id,$qyLoginId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyInstallErrLog',1);
                        return array('errcode' => 0, 'errmsg' => '添加记录失败');
                    }
                }
                $add_id = $qywx_guide_about['id'];
            } else {
                $type = 'qyLoginSelf';
                $guide_about_data = [
                    'is_del' => 0,
                    'business_type' => $business_type,
                    'type' => $type,
                    'from' => 0,
                    'add_time' => time()
                ];
                if (!$qyLoginId) {
                    // 长串保持为20位
                    $qyLoginId = $type . date('YmdHi') . createRandomStr(5).$guide_about_data['from'];
                }
                if ($qyLoginId) {
                    $guide_about_data['randomNumber'] = $qyLoginId;
                }
                if ($property_id) {
                    $guide_about_data['business_id'] = $property_id;
                }
                $add_id = $db_village_qywx_guide_about->addOne($guide_about_data);
                if (empty($add_id)) {
                    fdump_api(['qyLogin-lineSelf添加记录失败:'.__LINE__, $property_id,$qyLoginId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyInstallErrLog',1);
                    return array('errcode' => 0, 'errmsg' => '添加记录失败');
                }
            }
            $state = "{$qyLoginId}";
            $redirect_url = $site_url . "/index.php?g=Index&c=Qywx&a=auth_login";
            $redirect_url = urlencode($redirect_url);
            fdump_api(['获取企业微信配置'.__LINE__,$corpid,$redirect_url,$state],'qyLogin111',1);
            // 第三方服务商在自己的网站中放置“企业微信应用授权”的入口，引导企业微信管理员进入应用授权页。授权页网址为:
            // https://open.work.weixin.qq.com/wwopen/sso/3rd_qrConnect?appid=ww100000a5f2191&redirect_uri=http%3A%2F%2Fwww.oa.com&state=web_login@gyoss9&usertype=admin
//            $bind_url ="https://open.work.weixin.qq.com/wwopen/sso/3rd_qrConnect?appid={$corpid}&redirect_uri={$redirect_url}&state={$state}&usertype=admin";
            // https://login.work.weixin.qq.com/wwlogin/sso/login?login_type=ServiceApp&appid=APPID&redirect_uri=REDIRECT_URI&state=STATE
            $enterprise_login_suiteId    = cfg('enterprise_login_suiteId');
            $bind_url ="https://login.work.weixin.qq.com/wwlogin/sso/login?login_type=ServiceApp&appid={$enterprise_login_suiteId}&redirect_uri={$redirect_url}&state={$state}";
            $arr = [
                'errcode' => 0,
                'errmsg' => '',
            ];
            $arr['url'] = $bind_url;
            fdump_api(['获取企业微信配置'.__LINE__,$bind_url],'qyLogin111',1);
            $provider_config = [
                'corpid' => $corpid,
            ];
            $arr['provider_config'] = $provider_config;
            if ($add_id) {
                $trusted_ip = $_SERVER['SERVER_ADDR'];
                $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
                if ($siteIp=='127.0.0.1' && $trusted_ip) {
                    $siteIp = $trusted_ip;
                }
                $site_url = cfg('site_url');
                $domainName = str_replace('http://','',$site_url);
                $domainName = str_replace('https://','',$domainName);
                $ip = get_client_ip(0);
                $data = array(
                    'site_url' => cfg('site_url'),
                    'siteIp' => $siteIp,
                    'domainName' => $domainName,
                    'ip' => $ip,
                    'qyLoginId' => $qyLoginId,
                    'property_id' => $property_id,
                );
                $guide_about_data = [];
                $guide_about_data['param'] = serialize($data);
                $guide_about_data['param_url'] = '';
                $guide_about_data['param_result'] = serialize($arr);
                $guide_about_data['update_time'] = time();
                $where = [
                    'id' => $add_id
                ];
                $save_id = $db_village_qywx_guide_about->saveOne($where,$guide_about_data);
                if ($save_id!==false) {
                } else {
                    fdump_api(['qyLogin-lineSelf添加记录失败:'.__LINE__, $property_id,$qyLoginId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyRegisterErrLog',1);
                    return array('errcode' => 0, 'errmsg' => '添加记录失败');
                }
            }
            unset($arr['provider_config']);
            $arr['randomNumber']=$qyLoginId;
            $arr['login_url']=$this->post_url ."index.php?g=Index&c=Login&a=login_agreement&randomNumber=".$arr['randomNumber'];
            $arr['login_url']='';
            return $arr;
        } else {
            $trusted_ip = $_SERVER['SERVER_ADDR'];
            $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
            if ($siteIp=='127.0.0.1' && $trusted_ip) {
                $siteIp = $trusted_ip;
            }
            $site_url = cfg('site_url');
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
            $ip = get_client_ip(0);
            $data = array(
                'site_url' => cfg('site_url'),
                'siteIp' => $siteIp,
                'domainName' => $domainName,
                'ip' => $ip,
                'qyLoginId' => $qyLoginId,
                'property_id' => $property_id,
            );
            $qyLoginUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyLogin";
            $login_msg = http_request($qyLoginUrl,'POST', $data);
            if ($login_msg[0]==200){
                $login_msg=json_decode($login_msg[1],true);
            }
            if ($login_msg['url']) {
                $qyLoginId = $login_msg['qyLoginId'];
               // $db_village_qywx_guide_about = M('Village_qywx_guide_about');
                // 成功返回了 连接 做记录
                if ($property_id) {
                    $where = [
                        'is_del' => 0,
                        'from' => 0,
                        'business_type' => $business_type,
                        'business_id' => $property_id,
                        'type' => 'qyLoginPost',
                        'site_url' => $site_url,
                        'qyResult' => '',
                    ];
                } elseif ($qyLoginId) {
                    $property_id = 0;
                    $where = [
                        'is_del' => 0,
                        'from' => 0,
                        'business_type' => $business_type,
                        'randomNumber' => $qyLoginId,
                        'type' => 'qyLoginPost',
                        'site_url' => $site_url,
                        'qyResult' => '',
                    ];
                }
                $qywx_guide_about = $db_village_qywx_guide_about->getOne($where);
                if ($qywx_guide_about) {
                    $guide_about_data = [];
                    if ($qyLoginId && !$qywx_guide_about['randomNumber']) {
                        $guide_about_data['randomNumber'] = $qyLoginId;
                    }
                    if ($property_id && !$qywx_guide_about['business_id']) {
                        $guide_about_data['business_id'] = $property_id;
                    }
                    if ($siteIp && $siteIp!=$qywx_guide_about['siteIp']) {
                        $guide_about_data['siteIp'] = $siteIp;
                    }
                    if ($ip && $ip!=$qywx_guide_about['ip']) {
                        $guide_about_data['ip'] = $ip;
                    }
                    if ($domainName && $domainName!=$qywx_guide_about['domainName']) {
                        $guide_about_data['domainName'] = $domainName;
                    }
                    $param = serialize($data);
                    if ($param && $param!=$qywx_guide_about['param']) {
                        $guide_about_data['param'] = $param;
                    }
                    if ($qyLoginUrl && $qyLoginUrl!=$qywx_guide_about['param_url']) {
                        $guide_about_data['param_url'] = $qyLoginUrl;
                    }
                    $param_result = serialize($login_msg);
                    if ($param_result && $param_result!=$qywx_guide_about['param_result']) {
                        $guide_about_data['param_result'] = $param_result;
                    }
                    if (!empty($guide_about_data)) {
                        $guide_about_data['update_time'] = time();
                        $save_id = $db_village_qywx_guide_about->saveOne($where,$guide_about_data);
                        if ($save_id!==false) {
                        } else {
                            fdump_api(['qyLogin-linePost添加记录失败:'.__LINE__, $property_id,$qyLoginId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyInstallIdErrLog',1);
                            return array('errcode' => 0, 'errmsg' => '添加记录失败');
                        }
                    }
                } else {
                    $type = 'qyLoginPost';
                    $guide_about_data = [
                        'is_del' => 0,
                        'business_type' => $business_type,
                        'type' => $type,
                        'from' => 0,
                        'business_id' => $property_id,
                        'site_url' => $site_url?$site_url:'',
                        'siteIp' => $siteIp?$siteIp:'',
                        'ip' => $ip?$ip:'',
                        'domainName' => $domainName?$domainName:'',
                        'add_time' => time()
                    ];
                    if ($qyLoginId) {
                        $guide_about_data['randomNumber'] = $qyLoginId;
                    }
                    if ($property_id) {
                        $guide_about_data['business_id'] = $property_id;
                    }
                    $guide_about_data['param'] = serialize($data);
                    $guide_about_data['param_url'] = $qyLoginUrl;
                    $guide_about_data['param_result'] = serialize($login_msg);
                    $add_id = $db_village_qywx_guide_about->addOne($guide_about_data);
                    if (!$add_id) {
                        fdump_api(['qyLogin-linePost添加记录失败:'.__LINE__, $property_id,$qyLoginId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixin/qyInstallIdErrLog',1);
                        return array('errcode' => 0, 'errmsg' => '添加记录失败');
                    }
                }
            } else {
               fdump_api(['qyRegister-linePost请求返回异常:'.__LINE__, $property_id,$qyLoginId,$business_type,$qyLoginUrl,$data,$login_msg],'qyweixin/qyInstallIdErrLog',1);
            }
            $login_msg['randomNumber']=$login_msg['qyLoginId'];
            $login_msg['login_url']=$this->post_url ."index.php?g=Index&c=Login&a=login_agreement&randomNumber=".$login_msg['randomNumber'];
           //  print_r($login_msg);exit;
            return $login_msg;
        }
    }

    public function getDataConfig()
    {
//        $service_provider_common_set = cfg('service_provider_common_set'); 
        $service_provider_common_set = 1;
        $data_config = [];
        if (1==$service_provider_common_set) {
            $data_config['enterprise_wx_corpid'] =cfg('enterprise_wx_corpid');
            $data_config['enterprise_wx_provider_encodingaeskey'] = cfg('enterprise_wx_provider_encodingaeskey');
            $data_config['enterprise_wx_provider_secret'] = cfg('enterprise_wx_provider_secret');
            $data_config['enterprise_wx_provider_suiteid'] = cfg('enterprise_wx_provider_suiteid');
            $data_config['enterprise_wx_provider_token'] = cfg('enterprise_wx_provider_token');
            $data_config['service_provider_secret'] = cfg('service_provider_secret');
            $data_config['service_provider_token'] = cfg('service_provider_token');
            $data_config['service_provider_encodingaeskey'] = cfg('service_provider_encodingaeskey');
            $data_config['service_template_id'] = cfg('service_template_id');
            $where_suite_ticket = [
                'name' => 'enterprise_wx_suite_ticket'
            ];
            $db_config=new config();
            $SuiteTicket = $db_config->getOne($where_suite_ticket,'value');
            $SuiteTicket = trim($SuiteTicket['value']);
            $data_config['enterprise_wx_suite_ticket'] = $SuiteTicket;
        } else {
            $trusted_ip = $_SERVER['SERVER_ADDR'];
            $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
            if ($siteIp=='127.0.0.1' && $trusted_ip) {
                $siteIp = $trusted_ip;
            }
            $site_url = cfg('site_url');
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
            $ip = get_client_ip(0);
            $data = array(
                'site_url' => cfg('site_url'),
                'siteIp' => $siteIp,
                'domainName' => $domainName,
                'ip' => $ip,
            );
            $getDataConfigUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=getDataConfig";
            $getDataConfig =http_request($getDataConfigUrl,'POST', $data);
            $getDataConfig=json_decode($getDataConfig[1],true);
            if (isset($getDataConfig['errcode']) && $getDataConfig['errcode']) {
                return [];
            }
            $data_config = $getDataConfig;
        }
        return $data_config;
    }
}