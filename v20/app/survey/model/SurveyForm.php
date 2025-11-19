<?php

namespace app\survey\model;

use think\Model;
use think\model\relation\HasMany;

class SurveyForm extends Model
{
	// 时间字段自动格式化
	protected $autoWriteTimestamp = 'datetime';
	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';

	const STATUS_ENABLE = 1;//已启用
	const STATUS_DRAFT = 2;//草稿
	const STATUS_DISABLE = 3;//已禁用

	/**
	 * 关联问题表
	 * @return HasMany
	 */
	public function questions(): HasMany
	{
		return $this->hasMany(SurveyQuestion::class, 'form_id', 'id')->order('sort', 'asc');
	}

	/**
	 * 关联提交记录表
	 * @return HasMany
	 */
	public function submits(): HasMany
	{
		return $this->hasMany(SurveySubmit::class, 'form_id', 'id');
	}

	public function getStatusTextAttr($value, $data): string
	{
		$status = [
			self::STATUS_ENABLE => '已启用',
			self::STATUS_DRAFT => '草稿',
			self::STATUS_DISABLE => '已禁用',
		];
		return $status[$data['status']] ?? '未知';
	}
	
	public function getThemeConfigAttr($value): ?array
	{
		if($value) {
			$value = json_decode($value, true);
			if(!empty($value['formHeaderImage'])) {
				$value['formHeaderImage'] = replace_file_domain($value['formHeaderImage']);
			}
		}
		return $value ?: NULL;
	}

	public function setThemeConfigAttr($value): string
	{
		return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
	}
	
	public function searchTitleAttr($query, $value)
	{
		$value && $query->where('title', 'like', "%{$value}%");
	}

	public function searchStatusAttr($query, $value)
	{
		$value && $query->where('status', $value);
	}
}
