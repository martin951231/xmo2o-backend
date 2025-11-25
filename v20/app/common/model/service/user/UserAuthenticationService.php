<?php
/**
 * 用户实名认证
 * add by 衡婷妹
 */

namespace app\common\model\service\user;

use app\common\model\db\UserAuthentication;

class UserAuthenticationService{
    public $userAuthenticationModel = null;
    public function __construct()
    {
        $this->userAuthenticationModel = new UserAuthentication();
    }


    /**
     * 统计订单数量
     * @param  $where
     * @return string
     */
    public function getCount($where){
        return $this->userAuthenticationModel->getCount($where);
    }
}