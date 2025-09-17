<?php
/**
 * 活动预约座位model
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsAppointSeat extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据appoint_id获取座位信息
     * @param $appoint_id 预约id
     * @param $is_select 是否查询已售，0=不查询已售
     */
    public function getDataByAppointId($appoint_id, $is_select = 0)
    {
        $res = $this->where('appoint_id', $appoint_id)->select()->toArray();
        if($is_select){
            $soldArr = (new LifeToolsAppointSeatDetail)->where('appoint_id', $appoint_id)->where('status', 'in', [0, 1])->column('seat_num');
            foreach($res as $k => $v){
                if(in_array($v['seat_num'], $soldArr)){
                    $res[$k]['is_select'] = 1;
                }else{
                    $res[$k]['is_select'] = 0;
                }
            }
        }
        return $res;
    }
}