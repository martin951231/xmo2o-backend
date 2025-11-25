<?php

/**
 * 团购分类
 * Author: 衡婷妹
 * Date Time: 2020/11/16 16:43
 */
namespace app\group\model\db;

use think\Model;
class GroupCategory extends Model
{

    use \app\common\model\db\db_trait\CommonFunc;

    public function del($where)
    {
        $result = $this->where($where)->delete();
        return $result;
    }

}