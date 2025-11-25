<?php


namespace app\community\model\db;

use think\Model;

class AreaStreetWorkersOrderLog extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    public function getSome($where = [], $field = true,$order=true,$page=1,$limit=10){

        $sql = $this->field($field)->where($where)->order($order);
        if($limit)
        {
            $sql->page($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }

    /**
     * 时间数量统计
     * @author lijie
     * @date_time 2021/03/03
     * @param $where
     * @param string $group
     * @return mixed
     */
    public function getCount($where,$group='')
    {
        $count = $this->alias('l')
            ->leftJoin('area_street_workers_order o','o.order_id = l.order_id')
            ->where($where);
        if($group){
            $count = $count->group($group)->count();
        }else{
            $count = $count->count();
        }
        return $count;
    }
}