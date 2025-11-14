<?php

namespace app\paotui\model\db;

use think\Model;

/**
 * 骑手跑腿佣金设置
 *
 * @author: zt
 * @date: 2025/07/07
 */
class PaotuiRiderCommissionSetting extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    // 设置json类型字段
    protected $json = ['dynamics_commission_ladder', 'fixed_commission_distance_ladder', 'fixed_commission_weight_ladder'];

    // 设置JSON数据返回数组
    protected $jsonAssoc = true;
}
