<?php

namespace app\appoint\controller\platform;

use app\appoint\model\service\AppointOrderService;
use app\common\controller\platform\AuthBaseController;
use think\response\Json;

class AppointOrderController extends AuthBaseController
{
	const STATUS_OK = 1000;//状态码 正常

	public function orderRecord(): Json
	{
		$params = input();
		$this->validate($params, [
			'order_id|订单ID' => 'require'
		]);
		$data = (new AppointOrderService())->orderRecord($params['order_id']);
		return api_output(self::STATUS_OK, $data);
	}
}