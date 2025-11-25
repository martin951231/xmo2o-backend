<?php


namespace app\mall\model\db;

use think\Model;
class MallGoodUserWord extends Model
{
   public function getGoodsUserWordList($where){
       $prefix = config('database.connections.mysql.prefix');
           $field='m.mustid,m.word_name,m.word_name,m.word_type,s.word_id,s.goods_id,s.infos,s.img_addr';
           $result = $this ->alias('s')
               ->join($prefix.'mall_goods_words'.' m','s.goods_id = m.goods_id and s.word_id = m.id')
               ->where($where)
               ->field($field)
               ->select()->toArray();
           //var_dump($this->getLastSql());
       return $result;
   }
}