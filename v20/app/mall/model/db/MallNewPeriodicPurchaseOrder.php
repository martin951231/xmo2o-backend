<?php


namespace app\mall\model\db;

use think\Exception;
use think\Model;
use think\facade\Db;
class MallNewPeriodicPurchaseOrder extends Model
{
    /**
     *  添加返回主键
     * @param $data
     * @return int|string
     * @author mrdeng
     */
    public function addOne($data)
    {
        $result = $this->insertGetId($data);
        return $result;
    }

    /**
     * @param $where 条件
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 返回周期购期数记录
     */
    public function getPeriodicOrderList($where){
         $result=$this->where($where)->order("periodic_date asc")->select();
         if(empty($result)){
             return $result;
         }else{
             return $result->toArray();
         }
    }

    public function getNowPeriodicOrder($where,$order='id desc'){
        $result=$this->where($where)->order($order)->find();
        if(empty($result)){
            return $result;
        }else{
            return $result->toArray();
        }
    }

    /**
     * @param $where
     * @return mixed
     *联合订单活动表查询活动数据
     */
    public function getPeriodicNowNoCompletePurchase($where){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->join($prefix.'mall_new_periodic_purchase'.' a','s.act_id = a.id')
            ->where($where)
            ->find();
        if(empty($result)){
            return $result;
        }else{
            return $result->toArray();
        }
    }

    /**
     * @param $where 条件
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 返回周期购当期记录
     */
    public function getPeriodicOrder($where){
        $result=$this->where($where)->find();
        if(empty($result)){
            return $result;
        }else{
            return $result->toArray();
        }
    }

    /**
     * @param $where 条件
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 返回周期购条件个数
     */
    public function getPeriodicOrderNum($where){
        $result=$this->where($where)->count();
        return $result;
    }

    /**
     * @param $where 条件
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 更新周期购延期期数
     */
    public function getPeriodicOrderUpdate($where,$data){
        $result=$this->where($where)->save($data);
        return $result;
    }

    /**
     * @param $order_id
     * @return mixed
     * 按月份查询记录有几个月范围
     */
    public function getOrderListByOrderId($order_id){

        $where[] = ['order_id','=',$order_id];
        $arr = $this->where($where)
            ->field("periodic_date,id,FROM_UNIXTIME(periodic_date, '%Y-%m') AS datetime")
            ->group('datetime')->order('periodic_date asc')->select();
        if(!empty($arr)){
            $arr=$arr->toArray();
        }
       return $arr;
    }


    /**
     * @param $order_id
     * @param $monnth 如：2020-08
     * @return mixed
     * 每个月有几条记录
     */
    public function getListByMonth($order_id,$monnth){
        $where[] = [Db::raw("FROM_UNIXTIME(periodic_date, '%Y-%m')"),'=', $monnth];
        $where[] = ['order_id','=',$order_id];
        $arr = $this->where($where)->
        field("id,is_complete,periodic_date")
            ->order('periodic_date ASC')->select()->toArray();
        return $arr;
    }

    //更新数据
    public function _updateData($where,$data){
        $msg= $this->where($where)->update($data);
        return $msg;
    }

    public function retCountByOrderid($where,$field)
    {
        //表前缀
            $prefix = config('database.connections.mysql.prefix');
            $result = $this->alias('s')
                ->join($prefix . 'mall_new_periodic_purchase' . ' m', 's.act_id = m.id')
                ->field($field)
                ->where($where)
                ->find();
            if(!empty($result)){
                $result=$result->toArray();
            }
           return $result;
    }
}