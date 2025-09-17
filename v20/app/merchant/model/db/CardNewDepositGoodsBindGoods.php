<?php


namespace app\merchant\model\db;


use think\Model;

class CardNewDepositGoodsBindGoods extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @return bool
     * @throws \Exception
     * 删除
     */
    public function delData($where){
        return $this->where($where)->delete();
    }
}