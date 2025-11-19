<?php
/**
 * 店铺分类service
 * Author: hengtingmei
 * Date Time: 2021/05/11
 */

namespace app\merchant\model\service\store;
use app\merchant\model\db\MerchantCategory;
class MerchantCategoryService {
    public $merchantCategoryModel = null;
    public function __construct()
    {
        $this->merchantCategoryModel = new MerchantCategory();
    }

    /**
     * 获得绑定了话题的分类列表
     * @param $where array 条件
     * @return array
     */
    public function getListByTopic($param = []){
        $where = [];
        if(isset($param['keyword']) && $param['keyword']){
            $where[] = ['g.name','like', '%'.$param['keyword'].'%'];
        }
        $list = $this->merchantCategoryModel->getListByTopic($where);
        if(!$list) {
            return [];
        }

        $formatList = [];
        foreach($list as $cat){
            $formatList[] = [
                'cat_id' => $cat['cat_id'],
                'cat_name' => $cat['cat_name'],
            ];
        }
        return ['list' => $formatList];
    }

    /**
     * 根据条件获取其数量
     * @param $where array $where
     * @return array
     */
    public function getCount($where) {
        if(empty($where)){
            return false;
        }

        $count = $this->merchantCategoryModel->getCount($where);
        if(!$count) {
            return 0;
        }

        return $count;
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $detail = $this->merchantCategoryModel->getOne($where);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 根据条件返回多条数据
     * @param $where array 条件
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $detail = $this->merchantCategoryModel->getSome($where, $field, $order, $page, $limit);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 添加数据
     * @param $where array 条件
     * @return array
     */
    public function add($data) {
        if( empty($data)){
            return false;
        }

        $result = $this->merchantCategoryModel->save($data);
        if(!$result) {
            return false;
        }

        return $this->merchantCategoryModel->id;
    }

}