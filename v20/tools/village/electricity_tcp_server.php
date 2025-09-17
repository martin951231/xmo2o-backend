<?php

/**
 * 电表TCP交互 376.1 国标
 * Created by vscode.
 * Author: Jaty
 * Date Time: 2021/4/2 13:46
 */

//创建Server对象，监听 127.0.0.1:9501 端口
$redis = new Redis();
$host = '117.159.29.130';
$port = 6379;
$pass = 'qazWSX123';
$redis->pconnect($host, $port);
$redis->auth($pass); //密码验证
addNote('note','clear_redis',0,['type'=>'clear_redis']);
$key_list = $redis->KEYS('*');
foreach ($key_list as $v){
    if(strpos($v,'download') !== false || strpos($v,'upload') !== false){
        continue;
    }else{
        $redis->del($v);
    }
}
$start_time = time();
$server = new Swoole\Server('0.0.0.0', 6010);
$server->set([
    'daemonize'      => true,
    'dispatch_mode' => 2,
    'log_file' => 'log_file.log',
    'user' => 'www',
    'group'=>'www',
]);
//监听连接进入事件
$server->on('Connect', function ($server, $fd) {
    addNote('note','connect',$fd,['type'=>'connect request','data'=>$server]);
    echo "Client: Connect".$fd."\n";
});

//监听数据接收事件
$server->on('Receive', function ($server, $fd, $reactor_id, $data) {
    $redis = redisCli();
    $data = bin2hex($data);
    echo date('Y-m-d H:i:s', time()) . "\n";
    echo $data . "\n";
    handle_frame($data,$server,$fd,$redis);
});

//利用swoole的定时器，定时请求第三方接口，将数据实时推送到客户端
$process = new Swoole\Process(function($process) use ($server) {
    Swoole\Timer::tick(5000, function (int $timer_id, $server) {
        $redis = redisCli();
        foreach ($server->connections  as $fd){
            /*if($redis->HGet($fd,'logical_address') == '0371003c00'){
                $user_data = '5B71033c00000C6B04010110';//数据体（从控制域一直到CS,注意：不包含CS）
                $CS = hexSum($user_data);  //用户数据区8位位组算术和
                $len = dechex(bindec(decbin(strlen($user_data)/2).'10')).'00';
                $frame = '68'.$len.$len.'68'.$user_data.$CS.'16';
                $server->send($fd, hexToString($frame));
                addNote('note','info',$fd.':'.$redis->HGet($fd,'logical_address'),['address'=>$redis->HGet($fd,'logical_address'),'type'=>'now_freeze request','data'=>$frame]);
            }*/
            /*$data_unit = '02010110';//数据单元
            $user_data = '4b1214b10c000C60'.$data_unit;//数据体（从控制域一直到CS,注意：不包含CS）
            $CS = hexSum($user_data);  //用户数据区8位位组算术和
            $len = dechex(bindec(decbin(strlen($user_data)/2).'10')).'00';
            $frame = '68'.$len.$len.'68'.$user_data.$CS.'16';
            $server->send($fd, hexToString($frame));
            file_put_contents('frame.txt', var_export($frame.'-----------'.date('Y-m-d H:i:s',time()), true) . PHP_EOL .PHP_EOL, FILE_APPEND);*/
            /*if($redis->HGet($fd,'logical_address') == '8111232a00'){
                $a = '6863800287810068110433343333';
                $cs = hexSum($a);
                $a .= $cs.'16';
                $user_data = '4B11812a23001063000001001f6b8a141c00'.$a.'00000000000000000000000000000000';
                $CS = hexSum($user_data);  //用户数据区8位位组算术和
                $len = dechex(bindec(decbin(strlen($user_data)/2).'10')).'00';
                $frame = '68'.$len.$len.'68'.$user_data.$CS.'16';  //日冻结请求帧
                $server->send($fd, hexToString($frame));
                addNote('note','info',$fd.':'.$redis->HGet($fd,'logical_address'),['address'=>$redis->HGet($fd,'logical_address'),'type'=>'now_freeze request','data'=>$frame]);
            }*/
            /*if($redis->exists($fd) && $redis->HGet($fd,'last_heart_time') >0){
                try{
                    if(time() - $redis->HGet($fd,'last_heart_time') > 600){
                        $send_redis_data = [];
                        $send_redis_data['cmd'] = 'except';
                        $send_redis_data['logical_address'] = $redis->HGet($fd,'logical_address');
                        $send_redis_data['date']['I'] = date('i');
                        $send_redis_data['date']['H'] = date('H');
                        $send_redis_data['date']['D'] = date('d');
                        $send_redis_data['date']['M'] = date('m');
                        $send_redis_data['date']['Y'] = date('y');
                        $redis->RPush('upload',json_encode($send_redis_data));
                        $redis->del($redis->HGet($fd,'logical_address'));
                        $redis->del($fd);
                        continue;
                    }
                }catch (Exception $e){
                    print $e->getMessage();
                    continue;
                }
            }else{
                $server->close($fd, true);
                continue;
            }*/
            addNote('fd_record','info',$fd);
            if(!$redis->hGetAll($fd)){
                addNote('fd','fd',$fd);
                continue;
            }
            $logicalAddress = $redis->HGet($fd,'logical_address');
            $redis_list_name = 'download_'.$logicalAddress;
            addNote('download','fd',$redis_list_name);
            $bool = $redis->lLen($redis_list_name);
            if($bool){
                $redis_list_lenth = $redis->lLen($redis_list_name);
                addNote('redis_list_lenth','fd',$redis_list_lenth);
                if($redis_list_lenth >0){
                    for ($i=0;$i<$redis_list_lenth;$i++){
                        $download_data = $redis->LPop($redis_list_name);
                        if (!is_array($download_data)) {
                            $download_data = json_decode($download_data,true);
                        }
                        //开关闸
                        if($download_data['cmd'] == 'switch'){
                            if($download_data['electric_type'] == 1)
                                $pass = '35';
                            else
                                $pass = '35';
                            $time = time()+60*5;
                            $s = '88';
                            $f = dechex(hexdec(date('i',$time))+51);
                            $h = dechex(hexdec(date('H',$time))+51);
                            $d = dechex(hexdec(date('d',$time))+51);
                            $m = dechex(hexdec(date('m',$time))+51);
                            $y = dechex(hexdec(date('y',$time))+51);
                            //拉闸
                            if($download_data['type'] == 'close'){
                                $a = '68'.positionReversal($download_data['energy_meter_address']).'681c10'.$pass.'333333333333334d33'.$s.$f.$h.$d.$m.$y;
                                $cs = hexSum($a);
                                $a .= $cs.'16';
                                $user_data = '4B'.$redis->HGet($fd,'reversal_logical_address').'1062000001001f6b8a141c00'.$a.'00000000000000000000000000000000';
                                $CS = hexSum($user_data);  //用户数据区8位位组算术和
                                $frame = '68fa00fa0068'.$user_data.$CS.'16';  //日冻结请求帧
                                $server->send($fd, hexToString($frame));
                                addNote('note','info',$fd.':'.$logicalAddress,['address'=>$logicalAddress,'type'=>'close request ','data'=>$frame]);
                            }
                            //合闸
                            if($download_data['type'] == 'open'){
                                $a = '68'.positionReversal($download_data['energy_meter_address']).'681c10'.$pass.'333333333333334f33'.$s.$f.$h.$d.$m.$y;
                                $cs = hexSum($a);
                                $a .= $cs.'16';
                                $user_data = '4B'.$redis->HGet($fd,'reversal_logical_address').'1063000001001f6b8a141c00'.$a.'00000000000000000000000000000000';
                                $CS = hexSum($user_data);  //用户数据区8位位组算术和
                                $len = dechex(bindec(decbin(strlen($user_data)/2).'10')).'00';
                                $frame = '68'.$len.$len.'68'.$user_data.$CS.'16';  //日冻结请求帧
                                $server->send($fd, hexToString($frame));
                                addNote('note','info',$fd.':'.$logicalAddress,['address'=>$logicalAddress,'type'=>'open request','data'=>$frame]);
                            }
                        }
                        //集中器电表绑定
                        if($download_data['cmd'] == 'bang'){
                            $measuring_point = $download_data['measuring_point'];
                            $measuring_point = getBandPn($measuring_point);
                            $user_data = '4A'.$redis->HGet($fd,'reversal_logical_address').'047C000002010100'.$measuring_point.$measuring_point.'df1E'.positionReversal($download_data['energy_meter_address']).'000000020000040B0000000000000000000000000000000000000000000000';
                            $CS = hexSum($user_data);  //用户数据区8位位组算术和
                            $len = dechex(bindec(decbin(strlen($user_data)/2).'10')).'00';
                            $frame = '68'.$len.$len.'68'.$user_data.$CS.'16';  //请求帧
                            $server->send($fd, hexToString($frame));
                            addNote('note','info',$fd.':'.$logicalAddress,['address'=>$logicalAddress,'type'=>'bind request','data'=>$frame]);
                        }
                        //抄表
                        if($download_data['cmd'] == 'meter_reading'){
                            foreach ($download_data['data'] as $v){
                                $pn = getPn($v['measuring_point']);
                                $data_unit = $pn.'0114'.$v['date']['D'].$v['date']['M'].$v['date']['Y'];//数据单元
                                $user_data = '4b'.$redis->HGet($fd,'reversal_logical_address').'0D60'.$data_unit;//数据体（从控制域一直到CS,注意：不包含CS）
                                $CS = hexSum($user_data);  //用户数据区8位位组算术和
                                $len = dechex(bindec(decbin(strlen($user_data)/2).'10')).'00';
                                $frame = '68'.$len.$len.'68'.$user_data.$CS.'16';
                                $server->send($fd, hexToString($frame));
                                addNote('note','info',$fd.':'.$logicalAddress,['address'=>$logicalAddress,'type'=>'daily_freeze request','data'=>$frame]);
                            }
                        }
                    }
                }else{
                    continue;
                }
            }else{
                continue;
            }
        }
    }, $server);
});

$server->addProcess($process);

//监听连接关闭事件
$server->on('Close', function ($server, $fd) {
    $redis = redisCli();
    if($redis->hGetAll($fd)){
        addNote('note','close',$fd.':'.$redis->HGet($fd,'logical_address'),['address'=>$redis->HGet($fd,'logical_address'),'type'=>'close','data'=>$server]);
        echo "Client: Close".$fd."\n";
        $send_redis_data = [];
        $send_redis_data['cmd'] = 'except';
        $send_redis_data['logical_address'] = $redis->HGet($fd,'logical_address');
        $send_redis_data['date']['I'] = date('i');
        $send_redis_data['date']['H'] = date('H');
        $send_redis_data['date']['D'] = date('d');
        $send_redis_data['date']['M'] = date('m');
        $send_redis_data['date']['Y'] = date('y');
        $redis->RPush('upload',json_encode($send_redis_data));
        $redis->del($redis->HGet($fd,'logical_address'));
        $redis->del($fd);
    }else{
        addNote('note','close',$fd,['type'=>'close','data'=>$server]);
        echo "Client: Close".$fd."\n";
    }
});

$server->on('Start',function ($server){
    addNote('note','tcp_onstart',0,['type'=>'tcp_onstart']);
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
    /*$arr = str_split($string,2);
    $string = '';
    foreach ($arr as $v){
        $string .= chr($v);
    }
    return $string;*/
}

/**
 * 计算16进制算术和
 * @author lijie
 * @date_time 2021/01/10
 * @param $string
 * @return string
 */
function hexSum($string)
{
    $arr = str_split($string,2);
    $sum = 0;
    foreach ($arr as $v){
        $sum += hexdec($v);
    }
    $sum = substr(dechex($sum),-2);
    return $sum;
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
 * 16进制高位在前，低位在后
 * @author lijie
 * @date_time 2021/04/14
 * @param $hexdata
 * @return string
 */
function positionReversal($hexdata)
{
    $arr = str_split($hexdata,2);
    $arr = array_reverse($arr);
    $hexdata = '';
    foreach ($arr as $v){
        $hexdata .= $v;
    }
    return $hexdata;
}

/**
 * 根据测量点获取pn
 * @author lijie
 * @date_time 2021/04/16
 * @param $measuringPoint
 * @return int|string
 */
function getPn($measuringPoint)
{
    if(empty($measuringPoint))
        return 0;
    $measuringPoint = getInt($measuringPoint);
    $remainder = $measuringPoint%8;
    $DA1 = '';
    switch ($remainder){
        case 0:
            $DA1 = dechex(bindec('10000000'));
            break;
        case 1:
            $DA1 = dechex(bindec('00000001'));
            break;
        case 2:
            $DA1 = dechex(bindec('00000010'));
            break;
        case 3:
            $DA1 = dechex(bindec('00000100'));
            break;
        case 4:
            $DA1 = dechex(bindec('00001000'));
            break;
        case 5:
            $DA1 = dechex(bindec('00010000'));
            break;
        case 6:
            $DA1 = dechex(bindec('00100000'));
            break;
        case 7:
            $DA1 = dechex(bindec('01000000'));
            break;
    }
    if(strlen($DA1) == 1){
        $DA1=sprintf("%02d",$DA1);
    }
    $DA2 = dechex(ceil($measuringPoint/8));
    if(strlen($DA2) == 1){
        $DA2= '0'.$DA2;
    }
    return $DA1.$DA2;
}

/**
 * 根据pn获取测量点
 * @author lijie
 * @date_time 2021/04/21
 * @param $pn
 * @return float|int
 */
function getMeasuringPoint($pn)
{
    $DA1 = getString($pn,0,2);
    $DA2 = getString($pn,2,2);
    $Y = hexdec($DA2);
    $X = hexdec($DA1);
    switch ($X){
        case 1:
            $X = 1;
            break;
        case 2:
            $X = 2;
            break;
        case 4:
            $X = 3;
            break;
        case 8:
            $X = 4;
            break;
        case 16:
            $X = 5;
            break;
        case 32:
            $X = 6;
            break;
        case 64:
            $X = 7;
            break;
        case 128:
            $X = 8;
            break;
        default:
            $X = 0;
    };
    return ($Y-1)*8+$X;
}

/**
 * 取整，去掉整数前面的0
 * @author lijie
 * @date_time 2021/04/14
 * @param $measuringPoint
 * @return int
 */
function getInt($measuringPoint)
{
    $arr = str_split($measuringPoint,1);
    foreach ($arr as $k=>$v){
        if($v==0)
            unset($arr[$k]);
        else
            break;
    }
    $arr = array_values($arr);
    $intData = '';
    foreach ($arr as $v){
        $intData .= $v;
    }
    return intval($intData);
}

/**
 * 根据集中器返回值算出电表电量
 * @author lijie
 * @date_time 2021/04/23
 * @param $meter_reading_data
 * @return float
 */
function getDegrees($meter_reading_data)
{
    $meter_reading_data = array_reverse(str_split($meter_reading_data,2));
    $arr = [];
    foreach ($meter_reading_data as $k=>$v){
        $bin = str2bin($v);
        if(strlen($bin) >4){
            switch (8-strlen(str2bin($v))){
                case 0:
                    break;
                case 1:
                    $bin = '0'.$bin;
                    break;
                case 2:
                    $bin = '00'.$bin;
                    break;
                case 3:
                    $bin = '000'.$bin;
            }
            $res = str_split($bin,4);
            $arr[] = bindec($res[0]).bindec($res[1]);
        }else{
            $s = 0;
            $arr[] = $s.bindec($bin);
        }
    }
    return floatval($arr[0].$arr[1].$arr[2].'.'.$arr[3].$arr[4]);
}

/**
 * 根据测量点获取绑定电表时的pn
 * @author lijie
 * @date_time 2021/04/21
 * @param $measuringPoint
 * @return string
 */
function getBandPn($measuringPoint){
    $data = dechex($measuringPoint);
    $len = strlen($data);
    if($len < 4){
        switch (4-$len){
            case 3:
                $data = '000'.$data;
                break;
            case 2:
                $data = '00'.$data;
                break;
            case 1:
                $data = '0'.$data;
                break;
            default:
                $data = $data;
        }
    }
    return positionReversal($data);
}

/**
 * 添加日志
 * @author lijie
 * @date_time 2021/04/27
 * @param string $fileName
 * @param string $noteType
 * @param string $address
 * @param array $data
 */
function addNote($fileName='',$noteType='',$address='',$data=[])
{

    if(is_array($data)){
        $data = json_encode($data,JSON_UNESCAPED_UNICODE);
    }
    $file = $fileName.date('Y').date('m').date('d').'.log';
    if(!is_dir('runtime')){
        mkdir('runtime');
    }
    $info = var_export('['.date('Y-m-d H:i:s',time()).']'.$noteType.' '.$address.' '.$data, true);
    //iconv( "UTF-8", "gb2313" , $info);
    file_put_contents('/home/server/wwby_gateway/runtime/'.$file, trim($info,"'") . PHP_EOL , FILE_APPEND);
}

/**
 * 连接redis
 * @return Redis
 */
function redisCli()
{
    global $start_time;
    global $redis;
    if(time() - $start_time > 600){
        addNote('redis_','info',123);
        try{
            $redis->close();
        }catch (Exception $e){
            addNote('redis_warn','warn');
        }
        $redis = new Redis();
        $host = '117.159.29.130';
        $port = 6379;
        $pass = 'qazWSX123';
        $redis->pconnect($host, $port);
        $redis->auth($pass); //密码验证
        $start_time = time();
    }else{
        try{
            $connect_status = $redis->ping();
            if($connect_status != "+PONG"){
                addNote('redis_','info',456);
                try{
                    $redis->close();
                }catch (Exception $e){
                    addNote('redis_warn','warn');
                }
                $redis = new Redis();
                $host = '117.159.29.130';
                $port = 6379;
                $pass = 'qazWSX123';
                $redis->pconnect($host, $port);
                $redis->auth($pass); //密码验证
                $start_time = time();
            }else{
                addNote('redis_','info',777);
            }
        }catch (Exception $e){
            addNote('redis_','info',678);
            try{
                $redis->close();
            }catch (Exception $e){
                addNote('redis_warn','warn');
            }
            $redis = new Redis();
            $host = '117.159.29.130';
            $port = 6379;
            $pass = 'qazWSX123';
            $redis->pconnect($host, $port);
            $redis->auth($pass); //密码验证
            $start_time = time();
        }
    }
    return $redis;
}

/**
 * 获取帧总长度
 * @param $L
 * @return float|int
 */
function getFrameLen($L)
{
    $len = bindec(substr(decbin(hexdec($L)),0,-2))*2+16;
    return $len;
}

function str_split_unicode($str, $l = 0) {
    if ($l > 0) {
        $ret = array();
        $len = mb_strlen($str, "UTF-8");
        for ($i = 0; $i < $len; $i += $l) {
            $ret[] = mb_substr($str, $i, $l, "UTF-8");
        }
        return $ret;
    }
    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

/**
 * 处理集中器返回帧
 * @author lijie
 * @date_time 2021/06/21
 * @param $data_frame
 * @param $server
 * @param $fd
 * @param $redis
 */
function handle_frame($data_frame,$server,$fd,$redis)
{
    $L = getString($data_frame,2,2);
    $len = getFrameLen($L);
    $data = getString($data_frame,0,$len);
    if(getString($data,0,2)=='68' && getString($data,10,2)=='68'){
        //获取电表逻辑地址
        $logicalAddress = getString($data,16,2).getString($data,14,2).getString($data,20,2).getString($data,18,2).getString($data,22,2);
        $reversalLogicalAddress = getString($data,14,10);
        echo 'Address------'.$reversalLogicalAddress . "\n";
        //集中器登录
        if(getString($data,24,2) == '02' && getString($data,28,8) == '00000100'){
            addNote('note','info',$fd.':'.$logicalAddress,['address'=>$logicalAddress,'type'=>'login request','data'=>$data]);
            //发送确认登录下行报文给集中器
            $user_data = '0B'.getString($data,14,10).'006100000100';//确认登录下行报文用户数据区
            $CS = hexSum($user_data);  //用户数据区8位位组算术和
            $frame = '683200320068'.$user_data.$CS.'16';  //链路测试确认帧
            $server->send($fd,hexToString($frame)); //主站下发链路测试确认帧
            echo 'Login------SUCCESS,fid:'.$fd."\n";
            addNote('note','info',$fd.':'.$logicalAddress,['address'=>$logicalAddress,'type'=>'login response','data'=>$frame]);
            //绑定集中器fd
            $redis->hMSet($fd, array(
                'logical_address'            => $logicalAddress,
                'reversal_logical_address'=>$reversalLogicalAddress,
                'last_heart_time'          => time()
            ));
            $bool = $redis->hGetAll($logicalAddress);
            if($bool){
                $old_fd = $redis->HGet($logicalAddress,'fd');
                addNote('fd_record','fd',$fd.'---'.$old_fd);
                if($redis->hGetAll($old_fd)){
                    if($old_fd != $fd && $redis->HGet($old_fd,'logical_address') == $logicalAddress){
                        $redis->del($old_fd);
                    }
                }
                $redis->hMset($logicalAddress,array(
                    'fd'=>$fd,
                    'last_heart_time'          => time()
                ));
            }else{
                $redis->hMset($logicalAddress,array(
                    'fd'=>$fd,
                    'last_heart_time'          => time()
                ));
            }
        }else if(getString($data,24,2) == '02' && getString($data,28,8) == '00000400'){//集中器与主站心跳
            addNote('note','info',$fd.':'.$logicalAddress,['address'=>$logicalAddress,'type'=>'heart request','data'=>$data]);
            $Tp = getString($data,36,12);
            echo 'heartbeat------'.$Tp."\n";
            //发送心跳确认下行报文给集中器
            $user_data = '0B'.getString($data,14,10).'006000000100';//确认心跳下行报文用户数据区
            $CS = hexSum($user_data);  //用户数据区8位位组算术和
            $frame = '683200320068'.$user_data.$CS.'16';  //链路测试心跳确认帧
            $server->send($fd,hexToString($frame)); //主站下发链路测试心跳确认帧
            addNote('note','info',$fd.':'.$logicalAddress,['address'=>$logicalAddress,'type'=>'heart response','data'=>$frame]);
        }else if(getString($data,24,2) == '0d'){  //日冻结返回
            addNote('note','info',$fd.':'.$logicalAddress,['address'=>$logicalAddress,'type'=>'daily_freeze response','data'=>$data]);
            $pn = getString($data,28,4);
            $meter_reading_time = getString($data,36,6);
            $hardware_meter_reading_time = getString($data,42,10);
            $meter_reading_data = getString($data,54,10);
            if($meter_reading_data != 'eeeeeeeeee'){
                $degrees = getDegrees($meter_reading_data);
                //$degrees = 40;
                $measuring_point = getMeasuringPoint($pn);
                $send_redis_data = [];
                $send_redis_data['cmd'] = 'meter_reading';
                $send_redis_data['type'] = 'day_reading';
                $send_redis_data['logical_address'] = $logicalAddress;
                $send_redis_data['measuring_point'] = $measuring_point;
                $send_redis_data['degrees'] = $degrees;
                $send_redis_data['date']['I'] = getString($hardware_meter_reading_time,0,2);
                $send_redis_data['date']['H'] = getString($hardware_meter_reading_time,2,2);
                $send_redis_data['date']['D'] = getString($hardware_meter_reading_time,4,2);
                $send_redis_data['date']['M'] = getString($hardware_meter_reading_time,6,2);
                $send_redis_data['date']['Y'] = getString($hardware_meter_reading_time,8,2);
                $redis->RPush('upload',json_encode($send_redis_data));
            }
        }else if(getString($data,24,2) == '10'){ //开关闸返回
            $send_redis_data = [];
            if(getString($data,58,2) == '9c'){
                addNote('note','info',$fd.':'.$logicalAddress,['address'=>$logicalAddress,'type'=>'close-open response','data'=>$data]);
                $send_redis_data['cmd'] = 'switch';
                $send_redis_data['energy_meter_address'] = positionReversal(getString($data,44,12));
                $send_redis_data['logical_address'] = $logicalAddress;
                $send_redis_data['status'] = true;
            }
            if(getString($data,58,2) == 'dc'){
                addNote('note','failed',$fd.':'.$logicalAddress,['address'=>$logicalAddress,'type'=>'close-open response','data'=>$data]);
                $send_redis_data['cmd'] = 'switch';
                $send_redis_data['energy_meter_address'] = positionReversal(getString($data,44,12));
                $send_redis_data['logical_address'] = $logicalAddress;
                $send_redis_data['status'] = false;
            }
            if($send_redis_data){
                $redis->RPush('upload',json_encode($send_redis_data));
            }
        }else if(getString($data,24,2) == '00' && getString($data,26,2) == '6c'){
            addNote('note','info',$fd.':'.$logicalAddress,['address'=>$logicalAddress,'type'=>'bind response','data'=>$data]);
        }else{
            addNote('note','warn',$fd.':'.$logicalAddress,['address'=>$logicalAddress,'type'=>'other request','data'=>$data]);
        }
        //绑定集中器fd
        $redis->hMSet($fd, array(
            'logical_address'            => $logicalAddress,
            'reversal_logical_address'=>$reversalLogicalAddress,
            'last_heart_time'          => time()
        ));
        $redis->hMset($logicalAddress,array(
            'fd'=>$fd,
            'last_heart_time'          => time()
        ));
        echo "\n";
    }else{
        $server->close($fd, true);
    }
    $original_data = $data_frame;
    $data_frame = substr($data_frame, $len);
    if(!empty($data_frame) && getString($data_frame,0,2)=='68' && getString($data_frame,10,2)=='68'){
        addNote('note','info','more_frame',['type'=>'more_frame','data'=>$original_data]);
        handle_frame($data_frame,$server,$fd,$redis);
    }
}