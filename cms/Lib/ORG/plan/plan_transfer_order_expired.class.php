<?php
class plan_transfer_order_expired extends plan_base
{

	public function runTask()
	{
		$tm = time();
		$expireSecond = 180;
		$where = [
			'transfer_status' => 0,
			'transfer_time' => ['LT', $tm - $expireSecond]
		];

		$update = [
			'transfer_from_uid' => 0,
			'transfer_deliver_status' => 0,
			'transfer_to_uid' => 0,
			'transfer_accept_time' => 0,
			'transfer_refuse_time' => 0,
			'transfer_status' => 0,
			'transfer_time' => 0
		];
		D('Deliver_supply')->where($where)->save($update);
	}
}
