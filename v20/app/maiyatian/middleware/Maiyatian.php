<?php
declare (strict_types = 1);

namespace app\maiyatian\middleware;

use app\maiyatian\service\MaiyatianService;

class Maiyatian
{
	public function handle($request, \Closure $next)
	{
		$params = $request->param();
		fdump_api(['middleware', $params], 'MaiyatianCallBack/'.input('command').date('H'), 1);
		$signatureData = (new MaiyatianService())->signatureData($params['data'], $params['command'], $params['token'], (int)$params['timestamp']);
		if($signatureData['signature'] !== $params['signature']) {
			fdump_api([$signatureData, $params], 'maiyatian/signature_error', 1);
			return json(['code' => 401,'msg' => '签名验证失败']);
		}
		return $next($request);
	}
}
