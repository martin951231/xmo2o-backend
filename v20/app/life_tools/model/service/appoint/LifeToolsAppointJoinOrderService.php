<?php


namespace app\life_tools\model\service\appoint;
use app\common\model\db\Area;
use app\common\model\db\Merchant;
use app\common\model\db\SystemOrder;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\order\SystemOrderService;
use app\common\model\service\UserService;
use app\life_tools\model\db\LifeToolsAppoint;
use app\life_tools\model\db\LifeToolsAppointJoinOrder;
use app\life_tools\model\db\LifeToolsAppointSeatDetail;
use app\life_tools\model\db\LifeToolsAppointSaleDay;
use app\merchant\model\service\card\CardNewService;
use app\pay\model\service\PayService;
use think\facade\Db;
use app\life_tools\model\db\LifeToolsAppointSku;
class LifeToolsAppointJoinOrderService
{
    /**
     * 获取支付订单详情
     */
    public function getOrderPayInfo($orderId)
    {
        if (empty($orderId)) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }
        $lifeToolsAppointJoinOrder = new LifeToolsAppointJoinOrder();
        $nowOrder = $lifeToolsAppointJoinOrder->orderDetail(['j.pigcms_id'=>$orderId],'j.uid,j.appoint_id,j.status,j.paid,j.price,j.coupon_price,j.coupon_id,c.city_id,c.title,j.mer_id,j.real_orderid');
        $timeRemain = 900;
        if (empty($nowOrder)) {
            throw new \think\Exception(L_("当前订单不存在！"), 1003);
        }
        if ($nowOrder['paid']) {
            throw new \think\Exception(L_("订单已支付"), 1003);
        }
        if ($nowOrder['status'] == 1) {
            throw new \think\Exception(L_("您的订单已报名成功，不用付款了！"), 1003);
        }
        if ($nowOrder['status'] == 2) {
            throw new \think\Exception(L_("您的订单已报名失败，不能付款了！"), 1003);
        }

        $appoint = (new LifeToolsAppoint())->where('appoint_id', $nowOrder['appoint_id'])->find();
        if(!$appoint){
            throw new \think\Exception(L_("当前活动已失效！"), 1003);
        }
        if($appoint['is_sku'] == 0){
            if($appoint['limit_type'] == 1){
                $nowCount = $lifeToolsAppointJoinOrder->where(['appoint_id'=>$nowOrder['appoint_id'],'paid'=>1])->count();
                if($nowCount >= $appoint['limit_num']){
                    throw new \think\Exception(L_("库存不足！"), 1003);
                }
            }
            $nowUserCount = $lifeToolsAppointJoinOrder->where(['appoint_id'=>$nowOrder['appoint_id'],'uid'=>$nowOrder['uid'],'paid'=>1])->count();
            if($nowUserCount >= $appoint['limit']){
                throw new \think\Exception(L_("每个账号只能报名{$appoint['limit']}次!"), 1003);
            }
        }

        // 店铺信息
        if(empty($nowOrder['coupon_id'])){
            $payInfact = $nowOrder['price'];
        }else{
            $payInfact = $nowOrder['price'] - $nowOrder['coupon_price'];
        }

        $returnArr['order_money'] = get_format_number($payInfact);
        $returnArr['paid'] = $nowOrder['paid'];
        $returnArr['order_no'] =  $orderId;
        $returnArr['store_id'] = 0;
        $returnArr['city_id'] = $nowOrder['city_id'];
        $returnArr['mer_id'] = $nowOrder['mer_id'];
        $returnArr['is_cancel'] = 0;
        $returnArr['time_remaining'] = $timeRemain;//秒
        $returnArr['uid'] = $nowOrder['uid'];
        $returnArr['title'] = $nowOrder['title'];
        $returnArr['business_order_sn'] = $nowOrder['real_orderid'];
        return $returnArr;
    }

    /**
     * 获取支付结果页地址
     * @param  [type]  $combine_id  [description]
     * @param  integer $is_cancel 1=已取消  0=未取消
     * @return string             返回跳转链接
     */

    public function getPayResultUrl($orderId,$cancel = 0)
    {
        if(!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        $nowOrder =  (new LifeToolsAppointJoinOrder())->getOneDetail(['pigcms_id'=>$orderId],true);
        if(!$nowOrder) {
            throw new \think\Exception(L_("当前订单不存在！"), 1003);
        }

       /* if($cancel== 1){*/
            // 订单详情
            $url = get_base_url('pages/lifeTools/appointment/mineAppoint/detail?order_id='.$orderId);
            return $url;
        /*}else{
            // 支付成功页
            $url = get_base_url('pages/lifeTools/pocket/message');
            return ['redirect_url' => $url, 'direct' => 1];
        }*/
    }

    /**
     * 支付成功更新订单状态
     */
    public function afterPay($orderId, $payParam = []){
        $order = (new LifeToolsAppointJoinOrder())->getOneDetail(['pigcms_id'=>$orderId],true);
        fdump($payParam,'afterPay1111111',1);
        if(empty($order)) throw new \think\Exception("没有找到订单");

        if ($order['paid'] == 1) {
            throw new \think\Exception(L_("订单已支付"), 1003);
        }

        $UserService = new UserService();
        $nowUser = $UserService->getUser($order['uid']);
        if(empty($nowUser)){
            throw new \think\Exception(L_("购买用户不存在"), 1003);
        }

        $paidTime = isset($payParam['pay_time']) ? $payParam['pay_time'] : '';
        $paidMoney = isset($payParam['paid_money']) ? $payParam['paid_money'] : '';
        $paidType = isset($payParam['paid_type']) ? $payParam['paid_type'] : '';
        $currentScoreUse = isset($payParam['current_score_use']) ? $payParam['current_score_use'] : '';
        $currentScoreDeducte = isset($payParam['current_score_deducte']) ? $payParam['current_score_deducte'] : '';
        $currentSystemBalance = isset($payParam['current_system_balance']) ? $payParam['current_system_balance'] : '';
        $currentMerchantBalance = isset($payParam['current_merchant_balance']) ? $payParam['current_merchant_balance'] : '';        
        $currentMerchantGiveBalance = isset($payParam['current_merchant_give_balance']) ? $payParam['current_merchant_give_balance'] : '';


        if ($order['paid'] == 1) {
            // 该订单已付款
            return false;
        }

        if ($order['status'] == 1) {//已报名
            return false;
        }

        //判断帐户余额
        if ($currentSystemBalance > 0) {
            // 您的帐户余额不够此次支付
            if ($nowUser['now_money'] < $currentSystemBalance) {
                return false;
            }
        }

        // 平台积分
        if ($currentScoreUse > 0) {
            //判断积分数量是否正确
            if ($nowUser['score_count'] < $currentScoreUse) {
                return false;
            }
        }

        //如果使用了平台优惠券
        if ($order['coupon_id']) {
            try {
                $result = (new SystemCouponService())->useCoupon($order['coupon_id'], $order['pigcms_id'], 'life_tools_appoint_join', 0, $nowUser['uid']);
            } catch (\Exception $e) {
                return false;
            }
        }

        //如果用户使用了积分抵扣，则扣除相应的积分
        if ($currentScoreUse > 0) {
            $desc = L_("购买 X1商品 扣除X2", array("X1" => $order['name'], "X2" => cfg('score_name')));
            $desc .= L_('，订单编号') . $order['pigcms_id'];
            $use_result = (new UserService())->userScore($nowUser['uid'], $currentScoreUse, $desc);
            if ($use_result['error_code']) {
                return false;
            }
        }

        //如果用户使用了余额支付，则扣除相应的金额。
        if ($currentSystemBalance > 0) {
            $desc = L_("购买 X1商品 扣除余额，订单编号X2", array("X1" => $order['name'], 'X2' => $order['pigcms_id']));
            $use_result = (new UserService())->userMoney($nowUser['uid'], $currentSystemBalance, $desc);
            if ($use_result['error_code']) {
                return false;
            }
        }
        
        //判断会员卡余额
        if($currentMerchantBalance>0){
            $user_merchant_balance = (new CardNewService())->getCardByUidAndMerId($order['mer_id'],$order['uid']);
            // 您的会员卡余额不够此次支付
            if($user_merchant_balance['card_money'] < $currentMerchantBalance){
                return false;
            }
        }

        if($currentMerchantGiveBalance>0){
            $user_merchant_balance = (new CardNewService())->getCardByUidAndMerId($order['mer_id'],$order['uid']);
            //您的会员卡余额不够此次支付
            if($user_merchant_balance['card_money_give'] < $currentMerchantGiveBalance){
                return false;
            }
        }

        // 保存支付订单信息
        $saveData = [];
        $saveData['pay_time'] = $paidTime ? $paidTime : time();
        $saveData['pay_money'] = $paidMoney;//在线支付的钱
        $saveData['pay_type'] = $paidType;
        //$saveData['third_id'] = $paidOrderid;
        $saveData['paid'] = 1;
        $saveData['status'] = 1;
        $saveData['system_score'] = $currentScoreUse;//积分使用数量
        $saveData['system_score_money'] = $currentScoreDeducte;//积分抵扣金额
        $saveData['system_balance'] = $currentSystemBalance;//平台余额使用金额
        $saveData['merchant_balance_pay'] = $currentMerchantBalance;//平台余额使用金额
        $saveData['merchant_balance_give'] = $currentMerchantGiveBalance;//平台余额使用金额
        $appoint=(new LifeToolsAppoint())->getToolAppointMsg(['appoint_id'=>$order['appoint_id']]);
        // 保存订单信息
        if (!(new LifeToolsAppointJoinOrder())->updateThis(['pigcms_id'=>$orderId], $saveData)) {
            return false;
        }elseif(!empty($appoint)){
            (new LifeToolsAppoint())->setInc(['appoint_id'=>$order['appoint_id']],'join_num');
            if($order['sku_id']){
                (new LifeToolsAppointSku())->setDec(['sku_id'=>$order['sku_id'], 'appoint_id'=>$order['appoint_id']],'stock_num', 1);
            }
        }
        $systemOrderService = new SystemOrderService();
        // 更新系统订单
        $data=['paid'=>1,'status'=>1,'pay_type'=>$paidType,'coupon_price'=>$order['coupon_price'],
            'payment_money'=>$paidMoney,'balance_pay'=>$currentSystemBalance,'score_used_count'=>$currentScoreUse,
            'score_deducte'=>$currentScoreDeducte,'pay_time'=>time()];
        $systemOrderService->editOrder('life_tools_appoint_join',$orderId,$data);
        return true;
    }

    /**
     * 查询核销状态
     * @return array
     */
    public function verifyStatus($params) {
        $orderId = $params['order_id'] ?? '0';
        $nowTime = $params['now_time'] ?? '0';
        if($nowTime<=0){
            return ['status'=>0, 'now_time'=>time()];
        }

        // 查询核销记录
        $where  = [
            ['appoint_id', '=', $orderId],
            ['add_time', '>', $nowTime],
        ];

        $res = (new LifeToolsAppointJoinOrder())->getOne($where, true, ['add_time'=>'desc']);
        if($res){
            return [
                'status' => $res['status'],
                'msg' => $res['status']== 1 ? '核销成功' : '核销失败',
                'now_time'=>time()
            ];
        }
        
        return ['status'=>0, 'now_time'=>time()];
    }

    /**
     * 申请退款操作
     * @param  $business 业务标识
     * @param  $business_oid 业务订单ID
     * @return true | false
     */
    public function refundingOrder($business, $params) {
        $orderId = $params['pigcms_id'] ?? '0';
        $uid = $params['uid'] ?? '0';

        $order = (new LifeToolsAppointJoinOrder())->where('pigcms_id', $orderId)->find();
        if(!$order){
            throw new \think\Exception('订单不存在！');
        }
        if($uid && $order->uid != $uid){
            throw new \think\Exception('无权操作！');
        }
        $where  = [];
        $where[]  = ['pigcms_id', '=', $orderId];
        if($uid){
            $where[]  = ['uid', '=', $uid];
        }
        $update = [
            'is_apply_refund' => 1,
            'apply_refund_reason' => $params['apply_refund_reason'] ?? '',
            'refund_money' => $order->pay_money + $order->system_balance + $order->system_score_money + $order->system_score + $order->merchant_balance_pay + $order->merchant_balance_give,
        ];

        $res = (new LifeToolsAppointJoinOrder())->updateThis($where, $update);
        if ($res === false) {
            throw new \think\Exception('申请退款失败，请稍后重试', 1003);
        }
        return $res;
    }


    
    /**
     * 申请退款操作
     * @param  $business 业务标识
     * @param  $business_oid 业务订单ID
     * @return true | false
     */
    public function cancelRefund($params) {
        $orderId = $params['pigcms_id'] ?? '0';
        $uid = $params['uid'] ?? '0';

        $where  = [
            'pigcms_id' => $orderId,
            'uid'=>$uid
        ];
        $update = [
            'is_apply_refund' => 0,
        ];
        $res = (new LifeToolsAppointJoinOrder())->updateThis($where, $update);
        if ($res === false) {
            throw new \think\Exception('撤销退款失败，请稍后重试', 1003);
        }
        return $res;
    }

    /**
     * 后台审核退款
     * @param  $business 业务标识
     * @param  $business_oid 业务订单ID
     * @return true | false
     */
    public function auditRefund($params) {
        $orderId = $params['pigcms_id'] ?? '0';
        $type = $params['type'] ?? '0';
        $where  = [
            'pigcms_id' => $orderId,
        ];

        if($type == 1){// 同意退款
            // 查询订单详情
            $order = (new LifeToolsAppointJoinOrder())->getOne($where);
            if($order['status'] == 4){
                throw new \think\Exception('订单已退款，不要重复操作', 1003);
            }

            Db::startTrans();

            try {

                $refundMoney = 0;
                $now_join_num = (new LifeToolsAppoint())->where('appoint_id', $order['appoint_id'])->value('join_num');
                if($now_join_num){
                    // 活动报名人数减1
                    Db::name('life_tools_appoint')->where(['appoint_id'=>$order['appoint_id']])->dec('join_num', 1)->update();
                }
                
                if($order['paid'] == 1 && $order['price'] > 0){
                    $refundMoney = $order['pay_money'] + $order['system_score_money'] + $order['system_balance'];
                    // 在线支付金额
                    $paymentMoney = $order['pay_money'];
                    if ($paymentMoney > 0) {
                        $payOrder = (new PayService())->getByExtendsField(['business'=>'life_tools_appoint_join','business_order_id'=>$order['pigcms_id'],'paid'=>1]);
                        if($payOrder){
                            $payRes = (new PayService())->refund($payOrder['orderid'], $paymentMoney);
                        }
                    }

                    //平台积分退款
                    if ($order['system_score'] > 0) {
                        $result = (new UserService())->addScore($order['uid'], $order['system_score'], '活动预约订单退款,增加积分,订单编号' . $order['real_orderid']);
                    }

                    //平台余额退款
                    if ($order['system_balance'] > 0) {
                        $result = (new UserService())->addMoney($order['uid'], $order['system_balance'], '活动预约订单退款,增加余额,订单编号' . $order['real_orderid']);
                    }

                     //商家赠送余额退款
                    if ($order['merchant_balance_give'] > 0) {
                        $result = (new CardNewService())->addUserMoney($order['mer_id'], $order['uid'],0, $order['merchant_balance_give'], 0,'', '活动预约订单退款,增加余额,订单编号' . $order['real_orderid']);
                    }

                    //商家余额退款
                    if ($order['merchant_balance_pay'] > 0) {
                        $result = (new CardNewService())->addUserMoney($order['mer_id'], $order['uid'],$order['merchant_balance_pay'], 0, 0, '活动预约订单退款,增加余额,订单编号' . $order['real_orderid']);
                    }
                }

                // 更新订单状态
                $update = [
                    'is_apply_refund' => 2,
                    'status' => 5,
                    'refund_time' => time(),
                    'refund_money' => get_format_number($refundMoney),
                ];
                Db::name('life_tools_appoint_join_order')->where($where)->update($update);

                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();

                throw new \think\Exception($e->getMessage(), 1005);
            }
        }elseif($type == 2){
            // 拒绝退款
            $where  = [
                'pigcms_id' => $orderId,
            ];
            $update = [
                'is_apply_refund' => 3,
            ];
            $res = (new LifeToolsAppointJoinOrder())->updateThis($where, $update);
            if ($res === false) {
                throw new \think\Exception('拒绝退款失败，请稍后重试', 1003);
            }
        }else{
            throw new \think\Exception('参数错误', 1003);
        }
        return true;
    }

    /**
     * @return \json
     * 赛事活动-我的报名详情
     */
    public function orderDetail($param)
    {
        $where=[['j.pigcms_id','=',$param['pigcms_id']]];
        $list=(new LifeToolsAppointJoinOrder())->orderDetail($where,
            'c.title,c.start_time,c.end_time,c.appoint_id,c.province_id,c.city_id,c.is_del,
            c.area_id,c.long,c.lat,c.address,c.phone,j.price,j.status,j.pigcms_id as order_id,j.paid,
            j.name,j.phone as tel_phone,c.need_verify,j.verify_code,j.verify_time,j.mer_id,c.image_small as image,j.is_apply_refund,j.apply_refund_reason,j.refund_money,j.refund_time,j.custom_form,j.real_orderid,j.add_time,c.can_refund,c.refund_hours,j.sku_id,c.is_select_seat');
        if(!empty($list)){
            
            // 当前是否支持退款
            $canRefund = 0;
            if($list['can_refund'] == 2 || ($list['can_refund'] == 1 && time() < ($list['start_time']-$list['refund_hours']*3600))){
                $canRefund = 1;
            }
            if(!in_array($list['status'], [1,3,4])){// 是否可以申请退款
                $canRefund = 0;
            }
            $list['can_refund'] = $canRefund;

            if(time()<$list['start_time']){//未开始
                $list['compet_status']=1;
                $list['compet_status_txt']="未开始";
                $list['compet_text_color']="#1A86F9";
                
            }elseif ($list['start_time']<=time() && $list['end_time']>time()){//进行中
                $list['compet_status']=2;
                $list['compet_status_txt']="正在进行中";
                $list['compet_text_color']="#19C9AE";
            }else{
                $list['compet_status']=3;
                $list['compet_status_txt']="已结束";
                $list['compet_text_color']="#999999";
            }
            if($list['status'] == 6){
                $list['compet_status']=3;
                $list['compet_status_txt']="已取消";
                $list['compet_text_color']="#999999";
            }

            switch($list['status']){
                case 0:
                    $list['status_txt'] = '待支付';
                    break;
                case 1:
                    $list['status_txt'] = '未核销';
                    break;
                case 2:
                    $list['status_txt'] = '报名失败';
                    break;
                case 3:
                    $list['status_txt'] = '已核销';
                    break;
                case 4:
                    $list['status_txt'] = '已过期';
                    break;
                case 5:
                    $list['status_txt'] = '已退款';
                    break;
            }

            $codeList = [];
            if($list['need_verify'] && $list['paid'] == 1){
                require_once '../extend/phpqrcode/phpqrcode.php';
                if(!empty($list['is_select_seat'])){
                    $codeArr = (new LifeToolsAppointSeatDetail())->getVerify($list['order_id'])->toArray();
                    foreach($codeArr as $key => $val){
                        $tmp = [];
                        $tmp['verify_code'] = $val['code'];
                        $tmp['total_num'] = $val['total_num'];
                        $tmp['can_verify'] = $val['can_verify'];
                        $tmp['seat_num'] = $val['seat_num'];
                        $tmp['seat_title'] = $val['seat_title'];
                        $tmp['verify_status'] = $val['status'];
                        $tmp['code_img'] = $this->getQrCode($val['code']);
                        $tmp['code_img1']  = $this->getBarCode($val['code']);
                        $codeList[] = $tmp;
                    }
                }else{
                    $tmp = [];
                    $tmp['verify_code'] = $list['verify_code'];
                    $tmp['verify_status'] = $list['status'] == 3 ? 1 : 0;
                    $tmp['code_img'] = $this->getQrCode($list['verify_code']);
                    $tmp['code_img1']  = $this->getBarCode($list['verify_code']);
                    $codeList[] = $tmp;
                }
                
            }
            $list['code'] = $codeList;
            if(!empty($list['image'])){
                $list['image']=replace_file_domain($list['image']);
            }else{
                $list['image']="";
            }
            $list['refund_money']= get_format_number($list['refund_money']);
            $list['refund_time']=$list['refund_time'] ? date("Y.m.d H:i:s",$list['refund_time']) : '';
            $list['start_time']=date("Y.m.d H:i:s",$list['start_time']);
            $list['end_time']=date("Y.m.d H:i:s",$list['end_time']);
            $list['now_time'] = time();
            $pro_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$list['province_id']]);
            $city_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$list['city_id']]);
            $area_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$list['area_id']]);
            $list['address']=$pro_name.$city_name.$area_name.$list['address'];
            $list['add_time_text'] = date('Y-m-d H:i', $list['add_time']);
            $merchant = (new Merchant())->field('mer_id,phone,name')->where('mer_id', $list['mer_id'])->find();
            if($merchant){
                $list['mer_name'] = $merchant->name;
                $list['mer_phone'] = $merchant->phone;
            }
            //改为活动电话
            $list['mer_phone'] = $list['phone'];
            //多规格
            if($list['sku_id']){
                $sku = (new LifeToolsAppointSku())
                ->field(['sku_id','price','sku_info', 'stock_num','sku_str'])
                ->where('sku_id', $list['sku_id'])
                ->where('is_del', 0)
                ->find();
                $list['spec_txt'] = $sku['sku_str'];
            }
        }

        // 按钮
        $list['button']['pay_btn'] = ($list['paid'] == 0 && $list['status'] != 6) ? 1 : 0;
        $list['button']['cancel_btn'] = ($list['paid'] == 0 && $list['status'] != 6) ? 1 : 0;

        return $list;
    }

    /**
     * 获取条形码
     */
    private function getBarCode($code)
    {
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();

        $base64Image = base64_encode($generator->getBarcode($code, $generator::TYPE_CODE_128, 1, 1));

        $path = request()->server('DOCUMENT_ROOT').'/runtime/life_tools/Barcode/';

        if(!is_dir($path)){
            mkdir($path, 0777, true);
        }
        base64_to_img($path, $code, $base64Image, 'png');
        return cfg('site_url').'/runtime/life_tools/Barcode/'.$code.'.png';
    }

    /**
     * 获取二维码
     */
    private function getQrCode($code)
    {
        $date = date('Y-m-d');
        $time = date('Hi');
        $qrcode = new \QRcode();
        $errorLevel = "L";
        $size = "9";
        $dir = '../../runtime/qrcode/employee/'.$date. '/' .$time;
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
        $filename_url = '../../runtime/qrcode/employee/'.$date.'/'.$time . '/' . $code.'.png';
        $qrcode->png($code, $filename_url, $errorLevel, $size);
        $QR = 'runtime/qrcode/employee/'.$date.'/'.$time . '/' . $code.'.png';      //已经生成的原始二维码图片文件
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        return $http_type.$_SERVER["HTTP_HOST"].'/'.$QR;
    }

    /**
     * 用户端赛事列表
     */
    public function orderList($param)
    {
        $where=[['j.status','in',[1,2,3,4,5]],['j.is_del','=',0],['j.uid','=',$param['uid']],['j.paid','=',1]];
        $order='j.appoint_id desc';
        switch ($param['status']){
            case 1://未开始
                array_push($where,['c.start_time','>',time()]);
                break;
            case 2:
                array_push($where,['c.start_time','<',time()],['end_time','>',time()]);
                break;
            case 3:
                array_push($where,['c.end_time','<',time()]);
                break;
        }

        $list=(new LifeToolsAppointJoinOrder())->myCompetList($where, 'c.appoint_id,c.title,c.start_time,c.end_time,c.address,j.pigcms_id,j.status',$order,$param['page'],$param['pageSize']);
        if(!empty($list['data'])){
            foreach ($list['data'] as $key=>$value){
                $list['data'][$key]['order_id']=$value['pigcms_id'];
                if($value['start_time']>time()){
                    $list['data'][$key]['compet_status']=1;
                    $list['data'][$key]['compet_status_txt']="未开始";
                }elseif ($value['start_time']<time() && $value['end_time']>time()){
                    $list['data'][$key]['compet_status']=2;
                    $list['data'][$key]['compet_status_txt']="进行中";
                }else{
                    $list['data'][$key]['compet_status']=3;
                    $list['data'][$key]['compet_status_txt']="已结束";
                }
                $list['data'][$key]['start_time']=date("Y-m-d H:i:s",$value['start_time']);
                $list['data'][$key]['end_time']=date("Y-m-d H:i:s",$value['end_time']);
            }
        }
        return $list;
    }

}