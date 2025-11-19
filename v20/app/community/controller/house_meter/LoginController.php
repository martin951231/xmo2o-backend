<?php

/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/4/8 13:33
 */

namespace app\community\controller\house_meter;

use app\common\model\service\admin_user\SystemMenuService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseMeterService;
use app\community\model\service\HouseMeterMenuService;

class LoginController extends CommunityBaseController{

    public $serviceHouseMeter;
    public function initialize()
    {
        parent::initialize();
        $this->serviceHouseMeter = new HouseMeterService();
    }

    /**
     * Notes: 登录
     * @return \json
     * @author: zhubaodi
     * @datetime: 2021/4/8 13:34
     */
    public function check() {
        $username = $this->request->param('username', '', 'trim');
        if(empty($username)){
            return api_output_error(1001,'请上传账号！');
        }
        $pwd = $this->request->param('password', '', 'trim');
        if(empty($pwd)){
            return api_output_error(1001,'请上传密码！');
        }

        $login_data = [
            'username' => $username,
            'password' => $pwd,
        ];
        try {
            $data = $this->serviceHouseMeter->login($login_data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if($data) {
            return api_output(0, $data, "登录成功");
        }
        return api_output_error(-1, "账号或密码错误");
    }


    /**
     * 登录返回
     * @author:zhubaodi
     * @date_time: 2021/4/9 9:21
     */
    public function userInfo(){
        $admin_id=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $returnArr = $this->serviceHouseMeter->formatUserData($admin_id);
        if (!$returnArr) {
            return api_output_error(1002, "用户不存在或未登录");
        }
        return api_output(0, $returnArr);
    }

    /**
     * desc: 返回网站菜单信息,用于菜单展示
     * @author:zhubaodi
     * @date_time: 2021/4/9 9:52
     * return :array
     */
    public function menuList(){
        // 菜单
        $admin_id=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $houseMeterMenuService = new HouseMeterMenuService();
        $systemMenu = $houseMeterMenuService->formartMenuList($admin_id);
        $returnArr['systemMenu'] = $systemMenu;
        return api_output(0, $returnArr);
    }
}
