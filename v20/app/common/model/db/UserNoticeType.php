<?php
/**
 * 用户订单消息类型
 */

namespace app\common\model\db;

use think\Model;

class UserNoticeType extends Model
{
	use \app\common\model\db\db_trait\CommonFunc;

	public function getList($where,$field,$pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('a')
            ->join($prefix.'merchant_store b','a.store_id = b.store_id','left')
            ->where($where)
            ->field($field)
            ->order('a.is_top desc,a.new_time desc')
            ->paginate($pageSize)
            ->toArray();
        return $result;
    }
}