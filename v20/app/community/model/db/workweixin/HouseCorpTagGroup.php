<?php
/**
 * 企业微信标签组信息
 */
namespace app\community\model\db\workweixin;

use think\Model;

class HouseCorpTagGroup extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    
    public function getColumn($where,$field, string $key = '')
    {
        $info = $this->where($where)->column($field, $key);
        return $info;
    }
}