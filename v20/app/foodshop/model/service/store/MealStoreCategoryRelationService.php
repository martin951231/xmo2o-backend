<?php
/**
 * 餐饮商品分类店铺关系表service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/26 15:20
 */

namespace app\foodshop\model\service\store;
use app\foodshop\model\db\MealStoreCategoryRelation as MealStoreCategoryRelationModel;
class MealStoreCategoryRelationService {
    public $mealStoreCategoryModel = null;
    public function __construct()
    {
        $this->mealStoreCategoryRelationModel = new MealStoreCategoryRelationModel();
    }

    /**
     * 根据ID返回分类
     * @param $catId
     * @return array
     */
    public function getCategoryRelationByCatId($catId) {
        if(empty($catId)){
           return [];
        }

        $mealStoreCategoryRelation = $this->mealStoreCategoryRelationModel->getCategoryRelationByCatId($catId);
        if(!$mealStoreCategoryRelation) {
            return [];
        }
        
        return $mealStoreCategoryRelation->toArray(); 
    }

    
    /**
     * 根据父ID返回分类列表
     * @param $catFid
     * @return array
     */
    public function getCategoryRelationByCatFid($catFid) {
        if(empty($catFid)){
           return [];
        }

        $mealStoreCategoryRelationList = $this->mealStoreCategoryRelationModel->getCategoryRelationByCatFid($catFid);
        if(!$mealStoreCategoryRelationList) {
            return [];
        }
        
        return $mealStoreCategoryRelationList->toArray(); 
    }

    /**
     * 根据店铺ID返回分类列表
     * @param $storeId
     * @return array
     */
    public function getCategoryRelationByStoreId($storeId) {
        if(empty($storeId)){
           return [];
        }

        $mealStoreCategoryRelationList = $this->mealStoreCategoryRelationModel->getCategoryRelationByStoreId($storeId);
        if(!$mealStoreCategoryRelationList) {
            return [];
        }
        
        return $mealStoreCategoryRelationList->toArray(); 
    }

    /**
     * 根据店铺id更新分类
     * @param $storeId  店铺ID
     * @param $cateArr  分类id, 数组格式[catFid_catId]
     * @author 张涛
     * @date 2020/07/09
     */
    public function updateRelationByStoreId($storeId, $cateArr)
    {
        $this->mealStoreCategoryRelationModel->where('store_id', $storeId)->delete();
        $new = [];
        foreach ($cateArr as $v) {
            $split = explode('_', $v);
            $new[] = [
                'store_id' => $storeId,
                'cat_fid' => $split[0],
                'cat_id' => $split[1]

            ];
        }
        $new &&  $this->mealStoreCategoryRelationModel->saveAll($new);
        return true;
    }

}