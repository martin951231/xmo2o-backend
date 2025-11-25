<?php

declare (strict_types=1);

namespace app\appoint\model\service;

use app\appoint\model\db\AppointOrderRecord;
use app\appoint\model\db\AppointOrderRecords;
use app\appoint\model\db\AppointSupply;
use app\appoint\model\db\MerchantWorkers;
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
	private function getAppointDate(array $params): string
	{
		return empty($params['appoint_date'])
			? Carbon::now()->toDateString()
			: Carbon::parse($params['appoint_date'])->toDateString();
	}

	private function buildWorkerSearchWhere(array $params): array
	{
		$where = [];
		if (!empty($params['worker_name'])) {
			$where[] = ['name', 'like', "%{$params['worker_name']}%"];
		}
		return $where;
	}

	private function buildAppointWhereClause(array $params): array
	{
		$where = [];
		// 处理用户名称/电话搜索
		if (!empty($params['appoint_user_name'])) {
			if (is_numeric($params['appoint_user_name'])) {
				$where[] = ['u.phone', '=', $params['appoint_user_name']];
			} else {
				$where[] = ['u.nickname', 'like', "%{$params['appoint_user_name']}%"];
			}
		}
		if (isset($params['appoint_type']) && $params['appoint_type'] > -1) {
			$where[] = ['o.appoint_type', '=', $params['appoint_type']];
		}

		return $where;
	}
	
	private function getAppointCounts(array $workerIds, string $appointDate, array $params): array
	{
		$where = $this->buildAppointWhereClause($params);
		$counts = AppointOrder::alias('o')
			->join('user u', 'o.uid = u.uid')
			->join('appoint_supply s', 'o.order_id = s.order_id')
			->where($where)
			->where([
				['o.is_del', '=', 0],
				['o.appoint_date', '=', $appointDate],
				['o.merchant_worker_id', 'in', $workerIds],
			])
			->field('o.merchant_worker_id, count(*) as appoint_count')
			->group('o.merchant_worker_id')
			->select()
			->toArray();
		
		return array_column($counts, 'appoint_count', 'merchant_worker_id');
	}
	
	public function orderWorker(int $merId, array $params): array
	{
		$appointDate = $this->getAppointDate($params);
		$where = $this->buildWorkerSearchWhere($params);

		$workers = MerchantWorkers::where([
			'mer_id' => $merId,
			'status' => 1,
		])
			->where($where)
			->field('merchant_worker_id, name, office_time, before_time, time_gap')
			->select()
			->toArray();
		
		if (empty($workers)) {
			return [];
		}

		$workerIds = array_column($workers, 'merchant_worker_id');
		$appointCounts = $this->getAppointCounts($workerIds, $appointDate, $params);
		// 关联预约数量到服务人员
		foreach ($workers as &$worker) {
			$worker['appoint_order_count'] = $appointCounts[$worker['merchant_worker_id']] ?? 0;
		}

		return $workers;
	}
	
	public function workerOrder(int $merId, array $params)
	{
		$appointDate = $this->getAppointDate($params);
		
		$worker = MerchantWorkers::where([
			'mer_id' => $merId,
			'merchant_worker_id' => $params['merchant_worker_id'],
		])
			->field('merchant_worker_id, name, office_time, before_time, time_gap')
			->findOrEmpty()
			->toArray();
		
		if(empty($worker)) {
			throw_exception('服务人员信息不存在');
		}
		$worker['work_time'] = (new MerchantWorkService())->workTime($worker, false, ['now_date_time' => true]);
		$where = $this->buildAppointWhereClause($params);
		foreach ($worker['work_time'] as &$v) {
			$order = AppointOrder::alias('o')
				->where([
					['o.is_del', '=', 0],
					['o.appoint_time' ,'>=', $v['start']],
					['o.appoint_time' ,'<', $v['end']],
					['o.merchant_worker_id','=',$worker['merchant_worker_id']],
				])
				->where($where)
				->join('user u', 'o.uid = u.uid')
				->join('appoint_supply s', 'o.order_id = s.order_id')
				->append(['appoint_type_text', 'service_status_text'])
				->field(
					'o.uid, o.order_id, appoint_type, order_name, product_name, u.phone, nickname, truename, 
					service_status, push_position_info'
				)
				->where('appoint_date', '=', $appointDate)
				->select()
				->each(function ($item){
					$item->push_position_text = $item->push_position_info ? '已打卡' : '未打卡';
				})
				->toArray();
			$v['order'] = $order ?: [];
		}
		unset($worker['office_time']);
		return $worker;
	}
}