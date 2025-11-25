<?php
/**
 * 外卖商品规格表
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/19 09:49
 */

namespace app\shop\model\db;
use think\Model;
class ShopGoodsSpec extends Model {
    /**
     * 根据商品id获取规格列表
     * @param $goodsId
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSpecByGoodsId($goodsId) {
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
}