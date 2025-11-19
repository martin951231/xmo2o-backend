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
class PaotuiAddedFee extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    use SoftDelete;

    protected $deleteTime = 'delete_time';



    //绑定业务名称
    public function getBindBusinessTextAttr($value, $data)
    {
        $type = [
            1 => '帮我买',
            2 => '帮我送',
            3 => '帮我送（商家）',
        ];
        return $type[$data['bind_business']];
    }

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
