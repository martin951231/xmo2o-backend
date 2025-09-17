<?php

/**
 * @Author: jjc
 * @Date:   2020-06-12 14:31:25
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-12 14:44:56
 */
namespace app\mall\model\service;
use app\mall\model\db\MallStore as  MallStoreModel;

class MallStoreService{
	public $MallStoreModel = null;

    public function __construct()
    {
        $this->MallStoreModel = new MallStoreModel();
    }

    public function getOne($id){
    	$info = $this->MallStoreModel->getOne($id);
    	return $info;
    }
}
