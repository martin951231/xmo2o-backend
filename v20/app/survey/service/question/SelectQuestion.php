<?php

namespace app\survey\service\question;

use app\survey\model\SurveyQuestion;
use app\survey\model\SurveyQuestionOption;
use app\survey\model\SurveySubmitAnswer;
use app\survey\service\strategy\QuestionStrategyInterface;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;

class SelectQuestion implements QuestionStrategyInterface
{
	/**
	 * 验证答案
	 * @param SurveyQuestion $question 问题信息
	 * @param mixed $answer 用户答案
	 * @return bool
	 * @throws Exception
	 */
	public function validateAnswer(SurveyQuestion $question, array $answer): bool
	{
		if (empty($answer['value']) && $question['required'] == 1) {
			throw new Exception($question['title'] . '为必填项');
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

	/**
	 * 准备答案数据
	 * @param int $submitId
	 * @param SurveyQuestion $question
	 * @param array $answer
	 * @return array
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
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

	/**
	 * 处理统计数据
	 */
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