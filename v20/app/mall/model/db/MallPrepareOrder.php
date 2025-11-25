<?php


namespace app\mall\model\db;

use think\Model;

class MallPrepareOrder extends Model
{
    /**查询预售活动商品定金/尾款支付单数
     * @param $where
     * @param string $field
     * @return array
     */
    public function getList($where, $field = '*')
    {
        $result= $this->where($where)->field($field)->select();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }

    /**查询预售活动
     * @param $where
     * @param string $field
     * @return array
     */
    public function getOne($where, $field = '*')
    {
        $result= $this->where($where)->field($field)->find();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }
    /**
     * 获取预售信息
     * @param $prefield
     * @param $order_id
     * @return mixed
     */
    public function getPrepare($prefield, $order_id)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('po')
            ->field($prefield)
            ->join($prefix . 'mall_order o', 'po.order_id = o.order_id')
            ->join($prefix . 'mall_prepare_act_sku p', 'p.sku_id = po.sku_id and p.act_id=po.act_id')
            ->where(['o.order_id' => $order_id])
            ->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
    /**
     * @param $where
     * @return array
     * 查询活动基本信息
     */
    public function getInfo($where,$fields='*',$order='s.id desc')
    {
        $fields1=' m.*,s.*';
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->join($prefix.'mall_order'.' o','o.order_id = s.order_id')
            ->join($prefix.'mall_prepare_act_sku'.' m','s.act_id = m.act_id')
            ->field($fields1)
            ->where($where)
            ->order($order)
            ->find();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }

    /** 添加数据 获取插入的数据id
     * Date: 2020-10-19
     * @param $data
     * @return int|string
     */
    public function addPrepare($data)
    {
        return $this->insertGetId($data);
    }

    /** 修改数据
     * Date: 2020-10-19
     * @param $data
     * @return int|string
     */
    public function updatePrepare($data,$where) {
        $result = $this->where($where)->update($data);
        return $result;
    }
}