<?php
/**
 * 登录
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/23 13:31
 */

namespace app\community\controller\manage_api\v1;

//use app\BaseController;
use app\common\model\db\Plugin;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\manage_api\BaseController;
use app\community\model\db\HouseVillage;
use app\community\model\service\AreaStreetService;
use app\community\model\service\CommunityLoginService;
use app\community\model\service\HouseAppJpushInfoService;
use app\community\model\service\HouseNewPorpertyService;
use app\community\model\service\HousePropertyService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\ManageAppLoginService;
use app\community\model\service\OrganizationStreetService;
use app\community\model\service\PackageOrderService;
use app\community\model\service\PropertyAdminService;
use app\social_activity\model\service\SocialActivityAdminService;
use think\facade\Request;
use token\Token;

class LoginController extends BaseController{

    public $login_rolename=['1'=>'街道工作人员','3'=>'物业总管理员','4'=>'物业普通管理员','5'=>'小区物业管理人员','6'=>'小区物业工作人员'];

    /**
     * 社区管理APP 登录页面需要配置参数
     * @author: wanziyang
     * @date_time: 2020/4/23 13:31
     * @return mixed
     */
    public function config() {
        // 初始化 社区管理App 业务层
        $service_login = new ManageAppLoginService();
        // 社区管理APP 登录页面需要配置参数
        // 登录角色返回
        $app_version = $this->request->param('app_version','','int');
        if ($app_version<11000) {
            // 该版本以下  只返回一个角色
            $login_app_role = [
                ['type' => 5, 'name' => '小区物业管理人员']
            ];
        } else {
            $login_app_role = $service_login->login_app_role();
        }
        $account_cancellation_tpis='确定要注销当前账号？注销后不可恢复。';
        $version_code = $this->request->param('version_code','','int');
        if ($version_code>1500) {
            $login_role = [
                ['type' => 1, 'name' => '街道工作人员'],
//            ['type' => 2, 'name' => '社区'],
                ['type' => 3, 'name' => '物业总管理员'],
                ['type' => 4, 'name' => '物业普通管理员'],
                ['type' => 5, 'name' => '小区物业管理人员'],
                ['type' => 6, 'name' => '小区物业工作人员']
            ];
        } else {
            $login_role = $service_login->login_role();
        }
	    $plugin = Plugin::where('plugin_label','social_activity')->where('status',1)->find();
		if($plugin) {
			$login_role[] = [
				'type' => 'social_activity', 'name' => '社群活动管理员'
			];
		}

        $arr = [
            'login_role' => $login_role,
            'login_app_role' => $login_app_role
        ];
        $uid        = $this->request->log_uid;
        $login_role_v = $this->request->log_extends;
        $service_community_login =new CommunityLoginService();
        $loginTicket = $this->request->param('loginTicket');
        if ($loginTicket) {
            $login_arr = $service_community_login->getLoginSignInfo($loginTicket);
            if (isset($login_arr['ticket']) && $login_arr['ticket'] && isset($login_arr['member_id']) && $login_arr['member_id'] && isset($login_arr['extends']) && $login_arr['extends']) {
                $ticket     = $login_arr['ticket'];
                $uid        = $login_arr['member_id'];
                $login_role_v = $login_arr['extends'];
            }
        }
        $arr['login_role_v']=$login_role_v;
        if($login_role_v==5){
            $account_cancellation_tpis='确定要注销当前账号？注销后当前账号关联的小区物业工作人员身份也会注销不可恢复。';
        }else if($login_role_v==6){
            $account_cancellation_tpis='确定要注销当前账号？注销后当前账号关联的小区物业管理人员身份也会注销不可恢复。';
        }
        $arr['account_cancellation_tpis']=$account_cancellation_tpis;
        $arr['have_account_cancellation_func']=1;
        $site_url = cfg('site_url');
        $deviceId = $this->request->param('Device-Id','','trim');
        $appConfig = $service_login->get_appapi_app_config();

        foreach($appConfig as $k=>$v){
            if(in_array($k, ['v_manerge_ios_url', 'village_manager_ios_version','village_manager_icp', 'village_manager_ios_version_code', 'v_manerge_android_v', 'v_manerge_android_url', 'v_manerge_android_vcode', 'v_manerge_android_vdesc', 'village_privacy_policy'])){
                $arr['appConfig'][$k]   =   nl2br($v);
            }
        }
        if (!isset($arr['appConfig']) || !$arr['appConfig']) {
            $arr['appConfig'] = (object)[];
        }
        $register_agreement = cfg('register_agreement');
        if ($register_agreement) {
            // 用户协议
            $arr['register_agreement'] = $site_url.'/wap.php?c=Login&a=register_agreement';
            $arr['register_agreement_md5'] = md5($register_agreement);
        } elseif (!$register_agreement) {
            $arr['register_agreement'] = '';
            $arr['register_agreement_md5'] = '';
        }

        if (isset($appConfig['village_privacy_policy'])) {
            $privacy_policy = $appConfig['village_privacy_policy'];
            // 社区管理APP隐私政策
            if ($privacy_policy) {
                $arr['privacy_policy'] = $site_url.'/wap.php?c=Login&a=village_privacy_policy';
                $arr['privacy_policy_md5'] = md5($privacy_policy);
            } elseif (!$privacy_policy) {
                $arr['privacy_policy'] = '';
                $arr['privacy_policy_md5'] = '';
            }
        }
        $arr['icp_url']='https://beian.miit.gov.cn/#/Integrated/index';

        $community_jpush_appkey=cfg('community_jpush_appkey');
        $arr['community_jpush_appkey']=$community_jpush_appkey ? trim($community_jpush_appkey):'';
        $configCustomizationService= new ConfigCustomizationService();
        $wsxdsj_net_customized=$configCustomizationService->getWSXCustomizedOpen();
        $is_offline_smart_qrcode=$wsxdsj_net_customized ? 1:0;
        $arr['is_offline_smart_qrcode']=$is_offline_smart_qrcode;
        return api_output(0,$arr);
    }

    /**
     * 登录校验
     * @param 传参
     * array (
     *  'login_role' => '0',// 1 街道 2 社区 3 物业总管理员  4 物业普通管理员 5 小区工作人员 不传值默认0
     *  'login_account' => 'login_account', // 登录账号
     *  'pwd' => '对应账号的密码',
     *  'village_id'=> '如果同一个账号存在多个小区后端返回前端选择',
     *  'ticket' => '', 登录标识 如果是已经登录的上传，未登录的不传  建议前端统一请求接口处理
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @return \think\response\Json
     *@author: wanziyang
     * @date_time: 2020/4/23 15:22
     */
    public function check($cflag='') {
        // 初始化 社区管理App 业务层
        $service_login           = new ManageAppLoginService();
        //  初始化 社区 业务层
        $service_house_village   = new HouseVillageService();
        $service_community_login =new CommunityLoginService();
        $uid        = $this->request->log_uid;
        $login_role = $this->request->log_extends;
        $takeEffectTimeJudge = false;
        $role_list=[];
        $houseAppJpushInfoService = new HouseAppJpushInfoService();
        $loginTicket = $this->request->param('loginTicket');
        $jpush_registration_id = $this->request->post('jpush_registration_id','','trim');  //极光注册信息
        $platform_type = $this->request->post('platform_type','','trim'); //极光注册信息
        if ($loginTicket) {
            $login_arr = $service_community_login->getLoginSignInfo($loginTicket);
            if (isset($login_arr['ticket']) && $login_arr['ticket'] && isset($login_arr['member_id']) && $login_arr['member_id'] && isset($login_arr['extends']) && $login_arr['extends']) {
                $ticket     = $login_arr['ticket'];
                $uid        = $login_arr['member_id'];
                $login_role = $login_arr['extends'];
            }
        }
        $config = [
            'set_house_alias'=>(customization('set_house_alias')==1 ? 1 : 0),
            'house_name'=>cfg('house_name')?:L_('小区'),
            'single_name'=>cfg('single_name')?:L_('楼栋'),
            'floor_name'=>cfg('floor_name')?:L_('单元'),
            'room_name'=>cfg('room_name')?:L_('房间'),
        ];
        if ($uid && intval($uid)>0 && $login_role) {
            if (!isset($ticket) || !$ticket) {
                $ticket = Token::getToken();
            }
            $account_cancellation_tpis='确定要注销当前账号？注销后不可恢复。';
            $have_account_cancellation_func=1;
            if (1== $login_role) {
                // 街道登录
                //  初始化 社区 业务层
                $serviceOrganizationStreet = new OrganizationStreetService();
                $login_info = $serviceOrganizationStreet->getMemberInfo($uid);
                if (empty($login_info['info'])) {
                    return api_output(1002,[],'未登陆！');
                }
                $login_info = $login_info['info'];
                // 获得门密码
                $lockPwd = '';
                $role_list=$service_community_login->getRoleList($login_info['work_account']);
                //底部菜单
                $footer = [];
                // 给予登录用户名
                $login_info['login_name'] = $login_info['work_name'] ? $login_info['work_name'] : $login_info['work_account'];
                $arr = array(
                    'role_list'=>$role_list,
                    'login_role'	=>	$login_role,
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                    'config'    => $config,
                    'account_cancellation_tpis'=>$account_cancellation_tpis,
                    'have_account_cancellation_func'=>$have_account_cancellation_func,
                );
                if($cflag=='jpush_registration' && $jpush_registration_id){
                    $saveJpushArr=array('user_table_type'=>'area_street_workers','jpush_registration_id'=>$jpush_registration_id);
                    $saveJpushArr['platform']=$platform_type;
                    $saveJpushArr['login_role']=$login_role;
                    $saveJpushArr['user_id']=$login_info['worker_id'];
                    $saveJpushArr['street_id']=$login_info['area_id'];
                    $whereJpushArr=array('user_table_type'=>'area_street_workers','jpush_registration_id'=>$jpush_registration_id,'user_id'=>$login_info['worker_id'],'platform'=>$platform_type);
                    $houseAppJpushInfoService->saveAndUpdateAppJpushInfo($whereJpushArr,$saveJpushArr);
                }
                return api_output(0,$arr);
            }
            elseif (11== $login_role || 21 == $login_role) {
                // 街道和社区登录保留 值往后延
                // 街道登录
                //  初始化 社区 业务层
                $service_area_street = new AreaStreetService();
                $where = [
                    'area_id' => $uid
                ];
                $login_info = $service_area_street->getAreaStreet($where);
                if (empty($login_info)) {
                    return api_output(1002,[],'未登陆！');
                }
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
                    'login_role'	=>	$login_role,
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                    'config'    => $config,
                    'account_cancellation_tpis'=>$account_cancellation_tpis,
                    'have_account_cancellation_func'=>$have_account_cancellation_func,
                );
                if($cflag=='jpush_registration' && $jpush_registration_id){
                    $saveJpushArr=array('user_table_type'=>'area_street','jpush_registration_id'=>$jpush_registration_id);
                    $saveJpushArr['platform']=$platform_type;
                    $saveJpushArr['login_role']=$login_role;
                    $saveJpushArr['user_id']=$login_info['area_id'];
                    $saveJpushArr['street_id']=$login_info['area_id'];
                    $whereJpushArr=array('user_table_type'=>'area_street','jpush_registration_id'=>$jpush_registration_id,'user_id'=>$login_info['area_id'],'platform'=>$platform_type);
                    $houseAppJpushInfoService->saveAndUpdateAppJpushInfo($whereJpushArr,$saveJpushArr);
                }
                return api_output(0,$arr);
            }
            elseif (3 == $login_role) {
                $condition_house_property = [
                    'id' => $uid
                ];
                $house_property = $service_house_village->get_house_property_where($condition_house_property);
                if (empty($house_property)) {
                    return api_output(1002,[],'未登陆！');
                }
                $servicePackageOrder = new PackageOrderService();
                $package_msg = $servicePackageOrder->judgeOrderEmploy($house_property['id']);
                if ($package_msg && array_key_exists('code', $package_msg) && $package_msg['code'] != 1) {
                    return api_output(1003, [], $package_msg['msg']);
                }
                // 权限相关处理  给予全部权限
                $admin_menus = $service_house_village->getHouseMenuNew(array('status'=>1),'id');
                foreach ($admin_menus as $value) {
                    $power[] = $value['id'];
                }
                //底部菜单
                $footer = $service_house_village->getFooter($power);
                $login_info = $house_property;
                // 获得门密码
                $lockPwd = '';
                $role_list=$service_community_login->getRoleList($house_property['account']);
                // 给予登录用户名
                $login_info['login_name'] = $house_property['property_name'] ? $house_property['property_name'] : $house_property['account'];
                $login_info['property_id'] = $house_property['id'];
                if (isset($house_property['id']) && $house_property['id']) {
                    $serviceHouseNewPorperty = new HouseNewPorpertyService();
                    $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($house_property['id']);
                }
                $arr = array(
                    'role_list'=>$role_list,
                    'login_role'	=>	$login_role,
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                    'config'    => $config,
                    'account_cancellation_tpis'=>$account_cancellation_tpis,
                    'have_account_cancellation_func'=>$have_account_cancellation_func,
                );
                if($cflag=='jpush_registration' && $jpush_registration_id){
                    $saveJpushArr=array('user_table_type'=>'house_property','jpush_registration_id'=>$jpush_registration_id);
                    $saveJpushArr['platform']=$platform_type;
                    $saveJpushArr['login_role']=$login_role;
                    $saveJpushArr['user_id']=$login_info['property_id'];
                    $saveJpushArr['property_id']=$login_info['property_id'];
                    $whereJpushArr=array('user_table_type'=>'house_property','jpush_registration_id'=>$jpush_registration_id,'user_id'=>$login_info['property_id'],'platform'=>$platform_type);
                    $houseAppJpushInfoService->saveAndUpdateAppJpushInfo($whereJpushArr,$saveJpushArr);
                }
                return api_output(0,$arr);
            }
            elseif (4 == $login_role) {
                $condition_house_property = [
                    'id' => $uid
                ];
                $house_property = $service_login->get_property_admin_where($condition_house_property);
                if (empty($house_property)) {
                    return api_output(1002,[],'未登陆！');
                }
                $servicePackageOrder = new PackageOrderService();
                $package_msg = $servicePackageOrder->judgeOrderEmploy($house_property['property_id']);
                if($package_msg && $package_msg['code'] != 1)
                {
                    return api_output(1003,[],$package_msg['msg']);
                }
                // 权限相关处理  给予全部权限
                $admin_menus = $service_house_village->getHouseMenuNew(array('status'=>1),'id');
                foreach ($admin_menus as $value) {
                    $power[] = $value['id'];
                }
                //底部菜单
                $footer = $service_house_village->getFooter($power);
                $login_info = $house_property;
                // 获得门密码
                $lockPwd = '';
                $role_list=$service_community_login->getRoleList($house_property['account']);
                // 给予登录用户名
                $login_info['login_name'] = $house_property['realname'] ? $house_property['realname'] : $house_property['account'];
                if (isset($house_property['property_id']) && $house_property['property_id']) {
                    $serviceHouseNewPorperty = new HouseNewPorpertyService();
                    $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($house_property['property_id']);
                }
                $arr = array(
                    'role_list'=>$role_list,
                    'login_role'	=>	$login_role,
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                    'config'    => $config,
                    'account_cancellation_tpis'=>$account_cancellation_tpis,
                    'have_account_cancellation_func'=>$have_account_cancellation_func,
                );
                if($cflag=='jpush_registration' && $jpush_registration_id){
                    $saveJpushArr=array('user_table_type'=>'property_admin','jpush_registration_id'=>$jpush_registration_id);
                    $saveJpushArr['platform']=$platform_type;
                    $saveJpushArr['login_role']=$login_role;
                    $saveJpushArr['user_id']=$login_info['id'];
                    $saveJpushArr['property_id']=$login_info['property_id'];
                    $saveJpushArr['wid']=$login_info['wid'];
                    $whereJpushArr=array('user_table_type'=>'property_admin','jpush_registration_id'=>$jpush_registration_id,'user_id'=>$login_info['id'],'platform'=>$platform_type);
                    $houseAppJpushInfoService->saveAndUpdateAppJpushInfo($whereJpushArr,$saveJpushArr);
                }
                return api_output(0,$arr);

            }
            elseif (5 == $login_role) {
                $condition_house = [
                    'id' => $uid
                ];
                $role = $service_login->get_house_admin_where($condition_house);
                if(empty($role)){
                    return api_output(1002,[],'未登陆！');
                }
                $village_where[] = ['village_id','=',$role['village_id']];
                $village_info = $service_house_village->getHouseVillageInfo($village_where,'property_id,village_name');
                //套餐过滤 2020/11/7 start
                $servicePackageOrder = new PackageOrderService();
                $package_msg = $servicePackageOrder->judgeOrderEmploy($village_info['property_id']);
                if($package_msg && $package_msg['code'] != 1)
                {
                    return api_output(1003,[],$package_msg['msg']);
                }
                //套餐过滤 2020/11/7 end
                //权限
                $power = explode(',',$role['menus']);
                //底部菜单
                $footer = $service_house_village->getFooter($power);

                // 获得门密码
                $login_info = $role;
                $lockPwd = $service_house_village->get_lock_pwd($login_info);
                $role_list=$service_community_login->getRoleList($role['account']);
                // 给予登录用户名
                $login_info['login_name'] = $role['realname'] ? $role['realname'] : $village_info['village_name'];
                $login_info['property_id'] = $village_info['property_id'];
                if (isset($login_info['property_id']) && $login_info['property_id']) {
                    $serviceHouseNewPorperty = new HouseNewPorpertyService();
                    $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($login_info['property_id']);
                }
                $account_cancellation_tpis='确定要注销当前账号？注销后当前账号关联的小区物业工作人员身份也会注销不可恢复。';
                $arr = array(
                    'role_list'=>$role_list,
                    'login_role'	=>	$login_role,
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                    'config'    => $config,
                    'account_cancellation_tpis'=>$account_cancellation_tpis,
                    'have_account_cancellation_func'=>$have_account_cancellation_func,
                );
                if($cflag=='jpush_registration' && $jpush_registration_id){
                    $saveJpushArr=array('user_table_type'=>'house_admin','jpush_registration_id'=>$jpush_registration_id);
                    $saveJpushArr['platform']=$platform_type;
                    $saveJpushArr['login_role']=$login_role;
                    $saveJpushArr['user_id']=$login_info['id'];
                    $saveJpushArr['wid']=$login_info['wid'];
                    $saveJpushArr['village_id']=$login_info['village_id'];
                    $whereJpushArr=array('user_table_type'=>'house_admin','jpush_registration_id'=>$jpush_registration_id,'user_id'=>$login_info['id'],'platform'=>$platform_type);
                    $houseAppJpushInfoService->saveAndUpdateAppJpushInfo($whereJpushArr,$saveJpushArr);
                }
                return api_output(0,$arr);
            }
            elseif (6 == $login_role) {
                $where = [
                    'wid'=>$uid,
                ];
                $now_house_worker = $service_house_village->getHouseWorker($where);
                if ($now_house_worker) {
                    // 给予登录用户名
                    $now_house_worker['login_name'] = $now_house_worker['name'] ? $now_house_worker['name'] : $now_house_worker['nickname'];
                } else{
                    return api_output(1002,[],'未登陆！');
                }
                $role_list=$service_community_login->getRoleList($now_house_worker['account']);
                if (isset($now_house_worker['property_id']) && $now_house_worker['property_id']) {
                    $serviceHouseNewPorperty = new HouseNewPorpertyService();
                    $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($now_house_worker['property_id']);
                }
                if(isset($now_house_worker['village_id']) && $now_house_worker['village_id'] && customization('set_house_alias')==1){
                    $villageConfigData = (new HouseVillage())->getOne($now_house_worker['village_id'],'single_name,floor_name,room_name');
                    $villageConfigData = $villageConfigData ? $villageConfigData->toArray() : [];
                    if($villageConfigData){
                        $config['single_name'] = $villageConfigData['single_name'] ?: $config['single_name'];
                        $config['floor_name'] = $villageConfigData['floor_name'] ?: $config['floor_name'];
                        $config['room_name'] = $villageConfigData['room_name'] ?: $config['room_name'];
                    }

                }
                $account_cancellation_tpis='确定要注销当前账号？注销后当前账号关联的小区物业管理人员身份也会注销不可恢复。';
                $arr = array(
                    'role_list'=>$role_list,
                    'login_role'	=>	$login_role,
                    'user'	=>	$now_house_worker,
                    'ticket'	=>	$ticket,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                    'config'    => $config,
                    'account_cancellation_tpis'=>$account_cancellation_tpis,
                    'have_account_cancellation_func'=>$have_account_cancellation_func,
                );
                if($cflag=='jpush_registration' && $jpush_registration_id){
                    $saveJpushArr=array('user_table_type'=>'house_worker','jpush_registration_id'=>$jpush_registration_id);
                    $saveJpushArr['platform']=$platform_type;
                    $saveJpushArr['login_role']=$login_role;
                    $saveJpushArr['user_id']=$now_house_worker['wid'];
                    $saveJpushArr['wid']=$now_house_worker['wid'];
                    $saveJpushArr['village_id']=$now_house_worker['village_id'];
                    $whereJpushArr=array('user_table_type'=>'house_worker','jpush_registration_id'=>$jpush_registration_id,'user_id'=>$now_house_worker['wid'],'platform'=>$platform_type);
                    $houseAppJpushInfoService->saveAndUpdateAppJpushInfo($whereJpushArr,$saveJpushArr);
                }
                return api_output(0,$arr);
            }
        }

        // 处理传值参数
        // 账号类型 1 街道工作人员 2 社区 3 物业总管理员  4 物业普通管理员 (5 小区工作人员) 改=>5 小区物业管理人员  新增:6 小区物业工作人员
        $login_role = $this->request->param('login_role',0,'intval');
        $login_account = $this->request->param('login_account','','strval');
        if (empty($login_account)) {
            return api_output(1001, [], '请填写登录账号');
        }
        $power = [];
        // 获取ip
        $ip = Request::ip();
        $pwd = $this->request->param('pwd','','strval');
        $device_id = $this->request->param('Device-Id', '', 'trim');
        $village_id = $this->request->param('village_id', '', 'intval');
	    if($login_role == 'social_activity') {
		    $adminService = new SocialActivityAdminService();
		    try {
			    $data = $adminService->login([
				    'account' => $login_account,
				    'password' => $pwd,
			    ]);
			    $loginData = [
					'config' => [],
					'role_list' => [],
				    'login_role' => 'social_activity',
				    'ticket' => $data['ticket'],
				    'user' => [
						'avatar' => cfg('site_url').'/static/images/user_avatar.jpg',
						'login_name' => $data['realname'],
					    'role_name' => $data['role_name'],
				    ]
			    ];
		    } catch (\Exception $e) {
			    return api_output_error(1003, $e->getMessage());
		    }
		    return api_output(0, $loginData, "登录成功");
	    }
        // 获取的密码md5
        $pwd = md5($pwd);
        $res=$service_community_login->login($login_account,$pwd,$village_id,$login_role,$takeEffectTimeJudge,$ip,$device_id);
       if (empty($res)){
           return api_output(1001, [], '账号或密码错误');
       }else{
           if ($res['error']==0){
               return api_output(0, $res['data']);
           }else{
               return api_output($res['error'], [], $res['msg']);
           }
       }

    }

    public function check_old() {

//        $token = Token::createToken(10086,4);

        // 初始化 社区管理App 业务层
        $service_login = new ManageAppLoginService();
        //  初始化 社区 业务层
        $service_house_village = new HouseVillageService();

        $uid = $this->request->log_uid;
        $login_role = $this->request->log_extends;
        $takeEffectTimeJudge = false;
        if ($uid && intval($uid)>0 && $login_role) {
            if (1== $login_role) {
                // 街道登录
                //  初始化 社区 业务层
                $serviceOrganizationStreet = new OrganizationStreetService();
                $login_info = $serviceOrganizationStreet->getMemberInfo($uid);
                if (empty($login_info['info'])) {
                    return api_output(1002,[],'未登陆！');
                }
                $login_info = $login_info['info'];
                // 获得门密码
                $lockPwd = '';

                //底部菜单
                $footer = [];
                // 给予登录用户名
                $login_info['login_name'] = $login_info['work_name'] ? $login_info['work_name'] : $login_info['work_account'];
                $ticket = Token::getToken();
                $arr = array(
                    'login_role'	=>	$login_role,
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                );
                return api_output(0,$arr);
            }
            elseif (11== $login_role || 21 == $login_role) {
                // 街道和社区登录保留 值往后延
                // 街道登录
                //  初始化 社区 业务层
                $service_area_street = new AreaStreetService();
                $where = [
                    'area_id' => $uid
                ];
                $login_info = $service_area_street->getAreaStreet($where);
                if (empty($login_info)) {
                    return api_output(1002,[],'未登陆！');
                }
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
                $ticket = Token::getToken();
                $arr = array(
                    'login_role'	=>	$login_role,
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                );
                return api_output(0,$arr);
            }
            elseif (3 == $login_role) {
                $condition_house_property = [
                    'id' => $uid
                ];
                $house_property = $service_house_village->get_house_property_where($condition_house_property);
                if (empty($house_property)) {
                    return api_output(1002,[],'未登陆！');
                }
                $servicePackageOrder = new PackageOrderService();
                $package_msg = $servicePackageOrder->judgeOrderEmploy($house_property['id']);
                if ($package_msg && array_key_exists('code', $package_msg) && $package_msg['code'] != 1) {
                    return api_output(1003, [], $package_msg['msg']);
                }
                // 权限相关处理  给予全部权限
                $admin_menus = $service_house_village->getHouseMenuNew(array('status'=>1),'id');
                foreach ($admin_menus as $value) {
                    $power[] = $value['id'];
                }
                //底部菜单
                $footer = $service_house_village->getFooter($power);
                $login_info = $house_property;
                // 获得门密码
                $lockPwd = '';
                // 给予登录用户名
                $login_info['login_name'] = $house_property['property_name'] ? $house_property['property_name'] : $house_property['account'];
                $login_info['property_id'] = $house_property['id'];
                $ticket = Token::getToken();
                if (isset($house_property['id']) && $house_property['id']) {
                    $serviceHouseNewPorperty = new HouseNewPorpertyService();
                    $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($house_property['id']);
                }
                $arr = array(
                    'login_role'	=>	$login_role,
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                );
                return api_output(0,$arr);
            }
            elseif (4 == $login_role) {
                $condition_house_property = [
                    'id' => $uid
                ];
                $house_property = $service_login->get_property_admin_where($condition_house_property);
                if (empty($house_property)) {
                    return api_output(1002,[],'未登陆！');
                }
                $servicePackageOrder = new PackageOrderService();
                $package_msg = $servicePackageOrder->judgeOrderEmploy($house_property['property_id']);
                if($package_msg && $package_msg['code'] != 1)
                {
                    return api_output(1003,[],$package_msg['msg']);
                }
                // 权限相关处理  给予全部权限
                $admin_menus = $service_house_village->getHouseMenuNew(array('status'=>1),'id');
                foreach ($admin_menus as $value) {
                    $power[] = $value['id'];
                }
                //底部菜单
                $footer = $service_house_village->getFooter($power);
                $login_info = $house_property;
                // 获得门密码
                $lockPwd = '';
                // 给予登录用户名
                $login_info['login_name'] = $house_property['realname'] ? $house_property['realname'] : $house_property['account'];
                $ticket = Token::getToken();
                $login_info['property_id'] = $house_property['id'];
                if (isset($house_property['id']) && $house_property['id']) {
                    $serviceHouseNewPorperty = new HouseNewPorpertyService();
                    $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($house_property['id']);
                }
                $arr = array(
                    'login_role'	=>	$login_role,
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                );
                return api_output(0,$arr);

            }
            elseif (5 == $login_role) {
                $condition_house = [
                    'id' => $uid
                ];
                $role = $service_login->get_house_admin_where($condition_house);
                if(empty($role)){
                    return api_output(1002,[],'未登陆！');
                }
                $village_where[] = ['village_id','=',$role['village_id']];
                $village_info = $service_house_village->getHouseVillageInfo($village_where,'property_id,village_name');
                //套餐过滤 2020/11/7 start
                $servicePackageOrder = new PackageOrderService();
                $package_msg = $servicePackageOrder->judgeOrderEmploy($village_info['property_id']);
                if($package_msg && $package_msg['code'] != 1)
                {
                    return api_output(1003,[],$package_msg['msg']);
                }
                //套餐过滤 2020/11/7 end
                //权限
                $power = explode(',',$role['menus']);
                //底部菜单
                $footer = $service_house_village->getFooter($power);

                // 获得门密码
                $login_info = $role;
                $lockPwd = $service_house_village->get_lock_pwd($login_info);
                // 给予登录用户名
                $login_info['login_name'] = $role['realname'] ? $role['realname'] : $village_info['village_name'];
                $ticket = Token::getToken();
                $login_info['property_id'] = $village_info['property_id'];
                if (isset($login_info['property_id']) && $login_info['property_id']) {
                    $serviceHouseNewPorperty = new HouseNewPorpertyService();
                    $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($login_info['property_id']);
                }
                $arr = array(
                    'login_role'	=>	$login_role,
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                );
                return api_output(0,$arr);
            }
            elseif (6 == $login_role) {
                $where = [
                    'wid'=>$uid,
                ];
                $now_house_worker = $service_house_village->getHouseWorker($where);
                if ($now_house_worker) {
                    // 给予登录用户名
                    $now_house_worker['login_name'] = $now_house_worker['name'] ? $now_house_worker['name'] : $now_house_worker['nickname'];
                } else{
                    return api_output(1002,[],'未登陆！');
                }
                $ticket = Token::getToken();
                if (isset($now_house_worker['property_id']) && $now_house_worker['property_id']) {
                    $serviceHouseNewPorperty = new HouseNewPorpertyService();
                    $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($now_house_worker['property_id']);
                }
                $arr = array(
                    'login_role'	=>	$login_role,
                    'user'	=>	$now_house_worker,
                    'ticket'	=>	$ticket,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                );
                return api_output(0,$arr);
            }
        }

        // 处理传值参数
        // 账号类型 1 街道工作人员 2 社区 3 物业总管理员  4 物业普通管理员 (5 小区工作人员) 改=>5 小区物业管理人员  新增:6 小区物业工作人员
        $login_role = $this->request->param('login_role',0,'intval');
        $login_account = $this->request->param('login_account','','strval');
        if (empty($login_account)) {
            return api_output(1001, [], '请填写登录账号');
        }
        $power = [];
        // 获取ip
        $ip = Request::ip();
        $pwd = $this->request->param('pwd','','strval');
        $device_id = $this->request->param('Device-Id', '', 'trim');
        // 获取的密码md5
        $pwd = md5($pwd);
        if (1== $login_role) {
            $serviceOrganizationStreet = new OrganizationStreetService();
            $where = [];
            $where[] = ['work_account','=',$login_account];
            $where[] = ['work_status','<>',4];
            $login_info = $serviceOrganizationStreet->getMemberDetail($where);
            if (empty($login_info)) {
                return api_output(1002,[],'账号或密码错误！');
            }
            if($pwd != $login_info['work_passwd']){
                return api_output(1003,[],'账号或密码错误！');
            }
            unset($login_info['work_passwd']);
            if($login_info['work_status'] != 1){
                return api_output(1003,[],'当前账号被禁止！');
            }
            $ticket = Token::createToken($login_info['worker_id'],$login_role);
            // 获得门密码
            $lockPwd = '';
            //底部菜单
            $footer = [];
            // 给予登录用户名
            $login_info['login_name'] = $login_info['work_name'] ? $login_info['work_name'] : $login_info['work_account'];
            $arr = array(
                'login_rolename'=>$this->login_rolename[$login_role],
                'login_role'	=>	$login_role,
                'user'	=>	$login_info,
                'footer'      => $footer,
                'ticket'	=>	$ticket,
                'lockPwd'    => $lockPwd,
                'newVersionCharge'    => $takeEffectTimeJudge,
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
                return api_output(0,$arr);
            } else{
                return api_output(1003,[],'账号或密码错误！');
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
                return api_output(1003,[],'账号或密码错误！');
            }
            if($pwd != $login_info['pwd']){
                return api_output(1003,[],'账号或密码错误！');
            }
            unset($login_info['pwd']);
            if($login_info['is_open'] == 0){
                return api_output(1003,[],'当前账号被禁止！');
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
                'login_role'	=>	$login_role,
                'user'	=>	$login_info,
                'footer'      => $footer,
                'ticket'	=>	$ticket,
                'lockPwd'    => $lockPwd,
                'newVersionCharge'    => $takeEffectTimeJudge,
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
                return api_output(0,$arr);
            } else{
                return api_output(1003,[],'账号或密码错误！');
            }
        }
        elseif (3 == $login_role) {
            $condition_house_property['account'] = $login_account;
            $house_property = $service_house_village->get_house_property_where($condition_house_property);
            if (empty($house_property)) {
                return api_output(1003, [], '账号或密码错误！');
            }
            if($pwd != $house_property['password']){
                return api_output(1003,[],'账号或密码错误！');
            }
            if ($house_property['status'] != 1) {
                return api_output(1003, [], '当前账号被禁止！');
            }
            //套餐过滤 2020/11/7 start
            $servicePackageOrder = new PackageOrderService();
            $package_msg = $servicePackageOrder->judgeOrderEmploy($house_property['id']);
            if ($package_msg && array_key_exists('code', $package_msg) && $package_msg['code'] != 1) {
                return api_output(1003, [], $package_msg['msg']);
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
                'login_rolename'=>$this->login_rolename[$login_role],
                'login_role'	=>	$login_role,
                'user'	=>	$login_info,
                'footer'      => $footer,
                'ticket'	=>	$ticket,
                'lockPwd'    => $lockPwd,
                'newVersionCharge'    => $takeEffectTimeJudge,
            );
            // 处理登录记录和返回
            $serviceHouseProperty = new HousePropertyService();
            $data = [];
            $data['last_time'] = $_SERVER['REQUEST_TIME'];
            $add_id = $serviceHouseProperty->editData(['id'=>$house_property['id']],$data);
            if($add_id){
                // 更新设备号
                $service_login->up_device($login_role, $login_info['village_id'],$device_id);
                return api_output(0,$arr);
            } else{
                return api_output(1003,[],'账号或密码错误！');
            }
        }
        elseif (4 == $login_role) {
            $village_id = $this->request->param('village_id', '', 'intval');
            $condition_house_property['account'] = $login_account;
            $house_property = $service_login->get_property_admin_where($condition_house_property);
            if(empty($house_property)){
                return api_output(1003,[],'账号或密码错误！');
            }
            if($pwd != $house_property['pwd']){
                return api_output(1003,[],'账号或密码错误！');
            }
            if($house_property['status'] != 1){
                return api_output(1003,[],'当前账号被禁止！');
            }

            //套餐过滤 2020/11/7 start
            $servicePackageOrder = new PackageOrderService();
            $package_msg = $servicePackageOrder->judgeOrderEmploy($house_property['property_id']);
            if($package_msg && $package_msg['code'] != 1)
            {
                return api_output(1003,[],$package_msg['msg']);
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
                'login_rolename'=>$this->login_rolename[$login_role],
                'login_role'	=>	$login_role,
                'user'	=>	$login_info,
                'footer'      => $footer,
                'ticket'	=>	$ticket,
                'lockPwd'    => $lockPwd,
                'newVersionCharge'    => $takeEffectTimeJudge,
            );
            // 处理登录记录和返回
            $servicePropertyAdminService = new PropertyAdminService();
            $data = [];
            $data['last_time'] = time();
            $add_id = $servicePropertyAdminService->editData(['id'=>$house_property['id']],$data);
            if($add_id){
                // 更新设备号
                $service_login->up_device($login_role, $login_info['village_id'],$device_id);
                return api_output(0,$arr);
            } else{
                return api_output(1003,[],'账号或密码错误！');
            }
        }
        elseif (5 == $login_role) {
            $village_id = $this->request->param('village_id', '', 'intval');
            $condition_house = [
                'status' => 1
            ];
            $condition_house['account'] = $login_account;
            if($village_id>0){
                $condition_house['village_id'] = $village_id;
            }
            $now_house = $service_house_village->getList($condition_house);
//            $now_house = $now_house->toArray();
            if ($now_house) {
                // 获取到的是 小区工作人员
                $role = $service_login->get_house_admin_where($condition_house);

                if(empty($role)){
                    return api_output(1003,[],'账号或密码错误！');
                }
                $login_info = $role;
                if($pwd != $role['pwd']){
                    return api_output(1003,[],'账号或密码错误！');
                }
                $village_where[] = ['village_id','=',$role['village_id']];
                $village_info = $service_house_village->getHouseVillageInfo($village_where,'property_id');
                //套餐过滤 2020/11/7 start
                $servicePackageOrder = new PackageOrderService();
                $package_msg = $servicePackageOrder->judgeOrderEmploy($village_info['property_id']);
                if($package_msg && $package_msg['code'] != 1)
                {
                    return api_output(1003,[],$package_msg['msg']);
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
                    return api_output(1003,[],$msg[$login_info['status']]);
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
                $arr = array(
                    'login_rolename'=>$this->login_rolename[$login_role],
                    'login_role'	=>	$login_role,
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                );
                if($add_id){
                    // 更新设备号
                    $service_login->up_device($login_role, $login_info['village_id'],$device_id);
                    return api_output(0,$arr);
                } else{
                    return api_output(1003,[],'账号或密码错误！');
                }
            } else {
                // 角色登录
//                $village_id = $this->request->param('village_id', '', 'intval');
//                $condition_house = [
//                    'status' => 1
//                ];
//                $condition_house['account'] = $login_account;
//
//                $login_admin = $service_login->get_house_admin_where($condition_house);

                $condition_admin = [];
                $condition_admin[] = ['a.status', '=', 1];
                $condition_admin[] = ['a.account', '=', $login_account];
                if($village_id>0){
                    $condition_admin[] = ['a.village_id', '=', $village_id];
                }
                $field = 'a.*,hv.village_name';
                $login_admin = $service_login->get_admin_village_list($condition_admin,$field);
                if(count($login_admin)==1){
                    $login_admin =  $login_admin[0];
                } else {
                    $now_house_arr = [];
                    foreach($login_admin as $key=>$val) {
                        $now_house_arr[$key]['village_id'] = $val['village_id'];
                        $now_house_arr[$key]['village_name'] = $val['village_name'];
                    }
                    if(empty($now_house_arr)){
                        return api_output(1003,[],'账号或密码错误！');
                    }
                    $arr = [
                        'village_list' => $now_house_arr
                    ];
                    return api_output(0,$arr);
                }

                if(empty($login_admin)){
                    return api_output(1003,[],'账号或密码错误！');
                }
                if($pwd != $login_admin['pwd']){
                    return api_output(1003,[],'账号或密码错误！');
                }
                if($login_admin['status'] == 2){
                    return api_output(1003,[],'当前账号被禁止！');
                }
                $login_info = $service_house_village->getHouseVillage($login_admin['village_id']);
                unset($login_info['pwd']);
                //套餐过滤 2020/11/7 start
                $servicePackageOrder = new PackageOrderService();
                $package_msg = $servicePackageOrder->judgeOrderEmploy($login_info['property_id']);
                if($package_msg && $package_msg['code'] != 1)
                {
                    return api_output(1003,[],$package_msg['msg']);
                }
                //套餐过滤 2020/11/7 end
                //权限
                $power = explode(',',$login_info['menus']);
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
                $arr = array(
                    'login_role'	=>	$login_role,
                    'user'	=>	$login_info,
                    'footer'      => $footer,
                    'ticket'	=>	$ticket,
                    'lockPwd'    => $lockPwd,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                );
                if($add_id){
                    // 更新设备号
                    $service_login->up_device($login_role, $login_info['village_id'],$device_id);
                    return api_output(0,$arr);
                } else{
                    return api_output(1003,[],'账号或密码错误！');
                }
            }
        }
        elseif (6 == $login_role){//小区物业工作人员 2020/10/20 start  新增
            $village_id = $this->request->param('village_id', '', 'intval');
            $data = [
                'village_id'=>$village_id,
                'account'=>$login_account,
            ];
            $now_house_worker = $service_house_village->getHouseWorker($data);
            if ($now_house_worker) {
                if($pwd != $now_house_worker['password']){
                    return api_output(1003,[],'账号或密码错误！');
                }

                //套餐过滤 2020/11/7 start
                $servicePackageOrder = new PackageOrderService();
                $package_msg = $servicePackageOrder->judgeOrderEmploy($now_house_worker['property_id']);
                if($package_msg && $package_msg['code'] != 1)
                {
                    return api_output(1003,[],$package_msg['msg']);
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
                    return api_output(1003,[],$msg[$now_house_worker['status']]);
                }
                $ticket = Token::createToken($now_house_worker['wid'],$login_role);
                // 给予登录用户名
                $now_house_worker['login_name'] = $now_house_worker['name'] ? $now_house_worker['name'] : $now_house_worker['nickname'];
                if (isset($now_house_worker['property_id']) && $now_house_worker['property_id']) {
                    $serviceHouseNewPorperty = new HouseNewPorpertyService();
                    $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($now_house_worker['property_id']);
                }
                $arr = array(
                    'login_rolename'=>$this->login_rolename[$login_role],
                    'login_role'	=>	$login_role,
                    'user'	=>	$now_house_worker,
                    'ticket'	=>	$ticket,
                    'newVersionCharge'    => $takeEffectTimeJudge,
                );
                // 处理登录记录和返回
                $data = [];
                $data['login_time'] = time();
                $res = $service_house_village->saveHouseWorker($now_house_worker['wid'],$data);
                if($res){
                    return api_output(0,$arr);
                }else{
                    return api_output(1003,[],'账号或密码错误！');
                }
            } else {
                return api_output(1003,[],'账号或密码错误！');
            }
        }else {
            return api_output(1001,[],'请选择身份！');
        }
    }


    /**
     * 切换角色登录
     * @author:zhubaodi
     * @date_time: 2022/7/8 14:58
     */
    public function check_login(){
        $uid = $this->request->log_uid;
        $login_role = $this->request->log_extends;
        // 账号类型 1 街道工作人员 2 社区 3 物业总管理员  4 物业普通管理员 (5 小区工作人员) 改=>5 小区物业管理人员  新增:6 小区物业工作人员
        $login_role_new = $this->request->param('login_role',0,'intval');
        $device_id = $this->request->param('Device-Id', '', 'trim');
        $village_id = $this->request->param('village_id', 0, 'intval');
        $property_id = $this->request->param('property_id', 0, 'intval');
        $ip = Request::ip();
        if ($uid && intval($uid)>0 && $login_role) {
            $service_community_login=new CommunityLoginService();
            fdump_api([$uid,$login_role,$login_role_new,$ip,$device_id,$village_id],'check_login_0709',1);
            $res=$service_community_login->check($uid,$login_role,$login_role_new,$ip,$device_id,$village_id);
            fdump_api([$res],'check_login_0709',1);
            if (empty($res)){
                return api_output(1001, [], '账号或密码错误');
            }else{
                if ($res['error']==0){
                    return api_output(0, $res['data']);
                }else{
                    return api_output($res['error'], [], $res['msg']);
                }
            }
        }else{
            return api_output(1002,[],'未登陆！');
        }
    }
    /***
    *注销账号，删除相应的账号
     **/
    public function accountCancellation(){
        $service_login           = new ManageAppLoginService();
        //  初始化 社区 业务层
        $service_house_village   = new HouseVillageService();
        $service_community_login =new CommunityLoginService();
        $uid        = $this->request->log_uid;
        $login_role = $this->request->log_extends;

        $loginTicket = $this->request->param('loginTicket');
        if ($loginTicket) {
            $login_arr = $service_community_login->getLoginSignInfo($loginTicket);
            if (isset($login_arr['ticket']) && $login_arr['ticket'] && isset($login_arr['member_id']) && $login_arr['member_id'] && isset($login_arr['extends']) && $login_arr['extends']) {
                $ticket     = $login_arr['ticket'];
                $uid        = $login_arr['member_id'];
                $login_role = $login_arr['extends'];
            }
        }
        $cancel_date=date('Y-m-d H:i:s');
        $retArr=array('is_cancellation'=>1,'msg'=>'注销成功！');
        if ($uid && intval($uid)>0 && $login_role) {
            if (!isset($ticket) || !$ticket) {
                $ticket = Token::getToken();
            }
            if (1== $login_role) {
                // 街道登录
                //  初始化 社区 业务层
                $serviceOrganizationStreet = new OrganizationStreetService();
                $login_info = $serviceOrganizationStreet->getMemberInfo($uid);
                if (empty($login_info['info'])) {
                    return api_output(1002,[],'未登陆！');
                }
                $saveArr=array('work_status'=>4,'remark'=>'客户在'.$cancel_date.'注销了账号');
                $whereArr=array(['worker_id','=',$uid]);
                $ret=$serviceOrganizationStreet->saveStreetWork($whereArr,$saveArr);

                return api_output(0,$retArr);
            }
            elseif (11== $login_role || 21 == $login_role) {
                // 街道和社区登录保留 值往后延
                // 街道登录
                //  初始化 社区 业务层
                $service_area_street = new AreaStreetService();
                $where = [
                    'area_id' => $uid
                ];
                $login_info = $service_area_street->getAreaStreet($where);
                if (empty($login_info)) {
                    return api_output(1002,[],'未登陆！');
                }
                $saveArr=array('is_open'=>0,'remark'=>'客户在'.$cancel_date.'注销了账号');
                $whereArr=array(['area_id','=',$uid]);
                $ret=$service_area_street->saveAreaStreet($whereArr,$saveArr);
                return api_output(0,$retArr);
            }
            elseif (3 == $login_role) {
                $condition_house_property = [
                    'id' => $uid
                ];
                $house_property = $service_house_village->get_house_property_where($condition_house_property);
                if (empty($house_property)) {
                    return api_output(1002,[],'未登陆！');
                }
                /***禁止移动端登录******/
                $saveArr=array('mobile_forbid_time'=>time());
                $whereArr=array(['id','=',$uid]);
                $ret=$service_house_village->save_house_property($whereArr,$saveArr);
                return api_output(0,$retArr);
            }
            elseif (4 == $login_role) {
                $condition_house_property = [
                    'id' => $uid
                ];
                $house_property = $service_login->get_property_admin_where($condition_house_property);
                if (empty($house_property)) {
                    return api_output(1002,[],'未登陆！');
                }
                $saveArr=array('status'=>4,'remarks'=>'客户在'.$cancel_date.'注销了账号');
                $whereArr=array(['id','=',$uid]);
                $ret=$service_login->save_property_admin($whereArr,$saveArr);
                if(isset($house_property['wid']) && ($house_property['wid']>0)){
                    $saveWorkerArr=array('status'=>4,'openid'=>'','is_del'=>1,'note'=>'客户在'.$cancel_date.'注销了账号','remarks'=>'客户在'.$cancel_date.'注销了账号');
                    $retw=$service_house_village->saveHouseWorker($house_property['wid'],$saveWorkerArr,'destroy');

                }
                return api_output(0,$retArr);

            }
            elseif (5 == $login_role) {
                $condition_house = [
                    'id' => $uid
                ];
                $role = $service_login->get_house_admin_where($condition_house);
                if(empty($role)){
                    return api_output(1002,[],'未登陆！');
                }
                $saveArr=array('status'=>4,'remarks'=>'客户在'.$cancel_date.'注销了账号');
                $whereArr=array(['id','=',$uid]);
                $ret=$service_login->edit_house_admin($whereArr,$saveArr);
                if(isset($role['wid']) && ($role['wid']>0)){
                    $saveWorkerArr=array('status'=>4,'openid'=>'','is_del'=>1,'note'=>'客户在'.$cancel_date.'注销了账号','remarks'=>'客户在'.$cancel_date.'注销了账号');
                    $retw=$service_house_village->saveHouseWorker($role['wid'],$saveWorkerArr,'destroy');
                }
                return api_output(0,$retArr);
            }
            elseif (6 == $login_role) {
                $where = [
                    'wid'=>$uid,
                ];
                $now_house_worker = $service_house_village->getHouseWorker($where);
                if (empty($now_house_worker)) {
                    return api_output(1002,[],'未登陆！');
                }
                $saveWorkerArr=array('status'=>4,'openid'=>'','is_del'=>1,'note'=>'客户在'.$cancel_date.'注销了账号','remarks'=>'客户在'.$cancel_date.'注销了账号');
                $retw=$service_house_village->saveHouseWorker($now_house_worker['wid'],$saveWorkerArr,'destroy');
                if($now_house_worker['xtype']==1 && $now_house_worker['relation_id']>0){
                    $saveArr=array('status'=>4,'remarks'=>'客户在'.$cancel_date.'注销了账号');
                    $whereArr=array(['id','=',$now_house_worker['relation_id']]);
                    $ret=$service_login->save_property_admin($whereArr,$saveArr);
                }else if($now_house_worker['xtype']==2 && $now_house_worker['relation_id']>0){
                    $saveArr=array('status'=>4,'remarks'=>'客户在'.$cancel_date.'注销了账号');
                    $whereArr=array(['id','=',$now_house_worker['relation_id']]);
                    $ret=$service_login->edit_house_admin($whereArr,$saveArr);
                }
                return api_output(0,$retArr);
            }
        }
        return api_output(1002,[],'未登陆！');

    }
    public function save_jpush_registration_info(){
        return $this->check('jpush_registration');
    }
}