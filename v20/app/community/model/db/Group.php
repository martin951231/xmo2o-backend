<?php
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class Group extends Model
{
    /**
     * Notes:获取全部
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     */
    public function getList($where, $field = 'g.*', $order = 'g.group_id desc', $page = 0, $limit = 10)
    {
        $sql = $this->alias('g')->leftJoin('merchant m','g.mer_id=m.mer_id')
            ->leftJoin('house_village_group hvg','hvg.group_id=g.group_id')
            ->where($where)->field($field)->order($order);
        if ($page) {
            $list = $sql->page($page, $limit)->select();
        } else {
            $list = $sql->select();
        }
        return $list;
    }
}
