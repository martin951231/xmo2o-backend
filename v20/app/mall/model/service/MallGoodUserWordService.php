<?php


namespace app\mall\model\service;


use app\mall\model\db\MallGoodUserWord;

class MallGoodUserWordService
{
    public $MallGoodUserWord = null;
     public function __construct()
     {
         return $this->MallGoodUserWord=new MallGoodUserWord();
     }

     //查询是用户相关商品购买凭证列表
     public function getWordList($uid,$goods_id){
          $where[]=["s.uid","=",$uid];
          $where[]=["s.goods_id","=",$goods_id];
          $where[]=["m.is_del","=",0];
         return $this->MallGoodUserWord->getGoodsUserWordList($where);
     }
}