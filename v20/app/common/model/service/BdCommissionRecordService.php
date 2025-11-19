<?php


namespace app\common\model\service;

use app\common\model\db\Bd;
use app\common\model\db\BdCommissionRecord;
use app\mall\model\db\Merchant;

class BdCommissionRecordService
{
    /**
     * 增加业务员佣金
     * @param $money
     * @param $mer_id
     */
    public function addCommission($money, $mer_id, $store_id = 0, $order_type, $order_id){
        if($money > 0 && cfg('open_bd_spread') == '1'){
            $now_merchant =(new Merchant())->getOne(['mer_id' => $mer_id],'*');
            fdump_sql(['money'=>$money,'now_merchant'=>$now_merchant], 'addCommission');
            if($now_merchant && $now_merchant['bd_id'] > 0){
                $bd =(new Bd())->getOne(['bd_id'=>$now_merchant['bd_id']]);
                if(!empty($bd)){
                    $bd=$bd->toArray();
                }else{
                    return true;
                }

                if($bd['type'] == 1 && $bd['pbd_id'] > 0){
                    $pbd = (new Bd())->getOne(['bd_id' => $bd['pbd_id']]);
                    if(!empty($pbd)){
                        $pbd=$pbd->toArray();
                    }else{
                        return true;
                    }
                }
                if($bd && $bd['commission_rate'] > 0){
                    //为业务员添加分佣
                    $commission = round($money*$bd['commission_rate']/100,2);
                    $data = [
                        'bd_id' => $now_merchant['bd_id'],
                        'mer_id' => $mer_id,
                        'store_id' => $store_id,
                        'order_type' => $order_type,
                        'order_id' => $order_id,
                        'commission' => $commission,
                        'addtime' => date('Y-m-d H:i:s'),
                        'is_del' => 0
                    ];
                    (new BdCommissionRecord())->add($data);
                    (new Bd())->setInc(['bd_id'=>$now_merchant['bd_id']],'all_money',$commission);
                    (new Bd())->setInc(['bd_id'=>$now_merchant['bd_id']],'my_all_money',$commission);
                    (new UserService())->addMoney($bd['uid'],$commission,'推广的商家'.$now_merchant['name'].'发生消费获得分佣！');
                    fdump_sql(['bd'=>$bd,'data'=>$data], 'addCommission');
                }

                if(isset($pbd) && $pbd['son_commission_rate'] > 0){
                    //为业务经理添加分佣
                    $commission = round($money*$pbd['son_commission_rate']/100,2);
                    $data = [
                        'bd_id' => $bd['pbd_id'],
                        'mer_id' => $mer_id,
                        'store_id' => $store_id,
                        'order_type' => $order_type,
                        'order_id' => $order_id,
                        'commission' => $commission,
                        'addtime' => date('Y-m-d H:i:s'),
                        'is_del' => 0,
                        'type' => 1
                    ];
                    (new BdCommissionRecord())->add($data);
                    (new Bd())->setInc(['bd_id'=>$bd['pbd_id']],'all_money',$commission);
                    (new Bd())->setInc(['bd_id'=>$bd['pbd_id']],'son_all_money',$commission);
                    (new UserService())->addMoney($pbd['uid'],$commission,'下级'.$bd['bd_name'].'推广的商家'.$now_merchant['name'].'发生消费获得分佣！');
                    fdump_sql(['pbd'=>$pbd,'data'=>$data], 'addCommission');
                }
            }
        }
        return true;

    }
}