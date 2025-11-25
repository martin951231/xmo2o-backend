<?php
namespace app\survey\model;

use think\Model;

class SurveyQuestionOption extends Model
{
	public function getExtraDataAttr($value, $data): ?array
	{
		return $value ? json_decode($value, true) : NULL;
	}

	public function setExtraDataAttr($value, $data): string
	{
		return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
	}
}
