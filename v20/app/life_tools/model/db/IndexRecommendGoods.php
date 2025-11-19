<?php

/**
 * 首页推荐商品列表
 */
namespace app\life_tools\model\db;


use think\Model;

class IndexRecommendGoods extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $param
     * @param $where
     * @param $field
     * @param $order
     * 商品列表
     */
    public function getGoodsList($param,$where,$field,$order){
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('rg')
            ->order($order);
        switch ($param['goods_type']){
            case 'ticket':
                array_merge($where,['l.status'=>1,'l.is_del'=>0,'lt.status'=>1,'lt.is_del'=>0]);
                $field=$field.',lt.price as goods_price,l.images as image,l.title as goods_name,lt.label,lt.ticket_id,m.name';
                $arr=$arr->field($field)->where($where)
                    ->join($prefix . 'index_recommend r', 'rg.recommend_id = r.id')
                    ->join($prefix . 'life_tools_ticket lt', 'lt.ticket_id = rg.goods_id')
                    ->join($prefix . 'life_tools l', 'l.tools_id = lt.tools_id');
                break;
            case 'sport':
                array_merge($where,['l.status'=>1,'l.is_del'=>0]);
                $field=$field.',l.money as goods_price,l.images as image,l.title as goods_name,l.label,m.name';
                $arr=$arr->field($field)->where($where)
                    ->join($prefix . 'index_recommend r', 'rg.recommend_id = r.id')
                    ->join($prefix . 'life_tools l', 'l.tools_id = rg.goods_id');
                break;
            case 'shop':
                array_merge($where,['l.status'=>1,'l.goods_type'=>0,'ms.status'=>1]);
                $field=$field.',l.price as goods_price,l.image,l.name as goods_name,m.name';
                $arr=$arr->field($field)->where($where)
                    ->join($prefix . 'index_recommend r', 'rg.recommend_id = r.id')
                    ->join($prefix . 'shop_goods l', 'l.goods_id = rg.goods_id')
                    ->join($prefix . 'merchant_store ms', 'ms.store_id = rg.store_id');
                break;
            case 'mall':
                array_merge($where,['l.status'=>1,'l.is_del'=>0,'ms.status'=>1]);
                $field=$field.',l.price as goods_price,l.image,l.name as goods_name,m.name';
                $arr=$arr->field($field)->where($where)
                    ->join($prefix . 'index_recommend r', 'rg.recommend_id = r.id')
                    ->join($prefix . 'mall_goods l', 'l.goods_id = rg.goods_id')
                    ->join($prefix . 'merchant_store ms', 'ms.store_id = rg.store_id');
                break;
        }
        $arr=$arr->join($prefix . 'merchant m', 'm.mer_id = rg.mer_id')
            ->select()->toArray();
        fdump($this->getLastSql(),"bwww22222222222");
        return $arr;
    }

    /**
     * 获得商品id
     */
    public function getCol($where,$field){
        $arr=$this->field($field)->where($where)->column($field);
        return $arr;
    }
}