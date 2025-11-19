<?php
/**
 * 餐饮商品规格service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/02 16:23
 */

namespace app\foodshop\model\service\goods;
use app\foodshop\model\db\FoodshopGoodsSku as  FoodshopGoodsSkuModel;
class FoodshopGoodsSkuService {
    public $foodshopGoodsSkuModel = null;
    public $weekList = [];
    public function __construct()
    {
        $this->foodshopGoodsSkuModel = new FoodshopGoodsSkuModel();
    }

    /**
     * 根据商品id获取sku列表
     * @param $goodsId
     * @return array
     */
    public function getSkuListByGoodsId($goodsId,$order=[]) {
        if(empty($order)){
            // 排序
            $order = [
                // 'sort' => 'DESC',
                // 'sort_id' => 'ASC',
            ];
        }

        $skuList = $this->foodshopGoodsSkuModel->getSkuListByGoodsId($goodsId,$order);
        if(!$skuList) {
            return [];
        }
        
        return $skuList->toArray(); 
    }


    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->foodshopGoodsSkuModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 根据sku组合index获取sku
     * @param $index
     * @return array
     */
    public function getSkuByIndex($index,$goodsId = 0) {
        if(empty($index)){
            return [];
        }

        $sku = $this->foodshopGoodsSkuModel->getSkuByIndex($index,$goodsId);
        if(!$sku) {
            return [];
        }
        
        return $sku->toArray(); 
    }

    /**
     * 根据sku组合index获取sku
     * @param $index
     * @return array|bool|Model|null
     */
    public function updateSku($index,$data) {
        if(!$index || !$data){
            return false;
        }

        $where = [
            'index' => $index
        ];

        try {
            $result = $this->foodshopGoodsSkuModel->where($where)->update($data);
        }catch (\Exception $e) {
            return false;
        }
        return $result;
    }

    /**
     * 组合sku
     * @param $goodsId
     * @return array
     */
    public function combineSku($foodshopSku) {
        if(empty($foodshopSku)){
            return [];
        }

        $specs = '';
        $pre = '';
        $foodshopSpecValue = array();
        if($foodshopSku){
            foreach ($foodshopSku as $key => $value) {
                if($value){
                    // 第一个规格id拼接键值|规格价格:规格库存|#第二个规格id拼接键值|规格价格:规格库存|#
                    $specs .= $pre . $value['index'] . '|' . $value['price']. ':' . $value['spec_stock']. '|#';
                }
            }
        }
        return $specs; 
    }


    /**
     * 删除
     * @param where
     * @return bool
     */
    public function del($where){
        if(empty($where)){
            return false;
        }
        try {
            $result = $this->foodshopGoodsSkuModel->where($where)->delete();
//            var_dump($this->foodshopGoodsSkuModel->getLastSql());
        }catch (\Exception $e) {
            return false;
        }

        return $result;
    }
}