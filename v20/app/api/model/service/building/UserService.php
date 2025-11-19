<?php

namespace app\api\model\service\building;

use app\building\model\ShoppingMallParkingSetting;
use think\facade\Db;
use token\Token;

class UserService
{

    public function getInfo($code)
    {
        $token = Token::checkToken($code);
        if (isset($token['memberId']) && $token['memberId']) {
            $user = Db::name('user')->where('uid', $token['memberId'])->find();
            if (empty($user) || $user['status'] != 1) {
                throw new \Exception('用户不存在或已被禁用');
            }
            return [
                'nickname' => $user['nickname'],
                'wxapp_openid' => $user['wxapp_openid'],
                'phone' => $user['phone'],
                'wxapp_appid' => cfg('pay_wxapp_appid'),
                'union_id' => $user['union_id'],
                'country_code' => $user['country_code'] ?? ''
            ];
        } else {
            throw new \Exception('code已失效，请重新获取');
        }
    }

    //根据手机号获取用户账户信息，积分和余额账户
    public function getAccountInfo($phone)
    {
        if (empty($phone)) {
            throw new \Exception('手机号不能为空');
        }
        $user = Db::name('user')->where('phone', $phone)->where('status', 1)->find();
        if (empty($user)) {
            throw new \Exception('用户不存在或已被禁用');
        }
        return [
            'uid' => $user['uid'],
            'nickname' => $user['nickname'],
            'now_money' => $user['now_money'],
            'score_count' => $user['score_count'],
        ];
    }


    public function changeScore($params)
    {
        $phone = $params['phone'] ?? '';
        $scoreCount = $params['score_count'] ?? 0;
        $remark = $params['remark'] ?? '';

        $user = Db::name('user')->where('phone', $phone)->where('status', 1)->find();
        if (empty($user)) {
            throw new \Exception('用户不存在或已被禁用');
        }
        if (!is_numeric($scoreCount)) {
            throw new \Exception('积分必须是数字');
        }
        if (abs($scoreCount) == 0) {
            throw new \Exception('积分不能为0');
        }
        if (empty($remark)) {
            throw new \Exception('备注不能为空');
        }
        if ($scoreCount < 0) {
            //扣减积分
            if ($user['score_count'] < abs($scoreCount)) {
                throw new \Exception('用户积分不足,当前剩余积分数量：' . $user['score_count']);
            }
            $result = (new \app\common\model\service\UserService())->userScore($user['uid'], abs($scoreCount), $remark);
            if ($result['error_code']) {
                throw new \think\Exception($result['msg']);
            }
        } else {
            //增加积分
            $result = (new \app\common\model\service\UserService())->addScore($user['uid'], $scoreCount, $remark);
            if ($result['error_code']) {
                throw new \think\Exception($result['msg']);
            }
        }
        return true;
    }

    //获取用户会员等级停车优惠
    public function parkingCoupon($params)
    {
        $mallId = $params['mall_id'];
        $phone = $params['phone'];
        if (empty($mallId) || empty($phone)) {
            throw new \think\Exception('参数错误');
        }


        $user = Db::name('user')->where('phone', $phone)->where('status', 1)->find();
        if (empty($user)) {
            throw new \think\Exception('用户不存在');
        }

        $result = [
            'phone' => $user['phone'],
            'nickname' => $user['nickname'],
            'level' => $user['level'],
        ];
        if ($user['level'] > 0) {
            $levelInfo =  Db::name('user_level')->where('level', '=', $user['level'])->find();
            if (empty($levelInfo)) {
                throw new \think\Exception('用户等级不存在');
            }
            $result['level_name'] = $levelInfo['lname'];
            if ($levelInfo['validity'] == 0) {
                $result['expire_time'] = -1;  //-1表示永久有效
            } else {
                $result['expire_time'] = $user['level_time'] + $levelInfo['validity'] * 86400;
                if ($result['expire_time'] < time()) {
                    throw new \think\Exception('用户等级已过期');
                }

                $setting = (new ShoppingMallParkingSetting())->where('mall_id', $mallId)->where('level', $user['level'])->value('parking_config');
                $setting = $setting ? json_decode($setting, true) : [];
                $result['parking_config'] = array_values(array_filter($setting, function ($i) {
                    return $i['hours'] > 0 && $i['count'] > 0;
                }));
            }
        } else {
            throw new \think\Exception('当前会员未开通会员等级');
        }
        return $result;
    }


    //获取会员权益管理
    public function getBenefitLists(){
        $result = Db::name('shopping_mall_benefits')->select();
        return $result;
    }
}
