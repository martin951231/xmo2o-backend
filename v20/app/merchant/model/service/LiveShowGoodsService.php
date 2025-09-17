<?php

declare(strict_types=1);

namespace app\merchant\model\service;

use app\common\model\db\LiveGoods;
use app\common\model\db\VillageGroupGoods;
use app\villageGroup\model\db\VillageGroupOrder;

class LiveShowGoodsService
{
    public $pageSize;

    public function __construct()
    {
        $this->pageSize = min(input('page_size'), 100);
    }

    public function selectGoodsList(array $params)
    {
        $merId     = $params['mer_id'];
        $params['name']= $params['keyword'] ?? '';
        $goodsList = VillageGroupGoods::where([
            'mer_id'       => $merId,
            'status'       => 1,
            'audit_status' => 1,
        ])
            ->withSearch(['name'], $params)
            ->with(['live_goods' => function ($query) {
                $query->where(['business' => 'village_group','status' => 1])
                    ->field('business_id, show_sales, video_sales, create_time');
            }])
            ->field('goods_id, name, price, last_time, start_time, end_time')
            ->paginate($this->pageSize)
            ->each(function ($item){
                $item->last_time = $item->last_time ? date('Y-m-d H:i:s', $item->last_time) : '';
                $item->start_time = $item->start_time ? date('Y-m-d H:i:s', $item->start_time) : '';
                $item->end_time = $item->end_time ? date('Y-m-d H:i:s', $item->end_time) : '';
            });

        return $goodsList;
    }

    public function liveShowGoodsList($params)
    {
        $where = [
            'a.status' => 1,
            'a.business' => 'village_group',
            'a.mer_id' => $params['mer_id']
        ];
        $keyword = trim($params["keyword"]);
        if($keyword){
            $where['b.name'] = ['like', '%'.$keyword.'%'];
        }
        $field = "a.id, b.name,b.price, a.is_sys, a.percent, a.create_time";
        
        $data = LiveGoods::alias("a")
            ->join("village_group_goods b","a.business_id = b.goods_id")
            ->field($field)
            ->where($where)
            ->order("a.id desc ,create_time desc")
            ->paginate($this->pageSize);

        foreach ($data as $key => &$value) {
            $value['percent_txt'] = $value['is_sys'] == 1 ? "-1(使用平台佣金".cfg('live_show_commission_percent')."%)" :$value['percent']."%";
        }

        return $data;
    }

    public function changeLiveShowPercent($params)
    {
        $id      = $params['id'];
        $percent = $params['percent'];

        if ($percent < -1 || $percent > 100 || $percent != floatval($percent)) {
            throw_exception('请输入-1 - 100以内的数字');
        }
        $is_sys = $percent == -1 ? 1 : 0;
        $percent == -1 && $percent = cfg('live_show_commission_percent');
        $liveGoods = LiveGoods::where(['id' => $id])->find();
        $liveGoods->setAttrs(['percent' => $percent, 'is_sys' => $is_sys]);

        return $liveGoods->save();
    }

    public function addLiveShowGoods($params)
    {
        $merId    = $params['mer_id'];
        $type     = 'village_group';
        $selected = trim($params['selected'], ',');
        $selected = explode(',', $selected);

        foreach ($selected as $goods_id) {
            $liveGoods = LiveGoods::where(['business' => $type, 'business_id' => $goods_id, 'mer_id' => $merId])->find();
            if ($liveGoods) {
                $liveGoods->setAttrs([
                    'create_time' => date('Y-m-d H:i:s'),
                    'status'      => 1
                ]);
                if ($liveGoods->save() === false) {
                    throw_exception('商品加入失败');
                }
            } else {
                $data = [
                    'business'    => 'village_group',
                    'business_id' => $goods_id,
                    'show_sales'  => 0,
                    'video_sales' => 0,
                    'mer_id'      => $merId,
                    'store_id'    => 0,
                    'status'      => 1,
                    'percent'     => 0,
                    'create_time' => date('Y-m-d H:i:s'),
                ];

                $liveGoods = LiveGoods::create($data);
                if (empty($liveGoods->id)) {
                    throw_exception('商品加入失败');
                }
            }
        }

        return true;
    }

    public function delLiveShowGoods($params)
    {
        return LiveGoods::update(['status' => 2], ['id' => $params['id']]);
    }

    public function liveShowOrder($params)
    {
        $type       = $params['type'] ?: 'liveshow';
        $searchType = $params['searchtype'];
        $keyword    = trim($params['keyword']);
        $beginTime  = $params["begin_time"];
        $endTime    = $params["end_time"];
        $status     = $params["status"];
        
        $where = ['od.mer_id' => $params['mer_id']];
        if ($keyword) {
            switch ($searchType) {
                case 'uid':
                    $where['l.uid'] = $keyword;
                    break;
                case 'liveno':
                    if ($type == 'liveshow') {
                        $where['l.live_no'] = $keyword;
                    }
                    break;
                case 'real_orderid':
                    $where['o.real_orderid'] = $keyword;
                    break;
                case 'orderid':
                    $where['o.orderid'] = $keyword;
                    break;
                case 'customer_id':
                    $where['o.uid'] = $keyword;
                    break;
            }
        }
        if ($beginTime && $endTime) {
            $begin_time = strtotime($beginTime . ' 00:00:00');
            $end_time   = strtotime($endTime . ' 23:59:59');
            
            $where['o.create_time'] = ['between', [$begin_time, $end_time]];
        }
        if ($status) {
            $where['o.status'] = $status == '1' ? ['in', [2, 3]] : ['not in', [2, 3]];
        }
        
        if ($type == 'liveshow') {
            $where['o.live_type'] = 1;
            $field = 'o.order_id,o.real_orderid,o.add_time create_time,o.total_money total_price,l.live_id,
            l.live_no,l.uid as author_id,u.nickname as author,o.live_commission live_author_money,o.status';
        } else {
            $where['o.live_type'] = 2;
            $field = 'o.order_id,o.real_orderid,o.add_time create_time,o.total_money total_price,l.video_id,
            l.uid as author_id,u.nickname as author,o.live_commission live_author_money,o.status';
        }

        $orderList = VillageGroupOrder::alias("o");
             if ($type == 'liveshow') {
                 $orderList = $orderList->join('live_show l','o.live_id = l.live_id');
             } else {
                 $orderList = $orderList->join('live_video l','o.live_id = l.video_id');
             }
        $orderList = $orderList
            ->leftJoin('user u ','u.uid=l.uid')
            ->join('village_group_order_detail od','o.order_id = od.order_id')
            ->where($where)
            ->field($field)
            ->order("o.order_id desc")
            ->paginate($this->pageSize)
            ->each(function ($value)use($type){
                $value['type']        = $type == 'liveshow' ? "直播" : "短视频";
                $value['create_time'] = date('Y-m-d H:i', $value['create_time']);
                $value['order_money'] = $value['total_price'];
            })
            ->toArray();

        //统计
        $all = VillageGroupOrder::alias("o");
             if ($type == 'liveshow') {
                 $all = $all->join('live_show l','o.live_id = l.live_id');
             } else {
                 $all = $all->join('live_video l','o.live_id = l.video_id');
             }
        $all = $all->leftJoin('user u', 'u.uid=l.uid')
            ->join('village_group_order_detail od','o.order_id = od.order_id')
            ->where($where)
            ->field("sum(o.live_commission) as total_author_money")
            ->find();

        return [
            'order_list'           => $orderList,
            'total_order_nums'     => $orderList['total'],
            'total_author_money'   => $all['total_author_money'],
        ];
    }
}