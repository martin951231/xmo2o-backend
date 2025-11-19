<?php

namespace app\storestaff\controller\appapi;

use app\internal_card\model\service\InternalCardService;
use app\merchant\model\db\MerchantStoreStaff;
use think\response\Json;

/**
 * 配置参数
 *
 * @author: 张涛
 * @date: 2020/11/04
 */
class ConfigController
{
	public function index()
	{
		// 网站名称
		$returnArr['site_name'] = cfg('site_name');
		// 网站logo
		$returnArr['site_logo'] = replace_file_domain(cfg('site_logo'));
		// 系统后台LOGO
		$returnArr['system_admin_logo'] = cfg('system_admin_logo') ? replace_file_domain(cfg('system_admin_logo')) : "./tpl/System/Static/images/pigcms_logo.png";
		// 网站描述
		$returnArr['site_desc'] = cfg('site_desc');

		//货币符号
		$returnArr['currency_symbol'] = cfg('Currency_symbol') ? cfg('Currency_symbol') : '￥';

		$returnArr['dining_rules'] = '出餐时间=客户期望送达时间-骑手配送时间 （系统自动算出）用于您的出餐排序';
		return api_output(0, $returnArr);
	}

    public function internalCard(): Json
    {
        $uid = intval(request()->log_uid);
        $phone = input('phone');
        $password = input('password');

        $staff = MerchantStoreStaff::where(['id' => $uid])->find();
        
        empty($staff) && throw_exception(L_('店员未登录'));
        empty($phone) && throw_exception(L_('手机号不能为空'));

        $data = (new InternalCardService())->internalCard($staff['store_id'], $phone, $password);

        return api_output(0, $data);
    }

}
