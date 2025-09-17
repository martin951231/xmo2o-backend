<?php
class User_web_first_loginModel extends Model
{
	public function isUserWebFirstLogin($uid=0,$device_id='')
	{
		if($uid<1){
            return 0;
        }
        $condition_user = array();
		$condition_user['uid'] = $uid;
		$user=M('User')->where($condition_user)->field('uid,add_time,last_time')->find();
		$add_time=1672835400;   //2023-01-04 20:30:00 之前的老数据不处理
		if($user && $user['add_time']<$add_time){
            return 0;
        }
        $user_web_first_login=$this->where($condition_user)->order('popnum desc,id desc')->find();
        if(empty($user_web_first_login) || $user_web_first_login['popnum']<1){
            if(empty($user_web_first_login)){
                $inset_id=$this->addUserWebFirstLogin($uid,$device_id);
            }
            return 1;
        }
        return 0;
	}

    public function addUserWebFirstLogin($uid=0,$device_id='')
    {
        if($uid<1){
            return false;
        }
        
        $loginArr = array();
        $loginArr['uid'] = $uid;
        $loginArr['device_id'] = $device_id ? trim($device_id):'';
        $loginArr['add_time'] = time();
        $inset_id=$this->data($loginArr)->add();;
        if($inset_id){
            return true;
        }
        return false;
    }

    public function updateUserWebFirstLoginPopnum($uid=0,$device_id='')
    {
        if($uid<1){
            return false;
        }

        $condition_user = array();
        $condition_user['uid'] = $uid;
        
        $user_web_first_login=$this->where($condition_user)->order('popnum desc,id desc')->find();
        if(empty($user_web_first_login)){
            $inset_id=$this->addUserWebFirstLogin($uid=0,$device_id='');
        }
        $ret=$this->where($condition_user)->setInc('popnum', 1);
        if($ret){
            return true;
        }
        return false;
    }
}
?>