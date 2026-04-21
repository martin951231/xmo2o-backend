<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/8 19:07
 */

namespace app\community\model\service;

use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseMenuNew;
use app\community\model\db\ApplicationList;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\HouseNewRepairCate;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageConfig;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\PropertyAdmin;
use app\community\model\db\PropertyAdminAuth;
use app\community\model\db\HouseNewRepairSubject;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\ApplicationBind;
use app\common\model\service\config\ConfigCustomizationService;
use think\facade\Cache;

class VillageMenuService
{

    public $cacheTagKey;
    public function clearCache($village_id)
    {
        $this->cacheTagKey = 'villageMenuList:menus:'.$village_id;
        Cache::tag($this->cacheTagKey)->clear();
        return api_output(0,[],'缓存清空成功');
    }
    
    /**
     * Notes: 返回前端需要的菜单格式
     * @param array $menuList
     * @param array $adminUser
     * @return array
     * @author: wanzy
     * @date_time: 2020/8/3 16:58
     */
    public function formartMenuList($menuList = [], $adminUser = [], $property_login_role = 0, $property_uid = 0)
    {
        
        $village_id = $adminUser['village_id'];
        $cacheKey  = 'villageMenuList:menus:'.$village_id;
        /*
         * $returnArr = Cache::get($cacheKey);
		if (!empty($returnArr)){
			return $returnArr;
		}
		*/
        $db_house_menu_new = new HouseMenuNew();
        $servicePrivilegePackage = new PrivilegePackageService();
        $where_menus = [];
        $where_menus[] = ['status','=',1];
        $where_menus[] = ['show','=',1];
        $where_menus[] = ['type','<>',2];
        if ($adminUser && isset($adminUser['menus']) && $adminUser['menus']) {
            if (is_array($adminUser['menus'])) {
                $where_menus[] = ['id','in',$adminUser['menus']];
            } else {
                $menus = explode(',', $adminUser['menus']);
                if (is_array($menus) && !empty($menus)) {
                    // 新更新父级 赋予所有，没有子集的时候会自动隐藏
                    //$menus[] = 2000;
                    //$menus[] = 2001;
                   // $menus[] = 2002;
//                    $menus[] = 2003;
                    $where_menus[] = ['id','in',$menus];
                }
            }
        }
        $notInArrId=[111163];
        if(!in_array($property_login_role,[5,401])){
            $notInArrId[]=112163;
        }
        $where_menus[]=['id','not in',$notInArrId];

        $house_menu_list = $db_house_menu_new->getList($where_menus,true,'fid ASC,sort desc,id ASC');
        if (!empty($house_menu_list)) {
            $house_menu_list = $house_menu_list->toArray();
            fdump_api($house_menu_list, '$house_menu_list');
            //判断普通管理员
            if($property_login_role == 12 && $property_uid){
                $db_property_admin_auth = new PropertyAdminAuth();
                $auth_info = $db_property_admin_auth->getOne(['admin_id'=>$property_uid,'village_id'=>$adminUser['village_id']],'menus');
                if($auth_info){
                    $auth_info = $auth_info->toArray();
                }
                if($auth_info){
                    $property_menus = explode(',',$auth_info['menus']);
                    foreach ($house_menu_list as $k=>$v){
                        if(!in_array($v['id'],$property_menus) && !($v['module'] == 'Index' && $v['action'] == 'index' && $v['select_module'] == 'Index')){
                            unset($house_menu_list[$k]);
                        }
                    }
                    $house_menu_list = array_values($house_menu_list);
                }
            }
        } else {
            $house_menu_list = [];
        }
        $enterprise_wx_corpid = [];
        $application_have = true;
        $requestType = request()->param('requestType');
        $takeEffectTimeJudge = false;
        if ($adminUser && isset($adminUser['property_id']) && $adminUser['property_id']) {
            $house_property_id = $adminUser['property_id'];
            $packageData = $servicePrivilegePackage->filterNav($house_menu_list, $house_property_id);
            $house_menu_list = $packageData['house_menu_list'];

            $db_house_enterprise_wx_bind = new HouseEnterpriseWxBind();
            $where_enterprise_wx = [];
            $where_enterprise_wx[] = ['bind_id','=',$house_property_id];
            $where_enterprise_wx[] = ['bind_type','=',0];
            $enterprise_wx_corpid = $db_house_enterprise_wx_bind->getOne($where_enterprise_wx,'pigcms_id,corpid');
            if (!empty($enterprise_wx_corpid)) {
                $enterprise_wx_corpid = $enterprise_wx_corpid->toArray();
            } else {
                $enterprise_wx_corpid = [];
            }
            $servicePackageOrder=new PackageOrderService();
            $package_content = $servicePackageOrder->getPackageContent($house_property_id);
            $package_id = isset($package_content['package_id'])?$package_content['package_id']:0;
            $content_id = $package_content['content'];
            if (!empty($content_id)) {
                $array_diff_key = array_diff($content_id,[12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37]);
            }
            if(!$content_id){
                $content_id = [0];
                $application_have = false;
            } elseif ($array_diff_key==$content_id) {
                $application_have = false;
            }
            $serviceHouseNewPorperty = new HouseNewPorpertyService();
            $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($adminUser['property_id']);
        } else {
            $package_id = 0;
        }
        $application_arr = [];
        $db_application_list = new ApplicationList();
        if ($adminUser && isset($adminUser['village_id']) && $adminUser['village_id']) {
            $village_id = $adminUser['village_id'];
            $village_config_info = (new HouseVillageConfig())->getOne(['village_id'=>$village_id],'open_new_cashier');
            $where_column = [];
//        $where_column[] = ['from','=',0];
            $where_column[] = ['b.use_id','=',$village_id];
            $where_column[] = ['b.status','=',0];
            $application = $db_application_list->getColumn($where_column,'a.application_id,a.application_type');
            if (!empty($application)) {
                foreach ($application as $val) {
                    $application_arr[$val['application_id']] = $val;
                }
            }
            if ($takeEffectTimeJudge && (!isset($application_arr[33])||!$application_arr[33])) {
                // 如果新版收费开启了 且 没有绑定关系 再次绑定下
                $bind_arr = [
                    'application_id' => 33,
                    'from' => 0,
                    'use_id' => $adminUser['village_id'],
                    'other_id' => $adminUser['property_id'],
                    'status' => 0,
                    'add_time' => time(),
                ];
                $db_applicationBind = new ApplicationBind();
                $db_applicationBind->addOne($bind_arr);
                $application_arr[33] = [
                    'application_id' => 33,
                    'application_type' => 1,
                ];
            }
        }
        $fixed_application_arr = [];
        $where_column1 = [];
//        $where_column1[] = ['from','=',0];
        $where_column1[] = ['application_type','=',1];
        $fixed_application = $db_application_list->getSome($where_column1,'application_id,application_type,application_house');
        if (!empty($fixed_application)) {
            $fixed_application = $fixed_application->toArray();
            foreach ($fixed_application as $vals) {
                $fixed_application_arr[$vals['application_id']] = $vals;
            }
        }

        //新老版收银台显示判断
        $open_new_cashier=0;
        if (isset($village_config_info)){
            $open_new_cashier=$village_config_info['open_new_cashier'];
        }

        $house_menu = [];
        $my_application = false;
        $my_application_name = '';
        $tmpMenu1 = [];
        // 单独处理隐藏的子集
        $children = [];
        // 收集 不需要子目录的目录
        $no_children = [];
        $uniteSrc = [];
        $lastChidren=[];
        $oldVersionChargeMenus = [65,417];// 老版收费本目录过滤 当前 收费管理+移动抄表
        $oldVersionChangeNew = [];
        $oldVersionChangeNew['changePid'] = [
            '60' => 2002,
            '86' => 665,
        ];
        $oldVersionChangeNew['changeMenus'] = [60,86];// 物业余额+	打印模板设置

        //todo 存在a185人脸门禁判断是否显示室内机菜单栏
        $is_indoor=false;
        $face_where = [
            ['village_id', '=', $adminUser['village_id']],
            ['is_del','=',0],
            ['device_type','=',61]
        ];
        $a185Device=(new HouseFaceDevice())->getOne($face_where,'device_id');
        if($a185Device && $a185Device['device_id']){
            $is_indoor=true;
        }
        /** 车库不唯一了 判断无意义
        //todo 判断月租车收费是否显示
        $is_park=true;
        $face_where = [
            ['village_id', '=', $adminUser['village_id']],
           //  ['park_sys_type','=','D3'],
        ];
        $park_info=(new HouseVillageParkConfig())->getFind($face_where,'id,park_sys_type');
        if($park_info && $park_info['id']&&$park_info['park_sys_type']=='D3'){
            $is_park=false;
        }
        */
        $works_order_switch = false;
        $park_new_switch    = false;
        if(isset($adminUser['village_id'])){
            $villag_info=(new HouseVillageInfo())->getOne([['village_id','=',$adminUser['village_id']]],'works_order_switch,park_new_switch');
            if ($villag_info && !$villag_info->isEmpty() && $villag_info['works_order_switch'] == 1){
                $works_order_switch = true;
            }
            if ($villag_info && !$villag_info->isEmpty() && $villag_info['park_new_switch'] == 1){
                $park_new_switch = true;
            }
        }

        $false=0;
        $newPathComponentArr=array('visitorTmpParking');
        $cloudAccessIframeUrlMap = [
            'access_device@device_list' => cfg('site_url') . '/v20/public/platform/house_access_manager.html?view=device',
            'sensors@sensors_list' => cfg('site_url') . '/v20/public/platform/house_access_manager.html?view=sensor',
            'records@records_list' => cfg('site_url') . '/v20/public/platform/house_access_manager.html?view=record',
        ];

        // 单独针对部分进行处理  比如老版本收费和新版本收费
        $topApplicationArr = [65,665];
        $topChildrenApplicationArr = [];
        $configCustomizationService=new ConfigCustomizationService();
        $voice_recognition_asr_open=$configCustomizationService->getVoiceRecognitionOpenJudge();
        fdump_api($house_menu_list, '$house_menu_list', 1);

        $houseName = (new HouseVillageService())->getHouseAlias('house_name',$village_id) ?: '小区';
        $singleName = (new HouseVillageService())->getHouseAlias('single_name',$village_id) ?: '楼栋';
        $floorName = (new HouseVillageService())->getHouseAlias('floor_name',$village_id) ?: '单元';
        $roomName = (new HouseVillageService())->getHouseAlias('room_name',$village_id) ?: '房间';
        $roomNames = (new HouseVillageService())->getHouseAlias('room_name',$village_id) ?: '房屋';
        foreach($house_menu_list as $key => $value){
            if($value['name'] == '单元楼层'){
                $value['name'] = $floorName ? ($floorName.'管理') : '单元楼层';
            }else{
                $value['name'] = str_replace(['小区','楼栋','单元','房间','房屋'],['{小区}','{楼栋}','{单元}','{房间}','{房屋}'],$value['name']);//防止重复替换
                $value['name'] = str_replace(['{小区}','{楼栋}','{单元}','{房间}','{房屋}'],[$houseName,$singleName,$floorName,$roomName,$roomNames],$value['name']);
            }
            if(!$voice_recognition_asr_open && in_array($value['id'],[112064,112067,112073])){
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            }
            /*
            if($value['id'] == '1000' && !$is_park){
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            }
            if($value['name'] == '收费规则管理' && $is_park){
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            }
            */
            if($value['name'] == '新版收银台' && $open_new_cashier!=1){
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            }
            if($value['name'] == '收银台' && $open_new_cashier==1){
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            }
            if($value['id'] == '111157' && !$is_indoor){
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            }
            if($works_order_switch && in_array($value['id'],[219,222,224])){
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            }
            if($park_new_switch && in_array($value['id'],[416,45,55])){
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            }
            //免费车导航显示判断
            if (!empty($park_info)&&$park_info['park_sys_type']=='D7'&&in_array($value['id'],[112030])){
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            }
            if(!$takeEffectTimeJudge && in_array($value['id'],[112022])){
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            }
            if ($requestType && 'oldVersion'==$requestType && !in_array($value['id'],$oldVersionChargeMenus) && !in_array($value['fid'],$oldVersionChargeMenus)) {
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            }
            if (!$requestType && $takeEffectTimeJudge  && (in_array($value['id'], $oldVersionChargeMenus) || in_array($value['fid'], $oldVersionChargeMenus))) {
                if ($oldVersionChangeNew['changePid'] && $oldVersionChangeNew['changeMenus'] && in_array($value['id'],$oldVersionChangeNew['changeMenus'])) {
                    $value['fid'] = $oldVersionChangeNew['changePid'][$value['id']];
                } else {
                    $value['is_hide'] =  1;
                }
                if( $value['id'] ==417 && ($value['is_hide']==1)){
                    fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                    continue;
                }
            }
            if ($value['name']=='工单管理') {
                $false = 1;
            }
            if (!isset($application_arr[$value['application_id']])||!$application_arr[$value['application_id']]) {
                // 没有绑定的收费逻辑单独处理
                if ($value['application_id']==33&&!$value['application_txt']&&in_array($value['id'],$topApplicationArr)) {
                    $value['application_txt'] = $value['application_id'];
                    $topChildrenApplicationArr[$value['id']] = $value['application_id'];
                    $value['application_id'] = 0;
                } elseif (!$value['application_id']&&!$value['application_txt']&&in_array($value['fid'],$topApplicationArr)&&isset($topChildrenApplicationArr[$value['fid']])) {
                    $value['application_id'] = $topChildrenApplicationArr[$value['fid']];
                }
            }
            // 遇到父级为不要子目录的目录直接跳过
            if (in_array($value['fid'], $no_children)) {
                fdump_api(['line' => __LINE__, 'value' => $value, '$no_children' => $no_children], '$house_menu_list', 1);
                continue;
            }
            // 处理下是否已经添加到我的应用
            if (isset($value['application_txt']) && $value['application_txt']) {
                $application_txt = explode(',',$value['application_txt']);
            } else {
                $application_txt = array();
            }
            if (isset($value['application_id']) && $value['application_id']>0 && !isset($fixed_application_arr[$value['application_id']])  && (empty($application_arr) || !isset($application_arr[$value['application_id']]) || empty($application_arr[$value['application_id']]))) {
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            } elseif(isset($value['fid']) && $value['fid']==194 && !$value['application_id'] && empty($application_txt)) {
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            } elseif(isset($value['fid']) && $value['fid']==194 && !empty($application_txt)) {
                $is_continue = true;
                foreach($application_txt as $application_id_val) {
                    if ((isset($fixed_application_arr[$application_id_val]) && $fixed_application_arr[$application_id_val]) || (isset($application_arr[$application_id_val]) && $application_arr[$application_id_val])) {
                        $is_continue = false;
                    }
                }
                if ($is_continue) {
                    fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                    continue;
                }
            } elseif (isset($value['id']) && in_array($value['id'],[555,1114]) && (empty($enterprise_wx_corpid) || !isset($enterprise_wx_corpid['corpid']))) {
                // 企业微信没有配置 此处过滤 小区企业微信功能
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            } elseif (isset($value['id']) && $value['id']==1123 && $package_id && $package_id==17) {
                // 免费套餐过滤会话存档
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            } elseif (isset($value['id']) && $value['id']==430 && !$application_have) {
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            } elseif (isset($value['id']) && $value['id']==111169 && !in_array($_SERVER['HTTP_HOST'],['www.zatao.cc','www.group.com'])) {
                fdump_api(['line' => __LINE__, 'value' => $value], '$house_menu_list', 1);
                continue;
            }

            if (isset($value['application_id']) && $value['application_id']>0 && isset($fixed_application_arr[$value['application_id']]) && $fixed_application_arr[$value['application_id']] && (empty($application_arr) || !isset($application_arr[$value['application_id']]) || empty($application_arr[$value['application_id']]))) {
                if ($value['module'] && $value['action']) {
                    $a = $value['module'].'_'.$value['action'];
                } elseif (3==$value['jump_type'] && $value['component']) {
                    $a = $value['component'];
                }
                $url = '/shequ.php?g=House&c=Application&a='.$a.'&application_id='.$value['application_id'].'&menusId='.$value['id'];
                $value['url'] = cfg('site_url').$url;
                $uniteSrc[$value['id']] = $value['url'];
            } elseif(isset($value['application_id']) && $value['application_id']>0 && isset($fixed_application_arr[$value['application_id']]) && $fixed_application_arr[$value['application_id']] && isset($application_arr[$value['application_id']]) && $application_arr[$value['application_id']]) {
                $url = $fixed_application_arr[$value['application_id']]['application_house'];
                $value['url'] = cfg('site_url').$url;
            } elseif(isset($value['jump_type']) && $value['jump_type']==1){
                $url = '/shequ.php?g=House&c=Plugin&a='.$value['action'].'&url='.urlencode(cfg('site_url').$value['jump_url']);
                $value['url'] = cfg('site_url').$url;
            } elseif(isset($value['jump_type']) && $value['jump_type']==2){
                $url = '/shequ.php?g=House&c=Plugin&a=page&url='.urlencode($value['jump_url']);
                $value['url'] = cfg('site_url').$url;
            } elseif(isset($value['module']) && $value['module']){
                $url =  "/shequ.php?g=House&c={$value['module']}&a={$value['action']}";
                $value['url'] = cfg('site_url').$url;
            }
            if ($value['id']==194) {
                $my_application_name = $value['name'];
            }
            if ((isset($value['url']) && $value['url']) || (isset($value['jump_type']) && $value['jump_type']==3)) {
                $tmpMenu = [];
                $moduleActionKey = strtolower(trim($value['module'])).'@'.strtolower(trim($value['action']));
                if (isset($cloudAccessIframeUrlMap[$moduleActionKey])) {
                    $value['jump_type'] = 0;
                    $value['component'] = '';
                    $value['path'] = '';
                    $value['url'] = $cloudAccessIframeUrlMap[$moduleActionKey];
                }
                if (isset($value['jump_type']) && $value['jump_type']==3 && isset($value['component']) && $value['component']) {
                    if (isset($value['module']) && $value['module'] && isset($value['action']) &&  $value['action']) {
                        $tmpMenu['name'] = 'house_'.strtolower($value['module']).'_'.strtolower($value['action']);
                    } else {
                        $tmpMenu['name'] = 'house_'.strtolower($value['component']);
                    }
                } else {
                    $tmpMenu['name'] = 'house_'.$value['id'];
                }
                if (isset($value['fid']) && intval($value['fid'])<=0) {
                    $tmpMenu['parentId'] = 0;
                } elseif (isset($value['fid']) && intval($value['fid'])>0) {
                    $tmpMenu['parentId'] = 'house_'.$value['fid'];
                    if (isset($house_menu[$value['fid']]) && $house_menu[$value['fid']]) {
                        if (!isset($value['is_hide']) || $value['is_hide']!=1) {
                            $house_menu[$value['fid']]['component'] = 'RouteView';
                        }
                        if (isset($value['jump_type']) && $value['jump_type']==3 && (isset($house_menu[$value['fid']]['path']) || !empty($house_menu[$value['fid']]['path']))) {
                            $house_menu[$value['fid']]['path'] = "";
                        }
                    }
                }
                $tmpMenu['id'] = 'house_'.$value['id'];
                $tmpMenu['meta'] = [
                    'icon' => $value['icon'],
                    'title' => $value['name'],
                    'show' => isset($value['is_hide']) && $value['is_hide']==1 ? false : true,
                    'permission' => 'system',
                    'keepAlive' => isset($value['is_hide']) && $value['is_hide']==1 ? false : true,
                ];
                $is_src = true;
                $component = '';
                $is_path=false;
                $path = '';
                if (isset($value['jump_type']) && $value['jump_type']==3) {
                    $component = isset($value['component']) && $value['component'] ? $value['component'] : '';
                    $path = isset($value['path']) && $value['path'] ? $value['path'] : '';
                    if ($path) {
                        $is_src = false;
                        if(!empty($component) && in_array($component,$newPathComponentArr)){
                            $is_path=true;
                        }
                    }
                    if (isset($value['application_id']) && $value['application_id']==33){
                        $is_src = true;
                    }
                }

                if (!$component && isset($value['select_action']) && $value['select_action']) {
                    $component = 'System';
                } elseif (in_array($value['id'], $no_children)) {
                    // 遇到不要子目录的目录
                    $component = 'System';
                } elseif (!$component) {
                    $component = 'System';
                }
                if (!$path) {
                    if (isset($value['jump_type']) && $value['jump_type']==3 && isset($value['component']) && $value['component']) {
                        $path_name = 'house_'.strtolower($value['component']);
                    } else {
                        $path_name = 'house_'.strtolower($value['module']).'_'.strtolower($value['action']);
                    }
                    $path = "/village/village.iframe/{$path_name}";
                }
                $tmpMenu['component'] = $component;
                $tmpMenu['path'] = $path;
                if ($is_src) {
                    if(isset($value['url'])){
                        $tmpMenu['component'] = 'System';
                        $tmpMenu['src'] = urlencode($value['url'].'&iframe=true');
                    }
                } elseif (!$is_path && isset($value['fid']) && intval($value['fid'])>0 && isset($uniteSrc[$value['fid']]) && $uniteSrc[$value['fid']]) {
                    $tmpMenu['component'] = 'System';
                    $tmpMenu['src'] = urlencode($uniteSrc[$value['fid']].'&iframe=true');
                }
                if ($requestType && 'oldVersion'==$requestType && isset($tmpMenu['path']) && $tmpMenu['path'] && $tmpMenu['parentId']) {
                    $tmpMenu['path'] .= '/requestType='.$requestType;
                }
                if (!$requestType && $takeEffectTimeJudge  && (in_array($value['id'], $oldVersionChargeMenus) || in_array($value['fid'], $oldVersionChargeMenus))) {
                    $tmpMenu['path'] .= '/requestType='.$requestType;
                }
                $house_menu[$value['id']] = $tmpMenu;
                if (!$my_application && $value['fid']==194 && empty($tmpMenu1)) {
                    $my_application = true;
                    $tmpMenu1 = [
                        'name' => 'house_194',
                        'parentId' => 0,
                        'id' => 'house_194',
                        'meta' => [
                            'icon' => 'iconwode',
                            'title' => $my_application_name ? $my_application_name : '我的应用',
                            'show' => true,
                            'permission' => 'system',
                        ],
                        'component' => 'RouteView',
                        'path' => '',
                    ];
                    if (isset($tmpMenu['src'])) {
                        $tmpMenu1['src'] = $tmpMenu['src'];
                    }
                    $house_menu[$value['fid']] = $tmpMenu1;
                } elseif (!$my_application && $value['fid']==194 && isset($tmpMenu['src']) && !$tmpMenu['src']) {
                    $my_application = true;
                    $tmpMenu1['src'] = $tmpMenu['src'];
                    $house_menu[$value['fid']] = $tmpMenu1;
                } elseif (!$my_application && $value['fid']==194) {
                    $my_application = true;
                }
            } elseif ($value['id']==194 && !empty($tmpMenu1) && isset($tmpMenu1['meta']) && isset($tmpMenu1['meta']['title'])) {
                $tmpMenu1['meta']['title'] = $value['name'];
                $house_menu[$value['id']] = $tmpMenu1;
            } elseif ($value['id']==194 && empty($tmpMenu1)) {
                $tmpMenu1 = [
                    'name' => 'house_194',
                    'parentId' => 0,
                    'id' => 'house_194',
                    'meta' => [
                        'icon' => 'iconwode',
                        'title' => $my_application_name ? $my_application_name : '我的应用',
                        'show' => true,
                        'permission' => 'system',
                    ],
                    'component' => 'RouteView',
                    'path' => '',
                ];
                $house_menu[$value['id']] = $tmpMenu1;
            }


        }
        if ($house_menu && isset($house_menu[136])) {
            $tmpMenuChildren = [
                'name' => 'house_136_1',
                'parentId' => 'house_136',
                'id' => 'house_136_1',
                'meta' => [
                    'icon' => 'tasks',
                    'title' => '待审核文章',
                    'show' => false,
                    'permission' => 'system',
                    'keepAlive' => false,
                ],
                'component' => 'System',
                'path' => '/village/village.iframe/house_bbs_aricle_audit_list',
                'src' => urlencode(cfg('site_url').'/shequ.php?g=House&c=Bbs&a=aricle_audit_list&iframe=true'),
            ];
            $children[] = $tmpMenuChildren;
        }
        if (!$requestType && $takeEffectTimeJudge && isset($house_menu[86]) && $house_menu[86]) {
            $lastChidren = $house_menu[86];
            unset($house_menu[86]);
        }
        if (!$my_application && isset($house_menu[194])) {
            unset($house_menu[194]);
        }
        $house_menu = array_values($house_menu);
       
//        if (!empty($tmpMenu1)) {
//            $house_menu[] = $tmpMenu1;
//        }
        if (!empty($children)) {
            $house_menu = array_merge($children,$house_menu);
        }
        if ($lastChidren) {
            $house_menu[] = $lastChidren;
        }
        $returnArr = $house_menu;
        if (!empty($false) && !isset($uniteSrc[111158])){
            $subject=[];
            (new RepairCateService())->checkRepairCate($adminUser['property_id'],$adminUser['village_id']);
            $subject_list = (new HouseNewRepairCate())->getList([
                'village_id' => $adminUser['village_id'],
                'status' => [1],
                'parent_id'=>0
            ],'id,cate_name as subject_name,subject_id',0,0,'sort DESC,id desc');
            if (!empty($subject_list)){
                $subject_list=$subject_list->toArray();
                if (!empty($subject_list)){
                    foreach ($subject_list as $v){
                        $subject[]=[
                            'name'=>'house_repaircategorylist_'.$v['id'],
                            'parentId'=>'house_111158',
                            'id'=>'house_111158_'.$v['id'],
                            'meta'=>[
                                'icon'=>'',
                                'title'=>$v['subject_name'],
                                'show'=>false,
                                'permission'=>'system',
                                'keepAlive'=>'1',
                            ],
                            'component'=>'newRepairCateChildList',
                            'path'=>'/village/village.workOrder.repairCate/newRepairCateChildList_'.$v['id'],
                        ];
                    }
                    $returnArr=array_merge($returnArr,$subject);
                }
            }
        }
        fdump_api(['line' => __LINE__, 'returnArr' => $returnArr], '$house_menu_list', 1);

        //Cache::tag('villageMenuList:menus:'.$village_id)->set($cacheKey,$returnArr,86400*15);
        return $returnArr;
    }


   //查询数据整理递归函数（无限制级别）
    private function arrayPidHouseProcess($data, $res = array(), $pid = '0', $endlevel = '0')
    {
        foreach ($data as $k => $value) {
            if ($value['fid'] == $pid) {
                $res[$value['id']] = $value;
                unset($data[$k]);
                if ($endlevel != '0') {
                    if ($value['level'] != $endlevel) {
                        $child = $this->arrayPidHouseProcess($data, array(), $value['id'], $endlevel);
                    }
                    $res[$value['id']]['menu_list'] = $child;
                } else {
                    $child = $this->arrayPidHouseProcess($data, array(), $value['id']);
                    if (!($child == '' || $child == null)) {
                        $res[$value['id']]['menu_list'] = $child;
                    }
                }
            }
        }
        return $res;
    }

}
