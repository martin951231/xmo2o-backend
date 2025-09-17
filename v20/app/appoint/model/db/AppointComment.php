<?php

declare (strict_types=1);

namespace app\appoint\model\db;

use think\Model;

class AppointComment extends Model
{
    public function getCommentImgAttr($value)
    {
        if($value){
            $value = unserialize($value);
        }
        return $value;
    }
    
    public function getScoreTextAttr($value, $data)
    {
        $text = [
            1 => '非常差',
            2 => '差',
            3 => '一般',
            4 => '满意',
            5 => '非常满意'
        ];
        if(!empty($data['average_score'])){
            return $text[intval($data['average_score'])];
        }
        return '';
    }
}