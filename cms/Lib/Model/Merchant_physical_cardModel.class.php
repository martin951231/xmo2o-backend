<?php


class Merchant_physical_cardModel extends Model
{
    /**
     * 实体卡消费记录
     * @param $money
     * @param $card_id
     * @param $type
     * @param $mer_id
     * @param $order_id
     * @param int $store_id
     * @param int $uid
     */
    public function addPhysicalCardOrder($money,$card_id,$type,$mer_id,$order_id,$store_id=0,$uid=0){
        //获取商家实体卡
        $card_info = D('Merchant_physical_card')->where(['id'=>$card_id])->find();
        $use_money = $money;
        if(($card_info['use_money']+$money)>$card_info['money']){
            $use_money = $card_info['money'];
        }
        $saveData = [
            'card_id' => $card_id,
            'mer_id' => $mer_id,
            'type' => $type,
            'money' => $use_money,
            'add_time' => time()
        ];
        $uid = $uid?:$card_info['uid'];
        if($uid){
            $saveData['uid'] = $uid;
        }
        if($store_id){
            $saveData['store_id'] = $store_id;
        }
        if($order_id){
            $saveData['order_id'] = $order_id;
        }
        if($res = D('Merchant_physical_card')->where(['id'=>$card_id])->setInc('use_money',$use_money)){
            if($type=='merchant_card'&&$uid){
                D('Merchant_physical_card')->where(['id'=>$card_id])->save(['uid'=>$uid,'exchange_time'=>time()]);
            }
            D('Merchant_physical_card_order')->add($saveData);
        }
    }
}