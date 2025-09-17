<?php


namespace app\mall\model\service;

use app\mall\model\db\MallSearchFind as  MallSearchFindModel;
class MallSearchFindService
{
    public $MallSearchFindModel = null;

    public function __construct()
    {
        $this->MallSearchFindModel = new MallSearchFindModel();
    }

    public function getContent(){
        return $list = $this->MallSearchFindModel->getContent();
    }
}