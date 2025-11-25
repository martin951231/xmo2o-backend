<?php
namespace app\survey\model;

use think\helper\Str;
use think\Model;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

class SurveyQuestion extends Model
{
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

	protected $append = ['value'];
	/**
	 * 关联选项表
	 * @return HasMany
	 */
    public function questionOptions(): HasMany
    {
        return $this->hasMany(SurveyQuestionOption::class, 'question_id', 'id')->order('sort', 'asc');
    }

     // 获取问题对应的答卷详情
    public function answerDetails(): HasMany
    {
        return $this->hasMany(SurveySubmitAnswer::class, 'question_id', 'id');
    }

	public function submitAnswerDetail(): HasOne
	{
		return $this->hasOne(SurveySubmitAnswer::class, 'question_id', 'id');
	}

	public function getExtraConfigAttr($value, $data): ?array
	{
		return $value ? json_decode($value, true) : NULL;
	}

	public function setExtraConfigAttr($value, $data): string
	{
		return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
	}

    /**
     * 获取问题类型文本
     * @param $value
     * @param $data
     * @return string
     */
    public function getTypeNameAttr($value, $data): string
    {
        $typeMap = [
            'radio' => '单选题',
            'checkbox' => '多选题',
            'text' => '单行文本',
            'textarea' => '多行文本',
            'select' => '下拉选择',
            'image' => '图片上传',
            'rating' => '评分',
            'file' => '文件上传',
            'areaAddress' => '地址选择',
            'phone' => '手机号',
            'name' => '姓名',
            'sex' => '性别'
        ];

        return $typeMap[$data['type']] ?? $data['type'];
    }

	public function getValueAttr($value, $data): string
	{
		return '';
	}
}
