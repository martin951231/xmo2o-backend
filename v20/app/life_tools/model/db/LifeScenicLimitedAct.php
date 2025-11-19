<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeScenicLimitedAct extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @return array
     * 查询活动基本信息
     */
    public function getInfo($where,$fields='*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->join($prefix.'life_scenic_activity'.' m','s.id = m.act_id')
            ->field($fields)
            ->where($where)->find();
        if(!empty($result)){
            return $result->toArray();
        }else{
            return [];
        }
    }
}