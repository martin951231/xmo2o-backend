<?php
/**
 * 消息提醒接收人设置
 */

namespace app\common\model\db;
use think\Model;
class WarnUser extends Model {
    /**
     * 查询消息提醒人员
     */
    public function getUsersData($where,$field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $data = $this->alias('a')
            ->join($prefix.'user b','a.phone = b.phone','left')
            ->where($where)
            ->field($field)
            ->select()
            ->toArray();
        return $data;
    }
}