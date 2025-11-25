<?php
class User_payidModel extends Model
{
    /*获取用户信息*/
    public function get($user_payid)
    {
        $condition_info['payid'] = $user_payid;
        $info = $this->field('uid, phone, add_time')->where($condition_info)->find();
        // $time_info = time()-30; // 有效时间30秒
        // if ($info['add_time'] < $time_info) {
        //     $info = array(
        //         'error' => true,
        //         'msg' => '您扫的二维码已过期，请重新扫码'
        //     );
        // } else {
        //     $info['error'] = false;
        // }
        $info['error'] = false;
        return $info;
    }
}
?>