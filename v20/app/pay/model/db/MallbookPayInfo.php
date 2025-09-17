<?php


namespace app\pay\model\db;


use think\Model;

class MallbookPayInfo extends Model
{
    protected $json = ['goods_detail', 'split_list'];
    protected $jsonAssoc = true;

}