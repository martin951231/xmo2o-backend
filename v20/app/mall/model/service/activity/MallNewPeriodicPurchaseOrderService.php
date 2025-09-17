<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallNewPeriodicDeliver;
use app\mall\model\db\MallNewPeriodicPurchaseOrder;

class MallNewPeriodicPurchaseOrderService
{
    /**
     * @param $order_id
     * @return \think\db\BaseQuery
     * 获取分组月份订单记录
     */
       public function getOrderListMonthByOrderId($order_id){
          return (new MallNewPeriodicPurchaseOrder())->getOrderListByOrderId($order_id);
       }


    /**
     * @param $order_id
     * @param $monnth 如：2020-08
     * @return mixed
     * 每个月有几条记录
     */
       public function getListByMonth($order_id,$month){
           return (new MallNewPeriodicPurchaseOrder())->getListByMonth($order_id,$month);
       }
    /**
     * @param $where 条件
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 返回周期购期数记录
     */
    public function getPeriodicOrderList($where)
    {
        $arr=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderList($where);
        return $arr;
    }


    /**
     * @param $where 条件
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 返回周期购当前期数记录
     */
    public function getNowPeriodicOrder($where)
    {
        $arr=(new MallNewPeriodicPurchaseOrder())->getNowPeriodicOrder($where);
        return $arr;
    }

    public function getStoreManPreiodic($order_id){
        $return= (new MallNewPeriodicPurchaseOrder())->getOrderListByOrderId($order_id);//按月份查询记录有几个月
        $dates_list=array();
        $arr=array();
        if(!empty($return)){
            foreach ($return as $key=>$val){//遍历有几个月
                $dates_list['deliver_date']=$val['datetime'];//月
                $order_list=(new MallNewPeriodicPurchaseOrder())->getListByMonth($order_id,$val['datetime']);//每个月的记录
                $order_list1=array();
                $arrs=array();
                foreach ($order_list as $k=>$v){//遍历每个月的记录
                    $where=[['purchase_order_id','=',$v['id']]];
                    $order_list1['date_num']=date('d',$v['periodic_date'])*1;
                    $msg=(new MallNewPeriodicDeliver())->getPeriodicDeliver($where);//有没有配送记录

                    if(empty($msg)){//没有配送记录则还未发货 0待发货 1备货中 2已顺延 3待收货 4 已收货
                        $order_list1['deliver_status']=$v['is_complete'];
                    }else{
                       if($msg['status']==0){//配送中
                           $order_list1['deliver_status']=3;
                       }else{//已收货
                           $order_list1['deliver_status']=4;
                       }
                    }
                    $arrs[]=$order_list1;
                }
                $dates_list['deliver_list']=$arrs;
                $arr[]=$dates_list;
            }
        }else{
            return [];
        }
        return $arr;
    }

    /**
     * @param $periodic_order_id
     * @return mixed
     * 根据周期记录id返回当前期和活动总期数
     */
    public function retCountByOrderid($periodic_order_id){
           if (empty($periodic_order_id)) {
                throw new \think\Exception('参数缺失');
           }
           $where=[['s.id','=',$periodic_order_id]];
           $field='m.periodic_count,s.order_id,s.periodic_date,s.periodic_count as now_count';
           return (new MallNewPeriodicPurchaseOrder())->retCountByOrderid($where,$field);
    }
}