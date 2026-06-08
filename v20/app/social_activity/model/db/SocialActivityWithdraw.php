<?php


namespace app\social_activity\model\db;


use think\Model;

class SocialActivityWithdraw extends Model
{
    public function getStatusTextAttr($value, $data){
        $status = [0 => '待审核', 10 => '审核通过', 20 => '审核未通过'];
        return $status[$data['audit_status']] ?? '';
    }
}