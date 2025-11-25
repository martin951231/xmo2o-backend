<?php


namespace app\pay\model\db;


use think\Model;

class MallbookRequestLog extends Model
{
    protected $json = ['request_params', 'request_response'];
    protected $jsonAssoc = true;

}