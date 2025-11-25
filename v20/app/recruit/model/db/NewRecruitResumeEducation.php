<?php
namespace app\recruit\model\db;

use think\Model;

class NewRecruitResumeEducation extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getEducation($val)
    {
        $setting = [
            1 => '初中及以下',
            2 => '中专/中技',
            3 => '高中',
            4 => '大专',
            5 => '本科',
            6 => '硕士',
            7 => '博士'
        ];
        return $setting[$val] ?? '';
    }
}