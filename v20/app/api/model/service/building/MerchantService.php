<?php

namespace app\api\model\service\building;

use think\facade\Db;
use token\Ticket;
use token\Token;

class MerchantService
{
    //自动创建商家（名称、手机号）
    public function create($params)
    {
        $name = isset($params['name']) ? trim($params['name']) : '';
        $phone = isset($params['phone']) ? trim($params['phone']) : '';
        if (empty($name) || empty($phone)) {
            throw new \Exception('参数错误');
        }

        $merchant = Db::name('merchant')->where('phone', '=', $phone)->where('status', '<>', 4)->find();
        if ($merchant) {
            //商家存在
            return [
                'mer_id' => $merchant['mer_id'],
                'name' => $merchant['name'],
            ];
        } else {
            //商家不存在,默认给所有权限节点
            $menus = Db::name('new_merchant_menu')->where('status', 1)->where('show', 1)
                ->where('id', '<>', 70)
                ->where('fid', '<>', 70)
                ->order('id asc')
                ->column('id');

            $data = [
                'name' => $name,
                'phone' => $phone,
                'status' => 1,
                'menus' => $menus ? implode(',', $menus) : '',
                'reg_time' => time()
            ];
            $merId = Db::name('merchant')->insertGetId($data);
            return [
                'mer_id' => $merId,
                'name' => $name,
            ];
        }
    }

    //获取伪登录地址
    public function getLoginUrl($params)
    {
        $merId = isset($params['mer_id']) ? intval($params['mer_id']) : 0;
        $phone  = isset($params['phone']) ? trim($params['phone']) : '';

        if (empty($merId) && empty($phone)) {
            throw new \Exception('参数错误');
        }

        //优先读取商家ID、其次是手机号
        if ($merId) {
            $merchant = Db::name('merchant')->where('mer_id', '=', $merId)->where('status', '<>', 4)->find();
        } else {
            $merchant = Db::name('merchant')->where('phone', '=', $phone)->where('status', '<>', 4)->find();
        }
        if (!$merchant) {
            throw new \Exception('商家不存在或已删除');
        }

        $tm = time();
        $type = 'merchant';
        $ttl = $tm + 300;
        $ticket = Ticket::create($merchant['mer_id'], $type . $ttl, true)['ticket'];
        return ['login_url' => cfg('site_url') . '/source/login.php?type=' . $type . '&tm=' . $tm . '&tmp_ticket=' . $ticket];
    }
}
