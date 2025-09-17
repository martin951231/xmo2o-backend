<?php
/**
 * MallNewBargainSku.php
 * 砍价sku model
 * Create on 2020/10/23 18:44
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class MallNewBargainSku extends Model
{
    /**
     * @param $where
     * @return array
     * 通过id获取sku
     */
    public function getBySkuId($where,$field="*")
    {
        $arr = $this->field($field)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $condition1
     * @param $team
     * 根据条件保存数据
     * @author mrdeng
     */
    public function saveData($condition1, $team)
    {
        $arr= $this->where($condition1)->save($team);
        return $arr;
    }

    /**
     * 根据act_id连表查出sku信息
     */
    public function getSkuByActId($act_id)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('b')
            ->join($prefix . 'mall_goods_sku gs', 'b.sku_id=gs.sku_id')
            ->join($prefix . 'mall_goods g', 'b.goods_id=g.goods_id')
            ->join($prefix . 'mall_new_bargain_team bg', 'b.act_id=bg.act_id')
            ->where(['b.act_id' => $act_id])
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 根据act_id连表查出sku信息
     */
    public function getSkuByActId1($act_id)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('b')
            ->join($prefix . 'mall_new_bargain_act bt', 'bt.id=b.act_id')
            ->join($prefix . 'mall_goods_sku gs', 'b.sku_id=gs.sku_id')
            ->join($prefix . 'mall_goods g', 'b.goods_id=g.goods_id')
            ->where(['b.act_id' => $act_id])
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 根据act_id连表查出sku信息
     */
    public function getCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('b')
            ->join($prefix . 'mall_new_bargain_act a', 'b.act_id=a.id')
            ->join($prefix . 'mall_new_bargain_team bg', 'b.act_id=bg.act_id')
            ->where($where)
            ->count();
            return $arr;
    }
}