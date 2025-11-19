<?php


namespace app\mall\model\db;

use think\Model;
use think\facade\Db;
class MallFullGiveSku extends Model
{
    /**
     * @param $goods_id
     * @param $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 赠送活动id
     */
    public function getActId($goods_id,$store_id){
        $where[] = [
            'goods_id','=',$goods_id,
        ];
        $field='act_id';
        $arr= $this->field($field)->where($where)->find()->toArray();
        if(empty($arr)){
            $arr= (new MallActivity())->getFullGiveAct($store_id);
        }else{
            //判断活动的有效性
            $arr1= (new MallActivity())->getActivityEffic($arr['act_id']);
            if(empty($arr1)){
                $arr=[];
            }
        }
        return $arr;
    }

    /**
     * @param $act_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 根据活动id查询参与活动的商品
     */
    public function getGoodsListByActId($act_id){
        $where[] = [
            'act_id','=',$act_id,
        ];
        $field='goods_id';
        $arr= $this->field($field)->where($where)->select()->toArray();
        $list=array();
        foreach ($arr as $key=>$val){
            $list[$key]=(new MallGoods())->getGoodsByGoodsId($val['goods_id']);
        }
        return $list;
    }

    /**
     * @param $condition
     * @return array
     * 查询商品参与的活动详情
     */
    public function getDetail($condition){
        return [];
    }
}