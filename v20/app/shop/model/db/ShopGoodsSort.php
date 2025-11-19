<?php
/**
 * 外卖商品
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/19 09:36
 */

namespace app\shop\model\db;
use think\Model;
class ShopGoodsSort extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据条件获取分类列表
     * @param $where
     * @param $order 排序
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSortListByCondition($where,$order='') {
       if(empty($where)) {
            return false;
        }

        $this->name = _view($this->name);
        $result = $this->where($where)->order($order)->select();
        return $result;
    }
}