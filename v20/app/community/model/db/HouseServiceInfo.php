<?php
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseServiceInfo extends Model
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
    public function getList($where, $field = true, $order = 'id desc', $page = 0, $limit = 10)
    {
        $sql = $this->where($where)->field($field)->order($order);
        if ($page) {
            $list = $sql->page($page, $limit)->select();
        } else {
            $list = $sql->select();
        }
        return $list;
    }
}
