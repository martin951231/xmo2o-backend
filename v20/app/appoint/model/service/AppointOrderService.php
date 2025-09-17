<?php

declare (strict_types=1);

namespace app\appoint\model\service;

use app\appoint\model\db\AppointOrderRecord;
use app\appoint\model\db\AppointOrderRecords;
use app\appoint\model\db\AppointSupply;
use app\common\model\db\AppointOrder;
use app\common\model\db\AppointOrderRefund;
use app\common\model\db\MerchantStore;
use app\internal_card\model\db\InternalMembershipCardLog;
use app\internal_card\model\service\InternalCardMoneyBillService;
use Carbon\Carbon;
use think\Collection;

class AppointOrderService
{
    public function workerAppointCount($workerId, $startTimeAppoint, $endTimeAppoint)
    {
        $appointOrderIdArr = AppointSupply::where(['worker_id' => $workerId])
            ->where('start_time','BETWEEN', [strtotime(date('Y-m-d',$startTimeAppoint)), $endTimeAppoint])
            ->column('order_id');
        $result = [];
        if($appointOrderIdArr){
            $result = AppointOrder::where([
                    'type' => 0,
                    'service_status' => 0,
                    'is_del' => 0,
                ])
                ->where('appoint_date', '>=',  date('Y-m-d'))
                ->where('paid', 1)
                ->where('order_id', 'IN',  $appointOrderIdArr)
                ->field('count(*) as appoint_count, appoint_date, appoint_time')
                ->group('appoint_date,appoint_time')
                ->select();
        }
        return $result;
    }

    public function merchantStoreStatistic($storeId, $date)
    {
        $time = Carbon::parse($date);
        $startTime = $time->startOfDay()->timestamp;
        $endTime = $time->endOfDay()->timestamp;
        //总支付金额，平台余额支付金额， 支付订单数量
        $payMoney = AppointOrder::where([
            ['store_id', '=', $storeId],
            ['paid', '=', 1],
            ['pay_time', '>=', $startTime],
            ['pay_time', '<=', $endTime],
        ])
            ->field(
                'SUM(pay_money) pay_money,
                SUM(balance_pay + product_balance_pay) balance_pay_money,
                count(*) cnt'
            )
            ->find();

        $refund = AppointOrderRefund::alias('or')
            ->join('appoint_order o', 'o.order_id = or.order_id')
            ->where([
                ['o.store_id', '=', $storeId],
                ['o.paid', '=', 1],
                ['or.status', '=', 1],
                ['or.applytime', '>=', $startTime],
                ['or.applytime', '<=', $endTime],
            ])
            ->field(
                'SUM(or.price) refund_money'
            )
            ->find();
        $payMoney['refund_money'] = $refund['refund_money'];
        //支付宝支付金额
        $aliPayMoney = AppointOrder::alias('o')
            ->join('payment_money_order p', "o.order_id = p.order_id AND p.order_type = 'appoint'")
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
        $weixinPayMoney = AppointOrder::alias('o')
            ->join('payment_money_order p', "o.order_id = p.order_id AND p.order_type = 'appoint'")
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
            ['business_type', 'IN', 'appoint'],
            ['create_time', '>=', $startTime],
            ['create_time', '<=', $endTime],
        ])
            ->field('SUM(money) pay_money')
            ->find();

        $allPayMoney = bcsub(
            $cardMoney['pay_money'] + $payMoney['balance_pay_money'] + $aliPayMoney['pay_money'] + $weixinPayMoney['pay_money'],
            $payMoney['refund_money'],
            2);
        
        $data = [
            'mer_id' => MerchantStore::where(['store_id' => $storeId, 'status' => 1])->value('mer_id'),
            'store_id' => $storeId,
            'order_type' => 'appoint',
            'bill_date' => $date,
            'pay_money' => $allPayMoney,
            'pay_order_count' => $payMoney['cnt'],
            'internal_card_pay_money' => $cardMoney['pay_money'] ?: 0,
            'balance_pay_money' => $payMoney['balance_pay_money'] ?: 0,
            'alipay_pay_money' => $aliPayMoney['pay_money'] ?: 0,
            'wechat_pay_money' => $weixinPayMoney['pay_money'] ?: 0,
            'refund_money' => $payMoney['refund_money'] ?: 0,
        ];

        return (new InternalCardMoneyBillService())->saveMerchantStoreDateBill($data);
    }

	public function orderRecord(int $orderId, $params = []): Collection
	{
		$where[] = ['order_id','=', $orderId];
		if(!empty($params['mer_id'])) {
			$where[] = ['mer_id','=', $params['mer_id']];
		}
		return AppointOrderRecords::where($where)->order('id DESC')->select();
	}

	public function addOrderRecord(int $merId, int $orderId, string $title, array $params)
	{
		foreach ($params['content'] as $v) {
			validate([
				'title'  => 'require',
				'value' => 'require',
				'type'  => 'require',
			])->check($v);
		}
		return AppointOrderRecords::create([
			'mer_id'   => $merId,
			'order_id' => $orderId,
			'title'    => $title,
			'content'  => $params['content'],
		]);
	}
}