<?php
/**
 * Created by PhpStorm.
 * User: zhubaodi
 * Date Time: 2021/4/10 13:33
 */

namespace app\community\model\service;

use app\community\model\db\Express;
use app\community\model\db\HouseMeterAdminElectric;
use app\community\model\db\HouseMeterAdminVillage;
use app\community\model\db\HouseMeterAdminUser;
use app\community\model\db\HouseMeterUserPayorder;
use app\community\model\db\HouseMeterElectricGroup;
use app\community\model\db\HouseMeterElectricPrice;
use app\community\model\db\HouseMeterElectricRealtime;
use app\community\model\db\HouseMeterReadingSys;
use app\community\model\db\Country;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\Area;
use app\community\model\db\AreaStreet;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\User;
use token\Token;
use think\facade\Request;

class HouseMeterUserService
{
    public $base_url = '';
    public $street_url = '';

    public function __construct()
    {

        if (cfg('system_type') == 'village') {
            $this->base_url = '/packapp/village/';
        } else {
            $this->base_url = '/packapp/plat/';
        }
        $this->street_url = '/packapp/street/';
        // }
    }


    //缴费类型 0:预交费 1:余额扣费
    public $payment_type_arr = [
        0 => '预交费', 1 => '余额扣费'
    ];

    //缴费项目 0:电费 1:水费 2:燃气费
    public $payment_num_arr = [
        0 => '电费', 1 => '水费', 2 => '燃气费'
    ];
    //支付类型 0:支付宝 1:微信 2:银联 3:余额抵扣
    public $pay_type_arr = [
        0 => '支付宝', 1 => '微信', 2 => '银联', 3 => '余额抵扣'
    ];
    //支付类型 alipay:支付宝 wechat:微信 unionpay:银联
    public $pay_type_arr1 = [
        'wechat' => '1', 'alipay' => '1', 'unionpay' => '2'
    ];
    // 电表状态
    public $electric_status_arr = [
        0 => '正常', 1 => '异常'
    ];


    /**
     * 获取用户的设备列表
     * @param int $uid 用户uid
     * @param int $page 页码
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time:2021/4/8 16:57
     */
    public function getDeviceList($uid, $page)
    {
        $service_user = new User();
        $admin_user_info = $service_user->getOne(['uid' => $uid]);
        if (!$admin_user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        $service_electric = new HouseMeterAdminElectric();

        $where=[];
        $where=[
            ['b.uid' ,'=',$uid],
            ['b.type','in', [0,1,2,3]]
        ];
        $list = $service_electric->getLists($where, 'a.id,a.electric_name,a.remaining_capacity,a.electric_address,a.village_address', $page);
       fdump_api($list,'DeviceList',1);
        $arr = [];
        if (!$list || $list->isEmpty()) {
            $device_list = [];
        } else {
            $device_list = [];
            foreach ($list as $value) {
                $electric_content = [];
                $electric_content['id'] = $value['id'];
                $electric_content['electric_name'] = $value['electric_name'];
                $content = [];
                $content[] = [
                    'title' => '剩余电量',
                    'info' => $value['remaining_capacity']
                ];
                $content[] = [
                    'title' => '电表编号',
                    'info' => $value['electric_address']
                ];
                $content[] = [
                    'title' => '房间地址',
                    'info' => $value['village_address']
                ];
                $electric_content['content'] = $content;
                $device_list[] = $electric_content;
            }
        }
        $arr['device_list'] = $device_list;
        return $arr;
    }

    /**
     * 获取用户的缴费纪录
     * @param int $uid 用户uid
     * @param int $electric_id 电表uid
     * @param int $page 页码
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time:2021/4/10 16:57
     */
    public function getUserPaymentList($uid, $electric_id, $page)
    {
        $service_user = new User();
        $admin_user_info = $service_user->getOne(['uid' => $uid]);
        if (!$admin_user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        $service_payment = new HouseMeterUserPayorder();
        $where = ['electric_id' => $electric_id, 'payment_type' => 0,'status'=>2];

        $list = $service_payment->getLists($where, '*', $page, 10);
        $arr = [];
        if (!$list || $list->isEmpty()) {
            $payment_list = [];
        } else {
            $payment_list = [];
            foreach ($list as $value) {
                $payment_content = [
                    'id' => $value['id'],
                    'payment_num' => $this->payment_num_arr[$value['payment_num']],
                    'charge_price' => $value['charge_price'],
                    'pay_time' => date('Y-m-d H:i:s', $value['pay_time']),
                ];
                $payment_list[] = $payment_content;
            }
        }
        $arr['payment_list'] = $payment_list;
        return $arr;
    }


    /**
     * 获取用户的扣费纪录
     * @param int $uid 用户uid
     * @param int $electric_id 电表id
     * @param int $page 页码
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time:2021/4/10 16:57
     */
    public function getUserChargingList($uid, $electric_id, $page)
    {
        $service_user = new User();
        $admin_user_info = $service_user->getOne(['uid' => $uid]);
        if (!$admin_user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        $service_payment = new HouseMeterUserPayorder();
        $where = ['electric_id' => $electric_id, 'payment_type' => 1,'status'=>2];

        $list = $service_payment->getLists($where, '*', $page, 10);
        $arr = [];
        if (!$list || $list->isEmpty()) {
            $payment_list = [];
        } else {
            $payment_list = [];
            foreach ($list as $value) {

                $charge_num = $value['end_num'] - $value['begin_num'];
                if ($value['meter_reading_type'] == 1) {
                    $meter_reading_type = date('Y-m', $value['pay_time']);
                } else {
                    $meter_reading_type = date('Y-m-d', $value['pay_time']);
                }
                $payment_content = [
                    'id' => $value['id'],
                    'charge_num' => $value['charge_num'],
                    'charge_price' => $value['charge_price'],
                    'pay_time' =>$meter_reading_type,
                ];
                $payment_list[] = $payment_content;

            }
        }
        $arr['payment_list'] = $payment_list;
        return $arr;
    }


    /**
     * 获取扣费详情
     * @param integer $uid 用户uid
     * @param integer $id 扣费id
     * @param integer $electric_id 电表id
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time:2021/4/8 16:57
     */
    public function getUserChargingInfo($uid, $id, $electric_id)
    {
        $service_user = new User();
        $admin_user_info = $service_user->getOne(['uid' => $uid]);
        if (!$admin_user_info) {
            throw new \think\Exception("该用户不存在！");
        }
        $service_payment = new HouseMeterUserPayorder();
        $where = ['electric_id' => $electric_id, 'id' => $id,'status'=>2];

        $payment_info = $service_payment->getInfo($where);
        $payment_info['pay_time']=date('Y-m-d H:i:s',$payment_info['pay_time']);
        $payment_info['num']=$payment_info['end_num']-$payment_info['begin_num'];
        $arr['payment_info'] = $payment_info;
        return $arr;
    }


    /**
     * 获取实时电量列表
     * @param integer $uid
     * @param integer $electric_id 电表id
     * @param string $time
     * @author:zhubaodi
     * @date_time: 2021/4/10 18:09
     */
    public function getMeterReadingList($time, $electric_id)
    {

        $begin_time = date('Y-m-01', strtotime($time));
        $where[] = ['add_time', '>=', strtotime($begin_time)];
        $end_time = date('Y-m-d', strtotime($begin_time."+1 month -1 day"));
        $where[] = ['add_time', '<=', strtotime($end_time.' 23:59:59')];

        $where[] = ['electric_id', '=', $electric_id];
        $group = "FROM_UNIXTIME(add_time, '%Y-%m-%d')";


        $service_admin_electric = new HouseMeterElectricRealtime();
        $list = $service_admin_electric->getLists($where, 'FROM_UNIXTIME(add_time, "%Y-%m-%d") as time,FROM_UNIXTIME(add_time, "%m-%d") as datetime,end_num-begin_num as num,end_num,begin_num', $group,'add_time ASC');
        $arr = [];
        if (!$list || $list->isEmpty()) {
            $order_list = [];
        } else {
            $list=$list->toArray();
            $order_arr = [];
            $order_list = [];
            $arr['electric_count'] = 0;
            foreach ($list as $value) {

                $order_list=[
                   'time'=> $value['time'],
                   'num'=>$value['num'],
                    'begin_num'=>$value['begin_num'],
                    'end_num'=> $value['end_num'],

                ];

                $arr['electric_count'] += $value['num'];
                $arr['electric_count']=round($arr['electric_count'],2);
                $order_arr[] = $order_list;
            }
            $count=count($list);
            $charts=[];
            if ($count<5){
                foreach ($list as $val){
                    $chart['num']=round_number($val['num'],2);
                    $chart['time']=$val['datetime'];
                    $charts[]=$chart;
                }
            }else{
                for ($i=5;$i<=$count;$i=$i+5){
                    $chart['num']=round_number($list[$i-1]['begin_num']-$list[$i-5]['begin_num'],2);
                    $chart['time']=$list[$i-1]['datetime'];
                    $charts[]=$chart;
                }
                 $j=$i-5;
                if ($count-$j>0){
                    $chart['num']=round_number($list[$count-1]['begin_num']-$list[$j-1]['begin_num'],2);
                    $chart['time']=$list[$count-1]['datetime'];
                    $charts[]=$chart;

                }

            }
            $arr['chart_list']=$charts;
            $arr['list']=array_reverse($order_arr);
        }
        return $arr;
    }


    /**
     * 获取用户的缴费纪录
     * @param integer $uid 用户uid
     * @param integer $electric_id 电表id
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time:2021/4/10 16:57
     */
    public function getPaymentLastInfo($uid, $electric_id)
    {
        $service_user = new User();
        $admin_user_info = $service_user->getOne(['uid' => $uid]);
        if (!$admin_user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        $service_payment = new HouseMeterUserPayorder();
        $where = ['electric_id' => $electric_id, 'payment_type' => 0,'status'=>2];

        $list = $service_payment->getLists($where, 'pay_time,charge_price,id', 1, 1, 'id DESC', 1);
        $payment_arr = [];
        if (!empty($list)){
            $list=$list->toArray();
            if (isset($list[0]) && isset($list[0]['id'])) {
                $payment_content['id'] = $list[0]['id'];
                $payment_content['charge_price'] = $list[0]['charge_price'];
                $payment_content['pay_time'] = date('Y-m-d', $list[0]['pay_time']);
                $payment_arr['payment_last_info'] = $payment_content;
            }
        }

        return $payment_arr;
    }


    /**
     *获取用户当前电表的使用信息
     * @param integer $uid 用户uid
     * @param integer $electric_id 电表id
     * @author:zhubaodi
     * @date_time: 2021/4/12 9:51
     */
    public function getElectricUseInfo($uid, $electric_id)
    {
        $service_user = new User();
        $admin_user_info = $service_user->getOne(['uid' => $uid]);
        if (!$admin_user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        //获取电表的剩余电量
        $service_electric = new HouseMeterAdminElectric();
        $electric_info = $service_electric->getOne($electric_id);

        //获取电表的当前指数
        $service_admin_electric = new HouseMeterElectricRealtime();
        $realtime_list = $service_admin_electric->getList(['electric_id' => $electric_id, 'status' => 0], 'id,electric_id,end_num', 1, 1, 'id DESC');

        $arr = [];
        if (!empty($realtime_list)) {
            $realtime_list = $realtime_list->toArray();
            $realtime_info = reset($realtime_list);
            if (isset($realtime_info['end_num']) && $realtime_info['end_num']) {
                $begin_num = $realtime_info['end_num'].' kW.h';
            } else {
                $begin_num = '0  kW.h';
            }
        } else {
            $begin_num = '0  kW.h';
        }

            if ($electric_info['swicth']=='open'){
                $switch_type=true;
            }else{
                $switch_type=false;
            }
        if ($electric_info['disabled']=='true'){
            $disabled=true;
        }else{
            $disabled=false;
        }



        $electric_arr = [
            'id' => $electric_info['id'],
            'electric_name' => $electric_info['electric_name'],
            'remaining_capacity' => $electric_info['remaining_capacity']??0,
            'begin_num' => $begin_num,
            'disabled' => $disabled,
            'switch_type' => $switch_type,
        ];
        $arr['electric_info'] = $electric_arr;
        $arr['pay_money_list'] = [
            [
                'name' => '100元',
                'value' => 100,
            ],
            [
                'name' => '150元',
                'value' => 150,
            ],
            [
                'name' => '200元',
                'value' => 200,
            ],
            [
                'name' => '300元',
                'value' => 300,
            ],
            [
                'name' => '500元',
                'value' => 500,
            ],
            [
                'name' => '1000元',
                'value' => 1000,
            ],
        ];

//        $content = [];
//        $content[] = [
//            'title' => '电表名称',
//            'info' => $electric_info['electric_name']
//        ];
//        $content[] = [
//            'title' => '剩余电量',
//            'info' => $electric_info['remaining_capacity']
//        ];
//        $content[] = [
//            'title' => '当前电表数',
//            'info' => $realtime_list[0]['begin_num']
//        ];


        return $arr;
    }



    /**
     *获取用户当前电表的使用信息
     * @param integer $uid 用户uid
     * @param integer $electric_id 电表id
     * @author:zhubaodi
     * @date_time: 2021/4/12 9:51
     */
    public function getElectricInfo($electric_id)
    {

        //获取电表的剩余电量
        $service_electric = new HouseMeterAdminElectric();
        $electric_info = $service_electric->getOne($electric_id);


        if ($electric_info['swicth']=='open'){
            $switch_type=true;
        }else{
            $switch_type=false;
        }
        if ($electric_info['disabled']=='true'){
            $disabled=true;
        }else{
            $disabled=false;
        }
        $electric_arr = [
            'id' => $electric_info['id'],
            'disabled' => $disabled,
            'switch_type' => $switch_type,
        ];

        return $electric_arr;
    }

    /**
     * 计算预计充电量
     * @param integer $uid 用户uid
     * @param integer $electric_id 电表id
     * @param string $charge_price 充值金额
     * @author:zhubaodi
     * @date_time: 2021/4/12 9:51
     */
    public function getExpectElectric($uid, $electric_id, $charge_price)
    {
        $service_user = new User();
        $admin_user_info = $service_user->getOne(['uid' => $uid]);
        if (!$admin_user_info) {
            throw new \think\Exception("该用户不存在！");
        }
        if (floatval($charge_price) <= 0) {
            return 0;
        }
        $charge_price = get_format_number($charge_price);
        $service_electric = new HouseMeterAdminElectric();
        $electric_info = $service_electric->getOne($electric_id);


        if (empty($electric_info['electric_price_id'])&&empty($electric_info['unit_price'])) {
            $service_vacancy = new HouseVillageUserVacancy();
            $house_type = $service_vacancy->getOne([['pigcms_id' ,'=', $electric_info['vacancy_id']], ['status', 'in','1,2,3']]);
            $service_price = new HouseMeterElectricPrice();
            $electric_price = $service_price->getInfo(['city_id' => $electric_info['city_id'], 'house_type' => $house_type['house_type']]);
        } elseif(!empty($electric_info['electric_price_id'])) {
            $service_price = new HouseMeterElectricPrice();
            $electric_price = $service_price->getInfo(['id' => $electric_info['electric_price_id']]);
        }else{
            $electric_price['unit_price']=$electric_info['unit_price'];
            $electric_price['rate']=$electric_info['rate'];
        }

        if (empty($electric_price['unit_price'])||empty($electric_price['rate'])){
            throw new \think\Exception("该电表暂未绑定收费标准！");
        }
        $price = $electric_price['unit_price'] * $electric_price['rate'];
        $expect_electric = $charge_price / $price;
        $expect_electric= sprintf("%.3f",substr(sprintf("%.4f", $expect_electric), 0, -2));
        $expect_electric=round($expect_electric,2);
        return $expect_electric;
    }

}
