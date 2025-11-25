<?php

namespace app\survey\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\survey\service\FormService;
use app\survey\validate\FormValidate;
use think\response\Json;

class SurveyFormController extends AuthBaseController
{
	/** @var FormService $formService */
	protected $formService;

	public function initialize()
	{
		parent::initialize();
		$this->formService = app(FormService::class);
	}

	// 问卷列表
	public function index(): Json
	{
		$params = input();
		try {
			$list = $this->formService->getFormList($params)->toArray();
		} catch (\Exception $e) {
			throw_exception($e->getMessage());
		}
		return api_output(0, $list);
	}

	// 问卷详情
	public function detail(): Json
	{
		$params = input();
		$this->validate($params, FormValidate::class . '.detail');
		$detail = $this->formService->getFormDetail($params['form_id'])->toArray();
		return api_output(0, $detail);
	}

	// 保存问卷
	public function save(): Json
	{
		$params = input();
		try {
			$this->validate($params, FormValidate::class . '.save');
			if (empty($params['form_id'])) {
				$formId = $this->formService->createForm($params);
			} else {
				$formId = $this->formService->updateForm($params['form_id'], $params);
			}
		} catch (\Exception $e) {
			throw_exception($e->getMessage());
		}
		return api_output(0, ['form_id' => $formId]);
	}

	public function changeForm(): Json
	{
		$params = input();
		try {
			$this->validate($params, FormValidate::class . '.change');
			$this->formService->changeForm($params['form_id'], $params);
		} catch (\Exception $e) {
			throw_exception($e->getMessage());
		}
		return api_output(0, true);
	}
}
