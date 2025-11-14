<?php

namespace app\deliver\controller;

use app\common\model\service\AppapiAppConfigService;
use app\common\model\service\LangService;
use app\deliver\model\service\DeliverUserService;
use think\facade\Db;

/**
 * 全局配置
 * @author: 张涛
 * @date: 2020/9/7
 * @package app\deliver\controller
 */
class ConfigController extends ApiBaseController
{

    public function index()
    {
        try {
            $arr = [];
            $appConfig = (new AppapiAppConfigService())->getAllWithIndex();
            //app端底部显示ICP备案显示
            $arr['app_show_footer'] = [];
            if (isset($appConfig['deliver_icp']) && $appConfig['deliver_icp']) {
                $arr['app_show_footer'][] = [
                    'title' => 'ICP备案号：' . $appConfig['deliver_icp'],
                    'icon' => '',
                    'url' => 'https://beian.miit.gov.cn'
                ];
            }


            // 隐私政策
            $arr['privacy_policy_md5'] = isset($appConfig['deliver_privacy_policy']) && $appConfig['deliver_privacy_policy'] ? md5($appConfig['deliver_privacy_policy']) : '';
            $arr['privacy_policy_url'] = isset($appConfig['deliver_privacy_policy']) && $appConfig['deliver_privacy_policy'] ? cfg('site_url') . '/wap.php?c=Login&a=privacy_policy&app=deliver' : '';

            // 注册协议
            $arr['register_agreement_md5'] = isset($appConfig['deliver_user_agreement']) && $appConfig['deliver_user_agreement'] ? md5($appConfig['deliver_user_agreement']) : '';
            $arr['register_agreement_url'] = cfg('site_url') . '/wap.php?c=Login&a=user_agreement&type=deliver';

            //是否强制升级
            $arr['android_version'] = $appConfig['deliver_android_version'];
            $arr['android_vcode'] = $appConfig['deliver_android_vcode'];
            $arr['android_url'] = replace_file_domain($appConfig['deliver_android_url']);
            $arr['android_vdes'] = $appConfig['deliver_android_vdes'];

            $arr['ios_must_upgrade'] = $appConfig['deliver_ios_must_upgrade'] ?? false;
            $arr['android_must_upgrade'] = $appConfig['deliver_android_must_upgrade'] ?? false;

            //APP下载地址
            $arr['app_download_url'] = cfg('site_url') . '/topic/app_wap_deliver.html';
            //地图类型
            $arr['map_config'] = cfg('map_config');
            $arr['baidu_map_ak'] = cfg('baidu_map_ak');
            $arr['google_map_ak'] = cfg('google_map_ak');
            //开启国际手机
            $arr['international_phone'] = intval(cfg('international_phone'));
            //多语言列表
            $arr['lang_config'] = (new LangService())->langList();
            //联系电话
            $arr['site_phone'] = array_filter(explode(' ', cfg('site_phone')));
            //登录方式
            $arr['login_method'] = intval(cfg('deliver_login_method'));
            //轮询间隔，单位：秒
            $arr['polling_interval_config'] = [
                'new_order' => 5,
                'order_marker' => 5,
                'im_message' => 10
            ];
            $arr['is_hark_system'] = cfg('is_hark_system');
            $arr['open_route_plan'] = true;
            $arr['open_scan_order'] = true;
            $arr['open_navigation_sound'] = true;
            $arr['open_im'] = (cfg('deliver_app_im_open') == 1);
            $arr['im_unread_message_count'] = 0;
            $arr['im_url'] = '';
            $arr['tester'] = cfg('deliver_tester_phone');

            //登录用户根据配送员权限设置配送订单类型
            if ($this->request->log_uid) {
                //获取客服未读消息数
                $imUser = 'deliver_' . $this->request->log_uid;
                $arr['im_url'] = build_store_im_conversation_url($imUser,'deliver2user');
                $arr['im_unread_message_count'] =  (new \app\im\model\ImConversation())->getUnreadMsgCount($imUser, '');

                $arr['order_types'][] = ['name' => L_('全部'), 'order_type' => 'all'];
                $deliverUser = (new DeliverUserService())->getOneUser(['uid' => $this->request->log_uid], 'order_type,group');
                if ($deliverUser) {
                    if ($deliverUser['group'] == 2) {
                        $arr['order_types'][] = ['name' => L_('商家单'), 'order_type' => 'merchant_order'];
                    } else {
                        $types = explode(',', $deliverUser['order_type']);
                        if (in_array(1, $types)) {
                            $arr['order_types'][] = ['name' => L_('商家单'), 'order_type' => 'merchant_order'];
                        }
                        if (in_array(2, $types)) {
                            $arr['order_types'][] = ['name' => L_('帮买单'), 'order_type' => 'service_buy'];
                        }
                        if (in_array(3, $types)) {
                            $arr['order_types'][] = ['name' => L_('帮送单'), 'order_type' => 'service_send'];
                        }
                        if (in_array(4, $types)) {
                            $arr['order_types'][] = ['name' => L_('社区团购'), 'order_type' => 'village_group_order'];
                        }
                    }
                }
            } else {
                $arr['order_types'] = [
                    ['name' => L_('全部'), 'order_type' => 'all'],
                    ['name' => L_('商家单'), 'order_type' => 'merchant_order'],
                    ['name' => L_('帮买单'), 'order_type' => 'service_buy'],
                    ['name' => L_('帮送单'), 'order_type' => 'service_send'],
                ];
            }


            $arr['income_types'] = [
                [
                    'name' => L_('全部收入'),
                    'value' => '0'
                ],
                [
                    'name' => L_('配送收入'),
                    'value' => '1'
                ],
                [
                    'name' => L_('顾客打赏'),
                    'value' => '2'
                ]
            ];
            $arr['android_white_setting'] = [
                'xiaomi' => cfg('site_url') . '/wap.php?g=Wap&c=Help&a=appAuthSet&brand=xiaomi',
                'vivo' => cfg('site_url') . '/wap.php?g=Wap&c=Help&a=appAuthSet&brand=vivo',
                'oppo' => cfg('site_url') . '/wap.php?g=Wap&c=Help&a=appAuthSet&brand=oppo',
                'meizu' => cfg('site_url') . '/wap.php?g=Wap&c=Help&a=appAuthSet&brand=meizu',
                'huawei' => cfg('site_url') . '/wap.php?g=Wap&c=Help&a=appAuthSet&brand=huawei'
            ];
            $arr['task_map_introduction'] = cfg('site_url') . '/wap.php?g=Wap&c=Help&a=taskMap';

            $arr['open_register'] = boolval(cfg('open_deliver_register'));
            $arr['register_url'] = cfg('site_url') . '/packapp/deliver_web/register.html?t='.time();
            $arr['open_wallet'] = cfg('open_deliver_wallet');  //0:关闭  1：全职配送员  2：兼职配送员   3：全职+兼职
            $arr['wallet_url'] = cfg('site_url') . '/packapp/deliver_web/wallet.html?t='.time();
            $arr['deliver_minimum_withdrawal_amount'] = cfg('deliver_minimum_withdrawal_amount') ?: 0; //配送员提现最低金额
            $arr['wechat_name'] = cfg('wechat_name');

            //弃用
            $arr['withdraw_type'] = [
                [
                    'name'=>'微信' . (cfg('open_real_time_withdrawal') ? '('.L_('实时到账').')' : ''),
                    'value'=>'wechat'
                ],
                [
                    'name'=>'支付宝' . (cfg('open_real_time_withdrawal') ? '('.L_('实时到账').')' : ''),
                    'value'=>'alipay'
                ]
            ];


            $arr['deliver_rest_can_take_order'] = intval(cfg('deliver_rest_can_take_order'));

            $arr['currency_symbol'] = cfg('Currency_symbol');
            $arr['currency_txt'] = cfg('Currency_txt');
            $arr['open_multilingual'] = intval(cfg('open_multilingual'));
            $arr['default_language'] = cfg('default_language');
            $arr['open_deliver_face_recognition'] = intval(cfg('open_deliver_face_recognition'));
            $arr['face_recognition_default_image'] = cfg('face_recognition_default_image');

            $arr['paotui_goods_category'] = [
                ['name' => L_('鲜花')],
                ['name' => L_('餐饮')],
                ['name' => L_('生鲜')],
                ['name' => L_('文件')],
                ['name' => L_('电子产品')],
                ['name' => L_('钥匙')],
                ['name' => L_('服饰')],
                ['name' => L_('其他')],
            ];

            //是否隐藏骑手端app显示
            $arr['hide_map'] = intval(cfg('deliver_app_location'));

            return api_output(0, $arr, L_('成功'));
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }

    }
}
