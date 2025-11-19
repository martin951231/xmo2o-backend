<?php

declare(strict_types=1);

namespace app\villageGroup\model\db;

use app\common\model\db\User;
use think\Model;

class VillageGroupOrder extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class,'uid','uid');
    }

    public function shareUser()
    {
        return $this->belongsTo(User::class,'share_uid','uid');
    }

    public function searchStartTimeAttr($query, $value, $data)
    {
        if(empty($data['start_time'])){
            return;
        }
        $query->where('add_time', '>=', strtotime($value.' 00:00:00'));
    }
    
    public function searchStatusAttr($query, $value, $data)
    {
        if(empty($value)){
            return;
        }
        $query->where('status', $value);
    }
    
    public function searchEndTimeAttr($query, $value, $data)
    {
        if(empty($data['end_time'])){
            return;
        }
        $query->where('add_time', '<=', strtotime($value.' 23:59:59'));
    }

    public function searchUserNameAttr($query, $value, $data)
    {
        if(empty($value)){
            return;
        }
        $query->where('uid', 'IN', function ($query)use ($data) {
            $query->table(config('database.connections.mysql.prefix').'user')
                ->where('nickname', 'like',"%{$data['user_name']}%")
                ->field('uid');
        });
    }

    public function searchShareUserNameAttr($query, $value, $data)
    {
        if(empty($data['share_user_name'])){
            return;
        }
        $query->where('share_uid', 'IN', function ($query)use ($data) {
            $query->table(config('database.connections.mysql.prefix').'user')
                ->where('nickname', 'like',"%{$data['share_user_name']}%")
                ->field('uid');
        });
    }

    public function searchGoodsNameAttr($query, $value, $data)
    {
        if(empty($data['goods_name'])){
            return;
        }
        $query->where('order_id', 'IN', function ($query)use ($data) {
            
            $query->table(config('database.connections.mysql.prefix').'village_group_order_detail')
                ->alias('v')
                ->join('village_group_goods g','v.goods_id = g.goods_id')
                ->group('v.order_id')
                ->where('g.name', 'like',"%{$data['goods_name']}%")
                ->field('order_id');
        });
    }
    
    public function getGoodsCountAttr($value, $data)
    {
        return VillageGroupOrderDetail::where('order_id',$data['order_id'])->sum('num');
    }  
    
    public function getStatusAttr($value, $data)
    {
        $status = [
            1 => '已支付',
            2 => '已消费',
            3 => '部分消费',
            4 => '退款',
            5 => '已发货',
            6 => '团长收货 待自提',
            7=> '部分商品发货',
            8 => '部分商品收货，待自提',
            10 => '超时支付',
            11 => '已取消',
            12 => '已退款',
            13 => '已评价',
        ];
        
        return $status[$value] ?? '未知状态';
    }  
    
    public function getGoodsNameAttr($value, $data)
    {
        $goodsName = VillageGroupOrderDetail::alias('v')
            ->join('village_group_goods g','v.goods_id = g.goods_id')
            ->where('order_id',$data['order_id'])
            ->field('g.name')
            ->group('v.goods_id')
            ->column('g.name');
        
        return !empty($goodsName) ? implode(',',$goodsName) : '';
    }
} 