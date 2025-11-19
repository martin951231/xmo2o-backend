<?php
/**
 * 签到时间
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/29 14:09
 */

namespace app\common\model\service;

use app\common\model\db\UserSign;

class UserSignService
{
    public $userSignObj = null;
    public function __construct()
    {
        $this->userSignObj = new UserSign();
    }

    /**
     * 获取用户签到时间
     * User: chenxiang
     * Date: 2020/5/29 14:22
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return array|mixed|\think\Model|null
     */
    public function getUserSignData($where = [], $field = true, $order = '') {
        $result = $this->userSignObj->getUserSignData($where, $field, $order);
        return $result;
    }

    /**
     * 添加用户签到记录
     * User: chenxiang
     * Date: 2020/5/29 14:45
     * @param array $data
     * @return mixed
     */
    public function addUserSign($data = []) {
        $result = $this->userSignObj->addUserSign($data);
        return $result;
    }

    /**
     * 统计个数
     * User: chenxiang
     * Date: 2020/6/1 10:54
     * @param array $where
     * @return mixed
     */
    public function countNum($where = []) {
        $result = $this->userSignObj->countNum($where);
        return $result;
    }
}