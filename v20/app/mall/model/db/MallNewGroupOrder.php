<?php


namespace app\mall\model\db;
use think\Model;

class MallNewGroupOrder extends Model
{
    /**
     * 编辑其中一项
     * @param $where
     * @param $data
     * @return MallGoods
     */
    public function updateOne($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }


    /**
     * 找出订单列表
     * @param $where
     * @param $data
     * @return MallGoods
     */
    public function getList($where, $filed="*",$order='id asc')
    {
        $result = $this->field($filed)->where($where)->order($order)->select()->toArray();
        return $result;
    }
    /**
     * 获取订单列表
     */
    public function getPayList($whereOr,$field="s.*")
    {
        $msg = $this->alias('s')
            ->join('mall_order' . ' o', 's.order_id = o.order_id')
            ->whereOr($whereOr)
            ->field($field)
            ->select()
            ->toArray();
        return $msg;
    }

    /**
     * 添加一项
     * @param $where
     * @param $data
     */
    public function addOne($data)
    {
        $result = $this->insertGetId($data);
        return $result;
    }

    /**
     * @param $order_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 根据订单查询团购订单id
     */
    public function getOne($order_id){
        $where = [
            ['s.order_id','=',$order_id],
        ];
        $field="m.user_id,m.complete_num,m.num,m.start_time,m.end_time,m.status as team_status,s.*";
        $msg = $this->alias('s')
            ->join('mall_new_group_team' . ' m', 's.tid = m.id')
            ->where($where)
            ->field($field)
            ->find();
        if(!empty($msg)){
            $msg=$msg->toArray();
        }
        return $msg;
    }

    /**
     * @param $where
     * @return mixed
     * 链表查询拼团订单
     */
    public function getTeamOrderList($where){
        $field="s.*";
        $msg = $this->alias('s')
            ->join('mall_order' . ' od', 'od.order_id = s.order_id')
            ->join('mall_new_group_team' . ' m', 's.tid = m.id')
            ->where($where)
            ->field($field)
            ->select()
            ->toArray();
        return $msg;
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @return mixed
     * f
     */
    public function getOrderList($where,$field,$order){
        //$field="m.user_id,m.complete_num,m.num,m.start_time,m.end_time,m.status as team_status,s.*";
        $msg = $this->alias('s')
            ->join('mall_order' . ' od', 's.order_id = od.order_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->find();
        if(!empty($msg)){
            $msg=$msg->toArray();
        }
        return $msg;
    }

    /**
     * @param $order_id
     * @param $status
     * @return mixed
     * 订单加状态查出数据
     */
    public function getOneByWh($order_id,$status){
        $where = [
            ['s.order_id','=',$order_id],
            ['m.status','=',$status],
        ];
        $field="m.user_id,m.complete_num,m.num,m.start_time,m.end_time,m.status as team_status,s.*";
        $msg = $this->alias('s')
            ->join('mall_new_group_team' . ' m', 's.tid = m.id')
            ->where($where)
            ->field($field)
            ->find();
        if(!empty($msg)){
            $msg=$msg->toArray();
        }
        return $msg;
    }
    /**
     * @param $order_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 根据条件查询团购订单
     */
    public function getOneOrder($where,$field){
        $msg = $this->where($where)
            ->field($field)
            ->find();
        if(!empty($msg)){
            $msg=$msg->toArray();
        }
        return $msg;
    }

    /**
     * @param $where
     * @return mixed
     * 返回关联子订单的符合条件数量
     */
    public function getCount($where){
        $msg = $this->alias('s')
            ->join('mall_new_group_team_user' . ' m', 's.tid = m.tid')
            ->where($where)
            ->count();
        return $msg;
    }
    
    /**
     * 查询团员信息
     */
    public function getUserInfo($where,$field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $data = $this->alias('a')
            ->join($prefix.'user b','a.uid = b.uid')
            ->where($where)
            ->field($field)
            ->select();
        return $data;
    }
    
    /**
     * 查询拼单商品信息
     */
    public function getGoodsInfo($where,$field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $data = $this->alias('a')
            ->join($prefix.'mall_new_group_act b','a.act_id = b.id')
            ->join($prefix.'mall_goods c','b.goods_id = c.goods_id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }
}