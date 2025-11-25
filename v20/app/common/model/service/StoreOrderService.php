<?php
/**
 * 店铺订单
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 11:32
 */

namespace app\common\model\service;

use app\common\model\db\MerchantStore;
use app\common\model\db\StoreOrder;
use app\internal_card\model\db\InternalMembershipCardLog;
use app\internal_card\model\service\InternalCardMoneyBillService;
use Carbon\Carbon;

class StoreOrderService
{
    public $storeOrderObj = null;
    public function __construct()
    {
        $this->storeOrderObj = new StoreOrder();
    }

    /**
     * 获取店铺订单信息
     * User: chenxiang
     * Date: 2020/6/1 11:40
     * @param string $field
     * @param array $where
     * @param string $group
     * @return mixed
     */
    public function getList($field = true, $where = [], $group = '') {
        $result = $this->storeOrderObj->getList($field, $where, $group);
        return $result;
    }

    /**
     * 更新某个字段的值
     * User: chenxiang
     * Date: 2020/6/1 16:22
     * @param array $where
     * @param string $field
     * @param string $value
     * @return bool|mixed
     */
    public function setField($where = [], $field = '', $value = '') {
        if(empty($field)) {
            return false;
        }
        $result = $this->storeOrderObj->setField($where, $field, $value);
        return $result;
    }

    public function merchantStoreStatistic($storeId, $date): bool
    {
        $time = Carbon::parse($date);
        $startTime = $time->startOfDay()->timestamp;
        $endTime = $time->endOfDay()->timestamp;
        //总支付金额，平台余额支付金额， 支付订单数量
        $payMoney = StoreOrder::where([
            ['store_id', '=', $storeId],
            ['paid', '=', 1],
            ['pay_time', '>=', $startTime],
            ['pay_time', '<=', $endTime],
        ])
            ->field(
                "SUM(price) pay_money,
                SUM(CASE WHEN pay_type = 'offline' THEN price ELSE 0 END) offline_pay_money,
                SUM(balance_pay) balance_pay_money,
                count(*) cnt"
            )
            ->find();
        //支付宝支付金额
        $aliPayMoney = StoreOrder::alias('o')
            ->join('payment_money_order p', "o.order_id = p.order_id AND p.order_type = 'store'")
            ->where([
                ['o.store_id', '=', $storeId],
                ['o.paid', '=', 1],
                ['o.pay_time', '>=', $startTime],
                ['o.pay_time', '<=', $endTime],
                ['p.pay_type', 'IN', 'alipayh5, alipay'],
            ])
            ->field('SUM(p.paid_money) pay_money')
            ->find();
        //微信支付金额
        $weixinPayMoney = StoreOrder::alias('o')
            ->join('payment_money_order p', "o.order_id = p.order_id AND p.order_type = 'store'")
            ->where([
                ['o.store_id', '=', $storeId],
                ['o.paid', '=', 1],
                ['o.pay_time', '>=', $startTime],
                ['o.pay_time', '<=', $endTime],
                ['p.pay_type', 'IN', 'weixinh5, weixin'],
            ])
            ->field('SUM(p.paid_money) pay_money')
            ->find();
        //一卡通支付金额
        $cardMoney = InternalMembershipCardLog::where([
            ['store_id', '=', $storeId],
            ['operation_type', '=', 2],//支付
            ['business_type', 'IN', 'store'],
            ['create_time', '>=', $startTime],
            ['create_time', '<=', $endTime],
        ])
            ->field('SUM(money) pay_money')
            ->find();

        $allPayMoney =
            $cardMoney['pay_money'] +
            $payMoney['balance_pay_money'] +
            $payMoney['offline_pay_money'] +
            $aliPayMoney['pay_money'] +
            $weixinPayMoney['pay_money'];

        $data = [
            'mer_id' => MerchantStore::where(['store_id' => $storeId, 'status' => 1])->value('mer_id'),
            'store_id' => $storeId,
            'order_type' => 'store',
            'bill_date' => $date,
            'pay_order_count' => $payMoney['cnt'],
            'pay_money' => bcsub($allPayMoney, $payMoney['refund_money'], 2),
            'offline_pay_money' => $cardMoney['offline_pay_money'] ?: 0,
            'internal_card_pay_money' => $cardMoney['pay_money'] ?: 0,
            'balance_pay_money' => $payMoney['balance_pay_money'] ?: 0,
            'alipay_pay_money' => $aliPayMoney['pay_money'] ?: 0,
            'wechat_pay_money' => $weixinPayMoney['pay_money'] ?: 0,
            'refund_money' => $payMoney['refund_money'] ?? 0,
        ];

        return (new InternalCardMoneyBillService())->saveMerchantStoreDateBill($data);
    }
}