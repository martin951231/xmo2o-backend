<?php
/**
 * 房间套餐相关
 * @author weili
 * @datetime 2020/8/13
 */

namespace app\community\model\service;

use app\community\model\db\HouseProperty;
use app\community\model\db\PackageOrder;
use app\community\model\db\PackageRoomParentOrder;
use app\community\model\db\RoomPackage;
use app\community\model\db\PackageRoomOrder;//房间订单
use app\community\model\db\PrivilegePackage;//功能套餐
class RoomPackageService
{
    /**
     * Notes:获取房间套餐列表
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $limit
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/13 10:58
     */
    public function getRoomPackageList($where,$field,$order,$page,$limit)
    {
        $dbRoomPackage = new RoomPackage();
        $count = $dbRoomPackage->getCount($where);
        $dataRoomPackage = $dbRoomPackage->getSome($where,$field,$order,$page,$limit);
        $data = [
            'list'=>$dataRoomPackage,
            'count'=>$count?$count:0,
        ];
        return $data;
    }

    /**
     * Notes:获取详情
     * @param $where
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/13 11:26
     */
    public function detailsRoomPackage($where)
    {
        $dbRoomPackage = new RoomPackage();
        $data = $dbRoomPackage->getOne($where);
        return $data;
    }

    /**
     * Notes: 添加或者编辑房间套餐
     * @param $data
     * @param $room_id
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/8/13 11:20
     */
    public function saveRoomPackage($data,$room_id)
    {
        $dbRoomPackage = new RoomPackage();
        if($room_id)
        {
            $where[] = ['room_id','=',$room_id];
            $find_one = $dbRoomPackage->getOne($where, 'room_id');
            if(!$find_one)
            {
                throw new \think\Exception("此房间套餐不存在或已被删除");
            }
            $res = $dbRoomPackage->updateThis($where,$data);
            if(!$res)
            {
                throw new \think\Exception("编辑失败");
            }
        }else{
            $where[] = ['room_title','=',$data['room_title']];
            $where[] = ['status','<>','-1'];
            $find_one = $dbRoomPackage->getOne($where, 'room_id');
            if($find_one)
            {
                throw new \think\Exception("此房间套餐已存在");
            }
            $res = $dbRoomPackage->add($data);
            if(!$res)
            {
                throw new \think\Exception("添加失败");
            }
        }
        return $data;
    }

    /**
     * Notes: 删除房间套餐  （软删除）
     * @param $where
     * @param $data
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/13 11:32
     */
    public function deleteRoomPackage($where,$data)
    {
        $dbRoomPackage = new RoomPackage();
        $res = $dbRoomPackage->updateThis($where,$data);
        return $res;
    }

    /**
     * Notes: 功能套餐/房间列表
     * @param $package_id
     * @param $package_num
     * @param $type
     * @param int $order_id
     * @param int $property_id
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/8/19 9:00
     */
    public function getRoomList($package_id,$package_num,$type,$order_id=0,$property_id=0)
    {
        $dbRoomPackage = new RoomPackage();
        $dbPackageRoomOrder = new PackageRoomOrder();
        $dbPrivilegePackage = new PrivilegePackage();
        $dbPackageOrder = new PackageOrder();
        $dbPackageRoomParentOrder = new PackageRoomParentOrder();
        $where[] = ['status','<>','-1'];
        $order = 'room_id desc';
        $field = 'room_id,room_title,room_count,room_price,sort,status,create_time';
        $dataRoomPackage = $dbRoomPackage->getSome($where,$field,$order);
        $dataRoomPackage = $dataRoomPackage->toArray();

        $package_where[] = ['package_id','=',$package_id];
        $package_field = 'package_id,package_title,room_num,package_price,package_limit_num';
        $packageInfo = $dbPrivilegePackage->getOne($package_where,$package_field);

        $room_num = 0; //初始化房间数量（购买）
        $total_room_num = 0; //初始化房间数量（购买）
        if($property_id) {
            //自主购买的房间套餐数量
            $pr_where[] = ['property_id', '=', $property_id];
            $pr_where[] = ['pay_status', '=', 1];
            $total_room_num = $dbPackageRoomParentOrder->getFieldSum($pr_where,'total_num');
        }

        $trial_where[] = ['order_id', '=', $order_id];
        $package_order_info = $dbPackageOrder->getFind($trial_where);

        //和套餐一起购买的房间数量
        if($package_order_info) {
            $room_where[] = ['package_order_id','=',$package_order_info['order_id']];
            $room_where[] = ['status','=',1];
            $room_num = $dbPackageRoomOrder->getSum($room_where, 'room_num');
        }
        //房间总数量（和套餐一起购买+另外购买的房间套餐）
        $sum_room_num = $total_room_num+$room_num;
        //所购买的房间总数量之和
        $pay_total_num = $package_order_info['room_num'] + $total_room_num + $room_num-$packageInfo['room_num'];
        $pay_total_num = $pay_total_num>0?$pay_total_num:0;
        //求和功能套餐已购买周期
        $sum_where[] = ['status','=','1'];
        $sum_where[] = ['property_id','=',$property_id];
        $sum_where[] = ['package_id','=',$package_id];
        $sum_package_period = $dbPackageOrder->getSum($sum_where,'package_period');

        $total_remain_money = 0;//初始化剩余金额
        $package_end_period = 0;//初始化剩余可购买周期
        $time = strtotime(date('Y-m-d',time()));
        $package_end_time = ($package_num * 366 ) * 86400 + $time + 86400;//计算套餐到期时间
        $total_money = 0;//初始化功能套餐总额
        //第二天
        $next_day = strtotime(date("Y-m-d",strtotime("+1 day")));
        if($type == 2) {//升级购买
            if (intval($package_order_info['room_num']) > intval($packageInfo['room_num'])) {
                throw new \think\Exception("升级套餐的房间数不能小于现有套餐房间数！！");
            }
            //$package_order_info = $dbPackageOrder->getFind(['order_id'=>$order_id]);
            $package_end_period = floor($packageInfo['package_limit_num']-$package_order_info['package_period']);
            if($package_order_info['pay_type'] == 4){
                $package_num = $package_num?$package_num:1;
                $total_money = $packageInfo['package_price']*$package_num;//购买套餐价格

            }else {
                //剩余时间
                $surplus_time = strtotime(date('Y-m-d',$package_order_info['package_end_time'])) - $next_day;
                //判断是否还有剩余时间
                if ($surplus_time > 0) {
                    $surplus_day = $surplus_time / 86400;//剩余天数
                    //剩余的钱 = 支付金额 - （单价/366 * 剩余天数）
                    $surplus_money = round($package_order_info['pay_money'] / ($package_order_info['package_period'] * 366) * $surplus_day, 2);

                    if ($surplus_money < 0) {
                        $surplus_money = 0;
                    }
//                if ($package_num > $packageInfo['package_limit_num']) {
//                    throw new \think\Exception('套餐选择失败,该套餐最后可购买' . $packageInfo['package_limit_num'] . '年');
//                }
                    //计算要升级套餐单价
                    $package_odd_price = $packageInfo['package_price'] / 366;

                    //功能套餐总金额
                    $total_money = $surplus_day * $package_odd_price - $surplus_money;

                    $package_end_time = $package_order_info['package_end_time'];
                } else {
                    //计算要升级套餐单价
                    $total_money = $packageInfo['package_price'] * $package_num;
                }
            }
        }elseif ($type == 1){//续费
            if($order_id){
                $package_end_period = floor($packageInfo['package_limit_num']-$package_order_info['package_period']);
//                if( ( $package_num+$trialInfo['package_period'] ) > $packageInfo['package_limit_num']){
//                    throw new \think\Exception("购买数量超过最大允许购买数量");
//                }
                if( ( $package_num+$sum_package_period ) > $packageInfo['package_limit_num']){
                    throw new \think\Exception("购买数量超过最大允许购买数量");
                }
                if(!$package_order_info){
                    throw new \think\Exception("订单不存在");
                }
                //试用套餐
                if($package_order_info['pay_type'] == 4){
                    $remain_try_day = 0;//初始化试用剩余时间
                    //使用时间没到期情况
                    if($package_order_info['package_try_end_time'] > time()){
                        //计算剩余天数
                        $remain_try_day = ceil(strtotime(date('Y-m-d',$package_order_info['package_try_end_time']))-$next_day)/(24*3600);
                    }
                    $package_end_time = ($package_num*366+$remain_try_day)*24*3600+$time+86400;//计算套餐到期时间
                }else {
                    $remain_day = 0;//初始化剩余使用时间
                    //正在使用套餐
                    if ($package_order_info['package_end_time'] > time()) {
                        //计算原套餐剩余未使用时间 天数
                        $remain_day =(strtotime(date('Y-m-d',$package_order_info['package_end_time'])) - $next_day) / 86400;
                        //计算每天支付多少钱
                        $day_money = round($package_order_info['pay_money']/($package_order_info['package_period']*366),2);
                        //计算剩下未使用余额
                        $total_remain_money =round($remain_day*$day_money,2);

                    }
                    $package_end_time = ($package_num * 366 + $remain_day) * 24 * 3600 + $time + 86400;//计算套餐到期时间
                }
                //功能套餐总金额
                $total_money = $packageInfo['package_price']*$package_num;
            }
        }
        $price_arr = array_column($dataRoomPackage,'room_count');
        rsort($price_arr);
        $sum_room_numS = $this->getSimilar($price_arr,$pay_total_num);
        $room_total_price = 0;//初始化要购买房间价格
        if($pay_total_num)
        {
            foreach ($dataRoomPackage as $key=>&$val){
                if($val['room_count'] == $sum_room_numS){
                    $val['pitch_type'] = 1;
                    if($val['room_count']) {
                        if ($val['room_count'] > $pay_total_num) {
                            $room_total_price = $val['room_price'];
                        } else {
                            $room_total_price = round(ceil($pay_total_num / $val['room_count'])*$val['room_price'],2);
                        }
                    }
                }else{
                    $val['pitch_type'] = 0;
                }
            }
        }
        $packageInfo['room_total_price'] = $room_total_price;//要购买房间价格
        $packageInfo['total_money'] = round($total_money,2)+$room_total_price;//功能套餐总金额+系统给的默认房间总金额
        $packageInfo['total_remain_money'] = $total_remain_money;//剩下未使用余额
        $packageInfo['package_end_period'] = $package_end_period>0?$package_end_period:0;//剩余周期
        $packageInfo['package_num'] = $package_num; //购买数量（周期）
        $packageInfo['package_end_time'] = date('Y-m-d',$package_end_time);//套餐到期时间
        $packageInfo['pay_room_total_num'] = $pay_total_num;//所购买房间总数量之和
        $data['package'] = $packageInfo;//功能套餐
        $data['room_list'] = $dataRoomPackage;//房间套餐列表
        return $data;
    }

    /**
     * Notes: 比对筛选出接近值
     * @param $arr
     * @param $number
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/17 19:33
     */
    public function getSimilar($arr,$number)
    {
        $arr_new = [];
        $count=count($arr);
        for ($i=0; $i <$count ; $i++) {
            $arr_new[]=abs($number-$arr[$i]);
        }
        if($arr_new) {
            $min= min($arr_new);
            for ($i=0; $i <$count ; $i++) {
                if ($min==$arr_new[$i]) {
                    return $arr[$i];
                }
            }
        }else{
            return $arr;
        }
    }

    /**
     * Notes: 获取选中房间套餐信息及算出总金额
     * @param $room
     * @param $property_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/19 11:03
     */
    public function getOptBuyRoomOrder($room,$property_id)
    {
        $dbRoomPackage = new RoomPackage();
        $dbPackageOrder = new PackageOrder();

        $trial_where[] = ['property_id', '=', $property_id];
        $trial_where[] = ['pay_type', '<>', 4];//试用套餐不能购买房间套餐
//        $trial_where[] = ['order_type', '<>', 3];
        $trial_where[] = ['package_end_time', '>', time()];//房间套餐到期也不可购买房间套餐
        $trial_field = 'order_id,package_id,property_id,package_end_time,package_period';
        $packageOrderInfo = $dbPackageOrder->getFind($trial_where, $trial_field);
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
        $order='room_id desc';
        $roomList = $dbRoomPackage->getSome($map,$field,$order);
        //第二天
        $next_day = strtotime(date("Y-m-d",strtotime("+1 day")));

        //套餐剩余天数
        $package_end_day = ceil((strtotime(date('Y-m-d',$packageOrderInfo['package_end_time'])) - $next_day)/86400);

        $total_money = 0;//初始化房间订单总金额
        $pay_money = 0;//初始化实付金额
        foreach ($roomList as &$val) {
            $pay_period = $new_room[$val['room_id']];
            $val['pay_period'] = $pay_period;//购买周期
            $total_room_money = sprintf('%.2f',($val['room_price']*$pay_period/366*$package_end_day));//总额
            $val['total_room_money'] =$total_room_money;
            $total_money+=$val['total_room_money'];
            //时间支付金额 = 房间套餐金额/366*套餐剩余天数 （四舍五入取两位小数）
            $val['pay_money'] =$total_room_money;
            $pay_money +=$val['pay_money'];
        }
        $data['roomList'] = $roomList;//选中的房间列表
        $data['total_money'] = sprintf('%.2f',$total_money);//选中的总金额
        $data['pay_money'] = sprintf('%.2f',$pay_money);//时间支付总金额
        $data['room_end_time'] = date('Y-m-d',$packageOrderInfo['package_end_time']);//房间到期时间
        $data['room_end_day'] = $package_end_day;//剩余天数
        return $data;
    }

    public function getPackageRoomList($property_id){
        $dbRoomPackage = new RoomPackage();
        $dbPackageOrder = new PackageOrder();
        $where[] = ['status','<>','-1'];
        $order = 'room_id desc';
        $field = 'room_id,room_title,room_count,room_price,sort,status,create_time';
        $dataRoomPackage = $dbRoomPackage->getSome($where,$field,$order);

        if (!empty($dataRoomPackage)){
            $dataRoomPackage = $dataRoomPackage->toArray();
            if (!empty( $dataRoomPackage)){
                foreach ( $dataRoomPackage as &$vv){
                    $vv['pitch_type']=0;
                }
            }
        }

        $where1[] = ['property_id','=',$property_id];
        $where1[] = ['status','=',1];
        $where1[] = ['order_type','<>',3];
        $where1[] = ['package_end_time','>',time()];
        $field1 = 'order_id,order_no,pay_time,package_period,package_title,package_try_end_time,package_end_time,order_money,pay_money,room_num,order_type,package_price,status';
        $packageInfo = $dbPackageOrder->getFind($where1,$field1);
        if (!empty($packageInfo)){
            $packageInfo['package_end_time']=date('Y-m-d',$packageInfo['package_end_time']);
            $packageInfo['pay_time']=date('Y-m-d H:i:s',$packageInfo['pay_time']);
        }else{
            throw new \think\Exception("试用套餐不可购买或者您购买的功能套餐已过期，您可以续费或者升级功能套餐");
        }
        $data['package'] = $packageInfo;//功能套餐
        $data['room_list'] = $dataRoomPackage;//房间套餐列表
        return $data;
    }

    /**
     * 计算购买房间的总金额
     * @author:zhubaodi
     * @date_time: 2022/3/4 14:27
     */
    public function getRoomOrderPrice($data){
        $dbPackageOrder = new PackageOrder();
        $totel_money=0;
        if (!empty($data)){
            $where1[] = ['property_id','=',$data['property_id']];
            $where1[] = ['status','=',1];
            $where1[] = ['order_type','<>',3];
            $where1[] = ['package_end_time','>',time()];
            $field1 = 'order_id,order_no,pay_time,package_period,package_title,package_try_end_time,package_end_time,order_money,pay_money,room_num,order_type,package_price,status';
            $packageInfo = $dbPackageOrder->getFind($where1,$field1);
            $end_time=$packageInfo['package_end_time']-time();
            foreach ( $data['room_list'] as $vv){
                if ($vv['pitch_type']==1){
                    $room_order_money=($vv['room_price']*$vv['room_num'])*($end_time/(366*86400));
                    $totel_money += $room_order_money;
                }
            }
        }
        return ['total_money'=>round_number($totel_money,2)];
    }

    public function createRoomOrderNew($data){
        $dbPackageOrder = new PackageOrder();
        $dbPackageRoomOrder = new PackageRoomOrder();
        $dbPackageRoomParentOrder = new PackageRoomParentOrder();
        $totel_money=0;
        $total_num=0;
        $room_data=[];
        $room_round = date('YmdHis') . rand(100, 999);
        if (!empty($data)){
            $dbHouseProperty = new HouseProperty();
            $info = $dbHouseProperty->get_one(['id'=>$data['property_id']],'property_name,property_phone');
            $where1[] = ['property_id','=',$data['property_id']];
            $where1[] = ['status','=',1];
            $where1[] = ['order_type','<>',3];
            $where1[] = ['package_end_time','>',time()];
            $field1 = 'order_id,order_no,pay_time,package_period,package_title,package_try_end_time,package_end_time,order_money,pay_money,room_num,order_type,package_price,status';
            $packageInfo = $dbPackageOrder->getFind($where1,$field1);
            $end_time=$packageInfo['package_end_time']-time();

            foreach ($data['room_list'] as $vv){
                if ($vv['pitch_type']==1){
                    $room_order_money=($vv['room_price']*$vv['room_num'])*($end_time/(366*86400));
                    $room_data[] = [
                        'order_no' => $room_round,
                        'room_id' => $vv['room_id'],
                        'property_id' => $data['property_id'],
                        'property_name' =>isset($info['property_name'])&&trim($info['property_name'])?trim($info['property_name']):'',
                        'property_tel' =>isset($info['property_phone'])&&trim($info['property_phone'])?trim($info['property_phone']):'',
                        'pay_money' => $room_order_money,
                        'order_money' => $room_order_money,
                        'pay_type' => '',
                        'package_period' => intval($end_time/(366*86400)),
                        'package_end_time' => $packageInfo['package_end_time'],//结束时间和功能套餐结束时间相同
                        'num' => $vv['room_num'],
                        'status' => 0,
                        'details_info' => json_encode(['room_title' => $vv['room_title'], 'price' => $vv['room_price'], 'room_num' => $vv['room_count']], JSON_UNESCAPED_UNICODE),
                        'room_title' => $vv['room_title'],
                        'package_order_id' => $packageInfo['order_id'],
                        'room_num' => $vv['room_count'],
                        'room_prcie' => $vv['room_price'],
                        'create_time' => time(),
                        'pay_time' => 0
                    ];
                    $total_num +=$vv['room_count'];
                   $totel_money += $room_order_money;
                }
            }
            if ($totel_money>0){
                $parent_room_data=[];
                $parent_room_data['pay_money']=round_number($totel_money,2);
                $parent_room_data['pay_status']=0;
                $parent_room_data['create_time']=time();
                $parent_room_data['order_no']=$room_round;
                $parent_room_data['total_num']=$total_num;
                $parent_room_data['property_id']=$data['property_id'];
                $insert_id=$dbPackageRoomParentOrder->addFind($parent_room_data);
            }
            if (!empty($room_data)){
                foreach ($room_data as &$vr){
                    $vr['parent_id']=$insert_id;
                }
                $dbPackageRoomOrder->addAll($room_data);//一次生产多个房间订单
            }

        }
        $new_data=[];
        $new_data['total_money'] = round_number($totel_money,2);
        $new_data['package_order_no'] = $room_round;//只返回功能套餐订单号
        $new_data['package_room_order_id'] = isset($insert_id)?$insert_id:0;//只返回功能套餐订单id
        return  $new_data;
    }
}