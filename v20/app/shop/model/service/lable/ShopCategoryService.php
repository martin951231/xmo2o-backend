<?php
/**
 * 店铺分类service
 * Created by subline.
 * Author: wangchen
 * Date Time: 2021/03/08 10:44
 */

namespace app\shop\model\service\lable;

use app\shop\model\db\ShopCategory;

class ShopCategoryService {

    // 获取分类列表
    public function getLableListService($fields='*'){
        $result = (new ShopCategory())->getLableList($fields);
        return $result;
    }


    // 获取指定分类
    public function getOneLableService($cat_id, $fields='*'){
        $result = (new ShopCategory())->getOneLable($cat_id, $fields);
        return $result;
    }

    

}