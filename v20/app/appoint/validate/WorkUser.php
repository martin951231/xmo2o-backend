<?php

declare (strict_types=1);

namespace app\appoint\validate;

use think\Validate;

class WorkUser extends Validate
{
	protected $rule = [
		'account|账号'           => 'require',
		'password|密码'          => 'require',
		'old_password|旧密码'    => 'require',
		'supply_id|服务ID'       => 'require',
		'lat|纬度'               => 'require',
		'lng|经度'               => 'require',
		'start_time|开始时间'    => 'require',
		'end_time|结束时间'      => 'require',
		'worker_id|服务人员ID'   => 'require',
		'status|状态'            => 'require|in:0,2,3,4,5',
		'type|类型'              => 'require|in:1,2,3',
	];

	protected $message = [

	];

	protected $scene = [
		'login'               => ['account', 'password'],
		'appoint_supply_list' => ['status'],
		'complete_order'      => ['supply_id'],
		'service_detail'      => ['supply_id'],
		'grap_order'          => ['supply_id'],
		'one_click_help'      => ['lat', 'lng'],
		'statistics'          => ['start_time', 'end_time'],
		'change_pwd'          => ['old_password', 'password'],
		'rank_list'           => ['type'],
		'api_worker_appoint_list' => ['worker_id'],
	];


}
