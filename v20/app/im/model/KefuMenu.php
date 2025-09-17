<?php

declare(strict_types=1);

namespace app\im\model;

use think\facade\Db;
use think\Model;

/**
 * @mixin \think\Model
 */
class KefuMenu extends Model
{
    const  SEARCH_ORDERS = 'search_orders';

    const GUESS_YOU_LIKE = 'guess_you_like';

    const COUPON = 'coupon';

    const SYSTEM_COUPON = 'system_coupon';

    const LINK = 'link';

    /**
     * 获取平台优惠券
     */
    public function getSystemCouponByIds($couponIds)
    {
        if (empty($couponIds)) {
            return array();
        }
        $tm = time();
        $where = array(
            array('start_time', '<', $tm),
            array('end_time', '>', $tm),
            array('status', '=', 1),
            array('coupon_id', 'in', $couponIds)
        );
        $field = "coupon_id, name, discount,order_money,FROM_UNIXTIME(start_time,'%Y-%m-%d') AS start_time,FROM_UNIXTIME(end_time,'%Y-%m-%d')end_time";
        return Db::name('system_coupon')->where($where)->field($field)->select()->toArray();
    }

    public function getCouponByIds($couponIds)
    {
        $coupons = invoke_cms_model('Card_new_coupon/getCouponInfoByIds', [$couponIds]);
        return $coupons['retval'] ?? array();
    }

    /**
     * 猜您喜欢
     * 规则：目前仅做商城、外卖两个入口，根据商品分类获取
     */
    public function guessYouLike($storeId, $goodId = 0, $from = '')
    {
        if ($storeId < 1) {
            return array();
        }
        $goodMod = Db::name('shop_goods');

        $where = array(
            array('store_id', '=', $storeId),
        );

        if ($goodId) {
            $detail = $goodMod->where('goods_id', $goodId)->field('goods_id,sort_id,store_id,goods_type,cat_fid,cat_id')->find();
            if ($detail) {
                $where[] = array('goods_id', '<>', $goodId);
                if ($from == 'mall') {
                    $where[] = array('cat_fid', '=', $detail->cat_fid);
                    $where[] = array('goods_type', 'in', array(0, 1));
                } else if ($from == 'meal') {
                    $where[] = array('sort_id', '=', $detail->sort_id);
                    $where[] = array('goods_type', '=', 0);
                }
            }
        }

        $goods = $goodMod->removeOption('where')->where($where)
            ->field('goods_id AS id,store_id,`name`,price,image,sell_count AS sale_count,goods_type')
            ->order(Db::raw('RAND()'))
            ->limit(3)
            ->select()
            ->toArray();

        foreach ($goods as &$good) {
            $images = explode(';', $good['image']);
            $good['image'] = replace_file_domain($images[0] ?? '');
            $good['detail_url'] = get_base_url('pages/shop_new/goodsDetail/goodsDetail?store_id=' . $good['store_id'] . '&product_id=' . $good['id']);
        }
        return $goods;
    }

    /**
     * 入口（商城、外卖、团购、其他）分别获取对应订单最近一条
     */
    public function findLastPayOrder($storeId, $uid, $from = '')
    {
        $where = array(
            'o.uid' => $uid,
            'o.paid' => 1,
            'o.is_del' => 0
        );
        if ($storeId > 0) {
            $where['o.store_id'] = $storeId;
        }

        $order = Db::name('shop_order')->alias('o')
            ->join('shop_order_detail d ', 'o.order_id = d.order_id')
            ->where($where)
            ->field('o.order_id,o.real_orderid,o.order_from,d.name AS goods_name,d.price AS goods_price,d.num AS buy_numer,d.goods_image')
            ->order('o.order_id DESC')
            ->find();
        if ($order) {
            $order['order_detail_url'] = get_base_url('pages/shop_new/orderDetail/orderDetail?order_id=' . $order['order_id']);
            $order['order_list_url'] = get_base_url('pages/my/my_order');
        }
        return $order ?: array();
    }
}
