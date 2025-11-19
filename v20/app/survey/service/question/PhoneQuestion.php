<?php

namespace app\survey\service\question;

use app\survey\model\SurveyQuestion;
use app\survey\service\strategy\QuestionStrategyInterface;
use think\Exception;

class PhoneQuestion implements QuestionStrategyInterface
{
	public function validateAnswer(SurveyQuestion $question, array $answer): bool
	{
		// 必填项验证
		if (empty($answer['value']) && $question['required'] == 1) {
			throw new Exception("{$question['title']}为必填项");
		}
		if ($answer['value']) {
			// 手机号码验证
			if (!preg_match('/^1[3456789]\d{9}$/', $answer['value'])) {
				throw new Exception("{$question['title']}格式不正确");
			}
		}
		return true;
	}

	public function prepareAnswerData(int $submitId, SurveyQuestion $question, array $answer): array
	{
		return [
			'submit_id' => $submitId,
			'question_id' => $question['id'],
			'answer' => $answer['value'] ?: '',
		];
	}

	public function processStatistics(SurveyQuestion $question)
	{
		
	}
}
