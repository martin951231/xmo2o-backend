<?php
/**
 * 系统后台餐饮店铺model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/26 15:11
 */

namespace app\foodshop\model\db;
use think\Model;
class MerchantStoreFoodshopData extends Model {
    /**
     * 根据店铺ID获取店铺
     * @return array|bool|Model|null
     */
    public function getStoreDataByStoreId($storeId) {
        if(!$storeId){
            return null;
        }

        $where = [
            'store_id' => $storeId
        ];

        $this->name = _view($this->name);
        $result = $this->where($where)->find();
        return $result;
    }
}