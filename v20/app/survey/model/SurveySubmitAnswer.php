<?php
namespace app\survey\model;

use think\Model;
use think\model\relation\BelongsTo;

class SurveySubmitAnswer extends Model
{
	public function question(): BelongsTo
	{
		return $this->belongsTo(SurveyQuestion::class, 'question_id');
	}

	public function getExtraAnswerAttr($value): ?array
	{
		return $value ? json_decode($value, true) : NULL;
	}

	public function setExtraAnswerAttr($value): string
	{
		return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
	}
}
