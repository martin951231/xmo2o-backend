<?php
/**
 * 房间套餐订单相关
 * @author weili
 * @datetime 2020/8/13
 */

namespace app\community\model\service;

use app\community\model\db\PackageOrder;
use app\community\model\db\HouseProperty;
use app\community\model\db\PackageRoomOrder;
use app\community\model\db\PrivilegePackage;
use app\community\model\db\RoomPackage;
use app\community\model\db\PackageRoomParentOrder;
use app\community\model\service\HousePaidOrderRecordService;
use think\facade\Env;
require_once dirname(dirname(dirname(dirname(__DIR__)))).'/extend/phpqrcode/phpqrcode.php';
class PackageRoomOrderService
{
    public $pay_str = [
        1=>'微信支付',
        2=>'支付宝支付',
        3=>'平台添加',
        4=>'试用',
        5=>'余额支付',
        6=>'积分抵扣',
    ];
    /**
     * Notes: 获取列表
     * @param $where
     * @param $field
     * @param $page
     * @param $limit
     * @return array
     * @author: weili
     * @datetime: 2020/8/13 15:42
     */
    public function getPackageRoomOrderList($where,$field,$page,$limit)
    {
        $dbPackageRoomOrder = new PackageRoomOrder();
        $count = $dbPackageRoomOrder->getCount($where);
        $list = $dbPackageRoomOrder->getSelect($where,$field,$page,$limit);
        if($list) {
            foreach ($list as &$val) {
                if(array_key_exists('details_info',$val) && $val['details_info']) {
                    $val['details_info'] = json_decode($val['details_info'], true);
                }
                $val['is_use'] = 0;
                if($val['package_end_time']>time()){
                    $val['is_use'] = 1;
                    $val['use_type'] = '使用中';
                }elseif($val['package_end_time']<time() && $val['package_end_time']){
                    $val['use_type'] = '已过期';
                }
                if($val['package_period']) {
                    $val['package_period'] = $val['package_period'].'年';
                }
                if($val['package_end_time']) {
                    $val['package_end_time'] = date('Y-m-d', $val['package_end_time']);
                }
                if($val['pay_time']) {
                    $val['pay_time'] = date('Y-m-d H:i:s', $val['pay_time']);
                }
                if(isset($val['num']) && ($val['num']>0) && isset($val['room_num'])){
                    $val['room_num']=$val['room_num']*$val['num'];
                }
            }
        }
        $data = [
            'list'=>$list,
            'count'=>$count,
        ];
        return $data;
    }

    /**
     * Notes: 获取详情
     * @param $where
     * @return array|\think\Model|null
     * @author: weili
     * @datetime: 2020/8/13 15:44
     */
    public function getPackageRoomOrderInfo($where)
    {
        $dbPackageRoomOrder = new PackageRoomOrder();
        $info = $dbPackageRoomOrder->getFind($where);
        if($info && $info['details_info'])
        {
            $info['details_info'] = json_decode($info['details_info'],true);
        }
        if($info && $info['package_end_time']){
            $info['package_end_time'] = date('Y-m-d',$info['package_end_time']);
        }
        if($info && $info['pay_time']){
            $info['pay_time'] = date('Y-m-d H:i:s',$info['pay_time']);
        }
        if($info && isset($info['pay_type'])){
            $info['pay_type'] = $this->pay_str[$info['pay_type']];
        }
        return $info;
    }

    /**
     * Notes: 房间套餐生成订单
     * @param $room
     * @param $property_id
     * @param $pay_type
     * @return string
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/8/19 15:44
     */
    public function createOrder($room,$property_id)
    {
        $dbRoomPackage = new RoomPackage();
        $dbPackageOrder = new PackageOrder();
        $dbPrivilegePackage = new PrivilegePackage();
        $dbPackageRoomOrder = new PackageRoomOrder();
        $dbPackageRoomParentOrder = new PackageRoomParentOrder();
//        $dbHouseProperty = new HouseProperty();
        $trial_where[] = ['property_id', '=', $property_id];
        $trial_where[] = ['pay_type', '<>', 4];//试用套餐不能购买房间套餐
//        $trial_where[] = ['order_type', '<>', 3];
        $trial_where[] = ['status','=',1];
        $trial_where[] = ['package_end_time', '>', time()];//房间套餐到期也不可购买房间套餐
        $trial_field = 'order_id,package_id,property_id,package_end_time,package_period,property_name,property_tel';
        $packageOrderInfo = $dbPackageOrder->getFind($trial_where, $trial_field);

        //获取物业相关信息
        //$propertyInfo = $dbHouseProperty->get_one(['id'=>$property_id],'property_name,property_phone');

//        $field = 'package_id,package_title,package_price,room_num,package_limit_num,package_try_days';
//        $package = $dbPrivilegePackage->getOne(['package_id'=>$packageOrderInfo['package_id']],$field);

        if(!$packageOrderInfo){
            throw new \think\Exception("试用套餐不可购买或者您购买的功能套餐已过期，您可以续费或者升级功能套餐");
        }
        $room_id = array_column($room, 'room_id');
        $new_room = [];
        foreach ($room as $k => $v) {
            $new_room[$v['room_id']] = $v['room_num'];
        }
        $map[] = ['room_id', 'in', $room_id];
        $field = 'room_id,room_title,room_count,room_price';
        $roomList = $dbRoomPackage->getSome($map,$field);

        //套餐剩余天数
        $package_end_day = ceil(($packageOrderInfo['package_end_time']-time())/86400);
        //计算套餐剩余周期赋值给房间套餐
        $period = ($packageOrderInfo['package_end_time']-time())/86400/366;
        $total_money = 0;//初始化房间订单总金额
        $pay_money = 0;//初始化实付金额
        $room_round = date('YmdHis') . rand(100, 999);
        //只计算金额和房间总数
        $total_room_num = 0;//初始化房间总数量
        foreach ($roomList as $val) {
            $pay_period = $new_room[$val['room_id']];//购买周期
            $room_money = sprintf('%.2f',($val['room_price']*$pay_period/366*$package_end_day));
            $total_money+=$room_money;
            $total_room_num += $val['room_count'];
        }
        if($total_money <= 0 ){
            throw new \think\Exception("订单金额异常，生成订单失败");
        }
        //生成房间套餐上级订单
        $order_info = [
            'order_no'=>$room_round,
            'pay_money'=>$total_money,
            'pay_time'=>0,
            'pay_status'=>0,
            'create_time'=>time(),
            'property_id'=>$property_id,
            'total_num'=>$total_room_num,
        ];
        $parent_order_id = $dbPackageRoomParentOrder->addFind($order_info);
        $roomArr = [];
        foreach ($roomList as &$val) {
            //房间购买数量
            $pay_period = $new_room[$val['room_id']];
            $val['pay_period'] = $pay_period;//购买周期
            $room_money = sprintf('%.2f',($val['room_price']*$pay_period/366*$package_end_day));
//            $val['total_room_money'] =$room_money;
//            $total_money+=$val['total_room_money'];
            //时间支付金额 = 房间套餐金额/366*套餐剩余天数 （四舍五入取两位小数）
            $val['pay_money'] =$room_money;//每一个房间各自金额
            $pay_money +=$val['pay_money'];//支付总额
            $roomArr[] = [
                'order_no' => $room_round,
                'room_id' => $val['room_id'],
                'property_id' => $property_id,
                'property_name' => $packageOrderInfo['property_name'],
                'property_tel' => $packageOrderInfo['property_tel'],
                'pay_money' => $room_money,
                'order_money' => $room_money,
                'pay_type' => 0,//(1微信支付 2支付宝支付)
                'package_period' => $period,
                'package_end_time' => $packageOrderInfo['package_end_time'],//结束时间和功能套餐结束时间相同
                'num' => $pay_period,
                'status' => 0,
                'details_info' => json_encode(['room_title' => $val['room_title'], 'price' => $val['room_price'], 'room_num' => $val['room_count']], JSON_UNESCAPED_UNICODE),
                'room_title' => $val['room_title'],
                'package_order_id' => $packageOrderInfo['order_id'],
                'room_num' => $val['room_count'],
                'room_prcie' => $val['room_price'],
                'create_time' => time(),
                'pay_time' => 0,
                'parent_id'=>$parent_order_id,
            ];
        }
        $dbPackageRoomOrder->addAll($roomArr);//一次生产多个房间订单
        //生成套餐日志
        $PackageLogService = new PackageLogService();
        $log_msg = $packageOrderInfo['property_name'].'物业自主购买'.$val['room_title'].'房间套餐';
        $PackageLogService->addLog($log_msg,$packageOrderInfo['package_id'],$property_id,$room_round,$property_id,2,1,2);

        $data['pay_money'] = sprintf('%.2f',$pay_money);//时间支付总金额
        $data['room_order_no'] = $room_round;//订单号
        $data['order_id'] = $parent_order_id;//订单id
        return $data;

    }
    /**
     * Notes:查询订单状态 （0待支付 1支付成功 2支付失败）
     * @param $order_id
     * @return
     * @author: weili
     * @datetime: 2020/8/20 14:22
     */
    public function getOrderStatus($order_id)
    {

        $dbPackageRoomParentOrder = new PackageRoomParentOrder();
        $order_info = $dbPackageRoomParentOrder->getInfo(['order_id'=>$order_id],'order_id,pay_status');
        return $order_info;
        //$dbPackageRoomOrder = new PackageRoomOrder();
//        $order_info = $dbPackageRoomOrder->getSelect(['order_no','=',$order_no],'order_id,order_no,status');
//        $status = [];
//        foreach ($order_info as $val){
//            $status[] = $val['status'];
//        }
//        //检查数组中是否存在没有修改的状态 全部均为1则支付成功
//        if(in_array('0',$status)){
//            $data['status'] = 0;
//            return $data;
//        }else{
//            $data['status'] = 1;
//            return $data;
//        }

    }
    /**
     * Notes:支付回调  修改订单状态
     * @param $post
     * @param $order_id
     * @return bool
     * @author: weili
     * @datetime: 2020/8/20 14:10
     */
    public function afterPay($order_id,$post)
    {
        $dbPackageRoomOrder = new PackageRoomOrder();
        $dbPackageRoomParentOrder = new PackageRoomParentOrder();
        $where[] = ['order_id','=',$order_id];//功能套餐订单id
        $data['status']=$post['paid'];//支付成功状态改为1
        $parent_data['pay_status']=$post['paid'];//支付成功状态改为1
//        $data['pay_time']=$post['paid_time'];
        $data['transaction_no']=$post['paid_orderid'];//交易流水号
        $parent_data['transaction_no']=$post['paid_orderid'];//交易流水号
//        $data['pay_type']=$post['paid_type'];//交易类型
        if($post['current_system_balance']>0){
            $data['pay_time']=time();
            $parent_data['pay_time']=time();
            $data['pay_type']=5;//交易类型 5余额支付
        }else if($post['current_score_deducte']>0){
            $data['pay_time']=time();
            $parent_data['pay_time']=time();
            $data['pay_type']=6;//交易类型 6积分抵扣
        }else{
            $data['pay_time']=$post['paid_time'];
            $parent_data['pay_time']=$post['paid_time'];
            if($post['paid_type']== 'wechat'){
                $paid_type = 1;
            }else{
                $paid_type = 2;
            }
            $data['pay_type']=$paid_type;//交易类型
        }
        //todo 环球支付
        if(isset($post['is_hqpay']) && intval($post['is_hqpay']) == 1){
            $data['pay_type']=($post['hqpay_source'] == 'weixin') ? 1: 2;
        }
        $dbPackageRoomParentOrder->edit($where,$parent_data);
        $dbPackageRoomOrder->edit(['parent_id'=>$order_id],$data);
        $housePaidOrderRecordService=new HousePaidOrderRecordService();
        $housePaidOrderRecordService->addPayPackageRoomOrderRecord($order_id,$post);
        return true;
    }
    /**
     * Notes: 支付之前调用
     * @param $order_id
     * @param $order_no
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/8/20 14:09
     */
    public function getOrderPayInfo($order_id)
    {
        //$dbPackageRoomOrder = new PackageRoomOrder();
        $dbPackageRoomParentOrder = new PackageRoomParentOrder();
        $where[]=['order_id','=',$order_id];
//        dd($order_id);
        //查询订单是否存在
        $order_info = $dbPackageRoomParentOrder->getInfo($where,'order_id,order_no,pay_money,create_time');
        if(!$order_info){
            throw new \think\Exception('订单不存在');
        }
//        $map[] = ['order_no','=',$order_no];
        //房间套餐订单支付金额
        //$room_pay_money = $dbPackageRoomOrder->getSum($map,'pay_money');
        $data['paid'] = 0;
        $data['mer_id'] = 0;
        $data['city_id'] = 0;//城市id
        $data['store_id'] = 0;
        $data['order_money'] = $order_info['pay_money'];//订单支付金额
        $data['uid'] = 0;//用户id
        $data['order_no'] = $order_info['order_no'];//订单号
        $data['title'] = '房间套餐购买';
        $data['time_remaining'] = $order_info['create_time']+15*60-time();//倒计时（15分钟），订单可付款的剩余时间，超出不可支付
        $data['is_cancel'] = 0;//1表示取消订单
        return $data;
    }

    /**
     * Notes:支付成功返回地址
     * @return string
     * @param $order_id
     * @param $is_cancel
     * @author: weili
     * @datetime: 2020/8/20 14:19
     */
    public function getPayResultUrl($order_id,$is_cancel=0)
    {
        $serviceHouseVillage = new HouseVillageService();
        $dbPackageRoomParentOrder = new PackageRoomParentOrder();
        $dbPackageRoomOrder = new PackageRoomOrder();
        if($is_cancel)
        {
            $where[] = ['order_id','=',$order_id];
            $dbPackageRoomParentOrder->edit($where,['pay_status'=>3]);//取消支付
            $map[] = ['parent_id','=',$order_id];
            $dbPackageRoomOrder->edit($map,['status'=>3]);
        }
        $base_url = $serviceHouseVillage->base_url;
        $page_url = cfg('site_url') . $base_url;
        $url = $page_url;
        return $url;
    }
    /**
     * Notes: 生成调转二维码
     * @param $url
     * @param $order_no
     * @param $order_id
     * @return string
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/8/20 16:38
     */
    public function createQrCode($url,$order_id)
    {
        //$dbPackageRoomOrder = new PackageRoomOrder();
        $dbPackageRoomParentOrder = new PackageRoomParentOrder();
        $serviceHouseVillage = new HouseVillageService();
        $base_url = $serviceHouseVillage->base_url;
        $page_url = cfg('site_url') . $base_url.'pages/pay/check';
        $url = $page_url.$url;
        $where[] = ['order_id','=',$order_id];
        //$info = $dbPackageRoomOrder->getFind($where);
        $info = $dbPackageRoomParentOrder->getInfo($where);
        $order_no = $info['order_no'];
        if(!$info){
            throw new \think\Exception('订单不存在');
        }
        $dirName = dirname(__DIR__,5).'/';
        $filename = $dirName.'upload/qrcode/packageorder';
        if(!file_exists($filename)){
            mkdir($filename,0777,true);
        }
        $qrcode = new \QRcode();
        $errorLevel = "L";
        $size = "4";
        $filename = $filename.'/'.$order_no.'.png';
        $qrcode->png($url, $filename, $errorLevel, $size);
        $QR =str_replace($dirName,'',$filename);         //已经生成的原始二维码图片文件
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $data['qrcode'] =  cfg('site_url').'/'.$QR;
        $data['url'] =  $url;
        return $data;
    }
}