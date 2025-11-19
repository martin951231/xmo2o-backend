<?php


namespace app\mall\model\db;

use think\Model;

class MallNewGroupSku extends Model
{
    public function getOne($treamid)
    {
        $where = [
            ['id', '=', $treamid],
        ];
        $msg=$this->where($where)->find();
        if(!empty($msg)){
            $msg=$msg->toArray();
        }
        return $msg;
    }

    /**
     * @param $where
     * @return array
     * 通过id获取sku
     */
    public function getBySkuId($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    public function getPice($where){
        $arr = $this->where($where)->min('act_price');
        if (!empty($arr)) {
            return $arr;
        } else {
            //return 200;
            return 0;
        }
    }

    public function getMin($where,$field){
        $arr = $this->where($where)->min($field);
        if (!empty($arr)) {
            return $arr;
        } else {
            //return 200;
            return 0;
        }
    }
    /**
     * @param $where
     * @param $field
     * @return float
     * 求和
     */
    public function getSum($where,$field){
        $arr = $this->where($where)->sum($field);
        return $arr;
    }
    /**
     * 根据act_id连表查出sku信息
     */
    public function getSkuByActId($act_id)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('g')
            ->join($prefix . 'mall_goods_sku gs', 'g.sku_id=gs.sku_id')
            ->join($prefix . 'mall_goods mg', 'g.goods_id=mg.goods_id')
            ->join($prefix . 'mall_new_group_team gt', 'g.act_id=gt.act_id')
            ->where(['g.act_id' => $act_id])
            ->order('g.act_price ASC')
            ->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }

    }

    public function getSkuByActId1($act_id)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('g')
            ->join($prefix . 'mall_new_group_act at', 'at.id=g.act_id')
            ->join($prefix . 'mall_goods_sku gs', 'g.sku_id=gs.sku_id')
            ->join($prefix . 'mall_goods mg', 'g.goods_id=mg.goods_id')
            ->where(['g.act_id' => $act_id])
            ->order('g.act_price ASC')
            ->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }

    }
    public function getMaxPice($where){
        $arr = $this->where($where)->max('act_price');
        if (!empty($arr)) {
            return $arr;
        } else {
            //return 200;
            return 0;
        }
    }

    /**
     * 编辑其中一项
     * @param $where
     * @param $data
     * @return
     */
    public function updateOne($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }
}