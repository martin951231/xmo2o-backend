<?php

/**
 * 商家会员寄存-用户寄存记录
 * Author: hengtingmei
 * Date Time: 2021/11/04 11:49
 */
namespace app\merchant\model\db;
use think\Model;
class CardNewDepositGoodsBindUser extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @param $field
     * @param $order
     * @return array
     * 获取用户绑定表的商品
     */
    public function getGoodsByUser($where, $field=true){
// 表前缀
        $prefix = config('database.connections.mysql.prefix');
            $arr = $this->alias('a')
                ->join($prefix . 'card_new_deposit_goods b', 'a.goods_id=b.goods_id')
                ->field($field)
                ->where($where)
                ->find();
            if(!empty($arr)){
                $arr=$arr->toArray();
            }else{
                $arr=[];
            }
            return $arr;
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @return array
     * 获取用户绑定表的商品列表
     */
    public function getGoodsByUserList($where, $field,$order,$page,$pageSize,$sql=""){
// 表前缀
        $prefix = config('database.connections.mysql.prefix');
            if(!empty($sql)){
                $arr = $this->alias('a')
                    ->join($prefix . 'card_new_deposit_goods b', 'a.goods_id=b.goods_id')
                    ->field($field)
                    ->where($where)
                    ->whereColumn('a.num','>','a.use_num')
                    ->order($order);
            }else{
                $arr = $this->alias('a')
                    ->join($prefix . 'card_new_deposit_goods b', 'a.goods_id=b.goods_id')
                    ->field($field)
                    ->where($where)
                    ->order($order);
            }
            $ret['total']=$arr->count();
            $ret['list']=$arr->page($page,$pageSize)->select()->toArray();
        return $ret;
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @return array
     * 获取用户绑定表的商品列表
     */
    public function getGoodsByUserGoodsList($where, $field,$order,$page,$pageSize){
// 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
            ->join($prefix . 'card_new_deposit_goods b', 'a.goods_id=b.goods_id')
            ->join($prefix . 'card_new_deposit_goods_bind_goods c', 'a.id=c.bind_id')
            ->field($field)
            ->where($where)
            ->order($order)
            ->group('c.goods_id');
        $ret['list']=$arr->page($page,$pageSize)->select()->toArray();
        return $ret;
    }

    
    /**
     * 关联商户
     */
    public function merchant()
    {
        return $this->belongsTo('Merchant', 'mer_id', 'mer_id');
    }

    /**
     * 关联店铺
     */
    public function store()
    {
        return $this->belongsTo('MerchantStore', 'store_id', 'store_id');
    }

    /**
     * 关联寄存商品表
     */
    public function goods()
    {
        return $this->belongsTo('CardNewDepositGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联用户寄存记录商品表
     */
    public function bindgoods()
    {
        return $this->hasMany('CardNewDepositGoodsBindGoods','bind_id', 'id');
    }

    /**
     * 关联店员表
     */
    public function staff()
    {
        return $this->belongsTo('MerchantStoreStaff', 'staff_id', 'id');
    }
}