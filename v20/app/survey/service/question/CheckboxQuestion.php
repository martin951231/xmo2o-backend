<?php

namespace app\survey\service\question;

use app\survey\model\SurveyQuestion;
use app\survey\model\SurveyQuestionOption;
use app\survey\model\SurveySubmitAnswer;
use app\survey\service\strategy\QuestionStrategyInterface;
use think\Exception;

class CheckboxQuestion implements QuestionStrategyInterface
{
	public function validateAnswer(SurveyQuestion $question, array $answer): bool
	{
		// 必填项验证
		if (empty($answer['value']) && $question['required'] == 1) {
			throw new Exception("{$question['title']}为必填项");
		}

		// 选项有效性验证
		if (!empty($answer['value']) && $answer['value'] != 'other') {
			$optionIds = is_array($answer['value']) ? $answer['value'] : explode(',', $answer['value']);
			$validOptionIds = SurveyQuestionOption::where('question_id', $question['id'])->column('id');

			foreach ($optionIds as $optionId) {
				if (!in_array($optionId, $validOptionIds)) {
					throw new Exception("{$question['title']}问题中提交了无效的选项ID: {$optionId}");
				}
			}
		}

		return true;
	}

	public function prepareAnswerData(int $submitId, SurveyQuestion $question, array $answer): array
	{
		if ($answer['value'] == 'other') {
			$optionText = '其他:';
			$optionText .= $answer['other_value'] ?? '';
			return [
				'submit_id' => $submitId,
				'question_id' => $question['id'],
				'answer' => $optionText,
				'extra_answer' => [
					'value' => $answer['value'],
					'other_value' => $answer['other_value'] ?? '',
				],
			];
		} else {
			$optionIds = $answer['value'] ?? [];
			if (!is_array($optionIds)) {
				$optionIds = explode(',', $optionIds);
			}
			$options = SurveyQuestionOption::where('id', 'in', $optionIds)
				->where('question_id', $question['id'])
				->select()
				->toArray();
			$optionTexts = array_column($options, 'label');
			$optionIds = array_column($options, 'id');
			return [
				'submit_id' => $submitId,
				'question_id' => $question['id'],
				'option_id' => implode(',', $optionIds),
				'answer' => implode(',', $optionTexts),
			];
		}
	}

	public function processStatistics(SurveyQuestion $question)
	{
		$answer = SurveySubmitAnswer::where([
			'question_id' => $question['id'],
		])->where('option_id', '<>', '')
			->column('option_id');
		$answerCount = 0;
		foreach ($answer as $value){
			$answerCount += count(array_filter(explode(',', $value)));
		}
		foreach ($question->question_options as $option) {
			$option->submit_count = SurveySubmitAnswer::where([
				'question_id' => $question['id'],
			])->whereFindInSet('option_id', $option['id'])->count();

			$option->percent = $answerCount == 0 ? 0 : bcmul($option->submit_count / $answerCount, '100' ,2);
		}
	}
}
