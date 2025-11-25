<?php
/**
 * 系统后台餐饮分类model
 * Created by vscode.
 * Author: henhtingmei
 * Date Time: 2020/5/16 10:48
 */

namespace app\foodshop\model\db;
use think\Model;
class MealStoreCategoryRelation extends Model {

    /**
     * 根据分类父id获取分类关系列表
     * @param $catFid
     * @return array|bool|Model|null
     */
    public function getCategoryRelationByCatFid($catFid = 0) {
        if(!$catFid){
            return null;
        }

        $where = [
            'cat_fid' => $catFid
        ];

        $result = $this->where($where)->select();
        return $result;
    }

    
    /**
     * 根据分类id获取分类关系
     * @param $catId
     * @return array|bool|Model|null
     */
    public function getCategoryRelationByCatId($catId) {
        if(!$catId){
            return null;
        }

        $where = [
            'cat_id' => $catId
        ];

        $result = $this->where($where)->select();
        return $result;
    }

    /**
     * 根据分类id获取分类关系
     * @param $storeId
     * @return array|bool|Model|null
     */
    public function getCategoryRelationByStoreId($storeId) {
        if(!$storeId){
            return null;
        }

        $where[] = [
            'store_id' ,'in',  $storeId
        ];

        $result = $this->where($where)->select();
        return $result;
    }

    
}