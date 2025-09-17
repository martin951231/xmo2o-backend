<?php
// 推广分佣列表
class User_spread_listModel extends Model{
    /**
     * 订单退款收回佣金
     * @param $orderType
     * @param $orderId
     * @return false
     */
    public function cancelUserSpreadCommission($orderType, $orderId)
    {
        $spreadList = $this->where(['order_id' => $orderId, 'type' => $orderType, 'status' => 1])->select();
        if(empty($spreadList)){
            return false;
        }
        foreach ($spreadList as $v){
            $result = M('User')->where(['uid' => $v['spread_uid']])->setDec('now_money', $v['money']);
            if($result){
                D('User_money_list')->add_row(
                    $v['spread_uid'],
                    2, 
                    $v['money'],
                    "订单编号{$v['real_orderid']}订单退货,佣金收回。",
                    true,
                    D('UserSpreadMer')->userMoneyAsk($v['type']),
                    $v['order_id']
                );
            }
        }
        return true;
    }
}
