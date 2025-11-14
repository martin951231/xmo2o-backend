<?php

namespace app\survey\controller\platform;

use app\BaseController;
use app\survey\service\StatisticsService;
use think\response\Json;

class SurveyStatisticsController extends BaseController
{
	/**  @var StatisticsService $statisticsService */
	protected $statisticsService;

	public function initialize()
	{
		parent::initialize();
		$this->statisticsService = new StatisticsService();
	}

	// 问卷统计概况
	public function overview(): Json
	{
		$formId = input('form_id', 0, 'intval');
		if (!$formId) {
			throw_exception('缺少问卷ID');
		}
		try {
			$formData = $this->statisticsService->getFormStatistics($formId);
			$questionData = $this->statisticsService->getQuestionStatistics($formId);
		} catch (\Exception $e) {
			throw_exception($e->getMessage());
		}
		return api_output(0, [
			'form_data' => $formData,
			'question_data' => $questionData
		]);
	}
}
