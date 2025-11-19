<?php /** @noinspection PhpUnused */

namespace app\common\controller\api;

use app\common\model\db\SystemOrder;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallOrder;
use app\mall\model\service\MallOrderService;
use Carbon\Carbon;
use think\response\Json;

class OrderDataController extends ApiBaseController
{
    public function orderCount(): Json
    {
        $merIds = input('mer_ids');
        empty($merIds) && throw_exception('商家id不能为空');

        $orderCount = SystemOrder::alias('so')
            ->join('merchant m','so.mer_id = m.mer_id')
            ->where('m.mer_id','in', $merIds)
            ->group('m.mer_id')
            ->field('m.mer_id, m.name, count(*) order_count')
            ->select();
        
        return api_output(0, $orderCount);
    }
    
    public function mallCount(): Json
    {
        $type = input('type', 0);
        $merId = input('mer_id', 0);
        !in_array($type, [0,1,2]) && throw_exception('type参数错误');
        empty($merId) && throw_exception('商家id不能为空');
        
        $time = [
            [],
            [
                ['create_time', 'between', [Carbon::now()->startOfDay()->timestamp, Carbon::now()->endOfDay()->timestamp]],
            ],
            [
                ['create_time', 'between', [Carbon::now()->startOfMonth()->timestamp, Carbon::now()->endOfMonth()->timestamp]],
            ],
        ];

        $orderCount = MallOrder::where($time[$type])
            ->where(['mer_id' => $merId])
            ->field('
                COUNT(*) order_count,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS not_paid_count,
                SUM(CASE WHEN status >= 10 AND status <= 19 THEN 1 ELSE 0 END) AS wait_count,
                SUM(CASE WHEN status >= 20 AND status <= 29 THEN 1 ELSE 0 END) AS send_count,
                SUM(CASE WHEN status >= 30 AND status <= 49 THEN 1 ELSE 0 END) AS complete_count,
                SUM(CASE WHEN status >= 50 AND status <= 59 THEN 1 ELSE 0 END) AS cancel_count,
                SUM(CASE WHEN status = 70 THEN 1 ELSE 0 END) AS refund_count
            ')
            ->find();

        return api_output(0, $orderCount);
    }
    
    public function mallNewOrder(): Json
    {
        $merId = input('mer_id', 0);
        empty($merId) && throw_exception('商家id不能为空');
        
        $order = MallOrder::with(['order_detail' => function ($query) {
                $query->field('order_id, name, image, price, num');
            }])
            ->where(['mer_id' => $merId])
            ->field('order_id, order_no, create_time')
            ->order('order_id DESC')
            ->find();
        if($order){
            $order->create_time = date('Y-m-d H:i:s', $order->create_time);
            foreach ($order->order_detail as &$detail){
                $detail->image = replace_file_domain($detail->image);
            }
        }
       
        return api_output(0, $order);
    }
    
    public function mallHotGoods(): Json
    {
        $merId = input('mer_id', 0);
        empty($merId) && throw_exception('商家id不能为空');
        
        $goodsList = MallGoods::where(['status' => 1])
            ->where(['mer_id' => $merId])
            ->field('name, image, price, unit')
            ->order('sale_num DESC')
            ->limit(3)
            ->select()
            ->each(function ($item){
                $item->image = replace_file_domain($item->image);
            });
     
        return api_output(0, $goodsList);
    }
    
    public function storeOrderCount(): Json
    {
        $type = input('type', 0);
        !in_array($type, [0,1,2]) && throw_exception('type参数错误');

        $time = [
            [],
            [
                ['so.create_time', 'between', [Carbon::now()->startOfDay()->timestamp, Carbon::now()->endOfDay()->timestamp]],
            ],
            [
                ['so.create_time', 'between', [Carbon::now()->startOfMonth()->timestamp, Carbon::now()->endOfMonth()->timestamp]],
            ],
        ];
        $storeOrder = SystemOrder::alias('so')
            ->join('merchant_store ms','so.store_id = ms.store_id')
            ->where($time[$type])
            ->where([
                ['ms.status', '=', 1],
                ['so.type', 'IN', 'shop,mall3,mall,appoint'],
            ])
            ->field('so.store_id, ms.name, count(*) cnt')
            ->group('so.store_id')
            ->order('cnt DESC')
            ->limit(3)
            ->select()
            ->each(function ($item){
               unset($item->cnt); 
            });

        return api_output(0, $storeOrder);
    }

    /**
     * 单品销售排行榜
     * @return Json
     */
    public function mallGoodsSaleRank(): Json
    {
        $merId = input('mer_id', 2);// 2 星小二商城  3 到家服务
        $type = input('type', 1);//1 本月  2 本年
        !in_array($type, [1, 2]) && throw_exception('type参数错误');
        $orderDate = input('order_date',date('Y-m-d'));
        $carbon = Carbon::parse($orderDate,'Asia/Shanghai');

        $time = [
            1 => [
                ['o.create_time', 'between', [$carbon->startOfMonth()->timestamp, $carbon->endOfMonth()->timestamp]],
            ],
            [
                ['o.create_time', 'between', [$carbon->startOfYear()->timestamp, $carbon->endOfYear()->timestamp]],
            ],
        ];
        $goodsRank = MallOrder::where($time[$type])
            ->alias('o')
            ->join('mall_order_detail od', 'o.order_id = od.order_id')
            ->where(['o.mer_id' => $merId])
            ->where('o.status', 'between', [10, 49])
            ->field('
                od.goods_id,
                od.name goods_name, 
                SUM(od.price) goods_total_price, 
                SUM(od.num) goods_total_num
            ')
            ->group('od.goods_id')
            ->order('goods_total_price DESC, goods_total_num DESC')
            ->paginate(min(100, input('page_size', 20)));

        return api_output(0, $goodsRank);
    }

    /**
     * 线上订单流水数据
     * @return Json
     */
    public function mallOrder(): Json
    {
        $orderDate = input('order_date',date('Y-m-d'));
        $carbon = Carbon::parse($orderDate,'Asia/Shanghai');
        $time = [
            ['create_time', 'between', [$carbon->startOfDay()->timestamp, $carbon->endOfDay()->timestamp]],
        ];
        
        $merName = [
            2 => '星小二商城',
            3 => '到家服务',
        ];
        $goodsRank = MallOrder::where($time)
            ->with(['order_detail' => function ($query) {
                $query->field('order_id, goods_id, name, image, sku_info, num, price goods_price');
            }])
            ->where('mer_id', 'IN', [2, 3])// 2 星小二商城  3 到家服务
            ->field('
                order_id, order_no, mer_id, store_id, uid, username, status, money_total, money_goods,
                money_real, money_freight, money_packing, money_refund, pay_time, create_time
            ')
            ->paginate(min(100, input('page_size', 20)))
            ->each(function ($item)use($merName){
                $item->status_text = MallOrderService::$order_status[$item->status] ?? '';
                $item->merchant_name = $merName[$item->mer_id] ?? '';
                $item->create_time = date('Y-m-d H:i:s', $item->create_time);
                $item->pay_time = $item->pay_time ? date('Y-m-d H:i:s', $item->pay_time) : '';
                foreach ($item->order_detail as &$value){
                    $value->image = replace_file_domain($value->image);
                }
            });

        return api_output(0, $goodsRank);
    }

    /**
     * 线上销售数据
     * @return Json
     */
    public function orderSaleInfo(): Json
    {
        $type = input('type', 1);
        $merId = input('mer_id', 2);
        $orderDate = input('order_date',date('Y-m-d'));
        !in_array($type, [1,2]) && throw_exception('type参数错误');
        empty($merId) && throw_exception('商家id不能为空');
        $carbon = Carbon::parse($orderDate,'Asia/Shanghai');
        $time = [
            1 => [
                ['create_time', 'between', [$carbon->startOfMonth()->timestamp, $carbon->endOfMonth()->timestamp]],
            ],
            [
                ['create_time', 'between', [$carbon->startOfYear()->timestamp, $carbon->endOfYear()->timestamp]],
            ],
        ];
        $orderCount = MallOrder::where($time[$type])
            ->where(['mer_id' => $merId])
            ->field('
                SUM(CASE WHEN status >= 10 THEN money_total ELSE 0 END) AS order_total_money,
                SUM(CASE WHEN status >= 10 THEN money_refund ELSE 0 END) AS order_refund_money,
                SUM(CASE WHEN status >= 10 THEN 1 ELSE 0 END) AS order_count,
                SUM(CASE WHEN status >= 50 AND status <= 59 THEN 1 ELSE 0 END) AS cancel_count,
                SUM(CASE WHEN status = 70 THEN 1 ELSE 0 END) AS refund_count
            ')
            ->find();

        return api_output(0, $orderCount);
    }
}