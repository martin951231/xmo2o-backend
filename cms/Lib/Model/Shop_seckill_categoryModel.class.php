<?php
/*
 *平台外卖限时秒杀活动分类
 *author 衡婷妹
 *date 20210111
 */
class Shop_seckill_categoryModel extends Model
{
    /* 
	 * 获得秒杀活动基本信息
     */
    public function getBaseInfo($cityId, $uid = 0)
    {
        $info = $this->where(['cat_id'=>1])->find();

        // 获得秒杀结束时间
        $info['last_time'] = $this->getLastTime();

        // 获得秒杀商品列表
        $where = [
        	'cat_id'  => 1,
            'city_id' => $cityId,
            'uid'     => $uid
        ];
        $order = 'b.sort DESC';
        $field = 'b.*,l.start_date,l.end_date,l.week,l.start_time,l.end_time,l.limit_num,l.limit_type,l.stock_type,l.is_spec,l.stock,l.origin_stock,l.limit_price,l.update_stock_date,l.is_del,g.name,g.price,g.image,g.stock_num as goods_stock,g.status AS goods_status,g.spec_value,g.spec_value,g.is_properties';
        $info['goods_list'] = D('Shop_seckill_category_goods')->getList($where,$order,1,6);

        if(count($info['goods_list']) < 6){
            //默认分类不足三个商品时，就取其他所有分类下的最新的商品来补足。
            $limit = 6-count($info['goods_list']);
            $where = [
                'city_id' => $cityId,
                'cate.status' => 1,
                '_string' => 'b.cat_id <> 1',
                'uid'     => $uid
            ]; 
            $order = 'b.sort DESC';
            $field = 'b.*,l.start_date,l.end_date,l.week,l.start_time,l.end_time,l.limit_num,l.limit_type,l.stock_type,l.is_spec,l.stock,l.origin_stock,l.limit_price,l.update_stock_date,l.is_del,g.name,g.price,g.image,g.stock_num as goods_stock,g.status AS goods_status,g.spec_value,g.spec_value,g.is_properties';
            $goods_list = D('Shop_seckill_category_goods')->getList($where,$order,1,$limit);
            $info['goods_list'] = array_merge($info['goods_list'],$goods_list);

        }
        foreach ($info['goods_list'] as &$item){
            //消费一元获得积分
            $buy_can_get_score = D('Goods_score')->getGoodsScore($item['price'],'shop',$item['has_spec']);
            $item['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
            $item['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
        }
        return $info;
    }

    /* 
	 * 获得秒杀结束时间
     */
    public function getLastTime()
    {
        $week = date('w');
        $where = [
        	'd.is_del' => 0,
            '_string' => 'FIND_IN_SET('.$week.',week)'
        ];
        $order = 'd.end_date DESC,d.end_time DESC';
        $info = M('Shop_seckill_category_goods')->join(' as g join '.C('DB_PREFIX').'time_limited_discount_goods as d on d.goods_id=g.goods_id')->where($where)->order($order)->find();
        if (empty($info)) {
        	return 0;
        }
        $datetime = $info['end_date'] . ' ' . $info['end_time'];
        return strtotime($datetime);
    }
}
?>