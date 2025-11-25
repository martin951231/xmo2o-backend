<?php

class Maiyatian_deliveryModel extends Model
{
	public function getOrderSource($orderId)
	{
		$source = $this->alias('d')
			->join(C('DB_PREFIX') . 'maiyatian_order o ON d.order_id = o.id')
			->where(['d.publish_id' => $orderId])
			->getField('o.source');
		$sourceText = [
			'other' => '其他',
			'eleme' => '饿了么',
			'meituan' => '美团',
			'douyin' => '抖音',
			'youzan' => '有赞',
			'daojia' => '京东到家',
		];
		return $sourceText[$source] ?? $source;
	}

}
