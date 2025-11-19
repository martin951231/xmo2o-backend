<?php

namespace app\paotui\model\db;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 跑腿增值费用
 *
 * @author: zt
 * @date: 2025/07/07
 */
class PaotuiGrabPool extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    //状态名称
    public function getStatusTextAttr($value, $data)
    {
        $type = [
            0 => '关闭',
            1 => '开启',
        ];
        return $type[$data['status']];
    }
}
