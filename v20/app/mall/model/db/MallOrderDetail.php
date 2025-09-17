<?php


namespace app\mall\model\db;

use think\Model;

class MallOrderDetail extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    //热卖信息
    public function getMallGoodByHot($where)
    {
        //表前缀
        $prefix = config('database.connections.mysql.prefix');
        //暂时定3天
        $onetime = strtotime(date('Y-m-d H:i:s', strtotime('- 3 day')));
        //当前时间戳
        $nowtime = time();
        /*热卖*/
        $field = 's.goods_id,sum(s.goods_num) as goods_num';
        $result = $this->alias('s')->join($prefix . 'mall_order' . ' m', 's.order_id = m.order_id')->field($field)
            ->where($where)->whereBetween('m.pay_time', "$onetime,$nowtime")->group('s.goods_id')->select();
        //var_dump($this->getLastSql());
        $arr = [];
        foreach ($result as $key => $val) {
            if ($val['goods_num'] > 300) {
                $arr[$key] = $val['goods_id'];
            }
        }
        return $arr;
    }
    /**
     * @param $where
     * @param string $field
     * @return float
     * 统计某个条件信息
     */
    public function getSum($where, $field = '*')
    {
        return $this->where($where)->sum($field);
    }

    public function getOne($orderid)
    {
        $where = [
            ['order_id', '=', $orderid],
        ];
        $arr=$this->where($where)->find();
        return $arr;
    }

    public function getByDetailId($id)
    {
        $where = [
            ['id', '=', $id],
        ];
        return $this->where($where)->find()->toArray();
    }

    public function getGoodsDetail($orderId)
    {
        return self::where('order_id', $orderId)
            ->field('goods_id, name as goods_name, price, num as goods_num')
            ->select();
    }
    
    public function getByOrderId($field,$id)
    {
        $where = [
            ['order_id', '=', $id],
        ];
        return $this->field($field)->where($where)->select()->toArray();
    }

    /**
     * @param $where
     * @param $field
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 根据条件获取查找
     */
    public function geOrderMsg($where,$field)
    {
        $return=$this->field($field)->where($where)->select();
        if(!empty($return)){
            $return=$return->toArray();
        }
        return $return;
    }

    public function getOneOrderMsg($where)
    {
        $return=$this->where($where)->min('money_total');
        return $return;
    }

    /**
     * @param $where
     * @param $field
     * @return int
     * 查找条件数量
     */
    public function geOrderCount($where)
    {
        $return=$this->where($where)->count();
        return $return;
    }

    public function getGoodsJoinData($where, $field){
        $result = $this->alias('d')
                    ->leftJoin('mall_goods g', 'd.goods_id=g.goods_id')
                    ->field($field)
                    ->where($where)
                    ->select();
        return $result;
    }

    public function getOrderGoods($where, $field){
        $result = $this->alias('d')
                    ->leftJoin('mall_order o', 'o.order_id=d.order_id')
                    ->field($field)
                    ->where($where)
                    ->select();
        return $result;
    }
    
    public function getRealOrderGoods($where, $field){
        $result = $this->alias('d')
            ->join('mall_goods g', 'd.goods_id=g.goods_id')
            ->field($field)
            ->where($where)
            ->select();
        return $result;
    }
}