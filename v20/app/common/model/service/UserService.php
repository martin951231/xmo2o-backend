<?php
/**
 * 用户model
 * Author: chenxiang
 * Date Time: 2020/5/25 14:01
 */

namespace app\common\model\service;

use app\common\model\db\CardNew;
use app\common\model\db\CardNewCouponHadpull;
use app\common\model\db\OrderPayInfo;
use app\common\model\db\OrderPayRefundInfo;
use app\common\model\db\User;
use app\common\model\db\UserMoneyList;
use app\common\model\service\UserImportService;
use app\common\model\service\UserMoneyListService;
use app\common\model\service\UserScoreListService;
use app\common\model\service\HouseVillageUserBindService;
use app\common\model\service\FenrunRecommendAwardListService;
use app\common\model\service\UserShangyingtongService;
use app\common\model\service\UserSignService;
use app\common\model\service\UserSpreadService;
use app\common\model\service\MerchantPercentRateService;
use app\community\model\db\WeixinBindUser;
use app\group\model\service\GroupService;
use app\common\model\service\StoreOrderService;
use app\common\model\service\ShopOrderService;
use app\common\model\service\CardUserlistService;
use app\common\model\service\MerchantStoreShopService;
use app\common\model\service\FoodshopOrderService;
use app\common\model\service\PlatOrderService;
use app\common\model\service\MerchantService;
use app\common\model\service\UserLevelOpenLogService;
use app\common\model\service\SystemCouponHadpullService;
use app\common\model\service\weixin\TemplateNewsService;
use app\mall\model\service\SendTemplateMsgService;
use app\merchant\model\service\card\CardNewService;
use think\Exception;
use think\facade\Db;

class UserService
{
    public $userObj = null;
    public function __construct()
    {
        $this->userObj = new User();
    }

    /**
     * 获取单个用户信息
     * User: chenxiang
     * Date: 2020/5/25 16:42
     * @param string $field_value
     * @param string $field
     * @return array|\think\Model|null
     */
    public function getUser($field_value, $field = 'uid') {
        if(empty($field_value)){
            return [];
        }
        $condition_user[$field] = $field_value;

        $now_user = $this->userObj->getUser(true, $condition_user);

        if(empty($now_user)) {
            return [];
        }

//            // 执行获取用户信息的钩子，若有执行钩子，再重新查询一次用户
//            $hook_result = hook::hook_exec('user.get_user_before',['uid'=>$now_user['uid'],'user'=>$now_user]);
//            if($hook_result){
//                $now_user = $this->field(true)->where(['uid'=>$now_user['uid']])->find();
//            }

        if(cfg('nmgzhcs_appid') && $now_user['nmgzhcs_openid']) {
            $appid = cfg('nmgzhcs_appid');
            $appsecret = cfg('nmgzhcs_appsecret');

            $url = cfg('nmgzhcs_base_url') . '/api/member/memberinfo';

            $params = [
                'appid' => $appid,
                'openid' => $now_user['nmgzhcs_openid']
            ];
            ksort($params);
            $paramsJoined = [];
            foreach ($params as $param => $value) {
                $paramsJoined[] = "$param=$value";
            }
            $paramData = implode('&', $paramsJoined);

            $sign = strtoupper(md5($paramData.$appsecret));

            $params['sign'] = $sign;

            $res = http_request($url,'POST',$params);
            $res = json_decode($res[1],true);
            if($res['data']) {
                $data_user['nickname'] = $res['name'];
                $data_user['avatar'] = $res['data']['head_img'] ? $res['data']['head_img'] : '';
                $data_user['phone'] = $res['mobile'];
                $data_user['now_money'] = $res['data']['money_sum'];
                $data_user['score_count'] = $res['data']['points_sum'];

                $data_user['uid'] = $now_user['uid'];
                $this->userObj->saveUser($data_user);
                $now_user = $this->userObj->getUser(true, $condition_user);
            }
        }

        if(cfg('open_frozen_money') == 1) {
            if($now_user['frozen_time'] < $_SERVER['REQUEST_TIME'] && $_SERVER['REQUEST_TIME'] < $now_user['free_time']) {
                $now_user['now_money'] -= $now_user['frozen_money'];
                $now_user['now_money'] = $now_user['now_money']<0?0:$now_user['now_money'];
            }
        }

        $now_user['can_withdraw_money'] = floatval($now_user['now_money'])- floatval($now_user['score_recharge_money']);
        if($now_user['can_withdraw_money'] < 0){
            $now_user['can_withdraw_money'] = 0;
        }

        if(isset($now_user['phone']) && $now_user['phone']){
            if(strlen($now_user['phone']) == 11){
                $now_user['showPhone'] = substr($now_user['phone'],0,3).'****'.substr($now_user['phone'],7,4);
            }else if(strlen($now_user['phone']) == 10){
                $now_user['showPhone'] = substr($now_user['phone'],0,2).'****'.substr($now_user['phone'],7,4);
            }else if(strlen($now_user['phone']) == 9){
                $now_user['showPhone'] = substr($now_user['phone'],0,2).'****'.substr($now_user['phone'],7,3);
            }else if(strlen($now_user['phone']) == 8){
                $now_user['showPhone'] = substr($now_user['phone'],0,2).'***'.substr($now_user['phone'],7,3);
            }else if(strlen($now_user['phone']) == 7){
                $now_user['showPhone'] = substr($now_user['phone'],0,2).'***'.substr($now_user['phone'],7,2);
            }else{
                $now_user['showPhone'] = $now_user['phone'];
            }
            if($now_user['phone_country_type']){
                $now_user['showPhone'] = '+'.$now_user['phone_country_type'].' '.$now_user['showPhone'];
            }
        }else{
            $now_user['showPhone'] = '';
        }

        //处理头像
        if(!isset($now_user['avatar']) || !$now_user['avatar']){
            $now_user['avatar'] = cfg('site_url') . '/static/images/user_avatar.jpg';
        }else{
            $now_user['avatar'] = replace_file_domain($now_user['avatar']);
        }

        if(isset($now_user['email']) && $now_user['email']){
            $email_arr = explode('@',$now_user['email']);
            $now_user['showEmail'] = strlen($email_arr[0]) > 3 ? substr($email_arr[0],0,3) : (strlen($email_arr[0]) > 2 ? substr($email_arr[0],0,2) : substr($email_arr[0],0,1));
            $now_user['showEmail'].= '***@' . $email_arr[1];
        }else{
            $now_user['showEmail'] = '';
        }

        //判断生日是否正确
        if(isset($now_user['birthday']) && $now_user['birthday'] != '0000-00-00'){
            $birthYear = date('Y',strtotime($now_user['birthday']));
            if($birthYear < date('Y')-120){
                $now_user['birthday'] = '0000-00-00';
            }
        }

        $now_user['now_money'] = $now_user['now_money'] ?? 0;
        $now_user['now_money'] = get_format_number($now_user['now_money']);
        return $now_user->toArray();
    }

    /**
     * 账号密码检入
     * User: chenxiang
     * Date: 2020/5/26 19:28
     * @param string $phone
     * @param string $password
     * @param bool $type
     * @param bool $smscode
     * @param string $phone_country_type
     * @param bool $check_pwd
     * @return \json
     */
    public function checkIn($phone = '', $password = '', $type = false, $smscode = false, $phone_country_type = '', $check_pwd = true) {

        if(empty($phone)) {
            if($type) {
                return api_output_error(-1, '20042001');
            } else {
                return api_output_error(-1, '手机号不能为空');
            }
        }

        if (empty($pwd) && !$smscode && $check_pwd){
            return api_output_error(-1, '密码不能为空');
        }

        $sms_login_where = [];
        $sms_login_where['phone'] = $phone;
        if($phone_country_type && cfg('international_phone')){
            $sms_login_where['phone_country_type'] = $phone_country_type;
        }
        $now_user = $this->userObj->getUser(true, $sms_login_where);

        if($smscode && $now_user) {
            return api_output(1000, $now_user);
        }

        if ($now_user){
            $now_user = $this->getUser($now_user['uid']);
            //获取用户年龄
            $now_user['age'] = 0;
            $today_time = $_SERVER['REQUEST_TIME'];
            $birthday_time = strtotime($now_user['birthday']);
            if($now_user['birthday'] == '0000-00-00' || $birthday_time <= 0 || $today_time <= $birthday_time){
                $now_user['age'] = -1;
            }
            else{
                $now_user['age'] = ($today_time - $birthday_time)/(3600*24*365);
            }

            if($pwd && !$now_user['pwd']){
                return api_output_error(-1, '账号未设置密码，无法使用密码登录');
                }

            if($now_user['pwd'] != md5($pwd) && $now_user['pwd']){
                if($type){
                    return api_output_error(-1,'20120007');
                }else{
                    return api_output_error(-1,'密码不正确!');
                }
            }
            if(empty($now_user['status'])){
                if($type){
                    return api_output_error(-1,'20120008');
                }else{
                    return api_output_error(-1,'该账号被禁止登录!');
                }
            }
            if($now_user['status'] == 2){
                if($type){
                    return api_output_error(-1,'20120008');
                }else{
                    return api_output_error(-1,'该账号未审核，无法登录');
                }
            }

            $condition_save_user['uid'] = $now_user['uid'];
            $data_save_user['last_time'] = $_SERVER['REQUEST_TIME'];
            $data_save_user['last_ip'] = request()->ip();

            /****判断此用户是否在user_import表中***/
            $userImportService = new UserImportService();
            $user_import = $userImportService->getUserFromUserImport(['telphone'=>$phone, 'isuse'=>'0']);

            if(!empty($user_import)){
                !empty($user_import['ppname']) && $data_save_user['truename'] = $user_import['ppname'];
                $data_save_user['qq'] = $user_import['qq'];
                $data_save_user['email'] = $user_import['email'];
                $data_save_user['level'] = max($now_user['level'], $user_import['level']);
                $data_save_user['score_count'] = max($now_user['score_count'], $user_import['integral']);
                $data_save_user['now_money'] = max($now_user['now_money'], $user_import['money']);
                $data_save_user['importid'] = $user_import['id'];
                if(cfg('reg_verify_sms')){
                    $data_user['status'] = 1; //开启注册验证短信就不需要审核
                }else{
                    $data_user['status'] = 2; //未审核
                }
                $mer_id = $user_import['mer_id'];
                $data_save_user['openid'] = isset($_SESSION['weixin']) && isset($_SESSION['weixin']['user']) ? $_SESSION['weixin']['user']['openid'] : '';
                if(($mer_id>0) && !empty($data_save_user['openid'])){

                    $merchantUserRelationService = new MerchantUserRelationService();
                    $mwhere = [];
                    $mwhere['openid'] = $data_save_user['openid'];
                    $mwhere['mer_id'] = $mer_id;
                    $mtmp = $merchantUserRelationService->getDataMerUserRel($mwhere);
                    if(empty($mtmp)){
                        $mwhere['dateline'] = time();
                        $mwhere['from_merchant'] = 3;
                        $merchantUserRelationService->addMerUserRel($mwhere);
                    }
                }
            }

            $data_save_user['uid'] = $now_user['uid'];
            $save_res = $this->userObj->saveUser($data_save_user);

            if($save_res){
                if(!empty($user_import)){
                    if ($now_user['now_money']<$user_import['money']) {
                        // 增加余额记录
                        $userMoneyListService = new UserMoneyListService();
                        $userMoneyListService->addRow($now_user['uid'], 1, $user_import['now_money']-$now_user['now_money'], '增加余额',1);
                        //增加积分记录
                        $userScoreListService = new UserScoreListService();
                        $userScoreListService->addRow($now_user['uid'], 1, $user_import['integral']-$now_user['score_count'], '增加积分');
                    }

                    $userImportService->saveUserImport(['id'=>$user_import['id']], ['isuse'=>1]);
                }

                $houseVillageUserBindService = new HouseVillageUserBindService();
                $bind_where['uid'] = $now_user['uid'];
                $houseVillageUserBindService->saveData($bind_where, ['phone'=>input('post.phone')]);
            }
            return api_output(1000, ['user' => $now_user]);
        } else {
            if($type){
                return api_output_error(-1, '20120009');
            }else{
                return api_output_error(-1, '手机号不存在!');
            }
        }
    }

    /**
     * 手机号、union_id、open_id 直接登录入口
     * User: chenxiang
     * Date: 2020/5/26 20:24
     * @param string $field
     * @param string $value
     * @return \json
     */
    public function autoLogin($field = '',$value = '') {
        $now_user = $this->getUser($value, $field);
        if($now_user) {
            if(empty($now_user['status'])){
                return api_output_error(-1, '该账号被禁止登录');
            }
            if($now_user['status'] == 2){
                return api_output_error(-1, '该账号未审核，无法登录!');
            }
            if($now_user['status'] == 4){
                return api_output_error(-1, '该账号（'.$now_user['uid'].'）被禁止，无法登录!');
            }

            $condition_save_user['uid'] = $now_user['uid'];
            $data_save_user['last_time'] = $_SERVER['REQUEST_TIME'];
            $data_save_user['last_ip'] = request()->ip();
            $data_save_user['uid'] = $now_user['uid'];
            $this->userObj->saveUser($data_save_user);
            $this->checkScore($now_user['uid']);  //清空用户积分

            //获取用户年龄
            $now_user['age'] = 0;
            $today_time = $_SERVER['REQUEST_TIME'];
            $birthday_time = strtotime($now_user['birthday']);
            if($now_user['birthday'] == '0000-00-00' || $birthday_time <= 0 || $today_time <= $birthday_time){
                $now_user['age'] = -1;
            }
            else{
                $now_user['age'] = ($today_time - $birthday_time)/(3600*24*365);
            }
            return api_output(1000, ['user'=>$now_user]);
        } else {
            return api_output_error(1001, '没有此用户！');
        }
    }

    /**
     * 提供用户信息注册用户，密码需要自行md5处理
     * **** 请自行处理逻辑，此处直接插入用户表 ****
     * User: chenxiang
     * Date: 2020/5/28 19:13
     * @param $data_user
     * @param bool $is_app
     * @return \json
     */
    public function autoReg($data_user, $is_app = false ,$isreturn=false) {
        //针对抖音用户处理
        if (isset($data_user['dy_unionid'])) {
            if (empty($data_user['dy_unionid'])) {
                throw new Exception('参数有误');
            }
            $res = $this->userObj->getUser(true, ['dy_unionid' => $data_user['dy_unionid']]);
            if ($res) {
                if ($res['status'] == 0) {
                    throw new Exception('您已被禁止登录');
                }
                $uid = $res->uid;
            } else {
                $data_user['add_time'] = $data_user['last_time'] = $_SERVER['REQUEST_TIME'];
                $data_user['add_ip'] = $data_user['last_ip'] = request()->ip();
                $data_user['status'] = 1;
                $data_user['client'] = 1;
                $data_user['score_clean_time'] = $_SERVER['REQUEST_TIME'];
                $uid = $this->userObj->addUser($data_user);
            }
            $user = $this->userObj->where('uid', $uid)->field('uid,nickname,phone,dy_openid,dy_unionid,status')->findOrEmpty()->toArray();
            return $user;
        }
        
        if (isset($data_user['openid']) && $data_user['openid']) {
            $res = $this->userObj->getUser(true, ['openid' => $data_user['openid']]);
            if ($res['status'] == 0) {
                return api_output_error(-1, '您已被禁止登录');
            }
            return api_output_error(-1, '您已经注册过了');
        }
        
        $data_user['add_time'] = $data_user['last_time'] = $_SERVER['REQUEST_TIME'];
        $data_user['add_ip'] = $data_user['last_ip'] = request()->ip();
        $data_user['status'] = 1;
        $data_user['score_clean_time'] = $_SERVER['REQUEST_TIME'];

        if (isset($data_user['openid']) && $data_user['openid']) {
            $data_user['last_weixin_time'] = $_SERVER['REQUEST_TIME'];
        }
        if (isset($data_user['union_id']) && $data_user['union_id']) {
            $res = $this->userObj->getUser(true, ['union_id' => $data_user['union_id']]);
            if ($res) {
                $this->saveUserData($res['uid'], 'openid', $data_user['openid']);
                if ($res['status'] == 0) {
                    return api_output_error(-1, '您已被禁止登录');
                }
                if($isreturn){
                    return  ['uid' => $res['uid']];
                }
                return api_output(1000, ['uid' => $res['uid']]);
            }
        }
        /****判断此用户是否在user_import表中***/
        $userImportService = new UserImportService();
        $user_import = $userImportService->getUserFromUserImport(['telphone' => $data_user['phone'], 'isuse' => '0']);
        if (!empty($user_import)) {
            $data_user['truename'] = $user_import['ppname'];
            $data_user['qq'] = $user_import['qq'];
            $data_user['email'] = $user_import['email'];
            $data_user['level'] = $user_import['level'];
            $data_user['score_count'] = $user_import['integral'];
            $data_user['now_money'] = $user_import['money'] ? $user_import['money'] : 0;
            $data_user['importid'] = $user_import['id'];
            $data_user['youaddress'] = !empty($user_import['address']) ? str_replace('|', ' ', $user_import['address']) : '';
            // if($this->config['reg_verify_sms']){
            // 	$data_user['status'] = 1; //开启注册验证短信就不需要审核
            // }else{
            // 	$data_user['status'] = 2; /*             * *未审核*** */

            // }
            // $data_user['now_money'] = 0;
        }
        if ($uid = $this->userObj->addUser($data_user)) {
            if (!empty($user_import)) {
                $userImportService->saveUserImport(['id' => $user_import['id']], ['isuse' => 2]);
            }
            $this->registerGiveMoney($uid, $is_app);
            $spread_user_give_type = cfg('spread_user_give_type');
            if ($spread_user_give_type != 3 && !empty($_SESSION['openid'])) {
                $userSpreadService = new UserSpreadService();
                $now_user_spread = $userSpreadService->getUserSpreadData(true, ['uid' => $uid]);
                if ($now_user_spread) {
                    $spread_user = $this->getUser($now_user_spread['spread_uid']);
                    //待定....
                    $now_level = M(_view('User_level'))->where(array('level' => $spread_user['level']))->find();

                    if ($spread_user_give_type == 0 || $spread_user_give_type == 2) {
                        $spread_give_money = $now_level['spread_user_give_moeny'] > 0 ? $now_level['spread_user_give_moeny'] : cfg('spread_give_money');
                        if (cfg('open_score_fenrun')) {
//                            $this->addMoney($spread_user['uid'], $spread_give_money, L_('推荐新用户注册平台赠送余额'), '', '', $uid);
                            //$this->addMoney($spread_user['uid'], $spread_give_money, '推荐新用户注册平台赠送余额', '', '', $uid);
                        } else {
//                            $this->addMoney($spread_user['uid'], $spread_give_money, L_('推荐新用户注册平台赠送余额'));
                            //$this->addMoney($spread_user['uid'], $spread_give_money, '推荐新用户注册平台赠送余额');
                        }
                        $scrollMsgService = new ScrollMsgService();
                        //待定...
                        //$scrollMsgService->addMsg('spread_reg', $spread_user['uid'], L_("用户X1于X2推荐新用户注册获赠平台余额X3", array("X1" => str_replace_name($spread_user['nickname']), "X2" => date('Y-m-d H:i', $_SERVER['REQUEST_TIME']), "X3" => $spread_give_money . cfg('Currency_txt'))));
                    }

                    if ($spread_user_give_type == 1 || $spread_user_give_type == 2) {
                        $spread_give_score = $now_level['spread_user_give_score'] > 0 ? $now_level['spread_user_give_score'] : C('config.spread_give_score');
                        $this->addScore($spread_user['uid'], $spread_give_score, L_('推荐新用户注册平台赠送') . cfg('score_name'));
                        if ($spread_give_score > 0) {
                            $scrollMsgService->addMsg('spread_reg', $spread_user['uid'], L_("用户X1于X2推荐新用户注册获赠X3个", array("X1" => str_replace_name($spread_user['nickname']), "X2" => date('Y-m-d H:i', $_SERVER['REQUEST_TIME']), "X3" => cfg('score_name') . $spread_give_score)));
                        }
                    }

                }
            }
            if($isreturn){
                return  ['uid' => $uid];
            }
            return api_output(1000, ['uid' => $uid]);
        } else {
            return api_output_error(-1, '注册失败！请重试。');
        }
    }

    /*账号密码注册*/
    public function checkReg($phone,$pwd,$phone_country_type=''){
        if (empty($phone)) {
            return api_output_error(-1, L_('手机号不能为空'));
        }
        if (empty($pwd)) {
            return api_output_error(-1, L_('密码不能为空'));
        }

        if(!cfg('international_phone') && !preg_match('/^[0-9]{11}$/',$phone)){
            return api_output_error(-1, L_('请输入有效的手机号'));
        }
        $scrollMsgService = new ScrollMsgService();

        $condition_user['phone'] = $phone;
        if(cfg('international_phone')){
            $condition_user['phone_country_type'] = $phone_country_type;
            $condition_user['phone'] = phone_format($phone_country_type, $condition_user['phone']);
        }

        if($this->userObj->getUser('uid', $condition_user)){
            return api_output_error(-1, L_('手机号已存在'));
        }

        $data_user['phone'] = $phone;
        $data_user['pwd'] = md5($pwd);
        $data_user['status'] = 1;
        $data_user['nickname'] = substr($phone,0,3).'****'.substr($phone,7);

        $data_user['add_time'] = $data_user['last_time'] = $_SERVER['REQUEST_TIME'];
        $data_user['add_ip'] = $data_user['last_ip'] = request()->ip();
        $data_user['score_clean_time'] = $_SERVER['REQUEST_TIME'];
        $phone_country_type && $data_user['phone_country_type'] =$phone_country_type;
        if($uid = $this->userObj->addUser($data_user)){
            $this->registerGiveMoney($uid);

            $spread_user_give_type = cfg('spread_user_give_type');

            if($spread_user_give_type != 3 && !empty($_SESSION['openid'])){
                $userSpreadService = new UserSpreadService();
                $now_user_spread = $userSpreadService->getUserSpreadData(true, ['uid'=>$uid]);
                if($now_user_spread) {
                    $spread_user = $this->getUser($now_user_spread['spread_uid']);
                    //待定...
                    $now_level = M(_view('User_level'))->where(array('level' => $spread_user['level']))->find();
                    if ($spread_user_give_type == 0 ||$spread_user_give_type == 2) {
                        $spread_give_money = $now_level['spread_user_give_moeny']>0?$now_level['spread_user_give_moeny']: cfg('spread_give_money');
                        if(cfg('open_score_fenrun')){
                            //$this->addMoney($spread_user['uid'],  $spread_give_money, L_('推荐新用户注册平台赠送余额'),'','',$uid);
                        }else{
                            //$this->addMoney($spread_user['uid'],  $spread_give_money, L_('推荐新用户注册平台赠送余额'));
                        }

                        //$scrollMsgService->addMsg('spread_reg',$spread_user['uid'],L_("用户X1于X2推荐新用户注册获赠平台余额X3",array("X1" => str_replace_name($spread_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => $spread_give_money.cfg('Currency_txt'))));
                    }

                    if ($spread_user_give_type == 1 || $spread_user_give_type == 2) {
                        $spread_give_score = $now_level['spread_user_give_score']>0?$now_level['spread_user_give_score']: cfg('spread_give_score');
                        $this->addScore($spread_user['uid'], $spread_give_score, L_('推荐新用户注册平台赠送') . cfg('score_name'));
                        if($spread_give_score>0){
                            $scrollMsgService->addMsg('spread_reg',$spread_user['uid'],L_("用户X1于X2推荐新用户注册获赠X3个",array("X1" => str_replace_name($spread_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => cfg('score_name').$spread_give_score)));
                        }
                    }

                }
            }
            $return = $this->checkIn($phone,$pwd,false,false,$phone_country_type);

            if(empty($result['error_code'])){
                return $return;
            }else{
                return api_output(1000);
            }
        }else{
            return api_output_error(-1, L_('注册失败！请重试。'));
        }
    }

    /**
     * 检查手机号是否存在
     * User: chenxiang
     * Date: 2020/5/28 20:28
     * @param $phone
     * @return \json
     */
    public function checkPhone($phone){
        $condition_user['phone'] = $phone;
        if($this->userObj->getUser('uid', $condition_user)){
            return api_output_error(-1, L_('手机号已存在'));
        }
    }

//    /**
//     * 修改用户信息
//     * User: chenxiang
//     * Date: 2020/5/28 20:32
//     * @param $uid
//     * @param $field
//     * @param $value
//     * @return \json
//     */
//    public function saveUser($uid, $field, $value){
//        if(!$uid){
//            return api_output_error(-1, L_('请求参数必须携带 uid'));
//        }
//        $condition_user['uid'] = $uid;
//        $data_user[$field] = $value;
//
//        $data_user['uid'] = $uid;
//        if($this->userObj->saveUser($data_user)){
//            return api_output(1000,[$field=>$value]);
//        }else{
//            return api_output_error(-1, L_('修改失败！请重试。'));
//        }
//    }
    /**
     * 更新用户数据
     * User: chenxiang
     * Date: 2020/6/15 12:02
     * @param $uid
     * @param $data
     * @return \json
     */
    public function UpdateUser($where, $data){
        if(empty($where) || !is_array($where)){
            return false;
        }

        $save = $this->userObj->updateValue($where, $data);
        if($save){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 修改用户信息
     * User: chenxiang
     * Date: 2020/5/28 20:33
     * @param $where
     * @param $data
     * @return int
     */
    public function scenicSaveUser($where, $data){
        if(empty($where)){
            return 0;
        }
        if(!is_array($where)){
            return 0;
        }
        $save = $this->userObj->updateValue($where, $data);
        if($save){
            return 1;
        }else{
            return 0;
        }
    }


    //清空用户积分
    public function checkScore($uid) {
        if(cfg('open_score_clean')){
            if (!empty($uid)){
                $now_user = $this->getUser($uid);
                $clean_score_type = cfg('clean_score_type');
                $clean_type = $clean_score_type == 1 ? 'score_count' : 'score_extra_count';

                if ($now_user[$clean_type] <= 0) {
                    $upd_data[$clean_type] = 0;
                    $this->userObj->updateValue(['uid' => $uid],$upd_data);
                }

                //多语言  【待定...】
                $now_level = M(_view('User_level'))->where(array('id' => $now_user['level']))->find();

                $config_score_clean_time = cfg('score_clean_time');
                $clean_time = strtotime(date('Y') . '-' . ($now_level['score_clean_time'] != '' ? $now_level['score_clean_time'] : $config_score_clean_time));
                $clean_percent = ($now_level['score_clean_percent'] > 0) ? $now_level['score_clean_percent'] : $config_score_clean_time;
                $time = $_SERVER['REQUEST_TIME'];
                $time_Ymd = strtotime(date('Y-m-d', $time));

                if ($now_user['score_clean_time'] != 0 && date('Y', $time) > date('Y', $now_user['score_clean_time'])) { //下一年 一年一次 过期不减
                    $now_user['score_clean_time'] = 0;
                }

                if ($time_Ymd >= $clean_time && $clean_time && $now_user['score_clean_time'] == 0) {
                    $dec_score = round($now_user[$clean_type] * $clean_percent / 100, 2);  //四舍五入
                    $this->userScore($now_user['uid'], $dec_score, L_('积分到期清理，日期：') . date('Y-m-d', $time));
                    $this->userObj->saveUser(['uid' => $now_user['uid']], ['score_clean_time'=>$time]);
                }
            }
        }
    }

    /*使用用户的积分   【待定....】   */
    public function userScore($uid,$score,$desc,$type=2,$scoreParam=array()){
        if($score>0){
            // if(cfg('mdd_api_url')){
            //     // mdd 用户查询
            //     import('@.ORG.mdd_user');
            //     $mdd_user = new mdd_user();
            //     $mdd_result = $mdd_user->select($uid);
            //     if($mdd_result['error_code']){
            //         return array('error_code' => true, 'msg' => $mdd_result['msg']);
            //     }
            //     // mdd 用户查询完

            //     // mdd 消耗积分
            //     import('@.ORG.mdd_user');
            //     $mdd_user = new mdd_user();
            //     $mdd_result = $mdd_user->use_score($uid,$score,$desc);
            //     if($mdd_result['error_code']){
            //         return array('error_code' => true, 'msg' => $mdd_result['msg']);
            //     }
            //     // mdd 消耗积分完
            // }

            // if(cfg('uni_url')){
            //     import('@.ORG.uni_user');
            //     $uni_user = new uni_user();
            //     $uni_result = $uni_user->select($uid);
            //     if($uni_result['error_code']){
            //         return array('error_code' => true, 'msg' => $uni_result['msg']);
            //     }

            //     import('@.ORG.uni_user');
            //     $uni_user = new uni_user();
            //     $uni_result = $uni_user->use_score($uid,$score,$desc);
            //     if($uni_result['error_code']){
            //         return array('error_code' => true, 'msg' => $uni_result['msg']);
            //     }
            // }

            $now_user = $this->getUser($uid);
            if($now_user['score_count']<$score){
                return array('error_code' => true, 'msg' => L_("减少X1失败！请联系管理员协助解决。",array("X1" => cfg('score_name'))));
            }
            $condition_user['uid'] = $uid;
            $score_res = $this->userObj->setDec($condition_user,'score_count',$score);
            if($score_res){

                $dec_extra_score = $now_user['score_extra_count']<$score?$now_user['score_extra_count']:$score; 
                $this->userObj->setDec($condition_user,'score_extra_count',$dec_extra_score); //同时减少
                $userScoreListService = new UserScoreListService();
                $userScoreListService->addRow($uid,2,$score,$desc,true,false,0,$scoreParam);

                // //执行减少用户积分后的钩子
                // $hook_result = hook::hook_exec('user.use_user_score_after',['uid'=>$now_user['uid'],'user'=>$now_user,'score'=>$score,'desc'=>$desc]);

                // if(cfg('nmgzhcs_appid')){
                //     if($now_user['nmgzhcs_openid']){
                //         $this->nmgzhcs_score($now_user['nmgzhcs_openid'],-1 * $score,$desc);
                //     }
                // }

                return array('error_code' =>false,'msg' =>cfg('score_name').L_('回退成功！'));
            }else{
                return array('error_code' => true, 'msg' => L_("减少X1失败！请联系管理员协助解决。",array("X1" => cfg('score_name'))));
            }
        }else{
            return array('error_code' => true, 'msg' => cfg('score_name').L_('数据有误'));
        }
    }

    /**
     * 这个方法增加的积分到一定时间会清零
     * User: chenxiang
     * Date: 2020/5/29 14:50
     * @param $uid
     * @param $score
     * @param $desc
     * @return \json
     */
    public function addExtraScore($uid,$score,$desc){
        if($score>0){
            $condition_user['uid'] = $uid;
            if ($this->userObj->setInc($condition_user, 'score_extra_count', $score) && $this->userObj->setInc($condition_user, 'score_count', $score)) { //积分 跟 奖励积分同时增加，清理的时候同时减少
                //待定...
//                PD('User_score_list')->add_row($uid,1,$score,$desc);
                $userScoreListService = new UserScoreListService();
                $userScoreListService->addRow($uid, 1, $score, $desc);

                return api_output(1000, [], cfg('score_name').L_('回退成功！'));
            }else{
                return api_output_error(-1, L_("添加X1失败！请联系管理员协助解决。",array("X1" => cfg('score_name'))));
            }
        }else{
            return api_output_error(-1, cfg('score_name').L_('数据有误'));
        }
    }


    /**
     * 检测是否是新用户
     * User: chenxiang
     * Date: 2020/6/16 16:43
     * @param $uid
     * @param $cate_name
     * @return bool
     */
    public function checkNew($uid, $cate_name)
    {
        $user = $this->userObj->getUser('uid', ['phone'=>$uid]);
        if(empty($user)){
            $user = $this->userObj->getUser('uid', ['uid'=>$uid]);
        }
//        $m = new Model();

        $db = $this->getDb();
        $count = 0;
        switch ($cate_name) {
//            case 'all':
//                foreach ($db as $v) {
//                    $new_db = $v['db'];
//                    $where = array();
//                    $where['uid'] = $user['uid'];
//                    $where[$v['name']] = $v['condition'];
//                    $count += $m->table($new_db)->where($where)->count('order_id');
//                }
//                break;
            case 'group':
                $new_db = $db['group'];
                $where = array();
                $where['uid'] = $user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
//                $count = $m->table($new_db['db'])->where($where)->count('order_id');
                $groupService = new GroupService();
//                $count = $groupService->
                break;
            case 'meal':
                $new_db = $db['meal'];
                $where = array();
                $where['uid'] = $user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
//                $count = $m->table($new_db['db'])->where($where)->count('order_id');
                break;
            case 'appoint':
                $new_db = $db['appoint'];
                $where = array();
                $where['uid'] = $user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
//                $count = $m->table($new_db['db'])->where($where)->count('order_id');
                break;
            case 'shop':
                $new_db = $db['shop'];
                $where = array();
                $where['uid'] = $user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
                $where['order_from'] = ['neq', 1];//不是商城的订单
//                $count = $m->table($new_db['db'])->where($where)->count('order_id');
                break;
            case 'foodshop':
                $new_db = $db['foodshop'];
                $where = array();
                $where['uid'] = $user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
//                $count = $m->table($new_db['db'])->where($where)->count('order_id');
                break;
            case 'village_group':
                $new_db = $db['village_group'];
                $where = array();
                $where['uid'] = $user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
//                $count = $m->table($new_db['db'])->where($where)->count('order_id');
                break;
//            case 'all':
//                $new_db = $db['store'];
//                $where = array();
//                $where['uid'] = $user['uid'];
//                $where[$new_db['name']] = $new_db['condition'];
////                $count = $m->table($new_db['db'])->where($where)->count('order_id');
//                break;
        }

//        if($count>0){
//            return true;
//        }else{
//            return false;
//        }

            return true;

    }


    /**
     * 获取不同类型订单状态（存在这种订单状态的才算不是新用户）
     * User: chenxiang
     * Date: 2020/5/29 11:04
     * @return array[]
     */
    protected function getDb(){
        return array(
            'group' => array(
                'db' =>cfg('DB_PREFIX').'group_order',
                'name'=>'status',
                'condition'=>array('between',array('1','6'))
            ),
            'meal' => array(
                'db'=>cfg('DB_PREFIX').'meal_order_log',
                'name'=>'status',
                'condition'=>array('eq',2),
            ),
            'appoint' => array(
                'db'=>cfg('DB_PREFIX').'appoint_order',
                'name'=>'service_status',
                'condition'=>array('between',array('1','2'))
            ),
            'shop' => array(
                'db'=>cfg('DB_PREFIX').'shop_order',
                'name'=>'status',
                'condition'=>array('between',array('2','4')),
            ),
            'foodshop' => array(
                'db'=>cfg('DB_PREFIX').'foodshop_order',
                'name'=>'status',
                'condition'=>array('between',array('3','4'))
            ),
            'store' =>array(
                'db'=>cfg('DB_PREFIX').'store_order',
                'name'=>'paid',
                'condition'=>array('eq',1)
            ),
            'village_group' =>array(
                'db'=>cfg('DB_PREFIX').'village_group_order',
                'name'=>'status',
                'condition'=>array('in',[1,2,3,5,6,7,8,13])
            ),
        );
    }

    /**
     * 校验可使用的积分
     * User: chenxiang
     * Date: 2020/6/9 14:51
     * @param $uid
     * @param $money
     * @param $order_type
     * @param int $group_id
     * @param int $mer_id
     * @return array
     */
    public function checkScoreCanUse($uid, $money, $order_type, $group_id = 0, $mer_id = 0)
    {
        $now_user = $this->getUser($uid);
        $score_count = $now_user['score_count'];
        $score_can_use_count = 0;
        $score_deducte = 0;
        if ($order_type == 'group' || $order_type == 'meal' || $order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad' || $order_type == 'dining') {
            $user_score_use_condition = cfg('user_score_use_condition');
            $percentRateService = new MerchantPercentRateService();
            $user_score_max_use = $percentRateService->getMaxScoreUse($mer_id, $order_type, $money);//不同业务不同积分
            if ($order_type == 'group') {
                $groupService = new GroupService();
                $group_info = $groupService->getOne(['group_id' => $group_id]);

                if (isset($group_info['score_use']) && $group_info['score_use']) {
                    if ($group_info['group_max_score_use'] != 0) {
                        $user_score_max_use = $group_info['group_max_score_use'];
                    }
                } else {
                    $user_score_max_use = 0;
                }
            }
            $user_score_use_percent = (float)cfg('user_score_use_percent');
            $score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

            if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0) {   //如果设置没有错误
                if ($money >= $user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
                    if ($money > $score_max_deducte) {                    //判断积分最大抵扣金额是否比这个订单的总额大
                        $score_can_use_count = (int)($score_count > $user_score_max_use ? $user_score_max_use : $score_count);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
                        $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                        $score_deducte = $score_deducte > $money ? $money : $score_deducte;
                    } else {
                        //最大可抵扣的金额比总单金额大 只扣掉总单范围内的积分 扣除积分=总单*积分抵扣比例
                        $score_can_use_count = ceil($money * $user_score_use_percent);
                        $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                        $score_deducte = $score_deducte > $money ? $money : $score_deducte;
                    }
                }
            }
        }
        return array('score' => $score_can_use_count, 'score_money' => floatval($score_deducte));
    }

    /**
     * 通过手机号获取用户信息
     * User: chenxiang
     * Date: 2020/5/29 14:03
     * @param $phone
     * @return mixed
     */
    public function getUserByPhone($phone){
        $now_user = $this->userObj->getUser(true, ['phone'=>$phone]);
        return $now_user['uid'];
    }


    /**
     * 按出生年月算年龄
     * User: chenxiang
     * Date: 2020/5/29 14:05
     * @param $birthday
     * @return false|int|string
     */
    public function age($birthday)
    {
        if (empty($birthday)) {
            return '';
        }
        $age = date('Y', time()) - date('Y', strtotime($birthday)) - 1;
        if (date('m', time()) == date('m', strtotime($birthday))) {
            if (date('d', time()) > date('d', strtotime($birthday))) {
                $age++;
            }
        } elseif (date('m', time()) > date('m', strtotime($birthday))) {
            $age++;
        }
        return $age;
    }

    /**
     * 用户签到功能
     * User: chenxiang
     * Date: 2020/5/29 14:23
     * @param $uid
     * @return \json
     */
    public function checkSignToday($uid)
    {
        $userSignService = new UserSignService();
        $recently_sign = $userSignService->getUserSignData(['uid' => $uid], true, 'id DESC');
        if ($recently_sign && strtotime(date('Ymd', $_SERVER['REQUEST_TIME'])) == strtotime(date('Ymd', $recently_sign['sign_time']))) {
            return api_output(1000, L_('已经签到了'));
        } else {
            return api_output_error(-1, L_('今天没签到'));
        }
    }


    /**
     * 签到功能
     * User: chenxiang
     * Date: 2020/5/29 14:48
     * @param $uid
     * @return \json
     */
    public function signIn($uid)
    {
        if (cfg('sign_get_score') == '0' || empty(cfg('sign_get_score'))) {
            return api_output(1000, [], "签到成功！");
        }
        $userSignService = new UserSignService();
        $recently_sign = $userSignService->getUserSignData(['uid' => $uid], true, 'id DESC');
        $now_user = $this->getUser($uid);
        if (empty($now_user['phone'])) {
            return api_output_error(-1, L_('您未绑定手机，请绑定手机'));
        }
        if ($recently_sign && strtotime(date('Ymd', $_SERVER['REQUEST_TIME'])) == strtotime(date('Ymd', $recently_sign['sign_time']))) {
            return api_output_error(-1, L_('同一天只能签到一次，请明天再来！'));
        }
        if (cfg('sign_get_score') <= 0) {
            return api_output_error(-1, L_('抱歉，当前不能使用签到功能！'));
        }

        if ($recently_sign && (strtotime(date('Ymd', $_SERVER['REQUEST_TIME'])) - strtotime(date('Ymd', $recently_sign['sign_time']))) > 86400) {
            $score_get = cfg('sign_get_score');
            $sign_day = 1;
        } elseif ($recently_sign && $recently_sign['sign_id'] > 0) {
            $score_get = cfg('sign_get_score');
            $sign_day = 1;
        } else {
            $score_get = (($recently_sign['day'] + 1) % 30 === 0 ? 30 : ($recently_sign['day'] + 1) % 30) + cfg('sign_get_score') - 1;
            $sign_day = $recently_sign['day'] + 1;
        }
        $data['uid'] = $uid;
        $data['day'] = $sign_day;
        $data['score_count'] = $score_get;
        $data['sign_time'] = $_SERVER['REQUEST_TIME'];
        $userSignService->addUserSign($data);
        $this->addExtraScore($uid, $score_get, L_("第X1天签到获得X2个X3", array("X1" => $sign_day, "X2" => $score_get, "X3" => C('config.score_name'))));
        $scrollMsgService = new ScrollMsgService();
        $scrollMsgService->addMsg('sign', $uid, L_("用户X1于X2签到获得X3个X4", array("X1" => str_replace_name($now_user['nickname']), "X2" => date('Y-m-d H:i', $_SERVER['REQUEST_TIME']), "X3" => $score_get, "X4" => cfg('score_name'))));
        $return_msg = L_("签到成功!获得X1个X2", array("X1" => $score_get, "X2" => cfg('score_name')));
        if ($sign_day > 1) {
            $return_msg = L_("连续X1天签到!获得X2个X3", array("X1" => $sign_day, "X2" => $score_get, "X3" => cfg('score_name')));
        }
        return api_output(1000, $return_msg);
    }

    /**
     * 自定义签到接口
     * User: chenxiang
     * Date: 2020/5/29 15:05
     * @param $uid
     * @param $sign_list
     * @return \json
     */
    public function customSignIn($uid, $sign_list)
    {
        $userSignService = new UserSignService();
        $recently_sign = $userSignService->getUserSignData(['uid'=>$uid], true, 'id DESC');
        $now_user = $this->getUser($uid);
        if (empty($now_user['phone'])) {
            return api_output_error(2, L_('您未绑定手机，请绑定手机'));
        }
        if ($recently_sign && strtotime(date('Ymd', $_SERVER['REQUEST_TIME'])) == strtotime(date('Ymd', $recently_sign['sign_time']))) {
            return api_output_error(-1, L_('同一天只能签到一次，请明天再来！'));
        }
        if (cfg('sign_get_score') <= 0) {
            return api_output_error(-1, L_('抱歉，当前不能使用签到功能！'));
        }

        if ($recently_sign && (strtotime(date('Ymd', $_SERVER['REQUEST_TIME'])) - strtotime(date('Ymd', $recently_sign['sign_time']))) > 86400) {
            $sign_day = 1;
            $tsign_day = 1;
        } else {
            $max_day = count($sign_list);
            if ($recently_sign['tday'] > $max_day) {
                $tsign_day = 1;
            } else {
                $tsign_day = $recently_sign['tday'] + 1;
                //最大天数区域获取实际循环签到天数
                $tsign_day = $tsign_day % $max_day;
                $tsign_day = $tsign_day == 0 ? $max_day : $tsign_day;
            }
            $sign_day = $recently_sign['day'] + 1;

            //如果连续签到的天数已经达到系统后台设置的最大天数，那么签到天数还是1,tapd bugID1008215
            if ($recently_sign['day'] >= $max_day) {
                $sign_day = 1;
                $tsign_day = 1;
            }
        }
        $data['uid'] = $uid;
        $data['day'] = $sign_day;
        $data['tday'] = $tsign_day;
        $sign_info = $sign_list[$tsign_day - 1];
        $data['sign_id'] = $sign_info['id'];
        $reward_arr = json_decode($sign_info['reward_info'], true);
        $sign_info['reward_arr'] = $reward_arr;
        if ($sign_info['type'] == 1) {
            $score_get = $reward_arr['jifen'];
            $data['score_count'] = $score_get;
        }

        $systemCouponService = new SystemCouponService();
        if ($sign_info['type'] == 2) {
            $coupon_id = $reward_arr['coupon_id'];

            $result = $systemCouponService->hadPull($coupon_id, $uid);
//            $model = D('System_coupon');
//            $result = $model->had_pull($coupon_id, $uid);
            if ($result['errcode'] != 0) {
                return api_output_error(-1, L_('签到失败，请重试！'));
            }
            $reduce_num = $result['coupon']['reduce_num'];
            $reduce_num = $reduce_num > 0 ? $reduce_num : 1;
            $systemCouponService->decreaseSku(0, $reduce_num, $coupon_id);//网页领取完，微信卡券库存需要同步减少
            $data['coupon_id'] = $coupon_id;
        }
        $data['sign_time'] = $_SERVER['REQUEST_TIME'];

        $userSignService->addUserSign($data);

        $scrollMsgService = new ScrollMsgService();
        if ($sign_info['type'] == 1) {
            $this->addExtraScore($uid, $score_get, L_("第X1天签到获得X2个X3", array("X1" => $sign_day, "X2" => $score_get, "X3" => cfg('score_name'))));

            $scrollMsgService->addMsg('sign', $uid, L_("用户X1于X2签到获得X3个X4", array("X1" => str_replace_name($now_user['nickname']), "X2" => date('Y-m-d H:i', $_SERVER['REQUEST_TIME']), "X3" => $score_get, "X4" => cfg('score_name'))));
            $return_msg = L_("签到成功!获得X1个X2", array("X1" => $score_get, "X2" => cfg('score_name')));
            if ($sign_day > 1) {
                $return_msg = L_("连续X1天签到!获得X2个X3", array("X1" => $sign_day, "X2" => $score_get, "X3" => cfg('score_name')));
            }
        } else {
            $coupon_info = $systemCouponService->getCoupon(['coupon_id' => $coupon_id]);
            $coupon_info['day'] = floor(($coupon_info['end_time'] - $coupon_info['start_time']) / 86400);
            $sign_info['coupon_info'] = $coupon_info;
            $scrollMsgService->addMsg('sign', $uid, L_("用户X1于X2签到获得平台优惠券（X3）", array("X1" => str_replace_name($now_user['nickname']), "X2" => date('Y-m-d H:i', $_SERVER['REQUEST_TIME']), "X3" => $coupon_info['name'])));
            $return_msg = L_('签到成功!获得平台优惠券');
            if ($sign_day > 1) {
                $return_msg = L_("连续X1天签到!获得平台优惠券", array("X1" => $sign_day));
            }
        }
        return api_output(1000, ['sign_info'=>$sign_info]);
    }

    /**
     * 今天签到人数
     * User: chenxiang
     * Date: 2020/6/1 10:50
     * @return mixed
     */
    public function signNumToday(){
        $today = date('Y-m-d',$_SERVER['REQUEST_TIME']);
        $today_start = strtotime($today.' 00:00:00');
        $today_end = strtotime($today.' 23:59:59');
        $where['_string'] = 'sign_time > '.$today_start.' OR sign_time < '.$today_end;
        $userSignService = new UserSignService();
        $today_sign_num = $userSignService->countNum($where);
        return $today_sign_num;
    }


    public function clean_canel_userlist(){
        $this->join('AS u LEFT JOIN '.C('DB_PREFIX').'user_level AS l ON l.level = u.level_id')->where(array('score_clean_notice_time'=>0))->limit(10)->select();
    }

    /**
     * 合并用户
     * User: chenxiang
     * Date: 2020/6/1 11:25
     * @param $uid
     * @param $old_uid
     */
    public function mergeUserFromNew($uid,$old_uid){
        if(cfg('open_score_get_percent')==1){
            $score_get = cfg('score_get_percent')/100;
        }else{
            $score_get = cfg('user_score_get');
        }

        $where['uid'] =$uid;
        $where['paid'] =1;
        $storeOrderService = new StoreOrderService();
        $field = '(SUM(balance_pay)+SUM(payment_money*(SIGN(0-is_own)+1)) +SUM(merchant_balance)) AS paymoney';
        $arr[] = $storeOrderService->getList($field, $where, 'uid');

        $where['_string'] = 'status = 2 OR status = 3';
        $shopOrderService = new ShopOrderService();
        $field = '(SUM(balance_pay) +SUM(payment_money*(SIGN(0-is_own)+1)) +SUM(merchant_balance) )AS paymoney';
        $arr[] = $shopOrderService->getList($field, $where, 'uid');

        //unset($where['_string']);
        //$where['business_type']='foodshop';
        //$arr[] = M('Plat_order')->field('(SUM(system_balance)+SUM(pay_money*(SIGN(0-is_own)+1)) +SUM(merchant_balance_pay)) AS paymoney')->where($where)->group('uid')->select();
        $all_money =0;

        foreach ($arr as $item) {
            $all_money+=$item[0]['paymoney'];
        }
        $merge_score = round($all_money*$score_get);
        $this->addScore($old_uid,$merge_score,L_("来自合并账号ID：X1返还积分",array("X1" => $uid)));
        //return $all_money;

    }

    /**
     * 获取微信用户信息
     * User: chenxiang
     * Date: 2020/6/1 11:58
     * @param $openid
     * @return bool|mixed
     */
    public function getUserinfoWeixin($openid){
        import('ORG.Net.Http');
        $http = new Http();
        $access_token_array = D('Access_token_expires')->get_access_token();
        if (!$access_token_array['errcode']) {
            $return = $http->curlGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token_array['access_token'].'&openid='.$openid.'&lang=zh_CN');
            $userifo = json_decode($return,true);
            if(empty($userifo)){
                return false;
            }else{
                return $userifo;
            }
        }
        return false;
    }

    /**
     * 获取wxapp用户信息
     * User: chenxiang
     * Date: 2020/6/1 13:15
     * @param string $code
     * @return bool|mixed
     */
    public function getUserinfoxapp($code=''){
        $appid = cfg('pay_wxapp_appid');
        $appsecret = cfg('pay_wxapp_appsecret');

        import('ORG.Net.Http');
        $http = new Http();

        $return = Http::curlPost('https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$code.'&grant_type=authorization_code', array());

        import('@.ORG.aeswxapp.wxBizDataCrypt');

        $pc = new WXBizDataCrypt($appid, $return['session_key']);
        $errCode = $pc->decryptData($_POST['encryptedData'],$_POST['iv'],$data);
        $jsonrt = json_decode($data,true);
        if(empty($jsonrt) || $jsonrt['errcode']){
            return false;
        }else{
            return $jsonrt;
        }
    }

    /**
     * 获取单个用户信息
     * User: chenxiang
     * Date: 2020/6/1 13:18
     * @param $field
     * @param $where
     * @return bool
     */
    public function userFind($field, $where)
    {
        $result = $this->userObj->getUser($field, $where);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 获取 youshanzhu token值
     * User: chenxiang
     * Date: 2020/6/1 13:22
     */
    public function get_youshanzhu_token(){
        $url = cfg('youshanzhu_login_url').'/plugins/login_wx_client/index/index?pig_refer='.urlencode(cfg('site_url').'/wap.php?c=Login&a=login_from_youshanzhu');
        redirect($url);
    }


    /**
     * 修改用户信息
     * User: chenxiang
     * Date: 2020/5/27 10:27
     * @param $uid
     * @param $field
     * @param $value
     * @return \json
     */
    public function saveUserData($uid, $field, $value)
    {
        if (!$uid) {
            return api_output_error(-1, '请求参数必须携带 uid');
        }
        $condition_user['uid'] = $uid;
        $data_user[$field] = $value;
        $data_user['uid'] = $uid;
        if ($this->userObj->saveUser($data_user)) {
            return api_output(1000, [$field => $value]);
        } else {
            return api_output_error(-1, '修改失败！请重试。');
        }
    }

    /**
     * 注册赠送
     * User: chenxiang
     * Date: 2020/5/28 17:10
     * @param $uid
     * @param bool $is_app
     */
    public function registerGiveMoney($uid, $is_app = false)
    {
        $register_give_money_condition = cfg('register_give_money_condition');
        if ($register_give_money_condition == 2 || $register_give_money_condition == 4 || ($is_app && $register_give_money_condition == 3)) {
            $register_give_money_type = cfg('register_give_money_type');
            if ($register_give_money_type == '0' || $register_give_money_type == 2) {
                $this->addMoney($uid, cfg('register_give_money'), '新用户注册平台赠送余额');
                $this->addScoreRechargeMoney($uid, cfg('register_give_money'), '新用户注册平台赠送余额');
            }
            if ($register_give_money_type == 1 || $register_give_money_type == 2) {
                $this->addScore($uid, cfg('register_give_score'), '新用户注册平台赠送' . cfg('score_name'));
            }
        }
    }

    /**
     * 通过扫商家二维码 自动注册
     * User: chenxiang
     * Date: 2020/5/28 19:19
     * @param $data_user
     * @return \json
     */
    public function autoregByScanMerchantQrcode($data_user){
        $data_user['add_time'] = $data_user['last_time'] = $_SERVER['REQUEST_TIME'];
        //$data_user['add_ip'] = $data_user['last_ip'] = get_client_ip(0);
        $data_user['status'] = 1;
        $data_user['score_clean_time'] = $_SERVER['REQUEST_TIME'];
        $scrollMsgService = new ScrollMsgService();

        if($data_user['openid']){
            $data_user['last_weixin_time'] = $_SERVER['REQUEST_TIME'];
        }
        if($uid = $this->userObj->addUser($data_user)){
            $this->registerGiveMoney($uid);

            $spread_user_give_type = cfg('spread_user_give_type');
            if($spread_user_give_type != 3 && !empty($_SESSION['openid'])){
                $userSpreadService = new UserSpreadService();
                $now_user_spread = $userSpreadService->getUserSpreadData(true, ['uid'=>$uid]);
                if($now_user_spread) {
                    $spread_user = $this->getUser($now_user_spread['spread_uid']);
                    //待定...
                    $now_level = M(_view('User_level'))->where(array('level' => $spread_user['level']))->find();
                    if ($spread_user_give_type == 0 ||$spread_user_give_type == 2) {
                        $spread_give_money = $now_level['spread_user_give_moeny']>0?$now_level['spread_user_give_moeny']: cfg('spread_give_money');
                        if(cfg('open_score_fenrun')){
                            //$this->addMoney($spread_user['uid'],  $spread_give_money, L_('推荐新用户注册平台赠送余额'),'','',$uid);
                        }else{
                            //$this->addMoney($spread_user['uid'],  $spread_give_money, L_('推荐新用户注册平台赠送余额'));
                        }
//                        $scrollMsgService = new ScrollMsgService();
                        //$scrollMsgService->addMsg('spread_reg',$spread_user['uid'],L_("用户X1于X2推荐新用户注册获赠平台余额X3",array("X1" => str_replace_name($spread_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => $spread_give_money.cfg('Currency_txt'))));
                    }

                    if ($spread_user_give_type == 1 || $spread_user_give_type == 2) {
                        $spread_give_score = $now_level['spread_user_give_score']>0?$now_level['spread_user_give_score']: cfg('spread_give_score');
                        $scrollMsgService->addScore($spread_user['uid'], $spread_give_score, L_('推荐新用户注册平台赠送') . cfg('score_name'));
                        if($spread_give_score>0){
                        $scrollMsgService->addMsg('spread_reg',$spread_user['uid'],L_("用户X1于X2推荐新用户注册获赠X3个",array("X1" => str_replace_name($spread_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => cfg('score_name').$spread_give_score)));
                        }
                    }

                }
            }

            return api_output(1000, ['uid'=>$uid]);
        }else{
            return api_output_error(-1, '注册失败！请重试。');
        }
    }


    /**
     * 增加用户的钱
     * User: chenxiang
     * Date: 2020/5/28 17:07
     * @param $uid
     * @param $money
     * @param $desc
     * @param int $ask
     * @param int $ask_id
     * @param int $type_id
     * @param int $mer_id
     * @return array
     */
    public function addMoney($uid, $money, $desc, $ask = 0, $ask_id = 0, $type_id = 0, $mer_id=0) {

        if ($money > 0) {
            //【待定....】
//            if (cfg('mdd_api_url')) {
//                // mdd 用户查询
//                import('@.ORG.mdd_user');
//                $mdd_user = new mdd_user();
//                $mdd_result = $mdd_user->select($uid);
//                if ($mdd_result['error_code']) {
//                    return array('error_code' => true, 'msg' => $mdd_result['msg']);
//                }
//                // mdd 用户查询完
//
//                // mdd 扣款
//                import('@.ORG.mdd_user');
//                $mdd_user = new mdd_user();
//                $mdd_result = $mdd_user->add_money($uid, $money, $desc);
//                if ($mdd_result['error_code']) {
//                    return array('error_code' => true, 'msg' => $mdd_result['msg']);
//                }
//                // mdd 扣款完
//            }

            $condition_user['uid'] = $uid;
            $now_user = $this->getUser($uid);
            if ($type_id > 0) {
                $this->addRecommendAward($uid, $type_id, 1, $money, $desc);

            } else {
                $zbw_sync = false;
                if (cfg('zbw_key')) {
// 连接 智百威 数据库
//                    if ($now_user['zbw_cardid']) {
//                        $result = D('ZbwErp')->VipFullAmt($now_user, $money, $desc);
//
//                        if (!$result) {
//                            return array('error_code' => true, 'msg' => L_('智百威卡余额充值失败，请联系管理员'));
//                        }
//                        if (!$result['result']) {
//                            return array('error_code' => true, 'msg' => $result['err']);
//                        } else {
//                            D('ZbwErp')->sync_data($uid);
//                            $zbw_sync = true;
//                        }
//
//                    }
                }

                if ($this->userObj->setInc($condition_user, 'now_money', $money)) {
                    if (!$zbw_sync) {
                        $userMoneyListService = new UserMoneyListService();
                        $userMoneyListService->addRow($uid, 1, $money, $desc, true, $ask, $ask_id);
                    }

//                    //执行增加用户余额后的钩子
//                    $hook_result = hook::hook_exec('user.add_user_money_after', ['uid' => $now_user['uid'], 'user' => $now_user, 'money' => $money, 'desc' => $desc]);

                    //商盈通对接
                    $userShangyingtongService = new UserShangyingtongService();
                    $userShangyingtongService->recharge($uid, $money);

                    if (cfg('nmgzhcs_appid') && $now_user['nmgzhcs_openid']) {
                        $this->nmgzhcsMoney($now_user['nmgzhcs_openid'], $money, $desc);
                    }

                    if ($now_user['openid'] && (strpos($desc, '广用户') || $desc == '在线充值')) {
//                        if (strpos($desc, L_('广用户'))) {
//                            $money_type = L_('用户推广佣金结算');
//                        } else {
//                            $money_type = L_('平台余额在线充值');
//
//                        }
                        if (strpos($desc, '广用户')) {
                            $money_type = '用户推广佣金结算';
                        } else {
                            $money_type = '平台余额在线充值';
                        }
                        //待定...
                        $href = cfg('site_url') . '/wap.php?c=My&a=transaction';
                        $newInfo = [
                            'tempKey' => '45572',
                            'thing20' => '平台余额账户',
                            'amount3' => $money,
                            'amount15' => get_format_number($now_user['now_money'] + $money),
                            'time2' => date('Y-m-d H:i:s')
                        ];
                        $msgData = array('href' => $href,
                            'wecha_id' => $now_user['openid'],
                            'first' => L_("尊敬的X1，您的平台余额账户发生变动", array("X1" => $now_user['nickname'])),
                            'keyword1' => date('Y-m-d H:i'),
                            'keyword2' => $money_type,
                            'keyword3' => '+' . $money,
                            'keyword4' => $now_user['now_money'] + $money,
                            'remark' => L_('详情请点击此消息进入会员中心-余额记录进行查询!'),
                            'new_info' => $newInfo
                        );
                        (new TemplateNewsService())->sendTempMsg('OPENTM401833445', $msgData, $mer_id);
                    }
                    return array('error_code' => 0, 'msg' => 'OK');
                } else {
                    return array('error_code' => 1, 'msg' => L_('用户余额充值失败！请联系管理员协助解决。'));
                }
            }
        } else {
            return array('error_code' => 1, 'msg' => L_('充值金额有误'));
        }
    }

    /*使用用户的钱*/ //待定
    public function userMoney($uid, $money, $desc, $ask = 0, $ask_id = 0, $withdraw = 0){
        $userMoneyListService = new UserMoneyListService();
        if($money>0){
            if(cfg('mdd_api_url')){
                // mdd 用户查询
                import('@.ORG.mdd_user');
                $mdd_user = new mdd_user();
                $mdd_result = $mdd_user->select($uid);
                if($mdd_result['error_code']){
                    return array('error_code' => true, 'msg' => $mdd_result['msg']);
                }
                // mdd 用户查询完

                // mdd 扣款
                import('@.ORG.mdd_user');
                $mdd_user = new mdd_user();
                $mdd_result = $mdd_user->use_money($uid,$money,$desc);
                if($mdd_result['error_code']){
                    return array('error_code' => true, 'msg' => $mdd_result['msg']);
                }
                // mdd 扣款完
            }

            $zbw_sync =false;
            if(cfg('zbw_key')){
                $now_user = $this->userObj->getUser(true, ['uid'=>$uid]);
                if($now_user['zbw_cardid']){
                    //待定...
                    $zbw_card = D('ZbwErp')->GetVipInfo($now_user['zbw_cardid']);
                    if($zbw_card['money']!=$now_user['now_money']){
                        return array('error_code' => true, 'msg' => L_('智百威卡余额不足扣款失败，请联系管理员'));
                    }
                    $result = D('ZbwErp')->VipPaySheet($now_user,$money,$desc);
                    if(!$result){
                        return array('error_code' => true, 'msg' => L_('智百威卡余额扣款失败，请联系管理员'));
                    }
                    if(!$result['result'] ){
                        return array('error_code' => true, 'msg' => $result['err']);
                    }else{
                        D('ZbwErp')->sync_data($uid);
                        $zbw_sync = true;
                    }

                }
            }
            $now_user = $this->getUser($uid);
            if($now_user['now_money'] < $money){
                return array('error_code' => true, 'msg' =>  L_('用户余额扣除失败！请联系管理员协助解决。'));
            }
            $condition_user['uid'] = $uid;
            if($this->userObj->setDec($condition_user, 'now_money', $money)){

                //商盈通对接
                $userShangyingtong = new UserShangyingtongService();
                $userShangyingtong->consume($uid, $money);

                $score_recharge_money = $this->userObj->getField($condition_user, 'score_recharge_money');
                if($score_recharge_money > 0 && $withdraw == 0){
                    $now_score_recharge_money = $score_recharge_money>$money?$money:$score_recharge_money;
                    $this->userObj->setDec($condition_user, 'score_recharge_money', $now_score_recharge_money);

                    $userMoneyListService->addRow($uid,2,$now_score_recharge_money,L_("X1兑换余额记录减扣 X2",array("X1" => cfg('score_name'),"X2" => $now_score_recharge_money.cfg('Currency_txt'))),true,$ask,$ask_id);
                }
                if(!$zbw_sync){
                    $userMoneyListService->addRow($uid,2,$money,$desc,true,$ask,$ask_id);
                }

                //执行减少用户余额后的钩子    //待定...
//                $hook_result = hook::hook_exec('user.use_user_money_after',['uid'=>$now_user['uid'],'user'=>$now_user,'money'=>$money,'desc'=>$desc]);

                if(cfg('nmgzhcs_appid') && $now_user['nmgzhcs_openid']){
                    $this->nmgzhcsMoney($now_user['nmgzhcs_openid'],-1 * $money,$desc);
                }

                return array('error_code' => false, 'msg' =>  L_('ok'));
            }else{
                return array('error_code' => true, 'msg' =>  L_('用户余额扣除失败！请联系管理员协助解决。'));
            }
        }else{
            return array('error_code' => true, 'msg' =>  L_('余额数据有误'));
        }
    }

    /**
     * 奖励金额增加
     * User: chenxiang
     * Date: 2020/5/27 11:27
     * @param $uid
     * @param $type_id
     * @param $type
     * @param $money
     * @param $des
     */
    public function addRecommendAward($uid, $type_id, $type, $money, $des)
    {
        $this->userObj->setInc(['uid'=>$uid], 'frozen_award_money', $money);
        $data['uid'] = $uid;
        $data['type_id'] = $type_id;
        $data['type'] = $type;
        $data['income'] = 1;
        $data['money'] = $money;
        $data['des'] = $des;
        $data['add_time'] = $_SERVER['REQUEST_TIME'];
        $fenrunRecommendAwardListService = new FenrunRecommendAwardListService();
        $fenrunRecommendAwardListService->addRecommendAward($data);
    }

    /**
     * 内蒙古智慧城市 金额
     * User: chenxiang
     * Date: 2020/5/28 17:00
     * @param $nmgzhcs_openid
     * @param $money
     * @param $desc
     */
    public function nmgzhcsMoney($nmgzhcs_openid, $money, $desc) {
        $appid = cfg('nmgzhcs_appid') ;
        $appsecret = cfg('nmgzhcs_appsecret');

        $url=cfg('nmgzhcs_base_url').'/api/membermoney/index';

        $params=array(
            'appid' => $appid,
            'openid' => $nmgzhcs_openid,
            'money' => $money,
            'order_code' => $nmgzhcs_openid,
            'xnote' => $desc,
        );
        ksort($params);
        $paramsJoined = array();
        foreach($params as $param => $value) {
            $paramsJoined[] = "$param=$value";
        }
        $paramData = implode('&', $paramsJoined);

        $sign = strtoupper(md5($paramData.$appsecret));

        $params['sign'] = $sign;

        $res = httpRequest($url,'POST',$params);
        $res = json_decode($res[1],true);
//        fdump($params,'membermoney',true);
//        fdump($res,'membermoney',true);
    }

    /**
     * 内蒙古指挥城市 积分
     * User: chenxiang
     * Date: 2020/6/1 13:26
     * @param $nmgzhcs_openid
     * @param $score
     * @param $desc
     */
    public function nmgzhcsScore($nmgzhcs_openid, $score, $desc)
    {
        $appid = cfg('nmgzhcs_appid');
        $appsecret = cfg('nmgzhcs_appsecret');

        $url = cfg('nmgzhcs_base_url') . '/api/memberpoints/index';

        $params = array(
            'appid' => $appid,
            'openid' => $nmgzhcs_openid,
            'points' => $score,
            'order_code' => $nmgzhcs_openid,
            'xnote' => $desc,
        );
        ksort($params);
        $paramsJoined = array();
        foreach ($params as $param => $value) {
            $paramsJoined[] = "$param=$value";
        }
        $paramData = implode('&', $paramsJoined);

        $sign = strtoupper(md5($paramData . $appsecret));

        $params['sign'] = $sign;

        $res = httpRequest($url, 'POST', $params);
        $res = json_decode($res[1], true);
//        fdump($params,'memberscore',true);
//        fdump($res,'memberscore',true);
    }


    /**
     * 内蒙古智慧城市 自动登录
     * User: chenxiang
     * Date: 2020/6/1 13:35
     * @param $openid
     * @return \json
     */
    public function nmgzhcsAutologin($openid)
    {
        $appid = cfg('nmgzhcs_appid');
        $appsecret = cfg('nmgzhcs_appsecret');

        $url = cfg('nmgzhcs_base_url') . '/api/member/memberinfo';

        $params = array(
            'appid' => $appid,
            'openid' => $openid,
        );
        ksort($params);
        $paramsJoined = array();
        foreach ($params as $param => $value) {
            $paramsJoined[] = "$param=$value";
        }
        $paramData = implode('&', $paramsJoined);

        $sign = strtoupper(md5($paramData . $appsecret));

        $params['sign'] = $sign;
        $res = httpRequest($url, 'POST', $params);
        $res = json_decode($res[1], true);


        if ($res['status'] == 'y' && $res['openid'] != '') {
            $data_user['nickname'] = $res['name'];
            $data_user['avatar'] = $res['data']['head_img'] ? $res['data']['head_img'] : '';
            $data_user['nmgzhcs_openid'] = $res['openid'];
            if ($_SESSION['openid']) {
                $data_user['openid'] = strval($_SESSION['openid']);
            }
            if ($_SESSION['alipay_uid']) {
                $data_user['alipay_uid'] = strval($_SESSION['alipay_uid']);
            }
            $data_user['phone'] = $res['mobile'];

            $data_user['now_money'] = $res['data']['money_sum'];
            $data_user['score_count'] = $res['data']['points_sum'];
            $tmp_user = $this->getUser($res['openid'], 'nmgzhcs_openid');
            if (!$tmp_user) {
                $reg_result = $this->autoReg($data_user);
            } elseif ($tmp_user['uid']) {
                $data_user['uid'] = $tmp_user['uid'];
                $this->userObj->saveUser($data_user);
            }

            $login_result = $this->autoLogin('nmgzhcs_openid', $res['openid']);

            if ($login_result['error_code']) {
                return api_output_error(1001, L_('自动登录失败！'));
            } else {
                $now_user = $login_result['user'];
                session('user', $now_user);
                return api_output(1000, ['user' => $now_user]);
                $referer = !empty($_SESSION['weixin']['referer']) ? $_SESSION['weixin']['referer'] : url('My/index');

                redirect($referer);
                exit;
            }
        }
    }


    /**
     * 下载头像
     * User: chenxiang
     * Date: 2020/6/1 13:36
     * @param $avatar
     * @param $avatar_token
     * @return string
     */
    public function downAvatar($avatar, $avatar_token){
        if(empty($avatar)){
            return '';
        }
        if(strpos($avatar,'http') !== 0){
            return $avatar;
        }
        if(strpos($avatar,file_domain()) !== false){
            return $avatar;
        }
        import('ORG.Net.Http');
        $http = new Http();
        $avatar_content = Http::curlGet($avatar);
        if(empty($avatar_content)){
            return $avatar;
        }
        fdump($avatar,'avatar_content',true);
        fdump($avatar_content,'avatar_content',true);
        fdump($http,'avatar_content',true);
        $avatar_tmp = md5($avatar_token);
        $avatar_file_dir = WEB_PATH.'upload/avatar/'.substr($avatar_tmp,0,3).'/'.substr($avatar_tmp,3,3).'/';
        if(!file_exists($avatar_file_dir)){
            mkdir($avatar_file_dir,0777,true);
        }
        $avatar_file = $avatar_file_dir.$avatar_tmp.'.jpg';
        file_put_contents($avatar_file,$avatar_content);
        //判断是否需要上传至云存储
        $file_handle = new file_handle();
        $file_handle->upload($avatar_file);

        $avatar_file = str_replace(WEB_PATH, '', $avatar_file);

        $avatar = file_domain() . '/' . $avatar_file;

        return $avatar;
    }

    /**
     * 资产转移，更换账号
     * User: chenxiang
     * Date: 2020/6/1 13:38
     * @param $from_uid
     * @param $to_uid
     * @return \json
     */
    public function changeProperty($from_uid, $to_uid)
    {
        $from_user = $this->getUser($from_uid);
        $to_user = $this->getUser($to_uid);
        if ($from_user['status'] == 4 && $to_user) {
            // 转移会员卡
            $cardUserlistService = new CardUserlistService();
            $cardUserlistService->setField(['uid' => $from_uid], 'uid', $to_uid);
            // 转移推广关系
            if ($to_user['openid']) {
                $userSpreadService = new UserSpreadService();
                $userSpreadService->setField(['spread_uid' => $from_uid], 'spread_openid', $to_user['openid']);
            }
            return api_output(1000);
        } else {
            return api_output_error(1001, L_('不能转移'));
        }
    }

    /**
     * 隐私号码保护 获得虚拟分机号码
     * User: chenxiang
     * Date: 2020/6/1 14:12
     * @param $phone
     * @param $start_time
     * @return mixed
     */
    public function getVirtualPhone($phone, $start_time)
    {
        // 有效时间
        $start_time = $start_time ? $start_time : time();
        $time = cfg('protect_time');
        $time = $time ? $time : 2;

        $returnArr['error_code'] = 0;
        $returnArr['data']['phone'] = $phone;
        $returnArr['data']['expiration_time'] = $start_time + 86400 * $time;
        return $returnArr;
    }

    /**
     * 获取下一vip等级
     * User: chenxiang
     * Date: 2020/6/1 14:14
     * @param $uid
     * @param $storeId
     * @return array|bool|mixed
     */
    public function getNextVipLevel($uid, $storeId)
    {
        $user = $this->userObj->getUser(true, ['uid' => $uid]);

        if (empty($user)) {
            return [];
        } else {
            $tm = $_SERVER['REQUEST_TIME'];
            $userLevelService = new UserLevelService();
            $level = $user['level'];
            $levelInfo = $userLevelService->getOne(['level' => $level]);
            if ($user['level'] == 0 || ($levelInfo['validity'] > 0 && $user['level_time'] + $levelInfo['validity'] * 86400 < $tm)) {
                //未开通会员等级或者已过期
                return [];
            }
            $levelIncrByOne = $userLevelService->getOne(['level' => ['gt', $level]]);
            if (empty($levelIncrByOne) || $levelIncrByOne['coupon_ids'] == '') {
                return [];
            }
            $levelIncrByOne['current_user_level'] = $level;
            $merchantStoreShopService = new MerchantStoreShopService();
            $store = $merchantStoreShopService->getOne(['store_id' => $storeId], 'store_id, leveloff');
            $leveloff = unserialize($store['leveloff']);
            if (empty($leveloff) || empty($leveloff[$levelIncrByOne['level']]) || $leveloff[$levelIncrByOne['level']]['vv'] <= 0 || $leveloff[$levelIncrByOne['level']]['vv'] >= 100) {
                return [];
            }

            $levelIncrByOne['description'] = sprintf("开通超级会员，本单立享" . getFormatNumber($leveloff[$levelIncrByOne['level']]['vv'] / 10) . "折");
            $levelIncrByOne['alias'] = cfg('vip_card_alias') ? cfg('vip_card_alias') : '';
            $couponIds = explode(',', $levelIncrByOne['coupon_ids']);
            $systemCouponService = new SystemCouponService();
            $coupons = $systemCouponService->getCouponListByIds($couponIds);
            $levelIncrByOne['coupons'] = array_values($coupons);
            return $levelIncrByOne;
        }
    }

    /**
     * 通过openid或alipay_uid绑定以往订单,并且获得积分
     * User: chenxiang
     * Date: 2020/6/1 18:41
     * @param $uid
     * @param string $type
     * @param $openid
     * @return \json
     */
    public function bindOrder($uid, $type = 'openid', $openid)
    {
        if (!$uid || !$openid) {
            return api_output_error(-1, '参数不完整');
        }

        // 用户信息
        $now_user = $this->getUser($uid);
        if (!$now_user) {
            return api_output_error(-1, '用户不存在');
        }

        if ($type == 'openid') {
            $openid = $now_user['openid'] ? $now_user['openid'] : $now_user['wxapp_openid'];
        } elseif ($type == 'alipay_uid') {
            $openid = $now_user['alipay_uid'];
        }

        if (!$openid) {
            return api_output_error(-1, '用户openid或alipay_uid不存在');
        }

        // 支付总金额
        $total_momey = 0;
        $score_count = 0;
        $money_list = [];
        // 查询快速买单订单
        $storeOrderService = new StoreOrderService();
        $store_order = $storeOrderService->getList(true, [$type => $openid]);
        if ($store_order) {
            if ($storeOrderService->setField([$type => $openid], 'uid', $now_user['uid'])) {
                $store_money_list = $storeOrderService->getList('sum(payment_money) as payment_money,mer_id', [$type => $openid, 'paid' => 1, 'refund' => 0, 'score_discount_type' => array('neq', 2), 'is_own' => 0], 'mer_id');
                $store_money_list && $money_list = array_merge($money_list, $store_money_list);

                // 自由支付可以获得积分
                if (cfg('user_own_pay_get_score') == 1) {
                    $store_own_money_list = $storeOrderService->getList('sum(payment_money) as payment_money,mer_id', [$type => $openid, 'paid' => 1, 'refund' => 0, 'score_discount_type' => array('neq', 2), 'is_own' => array('gt', 0)], 'mer_id');
                    $store_own_money_list && $money_list = array_merge($money_list, $store_own_money_list);
                }
            }
        }

        // 查询扫码点餐订单
        $foodshopOrderService = new FoodshopOrderService();
        $foodshop_order = $foodshopOrderService->getOrderList([$type => $openid]);

        // 餐饮2.0订单
        // $where = [
        //     'user_type' => $type,
        //     'user_id' => $openid,
        // ];
        // $foodshop_order_new = (new \app\foodshop\model\service\order\FoodshopOrderService())->getOrderListByCondition($where);

        if ($foodshop_order) {
            if ($foodshopOrderService->setField([$type => $openid], 'uid', $now_user['uid'])) {
                $business_id_arr = array_column($foodshop_order, 'order_id');

                // 更新用户买单表plat_order
                $platOrderService = new PlatOrderService();
                $platOrderService->setField(array('business_id' => array('in', $business_id_arr), 'business_type' => 'foodshop'), 'uid', $now_user['uid']);

                // 用户买单总金额
                $plat_money_list = $foodshopOrderService->getOrderList(array('f.uid' => $now_user['uid'], 'p.paid' => 1, 'f.status' => 3, 'p.is_own' => 0, 'p.score_discount_type' => array('neq', 2)), 'sum(p.pay_money) as payment_money,f.mer_id', 'f.mer_id', ' as f left join ' . config('database.connections.mysql.prefix') . 'plat_order as p on p.business_type="foodshop" AND p.business_id=f.order_id');
                $plat_money_list && $money_list = array_merge($money_list, $plat_money_list);

                // 自由支付可以获得积分
                if (cfg('user_own_pay_get_score') == 1) {
                    $own_money_list = $foodshopOrderService->getOrderList(array('f.uid' => $now_user['uid'], 'p.paid' => 1, 'f.status' => 3, 'p.is_own' => array('gt', 0), 'p.score_discount_type' => array('neq', 2)), 'sum(p.pay_money) as payment_money', 'f.mer_id', ' as f left join ' . config('database.connections.mysql.prefix') . 'plat_order as p on p.business_type="foodshop" AND p.business_id=f.order_id');
                    $own_money_list && $money_list = array_merge($money_list, $own_money_list);
                }

                // 更新店员买单表store_order
                $storeOrderService->setField(array('business_id' => array('in', $business_id_arr), 'business_type' => 'foodshop'), 'uid', $now_user['uid']);

                // 店员买单总金额
                $store_money_list = $foodshopOrderService->getOrderList(array('f.uid' => $now_user['uid'], 'p.paid' => 1, 'f.status' => 3, 'p.is_own' => 0, 'p.score_discount_type' => array('neq', 2)), 'sum(p.payment_money) as payment_money,f.mer_id', 'f.mer_id', ' as f left join ' . config('database.connections.mysql.prefix') . 'store_order as p on p.business_type="foodshop" AND p.business_id=f.order_id');
                $store_money_list && $money_list = array_merge($money_list, $store_money_list);

                // 自由支付可以获得积分
                if (cfg('user_own_pay_get_score') == 1) {
                    $store_own_money_list = $foodshopOrderService->field('sum(p.payment_money) as payment_money,f.mer_id')->getOrderList(array('f.uid' => $now_user['uid'], 'p.paid' => 1, 'p.status' => 3, 'p.is_own' => array('gt', 0), 'p.score_discount_type' => array('neq', 2)), 'sum(p.payment_money) as payment_money,f.mer_id', 'f.mer_id', ' as f left join ' . config('database.connections.mysql.prefix') . 'store_order as p on p.business_type="foodshop" AND p.business_id=f.order_id');
                    $store_own_money_list && $money_list = array_merge($money_list, $store_own_money_list);
                }

            }

            foreach ($money_list as $key => $value) {
                $total_momey += $value['payment_money'];
                $score_count += $this->canGetScore($value['payment_money'], $value['mer_id'], $now_user['uid']);
            }
        }

        $total_momey = getFormatNumber($total_momey);
        $score_count = getFormatNumber($score_count);
        if ($score_count) {
            // 用户获得积分
            $this->userObj->addScore($now_user['uid'], $score_count, L_("用户注册合并积分资产，获得X1", array("X1" => cfg('score_name'))));
        }
        return api_output(1000, []);
    }

    /**
     * 通过消费金额 计算用户可以获得多少积分
     * User: chenxiang
     * Date: 2020/6/1 18:14
     * @param $momey
     * @param $mer_id
     * @param $uid
     * @return float|int
     */
    public function canGetScore($momey, $mer_id, $uid)
    {
        if (cfg('add_score_by_percent') == 0 && cfg('add_score_by_system_commission') == 0) {
            if (cfg('open_score_get_percent') == 1) {
                $score_get = cfg('score_get_percent') / 100;
            } else {
                $score_get = cfg('user_score_get');
            }

//            if ($order_info['is_own'] && cfg('user_own_pay_get_score') != 1) {
//                $order_info['payment_money'] = 0;
//            }
            $merchantService = new MerchantService();
            $now_merchant = $merchantService->getInfo($mer_id);
            if ($now_merchant['score_get_percent'] >= 0) {
                $score_get = $now_merchant['score_get_percent'] / 100;
            }

            $merchantPercentRateService = new MerchantPercentRateService();
            $score_get = $merchantPercentRateService->getUserAddScoreTimes($score_get, $uid);
            return $score_get * $momey;
        }
    }

    /**
     *
     * User: chenxiang
     * Date: 2020/6/1 19:09
     * @param $uid
     * @param $order_id
     * @param $order_type
     * @param int $refundMoney
     * @param int $refund_id
     * @param string $refund_type
     * @return int[]
     */
    public function refundScoreBack($uid, $order_id, $order_type, $refundMoney = 0, $refund_id = 0, $refund_type = 'shop_order_refund')
    {
        $returnArr = array('refundMoney' => $refundMoney, 'backMoney' => 0);
        // 积分
        // 获得的总积分
        $where = array(
            'order_id' => $order_id,
            'order_type' => $order_type,
            'type' => 1,
        );
        $userScoreListService = new UserScoreListService();
        $scoreGet = $userScoreListService->getOne($where);

        // 返回用户获得的积分
        $returnArr['scoreGet'] = $scoreGet['score'];

        // 已退总积分
        $where = array(
            'order_id' => $order_id,
            'order_type' => $order_type,
            'type' => 0,
        );
        $scoreBack = $userScoreListService->getOne($where, 'sum(score) as score');
        $scoreBack = $scoreBack['score'];

        // 还可以退还的积分
        $scoreCanBack = getFormatNumber($scoreGet['score'] - $scoreBack);

        // 当前退款用户信息
        $now_user = $this->getUser($uid);
        if ($scoreCanBack) {
            if ($order_type == 'shop' || $order_type == 'mall') {
                // 订单信息
                $shopOrderService = new ShopOrderService();
                $now_order = $shopOrderService->getOne(array('order_id' => $order_id), true);

                // 应该扣除的积分
                $scoreNeedBack = getFormatNumber($refundMoney / ($now_order['payment_money'] + $now_order['balance_pay']) * $scoreGet['score']);
                $scoreNeedBack = min($scoreNeedBack, $scoreCanBack);
                if ($now_user['score_count'] < $scoreNeedBack) {
                    // 返回 用户积分不够抵扣的部分
                    $returnArr['backScore'] = $scoreNeedBack - $now_user['score_count'];

                    // 用户积分实际抵扣的部分
                    $scoreNeedBack = $now_user['score_count'];

                    // 用户积分需要抵扣的退款金额
                    if (cfg('user_score_recharge_percent')) {
                        $scoreNeedBackMore = getFormatNumber($returnArr['backScore']);
                        $scoreNeedBackMoney = getFormatNumber($scoreNeedBackMore / C('config.user_score_recharge_percent'));
                    } else {
                        $scoreNeedBackMoney = 0;
                    }
                }

                // 退款金额相应减少
                if ($scoreNeedBackMoney) {
                    $returnArr['refundMoney'] = getFormatNumber($returnArr['refundMoney'] - $scoreNeedBackMoney);
                    $returnArr['backMoney'] = $scoreNeedBackMoney;
                }

                // 扣除用户积分
                if ($scoreNeedBack && $refund_id) {
                    $param['order_id'] = $order_id;
                    $param['order_type'] = $order_type;
                    $desc = L_("商品申请售后扣除已获得的平台X1", array("X1" => cfg('score_name')));
                    $this->userScore($uid, $scoreNeedBack, $desc, $param);
                    $data['score_back_money'] = $scoreNeedBackMoney;
                    $data['score_back_count'] = $scoreNeedBackMore;
                    //待定...
                    D(ucfirst($refund_type))->where(array('order_id' => $order_id, 'id' => $refund_id))->save($data);
                }
            }
        }
        return $returnArr;
    }

    /**
     * 升级会员
     */
    public function levelUpdate($uid, $level, $payment_money, $desc)
    {
        $tm = time();

        $userLevelService = new UserLevelService();

        $levelarr = array('level' => $level, 'level_time' => time(), 'uid' => $uid);
        $this->userObj->saveUser($levelarr);

        $user_level_info = $userLevelService->getOne(array('level' => $level));
        $userLevelOpenLogService = new UserLevelOpenLogService();
        $userLevelOpenLogService->addReward($uid, $user_level_info, $uid, 1, 2, $tm, 2, $desc, 0, 0, false, $payment_money);

        //绑定会员等级,获取优惠券
        $user = $this->userObj->getUser(true, array('uid' => $uid));
        if (!empty($user)) {
            if ($user['level'] > 0) {

                $user_level = $userLevelService->getOne(array('level' => $user['level']));
                if (!empty($user_level['coupon_ids'])) {
                    $systemCouponHadpullService = new SystemCouponHadpullService();
                    $coupon_id = explode(',', $user_level['coupon_ids']);
                    foreach ($coupon_id as $v) {
                        $coupon = M('System_coupon')->field(true)->where(array('coupon_id' => $v))->find();
                        $hadpull_count = $systemCouponHadpullService->countNum(array('uid' => $user['uid'], 'coupon_id' => $v));
                        //实际可领取的数量
                        $receive_num = $coupon['limit'] - $hadpull_count;
                        //剩余的数量
                        $after_num = $coupon['num'] - $coupon['had_pull'];
                        $reduce_num = min($receive_num, $after_num);
                        $where['coupon_id'] = $v;
                        $systemCouponService = new SystemCouponService();
                        $systemCouponService->setInc($where, 'had_pull', $reduce_num);
                        $systemCouponService->setField($where, 'last_time', $_SERVER['REQUEST_TIME']);
                        $data_all = array();
                        for ($i = 0; $i < $reduce_num; $i++) {
                            $data = array();
                            $data['coupon_id'] = $v;
                            $data['num'] = 1;
                            $data['receive_time'] = $_SERVER['REQUEST_TIME'];
                            $data['status'] = 0;

                            $data['uid'] = $user['uid'];
                            $data_all[] = $data;
                        }
                        $systemCouponHadpullService->addAll($data_all);
                    }
                }

            }
        }
    }

    /**
     * 添加积分兑换余额记录
     * User: chenxiang
     * Date: 2020/5/28 20:35
     * @param $uid
     * @param $money
     * @param string $desc
     * @return \json
     */
    public function addScoreRechargeMoney($uid,$money,$desc = ''){
        if($money>0){
            $condition_user['uid'] = $uid;

            if($this->userObj->setInc($condition_user, 'score_recharge_money', $money)){
                $userMoneyListService = new UserMoneyListService();
                $userMoneyListService->addRow($uid,1,$money,$desc);
                return api_output(1000, []);
            }else{
                //待定....
                return api_output_error(-1, L_("用户X1兑换余额保存记录失败！请联系管理员协助解决。",array("X1" => cfg('score_name'))));
            }
        }else{
            return api_output_error(-1, L_('充值金额有误'));
        }
    }

    /*增加用户的积分*/ //待定...
    public function addScore($uid, $score, $desc, $clean = 0, $param = []){
        if(cfg('score_round_two')==1){
            $score = sprintf('%.2f',floatval($score));
        }else{
            $score = round($score);
        }
        if($score>0){

            // if(cfg('mdd_api_url')){
            //     // mdd 用户查询
            //     import('@.ORG.mdd_user');
            //     $mdd_user = new mdd_user();
            //     $mdd_result = $mdd_user->select($uid);
            //     if($mdd_result['error_code']){
            //         return array('error_code' => true, 'msg' => $mdd_result['msg']);
            //     }
            //     // mdd 用户查询完

            //     // mdd 增加积分
            //     import('@.ORG.mdd_user');
            //     $mdd_user = new mdd_user();
            //     $mdd_result = $mdd_user->add_score($uid,$score,$desc);
            //     if($mdd_result['error_code']){
            //         return array('error_code' => true, 'msg' => $mdd_result['msg']);
            //     }
            //     // mdd 增加积分完
            // }

            // if(cfg('uni_url')){
            //     import('@.ORG.uni_user');
            //     $uni_user = new uni_user();
            //     $uni_result = $uni_user->select($uid);
            //     if($uni_result['error_code']){
            //         return array('error_code' => true, 'msg' => $uni_result['msg']);
            //     }

            //     import('@.ORG.uni_user');
            //     $uni_user = new uni_user();
            //     $uni_result = $uni_user->add_score($uid,$score,$desc);
            //     if($uni_result['error_code']){
            //         return array('error_code' => true, 'msg' => $uni_result['msg']);
            //     }
            // }

            $zbw_sync =false;
            // if(cfg('zbw_key')){
            //     $now_user = $this->get_user($uid);
            //     if($now_user['zbw_cardid']){
            //         $result = D('ZbwErp')->VipSaleSheet($now_user,$score,L_('获得系统积分'));
            //         D('ZbwErp')->sync_data($now_user['uid']);

            //     }
            // }

            $condition_user['uid'] = $uid;
            $score_res = $this->userObj->setInc($condition_user,'score_count',$score);
            if($score_res){
                if(!$zbw_sync){
                    $userMoneyListService = new UserScoreListService();
                    $userMoneyListService->addRow($uid,1,$score,$desc,1,$clean,0,$param);
                }
                // //执行增加用户积分后的钩子
                // empty($now_user) && $now_user = $this->where(['uid'=>$uid])->find();
                // $hook_result = hook::hook_exec('user.add_user_score_after',['uid'=>$now_user['uid'],'user'=>$now_user,'score'=>$score,'desc'=>$desc]);

                // if(cfg('nmgzhcs_appid')){
                //     $now_user = $this->getUser($uid);
                //     if($now_user['nmgzhcs_openid']){
                //         $this->nmgzhcs_score($now_user['nmgzhcs_openid'],$score,$desc);
                //     }
                // }

                // if(cfg('uni_score_url')){

                // }
                //添加积分推送提醒
                (new SendTemplateMsgService())->sendWxappMessage(['type' => 'integer_settlement', 'uid' => $uid, 'reason' => $desc, 'integer' => $score]);
                return array('error_code' =>false,'msg' =>cfg('score_name').L_('增加成功！'));
            }else{

                return array('error_code' => true, 'msg' => L_("添加X1失败！请联系管理员协助解决。",array("X1" => cfg('score_name'))));
            }
        }else{
            return array('error_code' => true, 'msg' => cfg('score_name').L_('数据有误'));
        }
    }


    /**
     * 用户头像优化，没有则取默认头像
     * @author 张涛
     * @date 2020/4/25
     */
    public function userAvatarDisplay($avatar)
    {
        $defalut = cfg('site_url') . '/static/images/user_avatar.jpg';
        return $avatar ? replace_file_domain($avatar) : $defalut;
    }


    /**
     * 支付成功后推广分佣处理
     * @author: 张涛
     * @date: 2020/8/25
     */
    public function handleSpreadAfterPay($now_order, $now_user, $from_type, $spread_total_money)
    {
        $now_store_shop = (new \app\shop\model\service\store\MerchantStoreShopService())->getStoreShopDetailByStoreId($now_order['store_id']);
        $open_extra_price = cfg('open_extra_price');
        $spread_users[] = $now_user['uid'];
        //上级分享佣金
        $now_user_spread = (new UserSpreadService())->getOneRow(['uid' => $now_user['uid']]);
        $spread_rate = (new \app\common\model\service\percent_rate\PercentRateService())->getUserSpreadRate($now_order['mer_id'], $from_type, $now_order['order_id']);
        $href = cfg('site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';


        if (!empty($now_user_spread)) {
            $spread_user = (new \app\common\model\service\UserService())->getUser($now_user_spread['spread_uid']);
            $user_spread_rate = $spread_rate['first_rate'];
            if ($now_order['is_pick_in_store'] != 0) {
                $now_order['freight_charge'] = 0;
            }
            if ($spread_user && $user_spread_rate && !in_array($spread_user['uid'], $spread_users)) {
                if (cfg('shop_goods_spread_edit') == 1 && ($spread_rate['type'] == 'shop' || $spread_rate['type'] == 'mall')) { // 开启每个商品单独佣金
                    $spread_money = $user_spread_rate;
                } else {
                    $spread_money = round($spread_total_money * $user_spread_rate / 100, 2);
                }

                $spread_data = array('uid' => $spread_user['uid'], 'spread_uid' => 0, 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => $from_type, 'order_id' => $now_order['order_id'], 'third_id' => $now_order['store_id'], 'add_time' => $_SERVER['REQUEST_TIME'], 'level' => 0);
                if ($spread_user['spread_change_uid'] != 0) {
                    $spread_data['change_uid'] = $spread_user['spread_change_uid'];
                }

                $buy_user = (new \app\common\model\service\UserService())->getUser($now_user_spread['uid']);
                if ($spread_money > 0) {
                    // 处理长订单id
                    if ($now_order['real_orderid']) {
                        $spread_data['real_orderid'] = $now_order['real_orderid'];
                    }
                    \think\facade\Db::name('user_spread_list')->insert($spread_data);
                    //TODO 模板消息先注释
                    //$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => L_("X1通过您的分享购买商品，验证消费后您将获得佣金。",array("X1" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
                }
                $spread_users[] = $spread_user['uid'];
            }

            //第二级分享佣金
            $second_user_spread = (new UserSpreadService())->getOneRow(['uid' => $spread_user['uid']]);
            if (!empty($second_user_spread) && !$open_extra_price) {
                $second_user = (new \app\common\model\service\UserService())->getUser($second_user_spread['spread_uid']);
                $sub_user_spread_rate = $spread_rate['second_rate'];

                if ($second_user && $sub_user_spread_rate && !in_array($second_user['uid'], $spread_users)) {
                    if (cfg('shop_goods_spread_edit') == 1 && ($spread_rate['type'] == 'shop' || $spread_rate['type'] == 'mall')) { // 开启每个商品单独佣金
                        $spread_money = $sub_user_spread_rate;
                    } else {
                        $spread_money = round($spread_total_money * $sub_user_spread_rate / 100, 2);
                    }
                    $spread_data = array('uid' => $second_user['uid'], 'spread_uid' => $spread_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => $from_type, 'order_id' => $now_order['order_id'], 'third_id' => $now_order['store_id'], 'add_time' => $_SERVER['REQUEST_TIME'], 'level' => 0);
                    if ($second_user['spread_change_uid'] != 0) {
                        $spread_data['change_uid'] = $second_user['spread_change_uid'];
                    }
                    $sec_user = (new \app\common\model\service\UserService())->getUser($second_user_spread['uid']);

                    if ($spread_money > 0) {
                        // 处理长订单id
                        if ($now_order['real_orderid']) {
                            $spread_data['real_orderid'] = $now_order['real_orderid'];
                        }
                        \think\facade\Db::name('user_spread_list')->insert($spread_data);
                        if ($open_extra_price) {
                            $money_name = cfg('extra_price_alias_name');
                        } else {
                            $money_name = L_('佣金');
                        }
                        //TODO 模板消息先注释
                        //$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => L_("X1的子用户X2通过分享购买商品，验证消费后您将获得X3。",array("X1" => $sec_user['nickname'],"X2" => $buy_user['nickname'],"X3" => $money_name)) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
                    }
                    $spread_users[] = $second_user['uid'];
                }

                //顶级分享佣金
                $first_user_spread = (new UserSpreadService())->getOneRow(['uid' => $second_user['uid']]);
                if (!empty($first_user_spread) && cfg('user_third_level_spread') && !$open_extra_price) {
                    $first_spread_user = (new \app\common\model\service\UserService())->getUser($first_user_spread['spread_uid']);
                    $sub_user_spread_rate = $spread_rate['third_rate'];
                    if ($first_spread_user && $sub_user_spread_rate && !in_array($first_spread_user['uid'], $spread_users)) {
                        if (cfg('shop_goods_spread_edit') == 1 && ($spread_rate['type'] == 'shop' || $spread_rate['type'] == 'mall')) { // 开启每个商品单独佣金
                            $spread_money = $sub_user_spread_rate;
                        } else {
                            $spread_money = round($spread_total_money * $sub_user_spread_rate / 100, 2);
                        }
                        $spread_data = array('uid' => $first_spread_user['uid'], 'spread_uid' => $second_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => $from_type, 'order_id' => $now_order['order_id'], 'third_id' => $now_order['store_id'], 'add_time' => $_SERVER['REQUEST_TIME'], 'level' => 0);
                        if ($first_spread_user['spread_change_uid'] != 0) {
                            $spread_data['change_uid'] = $first_spread_user['spread_change_uid'];
                        }
                        $fir_user = (new \app\common\model\service\UserService())->getUser($first_user_spread['uid']);
                        if ($spread_money > 0) {
                            // 处理长订单id
                            if ($now_order['real_orderid']) {
                                $spread_data['real_orderid'] = $now_order['real_orderid'];
                            }
                            \think\facade\Db::name('user_spread_list')->insert($spread_data);
                            //TODO 模板消息先注释
                            //$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' => L_("X1的子用户的子用户X2通过您的分享购买商品，验证消费后您将获得佣金。",array("X1" => $fir_user['nickname'],"X2" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => '点击查看详情！'), $now_order['mer_id']);
                        }
                    }
                }
            }
            /*平台三级积分分佣 2018/12/13 客户定制*/
            if ($spread_total_money > 0 && $spread_user['uid'] > 0) {
                if (cfg('first_jifen_percent') > 0 || cfg('second_jifen_percent') > 0 || cfg('third_jifen_percent') > 0) {
                    $param = array();
                    $param['get_uid'] = $now_user['uid'];
                    $param['first_uid'] = $spread_user['uid'];
                    $param['second_uid'] = $second_user['uid'];
                    $param['third_uid'] = $first_spread_user['uid'];
                    $param['spread_total_money'] = $spread_total_money;
                    $param['order_id'] = $now_order['order_id'];
                    (new \app\common\model\service\percent_rate\PercentRateService())->addSpreadJifenLog($param, $from_type, $now_order['store_id']);
                }
            }
        }
    }
    

    /**
     * 判断用户是否可以推广获取佣金
     * @param $uid
     * @return mixed
     * @author: 衡婷妹
     * @date: 2021/08/02
     */
    public function canSpread($uid)
    {
        $rs = $this->getLevelByUid($uid);
        return $rs['can_spread'];
    }

    /**
     * 获取用户会员等级信息
     * @param $uid
     * @return array
     * @author: 张涛
     * @date: 2020/11/2
     */
    public function getLevelByUid($uid)
    {
        $rs = ['is_level' => false, 'level' => 0, 'can_spread' => false, 'expire_time' => 0];
        $user = $this->userObj->getUser(true, ['uid' => $uid]);
        if (empty($user)) {
            return $rs;
        }

        $tm = time();
        $userLevelMod = new UserLevelService();
        $levelInfo = $userLevelMod->getOne(['level' => $user['level']]);

        //如果所有会员等级都关闭了推广，则所有用户都可以推广
        $isAllCloseSpread = $userLevelMod->getOne(['can_spread' => 1]) ? false : true;

        if ($levelInfo && $user['level'] > 0) {
            $rs['level_name'] = $levelInfo['lname'];
            if ($levelInfo['validity'] == 0) {
                $rs['expire_time'] = -1;  //-1表示永久有效
                $rs['level'] = $user['level'];
                $rs['is_level'] = true;
            } else {
                $rs['expire_time'] = $user['level_time'] + $levelInfo['validity'] * 86400;
                $rs['level'] = $user['level'];
                $rs['is_level'] = $rs['expire_time'] > $tm ? true : false;
            }
        }
        $rs['can_spread'] = ($isAllCloseSpread || ($levelInfo['can_spread'] == 1 && $rs['is_level'])) ? true : false;
        return $rs;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->userObj->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }


    public function getCount($where) {
        $result = $this->userObj->getCount($where);
        if(!$result) {
            return 0;
        }

        return $result;
    }

    /**
     * 扫码赠送
     * @param $uid
     * @param $type int  类型:1=余额,2=积分
     * @param $ideno int  余额/积分数量
     */

    public function ScanGiveDeno($uid, $type, $deno, $note = '')
    {
        if ($type == 1) {
            $res = $this->addMoney($uid, $deno, $note);
        } else if ($type == 2) {
            $res = $this->addScore($uid, $deno, $note);
        }
        if ($res['error_code']) {
            throw new Exception("积分/余额新增失败");
        }
        return $res;
    }

    public function getOne($where,$field='*') {
        $result = $this->userObj->getOne($where,$field);
        if(!$result || $result->isEmpty()) {
            return [];
        }

        return $result->toArray();
    }

    public function getSome($where=[]){
        $result = $this->userObj->getSome($where);

        return $result->toArray();
    }

    
    public function getWeixinBindUser($where, $field = true,$orderby='id desc') {
        $weixinBindUser = new WeixinBindUser();
        $bindUser = $weixinBindUser->getFind($where, $field,$orderby);
        return $bindUser;
    }
    
    public function getUserInfoByPhone($phone)
    {
        $fields = '
            uid, phone, nickname, avatar, FROM_UNIXTIME(add_time) create_time, now_money, score_count, status
        ';
        $user = User::where(['phone' => $phone])->field($fields)->find();
        if(empty($user)) {
            User::create([
                'phone' => $phone,
                'nickname' => $phone,
                'add_time' => time(),
            ]);
            $user = User::where(['phone' => $phone])->field($fields)->find();
        }
        if(empty($user['status'])){
            throw_exception('用户已被禁用');
        }
        $user['user_score_discount_money'] = false;//积分是否可以抵扣金额
        if(intval(cfg('user_score_use_condition')) && intval(cfg('user_score_use_percent'))) {
            $user['user_score_discount_money'] = true;
        }
        return $user;
    }

    /**
     * @param array $params $params = [
     * 'uid' => $uid,
     * 'money' => $money,
     * 'order_no' => $orderNo,
     * 'type' => $type,
     * 'mer_store' => $this->merchantStore,
     * 'staff' => $this->storeStaff,
     * ];
     * @return array
     */
    public function miaomaiChangeMoney(array $params)
    {
        return Db::transaction(function ()use($params){

            if ($params['type'] == 1) {//充值
                User::where('uid', $params['uid'])->inc('now_money', $params['money'])->update();
                $desc = "店内收银,商品退款，收银员（{$params['staff']['username']}）操作返还金额,订单编号:{$params['order_no']}";
                $data = ['add_money' => $params['money']];
                OrderPayRefundInfo::saveInfo([//记录订单退款信息
                    'order_no' => $params['order_no'],
                    'order_type' => 'miaomai',
                    'refund_type' => 'platform_pay_money',
                    'refund_money' => $params['money'],
                ]);
            } else {
                User::where('uid', $params['uid'])->dec('now_money', $params['money'])->update();
                $desc = "店内收银,商品扣款，收银员（{$params['staff']['username']}）操作扣除金额,订单编号:{$params['order_no']}";
                $data = ['discount_money' => $params['money']];
                OrderPayInfo::saveInfo([//记录订单支付信息
                    'uid' => $params['uid'],
                    'order_no' => $params['order_no'],
                    'order_type' => 'miaomai',
                    'pay_type' => 'platform_pay_money',
                    'mer_id' => $params['mer_store']['mer_id'],
                    'store_id' => $params['mer_store']['store_id'],
                    'pay_money' => $params['money'],
                ]);
            }
            $user = User::where('uid', $params['uid'])->find();
            (new UserMoneyList)->addData([
                'uid' => $params['uid'],
                'type' => $params['type'],
                'money' => $params['money'],
                'time' => time(),
                'desc' => $desc,
                'current_money' => $user['now_money'],
                'order_no' => $params['order_no'],
                'system_remarks' => $desc,
            ]);
            
            return $data;
        });
    }
}
