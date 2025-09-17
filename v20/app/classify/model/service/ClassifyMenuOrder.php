<?php

namespace app\classify\model\service;

use app\classify\model\db\ClassifyMenuDetail;
use app\classify\model\db\ClassifyMenu;
use app\classify\model\db\ClassifyMenuOrder as ClassifyMenuOrderModel;
use app\common\model\db\User;
use app\common\model\service\UserService;
use think\facade\Db;

class ClassifyMenuOrder
{
    private $pageSize;

    public function __construct()
    {
        $this->pageSize = min(input('page_size'), 100);
    }

    public function verifyUserMoney(array $params)
    {
        $classifyMenuDetail = ClassifyMenuDetail::where('id', $params['menu_detail_id'])->find();
        if (empty($classifyMenuDetail)) {
            throw_exception('当前套餐不存在');
        }
        $user = User::where('uid', $params['uid'])->field('now_money')->find();
        if ($user['now_money'] < $classifyMenuDetail['price']) {
            return [
                'now_money' => $user['now_money'],
                'classify_order_price' => $classifyMenuDetail['price'],
                'error_msg' => '余额不足，请前去充值后购买'
            ];
        }
        
        return [
            'now_money' => $user['now_money'],
            'classify_order_price' => $classifyMenuDetail['price']
        ];
    }

    public function addOrder(array $params)
    {
        $classifyOrder = ClassifyMenuOrderModel::where([
            'uid'            => $params['uid'],
            'menu_detail_id' => $params['menu_detail_id'],
        ])
            ->where('expire_time', '>', time())
            ->find();

        if ($classifyOrder) {
            throw_exception('当前套餐已购买，无需再次购买');
        }

        $classifyMenuDetail = ClassifyMenuDetail::where('id', $params['menu_detail_id'])->find();
        if (empty($classifyMenuDetail)) {
            throw_exception('当前套餐不存在');
        }
        $classifyMenu = ClassifyMenu::where('id', $classifyMenuDetail['menu_id'])->find();
        $params['menu_name'] = $classifyMenu['name'];
        $params['name'] = $classifyMenuDetail['name'];
        $user = User::where('uid', $params['uid'])->field('uid, now_money')->find();
        if ($user['now_money'] < $classifyMenuDetail['price']) {
            throw_exception('余额不足，无法购买，请前去充值后购买');
        }
        $params['expire_time'] = $classifyMenuDetail['effective_days'] * 3600 * 24 + time();

        try {
            Db::transaction(function () use ($params, $user, $classifyMenuDetail) {
                $order = ClassifyMenuOrderModel::create([
                    'uid'            => $params['uid'],
                    'menu_name'      => $params['menu_name'],
                    'name'           => $params['name'],
                    'price'          => $classifyMenuDetail['price'],
                    'menu_detail_id' => $params['menu_detail_id'],
                    'menu_id'        => $classifyMenuDetail['menu_id'],
                    'effective_days' => $classifyMenuDetail['effective_days'],
                    'expire_time'    => $params['expire_time'],
                ]);
                (new UserService())->userMoney(
                    $user['uid'],
                    $classifyMenuDetail['price'],
                    L_("购买X1套餐，扣除余额，订单Id X2", array("X1" => $params['menu_name'].$classifyMenuDetail['name'], "X2" => $order['id']))
                );
            });
        } catch (\Exception $e) {
            throw_exception($e->getMessage());
        }
        return true;
    }

    public function orderList(array $params = [])
    {
        $selectSearch = [
            1 => 'nick_name', 'phone', 'name',
        ];
        $where = [];
        if (isset($params['search_type']) && isset($selectSearch[$params['search_type']]) && !empty($params['keywords'])) {
            $params[$selectSearch[$params['search_type']]] = $params['keywords'];
        }
        isset($params['nick_name']) && $where['user.nickname'] = $params['keywords'];
        isset($params['phone']) && $where['user.phone'] = $params['keywords'];
        isset($params['uid']) && $where['user.uid'] = $params['uid'];

        $orderList = ClassifyMenuOrderModel::withSearch(['name', 'status', 'start_time', 'end_time'], $params)
            ->withJoin(['user' => function ($query) use ($params) {
                $query->withField(['nickname', 'phone', 'uid']);
            }])
            ->where($where);
        if (!empty($params['export'])) {
            $orderList = $orderList->select();
        } else {
            $orderList = $orderList->paginate($this->pageSize);
        }

        $orderList->each(function ($item) {
            $item->user_nickname = $item->user->nickname;
            $item->user_phone    = $item->user->phone;
            unset($item->user);
        });

        return $orderList;
    }

    public function changeExpire(array $params = [])
    {
        $classifyOrder = ClassifyMenuOrderModel::where(['id' => $params['order_id']])->find();
        if (empty($classifyOrder)) {
            throw_exception('订单不存在');
        }
        if (strtotime($params['expire_time']) < time()) {
            throw_exception('套餐过期时间不可小于当前时间');
        }
        $classifyOrder->expire_time = strtotime($params['expire_time']);
        return $classifyOrder->save();
    }
}