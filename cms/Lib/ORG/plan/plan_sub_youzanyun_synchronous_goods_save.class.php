<?php
/*
 * 批量同步有赞云商品 保存商品
 * @author hengtingmei
 * @date 2021/08/18
*/
class plan_sub_youzanyun_synchronous_goods_save extends plan_base{
	public function runTask($param=null){
        if(empty($param)){
            return true;
        }

        $id = $param['id'];// 有赞云商品分组id
        $goodsId = $param['goods_id'];// 有赞云商品d

        if( empty($id) || empty($goodsId)){
            return true;
        }

        // 获得分类信息
        $sortDetail = M('Shop_goods_sort')->where(['youzanyun_sort_id'=>$id])->find();
        if(!$sortDetail){
            return true;
        }

        // 店铺和有赞云配置
        $now_store = M('Merchant_store_shop')->where(['store_id' => $sortDetail['store_id']])->find();
        if(!$now_store || empty($now_store['youzanyun_client_id']) || empty($now_store['youzanyun_client_secret']) || empty($now_store['youzanyun_grant_id'])){
            return true;
        }

        // 同步商品
        import('@.ORG.youzanyun.SynchronousGoods');
        $synchronousGoods = new SynchronousGoods($now_store['youzanyun_client_id'], $now_store['youzanyun_client_secret'], $now_store['youzanyun_grant_id'], $now_store['youzanyun_store_id']);

        $synchronousGoods->synchronousGoodsSave($id, $goodsId);
		return true;
	}


}
?>