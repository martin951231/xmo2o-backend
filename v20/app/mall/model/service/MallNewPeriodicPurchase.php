<?php


namespace app\mall\model\service;

use think\facade\Db;
use think\Model;
class MallNewPeriodicPurchase extends Model
{
    /**
     * @param $condition
     * @return array
     * 查询商品参与的活动详情
     */
    public function getDetail($condition){
        $prefix = config('database.connections.mysql.prefix');
        $field='a.start_time,a.end_time,s.*,g.*';
        $result = $this ->alias('s')
            ->join($prefix.'mall_activity'.' a','s.id = a.act_id')
            ->join($prefix.'mall_goods'.' g','g.goods_id = m.goods_id')
            ->where($condition)
            ->field($field)
            ->find()
            ->toArray();
        return $result;
    }
}