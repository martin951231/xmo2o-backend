<?php
/**
 * @desc Created by PhpStorm.
 * @author: lumin
 * since: 2020/8/4 1:56 下午
 */
class BdModel extends Model{
    /**
     * 增加业务员佣金
     * @param $money
     * @param $mer_id
     * @param $is_mer 是否商家推广用户消费业务员获得佣金
     */
    public function addCommission($money, $mer_id, $store_id = 0, $order_type, $order_id, $is_mer = false){
        // 查询微信分账
        $where = [
            'order_type' =>  $order_type,
            'order_id' =>  $order_id,
            'is_withdraw' =>  ['neq', 2],
        ];
        $profit_sharing = M('Weixin_profit_sharing_record')->where($where)->find();

        if($money > 0 && C('config.open_bd_spread') == '1'){
            $now_merchant = M("Merchant")->where(['mer_id' => $mer_id])->find();
            fdump_sql(['money'=>$money,'now_merchant'=>$now_merchant], 'addCommission');
            if($now_merchant && $now_merchant['bd_id'] > 0){
                $bd = $this->where(['bd_id'=>$now_merchant['bd_id']])->find();
                if($bd['type'] == 1 && $bd['pbd_id'] > 0){
                    $pbd = $this->where(['bd_id' => $bd['pbd_id']])->find();
                }

                $commission_rate = $bd['commission_rate'];// 佣金比例
                if($is_mer){
                    $commission_rate = $bd['merchant_commission_rate'];// 商家推广用户佣金比例
                }

                if($bd && $commission_rate > 0){
                    //为业务员添加分佣
                    $commission = round($money*$commission_rate/100,2);
                    $data = [
                        'bd_id' => $now_merchant['bd_id'],
                        'mer_id' => $mer_id,
                        'store_id' => $store_id,
                        'order_type' => $order_type,
                        'order_id' => $order_id,
                        'commission' => $commission,
                        'addtime' => date('Y-m-d H:i:s'),
                        'is_mer' => $is_mer ? 1 : 0,
                        'is_del' => 0,
                    ];
                    M('Bd_commission_record')->data($data)->add();
                    $this->where(['bd_id'=>$now_merchant['bd_id']])->setInc('all_money', $commission);
                    $this->where(['bd_id'=>$now_merchant['bd_id']])->setInc('my_all_money', $commission);
                    
                    D('User')->add_money($bd['uid'],$commission,'推广的商家'.$now_merchant['name'].($is_mer ? '的推广用户' : '').'发生消费获得分佣！');
                }
                fdump_sql(['bd'=>$bd,'data'=>$data], 'addCommission');

                // 业务员的业务经理获得佣金
                $son_commission_rate = $pbd['son_commission_rate'];// 佣金比例
                if($is_mer){
                    $son_commission_rate = $pbd['son_merchant_commission_rate'];// 商家推广用户佣金比例
                }
                if($pbd && $son_commission_rate > 0){
                    //为业务经理添加分佣
                    $commission = round($money*$son_commission_rate/100,2);
                    $data = [
                        'bd_id' => $bd['pbd_id'],
                        'mer_id' => $mer_id,
                        'store_id' => $store_id,
                        'order_type' => $order_type,
                        'order_id' => $order_id,
                        'commission' => $commission,
                        'addtime' => date('Y-m-d H:i:s'),
                        'is_mer' => $is_mer ? 1 : 0,
                        'is_del' => 0,
                        'type' => 1,
                    ];
                    M('Bd_commission_record')->data($data)->add();

                    $this->where(['bd_id'=>$bd['pbd_id']])->setInc('all_money', $commission);
                    $this->where(['bd_id'=>$bd['pbd_id']])->setInc('son_all_money', $commission);
                    
                    D('User')->add_money($pbd['uid'],$commission,'下级'.$bd['bd_name'].'推广的商家'.$now_merchant['name'].($is_mer ? '的推广用户' : '').'发生消费获得分佣！');
                    fdump_sql(['pbd'=>$pbd,'data'=>$data], 'addCommission');
                }
            }
        }
        return true;

    }


    /**
     * 推广商家的推广用户消费，业务员也获得佣金
     * @param $money
     * @param $mer_id
     */
    public function addMerchantCommission($money, $uid, $store_id, $order_type, $order_id){
        if($money <= 0 || !C('config.open_bd_spread')){
            return true;
        }

        // 下单用户
        $nowUser = D('User')->get_user($uid);
        if(!$nowUser){
            return true;
        }

        // 查找推广关系
        $spreadUser = M('Merchant_spread')->where(array('openid'=>$nowUser['openid']))->find();
        if(!$spreadUser){
            return true;
        }

        $this->addCommission($money, $spreadUser['mer_id'], $store_id , $order_type, $order_id, true);
        return true;
    }

    public function changeUid($bd_id){
        return $this->where(['bd_id'=>$bd_id])->find();
    }
}