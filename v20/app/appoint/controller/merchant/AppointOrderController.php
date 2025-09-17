<?php

namespace app\appoint\controller\merchant;

use app\appoint\model\service\AppointOrderService;
use app\merchant\controller\merchant\AuthBaseController;
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
		$params['mer_id'] = $this->merId;
		$data = (new AppointOrderService())->orderRecord($params['order_id'], $params);
		return api_output(self::STATUS_OK, $data);
	}
}