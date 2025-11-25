<?php
/**
 * 团购频道页子分类页热搜词model
 * Author: 钱大双
 * Date Time: 2021年1月25日13:48:46
 */

namespace app\group\model\db;

use think\Model;

class GroupSearchHot extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;


    public function del($where)
    {
        $result = $this->where($where)->delete();
        return $result;
    }
}