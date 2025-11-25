<?php


namespace app\merchant\model\db;


use think\Model;

class CardNewDepositGoodsMessage extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    //关联用户寄存记录表
    public function binduser()
    {
        return $this->belongsTo('CardNewDepositGoodsBindUser', 'bind_id', 'id');
    }
}