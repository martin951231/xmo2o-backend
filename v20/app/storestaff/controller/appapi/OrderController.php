<?php

namespace app\storestaff\controller\appapi;


class OrderController extends ApiBaseController
{
    /**
     * 订单列表
     *
     * @author: 张涛
     * @date: 2020/11/03
     */
    public function pendingLists()
    {
        


        $rs = array (
            0 => 
            array (
              'order_id' => 1,
              'goods' => 
              array (
                0 => 
                array (
                  'goods_id' => 2,
                  'name' => '力力测试商品',
                  'spec' => '规格',
                  'num' => 1,
                  'price' => 25.3,
                  'is_give' => true,
                  'is_exchange' => false,
                  'is_seckill' => false,
                  'is_discount' => false,
                  'refund_num' => 0,
                  'subsidiary_goods' => 
                  array (
                    0 => 
                    array (
                      'goods_id' => 3,
                      'name' => '我是附属菜',
                      'spec' => '规格',
                      'num' => 1,
                      'price' => 0.01,
                    ),
                  ),
                ),
              ),
              'order' => 
              array (
                'order_id' => 1,
                'real_orderid' => '20110303422511235877',
                'goods_count_' => 2,
                'goods_type_count' => 2,
                'total_price' => 50.6,
                'packing_charge' => 0,
                'freight_charge' => 0,
                'create_time' => '2020-11-03 19:15:56',
                'real_price' => 50.6,
                'expect_income' => 50.6,
                'merchant_activity_expend' => 50.6,
                'plat_service_charge' => 50.6,
                'refund_price' => 0,
                'username' => '收件人',
                'userphone' => '收件人手机号',
                'address' => '亚夏大厦17F',
                'is_pick_in_store' => 1,
                'is_booking' => true,
                'expect_use_time' => 1604402156,
                'expect_use_show_time' => '19:15',
                'fetch_number' => 101,
                'paid' => 1,
                'note' => '我是订单备注',
                'status' => 1,
                'status_txt' => '已支付',
              ),
              'deliver_user' => 
              array (
                'uid' => 41,
                'name' => '张涛',
                'phone' => '15521092789',
              ),
              'discount' => 
              array (
                0 => 
                array (
                  'name' => '店铺优惠满10减5',
                  'value' => 5,
                ),
                1 => 
                array (
                  'name' => '会员卡8折',
                  'value' => 10,
                ),
              ),
              'btn_bar' => 
              array (
                'take_order' => true,
                'cancel_order' => true,
                'print_order' => true,
                'delivery_order' => true,
                'agree_refund' => true,
                'disagree_refund' => true,
                'handle_book_order' => true,
              ),
            ),
        );
        return api_output(0, $rs, '成功');
    }

    /**
     * 待处理状态标签
     * @author: 张涛
     * @date: 2020/11/4
     */
    public function pendingStatus()
    {
        $rs = [
            ['title' => '进行中', 'count' => 1, 'status' => 'progress'],
            ['title' => '新订单', 'count' => 1, 'status' => 'new'],
            ['title' => '退款单', 'count' => 1, 'status' => 'refund'],
        ];
        return api_output(0, $rs, '成功');
    }

    /**
     * 订单管理订单列表
     * @author: 张涛
     * @date: 2020/11/4
     */
    public function lists()
    {
        return $this->pendingLists();
    }

    /**
     * 订单管理-订单状态
     * @author: 张涛
     * @date: 2020/11/4
     */
    public function status()
    {
        $rs = [
            ['title' => '全部', 'status' => 'all'],
            ['title' => '完成单', 'status' => 'complete'],
            ['title' => '取消单', 'status' => 'cancal'],
            ['title' => '配送单', 'status' => 'delivery'],
            ['title' => '预订单', 'status' => 'booking'],
        ];
        return api_output(0, $rs, '成功');
    }

    public function newOrder()
    {
        $rs = [
            'count' => 5
        ];
        return api_output(0, $rs, '成功');
    }
}
