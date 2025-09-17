<?php
/**
 * MallSixAdverGoods.php
 * 六宫格关联的商品model
 * Create on 2020/10/21 11:31
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class MallSixAdverGoods extends Model
{
    /**
     * @param $field
     * @param $where
     * @return array
     * 根据条件获取
     */
    public function getByCondition($field, $where)
    {
        $arr = $this->field($field)->where($where)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @return array
     * 联合查询获取关联商品的详细
     */
    public function getDetail($limit, $where, $type = '', $order = 'id DESC')
    {
        $prefix = config('database.connections.mysql.prefix');
        if (intval($limit) == 0) {
            $arr = $this->alias('s')
                ->field('g.*,s.*')
                ->join($prefix . 'mall_goods g', 's.goods_id=g.goods_id')
                ->where($where)
                ->order($order)
                ->select();
        } else {
            if ($type == 'limited' || $type == 'bargain' || $type == 'group') {
                $where = array_merge($where, [['a.status', '<>', 2], ['a.end_time', '>', time()], ['type', '=', $type]]);
                $arr = $this->alias('s')
                    ->field('g.*,s.*')
                    ->join($prefix . 'mall_goods g', 's.goods_id=g.goods_id')
                    ->join($prefix . 'mall_activity_detail ad', 's.goods_id=ad.goods_id')
                    ->join($prefix . 'mall_activity a', 'ad.activity_id=a.id')
                    ->where($where)
                    ->order($order)
                    ->limit(4)
                    ->select();
            } else {
                $arr = $this->alias('s')
                    ->field('g.*,s.*')
                    ->join($prefix . 'mall_goods g', 's.goods_id=g.goods_id')
                    ->where($where)
                    ->order($order)
                    ->limit(4)
                    ->select();
            }
        }
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @return array
     * 联合查询获取关联商品的详细
     */
    public function getDetail2($where, $page, $pageSize, $order = 'id DESC')
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('s')
            ->field('g.*,s.*')
            ->join($prefix . 'mall_goods g', 's.goods_id=g.goods_id')
            ->where($where)
            ->order($order)
            ->page($page, $pageSize)
            ->select();

        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    public function getCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        return $this->alias('s')
            ->field('g.*,s.*')
            ->join($prefix . 'mall_goods g', 's.goods_id=g.goods_id')
            ->where($where)
            ->count();
    }

    /**
     * @param $data
     * @return int|string
     * 添加
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * @param $data
     * @return int|string
     * 更新
     */
    public function updateOne($where, $data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 删除
     */
    public function delSome($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }
}