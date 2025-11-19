<?php
/**
 * [dealTree 根据数组中的某一字段处理成树状结构]
 * @Author   JJC
 * @DateTime 2020-06-16T14:04:03+0800
 * @param    array                  $list [需要处理的数组]
 * @param    string                 $key  [数组中的某个值]
 * @return   array                        [处理好的数组]
 */
function dealTree($list,$key=''){
	$return = [];
	foreach ($list as $k => $v) {
		$return[$v[$key]][]=$v;
	}
	return $return;
}

// 这是系统自动生成的公共文件
function getfirstchar($s){   //获取单个汉字拼音首字母。注意:此处不要纠结。汉字拼音是没有以U和V开头的
    $fchar = ord($s{0});
    if($fchar >= ord("A") and $fchar <= ord("z") )return strtoupper($s{0});
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if($asc >= -20319 and $asc <= -20284) return "A";
    if($asc >= -20283 and $asc <= -19776) return "B";
    if($asc >= -19775 and $asc <= -19219) return "C";
    if($asc >= -19218 and $asc <= -18711) return "D";
    if($asc >= -18710 and $asc <= -18527) return "E";
    if($asc >= -18526 and $asc <= -18240) return "F";
    if($asc >= -18239 and $asc <= -17923) return "G";
    if($asc >= -17922 and $asc <= -17418) return "H";
    if($asc >= -17922 and $asc <= -17418) return "I";
    if($asc >= -17417 and $asc <= -16475) return "J";
    if($asc >= -16474 and $asc <= -16213) return "K";
    if($asc >= -16212 and $asc <= -15641) return "L";
    if($asc >= -15640 and $asc <= -15166) return "M";
    if($asc >= -15165 and $asc <= -14923) return "N";
    if($asc >= -14922 and $asc <= -14915) return "O";
    if($asc >= -14914 and $asc <= -14631) return "P";
    if($asc >= -14630 and $asc <= -14150) return "Q";
    if($asc >= -14149 and $asc <= -14091) return "R";
    if($asc >= -14090 and $asc <= -13319) return "S";
    if($asc >= -13318 and $asc <= -12839) return "T";
    if($asc >= -12838 and $asc <= -12557) return "W";
    if($asc >= -12556 and $asc <= -11848) return "X";
    if($asc >= -11847 and $asc <= -11056) return "Y";
    if($asc >= -11055 and $asc <= -10247) return "Z";
    return NULL;
}

function pinyin_long($zh){  //获取整条字符串汉字拼音首字母
    $ret = "";
    $s1 = iconv("UTF-8","GBK", $zh);
    $s2 = iconv("GBK","UTF-8", $s1);
    if($s2 == $zh){$zh = $s1;}
    for($i = 0; $i < strlen($zh); $i++){
        $s1 = substr($zh,$i,1);
        $p = ord($s1);
        if($p > 160){
            $s2 = substr($zh,$i++,2);
            $ret .= getfirstchar($s2);
        }else{
            $ret .= $s1;
        }
    }
    return $ret;
}

/**
 * PHP计算两个时间段是否有交集（边界重叠不算）
 *
 * @param string $beginTime1 开始时间1
 * @param string $endTime1 结束时间1
 * @param string $beginTime2 开始时间2
 * @param string $endTime2 结束时间2
 * @return bool
 */
function is_time_cross($beginTime1 = '', $endTime1 = '', $beginTime2 = '', $endTime2 = '') {
    $status = $beginTime2 - $beginTime1;
    if ($status > 0) {
      $status2 = $beginTime2 - $endTime1;
      if ($status2 >= 0) {
        return false;
      } else {
        return true;
      }
    } else {
      $status2 = $endTime2 - $beginTime1;
      if ($status2 > 0) {
        return true;
      } else {
        return false;
      }
    }
}
