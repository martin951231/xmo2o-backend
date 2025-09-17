<?php


namespace app\internal_card\controller;

use app\common\model\service\send_message\SmsService;
use app\internal_card\model\db\InternalMembershipCard;
use app\internal_card\model\db\InternalMembershipCardLog;
use app\internal_card\model\db\InternalMembershipNames;
use app\internal_card\model\service\InternalCardMoneyBillService;
use app\internal_card\model\service\InternalCardService;
use app\Redis;
use think\facade\Db;
use think\response\Json;

class IndexController
{
    //获取用户信息, 绑定一卡通
    public function bind_card(){
        $uid = intval(request()->log_uid);
        $user = Db::table('pigcms_user')->where(array('uid' => $uid))->find();
        Db::table('pigcms_internal_membership_card')
            ->where(array('phone' => $user['phone']))
            ->update(array('uid' => $uid, 'is_bind' => 1));
    }


    //通过手机验证码修改密码
    public function change_password(){
        //redis
        $redis = new Redis();
        $code = $_GET['code'];
        $new_password = md5($_GET['new_password']);
        $uid = intval(request()->log_uid);
        $user = Db::table('pigcms_user')->where(array('uid' => $uid))->find();
        if ($code != $redis->get($user['phone'])){
            return api_output(1003, '验证码错误');
        }
        $res = Db::table('pigcms_internal_membership_card')->where(array('uid' => $uid))->update(array('password' => $new_password));
        if (!$res){
            return api_output(1003, '修改失败');
        }
        return api_output(0, '修改成功');
    }

    //查看交易记录
    public function transaction_log(){
        $card_id = input('card_id');
        $operation_type = input('operation_type');
        $page = input('page');
        $page_size = input('page_size');

        $where['card_id'] = $card_id;
        $where['operation_type'] = $operation_type;
        if ($operation_type == 2){
            $where['operation_type'] = array(2, 3);
        }
        $count = InternalMembershipCardLog::where($where)->count();
        $card_log_list = (new InternalMembershipCardLog())
            ->alias('t1')
            ->leftJoin('pigcms_merchant_store t2', 't1.store_id = t2.store_id')
            ->field('t1.*, t2.name')
            ->where($where)
            ->order('t1.create_time', 'DESC')
            ->limit(($page-1)*$page_size, $page_size)
            ->select();
        $results = [
            'count' => $count,
            'data' => $card_log_list
        ];
        return api_output(0, $results);
    }

    //获取用户一卡通列表shop
    public function my_card(){
        $uid = intval(request()->log_uid);
        $cardId = input('card_id');
        $where = [
            'uid' => $uid,
            'c.status' => 1
        ];
        if($cardId){
            $where['card_id'] = $cardId;
        }
        $card_list = (new InternalMembershipCard())
            ->alias('c')
            ->field(
                'card_id, card_name, balance_money, user_name, c.phone, company_owner, 
                s.name as store_name, c.create_time, c.status'
            )
            ->leftJoin('pigcms_merchant_store s', 'c.store_id = s.store_id')
            ->where($where)
            ->select();
        $res = array();
        foreach($card_list as $card){
            $cardNames = InternalMembershipNames::where('card_name', $card['card_name'])->find();
            if (empty($cardNames)){
                continue;
            }
            if(empty($cardNames['status'])){
                $card['status'] = 0;//卡类型禁用，卡也禁用
            }
            $card['store_id'] = $cardNames['store_id'];
            switch ($card['status']){
                case 0:
                    $card['status'] = '禁止';
                    break;
                case 1:
                    $card['status'] = '正常';
                    break;
                case 2:
                    $card['status'] = '未审核';
                    break;
            }
            array_push($res, $card);
        }
        return api_output(0, $res);
    }

    //获取用户一卡通数量
    public function my_card_count(){
        $uid = intval(request()->log_uid);
        $count = Db::table('pigcms_internal_membership_card')->where(array('uid' => $uid, 'status' => 1))->count();
        return api_output(0, $count);
    }

    //用户改密码发送验证码
    public function send_code(){
        //redis
        $redis = new Redis();
        $uid = intval(request()->log_uid);
        $phone = $_GET['phone'];
        $user = Db::table('pigcms_user')->where(array('uid' => $uid))->find();
        if ($phone != $user['phone']){
            return api_output(0, '用户手机号错误');
        }
        if(empty($phone) and !preg_match("/^1[3456789]\d{9}$/", $_GET['phone'])){
            return api_output(0, '手机号格式错误');
        }
        //防止1分钟内多次发送短信
        $is_send = $redis->get($phone.'-resetCardPassword');
        if($is_send){
            return api_output(0, '一分钟内不能多次发送');
        }
        // 生成验证码
        $code = mt_rand(1000, 9999);
        // 短信内容
        $text = L_('您的验证码是：X1。此验证码5分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！',$code);
        $result = $redis->set($phone, $code, 300);
        if (!$result){
            return api_output(0, '验证码写入失败');
        }
        // 发送
        $smsData = [
            'mer_id' => 0,
            'store_id' => 0,
            'content' => $text,
            'mobile' => $phone,
            'uid' => 0,
            'type' => 'merchant_pwd'
        ];
        $return = (new SmsService())->sendSms($smsData);

        $redis->set($phone.'-resetCardPassword', 1, 60);
        return api_output(0, '发送成功');
    }

    public function verify_password(){
        $uid = intval(request()->log_uid);
        $password = input('post.password');
        $card_list = InternalMembershipCard::where(array('uid' => $uid))
            ->field('card_id, card_name, balance_money, user_name, phone, password, company_owner, create_time, status')
            ->select()
            ->toArray();
        if ($card_list){
            $internal_card_password = array_column($card_list, 'password');
            if (in_array(md5($password), $internal_card_password)){
                return api_output(0, '验证成功');
            }else{
                return api_output(1003, '密码错误');
            }
        }
        return api_output(0, '没有一卡通');
    }
    
    public function internalCardBill()
    {
        $data = (new InternalCardMoneyBillService())->internalCardBill();
        return api_output(0, $data);
    }

    public function merchantStoreBill()
    {
        $datetime = strtotime(input('bill_date'));
        if(!$datetime){
            throw_exception('请传入有效时间');
        }
        $date = date('Y-m-d', $datetime);
        list($year, $month, $day) = explode('-', $date);
        if(!checkdate((int)$month, (int)$day, (int)$year)){
            throw_exception('请传入有效时间');
        }
       
        $data = (new InternalCardMoneyBillService())->merchantStoreBill($date);
        return api_output(0, $data);
    }

    public function businessType(): Json
    {
        return api_output(0, InternalMembershipCardLog::businessType());
    }

    public function changeCardPassword(): Json
    {
        $uid = intval(request()->log_uid);
        empty($uid) && throw_exception('未登录');
        $params = input();
        $validateRule = [
            'password|密码' => 'require|number|length:6',
            'phone|手机号' => 'require|number',
            'captcha|验证码' => 'require|number',
        ];
        try {
            validate($validateRule)->check($params);
        } catch (\Exception $e) {
            throw_exception($e->getMessage());
        }
        (new SmsService())->checkCode($params['phone'], $params['captcha'], 6);//type 6 一卡通密码修改
     
        $result = InternalMembershipCard::where([
            'uid' => $uid,
        ])->save(['password' => md5($params['password'])]);

        return api_output(0, $result);
    }
}