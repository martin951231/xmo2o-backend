<?php
/**
 * 餐饮订单model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/30 17:28
 */

namespace app\foodshop\model\db;
use app\common\model\db\MerchantStore;
use app\internal_card\model\db\InternalMembershipCardLog;
use app\internal_card\model\service\InternalCardMoneyBillService;
use Carbon\Carbon;
use think\Model;

class DiningOrder extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据条件返回订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @return array|bool|Model|null
     */
    public function getOrderListByCondition($where, $order, $page, $pageSize) {
        if(!$where){
            return null;
        }
        if($page>0){
            $result = $this->where($where)
                            ->where('is_temp','=', '0')
                            ->order($order)
                            ->page($page,$pageSize)
                            ->select();
        }else{
            $result = $this->where($where)->where('is_temp','=', '0')->order($order)->select();
        }
        return $result;
    }
    
    /**
     * 根据条件返回订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @return array|bool|Model|null
     */
    public function getOrderListByJoin($where, $field, $order, $page, $pageSize,$pay_type='all') {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if($pageSize==0){
            $result = $this ->alias('o')
                ->where($where)
                ->where('o.is_temp','=', '0')
                ->field($field)
                ->leftJoin($prefix.'merchant_store s','s.store_id=o.store_id')
                ->leftJoin($prefix.'merchant m','m.mer_id = s.mer_id')
//                            ->leftJoin($prefix.'card_userlist card','card.mer_id = o.mer_id AND card.uid = o.uid AND o.uid>0 AND card.status=1')
                ->leftJoin($prefix.'user u','u.uid = o.uid')
                ->leftJoin($prefix.'foodshop_table tab','tab.id = o.table_id')
                ->leftJoin($prefix.'foodshop_table_type tab_type','tab_type.id = o.table_type')
                ->order($order)
                ->group('o.order_id')
                ->select();

        }else{
            $result = $this ->alias('o')
                ->where($where)
                ->where('o.is_temp','=', '0')
                ->field($field)
                ->leftJoin($prefix.'merchant_store s','s.store_id=o.store_id')
                ->leftJoin($prefix.'merchant m','m.mer_id = s.mer_id')
//                            ->leftJoin($prefix.'card_userlist card','card.mer_id = o.mer_id AND card.uid = o.uid AND o.uid>0 AND card.status=1')
                ->leftJoin($prefix.'user u','u.uid = o.uid')
                ->leftJoin($prefix.'foodshop_table tab','tab.id = o.table_id')
                ->leftJoin($prefix.'foodshop_table_type tab_type','tab_type.id = o.table_type')
                ->order($order)
                ->group('o.order_id')
                ->page($page,$pageSize)
                ->select();

        }
        return $result;
    }

    /**
     * 根据条件返回订单总数
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getOrderCountByJoin($where,$pay_type='all') {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $result = $this ->alias('o')
                        ->where($where)
                        ->where('o.is_temp','=', '0')
                        ->leftJoin($prefix.'merchant_store s','s.store_id=o.store_id')
                        ->leftJoin($prefix.'merchant m','m.mer_id = s.mer_id')
                        ->leftJoin($prefix.'user u','u.uid = o.uid')
                        ->leftJoin($prefix.'foodshop_table tab','tab.id = o.table_id')
                        ->leftJoin($prefix.'foodshop_table_type tab_type','tab_type.id = o.table_type');
        if($pay_type!='all'){
            $result=$result->leftJoin($prefix . 'foodshop_order_pay order_pay', 'order_pay.order_id = o.order_id')
                ->leftJoin($prefix . 'pay_order_info pay_order', 'pay_order.orderid = order_pay.third_id');
        }
        $result=$result->count();
        return $result;
    }

    /**
     * 根据条件返回订单总数
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getOrderSumByJoin($where,$pay_type='all',$field=true) {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $result = $this ->alias('o')
                        ->field($field)
                        ->where($where)
                        ->where('o.is_temp','=', '0')
                        ->leftJoin($prefix.'merchant_store s','s.store_id=o.store_id')
                        ->leftJoin($prefix.'merchant m','m.mer_id = s.mer_id')
                        ->leftJoin($prefix.'user u','u.uid = o.uid')
                        ->leftJoin($prefix.'foodshop_table tab','tab.id = o.table_id')
                        ->leftJoin($prefix.'foodshop_table_type tab_type','tab_type.id = o.table_type');
        if($pay_type!='all'){
            $result=$result->leftJoin($prefix . 'foodshop_order_pay order_pay', 'order_pay.order_id = o.order_id')
                ->leftJoin($prefix . 'pay_order_info pay_order', 'pay_order.orderid = order_pay.third_id');
        }
        $result=$result->find();
        return $result;
    }

    

    /**
     * 根据条件返回订单线下支付总额
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getOrderOfflineMoneyTotal($where,$field=true) {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $result = $this->name('dining_order_pay') ->alias('order_pay')
                        ->field($field)
                        ->where($where)
                        ->where('o.is_temp','=', '0')
                        ->join($prefix.'dining_order o','order_pay.order_id=o.order_id')
                        ->leftJoin($prefix . 'pay_order_info pay_order', 'pay_order.orderid = order_pay.third_id')
                        ->leftJoin($prefix.'merchant_store s','s.store_id=o.store_id')
                        ->leftJoin($prefix.'merchant m','m.mer_id = s.mer_id')
                        ->leftJoin($prefix.'foodshop_table tab','tab.id = o.table_id')
                        ->leftJoin($prefix.'foodshop_table_type tab_type','tab_type.id = o.table_type')
                        ->leftJoin($prefix.'user u','order_pay.uid = u.uid');
        
        $result=$result->find();
        return $result;
    }

    /**
     * 根据订单id条件返回订单
     * @param $orderId int 条件
     * @return array|bool|Model|null
     */
    public function getOrderByOrderId($orderId) {
        if(!$orderId){
            return null;
        }

        $where = [
            'order_id' => $orderId
        ];

        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 根据条件返回订单
     * @param $where int 条件
     * @return array|bool|Model|null
     */
    public function getOrderByCondition($where, $order) {
        if(!$where){
            return null;
        }

        $result = $this->where($where)->order($order)->find();
        return $result;
    }

    /**
     * 根据条件返回订单数量
     * @param $where array 条件
     * @return array
     */
    public function getOrderCountByCondition($where){
     
        $result = $this->where($where)
                        ->where('is_temp','=', '0')->count();
        return $result;
    }

    /**
     * 根据条件返回总数
     * @param $where array 条件 
     * @return array|bool|Model|null
     */
    public function getCount($where) {

        $result = $this->where($where)->where('is_temp','=', '0')->count();
        return $result;
    }

    public function merchantStoreStatistic($storeId, $date): bool
    {
        $time = Carbon::parse($date);
        $startTime = $time->startOfDay()->timestamp;
        $endTime = $time->endOfDay()->timestamp;
        //总支付金额，平台余额支付金额， 支付订单数量
        $payMoney = DiningOrderPay::where([
            ['store_id', '=', $storeId],
            ['pay_time', '>=', $startTime],
            ['pay_time', '<=', $endTime],
        ])
            ->field(
                'SUM(price) pay_money,
                SUM(system_balance) balance_pay_money,
                SUM(offline_money) offline_pay_money,
                SUM(refund_money) refund_money'
            )
            ->find();

        $orderCnt = DiningOrder::where([
            ['store_id', '=', $storeId],
            ['pay_time', '>=', $startTime],
            ['pay_time', '<=', $endTime],
        ])
            ->field('count(*) cnt')
            ->find();
        $payMoney['cnt'] = $orderCnt['cnt'];
        //支付宝支付金额
        $aliPayMoney = DiningOrderPay::alias('o')
            ->join('pay_order_info p', "o.order_id = p.business_order_id AND p.business = 'dining'")
            ->where([
                ['o.store_id', '=', $storeId],
                ['o.paid', '=', 1],
                ['o.pay_time', '>=', $startTime],
                ['o.pay_time', '<=', $endTime],
                ['p.pay_type', 'IN', 'alipayh5, alipay'],
            ])
            ->field('SUM(p.paid_money) pay_money')
            ->find();
        //微信支付金额
        $weixinPayMoney = DiningOrderPay::alias('o')
            ->join('pay_order_info p', "o.order_id = p.business_order_id AND p.business = 'dining'")
            ->where([
                ['o.store_id', '=', $storeId],
                ['o.paid', '=', 1],
                ['o.pay_time', '>=', $startTime],
                ['o.pay_time', '<=', $endTime],
                ['p.pay_type', 'IN', 'weixinh5, weixin, wechat'],
            ])
            ->field('SUM(p.paid_money) pay_money')
            ->find();
        //一卡通支付金额
        $cardMoney = InternalMembershipCardLog::where([
            ['store_id', '=', $storeId],
            ['operation_type', '=', 2],//支付
            ['business_type', 'IN', 'dining'],
            ['create_time', '>=', $startTime],
            ['create_time', '<=', $endTime],
        ])
            ->field('SUM(money) pay_money')
            ->find();

        $allPayMoney =
            $cardMoney['pay_money'] +
            $payMoney['balance_pay_money'] +
            $payMoney['offline_pay_money'] +
            $aliPayMoney['pay_money'] +
            $weixinPayMoney['pay_money'];
        
        $data = [
            'mer_id' => MerchantStore::where(['store_id' => $storeId, 'status' => 1])->value('mer_id'),
            'store_id' => $storeId,
            'order_type' => 'dining',
            'bill_date' => $date,
            'pay_order_count' => $payMoney['cnt'],
            'pay_money' => bcsub($allPayMoney, $payMoney['refund_money'], 2),
            'offline_pay_money' => $payMoney['offline_pay_money'] ?: 0,
            'internal_card_pay_money' => $cardMoney['pay_money'] ?: 0,
            'balance_pay_money' => $payMoney['balance_pay_money'] ?: 0,
            'alipay_pay_money' => $aliPayMoney['pay_money'] ?: 0,
            'wechat_pay_money' => $weixinPayMoney['pay_money'] ?: 0,
            'refund_money' => $payMoney['refund_money'] ?: 0,
        ];

        return (new InternalCardMoneyBillService())->saveMerchantStoreDateBill($data);
    }
}