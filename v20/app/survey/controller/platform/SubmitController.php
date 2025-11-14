<?php

namespace app\survey\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\survey\service\SubmitService;
use app\survey\validate\SubmitValidate;
use think\response\Json;

class SubmitController extends AuthBaseController
{
	/**  @var SubmitService $submitService */
	protected $submitService;

	public function initialize()
	{
		parent::initialize();
		$this->submitService = app(SubmitService::class);
	}

	public function formSubmitList(): Json
	{
		$params = input();
		try {
			validate(SubmitValidate::class)->scene('detail')->check($params);
			$list = $this->submitService->getUserSubmits($params['form_id'], 0, $params);
		} catch (\Exception $e) {
			throw_exception($e->getMessage());
		}
		return api_output(0, $list);
	}           
}
