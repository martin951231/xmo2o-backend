<?php

namespace app\survey\service\question;

use app\survey\model\SurveyQuestion;
use app\survey\service\strategy\QuestionStrategyInterface;
use think\Exception;

class AreaAddressQuestion implements QuestionStrategyInterface
{
	public function validateAnswer(SurveyQuestion $question, array $answer): bool
	{
		if (
			(empty($answer['value']['province_id']) ||
			empty($answer['value']['city_id']) ||
			empty($answer['value']['area_id']) ||
			empty($answer['value']['address'])) &&
			$question['required'] == 1
		) {
			throw new Exception($question['title'] . '为必填项');
		}

		return true;
	}

	public function prepareAnswerData(int $submitId,SurveyQuestion $question, array $answer): array
	{
		return [
			'submit_id' => $submitId,
			'question_id' => $question['id'],
			'answer' => ($answer['value']['label'] ?? '').$answer['value']['address'],
			'extra_answer' => $answer['value'],
		];
	}

	public function processStatistics(SurveyQuestion $question)
	{
		
	}
}
