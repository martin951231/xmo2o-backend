<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/9/2 13:40
 */
namespace think;

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\MySQL\Connection;
use app\community\model\service\HouseNewEnerpyPileService;

require realpath( __DIR__ . '/../../vendor/autoload.php');
$database_config = include realpath(  __DIR__ . '/../../../conf/db.php');

$worker = new Worker('tcp://0.0.0.0:9588');
$db = new Connection($database_config['DB_HOST'], $database_config['DB_PORT'], $database_config['DB_USER'], $database_config['DB_PWD'], $database_config['DB_NAME']);

$worker->onMessage = function(TcpConnection $connection, $data)
{
    $data = bin2hex($data);
    echo date('Y-m-d H:i:s', time()) . "\n";
    echo $_SERVER['SERVER_NAME'];
    echo 'data------'.$data . "\n";
    //处理设备上报的信息
    uploadData($connection,$data);
    //主动给设备下发指令
    echo '主动给设备下发指令------'. "\n";
    send($connection,$data);
    //$connection->send("hello");
};


// 运行worker
Worker::runAll();

 $funcArr = [
    '01'=> 'loginAuth',//
    '03' => 'loginResponse',//
];

//主动给设备下发指令
function send($connection, $data = ''){
    echo '主动给设备下发指令1111------'. "\n";
    global $db;
    if (! $data) {
        return false;
    }
    $function_code = substr($data,10,2);
    $msg_data = substr($data,12,-4);
    if (!in_array($function_code,['33','3B','13'])){
        $equipment_num=substr($data,12,14);
    }else{
        $equipment_num=substr($msg_data,32,14);
    }
    $pile_info = $db->query('select * from pigcms_house_new_pile_equipment where is_del=1 and  equipment_num="'.$equipment_num.'" ');
    $equipment_id = isset($pile_info[0]['id']) && $pile_info[0]['id'] ? intval($pile_info[0]['id']) : 0;
    addNote('command', 'send', __LINE__, ['msg' => '主动给对应设备下发指令', 'pile_info' => $pile_info, 'equipment_id' => $equipment_id]);
    if ($equipment_id > 0) {
        $command_list = $db->query('select * from pigcms_house_new_pile_command where type=1 and status=1 and device_type=1 AND equipment_id='.$equipment_id.' order by id ASC limit 0,100');
        addNote('command', 'send', __LINE__, ['msg' => '主动给设备下发指令', 'count' => count($command_list), 'command_list' => $command_list]);
        if (!empty($command_list)){
            foreach ($command_list as $vv){
                $command=json_decode($vv['command'],true);
                $command['cmd']= pack("H*",$command['cmd']);
                $connection->send($command['cmd']);
                $db->query('update pigcms_house_new_pile_command set status=2 where id="'.$vv['id'].'" ');
            }
        }
    }
    //12=>读取实时监测数据
    //34=>运营平台远程控制启机
    //36=>运营平台远程停机
    //42=>远程账户余额更新
    //44=>离线卡数据同步
    //46=>离线卡数据清除
    //48=>离线卡数据查询
    //56=>对时设置
    //58=>计费模型设置
    
}

//设备上报信息
function  uploadData($connection,$data){
    global $db;
    global $funcArr;
    $service=new HouseNewEnerpyPileService();
    $function_code = substr($data,10,2);
    $id_code = substr($data,4,4);
    $msg_data = substr($data,12,-4);
    if (!in_array($function_code,['33','3B','13'])){
        $equipment_num=substr($data,12,14); 
    }else{
        $equipment_num=substr($msg_data,32,14);
    }
    $time = time();
    $down_arr=['function_code'=>$function_code,'id_code'=>$id_code,'equipment_num'=>$equipment_num,'msg_data'=>$msg_data];
    echo '设备编号------'.$equipment_num . "\n";
    addNote('uploadData'.$function_code, 'upload', __LINE__, ['msg' => '设备上报信息', 'data' => $data, 'equipment_num' => $equipment_num]);
    $pile_info = $db->query('select * from pigcms_house_new_pile_equipment where is_del=1 and  equipment_num="'.$equipment_num.'" ');
    if (!empty($pile_info)){
        $village_id=$pile_info[0]['village_id'];
        $charge_id=$pile_info[0]['charge_id'];
    }
    echo '接口编码------'.$function_code . "\n";
    echo '指令序号内容------'.$msg_data . "\n";
    addNote('uploadData'.$function_code, 'upload', __LINE__, ['接口编码' => $function_code, '指令序号内容' => $msg_data, 'pile_info' => $pile_info]);
    if ($function_code=='01'){
       //充电桩登录认证
      if (!empty($pile_info)){
          $down_arr['status']=0;
          $db->query('update pigcms_house_new_pile_equipment set last_heart_time='.$time.',status=1 where is_del=1 and  equipment_num="'.$equipment_num.'" ');
      }else{
           $down_arr['status']=1;
       }
       // 充电桩登录认证应答
        var_dump($down_arr);
        echo  "\n";
       downloadData($connection,$down_arr,$pile_info);
     }
   elseif($function_code=='03'){
       //充电桩心跳包
       $socket_num=substr($data,26,2);
       $down_arr['socket_num']=$socket_num;
       if (!empty($pile_info)&&!empty($pile_info[0]['socket_status'])){
           $socket_status=json_decode($pile_info[0]['socket_status']);
           echo '枪头状态------'.$socket_num. "\n";
           var_dump($socket_status);
           if ($socket_status[(int)$socket_num-1]!=1){
               $socket_status[(int)$socket_num-1]=0;
               $status=json_encode($socket_status);
               $db->query('update pigcms_house_new_pile_equipment set last_heart_time='.$time.',status=1,socket_status="'.$status.'" where is_del=1 and  equipment_num="'.$equipment_num.'" ');
           }
           // 充电桩心跳包应答
           downloadData($connection,$down_arr,$pile_info);
       }
   } 
   elseif($function_code=='05'){
       //计费模型验证请求
       $charge_num=substr($data,26,4);
      if (!empty($pile_info[0]['charge_id'])){
              $down_arr['charge_num']=$charge_num;
              $down_arr['charge_id']=$pile_info[0]['charge_id'];
              // 计费模型验证请求应答
              downloadData($connection,$down_arr,$pile_info);
       }
   } 
    elseif($function_code=='09'){
        var_dump($pile_info);
       //充电桩计费模型请求
       if (!empty($pile_info)){
           $village_id=$pile_info[0]['village_id'];
           $charge_id=$pile_info[0]['charge_id'];
           echo 'village_id=>'.$village_id.'----charge_id=>'.$charge_id."\n";
           $charge_info = $db->query('select * from pigcms_house_new_pile_charge where status=1 and  village_id='.$village_id.' and rule_id='.$charge_id.' order by charge_valid_time DESC limit 0,1');
           var_dump($charge_info);
           if (!empty($charge_info)) {
               $down_arr['charge_info']=$charge_info[0];
               // 充电桩心跳包应答
               downloadData($connection,$down_arr,$pile_info);
           }
       }

   }
    elseif($function_code=='13'){
       //上传实时监测数据
        var_dump($pile_info[0]['id']);
        $down_arr['order_serial']=substr($msg_data,0,32);//流水号
        $down_arr['equipment_id']=$pile_info[0]['id'];
        $down_arr['socket_num']=substr($msg_data,46,2);//枪号
        $down_arr['status']=substr($msg_data,48,2);//状态 0x00：离线 0x01：故障 0x02：空闲 0x03：充电
        $down_arr['socket_status']=substr($msg_data,50,2);//枪是否归位 0x00 否 0x01 是 0x02 未知
        $down_arr['socket_status1']=substr($msg_data,52,2);//是否插枪 0x00 否 0x01 是
        $down_arr['voltage']=BinHexToNum(substr($msg_data,54,4),10);//输出电压
        $down_arr['electric_current']=BinHexToNum(substr($msg_data,58,4),10);//输出电流
        $down_arr['socket_line_temperature']=substr($msg_data,62,2);//枪线温度
        $down_arr['socket_line_num']=substr($msg_data,64,16);//枪线编码
        $down_arr['soc']=substr($msg_data,80,2);//SOC
        $down_arr['temperature']=BinHexToNum(substr($msg_data,82,2),10);//电池组最高温度
        $down_arr['continued_time']=BinHexToNum(substr($msg_data,84,4),0);//累计充电时间 单位：min；待机置零
        $down_arr['surplus_time']=BinHexToNum(substr($msg_data,88,4),0);//剩余时间 单位：min；待机置零、交流桩置零
        $down_arr['use_ele']=BinHexToNum(substr($msg_data,92,8),10000);//充电度数 精确到小数点后四位；待机置零
        $down_arr['loss_ele']=BinHexToNum(substr($msg_data,100,8),10000);//计损充电度数 精确到小数点后四位；待机置零未设置计损比例时等于充电度数
        $down_arr['use_money']=BinHexToNum(substr($msg_data,108,8),10000);//已充金额 精确到小数点后四位；待机置零（电费+服务费）*计损充电度数
        $down_arr['e_status']=substr($msg_data,116,4);//硬件故障
        addNote('uploadData'.$function_code, 'upload', __LINE__, ['function_code' => $function_code,'msg' => '上传实时监测数据', 'down_arr' => $down_arr]);
        curlPost('https://s.gtcdz.com/v20/public/index.php/community/village_api.Pile/getPileRealTimeInfo',$down_arr);
     //    $service->getPileRealTimeInfo($down_arr);
   }
   /*  设备目前无法上报接口数据
   
   elseif($function_code=='15'){
        //充电握手
   } elseif($function_code=='17'){
       //参数配置

   } elseif($function_code=='19'){
       //充电结束

   }elseif($function_code=='1B'){
       //错误报文

   } elseif($function_code=='1D'){
       //充电阶段 BMS 中止

   }*/
    elseif($function_code=='31'){
       //充电桩主动申请启动充电
        $down_arr['socket_num']=substr($msg_data,14,2); 
        $start_type=substr($msg_data,16,2);
        addNote('uploadData'.$function_code, 'upload', __LINE__, ['function_code' => $function_code,'msg' => '充电桩主动申请启动充电', 'down_arr' => $down_arr, 'start_type' => $start_type]);
        if ($start_type=='01'){
            $pile_card_no=substr($msg_data,20,16);
            $card_info = $db->query('select pile_card_no,current_money from pigcms_house_new_pile_user_money where business_id="'.$village_id.'" and  pile_card_no="'.$pile_card_no.'" ');
            if (!empty($card_info)){
                $card_info=$card_info[0];
                $down_arr['status']='01';
                $down_arr['card_no']=$card_info['card_no'];
                $down_arr['money']=NumToBinHex($card_info['current_money']*100,8);
                $down_arr['respond']='00';
            }else{
                $down_arr['status']='00';
                $down_arr['card_no']='0000000000000000';
                $down_arr['money']='00000000';
                //todo 需要根据实际来返回失败原因
                $down_arr['respond'] = '03';
            }
            addNote('uploadData'.$function_code, 'upload', __LINE__, [
                'function_code' => $function_code,
                'msg' => '充电桩主动申请启动充电',
                'down_arr' => $down_arr
            ]);
            downloadData($connection,$down_arr,$pile_info);
        }
    } 
    elseif($function_code=='33'){
        $down_arr['order_serial']=substr($msg_data,0,32);
        $down_arr['equipment_id']=$pile_info[0]['id'];
        $down_arr['socket_num']=substr($msg_data,46,2);
        $down_arr['status']=substr($msg_data,48,2);
        $down_arr['end_result']=substr($msg_data,50,2);
        addNote('uploadData'.$function_code, 'upload', __LINE__, [
            'function_code' => $function_code,
            'msg' => '远程启动充电命令回复',
            'down_arr' => $down_arr
        ]);
       //远程启动充电命令回复
        curlPost('https://s.gtcdz.com/v20/public/index.php/community/village_api.Pile/startCharging_tcp',$down_arr);
       //  $service->startCharging_tcp($down_arr);
   }
   elseif($function_code=='35'){
       //远程停机命令回复
       $down_arr['equipment_id']=$pile_info[0]['id'];
       $down_arr['socket_num']=substr($msg_data,14,2);
       $down_arr['status']=substr($msg_data,16,2);
       $down_arr['end_result']=substr($msg_data,18,2);
       addNote('uploadData'.$function_code, 'upload', __LINE__, [
           'function_code' => $function_code,
           'msg' => '远程停机命令回复',
           'down_arr' => $down_arr
       ]);
       if ($down_arr['status']=='01'){
           //远程启动充电命令回复
           curlPost('https://s.gtcdz.com/v20/public/index.php/community/village_api.Pile/stopCharging_tcp',$down_arr);
         //   $service->stopCharging_tcp($down_arr);
       }
   }
   elseif($function_code=='3B'||$function_code=='3b'){ //上报交易记录
       $down_arr['order_serial']    = substr($msg_data,0,32);
       $down_arr['equipment_id']    = $pile_info[0]['id'];
       $arr_data=$down_arr;
       $down_arr['socket_num']      = substr($msg_data,46,2);
       $down_arr['time_start']      = cp56Time2aToTime(substr($msg_data,48,14));//充电开始时间
       $down_arr['time_end']        = cp56Time2aToTime(substr($msg_data,62,14));//充电结束时间
       $down_arr['unitCharge1']     = BinHexToNum(substr($msg_data,76,8),100000);//尖单价
       $down_arr['ele1']            = BinHexToNum(substr($msg_data,84,8),10000);//尖电量
       $down_arr['loss_ele1']       = BinHexToNum(substr($msg_data,92,8),10000);//计损尖电量
       $down_arr['price1']          = BinHexToNum(substr($msg_data,100,8),10000);//尖金额
       $down_arr['unitCharge2']     = BinHexToNum(substr($msg_data,108,8),100000);//峰单价
       $down_arr['ele2']            = BinHexToNum(substr($msg_data,116,8),10000);//峰电量
       $down_arr['loss_ele2']       = BinHexToNum(substr($msg_data,124,8),10000);//计损峰电量
       $down_arr['price2']          = BinHexToNum(substr($msg_data,132,8),10000);//峰金额
       $down_arr['unitCharge3']     = BinHexToNum(substr($msg_data,140,8),100000);//平单价
       $down_arr['ele3']            = BinHexToNum(substr($msg_data,148,8),10000);//平电量
       $down_arr['loss_ele3']       = BinHexToNum(substr($msg_data,156,8),10000);//计损平电量
       $down_arr['price3']          = BinHexToNum(substr($msg_data,164,8),10000);//平金额
       $down_arr['unitCharge4']     = BinHexToNum(substr($msg_data,172,8),100000);//谷单价
       $down_arr['ele4']            = BinHexToNum(substr($msg_data,180,8),10000);//谷电量
       $down_arr['loss_ele4']       = BinHexToNum(substr($msg_data,188,8),10000);//计损谷电量
       $down_arr['price4']          = BinHexToNum(substr($msg_data,196,8),10000);//谷金额
       $down_arr['last_ammeter']    = BinHexToNum(substr($msg_data,204,10),10000);//电表总起值
       $down_arr['now_ammeter']     = BinHexToNum(substr($msg_data,214,10),10000);//电表总止值
       $down_arr['sum_ele']         = BinHexToNum(substr($msg_data,224,8),10000);//总电量
       $down_arr['sum_loss_ele']    = BinHexToNum(substr($msg_data,232,8),10000);//计损总电量
       $down_arr['sum_price']       = BinHexToNum(substr($msg_data,240,8),10000);//消费金额
       $down_arr['order_flag']      = substr($msg_data,-34,2);//交易标识
       $down_arr['order_time']      = substr($msg_data,-32,14);//交易日期、时间
       $down_arr['end_result']      = substr($msg_data,-18,2);//停止原因
       $down_arr['pile_card_no']    = substr($msg_data,-16);//物理卡号
       echo '设备上报交易记录------'. "\n";
       addNote('uploadData'.$function_code, 'upload', __LINE__, [
           'function_code' => $function_code,
           'msg' => '设备上报交易记录',
           'down_arr' => $down_arr
       ]);
       $socket_num=(int)$down_arr['socket_num'];
       $order_list = $db->query('select id,charge_id from pigcms_house_new_pile_pay_order where order_serial="'.$down_arr['order_serial'].'" and   equipment_id="'.$down_arr['equipment_id'].'" and socket_no="'.$socket_num.'" order by id DESC ');
       $arr_data['status']=$down_arr['status']='01';
       if (!empty($order_list)){
           $arr_data['status']=$down_arr['status']='00';
       }
       //上报交易记录应答
       echo '上报交易记录应答------'. "\n";
       addNote('uploadData'.$function_code, 'upload', __LINE__, [
           'function_code' => $function_code,
           'msg' => '上报交易记录应答',
           'down_arr' => $down_arr,
           'arr_data' => $arr_data
       ]);
       downloadData($connection,$arr_data,$pile_info);
       curlPost('https://s.gtcdz.com/v20/public/index.php/community/village_api.Pile/settlementOrder_tcp',$down_arr);
       // $service->settlementOrder_tcp($down_arr);
      
   } 
   elseif($function_code=='41'){//余额更新应答
       

   } 
   elseif($function_code=='43'){
       //离线卡数据同步应答

   }elseif($function_code=='45'){
       //离线卡数据清除应答

   }elseif($function_code=='47'){
       //离线卡数据查询应答

   } elseif($function_code=='51'){
       //充电桩工作参数设置应答

   } elseif($function_code=='55'){
       //对时设置应答

   }elseif($function_code=='57'){
       //计费模型应答

   }
    $equipment_id = isset($pile_info[0]['id']) && $pile_info[0]['id'] ? intval($pile_info[0]['id']) : 0;
        //写入指令表
    $db->query('insert into pigcms_house_new_pile_command (command,type,status,addtime,equipment_id) VALUES ("'.$data.'",2,1,'.$time.','.$equipment_id.')');
}

function  msgDataBinHexToNum($msg_data,$start,$length,$rate=10000)
{
  return  BinHexToNum(substr($msg_data,$start,$length),$rate);
}

//给设备下发应答指令
function downloadData($connection,$data, $pile_info = []){
    if ($data['function_code']=='01'){
        // 充电桩登录认证应答
        if ($data['status']==0){
            addNote('downloadData01', 'download', __LINE__, [
                'msg' => '给设备下发应答指令',
                'data' => $data,
                'pile_info' => $pile_info,
            ]);
            //登录成功
            $checkResult=wordStr($data['id_code'].'0002'.$data['equipment_num'].'00');
            $msg='680C'.$data['id_code'].'0002'.$data['equipment_num'].'00'.$checkResult;
            if (isset($pile_info[0]['id'])) {
                $down_arr['equipment_id'] = $pile_info[0]['id'];
                $down_arr['status'] = 00;
                addNote('downloadData01', 'download', __LINE__, [
                    'msg' => '给设备下发对时指令',
                    'data' => $data,
                    'down_arr' => $down_arr,
                ]);
                curlPost('https://s.gtcdz.com/v20/public/index.php/community/village_api.Pile/setPileTimeAndCharge', $down_arr);
            }
        }else{
            //登录失败
            $checkResult=wordStr($data['id_code'].'0002'.$data['equipment_num'].'01');
            $msg='680C'.$data['id_code'].'0002'.$data['equipment_num'].'01'.$checkResult; 
        }
        addNote('downloadData01', 'download', __LINE__, [
            'msg' => '充电桩登录认证应答',
            'sendMsg' => $msg,
        ]);
        echo '下发应答指令内容------'.$msg . "\n";
        $msg= pack("H*",$msg);
        $connection->send($msg);
    }
    elseif($data['function_code']=='03'){
        // 心跳包应答
        $str=$data['equipment_num'].$data['socket_num'].'00';
        $checkResult=wordStr($data['id_code'].'0004'.$str);
        $msg='680D'.$data['id_code'].'0004'.$str.$checkResult;
        echo '下发应答指令内容------'.$msg . "\n";
        addNote('downloadData03', 'download', __LINE__, [
            'msg' => '心跳包应答',
            'pile_info' => $pile_info,
            'sendMsg' => $msg,
        ]);
        $msg= pack("H*",$msg);
        $connection->send($msg);
    }
    elseif($data['function_code']=='05'){
        // 计费模型验证请求应答
        if ($data['charge_num']!=$data['charge_id']){
            $status='01';
        }else{
            $status='00';
        }
        $charge_id=NumToHex($data['charge_info']['id'],4);
        $str=$data['equipment_num'].$charge_id.$status;
        $len=$data['id_code'].'0006'.$str;
        $checkResult=wordStr($len);
        $length = strlen($len)/2; // 长度
        $length = dechex($length);
        $msg='68'.$length.$len.$checkResult;
        addNote('downloadData05', 'download', __LINE__, [
            'msg' => '计费模型验证请求应答',
            'pile_info' => $pile_info,
            'status' => $status,
            'sendMsg' => $msg,
        ]);
        $msg= pack("H*",$msg);
        $connection->send($msg);
    }
    elseif($data['function_code']=='09'){
        // 计费模型请求应答
        $charge_id=NumToBinHex($data['charge_info']['id'],4);
        $charge_time=json_decode($data['charge_info']['charge_time'],JSON_UNESCAPED_UNICODE);
        $charge_1=json_decode($data['charge_info']['charge_1'],JSON_UNESCAPED_UNICODE);
        $charge_2=json_decode($data['charge_info']['charge_2'],JSON_UNESCAPED_UNICODE);
        $charge_3=json_decode($data['charge_info']['charge_3'],JSON_UNESCAPED_UNICODE);
        $charge_4=json_decode($data['charge_info']['charge_4'],JSON_UNESCAPED_UNICODE);
        $charge_1_ele=NumToBinHex($charge_1['charge_ele']*100000,8);
        $charge_2_ele=NumToBinHex($charge_2['charge_ele']*100000,8);
        $charge_3_ele=NumToBinHex($charge_3['charge_ele']*100000,8);
        $charge_4_ele=NumToBinHex($charge_4['charge_ele']*100000,8);
        $charge_1_serve=NumToBinHex($charge_1['charge_serve']*100000,8);
        $charge_2_serve=NumToBinHex($charge_2['charge_serve']*100000,8);
        $charge_3_serve=NumToBinHex($charge_3['charge_serve']*100000,8);
        $charge_4_serve=NumToBinHex($charge_4['charge_serve']*100000,8);
        $str=$data['equipment_num'].$charge_id.$charge_1_ele.$charge_1_serve.$charge_2_ele.$charge_2_serve.$charge_3_ele.$charge_3_serve.$charge_4_ele.$charge_4_serve.'00';
        $charge_time_arr=pile_time();
        $charge_num=[];
        foreach ($charge_time as $v){
            foreach ($charge_time_arr as $k=>$vv){
                if ($v['time_start']<=$vv&&$v['time_end']>$vv){
                    $charge_num[$k]=$v['money'];
                    unset($charge_time_arr[$k]);
                }
            }
        }
        if ($data['charge_info']['price_set_value']!='-1'&&!empty($charge_time_arr)){
            foreach ($charge_time_arr as $k=>$vv1){
                    $charge_num[$k]=$data['charge_info']['price_set_value'];
                    unset($charge_time_arr[$k]);
            }
        }
        if (!empty($charge_num)){
            ksort($charge_num);
            foreach ($charge_num as $vs){
                $str.=NumToHex($vs,2);
            } 
        }
        
        $checkResult=wordStr($data['id_code'].'000A'.$str);
        $len=$data['id_code'].'000A'.$str;
        $length = strlen($len)/2; // 长度
        $length = dechex($length);
        $msg='68'.$length.$len.$checkResult;
        echo '下发应答指令内容------'.$msg . "\n";
        addNote('downloadData09', 'download', __LINE__, [
            'msg' => '计费模型请求应答',
            'pile_info' => $pile_info,
            'sendMsg' => $msg,
        ]);
        $msg= pack("H*",$msg);
        $connection->send($msg);
    }
    elseif($data['function_code']=='31'){
        // 运营平台确认启动充电
        $order_no=$data['equipment_num'].$data['socket_num'].date('ymdHis').rand(1000,9999);
        $str=$order_no.$data['equipment_num'].$data['socket_num'].$data['card_no'].$data['money'].$data['status'].$data['respond'];
        $len=$data['id_code'].'0032'.$str;
        $checkResult=wordStr($len);
        $length = strlen($len)/2; // 长度
        $length = dechex($length);
        $msg='68'.$length.$len.$checkResult;
        addNote('downloadData31', 'download', __LINE__, [
            'msg' => '运营平台确认启动充电',
            'pile_info' => $pile_info,
            'sendMsg' => $msg,
        ]);
        $msg= pack("H*",$msg);
        $connection->send($msg);
    }
    elseif($data['function_code']=='3B'||$data['function_code']=='3b'){
        //上报交易记录应答
        $str=$data['order_serial'].$data['status'];
        $len=$data['id_code'].'0040'.$str;
        $checkResult=wordStr($len);
        $length = strlen($len)/2; // 长度
        $length = dechex($length);
        $msg='68'.$length.$len.$checkResult;
	 echo '下发应答指令内容------'.$msg . "\n";
        addNote('downloadData3b', 'download', __LINE__, [
            'msg' => '上报交易记录应答',
            'pile_info' => $pile_info,
            'sendMsg' => $msg,
        ]);
        $msg= pack("H*",$msg);
        $connection->send($msg);
    }
}

/**
 * $time  unix时间戳
 * @author:zhubaodi
 * @date_time: 2022/9/16 14:23
 */
function timeTocp56Time2a($time){
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
    $date_his[2]=NumToHex($date_his[2]*1000,4);
    if (count($date_his)!=3){
        return false;
    }
    $day_m=NumToBin($date_ymd[2],5,10,2);
    $day_w=NumToBin($week,3,10,2);
    $day=NumToBin($day_w.$day_m,2,2,16);
    $date_s1=substr($date_his[2],0,2);
    $date_s2=substr($date_his[2],2,2);
    $str=$date_s2.$date_s1.NumToHex($date_his[1],2).NumToHex($date_his[0],2).$day.NumToHex($date_ymd[1],2).NumToHex($date_ymd[0],2);
    return  $str;
}

/**
 * $time  cp56Time2a时标
 * @author:zhubaodi
 * @date_time: 2022/9/16 14:23
 */
function cp56Time2aToTime($time){
    if (empty($time)){
        return false;
    }
    $len=strlen($time);
    for($i=0;$i<$len;$i=$i+2){
        $arr[]=$time[$i+1].$time[$i];
    }
    if (count($arr)!=7){
        return false;  
    }
    $date_arr['s']=intval(dechex($arr[1].$arr[0])/1000);
    $date_arr['i']=dechex($arr[2]);
    $date_arr['h']=dechex($arr[3]);
    $date_arr['d']=NumToBin($date_arr['d'],8,16,2);
    $date_arr['d']=NumToBin(substr($date_arr['d'],3),2,2,10);
    $date_arr['m']=dechex($arr[5]);
    $date_arr['y']=dechex($arr[6]);
    
    $date=$date_arr['y'].'-'.$date_arr['m'].'-'.$date_arr['d'];
    $time=$date_arr['h'].':'.$date_arr['i'].':'.$date_arr['s'];
    
    $str=strtotime($date.$time);
    return  $str;
}


 function wordStr($wordStr) {
    $wordStr = str_replace(' ','',$wordStr);
    $wordCmd = $wordStr;
    $packStr = pack('H*',$wordStr);// 把数据装入一个二进制字符串[十六进制字符串，高位在前]
    $crc166Str = crc166($packStr);// 进行CRC16校验
    $unpackStr = unpack("H*", $crc166Str); // 从二进制字符串对数据进行解包[十六进制字符串，高位在前];
    return strtoupper($unpackStr[1]);
}

 function crc166($string, $length = 0)
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
    return(chr($uchCRCLo).chr($uchCRCHi));
}

function pile_time()
{
    $pile_time=[
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
    return $pile_time;
}




/**
 * 将数字转换成16进制，长度不够的补0
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

/**
 * 将十进制数字转换成低位在前的十六进制，长度不够的补0
 * @param $num
 * @param $start  //数字类型
 * @param $end  //待转换数字类型
 * @param $len
 * @return string
 */
function NumToBinHex($num,$len,$start=10,$end=16)
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
 * 将十进制数字转换成低位在前的十六进制，长度不够的补0
 * @param $num
 * @param $start  //数字类型
 * @param $end  //待转换数字类型
 * @param $len
 * @return string
 */
function BinHexToNum($num,$rate,$start=16,$end=10)
{
    $arr=[];
    for ($i=0;$i<strlen($num);$i=$i+2){
        $arr[]=$num[$i].$num[$i+1];
    }
    krsort($arr);
    $hex=implode('',$arr);
    $num_hex = base_convert($hex,$start,$end);
    if (!empty($rate)){
        $num_hex=round($num_hex/$rate,4);
    }
    return $num_hex;
}


function curlPost($url,$data,$timeout=45,$header = "Content-type: application/x-www-form-urlencoded;charset=utf-8"){

    $ch = curl_init();
    $headers[] = $header;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.80 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch ,CURLOPT_TIMEOUT ,$timeout);
    $result = curl_exec($ch);
    curl_close($ch);
    //  $result = json_decode($result, true);
    /*if ($result && $result['rstCode']) {
        $rstCodeMsg = $this->a4_rstCode($result['rstCode']);
        $result['rstCodeMsg'] = $rstCodeMsg;
    }*/
    addNote('curlPost', 'curlPost', __LINE__, [
        'msg' => '接口请求',
        'url' => $url,
        'data' => $data,
        'result' => $result,
    ]);
    return $result;
}

/**
 * 添加日志
 * @date_time 2021/05/27
 * @param string $fileName
 * @param string $noteType
 * @param string $address
 * @param array $data
 */
function addNote($fileName = '', $noteType = '', $address = '', $data = [])
{
    try {
        $path = dirname(__FILE__) . '/../../../api/log/' . date('Ymd') . '_pile/v20_tools/';
        $fileName = $path . 'energy_' . $fileName . '_pile.log';
        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
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
        file_put_contents($fileName, trim($info, "'") . PHP_EOL, FILE_APPEND);
    } catch( \Exception $e) {}
}