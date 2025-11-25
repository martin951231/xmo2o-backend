<?php
/**
 * 用户消息
 * User: lumin
 */

namespace app\common\model\db;

use think\Model;

class UserMsg extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getMsg($where,$field,$page=1,$limit=10){
        if(empty($where)){
           return false;
        }
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $res = $this->where($where)
                    ->field($field)
                    ->alias('a')
                    ->join($prefix.'mail m','m.id = a.mail_id')
                    ->limit(($page-1)*$limit,$limit)
                    ->order(['a.add_time'=>'DESC'])
                    ->select();
        return $res;
    }
}