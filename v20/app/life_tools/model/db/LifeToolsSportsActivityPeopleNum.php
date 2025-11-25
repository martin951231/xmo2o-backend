<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsSportsActivityPeopleNum extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取字段值
     */
    public function getColumn($where,$field){
        $label = $this->where($where)
            ->column($field);
        return $label;
    }
}