<?php

/**
 * 团购
 * Author: 衡婷妹
 * Date Time: 2020/11/27 16:00
 */
namespace app\group\model\db;

use think\Model;
use think\facade\Db;
class GroupShareRelation extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获得一条拼团数据
    */
    public function getOneByJoin($where, $field=true){
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->where($where)
            ->field($field)
            ->leftJoin($prefix.'group_order o ','r.order_id = o.order_id')
            ->find();
        return $result;
    }
}