<?php

namespace app\mall\model\service;

use app\common\model\db\SystemCouponHadpull;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\UserService;
use app\employee\model\db\EmployeeCardLog;
use app\employee\model\db\EmployeeCardUser;
use app\internal_card\model\db\InternalMembershipCard;
use app\internal_card\model\db\InternalMembershipCardLog;
use app\internal_card\model\service\InternalCardService;
use app\mall\model\db\MallNewPeriodicPurchaseOrder;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallOrderDetail;
use app\mall\model\db\MallOrderLog;
use app\mall\model\db\MallOrderRefund;
use app\mall\model\db\MallOrderRefundDetail;
use app\mall\model\db\MallPrepareOrder;
use app\mall\model\service\activity\MallActivityService;
use app\mall\model\service\order_print\PrintHaddleService;
use app\merchant\model\service\card\CardNewService;
use app\merchant\model\service\MerchantMoneyListService;
use app\merchant\model\service\MerchantStoreService;
use app\pay\model\db\PayOrderInfo;
use app\pay\model\service\PayService;

class MallOrderRefundService
{
    /**
     * 通过订单ID获取退款单信息
     * @param  [type]  $order_id 订单ID
     * @param  integer $type     1=申请退款（已付款未发货） 2=申请售后（已发货后续）
     * @return [type]            [description]
     */
    public function getRefundByOrderId($order_id){
        if(empty($order_id)) return [];
        $where = [
            ['order_id', '=', $order_id],
            ['status', '<>', 3],
        ];
        $refund_info = (new MallOrderRefund)->getOne($where, true, 'create_time desc');
        if(empty($refund_info)) return [];
        return $refund_info->toArray();
    }

    /**
     * @param $order_id
     * @return array
     * 获取某个订单的所有退款信息
     */
    public function getAllRefundByOrderId($where)
    {
        $field = 'refund_id,order_id,order_no,create_time,type,status as refund_status,reason,error_reason,audit_time,voucher,refund_money,refund_nums,is_all';
        $refund_info = (new MallOrderRefund)->getSome($where, $field, 'create_time desc');
        if (empty($refund_info)) return [];
        return $refund_info->toArray();
    }
    /**
     * @param $order_id
     * @return array
     * 获取所有退款信息
     */
    public function getAllRefund($where,$field,$page='',$pageSize='')
    {
        $refund_info = (new MallOrderRefund)->getInfo($where, $field,$page,$pageSize);
        return $refund_info;
    }

    /**
     * @param $order_id
     * @return array
     * 获取所有退款信息
     */
    public function getAllRefundByOrderDetail($where,$field,$page='',$pageSize='')
    {
        $refund_info = (new MallOrderRefund)->getInfoByOrderDetail($where, $field,$page,$pageSize);
        return $refund_info;
    }

    public function getSuccessRefund($order_id){
        if(empty($order_id)) return [];
        $where = [
            ['order_id', '=', $order_id],
            ['status', '=', 1],
        ];
        $refund_data = (new MallOrderRefund)->getSome($where, true, 'create_time desc');
        if(empty($refund_data)) return [];
        return $refund_data->toArray();
    }

    public function getSuccessRefundDetail($refund_ids){
        if(empty($refund_ids)) return [];
        $where = [
            ['refund_id', 'in', $refund_ids]
        ];
        $data = (new MallOrderRefundDetail)->getSome($where, 'order_detail_id, refund_nums');
        if(empty($data)) return [];
        $return = [];
        foreach ($data->toArray() as $key => $value) {
            if(isset($return[$value['order_detail_id']])){
                $return[$value['order_detail_id']] += $value['refund_nums'];
            }
            else{
                $return[$value['order_detail_id']] = $value['refund_nums']; 
            }
        }
        return $return;
    }

    public function getRefundDetailList($refund_id){
        if(empty($refund_id)) return [];
        $where = [
            ['r.refund_id', '=', $refund_id]
        ];
        $field = 'r.refund_nums, d.name, d.sku_info, d.image, d.id,d.price';
        $data = (new MallOrderRefundDetail)->getJoinData($where, $field);
        if(empty($data)) return [];
        $return = [];
        foreach ($data->toArray() as $key => $value) {
            $return[] = [
                'detail_id' => $value['id'],
                'image' => replace_file_domain($value['image']),
                'sku_str' => $value['sku_info'],
                'num' => $value['refund_nums'],
                'goods_name' => $value['name'],
                'goods_price' => get_format_number($value['price'])
            ];
        }
        return $return;
    }

    //计算退款
    public function compute($order_id, $is_again, $option, $type = 1){
        $MallOrderService = new MallOrderService;
        $output = [
            'status' => -1,
            'count_down' => 0,//倒计时
            'refund_money' => 0,//退款总金额
            'refund_nums' => 0,//退款总件数
            'is_all' => false,//是否全退
            'money_real' => 0,//剩余退款总金额
            'date' => '',//审核成功或者失败的时间
            'again' => 0,//是否已经全退了
            'type' => $type,
            'order_id' => $order_id,
            'periodic_ids' => [],//待发货的周期购子订单ID
            'merchant_info' => [
                'address' => '',
                'name' => '',
                'phone' => '',
            ],
            'goods_list' => [],
            'refund_info' => [
                'refund_id' => 0,
                'reason' => '',
                'money' => 0,
                'nums' => 0, 
                'date' => '',
                'order_no' => '',
                'imgs' => [],
                'refund_list' => []
            ],
        ];
        $order = $MallOrderService->getOrderDetail($order_id);

        $user_refund = [];//用户选择的退货信息
        if(!empty($option)){
            $user_select = json_decode($option, true);
            if($user_select){
                $user_refund = array_column($user_select, 'num', 'detail_id');
            }
        }

        $periodic_count = 1;
        $already_send = 0;
        $no_get_count = 0;//未收货的周期购数量
        $already_send_freight = 0;//已经发货了的运费
        if($order['order']['goods_activity_type'] == 'periodic'){//周期购订单
            //获取订单已经发货的期数，把订单乘以这个期数
            $periodic_count = (new MallNewPeriodicPurchaseOrder)->getPeriodicOrderNum([['order_id', '=', $order_id],['is_complete','in',[0,1]]]);
            if($periodic_count > 0){
                $output['periodic_ids'] = array_column((new MallNewPeriodicPurchaseOrder)->getPeriodicOrderList([['order_id', '=', $order_id],['is_complete','in',[0,1]]]), 'id');
            }
            $already_send = (new MallNewPeriodicPurchaseOrder)->getPeriodicOrderNum([['order_id', '=', $order_id],['is_complete','in',[3,4]]]);
            $no_get_count = (new MallNewPeriodicPurchaseOrder)->getPeriodicOrderNum([['order_id', '=', $order_id],['is_complete','=',3]]);
            if(!empty($periodic_count + $already_send)){
                $already_send_freight = get_format_number($order['order']['money_freight']*$already_send/($periodic_count + $already_send));
                foreach ($user_refund as $key => $value) {//如果是周期购订单，则传递过来的是周期购的期数
                    $user_refund[$key] = $user_refund[$key]*$order['detail'][0]['num'];
                }
            }
        }

        $success_record = [];
        $success_record_detail = [];
        $success_money = 0;//已经成功退款了多少钱
        $success_nums = 0;//已经成功退款了多少件
        if($is_again == '1'){//说明之前已经申请成功过
            //把之前的申请成功的记录调出，进行整合计算
            $success_record = $this->getSuccessRefund($order_id);
            if($success_record){
                //拿到每个订单单品已退多少件
                $success_record_detail = $this->getSuccessRefundDetail(array_column($success_record, 'refund_id'));
                $success_money = array_sum(array_column($success_record, 'refund_money'));
                $success_nums = array_sum(array_column($success_record, 'refund_nums'));
            }
        }

        
        $order_total_nums = 0;//当前订单总件数
        $user_current_refund_nums = 0;//用户此次退款总件数

        $bargain_price = 0;
        $rest_price = 0;
        $discount_price = 0;//优惠金额
        if($order['order']['goods_activity_type'] == 'prepare'){//预售订单定金不退、优惠金额也不退
            $prepare_order = (new MallPrepareOrder)->getOne([['order_id','=',$order_id]]);
            $bargain_price = $prepare_order['bargain_price'] ?? 0;
            $rest_price = $prepare_order['rest_price'] ?? 0;
            $discount_price = $order['order']['discount_system_coupon'] + $order['order']['discount_merchant_coupon'] + $order['order']['discount_merchant_card'] + $order['order']['discount_system_level'];
        }
        foreach ($order['detail'] as $key => $value) {
            $value['num'] = $value['num']*$periodic_count;
            $success_num = $success_record_detail[$value['id']] ?? 0;
            if($order['order']['goods_activity_type'] == 'prepare'){
                $value['money_total'] = $rest_price;
            }
            if($value['is_gift'] == '0'){
                $temp = [
                    'detail_id' => $value['id'],
                    'image' => replace_file_domain($value['image']),
                    'sku_str' => $value['sku_info'],
                    'price' => $value['price'],
                    'num' => $value['num'] - $success_num,
                    'current_refund' => 0,
                    'periodic' => $periodic_count,
                    'goods_id' => $value['goods_id'],
                    'goods_name' => $value['name']
                ];
                $order_total_nums += $value['num'];
                if(isset($user_refund[$value['id']]) && $user_refund[$value['id']] > 0){
                    if($user_refund[$value['id']] > $temp['num']){
                        throw new \think\Exception("传入的数量不能超过购买数量");
                    }

                    $temp['current_refund'] = $user_refund[$value['id']];
                    $output['refund_money'] += ($user_refund[$value['id']]/$value['num'])*$value['money_total'];
                    $output['refund_nums'] += $user_refund[$value['id']];
                }
                $already_send = $already_send * $value['money_total'];
                $user_current_refund_nums += $temp['current_refund'];
                $output['goods_list'][] = $temp;
            }
        }

        $money_real = get_format_number($order['order']['money_real'] + $discount_price - $success_money - ($type == 1 ? 0 : $order['order']['money_freight']) - $bargain_price - $already_send - $already_send_freight);//售前需要退掉运费，所以这里不扣除运费
        $money_real < 0 && $money_real = 0;
        $output['money_real'] = $money_real;
        if(($output['refund_nums'] == $order_total_nums - $success_nums) || $output['refund_money'] > $money_real){
            $output['refund_money'] = $money_real;
        }
        $refunded_list = $this->getAllRefundByOrderId([['status','in',['0', '1']], ['order_id','=',$order_id]]);
        $refunded_nums = 0;
        foreach ($refunded_list as $refunded) {
            if(in_array($refunded['refund_status'], ['0', '1'])){
                $refunded_nums += $refunded['refund_nums'];
            }
        }
        if(($refunded_nums + $user_current_refund_nums) == $order_total_nums && $no_get_count === 0){
            $output['is_all'] = true;
        }
        $current_refund = $this->getRefundByOrderId($order_id);
        if(!empty($current_refund) && $is_again == '0'){
            $output['status'] = $current_refund['status'];
            if($current_refund['status'] == '0'){
                $refundTime = cfg('mall_order_refund_time')?:7;
                $output['count_down'] = ($current_refund['create_time'] + $refundTime*86400) - time();
            }
            if($current_refund['status'] != '2'){//退款金额不受被拒绝的退款信息的影响
                $output['refund_money'] = $current_refund['refund_money'];
            }
            if($current_refund['status'] == '1'){
                $output['again'] = $current_refund['is_all'] == '1' ? 0 : 1;
            }
            if($order['order']['goods_activity_type'] == 'periodic' && $current_refund['status'] == '1'){
                $output['again'] = 0;
            }
            $output['date'] = $current_refund['audit_time'] > 0 ? date('Y-m-d H:i:s', $current_refund['audit_time']) : '';
            $imgs = $current_refund['voucher'] != '' ? explode(',', $current_refund['voucher']) : [];
            $output['refund_info']['imgs'] = [];
            foreach ($imgs as $refund_img) {
                $output['refund_info']['imgs'][] = replace_file_domain($refund_img);
            }
            $output['refund_info']['refund_id'] = $current_refund['refund_id'];
            $output['refund_info']['reason'] = $current_refund['reason'];
            $output['refund_info']['audit_reason'] = $current_refund['error_reason'];
            $output['refund_info']['money'] = get_format_number($current_refund['refund_money']);
            $output['refund_info']['order_no'] = $current_refund['order_no'];
            $output['refund_info']['nums'] = $current_refund['refund_nums'];
            $output['refund_info']['date'] = date('Y-m-d H:i:s', $current_refund['create_time']);
            $output['refund_info']['refund_list'] = $this->getRefundDetailList($current_refund['refund_id']);
        }

        //商家信息
        $store_info = (new MerchantStoreService)->getStoreInfo($order['order']['store_id']);
        if($store_info){
            $output['merchant_info']['address'] = $store_info['adress'];
            $output['merchant_info']['name'] = $store_info['name'];
            $output['merchant_info']['phone'] = $store_info['phone'];
        }
        $output['refund_money'] = get_format_number($output['refund_money']);


        return $output;
    }

    public function addApplyRefundRcord($compute, $refund_money = 0, $reason = '', $image = ''){
        if($refund_money > 0 && $refund_money > $compute['money_real']){
            throw new \think\Exception("退款金额大于实际剩余金额");         
        }
        if($compute['refund_nums'] == '0'){
            throw new \think\Exception("非法提交");             
        }
        $data = [
            'order_id' => $compute['order_id'],
            'order_no' => (new PayService)->createOrderNo(),
            'create_time' => time(),
            'type' => $compute['type'],
            'status' => 0,
            'reason' => $reason,
            'voucher' => $image,
            'refund_money' => $refund_money > 0 ? $refund_money : $compute['refund_money'],
            'refund_nums' => $compute['refund_nums'],
            'is_all' => $compute['is_all'] ? 1 : 0,
            'periodic_ids' => $compute['periodic_ids'] ? implode(',', $compute['periodic_ids']) : ''
        ];
        if($refund_id = (new MallOrderRefund)->add($data)){
            (new MallOrderService())->checkOrderStatus(60,0,$compute['order_id']);
            (new MallOrderService())->changeOrderStatus($compute['order_id'], 60, '申请售后');
            foreach ($compute['goods_list'] as $key => $value) {
                if($value['current_refund'] > 0){
                    $insert = [
                        'refund_id' => $refund_id,
                        'order_id' => $compute['order_id'],
                        'order_detail_id' => $value['detail_id'],
                        'refund_nums' => $value['current_refund'],
                    ];
                    (new MallOrderRefundDetail)->add($insert);
                }
            }
            if(customization('internal_membership_card')){//淼迈订单申请退款
                (new MallOrderService())->miaomaiMallOrderRefund($refund_id);
            }
           
            return $refund_id;
        }
        else{
            return false;
        }
    }
    
    public function updateRefund($refund_id, $update_data){
        if(empty($refund_id) || empty($update_data)) return false;
        if((new MallOrderRefund)->updateThis(['refund_id'=>$refund_id], $update_data)){
            if($update_data['status']==3){
                $last_status=10;
                $ref=(new MallOrderRefund())->getOne(['refund_id'=>$refund_id])->toArray();
                $log_list=(new MallOrderLog())->getSome(['order_id'=>$ref['order_id']],'status','id desc')->toArray();
                if(!empty($log_list)){
                    foreach ($log_list as $k=>$v){
                        if($v['status']!=60){
                            $last_status=$v['status'];
                            break;
                        }
                    }
                }
                (new MallOrder())->updateThis(['order_id'=>$ref['order_id']], ['status'=>$last_status]);
                (new MallOrderDetail())->updateThis(['order_id'=>$ref['order_id']], ['status'=>$last_status]);
                (new MallOrderService())->mallOrderlog($ref['order_id'], $last_status, '撤销售后申请');
            }
            return true;
        }
        return false;
    }

    /**
     * 审核退款单
     * @param  [type]  $order_id  订单ID
     * @param  [type]  $refund_id 退款数据ID
     * @param  integer $res       审核结果 1=审核成功 2=审核失败
     * @param  integer $reason    审核原因 
     * @return bool             true=操作成功 false=操作失败
     */
    public function auditRefund($order_id, $refund_id, $res = 1, $reason = '',$is_all=0){
        $order = (new MallOrderService)->getOrderDetail($order_id);
        $refund = (new MallOrderRefund)->getOne([['refund_id','=',$refund_id]]);
        $refund_detail = (new MallOrderRefundDetail)->getSome([['refund_id','=',$refund_id]]);
        if(empty($refund) || empty($refund) || empty($refund_detail)){
            return false;
        }
        $refund = $refund->toArray();
        $refund_detail = $refund_detail->toArray();
        if($order['order']['money_refund'] >= $order['order']['money_real'] && $order['order']['money_refund']*1!=0){
            throw new \think\Exception("实际退款金额已经超过实际支付金额");            
        }
        if($refund['status'] != '0'){
            throw new \think\Exception("退款单状态异常");               
        }
        $update = [
            'status' => $res == 1 ? 1 : 2,
            'error_reason' => $reason,
            'audit_time' => time()
        ];
        if($res == 1){
            $return_counpon=false;
            if($is_all){//是全部退款则推优惠券
                $return_counpon=true;
            }
            
            \think\facade\Db::startTrans();
            try{
                $refundRs = $this->refund($order_id, $refund['refund_money'],$return_counpon);
                \think\facade\Db::commit();
            }catch(\Exception $e){
                \think\facade\Db::rollback();
                throw new \think\Exception($e->getMessage(), 1005);
            }
            
            if($refundRs){
                $order_update = ['money_refund'=>$refund['refund_money']+$order['order']['money_refund'], 'refund_status'=>1];
                if($refund['is_all'] == '1'){
                    $order_update['refund_status'] = 2;
                }
                (new MallOrderService)->updateMallOrder(['order_id'=>$order_id], $order_update);
                (new MallOrderRefund)->updateThis(['refund_id'=>$refund_id], $update);

                // 记录退款商品详情
                $refundGoodsList = [];
                //增加库存
                $refund_detail_ids = array_column($refund_detail, 'refund_nums', 'order_detail_id');
                foreach ($order['detail'] as $odetail) {
                    if(isset($refund_detail_ids[$odetail['id']])){
                        (new MallGoodsSkuService)->changeStock($odetail['sku_id'], $odetail['goods_id'], 1, $refund_detail_ids[$odetail['id']]);//(普通库存增加)

                        $odetail['goods_name'] = $odetail['name'];
                        $odetail['num'] = $refund_detail_ids[$odetail['id']];
                        $refundGoodsList[] = $odetail;
                    }
                    if ($odetail['activity_type'] == 'limited') {//限时优惠增加库存
                        (new MallActivityService)->refundOrderLimitAct($odetail['activity_id'],$odetail['sku_id'],$refund_detail_ids[$odetail['id']]);
                    }
                }

                //如果存在结算，则扣除商家余额
                $thisOrder = $order['order'];
                $moneyListMod = new MerchantMoneyListService();
                $records = $moneyListMod->getAll(['order_id' => $thisOrder['order_no'], 'type' => 'mall']);
                $totalGet = $totalRefund = 0;
                $totalSystemTake = 0;
                $reduceSystemTake = 0;
                $percent = 0;
                foreach ($records as $v) {
                    if ($v['income'] == 1) {
                        $totalGet += $v['money'];
                        $totalSystemTake += $v['system_take'];
                        $percent = $v['percent'];
                    } else {
                        $reduceSystemTake = bcadd((string)$reduceSystemTake, (string)$v['system_take'],2);
                        $totalRefund += $v['money'];
                    }
                }
                if ($refund['is_all'] == '1') {
                    $reduceMoney = get_format_number($totalGet - $totalRefund);
                    $reduceSystemTake = bcsub((string)$totalSystemTake, (string)$reduceSystemTake, 2);
                } else {
                    $reduceMoney = get_format_number($thisOrder['money_real'] > 0 ? ($refund['refund_money'] / $thisOrder['money_real']) * $totalGet : 0);
                    $reduceSystemTake = bcmul((string)$totalSystemTake, getFormatNumber($reduceMoney / $totalGet), 2);
                }

                $reduceMoney > 0 && $moneyListMod->useMoney(
                    $thisOrder['mer_id'], 
                    $reduceMoney, 
                    'mall', 
                    cfg('mall_alias_name_new') . '部分商品退货,减少余额,订单编号' . $thisOrder['order_no'], 
                    $thisOrder['order_no'],
                    $percent,
                    $reduceSystemTake,
                    [],
                    $thisOrder['store_id'],
                    $refundRs['raw_refund_data']??[]
                );

                
                // 退款成功后打印小票
                $param = [
                    'order_id' => $order_id,
                    'print_type' => 'refund',
                    'goods_list' => $refundGoodsList
                ];
                $res = (new PrintHaddleService)->printOrder($param, 3);
                return true;
            }
        }
        elseif($res == 2){
            (new MallOrderRefund)->updateThis(['refund_id'=>$refund_id], $update);
            return true;
        }
        return false;
    }
    /**
     * 审核退款单-手动
     * @param  [type]  $order_id  订单ID
     * @param  [type]  $refund_id 退款数据ID
     * @param  [type]  $price_back 是否退在线支付的金额（0：否）
     * @param  integer $res       审核结果 1=审核成功 2=审核失败
     * @param  integer $reason    审核原因 
     * @return bool             true=操作成功 false=操作失败
     */
    public function auditRefundManual($order_id, $refund_id, $res = 1, $reason = '',$is_all=0,$price_back=0){
        $order = (new MallOrderService)->getOrderDetail($order_id);
        $refund = $refund_detail = [];
        if($refund_id){
            $refund = (new MallOrderRefund)->getOne([['refund_id','=',$refund_id]]);
            $refund_detail = (new MallOrderRefundDetail)->getSome([['refund_id','=',$refund_id]]);
            if(empty($refund) || empty($refund) || empty($refund_detail)){
                return false;
            }
            $refund = $refund->toArray();
            $refund_detail = $refund_detail->toArray();
            if($order['order']['money_refund'] >= $order['order']['money_real'] && $order['order']['money_refund']*1!=0){
                throw new \think\Exception("实际退款金额已经超过实际支付金额");
            }
            if($refund['status'] != '0'){
                throw new \think\Exception("退款单状态异常");
            }
        }
        $update = [
            'status' => $res == 1 ? 1 : 2,
            'error_reason' => $reason,
            'audit_time' => time()
        ];
        if($res == 1){
            $return_counpon=false;
            if($is_all){//是全部退款则推优惠券
                $return_counpon=true;
            }
            $refundMoney = $refund['refund_money']??$order['order']['money_real'];
            $refundRs = $this->refundManual($order_id, $refundMoney,$return_counpon,$price_back);
            if($refundRs){
                $order_update = ['money_refund'=>$refundMoney+$order['order']['money_refund'], 'refund_status'=>1];
                if(!$refund_id || $refund['is_all'] == '1'){
                    $order_update['refund_status'] = 2;
                }
                (new MallOrderService)->updateMallOrder(['order_id'=>$order_id], $order_update);
                if($refund_id){
                    (new MallOrderRefund)->updateThis(['refund_id'=>$refund_id], $update);
                }

                // 记录退款商品详情
                $refundGoodsList = [];
                if($refund_detail){
                    //增加库存
                    $refund_detail_ids = array_column($refund_detail, 'refund_nums', 'order_detail_id');
                    foreach ($order['detail'] as $odetail) {
                        if(isset($refund_detail_ids[$odetail['id']])){
                            (new MallGoodsSkuService)->changeStock($odetail['sku_id'], $odetail['goods_id'], 1, $refund_detail_ids[$odetail['id']]);//(普通库存增加)

                            $odetail['goods_name'] = $odetail['name'];
                            $odetail['num'] = $refund_detail_ids[$odetail['id']];
                            $refundGoodsList[] = $odetail;
                        }
                        if ($odetail['activity_type'] == 'limited') {//限时优惠增加库存
                            (new MallActivityService)->refundOrderLimitAct($odetail['activity_id'],$odetail['sku_id'],$refund_detail_ids[$odetail['id']]);
                        }
                    }
                }

                //如果存在结算，则扣除商家余额
                $thisOrder = $order['order'];
                $moneyListMod = new MerchantMoneyListService();
                $records = $moneyListMod->getAll(['order_id' => $thisOrder['order_no'], 'type' => 'mall']);
                $totalGet = $totalRefund = 0;
                foreach ($records as $v) {
                    if ($v['income'] == 1) {
                        $totalGet += $v['money'];
                    } else {
                        $totalRefund += $v['money'];
                    }
                }
                if (!$refund_id || $refund['is_all'] == '1') {
                    $reduceMoney = get_format_number($totalGet - $totalRefund);
                } else {
                    $reduceMoney = get_format_number($thisOrder['money_real'] > 0 ? ($refund['refund_money'] / $thisOrder['money_real']) * $totalGet : 0);
                }
                $reduceMoney > 0 && $moneyListMod->useMoney(
                    $thisOrder['mer_id'], 
                    $reduceMoney, 
                    'mall', 
                    cfg('mall_alias_name_new') . '部分商品退货,减少余额,订单编号' . $thisOrder['order_no'], 
                    $thisOrder['order_no'],
                    0,
                    0,
                    [],
                    $thisOrder['store_id'],
                    $refundRs['raw_refund_data']??[]
                );

                if($refundGoodsList){
                    // 退款成功后打印小票
                    $param = [
                        'order_id' => $order_id,
                        'print_type' => 'refund',
                        'goods_list' => $refundGoodsList
                    ];
                    $res = (new PrintHaddleService)->printOrder($param, 3);
                }
                return true;
            }
        }
        elseif($res == 2){
            (new MallOrderRefund)->updateThis(['refund_id'=>$refund_id], $update);
            return true;
        }
        return false;
    }

    /**
     * 退款
     * @param  [type] $order_id     订单ID
     * @param  [type] $refund_money 需要退款的金额 ,不传或传''时为全部退款
     * @param  [type] $return_coupon 是否退还优惠券(取消订单时退还)
     * @param  [is_refund] $is_refund 是否退款 true=是，false=不退款
     * @return [type]               [description]
     */
    public function refund($order_id, $refund_money = '', $return_coupon = false,$is_refund=true){
        $order = (new MallOrderService)->getOrderDetail($order_id);
        $already_refund_money = $order['order']['money_refund'];//已退多少钱
        if($refund_money === ''){
            $refund_money = $order['order']['money_real'];
            if($order['order']['goods_activity_type'] == 'prepare'){//预售订单不退定金
                $pre_order_info = (new MallActivityService)->getPrepareOrderDetail($order_id);
                $refund_money = get_format_number($refund_money - $pre_order_info['bargain_price']);
                if($refund_money == '0'){
                    return true;
                }
            }
        }
        $alias_name = cfg("mall_alias_name");
        try{
            if($order['order']['pay_time'] == '0'||!$is_refund){
                return true;
            }

            //退款顺序  一卡通-商家优惠券-商家会员卡余额-商家会员卡赠送余额-平台优惠券-平台积分-平台余额-在线支付
            // 退回一卡通支付的金额
            $card_pay_info = json_decode($order['order']['internal_card_pay'], true);
            $time = time();
            if (!empty($card_pay_info) and $order['order']['pay_time'] != 0){
                foreach($card_pay_info as $card_id => $card_pay_money){
                    $card = InternalMembershipCard::where('card_id', $card_id)->find();
                    $card_balance = round($card['balance_money'] * 100) / 100;

                    $save_data['balance_money'] = round(($card_balance + $card_pay_money) * 100) / 100;
                    $save_data['last_time'] = $time;
                    InternalMembershipCard::where('card_id', $card_id)->update($save_data);
                    $condition_card_log['card_id'] = $card_id;
                    $condition_card_log['operation_type'] = 3; //交易退款
                    $condition_card_log['before_balance'] = $card_balance;
                    $condition_card_log['after_balance'] = $save_data['balance_money'];
                    $condition_card_log['money'] = $card_pay_money;
                    $condition_card_log['business_type'] = 'mall';
                    $condition_card_log['order_id'] = $order['order']['order_no'];
                    $condition_card_log['describe'] = '商城商品退款, 退回余额'. $card_pay_money .'元, 订单编号'.$order['order']['order_no'];
                    $condition_card_log['mer_id'] = $order['order']['mer_id'];
                    $condition_card_log['store_id'] = $order['order']['store_id'];
                    $condition_card_log['create_time'] = $time;
                    (new InternalMembershipCardLog())->insert($condition_card_log);
				    $refund_money = get_format_number($refund_money - $card_pay_money);
                    (new InternalCardService())->sendWxMsg($card_id, '+'.getFormatNumber($condition_card_log['money']), '商城商品退款');
                }
            }
            if($refund_money <= 0) return true;

            //取消订单 退优惠券
            if($order['order']['merchant_coupon_id'] > 0 && $return_coupon){//取消订单才退优惠券，因为申请退款支持部分退货，所以优惠券不好退，就不退了
                (new MerchantCouponService)->refundReturnCoupon($order['order']['merchant_coupon_id']);
            }
            //取消订单 退优惠券
            if($order['order']['system_coupon_id'] > 0 && $return_coupon){//取消订单才退优惠券，因为申请退款支持部分退货，所以优惠券不好退，就不退了
                // $is_used = (new SystemCouponHadpull())->where(['id' => $order['order']['system_coupon_id']])->value('is_use');
                // if($is_used==0){
                //     (new SystemCouponService)->refundReturnCoupon($order['order']['system_coupon_id']);
                // }
                (new SystemCouponService)->refundReturnCoupon($order['order']['system_coupon_id']);
            }
            
            //退商家会员卡余额
            $refund_merchant_card = $order['order']['money_merchant_balance'] - $already_refund_money;
            $_money1 = 0;
            if($refund_merchant_card > 0){
                $_money1 = $refund_money >= $refund_merchant_card ? $refund_merchant_card : $refund_money;
                $result = (new CardNewService())->addUserMoney($order['order']['mer_id'], $order['order']['uid'],$_money1, 0, 0,'',$alias_name . '订单退款,增加余额,订单编号' . $order['order']['order_no']);
                if(!$result){
                    throw new \think\Exception('退商家会员卡余额失败'); 
                }
                $refund_money -= $_money1;
            }
            if($refund_money <= 0) return true;

            //商家会员卡赠送余额
            $refund_merchant_card_give = ($order['order']['money_merchant_give_balance'] + $order['order']['money_merchant_balance']) - ($already_refund_money + $_money1);
            $_money2 = 0;
            if($refund_merchant_card_give > 0){
                $_money2 = $refund_money >= $refund_merchant_card_give ? $refund_merchant_card_give : $refund_money;
                $result = (new CardNewService())->addUserMoney($order['order']['mer_id'], $order['order']['uid'],0, $_money2, 0,'',$alias_name . '订单退款,增加余额,订单编号' . $order['order']['order_no']);
                if(!$result){
                    throw new \think\Exception('退商家会员卡赠送余额失败'); 
                }
                $refund_money -= $_money2;
            }
            if($refund_money <= 0) return true;

            //退积分
            $refund_score = ($order['order']['money_score'] + $order['order']['money_merchant_give_balance'] + $order['order']['money_merchant_balance']) - ($already_refund_money + $_money1 + $_money2);
            $_money3 = 0;
            if($refund_score > 0){
                $score_refund = $refund_money >= $refund_score ? floor($order['order']['score_use']*$refund_score/$order['order']['money_score']) : floor($order['order']['score_use']*$refund_money/$order['order']['money_score']);
                $_money3 = $refund_money >= $refund_score ? $refund_score : $refund_money;

                $result = (new UserService())->addScore($order['order']['uid'], $score_refund, $alias_name . '订单退款,增加积分,订单编号' . $order['order']['order_no']);
                if($result['error_code']){
                    throw new \think\Exception($result['msg']);  
                }
                $refund_money -= $_money3;
            }
            if($refund_money <= 0) return true;

            //平台余额
            $refund_system_balance = ($order['order']['money_system_balance'] + $order['order']['money_score'] + $order['order']['money_merchant_give_balance'] + $order['order']['money_merchant_balance']) - ($already_refund_money + $_money1 + $_money2 + $_money3);
            $_money4 = 0;
            if($refund_system_balance > 0){
                $_money4 = $refund_money >= $refund_system_balance ? $refund_system_balance : $refund_money;
                $result = (new UserService())->addMoney($order['order']['uid'], $_money4, $alias_name . '订单退款,增加余额,订单编号' . $order['order']['order_no']);
                if($result['error_code']){
                    throw new \think\Exception($result['msg']); 
                }
                $refund_money -= $_money4;
            }
            if($refund_money <= 0) return true;

            //员工卡支付退款
            $_money5 = 0;
            $_money6 = 0;
            if($order['order']['employee_balance_pay'] || $order['order'['employee_score_pay']]){
                $employee_balance_pay = $order['order']['employee_balance_pay'];
                $employee_score_pay = $order['order']['employee_score_pay'];
                $mall_alias_name = cfg('mall_alias_name');
                $time = time();
                $employeeCardUserModel = new EmployeeCardUser();
                $employeeCardLogModel = new EmployeeCardLog();
                $cardUser = $employeeCardUserModel->where('user_id', $order['order']['employee_card_user_id'])->find();
                if($cardUser){
                    if($employee_balance_pay > 0){
                        $_money5 = $refund_money >= $employee_balance_pay ? $employee_balance_pay : $refund_money;
                        $employeeCardUserModel->where('user_id', $order['order']['employee_card_user_id'])->update([
                            'card_money'    =>  $cardUser->card_money + $_money5,
                            'last_time'     =>  time()
                        ]);
                        $employeeCardLogModel->insert([
                            'card_id'		=>	$cardUser->card_id,
                            'user_id'		=>	$cardUser->user_id,
                            'mer_id'		=>	$cardUser->mer_id,
                            'uid'			=>	$cardUser->uid,
                            'num'			=>	$_money5,
                            'type'			=>	'money',
                            'change_type'	=>	'increase',
                            'description'	=>	 $mall_alias_name . '商品退款，余额增加'. $_money5 . '元',
                            'user_desc'		=>	 $mall_alias_name . '商品退款，余额增加'. $_money5 . '元',
                            'operate_type'	=>	'user',
                            'operate_id'	=>	$cardUser->uid,
                            'add_time'		=>	$time,
                            'log_type'		=>	11,
                            'pay_type'		=>	'money'
                        ]);
                        $refund_money -= $_money5;
                    }
                    if($employee_score_pay > 0){
                        $_money6 = $refund_money >= $employee_score_pay ? $employee_score_pay : $refund_money;
                        $employeeCardUserModel->where('user_id', $order['order']['employee_card_user_id'])->update([
                            'card_score'    =>  $cardUser->card_score + $_money6,
                            'last_time'     =>  time()
                        ]);
                        $employeeCardLogModel->insert([
                            'card_id'		=>	$cardUser->card_id,
                            'user_id'		=>	$cardUser->user_id,
                            'mer_id'		=>	$cardUser->mer_id,
                            'uid'			=>	$cardUser->uid,
                            'num'			=>	$_money6,
                            'type'			=>	'score',
                            'change_type'	=>	'increase',
                            'description'	=>	$mall_alias_name . '商品退款，增加'. $_money6 . '积分',
                            'user_desc'		=>	$mall_alias_name . '商品退款，增加'. $_money6 . '积分',
                            'operate_type'	=>	'user',
                            'operate_id'	=>	$cardUser->uid,
                            'add_time'		=>	$time,
                            'log_type'		=>	11,
                            'pay_type'		=>	'score'
                        ]);
                        $refund_money -= $_money6;
                    }
                }
            }
            if($refund_money <= 0) return true;

            //在线支付退款

            $add_arr = [$order['order']['money_online_pay'],$order['order']['money_system_balance'],$order['order']['money_score'], $order['order']['money_merchant_give_balance'],$order['order']['money_merchant_balance']];
            $add_total = 0;
            foreach ($add_arr as $a_item){
                $add_total = bcadd($add_total,$a_item*100);
            }
            $sub_arr = [$already_refund_money,$_money1,$_money2,$_money3,$_money4];
            $sub_total = 0;
            foreach ($sub_arr as $s_item){
                $sub_total = bcadd($sub_total,$s_item*100);
            }
            $refund_online_pay = bcsub($add_total,$sub_total)/100;
            
            if($refund_online_pay==0 && !empty($order['order']['online_pay_type']) && !empty($order['order']['source'])){
                $refund_online_pay=$order['order']['money_real'];
            }
            $_money5 = 0;
            if($refund_online_pay > 0){
                $PayService = new PayService;
                $_money5 = $refund_money >= $refund_online_pay ? $refund_online_pay : $refund_money;
                fdump_api([
                    'order' => $order['order'],
                    'refund_money' => $refund_money,
                    'msg' => '退款参数'
                ], 'mall_order/refund_online_pay', true);
                // if($order['order']['pre_pay_orderno']){
                //     //预定金
                //     $pre_order = $PayService->getPayOrderInfo($order['order']['pre_pay_orderno']);
                //     if($pre_order['paid'] == '1' && $pre_order['paid_money'] > 0){
                //         $pre_money = ($pre_order['paid_money'] - $pre_order['refund_money'])/100;
                //         if($pre_money > 0){
                //             $pre_refund_money = $pre_money >= $_money5 ? $_money5 : $pre_money;
                //             $pre_refund = (new PayService)->refund($order['order']['pre_pay_orderno'], $pre_refund_money);
                //             $_money5 = $_money5 - $pre_refund_money;//这一步没必要啊
                //         }                        
                //     }
                // }  //先注释，预定金是不退的呀
                if($order['order']['pay_orderno']){
                    //尾款(普通订单只有尾款，没有预定金)
                    $end_order = $PayService->getPayOrderInfo($order['order']['pay_orderno']);
                    if($end_order['paid'] == '1' && $end_order['paid_money'] > 0){
                        $end_money = ($end_order['paid_money'] - $end_order['refund_money'])/100;
                        if($end_money > 0){
                            $end_refund_money = $end_money >= $_money5 ? $_money5 : $end_money;
                            fdump_api([
                                'end_refund_money' => $end_refund_money,
                                'msg' => '退款参数2'
                            ], 'mall_order/refund_online_pay', true);
                            $end_refund = (new PayService)->refund($order['order']['pay_orderno'], $end_refund_money);
                            // $_money5 = $_money5 - $end_refund_money;//这一步没必要呀
                        }                        
                    }
                }
//                $refund_money -= $_money5;
                $refund_money = bcsub($refund_money,$_money5,2);//修改为高精度计算
            }
            fdump(['order_id'=>$order_id,
                'refund_money'=>$refund_money,
                'return_coupon'=>$return_coupon,
                'refund_merchant_card'=>$refund_merchant_card,
                'refund_merchant_card_give'=>$refund_merchant_card_give,
                'refund_score'=>$refund_score,
                'refund_system_balance'=>$refund_system_balance,
                'refund_online_pay'=>$refund_online_pay,
                'order'=>$order],
                "refund99887777771111111");

            if($refund_money <= 0){
                return $end_refund??true;
            }
            fdump_api(['order_id'=>$order_id,'refund_money'=>$refund_money,'return_coupon'=>$return_coupon, 'refund_merchant_card'=>$refund_merchant_card,'refund_merchant_card_give'=>$refund_merchant_card_give, 'refund_score'=>$refund_score,'refund_system_balance'=>$refund_system_balance,'refund_online_pay'=>$refund_online_pay,'order'=>$order],'mall_refund_error',1);
            //throw new \think\Exception('退款金额计算异常，退款溢出了，找技术看看'); 
        }
        catch(\Exception $e){

            throw new \think\Exception($e->getMessage(), 1005);
        }
    }
    /**
     * 退款-手动
     * @param  [type] $order_id     订单ID
     * @param  [type] $refund_money 需要退款的金额 ,不传或传''时为全部退款
     * @param  [type] $return_coupon 是否退还优惠券(取消订单时退还)
     * @return [type]               [description]
     */
    public function refundManual($order_id, $refund_money = '', $return_coupon = false,$price_back=0){
        $order = (new MallOrderService)->getOrderDetail($order_id);
        $already_refund_money = $order['order']['money_refund'];//已退多少钱
        if($refund_money === ''){
            $refund_money = $order['order']['money_real'];
            if($order['order']['goods_activity_type'] == 'prepare'){//预售订单不退定金
                $pre_order_info = (new MallActivityService)->getPrepareOrderDetail($order_id);
                $refund_money = get_format_number($refund_money - $pre_order_info['bargain_price']);
                if($refund_money == '0'){
                    return true;
                }
            }
        }
        $alias_name = cfg("mall_alias_name");

        try{
            //退款顺序  商家优惠券-商家会员卡余额-商家会员卡赠送余额-平台优惠券-平台积分-平台余额-在线支付
            //取消订单 退优惠券
            if($order['order']['merchant_coupon_id'] > 0 && $return_coupon){//取消订单才退优惠券，因为申请退款支持部分退货，所以优惠券不好退，就不退了
                (new MerchantCouponService)->refundReturnCoupon($order['order']['merchant_coupon_id']);
            }
            //取消订单 退优惠券
            if($order['order']['system_coupon_id'] > 0 && $return_coupon){//取消订单才退优惠券，因为申请退款支持部分退货，所以优惠券不好退，就不退了
                (new SystemCouponService)->refundReturnCoupon($order['order']['system_coupon_id']);
            }
            if($order['order']['pay_time'] == '0'){
                return true;
            }

            //退商家会员卡余额
            $refund_merchant_card = $order['order']['money_merchant_balance'] - $already_refund_money;
            $_money1 = 0;
            if($refund_merchant_card > 0){
                $_money1 = $refund_money >= $refund_merchant_card ? $refund_merchant_card : $refund_money;
                $result = (new CardNewService())->addUserMoney($order['order']['mer_id'], $order['order']['uid'],$_money1, 0, 0,'',$alias_name . '订单退款,增加余额,订单编号' . $order['order']['order_no']);
                if($result['error_code']){
                    throw new \think\Exception($result['msg']); 
                }
                $refund_money -= $_money1;
            }
            if($refund_money <= 0) return true;

            //商家会员卡赠送余额
            $refund_merchant_card_give = ($order['order']['money_merchant_give_balance'] + $order['order']['money_merchant_balance']) - ($already_refund_money + $_money1);
            $_money2 = 0;
            if($refund_merchant_card_give > 0){
                $_money2 = $refund_money >= $refund_merchant_card_give ? $refund_merchant_card_give : $refund_money;
                $result = (new CardNewService())->addUserMoney($order['order']['mer_id'], $order['order']['uid'],0, $_money2, 0,'',$alias_name . '订单退款,增加余额,订单编号' . $order['order']['order_no']);
                if($result['error_code']){
                    throw new \think\Exception($result['msg']); 
                }
                $refund_money -= $_money2;
            }
            if($refund_money <= 0) return true;

            //退积分
            $refund_score = ($order['order']['money_score'] + $order['order']['money_merchant_give_balance'] + $order['order']['money_merchant_balance']) - ($already_refund_money + $_money1 + $_money2);
            $_money3 = 0;
            if($refund_score > 0){
                $score_refund = $refund_money >= $refund_score ? floor($order['order']['score_use']*$refund_score/$order['order']['money_score']) : floor($order['order']['score_use']*$refund_money/$order['order']['money_score']);
                $_money3 = $refund_money >= $refund_score ? $refund_score : $refund_money;

                $result = (new UserService())->addScore($order['order']['uid'], $score_refund, $alias_name . '订单退款,增加积分,订单编号' . $order['order']['order_no']);
                if($result['error_code']){
                    throw new \think\Exception($result['msg']);  
                }
                $refund_money -= $_money3;
            }
            if($refund_money <= 0) return true;

            //平台余额
            $refund_system_balance = ($order['order']['money_system_balance'] + $order['order']['money_score'] + $order['order']['money_merchant_give_balance'] + $order['order']['money_merchant_balance']) - ($already_refund_money + $_money1 + $_money2 + $_money3);
            $_money4 = 0;
            if($refund_system_balance > 0){
                $_money4 = $refund_money >= $refund_system_balance ? $refund_system_balance : $refund_money;
                $result = (new UserService())->addMoney($order['order']['uid'], $_money4, $alias_name . '订单退款,增加余额,订单编号' . $order['order']['order_no']);
                if($result['error_code']){
                    throw new \think\Exception($result['msg']); 
                }
                $refund_money -= $_money4;
            }
            if($refund_money <= 0) return true;

            //在线支付退款
            $refund_online_pay = ($order['order']['money_online_pay'] + $order['order']['money_system_balance'] + $order['order']['money_score'] + $order['order']['money_merchant_give_balance'] + $order['order']['money_merchant_balance']) - ($already_refund_money + $_money1 + $_money2 + $_money3 + $_money4);
            if($refund_online_pay==0 && !empty($order['order']['online_pay_type']) && !empty($order['order']['source'])){
                $refund_online_pay=$order['order']['money_real'];
            }
            $_money5 = 0;
            if($refund_online_pay > 0){
                $PayService = new PayService;
                $_money5 = $refund_money >= $refund_online_pay ? $refund_online_pay : $refund_money;
                // if($order['order']['pre_pay_orderno']){
                //     //预定金
                //     $pre_order = $PayService->getPayOrderInfo($order['order']['pre_pay_orderno']);
                //     if($pre_order['paid'] == '1' && $pre_order['paid_money'] > 0){
                //         $pre_money = ($pre_order['paid_money'] - $pre_order['refund_money'])/100;
                //         if($pre_money > 0){
                //             $pre_refund_money = $pre_money >= $_money5 ? $_money5 : $pre_money;
                //             $pre_refund = (new PayService)->refund($order['order']['pre_pay_orderno'], $pre_refund_money);
                //             $_money5 = $_money5 - $pre_refund_money;//这一步没必要啊
                //         }                        
                //     }
                // }  //先注释，预定金是不退的呀
                if($order['order']['pay_orderno']){
                    if($price_back){
                        $pay_db = new PayOrderInfo;
                        $order = $pay_db->getByOrderNo($order['order']['pay_orderno']);
                        $save_data = [
                            'refund' => $order['refund_money'] + $refund_money > $order['paid_money'] ? 2 : 1,
                            'refund_money' => $order['refund_money'] + $refund_money,
                            'refund_nums' => $order['refund_nums']+1,
                            'refund_last_time' => date('Y-m-d H:i:s')
                        ];
                        if(!$pay_db->updateById($order['id'], $save_data)){
                            fdump_api(['param' => $_POST,'order_no' => $order['order']['pay_orderno'],'order' => $order,'save_data' => $save_data,'msg' => '更新退款信息失败'],'errPay/payServiceRefundLog');
                            throw new \think\Exception("更新退款信息失败！");
                        } 
                    }else{
                        //尾款(普通订单只有尾款，没有预定金)
                        $end_order = $PayService->getPayOrderInfo($order['order']['pay_orderno']);
                        if($end_order['paid'] == '1' && $end_order['paid_money'] > 0){
                            $end_money = ($end_order['paid_money'] - $end_order['refund_money'])/100;
                            if($end_money > 0){
                                $end_refund_money = $end_money >= $_money5 ? $_money5 : $end_money;
                                $end_refund = (new PayService)->refund($order['order']['pay_orderno'], $end_refund_money);
                                // $_money5 = $_money5 - $end_refund_money;//这一步没必要呀
                            }
                        }
                    }
                }
//                $refund_money -= $_money5;
                $refund_money = bcsub($refund_money,$_money5,2);//修改为高精度计算
            }
            fdump(['order_id'=>$order_id,'refund_money'=>$refund_money,
                'return_coupon'=>$return_coupon,
                'refund_merchant_card'=>$refund_merchant_card,'refund_merchant_card_give'=>$refund_merchant_card_give,
                'refund_score'=>$refund_score,'refund_system_balance'=>$refund_system_balance,'refund_online_pay'=>$refund_online_pay,'order'=>$order],"refund99887777771111111");
            if($refund_money <= 0){
                return $end_refund??true;
            }
            throw new \think\Exception('退款金额计算异常，退款溢出了，找技术看看'); 
        }
        catch(\Exception $e){

            throw new \think\Exception($e->getMessage(), 1005);
        }
    }
}