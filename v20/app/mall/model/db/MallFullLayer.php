<?php


namespace app\mall\model\db;

use think\Model;
use app\common\model\service\coupon\MerchantCouponService;

class MallFullLayer extends Model
{
    //满减满赠信息
    public function getOne($id,$store_id)
    {
        $where = [
            ['id', '=', $id]
        ];
        $arr= $this->where($where)->find()->toArray();
        $result1=[];
        if(isset($arr['coupon_id'])){
            $MerchantCoupon=new MerchantCouponService();
            $result=$MerchantCoupon->getCouponInfo($arr['coupon_id']);
            if($result){
                $result1['act_type']=0;
                $result1['name']="活动";
                $result1['msg']="满".round($result['order_money'])."优惠".round($result['discount']);
            }
        }else if(isset($arr['give_goods_id'])){
            $where[]=['is_del','=',0];
            if(strstr($arr['give_goods_id'], ',')){
               $con=explode(",",$arr['give_goods_id']);
               $where[]=['goods_id','in',$con];
            }else{
               $where[]=['goods_id','=',$arr['give_goods_id']];
            }
            $result=(new MallGoods())->getAllByID($where,$field='name as goods_name');
            if($result){
                $result1['act_type']=1;
                $result1['name']="赠品";
                $result1['msg']=$result;
            }
        }
        return $result1;
    }
}