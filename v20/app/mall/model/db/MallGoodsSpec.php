<?php

/**
 * @Author: jjc
 * @Date:   2020-06-12 10:49:10
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-12 14:00:21
 */

namespace app\mall\model\db;

use think\Model;

class MallGoodsSpec extends Model
{

    //获取所有规格信息
    public function getSpecList($where)
    {
        $arr = $this->where($where)->select()->toArray();
        return $arr;
    }

    /**
     * @param $where
     * @return bool
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
     * 添加
     */
    public function addOne($data)
    {
        return $this->insert($data);
    }
    /**
     * @param $field_spec
     * @param $where
     * @return array
     *根据商品id获取规格信息
     * @author 朱梦群
     */
    public function getSpec($field_spec, $where)
    {
        $prefix = config('database.connection.mysql.prefix');
        $arr = $this->alias('gs')
            ->join($prefix . 'mall_goods s', 's.goods_id = gs.goods_id')
            ->field($field_spec)
            ->where($where)
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
}

