<?php


namespace app\mall\model\db;
use think\facade\Db;
use think\Model;
class ShopDiscount extends Model
{
    public function getOne($goods_id,$store_id){
        $where[] = ['','exp',Db::raw("FIND_IN_SET($goods_id,goods_id)")];
        $result1=[];
        $arr= $this->where($where)->find();
        if($arr){
            $result1['act_type']=0;
            $result1['name']="活动";
            $result1['msg']="满".round($arr['full_money'])."优惠".round($arr['reduce_money']);
        }
        return $result1;
    }
}