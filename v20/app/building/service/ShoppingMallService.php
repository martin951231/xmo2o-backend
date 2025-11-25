<?php

namespace app\building\service;

use app\api\model\service\building\BuildingApiService;
use app\building\model\ShoppingMall;
use app\building\model\ShoppingMallBindAdmin;
use app\building\model\ShoppingMallBuilding;
use app\building\model\ShoppingMallMembershipBenefits;
use app\building\model\ShoppingMallPark;
use app\building\model\ShoppingMallWifi;
use app\building\model\ShoppingTicketPointApply;
use app\common\model\db\Admin;
use pinyin\Pinyin;
use think\facade\Db;
use think\facade\Validate;

class ShoppingMallService
{
    protected $shoppingMallMod;

    public function __construct()
    {
        $this->shoppingMallMod = new ShoppingMall();
    }

    public function getNearestMall($lng, $lat, $uid = 0, $mallId = 0)
    {
        if ($mallId) {
            $nearestMall = (new ShoppingMall())->where('id', $mallId)->where('status', 1)->findOrEmpty()->toArray();
        } else if ($lng && $lat) {
            $nearestMall = (new ShoppingMall())
                ->field(Db::raw("*, ST_Distance(point($lng, $lat), point(lng, lat)) as distance"))
                ->where('status', 1)
                ->order('distance', 'ASC')
                ->findOrEmpty()
                ->toArray();
        } else if ($uid) {
            $nearestMall = (new ShoppingMall())->where('status', 1)->findOrEmpty()->toArray();
        } else {
            $nearestMall = (new ShoppingMall())->where('status', 1)->findOrEmpty()->toArray();
        }
        return $nearestMall;
    }

    public function getAllMalls($lng = '', $lat = '')
    {
        $malls =  (new ShoppingMall())->where('status', 1)->field('id,name,lng,lat')->select()->toArray();
        $pinyin = new Pinyin();
        foreach ($malls as &$mall) {
            $firstChar =  strtoupper(substr($pinyin->str2py($mall['name']), 0, 1));
            $mall['first_char'] = $firstChar;
        }
        return $malls;
    }

    public function getNearByMalls($lng, $lat, $limit = 3)
    {
        if (!$lng || !$lat) {
            return [];
        }

        $malls = (new ShoppingMall())
            ->field(Db::raw("id,name,lng,lat, ST_Distance(point($lng, $lat), point(lng, lat)) as distance"))
            ->where('status', 1)
            ->order('distance', 'ASC')
            ->limit($limit)
            ->select()
            ->each(function ($mall) use ($lng, $lat) {
                $mall['distance'] = get_range(get_distance($mall['lng'], $mall['lat'], $lng, $lat));
                return $mall;
            })
            ->toArray();

        return $malls;
    }


    //获取商场导购

    public function floorGuide($mallId, $catFid = 0, $building = '')
    {
        if (!$mallId) {
            return [];
        }
        $mall = (new ShoppingMall())->where(['id' => $mallId])->findOrEmpty()->toArray();
        if (!$mall) {
            throw new \think\Exception('商场不存在');
        }
        $where  = [
            ['mall_id', '=', $mallId],
            ['status', '=', 1],
        ];
        if ($mall['is_self']) {
            $allBuildings = (new ShoppingMallBuilding())->where([['mall_id', '=', $mallId]])->column('id,name as building,0 AS selected', 'name');
        } else {
            //获取商场所有楼栋
            $allBuildings = Db::name('merchant_store')->where($where)->column('building,0 AS selected', 'building');
        }
        if (empty($allBuildings)) {
            return [];
        }

        $rs = [];
        if ($building) {
            $allBuildings[$building]['selected'] = 1;
            $allBuildings = array_values($allBuildings);
        } else {
            $allBuildings = array_values($allBuildings);
            $allBuildings[0]['selected'] = 1;
            $building = $allBuildings[0]['building'];
        }
        $rs['all_buildings'] = $allBuildings;

        //获取商场门店所有店铺分类
        $catIds = Db::name('merchant_store')->where('cat_fid', '>', 0)->where($where)->distinct()->column('cat_fid');

        //获取门店信息
        $catFid && $where[] = ['cat_fid', '=', $catFid];
        $building && $where[] = ['building', '=', $building];

        $rs['store_lists'] = Db::name('merchant_store')
            ->field('store_id,mer_id,logo,cat_fid,name,building,floor,room,floor_id,floor_number,brand_name')
            ->where($where)
            ->order('floor_number asc')
            ->select()
            ->each(function ($item) {
                $item['logo'] = replace_file_domain($item['logo']);
                $item['url'] = get_base_url('pages/store/v1/home/index?store_id=' . $item['store_id'], true);
                return $item;
            })
            ->toArray();
        if ($catIds) {
            $rs['store_cateory'] = Db::name('merchant_category')->where('cat_status', 1)->whereIn('cat_id', $catIds)->order('cat_sort desc')->column('cat_id,cat_name');
        } else {
            $rs['store_cateory'] = [];
        }
        return $rs;
    }

    public function searchStore($mallId, $keyword)
    {
        if (!$mallId) {
            return [];
        }

        $where = [
            ['s.mall_id', '=', $mallId],
            ['s.status', '=', 1],
        ];
        $keyword && $where[] = ['s.name', 'like', '%' . $keyword . '%'];
        $storeLists = Db::name('merchant_store')->alias('s')
            ->join('merchant_category c', 's.cat_fid = c.cat_id', 'LEFT')
            ->where($where)
            ->field('s.store_id,s.mer_id,s.logo,s.cat_fid,s.name,s.building,s.floor,s.room,c.cat_name')
            ->select()
            ->each(function ($item) {
                $item['logo'] = replace_file_domain($item['logo']);
                $item['url'] = get_base_url('pages/store/v1/home/index?store_id=' . $item['store_id'], true);
                return $item;
            })->toArray();

        return $storeLists;
    }

    public function getStore($floorId, $room, $mallId)
    {
        if (!$mallId || !$floorId || !$room) {
            return [];
        }

        $where = [
            ['s.mall_id', '=', $mallId],
            ['s.status', '=', 1],
            ['s.floor_id', '=', $floorId],
            ['s.room', '=', $room],
        ];
        $store = Db::name('merchant_store')->alias('s')
            ->join('merchant_category c', 's.cat_fid = c.cat_id', 'LEFT')
            ->where($where)
            ->field('s.store_id,s.mer_id,s.logo,s.cat_fid,s.name,s.building,s.floor,s.room,c.cat_name')
            ->find();
        if ($store) {
            $store['logo'] = replace_file_domain($store['logo']);
            $store['url'] = get_base_url('pages/store/v1/home/index?store_id=' . $store['store_id'], true);
        }
        return $store;
    }

    //个人中心
    public function personalCenter($uid)
    {
        //用户基础信息
        $userService = new \app\common\model\service\UserService();
        $userInfo = $userService->getUser($uid);
        if (empty($userInfo) || $userInfo['status'] != 1) {
            throw new \think\Exception('用户不存在或已禁用');
        }
        $rs['user_info'] = [
            'uid' => $userInfo['uid'],
            'nickname' => $userInfo['nickname'],
            'avatar' => $userInfo['avatar'],
            'level' => $userInfo['level'],
            'level_icon' => '',
            'level_name' => '无',
            'level_expire_date' => '',
            'join_days' => ceil(abs($userInfo['add_time'] - time()) / 86400),
            'total_consume_money' => 0,
            'sign_today' => 0
        ];

        $lastSign = Db::name('user_sign')->where('uid', $uid)->order('sign_time desc')->find();
        if ($lastSign && date('Ymd', $lastSign['sign_time']) == date('Ymd', time())) {
            $rs['user_info']['sign_today'] = 1;
        }

        if ($userInfo['level'] > 0) {
            $levelSetting = Db::name('user_level')->where('level', $userInfo['level'])->find();
            if ($levelSetting) {
                $rs['user_info']['level_name'] = $levelSetting['lname'];
                $rs['user_info']['level_icon'] = replace_file_domain($levelSetting['icon']);
                $rs['user_info']['level_expire_date'] = $levelSetting['validity'] == 0 ? '有效期：永久' : '有效期：' . date('Y-m-d', $userInfo['level_time'] + $levelSetting['validity'] * 86400) . '到期';
            }
        }

        //中间导航
        $collectCount = Db::name('merchant_user_relation')->where('uid', $uid)->whereIn('type', 'store,group,appoint,mallgoods')->count();
        $systemOrderData = Db::name('system_order')->where('uid', $uid)->where('type', '<>', 'membership_card')->field('SUM(IF(paid=1,price,0)) as total_price,SUM(IF(is_del=0,1,0)) as count')->find();
        $rs['user_info']['total_consume_money'] = getFormatNumber($systemOrderData['total_price']);
        $rs['user_links'] = [
            [
                'title' => '我的余额',
                'value' => getFormatNumber($userInfo['now_money']),
                'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=my_money',
            ],
            [
                'title' => '我的' . cfg('score_name'),
                'value' => getFormatNumber($userInfo['score_count']),
                'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=integral',
            ],
            [
                'title' => '我的订单',
                'value' => getFormatNumber($systemOrderData['count']),
                'url' => get_base_url('pages/my/my_order?state=0'),
            ],
            [
                'title' => '我的收藏',
                'value' => getFormatNumber($collectCount),
                'url' => get_base_url('pages/my/my_collect'),
            ],
        ];

        //底部楼层
        $rs['bottom_links'] = [
            [
                'title' => '我的小票',
                'type' => 'url',
                'url' => get_base_url('pages/shoppingMall/order'),
                'icon' => cfg('site_url') . '/static/shopping_mall/ticket.png',
            ],
            [
                'title' => '我的优惠券',
                'type' => 'url',
                'url' => get_base_url('pages/coupon/myCoupon'),
                'icon' => cfg('site_url') . '/static/shopping_mall/youhuiquan.png',
            ],
            [
                'title' => '团购券',
                'type' => 'url',
                'url' => get_base_url('pages/coupon/myGroupCoupon'),
                'icon' => cfg('site_url') . '/static/shopping_mall/tuangouquan.png',
            ],
            [
                'title' => '商家会员卡',
                'type' => 'url',
                'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=cards',
                'icon' => cfg('site_url') . '/static/shopping_mall/shangjiahuiyuanka.png',
            ],
            [
                'title' => '客服',
                'type' => 'kefu',
                'phone' => cfg('site_phone'),
                'url' => build_im_chat_url('user_' . $uid, 'plat_0', 'user2plat'),
                'icon' => cfg('site_url') . '/static/shopping_mall/kefu.png',
            ],
            [
                'title' => '我的评价',
                'type' => 'url',
                'url' => get_base_url('pages/my/my_evaluate'),
                'icon' => cfg('site_url') . '/static/shopping_mall/pingjia.png',
            ],
            [
                'title' => '我的足迹',
                'type' => 'url',
                'url' => get_base_url('pages/my/my_tracks'),
                'icon' => cfg('site_url') . '/static/shopping_mall/zuji.png',
            ],
        ];
        return $rs;
    }

    //提交拍照小票获取积分申请记录
    public function submitTicketPoint($params)
    {
        $uid = $params['uid'];
        $business = $params['business'];
        $realOrderid = $params['real_orderid'];
        $money = $params['money'];
        $ticketPic = $params['ticket_pic'];
        $payPic = $params['pay_pic'];
        $mallId = $params['mall_id'];


        // if (empty($realOrderid)) {
        //     throw new \think\Exception('请填写订单号');
        // }
        if (empty($ticketPic)) {
            throw new \think\Exception('请上传小票图');
        }
        // if (empty($payPic)) {
        //     throw new \think\Exception('请上传支付凭证');
        // }
        // if ($money < 0) {
        //     throw new \think\Exception('金额填写有误');
        // }

        $applyMod = new ShoppingTicketPointApply();
        //目前只有优惠买单和收银
        // if ($business == 'store') {
        //     $thisOrder = Db::name('store_order')->where('orderid', $realOrderid)->where('paid', 1)->find();
        //     if (!$thisOrder) {
        //         throw new \think\Exception('订单不存在或未支付');
        //     }
        //     if ($thisOrder['uid'] > 0) {
        //         throw new \think\Exception('订单已线上结算，请勿重复提交');
        //     }
        // }

        // $apply = $applyMod->where('business', $business)->where('real_orderid', $realOrderid)->whereIn('status', '0,1')->find();
        // if ($apply) {
        //     throw new \think\Exception('该订单已提交，请勿重复提交');
        // }

        $data = [
            'mall_id' => $mallId,
            'uid' => $uid,
            'business' => $business,
            'real_orderid' => $realOrderid,
            'money' => $money,
            'ticket_pic' => $ticketPic,
            'pay_pic' => $payPic,
            'apply_time' => time(),
            'status' => 0,
        ];
        return $applyMod->insert($data);
    }


    public function ticketPointApplyList($uid, $status, $page, $pageSize)
    {
        if (empty($uid) || empty($page) || empty($pageSize)) {
            return [];
        }

        $where = [
            ['uid', '=', $uid],
            ['status', '=', $status]
        ];
        $lists = (new ShoppingTicketPointApply())
            ->where($where)
            ->order('id desc')
            ->paginate(['list_rows' => $pageSize, 'page' => $page])
            ->each(function ($item) {
                $item['ticket_pic'] = replace_file_domain($item['ticket_pic']);
                $payPicArr = explode(';', $item['pay_pic']);
                $payPicArr = array_map(function ($pic) {
                    return replace_file_domain($pic);
                }, $payPicArr);
                $item['pay_pic'] = $payPicArr;
                $item['apply_time'] = date('Y.m.d H:i', $item['apply_time']);
                $item['review_time'] = $item['review_time'] > 0 ? date('Y.m.d H:i', $item['review_time']) : '';
                return $item;
            })
            ->toArray();
        return $lists;
    }

    //同步商场猜你喜欢任务
    public function syncMallGuessYouLikeTask()
    {
        $malls = Db::name('shopping_mall')->where('status', 1)->select()->toArray();
        foreach ($malls as $mall) {
            $this->syncMallGuessYouLikeItems($mall['id']);
        }
    }

    //定时更新商城猜你喜欢推荐
    public function syncMallGuessYouLikeItems($mallId)
    {
        ini_set('memory_limit', '1024M');
        //商场没有开启就不要去同步数据了
        $mall = Db::name('shopping_mall')->where('id', $mallId)->where('status', 1)->find();
        if (empty($mall)) {
            return;
        }

        //没有设置猜你喜欢规则的，也不同步
        $setting = Db::name('shopping_mall_like_setting')->where('mall_id', $mallId)->find();
        if (empty($setting)) {
            return;
        }

        $total = $setting['total_num'];
        $totalWeight = 0;

        if ($setting['mall_status']) {
            $totalWeight += $setting['mall_weight'];
        }
        Db::name('shopping_mall_like_items')->where('mall_id', $mallId)->where('item_type', 'mall_goods')->delete();


        if ($setting['shop_status']) {
            $totalWeight += $setting['shop_weight'];
        }
        Db::name('shopping_mall_like_items')->where('mall_id', $mallId)->where('item_type', 'shop_store')->delete();


        if ($setting['group_status']) {
            $totalWeight += $setting['group_weight'];
        }
        Db::name('shopping_mall_like_items')->where('mall_id', $mallId)->where('item_type', 'group_goods')->delete();


        if ($setting['dining_status']) {
            $totalWeight += $setting['dining_weight'];
        }
        Db::name('shopping_mall_like_items')->where('mall_id', $mallId)->where('item_type', 'dining_store')->delete();

        $allItems = [];
        //同步商城商品
        if ($setting['mall_status']) {
            $mallCount = ceil($total * $setting['mall_weight'] / $totalWeight);
            $order = $setting['mall_sort_rule'] == 1 ? 'mg.goods_id DESC' : 'mg.sale_num DESC';
            $items = Db::name('mall_goods')->alias('mg')
                ->join('merchant_store s', 'mg.store_id=s.store_id', 'INNER')
                ->join('merchant m', 'm.mer_id=s.mer_id', 'INNER')
                ->where('mg.status', 1)
                ->where('s.status', 1)
                ->where('m.status', 1)
                ->where('s.mall_id', $mallId)
                ->field("mg.goods_id AS item_id,'mall_goods' AS item_type,'{$mallId}' AS mall_id")
                ->order($order)
                ->limit($mallCount)
                ->select()
                ->toArray();
            $allItems = array_merge($allItems, $items);
        }

        //同步外卖店铺
        if ($setting['shop_status']) {
            $shopCount = ceil($total * $setting['shop_weight'] / $totalWeight);
            $order = $setting['shop_sort_rule'] == 1 ? 's.create_time DESC' : 's.sale_count DESC';
            $items = Db::name('merchant_store_shop')->alias('s')
                ->join('merchant_store ms', 'ms.store_id=s.store_id', 'INNER')
                ->join('merchant m', 'm.mer_id=ms.mer_id', 'INNER')
                ->where('ms.status', 1)
                ->where('m.status', 1)
                ->where('ms.mall_id', $mallId)
                ->field("s.store_id AS item_id,'shop_store' AS item_type,'{$mallId}' AS mall_id")
                ->order($order)
                ->limit($shopCount)
                ->select()
                ->toArray();
            $allItems = array_merge($allItems, $items);
        }

        //团购商品
        if ($setting['group_status']) {
            $groupCount = ceil($total * $setting['group_weight'] / $totalWeight);
            $order = $setting['group_sort_rule'] == 1 ? 'g.group_id DESC' : 'g.sale_count_total DESC';
            $items = Db::name('group')->alias('g')
                ->join('group_store gs', 'gs.group_id=g.group_id', 'INNER')
                ->join('merchant_store ms', 'ms.store_id=gs.store_id', 'INNER')
                ->join('merchant m', 'm.mer_id=ms.mer_id', 'INNER')
                ->where('g.status', 1)
                ->where('g.group_cate', 'normal')
                ->where('ms.status', 1)
                ->where('m.status', 1)
                ->field("g.group_id AS item_id,'group_goods' AS item_type,'{$mallId}' AS mall_id")
                ->group('g.group_id')
                ->limit($groupCount)
                ->select()
                ->toArray();
            $allItems = array_merge($allItems, $items);
        }

        //餐饮店铺
        if ($setting['dining_status']) {
            $diningCount = ceil($total * $setting['dining_weight'] / $totalWeight);
            $order = $setting['dining_sort_rule'] == 1 ? 's.create_time DESC' : 's.sale_count DESC';
            $items = Db::name('merchant_store_foodshop')->alias('s')
                ->join('merchant_store ms', 'ms.store_id=s.store_id', 'INNER')
                ->join('merchant m', 'm.mer_id=ms.mer_id', 'INNER')
                ->where('ms.status', 1)
                ->where('m.status', 1)
                ->where('ms.mall_id', $mallId)
                ->field("s.store_id AS item_id,'dining_store' AS item_type,'{$mallId}' AS mall_id")
                ->order($order)
                ->limit($diningCount)
                ->select()
                ->toArray();
            $allItems = array_merge($allItems, $items);
        }
        shuffle($allItems);
        $allItems && Db::name('shopping_mall_like_items')->insertAll($allItems);
        return true;
    }


    //获取用户端商场首页猜你喜欢数据
    public function getGuessYouLike($mallId, $page, $pageSize)
    {
        if ($mallId < 1 || $page < 1 || $pageSize < 1) {
            return [];
        }

        $items = Db::name('shopping_mall_like_items')
            ->where('mall_id', $mallId)
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->order('id ASC')
            ->select()
            ->toArray();
        $result = [];
        foreach ($items as $i) {
            if ($i['item_type'] == 'mall_goods') {
                $goods = Db::name('mall_goods')->where('goods_id', $i['item_id'])->where('status', 1)->find();
                $goods && $result[] = [
                    'goods_id' => $goods['goods_id'],
                    'name' => $goods['name'],
                    'image' => replace_file_domain($goods['image']),
                    'type' => 'mall_goods',
                    'tag' => cfg('mall_alias_name_new'),
                    'price' => getFormatNumber($goods['price']),
                    'old_price' => getFormatNumber($goods['price']),
                    'is_multi_spec' => $goods['goods_type'] == 'sku' ? 1 : 0,
                    'url' => get_base_url('pages/shopmall_third/commodity_details?goods_id=' . $goods['goods_id'])
                ];
            } else if ($i['item_type'] == 'shop_store') {
                $store = Db::name('merchant_store_shop')->alias('ss')
                    ->join('merchant_store ms', 'ss.store_id = ms.store_id')
                    ->where('ms.status', 1)
                    ->where('ss.store_id', $i['item_id'])
                    ->field('ms.name,ms.logo,ss.store_id,ss.score_mean,ss.sale_count')
                    ->find();
                $store && $result[] = [
                    'store_id' => $store['store_id'],
                    'name' => $store['name'],
                    'image' => replace_file_domain($store['logo']),
                    'type' => 'shop_store',
                    'tag' => cfg('shop_alias_name'),
                    'score' => getFormatNumber($store['score_mean'] > 0 ? $store['score_mean'] : 5),
                    'sale' => '已售' . ($store['sale_count'] >= 10000 ? round($store['sale_count'] / 10000, 1) . '万+' : $store['sale_count'] . '+'),
                    'deliver_time' => '',
                    'url' => get_base_url('pages/shop_new/shopDetail/shopDetail?store_id=' . $store['store_id'])
                ];
            } else if ($i['item_type'] == 'dining_store') {
                $store = Db::name('merchant_store_foodshop')->alias('ss')
                    ->join('merchant_store ms', 'ss.store_id = ms.store_id')
                    ->where('ms.status', 1)
                    ->where('ss.store_id', $i['item_id'])
                    ->field('ms.name,ms.logo,ss.store_id,ss.score_mean,ss.sale_count')
                    ->find();
                $store && $result[] = [
                    'store_id' => $store['store_id'],
                    'name' => $store['name'],
                    'image' => replace_file_domain($store['logo']),
                    'type' => 'dining_store',
                    'tag' => cfg('meal_alias_name'),
                    'score' => getFormatNumber($store['score_mean'] > 0 ? $store['score_mean'] : 5),
                    'sale' => '已售' . ($store['sale_count'] >= 10000 ? round($store['sale_count'] / 10000, 1) . '万+' : $store['sale_count'] . '+'),
                    'url' => get_base_url('pages/store/v1/home/index?store_id=' . $store['store_id'], true)
                ];
            } else if ($i['item_type'] == 'group_goods') {
                $group = Db::name('group')->alias('g')
                    ->where('g.status', 1)
                    ->where('g.group_cate', 'normal')
                    ->where('g.group_id', $i['item_id'])
                    ->field('g.group_id,g.name,g.pic,g.price,g.old_price')
                    ->find();
                $group && $result[] = [
                    'group_id' => $group['group_id'],
                    'name' => $group['name'],
                    'image' => $group['pic'] ? replace_file_domain(explode(';', $group['pic'])[0]) : '',
                    'type' => 'group_goods',
                    'tag' => cfg('group_alias_name'),
                    'price' => getFormatNumber($group['price']),
                    'old_price' => getFormatNumber($group['old_price']),
                    'url' => get_base_url('pages/group/v1/groupDetail/index?group_id=' . $group['group_id'], true)
                ];
            }
        }
        return $result;
    }


    //获取WiFi列表
    public function getWifiList($mallId)
    {
        return (new ShoppingMallWifi())
            ->where('mall_id', $mallId)
            ->where('status', 1)
            ->field('ssid,password')
            ->select();
    }

    public function saveShoppingMall(array $params)
    {
        return Db::transaction(function () use ($params) {
            if (!empty($params['id'])) {
                $shoppingMall = ShoppingMall::where('id', $params['id'])->find();
                empty($shoppingMall) && throw_exception('商场不存在');
                unset($params['is_self']);
            } else {
                $shoppingMall = new ShoppingMall();
                $params['is_self'] = ShoppingMall::SELF_MALL;
            }
            if ($shoppingMall->save($params)) {
                ShoppingMallBindAdmin::where('mall_id', $shoppingMall->id)->delete();
                foreach ($params['admin_ids'] as $adminId) {
                    ShoppingMallBindAdmin::create([
                        'mall_id' => $shoppingMall->id,
                        'admin_id' => $adminId,
                    ]);
                }
            }

            return $shoppingMall;
        });
    }

    public function getCanBindAdminUser(): array
    {
        return Admin::where([
            ['level', 'IN', [Admin::LEVEL_NORMAL, Admin::LEVEL_AREA]],
            ['status', '=', Admin::STATUS_NORMAL],
        ])
            ->field('id, account')
            ->select()
            ->toArray();
    }

    //获取商场停车场所有优惠券信息
    public function getBuildingSystemCoupon($mallId)
    {
        $parkList = (new ShoppingMallPark())->where('mall_id', $mallId)->select()->toArray();
        if (empty($parkList)) {
            return [];
        }

        $couponLists = [];
        foreach ($parkList as $park) {
            $path = '/work_park/api/merchant/coupon/list';
            $businessData = [
                'merchant_id' => $park['merchant_id'],
                'api_key' => $park['api_key'],
                'park_id' => $park['park_id'],
            ];
            $data = (new BuildingApiService())->invoke($path, $businessData);
            $couponLists = array_merge($couponLists, $data['list']);
        }
        return $couponLists;
    }


    //获取用户商场会员权益
    public function getUserLevelMallBenefits($mallId, $uid)
    {
        //当前会员是否还在有效期
        $userLevelInfo = (new \app\common\model\service\UserService())->getLevelByUid($uid);
        if (!$userLevelInfo['is_level']) {
            return [];
        }
        $res = (new ShoppingMallMembershipBenefits())->alias('mb')
            ->join('shopping_mall_benefits b', 'b.id=mb.b_id')
            ->where('mb.level', $userLevelInfo['level'])
            ->where('mb.mall_id', $mallId)
            ->field('b.*,mb.coupon_id,mb.coupon_name,mb.merchant_id')
            ->select()
            ->toArray();
        return $res;
    }

    //获取楼宇支付积分使用规则
    public function getScoreRule($params)
    {
        $rules = [
            'phone|手机号' => ['require', 'mobile'],
            'mall_id|商场ID' => ['require'],
            'cost_type|费用类型' => ['require'],
            'amount|金额' => ['require', 'gt' => 0]
        ];
        $result = [
            'score_use_count' => 0,
            'score_reduce_amount' => 0,
            'get_score_per_yuan' => 0
        ];

        if (validate($rules)->check($params)) {
            $phone = $params['phone'] ?? '';
            $mallId = $params['mall_id'] ?? 0;
            $costType = $params['cost_type'] ?? '';
            $amount = $params['amount'] ?? 0;

            $user = Db::name('user')->where('phone', $phone)->where('status', 1)->find();
            if (empty($user) || $user['score_count'] == 0) {
                return $result;
            }
            //使用和获取积分规则
            $useSetting = $this->getScoreUseSetting($user['uid'], $mallId, $costType, $amount);
            $paidSetting = $this->getScorePaidSetting($user['uid'], $mallId, $costType, $amount);

            $result['score_use_count'] = $useSetting['score_use_count'];
            $result['score_reduce_amount'] = $useSetting['score_reduce_amount'];
            $result['get_score_per_yuan'] = $paidSetting['get_score_per_yuan'];
        }
        return $result;
    }

    //根据缴费类型获取积分使用规则
    public function getScoreUseSetting($uid, $mallId, $costType, $amount)
    {
        $result = [
            'score_use_count' => 0,
            'score_reduce_amount' => 0
        ];
        $user = Db::name('user')->where('uid', $uid)->where('status', 1)->find();
        if (empty($user) || $user['score_count'] == 0) {
            return $result;
        }

        $mallScoreSet = Db::name('shopping_mall_score_setting')->alias('score')
            ->join('shopping_mall m', 'm.id=score.mall_id')
            ->where('m.status', 1)
            ->where('score.mall_id', $mallId)
            ->field('score.*')
            ->find();
        if (empty($mallScoreSet) || $mallScoreSet['is_online'] == 0) {
            return $result;
        }
        //匹配费用类型设置的积分规则
        $costTypeScoreRule = [];
        $useScoreRule = json_decode($mallScoreSet['use_score_rule'], true);
        foreach ($useScoreRule as $item) {
            if ($item['cost_type'] == $costType) {
                $costTypeScoreRule = $item;
                break;
            }
        }
        if (empty($costTypeScoreRule)) {
            return $result;
        }
        $useScoreCondition = cfg('user_score_use_condition'); //积分使用条件
        $scoreCountPerYuan = cfg('user_score_use_percent'); //抵扣1元所需积分数量
        if ($useScoreCondition < 0 || $scoreCountPerYuan < 0 || $amount < $useScoreCondition) {
            return $result;
        }

        if ($mallScoreSet['use_max_percent'] > 0) {
            //设置了全局的，按照全局的规则
            $maxUseScore = bcmul($amount, $mallScoreSet['use_max_percent'] / 100, 2);
            $result['score_use_count'] = min($maxUseScore, $user['score_count']);
        } else if ($costTypeScoreRule['score_type'] == 'count') {
            //按照积分数量
            $result['score_use_count'] = min($costTypeScoreRule['score_value'], $user['score_count']);
        } else if ($costTypeScoreRule['score_type'] == 'percent') {
            //按照积分百分比
            $maxUseScore = bcmul($amount, $costTypeScoreRule['score_value'] / 100, 2);
            $result['score_use_count'] = min($maxUseScore, $user['score_count']);
        }
        $result['score_reduce_amount'] = bcdiv($result['score_use_count'], $scoreCountPerYuan, 2);
        return $result;
    }

    //获取支付后获取积分规则
    public function getScorePaidSetting($uid, $mallId, $costType, $amount)
    {
        $result = [
            'get_score_per_yuan' => 0,
            'get_score_count' => 0
        ];
        $user = Db::name('user')->where('uid', $uid)->where('status', 1)->find();
        if (empty($user)) {
            return $result;
        }

        $mallScoreSet = Db::name('shopping_mall_score_setting')->alias('score')
            ->join('shopping_mall m', 'm.id=score.mall_id')
            ->where('m.status', 1)
            ->where('score.mall_id', $mallId)
            ->field('score.*')
            ->find();
        if (empty($mallScoreSet) || $mallScoreSet['is_online'] == 0) {
            return $result;
        }
        //匹配费用类型设置的积分规则
        $costTypeScoreRule = [];
        $getScoreRule = json_decode($mallScoreSet['get_score_rule'], true);
        foreach ($getScoreRule as $item) {
            if ($item['cost_type'] == $costType) {
                $costTypeScoreRule = $item;
                break;
            }
        }
        if (empty($costTypeScoreRule)) {
            return $result;
        }

        $scacle = cfg('score_round_two') ? 2 : 0;
        if ($costTypeScoreRule['score_type'] == 'count') {
            //按照积分数量
            $result['get_score_per_yuan'] = $costTypeScoreRule['score_value'];
        } else if ($costTypeScoreRule['score_type'] == 'percent') {
            //按照积分百分比
            $result['get_score_per_yuan'] = bcdiv($costTypeScoreRule['score_value'], 100, 2);
        }
        $result['get_score_count'] = bcmul($result['get_score_per_yuan'], $amount, $scacle);
        return $result;
    }
}
