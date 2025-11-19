<?php

/**
 * 体育限时秒杀门票提醒
 */
namespace app\life_tools\model\db;
use think\Model;

class LifeToolsSportsSecondsKillNotice extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * @param $where
     * @param string $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 详情
     */
    public function getDetail($where, $field = '*')
    {
        $arr = $this->field($field)->where($where)->find();
        if (!empty($arr)) {
            $arr = $arr->toArray();
        } else {
            $arr = [];
        }
        return $arr;
    }
}