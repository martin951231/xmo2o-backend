<?php
namespace app\survey\model;

use app\common\model\db\User;
use think\Model;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

class SurveySubmit extends Model
{
    // 获取提交的详细答案
    public function answers(): HasMany
    {
        return $this->hasMany(SurveySubmitAnswer::class, 'submit_id', 'id');
    }
	
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class, 'user_id', 'uid');
	}
	
	public function searchUserIdAttr($query, $value)
    {
	    $value && $query->where('user_id', $value);
    }
	
	public static function onAfterInsert($submit)
    {
	    return SurveyForm::where('id', $submit['form_id'])->inc('submit_count')->update();
    }
}
