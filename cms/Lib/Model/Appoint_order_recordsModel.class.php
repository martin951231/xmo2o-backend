<?php

class Appoint_order_recordsModel extends Model 
{
	public function addOrderRecord(int $merId, int $orderId, string $title, array $params)
	{
		return $this->add([
			'mer_id'   => $merId,
			'order_id' => $orderId,
			'title'    => $title,
			'content'  => json_encode($params['content'], JSON_UNESCAPED_UNICODE),
		]);
	}
}