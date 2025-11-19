<?php

/**
 * 团购购买详情表
 * Author: 衡婷妹
 * Date Time: 2020/11/27 16:19
 */
namespace app\group\model\db;

use think\Model;
use think\facade\Db;
class GroupBuyerList extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取拼团小组成员 包括机器人
     * @param $data array 数据
     * @return model
     */
    public function getBuyerListByJoin($where, $field=true, $order=[])
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('b')
            ->where($where)
            ->field($field)
            ->leftJoin($prefix.'user u ','b.uid = u.uid')
            ->leftJoin($prefix.'group_order o ','b.order_id = o.order_id')
            ->order($order)
            ->select();
        return $result;
    }

}