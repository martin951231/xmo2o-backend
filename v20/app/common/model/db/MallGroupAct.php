<?php
/**
 * MallGroupAct.php
 * 营销活动-拼团
 * Create on 2021/2/22 16:23
 * Created by zhumengqun
 */

namespace app\common\model\db;

use think\Model;

class MallGroupAct extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 根据条件删除
     */
    public function delOne($where)
    {
        return $this->where($where)->delete();
    }

    public function getInfo($where, $field, $order, $page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('act')
            ->join($prefix . 'shop_goods gd', 'act.good_id = gd.goods_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $arr;
    }

    public function getInfoCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        return $this->alias('act')
            ->join($prefix . 'shop_goods gd', 'act.good_id = gd.goods_id')
            ->where($where)
            ->count();
    }
}