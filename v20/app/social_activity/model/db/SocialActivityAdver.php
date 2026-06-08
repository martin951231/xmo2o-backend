<?php

namespace app\social_activity\model\db;

use think\Model;

class SocialActivityAdver extends Model
{
    public function getPicAttr($value, $data)
    {
        return $data['pic'] ? replace_file_domain($data['pic']) : '';
    }

    public function getSelectPicAttr($value, $data)
    {
        return $data['select_pic'] ? replace_file_domain($data['select_pic']) : '';
    }
}
