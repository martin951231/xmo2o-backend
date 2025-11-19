<?php

namespace app\community\model\service\Park;

class ScreenHelperService
{

    // 语音内容对应十六进制
    public $voiceHex = [
        '欢迎光临' => '01',
        '一路平安' => '02',
        '请等待人工确认' => '03',
        '余额不足' => '04',
        '此车已进场' => '05',
        '此车已出场' => '06',
        '此车无权限' => '07',
        '此车已过期' => '08',
        '此车黑名单' => '09',
        '车位已满' => '0A',
        '请缴费' => '0B',
        '有效期' => '0C',
        '音量' => '0D',
        '此卡' => '0E',
        '已过期' => '0F',
        '无效' => '10',
        '有效' => '11',
        '有效期' => '12',
        '此车' => '13',
        '请入场停车' => '14',
        '黑名单' => '15',
        '记录' => '16',
        '下载' => '17',
        '成功' => '18',
        '失败' => '19',
        '已进场' => '1A',
        '已出场' => '1B',
        '无权限' => '1C',
        '删除' => '1D',
        '请等待' => '1E',
        '人工确认' => '1F',
        '亲情车' => '20',
        '临时车' => '21',
        '月租车' => '22',
        '储值车' => '23',
        '免费车' => '24',
        '未派车' => '25',
        '谢谢' => '26',
        '欢迎回家' => '27',
        '请通行' => '28',
        '未授权' => '29',
        '已挂失' => '2A',
        '禁止通行' => '2B',
        '扣款' => '2C',
        '金额' => '2D',
        '停车' => '2E',
        '小时' => '2F',
        '节日' => '3A',
        '快乐' => '3B',
        '新年' => '3C',
        '剩余' => '3D',
        '读卡' => '3E',
        '车卡不符' => '3F',
        '健康' => '5B',
        '入场' => '5C',
        '欢迎' => '5D',
        '泊车' => '5E',
        '一路顺风' => '5F',
        '下次' => '60',
        '光临' => '61',
        '再次' => '62',
        '出入平安' => '63',
        '该时段' => '64',
        '不允许进入' => '65',
        '内部车场' => '66',
        '正在计费' => '67',
        '直接放行' => '68',
        '管控车辆' => '69',
        '缴费' => '6A',
        '中心' => '6B',
        '岗亭' => '6C',
        '超过' => '6D',
        '离场' => '6E',
        '限时' => '6F',
        '重新' => '70',
        '快递' => '71',
        '管理处' => '72',
        '及时' => '73',
        '缴纳' => '74',
        '管理费' => '75',
        '水电费' => '76',
        '春节' => '77',
        '中秋节' => '78',
        '国庆节' => '79',
        '劳动节' => '7A',
        '圣诞节' => '7B',
        '元旦' => '7C',
        '微信' => '7D',
        '支付' => '7E',
        '时租车' => '7F00',
        '月临车' => '7F01',
        '非法开闸' => '7F02',
        '交费处' => '7F03',
        '未解锁' => '7F04',
        '匹配' => '7F05',
        '场内' => '7F06',
        '通道' => '7F07',
        '按临时车计费' => '7F08',
        'good' => '7F09',
        'welcome' => '7F0A',
        '贵宾车' => '7F0B',
        '内部车' => '7F0C',
        '业主车' => '7F0D',
        '员工车' => '7F0E',
        'zero' => '7F0F',
        'one' => '7F10',
        'two' => '7F11',
        'three' => '7F12',
        'four' => '7F13',
        'five' => '7F14',
        'six' => '7F15',
        'seven' => '7F16',
        'eight' => '7F17',
        'nine' => '7F18',
        '无牌车' => '7F19',
        '民航' => '7F1A',
        '扫码' => '7F1B',
        '零' => '30',
        '一' => '31',
        '二' => '32',
        '三' => '33',
        '四' => '34',
        '五' => '35',
        '六' => '36',
        '七' => '37',
        '八' => '38',
        '九' => '39',
        'A' => '41', 'B' => '42', 'C' => '43', 'D' => '44', 'E' => '45', 'F' => '46', 'G' => '47', 'H' => '48', 'I' => '49', 'J' => '4A', 'K' => '4B', 'L' => '4C', 'M' => '4D', 'N' => '4E', 'O' => '4F', 'P' => '50', 'Q' => '51', 'R' => '52', 'S' => '53', 'T' => '54', 'U' => '55', 'V' => '56', 'W' => '57', 'X' => '58', 'Y' => '59', 'Z' => '5A',
        '十' => '', '百' => '',  '千' => '',  '万' => '', '年' => '', '月' => '',  '日' => '',  '天' => '',  '条' => '',  '京' => '',  '黑' => '',  '吉' => '',  '辽' => '', '苏' => '', '鲁' => '', '皖' => '', '冀' => '', '豫' => '', '鄂' => '', '湘' => '', '赣' => '', '陕' => '', '晋' => '', '川' => '', '青' => '', '琼' => '', '粤' => '', '浙' => '', '闽' => '', '甘' => '', '云' => '', '台' => '', '贵' => '', '渝' => '', '沪' => '', '津' => '',  '新' => '', '桂' => '', '蒙' => '', '宁' => '', '军' => '', '学' => '', '警' => '', '点' => '', '元' => '', '您' => '', '好' => '', '分' => '', '空' => '',  '海' => '',  '北' => '',  '沈' => '',  '兰' => '',  '济' => '', '南' => '',  '广' => '', '成' => '', '甲' => '', '乙' => '',  '丙' => '', '午' => '', '未' => '', '申' => '', '庚' => '', '己' => '', '辛' => '', '壬' => '', '寅' => '', '戍' => '', '祝' => '', '藏' => '', '港' => '', '澳' => '', '领' => '', '使' => '', '请' => '', '到' => '', '有' => '', '在' => '', '宝' => '', '次' => '', '秒' => '',
    ];

    // base64 编码表
    private $base64Arr = [
        '0' => 'A', '1' => 'B', '2' => 'C', '3' => 'D', '4' => 'E', '5' => 'F', '6' => 'G', '7' => 'H', '8' => 'I', '9' => 'J',
        '10' => 'K', '11' => 'L', '12' => 'M', '13' => 'N', '14' => 'O', '15' => 'P', '16' => 'Q', '17' => 'R', '18' => 'S', '19' => 'T',
        '20' => 'U', '21' => 'V', '22' => 'W', '23' => 'X', '24' => 'Y', '25' => 'Z', '26' => 'a', '27' => 'b', '28' => 'c', '29' => 'd',
        '30' => 'e', '31' => 'f', '32' => 'g', '33' => 'h', '34' => 'i', '35' => 'j', '36' => 'k', '37' => 'l', '38' => 'm', '39' => 'n',
        '40' => 'o', '41' => 'p', '42' => 'q', '43' => 'r', '44' => 's', '45' => 't', '46' => 'u', '47' => 'v', '48' => 'w', '49' => 'x',
        '50' => 'y', '51' => 'z', '52' => '0', '53' => '1', '54' => '2', '55' => '3', '56' => '4', '57' => '5', '58' => '6', '59' => '7',
        '60' => '8', '61' => '9', '62' => '+', '63' => '/',
    ];

    /**
     * 设置屏显内容.
     */
    public function setScreenData(string $orderType, string $data, int $maxLength, string $txtOder = '01')
    {
        $txtTop = 'AA55'; // 包头
        $txtEnd = 'AF'; // 包尾
        $txtAddr = '64'; // 地址
        $businessType = '00'; // 业务类型

        $length = strlen($data) / 2; // 长度
        $length = $this->numToHex(dechex($length), $maxLength);
        $checkData = $txtOder . $txtAddr . $businessType . $orderType . $length . $data;

        $checkResult = $this->wordStr(strtoupper($checkData)); // 校验
        $screenData = $txtTop . $checkResult . $txtEnd;

        // base64加密
        return $this->translation($screenData);
    }

    /**
     * 设置临显屏显内容.
     * @param string $txt 文本
     * @param int $showRow 第多少行
     * @param int $showSecond 显示时长秒数
     * @param int $showColor 文字颜色
     * @param string $txtOder 流水号
     * @return false|string
     */
    public function showScreenTxt(string $txt, int $showRow, int $showSecond, int $showColor, string $txtOder = '01')
    {
        $txtTop = 'AA55'; // 包头
        $txtEnd = 'AF'; // 包尾
        $txtAddr = '64'; // 地址
        $businessType = '00'; // 业务类型
        $orderType = '27'; // 下发临显内容指令  22 语音

        $dataHead = '0' . $showRow; //第多少行
        $dataHead .= $this->numToHex(dechex($showSecond), 2); //显示时长秒数
        $dataHead .= $this->numToHex(dechex($showColor), 2); //文字颜色
        $dataHead .= '00';

        $strTo16Gbk = $this->strTo16Gbk($txt);
        $data = $dataHead . $strTo16Gbk; // 内容
        $length = strlen($data) / 2; // 长度
        $length = dechex($length);
        $length = $this->numToHex($length, 4);
        $checkData = $txtOder . $txtAddr . $businessType . $orderType . $length . $data;
        $checkData = strtoupper($checkData);

        $checkResult = $this->wordStr($checkData); // 校验
        return $txtTop . $checkResult . $txtEnd;
    }

    /**
     * 64位转码.
     */
    public function translation(string $data = '')
    {
        if (empty($data)) {
            return '';
        }
        if (! is_array($data)) {
            $data = str_replace(' ', '', $data);
            $data = $this->mbStrSplit($data, 2);
        }
        $arrTo2bins = $this->arrTo2bin($data, true);
        $dataString = implode('', $arrTo2bins);
        $mbStrSplit = $this->mbStrSplit($dataString, 6, true, true);
        $dataStrings = implode('', $mbStrSplit);
        $dataStrings .= '=';
        return $dataStrings;
    }

    /**
     * @param array $txt 数组
     * @param string $txtOder 流水号
     * @return false|string
     */
    public function showVoice(array $voiceArr, string $txtOder = '01')
    {
        $txtTop = 'AA55'; // 包头
        $txtEnd = 'AF'; // 包尾
        $txtAddr = '64'; // 地址
        $businessType = '00'; // 业务类型
        $orderType = '22'; // 下发临显内容指令  22 语音
        $data = $this->voice2hex($voiceArr);
        $length = strlen($data) / 2; // 长度
        $length = dechex((int) $length);
        $length = $this->numToHex($length, 4);
        $checkData = $txtOder . $txtAddr . $businessType . $orderType . $length . $data;
        $checkData = strtoupper($checkData);
        //  dump($checkData);

        $checkResult = $this->wordStr($checkData); // 校验
        return $txtTop . $checkResult . $txtEnd;
    }

    public static function numToHex(string $num, int $len, bool $end = false)
    {
        if (! $len) {
            return '';
        }
        if (strlen($num) < $len) {
            $add = str_repeat('0', $len - strlen($num));
            if ($end) {
                $num = $num . $add;
            } else {
                $num = $add . $num;
            }
        }
        return $num;
    }

    public function strTo16Gbk($word)
    {
        $gbkStr = mb_convert_encoding($word, 'GBK', 'UTF-8');
        return $this->str2hex($gbkStr);
    }

    private function mbStrSplit($string, $len = 1, $isTo10 = false, $checkBase64 = false)
    {
        $start = 0;
        $strLen = mb_strlen($string);
        $array = [];
        while ($strLen) {
            $str = mb_substr($string, $start, $len, 'utf8');
            if ($isTo10) {
                // 是否进行2进制转10进制
                $str = $this->numToHex($str, 6, true);
                $str = bindec($str);
                if ($checkBase64 && isset($this->base64Arr[$str])) {
                    $str = $this->base64Arr[$str];
                }
            }
            $array[] = $str;
            $string = mb_substr($string, $len, $strLen, 'utf8');
            $strLen = mb_strlen($string);
        }
        return $array;
    }

    private function arrTo2bin($arr, bool $isNumToHex)
    {
        $arrTo2bins = [];
        foreach ($arr as $item) {
            $str = $this->str2bin($item);
            if ($isNumToHex) {
                $str = $this->numToHex($str, 8);
            }
            $arrTo2bins[] = $str;
        }
        return $arrTo2bins;
    }

    private function str2bin($hexData)
    {
        return decbin(hexdec($hexData));
    }

    private function voice2hex($voiceArr)
    {
        $voiceData = '';
        // 判断是欢迎还是禁止进入
        $cameraArr = explode('+', $voiceArr['voiceSetting']['camera']);
        foreach ($cameraArr as $item) {
            switch ($item) {
                case '问候语':
                    if ($voiceArr['voiceExtra'] && $voiceArr['voiceExtra']['errorMsg']) {
                        $txt = $voiceArr['voiceSetting']['noEntryWelcomeWords'];
                    } else {
                        $txt = $voiceArr['voiceSetting']['welcomeWords'];
                    }
                    switch ($txt) {
                        case "您好":
                            $voiceData.=$this->voiceHex['您好'];
                            break;
                        case "欢迎光临":
                            $voiceData.=$this->voiceHex['欢迎光临'];
                            break;
                        case "欢迎回家":
                            $voiceData.=$this->voiceHex['欢迎回家'];
                            break;
                        case "禁止通行":
                            $voiceData.=$this->voiceHex['禁止通行'];
                            break;
                    }
                    break;
                case '车辆类型':
                    $txt = $voiceArr['carData']['carChargeType'];
                    $voiceData .= $this->voiceHex[$txt] ?? '';
                    break;
                case '车牌号':
                    $txt = $voiceArr['carData']['carNumber'];
                    for ($i = 0,$length = mb_strlen($txt); $i < $length; ++$i) {
                        $tmp = mb_substr($txt, $i, 1);
                        $voiceData .= isset($this->voiceHex[$tmp]) && $this->voiceHex[$tmp] ? $this->voiceHex[$tmp] : $this->strTo16Gbk($tmp);
                    }
                    break;
                case '结束语':
                    if ($voiceArr['voiceExtra'] && $voiceArr['voiceExtra']['errorMsg']) {
                        $txt = $voiceArr['voiceSetting']['noEntryFinalWords'];
                    } else {
                        $txt = $voiceArr['voiceSetting']['finalWords'];
                    }

                    switch ($txt) {
                        case "一路顺风":
                            $voiceData.=$this->voiceHex['一路顺风'];
                            break;
                        case "一路平安":
                            $voiceData.=$this->voiceHex['一路平安'];
                            break;
                        case "欢迎下次光临":
                            $voiceData.=$this->voiceHex['欢迎'] . $this->voiceHex['下次'] . $this->voiceHex['光临'];
                            break;
                        case "禁止通行":
                            $voiceData.=$this->voiceHex['禁止通行'];
                            break;
                    }
                    break;
                case '有效期':
                    $txt = $this->dateToChinese($voiceArr['carData']['monthEndDay']);

                    $voiceData .= $this->voiceHex['有效期'];

                    for ($i = 0,$length = mb_strlen($txt); $i < $length; ++$i) {
                        $tmp = mb_substr($txt, $i, 1);
                        $voiceData .= isset($this->voiceHex[$tmp]) && $this->voiceHex[$tmp] ? $this->voiceHex[$tmp] : $this->strTo16Gbk($tmp);
                    }
                    break;
                case '剩余有效期天数':
                    if ($voiceArr['carData'] && $voiceArr['carData']['monthEndDay']) {
                        $txt = $this->timeToChinese((string) (strtotime($voiceArr['carData']['monthEndDay']) - strtotime(date('Y-m-d'))));

                        $voiceData .= $this->voiceHex['剩余'];

                        for ($i = 0,$length = mb_strlen($txt); $i < $length; ++$i) {
                            $tmp = mb_substr($txt, $i, 1);
                            $voiceData .= (isset($this->voiceHex[$tmp]) && $this->voiceHex[$tmp]) ? $this->voiceHex[$tmp] : $this->strTo16Gbk($tmp);
                        }
                    }
                    break;
                case '收费金额':
                    if ($voiceArr['carData'] && $voiceArr['carData']['fee']) {
                        $txt = $this->moneyToChinese((string) $voiceArr['carData']['fee']);

                        $voiceData .= $this->voiceHex['金额'];

                        for ($i = 0,$length = mb_strlen($txt); $i < $length; ++$i) {
                            $tmp = mb_substr($txt, $i, 1);
                            $voiceData .= (isset($this->voiceHex[$tmp]) && $this->voiceHex[$tmp]) ? $this->voiceHex[$tmp] : $this->strTo16Gbk($tmp);
                        }

                        if ($voiceArr['carData']['fee_tip']) {
                            $voiceData .= $this->voiceHex['余额不足'];
                            $voiceData .= $this->voiceHex['请缴费'];
                        }
                    }
                    break;
                case '停车时间':
                    if ($voiceArr['carData'] && $voiceArr['carData']['stayTime']) {
                        $txt = $this->timeToChinese((string) $voiceArr['carData']['stayTime']);

                        $voiceData .= $this->voiceHex['停车'];
                        $voiceData .= $txt;
                    }
                    break;
            }
        }

        return $voiceData;
    }

    /**
     * date日期转中文.
     */
    public function dateToChinese(string $date)
    {
        $chineseDate = '';
        if (! empty($date)) {
            // 把数字转换成中文
            $chineseArr = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
            // 十位数对应的中文
            $chineseTenArr = ['', '十', '二十', '三十'];
            // 获取年份
            $year = date('Y', strtotime($date));
            // 获取月份
            $month = date('m', strtotime($date));
            // 获取日期
            $day = date('d', strtotime($date));
            // 转换为数组
            $yearArr = str_split($year);
            foreach ($yearArr as $value) {
                // 将年份中的数字替换成对应的中文
                $chineseDate .= $chineseArr[$value];
            }
            $chineseDate .= '年';
            $monthArr = str_split($month);
            // 月，日去除零
            if ($monthArr[1] != 0) {
                // 将月份中的数字替换成中文
                $chineseDate .= $chineseTenArr[$monthArr[0]] . $chineseArr[$monthArr[1]] . '月';
            } else {
                $chineseDate .= $chineseTenArr[$monthArr[0]] . '月';
            }
            $dayArr = str_split($day);
            if ($dayArr[1] != 0) {
                $chineseDate .= $chineseTenArr[$dayArr[0]] . $chineseArr[$dayArr[1]] . '日';
            } else {
                $chineseDate .= $chineseTenArr[$dayArr[0]] . '日';
            }
        }
        return $chineseDate;
    }

    /**
     * 金额转中文.
     */
    public function moneyToChinese(string $money)
    {
        $chineseMoney = '';
        if (! empty($money)) {
            $chineseArr = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];

            $moneyArr = explode('.', $money);
            $int = $moneyArr[0];
            $float = $moneyArr[1] ?? 0;

            $intArr = str_split($int);
            if (count($intArr) == 4) {
                $chineseMoney .= $chineseArr[$intArr[0]] . '千' . $chineseArr[$intArr[1]] . '百' . $chineseArr[$intArr[2]] . '十' . $chineseArr[$intArr[3]];
            } elseif (count($intArr) == 3) {
                $chineseMoney .= $chineseArr[$intArr[0]] . '百' . $chineseArr[$intArr[1]] . '十' . $chineseArr[$intArr[2]];
            } elseif (count($intArr) == 2) {
                $chineseMoney .= $chineseArr[$intArr[0]] . '十' . $chineseArr[$intArr[1]];
            } elseif (count($intArr) == 1) {
                $chineseMoney .= $chineseArr[$intArr[0]];
            }

            if ($float) {
                $chineseMoney .= '点';
                $floatArr = str_split($float);
                if (count($floatArr) == 2) {
                    $chineseMoney .= $chineseArr[$floatArr[0]] . $chineseArr[$floatArr[1]];
                } elseif (count($floatArr) == 1) {
                    $chineseMoney .= $chineseArr[$floatArr[0]];
                }
            }

            $chineseMoney .= '元';
        }
        return $chineseMoney;
    }

    /**
     * 天时分秒转中文.
     */
    private function timeToChinese(string $date)
    {
        $chineseDate = '';
        if (! empty($date)) {
            // 把数字转换成中文
            $chineseArr = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
            // 十位数对应的中文
            $chineseTenArr = ['', '十', '二十', '三十', '四十', '五十'];
            // 获取天数
            $day = intval($date / 86400);
            // 获取小时
            $hour = intval(($date - 86400 * $day) / 3600);
            // 获取分钟
            $minute = ceil(($date - 86400 * $day - $hour * 3600) / 60);

            if ($day > 0) {
                // 转换为数组
                $yearArr = str_split((string) $day);
                if (count($yearArr) == 1) {
                    $chineseDate .= $chineseArr[$yearArr[0]] . '天';
                } else {
                    if ($yearArr[1] != 0) {
                        // 将月份中的数字替换成中文
                        $chineseDate .= $this->getVoiceHex($chineseTenArr[$yearArr[0]]) . $this->getVoiceHex($chineseArr[$yearArr[1]]) . '天';
                    } else {
                        $chineseDate .= $this->getVoiceHex($chineseTenArr[$yearArr[0]]) . '天';
                    }
                }
            }
            if ($hour > 0) {
                $monthArr = str_split((string) $hour);
                if (count($monthArr) == 1) {
                    $chineseDate .= $monthArr[0] != 0 ? $this->getVoiceHex($chineseArr[$monthArr[0]]) . $this->getVoiceHex('小时') : '';
                } else {
                    // 小时、分钟去除零
                    if ($monthArr[1] != 0) {
                        // 将月份中的数字替换成中文
                        $chineseDate .= $this->getVoiceHex($chineseTenArr[$monthArr[0]]) . $this->getVoiceHex($chineseArr[$monthArr[1]]) . $this->getVoiceHex('小时');
                    } else {
                        $chineseDate .= $this->getVoiceHex($chineseTenArr[$monthArr[0]]) . $this->getVoiceHex('小时');
                    }
                }
            }

            $dayArr = str_split((string) $minute);
            if (count($dayArr) == 1) {
                $chineseDate .= $dayArr[0] != 0 ? $this->getVoiceHex($chineseArr[$dayArr[0]]) . $this->getVoiceHex('分钟') : '';
            } else {
                if ($dayArr[1] != 0) {
                    $chineseDate .= $this->getVoiceHex($chineseTenArr[$dayArr[0]]) . $this->getVoiceHex($chineseArr[$dayArr[1]]) . $this->getVoiceHex('分钟');
                } else {
                    $chineseDate .= $this->getVoiceHex($chineseTenArr[$dayArr[0]]) . $this->getVoiceHex('分钟');
                }
            }
        }
        return $chineseDate;
    }

    private function getVoiceHex(string $str)
    {
        return (isset($this->voiceHex[$str]) && $this->voiceHex[$str]) ? $this->voiceHex[$str] : $this->strTo16Gbk($str);
    }

    private function str2hex($str)
    {
        $hex = '';
        for ($i = 0,$length = strlen($str); $i < $length; ++$i) {
            $hexStr = dechex(ord($str[$i]));
            $hex .= $hexStr;
        }
        return $hex;
    }

    /**
     * @param string $wordStr "AA55 01 64 00 11 00 0B 01 B5 DA D2 BB D0 D0 B9 E3 B8 E6 X1 X2 AF" 传参去除 头AA55和尾AF和校验X1X2  即 '01 64 00 11 00 0B 01 B5 DA D2 BB D0 D0 B9 E3 B8 E6'
     */
    private function wordStr(string $wordStr)
    {
        $wordStr = str_replace(' ', '', $wordStr);
        $wordCmd = $wordStr;
        $wordStr .= '0000';
        $packStr = pack('H*', $wordStr); // 把数据装入一个二进制字符串[十六进制字符串，高位在前]
        $crc166Str = $this->crc166($packStr); // 进行CRC16校验
        $unpackStr = unpack('H*', $crc166Str); // 从二进制字符串对数据进行解包[十六进制字符串，高位在前];
        return $wordCmd . strtoupper($unpackStr[1]);
    }

    private function crc166($string, $length = 0)
    {
        $auchCRCHi = [0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81,
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
            0x40, ];
        $auchCRCLo = [0x00, 0xC0, 0xC1, 0x01, 0xC3, 0x03, 0x02, 0xC2, 0xC6, 0x06, 0x07, 0xC7, 0x05, 0xC5, 0xC4,
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
            0x40, ];
        $length = ($length <= 0 ? strlen($string) : $length);
        $uchCRCHi = 0xFF;
        $uchCRCLo = 0xFF;
        $uIndex = 0;
        for ($i = 0; $i < $length; ++$i) {
            $uIndex = $uchCRCLo ^ ord(substr($string, $i, 1));
            $uchCRCLo = $uchCRCHi ^ $auchCRCHi[$uIndex];
            $uchCRCHi = $auchCRCLo[$uIndex];
        }
        return chr($uchCRCHi) . chr($uchCRCLo);
    }

}