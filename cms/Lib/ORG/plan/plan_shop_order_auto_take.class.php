<?php
//自动接单
class plan_shop_order_auto_take extends plan_base
{
	
	public function runTask()
	{
        return false;
        $time = time() - 1800;
        $sql = "SELECT o.* FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS s ON s.store_id=o.store_id WHERE o.paid=1 AND o.status=0 AND order_status=0 AND o.order_from<>1 AND s.is_auto_order=1 AND group_id=0 AND o.pay_time>0 AND  o.pay_time > {$time}";
        $res = D()->query($sql);
        foreach($res as $row){
            if($row['is_pick_in_store'] == 3){
                continue;
            }
            M('Shop_order')->where(['order_id' => $row['order_id']])->save(['status' => 1, 'order_status' => 1, 'last_time' => time()]);

            D("System_order")->save_order_status('shop', $row['order_id'], 1);
            
            //更新外卖已出餐数量
            if($order_static = D('Store_shop_order_statistics')->where(['store_id'=>$row['store_id']])->find()){
                D('Store_shop_order_statistics')->where(['store_id'=>$row['store_id']])->save(['plan_order'=>($order_static['plan_order']+1)]);
            }else{
                D('Store_shop_order_statistics')->add(['store_id'=>$row['store_id'],'plan_order'=>1,'create_date'=>time()]);
            }

            D('Shop_order_log')->add_log(array('is_auto_accept_order' => true,'order_id' => $row['order_id'], 'status' => 2, 'name' => L_('自动接单'), 'phone' => '', 'note' => L_('店铺设置下单后自动接单')));

            //接单后打印标签
            $orderDetail = D('Shop_order')->get_order_detail(array('order_id' => $row['order_id']), true);
            D('Label_printer')->shopOrderLabel($orderDetail);
            fdump_sql($row, 'shop_order_auto_take');
        }
        return true;
    }
}