<?php
/**
 * Created by PhpStorm.
 * Author: win7
 * Date Time: 2020/5/9 10:24
 */

namespace app\community\model\service;


use app\community\model\db\User;
use app\common\model\service\UserService as CommonUserService;

class UserService
{

    /**
     * 获取信息
     * @author: wanziyang
     * @date_time:2020/5/9 10:25
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @return array|null|\think\Model
     */
    public function getUserOne($where,$field = true) {
        // 初始化 数据层
        $db_user = new User();
        $info = $db_user->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }

    /**
     * 添加用户
     * @author lijie
     * @date_time 2020/09/01 10:52
     * @param $data
     * @return int|string
     */
    public function addUser($data)
    {
        $server_common_user = new CommonUserService();
        if (!isset($data['source']) || !$data['source']) {
            $data['source'] = 'houseautoreg';
        }
        $reg_result = $server_common_user->autoReg($data);
        if ($reg_result && isset($reg_result['uid'])) {
            $uid = $reg_result['uid'];
        } else {
            $uid = 0;
        }
        return $uid;
    }
}