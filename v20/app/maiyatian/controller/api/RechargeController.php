<?php

namespace app\maiyatian\controller\api;

use app\common\controller\api\ApiBaseController;
use app\maiyatian\service\MaiyatianRechargeService;
use app\middleware\User;
use think\response\Json;

class RechargeController extends ApiBaseController
{
	protected $middleware = [User::class];
	protected $maiyatianRechargeService;

	public function __construct()
	{
		parent::__construct(app());
		$this->maiyatianRechargeService = new MaiyatianRechargeService();
		$this->checkLogin();
	}

	public function saveRechargeOrder(): Json
	{
		$params = input();
		$this->validate($params, [
			'shop_id' =>'require|integer',
			'money' =>'require|float',
		]);
		$params['uid'] = $this->uid;
		try {
			$data = $this->maiyatianRechargeService->saveRechargeOrder($params);
		} catch (\Exception $e) {
			fdump_api([input(), $e->getMessage(), $e->getTraceAsString()], 'MaiyatianRecharge/saveRechargeOrder', true);
			throw_exception($e->getMessage());
		}
		return api_output(0,$data);
	}
	
	public function merchant(): Json
	{
		$params = input();
		$this->validate($params, [
			'shop_id' =>'require|integer',
		]);
		try {
			$data = $this->maiyatianRechargeService->getMerchant((int)$params['shop_id']);
		} catch (\Exception $e) {
			throw_exception($e->getMessage());
		}
		return api_output(0,$data);
	}
}