<?php
/**
 * 商品库存阈值设置表
 */

namespace app\common\model\db;
use think\Model;
class StockWarn extends Model {
    /**
     * 查询有设置提醒人员的商家配置信息
     */
    public function getSet($where,$field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $data = $this->alias('a')
            ->join($prefix.'warn_user b','a.mer_id = b.mer_id AND b.is_warn_mall=1 AND b.is_del=0 AND b.status=1','left')
            ->join($prefix.'user c','b.phone = c.phone','left')
            ->where($where)
            ->field($field)
            ->group('a.mer_id,b.pigcms_id')
            ->select()
            ->toArray();
        return $data;
    }

    /**
     * 查询有设置提醒人员的商家配置信息
     */
    public function getGroupSet($where,$field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $data = $this->alias('a')
            ->join($prefix.'warn_user b','a.mer_id = b.mer_id AND b.is_warn_group=1 AND b.is_del=0 AND b.status=1','left')
            ->join($prefix.'user c','b.phone = c.phone','left')
            ->where($where)
            ->field($field)
            ->group('a.mer_id,b.pigcms_id')
            ->select()
            ->toArray();
        return $data;
    }
}