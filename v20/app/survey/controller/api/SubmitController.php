<?php

namespace app\survey\controller\api;

use app\common\controller\api\ApiBaseController;
use app\survey\model\SurveySubmit;
use app\survey\service\SubmitService;
use app\survey\validate\FormValidate;
use app\survey\validate\SubmitValidate;
use think\response\Json;

class SubmitController extends ApiBaseController
{
	/**  @var SubmitService $submitService */
	protected $submitService;

	public function initialize()
	{
		parent::initialize();
		$this->submitService = app(SubmitService::class);
	}

	// 问卷详情
	public function detail(): Json
	{
		$params = input();
		$this->validate($params, FormValidate::class . '.detail');
		$detail = $this->submitService->getFormDetail($params['form_id']);
		$detail->inc('visit_count')->update();
		$detail = $detail->toArray();
		return api_output(0, $detail);
	}

	// 提交问卷
	public function submitForm(): Json
	{
		$params = input();
		try {
			validate(SubmitValidate::class)->scene('add')->check($params);
			$submitId = $this->submitService->submitForm($this->uid, $params['form_id'], $params['answers'], $params);
		} catch (\Exception $e) {
			throw_exception($e->getMessage());
		}
		return api_output(0, $submitId);
	}

	// 获取用户提交记录
	public function userSubmitForm(): Json
	{
		$this->checkLogin();
		try {
			$submits = $this->submitService->getUserSubmitForm($this->uid);
		} catch (\Exception $e) {
			throw_exception($e->getMessage());
		}
		return api_output(0, $submits);
	}

	// 获取提交详情
	public function submitDetail(): Json
	{
		$submitId = input('submit_id', 0, 'intval');
		if (!$submitId) {
			throw_exception('缺少提交ID');
		}
		try {
			$detail = $this->submitService->getSubmitDetail($submitId, $this->uid)->toArray();
		} catch (\Exception $e) {
			throw_exception($e->getMessage());
		}
		return api_output(0, $detail);
	}
}
