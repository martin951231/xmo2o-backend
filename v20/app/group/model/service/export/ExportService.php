<?php
/**
 * 团购导出service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/11/23 17:34
 */

namespace app\group\model\service\export;
use app\common\model\db\MerchantStore;
use app\common\model\service\export\ExportService as BaseExportService;
use app\group\model\service\order\GroupOrderService;

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
    public function addExport($type, $param, $systemUser = [], $merchantUser = [], $staffUser= []){

        $param['rand_number'] = time();
        $param['system_user']['area_id'] = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        $param['staff_user']['id'] = $staffUser ? $staffUser['id'] : 0;
        $param['staff_user']['store_id'] = $staffUser ? $staffUser['store_id'] : 0;
        switch ($type){
            case 'combine_order':
                // 优惠组合订单
                $title = cfg('meal_alias_name').'优惠组合订单';
                $param['type'] = 'combine_order';
                $param['service_path'] = '\app\group\model\service\export\ExportService';
                $param['service_name'] = 'groupCombineOrderExport';
                break;
        }


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
    public function groupCombineOrderExport($param){
        $param['pageSize'] = 0;
        $orderList = (new GroupOrderService())->getGroupCombineOrderList($param);
        $orderList = $orderList['list'];
        $csvHead = array(
            L_('订单编号'),
            L_('优惠组合信息'),
            L_('订单信息'),
            L_('订单状态'),
            L_('下单时间'),
            L_('支付时间'),
            L_('订单用户'),
            L_('订单有效期'),
        );

        $csvData = [];
        if (!empty($orderList)) {
            $tmp_id = 0;
            foreach ($orderList as $orderKey => $value) {
                // 优惠组合信息
                $combineInfo = L_('优惠组合ID： X1 优惠组合价： X2 优惠组合名称： X3',['X1'=>$value['combine_id'],'X2'=>cfg('Currency_symbol').$value['price'],'X3'=>$value['title']]);

                // 订单信息
                $orderInfo = L_('数量： X1 总价： X2',['X1'=>$value['num'],'X2'=>cfg('Currency_symbol').$value['price']]);

                // 用户信息
                $userInfo = L_('用户名：X1 订单手机号： X2',['X1'=>$value['nickname'],'X2'=>$value['phone']]);


                $csvData[$orderKey] = [
                    '`'.$value['real_orderid'].' ',
                    $combineInfo,
                    $orderInfo,
                    $value['status_str'],
                    $value['add_time_str'],
                    $value['pay_time_str'],
                    $userInfo,
                    $value['can_use_end_time'],
                ];
            }
        }
        
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.csv';
//        $csvFileName = './runtime/' . iconv("utf-8", "gb2312", $filename);
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);
    }

    

    
	/**
     * 添加团购订单导出计划任务
     * @param $title string 标题
     * @param $param array 数据
     * $param = [
     *   'type',  // 导出业务唯一标识
     * ]
     * @return array
     */
    public function addOrderExport($type, $param, $systemUser = [], $merchantUser = [], $staffUser= []){

        $param['rand_number'] = time();
        $param['system_user']['area_id'] = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        $param['staff_user']['id'] = $staffUser ? $staffUser['id'] : 0;
        $param['staff_user']['store_id'] = $staffUser ? $staffUser['store_id'] : 0;
        switch ($type){
            case 'goods_order':
                // 团购订单
                $title = '团购订单';
                $param['type'] = 'goods_order';
                $param['service_path'] = '\app\group\model\service\export\ExportService';
                $param['service_name'] = 'groupGoodsOrderExport';
                break;
        }

        $result = (new BaseExportService())->addExport($title, $param);
        return $result;

        
    }

    /**
     * 导出订单
     * @param $param array 数据
     * $param = [
     *   'type',  // 导出业务唯一标识
     * ]
     * @return array
     */
    public function groupGoodsOrderExport($param){
        $param['pageSize'] = 0;
        $orderList = (new GroupOrderService())->getGroupOrderList($param,1);
        $orderList = $orderList['list'];
        $csvHead = array(
            L_('订单编号'),
            L_('名称'),
            L_('店铺'),
            L_('订单信息'),
            L_('订单状态'),
            L_('下单时间'),
            L_('支付时间'),
            L_('订单用户'),
            L_('订单有效期'),
        );

        $csvData = [];
        if (!empty($orderList)) {
            $tmp_id = 0;
            $status_name = [0=>'未消费',1=>'已消费',2=>'已完成',3=>'已退款',4=>'已取消',5=>'部分消费',6=>'部分退款',7=>'未付款'];
            $store_model = (new MerchantStore());
            foreach ($orderList as $orderKey => $value) {
                if($value['effective_type']==1){
                    $can_use_end_time = date('Y-m-d H:i:s',($value['add_time']+$value['deadline_time']*24*3600));
                }else{
                    $can_use_end_time = date('Y-m-d H:i:s',$value['deadline_time']);
                }
                //获取店铺名称
                $store_name = $store_model->where(['store_id'=>$value['store_id']])->column('name');
                // 订单信息
                $orderInfo = L_('数量： X1 总价： X2',['X1'=>$value['num'],'X2'=>cfg('Currency_symbol').$value['price']]);

                // 用户信息
                $userInfo = L_('用户名：X1 订单手机号： X2',['X1'=>$value['user_name'],'X2'=>$value['user_phone']]);
                
                $csvData[$orderKey] = [
                    '`'.$value['real_orderid'].' ',
                    $value['order_name'],
                    $store_name?$store_name[0]:'',
                    $orderInfo,
                    $status_name[$value['status']]??'',
                    $value['addTime'],
                    $value['payTime'],
                    $userInfo,
                    $can_use_end_time,
                ];
            }
        }
        
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);
    }


}