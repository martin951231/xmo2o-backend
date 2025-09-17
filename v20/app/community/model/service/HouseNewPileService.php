<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/9/9 13:34
 */
namespace app\community\model\service;

use app\common\model\service\send_message\SmsService;
use app\common\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseMaintenanLog;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderSummary;
use app\community\model\db\HouseNewPileCharge;
use app\community\model\db\HouseNewPileCommand;
use app\community\model\db\HouseNewPileConfig;
use app\community\model\db\HouseNewPileEquipment;
use app\community\model\db\HouseNewPileEquipmentLog;
use app\community\model\db\HouseNewPileNews;
use app\community\model\db\HouseNewPilePayOrder;
use app\community\model\db\HouseNewPilePayOrderLog;
use app\community\model\db\HouseNewPileRefundOrder;
use app\community\model\db\HouseNewPileUserMoney;
use app\community\model\db\HouseNewPileUserMoneyLog;
use app\community\model\db\HouseNewPileWithdraw;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillagePileEquipment;
use app\community\model\db\PlatOrder;
use app\community\model\db\User;
use app\community\model\service\HousePaidOrderRecordService;
use function GuzzleHttp\Psr7\str;
use function think\crc166;
use function think\publish;
use tools;

require_once '../extend/phpqrcode/phpqrcode.php';
class HouseNewPileService
{
    public $result_code=['1'=>'电量用完', '2'=>'枪没插好', '3'=> '过电压', '4'=>'设备已经关闭', '5'=> '设备故障', '6'=>'平台下发结束充电','7'=>'电量充满停电', '8'=>'充电枪拔出停止充电', '9'=>'欠电压', '10'=> '电流过大', '11'=>'漏电', '12'=>'过温', '13'=>'急停', '14'=>'未接地'];
    public $end_result=['00'=>'无', '01'=>'设备编号不匹配', '02'=> '枪已在充电', '03'=>'设备故障', '04'=> '设备离线', '05'=>'未插枪'];
    public $mqtt_end=['01'=>'电量用完', '02'=> '枪没插好', '03'=> '过电压', '04'=> '设备已经关闭','05'=> '设备故障','06'=> '平台下发结束充电','07'=> '电量充满停电','08'=> '充电枪拔出停止充电','09'=> '欠电压','10'=>'电流过大', '11'=> '漏电','12'=> '过温','13'=> '急停','14'=> '未接地','20'=> '参数错误','30'=> '未知原因'];
    
    public     $pile_time=[
        0=>'00:00',
        1=>'00:30',
        2=>'01:00',
        3=>'01:30',
        4=>'02:00',
        5=>'02:30',
        6=>'03:00',
        7=>'03:30',
        8=>'04:00',
        9=>'04:30',
        10=>'05:00',
        11=>'05:30',
        12=>'06:00',
        13=>'06:30',
        14=>'07:00',
        15=>'07:30',
        16=>'08:00',
        17=>'08:30',
        18=>'09:00',
        19=>'09:30',
        20=>'10:00',
        21=>'10:30',
        22=>'11:00',
        23=>'11:30',
        24=>'12:00',
        25=>'12:30',
        26=>'13:00',
        27=>'13:30',
        28=>'14:00',
        29=>'14:30',
        30=>'15:00',
        31=>'15:30',
        32=>'16:00',
        33=>'16:30',
        34=>'17:00',
        35=>'17:30',
        36=>'18:00',
        37=>'18:30',
        38=>'19:00',
        39=>'19:30',
        40=>'20:00',
        41=>'20:30',
        42=>'21:00',
        43=>'21:30',
        44=>'22:00',
        45=>'22:30',
        46=>'23:00',
        47=>'23:30',
        48=>'24:00',
    ];
    /**
     * 编辑站点信息
     * @author:zhubaodi
     * @date_time: 2022/9/9 14:57
     */
    public function editPileConfig($data)
    {
        $db_house_new_pile_config = new HouseNewPileConfig();
        $config_info = $db_house_new_pile_config->getFind(['village_id' => $data['village_id']]);
        $arr = [];
        $arr['village_id'] = $data['village_id'];
        $arr['pile_name'] = $data['pile_name'];
        $arr['long'] = $data['lng'];
        $arr['lat'] = $data['lat'];
        $arr['park_type'] = $data['park_type'];
        $arr['pile_phone'] = $data['pile_phone'];
        $arr['park_desc'] = $data['park_desc'];
        $arr['open_time_desc'] = $data['open_time_desc'];
        $arr['work_time'] = $data['work_time_start'] . '-' . $data['work_time_end'];
        $arr['remark'] = $data['remark'];
        $arr['img'] = $data['img'];
        $arr['min_money'] = $data['min_money'];
        $arr['capacitance'] = $data['capacitance'];
        if (empty($config_info) || empty($config_info['id'])) {
            $res = $db_house_new_pile_config->addOne($arr);
        } else {
            $res = $db_house_new_pile_config->save_one(['id' => $config_info['id'], 'village_id' => $data['village_id']], $arr);
        }
        return $res;
    }

    /**
     * 查询站点信息
     * @author:zhubaodi
     * @date_time: 2022/9/9 14:57
     */
    public function getPileConfig($data)
    {
        $db_house_new_pile_config = new HouseNewPileConfig();
        $config_info = $db_house_new_pile_config->getFind(['village_id' => $data['village_id']]);
        $config_info['work_time_start'] = '';
        $config_info['work_time_end'] = '';
        if (!empty($config_info) && !empty($config_info['work_time'])) {
            $work_time = explode('-', $config_info['work_time']);
            if (is_array($work_time)) {
                $config_info['work_time_start'] = $work_time[0];
                $config_info['work_time_end'] = $work_time[1];
            }
            if (!empty($config_info['img'])) {
                $config_info['img'] = replace_file_domain($config_info['img']);
            }
        }
        return $config_info;
    }


    /**
     * 查询收费标准列表
     * @author:zhubaodi
     * @date_time: 2022/9/29 20:53
     */
    public function getRuleChargeList($data){
        $db_house_new_pile_charge = new HouseNewPileCharge();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $where1[]=[
            ['village_id','=',$data['village_id']],
            ['charge_valid_time','<',time()],
            ['status','=',1],
        ];
        $rule_ids=$db_house_new_pile_charge->getColumn($where1,'rule_id');
        if (!empty($rule_ids)){
            $where[]=[
                ['r.id','in',$rule_ids],
                ['r.village_id','=',$data['village_id']],
                ['r.charge_valid_time','<',time()],
                ['r.status','=',1],
            ];
            $rule_list=$db_house_new_charge_rule->getLists($where,'r.id,r.charge_name,r.charge_valid_time,r.add_time,p.name',$data['page'],$data['limit']);
            $count=$db_house_new_charge_rule->getCounts($where);
            if (!empty($rule_list)){
                $rule_list=$rule_list->toArray();
            }
            if (!empty($rule_list)){
                foreach ($rule_list as &$v){
                    if (!empty($v['add_time'])){
                        $v['add_time_txt']=date('Y-m-d H:i:s',$v['add_time']);
                        $v['charge_valid_time_txt']=date('Y-m-d H:i:s',$v['charge_valid_time']);
                    }
                }
            }
        }
        
        $data1=[];
        $data1['list']=$rule_list;
        $data1['count']=$count;
        return $data1;
        
    }

    /**
     * 查询设备绑定的收费标准
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:35
     */
    public function getRuleEquipmentList($data)
    {
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $where = [
            ['village_id', '=', $data['village_id']],
            ['is_del', '=', 1]
        ];
        $list = $db_house_new_pile_equipment->getList($where, 'charge_id,id,village_id,device_brand,brand_type,equipment_name,equipment_num,status,sim_num,socket_num,type,add_time,remark', $data['page'], $data['limit']);
        $count = $db_house_new_pile_equipment->getCount($where);
        if (!empty($list)) {
            $list = $list->toArray();
        }
        if (!empty($list)) {
            foreach ($list as &$v) {
                $v['bind']=0;
                if (empty($v['charge_id'])){
                    $v['bind_status']='未绑定';
                }else{
                    if ($v['charge_id']==$data['rule_id']){
                        $v['bind_status']='已绑定当前';
                        $v['bind']=1;
                    }else{
                        $v['bind_status']='已绑定其他';  
                    } 
                }
                
                $v['type_txt'] = $v['type'] == 1 ? '直流' : '交流';
                if ($v['status'] == 1) {
                    $v['status_txt'] = '在线';
                } elseif ($v['status'] == 3) {
                    $v['status_txt'] = '离线';
                } elseif ($v['status'] == 4) {
                    $v['status_txt'] = '故障';
                }

            }
        }

        $arr = [];
        $arr['list'] = $list;
        $arr['count'] = $count;
        $arr['limit'] = $data['limit'];
        return $arr;
    }

    /**
     * 设备绑定收费标准
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:40
     */
    public function bind($data){
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $db_house_new_pile_charge = new HouseNewPileCharge();
        $service_house_new_energy_pile = new HouseNewEnerpyPileService();
        $equipment_info=$db_house_new_pile_equipment->getInfo(['village_id'=>$data['village_id'],'id'=>$data['id']]);
        if (empty($equipment_info)){
            throw new \think\Exception("设备不存在！");
        }
        $rule_info=$db_house_new_pile_charge->getFind(['rule_id'=>$data['rule_id'],'village_id'=>$data['village_id']]);
        if (empty($rule_info)){
            throw new \think\Exception("收费标准不存在！");
        }
        $res=$db_house_new_pile_equipment->save_one(['village_id'=>$data['village_id'],'id'=>$data['id']],['charge_id'=>$data['rule_id']]);
        $cmd_data=[];
        $cmd_data['id']=$data['id'];
        $service_house_new_energy_pile->setPileChargeInfo($cmd_data);
        return $res;
    }
    
    
    /**
     * 充电桩设备统计
     * @author:zhubaodi
     * @date_time: 2022/9/9 16:35
     */
    public function countPileEquipment($data)
    {
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        //统计充电桩总数
        $sum_count = $db_house_new_pile_equipment->getCount(['village_id' => $data['village_id'], 'is_del' => 1]);
        //统计充电桩使用中总数
        $use_count = $db_house_new_pile_equipment->getCount(['village_id' => $data['village_id'], 'is_del' => 1, 'status' => 1]);
        //统计充电桩空闲总数
        $free_count = $db_house_new_pile_equipment->getCount(['village_id' => $data['village_id'], 'is_del' => 1, 'status' => 2]);
        //统计充电桩离线总数
        $offline_count = $db_house_new_pile_equipment->getCount(['village_id' => $data['village_id'], 'is_del' => 1, 'status' => 3]);
        //统计充电桩故障总数
        $fault_count = $db_house_new_pile_equipment->getCount(['village_id' => $data['village_id'], 'is_del' => 1, 'status' => 4]);

        $arr = [];
        $arr['sum_count'] = $sum_count;
        $arr['use_count'] = $use_count;
        $arr['free_count'] = $free_count;
        $arr['offline_count'] = $offline_count;
        $arr['fault_count'] = $fault_count;
        return $arr;
    }


    public function getEquipmentList($data)
    {
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $where = [
            ['village_id', '=', $data['village_id']],
            ['is_del', '=', 1]
        ];
        if (!empty($data['equipment_name'])) {
            $where[] = ['equipment_name', 'like', '%' . $data['equipment_name'] . '%'];
        }
        if (!empty($data['equipment_num'])) {
            $where[] = ['equipment_num', '=', $data['equipment_num']];
        }
        if (!empty($data['type'])) {
            $where[] = ['type', '=', $data['type']];
        }
        if (!empty($data['status'])) {
            $where[] = ['status', '=', $data['status']];
        }
        $list = $db_house_new_pile_equipment->getList($where, 'id,village_id,device_brand,brand_type,equipment_name,equipment_num,status,sim_num,socket_num,type,add_time,remark', $data['page'], $data['limit']);
        $count = $db_house_new_pile_equipment->getCount($where);
        if (!empty($list)) {
            $list = $list->toArray();
        }
        if (!empty($list)) {
            foreach ($list as &$v) {
                $v['brand_type_txt']='GTDC120KW';
                $v['device_brand_txt']='工泰';
                $v['type_txt'] = $v['type'] == 2 ? '交流' : '直流';
                if ($v['status'] == 1) {
                    $v['status_txt'] = '在线';
                } elseif ($v['status'] == 3) {
                    $v['status_txt'] = '离线';
                } elseif ($v['status'] == 4) {
                    $v['status_txt'] = '故障';
                }

            }
        }

        $arr = [];
        $arr['list'] = $list;
        $arr['count'] = $count;
        $arr['limit'] = $data['limit'];
        return $arr;
    }

    /**
     * 查询设备详情
     * @author:zhubaodi
     * @date_time: 2022/9/13 10:33
     */
    public function getEquipmentDetail($data)
    {
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $where = [
            ['village_id', '=', $data['village_id']],
            ['is_del', '=', 1],
            ['id', '=', $data['id']]
        ];
        $info = $db_house_new_pile_equipment->getInfo($where);
        $info['brand_type_txt']='GTDC120KW';
        $info['device_brand_txt']='工泰';

        $socket_arr = [];
        if (!empty($info) && !empty($info['socket_status'])) {
            $socket_status = \json_decode($info['socket_status']);
            if (!empty($socket_status)) {
                foreach ($socket_status as $k => $v) {
                    $arr = [];
                    $arr['number'] = $info['equipment_num'] . '0' . ($k + 1);
                    $arr['socket'] = $k + 1;
                    if ($v == 0) {
                        $arr['status'] = '空闲';
                    } else {
                        $arr['status'] = '充电中';
                    }
                    $socket_arr[] = $arr;
                }
            }
        }
        $data1 = [];
        $data1['info'] = $info;
        $data1['socket_arr'] = $socket_arr;
        return $data1;
    }


    /**
     * 添加设备
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2022/9/13 10:33
     */
    public function addEquipment($data)
    {
        $db_house_new_pile_config = new HouseNewPileConfig();
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $config_info = $db_house_new_pile_config->getFind(['village_id' => $data['village_id']]);
        if (empty($config_info)) {
            throw new \think\Exception("站点信息不存在");
        }
        $equipment_info = $db_house_new_pile_equipment->getInfo(['equipment_num' => $data['equipment_num'], 'is_del' => 1]);
        if (!empty($equipment_info)) {
            throw new \think\Exception("设备唯一编号不能重复");
        }
        $equipment_info1 = $db_house_new_pile_equipment->getInfo(['equipment_name' => $data['equipment_name'], 'is_del' => 1]);
        if (!empty($equipment_info1)) {
            throw new \think\Exception("设备名称不能重复");
        }
        $arr = [];
        $arr['equipment_name'] = $data['equipment_name'];
        $arr['village_id'] = $data['village_id'];
        $arr['device_brand'] = $data['brand'];
        $arr['brand_type'] = $data['brandType'];
        $arr['equipment_num'] = $data['equipment_num'];
        $arr['socket_num'] = $data['socket_num'];
        $arr['power'] = $data['power'];
        $arr['min_voltage'] = $data['min_voltage'];
        $arr['max_voltage'] = $data['max_voltage'];
        $arr['min_electric_current'] = $data['min_electric_current'];
        $arr['max_electric_current'] = $data['max_electric_current'];
        $arr['min_temperature'] = $data['min_temperature'];
        $arr['max_temperature'] = $data['max_temperature'];
        $arr['status'] = 3;
        $arr['type'] = $data['type'];
        if ($data['socket_num']>0){
            for ($i=0;$i<$data['socket_num'];$i++){
                $num[]=0;
            }
            $arr['socket_status'] = \json_encode($num);
        }
       
        $arr['remark'] = $data['remark'];
        $arr['add_time'] = time();
        $res = $db_house_new_pile_equipment->addOne($arr);
        if ($res>0&&$arr['type']==2){
            $db_house_new_pile_command = new HouseNewPileCommand();
            $key='qccdz:'.$data['equipment_num'].'5bccc1';
            $cmd='0001000101010000';
            $crc=$this->wordStr($cmd);
            $content=$this->opensslEncrypt($cmd.$crc,$key);
            $command = [
                'sn' => $data['equipment_num'],
                'cmd' => $content,
                'desc'=>'add'
            ];
            $arr=[];
            $arr['command'] = \json_encode($command);
            $arr['type'] = 1;
            $arr['status'] = 1;
            $arr['addtime'] = time();
            $arr['device_type'] = $data['type'];
            $arr['equipment_id'] = $res;
            $db_house_new_pile_command->addOne($arr);
        }
        return $res;
    }

    /**
     * 编辑设备
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2022/9/13 11:26
     */
    public function editEquipment($data)
    {
        $db_house_new_pile_config = new HouseNewPileConfig();
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $config_info = $db_house_new_pile_config->getFind(['village_id' => $data['village_id']]);
        if (empty($config_info)) {
            throw new \think\Exception("站点信息不存在");
        }
        $equipment_info = $db_house_new_pile_equipment->getInfo(['village_id' => $data['village_id'], 'id' => $data['id']]);
        if (empty($equipment_info)) {
            throw new \think\Exception("设备信息不存在");
        }
        $where_info = [
            ['id', '<>', $data['id']],
            ['equipment_num', '=', $data['equipment_num']],
            ['is_del', '=', 1]
        ];
        $equipment_info1 = $db_house_new_pile_equipment->getInfo($where_info);
        if (!empty($equipment_info1)) {
            throw new \think\Exception("设备唯一编号不能重复");
        }
        $where_info1 = [
            ['id', '<>', $data['id']],
            ['equipment_name', '=', $data['equipment_name']],
            ['is_del', '=', 1]
        ];
        $equipment_info2 = $db_house_new_pile_equipment->getInfo($where_info1);
        if (!empty($equipment_info2)) {
            throw new \think\Exception("设备名称不能重复");
        }

        $arr = [];
        $arr['equipment_name'] = $data['equipment_name'];
        $arr['village_id'] = $data['village_id'];
        $arr['device_brand'] = $data['brand'];
        $arr['brand_type'] = $data['brandType'];
        $arr['equipment_num'] = $data['equipment_num'];
        $arr['socket_num'] = $data['socket_num'];
        $arr['type'] = $data['type'];
        $arr['remark'] = $data['remark'];
        $arr['power'] = $data['power'];
        $arr['min_voltage'] = $data['min_voltage'];
        $arr['max_voltage'] = $data['max_voltage'];
        $arr['min_electric_current'] = $data['min_electric_current'];
        $arr['max_electric_current'] = $data['max_electric_current'];
        $arr['min_temperature'] = $data['min_temperature'];
        $arr['max_temperature'] = $data['max_temperature'];
        if ($data['socket_num'] > 0 && $equipment_info['socket_num'] != $data['socket_num']) {
            // 插口数量变更 插口状态数量同步变更
            for ($i = 0; $i < $data['socket_num']; $i++) {
                $num[] = 0;
            }
            $arr['socket_status'] = \json_encode($num);
        }
        $res = $db_house_new_pile_equipment->save_one(['id' => $data['id']], $arr);
        if ($equipment_info['last_heart_time'] > 1 && $arr['type'] == 1) {
            $dataArr = [
                'equipment_id' => $equipment_info['id'],
            ];
            (new HouseNewEnerpyPileService)->setPileTimeAndCharge($dataArr);
        }

        return $res;
    }


    /**
     * 删除设备
     * @author:zhubaodi
     * @date_time: 2022/9/13 11:42
     */
    public function delEquipment($data)
    {
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $equipment_info = $db_house_new_pile_equipment->getInfo(['village_id' => $data['village_id'], 'id' => $data['id'], 'is_del' => 1]);
        if (empty($equipment_info)) {
            throw new \think\Exception("设备信息不存在");
        }
        $res = $db_house_new_pile_equipment->save_one(['village_id' => $data['village_id'], 'id' => $data['id'], 'is_del' => 1], ['is_del' => 2]);
        return $res;
    }

    /**
     * 查询订单列表
     * @author:zhubaodi
     * @date_time: 2022/9/14 20:49
     */
    public function getOrderList($data)
    {
        $db_house_new_pile_pay_order = new HouseNewPilePayOrder();
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $service_user = new User();
        $where = [];
        $where[] = ['c.village_id', '=', $data['village_id']];
        $where[] = ['c.status', '>', 0];
        if (!empty($data['order_no'])) {
            $where[] = ['c.order_no', '=', $data['order_no']];
        }
        if (!empty($data['equipment_name'])) {
            $where[] = ['e.equipment_name', 'like', '%' . $data['equipment_name'] . '%'];
        }
        if (!empty($data['status'])) {
            $where[] = ['c.status', '=', $data['status']];
        }
        if (!empty($data['start_time'])) {
            $where[] = ['c.pay_time', '>=', strtotime($data['start_time'])];
        }
        if (!empty($data['end_time'])) {
            $where[] = ['c.pay_time', '<=', strtotime($data['end_time'])];
        }
        if (!empty($data['uid'])) {
            $where[] = ['c.uid', '=', $data['uid']];
        }

        $list = $db_house_new_pile_pay_order->getOrderList($where, 'c.*,e.equipment_num,e.equipment_name', $data['page'], $data['limit']);
        if (!empty($list)) {
            $list = $list->toArray();
        }
        $order_arr = [];
        $totalPaiedMoney=0;
        if (!empty($list)) {
            $totalPaiedMoney=$db_house_new_pile_pay_order->getSum($where,'c.use_money');
            $totalPaiedMoney=$totalPaiedMoney ? round($totalPaiedMoney,2):0;
            $totalPaiedMoney=$totalPaiedMoney*1;
            $totalUseEle=$db_house_new_pile_pay_order->getSum($where,'c.use_ele');
            $totalUseEle=$totalUseEle ? round($totalUseEle,2):0;
            $totalUseEle=$totalUseEle*1;
            foreach ($list as $v) {
                $arr = [];
                $arr['id'] = $v['id'];
                $arr['order_no'] = $v['order_no'];
                $arr['order_serial'] = $v['order_serial'];
                $arr['village_id'] = $v['village_id'];
                $arr['equipment_id'] = $v['equipment_id'];
                $arr['equipment_num'] = $v['equipment_num'];
                $arr['equipment_name'] = $v['equipment_name'];
                $arr['car_number'] = $v['car_number'];
                $arr['use_money'] = $v['use_money'];
                $arr['use_ele'] = $v['use_ele'];
                $arr['end_power'] = $v['end_power'];
                if ($v['pay_time'] > 1) {
                    $arr['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
                } else {
                    $arr['pay_time'] = '--';
                }
                if ($v['end_time'] > 1) {
                    $arr['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
                } else {
                    $arr['end_time'] = '--';
                }
                if ($v['continued_time'] > 0) {
                    $arr['continued_time'] = round_number($v['continued_time'] / 60, 2);
                } else {
                    $arr['continued_time'] = '--';
                }
                if ($v['pay_type'] == 1) {
                    $arr['pay_type'] = '余额支付';
                }
                if ($v['status'] == 1) {
                    $arr['status'] = '已结束';
                } elseif ($v['status'] == 2) {
                    $arr['status'] = '充电中';
                }
                $arr['name'] = '';
                $arr['phone'] = '';
                $user_info = $service_user->getOne(['uid' => $v['uid']]);
                if (!empty($user_info)) {
                    $arr['name'] = $user_info['nickname'];
                    $arr['phone'] = $user_info['phone'];
                }
                $order_arr[] = $arr;
            }
        }
        $count = $db_house_new_pile_pay_order->getOrderCount($where);
        $data1 = [];
        $data1['list'] = $order_arr;
        $data1['count'] = $count;
        $data1['total_limit'] = $data['limit'];
        $data1['totalPaiedMoney'] =$totalPaiedMoney.'元';
        $data1['totalUseEle']=$totalUseEle.'度';
        return $data1;
    }

    /**
     * 查询订单详情
     * @author:zhubaodi
     * @date_time: 2022/9/14 20:51
     */
    public function getOrderDetail($data)
    {
        $db_house_new_pile_pay_order = new HouseNewPilePayOrder();
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $db_house_new_pile_charge = new HouseNewPileCharge();
        $db_house_new_pile_pay_order_log = new HouseNewPilePayOrderLog();
        $service_user = new User();
        $where = ['village_id' => $data['village_id'], 'id' => $data['id']];
        $order_info = $db_house_new_pile_pay_order->getFind($where);
        $arr = [];
        if (!empty($order_info)) {
            $equipment_info = $db_house_new_pile_equipment->getInfo(['id' => $order_info['equipment_id']]);
            $list = [];
            $arr['id'] = $order_info['id'];
            $arr['order_no'] = $order_info['order_no'];
            $arr['order_serial'] = $order_info['order_serial'];
            $arr['village_id'] = $order_info['village_id'];
            $arr['equipment_id'] = $order_info['equipment_id'];
            $arr['equipment_num'] = $equipment_info['equipment_num'];
            $arr['equipment_name'] = $equipment_info['equipment_name'];
            $arr['car_number'] = $order_info['car_number'];
            $arr['use_ele'] = $order_info['use_ele'];
            $arr['use_money'] = $order_info['use_money'];
            $arr['end_power'] = $order_info['end_power'];
            if ($order_info['pay_time'] > 1) {
                $arr['pay_time'] = date('Y-m-d H:i:s', $order_info['pay_time']);
            } else {
                $arr['pay_time'] = '--';
            }
            if ($order_info['end_time'] > 1) {
                $arr['end_time'] = date('Y-m-d H:i:s', $order_info['end_time']);
            } else {
                $arr['end_time'] = '--';
            }
            if ($order_info['continued_time'] > 0) {
                $arr['continued_time'] = round_number($order_info['continued_time'] / 60, 2);
            } else {
                $arr['continued_time'] = '--';
            }
            if ($order_info['pay_type'] == 1) {
                $arr['pay_type'] = '余额支付';
            }
            if ($order_info['status'] == 1) {
                $arr['status'] = '已结束';
                $arr['end_result'] = $order_info['end_result'];
            } elseif ($order_info['status'] == 2) {
                $arr['status'] = '充电中';
                $arr['end_result'] = '--';
            }
            $arr['name'] = '';
            $arr['phone'] = '';
            $user_info = $service_user->getOne(['uid' => $order_info['uid']]);
            if (!empty($user_info)) {
                $arr['name'] = $user_info['nickname'];
                $arr['phone'] = $user_info['phone'];
            }
            $list[] = [
                'key' => '支付订单编号',
                'value' => $arr['order_no'],
            ];
            $list[] = [
                'key' => '交易流水号',
                'value' => $arr['order_serial'],
            ];
            $list[] = [
                'key' => '设备名称',
                'value' => $arr['equipment_name'],
            ];
            $list[] = [
                'key' => '设备唯一编码（桩编号）',
                'value' => $arr['equipment_num'],
            ];
            $list[] = [
                'key' => '姓名',
                'value' => $arr['name'],
            ];
            $list[] = [
                'key' => '联系方式',
                'value' => $arr['phone'],
            ];
            $list[] = [
                'key' => '充电开始时间',
                'value' => $arr['pay_time'],
            ];
            $list[] = [
                'key' => '充电结束时间',
                'value' => $arr['end_time'],
            ];
            $list[] = [
                'key' => '充电时长',
                'value' => $arr['continued_time'],
            ];
            $list[] = [
                'key' => '充电车牌号',
                'value' => $arr['car_number'],
            ];
            $list[] = [
                'key' => '状态',
                'value' => $arr['status'],
            ];
            $list[] = [
                'key' => '充电金额',
                'value' => $arr['use_money'],
            ];
            $list[] = [
                'key' => '充电电量',
                'value' => $arr['use_ele'],
            ];
            $list[] = [
                'key' => '结束功率',
                'value' => $arr['end_power'],
            ];
            $list[] = [
                'key' => '结束原因',
                'value' => $arr['end_result'],
            ];
            if (isset($order_info['socket_no'])) {
                $list[] = [
                    'key' => '插座编号',
                    'value' => $order_info['socket_no'],
                ];
            }
            if (isset($order_info['add_time']) && $order_info['add_time'] > 1) {
                $add_time_text = date('Y-m-d H:i:s', $order_info['add_time']);
                $list[] = [
                    'key' => '发起时间',
                    'value' => $add_time_text,
                ];
            }
            $charge_info1 = [];
            if ($order_info['status'] == 1 && !empty($order_info['charge_id'])) {
                //查询收费标准
                $charge_info = $db_house_new_pile_charge->getFind(['village_id' => $order_info['village_id'], 'rule_id' => $order_info['charge_id'], 'status' => 1]);
                $log_list=$db_house_new_pile_pay_order_log->get_list(['order_id'=>$order_info['id']],true,0,0,'id ASC');
                if (!empty($log_list)){
                    $log_list=$log_list->toArray();
                }
                if (!empty($log_list)&&!empty($charge_info)){
                    $charge_time = \json_decode($charge_info['charge_time'], true);
                    foreach ($log_list as $k=>&$vv) {
                        $arr1 = [];
                        $chargeMoney = [];
                        foreach ($charge_time as $kk => $vc) {
                            $time_end = strtotime(date('Y-m-d', $vv['add_time']) . ' ' . $vc['time_end']);
                            $time_start = strtotime(date('Y-m-d', $vv['add_time']) . ' ' . $vc['time_start']);
                            $data_log = '';
                            if ($time_end >= $vv['add_time'] && $time_start <= $vv['add_time']) {
                                $data_log = $vc['money'];
                                $vv['index'] = $kk;
                                $vv['time_start'] = $vc['time_start'];
                                $vv['time_end'] = $vc['time_end'];
                                break;
                            }
                        }
                        if (empty($data_log) && $charge_info['price_set_value'] != '-1') {
                            $data_log = $charge_info['price_set_value'];
                            $vv['index'] = '-1';
                        }
                        if (isset($data_log) && !empty($data_log)) {
                            $charge_money = $charge_info['charge_' . ($data_log + 1)];
                            if (!empty($charge_money)) {
                                $chargeMoney = \json_decode($charge_money, JSON_UNESCAPED_UNICODE);
                            }
                        }
                        if (!isset($chargeMoney)) {
                            $vv['charge_ele'] = 0;
                            $vv['charge_serve'] = 0;
                        } else {
                            $vv['charge_ele'] = $chargeMoney['charge_ele'];
                            $vv['charge_serve'] = $chargeMoney['charge_serve'];
                        }
                    }
                    foreach ($log_list as $k1=>$vv1)
                    {
                        $arr1 = [];
                        if ($k1==0){
                            $use_ele=$log_list[0]['use_ele'];
                            $use_money=$log_list[0]['use_money'];
                            continue;
                        }
                        if ($vv1['index']==$log_list[$k1-1]['index']){
                            if ($vv1['type']==1){
                                $use_ele=$vv1['use_ele'];
                                $use_money=$vv1['use_money'];
                            }else{
                                $use_ele=$vv1['use_ele']+ $use_ele;
                                $use_money=$vv1['use_money']+ $use_money;
                            }
                        }else{
                            $use_ele=$vv1['use_ele'];
                            $use_money=$vv1['use_money'];
                        }
                        if (!isset($log_list[$k1+1])){
                            $vv1['time_end']=date('H:i',$order_info['end_time']);
                        }
                        if ((isset($log_list[$k1+1])&&$vv1['index']!=$log_list[$k1+1]['index'])||(!isset($log_list[$k1+1]))){
                            if ($vv1['index']==$log_list[0]['index']){
                                $arr1['time']=date('H:i',$order_info['pay_time']).'-'.$vv1['time_end'];
                            }else{
                                $arr1['time']=$vv1['time_start'].'-'.$vv1['time_end'];
                            }
                            $arr1['ele_money'] = $vv1['charge_ele'];
                            $arr1['serve_money'] = $vv1['charge_serve'];
                            $arr1['use_ele'] = round_number($use_ele, 4);
                            $arr1['use_money'] = round_number($use_money, 4);
                            $charge_info1[] = $arr1;
                        }
                    }
                }
            }
            $data1 = [];
            $data1['list'] = $list;
            $data1['charge_info'] = $charge_info1;
        }
        return $data1;
    }


    /**
     * 查询退款订单列表
     * @author:zhubaodi
     * @date_time: 2022/9/14 20:49
     */
    public function getRefundOrderList($data)
    {
        $db_house_new_pile_refund_order = new HouseNewPileRefundOrder();
        $where = [];
        $where[] = ['village_id' => $data['village_id']];
        $list = $db_house_new_pile_refund_order->get_list($where, '*', $data['page'], $data['limit']);
        $count = $db_house_new_pile_refund_order->getCount($where);
        $data1 = [];
        $data1['list'] = $list;
        $data1['count'] = $count;
        $data1['total_limit'] = $data['limit'];
        return $data1;
    }

    /**
     * 查询退款订单详情
     * @author:zhubaodi
     * @date_time: 2022/9/14 20:51
     */
    public function getRefundOrderDetail($data)
    {
        $db_house_new_pile_pay_order_refund = new HouseNewPileRefundOrder();
        $where = [];
        $where[] = ['village_id' => $data['village_id'], 'id' => $data['id']];
        $order_info = $db_house_new_pile_pay_order_refund->getFind($where);
        return $order_info;
    }

    /**
     *查询设备枪头二维码
     * @author:zhubaodi
     * @date_time: 2022/9/26 20:37
     */
    public function getEquipmentCode($data)
    {
        //查询设备列表
        $service_equipment = new HouseNewPileEquipment();
        $equipment_list = $service_equipment->getInfo(['village_id' => $data['village_id'], 'id' => $data['id'], 'status' => 1, 'is_del' => 1], '*');

        if (empty($equipment_list)) {
            throw new \think\Exception("设备信息不存在");
        }
        //二维码
        $qrcode_url = get_base_url() . 'pages/newCharge/pages/startCharging?id=' . $data['id'] . '&socket=' . $data['socket'] . '&village_id=' . $data['village_id'];
        $nowtime = time();
        // 创建目录
        $filename = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload/pile/' . date('Ymd') . '/qrcode_' . $data['id'] . '_' . $nowtime . '.png';
        $dirName = dirname($filename);
        if (!file_exists($dirName)) {
            mkdir($dirName, 0777, true);
        }
        if (!file_exists($filename)) {
            $QRcode = new \QRcode();
            $errorCorrectionLevel = 'L';  //容错级别
            $matrixPointSize = 5;      //生成图片大小
            $QRcode::png($qrcode_url, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
        }
        $filename1 = '/upload/pile/' . date('Ymd') . '/qrcode_' . $data['id'] . '_' . $nowtime . '.png';
        $data = cfg('site_url') . $filename1;
        return ['qrcode' => $data];

    }

    /**
     * 查询小区充电账户列表
     * @author:zhubaodi
     * @date_time: 2022/10/18 16:01
     */
    public function getUserMoneyList($data){
        $db_house_new_pile_user_money=new HouseNewPileUserMoney();
        $where=[
           ['p.business_type','=',1],
           ['p.business_id','=',$data['village_id']]
            ];
        if (!empty($data['name'])){
            $where[]=['u.nickname','like','%'.$data['name'].'%'];
        }
        if (!empty($data['phone'])){
            $where[]=['u.phone','like','%'.$data['phone'].'%'];
        }
        if (!empty($data['card_no'])){
            $where[]=['p.card_no','=',$data['card_no']];
        }
        $list=$db_house_new_pile_user_money->getUserList($where,'p.uid,p.card_no,p.pile_card_no,p.current_money,u.nickname as name,u.phone',$data['page'],$data['limit']);
        if (!empty($list)){
            $list=$list->toArray();
        }
        $count=$db_house_new_pile_user_money->getUserCount($where);
        $data1=[];
        $data1['list']=$list;
        $data1['count']=$count;
        $data1['limit']=$data['limit'];
        return $data1;
    }


    /**
     * 查询充电卡号
     * @author:zhubaodi
     * @date_time: 2022/10/18 16:54
     */
    public function getUserCardInfo($data){
        $db_house_new_pile_user_money=new HouseNewPileUserMoney();
        $cardInfo=$db_house_new_pile_user_money->getOne(['id'=>$data['id'],'business_id'=>$data['village_id']],'card_no,pile_card_no');
        if (empty($cardInfo)){
            $cardInfo=(object)[];
        }
        return $cardInfo;
    }


    /**
     * 编辑充电卡号
     * @author:zhubaodi
     * @date_time: 2022/10/18 17:06
     */
    public function editUserCard($data){
       $db_house_new_pile_user_money=new HouseNewPileUserMoney();
       $cardInfo=$db_house_new_pile_user_money->getOne(['uid'=>$data['id'],'business_id'=>$data['village_id']],'uid,card_no,pile_card_no');
       if (empty($cardInfo)){
           throw new \think\Exception("卡信息不存在");
       }
       $res=$db_house_new_pile_user_money->saveOne(['uid'=>$data['id'],'business_id'=>$data['village_id']],['card_no'=>$data['card_no'],'pile_card_no'=>$data['pile_card_no']]);
       return $res; 
    }

    /**
     * 充值明细
     * @author:zhubaodi
     * @date_time: 2022/10/18 19:04
     */
    public function getUserMoneyLog($data){
        $db_house_new_pile_user_money_log=new HouseNewPileUserMoneyLog();
        $db_house_new_pile_user_money=new HouseNewPileUserMoney();
        $userInfo=$db_house_new_pile_user_money->getOne(['uid'=>$data['id'],'business_id'=>$data['village_id']]);
        if (empty($userInfo)){
            throw new \think\Exception("账户信息不存在"); 
        }
        $list=$db_house_new_pile_user_money_log->getList(['uid'=>$userInfo['uid'],'business_id'=>$data['village_id']],'*',$data['page'],$data['limit']);
        $count=$db_house_new_pile_user_money_log->getCount(['uid'=>$userInfo['uid'],'business_id'=>$data['village_id']]);
        if (!empty($list)){
            $list=$list->toArray(); 
        }
        if (!empty($list)){
            foreach ($list as &$v){
                if (!empty($v['add_time'])){
                    $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                }else{
                    $v['add_time']='--';
                }
                if ($v['type']==1){
                    $v['money']='+'.$v['money'];
                }else{
                    $v['money']='-'.$v['money']; 
                }
            }
        }
        $data1=[];
        $data1['list']=$list;
        $data1['count']=$count;
        $data1['limit']=$data['limit'];
        return $data1;
    }
    
    
    
    /**
     * 查询协议内容
     * @author:zhubaodi
     * @date_time: 2022/10/18 17:24
     */
    public function getNews($data){
        $db_house_new_pile_news=new HouseNewPileNews();
        $info=$db_house_new_pile_news->getFind(['village_id'=>$data['village_id'],'type'=>$data['type']]);
        $info['content']=htmlspecialchars_decode($info['content']);
        return $info;
    }


    /**
     * 编辑协议内容
     * @author:zhubaodi
     * @date_time: 2022/10/18 18:05
     */
    public function editNews($data){
        $db_house_new_pile_news=new HouseNewPileNews();
        $info=$db_house_new_pile_news->getFind(['village_id'=>$data['village_id'],'type'=>$data['type']]);
        if (empty($info)){
            $arr=[];
            $arr['village_id']=$data['village_id'];
            $arr['title']=$data['title'];
            $arr['content']=htmlspecialchars($data['content']);
            $arr['status']=$data['status'];
            $arr['addTime']=time();
            $arr['updateTime']=time();
            $arr['type']=$data['type'];
            $res=$db_house_new_pile_news->addOne($arr);
        }else{
            $arr=[];
            $arr['title']=$data['title'];
            $arr['content']=htmlspecialchars($data['content']);
            $arr['status']=$data['status'];
            $arr['updateTime']=time();
            $res=$db_house_new_pile_news->save_one(['village_id'=>$data['village_id']],$arr);
        }
        return $res;
    }

    /**
     * 余额提现
     * @author:zhubaodi
     * @date_time: 2022/10/17 11:20
     */
    public function withdraw($data){
        $db_house_new_pile_user_money=new HouseNewPileUserMoney();
        $db_house_new_pile_user_money_log=new HouseNewPileUserMoneyLog();
        $db_house_new_pile_withdraw=new HouseNewPileWithdraw();
        $where=[
            'uid'=>$data['id'],
            'business_id'=>$data['village_id'],
        ];
        $userMoneyInfo=$db_house_new_pile_user_money->getOne($where);
        if (empty($userMoneyInfo)){
            throw new \think\Exception("账户余额信息不存在，无法退款！");
        }
        if($data['current_money']>$userMoneyInfo['current_money']){
            throw new \think\Exception("当前账户余额不足，无法退款！");
        }
        $extra_data='';
            if ($data['type']==1){
                //提现到微信 
                $flag='微信';
                $service_user=new User();
                $user_data = $service_user->getOne(['uid' => $userMoneyInfo['uid']]);
                if($user_data && !$user_data->isEmpty()){
                    $openid=$user_data['openid'];
                    if($openid && strlen($openid)>16){
                        $paramWx=array();
                        $paramWx['openid']=$openid;
                        $paramWx['money']=$data['current_money']*100;
                        $name=$user_data['nickname'];
                        if(isset($data['true_name']) && !empty($data['true_name'])){
                            $data['true_name']=trim($data['true_name']);
                            $name=$data['true_name'];
                        }
                        $paramWx['name']=$name;
                        $paramWx['withdraw_name']=$name."余额提现";
                        $paramWx['partner_trade_no']=date('mdHis').'000'.$data['village_id'].'090'. $userMoneyInfo['uid'];
                        $withdrawWx=array('wxparam'=>$paramWx);
                        $res= invoke_cms_model('Plat_order/withdrawWx',$withdrawWx);
                        fdump_api([__LINE__,'param'=>$paramWx,'res'=>$res],'000withdrawWx',1);
                        $error_msg='';
                        $issuccess=false;
                        if( !empty($res['retval'])){
                            if($res['retval']['error_code'] && $res['retval']['error_code']>0){
                                $error_msg=$res['retval']['msg'];
                            }else{
                                $third_id=$res['retval']['third_id'];
                                $extra_data=$res['retval'];
                                $extra_data['partner_trade_no']=$paramWx['partner_trade_no'];
                                $issuccess=true;
                            }
                        }else{
                            $error_msg='提现失败了，'.$res['error_msg'];
                        }
                        if(!$issuccess){
                            $error_msg=$error_msg?$error_msg:'提现失败了!';
                            throw new \think\Exception($error_msg);
                        }
                    }else{
                        throw new \think\Exception('未获取到用户微信身份openId');
                    }
                }else{
                    throw new \think\Exception('提现失败了!!');
                }
            }else{
                //提现到平台余额
                $flag='平台余额';
                $useResult = (new UserService())->addMoney($userMoneyInfo['uid'], $data['current_money'], L_("小区汽车充电桩账户余额提现，增加余额，小区编号X1", array("X1" =>$data['village_id'])));
                if ($useResult['error_code']) {
                    throw new \think\Exception($useResult['msg']);
                }
            }
            $res=$db_house_new_pile_user_money->saveOne($where,['current_money'=>($userMoneyInfo['current_money']-$data['current_money'])]);
            $arr=[];
            $arr['uid']=$userMoneyInfo['uid'];
            $arr['business_type']=1;
            $arr['business_id']=$data['village_id'];
            $arr['type']=2;
            $arr['current_money']=$userMoneyInfo['current_money'];
            $arr['money']=$data['current_money'];
            $arr['after_price']=($userMoneyInfo['current_money']-$data['current_money']);
            $arr['add_time']=time();
            $arr['ip']=get_client_ip();
            $arr['desc']='后台操作账户余额提现'.$flag;
            $db_house_new_pile_user_money_log->addOne($arr);
            $arr1=[];
            $arr1['uid']=$userMoneyInfo['uid'];
            $arr1['village_id']=$data['village_id'];
            $arr1['status']=3;
            $arr1['refundType']=$data['type'];
            $arr1['refund_money']=$data['current_money'];
            $arr1['add_time']=time();
            $arr1['refund_reason']=isset($data['reason']) && !empty($data['reason']) ? $data['reason']:'后台操作账户余额提现';
            if(isset($data['true_name']) && !empty($data['true_name'])){
                $arr1['true_name']=trim($data['true_name']);
            }
            if(!empty($extra_data) && is_array($extra_data)){
                $arr1['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
            }
        $idd=$db_house_new_pile_withdraw->addOne($arr1);
        $housePaidOrderRecordService= new HousePaidOrderRecordService();
        $housePaidOrderRecordService->carElePileOrderPayRecord($idd,$data['village_id'],$data['current_money'],true);

        return $res;
    }

    /**
     * 查询余额提现申请列表
     * @author:zhubaodi
     * @date_time: 2022/10/19 11:15
     */
    public function getWithdrawList($data){
        $db_house_new_pile_withdraw=new HouseNewPileWithdraw();
        $where[]=['p.village_id','=',$data['village_id']];
        if (!empty($data['status'])){
            $where[]=['p.status','=',$data['status']];
        }
        if (!empty($data['name'])){
            $where[]=['u.nickname','=',$data['name']];
        }
        if (!empty($data['phone'])){
            $where[]=['u.phone','=',$data['phone']];
        }
        if (!empty($data['order_id'])){
            $where[]=['p.id','=',$data['order_id']];
        }
        if (!empty($data['start_time'])){
            $where[]=['p.add_time','>=',strtotime($data['start_time'])];
        }
        if (!empty($data['end_time'])){
            $where[]=['p.add_time','<=',strtotime($data['end_time'].' 23:59:59')];
        }
        $list=$db_house_new_pile_withdraw->getUserList($where,'p.*,u.nickname as name,u.phone',$data['page'],$data['limit'],'p.id DESC');
        if (!empty($list)){
            $list=$list->toArray();   
        }
        if (!empty($list)){
            foreach ($list as &$v){
                if (!empty($v['add_time'])){
                    $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                }else{
                    $v['add_time']='--';
                }
                if (!empty($v['status'])){
                    if ($v['status']==1){
                        $v['status_txt']='已取消';
                    }elseif ($v['status']==2){
                        $v['status_txt']='待审核';
                    }elseif ($v['status']==3){
                        $v['status_txt']='审核成功';
                    }elseif ($v['status']==4){
                        $v['status_txt']='审核失败';
                    }
                }
                
            }
        }
        $count=$db_house_new_pile_withdraw->getUserCount($where);
        $data1=[];
        $data1['list']=$list;
        $data1['count']=$count;
        $data1['limit']=$data['limit'];
        return $data1;
    }

    /**
     * 查询余额提现申请详情
     * @author:zhubaodi
     * @date_time: 2022/10/19 11:15
     */
    public function getWithdrawInfo($data){
        $db_house_new_pile_withdraw=new HouseNewPileWithdraw();
        $service_user=new UserService();
        $where=[
            ['village_id','=',$data['village_id']],
            ['id','=',$data['id']],
            ];
        $info=$db_house_new_pile_withdraw->getFind($where);
        if (empty($info)){
            throw new \think\Exception("提现申请信息不存在");
        }
        $user_info=$service_user->getUser($info['uid']);
        $info['name']=$user_info['nickname'];
        $info['phone']=$user_info['phone'];
        return $info;
    }
    

    /**
     * 余额提现申请审核
     * @author:zhubaodi
     * @date_time: 2022/10/19 13:10
     */
    public function checkWithdraw($data){
        $db_house_new_pile_withdraw=new HouseNewPileWithdraw();
        $db_house_new_pile_user_money=new HouseNewPileUserMoney();
        $db_house_new_pile_user_money_log=new HouseNewPileUserMoneyLog();
        $withdraw_info=$db_house_new_pile_withdraw->getFind(['id'=>$data['id'],'village_id'=>$data['village_id']]);
        if (empty($withdraw_info)){
            throw new \think\Exception("提现申请信息不存在");
        }
        $user_info=$db_house_new_pile_user_money->getOne(['uid'=>$withdraw_info['uid'],'business_id'=>$data['village_id'],'business_type'=>1]);
        if (empty($user_info)){
            $db_house_new_pile_withdraw->save_one(['id'=>$data['id'],'village_id'=>$data['village_id']],['status'=>4,'refuse_reason'=>'提现账户信息不存在','refund_time'=>time()]);
            throw new \think\Exception("提现账户信息不存在");
        }
        $extra_data='';
        if ($data['status']==3){
            if ($withdraw_info['refund_money']>$user_info['current_money']){
                $db_house_new_pile_withdraw->save_one(['id'=>$data['id'],'village_id'=>$data['village_id']],['status'=>4,'refuse_reason'=>'账户余额不足','refund_time'=>time()]);
                throw new \think\Exception("账户余额不足，无法提现");
            }
                if ($withdraw_info['refundType']==1){
                    //提现到微信 
                    $flag='微信';
                    $service_user=new User();
                    $user_data = $service_user->getOne(['uid' => $withdraw_info['uid']]);
                    if($user_data && !$user_data->isEmpty()){
                        $openid=$user_data['openid'];
                        if($openid && strlen($openid)>16){
                            $paramWx=array();
                            $paramWx['openid']=$openid;
                            $paramWx['money']=$withdraw_info['refund_money']*100;
                            $name=trim($user_data['nickname']);
                            if(isset($withdraw_info['true_name']) && !empty($withdraw_info['true_name'])){
                                $name=trim($withdraw_info['true_name']);
                            }
                            $paramWx['name']=$name;
                            $paramWx['withdraw_name']=$name."余额提现";
                            $paramWx['partner_trade_no']=date('mdHis').'000'.$withdraw_info['village_id'].'000'.$withdraw_info['id'];
                            $withdrawWx=array('wxparam'=>$paramWx);
                            $res= invoke_cms_model('Plat_order/withdrawWx',$withdrawWx);
                            fdump_api([__LINE__,'param'=>$paramWx,'res'=>$res],'000withdrawWx',1);
                            $error_msg='';
                            $issuccess=false;
                            if( !empty($res['retval'])){
                                if($res['retval']['error_code'] && $res['retval']['error_code']>0){
                                    $error_msg=$res['retval']['msg'];
                                }else{
                                    $third_id=$res['retval']['third_id'];
                                    $extra_data=$res['retval'];
                                    $extra_data['partner_trade_no']=$paramWx['partner_trade_no'];
                                    $issuccess=true;
                                }
                            }else{
                                $error_msg='提现失败了，'.$res['error_msg'];
                            }
                            if(!$issuccess){
                                $error_msg=$error_msg?$error_msg:'提现失败了!';
                                throw new \think\Exception($error_msg);
                            }
                        }else{
                            throw new \think\Exception('未获取到用户微信身份openId');
                        }
                    }else{
                        throw new \think\Exception('提现失败了!!');
                    }
                }else{
                    //提现到平台余额
                    $flag='平台余额';
                    $useResult = (new UserService())->addMoney($withdraw_info['uid'], $withdraw_info['refund_money'], L_("小区汽车充电桩账户余额提现，增加余额，小区编号X1", array("X1" =>$data['village_id'])));
                    if ($useResult['error_code']) {
                        throw new \think\Exception($useResult['msg']);
                    }
                }
                $res=$db_house_new_pile_user_money->saveOne(['uid'=>$withdraw_info['uid'],'business_id'=>$data['village_id'],'business_type'=>1],['current_money'=>($user_info['current_money']-$withdraw_info['refund_money'])]);
                $saveArr=['status'=>3,'refund_time'=>time()];
                if($extra_data && is_array($extra_data)){
                    $saveArr['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
                }
                $result=$db_house_new_pile_withdraw->save_one(['id'=>$data['id'],'village_id'=>$data['village_id']],$saveArr);
            $housePaidOrderRecordService= new HousePaidOrderRecordService();
            $housePaidOrderRecordService->carElePileOrderPayRecord($data['id'],$data['village_id'],$withdraw_info['refund_money'],true);
                $arr=[];
                $arr['uid']=$withdraw_info['uid'];
                $arr['business_type']=1;
                $arr['business_id']=$data['village_id'];
                $arr['type']=2;
                $arr['current_money']=$user_info['current_money'];
                $arr['money']=$withdraw_info['refund_money'];
                $arr['after_price']=($user_info['current_money']-$withdraw_info['refund_money']);
                $arr['add_time']=time();
                $arr['ip']=get_client_ip();
                $arr['desc']='后台操作账户余额提现'.$flag;
                $db_house_new_pile_user_money_log->addOne($arr);
                
        }else{
            $result=$db_house_new_pile_withdraw->save_one(['id'=>$data['id'],'village_id'=>$data['village_id']],['status'=>4,'refuse_reason'=>$data['reason'],'refund_time'=>time()]);
        }
        return $result;
    }
    

    //----------------------用户端功能接口start----------------//
    
    /**
     * CRC算法
     * @author:zhubaodi
     * @date_time: 2022/10/18 15:50
     */
    public function wordStr($wordStr)
    {
        $wordStr = str_replace(' ', '', $wordStr);
        $wordCmd = $wordStr;
        //   $wordStr .= '0000';
        $packStr = pack('H*', $wordStr);// 把数据装入一个二进制字符串[十六进制字符串，高位在前]
        $crc166Str = $this->crc166($packStr);// 进行CRC16校验
        $unpackStr = unpack("H*", $crc166Str); // 从二进制字符串对数据进行解包[十六进制字符串，高位在前];
        return strtoupper($unpackStr[1]);
    }
    public function crc166($string, $length = 0)
    {
        $auchCRCHi = array(0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81,
            0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0,
            0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01,
            0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81,
            0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0,
            0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01,
            0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81,
            0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0,
            0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01,
            0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81,
            0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0,
            0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01,
            0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81,
            0x40);
        $auchCRCLo = array(0x00, 0xC0, 0xC1, 0x01, 0xC3, 0x03, 0x02, 0xC2, 0xC6, 0x06, 0x07, 0xC7, 0x05, 0xC5, 0xC4,
            0x04, 0xCC, 0x0C, 0x0D, 0xCD, 0x0F, 0xCF, 0xCE, 0x0E, 0x0A, 0xCA, 0xCB, 0x0B, 0xC9, 0x09,
            0x08, 0xC8, 0xD8, 0x18, 0x19, 0xD9, 0x1B, 0xDB, 0xDA, 0x1A, 0x1E, 0xDE, 0xDF, 0x1F, 0xDD,
            0x1D, 0x1C, 0xDC, 0x14, 0xD4, 0xD5, 0x15, 0xD7, 0x17, 0x16, 0xD6, 0xD2, 0x12, 0x13, 0xD3,
            0x11, 0xD1, 0xD0, 0x10, 0xF0, 0x30, 0x31, 0xF1, 0x33, 0xF3, 0xF2, 0x32, 0x36, 0xF6, 0xF7,
            0x37, 0xF5, 0x35, 0x34, 0xF4, 0x3C, 0xFC, 0xFD, 0x3D, 0xFF, 0x3F, 0x3E, 0xFE, 0xFA, 0x3A,
            0x3B, 0xFB, 0x39, 0xF9, 0xF8, 0x38, 0x28, 0xE8, 0xE9, 0x29, 0xEB, 0x2B, 0x2A, 0xEA, 0xEE,
            0x2E, 0x2F, 0xEF, 0x2D, 0xED, 0xEC, 0x2C, 0xE4, 0x24, 0x25, 0xE5, 0x27, 0xE7, 0xE6, 0x26,
            0x22, 0xE2, 0xE3, 0x23, 0xE1, 0x21, 0x20, 0xE0, 0xA0, 0x60, 0x61, 0xA1, 0x63, 0xA3, 0xA2,
            0x62, 0x66, 0xA6, 0xA7, 0x67, 0xA5, 0x65, 0x64, 0xA4, 0x6C, 0xAC, 0xAD, 0x6D, 0xAF, 0x6F,
            0x6E, 0xAE, 0xAA, 0x6A, 0x6B, 0xAB, 0x69, 0xA9, 0xA8, 0x68, 0x78, 0xB8, 0xB9, 0x79, 0xBB,
            0x7B, 0x7A, 0xBA, 0xBE, 0x7E, 0x7F, 0xBF, 0x7D, 0xBD, 0xBC, 0x7C, 0xB4, 0x74, 0x75, 0xB5,
            0x77, 0xB7, 0xB6, 0x76, 0x72, 0xB2, 0xB3, 0x73, 0xB1, 0x71, 0x70, 0xB0, 0x50, 0x90, 0x91,
            0x51, 0x93, 0x53, 0x52, 0x92, 0x96, 0x56, 0x57, 0x97, 0x55, 0x95, 0x94, 0x54, 0x9C, 0x5C,
            0x5D, 0x9D, 0x5F, 0x9F, 0x9E, 0x5E, 0x5A, 0x9A, 0x9B, 0x5B, 0x99, 0x59, 0x58, 0x98, 0x88,
            0x48, 0x49, 0x89, 0x4B, 0x8B, 0x8A, 0x4A, 0x4E, 0x8E, 0x8F, 0x4F, 0x8D, 0x4D, 0x4C, 0x8C,
            0x44, 0x84, 0x85, 0x45, 0x87, 0x47, 0x46, 0x86, 0x82, 0x42, 0x43, 0x83, 0x41, 0x81, 0x80,
            0x40);
        $length = ($length <= 0 ? strlen($string) : $length);
        $uchCRCHi = 0xFF;
        $uchCRCLo = 0xFF;
        $uIndex = 0;
        for ($i = 0; $i < $length; $i++) {
            $uIndex = $uchCRCLo ^ ord(substr($string, $i, 1));
            $uchCRCLo = $uchCRCHi ^ $auchCRCHi[$uIndex];
            $uchCRCHi = $auchCRCLo[$uIndex];
        }
        return ( chr($uchCRCLo).chr($uchCRCHi));
    }
    /**
     * 将数字转换成16进制，长度不够的补0
     * @param $num
     * @param $len
     * @return string
     */
    public function numToHex($num,$len)
    {
        $num_hex = dechex($num);
        if(strlen($num_hex) != $len){
            $add = '';
            for($i=0;$i<$len-strlen($num_hex);$i++){
                $add .= '0';
            }
            $num_hex = $add.$num_hex;
        }
        return $num_hex;
    }
    /**
     * 将十进制数字转换成低位在前的十六进制，长度不够的补0
     * @param $num
     * @param $start  //数字类型
     * @param $end  //待转换数字类型
     * @param $len
     * @return string
     */
    public function numToBinHex($num,$len,$start=10,$end=16)
    {
        $num_hex = base_convert($num,$start,$end);
        if(strlen($num_hex) < $len){
            $add = '';
            for($i=0;$i<$len-strlen($num_hex);$i++){
                $add .= '0';
            }
            $num_hex = $add.$num_hex;
        }
        $arr=[];
        for ($i=0;$i<$len;$i=$i+2){
            $arr[]=$num_hex[$i].$num_hex[$i+1];
        }
        krsort($arr);
        $hex=implode('',$arr);
        return $hex;
    }
    
    
    /**
     * 查询充电桩列表
     * @author:zhubaodi
     * @date_time: 2021/8/26 13:40
     */
    public function getPileList($data)
    {
        //查询设备基本信息
        $service_pile = new HouseNewPileConfig();
        $where = [];
        if (!empty($data['pile_name'])) {
            $where[] = ['pile_name', 'like', '%' . $data['pile_name'] . '%'];
        }

        $pileList = $service_pile->get_list($where, '*', $data['page'], $data['limit']);
        $count = $service_pile->getCount($where);
        $pile_list = [];
        if (!empty($pileList)) {
            $pileList = $pileList->toArray();
            if (!empty($pileList)) {
                foreach ($pileList as $v) {
                    //查询小区名称
                    $service_village = new HouseVillage();
                    $villageInfo = $service_village->getInfo(['village_id' => $v['village_id']], 'village_address,village_name,village_id,property_phone');

                    //查询设备列表
                    $service_equipment = new HouseNewPileEquipment();
                    $equipment_list = $service_equipment->getList(['village_id' => $v['village_id'], 'status' => 1, 'is_del' => 1], '*');
                    if (!empty($equipment_list)) {
                        $equipment_list = $equipment_list->toArray();
                    }
                    //直流设备枪头总数
                    $socket_num_1 = 0;
                    //交流设备枪头总数
                    $socket_num_2 = 0;
                    //直流设备枪头空闲总数
                    $socket_num_1_free = 0;
                    //交流设备枪头空闲总数
                    $socket_num_2_free = 0;
                    //   print_r($equipment_list);die;
                    if (!empty($equipment_list)) {
                        foreach ($equipment_list as $vv) {
                            if (!empty($vv['socket_status'])) {
                                $socket_status = \json_decode($vv['socket_status']);
                                //统计直流设备枪头状态
                                if ($vv['type'] == 1) {
                                    $socket_num_1 = $socket_num_1 + count($socket_status);
                                    for ($i = 0; $i < count($socket_status); $i++) {
                                        if ($socket_status[$i] == 0) {
                                            $socket_num_1_free = $socket_num_1_free + 1;
                                        }
                                    }
                                } else {
                                    $socket_num_2 = $socket_num_2 + count($socket_status);
                                    for ($i = 0; $i < count($socket_status); $i++) {
                                        if ($socket_status[$i] == 0) {
                                            $socket_num_2_free = $socket_num_2_free + 1;
                                        }
                                    }
                                }
                            }

                        }

                    }
                    $param = [
                        'lat' => $v['lat'],
                        'lng' => $v['long']
                    ];
                  //  $address_arr = invoke_cms_model('Area/cityMatching', $param);
                    if (empty($data['lat']) || empty($data['lng'])) {
                        $juli = 0;
                    } else {
                        $juli = getDistance($data['lat'], $data['lng'], $v['lat'], $v['long']);
                    }
                    $juli1 = $juli;
                    if ($juli >= 1000) {
                        $juli = round_number($juli / 1000, 2) . 'km';
                    } else {
                        $juli = $juli . 'm';
                    }
                    $pile_list[] = [
                        'id' => $v['id'],
                        'lat' => $v['lat'],
                        'lng' => $v['long'],
                        'pile_name' => $v['pile_name'],
                        'address' => $villageInfo['village_address'],
                        'distance' => $juli,
                        'juli' => $juli1,
                        'socket_num_1' => $socket_num_1,
                        'socket_num_2' => $socket_num_2,
                        'socket_num_1_free' => $socket_num_1_free,
                        'socket_num_2_free' => $socket_num_2_free,
                        'socket_num_free' => $socket_num_2_free + $socket_num_1_free,
                    ];
                }
                //排序
                if (!empty($data['orderBy'])) {
                    if ($data['orderBy'] == 1) {
                        $last_names = array_column($pile_list, 'juli');
                    } else {
                        $last_names = array_column($pile_list, 'socket_num_free');
                    }
                } else {
                    $last_names = array_column($pile_list, 'juli');
                }
                array_multisort($last_names, SORT_ASC, $pile_list);
            }
        }
        $data1 = [];
        $data1['count'] = $count;
        $data1['list'] = $pile_list;

        return $data1;
    }


    /**
     * 查询站点详情
     * @author:zhubaodi
     * @date_time: 2022/9/22 14:25
     */
    public function getPileInfo($data)
    {
        //查询站点信息
        $service_pile = new HouseNewPileConfig();
        //查询设备信息
        $service_equipment = new HouseNewPileEquipment();
        $pileInfo = $service_pile->getFind(['id' => $data['pile_id']]);
        if (empty($pileInfo)) {
            throw new \think\Exception("站点信息不存在");
        }
        $equipment_list = $service_equipment->getList(['village_id' => $pileInfo['village_id'], 'status' => 1, 'is_del' => 1]);
        //直流设备枪头总数
        $socket_num_1 = 0;
        //交流设备枪头总数
        $socket_num_2 = 0;
        //直流设备枪头空闲总数
        $socket_num_1_free = 0;
        //交流设备枪头空闲总数
        $socket_num_2_free = 0;
        if (!empty($equipment_list)) {
            foreach ($equipment_list as $vv) {
                if (!empty($vv['socket_status'])) {
                    $socket_status = \json_decode($vv['socket_status']);
                    //统计直流设备枪头状态
                    if ($vv['type'] == 1) {
                        $socket_num_1 = $socket_num_1 + count($socket_status);
                        for ($i = 0; $i < count($socket_status); $i++) {
                            if ($socket_status[$i] == 0) {
                                $socket_num_1_free = $socket_num_1_free + 1;
                            }
                        }
                    } else {
                        $socket_num_2 = $socket_num_2 + count($socket_status);
                        for ($i = 0; $i < count($socket_status); $i++) {
                            if ($socket_status[$i] == 0) {
                                $socket_num_2_free = $socket_num_2_free + 1;
                            }
                        }
                    }
                }

            }

        }
        $param = [
            'lat' => $pileInfo['lat'],
            'lng' => $pileInfo['long']
        ];
        //查询小区名称
        $service_village = new HouseVillage();
        $villageInfo = $service_village->getInfo(['village_id' => $pileInfo['village_id']], 'village_address,village_name,village_id,property_phone');

        // $address_arr = invoke_cms_model('Area/cityMatching', $param);
        if (empty($data['lat']) || empty($data['lng'])) {
            $juli = 0;
        } else {
            $juli = getDistance($data['lat'], $data['lng'], $pileInfo['lat'], $pileInfo['long']);
        }
        $juli1 = $juli;
        if ($juli >= 1000) {
            $juli = round_number($juli / 1000, 2) . 'km';
        } else {
            $juli = $juli . 'm';
        }

        $pileInfo['address'] = $villageInfo['village_address'];
        $pileInfo['distance'] = $juli;
        $pileInfo['juli'] = $juli1;
        $pileInfo['socket_num_1'] = $socket_num_1;
        $pileInfo['socket_num_2'] = $socket_num_2;
        $pileInfo['socket_num_1_free'] = $socket_num_1_free;
        $pileInfo['socket_num_2_free'] = $socket_num_2_free;


        // $pile_info=array_merge($pileInfo,$arr);
        return $pileInfo;
    }


    /**
     * 查询收费标准详情
     * @author:zhubaodi
     * @date_time: 2022/9/22 15:04
     */
    public function getPileChargeDetail($data)
    {
        $db_house_new_pile_charge = new HouseNewPileCharge();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $charge_info = $db_house_new_pile_charge->getFind(['village_id' => $data['village_id'], 'rule_id' => $data['charge_id'], 'status' => 1]);
        if (empty($charge_info)) {
            throw new \think\Exception("收费标准信息不存在");
        }
        if (empty($charge_info['rule_id'])) {
            throw new \think\Exception("收费标准信息不存在");
        }
        $rule_info = $db_house_new_charge_rule->getOne(['village_id' => $data['village_id'], 'id' => $charge_info['rule_id']]);
        if (empty($rule_info)) {
            throw new \think\Exception("收费标准信息不存在");
        }
        if ($rule_info['charge_valid_time'] > time()) {
            throw new \think\Exception("收费标准未生效");
        }
        $time_info = [];
        $charge_time = \json_decode($charge_info['charge_time'], true);
        if (!empty($charge_time)) {
            foreach ($charge_time as $vc) {
                $time_start = date('Y-m-d') . ' ' . $vc['time_start'];
                $time_end = date('Y-m-d') . ' ' . $vc['time_end'];
                if (strtotime($time_start) < time() && strtotime($time_end) > time()) {
                    $flag = 1;
                } else {
                    $flag = 0;
                }
                $charge_money = $charge_info['charge_' . ($vc['money']+1)];
                $chargeInfo = 0;
                if (!empty($charge_money)) {
                    $charge_money = \json_decode($charge_money, JSON_UNESCAPED_UNICODE);
                    $chargeInfo = $charge_money['charge_ele'] + $charge_money['charge_serve'];
                    $chargeInfo = round_number($chargeInfo, 2);
                }
                $arr = [];
                $arr['title'] = $vc['time_start'] . '-' . $vc['time_end'];
                $arr['flag'] = $flag;
                $arr['list'][] = [
                    'key' => '电费',
                    'value' => $charge_money['charge_ele']
                ];
                $arr['list'][] = [
                    'key' => '服务费',
                    'value' => $charge_money['charge_serve']
                ];
                $arr['list'][] = [
                    'key' => '总计',
                    'value' => $chargeInfo
                ];
                $time_info[] = $arr;
            }
            if ($charge_info['price_set_value']>-1){
                $arr1 = [];
                $arr1['title'] = '剩余时间区间';
                $charge_money1 = $charge_info['charge_' . ($charge_info['price_set_value']+1)];
                $chargeInfo1 = 0;
                if (!empty($charge_money1)) {
                    $charge_money1 = \json_decode($charge_money1, JSON_UNESCAPED_UNICODE);
                    $chargeInfo1 = $charge_money1['charge_ele'] + $charge_money1['charge_serve'];
                    $chargeInfo1 = round_number($chargeInfo1, 2);
                }
                if (isset($flag)&&$flag==1){
                    $arr1['flag'] = 0;
                }else{
                    $arr1['flag'] = 1;
                }

                $arr1['list'][] = [
                    'key' => '电费',
                    'value' => $charge_money1['charge_ele']
                ];
                $arr1['list'][] = [
                    'key' => '服务费',
                    'value' => $charge_money1['charge_serve']
                ];
                $arr1['list'][] = [
                    'key' => '总计',
                    'value' => $chargeInfo1
                ];
                $time_info[] = $arr1; 
            }

        }


        return $time_info;
    }


    /**
     * 根据类型查询设备列表
     * @author:zhubaodi
     * @date_time: 2022/9/22 15:36
     */
    public function getTypeEquipmentList($data)
    {
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $db_house_new_pile_pay_order = new HouseNewPilePayOrder();
        $db_house_new_pile_charge = new HouseNewPileCharge();
        $db_house_new_pile_equipment_log = new HouseNewPileEquipmentLog();
        $list = $db_house_new_pile_equipment->getList(['village_id' => $data['village_id'], 'type' => $data['type'], 'is_del' => 1, 'status' => 1], true, $data['page'], $data['limit']);
        if (!empty($list)) {
            $list = $list->toArray();
        }
        $count = $db_house_new_pile_equipment->getCount(['village_id' => $data['village_id'], 'type' => $data['type'], 'is_del' => 1, 'status' => 1]);
        $equipment_list = [];
        if (!empty($list)) {
            foreach ($list as $v) {
                $log=$db_house_new_pile_equipment_log->getFind(['equipment_id'=>$v['id']]);
                $chargeInfo = '';
                if (!empty($v['charge_id'])) {
                    $charge_info = $db_house_new_pile_charge->getFind(['id' => $v['charge_id'], 'status' => 1]);
                    if (!empty($charge_info) && !empty($charge_info['charge_time'])) {
                        $charge_time = \json_decode($charge_info['charge_time'], true);
                        if (!empty($charge_time)) {
                            foreach ($charge_time as $vc) {
                                $time_start = date('Y-m-d') . ' ' . $vc['time_start'];
                                $time_end = date('Y-m-d') . ' ' . $vc['time_end'];
                                if (strtotime($time_start) < time() && strtotime($time_end) > time()) {
                                    $charge = $vc['money'];
                                    break;
                                }
                            }
                            if (isset($charge) && !empty($charge)) {
                                $charge_money = $charge_info['charge_' . $charge];
                                if (!empty($charge_money)) {
                                    $charge_money = \json_decode($charge_money, JSON_UNESCAPED_UNICODE);
                                    $chargeInfo = $charge_money['charge_ele'] + $charge_money['charge_serve'];
                                    $chargeInfo = round_number($chargeInfo, 2);
                                    $chargeInfo = $chargeInfo . '元/度';
                                }
                            }else{
                                $charge_money = $charge_info['charge_' . ($charge_info['price_set_value']+1)];
                                if (!empty($charge_money)) {
                                    $charge_money = \json_decode($charge_money, JSON_UNESCAPED_UNICODE);
                                    $chargeInfo = $charge_money['charge_ele'] + $charge_money['charge_serve'];
                                    $chargeInfo = round_number($chargeInfo, 2);
                                    $chargeInfo = $chargeInfo . '元/度';
                                }
                            }

                        }
                    }
                }
                if (empty($v['socket_status'])) {
                    continue;
                }
                $socket_status = \json_decode($v['socket_status']);
                for ($i = 0; $i < count($socket_status); $i++) {
                    $socket_order = $db_house_new_pile_pay_order->getFind(['village_id' => $data['village_id'], 'equipment_id' => $v['id'], 'socket_no' => ($i + 1), 'status' => 2]);
                    if (!empty($socket_order)) {
                        $order_status = 1;
                        $use_ele = $socket_order['use_ele'];
                        $use_time = ceil((time() - $socket_order['pay_time']) / 60) . '分钟';
                    } else {
                        $use_ele = 0;
                        $use_time = '';
                        $order_status = 0;
                    }
                    if ($socket_status[$i] == 0) {
                        $status = 0;
                    }
                    $equipment_list[] = [
                        'id' => $v['id'],
                        'equipment_name' => $v['equipment_name'] . '-0' . ($i + 1),
                        'socket_status' => $status,
                        'equipment_num' => $v['equipment_num'] . '0' . ($i + 1),
                        'socket' => $i + 1,
                        'type' => $v['type'],
                        'power' => $v['power'],
                        'voltage' => isset($log['voltage'])&&!empty($log['voltage'])?$log['voltage']:0,
                        'electric_current' => isset($log['electric_current'])&&!empty($log['electric_current'])?$log['electric_current']:0,
                        'use_ele' => $use_ele,
                        'use_time' => $use_time,
                        'status' => $order_status,
                        'charge_info' => $chargeInfo,
                    ];
                }
            }
        }
        $data1 = [];
        $data1['count'] = $count;
        $data1['list'] = $equipment_list;
        return $data1;

    }

    /**
     * 充电页面设备详情
     * @author:zhubaodi
     * @date_time: 2022/9/23 10:40
     */
    public function getTypeEquipmentDetail($data)
    {
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $db_house_new_pile_pay_order = new HouseNewPilePayOrder();
        $db_house_new_pile_charge = new HouseNewPileCharge();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $db_house_new_pile_user_money = new HouseNewPileUserMoney();
        $db_house_new_pile_equipment_log = new HouseNewPileEquipmentLog();
        $where = [];
        $where['is_del'] = 1;
        if (!empty($data['id'])) {
            $where['id'] = $data['id'];
        }
        if (!empty($data['village_id'])) {
            $where['village_id'] = $data['village_id'];
        }
        if (!empty($data['equipment_num'])) {
            $where['equipment_num'] = substr($data['equipment_num'], 0, -2);
            $data['socket'] = substr($data['equipment_num'], -2);
        }
        //查询设备信息
        $info = $db_house_new_pile_equipment->getInfo($where);

        if (empty($info)) {
            throw new \think\Exception("设备信息不存在");
        }
        if (empty($info['charge_id'])) {
            throw new \think\Exception("设备未绑定收费标准，无法充电");
        }
        if ($info['status'] != 1) {
            throw new \think\Exception("设备已离线，无法充电");
        }
        if (empty($info['socket_status'])) {
            throw new \think\Exception("设备枪头信息不存在，无法充电");
        }
        if (empty($data['id'])) {
            $data['id'] = $info['id'];
        }
        if (empty($data['village_id'])) {
            $data['village_id'] = $info['village_id'];
        }
        //查询订单信息
        $socket_order = $db_house_new_pile_pay_order->getFind(['village_id' => $info['village_id'], 'equipment_id' => $data['id'], 'socket_no' => $data['socket'], 'status' => 2]);
        if (!empty($socket_order) && !$socket_order->isEmpty()) {
            $socket_order=$socket_order->toArray();
            if(isset($data['return_charge_err']) && $data['return_charge_err']==1 && $socket_order['uid']==$data['uid']){
                $socket_order['order_id']=$socket_order['id'];
                return array('err_code'=>1,'err_msg'=>"设备枪头充电中，无法充电",'orderdata'=>$socket_order);
            }else{
                throw new \think\Exception("设备枪头充电中，无法充电");
            }
        }

        //查询收费标准
        $charge_info = $db_house_new_pile_charge->getFind(['village_id' => $info['village_id'], 'rule_id' => $info['charge_id'], 'status' => 1]);
        if (empty($charge_info)) {
            throw new \think\Exception("收费标准信息不存在");
        }
        if (empty($charge_info['rule_id'])) {
            throw new \think\Exception("收费标准信息不存在");
        }
        $rule_info = $db_house_new_charge_rule->getOne(['village_id' => $info['village_id'], 'id' => $charge_info['rule_id']]);
        if (empty($rule_info)) {
            throw new \think\Exception("收费标准信息不存在");
        }
        if ($rule_info['charge_valid_time'] > time()) {
            throw new \think\Exception("收费标准未生效");
        }
        $time_info = [];
        $time_info['charge_id'] = $info['charge_id'];
        $charge_time = \json_decode($charge_info['charge_time'], true);
        if (!empty($charge_time)) {
            foreach ($charge_time as $vc) {
                $time_start = date('Y-m-d') . ' ' . $vc['time_start'];
                $time_end = date('Y-m-d') . ' ' . $vc['time_end'];
                if (strtotime($time_start) < time() && strtotime($time_end) > time()) {
                    $time_info['time'] = $vc['time_start'] . '-' . $vc['time_end'];
                    $charge = $vc['money'];
                    break;
                }
            }
            
            if (isset($charge)) {
                $charge_money = $charge_info['charge_' . ($charge+1)];
                
            }elseif($charge_info['price_set_value']!='-1'){
                $charge_money = $charge_info['charge_' . ($charge_info['price_set_value']+1)]; 
            }
            if (isset($charge_money)&&!empty($charge_money)) {
                $charge_money = \json_decode($charge_money, JSON_UNESCAPED_UNICODE);
                $time_info['money'] = $charge_money['charge_ele'] + $charge_money['charge_serve'];
                $time_info['money'] = round_number($time_info['money'], 2);
            }

        }
        $log=$db_house_new_pile_equipment_log->getFind(['equipment_id'=>$info['id']]);

        //查询用户余额信息
        $user_money_info = $db_house_new_pile_user_money->getOne(['business_type' => 1, 'business_id' => $info['village_id'], 'uid' => $data['uid']]);
        if (empty($user_money_info)) {
            $user_money = 0;
        } else {
            $user_money = $user_money_info['current_money'];
        }

        //查询最近一次充电的车牌号
        $order_info = $db_house_new_pile_pay_order->getFind(['village_id' => $info['village_id'], 'uid' => $data['uid']]);
        if (empty($order_info)) {
            $car_number = '';
        } else {
            $car_number = $order_info['car_number'];
        }

        if ($info['type']==1){
            if (strlen($data['socket'])==1){
                $data['socket']='0'.$data['socket'];
            }
            $equipment_info = [
                'id' => $data['id'],
                'equipment_name' => $info['equipment_name'] .'-' .$data['socket'],
                'equipment_num' => $info['equipment_num'] . $data['socket'],
                'socket' => $data['socket'],
                'type' => $info['type'],
                'power' => $info['power'],
                'voltage' => isset($log['voltage'])&&!empty($log['voltage'])?$log['voltage']:0,
                'electric_current' => isset($log['electric_current'])&&!empty($log['electric_current'])?$log['electric_current']:0,
            ];
        }else{
            $equipment_info = [
                'id' => $data['id'],
                'equipment_name' => $info['equipment_name'] . '-0' . $data['socket'],
                'equipment_num' => $info['equipment_num'] . '0' . $data['socket'],
                'socket' => $data['socket'],
                'type' => $info['type'],
                'power' => $info['power'],
                'voltage' => isset($log['voltage'])&&!empty($log['voltage'])?$log['voltage']:0,
                'electric_current' => isset($log['electric_current'])&&!empty($log['electric_current'])?$log['electric_current']:0,
            ];
        }
        

        $data1 = [];
        $data1['equipment_info'] = $equipment_info;
        $data1['charge_info'] = $time_info;
        $data1['car_number'] = $car_number;
        $data1['user_money'] = $user_money;
        $data1['village_id'] = $info['village_id'];

        return $data1;
    }


    /**
     * 查询用户订单列表
     * @author:zhubaodi
     * @date_time: 2022/9/23 13:59
     */
    public function getUserOrderList($data)
    {
        $db_house_new_pile_config = new HouseNewPileConfig();
        $db_house_new_pile_pay_order = new HouseNewPilePayOrder();
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $where = [];
        $where[] = ['uid', '=', $data['uid']];
        if (!empty($data['status'])) {
            $where[] = ['status', '=', $data['status']];
        } else {
            $where[] = ['status', '=', 0];
        }
        $list = $db_house_new_pile_pay_order->get_list($where, '*', $data['page'], $data['limit']);
        $count = $db_house_new_pile_pay_order->getCount($where);
        if (!empty($list)) {
            $list = $list->toArray();
        }
        $order_list = [];
        if (!empty($list)) {
            foreach ($list as $v) {
                $arr=[];
                $equipment_info = $db_house_new_pile_equipment->getInfo(['id' => $v['equipment_id']]);
                if (empty($equipment_info)) {
                    continue;
                }
                $pile_config = $db_house_new_pile_config->getFind(['village_id' => $v['village_id']]);
                $arr['list'][] = [
                    'key' => '枪头编号',
                    'value' => $equipment_info['equiment_num'] . '0' . $v['socket_no']
                ];
                $arr['list'][] = [
                    'key' => '开始时间',
                    'value' =>$v['pay_time']>100?date('Y-m-d H:i:s', $v['pay_time']):'未开始'
                ];
                if ($v['status'] == 2) {

                    $arr['list'][] = [
                        'key' => '结束时间',
                        'value' => '--'
                    ];
                    $arr['btn_list'][] = [
                        'key' => 'is_charging',
                        'name' => '充电中'
                    ];

                } elseif ($v['status'] == 1) {
                    $arr['list'][] = [
                        'key' => '结束时间',
                        'value' => date('Y-m-d H:i:s', $v['end_time'])
                    ];
                    $arr['btn_list'] = [];
                }
                $arr['list'][] = [
                    'key' => '已充电量',
                    'value' => $v['use_ele']
                ];
                $arr['list'][] = [
                    'key' => '消费金额',
                    'value' => $v['use_money']
                ];
                $arr['pile_name'] = $pile_config['pile_name'];
                $arr['status'] = $v['status'];
                $arr['order_id'] = $v['id'];

                $order_list[] = $arr;
            }
        }

        $data1 = [];
        $data1['list'] = $order_list;
        $data1['count'] = $count;
        return $data1;
    }


    /**
     * 查询用户订单详情
     * @author:zhubaodi
     * @date_time: 2022/9/14 20:51
     */
    public function getUserOrderDetail($data)
    {
        $db_house_new_pile_pay_order = new HouseNewPilePayOrder();
        
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $db_house_new_pile_equipment_log = new HouseNewPileEquipmentLog();
        $db_house_new_pile_charge = new HouseNewPileCharge();
        $db_house_new_pile_pay_order_log = new HouseNewPilePayOrderLog();
        $where = [];
        $where = ['uid' => $data['uid'], 'id' => $data['order_id']];
        $order_info = $db_house_new_pile_pay_order->getFind($where);
        if (empty($order_info)) {
            throw new \think\Exception("订单不存在！");
        }
        $equipment_info = $db_house_new_pile_equipment->getInfo(['id' => $order_info['equipment_id']]);
        if (empty($equipment_info)) {
            throw new \think\Exception("设备信息不存在！");
        }
        $arr['equipment'][] = [
            'key' => '设备名称',
            'value' => $equipment_info['equipment_name'] . '-0' . $order_info['socket_no'],
        ];
        $arr['equipment'][] = [
            'key' => '设备编号',
            'value' => $equipment_info['equipment_num'] . '0' . $order_info['socket_no'],
        ];
        $arr['equipment'][] = [
            'key' => '设备类型',
            'value' => $equipment_info['type'] == 1 ? '直流' : '交流',
        ];
        $arr['flag']=1;
        if ($order_info['status'] == 2) {
            if ($data['type']=='over'){
                $arr['flag']=0;
            }
            $log_info=$db_house_new_pile_equipment_log->getFind(['equipment_id'=>$order_info['equipment_id']]);
            $arr['equipment'][] = [
                'key' => '实时电压',
                'value' => isset($log_info['voltage'])&&!empty($log_info['voltage'])?$log_info['voltage'].'V':0 .'V',
            ];
            $arr['equipment'][] = [
                'key' => '实时电流',
                'value' => isset($log_info['electric_current'])&&!empty($log_info['electric_current'])?$log_info['electric_current'].'A':0 .'A',
            ];
            $arr['equipment'][] = [
                'key' => '实时温度',
                'value' =>isset($log_info['temperature'])&&!empty($log_info['temperature'])?$log_info['temperature'].'℃':0 .'℃',
            ];
            if ($order_info['type']==2){
                $arr['equipment'][] = [
                    'key' => '实时功率',
                    'value' =>isset($order_info['end_power'])&&!empty($order_info['end_power'])?$order_info['end_power'].'W':0 .'W',
                ];  
            }else{
                $voltage=isset($log_info['voltage'])&&!empty($log_info['voltage'])?$log_info['voltage']:0;
                $electric_current=isset($log_info['electric_current'])&&!empty($log_info['electric_current'])?$log_info['electric_current']:0;
                $power=$voltage*$electric_current;
                if ($power>1000){
                    $power=round_number(($power/1000),3).'KW'; 
                }else{
                    $power=$power.'W';
                }
                $arr['equipment'][] = [
                    'key' => '实时功率',
                    'value' =>$power,
                ];
            }
            $arr['order_arr']=[];
            $arr['pile'][] = [
                'key' => '充电时长',
                'value' => round_number((time() - $order_info['pay_time']) / 60, 2).'分钟',
            ];
            $arr['pile'][] = [
                'key' => '充电电量',
                'value' => $order_info['use_ele'].'度',
            ];
            $arr['pile'][] = [
                'key' => '充电金额',
                'value' => $order_info['use_money'].'元',
            ];
        } elseif ($order_info['status'] == 1) {
           //  print_r($order_info);die;
           
            $arr['title'] = date('Y-m-d H:i', $order_info['pay_time']) . '-' . date('Y-m-d H:i', $order_info['end_time']);
            $arr['time']=[];
            //查询收费标准
            $charge_info = $db_house_new_pile_charge->getFind(['village_id' => $order_info['village_id'], 'rule_id' => $order_info['charge_id'], 'status' => 1]);
            $log_list=$db_house_new_pile_pay_order_log->get_list(['order_id'=>$order_info['id']],true,0,0,'id ASC');
            if (!empty($log_list)){
                $log_list=$log_list->toArray();
            }
            $sum_money=0;
            $sum_ele=0;
            if (!empty($log_list)&&!empty($charge_info)){
                $charge_time = \json_decode($charge_info['charge_time'], true);
                foreach ($log_list as $k=>&$vv) {
                    $chargeMoney = [];
                    foreach ($charge_time as $kk => $vc) {
                        $time_end = strtotime(date('Y-m-d', $vv['add_time']) . ' ' . $vc['time_end']);
                        $time_start = strtotime(date('Y-m-d', $vv['add_time']) . ' ' . $vc['time_start']);
                        $data_log = '';
                        if ($time_end >= $vv['add_time'] && $time_start <= $vv['add_time']) {
                            $data_log = $vc['money'];
                            $vv['index'] = $kk;
                            $vv['time_start'] = $vc['time_start'];
                            $vv['time_end'] = $vc['time_end'];
                            break;
                        }
                    }
                    if (empty($data_log) && $charge_info['price_set_value'] != '-1') {
                        $data_log = $charge_info['price_set_value'];
                        $vv['index'] = '-1';
                    }
                    if (isset($data_log) && !empty($data_log)) {
                        $charge_money = $charge_info['charge_' . ($data_log + 1)];
                        if (!empty($charge_money)) {
                            $chargeMoney = \json_decode($charge_money, JSON_UNESCAPED_UNICODE);
                        }
                    }
                    if (!isset($chargeMoney)) {
                        $vv['charge_ele'] = 0;
                        $vv['charge_serve'] = 0;
                    } else {
                        $vv['charge_ele'] = $chargeMoney['charge_ele'];
                        $vv['charge_serve'] = $chargeMoney['charge_serve'];
                    }
                }
                
                foreach ($log_list as $k1=>$vv1)
                {
                    $arr1 = [];
                    if ($k1==0){
                        $use_ele=$log_list[0]['use_ele'];
                        $use_money=$log_list[0]['use_money'];
                        continue;
                    }
                    if ($vv1['index']==$log_list[$k1-1]['index']){
                        if ($vv1['type']==1){
                            $use_ele=$vv1['use_ele'];
                            $use_money=$vv1['use_money'];  
                        }else{
                            $use_ele=$vv1['use_ele']+ $use_ele;
                            $use_money=$vv1['use_money']+ $use_money;
                        }
                        
                    }else{
                        $use_ele=$vv1['use_ele'];
                        $use_money=$vv1['use_money'];
                    }
                    if (!isset($log_list[$k1+1])){
                        $vv1['time_end']=date('H:i',$order_info['end_time']);
                    }
                    if ((isset($log_list[$k1+1])&&$vv1['index']!=$log_list[$k1+1]['index'])||(!isset($log_list[$k1+1]))){
                        if ($vv1['index']==$log_list[0]['index']){
                            $arr1['title']=date('H:i',$order_info['pay_time']).'-'.$vv1['time_end'];
                        }else{
                            $arr1['title']=$vv1['time_start'].'-'.$vv1['time_end']; 
                        }
                        $arr1['list'][] = [
                            'key' => '电费',
                            'value' => $vv1['charge_ele'].'元/度',
                        ];
                        $arr1['list'][] = [
                            'key' => '服务费',
                            'value' => $vv1['charge_serve'].'元/度',
                        ];
                        $arr1['list'][] = [
                            'key' => '电量',
                            'value' => $use_ele.'度',
                        ];
                        $arr1['list'][] = [
                            'key' => '费用',
                            'value' => $use_money.'元',
                        ];
                        $sum_money=$use_money+$sum_money;
                        $sum_ele=$sum_ele+$use_ele;
                        $arr['time'][] = $arr1;
                    }
                }
            }
            $order_arr=[round_number($sum_ele,2),round_number($sum_money,2)];
            $arr['order_arr']=$order_arr;
        }else{
            $arr['flag']=0;
        }
        
        $arr['order'][] = [
            'key' => '订单编号',
            'value' => $order_info['order_no'],
        ];
        $arr['order'][] = [
            'key' => '支付金额',
            'value' => $order_info['use_money'],
        ];
        $arr['order'][] = [
            'key' => '支付方式',
            'value' => '余额支付',
        ];
        $arr['order'][] = [
            'key' => '结束原因',
            'value' => empty($order_info['end_result'])?'--':$order_info['end_result'],
        ];
        $arr['order'][] = [
            
            'key' => '退款金额',
            'value' => '--',
        ];
        $arr['order'][] = [
            'key' => '退款时间',
            'value' => '--',
        ];

        return $arr;
    }

    /**
     * 结束充电
     * @author:zhubaodi
     * @date_time: 2022/9/24 16:18
     */
    public function stopCharge($data)
    {
        $db_house_new_pile_pay_order = new HouseNewPilePayOrder();
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $db_house_new_pile_command = new HouseNewPileCommand();

        $order_info = $db_house_new_pile_pay_order->getFind(['id' => $data['order_id'], 'uid' => $data['uid']]);
        if (empty($order_info)) {
            throw new \think\Exception("订单信息不存在！");
        }
        $equipment_info = $db_house_new_pile_equipment->getInfo(['id' => $order_info['equipment_id']]);
        if (empty($equipment_info)) {
            throw new \think\Exception("设备信息不存在！");
        }
        // 写入指令表
        $arr = [];
        if ($equipment_info['type']==1){
            $str=$equipment_info['equipment_num'].'0'.$order_info['socket_no'];
            $len=$this->numToHex($data['order_id'],3).'10036'.$str;
            $checkResult=$this->wordStr($len);
            $length = strlen($len)/2; // 长度
            $length = $this->numToHex($length,2);
            $content='68'.$length.$len.$checkResult;
        }else{
            $key='qccdz:'.$equipment_info['equipment_num'].'5bccc1';
            $cmd='0001000122020000';
            $crc=$this->wordStr($cmd);
            $content=$this->opensslEncrypt($cmd.$crc,$key);
        }
        $command = [
            'sn' => $equipment_info['equipment_num'],
            'cmd' => $content,
            'desc'=>'stopCharge',
        ];
        $arr['command'] = \json_encode($command);
        $arr['type'] = 1;
        $arr['status'] = 1;
        $arr['addtime'] = time();
        $arr['device_type'] = $equipment_info['type'];
        $arr['equipment_id'] = $equipment_info['id'];
        if ($equipment_info['equipment_num']=='89860493192080129852'){
            fdump_api([$data,$cmd,$equipment_info],'command_mqtt_0104',1);
        }
        $id = $db_house_new_pile_command->addOne($arr);
        return $id;
    }

    /**
     * 开始充电
     * @author:zhubaodi
     * @date_time: 2022/9/26 11:37
     */
    public function payOrder($data)
    {
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $db_house_new_pile_pay_order = new HouseNewPilePayOrder();
        $db_hoouse_new_pile_config = new HouseNewPileConfig();
        $db_house_new_pile_command = new HouseNewPileCommand();
        $db_house_new_pile_charge = new HouseNewPileCharge();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $db_house_new_pile_user_money = new HouseNewPileUserMoney();
        $equipment_info = $db_house_new_pile_equipment->getInfo(['id' => $data['id'], 'status' => 1]);
        $random_number = createRandomStr(4, true);
        if (empty($equipment_info)) {
            throw new \think\Exception("设备不存在！");
        }
        if ($equipment_info['status'] == 3) {
            throw new \think\Exception("设备已离线，无法充电！");
        }
        if ($equipment_info['status'] == 4) {
            throw new \think\Exception("设备故障，无法充电！");
        }
        $config_info = $db_hoouse_new_pile_config->getFind(['village_id' => $equipment_info['village_id']]);
        if (empty($config_info)) {
            throw new \think\Exception("站点信息不存在，无法充电！");
        }
        $order_info = $db_house_new_pile_pay_order->getFind(['equipment_id' => $equipment_info['id'], 'socket_no' => $data['socket_no']]);
        if (!empty($order_info) && $order_info['status'] == 2) {
            throw new \think\Exception("当前设备枪头正在充电中，无法开启充电！");
        }
        if (!empty($order_info) && $order_info['status'] == 0 && $data['uid'] == $order_info['uid'] && $order_info['add_time'] > time()-60) {
            throw new \think\Exception("您的订单已经生成请不要重复进行操作，如需再次下单请1分钟后再进行下单！");
        } elseif (!empty($order_info) && $order_info['status'] == 0 && $order_info['add_time'] > time()-60) {
            fdump_api([$order_info, $data], '$payOrder',1);
            throw new \think\Exception("当前设备枪头正在等候充电中，无法开启充电！");
        }
        $user_money = $db_house_new_pile_user_money->getOne(['business_id' => $equipment_info['village_id'], 'uid' => $data['uid']]);
        if (empty($user_money)) {
            throw new \think\Exception("当前账户余额不足，无法充电！");
        }
        if ($user_money['current_money'] < $config_info['min_money']) {
            throw new \think\Exception("当前账户余额小于最低充电金额，无法充电！");
        }
        // 写入订单表
        $order_data = [
            'village_id' => $equipment_info['village_id'],
            'equipment_id' => $equipment_info['id'],
            'uid' => $data['uid'],
            'socket_no' => $data['socket_no'],
            'type' => $equipment_info['type'],
            'order_no' => build_real_orderid($data['uid']),
            'car_number' => $data['car_number'],
            'charge_id' => $equipment_info['charge_id'],
            'add_time' => time(),
        ];
        if ($equipment_info['type']==1){
           $order_data['order_serial'] =$equipment_info['equipment_num'].$data['socket_no'].date('ymdHis').rand(1000,9999);
        }else{
            $order_data['order_serial'] =date('ymdHis').rand(1000,9999);
        }
        if ($random_number) {
            $order_data['random_number'] = $random_number;
        }
        $order_id = $db_house_new_pile_pay_order->addOne($order_data);
        if (empty($order_id)) {
            throw new \think\Exception("订单生成失败，无法充电！");
        }

        // 写入指令表
        $arr = [];
        if ($equipment_info['type']==1){
            $len=16;
            $pile_card_no=$user_money['pile_card_no'];
            $card_no=$user_money['card_no'];
            if(strlen($user_money['pile_card_no']) != $len){
                $add = '';
                for($i=0;$i<$len-strlen($user_money['pile_card_no']);$i++){
                    $add .= '0';
                }
                $pile_card_no = $add.$user_money['pile_card_no'];
            }
            if(strlen($user_money['card_no']) != $len){
                $add = '';
                for($i=0;$i<$len-strlen($user_money['card_no']);$i++){
                    $add .= '0';
                }
                $card_no = $add.$user_money['card_no'];
            }
           
            $str=$order_data['order_serial'].$equipment_info['equipment_num'].$data['socket_no'].$card_no.$pile_card_no.$this->numToBinHex($user_money['current_money']*100,8);
            $len=$this->numToHex($order_id,4).'0034'.$str;
            $checkResult=$this->wordStr($len);
            $length = strlen($len)/2; // 长度
            $length = dechex($length);
            $content='68'.$length.$len.$checkResult;
        }else{
            $key='qccdz:'.$equipment_info['equipment_num'].'5bccc1';
//            if ($random_number && strlen($random_number) ==  4) {
//                $cmd='0001'.$random_number.'2102000C'.$order_data['order_serial'].'0100000000';
//            } else {
//                $cmd='000100012102000C'.$order_data['order_serial'].'0100000000';
//            }
            $cmd='000100012102000C'.$order_data['order_serial'].'0100000000';
            $crc=$this->wordStr($cmd);
            $content=$this->opensslEncrypt($cmd.$crc,$key);
            fdump_api(['msg' => '开电', 'cmd' => $cmd, 'random_number' => $random_number, 'order_data' => $order_data, 'order_id' => $order_id, 'content' => $content], 'pileJiaoLiu/openCmdLog' ,1);
        }
        $command = [
            'sn' => $equipment_info['equipment_num'],
            'cmd' => $content,
            'desc'=>'chargeOrder',
        ];
        $arr['command'] = \json_encode($command);
        $arr['type'] = 1;
        $arr['status'] = 1;
        $arr['addtime'] = time();
        $arr['device_type'] = $equipment_info['type'];
        $arr['equipment_id'] = $equipment_info['id'];
        $id = $db_house_new_pile_command->addOne($arr);
        return $order_id;
    }

    /**
     * 账户余额充值
     * @author:zhubaodi
     * @date_time: 2022/10/18 15:53
     */
    public function addRecharge($data)
    {
        $db_house_village = new HouseVillage();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $db_plat_order = new PlatOrder();
        $service_user = new User();
        $db_house_new_pile_config=new HouseNewPileConfig();
        $user_info = $service_user->getOne(['uid' => $data['uid']]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }
        $pile_config=$db_house_new_pile_config->getFind(['village_id'=>$data['village_id']]);
        if (!empty($pile_config)&&$pile_config['min_money']>$data['price']){
            throw new \think\Exception("充值金额不能小于".$pile_config['min_money'].'元！');
        }
        $plat_order_result = 0;
        $now_village = $db_house_village->getOne($data['village_id']);
        $postData = [];
        $postData['total_money'] = $data['price'];
        $postData['modify_money'] = $data['price'];
        $postData['unit_price'] = $data['price'];
        $postData['uid'] = $data['uid'];
        $postData['pay_bind_name'] = $user_info['name'] ? $user_info['name'] : '';
        $postData['pay_bind_phone'] = $user_info['phone'] ? $user_info['phone'] : '';
        $postData['order_type'] = 'pile';
        $postData['order_name'] = '汽车充电收费';
        $postData['village_id'] = $data['village_id'];
        $postData['is_paid'] = 2;
        $postData['is_prepare'] = 2;
        $postData['pay_bind_id'] = 0;
        $postData['pigcms_id'] = 0;
        $postData['position_id'] = 0;
        $postData['property_id'] = $now_village['property_id'];
        $postData['order_no'] = '';
        $postData['add_time'] = time();
        $postData['from'] = 0;
        if (!empty($data['equipment_num'])){
            $postData['car_number']=$data['equipment_num'];
        }
        $order_id = $db_house_new_pay_order->addOne($postData);
        if ($order_id > 0) {
            $summaryData = [];
            $summaryData['uid'] = isset($postData['uid']) ? $postData['uid'] : 0;
            $summaryData['pay_uid'] = isset($postData['uid']) ? $postData['uid'] : 0;
            $summaryData['order_no'] = build_real_orderid($summaryData['uid']);
            $summaryData['total_money'] = $data['price'];
            $summaryData['is_paid'] = 2;
            $summaryData['pay_type'] = 4;
            $summaryData['is_online'] = 1;
            $summaryData['village_id'] = $data['village_id'];
            $summaryData['property_id'] = $now_village['property_id'];
            $summaryData['add_time'] = time();
            $summary_order_id = $db_house_new_pay_order_summary->addOne($summaryData);
            if ($summary_order_id) {
                $db_house_new_pay_order->saveOne(['order_id' => $order_id], ['summary_id' => $summary_order_id, 'pay_type' => 4]);
            } else {
                throw new \think\Exception("订单添加失败！");
            }
            $pay_order_param = array(
                'business_type' => 'village_new_pay',
                'business_id' => $summary_order_id,
                'order_name' => $postData['order_name'],
                'uid' => $data['uid'],
                'total_money' => $data['price'],
                'wx_cheap' => 0,
                'add_time' => time(),
            );
            if (cfg('open_village_sub_mchid') && $now_village['sub_mch_id']) {
                $pay_order_param['is_own'] = 5;
            } else {
                $pay_order_param['is_own'] = 4;
            }
            $plat_order_result = $db_plat_order->add_order($pay_order_param);
            if ($plat_order_result<1) {
                throw new \think\Exception("订单添加失败！");
            }
        }
        return $plat_order_result;
    }


    /**
     * 充值/消费明细列表
     * @author:zhubaodi
     * @date_time: 2022/10/17 10:46
     */
    public function getUserMoneyLogList($data){
       $db_house_new_pile_user_money_log=new HouseNewPileUserMoneyLog();
       $where=[
           'uid'=>$data['uid'],
           'business_type'=>1,
           'business_id'=>$data['village_id'],
       ];
       $list=$db_house_new_pile_user_money_log->getList($where,true,$data['page'],$data['limit'], 'add_time DESC, id DESC');

       $count=$db_house_new_pile_user_money_log->getCount($where);
       if (!empty($list)){
           $list=$list->toArray();
       }
       $dataList=[];
       if (!empty($list)){
           foreach ($list as $v){
               $arr=[];
               $arr['desc']=$v['desc'];
               $arr['order_id']=$v['order_id'];
               $arr['add_time']=date('Y-m-d H:i:s',$v['add_time']);
               if ($v['type']==1){
                   $arr['money']='+'.abs($v['money']);
               }else{
                   $arr['money']='-'.abs($v['money']);
               }
               $dataList[]=$arr;
           }
       }
       $data1=[];
       $data1['list']=$dataList;
       $data1['count']=$count;
       $data1['total']=$data['limit'];
       return $data1;
    }

    /**
     * 查询实体卡信息
     * @author:zhubaodi
     * @date_time: 2022/10/18 16:27
     */
    public function getCardInfo($data){
        $db_house_new_pile_withdraw=new HouseNewPileWithdraw();
        $where1=[
            'uid'=>$data['uid'],
            'village_id'=>$data['village_id'],
            'status'=>2,
        ];
        $field='id,uid,sum(refund_money) as total_refund_money,status';
        $withdraw_info=$db_house_new_pile_withdraw->getFind($where1,$field);
        if (!empty($withdraw_info)){
            $withdraw['money']=$withdraw_info['total_refund_money'];
            $withdraw['status']='提现中';
        }else{
            $withdraw=[];
        }
        $db_house_new_pile_user_money=new HouseNewPileUserMoney();
        $where=[
            'uid'=>$data['uid'],
            'business_id'=>$data['village_id'],
        ];
        $userMoneyInfo=$db_house_new_pile_user_money->getOne($where);
        if (empty($userMoneyInfo)){
          return [
                'current_money'=>'0',
                'withdraw'=>['money'=>'0', 'status'=> '']];
        }
        $userMoneyInfo['withdraw']=$withdraw;
        return $userMoneyInfo;
    }

    /**
     * 绑定实体卡
     * @author:zhubaodi
     * @date_time: 2022/10/17 10:49
     */
    public function bindCard($data){
        $db_house_new_pile_user_money=new HouseNewPileUserMoney();
        $where=[
            'uid'=>$data['uid'],
            'business_id'=>$data['village_id'],
        ]; 
        $userMoneyInfo=$db_house_new_pile_user_money->getOne($where);
        if (empty($userMoneyInfo)){
            $arr=[
                'uid'=>$data['uid'],
                'business_id'=>$data['village_id'],
                'pile_card_no'=>$data['pile_card_no'],
                'card_no'=>$data['card_no'],
                'add_time'=>time(),
                'update_time'=>time(),
            ];
            $res=$db_house_new_pile_user_money->addOne($arr);
        }else{
            $res=$db_house_new_pile_user_money->saveOne($where,['pile_card_no'=>$data['pile_card_no'],'card_no'=>$data['card_no']]);
        }
        return $res;
    }

    /**
     * 解绑实体卡
     * @author:zhubaodi
     * @date_time: 2022/10/17 11:19
     */
    public function unBindCard($data){
        $db_house_new_pile_user_money=new HouseNewPileUserMoney();
        $where=[
            'uid'=>$data['uid'],
            'business_id'=>$data['village_id'],
        ];
        $userMoneyInfo=$db_house_new_pile_user_money->getOne($where);
        if (!empty($userMoneyInfo)&&!empty($userMoneyInfo['card_no'])){
            $res=$db_house_new_pile_user_money->saveOne($where,['pile_card_no'=>'','card_no'=>'']);
        } else{
            throw new \think\Exception("卡信息不存在！");
        }
        return $res;
    }

    /**
     * 用户申请余额提现
     * @author:zhubaodi
     * @date_time: 2022/10/17 11:20
     */
    public function userWithdraw($data){
        $db_house_new_pile_user_money=new HouseNewPileUserMoney();
        $db_house_new_pile_withdraw=new HouseNewPileWithdraw();
        $where=[
            'uid'=>$data['uid'],
            'business_id'=>$data['village_id'],
        ];
        $userMoneyInfo=$db_house_new_pile_user_money->getOne($where);
        if (empty($userMoneyInfo)){
            throw new \think\Exception("账户余额信息不存在，无法退款！");
        }
        if($data['current_money']>$userMoneyInfo['current_money']){
            throw new \think\Exception("当前账户余额不足，无法退款！");
        }
        $where1=[
            'uid'=>$data['uid'],
            'village_id'=>$data['village_id'],
            'status'=>2,
        ];
        $field='id,uid,sum(refund_money) as total_refund_money,status';
        $withdraw_info=$db_house_new_pile_withdraw->getFind($where1,$field);
        if($withdraw_info && isset($withdraw_info['total_refund_money'])){
            $tmp_money=$withdraw_info['total_refund_money']+$data['current_money'];
            $tmp_money=round_number($tmp_money);
            if($tmp_money>$userMoneyInfo['current_money']){
                throw new \think\Exception("您已经有金额为".$withdraw_info['total_refund_money'].'元的提现在审核中,请耐性等待！');
            }
        }
        $arr=[];
        $arr['uid']=$data['uid'];
        $arr['village_id']=$data['village_id'];
        $arr['status']=2;
        $arr['refundType']=$data['type'];
        $arr['refund_money']=$data['current_money'];
        $arr['add_time']=time();
        $arr['refund_reason']='用户操作账户余额提现';
        if(isset($data['true_name']) && !empty($data['true_name'])){
            $arr['true_name']=$data['true_name'];
        }
        $res=$db_house_new_pile_withdraw->addOne($arr);
        return $res;
    }

    //----------------------用户端功能接口end----------------//

    
    
    public function  command_mqtt($data){
        $db_house_new_pile_pay_order=new HouseNewPilePayOrder();
        $db_house_new_pile_pay_order_log=new HouseNewPilePayOrderLog();
        $db_house_new_pile_charge = new HouseNewPileCharge();
        $db_house_new_pile_config = new HouseNewPileConfig();
        $db_house_new_pile_command = new HouseNewPileCommand();
        $db_house_new_pile_equipment_log=new HouseNewPileEquipmentLog();
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $db_house_new_pile_user_money_log=new HouseNewPileUserMoneyLog();
        $db_house_new_pile_user_money=new HouseNewPileUserMoney();
        $service_user = new UserService();
        if (empty($data['cmd'])||empty($data['sn'])){
            return false;
        }
        $dayH = date('H');
        $str= $data['cmd'];
        $key='qccdz:'.$data['sn'].'5bccc1';
        $cmd = $this->opensslDecrypt($str,$key);
        fdump_api(['data' => $data, 'str' => $str, 'key' => $key, 'cmd' => $cmd], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
        if (empty($cmd)){
            fdump_api(['cmd' => $cmd, 'msg' => '指令解析失败', 'data' => $data], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            return false;
        }
        $equipment_info=$db_house_new_pile_equipment->getInfo(['equipment_num'=>$data['sn'],'is_del'=>1]);
        if (empty($equipment_info)){
            fdump_api(['cmd' => $cmd, 'msg' => '对应设备不存在', 'equipment_info' => $equipment_info], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            return false;
        }
        fdump_api(['cmd' => $cmd, 'equipment_info' => $equipment_info->toArray()], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
        $function_code=substr($cmd,8,2);
        $content=substr($cmd,16,-2);
        fdump_api(['cmd' => $cmd, 'function_code' => $function_code, 'content' => $content], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
        $serviceHouseVillage = new HouseVillageService();
        $village_info=$serviceHouseVillage->getHouseVillageInfoExtend(['village_id'=>$equipment_info['village_id']],'urge_notice_type');
        $pile_config=$db_house_new_pile_config->getFind(['village_id'=>$equipment_info['village_id']]);
        if ($function_code=='21'){
            $serial_no  = substr($content, 2, 16);//订单号
            $pileModel  = substr($content, 18, 2);// 充电模式 1 充满为止 2 充固定瓦数（目前固定为 1 充满为止）
            $pileCharge = substr($content, 20, 8);// 充电量   瓦（目前私人桩统一采用充满为止，电量用 0000 代替）
            $pileStatus = substr($content, 28, 2);// 状态     1 充电成功，0 充电失败
            $pileResult = substr($content, 30, 2);// 失败原因
            //开启充电回复
            $order_info = $db_house_new_pile_pay_order->getFind(['equipment_id' => $equipment_info['id'], 'status' => 0]);
            fdump_api([
                'cmd' => $cmd,
                'msg' => '开电结果',
                'data'   => $data,
                'content'   => $content,
                'serial_no' => $serial_no,
                'pileModel' => $pileModel,
                'pileCharge' => $pileCharge,
                'pileStatus' => $pileStatus,
                'pileResult' => $pileResult,
            ], 'pileJiaoLiu/command_mqtt'.$dayH,1);
            if (empty($order_info)) {
                fdump_api(['cmd' => $cmd, 'msg' => '开启充电回复缺少对应订单'], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                fdump_api(['msg' => '开启充电回复缺少对应订单', 'equipment_info_id' => $equipment_info['id']], '$command_mqtt_err', 1);
                return false;
            }
            $status = substr($content, 0, 2);
            $order_data['pay_time'] = time();
            fdump_api(['cmd' => $cmd, 'order_info' => $order_info->toArray(), 'status' => $status, 'village_info' => $village_info], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            if ($status == '01') {
                $order_data['status'] = 2;
                // todo 如果充电桩枪头正常开启了充电 将非本订单的本枪头前置处于充电中的订单进行结算操作
                fdump_api(['cmd' => $cmd, 'msg' => '将非本订单的本枪头前置处于充电中的订单进行结算操作'], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                $this->finishOrder($equipment_info, $order_info, time(), 0);
            } else {
                $order_data['status'] = 1;
                $order_data['end_time'] = time();
                if (! isset($data['end_result'])) {
                    $data['end_result'] = substr($content, 2, 2);
                }
                $order_data['end_result'] = $this->mqtt_end[$data['end_result']];
                if (!$order_data['end_result']) {
                    $order_data['end_result'] = '开电反馈失败';
                    fdump_api(['cmd' => $cmd, 'msg' => '开启充电回复缺少失败原因', 'data' => $data, 'order_data' => $order_data], '$command_mqtt_err', 1);
                }
            }
            $order_no=$order_info['order_no']?$order_info['order_no']:'';
            $res = $db_house_new_pile_pay_order->save_one(['id' => $order_info['id']], $order_data);
            fdump_api(['cmd' => $cmd, 'res' => $res, 'order_data' => $order_data], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            if ($res > 0) {
                $now_user = $service_user->getUser($order_info['uid'], 'uid');
                if ($status == '01') {
                    $order_data['status'] = 2;
                } else {
                    $order_data['status'] = 1;
                    $order_data['end_time'] = time();
                    $order_data['end_result'] = $this->mqtt_end[$data['end_result']];
                }
                if ($village_info['urge_notice_type'] == 1) {
                    if (!empty($now_user['phone'])) {
                        //发短信
                        $sms_data = array('type' => 'fee_notice');
                        $sms_data['uid'] = 0;
                        $sms_data['village_id'] = $order_info['village_id'];
                        $sms_data['mobile'] = $now_user['phone'];
                        $sms_data['sendto'] = 'user';
                        $sms_data['mer_id'] = 0;
                        $sms_data['store_id'] = 0;
                        $sms_data['nationCode'] = 86;
                        $sms_content = L_('尊敬的x1您好，您在x2电动汽车已开始充电', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name']));
                        $sms_data['content'] = $sms_content;
                        fdump_api(['cmd' => $cmd, 'msg' => '发送消息', 'sms_data' => $sms_data], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                        $sms = (new SmsService())->sendSms($sms_data);
                    }
                } elseif ($village_info['urge_notice_type'] == 2) {
                    if (!empty($now_user['openid'])) {
                        $templateNewsService = new TemplateNewsService();
                        $base_url = $serviceHouseVillage->base_url;
                        $href = cfg('site_url') . $base_url . 'pages/newCharge/pages/chargeDetail?village_id=' . $order_info['village_id'] . '&order_id=' . $order_info['id'];
                        
                        $dataoldmsg = [
                            'tempKey' => 'OPENTM400166399',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $now_user['openid'],
                                'first' => '您的电动汽车已开始充电',
                                'keyword1' => '汽车充电状态提醒',
                                'keyword2' => '已发送',
                                'keyword3' => date('H:i'),
                                'remark' => '请点击查看详细信息！'
                            ]
                        ];
                        
                        //新的模板
                        $datamsg = [
                            'tempKey' => 'OPENTM407216824',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $now_user['openid'],
                                'first' => '您的电动汽车已开始充电',
                                'keyword1' => '您的电动汽车已开始充电',
                                'keyword2' => $order_no,
                                'keyword3' => '已开始充电',
                                'keyword4' => date('H:i'),
                                'remark' => '请点击查看详细信息！',
                                'oldTempMsg'=>$dataoldmsg
                            ]
                        ];
                        fdump_api(['cmd' => $cmd, 'msg' => '发送消息', 'datamsg' => $datamsg], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'], 0, 0, 1);
                    }
                } else {
                    if (!empty($now_user['openid'])) {
                        $templateNewsService = new TemplateNewsService();
                        $base_url = $serviceHouseVillage->base_url;
                        $href = cfg('site_url') . $base_url . 'pages/newCharge/pages/chargeDetail?village_id=' . $order_info['village_id'] . '&order_id=' . $order_info['id'];
                        
                        $dataoldmsg = [
                            'tempKey' => 'OPENTM400166399',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $now_user['openid'],
                                'first' => '您的电动汽车已开始充电',
                                'keyword1' => '汽车充电状态提醒',
                                'keyword2' => '已发送',
                                'keyword3' => date('H:i'),
                                'remark' => '请点击查看详细信息！'
                            ]
                        ];
                        
                        //新的模板
                        $datamsg = [
                            'tempKey' => 'OPENTM407216824',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $now_user['openid'],
                                'first' => '您的电动汽车已开始充电',
                                'keyword1' => '您的电动汽车已开始充电',
                                'keyword2' => $order_no,
                                'keyword3' => '已开始充电',
                                'keyword4' => date('H:i'),
                                'remark' => '请点击查看详细信息！',
                                'oldTempMsg'=>$dataoldmsg
                            ]
                        ];
                        fdump_api(['cmd' => $cmd, 'msg' => '发送消息', 'datamsg' => $datamsg], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'], 0, 0, 1);
                    }
                    if (!empty($now_user['phone'])) {
                        //发短信
                        $sms_data = array('type' => 'fee_notice');
                        $sms_data['uid'] = 0;
                        $sms_data['village_id'] = $order_info['village_id'];
                        $sms_data['mobile'] = $now_user['phone'];
                        $sms_data['sendto'] = 'user';
                        $sms_data['mer_id'] = 0;
                        $sms_data['store_id'] = 0;
                        $sms_data['nationCode'] = 86;
                        $sms_content = L_('尊敬的x1您好，您在x2电动汽车已开始充电', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name']));
                        $sms_data['content'] = $sms_content;
                        fdump_api(['cmd' => $cmd, 'msg' => '发送消息', 'sms_data' => $sms_data], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                        $sms = (new SmsService())->sendSms($sms_data);
                    }
                }

            }
            return $res;
        }
        elseif($function_code=='22'){
            //结束充电回复 
            $order_info=$db_house_new_pile_pay_order->getFind(['equipment_id'=>$equipment_info['id'],'status'=>2]);
            if (empty($order_info)){
                fdump_api(['cmd' => $cmd, 'msg' => '结束充电回复缺少对应订单', 'equipment_info_id' => $equipment_info['id']], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                fdump_api(['msg' => '结束充电回复缺少对应订单', 'equipment_info_id' => $equipment_info['id']], '$command_mqtt_err',1);
                return false;
            }
            $status=substr($content,0,2);
            fdump_api(['cmd' => $cmd, 'status' => $status, 'order_info' => $order_info->toArray(), 'msg' => '结束充电回复'], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            if ($status=='01'){
                $order_data['status']=1;
                $order_data['end_time']=time();
                $res=$db_house_new_pile_pay_order->save_one(['id'=>$order_info['id']],$order_data);
                return  $res;
            }else{
                fdump_api(['cmd' => $cmd, 'msg' => '结束充电回复-结束失败'], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                fdump_api(['msg' => '结束充电回复-结束失败', 'equipment_info_id' => $equipment_info['id'], 'content' => $content, 'status' => $status], '$command_mqtt_err',1);
                return false;
            }
        }
        elseif($function_code=='23'){
            //充电结束上报
            $serial_no=substr($content,0,16);//订单号
            fdump_api(['cmd' => $cmd,'serial_no' => $serial_no, 'msg' => '充电结束上报'], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            $order_info=$db_house_new_pile_pay_order->getFind(['order_serial'=>$serial_no]);
            if (empty($order_info)){
                fdump_api(['cmd' => $cmd,'msg' => '充电结束上报-缺少对应订单', 'serial_no' => $serial_no], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                fdump_api(['msg' => '充电结束上报-缺少对应订单', 'order_serial' => $serial_no, 'content' => $content], '$command_mqtt_err',1);
                return false;
            }
            fdump_api(['cmd' => $cmd,'order_info' => $order_info->toArray()], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            if ($order_info['end_type']==1){
                fdump_api(['cmd' => $cmd, 'msg' => '充电结束上报-订单已经进行了结算，避免重复结算', 'serial_no' => $serial_no], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                fdump_api(['msg' => '充电结束上报-订单已经进行了结算，避免重复结算', 'order_serial' => $serial_no, 'content' => $content, 'order_info' => $order_info], '$command_mqtt_err',1);
                return false;
            }
            $order_data_1=[];
            $order_data_1['status']=1;
            $order_data_1['end_time']=time();
            $order_data_1['end_type']=1;
            $order_res=$db_house_new_pile_pay_order->save_one(['id'=>$order_info['id']],$order_data_1);
            fdump_api(['cmd' => $cmd, 'order_data_1' => $order_data_1, 'order_res' => $order_res], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            if ($content>24){
                $use_ele=base_convert(substr($content,16,8),16,10)/3600000;//使用电量
                $use_time=base_convert(substr($content,24,8),16,10);//时间，秒 
                fdump_api(['cmd' => $cmd, 'use_ele' => $use_ele, 'use_time' => $use_time, 'content' => $content, 'msg' => '使用电量和时间'], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            }else{
                fdump_api(['cmd' => $cmd, 'msg' => '充电结束上报-使用电量和时间相关数据缺少', 'serial_no' => $serial_no], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                fdump_api(['cmd' => $cmd, 'msg' => '充电结束上报-使用电量和时间相关数据缺少', 'order_serial' => $serial_no, 'content' => $content, 'order_info' => $order_info], '$command_mqtt_err',1);
                return false;
            }
            //充电结束上报回复
            $cmd1=substr($cmd,0,14).'08'.substr($cmd,16,16);
            $crc=$this->wordStr($cmd1);
            $content1=$this->opensslEncrypt($cmd1.$crc,$key);
            $command = [
                'sn' => $equipment_info['equipment_num'],
                'cmd' => $content1,
                'desc'=>'23reply'
            ];
            $arr=[];
            $arr['command'] = \json_encode($command);
            $arr['type'] = 1;
            $arr['status'] = 1;
            $arr['addtime'] = time();
            $arr['device_type'] = $equipment_info['type'];
            $arr['equipment_id'] = $equipment_info['id'];
            fdump_api(['cmd' => $cmd, 'arr' => $arr, 'msg' => '充电结束上报回复'], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            $id = $db_house_new_pile_command->addOne($arr);
            if ($id<1){
                fdump_api(['cmd' => $cmd, 'msg' => '充电结束上报-指令写入失败', 'order_serial' => $serial_no, 'arr' => $arr, 'id' => $id], '$command_mqtt_err',1);
                return false;
            }
            $charge_info = $db_house_new_pile_charge->getFind(['village_id' => $order_info['village_id'], 'rule_id' => $order_info['charge_id'], 'status' => 1]);
            if (empty($charge_info)) {
                fdump_api(['cmd' => $cmd, 'msg' => '充电结束上报回复-缺少收费标准', 'order_serial' => $serial_no, 'order_info' => $order_info], '$command_mqtt_err',1);
                return false;
            }
            fdump_api(['cmd' => $cmd, 'charge_info' => $charge_info->toArray(), 'msg' => '收费标准'], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            $sum_money=$db_house_new_pile_pay_order_log->getSum(['order_id'=>$order_info['id']]);
            $use_money=0;
            $sum_ele=$db_house_new_pile_pay_order_log->getSumEle(['order_id'=>$order_info['id']]);
            if (empty($sum_ele)){
                $use_ele_start=0;
            }else{
                $use_ele_start=$sum_ele;
            }
            fdump_api(['cmd' => $cmd, 'sum_money' => $sum_money,'use_money' => $use_money,'sum_ele' => $sum_ele, 'use_ele_start' => $use_ele_start], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            $now_user = $service_user->getUser($order_info['uid'],'uid');
            $charge_time = \json_decode($charge_info['charge_time'], true);
            if (!empty($charge_time)) {
                foreach ($charge_time as $vc) {
                    $time_end = date('Y-m-d') . ' ' . $vc['time_end'];
                    $time_start = date('Y-m-d') . ' ' . $vc['time_start'];
                    if (strtotime($time_end) > (time()) && strtotime($time_start) < (time())) {
                        $money=$vc['money'];
                        break;
                    }
                }
                if (empty($money)&&$charge_info['price_set_value']!='-1'){
                    $money=$charge_info['price_set_value'];
                }
                if (isset($money) && !empty($money)) {
                    $charge_money = $charge_info['charge_' . ($money+1)];
                    if (!empty($charge_money)) {
                        $chargeMoney = \json_decode($charge_money, JSON_UNESCAPED_UNICODE);
                    }
                }
                if (isset($chargeMoney)){
                    $charge_price = $chargeMoney['charge_ele'] + $chargeMoney['charge_serve'];
                    $use_money=$use_money+($use_ele-$use_ele_start)*$charge_price;
                }
            }
            fdump_api([
                'cmd' => $cmd, 'charge_time' => $charge_time,'money' => isset($money) ? $money : '-0','charge_money' => isset($charge_money) ? $charge_money : '-0',
                'charge_price' => isset($charge_price) ? $charge_price : '-0', 'use_money' => $use_money, 'msg' => '充电结束计算参数'
            ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            if ($use_money<0){
                $use_money=0;
            }
            $work_time=date('H:i');
            //$equipment_info=$data['pile_info'][0];
            $log_arr=[];
            $log_arr['equipment_id']=$equipment_info['id'];
            $log_arr['equipment_num']=$equipment_info['equipment_num'];
            $log_arr['socket_no']=$order_info['socket_no'];
            $log_arr['order_id']=$order_info['id'];
            $log_arr['order_no']=$order_info['order_no'];
            $log_arr['order_serial']=$serial_no;
            $log_arr['continued_time']=$use_time;
            $log_arr['use_ele']=$use_ele-$use_ele_start;
            $log_arr['type']=2;
            $log_arr['use_money']=$use_money;
            $log_arr['work_time']=$work_time;
            $log_arr['add_time']=time();
            if ($use_money>0&&($use_ele-$use_ele_start)>0){
               // $use_money=0;
                fdump_api([
                    'cmd' => $cmd, 'work_time' => $work_time, 'log_arr' => $log_arr, 'msg' => 'log记录'
                ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                $db_house_new_pile_pay_order_log->addOne($log_arr);
            }
            $amount_money = round_number($use_money+$sum_money,2);
            //结算订单信息
            $order_data=[];
            $order_data['use_money']=$amount_money;
            $order_data['continued_time']=$use_time;
            $order_data['use_ele']=$use_ele;
            $order_data['status']=1;
            $order_data['end_time']=time();
            $order_data['end_type']=1;
            $res111=$db_house_new_pile_pay_order->save_one(['id'=>$order_info['id']],$order_data);
            $order_no=$order_info['order_no']?$order_info['order_no']:'';
            fdump_api([
                'cmd' => $cmd, 'order_data' => $order_data, 'id' => $order_info['id'], 'msg' => '订单数据修改'
            ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            if ($res111>0){
                if ($village_info['urge_notice_type']==1){
                    if (!empty($now_user['phone'])){
                        //发短信
                        $sms_data = array('type' => 'fee_notice');
                        $sms_data['uid'] = 0;
                        $sms_data['village_id'] = $order_info['village_id'];
                        $sms_data['mobile'] = $now_user['phone'];
                        $sms_data['sendto'] = 'user';
                        $sms_data['mer_id'] = 0;
                        $sms_data['store_id'] = 0;
                        $sms_data['nationCode'] =86;
                        $sms_content = L_('尊敬的x1您好，您在x2电动汽车已完成充电，消费金额x3元', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name'], 'x3' => $amount_money));
                        $sms_data['content']=$sms_content;
                        fdump_api(['cmd' => $cmd, 'msg' => '发送消息', 'sms_data' => $sms_data], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                        $sms = (new SmsService())->sendSms($sms_data);
                    }
                }elseif ($village_info['urge_notice_type']==2){
                    if(!empty($now_user['openid'])){
                        $templateNewsService = new TemplateNewsService();
                        $base_url = $serviceHouseVillage->base_url;
                        $href = cfg('site_url').$base_url.'pages/newCharge/pages/chargeDetail?village_id='.$order_info['village_id'].'&order_id='.$order_info['id'];
                        
                        $dataoldmsg = [
                            'tempKey' => 'OPENTM400166399',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $now_user['openid'],
                                'first' => '您的电动汽车已完成充电，消费金额'.$amount_money.'元',
                                'keyword1' => '汽车充电状态提醒',
                                'keyword2' => '已发送',
                                'keyword3' => date('H:i'),
                                'remark' => '请点击查看详细信息！'
                            ]
                        ];
                        
                        //新的模板
                        $datamsg = [
                            'tempKey' => 'OPENTM407216824',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $now_user['openid'],
                                'first' => '您的电动汽车已完成充电，消费金额'.round_number($amount_money,2).'元',
                                'keyword1' => '您的电动汽车已完成充电，消费金额'.round_number($amount_money,2).'元',
                                'keyword2' => $order_no,
                                'keyword3' => '已完成充电',
                                'keyword4' => date('H:i'),
                                'remark' => '请点击查看详细信息！',
                                'oldTempMsg'=>$dataoldmsg
                            ]
                        ];
                        fdump_api(['cmd' => $cmd, 'msg' => '发送消息', 'datamsg' => $datamsg], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,0,1);
                    }
                }else{
                    if(!empty($now_user['openid'])){
                        $templateNewsService = new TemplateNewsService();
                        $base_url = $serviceHouseVillage->base_url;
                        $href = cfg('site_url').$base_url.'pages/newCharge/pages/chargeDetail?village_id='.$order_info['village_id'].'&order_id='.$order_info['id'];
                        
                        $dataoldmsg = [
                            'tempKey' => 'OPENTM400166399',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $now_user['openid'],
                                'first' => '您的电动汽车已完成充电，消费金额'.$amount_money.'元',
                                'keyword1' => '汽车充电状态提醒',
                                'keyword2' => '已发送',
                                'keyword3' => date('H:i'),
                                'remark' => '请点击查看详细信息！'
                            ]
                        ];
                        
                        //新的模板
                        $datamsg = [
                            'tempKey' => 'OPENTM407216824',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $now_user['openid'],
                                'first' => '您的电动汽车已完成充电，消费金额'.round_number($amount_money,2).'元',
                                'keyword1' => '您的电动汽车已完成充电，消费金额'.round_number($amount_money,2).'元',
                                'keyword2' => $order_no,
                                'keyword3' => '已完成充电',
                                'keyword4' => date('H:i'),
                                'remark' => '请点击查看详细信息！',
                                'oldTempMsg'=>$dataoldmsg
                            ]
                        ];
                        fdump_api(['cmd' => $cmd, 'msg' => '发送消息', 'datamsg' => $datamsg], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,0,1);
                    }
                    if (!empty($now_user['phone'])){
                        //发短信
                        $sms_data = array('type' => 'fee_notice');
                        $sms_data['uid'] = 0;
                        $sms_data['village_id'] = $order_info['village_id'];
                        $sms_data['mobile'] = $now_user['phone'];
                        $sms_data['sendto'] = 'user';
                        $sms_data['mer_id'] = 0;
                        $sms_data['store_id'] = 0;
                        $sms_data['nationCode'] =86;
                        $sms_content = L_('尊敬的x1您好，您在x2电动汽车已完成充电，消费金额x3元', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name'], 'x3' => $amount_money));
                        $sms_data['content']=$sms_content;
                        fdump_api(['cmd' => $cmd, 'msg' => '发送消息', 'sms_data' => $sms_data], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                        $sms = (new SmsService())->sendSms($sms_data);
                    }
                }
                $userInfo=$db_house_new_pile_user_money->getOne(['uid'=>$order_info['uid'],'business_id'=>$order_info['village_id']]);
                if (empty($userInfo)){
                    fdump_api(['cmd' => $cmd, 'msg' => '充电结束上报-账户信息不存在', 'order_serial' => $serial_no, 'order_data' => $order_data], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                    fdump_api(['msg' => '充电结束上报-账户信息不存在', 'order_serial' => $serial_no, 'order_info' => $order_info, 'order_data' => $order_data], '$command_mqtt_err',1);
                    throw new \think\Exception("账户信息不存在");
                }
                $current_money = round_number($userInfo['current_money']-$amount_money,2);
                $res1=$db_house_new_pile_user_money->saveOne(['uid'=>$order_info['uid'],'business_id'=>$order_info['village_id']],['current_money'=>$current_money]);
                if ($res1){
                    $housePaidOrderRecordService= new HousePaidOrderRecordService();
                    $housePaidOrderRecordService->carElePileOrderPayRecord($order_info['id'],$order_info['village_id'],$amount_money);
                    $arr=[];
                    $arr['uid']=$order_info['uid'];
                    $arr['order_id']=$order_info['id'];
                    $arr['order_type']=1;
                    $arr['business_type']=1;
                    $arr['business_id']=$order_info['village_id'];
                    $arr['type']=2;
                    $arr['current_money']=$userInfo['current_money'];
                    $arr['money']=$amount_money;
                    $arr['after_price']=$current_money;
                    $arr['add_time']=time();
                    $arr['ip']=get_client_ip();
                    $arr['desc']='充电结算扣款';
                    fdump_api(['cmd' => $cmd, 'msg' => '充电结算扣款', 'arr' => $arr], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                    $db_house_new_pile_user_money_log->addOne($arr);
                }
            } else {
                fdump_api(['cmd' => $cmd, 'msg' => '充电结束上报-订单更新失败', 'order_serial' => $serial_no, 'order_data' => $order_data], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                fdump_api(['msg' => '充电结束上报-订单更新失败', 'order_serial' => $serial_no, 'order_info' => $order_info, 'order_data' => $order_data], '$command_mqtt_err',1);
            }
            return  $res111;
        }
        elseif($function_code=='24'){
            //查询订单充电情况
            fdump_api(['cmd' => $cmd, 'msg' => '查询订单充电情况-反馈'], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            $serial_no=substr($content,0,16);//订单号
            $use_power=base_convert(substr($content,16,8),16,10);//功率
            $use_ele=base_convert(substr($content,24,8),16,10)/3600000;//使用电量
            $use_ele=round_number($use_ele,2);
            fdump_api(['cmd' => $cmd, 'serial_no' => $serial_no, 'use_power' => $use_power, 'use_ele' => $use_ele], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            $order_info=$db_house_new_pile_pay_order->getFind(['order_serial'=>$serial_no,'end_type'=>0]);
            if (empty($order_info)){
                fdump_api(['cmd' => $cmd, 'msg' => '查询订单充电情况反馈-缺少对应订单', 'serial_no' => $serial_no, 'use_power' => $use_power, 'use_ele' => $use_ele], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                fdump_api(['msg' => '查询订单充电情况反馈-缺少对应订单', 'order_serial' => $serial_no, 'use_power' => $use_power, 'use_ele' => $use_ele], '$command_mqtt_err',1);
                return false;
            }
            fdump_api(['cmd' => $cmd, 'msg' => '查询订单充电情况反馈-订单', 'order_info' => $order_info->toArray()], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            $saveOrderData = ['end_power' => $use_power];
            if (isset($order_info['pay_time']) && $order_info['pay_time'] < 100) {
                $saveOrderData['pay_time'] = time();
            }
            $db_house_new_pile_pay_order->save_one(['id'=>$order_info['id']],$saveOrderData);
            $charge_info = $db_house_new_pile_charge->getFind(['village_id' => $order_info['village_id'], 'rule_id' => $order_info['charge_id'], 'status' => 1]);
            if (empty($charge_info)) {
                fdump_api(['cmd' => $cmd, 'msg' => '查询订单充电情况反馈-缺少收费标准'], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                fdump_api(['msg' => '查询订单充电情况反馈-缺少收费标准', 'order_serial' => $serial_no,'order_info' => $order_info, 'use_power' => $use_power, 'use_ele' => $use_ele], '$command_mqtt_err',1);
                return false;
            }
            fdump_api(['cmd' => $cmd, 'msg' => '查询订单充电情况反馈-收费标准', 'charge_info' => $charge_info->toArray()], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            $sum_money=$db_house_new_pile_pay_order_log->getSum(['order_id'=>$order_info['id']]);
            $sum_ele=$db_house_new_pile_pay_order_log->getSumEle(['order_id'=>$order_info['id']]);
            $use_money=0;
            $order_log=$db_house_new_pile_pay_order_log->getFind(['order_id'=>$order_info['id']]);
            if (empty($sum_ele)){
                $use_ele_start=0;
            }else{
                $use_ele_start=$sum_ele;
            }
            $userInfo=$db_house_new_pile_user_money->getOne(['uid'=>$order_info['uid'],'business_id'=>$order_info['village_id']]);
            if ($userInfo['current_money']<=$sum_money){
                //下发充电结束指令
                $cmd2='0001000122020000';
                $crc2=$this->wordStr($cmd2);
                $content2=$this->opensslEncrypt($cmd2.$crc2,$key);
                $command2 = [
                    'sn' => $equipment_info['equipment_num'],
                    'cmd' => $content2,
                    'desc' => 'mqtt_stopCharge',
                ];
                $arr=[];
                $arr['command'] = \json_encode($command2);
                $arr['type'] = 1;
                $arr['status'] = 1;
                $arr['addtime'] = time();
                $arr['device_type'] = $equipment_info['type'];
                $arr['equipment_id'] = $equipment_info['id'];
                fdump_api(['cmd' => $cmd, 'msg' => '下发充电结束指令', 'arr' => $arr, 'command2' => $command2], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                $db_house_new_pile_command->addOne($arr);
            }
            $work_time='';
            $charge_time = \json_decode($charge_info['charge_time'], true);
            if (!empty($charge_time)) {
                foreach ($charge_time as $vc) {
                    $time_end = date('Y-m-d') . ' ' . $vc['time_end'];
                    $time_start = date('Y-m-d') . ' ' . $vc['time_start'];
                    if (strtotime($time_end) > (time()) && strtotime($time_start) < (time())) {
                        $money=$vc['money'];
                    }
                }
                if (empty($money)&&$charge_info['price_set_value']!='-1'){
                    $money=$charge_info['price_set_value'];
                }
                if (isset($money) && !empty($money)) {
                    $charge_money = $charge_info['charge_' . ($money+1)];
                    if (!empty($charge_money)) {
                        $chargeMoney = \json_decode($charge_money, JSON_UNESCAPED_UNICODE);
                    }
                }
                if (isset($chargeMoney)){
                    $charge_price = $chargeMoney['charge_ele'] + $chargeMoney['charge_serve'];
                    $use_money=($use_ele-$use_ele_start)*$charge_price;
                }
            }
            fdump_api([
                'cmd' => $cmd, 'charge_time' => $charge_time,'money' => isset($money) ? $money : '-0','charge_money' => isset($charge_money) ? $charge_money : '-0',
                'charge_price' => isset($charge_price) ? $charge_price : '-0', 'use_money' => $use_money, 'msg' => '查询订单充电情况'
            ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            $flag=1;
            if (isset($flag)&&$flag==1){
                $log_arr=[];
                $log_arr['equipment_id']=$order_info['equipment_id'];
                $log_arr['equipment_num']=$equipment_info['equipment_num'];
                $log_arr['socket_no']=$order_info['socket_no'];
                $log_arr['order_id']=$order_info['id'];
                $log_arr['order_no']=$order_info['order_no'];
                $log_arr['order_serial']=$serial_no;
                $log_arr['continued_time']=(time()-$order_info['pay_time']);
                $log_arr['power']=$use_power;
                $log_arr['type']=2;
                $log_arr['use_ele']=($use_ele-$use_ele_start);
                $log_arr['use_money']=$use_money;
                $log_arr['work_time']=$work_time;
                $log_arr['add_time']=time();
                if (($use_ele-$use_ele_start)>0&&$use_money>0){
                    fdump_api([
                        'cmd' => $cmd, 'log_arr' => $log_arr,'use_ele' => $use_ele,'use_ele_start' => $use_ele_start,'use_money' => $use_money, 'msg' => '查询订单充电情况-记录'
                    ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                    $res=$db_house_new_pile_pay_order_log->addOne($log_arr);
                    $db_house_new_pile_pay_order->save_one(['id'=>$order_info['id']],['use_ele'=>$use_ele,'actual_ele'=>($use_ele-$use_ele_start),'use_money'=>($use_money+$sum_money)]);

                }else{
                    fdump_api([
                        'cmd' => $cmd, 'log_arr' => $log_arr,'use_ele' => $use_ele,'use_ele_start' => $use_ele_start,'use_money' => $use_money, 'msg' => '汽车充电桩充电信息实时纪录失败-记录'
                    ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                    fdump_api(['msg' => '查询订单充电情况反馈-汽车充电桩充电信息实时纪录失败', 'use_ele' => $use_ele, 'use_ele_start' => $use_ele_start, 'use_money' => $use_money], '$command_mqtt_err',1);
                    $res=0;
                }
                return  $res;
            }else{
                fdump_api([
                    'cmd' => $cmd, 'use_ele' => $use_ele,'use_ele_start' => $use_ele_start,'use_money' => $use_money, 'msg' => '不记录'
                ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                return  false;
            }
        }
        elseif($function_code=='01'){
            $flag_code=substr($cmd,2,2);
            $temperature=base_convert(substr($content,0,4),16,10);
            $power=base_convert(substr($content,4,4),16,10);
            $voltage=base_convert(substr($content,12,4),16,10);
            $electric_currentr=base_convert(substr($content,16,4),16,10);
            $electric_currentr=round($electric_currentr/100);
            $status=substr($content,20,2);
            $signal=base_convert(substr($content,22,2),16,10);
            fdump_api([
                'cmd' => $cmd, 'flag_code' => $flag_code,'temperature' => $temperature, 'power' => $power, 'voltage' => $voltage,
                'electric_currentr' => $electric_currentr, 'status' => $status, 'signal' => $signal,  'msg' => '0x01反馈'
            ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            $addTimeLimit = time() - 600;
            $whereOrder = [];
            $whereOrder[] = ['equipment_id', '=', $equipment_info['id']];
            $whereOrder[] = ['end_type', '=', 0];
            $order_info=$db_house_new_pile_pay_order->getFind($whereOrder);
            if ($equipment_info['min_temperature']>$temperature||$equipment_info['max_temperature']>$temperature){
                $temperature_start=1;
            }else{
                $temperature_start=0; 
            }
            $order_no=$order_info && $order_info['order_info'] ? $order_info['order_info']:'';
            fdump_api([
                'cmd' => $cmd, 'temperature_start' => $temperature_start, 'order_info' => $order_info&&!is_array($order_info)?$order_info->toArray():$order_info,
            ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            if (!empty($order_info)&& $order_info['status']==2){
                if (!in_array($status,['03','04'])){ //非充电中
                    fdump_api([
                        'cmd' => $cmd, 'msg' => '非充电中', 'payTime' => $order_info['pay_time'],
                    ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                    if ($order_info['pay_time']+60<time()){
                        $order_data_status1=[];
                        $order_data_status1['status']=1;
                        $order_data_status1['end_time']=time();
                        $db_house_new_pile_pay_order->save_one(['id'=>$order_info['id']],$order_data_status1);
                        // todo 如果充电桩枪头 空闲了 然后订单结束了 就以当前订单之前查询到的订单进行结算行为
                        fdump_api([
                            'cmd' => $cmd, 'msg' => '就以当前订单之前查询到的订单进行结算行为',
                            'equipment_info' => ($equipment_info && !is_array($equipment_info)) ? $equipment_info->toArray() : $equipment_info,
                            'order_info' => ($order_info && !is_array($order_info)) ? $order_info->toArray() : $order_info,
                        ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                        $this->finishOrder($equipment_info, $order_info, time(), 1);
                    }
                }else{ //充电中
                    //下发订单查询指令
                    $cmd2='0001000124010008'.$order_info['order_serial'];
                    $crc=$this->wordStr($cmd2);
                    $content1=$this->opensslEncrypt($cmd2.$crc,$key);
                    $command = [
                        'sn' => $equipment_info['equipment_num'],
                        'cmd' => $content1,
                        'desc'=>'mqtt_check'
                    ];
                    $arr=[];
                    $arr['command'] = \json_encode($command);
                    $arr['type'] = 1;
                    $arr['status'] = 1;
                    $arr['addtime'] = time();
                    $arr['device_type'] = $equipment_info['type'];
                    $arr['equipment_id'] = $equipment_info['id'];
                    fdump_api([
                        'cmd' => $cmd, 'msg' => '充电中-下发订单查询指令', 'arr' => $arr, 'command' => $command
                    ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                    $id1 = $db_house_new_pile_command->addOne($arr);
                    fdump_api([$cmd2,$command,$arr],'command_mqtt_1101',1);
                    if ($id1<1){
                        fdump_api([
                            'cmd' => $cmd, 'msg' => '查询设备状态-下发订单查询指令记录失败', 'arr' => $arr, 'status' => $status,
                            'order_info' => ($order_info && !is_array($order_info)) ? $order_info->toArray() : $order_info,
                        ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                        fdump_api(['cmd' => $cmd,'msg' => '查询设备状态-下发订单查询指令记录失败', 'arr' => $arr, 'status' => $status, 'order_info' => $order_info], '$command_mqtt_err',1);
                        return false;
                    }
                    
                }
                if (!empty($order_info)){
                    $db_house_new_pile_pay_order->save_one(['id'=>$order_info['id']],['end_power'=>$power]);
                }
                if ($temperature_start==1){
                   if ($village_info['urge_notice_type']==1){
                        if (!empty($now_user['phone'])){
                            //发短信
                            $sms_data = array('type' => 'fee_notice');
                            $sms_data['uid'] = 0;
                            $sms_data['village_id'] = $order_info['village_id'];
                            $sms_data['mobile'] = $now_user['phone'];
                            $sms_data['sendto'] = 'user';
                            $sms_data['mer_id'] = 0;
                            $sms_data['store_id'] = 0;
                            $sms_data['nationCode'] =86;
                            $sms_content = L_('尊敬的x1您好，您在x2电动汽车所在设备有充电异常，请及时查看', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name']));
                            $sms_data['content']=$sms_content;
                            fdump_api([
                                'cmd' => $cmd,'msg' => '发送消息', 'sms_data' => $sms_data
                            ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                            $sms = (new SmsService())->sendSms($sms_data);
                        }
                    }elseif ($village_info['urge_notice_type']==2){
                        if(!empty($now_user['openid'])){
                            $templateNewsService = new TemplateNewsService();
                            $base_url = $serviceHouseVillage->base_url;
                            $href = cfg('site_url').$base_url.'pages/newCharge/pages/chargeDetail?village_id='.$order_info['village_id'].'&order_id='.$order_info['id'];
                            
                            $dataoldmsg = [
                                'tempKey' => 'OPENTM400166399',
                                'dataArr' => [
                                    'href' => $href,
                                    'wecha_id' => $now_user['openid'],
                                    'first' => '您的电动汽车所在设备有充电异常，请及时查看',
                                    'keyword1' => '汽车充电状态提醒',
                                    'keyword2' => '已发送',
                                    'keyword3' => date('H:i'),
                                    'remark' => '请点击查看详细信息！'
                                ]
                            ];
                            
                            //新的模板
                            $datamsg = [
                                'tempKey' => 'OPENTM407216824',
                                'dataArr' => [
                                    'href' => $href,
                                    'wecha_id' => $now_user['openid'],
                                    'first' => '您的电动汽车所在设备有充电异常，请及时查看',
                                    'keyword1' => '您的电动汽车所在设备有充电异常，请及时查看',
                                    'keyword2' => $order_no,
                                    'keyword3' => '充电异常',
                                    'keyword4' => date('H:i'),
                                    'remark' => '请点击查看详细信息！',
                                    'oldTempMsg'=>$dataoldmsg
                                ]
                            ];
                            fdump_api([
                                'cmd' => $cmd,'msg' => '发送消息', 'datamsg' => $datamsg
                            ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                            $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,0,1);
                        }
                    }else{
                        if(!empty($now_user['openid'])){
                            $templateNewsService = new TemplateNewsService();
                            $base_url = $serviceHouseVillage->base_url;
                            $href = cfg('site_url').$base_url.'pages/newCharge/pages/chargeDetail?village_id='.$order_info['village_id'].'&order_id='.$order_info['id'];
                            
                            $dataoldmsg = [
                                'tempKey' => 'OPENTM400166399',
                                'dataArr' => [
                                    'href' => $href,
                                    'wecha_id' => $now_user['openid'],
                                    'first' => '您的电动汽车所在设备有充电异常，请及时查看',
                                    'keyword1' => '汽车充电状态提醒',
                                    'keyword2' => '已发送',
                                    'keyword3' => date('H:i'),
                                    'remark' => '请点击查看详细信息！'
                                ]
                            ];
                            
                            //新的模板
                            $datamsg = [
                                'tempKey' => 'OPENTM407216824',
                                'dataArr' => [
                                    'href' => $href,
                                    'wecha_id' => $now_user['openid'],
                                    'first' => '您的电动汽车所在设备有充电异常，请及时查看',
                                    'keyword1' => '您的电动汽车所在设备有充电异常，请及时查看',
                                    'keyword2' => $order_no,
                                    'keyword3' => '充电异常',
                                    'keyword4' => date('H:i'),
                                    'remark' => '请点击查看详细信息！',
                                    'oldTempMsg'=>$dataoldmsg
                                ]
                            ];
                            fdump_api([
                                'cmd' => $cmd,'msg' => '发送消息', 'datamsg' => $datamsg
                            ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                            $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,0,1);
                        }
                        if (!empty($now_user['phone'])){
                            //发短信
                            $sms_data = array('type' => 'fee_notice');
                            $sms_data['uid'] = 0;
                            $sms_data['village_id'] = $order_info['village_id'];
                            $sms_data['mobile'] = $now_user['phone'];
                            $sms_data['sendto'] = 'user';
                            $sms_data['mer_id'] = 0;
                            $sms_data['store_id'] = 0;
                            $sms_data['nationCode'] =86;
                            $sms_content = L_('尊敬的x1您好，您在x2电动汽车所在设备有充电异常，请及时查看', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name']));
                            $sms_data['content']=$sms_content;
                            fdump_api([
                                'cmd' => $cmd,'msg' => '发送消息', 'sms_data' => $sms_data
                            ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                            $sms = (new SmsService())->sendSms($sms_data);
                        }
                    }  
                }
            }
            else{
                if ($status=='03'&&$order_info['status']==0&&$order_info['add_time']>$addTimeLimit){
                    $status_e=1;
                    $socket_status=[1];
                    if ($order_info['status']==0){
                        $order_data_status=[];
                        $order_data_status['pay_time']=time();
                        $order_data_status['status']=2;
                        $res_order=$db_house_new_pile_pay_order->save_one(['id'=>$order_info['id']],$order_data_status);
                        fdump_api([
                            'cmd' => $cmd, 'msg' => '充电中','id' => $order_info['id'], 'order_data_status' => $order_data_status,
                        ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                        // todo 插头充电了 将非本订单的本枪头前置处于充电中的订单进行结算操作
                        fdump_api(['cmd' => $cmd, 'msg' => '插头充电了-将非本订单的本枪头前置处于充电中的订单进行结算操作'], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                        $this->finishOrder($equipment_info, $order_info, time(), 0);
                        if ($res_order>0){
                            $now_user = $service_user->getUser($order_info['uid'],'uid');
                            if ($village_info['urge_notice_type']==1){
                                if (!empty($now_user['phone'])){
                                    //发短信
                                    $sms_data = array('type' => 'fee_notice');
                                    $sms_data['uid'] = 0;
                                    $sms_data['village_id'] = $order_info['village_id'];
                                    $sms_data['mobile'] = $now_user['phone'];
                                    $sms_data['sendto'] = 'user';
                                    $sms_data['mer_id'] = 0;
                                    $sms_data['store_id'] = 0;
                                    $sms_data['nationCode'] =86;
                                    $sms_content = L_('尊敬的x1您好，您在x2电动汽车已开始充电', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name']));
                                    $sms_data['content']=$sms_content;
                                    fdump_api([
                                        'cmd' => $cmd, 'msg' => '发送消息', 'sms_data' => $sms_data
                                    ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                                    $sms = (new SmsService())->sendSms($sms_data);
                                }
                            }elseif ($village_info['urge_notice_type']==2){
                                if(!empty($now_user['openid'])){
                                    $templateNewsService = new TemplateNewsService();
                                    $base_url = $serviceHouseVillage->base_url;
                                    $href = cfg('site_url').$base_url.'pages/newCharge/pages/chargeDetail?village_id='.$order_info['village_id'].'&order_id='.$order_info['id'];

                                    $dataoldmsg = [
                                        'tempKey' => 'OPENTM400166399',
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $now_user['openid'],
                                            'first' => '您的电动汽车已开始充电',
                                            'keyword1' => '汽车充电状态提醒',
                                            'keyword2' => '已发送',
                                            'keyword3' => date('H:i'),
                                            'remark' => '请点击查看详细信息！'
                                        ]
                                    ];
                                    
                                    //新的模板
                                    $datamsg = [
                                        'tempKey' => 'OPENTM407216824',
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $now_user['openid'],
                                            'first' => '您的电动汽车已开始充电',
                                            'keyword1' => '您的电动汽车已开始充电',
                                            'keyword2' => $order_no,
                                            'keyword3' => '已开始充电',
                                            'keyword4' => date('H:i'),
                                            'remark' => '请点击查看详细信息！',
                                            'oldTempMsg'=>$dataoldmsg
                                        ]
                                    ];
                                    fdump_api([
                                        'cmd' => $cmd, 'msg' => '发送消息', 'datamsg' => $datamsg
                                    ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                                    $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,0,1);
                                }
                            }else{
                                if(!empty($now_user['openid'])){
                                    $templateNewsService = new TemplateNewsService();
                                    $base_url = $serviceHouseVillage->base_url;
                                    $href = cfg('site_url').$base_url.'pages/newCharge/pages/chargeDetail?village_id='.$order_info['village_id'].'&order_id='.$order_info['id'];
                                    
                                    $dataoldmsg = [
                                        'tempKey' => 'OPENTM400166399',
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $now_user['openid'],
                                            'first' => '您的电动汽车已开始充电',
                                            'keyword1' => '汽车充电状态提醒',
                                            'keyword2' => '已发送',
                                            'keyword3' => date('H:i'),
                                            'remark' => '请点击查看详细信息！'
                                        ]
                                    ];
                                    
                                    //新的模板
                                    $datamsg = [
                                        'tempKey' => 'OPENTM407216824',
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $now_user['openid'],
                                            'first' => '您的电动汽车已开始充电',
                                            'keyword1' => '您的电动汽车已开始充电',
                                            'keyword2' => $order_no,
                                            'keyword3' => '已开始充电',
                                            'keyword4' => date('H:i'),
                                            'remark' => '请点击查看详细信息！',
                                            'oldTempMsg'=>$dataoldmsg
                                        ]
                                    ];
                                    fdump_api([
                                        'cmd' => $cmd, 'msg' => '发送消息', 'datamsg' => $datamsg
                                    ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                                    $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,0,1);
                                }
                                if (!empty($now_user['phone'])){
                                    //发短信
                                    $sms_data = array('type' => 'fee_notice');
                                    $sms_data['uid'] = 0;
                                    $sms_data['village_id'] = $order_info['village_id'];
                                    $sms_data['mobile'] = $now_user['phone'];
                                    $sms_data['sendto'] = 'user';
                                    $sms_data['mer_id'] = 0;
                                    $sms_data['store_id'] = 0;
                                    $sms_data['nationCode'] =86;
                                    $sms_content = L_('尊敬的x1您好，您在x2电动汽车已开始充电', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name']));
                                    $sms_data['content']=$sms_content;
                                    fdump_api([
                                        'cmd' => $cmd, 'msg' => '发送消息', 'sms_data' => $sms_data
                                    ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                                    $sms = (new SmsService())->sendSms($sms_data);
                                }
                            }

                        }
                    }
                }
                elseif($status=='04'){
                    $db_house_village=new HouseVillage();
                    $houseMaintenanLog = new HouseMaintenanLog();
                    $status_e=4;
                    $where_log=[];
                    $where_log['village_id']=$equipment_info['village_id'];
                    $where_log['device_id']=$equipment_info['id'];
                    $log_info=$houseMaintenanLog->get_one($where_log);
                    fdump_api([
                        'cmd' => $cmd, 'msg' => '设备异常','log_info' => ($log_info && !is_array($log_info)) ? $log_info->toArray() : $log_info,
                    ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                    $village_name=$db_house_village->getOne($equipment_info['village_id'],'village_name,village_address');
                    if (empty($log_info)&&$status==2){
                        $data_log=[];
                        $data_log['village_id']=$equipment_info['village_id'];
                        $data_log['village_name']=$village_name['village_name'];
                        $data_log['device_id']=$equipment_info['id'];
                        $data_log['device_type']=8;
                        $data_log['address']=$village_name['village_address'];
                        $data_log['device_name']=$equipment_info['equipment_name'];
                        $data_log['reason']='设备异常';
                        $data_log['next_key']=30;
                        $data_log['next_time']=time();
                        $data_log['add_time']=time();
                        fdump_api([
                            'cmd' => $cmd, 'data_log' => $data_log,
                        ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                        $houseMaintenanLog->addOne($data_log);
                    }elseif(!empty($log_info)){
                        if (!empty($log_info['next_key'])&&$status==1){
                            $data_log=[];
                            $data_log['next_key']=0;
                            $where_log_save=['id'=>$log_info['id']];
                            fdump_api([
                                'cmd' => $cmd, 'status' => $status, 'where_log_save' => $where_log_save, 'data_log' => $data_log,
                            ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                            $houseMaintenanLog->save_one($where_log_save,$data_log);
                        }elseif (empty($log_info['next_key'])&&$status==2){
                            $data_log=[];
                            $data_log['village_id']=$equipment_info['village_id'];
                            $data_log['village_name']=$village_name['village_name'];
                            $data_log['device_id']=$equipment_info['id'];
                            $data_log['device_type']=8;
                            $data_log['address']=$village_name['village_address'];
                            $data_log['device_name']=$equipment_info['equipment_name'];
                            $data_log['reason']='设备异常';
                            $data_log['next_key']=30;
                            $data_log['next_time']=time();
                            $data_log['add_time']=time();
                            fdump_api([
                                'cmd' => $cmd, 'status' => $status, 'data_log' => $data_log,
                            ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                            $houseMaintenanLog->addOne($data_log);
                        }

                    }
                } else{
                    $status_e=1;
                    $socket_status=[0];
                }
                $socket_status=json_encode($socket_status);
                fdump_api([
                    'cmd' => $cmd, 'msg' => '更新插口状态', 'id' => $equipment_info['id'], 'soc' => $socket_status, 'status_e' => $status_e,
                ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                $res1=$db_house_new_pile_equipment->save_one(['id'=>$equipment_info['id'],'is_del'=>1],['status'=>$status_e,'socket_status'=>$socket_status]);
            }
            $log_arr=[];
            $log_arr['equipment_id']=$equipment_info['id'];
            $log_arr['equipment_num']=$equipment_info['equipment_num'];
            $log_arr['status']=(int)$status;//枪头状态 0 空闲 1 插枪 2 充电中 3 充满 4 异常
            $log_arr['type']=2;
            $log_arr['power']=$power;
            $log_arr['voltage']=$voltage;
            $log_arr['electric_current']=$electric_currentr;
            $log_arr['temperature']=$temperature;
            $log_arr['signal']=$signal;
            $log_arr['add_time']=time();
            fdump_api([
                'cmd' => $cmd, 'msg' => '设备状态日志', 'log_arr' => $log_arr,
            ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
            $res=$db_house_new_pile_equipment_log->addOne($log_arr);
            $db_house_new_pile_equipment->save_one(['id'=>$equipment_info['id']],['status'=>1]);
            $equipment_list=$db_house_new_pile_equipment->getList(['type'=>2,'is_del'=>1],'id');
            if (!empty($equipment_list)){
                $equipment_list=$equipment_list->toArray();
            }
            if (!empty($equipment_list)){
                fdump_api([
                    'cmd' => $cmd, 'msg' => '心跳更新设备状态',
                ], 'pileJiaoLiu/command_mqtt'.$dayH, 1);
                foreach ($equipment_list as $ev){
                    $logInfo=$db_house_new_pile_equipment_log->getFind(['equipment_id'=>$ev['id']]);
                    if (empty($logInfo)||$logInfo['add_time']+900<time()){
                        $db_house_new_pile_equipment->save_one(['id'=>$ev['id']],['status'=>3]);
                    }
                }
            }
            return  $res;
            
        }
        return false;
    }
    

    /**
     * 解密
     * @author:zhubaodi
     * @date_time: 2022/10/27 16:06
     */ 
   public function opensslDecrypt($res,$key){
       $key=md5($key);
       $key= pack("H*",$key);
       $plaintext= pack("H*",$res);
       $cipher = 'AES-128-ECB';
       // 解密
       $chiperRaw = openssl_decrypt($plaintext, $cipher, $key,OPENSSL_RAW_DATA); //OPENSSL_RAW_DATA方式【会用PKCS#7进行补位】
       // 加密
       $cipherHex = bin2hex($chiperRaw);
       return $cipherHex;
   }


    /**
     * 加密
     * @author:zhubaodi
     * @date_time: 2022/10/27 16:06
     */
    public function opensslEncrypt($res,$key){
        $key=md5($key);
        $key= pack("H*",$key);
        $plaintext= pack("H*",$res);
        $cipher = 'AES-128-ECB';
        // 解密
        $chiperRaw = openssl_encrypt($plaintext, $cipher, $key,OPENSSL_RAW_DATA); //OPENSSL_RAW_DATA方式【会用PKCS#7进行补位】
        // 加密
        $cipherHex = bin2hex($chiperRaw);
        return $cipherHex;
    } 
    
    
    public function finishOrder($equipment_info, $origin_order_info, $now_time = 0, $isCurrent = 1) {
        $equipment_info = ($equipment_info && ! is_array($equipment_info)) ? $equipment_info->toArray() : $equipment_info;
        $origin_order_info = ($origin_order_info && ! is_array($origin_order_info)) ? $origin_order_info->toArray() : $origin_order_info;
        if (isset($origin_order_info['end_type']) && $origin_order_info['end_type'] == 1) {
            fdump_api(['msg' => '结单订单失败-订单已结单', 'equipment_info' => $equipment_info, '$origin_order_info' => $origin_order_info, 'isCurrent' => $isCurrent], '$command_mqtt_err',1);
            return false;
        }
        if (!$now_time) {
            $now_time = time();
        }
        fdump_api([
            'msg' => '结单订单', 'equipment_info' => $equipment_info,
            '$origin_order_info' => $origin_order_info, 'now_time' => $now_time, 'isCurrent' => $isCurrent
        ], '$finishOrder',1);
        $db_house_new_pile_pay_order     = new HouseNewPilePayOrder();
        $db_house_new_pile_pay_order_log = new HouseNewPilePayOrderLog();
        $db_house_new_pile_user_money    = new HouseNewPileUserMoney();
        if ($isCurrent == 1) {
            $order_info = $origin_order_info;
            if (empty($order_info)) {
                fdump_api(['msg' => '结单订单失败-没有符合条件订单', 'equipment_info' => $equipment_info, 'origin_order_info' => $origin_order_info, 'isCurrent' => $isCurrent], '$command_mqtt_err',1);
                return false;
            }
            // 当前订单就查询下这个订单的历史进行清算
            $field = 'SUM(use_money) as use_money_total, SUM(use_ele) as use_ele_total';
            $order_log = $db_house_new_pile_pay_order_log->getFind(['order_id' => $order_info['id']], $field);
            if ($order_log && !is_array($order_log)) {
                $order_log = $order_log->toArray();
            }
            fdump_api(['msg' => '1结单订单消耗', 'order_log' => $order_log, 'order_info' => $order_info, 'sql' => $db_house_new_pile_pay_order_log->getLastSql()], '$finishOrder',1);
            if (empty($order_log) || !isset($order_log['use_money_total']) || !$order_log['use_money_total']) {
                fdump_api(['msg' => '结单订单失败-订单没有相关记录不进行结算', 'equipment_info' => $equipment_info, 'order_info' => $order_info, 'isCurrent' => $isCurrent], '$command_mqtt_err',1);
                return false;
            }
            $amount_money = $order_log['use_money_total'];
            $use_ele      = $order_log['use_ele_total'];
        } else {
            $whereOrder = [];
            $whereOrder[]  = ['id', '<>', $origin_order_info['id']];
            $whereOrder[]  = ['end_type', '=', 0];
            $whereOrder[]  = ['equipment_id', '=', $equipment_info['id']];
            $whereOrder[]  = ['status', 'in', [0,2]];
            $order_list = $db_house_new_pile_pay_order->get_list($whereOrder);
            if ($order_list && !is_array($order_list)) {
                $order_list = $order_list->toArray();
            }
            if (empty($order_list)) {
                fdump_api(['msg' => '结单订单失败-没有符合条件订单', 'equipment_info' => $equipment_info, 'whereOrder' => $whereOrder, 'isCurrent' => $isCurrent], '$command_mqtt_err',1);
                return false;
            }
            fdump_api(['msg' => '1结单订单消耗', 'order_list' => $order_list], '$finishOrder',1);
            foreach ($order_list as $origin_order_info) {
                $this->finishOrder($equipment_info, $origin_order_info, $now_time, 1);
            }
            return  true;
        }
        fdump_api(['msg' => '结单订单消耗', 'amount_money' => $amount_money, 'use_ele' => $use_ele, 'order_info' => $order_info], '$finishOrder',1);
        if ($amount_money < 0 || $use_ele < 0) {
            return false;
        }
        $order_no=$origin_order_info && $origin_order_info['order_no'] ? $origin_order_info['order_no']:'';
        //结算订单信息
        $order_data = [];
        $order_data['use_money'] = $amount_money;
        $order_data['continued_time'] = $now_time - $order_info['pay_time'];
        $order_data['use_ele']  = $use_ele;
        $order_data['status']   = 1;
        $order_data['end_time'] = time();
        $order_data['end_type'] = 1;
        fdump_api(['msg' => '结单订单更改记录', 'order_data' => $order_data], '$finishOrder',1);
        $res111 = $db_house_new_pile_pay_order->save_one(['id' => $order_info['id']], $order_data);
        if ($res111 > 0) {
            $service_user = new UserService();
            $serviceHouseVillage = new HouseVillageService();
            $db_house_new_pile_user_money_log = new HouseNewPileUserMoneyLog();
            $db_house_new_pile_config = new HouseNewPileConfig();
            $village_info = $serviceHouseVillage->getHouseVillageInfoExtend(['village_id' => $equipment_info['village_id']], 'urge_notice_type');
            $pile_config = $db_house_new_pile_config->getFind(['village_id' => $equipment_info['village_id']]);
            $now_user = $service_user->getUser($order_info['uid'],'uid');
            if ($village_info['urge_notice_type'] == 1) {
                if (!empty($now_user['phone'])) {
                    //发短信
                    $sms_data = array('type' => 'fee_notice');
                    $sms_data['uid'] = 0;
                    $sms_data['village_id'] = $order_info['village_id'];
                    $sms_data['mobile'] = $now_user['phone'];
                    $sms_data['sendto'] = 'user';
                    $sms_data['mer_id'] = 0;
                    $sms_data['store_id'] = 0;
                    $sms_data['nationCode'] = 86;
                    $sms_content = L_('尊敬的x1您好，您在x2电动汽车充电结束，消费金额x3元', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name'], 'x3' => $amount_money));
                    $sms_data['content'] = $sms_content;
                    $sms = (new SmsService())->sendSms($sms_data);
                }
            } elseif ($village_info['urge_notice_type'] == 2) {
                if (!empty($now_user['openid'])) {
                    $templateNewsService = new TemplateNewsService();
                    $base_url = $serviceHouseVillage->base_url;
                    $href = cfg('site_url') . $base_url . 'pages/newCharge/pages/chargeDetail?village_id=' . $order_info['village_id'] . '&order_id=' . $order_info['id'];
                    
                    $dataoldmsg = [
                        'tempKey' => 'OPENTM400166399',
                        'dataArr' => [
                            'href' => $href,
                            'wecha_id' => $now_user['openid'],
                            'first' => '您的电动汽车充电结束，消费金额' . $amount_money . '元',
                            'keyword1' => '汽车充电状态提醒',
                            'keyword2' => '已发送',
                            'keyword3' => date('H:i'),
                            'remark' => '请点击查看详细信息！'
                        ]
                    ];
                    
                    //新的模板
                    $datamsg = [
                        'tempKey' => 'OPENTM407216824',
                        'dataArr' => [
                            'href' => $href,
                            'wecha_id' => $now_user['openid'],
                            'first' => '您的电动汽车充电结束，消费金额' . round_number($amount_money) . '元',
                            'keyword1' => '您的电动汽车充电结束，消费金额' . round_number($amount_money) . '元',
                            'keyword2' => $order_no,
                            'keyword3' => '充电结束',
                            'keyword4' => date('H:i'),
                            'remark' => '请点击查看详细信息！',
                            'oldTempMsg'=>$dataoldmsg
                        ]
                    ];
                    $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'], 0, 0, 1);
                }
            } else {
                if (!empty($now_user['openid'])) {
                    $templateNewsService = new TemplateNewsService();
                    $base_url = $serviceHouseVillage->base_url;
                    $href = cfg('site_url') . $base_url . 'pages/newCharge/pages/chargeDetail?village_id=' . $order_info['village_id'] . '&order_id=' . $order_info['id'];
                    
                    $dataoldmsg = [
                        'tempKey' => 'OPENTM400166399',
                        'dataArr' => [
                            'href' => $href,
                            'wecha_id' => $now_user['openid'],
                            'first' => '您的电动汽车充电结束，消费金额' . $amount_money . '元',
                            'keyword1' => '汽车充电状态提醒',
                            'keyword2' => '已发送',
                            'keyword3' => date('H:i'),
                            'remark' => '请点击查看详细信息！'
                        ]
                    ];
                    
                    //新的模板
                    $datamsg = [
                        'tempKey' => 'OPENTM407216824',
                        'dataArr' => [
                            'href' => $href,
                            'wecha_id' => $now_user['openid'],
                            'first' => '您的电动汽车充电结束，消费金额' . round_number($amount_money) . '元',
                            'keyword1' => '您的电动汽车充电结束，消费金额' . round_number($amount_money) . '元',
                            'keyword2' => $order_no,
                            'keyword3' => '已充电结束',
                            'keyword4' => date('H:i'),
                            'remark' => '请点击查看详细信息！',
                            'oldTempMsg'=>$dataoldmsg
                        ]
                    ];
                    $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'], 0, 0, 1);
                }
                if (!empty($now_user['phone'])) {
                    //发短信
                    $sms_data = array('type' => 'fee_notice');
                    $sms_data['uid'] = 0;
                    $sms_data['village_id'] = $order_info['village_id'];
                    $sms_data['mobile'] = $now_user['phone'];
                    $sms_data['sendto'] = 'user';
                    $sms_data['mer_id'] = 0;
                    $sms_data['store_id'] = 0;
                    $sms_data['nationCode'] = 86;
                    $sms_content = L_('尊敬的x1您好，您在x2电动汽车充电结束，消费金额x3元', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name'], 'x3' => $amount_money));
                    $sms_data['content'] = $sms_content;
                    $sms = (new SmsService())->sendSms($sms_data);
                }
            }
            $userInfo = $db_house_new_pile_user_money->getOne(['uid' => $order_info['uid'], 'business_id' => $order_info['village_id']]);
            if (empty($userInfo)) {
                fdump_api(['msg' => '充电结束上报-账户信息不存在', 'order_info' => $order_info, 'order_data' => $order_data], '$command_mqtt_err', 1);
                throw new \think\Exception("账户信息不存在");
            }
            $current_money = round_number($userInfo['current_money'] - $amount_money, 2);
            $res1 = $db_house_new_pile_user_money->saveOne(['uid' => $order_info['uid'], 'business_id' => $order_info['village_id']], ['current_money' => $current_money]);
            if ($res1) {
                $housePaidOrderRecordService= new HousePaidOrderRecordService();
                $housePaidOrderRecordService->carElePileOrderPayRecord($order_info['id'],$order_info['village_id'],$amount_money);
                $arr = [];
                $arr['uid'] = $order_info['uid'];
                $arr['order_id'] = $order_info['id'];
                $arr['order_type'] = 1;
                $arr['business_type'] = 1;
                $arr['business_id'] = $order_info['village_id'];
                $arr['type'] = 2;
                $arr['current_money'] = $userInfo['current_money'];
                $arr['money'] = $amount_money;
                $arr['after_price'] = $current_money;
                $arr['add_time'] = time();
                $arr['ip'] = get_client_ip();
                $arr['desc'] = '充电结算扣款';
                fdump_api(['msg' => '结单订单扣款记录', 'arr' => $arr], '$finishOrder',1);
                $db_house_new_pile_user_money_log->addOne($arr);
            }
        } else {
            fdump_api(['msg' => '充电结束上报-订单更新失败', 'order_info' => $order_info, 'order_data' => $order_data], '$command_mqtt_err', 1);
        }
    }

}