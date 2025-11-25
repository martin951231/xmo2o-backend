<?php


namespace app\mall\model\service;


use app\mall\model\db\MerchantUserRelation;

class MerchantUserRelationService
{
    /**
     * @param $goods_id
     * @param $uid
     * @param $store_id
     * @return mixed
     * 商品详情收藏查询
     */
  public function getMsg($goods_id,$uid,$store_id){
      $msg=(new MerchantUserRelation())->getOne($goods_id,$uid,$store_id);
      if(!empty($msg)){
          $return['is_col']=1;//已收藏
      }else{
          $return['is_col']=0;//未收藏
      }
      return $return;
  }
}