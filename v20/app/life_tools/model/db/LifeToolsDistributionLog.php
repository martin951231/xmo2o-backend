<?php
/**
 * 三级分销-业务员下单记录
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsDistributionLog extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'pigcms_id';

    /**
     * 获取订单数量
     * @param $type 查询类型，1：订单数量（已确认），2=订单金额, 3=订单数（总）
     */
    public function getOrderDataByUserId($user_id, $type = 1)
    {
        switch ($type) {
            case 1:
                $condition = [];
                $condition[] = ['status', '=', 2];
                $condition[] = ['user_id', '=', $user_id];
                return $this->where($condition)->count();
                break;
            
            case 2:
                $condition = [];
                $condition[] = ['status', '=', 2];
                $condition[] = ['user_id', '=', $user_id];
                $data = $this->alias('l')
                        ->field(['SUM(o.total_price) total_price'])
                        ->join('life_tools_order o', 'l.order_id = o.order_id')
                        ->where($condition)
                        ->find();
                return $data['total_price'] ?: 0;
                break;
            case 3:
                $condition = [];
                $condition[] = ['user_id', '=', $user_id];
                return $this->where($condition)->count();
                break;
        } 
    }
}