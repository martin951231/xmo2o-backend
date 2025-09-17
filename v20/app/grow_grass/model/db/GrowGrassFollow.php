<?php
/**
 * 种草文章用户互关表model
 * Author: hengtingmei
 * Date Time: 2021/5/15 11:11
 */

namespace app\grow_grass\model\db;

use think\Model;

class GrowGrassFollow extends Model
{

    use \app\common\model\db\db_trait\CommonFunc;

    public function myFollowList($where, $field, $order, $page, $limit,$sqlField)
    {
        $result = $this->field($field)
            ->alias('a')
            ->leftJoin('user u', 'u.uid = a.'.$sqlField)
            ->where($where)
            ->group('a.'.$sqlField)
            ->order($order)
            ->page($page, $limit)
            ->select()
            ->toArray();
        return $result;
    }
}