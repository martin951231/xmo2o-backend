<?php

/**
 * 电表TCP交互 376.1 国标
 * Created by vscode.
 * Author: Jaty
 * Date Time: 2021/4/2 13:46
 * 注意
 * 设备涉及金额的 单位为分
 * 设备涉及时间的 时间戳以2015年1月1日00:00:00 开始计算
 * 设备涉及电量的 数值除以 3600000 后为对应度数（Kwh）
 */
$database_config = include '../../../conf/db.php';
//创建Server对象，监听 127.0.0.1:9501 端口
$server = new Swoole\Server('0.0.0.0', 9502);
$server->set([
    'daemonize'      => false,
    'dispatch_mode' => 2,
//    'log_file' => 'log_file.log',
]);
//监听连接进入事件
$server->on('Connect', function ($server, $fd) {
    addNote('note_connect','connect',$fd,['type'=>'connect request']);
});

use Swoole\Coroutine\MySQL;
use function Swoole\Coroutine\run;
$start_mysql = false;
$swoole_mysql = '';
//监听数据接收事件
$server->on('Receive', function ($server, $fd, $reactor_id, $data) {
    $data = clearFrame(bin2hex($data));
    echo date('Y-m-d H:i:s', time()) . "\n";
    echo $data . "\n";
    echo 'data------'.$data . "\n";
    echo "\n";
    $frame_data = '';
    $header_code = getString($data,26,4);
    if ($header_code != '3002' && $header_code != '3004') {
        $header_code = '3002';
    }
    $function_code = getString($data,30,4);
    $frame_type = getString($data,34,2);
    $device_id = getString($data,4,16);
    $random_number = getString($data,20,4);
    $deviceDateTimeStart = 1420041600;// 时间初始值 2015年1月1日00:00:00
    //addNote('note_'.$function_code.'_','info',$fd.':'.$device_id,['line'=>__LINE__,'header_code'=>$header_code,'equipment_num'=>$device_id,'frame_type'=>$frame_type,'type'=>'pile frame request','data'=>$data]);
    if($function_code == '0204'){     //充电桩上传终端设备版本号
        $hardware = hexToString(getString($data,40,hexdec(getString($data,38,2))*2));
        $software = hexToString(getString($data,40+hexdec(getString($data,38,2))*2+2,hexdec(getString($data,40+hexdec(getString($data,38,2))*2,2))*2));
        $frame_data = $header_code . '0204040101';
        if ($header_code == '3004') {
            run_mysql('update pigcms_house_village_pile_equipment set status=1, device_family=1, fd='.$fd.',hardware_version='."'$hardware'".',software_version='."'$software'".' where equipment_num ='."'$device_id'".' and is_del = 1');
        } else {
            run_mysql('update pigcms_house_village_pile_equipment set status=1, device_family=0, fd='.$fd.',hardware_version='."'$hardware'".',software_version='."'$software'".' where equipment_num ='."'$device_id'".' and is_del = 1');
        }
    } elseif ($function_code == '020e'){       //充电桩上传终端设备心跳
        $frame_data = $header_code . '020e040101';
        $device_info = run_mysql('select port_status,status,statusRemark from pigcms_house_village_pile_equipment where (equipment_num = '."'$device_id'".' and is_del = 1) limit 1');
        if ($device_info && isset($device_info[0]) &&  isset($device_info[0]['statusRemark']) && $device_info[0]['statusRemark']=='closeServer' && $device_info[0]['status']==2) {
            run_mysql('update pigcms_house_village_pile_equipment set fd='.$fd.',last_heart_time='.time().',statusRemark="",status=1 where equipment_num = '."'$device_id'".' and is_del = 1');
        } else {
            run_mysql('update pigcms_house_village_pile_equipment set fd='.$fd.',last_heart_time='.time().' where equipment_num = '."'$device_id'".' and is_del = 1');
        }
//        pileCmdSend($device_id,$fd,$server,$header_code); // 心跳指令先暂停
    }elseif ($function_code == '0207'){            //0x0207 实时温度
        $frame_data = $header_code . '0207040101';
        $dataArrs = explode($header_code . '02070404',$data);
        $note_query_order_param = [
            'line'=>__LINE__,'msg' => '0207实时温度','data' => $data,'dataArrs' => $dataArrs,
        ];
       // addNote('note_warning_temperature','info',$fd.':'.$device_id,$note_query_order_param);
    }elseif ($function_code == '0208'){            //0x0208 设备报警
        $frame_data = $header_code . '0208040101';
        $dataArrs = explode($header_code . '02080401',$data);
        $dataVal = $dataArrs[1];
        if($dataVal == '01'){
            $reason = '低温报警';
        } elseif ($dataVal == '81'){
            $reason = '解除低温报警';
        } elseif ($dataVal == '02'){
            $reason = '高温报警';
        } elseif ($dataVal == '82'){
            $reason = '解除高温报警';
        } elseif ($dataVal == '03'){
            $reason = '过压报警';
        } elseif ($dataVal == '83'){
            $reason = '解除过压报警';
        } elseif ($dataVal == '04'){
            $reason = '欠压报警';
        } elseif ($dataVal == '84'){
            $reason = '解除欠压报警';
        } elseif ($dataVal == '05'){
            $reason = '烟雾报警';
        } elseif ($dataVal == '85'){
            $reason = '解除烟雾报警';
        } else {
            $reason = '';
        }
        $note_query_order_param = [
            'line'=>__LINE__,'msg' => '0208设备报警','data' => $data,'dataArrs' => $dataArrs,'dataVal' => $dataVal,'reason' => $reason,
        ];
        addNote('note_warning'.date('H'),'info',$fd.':'.$device_id,$note_query_order_param);
    }elseif ($function_code == '0209'){            //0x0209 设备故障
        $frame_data = $header_code . '0209040101';
        $dataArrs = explode($header_code . '02090401',$data);
        $dataVal = $dataArrs[1];
        if($dataVal == '39' || $dataVal == '57'){
            $reason = 'Flash故障';
        } elseif ($dataVal == '3a' || $dataVal == '58'){
            $reason = 'Eeprom故障';
        } elseif ($dataVal == '3b' || $dataVal == '59'){
            $reason = '刷卡芯片故障';
        } elseif ($dataVal == '3e' || $dataVal == '62'){
            $reason = '设备暂停工作';
        } elseif ($dataVal == '3f' || $dataVal == '63'){
            $reason = '设备未标定';
        } elseif ($dataVal == '42' || $dataVal == '66'){
            $reason = '485故障';
        } elseif ($dataVal == '4c' || $dataVal == '76'){
            $reason = 'sim卡故障';
        } else {
            $reason = '';
        }
        $note_query_order_param = [
            'line'=>__LINE__,'msg' => '0209设备故障','data' => $data,'dataArrs' => $dataArrs,'dataVal' => $dataVal,'reason' => $reason,
        ];
        addNote('note_fault'.date('H'),'info',$fd.':'.$device_id,$note_query_order_param);
    }elseif ($function_code == '0302' || $function_code == '0304' || $function_code == '0305' || $function_code == '0307'){
        //0x0302 储值卡充电订单  0x0304 免费充电订单 0305 投币充电订单 0x0307 本地包月充电订单
        $begin = 38;
        if ($function_code == '0304') {
            $order_no = getString($data,$begin,16);
            $frame_data = $header_code . '0304040901'.$order_no;
            $begin = $begin + 16;
            $msg = '0304免费充电订单';
        } else if($function_code == '0302') {
            $order_no = getString($data,$begin,4);
            $frame_data = $header_code . '0302040301'.$order_no;
            $begin = $begin + 4;
            $msg = '0302储值卡充电订单';
        } else if($function_code == '0305') {
            $order_no = getString($data,$begin,4);
            $frame_data = $header_code . '0305040301'.$order_no;
            $begin = $begin + 4;
            $msg = '0305投币充电订单';
        } else {
            $order_no = getString($data,$begin,4);
            $frame_data = $header_code . '0307040301'.$order_no;
            $begin = $begin + 4;
            $msg = '0307本地包月充电订单';
        }
        $port = hexdec(getString($data,$begin,2));
        $begin = $begin + 2;
        $use_ele = sprintf("%.2f",hexdec(getString($data,$begin,8))/3600000);
        $begin = $begin + 8;
        $use_time = hexdec(getString($data,$begin,8));
        $begin = $begin + 8;
        $card_num = getString($data,$begin,32);
        $begin = $begin + 32;
        $use_money = hexdec(getString($data,$begin,4))/100;
        $begin = $begin + 4;
        $remain_money = hexdec(getString($data,$begin,8))/100;
        $begin = $begin + 8;
        $max_power = hexdec(getString($data,$begin,8));
        $begin = $begin + 8;
        $end_power = hexdec(getString($data,$begin,8));
        $begin = $begin + 8;
        $end_time = hexdec(getString($data,$begin,8));
        $begin = $begin + 8;
        $end_reason = getString($data,$begin,2);
        if($end_reason == '01'){
            $reason = '充电时间/电量用完';
        } elseif ($end_reason == '02'){
            $reason = '过载';
        } elseif ($end_reason == '03'){
            $reason = '充满';
        } elseif ($end_reason == '04'){
            $reason = '终止';
        } elseif ($end_reason == '05'){
            $reason = '过充';
        } elseif ($end_reason == '06'){
            $reason = '通道故障';
        } elseif ($end_reason == '07'){
            $reason = '空载';
        } elseif ($end_reason == '08'){
            $reason = '退款';
        } elseif ($end_reason == '09'){
            $reason = '电量耗尽';
        } elseif ($end_reason == '10'){
            $reason = '电表故障';
        } elseif ($end_reason == '11'){
            $reason = 'Flash故障';
        } elseif ($end_reason == '12'){
            $reason = '显示屏故障';
        } elseif ($end_reason == '13'){
            $reason = '插枪故障';
        } elseif ($end_reason == '14'){
            $reason = '计费模型存储已用完';
        } else{
            $reason = '充电时间/电量用完';
        }
        $note_end_order_param = [
            'line'=>__LINE__,'msg' => $msg,'order_no' => $order_no,'card_num'=>$card_num,'port' => $port,'use_ele' => $use_ele,
            'use_time'=>$use_time,'use_money'=>$use_money,'max_power' => $max_power,'end_power' => $end_power,'end_time' => $end_time,
            'end_reason'=>$end_reason,'reason' => $reason,'remain_money' => $remain_money,
            'equipment_num'=>$device_id,'type'=>'order info'
        ];
        //addNote('card_note_end_order','info',$fd.':'.$device_id,$note_end_order_param);
    }elseif ($function_code == '0105'){            //充电桩设置消费标准结果上报
        if(getString($data,38,2) == '01'){         //成功

        }else{                                                            //失败

        }
    }elseif ($function_code == '0101'){            //充电桩设置极限功率结果上报
        if(getString($data,38,2) == '01'){         //成功

        }else{                                                            //失败

        }
    } elseif ($function_code == '020d') {  // 充电桩订单查询
        $dataArrs = explode($header_code . '020d0120',$data);
        $dataVal = $dataArrs[1];
        $judge = getString($dataVal,0,2);
        $dataType = getString($dataVal,2,2);
        $order_no = getString($dataVal,4,16);
        $port = hexdec(getString($dataVal,20,2));
        $use_ele = sprintf("%.2f",hexdec(getString($dataVal,22,8))/3600000);
        $use_time = hexdec(getString($dataVal,30,8));
        $max_power = hexdec(getString($dataVal,38,8));
        $end_power = hexdec(getString($dataVal,46,8));
        $end_time = hexdec(getString($dataVal,54,8));
        $end_reason = getString($dataVal,62,2);
        if($end_reason == '01'){
            $reason = '充电时间/电量用完';
        } elseif ($end_reason == '02'){
            $reason = '过载';
        } elseif ($end_reason == '03'){
            $reason = '充满';
        } elseif ($end_reason == '04'){
            $reason = '终止';
        } elseif ($end_reason == '05'){
            $reason = '过充';
        } elseif ($end_reason == '06'){
            $reason = '通道故障';
        } elseif ($end_reason == '07'){
            $reason = '空载';
        } elseif ($end_reason == '08'){
            $reason = '退款';
        } elseif ($end_reason == '09'){
            $reason = '电量耗尽';
        } elseif ($end_reason == '10'){
            $reason = '电表故障';
        } elseif ($end_reason == '11'){
            $reason = 'Flash故障';
        } elseif ($end_reason == '12'){
            $reason = '显示屏故障';
        } elseif ($end_reason == '13'){
            $reason = '插枪故障';
        } elseif ($end_reason == '14'){
            $reason = '计费模型存储已用完';
        } else{
            $reason = '充电时间/电量用完';
        }
        $order_info = run_mysql('select id,continued_time,use_ele,use_money,village_id,pay_type from pigcms_house_village_pile_pay_order where order_no = '."'$order_no'");
        $note_query_order_param = [
            'line'=>__LINE__,'data' => $data,'judge' => $judge,'dataVal' => $dataVal,'order_no' => $order_no,'port' => $port,'use_ele' => $use_ele,'dataType' => $dataType,
            'use_time'=>$use_time,'max_power' => $max_power,'end_power' => $end_power,'end_time' => $end_time,
            'end_reason'=>$end_reason,'reason' => $reason,'order_info' => $order_info[0],
            'equipment_num'=>$device_id,'type'=>'order info'
        ];
        addNote('note_query_order'.date('H'),'info',$fd.':'.$device_id,$note_query_order_param);
        if ($judge=='01') {
            $time = time();
            if (isset($order_info[0])&&$order_info[0]['id']) {
                $orderId = $order_info[0]['id'];
                $refund_order_info = run_mysql('select * from pigcms_house_village_pile_refund_order where order_id = '."'$orderId'");
                if ($refund_order_info&&$refund_order_info[0]&&$refund_order_info[0]['refund_id']&&$refund_order_info[0]['refundType']!=2) {
                    //addNote('note_query_order_refund','info',$fd.':'.$device_id,['line'=>__LINE__,'refund_order_info'=> $refund_order_info]);
                } elseif(!empty($order_info)&&$order_info[0]){
                    if($order_info[0]['pay_type'] != 3 && $order_info[0]['pay_type'] != 10){
                        $res = run_mysql('update pigcms_house_village_pile_pay_order set end_time='.$time.',status=1,end_result='."'$reason'".',end_power='."'$end_power'".',continued_time='."'$use_time'".',use_ele='."'$use_ele'".',notificationTxt='."'$data'".' where order_no ='."'$order_no'");
                        //addNote('note_query_order_update','info',$fd.':'.$device_id,['line'=>__LINE__,'res' => $res]);
                        $config_info = run_mysql('select * from pigcms_house_village_pile_config where village_id='.$order_info[0]['village_id']);
                        if(!empty($config_info)){
                            $device_info = run_mysql('select work_type from pigcms_house_village_pile_equipment where equipment_num = '."'$device_id'".' and is_del = 1');
                            if(!empty($device_info)){
                                $return_money = 0;
                                if($device_info[0]['work_type'] == 1){         //计时版充电桩
                                    if($config_info[0]['low_consume_time']*60 >= $use_time){                //全额退款
                                        $return_money = $order_info[0]['use_money'];
                                    }elseif($config_info[0]['des_time_level'] >= 0){                                      //部分退款
                                        $need_pay_time = get_actual_use_time($config_info[0]['des_time_level'],$use_time);
                                        $return_money = sprintf("%.2f", $order_info[0]['use_money'] - $order_info[0]['use_money']/($order_info[0]['continued_time']/$need_pay_time));
                                    }
                                }else{                                                                      //计量版充电桩
                                    if($config_info[0]['low_consume_power'] >= $use_ele){                     //全额退款
                                        $return_money = $order_info[0]['use_money'];
                                    }elseif($config_info[0]['des_power_level'] >= 0){                                  //部分退款
                                        $need_pay_ele = get_actual_use_ele($config_info[0]['des_power_level'],$use_ele);
                                        $return_money = sprintf("%.2f", $order_info[0]['use_money'] - $order_info[0]['use_money']/($order_info[0]['use_ele']/$need_pay_ele));
                                    }
                                }
                                if($return_money > 0){                        //判断是否需要退款
                                    $data = [
                                        'cmd'=>'refundMoneyCheck',
                                        'orderNo'=>$order_no,
                                        'refund_money' => $return_money,
                                        'business_type'=>'house_village_pile_pay',
                                        'type'=>'adopt',
                                    ];
                                    $data = json_encode($data,true);
                                    $time = time();
                                    run_mysql('insert into pigcms_house_village_pile_command (command,type,status,addtime) VALUES ('."'$data'".',2,1,'.$time.')');
                                }
                                //addNote('note_query_order_refund','info',$fd.':'.$device_id,['return_money'=> $return_money,'device_info' => $device_info[0]]);
                            } else {
                                //addNote('err_note_query_order_refund','info',$fd.':'.$device_id,['line'=>__LINE__,'config_info'=> $config_info[0],'device_info' => $device_info]);
                            }
                        } else {
                           // addNote('err_note_query_order_refund','info',$fd.':'.$device_id,['line'=>__LINE__,'config_info'=> $config_info]);
                        }
                    } else {
                       // addNote('err_note_query_order_refund','info',$fd.':'.$device_id,['line'=>__LINE__,'pay_type'=> $order_info[0]['pay_type']]);
                    }
                } else {
                   // addNote('err_note_query_order_refund','info',$fd.':'.$device_id,['line'=>__LINE__,'end_reason'=> $end_reason]);
                }
            }
        }
    } elseif ($function_code == '020c') {// 查询订单存储状态
        $dataArrs = explode($header_code . '020c010a',$data);
        $dataVal = $dataArrs[1];
        $judge = getString($dataVal,0,2);
        $orderIndexes = getString($dataVal,2,8);
        $orderIndexesMax = getString($dataVal,10,8);
        $note_query_order_param = [
            'line'=>__LINE__,'judge' => $judge,'orderIndexes' => $orderIndexes,'orderIndexesMax' => $orderIndexesMax,
        ];
       // addNote('note_query_storage_order','info',$fd.':'.$device_id,$note_query_order_param);
    }elseif ($function_code == "0202" && $frame_type=="04"){        //充电桩上报插座
        $port = getString($data,40,(hexdec(getString($data,36,2))-1)*2);   //插座
        $arr = str_split($port,2);
        $list = [];
        foreach ($arr as $v){
            switch ($v){
                case '00':
                    $list[] = 0;
                    break;
                case '01':
                    $list[] = 1;
                    break;
                case '38':
                    $list[] = -1;
                    break;
                case '3a':
                    $list[] = 2;
                    break;
                default:
                    $list[] = 0;
            }
        }
        if (count($list)==1&&strpos($data,$header_code . '02020402')!== false) {
            $device_info = run_mysql('select port_status from pigcms_house_village_pile_equipment where (equipment_num = '."'$device_id'".' and is_del = 1) limit 1');
            $list = [];
            if ($device_info && isset($device_info[0]) &&  isset($device_info[0]['port_status'])) {
                $dataArrs = explode($header_code . '02020402',$data);
                $dataVal = $dataArrs[1];
                $key = getString($dataVal,0,2);
                $key = intval($key)-1;
                $valKey = getString($dataVal,2,2);
                switch ($valKey){
                    case '00':
                        $portStatus = 0;
                        break;
                    case '01':
                        $portStatus = 1;
                        break;
                    case '38':
                        $portStatus = -1;
                        break;
                    case '3a':
                        $portStatus = 2;
                        break;
                    default:
                        $portStatus = 0;
                }
                $list = json_decode($device_info[0]['port_status']);
                if (isset($list[$key])) {
                    $list[$key] = $portStatus;
                }
               // addNote('note_'.$function_code.'_','info',$fd.':'.$device_id,['line'=>__LINE__,'key' => $key,'valKey' => $valKey,'type'=>'pile 0202','dataArrs'=>$dataArrs]);
            }
        } else {
            $device_info = [];
        }
        if (!empty($list)) {
            $port_status = json_encode($list);
            $sqlRes = run_mysql('update pigcms_house_village_pile_equipment set port_status='."'$port_status'".' where equipment_num ='."'$device_id' and is_del = 1");
           // addNote('note_'.$function_code.'_','info',$fd.':'.$device_id,['line'=>__LINE__,'arr' => $arr,'device_info' => $device_info,'type'=>'pile 0202','port_status'=>$port_status,'sqlRes'=>$sqlRes]);
        } else {
           // addNote('note_'.$function_code.'_','infoErr',$fd.':'.$device_id,['line'=>__LINE__,'arr' => $arr,'device_info' => $device_info,'type'=>'pile 0202','data'=>$list]);
        }
        $frame_data = $header_code . '020204020100';
    }elseif ($function_code == '0102'){            //充电桩设置标准功率结果上报
        if(getString($data,38,2) == '01'){         //成功

        }else{                                                            //失败

        }
    }elseif ($function_code == '0103'){            //充电桩设置充满断电结果上报
        if(getString($data,38,2) == '01'){         //成功

        }else{                                                            //失败

        }
    }elseif ($function_code == '0104'){            //充电桩设置空载断电结果上报
        if(getString($data,38,2) == '01'){         //成功

        }else{                                                            //失败

        }
    }elseif ($function_code == '0107'){            //充电桩设置最长充电时间结果上报
        if(getString($data,38,2) == '01'){         //成功

        }else{                                                            //失败

        }
    }elseif ($function_code == '0301'){            //充电桩充电任务状态结果上报
        $order_no = getString($data,42,16);
        $judge = getString($data,38,2);
        if($judge == '01'){         //成功
            $res = 'success';
            $frame_head = '7e';
            $frame_version = '30';
            $frame_device_id = $device_id;
            $frame_random_number = 'ffb7';
            $frame_socket_data = $header_code . '0202010101';   //获取设备插座数
            $frame_data_length = lengthToHex($frame_socket_data);
            $frame_check = stringOr($frame_version.$frame_device_id.$frame_random_number.$frame_data_length.$frame_socket_data);
            $frame_tail = '7f';
            $frame = getFrame($frame_head.$frame_version.$frame_device_id.$frame_random_number.$frame_data_length.$frame_socket_data.$frame_check.$frame_tail);
            $server->send($fd, hexToString($frame));
            $res = run_mysql('update pigcms_house_village_pile_pay_order set status=2 where order_no ='."'$order_no'");
            addNote('note_'.$function_code.'_'.date('H'),'info',$fd.':'.$device_id,['line'=>__LINE__,'equipment_num'=>$device_id,'order_no'=>$order_no,'res'=>$res,'type'=>'frame request','data'=>$frame]);
        }else{                                                            //失败
            $res = 'fail';
            $reason = '开电异常';
            $time = time();
            $end_power = 0;
            $use_time = 0;
            $use_ele = 0;
            $order_info = run_mysql('select continued_time,use_ele,use_money,village_id from pigcms_house_village_pile_pay_order where order_no = '."'$order_no'");
            $res = run_mysql('update pigcms_house_village_pile_pay_order set end_time='.$time.',status=1,end_result='."'$reason'".',end_power='."'$end_power'".',continued_time='."'$use_time'".',use_ele='."'$use_ele'".',notificationTxt='."'$data'".' where order_no ='."'$order_no'");
            if(!empty($order_info)){
                $return_money = $order_info[0]['use_money'];
                if($return_money > 0){                        //判断是否需要退款
                    $data = [
                        'cmd'=>'refundMoney',
                        'orderNo'=>$order_no,
                        'refund_money' => $return_money,
                        'business_type'=>'house_village_pile_pay',
                        'type'=>'adopt',
                    ];
                    $data = json_encode($data,true);
                    $time = time();
                    run_mysql('insert into pigcms_house_village_pile_command (command,type,status,addtime) VALUES ('."'$data'".',2,1,'.$time.')');
                    $unique_id = "pile_notice".$time.rand(1,9999);
                    run_mysql('insert into pigcms_process_sub_plan (plan_time,space_time,add_time,file,time_type,unique_id) VALUES ('.-$time.',0,'.$time.',"sub_pile_notice",1,'."'$unique_id'".')');
                }
            }
            addNote('note_'.$function_code.'_'.date('H'),'info',$fd.':'.$device_id,['line'=>__LINE__,'equipment_num'=>$device_id,'order_no'=>$order_no,'judge' => $judge,'type'=>'frame request']);
        }
    }elseif ($function_code == '0303'){    //充电桩充电任务结束结果上报
        $order_no = getString($data,38,16);
        $port = hexdec(getString($data,54,2));
        $use_ele = sprintf("%.2f",hexdec(getString($data,56,8))/3600000);
        $use_time = hexdec(getString($data,64,8));
        $max_power = hexdec(getString($data,72,8));
        $end_power = hexdec(getString($data,80,8));
        $end_time = hexdec(getString($data,88,8));
        $end_reason = getString($data,96,2);
        if ($end_reason == '01') {
            $reason = '充电时间/电量用完';
        } elseif ($end_reason == '02') {
            $reason = '过载';
        } elseif ($end_reason == '03') {
            $reason = '充满';
        } elseif ($end_reason == '04') {
            $reason = '终止';
        } elseif ($end_reason == '05') {
            $reason = '过充';
        } elseif ($end_reason == '06') {
            $reason = '通道故障';
        } elseif ($end_reason == '07') {
            $reason = '空载';
        } elseif ($end_reason == '08') {
            $reason = '退款';
        } elseif ($end_reason == '09') {
            $reason = '电量耗尽';
        } elseif ($end_reason == '10') {
            $reason = '电表故障';
        } elseif ($end_reason == '11') {
            $reason = 'Flash故障';
        } elseif ($end_reason == '12') {
            $reason = '显示屏故障';
        } elseif ($end_reason == '13') {
            $reason = '插枪故障';
        } elseif ($end_reason == '14') {
            $reason = '计费模型存储已用完';
        } else {
            $reason = '充电时间/电量用完';
        }
        $time = time();
        $order_info = run_mysql('select id,continued_time,use_ele,use_money,village_id,pay_type from pigcms_house_village_pile_pay_order where order_no = '."'$order_no'");
        $res = run_mysql('update pigcms_house_village_pile_pay_order set end_time='.$time.',status=1,end_result='."'$reason'".',end_power='."'$end_power'".',continued_time='."'$use_time'".',use_ele='."'$use_ele'".',notificationTxt='."'$data'".' where order_no ='."'$order_no'");
        if($res){
            $frame_data = $header_code . '0303040901'.$order_no;
        }
        //判断是否是充电异常导致停止充电
        if($end_reason == '07' || $end_reason == '06' || $end_reason == '05' || $end_reason =='02'){
            $data = [
                'cmd'=>'errorStopCharging',
                'equipment_num'=>$device_id,
                'socket_no'=>$port,
            ];
            $data = json_encode($data,true);
            $time = time();
            run_mysql('insert into pigcms_house_village_pile_command (command,type,status,addtime) VALUES ('."'$data'".',2,1,'.$time.')');
        }else{
            $data = [
                'cmd'=>'stopCharging',
                'equipment_num'=>$device_id,
                'socket_no'=>$port,
            ];
            $data = json_encode($data,true);
            $time = time();
            run_mysql('insert into pigcms_house_village_pile_command (command,type,status,addtime) VALUES ('."'$data'".',2,1,'.$time.')');
        }
        $note_end_order_param = [
            'line'=>__LINE__,'order_no' => $order_no,'port' => $port,'use_ele' => $use_ele,
            'use_time'=>$use_time,'max_power' => $max_power,'end_power' => $end_power,'end_time' => $end_time,
            'end_reason'=>$end_reason,'reason' => $reason,'res' => $res,'order_info' => $order_info[0],
            'equipment_num'=>$device_id,'type'=>'order info'
        ];
        addNote('note_end_order'.date('H'),'info',$fd.':'.$device_id,$note_end_order_param);
        //判断是否满足自动退款的需求 如果是 01 也可能退款
        //if($end_reason != '01'){
            if(!empty($order_info)){
                if($order_info[0]['pay_type'] != 3 && $order_info[0]['pay_type'] != 10){
                    $config_info = run_mysql('select * from pigcms_house_village_pile_config where village_id='.$order_info[0]['village_id']);
                    if(!empty($config_info)){
                        $device_info = run_mysql('select id,work_type from pigcms_house_village_pile_equipment where equipment_num = '."'$device_id'".' and is_del = 1');
                        if(!empty($device_info)){
                            $return_money = 0;
                            $work_type = isset($device_info[0]['work_type']) ? intval($device_info[0]['work_type']) : 1;
                            $low_consume_time = isset($config_info[0]['low_consume_time']) ? $config_info[0]['low_consume_time'] : 0;
                            $des_time_level = isset($config_info[0]['des_time_level']) ? $config_info[0]['des_time_level'] : 0;
                            if($work_type == 1){         //计时版充电桩
                                if ($low_consume_time * 60 >= $use_time) {                //全额退款
                                    $return_money = $order_info[0]['use_money'];
                                   // addNote('note_end_order','info',$fd.':'.$device_id,['line'=>__LINE__,'low_consume_time'=>$low_consume_time,'msg'=> '计时全额退款','return_money'=> $return_money,'device_info' => $device_info[0]]);
                                }elseif($des_time_level >= 0){                                      //部分退款
                                    $need_pay_time = get_actual_use_time($des_time_level,$use_time);
                                    $use_money = $order_info[0]['use_money'];
                                    $order_continued_time = $order_info[0]['continued_time'];
                                    $return_money = sprintf("%.2f", $use_money - $use_money/($order_continued_time/$need_pay_time));
                                    /*
                                    addNote('note_end_order','info',$fd.':'.$device_id,[
                                        'line'=>__LINE__,'des_time_level'=>$des_time_level,'msg'=> '计时部分退款',
                                        'return_money'=> $return_money,'device_info' => $device_info[0],
                                        'use_money'=> $use_money, 'order_continued_time'=> $order_continued_time,
                                        'need_pay_time'=> $need_pay_time,
                                    ]);
                                    */
                                }
                            }else{
                                $low_consume_power = isset($config_info[0]['low_consume_power']) ? $config_info[0]['low_consume_power'] : 0;
                                $des_power_level = isset($config_info[0]['des_power_level']) ? $config_info[0]['des_power_level'] : 0;                                                   //计量版充电桩
                                if($low_consume_power >= $use_ele){                     //全额退款
                                    $return_money = $order_info[0]['use_money'];
                                    //addNote('note_end_order','info',$fd.':'.$device_id,['line'=>__LINE__,'low_consume_power'=>$low_consume_power,'msg'=> '计量全额退款','return_money'=> $return_money,'device_info' => $device_info[0]]);
                                }elseif($des_power_level >= 0){                                  //部分退款
                                    $need_pay_ele = get_actual_use_ele($des_power_level,$use_ele);
                                    $use_money = $order_info[0]['use_money'];
                                    $order_use_ele = $order_info[0]['use_ele'];
                                    $return_money = sprintf("%.2f", $use_money - $use_money/($order_use_ele/$need_pay_ele));
                                    /*
                                    addNote('note_end_order','info',$fd.':'.$device_id,[
                                        'line'=>__LINE__,'des_power_level'=>$des_power_level,'msg'=> '计量部分退款',
                                        'return_money'=> $return_money,'device_info' => $device_info[0],
                                        'use_money'=> $use_money, 'order_use_ele'=> $order_use_ele,
                                        'need_pay_ele'=> $need_pay_ele,
                                    ]);
                                    */
                                }
                            }
                            if($return_money > 0){                        //判断是否需要退款
                                $data = [
                                    'cmd'=>'refundMoney',
                                    'orderNo'=>$order_no,
                                    'refund_money' => $return_money,
                                    'business_type'=>'house_village_pile_pay',
                                    'type'=>'adopt',
                                ];
                                $data = json_encode($data,true);
                                $time = time();
                                run_mysql('insert into pigcms_house_village_pile_command (command,type,status,addtime) VALUES ('."'$data'".',2,1,'.$time.')');
                            }
                            //addNote('note_end_order','info',$fd.':'.$device_id,['return_money'=> $return_money,'device_info' => $device_info[0]]);
                        } else {
                            //addNote('err_note_end_order','info',$fd.':'.$device_id,['line'=>__LINE__,'config_info'=> $config_info[0],'device_info' => $device_info]);
                        }
                    } else {
                        //addNote('err_note_end_order','info',$fd.':'.$device_id,['line'=>__LINE__,'config_info'=> $config_info]);
                    }
                } else {
                    //addNote('err_note_end_order','info',$fd.':'.$device_id,['line'=>__LINE__,'pay_type'=> $order_info[0]['pay_type']]);
                }
            } else {
                //addNote('err_note_end_order','info',$fd.':'.$device_id,['line'=>__LINE__,'end_reason'=> $end_reason]);
            }
        //}
        $time = time();
        $unique_id = "pile_notice".$time.rand(1,9999);
        run_mysql('insert into pigcms_process_sub_plan (plan_time,space_time,add_time,file,time_type,unique_id) VALUES ('.-$time.',0,'.$time.',"sub_pile_notice",1,'."'$unique_id'".')');
    }elseif($function_code == '0306'){
        $serial_no = getString($data,38,4);
        $frame_data = $header_code . '0306040301'.$serial_no;
        $port = hexdec(getString($data,42,2));
        $use_ele = sprintf("%.2f",hexdec(getString($data,44,8))/3600000);
        $use_time = hexdec(getString($data,52,8));
        $card_num = getString($data,60,32);
        $use_money = hexdec(getString($data,92,4))/100;
        $remain_money = hexdec(getString($data,96,8))/100;
        $max_power = hexdec(getString($data,104,8));
        $end_power = hexdec(getString($data,112,8));
        $end_time = hexdec(getString($data,120,8));
        $end_reason = getString($data,128,2);
        if ($end_reason == '01') {
            $reason = '充电时间/电量用完';
        } elseif ($end_reason == '02') {
            $reason = '过载';
        } elseif ($end_reason == '03') {
            $reason = '充满';
        } elseif ($end_reason == '04') {
            $reason = '终止';
        } elseif ($end_reason == '05') {
            $reason = '过充';
        } elseif ($end_reason == '06') {
            $reason = '通道故障';
        } elseif ($end_reason == '07') {
            $reason = '空载';
        } elseif ($end_reason == '08') {
            $reason = '退款';
        } elseif ($end_reason == '09') {
            $reason = '电量耗尽';
        } elseif ($end_reason == '10') {
            $reason = '电表故障';
        } elseif ($end_reason == '11') {
            $reason = 'Flash故障';
        } elseif ($end_reason == '12') {
            $reason = '显示屏故障';
        } elseif ($end_reason == '13') {
            $reason = '插枪故障';
        } elseif ($end_reason == '14') {
            $reason = '计费模型存储已用完';
        } else {
            $reason = '充电时间/电量用完';
        }
        $equipment_info = run_mysql('select * from pigcms_house_village_pile_equipment where equipment_num = '."'$device_id'".' and is_del = 1');
        if($equipment_info){
            $equipment_info = $equipment_info[0];
        }
        $village_id = $equipment_info['village_id'];
        $equipment_id = $equipment_info['id'];
        $time = time();
        $note_end_order_param = [
            'line'=>__LINE__,'serial_no' => $serial_no,'card_num'=>$card_num,'port' => $port,'use_ele' => $use_ele,
            'use_time'=>$use_time,'use_money'=>$use_money,'max_power' => $max_power,'end_power' => $end_power,'end_time' => $end_time,
            'end_reason'=>$end_reason,'reason' => $reason,'equipment_info' => $equipment_info,'remain_money' => $remain_money,
            'equipment_num'=>$device_id,'type'=>'order info'
        ];
       // addNote('card_note_end_order','info',$fd.':'.$device_id,$note_end_order_param);
        run_mysql('insert into pigcms_house_village_pile_pay_order (village_id,order_type,order_no,equipment_id,continued_time,status,use_money,refund_money,use_ele,end_power,pigcms_id,pay_type,pay_time,end_time,add_time,socket_no,type,uid,end_result,card_num,notificationTxt) VALUES ('.$village_id.',1,'."'$serial_no'".','.$equipment_id.','.$use_time.',1,'.$use_money.',0,'.$use_ele.','.$end_power.',0,10,'.$time.','.$time.','.$time.','.$port.',2,0,'."'$reason'".','."'$card_num'".','."'$data'".')');
    }elseif ($function_code == '0202'){            //充电桩插座状态上报
        $judge = getString($data,38,2);
       // addNote('note_'.$function_code.'_','infoMsg',$fd.':'.$device_id,['line'=>__LINE__,'frame_type' => $frame_type,'type'=>'pile 0202']);
        if ($frame_type=="04") {
            $port = getString($data,40,(hexdec(getString($data,36,2))-1)*2);   //插座
            $arr = str_split($port,2);
            $list = [];
            foreach ($arr as $v){
                switch ($v){
                    case '00':
                        $list[] = 0;
                        break;
                    case '01':
                        $list[] = 1;
                        break;
                    case '38':
                        $list[] = -1;
                        break;
                    case '3a':
                        $list[] = 2;
                        break;
                    default:
                        $list[] = 0;
                }
            }
            if (count($list)==1&&strpos($data,$header_code . '02020402')!== false) {
                $device_info = run_mysql('select port_status from pigcms_house_village_pile_equipment where (equipment_num = '."'$device_id'".' and is_del = 1) limit 1');
                $list = [];
                if ($device_info && isset($device_info[0]) &&  isset($device_info[0]['port_status'])) {
                    $dataArrs = explode($header_code . '02020402',$data);
                    $dataVal = $dataArrs[1];
                    $key = getString($dataVal,0,2);
                    $key = intval($key)-1;
                    $valKey = getString($dataVal,2,2);
                    switch ($valKey){
                        case '00':
                            $portStatus = 0;
                            break;
                        case '01':
                            $portStatus = 1;
                            break;
                        case '38':
                            $portStatus = -1;
                            break;
                        case '3a':
                            $portStatus = 2;
                            break;
                        default:
                            $portStatus = 0;
                    }
                    $list = json_decode($device_info[0]['port_status']);
                    if (isset($list[$key])) {
                        $list[$key] = $portStatus;
                    }
                }
            } else {
                $device_info = [];
            }
            if (!empty($list)) {
                $port_status = json_encode($list);
                $sqlRes = run_mysql('update pigcms_house_village_pile_equipment set port_status='."'$port_status'".' where equipment_num ='."'$device_id' and is_del = 1");
               // addNote('note_'.$function_code.'_','info',$fd.':'.$device_id,['line'=>__LINE__,'arr' => $arr,'device_info' => $device_info,'type'=>'pile 0202','port_status'=>$port_status,'sqlRes'=>$sqlRes]);
            } else {
              //  addNote('note_'.$function_code.'_','infoErr',$fd.':'.$device_id,['line'=>__LINE__,'arr' => $arr,'device_info' => $device_info,'type'=>'pile 0202','data'=>$list]);
            }
            $frame_data = $header_code . '020204020100';
        } elseif($judge == '01'){         //成功
            $port = getString($data,40,(hexdec(getString($data,36,2))-1)*2);   //插座
            $arr = str_split($port,2);
            $list = [];
            foreach ($arr as $v){
                switch ($v){
                    case '00':
                        $list[] = 0;
                        break;
                    case '01':
                        $list[] = 1;
                        break;
                    case '38':
                        $list[] = -1;
                        break;
                    case '3a':
                        $list[] = 2;
                        break;
                    default:
                        $list[] = 0;
                }
            }
            $port_list = json_encode($list);
            run_mysql('update pigcms_house_village_pile_equipment set port_status='."'$port_list'".' where equipment_num ='."'$device_id' and is_del = 1");
            $rand = rand(1,10);
            if($rand == 6){
                $date_time = getTime(date('y'),date('m'),date('d'),date('H'),date('i'),date('s'));
                $frame_data = $header_code . '010e0706'.$date_time;
            }
        }else{                                                            //失败

        }
    }elseif ($function_code == '030a'){                //充电桩状态操作上报
        $res = run_mysql('select status from pigcms_house_village_pile_equipment where equipment_num = '."'$device_id'".' and is_del = 1');
        $judge = getString($data,38,2);
        if($judge == '01'){         //成功
            if($res[0]['status'] == 4){
                $status = 2;
            }else{
                $status = 1;
            }
        }elseif($judge == '00' && isset($res[0]['status']) && $res[0]['status'] != 4){         //格式非法  但是如果处于非停止状态 标明设备恢复了
            $status = 1;
        }else{                                                            //失败
            if($res[0]['status'] == 4){
                $status = 1;
            }
            else{
                $status = 2;
            }
        }
        run_mysql('update pigcms_house_village_pile_equipment set status='.$status.' where equipment_num ='."'$device_id'".' and is_del = 1');
    }elseif ($function_code == '030b'){                      //停止充电结果返回
        if(getString($data,40,2) == '01'){     //成功
            $data = [
                'cmd'=>'stopCharging',
                'equipment_num'=>$device_id,
                'socket_no'=>hexdec(getString($data,38,2)),
            ];
            $data = json_encode($data,true);
            $time = time();
            run_mysql('insert into pigcms_house_village_pile_command (command,type,status,addtime) VALUES ('."'$data'".',2,1,'.$time.')');
        }else{

        }
    }elseif ($function_code == '020a' && $frame_type == '01'){            //设备返回充电充通道状态
        $port_num = hexdec(getString($data,40,2));
        for($i=0;$i<$port_num;$i++){
            $info = getString($data,42+26*$i,26);
            $port = hexdec(getString($info,0,2));
            $use_ele = hexdec(getString($info,10,8));
            run_mysql('update pigcms_house_village_pile_pay_order p LEFT JOIN  pigcms_house_village_pile_equipment e ON p.equipment_id=e.id set p.actual_ele='.$use_ele.' where e.equipment_num ='."'$device_id'".' and p.socket_no ='."'$port'".' ORDER BY p.id DESC LIMIT 1');
        }
    } elseif ($function_code == '0502') {
        // 0x0502 充电中数据
        $note_0502_param = ['line' => __LINE__];
        // 实时电压 8
        $begin = 38;
        $now_voltage = hexdec(getString($data,$begin,8));
        $note_0502_param['now_voltage'] = $now_voltage;
        // 总插头数 2
        $begin = $begin + 8;
        $socket_num = getString($data,$begin,2);
        $note_0502_param['socket_num'] = $socket_num;
        // 通道号 2
        $begin = $begin + 2;
        $port = getString($data,$begin,2);
        $note_0502_param['port'] = $port;
        // 实时功率 8
        $begin = $begin + 2;
        $now_power = hexdec(getString($data,$begin,8));
        $note_0502_param['now_power'] = $now_power;
        // 尖电量 8
        $begin = $begin + 8;
        $top_charge = hexdec(getString($data,$begin,8));
        $note_0502_param['top_charge'] = $top_charge;
        // 峰电量 8
        $begin = $begin + 8;
        $peak_charge = hexdec(getString($data,$begin,8));
        $note_0502_param['peak_charge'] = $peak_charge;
        // 平电量 8
        $begin = $begin + 8;
        $level_charge = hexdec(getString($data,$begin,8));
        $note_0502_param['level_charge'] = $level_charge;
        // 谷电量 8
        $begin = $begin + 8;
        $valley_charge = hexdec(getString($data,$begin,8));
        $note_0502_param['valley_charge'] = $valley_charge;
        // 已充电电量 8
        $begin = $begin + 8;
        $charged = hexdec(getString($data,$begin,8));
        $note_0502_param['charged'] = $charged;
        // 服务费 4
        $begin = $begin + 4;
        $total_service_money = hexdec(getString($data,$begin,4));
        $note_0502_param['total_service_money'] = $total_service_money;
        // 占位费 4
        $begin = $begin + 4;
        $sitting_money = hexdec(getString($data,$begin,4));
        $note_0502_param['sitting_money'] = $sitting_money;
        // 预约费 4
        $begin = $begin + 4;
        $subscription_money = hexdec(getString($data,$begin,4));
        $note_0502_param['subscription_money'] = $subscription_money;
        // 已充电时间 8
        $begin = $begin + 4;
        $now_use_time = getString($data,$begin,8);
        $note_0502_param['now_use_time'] = $now_use_time;
        // 已充电金额 8
        $begin = $begin + 8;
        $now_use_money = getString($data,$begin,8);
        $note_0502_param['now_use_money'] = $now_use_money;
        // 预计剩余充电时间 8
        $begin = $begin + 8;
        $remainder_time = getString($data,$begin,8);
        $note_0502_param['remainder_time'] = $remainder_time;
        // 剩余金额 8
        $begin = $begin + 8;
        $remainder_money = getString($data,$begin,8);
        $note_0502_param['remainder_money'] = $remainder_money;
      //  addNote('note_0502_param','info',$fd.':'.$device_id,$note_0502_param);
    } elseif ($function_code == '0503') {
        // 0x0503 充电订单（汽车桩）

        $note_end_order_param = ['line' => __LINE__];
        $begin = 38;
        // 通道号
        $port = hexdec(getString($data,$begin,2));
        $note_end_order_param['port'] = $port;
        // 订单类型
        // 订单类型：储值卡订单：0x01；扫码订单：0x02；免费（手机）订单：0x03；投币订
        //单：0x04；本地余额卡订单：0x05；本地包月订单：0x06；按键启动/即插即用：0x07；储
        //值卡透支订单：0x08
        $begin = $begin + 2;
        $orderType = getString($data,$begin,2);
        $note_end_order_param['orderType'] = $orderType;
        // 流水号
        $begin = $begin + 2;
        $number = getString($data,$begin,4);
        $note_end_order_param['number'] = $number;
        // 订单号
        $begin = $begin + 4;
        $order_no = getString($data,$begin,16);
        $note_end_order_param['order_no'] = $order_no;
        // 卡号
        $begin = $begin + 16;
        $card_num = getString($data,$begin,32);
        $note_end_order_param['card_num'] = $card_num;
        // 订单金额
        $begin = $begin + 32;
        $order_money = hexdec(getString($data,$begin,4));
        $note_end_order_param['order_money'] = $order_money;
        // 本地卡余额
        $begin = $begin + 4;
        $card_num_money = hexdec(getString($data,$begin,8));
        $note_end_order_param['card_num_money'] = $card_num_money;
        // 最大功率
        $begin = $begin + 8;
        $max_power = hexdec(getString($data,$begin,8));
        $note_end_order_param['max_power'] = $max_power;
        // 结束功率
        $begin = $begin + 8;
        $end_power = hexdec(getString($data,$begin,8));
        $note_end_order_param['end_power'] = $end_power;
        // 开始时间
        $begin = $begin + 8;
        $start_time = getString($data,$begin,8);
        $note_end_order_param['1start_time'] = $start_time;
        $start_time = $deviceDateTimeStart + hexdec($start_time);
        $note_end_order_param['start_time'] = $start_time;
        // 完成时间
        $begin = $begin + 8;
        $end_time = getString($data,$begin,8);
        $note_end_order_param['1end_time'] = $end_time;
        $end_time = $deviceDateTimeStart + hexdec($end_time);
        $note_end_order_param['end_time'] = $end_time;
        // 结束原因
        // 充电结束原因：0x01：额度耗尽，0x02 过载，0x03 充满，0x04 终止，0x05 过充，0x06
        //通道故障，0x07 空载，0x08 退款；
        $begin = $begin + 8;
        $end_reason = getString($data,$begin,2);
        $note_end_order_param['end_reason'] = $end_reason;
        if ($end_reason == '01') {
            $reason = '充电时间/电量用完';
        } elseif ($end_reason == '02') {
            $reason = '过载';
        } elseif ($end_reason == '03') {
            $reason = '充满';
        } elseif ($end_reason == '04') {
            $reason = '终止';
        } elseif ($end_reason == '05') {
            $reason = '过充';
        } elseif ($end_reason == '06') {
            $reason = '通道故障';
        } elseif ($end_reason == '07') {
            $reason = '空载';
        } elseif ($end_reason == '08') {
            $reason = '退款';
        } elseif ($end_reason == '09') {
            $reason = '电量耗尽';
        } elseif ($end_reason == '10') {
            $reason = '电表故障';
        } elseif ($end_reason == '11') {
            $reason = 'Flash故障';
        } elseif ($end_reason == '12') {
            $reason = '显示屏故障';
        } elseif ($end_reason == '13') {
            $reason = '插枪故障';
        } elseif ($end_reason == '14') {
            $reason = '计费模型存储已用完';
        } else {
            $reason = '充电时间/电量用完';
        }
        $note_end_order_param['reason'] = $reason;
        // 充电时长
        $begin = $begin + 2;
        $use_time = hexdec(getString($data,$begin,8));
        $note_end_order_param['use_time'] = $use_time;
        // 尖电量
        $begin = $begin + 8;
        $top_charge = hexdec(getString($data,$begin,8));
        $note_end_order_param['1top_charge'] = $top_charge;
        $top_charge = sprintf("%.2f",$top_charge/3600000);
        $note_end_order_param['top_charge'] = $top_charge;
        // 尖金额
        $begin = $begin + 8;
        $top_money = hexdec(getString($data,$begin,4));
        $note_end_order_param['top_money'] = $top_money;
        // 峰电量
        $begin = $begin + 4;
        $peak_charge = hexdec(getString($data,$begin,8));
        $note_end_order_param['1peak_charge'] = $peak_charge;
        $peak_charge = sprintf("%.2f",$peak_charge/3600000);
        $note_end_order_param['peak_charge'] = $peak_charge;
        // 峰金额
        $begin = $begin + 8;
        $peak_money = hexdec(getString($data,$begin,4));
        $note_end_order_param['peak_money'] = $peak_money;
        // 平电量
        $begin = $begin + 4;
        $level_charge = hexdec(getString($data,$begin,8));
        $note_end_order_param['1level_charge'] = $level_charge;
        $level_charge = sprintf("%.2f",$level_charge/3600000);
        $note_end_order_param['level_charge'] = $level_charge;
        // 平金额
        $begin = $begin + 8;
        $level_money = hexdec(getString($data,$begin,4));
        $note_end_order_param['level_money'] = $level_money;
        // 谷电量
        $begin = $begin + 4;
        $valley_charge = hexdec(getString($data,$begin,8));
        $note_end_order_param['1valley_charge'] = $valley_charge;
        $valley_charge = sprintf("%.2f",$valley_charge/3600000);
        $note_end_order_param['valley_charge'] = $valley_charge;
        // 谷金额
        $begin = $begin + 8;
        $valley_money = hexdec(getString($data,$begin,4));
        $note_end_order_param['valley_money'] = $valley_money;
        // 总电量
        $begin = $begin + 4;
        $use_ele = hexdec(getString($data,$begin,8));
        $note_end_order_param['1use_ele'] = $use_ele;
        $use_ele = sprintf("%.2f",$use_ele/3600000);
        $note_end_order_param['use_ele'] = $use_ele;
        // 总电费金额
        $begin = $begin + 8;
        $total_money = hexdec(getString($data,$begin,4));
        $note_end_order_param['total_money'] = $total_money;
        // 服务费
        $begin = $begin + 4;
        $total_service_money = hexdec(getString($data,$begin,4));
        $note_end_order_param['total_service_money'] = $total_service_money;
        // 占位费
        $begin = $begin + 4;
        $sitting_money = hexdec(getString($data,$begin,4));
        $note_end_order_param['sitting_money'] = $sitting_money;
        // 预约费
        $begin = $begin + 4;
        $subscription_money = hexdec(getString($data,$begin,4));
        $note_end_order_param['subscription_money'] = $subscription_money;
        // 总费用
        $begin = $begin + 4;
        $total = hexdec(getString($data,$begin,8));
        $note_end_order_param['1total'] = $total;
        $total = sprintf("%.2f",$total/100);
        $note_end_order_param['total'] = $total;

        $time = $end_time > 1686672000 ? $end_time : time();
        $order_info = run_mysql('select id,continued_time,use_ele,use_money,village_id,pay_type from pigcms_house_village_pile_pay_order where order_no = '."'$order_no'");

        $note_end_order_param['order_info'] = $order_info;
        addNote('note_end_order_car'.date('H'),'info',$fd.':'.$device_id,$note_end_order_param);
        
        if (isset($order_info[0])&&$order_info[0]['id']) {
            $sql = 'update pigcms_house_village_pile_pay_order set end_time='.$time;
            if ($start_time && $start_time > 1686672000) {
                $sql .= ',pay_time='.$start_time;
            }
            $sql .= ',status=1,end_result='."'$reason'".',end_power='."'$end_power'".',continued_time='."'$use_time'".',use_ele='."'$use_ele'".',notificationTxt='."'$data'".' where order_no ='."'$order_no'";
            
            $res = run_mysql($sql);
           // addNote('note_end_order0503','info',$fd.':'.$device_id,['res' => $res]);
            if($res){
                $frame_data = $header_code . '0503040C01' . $orderType . $number . $order_no;
            }
            //判断是否是充电异常导致停止充电
            $errStopReason = ['02', '05', '06', '07', '09', '10', '11', '12', '13', '14'];
            if(in_array($end_reason, $errStopReason)){
                $data = [
                    'cmd'=>'errorStopCharging',
                    'equipment_num'=>$device_id,
                    'socket_no'=>$port,
                ];
                $data = json_encode($data,true);
                $time = time();
                run_mysql('insert into pigcms_house_village_pile_command (command,type,status,addtime) VALUES ('."'$data'".',2,1,'.$time.')');
            }else{
                $data = [
                    'cmd'=>'stopCharging',
                    'equipment_num'=>$device_id,
                    'socket_no'=>$port,
                ];
                $data = json_encode($data,true);
                $time = time();
                run_mysql('insert into pigcms_house_village_pile_command (command,type,status,addtime) VALUES ('."'$data'".',2,1,'.$time.')');
            }
            if ($total && isset($order_info[0]) && isset($order_info[0]['use_money']) && floatval($order_info[0]['use_money']) > floatval($total)) {
                $return_money = floatval($order_info[0]['use_money']) - floatval($total);
                $return_money = sprintf("%.2f",$return_money);
                if($return_money > 0){                        //判断是否需要退款
                    $data = [
                        'cmd'=>'refundMoney',
                        'orderNo'=>$order_no,
                        'refund_money' => $return_money,
                        'business_type'=>'house_village_pile_pay',
                        'type'=>'adopt',
                    ];
                    $data = json_encode($data,true);
                    $time = time();
                    run_mysql('insert into pigcms_house_village_pile_command (command,type,status,addtime) VALUES ('."'$data'".',2,1,'.$time.')');
                }
               // addNote('note_end_order0503','info',$fd.':'.$device_id,['return_money'=> $return_money,'msg' => '按照设备反馈消费金额进行退款']);
            } elseif ($end_reason != '01') {
                if($order_info[0]['pay_type'] != 3 && $order_info[0]['pay_type'] != 10){
                    $config_info = run_mysql('select * from pigcms_house_village_pile_config where village_id='.$order_info[0]['village_id']);
                    if(!empty($config_info)){
                        $device_info = run_mysql('select work_type from pigcms_house_village_pile_equipment where equipment_num = '."'$device_id'".' and is_del = 1');
                        if(!empty($device_info)){
                            $return_money = 0;
                            if($device_info[0]['work_type'] == 1){         //计时版充电桩
                                if($config_info[0]['low_consume_time']*60 >= $use_time){                //全额退款
                                    $return_money = $order_info[0]['use_money'];
                                }elseif($config_info[0]['des_time_level'] >= 0){                                      //部分退款
                                    $need_pay_time = get_actual_use_time($config_info[0]['des_time_level'],$use_time);
                                    $return_money = sprintf("%.2f", $order_info[0]['use_money'] - $order_info[0]['use_money']/($order_info[0]['continued_time']/$need_pay_time));
                                }
                            }else{                                                                      //计量版充电桩
                                if($config_info[0]['low_consume_power'] >= $use_ele){                     //全额退款
                                    $return_money = $order_info[0]['use_money'];
                                }elseif($config_info[0]['des_power_level'] >= 0){                                  //部分退款
                                    $need_pay_ele = get_actual_use_ele($config_info[0]['des_power_level'],$use_ele);
                                    $return_money = sprintf("%.2f", $order_info[0]['use_money'] - $order_info[0]['use_money']/($order_info[0]['use_ele']/$need_pay_ele));
                                }
                            }
                            if($return_money > 0){                        //判断是否需要退款
                                $data = [
                                    'cmd'=>'refundMoney',
                                    'orderNo'=>$order_no,
                                    'refund_money' => $return_money,
                                    'business_type'=>'house_village_pile_pay',
                                    'type'=>'adopt',
                                ];
                                $data = json_encode($data,true);
                                $time = time();
                                run_mysql('insert into pigcms_house_village_pile_command (command,type,status,addtime) VALUES ('."'$data'".',2,1,'.$time.')');
                            }
                         //   addNote('note_end_order0503','info',$fd.':'.$device_id,['return_money'=> $return_money,'device_info' => $device_info[0]]);
                        } else {
                           // addNote('err_note_end_order0503','info',$fd.':'.$device_id,['line'=>__LINE__,'config_info'=> $config_info[0],'device_info' => $device_info]);
                        }
                    } else {
                      //  addNote('err_note_end_order0503','info',$fd.':'.$device_id,['line'=>__LINE__,'config_info'=> $config_info]);
                    }
                } else {
                   // addNote('err_note_end_order0503','info',$fd.':'.$device_id,['line'=>__LINE__,'pay_type'=> $order_info[0]['pay_type']]);
                }
            }
            $time = time();
            $unique_id = "pile_notice".$time.rand(1,9999);
            run_mysql('insert into pigcms_process_sub_plan (plan_time,space_time,add_time,file,time_type,unique_id) VALUES ('.-$time.',0,'.$time.',"sub_pile_notice",1,'."'$unique_id'".')');
            
        } elseif ($orderType == '04') {
            $equipment_info = run_mysql('select * from pigcms_house_village_pile_equipment where equipment_num = '."'$device_id'".' and is_del = 1');
            if($equipment_info){
                $equipment_info = $equipment_info[0];
            }
            $village_id = $equipment_info['village_id'];
            $equipment_id = $equipment_info['id'];
            $time = time();
            $use_money = $total > 0 ? $total/100 : 0;
            run_mysql('insert into pigcms_house_village_pile_pay_order (village_id,order_type,order_no,equipment_id,continued_time,status,use_money,refund_money,use_ele,end_power,pigcms_id,pay_type,pay_time,end_time,add_time,socket_no,type,uid,end_result,card_num) VALUES ('.$village_id.',1,'."'$order_no'".','.$equipment_id.','.$use_time.',1,'.$use_money.',0,'.$use_ele.','.$end_power.',0,10,'.$time.','.$time.','.$time.','.$port.',2,0,'."'$reason'".','."'$card_num'".')');
            
            $frame_data = $header_code . '0503040C01' . $orderType . $number . $order_no;
        } else {
            $frame_data = $header_code . '0503040C01' . $orderType . $number . $order_no;
        }
    } elseif ($function_code == '0402') {
        // 0x0402 自有平台远程升级
        $frame_data = $header_code . '030B040101';
    } elseif ($function_code == '0401') {
        // 0x0401 设备远程升级
        $frame_data = $header_code . '030B040101';
    } elseif ($function_code == '030D' || $function_code == '030d') {
        // 0x030D 地锁控制（汽车桩）
        $frame_data = $header_code . '030D070101';
    } elseif ($function_code == '030C' || $function_code == '030c') {
        // 0x030C 本地卡充值
        $frame_data = $header_code . '030C070101';
    } elseif ($function_code == '0309') {
        // 0x0309 储值卡透支充电订单
        $frame_data = $header_code . '03090403010009';
    } elseif ($function_code == '0309') {
        // 0x0308 按键启动/即插即用充电订单
        $frame_data = $header_code . '03080403010009';
    } elseif ($function_code == '0212') {
        // 0x0212 停车位地锁状态（汽车桩）
        $frame_data = $header_code . '011204020101';
    } elseif ($function_code == '0211') {
        // 0x0210 三相电实时电压
        $frame_data = $header_code . '021001070100DC017C017C';
    } elseif ($function_code == '020F' || $function_code == '020f') {
        // 0x020F 设备问题定位
        $frame_data = $header_code . '020F040501';
    } elseif ($function_code == '0113') {
        // 0x0113 本地卡扣款金额（汽车桩）
        $frame_data = $header_code . '30020113020101';
    } elseif ($function_code == '0112') {
        // 0x0112 停车位地锁
        $frame_data = $header_code . '0112070101';
    }
    
    if(!empty($frame_data)){
        $frame_head = '7e';
        $frame_version = '30';
        $frame_device_id = $device_id;
        $frame_random_number = $random_number;
        $frame_data_length = lengthToHex($frame_data);
        $frame_check = stringOr($frame_version.$frame_device_id.$frame_random_number.$frame_data_length.$frame_data);
        $frame_tail = '7f';
        $frame = getFrame($frame_head.$frame_version.$frame_device_id.$frame_random_number.$frame_data_length.$frame_data.$frame_check.$frame_tail);
        $server->send($fd, hexToString($frame));
        //addNote('note_'.$function_code.'_','info',$fd.':'.$device_id,['line'=>__LINE__,'equipment_num'=>$device_id,'type'=>'frame response','data'=>$frame]);
    }
    if($function_code == '0303'){
        sleep(1);
        $result = run_mysql('select equipment_num from pigcms_house_village_pile_equipment where fd = '.$fd.' and is_del = 1');
        $frame_head = '7e';
        $frame_version = '30';
        $frame_device_id = $result[0]['equipment_num'];
        $frame_random_number = 'ffb7';
        $frame_socket_data = $header_code . '0202010101';   //获取设备插座数
        $frame_data_length = lengthToHex($frame_socket_data);
        $frame_check = stringOr($frame_version.$frame_device_id.$frame_random_number.$frame_data_length.$frame_socket_data);
        $frame_tail = '7f';
        $frame = getFrame($frame_head.$frame_version.$frame_device_id.$frame_random_number.$frame_data_length.$frame_socket_data.$frame_check.$frame_tail);
        $server->send($fd, hexToString($frame));
       // addNote('note_'.$function_code.'_','info',$fd.':'.$result[0]['equipment_num'],['line'=>__LINE__,'equipment_num'=>$result[0]['equipment_num'],'type'=>'frame request','data'=>$frame]);
    }
});

//利用swoole的定时器，定时请求第三方接口，将数据实时推送到客户端
$process = new Swoole\Process(function($process) use ($server) {
    Swoole\Timer::tick(3000, function (int $timer_id, $server) {
//        $times = time() - 86400; // 超过一天的指令不执行
        $times = time() - 600; // 缩短指令执行时间 10分钟内的
        $result = run_mysql('select * from pigcms_house_village_pile_command where type = 1 AND addtime > '.$times.' limit 20');
        foreach ($server->connections  as $fd){
            $info = run_mysql('select equipment_num,device_family from pigcms_house_village_pile_equipment where fd = '.$fd.' and is_del = 1');
            if(empty($info)) {
//                addNote('notice_note_timer','timer',$fd,['line'=>__LINE__,'fd'=>$fd, 'type'=>'timer']);
                continue;
            }
            if(is_array($result) && count($result)>0) {
                $result = array_values($result);
            }
            if($result){
                $header_code = '3002';
                if (isset($info[0]['device_family']) && intval($info[0]['device_family']) === 1) {
                    $header_code = '3004';
                }
                //addNote('note_swoole_','info',$fd.':'.$info[0]['equipment_num'],['equipment_num'=>$info[0]['equipment_num'],'header_code'=>$header_code,'info'=>$info[0]]);
                foreach ($result as $k=>$v){
                    $command = json_decode($v['command'],true);
                    $addtime = isset($v['addtime']) ? intval($v['addtime']) : 0;
                    if ($addtime > 1) {
                        $addTimeText = date('Y-m-d H:i:s', $addtime);
                    } else {
                        $addTimeText = '';
                    }
                    if(isset($command['equipment_num']) &&$command['equipment_num'] == $info[0]['equipment_num']){
                        if ($command['equipment_num'] == '00030e724ff45f13' || $command['equipment_num'] == '00030e724ff489e9') {
                            $header_code = '3004';
                        }
                        run_mysql('delete from pigcms_house_village_pile_command where id = '.$v['id']);
                        if($command['cmd'] == 'bindAttribute'){
                          //  addNote('note_bindAttribute_tmp_','info',$fd.':'.$info[0]['equipment_num'],['header_code'=>$header_code,'attribute_info_unserialize'=>$command['attribute_info'], 'equipment_num'=>$info[0]['equipment_num']]);
                            $attribute_info = unserialize($command['attribute_info']);
                          //  addNote('note_bindAttribute_tmp_','info',$fd.':'.$info[0]['equipment_num'],['header_code'=>$header_code,'attribute_info_unserialize'=>$command['attribute_info'],'attribute_info' => $attribute_info, 'equipment_num'=>$info[0]['equipment_num']]);
                            switch ($command['attribute_id']){
                                case 1:
                                    if ($header_code == '3004') {
                                        $frame_data = $header_code . '0105021d'.NumToHex($attribute_info[0][2]*100,4).NumToHex($attribute_info[1][2]*60*60,8).NumToHex($attribute_info[2][2]*3600000,8).NumToHex($attribute_info[1][2]*60*60,8).NumToHex($attribute_info[2][2]*3600000,8).NumToHex($attribute_info[0][2]*100,4).NumToHex($attribute_info[1][2]*60*60,8).NumToHex($attribute_info[2][2]*3600000,8).'64';
                                    } else {
                                        $frame_data = $header_code . '0105021d'.NumToHex($attribute_info[0][2]*100,4).NumToHex($attribute_info[1][2]*60*60,8).NumToHex($attribute_info[2][2]*1000,8).NumToHex($attribute_info[1][2]*60*60,8).NumToHex($attribute_info[2][2]*1000,8).NumToHex($attribute_info[0][2]*100,4).NumToHex($attribute_info[1][2]*60*60,8).NumToHex($attribute_info[2][2]*1000,8).'64';
                                    }
                                    break;
                                case 2:
                                    $frame_data = $header_code . '01020204'.NumToHex($attribute_info[0][2],8);
                                    break;
                                case 4:
                                    $frame_data = $header_code . '01010204'.NumToHex($attribute_info[0][2],8);
                                    break;
                                case 5:
                                    $frame_data = $header_code . '01070204'.NumToHex($attribute_info[0][2],8);
                                    break;
                                case 6:
                                    $frame_data = $header_code . '01030204'.NumToHex($attribute_info[1][2],4).NumToHex($attribute_info[0][2],4);
                                    break;
                                case 7:
                                    $frame_data = $header_code . '01040204'.NumToHex($attribute_info[1][2],4).NumToHex($attribute_info[0][2],4);
                                    break;
                                case 8:
                                    $frame_data = $header_code . '010C0208'.NumToHex($attribute_info[1][2],4).NumToHex($attribute_info[0][2],4);
                                    break;
                                default:
                                    $frame_data = '';
                            }
                        }elseif($command['cmd'] == 'newPriceStandardCharge'){
                            // 开电
                            if (isset($command['workType']) && isset($command['money'])) {
                                $workType = $command['workType'] ? $command['workType'] : '01';
                                $money = $command['money'] * 100;// 设备识别金额为 分 所以金额乘以100
                                $money = round($money, 0);
                                $frame_data = $header_code . '0301071702'.$command['orderNo'].NumToHex($command['port'],2).NumToHex($command['param']*3600000,8).NumToHex($command['time'],8).NumToHex($workType, 2).NumToHex($money, 8);
                            } else {
                                $frame_data = $header_code . '0301071202'.$command['orderNo'].NumToHex($command['port'],2).NumToHex($command['param']*3600000,8).NumToHex($command['time'],8);
                            }
                        }elseif ($command['cmd'] == 'pileQueryRule'){
                            // 查询费标准
                            $frame_data = $header_code . '0105010101';
                        }elseif ($command['cmd'] == 'pileQueryRuleInfo'){
                            // 查询新版费标准
                            $frame_data = $header_code . '0111010101';
                        }elseif ($command['cmd'] == 'pileQueryStorage'){
                            // 查询订单存储
                            $frame_data = $header_code . '020C010101';
                        }elseif ($command['cmd'] == 'pileQueryOrder'){
                            // 查询订单
                            $frame_data = $header_code . '020D0104'.$command['orderNo'];
                        }elseif ($command['cmd'] == 'stopCharging'){
                            // 插座口停止
                            $frame_data = $header_code . '030B0702'.NumToHex($command['port'],2).'01';
                        }elseif($command['cmd'] == 'stop'){
                            // 充电桩停止
                            $frame_data = $header_code . '030A070102';
                        }elseif ($command['cmd'] == 'recovery'){
                            // 充电桩恢复
                            $frame_data = $header_code . '030A070103';
                        }elseif ($command['cmd'] == 'restart'){
                            // 充电桩重启
                            $frame_data = $header_code . '030A070101';
                        }elseif ($command['cmd'] == 'devInitialize'){
                            // 充电桩格式化设备
                            $frame_data = $header_code . '030A070104';
                        }elseif($command['cmd'] == 'setWorkType'){
                            // 充电桩设置
                            if($command['type'] == 1) {
                                $frame_data = $header_code . '010b020101';
                            } else {
                                $frame_data = $header_code . '010b020100';
                            }
                        }elseif ($command['cmd'] == 'portStatus'){
                            // 充电桩插座状态获取
                            $frame_data = $header_code . '020A010101';
                        }elseif($command['cmd'] == 'updateTime'){
                            // 充电桩更新时间
                            $date_time = getTime(date('y'),date('m'),date('d'),date('H'),date('i'),date('s'));
                            $frame_data = $header_code . '010e0706'.$date_time;
                        }
                        if(!empty($frame_data)){
                            $frame_head = '7e';
                            $frame_version = '30';
                            $frame_device_id = $command['equipment_num'];
                            $frame_random_number = 'ffb7';
                            //$frame_data = $header_code . '0202010101';//查看设备通道状态
                            //$frame_data = $header_code . '030A070102'; //停止设备
                            //$frame_data = $header_code . '030A070103'; //回复设备
                            //$frame_data = $header_code . '030a070101'; //重启设备
                            //$frame_data = $header_code . '0105021D0064000038400036EE80000038400036EE800064000038400036EE8064'; //设置消费标准
                            //$frame_data = $header_code . '01020204000000DC';      //设置标准功率
                            $frame_data_length = lengthToHex($frame_data);
                            $frame_check = stringOr($frame_version.$frame_device_id.$frame_random_number.$frame_data_length.$frame_data);
                            $frame_tail = '7f';
                            $frame = getFrame($frame_head.$frame_version.$frame_device_id.$frame_random_number.$frame_data_length.$frame_data.$frame_check.$frame_tail);
                            $server->send($fd, hexToString($frame));
                            addNote('note_'.$command['cmd'].'_'.date('H'),'info',$fd.':'.$info[0]['equipment_num'],['addTimeText' => $addTimeText, 'equipment_num'=>$info[0]['equipment_num'],'type'=>'frame request','data'=>$frame,'command'=>$command]);
                            unset($result[$k]);
                        } else {
                           addNote('notice_note_timer'.date('H'),'timer',$fd,['line'=>__LINE__,'fd'=>$fd,'command'=>$command]);
                        }
                    }
                }
            }
        }
    }, $server);
});

function pileCmdSend($equipment_num,$fd,$server,$header_code) {
//        $times = time() - 86400; // 超过一天的指令不执行
    $times = time() - 600; // 缩短指令执行时间 10分钟内的
    $sql = "SELECT * FROM `pigcms_house_village_pile_command` WHERE `command` LIKE '".'%"equipment_num":"'.$equipment_num.'"%'."' AND type=1 AND addtime > ".$times." LIMIT 1";
    $cmdInfo = run_mysql($sql);
//    addNote('pileCmdSend_timer','timer',$fd,['line'=>__LINE__,'fd'=>$fd,'cmdInfo'=>$cmdInfo,'equipment_num'=>$equipment_num,'cmdInfo'=>$cmdInfo,'sql'=>$sql, 'type'=>'timer']);
    if (!empty($cmdInfo)) {
        $cmdInfo = reset($cmdInfo);
        $command = json_decode($cmdInfo['command'],true);
        $addtime = isset($cmdInfo['addtime']) ? intval($cmdInfo['addtime']) : 0;
        if ($addtime > 1) {
            $addTimeText = date('Y-m-d H:i:s', $addtime);
        } else {
            $addTimeText = '';
        }
        if(isset($command['equipment_num']) &&$command['equipment_num'] == $equipment_num){
            run_mysql('delete from pigcms_house_village_pile_command where id = '.$cmdInfo['id']);
            if($command['cmd'] == 'bindAttribute'){
                $attribute_info = unserialize($command['attribute_info']);
                switch ($command['attribute_id']){
                    case 1:
                        if ($header_code == '3004') {
                            $frame_data = $header_code . '0105021d'.NumToHex($attribute_info[0][2]*100,4).NumToHex($attribute_info[1][2]*60*60,8).NumToHex($attribute_info[2][2]*3600000,8).NumToHex($attribute_info[1][2]*60*60,8).NumToHex($attribute_info[2][2]*3600000,8).NumToHex($attribute_info[0][2]*100,4).NumToHex($attribute_info[1][2]*60*60,8).NumToHex($attribute_info[2][2]*3600000,8).'64';
                        } else {
                            $frame_data = $header_code . '0105021d'.NumToHex($attribute_info[0][2]*100,4).NumToHex($attribute_info[1][2]*60*60,8).NumToHex($attribute_info[2][2]*1000,8).NumToHex($attribute_info[1][2]*60*60,8).NumToHex($attribute_info[2][2]*1000,8).NumToHex($attribute_info[0][2]*100,4).NumToHex($attribute_info[1][2]*60*60,8).NumToHex($attribute_info[2][2]*1000,8).'64';
                        }
                        break;
                    case 2:
                        $frame_data = $header_code . '01020204'.NumToHex($attribute_info[0][2],8);
                        break;
                    case 4:
                        $frame_data = $header_code . '01010204'.NumToHex($attribute_info[0][2],8);
                        break;
                    case 5:
                        $frame_data = $header_code . '01070204'.NumToHex($attribute_info[0][2],8);
                        break;
                    case 6:
                        $frame_data = $header_code . '01030204'.NumToHex($attribute_info[1][2],4).NumToHex($attribute_info[0][2],4);
                        break;
                    case 7:
                        $frame_data = $header_code . '01040204'.NumToHex($attribute_info[1][2],4).NumToHex($attribute_info[0][2],4);
                        break;
                    case 8:
                        $frame_data = $header_code . '010C0208'.NumToHex($attribute_info[1][2],4).NumToHex($attribute_info[0][2],4);
                        break;
                    default:
                        $frame_data = '';
                }
            }elseif($command['cmd'] == 'newPriceStandardCharge'){
                // 开电
                if (isset($command['workType']) && isset($command['money'])) {
                    $workType = $command['workType'] ? $command['workType'] : '01';
                    $money = $command['money'] * 100;// 设备识别金额为 分 所以金额乘以100
                    $money = round($money, 0);
                    $frame_data = $header_code . '0301071702'.$command['orderNo'].NumToHex($command['port'],2).NumToHex($command['param']*3600000,8).NumToHex($command['time'],8).NumToHex($workType, 2).NumToHex($money, 8);
                } else {
                    $frame_data = $header_code . '0301071202'.$command['orderNo'].NumToHex($command['port'],2).NumToHex($command['param']*3600000,8).NumToHex($command['time'],8);
                }
            }elseif ($command['cmd'] == 'pileQueryRule'){
                // 查询费标准
                $frame_data = $header_code . '0105010101';
            }elseif ($command['cmd'] == 'pileQueryStorage'){
                // 查询订单存储
                $frame_data = $header_code . '020C010101';
            }elseif ($command['cmd'] == 'pileQueryOrder'){
                // 查询订单
                $frame_data = $header_code . '020D0104'.$command['orderNo'];
            }elseif ($command['cmd'] == 'stopCharging'){
                // 插座口停止
                $frame_data = $header_code . '030B0702'.NumToHex($command['port'],2).'01';
            }elseif($command['cmd'] == 'stop'){
                // 充电桩停止
                $frame_data = $header_code . '030A070102';
            }elseif ($command['cmd'] == 'recovery'){
                // 充电桩恢复
                $frame_data = $header_code . '030A070103';
            }elseif ($command['cmd'] == 'restart'){
                // 充电桩重启
                $frame_data = $header_code . '030A070101';
            }elseif($command['cmd'] == 'setWorkType'){
                // 充电桩设置
                if($command['type'] == 1) {
                    $frame_data = $header_code . '010b020101';
                } else {
                    $frame_data = $header_code . '010b020100';
                }
            }elseif ($command['cmd'] == 'portStatus'){
                // 充电桩插座状态获取
                $frame_data = $header_code . '020A010101';
            }elseif($command['cmd'] == 'updateTime'){
                // 充电桩更新时间
                $date_time = getTime(date('y'),date('m'),date('d'),date('H'),date('i'),date('s'));
                $frame_data = $header_code . '010e0706'.$date_time;
            }
            if(!empty($frame_data)){
                $frame_head = '7e';
                $frame_version = '30';
                $frame_device_id = $command['equipment_num'];
                $frame_random_number = 'ffb7';
                //$frame_data = $header_code . '0202010101';//查看设备通道状态
                //$frame_data = $header_code . '030A070102'; //停止设备
                //$frame_data = $header_code . '030A070103'; //回复设备
                //$frame_data = $header_code . '030a070101'; //重启设备
                //$frame_data = $header_code . '0105021D0064000038400036EE80000038400036EE800064000038400036EE8064'; //设置消费标准
                //$frame_data = $header_code . '01020204000000DC';      //设置标准功率
                $frame_data_length = lengthToHex($frame_data);
                $frame_check = stringOr($frame_version.$frame_device_id.$frame_random_number.$frame_data_length.$frame_data);
                $frame_tail = '7f';
                $frame = getFrame($frame_head.$frame_version.$frame_device_id.$frame_random_number.$frame_data_length.$frame_data.$frame_check.$frame_tail);
                $server->send($fd, hexToString($frame));
                addNote('pileCmdSend_'.$command['cmd'].'_','info',$fd.':'.$equipment_num,['addTimeText' => $addTimeText, 'equipment_num'=>$equipment_num,'type'=>'frame request','data'=>$frame]);
            } else {
                addNote('notice_pileCmdSend','timer',$fd,['line'=>__LINE__,'fd'=>$fd,'command'=>$command]);
            }
        }
    }
}

$server->addProcess($process);

//监听连接关闭事件
$server->on('Close', function ($server, $fd) {
    $info = run_mysql('select equipment_num from pigcms_house_village_pile_equipment where fd = '.$fd);
    echo "Client: Close".$fd."\n";
    run_mysql('update pigcms_house_village_pile_equipment set fd=0,last_heart_time=1,status=2,statusRemark="closeServer" where fd ='.$fd);
    addNote('note_close','close',$fd.':'.$info[0]['equipment_num'],['equipment_num'=>$info[0]['equipment_num'],'type'=>'close']);
});

//启动服务器
$server->start();

/**
 * 16进制转字符串
 * @author lijie
 * @date_time 2021/04/10
 * @param $string
 * @return false|string
 */
function hexToString($string){
    return pack("H*",$string);
}

/**
 * 截取字符串
 * @author lijie
 * @date_time 2021/04/09
 * @param string $string
 * @param int $begin
 * @param int $length
 * @return bool|string
 */
function getString($string='',$begin=0,$length=1)
{
    return substr($string,$begin,$length);
}

/**
 * 16进制转2进制
 * @author lijie
 * @date_time 2021/04/09
 * @param $hexdata
 * @return string
 */
function str2bin($hexdata)
{
    return decbin(hexdec($hexdata));
}

/**
 * 获取帧异或值
 * @author lijie
 * @date_time 2021/05/22
 * @param $string
 * @return string
 */
function stringOr($string)
{
    $arr = str_split($string,2);
    $hex = '';
    foreach ($arr as $k=>$v){
        if($k == 0){
            $hex = $v;
        }else{
            $hex= hexOr($hex,$v);
        }
    }
    if(strlen($hex) != 2){
        $add = '';
        for($i=0;$i<2-strlen($hex);$i++){
            $add .= '0';
        }
        $hex = $add.$hex;
    }
    return $hex;
}

/**
 * 计算2个字节的异或值
 * @author lijie
 * @date_time 2021/05/22
 * @param $byte1
 * @param $byte2
 * @return string
 */
function hexOr($byte1, $byte2)
{
    $result='';
    $byte1= str_pad(base_convert($byte1, 16, 2), '8', '0', STR_PAD_LEFT);
    $byte2= str_pad(base_convert($byte2, 16, 2), '8', '0', STR_PAD_LEFT);
    $len1 = strlen($byte1);
    for ($i = 0; $i < $len1 ; $i++) {
        $result .= $byte1[$i] == $byte2[$i] ? '0' : '1';
    }
    return strtoupper(base_convert($result, 2, 16));
}

/**
 * 数据长度
 * @author lijie
 * @date_time 2021/05/22
 * @param $string
 * @return string
 */
function lengthToHex($string)
{
    $length = dechex(strlen($string)/2);
    if(strlen($length) == 1)
        $length = '0'.$length;
    return $length;
}

/**
 * 在帧头 0x7E 和帧尾 0x7F 之间的数据出现的额 0x7D、0x7E、0x7F 分别与 0x20 异或
 * @author lijie
 * @date_time 2021/05/22
 * @param $frame
 * @return string
 */
function getFrame($frame)
{
    $frame = strtolower($frame);
    $arr = str_split($frame,2);
    foreach ($arr as $k=>&$v){
        if($k!=0 && $k+1!=count($arr)){
            switch ($v){
                case '7d':
                    $v = '7d5d';
                    break;
                case '7e':
                    $v = '7d5e';
                    break;
                case '7f':
                    $v = '7d5f';
                    break;
                default:
                    $v = $v;
            }
        }
    }
    return implode("",$arr);
}

/**
 * 在帧头 0x7E 和帧尾 0x7F 之间的数据出现的额 0x7D、0x7E、0x7F 分别与 0x20 异或
 * @author lijie
 * @date_time 2021/08/06
 * @param $frame
 * @return mixed
 */
function clearFrame($frame)
{
    $frame = str_replace("7d5f",'7f',$frame);
    $frame = str_replace("7d5e",'7e',$frame);
    $frame = str_replace("7d5d",'7d',$frame);
    return $frame;
}

/**
 * 将数字转换成16进制，长度不够的补0
 * @author lijie
 * @date_time 2021/05/26
 * @param $num
 * @param $len
 * @return string
 */
function NumToHex($num,$len)
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
 * 添加日志
 * @author lijie
 * @date_time 2021/05/27
 * @param string $fileName
 * @param string $noteType
 * @param string $address
 * @param array $data
 */
function addNote($fileName = '', $noteType = '', $address = '', $data = [])
{

    $path = dirname(__FILE__) . '/../../../api/log/' . date('Ymd') . '/pilecmd';
    $fileName = $path .'/' .$fileName . '_pile.log';
    if (is_array($data)) {
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    $dirName = dirname($path);
    if (!file_exists($dirName)) {
        try {
            $oldUmask = umask();
            umask(0);
            @mkdir($dirName, 0777, true);
            @chown($dirName, 'www');
            @chgrp($dirName, 'www');
            umask($oldUmask);
        } catch (\Exception $e) {
            umask($oldUmask);
        }
    }
    $dirName = dirname($fileName);
    if (!file_exists($dirName)) {
        try {
            $oldUmask = umask();
            umask(0);
            @mkdir($dirName, 0777, true);
            @chown($dirName, 'www');
            @chgrp($dirName, 'www');
            umask($oldUmask);
        } catch (\Exception $e) {
            umask($oldUmask);
        }
    }

    $info = var_export('[' . date('Y-m-d H:i:s', time()) . ']' . $noteType . ' ' . $address . ' ' . $data, true);
    @file_put_contents($fileName, trim($info, "'") . PHP_EOL, FILE_APPEND);

}

/**
 * 连接mysql
 * @author lijie
 * @date_time 2021/05/31
 * @param $sql
 * @return mixed
 */
function run_mysql($sql){
    global $database_config;
    //global $swoole_mysql;
    try{
        $swoole_mysql = new MySQL();
        $swoole_mysql->connect([
            'host'     => $database_config['DB_HOST'],
            'port'     => $database_config['DB_PORT'],
            'user'     => $database_config['DB_USER'],
            'password' => $database_config['DB_PWD'],
            'database' => $database_config['DB_NAME'],
        ]);
        $res = $swoole_mysql->query($sql);
        $swoole_mysql->close();//关闭连接
        if ($res===false) {
          //  addNote('sql_error','sql', 'sql_'.__LINE__,['res'=>$res,'sql'=>$sql,'connect_errno'=>$swoole_mysql->connect_errno,'connect_error'=>$swoole_mysql->connect_error]);
        }
    }catch (\Exception $e){
       // addNote('sql_error','sql', 'sql_'.__LINE__,['sqlErr'=>$e->getMessage()]);
        $swoole_mysql = new MySQL();
        $swoole_mysql->connect([
            'host'     => $database_config['DB_HOST'],
            'port'     => $database_config['DB_PORT'],
            'user'     => $database_config['DB_USER'],
            'password' => $database_config['DB_PWD'],
            'database' => $database_config['DB_NAME'],
        ]);
        $res = $swoole_mysql->query($sql);
        $swoole_mysql->close();//关闭连接
    }
    return $res;
    /*global $database_config;
    global $swoole_mysql;
    global $start_mysql;
    if(!$start_mysql){
        $swoole_mysql = new MySQL();
        $swoole_mysql->connect([
            'host'     => $database_config['DB_HOST'],
            'port'     => $database_config['DB_PORT'],
            'user'     => $database_config['DB_USER'],
            'password' => $database_config['DB_PWD'],
            'database' => $database_config['DB_NAME'],
        ]);
        $start_mysql = true;
    }else{
        try{
            $swoole_mysql_arr = (array)$swoole_mysql;
            if($swoole_mysql_arr['connected'] != true){
                try{
                    $swoole_mysql->close();//关闭连接
                }catch (Exception $e){
                    echo 456;
                }
                $swoole_mysql = new MySQL();
                $swoole_mysql->connect([
                    'host'     => $database_config['DB_HOST'],
                    'port'     => $database_config['DB_PORT'],
                    'user'     => $database_config['DB_USER'],
                    'password' => $database_config['DB_PWD'],
                    'database' => $database_config['DB_NAME'],
                ]);
            }
        }catch (Exception $e){
            try{
                $swoole_mysql->close();//关闭连接
            }catch (Exception $e){
                echo 789;
            }
            $swoole_mysql = new MySQL();
            $swoole_mysql->connect([
                'host'     => $database_config['DB_HOST'],
                'port'     => $database_config['DB_PORT'],
                'user'     => $database_config['DB_USER'],
                'password' => $database_config['DB_PWD'],
                'database' => $database_config['DB_NAME'],
            ]);
        }
    }
    $res = $swoole_mysql->query($sql);
    return $res;*/
}

/**
 * 获取需要支付的时间数
 * @author lijie
 * @date_time 2021/07/22
 * @param $des_time_level
 * @param $use_time
 * @return float|int
 */
function get_actual_use_time($des_time_level,$use_time)
{
    $des_time_level = $des_time_level*60;
    return ceil($use_time/$des_time_level)*$des_time_level;
}

/**
 * 获取需要支付的度数
 * @author lijie
 * @date_time 2021/07/22
 * @param $des_power_level
 * @param $use_ele
 * @return float|int
 */
function get_actual_use_ele($des_power_level,$use_ele)
{
    return ceil($use_ele/$des_power_level)*$des_power_level;
}

/**
 * @param $y
 * @param $m
 * @param $d
 * @param $h
 * @param $i
 * @param $s
 * @return string
 */
function getTime($y,$m,$d,$h,$i,$s)
{
    $time = NumToHex($y,2).NumToHex($m,2).NumToHex($d,2).NumToHex($h,2).NumToHex($i,2).NumToHex($s,2);
    return $time;
}