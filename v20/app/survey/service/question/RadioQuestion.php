<?php

namespace app\survey\service\question;

use app\survey\model\SurveyQuestion;
use app\survey\model\SurveyQuestionOption;
use app\survey\model\SurveySubmitAnswer;
use app\survey\service\strategy\QuestionStrategyInterface;
use think\Exception;

class RadioQuestion implements QuestionStrategyInterface
{
	public function validateAnswer(SurveyQuestion $question, array $answer): bool
	{
		// 必填项验证
		if (empty($answer['value']) && $question['required'] == 1) {
			throw new Exception("{$question['title']}为必填项");
		}
		if (!empty($answer['value']) && $answer['value'] != 'other') {
			$option = SurveyQuestionOption::where([
				'question_id' => $question['id'],
				'id' => $answer['value'],
			])->find();

			if (!$option) {
				throw new Exception("{$question['title']}问题提交了无效的选项");
			}
		}
		return true;
	}

	public function prepareAnswerData(int $submitId, SurveyQuestion $question, array $answer): array
	{
		if ($answer['value'] == 'other') {
			$optionText = '其他:';
			$optionText .= $answer['other_value'] ?? '';
			$extraAnswer = [
				'value' => $answer['value'],
				'other_value' => $answer['other_value'] ?? '',
			];
		} else {
			$optionId = $answer['value'] ?? 0;
			$optionText = '';
			// 获取选项文本
			if ($optionId > 0) {
				$option = SurveyQuestionOption::where([
					'question_id' => $question['id'],
					'id' => $optionId,
				])->find();
				$optionText = $option ? $option['label'] : '';
			}
		}
		return [
			'submit_id' => $submitId,
			'question_id' => $question['id'],
			'option_id' => $optionId ?? 0,
			'answer' => $optionText,
			'extra_answer' => $extraAnswer ?? [],
		];
	}

	public function processStatistics(SurveyQuestion $question)
	{
		$answerCount = SurveySubmitAnswer::where([
			'question_id' => $question['id'],
		])->where('option_id', '<>', '')
			->count();
		foreach ($question->question_options as $option) {
			$option->submit_count = SurveySubmitAnswer::where([
				'question_id' => $question['id'],
				'option_id' => $option['id'],
			])->count();

			$option->percent = $answerCount == 0 ? 0 : bcmul($option->submit_count / $answerCount, '100' ,2);
		}
	}
}
