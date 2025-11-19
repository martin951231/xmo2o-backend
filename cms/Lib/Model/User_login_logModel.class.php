<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/11/9 13:40
 */
class User_login_logModel extends Model
{
    public $max_user_login_limit=5;// 同一个账号最多一天可以重试 5次 输错密码尝试
    public $max_user_ip_login_limit=10;// 最多一天同一个终端同一个ip可以重试 10次 输错密码尝试
    public $max_user_code_err_limit=3;// 验证码输入错误3次 当前用户的验证码失效需要重新发送

    // 记录登录错误日志
    public function addLoginErrLog($loginLog) {
        $logData = [];
        if (!isset($loginLog['logType'])) {
            $logData['logType'] = 0;
        } else {
            $logData['logType'] = $loginLog['logType'];
        }
        if (!trim($loginLog['logAccount'])) {
            // 至少要有账号
            return false;
        } else {
            $logData['logAccount'] = trim($loginLog['logAccount']);
        }
        if (!$loginLog['logIp']) {
            // 至少要有账号
            $logData['logIp'] = get_client_ip(0);
        } else {
            $logData['logIp'] = $loginLog['logIp'];
        }
        if (!$loginLog['httpUserAgent']) {
            // 至少要有账号
            $logData['httpUserAgent'] = $_SERVER['HTTP_USER_AGENT'];
        } else {
            $logData['httpUserAgent'] = $loginLog['httpUserAgent'];
        }
        if (!$loginLog['logDayTime']) {
            // 至少要有账号
            $logData['logDayTime'] = date('Ymd');
        } else {
            $logData['logDayTime'] = $loginLog['logDayTime'];
        }
        if ($loginLog['logContent']) {
            // 错误相关内容
            $logData['logContent'] = $loginLog['logContent'];
        }
        $logData['logStatus'] = 1;
        $logData['logTime'] = time();
        $logId = $this->add($logData);
        return $logId;
    }

    // 拦截由于错误密码次数太多不允许再次尝试
    public function checkErr($data) {
        if (!trim($data['logAccount'])) {
            // 至少要有账号
            return false;
        } else {
            $logAccount = trim($data['logAccount']);
        }
        if (!isset($data['logType'])) {
            $logType = 0;
        } else {
            $logType = $data['logType'];
        }
        if (!$data['logIp']) {
            // 至少要有账号
            $logIp = get_client_ip(0);
        } else {
            $logIp = $data['logIp'];
        }
        if (!$data['httpUserAgent']) {
            // 至少要有账号
            $httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
        } else {
            $httpUserAgent = $data['httpUserAgent'];
        }
        $dayTime = date('Ymd');
        $whereAccount = [
            'logAccount' => $logAccount,
            'logDayTime' => $dayTime,
            'logType' => $logType,
            'logStatus' => 1
        ];
        $accountLoginDayCount = $this->where($whereAccount)->count();
        if (1==$logType) {
            if ($accountLoginDayCount && $accountLoginDayCount>=$this->max_user_code_err_limit) {
                if ($data['device_id']) {
                    // 验证码 同一账号
                    $now_time = time();
                    // 当前手机号 先前发送的验证码 重置为失效
                    $where_repeat = array();
                    $where_repeat['phone'] = $logAccount;
                    $where_repeat['device_id'] = $data['device_id'];
                    $where_repeat['status'] = 0;
                    $where_repeat['expire_time'] = array('gt', $now_time);
                    $save_set = [
                        'status' => 1,
                        'expire_time' => $now_time,
                    ];
                    D("App_sms_record")->where($where_repeat)->save($save_set);
                    $set = [
                        'logStatus' => 2,
                        'updateTime' => $now_time,
                    ];
                    $this->where($whereAccount)->save($set);
                }
                throw new Exception("您输入验证码错误次数过多，当前验证码已失效，请重新获取新的验证码!");
            }
            return $data;
        }
        if ($accountLoginDayCount && $accountLoginDayCount>=$this->max_user_login_limit) {
            throw new Exception("您输入错误次数太多，请您明天再试!");
        }
        $whereIpAccount = [
            'logIp' => $logIp,
            'httpUserAgent' => $httpUserAgent,
            'logDayTime' => $dayTime,
            'logType' => $logType,
            'logStatus' => 1,
        ];
        $accountLoginIpDayCount = $this->where($whereIpAccount)->count();
        if ($accountLoginIpDayCount && $accountLoginIpDayCount>=$this->max_user_ip_login_limit) {
            throw new Exception("您输入错误次数太多，请您明天再试!");
        }
        return $data;
    }
}