<?php
/**
 * 打印service
 * Author: hengtingmei
 * Date Time: 2021/05/28 09:51
 */

namespace app\group\model\service\order_print;

use app\common\model\service\UserService;
use app\merchant\model\service\print_order\PrintHaddleService as PrintHaddleBase;
use app\merchant\model\service\MerchantStoreService;
use app\group\model\service\order\GroupOrderService;
use app\merchant\model\service\print_order\OrderprintService;
use app\pay\model\service\PayService;
class PrintHaddleService {
    public $printBase = null;
    public function __construct()
    {
        $this->printBase = new PrintHaddleBase();
    }

    /**
     * 打印团购订单
     * @param int $orderId 订单id
     * @param int $status 打印状态（哪种状态的打印机可打印） -1为不限制
     * @return void
     */
    public function groupOrderPrint($orderId, $status = 0)
    {
        // 订单信息
        $order =  (new GroupOrderService())->getOne(['order_id' => $orderId]);
        if (empty($order)) return false;

        // 打印机列表
        $prints = (new OrderprintService())->getPrintList(['store_id'=>$order['store_id']]);
        fdump_api([$prints,$orderId,$status],'group_order/afterPay',1);
        if (empty($prints['list'])) return false;
        $prints = $prints['list'];

        // 用户信息
        $user = (new UserService())->getUser($order['uid']);
        $nickname = isset($user['nickname']) ? $user['nickname'] : '';

        // 店铺信息
        $store = (new MerchantStoreService())->getStoreByStoreId($order['store_id']);

        $printFormat = cfg('group_print_format');
        $arr = explode("\r\n", $printFormat);
        $arr = array_map(function ($r) {
            return "<FH><FW>" . $r . "</FW></FH>";
        }, $arr);
        $printFormat = implode("\n", $arr);

        $printFormat = preg_replace('/\{user_name\}/', $nickname, $printFormat);
        $printFormat = preg_replace('/\{user_phone\}/', $order['phone'], $printFormat);
        $printFormat = preg_replace('/\{user_address\}/', $order['adress'], $printFormat);
        $printFormat = preg_replace('/\{orderid\}/', $order['real_orderid'], $printFormat);
        
        $printFormat = preg_replace('/\{goods_name\}/', $order['order_name'], $printFormat);
        $printFormat = preg_replace('/\{goods_count\}/', $order['num'], $printFormat);
        $printFormat = preg_replace('/\{goods_price\}/', $order['total_money'], $printFormat);
        $printFormat = preg_replace('/\{minus_price\}/', $order['wx_cheap'], $printFormat);
        $printFormat = preg_replace('/\{true_price\}/', $order['total_money'] - $order['wx_cheap'], $printFormat);

        // 处理时间显示
        $useTime = $order['use_time']>0 ? date("Y-m-d H:i:s", $order['use_time']) : L_('未使用');

        $printFormat = preg_replace('/\{buy_time\}/', date("Y-m-d H:i:s", $order['add_time']), $printFormat);
        $printFormat = preg_replace('/\{pay_time\}/', date("Y-m-d H:i:s", $order['pay_time']), $printFormat);
        $printFormat = preg_replace('/\{use_time\}/', $useTime, $printFormat);
        
        $printFormat = preg_replace('/\{store_name\}/', ($store['name'] ?? ''), $printFormat);
        $printFormat = preg_replace('/\{store_phone\}/', ($store['phone'] ?? ''), $printFormat);
        $printFormat = preg_replace('/\{store_address\}/', ($store['adress'] ?? ''), $printFormat);
        
        if (empty($order['paid'])) {
            $payStatus = '未支付';
        } else {
            if (empty($order['status'])) {
                $payStatus = '未消费';
            } elseif ($order['status'] == 1) {
                $payStatus = '已消费';
            } elseif ($order['status'] == 2) {
                $payStatus = '已完成';
            } elseif ($order['status'] == 3) {
                $payStatus = '已退款';
            }
        }

        // 获得支付信息
        $payInfo = [];
        $payType = '';
        if($order['pay_type'] == 'offline'){
            $payType = L_('线下支付');
        }elseif($order['third_id']){
            $payInfo = (new PayService())->getPayOrderData([$order['third_id']]);
            if($payInfo){
                $payInfo = $payInfo[$order['third_id']] ?? [];
                $payInfo['pay_type_chanel'] = '';
                if(isset($payInfo['pay_type'])){
                    $payInfo['pay_type_chanel'] =  ($payInfo['pay_type'] ? $payInfo['pay_type_txt'] : '').($payInfo['channel'] ? '('.$payInfo['channel_txt'].')' : '');
                }
                $payType = $payInfo['channel_txt'];
                
            }
        }
        if (empty($payType) && $order['paid']) {
            $payType = L_('余额支付');
        }
        $printFormat = preg_replace('/\{payStatus\}/', $payStatus, $printFormat);
        $printFormat = preg_replace('/\{pay_type\}/', $payType, $printFormat);
        $printFormat = preg_replace('/\{print_time\}/', date('Y-m-d H:i:s'), $printFormat);
        
        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }
            if ($status != -1) {
                $statusArr = explode(',', $usePrinter['paid']);
                if (!in_array($status, $statusArr)) {
                    continue;
                }
            }
            fdump_api([$printFormat,$usePrinter,$orderId],'group_order/afterPay',1);
            // 去打印
            $this->printBase->toPrint($printFormat, $usePrinter);
        }
        return false;
    }
}