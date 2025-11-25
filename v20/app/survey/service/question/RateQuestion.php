<?php

namespace app\survey\service\question;

use app\survey\model\SurveyQuestion;
use app\survey\model\SurveySubmitAnswer;
use app\survey\service\strategy\QuestionStrategyInterface;
use think\Exception;

class RateQuestion implements QuestionStrategyInterface
{
	public function validateAnswer(SurveyQuestion $question, array $answer): bool
	{
		if (empty($answer['value']) && $question['required'] == 1) {
			throw new Exception($question['title'] . '为必填项');
		}
		if ($answer['value'] && ($answer['value'] < 1 || $answer['value'] > 5)) {
			throw new Exception($question['title'] . '的分值必须在1-5之间');
		}
		return true;
	}

	public function prepareAnswerData(int $submitId, SurveyQuestion $question, array $answer): array
	{
		$score = $answer['value'] ?? 0;
		return [
			'submit_id' => $submitId,
			'question_id' => $question['id'],
			'answer' => (string)$score,
		];
	}

	public function processStatistics(SurveyQuestion $question)
	{
		$answerCount = SurveySubmitAnswer::where('question_id', $question['id'])->where('answer', '<>', '')->count();
		$rate = SurveySubmitAnswer::where('question_id', $question['id'])
			->group('answer')
			->field('count(*) as submit_count, answer as rate')
			->select()
			->toArray();
		
		foreach ($rate as &$option) {
			$option['percent'] = $answerCount == 0 ? 0 : bcmul($option['submit_count'] / $answerCount, '100' ,2);
		}
		$question->question_percent = $rate;
	}
}
