<?php
/**
 * 购买店铺记录表
 * 2021/08/31
 */
namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingStore extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @param $field
     * @return float
     * 求和计算
     */
    public function getSum($where,$field){
        $sum=$this->where($where)->sum($field);
        return $sum;
    }

    /**
     * @param $where
     * @param $field
     * @return mixed
     * 字段加1
     */
    public function setInc($where = [], $field = '', $num = 1) {
        $result = $this->where($where)->inc($field, $num)->update();
        return $result;
    }
}