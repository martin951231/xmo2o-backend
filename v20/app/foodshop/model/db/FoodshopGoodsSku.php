<?php
/**
 * 餐饮商品skumodel
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/30 10:38
 */

namespace app\foodshop\model\db;
use think\Model;
class FoodshopGoodsSku extends Model {
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 根据商品id获取sku列表
     * @param $goodsId
     * @return array|bool|Model|null
     */
    public function getSkuListByGoodsId($goodsId,$order=[]) {
        if(!$goodsId){
            return null;
        }

        $where = [
            'goods_id' => $goodsId
        ];

        $result = $this->where($where)->order($order)->select();
        return $result;
    }

    /**
     * 根据sku组合index获取sku
     * @param $index
     * @return array|bool|Model|null
     */
    public function getSkuByIndex($index, $goodsId = 0) {
        if(!$index){
            return null;
        }

        $where = [
            'index' => $index,
            'goods_id' => $goodsId
        ];

        $result = $this->where($where)->find();
        return $result;
    }

    
}