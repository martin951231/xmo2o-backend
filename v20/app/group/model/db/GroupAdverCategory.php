<?php

/**
 * 新版团购广告分类
 * Author: 钱大双
 * Date Time: 2021-1-20 14:49:09
 */

namespace app\group\model\db;

use think\Model;

class GroupAdverCategory extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function addData($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }
}