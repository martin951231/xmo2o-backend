<?php
/**
 * 外卖附属商品
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/03 16:49
 */

namespace app\shop\model\db;
use think\Model;
class ShopSubsidiaryPieceGoods extends Model {
    /**
     * 根据商品获取附属菜
     * @param $where
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSubsidiaryPieceGoods($where) {
       if(empty($where)) {
            return false;
        }

        $result = $this->where($order)->find();
        return $result;
    }
}