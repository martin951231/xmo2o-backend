<?php
/**
 * 餐饮商品分类model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:50
 */

namespace app\foodshop\model\db;
use think\Model;
class FoodshopGoodsSort extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据分类父id获取分类列表
     * @return array|bool|Model|null
     */
    public function getSortListByStoreId($storeId,$order=['sort_id']) {
        if(!$storeId){
            return null;
        }

        $where = [
            'store_id' => $storeId
        ];

        $this->name = _view($this->name);
        $result = $this->where($where)->order($order)->select();
        return $result;
    }
      
    /**
     * 根据店铺ID返回该店铺的分类列表
     * @param $where
     * @return array
     */
    public function getList($where,$order=[]) {
        if(empty($order)){
            // 排序
            $order = [
                'sort' => 'DESC',
                'sort_id' => 'ASC',
            ];
        }

        $this->name = _view($this->name);
        $result = $this->where($where)->order($order)->select();
        return $result; 
    }

    public function getOne($where = [], $field = true, $order = []) {
        $this->name = _view($this->name);
        $result = $this->field($field)->where($where)->order($order)->find();
        return $result;
    }
    
}