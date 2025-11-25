<?php
//echo (date('y',time()));

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

function getString($string='',$begin=0,$length=1)
{
    return substr($string,$begin,$length);
}
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

//echo getBandPn(1999);

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

function str2bin($hexdata)
{
    return decbin(hexdec($hexdata));
}

echo getDegrees('b64b3333');
//echo date('H');
//echo json_decode("{\"cmd\":\"switch\",\"logical_address\":\"8111232a00\",\"type\":\"close\",\"energy_meter_address\":\"008187028063\",\"measuring_point\":2,\"electric_type\":2}");