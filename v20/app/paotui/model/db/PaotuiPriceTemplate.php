<?php

namespace app\paotui\model\db;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 跑腿价格模板
 *
 * @author: zt
 * @date: 2025/07/07
 */
class PaotuiPriceTemplate extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    use SoftDelete;

    protected $deleteTime = 'delete_time';


    //计价类型名称
    public function getTypeTextAttr($value, $data)
    {
        $type = [
            1 => '里程计价（1公里）',
            2 => '里程计价（自定义）',
        ];
        return $type[$data['type']];
    }

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
            1 => '启用',
        ];
        return $type[$data['status']];
    }
}
