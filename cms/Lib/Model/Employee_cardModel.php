<?php
class Employee_cardModel extends Model
{
    /**
     * 清积分
     */
    public function remove_integral($card_id)
    {
        $card = $this->where(['card_id'=>$card_id])->find();
    }
}