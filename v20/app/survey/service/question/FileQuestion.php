<?php

namespace app\survey\service\question;

use app\survey\model\SurveyQuestion;
use app\survey\service\strategy\QuestionStrategyInterface;
use think\Exception;

class FileQuestion implements QuestionStrategyInterface
{
	public function validateAnswer(SurveyQuestion $question, array $answer): bool
	{
		// 必填项验证
		if (empty($answer['value']) && $question['required'] == 1) {
			throw new Exception("{$question['title']}为必填项");
		}

		// 文件URL验证
		if (!empty($answer['value'])) {
			$allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif'];
			$forbiddenExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'bat', 'cmd', 'sh', 'js', 'html', 'htm'];

			foreach ($answer['value'] as $url) {
				if (!preg_match('/^https?:\/\//', $url)) {
					throw new Exception("文件URL格式不正确");
				}

				// 提取文件扩展名
				$pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
				$extension = strtolower($pathInfo['extension'] ?? '');

				if (empty($extension)) {
					throw new Exception("无法识别文件类型");
				}

				if (in_array($extension, $forbiddenExtensions)) {
					throw new Exception("不允许上传{$extension}类型的文件");
				}

				if (!in_array($extension, $allowedExtensions)) {
					throw new Exception("仅支持上传pdf、word、Excel、PPT、图片、压缩包等常见文件类型");
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
