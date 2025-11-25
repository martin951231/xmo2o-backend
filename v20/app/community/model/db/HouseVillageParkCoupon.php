<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/11/4 13:43
 */
namespace app\community\model\db;

use think\Model;
class HouseVillageParkCoupon extends Model
{
    /**
     * Notes: 获取一条
     * @param $where
     * @param $field
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/27 13:37
     */
    public function getFind($where,$field='*',$order='id ASC')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }
    public function getOneCoupon($where,$field='*')
    {
        $info =$this->alias('vpc')->leftJoin('park_shop_coupons sc','vpc.coupons_id=sc.id')->where($where)->field($field)->find();
        return $info;
    }
    /**
     * 修改订单
     * @param array $where
     * @param array $data
     * @return bool
     * @author zhubaodi
     * @date_time 2022/02/07
     */
    public function saveOne($where = [], $data = [])
    {
        $res = $this->where($where)->save($data);
        return $res;
    }
    public function addOneData( $data = [])
    {
        $res = $this->insertGetId($data);
        return $res;
    }
    public function getListss($where,$field=true,$page=0,$limit=15,$order='mc.id DESC')
    {
        $sql= $this->alias('mc')
            ->leftJoin('house_village_car_access_record c','mc.id=c.coupon_id')
            ->leftJoin('house_village_car_access_record cr','c.order_id=cr.order_id and c.park_id=cr.park_id and cr.accessType=1')
            ->where($where)->field($field);
        if ($page){
            $data = $sql->page($page,$limit)->order($order)->select();
        }else{
            $data = $sql->order($order)->select();
        }
     
        return $data;

    }

    public function getCounts($where)
    {
        $data= $this->alias('mc')
            ->leftJoin('house_village_car_access_record c','mc.id=c.coupon_id')
            ->leftJoin('house_village_car_access_record cr','c.order_id=cr.order_id and c.park_id=cr.park_id and cr.accessType=1')
            ->where($where)->count();

        return $data;

    }

    public function getCouponListss($where,$field=true,$page=0,$limit=15,$order='mc.id DESC')
    {
        $sql= $this->alias('mc')
            ->where($where)->field($field);
        if ($page){
            $data = $sql->page($page,$limit)->order($order)->select();
        }else{
            $data = $sql->order($order)->select();
        }

        return $data;

    }

    public function getCouponCounts($where)
    {
        $data= $this->alias('mc')
            ->where($where)->count();

        return $data;

    }
}