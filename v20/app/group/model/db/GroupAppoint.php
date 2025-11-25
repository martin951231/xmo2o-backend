<?php


namespace app\group\model\db;


use think\Model;

class GroupAppoint extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $pageSize
     * @return mixed
     * 预约到店列表
     */
    public function getAppointArriveList($where, $field, $order, $page, $pageSize)
    {
        $result = $this->alias('g')
            ->where($where)
            ->field($field)
            ->join('merchant_store s', 's.store_id = g.store_id')
            ->join('user u', 'g.uid = u.uid');
        $assign['count'] = $result->count();
        $assign['list'] = $result->order($order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $assign;
    }
}