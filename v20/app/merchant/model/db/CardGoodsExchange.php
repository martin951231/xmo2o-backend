<?php

namespace app\merchant\model\db;
use think\Model;
class CardGoodsExchange extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    public function goodsExchangeList($where,$whereOr,$field,$order,$pageSize)
    {
        $data = $this->alias('a')
            ->join('card_goods b','a.goods_id = b.id')
            ->join('card_new_coupon c','b.coupon_id = c.coupon_id','LEFT')
            ->join('user d','a.uid = d.uid','LEFT')
            ->join('card_new_coupon_hadpull e','a.coupon_record_id = e.id','LEFT');
        if($whereOr){
            $data = $data->whereOr([$where,$whereOr]);
        }else{
            $data = $data->where($where);
        }
            $data = $data->field($field)
            ->order($order)
            ->paginate($pageSize);
        return $data;
    }

    public function exchangeDetail($where,$field)
    {
        $data = $this->alias('a')
            ->join('card_goods b','a.goods_id = b.id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }
}