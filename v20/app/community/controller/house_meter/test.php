<?php
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

function getFrame($frame)
{
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

//echo getFrame('7f123456787f3456747d');

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

//echo NumToHex(14400,8);
$a = [1,-1,0,0,0,0,0,0,0,0,0,0,0];
//echo json_encode($a);
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
        $DA2=sprintf("%02d",$DA2);
    }
    echo $DA2;exit;
    return $DA1.$DA2;
}
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
echo getPn('120');
echo $DA2 = dechex(ceil(120/8));