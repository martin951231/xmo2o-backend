<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/8/6 16:17
 */

namespace app\community\model\db;

use think\Model;
class PrivilegePackage extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    public function getAddId($data)
    {
        $result = $this->insertGetId($data);
        return $result;
    }
}