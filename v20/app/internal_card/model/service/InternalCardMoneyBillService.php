<?php

namespace app\internal_card\model\service;

use app\appoint\model\service\AppointOrderService;
use app\common\model\db\AppointOrder;
use app\common\model\db\Area;
use app\common\model\db\Merchant;
use app\common\model\db\MerchantStore;
use app\common\model\db\ShopOrder;
use app\common\model\db\StoreOrder;
use app\common\model\service\StoreOrderService;
use app\foodshop\model\db\DiningOrder;
use app\foodshop\model\db\DiningOrderPay;
use app\group\model\db\GroupOrder;
use app\group\model\service\GroupOrderService;
use app\internal_card\model\db\InternalMembershipCardBill;
use app\internal_card\model\db\InternalMembershipCardBillRecord;
use app\internal_card\model\db\InternalMembershipCardLog;
use app\internal_card\model\db\InternalMembershipNames;
use app\job\order\StatisticOrderMoneyJob;
use app\mall\model\service\MallOrderCombineService;
use app\shop\model\service\order\ShopOrderService;
use app\storestaff\model\db\MerchantStoreDateBill;
use Carbon\Carbon;
use think\facade\Db;
use think\facade\Queue;

class InternalCardMoneyBillService
{
    public function internalCardBill($subMonth = 1): bool
    {
        $now = Carbon::now()->subMonths($subMonth);
        $firstDayOfLastMonth = $now->startOfMonth()->timestamp;
        $lastDayOfLastMonth = $now->endOfMonth()->timestamp;
        $merMoneyInfo = InternalMembershipCardLog::where('create_time', 'between', [$firstDayOfLastMonth, $lastDayOfLastMonth])
            ->where('operation_type', 'IN', '2,3')
            ->field("
                mer_id,
                SUM(CASE WHEN operation_type = 2 THEN money ELSE 0 END) AS pay_money,
                SUM(CASE WHEN operation_type = 3 THEN money ELSE 0 END) AS refund_money,
                SUM(CASE WHEN operation_type = 2 THEN service_fee ELSE 0 END) AS pay_service_fee,
                SUM(CASE WHEN operation_type = 3 THEN service_fee ELSE 0 END) AS refund_service_fee
                ")
            ->group('mer_id')
            ->select()
            ->toArray();

        $date = $now->format('Y-m');
        foreach ($merMoneyInfo as $mer) {
            $bill = InternalMembershipCardBill::where(['mer_id' => $mer['mer_id'], 'bill_date' => $date])->find();
            $payMoney = $mer['pay_money'] - $mer['refund_money'];
            $serviceFee = $mer['pay_service_fee'] - $mer['refund_service_fee'];
            $billMoney = $payMoney - $serviceFee;
            
            if(empty($bill)){
                InternalMembershipCardBill::insert([
                    'mer_id' => $mer['mer_id'],
                    'bill_date' => $date,
                    'pay_money' => $payMoney,
                    'bill_money' => $billMoney,
                    'wait_bill_money' => $payMoney - $serviceFee,
                    'service_fee' => $serviceFee,
                ]);
            }else{
                $bill->setAttrs([
                    'pay_money' => $payMoney,
                    'bill_money' => $billMoney,
                    'wait_bill_money' => $payMoney - $serviceFee - $bill['already_bill_money'],
                    'service_fee' => $serviceFee,
                ]);
                $bill->save();
            }
        }

        return true;
    }

    /**
     * 定时任务统计店铺支付金额
     * @return true
     */
    public function merchantStoreBill($date = ''): bool
    {
        if($date){
            $date = Carbon::parse($date)->format('Y-m-d');
        }else{
            $date = Carbon::now()->subDays()->format('Y-m-d');
        }
        
        $storeIds = MerchantStore::where(['status' => 1])->column('store_id');
        $orderType = [
            ShopOrderService::class,
            GroupOrderService::class,
            AppointOrderService::class,
            StoreOrderService::class,
            MallOrderCombineService::class,
            DiningOrder::class,
        ];
        fdump_api(['queue_data' => ['store_ids' => $storeIds, 'date' => $date]], 'task/StatisticStoreMoneyBillTask',1);
        foreach ($orderType as $order) {
            foreach ($storeIds as $storeId){
                $data = [
                    'order_class' => $order,
                    'store_id' => $storeId,
                    'date' => $date,
                ];
                Queue::later(random_int(1,600),StatisticOrderMoneyJob::class , $data,'store_date_bill');
            }
        }
        return true;
    }
    
    public function saveMerchantStoreDateBill($data): bool
    {
       $dateBill = MerchantStoreDateBill::where([
            'store_id' => $data['store_id'],
            'order_type' => $data['order_type'],
            'bill_date' => $data['bill_date'],
        ])->find();
       
       if(empty($dateBill)){
           if(empty($data['mer_id'])){
               fdump_api(['queue_data' => $data], 'job/saveMerchantStoreDateBill', 1);
               return false;
           }
           $dateBill = new MerchantStoreDateBill;
       }
       $dateBill->setAttrs($data);
       return $dateBill->save();
    }
    public function billList(array $params)
    {
        if(
            !empty($params['type']) && !in_array($params['type'], ['pay_money', 'bill_money', 'already_bill_money', 'wait_bill_money', 'service_fee'])) {
            throw_exception('筛选条件错误');
        }
        $params['bill_prefix'] = 'b.';
        $where = [];
        if(!empty($params['min_money'])){
            $where[] = [$params['bill_prefix'].$params['type'], '>=', $params['min_money']];
        }
        if(!empty($params['max_money'])){
            $where[] = [$params['bill_prefix'].$params['type'], '<=', $params['max_money']];
        }
        if(!empty($params['bill_date'])){
            $where[] = [$params['bill_prefix'].'bill_date', '=', $params['bill_date']];
        }
        $billList = InternalMembershipCardBill::withSearch(['bill_date', 'mer_id'], $params);
        if (!empty($params['date_group'])) {
            $billList = $billList->alias('b')->group('bill_date')
                ->field("
                    bill_date,
                    SUM(pay_money) pay_money, 
                    SUM(bill_money) bill_money, 
                    SUM(already_bill_money) already_bill_money,
                    SUM(service_fee) service_fee,
                    SUM(wait_bill_money) wait_bill_money
                ");
            $subQuery = $billList->buildSql();
            $billList = Db::table($subQuery . ' b')->where($where)->order('bill_date DESC');
            if(isset($params['bill_sum'])) {
                $billList = $billList
                    ->field("
                        SUM(pay_money) pay_money_sum, 
                        SUM(bill_money) bill_money_sum, 
                        SUM(wait_bill_money) wait_bill_money_sum,
                        SUM(already_bill_money) already_bill_money_sum,
                        SUM(service_fee) service_fee_sum
                    ")
                    ->select();
                $billList = $billList[0];
                array_walk($billList,function (&$item){
                    $item = $item ?: 0;
                });
            }else{
                if(!empty($params['export'])){
                    $billList = $billList->select()->toArray();
                    $title = '总账单记录';
                    $xlsCell = [
                        ['bill_date', '账单日期'],
                        ['pay_money', '交易金额(元)'],
                        ['bill_money', '总结算金额(元)'],
                        ['wait_bill_money', '待结算金额(元)'],
                        ['already_bill_money', '已结算金额(元)'],
                        ['service_fee', '服务费(元)'],
                    ];
                    $fileName = $title.date("Y-m-d", time());
                    $exportResult = export_excel($title, $xlsCell, $billList, $fileName);
                    return ['file_url' => $exportResult['url']];
                }else{
                    $billList = $billList->paginate(input('page_size', 15));
                }
            }
        } else {
            $search = [
                'merchant_name' => 'm.name',
            ];
            if(!empty($params['keywords']) && !empty($search[$params['search_type']])){
                $where[] = [$search[$params['search_type']], 'like', "{$params['keywords']}%"];
            }
            if(!empty($params['province_id'])){
                $where[] = ['m.province_id', '=', $params['province_id']];
            }
            if(!empty($params['city_id'])){
                $where[] = ['m.city_id', '=', $params['city_id']];
            }
            if(!empty($params['area_id'])){
                $where[] = ['m.area_id', '=', $params['area_id']];
            }
            $billList = $billList->alias('b')
                ->leftJoin('merchant m', 'b.mer_id = m.mer_id')
                ->where($where);
            if(isset($params['bill_sum'])) {
                $billList = $billList
                    ->field("
                        SUM(b.pay_money) pay_money_sum, 
                        SUM(b.bill_money) bill_money_sum, 
                        SUM(b.wait_bill_money) wait_bill_money_sum,
                        SUM(b.already_bill_money) already_bill_money_sum,
                        SUM(b.service_fee) service_fee_sum
                    ")
                    ->find();
            }else{
                $billList = $billList->field('b.*, m.name merchant_name, m.province_id, m.city_id, m.area_id');
                if(!empty($params['export'])){
                    $billList = $billList->select()
                        ->each(function ($item) {
                            if ($item && !empty($item['province_id'])) {
                                $item['area_address'] = Area::where('area_id', $item['province_id'])->value('area_name') .
                                    Area::where('area_id', $item['city_id'])->value('area_name') .
                                    Area::where('area_id', $item['area_id'])->value('area_name');
                            }
                        })->toArray();
                    $title = '账单记录';
                    $xlsCell = [
                        ['mer_id', '商家ID'],
                        ['merchant_name', '商家名称'],
                        ['area_address', '	商家区域'],
                        ['pay_money', '交易金额(元)'],
                        ['service_fee', '服务费(元)'],
                        ['bill_money', '总结算金额(元)'],
                        ['wait_bill_money', '待结算金额(元)'],
                        ['already_bill_money', '已结算金额(元)'],
                    ];
                    $fileName = $title.date("Y-m-d", time());
                    $exportResult = export_excel($title, $xlsCell, $billList, $fileName);
                    return ['file_url' => $exportResult['url']];
                }else{
                    $billList = $billList->paginate(input('page_size', 15))
                        ->each(function ($item) {
                            if ($item && !empty($item['province_id'])) {
                                $item['area_address'] = Area::where('area_id', $item['province_id'])->value('area_name') .
                                    Area::where('area_id', $item['city_id'])->value('area_name') .
                                    Area::where('area_id', $item['area_id'])->value('area_name');
                            }
                        });
                }
            }
        }
        
        return $billList;
    }
    
    public function merCardList(array $params)
    {
        if(
            !empty($params['type']) && !in_array($params['type'], ['pay_money', 'bill_money', 'service_fee'])) {
            throw_exception('筛选条件错误');
        }
        $search = [
            'card_name' => 'imc.card_name',
            'merchant_name' => 'm.name',
        ];
        $where = [];
        if(!empty($params['keywords']) && !empty($search[$params['search_type']])){
            $where[] = [$search[$params['search_type']], 'like', "{$params['keywords']}%"];
        }
        $params['log_prefix'] = 'l.';
        $billCardList = InternalMembershipCardLog::withSearch(['bill_date', 'mer_id'], $params)
            ->alias('l')
            ->join('internal_membership_card imc', 'l.card_id = imc.card_id')
            ->join('merchant m', 'l.mer_id = m.mer_id')
            ->group('imc.card_name, l.mer_id')
            ->where($where);
         $subQuery = $billCardList
             ->field(
                "l.id,l.mer_id, imc.card_name, m.name merchant_name, 
                (SUM(CASE WHEN operation_type = 2 THEN l.money ELSE 0 END) - SUM(CASE WHEN operation_type = 3 THEN l.money ELSE 0 END)) AS pay_money,
                (SUM(CASE WHEN operation_type = 2 THEN l.service_fee ELSE 0 END) -  SUM(CASE WHEN operation_type = 3 THEN l.service_fee ELSE 0 END)) service_fee, 
                ((SUM(CASE WHEN operation_type = 2 THEN l.money ELSE 0 END) - SUM(CASE WHEN operation_type = 3 THEN l.money ELSE 0 END)) - (SUM(CASE WHEN operation_type = 2 THEN l.service_fee ELSE 0 END) - SUM(CASE WHEN operation_type = 3 THEN l.service_fee ELSE 0 END))) bill_money
             ")
             ->buildSql();
        $where = [];
        if(!empty($params['min_money'])){
            $where[] = [$params['type'], '>=', $params['min_money']];
        }
        if(!empty($params['max_money'])){
            $where[] = [$params['type'], '<=', $params['max_money']];
        }
        if(isset($params['bill_sum'])) {
            $billCardList = Db::table($subQuery . ' l')
                ->where($where)
                ->field("
                        SUM(pay_money) pay_money_sum, 
                        SUM(bill_money) bill_money_sum,
                        SUM(service_fee) service_fee_sum
                    ")
                ->select();
            $billCardList = $billCardList[0];
           
            array_walk($billCardList,function (&$item){
                $item = $item ?: 0;
            });
        }else{
            $billCardList = Db::table($subQuery . ' l')
                ->where($where);
            if(!empty($params['export'])){
                $billCardList =  $billCardList->select()
                    ->toArray();
                foreach ($billCardList as &$item) {
                    $areaIds = InternalMembershipNames::where('card_name',$item['card_name'])->value('use_area_ids');
                    $item['area_name'] = Area::where('area_id', 'IN', $areaIds)->column('area_name');
                    $item['area_name'] = implode(',', $item['area_name']);
                }
                $title = "商家{$params['bill_date']}月卡消费记录";
                $xlsCell = [
                    ['card_name', '卡名称'],
                    ['merchant_name', '商家名称'],
                    ['area_name', '	卡区域'],
                    ['pay_money', '交易金额(元)'],
                    ['service_fee', '服务费(元)'],
                    ['bill_money', '总结算金额(元)'],
                ];
                $fileName = $title.date("Y-m-d", time());
                $exportResult = export_excel($title, $xlsCell, $billCardList, $fileName);
                return ['file_url' => $exportResult['url']];
            }else{
                $billCardList =  $billCardList->paginate(input('page_size', 15))
                    ->toArray();
                foreach ($billCardList['data'] as &$item) {
                    $areaIds = InternalMembershipNames::where('card_name',$item['card_name'])->value('use_area_ids');
                    $item['area_name'] = Area::where('area_id', 'IN', $areaIds)->column('area_name');
                    $item['area_name'] = implode(',', $item['area_name']);
                }
            }
        }

        return $billCardList;
    }

    public function merBill(array $params)
    {
        $bill = InternalMembershipCardBill::where([
            'mer_id' => $params['mer_id'],
            'bill_date' => $params['bill_date']
        ])->find();
        if(empty($bill)) {
            throw_exception("{$params['bill_date']}月账单未生成");
        }
        $data = InternalMembershipCardBillRecord::insert([
            'admin_uid' => $params['admin_uid'],
            'mer_id' => $params['mer_id'],
            'bill_date' => $params['bill_date'],
            'bill_money' => $params['bill_money'],
            'bill_type' => $params['bill_type'],
            'tax_ticket_type' => $params['tax_ticket_type'],
            'tax_point' => $params['tax_point'],
            'remark' => $params['remark'],
        ]);

        $bill->already_bill_money = bcadd($params['bill_money'], $bill->already_bill_money, 2);
        $bill->wait_bill_money = bcsub($bill->wait_bill_money, $params['bill_money'], 2);
        $bill->save();
        
        return $data;
    }
    public function merBillRecord(array $params)
    {
        $where = [];
        $search = [
            'admin_user_name' => 'a.account',
            'tax_point' => 'br.tax_point',
            'bill_money' => 'br.bill_money',
        ];
        if(!empty($params['keywords']) && !empty($search[$params['search_type']])){
            $where[] = [$search[$params['search_type']], '=', "{$params['keywords']}"];
        }
        if(!empty($params['bill_type'])){
            $where[] = ['bill_type', '=', $params['bill_type']];
        }
        if(!empty($params['tax_ticket_type'])){
            $where[] = ['tax_ticket_type', '=', $params['tax_ticket_type']];
        }
        $data = InternalMembershipCardBillRecord::alias('br')
            ->join('admin a', 'br.admin_uid = a.id')
            ->where([
                'bill_date' => $params['bill_date'],
                'mer_id' => $params['mer_id']
            ])
            ->append(['bill_type_text', 'tax_ticket_type_text'])
            ->field('br.*,a.account admin_user_name')
            ->where($where);
        if(!empty($params['export'])) {
            $merchantName = Merchant::where('mer_id', $params['mer_id'])->value('name');
            $data = $data->select()->toArray();
            $title = "{$merchantName}商家{$params['bill_date']}月结算记录";
            $xlsCell = [
                ['admin_user_name', '经办人'],
                ['bill_type_text', '结算方式'],
                ['tax_ticket_type_text', '票据类型'],
                ['tax_point', '税点'],
                ['bill_money', '结算金额(元)'],
                ['create_time', '时间'],
            ];
            $fileName = $title.date("Y-m-d", time());
            $exportResult = export_excel($title, $xlsCell, $data, $fileName);
            return ['file_url' => $exportResult['url']];
        }else{
            $data = $data->paginate(input('page_size', 15));
        }

        return $data;
    }
    
    public function setServiceFee($order, $percent): bool
    {
        fdump_api(['order' => $order, 'percent' => $percent], 'ecard/setServiceFee', 1);
        $cardOrders = [];
        try{
            switch ($order['order_type']) {
                case 'shop':
                case 'shop_offline':
                    if(isset($order['order_id'])){
                        $order['real_orderid'] = ShopOrder::where('order_id', $order['order_id'])->value('real_orderid');
                    }
                    $cardOrders = InternalMembershipCardLog::whereIn('business_type','shop, shop_offline')
                        ->where('order_id', $order['real_orderid'])
                        ->select();
                    break;
                case 'group':
                    if(isset($order['order_id'])){
                        $order['real_orderid'] = GroupOrder::where('order_id', $order['order_id'])->value('real_orderid');
                    }
                    $cardOrders = InternalMembershipCardLog::whereIn('business_type','group')
                        ->where('order_id', $order['real_orderid'])
                        ->select();
                    break; 
                case 'store':
                case 'cash':
                    if(isset($order['order_id'])){
                        $order['orderid'] = StoreOrder::where('order_id', $order['order_id'])->value('orderid');
                    }
                    $cardOrders = InternalMembershipCardLog::whereIn('business_type','store, cash')
                        ->where('order_id', $order['orderid'])
                        ->select();
                    break;
                case 'appoint':
                    if(isset($order['order_id'])){
                        $order['real_orderid'] = AppointOrder::where('order_id', $order['order_id'])->value('real_orderid');
                    }
                    $cardOrders = InternalMembershipCardLog::where('business_type','appoint')
                        ->where('order_id', $order['real_orderid'])
                        ->select();
                    break;
                case 'dining':
                    $orderIds = DiningOrderPay::where('order_id', $order['order_id'])->column('orderid');
                    $cardOrders = InternalMembershipCardLog::whereIn('business_type','dining')
                        ->whereIn('order_id', $orderIds)
                        ->select();
                    break;
                case 'mall':
                    $cardOrders = InternalMembershipCardLog::whereIn('business_type','mall')
                        ->where('order_id', $order['real_orderid'])
                        ->select();
                    break;
            }
            
            foreach ($cardOrders as $cardOrder) {
                $cardOrder->service_fee = round($cardOrder->money * $percent / 100, 2);
                $cardOrder->save();
            }
        }catch (\Exception $e){
            fdump_api(['error_msg' => $e->getMessage()], 'ecard/setServiceFee', 1);
        }
        return true;
    }

}