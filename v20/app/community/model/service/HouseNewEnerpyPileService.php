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
use app\community\model\db\HouseNewPilePayOrder;
use app\community\model\db\HouseNewPilePayOrderLog;
use app\community\model\db\HouseNewPileRefundOrder;
use app\community\model\db\HouseNewPileUserMoney;
use app\community\model\db\HouseNewPileUserMoneyLog;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillagePileEquipment;
use app\community\model\db\PlatOrder;
use app\community\model\db\User;
use app\community\model\service\HousePaidOrderRecordService;
use function think\crc166;
use function think\publish;
use tools;

class HouseNewEnerpyPileService
{
    public $result_code=['1'=>'电量用完', '2'=>'枪没插好', '3'=> '过电压', '4'=>'设备已经关闭', '5'=> '设备故障', '6'=>'平台下发结束充电','7'=>'电量充满停电', '8'=>'充电枪拔出停止充电', '9'=>'欠电压', '10'=> '电流过大', '11'=>'漏电', '12'=>'过温', '13'=>'急停', '14'=>'未接地'];
    public $end_result=['00'=>'无', '01'=>'设备编号不匹配', '02'=> '枪已在充电', '03'=>'设备故障', '04'=> '设备离线', '05'=>'未插枪'];
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
    ];

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

    public function setPileTimeAndCharge($data) {
        fdump_api(['data' => $data],'energy_pile/setPileTimeAndChargeLog',1);
        $arr=[];
        $arr['id']=$data['equipment_id'];
        $this->setPileTime($arr);
        $this->setPileChargeInfo($arr);
        return true;
    }
    
    /**
     * 直流充电桩开电回复
     * @author:zhubaodi
     * @date_time: 2022/10/10 11:28
     */
    public function startCharging_tcp($data){
        $keyH = date('H');
        $now_time = time();
        fdump_api(['data' => $data],'energy_pile/startCharging_tcp_log'.$keyH,1);
        $db_house_new_pile_pay_order = new HouseNewPilePayOrder();
        $db_house_new_pile_config = new HouseNewPileConfig();
        $service_user = new UserService();
        $whereOrder = ['type'=>1,'equipment_id' => $data['equipment_id'], 'socket_no' => (int)$data['socket_num'],'status'=>[0,1]];
        $order_info = $db_house_new_pile_pay_order->getFind($whereOrder);
        fdump_api(['whereOrder' => $whereOrder, 'order_info' => $order_info],'energy_pile/startCharging_tcp_log'.$keyH,1);
        if (empty($order_info)){
            return false;
        }
        if ($order_info['order_serial']!=$data['order_serial']){
            $whereOrder = ['type'=>1,'equipment_id' => $data['equipment_id'], 'order_serial' => $data['order_serial']];
            $order_info = $db_house_new_pile_pay_order->getFind($whereOrder);
            if (! $order_info) {
                fdump_api(['err' => '反馈订单不能匹配'],'energy_pile/startCharging_tcp_log'.$keyH,1);
                return false;
            }
        }
        $order_no=$order_info && $order_info['order_no'] ? $order_info['order_no']:'';
        $order_data['pay_time']=$now_time;
        if ($data['status']=='01'){
            $order_data['status']=2;
            $serviceHouseVillage = new HouseVillageService();
            $village_info=$serviceHouseVillage->getHouseVillageInfoExtend(['village_id'=>$order_info['village_id']],'urge_notice_type');
            $pile_config=$db_house_new_pile_config->getFind(['village_id'=>$order_info['village_id']]);
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
                    $sms = (new SmsService())->sendSms($sms_data);
                }
            }

        }else{
            $order_data['status']=1;
            $order_data['end_time']=$now_time;
            $order_data['end_result']=$this->end_result[$data['end_result']];
            $arr=[];
            $arr['id']=$data['equipment_id'];
            $this->setPileTime($arr);
            $this->setPileChargeInfo($arr);
            fdump_api(['arr' => $arr],'energy_pile/startCharging_tcp_log'.$keyH,1);
        }
        $res=$db_house_new_pile_pay_order->save_one(['id'=>$order_info['id']],$order_data);
        fdump_api(['res' => $res, 'order_data' => $order_data],'energy_pile/startCharging_tcp_log'.$keyH,1);
        if (isset($order_data['status']) && $order_data['status'] == 1) {
            // todo 开电成功 截停其他同设备插口订单(有相关消费信息的进行结算)
            $this->finishTcpOrder([], $order_info, $now_time, 0);
        }
        return  $res;
    }

    /**
     * 直流充电桩结束充电回复
     * @author:zhubaodi
     * @date_time: 2022/10/10 13:11
     */
    public function stopCharging_tcp($data){
        $db_house_new_pile_pay_order = new HouseNewPilePayOrder();
        $order_info = $db_house_new_pile_pay_order->getFind(['type'=>1,'equipment_id' => $data['equipment_id'], 'socket_no' => (int)$data['socket_num'],'status'=>2]);
        if (empty($order_info)){
            return false;
        }
        $order_data['status']=1;
        $order_data['end_time']=time();
        $res=$db_house_new_pile_pay_order->save_one(['id'=>$order_info['id']],$order_data);
        return  $res;
    }


    /**
     * 直流充电桩上报交易记录
     * @author:zhubaodi
     * @date_time: 2022/10/10 14:58
     */
    public function settlementOrder_tcp($data){
        $db_house_new_pile_pay_order = new HouseNewPilePayOrder();
        $db_house_new_pile_pay_order_log=new HouseNewPilePayOrderLog();
        $db_house_new_pile_config = new HouseNewPileConfig();
        $service_user = new UserService();
        fdump_api([$data],'carPile/settlementOrder_tcp',1);
        $order_info = $db_house_new_pile_pay_order->getFind(['type'=>1,'equipment_id' => $data['equipment_id'], 'socket_no' => (int)$data['socket_num'],'order_serial'=>$data['order_serial']]);
        if (empty($order_info)){
            return false;
        }
	fdump_api([$data,$order_info],'carPile/settlementOrder_tcp',1);
        $work_time=date('H:i');
       /* $time1=substr($work_time,0,2);
        $time2=substr($work_time,2,2);
        $work_time=$time1.':'.$time2;*/
        $order_no=$order_info['order_no'] ? $order_info['order_no']:'';
        $log_arr=[];
        $log_arr['equipment_id']=$data['equipment_id'];
        $log_arr['equipment_num']=$data['equipment_num'];
        $log_arr['socket_no']=(int)$data['socket_num'];
        $log_arr['order_id']=$order_info['id'];
        $log_arr['type']=1;
        $log_arr['status']=1;
        $log_arr['order_no']=$order_info['order_no'];
        $log_arr['order_serial']=$data['order_serial'];
       /* $log_arr['voltage']=$data['voltage'];
        $log_arr['electric_current']=$data['electric_current'];
        $log_arr['temperature']=$data['temperature'];*/
        $log_arr['continued_time']=$order_info['continued_time'];
        $log_arr['use_ele']=$data['sum_ele'];
        $log_arr['use_money']=$data['sum_price'];
        $log_arr['work_time']=$work_time;
        $log_arr['add_time']=time();
        $db_house_new_pile_pay_order_log->addOne($log_arr);
        fdump_api([$log_arr],'carPile/settlementOrder_tcp',1);
        //结算订单信息
        if ($data['sum_price']==$order_info['use_money']&&$data['sum_ele']==$order_info['use_ele']&&$order_info['status']==1&&$order_info['end_time']>100){
            $res=1; 
        }else{
            $order_data['use_money']=$data['sum_price'];
            $order_data['use_ele']=$data['sum_ele'];
            $order_data['status']=1;
            $order_data['end_time']=time();
            $res=$db_house_new_pile_pay_order->save_one(['id'=>$order_info['id']],$order_data);
            fdump_api([$order_data,$order_info['id'],$res],'carPile/settlementOrder_tcp',1); 
        }
        fdump_api([$order_info['id'],$res],'carPile/settlementOrder_tcp',1);
        if ($res>0){
            $serviceHouseVillage = new HouseVillageService();
            $village_info=$serviceHouseVillage->getHouseVillageInfoExtend(['village_id'=>$order_info['village_id']],'urge_notice_type');
            $pile_config=$db_house_new_pile_config->getFind(['village_id'=>$order_info['village_id']]);
            $now_user = $service_user->getUser($order_info['uid'],'uid');
            fdump_api([$now_user,$order_info['id'],$village_info['urge_notice_type']],'carPile/settlementOrder_tcp',1);
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
                    $sms_content = L_('尊敬的x1您好，您在x2电动汽车已完成充电，消费金额x3元', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name'], 'x3' =>round_number($data['sum_price'],2)));
                    $sms_data['content']=$sms_content;
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
                            'first' => '您的电动汽车已完成充电，消费金额'.round_number($data['sum_price'],2).'元',
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
                            'first' => '您的电动汽车已完成充电，消费金额'.round_number($data['sum_price'],2).'元',
                            'keyword1' => '您的电动汽车已完成充电，消费金额'.round_number($data['sum_price'],2).'元',
                            'keyword2' => $order_no,
                            'keyword3' => '已完成充电',
                            'keyword4' => date('H:i'),
                            'remark' => '请点击查看详细信息！',
                            'oldTempMsg'=>$dataoldmsg
                        ]
                    ];
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
                            'first' => '您的电动汽车已完成充电，消费金额'.round_number($data['sum_price'],2).'元',
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
                            'first' => '您的电动汽车已完成充电，消费金额'.round_number($data['sum_price'],2).'元',
                            'keyword1' => '您的电动汽车已完成充电，消费金额'.round_number($data['sum_price'],2).'元',
                            'keyword2' => $order_no,
                            'keyword3' => '已完成充电',
                            'keyword4' => date('H:i'),
                            'remark' => '请点击查看详细信息！',
                            'oldTempMsg'=>$dataoldmsg
                        ]
                    ];
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
                    $sms_content = L_('尊敬的x1您好，您在x2电动汽车已完成充电，消费金额x3元', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name'], 'x3' => round_number($data['sum_price'],2)));
                    $sms_data['content']=$sms_content;
                    $sms = (new SmsService())->sendSms($sms_data);
                }
            }

            $db_house_new_pile_user_money_log=new HouseNewPileUserMoneyLog();
            $db_house_new_pile_user_money=new HouseNewPileUserMoney();
            $userInfo=$db_house_new_pile_user_money->getOne(['uid'=>$order_info['uid'],'business_id'=>$order_info['village_id']]);
            if (empty($userInfo)){
                throw new \think\Exception("账户信息不存在");
            }
            $res1=$db_house_new_pile_user_money->saveOne(['uid'=>$order_info['uid'],'business_id'=>$order_info['village_id']],['current_money'=>($userInfo['current_money']-$data['sum_price'])]);
            if ($res1){
                $housePaidOrderRecordService= new HousePaidOrderRecordService();
                $housePaidOrderRecordService->carElePileOrderPayRecord($order_info['id'],$order_info['village_id'],$data['sum_price']);
                $arr=[];
                $arr['uid']=$order_info['uid'];
                $arr['order_id']=$order_info['id'];
                $arr['order_type']=1;
                $arr['business_type']=1;
                $arr['business_id']=$order_info['village_id'];
                $arr['type']=2;
                $arr['current_money']=$userInfo['current_money'];
                $arr['money']=$data['sum_price'];
                $arr['after_price']=($userInfo['current_money']-$data['sum_price']);
                $arr['add_time']=time();
                $arr['ip']=get_client_ip();
                $arr['desc']='充电结算扣款';
                $db_house_new_pile_user_money_log->addOne($arr);
            }
        }
        return  $res;
    }

    /**
     * 直流充电桩读取实时监测数据
     * @author:zhubaodi
     * @date_time: 2022/10/10 17:29
     */
    public function getPileRealTimeInfo($data){
        $db_house_new_pile_pay_order = new HouseNewPilePayOrder();
        $db_house_new_pile_pay_order_log=new HouseNewPilePayOrderLog();
        $db_house_new_pile_equipment_log=new HouseNewPileEquipmentLog();
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $db_house_new_pile_charge = new HouseNewPileCharge();
        $db_house_new_pile_config = new HouseNewPileConfig();
        $db_house_village=new HouseVillage();
        $houseMaintenanLog = new HouseMaintenanLog();
        $service_user = new UserService();
        if (empty($data['equipment_num'])){
            return  false;
        }
        $where=['equipment_num'=>$data['equipment_num'],'is_del'=>1];
        $equipment_info=$db_house_new_pile_equipment->getInfo($where);
        if (empty($equipment_info)){
            return false;
        }
        $serviceHouseVillage = new HouseVillageService();
        $village_info=$serviceHouseVillage->getHouseVillageInfoExtend(['village_id'=>$equipment_info['village_id']],'urge_notice_type');
        $pile_config=$db_house_new_pile_config->getFind(['village_id'=>$equipment_info['village_id']]);

        fdump_api([$data,$equipment_info],'carPile/getPileRealTimeInfo',1);
        $res=0;
        //状态 0x00：离线 0x01：故障 0x02：空闲 0x03：充电
        $temperature_start=0;
        $status_txt='';
        if ($data['status']=='03'){
            $order_info=$db_house_new_pile_pay_order->getFind(['equipment_id'=>$data['equipment_id'],'socket_no'=>(int)$data['socket_num'],'order_serial'=>$data['order_serial']]);
            if (!empty($order_info)){
                $order_no=$order_info['order_no'];
                $db_house_new_pile_user_money=new HouseNewPileUserMoney();
                $db_house_new_pile_command = new HouseNewPileCommand();
                $userInfo=$db_house_new_pile_user_money->getOne(['uid'=>$order_info['uid'],'business_id'=>$order_info['village_id']]);
                if (empty($userInfo)){
                    return false;
                }
                $db_house_new_pile_pay_order->save_one(['id'=>$order_info['id']],['use_ele'=>$data['use_ele'],'actual_ele'=>$data['use_ele'],'use_money'=>$data['use_money']]);
                $charge_info = $db_house_new_pile_charge->getFind(['village_id' => $order_info['village_id'], 'rule_id' => $order_info['charge_id'], 'status' => 1]);
                if (empty($charge_info)) {
                    return false;
                }
                if ($data['use_money']>=$userInfo['current_money']){
                    $str=$equipment_info['equipment_num'].'0'.$order_info['socket_no'];
                    $len=$this->numToHex($data['order_id'],3).'10036'.$str;
                    $checkResult=$this->wordStr($len);
                    $length = strlen($len)/2; // 长度
                    $length = $this->numToHex($length,2);
                    $content='68'.$length.$len.$checkResult;
                    $command = [
                        'sn' => $equipment_info['equipment_num'],
                        'cmd' => $content,
                    ];
                    $arr['command'] = \json_encode($command);
                    $arr['type'] = 1;
                    $arr['status'] = 1;
                    $arr['addtime'] = time();
                    $arr['equipment_id'] = $equipment_info['id'];
                    $arr['device_type'] = $equipment_info['type'];
                    $db_house_new_pile_command->addOne($arr);
                }
               //  print_r($charge_info);die;
                $work_time='';
               /* $charge_time = \json_decode($charge_info['charge_time'], true);
                if (!empty($charge_time)) {
                    foreach ($charge_time as $vc) {
                        $time_end = date('Y-m-d') . ' ' . $vc['time_end'];
                        if (strtotime($time_end) > (time()-8) && strtotime($time_end) < (time()+8)) {
                            $flag=1;
                            $work_time=$vc['time_end'];
                        }
                        $time_start = date('Y-m-d') . ' ' . $vc['time_start'];
                        if (strtotime($time_start) > (time()-8) && strtotime($time_start) < (time()+8)) {
                            $flag=1;
                            $work_time=$vc['time_start'];
                        }
                        
                    }
                }*/
                $flag=1;
                if (isset($flag)&&$flag==1){
                    $log_arr=[];
                    $log_arr['equipment_id']=$data['equipment_id'];
                    $log_arr['equipment_num']=$data['equipment_num'];
                    $log_arr['socket_no']=(int)$data['socket_num'];
                    $log_arr['order_id']=$order_info['id'];
                    $log_arr['order_no']=$order_info['order_no'];
                    $log_arr['order_serial']=$data['order_serial'];
                    $log_arr['voltage']=$data['voltage'];
                    $log_arr['electric_current']=$data['electric_current'];
                    $log_arr['power']=$data['voltage']*$data['electric_current'];
                    $log_arr['temperature']=$data['temperature'];
                    $log_arr['continued_time']=$data['continued_time']*60;
                    $log_arr['use_ele']=$data['use_ele'];
                    $log_arr['use_money']=$data['use_money'];
                    $log_arr['work_time']=$work_time;
                    $log_arr['add_time']=time();
                    $res=$db_house_new_pile_pay_order_log->addOne($log_arr);
                }
                if ($temperature_start==1){
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
                            $sms_content = L_('尊敬的x1您好，您在x2电动汽车所在设备有充电异常，请及时查看', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name']));
                            $sms_data['content']=$sms_content;
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
                            $sms = (new SmsService())->sendSms($sms_data);
                        }
                    }
                }
            }
        }
        if ($data['status']=='07'){
            $status=3;
            $socket_status=4;
            $status_txt='设备异常';
        }elseif($data['status']=='01'){
            $status=4;
            $socket_status=4;
            $status_txt='设备离线';
        }elseif($data['status']=='02'){
            $status=1;
            $socket_status=1;
        } else{
            $status=1;
            $socket_status=0;
        }
        if (isset($data['order_serial']) && $data['order_serial'] != '00000000000000000000000000000000') {
            $whereOrder0 = [
                'equipment_id' => $data['equipment_id'],
                'order_serial' => trim($data['order_serial']),
                'socket_no' => intval($data['socket_num']),
            ];
            $order_info0 = $db_house_new_pile_pay_order->getFind($whereOrder0);
        }
        if (isset($order_info0) && $order_info0['status']) {
            $order_info1 = $order_info0;
        } else {
            $order_info1=$db_house_new_pile_pay_order->getFind(['equipment_id'=>$data['equipment_id'],'socket_no'=>(int)$data['socket_num']]);
        }
        if ($order_info1['status']==2&&$data['status']!='03'){
            $db_house_new_pile_pay_order->save_one(['id'=>$order_info1['id']],['status'=>1,'end_time'=>time()]);
            $this->finishTcpOrder([], $order_info1, time(), 1);
        }
        if (!empty($status_txt)) {
            $where_log = [];
            $where_log['village_id'] = $equipment_info['village_id'];
            $where_log['device_id'] = $equipment_info['id'];
            $log_info = $houseMaintenanLog->get_one($where_log);
            $village_name = $db_house_village->getOne($equipment_info['village_id'], 'village_name,village_address');
            if (empty($log_info) && $status == 2) {
                $data_log = [];
                $data_log['village_id'] = $equipment_info['village_id'];
                $data_log['village_name'] = $village_name['village_name'];
                $data_log['device_id'] = $equipment_info['id'];
                $data_log['device_type'] = 8;
                $data_log['address'] = $village_name['village_address'];
                $data_log['device_name'] = $equipment_info['equipment_name'];
                $data_log['reason'] = $status_txt;
                $data_log['next_key'] = 30;
                $data_log['next_time'] = time();
                $data_log['add_time'] = time();
                $houseMaintenanLog->addOne($data_log);
            } elseif (!empty($log_info)) {
                if (!empty($log_info['next_key']) && $status == 1) {
                    $data_log = [];
                    $data_log['next_key'] = 0;
                    $where_log_save = ['id' => $log_info['id']];
                    $houseMaintenanLog->save_one($where_log_save, $data_log);
                } elseif (empty($log_info['next_key']) && $status == 2) {
                    $data_log = [];
                    $data_log['village_id'] = $equipment_info['village_id'];
                    $data_log['village_name'] = $village_name['village_name'];
                    $data_log['device_id'] = $equipment_info['id'];
                    $data_log['device_type'] = 8;
                    $data_log['address'] = $village_name['village_address'];
                    $data_log['device_name'] = $equipment_info['equipment_name'];
                    $data_log['reason'] = $status_txt;
                    $data_log['next_key'] = 30;
                    $data_log['next_time'] = time();
                    $data_log['add_time'] = time();
                    $houseMaintenanLog->addOne($data_log);
                }

            }
        }
        $res1=$db_house_new_pile_equipment->save_one(['id'=>$data['equipment_id'],'is_del'=>1],['status'=>$status,'last_heart_time'=>time()]);
        fdump_api([$status,$res1],'carPile/getPileRealTimeInfo',1);
        $log_arr=[];
        $log_arr['equipment_id']=$data['equipment_id'];
        $log_arr['equipment_num']=$data['equipment_num'];
        $log_arr['status']=$socket_status;//枪头状态 0 空闲 1 插枪 2 充电中 3 充满 4 异常
        $log_arr['type']=1;
        $log_arr['voltage']=$data['voltage'];
        $log_arr['electric_current']=$data['electric_current'];
        $log_arr['temperature']=$data['temperature'];
        $log_arr['power']=$data['voltage']*$data['electric_current'];
        $log_arr['add_time']=time();
        fdump_api([$log_arr],'carPile/getPileRealTimeInfo',1);
        $res=$db_house_new_pile_equipment_log->addOne($log_arr);
        fdump_api([$res,$log_arr],'carPile/getPileRealTimeInfo',1);
        $where_type=[
            ['type','=',1],
            ['is_del','=',1],
            ['last_heart_time','<',time()-900]
        ];
        $db_house_new_pile_equipment->save_one($where_type,['status'=>3]);
       /* $equipment_list=$db_house_new_pile_equipment->getList(['type'=>1,'is_del'=>1],'id');
        if (!empty($equipment_list)){
            $equipment_list=$equipment_list->toArray();
        }
        if (!empty($equipment_list)){
            foreach ($equipment_list as $ev){
                $logInfo=$db_house_new_pile_equipment_log->getFind(['equipment_id'=>$ev['id']]);
                if (!empty($logInfo)&&$logInfo['add_time']-1800<time()){
                    $db_house_new_pile_equipment->save_one(['id'=>$ev['id']],['status'=>3]);
                }
            }
        }*/
        return $res;
    }

    /**
     * 直流充电桩离线卡数据同步到设备上
     * @author:zhubaodi
     * @date_time: 2022/10/10 17:29
     */
    public function synchroPileCardNo($data){

    }

    /**
     * 直流充电桩离线卡数据查询
     * @author:zhubaodi
     * @date_time: 2022/10/10 17:29
     */
    public function getPileCardNoInfo($data){

    }
    
    /**
     * 直流充电桩离线卡数据从设备上删除
     * @author:zhubaodi
     * @date_time: 2022/10/10 17:29
     */
    public function delPileCardNo($data){

    }

    /**
     * 直流充电桩对时设置
     * @author:zhubaodi
     * @date_time: 2022/10/10 17:29
     */
    public function setPileTime($data){
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $db_house_new_pile_command = new HouseNewPileCommand();
        $equipment_info = $db_house_new_pile_equipment->getInfo(['id' => $data['id'],'type'=>1, 'status' => 1]);
       //  $equipment_info['type']=1;
        if (empty($equipment_info)){
            return false;
        }
        // 写入指令表
        $arr = [];
        $content='';
        if ($equipment_info['type']==1){
            $time=$this->timeToCp56Time2a(time());
            $str=$equipment_info['equipment_num'].$time;
            $len=rand(1000,9999).'0056'.$str;
            $checkResult=$this->wordStr($len);
            $length = strlen($len)/2; // 长度
            $length = dechex($length);
            $content='68'.$length.$len.$checkResult;
        }
        if (empty($content)){
            return  false;
        }
        $command = [
            'sn' => $equipment_info['equipment_num'],
            'cmd' => $content,
        ];
        $arr['command'] = \json_encode($command);
        $arr['type'] = 1;
        $arr['equipment_id'] = $data['id'];
        $arr['status'] = 1;
        $arr['addtime'] = time();
        $arr['device_type'] = $equipment_info['type'];
        $id = $db_house_new_pile_command->addOne($arr);
        return $id;
    }

    /**
     * 直流充电桩计费模型设置
     * @author:zhubaodi
     * @date_time: 2022/10/10 17:29
     */
    public function setPileChargeInfo($data){
        $db_house_new_pile_charge=new HouseNewPileCharge();
        $db_house_new_pile_equipment = new HouseNewPileEquipment();
        $db_house_new_pile_command = new HouseNewPileCommand();
        $equipment_info = $db_house_new_pile_equipment->getInfo(['id' => $data['id'],'type'=>1, 'is_del' => 1]);
        if (empty($equipment_info)){
            return false;
        }
        $charge_info = $db_house_new_pile_charge->getFind(['village_id' => $equipment_info['village_id'], 'rule_id' => $equipment_info['charge_id'], 'status' => 1]);
        if (empty($charge_info)) {
            return false;
        }
        $charge_id=$this->numToHex($charge_info['id'],4);
        $charge_time=json_decode($charge_info['charge_time'],JSON_UNESCAPED_UNICODE);
        $charge_1=json_decode($charge_info['charge_1'],JSON_UNESCAPED_UNICODE);
        $charge_2=json_decode($charge_info['charge_2'],JSON_UNESCAPED_UNICODE);
        $charge_3=json_decode($charge_info['charge_3'],JSON_UNESCAPED_UNICODE);
        $charge_4=json_decode($charge_info['charge_4'],JSON_UNESCAPED_UNICODE);
        $charge_1_ele=$this->numToBinHex($charge_1['charge_ele']*100000,8);
        $charge_2_ele=$this->numToBinHex($charge_2['charge_ele']*100000,8);
        $charge_3_ele=$this->numToBinHex($charge_3['charge_ele']*100000,8);
        $charge_4_ele=$this->numToBinHex($charge_4['charge_ele']*100000,8);
        $charge_1_serve=$this->numToBinHex($charge_1['charge_serve']*100000,8);
        $charge_2_serve=$this->numToBinHex($charge_2['charge_serve']*100000,8);
        $charge_3_serve=$this->numToBinHex($charge_3['charge_serve']*100000,8);
        $charge_4_serve=$this->numToBinHex($charge_4['charge_serve']*100000,8);
        $str=$equipment_info['equipment_num'].$charge_id.$charge_1_ele.$charge_1_serve.$charge_2_ele.$charge_2_serve.$charge_3_ele.$charge_3_serve.$charge_4_ele.$charge_4_serve.'00';
        $charge_time_arr=$this->pile_time;
        $charge_num=[];
        foreach ($charge_time as $v){
            foreach ($charge_time_arr as $k=>$vv){
                if ($v['time_start']<=$vv&&$v['time_end']>$vv){
                    $charge_num[$k]=$v['money'];
                    unset($charge_time_arr[$k]);
                }
            }
        }
        if ($charge_info['price_set_value']!='-1'&&!empty($charge_time_arr)){
            foreach ($charge_time_arr as $k=>$vv1){
                $charge_num[$k]=$charge_info['price_set_value'];
                unset($charge_time_arr[$k]);
            }
        }
        if (!empty($charge_num)){
            ksort($charge_num);
            foreach ($charge_num as $vs){
                $str.=$this->numToHex($vs,2);
            }
        }
        $len=rand(1000,9999).'0058'.$str;
        $checkResult=$this->wordStr($len);
        $length = strlen($len)/2; // 长度
        $length = dechex($length);
        $msg='68'.$length.$len.$checkResult;
        $command = [
            'sn' => $equipment_info['equipment_num'],
            'cmd' => $msg,
        ];
        $arr['command'] = \json_encode($command);
        $arr['type'] = 1;
        $arr['status'] = 1;
        $arr['equipment_id'] = $data['id'];
        $arr['addtime'] = time();
        $arr['device_type'] = $equipment_info['type'];
        $id = $db_house_new_pile_command->addOne($arr);
        return $id;
    }

    /**
     * $time  unix时间戳
     * @author:zhubaodi
     * @date_time: 2022/9/16 14:23
     */
   public function timeToCp56Time2a($time){
        if (empty($time)){
            $time=time();
        }
        $week=date("w",$time);
        $date_info=date('y-m-d H:i:s',$time);
        $date_arr=explode(' ',$date_info);
        if (count($date_arr)!=2){
            return false;
        }
        $date_ymd=explode('-',$date_arr[0]);
        if (count($date_ymd)!=3){
            return false;
        }
        $date_his=explode(':',$date_arr[1]);
        $date_his[2]=$this->numToHex($date_his[2]*1000,4);
        if (count($date_his)!=3){
            return false;
        }
        $day_m=$this->NumToBin($date_ymd[2],5,10,2);
        $day_w=$this->NumToBin($week,3,10,2);
        $day=$this->NumToBin($day_w.$day_m,2,2,16);
        $date_s1=substr($date_his[2],0,2);
        $date_s2=substr($date_his[2],2,2);
        $str=$date_s2.$date_s1.$this->numToHex($date_his[1],2).$this->numToHex($date_his[0],2).$day.$this->numToHex($date_ymd[1],2).$this->numToHex($date_ymd[0],2);
        return  $str;
    }

    /**
     * 将数字转换成16进制，长度不够的补0
     * @param $num
     * @param $len
     * @return string
     */
    function numToHex($num,$len)
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
     * 将数字转换成2进制，长度不够的补0
     * @param $num
     * @param $start  //数字类型
     * @param $end  //待转换数字类型
     * @param $len
     * @return string
     */
    function NumToBin($num,$len,$start=10,$end=2)
    {
        $num_hex = base_convert($num,$start,$end);
        if(strlen($num_hex) < $len){
            $add = '';
            for($i=0;$i<$len-strlen($num_hex);$i++){
                $add .= '0';
            }
            $num_hex = $add.$num_hex;
        }
        return $num_hex;
    }


    public function finishTcpOrder($equipment_info, $origin_order_info, $now_time = 0, $isCurrent = 1)
    {
        // 结束tcp汽车充电桩并结算
        if (isset($origin_order_info['end_type']) && $origin_order_info['end_type'] == 1) {
            fdump_api(['msg' => '结单订单失败-订单已结单', 'equipment_info' => $equipment_info, '$origin_order_info' => $origin_order_info, 'isCurrent' => $isCurrent], 'energy_pile/finishOrderLog', 1);
            return false;
        }
        if (!$now_time) {
            $now_time = time();
        }
        if (empty($equipment_info)) {
            $db_house_new_pile_equipment = new HouseNewPileEquipment();
            $equipment_info = $db_house_new_pile_equipment->getInfo(['village_id' => $origin_order_info['village_id'], 'id' => $origin_order_info['equipment_id']]);
            if ($equipment_info && !is_array($equipment_info)) {
                $equipment_info = $equipment_info->toArray();
            }
        }
        fdump_api(['msg' => '结单订单', 'equipment_info' => $equipment_info, '$origin_order_info' => $origin_order_info, 'now_time' => $now_time, 'isCurrent' => $isCurrent], 'energy_pile/finishOrderLog', 1);
   
        $db_house_new_pile_pay_order_log = new HouseNewPilePayOrderLog();
        $db_house_new_pile_pay_order = new HouseNewPilePayOrder();
        if ($isCurrent == 1) {
            $order_info = $origin_order_info;
            if ($order_info && !is_array($order_info)) {
                $order_info = $order_info->toArray();
            }
            if (empty($order_info)) {
                fdump_api(['msg' => '结单订单失败-没有符合条件订单', 'equipment_info' => $equipment_info, 'origin_order_info' => $origin_order_info, 'isCurrent' => $isCurrent], 'energy_pile/finishOrderLog', 1);
                return false;
            }
            // 当前订单就查询下这个订单的历史进行清算
            $field = 'id, use_money, use_ele, work_time';
            $order_log = $db_house_new_pile_pay_order_log->getFind(['order_id' => $order_info['id']], $field, 'add_time DESC,id DESC');
            if ($order_log && !is_array($order_log)) {
                $order_log = $order_log->toArray();
            }
            fdump_api(['msg' => '1结单订单消耗', 'order_log' => $order_log, 'order_info' => $order_info, 'sql' => $db_house_new_pile_pay_order_log->getLastSql()], 'energy_pile/finishOrderLog', 1);
            if (empty($order_log) || !isset($order_log['use_money_total']) || !$order_log['use_money_total']) {
                fdump_api(['msg' => '结单订单失败-订单没有相关记录不进行结算', 'equipment_info' => $equipment_info, 'order_info' => $order_info, 'isCurrent' => $isCurrent], 'energy_pile/finishOrderLog', 1);
                return false;
            }
            $amount_money = round_number($order_log['use_money'], 2);
            $use_ele = round_number($order_log['use_ele'], 2);
        } else {
            $whereOrder = [];
            $whereOrder[] = ['id', '<>', $origin_order_info['id']];
            $whereOrder[] = ['end_type', '=', 0];
            $whereOrder[] = ['socket_no', '=', $origin_order_info['socket_no']];
            $whereOrder[] = ['equipment_id', '=', $equipment_info['id']];
            $whereOrder[] = ['status', 'in', [0, 2]];
            $order_list = $db_house_new_pile_pay_order->get_list($whereOrder);
            if ($order_list && !is_array($order_list)) {
                $order_list = $order_list->toArray();
            }
            if (empty($order_list)) {
                fdump_api(['msg' => '结单订单失败-没有符合条件订单', 'equipment_info' => $equipment_info, 'whereOrder' => $whereOrder, 'isCurrent' => $isCurrent], 'energy_pile/finishOrderLog', 1);
                return false;
            }
            foreach ($order_list as $origin_order_info) {
                $this->finishTcpOrder($equipment_info, $origin_order_info, $now_time, 1);
            }
            return true;
        }
        fdump_api(['msg' => '结单订单消耗', 'amount_money' => $amount_money, 'use_ele' => $use_ele, 'order_info' => $order_info], 'energy_pile/finishOrderLog', 1);
        if ($amount_money < 0 || $use_ele < 0) {
            return false;
        }
        $db_house_new_pile_user_money_log = new HouseNewPileUserMoneyLog();
        $whereReplace = [
            'order_type' => 1,
            'type' => 2,
            'business_type' => 1,
            'order_id' => $order_info['id'],
            'uid' => $order_info['uid'],
        ];
        $uniqueInfo = $db_house_new_pile_user_money_log->getOne($whereReplace);
        if ($uniqueInfo && isset($uniqueInfo['id']) && $uniqueInfo['id']) {
            fdump_api(['msg' => '订单已经进进行了结算避免2次结算'], 'energy_pile/finishOrderLog', 1);
            return false;
        }
        
        $order_no=$origin_order_info && $origin_order_info['order_no'] ? $origin_order_info['order_no']:'';
        $db_house_new_pile_config = new HouseNewPileConfig();
        //结算订单信息
        $order_data = [];
        $order_data['use_money'] = $amount_money;
        $order_data['continued_time'] = $now_time - $order_info['pay_time'];
        $order_data['use_ele'] = $use_ele;
        $order_data['status'] = 1;
        $order_data['end_time'] = time();
        $order_data['end_type'] = 1;
        fdump_api(['msg' => '结单订单更改记录', 'order_data' => $order_data], 'energy_pile/finishOrderLog', 1);
        $res111 = $db_house_new_pile_pay_order->save_one(['id' => $order_info['id']], $order_data);
        if ($res111 > 0) {
            $service_user = new UserService();
            $serviceHouseVillage = new HouseVillageService();
            $village_info = $serviceHouseVillage->getHouseVillageInfoExtend(['village_id' => $order_info['village_id']], 'urge_notice_type');
            $pile_config = $db_house_new_pile_config->getFind(['village_id' => $order_info['village_id']]);
            $now_user = $service_user->getUser($order_info['uid'], 'uid');
            fdump_api([$now_user, $order_info['id'], $village_info['urge_notice_type']], 'energy_pile/finishOrderLog', 1);
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
                    $sms_content = L_('尊敬的x1您好，您在x2电动汽车已完成充电，消费金额x3元', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name'], 'x3' => $amount_money));
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
                            'first' => '您的电动汽车已完成充电，消费金额' . $amount_money . '元',
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
                            'first' => '您的电动汽车已完成充电，消费金额' . round_number($amount_money) . '元',
                            'keyword1' => '您的电动汽车已完成充电，消费金额' . round_number($amount_money) . '元',
                            'keyword2' => $order_no,
                            'keyword3' => '已完成充电',
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
                            'first' => '您的电动汽车已完成充电，消费金额' . $amount_money . '元',
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
                            'first' => '您的电动汽车已完成充电，消费金额' . $amount_money . '元',
                            'keyword1' => '您的电动汽车已完成充电，消费金额' . $amount_money . '元',
                            'keyword2' => $order_no,
                            'keyword3' => '已完成充电',
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
                    $sms_content = L_('尊敬的x1您好，您在x2电动汽车已完成充电，消费金额x3元', array('x1' => $now_user['nickname'], 'x2' => $pile_config['pile_name'], 'x3' => $amount_money));
                    $sms_data['content'] = $sms_content;
                    $sms = (new SmsService())->sendSms($sms_data);
                }
            }

            $db_house_new_pile_user_money = new HouseNewPileUserMoney();
            $userInfo = $db_house_new_pile_user_money->getOne(['uid' => $order_info['uid'], 'business_id' => $order_info['village_id']]);
            if (empty($userInfo)) {
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
                $db_house_new_pile_user_money_log->addOne($arr);
                fdump_api(['msg' => '充电结算记录', 'arr' => $arr], 'energy_pile/finishOrderLog', 1);
            }
        } else {
            fdump_api(['msg' => '充电结束上报-订单更新失败', 'order_info' => $order_info, 'order_data' => $order_data], 'energy_pile/finishOrderLog', 1);
        }
    }
}