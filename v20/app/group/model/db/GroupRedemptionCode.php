<?php


namespace app\group\model\db;


use think\Model;

class GroupRedemptionCode extends Model
{
    public function getList($where, $field = '*', $page = 0, $pageSize = 10)
    {
        $query = $this->alias('c')->field($field)
            ->join('group g', 'c.group_id = g.group_id')
            ->join('group_order o', 'c.order_id = o.order_id', 'left')
            ->join('merchant_store s', 'o.store_id = s.store_id', 'left')
            ->join('user u', 'o.uid = u.uid', 'left')
            ->where($where)
            ->order('c.id desc');
        if ($page > 0) {
            $list = $query->paginate($pageSize);
        } else {
            $list = $query->select();
        }
        return $list->toArray();
    }
}