<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/4/26 9:36
 */

namespace app\community\model\service;

use app\common\model\service\UserService;
use app\community\model\db\ProcessSubPlan;
use app\community\model\db\WeixinAppBind;
use app\community\model\service\UserService as UserPileService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseOpenDoorAdver;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillagePileAttribute;
use app\community\model\db\HouseVillagePileAttributeSet;
use app\community\model\db\HouseVillagePileCommand;
use app\community\model\db\HouseVillagePileConfig;
use app\community\model\db\HouseVillagePileEquipment;
use app\community\model\db\HouseVillagePileEquipmentAttribute;
use app\community\model\db\HouseVillagePileMonthCard;
use app\community\model\db\HouseVillagePileMonthCardUser;
use app\community\model\db\HouseVillagePilePayOrder;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillagePileRefundOrder;
use app\community\model\db\HouseVillageHaokuaichongRegion;
use app\community\model\db\HouseVillageHaokuaichongRate;
use app\community\model\db\HouseVillagePileUserBalance;
use app\community\model\db\HouseVillagePileUserBalanceLog;
use app\community\model\db\User;
use app\pay\model\db\PayOrderInfo;
use app\traits\CommonLogTraits;
use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\ConfigService;

class PileUserService
{
    use CommonLogTraits;

    //驴充充配置信息
    public $lbbtechCenterToken = '';
    public $lbbtechCenterUrl = '';

    //艾特充配置信息
    public $outeruser_url = 'http://ap3204.haokuaichong.cn/outeruser';
    public $apiServiceUrl         =   'http://out.haokuaichong.cn/api'; //正式地址
    
    public $userId = '237';

    public $time = 300;//单位：秒
    public $pay_arr = [1 => '余额支付', 2 => '微信支付', 3 => '月卡支付', 4 => '支付宝支付', 10 => '本地余额卡支付',5=>'充电桩余额'];
    public $port_state_info=array(
        '0'=>'空闲',
        '1'=>'充电中',
        '2'=>'有充电器但未充电（用 户未启动充电）',
        '3'=>'有充电器但未充电（已充满电）',
        '4'=>'端口有故障（短路或该路无法计量）',
        '5'=>'浮充',
        '6'=>'存储器损坏',
        '7'=>'门磁开关故障',
        '8'=>'接触不良或保险丝烧断故障',
        '170'=>'等待刷卡中',
        );
    public $lvvEndTypeDesc = array(
        1 => '计时结束',
        2 => '充满结束',
        3 => '手动结束 （二轮车）',
        4 => '功率过大',
        5 => '空载结束',
        6 => '充电结束',
        12 => '时长超限',
        13 => '温度过高',
        14 => '电表故障',
        15 => '负载异常-电压过高',
        16 => '负载异常-触发漏保',
        17 => '设备故障',
        18 => '通道故障，灭火器触发',
        19 => '烟感报警',
        20 => '未关充电柜门',
        51 => '用户手动停止充电',
        52 => '输出不可控',
        53 => 'SOC达到设定值',
        54 => '单体电池电压达到设定值',
        55 => '连接器过温',
        56 => '连接器故障',
        57 => '充电电压异常',
        58 => 'DC20通信失败',
        59 => 'RC10通信失败',
        60 => '电度表通信中断',
        61 => '输入过压',
        62 => '输入缺相',
        63 => '内部过温',
        64 => '输出欠压',
        65 => '熔断器故障',
        66 => '输出短路',
        67 => '电流采样异常',
        68 => '绝缘故障',
        69 => '充电枪过温',
        70 => '余额不足',
        71 => '达到设定时间、金额、电量（计时结束）',
        72 => '过电量保护',
        73 => '后台通信异常',
        74 => '充电机处于手动状态',
        75 => '车内接触器粘连',
        76 => '充电枪断开',
        77 => '输出过流',
        78 => '输出过压',
        79 => '电池总电压达到设定值',
        80 => '电池过温',
        81 => '模块通讯故障',
        82 => '紧急停机',
        83 => '柜门打开',
        84 => '枪加锁失败',
        85 => '充电机启动超时',
        86 => '车辆BMS异常',
        87 => '输出开关断开',
        88 => '电流过于小',
        89 => '接地故障',
        90 => '主从机通信断开',
        91 => '网络断线',
        92 => '电表电能异常值',
        93 => 'BMS主动终止充电',
        95 => '连接故障cc跳变',
        96 => '电池反接',
        98 => '防雷器告警停止',
        99 => '电子锁未锁',
        100 => '屏幕停止',
        101 => '刷卡停止',
        102 => '单体蓄电池电压过高',
        103 => '单体蓄电池电压过低',
        104 => '整车蓄电池SOC过高',
        105 => '动力蓄电池充电过流',
        106 => '整车蓄电池SOC过低',
        107 => '动力蓄电池充电过流',
        108 => '动力蓄电池充电过温',
        109 => '动力蓄电池绝缘异常',
        110 => '动力蓄电池组输出连接器异常',
        111 => 'BMS达到设定总电压',
        112 => 'BMS达到单体电压设定值',
        113 => 'BMS绝缘异常',
        114 => 'BMS连接器过温',
        115 => 'BMS元件过温',
        116 => 'BMS连接器故障',
        117 => 'BMS电池组过温',
        118 => 'BMS高压继电器故障',
        119 => 'BMS监测点2电压异常',
        120 => 'BMS其它故障',
        121 => 'BMS报电流过大的停止充电',
        122 => 'BMS报电压异常的停止充电',
        123 => '达到桩设定SOC值（充满结束）',
        124 => '最高单体充电电压异常',
        125 => '最高电池组过温',
        126 => 'BMV单体充电电压异常',
        127 => 'BMT电池组过温',
        128 => '电池状态异常',
        129 => '禁止充电',
        130 => '输出连接器过温故障',
        131 => 'BMS元件、电池组输出连接器过温',
        132 => '电池组温度过高故障',
        133 => '高压继电器故障',
        134 => '监测点2电压检测故障 看门狗状态故障',
        135 => '电流过大',
        136 => '电压异常',
        137 => '功率过大',
        138 => '接收电池充电总状态报文超时',
        139 => '接收电池充电要求报文超时',
        140 => '接收电池状态信息报文超时',
        141 => '接收BMS中止充电报文超时',
        142 => '接收BMS充电统计报文超时',
        143 => '低压辅助电源异常',
        144 => '接收时间同步和最大输出能力报文超时',
        145 => '接收充电机充电状态报文超时',
        146 => '接收充电机中止充电报文超时',
        147 => '接收充电机充电统计报文超时',
    );
    /**
     * 初始化数据
     */
    public function __construct()
    {
        $service_adver = new HouseOpenDoorAdver();
        $this->lbbtechCenterToken = cfg('lbbtechCenterToken');
        $this->lbbtechCenterUrl = cfg('lbbtechCenterUrl');
        if (!$this->lbbtechCenterUrl) {
            $this->lbbtechCenterUrl = 'https://center.lbbtech.com';
        }
        $data = $service_adver->getList([['intelligent_hardware_type', '=', 4], ['end_time', '>', time()], ['status', '=', 1]], 'id,android_pic,play_time')->toArray();
        if (!empty($data)) {
            $key = array_rand($data);
            $data1 = $data[$key];
            if (!empty($data1)) {
                $data1['android_pic'] = dispose_url($data1['android_pic']);
                $data1['play_time'] = $data1['play_time'] * 1000;
            }
        } else {
            $data1 = [];
        }

        $this->adverList = $data1;
    }

    /**
     * 扫码获取设备详情
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/26 18:41
     */

    public function getEquipmentInfo($uid, $qcodeNum, $deviceId, $lat, $long, $type = 1)
    {
        //查询设备基本信息
        $service_equipment = new HouseVillagePileEquipment();
        $filed = '*';

        if ($type == 2) {
            if (!empty($qcodeNum)) {
                $where = [
                    'is_del' => 1,
//                    'status'=>1,
                    'equipment_serial' => $qcodeNum,

                ];
            }
            if (!empty($deviceId)) {
                $where = [
                    'is_del' => 1,
//                    'status'=>1,
                    'equipment_serial' => $deviceId,
                ];
            }
        } else {
            if (!empty($qcodeNum)) {
                $where = [
                    'is_del' => 1,
                    'equipment_num' => $qcodeNum,

                ];
            }
            if (!empty($deviceId)) {
                $where = [
                    'is_del' => 1,
                    'equipment_num' => $deviceId,
                ];
            }
        }

        $equipmentInfo = $service_equipment->getInfo($where, $filed);
        $equipment = [];

        if ($qcodeNum && (empty($equipmentInfo) || $equipmentInfo->isEmpty())) {
            $whereArr = array(['type', '=', 1], ['equipment_sn', '=', $qcodeNum], ['is_del', '=', 1]);
            $equipmentInfo = $service_equipment->getInfo($whereArr, $filed);
        }
        if (empty($equipmentInfo) || $equipmentInfo->isEmpty()) {
            throw new \think\Exception("当前设备不存在，请先添加");
        }
        if ($equipmentInfo['status'] != 1) {
            //todo 充电桩故障写入
            invoke_cms_model('House_maintenan/faultLog', ['type' => 2, 'device_id' => $equipmentInfo['id'], 'reason' => '当前设备已离线'], true);
            throw new \think\Exception("当前设备已离线，无法充电");
        }
        //查询小区名称
        $service_village = new HouseVillage();
        $villageInfo = $service_village->getInfo(['village_id' => $equipmentInfo['village_id']], 'village_name,village_id,property_phone');
        $phone_arr = explode(' ', $villageInfo['property_phone']);

        if (empty($lat) || empty($long)) {
            $juli = 0;
        } else {
            $juli = getDistance($lat, $long, $equipmentInfo['lat'], $equipmentInfo['lng']);
        }

        if ($juli >= 1000) {
            $juli = round_number($juli / 1000, 2) . 'km';
        } else {
            $juli = $juli . 'm';
        }
        $info = [
            'id' => $equipmentInfo['id'],
            'equipment_name' => $equipmentInfo['equipment_name'],
            'address' => $villageInfo['village_name'],
            'property_phone' => $phone_arr[0],
            'property_phone_arr' => $phone_arr,
            'lat' => $equipmentInfo['lat'],
            'lng' => $equipmentInfo['lng'],
            'distance' => $juli,
            'equipment_num' => $equipmentInfo['equipment_num'],
            'type' => $equipmentInfo['type'],
        ];
        if ($equipmentInfo['type'] == 2 || $equipmentInfo['type'] == 3) {
            $info['equipment_num'] = $equipmentInfo['equipment_sn'];
        }
        fdump_api(['获取参数' . __LINE__, $info], 'equipmentInfo', 1);
        if ($equipmentInfo['type'] == 1) {
            //获取插座状态
            $socket_arr = $this->deviceStatus($equipmentInfo['equipment_num'],0,$equipmentInfo['id']);
            unset($socket_arr[0]);
            if (empty($socket_arr)) {
                //todo 充电桩故障写入
                invoke_cms_model('House_maintenan/faultLog', ['type' => 2, 'device_id' => $equipmentInfo['id'], 'reason' => '当前设备已离线'], true);
                throw new \think\Exception("当前设备已离线，无法充电");
            }
        } elseif ($equipmentInfo['type'] == 21) {
            //获取插座状态
            $socketList = $this->outGetPorts($equipmentInfo['equipment_num']);
            if (empty($socketList)) {
                //todo 充电桩故障写入
                invoke_cms_model('House_maintenan/faultLog', ['type' => 2, 'device_id' => $equipmentInfo['id'], 'reason' => '当前设备已离线'], true);
                throw new \think\Exception("当前设备已离线，无法充电");
            }
        } elseif ($equipmentInfo['type'] == 3) {
            $tmptime = time() - 3;
            $socketList = $equipmentInfo['port_status'] ? json_decode($equipmentInfo['port_status'], true) : array();
            if (isset($equipmentInfo['dev_update_time']) && $equipmentInfo['dev_update_time'] < $tmptime) {
                $param = array('village_id' => $equipmentInfo['village_id'], 'equipment_num' => $equipmentInfo['equipment_num'], 'equipment_id' => $equipmentInfo['id']);
                $res = invoke_cms_model('House_village_pile_equipment/getType3EquipmentDetail', $param);
                if ($res && isset($res['retval']) && !empty($res['retval']) && isset($res['retval']['data']) && !empty($res['retval']['data'])) {
                    $portState = $res['retval']['data']['portState'];
                    $port_status = $portState ? explode(',', $portState) : '';
                    if ($port_status) {
                        $socketList = $port_status;
                    }
                }
            }
            if (empty($socketList)) {
                //todo 充电桩故障写入
                invoke_cms_model('House_maintenan/faultLog', ['type' => 2, 'device_id' => $equipmentInfo['id'], 'reason' => '当前设备已离线'], true);
                throw new \think\Exception("当前设备已离线，无法充电");
            }
        } else {
            if ($equipmentInfo['status'] != 1) {
                //todo 充电桩故障写入
                invoke_cms_model('House_maintenan/faultLog', ['type' => 2, 'device_id' => $equipmentInfo['id'], 'reason' => '当前设备已离线'], true);
                throw new \think\Exception("当前设备已离线，无法充电");
            }
            $socket_arr = json_decode($equipmentInfo['port_status']);
        }
        fdump_api(['获取参数' . __LINE__, $socket_arr], 'equipmentInfo', 1);
        $socket_data = [];
        if ($equipmentInfo['type'] == 21) {
            $socket_data = $socketList;
        } elseif ($equipmentInfo['type'] == 2) {
            if (!empty($socket_arr)) {
                foreach ($socket_arr as $k => $value) {
                    $data_arr['name'] = $k + 1;
                    $data_arr['status'] = $value;
                    $socket_data[] = $data_arr;
                }
            }
        } elseif ($equipmentInfo['type'] == 3) {
            //空闲、未充占用、充电中、充满占用、故障
            foreach ($socketList as $kk => $dks) {
                $pname = $kk + 1;
                if ($pname < 10) {
                    $pname = '0' . $pname;
                }
                $status = 0;
                $color = '#2681f3';
                if ($dks == 0) {
                    $status = 4; //0 空闲不可充电
                    $color = '#40D851';
                } elseif ($dks == 2) {
                    $color = '#BC12ED';
                    $status = 0; //2 有充电器但未充电（用户未启动充电）
                } else if (in_array($dks, [1, 5, 170])) {
                    $color = '#CDCDCD';
                    $status = 1;  //充电中
                } elseif ($dks == 3) {
                    $color = '#2681f3';
                    $status = 3; //有充电器但未充电（已充满电）
                } else {
                    $color = '#fa565f';
                    $status = 2; //故障
                }
                $status_des = isset($this->port_state_info[$dks]) ? $this->port_state_info[$dks] : '未知';
                $port_info = array('name' => $pname, 'status' => $status, 'status_des' => $status_des, 'color' => $color);
                $socket_data[] = $port_info;
            }
        } else {
            if (!empty($socket_arr)) {
                foreach ($socket_arr as $k => $value) {
                    $data_arr['name'] = $k;
                    $data_arr['status'] = $value;
                    $socket_data[] = $data_arr;
                }

            }
        }

        $service_equipment = new HouseVillagePileMonthCard();
        $whereArr = array(['village_id', '=', $equipmentInfo['village_id']]);
        $whereArr[] = array('is_del', '=', 1);
        $whereArr[] = array('status', '=', 1);
        if ($equipmentInfo['type'] == 3) {
            $whereArr[] = array('type', '=', 3);
        }
        $monthCardList = $service_equipment->getList($whereArr);
        if (!empty($monthCardList) && !$monthCardList->isEmpty()) {
            $equipment['monthCard'] = true;
        } else {
            $equipment['monthCard'] = false;
        }
        $service_Card_User = new HouseVillagePileMonthCardUser();

        $where = [];
        $where[] = ['uid', '=', $uid];
        $where[] = ['status', '=', 1];
        $where[] = ['expire_time', '>', time()];
        $where[] = ['surplus_num', '>', 0];
        $cardUserInfo = $service_Card_User->getOne($where);
        if ($cardUserInfo) {
            $equipment['card_info'] = 1;
        } else {
            $equipment['card_info'] = 2;
        }
        $equipment['advertisement'] = $this->adverList;
        $equipment['equipmentInfo'] = $info;
        $equipment['socketList'] = $socket_data;
        $equipment['id'] = $equipmentInfo['id'];
        $equipment['have_user_balance_pay'] = 0;
        $equipment['user_pile_balance'] = 0;
        $configCustomizationService = new ConfigCustomizationService();
        $pile_charge_consume_point = $configCustomizationService->getPileChargeConsumePoint();
        if ($pile_charge_consume_point) {
            $equipment['have_user_balance_pay'] = 1;
            $userBalanceInfo = $this->getPileUserBalance($uid);
            $equipment['user_pile_balance'] = $userBalanceInfo['current_money'];
        }

        //todo 充电桩故障解除
        invoke_cms_model('House_maintenan/normalLog', ['type' => 2, 'device_id' => $equipmentInfo['id']], true);
        fdump_api(['获取参数' . __LINE__, $equipment], 'equipmentInfo', 1);
        return $equipment;

    }
    public function getPileUserBalance($uid=0){
        $houseVillagePileUserBalance=new HouseVillagePileUserBalance();
        $whereArr=array(['uid','=',$uid]);
        $returnArr=array('uid'=>$uid,'current_money'=>0,'balance_info'=>'');
        $tmpObj=$houseVillagePileUserBalance->getOneData($whereArr);
        if($tmpObj && !$tmpObj->isEmpty()){
            $userBalance=$tmpObj->toArray();
            $returnArr['current_money']=$userBalance['current_money'];
            $returnArr['balance_info']=$userBalance;
        }
        return $returnArr;
    }
    public function getEquipmentDetail($e_id=0,$uid=0){
        $service_equipment = new HouseVillagePileEquipment();
        $equipmentInfo = $service_equipment->getInfo(['id' => $e_id, 'is_del' => 1]);
        $returnArr=array('err_code'=>0,'tipmsg'=>'','data_info'=>array());
        if (empty($equipmentInfo)) {
            $returnArr['err_code']=1;
            $returnArr['tipmsg']="当前设备不存在!";
            return $returnArr;
        }
        $tmpdata=$this->getEquipmentDetailInfo($equipmentInfo,$uid,'');
        $service_village = new HouseVillage();
        $villageInfo = $service_village->getInfo(['village_id' => $equipmentInfo['village_id']], 'village_name,village_id,property_phone');
        $phone_arr = explode(' ', $villageInfo['property_phone']);

        if (empty($lat) || empty($long)) {
            $juli = 0;
        } else {
            $juli = getDistance($lat, $long, $equipmentInfo['lat'], $equipmentInfo['lng']);
        }

        if ($juli >= 1000) {
            $juli = round_number($juli / 1000, 2) . 'km';
        } else {
            $juli = $juli . 'm';
        }
        $info = [
            'id' => $equipmentInfo['id'],
            'equipment_name' => $equipmentInfo['equipment_name'],
            'address' => $villageInfo['village_name'],
            'property_phone' => $phone_arr[0],
            'property_phone_arr' => $phone_arr,
            'lat' => $equipmentInfo['lat'],
            'lng' => $equipmentInfo['lng'],
            'distance' => $juli,
            'equipment_num'=>$equipmentInfo['equipment_num'],
            'type'=>$equipmentInfo['type'],
        ];
        if($equipmentInfo['type']==2 || $equipmentInfo['type']==3){
            $info['equipment_num']=$equipmentInfo['equipment_sn'];
        }
        $tmpdata['equipmentInfo']=$info;
        $tmpdata['have_user_balance_pay']=0;
        $tmpdata['user_pile_balance']=0;
        $configCustomizationService=new ConfigCustomizationService();
        $pile_charge_consume_point=$configCustomizationService->getPileChargeConsumePoint();
        if($pile_charge_consume_point){
            $tmpdata['have_user_balance_pay']=1;
            $userBalanceInfo=$this->getPileUserBalance($uid);
            $tmpdata['user_pile_balance']=$userBalanceInfo['current_money'];
        }
        return $tmpdata;
    }

    public function getEquipmentDetailInfo($equipmentInfo, $uid = 0, $socket = '')
    {

        $returnArr = array('err_code' => 0, 'tipmsg' => '', 'data_info' => array());
        if (empty($equipmentInfo) || !isset($equipmentInfo['id'])) {
            $returnArr['err_code'] = 1;
            $returnArr['tipmsg'] = "充电设备信息不存在！";
            return $returnArr;
        }
        $id = $equipmentInfo['id'];
        $cardUserInfo = array();
        $attribute = [];
        if ($equipmentInfo['type'] == 1) {
            //获取插座状态
            $deviceStatus = $this->deviceStatus($equipmentInfo['equipment_num'], 2,$equipmentInfo['id']);
            $attribute = [];
            //判断设备是否存在且正常
            if (empty($deviceStatus)) {
                $returnArr['err_code'] = 1;
                $returnArr['tipmsg'] = "设备信息查询失败！";
                return $returnArr;
            }
            if ($socket) {
                $socketArr = $deviceStatus['data']['device']['data']['ports'];
                $status = $socketArr ? $socketArr[$socket] : 0;
                //判断插座状态
                if ($status != 0) {
                    $url = $this->getChargeOrderInfo($id, $socket, $uid);
                    if (empty($url)) {
                        $returnArr['err_code'] = 1;
                        $returnArr['tipmsg'] = "当前插座不能充电，请更换插座!";
                        return $returnArr;
                    } else {
                        $returnArr['err_code'] = 0;
                        $returnArr['tipmsg'] = '';
                        $returnArr['data_info']['url'] = $url;
                        return $returnArr;
                    }
                }
            }
            if ($uid > 0) {
                //查询月卡
                $service_Card_User = new HouseVillagePileMonthCardUser();
                $where[] = ['uid', '=', $uid];
                $where[] = ['status', '=', 1];
                $where[] = ['expire_time', '>', time()];
                $where[] = ['surplus_num', '>', 0];
                $cardUserInfo = $service_Card_User->getOne($where);
                $service_Card = new HouseVillagePileMonthCard();
                $where1 = ['id' => $cardUserInfo['card_id']];
                $cardInfo = $service_Card->getOne($where1);
                if (!empty($cardInfo) && $cardInfo['village_id'] != $equipmentInfo['village_id']) {
                    $cardUserInfo = [];
                }
            }
            if (isset($deviceStatus['data']['priceDetail']) && $deviceStatus['data']['priceDetail']) {
                $price_standard_list_data = $deviceStatus['data']['priceDetail'];
            } else {
                //查询消费标准
                $price_standard_list = $this->getPriceStandard($equipmentInfo['equipment_num']);
                $price_standard_list_data = isset($price_standard_list['data']) ? $price_standard_list['data'] : [];
            }
            $priceStandardUniteList = isset($price_standard_list_data['priceStandardUniteList']) ? $price_standard_list_data['priceStandardUniteList'] : [];
            $paramView0 = isset($priceStandardUniteList[0]['priceView']) ? $priceStandardUniteList[0]['priceView'] : '';
            $pricePay0 = isset($priceStandardUniteList[0]['pricePay']) ? $priceStandardUniteList[0]['pricePay'] : '';
            $paramUnitName0 = isset($priceStandardUniteList[0]['paramUnitName']) ? $priceStandardUniteList[0]['paramUnitName'] : '';
            $priceUnitName0 = isset($priceStandardUniteList[0]['priceUnitName']) ? $priceStandardUniteList[0]['priceUnitName'] : '';
            $fullBlackoutForButton0 = isset($priceStandardUniteList[0]['fullBlackoutForButton']) ? $priceStandardUniteList[0]['fullBlackoutForButton'] : '';

            if (isset($price_standard_list_data['fullBlackout']) && $price_standard_list_data['fullBlackout'] == 1) {
                $priceStandardUniteList[0]['priceView'] = $pricePay0;
                $priceStandardUniteList[0]['paramView'] = '';
            }
            $equipment_name = $equipmentInfo['equipment_name'];
            $work_type = isset($price_standard_list_data['type']) ? intval($price_standard_list_data['type']) : -1; //消费标准类型 0、自定义时间;1、功率分段;2、电量计费
            $coinParam = isset($price_standard_list_data['coinParam']) ? intval($price_standard_list_data['coinParam']) : 0;
            $windowNotice = isset($price_standard_list_data['windowNotice']) ? trim($price_standard_list_data['windowNotice']) : '';

            $handle_work_type = -1;
            if ($work_type == 1) {
                // 功率计费
                $handle_work_type = 2;
                // 1-充满自停(Poweroff when full)[充满自停 不额外退费] 2-按功率计费(Charge by power)[消耗固定功率后停止充电]
                $chargeType = 1;
                if (strpos($priceUnitName0, '充满自停') !== false) {
                    $chargeType = 1;
                } elseif (strpos($priceUnitName0, 'Poweroff when full') !== false) {
                    $chargeType = 1;
                } elseif (strpos($priceUnitName0, '按功率计费') !== false) {
                    $chargeType = 2;
                } elseif (strpos($priceUnitName0, 'Charge by power') !== false) {
                    $chargeType = 2;
                }
                $standard=$price_standard_list_data['name'];
                $standard_fee=1;
                $htime=0;
                $noticeNewList=array();
                /**
                noticeList数据格式 [
                    "0.16元/1小时(0瓦-200瓦)",
                    "0.33元/1小时(201瓦-400瓦)",
                    "0.5元/1小时(401瓦-600瓦)",
                    "1元/1小时(601瓦-1200瓦)",
                    "1.5元/1小时(1201瓦-4000瓦)"
                ]
                **/
                if(isset($price_standard_list_data['noticeList']) && $price_standard_list_data['noticeList']){
                    foreach ($price_standard_list_data['noticeList'] as $kk=>$dvvS){
                        $delimiter='元';
                        $tmpArr=explode($delimiter,$dvvS);
                        $standard_fee_tmp=$tmpArr['0'];
                        $htimeTmp = 1/$standard_fee_tmp;
                        $htimeTmp=round($htimeTmp);
                        $feeStr='1元/'.$htimeTmp.'小时';
                        if($kk==0){
                            $htime= $htimeTmp;
                            $standard=$feeStr;
                        }

                        $delimiter='1小时';
                        $tmpArr=explode($delimiter,$dvvS);
                        $powerStr=$tmpArr['1'];
                        $feeStr=$tmpArr['0'].'/1小时';
                        $feeStr=str_replace('//','/',$feeStr);
                        $powerStr=str_replace('(0瓦','1',$powerStr);
                        $powerStr=str_replace('(','',$powerStr);
                        $powerStr=str_replace(')','',$powerStr);
                        $powerStr=str_replace('瓦','',$powerStr);
                        $noticeNewList[]=array('power'=>$powerStr,'fee'=>$feeStr);
                    }
                }
                if ($cardUserInfo) {
                    $card = 1;
                    $info = [
                        'notice_info' => ['standard' => $standard,'standard_fee'=>$standard_fee,'htime'=>$htime,'standard_tips'=>'' ,'power' => ''],
                        'notice_new_list'=>$noticeNewList,
                        'socket_num' => $socket,
                        'standard_value' => $coinParam,
                        'surplus_num' => $cardUserInfo['surplus_num'],
                    ];
                    if ($chargeType == 1) {
                        if (!isset($priceStandardUniteList[0]['paramView']) && $paramUnitName0) {
                            $cardUserInfo['priceNanme'] = '充满断电(' . $paramUnitName0 . '充满),您选择的是充满自停方式充电，中途中断充电，系统不退费';
                        } elseif ($paramView0) {
                            $cardUserInfo['priceNanme'] = '充电(' . $paramView0 . $paramUnitName0 . '充满),您选择的是充满自停方式充电，中途中断充电，系统不退费';
                        } elseif ($pricePay0) {
                            $cardUserInfo['priceNanme'] = '充满断电(' . $pricePay0 . $paramUnitName0 . '充满),您选择的是充满自停方式充电，中途中断充电，系统不退费';
                        }
                    }
                    if ($chargeType == 2) {
                        $cardUserInfo['priceNanme'] = $pricePay0 . $paramUnitName0 . '/' . $paramView0 . '小时';
                    }
                    $attribute['cardUserInfo'] = $cardUserInfo;
                } else {
                    $card = 2;
                    foreach ($priceStandardUniteList as &$v) {
                        $paramView = isset($v['paramView']) ? $v['paramView'] : '';
                        $pricePay = isset($v['pricePay']) ? $v['pricePay'] : '';
                        $paramUnitName = isset($v['paramUnitName']) ? $v['paramUnitName'] : '';
                        $priceUnitName = isset($v['priceUnitName']) ? $v['priceUnitName'] : '';

                        // 1-充满自停(Poweroff when full)[充满自停 不额外退费] 2-按功率计费(Charge by power)[消耗固定功率后停止充电]
                        $chargeType = 1;
                        if (strpos($priceUnitName, '充满自停') !== false) {
                            $chargeType = 1;
                        } elseif (strpos($priceUnitName, 'Poweroff when full') !== false) {
                            $chargeType = 1;
                        } elseif (strpos($priceUnitName, '按功率计费') !== false) {
                            $chargeType = 2;
                        } elseif (strpos($priceUnitName, 'Charge by power') !== false) {
                            $chargeType = 2;
                        }
                        if ($chargeType == 1) {
                            if (!isset($v['paramView']) && $paramUnitName) {
                                $v['paramViewText'] = '充满断电(' . $paramUnitName . '充满),您选择的是充满自停方式充电，中途中断充电，系统不退费';
                            } elseif ($paramView) {
                                $v['paramViewText'] = '充满断电(' . $paramView . $paramUnitName . '充满),您选择的是充满自停方式充电，中途中断充电，系统不退费';
                            } elseif ($pricePay) {
                                $v['paramViewText'] = '充满断电(' . $pricePay . $paramUnitName . '充满),您选择的是充满自停方式充电，中途中断充电，系统不退费';
                            }
                        }
                        if ($chargeType == 2) {
                            $v['paramViewText'] = $pricePay . $paramUnitName . '/' . $paramView . '小时';
                        }
                        $v['priceView'] = $pricePay;
                        $v['paramView'] = '';
                    }
                    $info = [
                        'notice_info' => ['standard' => $standard,'standard_fee'=>$standard_fee,'htime'=>$htime,'standard_tips'=>'', 'power' => ''],
                        'notice_new_list'=>$noticeNewList,
                        'socket_num' => $socket,
                        'notice_list' => $price_standard_list_data['noticeList'],
                        'standard_list' => $priceStandardUniteList,
                    ];
                }
            }
            // 计时模式
            if ($work_type == 0) {
                $standard=$price_standard_list_data['name'];
                $standard_fee=0;
                $htime=0;
                $noticeNewList=array();
                foreach ($priceStandardUniteList as $kk=>$dvv){
                    $feeStr=$dvv['priceView'].'元/'.$dvv['paramView'].'小时';
                    if($kk==0){
                        $standard_fee=$dvv['priceView'];
                        $standard=$feeStr;
                        $htime=$dvv['paramView'];
                    }
                    $noticeNewList[]=array('power'=>'不限','fee'=>$feeStr);
                }
                $handle_work_type = 1;
                if ($cardUserInfo) {
                    $card = 1;
                    $info = [
                        'notice_info' => ['standard' => $standard,'standard_fee'=>$standard_fee,'htime'=>$htime,'standard_tips'=>'', 'power' => ''],
                        'socket_num' => $socket,
                        'notice_new_list'=>$noticeNewList,
                        'standard_value' => $price_standard_list_data['coinParam'],
                        'surplus_num' => $cardUserInfo['surplus_num'],
                    ];
                    $cardUserInfo['priceNanme'] = '预计充电时长' . $paramView0 . $paramUnitName0;
                    $attribute['cardUserInfo'] = $cardUserInfo;

                } else {
                    $card = 2;
                    $info = [
                        'notice_info' => ['standard' => $standard,'standard_fee'=>$standard_fee,'htime'=>$htime,'standard_tips'=>'', 'power' => ''],
                        'socket_num' => $socket,
                        'notice_new_list'=>$noticeNewList,
                        'notice_list' => $price_standard_list_data['noticeList'],
                        'standard_list' => $priceStandardUniteList,
                    ];
                }
            }
            // 计量模式
            if ($work_type == 2) {
                $handle_work_type = 3;
                $standard=$price_standard_list_data['name'];
                $standard_fee=0;
                $noticeNewList=array();
                if(isset($price_standard_list_data['priceStandardElectricList']) && $price_standard_list_data['priceStandardElectricList']){
                    $tmpArrLen=count($price_standard_list_data['priceStandardElectricList']);
                    if($tmpArrLen==1){
                        $priceStandardElectric=$price_standard_list_data['priceStandardElectricList']['0'];
                        $price_fee=$priceStandardElectric['price']/100;
                        $price_fee=round($price_fee,2);
                        $service_fee=$priceStandardElectric['serviceFee']/100;
                        $service_fee=round($service_fee,2);
                        $standard_fee=($priceStandardElectric['price']+$priceStandardElectric['serviceFee'])/100;
                        $standard_fee=round($standard_fee,2);
                        $standard=$standard_fee.'元/度';
                        $noticeNewList[]=array('time_span'=>'00:00-23:59','fee'=>$price_fee.'元','service_fee'=>$service_fee.'元','total_fee'=>$standard_fee.'元');
                    }else {
                        $date = new \DateTime();
                        // 若需指定时区（例如UTC+8）
                        $date->setTimezone(new \DateTimeZone('Asia/Shanghai')); // 东八区
                        
                        foreach ($price_standard_list_data['priceStandardElectricList'] as $kk=>$dvv){
                            $price_fee=$dvv['price']/100;
                            $price_fee=round($price_fee,2);
                            $service_fee=$dvv['serviceFee']/100;
                            $service_fee=round($service_fee,2);
                            $standard_fee_tmp=($dvv['price']+$dvv['serviceFee'])/100;
                            $standard_fee_tmp=round($standard_fee_tmp,2);
                            if($kk==0){
                                $standard_fee=$standard_fee_tmp;
                                $standard=$standard_fee_tmp.'元/度';
                            }
                            $xtimestamp = $dvv['startTime']/1000 ; // 转为秒
                            $date->setTimestamp($xtimestamp); // 设置时间戳
                            $startTime= $date->format('H:i');
                            $xtimestamp = $dvv['endTime']/1000 ; // 转为秒
                            $date->setTimestamp($xtimestamp); // 设置时间戳
                            $endTime= $date->format('H:i');
                            $noticeNewList[]=array('time_span'=>$startTime.'-'.$endTime,'fee'=>$price_fee.'元','service_fee'=>$service_fee.'元','total_fee'=>$standard_fee_tmp.'元');
                        }
                    }
                }else if(isset($price_standard_list_data['priceStandardElectric']) && $price_standard_list_data['priceStandardElectric']){
                    $price_fee=$price_standard_list_data['priceStandardElectric']['price']/100;
                    $price_fee=round($price_fee,2);
                    $service_fee=$price_standard_list_data['priceStandardElectric']['serviceFee']/100;
                    $service_fee=round($service_fee,2);
                    $standard_fee=($price_standard_list_data['priceStandardElectric']['price']+$price_standard_list_data['priceStandardElectric']['serviceFee'])/100;
                    $standard_fee=round($standard_fee,2);
                    $standard=$standard_fee.'元/度';
                    $noticeNewList[]=array('time_span'=>'00:00-23:59','fee'=>$price_fee.'元','service_fee'=>$service_fee.'元','total_fee'=>$standard_fee.'元');
                }
                if ($cardUserInfo) {
                    $card = 1;
                    $info = [
                        'notice_info' => ['standard' => $standard, 'standard_fee'=>$standard_fee,'standard_tips'=>'（含服务费）','power' => ''],
                        'notice_new_list'=>$noticeNewList,
                        'socket_num' => $socket,
                        'standard_value' => $price_standard_list_data['coinParam'],
                        'surplus_num' => $cardUserInfo['surplus_num'],
                    ];
                    $cardUserInfo['priceNanme'] = '预计充电电量' . $paramView0 . $paramUnitName0;
                    $attribute['cardUserInfo'] = $cardUserInfo;
                } else {
                    if (!empty($priceStandardUniteList) && !empty($price_standard_list_data['electricPrice'])) {
                        foreach ($priceStandardUniteList as &$v) {
                            $v['paramUnitName'] = '度';
                            $v['paramView'] = round_number($v['param'] / ($price_standard_list_data['electricPrice'] + $price_standard_list_data['electricServiceFee']), 2);
                            $v['priceView'] = $v['pricePay'];
                            $v['electricPrice'] = $price_standard_list_data['electricPrice'];
                        }
                    }
                    $card = 2;
                    $info = [
                        'notice_info' => ['standard' => $standard, 'standard_fee'=>$standard_fee,'standard_tips'=>'（含服务费）','power' => ''],
                        'socket_num' => $socket,
                        'notice_new_list'=>$noticeNewList,
                        'notice_list' => $price_standard_list_data['noticeList'],
                        'standard_list' => $priceStandardUniteList,
                    ];
                }
            }

            $attribute['equipment_name'] = $equipment_name;
            $attribute['work_type'] = $handle_work_type;
            $attribute['card_info'] = $card;
            $attribute['socket_info'] = $info;
            $attribute['advertisement'] = $this->adverList;
            $attribute['windowNotice'] = $windowNotice;
        } elseif ($equipmentInfo['type'] == 21) {
            //获取设备状态
            $equipment_status = $this->queList($equipmentInfo['pointId'], $equipmentInfo['equipment_num']);
            if ($equipment_status != '联网') {
                $returnArr['err_code'] = 1;
                $returnArr['tipmsg'] = "当前设备未连接网络，无法充电!";
                return $returnArr;
            }
            if ($socket) {
                //获取插座状态
                $socketList = $this->outGetPorts($equipmentInfo['equipment_num']);
                if (!empty($socketList)) {
                    foreach ($socketList as $v) {
                        if ($v['name'] == $socket) {
                            $status = $v['status'];
                            break;
                        }
                    }
                    //判断插座状态
                    if (!isset($status) || $status < 1) {
                        $returnArr['err_code'] = 1;
                        $returnArr['tipmsg'] = "当前插座不能充电，请更换插座!";
                        return $returnArr;
                    }
                } else {
                    $returnArr['err_code'] = 1;
                    $returnArr['tipmsg'] = "当前插座不能充电，请更换插座!";
                    return $returnArr;
                }
            }
            if ($uid > 0) {
                //查询月卡
                $service_Card_User = new HouseVillagePileMonthCardUser();
                $where = [];
                $where[] = ['uid', '=', $uid];
                $where[] = ['status', '=', 1];
                $where[] = ['expire_time', '>', time()];
                $where[] = ['surplus_num', '>', 0];
                $cardUserInfo = $service_Card_User->getOne($where);
                if (!empty($cardUserInfo)) {
                    $service_Card = new HouseVillagePileMonthCard();
                    $where1 = ['id' => $cardUserInfo['card_id']];
                    $cardInfo = $service_Card->getOne($where1);
                    if (!empty($cardInfo) && $cardInfo['village_id'] != $equipmentInfo['village_id']) {
                        $cardUserInfo = [];
                    } else {
                        $service_order = new HouseVillagePilePayOrder();
                        $where_order = [
                            ['uid', '=', $uid],
                            ['pay_type', '=', 3],
                            ['pay_time', '>', strtotime(date('Y-m-d 00:00:00'))],
                        ];
                        $count = $service_order->get_count($where_order);
                        if ($count >= $cardUserInfo['max_num']) {
                            $cardUserInfo = [];
                        }
                    }
                }

            }

            $service_Equipment_Attribute = new HouseVillagePileEquipmentAttribute();
            $attributeId = $service_Equipment_Attribute->getOne(['equipment_id' => $equipmentInfo['id'], 'attribute_id' => 1, 'is_del' => 1]);

            $attributeInfo = [];
            $service_Attribute_Set = new HouseVillagePileAttributeSet();
            if (!empty($attributeId)) {
                $attributeInfo = $service_Attribute_Set->getOne(['id' => $attributeId['attribute_standard_id'], 'status' => 1, 'is_del' => 1]);
            }
            if (empty($attributeInfo)) {
                $returnArr['err_code'] = 1;
                $returnArr['tipmsg'] = "消费标准为空!";
                return $returnArr;
            }
            //查询消费标准
            $equipment_name = $equipmentInfo['equipment_name'];
            $work_type = $equipmentInfo['work_type'];
            $price_standard_list = unserialize($attributeInfo['standard_value']);

            //计时模式
            if ($work_type == 1) {
                if (!empty($cardUserInfo)) {
                    $card = 1;
                    $info = [
                        'socket_num' => $socket,
                        'standard_value' => $price_standard_list[1][2],
                        'surplus_num' => $cardUserInfo['surplus_num'],
                    ];
                    $times = ceil($cardUserInfo['per_price'] / $price_standard_list[0][2] * $price_standard_list[1][2]);
                    $cardUserInfo['priceNanme'] = '预计充电时长' . $times . '小时';
                    $attribute['cardUserInfo'] = $cardUserInfo;

                } else {
                    $noticeList = [];
                    $attributePowerId = $service_Equipment_Attribute->getOne(['equipment_id' => $equipmentInfo['id'], 'attribute_id' => 2, 'is_del' => 1]);
                    $PowerInfo = [];
                    if (!empty($attributePowerId)) {
                        $PowerInfo = $service_Attribute_Set->getOne(['id' => $attributePowerId['attribute_standard_id'], 'status' => 1, 'is_del' => 1]);
                        $powerInfo_value = $PowerInfo['standard_value'];
                    } else {
                        $service_Attribute_Set = new HouseVillagePileAttribute();
                        $PowerInfo = $service_Attribute_Set->getOne(['value' => 2]);
                        $powerInfo_value = $PowerInfo['default_value'];
                    }
                    if (!empty($powerInfo_value)) {
                        $PowerInfo = unserialize($powerInfo_value);
                        $times = $price_standard_list[1][2] / $price_standard_list[0][2];// 小时/元
                        $noticeList = [
                            0 => '0-' . $PowerInfo[0][2] . 'W 约' . round($times, 1) . '小时/元',
                            1 => $PowerInfo[0][2] + 1 . '-' . $PowerInfo[0][2] * 2 . 'W 约' . round($times / 2, 1) . '小时/元',
                            2 => $PowerInfo[0][2] * 2 + 1 . '-' . $PowerInfo[0][2] * 3 . 'W 约' . round($times / 3, 1) . '小时/元',
                            3 => $PowerInfo[0][2] * 3 + 1 . '-' . $PowerInfo[0][2] * 4 . 'W 约' . round($times / 4, 1) . '小时/元',
                        ];
                    }
                    $card = 2;
                    $info = [
                        'socket_num' => $socket,
                        'notice_list' => $noticeList,
                        'notice_info' => ['standard' => $price_standard_list[0][2] . '元' . $price_standard_list[1][2] . '小时','standard_fee'=>$price_standard_list[0][2] . '元' . $price_standard_list[1][2] . '小时','standard_tips'=>'', 'power' => $PowerInfo[0][2] . 'W'],
                        'standard_list' => [
                            0 => ['priceView' => round_number($price_standard_list[0][2], 2),
                                'paramUnitName' => '小时',
                                'paramView' => round_number($price_standard_list[1][2], 2),
                                'pricePay' => round_number($price_standard_list[0][2], 2)],
                            1 => ['priceView' => round_number($price_standard_list[0][2] * 2, 2),
                                'paramUnitName' => '小时',
                                'paramView' => round_number($price_standard_list[1][2] * 2, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 2, 2)],
                            2 => ['priceView' => round_number($price_standard_list[0][2] * 3, 2),
                                'paramUnitName' => '小时',
                                'paramView' => round_number($price_standard_list[1][2] * 3, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 3, 2)],
                            3 => ['priceView' => round_number($price_standard_list[0][2] * 4, 2),
                                'paramUnitName' => '小时',
                                'paramView' => round_number($price_standard_list[1][2] * 4, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 4, 2)],
                            4 => ['priceView' => round_number($price_standard_list[0][2] * 5, 2),
                                'paramUnitName' => '小时',
                                'paramView' => round_number($price_standard_list[1][2] * 5, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 5, 2)],
                            5 => ['priceView' => round_number($price_standard_list[0][2] * 6, 2),
                                'paramUnitName' => '小时',
                                'paramView' => round_number($price_standard_list[1][2] * 6, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 6, 2)],
                        ],
                    ];

                }
            }
            //计量模式
            if ($work_type == 2) {
                if (!empty($cardUserInfo)) {
                    $card = 1;
                    $info = [
                        'socket_num' => $socket,
                        'standard_value' => $price_standard_list['data']['coinParam'],
                        'surplus_num' => $cardUserInfo['surplus_num'],
                    ];
                    $times = ceil($cardUserInfo['per_price'] / $price_standard_list[0][2] * $price_standard_list[2][2]);
                    $cardUserInfo['priceNanme'] = '预计充电电量' . $times . '度';

                    //  $cardUserInfo['priceNanme']='预计充电电量'.$price_standard_list['data']['priceStandardUniteList'][0]['paramView'].$price_standard_list['data']['priceStandardUniteList'][0]['paramUnitName'];
                    $attribute['cardUserInfo'] = $cardUserInfo;
                } else {
                    $card = 2;
                    $info = [
                        'notice_info' => ['standard' => '', 'standard_fee'=>'','standard_tips'=>'','power' => ''],
                        'socket_num' => $socket,
                        'notice_list' => $price_standard_list['data']['noticeList'],
                        'standard_list' => [
                            0 => ['priceView' => round_number($price_standard_list[0][2], 2),
                                'paramUnitName' => '度',
                                'paramView' => round_number($price_standard_list[2][2], 2),
                                'pricePay' => round_number($price_standard_list[0][2], 2)],
                            1 => ['priceView' => round_number($price_standard_list[0][2] * 2, 2),
                                'paramUnitName' => '度',
                                'paramView' => round_number($price_standard_list[2][2] * 2, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 2, 2)],
                            2 => ['priceView' => round_number($price_standard_list[0][2] * 3, 2),
                                'paramUnitName' => '度',
                                'paramView' => round_number($price_standard_list[2][2] * 3, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 3, 2)],
                            3 => ['priceView' => round_number($price_standard_list[0][2] * 4, 2),
                                'paramUnitName' => '度',
                                'paramView' => round_number($price_standard_list[2][2] * 4, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 4, 2)],
                            4 => ['priceView' => round_number($price_standard_list[0][2] * 5, 2),
                                'paramUnitName' => '度',
                                'paramView' => round_number($price_standard_list[2][2] * 5, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 5, 2)],
                            5 => ['priceView' => round_number($price_standard_list[0][2] * 6, 2),
                                'paramUnitName' => '度',
                                'paramView' => round_number($price_standard_list[2][2] * 6, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 6, 2)],
                        ],
                        //    'standard_list' => $price_standard_list['data']['priceStandardUniteList'],

                    ];
                }
                $work_type = $work_type + 1;
            }
            $attribute['equipment_name'] = $equipment_name;
            $attribute['work_type'] = $work_type;
            $attribute['card_info'] = $card;
            $attribute['socket_info'] = $info;
            $attribute['advertisement'] = $this->adverList;

        } elseif ($equipmentInfo['type'] == 3) {
            //好快充 平台
            $tmptime = time() - 180;
            if ($socket) {
                //实时查设备的充电端口状态
                $socketList = $equipmentInfo['port_status'] ? json_decode($equipmentInfo['port_status'], true) : array();

                $param = array('village_id' => $equipmentInfo['village_id'], 'equipment_num' => $equipmentInfo['equipment_num'], 'equipment_id' => $equipmentInfo['id']);
                $res = invoke_cms_model('House_village_pile_equipment/getType3EquipmentDetail', $param);
                if ($res && isset($res['retval']) && !empty($res['retval']) && isset($res['retval']['data']) && !empty($res['retval']['data'])) {
                    $portState = $res['retval']['data']['portState'];
                    $port_status = $portState ? explode(',', $portState) : '';
                    if ($port_status) {
                        $socketList = $port_status;
                    }
                }
                $socket = intval($socket);
                $portStatusErr = '';
                if (!empty($socketList)) {
                    foreach ($socketList as $skk => $dks) {
                        $sname = $skk + 1;
                        if ($sname == $socket) {
                            $socket_status = $dks;
                            if ($dks == 0) {
                                //$portStatusErr='充电端口未检测到充电器插入，请先插上充电器！';

                            } elseif (in_array($dks, [1, 3, 5, 170])) {
                                $portStatusErr = '充电端口已经在充电中，请选择其他端口充电！';

                            } elseif ($dks != 2) {
                                $portStatusErr = '充电端口故障无法充电，请选择其他端口充电！';

                            }
                            break;
                        }
                    }
                    if ($portStatusErr) {
                        $returnArr['err_code'] = 1;
                        $returnArr['tipmsg'] = $portStatusErr;
                        return $returnArr;
                    }
                    //判断插座状态
                    if (isset($socket_status) && in_array($socket_status, [0, 2])) {
                        //设备端口正常
                    } else {
                        $returnArr['err_code'] = 1;
                        $returnArr['tipmsg'] = '当前插座不能充电，请更换插座';
                        return $returnArr;
                    }
                } else {
                    $returnArr['err_code'] = 1;
                    $returnArr['tipmsg'] = '当前插座不能充电，请更换插座';
                    return $returnArr;

                }
            }
            if ($uid > 0) {
                //查询月卡
                $service_Card_User = new HouseVillagePileMonthCardUser();
                $where = [];
                $where[] = ['uid', '=', $uid];
                $where[] = ['status', '=', 1];
                $where[] = ['expire_time', '>', time()];
                $where[] = ['surplus_num', '>', 0];
                $cardUserInfo = $service_Card_User->getOne($where);
                if (!empty($cardUserInfo)) {
                    $service_Card = new HouseVillagePileMonthCard();
                    $where1 = ['id' => $cardUserInfo['card_id'], 'type' => 3];
                    $cardInfo = $service_Card->getOne($where1);
                    if (!empty($cardInfo) && $cardInfo['village_id'] != $equipmentInfo['village_id']) {
                        $cardUserInfo = [];
                    } else {
                        $service_order = new HouseVillagePilePayOrder();
                        $where_order = [
                            ['uid', '=', $uid],
                            ['pay_type', '=', 3],
                            ['type', '=', 3],
                            ['pay_time', '>', strtotime(date('Y-m-d 00:00:00'))],
                        ];
                        $count = $service_order->get_count($where_order);
                        if ($count >= $cardUserInfo['max_num']) {
                            $cardUserInfo = [];
                        }
                    }
                }
            }

            $area_id = $equipmentInfo['area_id'];
            if (empty($area_id)) {
                $returnArr['err_code'] = 1;
                $returnArr['tipmsg'] = '设备所属充电区不存在，无法充电！';
                return $returnArr;
            }
            $houseVillageHaokuaichongRegionDb = new HouseVillageHaokuaichongRegion();
            $whereRegion = array('area_id' => $area_id, 'village_id' => $equipmentInfo['village_id']);
            $hkcRegionObj = $houseVillageHaokuaichongRegionDb->getOneData($whereRegion, '*');
            $hkcRegion = array();
            if ($hkcRegionObj && !$hkcRegionObj->isEmpty()) {
                $hkcRegion = $hkcRegionObj->toArray();
            }
            if (empty($hkcRegion) || $hkcRegion['del_time'] > 0) {
                $returnArr['err_code'] = 1;
                $returnArr['tipmsg'] = '设备所属充电区不存在，无法充电！';
                return $returnArr;
            }
            $common_id = $hkcRegion['common_id'];
            if (empty($common_id)) {
                $returnArr['err_code'] = 1;
                $returnArr['tipmsg'] = '设备所属充电区费率套餐未设置，无法充电！';
                return $returnArr;
            }
            $houseVillageHaokuaichongRateDb = new HouseVillageHaokuaichongRate();
            $whereRate = array('rate_id' => $common_id, 'village_id' => $equipmentInfo['village_id']);
            $hkcRateObj = $houseVillageHaokuaichongRateDb->getOneData($whereRate, '*');
            $hkcRate = array();
            if ($hkcRateObj && !$hkcRateObj->isEmpty()) {
                $hkcRate = $hkcRateObj->toArray();
            }
            if (empty($hkcRate) || $hkcRate['del_time'] > 0) {
                $returnArr['err_code'] = 1;
                $returnArr['tipmsg'] = '设备所属充电区费率套餐不存在，无法充电！';
                return $returnArr;
            }
            $work_type = 0;
            if ($hkcRate['rate_type'] == 'B1') {
                //计时
                $work_type = 1;
            } else if ($hkcRate['rate_type'] == 'B2') {
                //计量
                $work_type = 2;
            }
            $price_standard_list = $hkcRate['rate_detail'] ? json_decode($hkcRate['rate_detail'], true) : array();
            //查询消费标准
            $equipment_name = $equipmentInfo['equipment_name'];
            //计时模式
            if ($work_type == 1) {
                if (!empty($cardUserInfo)) {
                    $card = 1;
                    $info = [
                        'socket_num' => $socket,
                        'standard_value' => $price_standard_list[0]['standard'],
                        'surplus_num' => $cardUserInfo['surplus_num'],
                    ];
                    $times = ceil($cardUserInfo['per_price'] / $price_standard_list[0]['standard']);
                    $cardUserInfo['priceNanme'] = '预计充电时长' . $times . '小时';
                    $attribute['cardUserInfo'] = $cardUserInfo;

                } else {
                    $noticeList = array();
                    $standard_list = array();
                    foreach ($price_standard_list as $dvv) {
                        $noticeList[] = $dvv['minPower'] . '瓦 到' . $dvv['maxPower'] . '瓦 约' . $dvv['standard'] . '小时/元';

                    }
                    $standardPrice = $price_standard_list[0]['standard'];
                    for ($ii = 1; $ii < 7; $ii++) {
                        $paramView = $ii / $standardPrice;
                        $paramView = ceil($paramView);
                        $tmpAtandard = array('priceView' => $ii, 'paramUnitName' => '小时', 'paramView' => $paramView, 'pricePay' => $ii);
                        $standard_list[] = $tmpAtandard;
                    }
                    $card = 2;
                    $info = [
                        'socket_num' => $socket,
                        'notice_list' => $noticeList,
                        'notice_info' => ['standard' => $price_standard_list[0]['standard'] . '元 1小时', 'standard_fee'=>$price_standard_list[0]['standard'] . '元 1小时','standard_tips'=>'', 'power' => $price_standard_list[0]['maxPower'] . '瓦'],
                        'standard_list' => $standard_list,
                    ];

                }
            }
            //计量模式
            if ($work_type == 2) {
                if (!empty($cardUserInfo)) {
                    $card = 1;
                    $info = [
                        'socket_num' => $socket,
                        'standard_value' => $price_standard_list[0]['standard'],
                        'surplus_num' => $cardUserInfo['surplus_num'],
                    ];
                    $times = ceil($cardUserInfo['per_price'] / $price_standard_list[0]['standard']);
                    $cardUserInfo['priceNanme'] = '预计充电电量' . $times . '度';
                    $attribute['cardUserInfo'] = $cardUserInfo;
                } else {
                    $card = 2;
                    $noticeList[] = $price_standard_list[0]['standard'] . '元 1度电';
                    $standard_list = array();
                    $standardPrice = $price_standard_list[0]['standard'];
                    for ($ii = 1; $ii < 7; $ii++) {
                        $paramView = $ii / $standardPrice;
                        $paramView = ceil($paramView);
                        $tmpAtandard = array('priceView' => $ii, 'paramUnitName' => '度', 'paramView' => $paramView, 'pricePay' => $ii);
                        $standard_list[] = $tmpAtandard;
                    }
                    $info = [
                        'notice_info' => ['standard' => '', 'standard_fee'=>'','standard_tips'=>'', 'power' => ''],
                        'socket_num' => $socket,
                        'notice_list' => $noticeList,
                        'standard_list' => $standard_list,
                    ];
                }
                $work_type = $work_type + 1;
            }

            $attribute['equipment_name'] = $equipment_name;
            $attribute['work_type'] = $work_type;
            $attribute['card_info'] = $card;
            $attribute['socket_info'] = $info;
            $attribute['advertisement'] = $this->adverList;
        } else {
            if ($equipmentInfo['status'] != 1) {
                //todo 充电桩故障写入
                invoke_cms_model('House_maintenan/faultLog', ['type' => 2, 'device_id' => $equipmentInfo['id'], 'reason' => '当前设备已离线'], true);
                $returnArr['err_code'] = 1;
                $returnArr['tipmsg'] = '当前设备已离线，无法充电';
                return $returnArr;
            }
            if ($socket) {
                $socket_arr = json_decode($equipmentInfo['port_status']);
                if (!empty($socket_arr)) {
                    $status = 0;
                    foreach ($socket_arr as $k => $value) {
                        if ($k == $socket - 1) {
                            $status = $value;
                            break;
                        }
                    }
                    //判断插座状态
                    if ($status != 0) {
                        $url = $this->getChargeOrderInfo($id, $socket, $uid);
                        if (empty($url)) {
                            $returnArr['err_code'] = 1;
                            $returnArr['tipmsg'] = '当前插座不能充电，请更换插座';
                            return $returnArr;
                        } else {
                            $returnArr['err_code'] = 0;
                            $returnArr['tipmsg'] = '';
                            $returnArr['data_info']['url'] = $url;
                            return $returnArr;
                        }
                    }
                } else {
                    $returnArr['err_code'] = 1;
                    $returnArr['tipmsg'] = '当前插座不能充电，请更换插座';
                    return $returnArr;
                }
            }
            if ($uid > 0) {
                //查询月卡
                $service_Card_User = new HouseVillagePileMonthCardUser();
                $where[] = ['uid', '=', $uid];
                $where[] = ['status', '=', 1];
                $where[] = ['expire_time', '>', time()];
                $where[] = ['surplus_num', '>', 0];
                $cardUserInfo = $service_Card_User->getOne($where);

                if (!empty($cardUserInfo)) {
                    //查询月卡
                    $service_Card = new HouseVillagePileMonthCard();
                    $where1 = ['id' => $cardUserInfo['card_id']];
                    $cardInfo = $service_Card->getOne($where1);
                    if (!empty($cardInfo) && $cardInfo['village_id'] != $equipmentInfo['village_id']) {
                        $cardUserInfo = [];
                    } else {
                        $service_order = new HouseVillagePilePayOrder();
                        $where_order = [
                            ['uid', '=', $uid],
                            ['pay_type', '=', 3],
                            ['pay_time', '>', strtotime(date('Y-m-d 00:00:00'))],
                        ];
                        $count = $service_order->get_count($where_order);
                        if ($count >= $cardUserInfo['max_num']) {
                            $cardUserInfo = [];
                        }
                    }

                }
            }
            $service_Equipment_Attribute = new HouseVillagePileEquipmentAttribute();

            $attributeId = $service_Equipment_Attribute->getOne(['equipment_id' => $equipmentInfo['id'], 'attribute_id' => 1, 'is_del' => 1]);
            $attributeInfo = [];
            $service_Attribute_Set = new HouseVillagePileAttributeSet();
            if (!empty($attributeId)) {

                $attributeInfo = $service_Attribute_Set->getOne(['id' => $attributeId['attribute_standard_id'], 'status' => 1, 'is_del' => 1]);
            }
            if (empty($attributeInfo) || !isset($attributeInfo['standard_value']) || empty($attributeInfo['standard_value'])) {
                $service_Attribute = new HouseVillagePileAttribute();
                $attributeInfo = $service_Attribute->getOne(['value' => 1, 'type' => 2]);
                $price_standard_list = unserialize($attributeInfo['default_value']);
            } else {
                $price_standard_list = unserialize($attributeInfo['standard_value']);
            }
            if (empty($price_standard_list)) {
                $returnArr['err_code'] = 1;
                $returnArr['tipmsg'] = '消费标准为空';
                return $returnArr;

            }
            $equipment_name = $equipmentInfo['equipment_name'];
            $work_type = $equipmentInfo['work_type'];
            $standard_fee=$price_standard_list[0][2];
            $service_fee=0;
            if(isset($price_standard_list['ffee']) && $price_standard_list['ffee'][2]){
                $standard_fee=$standard_fee+$price_standard_list['ffee'][2];
                $standard_fee=round($standard_fee,2);
                $service_fee=$price_standard_list['ffee'][2];
            }
            
            //计时模式
            if ($work_type == 1) {
                $noticeList = [];
                $noticeNewList=array();
                $attributePowerId = $service_Equipment_Attribute->getOne(['equipment_id' => $equipmentInfo['id'], 'attribute_id' => 2, 'is_del' => 1]);
                $PowerInfo = [];
                if (!empty($attributePowerId)) {
                    $PowerInfo = $service_Attribute_Set->getOne(['id' => $attributePowerId['attribute_standard_id'], 'status' => 1, 'is_del' => 1]);
                }
                if($standard_fee>1){
                    $times = $price_standard_list[1][2] / $standard_fee; // 小时/元
                }else{
                    $times = $price_standard_list[1][2]; 
                }
                if (!empty($PowerInfo)) {
                    $PowerInfo = unserialize($PowerInfo['standard_value']);
                } else {
                    $service_Attribute = new HouseVillagePileAttribute();
                    $PowerInfo = $service_Attribute->getOne(['value' => 2, 'type' => 2]);
                    $PowerInfo = unserialize($PowerInfo['default_value']);
                }
                if($PowerInfo){
                    $noticeList = [
                        0 => '0-' . $PowerInfo[0][2] . 'W 约' . round($times, 1) . '小时/元',
                        1 => $PowerInfo[0][2] + 1 . '-' . $PowerInfo[0][2] * 2 . 'W 约' . round($times / 2, 1) . '小时/元',
                        2 => $PowerInfo[0][2] * 2 + 1 . '-' . $PowerInfo[0][2] * 3 . 'W 约' . round($times / 3, 1) . '小时/元',
                        3 => $PowerInfo[0][2] * 3 + 1 . '-' . $PowerInfo[0][2] * 4 . 'W 约' . round($times / 4, 1) . '小时/元',
                    ];
                    
                    $tpsyuan=($standard_fee>1 ? 1:$standard_fee) .'元(含服务费'.$service_fee.'元)';
                    
                    $noticeNewList[]=array('power'=>'1 - '.$PowerInfo[0][2],'fee'=>$tpsyuan.'/'. round($times, 1). '小时');
                    $noticeNewList[]=array('power'=>($PowerInfo[0][2] + 1).' - '.$PowerInfo[0][2] * 2,'fee'=>$tpsyuan.'/'. round($times / 2, 1). '小时');
                    $noticeNewList[]=array('power'=>($PowerInfo[0][2]*2 + 1).' - '.$PowerInfo[0][2] * 3,'fee'=>$tpsyuan.'/'. round($times / 3, 1). '小时');
                    $noticeNewList[]=array('power'=>($PowerInfo[0][2]*3 + 1).' - '.$PowerInfo[0][2] * 4,'fee'=>$tpsyuan.'/'. round($times / 4, 1). '小时');
                }
                $price_standard_list[0][2]=$standard_fee;
                if ($cardUserInfo) {
                    $card = 1;
                    $info = [
                        'socket_num' => $socket,
                        'standard_value' => $price_standard_list[1][2],
                        'notice_info' => ['standard' => $standard_fee . '元' . $price_standard_list[1][2] . '小时','standard_fee'=>$standard_fee,'htime'=>$price_standard_list[1][2],'standard_tips'=>'(含服务费)', 'power' => $PowerInfo[0][2] . 'W'],
                        'notice_new_list'=>$noticeNewList,
                        'surplus_num' => $cardUserInfo['surplus_num'],
                    ];
                    $times = ceil($cardUserInfo['per_price'] / $standard_fee * $price_standard_list[1][2]);
                    $cardUserInfo['priceNanme'] = '预计充电时长' . $times . '小时';
                    $attribute['cardUserInfo'] = $cardUserInfo;

                } else {
                    $card = 2;
                    $info = [
                        'socket_num' => $socket,
                        'notice_list' => $noticeList,
                        'notice_new_list'=>$noticeNewList,
                        'notice_info' => ['standard' => $standard_fee . '元' . $price_standard_list[1][2] . '小时','standard_fee'=>$standard_fee,'htime'=>$price_standard_list[1][2],'standard_tips'=>'(含服务费)', 'power' => $PowerInfo[0][2] . 'W'],
                        'standard_list' => [
                            0 => ['priceView' => round_number($price_standard_list[0][2], 2),
                                'paramUnitName' => '小时',
                                'paramView' => round_number($price_standard_list[1][2], 2),
                                'pricePay' => round_number($price_standard_list[0][2], 2)],
                            1 => ['priceView' => round_number($price_standard_list[0][2] * 2, 2),
                                'paramUnitName' => '小时',
                                'paramView' => round_number($price_standard_list[1][2] * 2, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 2, 2)],
                            2 => ['priceView' => round_number($price_standard_list[0][2] * 3, 2),
                                'paramUnitName' => '小时',
                                'paramView' => round_number($price_standard_list[1][2] * 3, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 3, 2)],
                            3 => ['priceView' => round_number($price_standard_list[0][2] * 4, 2),
                                'paramUnitName' => '小时',
                                'paramView' => round_number($price_standard_list[1][2] * 4, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 4, 2)],
                            4 => ['priceView' => round_number($price_standard_list[0][2] * 5, 2),
                                'paramUnitName' => '小时',
                                'paramView' => round_number($price_standard_list[1][2] * 5, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 5, 2)],
                            5 => ['priceView' => round_number($price_standard_list[0][2] * 6, 2),
                                'paramUnitName' => '小时',
                                'paramView' => round_number($price_standard_list[1][2] * 6, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 6, 2)],
                        ],
                    ];
                }
            }
            //计量模式
            if ($work_type == 2) {
                $noticeNewList=array();
                $noticeNewList[]=array('time_span'=>'00:00-23:59','fee'=>$price_standard_list[0][2].'元','service_fee'=>$service_fee.'元','total_fee'=>$standard_fee.'元');
                $price_standard_list[0][2]=$standard_fee;
                $standard_fee_per=$standard_fee;
                if($price_standard_list[2][2]>0){
                    $standard_fee_per=$standard_fee/$price_standard_list[2][2];
                    $standard_fee_per=round($standard_fee_per,2);
                }
                if ($cardUserInfo) {
                    $card = 1;
                    $info = [
                        'socket_num' => $socket,
                        'notice_info' => ['standard' => $standard_fee.'元'.$price_standard_list[2][2].'度','standard_fee'=>$standard_fee_per,'standard_tips'=>'（含服务费）', 'power' => ''],
                        'notice_new_list'=>$noticeNewList,
                        'standard_value' => $price_standard_list['data']['coinParam'],
                        'surplus_num' => $cardUserInfo['surplus_num'],
                    ];
                    $times = ceil($cardUserInfo['per_price'] / $price_standard_list[0][2] * $price_standard_list[2][2]);
                    $cardUserInfo['priceNanme'] = '预计充电电量' . $times . '度';
                    $attribute['cardUserInfo'] = $cardUserInfo;
                } else {
                    $card = 2;
                    $info = [
                        'notice_info' => ['standard' => $standard_fee.'元'.$price_standard_list[2][2].'度','standard_fee'=>$standard_fee_per,'standard_tips'=>'（含服务费）', 'power' => ''],
                        'socket_num' => $socket,
                        'notice_list' => $price_standard_list['data']['noticeList'] ? $price_standard_list['data']['noticeList']:array(),
                        'notice_new_list'=>$noticeNewList,
                        'standard_list' => [
                            0 => ['priceView' => round_number($price_standard_list[0][2], 2),
                                'paramUnitName' => '度',
                                'paramView' => round_number($price_standard_list[2][2], 2),
                                'pricePay' => round_number($price_standard_list[0][2], 2)],
                            1 => ['priceView' => round_number($price_standard_list[0][2] * 2, 2),
                                'paramUnitName' => '度',
                                'paramView' => round_number($price_standard_list[2][2] * 2, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 2, 2)],
                            2 => ['priceView' => round_number($price_standard_list[0][2] * 3, 2),
                                'paramUnitName' => '度',
                                'paramView' => round_number($price_standard_list[2][2] * 3, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 3, 2)],
                            3 => ['priceView' => round_number($price_standard_list[0][2] * 4, 2),
                                'paramUnitName' => '度',
                                'paramView' => round_number($price_standard_list[2][2] * 4, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 4, 2)],
                            4 => ['priceView' => round_number($price_standard_list[0][2] * 5, 2),
                                'paramUnitName' => '度',
                                'paramView' => round_number($price_standard_list[2][2] * 5, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 5, 2)],
                            5 => ['priceView' => round_number($price_standard_list[0][2] * 6, 2),
                                'paramUnitName' => '度',
                                'paramView' => round_number($price_standard_list[2][2] * 6, 2),
                                'pricePay' => round_number($price_standard_list[0][2] * 6, 2)],
                        ],

                    ];
                }
                $work_type = $work_type + 1;
            }
            $attribute['equipment_name'] = $equipment_name;
            $attribute['work_type'] = $work_type;
            $attribute['card_info'] = $card;
            $attribute['socket_info'] = $info;
            $attribute['advertisement'] = $this->adverList;
        }

        $returnArr['err_code'] = 0;
        $returnArr['tipmsg'] = '';
        $returnArr['data_info']=$attribute;
        return $returnArr;
    }
    
    /**
     *获取设备工作详情页
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/26 19:42
     */
    public function getEquipmentwork($uid, $id, $socket)
    {
        //查询设备基本信息
        $service_equipment = new HouseVillagePileEquipment();

        $equipmentInfo = $service_equipment->getInfo(['id' => $id, 'is_del' => 1]);
        if (empty($equipmentInfo)) {
            throw new \think\Exception("当前设备不存在");
        }
        if ($equipmentInfo['socket_num'] < $socket) {
            throw new \think\Exception("当前设备插座号不存在");
        }
        $tmpData=$this->getEquipmentDetailInfo($equipmentInfo,$uid,$socket);
        if($tmpData['err_code']==1 && $tmpData['tipmsg']){
            throw new \think\Exception($tmpData['tipmsg']);
        }else{
            $attribute=$tmpData['data_info'];
        }
        //todo 充电桩故障解除
        invoke_cms_model('House_maintenan/normalLog',['type'=>2,'device_id'=>$equipmentInfo['id']], true);
        $attribute['equipment_type']=$equipmentInfo['type'];
        $attribute['url'] = '';
        $attribute['village_id'] = $equipmentInfo['village_id'];
        $attribute['have_user_balance_pay']=0;
        $attribute['user_pile_balance']=0;
        $configCustomizationService=new ConfigCustomizationService();
        $pile_charge_consume_point=$configCustomizationService->getPileChargeConsumePoint();
        if($pile_charge_consume_point){
            $attribute['have_user_balance_pay']=1;
            $userBalanceInfo=$this->getPileUserBalance($uid);
            $attribute['user_pile_balance']=$userBalanceInfo['current_money'];
        }
        return $attribute;
    }

    /**
     * 获取月卡列表
     * @author:zhubaodi
     * @date_time: 2021/4/26 19:49
     */
    public function getCardList($uid, $id)
    {
        $service_equipment = new HouseVillagePileMonthCard();
        $monthCardList = $service_equipment->getList(['is_del' => 1, 'status' => 1]);
        $service_Card_User = new HouseVillagePileMonthCardUser();
        $where[] = ['uid', '=', $uid];
        $where[] = ['status', '=', 1];
        $where[] = ['expire_time', '>', time()];
        $cardUserInfo = $service_Card_User->getOne($where);
        if ($cardUserInfo) {
            $cardUserInfo['expire_time'] = date('Y-m-d', $cardUserInfo['expire_time']);
        }

        //查询设备基本信息
        $service_equipment = new HouseVillagePileEquipment();
        $equipmentInfo = $service_equipment->getInfo(['id' => $id]);
        $village_name = '';
        if ($equipmentInfo) {
            if (!empty($cardUserInfo)&&$cardUserInfo['village_id']!=$equipmentInfo['village_id']){
                $cardUserInfo=[];
            }
            $service_village = new HouseVillage();
            $village_name = $service_village->getOne($equipmentInfo['village_id'], 'village_id,village_name');
            if ($equipmentInfo['type'] == 1) {
                //查询消费标准
                $price_standard_list = $this->getPriceStandard($equipmentInfo['equipment_num']);
                if (!empty($monthCardList)) {
                    $monthCardList = $monthCardList->toArray();
                    $priceStandardUniteList = isset($price_standard_list['data']['priceStandardUniteList']) ? $price_standard_list['data']['priceStandardUniteList'] : [];
                    if (!empty($monthCardList)) {
                        $viewType = isset($price_standard_list['data']['viewType']) ? intval($price_standard_list['data']['viewType']) : 0;
                        $type = isset($price_standard_list['data']['type']) ? intval($price_standard_list['data']['type']) : 0;
                        foreach ($monthCardList as $k => $val) {
                            if ($val['village_id']!=$equipmentInfo['village_id']){
                                unset($monthCardList[$k]);continue;
                            }
                            $val['days'] = $val['use_month'] * 30;
                            $val['countPrice'] = $val['all_num'] * $val['per_price'];
                            $val['discount'] = round_number($val['price'] / $val['countPrice'], 2) * 10;
                            $per_price = $val['per_price'];
                            $tipParamView = 0;
                            $tipPricePay = 0;
                            $tipParamUnitName = '';
                            $priceUnitName = '';
                            foreach ($priceStandardUniteList as $key => $itemVal) {
                                $paramView = isset($itemVal['paramView']) ? $itemVal['paramView'] : 0;
                                $pricePay = isset($itemVal['pricePay']) ? $itemVal['pricePay'] : 0;
                                $paramUnitName = isset($itemVal['paramUnitName']) ? $itemVal['paramUnitName'] : '';
                                if (floatval($per_price) >= floatval($pricePay) && floatval($pricePay) >= floatval($tipPricePay)) {
                                    // 月卡金额大于收费标准费用梯度 + 当前梯度金额大于之前赋值金额
                                    $tipPricePay = $pricePay;
                                    $tipParamView = $paramView;
                                    $tipParamUnitName = $paramUnitName;
                                    $priceUnitName = isset($itemVal['priceUnitName']) ? $itemVal['priceUnitName'] : '';
                                    $paramUnitName && $priceUnitName = str_replace('(Poweroff when full)', '', $priceUnitName);
                                    $paramUnitName && $priceUnitName = str_replace('(Charge by power)', '', $priceUnitName);
                                }
                            }
                            if ($viewType == 1) {
                                $val['priceName'] = '预计每次充电时长' . $tipParamView . $tipParamUnitName;
                            }
                            if ($viewType == 2 && $type == 2) {
                                $val['priceName'] = '每次充电金额' . $val['per_price'] . '元';
                                $priceUnitName && $val['priceName'] .= "($priceUnitName)";
                            }
                            if ($viewType == 2 && $type == 3) {
                                $val['priceName'] = '预计每次充电电量' . $tipParamView . $tipParamUnitName;
                            }
                            $monthCardList[$k] = $val;
                        }
                    }
                }
            } else {
                //查询消费标准
                $service_Equipment_Attribute = new HouseVillagePileEquipmentAttribute();
                $attributeId = $service_Equipment_Attribute->getOne(['equipment_id' => $equipmentInfo['id'], 'attribute_id' => 1, 'is_del' => 1]);
                $attributeInfo = [];
                if (!empty($attributeId)) {
                    //pigcms_house_village_pile_attribute_set
                    $service_Attribute_Set = new HouseVillagePileAttributeSet();
                    $attributeInfo = $service_Attribute_Set->getOne(['id' => $attributeId['attribute_standard_id'], 'status' => 1]);
                }
                /*if (empty($attributeInfo)) {
                    throw new \think\Exception("消费标准为空");
                }*/
                if (empty($attributeInfo)) {
                    $service_Attribute = new HouseVillagePileAttribute();
                    $attributeInfo = $service_Attribute->getOne(['value' => 1, 'type' => 2]);
                    $price_standard_list = unserialize($attributeInfo['default_value']);
                } else {
                    $price_standard_list = unserialize($attributeInfo['standard_value']);
                }
                $work_type = $attributeInfo['type'];
                $standard_fee=$price_standard_list[0][2];
                $service_fee=0;
                if(isset($price_standard_list['ffee']) && $price_standard_list['ffee'][2]){
                    $standard_fee=$standard_fee+$price_standard_list['ffee'][2];
                    $standard_fee=round($standard_fee,2);
                    $service_fee=$price_standard_list['ffee'][2];
                }
                if (!empty($monthCardList)) {
                    $monthCardList = $monthCardList->toArray();
                    if (!empty($monthCardList)) {
                        foreach ($monthCardList as $k => $val) {
                            if ($val['village_id']!=$equipmentInfo['village_id']){
                                unset($monthCardList[$k]);continue;
                            }
                            $val['days'] = $val['use_month'] * 30;
                            $val['countPrice'] = $val['all_num'] * $val['per_price'];
                            $val['discount'] = round_number($val['price'] / $val['countPrice'], 2) * 10;

                            if ($work_type == 0) {
                                $times = ceil(($val['per_price'] / $standard_fee) * $price_standard_list[1][2]);
                                $val['priceName'] = '预计每次充电时长' . $times . '小时';
                            }
                            if ($work_type == 2) {
                                $times = ceil(($val['per_price'] /$standard_fee) * $price_standard_list[2][2]);
                                $val['priceName'] = '预计每次充电电量' . $times . $price_standard_list[2][3];
                            }
                            $monthCardList[$k] = $val;
                        }
                    }
                }
            }
        }


        $arr = [];
        if (!empty($monthCardList)){
            $arr['monthCardList'] = array_values($monthCardList);
        }else{
            $arr['monthCardList'] = $monthCardList;
        }
        $arr['cardUserInfo'] = $cardUserInfo;
        $arr['village_name'] = $village_name['village_name'];
        return $arr;

    }

    /**
     * 获取购买的月卡列表
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/27 13:09
     */
    public function getOrderCardList($uid, $page, $limit)
    {

        $user = new User();
        $userInfo = $user->getOne(['uid' => $uid]);
        if (!$userInfo) {
            throw new \think\Exception("该用户不存在！");
        }
        if (empty($page)) {
            $page = 1;
        }
        $arr = [];
        $service_Card_User = new HouseVillagePileMonthCardUser();
        $cardUserList = $service_Card_User->getList(['uid' => $uid, 'status' => [1, 2]], '*', $page, $limit);
        $service_Card = new HouseVillagePileMonthCard();
        $cardList = $service_Card->getList([]);
        if (!empty($cardUserList) && !empty($cardList)) {
            $cardUserList = $cardUserList->toArray();
            $cardList = $cardList->toArray();
            if (!empty($cardUserList) && !empty($cardList)) {
                foreach ($cardUserList as $k => $value) {
                    foreach ($cardList as $val) {
                        if ($val['id'] == $value['card_id']) {
                            $value['name'] = $val['card_name'];
                        }
                    }
                    if ($value['expire_time'] > time()&&$value['status']==1) {
                        $value['status'] = 1;
                    } else {
                        $value['status'] = 2;
                    }
                    $value['expire_time'] = date('Y-m-d', $value['expire_time']);
                    $cardUserList[$k] = $value;
                }
            }
        }
        $arr['cardUserList'] = $cardUserList;
        return $arr;
    }


    /**
     * 使用月卡充电
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/27 13:09
     */
    public function payOrderCard($data)
    {
        $user = new User();
        $userInfo = $user->getOne(['uid' => $data['uid']]);
        if (!$userInfo) {
            throw new \think\Exception("该用户不存在！");
        }
        $service_equipment = new HouseVillagePileEquipment();
        $equipment_info = $service_equipment->getInfo(['id' => $data['id']]);
        if (!$equipment_info) {
            throw new \think\Exception("设备不存在！");
        }
        $service_Card_User = new HouseVillagePileMonthCardUser();
        $cardUserInfo = $service_Card_User->getOne(['id' => $data['card_id']]);
        if (!$cardUserInfo) {
            throw new \think\Exception("请先购买月卡！");
        }
        if ($cardUserInfo['surplus_num'] < 1) {
            throw new \think\Exception("月卡次数已用完！");
        }
        $service_order = new HouseVillagePilePayOrder();
        $where_order = [
            ['uid', '=', $data['uid']],
            ['pay_type', '=', 3],
            ['pay_time', '>', strtotime(date('Y-m-d 00:00:00'))],
        ];
        if($equipment_info['type']==3){
            $where_order[]= ['type', '=', 3];
        }
        $count = $service_order->get_count($where_order);
        if ($count >= $cardUserInfo['max_num']) {
            throw new \think\Exception("今日月卡使用次数已达到上限！");
        }

        $type = 1;
        if ($equipment_info['type'] == 2) {
            $type = 2;

            $order_pile = [
                'village_id' => $equipment_info['village_id'],
                'order_type' => 1,
                'order_no' => mt_rand(1000000, 9999999) . $data['uid'] . substr(time(), 2, 9 - strlen($data['uid'])),
                'socket_no' => $data['portNum'],
                'equipment_id' => $equipment_info['id'],
                'use_money' => $data['price'],
                'uid' => $data['uid'],
                'pay_type' => 3,
                'type' => $type,
                'card_use_money'=>$cardUserInfo['per_price'],
                'add_time' => time(),
                'pay_time' => time(),
            ];

        }
        elseif ($equipment_info['type'] == 21){
            $type = 21;
            $portNum=$data['portNum']-1;
            $portNum=$portNum<0 ? 0:$portNum;
            if ($portNum<=9){
                $portNum='0'.$portNum;
            }
            if (strlen($equipment_info['equipment_sn'])==8){
                $equipment_serial='00'.$equipment_info['equipment_sn'];
            }else{
                $equipment_serial=$equipment_info['equipment_sn'];
            }
            $order_pile = [
                'village_id' => $equipment_info['village_id'],
                'order_type' => 1,
                'order_no' => date('ymdhis').mt_rand(10000000,99999999).$equipment_serial.$portNum,
                'socket_no' => $data['portNum'],
                'equipment_id' => $equipment_info['id'],
                'use_money' => $data['price'],
                'uid' => $data['uid'],
                'pay_type' => 3,
                'type' => $type,
                'card_use_money'=>$cardUserInfo['per_price'],
                'add_time' => time(),
                'pay_time' => time(),
            ];
        }else if($equipment_info['type'] == 3){
            $type=3;
            $portNum=$data['portNum']-1;
            $portNum=$portNum<0 ? 0:$portNum;
            $tmpPortNum=$portNum;
            if ($portNum<=9){
                $portNum='0'.$portNum;
            }
            $area_id=$equipment_info['area_id'];
            if(empty($area_id)){
                throw new \think\Exception("设备所属充电区不存在，无法充电！");
            }
            $houseVillageHaokuaichongRegionDb = new HouseVillageHaokuaichongRegion();
            $whereRegion=array('area_id'=>$area_id,'village_id'=>$equipment_info['village_id']);
            $hkcRegionObj=$houseVillageHaokuaichongRegionDb->getOneData($whereRegion,'*');
            $hkcRegion=array();
            if($hkcRegionObj && !$hkcRegionObj->isEmpty()){
                $hkcRegion=$hkcRegionObj->toArray();
            }
            if(empty($hkcRegion) || $hkcRegion['del_time']>0){
                throw new \think\Exception("设备所属充电区不存在，无法充电！");
            }
            $common_id=$hkcRegion['common_id'];
            if(empty($common_id)){
                throw new \think\Exception("设备所属充电区费率套餐未设置，无法充电！");
            }
            $socketList = $equipment_info['port_status'] ? json_decode($equipment_info['port_status'], true) : array();
            //实时查设备的充电端口状态
            $param = array('village_id' => $equipment_info['village_id'], 'equipment_num' => $equipment_info['equipment_num'], 'equipment_id' => $equipment_info['id']);
            $res = invoke_cms_model('House_village_pile_equipment/getType3EquipmentDetail', $param);
            if($res && isset($res['retval']) && !empty($res['retval']) &&  isset($res['retval']['data']) && !empty($res['retval']['data'])){
                $portState=$res['retval']['data']['portState'];
                $port_status=$portState ? explode(',',$portState):'';
                if($port_status){
                    $socketList=$port_status;
                }
            }
            $portStatusErr='';
            foreach ($socketList as $kk=>$dks){
                if($kk==$tmpPortNum && $dks==0){
                   // $portStatusErr='充电端口未检测到充电器插入，请先插上充电器！';
                    // break;
                }elseif ($kk==$tmpPortNum && in_array($dks,[1,3,5,170])){
                    $portStatusErr='充电端口已经在充电中，请选择其他端口充电！';
                    break;
                }elseif ($kk==$tmpPortNum && $dks!=2){
                    $portStatusErr='充电端口故障无法充电，请选择其他端口充电！';
                    break;
                }
            }
            if($portStatusErr){
                throw new \think\Exception($portStatusErr);
            }
            $houseVillageHaokuaichongRateDb = new HouseVillageHaokuaichongRate();
            $whereRate=array('rate_id'=>$common_id,'village_id'=>$equipment_info['village_id']);
            $hkcRateObj=$houseVillageHaokuaichongRateDb->getOneData($whereRate,'*');
            $hkcRate=array();
            if($hkcRateObj && !$hkcRateObj->isEmpty()){
                $hkcRate=$hkcRateObj->toArray();
            }
            if(empty($hkcRate) || $hkcRate['del_time']>0){
                throw new \think\Exception("设备所属充电区费率套餐不存在，无法充电！");
            }
            $extra_data=array('equipment_num'=>$equipment_info['equipment_num'],'equipment_port'=>$portNum,'area_id'=>$area_id);
            $extra_data['haokuaichong_rate']=$hkcRate;
            $extra_data_str=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
            $order_no=date('YmdHis').'33'.mt_rand(100000,999999).$equipment_info['equipment_sn'].$portNum;
            $buy_ele=0;
            $buy_time=0;
            $rate_detail=json_decode($hkcRate['rate_detail'],true);
            
            if($hkcRate['rate_type']=='B2'){
                //计量
                $standard=$rate_detail && $rate_detail['0'] ? $rate_detail['0']['standard']:1;
                $standard=$standard>0 ? $standard:1;
                $buy_ele=$cardUserInfo['per_price']/$standard;
                $buy_ele=round($buy_ele,2);
            }elseif($hkcRate['rate_type']=='B1'){
                //计时
                $standard=$rate_detail && $rate_detail['0'] ? $rate_detail['0']['standard']:0.5;
                $buy_time=($cardUserInfo['per_price']/$standard);
                if($buy_time>18){
                    $buy_time=18;  //最大时长为18小时
                }
                $buy_time=$buy_time*3600;
                $buy_time=floor ($buy_ele);
            }
            $order_pile = [
                'village_id' => $equipment_info['village_id'],
                'order_type' => 1,
                'order_no' => $order_no,
                'socket_no' => $data['portNum'],
                'equipment_id' => $equipment_info['id'],
                'use_money' => $data['price'],
                'uid' => $data['uid'],
                'pay_type' => 3,
                'type' => $type,
                'card_use_money'=>$cardUserInfo['per_price'],
                'add_time' => time(),
                'pay_time' => time(),
                'buy_ele'=>$buy_ele,
                'buy_time'=>$buy_time,
                'extra_data'=>$extra_data_str,
            ];
        }
        else{
            $order_pile = [
                'village_id' => $equipment_info['village_id'],
                'order_type' => 1,
                'order_no' => build_real_orderid($data['uid']),
                'socket_no' => $data['portNum'],
                'equipment_id' => $equipment_info['id'],
                'use_money' => $data['price'],
                'uid' => $data['uid'],
                'pay_type' => 3,
                'type' => $type,
                'add_time' => time(),
                'pay_time' => time(),
            ];
        }
        $order_pile_id = $service_order->add_order($order_pile);
        $surplus_num = $cardUserInfo['surplus_num'] - 1;
        $service_Card_User->saveOne(['id' => $data['card_id']], ['surplus_num' => $surplus_num]);
        $res = '';
        if ($order_pile_id) {
            $templateNewsService = new TemplateNewsService();
            if (!empty($userInfo['openid'])) {
                $href = get_base_url('pages/village/smartCharge/monthCardDetails?mycardid=' . $cardUserInfo['id'] . '&cardId=' . $cardUserInfo['card_id'] . '&id=' . $data['id']);
                $datamsg = [
                    'tempKey' => 'OPENTM400166399',
                    'dataArr' => [
                        'href' => $href,
                        'wecha_id' => $userInfo['openid'],
                        'first' => '当前生效月卡剩余使用次数' . $surplus_num . '次',
                        'keyword1' => '月卡使用详情',
                        'keyword2' => '已发送',
                        'keyword3' => date('H:i'),
                        'remark' => '请点击查看详细信息！'
                    ]
                ];
                $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
            }

            if ($equipment_info['type'] == 2) {
                $res = $this->newPriceStandardCharge_tcp($order_pile_id);
            } elseif ($equipment_info['type'] == 21) {
                $res = $this->outCharge($order_pile_id, 2);
            }else if($equipment_info['type'] == 3){
                $param=array('village_id'=>$equipment_info['village_id'],'order_pile_id'=>$order_pile_id,'equipment_num'=>$equipment_info['equipment_num']);
                $invokeres = invoke_cms_model('House_village_pile_equipment/startType3Tocharge', $param);
                 fdump_api(['param'=>$param,'invokeres'=>$invokeres],'haokuaichong/startType3Tocharge',1);
                if($invokeres && isset($invokeres['retval']) && !empty($invokeres['retval']) &&  isset($invokeres['retval']['data'])){
                    $res=$invokeres['retval']['data'];
                }
            } else {
                $res = $this->newPriceStandardCharge($order_pile_id);
            }

        }
        $equipment_info['order_id'] = $order_pile_id;
        return $equipment_info;
    }
    /**
     *使用充电桩余额支付
    ***/
    public function useUserBalancePayOrder($data=array()){
        $user = new User();
        $userInfo = $user->getOne(['uid' => $data['uid']]);
        if (!$userInfo) {
            throw new \think\Exception("该用户不存在！");
        }
        $service_equipment = new HouseVillagePileEquipment();
        $equipment_info = $service_equipment->getInfo(['id' => $data['id']]);
        if (!$equipment_info || $equipment_info->isEmpty()) {
            throw new \think\Exception("设备不存在！");
        }
        $equipment_info=$equipment_info->toArray();
        $houseVillagePileUserBalance=new HouseVillagePileUserBalance();
        $whereArr=array(['uid','=',$data['uid']]);
        $tmpObj=$houseVillagePileUserBalance->getOneData($whereArr);
        $userSurplusBalance=0;
        $use_money_balance=0;
        $userBalance='';
        if($tmpObj && !$tmpObj->isEmpty()){
            $userBalance=$tmpObj->toArray();
            $userSurplusBalance=$userBalance['current_money'];
            $use_money_balance=$userBalance['use_money'];
        }
        if($userSurplusBalance<$data['price']){
            throw new \think\Exception("余额不足，请充值！");
        }
        $service_order = new HouseVillagePilePayOrder();
        $type = 1;
        if ($equipment_info['type'] == 2) {
            $type = 2;

            $order_pile = [
                'village_id' => $equipment_info['village_id'],
                'order_type' => 1,
                'order_no' => mt_rand(1000000, 9999999) . $data['uid'] . substr(time(), 2, 9 - strlen($data['uid'])),
                'socket_no' => $data['portNum'],
                'equipment_id' => $equipment_info['id'],
                'use_money' => $data['price'],
                'uid' => $data['uid'],
                'pay_type' => 5,
                'type' => $type,
                'add_time' => time(),
                'pay_time' => time(),
            ];

        }
        elseif ($equipment_info['type'] == 21){
            $type = 21;
            $portNum=$data['portNum']-1;
            $portNum=$portNum<0 ? 0:$portNum;
            if ($portNum<=9){
                $portNum='0'.$portNum;
            }
            if (strlen($equipment_info['equipment_sn'])==8){
                $equipment_serial='00'.$equipment_info['equipment_sn'];
            }else{
                $equipment_serial=$equipment_info['equipment_sn'];
            }
            $order_pile = [
                'village_id' => $equipment_info['village_id'],
                'order_type' => 1,
                'order_no' => date('ymdhis').mt_rand(10000000,99999999).$equipment_serial.$portNum,
                'socket_no' => $data['portNum'],
                'equipment_id' => $equipment_info['id'],
                'use_money' => $data['price'],
                'uid' => $data['uid'],
                'pay_type' => 5,
                'type' => $type,
                'add_time' => time(),
                'pay_time' => time(),
            ];
        }else if($equipment_info['type'] == 3){
            $type=3;
            $portNum=$data['portNum']-1;
            $portNum=$portNum<0 ? 0:$portNum;
            $tmpPortNum=$portNum;
            if ($portNum<=9){
                $portNum='0'.$portNum;
            }
            $area_id=$equipment_info['area_id'];
            if(empty($area_id)){
                throw new \think\Exception("设备所属充电区不存在，无法充电！");
            }
            $houseVillageHaokuaichongRegionDb = new HouseVillageHaokuaichongRegion();
            $whereRegion=array('area_id'=>$area_id,'village_id'=>$equipment_info['village_id']);
            $hkcRegionObj=$houseVillageHaokuaichongRegionDb->getOneData($whereRegion,'*');
            $hkcRegion=array();
            if($hkcRegionObj && !$hkcRegionObj->isEmpty()){
                $hkcRegion=$hkcRegionObj->toArray();
            }
            if(empty($hkcRegion) || $hkcRegion['del_time']>0){
                throw new \think\Exception("设备所属充电区不存在，无法充电！");
            }
            $common_id=$hkcRegion['common_id'];
            if(empty($common_id)){
                throw new \think\Exception("设备所属充电区费率套餐未设置，无法充电！");
            }
            $socketList = $equipment_info['port_status'] ? json_decode($equipment_info['port_status'], true) : array();
            //实时查设备的充电端口状态
            $param = array('village_id' => $equipment_info['village_id'], 'equipment_num' => $equipment_info['equipment_num'], 'equipment_id' => $equipment_info['id']);
            $res = invoke_cms_model('House_village_pile_equipment/getType3EquipmentDetail', $param);
            if($res && isset($res['retval']) && !empty($res['retval']) &&  isset($res['retval']['data']) && !empty($res['retval']['data'])){
                $portState=$res['retval']['data']['portState'];
                $port_status=$portState ? explode(',',$portState):'';
                if($port_status){
                    $socketList=$port_status;
                }
            }
            $portStatusErr='';
            foreach ($socketList as $kk=>$dks){
                if($kk==$tmpPortNum && $dks==0){
                    // $portStatusErr='充电端口未检测到充电器插入，请先插上充电器！';
                    // break;
                }elseif ($kk==$tmpPortNum && in_array($dks,[1,3,5,170])){
                    $portStatusErr='充电端口已经在充电中，请选择其他端口充电！';
                    break;
                }elseif ($kk==$tmpPortNum && $dks!=2){
                    $portStatusErr='充电端口故障无法充电，请选择其他端口充电！';
                    break;
                }
            }
            if($portStatusErr){
                throw new \think\Exception($portStatusErr);
            }
            $houseVillageHaokuaichongRateDb = new HouseVillageHaokuaichongRate();
            $whereRate=array('rate_id'=>$common_id,'village_id'=>$equipment_info['village_id']);
            $hkcRateObj=$houseVillageHaokuaichongRateDb->getOneData($whereRate,'*');
            $hkcRate=array();
            if($hkcRateObj && !$hkcRateObj->isEmpty()){
                $hkcRate=$hkcRateObj->toArray();
            }
            if(empty($hkcRate) || $hkcRate['del_time']>0){
                throw new \think\Exception("设备所属充电区费率套餐不存在，无法充电！");
            }
            $extra_data=array('equipment_num'=>$equipment_info['equipment_num'],'equipment_port'=>$portNum,'area_id'=>$area_id);
            $extra_data['haokuaichong_rate']=$hkcRate;
            $extra_data_str=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
            $order_no=date('YmdHis').'33'.mt_rand(100000,999999).$equipment_info['equipment_sn'].$portNum;
            $buy_ele=0;
            $buy_time=0;
            $rate_detail=json_decode($hkcRate['rate_detail'],true);

            if($hkcRate['rate_type']=='B2'){
                //计量
                $standard=$rate_detail && $rate_detail['0'] ? $rate_detail['0']['standard']:1;
                $standard=$standard>0 ? $standard:1;
                $buy_ele=$data['price']/$standard;
                $buy_ele=round($buy_ele,2);
            }elseif($hkcRate['rate_type']=='B1'){
                //计时
                $standard=$rate_detail && $rate_detail['0'] ? $rate_detail['0']['standard']:0.5;
                $buy_time=($data['price']/$standard);
                if($buy_time>18){
                    $buy_time=18;  //最大时长为18小时
                }
                $buy_time=$buy_time*3600;
                $buy_time=floor ($buy_ele);
            }
            $order_pile = [
                'village_id' => $equipment_info['village_id'],
                'order_type' => 1,
                'order_no' => $order_no,
                'socket_no' => $data['portNum'],
                'equipment_id' => $equipment_info['id'],
                'use_money' => $data['price'],
                'uid' => $data['uid'],
                'pay_type' => 5,
                'type' => $type,
                'add_time' => time(),
                'pay_time' => time(),
                'buy_ele'=>$buy_ele,
                'buy_time'=>$buy_time,
                'extra_data'=>$extra_data_str,
            ];
        }
        else{
            $order_pile = [
                'village_id' => $equipment_info['village_id'],
                'order_type' => 1,
                'order_no' => build_real_orderid($data['uid']),
                'socket_no' => $data['portNum'],
                'equipment_id' => $equipment_info['id'],
                'use_money' => $data['price'],
                'uid' => $data['uid'],
                'pay_type' => 5,
                'type' => $type,
                'add_time' => time(),
                'pay_time' => time(),
            ];
        }
        $order_pile_id = $service_order->add_order($order_pile);
        $res = '';
        if ($order_pile_id) {
            $houseVillagePileUserBalanceLog = new HouseVillagePileUserBalanceLog();
            $newBalance=$userSurplusBalance-$data['price'];
            $saveBalanceArr=array('village_id'=>$equipment_info['village_id'],'current_money'=>$newBalance);
            $saveBalanceArr['update_time']=time();
            $saveBalanceArr['use_money']=$data['price']+$use_money_balance;
            $houseVillagePileUserBalance->saveOneData(array('uid'=>$data['uid']),$saveBalanceArr);
            //添加使用记录
            $balanceLogArr=array('uid'=>$data['uid'],'village_id'=>$equipment_info['village_id']);
            $balanceLogArr['before_money']=$userSurplusBalance;
            $balanceLogArr['money']=$data['price'];
            $balanceLogArr['after_money']=$newBalance;
            $balanceLogArr['opt_type']=2;
            $balanceLogArr['opt_type_flag']='order_use_balance';
            $balanceLogArr['remarks']='使用余额充电';
            $balanceLogArr['add_time']=time();
            $balanceLogArr['pay_order_no']=$order_pile['order_no'];
            $balanceLogArr['equipment_id']=$equipment_info['id'];
            $balanceLogArr['pile_pay_order_id']=$order_pile_id;
            $balanceLogArr['extra_data']=$userBalance ? json_encode($userBalance,JSON_UNESCAPED_UNICODE):'';
            $idd=$houseVillagePileUserBalanceLog->addOneData($balanceLogArr);
            
            $templateNewsService = new TemplateNewsService();
            if (!empty($userInfo['openid'])) {
                $href='';
                $datamsg = [
                    'tempKey' => 'OPENTM400166399',
                    'dataArr' => [
                        'href' => $href,
                        'wecha_id' => $userInfo['openid'],
                        'first' => '使用充电桩余额充电',
                        'keyword1' => '你当前余额剩余'.round($newBalance,2).'元',
                        'keyword2' => '使用充电桩余额充电',
                        'keyword3' => date('H:i'),
                        'remark' => ''
                    ]
                ];
                $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
            }

            if ($equipment_info['type'] == 2) {
                $res = $this->newPriceStandardCharge_tcp($order_pile_id);
            } elseif ($equipment_info['type'] == 21) {
                $res = $this->outCharge($order_pile_id, 2);
            }else if($equipment_info['type'] == 3){
                $param=array('village_id'=>$equipment_info['village_id'],'order_pile_id'=>$order_pile_id,'equipment_num'=>$equipment_info['equipment_num']);
                $invokeres = invoke_cms_model('House_village_pile_equipment/startType3Tocharge', $param);
                fdump_api(['param'=>$param,'invokeres'=>$invokeres],'haokuaichong/startType3Tocharge',1);
                if($invokeres && isset($invokeres['retval']) && !empty($invokeres['retval']) &&  isset($invokeres['retval']['data'])){
                    $res=$invokeres['retval']['data'];
                }
            } else {
                $res = $this->newPriceStandardCharge($order_pile_id);
            }

        }
        $equipment_info['order_id'] = $order_pile_id;
        return $equipment_info;
    }
    /**
     * 开启充电
     * @author:zhubaodi
     * @date_time: 2021/4/28 19:29
     */
    public function newPriceStandardCharge($order_id, $status = [])
    {
        $service_order = new HouseVillagePilePayOrder();
        $order_info = $service_order->get_one(['id' => $order_id]);
        fdump_api([$order_id,$order_info],'pile/price_standard_list',1);

        try {
            if ($order_info) {
                if (!empty($order_info['order_serial']) && empty($status)){
                    return false;
                }
                $service_equipment = new HouseVillagePileEquipment();
                $templateNewsService = new TemplateNewsService();
                $equipment_info = $service_equipment->getInfo(['id' => $order_info['equipment_id']]);
                if (!$equipment_info) {
                    throw new \think\Exception("设备不存在！");
                }

                //获取插座状态
                $socket_arr = $this->deviceStatus($equipment_info['equipment_num'],0,$equipment_info['id']);
                if (!empty($socket_arr) && empty($status)) {
                    $status = $socket_arr[$order_info['socket_no']];
                    //判断插座状态
                    if ($status != 0) {
                        fdump_api(['err' => '当前插座不能充电，请更换插座','$socket_arr' => $socket_arr, 'equipment_num' => $equipment_info['equipment_num']],'pile/price_standard_list',1);
                        throw new \think\Exception("当前插座不能充电，请更换插座");
                    }
                }
                //查询消费标准
                $price_standard_list = $this->getPriceStandard($equipment_info['equipment_num']);

                if (empty($price_standard_list)) {
                    throw new \think\Exception("消费标准不存在");
                }
                if ($price_standard_list['data']['fullBlackout'] == 0) {
                    if ($order_info['pay_type'] == 3) {
                        $service_Card_User = new HouseVillagePileMonthCardUser();
                        $where = [['uid', '=', $order_info['uid']], ['expire_time', '>=', time()], ['status', '=', 1]];
                        $cardUserInfo = $service_Card_User->getOne($where);
                        if (empty($cardUserInfo)) {
                            throw new \think\Exception("月卡不存在");
                        }
                        $data = [
                            'token' => $this->lbbtechCenterToken,
                            'simId' => $equipment_info['equipment_num'],
                            'portNum' => $order_info['socket_no'],
                            'param' => round($cardUserInfo['per_price'] / $price_standard_list['data']['priceStandardUniteList'][0]['pricePay'] * $price_standard_list['data']['priceStandardUniteList'][0]['param']),
                            //  'payMoney'=>$price_standard_list['data']['priceStandardUniteList'][0]['priceView'],
                            'payMoney' => $cardUserInfo['per_price'],
                            'selectPriceType' => 1,
                            'infoPriceId' => $price_standard_list['data']['id'],
                        ];
                    } else {
                        $data = [
                            'token' => $this->lbbtechCenterToken,
                            'simId' => $equipment_info['equipment_num'],
                            'portNum' => $order_info['socket_no'],
                            'param' => round($order_info['use_money'] / $price_standard_list['data']['priceStandardUniteList'][0]['pricePay'] * $price_standard_list['data']['priceStandardUniteList'][0]['param']),
                            'payMoney' => $order_info['use_money'],
                            'selectPriceType' => 1,
                            'infoPriceId' => $price_standard_list['data']['id'],
                        ];
                    }
                } else {
                    if ($order_info['pay_type'] == 3) {
                        $service_Card_User = new HouseVillagePileMonthCardUser();
                        $where = [['uid', '=', $order_info['uid']], ['expire_time', '>=', time()], ['status', '=', 1]];
                        $cardUserInfo = $service_Card_User->getOne($where);
                        if (empty($cardUserInfo)) {
                            throw new \think\Exception("月卡不存在");
                        }
                        $data = [
                            'token' => $this->lbbtechCenterToken,
                            'simId' => $equipment_info['equipment_num'],
                            'portNum' => $order_info['socket_no'],
                            'param' => round($cardUserInfo['per_price'] / $price_standard_list['data']['priceStandardUniteList'][1]['pricePay'] * $price_standard_list['data']['priceStandardUniteList'][1]['param']),
                            //  'payMoney'=>$price_standard_list['data']['priceStandardUniteList'][0]['priceView'],
                            'payMoney' => $cardUserInfo['per_price'],
                            'selectPriceType' => 1,
                            'infoPriceId' => $price_standard_list['data']['id'],
                        ];
                    } else {
                        $data = [
                            'token' => $this->lbbtechCenterToken,
                            'simId' => $equipment_info['equipment_num'],
                            'portNum' => $order_info['socket_no'],
                            'param' => round($order_info['use_money'] / $price_standard_list['data']['priceStandardUniteList'][1]['pricePay'] * $price_standard_list['data']['priceStandardUniteList'][1]['param']),
                            'payMoney' => $order_info['use_money'],
                            'selectPriceType' => 1,
                            'infoPriceId' => $price_standard_list['data']['id'],
                        ];
                    }
                }
                // print_r($data);exit;
                //开启充电
                $url = $this->lbbtechCenterUrl . '/open/newPriceStandardCharge';
                if (empty($status)) {
                    $status = http_request($url, 'POST', http_build_query($data));
                }
                fdump_api([$status,$data,$order_id],'pile/price_standard_list',1);
                if ($status[0] == 200) {
                    $cache_key_clear = 'lvcchong_api_equipment_detail_'.$equipment_info['equipment_num'];
                    cache($cache_key_clear, null);
                    $standard = json_decode($status[1], true);
                    if ($standard && (!isset($standard['data']) || empty($standard['data']))){
                        $order_info_tmp = $service_order->get_one(['id' => $order_id],'id,order_serial');
                        if($order_info_tmp && isset($order_info_tmp['order_serial']) && !empty($order_info_tmp['order_serial'])){
                            fdump_api(['order_id'=>$order_id,'order_serial'=>$order_info_tmp['order_serial']],'pile/price_standard_list',1);
                            return true;
                        }
                        if($standard['error'] && strpos($standard['error'],'有订单正在启动中') && strpos($standard['error'],'请勿重复支付')){
                            return true;
                        }
                    }
                    if (!empty($standard['data']['orderNum']) && ($standard['success'] == 1 || $standard['success'] == true)) {
                        $queueData = ['standard' => $standard, 'execution_time' => $this->lvccQueryTimes, 'nowTime' => time()];
                        $job_id = $this->traitCommonLvCC($queueData, $this->lvccQueryTimes);
                        fdump_api(['job_id' => $job_id, 'queueData' => $queueData, 'lvccQueryTimes' => $this->lvccQueryTimes],'pile/price_standard_list',1);
                        $continued_time = 0;
                        $use_ele = 0;
                        if ($price_standard_list && isset($price_standard_list['data']['viewType']) && $price_standard_list['data']['viewType'] == 1) {
                            if ($order_info['pay_type'] == 3) {
                                if (empty($price_standard_list['data']['fullBlackout'])) {
                                    if ($price_standard_list['data']['priceStandardUniteList'][0]['paramUnit'] == 'min') {
                                        $continued_time = $price_standard_list['data']['priceStandardUniteList'][0]['paramView'] * 60;
                                    } else {
                                        $continued_time = $price_standard_list['data']['priceStandardUniteList'][0]['paramView'] * 3600;
                                    }
                                } else {
                                    if ($price_standard_list['data']['priceStandardUniteList'][1]['paramUnit'] == 'min') {
                                        $continued_time = $price_standard_list['data']['priceStandardUniteList'][1]['paramView'] * 60;
                                    } else {
                                        $continued_time = $price_standard_list['data']['priceStandardUniteList'][1]['paramView'] * 3600;
                                    }
                                }

                            } else {
                                foreach ($price_standard_list['data']['priceStandardUniteList'] as $val) {
                                    if ($val['pricePay'] == $order_info['use_money']) {
                                        if ($val['fullBlackoutForButton']==1){
                                            $continued_time = $val['param'] * 60;
                                        } elseif ($val['paramUnit'] == 'hour') {
                                            $continued_time = $val['paramView'] * 3600;
                                        } else {
                                            $continued_time = $val['paramView'] * 60;
                                        }
                                    }
                                }
                            }
                        }
                        if ($price_standard_list && isset($price_standard_list['data']['viewType']) && $price_standard_list['data']['viewType'] == 2&&$price_standard_list['data']['type'] == 2) {
                            if ($order_info['pay_type'] == 3) {
                                if ($price_standard_list['data']['priceStandardUniteList'][0]['priceView'] == $order_info['use_money']) {
                                    $use_ele = $price_standard_list['data']['priceStandardUniteList'][0]['paramView'];
                                }
                            } else {
                                foreach ($price_standard_list['data']['priceStandardUniteList'] as $val) {
                                    if ($val['pricePay'] == $order_info['use_money']) {
                                        $use_ele = $val['paramView'];
                                    }
                                }
                            }

                        }
                        fdump_api([$price_standard_list,$use_ele,$continued_time,$order_info['pay_type'],$order_info['use_money'],$order_info],'pile/price_standard_list',1);
                        if (!empty($continued_time)||!empty($order_info['buy_time'])) {
                            if (empty($continued_time)){
                                $continued_time= $order_info['buy_time'];
                            }
                            $data11 = [
                                'order_serial' => $standard['data']['orderNum'],
                                'continued_time' => $continued_time,
                                'buy_time' => $continued_time
                            ];
                        } elseif (!empty($use_ele)||!empty($order_info['buy_ele'])) {
                            if (empty($use_ele)){
                                $use_ele= $order_info['buy_ele'];
                            }
                            $data11 = [
                                'order_serial' => $standard['data']['orderNum'],
                                'use_ele' => $use_ele,
                                'buy_ele' => $use_ele
                            ];
                        } else {
                            $data11 = [
                                'order_serial' => $standard['data']['orderNum'],
                            ];
                        }
                        $service_order->saveOne(['id' => $order_id], $data11);
                    }else{
                        if ($order_info['pay_type'] != 3){
                            //全额退了
                            $refund_money = round_number($order_info['use_money'], 2);
                            $param = [
                                'param' => array('business_type' => 'pile', 'business_id' => $order_info['order_id'], 'pile_pay_order_id' => $order_info['id']),
                                'operation_info' => '',
                                'refund_money' => $refund_money
                            ];
                            $refund = invoke_cms_model('Plat_order/pile_order_refund', $param);
                            fdump_api([$refund, $param], 'pile/newPriceStandardCharge', 1);
                            if (!$refund['retval']['error']) {
                                $res = $service_order->saveOne(['id' => $order_info['id']], ['is_refund' => 3,'refund_money'=>$refund_money,'end_time'=>time(),'status'=>1,'end_result'=>'开电失败']);
                                if ($res) {
                                    $refund_data = [
                                        'order_id' => $order_info['id'],
                                        'status' => 3,
                                        'refund_money' => $refund_money,
                                        'refund_reason' => '自动退款',
                                        'refund_time' => time(),
                                        'add_time' => time(),
                                    ];
                                    $db_refund = new HouseVillagePileRefundOrder();
                                    $id = $db_refund->add_order($refund_data);
                                }
                                $user = new User();
                                $openid = $user->getOne(['uid' => $order_info['uid']]);
                                if (!empty($openid['openid']) && !empty($equipment_info)) {
                                    $href = get_base_url('pages/village/smartCharge/chargeDetails?order_id=' . $order_info['id'] . '&status=1');
                                    $datamsg = [
                                        'tempKey' => 'OPENTM400166399',
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $openid['openid'],
                                            'first' => '您的充电桩账单退款￥' . $refund_money . '成功',
                                            'keyword1' => '充电桩充电提醒',
                                            'keyword2' => '已发送',
                                            'keyword3' => date('H:i'),
                                        ]
                                    ];
                                    $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                }
                            }
                        }
                    }
                    return true;
                }
                else{
                    fdump_api(['err' => '开电失败', '$status' => $status, '$data' => $data, '$order_id' => $order_id],'pile/price_standard_list',1);
                    if (isset($equipment_info['equipment_num']) && $equipment_info['equipment_num']) {
                        $deviceId = $equipment_info['equipment_num'];
                    } else {
                        $deviceId = '';
                    }
                    $standard = json_decode($status[1], true);
                    $error = isset($standard['error']) ? trim($standard['error']) : '开电接口请求失败';
                    $end_result = '开电失败' . "($error)";
                    $nowTime = time();
                    $pay_time = isset($order_info['pay_time']) ? intval($order_info['pay_time']) : $nowTime;
                    if ($pay_time > $nowTime) {
                        $endTime = ($pay_time + 3) * 1000;
                    } else {
                        $endTime = $nowTime * 1000;
                    }
                    $notificationData = [
                        'order_id' => $order_id,
                        'orderNum' => '',
                        'endType' => 3,
                        'powerCount' => '0.0',
                        'startTime' => $pay_time * 1000,
                        'endTime' => $endTime,
                        'totalElectricMoney' => 0,
                        'totalServiceFee' => 0,
                        'deviceId' => $deviceId,
                        'portNum' => $order_info['socket_no'],
                        'payPrice' => '0.0',
                        'cardNum' => '',
                        'end_result' => $end_result,
                    ];
                    fdump_api(['err' => '直接通知退款', 'notificationData' => $notificationData],'pile/price_standard_list',1);
                    try {
                        $this->notification($notificationData);
                    } catch (\Exception $e) {
                        fdump_api(['line' => $e->getLine(), 'code' => $e->getCode(), 'err' => $e->getMessage()], 'pile/price_standard_list_err', 1);
                    }
                }
            }
        } catch (\Exception $e) {
            fdump_api(['err' => '错误', 'order_id' => $order_id, 'getMessage' => $e->getMessage(), 'getLine' => $e->getLine(), 'getCode' => $e->getCode(), 'getFile' => $e->getFile()],'pile/price_standard_list',1);
            $service_equipment = new HouseVillagePileEquipment();
            $equipment_info = $service_equipment->getInfo(['id' => $order_info['equipment_id']]);
            if (isset($equipment_info['equipment_num']) && $equipment_info['equipment_num']) {
                $deviceId = $equipment_info['equipment_num'];
            } else {
                $deviceId = '';
            }
            $message = $e->getMessage();
            $end_result = '开电失败' . "($message)";
            $nowTime = time();
            $pay_time = isset($order_info['pay_time']) ? intval($order_info['pay_time']) : $nowTime;
            if ($pay_time > $nowTime) {
                $endTime = ($pay_time + 3) * 1000;
            } else {
                $endTime = $nowTime * 1000;
            }
            $notificationData = [
                'order_id' => $order_id,
                'orderNum' => '',
                'endType' => 3,
                'powerCount' => '0.0',
                'startTime' => $pay_time * 1000,
                'endTime' => $endTime,
                'totalElectricMoney' => 0,
                'totalServiceFee' => 0,
                'deviceId' => $deviceId,
                'portNum' => $order_info['socket_no'],
                'payPrice' => '0.0',
                'cardNum' => '',
                'end_result' => $end_result,
            ];
            fdump_api(['err' => '直接通知退款', 'notificationData' => $notificationData],'pile/price_standard_list',1);
            try {
                $this->notification($notificationData);
            } catch (\Exception $e) {
                fdump_api(['line' => $e->getLine(), 'code' => $e->getCode(), 'err' => $e->getMessage()], 'pile/price_standard_list_err', 1);
            }
        }
        return false;
    }
    
    /**
     * tcp充电桩开启充电
     * @author:zhubaodi
     * @date_time: 2021/4/28 19:29
     */
    public function newPriceStandardCharge_tcp($order_id)
    {
        $service_order = new HouseVillagePilePayOrder();
        $order_info = $service_order->get_one(['id' => $order_id]);
        fdump_api(['tcp指令-开电', 'order_info' => $order_info && !is_array($order_info) ? $order_info->toArray() : $order_info], 'pile/newPriceStandardCharge_tcpLog', 1);
        if ($order_info) {
            try {
                $service_equipment = new HouseVillagePileEquipment();
                $nowTime = time();
                $equipment_info = $service_equipment->getInfo(['id' => $order_info['equipment_id']]);
                if (!$equipment_info) {
                    fdump_api(['设备已离线'.__LINE__,$equipment_info], 'command_data', 1);
                    $this->refund($order_info['order_no'],'(设备异常)', false);
                    return false;
                   // throw new \think\Exception("设备不存在！");
                }
                if ($equipment_info['status'] != 1) {
                    fdump_api(['设备已离线'.__LINE__,$equipment_info], 'command_data', 1);
                    $this->refund($order_info['order_no'],'(设备离线)', false);
                    //todo 充电桩故障写入
                    invoke_cms_model('House_maintenan/faultLog',['type'=>2,'device_id'=>$equipment_info['id'],'reason'=>'当前设备已离线'], true);
                    return false;
                 //   throw new \think\Exception("当前设备已离线，无法充电");
                }
                if (empty($equipment_info['fd'])) {
                    fdump_api(['当前设备异常'.__LINE__,$equipment_info], 'command_data', 1);
                    $this->refund($order_info['order_no'],'(监听异常)', false);
                    return false;
                   // throw new \think\Exception("当前设备异常，无法充电");
                }
                $time1 = $nowTime - $this->time;
                if ($equipment_info['last_heart_time'] < $time1) {
                    fdump_api(['设备已离线'.__LINE__,$equipment_info,$time1], 'command_data', 1);
                    $this->refund($order_info['order_no'],'(连接异常)', false);
                    //todo 充电桩故障写入
                    invoke_cms_model('House_maintenan/faultLog',['type'=>2,'device_id'=>$equipment_info['id'],'reason'=>'当前设备已离线'], true);
                    return false;
                   //  throw new \think\Exception("当前设备已离线，无法充电");
                }
                $socket_arr = json_decode($equipment_info['port_status']);
                if (!empty($socket_arr)) {
                    $status = 0;
                    foreach ($socket_arr as $k => $value) {
                        if ($k == $order_info['socket_no']-1) {
                            $status = $value;
                            break;
                        }
                    }
                    //判断插座状态
                    if ($status != 0) {
                        fdump_api(['插座不能充电'.__LINE__,$status,$socket_arr], 'command_data', 1);
                        $this->refund($order_info['order_no'],'(插座异常)', false);
                        return false;
                       // throw new \think\Exception("当前插座不能充电，请更换插座");
                    }
                }
                if (isset($order_info['pay_time']) && $order_info['pay_time']>1) {
                    if (intval($order_info['pay_time'])<$time1) {
                        fdump_api(['支付后反馈响应超时'.__LINE__,$order_info,$time1], 'command_data', 1);
                        $this->refund($order_info['order_no'],'(支付反馈响应超时)', false);
                        return false;
                    }
                } elseif(isset($order_info['order_id'])) {
                    $service_paid_order = new PayOrderInfo();
                    $wherePayOrderInfo = [];
                    $wherePayOrderInfo[] = ['business','=','pile'];
                    $wherePayOrderInfo[] = ['business_order_id','=',$order_info['order_id']];
                    $order_paid_no = $service_paid_order->getOne($wherePayOrderInfo);
                    if ($order_paid_no && isset($order_paid_no['orderid']) && $order_paid_no['orderid']) {
                        $orderid = trim($order_paid_no['orderid']);
                        $scrcuRecord = \think\facade\Db::name('Scrcu_record')->where([['order_number', '=', $orderid]])->find();
                        if ($scrcuRecord && isset($scrcuRecord['add_time']) && intval($scrcuRecord['add_time'])<$time1) {
                            fdump_api(['支付反馈响应超时'.__LINE__,$equipment_info,$scrcuRecord,$time1], 'command_data', 1);
                            $this->refund($order_info['order_no'],'(支付反馈响应超时)', false);
                            return false;
                        }
                    }
                }
    
                //todo 充电桩故障解除
                invoke_cms_model('House_maintenan/normalLog',['type'=>2,'device_id'=>$equipment_info['id']], true);
    
                //查询消费标准
                $service_Equipment_Attribute = new HouseVillagePileEquipmentAttribute();
                $attributeId = $service_Equipment_Attribute->getOne(['equipment_id' => $equipment_info['id'], 'attribute_id' => 1, 'is_del' => 1]);
                $attributeInfo = [];
                if (!empty($attributeId)) {
                    $service_Attribute_Set = new HouseVillagePileAttributeSet();
                    $attributeInfo = $service_Attribute_Set->getOne(['id' => $attributeId['attribute_standard_id'], 'status' => 1]);
                }
                
                if (empty($attributeInfo)) {
                    $service_Attribute = new HouseVillagePileAttribute();
                    $attributeInfo = $service_Attribute->getOne(['value' => 1, 'type' => 2]);
                    $price_standard_list = unserialize($attributeInfo['default_value']);
                } else {
                    $price_standard_list = unserialize($attributeInfo['standard_value']);
                }
                fdump_api(['tcp指令-收费标准', 'price_standard_list' => $price_standard_list], 'pile/newPriceStandardCharge_tcpLog', 1);
                $use_money_order=$order_info['use_money'];
                $standard_fee= $price_standard_list[0][2];
                if(isset($price_standard_list['ffee']) && $price_standard_list['ffee'] && $price_standard_list['ffee'][2]>0){
                    $standard_fee=$standard_fee+$price_standard_list['ffee'][2];
                }
                if ($order_info['pay_type'] == 3) {
                    $service_Card_User = new HouseVillagePileMonthCardUser();
                    $where[] = ['uid', '=', $order_info['uid']];
                    $where[] = ['status', '=', 1];
                    $where[] = ['expire_time', '>', $nowTime];
                    $cardUserInfo = $service_Card_User->getOne($where);
                    $use_money_order=$cardUserInfo['per_price'];
                    $data = [
                        'cmd' => 'newPriceStandardCharge',
                        'equipment_num' => $equipment_info['equipment_num'],
                        'orderNo' => $order_info['order_no'],
                        'port' => $order_info['socket_no'],
                        'param' => round($cardUserInfo['per_price'] / $standard_fee * $price_standard_list[2][2], 6),
                        'time' => ceil($cardUserInfo['per_price'] / $standard_fee * $price_standard_list[1][2] * 3600),
                    ];
                    $data11 = [
                        'use_ele' => round($cardUserInfo['per_price'] / $standard_fee * $price_standard_list[2][2], 2),
                        'continued_time' => ceil($cardUserInfo['per_price'] / $standard_fee * $price_standard_list[1][2] * 3600),
                    ];
                } else {
                    $data = [
                        'cmd' => 'newPriceStandardCharge',
                        'equipment_num' => $equipment_info['equipment_num'],
                        'orderNo' => $order_info['order_no'],
                        'port' => $order_info['socket_no'],
                        'param' => round($order_info['use_money'] / $standard_fee * $price_standard_list[2][2], 2),
                        'time' => ceil($order_info['use_money'] / $standard_fee * $price_standard_list[1][2] * 3600),
                    ];
                    $data11 = [
                        'use_ele' => round($order_info['use_money'] / $standard_fee * $price_standard_list[2][2], 2),
                        'continued_time' => ceil($order_info['use_money'] / $standard_fee * $price_standard_list[1][2] * 3600),
                    ];
                }
                if (isset($equipment_info['device_family']) && intval($equipment_info['device_family']) === 1) {
                    $data['workType'] = intval($equipment_info['work_type']) === 2 ? '01' : '02';
                    $data['money'] = $order_info['use_money'];
                }
                fdump_api(['tcp指令-收费指令', 'data' => $data, 'data11' => $data11], 'pile/newPriceStandardCharge_tcpLog', 1);
    
                $command_data = [
                    'command' => json_encode($data),
                    'type' => 1,
                    'status' => 1,
                    'addtime' => $nowTime,
                ];
    
                if (isset($data11['use_ele'])) {
                    $data11['buy_ele'] = $data11['use_ele'];
                }
                if (isset($data11['continued_time'])) {
                    $data11['buy_time'] = $data11['continued_time'];
                }
                if(isset($price_standard_list['ffee']) && $price_standard_list['ffee'] && $price_standard_list['ffee'][2]>0){
                    $extra_data=$order_info['extra_data'] ? json_decode($order_info['extra_data'],true):array();
                    $extra_data=$extra_data ? $extra_data:array();
                    $extra_data['standard_value']=$price_standard_list;
                    $extra_data['use_money_order']=$use_money_order;
                    $service_fee_rate=$price_standard_list['ffee'][2]/($price_standard_list['ffee'][2]+$price_standard_list[0][2]);
                    $service_fee=$service_fee_rate*$use_money_order;
                    $service_fee=round($service_fee,2);
                    $data11['service_fee']=$service_fee;
                    $data11['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
                }
                fdump_api([$order_id, $data11], 'pile/newPriceStandardCharge_tcpLog', 1);
                $service_order->saveOne(['id' => $order_id], $data11);
                $service_command = new HouseVillagePileCommand();
                fdump_api(['tcp指令-收费指令-command_data', 'command_data' => $command_data], 'pile/newPriceStandardCharge_tcpLog', 1);
                $id = $service_command->insertCommand($command_data);
                fdump_api([$command_data, $id], 'command_data', 1);
                fdump_api(['tcp指令-收费指令-id', 'id' => $id], 'pile/newPriceStandardCharge_tcpLog', 1);
            } catch (\Exception $e) {
                $text_message = $e->getMessage();
                fdump_api(['tcp指令-收费指令-id', '执行错误' => $text_message, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'order_id' => $order_id], 'pile/newPriceStandardCharge_tcpLog', 1);
                $this->refund($order_info['order_no'],"($text_message)", false);
                return false;
            }
            if($id){
                //计划任务定时查看充电状态
                $data = [
                    'cmd' => 'isPileSuccess',
                    'orderNo' => $order_info['order_no'],
                    'equipment_num' => $equipment_info['equipment_num'],
                    'type'=>'adopt',
                ];
                $command_data = [
                    'command' => json_encode($data),
                    'type' => 2,
                    'status' => 1,
                    'addtime' => $nowTime,
                ];
                $res = $service_command->insertCommand($command_data);
                if($res){
                    $unique_id = "pile_notice".$nowTime.rand(1,9999);
                    $insert = [
                        'plan_time'=>$nowTime+100,
                        'space_time'=>0,
                        'add_time'=>$nowTime,
                        'file'=>'sub_pile_notice',
                        'time_type'=>1,
                        'unique_id'=>$unique_id
                    ];
                    $db_process_sub_plan = new ProcessSubPlan();
                    $db_process_sub_plan->add($insert);
                }
            }
            return $id;
        }
        return 0;

    }


    /**
     * 驴充充充电结束通知
     * @author:zhubaodi
     * @date_time: 2021/4/28 19:29
     */
    public function notification($data, $repair = 0)
    {
        fdump_api(['接收参数=='.__LINE__,$data],'lvcc/notification',1);
        $templateNewsService = new TemplateNewsService();
        $service_order_pile = new HouseVillagePilePayOrder();
        $db_pile_config = new HouseVillagePileConfig();
        $order_id = isset($data['order_id']) ? intval($data['order_id']) : 0;
        $order_serial = isset($data['orderNum']) ? $data['orderNum'] : '';
        $whereOrder = [];
        if ($order_serial) {
            $whereOrder[] = ['order_serial', '=', $order_serial];
        } else {
            $whereOrder[] = ['id', '=', $order_id];
        }
        $order_info = $service_order_pile->get_one($whereOrder);
        if (!isset($data['orderNum']) || !$data['orderNum']) {
            $data['orderNum'] = isset($order_info['order_serial']) ? $order_info['order_serial'] : '';
        }
        fdump_api(['接收参数=='.__LINE__,'order_info' => ($order_info && !is_array($order_info)) ? $order_info->toArray() : $order_info],'lvcc/notification',1);
        $endTime = time();
        if(isset($data['endTime']) && !empty($data['endTime'])){
           $endTimeStr=strval($data['endTime']) ;
            if(strlen($endTimeStr)>11){
                $endTime =$data['endTime']/1000;
            }else{
                $endTime =$data['endTime'];
            }
            if($endTime<100){
                $endTime = time();
            }
        }
        $payPrice = isset($data['payPrice']) && $data['payPrice'] > 0 ? $data['payPrice'] : 0;
        $end_result = isset($data['end_result']) && trim($data['end_result']) ? trim($data['end_result']) : '';
        if(empty($end_result) &&  isset($data['endType']) && isset($this->lvvEndTypeDesc[$data['endType']])){
            $end_result=$this->lvvEndTypeDesc[$data['endType']];
        }
        $order_data=[
            'end_time' => $endTime,
            'status' => 1
        ];
        if($data['powerCount']){
            $order_data['use_ele']=$data['powerCount'];
        }
        if(isset($data['totalServiceFee']) && $data['totalServiceFee']>0){
            $order_data['service_fee']=$data['totalServiceFee'];
        }
        $notificationData = $data;
        $notificationData['notification_time'] = time();
        $notificationTxt = json_encode($notificationData,JSON_UNESCAPED_UNICODE);
        $order_data['notificationTxt'] = $notificationTxt;
        /*if($data['startTime'] && $data['endTime']){
            $interval=intval($data['endTime'] / 1000) - intval($data['startTime'] / 1000);
            if($interval > 0){
                $order_data['continued_time']=$interval;
            }
        }*/
        if (!empty($order_info)) {
            if($order_info['status']==1){
                //已经通知处理过了，驴充充有时会通知多次
                fdump_api(['已经通知结束过了=='.__LINE__,'data'=>$data,'order_info'=>$order_info],'lvcc/notification',1);
                return $order_id;
            }
            $retTmp = $service_order_pile->saveOne(['id' => $order_info['id']],$order_data);
            $service_equipment = new HouseVillagePileEquipment();
            $equipment_info = $service_equipment->getInfo(['id' => $order_info['equipment_id']]);
            $user = new User();
            $openid = $user->getOne(['uid' => $order_info['uid']]);
            if ((!empty($openid['openid']) || $order_info['pay_type']==4) && !empty($equipment_info)) {
                $href = get_base_url('pages/village/smartCharge/chargeDetails?order_id=' . $order_info['id'] . '&status=1');
                if ($data['endType'] == 1) {
                    if(!empty($openid['openid'])) {
                        $datamsg = [
                            'tempKey' => 'OPENTM400166399',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $openid['openid'],
                                'first' => '您设备所在的' . $equipment_info['equipment_name'] . $order_info['socket_no'] . '号插座口充电完成，欢迎再次使用，祝您生活愉快',
                                'keyword1' => '充电桩充电提醒',
                                'keyword2' => '已发送',
                                'keyword3' => date('H:i'),
                                'remark' => '请点击查看详细信息！'
                            ]
                        ];
                    }
                } else {
                    $config_info = $db_pile_config->get_one(['village_id' => $order_info['village_id']]);
                    fdump_api(['配置', $config_info, $order_info, $data['powerCount'], $payPrice], 'pile/notification', 1);
                    if (!empty($config_info)) {
                        $buy_ele = isset($order_info['buy_ele']) && floatval($order_info['buy_ele']) > 0 ? $order_info['buy_ele'] : 0;
                        if (floatval($buy_ele) <= 0 && isset($order_info['use_ele']) && $order_info['use_ele']) {
                            $buy_ele = $order_info['use_ele'];
                        }
                        if ($payPrice > 0 || (isset($data['payPrice']) && $data['payPrice'] && $data['payPrice'] !== 'null')) {
                            $refund_money = floatval($order_info['use_money']) - floatval($payPrice);
                            $keyTime = $endTime - $order_info['pay_time'];
                            $savePileOrder = ['continued_time' => $keyTime];
                            $end_result && $savePileOrder['end_result'] = $end_result;
                            $service_order_pile->saveOne(['id' => $order_info['id']], $savePileOrder);
                            fdump_api(['退款金额', $refund_money, $keyTime, $payPrice], 'pile/notification', 1);
                        } elseif (!empty($data['totalElectricMoney']) || !empty($data['totalServiceFee']) || $buy_ele) {
                            if ((!empty($config_info['low_consume_power']) && $config_info['low_consume_power'] >= $data['powerCount']) || (!empty($config_info['des_power_level']) && $config_info['des_power_level'] >= $data['powerCount'])) {
                                $refund_money = $order_info['use_money'];
                            } elseif (!empty($config_info['des_power_level']) && $config_info['des_power_level'] < $data['powerCount'] && $buy_ele > $data['powerCount']) {
                                $level = ceil($data['powerCount'] / $config_info['des_power_level']);
                                $des_power_level = $config_info['des_power_level'] * $level;
                                if ($des_power_level >= $data['powerCount'] && $des_power_level <= $buy_ele) {
                                    $refund_money = $order_info['use_money'] - ($order_info['use_money'] / $buy_ele) * ($config_info['des_power_level'] * $level);
                                }
                                fdump_api(['1计算', $level, $des_power_level, $refund_money], 'pile/notification', 1);
                            }
                            fdump_api(['2计算', $order_info, $level, $des_power_level, $refund_money], 'pile/notification', 1);
                        } else {
                            $keyTime = $endTime - $order_info['pay_time'];
                            $continued_time = ceil($order_info['continued_time'] / 60);
                            $consume_time1 = ceil($keyTime / 60);
                            if ((!empty($config_info['low_consume_time']) && $config_info['low_consume_time'] >= $consume_time1) || (!empty($config_info['des_time_level']) && $config_info['des_time_level'] >= $consume_time1)) {
                                $refund_money = $order_info['use_money'];
                            } elseif (!empty($config_info['des_time_level']) && $config_info['des_time_level'] < $consume_time1 && $continued_time > $consume_time1) {
                                $level = ceil($consume_time1 / $config_info['des_time_level']);
                                $des_time_level = $config_info['des_time_level'] * $level;
                                if ($des_time_level >= $consume_time1 && $des_time_level <= $continued_time) {
                                    $refund_money = $order_info['use_money'] - ($order_info['use_money'] / $continued_time) * ($config_info['des_time_level'] * $level);
                                }
                            }
                            fdump_api(['3计算', $continued_time, $consume_time1, $refund_money], 'pile/notification', 1);
                            $savePileOrder = ['continued_time' => $keyTime];
                            $end_result && $savePileOrder['end_result'] = $end_result;
                            $service_order_pile->saveOne(['id' => $order_info['id']], $savePileOrder);
                        }
                        $refund_money = round_number($refund_money, 2);
                        $param = [
                            'param' => array('business_type' => 'pile', 'business_id' => $order_info['order_id'], 'pile_pay_order_id' => $order_info['id']),
                            'operation_info' => '',
                            'refund_money' => $refund_money,
                        ];
                        if (floatval($refund_money) > 0) {
                            $refund = invoke_cms_model('Plat_order/pile_order_refund', $param);
                            fdump_api([$refund, $param], 'pile/notification', 1);
                        }
                        if (floatval($refund_money) > 0 && !$refund['retval']['error']) {
                            $savePileOrder = ['is_refund' => 3, 'refund_money' => $refund_money];
                            $end_result && $savePileOrder['end_result'] = $end_result;
                            $res = $service_order_pile->saveOne(['id' => $order_info['id']], $savePileOrder);
                            // $res = M('House_village_pile_pay_order')->where(['id' => $order_info['id']])->data(['is_refund' => 3])->save();
                            if ($res) {
                                $refund_data = [
                                    'order_id' => $order_info['id'],
                                    'status' => 3,
                                    'refund_money' => $refund_money,
                                    'refund_reason' => '自动退款',
                                    'refund_time' => time(),
                                    'add_time' => time(),
                                ];
                                $end_result && $refund_data['refund_reason'] = "自动退款【{$end_result}】";
                                $db_refund = new HouseVillagePileRefundOrder();
                                $id = $db_refund->add_order($refund_data);
                                //  M('House_village_pile_refund_order')->add($refund_data);
                            }
                            if (!empty($openid['openid'])) {
                                $datamsg = [
                                    'tempKey' => 'OPENTM400166399',
                                    'dataArr' => [
                                        'href' => $href,
                                        'wecha_id' => $openid['openid'],
                                        'first' => '您的充电桩账单退款￥' . $refund_money . '成功',
                                        'keyword1' => '充电桩充电提醒',
                                        'keyword2' => '已发送',
                                        'keyword3' => date('H:i'),
                                    ]
                                ];
                                $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                            }

                        } elseif (floatval($refund_money) > 0) {
                            $savePileOrder = ['is_refund' => 4];
                            $end_result && $savePileOrder['end_result'] = $end_result;
                            $service_order_pile->saveOne(['id' => $order_info['id']], $savePileOrder);
                            $refund_data = [
                                'order_id' => $order_info['id'],
                                'status' => 4,
                                'refund_money' => $refund_money,
                                'refund_reason' => $refund['retval']['msg'],
                                'refund_time' => time(),
                                'add_time' => time(),
                            ];
                            $end_result && $refund_data['refund_reason'] = $refund['retval']['msg'] . "【{$end_result}】";

                            $db_refund = new HouseVillagePileRefundOrder();
                            $id = $db_refund->add_order($refund_data);
                        }
                    }
                    if (!empty($openid['openid'])) {
                        $datamsg = [
                            'tempKey' => 'OPENTM400166399',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $openid['openid'],
                                'first' => '您设备所在的' . $equipment_info['equipment_name'] . $order_info['socket_no'] . '号插座口由于设备异常情况，停止充电',
                                'keyword1' => '充电桩充电提醒',
                                'keyword2' => '已发送',
                                'keyword3' => date('H:i'),
                                'remark' => '请点击查看详细信息！'
                            ]
                        ];
                    }
                }
                if (!$repair && isset($datamsg) && $datamsg && isset($datamsg['tempKey']) && $datamsg['dataArr']) {
                    $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                }
            }
            $order_id = $service_order_pile->saveOne(['id' => $order_info['id']],$order_data);
            $configCustomizationService=new ConfigCustomizationService();
            $pile_charge_consume_point=$configCustomizationService->getPileChargeConsumePoint();
            if($pile_charge_consume_point>0){
                 //算积分处理
                $tparam=array('energy_type'=>$equipment_info['energy_type'],'comfrom'=>'lvcc_notification');
                $this->handlePileChargeConsumePoint($order_info['id'],$tparam,'lvv');
            }
            return $order_id;
        }
        fdump_api(['order数据=='.__LINE__,$order_data,$order_info],'lvcc/notification',1);
        return 0;

    }
    
    public function handlePileChargeConsumePoint($order_id=0,$param=array(),$pile_type=''){
        
       
        if(isset($param['pile_charge_give_point'])){
            $pile_charge_give_point=$param['pile_charge_give_point'];
            $energy_pile_charge_give_point= isset($param['energy_pile_charge_give_point']) ? $param['energy_pile_charge_give_point']:0;
        }else{
            $configService =new ConfigService();
            $pile_charge_give_point= $configService ->getOneField('pile_charge_give_point');
            $pile_charge_give_point=$pile_charge_give_point>0 ? $pile_charge_give_point:0;
            $energy_pile_charge_give_point= $configService ->getOneField('energy_pile_charge_give_point');
            $energy_pile_charge_give_point=$energy_pile_charge_give_point>0 ? $energy_pile_charge_give_point:0;
        }
        $returnArr=array('err_code'=>0,'msg'=>'','is_handle_point'=>0);
        if($pile_charge_give_point<=0 && $energy_pile_charge_give_point<=0){
            $returnArr['err_code']=1;
            $returnArr['msg']='未设置赠送积分比例';
            return $returnArr;
        }
        $energy_type=0; //0普通 1新能源
        $service_order_pile = new HouseVillagePilePayOrder();
        if($param['order_pile'] && $param['order_pile']){
            $order_pile=$param['order_pile'];
        }else{
            $order_pile = $service_order_pile->get_one(array('id'=>$order_id),'*');
        }
        $uid=$order_pile['uid'];
        if(empty($order_pile) || $order_pile->isEmpty()){
            $returnArr['err_code']=1;
            $returnArr['msg']='订单信息不存在';
            return $returnArr;
        }
        $service_equipment = new HouseVillagePileEquipment();
        $order_pile=$order_pile->toArray();
        if(empty($order_pile) && !isset($order_pile['id'])){
            $returnArr['err_code']=1;
            $returnArr['msg']='订单信息错误';
            return $returnArr;
        }
        $surplusMoney=$order_pile['use_money']-$order_pile['refund_money']; //剩余的钱
        if($surplusMoney<=0){
            $returnArr['err_code']=1;
            $returnArr['msg']='没有剩余的消费金额';
            return $returnArr;
        }
        $desc='普通充电桩消费赠送积分';
        $comfrom=isset($param['comfrom']) ? $param['comfrom']:'';
        $consumePoint=0;
        $consume_point_arr=array('surplus_money'=>$surplusMoney,'pile_charge_give_point'=>$pile_charge_give_point,'energy_pile_charge_give_point'=>$energy_pile_charge_give_point);
        $consume_point_arr['comfrom']=$comfrom;
        if($pile_type=='lvv'){
            if(isset($param['energy_type'])){
                $energy_type=$param['energy_type'];
            }else{
                $equipment_info = $service_equipment->getInfo(['id' => $order_pile['equipment_id']]);
                if($equipment_info && isset($equipment_info['energy_type'])){
                    $energy_type=$equipment_info['energy_type'];
                }
            }
            if($energy_type<1 && $pile_charge_give_point>0){
                $consumePoint=($pile_charge_give_point*$surplusMoney)/100;
                $consume_point_arr['energy_type']=0;
            }else if($energy_type==1 && $energy_pile_charge_give_point>0){
                $desc='新能源充电桩消费赠送积分';
                $consumePoint=($energy_pile_charge_give_point*$surplusMoney)/100;
                $consume_point_arr['energy_type']=1;
            }
        }else if($pile_charge_give_point>0){
            $consumePoint=($pile_charge_give_point*$surplusMoney)/100;
            $consume_point_arr['energy_type']=0;
        }
        $consumePoint=$consumePoint>0 ? round($consumePoint,2):0;
        $consume_point_arr['consume_point']=$consumePoint;
        $consume_point_arr['desc']=$desc;
        $consume_point_arr['is_add_score']=0;
        $consume_point_arr['optime']=date('Y-m-d H:i:s');
        fdump_api([__LINE__,'uid'=>$uid,'pile_type'=>$pile_type,'order_id'=>$order_id,'consume_point_arr'=>$consume_point_arr],'pile/handlePileChargeConsumePoint',1);
        $consume_point_json=json_encode($consume_point_arr,JSON_UNESCAPED_UNICODE);
        $tmpDataArr=array('consume_point'=>$consume_point_json,'is_handle_point'=>3);
        $service_order_pile->saveOne(['id' => $order_id],$tmpDataArr);
        if($consumePoint<=0 || $uid<=0){
            $returnArr['err_code']=1;
            $returnArr['is_handle_point']=3;
            $returnArr['msg']='算出的消费计费小于0';
            return $returnArr;
        }
        //增加积分处理
        $userComService = new UserService();
        $param=array('order_id'=>$order_id,'house_type'=>1,'order_type'=>'house_pile','opt_level'=>'sys_platform');
        $ret=$userComService->addScore($uid,$consumePoint,$desc,0,$param);
        if($ret && is_array($ret) && !$ret['error_code']){
            $consume_point_arr['is_add_score']=1;
        }
        $consume_point_arr['ret_score']=$ret;
        $consume_point_json=json_encode($consume_point_arr,JSON_UNESCAPED_UNICODE);
        $tmpDataArr=array('consume_point'=>$consume_point_json,'is_handle_point'=>3);
        $service_order_pile->saveOne(['id' => $order_id],$tmpDataArr);
        fdump_api([__LINE__,'pile_type'=>$pile_type,'order_id'=>$order_id,'ret'=>$ret],'pile/handlePileChargeConsumePoint',1);
        $returnArr['err_code']=0;
        $returnArr['is_handle_point']=3;
        $returnArr['msg']='积分赠送已处理';
        return $returnArr;
    }

    public function handleTaskPileChargeConsumePoint()
    {
        $configService = new ConfigService();
        $pile_charge_give_point = $configService->getOneField('pile_charge_give_point');
        $pile_charge_give_point = $pile_charge_give_point > 0 ? $pile_charge_give_point : 0;
        $energy_pile_charge_give_point = $configService->getOneField('energy_pile_charge_give_point');
        $energy_pile_charge_give_point = $energy_pile_charge_give_point > 0 ? $energy_pile_charge_give_point : 0;
        $tparam = array('energy_type' => 0, 'comfrom' => 'tcp_task', 'pile_charge_give_point' => $pile_charge_give_point, 'energy_pile_charge_give_point' => $energy_pile_charge_give_point);
        $service_order_pile = new HouseVillagePilePayOrder();
        $whereArr = array(['is_handle_point', '<', 1], ['type', '=', 2], ['status', '=', 1], ['use_money', '>', 0], ['is_refund', '<', 2], ['pay_time', '>', 100]);
        $order_pile_obj = $service_order_pile->getList($whereArr, '*', 1, 20, 'id ASC');
        if ($order_pile_obj && !$order_pile_obj->isEmpty()) {
            $order_piles = $order_pile_obj->toArray();
            foreach ($order_piles as $vv) {
                $tparam['order_pile'] = $vv;
                $retTmp = $this->handlePileChargeConsumePoint($vv['id'], $tparam, 'tcp_pile');
                if (empty($retTmp) || ($retTmp['err_code'] > 0 && $retTmp['is_handle_point'] < 1)) {
                    $tmpData = $retTmp && is_array($retTmp) ? $retTmp : array();
                    $tmpData['optime'] = date('Y-m-d H:i:s');
                    $consume_point_json = json_encode($tmpData, JSON_UNESCAPED_UNICODE);
                    $tmpDataArr = array('is_handle_point' => 2, 'consume_point' => $consume_point_json);
                    $service_order_pile->saveOne(['id' => $vv['id']], $tmpDataArr);
                }
            }
        }

        return true;
    }

    /**
     * 艾特充充电结束通知
     * @author:zhubaodi
     * @date_time: 2021/4/28 19:29
     */
    public function outSettlement($data)
    {
        $templateNewsService = new TemplateNewsService();
        $service_order_pile = new HouseVillagePilePayOrder();
        $db_pile_config = new HouseVillagePileConfig();
        $order_info = $service_order_pile->get_one(['order_no' => $data['orderNo']]);
        if (!empty($order_info)) {
            $service_equipment = new HouseVillagePileEquipment();
            $equipment_info = $service_equipment->getInfo(['id' => $order_info['equipment_id']]);
            $user = new User();
            $openid = $user->getOne(['uid' => $order_info['uid']]);
            if (!empty($openid['openid']) && !empty($equipment_info)) {
                $href = get_base_url('pages/village/smartCharge/chargeDetails?order_id=' . $order_info['id'] . '&status=1');
                if (in_array($data['stopReason'], [1, 2, 3, 4])) {
                    $datamsg = [
                        'tempKey' => 'OPENTM400166399',
                        'dataArr' => [
                            'href' => $href,
                            'wecha_id' => $openid['openid'],
                            'first' => '您设备所在的' . $equipment_info['equipment_name'] . $order_info['socket_no'] . '号插座口充电完成，欢迎再次使用，祝您生活愉快',
                            'keyword1' => '充电桩充电提醒',
                            'keyword2' => '已发送',
                            'keyword3' => date('H:i'),
                            'remark' => '请点击查看详细信息！'
                        ]
                    ];
                } elseif (in_array($data['stopReason'], [5, 6, 7, 8])) {
                    $config_info = $db_pile_config->get_one(['village_id' => $order_info['village_id']]);
                    if (!empty($config_info)) {
                        if (!empty($order_info['use_ele'])) {
                            if ((!empty($config_info['low_consume_power']) && $config_info['low_consume_power'] > $data['powerConsumption']) || (!empty($config_info['des_power_level']) && $config_info['des_power_level'] > $data['powerConsumption'])) {
                                $refund_money = $order_info['use_money'];
                            } elseif (!empty($config_info['des_power_level']) && $config_info['des_power_level'] < $data['powerConsumption'] && $order_info['use_ele'] > $data['powerConsumption']) {
                                $level = ceil($data['powerConsumption'] / $config_info['des_power_level']);
                                $des_power_level = $config_info['des_power_level'] * $level;
                                if ($des_power_level > $data['powerConsumption'] && $des_power_level < $order_info['use_ele']) {
                                    $refund_money = $order_info['use_money'] - ($order_info['use_money'] / $order_info['use_ele']) * ($config_info['des_power_level'] * $level);
                                }
                            }
                        } elseif (!empty($order_info['continued_time'])) {
                            $continued_time = ceil($order_info['continued_time'] / 60);
                            $consume_time1 = ceil((time() - $order_info['pay_time']) / 60);
                            if ((!empty($config_info['low_consume_time']) && $config_info['low_consume_time'] > $consume_time1) || (!empty($config_info['des_time_level']) && $config_info['des_time_level'] > $consume_time1)) {
                                $refund_money = $order_info['use_money'];
                            } elseif (!empty($config_info['des_time_level']) && $config_info['des_time_level'] < $consume_time1 && $continued_time > $consume_time1) {
                                $level = ceil($consume_time1 / $config_info['des_time_level']);
                                $des_time_level = $config_info['des_time_level'] * $level;
                                if ($des_time_level > $consume_time1 && $des_time_level < $continued_time) {
                                    $refund_money = $order_info['use_money'] - ($order_info['use_money'] / $continued_time) * ($config_info['des_time_level'] * $level);
                                }
                            }
                        }
                        $param = [
                            'param' => array('business_type' => 'pile', 'business_id' => $order_info['order_id'], 'pile_pay_order_id' => $order_info['id']),
                            'operation_info' => '',
                            'refund_money' => $refund_money
                        ];
                        $refund = invoke_cms_model('Plat_order/pile_order_refund', $param);
                        if (!$refund['error']) {
                            $res = $service_order_pile->saveOne(['id' => $order_info['id']], ['is_refund' => 3]);
                            // $res = M('House_village_pile_pay_order')->where(['id' => $order_info['id']])->data(['is_refund' => 3])->save();
                            if ($res) {
                                $refund_data = [
                                    'order_id' => $order_info['id'],
                                    'status' => 3,
                                    'refund_money' => $order_info['use_money'],
                                    'refund_reason' => '自动退款',
                                    'refund_time' => time(),
                                    'add_time' => time(),
                                ];

                                $db_refund = new HouseVillagePileRefundOrder();
                                $id = $db_refund->add_order($refund_data);
                                //  M('House_village_pile_refund_order')->add($refund_data);
                            }
                            $datamsg = [
                                'tempKey' => 'OPENTM400166399',
                                'dataArr' => [
                                    'href' => $href,
                                    'wecha_id' => $openid['openid'],
                                    'first' => '您的充电桩账单退款￥' . $refund_money . '成功',
                                    'keyword1' => '充电桩充电提醒',
                                    'keyword2' => '已发送',
                                    'keyword3' => date('H:i'),
                                    'remark' => '请点击查看详细信息！'
                                ]
                            ];
                            $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                        }
                    }
                    $datamsg = [
                        'tempKey' => 'OPENTM400166399',
                        'dataArr' => [
                            'href' => $href,
                            'wecha_id' => $openid['openid'],
                            'first' => '您设备所在的' . $equipment_info['equipment_name'] . $order_info['socket_no'] . '号插座口由于设备异常情况，停止充电',
                            'keyword1' => '充电桩充电提醒',
                            'keyword2' => '已发送',
                            'keyword3' => date('H:i'),
                        ]
                    ];
                }
                $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
            }

            $order_id = $service_order_pile->saveOne(['order_serial' => $data['orderNo']], ['end_time' => time(), 'status' => 1]);
            return $order_id;
        }
        return 0;
    }

    /**
     * 获取消费标准
     * @author:zhubaodi
     * @date_time: 2021/5/5 14:14
     */
    public function getPriceStandard($device_id)
    {
        if (empty($device_id)) {
            throw new \think\Exception("设备id不能为空");
        }
        $url = $this->lbbtechCenterUrl .'/open/getPriceStandard';
        $data = [
            'token' => $this->lbbtechCenterToken,
            'simId' => $device_id
        ];
        $cache_key='lvcchong_api_equipment_getPriceStandard_'.$device_id;
        $price_standard=cache($cache_key);
        if(empty($price_standard) || !isset($price_standard[0]) || $price_standard[0]!=200) {
            $price_standard = http_request($url, 'POST', http_build_query($data));
            cache($cache_key, $price_standard, 30);
        }
        fdump_api(['data' => $data, 'price_standard' => $price_standard], 'pile/priceStandardBZ',1);

        $price_standard_list = [];

        if ($price_standard[0] == 200) {
            $price_standard_list = json_decode($price_standard[1], true);
            if (!isset($price_standard_list['success']) || $price_standard_list['success'] != 1) {

                throw new \think\Exception('消费标准不存在');
            }

        } else {
            $this->getPriceStandard($device_id);
        }


        return $price_standard_list;
    }


    /**
     * 获取插座状态
     * @author:zhubaodi
     * @date_time: 2021/5/5 14:14
     * source  2 获取所有数据
     */
    public function deviceStatus($device_id,$source=0,$eid=0)
    {
        if (empty($device_id)) {
            throw new \think\Exception("设备id不能为空");
        }
        //获取插座状态
        $url = $this->lbbtechCenterUrl .'/open/deviceStatus';
        $data = [
            'token' => $this->lbbtechCenterToken,
            'deviceId' => $device_id
        ];
        $service_equipment = new HouseVillagePileEquipment();
        $equipmentInfo = $service_equipment->getInfo(['id' =>$eid], 'id,village_id,status');
        $cache_key='lvcchong_api_equipment_detail_'.$device_id;
        $device_list=cache($cache_key);
        if(empty($device_list) || !isset($device_list['data'])) {
            $device_status = http_request($url, 'POST', http_build_query($data));
            if ($device_status[0] == 200) {
                $device_list = json_decode($device_status[1], true);
                cache($cache_key, $device_list, 30);
                if (isset($device_list['code']) && $device_list['code'] == -1) {
                    if($eid>0 && !empty($equipmentInfo) && isset($equipmentInfo['status']) && $equipmentInfo['status']==1){
                        $service_equipment->saveDataInfo(['id' =>$eid],array('status'=>2));
                        invoke_cms_model('House_maintenan/faultLog', ['type' => 2, 'device_id' => $eid, 'reason' => '当前设备已离线'], true);
                    }
                    if ($source<1) {
                        throw new \think\Exception($device_list['error']);
                    } else {
                        return [];
                    }
                }
                
            } else {
                $device_list = [];
            }
            if($eid>0 && $device_list && isset($device_list['data']) &&  !empty($equipmentInfo) && isset($equipmentInfo['status']) && $equipmentInfo['status']!=1){
                $service_equipment->saveDataInfo(['id' =>$eid],array('status'=>1));
                invoke_cms_model('House_maintenan/normalLog', ['type' => 2, 'device_id' => $eid], true);
            }
        }
        if($source==2){
            return $device_list;
        }
        $socket_arr = [];
        if ($device_list) {
            $socket_arr = $device_list['data']['device']['data']['ports'];
        }

        return $socket_arr;
    }

    /**
     *获取广告信息
     * @author:zhubaodi
     * @date_time: 2021/5/7 10:53
     */
    public function getAdvertisement()
    {
        $attribute=array();
        $attribute['advertisement'] = $this->adverList;
        return $attribute;
    }
    /**
     * 获取tcp返回数据
     * @author:zhubaodi
     * @date_time: 2021/5/26 17:29
     */
    public function tcp_upload()
    {
        $service_command = new HouseVillagePileCommand();
        $service_order_pile = new HouseVillagePilePayOrder();
        $command_list = $service_command->getList(['type' => 2, 'status' => 1]);
        if (!empty($command_list)) {
            $command_list = $command_list->toArray();
            if (!empty($command_list)) {
                foreach ($command_list as $value) {
                    $list = json_decode($value['command']);
                    if ($list['cmd'] == 'newPriceStandardCharge') {
                        $data11 = [];
                        $service_order_pile->saveOne(['order_no' => $list['orderNo']], $data11);
                        $id = $service_command->saveOne(['id' => $value['id']], ['status' => 2]);
                        return $id;
                    }
                    if ($list['cmd'] == 'notification') {
                        $service_order_pile->saveOne(['order_no' => $list['orderNo']], ['end_time' => time(), 'status' => 1]);
                        $id = $service_command->saveOne(['id' => $value['id']], ['status' => 2]);
                        return $id;
                    }
                }
            }
        }

    }

    /**
     * 获取下发充电桩指令列表
     * @return array
     * @author lijie
     * @date_time 2021/05/27
     */
    public function tcp_download()
    {
        $service_command = new HouseVillagePileCommand();
        $command_list = $service_command->getList(['type' => 1, 'status' => 1])->toArray();
        return $command_list;
    }

    /**
     * 获取上行指令列表
     * @return array
     * @author lijie
     * @date_time 2021/05/27
     */
    public function get_upload()
    {
        $service_command = new HouseVillagePileCommand();
        $command_list = $service_command->getList(['type' => 2, 'status' => 1])->toArray();
        return $command_list;
    }

    /**
     * 添加上行指令
     * @param array $data
     * @return mixed
     * @author lijie
     * @date_time 2021/05/27
     */
    public function addUploadFrame($data = [])
    {
        $service_command = new HouseVillagePileCommand();
        $res = $service_command->insertCommand($data);
        return $res;
    }


    /**
     *获取订单列表信息(不包括月卡支付)
     * @author:zhubaodi
     * @date_time: 2021/5/7 10:53
     */

    public function getOrderList($uid, $page, $limit = 10)
    {

        $service_order = new HouseVillagePilePayOrder();
        $service_equipment = new HouseVillagePileEquipment();

        $order_list = $service_order->getList(['uid' => $uid, 'pay_type' => [1, 2, 3,4,5]], true, $page, $limit);
        $orderList = [];

        if (!empty($order_list)) {
            $order_list = $order_list->toArray();
            if (!empty($order_list)) {
                foreach ($order_list as $value) {
                    $order_arr=[];
                 //   $order_arr['list']=[];
//                    $order_arr['pay_status'] = $value['pay_time'] > 1 ? '支付成功' : '支付失败';
//                    $order_arr['color'] = $value['pay_time'] > 1 ? 'green' : 'red';
                    $order_arr['list'][0]['key'] = '支付金额';
                    $order_arr['list'][0]['value'] = $value['use_money'] . '元';
                    $order_arr['list'][1]['key'] = '支付时间';
                    $order_arr['list'][1]['value'] = date('Y-m-d H:i:s', $value['add_time']);
                    $order_arr['list'][2]['key'] = '支付方式';
                    $order_arr['list'][2]['value'] = $this->pay_arr[$value['pay_type']];
                    if ($value['order_type'] == 1) {
                        if ($value['type']!=1){
                            //查询消费标准
                            $service_Equipment_Attribute = new HouseVillagePileEquipmentAttribute();
                            $attributeId = $service_Equipment_Attribute->getOne(['equipment_id' => $value['equipment_id'], 'attribute_id' => 1, 'is_del' => 1]);
                            $attributeInfo = [];
                            if (!empty($attributeId)) {
                                //pigcms_house_village_pile_attribute_set
                                $service_Attribute_Set = new HouseVillagePileAttributeSet();
                                $attributeInfo = $service_Attribute_Set->getOne(['id' => $attributeId['attribute_standard_id'], 'status' => 1]);
                            }
                            if (empty($attributeInfo)) {
                                $service_Attribute = new HouseVillagePileAttribute();
                                $attributeInfo = $service_Attribute->getOne(['value' => 1, 'type' => 2]);
                                $price_standard_list = unserialize($attributeInfo['default_value']);
                            } else {
                                $price_standard_list = unserialize($attributeInfo['standard_value']);
                            }
                            if ($value['pay_type'] == 3) {
                                $use_money = $value['card_use_money'];
                            } else {
                                $use_money = $value['use_money'];
                            }

                            $equipment_info = $service_equipment->getInfo(['id' => $value['equipment_id']]);
                            $order_arr['equipment_name'] = $equipment_info['equipment_name'];
                            /*
                            if (!empty($value['continued_time']) && $value['continued_time']>0) {
                                $order_arr['list'][3]['key'] = '预计充电时长';
                                $value['continued_time'] =(empty($price_standard_list[0][2]) || empty($price_standard_list[1][2])) ? 0 : ceil($use_money / $price_standard_list[0][2] * $price_standard_list[1][2] * 3600);
                                $order_arr['list'][3]['value'] = getFormatNumber($value['continued_time'] / 3600) . '小时';
                            } else {
                                if ($equipment_info['work_type']==1){
                                    $order_arr['list'][3]['key'] = '预计充电时长';
                                    $value['continued_time'] =(empty($price_standard_list[0][2]) || empty($price_standard_list[1][2])) ? 0 :  ceil($use_money / $price_standard_list[0][2] * $price_standard_list[1][2] * 3600);
                                    $order_arr['list'][3]['value'] = getFormatNumber($value['continued_time'] / 3600) . '小时';
                                }else{
                                    $order_arr['list'][3]['key'] = '预计充电电量';
                                    $value['use_ele']=0;
                                    if($price_standard_list[0][2] &&  $price_standard_list[2][2]){
                                        $value['use_ele'] = round($use_money / $price_standard_list[0][2] * $price_standard_list[2][2], 2);
                                    }
                                    
                                    $order_arr['list'][3]['value'] = $value['use_ele'] . '度';
                                }

                            }
                            */
                        }else{
                            $equipment_info = $service_equipment->getInfo(['id' => $value['equipment_id']]);
                            //查询消费标准
                            /*
                            $price_standard_list = $this->getPriceStandard($equipment_info['equipment_num']);
                            if (empty($price_standard_list['data']['fullBlackout'])){
                                $price_list=$price_standard_list['data']['priceStandardUniteList'][0];
                            }else{
                                $price_list=$price_standard_list['data']['priceStandardUniteList'][1];
                            }
                            //  显示开电方式（Power-on display mode） 1、时间（Time） 2、金额（Money） 3、电量（Electricity）
                            $viewType = isset($price_standard_list['data']['viewType']) ? intval($price_standard_list['data']['viewType']) : '';
                            //  消费标准类型（Consumption Standard Type）'0、自定义时间;1、功率分段;2、电量计费'
                            $standard_type = isset($price_standard_list['data']['type']) ? intval($price_standard_list['data']['type']) : '';
                            if ($value['pay_type'] == 3) {
                                $use_money = $value['card_use_money'];
                            } else {
                                $use_money = $value['use_money'];
                            }
                            */
                            $order_arr['equipment_name'] = $equipment_info['equipment_name'];
                            /*
                            if (!empty($value['continued_time'])) {
                                
                                $order_arr['list'][3]['key'] = '预计充电时长';
                                $value['continued_time'] =(empty($price_list['priceView']) || empty($price_list['param'])) ? 0 : ceil($use_money / $price_list['priceView'] * $price_list['param'] * 60);
                                $order_arr['list'][3]['value'] = getFormatNumber($value['continued_time'] / 3600) . '小时';
                                
                            } else {
                                if ($viewType == 1) {
                                    
                                    $order_arr['list'][3]['key'] = '预计充电时长';
                                    $value['continued_time'] =(empty($price_list['priceView']) || empty($price_list['param'])) ? 0 : ceil($use_money / $price_list['priceView'] * $price_list['param'] * 60);
                                    $order_arr['list'][3]['value'] = getFormatNumber($value['continued_time'] / 3600) . '小时';
                                    
                                } elseif ($viewType == 2 && $standard_type == 1) {
                                    $order_arr['list'][3]['key'] = '充电金额';
                                    $order_arr['list'][3]['value'] = $price_list['pricePay'] . '元';
                                } elseif ($viewType == 2 && $standard_type == 2) {
                                    $order_arr['list'][3]['key'] = '预计充电电量';
									$value['use_ele']=0;
									if($price_list['priceView'] &&  $price_list['param']){
										$value['use_ele'] = round($use_money / $price_list['priceView'] * $price_list['param'], 2);
									}
                                    $order_arr['list'][3]['value'] = $value['use_ele'] . '度';
                                } elseif ($equipment_info['work_type']==1){
                                    $order_arr['list'][3]['key'] = '预计充电时长';
                                    $value['continued_time'] =(empty($price_list['priceView']) || empty($price_list['param'])) ? 0 : ceil($use_money / $price_list['priceView'] * $price_list['param'] * 60);
                                    $order_arr['list'][3]['value'] = getFormatNumber($value['continued_time'] / 3600) . '小时';
                                }else{
                                    $order_arr['list'][3]['key'] = '预计充电电量';
                                    $value['use_ele']=0;
                                    if(!empty($price_list['priceView']) && !empty($price_list['param'])){
                                        $value['use_ele'] = round($use_money / $price_list['priceView'] * $price_list['param'], 2);
                                    }
                                    
                                    $order_arr['list'][3]['value'] = $value['use_ele'] . '度';
                                }
                            }
                            */
                        }
                    } else {
                        $order_arr['equipment_name'] = '月卡购买';
                    }
                    if (isset($order_arr['list']) && $order_arr['list'] && isset($value['order_no']) && $value['order_no']) {
                        $lenKey = count($order_arr['list']);
                        $order_arr['list'][$lenKey]['key'] = '消费订单编号';
                        $order_arr['list'][$lenKey]['value'] = $value['order_no'];
                    }
                    if (isset($order_arr['list']) && $order_arr['list'] && isset($value['order_id']) && $value['order_id']) {
                        $lenKey = count($order_arr['list']);
                        $order_arr['list'][$lenKey]['key'] = '订单号';
                        $order_arr['list'][$lenKey]['value'] = $value['order_id'];
                    }
                    $order_arr['money'] = $value['use_money'];
                    $order_arr['order_id'] = $value['id'];
                    if ($value['status']==2) {
                        // 状态2设备开电也是订单充电中
                        $value['status'] = 0;
                    }
                    $order_arr['status'] = $value['status']; //0:充电中 1：已完成
                    $is_refund = 0;
                    if (($value['is_refund'] == 0) && $value['pay_time'] > 1 && $value['status'] == 1) {
                        $is_refund = 1;
                    }
                    if ($value['is_refund'] == 2) {
                        $is_refund = 2;
                        $order_arr['pay_status'] = '退款中';
                        $order_arr['is_pay'] = 2;
                        $order_arr['color'] = 'red';
                    } elseif ($value['is_refund'] == 3) {
                        $order_arr['pay_status'] = '已退款';
                        $order_arr['is_pay'] = 3;
                        $order_arr['color'] = 'blue';
                    } elseif ($value['is_refund'] == 4) {
                        $order_arr['pay_status'] = '支付成功';
                        $order_arr['is_pay'] = 4;
                        $order_arr['color'] = 'red';
                    } else {
                        $order_arr['pay_status'] = $value['pay_time'] > 1 ? '支付成功' : '支付失败';
                        if ($value['order_type'] == 2) {
                            $order_arr['is_pay'] = 5;
                        } else {
                            $order_arr['is_pay'] = $value['pay_time'] > 1 ? 1 : 0;
                        }
                        $order_arr['color'] = $value['pay_time'] > 1 ? 'green' : 'red';
                    }
                    // 显示申请退款或者显示取消退款  不显示
                    $order_arr['is_refund'] = $is_refund; //0：不显示 1：申请退款 2：取消退款
                    $orderList[] = $order_arr;
                }
            }
        }
        return $orderList;
    }

    /**
     * 订单详情
     * @param $uid
     * @param $order_id
     * @author: liukezhu
     * @date : 2021/7/20
     */
    public function getOrderListDetails($uid, $order_id)
    {
        $service_order = new HouseVillagePilePayOrder();
        $service_equipment = new HouseVillagePileEquipment();
        $order_list = $service_order->get_one(['uid' => $uid, 'id' => $order_id]);
        if (!$order_list) {
            throw new \think\Exception("订单不存在！");
        }
        $equipment_info = $service_equipment->getInfo(['id' => $order_list['equipment_id']]);
        $continued_time = 0;
        $nowTime = time();
        $use_ele='';
        if ($equipment_info['type']!=1){
            // 1-计时版 2-计量版
            $work_type = isset($equipment_info['work_type']) ? intval($equipment_info['work_type']) : 0;
            //查询消费标准
            $service_Equipment_Attribute = new HouseVillagePileEquipmentAttribute();
            $attributeId = $service_Equipment_Attribute->getOne(['equipment_id' => $equipment_info['id'], 'attribute_id' => 1, 'is_del' => 1]);
            $attributeInfo = [];
            if (!empty($attributeId)) {
                $service_Attribute_Set = new HouseVillagePileAttributeSet();
                $attributeInfo = $service_Attribute_Set->getOne(['id' => $attributeId['attribute_standard_id'], 'status' => 1]);
            }
            if (empty($attributeInfo)) {
                $service_Attribute = new HouseVillagePileAttribute();
                $attributeInfo = $service_Attribute->getOne(['value' => 1, 'type' => 2]);
                $price_standard_list = unserialize($attributeInfo['default_value']);
            } else {
                $price_standard_list = unserialize($attributeInfo['standard_value']);
            }

            if ($order_list['pay_type'] == 3) {
                $use_money = $order_list['card_use_money'];
            } else {
                $use_money = $order_list['use_money'];
            }
            if(isset($price_standard_list['ffee']) && $price_standard_list['ffee']){
                $price_standard_list[0][2]=$price_standard_list[0][2]+$price_standard_list['ffee']['2'];
            }
            if (intval($order_list['continued_time']) > 1) {
                $type = 1;
                $use_ele='';
                $order_list['continued_time'] =(empty($price_standard_list[0][2]) || empty($price_standard_list[1][2])) ? 0 :  ceil($use_money / $price_standard_list[0][2] * $price_standard_list[1][2] * 3600);
                $estimate = getFormatNumber($order_list['continued_time'] / 3600) . '小时';
                $continued_time = $order_list['continued_time'] - (time() - $order_list['pay_time']);
            } 
            elseif (intval($order_list['use_ele']) > 1) {
                if ($equipment_info['type'] == 1) {
                    //获取充电订单状态
                    $url = $this->lbbtechCenterUrl .'/open/queryChargeOrderDetail';
                    $data = [
                        'token' => $this->lbbtechCenterToken,
                        'orderNum' => $order_list['order_serial']
                    ];
                    $order_status = http_request($url, 'POST', http_build_query($data));
                    if ($order_status[0] == 200) {
                        $order_info_lbbtech = json_decode($order_status[1], true);
                        if (isset($order_info_lbbtech['code']) && $order_info_lbbtech['code'] == -1) {
                            throw new \think\Exception($order_info_lbbtech['error']);
                        }
                        $use_ele = $order_info_lbbtech['data']['actualElectricQuantity'];
                    }
                } elseif ($equipment_info['type'] == 2) {
                    $use_ele = $order_list['use_ele'];
                    $data = [
                        'cmd' => 'portStatus',
                        'equipment_num' => $equipment_info['equipment_num'],
                    ];
                    $command_data = [
                        'command' => json_encode($data),
                        'type' => 1,
                        'status' => 1,
                        'addtime' => time(),
                    ];
                    $service_command = new HouseVillagePileCommand();
                    $service_command->insertCommand($command_data);
                }
                $type = 2;
                if($use_ele){
                    $use_ele=$use_ele.'度';
                }
                $estimate =(empty($price_standard_list[0][2]) || empty($price_standard_list[2][2])) ? 0 : round($use_money / $price_standard_list[0][2] * $price_standard_list[2][2], 2) . '度';
            }
            elseif ($order_list['status'] == 0 || $order_list['status'] == 2) {
                if ($order_list['pay_type'] == 3) {
                    $service_Card_User = new HouseVillagePileMonthCardUser();
                    $where[] = ['uid', '=', $order_list['uid']];
                    $where[] = ['status', '=', 1];
                    $where[] = ['expire_time', '>', $nowTime];
                    $cardUserInfo = $service_Card_User->getOne($where);
                    $use_ele = round($cardUserInfo['per_price'] / $price_standard_list[0][2] * $price_standard_list[2][2], 2);
                    $continued_time = ceil($cardUserInfo['per_price'] / $price_standard_list[0][2] * $price_standard_list[1][2] * 3600);
                } else {
                    $use_ele = round($order_list['use_money'] / $price_standard_list[0][2] * $price_standard_list[2][2], 2);
                    $continued_time = ceil($order_list['use_money'] / $price_standard_list[0][2] * $price_standard_list[1][2] * 3600);
                }
                fdump_api(['msg' => '支付结果没有拿到直接计算性返回', 'use_ele' => $use_ele, 'continued_time' => $continued_time],'pile/getOrderListDetails',1);
                if (intval($continued_time) > 1) {
                    $type = 1;
                    $use_ele='';
                    $order_list['continued_time'] = $continued_time;
                    $estimate = getFormatNumber($continued_time / 3600) . '小时';
                    fdump_api(['msg' => '返回充电时长', 'estimate' => $estimate, 'order_list' => $order_list && !is_array($order_list) ? $order_list->toArray() : $order_list],'pile/getOrderListDetails',1);
                } else {
                    if ($equipment_info['type'] == 1) {
                        //获取充电订单状态
                        $url = $this->lbbtechCenterUrl .'/open/queryChargeOrderDetail';
                        $data = [
                            'token' => $this->lbbtechCenterToken,
                            'orderNum' => $order_list['order_serial']
                        ];
                        $order_status = http_request($url, 'POST', http_build_query($data));
                        if ($order_status[0] == 200) {
                            $order_info_lbbtech = json_decode($order_status[1], true);
                            if (isset($order_info_lbbtech['code']) && $order_info_lbbtech['code'] == -1) {
                                throw new \think\Exception($order_info_lbbtech['error']);
                            }
                            $use_ele = $order_info_lbbtech['data']['actualElectricQuantity'];
                        }
                        fdump_api(['msg' => '支付结果-获取充电订单状态', 'data' => $data, 'order_status' => $order_status],'pile/getOrderListDetails',1);
                    } elseif ($equipment_info['type'] == 2) {
                        $use_ele = $order_list['use_ele'];
                        $data = [
                            'cmd' => 'portStatus',
                            'equipment_num' => $equipment_info['equipment_num'],
                        ];
                        $command_data = [
                            'command' => json_encode($data),
                            'type' => 1,
                            'status' => 1,
                            'addtime' => time(),
                        ];
                        $service_command = new HouseVillagePileCommand();
                        $service_command->insertCommand($command_data);
                        fdump_api(['msg' => '支付结果-充电桩插座状态获取', 'data' => $data, 'command_data' => $command_data],'pile/getOrderListDetails',1);
                    }
                    $type = 2;
                    if($use_ele){
                        $use_ele=$use_ele.'度';
                    }
                    $estimate =(empty($price_standard_list[0][2]) || empty($price_standard_list[2][2])) ? 0 : round($use_money / $price_standard_list[0][2] * $price_standard_list[2][2], 2) . '度';
                    fdump_api(['msg' => '支付结果-type', 'type' => $type, 'estimate' => $estimate],'pile/getOrderListDetails',1);
                }
            } else {
                if (isset($price_standard_list[0][2]) && isset($price_standard_list[2][2])) {
                    $use_ele = round($use_money / $price_standard_list[0][2] * $price_standard_list[2][2], 2);
                }
                if (isset($price_standard_list[0][2]) && isset($price_standard_list[2][2])) {
                    $continued_time = ceil($use_money / $price_standard_list[0][2] * $price_standard_list[1][2] * 3600);
                }
                $pay_time = isset($order_list['pay_time']) && intval($order_list['pay_time']) > 0 ? intval($order_list['pay_time']) : time();
                if (intval($continued_time) > 1) {
                    $type = 1;
                    $estimate = getFormatNumber($continued_time / 3600) . '小时';
                    $continued_time = $continued_time - (time() - $pay_time);
                    $use_ele='';
                } else {
                    $type = 2;
                    $use_ele='';
                    $estimate =(empty($price_standard_list[0][2]) || empty($price_standard_list[2][2])) ? 0 : round($use_money / $price_standard_list[0][2] * $price_standard_list[2][2], 2) . '度';
                }
            }
            if (!isset($type) || $type <= 0) {
                fdump_api(['msg' => '类型不存在，请重新访问', 'order_list' => $order_list && !is_array($order_list) ? $order_list->toArray() : $order_list],'pile/getOrderListDetails',1);
                throw new \think\Exception("类型不存在，请重新访问！");
            }
        }
        else{
            //查询消费标准
            $price_standard_list = $this->getPriceStandard($equipment_info['equipment_num']);
            fdump_api([$price_standard_list,$order_list],'pile/getOrderListDetails',1);
            $price_standard_list_data = isset($price_standard_list['data']) ? $price_standard_list['data'] : [];
            if (empty($price_standard_list_data['fullBlackout'])) {
                $price_list = $price_standard_list_data['priceStandardUniteList'][1];
            } else {
                $price_list = $price_standard_list_data['priceStandardUniteList'][0];
            }
            
            if ($order_list['pay_type'] == 3) {
                $use_money = $order_list['card_use_money'];
            } else {
                $use_money = $order_list['use_money'];
            }
            if (intval($order_list['continued_time']) > 1) {
                $type = 1;
              //  $order_list['continued_time'] = (empty($price_list['priceView']) || empty($price_list['param']))  ? 0 : ceil($use_money / $price_list['priceView'] * $price_list['param'] * 60);
                $estimate = getFormatNumber($order_list['continued_time'] / 3600) . '小时';
                $continued_time = $order_list['continued_time'] - (time() - $order_list['pay_time']);
            } elseif (floatval($order_list['use_ele']) > 0) {
                if ($equipment_info['type'] == 1) {
                    //获取充电订单状态
                    $url = $this->lbbtechCenterUrl .'/open/queryChargeOrderDetail';
                    $data = [
                        'token' => $this->lbbtechCenterToken,
                        'orderNum' => $order_list['order_serial']
                    ];
                    $order_status = http_request($url, 'POST', http_build_query($data));
                    fdump_api([$order_status,$data],'pile/queryChargeOrderDetail',1);
                    if ($order_status[0] == 200) {
                        $order_info_lbbtech = json_decode($order_status[1], true);
                        if (isset($order_info_lbbtech['code']) && $order_info_lbbtech['code'] == -1) {
                            throw new \think\Exception($order_info_lbbtech['error']);
                        }
                        $use_ele = $order_info_lbbtech['data']['actualElectricQuantity'];
                    }
                    if (!isset($use_ele)||empty($use_ele)){
                        $use_ele= $order_list['use_ele'];
                    }
                } elseif ($equipment_info['type'] == 2) {
                    $use_ele = $order_list['actual_ele'];
                    $data = [
                        'cmd' => 'portStatus',
                        'equipment_num' => $equipment_info['equipment_num'],
                    ];
                    $command_data = [
                        'command' => json_encode($data),
                        'type' => 1,
                        'status' => 1,
                        'addtime' => time(),
                    ];
                    $service_command = new HouseVillagePileCommand();
                    $service_command->insertCommand($command_data);
                }
                $type = 2;
                $estimate =(empty($price_list['priceView']) || empty($price_list['param'])) ? 0 :  round($use_money / $price_list['priceView'] * $price_list['param'], 2) . '度';
                if (empty($estimate)){
                    $estimate= $order_list['use_ele']. '度';
                }
                $use_ele=$use_ele.'度';
            } else {
                //  显示开电方式（Power-on display mode） 1、时间（Time）  2、电量（Electricity） 3、金额（Money）
                $viewType = isset($price_standard_list_data['viewType']) ? intval($price_standard_list_data['viewType']) : '';
                $standard_type = isset($price_standard_list_data['type']) ? intval($price_standard_list_data['type']) : 0;
                if ($viewType == 1) {
                    if ($order_list['pay_type'] == 3) {
                        if (empty($price_standard_list_data['fullBlackout'])) {
                            if ($price_standard_list_data['priceStandardUniteList'][0]['paramUnit'] == 'min') {
                                $continued_time = $price_standard_list_data['priceStandardUniteList'][0]['paramView'] * 60;
                            } else {
                                $continued_time = $price_standard_list_data['priceStandardUniteList'][0]['paramView'] * 3600;
                            }
                        } else {
                            if ($price_standard_list_data['priceStandardUniteList'][1]['paramUnit'] == 'min') {
                                $continued_time = $price_standard_list_data['priceStandardUniteList'][1]['paramView'] * 60;
                            } else {
                                $continued_time = $price_standard_list_data['priceStandardUniteList'][1]['paramView'] * 3600;
                            }
                        }
                    } else {
                        foreach ($price_standard_list_data['priceStandardUniteList'] as $val) {
                            if ($val['pricePay'] == $order_list['use_money']) {
                                if ($val['fullBlackoutForButton'] == 1) {
                                    $continued_time = $val['param'] * 60;
                                } elseif ($val['paramUnit'] == 'hour') {
                                    $continued_time = $val['paramView'] * 3600;
                                } else {
                                    $continued_time = $val['paramView'] * 60;
                                }
                            }
                        }
                    }
                    $type = 1;
                    $pay_time = isset($order_list['pay_time']) && intval($order_list['pay_time']) > 0 ? intval($order_list['pay_time']) : time();
                    $continued_time = $continued_time - (time() - $pay_time);
                } elseif ($viewType == 2 && $standard_type == 2) {
                    if ($order_list['pay_type'] == 3) {
                        if ($price_standard_list_data['priceStandardUniteList'][0]['priceView'] == $order_list['use_money']) {
                            //$use_ele = $price_standard_list_data['priceStandardUniteList'][0]['paramView'];
                        }
                    } else {
                        foreach ($price_standard_list_data['priceStandardUniteList'] as $val) {
                            if ($val['pricePay'] == $order_list['use_money']) {
                                //$use_ele = $val['paramView'];
                            }
                        }
                    }
                    $type = 2;
                    if(isset($price_standard_list_data['totalElectricFee']) && $price_standard_list_data['totalElectricFee']>0){
                        $use_ele= $order_list['use_money']/$price_standard_list_data['totalElectricFee'];
                    }elseif (isset($price_standard_list_data['totalElectricFeeYuan']) && $price_standard_list_data['totalElectricFeeYuan']>0){
                        $use_ele= $order_list['use_money']/$price_standard_list_data['totalElectricFeeYuan'];
                    }
                    $use_ele=round($use_ele,2);
                    if ($use_ele) {
                        $estimate = $use_ele . '度';
                        $use_ele=$estimate;
                    } else {
                        $estimate = (empty($price_list['priceView']) || empty($price_list['param'])) ? 0 : round($use_money / $price_list['priceView'] * $price_list['param'], 2) . '度';
                        if (empty($estimate)) {
                            $estimate = $order_list['use_ele'] . '度';
                            $use_ele=$estimate;
                        }
                    }
                } elseif ($viewType == 2 && $standard_type == 1) {
                    $type = 3;
                    $estimate = $price_list['pricePay'] . '元';
                }
            } 
            if (!isset($type) || $type <= 0) {
                throw new \think\Exception("类型不存在，请重新访问！");
            }
        }

        $pay_time_text = isset($order_list['pay_time']) && $order_list['pay_time'] > 1 ? date('Y-m-d H:i:s', $order_list['pay_time']) : '--';
        $end_time_text = isset($order_list['end_time']) && $order_list['end_time'] > 1 ? date('Y-m-d H:i:s', $order_list['end_time']) : '--';
        $get_point=0;
        $configCustomizationService=new ConfigCustomizationService();
        $pile_charge_consume_point=$configCustomizationService->getPileChargeConsumePoint();
        $is_show_point=0;
        if($pile_charge_consume_point>0){
            $is_show_point=1;
        }
        $equipment_type='tcp';
        if($equipment_info['type']==1){
            $equipment_type='lvv';
        }else if($equipment_info['type']>2){
            $equipment_type=$equipment_info['type'];
        }
        $service_fee=0;  //服务费
        $electric_fee=$order_list['use_money']-$order_list['refund_money'];  //电费
        if(isset($order_list['service_fee'])){
            $service_fee=$order_list['service_fee'];
            $electric_fee=$electric_fee-$service_fee;
            $electric_fee=$electric_fee>0 ? round($electric_fee,2):0;
        }
        $point_name='积分';
        $score_name=cfg('score_name');
        if($score_name){
            $point_name= trim($score_name);
        }
        $nowtime=time();
        $work_time_str='--';
       
        if ($order_list['status'] != 1) {
            if (!isset($continued_time)||empty($continued_time)||$continued_time<0){
                $continued_time=0;
            }
            $progress='--';
            if($pile_charge_consume_point>0){
                $pile_charge_give_point=cfg('pile_charge_give_point');
                $pile_charge_give_point=$pile_charge_give_point ? $pile_charge_give_point:0;
                $energy_pile_charge_give_point=cfg('energy_pile_charge_give_point');
                $energy_pile_charge_give_point=$energy_pile_charge_give_point ? $energy_pile_charge_give_point:0;
                if(isset($equipment_info['energy_type']) && $equipment_info['energy_type']==1){
                    $get_point=($order_list['use_money']*$energy_pile_charge_give_point)/100;
                }else{
                    $get_point=($order_list['use_money']*$pile_charge_give_point)/100;
                }
                $get_point=$get_point>0 ? round($get_point,2):0;
            }
            $chargingCurrent='--';
            $chargingVoltage='--';
            $use_money_tmp='--';
            if($equipment_info['type']==1){
                $equipment_type='lvv';
                //驴充充
                $orderRealtimeRecord=$this->lvvOrderLastRealtimeRecord($order_list['order_serial']);
                if($orderRealtimeRecord && is_array($orderRealtimeRecord) && isset($orderRealtimeRecord['actualElectricQuantity'])){
                    $use_ele=$orderRealtimeRecord['actualElectricQuantity'].'度';
                    $chargingCurrent=isset($orderRealtimeRecord['chargingCurrent']) ? $orderRealtimeRecord['chargingCurrent'].'安':'';
                    $chargingVoltage=isset($orderRealtimeRecord['chargingVoltage']) ? $orderRealtimeRecord['chargingVoltage'].'伏':'';
                    $electricityPrice=isset($orderRealtimeRecord['electricityPrice']) ? $orderRealtimeRecord['electricityPrice']:0;
                    $serviceFeePrice=isset($orderRealtimeRecord['serviceFeePrice']) ? $orderRealtimeRecord['serviceFeePrice']:0;
                    $service_fee=$serviceFeePrice;
                    $electric_fee=$electricityPrice;
                    $use_money_tmp=$electricityPrice+$serviceFeePrice;
                    $use_money_tmp=$use_money_tmp>0 ? round($use_money_tmp,2):0;
                    if(isset($orderRealtimeRecord['actualTime']) && $orderRealtimeRecord['actualTime']>0){
                        $estimate=$orderRealtimeRecord['actualTime'].'分钟';
                        if($orderRealtimeRecord['actualTime']>60){
                            $shih=$orderRealtimeRecord['actualTime']/60;
                            $shih=floor($shih);
                            $shih=intval($shih);
                            $estimate=$shih.'小时';
                            $fenz= $orderRealtimeRecord['actualTime']-$shih*60;
                            $fenz=intval($fenz);
                            $estimate=$shih.'小时'.$fenz.'分钟';
                        }
                    }
                }else if($orderRealtimeRecord && is_array($orderRealtimeRecord) && (isset($orderRealtimeRecord['electric'])||isset($orderRealtimeRecord['voltage']))){
                    $use_ele=$orderRealtimeRecord['electric']/100;
                    $use_ele=$use_ele>0 ? round($use_ele,2).'度':'';
                    $chargingCurrent=isset($orderRealtimeRecord['current']) ? $orderRealtimeRecord['current']/100:'';
                    $chargingCurrent=$chargingCurrent>0 ? round($chargingCurrent,2).'安':'--';
                    $chargingVoltage=isset($orderRealtimeRecord['voltage']) ? $orderRealtimeRecord['voltage']/100:'';
                    $chargingVoltage=$chargingVoltage>0 ? round($chargingVoltage,2).'伏':'--';
                }
            }else if($equipment_info['type']>2){
                
                $equipment_type=$equipment_info['type'];
            }
           
            
            $end_time_tmp=0;
            if($order_list['continued_time']>0 && isset($order_list['pay_time']) && $order_list['pay_time'] > 100){
                $end_time_tmp=$order_list['pay_time']+$order_list['continued_time'];
                $progressTime= time()-$order_list['pay_time'];
                $progress=$progressTime < $order_list['continued_time'] ? $progressTime/$order_list['continued_time'] :0.99;
                $progress=$progress*100;
                $progress=round($progress,0);
            }
            if (isset($order_list['pay_time']) && $order_list['pay_time'] > 100) {
                $work_time = $nowtime - $order_list['pay_time'];
                $shih = '';
                if ($work_time > 3600) {
                    $shih = $work_time / 3600;
                    $shih = floor($shih);
                    $shih = intval($shih);
                    $work_time = $work_time - $shih * 3600;
                    $shih = $shih . '小时';
                }
                $fenz = '';
                if ($work_time > 60) {
                    $fenz = $work_time / 60;
                    $fenz = floor($fenz);
                    $fenz = intval($fenz);
                    $work_time = $work_time - $fenz * 60;
                    $fenz = $fenz . '分钟';
                }
                $work_time_str = $shih . $fenz;
                if (empty($work_time_str)) {
                    $work_time_str = '--';
                }
            }
            //订单使用中
            $data = [
                'use_ele' => $use_ele ? $use_ele:'--',
                'order_id' => $order_list['id'],
                'type' => $type,
                'pay_time' => $pay_time_text,
                'start_time'=>$pay_time_text,
                'start_time_day'=>date('m月d日',$order_list['pay_time']),
                'start_time_hour'=>date('H:i',$order_list['pay_time']),
                'end_time_day'=>$end_time_tmp>100 ? date('m月d日',$end_time_tmp):'--',
                'end_time_hour'=>$end_time_tmp>100 ? date('H:i',$end_time_tmp):'--',
                'estimate_duration' => $estimate,
                'continued_time' => $continued_time,
                'pay_price' => $order_list['use_money'] . '元',
                'use_price' => $use_money_tmp . '元',
                'equipment_type'=>$equipment_type,
                'equipment_name' => $equipment_info['equipment_name'],
                'socket_no' => $order_list['socket_no'] . '号',
                'equipment_num'=>$equipment_info['equipment_num'], //设备唯一编号
                'equipment_sn'=>$equipment_info['equipment_sn'], //SN标号
                'get_point'=>$get_point,
                'status'=>1,  //充电中
                'status_txt'=>'充电中',
                'charging_current'=>$chargingCurrent,
                'charging_voltage'=>$chargingVoltage,
                'is_show_point'=>$is_show_point,
                'service_fee'=>$service_fee,  //服务费
                'electric_fee'=>$electric_fee, //电费
                'progress'=>$progress,
                'point_name'=>$point_name,
                'equipment_id'=>$equipment_info['id'],
                'village_id'=>$equipment_info['village_id'],
                'work_time_str'=>$work_time_str,
                'adver_list' => $this->adverList
            ];
            if($equipment_info['type']==2){
                //tcp
                $data['equipment_num']=$equipment_info['equipment_serial'];
            }
            fdump_api(['msg' => '支付结果-返回结果', 'data' => $data],'pile/getOrderListDetails',1);
        } else {
            if (!isset($continued_time)||empty($continued_time)||$continued_time<0){
                $continued_time=0;
            }
            //订单已完成
            if (isset($order_list['pay_time']) && intval($order_list['pay_time']) > 1 && isset($order_list['end_time']) && intval($order_list['end_time']) > 1) {
                $timediff = timediff($order_list['pay_time'], $order_list['end_time']);
                $actual_duration = $timediff['hour'] . '.' . $timediff['min'] . '小时';
            } else {
                $actual_duration = '--';
            }
            $use_ele=$order_list['use_ele']>0 ? $order_list['use_ele'].'度':'--';
            if($type==2){
                $actual_duration=$order_list['use_ele']>0 ?$order_list['use_ele'].'度':'--';
            }
            if(isset($order_list['consume_point']) && $order_list['consume_point']){
                $consume_point_info=json_decode($order_list['consume_point'],true);
                if($consume_point_info && isset($consume_point_info['consume_point'])){
                    $get_point=$consume_point_info['consume_point'];
                }
            }
            $use_price=$order_list['use_money']-$order_list['refund_money'];
            $use_price=round($use_price,2);
            if (isset($order_list['end_time']) && $order_list['end_time'] > 100 && $order_list['pay_time']>100) {
                $work_time = $order_list['end_time']-$order_list['pay_time'];
                $shih = '';
                if ($work_time > 3600) {
                    $shih = $work_time / 3600;
                    $shih = floor($shih);
                    $shih = intval($shih);
                    $work_time = $work_time - $shih * 3600;
                    $shih = $shih . '小时';
                }
                $fenz = '';
                if ($work_time > 60) {
                    $fenz = $work_time / 60;
                    $fenz = floor($fenz);
                    $fenz = intval($fenz);
                    $work_time = $work_time - $fenz * 60;
                    $fenz = $fenz . '分钟';
                }
                $work_time_str = $shih . $fenz;
                if (empty($work_time_str)) {
                    $work_time_str = '--';
                }
            }
            $data = [
                'order_id' => $order_list['id'],
                'type' => $type,
                'continued_time' => $continued_time,
                'equipment_name' => $equipment_info['equipment_name'],
                'socket_no' => $order_list['socket_no'] . '号',
                'pay_price' => $order_list['use_money'] . '元',
                'use_price'=>$use_price,
                'refund_money'=>$order_list['refund_money'] . '元',
                'pay_time' => $pay_time_text,
                'estimate_duration' => $estimate,
                'actual_duration' => $actual_duration,
                'use_ele' => $use_ele,
                'start_time' => $pay_time_text,
                'end_time' => $end_time_text,
                'start_time_day'=>date('m月d日',$order_list['pay_time']),
                'start_time_hour'=>date('H:i',$order_list['pay_time']),
                'end_time_day'=>$order_list['end_time']>100 ? date('m月d日',$order_list['end_time']):'--',
                'end_time_hour'=>$order_list['end_time']>100 ? date('H:i',$order_list['end_time']):'--',
                'equipment_num'=> $equipment_info['equipment_num'], //设备唯一编号
                'equipment_sn'=>$equipment_info['equipment_sn'], //SN标号
                'get_point'=>$get_point,
                'is_show_point'=>$is_show_point,
                'charging_current'=>'--',
                'charging_voltage'=>'--',
                'status'=>2, //已结束
                'status_txt'=>'充电完成',
                'equipment_type'=>$equipment_type,
                'service_fee'=>$service_fee,  //服务费
                'electric_fee'=>$electric_fee, //电费
                'progress'=>100,
                'point_name'=>$point_name,
                'equipment_id'=>$equipment_info['id'],
                'village_id'=>$equipment_info['village_id'],
                'work_time_str'=>$work_time_str,
                'adver_list' => $this->adverList
            ];
            if($equipment_info['type']==2){
                //tcp
                $data['equipment_num']=$equipment_info['equipment_serial'];
            }
            fdump_api(['msg' => '支付结果-返回结果', 'data' => $data],'pile/getOrderListDetails',1);
        }
        return $data;
    }
    /****
     *获取订单最新实时数据
     */
    public function lvvOrderLastRealtimeRecord($orderNum=''){
        $url = $this->lbbtechCenterUrl .'/open/getLastChargeOrderRealtimeRecord';
        $data = [
            'token' => $this->lbbtechCenterToken,
            'orderNum' => $orderNum
        ];
        $orderRealtimeRecord = http_request($url, 'POST', http_build_query($data));
        fdump_api(['data' => $data, 'orderRealtimeRecord' => $orderRealtimeRecord],'pile/lvvOrderLastRealtimeRecord',1);
        if ($orderRealtimeRecord[0] == 200 && $orderRealtimeRecord[1]) {
            $orderRealtimeInfo = json_decode($orderRealtimeRecord[1], true);
            $orderRealtimeArr = $orderRealtimeInfo && isset($orderRealtimeInfo['data']) ? $orderRealtimeInfo['data']:array();
            return $orderRealtimeArr;
        }
        return '';
    }
    
    /***
     **获取订单所有实时数据
     **/
    public function lvvOrderRealtimeRecord($orderNum='',$getAll=false){
        $url = $this->lbbtechCenterUrl .'/open/getChargeOrderRealtimeRecord';
        $data = [
            'token' => $this->lbbtechCenterToken,
            'orderNum' => $orderNum
        ];
        $orderRealtimeRecord = http_request($url, 'POST', http_build_query($data));
        fdump_api(['data' => $data, 'orderRealtimeRecord' => $orderRealtimeRecord],'pile/lvvOrderRealtimeRecord',1);
        if ($orderRealtimeRecord[0] == 200 && $orderRealtimeRecord[1]) {
            $orderRealtimeInfo = json_decode($orderRealtimeRecord[1], true);
            $orderRealtimeArr = $orderRealtimeInfo && isset($orderRealtimeInfo['data']) ? $orderRealtimeInfo['data']:array();
            if($getAll){
                return $orderRealtimeArr;
            }
            if(isset($orderRealtimeArr['0']) && is_array($orderRealtimeArr['0'])){
                $count=count($orderRealtimeArr);
                $ikey=$count-1;
                return $orderRealtimeArr[$ikey];
            }else{
                return $orderRealtimeArr;
            }
        }
        return '';
    }

    /**
     *获取订单列表信息（月卡支付）
     * @author:zhubaodi
     * @date_time: 2021/5/25 10:53
     */

    public function getConsumeList($uid, $cardId, $page, $limit = 10)
    {
        if (empty($cardId)) {
            throw new \think\Exception("月卡id不能为空");
        }

        $service_card = new HouseVillagePileMonthCardUser();
        $service_order = new HouseVillagePilePayOrder();
        $service_equipment = new HouseVillagePileEquipment();


        $cardInfo = $service_card->getOne(['id' => $cardId, 'uid' => $uid]);
        if (empty($cardInfo)) {
            throw new \think\Exception("月卡信息不存在");
        }

        $where = [
            ['uid', '=', $uid],
            ['add_time', '>=', $cardInfo['add_time']],
            ['add_time', '<=', $cardInfo['expire_time']],
            ['pay_time', '>', 1],
        ];
        $order_list = $service_order->getList($where, true, $page, $limit);


        $orderList = [];
        if (!empty($order_list)) {
            $order_list = $order_list->toArray();
            if (!empty($order_list)) {
                $equipment = $service_equipment->getInfo(['id' => $order_list[0]['equipment_id']]);

                /*$PriceStandard=$this->deviceStatus11($equipment['equipment_num']);
                print_r($PriceStandard);exit;
                if ($PriceStandard['data']['fullBlackout']==0){
                    $money=$PriceStandard['data']['priceStandardUniteList'][0]['pricePay'];
                }else{
                    $money=$PriceStandard['data']['priceStandardUniteList'][1]['pricePay'];
                }*/

                foreach ($order_list as $value) {
                    if ($value['order_type'] == 2) {
                        $order_arr['name'] = '购买';
                        $order_arr['pay_money'] = $value['use_money'] . '元';
                    } else {
                        $order_arr['name'] = '充电抵扣费用';
                        $order_arr['pay_money'] = $cardInfo['per_price'] . '元';
                    }
                    $order_arr['addtime'] = date('Y-m-d H:i:s', $value['add_time']);
                    $orderList[] = $order_arr;
                }
            }
        }
        return $orderList;
    }

    /**
     * 获取购买的月卡详情
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/27 13:09
     */

    public function getOrderCardInfo($uid, $cardId, $id)
    {

        $user = new User();
        $userInfo = $user->getOne(['uid' => $uid]);
        if (!$userInfo) {
            throw new \think\Exception("该用户不存在！");
        }
        $arr = [];
        $service_Card_User = new HouseVillagePileMonthCardUser();

        $cardUserInfo = $service_Card_User->getOne(['uid' => $uid, 'id' => $cardId]);
        $service_Card = new HouseVillagePileMonthCard();

        if (!empty($cardUserInfo)) {
            $cardUserList = [];
            $cardInfo = $service_Card->getOne(['id' => $cardUserInfo['card_id']]);
            $cardUserList['id'] = $cardUserInfo['id'];
            $cardUserList['name'] = $cardInfo['card_name'];
            if ($cardUserInfo['expire_time'] > time()) {
                $cardUserList['status'] = 1;
            } else {
                $cardUserList['status'] = 2;
            }
            $cardUserList['expire_time'] = date('Y-m-d', $cardUserInfo['expire_time']);

            $cardUserList['surplus_num'] = $cardUserInfo['surplus_num'];
            $cardUserList['list'][0]['name'] = '月卡价格';
            $cardUserList['list'][0]['value'] = $cardUserInfo['price'].'元';
            $cardUserList['list'][1]['name'] = '每天最多使用次数';
            $cardUserList['list'][1]['value'] = $cardUserInfo['max_num'].'次';
            $cardUserList['list'][3]['name'] = '共计使用次数';
            $cardUserList['list'][3]['value'] = $cardUserInfo['all_num'] - $cardUserInfo['surplus_num'].'次';
            $service_equipment = new HouseVillagePileEquipment();
            $equipmentInfo = $service_equipment->getInfo(['id' => $id]);

            if ($equipmentInfo['type'] == 1) {
                //查询消费标准
                $price_standard_list = $this->getPriceStandard($equipmentInfo['equipment_num']);

                if ($price_standard_list['data']['fullBlackout'] == 0) {
                    if ($price_standard_list['data']['type'] == 0) {
                        $cardUserList['list'][2]['name'] = '每次预计充电时长';
                        $cardUserList['list'][2]['value'] = $price_standard_list['data']['priceStandardUniteList'][0]['paramView'] . $price_standard_list['data']['priceStandardUniteList'][0]['paramUnitName'];
                        //$cardUserInfo['priceName']='每次预计充电时长'.$price_standard_list['data']['priceStandardUniteList'][0]['paramView'].$price_standard_list['data']['priceStandardUniteList'][0]['paramUnitName'];
                    }
                    if ($price_standard_list['data']['type'] == 1) {
                        $cardUserList['list'][2]['name'] = '每次充电金额';
                        $cardUserList['list'][2]['value'] = $price_standard_list['data']['priceStandardUniteList'][0]['pricePay'];
                    }
                    if ($price_standard_list['data']['type'] == 2) {
                        $cardUserList['list'][2]['name'] = '每次预计充电电量';
                        $cardUserList['list'][2]['value'] = $price_standard_list['data']['priceStandardUniteList'][0]['paramView'] . $price_standard_list['data']['priceStandardUniteList'][0]['paramUnitName'];
                        //$cardUserInfo['priceName']='每次预计充电电量'.$price_standard_list['data']['priceStandardUniteList'][0]['paramView'].$price_standard_list['data']['priceStandardUniteList'][0]['paramUnitName'];
                    }
                } else {
                    if ($price_standard_list['data']['type'] == 0) {
                        $cardUserList['list'][2]['name'] = '每次预计充电时长';
                        $cardUserList['list'][2]['value'] = $price_standard_list['data']['priceStandardUniteList'][1]['paramView'] . $price_standard_list['data']['priceStandardUniteList'][1]['paramUnitName'];
                        //  $cardUserInfo['priceName']='每次预计充电时长'.$price_standard_list['data']['priceStandardUniteList'][1]['paramView'].$price_standard_list['data']['priceStandardUniteList'][1]['paramUnitName'];
                    }
                    if ($price_standard_list['data']['type'] == 1) {
                        $cardUserList['list'][2]['name'] = '每次充电金额';
                        $cardUserList['list'][2]['value'] = $price_standard_list['data']['priceStandardUniteList'][1]['pricePay'];
                    }
                    if ($price_standard_list['data']['type'] == 2) {
                        $cardUserList['list'][2]['name'] = '每次预计充电电量';
                        $cardUserList['list'][2]['value'] = $price_standard_list['data']['priceStandardUniteList'][1]['paramView'] . $price_standard_list['data']['priceStandardUniteList'][1]['paramUnitName'];
                        //  $cardUserInfo['priceName']='每次预计充电电量'.$price_standard_list['data']['priceStandardUniteList'][1]['paramView'].$price_standard_list['data']['priceStandardUniteList'][1]['paramUnitName'];
                    }
                }

            } elseif ($equipmentInfo['type'] == 2) {
                //查询消费标准
                $service_Equipment_Attribute = new HouseVillagePileEquipmentAttribute();
                $attributeId = $service_Equipment_Attribute->getOne(['equipment_id' => $equipmentInfo['id'], 'attribute_id' => 1, 'is_del' => 1]);
                $attributeInfo = [];
                if (!empty($attributeId)) {
                    //pigcms_house_village_pile_attribute_set
                    $service_Attribute_Set = new HouseVillagePileAttributeSet();
                    $attributeInfo = $service_Attribute_Set->getOne(['id' => $attributeId['attribute_standard_id'], 'status' => 1]);
                }
                if (!empty($attributeInfo)) {
                    $price_standard_list = unserialize($attributeInfo['standard_value']);
                }
                if (!empty($price_standard_list)) {
                    $standard_fee=$price_standard_list[0][2];
                    $service_fee=0;
                    if(isset($price_standard_list['ffee']) && $price_standard_list['ffee'][2]){
                        $standard_fee=$standard_fee+$price_standard_list['ffee'][2];
                        $standard_fee=round($standard_fee,2);
                        $service_fee=$price_standard_list['ffee'][2];
                    }
                    if ($equipmentInfo['work_type'] == 1) {
                        $cardUserList['list'][2]['name'] = '每次预计充电时长';
                        $cardUserList['list'][2]['value'] = ceil(($cardUserInfo['per_price'] /$standard_fee) * $price_standard_list[1][2]) . '小时';
                    }
                    if ($equipmentInfo['work_type'] == 2) {
                        $cardUserList['list'][2]['name'] = '每次预计充电电量';
                        $cardUserList['list'][2]['value'] = ceil(($cardUserInfo['per_price'] / $standard_fee) * $price_standard_list[2][2]) . '度';

                    }
                }

            }elseif ($equipmentInfo['type'] == 21){
                //查询消费标准
                $service_Equipment_Attribute = new HouseVillagePileEquipmentAttribute();
                $attributeId = $service_Equipment_Attribute->getOne(['equipment_id' => $equipmentInfo['id'], 'attribute_id' => 1, 'is_del' => 1]);
                $attributeInfo = [];
                if (!empty($attributeId)) {
                    //pigcms_house_village_pile_attribute_set
                    $service_Attribute_Set = new HouseVillagePileAttributeSet();
                    $attributeInfo = $service_Attribute_Set->getOne(['id' => $attributeId['attribute_standard_id'], 'status' => 1]);
                }
                if (!empty($attributeInfo)) {
                    $price_standard_list = unserialize($attributeInfo['standard_value']);
                }
                if (!empty($price_standard_list)) {
                    $standard_fee=$price_standard_list[0][2];
                    $service_fee=0;
                    if(isset($price_standard_list['ffee']) && $price_standard_list['ffee'][2]){
                        $standard_fee=$standard_fee+$price_standard_list['ffee'][2];
                        $standard_fee=round($standard_fee,2);
                        $service_fee=$price_standard_list['ffee'][2];
                    }
                    if ($equipmentInfo['work_type'] == 1) {
                        $cardUserList['list'][2]['name'] = '每次预计充电时长';
                        $cardUserList['list'][2]['value'] = ceil(($cardUserInfo['per_price'] / $standard_fee) * $price_standard_list[1][2]) . '小时';
                    }
                    if ($equipmentInfo['work_type'] == 2) {
                        $cardUserList['list'][2]['name'] = '每次预计充电电量';
                        $cardUserList['list'][2]['value'] = ceil(($cardUserInfo['per_price'] / $standard_fee) * $price_standard_list[2][2]) . '度';

                    }
                }
            }


        }
        $arr['cardUserInfo'] = $cardUserList;
        return $arr;
    }


    /**
     * 获取插座状态
     * @author:zhubaodi
     * @date_time: 2021/5/5 14:14
     */
    public function deviceStatus11($device_id)
    {
        if (empty($device_id)) {
            throw new \think\Exception("设备id不能为空");
        }
        //获取插座状态
        $url = $this->lbbtechCenterUrl .'/open/deviceStatus';
        $data = [
            'token' => $this->lbbtechCenterToken,
            'deviceId' => $device_id
        ];
        $socket_status = http_request($url, 'POST', http_build_query($data));
        if ($socket_status[0] == 200) {
            $socket_list = json_decode($socket_status[1], true);
        } else {
            $socket_list = [];
        }
        $socket_arr = [];
        if ($socket_list) {
            $socket_arr = $socket_list['data']['device']['data']['ports'];
        }
        return $socket_list;
    }

    public function getTypePortsState($deviceId)
    {
        if (empty($deviceId)) {
            throw new \think\Exception("设备id不能为空");
        }
        //获取插座状态
        $url = $this->outeruser_url . '/api/charge/outGetPorts?deviceId=' . $deviceId;
        $socket_status = http_request($url, 'GET');
        $socket_data = [];
        if (!empty($socket_status) && $socket_status[0] == 200) {
            $socket_status = json_decode($socket_status[1], true);
            if ($socket_status['code'] == 200) {
                $socketList = $socket_status['ports'];
                foreach ($socketList as $k => $value) {
                    $socket_data[$k]['name'] = $value['port'] + 1;
                    $socket_data[$k]['status'] = $value['portType'];

                }
            }
        }
        $last_names = array_column($socket_data, 'name');
        array_multisort($last_names, SORT_ASC, $socket_data);
        return $socket_data;
    }
    
    /**
     * 艾特充获取插座状态
     * @author:zhubaodi
     * @date_time: 2021/7/13 11:51
     */
    public function outGetPorts($deviceId)
    {
        if (empty($deviceId)) {
            throw new \think\Exception("设备id不能为空");
        }
        //获取插座状态
        $url = $this->outeruser_url . '/api/charge/outGetPorts?deviceId=' . $deviceId;
        $socket_status = http_request($url, 'GET');
        $socket_data = [];
        if (!empty($socket_status) && $socket_status[0] == 200) {
            $socket_status = json_decode($socket_status[1], true);
            if ($socket_status['code'] == 200) {
                $socketList = $socket_status['ports'];
                foreach ($socketList as $k => $value) {
                    $socket_data[$k]['name'] = $value['port'] + 1;
                    $socket_data[$k]['status'] = $value['portType'];

                }
            }
        }
        $last_names = array_column($socket_data, 'name');
        array_multisort($last_names, SORT_ASC, $socket_data);
        return $socket_data;
    }

    /**
     * 艾特充获取小区id和充电区id
     * @author:zhubaodi
     * @date_time: 2021/7/13 11:55
     */
    public function outGetArea($deviceId)
    {
        if (empty($deviceId)) {
            throw new \think\Exception("设备id不能为空");
        }
        $url = $this->outeruser_url . '/api/charge/outGetArea?deviceId=' . $deviceId;
        $socket_status = http_request($url, 'GET');
        if (!empty($socket_status) && $socket_status[0] == 200) {
            $socket_list = json_decode($socket_status[1], true);
            if ($socket_list['code'] == 200) {
                $socketList = $socket_list;
            }
        } else {
            $socketList = [];
        }

        return $socketList;
    }

    /**
     * 艾特充获取单个小区信息
     * @author:zhubaodi
     * @date_time: 2021/7/13 11:54
     */
    public function projectDetails($id)
    {
        if (empty($id)) {
            throw new \think\Exception("小区id不能为空");
        }
        $url = $this->outeruser_url . '/project/projectDetails?userId=' . $this->userId . '&id=' . $id;
        $village = http_request($url, 'GET');
        if (!empty($village) && $village[0] == 200) {
            $villageInfo = json_decode($village[1], true);
            if ($villageInfo['code'] == 200) {
                $village_info = $villageInfo['data'];
            }
        } else {
            $village_info = [];
        }

        return $village_info;
    }

    /**
     * 艾特充获取充电区费率
     * @author:zhubaodi
     * @date_time: 2021/7/13 11:55
     */
    public function point($pointId)
    {
        if (empty($pointId)) {
            throw new \think\Exception("充电区id不能为空");
        }
        $url = $this->outeruser_url . '/projectManager/point?pointId=' . $pointId;
        $point = http_request($url, 'GET');
        if (!empty($point) && $point[0] == 200) {
            $point = json_decode($point[1], true);
            if ($point['code'] == 200) {
                $point_nfo = $point['data'];
            }
        } else {
            $point_nfo = [];
        }

        return $point_nfo;
    }


    /**
     * 开始充电
     * @author:zhubaodi
     * @date_time: 2021/7/13 15:44
     */
    public function outCharge($order_id, $isCard = 2, $cardData = [])
    {
        $service_order = new HouseVillagePilePayOrder();
        $order_info = $service_order->get_one(['id' => $order_id]);
        fdump_api(['开始充电'.__LINE__,$order_info,$order_id],'outCharge',1);
        if ($order_info) {
            $service_equipment = new HouseVillagePileEquipment();
            $equipmentInfo = $service_equipment->getInfo(['id' => $order_info['equipment_id']]);
            //获取插座状态
            $socket_arr = $this->outGetPorts($equipmentInfo['equipment_num']);
            fdump_api(['开始充电'.__LINE__,$socket_arr,$equipmentInfo],'outCharge',1);
            if (!empty($socket_arr)) {
                foreach ($socket_arr as $value) {
                    if ($value['name'] == $order_info['socket_no']) {
                        $status = $value['status'];
                    }
                }
                //判断插座状态
                if (!isset($status) || $status != 0) {
                    throw new \think\Exception("当前插座不能充电，请更换插座");
                }
            }
            //查询消费标准
            $service_Equipment_Attribute = new HouseVillagePileEquipmentAttribute();
            $attributeId = $service_Equipment_Attribute->getOne(['equipment_id' => $equipmentInfo['id'], 'attribute_id' => 1, 'is_del' => 1]);
            if (!empty($attributeId)) {
                $service_Attribute_Set = new HouseVillagePileAttributeSet();
                $attributeInfo = $service_Attribute_Set->getOne(['id' => $attributeId['attribute_standard_id'], 'status' => 1]);
                if (!empty($attributeInfo)) {
                    $price_standard_list = unserialize($attributeInfo['standard_value']);
                }
            } else {
                $service_Attribute = new HouseVillagePileAttribute();
                $attributeInfo = $service_Attribute->getOne(['value' => 1]);
                $price_standard_list = unserialize($attributeInfo['default_value']);
            }
            $data = [];
            $data['deviceId'] = $equipmentInfo['equipment_num'];
            if (empty($data['deviceId'])) {
                throw new \think\Exception("设备号不能为空");
            }
            $data['port'] = $order_info['socket_no'];
            if (empty($data['port'])) {
                throw new \think\Exception("插座编号不能为空");
            } else {
                $data['port'] = $data['port'] - 1;
                if ($data['port'] <= 9) {
                    $data['port'] = '0' . $data['port'];
                }
            }
            $userService = new UserService();
            $buyer = $userService->getUser($order_info['uid'], 'uid');
            if (empty($buyer['now_money'])&&!empty($order_info['use_money'])){
                $buyer['now_money']=$order_info['use_money'];
            }elseif (empty($buyer['now_money'])&&empty($order_info['use_money'])){
                $buyer['now_money']='1';
            }
            if ($isCard == 1 && $cardData['mode'] == '01') {
                $data['chargeType'] = 1;
                $data['moneyOrTime'] = $cardData['moneyOrTime'];
                $data['time'] = '00';
            } elseif ($isCard == 1 && $cardData['mode'] != '01') {
                //获取购买用户信息
                $data['chargeType'] = $cardData['mode'];
                $data['moneyOrTime'] = $buyer['now_money'];
                $data['time'] = round(($order_info['use_money'] / ($price_standard_list[0][2] / $price_standard_list[1][2])) * 3600);
            } else {
                if ($equipmentInfo['work_type'] == 1) {
                    $data['chargeType'] = 0;
                } elseif ($equipmentInfo['work_type'] == 2) {
                    $data['chargeType'] = 2;
                    $data['degrees'] = round($order_info['use_money'] / ($price_standard_list[0][2] / $price_standard_list[2][2]));
                } else {
                    $data['chargeType'] = 4;
                }
                $data['moneyOrTime'] = $buyer['now_money'];
                if ($order_info['pay_type']==3){
                    $service_Card_User = new HouseVillagePileMonthCardUser();
                    $card_where=[
                       ['expire_time','>',time()],
                       ['uid','=',$order_info['uid']]
                    ];
                    $cardUserInfo = $service_Card_User->getOne($card_where);
                    if (empty($cardUserInfo)){
                        throw new \think\Exception("月卡信息不存在，无法开启充电");
                    }else{
                      $use_money=$cardUserInfo['per_price'];
                    }
                }else{
                    $use_money=$order_info['use_money'];
                }
                $data['time'] = round(($use_money / ($price_standard_list[0][2] / $price_standard_list[1][2])) * 3600);
            }
            if (empty($data['moneyOrTime'])) {
                throw new \think\Exception("设备播报余额或包月时长不能为空");
            }
            $data['orderNo'] = $order_info['order_no'];
            if (empty($data['orderNo'])) {
                throw new \think\Exception("订单号不能为空");
            }
            $data['isSpan'] = 0;
            $data['isCard'] = $isCard;
            if (empty($data['isCard'])) {
                throw new \think\Exception("请选择是否刷卡上电");
            }
            $url = $this->outeruser_url . '/api/charge/outCharge';
          //   print_r($data);exit;
            $outCharge = http_request($url . '?' . http_build_query($data));
            fdump_api(['开始充电'.__LINE__,$outCharge,$data],'outCharge',1);
            if (!empty($outCharge) && $outCharge[0] == 200) {
                $charge = json_decode($outCharge[1], true);
                fdump_api(['开始充电'.__LINE__,$charge],'outCharge',1);
                if ($charge['code'] == 200) {
                    $continued_time = 0;
                    $use_ele = 0;
                    if ($equipmentInfo['work_type'] == 1) {
                        $continued_time = $data['time'];
                    }
                    if ($equipmentInfo['work_type'] == 2) {
                        $use_ele = $data['degrees'];
                    }
                    if (!empty($continued_time)) {
                        $data11 = [
                            'continued_time' => $continued_time,
                            'buy_time' => $continued_time
                        ];
                    } elseif (!empty($use_ele)) {
                        $data11 = [
                            'use_ele' => $use_ele,
                            'buy_ele' => $use_ele
                        ];
                    }
                    fdump_api(['开始充电'.__LINE__,$data11,$order_id],'outCharge',1);
                    $service_order->saveOne(['id' => $order_id], $data11);
                }
            }

        }
        return false;
    }

    /**
     * 结束充电
     * @author:zhubaodi
     * @date_time: 2021/7/13 15:50
     */
/*    public function stopCharge($order_id)
    {
        $service_order = new HouseVillagePilePayOrder();
        $order_info = $service_order->get_one(['id' => $order_id]);
        if ($order_info) {
            $url = $this->outeruser_url . '/api/charge/stopCharge?orderNo=' . $order_info['order_no'];
            $stopCharge = http_request($url, 'GET');
            if (!empty($stopCharge) && $stopCharge[0] == 200) {
                $stop = json_decode($stopCharge[1], true);
                if ($stop['code'] == 200) {
                    return $stop['msg'];
                }
            }
        }
        return '操作失败';
    }*/


    /**
     * 查询设备信息
     * @author:zhubaodi
     * @date_time: 2021/7/19 14:55
     */
    public function queList($pointId, $deviceId)
    {
        $url = $this->outeruser_url . '/projectManager/queList?pointId=' . $pointId;
        $queList = http_request($url, 'GET');
        $state = '';
        if (!empty($queList)) {
            if ($queList[0] == 200) {
                $queList = json_decode($queList[1], true);
                if ($queList['code'] == 200) {
                    if (!empty($queList['rows'])) {
                        $device_list = $queList['rows'];
                        foreach ($device_list as $value) {
                            if ($value['eqDeviceId'] == $deviceId) {
                                $state = $value['state'];
                                break;
                            }
                        }
                    }
                }
            }

        }
        return $state;
    }

    /**
     * 接收上电结果
     * @author:zhubaodi
     * @date_time: 2021/7/26 10:55
     */
    public function chargeResult($code, $orderNo)
    {
        if ($code != 200) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 刷卡获取电卡信息
     * @author:zhubaodi
     * @date_time: 2021/7/26 10:55
     */
    public function getCard($cardNo)
    {
        $data = [
            'isExist' => 0,
            'isMonth' => 0,
            "amount" => "00",
            "endTime" => ""
        ];
        if (!empty($cardNo)) {
            $db_bind = new HouseVillageUserBind();
            $db_mouth_card_user = new HouseVillagePileMonthCardUser();
            $cardInfo = $db_bind->getOne(['ic_card' => $cardNo]);
            if (!empty($cardInfo)) {
                $where[] = ['uid', '=', $cardInfo['uid']];
                $where[] = ['status', '=', 1];
                $where[] = ['expire_time', '>', time()];
                $cardUserInfo = $db_mouth_card_user->getOne($where);
                if (!empty($cardUserInfo)) {
                    $data = [
                        'isExist' => 1,
                        'isMonth' => 1,
                        "amount" => "00",
                        "endTime" => $cardUserInfo['expire_time']
                    ];
                }
            }
        }

        return $data;
    }


    /**
     * 刷卡请求上电
     * @author:zhubaodi
     * @date_time: 2021/7/26 14:13
     */
    public function chargeRequest($cardNo, $deviceId, $port)
    {
        $data = [
            "accountState" => "01",
            "mode" => "01",
            "moneyOrTime" => "10",
            "errorMsg" => "请购买包月"
        ];
        if (!empty($cardNo)) {
            $db_bind = new HouseVillageUserBind();
            $db_mouth_card_user = new HouseVillagePileMonthCardUser();
            $userInfo = $db_bind->getOne(['ic_card' => $cardNo]);
            if (!empty($userInfo)) {
                $where[] = ['uid', '=', $userInfo['uid']];
                $where[] = ['status', '=', 1];
                $where[] = ['expire_time', '>', time()];
                $cardUserInfo = $db_mouth_card_user->getOne($where);
                if (!empty($cardUserInfo)) {
                    $service_order = new HouseVillagePilePayOrder();
                    $where_order = [
                        ['uid', '=', $userInfo['uid']],
                        ['pay_type', '=', 3],
                        ['pay_time', '>', strtotime(date('Y-m-d 00:00:00'))],
                    ];
                    $count = $service_order->get_count($where_order);
                    if ($count >= $cardUserInfo['max_num']) {
                        throw new \think\Exception("今日月卡使用次数已达到上限！");
                    }
                    $service_equipment = new HouseVillagePileEquipment();
                    $equipment_info = $service_equipment->getInfo(['equipment_num' => $deviceId]);
                    if (!$equipment_info) {
                        throw new \think\Exception("设备不存在！");
                    }
                    //获取插座状态
                    $socket_arr = $this->outGetPorts($equipment_info['equipment_num']);
                    if (!empty($socket_arr)) {
                        foreach ($socket_arr as $value) {
                            if ($value['name'] == ($port+1)) {
                                $status = $value['status'];
                            }
                        }
                        //判断插座状态
                        if (!isset($status) || $status != 0) {
                            throw new \think\Exception("当前插座不能充电，请更换插座");
                        }
                    }

 if (strlen($equipment_info['equipment_serial'])==8){
                $equipment_serial='00'.$equipment_info['equipment_serial'];
            }else{
                $equipment_serial=$equipment_info['equipment_serial'];
            }
                    $order_pile = [
                        'village_id' => $equipment_info['village_id'],
                        'order_type' => 1,
                        'order_no' => date('ymdhis') . mt_rand(10000000, 99999999) .  $equipment_serial . $port,
                        'socket_no' => $port+1,
                        'equipment_id' => $equipment_info['id'],
                        'use_money' => 0.00,
                        'uid' => $userInfo['uid'],
                        'pay_type' => 3,
                        'type' => 21,
                        'add_time' => time(),
                        'pay_time' => time(),
                    ];
                    $order_pile_id = $service_order->add_order($order_pile);
                    $surplus_num = $cardUserInfo['surplus_num'] - 1;
                    $db_mouth_card_user->saveOne(['id' => $cardUserInfo['card_id']], ['surplus_num' => $surplus_num]);
                    $res = $this->outCharge($order_pile_id, 2);
                    $data = [
                        "accountState" => "00",
                        "mode" => "01",
                        "moneyOrTime" => $cardUserInfo['expire_time'],
                        "errorMsg" => ""
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * 结束充电
     * @author:zhubaodi
     * @date_time: 2021/7/22 9:16
     */
    public function stopCharge($order_id, $uid)
    {
        fdump_api([$order_id, $uid],'pile/stopCharge',1);
        $service_order = new HouseVillagePilePayOrder();
        $service_equipment = new HouseVillagePileEquipment();
        $order_info = $service_order->get_one(['uid' => $uid, 'id' => $order_id, 'order_type' => 1]);
        if (empty($order_info)) {
            fdump_api([$order_id, $uid,$order_info],'pile/errTipStopCharge',1);
            throw new \think\Exception("订单不存在！");
        }
        if ($order_info['status']==1) {
            fdump_api([$order_id, $uid,$order_info],'pile/errTipStopCharge',1);
            throw new \think\Exception("订单状态不在充电中，无法结束充电！");
        }
        $equipment_info = $service_equipment->getInfo(['id' => $order_info['equipment_id']]);
        if (empty($equipment_info)) {
            fdump_api([$order_id, $uid,$order_info,$equipment_info],'pile/errTipStopCharge',1);
            throw new \think\Exception("设备信息不存在！");
        }
        if ($equipment_info['type'] == 1) {
            $url = $this->lbbtechCenterUrl . '/open/stopCharge';
            $data = [
                'token' => $this->lbbtechCenterToken,
                'deviceId' => $equipment_info['equipment_num'],
                'portNum' => $order_info['socket_no']
            ];
            $stopCharge = http_request($url, 'POST', http_build_query($data));
            fdump_api([__LINE__,'order_id'=>$order_id, $uid,'stopCharge'=>$stopCharge,'data'=>$data],'pile/stopCharge',1);
            if ($stopCharge[0] == 200) {
                $stopChargeInfo = json_decode($stopCharge[1], true);
                if (!empty($stopChargeInfo) && $stopChargeInfo['success'] == 'true') {
                    return '操作成功';
                }
            }

        } 
        elseif ($equipment_info['type'] == 2) {
            $whereOtherOrder = [];
            $whereOtherOrder[] = ['p.id','<>',$order_id];
            $whereOtherOrder[] = ['p.socket_no','=',$order_info['socket_no']];
            $whereOtherOrder[] = ['p.status','in',[0,2]];
            $whereOtherOrder[] = ['e.equipment_num','=',$equipment_info['equipment_num']];
            $whereOtherOrder[] = ['p.pay_time','>',$order_info['pay_time']];
            $otherOrderInfo = $this->getOrderInfo($whereOtherOrder, 'e.equipment_name,p.socket_no,p.uid,p.id');
            fdump_api([$whereOtherOrder, $otherOrderInfo],'pile/stopCharge',1);
            $socket_no = $order_info['socket_no'];
            $list = json_decode($equipment_info['port_status'],true);
            fdump_api([$socket_no, $list, $order_info],'pile/stopCharge',1);
            if (!empty($otherOrderInfo) || isset($otherOrderInfo['id'])) {
                // 由于插座已经停止或者存在其他人正在使用所以直接进行相关退款计算
                $db_pile_config = new HouseVillagePileConfig();
                $villageId = isset($order_info['village_id'])&&$order_info['village_id']?$order_info['village_id']:0;
                if (!$villageId) {
                    $villageId = isset($equipment_info['village_id'])&&$equipment_info['village_id']?$equipment_info['village_id']:0;
                }
                $config_info = $db_pile_config->get_one(['village_id' => $villageId]);

                $return_money = 0;
                $nowTime = time();
                fdump_api([$config_info, $nowTime],'pile/stopCharge',1);
                if($equipment_info['work_type'] == 1){         //计时版充电桩
                    if ($config_info['low_consume_time']>0) {
                        $judeTimes = intval($config_info['low_consume_time'])*60 + $order_info['pay_time'];
                        if ($judeTimes>=$nowTime) {
                            $return_money = $order_info['use_money'];//全额退款
                        }
                        fdump_api([$judeTimes,$return_money],'pile/stopCharge',1);
                    }
                    if(!$return_money && floatval($order_info['buy_time']) == floatval($order_info['continued_time'])){
                        $return_money = $order_info['use_money']; // 如果出现时间一致 标明没有实际充上电全额退款
                    }elseif(!$return_money && $config_info['des_time_level'] >= 0){//部分退款
                        $need_pay_time = $this->get_actual_use_time($config_info['des_time_level'],$order_info['continued_time']);
                        $return_money = sprintf("%.2f", $order_info['use_money'] - $order_info['use_money']/($order_info['buy_time']/$need_pay_time));
                    }
                    fdump_api([$return_money],'pile/stopCharge',1);
                }else{
                    //计量版充电桩
                    $judeTimes = intval($config_info['low_consume_time'])*60 + $order_info['pay_time'];
                    if ($order_info['buy_ele']==0 && $judeTimes>=$nowTime) {
                        $return_money = $order_info['use_money'];//全额退款
                    } elseif(floatval($order_info['buy_ele']) == floatval($order_info['use_ele'])){
                        $return_money = $order_info['use_money'];//全额退款
                    } elseif($config_info['low_consume_power'] >= $order_info['use_ele']){                     //全额退款
                        $return_money = $order_info['use_money'];
                    }elseif($config_info['des_power_level'] >= 0){                                  //部分退款
                        $need_pay_ele = get_actual_use_ele($config_info['des_power_level'],$order_info['use_ele']);
                        $return_money = sprintf("%.2f", $order_info['use_money'] - $order_info['use_money']/($order_info['use_ele']/$need_pay_ele));
                    }
                    fdump_api([$judeTimes,$return_money],'pile/stopCharge',1);
                }
                if($return_money > 0){                        //判断是否需要退款
                    $data = [
                        'cmd'=>'refundMoneyUserEnd',
                        'orderNo'=>$order_info['order_no'],
                        'refund_money' => $return_money,
                        'business_type'=>'house_village_pile_pay',
                        'type'=>'adopt',
                    ];
                    $data = json_encode($data,true);
                    $command_data = [
                        'command' => $data,
                        'type' => 2,
                        'status' => 1,
                        'addtime' => time(),
                    ];
                    $service_command = new HouseVillagePileCommand();
                    $id = $service_command->insertCommand($command_data);
                    fdump_api([$id,$command_data],'pile/stopCharge',1);
                    if ($id > 0) {
                        return '操作成功';
                    }
                }
            } else {
                $data = [
                    'cmd' => 'stopCharging',
                    'equipment_num' => $equipment_info['equipment_num'],
                    'port' => $order_info['socket_no']
                ];
                $command_data = [
                    'command' => json_encode($data),
                    'type' => 1,
                    'status' => 1,
                    'addtime' => time(),
                ];
                $service_command = new HouseVillagePileCommand();
                $id = $service_command->insertCommand($command_data);
                fdump_api([$id,$command_data],'pile/stopCharge',1);
                if ($id > 0) {
                    return '操作成功';
                }
            }
        } elseif ($equipment_info['type'] == 21) {
            $url = $this->outeruser_url . '/api/charge/stopCharge?orderNo=' . $order_info['order_no'];
            $stopCharge = http_request($url, 'GET');
            if ($stopCharge[0] == 200) {
                $stopChargeInfo = json_decode($stopCharge[1], true);
                if (!empty($stopChargeInfo) && $stopChargeInfo['code'] == 200) {
                    return '操作成功';
                }
            }
        }else if($equipment_info['type'] == 3){
                //该接口仅下发停止充电指令，返回成功不代表设备实际停止电成功
                $param = array('village_id' => $order_info['village_id'], 'order_pile_id' => $order_info['id'], 'order_no' => $order_info['order_no']);
                $res = invoke_cms_model('House_village_pile_equipment/stopType3Tocharge', $param);
                 fdump_api(['param'=>$param,'invokeres'=>$res],'haokuaichong/stopType3Tocharge',1);
                if ($res && isset($res['retval']) && !empty($res['retval']) && isset($res['retval']['err_code']) && ($res['retval']['err_code']==0)) {
                    return '操作成功';
                }else{
                    return '操作失败';
                }
            
        }
        return '操作失败';
    }


    /**
     * 添加退款申请纪录
     * @author:zhubaodi
     * @date_time: 2021/7/22 16:46
     */
    public function add_refund_order($data)
    {
        $db_order = new HouseVillagePilePayOrder();
        $order_info = $db_order->get_one(['id' => $data['order_id']]);
        if (empty($order_info)) {
            throw new \think\Exception("订单不存在！");
        }
        if ($order_info['is_refund'] != 0) {
            throw new \think\Exception("当前订单已申请过退款，无法重复申请！");
        }
        if (!empty($data['img']) && is_array($data['img'])) {
            $img = implode(',', $data['img']);
        } else {
            $img = '';
        }
        $refund_data = [
            'order_id' => $order_info['id'],
            'status' => 2,
            'img' => $img,
            'refund_money' => $order_info['use_money'],
            'refund_reason' => $data['refund_reason'],
            'add_time' => time()
        ];
        $db_refund = new HouseVillagePileRefundOrder();
        $id = $db_refund->add_order($refund_data);
        if ($id > 0) {
            $db_order->saveOne(['id' => $data['order_id']], ['is_refund' => 2]);
            return $id;
        }
        return 0;
    }

    public function getOrderInfo($where = [], $field = true)
    {
        $db_house_village_pile_pay_order = new HouseVillagePilePayOrder();
        $data = $db_house_village_pile_pay_order->getOne($where, $field);
        return $data;
    }

    /**
     * 计划任务处理指令
     * @return \json
     * @author lijie
     * @date_time 2021/08/03
     */
    public function PileCommand()
    {
        $userService = new UserPileService();
        $templateNewsService = new TemplateNewsService();
        $db_house_village_pile_command = new HouseVillagePileCommand();
        $db_pile_config = new HouseVillagePileConfig();
        try {
            $configCustomizationService=new ConfigCustomizationService();
            $pile_charge_consume_point=$configCustomizationService->getPileChargeConsumePoint(true);
            $command_list = $this->get_upload();
            if ($command_list) {
                fdump_api(['tip' => '充电桩命令','command_list' => $command_list],'pile/PileCommandLog',1);
                foreach ($command_list as $value) {
                    $command_info = json_decode($value['command'], true);
                    fdump_api(['tip' => '充电桩命令','command_info' => $command_info],'pile/PileCommandLog',1);
                    if($command_info['cmd'] != 'isPileSuccess'){
                        $db_house_village_pile_command->delOne(['id' => $value['id']]);
                    }
                    $first = '';
                    $userInfo = [];
                    if ($command_info['cmd'] == 'errorStopCharging' || $command_info['cmd'] == 'stopCharging') {                //充电异常导致停止充电消息提醒
                        $orderInfo = $this->getOrderInfo(['p.socket_no' => $command_info['socket_no'], 'p.status' => 1, 'e.equipment_num' => $command_info['equipment_num']], 'e.equipment_name,p.socket_no,p.uid,p.id');
                        if (!empty($orderInfo) && $orderInfo['uid']) {
                            $userInfo = $userService->getUserOne(['uid' => $orderInfo['uid']]);
                            if (!empty($userInfo['openid'])) {
                                if ($command_info['cmd'] == 'errorStopCharging') {
                                    $first = '您设备所在的' . $orderInfo['equipment_name'] . $orderInfo['socket_no'] . '号插座口由于设备异常情况，停止充电';
                                } else {
                                    $first = '您设备所在的' . $orderInfo['equipment_name'] . $orderInfo['socket_no'] . '号插座口充电完成，欢迎再次使用，祝您生活愉快';
                                }
                            }
                        }
                        $href = get_base_url('pages/village/smartCharge/chargeDetails?order_id=' . $orderInfo['id'] . '&status=1');
                        fdump_api(['tip' => '处理','orderInfo' => $orderInfo,'first' => $first,'href' => $href],'pile/PileCommandLog',1);
                    }
                    elseif ($command_info['cmd'] == 'refundMoneyCheck') {
                        // 需要审核的退款
                        $orderInfo = $this->getOrderInfo(['p.order_no' => $command_info['orderNo']], 'e.equipment_name,p.socket_no,p.uid,p.id,p.order_id,p.is_refund');
                        if($orderInfo['is_refund'] == 3){
                            fdump_api(['tip' => '已经退过款','orderInfo' => $orderInfo],'pile/PileCommandLog',1);
                            continue;
                        }
                        if (!empty($orderInfo) && $orderInfo['uid']) {
                            $db_house_village_pile_refund_order = new HouseVillagePileRefundOrder();
                            $refund_info = $db_house_village_pile_refund_order->get_one(['order_id' => $orderInfo['id']]);
                            if (empty($refund_info)){
                                $db_house_village_pile_refund_order->add_order([
                                    'order_id'=>$orderInfo['id'],
                                    'status'=>2,
                                    'refund_money'=>$command_info['refund_money'],
                                    'refund_reason'=>'结束未进行退款，通过审核退款',
                                    'add_time'=>time()
                                ]);
                                $db_house_village_pile_pay_order = new HouseVillagePilePayOrder();
                                $saveTmpData=['is_refund' => 2, 'status' => 1];
                                if($pile_charge_consume_point>0){
                                    $saveTmpData=['is_refund' => 2, 'status' => 1,'is_handle_point'=>1];
                                }
                                $db_house_village_pile_pay_order->saveOne(['id' => $orderInfo['id']], $saveTmpData);
                            } elseif ($refund_info['status']==2) {
                                $db_house_village_pile_refund_order->saveOne(['order_id' => $orderInfo['id']],['refund_money'=>$command_info['refund_money'],'refund_reason'=>'结束未进行退款，通过审核退款', 'add_time'=>time()]);
                            }
                        }
                    }
                    elseif ($command_info['cmd'] == 'refundMoney' || $command_info['cmd'] == 'refundMoneyUserEnd') {
                        $orderInfo = $this->getOrderInfo(['p.order_no' => $command_info['orderNo']], 'e.equipment_name,p.socket_no,p.uid,p.id,p.type,p.order_id,p.is_refund,p.extra_data,p.service_fee,p.use_money');
                        if($orderInfo['is_refund'] == 3){
                            fdump_api(['tip' => '已经退过款','orderInfo' => $orderInfo],'pile/PileCommandLog',1);
                            continue;
                        }
                        if (!empty($orderInfo) && $orderInfo['uid']) {
                            if ($command_info['type'] == 'adopt') {
                                $db_house_village_pile_refund_order = new HouseVillagePileRefundOrder();
                                $db_house_village_pile_pay_order = new HouseVillagePilePayOrder();
                                $refund_info = $db_house_village_pile_refund_order->get_one(['order_id' => $orderInfo['id']]);
                                if (empty($refund_info)||$refund_info['status']!=3){
                                    $param = [
                                        'param' => array('business_type' => 'pile', 'business_id' => $orderInfo['order_id'], 'pile_pay_order_id' => $orderInfo['id']),
                                        'operation_info' => '',
                                        'refund_money' => $command_info['refund_money'],
                                    ];
                                    $refund = invoke_cms_model('Plat_order/pile_order_refund', $param);
                                    fdump_api(['tip' => '执行退款','param' => $param,'refund' => $refund],'pile/PileCommandLog',1);
                                    if ((isset($refund['retval']['error']) && !$refund['retval']['error']) || !isset($refund['retval']['error'])) {
                                        $userInfo = $userService->getUserOne(['uid' => $orderInfo['uid']]);
                                        if (!empty($userInfo['openid'])) {
                                            $first = '您的充电桩账单退款￥' . $command_info['refund_money'] . '成功';
                                        }
                                        if ($refund_info) {
                                            $db_house_village_pile_refund_order->saveOne(['order_id' => $orderInfo['id']], ['refund_time' => time(), 'status' => 3]);
                                           if ($orderInfo['status']!=1){
                                               $db_house_village_pile_pay_order->saveOne(['id' => $orderInfo['id']], ['is_refund' => 3,'status'=>1,'refund_money'=>$command_info['refund_money'],'end_time'=>time()]);
                                           }else{
                                               $db_house_village_pile_pay_order->saveOne(['id' => $orderInfo['id']], ['is_refund' => 3,'refund_money'=>$command_info['refund_money']]);
                                           }

                                        }else{
                                            if ($command_info['cmd'] == 'refundMoneyUserEnd') {
                                                $refund_reason = '用户主动结束充电';
                                            } else {
                                                $refund_reason = '自动退款';
                                            }
                                            if ($orderInfo['status']!=1){
                                                $db_house_village_pile_pay_order->saveOne(['id' => $orderInfo['id']], ['is_refund' => 3,'status'=>1,'refund_money'=>$command_info['refund_money'],'end_time'=>time()]);
                                            }else{
                                                $db_house_village_pile_pay_order->saveOne(['id' => $orderInfo['id']], ['is_refund' => 3,'refund_money'=>$command_info['refund_money']]);
                                            }
                                            $db_house_village_pile_refund_order->add_order([
                                                'order_id'=>$orderInfo['id'],
                                                'status'=>3,
                                                'refund_money'=>$command_info['refund_money'],
                                                'refund_reason'=>$refund_reason,
                                                'refund_time'=>time(),
                                                'add_time'=>time()
                                            ]);
                                        }
                                        $saveArr=['refund_money' => $command_info['refund_money']];
                                        if(!empty($orderInfo['extra_data'])){
                                            $extra_data=json_decode($orderInfo['extra_data'],true);
                                            if($extra_data && isset($extra_data['standard_value']) && $extra_data['standard_value']){
                                                $use_money_tmp= $orderInfo['use_money']-$command_info['refund_money'];
                                                $service_fee=0;
                                                if($use_money_tmp>0){
                                                    $service_fee_rate=$extra_data['standard_value']['ffee'][2]/($extra_data['standard_value']['ffee'][2]+$extra_data['standard_value'][0][2]);
                                                    $service_fee=$service_fee_rate*$use_money_tmp;
                                                    $service_fee=round($service_fee,2);
                                                }
                                                $extra_data['original_service_fee']=$orderInfo['service_fee'];
                                                $saveArr['service_fee']=$service_fee;
                                                $saveArr['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
                                            }
                                        }
                                        if ($orderInfo['status']!=1){
                                            $saveArr['status']=1;
                                            $saveArr['end_time']=time();
                                            $db_house_village_pile_pay_order->saveOne(['id' => $orderInfo['id']], $saveArr);
                                        }else{
                                            $db_house_village_pile_pay_order->saveOne(['id' => $orderInfo['id']],$saveArr);
                                        }
                                        if($pile_charge_consume_point>0 && $orderInfo['type']<3){
                                            $tparam=array('energy_type'=>0,'comfrom'=>'tcp_pile');
                                            $this->handlePileChargeConsumePoint($orderInfo['id'],$tparam,'tcp_pile');
                                        }
                                   } elseif (isset($refund['retval']['msg']) && $refund['retval']['msg']) {
                                        // 自动退款失败 记录自动进行待审核退款
                                        $refund_info = $db_house_village_pile_refund_order->get_one(['order_id' => $orderInfo['id']]);
                                        if (empty($refund_info)){
                                            $db_house_village_pile_refund_order->add_order([
                                                'order_id'=>$orderInfo['id'],
                                                'status'=>2,
                                                'refund_money'=>$command_info['refund_money'],
                                                'refund_reason'=>$refund['retval']['msg'],
                                                'add_time'=>time()
                                            ]);
                                            $saveTmpData=['is_refund' => 2, 'status' => 1];
                                            if($pile_charge_consume_point>0){
                                                $saveTmpData=['is_refund' => 2, 'status' => 1,'is_handle_point'=>1];
                                            }
                                            $db_house_village_pile_pay_order->saveOne(['id' => $orderInfo['id']], $saveTmpData);
                                        } elseif ($refund_info['status']==2) {
                                            $db_house_village_pile_refund_order->saveOne(['order_id' => $orderInfo['id']],['refund_money'=>$command_info['refund_money'],'refund_reason'=>$refund['retval']['msg'], 'add_time'=>time()]);
                                        }
                                    }
                                    fdump_api(['line' => __LINE__,'funs' => __FUNCTION__,'value' => $value,'orderInfo' => $orderInfo,'param' => $param,'refund' => $refund],'pile/refundLog',1);
                                }
                            } else {
                                $userInfo = $userService->getUserOne(['uid' => $orderInfo['uid']]);
                                if (!empty($userInfo['openid'])) {
                                    $first = '您的充电桩账单退款￥' . $command_info['refund_money'] . '失败，原因：' . $command_info['reason'];
                                }
                            }
                        }
                        $href = get_base_url('pages/village/smartCharge/historyOrderDetails');
                        fdump_api(['tip' => '处理','orderInfo' => $orderInfo,'first' => $first,'href' => $href],'pile/PileCommandLog',1);
                    }
                    elseif ($command_info['cmd'] == 'isPileSuccess'){
                        $orderInfo = $this->getOrderInfo(['p.order_no' => $command_info['orderNo']], 'e.equipment_name,p.village_id,p.socket_no,p.uid,p.id,p.order_id,p.is_refund,p.pay_time,p.use_money,p.status');
                        fdump_api(['tip' => '充电桩开启成功且订单未退款','orderInfo' => $orderInfo,$orderInfo['is_refund']],'pile/PileCommandLog',1);
                        if($orderInfo['is_refund'] != 0){
                            $db_house_village_pile_command->delOne(['id' => $value['id']]);
                            fdump_api(['tip' => '充电桩开启成功且订单未退款','orderInfo' => $orderInfo],'pile/PileCommandLog',1);
                            continue;
                        }
                        fdump_api(['tip' => '充电桩开启成功且订单未退款',$orderInfo['is_refund']],'pile/PileCommandLog',1);
                        $judgeTimes = 70;
                        if (isset($orderInfo['village_id'])&&$orderInfo['village_id']) {
                            fdump_api(['tip' => '充电桩开启成功且订单未退款',$orderInfo['village_id']],'pile/PileCommandLog',1);
                            $config_info = $db_pile_config->get_one(['village_id' => $orderInfo['village_id']]);
                            fdump_api(['tip' => '充电桩开启成功且订单未退款',$config_info,$orderInfo['village_id']],'pile/PileCommandLog',1);
                            if (isset($config_info['low_consume_time'])&&$config_info['low_consume_time']>1) {
                                $judgeTimes = $config_info['low_consume_time'] * 60 - 20;
                            }
                        }
                        if ($judgeTimes<0) {
                            $judgeTimes = 70;
                        }
                        fdump_api(['tip' => '充电桩开启成功且订单未退款',time(),$orderInfo['pay_time'],(time() - $orderInfo['pay_time']) , $judgeTimes],'pile/PileCommandLog',1);

                        if(time() - $orderInfo['pay_time'] < $judgeTimes){
                            fdump_api(['tip' => '避免错误70秒开电未执行不进行退款','orderInfo' => time() - $orderInfo['pay_time']],'pile/PileCommandLog',1);
                            continue;
                        }else{
                            // 判断下命令是否已经执行了
                            $whereUnExecuted = [];
                            $whereUnExecuted[] = ['command','like','%\"cmd\":\"newPriceStandardCharge\"%'];
                            $whereUnExecuted[] = ['command','like','%\"orderNo\":\"'.$command_info['orderNo'].'\"%'];
                            $whereUnExecuted[] = ['command','like','%\"equipment_num\":\"'.$command_info['equipment_num'].'\"%'];
                            $whereUnExecuted[] = ['command','like','%\"port\":\"'.$orderInfo['socket_no'].'\"%'];
                            $unExecutedInfo = $db_house_village_pile_command->getOne($whereUnExecuted);
                            fdump_api(['tip' => '充电桩开启成功且订单未退款',$unExecutedInfo],'pile/PileCommandLog',1);

                            if ($unExecutedInfo&&isset($unExecutedInfo['id'])) {
                                // 命令还没有执行 继续
                                fdump_api(['line' => __LINE__,'funs' => __FUNCTION__,'value' => $value,'orderInfo' => $orderInfo,'msg' => '命令还没有执行','unExecutedInfo' => $unExecutedInfo],'pile/refundLog',1);
                                continue;
                            }
                            $res_pile=$db_house_village_pile_command->delOne(['id' => $value['id']]);
                            fdump_api(['tip' => '充电桩开启成功且订单未退款',$res_pile],'pile/PileCommandLog',1);

                            if($orderInfo['status'] == 0){
                                if ($command_info['type'] == 'adopt') {
                                    $param = [
                                        'param' => array('business_type' => 'pile', 'business_id' => $orderInfo['order_id'], 'pile_pay_order_id' => $orderInfo['id']),
                                        'operation_info' => '',
                                        'refund_money' => $orderInfo['use_money'],
                                    ];
                                    $refund = invoke_cms_model('Plat_order/pile_order_refund', $param);
                                    fdump_api(['tip' => '70秒后开电未执行进行退款','param' => $param,'refund' => $refund],'pile/PileCommandLog',1);
                                    if ((isset($refund['retval']['error']) && !$refund['retval']['error']) || !isset($refund['retval']['error'])) {
                                        $userInfo = $userService->getUserOne(['uid' => $orderInfo['uid']]);
                                        if (!empty($userInfo['openid'])) {
                                            $first = '您的充电桩开电异常，账单退款￥' . $orderInfo['use_money'] . '成功';
                                        }
                                        $db_house_village_pile_refund_order = new HouseVillagePileRefundOrder();
                                        $db_house_village_pile_pay_order = new HouseVillagePilePayOrder();
                                        $refund_info = $db_house_village_pile_refund_order->get_one(['order_id' => $orderInfo['id']]);
                                        if ($refund_info) {
                                            $db_house_village_pile_refund_order->saveOne(['order_id' => $orderInfo['id']], ['refund_time' => time(), 'status' => 3]);
                                            $db_house_village_pile_pay_order->saveOne(['id' => $orderInfo['id']], ['is_refund' => 3,'end_time'=>time(),'end_result'=>'下单异常','end_power'=>0,'continued_time'=>0,'use_ele'=>0,'status'=>1]);
                                        }else{
                                            $db_house_village_pile_pay_order->saveOne(['id' => $orderInfo['id']], ['is_refund' => 3,'end_time'=>time(),'end_result'=>'下单异常','end_power'=>0,'continued_time'=>0,'use_ele'=>0,'status'=>1]);
                                            $db_house_village_pile_refund_order->add_order([
                                                'order_id'=>$orderInfo['id'],
                                                'status'=>3,
                                                'refund_money'=>$orderInfo['use_money'],
                                                'refund_reason'=>'自动退款(开电异常)',
                                                'refund_time'=>time(),
                                                'add_time'=>time()
                                            ]);
                                        }
                                        $saveTmpData=['refund_money' => $orderInfo['use_money'],'service_fee'=>0];
                                        if($pile_charge_consume_point>0){
                                            //全额退了
                                            $saveTmpData['is_handle_point']=1;
                                        }
                                        $db_house_village_pile_pay_order->saveOne(['id' => $orderInfo['id']], $saveTmpData);
                                        // 设备开电异常退款 下发停止充电逻辑
                                        $data = [
                                            'cmd' => 'stopCharging',
                                            'equipment_num' => $command_info['equipment_num'],
                                            'port' => $orderInfo['socket_no']
                                        ];
                                        $command_data = [
                                            'command' => json_encode($data),
                                            'type' => 1,
                                            'status' => 1,
                                            'addtime' => time(),

                                        ];
                                        $service_command = new HouseVillagePileCommand();
                                        $id = $service_command->insertCommand($command_data);
                                        fdump_api(['tip' => '退款成功后 执行停止充电逻辑','command_data' => $command_data,'id' => $id],'pile/PileCommandLog',1);
                                    }
                                    fdump_api(['line' => __LINE__,'funs' => __FUNCTION__,'value' => $value,'orderInfo' => $orderInfo,'param' => $param,'refund' => $refund],'pile/refundLog',1);
                                }
                            }
                        }
                        $href = get_base_url('pages/village/smartCharge/historyOrderDetails');
                        fdump_api(['tip' => '处理','orderInfo' => $orderInfo,'first' => $first,'href' => $href],'pile/PileCommandLog',1);
                    }
                    if ($href&&isset($userInfo['openid']) && !empty($userInfo['openid'])) {
                        $datamsg = [
                            'tempKey' => 'OPENTM400166399',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $userInfo['openid'],
                                'first' => isset($first) ? $first : '',
                                'keyword1' => '充电桩充电提醒',
                                'keyword2' => '已发送',
                                'keyword3' => date('H:i'),
                                'remark' => '请点击查看详细信息！'
                            ]
                        ];
                        fdump_api(['tip' => '通知','tempKey' => $datamsg['tempKey'],'dataArr' => $datamsg['dataArr']],'pile/PileCommandLog',1);
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                    } else {
                        fdump_api(['tip' => '未通知','userInfo' => $userInfo],'pile/PileCommandLog',1);
                    }
                }
            }

            if($pile_charge_consume_point>0){
                $this->handleTaskPileChargeConsumePoint();
            }
        } catch (\Exception $e) {
            if($pile_charge_consume_point>0){
                $this->handleTaskPileChargeConsumePoint();
            }
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, [], '成功');
    }
    
    /**
     * 取消退款
     * @author:zhubaodi
     * @date_time: 2021/7/29 15:43
     */
    public function cancel_refund_order($data)
    {
        $db_house_village_pile_pay_order = new HouseVillagePilePayOrder();
        $db_house_village_pile_refund_order = new HouseVillagePileRefundOrder();
        $order_info = $db_house_village_pile_pay_order->get_one(['uid' => $data['uid'], 'id' => $data['order_id']]);
        if (empty($order_info)) {
            throw new \think\Exception("当前订单不存在！");
        }
        if ($order_info['is_refund'] != 2) {
            throw new \think\Exception("当前订单无法取消申请！");
        }
        $id = $db_house_village_pile_pay_order->saveOne(['id' => $data['order_id']], ['is_refund' => 1]);
        if ($id > 0) {
            $refund_info = $db_house_village_pile_refund_order->get_One(['order_id' => $data['order_id']]);
            if (!empty($refund_info)) {
                $db_house_village_pile_refund_order->saveOne(['order_id' => $data['order_id']], ['status' => 1]);
            }
        }
        return $id;

    }

    public function getChargeOrderInfo($device_id, $port, $uid)
    {
        $db_order = new HouseVillagePilePayOrder();
        $where = [
            'e.id' => $device_id,
            'p.socket_no' => $port,
            'p.status' => 0
        ];
        $url = '';
        $order_info = $db_order->getOne($where, 'p.*');
        if (!empty($order_info) && $order_info['uid'] == $uid) {
            $url = get_base_url('pages/village/smartCharge/chargeDetails?order_id=' . $order_info['id'] . '&status=0');
        }
        return $url;
    }


    /**
     * 查询充电桩列表
     * @author:zhubaodi
     * @date_time: 2021/8/26 13:40
     */
    public function getPileList($data,$village_id=0){
        //查询设备基本信息
        $service_equipment = new HouseVillagePileEquipment();
        $where = [];
        if (!empty($data['equipment_name'])) {
            $where[] = ['equipment_name', 'like','%'.$data['equipment_name'].'%'];
        }
        $where[]=['status','=',1];
        $where[]=['is_del','=',1];
        $limit=200;
        if($village_id>0){
            $where[]=['village_id','=',$village_id];
            if(isset($data['limit']) && ($data['limit']>0)){
                $limit=$data['limit'];
            }
        }
       //  $where[]=['type','<>',1];
        $tmptime=time()-30;
        $count=0;
        $pileList=$service_equipment->getList($where,'*',$data['page'],$limit);
        $equipment_list=[];
        if (!empty($pileList) && !$pileList->isEmpty()){
            $pileList=$pileList->toArray();
            if (!empty($pileList)){
                $count=$service_equipment->getCount($where);
                $count=$count>0 ? $count:0;
                $villageInfoArr=array();
                $service_village = new HouseVillage();
                foreach ($pileList as $v){
                    //查询小区名称
                    $villageInfo=array();
                    if(isset($villageInfoArr[$v['village_id']])){
                        $villageInfo=$villageInfoArr[$v['village_id']];
                    }else{
                        $villageInfoObj = $service_village->getInfo(['village_id' => $v['village_id']], 'village_name,village_id,village_address');
                        if($villageInfoObj && !$villageInfoObj->isEmpty()){
                            $villageInfo=$villageInfoObj->toArray();
                            $villageInfoArr[$v['village_id']]=$villageInfo;
                        }
                    }
                    $type_str='';
                    /*
                    $param = [
                        'lat' =>$v['lat'] ,
                        'lng' => $v['lng']
                    ];
                    $address_arr = invoke_cms_model('Area/cityMatching', $param);
                    */
                    if (empty($data['lat']) || empty($data['lng'])) {
                        $juli = 0;
                    } else {
                        $juli = getDistance($data['lat'], $data['lng'], $v['lat'], $v['lng']);
                    }
                    $juli1=$juli;
                    if ($juli >= 1000) {
                        $juli = round_number($juli / 1000, 2) . 'km';
                    } else {
                        $juli = $juli . 'm';
                    }
                    $work_socket_num=0;
                    $free_socket_num=0;
                    if ($v['type'] == 1) {
		       $free_socket_num=$v['socket_num'];
                        $type_str='lvv';
                        $qcodeNum=$v['equipment_num'];
                        $deviceId='';
                        //获取插座状态
                        /*
                        if(!empty($v['equipment_num'])){
                            $equipment_sn=$v['equipment_num'];
                        }elseif (!empty($v['equipment_sn'])){
                            $equipment_sn=$v['equipment_sn'];
                        }elseif (!empty($v['equipment_serial'])){
                            $equipment_sn=$v['equipment_serial'];
                        }else{
                            $equipment_sn='';
                        }
                        $socket_arr = $this->deviceStatus($equipment_sn,1);
                        unset($socket_arr[0]);
                        if (!empty($socket_arr)) {
                           foreach ($socket_arr as $val){
                               if ($val==0){
                                   $free_socket_num++;
                               }elseif($val==1){
                                   $work_socket_num++;
                               }
                           }
                        }else{
                            continue;
                        }
                        */
                    } elseif ($v['type'] == 21) {
                        $qcodeNum=$v['equipment_num'];
                        $deviceId='';
                        //获取插座状态
                        $socketList = $this->outGetPorts($v['equipment_num']);
                        if (!empty($socketList)) {
                            foreach ($socketList as $val){
                                if ($val['status']==0){
                                    $free_socket_num++;
                                }elseif($val['status']==1){
                                    $work_socket_num++;
                                }
                            }
                        }else{
                            continue;
                        }
                    }elseif($v['type'] == 3){
                        //定制的
                        $qcodeNum=$v['equipment_num'];
                        $deviceId='';
                        $socketList = $v['port_status'] ? json_decode($v['port_status'], true) : array();
                        if( isset($v['dev_update_time']) && $v['dev_update_time']<$tmptime) {
                            //不能太频繁查询，接口有次数限制
                            $param = array('village_id' => $v['village_id'], 'equipment_num' => $v['equipment_num'], 'equipment_id' => $v['id']);
                            $res = invoke_cms_model('House_village_pile_equipment/getType3EquipmentDetail', $param);
                            if($res && isset($res['retval']) && !empty($res['retval']) &&  isset($res['retval']['data']) && !empty($res['retval']['data'])){
                                $portState=$res['retval']['data']['portState'];
                                $port_status=$portState ? explode(',',$portState):'';
                                if($port_status){
                                    $socketList=$port_status;
                                }
                            }
                        }
                        if($socketList){
                            foreach ($socketList as $dks){
                                if ($dks==0 || $dks==2){
                                    //2 有充电器但未充电（用户未启动充电）
                                    $free_socket_num++;
                                }elseif(in_array($dks,[1,3,5,170])){
                                    $work_socket_num++;
                                }
                            }
                        }else{
                            continue;
                        }
                    } else {
                        $qcodeNum=$v['equipment_serial'];
                        $deviceId='';
                        $socket_arr = json_decode($v['port_status']);
                        if (!empty($socket_arr)) {
                            foreach ($socket_arr as $val){
                                if ($val==0){
                                    $free_socket_num++;
                                }elseif($val==1){
                                    $work_socket_num++;
                                }
                            }
                        }else{
                            continue;
                        }

                    }
                    
                    $equipment_list[] = [
                        'id' => $v['id'],
                        'type' => $v['type'],
                        'type_str'=>$type_str,
                        'qcodeNum' => $qcodeNum,
                        'deviceId' => $deviceId,
                        'village_id'=>$v['village_id'],
                        'port_status'=>$v['port_status'],
                        'equipment_name' => $v['equipment_name'],
                        //'address' =>  $address_arr['retval']['address_addr'].$villageInfo['village_name'],
                        'address' =>  $villageInfo['village_address'].' '.$villageInfo['village_name'],
                        'lat' => $v['lat'],
                        'lng' => $v['lng'],
                        'distance' => $juli,
                        'juli' => $juli1,
                        'socket_num'=>$v['socket_num'],
                        'work_socket_num'=>$work_socket_num,
                        'free_socket_num'=>$free_socket_num,
                    ];
                }
                $last_names = array_column($equipment_list, 'juli');
                array_multisort($last_names, SORT_ASC, $equipment_list);
            }
        }
        $returnArr=array('equipment_list'=>$equipment_list,'count'=>$count);
        return $returnArr;
    }
    public function refund($orderNo, $reason='', $is_stop = true){
        fdump_api(['tip' => '执行退款','REQUEST_URI' => $_SERVER['REQUEST_URI'],'orderNo' => $orderNo,'reason' => $reason],'pile/pileUserRefundLog',1);
        $userService = new UserPileService();
        $templateNewsService = new TemplateNewsService();
        $orderInfo = $this->getOrderInfo(['p.order_no' => $orderNo], 'e.equipment_name,e.equipment_num,p.*');
        if($orderInfo['is_refund'] != 3) {
            $first = '';
            if (!empty($orderInfo) && $orderInfo['uid']) {
                    //全退了
                    $param = [
                        'param' => array('business_type' => 'pile', 'business_id' => $orderInfo['order_id'], 'pile_pay_order_id' => $orderInfo['id']),
                        'operation_info' => '',
                        'refund_money' => $orderInfo['use_money'],
                    ];
                    $refund = invoke_cms_model('Plat_order/pile_order_refund', $param);
                    fdump_api(['tip' => '退款','param' => $param,'url' => 'Plat_order/pile_order_refund','refund' => $refund],'pile/pileUserRefundLog',1);
                    if ($refund && isset($refund['retval']) && isset($refund['retval']['error']) && !$refund['retval']['error']) {
                        $userInfo = $userService->getUserOne(['uid' => $orderInfo['uid']]);
                        if (!empty($userInfo['openid'])) {
                            $first = '您的充电桩账单退款￥' . $orderInfo['use_money'] . '成功';
                        }
                        $db_house_village_pile_refund_order = new HouseVillagePileRefundOrder();
                        $db_house_village_pile_pay_order = new HouseVillagePilePayOrder();
                        $refund_info = $db_house_village_pile_refund_order->get_one(['order_id' => $orderInfo['id']]);
                        $saveData = [];
                        $nowTime = time();
                        if ($refund_info) {
                            $db_house_village_pile_refund_order->saveOne(['order_id' => $orderInfo['id']], ['refund_time' => $nowTime, 'status' => 3]);
                            $saveData = ['status'=> 1, 'is_refund' => 3];
                        } else {
                            $saveData = ['status'=> 1, 'is_refund' => 3];
                            $db_house_village_pile_refund_order->add_order([
                                'order_id' => $orderInfo['id'],
                                'status' => 3,
                                'refund_money' => $orderInfo['use_money'],
                                'refund_reason' => '自动退款'.$reason,
                                'refund_time' => $nowTime,
                                'add_time' => $nowTime
                            ]);
                        }
                        if (!isset($orderInfo['end_time']) || !$orderInfo['end_time']) {
                            $saveData['end_time'] = $nowTime;
                        }
                        if ($reason && !isset($orderInfo['end_result']) || !$orderInfo['end_result']) {
                            $end_result = str_replace('(','',$reason);
                            $end_result = str_replace(')','',$end_result);
                            $saveData['end_result'] = $end_result;
                        }
                        $saveData['refund_money'] = $orderInfo['use_money'];
                        $db_house_village_pile_pay_order->saveOne(['id' => $orderInfo['id']], $saveData);
                        if ($is_stop) {
                            // 设备开电异常退款 下发停止充电逻辑
                            $data = [
                                'cmd' => 'stopCharging',
                                'equipment_num' => $orderInfo['equipment_num'],
                                'port' => $orderInfo['socket_no']
                            ];
                            $command_data = [
                                'command' => json_encode($data),
                                'type' => 1,
                                'status' => 1,
                                'addtime' => time(),

                            ];
                            $service_command = new HouseVillagePileCommand();
                            $id = $service_command->insertCommand($command_data);
                            fdump_api(['tip' => '退款成功后 执行停止充电逻辑','command_data' => $command_data,'id' => $id,'orderInfo'=>$orderInfo],'pile/pileUserRefundLog',1);
                        }
                    }
            }
            $href = get_base_url('pages/village/smartCharge/historyOrderDetails');
            fdump_api(['tip' => '处理','href' => $href,'first' => $first,'orderInfo' => $orderInfo],'pile/pileUserRefundLog',1);
            if (isset($userInfo['openid']) && !empty($userInfo['openid'])) {
                $datamsg = [
                    'tempKey' => 'OPENTM400166399',
                    'dataArr' => [
                        'href' => $href,
                        'wecha_id' => $userInfo['openid'],
                        'first' => isset($first) ? $first : '',
                        'keyword1' => '充电桩充电提醒',
                        'keyword2' => '已发送',
                        'keyword3' => date('H:i'),
                        'remark' => '请点击查看详细信息！'
                    ]
                ];
                fdump_api(['tip' => '通知','tempKey' => $datamsg['tempKey'],'dataArr' => $datamsg['dataArr']],'pile/pileUserRefundLog',1);
                $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
            }
        } else {
            fdump_api(['tip' => '已退款','orderInfo' => $orderInfo],'pile/pileUserRefundLog',1);
        }
        return true;
    }

    public function get_actual_use_time($des_time_level,$use_time)
    {
        $des_time_level = $des_time_level*60;
        return ceil($use_time/$des_time_level)*$des_time_level;
    }

    /**
     * 驴充充添加用户
     * @author:zhubaodi
     * @date_time: 2022/11/17 10:19
     */
    public function addLvccUser()
    {
        $data = [
            'token' => $this->lbbtechCenterToken,
            'phone' => '18856053203',
            'userName'=>'吴小珊'
        ];
        $url = $this->lbbtechCenterUrl . '/open/apiCreateUser';
        $status = http_request($url, 'POST', http_build_query($data));
        print_r($status);die;
        return true;
    }
    
    public function lvccQueryResult($data) {
        $standard = isset($data['standard']) ? $data['standard'] : [];
        $standard_data = isset($standard['data']) ? $standard['data'] : [];
        $execution_time = isset($data['execution_time']) ? intval($data['execution_time']) : $this->lvccQueryTimes;
        $nowTime = isset($data['nowTime']) ? intval($data['nowTime']) : time() - $execution_time;
        
        if (empty($standard_data)) {
            fdump_api(['line' => __LINE__, 'data' => $data, 'err' => '缺少开电成功后的参数'], 'lvcc/lvccQueryResult', 1);
            return false;
        }
        if( isset($data['nowTime']) && $data['nowTime']>1000){
            $nowQueryTime=$data['nowTime']+41;
            $current_time=time();
            if($nowQueryTime<$current_time){
                //开电后查询接口只支持查45秒内的，45后这个接口将返回错误数据
                return false;
            }
        }
        $portNum = isset($standard_data['portNum']) ? $standard_data['portNum'] : '';
        $simId = isset($standard_data['simId']) ? $standard_data['simId'] : '';
        $resultKey = isset($standard_data['resultKey']) ? $standard_data['resultKey'] : '';
        $orderNum = isset($standard_data['orderNum']) ? $standard_data['orderNum'] : '';
        if (!$resultKey) {
            fdump_api(['line' => __LINE__, 'data' => $data, 'err' => '缺少查询充电结果Key Query the charging result Key'], 'lvcc/lvccQueryResult', 1);
            return false;
        }
        if (!$orderNum) {
            fdump_api(['line' => __LINE__, 'data' => $data, 'err' => '缺少订单'], 'lvcc/lvccQueryResult', 1);
            return false;
        }
        $url = $this->lbbtechCenterUrl . '/open/query/result';
        $paramData = [
            'token' => $this->lbbtechCenterToken,
            'resultKey' => $resultKey,
        ];
        $result = http_request($url, 'POST', http_build_query($paramData));
        fdump_api(['line' => __LINE__, 'data' => $data,  'paramData' => $paramData, 'result' => $result], 'lvcc/lvccQueryResult', 1);
        $endTime = time() * 1000;
        if ($result[0] == 200) {
            $resultData = json_decode($result[1], true);
            fdump_api(['line' => __LINE__, 'resultData' => $resultData], 'lvcc/lvccQueryResult', 1);
            $success = isset($resultData['success']) ? $resultData['success'] : false;
            $code = isset($resultData['code']) ? $resultData['code'] : '';
            $successData = isset($resultData['data']) ? $resultData['data'] : [];
            $successDataResult = isset($successData['result']) ? $successData['result'] : 0;
            if ($success && $successDataResult == 1) {
                // 查询开电结果成功
                fdump_api(['line' => __LINE__, 'success' => $success, 'successDataResult' => $successDataResult, 'successData' => $successData, 'msg' => '查询开电结果成功'], 'lvcc/lvccQueryResult', 1);
                return true;
            }
            if ($success && $successDataResult > 1) {
                // 查询开电结果失败
                fdump_api(['line' => __LINE__, 'success' => $success, 'successDataResult' => $successDataResult, 'successData' => $successData, 'msg' => '查询开电结果失败'], 'lvcc/lvccQueryResult', 1);
                $deviceId = isset($successData['deviceId']) ? $successData['deviceId'] : '';
                if (!$deviceId && $simId) {
                    $deviceId = $simId;
                }
                $successPortNum = isset($successData['portNum']) ? $successData['portNum'] : '';
                if (!$successPortNum && $portNum) {
                    $successPortNum = $portNum;
                }

                $service_order_pile = new HouseVillagePilePayOrder();
                $order_info = $service_order_pile->get_one(['order_serial' => $orderNum]);
                if (!$successPortNum && isset($order_info['socket_no']) && $order_info['socket_no']) {
                    $successPortNum = $order_info['socket_no'];
                }
                if (!$deviceId && isset($order_info['equipment_id']) && $order_info['equipment_id']) {
                    $service_equipment = new HouseVillagePileEquipment();
                    $equipment_info = $service_equipment->getInfo(['id' => $order_info['equipment_id']]);
                    if (isset($equipment_info['equipment_num']) && $equipment_info['equipment_num']) {
                        $deviceId = $equipment_info['equipment_num'];
                    }
                }
                $end_result = '开电失败';
                if (isset($resultData['error']) && $resultData['error']) {
                    $end_result = '查询充电结果：' . $resultData['error'];
                }
                $end_result = $this->getLvccQueryResultReason($code, $end_result);
                $notificationData = [
                    'orderNum' => $orderNum,
                    'endType' => 3,
                    'powerCount' => '0.0',
                    'startTime' => $nowTime * 1000,
                    'endTime' => $endTime,
                    'totalElectricMoney' => 0,
                    'totalServiceFee' => 0,
                    'deviceId' => $deviceId,
                    'portNum' => $successPortNum,
                    'payPrice' => '0.0',
                    'cardNum' => '',
                    'end_result' => $end_result,
                ];
                try {
                    $this->notification($notificationData);
                } catch (\Exception $e) {
                    fdump_api(['line' => $e->getLine(), 'code' => $e->getCode(), 'err' => $e->getMessage()], 'lvcc/lvccQueryResult', 1);
                }
                return true;
            }
            if ($execution_time < 45) {
                $data['execution_time'] = $execution_time + $this->lvccQuerySpaceTimes;
                $job_id = $this->traitCommonLvCC($data, $this->lvccQuerySpaceTimes);
                fdump_api(['line' => __LINE__, 'job_id' => $job_id, 'queueData' => $data, 'lvccQuerySpaceTimes' => $this->lvccQuerySpaceTimes], 'lvcc/lvccQueryResult', 1);
            } else {
                // 超过了45秒未查询出结果 当失败处理
                fdump_api(['line' => __LINE__, 'success' => $success, 'successDataResult' => $successDataResult, 'successData' => $successData, 'msg' => '超过了45秒未查询出结果 当失败处理'], 'lvcc/lvccQueryResult', 1);
                $deviceId = isset($successData['deviceId']) ? $successData['deviceId'] : '';
                if (!$deviceId && $simId) {
                    $deviceId = $simId;
                }
                $successPortNum = isset($successData['portNum']) ? $successData['portNum'] : '';
                if (!$successPortNum && $portNum) {
                    $successPortNum = $portNum;
                }

                $service_order_pile = new HouseVillagePilePayOrder();
                $order_info = $service_order_pile->get_one(['order_serial' => $orderNum]);
                if (!$successPortNum && isset($order_info['socket_no']) && $order_info['socket_no']) {
                    $successPortNum = $order_info['socket_no'];
                }
                if (!$deviceId && isset($order_info['equipment_id']) && $order_info['equipment_id']) {
                    $service_equipment = new HouseVillagePileEquipment();
                    $equipment_info = $service_equipment->getInfo(['id' => $order_info['equipment_id']]);
                    if (isset($equipment_info['equipment_num']) && $equipment_info['equipment_num']) {
                        $deviceId = $equipment_info['equipment_num'];
                    }
                }
                $end_result = '开电失败';
                if (isset($resultData['error']) && $resultData['error']) {
                    $end_result = '查询充电结果：' . $resultData['error'];
                }
                $end_result = $this->getLvccQueryResultReason($code, $end_result);
                $notificationData = [
                    'orderNum' => $orderNum,
                    'endType' => 3,
                    'powerCount' => '0.0',
                    'startTime' => $nowTime * 1000,
                    'endTime' => $endTime,
                    'totalElectricMoney' => 0,
                    'totalServiceFee' => 0,
                    'deviceId' => $deviceId,
                    'portNum' => $successPortNum,
                    'payPrice' => '0.0',
                    'cardNum' => '',
                    'end_result' => $end_result,
                ];
                try {
                    $this->notification($notificationData);
                } catch (\Exception $e) {
                    fdump_api(['line' => $e->getLine(), 'code' => $e->getCode(), 'err' => $e->getMessage()], 'lvcc/lvccQueryResult', 1);
                }
            }
        }
        return true;
    }

    public function getLvccQueryResultReason($code, $errText = '') {
        $codeMessageArr = [
            1 => '成功', 2 => '未知失败', 3 => '未插插座', 4 => '保险丝熔断',
            51 => 'CC1 未连接（未插枪）', 52 => '绝缘检测超时', 53 => '绝缘检测异常',
            54 => '充电机暂停服务', 55 => '充电机系统故障,不能充电', 56 => '辅电不匹配',
            57 => '辅电开启失败', 58 => '充电启动超时', 59 => 'BMS 通信握手失败',
            60 => 'BMS 通信配置失败', 61 => 'BMS 参数异常', 62 => '桩正在充电中，不能再启动',
            63 => '启动未知错误', 64 => '电池反接', 65 => '重新插拔枪',
        ];
        $code = intval($code);
        if (isset($codeMessageArr[$code]) && $codeMessageArr[$code]) {
            $errText = $codeMessageArr[$code];
        }
        return $errText;
    }
    public function getPropertyBindWxMiniInfo($village_id=0){
        $service_village = new HouseVillage();
        $villageInfo = $service_village->getInfo(['village_id' => $village_id], 'village_name,village_id,property_id,property_phone');
        $property_wxapp_info=array();
        if($villageInfo && isset($villageInfo['property_id']) && $villageInfo['property_id']){
            $db_weixin_app_bind = new WeixinAppBind();
            $whereOwnFrom = [];
            $whereOwnFrom[] = ['bind_type', '=', 1];
            $whereOwnFrom[] = ['other_id', '=', $villageInfo['property_id']];
            $whereOwnFrom[] = ['status', '=', 1];
            $property_wxapp_info_obj = $db_weixin_app_bind->getFind($whereOwnFrom, '*');
            if($property_wxapp_info_obj && !$property_wxapp_info_obj->isEmpty()){
                $property_wxapp_info=$property_wxapp_info_obj->toArray();
            }
        }
        return  $property_wxapp_info;
    }
}


