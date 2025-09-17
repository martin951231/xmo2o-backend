<?php
/**
 * 系统后台首页
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/12/16
 */

namespace app\common\model\service;
use app\common\model\db\Plugin;
use app\common\model\db\ShopOrder;
use app\common\model\db\SystemOrder;
use app\common\model\db\VillageGroupOrder;
use app\common\model\service\admin_user\AdminUserService;
use app\common\model\service\order\SystemOrderService;
use app\common\model\service\order\UserRechargeOrderService;
use app\common\model\service\user\UserAuthenticationService;
use app\common\model\service\weixin\ChanelMsgListService;
use app\common\model\service\activity\ExtensionActivityListService;
use app\common\model\service\weixin\SendLogService;
use app\common\model\service\weixin\TempmsgService;
use app\common\model\service\weixin\WxappTemplateService;
use app\deliver\model\service\DeliverUserRegisterService;
use app\foodshop\model\db\DiningOrder;
use app\group\model\db\GroupOrder;
use app\mall\model\db\MallOrder;
use app\market\model\service\MarketGoodsService;
use app\merchant\model\db\NewMerchantMenu;
use app\merchant\model\service\MerchantMoneyListService;
use app\merchant\model\service\MerchantService;
use app\merchant\model\service\MerchantStoreService;
use app_pack\appPack;
use app\community\model\db\Area;
class IndexService {
    public $weekList = [];
    public function __construct()
    {

        $this->weekList = [
            '1' => L_('星期一'),
            '2' => L_('星期二'),
            '3' => L_('星期三'),
            '4' => L_('星期四'),
            '5' => L_('星期五'),
            '6' => L_('星期六'),
            '0' => L_('星期日'),
        ];
    }
    /**
     * 获得配置项的完整度
     * @param $catKey
     * @return array
     */
    public function getConfigSetSpeedProgress($systemUser) {
        $returnArr = [];
        /*
         * 系统配置信息
         */
        $returnArr['help_list'] = [];
        // 需要提示的配置数量
        $totalCount = 0;
        // 未配置的数量
        $warnCount = 0;

        if($systemUser['level'] != 2){//普通管理员没有权限
            return $returnArr;
        }

        // 当前网站域名
        $serverHost = request()->server('HTTP_HOST');

        /* 域名配置 */
        $domain_help_arr = [
            'cat_name' => '域名配置',
        ];

        $need = false;
        $totalCount++;
        $totalCount++;
        if(strpos($serverHost,'.weihubao.com') !== false && strpos($serverHost,'.dazhongbanben.com') !== false){
            $need = true;
            $warnCount++;
            $warnCount++;
        }

        // 查看是否需要备案域名
        $domain_help_arr['item_list'][] = [
            'title' => '备案正式域名',
            'level' => $need ? 'warn' : 'safe',
            'info' => '备案后请及时更换正式域名',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/gppqy0',
        ];

        // 更换正式域名
        $domain_help_arr['item_list'][] = [
            'title' => '更换正式域名',
            'level' => $need ? 'warn' : 'safe',
            'info' => '请及时更换',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/gppqy0',
        ];

        // 购买HTTPS证书
        $need = false;
        $totalCount++;
        if(strtolower(request()->server('REQUEST_SCHEME')) == 'http'){
            $need = true;
            $warnCount++;
        }
        $domain_help_arr['item_list'][] = [
            'title' => '购买HTTPS证书',
            'level' => $need ? 'warn' : 'safe',
            'info' => '',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/nipf2p',
        ];
        $returnArr['help_list'][] = $domain_help_arr;

        /* 基本信息配置 */
        $basic_help_arr = [
            'cat_name' => '基本信息配置',
        ];

        //配置redis
        $totalCount++;
        $queueRedis = \think\facade\Config::get('cache.stores.queueRedis');
        if(empty($queueRedis)){
            $warnCount++;
        }
        $basic_help_arr['item_list'][] = [
            'title' => 'Redis配置',
            'level' =>  $queueRedis ? 'safe' : 'warn',
            'info' => '计划任务和队列使用',
            'link_type' => 'new_tab',
            'link_url' => '',
        ];

        // 更换初始密码
        $need = false;
        $totalCount++;
        if($systemUser['pwd'] == md5('123456')){
            $need = true;
            $warnCount++;
        }
        $basic_help_arr['item_list'][] = [
            'title' => '更换初始密码',
            'level' => $need ? 'warn' : 'safe',
            'info' => '及时更新保障后台安全',
            'link_type' => 'new_tab',
            'link_url' => '/common/platform.iframe/menu_7',
        ];

        // 百度地图开放平台
        $need = false;
        $totalCount++;
        if(empty(cfg('baidu_map_ak'))){
            $need = true;
            $warnCount++;
        }
        $basic_help_arr['item_list'][] = [
            'title' => '百度地图开放平台',
            'level' => $need ? 'warn' : 'safe',
            'info' => '',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/rwbng9',
        ];

        // 短信配置
        $need = false;
        $totalCount++;
        if(empty(cfg('sms_key')) || empty(cfg('sms_sign'))){
            $need = true;
            $warnCount++;
        }
        $basic_help_arr['item_list'][] = [
            'title' => '短信配置',
            'level' => $need ? 'warn' : 'safe',
            'info' => '',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/zgoc83',
        ];

        // 可视化页面配置
        if(!cfg('system_type') || cfg('system_type') == 'plat'){
            $need = false;
            $totalCount++;
            // 查看是否设置了导航
            $adverList = (new AdverService())->getAdverByCatKey('app_index_top');
            $adverList2 = (new AdverService())->getAdverByCatKey('wap_index_top');
            if(empty($adverList) && empty($adverList2)){
                $need = true;
                $warnCount++;
            }
            $basic_help_arr['item_list'][] = [
                'title' => '可视化页面配置',
                'level' => $need ? 'warn' : 'safe',
                'info' => '',
                'link_type' => 'new_blank',
                'link_url' => 'https://www.yuque.com/pigo2o-service/customer/zmg5oz',
            ];
        }

        // 腾讯云直播
        $need = false;
        if(empty(cfg('live_push_domain')) || empty(cfg('live_secret_id')) || empty(cfg('live_play_domain')) || empty(cfg('live_secret_key')) || empty(cfg('live_back_key')) || empty(cfg('live_push_apikey')) || empty(cfg('tencent_SDKAppID')) || empty(cfg('live_licence_url')) || empty(cfg('live_licence_key')) || empty(cfg('tencent_key')) || empty(cfg('tencent_administrator'))){
            $need = true;
        }
        $basic_help_arr['item_list'][] = [
            'title' => '腾讯云直播',
            'level' => $need ? 'recommend' : 'safe',
            'info' => '',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/azhwy8',
        ];

        // OSS图片存储
        $need = false;
        if(
            (empty(cfg('static_oss_access_id')) || empty(cfg('static_oss_access_key')) || empty(cfg('static_oss_bucket')) || empty(cfg('static_oss_access_domain_names')) || empty(cfg('static_oss_endpoint')))
            &&
            (empty(cfg('static_cos_key')) || empty(cfg('static_cos_secret')) || empty(cfg('static_cos_region')) || empty(cfg('static_cos_bucket')) || empty(cfg('static_cos_access_domain_names')))
        ){
            $need = true;
        }
        $basic_help_arr['item_list'][] = [
            'title' => 'COS图片存储',
            'level' => $need ? 'recommend' : 'safe',
            'info' => '',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/db1fqr',
        ];

        // 七牛云存储
        $need = false;
        if(empty(cfg('qiniu_bucket')) || empty(cfg('qiniu_domain')) || empty(cfg('qiniu_accesskey')) || empty(cfg('qiniu_secretkey'))){
            $need = true;
        }
        $basic_help_arr['item_list'][] = [
            'title' => '七牛云存储',
            'level' => $need ? 'recommend' : 'safe',
            'info' => '',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/se6kyq',
        ];

        // 管理员绑定微信
        $need = false;
        $where = [
            'account' => 'admin',
        ];
        $admin = (new AdminUserService())->getOne($where);
        if(empty($admin['openid'])){
            $need = true;
        }
        $basic_help_arr['item_list'][] = [
            'title' => '管理员绑定微信',
            'level' => $need ? 'recommend' : 'safe',
            'info' => '',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/fpgup8',
        ];
        $returnArr['help_list'][] = $basic_help_arr;

        /* 公众号/支付配置 */
        $basic_help_arr = [
            'cat_name' => '公众号/支付配置',
        ];

        // 平台公众号配置
        $need = false;
        $totalCount++;
        if(empty(cfg('wechat_name')) || empty(cfg('wechat_sourceid')) || empty(cfg('wechat_id')) || empty(cfg('wechat_appid')) || empty(cfg('wechat_appsecret'))){
            $need = true;
            $warnCount++;
        }
        $basic_help_arr['item_list'][] = [
            'title' => '平台公众号配置',
            'level' => $need ? 'warn' : 'safe',
            'info' => '',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/qu367g',
        ];

        // 商家自有公众号配置
        $need = false;
        if(empty(cfg('wx_appid')) || empty(cfg('wx_token')) || empty(cfg('wx_appsecret')) || empty(cfg('wx_encodingaeskey'))){
            $need = true;
        }
        $basic_help_arr['item_list'][] = [
            'title' => '商家自有公众号配置',
            'level' => $need ? 'recommend' : 'safe',
            'info' => '',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/whu1om',
        ];

        // 微信支付配置
        $need = false;
        $totalCount++;
        if(empty(cfg('pay_weixin_appid')) || empty(cfg('pay_weixin_mchid')) || empty(cfg('pay_weixin_key')) || empty(cfg('pay_weixin_appsecret'))){
            $need = true;
            $warnCount++;
        }
        $basic_help_arr['item_list'][] = [
            'title' => '微信支付配置',
            'level' => $need ? 'warn' : 'safe',
            'info' => '',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/bnh3ay',
        ];
        // 支付宝H5配置
        $need = false;
        $totalCount++;
        if(empty(cfg('pay_alipayh5_appid')) || empty(cfg('pay_alipayh5_merchant_private_key')) || empty(cfg('pay_alipayh5_public_key')) || empty(cfg('alipay_app_id')) || empty(cfg('alipay_app_prikey'))){
            $need = true;
            $warnCount++;
        }
        $basic_help_arr['item_list'][] = [
            'title' => '支付宝H5配置',
            'level' => $need ? 'warn' : 'safe',
            'info' => '及时更新保障后台安全',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/od0gq5',
        ];

        // 准备APP打包资料
        $newVersionArr = (new appPack())->getNewVersion();
        if ($newVersionArr){
            $need = false;
            $info = '';
            if(isset($newVersionArr['plat']) && $newVersionArr['plat']){
                // 查看平台App是否需要打包
                // 安卓
                $where = [
                    'var' => 'plat_android_name'
                ];
                $res = (new AppapiPackConfigService())->getOne($where);
                if(empty($res)){
                    $need = true;
                    $info = '您当前有新的安卓平台App版本可进行升级~';
                }else{
                    // ios
                    $where = [
                        'var' => 'plat_ios_name'
                    ];
                    $res = (new AppapiPackConfigService())->getOne($where);
                    if(empty($res)){
                        $need = true;
                        $info = '您当前有新的ios平台App版本可进行升级~';
                    }
                }
            }elseif (isset($newVersionArr['storestaff']) && $newVersionArr['storestaff']){
                // 安卓
                $where = [
                    'var' => 'storestaff_android_name'
                ];
                $res = (new AppapiPackConfigService())->getOne($where);
                if(empty($res)){
                    $need = true;
                    $info = '您当前有新的安卓店员App版本可进行升级~';
                }else{
                    // ios
                    $where = [
                        'var' => 'storestaff_ios_name'
                    ];
                    $res = (new AppapiPackConfigService())->getOne($where);
                    if(empty($res)){
                        $need = true;
                        $info = '您当前有新的ios店员App版本可进行升级~';
                    }
                }
            }elseif (isset($newVersionArr['merchant']) && $newVersionArr['merchant']){
                // 安卓
                $where = [
                    'var' => 'merchant_android_name'
                ];
                $res = (new AppapiPackConfigService())->getOne($where);
                if(empty($res)){
                    $need = true;
                    $info = '您当前有新的安卓商家App版本可进行升级~';
                }else{
                    // ios
                    $where = [
                        'var' => 'merchant_ios_name'
                    ];
                    $res = (new AppapiPackConfigService())->getOne($where);
                    if(empty($res)){
                        $need = true;
                        $info = '您当前有新的ios商家App版本可进行升级~';
                    }
                }
            }elseif (isset($newVersionArr['deliver']) && $newVersionArr['deliver']){
                // 安卓
                $where = [
                    'var' => 'deliver_android_name'
                ];
                $res = (new AppapiPackConfigService())->getOne($where);
                if(empty($res)){
                    $need = true;
                    $info = '您当前有新的安卓配送App版本可进行升级~';
                }else{
                    // ios
                    $where = [
                        'var' => 'deliver_ios_name'
                    ];
                    $res = (new AppapiPackConfigService())->getOne($where);
                    if(empty($res)){
                        $need = true;
                        $info = '您当前有新的ios配送App版本可进行升级~';
                    }
                }
            }elseif (isset($newVersionArr['village']) && $newVersionArr['village']){
                // 安卓
                $where = [
                    'var' => 'village_android_name'
                ];
                $res = (new AppapiPackConfigService())->getOne($where);
                if(empty($res)){
                    $need = true;
                    $info = '您当前有新的安卓社区App版本可进行升级~';
                }else{
                    // ios
                    $where = [
                        'var' => 'village_ios_name'
                    ];
                    $res = (new AppapiPackConfigService())->getOne($where);
                    if(empty($res)){
                        $need = true;
                        $info = '您当前有新的ios社区App版本可进行升级~';
                    }
                }
            }elseif (isset($newVersionArr['village_manager']) && $newVersionArr['village_manager']){
                // 安卓
                $where = [
                    'var' => 'village_manager_android_name'
                ];
                $res = (new AppapiPackConfigService())->getOne($where);
                if(empty($res)){
                    $need = true;
                    $info = '您当前有新的安卓社区管理App版本可进行升级~';
                }else{
                    // ios
                    $where = [
                        'var' => 'village_manager_ios_name'
                    ];
                    $res = (new AppapiPackConfigService())->getOne($where);
                    if(empty($res)){
                        $need = true;
                        $info = '您当前有新的ios社区管理App版本可进行升级~';
                    }
                }
            }
            $basic_help_arr['item_list'][] = [
                'title' => '准备APP打包资料',
                'level' => $need ? 'recommend' : 'safe',
                'info' => $info,
                'link_type' => 'new_blank',
                'link_url' => 'https://www.yuque.com/pigo2o-service/customer/dam3io',
            ];
        }

        // 准备小程序打包资料
        $need = false;
        $totalCount++;
        if(empty(cfg('pay_wxapp_appid'))){
            $need = true;
            $warnCount++;
        }
        $basic_help_arr['item_list'][] = [
            'title' => '准备小程序打包资料',
            'level' => $need ? 'warn' : 'safe',
            'info' => '',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/qhnygi',
        ];

        // 模板与订阅消息
        $need = false;
        $totalCount++;
        $tempMsg = (new TempmsgService())->getOne([['id','>',0]]);
        $wxappTempMsg = (new WxappTemplateService())->getOne([['id','>',0],['template_key','<>','']]);
        if(empty($tempMsg) && empty($wxappTempMsg)){
            $need = true;
            $warnCount++;
        }
        $basic_help_arr['item_list'][] = [
            'title' => '模板与订阅消息',
            'level' => $need ? 'warn' : 'safe',
            'info' => '',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/so489m',
        ];

        // 微信开放平台注册
        $need = false;
        $totalCount++;
        if(empty(cfg('wechat_name')) || empty(cfg('wechat_sourceid')) || empty(cfg('wechat_id')) || empty(cfg('wechat_appid')) || empty(cfg('wechat_appsecret'))){
            $need = true;
            $warnCount++;
        }
        $basic_help_arr['item_list'][] = [
            'title' => '微信开放平台注册',
            'level' => $need ? 'warn' : 'safe',
            'info' => '',
            'link_type' => 'new_blank',
            'link_url' => 'https://www.yuque.com/pigo2o-service/customer/xz2xkk',
        ];
        $returnArr['help_list'][] = $basic_help_arr;

        // 完成度
        if($totalCount == $warnCount){//一个都没配置
            $returnArr['speed_progress'] = 0;
        }else{
            $returnArr['speed_progress'] = intval(($totalCount-$warnCount)/$totalCount*100);
        }

        //头部描述
        $returnArr['description'] = "检测到您的系统有多项重要信息未进行配置，如需正常营业，请尽快配置~";
        return $returnArr;
    }


    /**
     * 根据菜单id和打开类型获得对应url
     * @return string
     */
    public function getLinkUrlByMenuId($menuId,$linkType = 'new_tab')
    {
        if($linkType == 'new_tab'){
            $url = '/common/platform.iframe/menu_'.$menuId;
        }elseif ($linkType == 'new_blank'){
            $url = cfg('site_url').'/v20/public/platform/#/common/platform.iframe/menu_'.$menuId;
        }

        return $url;
    }

    /**
     * 获得头部小块统计数据显示
     * @return array
     */
    public function getSmallStatisticsData() {
        $returnArr = [];
        $returnArr['link_type'] = 'new_blank';
        $returnArr['statistics_url'] = $this->getLinkUrlByMenuId(413,$returnArr['link_type']);
        // 当前时间
        $nowTime = time();
        $returnArr['now_time'] = date('Y-m-d H:i:s',$nowTime);

        // 今日凌晨时间戳
        $startTime = strtotime(date('Y-m-d',$nowTime));

        /* 今日实收金额 */
//        $where = [
//            ['paid','=',1],
//            ['system_status','not in','4,5'],
//            ['create_time','between',[$startTime,$nowTime]]
//        ];
//        $totalMoney = (new SystemOrderService())->getTotalByCondition($where,'total_price');
//        $totalMoney = get_format_number($totalMoney);
//        $orderCount = (new SystemOrderService())->getCount($where);
//
//        // 昨日实收金额
//        $lastStartTime = strtotime(date('Y-m-d',$nowTime))-86400;
//        $lastEndTime = $lastStartTime+86399;
//        $where = [
//            ['paid','=',1],
//            ['system_status','not in','4,5'],
//            ['create_time','between',[$lastStartTime,$lastEndTime]]
//        ];
//
//        $lastDaytotalMoney = (new SystemOrderService())->getTotalByCondition($where,'total_price');
//        $lastDaytotalMoney = get_format_number($lastDaytotalMoney);
//        $todayTotalPrice = [
//            'cat_name' => '今日实收总额',
//            'cat_type' => 'totay_total_money',
//            'total_money' => $totalMoney ,
//            'total_count' => $orderCount,
//        ];

        /* 今日实收金额new */
        $lastStartTime = strtotime(date('Y-m-d',$nowTime))-86400;
        $lastEndTime = $lastStartTime+86399;
        $where = [
            ['paid','=',1],
            ['system_status','<>','5'],
            ['type','IN',['malls','shop','group','village_group','dining']],
            ['create_time','between',[$lastStartTime,$nowTime]]
        ];
        $whereOr = [
            ['paid','=',1],
            ['system_status','not in','4,5'],
            ['type','not in','malls,shop,group,village_group,dining'],
            ['create_time','between',[$lastStartTime,$nowTime]]
        ];
        $allOrder = (new SystemOrder())->whereOr([$where,$whereOr])->field('total_price,order_id,type,create_time, merchant_reduce_total')->select();
        $mallAry = $shopAry = $mealAry = $groupAry = $appointAry = $serviceAry = $storeAry = $mobileRechargeAry = $giftAry = $villageGroupAry = $diningAry = [];
        $todayMoney = 0;
        $lastDayMoney = 0;
        $totalCount = 0;
        foreach ($allOrder as $v){
            //分别结算昨天和今天的总金额
            if($v['create_time'] >= $startTime){
                $todayMoney += $v['total_price'];
                $totalCount += 1;
            }else{
                $lastDayMoney += $v['total_price'];
            }
            //分别获取每个类型的昨天和今天的订单id
            switch ($v['type']){
                case 'malls'://新版商城
                    $mallAry[] = $v['order_id'];
                    break;
                case 'shop'://外卖
                    $shopAry[] = $v['order_id'];
                    break;
//                case 'meal'://餐饮
//                    break;
                case 'group'://团购
                    $groupAry[] = $v['order_id'];
                    break;
//                case 'service'://帮买、跑腿 pigcms_service_user_publish
//                    break;
//                case 'store'://优惠买单or线下零售 pigcms_store_order
//                    break;
//                case 'mobile_recharge'://手机充值
//                    break;
//                case 'gift'://礼品
//                    break;
                case 'village_group'://社区拼团
                    $villageGroupAry[] = $v['order_id'];
                    break;
                case 'dining'://新版餐饮
                    $diningAry[] = $v['order_id'];
                    break;
                case 'appoint'://预约订单
                    $appointAry[] = $v['order_id'];
                    break;
            }
        }
        //分别计算每种业务的退款金额
        $todayRefund = 0;
        $lastDayRefund = 0;
        if($mallAry){
            $mallOrderInfo = (new MallOrder())->where([['order_id','IN',$mallAry]])->field('money_refund,create_time')->select()->toArray();
            foreach ($mallOrderInfo as $vMall){
                if($vMall['create_time'] >= $startTime){
                    $todayRefund += $vMall['money_refund'];
                }else{
                    $lastDayRefund += $vMall['money_refund'];
                }
            }
        }
        if($shopAry){
            $shopOrderInfo = (new ShopOrder())->where([['order_id','IN',$shopAry]])->field('refund_money,create_time')->select()->toArray();
            foreach ($shopOrderInfo as $vShop){
                if($vShop['create_time'] >= $startTime){
                    $todayRefund += $vShop['refund_money'];
                }else{
                    $lastDayRefund += $vShop['refund_money'];
                }
            }
        }
        if($groupAry){
            $sgroupOrderInfo = (new GroupOrder())->where([['order_id','IN',$groupAry]])->field('refund_money,add_time')->select()->toArray();
            foreach ($sgroupOrderInfo as $vGroup){
                if($vGroup['add_time'] >= $startTime){
                    $todayRefund += $vGroup['refund_money'];
                }else{
                    $lastDayRefund += $vGroup['refund_money'];
                }
            }
        }
        if($villageGroupAry){
            $villageGroupOrderInfo = (new VillageGroupOrder())->where([['order_id','IN',$groupAry]])->field('refund_money,add_time')->select()->toArray();
            foreach ($villageGroupOrderInfo as $vVillageGroup){
                if($vVillageGroup['add_time'] >= $startTime){
                    $todayRefund += $vVillageGroup['refund_money'];
                }else{
                    $lastDayRefund += $vVillageGroup['refund_money'];
                }
            }
        }
        if($diningAry){
            $diningOrderInfo = (new DiningOrder())->where([['order_id','IN',$groupAry]])->field('refund_money,create_time')->select()->toArray();
            foreach ($diningOrderInfo as $vDining){
                if($vDining['create_time'] >= $startTime){
                    $todayRefund += $vDining['refund_money'];
                }else{
                    $lastDayRefund += $vDining['refund_money'];
                }
            }
        }

        if ($appointAry) {
            $appointOrders = \app\common\model\db\AppointOrder::alias('o')
                ->leftJoin('appoint_order_refund r', 'r.order_id = o.order_id AND r.status = 1')
                ->whereIn('o.order_id', $appointAry)
                ->where('o.product_real_balace_pay_time', '>', 0)
                ->where('o.product_price', '>', 0)
                ->field('o.product_real_balace_price,o.order_time,r.price')
                ->select();
            foreach ($appointOrders as $vAppoint) {
                if (strtotime($vAppoint->order_time) >= $startTime) {
                    $todayRefund += floatval($vAppoint->price);
                    $todayMoney += floatval($vAppoint->product_real_balace_price);
                } else {
                    $lastDayRefund += floatval($vAppoint->price);
                    $lastDayMoney += floatval($vAppoint->product_real_balace_price);
                }
            }
        }

        $totalMoney = ($todayMoney - $todayRefund) < 0 ? 0 : ($todayMoney - $todayRefund);
        $lastDaytotalMoney = ($lastDayMoney - $lastDayRefund) < 0 ? 0 : ($lastDayMoney - $lastDayRefund);
        $totalMoney = get_format_number($totalMoney);
        $lastDaytotalMoney = get_format_number($lastDaytotalMoney);
        $todayTotalPrice = [
            'cat_name' => '今日实收总额',
            'cat_type' => 'totay_total_money',
            'total_money' => $totalMoney ,
            'total_count' => $totalCount,
        ];

        // 获得对比数据
        $percent = $this->getComparePercent($totalMoney,$lastDaytotalMoney);
        //日同比类型： 1-上升 ；-1-下降 ；0-等比
        $todayTotalPrice['today_percent_type'] = $percent['type'];
        //日同比
        $todayTotalPrice['today_percent'] = get_format_number($percent['percent']);

        $date = date('Y-m-d',$nowTime);  //当前日期
        $first = 1; //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
        $w = date('w',strtotime($date));  //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        // 本周开始日期
        $nowStart = date('Y-m-d',strtotime("$date -".($w ? $w - $first : 6).' days')); //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        // 本周结束日期
        $nowEnd=date('Y-m-d',strtotime("$nowStart +6 days"));  //本周结束日期
        //上周开始日期
        $lastStart = date('Y-m-d',strtotime("$nowStart - 7 days"));  //上周开始日期
        // 上周结束日期
        $lastEnd = date('Y-m-d',strtotime("$nowStart - 1 days"));  //上周结束日期

        // 本周实收金额
        $where = [
            ['paid','=',1],
            ['system_status','not in','4,5'],
            ['create_time','between',[strtotime($nowStart),$nowTime]]
        ];
        $thisWeekMoney = (new SystemOrderService())->getTotalByCondition($where,'total_price');
        $thisWeekMoney = get_format_number($thisWeekMoney);

        // 上周实收金额
        $where = [
            ['paid','=',1],
            ['system_status','not in','4,5'],
            ['create_time','between',[strtotime($lastStart),strtotime($lastEnd.' 23:59:59')]]
        ];
        $lastWeekMoney = (new SystemOrderService())->getTotalByCondition($where,'total_price');
        $lastWeekMoney = get_format_number($lastWeekMoney);

        // 获得对比数据
        $percent = $this->getComparePercent($thisWeekMoney,$lastWeekMoney);
        //周同比类型： 1-上升 ；-1-下降 ；0-等比
        $todayTotalPrice['week_percent_type'] = $percent['type'];
        //周同比
        $todayTotalPrice['week_percent'] = get_format_number($percent['percent']);
        $returnArr['statistics_list'][] = $todayTotalPrice;

        /* 今日新增用户数 */
        $where = [
            ['add_time','between',[$startTime,$nowTime]]
        ];
        $todayUserCount = (new UserService())->getCount($where);

        $where = [
            ['add_time','between',[$lastStartTime,$lastEndTime]]
        ];
        $yestodayUserCount = (new UserService())->getCount($where);
        $todayUserCountArr = [
            'cat_name' => '今日新增用户数',
            'cat_type' => 'totay_new_user',
            'total_count' => $todayUserCount,
        ];

        // 获得对比数据
        $percent = $this->getComparePercent($todayUserCount,$yestodayUserCount);
        //日同比类型： 1-上升 ；-1-下降 ；0-等比
        $todayUserCountArr['today_percent_type'] = $percent['type'];
        //日同比
        $todayUserCountArr['today_percent'] = get_format_number($percent['percent']);

        // 近10日数据
        $todayUserCountArr['list'] = $this->getTenDaysData($todayUserCountArr['cat_type'],$nowTime);

        $returnArr['statistics_list'][] = $todayUserCountArr;
        /* 今日抽成总额 */
        $where = [
            ['use_time','between',[$startTime,$nowTime]],
            ['income','=',1],
            ['type','<>','withdraw'],
        ];
        $todaySpreadCount = (new MerchantMoneyListService())->getTotalByCondition($where,'system_take');

        $where = [
            ['use_time','between',[$lastStartTime,$lastEndTime]],
            ['income','=',1],
        ];
        $yestodaySpreadCount = (new MerchantMoneyListService())->getTotalByCondition($where,'system_take');
        $todaySpreadCountArr = [
            'cat_name' => '今日抽成总额',
            'cat_type' => 'totay_spread_money',
            'total_money' => get_format_number($todaySpreadCount),
        ];

        // 获得对比数据
        $percent = $this->getComparePercent($todaySpreadCount,$yestodaySpreadCount);
        //日同比类型： 1-上升 ；-1-下降 ；0-等比
        $todaySpreadCountArr['today_percent_type'] = $percent['type'];
        //日同比
        $todaySpreadCountArr['today_percent'] = get_format_number($percent['percent']);

        // 近10日数据
        $todaySpreadCountArr['list'] = $this->getTenDaysData($todaySpreadCountArr['cat_type'],$nowTime);

        $returnArr['statistics_list'][] = $todaySpreadCountArr;

        /* 今日充值总额 */
        $where = [
            ['add_time','between',[$startTime,$nowTime]],
            ['paid','=',1],
        ];
        $todayRechargeMoney = (new UserRechargeOrderService())->getTotalByCondition($where,'money');
        $todayRechargeCount = (new UserRechargeOrderService())->getCount($where);

        $todayRechargeCountArr = [
            'cat_name' => '今日充值总额',
            'cat_type' => 'totay_recharge_money',
            'total_money' => get_format_number($todayRechargeMoney),
            'total_count' => get_format_number($todayRechargeCount),
        ];

        // 近10日数据
        $todayRechargeCountArr['list'] = $this->getTenDaysData($todayRechargeCountArr['cat_type'],$nowTime);

        $returnArr['statistics_list'][] = $todayRechargeCountArr;


        return $returnArr;
    }

    /**
     * 获得中部部小块统计数据显示
     * @return array
     */
    public function getMiddleStatisticsData($param) {
        $type = $param['type'] ?? 'sales_money';
        $timeType = $param['time_type'] ?? 'month';

        $returnArr = [];
        $nowTime = time();

        $whereMerchant = [];
        $whereMerchantAll = [];
        if(customization('life_tools') && request()->systemUser['level'] != 2){// 城投生活通定制 商家绑定管理员
            $adminIdArr = (new AdminUserService)->getAdminByFid(request()->systemUser['id']);
        	if($adminIdArr){
                $conditionMerchant[] = ['admin_id', 'in', $adminIdArr];
                $merchantList = (new MerchantService())->getSome($conditionMerchant,'mer_id');
        	}
            if($merchantList){
                $merId = implode(',', array_column($merchantList, 'mer_id'));
                $whereMerchant[] = ['mer_id', 'in', $merId];
                $whereMerchantAll[] = ['o.mer_id', 'in', $merId];
            }else{
                $whereMerchant[] = ['mer_id', '=', 0];
                $whereMerchantAll[] = ['o.mer_id', 'in', [0]];
            }
        }

        // 统计全时段数据条件
        $whereAll = [];
        switch ($timeType){
            case 'month':
                $startTime = date('Y-m-01');
                $endTime = $this->endDayOfMonth(date('Y-m-d H:i:s',$nowTime));
                $endTime = strtotime($endTime);
                $endTime = date('Y-m-d',$endTime);

                $whereAll = [
                    ['o.paid','=',1],
                    ['o.system_status','not in','4,5'],
                    ['o.create_time','between',[strtotime($startTime),strtotime($endTime)+86399]]
                ];                
                $dataArr = [];
                while(strtotime($endTime) >=  strtotime($startTime)){
                    $dataArr[date('d',strtotime($startTime))] = [
                        'start_time' => strtotime($startTime),
                        'end_time' => strtotime($startTime)+86399
                    ];
                    $startTime = date('Y-m-d',strtotime($startTime)+86400);
                }
                break;
            case 'week':
                $date = date('Y-m-d',$nowTime);  //当前日期
                $first = 1; //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
                $w = date('w',strtotime($date));  //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
                // 本周开始日期
                $startTime = date('Y-m-d',strtotime("$date -".($w ? $w - $first : 6).' days')); //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
                // 本周结束日期
                $endTime=date('Y-m-d',strtotime("$startTime +6 days"));  //本周结束日期

                $whereAll = [
                    ['o.paid','=',1],
                    ['o.system_status','not in','4,5'],
                    ['o.create_time','between',[strtotime($startTime),strtotime($endTime)+86399]]
                ];
                while(strtotime($endTime) >=  strtotime($startTime)){
                    $dataArr[date('w',strtotime($startTime))] = [
                        'start_time' => strtotime($startTime),
                        'end_time' => strtotime($startTime)+86399
                    ];
                    $startTime = date('Y-m-d',strtotime($startTime)+86400);
                }
                break;
            case 'year':
                // 本年第一天
                $startTime = date('Y-01-01');
                // 本年最后第一天
                $endTime = date('Y-12-01');

                $whereAll = [
                    ['o.paid','=',1],
                    ['o.system_status','not in','4,5'],
                    ['o.create_time','between',[strtotime($startTime),strtotime(date('Y-12-31'))+86399]]
                ];
                while(strtotime($endTime) >=  strtotime($startTime)){
                    $dataArr[date('m',strtotime($startTime))] = [
                        'start_time' => strtotime($startTime),
                        'end_time' => strtotime($this->endDayOfMonth($startTime))+86399
                    ];
                    $startTime = date('Y-m-d',strtotime($startTime.' +1 month'));
                }
                break;
        }

        if($dataArr){
            foreach ($dataArr as $key => $value){
                $where = [
                    ['paid','=',1],
                    ['system_status','not in','4,5'],
                    ['create_time','between',[$value['start_time'],$value['end_time']]]
                ];
                $where = array_merge($where, $whereMerchant);
                switch ($type){
                    case 'sales_money':
                        $total= (new SystemOrderService())->getTotalByCondition($where,'total_price');
                        break;
                    case 'order_count':
                        $total= (new SystemOrderService())->getCount($where);
                        break;
                }

                switch ($timeType){
                    case 'month':
                        $title = intval($key).'号';
                        break;
                    case 'week':
                        $title = $this->weekList[$key];
                        break;
                    case 'year':
                        $title = intval($key).'月';
                        break;
                }
                $returnArr['statistics_list'][] = [
                    'title' => $title,
                    'value' => get_format_number($total)
                ];
            }
        }

        $whereAll = array_merge($whereAll, $whereMerchantAll);
        $whereAll[] = ['m.status', '=', 1];
        // 商家销售排行榜
        switch ($type){
            case 'sales_money':
                $list = (new SystemOrderService())->getMerchantRanking($whereAll,'sum(total_price+IF(app.product_price > 0,app.product_real_balace_price,0))',7);
                break;
            case 'order_count':
                $list = (new SystemOrderService())->getMerchantRanking($whereAll,'count(o.order_id)',7);
                break;
        }
        $returnArr['mer_list'] = $list;
        return $returnArr;
    }

    /**
     * 待办事项
     * @return array
     */
    public function getBacklog($systemUser) {
        $returnArr = [];
        $pluginMod = new \app\common\model\db\Plugin();

        // 待审核商家数量
        $where = [
            'status' => 2
        ];
        $count = (new \app\merchant\model\service\MerchantService())->getCount($where);
        $returnArr['list'][] = [
            'name' => '待审核商家数量',
            'count' => $count,
            'link_type' => 'new_tab',
            'link_url' => $this->getLinkUrlByMenuId(45,'new_tab')
        ];


        // 待审核店铺数量
        $where = [
            'status' => 2
        ];
        $count = (new MerchantStoreService())->getCount($where);
        $returnArr['list'][] = [
            'name' => '待审核店铺数量',
            'count' => $count,
            'link_type' => 'new_tab',
            'link_url' => $this->getLinkUrlByMenuId(70,'new_tab')
        ];

        //待审核的外卖商品
        if ($pluginMod->where('plugin_name', '外卖管理')->where('status', 1)->find()) {
            $shopGoodsNum = (new \app\mall\model\db\ShopGoods())->getCount(['a.audit_status' => 0]);
            $systemGoodsNum = (new \app\mall\model\db\SystemGoods())->where(['status' => 0])->count();
            $returnArr['list'][] = [
                'name' => '待审核' . cfg('shop_alias_name') . '商品数量',
                'count' => $shopGoodsNum + $systemGoodsNum,
                'link_type' => 'new_blank',
                'link_url' => $this->getLinkUrlByMenuId(718, 'new_blank')
            ];
        }

        //待审核商城商品
        if ($pluginMod->where('plugin_name', '新版商城管理')->where('status', 1)->find()) {
            $count = (new \app\mall\model\db\MallGoods())->getAuditByCondition([['a.is_del', '=', 0], ['a.audit_status', '=', 0], ['c.status', '<>', 4]], false, '', '', '');
            $systemGoodsNum = (new \app\mall\model\db\SystemGoods())->where(['status' => 0])->count();
            $returnArr['list'][] = [
                'name' => '待审核' . cfg('mall_alias_name_new') . '商品数量',
                'count' => $count,
                'link_type' => 'new_blank',
                'link_url' => cfg('site_url').'/v20/public/platform/#/mall/platform.MallPlatformGoods/MallCommodityAudit'
            ];
        }

        // 待审核团购数量
        if ($pluginMod->where('plugin_name', '团购管理')->where('status', 1)->find()) {
            $where = [
                'status' => 2
            ];
            $count = (new \app\group\model\service\GroupService())->getCount($where);
            $returnArr['list'][] = [
                'name' => '待审核'.cfg('group_alias_name').'商品数量',
                'count' => $count,
                'link_type' => 'new_blank',
                'link_url' => $this->getLinkUrlByMenuId(44,'new_blank')
            ];
        }

        //待审核预约商品数量
        if ($pluginMod->where('plugin_name', '预约管理')->where('status', 1)->find()) {
            $count = (new \app\appoint\model\db\Appoint())->where('check_status', '=', 0)->count();
            $returnArr['list'][] = [
                'name' => '待审核' . cfg('appoint_alias_name') . '商品数量',
                'count' => $count,
                'link_type' => 'new_blank',
                'link_url' => $this->getLinkUrlByMenuId(58, 'new_blank')
            ];
        }

        // 待审核店铺资质数量
        $where = [
            ['auth' , 'in', '1,4'],
            ['status' , '<>', '4'],
        ];
        $count = (new MerchantStoreService())->getCount($where);
        $returnArr['list'][] = [
            'name' => '待审核店铺资质数量',
            'count' => $count,
            'link_type' => 'new_tab',
            'link_url' => $this->getLinkUrlByMenuId(195,'new_tab')
        ];

        // 待审核配送员数量 
        $returnArr['list'][] = [
            'name' => '待审核配送员数量',
            'count' =>  (new DeliverUserRegisterService())->getCount(['status'=>0]),
            'link_type' => 'new_tab',
            'link_url' => $this->getLinkUrlByMenuId(672,'new_tab')
        ];

        // 用户实名认证审核
        $where = [
            ['authentication_status' , '=', '0'],
        ];
        $count = (new UserAuthenticationService())->getCount($where);
        $returnArr['list'][] = [
            'name' => '用户实名认证审核',
            'count' => $count,
            'link_type' => 'new_tab',
            'link_url' => $this->getLinkUrlByMenuId(133,'new_tab')
        ];

        // 批发市场商品审核
        $where = [
            ['status' , '=', '0'],
        ];
        $count = (new MarketGoodsService())->getCount($where);
        $returnArr['list'][] = [
            'name' => '批发市场商品审核',
            'count' => $count,
            'link_type' => 'new_blank',
            'link_url' => $this->getLinkUrlByMenuId(219,'new_blank')
        ];

        // 平台活动审核
        $where = [
            ['status' , '=', '0'],
        ];
        $count = (new ExtensionActivityListService())->getCount($where);
        $returnArr['list'][] = [
            'name' => '平台活动审核',
            'count' => $count,
            'link_type' => 'new_blank',
            'link_url' => $this->getLinkUrlByMenuId(49,'new_blank')
        ];

        // 渠道二维码审核
        $where = [
            ['status' , '=', '0'],
        ];
        $count = (new ChanelMsgListService())->getCount($where);
        $returnArr['list'][] = [
            'name' => '渠道二维码审核',
            'count' => $count,
            'link_type' => 'new_tab',
            'link_url' => $this->getLinkUrlByMenuId(88,'new_tab')
        ];

        // 商家群发审核
        $where = [
            ['status' , '=', '0'],
        ];
        $count = (new SendLogService())->getCount($where);
        $returnArr['list'][] = [
            'name' => '商家群发审核',
            'count' => $count,
            'link_type' => 'new_tab',
            'link_url' => $this->getLinkUrlByMenuId(35,'new_tab')
        ];

        return $returnArr;
    }

    function endDayOfMonth($date) {
        list($year, $month) = explode('-',$date);
        $nextYear = $year;
        $nexMonth = $month+1;
        //如果是年底12月 下个月就是1月
        if($month == 12) {
            $nexMonth = "01";
            $nextYear = $year+1;
        }
        $begin = "{$year}-{$month}-01 00:00:00";
        $end = "{$nextYear}-{$nexMonth}-01 00:00:00";
        $day = (strtotime($end) - strtotime($begin) )/ (24*60*60);
        return "{$year}-{$month}-{$day}";
    }

    /**
     * 获得对比结果
     * 公式为：当期同比增长（下降）率(%)=[(当期营收额/上期营收额)-1]*100%。如果计算值为正值（+），则称增长率；如果计算值为负值（-），则称下降率；如果计算值为0，则称持平。
     * @return array
     */
    public function getComparePercent($first, $second) {
        $returnArr = [];
        // 符号
        $diff = $first-$second;
        if ($diff>=0) {
            $fuhao = 1;
        }elseif ($diff<0) {
            $fuhao = -1;
        }

        if ($first!=0 && $second!=0) {
            $compare = $fuhao * abs(get_format_number(($first/$second)-1))*100;
        }elseif ($second!=0&&$first==0 || $second==0&&$first!=0) {
            $compare = $fuhao * 100;
        }else{
            $compare = '0';
        }
        if($compare == 0){
            $fuhao == 0;
        }

        return ['type' => $fuhao, 'percent' => abs($compare)];
    }

    /**
     * 获得近10日的数据
     * @return array
     */
    public function getTenDaysData($type, $time, $whereMerchant = []) {
        $dataArr = [0,1,2,3,4,5,6,7,8,9];
        $start = strtotime(date('Y-m-d', $time));

        $returnArr  = [];
        foreach ($dataArr as $day){
            $startTime = ($day-9)*86400 + $start;
            $endTime = ($day-9)*86400 + $start + 86399;
            switch ($type){
                case 'totay_new_user':

                    $where = [
                        ['add_time','between',[$startTime,$endTime]]
                    ];
                    $count = (new UserService())->getCount($where);
                    break;
                case 'totay_spread_money':
                    $where = [
                        ['use_time','between',[$startTime,$endTime]],
                        ['income','=',1],
                        ['type','<>','withdraw'],
                    ];
                    $where = array_merge($where, $whereMerchant);
                    $count = (new MerchantMoneyListService())->getTotalByCondition($where,'system_take');
                    break;
                case 'totay_recharge_money':
                    $where = [
                        ['add_time','between',[$startTime,$endTime]],
                        ['paid','=',1],
                    ];
                    $count = (new UserRechargeOrderService())->getTotalByCondition($where,'money');
                    break;
            }

            $returnArr[] = [
                'title' => date('Y-m-d',$startTime),
                'value' => get_format_number($count)
            ];
        }
        return $returnArr;
    }
    /**
     * 是否展示关闭老版商城和餐饮的按钮
     */
    public function closeOldShow()
    {
        $merchantMenuMall = (new NewMerchantMenu())->where(['name'=>'商城管理','module'=>'Mall','status'=>1])->value('id');
        $merchantMenuMeal = (new NewMerchantMenu())->where(['name'=>'餐饮管理','module'=>'Foodshop','status'=>1])->value('id');
        $pluginMall = (new Plugin())->where(['plugin_name'=>'商城管理','plugin_label'=>'mall','show'=>1])->value('plugin_id');
        $pluginMeal = (new Plugin())->where(['plugin_name'=>'餐饮管理','plugin_label'=>'foodshop','show'=>1])->value('plugin_id');
        if($merchantMenuMall || $merchantMenuMeal || $pluginMall || $pluginMeal){
            return ['isShow'=>1];
        }
        return ['isShow'=>0];
    }
    
    /**
     * 关闭老版业务
     */
    public function closeOld($business)
    {
        //关闭老版商城
        if(in_array('mall',$business)){
            //关闭商家菜单
            (new NewMerchantMenu())->where(['name'=>'商城管理','module'=>'Mall'])->update(['status'=>0]);
            //关闭应用中心入口
            (new Plugin())->where(['plugin_name'=>'商城管理','plugin_label'=>'mall'])->update(['show'=>0]);
        }
        //关闭老版餐饮
        if(in_array('meal',$business)){
            //关闭商家菜单
            (new NewMerchantMenu())->where(['name'=>'餐饮管理','module'=>'Foodshop'])->update(['status'=>0]);
            //关闭应用中心入口
            (new Plugin())->where(['plugin_name'=>'餐饮管理','plugin_label'=>'foodshop'])->update(['show'=>0]);
        }
        return true;
    }
    public function getCityInfoList($whereArr, $field='*',$page=1,$limit=10){
        $areaDb=new Area();
        $resObj=$areaDb->getLists($whereArr,$field,$page,$limit);
        $cityInfoArr=array('city_list'=>array());
        if($resObj && !$resObj->isEmpty()){
            $nextRegionArr=$resObj->toArray();
            $cityInfoArr['city_list']=$nextRegionArr;
        }
        return $cityInfoArr;
    }
}