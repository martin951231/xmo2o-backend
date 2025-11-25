<?php

/**
 * @Author: jjc
 * @Date:   2020-06-12 10:47:04
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-18 10:32:27
 */

namespace app\mall\model\db;

use think\Model;


class MallGoodsSku extends Model
{

    use \app\common\model\db\db_trait\CommonFunc;

    public function getOne($where, $field = "*")
    {
        $info = $this->where($where)->field($field)->find();
        return empty($info) ? [] : $info->toArray();
    }


    //查询库存为0的规格商品
    public function getZeroGood($where, $field = "sku_info")
    {
        $info = $this->where($where)->field($field)->select();
        //var_dump($this->getLastSql());
        return empty($info) ? [] : $info->toArray();
    }

    //查询库规格商品
    public function getGood($where, $field = "sku_info")
    {
        $info = $this->where($where)->field($field)->find();
        //var_dump($this->getLastSql());
        return empty($info) ? [] : $info->toArray();
    }

    //通过skuid，获取sku的商品所有信息
    public function getSkuGoods($sku_ids)
    {
        $where = [
            ['sku.sku_id', 'in', $sku_ids]
        ];
        $field = 'goods.*,sku.*,goods.is_del as goods_del,goods.image as goods_image';
        $data = $this->alias('sku')->leftJoin('mall_goods goods', 'sku.goods_id=goods.goods_id')->field($field)->where($where)->select();
        return $data;
    }

    /**
     * @param $sku_field
     * @param $sku_where
     * @return array
     * 根据条件获取sku
     * @author zhumengqun
     */
    public function getSkuByCondition($sku_field, $sku_where)
    {
        $arr = $this->field($sku_field)->order('price ASC')->where($sku_where)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @param $data
     * @return MallGoodsSku
     * 更新sku
     */
    public function setSku($where, $data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * @param $where
     * @throws \Exception
     * 删除
     */
    public function delSome($where)
    {
        return $this->where($where)->delete();
    }

    /**
     * @param $data
     * @return int|string
     * 添加一个
     */
    public function addOne($data)
    {
        return $this->insertGetId($data);
    }
}