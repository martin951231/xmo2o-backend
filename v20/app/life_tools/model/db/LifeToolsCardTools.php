<?php


namespace app\life_tools\model\db;

use think\Model;

class LifeToolsCardTools extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取可以核销指定次卡的店员信息
     */
    public function getStaffByCardId($where,$field)
    {
        $data = $this->alias('a')
            ->join('life_tools_ticket b','a.tools_id = b.tools_id')
            ->where($where)
            ->field($field)
            ->select();
        return $data;
    }
}