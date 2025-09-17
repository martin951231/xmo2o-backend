<?php

/**
 * 体育限时秒杀活动主表
 */
namespace app\life_tools\model\db;
use think\Model;

class LifeToolsSportsSecondsKill extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 获取活动的id
     * @param $where
     * @param $field
     * @return array
     */
    public function getActInfo($where, $field)
    {
        $arr = $this->field($field)->where($where)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

}