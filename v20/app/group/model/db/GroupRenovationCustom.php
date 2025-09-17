<?php

/**
 * 团购自定义配置店铺活动推荐
 * Created by PhpStorm.
 * Author: 钱大双
 * Date Time: 2021-1-15 10:21:35
 */

namespace app\group\model\db;

use think\Model;

class GroupRenovationCustom extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function del($where)
    {
        $result = $this->where($where)->delete();
        return $result;
    }
}