<?php

/**
 * 商店员后台菜单
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/07/09 17:41
 */

namespace app\merchant\model\service\storestaff;

use app\common\model\service\admin_user\SystemMenuService;
use app\common\model\service\config\AppapiAppConfigService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use app\merchant\model\service\store\MerchantStoreKefuService;
use app\common\model\service\plugin\PluginService;
use app\community\model\service\ParkCouponsShopService;
use app\merchant\model\service\MerchantMenuService;
use app\merchant\model\service\MerchantService;
use app\shop\model\db\MerchantStoreShop;
use app\shop\model\service\store\MerchantStoreShopService;
use net\Http;
use token\Ticket;
use token\Token;

class MenuService
{
     /**
     * 返回每个业务的PC端链接
     * @param $business string 业务类型
     * @return string
     */
    public function getPcMenuUrl($business='')
    {
        $url = cfg('site_url') . '/v20/public/platform/#/storestaff/storestaff.index/index';
        switch($business){
            case 'group' : 
                $url = $this->getUrl('Store', 'group_list');
                break;
            case 'scenic':
                $url = $this->getUrl('Store', 'ticket_order_list');
                break;
            case 'foodshop':
                if (cfg('store_open_table_type') == 1) {
                    $action = 'tmp_table';
                } else {
                    $action = 'foodshop';
                }
                $url = $this->getUrl('Store', $action);
                break;
            case 'dining':
                $url = cfg('site_url') . '/v20/public/platform/#/storestaff/storestaff.cashier/cashier/order';
                break;
            case 'shop':
                $url = $this->getUrl('Store', 'shop_list');
                break;
            case 'mall':
                $url = $this->getUrl('Store', 'shop_list', ['shop_type' => 2]);
                break;
            case 'mall_new':
                $url = cfg('site_url') . '/v20/public/platform/#/storestaff/storestaff.mall/mall/orderCopy';
                break;
            case 'appoint':
                $url = $this->getUrl('Store', 'appoint_list');
                break;
            case 'cash':// 快速买单
                $url = $this->getUrl('Store', 'store_order');
                break;
            case 'store_arrival':// 店内收银
                $url = $this->getUrl('Store', 'store_arrival');
                break;
            case 'report':// 报表统计
                $url = $this->getUrl('Store', 'report');
                break;
            case 'coupon':// 优惠券
                $url = $this->getUrl('Store', 'coupon_list');
                break;
            case 'physical_card':// 实体卡管理
                $url = $this->getUrl('Store', 'physical_card');
                break;
            case 'store':// 实体卡管理
                $url = $this->getUrl('Config', 'store');
                break;
            case 'money_list':// 店铺余额
                $url = $this->getUrl('Store', 'money_list');
                break;
            case 'sub_card':// 免单套餐
                $url = $this->getUrl('Store', 'sub_card');
                break;
        }
        return $url;
    }

    /**
     * 返回app顶部显示的菜单
     * @param $where
     * @return array
     */
    public function getAppTopMenuList($merchentStore = [], $staffUser = [])
    {
        $agent = request()->agent; // 访问来源

        // 功能菜单
        $menuList = [];

        if (empty(cfg('pay_in_store'))) {
            return [];
        }

        // 店内收银
        if (cfg('is_cashier') || cfg('pay_in_store')) {
            $menuList[] = [
                'name' => L_('收银'),
                "business_type" =>  'store_arrival', // 业务类型
                "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                'url' => $this->getAppUrl('store_arrival_top'),
                'image' => 'store_arrival_top',
                'need_perfect_store' => false, //是否需要完善店铺信息
                'image_type' => 'local', // 图片类型 local-本地 url-远程
            ];
        }

        $menuList[] = [
            'name' => L_('零售'),
            "business_type" =>  'retail', // 业务类型
            "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
            'url' => $this->getAppUrl('retail'),
            'image' => 'retail_top',
            'need_perfect_store' => false, //是否需要完善店铺信息
            'image_type' => 'local', // 图片类型 local-本地 url-远程
        ];

        $menuList[] = [
            'name' => L_('扫一扫'),
            "business_type" =>  'scan', // 业务类型
            "url_type" => 'scan', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
            'url' => '',
            'image' => 'scan_top',
            'need_perfect_store' => false, //是否需要完善店铺信息
            'image_type' => 'local', // 图片类型 local-本地 url-远程
        ];

        return $menuList;
    }

    /**
     * 返回正常显示的菜单
     * @param $where
     * @return array
     */
    public function getMenuList($merchentStore = [], $staffUser = [])
    {
        $agent = request()->agent; // 访问来源
        $appVersion = request()->param('app_version'); // 访问来源
        $siteUrl =  request()->server('REQUEST_SCHEME') . '://' . request()->server('SERVER_NAME');
        $isPc = true;
        if ($agent != 'pc') {
            $isPc = false;
        }

        
        // 构建小程序所需参数      
        // 设备id
        $deviceId = request()->param('Device-Id') ?? '';
        // 获得老店员app ticket
        $ticket = Token::createToken($staffUser['id']);
        $params = [
            'device_id' => $deviceId,
            'ticket' => $ticket,
            'domain' => $siteUrl,
        ];
        $appData = [
            'appid' => '__UNI__8799035',
            'path' => '',
            'arguments' => json_encode($params, JSON_UNESCAPED_SLASHES),
        ];
        
        // 功能菜单
        $menuList = [];
        $isSingleShopSystem = cfg('single_system_type') == 'shop';
        // 团购
        if (!$isSingleShopSystem && isset($merchentStore['have_group']) && $merchentStore['have_group']) {
            $menuList[] = [
                'name' => L_(cfg('group_alias_name')),
                "business_type" =>  'group', // 业务类型
                "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                'url' => $isPc ? $this->getUrl('Store', 'group_list') : $this->getAppUrl('group'),
                'image' => $isPc ? cfg('site_url') . '/v20/public/static/storestaff/images/index/group.png' : 'group',
                'need_perfect_store' => false, //是否需要完善店铺信息
                'image_type' => $isPc ? 'url' : 'local', // 图片类型 local-本地 url-远程
            ];
        }
        
        // 景区
        if(isset($merchentStore['is_open_scenic']) && $merchentStore['is_open_scenic'] == 1){
            if (!$isSingleShopSystem && $isPc && isset($staffUser['is_verify_ticket']) && $staffUser['is_verify_ticket']) {
                $menuList[] = [
                    'name' => L_(cfg('scenic_alias_name')) ?: L_('景区'),
                    "business_type" =>  'scenic', // 业务类型
                    'url' => $this->getUrl('Store', 'ticket_order_list'),
                    'image' => cfg('site_url') . '/v20/public/static/storestaff/images/index/scenic.png',
                    'need_perfect_store' => false, //是否需要完善店铺信息
                ];
            }
        }

        // 餐饮
        $hasMeal = false;
        $merchentStoreFoodshop = (new MerchantStoreFoodshopService())->getOne(['store_id' => $merchentStore['store_id']]);
        if (!$isSingleShopSystem && isset($merchentStore['have_meal']) && $merchentStore['have_meal'] && !empty($merchentStoreFoodshop)) {
            // 餐饮店铺信息

            // 查看有没有餐饮菜单餐饮管理订餐管理
            $menu = (new MerchantMenuService())->getOne([['name', 'in', '餐饮管理,订餐管理'],['module','=','Foodshop'],['status','=',1]]);
            if($menu){
                if (cfg('store_open_table_type') == 1) {
                    $action = 'tmp_table';
                } else {
                    $action = 'foodshop';
                }
                //应用中心不存在老版餐饮，则隐藏老餐饮的入口
                $plugin = (new PluginService())->getOne(['plugin_name' => '餐饮管理','plugin_label'=>'foodshop','status'=>1,'show'=>1]);
                if ($plugin) {
                    $hasMeal = true;
                    $menuList[] = [
                        'name' => L_(cfg('meal_alias_name')),
                        "business_type" =>  'foodshop', // 业务类型
                        "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                        'url' => $isPc ? $this->getUrl('Store', $action) : $this->getAppUrl('foodshop'),
                        'image' => $isPc ? cfg('site_url') . '/v20/public/static/storestaff/images/index/foodshop.png' : 'foodshop',
                        'need_perfect_store' => $merchentStoreFoodshop ? false : true, //是否需要完善店铺信息
                        'image_type' => $isPc ? 'url' : 'local', // 图片类型 local-本地 url-远程
                    ];
                }
            }
        }

        // 餐饮2.0
        if (!$isSingleShopSystem && isset($merchentStore['have_meal']) && $merchentStore['have_meal'] && !empty($merchentStoreFoodshop)) {
            // 餐饮店铺信息
            $tempMenu = [
                'name' => ($hasMeal ? L_('新版X1', cfg('meal_alias_name')) : L_(cfg('meal_alias_name'))),
                "business_type" =>  'dining', // 业务类型
                "url_type" => 'miniapp', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                'url' => $isPc ? cfg('site_url') . '/v20/public/platform/#/storestaff/storestaff.cashier/cashier/order' : $this->getAppUrl('dining'),
                'image' => $isPc ? cfg('site_url') . '/v20/public/static/storestaff/images/index/foodshop.png' : 'dining',
                'need_perfect_store' => $merchentStoreFoodshop ? false : true, //是否需要完善店铺信息
                'image_type' => $isPc ? 'url' : 'local', // 图片类型 local-本地 url-远程
            ];
            if (!$isPc) {
                // 设备id
                $deviceId = request()->param('Device-Id') ?? '';
                // 获得老店员app ticket
                $ticket = Token::createToken($staffUser['id']);
                $params = [
                    'device_id' => $deviceId,
                    'ticket' => $ticket,
                    'domain' => $siteUrl,
                ];
                $tempMenu['app_data'] = [
                    'appid' => '__UNI__8799035',
                    'path' => 'pages/foodshop/index/index',
                    'arguments' => json_encode($params, JSON_UNESCAPED_SLASHES),
                ];
            }

            $menuList[] = $tempMenu;
        }

        // 外卖
        $storeShop = (new MerchantStoreShopService())->getStoreByStoreId($merchentStore['store_id']);
        if (isset($merchentStore['have_shop']) && $merchentStore['have_shop'] && !empty($storeShop)) {
            $tempMenu = [
                'name' => cfg('shop_alias_name'),
                "business_type" =>  'shop', // 业务类型
                "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                'url' => $isPc ? $this->getUrl('Store', 'shop_list') : $this->getAppUrl('shop'),
                'image' => $isPc ? cfg('site_url') . '/v20/public/static/storestaff/images/index/shop.png' : 'shop',
                'need_perfect_store' => $storeShop ? false : true, //是否需要完善店铺信息
                'image_type' => $isPc ? 'url' : 'local', // 图片类型 local-本地 url-远程
            ];

            if ($appVersion >= '30200') {
                $tempMenu['url'] = 'pages/shop/order/order';
                $tempMenu['url_type'] = 'miniapp';
                // 设备id
                $deviceId = request()->param('Device-Id') ?? '';
                // 获得老店员app ticket
                $ticket = Token::createToken($staffUser['id']);
                $params = [
                    'device_id' => $deviceId,
                    'ticket' => $ticket,
                    'domain' => $siteUrl,
                ];
                $tempMenu['app_data'] = [
                    'appid' => '__UNI__8799035',
                    'path' => 'pages/shop/order/order',
                    'arguments' => json_encode($params, JSON_UNESCAPED_SLASHES),
                ];
            }
            $menuList[] = $tempMenu;
        }

        $merchentStoreShop = (new MerchantStoreShop())->getStoreByStoreId($merchentStore['store_id']);
        // 商城
        if (!$isSingleShopSystem && isset($merchentStore['have_mall']) && $merchentStore['have_mall'] && !empty($merchentStoreShop)) {
            $menuMall = (new MerchantMenuService())->getOne([['name', '=', '商城管理'],['module','=','Mall'],['status','=',1]]);
            if($menuMall){//应用中心不存在老版商城，则隐藏老商城的入口
                $plugin_mall = (new PluginService())->getOne(['plugin_name' => '商城管理','plugin_label'=>'mall','status'=>1,'show'=>1]);
                if($plugin_mall){
                    $menuList[] = [
                        'name' => L_(cfg('mall_alias_name')),
                        "business_type" =>  'mall', // 业务类型
                        "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                        'url' =>  $isPc ? $this->getUrl('Store', 'shop_list', ['shop_type' => 2]) : $this->getAppUrl('mall'),
                        'image' => $isPc ? cfg('site_url') . '/v20/public/static/storestaff/images/index/mall.png' : 'mall',
                        'need_perfect_store' => false, //是否需要完善店铺信息
                        'image_type' => $isPc ? 'url' : 'local', // 图片类型 local-本地 url-远程
                    ];
                }
            }
        }
        //新版商城
        if (!$isSingleShopSystem && isset($merchentStore['have_mall']) && $merchentStore['have_mall']) {
            if($isPc){
                $tempMenu = [
                    'name' => L_('新版X1', cfg('mall_alias_name_new')),
                    "business_type" =>  'mall_new', // 业务类型
                    'url' => cfg('site_url') . '/v20/public/platform/#/storestaff/storestaff.mall/mall/orderCopy',
                    'image' => cfg('site_url') . '/v20/public/static/storestaff/images/index/mall.png',
                    'need_perfect_store' => false, //是否需要完善店铺信息
                ];

            }else {
                $tempMenu = [
                    'name' => L_('新版X1', cfg('mall_alias_name_new')),
                    "business_type" =>  'mall', // 业务类型
                    "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                    'url' => $this->getAppUrl('shop'),
                    'image' => 'mall',
                    'need_perfect_store' => false , //是否需要完善店铺信息
                    'image_type' => 'local', // 图片类型 local-本地 url-远程
                ];
                if ($appVersion >= '30200') {
                    $tempMenu['url'] = 'pages/mall/index/index';
                    $tempMenu['url_type'] = 'miniapp';
                    // 设备id
                    $deviceId = request()->param('Device-Id') ?? '';
                    // 获得老店员app ticket
                    $ticket = Token::createToken($staffUser['id']);
                    $params = [
                        'device_id' => $deviceId,
                        'ticket' => $ticket,
                        'domain' => $siteUrl,
                    ];
                    $tempMenu['app_data'] = [
                        'appid' => '__UNI__8799035',
                        'path' => 'pages/mall/index/index',
                        'arguments' => json_encode($params, JSON_UNESCAPED_SLASHES),
                    ];
                }
            }
            $menuList[] = $tempMenu;
        }

        // 预约
        $merchentStore['have_appoint'] = cfg('appoint_page_row') ? 1 : 0;
        if (!$isSingleShopSystem && isset($merchentStore['have_appoint']) && $merchentStore['have_appoint']) {

            $nowMerchant = (new MerchantService())->getMerchantByMerId($merchentStore['mer_id']);
            $merchantMenusArr = explode(',', $nowMerchant['menus']);
            if (empty($nowMerchant['menus']) || (in_array(60, $merchantMenusArr))) {
                $menuList[] = [
                    'name' => L_(cfg('appoint_alias_name')),
                    "business_type" =>  'appoint', // 业务类型
                    "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                    'url' => $isPc ? $this->getUrl('Store', 'appoint_list') : $this->getAppUrl('appoint'),
                    'image' => $isPc ? cfg('site_url') . '/v20/public/static/storestaff/images/index/appoint.png' : 'appoint',
                    'need_perfect_store' => false, //是否需要完善店铺信息
                    'image_type' => $isPc ? 'url' : 'local', // 图片类型 local-本地 url-远程
                ];
            }
        }

        if (cfg('is_cashier') || cfg('pay_in_store')) {
            // 快速买单
            $menuList[] = [
                'name' => L_(cfg('cash_alias_name')),
                "business_type" =>  'cash', // 业务类型
                "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                'url' => $isPc ? $this->getUrl('Store', 'store_order') : $this->getAppUrl('cash'),
                'image' => $isPc ? cfg('site_url') . '/v20/public/static/storestaff/images/index/cash.png' : 'cash',
                'need_perfect_store' => false, //是否需要完善店铺信息
                'image_type' => $isPc ? 'url' : 'local', // 图片类型 local-本地 url-远程
            ];


            // 店内收银
            $menuList[] = [
                'name' => L_('店内收银'),
                "business_type" =>  'store_arrival', // 业务类型
                "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                'url' => $isPc ? $this->getUrl('Store', 'store_arrival') : $this->getAppUrl('store_arrival'),
                'image' => $isPc ? cfg('site_url') . '/v20/public/static/storestaff/images/index/store_arrival.png' : 'store_arrival',
                'need_perfect_store' => false, //是否需要完善店铺信息
                'image_type' => $isPc ? 'url' : 'local', // 图片类型 local-本地 url-远程
            ];
        }

        // 不开启多语言
        if (!cfg('open_multilingual')) {
            if ($isPc && ($merchentStore['have_meal'] || cfg('is_cashier') || cfg('pay_in_store'))) {
                $menuList[] = [
                    'name' => L_('报表统计'),
                    "business_type" =>  'report', // 业务类型
                    'url' => $this->getUrl('Store', 'report'),
                    'image' => cfg('site_url') . '/v20/public/static/storestaff/images/index/report.png',
                    'need_perfect_store' => false, //是否需要完善店铺信息
                    'image_type' => $isPc ? 'url' : 'local', // 图片类型 local-本地 url-远程
                ];
            }
            if (!cfg('single_system')) {
                $menuList[] = [
                    'name' => L_('优惠券'),
                    "business_type" =>  'coupon', // 业务类型
                    "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                    'url' => $isPc ? $this->getUrl('Store', 'coupon_list') : $this->getAppUrl('coupon'),
                    'image' => $isPc ? cfg('site_url') . '/v20/public/static/storestaff/images/index/coupon.png' : 'coupon',
                    'need_perfect_store' => false, //是否需要完善店铺信息
                    'image_type' => $isPc ? 'url' : 'local', // 图片类型 local-本地 url-远程
                ];

                if ($isPc) {
                    $menuList[] = [
                        'name' => L_('实体卡管理'),
                        "business_type" =>  'physical_card', // 业务类型
                        'url' => $this->getUrl('Store', 'physical_card'),
                        'image' => cfg('site_url') . '/v20/public/static/storestaff/images/index/physical_card.png',
                        'need_perfect_store' => false, //是否需要完善店铺信息
                        'image_type' => $isPc ? 'url' : 'local', // 图片类型 local-本地 url-远程
                    ];
                }
            }
        }

        if ($staffUser['type'] == 2) {
            if ($agent != 'iosapp' && $agent != 'androidapp') { // h5和PC
                $menuList[] = [
                    'name' => L_('店铺管理'),
                    "business_type" =>  'store', // 业务类型
                    "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                    'url' => $isPc ? $this->getUrl('Config', 'store') : $this->getAppUrl('store'),
                    'image' => $isPc ? cfg('site_url') . '/v20/public/static/storestaff/images/index/store.png' : 'store',
                    'need_perfect_store' => false, //是否需要完善店铺信息
                    'image_type' => $isPc ? 'url' : 'local', // 图片类型 local-本地 url-远程
                ];
            } else if(empty(customization('internal_membership_card'))){ // App
                $tempMenu = [
                    'name' => L_('店铺管理'),
                    "business_type" =>  'store', // 业务类型
                    "url_type" => 'app', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                    'image' => 'store',
                    'need_perfect_store' => false, //是否需要完善店铺信息
                    'image_type' => 'local', // 图片类型 local-本地 url-远程
                ];
                // 设备id
                $deviceId = request()->param('Device-Id') ?? '';
                // 获得App版本信息
                $appConfig = (new AppapiAppConfigService())->get();
                // 获得老版本的商家app ticket
                $ticket = Ticket::create($merchentStore['mer_id'], $deviceId, true);
                if ($agent == 'iosapp' && $appConfig['mer_ios_package_name']) { //ios
                    $tempMenu['app_data'] = [
                        'app_name' => $appConfig['mer_ios_package_name'],
                        'app_download_url' => $appConfig['mer_ios_download_url'],
                        'device_id' => $deviceId,
                        'from' => 'storestaff',
                        'ticket' => $ticket['ticket'],
                    ];
                    $menuList[] = $tempMenu;
                } elseif ($agent == 'androidapp' && $appConfig['mer_android_package_name']) {
                    $tempMenu['app_data'] = [
                        'app_name' => $appConfig['mer_android_package_name'],
                        'app_download_url' => $appConfig['mer_android_url'],
                        'device_id' => $deviceId,
                        'from' => 'storestaff',
                        'ticket' => $ticket['ticket'],
                    ];
                    $menuList[] = $tempMenu;
                }
            }
        }
        if ($isPc && ((isset($merchentStore['store_wx_sub_mchid']) && $merchentStore['store_wx_sub_mchid'] != '') || (isset($merchentStore['store_alipay_sub_mchid']) && $merchentStore['store_alipay_sub_mchid'] != ''))) {
            $menuList[] = [
                'name' => L_('店铺余额'),
                "business_type" =>  'money_list', // 业务类型
                'url' => $this->getUrl('Store', 'money_list'),
                'image' => cfg('site_url') . '/v20/public/static/storestaff/images/index/money.png',
                'need_perfect_store' => false, //是否需要完善店铺信息
            ];
        }

        if ($isPc && cfg('open_sub_card') == 1) {
            $menuList[] = [
                'name' => L_('免单套餐'),
                "business_type" =>  'sub_card', // 业务类型
                'url' => $this->getUrl('Store', 'sub_card'),
                'image' => cfg('site_url') . '/v20/public/static/storestaff/images/index/sub_card.png',
                'need_perfect_store' => false, //是否需要完善店铺信息
                'image_type' => 'local', // 图片类型 local-本地 url-远程
            ];
        }

        if (!$isPc && cfg('single_system_type') != 'shop') {
            $menuList[] = [
                'name' => L_('台卡'),
                "business_type" =>  'store_qrcode', // 业务类型
                "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                'url' => $this->getAppUrl('store_qrcode'),
                'image' => 'store_qrcode',
                'need_perfect_store' => false, //是否需要完善店铺信息
                'image_type' => 'local', // 图片类型 local-本地 url-远程
            ];
        }

        $im = (new MerchantStoreKefuService())->getImUrl($staffUser);
        if (!$isPc && $im['url']) {
            $menuList[] = [
                'name' => L_('客服'),
                "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                "business_type" =>  'kefu', // 业务类型
                'url' => $im['url'],
                'image' => 'kefu',
                'need_perfect_store' => false, //是否需要完善店铺信息
                'image_type' => 'local', // 图片类型 local-本地 url-远程
            ];
        }
        if(isset($merchentStore['is_open_scenic']) && $merchentStore['is_open_scenic'] == 1) {
            if (cfg('life_tools_scenic_alias_name')) {
                if ($isPc) {
                    $menuList[] = [
                        'name' => '景区管理',
                        "business_type" => 'life_tools', // 业务类型
                        'url' => cfg('site_url') . '/v20/public/platform/#/storestaff/storestaff.life_tools/scenic/autonomously',
//                'url' => cfg('site_url') . '/v20/public/platform/#/storestaff/storestaff.life_tools/scenic/verifiy',
                        'image' => cfg('site_url') . '/v20/public/static/storestaff/images/index/scenic.png',
                        'need_perfect_store' => false, //是否需要完善店铺信息
                    ];
                } else {
                    $menuList[] = [
                        'name' => '景区',
                        'url_type' => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                        "business_type" => 'life_tools', // 业务类型
                        'url' => cfg('site_url') . '/packapp/storestaff/scenic_consume.html',
                        'image' => cfg('site_url') . '/static/images/storestaff/scenic_new.png',
                        'need_perfect_store' => false, //是否需要完善店铺信息
                        'image_type' => 'url', // 图片类型 local-本地 url-远程
                    ];
                }
            }
        }

        if ($isPc && customization('life_tools') == 1) {
            $menuList[] = [
                'name' => '员工卡',
                "business_type" =>  'life_tools', // 业务类型
                'url' => cfg('site_url') . '/v20/public/platform/#/storestaff/storestaff.employee/employee/cancelPay',
                'image' => cfg('site_url') . '/v20/public/static/storestaff/images/index/life_tools.png',
                'need_perfect_store' => false, //是否需要完善店铺信息
            ];
            
            if($staffUser['can_verify_activity_appoint'] == 1){
                // $menuList[] = [
                //     'name' => '活动预约',
                //     "business_type" =>  'life_tools', // 业务类型
                //     'url' => cfg('site_url') . '/v20/public/platform/#/storestaff/storestaff.life_tools/appoint/verifiy',
                //     'image' => cfg('site_url') . '/v20/public/static/storestaff/images/index/active_appoint.png',
                //     'need_perfect_store' => false, //是否需要完善店铺信息
                // ];
            }
            $menuList[] = [
                'name' => '体育管理',
                "business_type" =>  'life_tools', // 业务类型
                'url' => cfg('site_url') . '/v20/public/platform/#/storestaff/storestaff.life_tools/sports/verifiy',
                'image' => cfg('site_url') . '/v20/public/static/storestaff/images/index/sports.png',
                'need_perfect_store' => false, //是否需要完善店铺信息
            ];
        }

        if (!$isPc && customization('life_tools') == 1) {
            $menuList[] = [
                'name' => '体育',
                'url_type' => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                "business_type" =>  'life_tools', // 业务类型
                'url' => cfg('site_url') . '/packapp/storestaff/sports.html',
                'image' => cfg('site_url') . '/static/images/storestaff/sports.png',
                'need_perfect_store' => false, //是否需要完善店铺信息
                'image_type' => 'url' , // 图片类型 local-本地 url-远程
            ];
            if($staffUser['can_verify_activity_appoint'] == 1){
                $menuList[] = [
                    'name' => '活动预约',
                    'url_type' => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                    "business_type" =>  'life_tools', // 业务类型https://aaa.zairizhao.cn/packapp/storestaff/merchantActivityList.html
                    'url' => cfg('site_url') . '/packapp/storestaff/merchantActivityList.html',
                    'image' => cfg('site_url') . '/packapp/storestaff/images/lifetools_appoint_new.png',
                    'need_perfect_store' => false, //是否需要完善店铺信息
                    'image_type' => 'url' , // 图片类型 local-本地 url-远程
                ];
            }
        }

        //优先从楼宇获取，楼宇没有对应的商家停车场则从社区获取
        $parkCoupon = false;
        if (cfg('building_domain')) {
            $nowMerchant = (new MerchantService())->getMerchantByMerId($merchentStore['mer_id']);
            $merPhone = $nowMerchant['phone'] ?? '';
            if ($merPhone) {
                $res = Http::curlGet(cfg('building_domain') . '/work_park/coupon/prelogin?phone=' . $merPhone);
                $res = json_decode($res, true);
                if ($res && isset($res['code']) && $res['code'] == 1000 && isset($res['data']['url']) && !empty($res['data']['url'])) {
                    $menuList[] = [
                        'name' => L_('停车优惠券'),
                        'url' => $res['data']['url'],
                        "url_type" => $isPc ? 'new_blank' : 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫 ，new_blank：打开新窗口 
                        "business_type" =>  'village_park', // 业务类型
                        'image' => $isPc ? cfg('site_url') . '/static/images/house/park_coupons_pc.png' : cfg('site_url') . '/static/images/house/park_coupons.png',
                        'need_perfect_store' => false, //是否需要完善店铺信息
                        'image_type' => 'url', // 图片类型 local-本地 url-远程
                    ];
                    $parkCoupon = true;
                }
            }
        }
        if (!$isPc) { // 店铺关联停车场
            // 获取停车场信息
            if(!$parkCoupon){
                $where = [
                    'status' => 0,
                    'bind_m_id' => $merchentStore['store_id']
                ];
                $pack = (new ParkCouponsShopService())->getSome($where);
                if ($pack) {
                    if (count($pack) == 1) { //只有一个直接跳转
                        $url = cfg('site_url') . '/packapp/storestaff/iframe.html?url=' . urlencode($pack[0]['turn_url']);
                    } else { // 多个停车场跳转选择停车场页面
                        $url = cfg('site_url') . '/packapp/storestaff/house_park.html?store_id=' . $merchentStore['store_id'];
                    }
                    $menuList[] = [
                        'name' => L_('停车优惠券'),
                        'url' => $url,
                        "url_type" => 'url', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                        "business_type" =>  'village_park', // 业务类型
                        'image' => cfg('site_url') . '/static/images/house/park_coupons.png',
                        'need_perfect_store' => false, //是否需要完善店铺信息
                        'image_type' => 'url', // 图片类型 local-本地 url-远程
                    ];
                }
            }
            
            if(customization('deposita')){// 会员卡寄存功能
                $tempMenu = [
                    'name' => L_('会员卡管理'),
                    "business_type" =>  'deposita', // 业务类型
                    "url_type" => 'miniapp', // 跳转类型 app-app原生, miniapp-小程序, url-网址，scan-扫一扫
                    'url' => $this->getAppUrl('deposita'),
                    'image' => cfg('site_url') . '/static/images/store/deposita.png',
                    'need_perfect_store' =>  false, //是否需要完善店铺信息
                    'image_type' =>  'url', // 图片类型 local-本地 url-远程
                ];
                $tempMenu['app_data'] = $appData;
                $tempMenu['app_data']['path'] = $tempMenu['url'];
                $menuList[] = $tempMenu;
            }

            if(customization('internal_membership_card')){ //一卡通日结报表
                $menuList[] = [
                    'name' => '日结报表',
                    'url_type' => 'url', 
                    "business_type" => 'internal_membership_card', // 业务类型
                    'url' => cfg('site_url') . '/packapp/storestaff/daily_report.html',
                    'image' => cfg('site_url') . '/packapp/storestaff/images/daily_report.png',
                    'need_perfect_store' => false, 
                    'image_type' => 'url', 
                ];

                $menuList[] = [
                    'name' => '一卡通统计',
                    'url_type' => 'url', 
                    "business_type" => 'internal_membership_card_report', // 业务类型
                    'url' => cfg('site_url') . '/packapp/storestaff/internalCardStatistics.html',
                    'image' => cfg('site_url') . '/packapp/storestaff/images/internal_card_bill.png',
                    'need_perfect_store' => false, 
                    'image_type' => 'url', 
                ];
            }
        }
        return $menuList;
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

        $retuenUrl = $siteUrl . '/store.php?g=Store&c=' . ucfirst($modelName) . '&a=' . $actionName;

        if ($param) {
            foreach ($param as $key => $value) {
                $retuenUrl .= '&' . $key . '=' . $value;
            }
        }
        return $retuenUrl;
    }

    /**
     * 获得店员app各业务跳转路径
     * @param $modelName 控制器名
     * @param $actionName 方法名
     * @param $param 参数
     * @param $siteUrl 网站跟目录
     * @return string
     */
    protected function getAppUrl($modelName)
    {
        if (empty($siteUrl)) {
            $siteUrl = request()->server('REQUEST_SCHEME') . '://' . request()->server('SERVER_NAME');
        }

        $url = '';
        switch ($modelName) {
            case 'store_arrival': //店内收银
                $url = $siteUrl . '/packapp/storestaff/cashier.html';
                break;
            case 'store_arrival_top': //顶部店内收银
                $url = $siteUrl . '/packapp/storestaff/cashier_set.html';
                break;
            case 'retail': //零售
                $url = $siteUrl . '/packapp/storestaff/retail.html';
                break;
            case 'group': //团购
                $url = $siteUrl . '/packapp/storestaff/group_list.html';
                break;
            case 'foodshop': //餐饮
                if (cfg('open_meal_electronic_menu')) {
                    // 开启堂扫电子菜单模式
                    $url = $siteUrl . '/packapp/storestaff/foodshop_order_list_new.html';
                } else {
                    $url = $siteUrl . '/packapp/storestaff/foodshop.html';
                }
                break;
            case 'dining': //新版餐饮
                $url = 'pages/foodshop/index/index';
            case 'deposita': //新版餐饮
                $url = 'pages/deposit/member/memberCardList';
                break;
            case 'shop': //外卖
                $url = $siteUrl . '/packapp/storestaff/shop.html';
                break;
            case 'mall': //商城
                $url = $siteUrl . '/packapp/storestaff/mall.html';
                break;
            case 'appoint': //预约
                $url = $siteUrl . '/packapp/storestaff/appoint.html';
                break;
            case 'cash': //快速买单
                $url = $siteUrl . '/packapp/storestaff/store.html';
                break;
            case 'coupon': //优惠券
                $url = $siteUrl . '/packapp/storestaff/couponList.html';
                break;
            case 'store_qrcode': //台卡
                $url = $siteUrl . '/packapp/storestaff/store_qrcode.html';
                break;
            case 'store': //店铺管理
                $url = $siteUrl . '/packapp/storestaff/store_qrcode.html';
                break;
        }



        return $url;
    }
}
