<?php
/**
 * 商家后台菜单
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 09:01
 */

namespace app\merchant\model\service;

use app\merchant\model\db\NewMerchantMenu as MerchantMenuModel;
use app\community\model\service\HouseVillageService;
use app\merchant\model\db\MerchantUserStations;
use Exception;

class MerchantMenuService
{
    public $merchantMenuModel = null;

    public function __construct()
    {
        $this->merchantMenuModel = new MerchantMenuModel();
    }

    /**
     * 返回正常显示的菜单
     * @param $merchantUser
     * @param $config
     * @return array
     */
    public function getShowMenuList($merchantUser, $config = [])
    {
        if (empty($merchantUser)) {
            return [];
        }

        // 实时查找商家的权限
        $merchant = (new MerchantService())->getMerchantByMerId($merchantUser['mer_id']);
        if (empty($merchant['menus'])) {
            return [];
        } else {
            $merchantUser['menus'] = explode(",", $merchant['menus']);
        }

        // 开启商家权限购买
        if (cfg('buy_merchant_auth') == 1 && $merchantUser['try_end_time'] != 0 && $merchantUser['try_end_time'] < time()) {
            $date_auth = array();
            $date_auth['authority_group_id'] = 0;
            $date_auth['try_end_time'] = 0;
            (new MerchantService())->updateByMerId($merchantUser['mer_id'], $date_auth);
        }

        // 开启商家权限购买
        if (cfg('buy_merchant_auth') == 1 && $merchantUser['merchant_end_time'] != 0 && $merchantUser['merchant_end_time'] < time()) {
            $date_auth = array();
            $date_auth['authority_group_id'] = 0;
            $date_auth['merchant_end_time'] = 0;
            (new MerchantService())->updateByMerId($merchantUser['mer_id'], $date_auth);
        }

        // 获得所有菜单 TODO加缓存
        $merchantMenuList = $this->getNormalMenuList();
        if (!$merchantMenuList) {
            return [];
        }

        $flag = false;

        // 查看绑定小区
        $bindVillage = [];
        if($merchantUser['village_ids']){
            $conditionVillage = [];
            $conditionVillage[] = ['village_id', 'in', $merchantUser['village_ids']];
            $conditionVillage[] = ['status', '<>', 2];
            $bindVillage = (new HouseVillageService())->getList($conditionVillage);
        }

        $merchantMenu = [];
        foreach ($merchantMenuList as $key => $value) {
            if (empty(cfg('wxapp_url')) && strtolower($value['module']) == 'wxapp') {
                unset($merchantMenuList[$key]);
            }
            if (empty(cfg('merchant_ownpay')) && 'ownpay' == strtolower($value['module'])) {
                unset($merchantMenuList[$key]);
            }
            if (empty(cfg('is_open_market')) && 'market' == strtolower($value['module'])) {
                unset($merchantMenuList[$key]);
            }
            // 平台商家积分
            if (!cfg('open_score_pay_back_mer') && 'merchant_get_score' == strtolower($value['module'])) {
                unset($merchantMenuList[$key]);
            }
            // 绑定社区
            if (!$bindVillage && 'house' == strtolower($value['module'])) {
                unset($merchantMenuList[$key]);
            }
            // 商家联盟统计
            if (!cfg('mer_card_union') && 'card_union_statistics' == strtolower($value['module'])) {
                unset($merchantMenuList[$key]);
            }

        }

        $storeMenuList = array();

        // 商家菜单总数
        $where = [
            'status' => 1,
            'show' => 1
        ];
        $menusCount = $this->merchantMenuModel->getCount();
//        if (!empty($merchantUser['menus']) && empty($merchantUser['root_edit_time'])) {
//            foreach ($merchantMenuList as $k => $v) {
//                /*是否开启快店权限*/
//                if ($v['module'] == 'Shop' && $v['action'] == 'index') {
//                    if (in_array($v['id'], $merchantUser['menus'])) {
//                        $shopRoot = 1;
//                    } else {
//                        $shopRoot = 0;
//                    }
//                    break;
//                }
//            }
//            foreach ($merchantMenuList as $k => $v) {
//                /*商城的权限*/
//                if ($shopRoot == 1 && $v['module'] == 'Mall' && $v['action'] == 'index') {
//                    $merchantUser['menus'][] = $v['id'];
//                }
//            }
//        }
        if (empty($merchantUser['menus'])) {
            $merchantUser['menus'] = [];
        }
        if (cfg('buy_merchant_auth') && !in_array(10013, $merchantUser['menus'])) {
            $merchantUser['menus'][] = 10013;
        }

        foreach ($merchantMenuList as $value) {
            //****处理权限****//
            if ($value['action'] == 'buy_merchant_service' && (!cfg('buy_merchant_auth'))) {
                continue;
            }
            if ($value['module'] == 'Weidian' && $value['action'] == 'index') {
                if (!empty($merchantUser['menus']) && !in_array($value['id'], $merchantUser['menus'])) {
                    $flag = true;
                }
            }

            // if ($value['module'] == MODULE_NAME && $value['action'] == ACTION_NAME && ACTION_NAME!='buy_merchant_service') {
            //     if (!empty($merchantUser['menus']) && !in_array($value['id'], $merchantUser['menus'])) {
            //         if (MODULE_NAME == 'Cashier' && ACTION_NAME == 'index') {
            //             cfg('is_cashier') = 0;
            //             $this->assign('config',$this->config);
            //         }
            //         if(cfg('buy_merchant_auth')){
            //             redirect(U('Merchant_money/buy_merchant_service'));
            //         }else{
            //             $this->error(L_('您还没有这个使用权限，联系管理员开通！'));
            //         }
            //     }
            // }

            //****处理权限****//
            if ($value['module'] == 'Weixin' && (empty(cfg('is_open_oauth')) && empty($merchantUser['is_open_oauth']))) continue;
            if ($value['module'] == 'Weidian' && (empty(cfg('is_open_weidian')) && empty($merchantUser['is_open_weidian']))) continue;
            if (($value['module'] == 'Scenic' || $value['module'] == 'Scenic_config' || $value['module'] == 'Scenic_ticket' || $value['module'] == 'Scenic_park' || $value['module'] == 'Scenic_guide' || $value['module'] == 'Scenic_reply' || $value['module'] == 'Scenic_money') && empty($merchantUser['is_open_scenic'])) continue;
            if ($value['module'] == 'Gift' && empty(cfg('is_open_merchant_gift'))) continue;

            /**********控制商家的菜单显示************/
            if (!empty($merchantUser['menus']) && !in_array($value['id'], $merchantUser['menus'])) continue;
            /**********控制商家的菜单显示************/
            $select_module = explode(',', $value['select_module']);
            $select_action = explode(',', $value['select_action']);

            //此处不能加 多语言判断， 词语是在数据库里的。
            if($value['name'] == '社区团购'){
                $value['name'] = str_replace(array('社区团购'), array(cfg('village_group_alias')), $value['name']);
            }else{
                $value['name'] = str_replace(array('团购', '订餐', '快店', '预约'), array(cfg('group_alias_name'), cfg('meal_alias_name'), cfg('shop_alias_name'), cfg('appoint_alias_name')), $value['name']);
            }
            cfg('appoint_worker_name') && $value['name'] = str_replace('技师', cfg('appoint_worker_name'), $value['name']);
            $merchantMenu[] = $value;
            $storeMenuList[] = $value;
        }
        $merchantMenu = $this->arrayPidProcess($merchantMenu);


//        if ($flag && MODULE_NAME == 'Weidian') $this->error(L_('您还没有这个使用权限，联系管理员开通！'));

        foreach ($merchantMenu as $key => $menu) {
            if (!empty($menu['menu_list'])) {
                foreach ($menu['menu_list'] as $index => $val) {
                    if (isset($val['is_active']) && $val['is_active']) {
                        $merchantMenu[$val['fid']]['is_active'] = true;
                    }
                    //人工客服菜单判断
                    if (cfg('jg_im_appkey') && cfg('jg_im_masterkey') && $val['module'] == 'Customer' && $val['action'] == 'service') {
                        unset($merchantMenu[$key]['menu_list'][$index]);
                    }
                }
            }
        }
        if (0 == cfg('is_open_merchant_foodshop_discount')) {
            foreach ($merchantMenu as $kk => $vv) {
                if ($vv['id'] == '2') {
                    unset($merchantMenu[$kk]["menu_list"][10020]);
                }
            }
        }

        return $merchantMenu;
    }


    /**
     * 返回前端需要的菜单格式
     * @param $menuList
     * @param $siteUrl
     * @return array
     */
    public function formartMenuList($menuList,$merchantUser)
    {
        $returnArr = [];

        // 首页
        $indeArr['id'] = '999999';
        $indeArr['fid'] = 0;
        $indeArr['icon'] = 'iconshouye';
        $indeArr['name'] = '首页';
        $indeArr['module'] = 'Index';
        $indeArr['action'] = 'index';
        $returnArr[] = $this->getMenuField($indeArr, 'Index');

        foreach ($menuList as $key => $value) {
            if ($value['id'] == 1) {
                continue;
            }
            // 一级菜单
            $tmpMenu = $this->getMenuField($value, 'RouteView');

            if (isset($value['menu_list']) && $value['menu_list']) {
                // 二级菜单
                $redirect = '';
                $childArr = [];

                foreach ($value['menu_list'] as $key => $_child) {
					if(!$_child['is_hide']){
						$showChild = true;
					}

                    // 二级菜单
                    if (isset($_child['menu_list']) && $_child['menu_list']) {
                        $showChild2 = false;
                        foreach ($_child['menu_list'] as $key => $_child2) {
                            if (!$_child2['is_hide']) {
                                $showChild2 = true;
                            }
                        }
                        $component = $showChild2 ? 'RouteView' : 'System';
                    }else{
                        $component = 'System';
                    }

                    $tmpChildMenu = $this->getMenuField($_child,$component);
                    $tmpChildMenu['meta']['icon'] = null;
                    if ($key == 0) {
                        $redirect = $tmpChildMenu['path'];
                    }
                    if (stripos($tmpChildMenu['path'], '/common/merchant.custom/index') !== false) {
                        $tmpChildMenu['meta']['target'] = '_self';
                        $tmpChildMenu['path'] = cfg('site_url') . '/v20/public/platform/#/common/merchant.custom/index?source=merchant&source_id='.$merchantUser['mer_id'];
                    }
                    // 三级菜单
                    if (isset($_child['menu_list']) && $_child['menu_list']) {
                        $childArr2 = [];
                        $showChild2 = false;
                        foreach ($_child['menu_list'] as $key => $_child2) {
                            if (!$_child2['is_hide']) {
                                $showChild2 = true;
                            }
                            $tmpChildMenu2 = $this->getMenuField($_child2, 'System');
                            $tmpChildMenu2['meta']['icon'] = null;
                            $childArr2[] = $tmpChildMenu2;
                        }
                        if ($showChild2 == false) {// 没有子集
                            $parantMenu2 = $tmpChildMenu;
                            $parantMenu2['parentId'] = $parantMenu2['id'];
                            $parantMenu2['id'] = $parantMenu2['id'] . '_s';
                            $parantMenu2['meta']['show'] = false;
                            $parantMenu2['name'] = $parantMenu2['name'] . '_s';
                            $tmpChildMenu['component'] = 'RouteView';
                            $childArr[] = $parantMenu2;
                            $tmpChildMenu['redirect'] = $parantMenu2['path'];
                        }
                        $returnArr = array_merge($returnArr, $childArr2);
                    }
                    $childArr[] = $tmpChildMenu;
                }
                $tmpMenu['redirect'] = $redirect; // 父级访问第一个子级

                if ($showChild == false) {
                    $parantMenu = $tmpMenu;
                    $parantMenu['parentId'] = $parantMenu['id'];
                    $parantMenu['id'] = 's_' . $parantMenu['id'];
                    $parantMenu['meta']['show'] = false;
                    $parantMenu['name'] = $parantMenu['name'] . '_s';
                    $parantMenu['component'] = 'RouteView';

                    $returnArr[] = $parantMenu;
                    $tmpMenu['redirect'] = $parantMenu['path'];
                }
                $returnArr[] = $tmpMenu;

                $returnArr = array_merge($returnArr, $childArr);
            } else {
                $returnArr[] = $tmpMenu;
            }
        }
        fdump($returnArr,'merchantMenu');
        return $returnArr;
    }

    /**
     * 返回正常显示的菜单
     * @param $where
     * @return array
     */
    public function getNormalMenuList($where = [], $order = [])
    {
        $where['status'] = 1;
        $where['show'] = 1;
        if (empty($order)) {
            $order = [
                'sort' => 'desc',
                'fid' => 'ASC',
                'id' => 'ASC',
            ];
        }
        $menuList = $this->merchantMenuModel->getMenuList($where, $order);
        // var_dump($this->merchantMenuModel->getLastSql());
        if (!$menuList) {
            return [];
        }
        return $menuList->toArray();
    }

    protected function menuSort($array, $sort)
    {
        $mune = array();    //菜单
        $arrsa = array();    //排序
        foreach ($array as $k => &$v) {
            if ($v[$sort] == 0) {
                $arrsa[] = $v;
            } else {
                $mune[] = $v;
            }
        }
        $muneSort = $this->mySort($mune, $sort, SORT_DESC, SORT_NUMERIC);
        $arrsaSort = $this->mySort($arrsa, 'sort', SORT_DESC, SORT_NUMERIC);
        $arr = array_merge_recursive($muneSort, $arrsaSort);
        return $arr;
    }

    # 多维数组排序
    protected function mySort($arrays, $sortKey, $sortOrder = SORT_ASC, $sortType = SORT_NUMERIC)
    {
        if (is_array($arrays)) {
            foreach ($arrays as $array) {
                if (is_array($array)) {
                    $keyArrays[] = $array[$sortKey];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($keyArrays, $sortOrder, $sortType, $arrays);
        return $arrays;
    }

    // 组合前端所需字段
    protected function getMenuField($value, $component = 'Index')
    {
        $menuArr = [];
        $menuArr['name'] = 'menu_' . $value['id']; // 菜单name，保持唯一
        $menuArr['parentId'] = $value['fid']; // 父菜单id，0表示为一级菜单
        $menuArr['id'] = $value['id']; // 菜单id

        // 菜单显示配置
        $menuArr['meta']['icon'] = $value['icon']; // 菜单左侧图标，非必须
        $menuArr['meta']['title'] = $value['name']; // 菜单名称
        $menuArr['meta']['show'] = isset($value['is_hide']) && $value['is_hide'] == 1 ? false : true; // 菜单是否在左侧菜单栏显示
        $menuArr['meta']['permission'] = 'system'; // 菜单权限标识，无则所有角色可见
        $menuArr['component'] = isset($value['component']) && $value['component'] ? $value['component'] : $component; // 对应前端组件名称
        $menuArr['path'] = isset($value['path']) && $value['path'] ? $value['path'] : '/merchant/merchant.iframe/' . $menuArr['name']; // 显示路由
        $menuArr['src'] = urlencode($this->getUrl($value['module'], $value['action'])); // 真实路由
        // $menuArr['redirect'] = $menuArr['path'];
        return $menuArr;
    }


    /**
     * 查询数据整理递归函数（无限制级别）
     */
    function arrayPidProcess($data, $res = array(), $pid = '0', $endlevel = '0')
    {
        foreach ($data as $k => $value) {
            /**********控制商家的菜单显示************/
            if ($value['is_hide'] == 1) {
                //$value['fid'] = 0;
            }
            if ($value['fid'] == $pid) {
                $select_module = explode(',', $value['select_module']);
                $select_action = explode(',', $value['select_action']);

                $res[$value['id']] = $value;
                unset($data[$k]);
                if ($endlevel != '0') {
                    if ($value['level'] != $endlevel) {
                        $child = $this->arrayPidProcess($data, array(), $value['id'], $endlevel);
                    }
                    $res[$value['id']]['menu_list'] = $child;
                } else {
                    $child = $this->arrayPidProcess($data, array(), $value['id']);
                    if (!($child == '' || $child == null)) {
                        $res[$value['id']]['menu_list'] = $child;
                    }
                }
            }
        }

        return $res;
    }


    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where)
    {
        $detail = $this->merchantMenuModel->getOne($where);
        if (!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 获得原网站路径
     * @param $modelName 控制器名
     * @param $actionName 方法名
     * @param $param 参数
     * @param $siteUrl 网站跟目录
     * @return string
     */
    protected function getUrl($modelName, $actionName, $param = [], $siteUrl = '')
    {
        if (!$modelName || !$actionName) {
            return '';
        }

        if (empty($siteUrl)) {
            $siteUrl = request()->server('REQUEST_SCHEME') . '://' . request()->server('SERVER_NAME') . '/';
        }

        $retuenUrl = $siteUrl . '/merchant.php?g=Merchant&c=' . ucfirst($modelName) . '&a=' . $actionName;

        if ($param) {
            foreach ($param as $key => $value) {
                $retuenUrl .= '&' . $key . '=' . $value;
            }
        }
        return $retuenUrl;
    }

    public function formatUserAccountMenuList($menus)
    {
        $data = [];
        foreach ($menus as $v) {
            $childrenData = [
                'id'       => $v['id'],
                'title'    => $v['name'],
                'children' => []
            ];
            if(!empty($v['menu_list']) && is_array($v['menu_list'])){
                $childrenData['children'] = $this->formatUserAccountMenuList($v['menu_list']);
            }
            array_push($data, $childrenData);
        }

        return $data;
    }

    /**
     * 根据子账号所在岗位权限，过滤商家权限
     *
     * @param array $menu
     * @param int $stationId
     * @return void
     * @author: zt
     * @date: 2023/04/13
     */
    public function filterBySubAccountStation($menus, $stationId)
    {
        $station = (new MerchantUserStations())->where(['id' => $stationId, 'status' => 1])->find();
        if (!$station) {
            throw new Exception(L_('商家岗位已删除或者禁用'));
        }
        $stationMenu = explode(',', $station->menus);
        if (in_array(1, $stationMenu)) {
            $stationMenu[] = 999999;
        }
        $newMenu = array_filter($menus, function ($r) use ($stationMenu) {
            return in_array(intval($r['id']), $stationMenu);
        });
        return array_values($newMenu);
    }
}