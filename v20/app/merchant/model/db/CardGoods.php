<?php

namespace app\merchant\model\db;
use think\Model;
class CardGoods extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    public function goodsList($where,$field,$order,$pageSize)
    {
        $data = $this->alias('a')
            ->join('card_new_coupon b','a.coupon_id = b.coupon_id','LEFT')
            ->where($where)
            ->order($order)
            ->field($field)
            ->paginate($pageSize)->each(function($item, $key){
                $item['num'] = $item['type'] == 1 ? $item['num'] : $item['coupon_num'];
                $item['image'] = $item['type'] == 1 ? $item['image'] : $item['img'];
                $item['sale'] = $item['type'] == 1 ? $item['sale'] : $item['had_pull'];
                $item['type'] = $item['type'] == 1 ? '商品' : '优惠券';
                unset($item['coupon_num'],$item['img'],$item['had_pull']);
                return $item;
            });
        return $data;
    }
}