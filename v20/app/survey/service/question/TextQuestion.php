<?php

namespace app\survey\service\question;

use app\survey\model\SurveyQuestion;
use app\survey\service\strategy\QuestionStrategyInterface;
use think\Exception;

class TextQuestion implements QuestionStrategyInterface
{
	public function validateAnswer(SurveyQuestion $question, array $answer): bool
	{
		if (empty($answer['value']) && $question['required'] == 1) {
			throw new Exception($question['title'] . '为必填项');
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
