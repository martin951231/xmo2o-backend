<?php

namespace app\deliver\model\db;

use think\Model;

/**
 * 配送员
 * @author: 张涛
 * @date: 2020/9/7
 * @package app\deliver\model\db
 */
class DeliverUser extends Model
{
    //正常
    const STATUS_NORMAL = 1;

    //禁用
    const STATUS_FORBIDDEN = 0;

    //删除
    const STATUS_DEL = 4;

    /**
     * 获取一条记录
     * @param $where
     * @param string $fields
     * @author: 张涛
     * @date: 2020/9/7
     */
    public function getOne($where, $fields = '*')
    {
        return $this->where($where)->field($fields)->find();
    }
}
