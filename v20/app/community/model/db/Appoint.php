<?php
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class Appoint extends Model
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
    public function getList($where, $field = 'a.*', $order = 'a.appoint_id desc', $page = 0, $limit = 10)
    {
        $sql = $this->alias('a')->leftJoin('merchant m','a.mer_id=m.mer_id')
            ->leftJoin('house_village_appoint hva','hva.appoint_id=a.appoint_id')
            ->where($where)->field($field)->order($order);
        if ($page) {
            $list = $sql->page($page, $limit)->select();
        } else {
            $list = $sql->select();
        }
        return $list;
    }
}
