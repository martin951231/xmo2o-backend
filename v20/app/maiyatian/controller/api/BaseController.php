<?php

namespace app\maiyatian\controller\api;

use think\response\Json;

class BaseController extends \app\BaseController
{
	protected function success(array $result = []): Json
	{
		$data = [
			'code' => 200,
			'message' => '成功',
		];
		if (!empty($data)) {
			$data['data'] = json_encode($result, JSON_UNESCAPED_UNICODE);
		}
		fdump_api(['api_success', 'request' => input(), 'response' =>$data], 'maiyatian/api_success'.date('H'), 1);
		return json($data);
	}

	protected function error($message): Json
	{
		fdump_api(['api_error', 'request' => input(), 'error' =>$message], 'maiyatian/api_error'.date('H'), 1);
		return json([
			'code' => 404,
			'message' => $message,
		]);
	}
}