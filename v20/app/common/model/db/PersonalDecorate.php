<?php
/**
 * PersonalDecorate.php
 * 个人中心装修model
 * Create on 2021/2/19 16:10
 * Created by zhumengqun
 */

namespace app\common\model\db;

use think\Model;

class PersonalDecorate extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 根据条件删除
     */
    public function delOne($where)
    {
        return $this->where($where)->delete();
    }
}