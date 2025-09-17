<?php
/**
 * 外卖商品
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/19 09:35
 */

namespace app\shop\model\db;
use think\Model;
class ShopGoodsSpecValue extends Model {
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 根据规格id获取规格值
     * @param $sid
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSpecValueBySid($sid) {
       if(empty($sid)) {
            return false;
        }

        $where[] = [
            "sid" , 'in', $sid
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