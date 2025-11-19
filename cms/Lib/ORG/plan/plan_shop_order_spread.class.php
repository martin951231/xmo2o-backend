<?php
/**
 * author htm
 * description 平台佣金获得按照实际7天后的剩余当前未退款的实际支付金额来算获得佣金
 * data 20191219 17:14
*/
class plan_shop_order_spread extends plan_base{
	public function runTask(){
        $time = time() - 86400*7;
        $limit = 200;
        $start = 0;

        $sql = 'select * from '.C('DB_PREFIX').'shop_order where ((use_time>0 AND use_time<'.$time.') OR (last_time>0 AND last_time<'.$time.')) AND status in(2,3) AND paid=1 AND is_spread=0 limit '.$start.','.$limit;

        $list = M()->query($sql);
        while($list) {
            $this->keepThread();
            foreach ($list as $key => $value) {
                $this->keepThread();
                // 获得三级分佣
                D('Shop_order')->spreadSend($value);
            }
            $start++;
            $sql = 'select * from '.C('DB_PREFIX').'shop_order where ((use_time>0 AND use_time<'.$time.') OR (last_time>0 AND last_time<'.$time.')) AND status in(2,3) AND paid=1 AND is_spread=0 limit '.$start.','.$limit;
            $list = M()->query($sql);

        }
        
		return true;
	}
}
?>