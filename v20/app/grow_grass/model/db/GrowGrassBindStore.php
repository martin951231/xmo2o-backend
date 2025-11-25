<?php
/**
 * 种草文章绑定店铺表model
 * Author: hengtingmei
 * Date Time: 2021/5/15 11:08
 */

namespace app\grow_grass\model\db;

use think\Model;

class GrowGrassBindStore extends Model
{

    use \app\common\model\db\db_trait\CommonFunc;

    public function getSome($where = [], $order = true, $page = 0, $limit = 0)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $sql = $this->alias('b')
            ->leftJoin($prefix . 'merchant_store s', 's.store_id=b.store_id')
            ->where($where)
            ->order($order);
        if ($limit) {
            $sql->limit($page, $limit);
        }
        $result = $sql->select();
        return $result;
    }
}