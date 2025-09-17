<?php    
/*
 * 批量同步有赞云商品 加入子计划任务
 * @author hengtingmei
 * @date 2021/10/14
*/
class plan_youzanyun_synchronous_goods extends plan_base{
	public function runTask(){
        $storeId = 41;// 雅思到家商品数据自动更新
        $now_store = M('Merchant_store_shop')->where(['store_id'=>41])->find();

        import('@.ORG.youzanyun.SynchronousGoods');

        if(!$now_store['youzanyun_client_id'] || !$now_store['youzanyun_client_secret'] || !$now_store['youzanyun_grant_id']){
            return true;
        }

        $synchronousGoods = new SynchronousGoods($now_store['youzanyun_client_id'], $now_store['youzanyun_client_secret'], $now_store['youzanyun_grant_id'], $now_store['youzanyun_store_id']);

        $return = $synchronousGoods->synchronousGoodsSort($storeId);
		return true;
	}

	


}
?>