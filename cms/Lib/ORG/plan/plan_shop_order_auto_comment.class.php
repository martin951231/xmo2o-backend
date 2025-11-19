<?php
/**
 * 外卖订单到期自动评论
 */
class plan_shop_order_auto_comment extends plan_base
{
    public function runTask()
    {
        $commentTime = intval(C('config.shop_order_add_comment_time'));
        if(empty($commentTime)){//要求不设置就不执行
            return false;
        }
        $shopOrder = D('Shop_order');
        $timeout = time() - ($commentTime ?: 1) * 3600;
        
        $where['_string'] = "order_status IN (4, 5, 6) AND last_time < {$timeout} AND status = 2";
        $data = $shopOrder->where($where)
            ->limit(0,10)
            ->field('order_id')
            ->select();
        if(empty($data)){
            return false;
        }
        fdump_sql([
            'data'=>$data,
            'sql'=>$shopOrder->getLastSql()
        ],"ShopOrderAddCommentService");
        
        foreach ($data as $key => $value) {
            $orderId = $value['order_id'];
            try{
                $shopOrder->shopOrderAutoComment($orderId);
            } catch (\Exception $e) {
                $remark = [
                    'error_msg' => $e->getMessage(),
                    'trace'     => $e->getTraceAsString(),
                    'order_id'  => $orderId,
                    'msg'       => '外卖订单自动评价'
                ];
                fdump_sql($remark,"shopOrderAutoComment");
                fdump_api($remark,"shop_order/shopOrderAutoComment",1);
            }
        }
        return true;
    }
}

?>