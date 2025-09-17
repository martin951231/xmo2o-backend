<?php
/**
 * PersonalCenterController.php
 * 个人中心controller
 * Create on 2020/9/21 10:20
 * Created by zhumengqun
 */

namespace app\mall\controller\api;

use app\BaseController;
use app\mall\model\service\UserService;

class PersonalCenterController extends BaseController
{
    /**
     * 个人中心信息获取
     * @return \json
     */
    public function getPersonalInfo()
    {
        $log_uid = request()->log_uid ? request()->log_uid : 0;
        $userService = new UserService();
        try {
            $arr = $userService->getPersonalInfo($log_uid);

            //关闭用户余额
            $arr['hide_user_money'] = false;
            if (cfg('open_ushow_user_moneyser_money') != 1 && isset($arr['now_money']) && $arr['now_money'] == 0) {
                $arr['hide_user_money'] = true;
            }
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }
}