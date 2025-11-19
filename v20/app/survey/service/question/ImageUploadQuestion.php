<?php

namespace app\survey\service\question;

use app\survey\model\SurveyQuestion;
use app\survey\service\strategy\QuestionStrategyInterface;
use think\Exception;

class ImageUploadQuestion implements QuestionStrategyInterface
{
	public function validateAnswer(SurveyQuestion $question, array $answer): bool
	{
		// 必填项验证
		if (empty($answer['value']) && $question['required'] == 1) {
			throw new Exception("{$question['title']}为必填项");
		}

		// 图片URL验证
		if (!empty($answer['value'])) {
			// 这里可以添加图片URL格式验证或文件存在性验证
			foreach ($answer['value'] as $url) {
				if (!preg_match('/^https?:\/\//', $url)) {
					throw new Exception("图片URL格式不正确");
				}
			}
		}

		return true;
	}

	public function prepareAnswerData(int $submitId, SurveyQuestion $question, array $answer): array
	{
		$answer = $answer['value'] ?? [];

		return [
			'submit_id' => $submitId,
			'question_id' => $question['id'],
			'answer' => $answer ? implode(',', $answer) : '',
			'extra_answer' => $answer ? array_map(function ($url) {
				return replace_file_domain($url);
			}, $answer) : '',
		];
	}

	public function processStatistics(SurveyQuestion $question)
	{
		
	}
}
