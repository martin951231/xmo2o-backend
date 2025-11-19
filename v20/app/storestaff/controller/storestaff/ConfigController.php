<?php
/**
 * 店员后台配置信息
 * author by hengtingmei
 */
namespace app\storestaff\controller\storestaff;

use app\common\controller\CommonBaseController;
use app\common\model\service\config\AppapiAppConfigService;
use app\common\model\service\config\LangService;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;
use token\Token;
class ConfigController extends CommonBaseController{
    public function initialize()
    {
        parent::initialize();
    }


    /**
     * desc: 返回网站基本信息
     * return :array
     */
    public function index(){
        $ticket = $this->request->param('ticket');

        // 网站名称
        $returnArr['site_name'] = cfg('site_name');
        // 网站logo
        $returnArr['site_logo'] = replace_file_domain(cfg('site_logo'));
        // 系统后台LOGO
        $returnArr['system_admin_logo'] = cfg('system_admin_logo') ? replace_file_domain(cfg('system_admin_logo')) : "./tpl/System/Static/images/pigcms_logo.png";
        // 网站描述
        $returnArr['site_desc'] = cfg('site_desc') ? cfg('site_desc') : '';
        //网站电话
        $returnArr['site_phone'] 	 = 	strval(cfg('site_phone'));
        //网站QQ
        $returnArr['site_qq'] 		 = 	strval(cfg('site_qq'));
        //网站site_email
        $returnArr['site_email'] 		 = 	strval(cfg('site_email'));
        //网站公众号
        $returnArr['wechat_qrcode'] =  strval(cfg('wechat_qrcode'));

        $returnArr['can_register'] = true;// 是否可以注册新账号 app不可注册账号此配置项仅用于app审核使用
        $returnArr['is_packapp'] = true;

        // 获得app版本信息
        $appConfig  =   (new AppapiAppConfigService())->get();
        $returnArr['staff_android_v'] = strval($appConfig['staff_android_v']);// 安卓版本号
        $returnArr['staff_android_vcode'] = strval($appConfig['staff_android_vcode']);//店员app Android版本号
        $returnArr['staff_android_url'] = strval($appConfig['staff_android_url']);//店员app Android下载地址
        $returnArr['staff_android_vdesc'] = strval($appConfig['staff_android_vdesc']);//店员app Android版本描述
        $returnArr['storestaff_android_package_name'] = $appConfig['storestaff_android_package_name'] ?: '';//店员app Android包名
        $returnArr['storestaff_ios_package_name'] = $appConfig['storestaff_ios_package_name'] ?: '';//店员app IOS包名
        $returnArr['storestaff_ios_must_upgrade'] = isset($appConfig['storestaff_ios_must_upgrade']) && $appConfig['storestaff_ios_must_upgrade'] ? true : false;
        $returnArr['storestaff_android_must_upgrade'] = isset($appConfig['storestaff_android_must_upgrade']) && $appConfig['storestaff_android_must_upgrade'] ? true : false;
        
        //app端底部显示ICP备案显示
        $returnArr['app_show_footer'] = [];
        if (isset($appConfig['storestaff_icp']) && $appConfig['storestaff_icp']) {
            $returnArr['app_show_footer'][] = [
                'title' => 'ICP备案号：' . $appConfig['storestaff_icp'],
                'icon' => '',
                'url' => 'https://beian.miit.gov.cn'
            ];
        }

        //顶象验证码安全的appid
        $returnArr['dingxiang_captcha_appid'] = cfg('dingxiang_captcha_appid') ?: (cfg('qcloud_captcha_appid') ?:'');
        $returnArr['code_captcha_url'] = (cfg('dingxiang_captcha_appid') || cfg('qcloud_captcha_appid'))  ? cfg('site_url') . '/wap.php?c=Login&a=app_sms_safe' : '';

        // 隐私政策
        $returnArr['privacy_policy_url'] = cfg('site_url').'/packapp/storestaff/agreement.html';
        $returnArr['privacy_policy_md5'] = md5($appConfig['staff_privacy_policy'].cfg('register_agreement'));
        // 注册协议
        $returnArr['register_agreement_url'] = cfg('site_url') . '/wap.php?c=Login&a=user_agreement&type=storestaff';

        // 商家App
        $returnArr['mer_android_package_name'] = $appConfig['mer_android_package_name'] ?: '';
        $returnArr['mer_ios_package_name'] = $appConfig['mer_ios_package_name'] ?: '';
        $returnArr['mer_ios_download_url'] = $appConfig['mer_ios_download_url'] ?: '';
        $returnArr['mer_android_download_url'] = $appConfig['mer_android_url'] ?: '';


        $returnArr['map_config'] = cfg('map_config') ? cfg('map_config') : 'baidu';//地图类型
        $returnArr['baidu_map_ak'] = cfg('baidu_map_ak') ? cfg('baidu_map_ak') : '';//百度地图AK密钥
        if($returnArr['map_config'] == 'google'){
            $returnArr['google_map_ak'] = cfg('google_map_ak') ? cfg('google_map_ak') : '';//谷歌地图key
        }

        $returnArr['Currency_symbol'] = cfg('Currency_symbol') ? cfg('Currency_symbol') : '￥';//货币符号
        $returnArr['score_name'] = cfg('score_name') ? cfg('score_name') : L_('积分');// 积分名称

        $returnArr['open_multilingual'] = cfg('open_multilingual') ? cfg('open_multilingual') : '0';//是否开启多语言切换
        $returnArr['international_phone'] = cfg('international_phone') ? cfg('international_phone') : '0';//开启国际手机
        $returnArr['lang_config'] = (new LangService())->langList();//语言配置


        $returnArr['h5_qrcode_url'] =  cfg('site_url').'/index.php?c=Recognition&a=get_own_qrcode&qrCon='.urlencode(cfg('site_url').'/packapp/storestaff/index.html');	//H5页面
//        $returnArr['app_qrcode_url'] =  $returnArr['staff_android_url'] ? cfg('site_url'].'/index.php?c=Recognition&a=get_own_qrcode&qrCon='.urlencode(cfg('site_url'].'/topic/app_wap_clerk.html') : '';	//APP页面
        $returnArr['footer_copyright'] = 'Copyright &copy; '.date('Y').' '.cfg('site_name').(cfg('top_domain') ?? '').' '.L_('版权所有').' '.(cfg('site_icp') ? '<a href="http://www.miibeian.gov.cn/" target="_blank">'.cfg('site_icp').'</a>' : '');

        $returnArr['mall_deliver_to_expess'] = cfg('mall_deliver_to_expess') ? true : false;//快递配送转平台配送

        // 安卓白名单设置
        $returnArr['android_white_setting'] = [
            'xiaomi' => cfg('site_url') . '/wap.php?g=Wap&c=Help&a=appAuthSet&brand=xiaomi',
            'vivo' => cfg('site_url') . '/wap.php?g=Wap&c=Help&a=appAuthSet&brand=vivo',
            'oppo' => cfg('site_url') . '/wap.php?g=Wap&c=Help&a=appAuthSet&brand=oppo',
            'meizu' => cfg('site_url') . '/wap.php?g=Wap&c=Help&a=appAuthSet&brand=meizu',
            'huawei' => cfg('site_url') . '/wap.php?g=Wap&c=Help&a=appAuthSet&brand=huawei'
        ];
        // 处理登录信息
        $returnArr['ticket'] = '';
        $returnArr['staff_info'] = ['error'=>0];
        if($ticket){
            // 获取店员登录信息
            $token = Token::checkToken($ticket);
            $staffId = $token['memberId'];
            if($staffId){// 店员id
                // 店员信息
                $nowStaff = (new MerchantStoreStaffService())->getStaffById($staffId);
                unset($nowStaff['password']);

                // 店铺信息
                $nowStore = (new MerchantStoreService())->getStoreByStoreId($nowStaff['store_id']);

                // 生成ticket
                $ticket = Token::createToken($staffId);
                $returnArr['ticket'] = $ticket;
                $returnArr['staff_info'] = $nowStaff ?: ['error'=>''];
                $returnArr['staff_info']['store_name'] = $nowStore['name'];
            }

        }
        return api_output(0, $returnArr);
    }
}