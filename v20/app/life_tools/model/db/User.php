<?php 
namespace app\life_tools\model\db;

use think\Model;

class User extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'uid';

    public function getGenderAttr($value, $data)
    {
        $genderArr = ['未知', '男', '女'];
        return $genderArr[$data['sex']] ?? '';
    }
}