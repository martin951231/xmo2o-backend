<?php
/**
 * 小区巡检次数汇总表
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;
class WisdomQrcodeStatistics extends Model{
    public function getOne($where,$field)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }
    
    public function addOne($data)
    {
        $add = $this->insert($data);
        return $add;
    }
    
    public function addNum($where,$field,$num)
    {
        $update = $this->where($where)->inc($field,$num)->update();
        return $update;
    }
    
    public function updateRecord($where,$data)
    {
        $update = $this->where($where)->update($data);
        return $update;
    }
    
    public function getPatrolInspectionRateList($field,$order,$group,$limit)
    {
        $list = $this
            ->field($field)
            ->order($order)
            ->group($group)
            ->paginate($limit)
            ->toArray();
        return $list;
    }
}