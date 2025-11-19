<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/27 18:00
 */

namespace app\community\model\service;

use app\community\model\db\HouseVillagePayCashierOrder;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillagePropertyPaylist;
use app\community\model\db\HouseVillagePaymentStandardBind;
use app\community\model\db\HouseVillagePaymentStandard;
use app\community\model\db\HouseVillagePaymentPaylist;

class HouseVillagePayCashierOrderService
{
    /**
     * 添加收银台总订单
     * @author: wanziyang
     * @date_time: 2020/4/23 13:11
     * @param array $data 添加的数据
     * @return int
     */
    public function add_house_village_pay_cashier_order($data) {
        // 初始化 数据层
        $db_house_village_pay_cashier_order= new HouseVillagePayCashierOrder();
        $cashier_id = $db_house_village_pay_cashier_order->add_order($data);
        return $cashier_id;
    }

    /**
     * 获取订单列表
     * @author: wanziyang
     * @date_time: 2020/4/28 20:58
     * @param array $where
     * @param string|true $field
     * @param string|int $page
     * @param string $order
     * @param int $page_size
     * @return mixed
     */
    public function get_limit_list($where,$field='b.name,b.address,b.phone,a.*',$page='',$order='a.cashier_id DESC',$page_size=10)
    {
        $db_house_village_pay_cashier_order = new HouseVillagePayCashierOrder();
        $db_house_village_pay_order = new HouseVillagePayOrder();
        $service_house_village = new HouseVillageService();
        $service_login = new ManageAppLoginService();
        $list = $db_house_village_pay_cashier_order->get_limit_list($where, $field, $page, $order, $page_size);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        $site_url = cfg('site_url');
        $base_url = $service_login->base_url;
        foreach ($list as $k => $v) {
            $list[$k]['address'] = $service_house_village->getSingleFloorRoom($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$v['village_id']);
            if (isset($v['pay_time']) && $v['pay_time']>0) {
                $list[$k]['pay_time'] = date('Y-m-d H:i:s',$v['pay_time']);
            }
            $pay_order_list = $db_house_village_pay_order->get_list(['cashier_id' => $v['cashier_id']], 'order_name,order_type,order_id')->toArray();
            if (count($pay_order_list) > 1) {
                $list[$k]['order_name'] = '合计费用';
                $list[$k]['icon'] = $site_url. '/v20/public/static/community/images/cashier/all.png';
                $list[$k]['url'] = $site_url . $base_url . "pages/Community/CollectMoney/allHistoryPayMoney?cashier_id=".$v['cashier_id'];
                $list[$k]['type'] = 'more_order';
            } elseif(count($pay_order_list) == 1) {
                $list[$k]['order_id'] = $pay_order_list[0]['order_id'];
                $list[$k]['type'] = 'one_order';
                $list[$k]['order_name'] = isset($pay_order_list[0]['order_name']) ? $pay_order_list[0]['order_name'] : '';
                $list[$k]['url'] = $site_url . $base_url . "pages/Community/CollectMoney/HistoryPayMoney?cashier_id=".$v['cashier_id'].'&order_id='.$pay_order_list[0]['order_id'];
                if (isset($pay_order_list[0]['order_type'])) {
                    switch ($pay_order_list[0]['order_type']) {
                        case 'water':
                            $list[$k]['icon'] = $site_url. '/v20/public/static/community/images/cashier/water.png';
                            break;
                        case 'electric':
                            $list[$k]['icon'] = $site_url. '/v20/public/static/community/images/cashier/electric.png';
                            break;
                        case 'property':
                            $list[$k]['icon'] = $site_url. '/v20/public/static/community/images/cashier/property.png';
                            break;
                        case 'gas':
                            $list[$k]['icon'] = $site_url. '/v20/public/static/community/images/cashier/gas.png';
                            break;
                        case 'custom':
                            $list[$k]['icon'] = $site_url. '/v20/public/static/community/images/cashier/custom.png';
                            break;
                        default:
                            $list[$k]['icon'] = $site_url. '/v20/public/static/community/images/cashier/custom.png';
                    }
                }
            }
            $arr = array();
            for ($i = 0; $i < 6; $i++) {
                switch ($i) {
                    case 0:
                        $arr[$i]['title'] = '支付方式';
                        $arr[$i]['name'] = !empty($v['pay_name'])?$v['pay_name']:'线上支付';
                        $list[$k]['list'] = $arr;
                        break;
                    case 1:
                        $arr[$i]['title'] = '业主姓名';
                        $arr[$i]['name'] = $v['name'];
                        $list[$k]['list'] = $arr;
                        break;
                    case 2:
                        $arr[$i]['title'] = '联系方式';
                        $arr[$i]['name'] = $v['phone'];
                        $list[$k]['list'] = $arr;
                        break;
                    case 3:
                        $arr[$i]['title'] = '地址';
                        $arr[$i]['name'] = $v['address'];
                        $list[$k]['list'] = $arr;
                        break;
                    case 4:
                        $arr[$i]['title'] = '支付时间';
                        $arr[$i]['name'] = $v['pay_time'];
                        $list[$k]['list'] = $arr;
                        break;
                    case 5:
                        $arr[$i]['title'] = '备注';
                        $arr[$i]['name'] = $v['remarks'];
                        $list[$k]['list'] = $arr;
                        break;
                }
            }
        }
        return $list;
    }

    /**
     * 支付
     * @author: wanziyang
     * @date_time: 2020/4/23 13:16
     * @param int $cashier_id 订单id
     * @param string $remarks 备注
     * @return array
     */
    public function cashier_pay($cashier_id,$remarks = '') {
        if (!$cashier_id) {
            return ['error_code'=>1,'msg'=>'参数传递出错！'];
        }

        $db_house_village_pay_cashier_order= new HouseVillagePayCashierOrder();
        $where = [];
        $where[] = ['cashier_id', '=', $cashier_id];
        $cashier_order = $db_house_village_pay_cashier_order->get_one($where);
        if($cashier_order['paid'] > 0){
            return ['error_code'=>1,'msg'=>'该订单已经支付！'];
        }
        $where_save = [];
        $where_save[] = ['cashier_id','=',$cashier_id];
        $data = [
            'paid' => 1,
            'pay_time' => time(),
        ];
        $res = $db_house_village_pay_cashier_order->save_one($where_save,$data);
        if (!$res) {
            return ['error_code'=>1,'msg'=>'支付失败！'];
        }
        //查询订单
        $db_house_village_pay_order = new HouseVillagePayOrder();

        $order_list = $db_house_village_pay_order->get_list(['cashier_id'=>$cashier_id]);
        if (!$order_list) {
            return ['error_code'=>1,'msg'=>'没有子订单！'];
        }
        // 跟新订单状态
        $data['paid'] = 1;
        $data['pay_time'] = time();
        $data['pay_type'] = ($cashier_order['pay_type']==0) ? 0 : 1;
        if ($remarks) {
            $data['remarks'] = $remarks;
        }

        // 初始化 数据层
        $db_house_village_user_bind = new HouseVillageUserBind();
        $db_house_village_property_paylist = new HouseVillagePropertyPaylist();
        $db_house_village_payment_standard_bind = new HouseVillagePaymentStandardBind();
        $db_house_village_payment_standard = new HouseVillagePaymentStandard();
        $db_house_village_payment_paylist = new HouseVillagePaymentPaylist();

        foreach ($order_list as $key => $value) {
            $db_house_village_pay_order->save_one(['order_id'=>$value['order_id']],$data);
            $where_one = [];
            $where_one[] = ['order_id','=',$value['order_id']];
            $now_order = $db_house_village_pay_order->get_one($where_one);

            switch($now_order['order_type']){
                case 'property':
                    $bind_field = 'property_price';
                    $bind_where = [];
                    $bind_where[] = ['pigcms_id','=',$now_order['bind_id']];
                    $now_user_info = $db_house_village_user_bind->getOne($bind_where);
                    $paylist_data['bind_id'] = $now_order['bind_id'];
                    $paylist_data['uid'] = $now_order['uid'];
                    $paylist_data['village_id'] = $now_order['village_id'];
                    $paylist_data['property_month_num'] = $now_order['property_month_num'];
                    $paylist_data['presented_property_month_num'] = $now_order['presented_property_month_num'];
                    $paylist_data['house_size'] = $now_order['house_size'];
                    $paylist_data['property_fee'] = $now_order['property_fee'];
                    $paylist_data['floor_type_name'] = $now_order['floor_type_name'];


                    if($now_user_info['property_endtime']){
                        $paylist_data['start_time'] = $now_user_info['property_endtime'];
                        $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", $now_user_info['property_endtime']);
                    }else{
                        if($now_user_info['add_time'] > 0){
                            $paylist_data['start_time'] = $now_user_info['add_time'] ;
                            $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", $now_user_info['add_time']);
                        }else{
                            $paylist_data['start_time'] = time();
                            $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", time());
                        }

                    }

                    $paylist_data['add_time'] = time();
                    $paylist_data['order_id'] = $now_order['order_id'];

                    $paylist_data['end_time'] = strtotime(date('Y-m-d 23:59:59',$paylist_data['end_time']));
                    $db_house_village_property_paylist->add_one($paylist_data);

                    //同步物业到期时间
                    $where = [];
                    $where[] = ['uid','=',$now_order['uid']];
                    $where[] = ['village_id','=',$now_order['village_id']];
                    $where[] = ['pigcms_id','=',$now_order['bind_id']];

                    $db_house_village_user_bind->saveOne($where, ['property_endtime'=>$paylist_data['end_time']]);
                    $tmp_order['desc'] = '用户交物业费';
                    break;
                case 'water':
                    $bind_field = 'water_price';
                    $tmp_order['desc'] = '用户交水费';
                    break;
                case 'electric':
                    $bind_field = 'electric_price';
                    $tmp_order['desc'] = '用户交电费';
                    break;
                case 'gas':
                    $bind_field = 'gas_price';
                    $tmp_order['desc'] = '用户交燃气费';
                    break;
                case 'park':
                    $bind_field = 'park_price';
                    $tmp_order['desc'] = '用户交车位费';
                    break;
                case 'custom_payment':
                    $bind_field = '';
                    $tmp_order['desc']= $now_order['order_name'];
                    // 计算缴费时间
                    $bind_payment = $db_house_village_payment_standard_bind->get_one(['bind_id'=>$now_order['payment_bind_id']]);
                    $payment_standard = $db_house_village_payment_standard->get_one(['standard_id'=>$bind_payment['standard_id']]);
                    $paylist_data = array();
                    $paylist_data['bind_id'] = $now_order['bind_id'];
                    $paylist_data['uid'] = $now_order['uid'];
                    $paylist_data['village_id'] = $now_order['village_id'];
                    $paylist_data['paid_cycle'] = $now_order['payment_paid_cycle'];

                    switch ($payment_standard['cycle_type']) {
                        case 'Y': // 年
                            // 计算到期时间 = 开始时间 + 已缴时间（收费周期*已缴周期）
                            $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " years", $bind_payment['start_time']);
                            $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " years", $start_time);;
                            break;
                        case 'M': //月
                            $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " months", $bind_payment['start_time']);
                            $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " months", $start_time);;
                            break;
                        case 'D': // 日
                            $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " days", $bind_payment['start_time']);
                            $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " days", $start_time);;
                            break;
                    }
                    $paylist_data['start_time'] = $start_time ? $start_time : 0;
                    $paylist_data['end_time'] = $end_time ? $end_time : 0;;
                    $paylist_data['add_time'] = time();
                    $paylist_data['order_id'] = $now_order['order_id'];
                    $db_house_village_payment_paylist->add_one($paylist_data);
                    //修改绑定缴费项目
                    $save_paid_cycle=$bind_payment['paid_cycle']+$now_order['payment_paid_cycle'];
                    $db_house_village_payment_standard_bind->save_one(['bind_id'=>$now_order['payment_bind_id']],['paid_cycle'=>$save_paid_cycle]);
                default:
                    $bind_field = '';
                    $tmp_order['desc']= $now_order['order_name'];
            }



            // 欠费更新
            if(!empty($bind_field)){
                if (empty($now_user_info)) {
                    $bind_where = [];
                    $bind_where[] = ['pigcms_id','=',$now_order['bind_id']];
                    $now_user_info = $db_house_village_user_bind->getOne($bind_where);
                }
                if($now_user_info[$bind_field] - $now_order['money'] >= 0){
                    $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'];
                }else{
                    $data_bind[$bind_field] = 0;
                }
                $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'] >= 0 ? $now_user_info[$bind_field] - $now_order['money'] : 0;

                $where_bind = [];
                $where_bind[] = ['pigcms_id','=',$now_user_info['pigcms_id']];
                $db_house_village_user_bind->saveOne($where_bind,$data_bind);
            }

            // 小票打印start
            // $printHaddle = new PrintVillage();
            // $printHaddle->printit($order_id);
            // 小票打印end
        }
        return ['error_code'=>0,'msg'=>'提交成功！'];
    }

    /**
     * 获取条件下订单数量
     * @author: wanziyang
     * @date_time: 2020/4/29 10:46
     * @param array $where 查询条件
     * @return \think\Collection
     */
    public function get_house_village_pay_order_count($where) {
        $db_house_village_pay_order = new HouseVillagePayOrder();
        $field = 'COUNT(order_id) as count,order_id';
        $count_info = $db_house_village_pay_order->get_one($where,$field);
        if (!$count_info['count']) {
            $count_info['count'] = 0;
        }
        return $count_info;
    }

    /**
     * 获取总订单
     * @author: wanziyang
     * @date_time: 2020/4/29 13:09
     * @param $where
     * @param bool $field
     * @return array|int
     */
    public function get_house_village_pay_cashier_order($where,$field =true) {
        $db_house_village_pay_cashier_order = new HouseVillagePayCashierOrder();
        $info = $db_house_village_pay_cashier_order->get_one($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }

    /**
     * 获取订单列表
     * @author: wanziyang
     * @date_time: 2020/4/29 14:09
     * @param $where
     * @param bool $field
     * @return array|int
     */
    public function get_house_village_pay_order_list($where,$field =true) {
        $db_house_village_pay_order = new HouseVillagePayOrder();
        $info = $db_house_village_pay_order->get_list($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }
}