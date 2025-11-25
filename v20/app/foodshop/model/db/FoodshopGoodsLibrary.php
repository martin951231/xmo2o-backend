<?php
/**
 * 餐饮商品model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:38
 */

namespace app\foodshop\model\db;
use think\Model;
use think\model\relation\BelongsTo;

class FoodshopGoodsLibrary extends Model {

    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 根据条件获取商品列表
     * @param $where
     * @param $order 排序
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGoodsListByCondition($where,$order='') {
        if(empty($where)) {
             return false;
         }
 
         $result = $this->where($where)->order($order)->select();
         return $result;
    }
    
    /**
     * 根据id更新数据
     * @param $goodsId
     * @param $data
     * @return array|bool|Model|null
     */
    public function updateByGoodsId($goodsId,$data) {
        if(!$goodsId || !$data){
            return false;
        }

        $where = [
            'goods_id' => $goodsId
        ];

        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * 关联分类表
     */
    public function sorts()
    {
        return $this->belongsTo(FoodshopGoodsSort::class, 'spec_sort_id', 'sort_id');
    }
    
}