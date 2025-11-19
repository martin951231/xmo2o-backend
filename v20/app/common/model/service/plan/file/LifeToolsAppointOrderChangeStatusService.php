<?php
/**
 * 通用预约改变订单状态
 */

namespace app\common\model\service\plan\file;

use app\life_tools\model\db\LifeToolsAppointJoinOrder;
use app\common\model\db\SystemOrder;

class LifeToolsAppointOrderChangeStatusService
{

    public function runTask()
    {
        $lifeToolsAppointJoinOrderModel = new LifeToolsAppointJoinOrder();
        $SystemOrder = new SystemOrder();

        $time = time();

        //活动进行中
        $condition = [];
        $condition[] = ['a.start_time', '<=', $time];
        $condition[] = ['a.end_time', '>', $time];
        $condition[] = ['o.paid', '=', 1];
        $condition[] = ['so.system_status', '<>', 1];
        $data = $lifeToolsAppointJoinOrderModel->orderList($condition, 'so.id');
        if($data){
            $systemOrderIds = array_column($data, 'id');
            if(count($systemOrderIds)){
                $SystemOrder->where('id', 'in', $systemOrderIds)->update([
                    'system_status' => 1,
                    'last_time' =>  $time
                ]);
            }
            
        }

        //活动结束
        $condition = [];
        $condition[] = ['a.end_time', '<=', $time];
        $condition[] = ['o.paid', '=', 1];
        $condition[] = ['so.system_status', '<>', 2];
        $data = $lifeToolsAppointJoinOrderModel->orderList($condition, 'so.id');
        if($data){
            $systemOrderIds = array_column($data, 'id');
            if(count($systemOrderIds)){
                $SystemOrder->where('id', 'in', $systemOrderIds)->update([
                    'system_status' => 2,
                    'last_time' =>  $time
                ]);
            }
        }
     
        return true;
    }

}