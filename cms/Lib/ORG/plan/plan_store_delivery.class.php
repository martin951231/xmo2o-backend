<?php

/**
 * 0元起送，免配送费更新
 */
class plan_store_delivery extends plan_base
{
    public function runTask()
    {
        $storeMod = D('Merchant_store');
        $storeList = $storeMod->alias('ms')->join(C('DB_PREFIX') . 'merchant_store_shop AS s ON ms.store_id = s.store_id')
            ->field('ms.status,ms.store_id,s.deliver_type')
            ->where(['ms.status' => ['IN', [0, 1]]])
            ->select();
        $db = M();
        $tm = time();
        foreach ($storeList as $value) {
			$this->keepThread();
			
            $sql = sprintf("SELECT `coupon_id` FROM " . C('DB_PREFIX') . "card_new_coupon WHERE `status`=%d AND `start_time` <= %d AND `end_time` >= %d AND `cate_name` IN ('shop','all') AND FIND_IN_SET(%d,`store_id`) LIMIT 1", 1, $tm, $tm, $value['store_id']);
            $coupon = $db->query($sql);

            //自提和快递配送不参与
            if (in_array($value['deliver_type'], [2, 5])) {
                $update = [
                    'free_delivery' => 0,
                    'zero_delivery' => 0,
                    'jdlq' => $coupon ? 1 : 0
                ];
            } else {
                $priceList = $storeMod->getStoreBasePrice($value['store_id']);
                $update = [
                    'zero_up_to_send' => $priceList['delivery_price'] > 0 ? 0 : 1,
                    'free_delivery' => $priceList['delivery_money'] > 0 ? 0 : 1,
                    'jdlq' => $coupon ? 1 : 0
                ];
            }
            M('Merchant_store_shop')->where(['store_id' => $value['store_id']])->save($update);
        }
		
		return true;
    }
}

?>