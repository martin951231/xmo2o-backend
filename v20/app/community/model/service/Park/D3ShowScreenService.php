<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/4/6 13:27
 */

namespace app\community\model\service\Park;


class D3ShowScreenService{
    public $data = '';
    // base64 编码表
    public $base64Arr = [
        '0' => 'A', '1' => 'B', '2' => 'C', '3' => 'D', '4' => 'E', '5' => 'F', '6' => 'G', '7' => 'H', '8' => 'I', '9' => 'J',
        '10' => 'K','11' => 'L', '12' => 'M', '13' => 'N', '14' => 'O', '15' => 'P', '16' => 'Q', '17' => 'R', '18' => 'S', '19' => 'T',
        '20' => 'U', '21' => 'V', '22' => 'W', '23' => 'X', '24' => 'Y', '25' => 'Z', '26' => 'a', '27' => 'b', '28' => 'c', '29' => 'd',
        '30' => 'e', '31' => 'f', '32' => 'g', '33' => 'h', '34' => 'i', '35' => 'j', '36' => 'k', '37' => 'l', '38' => 'm', '39' => 'n',
        '40' => 'o', '41' => 'p', '42' => 'q', '43' => 'r', '44' => 's', '45' => 't', '46' => 'u', '47' => 'v', '48' => 'w', '49' => 'x',
        '50' => 'y', '51' => 'z', '52' => '0', '53' => '1', '54' => '2', '55' => '3', '56' => '4', '57' => '5', '58' => '6', '59' => '7',
        '60' => '8', '61' => '9', '62' => '+', '63' => '/',
    ];

    // 语音内容
    public $voiceArr = [
        // 'passage_type' 1入场，0出场
        // 'car_type' 1临时车，2月租车
        '0'=>[
            'key'=>1,
            'passage_type'=>[1],
            'car_type'=>[1,2],
            'txt'=>'{车牌号}欢迎光临',
            'hexarr'=>[
                '0'=>['voice','car_number'],
                '1'=>'01',

            ],
        ],
        '1'=>[
            'key'=>2,
            'passage_type'=>[0],
            'car_type'=>[1,2],
            'txt'=>'{车牌号}一路平安',
            'hexarr'=>[
                '0'=>['voice','car_number'],
                '1'=>'02',

            ],
        ],

        '2'=>[
            'key'=>3,
            'passage_type'=>[1],
            'car_type'=>[1],
         //   'txt'=>'临时车禁止通行，请登记',
            'txt'=>'{车牌号}临时车未登记，请扫码入场',
            'hexarr'=>[
                '0'=>['voice','car_number'],
                '1'=>'212BC7EB7F1B',
            ],
        ],

        '3'=>[
            'key'=>4,
            'passage_type'=>[1,0],
            'car_type'=>[2],
            'txt'=>'{车牌号}停车有效期{到期时间}',
            'hexarr'=>[
                '0'=>['voice','car_number'],
                '1'=>'2E12',
                '2'=>['voice','end_time'],
            ],
        ],
        '4'=>[
            'key'=>5,
            'passage_type'=>[0],
            'car_type'=>[1],
            'txt'=>'{车牌号}停车{停车时长},请缴费{停车费}元',
            'hexarr'=>[
                '0'=>['voice','car_number'],
                '1'=>'2E',
                '2'=>['voice','duration'],
                '3'=>'6A',
                '4'=>['voice','price'],
                '5'=>'D4AA',
            ],
        ],
        '5'=>[
            'key'=>6,
            'passage_type'=>[0],
            'car_type'=>[1,2],
            'txt'=>'{车牌号}请通行,祝您一路平安',
            'hexarr'=>[
                '0'=>['voice','car_number'],
                '1'=>'28D7A3C4FA02',

            ],
        ],
        '6'=>[
            'key'=>7,
            'passage_type'=>[1],
            'car_type'=>[1],
            'txt'=>'{车牌号}临时车禁止通行',
            'hexarr'=>[
                '0'=>['voice','car_number'],
                '1'=>'212B',
            ],
        ],
        '7'=>[
            'key'=>8,
            'passage_type'=>[0],
            'car_type'=>[1],
            'txt'=>'{车牌号}未入场',
            'hexarr'=>[
                '0'=>['voice','car_number'],
                '1'=>'CEB45C',
            ],
        ],
        '8'=>[
            'key'=>9,
            'passage_type'=>[0],
            'car_type'=>[1],
            
            'txt'=>'{车牌号}停车{停车时长},扣款{停车费}元，请通行',
            'hexarr'=>[
                '0'=>['voice','car_number'],
                '1'=>'2E',
                '2'=>['voice','duration'],
                '3'=>'2C',
                '4'=>['voice','price'],
                '5'=>'D4AA28',
            ],
        ],
        '9'=>[
            'key'=>10,
            'passage_type'=>[0,1],
            'car_type'=>[1],
            'txt'=>'{车牌号}黑名单禁止通行',
            'hexarr'=>[
                '0'=>['voice','car_number'],
                '1'=>'152B',
            ],
        ],
        '10'=>[
            'key'=>11,
            'passage_type'=>[0,1],
            'car_type'=>[1],
            'txt'=>'{车牌号}岗亭人工确认，请通行',
            'hexarr'=>[
                '0'=>['voice','car_number'],
                '1'=>'6C1F28',
            ],
        ],
    ];

    // 语音内容对应十六进制
    public $voiceHex = [
        '0' => '30', '1' => '31', '2' => '32', '3' => '33', '4' => '34', '5' => '35', '6' => '36', '7' => '37', '8' => '38', '9' => '39',
        '零' => '30','小时' => '2F','时' => '2F', '一' => '31', '二' => '32', '三' => '33', '四' => '34', '五' => '35', '六' => '36', '七' => '37', '八' => '38', '九' => '39',
        'A' => '41','B' => '42', 'C' => '43', 'D' => '44', 'E' => '45', 'F' => '46', 'G' => '47', 'H' => '48', 'I' => '49', 'J' => '4A',
        'K' => '4B', 'L' => '4C', 'M' => '4D', 'N' => '4E', 'O' => '4F', 'P' => '50', 'Q' => '51', 'R' => '52', 'S' => '53', 'T' => '54',
        'U' => '55', 'V' => '56', 'W' => '57', 'X' => '58', 'Y' => '59', 'Z' => '5A',
    ];

    public function __construct($data=[]) {
        $this->data = $data;
    }

    public function getVoiceArr(){
        return $this->voiceArr;
    }
    
    public function deTranslation($data){
        if (empty($data)) {
            throw new \Exception(L_('参数缺失'));
        }
        $arr=str_split($data);
        $len=count($arr);
        if ($len<2){
            return false;
        }
       if ($arr[$len-1]=='='&&$arr[$len-2]=='='){
            unset($arr[$len-1]);unset($arr[$len-2]);
        }
        foreach ($arr as $k=>$v){
            $key=array_search($v,$this->base64Arr); //找字母 代表的10进制数字
            $bin=decbin($key); //十进制转换为二进制 12 =>001100
            $bin=$this->NumToHex($bin,6);
            $arrTo2bins[]=$bin;
        }
        $dataString = implode("",$arrTo2bins);  //2进制字符串
        $mbStrSplit = $this->mbStrSplit_hex($dataString,8,true); //16进制字符串
        if (!empty($mbStrSplit)){
            $count=count($mbStrSplit);
            unset($mbStrSplit[$count-1]);
        }
        $dataStrings = implode("",$mbStrSplit);
        return $dataStrings;
    }
    public function translation($data='') {
        if (!$data) {
            $data = $this->data;
        }
        if (empty($data)) {
            throw new Exception(L_('参数缺失'));
        }
        if (!is_array($data)) {
            $data = str_replace(' ','',$data);
            $data = $this->mbStrSplit($data,2);
        }
        $arrTo2bins = $this->arrTo2bin($data,true);
        $dataString = implode("",$arrTo2bins);
        //$dataString='1010101101010101000000010111011100000000111111110000000000000000000000000000000010101111';
        $mbStrSplit = $this->mbStrSplit($dataString,6,true,true);
        $dataStrings = implode("",$mbStrSplit);
        $dataStrings .= '=';
        return $dataStrings;
    }

    public function NumToHex($num,$len,$end=false)
    {
        if(strlen($num) != $len){
            $add = '';
            for($i=0;$i<$len-strlen($num);$i++){
                $add .= '0';
            }
            if ($end) {
                $num = $num.$add;
            } else {
                $num = $add.$num;
            }
        }
        return $num;
    }

    public function mbStrSplit_hex($string, $len=1) {
        $start = 0;
        $strlen = mb_strlen($string);
        $array=[];
        while ($strlen) {
            $str = mb_substr($string,$start,$len,"utf8");
            $str = base_convert($str,2,16);
            $str = $this->NumToHex($str,2);
            $str=strtoupper($str);
            $array[]=$str;
            $string = mb_substr($string, $len, $strlen,"utf8");
            $strlen = mb_strlen($string);
        }
        return $array;
    }
    
    public function mbStrSplit($string, $len=1,$isTo10=false,$checkBase64=false) {
        $start = 0;
        $strlen = mb_strlen($string);
        while ($strlen) {
            $str = mb_substr($string,$start,$len,"utf8");
            if ($isTo10) {
                // 是否进行2进制转10进制
                $str = $this->NumToHex($str,6,true);
                $str = bindec($str);
                if ($checkBase64&&isset($this->base64Arr[$str])) {
                    $str = $this->base64Arr[$str];
                }
            }
            $array[] = $str;
            $string = mb_substr($string, $len, $strlen,"utf8");
            $strlen = mb_strlen($string);
        }
        return $array;
    }

    public function arrTo2bin($arrs,$isNumToHex) {
        $arrTo2bins = [];
        foreach ($arrs as $key=>$item) {
            $str = $this->str2bin($item);
            if ($isNumToHex) {
                $str = $this->NumToHex($str,8);
            }
            $arrTo2bins[] = $str;
        }
        return $arrTo2bins;
    }

    public function str2bin($hexdata)
    {
        return decbin(hexdec($hexdata));
    }

    /**
     * @param string $wordStr "AA55 01 64 00 11 00 0B 01 B5 DA D2 BB D0 D0 B9 E3 B8 E6 X1 X2 AF" 传参去除 头AA55和尾AF和校验X1X2  即 '01 64 00 11 00 0B 01 B5 DA D2 BB D0 D0 B9 E3 B8 E6'
     * @return string
     */
    public function wordStr($wordStr) {
        $wordStr = str_replace(' ','',$wordStr);
        $wordCmd = $wordStr;
        $wordStr .= '0000';
        $packStr = pack('H*',$wordStr);// 把数据装入一个二进制字符串[十六进制字符串，高位在前]
        $crc166Str = $this->crc166($packStr);// 进行CRC16校验
        $unpackStr = unpack("H*", $crc166Str); // 从二进制字符串对数据进行解包[十六进制字符串，高位在前];
        return $wordCmd . strtoupper($unpackStr[1]);
    }

    public function crc166($string, $length = 0)
    {
        $auchCRCHi = array(
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 
            0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 
            0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 
            0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41,
            0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x00, 0xC1, 0x81, 0x40,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41, 
            0x00, 0xC1, 0x81, 0x40, 0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40, 0x01, 0xC0, 0x80, 0x41, 0x01, 0xC0, 0x80, 0x41,
            0x00, 0xC1, 0x81, 0x40
        );
        $auchCRCLo = array(
            0x00, 0xC0, 0xC1, 0x01, 0xC3, 0x03, 0x02, 0xC2, 0xC6, 0x06, 0x07, 0xC7, 
            0x05, 0xC5, 0xC4, 0x04, 0xCC, 0x0C, 0x0D, 0xCD, 0x0F, 0xCF, 0xCE, 0x0E, 
            0x0A, 0xCA, 0xCB, 0x0B, 0xC9, 0x09, 0x08, 0xC8, 0xD8, 0x18, 0x19, 0xD9, 
            0x1B, 0xDB, 0xDA, 0x1A, 0x1E, 0xDE, 0xDF, 0x1F, 0xDD, 0x1D, 0x1C, 0xDC, 
            0x14, 0xD4, 0xD5, 0x15, 0xD7, 0x17, 0x16, 0xD6, 0xD2, 0x12, 0x13, 0xD3,
            0x11, 0xD1, 0xD0, 0x10, 0xF0, 0x30, 0x31, 0xF1, 0x33, 0xF3, 0xF2, 0x32, 
            0x36, 0xF6, 0xF7, 0x37, 0xF5, 0x35, 0x34, 0xF4, 0x3C, 0xFC, 0xFD, 0x3D, 
            0xFF, 0x3F, 0x3E, 0xFE, 0xFA, 0x3A, 0x3B, 0xFB, 0x39, 0xF9, 0xF8, 0x38, 
            0x28, 0xE8, 0xE9, 0x29, 0xEB, 0x2B, 0x2A, 0xEA, 0xEE, 0x2E, 0x2F, 0xEF, 
            0x2D, 0xED, 0xEC, 0x2C, 0xE4, 0x24, 0x25, 0xE5, 0x27, 0xE7, 0xE6, 0x26,
            0x22, 0xE2, 0xE3, 0x23, 0xE1, 0x21, 0x20, 0xE0, 0xA0, 0x60, 0x61, 0xA1,
            0x63, 0xA3, 0xA2, 0x62, 0x66, 0xA6, 0xA7, 0x67, 0xA5, 0x65, 0x64, 0xA4,
            0x6C, 0xAC, 0xAD, 0x6D, 0xAF, 0x6F, 0x6E, 0xAE, 0xAA, 0x6A, 0x6B, 0xAB, 
            0x69, 0xA9, 0xA8, 0x68, 0x78, 0xB8, 0xB9, 0x79, 0xBB, 0x7B, 0x7A, 0xBA, 
            0xBE, 0x7E, 0x7F, 0xBF, 0x7D, 0xBD, 0xBC, 0x7C, 0xB4, 0x74, 0x75, 0xB5,
            0x77, 0xB7, 0xB6, 0x76, 0x72, 0xB2, 0xB3, 0x73, 0xB1, 0x71, 0x70, 0xB0,
            0x50, 0x90, 0x91, 0x51, 0x93, 0x53, 0x52, 0x92, 0x96, 0x56, 0x57, 0x97, 
            0x55, 0x95, 0x94, 0x54, 0x9C, 0x5C, 0x5D, 0x9D, 0x5F, 0x9F, 0x9E, 0x5E, 
            0x5A, 0x9A, 0x9B, 0x5B, 0x99, 0x59, 0x58, 0x98, 0x88, 0x48, 0x49, 0x89, 
            0x4B, 0x8B, 0x8A, 0x4A, 0x4E, 0x8E, 0x8F, 0x4F, 0x8D, 0x4D, 0x4C, 0x8C,
            0x44, 0x84, 0x85, 0x45, 0x87, 0x47, 0x46, 0x86, 0x82, 0x42, 0x43, 0x83,
            0x41, 0x81, 0x80, 0x40
        );
        $length = ($length <= 0 ? strlen($string) : $length);
        $uchCRCHi = 0xFF;
        $uchCRCLo = 0xFF;
        $uIndex = 0;
        for ($i = 0; $i < $length; $i++) {
            $uIndex = $uchCRCLo ^ ord(substr($string, $i, 1));
            $uchCRCLo = $uchCRCHi ^ $auchCRCHi[$uIndex];
            $uchCRCHi = $auchCRCLo[$uIndex];
        }
        return(chr($uchCRCHi) . chr($uchCRCLo));
    }

    // AA55(包头) 14(流水号) 64(地址) 00(业务类型) 27(命令) 0016(长度) 01000100BBB6D3ADB9E2C1D9A3ACCDEE413132333435(内容) 1694(校验) AF(结束符)

    /**
     * @param string $txt 文本
     * @param int $rows 显屏行数
     * @param string $txtOder 流水号
     * @param string $orderType 下发临显内容指令  22 语音
     * @return false|string
     */
    public function showVoiceTxt($txt, $rows = 1, $txtOder = '16', $orderType = '27', $passage_type = 1)
    {
        $txtTop = 'AA55'; // 包头
        $txtEnd = 'AF'; // 包尾
        if ($passage_type==1){
            $txtAddr = '64'; // 地址
        }else{
            $txtAddr = '65'; // 地址
        }
        
        $businessType = '00'; // 业务类型
       // $orderType = '27'; // 下发临显内容指令  22 语音
        if ($orderType=='25'){
            if (1==$rows) {
                // 第一行显示 01000100
                $dataHead = '01000100';
            } elseif (2==$rows) {
                // 第二行显示 02000100
                $dataHead = '02000100';
            } elseif (3==$rows) {
                // 第三行显示 03000100
                $dataHead = '03000100';
            } elseif (4==$rows) {
                // 第四行显示 04000100
                $dataHead = '04000100';
            } else {
                return false;
            }
        }else{
            if (1==$rows) {
                // 第一行显示 01000100
                $dataHead = '01200100';
            } elseif (2==$rows) {
                // 第二行显示 02000100
                $dataHead = '02200100';
            } elseif (3==$rows) {
                // 第三行显示 03000100
                $dataHead = '03200100';
            } elseif (4==$rows) {
                // 第四行显示 04000100
                $dataHead = '04200100';
            } else {
                return false;
            }
        }

        $strTo16Gbk = $this->strTo16Gbk($txt);
        $data = $dataHead.$strTo16Gbk; // 内容
        $length = strlen($data)/2; // 长度
        $length = dechex($length);
        $length = $this->NumToHex($length,4);
        $checkData = $txtOder . $txtAddr . $businessType . $orderType . $length . $data;
        $checkData = strtoupper($checkData);
       //  dump($checkData);
        $checkResult = $this->wordStr($checkData);// 校验
        return $txtTop.$checkResult.$txtEnd;
    }

    /**
     * @param string $txt 文本
     * @param int $rows 显屏行数
     * @param string $txtOder 流水号
     * @return false|string
     */
    public function showVoice($txt,$txtOder='16',$passage_type=1) {
        $txtTop = 'AA55'; // 包头
        $txtEnd = 'AF'; // 包尾
        if ($passage_type==1){
            $txtAddr = '64'; // 地址
        }else{
            $txtAddr = '65'; // 地址
        }
        $businessType = '00'; // 业务类型
        $orderType = '22'; // 下发临显内容指令  22 语音
        $strTo16Gbk = $this->voice2hex($txt);
        $data =$strTo16Gbk; // 内容
        $length = strlen($strTo16Gbk)/2; // 长度
        $length = dechex($length);
        $length = $this->NumToHex($length,4);
        $checkData = $txtOder . $txtAddr . $businessType . $orderType . $length . $data;
        $checkData = strtoupper($checkData);
        //  dump($checkData);
        $checkResult = $this->wordStr($checkData);// 校验
        return $txtTop.$checkResult.$txtEnd;
    }

    public function strTo16Gbk($word) {
        $gbkstr = mb_convert_encoding($word,'GBK','UTF-8');
        $hex = $this->str2hex($gbkstr);
        return $hex;
    }

    public function str2hex($str){
        $hex = '';
        for($i=0,$length=strlen($str); $i<$length; $i++){
            $hexStr = dechex(ord($str{$i}));
            $hex .= $hexStr;
        }
        return $hex;
    }

    public function voice2hex($str){
        $voiceArr=$this->voiceArr;
        $content_data=[];

        foreach ($voiceArr as $v1){
            if ($str['content']==$v1['key']){
                $content_data=$v1['hexarr'];
                break;
            }
        }
        $content_data_voice='';
        fdump_api([$voiceArr,$str,$content_data],'voice2hex-0418',1);
      if (!empty($content_data)){
          foreach ($content_data as $v){
              if (is_array($v)&&$v[0]=='voice'){
                  $hex='';
                  if ($v[1]=='end_time'){
                      $string=$this->dateToChinese(date('Y-m-d',$str[$v[1]]));
                  }elseif($v[1]=='duration'){
                      $string=$this->timeToChinese($str[$v[1]]);
                  }elseif($v[1]=='price'){
                      $string=$str[$v[1]];
                      if (floatval($string) > 0) {
                          $string = str_replace('.','点',$string);
                      } elseif (floatval($string) == 0) {
                          $string = 0;
                      }
                  }else{
                      $string=$str[$v[1]];
                  }
                  if($string){
                      for ($i=0,$length=mb_strlen($string);$i<$length;$i++){
                          $ss=mb_substr($string,$i,1);
                          if (isset($this->voiceHex[$ss])){
                              $hexStr = $this->voiceHex[$ss];
                          }else{
                              $hexStr = $this->strTo16Gbk($ss);
                          }
                          $hex .= $hexStr;
                      }
                  }
                  $content_data_voice .=$hex;
              }else{
                  $content_data_voice .= $v;
              }
          }
      }
        return $content_data_voice;
    }

    /**
     * date日期转中文
     * @author:zhubaodi
     * @date_time: 2022/4/13 18:05
     */
    public  function dateToChinese($date)
    {
        $chineseDate = '';
        if (false == empty($date)) {
            // 把数字转换成中文
            $chineseArr = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
            // 十位数对应的中文
            $chineseTenArr = array('', '十', '二十', '三十');
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
     * 天时分秒转中文
     * @author:zhubaodi
     * @date_time: 2022/4/13 18:05
     */
    public  function timeToChinese($date)
    {
        $chineseDate = '';
        if (false == empty($date)) {
            // 把数字转换成中文
            $chineseArr = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
            // 十位数对应的中文
            $chineseTenArr = array('', '十', '二十', '三十','四十','五十');
            // 获取天数
            $day = intval($date/86400);
            // 获取小时
            $hour =intval(($date-86400*$day)/3600);
            // 获取分钟
            $minte = ceil(($date-86400*$day-$hour*3600)/60);
            if ($day>0){
                // 转换为数组
                $yearArr = str_split($day);
                if(count($yearArr)==1){
                    $chineseDate .= $chineseArr[$yearArr[0]] . '天';
                }else{
                    if ($yearArr[1] != 0) {
                        // 将月份中的数字替换成中文
                        $chineseDate .= $chineseTenArr[$yearArr[0]] . $chineseArr[$yearArr[1]] . '天';
                    } else {
                        $chineseDate .= $chineseTenArr[$yearArr[0]] . '天';
                    }
                }
            }
           if ($hour>0){
               $monthArr = str_split($hour);
               if(count($monthArr)==1){
                   $chineseDate .= $chineseArr[$monthArr[0]] . '时';
               }else {
                   // 小时、分钟去除零
                   if ($monthArr[1] != 0) {
                       // 将月份中的数字替换成中文
                       $chineseDate .= $chineseTenArr[$monthArr[0]] . $chineseArr[$monthArr[1]] . '时';
                   } else {
                       $chineseDate .= $chineseTenArr[$monthArr[0]] . '时';
                   }
               }
           }

            $dayArr = str_split($minte);
            if(count($dayArr)==1){
                $chineseDate .= $chineseArr[$dayArr[0]] . '分';
            }else {
                if ($dayArr[1] != 0) {
                    $chineseDate .= $chineseTenArr[$dayArr[0]] . $chineseArr[$dayArr[1]] . '分';
                } else {
                    $chineseDate .= $chineseTenArr[$dayArr[0]] . '分';
                }
            }
        }
        return $chineseDate;
    }
    
    public function to8($str)
    {
        if (strlen($str) < 8) {
            for ($i = 8 - strlen($str); $i > 0; $i--) {
                $str = "0" . $str;
            }
        }
        return $str;
    }

    public function everyTo8(&$arr)
    {
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = $this->to8(base_convert($arr[$i], 16, 2));
        }
    }

    public function everyToDec(&$arr)
    {
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = base_convert($arr[$i], 2, 10);
        }
    }

    public function checkTotal(&$arr)
    {
        $end = 3 - count($arr) % 3;
        if (count($arr) % 3 != 0) {
            for ($i = 0; $i < $end; $i++) {
                $arr[] = "00000000";
            }
            return $end;
        } else {
            return 0;
        }
    }

    public function divide6($arr)
    {
        $str = '';
        foreach ($arr as $val) {
            $str .= $val;
        }
        if (strlen($str) % 6 != 0) die("error");
        for ($i = 0; $i < strlen($str) / 6; $i++) {
            $result[] = substr($str, $i * 6, 6);
        }
        return $result;
    }

    public function addZero(&$arr)
    {
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = $this->to8($arr[$i]);
        }
    }

    public function encode64($arr, $table, $endMod2)
    {
        $result = '';
        for ($i = 0; $i < count($arr) - $endMod2; $i++) {
            $result .= $table[$arr[$i]];
        }
        for ($i = 0; $i < $endMod2; $i++) {
            $result .= "=";
        }
        return $result;
    }

    public function handleBase64Bit16($text1)
    {
        $text1 = str_replace(' ', '', $text1);
        $textArr = str_split($text1);
        $text = '';
        foreach ($textArr as $key => $val) {
            if ($key > 0 && $key%2==0) {
                $text .= ' '.$val;
            } else {
                $text .= $val;
            }
        }
        $str = explode(" ", $text);
        $this->everyTo8($str);
        $endMod = $this->checkTotal($str);
        $str = $this->divide6($str);
        $this->addZero($str);
        $this->everyToDec($str);
        $base64Arr = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '+', '/', '='
        ];
        return $this->encode64($str, $base64Arr, $endMod);
    }
}