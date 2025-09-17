<?php


namespace app\merchant\model\service;


use app\common\model\db\MerchantCategory;
use app\merchant\model\db\IndustryProperty;

class MerchantCategoryService
{
    public $merchantCategoryModel = null;

    public function __construct()
    {
        $this->merchantCategoryModel = new MerchantCategory();
    }


    /**
     * 获得店铺树形结构分类列表
     * @return array
     */
    public function getCategoryListTree(){
        $sortList = $this->getStoreCategoryList([], ['cat_sort'=>'DESC','cat_id'=>'DESC']);
        if(!$sortList) {
            return [];
        }

        $tmpMap = array();
        foreach ($sortList as $key => $_sort) {
            // 分类id
            $_sort['cat_id'] = $_sort['cat_id'];
            $_sort['value'] = $_sort['cat_id'];
            $_sort['key'] = $_sort['cat_fid'] ? $_sort['cat_fid'].'-'.$_sort['cat_id'] : $_sort['cat_id'];
            // 分类名陈
            $_sort['title'] = $_sort['cat_name'] = htmlspecialchars_decode($_sort['cat_name'],ENT_QUOTES);
            $tmpMap[$_sort['cat_id']] = $_sort;
        }

        $list = array();
        foreach ($sortList as $_sort) {
            if ($_sort['cat_fid'] && isset($tmpMap[$_sort['cat_fid']])) {
                $tmpMap[$_sort['cat_fid']]['children'][$_sort['cat_id']] = &$tmpMap[$_sort['cat_id']];
            } elseif(!$_sort['cat_fid']) {
                $list[$_sort['cat_id']] = &$tmpMap[$_sort['cat_id']];
            }
        }
        unset($tmpMap);
        $list = array_values($list);
        foreach ($list as &$child){
            if(isset($child['children'])){
                $child['children'] = array_values($child['children']);
                foreach ($child['children'] as &$child2){
                    if(isset($child2['children'])){
                        $child2['children'] = array_values($child2['children']);
                    }
                }
            }
        }
        return $list;
    }

    /**
     * @param $where
     * @param $order
     * @param $page
     * @param $pageSize
     * @return mixed]
     * 系统后台店铺分类页面列表
     */
    public function getStoreCategoryList($where,$order,$page=0,$pageSize=0)
    {
        $return=$this->merchantCategoryModel->getSome($where,true,$order,($page-1)*$pageSize,$pageSize)->toArray();
         return $return;
    }

    /**
     * @param $where
     * @param $order
     * @return mixed
     * 获取行业列表
     */
    public function getSome($where,$order){
        $return=(new IndustryProperty())->getSome($where,true,$order)->toArray();
        return $return;
    }
    /**
     * @param $where
     * @return mixed
     * 编辑系统后台店铺分类页面
     */
    public function editStoreCategory($where){
        $return=$this->merchantCategoryModel->getOne($where);
        if(!empty($return)){
            $return=$return->toArray();
        }
        return $return;
    }
}