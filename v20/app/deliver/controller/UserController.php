<?php

namespace app\deliver\controller;

use app\BaseController;
use app\common\model\service\AppPushMsgService;
use app\common\model\service\AreaService;
use app\common\model\service\send_message\SmsService;
use app\deliver\model\service\DeliverSupplyService;
use app\deliver\model\service\DeliverUserClockLogService;
use app\deliver\model\service\DeliverUserRegisterService;
use app\deliver\model\service\DeliverUserService;
use app\deliver\model\service\DeliverUserFieldService;
use think\Exception;
use think\facade\Cache;
use token\Token;

/**
 * 登录控制器
 * @author: 张涛
 * @date: 2020/9/7
 * @package app\deliver\controller
 */
class UserController extends ApiBaseController
{

    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 登录
     * @author: 张涛
     * @date: 2020/9/7
     */
    public function login()
    {
        $phone = $this->request->param('phone', '', 'trim');
        $password = $this->request->param('password', '', 'trim');
        $code = $this->request->param('code', '', 'trim');
        $client = $this->request->param('client', 0);
        $face_img = $this->request->param('face_img', '', 'trim');//人脸登陆图片
        $uid = $this->request->log_uid;
        $appVersion = $this->request->param('app_version','','intval');
        $registrationId  = $this->request->param("registrationId", "", "trim");
        $deliverUserService = new DeliverUserService();
        if ($uid > 0) {
            $deliverUser = $deliverUserService->getLoginInfo($uid);
            $return = [
                'device_id' => $this->deviceId,
                'ticket' => $this->request->param('ticket', '', 'trim'),
                'user' => $deliverUser,
            ];
            //登录成功应该把同样设备号的其他的用户全部设置为下线
            if (!empty($this->deviceId)) {
                $deliverUserService->setLogoutByDeviceId($this->deviceId, $uid);
            }
            return api_output(0, $return);
        }

        if (empty($face_img) && empty($phone)) {
            return api_output(1001, [], L_('手机号有误'));
        } else if (empty($face_img) && empty($password) && empty($code)) {
            return api_output(1001, [], L_('参数有误'));
        } else {
            try {
                if ($password) {
                    //密码登录
                    $deliverUser = $deliverUserService->login($phone, $password, true);
                } else if ($face_img) {//人脸登陆
                    $deliverUser = $deliverUserService->faceOperate(['type' => 3, 'img' => $face_img]);
                    if (!$deliverUser) {
                        return api_output(1001, [], L_('未查到人脸，请重试'));
                    }
                } else {
                    //验证码登录
                    $deliverUser = $deliverUserService->login($phone, $code, false);
                }
                preg_match('/versionCode=(\d+)/', $this->request->header('user-agent'), $versionCode);
                $dataDeliverUser = [];
                if ($versionCode && isset($versionCode[1])) {
                    $dataDeliverUser['app_version'] = intval($versionCode[1]);
                }else if($appVersion){
                    $dataDeliverUser['app_version'] =  $appVersion;
                }
                $dataDeliverUser['last_time'] = time();
                $dataDeliverUser['device_id'] = $this->deviceId;
                //$dataDeliverUser['device_token'] = $this->deviceId;
                $dataDeliverUser['client'] = $client;
                $dataDeliverUser['ip'] = $this->request->ip();
                $dataDeliverUser['jpush_registrationId'] = $registrationId;

                $deliverUserService->loginSucessAfter($dataDeliverUser, $deliverUser['uid']);
                $return = [
                    'device_id' => $this->deviceId,
                    'ticket' => Token::createToken($deliverUser['uid'], $this->deviceId),
                    'user' => $deliverUser,
                ];
                return api_output(0, $return);
            } catch (\Exception $e) {
                return api_output(1003, [], $e->getMessage());
            }
        }
    }

    /**
     * 登录
     * @author: 张涛
     * @date: 2020/9/7
     */
    public function logout()
    {
        $this->checkLogin();
        $uid = $this->request->log_uid;
        try {
            (new DeliverUserService())->logout($uid);
            return api_output(0, [], L_('退出成功'));
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 发送验证码
     * @author: 张涛
     * @date: 2020/9/7
     */
    public function sendCode()
    {
        $phone = $this->request->param('phone', '', 'trim');
        //来源  login：登录  reset_password:重置密码
        $type = ['login' => 31, 'reset_password' => 32, 'change_phone' =>33, 'register' => 34];
        $from = $this->request->param('from', '', 'trim');
        $phoneCountryType = $this->request->param('phone_country_type', '', 'trim');

        if ($from != 'login' && $from != 'register') {
            $this->checkLogin();
        }

        //检验手机号是否合法 国内,11位,海外，数字组合
        if ($phoneCountryType == '' || $phoneCountryType == '86') {
            $regx = '/^1\d{10}$/';
        } else {
            $regx = '/^\d+$/';
        }
        if (!preg_match($regx, $phone)) {
            return api_output(1001, [], L_('手机号格式有误'));
        }
        if (!isset($type[$from])) {
            return api_output(1001, [], L_('非法请求来源'));
        }
        try {
            (new SmsService())->sendDeliverCode($from, $phone, $phoneCountryType, $this->deviceId, $this->request->log_uid);
            return api_output(0, [], L_('发送成功'));
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 重置密码
     * @author: 张涛
     * @date: 2020/9/7
     */
    public function resetPasswrod()
    {
        $this->checkLogin();

        $code = $this->request->param('code', '', 'trim');
        $password = $this->request->param('password', '', 'trim');
        $confirm_password = $this->request->param('confirm_password', '', 'trim');
        $uid = $this->request->log_uid;
        if (empty($password) || empty($confirm_password) || $password != $confirm_password) {
            return api_output(1001, [], L_('新密码和确认密码不一致'));
        }
        if (empty($code)) {
            return api_output(1001, [], L_('验证码不能为空'));
        }
        //重置密码
        try {
            (new DeliverUserService())->resetPasswrod($uid, $password, $code);
            return api_output(0, [], L_('重置成功'));
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 更换手机
     * @author: 张涛
     * @date: 2020/9/7
     */
    public function changePhone()
    {
        $this->checkLogin();

        $code = $this->request->param('code', '', 'trim');
        $phone = $this->request->param('phone', '', 'trim');
        $uid = $this->request->log_uid;
        if (empty($phone)) {
            return api_output(1001, [], L_('手机号码不能为空'));
        }
        if (empty($code)) {
            return api_output(1001, [], L_('验证码不能为空'));
        }

        //更换手机
        try {
            (new DeliverUserService())->changePhone($uid, $phone, $code);
            return api_output(0, [], L_('更换成功'));
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }


    /**
     * 注销账号
     * @author: 张涛
     * @date: 2020/9/7
     */
    public function destroyAccount()
    {
        $this->checkLogin();
        try {
            (new DeliverUserService())->destroyAccount($this->request->log_uid);
            return api_output(0, [], L_('注销成功'));
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 个人中心
     * @author: 张涛
     * @date: 2020/9/8
     */
    public function center()
    {
        $this->checkLogin();
        $uid = $this->request->log_uid;
        try {
            $arr['today_finish_count'] = (new DeliverUserService())->getTodayFinishCount($uid);
            $arr['today_deliver_fee'] = (new DeliverUserService())->getTodayDeliverFee($uid);
            $arr['month_high_opinion'] = (new DeliverUserService())->getMonthHighOpinion($uid);
            $arr['deliver_info'] = (new DeliverUserService())->getBaseInfo($uid);
            $arr['diy_columns'] = [];
            // if (cfg('show_ranking')) {
            //     $arr['diy_columns'][] = [
            //         'name' => '排行榜',
            //         'icon' => cfg('site_url') . '/packapp/deliver_web/images/ranking_cup.png',
            //         'url' => cfg('site_url') . '/packapp/deliver_web/ranking.html?t=' . time()
            //     ];
            // }

            return api_output(0, $arr, L_('成功'));
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }


    /**
     * 可转单配送员列表
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function deliverNearby()
    {
        $supplyId = $this->request->param('supply_id', 0, 'intval');
        if ($supplyId < 1) {
            return api_output(1001, [], L_('参数错误'));
        }
        try {
            $rs = (new DeliverUserService())->deliverNearby($supplyId, $this->request->log_uid);
            return api_output(0, $rs);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }


    /**
     * 获取当前配送员进行中订单数量
     * @author: 张涛
     * @date: 2020/9/12
     */
    public function orderCountInProgress()
    {
        if ($this->request->log_uid < 1) {
            return api_output(1001, [], L_('参数错误'));
        }
        $count = (new DeliverUserService)->getCurrentCount($this->request->log_uid);
        return api_output(0, ['count' => $count], L_('成功'));
    }

    /**
     * 接单中/休息中
     * @author: 张涛
     * @date: 2020/09/12
     */
    public function saveNotice()
    {
        $this->checkLogin();
        $isNotice = $this->request->param('is_notice', 0, 'intval');
        $uid = $this->request->log_uid;
        try {
            (new DeliverUserService)->saveNotice($uid, $isNotice);
            $arr = ['show' => false];
            if ($isNotice == 1) {
                if ($this->deviceId) {
                    (new AppPushMsgService())->setIsSendByDeviceId($this->deviceId);
                }
                //设置成休息，返回当天统计数据
                $arr = [
                    'show' => true,
                    'today_order_count' => (new DeliverUserService())->getTodayFinishCount($uid),
                    'today_income' => (new DeliverUserService())->getTodayDeliverFee($uid),
                ];
            }
            //记录上下线日志
            (new DeliverUserClockLogService())->record($uid, $isNotice == 0 ? 1 : 2);
            return api_output(0, $arr, L_('设置成功'));
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 评分
     * @author: 张涛
     * @date: 2020/9/14
     */
    public function reply()
    {
        $this->checkLogin();
        try {
            $rs = (new DeliverSupplyService())->getScoreReportByUid($this->request->log_uid);
            return api_output(0, $rs, L_('成功'));
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 我的评价列表
     * @author: 张涛
     * @date: 2020/9/14
     */
    public function replyList()
    {
        $this->checkLogin();
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 20, 'intval');
        $scoreType = $this->request->param('score_type', 'all', 'trim');
        try {
            $rs = (new DeliverSupplyService())->replyList($this->request->log_uid, $page, $pageSize, $scoreType);
            return api_output(0, $rs, L_('成功'));
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 获取配送路线
     * @author: 张涛
     * @date: 2020/9/16
     */
    public function getRoute()
    {
        $this->checkLogin();
        try {
            $detail = (new DeliverUserService())->getRoute($this->request->log_uid);
            return api_output(0, $detail);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }


    /**
     * 日订单统计
     * @author: 张涛
     * @date: 2020/9/16
     */
    public function dayReport()
    {
        $this->checkLogin();
        try {
            $uid = $this->request->log_uid;
            $today = date('Y-m-d');
            $date = $this->request->param('date', '', 'trim');
            $date = $date ?: $today;
            $detail = (new DeliverUserService())->dayReport($uid, $date);
            return api_output(0, $detail);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 月订单统计
     * @author: 张涛
     * @date: 2020/9/17
     */
    public function monthReport()
    {
        $this->checkLogin();
        try {
            $uid = $this->request->log_uid;
            $count = 4;
            $detail = (new DeliverUserService())->monthReport($uid, $count);
            return api_output(0, $detail);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 订单明细
     * @author: 张涛
     * @date: 2020/9/17
     */
    public function orderLog()
    {
        $this->checkLogin();
        try {
            $uid = $this->request->log_uid;
            $today = date('Y-m-d');
            $date = $this->request->param('date', '', 'trim');
            $date = $date ?: $today;
            $status = $this->request->param('status', '', 'trim');
            $detail = (new DeliverUserService())->getOrderLogByDate($uid, $status, $date);
            return api_output(0, $detail);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 更新配送员device_token
     * @author: 张涛
     * @date: 2020/11/20
     */
    public function getDeviceToken()
    {
        try {
            $deviceId = $this->deviceId;
            $deviceToken = $this->request->param('device_token', '', 'trim');
            if (empty($deviceId) || empty($deviceToken)) {
                return api_output(0, []);
            }
            $where = [
                ['device_id', '=', $deviceId],
                ['status', '<>', 4],
            ];
            (new DeliverUserService())->updateDeviceToken($where, $deviceToken);
            return api_output(0, []);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 获取配送员注册自定义字段
     * @author: 汪晨
     * @date: 2021/03/29
     */
    public function registerFields()
    {
        $deliverFields = (new DeliverUserFieldService())->getFielsdList();
        $arr['deliverFields'] = $deliverFields;
        return api_output(0, $arr?: new \stdClass());
    }

    /**
     * 兼职配送员注册
     * @author: 张涛
     * @date: 2020/11/26
     */
    public function register()
    {
        try {
            if (!boolval(cfg('open_deliver_register'))) {
                return api_output(1003, [], L_('禁止注册'));
            }

            $param['phone'] = $this->request->param('phone', '', 'trim');
            $param['truename'] = $this->request->param('truename', '', 'trim');
            $param['card_number'] = $this->request->param('card_number', '', 'trim');
            $param['code'] = $this->request->param('code', '', 'trim');
            $param['face_img'] = $this->request->param('face_img', '', 'trim');//人脸登陆图片
            $param['province_id'] = $this->request->param('province_id', 0, 'intval');
            $param['city_id'] = $this->request->param('city_id', 0, 'intval');
            $param['lng'] = $this->request->param('lng', '');
            $param['lat'] = $this->request->param('lat', '');
            $param['adress'] = $this->request->param('adress', '', 'trim');
            // $param['fields'] = serialize($this->request->param('fields', '', 'trim'));
            (new DeliverUserRegisterService())->register($param);
            return api_output(0, [], L_('注册成功'));
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 我的钱包
     * @author: 张涛
     * @date: 2020/12/2
     */
    public function wallet()
    {
        $this->checkLogin();
        try {
            $rs = (new DeliverUserService())->myWallet($this->request->log_uid);
            return api_output(0, $rs);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 添加银行卡
     */
    public function createBankCard()
    {
        $this->checkLogin();
        $params = [
            'uid' => $this->request->log_uid,
            'account_name' => input('account_name', '', 'trim,string'),
            'account' => input('account', '', 'trim,string'),
            'remark' => input('remark', '', 'trim,string'),
            'province_id' => input('province_id', 0, 'trim,intval'),
            'city_id' => input('city_id', 0, 'trim,intval'),
            'branch' => input('branch', '', 'trim,string'),
            'is_default' => input('is_default', 0, 'trim,intval'),
            'bank_id' => input('bank_id', 0, 'trim,intval'),
        ];
        try {
            $rs = (new DeliverUserService())->createBankCard($params);
            return api_output(0, $rs);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 删除银行卡
     */
    public function deleteBankCard()
    {
        $this->checkLogin();
        $params = [
            'uid' => $this->request->log_uid,
            'bank_id' => input('bank_id', 0, 'trim,intval'),
        ];
        try {
            $rs = (new DeliverUserService())->deleteBankCard($params);
            return api_output(0, $rs);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 收入明细
     * @author: 张涛
     * @date: 2020/12/2
     */
    public function incomeDetail()
    {
        $this->checkLogin();
        try {
            $param['date'] = $this->request->param('date', '', 'trim');
            $param['page'] = $this->request->param('page', 1, 'intval');
            $param['pageSize'] = $this->request->param('pageSize', 20, 'intval');
            $param['type'] = $this->request->param('type', '0', 'trim');
            $param['uid'] = $this->request->log_uid;
            $rs = (new DeliverUserService())->incomeDetail($param);
            return api_output(0, $rs);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 申请提现
     * @author: 张涛
     * @date: 2020/12/3
     */
    public function applyWithdraw()
    {
        $this->checkLogin();
        $lockKey = 'withdrawLock';
        try {
            if (Cache::get($lockKey)) {
                throw new Exception('频繁操作，稍后重试');
            }
            $param['truename'] = $this->request->param('truename', '', 'trim');
            $param['money'] = $this->request->param('money', 0);
            $param['type'] = $this->request->param('type', '', 'trim');
            $param['account'] = $this->request->param('account', '', 'trim');
            $param['bank_id'] = $this->request->param('bank_id', 0, 'trim,intval');
            Cache::set($lockKey, 1, 30);
            (new DeliverUserService())->applyWithdraw($this->request->log_uid, $param);
            Cache::delete($lockKey);
            return api_output(0);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }


    /**
     * 是否绑定微信
     * @author: 张涛
     * @date: 2020/12/3
     */
    public function isBind()
    {
        $this->checkLogin();
        try {
            $type = $this->request->param('type', '', 'trim');
            if ($type == 'wechat') {
                $rs = (new DeliverUserService())->isBindWechat($this->request->log_uid);
            } else if ($type == 'alipay') {
                $rs = (new DeliverUserService())->isBindUser($this->request->log_uid);
            } else {
                return api_output(1003, [], L_('参数有误'));
            }
            return api_output(0, ['is_bind' => $rs]);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 上报位置
     * @author: 张涛
     * @date: 2021/06/22
     */
    public function reportLocation()
    {
        $lng = $this->request->param('lng', null);
        $lat = $this->request->param('lat', null);
        $uid = $this->request->log_uid;
        //上报位置
        if ($uid > 0 && !is_null($lng) && !is_null($lat) && $lat != 4.9E-324 && $lng != 4.9E-324) {
            (new DeliverUserService())->reportLocation($uid, $lat, $lng);
        }
        return api_output(0, []);
    }


    /**
     * 实名认证
     * @author: 汪晨
     * @date: 2021/09/16
     */
    public function deliverRealAuthe()
    {
        $this->checkLogin();
        try {
            $uid = $this->request->log_uid;
            $rs = (new DeliverUserService())->deliverRealAuthe($uid);
            return api_output(0, $rs);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 实名认证上传图片
     * @author: 汪晨
     * @date: 2021/09/16
     */
    public function deliverRealAutheImg()
    {
        $this->checkLogin();
        try {
            $param['uid'] = $this->request->log_uid;
            $param['face_recognition_images'] = $this->request->param('face_recognition_images', null);
            $rs = (new DeliverUserService())->deliverRealAutheImg($param);
            return api_output(0, $rs);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 人脸打卡-验证人脸是否正确
     * @author: liuruofei
     * @date: 2021/09/17
     */
    public function checkFace()
    {
        $this->checkLogin();
        $deliverUserService = new DeliverUserService();
        try {
            $uid = $this->request->log_uid;
            $face_img = $this->request->param('face_img', '', 'trim');//人脸图片地址
            $deliverUser = $deliverUserService->faceOperate(['type' => 3, 'img' => $face_img]);
            if (!$deliverUser) {
                return api_output(0, ['status' => 0]);//匹配失败
            }
            if ($uid != $deliverUser['uid']) {
                return api_output(0, ['status' => 0]);//匹配失败
            }
            return api_output(0, ['status' => 1]);//匹配成功
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }


    /**
     * 注销检测
     */
    public function logoffCheck()
    {
        $this->checkLogin();
        $deliverUserService = new DeliverUserService();
        try {
            $uid = $this->request->log_uid;
            $deliverUser = $deliverUserService->logoffCheck($uid);
            return api_output(0, $deliverUser);
        } catch (\Throwable $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 注销账号
     */
    public function logoffUser()
    {
        $this->checkLogin();
        $deliverUserService = new DeliverUserService();
        try {
            $uid = $this->request->log_uid;
            $res = $deliverUserService->logoffUser($uid);
            if($res){
                $deliverUserService->logout($uid);
            }
            return api_output(0, ["status" => 1]);//匹配成功
        } catch (\Throwable $e) {
            return api_output(1003, ["status" => 0], $e->getMessage());
        }
    }


    /**
     * 重新登录
     *
     * @return void
     * @author: zt
     * @date: 2023/03/20
     */
    public function reLogin()
    {
        $client = $this->request->param('client', 0);
        $uid = $this->request->log_uid;
        $appVersion = $this->request->param('app_version', '', 'intval');
        $registrationId  = $this->request->param("registrationId", "", "trim");
        $deliverUserService = new DeliverUserService();
        if ($uid > 0) {
            $deliverUser = $deliverUserService->getLoginInfo($uid);
            preg_match('/versionCode=(\d+)/', $this->request->header('user-agent'), $versionCode);
            $dataDeliverUser = [];
            if ($versionCode && isset($versionCode[1])) {
                $dataDeliverUser['app_version'] = intval($versionCode[1]);
            } else if ($appVersion) {
                $dataDeliverUser['app_version'] =  $appVersion;
            }
            $dataDeliverUser['last_time'] = time();
            $dataDeliverUser['device_id'] = $this->deviceId;
            $dataDeliverUser['client'] = $client;
            $dataDeliverUser['ip'] = $this->request->ip();
            $dataDeliverUser['jpush_registrationId'] = $registrationId;

            $deliverUserService->loginSucessAfter($dataDeliverUser, $deliverUser['uid']);
            $return = [
                'device_id' => $this->deviceId,
                'ticket' => Token::createToken($deliverUser['uid'], $this->deviceId),
                'user' => $deliverUser,
            ];
            return api_output(0, $return);
        } else {
            return api_output(1003, [], '获取登录信息失败');
        }
    }

    /**
     * 获取骑手注册城市
     *
     * @return void
     * @author: zt
     * @date: 2023/12/06
     */
    public function registerArea()
    {
        $areas = (new AreaService)->getProvinceCity();
        return api_output(0, $areas);
    }

}
