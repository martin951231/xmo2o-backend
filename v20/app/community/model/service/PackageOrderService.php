<?php
/**
 *功能套餐订单
 * @author weili
 * @datetime 2020/8/13
 */

namespace app\community\model\service;

use app\community\model\db\HouseProperty;
use app\community\model\db\HouseVillage;
use app\community\model\db\PackageOrder;
use app\community\model\db\PackageRoomParentOrder;
use app\community\model\db\PrivilegePackageBind;
use app\community\model\service\PackageLogService;
use app\community\model\db\PrivilegePackage;
use app\community\model\db\RoomPackage;
use app\community\model\db\PackageRoomOrder;
use app\community\model\db\PrivilegePackageContent;
use app\new_marketing\model\db\NewMarketingOrderType;
use app\new_marketing\model\db\NewMarketingPersonMer;
use app\community\model\service\HousePaidOrderRecordService;
use think\facade\Env;
require_once dirname(dirname(dirname(dirname(__DIR__)))).'/extend/phpqrcode/phpqrcode.php';
class PackageOrderService
{
    public $pay_str = [
        1=>'微信支付',
        2=>'支付宝支付',
        3=>'平台添加',
        4=>'试用',
        5=>'余额支付',
        6=>'积分抵扣',
    ];

    public $orderTypeStr = [
        0 => '新订单',
        1 => '升级订单',
        2 => '续费订单',
        3 => '过期订单',
    ];

    /**
     * Notes: 获取订单列表
     * @param $where
     * @param $field
     * @param $page
     * @param $limit
     * @return array
     * @author: weili
     * @datetime: 2020/8/13 14:36
     */
    public function getPackageOrderList($where,$field,$page,$limit)
    {
        $dbPackageOrder = new PackageOrder();
        $map[] = ['status','<>','-1'];
        $count = $dbPackageOrder->getCount($where);
        $list = $dbPackageOrder->getSelect($where,$field,$page,$limit,'order_id desc');
        if($list && !$list->isEmpty()) {
            foreach ($list as &$val) {
                if($val['details_info']) {
                    $val['details_info'] = json_decode($val['details_info'], true);
                }
                $val['is_use'] = 0;
                if($val['order_type'] == 3)
                {
                    $val['use_type'] = '已过期';
                }else {
                    if ($val['package_end_time'] <> 0) {
                        if ($val['package_end_time'] > time()) {
                            $val['is_use'] = 1;
                            $val['use_type'] = '使用中';
                        } elseif ($val['package_end_time'] < time()) {
                            $val['use_type'] = '已过期';
                        }
                    } else {
                        if ($val['package_try_end_time'] && $val['package_try_end_time'] > time()) {
                            $val['is_use'] = 1;
                            $val['use_type'] = '试用中';
                        } elseif ($val['package_try_end_time'] && $val['package_try_end_time'] < time()) {
                            $val['use_type'] = '试用已过期';
                        }
                    }
                }
                if($val['package_end_time']) {
                    $val['package_end_time'] = date('Y-m-d', $val['package_end_time']);
                }
                if($val['pay_time']) {
                    $val['pay_time'] = date('Y-m-d H:i:s', $val['pay_time']);
                }
                if($val['package_try_end_time']){
                    $val['package_try_end_time'] = date('Y-m-d',$val['package_try_end_time']);
                }

                $val['order_type_status'] = $this->orderTypeStr[$val['order_type']] ?? '';
                
            }
        }else{
            $list=array();
        }
        $data = [
            'list'=>$list,
            'count'=>$count,
        ];
        return $data;
    }

    /**
     * Notes: 获取功能套餐订单详情
     * @param $where
     * @return array|\think\Model|null
     * @author: weili
     * @datetime: 2020/8/13 14:47
     */
    public function getPackageOrderInfo($where)
    {
        $dbPackageOrder = new PackageOrder();
        $info = $dbPackageOrder->getFind($where);
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
     * Notes: 生成试用订单
     * @param $post
     * @return array
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/8/17 15:09
     */
    public function addPackageOrder($post)
    {
        $dbPrivilegePackage = new PrivilegePackage();
        $db_privilege_package_bind = new PrivilegePackageBind();
        $package_id = $post['package_id'];
        $property_id = $post['property_id'];
        $field = 'package_title,package_price,room_num,package_limit_num,package_try_days';
        $package = $dbPrivilegePackage->getOne(['package_id'=>$package_id],$field);
        if(!$package_id || !$property_id){
            throw new \think\Exception('请选择有效套餐');
        }
        $package_count = $db_privilege_package_bind->getCount(['package_id'=>$package_id]);
        $order_no = date('YmdHis').rand(100,999);
        $package_try_end_time = intval($package['package_try_days'])*24*3600+strtotime(date('Y-m-d',time()))+24*3600;
        //查询功能套餐对应的应用id
        $content_id_arr = $this->getContentId($package_id);
        $content_id = implode(',',$content_id_arr);
        //生成功能套餐订单
        $data = [
            'order_no' =>$order_no,
            'package_id' =>$package_id,
            'property_id' =>$property_id,
            'property_name' =>isset($post['property_name'])&&trim($post['property_name'])?trim($post['property_name']):'',
            'property_tel' =>isset($post['property_tel'])&&trim($post['property_tel'])?trim($post['property_tel']):'',
            'order_money' =>0,
            'pay_money' =>0,
            'pay_type' =>4,//4表示试用
            'create_time' =>time(),
            'pay_time' =>0,
            'package_period' =>0,
            'package_end_time' =>0,
            'package_try_end_time'=>$package_try_end_time,
            'num' =>0,
            'status' =>0,
            'transaction_no' =>'',
            'details_info' =>json_encode(['package_title'=>$package['package_title'],'num'=>$package_count,'price'=>$package['package_price'],'room_num'=>$package['room_num']],JSON_UNESCAPED_UNICODE),
            'package_title' =>$package['package_title'],
            'room_num' =>$package['room_num'],
            'package_price'=>$package['package_price'],
            'content_id'=>$content_id,
        ];
        $dbPackageOrder = new PackageOrder();
        $order_ids = $dbPackageOrder->insertOrder($data);
        if (!empty($order_ids)&&!empty($property_id)){
            $db_person_mer=new NewMarketingPersonMer();
            $person_info=$db_person_mer->getOne(['mer_id'=>$property_id,'type'=>1]);
            if (!empty($person_info)){
                $db_order_type=new NewMarketingOrderType();
                $data_person=[
                    'order_type'=>1,
                    'order_id'=>$order_ids,
                    'team_id'=>$person_info['team_id'],
                ];
                $db_order_type->addOne($data_person);
            }
        }
        $log_msg = $post['property_name'].'物业试用'.$package['package_title'].'套餐,试用截止时间:'.date('Y-m-d',$package_try_end_time);
        $PackageLogService = new PackageLogService();
        $PackageLogService->addLog($log_msg,$package_id,$property_id,$order_no,1,1,4,2);
        return $data;
    }

    /**
     * Notes: 判断试用 或者使用套餐是否过期
     * @param $property_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/18 10:39
     */
    public function judgeOrderEmploy($property_id)
    {
        $dbPackageOrder = new PackageOrder();
        $where[] = ['property_id','=',$property_id];
//        $where[] = ['status','in',[0,1]];
        $where[] = ['status','=',1];
        $field = 'order_id,order_no,package_try_end_time,package_end_time,order_type,pay_type';
        $packageOrderInfo = $dbPackageOrder->getFind($where,$field);
        if(!$packageOrderInfo){
            unset($where[1]);
            $where[] = ['status','=',0];
            $where[] = ['pay_type','=',4];
            $packageOrderInfo = $dbPackageOrder->getFind($where,$field);
        }
        if($packageOrderInfo){

            // pay_type=4 表示试用
            if($packageOrderInfo['pay_type'] == 4 && ($packageOrderInfo['package_try_end_time'] < time())){
//                $return['msg'] = '试用套餐已过期';
                $return['msg'] = '该物业已到期';
                $return['code'] = '2';
            }else if( $packageOrderInfo['package_end_time'] && ($packageOrderInfo['order_type'] == 3 || $packageOrderInfo['package_end_time'] < time()) ){//order_type=3表示过期
//                $return['msg'] = '使用套餐已过期';
                $return['msg'] = '该物业已到期';
                $return['code'] = '3';
            }else{
                $return['msg'] = '套餐正常试用';
                $return['code'] = '1';
            }
        }else{
            $return['msg'] = '该物业未购买功能套餐';
            $return['code'] = '4';
        }
        $return['data'] = $packageOrderInfo;
        return $return;
    }

    /**
     * Notes: 生产订单
     * @param $post
     * @param $type
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/8/18 16:34
     */
    public function createPackageOrder($post,$type)
    {

        $dbPrivilegePackage = new PrivilegePackage();
        $db_privilege_package_bind = new PrivilegePackageBind();
        $dbPackageOrder = new PackageOrder();
        $package_id = $post['package_id'];
        $property_id = $post['property_id'];
        $field = 'package_title,package_price,room_num,package_limit_num,package_try_days';
        $package = $dbPrivilegePackage->getOne(['package_id'=>$package_id],$field);
        if(!$package_id || !$property_id){
            throw new \think\Exception('参数异常，生成套餐订单失败');
        }

        $dbHouseProperty = new HouseProperty();
        $info = $dbHouseProperty->get_one(['id'=>$property_id],'property_name,property_phone');

        $package_count = $db_privilege_package_bind->getCount(['package_id'=>$package_id]);
        $pay_year= $post['package_num'];
        $order_no = date('YmdHis').rand(100,999);

        //求和功能套餐已购买周期
        $sum_where[] = ['status','=','1'];
        $sum_where[] = ['property_id','=',$property_id];
        $sum_where[] = ['package_id','=',$package_id];
        $sum_package_period = $dbPackageOrder->getSum($sum_where,'package_period');

        $package_end_time = 0; //初始化结束时间
        $order_money = 0; //初始化话
        $money = 0;       //初始化实际支付金额
        $room_z_day = 0;//初始化房间订单周期天数
        $content_id = '';//初始化套餐对应的应用id
        //第二天
        $next_day = strtotime(date("Y-m-d",strtotime("+1 day")));
        if($type == 3){//购买
            //订单金额
            $order_money = $pay_year*$package['package_price'];
            //支付
            $money = $order_money;
            $package_end_time = strtotime(date('Y-m-d',time()))+366*$pay_year*86400+86400;
            $log_msg = $info['property_name'].'物业自主购买'.$package['package_title'].'套餐';
            $room_z_day = $pay_year*366;
            $content_id_arr = $this->getContentId($package_id);
            $content_id = implode(',',$content_id_arr);
        }elseif($type == 2){//升级
            $package_order_info = $dbPackageOrder->getFind(['order_id'=>$post['order_id']]);
            //判断现购买的房间房间是否小于现有的房间数
            if($post['compute_type'] == 2) {
                $pay_total_room_num_status = $this->thanRoomNum($property_id, $package_order_info, $post['room'], $package['room_num']);
                if (!$pay_total_room_num_status) {
                    throw new \think\Exception('现购买的房间数量不得小于现有的房间总数量');
                }
            }
            //根据升级套餐id 查询最新的套餐对应的应用id
            $content_id_arr = $this->getContentId($package_id);
            $content_id = implode(',',$content_id_arr);
            //试用期情况
            if($package_order_info['pay_type'] == 4)
            {
                $time = strtotime(date('Y-m-d',time()));
                $order_money = $package['package_price']*$pay_year;
                $money = $order_money;//支付金额
                $package_end_time = ($post['package_num'] * 366 ) * 86400 + $time + 86400;//计算套餐到期时间
                $room_z_day =$pay_year*366;
            }else {//不是试用的情况
                //剩余时间
                $surplus_time = strtotime(date('Y-m-d',$package_order_info['package_end_time'])) - $next_day;

                //判断是否还有剩余时间
                if ($package_order_info['package_end_time'] && $surplus_time > 0) {
                    $surplus_day = $surplus_time / 86400;//剩余天数
                    //剩余的钱 = 支付金额 - （单价/366 * 剩余天数）
                    $surplus_money = round($package_order_info['pay_money'] / ($package_order_info['package_period'] * 366) * $surplus_day, 2);

                    if ($surplus_money < 0) {
                        $surplus_money = 0;
                    }
                    if ($pay_year > $package['package_limit_num']) {
                        throw new \think\Exception('订单生成失败,该套餐最后可购买' . $package['package_limit_num'] . '年');
                    }
                    //计算要升级套餐单价
                    $package_odd_price = $package['package_price'] / 366;

                    //订单金额
                    $order_money = $surplus_day * $package_odd_price - $surplus_money;
                    $money = $order_money;//支付金额

                    $package_end_time = $package_order_info['package_end_time'];

                    $room_z_day = $surplus_day;
                    $pay_year = ceil($surplus_day/366);//剩余时间换算成年 进一法取整
                } else {
                    //计算要升级套餐单价
                    $order_money = $package['package_price'] * $pay_year;
                    $money = $order_money;//支付金额
                    $room_z_day =$pay_year*366;
                    $time = strtotime(date('Y-m-d',time()));
                    $package_end_time = ($post['package_num'] * 366 ) * 86400 + $time + 86400;
                }
            }
            $log_msg = $info['property_name'].'物业自主升级'.$package['package_title'].'套餐';
        }elseif($type == 1){ //续费
            $package_order_info = $dbPackageOrder->getFind(['order_id'=>$post['order_id']]);
            //判断现购买的房间房间是否小于现有的房间数
            if($post['compute_type'] == 2) {
                $pay_total_room_num_status = $this->thanRoomNum($property_id, $package_order_info, $post['room'], $package['room_num']);
                if (!$pay_total_room_num_status) {
                    throw new \think\Exception('现购买的房间数量不得小于现有的房间总数量');
                }
            }
            //如果套餐已过期查最新的套餐应用id
            if($package_order_info['package_end_time']<time() && $package_order_info['package_try_end_time']<time())
            {
                $bind_where[] = ['package_id','=',$package_order_info['package_id']];
                $content_id_arr =$db_privilege_package_bind->getColumn($bind_where,'content_id');
                $content_id = implode(',',$content_id_arr);
            }else{
                //续费 套餐对应的应用id 取原订单的 不变
                $content_id = $package_order_info['content_id'];
            }

            //订单金额
            $order_money = $pay_year*$package['package_price'];
            $money = $order_money;

            //试用期情况
            if($package_order_info['pay_type'] == 4)
            {
                //剩余时间
                $surplus_time = strtotime(date('Y-m-d',$package_order_info['package_try_end_time'])) - $next_day;
            }else{//不是试用的情况
                //剩余时间
                $surplus_time = strtotime(date('Y-m-d',$package_order_info['package_end_time'])) - $next_day;
            }
            $surplus_time = $surplus_time<0?0:$surplus_time;
            $package_end_time = strtotime(date('Y-m-d',time()))+366*$pay_year*86400+86400+$surplus_time;
            //计算房间订单周期天数
            if($package_order_info['package_end_time'] && $surplus_time>0){
                $room_z_day = $surplus_time/86400;
            }else{
                $room_z_day =$pay_year*366;
            }
            //$pay_year = $package_order_info['package_period']+$pay_year;//购买周期继承上一个订单的周期  (已废弃)
            $has_been_used_period = $sum_package_period+$pay_year;//物业所有功能套餐周期和+续费购买周期
            //进一法取总和周期值 如果大于允许周期 return
            if(ceil($has_been_used_period) > $package['package_limit_num']){
                throw new \think\Exception('订单生成失败,套餐最大允许周期已使用完毕');
            }
            $log_msg = $info['property_name'].'物业自主续费'.$package['package_title'].'套餐';
        }
        //生成功能套餐订单
        $data = [
            'order_no' =>$order_no,
            'package_id' =>$package_id,
            'property_id' =>$property_id,
            'property_name' =>isset($info['property_name'])&&trim($info['property_name'])?trim($info['property_name']):'',
            'property_tel' =>isset($info['property_phone'])&&trim($info['property_phone'])?trim($info['property_phone']):'',
            'order_money' =>$order_money,
            'pay_money' =>$money,
            'pay_type' =>$post['pay_type'],//支付类型
            'create_time' =>time(),
            'pay_time' =>0,
            'package_period' =>$pay_year,
            'package_end_time' =>$package_end_time,
            'package_try_end_time'=>0,
            'num' =>$pay_year,
            'status' =>0,
            'transaction_no' =>'',//交易流水号
            'details_info' =>json_encode(['package_title'=>$package['package_title'],'num'=>$package_count,'price'=>$package['package_price'],'room_num'=>$package['room_num']],JSON_UNESCAPED_UNICODE),
            'package_title' =>$package['package_title'],
            'room_num' =>$package['room_num'],
            'package_price'=>$package['package_price'],
            'order_type'=>$type,
            'content_id'=>$content_id,
        ];
        if($room_z_day<=0){
            throw new \think\Exception('订单生成失败,房间套餐结束时间异常');
        }
        if($post['compute_type'] == 2) {
            $order_ids = $dbPackageOrder->insertOrder($data);
            if (!empty($order_ids)&&!empty($property_id)){
                $db_person_mer=new NewMarketingPersonMer();
                $person_info=$db_person_mer->getOne(['mer_id'=>$property_id,'type'=>1]);
                if (!empty($person_info)){
                    $db_order_type=new NewMarketingOrderType();
                    $data_person=[
                        'order_type'=>1,
                        'order_id'=>$order_ids,
                        'team_id'=>$person_info['team_id'],
                    ];
                    $db_order_type->addOne($data_person);
                }
            }
            $PackageLogService = new PackageLogService();
            $PackageLogService->addLog($log_msg,$package_id,$property_id,$order_no,$property_id,1,$type,2);
        }else{
            $order_ids = 0;
        }

        $dbPackageRoomOrder = new PackageRoomOrder();
        //初始化房间总支付金额
        $total_room_money = 0;
        //升级只需要修改房间订单关联id，不生成新的房间订单
        if($type == 2 && $post['compute_type']==2 && $post['order_id']){
            $roomOrder = $dbPackageRoomOrder->getSelect(['package_order_id'=>$post['order_id']],'order_id');
            if($roomOrder && !is_array($roomOrder) && is_object($roomOrder)){
                $roomOrder=$roomOrder->toArray();
            }
            if(empty($roomOrder)){
                $roomOrder=array();
            }
            $room_order_id = array_column($roomOrder,'order_id');
            $room_where[] = ['order_id','in',$room_order_id];
            $dbPackageRoomOrder->edit($room_where,['package_order_id'=>$order_ids]);
        }else {
            //房间id和房间购买量 数组 例如：room=>[['room_id'=>1,'room_num'=>2],['room_id'=>2,'room_num'=>3]]
            if ($post['room'] && count($post['room']) > 0) {
                $dbRoomPackage = new RoomPackage();
                $room = $post['room'];
                $room_id = array_column($room, 'room_id');
                $new_room = [];
                foreach ($room as $k => $v) {
                    $new_room[$v['room_id']] = $v['room_num'];
                }
                $map[] = ['room_id', 'in', $room_id];
                $roomList = $dbRoomPackage->getSome($map);
                $room_round = date('YmdHis') . rand(100, 999);
                $roomArr = [];
                foreach ($roomList as $key => $val) {
                    $period = $new_room[$val['room_id']];
                    $day_room_price = $val['room_price']/366;//计算房间每天的价格
//                    $room_order_money = $val['room_price'] * $period*$room_z_day;
                    $room_order_money = $day_room_price*$period*$room_z_day;
                    //房间总支付金额
                    $total_room_money += $room_order_money;
                    $roomArr[] = [
                        'order_no' => $room_round,
                        'room_id' => $val['room_id'],
                        'property_id' => $property_id,
                        'property_name' =>isset($info['property_name'])&&trim($info['property_name'])?trim($info['property_name']):'',
                        'property_tel' =>isset($info['property_phone'])&&trim($info['property_phone'])?trim($info['property_phone']):'',
                        'pay_money' => $room_order_money,
                        'order_money' => $room_order_money,
                        'pay_type' => $post['pay_type'],
                        'package_period' => $period,
                        'package_end_time' => $package_end_time,//结束时间和功能套餐结束时间相同
                        'num' => $period,
                        'status' => 0,
                        'details_info' => json_encode(['room_title' => $val['room_title'], 'price' => $val['room_price'], 'room_num' => $val['room_count']], JSON_UNESCAPED_UNICODE),
                        'room_title' => $val['room_title'],
                        'package_order_id' => $order_ids,
                        'room_num' => $val['room_count'],
                        'room_prcie' => $val['room_price'],
                        'create_time' => time(),
                        'pay_time' => 0
                    ];
                }
                if ($roomArr && count($roomArr) > 0) {
                    if($post['compute_type']==2){
                        $dbPackageRoomOrder->addAll($roomArr);//一次生产多个房间订单
                    }
                }
            }
        }
        $new_data['total_money'] = round($money+$total_room_money,2);
        $new_data['package_order_no'] = $order_no;//只返回功能套餐订单号
        $new_data['package_order_id'] = $order_ids;//只返回功能套餐订单id

        return $new_data;
    }

    /**
     * Notes:判断现购买的房间房间是否小于现有的房间数
     * @param $property_id
     * @param $package_order_info
     * @param $room
     * @param $package_room_num
     * @return int
     * @author: weili
     * @datetime: 2020/9/23 18:40
     */
    public function thanRoomNum($property_id,$package_order_info,$room,$package_room_num)
    {
        $dbPackageRoomOrder = new PackageRoomOrder();
        $dbPackageRoomParentOrder = new PackageRoomParentOrder();
        $dbRoomPackage = new RoomPackage();
        $room_num = 0; //初始化房间数量（购买）
        $total_room_num = 0; //初始化房间数量（购买）
        if($property_id) {
            //自主购买的房间套餐数量
            $pr_where[] = ['property_id', '=', $property_id];
            $pr_where[] = ['pay_status', '=', 1];
            $total_room_num = $dbPackageRoomParentOrder->getFieldSum($pr_where,'total_num');
        }
        //和套餐一起购买的房间数量
        if($package_order_info) {
            $room_where[] = ['package_order_id','=',$package_order_info['order_id']];
            $room_where[] = ['status','=',1];
            $room_num = $dbPackageRoomOrder->getSum($room_where, 'room_num');
        }
        //之前购买的房间总数量 = 功能套餐包含的房间数量+自主购买房间套餐房间数量+之前和套餐一起购买的房间数量
        $pay_total_num =  $package_order_info['room_num'] + $total_room_num + $room_num;
        //根据选的房间套餐计算房间总数量
        $room_total_num = 0;//初始化，选择的房间套餐总数量
        if(is_array($room) && count($room)>0) {
            $room_id = array_column($room, 'room_id');
            foreach ($room as $k => $v) {
                $new_room[$v['room_id']] = $v['room_num'];
            }
            $map[] = ['room_id', 'in', $room_id];
            $roomList = $dbRoomPackage->getSome($map);
            foreach ($roomList as $key => $val) {
                $period = $new_room[$val['room_id']];
                $room_total_num += $val['room_count'] * $period;
            }
        }
        //本次购买套餐及房间套餐总房间数=现购买套餐包含房间数+现购买房间套餐房间总数量
        $now_pay_total_num = $package_room_num+$room_total_num;

        if($pay_total_num > $now_pay_total_num)
        {
            return 0;
        }else{
            return 1;
        }
    }

    public function getRoomOrderList($where,$field,$page,$limit)
    {
        $dbPackageRoomOrder = new PackageRoomOrder();
        $listObj = $dbPackageRoomOrder->getSelect($where,$field,$page,$limit);
        $list=array();
        if($listObj && is_object($listObj) && !$listObj->isEmpty()){
            $list=$listObj->toArray();
        }
        return $list;
    }
    
    /**
     * Notes:根据功能套餐id 查询对应的应用id
     * @param $package_id
     * @return array
     * @author: weili
     * @datetime: 2020/8/27 9:57
     */
    public function getContentId($package_id)
    {
        $dbPrivilegePackageBind = new PrivilegePackageBind();
        $map[] = ['package_id', '=', $package_id];
        $content = 'content_id';
        $data = $dbPrivilegePackageBind->getColumn($map, $content);
        return $data;
    }
    /**
     * Notes:查询订单状态 （0待支付 1支付成功 2支付失败）
     * @param $order_id
     * @return array|\think\Model|null
     * @author: weili
     * @datetime: 2020/8/20 14:22
     */
    public function getOrderStatus($order_id)
    {
        $dbPackageOrder = new PackageOrder();
        $order_info = $dbPackageOrder->getFind(['order_id'=>$order_id],'order_id,order_no,status');
        $data['status'] = $order_info['status'];
        return $data;
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
        $dbPackageOrder = new PackageOrder();
        $dbPackageRoomOrder = new PackageRoomOrder();
        $where[] = ['order_id','=',$order_id];//功能套餐订单id
        $data['status']=$post['paid'];//支付成功状态改为1
        if($post['current_system_balance']>0){
            $data['pay_time']=time();
            $data['pay_type']=5;//交易类型 5余额支付
        }else if($post['current_score_deducte']>0){
            $data['pay_time']=time();
            $data['pay_type']=6;//交易类型 6积分抵扣
        }else{
            $data['pay_time']=$post['paid_time']?$post['paid_time']:time();
            $data['pay_type']=$post['paid_type'];//交易类型
        }
        //todo 环球支付
        if(isset($post['is_hqpay']) && intval($post['is_hqpay']) == 1){
            $data['pay_type']=($post['hqpay_source'] == 'weixin') ? 1: 2;
        }
        $data['transaction_no']=$post['paid_orderid'];//交易流水号
        $dbPackageOrder->edit($where,$data);
        $dbPackageRoomOrder->edit(['package_order_id'=>$order_id],$data);
        $housePaidOrderRecordService=new HousePaidOrderRecordService();
        $housePaidOrderRecordService->addPayPackageOrderRecord($order_id,$post);
        return true;
    }
    /**
     * Notes: 支付之前调用
     * @param $order_id
     * @param $order_on
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/8/20 14:09
     */
    public function getOrderPayInfo($order_id)
    {
        $dbPackageOrder = new PackageOrder();
        $dbPackageRoomOrder = new PackageRoomOrder();
        $where[]=['order_id','=',$order_id];
        //查询订单是否存在
        $order_info = $dbPackageOrder->getFind($where,'order_id,pay_money,order_no,create_time');
        if(!$order_info){
            throw new \think\Exception('订单不存在');
        }
        $map[] = ['package_order_id','=',$order_id];
        //房间套餐订单支付金额
        $room_pay_money = $dbPackageRoomOrder->getSum($map,'pay_money');
        $data['paid'] = 0;
        $data['mer_id'] = 0;
        $data['city_id'] = 0;//城市id
        $data['store_id'] = 0;
        $data['order_money'] = $order_info['pay_money']+$room_pay_money;//订单支付金额
        $data['uid'] = 0;//用户id
        $data['order_no'] = $order_info['order_no'];//订单号
        $data['title'] = '功能套餐购买';
        $data['time_remaining'] = $order_info['create_time']+15*60-time();//倒计时（15分钟），订单可付款的剩余时间，超出不可支付
        $data['is_cancel'] = 0;//1表示取消订单
        return $data;
    }

    /**
     * Notes:支付成功返回地址
     * @return string
     * @author: weili
     * @param $order_id
     * @param $is_cancel
     * @datetime: 2020/8/20 14:19
     */
    public function getPayResultUrl($order_id,$is_cancel=0)
    {
        $serviceHouseVillage = new HouseVillageService();
        $dbPackageOrder = new PackageOrder();
        $dbPackageRoomOrder = new PackageRoomOrder();
        if($is_cancel)
        {
            $where[] = ['order_id','=',$order_id];
            $dbPackageOrder->edit($where,['status'=>3]);//取消订单
            $map[] = ['package_order_id','=',$order_id];
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
        $dbPackageOrder = new PackageOrder();
        $serviceHouseVillage = new HouseVillageService();
        $base_url = $serviceHouseVillage->base_url;
        $page_url = cfg('site_url') . $base_url.'pages/pay/check';
        $url = $page_url.$url;
        $where[] = ['order_id','=',$order_id];
        $info = $dbPackageOrder->getFind($where);
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
        $QR =str_replace($dirName,'',$filename);       //已经生成的原始二维码图片文件
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $data['qrcode'] =  cfg('site_url').'/'.$QR;
        $data['url'] =  $url;
        return $data;
    }

    /**
     * Notes: 根据订单查询该物业购买的套餐的相关功能id
     * @param $property_id
     * @return array|\think\Model|null  content=>[1,2,3,4,5.....]
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/8/24 15:31
     */
    public function getPackageContent($property_id)
    {
        if(!$property_id){
            throw new \think\Exception('物业id异常');
        }
        $dbPackageOrder = new PackageOrder();
        $where[] = ['property_id','=',$property_id];
        $where[] = ['status','=',1];
//        $where[] = ['package_end_time|package_try_end_time','>',time()];
        $field = 'order_id,package_id,property_id';
        $data = $dbPackageOrder->getFind($where,$field);
        if(!$data){
            $where = [];
            $where[] = ['property_id','=',$property_id];
            $where[] = ['status','=',0];
            $where[] = ['pay_type','=',4];
            $where[] = ['package_try_end_time','>',time()];
            $data = $dbPackageOrder->getFind($where,$field);
        }
        if($data && isset($data['package_id'])) {
            $data['content'] = $this->getContentId($data['package_id']);
        }
        return $data;
    }

    /**
     * Notes: 增加功能的功能应用，同时增加购买该套餐的功能应用
     * @param $package_id
     * @param $content_arr
     * @return array
     * @author: weili
     * @datetime: 2020/8/29 16:54
     */
    public function increasePackageContent($package_id,$content_arr)
    {
        $dbPackageOrder = new PackageOrder();
        $where[] = ['status','=',1];
        $where[] = ['package_id','=',$package_id];
        $where[] = ['property_id','>',0];
        $where[] = ['package_end_time','>',time()];
//        $content_arr = [1,2,10];
        $list = $dbPackageOrder->getSelect($where);
        $new_arr = [];//初始化
        if($list) {
            foreach ($list as $key => $val) {
                $content_id = explode(',', $val['content_id']);
                $diff = array_diff($content_arr, $content_id);//找出不同应用
                if ($diff && count($diff) > 0) {
                    //$intersect_arr_content = array_intersect($diff,$val['content_id']);//减少
                    //生成新的数组
                    $new_content = array_keys(array_flip($content_arr) + array_flip($content_id));
                    set_time_limit(0);
                    ini_set('memory_limit', '2048M');
                    $intersect_arr_bind_data = array_intersect($diff, $content_arr);//

                    //增加则都增加
                    if ($intersect_arr_bind_data && count($intersect_arr_bind_data) > 0) {
                        sort($new_content);
                        $data['content_id'] = implode(',',$new_content);
                        $res = $dbPackageOrder->edit(['order_id'=>$val['order_id']],$data);
                    }
                    $new_arr[] = [
                        'order_id' => $val['order_id'],
                        'content_id' => $new_content
                    ];
                }
            }
        }
        return $new_arr;
    }

    /**
     * Notes: 根据物业id 或者小区id 获取物业购买的套餐所包含的功能
     * @param string $property_id
     * @param string $village_id
     * @return array|\think\Model|null
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/11/9 11:10
     */
    public function getPropertyOrderPackage($property_id='',$village_id='')
    {

        $dbHouseVillage = new HouseVillage();
        $dbPackageOrder = new PackageOrder();
        $dbPrivilegePackageContent = new PrivilegePackageContent();
        if(!$property_id && $village_id){
            $village_info = $dbHouseVillage->getOne($village_id,'property_id');
            $property_id = $village_info['property_id'];
        }
        if(!$property_id){
            throw new \think\Exception('社区id/物业id异常');
        }
        $map[] = ['property_id','=',$property_id];
        $map[] = ['status','=',1];
        $map[] = ['order_type','<>',3];
        $map[] = ['package_end_time','>',time()];
        $field = 'order_id,package_id,property_id,content_id';
        $package_order_data = $dbPackageOrder->getFind($map,$field,'order_id desc');
        if(!$package_order_data){//查试用套餐
            $map = [];
            $map[] = ['property_id','=',$property_id];
            $map[] = ['status','=',0];
            $map[] = ['package_try_end_time','>',time()];
            $field = 'order_id,package_id,property_id,content_id';
            $package_order_data = $dbPackageOrder->getFind($map,$field,'order_id desc');
        }
        if($package_order_data){
            if($package_order_data['content_id']) {
                $package_order_data['content'] = explode(',', $package_order_data['content_id']);
                $map = [];
                $map[] = ['content_id','in',$package_order_data['content']];
                $package_order_data['application_id'] = $dbPrivilegePackageContent->getColumn($map,'application_id');
                unset($package_order_data['content_id']);
            }
        }
        return $package_order_data;
    }

    

    /**
     * 统计
     * @return float
     */
    public function getSum($where = [], $field='order_money'){
        $count =  (new PackageOrder())->where($where)->sum($field);
        if(!$count) {
            return 0;
        }
        return $count;
    }

    /**
     * 获得总数
     * @return int
     */
    public function getCount($where = []){
        $res = (new PackageOrder())->getCount($where);
        if(!$res) {
            return 0;
        }
        return $res;
    }
}