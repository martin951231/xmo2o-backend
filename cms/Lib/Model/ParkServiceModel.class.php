<?php

use app\community\model\db\HouseVillageParkCharge;
use app\community\model\db\HouseVillageParkCoupon;
use app\community\model\db\InPark;

/**
 * 智慧停车场业务
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/4/15 17:52
 */

class ParkServiceModel extends Model
{
    public $park_sys_type = [
        0 => array(
            'park_sys_type' => 'A1',
            'name' => 'A1智慧停车',
            'park_sys_type_box' => 0
        ),
        1 => array(
            'park_sys_type' => 'D1',
            'name' => 'D1智慧停车',
            'park_sys_type_box' => 1
        ),
        3 => array(
            'park_sys_type' => 'D3',
            'name' => 'D3智慧停车',
            'park_sys_type_box' => 0
        ),
        4 => array(
            'park_sys_type' => 'D5',
            'name' => 'D5智慧停车',
            'park_sys_type_box' => 0
        ),
        5 => array(
            'park_sys_type' => 'D6',
            'name' => 'D6智慧停车',
            'park_sys_type_box' => 0
        ),
    ];
    public function getParkSysType() {
        $park_sys_type = [
            array(
                'park_sys_type' => 'A1',
                'name' => 'A1智慧停车',
                'park_sys_type_box' => 0
            ),
        ];
        $judgeD1Park = D('Config_customization')->getD1ParkJudge();
        if ($judgeD1Park) {
            $park_sys_type[] = array(
                'park_sys_type' => 'D1',
                'name' => 'D1智慧停车',
                'park_sys_type_box' => 1
            );
        }
        $judgeD3Park = D('Config_customization')->getD3ParkJudge();
        if ($judgeD3Park) {
            $park_sys_type[] = array(
                'park_sys_type' => 'D3',
                'name' => 'D3智慧停车',
                'park_sys_type_box' => 0
            );
        }
        $judgeAnakePark = D('Config_customization')->getAnakeParkJudge();
        if ($judgeAnakePark) {
            $park_sys_type[] = array(
                'park_sys_type' => 'D5',
                'name' => 'D5智慧停车',
                'park_sys_type_box' => 0
            );
        }
        $judgeD6Park = D('Config_customization')->getD6ParkJudge();
        if ($judgeD6Park) {
            $park_sys_type[] = array(
                'park_sys_type' => 'D6',
                'name' => 'D6智慧停车',
                'park_sys_type_box' => 0
            );
        }
        
        return $park_sys_type;
    }
    //A11停车卡类
    const  Month_A = 1;
    const  Month_B = 2;
    const  Month_C = 3;
    const  Month_D = 4;
    const  Month_E = 5;
    const  Month_F = 6;
    const  Month_G = 7;
    const  Month_H = 8;
    const  Temp_A = 9;
    const  Temp_B = 10;
    const  Temp_C = 11;
    const  Temp_D = 12;
    const  Temp_E = 13;
    const  Temp_F = 14;
    const  Temp_G = 15;
    const  Temp_H = 16;
    const  Stored_A = 17;
    const  Stored_B = 18;
    const  Stored_C = 19;
    const  Stored_D = 20;
    const  Free_A = 21;

    public $parking_a11_car_type_arr  = [
        self::Month_A => '月租车A',
        self::Month_B => '月租车B',
        self::Month_C => '月租车C',
        self::Month_D => '月租车D',
        self::Month_E => '月租车E',
        self::Month_F => '月租车F',
        self::Month_G => '月租车G',
        self::Month_H => '月租车H',
        self::Temp_A => '临时车A',
        self::Temp_B => '临时车B',
        self::Temp_C => '临时车C',
        self::Temp_D => '临时车D',
        self::Temp_E => '临时车E',
        self::Temp_F => '临时车F',
        self::Temp_G => '临时车G',
        self::Temp_H => '临时车H',
        self::Stored_A => '储值车A',
        self::Stored_B => '储值车B',
        self::Stored_C => '储值车C',
        self::Stored_D => '储值车D',
        self::Free_A => '免费车A',
    ];
    //A11月租车卡类
    public $A11_Month=[self::Month_A,self::Month_B,self::Month_C,self::Month_D,self::Month_E,self::Month_F,self::Month_G,self::Month_H];
    //A11临时车卡类
    public $A11_Temp=[self::Temp_A,self::Temp_B,self::Temp_C,self::Temp_D,self::Temp_E,self::Temp_F,self::Temp_G,self::Temp_H];
    //A11储值车卡类
    public $A11_Stored=[self::Stored_A,self::Stored_B,self::Stored_C,self::Stored_D];

    //D3定制停车参数
    public $AppID = '平台测试';
    public $AppSecret = '12345678';
    public $url = 'http://admin.scxhwtkj.com/parking/api';
    public $url_v2 = 'http://admin.scxhwtkj.com/parking/api/v2';

    // 测试接口 http://test.bolink.club/zld
    // 测试接口 http://yun.bolink.club/zld
    public $formal_url = "http://yun.bolink.club/zld";
    //public $third_ai_park_request_url='http://openapi.szymzh.com/Api';
    public $third_ai_park_request_url='http://openapi.ymiot.net/Api';
    /**
     * Notes:获取签名返回
     * @param $village_id
     * @param $data
     * @return bool|string
     * @author: wanzy
     * @date_time: 2021/4/17 15:55
     */
    public function getD1ParkSign($village_id, $data, $ckey = '')
    {
        if (!$village_id || empty($data)) {
            return false;
        }
        if (!$ckey) {
            $village_park_config = M('House_village_park_config')->where(array('village_id' => $village_id))->find();
            if (empty($village_park_config['ckey'])) {
                $msg['error'] = 1;
                $msg['msg'] = '请联系管理员配置相关数据';
                return $msg;
            }
            $ckey = $village_park_config['ckey'];
        }
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $sign = md5($jsonData . 'key=' . $ckey);
        $sign = strtoupper($sign);
        return $sign;
    }

    /**
     * Notes:组装数据 返回
     * @param $village_id
     * @param $data
     * @return array|bool
     * @author: wanzy
     * @date_time: 2021/4/17 15:55
     */
    public function getAssembleD1Data($village_id, $data, $ckey = '')
    {
        $sign = $this->getD1ParkSign($village_id, $data, $ckey);
        if ($sign) {
            $data_json = [];
            $data_json['data'] = $data;
            $data_json['sign'] = $sign;
            $arr = [];
            // 原始数据
            $arr['original_data'] = $data_json;
            // base64后数据
            $jsonData = json_encode($data_json, JSON_UNESCAPED_UNICODE);
            $arr['base64_data'] = base64_encode($jsonData);
            $arr['sign'] = $sign;
            return $arr;
        } else {
            $msg['error'] = 1;
            $msg['msg'] = $sign['msg'] ? $sign['msg'] : '获取失败';
            return $msg;
        }
    }

    /**
     * Notes:编辑删除月卡会员卡
     * @param $village_id
     * @param $car_id
     * @param $param
     * @param array $carInfo
     * @return array
     * @author: wanzy
     * @date_time: 2021/4/21 14:24
     */
    public function handleMonthCard($village_id, $car_id, $param, $carInfo = [])
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $village_id))->find();
        if (!$village_park_config['ckey'] || !$village_park_config['union_id'] || !$village_park_config['comid']) {
            return false;
        }
        $msg = [
            'error' => 0,
            'msg' => ''
        ];
        $operate_type = 1;
        if (isset($param['is_del']) && $param['is_del']) {
            // 判断是删除
            $operate_type = 3;
        } elseif (isset($param['is_edit']) && $param['is_edit']) {
            // 判断是编辑
            $operate_type = 2;
        }
        $data = [
            'comid' => trim($village_park_config['comid']),
            'union_id' => trim($village_park_config['union_id']),
        ];
        if ($operate_type != 3 && (!$param['begin_time'] || !$param['end_time'])) {
            return false;
        } elseif ($operate_type != 3) {
            $data['begin_time'] = $param['begin_time'];
            $data['end_time'] = $param['end_time'];
        }
        if (isset($param['car_number'])) {
            $data['car_number'] = $param['car_number'];
        }
        if (!isset($param['card_id']) && $operate_type != 3) {
            $where_car = [
                'car_id' => $car_id
            ];
            if (empty($carInfo)) {
                $carInfo = M('House_village_parking_car')->where($where_car)->find();
            }
            if (isset($carInfo['card_id']) && $carInfo['card_id']) {
                $data['card_id'] = $carInfo['card_id'];
                // 判断是编辑
                $operate_type = 2;
            }
        } elseif (isset($param['card_id'])) {
            $data['card_id'] = $param['card_id'];
        }
        if ($operate_type == 1) {
            // 新建时间(unix时间戳格式)
            $data['create_time'] = time();
        } elseif ($operate_type == 2) {
            // 修改时间(unix时间戳格式)
            $data['update_time'] = time();
        }
        if (isset($param['name']) && $operate_type != 3) {
            $data['name'] = $param['name'];
        }
        if (isset($param['tel']) && $operate_type != 3) {
            $data['tel'] = $param['tel'];
        }
        if (isset($param['address']) && $operate_type != 3) {
            $data['address'] = $param['address'];
        }
        if (isset($param['remark']) && $operate_type != 3) {
            $data['remark'] = $param['remark'];
        }
        if (isset($param['car_type']) && $operate_type != 3) {
            $data['car_type'] = $param['car_type'];
        }
        if (isset($param['p_lot']) && $operate_type != 3) {
            $data['p_lot'] = $param['p_lot'];
        }
        $data['operate_type'] = $operate_type;
        $data_msg = $this->getAssembleD1Data($village_id, $data, $village_park_config['ckey']);
        if ($data_msg['base64_data']) {
            $url = $this->formal_url . '/monthcard/handlemonthcard';
            $headers = array(
                "Content-type: application/json;charset='utf-8'",
                'Content-Encoding: UTF-8',
            );
            $monthCard = httpRequest($url, 'POST', $data_msg['base64_data'], $headers);
            $monthCardData = base64_decode($monthCard[1]);
            $cardData = json_decode($monthCardData, true);
            if (!$cardData['data']['state']) {
                $msg['error'] = 1;
                $msg['msg'] = $cardData['data']['errmsg'] ? $cardData['data']['errmsg'] : '查询失败';
                fdump_api(['d1月卡会员 失败:' . __LINE__, $village_id, $param, $monthCard, $cardData], 'park/d1/d1MonthCardErrLog', 1);
                return $msg;
            }
            fdump_api(['d1月卡会员' . __LINE__, $village_id, $param, $url, $data_msg, $monthCard, $cardData], 'park/d1/d1MonthCardLog', 1);
            return $msg;
        } else {
            $msg['error'] = 1;
            $msg['msg'] = $data_msg['msg'] ? $data_msg['msg'] : '查询失败';
            fdump_api(['d1月卡会员 失败:' . __LINE__, $village_id, $param, $data_msg], 'park/d1/d1MonthCardErrLog', 1);
            return $msg;
        }
    }

    /**
     * Notes: 月租车辆查询接口
     * @param $village_id
     * @param $car_number
     * @return array|bool
     * @author: wanzy
     * @date_time: 2021/4/28 9:57
     */
    public function getMonthCar($village_id, $car_number)
    {
        if (!$car_number) {
            return false;
        }
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $village_id))->find();
        if (!$village_park_config['ckey'] || !$village_park_config['union_id'] || !$village_park_config['comid']) {
            return false;
        }
        $msg = [
            'error' => 0,
            'msg' => ''
        ];
        $data = [
            'comid' => trim($village_park_config['comid']),
            'union_id' => trim($village_park_config['union_id']),
        ];
        $data['car_number'] = $car_number;
        $data_msg = $this->getAssembleD1Data($village_id, $data, $village_park_config['ckey']);
        if ($data_msg['base64_data']) {
            $url = $this->formal_url . '/openservice/getmonthcar';
            $headers = array(
                "Content-type: application/json;charset='utf-8'",
                'Content-Encoding: UTF-8',
            );
            $monthCard = httpRequest($url, 'POST', $data_msg['base64_data'], $headers);
            $monthCardData = base64_decode($monthCard[1]);
            $cardData = json_decode($monthCardData, true);
            if (!$cardData['data']['state']) {
                $msg['error'] = 1;
                $msg['msg'] = $cardData['data']['message'] ? $cardData['data']['message'] : '查询失败';
                fdump_api(['d1月租车辆查询 失败:' . __LINE__, $village_id, $car_number, $monthCard, $cardData], 'park/d1/getMonthCarErrLog', 1);
                return $msg;
            }
            $data = $cardData['data'];
            if (!empty($data['car_list'])) {
                $car_list = $data['car_list'];
                $car_info = reset($car_list);
                $msg['car_info'] = $car_info;
            } else {
                $msg['car_info'] = [];
            }
            fdump_api(['d1月租车辆查询' . __LINE__, $village_id, $car_number, $url, $data_msg, $monthCard, $cardData], 'park/d1/getMonthCarLog', 1);
            return $msg;
        } else {
            $msg['error'] = 1;
            $msg['msg'] = $data_msg['msg'] ? $data_msg['msg'] : '查询失败';
            fdump_api(['d1月租车辆查询 失败:' . __LINE__, $village_id, $car_number, $data_msg], 'park/d1/getMonthCarErrLog', 1);
            return $msg;
        }
    }


    /**
     * Notes: 在场订单价格查询
     * @param $village_id
     * @param $car_id
     * @param $param
     * @param array $carInfo
     * @return array
     * @author: wanzy
     * @date_time: 2021/4/21 17:27
     */
    public function queryOrderPrice($village_id, $param, $car_id = 0, $carInfo = [])
    {
        $garage_id=0;
        if(isset($param['garage_id']) && $param['garage_id']>0){
            $garage_id=$param['garage_id'];
        }
        $village_park_config=D('House_village_park_garage_config')->getVillageParkConfig($garage_id,$village_id);
        
        $msg = [
            'error' => 0,
            'msg' => ''
        ];
        if (!$village_park_config['ckey'] || !$village_park_config['union_id'] || !$village_park_config['comid']) {
            $msg['error'] = 1;
            $msg['msg'] = '请联系工作人员，配置现相关参数';
            return $msg;
        }
        $data = [
            'comid' => trim($village_park_config['comid']),
            'union_id' => trim($village_park_config['union_id']),
        ];
        if (isset($param['car_number']) && $param['car_number']) {
            $data['car_number'] = $param['car_number'];
        } elseif (isset($carInfo['car_number']) && $carInfo['car_number']) {
            $data['car_number'] = $carInfo['province'] . $carInfo['car_number'];
        } elseif ($car_id) {
            $where = [
                'car_id' => $car_id
            ];
            $carInfo = D('House_village_parking_car')->where($where)->find();
            $data['car_number'] = $carInfo['province'] . $carInfo['car_number'];
        } else {
            $msg['error'] = 1;
            $msg['msg'] = '缺少对应车牌';
            fdump_api(['d1在场订单价格查询[缺少车牌]:' . __LINE__, $village_id, $param, $car_id, $carInfo], 'park/d1/d1QueryOrderPriceErrLog', 1);
            return $msg;
        }
        $data_msg = $this->getAssembleD1Data($village_id, $data, $village_park_config['ckey']);
        if ($data_msg['base64_data']) {
            $url = $this->formal_url . '/queryorderprice';
            $headers = array(
                "Content-type: application/json;charset='utf-8'",
                'Content-Encoding: UTF-8',
            );
            $queryOrderPrice = httpRequest($url, 'POST', $data_msg['base64_data'], $headers);
            $queryOrderPriceData = base64_decode($queryOrderPrice[1]);
            $price_data = json_decode($queryOrderPriceData, true);
            fdump_api(['d1在场订单价格查询:' . __LINE__, $village_id, $param, $url, $data_msg, $queryOrderPrice, $price_data], 'park/d1/d1QueryOrderPriceLog', 1);
            if (isset($price_data['result_list']['data'])) {
                $result_list = $price_data['result_list']['data'];
            } elseif (isset($price_data['result_list'])) {
                $result_list = $price_data['result_list'];
            } elseif (isset($price_data['data'])) {
                $result_list = $price_data['data'];
            }
            if (!$result_list['state']) {
                $msg['error'] = 1;
                $msg['msg'] = $result_list['errmsg'] ? $result_list['errmsg'] : '查询失败';
                fdump_api(['d1在场订单价格查询失败[查询平台反馈失败]:' . __LINE__, $village_id, $param, $queryOrderPrice, $price_data], 'park/d1/d1QueryOrderPriceErrLog', 1);
                return $msg;
            }
            fdump_api(['d1在场订单价格查询:' . __LINE__, $village_id, $param, $url, $data_msg, $queryOrderPrice, $price_data], 'park/d1/d1QueryOrderPriceLog', 1);
            $data_temp = [];
            $data_temp['service_name'] = 'd1_query_price';
            $data_temp['village_id'] = $village_id;
            $data_temp['add_time'] = time();
            $data_temp['state'] = 1;
            $data_temp['status'] = 0;
            $data_temp['is_pay_scene'] = 0;
            $data_temp['comid'] = $result_list['comid'];
            if (isset($result_list['money']) && $result_list['money']) {
                $data_temp['price'] = $result_list['money'];
            } else {
                $data_temp['price'] = 0;
            }
            if (isset($result_list['total']) && $result_list['total']) {
                $data_temp['total'] = $result_list['total'];
            } else {
                $data_temp['total'] = 0;
            }
            if (isset($result_list['derate_money']) && $result_list['derate_money']) {
                $data_temp['derate_money'] = $result_list['derate_money'];
            } else {
                $data_temp['derate_money'] = 0;
            }
            if (isset($result_list['derate_duration']) && $result_list['derate_duration']) {
                $data_temp['derate_duration'] = $result_list['derate_duration'];
            }
            if (isset($result_list['position']) && $result_list['position']) {
                $data_temp['position'] = $result_list['position'];
            }
            if (isset($result_list['free_out_time']) && $result_list['free_out_time']) {
                $data_temp['free_out_time'] = $result_list['free_out_time'];
            }
            if ($result_list['query_time']) {
                $query_time = date('Y') . '-' . $result_list['query_time'];
                $query_time = strtotime($query_time);
                if ($query_time) {
                    $data_temp['query_time'] = $query_time;
                }
            }
            if (isset($result_list['order_id']) && $result_list['order_id']) {
                $data_temp['order_id'] = $result_list['order_id'];
            }
            if (isset($result_list['park_name']) && $result_list['park_name']) {
                $data_temp['park_name'] = $result_list['park_name'];
            }
            if (isset($result_list['plate_number']) && $result_list['plate_number']) {
                $data_temp['car_number'] = $result_list['plate_number'];
            } else {
                $data_temp['car_number'] = $data['car_number'];
            }
            if ($result_list['start_time']) {
                $start_time = date('Y') . '-' . $result_list['start_time'];
                $start_time = strtotime($start_time);
                if ($start_time) {
                    $data_temp['in_time'] = $start_time;
                }
            }
            if (isset($result_list['duration']) && $result_list['duration']) {
                if (preg_match('/[\x7f-\xff]/', $result_list['duration'])) {
                    // 检查到有汉字单独针对处理  当前会出现 "5分钟"、"2小时3分钟"、"1天 2小时7分钟"
                    $duration_all = trim($result_list['duration']);
                    $stripos_day = stripos($duration_all, '天');
                    $stripos_hour = stripos($duration_all, '小时');
                    $stripos_minute = stripos($duration_all, '分钟');
                    $day = 0;
                    $hour = 0;
                    $minute = 0;
                    if ($stripos_day !== false) {
                        $day_arr = explode('天', $duration_all);
                        $day = intval($day_arr[0]);
                        $duration_h = $day_arr[1];
                    }
                    if ($stripos_day === false && $stripos_hour !== false) {
                        $day = 0;
                        $duration_h = $duration_all;
                    }
                    if ($stripos_day === false && $stripos_hour === false && $stripos_minute !== false) {
                        $day = 0;
                        $hour = 0;
                        $duration_m = $duration_all;
                    }
                    if ($duration_h) {
                        $hour_arr = explode('小时', $duration_h);
                        $hour = intval($hour_arr[0]);
                        $duration_m = $hour_arr[1];
                    }
                    if ($duration_m) {
                        $minute = intval(str_replace('分钟', '', $duration_m));
                    } elseif (!$day && !$hour) {
                        $minute = intval($duration_all);
                    }
                    $duration = $day * 24 * 60 + $hour * 60 + $minute;
                } else {
                    $duration = intval($result_list['duration']);
                }
                $data_temp['duration'] = $duration;
            } elseif ($data_temp['in_time']) {
                $duration_time = intval(time()) - $data_temp['in_time'];
                if ($duration_time > 0) {
                    $data_temp['duration'] = ceil($duration_time / 60);
                }
            }
            $data_temp['query_order_no'] = $result_list['query_order_no'];
            $data_temp['prepay'] = $result_list['prepay'];
            $query_order_no = M('House_village_parking_temp')->data($data_temp)->add();
            if (!$query_order_no) {
                fdump_api(['d1在场订单价格查询[对应添加查询结果失败]:' . __LINE__, $village_id, $param, $data_temp, M('House_village_parking_temp')->getDbError()], 'park/d1/d1QueryOrderPriceLog', 1);
            }
            $msg['query_order_no'] = $query_order_no;
            return $msg;
        } else {
            $msg['error'] = 1;
            $msg['msg'] = $data_msg['msg'] ? $data_msg['msg'] : '查询失败';
            fdump_api(['d1在场订单价格查询失败[参数组装失败]:' . __LINE__, $village_id, $param, $data, $data_msg], 'park/d1/d1QueryOrderPriceErrLog', 1);
            return $msg;
        }
    }


    public function queryOrderPriceD3($village_id, $car_number, $uid, $spread, $channel_id = '', $garage_id = 0, $free_park_time = 0)
    {
        fdump_api(['$village_id' => $village_id, '$car_number' => $car_number, '$uid' => $uid, '$spread' => $spread], 'park_pay_order_0411_1', 1);
        $msg = [
            'error' => 0, 'msg' => ''
        ];
        if (empty($car_number)) {
            $tmp_add_time=time()-100*24*3600;
            $where_visitor = [
                'village_id' => $village_id, 'uid' => $uid, 'car_id' => ['like', '%临%'],'add_time'=>array('gt',$tmp_add_time),
            ];
            $visitor_info = D('House_village_visitor')->where($where_visitor)->order('id DESC')->find();
            if (empty($visitor_info)) {
                $msg['error'] = 1;
                $msg['msg'] = '没有查到停车纪录，请手动输入车牌查询！';
                return $msg;
            }
            $car_number = $visitor_info['car_id'];
        }
        if(empty($car_number)){
            $msg['error'] = 1;
            $msg['msg'] = '没有查到停车纪录，请手动输入车牌查询！';
            return $msg;
        }

        $db_in_park = D('In_park');
        $db_house_village_park_temp = M('House_village_parking_temp');
        $db_house_village_park_charge = D('House_village_park_charge');

        
        $parkConfigWhere = array('village_id' => $village_id);
        $house_village_park_config = D('House_village_park_garage_config')->getVillageParkConfigByWhere($parkConfigWhere, $garage_id);
        if ($free_park_time <= 0 && $house_village_park_config && isset($house_village_park_config['free_park_time'])) {
            $free_park_time = $house_village_park_config['free_park_time'];
        }
        $park_sys_type =$house_village_park_config ?  $house_village_park_config['park_sys_type']:'D3';
        $park_sys_type = $park_sys_type=='HXT85' ? 'HXT85':'D3';
        $where = [];
        $where['car_number'] = $car_number;
        $where['park_id'] = $village_id;
        $where['park_sys_type'] = $park_sys_type;
        $where['is_paid'] = 0;
        $where['is_out'] = 0;
        $where['del_time'] = 0;
        $park_info = $db_in_park->where($where)->order('id DESC')->find();
        $db_house_village_car_flow_temp = M('House_village_car_flow_temp');
        $nowTime = time();
        if (empty($park_info)) {
            if($house_village_park_config['not_inPark_money']>0){
                /***无入场记录收费**/
                $data_temp = [];
                $data_temp['village_id'] = $village_id;
                $data_temp['add_time'] = $nowTime;
                $data_temp['free_out_time'] = 0;
                $data_temp['duration'] = 0;
                $data_temp['derate_money'] = 0;
                $data_temp['derate_duration'] = 0;
                $order_id = '331' . date('YmdHis') . rand(100, 999);
                $data_temp['order_id'] = $order_id;
                $data_temp['query_order_no'] = build_real_orderid($uid);
                $data_temp['errmsg'] = $msg['msg'];
                $data_temp['price'] = $house_village_park_config['not_inPark_money'];
                $data_temp['total'] = $house_village_park_config['not_inPark_money'];
                $data_temp['in_time'] = 0;
                $data_temp['car_number'] = $car_number;
                $data_temp['out_channel_id'] = $channel_id;
                $data_temp['park_sys_type'] = $park_sys_type;
                $data_temp['is_pay_scene'] = $spread - 1;
                $data_temp['coupon_id'] = 0;
                $data_temp['garage_id']=$garage_id;
                fdump_api([__LINE__, $data_temp, $spread], 'park_pay_order_0411_1', 1);
                $query_order_no = M('House_village_parking_temp')->data($data_temp)->add();
                if ($query_order_no) {
                    $msg['query_order_no'] = $query_order_no;
                }
                $is_paid = 0;
                $hasCoupon = false;
                $is_pay_scene = $spread - 1;
                $uniqueArr = [
                    'car_number' => $car_number, 'village_id' => $village_id, 'is_paid' => $is_paid, 'is_pay_scene' => $is_pay_scene,
                    'out_channel_id' => $channel_id, 'park_sys_type' => $park_sys_type, 'hasCoupon' => $hasCoupon,
                ];
                $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
                $flowTempData = [
                    'parking_temp_id' => $query_order_no, 'village_id' => $village_id, 'car_number' => $car_number,
                    'is_paid' => $is_paid, 'park_sys_type' => $park_sys_type, 'coupon_id' => 0, 'out_channel_id' => $channel_id,
                    'is_pay_scene' => $is_pay_scene, 'order_id' => $order_id, 'price' => $data_temp['total'], 'query_time' => $nowTime, 
                    'in_time' => 0, 'unique_id' => $unique_id, 'out_record_id' => '', 'is_out' => 0, 'in_record_id' => 0,
                    'reduction_time' => 0, 'deduction_amount' => 0, 'free_out_time' => 0, 'duration' => 0,
                ];
                if ($flowTempData['price'] == 0) {
                    $flowTempData['is_paid'] = 1;
                    $flowTempData['pay_time'] = time();
                }
                $whereUnique = [
                    'unique_id' => $unique_id,
                ];
                $flowTempInfo = $db_house_village_car_flow_temp->where($whereUnique)->find();
                if (isset($flowTempInfo['id']) && $flowTempInfo['id']) {
                    $flowTempData['update_time'] = time();
                    $db_house_village_car_flow_temp->where($whereUnique)->data($flowTempData)->save();
                } else {
                    $flowTempData['add_time'] = time();
                    $flow_temp_id = $db_house_village_car_flow_temp->add($flowTempData);
                }
                return $msg;
            }else{
                /***无入场记录收费**/
                $data_temp=[];
                $data_temp['village_id']=$village_id;
                $data_temp['add_time']=time();
                $data_temp['free_out_time']=0;
                $data_temp['duration']=0;
                $data_temp['derate_money']=0;
                $data_temp['derate_duration']=0;
                $order_id='332'.date('YmdHis').rand(100,999);
                $data_temp['order_id']=$order_id;
                $data_temp['query_order_no']=build_real_orderid($uid);
                $data_temp['errmsg']=$msg['msg'];
                $data_temp['price']=0;
                $data_temp['total']=0;
                $data_temp['in_time']=0;
                $data_temp['car_number']=$car_number;
                $data_temp['out_channel_id']=$channel_id;
                $data_temp['park_sys_type']=$park_sys_type;
                $data_temp['is_pay_scene']=$spread-1;
                $data_temp['coupon_id']=0;
                $data_temp['garage_id']=$garage_id;
                fdump_api([__LINE__,$data_temp,$spread],'park_pay_order_0411_1',1);
                $query_order_no = M('House_village_parking_temp')->data($data_temp)->add();
                if ($query_order_no) {
                    $msg['query_order_no'] = $query_order_no;
                }
                $is_paid = 0;
                $hasCoupon = false;
                $is_pay_scene = $spread - 1;
                $uniqueArr = [
                    'car_number' => $car_number, 'village_id' => $village_id, 'is_paid' => $is_paid, 'is_pay_scene' => $is_pay_scene,
                    'out_channel_id' => $channel_id, 'park_sys_type' => $park_sys_type, 'hasCoupon' => $hasCoupon,
                ];
                $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
                $flowTempData = [
                    'parking_temp_id' => $query_order_no, 'village_id' => $village_id, 'car_number' => $car_number,
                    'is_paid' => $is_paid, 'park_sys_type' => $park_sys_type, 'coupon_id' => 0, 'out_channel_id' => $channel_id,
                    'is_pay_scene' => $is_pay_scene, 'order_id' => $order_id, 'price' => 0, 'query_time' => $nowTime,
                    'in_time' => 0, 'unique_id' => $unique_id, 'out_record_id' => '', 'is_out' => 0, 'in_record_id' => 0,
                    'reduction_time' => 0, 'deduction_amount' => 0, 'free_out_time' => 0, 'duration' => 0,
                ];
                if ($flowTempData['price'] == 0) {
                    $flowTempData['is_paid'] = 1;
                    $flowTempData['pay_time'] = time();
                }
                $whereUnique = [
                    'unique_id' => $unique_id,
                ];
                $flowTempInfo = $db_house_village_car_flow_temp->where($whereUnique)->find();
                if (isset($flowTempInfo['id']) && $flowTempInfo['id']) {
                    $flowTempData['update_time'] = time();
                    $db_house_village_car_flow_temp->where($whereUnique)->data($flowTempData)->save();
                } else {
                    $flowTempData['add_time'] = time();
                    $flow_temp_id = $db_house_village_car_flow_temp->add($flowTempData);
                }
                return $msg;
            }
        }
        $park_count = $db_in_park->where(['car_number' => $car_number, 'park_id' => $village_id])->count();
        $where_rule['village_id'] = $village_id;
        $where_rule['status'] = 1;
        $where_rule['fees_type'] = 3;
        $where_rule['charge_valid_time'] = array('elt', time());
        $rule_info = D('House_new_charge_rule')->where($where_rule)->field('*')->order('charge_valid_time DESC')->find();
        if (empty($rule_info)) {
            $msg['error'] = 1;
            $msg['msg'] ='该停车场未绑定收费规则';
            return $msg;
        }
        $rule_id=$rule_info['id'];
        $project_id=$rule_info['charge_project_id'];
        $where_out = [];
        $where_out['car_number'] = $car_number;
        $where_out['park_id'] = $village_id;
        $where_out['park_sys_type'] = $park_sys_type;
        $where_out['is_out'] = 1;
        $park_info1 = $db_in_park->where($where_out)->order('id DESC')->find();
        if (!empty($park_info1)&&$park_info1['out_time']>$park_info['in_time']&&$park_info1['out_time']<time()){
            $park_info['in_time']=$park_info1['out_time'];
        }
        $where_charge = [];
        $where_charge['village_id'] = $village_id;
        //$where_charge['park_sys_type'] = $park_sys_type;
        $where_charge['status'] = 1;
        $where_charge['id'] = $rule_info['park_charge_id'];
        $park_charge = $db_house_village_park_charge->where($where_charge)->find();
        if (empty($park_charge)) {
            $msg['error'] = 1;
            $msg['msg'] ='该停车场未绑定收费规则';
            return $msg;
        }
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        $data_temp = [];
        $car_info = D('House_village_parking_car')->where(['province' => $province, 'car_number' => $car_no,'village_id'=>$village_id])->find();
        fdump_api(['car_number' => $car_number, 'car_info' => $car_info, 'car_no' => $car_no], 'park_pay_order_0411_1', 1);
        if ($car_number && $car_info) {
            try {
                $outRightPosition = $this->getCarRightOutPosition($car_info, $village_id, $garage_id, $car_number);
            } catch (\Exception $e) {
                $outRightPosition = [];
            }
            fdump_api(['car_number' => $car_number, 'outRightPosition' => $outRightPosition], 'park_pay_order_0411_1', 1);
            if (!empty($outRightPosition)) {
                if ($outRightPosition['start_time'] <= $nowTime && $outRightPosition['end_time'] >= $car_info['end_time']) {
                    $car_info['end_time'] = $outRightPosition['end_time'];
                    
                    if (isset($outRightPosition['temporary_end_time']) && $outRightPosition['temporary_end_time'] > 0) {
                        $car_info['temporary_end_time'] = $outRightPosition['temporary_end_time'];
                    }
                    
                }
                if ($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids']) && $outRightPosition['start_time'] > $nowTime) {
                    //车位时间还没开始
                    $car_info['end_time'] = 0;
                }
                if (isset($outRightPosition['garage_id']) && $outRightPosition['garage_id']) {
                    $data_temp['garage_id'] = $outRightPosition['garage_id'];
                }
            } else {
                //没有可用车位
                $bind_position_ids = array();
                if ($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])) {
                    //新的绑定车位 多车多位逻辑
                    $bind_position_ids = explode(',', $car_info['bind_position_ids']);
                }
                $car_info['end_time'] = 0;   //没有可用车位时
            }
        }
        if(isset($car_info['temporary_end_time']) && $car_info['temporary_end_time']>100 ){
            $park_time=$car_info['temporary_end_time']-$park_info['in_time'];
        }else if (!empty($car_info)&&$car_info['end_time']>1&&$park_info['in_time']<$car_info['end_time']){
            $park_time = time() - $car_info['end_time'];
        }else{
            $park_time = time() - $park_info['in_time'];
        }

        $whereTemp = [];
        $whereTemp['car_number'] = $car_number;
        $whereTemp['village_id'] = $village_id;
        $parkTemp = $db_house_village_park_temp->where($whereTemp)->order('pay_time DESC,id DESC')->find();
        fdump_api(['parkTemp' => $parkTemp, 'in_time' => $park_info['in_time'], 'park_time' => $park_time], 'park_pay_order_0411_1', 1);
        if ($parkTemp && isset($parkTemp['pay_time']) && $parkTemp['pay_time']>$park_info['in_time']) {
            $parkTemp_money=$parkTemp['price'];
            if (empty($free_park_time)){
                if (time() - $parkTemp['pay_time']<900){
                    $park_time=0;
                }
            }else{
                if (time() - $parkTemp['pay_time']<($free_park_time*60)){
                    $park_time=0;
                }
            }
        }else{
            $parkTemp_money=0;
        }
        fdump_api(['park_time' => $park_time], 'park_pay_order_0411_1', 1);
        $in_record_id = isset($parkTemp['in_record_id']) && $parkTemp['in_record_id'] ? intval($parkTemp['in_record_id']) : 0;
        $out_record_id = isset($parkTemp['out_record_id']) && $parkTemp['out_record_id'] ? intval($parkTemp['out_record_id']) : 0;
        $park_charge_info=array();
        if($park_charge['charge_type']==1){
            $park_charge_info = unserialize($park_charge['charge_set']);
            $last_ages = array_column($park_charge_info,'time');
            array_multisort($last_ages ,SORT_DESC,$park_charge_info);
        }elseif ($park_charge['charge_type'] == 4) {
            //24小时逐时收费制
            $park_charge_info = unserialize($park_charge['charge_set']);
            if (empty($park_charge_info)) {
                $msg['error'] = 1;
                $msg['msg'] ='收费类型的费用设置数据错误';
                return $msg;
            }
        }
        $free_time=$park_charge['free_time'];
        $total_money=0;
        $park_money=0;
        $park_time_total = $park_time;
        fdump_api([$park_charge, $park_charge_info, $park_time], 'park_pay_order_0411_1', 1);
        $coupon_info = array();
        $deduction_amount = 0;
        $reduction_time = 0;
        if ($park_time > 0) {
            /***
             *新按次收费
             * 1连续多少小时只收取一次费(在原来charge_type是3 上面时间不一定是24了) 2按次收费（和 charge_type是2一样） 3当天只收取一次费(和 charge_type是3一样)
             */
            $charge_type5_set = isset($park_charge['charge_type5_set']) ? $park_charge['charge_type5_set'] : 0;
            $parkTime = ceil($park_time / 60);
            if ($parkTime <= $park_charge['free_time']) {
                $pay_money = 0;
                $reduction_time = $parkTime;
            } else {
                if (isset($park_charge['free_time_no_count']) && $park_charge['free_time_no_count'] == 1 && $park_charge['free_time'] > 0) {
                    //1从免费时间后开始计算费用 
                    $park_time = $park_time - ($park_charge['free_time'] * 60);
                    $park_time = $park_time > 0 ? $park_time : 0;
                    $reduction_time = $park_charge['free_time'] * 60;
                }
                if ($park_count == 1 && !empty($park_charge['first_free_time']) && $parkTime <= $park_charge['first_free_time']) {
                    $pay_money = $park_charge['first_charge_money'];
                } else {
                    if ($park_charge['charge_type'] == 1) {
                        if (!empty($park_charge_info)) {
                            $parkTime1 = ceil($park_time / 3600);
                            if ($park_charge['max_charge_money'] > 0 && $parkTime1 > 24) {
                                $level = intval($park_time / 86400);
                                $park_money = $level * $park_charge['max_charge_money'];
                                $park_time = $park_time - $level * 86400;
                                $parkTime1 = $parkTime1 - $level * 24;
                            }
                            fdump_api([$parkTime1, $park_money, $park_time], 'park_pay_order_0411_1', 1);
                            if ($park_charge_info[0]['time'] < $parkTime1) {
                                $pay_money = $park_charge_info[0]['money'];
                                $parkTime2 = $park_time - $park_charge_info[0]['time'] * 3600;
                                if ($park_charge['max_charge_money'] > 0) {
                                    /*if($parkTime1>24){
                                        $level=intval($park_time / 86400);
                                        $park_money=$level*$park_charge['max_charge_money'];
                                        $parkTime2=$park_time-$level*86400;
                                    }*/
                                    $parkTime3 = ceil($parkTime2 / ($park_charge['charge_time'] * 60));
                                    $park_money1 = $parkTime3 * $park_charge['charge_money'];
                                    if ($parkTime1 > 24 && ($pay_money + $park_money1) > $park_charge['max_charge_money']) {
                                        $park_money += $park_charge['max_charge_money'];
                                    } elseif ($parkTime1 <= 24 && ($pay_money + $park_money1) > $park_charge['max_charge_money']) {
                                        $pay_money = $park_charge['max_charge_money'];
                                    } else {
                                        $park_money += $park_money1;
                                    }
                                    fdump_api([$pay_money, $parkTime2, $park_charge['max_charge_money'], $parkTime3, $park_charge['charge_time'], $park_money1, $park_money], 'park_pay_order_0411_1', 1);
                                } else {
                                    $parkTime3 = ceil($parkTime2 / ($park_charge['charge_time'] * 60));
                                    $park_money1 = $parkTime3 * $park_charge['charge_money'];
                                    $park_money += $park_money1;
                                    //  print_r([$parkTime3,$parkTime2,$park_charge['charge_time'],$park_charge['charge_money'],$park_money1]);die;
                                    fdump_api([$parkTime3, $parkTime2, $park_charge['charge_time'], $park_charge['charge_money'], $park_money1], 'park_pay_order_0411_1', 1);

                                }
                            } else {
                                foreach ($park_charge_info as $k => $v) {
                                    if ($v['time'] == $parkTime1) {
                                        $pay_money = $v['money'];
                                        break;
                                    } elseif ($v['time'] < $parkTime1) {
                                        $pay_money = $park_charge_info[$k - 1]['money'];
                                        break;
                                    } elseif (!isset($park_charge_info[$k + 1]) && $v['time'] > $parkTime1) {
                                        $pay_money = $park_charge_info[$k]['money'];
                                        break;
                                    }
                                }
                            }
                        }
                    } elseif ($park_charge['charge_type'] == 4) {
                        $parkTimeHour = ceil($park_time / 3600); //算小时
                        $tmp_pay_money = 0;
                        do {
                            foreach ($park_charge_info as $kk => $vv) {
                                $new_key = $kk + 1;
                                if ($vv['hour'] < 24 && ($parkTimeHour >= $vv['hour']) && ($parkTimeHour < $park_charge_info[$new_key]['hour'])) {
                                    $tmp_pay_money += $vv['fee'];
                                } else if ($vv['hour'] == 1 && $parkTimeHour <= $vv['hour']) {
                                    $tmp_pay_money += $vv['fee'];
                                } else if (($vv['hour'] == 24) && ($parkTimeHour >= $vv['hour'])) {
                                    $tmp_pay_money += $vv['fee'];
                                }
                            }
                            $parkTimeHour = $parkTimeHour - 24;
                        } while ($parkTimeHour > 0);
                        $pay_money = round($tmp_pay_money, 2);
                    } elseif ($park_charge['charge_type'] == 2 || ($park_charge['charge_type'] == 5 && $charge_type5_set == 2)) {
                        $parkTime_charge2 = ceil($park_time / 86400);
                        $pay_money = $park_charge['max_charge_money'] * $parkTime_charge2;
                    } else {
                        $parkTime_charge3 = $park_info['in_time'] + $park_time;
                        $oneDaySecond = 86400;  //24小时
                        if ($park_charge['charge_type'] == 5 && $charge_type5_set == 1 && $park_charge['hour_once_fee'] > 0) {
                            $oneDaySecond = $park_charge['hour_once_fee'] * 3600;  //自定义的
                        }
                        $time_charge = strtotime(date('Y_m-d 00:00:00', $park_info['in_time'])) + $oneDaySecond;
                        if ($time_charge < $parkTime_charge3) {
                            $parkTime_charge2 = ceil($park_time / $oneDaySecond);
                            $pay_money = $park_charge['max_charge_money'] * $parkTime_charge2;
                        } else {
                            $pay_money = $park_charge['max_charge_money'];
                        }
                    }
                    $pay_money = round_number($pay_money + $park_money - $parkTemp_money, 2);
                }
            }
            if ($pay_money > 0) {
                $this->checkCouponUseStatus($village_id, $car_number, $park_sys_type, $uid);
                $where_coupon = [];
                $where_coupon['village_id'] = $village_id;
                $where_coupon['car_number'] = $car_number;
                $where_coupon['user_id'] = $uid;
                $where_coupon['park_sys_type'] = $park_sys_type;
                $where_coupon['is_use'] = 0;
                $coupon_info = D('House_village_park_coupon')->where($where_coupon)->find();
                fdump_api([$car_number, 'where_coupon' => $where_coupon, 'coupon_info' => $coupon_info], 'park_pay_order_0411_1', 1);
                if (isset($coupon_info['money']) && floatval($coupon_info['money']) > 0) {
                    $pay_money = $pay_money - $coupon_info['money'];
                    if ($pay_money <= 0) {
                        $pay_money = 0;
                        $deduction_amount = $pay_money;
                    } else {
                        $deduction_amount = $coupon_info['money'];
                    }
                    D('House_village_park_coupon')->where($where_coupon)->data(['is_use' => 1, 'use_time' => $nowTime])->save();
                    $db_house_village_car_flow_coupon = M('House_village_car_flow_coupon');
                    $whereUniqueArr = [
                        'car_number' => $car_number, 'uid' => $uid, 'village_id' => $village_id,
                        'park_sys_type' => $park_sys_type, 'is_use' => 0,
                    ];
                    $where_unique_id = md5(json_encode($whereUniqueArr, JSON_UNESCAPED_UNICODE));
                    $whereFlowTemp = [
                        'unique_id' => $where_unique_id,
                    ];
                    $uniqueArr = [
                        'car_number' => $car_number, 'uid' => $uid, 'village_id' => $village_id,
                        'park_sys_type' => $park_sys_type, 'is_use' => 1,
                    ];
                    $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
                    $flowTempArr = array('is_use' => 1, 'use_time' => $nowTime, 'unique_id' => $unique_id);
                    $db_house_village_car_flow_coupon->where($whereFlowTemp)->data($flowTempArr)->save();
                }
            }
        } else {
            $msg['error'] = 1;
            $msg['msg'] ='没有查到停车时长，请手动输入车牌查询！';
            return $msg;
        }
        //  print_r([$parkTime3,$parkTime2,$park_charge['charge_time'],$park_charge['charge_money'],$park_money1,$park_money,$pay_money]);die;
        fdump_api([$parkTime3,$parkTime2,$park_charge['charge_time'],$park_charge['charge_money'],$park_money1,$park_money,$pay_money],'park_pay_order_0411_1',1);

        $whereTempMoney = [];
        $whereTempMoney['car_number'] = $car_number;
        $whereTempMoney['is_paid'] = array('neq', 1);
        $whereTempMoney['village_id'] = $village_id;
        $parkTempMoney = $db_house_village_park_temp->where($whereTempMoney)->order('id DESC')->find();
        if (!empty($parkTempMoney) && $parkTempMoney['add_time'] > (time() - 180) && $pay_money > $parkTempMoney['price']) {
            $pay_money = $parkTempMoney['price'];
            $park_time_total = $parkTempMoney['duration'] * 60;
            !$in_record_id && $in_record_id = isset($parkTempMoney['in_record_id']) && $parkTempMoney['in_record_id'] ? intval($parkTempMoney['in_record_id']) : 0;
            !$out_record_id && $out_record_id = isset($parkTempMoney['out_record_id']) && $parkTempMoney['out_record_id'] ? intval($parkTempMoney['out_record_id']) : 0;
        }
        $duration = ceil($park_time_total / 60);
        $coupon_id = isset($coupon_info['id']) ? intval($coupon_info['id']) : 0;
        $in_time = isset($park_info['in_time']) ? intval($park_info['in_time']) : 0;
        $data_temp['village_id'] = $village_id;
        $data_temp['add_time'] = time();
        $data_temp['free_out_time'] = $free_time;
        $data_temp['duration'] = $duration;
        $data_temp['derate_money'] = $deduction_amount;
        $data_temp['derate_duration'] = 0;
        $data_temp['order_id'] = $park_info['order_id'];
        $data_temp['query_order_no'] = build_real_orderid($uid);
        $data_temp['errmsg'] = $msg['msg'];
        $data_temp['price'] = $pay_money;
        $data_temp['total'] = $total_money;
        $data_temp['in_time'] = $in_time;
        $data_temp['out_channel_id'] = $channel_id;
        $data_temp['park_sys_type'] = $park_sys_type;
        $data_temp['car_number'] = $car_number;
        $data_temp['is_pay_scene'] = intval($spread) <= 1 ? 0 : intval($spread) - 1;
        $data_temp['coupon_id'] = $coupon_id;
        if ($pay_money == 0) {
            $data_temp['is_paid'] = 1;
            $data_temp['pay_time'] = time();
        }
        $data_temp['in_record_id'] = $in_record_id;
        $data_temp['out_record_id'] = $out_record_id;
        $data_temp['garage_id']=$garage_id;
        $data_temp['rule_id']=$rule_id;
        $data_temp['project_id']=$project_id;
        fdump_api([$data_temp, $spread], 'park_pay_order_0411_1', 1);
        $query_order_no = M('House_village_parking_temp')->data($data_temp)->add();
        if ($query_order_no) {
            $msg['query_order_no'] = $query_order_no;
        }
        $is_paid = 0;
        $hasCoupon = false;
        $is_pay_scene = $spread - 1;
        $uniqueArr = [
            'car_number' => $car_number, 'village_id' => $village_id, 'is_paid' => $is_paid, 'is_pay_scene' => $is_pay_scene,
            'out_channel_id' => $channel_id, 'park_sys_type' => $park_sys_type, 'hasCoupon' => $hasCoupon,
        ];
        $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
        $flowTempData = [
            'parking_temp_id' => $query_order_no, 'village_id' => $village_id, 'car_number' => $car_number,
            'is_paid' => $is_paid, 'park_sys_type' => $park_sys_type, 'coupon_id' => $coupon_id, 'out_channel_id' => $channel_id,
            'is_pay_scene' => $is_pay_scene, 'order_id' => $data_temp['order_id'], 'price' => $data_temp['price'], 'query_time' => $nowTime,
            'in_time' => $in_time, 'unique_id' => $unique_id, 'out_record_id' => '', 'is_out' => 0, 'in_record_id' => 0,
            'reduction_time' => $reduction_time, 'deduction_amount' => $deduction_amount, 'free_out_time' => $free_time, 
            'duration' => $duration, 'park_time' => $park_time_total
        ];
        $flowTempData['park_charge'] = json_encode($park_charge,JSON_UNESCAPED_UNICODE);
        if ($flowTempData['price'] == 0) {
            $flowTempData['is_paid'] = 1;
            $flowTempData['pay_time'] = $nowTime;
        }
        $in_record_id && $flowTempData['in_record_id'] = $in_record_id;
        $out_record_id && $flowTempData['out_record_id'] = $out_record_id;
        
        $whereUnique = [
            'unique_id' => $unique_id,
        ];
        $flowTempInfo = $db_house_village_car_flow_temp->where($whereUnique)->find();
        if (isset($flowTempInfo['id']) && $flowTempInfo['id']) {
            $flowTempData['update_time'] = time();
            $db_house_village_car_flow_temp->where($whereUnique)->data($flowTempData)->save();
        } else {
            $flowTempData['add_time'] = time();
            $flow_temp_id = $db_house_village_car_flow_temp->add($flowTempData);
        }
        return $msg;
    }

    public function checkCouponUseStatus($village_id=0,$car_number='',$park_sys_type='',$user_id=0){
        $db_house_village_park_temp = M('House_village_parking_temp');
        $whereTemp = array();
        $whereTemp['village_id'] =$village_id;
        $whereTemp['car_number'] =$car_number;
        $whereTemp['is_paid'] =  0;
        if($park_sys_type){
            $whereTemp['park_sys_type'] = $park_sys_type;
        }
        $nowtime=time();
        $tmptime=$nowtime-36000;
        $whereTemp['coupon_id'] = array('gt',0);
        $whereTemp['_string'] = ' (add_time>'.$tmptime.' AND add_time<'.$nowtime.')';
        $parkTemp = $db_house_village_park_temp->where($whereTemp)->field('id,coupon_id,duration,derate_money,price,total')->order('id DESC')->find();
        fdump_api([$car_number,'whereTemp'=>$whereTemp,'parkTemp'=>$parkTemp],'park_pay_order_0411_1',1);
        if($parkTemp && isset($parkTemp['coupon_id'])){
            $whereArr=array('vpc.village_id'=>$village_id,'vpc.id'=>$parkTemp['coupon_id'],'sc.village_id'=>$village_id);
            $oneCoupon= M('Park_shop_coupons')->join('as vpc LEFT JOIN '.C('DB_PREFIX')."park_shop_coupons as sc ON vpc.coupons_id=sc.id")->where($whereArr)->field('sc.*')->find();
            $is_coupon_can_use=0;
            if($oneCoupon && $oneCoupon['cid']){
                $whereCoupon=array('village_id'=>$village_id,'c_id'=>$oneCoupon['cid']);
                $park_coupons= M('Park_coupons')->where($whereCoupon)->field('*')->find();
                if($park_coupons && $park_coupons['status']==0){
                    $is_coupon_can_use=1;
                }
            }
            $db_house_village_park_coupon = D('House_village_park_coupon');
            if($is_coupon_can_use<1){
                return true;
            }
            $where_coupon = [];
            $where_coupon['village_id'] = $village_id;
            $where_coupon['car_number'] = $car_number;
            if ($user_id) {
                $where_coupon['user_id'] = $user_id;
            }
            $where_coupon['id'] = $parkTemp['coupon_id'];
            $parkCouponInfo = $db_house_village_park_coupon->where($where_coupon)->field('id')->find();
            $parking_temp_id = isset($parkCouponInfo['id']) ? intval($parkCouponInfo['id']) : 0;
            $saveArr=array('is_use'=>0,'use_time'=>0,'use_txt'=>'');
            $db_house_village_park_coupon->where($where_coupon)->data($saveArr)->save();
            
            $db_house_village_car_flow_coupon = M('House_village_car_flow_coupon');
            $whereFlowTemp = [
                'village_id' => $village_id, 'car_number' => $car_number,
            ];
            if ($user_id) {
                $whereFlowTemp['uid'] = $user_id;
            }
            if ($parking_temp_id != $parkTemp['coupon_id']) {
                $whereFlowTemp['park_coupon_id'] = ['in', [$parking_temp_id, $parkTemp['coupon_id']]];
            } else {
                $whereFlowTemp['park_coupon_id'] = $parkTemp['coupon_id'];
            }
            $flowTempList = $db_house_village_car_flow_coupon->where($whereFlowTemp)->select();
            foreach ($flowTempList as $item) {
                $car_number = isset($item['car_number']) ? trim($item['car_number']) : '';
                $uid = isset($item['uid']) ? intval($item['uid']) : 0;
                $village_id = isset($item['village_id']) ? intval($item['village_id']) : 0;
                $park_sys_type = isset($item['park_sys_type']) ? trim($item['park_sys_type']) : '';
                $id = isset($item['id']) ? intval($item['id']) : 0;
                
                $uniqueArr = [
                    'car_number' => $car_number, 'uid' => $uid, 'village_id' => $village_id,
                    'park_sys_type' => $park_sys_type, 'is_use' => 1,
                ];
                $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
                $flowTempArr = array('is_use' => 0, 'use_time' => 0, 'unique_id' => $unique_id);
                $db_house_village_car_flow_coupon->where(['id' => $id])->data($flowTempArr)->save();
            }
            return $parkTemp['coupon_id'];
        }
        return true;
    }
    
    public function queryOrderPriceA11($village_id, $car_number, $uid, $spread, $channel_id = '',$car_number_type='',$park_config=array())
    {
        fdump_api([$village_id, $car_number, $uid, $spread], 'park_pay_order_0411_1', 1);
        $msg = [
            'error' => 0,
            'msg' => ''
        ];
        if (empty($car_number)){
            $where_visitor=[
                'village_id'=>$village_id,
                'uid'=>$uid,
                'car_id'=>['like','%临%'],
            ];
            $visitor_info=D('house_village_visitor')->where($where_visitor)->order('id DESC')->find();
            if (empty($visitor_info)){
                $msg['error'] = 1;
                $msg['msg'] = '没有停车纪录';
                return $msg;
            }
            $car_number=$visitor_info['car_id'];
        }
        $park_sys_type = 'A11';
        $db_in_park = D('In_park');
        $db_house_village_park_temp = M('House_village_parking_temp');
        $db_house_village_park_charge = D('House_village_park_charge');
        if(empty($park_config)){
            $parkConfigWhere=array('village_id'=>$village_id,'park_sys_type'=>$park_sys_type);
            $park_config=D('House_village_park_garage_config')->getVillageParkConfigByWhere($parkConfigWhere);
        }
        $garage_id =0;
        if($park_config && isset($park_config['garage_id'])){
            $garage_id =$park_config['garage_id'];
        }
        $where = [];
        $where['car_number'] = $car_number;
        $where['park_id'] = $village_id;
        $where['park_sys_type'] = $park_sys_type;
        $where['is_paid'] = 0;
        $where['is_out'] = 0;
        $where['del_time'] = 0;
        $park_info = $db_in_park->where($where)->order('id DESC')->find();
        fdump_api(['car_number' => $car_number, 'park_info' => $park_info, 'where' => $where], 'park_pay_order_0411_1', 1);
        $db_house_village_car_flow_temp = M('House_village_car_flow_temp');
        $nowTime = time();
        if (empty($park_info)) {
            if($park_config['not_inPark_money']>0){
                /***无入场记录收费**/
                $data_temp=[];
                $data_temp['village_id']=$village_id;
                $data_temp['add_time']=time();
                $data_temp['free_out_time']=0;
                $data_temp['duration']=0;
                $data_temp['derate_money']=0;
                $data_temp['derate_duration']=0;
                $order_id='331'.date('YmdHis').rand(100,999);
                $data_temp['order_id']=$order_id;
                $data_temp['query_order_no']=build_real_orderid($uid);
                $data_temp['errmsg']=$msg['msg'];
                $data_temp['price']=$park_config['not_inPark_money'];
                $data_temp['total']=$park_config['not_inPark_money'];
                $data_temp['in_time']=0;
                $data_temp['car_number']=$car_number;
                $data_temp['out_channel_id']=$channel_id;
                $data_temp['park_sys_type']=$park_sys_type;
                $data_temp['is_pay_scene']=$spread-1;
                $data_temp['coupon_id']=0;
                $data_temp['garage_id']=$garage_id;
                fdump_api([__LINE__,$data_temp,$spread],'park_pay_order_0411_1',1);
                $query_order_no = M('House_village_parking_temp')->data($data_temp)->add();
                if ($query_order_no) {
                    $msg['query_order_no'] = $query_order_no;
                }
                $is_paid = 0;
                $hasCoupon = false;
                $is_pay_scene = $spread - 1;
                $uniqueArr = [
                    'car_number' => $car_number, 'village_id' => $village_id, 'is_paid' => $is_paid, 'is_pay_scene' => $is_pay_scene,
                    'out_channel_id' => $channel_id, 'park_sys_type' => $park_sys_type, 'hasCoupon' => $hasCoupon,
                ];
                $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
                $flowTempData = [
                    'parking_temp_id' => $query_order_no, 'village_id' => $village_id, 'car_number' => $car_number,
                    'is_paid' => $is_paid, 'park_sys_type' => $park_sys_type, 'coupon_id' => 0, 'out_channel_id' => $channel_id,
                    'is_pay_scene' => $is_pay_scene, 'order_id' => $order_id, 'price' => $data_temp['total'], 'query_time' => $nowTime,
                    'in_time' => 0, 'unique_id' => $unique_id, 'out_record_id' => '', 'is_out' => 0, 'in_record_id' => 0,
                    'reduction_time' => 0, 'deduction_amount' => 0, 'free_out_time' => 0, 'duration' => 0,
                ];
                if ($flowTempData['price'] == 0) {
                    $flowTempData['is_paid'] = 1;
                    $flowTempData['pay_time'] = $nowTime;
                }
                $whereUnique = [
                    'unique_id' => $unique_id,
                ];
                $flowTempInfo = $db_house_village_car_flow_temp->where($whereUnique)->find();
                if (isset($flowTempInfo['id']) && $flowTempInfo['id']) {
                    $flowTempData['update_time'] = time();
                    $db_house_village_car_flow_temp->where($whereUnique)->data($flowTempData)->save();
                } else {
                    $flowTempData['add_time'] = time();
                    $flow_temp_id = $db_house_village_car_flow_temp->add($flowTempData);
                }
                return $msg;
            }else{
                /***无入场记录收费**/
                $data_temp=[];
                $data_temp['village_id']=$village_id;
                $data_temp['add_time']=time();
                $data_temp['free_out_time']=0;
                $data_temp['duration']=0;
                $data_temp['derate_money']=0;
                $data_temp['derate_duration']=0;
                $order_id='332'.date('YmdHis').rand(100,999);
                $data_temp['order_id']=$order_id;
                $data_temp['query_order_no']=build_real_orderid($uid);
                $data_temp['errmsg']=$msg['msg'];
                $data_temp['price']=0;
                $data_temp['total']=0;
                $data_temp['in_time']=0;
                $data_temp['car_number']=$car_number;
                $data_temp['out_channel_id']=$channel_id;
                $data_temp['park_sys_type']=$park_sys_type;
                $data_temp['is_pay_scene']=$spread-1;
                $data_temp['coupon_id']=0;
                $data_temp['garage_id']=$garage_id;
                fdump_api([__LINE__,$data_temp,$spread],'park_pay_order_0411_1',1);
                $query_order_no = M('House_village_parking_temp')->data($data_temp)->add();
                if ($query_order_no) {
                    $msg['query_order_no'] = $query_order_no;
                }
                $is_paid = 0;
                $hasCoupon = false;
                $is_pay_scene = $spread - 1;
                $uniqueArr = [
                    'car_number' => $car_number, 'village_id' => $village_id, 'is_paid' => $is_paid, 'is_pay_scene' => $is_pay_scene,
                    'out_channel_id' => $channel_id, 'park_sys_type' => $park_sys_type, 'hasCoupon' => $hasCoupon,
                ];
                $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
                $flowTempData = [
                    'parking_temp_id' => $query_order_no, 'village_id' => $village_id, 'car_number' => $car_number,
                    'is_paid' => $is_paid, 'park_sys_type' => $park_sys_type, 'coupon_id' => 0, 'out_channel_id' => $channel_id,
                    'is_pay_scene' => $is_pay_scene, 'order_id' => $order_id, 'price' => $data_temp['total'], 'query_time' => $nowTime,
                    'in_time' => 0, 'unique_id' => $unique_id, 'out_record_id' => '', 'is_out' => 0, 'in_record_id' => 0,
                    'reduction_time' => 0, 'deduction_amount' => 0, 'free_out_time' => 0, 'duration' => 0,
                ];
                if ($flowTempData['price'] == 0) {
                    $flowTempData['is_paid'] = 1;
                    $flowTempData['pay_time'] = $nowTime;
                }
                $whereUnique = [
                    'unique_id' => $unique_id,
                ];
                $flowTempInfo = $db_house_village_car_flow_temp->where($whereUnique)->find();
                if (isset($flowTempInfo['id']) && $flowTempInfo['id']) {
                    $flowTempData['update_time'] = time();
                    $db_house_village_car_flow_temp->where($whereUnique)->data($flowTempData)->save();
                } else {
                    $flowTempData['add_time'] = time();
                    $flow_temp_id = $db_house_village_car_flow_temp->add($flowTempData);
                }
                return $msg;
            }
        }
        $deduction_amount = 0;
        $reduction_time = 0;
        $park_count = $db_in_park->where(['car_number' => $car_number, 'park_id' => $village_id, 'del_time' => 0])->count();
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        $car_info = M('House_village_parking_car')->where(['province' => $province, 'car_number' => $car_no,'village_id'=>$village_id])->find();
        if (empty($car_info)){
            $db_park_plateresult_log = M('Park_plateresult_log');
            $whereOutLog = [
                'car_number' => $car_number,
                'village_id' => $village_id,
                'park_sys_type' => $park_sys_type,
                'park_type' => 2,
            ];
            if ($channel_id) {
                $whereOutLog['out_channel_id'] = $channel_id;
            }
            $car_number_type = $db_park_plateresult_log->where($whereOutLog)->order('add_time DESC')->getField('car_number_type');
            fdump_api(['0查询车临时记录' => $car_number, 'whereOutLog' => $whereOutLog, 'car_number_type' => $car_number_type], 'park_pay_order_0411_1', 1);
            if ($car_number_type) {
                $db_house_village_park_charge_cartype = M('House_village_park_charge_cartype');
                $where_charge_cartype = [
                    'village_id' => $village_id,
                    'car_number_type' => $car_number_type,
                    'status'=>1,
                    'park_sys_type' => $park_sys_type
                ];
                $car_type = $db_house_village_park_charge_cartype->where($where_charge_cartype)->getField('car_type');
                fdump_api(['1查询车临时记录' => $car_number, 'where_charge_cartype' => $where_charge_cartype, 'car_type' => $car_type], 'park_pay_order_0411_1', 1);
            }
            if (!isset($car_type) || !$car_type) {
                $where_park_temp = [
                    'car_number'    => $car_number,
                    'village_id'    => $village_id,
                    'park_sys_type' => $park_sys_type,
                    'is_paid'       => 0,
                ];
                if ($channel_id) {
                    $where_park_temp['out_channel_id'] = $channel_id;
                }
                $car_type = $db_house_village_park_temp->where($where_park_temp)->order('add_time DESC')->getField('car_type');
                fdump_api(['2查询车临时记录' => $car_number, 'where_park_temp' => $where_park_temp, 'car_type' => $car_type], 'park_pay_order_0411_1', 1);
            }
            if (!isset($car_type) || !$car_type) {
                $car_type = self::Temp_A;
            }
        }else{
            if (!in_array($car_info['parking_car_type'],$this->A11_Temp)&&!in_array($car_info['parking_car_type'],$this->A11_Stored)){
                $car_types=M('House_village_park_charge_cartype')->where(['village_id'=>$village_id,'car_number_type'=>$car_number_type,'status'=>1,'park_sys_type'=>'A11'])->getField('car_type');
                if (!empty($car_types)){
                    $car_type=$car_types['car_type'];
                }else{
                    $car_type=self::Temp_A;
                }
            }else{
                $car_type=$car_info['parking_car_type'];
            }
        }
        $whereRules = ['village_id'=>$village_id,'car_type'=>$car_type,'status'=>1,'park_sys_type'=>$park_sys_type];
        $rule_ids=M('House_village_park_charge_cartype')->where($whereRules)->getField('rule_id',true);
        fdump_api(['查询车临时记录' => $car_number, 'car_type' => $car_type, 'rule_ids' => $rule_ids, 'whereRules' => $whereRules], 'park_pay_order_0411_1', 1);
        if (empty($rule_ids)){
            throw new \Exception('当前卡类未绑定收费标准');
        }
        /*$where_rule=[
             ['id','in',$rule_ids],
             ['village_id','=',$village_id],
             ['status','=',1],
             ['charge_valid_time','<=',time()],
             ['fees_type','=',3],
         ];*/
        $where_rule=[];
        $where_rule['village_id'] = $village_id;
        $where_rule['status'] = 1;
        $where_rule['fees_type'] = 3;
        $where_rule['charge_valid_time'] = array('elt', time());
        $where_rule['id'] = array('in',$rule_ids);
        $rule_info=M('House_new_charge_rule')->where($where_rule)->order('charge_valid_time DESC')->field('*')->find();
        fdump_api(['查询车临时记录' => $car_number, 'where_rule' => $where_rule, 'rule_info' => $rule_info], 'park_pay_order_0411_1', 1);
        if (empty($rule_info)) {
            throw new \Exception('该停车场未绑定收费规则');
        }
        $rule_id=$rule_info['id'];
        $project_id=$rule_info['charge_project_id'];
        //查询当前车库信息
        $garage_info = [];
        $passage_info = M('Park_passage')->where(['id' => $park_info['in_channel_id']])->find();
        if ($passage_info && isset($passage_info['garage_id']) && $passage_info['garage_id'] > 0) {
            $garage_id = $passage_info['garage_id'];
        } else if ($passage_info && isset($passage_info['passage_area']) && !empty($passage_info['passage_area']) && $passage_info['area_type'] == 2) {
            $garage_id = intval($passage_info['passage_area']);
        }
        $garage_info = M('House_village_parking_garage')->where(['garage_id' => $garage_id])->find();
        
        //查询当前车库是否是车辆绑定的车库
        if (!empty($garage_info)&&!empty($car_info['garage_id'])&& $garage_id==$car_info['garage_id']){
            if (!empty($car_info)&&$car_info['end_time']>1&&$park_info['in_time']<$car_info['end_time']){
                $park_time = time() - $car_info['end_time'];
            }else{
                $park_time = time() - $park_info['in_time'];
            }
        }else{
            $park_time = time() - $park_info['in_time'];
            $where_out = [];
            $where_out['car_number'] = $car_number;
            $where_out['park_id'] = $village_id;
            $where_out['park_sys_type'] = $park_sys_type;
            $where_out['is_out'] = 1;
            $where_out['del_time'] = 0;
            $park_info1 = $db_in_park->where($where_out)->order('id DESC')->find();
            if (!empty($park_info1)&&$park_info1['out_time']>$park_info['in_time']&&$park_info1['out_time']<time()){
                $passage_info1=M('Park_passage')->where(['id'=>$park_info1['in_channel_id']])->find();
                if (!empty($garage_info1)&&!empty($car_info['garage_id']) && $garage_id==$car_info['garage_id']){
                    $park_time=time() - $park_info['in_time']-$park_info1['park_time'];
                }
            }
        }
        $where_charge = [];
        $where_charge['village_id'] = $village_id;
        //$where_charge['park_sys_type'] = $park_sys_type;
        $where_charge['status'] = 1;
        $where_charge['id'] = $rule_info['park_charge_id'];
        $park_charge = $db_house_village_park_charge->where($where_charge)->find();
        if (empty($park_charge)) {
            $msg['error'] = 1;
            $msg['msg'] ='该停车场未绑定收费规则';
            return $msg;
        }
        $whereTemp = [];
        $whereTemp['car_number'] =  $car_number;
        $whereTemp['village_id'] =  $village_id;
        $parkTemp = $db_house_village_park_temp->where($whereTemp)->order('pay_time DESC,id DESC')->find();
        $in_record_id = isset($parkTemp['in_record_id']) && $parkTemp['in_record_id'] ? intval($parkTemp['in_record_id']) : 0;
        $out_record_id = isset($parkTemp['out_record_id']) && $parkTemp['out_record_id'] ? intval($parkTemp['out_record_id']) : 0;
        
        fdump_api([$parkTemp,$park_info['in_time'],$park_time],'park_pay_order_0411_1',1);
        if ($parkTemp && isset($parkTemp['pay_time']) && $parkTemp['pay_time']>$park_info['in_time']) {
            $parkTemp_money=$parkTemp['price'];
            if (empty($park_config['free_park_time'])){
                if (time() - $parkTemp['pay_time']<900){
                    $park_time=0;
                }
            }else{
                if (time() - $parkTemp['pay_time']<($park_config['free_park_time']*60)){
                    $reduction_time = $park_config['free_park_time'] * 60;
                    $park_time=0;
                }
            }
        }else{
            $parkTemp_money=0;
        }
        $park_charge_info=array();
        if($park_charge['charge_type']==1){
            $park_charge_info = unserialize($park_charge['charge_set']);
            $last_ages = array_column($park_charge_info,'time');
            array_multisort($last_ages ,SORT_DESC,$park_charge_info);
        }elseif ($park_charge['charge_type'] == 4) {
            //24小时逐时收费制
            $park_charge_info = unserialize($park_charge['charge_set']);
            if (empty($park_charge_info)) {
                $msg['error'] = 1;
                $msg['msg'] ='收费类型的费用设置数据错误';
                return $msg;
            }
        }
        fdump_api([$park_charge_info, $park_time], 'park_pay_order_0411_1', 1);
        $free_time = $park_charge['free_time'];
        $total_money = 0;
        $park_money = 0;
        $park_time_total = $park_time;
        // print_r([$park_charge,$park_charge_info]);die;
        fdump_api([$park_charge, $park_charge_info, $park_time], 'park_pay_order_0411_1', 1);
        if ($park_time > 0) {
            /***
             *新按次收费
             * 1连续多少小时只收取一次费(在原来charge_type是3 上面时间不一定是24了) 2按次收费（和 charge_type是2一样） 3当天只收取一次费(和 charge_type是3一样)
             */
            $charge_type5_set = isset($park_charge['charge_type5_set']) ? $park_charge['charge_type5_set'] : 0;
            $parkTime = ceil($park_time / 60);
            if ($parkTime <= $park_charge['free_time']) {
                $pay_money = 0;
                $reduction_time = $parkTime;
            } else {
                if (isset($park_charge['free_time_no_count']) && $park_charge['free_time_no_count'] == 1 && $park_charge['free_time'] > 0) {
                    //1从免费时间后开始计算费用 
                    $park_time = $park_time - ($park_charge['free_time'] * 60);
                    $park_time = $park_time > 0 ? $park_time : 0;
                    $reduction_time = $park_charge['free_time'] * 60;
                }
                if ($park_count == 1 && !empty($park_charge['first_free_time']) && $parkTime <= $park_charge['first_free_time']) {
                    $pay_money = $park_charge['first_charge_money'];
                } else {
                    if ($park_charge['charge_type'] == 1) {
                        if (!empty($park_charge_info)) {
                            $parkTime1 = ceil($park_time / 3600);
                            if ($park_charge['max_charge_money'] > 0 && $parkTime1 > 24) {
                                $level = intval($park_time / 86400);
                                $park_money = $level * $park_charge['max_charge_money'];
                                $park_time = $park_time - $level * 86400;
                                $parkTime1 = $parkTime1 - $level * 24;
                            }
                            fdump_api([$parkTime1, $park_money, $park_time], 'park_pay_order_0411_1', 1);
                            if ($park_charge_info[0]['time'] < $parkTime1) {
                                $pay_money = $park_charge_info[0]['money'];
                                $parkTime2 = $park_time - $park_charge_info[0]['time'] * 3600;
                                if ($park_charge['max_charge_money'] > 0) {
                                    /*if($parkTime1>24){
                                        $level=intval($park_time / 86400);
                                        $park_money=$level*$park_charge['max_charge_money'];
                                        $parkTime2=$park_time-$level*86400;
                                    }*/
                                    $parkTime3 = ceil($parkTime2 / ($park_charge['charge_time'] * 60));
                                    $park_money1 = $parkTime3 * $park_charge['charge_money'];
                                    if ($parkTime1 > 24 && ($pay_money + $park_money1) > $park_charge['max_charge_money']) {
                                        $park_money += $park_charge['max_charge_money'];
                                    } elseif ($parkTime1 <= 24 && ($pay_money + $park_money1) > $park_charge['max_charge_money']) {
                                        $pay_money = $park_charge['max_charge_money'];
                                    } else {
                                        $park_money += $park_money1;
                                    }
                                    fdump_api([$pay_money, $parkTime2, $park_charge['max_charge_money'], $parkTime3, $park_charge['charge_time'], $park_money1, $park_money], 'park_pay_order_0411_1', 1);
                                } else {
                                    $parkTime3 = ceil($parkTime2 / ($park_charge['charge_time'] * 60));
                                    $park_money1 = $parkTime3 * $park_charge['charge_money'];
                                    $park_money += $park_money1;
                                    //  print_r([$parkTime3,$parkTime2,$park_charge['charge_time'],$park_charge['charge_money'],$park_money1]);die;
                                    fdump_api([$parkTime3, $parkTime2, $park_charge['charge_time'], $park_charge['charge_money'], $park_money1], 'park_pay_order_0411_1', 1);

                                }
                            } else {
                                foreach ($park_charge_info as $k => $v) {
                                    if ($v['time'] == $parkTime1) {
                                        $pay_money = $v['money'];
                                        break;
                                    } elseif ($v['time'] < $parkTime1) {
                                        $pay_money = $park_charge_info[$k - 1]['money'];
                                        break;
                                    } elseif (!isset($park_charge_info[$k + 1]) && $v['time'] > $parkTime1) {
                                        $pay_money = $park_charge_info[$k]['money'];
                                        break;
                                    }
                                }
                            }
                        }
                    } elseif ($park_charge['charge_type'] == 4) {
                        $parkTimeHour = ceil($park_time / 3600); //算小时
                        $tmp_pay_money = 0;
                        do {
                            foreach ($park_charge_info as $kk => $vv) {
                                $new_key = $kk + 1;
                                if ($vv['hour'] < 24 && ($parkTimeHour >= $vv['hour']) && ($parkTimeHour < $park_charge_info[$new_key]['hour'])) {
                                    $tmp_pay_money += $vv['fee'];
                                } else if ($vv['hour'] == 1 && $parkTimeHour <= $vv['hour']) {
                                    $tmp_pay_money += $vv['fee'];
                                } else if (($vv['hour'] == 24) && ($parkTimeHour >= $vv['hour'])) {
                                    $tmp_pay_money += $vv['fee'];
                                }
                            }
                            $parkTimeHour = $parkTimeHour - 24;
                        } while ($parkTimeHour > 0);
                        $pay_money = round($tmp_pay_money, 2);
                    } elseif ($park_charge['charge_type'] == 2 || ($park_charge['charge_type'] == 5 && $charge_type5_set == 2)) {
                        $parkTime_charge2 = ceil($park_time / 86400);
                        $pay_money = $park_charge['max_charge_money'] * $parkTime_charge2;
                    } else {
                        $parkTime_charge3 = $park_info['in_time'] + $park_time;
                        $oneDaySecond = 86400;  //24小时
                        if ($park_charge['charge_type'] == 5 && $charge_type5_set == 1 && $park_charge['hour_once_fee'] > 0) {
                            $oneDaySecond = $park_charge['hour_once_fee'] * 3600;  //自定义的
                        }
                        $time_charge = strtotime(date('Y_m-d 00:00:00', $park_info['in_time'])) + $oneDaySecond;
                        if ($time_charge < $parkTime_charge3) {
                            $parkTime_charge2 = ceil($park_time / $oneDaySecond);
                            $pay_money = $park_charge['max_charge_money'] * $parkTime_charge2;
                        } else {
                            $pay_money = $park_charge['max_charge_money'];
                        }
                    }
                    $pay_money = round_number($pay_money + $park_money - $parkTemp_money, 2);
                }
            }
            if ($pay_money > 0) {
                $this->checkCouponUseStatus($village_id,$car_number,$park_sys_type,$uid);
                $where_coupon = [];
                $where_coupon['village_id'] = $village_id;
                $where_coupon['car_number'] = $car_number;
                $where_coupon['user_id'] = $uid;
                $where_coupon['park_sys_type'] = $park_sys_type;
                $where_coupon['is_use'] = 0;
                $coupon_info = D('House_village_park_coupon')->where($where_coupon)->find();
                if (!empty($coupon_info)) {
                    $pay_money = $pay_money - $coupon_info['money'];
                    if ($pay_money <= 0) {
                        $pay_money = 0;
                        $deduction_amount = $pay_money;
                    } else {
                        $deduction_amount = $coupon_info['money'];
                    }
                    D('House_village_park_coupon')->where($where_coupon)->data(['is_use' => 1, 'use_time' => $nowTime])->save();
                    $db_house_village_car_flow_coupon = M('House_village_car_flow_coupon');
                    
                    $whereUniqueArr = [
                        'car_number' => $car_number, 'uid' => $uid, 'village_id' => $village_id,
                        'park_sys_type' => $park_sys_type, 'is_use' => 0,
                    ];
                    $where_unique_id = md5(json_encode($whereUniqueArr, JSON_UNESCAPED_UNICODE));
                    $whereFlowTemp = [
                        'unique_id' => $where_unique_id,
                    ];
                    $uniqueArr = [
                        'car_number' => $car_number, 'uid' => $uid, 'village_id' => $village_id,
                        'park_sys_type' => $park_sys_type, 'is_use' => 1,
                    ];
                    $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
                    $flowTempArr = array('is_use' => 1, 'use_time' => $nowTime, 'unique_id' => $unique_id);
                    $db_house_village_car_flow_coupon->where($whereFlowTemp)->data($flowTempArr)->save();
                }
            }
        } else {
            $msg['error'] = 1;
            $msg['msg'] = '没有停车时长';
            return $msg;
        }
        //  print_r([$parkTime3,$parkTime2,$park_charge['charge_time'],$park_charge['charge_money'],$park_money1,$park_money,$pay_money]);die;
        fdump_api([$parkTime3, $parkTime2, $park_charge['charge_time'], $park_charge['charge_money'], $park_money1, $park_money, $pay_money], 'park_pay_order_0411_1', 1);
        
        $whereTempMoney = [];
        $whereTempMoney['car_number'] =$car_number;
        $whereTempMoney['is_paid'] =array('neq',1);
        $whereTempMoney['village_id'] = $village_id;
        $parkTempMoney = $db_house_village_park_temp->where($whereTempMoney)->order('id DESC')->find();
        if (!empty($parkTempMoney) && $parkTempMoney['add_time']>(time()-180)&&$pay_money>$parkTempMoney['price']) {
            $pay_money=$parkTempMoney['price'];
            $park_time_total=$parkTempMoney['duration']*60;
            !$in_record_id && $in_record_id = isset($parkTempMoney['in_record_id']) && $parkTempMoney['in_record_id'] ? intval($parkTempMoney['in_record_id']) : 0;
            !$out_record_id && $out_record_id = isset($parkTempMoney['out_record_id']) && $parkTempMoney['out_record_id'] ? intval($parkTempMoney['out_record_id']) : 0;
        }
        $duration = ceil($park_time_total / 60);
        $coupon_id = isset($coupon_info['id']) ? intval($coupon_info['id']) : 0;
        $in_time = isset($park_info['in_time']) ? intval($park_info['in_time']) : 0;
        
        $data_temp = [];
        $data_temp['village_id'] = $village_id;
        $data_temp['add_time'] = time();
        $data_temp['free_out_time'] = $free_time;
        $data_temp['duration'] = $duration;
        $data_temp['derate_money'] = isset($coupon_info['money']) ? $coupon_info['money'] : 0;
        $data_temp['derate_duration'] = $reduction_time > 0 ? ceil($reduction_time/ 60) : 0;
        $data_temp['order_id'] = $park_info['order_id'];
        $data_temp['query_order_no'] = build_real_orderid($uid);
        $data_temp['errmsg'] = $msg['msg'];
        $data_temp['price'] = $pay_money;
        $data_temp['total'] = $total_money;
        $data_temp['in_time'] = $in_time;
        $data_temp['car_number'] = $car_number;
        $data_temp['out_channel_id'] = $channel_id;
        $data_temp['park_sys_type'] = $park_sys_type;
        $data_temp['is_pay_scene'] = $spread - 1;
        $data_temp['car_type'] = $car_type;
        $data_temp['park_charge'] = json_encode($park_charge_info, JSON_UNESCAPED_UNICODE);
        $data_temp['coupon_id'] = $coupon_id;
        $data_temp['garage_id']=$garage_id;
        if ($pay_money == 0) {
            $data_temp['is_paid'] = 1;
            $data_temp['pay_time'] = time();
        }
        $data_temp['in_record_id'] = $in_record_id;
        $data_temp['out_record_id'] = $out_record_id;
        $data_temp['rule_id']=$rule_id;
        $data_temp['project_id']=$project_id;
        
        fdump_api([$data_temp, $spread], 'park_pay_order_0411_1', 1);
        $query_order_no = M('House_village_parking_temp')->data($data_temp)->add();
        if ($query_order_no) {
            $msg['query_order_no'] = $query_order_no;
        }
        $is_paid = 0;
        $hasCoupon = false;
        $is_pay_scene = $spread - 1;
        $uniqueArr = [
            'car_number' => $car_number, 'village_id' => $village_id, 'is_paid' => $is_paid, 'is_pay_scene' => $is_pay_scene,
            'out_channel_id' => $channel_id, 'park_sys_type' => $park_sys_type, 'hasCoupon' => $hasCoupon,
        ];
        $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
        $flowTempData = [
            'parking_temp_id' => $query_order_no, 'village_id' => $village_id, 'car_number' => $car_number,
            'is_paid' => $is_paid, 'park_sys_type' => $park_sys_type, 'coupon_id' => $coupon_id, 'out_channel_id' => $channel_id,
            'is_pay_scene' => $is_pay_scene, 'order_id' => $data_temp['order_id'], 'price' => $data_temp['price'], 'query_time' => $nowTime,
            'in_time' => $in_time, 'unique_id' => $unique_id, 'out_record_id' => '', 'is_out' => 0, 'in_record_id' => 0,
            'reduction_time' => $reduction_time, 'deduction_amount' => $deduction_amount, 'free_out_time' => $free_time,
            'duration' => $duration, 'park_time' => $park_time_total
        ];
        $flowTempData['park_charge'] = json_encode($park_charge,JSON_UNESCAPED_UNICODE);
        $car_type && $flowTempData['car_type'] = $car_type;
        $in_record_id && $flowTempData['in_record_id'] = $in_record_id;
        $out_record_id && $flowTempData['out_record_id'] = $out_record_id;
        if ($pay_money == 0) {
            $flowTempData['is_paid'] = 1;
            $flowTempData['pay_time'] = $nowTime;
        }
        $whereUnique = [
            'unique_id' => $unique_id,
        ];
        $flowTempInfo = $db_house_village_car_flow_temp->where($whereUnique)->find();
        if (isset($flowTempInfo['id']) && $flowTempInfo['id']) {
            $flowTempData['update_time'] = time();
            $db_house_village_car_flow_temp->where($whereUnique)->data($flowTempData)->save();
        } else {
            $flowTempData['add_time'] = time();
            $flow_temp_id = $db_house_village_car_flow_temp->add($flowTempData);
        }
        return $msg;

    }
    public function queryOrderPriceThirdAiPark($village_id=0,$passage_info=array(),$car_number='',$spread=2,$uid=0){
        //$requestApiUri='http://openapi.szymzh.com/Api';
        $version='v1.0';
        $app_id=C('config.park_aicar_app_id');
        $app_id= $app_id ? trim($app_id):'';
        $app_secret=C('config.park_aicar_app_secret');
        $app_secret= $app_secret ? trim($app_secret):'';
        $ctrlNo=$passage_info['device_number'];
        $ctrlNo=strval($ctrlNo);
        $tmp_uri='/Inquire/GetVehicleCarInfo';  //获取车道车辆订单详情
        $params=array('appid'=>$app_id,'parkKey'=>$passage_info['park_type'],'ctrlNo'=>$ctrlNo);
        if(!empty($car_number)){
            $tmp_uri='/Inquire/GetCarNoOrderFee';  //车牌号查询停车订单费用
            $params=array('appid'=>$app_id,'parkKey'=>$passage_info['park_type'],'carNo'=>$car_number);
        }
        $params['rand']=rand(1,9).'.'.date('His').rand(100,999);
        $params['version']=$version;
        ksort($params);
        $signStr = $this->getThirdDataSign($params,$app_secret);
        $params['sign'] = $signStr;
        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            'Content-Encoding: UTF-8',
        );
        $requestApiUrl=$this->third_ai_park_request_url.$tmp_uri;
        $requestJsonStr = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $result = httpRequest($requestApiUrl, 'POST', $requestJsonStr, $headers);
        fdump_api(['_Line_' . __LINE__,'queryOrderPriceThirdAiPark','params' => $params, 'requestJsonStr' => $requestJsonStr,'requestApiUrl'=>$requestApiUrl, 'result' => $result], 'aiHorseSzymzh/thirdAiParkOrderPay' . date('H') . 'Log', 1);
        $responseData=array();
        if ($result && $result['1']) {
            $responseData = json_decode($result['1'], 1);
        }
        
        $returnArr=array('err_code'=>0,'err_msg'=>'','query_order_no'=>'','data'=>'');
        if($responseData && isset($responseData['code'])){
            if(isset($responseData['data']) && !empty($responseData['data'])){
                $returnArr['data']=$responseData['data'];
                $enterTimeStr=$responseData['data']['enterTime'];  //入场时间
                $enterTime=strtotime($enterTimeStr);
                $nowtime=time();
                $duration=($nowtime-$enterTime)/60;
                $duration=ceil($duration);
                $responseData['data']['ctrlNo']=$ctrlNo;
                $responseData['data']['parkKey']=$passage_info['park_type'];
                $responseData['data']['passage_id']=$passage_info['id'];
                if(isset($responseData['data']['carNo'])){
                    $car_number=$responseData['data']['carNo'];
                }
                $car_type='';
                if(isset($responseData['data']['carType'])){
                    $car_type=$responseData['data']['carType'];
                }
                $data_temp = [];
                $data_temp['village_id'] = $village_id;
                $data_temp['add_time'] = $nowtime;
                $data_temp['query_time'] = $nowtime;
                $data_temp['free_out_time'] = 0;
                $data_temp['token']=$passage_info['park_type'];
                $data_temp['comid']=$passage_info['park_type'];
                $data_temp['duration'] = $duration;
                $data_temp['sign'] = $responseData['sign'];
                $data_temp['derate_money'] =  0;
                $data_temp['derate_duration'] = 0;
                $data_temp['order_id'] = $responseData['data']['orderNo'];
                $data_temp['query_order_no'] = $responseData['data']['orderNo'];
                $data_temp['errmsg'] = $responseData['msg'];
                $data_temp['price'] = $responseData['data']['totalAmount'];
                $data_temp['total'] = $responseData['data']['totalAmount'];
                $data_temp['in_time'] = $enterTime;
                $data_temp['car_number'] = $car_number;
                $data_temp['out_channel_id'] = $ctrlNo;
                $data_temp['park_sys_type'] = 'third_ai_park';
                $data_temp['is_pay_scene'] = $spread - 1;
                $data_temp['car_type'] = $car_type;
                $data_temp['park_charge'] = json_encode($responseData['data'], JSON_UNESCAPED_UNICODE);
                $data_temp['coupon_id'] = 0;
                $data_temp['garage_id'] = $passage_info['garage_id'];
                if ($responseData['data']['totalAmount']<=0) {
                    $data_temp['is_paid'] = 1;
                    $data_temp['pay_time'] = $nowtime;
                }
                $data_temp['in_record_id'] = 0;
                $data_temp['out_record_id'] = 0;
                $query_order_no = M('House_village_parking_temp')->data($data_temp)->add();
                $returnArr['query_order_no']=$query_order_no;
            }else {
                $err_msg='未查询到费用数据！';
                if($responseData['code']!=1 && isset($responseData['msg']) && !empty($responseData['msg'])){
                    $err_msg=$responseData['msg'];
                }
                $returnArr['err_code']=1;
                $returnArr['err_msg']=$err_msg;
            }
        }else{
            $returnArr['err_code']=1;
            $returnArr['err_msg']='请求接口失败！';
        }
        return $returnArr;
    }

    public function thirdAiParkOrderPayCreate($temp = array())
    {
        // $requestApiUri='http://openapi.szymzh.com/Api';
        $version = 'v1.0';
        $app_id = C('config.park_aicar_app_id');
        $app_id = $app_id ? trim($app_id) : '';
        $app_secret = C('config.park_aicar_app_secret');
        $app_secret = $app_secret ? trim($app_secret) : '';
        $tmp_uri = '/Inform/OrderPayCreate';  //创建临停车支付订单
        $parkKey = $temp['comid'] ? $temp['comid'] : $temp['token'];
        $params = array('appid' => $app_id, 'parkKey' => $parkKey, 'orderNo' => $temp['order_id']);
        $params['orderAmount'] = $temp['price'];
        $params['payScene'] = '2';  //2出口缴费  1中央缴费
        $params['rand'] = rand(1, 9) . '.' . date('His') . rand(100, 999);
        $params['version'] = $version;
        ksort($params);
        $signStr = $this->getThirdDataSign($params, $app_secret);
        $params['sign'] = $signStr;
        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            'Content-Encoding: UTF-8',
        );
        $requestApiUrl = $this->third_ai_park_request_url . $tmp_uri;
        $requestJsonStr = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $result = httpRequest($requestApiUrl, 'POST', $requestJsonStr, $headers);
        fdump_api(['_Line_' . __LINE__, 'thirdAiParkOrderPayCreate', 'params' => $params, 'requestJsonStr' => $requestJsonStr, 'requestApiUrl' => $requestApiUrl, 'result' => $result], 'aiHorseSzymzh/thirdAiParkOrderPay' . date('H') . 'Log', 1);
        $responseData = array();
        if ($result && $result['1']) {
            $responseData = json_decode($result['1'], 1);
        }
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if ($responseData && isset($responseData['code'])) {
            if (isset($responseData['data']) && !empty($responseData['data'])) {
                $returnArr['data'] = $responseData['data'];
                $payOrderNo=$responseData['data']['payOrderNo']  ? $responseData['data']['payOrderNo']:'';
                $saveArr=array('query_order_no'=>$payOrderNo);
                if($temp['park_charge'] && !empty($temp['park_charge'])){
                    $temp['park_charge']= json_decode($temp['park_charge'],true);
                    $temp['park_charge']['payOrderNo']=$payOrderNo;
                    $saveArr['park_charge']=json_encode($temp['park_charge'],JSON_UNESCAPED_UNICODE);
                }
                M('House_village_parking_temp')->where(array('id'=>$temp['id']))->data($saveArr)->save();
            } else {
                $err_msg = '下单请求失败！';
                if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                    $err_msg = $responseData['msg'];
                }
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = $err_msg;
            }
        } else {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '请求下单接口失败！';
        }
        return $returnArr;
    }
    /**
     * 数据签名.
     */
    public function getThirdDataSign($datas = array(),$app_secret)
    {
        //签名步骤一：按字典序排序参数
        ksort($datas);
        $buffStr = "";
        foreach ($datas as $kk => $vv) {
            if (!empty($vv)) {
                $buffStr .= $kk .'='. $vv.'&';
            }
        }
        $pString = '';
        if (strlen($buffStr) > 0) {
            //签名步骤二：在string后加入KEY
            $buffStr =  $buffStr . $app_secret;
            $pString = md5($buffStr);
            $pString=strtoupper($pString);
        }
        return $pString;
    }
    public function getCarRightOutPosition($car = array(), $village_id = 0, $garage_id = 0, $car_number = '')
    {
        $whereArr = array();
        if (!empty($car_number)) {
            $whereArr['use_car_number'] = $car_number;
        } elseif ($car && isset($car['car_id']) && !empty($car['car_id'])) {
            $whereArr['use_car_id'] = $car['car_id'];
        }
        $parkTime = date('YmdH');
        $position = array();
        if (!empty($whereArr)) {
            $db_house_village_parking_position = M('House_village_parking_position');
            $whereArr['village_id'] = $village_id;
            if ($garage_id > 0) {
                $whereArr['garage_id'] = $garage_id;
            }
            $position = $db_house_village_parking_position->where($whereArr)->order('end_time desc')->find();
            fdump_api([__LINE__, 'car_number' => $car_number, 'whereArr' => $whereArr, 'position' => $position], 'D3Park/d3GetCarRightOutPosition' . $parkTime, 1);
        }
        if (!empty($position)) {
            $position['start_time'] = isset($position['start_time']) && intval($position['start_time']) > 0 ? intval($position['start_time']) : 0;
        }
        $nowTime = time();
        if (empty($position)) {
            $bind_position_ids = array();
            if ($car && isset($car['bind_position_ids']) && !empty($car['bind_position_ids'])) {
                $bind_position_ids = explode(',', $car['bind_position_ids']);
                $bind_position_ids = array_map('intval', $bind_position_ids);
            }
            if ($car && $car['car_position_id']) {
                $bind_position_ids[] = intval($car['car_position_id']);
            }
            if (!empty($bind_position_ids)) {
                $bind_position_ids = array_unique($bind_position_ids);
                $db_house_village_car_access_record = M('House_village_car_access_record');
                $access_record_where_near = [
                    'car_number' => $car_number,
                    'park_id' => $village_id,
                    'accessType' => 1,
                    'del_time' => array('lt', 1),
                    'accessTime' => array('elt', $nowTime),
                ];
                $houseVillageParkingCarBindPositionDb = M('House_village_parking_car_bind_position');
                if (count($bind_position_ids) == 1) {
                    //只绑了一个车位
                    $whereArr = array('position_id' => $bind_position_ids['0']);
                    if ($garage_id > 0) {
                        $whereArr['garage_id'] = $garage_id;
                    }
                    $position = $db_house_village_parking_position->where($whereArr)->field('*')->find();
                    //绑定的这个没被占用
                    fdump_api([__LINE__, 'car_number' => $car_number, 'whereArr' => $whereArr, 'position' => $position], 'D3Park/d3GetCarRightOutPosition' . $parkTime, 1);
                    if ($position['use_car_id'] < 1 && empty($position['use_car_number'])) {
                        $position['start_time'] = $position['start_time'] ? $position['start_time'] : 0;
                        $whereBind = array('position_id' => $bind_position_ids['0'], 'car_id' => $car['car_id'], 'del_time' => 0);
                        $oneBindObj = $houseVillageParkingCarBindPositionDb->where($whereBind)->field('*')->find();
                        if ($oneBindObj) {
                            $bind_position_time = $oneBindObj['bind_position_time'];
                            // 通行时间小于出场时间的最近的一条入场时间的记录
                            $park_access_record = $db_house_village_car_access_record->where($access_record_where_near)->field('*')->order('record_id DESC')->find();
                            if ($park_access_record['flag_status'] == 'temporaryCarNoPosition' || $park_access_record['park_car_type'] == 'temporaryCarNoPosition' || $park_access_record['car_type'] == 'temporaryCarNoPosition') {
                                //无车位进场的
                                $inAccessTime = $park_access_record['accessTime'] > 100 ? $park_access_record['accessTime'] : $nowTime;
                                $inAccessTimeDay = date('Ymd', $inAccessTime);
                                $inAccessTimeDay = intval($inAccessTimeDay);
                                $bindTimeDay = date('Ymd', $bind_position_time);
                                $bindTimeDay = intval($bindTimeDay);
                                $chaDay = $bindTimeDay - $inAccessTimeDay;
                                if ($chaDay > 0 && $position['end_time'] > $nowTime) {
                                    //车位有效，并且绑定时间和进场时间隔了至少一个零点
                                    $bindTimeDate = date('Y-m-d', $bind_position_time);
                                    $bindTimeDay_time = strtotime($bindTimeDate);
                                    //绑定时间日 凌晨算临时车结束时间
                                    $position['temporary_end_time'] = $bindTimeDay_time - 1;
                                }
                            }
                        }
                    } else {
                        $position = array();
                    }
                }
                fdump_api(['position' => $position, 'bind_position_ids' => $bind_position_ids], 'D3Park/d3GetCarRightOutPosition' . $parkTime, 1);
                //还是没找到车位
                if (empty($position)) {
                    $whereBindArr = array();
                    $whereBindArr['position_id'] = array('in', $bind_position_ids);
                    $whereBindArr['del_time'] = array('lt', 1);
                    $whereBindArr['village_id'] = $village_id;
                    $carPositionBind = $houseVillageParkingCarBindPositionDb->where($whereBindArr)->field('position_id,car_id,bind_position_time,garage_id,village_id')->order('id DESC')->limit(100)->select();
                    $usePositionArr = array();
                    if (!empty($carPositionBind)) {
                        $tmpCarPositionBind = array();
                        foreach ($carPositionBind as $ppv) {
                            if (isset($tmpCarPositionBind[$ppv['position_id']])) {
                                $tmpCarPositionBind[$ppv['position_id']][] = $ppv['car_id'];
                            } else {
                                $tmpCarPositionBind[$ppv['position_id']] = array($ppv['car_id']);
                            }
                        }
                        foreach ($bind_position_ids as $p_id) {
                            $tmpxPosition = isset($tmpCarPositionBind[$p_id]) && $tmpCarPositionBind[$p_id] ? array_unique($tmpCarPositionBind[$p_id]) : '';
                            if (empty($tmpxPosition)) {
                                $usePositionArr[] = $p_id;
                            } else if (is_array($tmpxPosition) && in_array($car['car_id'], $tmpxPosition) && count($tmpxPosition) == 1) {
                                $usePositionArr[] = $p_id;
                            }
                        }
                    } else {
                        $usePositionArr = $bind_position_ids;
                    }
                    fdump_api(['usePositionArr' => $usePositionArr, 'bind_position_ids' => $bind_position_ids], 'D3Park/d3GetCarRightOutPosition' . $parkTime, 1);
                    if (!empty($usePositionArr)) {
                        $whereArr = array('position_id'=>array('in', $usePositionArr));
                        $whereArr['start_time'] = array('lt', $nowTime);
                        $whereArr['end_time'] = array('gt', $nowTime);
                        if ($garage_id > 0) {
                            $whereArr['garage_id'] =  $garage_id;
                        }
                        $position = $db_house_village_parking_position->where($whereArr)->order('use_start_time ASC,position_id DESC')->find();
                    }
                }
            }
        }
        return $position;
    }
    
    /**
     * D3查询车辆的需缴费信息
     * @author:zhubaodi
     * @date_time: 2021/10/8 17:05
     */
    public function queryD3OrderPrice($param)
    {
        $data = [
            'method' => 'getCarInfo',
            'park_id' => $param['park_id'],
            'car_no' => $param['car_no'],
        ];
        $parkCarInfo = $this->getParkingApi($data, $this->url);
        if (!empty($parkCarInfo)){
            $arr_data = [
                'car_number' => $parkCarInfo['car_no'],
                'duration' => $parkCarInfo['charge_hour']*60+$parkCarInfo['charge_minute'].'分钟',
                'duration_num' => $parkCarInfo['charge_hour']*60+$parkCarInfo['charge_minute'],
                'free_out_time' => 0,
                'free_out_time_desc' => 0,
                'in_time' => date('Y-m-d H:i:s',$parkCarInfo['starttime']),
                'price' => $parkCarInfo['total_price'],
                'village_name' =>$parkCarInfo['park_name'],
            ];
            $park_data=[
                'car_number'=>$parkCarInfo['car_no'],
                'in_time'=>$parkCarInfo['starttime'],
                'in_channel_id'=>$parkCarInfo['deviceCodeIn'],
                'park_id'=>$param['park_id'],
                'park_sys_type'=>'D3',
                'park_name'=>$parkCarInfo['park_name'],
                'park_time'=>($parkCarInfo['charge_hour']*60+$parkCarInfo['charge_minute'])*60,
                'ordersn'=>$parkCarInfo['ordersn'],
            ];
            M('In_park')->data($park_data)->add();
        }else{
            $arr_data = [];
        }

        return $arr_data;
    }


    /**
     * Notes: 平台缴费通知车场接口
     * @param $village_id
     * @param $car_id
     * @param $param
     * @param array $carInfo
     * @return array
     * @author: wanzy
     * @date_time: 2021/4/25 13:40
     */
    public function payNoticeToPark($village_id, $param, $car_id = 0, $carInfo = [])
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $village_id))->find();
        if ($village_park_config['park_sys_type']=='D3'){
            $msg = [
                'error' => 0,
                'msg' => ''
            ];
            $data = [
                'method' => 'getCarInfo',
                'park_id' => $village_park_config['comid'],
                'car_no' => $param['car_number'],
            ];
            $parkCarInfo = $this->getParkingApi($data, $this->url_v2);
            if (!empty($parkCarInfo)){
                $data_notice=[
                    'method'=>'receivePayNotice',
                    'park_id'=>$village_park_config['comid'],
                    'car_no' => $param['car_number'],
                    'ordersn'=>$parkCarInfo['ordersn'],
                    'starttime'=>$parkCarInfo['starttime'],
                    'deviceCodeIn'=>$parkCarInfo['deviceCodeIn'],
                    'price'=>$parkCarInfo['price'],
                ];
                $res=$this->editParkingApi($data_notice, $this->url_v2);
                if ($res['code']==200){
                    return $msg;
                }else{
                    $msg['error'] = 1;
                    $msg['msg'] = '通知发送失败';
                    return $msg;
                }
            }else{
                $msg['error'] = 1;
                $msg['msg'] = '通知发送失败';
                return $msg;
            }
        }
        else{
            $msg = [
                'error' => 0,
                'msg' => ''
            ];
            if (!$village_park_config['ckey'] || !$village_park_config['union_id'] || !$village_park_config['comid']) {
                $msg['error'] = 1;
                $msg['msg'] = '请联系工作人员，配置现相关参数';
                return $msg;
            }
            $data = [
                'comid' => trim($village_park_config['comid']),
                'union_id' => trim($village_park_config['union_id']),
            ];
            if (isset($param['car_number']) && $param['car_number']) {
                $data['car_number'] = $param['car_number'];
            } elseif (isset($carInfo['car_number']) && $carInfo['car_number']) {
                $data['car_number'] = $carInfo['province'] . $carInfo['car_number'];
            } elseif ($car_id) {
                $where = [
                    'car_id' => $car_id
                ];
                $carInfo = D('House_village_parking_car')->where($where)->find();
                $data['car_number'] = $carInfo['province'] . $carInfo['car_number'];
            } else {
                $msg['error'] = 1;
                $msg['msg'] = '缺少对应车牌';
                return $msg;
            }
            // 1，平台缴费/积分抵扣；2，酒店订单
            $data['type'] = 1;
            if (isset($param['money'])) {
                // 应付金额
                $data['payable'] = $param['money'];
                // 实收金额
                $data['act_total'] = $param['money'];
            }
            if (isset($param['pay_time'])) {
                // 支付时间
                $data['pay_time'] = $param['pay_time'];
            }

            if (isset($param['order_id']) && $param['order_id']) {
                $data['order_id'] = $param['order_id'];
            }
            if (!$data['order_id'] || !$data['money']) {
                $whereTemp = [
                    'service_name' => 'd1_query_price',
                    'car_number' => $data['car_number'],
                    'comid' => trim($village_park_config['comid']),
                    'is_paid' => 0,
                ];
                $temp = M('House_village_parking_temp')->where($whereTemp)->field('id,order_id, price')->order('id DESC')->find();
                if ($temp && $temp['price'] == $data['payable']) {
                    $data['order_id'] = $temp['order_id'];
                    // 应付金额 更正 为查询的 订单一致
                    $data['payable'] = $temp['price'];
                } else {
                    fdump_api(['d1平台缴费通知车场[缺少对应订单号]:' . __LINE__, $village_id, $param, $data, $temp], 'park/d1/d1PayNoticeToParkErrLog', 1);
                    $msg['error'] = 1;
                    $msg['msg'] = '缺少对应订单号';
                    return $msg;
                }
            }
            $data_msg = $this->getAssembleD1Data($village_id, $data, $village_park_config['ckey']);
            if ($data_msg['base64_data']) {
                $url = $this->formal_url . '/openservice/paynoticetopark';
                $headers = array(
                    "Content-type: application/json;charset='utf-8'",
                    'Content-Encoding: UTF-8',
                );
                $payNoticeToPark = httpRequest($url, 'POST', $data_msg['base64_data'], $headers);
                $payNoticeToParkData = base64_decode($payNoticeToPark[1]);
                $toParkData = json_decode($payNoticeToParkData, true);
                if (!$toParkData['data']['state']) {
                    $msg['error'] = 1;
                    $msg['msg'] = $toParkData['message'] ? $toParkData['message'] : '查询失败';
                    fdump_api(['d1平台缴费通知车场[请求回馈错误]:' . __LINE__, $village_id, $data, $data_msg, $payNoticeToPark, $toParkData], 'park/d1/d1PayNoticeToParkErrLog', 1);
                    return $msg;
                }
                fdump_api(['d1平台缴费通知车场:' . __LINE__, $village_id, $param, $url, $data, $data_msg, $payNoticeToPark, $toParkData], 'park/d1/d1PayNoticeToParkLog', 1);
                return $msg;
            } else {
                $msg['error'] = 1;
                $msg['msg'] = $data_msg['msg'] ? $data_msg['msg'] : '查询失败';
                fdump_api(['d1平台缴费通知车场[参数组装失败]:' . __LINE__, $village_id, $param, $data, $data_msg], 'park/d1/d1PayNoticeToParkErrLog', 1);
                return $msg;
            }
        }

    }


    /**
     * D3获取token
     * @author:zhubaodi
     * @date_time: 2021/9/8 13:45
     */
    public function getToken()
    {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $url = $this->url;
        $data = [
            'method' => 'auth',
            'AppID' => $this->AppID,
            'AppSecret' => $this->AppSecret
        ];
        $res = $this->http->curlPostOwnWithHeader($url, http_build_query($data), []);
        $res = json_decode($res, true);
        $token = '';
        if ($res['code'] == 200) {
            $token = $res['data']['token'];
        }
        return $token;
    }

    public function getParkingApi($data, $url)
    {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $token = $this->getToken();
        $headersArr = [];
        $headersArr[] = "token:" . $token;
        $res = $this->http->curlPostOwnWithHeader($url, http_build_query($data), $headersArr);
        $res = json_decode($res, true);
        $dataList = [];
        if ($res['code'] == 200) {
            $dataList = $res['data'];
        }
        // print_r($dataList);exit;
        return $dataList;
    }

    public function editParkingApi($data,$url)
    {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $token = $this->getToken();
        $headersArr = [];
        $headersArr[] = "token:".$token;
        $res = $this->http->curlPostOwnWithHeader($url, http_build_query($data), $headersArr);
        $res=json_decode($res,true);

        return $res;
    }


    /**
     *D5 获取设备状态
     * @author: liukezhu
     * @date : 2022/1/18
     * @param $village_park
     * @return array
     */
    public function getChannel($village_park){
        $data=[];
        if($village_park['park_sys_type'] != 'D5'){
            return $data;
        }
        $config=[
            'base_url'=>$village_park['d5_url'],
            'userName'=>$village_park['d5_name'],
            'passWord'=>$village_park['d5_pass']
        ];
        $key=md5(json_encode($config));
        if(S($key)){
            return json_decode(S($key),true);
        }
        $channe=invoke_v20_service('\app\community\model\service\Park\D5Service/getChannelList',['config'=>$config]);
        if(!isset($channe['data']['retval']['data']) || empty($channe['data']['retval']['data'])){
            return $data;
        }
        foreach ($channe['data']['retval']['data'] as $v){
            if(intval($v['online']) == 1){
                $data[]=$v['channelId'];
            }
        }
        S($key,json_encode($data,JSON_UNESCAPED_UNICODE),1800); //缓存三分钟
        return $data;
    }

    /**
     * D7查询车辆的需缴费信息
     * @author:zhubaodi
     * @date_time: 2021/10/8 17:05
     */
    public function queryOrderPriceD7($village_id, $car_number, $uid, $spread=1, $channel_id = '',$d7_park_id='')
    {
        $msg = [
            'error' => 0,
            'msg' => ''
        ];
        if(empty($d7_park_id)){
            $parkConfigWhere=array('village_id'=>$village_id,'park_sys_type'=>'D7');
            $house_village_park_config=D('House_village_park_garage_config')->getVillageParkConfigByWhere($parkConfigWhere);
            if (empty($house_village_park_config)||empty($house_village_park_config['d7_park_id'])){
                $msg['error'] = 1;
                $msg['msg'] = '停车场信息不存在';
                return $msg;
            }else{
                $d7_park_id=$house_village_park_config['d7_park_id'];
            }
        }

        $uid_phone='';
        $visitor_info = D('House_village_visitor')->where(['village_id'=>$village_id,'uid'=>$uid,'_string'=>'car_id is not null and car_id <> ""'])->order('id DESC')->find();
        if (!empty($visitor_info)){
            $uid_phone=$visitor_info['visitor_phone'];
        }
        if ($spread==2){
            if (empty($channel_id)){
                $msg['error'] = 1;
                $msg['msg'] = '车道信息不存在';
                return $msg;
            }
            $param=[
                'parkId'=>$d7_park_id,
                'queryType'=>1,
                'channelId'=>$channel_id,
                'carnumber'=>'',
                'uid'=>$uid_phone,
            ];

        }else{
            if (empty($car_number)){
                $msg['error'] = 1;
                $msg['msg'] = '车牌号不能为空';
                return $msg;
            }
            $param=[
                'parkId'=>$d7_park_id,
                'queryType'=>0,
                'channelId'=>'',
                'carnumber'=>$car_number,
            ];
        }
       //
        $query=invoke_v20_service('\app\community\model\service\Park\QinLinCloudService/queryParkingBill',$param);
      //  print_r($query);die;
        fdump_api([$query,$param],'D7park/park_pay_order',1);
        if(isset($query['data']['retval']['state']) &&$query['data']['retval']['state'] != '200'){
            $msg['error'] = 1;
            $msg['msg'] = $query['data']['retval']['errMsg'];
            return $msg;
        }
        if(!isset($query['data']['retval']['data'])|| empty($query['data']['retval']['data'])){
            $msg['error'] = 1;
            $msg['msg'] = '查询失败';
            return $msg;
        }
        $price_data=json_decode($query['data']['retval']['data'],true);
        $data_temp=[];
        $data_temp['village_id']=$village_id;
        $data_temp['add_time']=time();
        $data_temp['query_time']=time();
        $data_temp['duration']=$price_data['parkingTime'];
        $data_temp['derate_money']=isset($price_data['discountMoney'])?round_number($price_data['discountMoney']/100,2):0;
        $data_temp['derate_duration']=isset($price_data['discountTime'])?$price_data['discountTime']:0;
        $data_temp['order_id']=$price_data['orderNo'];
        $data_temp['query_order_no']=$price_data['parkingId'];
        $data_temp['price']=round_number($price_data['paymentAmount']/100,2);
        $data_temp['total']=round_number($price_data['orderMoney']/100,2);
        $data_temp['in_time']=$price_data['inTime'];
        $data_temp['car_number']=empty($car_number)?$price_data['carnumber']:$car_number;
        $data_temp['out_channel_id']=$channel_id;
        $data_temp['park_sys_type']='D7';
        $data_temp['is_pay_scene']=$spread-1;
        $data_temp['errmsg']=serialize($price_data['orderDetails']);
        fdump_api([$data_temp,$spread],'D7park/park_pay_order',1);
        $query_order_no = M('House_village_parking_temp')->data($data_temp)->add();
        if ($query_order_no) {
            $msg['query_order_no'] = $query_order_no;
        }
        return $msg;

    }
    
    public function getParkSystemHeartTime($village_id)
    {
        $cacheKey = "park_system_" . $village_id;
        return  cache($cacheKey);
    }
}