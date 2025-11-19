<?php
/**
 * 商家寄存商品类型model
 * Author: fenglei
 * Date Time: 2021/11/04 11:06
 */

namespace app\merchant\model\db;
use think\Model;
class CardNewDepositGoodsSort extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @param $field
     * @return mixed
     * 获取某个字段值
     */
    public function getSortName($where,$field){
       return $this->where($where)->value($field);
    }
}