<?php

class Kefu_menuModel extends Model
{
    const  SEARCH_ORDERS = 'search_orders';

    const GUESS_YOU_LIKE = 'guess_you_like';

    const COUPON = 'coupon';

    const SYSTEM_COUPON = 'system_coupon';

    const LINK = 'link';

    public function getTypeDesc($type = '', $returnAll = false, $isPlatKf = false, $isVillage = false)
    {
        if ($isPlatKf) {
            $desc = array(
                self::SEARCH_ORDERS => '订单查询',
                self::SYSTEM_COUPON => '优惠券',
                self::LINK => '自定义链接'
            );
        }else if($isVillage){
            $desc = array(
                self::LINK => '自定义链接'
            );
        } else {
            $desc = array(
                self::SEARCH_ORDERS => '订单查询',
                self::GUESS_YOU_LIKE => '猜您喜欢',
                self::COUPON => '优惠券',
                self::LINK => '自定义链接'
            );
        }

        if ($returnAll) {
            return $desc;
        } else {
            return isset($desc[$type]) ? $desc[$type] : '未知';
        }
    }

    public function checkType($type)
    {
        return array($type, array(self::SEARCH_ORDERS, self::GUESS_YOU_LIKE, self::COUPON, self::LINK, self::SYSTEM_COUPON));
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
        if ($from == 'mall') {
            $where['o.order_from'] = 1;
        } else if ($from == 'meal') {
            $where['o.order_from'] = array('NEQ', 1);
        }

        $order = M("Shop_order")->alias('o')
            ->join(C('DB_PREFIX') . 'shop_order_detail AS d ON o.order_id = d.order_id')
            ->where($where)
            ->field('o.order_id,o.real_orderid,o.order_from,d.name AS goods_name,d.price AS goods_price,d.num AS buy_numer,d.goods_image')
            ->order('o.order_id DESC')
            ->find();
        if ($order) {
            //如果缺少from参数，则根据订单判断
            if (!$from) {
                $from = $order['order_from'] == 1 ? 'mall' : 'meal';
            }
            $order['order_detail_url'] = get_order_detail_url($order['order_id'], $from);
            $order['order_list_url'] = get_order_list_url($from);
        }
        return $order ?: array();
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
        $goodMod = M('Shop_goods');
        $detail = $goodMod->field('goods_id,sort_id,store_id,goods_type,cat_fid,cat_id')->find($goodId);
        $goods = array();
        $where = array('store_id' => $storeId, 'is_del' => 0);
        if ($detail) {
            //有商品则按照商品分类取数据
            $where['goods_id'] = array('NEQ', $goodId);
            if ($from == 'mall') {
                $where['cat_fid'] = $detail['cat_fid'];
                $where['goods_type'] = array('IN', array(0, 1));
            } else if ($from == 'meal') {
                $where['sort_id'] = $detail['sort_id'];
                $where['goods_type'] = 0;
            }
        }
        $goods = $goodMod->where($where)
            ->field('goods_id AS id,store_id,`name`,price,image,sell_count AS sale_count,goods_type')
            ->order('sell_count DESC')
            ->limit(3)
            ->select();
        $goodsImage = new goods_image();
        foreach ($goods as &$good) {
            $images = explode(';', $good['image']);
            if ($images[0]) {
                $good['image'] = $goodsImage->get_image_by_path($images[0], 0);
            }
            if(!$from){
                $from = 'mall';
            }
            if ($from == 'mall') {
                $good['detail_url'] = C('config.site_url') . '/wap.php?g=Wap&c=Mall&a=detail&goods_id=' . $good['id'];
            } else {
                $good['detail_url'] = C('config.site_url') . '/wap.php?c=Shop&a=classic_good&shop_id=' . $good['store_id'] . '&good_id=' . $good['id'];
            }
        }
        return $goods;
    }

    public function getCouponByIds($couponIds)
    {
        $coupons = PD('Card_new_coupon')->getCouponInfoByIds($couponIds);
        return $coupons ?: array();
    }


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
            'start_time' => array('lt', $tm),
            'end_time' => array('gt', $tm),
            'status' => 1,
            'coupon_id' => array('IN', $couponIds)
        );
        $field = "coupon_id, name, discount,order_money,FROM_UNIXTIME(start_time,'%Y-%m-%d') AS start_time,FROM_UNIXTIME(end_time,'%Y-%m-%d')end_time";
        return M('System_coupon')->where($where)->field($field)->select();
    }
}