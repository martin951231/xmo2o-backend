<?php

namespace app\new_marketing\model\service;

use app\new_marketing\model\db\MerchantCategory;

class MerchantCategoryService
{

    //获取店铺一二级全部分类
    public function getStoreTypeList($where) {
        $where[] = ['cat_fid', '=', 0];
        $list = (new MerchantCategory())->getStoreTypeList($where);
        if ($list) {
            foreach ($list as $k => $v) {
                $where1 = [
                    ['cat_status', '=', 1],
                    ['cat_fid', '=', $v['value']]
                ];
                $list[$k]['children'] = (new MerchantCategory())->getStoreTypeList($where1);
            }
        }
        return $list;
    }

    //根据条件获取店铺分类
    public function getStoreCatList($where) {
        $list = (new MerchantCategory())->getStoreTypeList($where);
        return $list;
    }

    public function getOneData($where) {
        $data = (new MerchantCategory())->getOneData($where);
        return $data;
    }

    //根据条件获取全部一级分类ID
    public function getStoreFcatIds($where) {
        $list = (new MerchantCategory())->getStoreFcatIds($where);
        return $list;
    }


    /**
     * 获得多条数据
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $list = (new MerchantCategory())->getSome($where,$field,$order,$page,$limit);
        if(!$list) {
            return [];
        }
        return $list->toArray();
    }

}