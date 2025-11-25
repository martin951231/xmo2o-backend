<?php

namespace app\merchant\model\service;

use app\common\model\db\CardNewCoupon;
use app\common\model\db\User;
use app\common\model\service\user\UserNoticeService;
use app\job\SendMerCouponJob;
use app\merchant\model\db\CardGroup;
use app\merchant\model\db\MerchantCouponPlan;
use app\merchant\model\db\MerchantCouponPlanDate;
use app\merchant\model\db\MerchantCouponPlanSendRecords;
use Carbon\Carbon;
use think\Collection;
use think\facade\Db;
use think\facade\Queue;
use think\Paginator;

class CouponService
{
	public function groupList(int $merId): Collection
	{
		return CardGroup::where('mer_id', $merId)->field('id, name')->select();
	}

	public function couponList(int $merId): Collection
	{
		return CardNewCoupon::where([
			'mer_id' => $merId,
			'status' => 1,
		])->where('end_time', '>', time())
			->field('coupon_id, name, des, limit, 1 as send_num')
			->select();
	}

	public function savePlan(int $merId, array $params): bool
	{
		sort($params['type_day']);
		empty($params['coupon_info']) && throw_exception('请选择优惠券');
		$data = [
			'mer_id' => $merId,
			'plan_name' => $params['plan_name'],
			'start_date' => $params['start_date'],
			'end_date' => $params['end_date'],
			'type' => $params['type'],
			'type_day' => implode(',', $params['type_day']),
			'send_time' => $params['send_time'],
			'user_type' => $params['user_type'],
			'condition_type' => $params['condition_type'] ?? 0,
			'user_level' => $params['user_level'] ?? 0,
			'money_use_type' => $params['money_use_type'] ?? 0,
			'use_recent_day' => $params['use_recent_day'] ?? 0,
			'user_group_id' => $params['user_group_id'] ?? 0,
			'coupon_info' => $params['coupon_info'],
		];

		if (!empty($params['id'])) {
			$couponPlan = MerchantCouponPlan::where('id', $params['id'])->find();
			if ($couponPlan->status) {
				//throw_exception('任务已启用，不可编辑');
			}
			MerchantCouponPlanDate::where('plan_id', $couponPlan->id)->delete();
		} else {
			$couponPlan = new MerchantCouponPlan();
		}
		$userCount = $this->getPlanUser('user_count', $data);
		$data['estimate_user_count'] = $userCount;
		$data['status'] = $params['status'];
		return Db::transaction(function () use ($couponPlan, $data) {
			$couponPlan->save($data);
			$data['plan_id'] = $couponPlan['id'];
			$planDate = $this->getPlanDate($data);
			!count($planDate) && throw_exception('任务发送日期时间错误');
			(new MerchantCouponPlanDate)->saveAll($planDate);
			return true;
		});
	}

	private function getPlanDate(array $params): array
	{
		$carbonStartDate = Carbon::parse($params['start_date']);
		$carbonEndDate = Carbon::parse($params['end_date']);
		$startTime = $carbonStartDate->timestamp;
		$endTime = $carbonEndDate->timestamp;
		$planDate = [];
		$typeDay = explode(',', $params['type_day']);
		$dateArr = [];
		while ($startTime <= $endTime) {
			if ($params['type'] == MerchantCouponPlan::SEND_DATE_TYPE) {
				$date = date('d', $startTime);
			} else {
				$date = date('w', $startTime);
				if (empty($date)) {
					$date = 7;//指定7为周日
				}
			}
			$dateArr[] = $date;
			if (in_array($date, $typeDay)) {
				$planDate[] = [
					'plan_id' => $params['plan_id'],
					'plan_date' => date('Y-m-d', $startTime) . ' ' . $params['send_time'],
				];
			}
			$startTime = Carbon::parse($startTime)->addDay()->timestamp;
		}
		$dateArr = array_unique($dateArr);
		foreach ($typeDay as $day) {
			if (!in_array($day, $dateArr)) {
				throw_exception('派发周期日期选择错误');
			}
		}
		return $planDate;
	}

	public function getPlanUser(string $type, array $params)
	{
		if ($type == 'user_count') {
			if ($params['user_type'] == MerchantCouponPlan::ALL_USER) {
				return User::where('status', 1)->count();
			} else {
				[$user, $where] = $this->searchUserCondition($params);
				if ($params['condition_type'] == MerchantCouponPlan::CONDITION_TYPE_2) {
					return $user->where($where)->count('DISTINCT u.uid');
				} else {
					return $user->whereOr($where)->count('DISTINCT u.uid');
				}
			}
		} elseif ($type == 'uid') {
			if ($params['user_type'] == MerchantCouponPlan::ALL_USER) {
				$user = User::where('status', 1)->field('uid');
			} else {
				[$user, $where] = $this->searchUserCondition($params);
				if ($params['condition_type'] == MerchantCouponPlan::CONDITION_TYPE_2) {
					$user = $user->where($where)->field('DISTINCT u.uid');
				} else {
					$user = $user->whereOr($where)->field('DISTINCT u.uid');
				}
			}
			return $user;
		}
		throw_exception('获取任务用户类型错误');
	}

	public function addCouponUserToQueue()
	{
		MerchantCouponPlanDate::alias('d')
			->join('merchant_coupon_plan p', 'd.plan_id = p.id')
			->where([
				['d.plan_date', 'between', [date('Y-m-d H:i:00'), date('Y-m-d H:i:59')]],
				['d.is_execute', '=', 0],
				['p.status', '=', 1],
				['p.is_del', '=', 0],
			])
			->field('d.*')
			->chunk(10, function ($plans) {
				foreach ($plans as $plan) {
					$plan->is_execute = 1;
					$plan->save();
					$couponPlan = MerchantCouponPlan::where('id', $plan->plan_id)->find();
					$this->getPlanUser('uid', $couponPlan->toArray())->chunk(100, function ($users) use ($couponPlan) {
						foreach ($users as $user) {
							$uid = $user->uid;
							$coupons = $couponPlan->coupon_info;
							foreach ($coupons as $coupon) {
								$typeDay = $couponPlan->type == 1 ? date('d') : (date('w') ?: 7); //指定7为周日
								$record = MerchantCouponPlanSendRecords::create([
									'mer_id' => $couponPlan->mer_id,
									'plan_id' => $couponPlan->id,
									'receive_user_id' => $uid,
									'coupon_id' => $coupon['coupon_id'],
									'coupon_name' => $coupon['coupon_name'],
									'coupon_count' => $coupon['send_num'],
									'type_day' => $typeDay,
								]);
								Queue::later(random_int(1, 60), SendMerCouponJob::class, [
									'uid' => $uid,
									'coupon_id' => $coupon['coupon_id'],
									'num' => $coupon['send_num'],
									'record_id' => $record->id,
								], 'sendMerCouponJob');
								fdump_api([
									'uid' => $uid,
									'coupon_id' => $coupon['coupon_id'],
									'num' => $coupon['send_num'],
									'record_id' => $record->id,
								], 'coupon_queue/add', 1);
							}
						}
					});
					$plan->is_execute = 2;
					$plan->save();
				}
			}, 'd.id');
	}

	public function sendCoupon(array $couponData): bool
	{
		$result = invoke_cms_model('Card_new_coupon/had_pull', [
			$couponData['coupon_id'], $couponData['uid'], '', false, false, 0, false, true, true, $couponData['num'],
		]);
		fdump_api([$couponData, $result], 'cron/sendCoupon', 1);
		if ($result['retval']['error_code']) {
			MerchantCouponPlanSendRecords::where('id', $couponData['record_id'])->save([
				'status' => MerchantCouponPlanSendRecords::STATUS_SEND_FAILED,
				'send_result' => json_encode($result['retval'], JSON_UNESCAPED_UNICODE),
			]);
			throw_exception('发券失败！');
		}
		(new UserNoticeService())->addNotice([
			'type' => 9,//商家优惠券
			'business' => 'mer_coupon',
			'order_id' => $couponData['coupon_id'],
			'uid' => $couponData['uid'],
			'title' => '优惠券',
			'content' => '您有收到新的商家优惠券，请点击查看',
		]);
		return MerchantCouponPlanSendRecords::where('id', $couponData['record_id'])->save([
			'status' => MerchantCouponPlanSendRecords::STATUS_SEND_SUCCESS,
			'send_result' => json_encode($result['retval']['coupon'], JSON_UNESCAPED_UNICODE),
		]);
	}

	private function searchUserCondition(array $params): array
	{
		$user = User::alias('u');
		$where = [];
		if ($params['user_level']) {
			$where[] = ['u.level', '=', $params['user_level']];
		}

		if ($params['money_use_type'] && $params['use_recent_day']) {
			$orderWhere[] = ['o.create_time', '>', strtotime("-{$params['use_recent_day']} days")];
			if ($params['money_use_type'] == 1) {
				$orderWhere[] = ['o.id', '>', 0];
			} else {
				$orderWhere[] = ['o.id', 'NULL', ''];
			}
			$where[] = $orderWhere;
			$user = $user->leftJoin('system_order o', 'u.uid = o.uid');
		}

		if ($params['user_group_id']) {
			$where[] = ['c.gid', '=', $params['user_group_id']];
			$user = $user->leftJoin('card_userlist c', 'u.uid = c.uid');
		}
		if (empty($where)) {
			throw_exception('筛选条件不能全为空');
		}
		return [$user, $where];
	}

	public function couponSendRecord(int $merId, int $planId, array $params, $desensitizedPhone = false)
	{
		$where = [
			['r.mer_id', '=', $merId],
			['r.plan_id', '=', $planId],
		];
		if (!empty($params['nickname'])) {
			$where[] = ['u.nickname', '=', $params['nickname']];
		}
		if (!empty($params['phone'])) {
			$where[] = ['u.phone', '=', $params['phone']];
		}
		if (!empty($params['type_day'])) {
			$where[] = ['r.type_day', 'IN', $params['type_day']];
		}
		if (isset($params['status']) && $params['status'] != -1) {
			$where[] = ['r.status', '=', $params['status']];
		}
		$failReason = [
			1 => '优惠券不存在或未启用',
			2 => '优惠券已过期',
			3 => '优惠券已领完',
			4 => '优惠券仅新人可领取',
		];
		$res =  MerchantCouponPlanSendRecords::alias('r')
			->join('user u', 'r.receive_user_id = u.uid')
			->where($where)
			->field('r.*, u.nickname, u.phone')
			->order('r.create_time desc')
			->append(['status_text'])
			->paginate(min(input('pageSize', 10), 100))
			->each(function ($item)use($failReason, $desensitizedPhone) {
				$item->fail_reason = '';
				if($item->send_result) {
					$send_result = json_decode($item->send_result, true);
					if($send_result['error_code']) {
						$item->fail_reason = $failReason[$send_result['error_code']] ?? '未知错误';
					}
				}
				$desensitizedPhone && $item->phone = phone_show($item->phone);
			})
			->toArray();

		//第一页统计状态数据
		if ($res['current_page'] == 1) {
			$statistic = MerchantCouponPlanSendRecords::alias('r')
				->join('user u', 'r.receive_user_id = u.uid')
				->where($where)
				->group('r.status')
				->field('r.status, count(*) as count')
				->select()
				->toArray();
			$statistic = array_column($statistic, 'count', 'status');
			$res['statistic'] = [
				[
					'title' => '总数量',
					'count' => array_sum($statistic)
				],
				[
					'title' => '未发放数量',
					'count' => $statistic[MerchantCouponPlanSendRecords::STATUS_UN_SEND] ?? 0
				],
				[
					'title' => '已发放数量',
					'count' => $statistic[MerchantCouponPlanSendRecords::STATUS_SEND_SUCCESS] ?? 0
				],
				[
					'title' => '发放失败数量',
					'count' => $statistic[MerchantCouponPlanSendRecords::STATUS_SEND_FAILED] ?? 0
				],
			];
		}
		return $res;
	}

	public function planList(int $merId, array $params): Paginator
	{
		return MerchantCouponPlan::where('mer_id', $merId)
			->withSearch(['plan_name', 'start_date', 'end_date'], $params)
			->where('is_del', 0)
			->order('id DESC')
			->paginate(min(input('pageSize', 10), 100));
	}

	public function changePlanStatus(int $merId, int $id): bool
	{
		$couponPlan = MerchantCouponPlan::where('id', $id)->where('mer_id', $merId)->find();
		empty($couponPlan) && throw_exception('任务不存在');

		if (time() >= strtotime($couponPlan->start_date) && $couponPlan->status) {
			//throw_exception('任务已开始，不可关闭');
		}
		$couponPlan->status = $couponPlan->status ? 0 : 1;
		return $couponPlan->save();
	}

	public function deletePlan(int $merId, int $id): bool
	{
		$couponPlan = MerchantCouponPlan::where('id', $id)->where('mer_id', $merId)->find();
		empty($couponPlan) && throw_exception('任务不存在');

		if ($couponPlan->status) {
			//throw_exception('任务已启用，不可删除');
		}
		$couponPlan->is_del = 1;
		return $couponPlan->save();
	}

}