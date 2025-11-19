<?php
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class MerchantStore extends Model
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
    public function getList($where, $field = 'ms.*', $order = 'ms.store_id desc', $page = 0, $limit = 10)
    {
        $sql = $this->alias('ms')->leftJoin('merchant_store_foodshop msm','ms.store_id=msm.store_id')
            ->leftJoin('house_village_meal hvm','ms.store_id=hvm.store_id')
            ->leftJoin('merchant m','m.mer_id=ms.mer_id')
            ->where($where)->field($field)->order($order);
        if ($page) {
            $list = $sql->page($page, $limit)->select();
        } else {
            $list = $sql->select();
        }
        return $list;
    }
}
