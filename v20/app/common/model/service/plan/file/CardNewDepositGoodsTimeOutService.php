<?php


namespace app\common\model\service\plan\file;


use app\merchant\model\db\CardNewDepositGoods;
use app\merchant\model\db\CardNewDepositGoodsBindUser;

class CardNewDepositGoodsTimeOutService
{
    /**
     * 计划任务执行--寄存过期库存回滚
     * @param  string  $param 参数
     */

    public function runTask()
    {
        $where=[['is_del','=',0],['is_back_stock','=',0],['is_back_stock','=',0],['end_time','<',time()]];
        $list=(new CardNewDepositGoodsBindUser())->getSome($where)->toArray();
        if(!empty($list)){
            foreach ($list as $k=>$v){
                if(($v['num']-$v['use_num'])>0){
                    (new CardNewDepositGoods())->setInc(['goods_id'=>$v['goods_id']],'stock_num',($v['num']-$v['use_num']));
                }
                (new CardNewDepositGoodsBindUser())->updateThis(['id'=>$v['id']],['is_back_stock'=>1]);
            }
        }
        return true;
    }
}