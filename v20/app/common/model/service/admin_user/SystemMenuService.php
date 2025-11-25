<?php
/**
 * 系统后台用户登录权限服务
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 09:01
 */

namespace app\common\model\service\admin_user;
use app\common\model\db\SystemMenu as SystemMenuModel;
use app\common\model\service\AreaService;
use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\ConfigService;

use app\community\model\db\HouseVillageCheckauthApply;
use app\community\model\db\HouseVillageCheckauthDetail;
use app\community\model\service\Device\Hik6000CCameraService;

use app\community\model\service\HouseVillageService;
use app\life_tools\model\db\LifeTools;
use app\life_tools\model\db\LifeToolsTicket;
use app\mall\model\db\MallGoods;
use app\mall\model\db\ShopGoods;
use app\mall\model\db\SystemGoods;
use app\Request;
use think\facade\Db;
use net\http;
class SystemMenuService {
    public $systemMenuObj = null;
    public function __construct()
    {
        $this->systemMenuObj = new SystemMenuModel();
    }

    /**
     * 返回正常显示的菜单
     * @param $systemUser
     * @return array
     */
    public function getShowMenuList($systemUser) {
        if (empty($systemUser)) {
            return [];
        }
        $where=[];
        $ids=[];
        $isCheckApplyHouseAdvert = false;
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if ($jz_open) {
            $dbHouseVillageCheckauthDetail = new HouseVillageCheckauthDetail();
            $whereCheckAuthDetail = ['property_id' => 0, 'village_id' => 0, 'wid' => $systemUser['id'], 'status' => 0];
            $checkAuthDetail = $dbHouseVillageCheckauthDetail->getOne($whereCheckAuthDetail, 'id, apply_id');
            $apply_id = isset($checkAuthDetail['apply_id']) ? intval($checkAuthDetail['apply_id']) : 0;
            $dbHouseVillageCheckauthApply = new HouseVillageCheckauthApply();
            $whereApply = [];
            $whereApply[] = ['xtype', '=', 'house_advert'];
            $whereApply[] = ['id', '=', $apply_id];
            $applyHouseAdvertInfo = $dbHouseVillageCheckauthApply->getOne($whereApply, 'id, xtype');
            if (isset($applyHouseAdvertInfo['id']) && intval($applyHouseAdvertInfo['id']) > 0) {
                $isCheckApplyHouseAdvert = true;
            }
        }
        //todo 菜单栏【社区可视化界面】和【小区广告管理】开关切换
        if(intval(cfg('house_visualize_switch'))){
            !$isCheckApplyHouseAdvert && $ids[]=431;
        }else{
            $ids[]=605;
        }
        //todo 菜单栏【运维管理人员】开关
        if(!intval(cfg('hardware_switch'))){
            $ids[]=631;
        }

        $configCustomizationService=new ConfigCustomizationService();
        $cityCountyManageFuncJudge=$configCustomizationService->getCityCountyManageFuncJudge();
        if($cityCountyManageFuncJudge<1 || (!isset($systemUser['admin_area_type']) || !in_array($systemUser['admin_area_type'],[2,3])) || $systemUser['street_id']>0){
            $ids[]=896;
            $ids[]=897;
            $ids[]=898;
            $ids[]=899;
        }
        if(!empty($ids)){
            $where[] = ['id', 'not in', $ids];
        }
        // 获得所有菜单
        $menuList = $this->getNormalMenuList($where);
        if(!$menuList) {
            return [];
        }

        $flag = false;//echo "<pre>";var_dump($menu_list);exit;
        $is_deliver = false;
        $module = $action = '';
        $tmpMap = array();
        $items = array();

        /*设置了权限且设置时间未更新*/
        if(!empty($systemUser['menus']) && empty($systemUser['root_edit_time'])){
            foreach ($menuList as $k => $v) {
                if($v['module']=='shop' && $v['action']==''){
                    if(in_array($v['id'], $systemUser['menus'])){
                        $is_shop=1;
                    }else{
                        $is_shop=0;
                    }
                    break;
                }
            }
            foreach ($menuList as $k => $v) {
                if($is_shop==1 && ($v['id']==293 || $v['fid']==293)){
                    $systemUser['menus'][] = $v['id'];
                }
            }
        }
        if ($isCheckApplyHouseAdvert && !empty($systemUser['menus']) && !in_array(431, $systemUser['menus'])) {
            if (!in_array(74, $systemUser['menus'])) {
                $systemUser['menus'][] = 74;
            }
            $systemUser['menus'][] = 431;
        }

        $community6000CJudgeConfig = (new Hik6000CCameraService())->judgeConfig();
        foreach($menuList as $key => $value){
            if (isset($value['component']) && $value['component'] == 'Community6000CList' && !$community6000CJudgeConfig) {
                continue;
            }
            if(empty(cfg('wxapp_url')) && ((strtolower($value['module']) == 'wxapp'&&strtolower($value['action'])!= 'wxapp_list') || $value['name'] == '营销活动')) {
                continue;
            }
            if(empty(cfg('open_single_spread')) && (strtolower($value['module']) == 'user' && $value['action'] == 'distributor')) {
                continue;
            }

            if (strtolower($value['module']) == 'deliver' && in_array($value['id'], $systemUser['menus'])) {
                $is_deliver = true;
            }
            
            //****处理权限****//
            $galias = request()->param('galias');
            $modelName = request()->controller;
            $actionName = request()->action;
            if (strtolower($value['module']) == strtolower($modelName) && strtolower($value['action']) == strtolower($actionName) && (empty($galias) OR !in_array($galias, $systemUser['menus_galias']))) {
                if (!empty($systemUser['menus']) && !in_array($value['id'], $systemUser['menus']) && $value['level'] != 3) {
                    $flag = true;
                    continue;
                }
            }
            //****处理权限****//
            
            if($value['is_auth_menu']){
                continue;
            }

            if (cfg('open_merchant_spread') != 1 && $value['id'] == 503) {
                continue;
            }

            if (empty($value['area_access']) && $systemUser['area_id'] && !in_array($value['id'], $systemUser['menus']) && $value['level'] != 3) continue;
            /**********控制账号的菜单显示************/
            if (!empty($systemUser['menus']) && !in_array($value['id'], $systemUser['menus']) && $value['level'] != 3) continue;
            /**********控制账号的菜单显示************/

            if (empty($module) && $value['fid']) {
                $module = ucfirst($value['module']);
                $action = $value['action'];
            }

            if( strpos($value['name'], '订餐')!==false){
                $value['name'] =  str_replace('订餐',cfg('meal_alias_name'),$value['name']);
            }else{
                $value['name'] =  str_replace('餐饮',cfg('meal_alias_name'),$value['name']);
            }
//            $value['name'] =  str_replace('订餐', cfg('meal_alias_name'] ?? '订餐', $value['name']);
//            $value['name'] =  str_replace('餐饮', cfg('meal_alias_name'] ?? '餐饮', $value['name']);
            $value['name'] =  str_replace('快店', cfg('shop_alias_name') ?? '快店', $value['name']);
            if(strpos($value['name'], '社区团购')!==false){
                $value['name'] = str_replace('社区团购', cfg('village_group_alias') ?? '社区团购', $value['name']);
            }elseif (strpos($value['name'], '团购')!==false){
                $value['name'] = str_replace('团购', cfg('group_alias_name') ?? '团购', $value['name']);
            }
            $value['name'] = str_replace('预约', cfg('appoint_alias_name') ?? '预约', $value['name']);
            $value['name'] = str_replace('优惠买单', cfg('cash_alias_name') ?? '优惠买单', $value['name']);
//            $houseName = cfg('house_name') ?: '小区';
//            $singleName = cfg('single_name') ?: '楼栋';
//            $floorName = cfg('floor_name') ?: '单元';
//            $roomName = cfg('room_name') ?: '房间';
//            $roomNames = cfg('room_name') ?: '房屋';
            $houseName = (new HouseVillageService())->getHouseAlias('house_name') ?: '小区';
            $singleName = (new HouseVillageService())->getHouseAlias('single_name') ?: '楼栋';
            $floorName = (new HouseVillageService())->getHouseAlias('floor_name') ?: '单元';
            $roomName = (new HouseVillageService())->getHouseAlias('room_name') ?: '房间';
            $roomNames = (new HouseVillageService())->getHouseAlias('room_name') ?: '房屋';
            $value['name'] = str_replace(
                ['小区','楼栋','单元','房间','房屋'],
                ['{小区}','{楼栋}','{单元}','{房间}','{房屋}'],
                $value['name']);//防止重复替换
            $value['name'] = str_replace(
                ['{小区}','{楼栋}','{单元}','{房间}','{房屋}'],
                [$houseName,$singleName,$floorName,$roomName,$roomNames],
                $value['name']);
            
            $tmpMap[$value['id']] = $value;
            $items[] = $value;
        }
        
        

        $system_menu= array();
        foreach ($items as $item) {
//            if($item['is_hide']==1){
//                $item['fid'] = 0;
//            }
            if (isset($tmpMap[$item['fid']])) {
                $tmpMap[$item['fid']]['menu_list'][$item['id']] = &$tmpMap[$item['id']];
            } else {
                $system_menu[$item['id']] = &$tmpMap[$item['id']];
            }
        }
        unset($tmpMap);

       /* $header = request()->param('header');
        if ($flag) {
            if (!($header == 'Deliver/header' && $is_deliver && 'config' == strtolower($modelName) && 'index' == strtolower($actionName))) {
                if ('index' == strtolower($modelName) && 'main' == strtolower($actionName)) {
                    // $this->redirect(U("$module/$action"));
                    return api_output(0, ['url'=>U("$module/$action")], "跳转");
                } else {
                    return api_output(1111, ['url' => U("$module/$action")], "您还没有这个使用权限，联系管理员开通！");
                    // $this->error('您还没有这个使用权限，联系管理员开通！', U("$module/$action"));
                }
            }
        }*/

        $tmp    =   array();
        foreach($system_menu as $key=>$value){
            if($systemUser['sort_menus']){
                if(isset($systemUser['sort_menus'][$key])){
                    $system_menu[$key]['sort_menu'] =   $systemUser['sort_menus'][$key];
                }else{
                    $system_menu[$key]['sort_menu'] =   0;
                }
            }
        }

        if($systemUser['sort_menus']){
            $system_menu    =   $this->menuSort($system_menu,'sort_menu');
        }

        foreach ($system_menu as $key => $value) {
            if (isset($value['menu_list'])) {
                $value['menu_list'] = array_values($value['menu_list']);
            }
            $system_menu[$key] = $value;
        }

        if ($systemUser['area_id'] && $systemUser['open_admin_area']) {
            $nowArea = (new AreaService)->getAreaByAreaId($systemUser['area_id']);
            $systemUser['area_type'] = $nowArea['area_type'] ;
            $systemUser['area_pid'] = $nowArea['area_pid'] ;
        }

        return $system_menu;
    }


    /**
     * 返回前端需要的菜单格式
     * @param $menuList
     * @param $systemUser
     * @return array
     */   
    public function formartMenuList($menuList,$systemUser=[]) {
        $returnArr = [];
        
        // 首页
        $indeArr['id'] = '999999';
        $indeArr['fid'] = 0;
        $indeArr['icon'] = 'iconshouye';
        $indeArr['name'] = '首页';
        $indeArr['module'] = 'Index';
        $indeArr['action'] = 'pass';
        $indeArr['is_app'] = '0';
        if(cfg('single_system_type') != 'runErrands' && ($systemUser['level'] == 2 || ($systemUser['menus'] && in_array('999999',$systemUser['menus'])))){// 总管理员或者有首页权限且不是跑腿单系统 有新版首页权限 否则跳修改密码页
            $returnArr[] = $this->getMenuField($indeArr,'PlatformIndex');
        }else{
            $returnArr[] = $this->getMenuField($indeArr);
        }
        foreach ($menuList as $key => $value) {
            // 一级菜单
            if (isset($value['menu_list']) && $value['menu_list']) {
                $tmpMenu = $this->getMenuField($value,'RouteView');
            }else{
                $tmpMenu = $this->getMenuField($value,'System');
            }
           
            if (isset($value['menu_list']) && $value['menu_list']) {
                // 二级菜单
                $redirect = '';
                $childArr = [];
                foreach ($value['menu_list'] as $key => $_child) {
                    if($_child['is_hide'] == 1){
                        $tmpChildMenu['fid'] = 0;
                        $tmpChildMenu['is_hide'] = 1;
                    }
                    $tmpChildMenu = $this->getMenuField($_child,'System');
                    if($value['is_app']){//是否应用
                        $tmpChildMenu['app_id'] = $value['id']; // 菜单id
                    }

                    $childArr[] = $tmpChildMenu;
                    if ($key == 0) {
                        $redirect = $tmpChildMenu['path'];
                    }

                    // 三级菜单
                    if (isset($_child['menu_list']) && $_child['menu_list']) {
                        $childArr2 = [];
                        foreach ($_child['menu_list'] as $key => $_child2) {
                            if($_child2['is_hide'] == 1){
                                $tmpChildMenu2['fid'] = 0;
                                $tmpChildMenu2['is_hide'] = 1;
                            }
                            $tmpChildMenu2 = $this->getMenuField($_child2,'System');
                            if($value['is_app']){//是否应用
                                $tmpChildMenu2['app_id'] = $value['id']; // 菜单id
                            }
                            $childArr2[] = $tmpChildMenu2;
                        }
//                        var_dump($childArr2);
                        $returnArr = array_merge($returnArr,$childArr2);
                    }
                }
                $tmpMenu['redirect'] = $redirect; // 父级访问第一个子级
                $returnArr[] = $tmpMenu;

                $returnArr = array_merge($returnArr,$childArr);
            }else{
                $returnArr[] = $tmpMenu;
            }
        }
        return $returnArr;
    }

    /**
     * 返回正常显示的菜单
     * @param $where
     * @return array
     */
    public function getNormalMenuList($where = []) {
        $where[] = ['status','=',1];
        $where[] = ['show','=',1];
        $menuList = $this->systemMenuObj->geMenuList($where);
        if(!$menuList) {
            return [];
        }
        return $menuList->toArray();
    }

    protected function menuSort($array,$sort){
        $mune   =   array();    //菜单
        $arrsa  =   array();    //排序
        foreach($array as $k=>&$v){
            if($v[$sort] == 0){
                $arrsa[]    =   $v;
            }else{
                $mune[] =   $v;
            }
        }
        $muneSort = $this->mySort($mune,$sort,SORT_DESC,SORT_NUMERIC);
        $arrsaSort = $this->mySort($arrsa,'sort',SORT_DESC,SORT_NUMERIC);
        $arr    =   array_merge_recursive($muneSort,$arrsaSort);
        return $arr;
    }

    # 多维数组排序
    protected function mySort($arrays, $sortKey, $sortOrder=SORT_ASC, $sortType=SORT_NUMERIC){
        $keyArrays=[];
        if(is_array($arrays)){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $keyArrays[] = $array[$sortKey];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($keyArrays,$sortOrder,$sortType,$arrays);
        return $arrays;
    }

    // 组合前端所需字段
    protected function getMenuField($value,$component= 'Index'){
        $menuArr = [];
        $menuArr['name'] = 'menu_'.$value['id']; // 菜单name，保持唯一
        $menuArr['parentId'] = $value['fid']; // 父菜单id，0表示为一级菜单
        $menuArr['id'] = $value['id']; // 菜单id
        if($value['is_app']){
            $menuArr['app_id'] = $value['id']; // 菜单id
        }

        // 菜单显示配置
        $menuArr['meta']['icon'] = $value['icon']; // 菜单左侧图标，非必须
        $menuArr['meta']['title'] = $value['name']; // 菜单名称
        $menuArr['meta']['show_app'] = isset($value['is_hide_app']) && $value['is_hide_app']==1 ? false : true; // 菜单是否在应用中心左侧菜单栏显示
        $menuArr['meta']['show'] = isset($value['is_hide']) && $value['is_hide']==1 ? false : true; // 菜单是否在左侧菜单栏显示
        $menuArr['meta']['permission'] = 'system'; // 菜单权限标识，无则所有角色可见
        $menuArr['component'] = isset($value['component'])&&$value['component'] ? $value['component'] : $component; // 对应前端组件名称
        $menuArr['path'] = isset($value['path'])&&$value['path'] ? $value['path'] : '/common/platform.iframe/'.$menuArr['name']; // 显示路由

        $menuArr['src'] = urlencode($this->getUrl($value['module'], $value['action'], [], cfg('site_url').'/')); // 真实路由
	$menuArr['meta']['not_audit_num'] = 0; // 未审核数量

        // $menuArr['redirect'] = $menuArr['path'];


        //查询未审核的外卖商品
        if($value['id'] == '718'){
//            $shopGoodsNum = (new ShopGoods())->where(['audit_status'=>0])->count();
            $shopGoodsNum = (new ShopGoods())->getCount(['a.audit_status'=>0]);
            $systemGoodsNum = (new SystemGoods())->where(['status'=>0])->count();
            $menuArr['meta']['not_audit_num'] = $shopGoodsNum + $systemGoodsNum;
        }
        //查询未审核的商城商品
        if($value['id'] == '710'){
            $menuArr['meta']['not_audit_num'] = (new MallGoods())->getAuditByCondition([['a.is_del','=',0],['a.audit_status','=',0],['c.status', '<>', 4]],false,'','','');
//            $menuArr['meta']['not_audit_num'] = (new MallGoods())->where(['is_del'=>0,'audit_status'=>0])->count();
        }
        //查询未审核的景点
        if($value['id'] == '707'){
            $menuArr['meta']['not_audit_num'] = (new LifeTools())->where(['is_del'=>0,'audit_status'=>0,'type'=>'scenic'])->count() +
                (new LifeToolsTicket())->getAuditList(['r.is_del'=>0,'r.audit_status'=>0,'t.is_del'=>0,'t.type'=>'scenic'],false,0);
        }
        //查询未审核的体育
        if($value['id'] == '708'){
            $menuArr['meta']['not_audit_num'] = (new LifeTools())->where([['is_del','=',0],['audit_status','=',0],['type','IN',['stadium','course']]])->count() +
                (new LifeToolsTicket())->getAuditList([['r.is_del','=',0],['r.audit_status','=',0],['t.is_del','=',0],['t.type','IN',['stadium','course']]],false,0);
        }
        if($menuArr['meta']['not_audit_num'] > 99){
            $menuArr['meta']['not_audit_num'] = '99+';
        }
        return $menuArr;
    }

    /**
     * 获得网站右侧信息
     * @param $param 参数
     * @return array
     */
    public function getRightMenu($param, $systemUser){
        $returnArr = [];

        // 显示类型 base-基本信息 help-帮助文档
        $returnArr['show_type'] = 'base';
        // 是否显示友情链接
        $returnArr['show_friendly_link'] = '0';
        // 官方动态
        $returnArr['show_news'] = 0;
        // 是否显示提交售后工单
        $returnArr['show_feedback'] = 0;

        // 会员信息
        $systemUser['last_time'] = date('Y-m-d H:i:s',$systemUser['last_time']);
        $showAccount = '超级管理员';
        if ($systemUser['level'] == 1) {
            if ($systemUser['area_id']) {
                $areaServiceObj = new AreaService();
                $area = $areaServiceObj->getAreaByAreaId($systemUser['area_id']);
                $showAccount = $area['area_name'] . '管理员';
            }
        }else if($systemUser['level'] == 2) {
            $showAccount = '超级管理员';
            $returnArr['show_feedback'] = !is_null(cfg('show_feedback')) ? intval(cfg('show_feedback')) : 1;
        } else {
            $showAccount = '普通管理员';
        }
        $systemUser['group'] = $showAccount;
        $returnArr['user'] = $systemUser;

        // 系统信息
        $mysqlVersion = Db::query('select VERSION()');
        $server_info = array(
            'PHP运行环境' => PHP_OS,
            'PHP运行方式' => php_sapi_name(),
            'PHP版本' => PHP_VERSION,
            'MYSQL版本' => $mysqlVersion[0]['VERSION()'],
            '上传附件限制' => ini_get('upload_max_filesize'),
            '执行时间限制' => ini_get('max_execution_time') . '秒',
            '磁盘剩余空间' => round((@disk_free_space(".") / (1024 * 1024 * 1024)), 4) . ' G',
        );
        $returnArr['server_info'] = $server_info;
        if ($systemUser['area_id'] != 0) {
            $now_area = (new AreaService())->getAreaByAreaId($systemUser['area_id']);
            $returnArr['now_area'] = $now_area;
        }

        // git支持
        $returnArr['git'] = invoke_cms_model('Admin/getGitInfo')['retval'] ?? '';

        // 官方动态
        $url = 'https://o2o-service.pigcms.com/updatetips_v20.php';
        $where = [
            'name' => 'system_version'
        ];
        $version = (new ConfigService())->getDataOne($where);
        $data = [
            'soft_version' => $version['value'],
            'domain' => request()->server('HTTP_HOST'),
        ];

        $returnArr['system_news'] = [];
        if($systemUser['level'] == 2){
            $res = Http::curlPost($url,$data);
            fdump($res,'system_news',1);
            if($res['errcode'] === 0 && isset($res['data']) ){
                $returnArr['show_news'] = 1;
                $returnArr['system_news'] = $res['data'];
                $returnArr['system_news']['update_history_count'] = count($returnArr['system_news']['update_history']);
            }
        }


        // 友情链接
        $returnArr['friendly_link'] = [];

        return $returnArr;
    }

    
    /**
     * 获得原网站路径
     * @param $modelName 控制器名
     * @param $actionName 方法名
     * @param $param 参数
     * @param $siteUrl 网站跟目录
     * @return string
     */
    public function getUrl($modelName, $actionName, $param = [], $siteUrl = '', $indexFile = ''){
        if (!$modelName || !$actionName) {
            return '';
        }
        
        if (empty($siteUrl)) {
            $siteUrl = request()->server('REQUEST_SCHEME').'://'.request()->server('SERVER_NAME').'/';
        }

        $retuenUrl = $siteUrl .($indexFile ? $indexFile : 'admin.php') .'?g=System&c=' . ucfirst($modelName) . '&a=' . $actionName;

        if ($param) {
            foreach ($param as $key => $value) {
                $retuenUrl .= '&' . $key . '=' . $value;
            }
        }

        if($indexFile=='plugin.php'){
            $retuenUrl .= '&plugin=index';
        }
        return $retuenUrl;
    }

    /**
     * 替换自定义设置的菜单名称
     * @param $value 控制器名
     * @return array
     */
    public function replaceMenuName($value){
        if( strpos($value['plugin_name'], '订餐')!==false){
            $value['plugin_name'] =  str_replace('订餐',cfg('meal_alias_name'),$value['plugin_name']);
        }else{
            $value['plugin_name'] =  str_replace('餐饮',cfg('meal_alias_name'),$value['plugin_name']);
        }
        $value['plugin_name'] =  str_replace('订餐', cfg('meal_alias_name') ?? '订餐', $value['plugin_name']);
        $value['plugin_name'] =  str_replace('餐饮', cfg('meal_alias_name') ?? '餐饮', $value['plugin_name']);
        $value['plugin_name'] =  str_replace('快店', cfg('shop_alias_name') ?? '快店', $value['plugin_name']);
        $value['plugin_name'] =  str_replace('外卖', cfg('shop_alias_name') ?? '快店', $value['plugin_name']);
        $value['plugin_name'] = str_replace('团购', cfg('group_alias_name') ?? '团购', $value['plugin_name']);
        $value['plugin_name'] = str_replace('预约', cfg('appoint_alias_name') ?? '预约', $value['plugin_name']);
        $value['plugin_name'] = str_replace('优惠买单', cfg('cash_alias_name') ?? '优惠买单', $value['plugin_name']);
        return $value;
    }

    /**
     * 获得一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where){
        $res = $this->systemMenuObj->getOne($where);
        if(!$res) {
            return [];
        }
        return $res->toArray();
    }
}