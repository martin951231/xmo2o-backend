<?php
/**
 * 用户等级
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 14:25
 */

namespace app\common\model\service;

use app\common\model\db\UserLevel;

class UserLevelService
{
    public $userLevelObj = null;
    public function __construct()
    {
        $this->userLevelObj = new UserLevel();
    }

    /**
     * 获取用户等级信息
     * User: chenxiang
     * Date: 2020/6/1 14:39
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getOne($where = [], $field = true) {
        $result = $this->userLevelObj->getOne($where, $field);
        return $result;
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true, $order = true, $page = 0, $limit = 0)
    {
        $start = ($page-1)*$limit;
        $result = $this->userLevelObj->getSome($where, $field, $order, $start, $limit);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }
}