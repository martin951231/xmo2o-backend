<?php
/**
 * 活动预约座位购买详情model
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsAppointSeatDetail extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'id';

    /**
     * 获取核销详情
     */
    public function getVerify($order_id)
    {
        $field = [];
        $field[] = 'COUNT(code) AS total_num'; //总数
        $field[] = 'SUM(CASE status WHEN 0 THEN 1 ELSE 0 END) AS can_verify'; //可核销数
        $field[] = 'GROUP_CONCAT(seat_num) AS seat_num'; //座位号
        $field[] = 'GROUP_CONCAT(seat_title) AS seat_title'; //座位名称
        $field[] = 'code'; 
        $field[] = 'MIN(status) AS status';
        $res = $this->field($field)
                    ->where('order_id', $order_id)
                    ->group('code')
                    ->select();
        return $res;
    }
}