<?php

namespace app\survey\service;

use app\survey\model\SurveyForm;
use app\survey\model\SurveySubmit;
use app\survey\service\strategy\QuestionStrategyFactory;
use Carbon\Carbon;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;

class StatisticsService
{
	/**
	 * 问卷统计概览
	 * @throws DataNotFoundException
	 * @throws ModelNotFoundException
	 * @throws DbException
	 * @throws Exception
	 */
	public function getFormStatistics($formId): array
	{
		$form = SurveyForm::where('is_del', 0)->find($formId);
		if (!$form) {
			throw new Exception('问卷不存在');
		}
		$todaySubmitCount = SurveySubmit::where('form_id', $formId)
			->where('is_del', 0)
			->whereBetweenTime(
				'create_time',
				Carbon::now()->startOfDay()->toDateTimeString(),
				Carbon::now()->endOfDay()->toDateTimeString()
			)
			->count();

		$userCount = SurveySubmit::where('form_id', $formId)
			->where('is_del', 0)
			->where('user_id', '>', 0)
			->group('user_id')
			->count();

		$ipUserCount = SurveySubmit::where('form_id', $formId)
			->where('is_del', 0)
			->where('user_id', '=', 0)
			->group('ip_address')
			->count();

		return [
			'submit_count' => $form->submit_count,
			'visit_count' => $form->visit_count,
			'total_submit_count' => $todaySubmitCount,
			'user_count' => $userCount + $ipUserCount,
		];
	}


	/**
	 * @throws Exception
	 */
	public function getQuestionStatistics($formId): array
	{
		$form = (new FormService())->getFormDetail($formId, ['fields' => 'id,title,description, submit_count']);

		foreach ($form['questions'] as $key => $question) {
			if (in_array($question->type, ['radio', 'checkbox', 'select', 'rating', 'sex'])) {
				$question->submit_count = $form->submit_count;
				$strategy = QuestionStrategyFactory::create($question->type);
				$strategy->processStatistics($question);
			} else {
				unset($form['questions'][$key]);
			}
		}
		$form = $form->hidden(['submit_count'])->toArray();
		$form['questions'] = array_values($form['questions']);
		foreach ($form['questions'] as &$question) {
			if($question['type'] == 'rating') {
				$question['question_options'] = $question['question_percent'];
				unset($question['question_percent']);
			}
		}
		return $form;
	}
}
