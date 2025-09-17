<?php
/**
 * 站内信
 * User: lumin
 */

namespace app\common\model\db;

use think\Model;

class Mail extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 删除后台站内信
     */
    public function delData($where){
        return $this->where($where)->delete();
    }
}