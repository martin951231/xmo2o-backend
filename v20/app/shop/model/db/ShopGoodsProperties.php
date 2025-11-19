<?php
/**
 * 外卖店铺的商品属性表
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/20 09:50
 */

namespace app\shop\model\db;
use think\Model;
class ShopGoodsProperties extends Model {
    /**
     * 根据商品id获取属性列表
     * @param $goodsId
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPropertiesByGoodsId($goodsId) {
       if(empty($goodsId)) {
            return false;
        }

        $where = [
            'goods_id' => $goodsId, 
            
        ];

        $this->name = _view($this->name);
        $result = $this->where($where)->select();
        return $result;
    }

    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $this->name = _view($this->name);
        $sql = $this->field($field)->where($where)->order($order);
        if($limit)
        {
            $sql->limit($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }
}