<?php
/**
 * 店铺分类service
 * Created by subline.
 * Author: wangchen
 * Date Time: 2021/03/08 10:44
 */

namespace app\shop\model\service\lable;

use app\shop\model\db\ShopCategoryRelation;

class ShopCategoryRelationService {

    // 获取店铺数量
    public function getShopNumerService($cat_id, $cat_fid, $fields='*'){
        $result = (new ShopCategoryRelation())->getShopNumer($cat_id, $cat_fid, $fields);
        return $result;
    }


    // 获取店铺数量
    public function getShopStoreIdService($cat_id, $cat_fid, $fields='*'){
        $result = (new ShopCategoryRelation())->getShopStoreId($cat_id, $cat_fid, $fields);
        return $result;
    }

}