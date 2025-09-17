<?php
/**
 * User.php
 * 用户model
 * Create on 2020/9/9 16:46
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class User extends Model
{
    public function getUserById($uid)
    {
        $where = ['uid' => $uid,'status'=>1];
        //用户头像需要确认一个默认值 目前都没有值 先空着
        $user = $this->field('uid,nickname,phone,avatar as user_logo,now_money,score_count,level,wxapp_openid,openid')->where($where)->find();
        if ($user) {
            return $user->toArray();
        } else {
            return [];
        }
    }

}