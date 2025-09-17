<?php


namespace app\mall\model\service;


use app\mall\model\db\MallGoodsWords;

class MallGoodsWordsService
{
    public $MallGoodsWords=null;
    public function __construct()
    {
        $this->MallGoodsWords = new MallGoodsWords();
    }

    public function getGoodsWordType($goods_id){
        $where[]=[
            "goods_id","=",$goods_id
        ];
        return $this->MallGoodsWords->getGoodsWordTypeList($where);
    }
}