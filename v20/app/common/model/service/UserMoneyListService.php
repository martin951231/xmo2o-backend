<?php
/**
 * 会员余额操作
 * Created by.PhpStorm
 * User: chenxiang
 * Date: 2020/5/26 9:55
 */

namespace app\common\model\service;

use app\common\model\db\UserMoneyList;

class UserMoneyListService
{
    public $userMoneyListObj = null;
    public function __construct()
    {
        $this->userMoneyListObj = new UserMoneyList();
    }

    /**
     * 增加会员余额记录
     * User: chenxiang
     * Date: 2020/5/26 11:06
     * @param $uid
     * @param $type
     * @param $money
     * @param $msg
     * @param bool $record_ip
     * @param int $ask
     * @param int $ask_id
     * @param bool $admin
     * @param int $time
     * @return bool
     */
    public function addRow($uid, $type, $money, $msg, $record_ip = true, $ask = 0, $ask_id = 0, $admin = false, $time = 0) {
        if(!$time){
            $time = time();
        }
        $data_user_money_list['uid'] = $uid;
        $data_user_money_list['type'] = $type;
        $data_user_money_list['money'] = $money;
        $data_user_money_list['desc'] = $msg;
        $data_user_money_list['time'] = $time;
        $data_user_money_list['ask'] = $ask;
        $data_user_money_list['ask_id'] = $ask_id;
//        if($_SESSION['system']['id'] && $admin){
//            $data_user_money_list['admin_id'] = $_SESSION['system']['id'];
//        }
        if($record_ip){
            $data_user_money_list['ip'] = request()->ip();
        }

        if($this->userMoneyListObj->addData($data_user_money_list)){
            return true;
        }else{
            return false;
        }
    }

}
