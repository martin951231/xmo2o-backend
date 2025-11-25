<?php

namespace app\maiyatian\controller\api;

use app\maiyatian\middleware\Maiyatian;
use app\maiyatian\model\MaiyatianToken;
use app\maiyatian\service\MaiyatianCallBackService;
use app\maiyatian\service\MaiyatianService;
use think\response\Json;

class MaiyatianController extends BaseController
{
	protected $middleware = [Maiyatian::class];
	protected $maiyatianCallBackService;

	protected $maiyatianData;
	public function __construct()
	{
		parent::__construct(app());
		$this->maiyatianCallBackService = new MaiyatianCallBackService();
		$this->maiyatianData = json_decode(input('data'), true);
		if (empty($this->maiyatianData)) {
			return $this->error('数据为空');
		}
		fdump_api([input('command'), json_encode($this->maiyatianData, JSON_UNESCAPED_UNICODE)], 'MaiyatianCallBack/data'.date('H'), true);
		if ($shopId = $this->maiyatianData['shop_id']) {
			MaiyatianCallBackService::$merId = MaiyatianToken::where('shop_id', $shopId)->value('mer_id');
			MaiyatianService::$merId = MaiyatianCallBackService::$merId;
		}
	}

	public function valuating(): Json
	{
		try {
			$data = $this->maiyatianCallBackService->valuating($this->maiyatianData);
			$data['pay_amount'] = (int) bcadd($data['pay_amount'], $this->maiyatianData['tips']);
		} catch (\Exception $e) {
			fdump_api([input(), $e->getMessage(), $e->getTraceAsString()], 'MaiyatianCallBack/valuatingError', true);
			return $this->error($e->getMessage());
		}
		return $this->success($data);
	}

	public function send(): Json
	{
		try {
			$data = $this->maiyatianCallBackService->send($this->maiyatianData);
			$data['pay_amount'] = (int) bcadd($data['pay_amount'], $this->maiyatianData['tips']);
		} catch (\Exception $e) {
			fdump_api([input(), $e->getMessage(), $e->getTraceAsString()], 'MaiyatianCallBack/sendError', true);
			return $this->error($e->getMessage());
		}
		return $this->success($data);
	}

	public function tips(): Json
	{
		try {
			$data = $this->maiyatianCallBackService->tips($this->maiyatianData);
		} catch (\Exception $e) {
			fdump_api([input(), $e->getTraceAsString()], 'MaiyatianCallBack/tipsError', true);
			return $this->error($e->getMessage());
		}
		return $this->success($data);
	}

	public function cancel(): Json
	{
		try {
			$data = $this->maiyatianCallBackService->cancel($this->maiyatianData);
		} catch (\Exception $e) {
			fdump_api([input(), $e->getTraceAsString()], 'MaiyatianCallBack/cancelError', true);
			return $this->error($e->getMessage());
		}
		return $this->success($data);
	}

	public function queryInfo(): Json
	{
		try {
			$data = $this->maiyatianCallBackService->queryInfo($this->maiyatianData);
		} catch (\Exception $e) {
			fdump_api([input(), $e->getTraceAsString()], 'MaiyatianCallBack/queryInfoError', true);
			return $this->error($e->getMessage());
		}
		return $this->success($data);
	}

	public function riderLocation(): Json
	{
		try {
			$data = $this->maiyatianCallBackService->riderLocation($this->maiyatianData);
		} catch (\Exception $e) {
			fdump_api([input(), $e->getTraceAsString()], 'MaiyatianCallBack/riderLocationError', true);
			return $this->error($e->getMessage());
		}
		return $this->success($data);
	}

	public function multiRiderLocation(): Json
	{
		try {
			$data = $this->maiyatianCallBackService->multiRiderLocation($this->maiyatianData);
		} catch (\Exception $e) {
			fdump_api([input(), $e->getTraceAsString()], 'MaiyatianCallBack/multiRiderLocationError', true);
			return $this->error($e->getMessage());
		}
		return $this->success($data);
	}

	public function tokenUnbind(): Json
	{
		try {
			$data = $this->maiyatianCallBackService->tokenUnbind($this->maiyatianData);
			if (!$data) {
				return $this->error('解绑失败');
			}
		} catch (\Exception $e) {
			fdump_api([input(), $e->getTraceAsString()], 'MaiyatianCallBack/tokenUnbindError', true);
			return $this->error($e->getMessage());
		}
		return $this->success();
	}

	public function balance(): Json
	{
		try {
			$data = $this->maiyatianCallBackService->balance();
		} catch (\Exception $e) {
			fdump_api([input(), $e->getTraceAsString()], 'MaiyatianCallBack/balanceError', true);
			return $this->error($e->getMessage());
		}
		return $this->success($data);
	}

	public function rechargeUrl(): Json
	{
		try {
			$data = $this->maiyatianCallBackService->rechargeUrl($this->maiyatianData);
		} catch (\Exception $e) {
			fdump_api([input(), $e->getTraceAsString()], 'MaiyatianCallBack/rechargeUrlError', true);
			return $this->error($e->getMessage());
		}
		return $this->success($data);
	}
}