<?php


namespace app\mall\model\db;

use think\Model;

class MallPrepareActSku extends Model
{
    public function getDetail($goods_id)
    {

    }

    /**批量添加商品信息
     * @param $data
     * @return int
     */
    public function addAll($data)
    {
        return $this->insertAll($data);
    }

    /**
     * 删除数据
     * @param $where
     * @return boolean
     */
    public function del($where)
    {
        $res = $this->where($where)->delete();
        return $res;
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

    /**
     * 编辑其中一项
     * @param $where
     * @param $data
     * @return
     */
    public function getSum($where, $field)
    {
        $result = $this->where($where)->sum($field);
        return $result;
    }
    /**
     * @param $act_id
     * @return bool
     * 活动商品基本信息
     */
    public function getGoodsByActId($act_id)
    {
        $condition[] = ['act_id', '=', $act_id];
        $result = $this->alias('s')
            ->where($condition)
            ->select();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return   $result;
    }
    //查找最低价
    public function prepareMinPrice($act_id,$goods_id)
    {
        $where[] = ['act_id', '=', $act_id];
        $where[] = ['goods_id', '=', $goods_id];
        $field="bargain_price+rest_price as act_price";
        $res=$this->field($field)->where($where)->select()->toArray();
        if(!empty($res)){
            $last_names = array_column($res,'act_price');
            array_multisort($last_names,SORT_ASC,$res);
            $act_price=$res[0]['act_price'];
        }else{
            $act_price=0;
        }
        return $act_price;

    }

}