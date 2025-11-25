<?php

namespace app\job;

use app\merchant\model\service\CouponService;
use think\queue\Job;

class SendMerCouponJob
{
	public function fire(Job $job, $data)
	{
		try {
			if ($job->attempts() > 3) {
				$job->delete();
			}
			/*
			 *  $data = [
					'uid' => $uid,
					'coupon_id' => $couponId,
					'num' => $num,
					'record_id' => $recordId,
				];
			 * */
			fdump_api(['job_queue_data' => $data], 'job/SendMerCouponJob', 1);
			(new CouponService())->sendCoupon($data);
			$job->delete();
		} catch (\Exception $e) {
			fdump_api([$data, $e->getMessage()], 'job/SendMerCouponJob', 1);
			$job->release(3); //$delay为延迟时间
		}
	}

	public function failed($data)
	{
		fdump_api(['SendMerCouponJobFailed' => $data], 'job/SendMerCouponJobFailed', 1);
	}
}