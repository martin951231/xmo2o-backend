<?php

/**
 * @Author: jjc
 * @Date:   2020-06-16 13:49:01
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-16 14:12:59
 */
namespace app\mall\model\service;

use app\mall\model\db\MallCategorySpecVal as  MallCategorySpecValModel;

class MallCategorySpecValService{

	public $MallCategorySpecValModel = null;

    public function __construct()
    {
        $this->MallCategorySpecValModel = new MallCategorySpecValModel();
    }

    public function getAll(){
    	$list = $this->MallCategorySpecValModel->getNormalList();
    	return dealTree($list,'cat_spec_id');
    }

    //包含当前分类下的所有属性和属性值
    public function getSpecVal($where){
        return $list = $this->MallCategorySpecValModel->getSpecVal($where);
        //return dealTree($list,'cat_spec_id');
    }

    /**
     * auth 朱梦群
     * 根据cate_spec_id 删除属性值
     * @param $where_spec_val
     * @return bool
     */
    public function delSpecVals($where_spec_val)
    {
        $res = $this->MallCategorySpecValModel->delSpecVals($where_spec_val);
        return $res;
    }

    /**
     * auth 朱梦群
     * 根据cate_spec_id 添加属性值
     * @param $data
     * @return bool
     */
    public function addSpecVals($data)
    {
        $res = $this->MallCategorySpecValModel->addSpecVals($data);
        return $res;
    }

    /**
     * @param $where_spec_val
     * @return array
     * auth zhumengqun
     * 根据条件获取属性值
     */
    public function getSpecVals($where_spec_val)
    {
        $arr = $this->MallCategorySpecValModel->getSpecVals($where_spec_val);
        return $arr;
    }
}
