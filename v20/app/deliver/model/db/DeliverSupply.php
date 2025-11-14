<?php

namespace app\deliver\model\db;

use think\Model;

/**
 * 配送表
 * @author: 张涛
 * @date: 2020/9/9
 */
class DeliverSupply extends Model
{
	protected $pk = 'supply_id';
	const STATUS_CANCEL = 0; //取消
	const ITEM_USER_PUBLISH = 3; //帮我送
    /**
     * 获取一条配送记录
     * @param $where
     * @param string $fields
     * @author: 汪晨
     * @date: 2021/1/27
     */
    public function geOrder($where, $fields = '*')
    {
        return $this->where($where)->field($fields)->find();
    }

}