<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeScenicActivity extends Model
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