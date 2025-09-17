<?php
/**
 * 餐饮订单购物车service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/03 10:44
 */

namespace app\foodshop\model\service\export;
use app\common\model\service\export\ExportService as BaseExportService;
use app\foodshop\model\service\order\DiningOrderService;
class ExportService {
    
	/**
     * 添加导出计划任务
     * @param $title string 标题
     * @param $param array 数据
     * $param = [
     *         'type',//导出业务唯一标识
     * ]
     * @return array
     */
    public function addDiningOrderExport($param, $systemUser = [], $merchantUser = [], $staffUser= []){
        $title = cfg('meal_alias_name').'订单';

        
		$param['type'] = 'dining';
		$param['service_path'] = '\app\foodshop\model\service\export\ExportService';
		$param['service_name'] = 'diningOrderExport';
		$param['rand_number'] = time();
        $param['system_user']['area_id'] = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        $param['staff_user']['id'] = $staffUser ? $staffUser['id'] : 0;
        $param['staff_user']['store_id'] = $staffUser ? $staffUser['store_id'] : 0;
        $result = (new BaseExportService())->addExport($title, $param);
        return $result;

        
    }

    /**
     * 导出订单
     * @param $param array 数据
     * $param = [
     *         'type',//导出业务唯一标识
     * ]
     * @return array
     */
    public function diningOrderExport($param){
        $merchantUser = isset($param['merchant_user']) ? $param['merchant_user'] : [];//商家信息
        $systemUser = isset($param['system_user']) ? $param['system_user'] : [];//平台管理员信息
        $staffUser = isset($param['staff_user']) ? $param['staff_user'] : [];//店员管理员信息
        $param['pageSize'] = 0;
        ($staffUser && $staffUser['store_id']) && $param['store_id'] = $staffUser['store_id'];
        $orderList = (new DiningOrderService())->getOrderListLimit($param, $systemUser, $merchantUser);
        $orderList = $orderList['list'];
        $csvHead = array(
            L_('订单编号'),
            L_('店铺名称'),
            L_('商家名称'),
            L_('订单状态'),
            L_('商品总数量'),
            L_('商品总金额'),
            L_('订单实付金额'),
            L_('线下支付金额'),
            L_('线上支付金额'),
            L_('平台余额支付'),
            L_('商家余额支付'),
            L_('桌台号'),
            L_('支付方式'),
            L_('平台结算给商家金额（元）'),
            L_('下单时间'),
            L_('支付时间')
        );

        $csvData = [];
        if (!empty($orderList)) {
            $tmp_id = 0;
            foreach ($orderList as $orderKey => $value) {
                $csvData[$orderKey] = [
                    '`'.$value['real_orderid'].' ',
                    $value['store_name'],
                    $value['merchant_name'],
                    $value['order_status_txt'],
                    $value['goods_num'],
                    $value['total_price'],
                    $value['pay_price'],
                    $value['offline_money'],
                    $value['online_money'] ?? 0,
                    $value['system_balance'],
                    $value['balance_merchant'] ?? 0,
                    $value['table_id'] ?? '',
                    $value['pay_type_txt'] ?? '',
                    $value['settlement_merchant_money'] ?? '',
                    $value['create_time'],
                    $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '',
                ];
            }
        }
        
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.csv';
//        $csvFileName = './runtime/' . iconv("utf-8", "gb2312", $filename);
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);
    }

    

}