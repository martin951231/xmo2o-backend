<?php
/**
 * 店铺列表service
 * Created by subline.
 * Author: wangchen
 * Date Time: 2021/03/08 10:44
 */

namespace app\shop\model\service\lable;

use app\shop\model\db\ShopCategory;
use app\shop\model\db\MerchantStoreShop;

class MerchantStoreShopService {

    // 获取店铺列表
    public function getShopListService($store, $fields='*'){
        $result = (new MerchantStoreShop())->getShopList($store, $fields);
        return $result;
    }

}