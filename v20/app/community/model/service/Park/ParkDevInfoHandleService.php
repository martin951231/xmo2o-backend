<?php

namespace app\community\model\service\Park;

use app\community\model\db\Car\HouseVillageCarFlowRecord;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageCarAccessRecord;
use app\community\model\db\HouseVillageParkBlack;
use app\community\model\db\HouseVillageParkCharge;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\HouseVillageParkCoupon;
use app\community\model\db\ParkCoupons;
use app\community\model\db\HouseVillageParkFree;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingCarBindPosition;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\ParkWhiteRecord;
use app\community\model\service\Car\CarHandleGetInfoService;
use app\community\model\service\Car\ParkCarToJobService;
use app\community\model\service\HouseVillageParkGarageConfigService;
use app\community\model\db\HouseVillageParkingTemp;
use app\community\model\db\HouseVillageParkShowscreenConfig;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillageVisitor;
use app\community\model\db\InPark;
use app\community\model\db\OutPark;
use app\community\model\db\ParkOpenLog;
use app\community\model\db\ParkPassage;
use app\community\model\db\ParkPlateresultLog;
use app\community\model\db\ParkSerialLog;
use app\community\model\db\ParkShowscreenLog;

use app\traits\ParkPassageHouseTraits;
use InvalidArgumentException;
use RuntimeException;
use think\Exception;
use think\facade\Cache;
use Throwable;
class ParkDevInfoHandleService
{
    use ParkPassageHouseTraits;
    public $register_day = 15;

    /***
     * 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
     * cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
     * 交易流水号(pay_type为wallet、scancode、sweepcode必传)
     **/
    public $pay_type = [0 => 'cash', 1 => 'wallet', 2 => 'sweepcode', 3 => 'escape', 4 => 'monthuser', 5 => 'free', 6 => 'scancode'];
    public $vr = 1; //1是64  2是C8
    public $voiceDataArr = array('IP', '澳', '百', '半', '北', '藏', '车场已满', '车牌', '车牌号为', '车未入场', '车位已满', '车已过期', '车已在场', '成', 
        '出入平安', '储值车', '川', '此车到期时间剩余', '此车可用日期', '此车已过期', '次', '第', '点', '吨', '鄂', '分', '分钟', '负', '该车', '甘', '赣', '港',
        '个', '固定车', '挂', '广', '贵', '桂', '过期车辆', '过期用户', '海', '号', '黑', '沪', '欢迎光临', '吉', '即将过期', '己', '济', '冀', '交费',
        '角', '津', '晋', '禁止通行', '京', '警', '军', '军警车', '可用日期', '空', '兰', '两', '辽', '临', '临时车', '临时车辆', '鲁', '蒙', '免', '秒', 
        '闽', '南', '内部车', '年', '您好', '宁', '农', '千', '青', '请减速慢行', '请交费', '请入场停车', '请通过', '请通行', '琼', '人', '日', '入场', 
        '入场时间', '入口', '陕', '深', '沈', '剩余', '剩余车位', '剩余金额', '剩余天数为', '十', '时', '时间', '使', '收费', '苏', '台', '天', '停车', '停车时长',
        '停车有效期', '皖', '万', '为', '未', '未授权', '位', '无权出场', '无权入场', '湘', '小时', '谢谢', '新', '学', '一路平安', '一路顺风', '已过期', '已缴费',
        '有效期至', '渝', '豫', '元', '月', '月卡车', '月租车', '月租车剩余', '月租车已到期', '月租已到期', '粤', '云', '浙', '整', '正', '祝您一路平安', '祝您一路顺风',
        'ETCP为您服务', 'MNull', 'SNull', '本车位已停车', '波特率为', '操作成功', '超过离场限时', '超时交费', '车号已过期', '车辆禁止出场', '车辆匹配异常', 
        '车牌不符', '车牌匹配异常', '车牌识别失败', '车位已使用', '车位已用', '车位已用完', '车位有余', '出场', '出口', '春节快乐', '此车号无效', '此车为黑名单车辆',
        '此车未登记', '此车位车辆已进场', '此车位车辆已经入场', '此车已超时', '此卡未登记', '此卡已出场', '此卡已挂失', '此卡已过期', '此卡已锁定', '此卡已在场', 
        '此卡已注销', '道闸已开启', '地址', '地址为', '端口', '端口为', '对不起', '二维码', '付款码', '该时段不允许进入', '管控车辆', '贵宾车', '国庆节快乐', '过期出场',
        '过期入场', '黑名单卡', '欢迎泊车', '欢迎登录', '欢迎回家', '欢迎入场', '欢迎使用车牌识别', '欢迎下次光临', '欢迎再次光临', '会员车', '缴费成功', '进出延时',
        '禁止出场', '禁止出入', '禁止取卡', '禁止入场', '拒绝进入', '军警车免费通行', '开闸请注意', '扣款成功', '扣款金额为', '劳动节快乐', '临时车位已满', '临时车无权限',
        '临时车无入场记录', '免费车辆', '免费放行', '免费停车', '免费通行', '内部车场不允许进入', '您有快递在管理处', '欠费外出', '请充值', '请到管理处缴费', '请等待确认',
        '请等待确认放行', '请等待人工放行', '请及时缴纳管理费', '请及时缴纳水电费', '请尽快延期', '请靠边停车检查', '请入场', '请扫码', '请扫描屏上二维码', '请稍等', 
        '请稍候', '请使用', '请停车检查', '请先到中心岗亭缴费', '请先登录操作员', '请与管理员联系', '请在', '请重新补交停车费', '请重新缴费', '请重新进入识别区', '请注意',
        '区已满', '人工开闸', '人工落闸', '扫码支付', '圣诞节快乐', '识别成功', '收费中', '手动放行', '手动关闸', '手动开闸', '手机', '刷卡成功', '抬闸保持', 
        '外来车位已满', '微信', '未缴费请到缴费处缴费', '无法人工入场', '无法入场', '无牌车', '无入场记录', '无剩余车位', '无通行权限', '系统过期', '系统开始', 
        '系统退出', '现在时间', '新年快乐', '信息已确认', '星期', '业主车辆', '已超时', '已电子支付', '音量', '银联卡', '用户', '余额', '余额不足', '余额不足请及时充值',
        '余额为', '元旦节快乐', '月租车未授权', '月租快到期', '允许取卡', '允许入场', '暂停使用', '闸杆下落', '正在计费', '正在人工处理', '支付宝', '直接放行',
        '中秋节快乐', '重复读卡', '重复进入', '重复进入不允许', '重复外出', '重复外出不允许', '注意安全', '祝您健康', '祝您早日康复');
    public $voiceFormatArr = array(
        'v1' => array('demo' => '{车牌号}欢迎光临', 'hexarr' => array('car_number', '欢迎光临')), 
        'v2' => array('demo' => '{车牌号}一路平安', 'hexarr' => array('car_number', '一路平安')), 
        'v3' => array('demo' => '{车牌号}此车未登记,请扫码,入场', 'hexarr' => array('car_number', '此车未登记,请扫码,入场')),
        'v4' => array('demo' => '{车牌号}停车有效期{到期时间}', 'hexarr' => array('car_number', '停车有效期', 'end_time')), 
        'v5' => array('demo' => '{车牌号}停车{停车时长},请交费{停车费}元', 'hexarr' => array('car_number', '停车', 'duration', ',请交费', 'price', '元')),
        'v6' => array('demo' => '{车牌号}请通行,祝您一路平安', 'hexarr' => array('car_number', '请通行,祝您一路平安')),
        'v7' => array('demo' => '{车牌号}临时车,禁止通行', 'hexarr' => array('car_number', '临时车,禁止通行')),
        'v8' => array('demo' => '{车牌号}车未入场', 'hexarr' => array('car_number', '车未入场')),
        'v9' => array('demo' => '{车牌号}停车{停车时长},扣款金额为{停车费}元,请通行', 'hexarr' => array('car_number', '停车', 'duration', ',扣款金额为', 'price', '元,请通行')), 
        'v10' => array('demo' => '{车牌号}此车为黑名单车辆,禁止通行', 'hexarr' => array('car_number', '此车为黑名单车辆,禁止通行')), 
        'v11' => array('demo' => '{车牌号}信息已确认，请通行', 'hexarr' => array('car_number', '信息已确认，请通行')),
        );

    public function __construct()
    {

    }

    public function encryptVerifyTxt($pwdTxt = '', $vr = 1)
    {
        $this->vr = $vr;
        $da = '00'; // DA:为显示屏的地址，取值范围为0x00 - 0xFF,默认为0x00
        $ver = '64'; //描述了协议版本号，目前支持版本0x64(100)和版本0xC8(200)
        if ($vr == 2) {
            $ver = 'C8'; // 16进制地址
        }
        $pn = 'FFFF'; // 包的序列  在传输小于255个字节的数据时，该值应该设置为0XFFFF
        /*
        PN:是包的序列，在传输超过255个字节的数据时，需要分包传输，
        PN表示了包的序列，每次交互完之后自增1，
        设置为最大值0xFFFF时表示当前包是最后一个包。
        在传输小于255个字节的数据时，该值应该设置为0XFFFF
        */
        $cmdType = '00'; // 00 设备链接验证

        /**数据格式:请求格式: DA + VR + PN[2] + 0x00 + DL + PWD[6~32] + CRC[2] ***/
        $strTo16Gbk = $this->strTo16Gbk($pwdTxt);
        $data = $strTo16Gbk; // 内容
        $length = strlen($strTo16Gbk) / 2; // 10进制长度
        $length = dechex($length); //转成16进制数字
        $zero_fill_len = 2;  //长度补0
        if ($vr == 2) {
            $zero_fill_len = 4;
        }
        $length = $this->NumToHex($length, $zero_fill_len);  //检测16进制数位够不够  不够补0

        $checkData = $da . $ver . $pn . $cmdType . $length . $data;
        $checkData = strtoupper($checkData);
        $checkResult = $this->wordStr($checkData); // 生成带CRC校验的数据 
        return $checkResult;
    }

    public function strTo16Gbk($word)
    {
        /***GB2312是GBK的子集，若目标系统支持GBK，建议直接使用GBK以获得更广的字符兼容性‌****/
        $gbkstr = mb_convert_encoding($word, 'GBK', 'UTF-8');//转成设备支持的 GBK编码
        $hex = $this->str2hex($gbkstr);
        return $hex;
    }

    public function str2hex($str)
    {
        $hex = '';
        for ($i = 0, $length = strlen($str); $i < $length; $i++) {
            $tmp10 = ord($str{$i});  //取字符 10进制值
            $hexStr = dechex($tmp10); //十进制转换为十六进制
            $hex .= $hexStr;
        }
        return $hex;
    }

    public function NumToHex($num, $len, $end = false)
    {
        $numStr = strval($num);
        $numLen = strlen($numStr);
        if ($numLen < $len) {
            $add = '';
            for ($i = 0; $i < $len - $numLen; $i++) {
                $add .= '0';
            }
            if ($end) {
                $num = $num . $add;
            } else {
                $num = $add . $num; //默认在前面补0
            }
        }
        return $num;
    }

    public function wordStr($wordStr)
    {
        $wordStr = str_replace(' ', '', $wordStr);
        $wordCmd = $wordStr;
        $wordStr .= '0000';
        $packStr = pack('H*', $wordStr);// 把数据装入一个二进制字符串[十六进制字符串，高位在前]
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
        return (chr($uchCRCHi) . chr($uchCRCLo));
    }

    /***
     *无车辆进出时 空闲时屏幕上显示的 内容
     * 车道设置的显屏数据 下发
     * $vr 1是64 2是C8
     **/
    public function showBaseScreenTxt($screenTxt = '', $rows = 1, $vr = 1)
    {
        $this->vr = $vr;
        $da = '00'; // DA:为显示屏的地址，取值范围为0x00 - 0xFF,默认为0x00
        $ver = '64'; //描述了协议版本号，目前支持版本0x64(100)和版本0xC8(200)
        if ($vr == 2) {
            $ver = 'C8'; // 16进制地址
        }
        $pn = 'FFFF'; // 包的序列  在传输小于255个字节的数据时，该值应该设置为0XFFFF
        /*
        PN:是包的序列，在传输超过255个字节的数据时，需要分包传输，
        PN表示了包的序列，每次交互完之后自增1，
        设置为最大值0xFFFF时表示当前包是最后一个包。
        在传输小于255个字节的数据时，该值应该设置为0XFFFF
        */
        $cmdType = '67'; // 67下载广告语 
        $twid = '00';
        if (1 == $rows) {
            // 第一行显示 
            $twid = '00';
        } elseif (2 == $rows) {
            // 第二行显示 
            $twid = '01';
        } elseif (3 == $rows) {
            // 第三行显示 
            $twid = '02';
        } elseif (4 == $rows) {
            // 第四行显示 
            $twid = '03';
        }

        $strTo16Gbk = $this->strTo16Gbk($screenTxt);
        $data = $strTo16Gbk; // 内容
        $length = strlen($strTo16Gbk) / 2; // 10进制长度
        $allLen = $length + 20;  //总长度

        $length = dechex($length); //转成16进制数字
        $zero_fill_len = 2;  //长度补0
        if ($vr == 2) {
            $zero_fill_len = 4;
        }
        $tl = $this->NumToHex($length, $zero_fill_len);  //检测16进制数位够不够  不够补0
        /***tl 为文字的长度 ***/

        $allLen = dechex($allLen); //转成16进制数字
        $zero_fill_len = 2;  //长度补0
        if ($vr == 2) {
            $zero_fill_len = 4;
        }
        $allLen = $this->NumToHex($allLen, $zero_fill_len);  //检测16进制数位够不够  不够补0

        $etm = '00';
        /**
         * ETM 16禁止数据 取值含义
         * 00 立即显示
         * 01 从右向左移动
         * 02 从左向右移动
         * 03 从下向上移动
         * 04 从上向下移动
         * 05 向下拉窗
         * 06 向上拉窗
         * 07 向左拉窗
         * 08 向右拉窗
         * 0D 逐字显示
         * 15 连续左移
         **/
        $ets = "01";  //为文字进入的速度。取值范围为1~32。时基取决于当前的扫描周期
        $dt = "06";  //为文字停留的时间。取值范围为0 ~255
        $ra1 = "0000";     //2个字节保留值，固定取值为0x00 0x00
        $findex = "04";
        /*
         * findex 为字体索引值，注意:每种主板支持字体不一样 
         * 00 ASCII8
         * 01 ASCII10 
         * 02 ASCII13
         * 03 宋体16 
         * 04 宋体24 
         * 05 宋体32 
         * 06 宋体48 
         * 07 宋体64 
         */
        $tc = "FF000000"; //为文字的颜色值。存储结构为 R G B A三基色，各占8位，R表示红色分量，G表示绿色分量,B表示蓝色分量，A目前没用使用，作为保留字。各取值范围为0~255。
        $ra2 = '00000000';  //4 个字节保留值，固定取值为0x00 0x00 0x00 0x00
        $r1 = "00";  //保留值，固定取值为0x00
        $r2 = "0C";  //保留值，固定取值为0x00
        $r3 = "00";  //保留值，固定取值为0x00
        //TEXT:为显示的文字内容，支持ASCII和GBK2312编码 /**数据格式: DA+VR+PN[2]+0x67+DL+TWID+R1+R2+ETM+ETS+R3+DT+RA1[2]+FINDEX+TC[4]+RA2[4]+TL+R3+TEXT[...]+CRC[2]   **/
        //            00  00   0C   15  01  00  06    0000    04    FF000000 00000000  1A  00
        $set16Data = $twid . $r1 . $r2 . $etm . $ets . $r3 . $dt . $ra1 . $findex . $tc . $ra2 . $tl . $r3;  //20个长度

        $checkData = $da . $ver . $pn . $cmdType . $allLen . $set16Data . $data;
        $checkData = strtoupper($checkData);
        $checkResult = $this->wordStr($checkData); // 生成带CRC校验的数据 
        return $checkResult;
    }

    /***
     *时间大小设置
     * $tvl 为文本播报音量百分比，取值范围在0~100
     * $avl 为视频播放音量百分比，取值范围在0~100
     * $timeSegment 时间段设置 包含数据如下
     * 如果不需要时间段控制音量，可以不用携带以下参数:
     * NE:时间段控制音量开关,0关闭，1打开。
     * SH:时间段开始小时 0~23。
     * SM:时间段开始分钟0~59。
     * EH:时间段结束小时0~23。
     * EM:时间段结束分钟0~59。
     * NVL:时间段内的音量值0~100。
     */
    public function voiceSoundControl($tvl = 50, $avl = 50, $vr = 1, $timeSegment = array())
    {
        $this->vr = $vr;
        $da = '00'; // DA:为显示屏的地址，取值范围为0x00 - 0xFF,默认为0x00
        $ver = '64'; //描述了协议版本号，目前支持版本0x64(100)和版本0xC8(200)
        if ($vr == 2) {
            $ver = 'C8'; // 16进制地址
        }
        $pn = 'FFFF'; // 包的序列  在传输小于255个字节的数据时，该值应该设置为0XFFFF
        $cmdType = '0D';
        $zero_fill_len = 2;  //长度补0
        if ($vr == 2) {
            $zero_fill_len = 4;
        }
        $allLen = 0;
        $tvl = intval($tvl);
        if ($tvl < 0 || $tvl > 100) {
            return '';
        }
        $tvl = dechex($tvl); //转成16进制数字
        $tvl = $this->NumToHex($tvl, $zero_fill_len);  //检测16进制数位够不够  不够补0
        $avl = intval($avl);
        if ($avl < 0 || $avl > 100) {
            return '';
        }
        $avl = dechex($avl); //转成16进制数字
        $avl = $this->NumToHex($avl, $zero_fill_len);  //检测16进制数位够不够  不够补0
        $timeAlotStr = '';
        $allLen = 2;
        if ($timeSegment && isset($timeSegment['is_sound_control']) && isset($timeSegment['sound_segment']) && isset($timeSegment['nvl'])) {
            $timeAlotStr = '00';
            if ($timeSegment['is_sound_control'] <= 0) {
                $timeAlotStr = '01';
            }
            $sh = $timeSegment['sound_segment']['sh'] > 0 ? $timeSegment['sound_segment']['sh'] : 0;
            $sm = $timeSegment['sound_segment']['sm'] > 0 ? $timeSegment['sound_segment']['sm'] : 0;
            $eh = $timeSegment['sound_segment']['eh'] > 0 ? $timeSegment['sound_segment']['eh'] : 23;
            $eh = $eh > 23 ? 23 : $eh;
            $em = $timeSegment['sound_segment']['em'] > 0 ? $timeSegment['sound_segment']['em'] : 59;
            $em = $em > 59 ? 59 : $em;

            $sh16 = dechex($sh);  //开始小时
            $sh16 = $this->NumToHex($sh16, $zero_fill_len);  //检测16进制数位够不够  不够补0
            $sm16 = dechex($sm);  //开始分钟
            $sm16 = $this->NumToHex($sm16, $zero_fill_len);
            $eh16 = dechex($eh);  //结束小时
            $eh16 = $this->NumToHex($eh16, $zero_fill_len);
            $em16 = dechex($em);  //结束分钟
            $em16 = $this->NumToHex($em16, $zero_fill_len);
            $nvl = $timeSegment['nvl'] > 100 ? 100 : $timeSegment['nvl'];
            $nvl = $nvl < 0 ? 0 : $nvl;
            $nvl16 = dechex($nvl);
            $nvl16 = $this->NumToHex($nvl16, $zero_fill_len);  //检测16进制数位够不够  不够补0
            $timeAlotStr .= $sh16 . $sm16 . $eh16 . $em16 . $nvl16;
            $allLen = $allLen + 6;
        }
        $allLen = dechex($allLen);
        $allLen = $this->NumToHex($allLen, $zero_fill_len);
        /**数据格式:  DA + VR + PN[2] + 0x0D + DL +TVL+ AVL +NE+SH+SM+EH+EM+NVL + CRC[2]  **/
        $checkData = $da . $ver . $pn . $cmdType . $allLen . $tvl . $avl . $timeAlotStr;
        $checkData = strtoupper($checkData);
        $checkResult = $this->wordStr($checkData); // 生成带CRC校验的数据 
        return $checkResult;
    }

    public function getOnePassageInfo($whereArr = array())
    {
        $db_park_passage = new ParkPassage();
        $passage_info = array();
        if ($whereArr) {
            $passage_info_obj = $db_park_passage->getFind($whereArr, '*');
            if ($passage_info_obj && !$passage_info_obj->isEmpty()) {
                $passage_info = $passage_info_obj->toArray();
            }
        }
        return $passage_info;
    }

    /**
     * 设备获取车牌后回调接口
     * @author:zhubaodi
     * @date_time: 2021/11/11 13:23
     */
    public function add_park_info($data, $parkTime = '')
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'can_open_gate' => false);
        $car_number = '';
        if (isset($data['plate_num'])) {
            $car_number = $data['plate_num'];
        }
        $db_park_passage = new ParkPassage();
        //查询设备
        $serialno = '';
        $passage_info = array();
        if (isset($data['cam_id'])) {
            $serialno = $data['cam_id'];
            $cache_serialno_key = 'park:hxt85:' . $serialno . ':serialno';
            if ($serialno && !empty(cache($cache_serialno_key))) {
                $passage_info = cache($cache_serialno_key);
            } else {
                $passage_info_obj = $db_park_passage->getFind(['device_number' => $serialno, 'status' => 1]);
                if ($passage_info_obj && !$passage_info_obj->isEmpty()) {
                    $passage_info = $passage_info_obj->toArray();
                    cache($cache_serialno_key, $passage_info, 7200);
                }
            }
        }
        if (empty($passage_info)) {
            //TODO:屏显和语音提示错误信息
            if ($parkTime) {
                fdump_api(['err_msg' => '停车设备不存在', 'car_number' => $car_number, 'serialno' => $serialno], 'hxPark/plateresult' . $parkTime, true);
            }
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '停车设备不存在';
            $returnArr['can_open_gate'] = false;
            return $returnArr;
        }
        //未识别到车牌
        if (!$car_number || strpos($car_number, '无') !== false) {
            //TODO:屏显和语音提示错误信息
            //未识别到车牌号
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '未识别到车牌';
            $returnArr['can_open_gate'] = false;
            return $returnArr;
        }
        $village_info = array();
        if (isset($passage_info['village_id']) && $passage_info['village_id'] && !empty(cache($passage_info['village_id']))) {
            $village_info = cache($passage_info['village_id']);
        } else {
            $db_house_village = new HouseVillage();
            $village_info_obj = $db_house_village->getOne($passage_info['village_id'], 'village_id,village_name');
            if (!empty($village_info_obj) && !$village_info_obj->isEmpty()) {
                $village_info = $village_info_obj->toArray();
                cache($passage_info['village_id'], $village_info, 7200);
            }
        }
        if ($parkTime) {
            fdump_api(['设备获取车牌后回调接口' => $car_number, 'passage_info' => $passage_info, 'village_info' => $village_info], 'hxPark/plateresult' . $parkTime, true);
        }
        //黑名单查询
        if (!empty(cache('backList_' . $passage_info['village_id']))) {
            $black_list = cache('backList_' . $passage_info['village_id']);
        } else {
            $db_house_village_park_black = new HouseVillageParkBlack();
            $whereBlackList = [];
            $whereBlackList[] = ['village_id', '=', $passage_info['village_id']];
            $black_list = $db_house_village_park_black->getList($whereBlackList);
            if ($black_list && !$black_list->isEmpty()) {
                $black_list = $black_list->toArray();
            } else {
                $black_list = array();
            }
            cache('backList_' . $passage_info['village_id'], $black_list, 3600);
        }
        if (!empty($black_list)) {
            if ($parkTime) {
                fdump_api(['黑名单' => $car_number, 'black_list' => $black_list], 'hxPark/plateresult' . $parkTime, true);
            }
            if (!empty($black_list)) {
                $is_black_car = false;
                foreach ($black_list as $va) {
                    if ($car_number == $va['car_number']) {
                        $is_black_car = true;
                        //TODO:屏显和语音提示错误信息
                        //黑名单车辆不允许通行
                        break;
                    }
                }
                if ($is_black_car) {
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = '黑名单车辆不允许通行';
                    $returnArr['can_open_gate'] = false;
                    return $returnArr;
                }
            }
        }
        //车辆入场
        if ($passage_info['passage_direction'] == 1) {
            $param = ['parkTime' => $parkTime, 'car_number' => $car_number, 'serialno' => $serialno,];

            fdump_api(['车辆入场' => $car_number, 'param' => $param], 'hxPark/plateresult' . $parkTime, true);

            try {
                $inArrRet = $this->in_park($car_number, $data, $village_info, $passage_info, $param);
            } catch (\Exception $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param], 'hxPark/errLog1', true);
            } catch (Throwable $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param], 'hxPark/errLog1', true);
            } catch (RuntimeException $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param], 'hxPark/errLog1', true);
            } catch (InvalidArgumentException $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param], 'hxPark/errLog1', true);
            }
            fdump_api(['车辆入场' => $car_number, 'res' => $inArrRet], 'hxPark/plateresult' . $parkTime, true);
            if ($inArrRet['is_ok'] === true) {
                $returnArr['err_code'] = 0;
                $returnArr['err_msg'] = '';
                $returnArr['can_open_gate'] = true;
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = $inArrRet['err_msg'] ? $inArrRet['err_msg'] : '无法开闸通行';
                $returnArr['can_open_gate'] = false;
            }
            return $returnArr;
        }
        //车辆出场
        if ($passage_info['passage_direction'] == 0) {
            $param = ['parkTime' => $parkTime, 'car_number' => $car_number, 'serialno' => $serialno,];
            fdump_api(['车辆出场' => $car_number, 'param' => $param], 'hxPark/plateresult' . $parkTime, true);
            try {
                $outArrRet = $this->out_park($car_number, $data, $village_info, $passage_info, $param);
            } catch (\Exception $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param], 'hxPark/errLog1', true);
            } catch (Throwable $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param], 'hxPark/errLog1', true);
            } catch (RuntimeException $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param], 'hxPark/errLog1', true);
            } catch (InvalidArgumentException $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param], 'hxPark/errLog1', true);
            }
            fdump_api(['车辆出场' => $car_number, 'res' => $outArrRet], 'hxPark/plateresult' . $parkTime, true);
            if ($outArrRet['is_ok'] === true) {
                $returnArr['err_code'] = 0;
                $returnArr['err_msg'] = '';
                $returnArr['can_open_gate'] = true;
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = $outArrRet['err_msg'] ? $outArrRet['err_msg'] : '请扫码缴费';
                $returnArr['can_open_gate'] = false;
            }
            return $returnArr;
        }

        $returnArr['err_code'] = 1;
        $returnArr['err_msg'] = '车闸出入口异常';
        $returnArr['can_open_gate'] = false;
        return $returnArr;
    }

    /**
     * 车辆入场
     * @author:zhubaodi
     * @date_time: 2022/3/17 11:52
     */
    public function in_park($car_number = '', $data = array(), $village_info = array(), $passage_info = array(), $param = [])
    {
        $inArrRet = array('err_code' => 0, 'err_msg' => '', 'is_ok' => false);
        if (isset($param['parkTime']) && $param['parkTime']) {
            $parkTime = $param['parkTime'];
        } else {
            $parkTime = date('YmdH');
        }
        $serialno = '';
        if (isset($param['serialno']) && $param['serialno']) {
            $serialno = trim($param['serialno']);
        }
        $garage_id = 0;
        if ($passage_info && isset($passage_info['garage_id']) && $passage_info['garage_id'] > 0) {
            $garage_id = $passage_info['garage_id'];
        } else if ($passage_info && isset($passage_info['passage_area']) && !empty($passage_info['passage_area']) && $passage_info['area_type'] == 2) {
            $garage_id = intval($passage_info['passage_area']);
        }
        $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'HXT85';
        $db_in_park = new InPark();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_park_free = new HouseVillageParkFree();
        $db_house_village_visitor = new HouseVillageVisitor();
        $db_park_plateresult_log = new ParkPlateresultLog();
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $showscreen_data = [];
        $showscreen_data['passage'] = $passage_info;
        $showscreen_data['village_id'] = $passage_info['village_id'];
        $showscreen_data['car_number'] = $car_number;
        $showscreen_data['channel_id'] = $serialno;
        $showscreen_data['car_type'] = '';
        $showscreen_data['voice_content'] = 'v1';
        $showscreen_data['content'] = '欢迎光临';

        $now_time = time();
        $park_log_data = [];
        $park_log_data['car_number'] = $car_number;
        $park_log_data['channel_id'] = $serialno;
        $park_log_data['park_type'] = 1;
        $park_log_data['village_id'] = $passage_info['village_id'];
        $park_log_data['add_time'] = $now_time;
        $logId = $db_park_plateresult_log->add($park_log_data);

        fdump_api(['添加识别记录' => $car_number, 'park_log_data' => $park_log_data, 'showscreen_data' => $showscreen_data, 'logId' => $logId], 'hxPark/plateresult' . $parkTime, true);

        $rand_num = date('Ymd', $now_time);// 换成日期存储

        $path = '/upload/park_hx/' . $rand_num . '/' . $serialno . '/';
        $up_dir = $_SERVER['DOCUMENT_ROOT'] . $path;
        $park_data = [];
        // 记录车辆进入信息
        $car_access_record = [];
        $park_data['car_number'] = $car_number;
        $park_data['in_time'] = $now_time;
        $park_data['order_id'] = uniqid();
        $park_data['in_channel_id'] = $passage_info['id'];
        $park_data['is_paid'] = 0;
        $park_data['park_id'] = $passage_info['village_id'];
        $park_data['park_sys_type'] = $park_sys_type;
        $park_data['park_name'] = $village_info['village_name'];

        fdump_api(['车辆相关信息' => $car_number, 'park_data' => $park_data], 'hxPark/plateresult' . $parkTime, true);

        $ymdhis = date('Ymdhis', $now_time);
        $imageToJob = [];
        $isImageToJob = false;
        if (isset($data['picture']) && !empty($data['picture'])) {
            $imageFile = trim($data['picture']);
            $imageFile = str_replace(array('-', '_', '.'), array('+', '/', '='), $imageFile);
            $savePathFile = $ymdhis . '_image_big_' . $car_number;
            $isImageToJob = true;
            $imageToJob['park_type'] = 'in_park';
            $imageToJob['serialno'] = $serialno;
            $imageToJob['car_number'] = $car_number;
            $imageToJob['in_time'] = $now_time;
            $imageToJob['order_id'] = $park_data['order_id'];
            $imageToJob['village_id'] = $passage_info['village_id'];
            $imageToJob['nowTime'] = $now_time;
            $imageToJob['imageSavePath'] = $up_dir;
            $imageToJob['imageFile'] = $imageFile;
            $imageToJob['imageFileSaveFileName'] = $savePathFile;
            if (isset($data['from_xtype'])) {
                $imageToJob['from_xtype'] = $data['from_xtype'];
            }

            fdump_api(['车辆相关信息-1图片' => $car_number], 'hxPark/plateresult' . $parkTime, true);
            unset($data['picture']);
        }
        if (isset($data['closeup_pic']) && !empty($data['closeup_pic'])) {
            $imageFragmentFile = trim($data['closeup_pic']);
            $imageFragmentFile = str_replace(array('-', '_', '.'), array('+', '/', '='), $imageFragmentFile);
            $savePathFragmentFile = $ymdhis . '_image_small_' . $car_number;
            if (!$isImageToJob) {
                $isImageToJob = true;
                $imageToJob['park_type'] = 'in_park';
                $imageToJob['serialno'] = $serialno;
                $imageToJob['car_number'] = $car_number;
                $imageToJob['in_time'] = $now_time;
                $imageToJob['order_id'] = $park_data['order_id'];
                $imageToJob['village_id'] = $passage_info['village_id'];
                $imageToJob['nowTime'] = $now_time;
                $imageToJob['imageSavePath'] = $up_dir;
            }
            $imageToJob['imageFragmentFile'] = $imageFragmentFile;
            $imageToJob['imageFragmentFileSaveFileName'] = $savePathFragmentFile;
            if (isset($data['from_xtype'])) {
                $imageToJob['from_xtype'] = $data['from_xtype'];
            }

            fdump_api(['车辆相关信息-2图片' => $car_number], 'hxPark/plateresult' . $parkTime, true);
            unset($data['closeup_pic']);
        }

        fdump_api(['车辆相关信息' => $car_number, 'park_data' => $park_data], 'hxPark/plateresult' . $parkTime, true);

        $car_access_record['channel_id'] = $passage_info['id'];
        $car_access_record['channel_number'] = $passage_info['channel_number'];
        $car_access_record['channel_name'] = $passage_info['passage_name'];
        $imageBigUrlPath = '';
        $imageSmallUrlPath = '';
        $car_access_record['channel_id'] = $passage_info['id'];
        $car_access_record['channel_number'] = $passage_info['channel_number'];
        $car_access_record['channel_name'] = $passage_info['passage_name'];
        /*
        if(isset($data['imagePath']) && $data['imagePath']){
            $imageUrlPathTmp= base64_decode($data['AlarmInfoPlate']['result']['PlateResult']['imagePath']);
            if($imageUrlPathTmp && (strpos($imageUrlPathTmp,'ttp://') || strpos($imageUrlPathTmp,'ttps://'))){
                $imageUrlPathArr=explode('?',$imageUrlPathTmp);
                $imageBigUrlPath=$imageUrlPathArr['0'];
                $imageBigUrlPath=str_replace('http:','https:',$imageBigUrlPath);
                $car_access_record['accessBigImage']=$imageBigUrlPath;
                $park_data['in_image_big']=$imageBigUrlPath;
            }
        }
        if(isset($data['imageFragmentPath']) && $data['imageFragmentPath']){
            $imageUrlPathTmp= base64_decode($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentPath']);
            if($imageUrlPathTmp && (strpos($imageUrlPathTmp,'ttp://') || strpos($imageUrlPathTmp,'ttps://'))){
                $imageUrlPathArr=explode('?',$imageUrlPathTmp);
                $imageSmallUrlPath=$imageUrlPathArr['0'];
                $imageSmallUrlPath=str_replace('http:','https:',$imageSmallUrlPath);
                $car_access_record['accessImage']=$imageSmallUrlPath;
                $park_data['in_image_small']=$imageSmallUrlPath;
            }
        }
        */
        //查询免费车
        if (!empty(cache('freeList_' . $passage_info['village_id']))) {
            $free_list = cache('freeList_' . $passage_info['village_id']);
        } else {
            $free_list_obj = $db_house_village_park_free->getList(['village_id' => $passage_info['village_id']]);
            $free_list = '';
            if ($free_list_obj && !$free_list_obj->isEmpty()) {
                $free_list = $free_list_obj->toArray();
            }
            cache('freeList_' . $passage_info['village_id'], $free_list, 3600);
        }
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        if ($isImageToJob) {
            $imageToJob['car_number_no'] = $car_no;
        }

        fdump_api(['查询条件' => $car_number, 'province' => $province, 'car_no' => $car_no], 'hxPark/plateresult' . $parkTime, true);

        $carCachekey = 'village:' . $passage_info['village_id'] . ':car:' . $car_number;

        if (!empty(cache($carCachekey))) {
            $car_info = cache($carCachekey);
            if (!isset($car_info['end_time']) || $car_info['end_time'] <= $now_time) {
                // 缓存中取不出来车辆信息或者截止时间过期了 避免出现问题 及时查询下最新的信息并缓存
                $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
                if ($car_info && !$car_info->isEmpty()) {
                    $car_info = $car_info->toArray();
                    cache($carCachekey, $car_info, 3600);
                } else {
                    cache($carCachekey, null);
                }
            }
        } else {
            $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
            if ($car_info && !$car_info->isEmpty()) {
                $car_info = $car_info->toArray();
                cache($carCachekey, $car_info, 3600);
            } else {
                cache($carCachekey, null);
            }
        }
        $car_access_record['garage_id'] = $garage_id;
        $rightPosition = array();
        if ($car_info && isset($car_info['car_id'])) {
            $rightPosition = $this->getCarRightInPosition($car_info, $passage_info['village_id'], $garage_id);
            fdump_api(['car_info' => $car_info, 'rightPosition' => $rightPosition], 'hxPark/plateresult' . $parkTime, 1);
            if (!empty($rightPosition)) {
                if ($rightPosition['start_time'] <= $now_time && $rightPosition['end_time'] >= $car_info['end_time']) {
                    $car_info['end_time'] = $rightPosition['end_time'];
                    cache($carCachekey, $car_info, 3600);
                }
                if ($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids']) && $rightPosition['start_time'] > $now_time) {
                    //车位时间还没开始
                    $car_info['end_time'] = 0;
                }
                if (isset($rightPosition['position_id']) && $rightPosition['position_id']) {
                    $car_access_record['position_id'] = $rightPosition['position_id'];
                }
                if (isset($rightPosition['garage_id']) && $rightPosition['garage_id']) {
                    $car_access_record['garage_id'] = $rightPosition['garage_id'];
                }
                if (isset($rightPosition['position_num']) && $rightPosition['position_num']) {
                    $car_access_record['position_num'] = $rightPosition['position_num'];
                }
            } elseif (empty($rightPosition)) {
                $bind_position_ids = array();
                if ($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])) {
                    //新的绑定车位 多车多位逻辑，没有可有车位时
                    $bind_position_ids = explode(',', $car_info['bind_position_ids']);
                    $car_info['end_time'] = 0;   //没有可有车位时
                }
                if (isset($car_info['garage_id']) && $car_info['garage_id'] > 0 && $car_info['garage_id'] != $garage_id) {
                    //进入的不是同一个车库
                    $car_info['end_time'] = 0;
                }
                if ($car_info && $car_info['car_position_id']) {
                    $bind_position_ids[] = $car_info['car_position_id'];
                }
                if (empty($bind_position_ids)) {
                    //标记一下无车位进场临时车
                    $car_access_record['flag_status'] = 'temporaryCarNoPosition';
                    $car_access_record['park_car_type'] = 'temporaryCarNoPosition';
                    $car_access_record['car_type'] = 'temporaryCarNoPosition';
                }
            }
            $car_access_record['car_id'] = $car_info['car_id'];
        } else {
            //标记一下无车位进场临时车
            $car_access_record['flag_status'] = 'temporaryCarNoPosition';
            $car_access_record['park_car_type'] = 'temporaryCarNoPosition';
            $car_access_record['car_type'] = 'temporaryCarNoPosition';
        }

        $user_name = empty($car_info['car_user_name']) ? '' : $car_info['car_user_name'];
        $user_phone = empty($car_info['car_user_phone']) ? '' : $car_info['car_user_phone'];

        fdump_api(['车辆信息' => $car_number, 'car_info' => $car_info], 'hxPark/plateresult' . $parkTime, true);

        $car_visitor_cache = 'village:' . $passage_info['village_id'] . ':car_visitor:' . $car_number;
        $visitor_info = array();
        if (!$user_name && !$user_phone && !empty(cache($car_visitor_cache))) {
            $visitor_info = cache($car_visitor_cache);
            $user_name = empty($visitor_info['visitor_name']) ? '' : $visitor_info['visitor_name'];
            $user_phone = empty($visitor_info['visitor_phone']) ? '' : $visitor_info['visitor_phone'];
            $uid = empty($visitor_info['uid']) ? '' : $visitor_info['uid'];
        } elseif (!$user_name && !$user_phone) {
            $visitor_info = $db_house_village_visitor->get_one(['village_id' => $passage_info['village_id'], 'car_id' => $car_number], 'id, visitor_name, visitor_phone, uid');
            cache($car_visitor_cache, $visitor_info);
            $user_name = empty($visitor_info['visitor_name']) ? '' : $visitor_info['visitor_name'];
            $user_phone = empty($visitor_info['visitor_phone']) ? '' : $visitor_info['visitor_phone'];
            $uid = empty($visitor_info['uid']) ? '' : $visitor_info['uid'];
        }
        if ($user_name) {
            $car_access_record['user_name'] = $user_name;
        }
        if ($user_phone) {
            $car_access_record['user_phone'] = $user_phone;
        }
        if (isset($uid) && $uid) {
            $car_access_record['uid'] = $uid;
        }
        $starttime = $now_time - 100;
        $endtime = $now_time + 10;
        // 查询in_park
        $park_where = [['car_number', '=', $car_number], ['park_id', '=', $passage_info['village_id']], ['in_time', '>=', $starttime], ['in_time', '<=', $endtime], ['del_time', '<', 1],];
        // 查询house_village_car_access_record
        $whereAccessRecord = [['car_number', '=', $car_number], ['park_id', '=', $passage_info['village_id']], ['accessType', '=', 1], ['park_sys_type', '=', $park_sys_type], ['accessTime', '>=', $starttime], ['accessTime', '<=', $endtime], ['del_time', '<', 1],];

        if (!empty($free_list)) {
            fdump_api(['免费车' => $car_number, 'free_list' => $free_list], 'hxPark/plateresult' . $parkTime, true);
            $isFreePark = false;
            foreach ($free_list as $v) {
                $first_number = substr($car_number, 0, strlen($v['first_name']));
                $last_name = substr($car_number, '-' . strlen($v['last_name']));
                if (!empty($v['first_name']) && $first_number == $v['first_name']) {
                    $isFreePark = true;
                } elseif (!empty($v['last_name']) && $last_name == $v['last_name']) {
                    $isFreePark = true;
                } elseif ($car_number == $v['free_park']) {
                    $isFreePark = true;
                }
                if ($isFreePark) {
                    fdump_api(['免费车' => $car_number, 'fv' => $v], 'hxPark/plateresult' . $parkTime, true);
                    break;
                }
            }
            fdump_api(['免费车' => $car_number, 'isFreePark' => $isFreePark], 'hxPark/plateresult' . $parkTime, true);
            if ($isFreePark) {
                $park_info_car = $db_in_park->getOne1($park_where);
                if ($park_info_car && !$park_info_car->isEmpty()) {
                    $park_info_car = $park_info_car->toArray();
                } else {
                    $park_info_car = array();
                }
                fdump_api(['条件查询结果' => $car_number, 'park_where' => $park_where, 'park_info_car' => $park_info_car], 'hxPark/plateresult' . $parkTime, true);
                if (empty($park_info_car) || !isset($park_info_car['id'])) {
                    //写入车辆入场表
                    $insert_id = $db_in_park->insertOne($park_data);
                    fdump_api(['in_park' => '插入in_park表', 'park_data' => $park_data, 'insert_id' => $insert_id], 'hxPark/plateresult' . $parkTime, true);

                    //写入车辆入场表
                    $car_access_record['business_type'] = 0;
                    $car_access_record['business_id'] = $passage_info['village_id'];
                    $car_access_record['car_number'] = $car_number;
                    $car_access_record['accessType'] = 1;
                    $car_access_record['accessTime'] = $now_time;
                    $car_access_record['accessMode'] = 8;
                    $car_access_record['park_sys_type'] = $park_sys_type;
                    $car_access_record['is_out'] = 0;
                    $car_access_record['park_id'] = $passage_info['village_id'];
                    $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                    $car_access_order_id = date('YmdHis') . rand(100, 999);
                    $car_access_record['order_id'] = $car_access_order_id;
                    $car_access_record['update_time'] = $now_time;
                    if (isset($insert_id) && !empty($insert_id)) {
                        $car_access_record['from_id'] = $insert_id;
                    }

                    fdump_api(['记录通行记录' => $car_number, 'car_access_record' => $car_access_record], 'hxPark/plateresult' . $parkTime, true);
                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                    if ($record_id > 0) {

                        $in_park_info = $db_in_park->getOne(array('id' => $insert_id), '*');
                        if ($in_park_info && isset($in_park_info['access_order_id']) && empty($in_park_info['access_order_id'])) {
                            $saveArr = array('access_record_id' => $record_id, 'access_order_id' => $car_access_order_id);
                            $db_in_park->saveOne(array('id' => $insert_id), $saveArr);
                        }

                        fdump_api(['car_number' => $car_number, 'record_id' => $record_id, 'msg' => '数据记录添加完成'], 'hxPark/plateresult' . $parkTime, true);

                        $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 1, 'access_time' => time());
                        (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                    }
                    fdump_api(['记录通行记' => $car_number, 'record_id' => $record_id], 'hxPark/plateresult' . $parkTime, true);

                } else {
                    $insert_id = $park_info_car['id'];
                    $record_id = isset($park_info_car['access_record_id']) ? intval($park_info_car['access_record_id']) : 0;
                    $car_access_order_id = isset($park_info_car['access_order_id']) ? trim($park_info_car['access_order_id']) : '';
                }
                if ($isImageToJob) {
                    $imageToJob['in_park_id'] = $insert_id;
                }

                //TODO:屏显和语音提示特殊车辆通行
                $showscreen_data['content'] = '免费车通行';
                fdump_api(['记录通行记录' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);
                $this->addParkShowScreenLog($showscreen_data);
                //特殊车辆通行
                if ($isImageToJob && ($record_id || $insert_id)) {
                    if (isset($car_access_order_id) && $car_access_order_id) {
                        $imageToJob['car_access_order_id'] = $car_access_order_id;
                    }
                    $imageToJob['in_record_id'] = $record_id;
                    (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                }
                $inArrRet['err_code'] = 0;
                $inArrRet['err_msg'] = '正常通行';
                $inArrRet['is_ok'] = true;
                return $inArrRet;
            }

        }
        //查询车辆到期时间
        if (!empty($car_info) && $car_info['end_time'] > $now_time) {
            fdump_api(['查询下1分钟内入场时间' => $car_number, 'park_where' => $park_where], 'hxPark/plateresult' . $parkTime, true);
            $park_info_car = $db_in_park->getOne1($park_where);
            if ($park_info_car && !$park_info_car->isEmpty()) {
                $park_info_car = $park_info_car->toArray();
            } else {
                $park_info_car = array();
            }
            fdump_api(['条件查询结果' => $car_number, 'park_where' => $park_where, 'park_info_car' => $park_info_car], 'hxPark/plateresult' . $parkTime, true);
            if (empty($park_info_car) || !isset($park_info_car['id'])) {
                //写入车辆入场表
                $insert_id = $db_in_park->insertOne($park_data);

                fdump_api(['in_park' => '插入in_park表', 'park_data' => $park_data, 'insert_id' => $insert_id], 'hxPark/plateresult' . $parkTime, true);

                //写入车辆入场表
                $car_access_record['business_type'] = 0;
                $car_access_record['business_id'] = $passage_info['village_id'];
                $car_access_record['car_number'] = $car_number;
                $car_access_record['accessType'] = 1;
                $car_access_record['accessTime'] = $now_time;
                $car_access_record['accessMode'] = 5;
                $car_access_record['park_sys_type'] = $park_sys_type;
                $car_access_record['is_out'] = 0;
                $car_access_record['park_id'] = $passage_info['village_id'];
                $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                $car_access_order_id = date('YmdHis') . rand(100, 999);
                $car_access_record['order_id'] = $car_access_order_id;
                $car_access_record['update_time'] = $now_time;
                if (isset($insert_id) && !empty($insert_id)) {
                    $car_access_record['from_id'] = $insert_id;
                }

                fdump_api(['入车辆入场表' => $car_number, 'car_access_record' => $car_access_record], 'hxPark/plateresult' . $parkTime, true);
                $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                if ($record_id > 0) {
                    $in_park_info = $db_in_park->getOne(array('id' => $insert_id), '*');
                    if ($in_park_info && isset($in_park_info['access_order_id']) && empty($in_park_info['access_order_id'])) {
                        $saveArr = array('access_record_id' => $record_id, 'access_order_id' => $car_access_order_id);
                        $db_in_park->saveOne(array('id' => $insert_id), $saveArr);
                    }
                    fdump_api(['car_number' => $car_number, 'record_id' => $record_id, 'msg' => '数据记录添加完成'], 'hxPark/plateresult' . $parkTime, true);
                    $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 1, 'access_time' => time());
                    (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                }

                fdump_api(['入车辆入场表' => $car_number, 'record_id' => $record_id], 'hxPark/plateresult' . $parkTime, true);

            } else {
                $insert_id = $park_info_car['id'];
                $record_id = isset($park_info_car['access_record_id']) ? intval($park_info_car['access_record_id']) : 0;
                $car_access_order_id = isset($park_info_car['access_order_id']) ? trim($park_info_car['access_order_id']) : '';
            }
            if ($isImageToJob) {
                $imageToJob['in_park_id'] = $insert_id;
            }
            //写入车辆入场表
            //$in_record_info = $db_house_village_car_access_record->getOne($whereAccessRecord);

            //TODO:屏显和语音提示特殊车辆通行
            $showscreen_data['car_type'] = 'month_type';
            $showscreen_data['end_time'] = $car_info['end_time'];
            $showscreen_data['end_time_txt'] = date('Y-m-d', $car_info['end_time']);

            fdump_api(['屏显和语音提示特殊车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

            if (!empty($rightPosition)) {
                $whereArr = array('position_id' => $rightPosition['position_id']);
                $saveArr = array('use_car_id' => $car_info['car_id'], 'use_start_time' => $now_time, 'use_car_number' => $car_number, 'access_record_id' => $record_id);
                $this->updateParkingPositionUse($whereArr, $saveArr, $car_number, 'in_park');
            }
            $this->addParkShowScreenLog($showscreen_data);
            //特殊车辆通行
            if ($isImageToJob && ($record_id || $insert_id)) {
                if (isset($car_access_order_id) && $car_access_order_id) {
                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                }
                $imageToJob['in_record_id'] = $record_id;
                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
            }
            $inArrRet['err_code'] = 0;
            $inArrRet['err_msg'] = '正常通行';
            $inArrRet['is_ok'] = true;
            return $inArrRet;
        } else {
            //临时车入场
            $park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id, $passage_info['village_id']);
            $expire_month_car_notin = 0;
            //曾今是月租车 现在过期了
            if (($car_info && $car_info['end_time'] > 100 && $car_info['end_time'] < $now_time) && $park_config && isset($park_config['expire_month_car_type']) && $park_config['expire_month_car_type']) {
                /*** 1月租车过期按临时车收费2月租车过期禁止入场3过期多少天后禁止入场**/
                $expire_month_car_type = $park_config['expire_month_car_type'];
                $expire_month_car_day = $park_config['expire_month_car_day'];
                if ($expire_month_car_type == 2 || ($expire_month_car_type == 3 && empty($expire_month_car_day))) {
                    $expire_month_car_notin = 1;
                } else if ($expire_month_car_type == 3 && $expire_month_car_day > 0) {
                    $expire_month_car_second = $expire_month_car_day * 24 * 3600;
                    $month_end_time = !empty($car_info) && $car_info['end_time'] > 0 ? $car_info['end_time'] : 0;
                    $expire_month_time_tmp = $now_time - $month_end_time;
                    if ($expire_month_time_tmp > $expire_month_car_second) {
                        $expire_month_car_notin = 1;
                    }
                }
            }
            //开启禁止临时车入场配置，禁止临时车入场
            if (!empty($park_config) && (empty($park_config['temp_in_park_type']) || ($expire_month_car_notin == 1))) {
                $showscreen_data['car_type'] = 'temporary_type';
                $showscreen_data['content'] = '临时车禁止通行';
                $showscreen_data['voice_content'] = 'v7';
                //TODO:屏显和语音提示错误信息
                $this->addParkShowScreenLog($showscreen_data);
                fdump_api(['停车设备不存在' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

                $inArrRet['err_code'] = 1;
                $inArrRet['err_msg'] = '临时车禁止通行';
                $inArrRet['is_ok'] = false;
                return $inArrRet;
            } elseif (!empty($park_config) && !empty($park_config['register_day'])) {
                $addTime = strtotime(date('Y-m-d 00:00:00')) - $park_config['register_day'] * 86400;
            } else {
                $addTime = strtotime(date('Y-m-d 00:00:00')) - $this->register_day * 86400;
            }
            //查询临时车
            $where_temp = [['car_id', '=', $car_number], ['village_id', '=', $passage_info['village_id']], ['add_time', '>', $addTime], ['add_time', '<', strtotime(date('Y-m-d 23:59:59'))],];
            $temp_info_obj = $db_house_village_visitor->get_one($where_temp, '*');
            $temp_info = '';
            if ($temp_info_obj && !$temp_info_obj->isEmpty()) {
                $temp_info = $temp_info_obj->toArray();
            }
            fdump_api(['查询临时车结果' => $car_number, 'park_config' => $park_config, 'temp_info' => $temp_info, 'where_temp' => $where_temp], 'hxPark/plateresult' . $parkTime, true);
            // 是否是多车多位的月租车
            $isCarsAndPositionsMonth = 0;
            if (isset($car_info['car_position_id']) && intval($car_info['car_position_id']) > 0 && isset($car_info['car_id']) && intval($car_info['car_id']) > 0) {
                try {
                    $db_house_village_parking_position = new HouseVillageParkingPosition();
                    $wherePosition = [];
                    $wherePosition[] = ['village_id', '=', $passage_info['village_id']];
                    $wherePosition[] = ['position_id', '=', intval($car_info['car_position_id'])];
                    $wherePosition[] = ['end_time', '>=', $now_time];
                    $positionObj = $db_house_village_parking_position->getFindOne($wherePosition, 'position_id, village_id, start_time, end_time');
                    if ($positionObj && !is_array($positionObj)) {
                        $position = $positionObj->toArray();
                    }
                    if (!empty($position) && isset($position['position_id']) && $position['position_id']) {
                        $isCarsAndPositionsMonth = 1;
                    }
                } catch (\Exception $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'hxPark/plateresult' . $parkTime, true);
                }
            }

            fdump_api(['是否是多车多位的月租车' => $car_number, 'isCarsAndPositionsMonth' => $isCarsAndPositionsMonth], 'hxPark/plateresult' . $parkTime, true);

            $showscreen_data['car_type'] = 'temporary_type';
            $showscreen_data['content'] = '临时车未登记，禁止入场';
            $showscreen_data['voice_content'] = 'v3';
            if (empty($temp_info) && $park_config['register_type'] == 1 && $isCarsAndPositionsMonth == 0) {
                //TODO:屏显和语音提示错误信息
                $this->addParkShowScreenLog($showscreen_data);
                fdump_api(['屏显和语音提示错误信息' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);
                $inArrRet['err_code'] = 1;
                $inArrRet['err_msg'] = '临时车未登记，禁止入场';
                $inArrRet['is_ok'] = false;
                return $inArrRet;
            } else {
                $park_info_car = $db_in_park->getOne1($park_where);
                if ($park_info_car && !$park_info_car->isEmpty()) {
                    $park_info_car = $park_info_car->toArray();
                } else {
                    $park_info_car = array();
                }
                fdump_api(['查询车辆' => $car_number, 'park_info_car' => $park_info_car], 'hxPark/plateresult' . $parkTime, true);
                if (empty($park_info_car) || !isset($park_info_car['id'])) {
                    //写入车辆入场表
                    $insert_id = $db_in_park->insertOne($park_data);
                    fdump_api(['in_park' => '插入in_park表', 'park_data' => $park_data, 'insert_id' => $insert_id], 'hxPark/plateresult' . $parkTime, true);

                    //写入车辆入场表
                    $car_access_record['business_type'] = 0;
                    $car_access_record['business_id'] = $passage_info['village_id'];
                    $car_access_record['car_number'] = $car_number;
                    $car_access_record['accessType'] = 1;
                    $car_access_record['accessTime'] = $now_time;
                    $car_access_record['accessMode'] = 9;
                    $car_access_record['park_sys_type'] = $park_sys_type;
                    if (!isset($car_access_record['user_name']) || !$car_access_record['user_name']) {
                        $car_access_record['user_name'] = empty($temp_info['visitor_name']) ? '' : $temp_info['visitor_name'];
                    }
                    if (!isset($car_access_record['user_phone']) || !$car_access_record['user_phone']) {
                        $car_access_record['user_phone'] = empty($temp_info['visitor_phone']) ? '' : $temp_info['visitor_phone'];
                    }
                    $car_access_record['is_out'] = 0;
                    $car_access_record['park_id'] = $passage_info['village_id'];
                    $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                    $car_access_order_id = date('YmdHis') . rand(100, 999);
                    $car_access_record['order_id'] = $car_access_order_id;
                    $car_access_record['update_time'] = $now_time;
                    if (isset($insert_id) && !empty($insert_id)) {
                        $car_access_record['from_id'] = $insert_id;
                    }

                    fdump_api(['写入车辆' => $car_number, 'car_access_record' => $car_access_record, 'in_park_id' => $insert_id], 'hxPark/plateresult' . $parkTime, true);

                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                    if ($record_id > 0) {

                        $in_park_info = $db_in_park->getOne(array('id' => $insert_id), '*');
                        if ($in_park_info && isset($in_park_info['access_order_id']) && empty($in_park_info['access_order_id'])) {
                            $saveArr = array('access_record_id' => $record_id, 'access_order_id' => $car_access_order_id);
                            $db_in_park->saveOne(array('id' => $insert_id), $saveArr);
                        }

                        fdump_api(['car_number' => $car_number, 'record_id' => $record_id, 'msg' => '数据记录添加完成'], 'hxPark/plateresult' . $parkTime, true);

                        $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 1, 'access_time' => time());
                        (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                    }

                    fdump_api(['写入车辆' => $car_number, 'record_id' => $record_id], 'hxPark/plateresult' . $parkTime, true);

                } else {
                    $insert_id = $park_info_car['id'];
                    $record_id = isset($park_info_car['access_record_id']) ? intval($park_info_car['access_record_id']) : 0;
                    $car_access_order_id = isset($park_info_car['access_order_id']) ? trim($park_info_car['access_order_id']) : '';
                }
                if ($isImageToJob) {
                    $imageToJob['in_park_id'] = $insert_id;
                }
                //$in_record_info = $db_house_village_car_access_record->getOne($whereAccessRecord);

                //TODO:屏显和语音提示特殊车辆通行
                $showscreen_data['content'] = '欢迎光临';
                $showscreen_data['voice_content'] = 'v1';

                fdump_api(['写入车辆' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

                $this->addParkShowScreenLog($showscreen_data);
                //特殊车辆通行
                if ($isImageToJob && ($record_id || $insert_id)) {
                    if (isset($car_access_order_id) && $car_access_order_id) {
                        $imageToJob['car_access_order_id'] = $car_access_order_id;
                    }
                    $imageToJob['in_record_id'] = $record_id;
                    (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                }
                $inArrRet['err_code'] = 0;
                $inArrRet['err_msg'] = '正常通行';
                $inArrRet['is_ok'] = true;
                return $inArrRet;
            }
        }
        $inArrRet['err_code'] = 1;
        $inArrRet['err_msg'] = '通行异常';
        $inArrRet['is_ok'] = false;
        return $inArrRet;
    }

    /***
     *获取车辆绑定的车位 最长停车时间
     ***/
    public function getCarRightInPosition($car = array(), $village_id = 0, $garage_id = 0)
    {

        $bind_position_ids = array();
        if ($car && isset($car['bind_position_ids']) && !empty($car['bind_position_ids'])) {
            $bind_position_ids = explode(',', $car['bind_position_ids']);
            $bind_position_ids = array_map('intval', $bind_position_ids);
        }
        if ($car && $car['car_position_id']) {
            $bind_position_ids[] = intval($car['car_position_id']);
        }
        $parkTime = date('YmdH');
        $nowtime = time();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $car_number = '';
        if ($car && isset($car['car_number'])) {
            //检查一下异常的占用 重置成未占用 走队列
            /*
            $jobParams = [
                'jobType' => 'resettingUseParkingPosition', 'village_id' => $village_id, 'car_id' => $car['car_id'],
                'nowTime' => $nowtime
            ];
            (new ParkCarToJobService())->HandleUseParkingPositionToJob($jobParams);
            */

            //检查一下异常的占用 重置成未占用
            $car_number = $car['province'] . $car['car_number'];
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            //$whereArr[] = array('use_car_id', '=', $car['car_id']);
            $whereArr[] = array('use_car_number', '=', $car_number);
            $whereArr[] = array('use_start_time', '<', $nowtime);
            $fieldStr = 'position_id,garage_id,end_time,access_record_id,use_start_time,use_car_number,use_car_id';
            $use_position_obj = $db_house_village_parking_position->getList($whereArr, $fieldStr, 0);
            if ($use_position_obj && !$use_position_obj->isEmpty()) {
                $use_position = $use_position_obj->toArray();
                $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => 0);
                $this->updateParkingPositionUse($whereArr, $saveArr, $car_number, 'in_park_check');
            }
        }
        $position = array();
        fdump_api(['bind_position_ids' => $bind_position_ids, 'car_number' => $car_number], 'hxPark/plateresult' . $parkTime, 1);
        if ($bind_position_ids) {
            $bind_position_ids = array_unique($bind_position_ids);
            /*
            $positionWhere=array();
            $positionWhere[] = array('village_id', '=', $village_id);
            $positionWhere[] = array('position_id', 'in', $bind_position_ids);
            $positionWhere[] = array('use_start_time', '>', 100);
            $positionWhere[] = array('use_start_time', '<', $nowtime);
            $fieldStr = 'position_id,garage_id,end_time,access_record_id,use_start_time,use_car_number,use_car_id';
            $use_position_obj = $db_house_village_parking_position->getList($positionWhere, $fieldStr, 0);
            $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
            if ($use_position_obj && !$use_position_obj->isEmpty()) {
                $use_positions = $use_position_obj->toArray();
                foreach ($use_positions as $pvv){
                    if(!empty($pvv['use_car_number'])){
                        $access_record_where=array('car_number'=>$pvv['use_car_number'],'park_id'=>$village_id,'garage_id'=>$pvv['garage_id']);
                        $park_access_record_obj = $db_house_village_car_access_record->getOne($access_record_where,'record_id,business_id,car_number,accessType,accessTime','accessTime DESC,record_id DESC');
                        if($park_access_record_obj && !$park_access_record_obj->isEmpty() && $park_access_record_obj['accessType']==2){
                           //车子已经出去了，车位占用未被解除的
                            $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => 0);
                            $this->updateParkingPositionUse(array('position_id'=>$pvv['position_id'],'village_id'=>$village_id), $saveArr, $pvv['use_car_number'], 'in_park_check');
                        }
                    }
                }
            }
            */
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = array('position_id', 'in', $bind_position_ids);
            if ($garage_id > 0) {
                $whereArr[] = array('garage_id', '=', $garage_id);
            }
            //$end_time = $nowtime - 86400;
            //$whereArr[] = array('end_time', '>', $end_time);
            $whereArr[] = array('use_car_id', '=', 0);
            $whereArr[] = array('use_start_time', '<', 100);

            $positionObj = $db_house_village_parking_position->getFind($whereArr, '*', 'end_time desc');
            if ($positionObj && !$positionObj->isEmpty()) {
                $position = $positionObj->toArray();
            }
            fdump_api(['whereArr' => $whereArr, 'car_number' => $car_number, 'position' => $position], 'hxPark/plateresult' . $parkTime, 1);
            if (empty($position) && count($bind_position_ids) == 1 && $car && isset($car['car_id'])) {
                try {
                    $db_house_village_parking_car = new HouseVillageParkingCar();
                    $whereCarPositionCount = [];
                    $whereCarPositionCount[] = ['is_del', '=', 1];
                    $whereCarPositionCount[] = ['village_id', '=', $village_id];
                    $whereCarPositionCount[] = ['car_id', '<>', $car['car_id']];
                    $whereRaw = '(car_position_id =' . $bind_position_ids['0'] . ' OR FIND_IN_SET(' . $bind_position_ids['0'] . ',bind_position_ids))';
                    $otherCarObj = $db_house_village_parking_car->getOneCar($whereCarPositionCount, $whereRaw, 'car_id');
                    if (empty($otherCarObj) || $otherCarObj->isEmpty()) {
                        $wherePosition = [];
                        $wherePosition[] = ['village_id', '=', $village_id];
                        $wherePosition[] = ['position_id', '=', $bind_position_ids['0']];
                        if ($garage_id > 0) {
                            $wherePosition[] = array('garage_id', '=', $garage_id);
                        }
                        $positionObj = $db_house_village_parking_position->getFindOne($wherePosition, '*');
                        if ($positionObj && !$positionObj->isEmpty()) {
                            $position = $positionObj->toArray();
                        }
                    }
                } catch (\Exception $e) {
                    fdump_api(['bind_position_ids' => $bind_position_ids, 'car_number' => $car_number, 'err' => $e->getMessage()], 'hxPark/plateresult' . $parkTime, 1);
                }
            }
        }
        if (!empty($position)) {
            $position['start_time'] = isset($position['start_time']) && intval($position['start_time']) > 0 ? intval($position['start_time']) : 0;
        }
        //$this->updateHXt85WhiteList($position, $car, $village_id, 'in_park');
        return $position;
    }

    public function updateParkingPositionUse($whereArr = array(), $saveArr = array(), $car_number = '', $comfrom = 'out_park')
    {
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        fdump_api(['car_number' => $car_number, 'comfrom' => $comfrom, 'whereArr' => $whereArr, 'saveArr' => $saveArr], 'park_temp/00updateParkingPositionUse' . date('H'), 1);
        $ret = $db_house_village_parking_position->saveOne($whereArr, $saveArr);
        return $ret;
    }

    /**
     * 添加显屏指令
     * @author:zhubaodi
     * @date_time: 2022/4/6 17:45
     */
    public function addParkShowScreenLog($data)
    {
        $db_house_village_park_showScreen_config = new HouseVillageParkShowscreenConfig();
        $db_park_showscreen_log = new ParkShowscreenLog();
        /*----------添加显屏指令start-----------*/
        //临时车入场
        $temp_in_content = '欢迎光临';
        //临时车出场
        $temp_out_content1 = '停车';
        $temp_out_content2 = '请交费';
        $temp_out_content3 = '一路顺风';
        //月租车入场
        $mouth_in_content = '欢迎光临';
        //月租车出场
        $mouth_out_content1 = '停车有效期';
        $config_info = [];
        //车辆类型
        $carType = isset($data['car_type']) ? $data['car_type'] : '';
        //通道方向
        $passageDirection = isset($data['passage']['passage_direction']) ? $data['passage']['passage_direction'] : 0;
        //设备类型
        $deviceType = isset($data['passage']['device_type']) ? $data['passage']['device_type'] : 0;
        //设备类型 下发显屏指令类型（30播放语音 62临显 67广告）
        $orderType = 62;
        $park_sys_type = isset($data['passage']['park_sys_type']) && $data['passage']['park_sys_type'] ? trim($data['passage']['park_sys_type']) : 'HXT85';
        //通道类型
        $showContentArr = [];
        $voice_num_arr = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11);
        if (isset($data['voice_content']) && in_array($data['voice_content'], $voice_num_arr)) {
            $data['voice_content'] = 'v' . $data['voice_content'];
        }
        //todo 进出场 显屏
        switch ($carType) {
            case 'temporary_type':
                if ($passageDirection == 1) {
                    //todo 车辆入场
                    //todo 横屏
                    $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $data['car_number']];
                    $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => empty($data['content']) ? $temp_in_content : $data['content']];

                } elseif ($passageDirection == 0) {
                    //todo 车辆出场
                    if (!isset($data['price']) || empty($data['price'])) {
                        if (empty($data['content'])) {
                            $content = $temp_out_content3;
                        } else {
                            $content = $data['content'];
                        }
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $data['car_number'], 'duration' => $data['duration'], 'price' => 0];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content, 'duration' => $data['duration'], 'price' => 0];
                    } else {
                        if (empty($data['content'])) {
                            if (isset($data['voice_content']) && $data['voice_content'] == 'v9') {
                                $content = '扣款金额为' . $data['price'] . '元,请通行';
                            } else {
                                $content = $temp_out_content2 . $data['price'] . '元';
                            }

                        } else {
                            $content = $data['content'];
                        }

                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $data['car_number'], 'duration' => $data['duration'], 'price' => $data['price']];

                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => empty($data['content']) ? $temp_out_content1 . $data['duration_txt'] : $data['content'], 'duration' => $data['duration'], 'price' => $data['price']];

                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 3, 'serial' => '03', 'content' => $content, 'duration' => $data['duration'], 'price' => $data['price']];
                    }
                }
                break;
            case 'month_type':
                if ($passageDirection == 1) {
                    //todo 车辆入场

                    $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $data['car_number'], 'end_time' => $data['end_time']];
                    $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => empty($data['content']) ? $mouth_in_content : $data['content'], 'end_time' => $data['end_time']];

                } elseif ($passageDirection == 0) {
                    //todo 车辆出场
                    $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $data['car_number'], 'end_time' => $data['end_time']];
                    $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => empty($data['content']) ? $mouth_out_content1 . $data['end_time_txt'] : $data['content'], 'end_time' => $data['end_time']];
                }
                break;
            default:
                if ($passageDirection == 1) {
                    //todo 车辆入场
                    $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $data['car_number'], 'end_time' => $data['end_time']];
                    $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => empty($data['content']) ? $mouth_in_content : $data['content'], 'end_time' => $data['end_time']];

                } elseif ($passageDirection == 0) {
                    //todo 车辆出场
                    $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $data['car_number'], 'end_time' => $data['end_time']];
                    $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => empty($data['content']) ? $temp_out_content3 : $data['content'], 'end_time' => $data['end_time']];
                }
        }
        $xcontent = (!isset($data['content']) && !empty($data['content'])) ? $data['content'] : '';
        //todo 进场 语音
        if ($passageDirection == 1) {
            $voice_code = 'v1';
            if (isset($data['voice_content']) && !empty($data['voice_content'])) {
                $voice_code = $data['voice_content'];
            }
            $showContentArr[] = ['content_type' => 2, 'screen_row' => 3, 'serial' => '01', 'car_number' => $data['car_number'], 'content' => $xcontent, 'voice_code' => $voice_code, 'end_time' => isset($data['end_time']) ? $data['end_time'] : 0, 'duration' => isset($data['duration']) ? $data['duration'] : 0, 'price' => isset($data['price']) ? $data['price'] : 0];
        }

        //todo 出场 语音
        if ($passageDirection == 0) {
            $voice_code = 'v2';
            switch ($carType) {
                case 'temporary_type':
                    if (!empty($data['duration'])) {
                        $voice_code = (!isset($data['voice_content']) || empty($data['voice_content'])) ? 'v5' : $data['voice_content'];
                    } else {
                        $voice_code = (!isset($data['voice_content']) || empty($data['voice_content'])) ? 'v2' : $data['voice_content'];
                    }
                    $showContentArr[] = ['content_type' => 2, 'screen_row' => 3, 'serial' => '01', 'car_number' => $data['car_number'], 'content' => $content, 'voice_code' => $voice_code, 'end_time' => isset($data['end_time']) ? $data['end_time'] : 0, 'duration' => isset($data['duration']) ? $data['duration'] : 0, 'price' => isset($data['price']) ? $data['price'] : 0];
                    break;
                case 'month_type':
                    if ($data['end_time'] > 10) {
                        if (!isset($data['voice_content']) || empty($data['voice_content'])) {
                            $voice_code = 'v4';
                        } else {
                            $voice_code = $data['voice_content'];
                        }
                    } else {
                        if (!isset($data['voice_content']) || empty($data['voice_content'])) {
                            $voice_code = 'v2';
                        } else {
                            $voice_code = $data['voice_content'];
                        }
                    }
                    $showContentArr[] = ['content_type' => 2, 'screen_row' => 3, 'serial' => '01', 'car_number' => $data['car_number'], 'content' => $content, 'voice_code' => $voice_code, 'end_time' => isset($data['end_time']) ? $data['end_time'] : 0, 'duration' => isset($data['duration']) ? $data['duration'] : 0, 'price' => isset($data['price']) ? $data['price'] : 0];
                    break;
                default:
                    if (!isset($data['voice_content']) || empty($data['voice_content'])) {
                        $voice_code = 'v2';
                    } else {
                        $voice_code = $data['voice_content'];
                    }
                    $showContentArr[] = ['content_type' => 2, 'screen_row' => 3, 'serial' => '01', 'car_number' => $data['car_number'], 'content' => $xcontent, 'voice_code' => $voice_code, 'end_time' => isset($data['end_time']) ? $data['end_time'] : 0, 'duration' => isset($data['duration']) ? $data['duration'] : 0, 'price' => isset($data['price']) ? $data['price'] : 0];
            }
        }

        $serialData = [];
        if ($showContentArr) {
            foreach ($showContentArr as $kk => $item) {
                if ($item['content_type'] == 1) { //显屏
                    $showVoiceTxt = $this->showTempScreenTxt($item['content'], $item['screen_row']);
                } else { //语音
                    $showVoiceTxt = $this->showVoice($item);
                }
                if ($showVoiceTxt) {
                    $serialData[] = ['encodetype' => 'hex2string', 'data' => $showVoiceTxt,];
                }
            }
        }
        $showscreen_log = ['village_id' => $data['village_id'], 'park_sys_type' => $park_sys_type, 'content' => $data['car_number'], 'content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'car_number' => $data['car_number'], 'channel_id' => $data['channel_id'], 'park_type' => 1, 'orderType' => $orderType, 'add_time' => time(), 'showcontent' => json_encode($serialData, JSON_UNESCAPED_UNICODE)];
        $logId = $db_park_showscreen_log->add($showscreen_log);
        fdump_api([__LINE__, ['car_number' => $data['car_number'], 'showContentArr' => $showContentArr, 'logId' => $logId, 'showscreen_log' => $showscreen_log]], 'hxPark/addShowScreenLog', 1);
        return $logId;
    }

    /***
     *车辆进出 临时显示的显屏
     * $vr 1是64 2是C8
     **/
    public function showTempScreenTxt($screenTxt = '', $rows = 1, $vr = 1)
    {
        $this->vr = $vr;
        $da = '00'; // DA:为显示屏的地址，取值范围为0x00 - 0xFF,默认为0x00
        $ver = '64'; //描述了协议版本号，目前支持版本0x64(100)和版本0xC8(200)
        if ($vr == 2) {
            $ver = 'C8'; // 16进制地址
        }
        $pn = 'FFFF'; // 包的序列  在传输小于255个字节的数据时，该值应该设置为0XFFFF
        /*
        PN:是包的序列，在传输超过255个字节的数据时，需要分包传输，
        PN表示了包的序列，每次交互完之后自增1，
        设置为最大值0xFFFF时表示当前包是最后一个包。
        在传输小于255个字节的数据时，该值应该设置为0XFFFF
        */
        $cmdType = '62'; // 62下发临显内容指令 
        $twid = '00';
        if (1 == $rows) {
            // 第一行显示 
            $twid = '00';
        } elseif (2 == $rows) {
            // 第二行显示 
            $twid = '01';
        } elseif (3 == $rows) {
            // 第三行显示 
            $twid = '02';
        } elseif (4 == $rows) {
            // 第四行显示 
            $twid = '03';
        }

        $strTo16Gbk = $this->strTo16Gbk($screenTxt);
        $data = $strTo16Gbk; // 内容
        $length = strlen($strTo16Gbk) / 2; // 10进制长度
        $allLen = $length + 19;  //总长度

        $length = dechex($length); //转成16进制数字
        $zero_fill_len = 2;  //长度补0
        if ($vr == 2) {
            $zero_fill_len = 4;
        }
        $tl = $this->NumToHex($length, $zero_fill_len);  //检测16进制数位够不够  不够补0
        /***tl 为文字的长度 ***/

        $allLen = dechex($allLen); //转成16进制数字
        $zero_fill_len = 2;  //长度补0
        if ($vr == 2) {
            $zero_fill_len = 4;
        }
        $allLen = $this->NumToHex($allLen, $zero_fill_len);  //检测16进制数位够不够  不够补0

        $etm = '00';
        /**
         * ETM 16禁止数据 取值含义
         * 00 立即显示
         * 01 从右向左移动
         * 02 从左向右移动
         * 03 从下向上移动
         * 04 从上向下移动
         * 05 向下拉窗
         * 06 向上拉窗
         * 07 向左拉窗
         * 08 向右拉窗
         * 0D 逐字显示
         * 15 连续左移
         **/
        $ets = "01";  //为文字进入的速度。取值范围为1~32。时基取决于当前的扫描周期
        $gsp = "00";
        /**
         * GST: 界面显示时间，单位为秒。该参数只有在多界面的显示模板才生效，取值为0时
         * 表示使用模板默认值，取值为255表示一直显示当前界面，取值1~254表示用户自定
         * 义的界面显示时间。
         * */
        $dt = "06";  //为文字停留的时间。取值范围为0 ~255
        $ra1 = "0000";     //2个字节保留值，固定取值为0x00 0x00
        $findex = "04";
        /*
         * findex 为字体索引值，注意:每种主板支持字体不一样 
         * 00 ASCII8
         * 01 ASCII10 
         * 02 ASCII13
         * 03 宋体16 
         * 04 宋体24 
         * 05 宋体32 
         * 06 宋体48 
         * 07 宋体64 
         */
        $drs = "03";  //为显示的次数。取值范围为0~255,当为0的时，表示无限循环显示
        $tc = "FF000000"; //为文字的颜色值。存储结构为 R G B A三基色，各占8位，R表示红色分量，G表示绿色分量,B表示蓝色分量，A目前没用使用，作为保留字。各取值范围为0~255。
        $ra2 = '00000000';  //4 个字节保留值，固定取值为0x00 0x00 0x00 0x00
        $r1 = "00";  //保留值，固定取值为0x00
        //TEXT:为显示的文字内容，支持ASCII和GBK2312编码 /**数据格式: DA+VR+PN[2]+CMD+DL+TWID+ETM+ETS+GST+DT+RA1[2]+FINDEX+DRS+TC[4]+RA2[4]+TL+R1+TEXT[...]+CRC[2]  **/
        //            00   00   01   00   05  00 01      04    03  FF000000 00000000  08 00
        $set16Data = $twid . $etm . $ets . $gsp . $dt . $ra1 . $findex . $drs . $tc . $ra2 . $tl . $r1;  //19个长度

        $checkData = $da . $ver . $pn . $cmdType . $allLen . $set16Data . $data;
        $checkData = strtoupper($checkData);
        $checkResult = $this->wordStr($checkData); // 生成带CRC校验的数据 
        return $checkResult;
    }

    public function showVoice($screenArr = array(), $vr = 1)
    {
        $this->vr = $vr;
        $da = '00'; // DA:为显示屏的地址，取值范围为0x00 - 0xFF,默认为0x00
        $ver = '64'; //描述了协议版本号，目前支持版本0x64(100)和版本0xC8(200)
        if ($vr == 2) {
            $ver = 'C8'; // 16进制地址
        }
        $pn = 'FFFF'; // 包的序列  在传输小于255个字节的数据时，该值应该设置为0XFFFF
        /**
         * PN:是包的序列，在传输超过255个字节的数据时，需要分包传输，
         * PN表示了包的序列，每次交互完之后自增1，
         * 设置为最大值0xFFFF时表示当前包是最后一个包。
         * 在传输小于255个字节的数据时，该值应该设置为0XFFFF
         **/
        $cmdType = '30'; // 下发临显内容指令  30 语音
        $opt = '01';
        /**
         * OPT 取值含义
         * 00:添加到语音队列但是不开始播放
         * 01:添加到语音队列并且开始播放
         * 02:先清除队列，再添加新语音到队列，然后开始播放
         **/
        $strTo16Gbk = $this->voiceToHexStr($screenArr);
        if (empty($strTo16Gbk)) {
            return '';
        }
        $data = $strTo16Gbk; // 内容
        $length = strlen($strTo16Gbk) / 2; // 10进制长度
        $length = $length + 1; //加上一个OPT 长度
        $length = dechex($length); //转成16进制数字
        $zero_fill_len = 2;  //长度补0
        if ($vr == 2) {
            $zero_fill_len = 4;
        }
        $length = $this->NumToHex($length, $zero_fill_len);  //检测16进制数位够不够  不够补0

        /**数据格式: DA + VR + PN[2] + CMD + length +OTP + data[MAX 254]+ CRC[2]**/

        $checkData = $da . $ver . $pn . $cmdType . $length . $opt . $data;
        $checkData = strtoupper($checkData);
        $checkResult = $this->wordStr($checkData); // 生成带CRC校验的数据 
        return $checkResult;
    }

    public function voiceToHexStr($screenArr)
    {
        $content_data_voice = '';
        if (!empty($screenArr) && is_array($screenArr)) {
            $string = '';
            //有已经定义的格式的语音
            if (isset($screenArr['voice_code']) && isset($this->voiceFormatArr[$screenArr['voice_code']])) {
                $voiceFormat = $this->voiceFormatArr[$screenArr['voice_code']];
                foreach ($voiceFormat as $vv) {
                    if ($vv == 'car_number' && $screenArr['car_number']) {
                        $string .= $screenArr['car_number'];
                    } elseif ($vv == 'end_time' && $screenArr['end_time'] > 100) {
                        $string .= date('Y-m-d', $screenArr['end_time']);
                    } elseif ($vv == 'duration' && $screenArr['duration'] > 0) {
                        $string .= $this->timeToChinese($screenArr['duration']);
                    } elseif ($vv == 'price' && $screenArr['price'] > 0) {
                        $price = round($screenArr['price'], 2);
                        $string .= $price;
                        //$string .= str_replace('.', '点', $price);
                    } else {
                        $string .= $vv;
                    }
                }
            } elseif (isset($screenArr['content']) && $screenArr['content']) {
                if (isset($screenArr['car_number']) && $screenArr['car_number']) {
                    $string .= $screenArr['car_number'];
                }
                $string .= $screenArr['content'];
            }
            if ($string) {
                $content_data_voice = $this->strTo16Gbk($string);
            }
        }
        return $content_data_voice;
    }

    public function timeToChinese($date)
    {
        $chineseDate = '';
        if ($date && $date > 0) {
            // 把数字转换成中文
            $chineseArr = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
            // 十位数对应的中文
            $chineseTenArr = array('', '十', '二十', '三十', '四十', '五十');
            // 获取天数
            $day = intval($date / 86400);
            // 获取小时
            $hour = intval(($date - 86400 * $day) / 3600);
            // 获取分钟
            $minte = ceil(($date - 86400 * $day - $hour * 3600) / 60);

            if ($day > 0) {
                $chineseDate .= $day > 10 ? $day : '0' . $day;
                $chineseDate .= $chineseDate . '天';
            }
            if ($hour > 0) {
                $chineseDate .= $hour > 10 ? $hour : '0' . $hour;
                $chineseDate .= $chineseDate . '小时';
            }
            if ($minte > 0) {
                $chineseDate .= $minte > 10 ? $minte : '0' . $minte;
                $chineseDate .= $chineseDate . '分钟';
            }
        }
        return $chineseDate;
    }

    /**
     * 车辆出场
     * @author:zhubaodi
     * @date_time: 2022/3/17 11:52
     */
    public function out_park($car_number = '', $data = array(), $village_info = array(), $passage_info = array(), $param = [])
    {
        $outArrRet = array('err_code' => 0, 'err_msg' => '', 'is_ok' => false);
        if (isset($param['parkTime']) && $param['parkTime']) {
            $parkTime = $param['parkTime'];
        } else {
            $parkTime = date('YmdH');
        }
        $serialno = '';
        if (isset($param['serialno']) && $param['serialno']) {
            $serialno = trim($param['serialno']);
        }
        $garage_id = 0;
        if ($passage_info && isset($passage_info['garage_id']) && $passage_info['garage_id'] > 0) {
            $garage_id = $passage_info['garage_id'];
        } else if ($passage_info && isset($passage_info['passage_area']) && !empty($passage_info['passage_area']) && $passage_info['area_type'] == 2) {
            $garage_id = intval($passage_info['passage_area']);
        }
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_park_free = new HouseVillageParkFree();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_park_plateresult_log = new ParkPlateresultLog();
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
        $db_house_village_park_config = new HouseVillageParkConfig();
        $db_park_passage = new ParkPassage();
        $db_house_village_visitor = new HouseVillageVisitor();
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        if (!empty(cache('config_' . $passage_info['village_id'] . '_' . $garage_id))) {
            $park_config = cache('config_' . $passage_info['village_id'] . '_' . $garage_id);
        } else {
            $park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id, $passage_info['village_id']);
            cache('config_' . $passage_info['village_id'] . '_' . $garage_id, $park_config);
        }
        $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'HXT85';
        $showscreen_data = [];
        $showscreen_data['passage'] = $passage_info;
        $showscreen_data['village_id'] = $passage_info['village_id'];
        $showscreen_data['car_number'] = $car_number;
        $showscreen_data['channel_id'] = $serialno;
        $showscreen_data['car_type'] = '';
        $showscreen_data['voice_content'] = 'v2';
        $showscreen_data['content'] = '一路平安';
        $now_time = time();
        $park_log_data = [];
        $park_log_data['car_number'] = $car_number;
        $park_log_data['channel_id'] = $serialno;
        $park_log_data['park_type'] = 2;
        $park_log_data['village_id'] = $passage_info['village_id'];
        $park_log_data['add_time'] = $now_time;
        $logId = $db_park_plateresult_log->add($park_log_data);
        $whereInRecord = ['park_id' => $passage_info['village_id'], 'car_number' => $car_number, 'is_out' => 0, 'park_sys_type' => $park_sys_type, 'del_time' => 0];
        $in_park_info = $db_in_park->getOne1($whereInRecord);
        if ($in_park_info && !$in_park_info->isEmpty()) {
            $in_park_info = $in_park_info->toArray();
        } else {
            $in_park_info = array();
        }

        fdump_api(['添加识别记录' => $car_number, 'park_log_data' => $park_log_data, 'showscreen_data' => $showscreen_data, 'in_park_info' => $in_park_info], 'hxPark/plateresult' . $parkTime, true);

        $rand_num = date('Ymd', $now_time);// 换成日期存储

        $path = '/upload/park_hx/' . $rand_num . '/' . $serialno . '/';
        $up_dir = $_SERVER['DOCUMENT_ROOT'] . $path;
        $park_data = [];
        $park_data['out_time'] = $now_time;
        $park_data['is_out'] = 1;
        $park_data['is_paid'] = 1;
        // 记录车辆进入信息
        $car_access_record = [];
        $ymdhis = date('Ymdhis', $now_time);
        $imageToJob = [];
        $isImageToJob = false;
        if (isset($data['picture']) && !empty($data['picture'])) {
            $imageFile = trim($data['picture']);
            $imageFile = str_replace(array('-', '_', '.'), array('+', '/', '='), $imageFile);
            $savePathFile = $ymdhis . '_image_big_' . $car_number;
            $isImageToJob = true;
            $imageToJob['park_type'] = 'out_park';
            $imageToJob['serialno'] = $serialno;
            $imageToJob['car_number'] = $car_number;
            $imageToJob['out_time'] = $now_time;
            $imageToJob['village_id'] = $passage_info['village_id'];
            $imageToJob['nowTime'] = $now_time;
            $imageToJob['imageSavePath'] = $up_dir;
            $imageToJob['imageFile'] = $imageFile;
            $imageToJob['imageFileSaveFileName'] = $savePathFile;
            if (isset($data['from_xtype'])) {
                $imageToJob['from_xtype'] = $data['from_xtype'];
            }
            fdump_api(['车辆相关信息-1图片' => $car_number], 'hxPark/plateresult' . $parkTime, true);
            unset($data['picture']);
        }
        if (isset($data['closeup_pic']) && !empty($data['closeup_pic'])) {
            $imageFragmentFile = trim($data['closeup_pic']);
            $imageFragmentFile = str_replace(array('-', '_', '.'), array('+', '/', '='), $imageFragmentFile);
            $savePathFragmentFile = $ymdhis . '_image_small_' . $car_number;
            if (!$isImageToJob) {
                $isImageToJob = true;
                $imageToJob['park_type'] = 'out_park';
                $imageToJob['serialno'] = $serialno;
                $imageToJob['car_number'] = $car_number;
                $imageToJob['out_time'] = $now_time;
                $imageToJob['village_id'] = $passage_info['village_id'];
                $imageToJob['nowTime'] = $now_time;
                $imageToJob['imageSavePath'] = $up_dir;
            }
            $imageToJob['imageFragmentFile'] = $imageFragmentFile;
            $imageToJob['imageFragmentFileSaveFileName'] = $savePathFragmentFile;
            if (isset($data['from_xtype'])) {
                $imageToJob['from_xtype'] = $data['from_xtype'];
            }
            fdump_api(['车辆相关信息-2图片' => $car_number], 'hxPark/plateresult' . $parkTime, true);
            unset($data['closeup_pic']);
        }
        $imageBigUrlPath = '';
        $imageSmallUrlPath = '';
        $car_access_record['channel_id'] = $passage_info['id'];
        $car_access_record['channel_number'] = $passage_info['channel_number'];
        $car_access_record['channel_name'] = $passage_info['passage_name'];
        /*
        if (isset($data['imagePath']) && $data['imagePath']) {
            $imageUrlPathTmp = base64_decode($data['AlarmInfoPlate']['result']['PlateResult']['imagePath']);
            if ($imageUrlPathTmp && (strpos($imageUrlPathTmp, 'ttp://') || strpos($imageUrlPathTmp, 'ttps://'))) {
                $imageUrlPathArr = explode('?', $imageUrlPathTmp);
                $imageBigUrlPath = $imageUrlPathArr['0'];
                $imageBigUrlPath = str_replace('http:', 'https:', $imageBigUrlPath);
                $car_access_record['accessBigImage'] = $imageBigUrlPath;
                $park_data['out_image_big'] = $imageBigUrlPath;
            }
        }
        if (isset($data['imageFragmentPath']) && $data['imageFragmentPath']) {
            $imageUrlPathTmp = base64_decode($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentPath']);
            if ($imageUrlPathTmp && (strpos($imageUrlPathTmp, 'ttp://') || strpos($imageUrlPathTmp, 'ttps://'))) {
                $imageUrlPathArr = explode('?', $imageUrlPathTmp);
                $imageSmallUrlPath = $imageUrlPathArr['0'];
                $imageSmallUrlPath = str_replace('http:', 'https:', $imageSmallUrlPath);
                $car_access_record['accessImage'] = $imageSmallUrlPath;
                $park_data['out_image_small'] = $imageSmallUrlPath;
            }
        }
        */
        $out_data = [];
        $out_data['car_number'] = $car_number;
        $out_park_info = [];
        $out_time = $now_time;
        if (!empty($in_park_info)) {
            if ($in_park_info['in_time'] > ($now_time - 60)) {
                // 如果进入记录在一分钟内  避免由于识别到车辆尾部导致的意外开闸 直接反馈不允许开闸
                $outArrRet['err_code'] = 1;
                $outArrRet['err_msg'] = '不允许重复开闸';
                $outArrRet['is_ok'] = false;
                return $outArrRet;
            }
            /*
            $out_data['in_time'] = $in_park_info['in_time'];
            $out_data['order_id'] = isset($in_park_info['order_id']) && $in_park_info['order_id'] ? $in_park_info['order_id'] : '';
            */
        } else {
            $whereInOut = ['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 1, 'del_time' => 0];
            $out_park_info = $db_in_park->getOne1($whereInOut);
            if ($out_park_info && !$out_park_info->isEmpty()) {
                $out_park_info = $out_park_info->toArray();
            }
            $out_time = $now_time - $park_config['out_park_time'] * 60;
        }
        /*
        $out_data['out_time'] = $now_time;
        $out_data['park_id'] = $passage_info['village_id'];
        $out_data['park_sys_type'] = $park_sys_type;
        */
        //查询免费车
        if (!empty(cache('freeList_' . $passage_info['village_id']))) {
            $free_list = cache('freeList_' . $passage_info['village_id']);
        } else {
            $free_list_obj = $db_house_village_park_free->getList(['village_id' => $passage_info['village_id']]);
            $free_list = '';
            if ($free_list_obj && !$free_list_obj->isEmpty()) {
                $free_list = $free_list_obj->toArray();
            }
            cache('freeList_' . $passage_info['village_id'], $free_list, 3600);
        }
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        if ($isImageToJob) {
            $imageToJob['in_park_id'] = isset($in_park_info['id']) ? intval($in_park_info['id']) : 0;
            $imageToJob['in_time'] = isset($in_park_info['in_time']) ? intval($in_park_info['in_time']) : 0;
            $imageToJob['order_id'] = isset($in_park_info['order_id']) ? trim($in_park_info['order_id']) : '';
            $imageToJob['car_number_no'] = $car_no;
        }
        $carCachekey = 'village:' . $passage_info['village_id'] . ':car:' . $car_number;
        if (!empty(cache($carCachekey))) {
            $car_info = cache($carCachekey);
            if (!isset($car_info['end_time']) || $car_info['end_time'] <= $now_time) {
                // 缓存中取不出来车辆信息或者截止时间过期了 避免出现问题 及时查询下最新的信息并缓存
                $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
                if ($car_info && !$car_info->isEmpty()) {
                    $car_info = $car_info->toArray();
                    cache($carCachekey, $car_info, 3600);
                } else {
                    cache($carCachekey, null);
                }
            }
        } else {
            $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
            if ($car_info && !$car_info->isEmpty()) {
                $car_info = $car_info->toArray();
                cache($carCachekey, $car_info, 3600);
            } else {
                cache($carCachekey, null);
            }
        }

        $user_name = '';
        $user_phone = '';
        $car_access_record['garage_id'] = $garage_id;
        $outRightPosition = array();
        if ($car_info && isset($car_info['car_id'])) {
            $outRightPosition = $this->getCarRightOutPosition($car_info, $passage_info['village_id'], $garage_id, $car_number);
            fdump_api(['car_info' => $car_info, 'rightPosition' => $outRightPosition], 'hxPark/plateresult' . $parkTime, 1);
            if (!empty($outRightPosition)) {
                if ($outRightPosition['start_time'] <= $now_time && $outRightPosition['end_time'] >= $car_info['end_time']) {
                    $car_info['end_time'] = $outRightPosition['end_time'];
                    cache($carCachekey, $car_info, 3600);
                    if (isset($outRightPosition['temporary_end_time']) && $outRightPosition['temporary_end_time'] > 0) {
                        $car_info['end_time'] = $outRightPosition['temporary_end_time'];
                        $car_info['temporary_end_time'] = $outRightPosition['temporary_end_time'];
                    }
                }
                if ($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids']) && $outRightPosition['start_time'] > $now_time) {
                    //车位时间还没开始
                    $car_info['end_time'] = 0;
                }

                if (isset($outRightPosition['position_id']) && $outRightPosition['position_id']) {
                    $car_access_record['position_id'] = $outRightPosition['position_id'];
                }
                if (isset($outRightPosition['garage_id']) && $outRightPosition['garage_id']) {
                    $car_access_record['garage_id'] = $outRightPosition['garage_id'];
                }
                if (isset($outRightPosition['position_num']) && $outRightPosition['position_num']) {
                    $car_access_record['position_num'] = $outRightPosition['position_num'];
                }
            } else {
                $car_info['end_time'] = 0;
                /*
                //没有可用车位
                $bind_position_ids=array();
                if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])){
                    //新的绑定车位 多车多位逻辑
                    $bind_position_ids=explode(',',$car_info['bind_position_ids']);
                    $car_info['end_time']=0;   //没有可用车位时
                }
                if(isset($car_info['garage_id']) && $car_info['garage_id']>0 &&  $car_info['garage_id']!=$garage_id){
                    //进入的不是同一个车库
                    $car_info['end_time']=0;
                }
                */
            }
            $car_access_record['car_id'] = $car_info['car_id'];
            $user_name = empty($car_info['car_user_name']) ? '' : $car_info['car_user_name'];
            $user_phone = empty($car_info['car_user_phone']) ? '' : $car_info['car_user_phone'];
        }

        if ($parkTime) {
            fdump_api(['查询车辆到期时间' => $car_number, 'province' => $province, 'car_no' => $car_no, 'car_info' => $car_info, 'in_park_info' => $in_park_info], 'hxPark/plateresult' . $parkTime, true);
        }
        $car_visitor_cache = 'village:' . $passage_info['village_id'] . ':car_visitor:' . $car_number;
        $visitor_info = [];
        if (!$user_name && !$user_phone && !empty(cache($car_visitor_cache))) {
            $visitor_info = cache($car_visitor_cache);
            $user_name = empty($visitor_info['visitor_name']) ? '' : $visitor_info['visitor_name'];
            $user_phone = empty($visitor_info['visitor_phone']) ? '' : $visitor_info['visitor_phone'];
            $uid = empty($visitor_info['uid']) ? '' : $visitor_info['uid'];
        } elseif (!$user_name && !$user_phone) {
            $visitor_info = $db_house_village_visitor->get_one(['village_id' => $passage_info['village_id'], 'car_id' => $car_number], 'id, visitor_name, visitor_phone, uid');
            cache($car_visitor_cache, $visitor_info);
            $user_name = empty($visitor_info['visitor_name']) ? '' : $visitor_info['visitor_name'];
            $user_phone = empty($visitor_info['visitor_phone']) ? '' : $visitor_info['visitor_phone'];
            $uid = empty($visitor_info['uid']) ? '' : $visitor_info['uid'];
        }
        if ($user_name) {
            $car_access_record['user_name'] = $user_name;
        }
        if ($user_phone) {
            $car_access_record['user_phone'] = $user_phone;
        }
        if (isset($uid) && $uid) {
            $car_access_record['uid'] = $uid;
        }
        $starttime = $now_time - 100;
        $endtime = $now_time + 10;
        $park_out_where = [['car_number', '=', $car_number], ['park_id', '=', $passage_info['village_id']], ['out_time', '>=', $starttime], ['out_time', '<=', $endtime], ['del_time', '<', 1],];
        $access_record_where = [['car_number', '=', $car_number], ['park_id', '=', $passage_info['village_id']], ['accessType', '=', 2], ['accessTime', '>=', $starttime], ['accessTime', '<=', $endtime], ['del_time', '<', 1],];
        $record_id = 0;
        if (!empty($free_list)) {
            fdump_api(['car_number' => $car_number, 'free_list' => $free_list], 'hxPark/plateresult' . $parkTime, true);
            $isFreePark = false;
            foreach ($free_list as $v) {
                $first_number = substr($car_number, 0, strlen($v['first_name']));
                $last_name = substr($car_number, '-' . strlen($v['last_name']));
                $isFreePark = false;
                if (!empty($v['first_name']) && $first_number == $v['first_name']) {
                    $isFreePark = true;
                } elseif (!empty($v['last_name']) && $last_name == $v['last_name']) {
                    $isFreePark = true;
                } elseif ($car_number == $v['free_park']) {
                    $isFreePark = true;
                }
                if ($isFreePark) {
                    fdump_api(['免费车' => $car_number, 'fv' => $v], 'hxPark/plateresult' . $parkTime, true);
                    break;
                }
            }
            fdump_api(['car_number' => $car_number, 'isFreePark' => $isFreePark], 'hxPark/plateresult' . $parkTime, true);
            if ($isFreePark) {
                $insert_id = 0;
                if ($in_park_info && isset($in_park_info['id']) && $in_park_info['id']) {
                    $insert_id = $in_park_info['id'];
                    $db_in_park->saveOne(['id' => $in_park_info['id'], 'car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'is_out' => 0], $park_data);
                }

                $record_id = 0;
                $car_access_order_id = '';
                if ($in_park_info && isset($in_park_info['access_order_id']) && $in_park_info['access_order_id']) {
                    $car_access_order_id = $in_park_info['access_order_id'];
                }
                if ($out_park_info && isset($out_park_info['id'])) {
                    $access_record_info = $db_house_village_car_access_record->getOne($access_record_where);
                    if ($access_record_info && !$access_record_info->isEmpty()) {
                        $record_id = isset($access_record_info['record_id']) ? intval($access_record_info['record_id']) : 0;
                        $car_access_order_id = isset($access_record_info['order_id']) ? trim($access_record_info['order_id']) : '';
                    }
                }
                //写入车辆出场表
                if ($record_id < 1) {

                    $park_time_tmp = 0;
                    if ($in_park_info && isset($in_park_info['in_time'])) {
                        $park_time_tmp = $now_time - $in_park_info['in_time'];
                    }
                    $car_access_record['business_type'] = 0;
                    $car_access_record['business_id'] = $passage_info['village_id'];
                    $car_access_record['car_number'] = $car_number;
                    $car_access_record['accessType'] = 2;
                    $car_access_record['accessTime'] = $now_time;
                    $car_access_record['park_time'] = $park_time_tmp;
                    $car_access_record['accessMode'] = '3';
                    $car_access_record['park_sys_type'] = $park_sys_type;
                    $car_access_record['is_out'] = 1;
                    $car_access_record['park_id'] = $passage_info['village_id'];
                    $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                    //$car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                    $car_access_record['order_id'] = $car_access_order_id;
                    $car_access_record['total'] = 0;
                    // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                    // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                    //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                    $car_access_record['pay_type'] = $this->pay_type[5];
                    $car_access_record['update_time'] = $now_time;
                    $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                    if (isset($insert_id)) {
                        $car_access_record['from_id'] = $insert_id;
                    }
                    $car_access_record['pay_order_id'] = isset($in_park_info['pay_order_id']) && $in_park_info['pay_order_id'] ? $in_park_info['pay_order_id'] : '';
                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                    fdump_api(['记录出场' => $car_number, 'car_access_record' => $car_access_record, 'record_id' => $record_id], 'hxPark/plateresult' . $parkTime, true);

                    $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 2, 'access_time' => time());
                    (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                }
                //TODO:屏显和语音提示特殊车辆通行
                $showscreen_data['content'] = '免费车通行';

                fdump_api(['显屏' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

                if (!empty($outRightPosition)) {
                    //释放车位
                    $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                    $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                    $this->updateParkingPositionUse($whereArr, $saveArr, $car_number);
                }
                $this->addParkShowScreenLog($showscreen_data);
                //特殊车辆通行
                if ($isImageToJob && ($record_id || $insert_id)) {
                    if (isset($car_access_order_id) && $car_access_order_id) {
                        $imageToJob['car_access_order_id'] = $car_access_order_id;
                    }
                    $imageToJob['out_record_id'] = $record_id;
                    (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                }

                $outArrRet['err_code'] = 0;
                $outArrRet['err_msg'] = '正常通行';
                $outArrRet['is_ok'] = true;
                return $outArrRet;
            }
        }

        //查询车辆到期时间
        fdump_api(['car_info' => $car_info, 'in_park_info' => $in_park_info, 'out_park_info' => $out_park_info], 'hxPark/plateresult' . $parkTime, 1);

        if (!empty($car_info) && $car_info['end_time'] > $now_time) {
            if (empty($in_park_info) && !empty($out_park_info) && $out_park_info['out_time'] > $out_time) {
                // 重复离场-判断下是否需要新增记录
                //TODO:屏显和语音提示车辆通行
                $showscreen_data['car_type'] = 'month_type';
                $showscreen_data['end_time'] = $car_info['end_time'];
                $showscreen_data['end_time_txt'] = date('Y-m-d', $car_info['end_time']);

                fdump_api(['屏显和语音提示车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

                if (!empty($outRightPosition)) {
                    //释放车位
                    $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                    $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => 0);
                    $this->updateParkingPositionUse($whereArr, $saveArr, $car_number);
                }
                $this->addParkShowScreenLog($showscreen_data);
                //车辆通行
                $outArrRet['err_code'] = 0;
                $outArrRet['err_msg'] = '正常通行';
                $outArrRet['is_ok'] = true;
                return $outArrRet;
            }
            $insert_id = 0;
            if ($in_park_info && isset($in_park_info['id']) && $in_park_info['id']) {
                $insert_id = $in_park_info['id'];
                $db_in_park->saveOne(['id' => $in_park_info['id'], 'car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'is_out' => 0], $park_data);
            }

            $record_id = 0;
            $car_access_order_id = '';
            if ($in_park_info && isset($in_park_info['access_order_id']) && $in_park_info['access_order_id']) {
                $car_access_order_id = $in_park_info['access_order_id'];
            }
            if ($out_park_info && isset($out_park_info['id'])) {
                $access_record_info = $db_house_village_car_access_record->getOne($access_record_where);
                if ($access_record_info && !$access_record_info->isEmpty()) {
                    fdump_api(['查询是否有对应出场车辆' => $car_number, 'access_record_where' => $access_record_where], 'hxPark/plateresult' . $parkTime, true);
                    $record_id = isset($access_record_info['record_id']) ? intval($access_record_info['record_id']) : 0;
                    $car_access_order_id = isset($access_record_info['order_id']) ? trim($access_record_info['order_id']) : '';
                }
            }
            //写入车辆出场表
            if ($record_id < 1) {
                $park_time_tmp = 0;
                if ($in_park_info && isset($in_park_info['in_time'])) {
                    $park_time_tmp = $now_time - $in_park_info['in_time'];
                }

                fdump_api(['car_number' => $car_number, 'in_park_info' => $in_park_info], 'hxPark/plateresult' . $parkTime, true);

                $car_access_record['business_type'] = 0;
                $car_access_record['business_id'] = $passage_info['village_id'];
                $car_access_record['car_number'] = $car_number;
                $car_access_record['accessType'] = 2;
                $car_access_record['accessTime'] = $now_time;
                $car_access_record['park_time'] = $park_time_tmp;
                $car_access_record['accessMode'] = 5;
                $car_access_record['park_sys_type'] = $park_sys_type;
                $car_access_record['is_out'] = 1;
                $car_access_record['park_id'] = $passage_info['village_id'];
                $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                // $car_access_order_id = $park_info_car111['order_id'];
                $car_access_record['order_id'] = $car_access_order_id;
                $car_access_record['total'] = 0;
                // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                $car_access_record['pay_type'] = $this->pay_type[4];
                $car_access_record['update_time'] = $now_time;
                $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                if (isset($insert_id)) {
                    $car_access_record['from_id'] = $insert_id;
                }
                $car_access_record['pay_order_id'] = isset($in_park_info['pay_order_id']) && $in_park_info['pay_order_id'] ? $in_park_info['pay_order_id'] : '';
                $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                fdump_api(['记录出场记录' => $car_number, 'car_access_record' => $car_access_record, 'record_id' => $record_id], 'hxPark/plateresult' . $parkTime, true);

                $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 2, 'access_time' => time());
                (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
            }
            //TODO:屏显和语音提示车辆通行
            $showscreen_data['voice_content'] = 'v4';
            $showscreen_data['content'] = '';
            $showscreen_data['car_type'] = 'month_type';
            $showscreen_data['end_time'] = $car_info['end_time'];
            $showscreen_data['end_time_txt'] = date('Y-m-d', $car_info['end_time']);

            fdump_api(['屏显和语音提示车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

            if (!empty($outRightPosition)) {
                //释放车位
                $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                $this->updateParkingPositionUse($whereArr, $saveArr, $car_number);
            }
            $this->addParkShowScreenLog($showscreen_data);
            //车辆通行
            if ($isImageToJob && ($record_id || $insert_id)) {
                if (isset($car_access_order_id) && $car_access_order_id) {
                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                }
                $imageToJob['out_record_id'] = $record_id;
                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
            }
            $outArrRet['err_code'] = 0;
            $outArrRet['err_msg'] = '正常通行';
            $outArrRet['is_ok'] = true;
            return $outArrRet;
        } else {
            if (empty($in_park_info) && !empty($out_park_info) && $out_park_info['out_time'] > $out_time) {
                // 无入场记录-重复离场
                //车辆通行
                $showscreen_data['car_type'] = 'temporary_type';
                $showscreen_data['duration_txt'] = 0;
                $showscreen_data['duration'] = 0;
                $showscreen_data['price'] = 0;

                fdump_api(['车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

                if (!empty($outRightPosition)) {
                    //释放车位
                    $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                    $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => 0);
                    $this->updateParkingPositionUse($whereArr, $saveArr, $car_number);
                }
                $this->addParkShowScreenLog($showscreen_data);
                $outArrRet['err_code'] = 0;
                $outArrRet['err_msg'] = '正常通行';
                $outArrRet['is_ok'] = true;
                return $outArrRet;
            } elseif (empty($in_park_info)) {
                //无入场纪录，根据车牌推送信息计算停车费用
                $in_park_plateresult_info_obj = $db_park_plateresult_log->get_one(['car_number' => $car_number, 'park_type' => 1]);
                $in_park_plateresult_info = '';
                if ($in_park_plateresult_info_obj && !$in_park_plateresult_info_obj->isEmpty()) {
                    $in_park_plateresult_info = $in_park_plateresult_info_obj->toArray();
                }
                fdump_api(['无入场纪录，根据车牌推送信息计算停车费用' => $car_number, 'in_park_plateresult_info' => $in_park_plateresult_info], 'hxPark/plateresult' . $parkTime, true);
                if (empty($in_park_plateresult_info)) {
                    // 无识别记录 以当前时间为准加一条入场记录
                    $in_passage_conut = $db_park_passage->getCount(['village_id' => $passage_info['village_id'], 'status' => 1, 'passage_direction' => 1]);
                    $in_car_access_record = [];
                    $in_park_data = [];
                    $in_park_data['out_time'] = $now_time;
                    $in_park_data['is_out'] = 1;
                    $in_park_data['is_paid'] = 1;
                    $in_park_data['car_number'] = $car_number;
                    $in_park_data['in_time'] = $now_time;
                    $in_park_data['order_id'] = uniqid();
                    if ($in_passage_conut == 1) {
                        $in_passage_info = $db_park_passage->getFind(['village_id' => $passage_info['village_id'], 'status' => 1, 'passage_direction' => 1]);
                        $in_park_data['in_channel_id'] = $in_passage_info['id'];
                        $in_car_access_record['channel_id'] = $in_passage_info['id'];
                        $in_car_access_record['channel_number'] = $in_passage_info['channel_number'];
                        $in_car_access_record['channel_name'] = $in_passage_info['passage_name'];
                    }
                    $in_park_data['park_id'] = $passage_info['village_id'];
                    $in_park_data['park_sys_type'] = $park_sys_type;
                    $in_park_data['park_name'] = $village_info['village_name'];
                    $in_park_data['out_image_big'] = $imageBigUrlPath;
                    $in_park_data['out_image_small'] = $imageSmallUrlPath;
                    $insert_id = $db_in_park->insertOne($in_park_data);
                    if ($isImageToJob) {
                        $imageToJob['in_park_id'] = $insert_id;
                        $imageToJob['in_time'] = isset($in_park_data['in_time']) ? intval($in_park_data['in_time']) : 0;
                        $imageToJob['order_id'] = isset($in_park_data['order_id']) ? trim($in_park_data['order_id']) : '';
                    }

                    fdump_api(['无入场纪录，根据车牌推送信息计算停车费用' => $car_number, 'in_park_data' => $in_park_data], 'hxPark/plateresult' . $parkTime, true);

                    $in_car_access_record['accessImage'] = $imageSmallUrlPath;
                    $in_car_access_record['accessBigImage'] = $imageBigUrlPath;
                    $in_car_access_record['business_type'] = 0;
                    $in_car_access_record['business_id'] = $passage_info['village_id'];
                    $in_car_access_record['car_number'] = $car_number;
                    $in_car_access_record['accessType'] = 1;
                    $in_car_access_record['accessTime'] = $now_time;
                    $in_car_access_record['accessMode'] = 9;
                    $in_car_access_record['park_sys_type'] = $park_sys_type;
                    $in_car_access_record['exception_type'] = 1;
                    $in_car_access_record['is_out'] = 1;
                    $in_car_access_record['park_id'] = $passage_info['village_id'];
                    $in_car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                    $car_access_order_id = date('YmdHis') . rand(100, 999);
                    $in_car_access_record['order_id'] = $car_access_order_id;
                    $in_car_access_record['update_time'] = $now_time;
                    if (isset($insert_id) && !empty($insert_id)) {
                        $in_car_access_record['from_id'] = $insert_id;
                    }
                    if (isset($car_access_record['user_name'])) {
                        $in_car_access_record['user_name'] = $car_access_record['user_name'];
                    }
                    if (isset($car_access_record['user_phone'])) {
                        $in_car_access_record['user_phone'] = $car_access_record['user_phone'];
                    }
                    $in_record_id = $db_house_village_car_access_record->addOne($in_car_access_record);
                    if ($isImageToJob) {
                        $imageToJob['in_record_id'] = $in_record_id;
                    }

                    //写入车辆出场表
                    $record_id = 0;
                    if ($in_record_id > 0) {
                        $car_access_record['business_type'] = 0;
                        $car_access_record['business_id'] = $passage_info['village_id'];
                        $car_access_record['car_number'] = $car_number;
                        $car_access_record['accessType'] = 2;
                        $car_access_record['accessTime'] = $now_time;
                        $car_access_record['accessMode'] = 4;
                        $car_access_record['park_sys_type'] = $park_sys_type;
                        $car_access_record['is_out'] = 1;
                        $car_access_record['park_id'] = $passage_info['village_id'];
                        $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                        $car_access_record['order_id'] = $car_access_order_id;
                        $car_access_record['total'] = 0;
                        // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                        // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                        //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                        $car_access_record['pay_type'] = $this->pay_type[6];
                        $car_access_record['update_time'] = $now_time;
                        $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                        if (isset($insert_id)) {
                            $car_access_record['from_id'] = $insert_id;
                        }
                        $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                        fdump_api(['车辆通行记录' => $car_number, 'car_access_record' => $car_access_record, 'record_id' => $record_id], 'hxPark/plateresult' . $parkTime, true);

                    }
                    //TODO:屏显和语音提示车辆通行
                    //车辆通行
                    $showscreen_data['car_type'] = 'temporary_type';
                    $showscreen_data['duration_txt'] = '';
                    $showscreen_data['duration'] = 0;
                    $showscreen_data['price'] = 0;

                    fdump_api(['屏显和语音提示车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

                    if (!empty($outRightPosition)) {
                        //释放车位
                        $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                        $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                        $this->updateParkingPositionUse($whereArr, $saveArr, $car_number);
                    }
                    $this->addParkShowScreenLog($showscreen_data);
                    if ($isImageToJob && ($record_id || $insert_id)) {
                        if (isset($car_access_order_id) && $car_access_order_id) {
                            $imageToJob['car_access_order_id'] = $car_access_order_id;
                        }
                        $imageToJob['out_record_id'] = $record_id;
                        (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                    }
                    $outArrRet['err_code'] = 0;
                    $outArrRet['err_msg'] = '正常通行';
                    $outArrRet['is_ok'] = true;
                    return $outArrRet;
                } else {
                    // 取车辆识别入场通道信息
                    $in_passage_info = $db_park_passage->getFind(['village_id' => $passage_info['village_id'], 'device_number' => $in_park_plateresult_info['channel_id']]);
                    if (empty($in_passage_info)) {
                        //无车道信息
                        $in_passage_conut = $db_park_passage->getCount(['village_id' => $passage_info['village_id'], 'status' => 1, 'passage_direction' => 1]);
                        $in_car_access_record = [];
                        $in_park_data = [];
                        $in_park_data['out_time'] = $now_time;
                        $in_park_data['is_out'] = 1;
                        $in_park_data['is_paid'] = 1;
                        $in_park_data['car_number'] = $car_number;
                        $in_park_data['in_time'] = $now_time;
                        $in_park_data['order_id'] = uniqid();
                        if ($in_passage_conut == 1) {
                            $in_passage_info = $db_park_passage->getFind(['village_id' => $passage_info['village_id'], 'status' => 1, 'passage_direction' => 1]);
                            $in_park_data['in_channel_id'] = $in_passage_info['id'];
                            $in_car_access_record['channel_id'] = $in_passage_info['id'];
                            $in_car_access_record['channel_number'] = $in_passage_info['channel_number'];
                            $in_car_access_record['channel_name'] = $in_passage_info['passage_name'];
                        }
                        $in_park_data['park_id'] = $passage_info['village_id'];
                        $in_park_data['park_sys_type'] = $park_sys_type;
                        $in_park_data['park_name'] = $village_info['village_name'];
                        $in_park_data['out_image_big'] = $imageBigUrlPath;
                        $in_park_data['out_image_small'] = $imageSmallUrlPath;
                        $insert_id = $db_in_park->insertOne($in_park_data);
                        $insert_id && $in_park_data['id'] = $insert_id;
                        if ($isImageToJob) {
                            $imageToJob['in_park_id'] = $insert_id;
                            $imageToJob['in_time'] = isset($in_park_data['in_time']) ? intval($in_park_data['in_time']) : 0;
                            $imageToJob['order_id'] = isset($in_park_data['order_id']) ? trim($in_park_data['order_id']) : '';
                        }

                        fdump_api(['无入场纪录，根据车牌推送信息计算停车费用' => $car_number, 'in_park_data' => $in_park_data], 'hxPark/plateresult' . $parkTime, true);

                        $in_car_access_record['accessImage'] = $imageSmallUrlPath;
                        $in_car_access_record['accessBigImage'] = $imageBigUrlPath;
                        $in_car_access_record['business_type'] = 0;
                        $in_car_access_record['business_id'] = $passage_info['village_id'];
                        $in_car_access_record['car_number'] = $car_number;
                        $in_car_access_record['accessType'] = 1;
                        $in_car_access_record['accessTime'] = $now_time;
                        $in_car_access_record['accessMode'] = 9;
                        $in_car_access_record['park_sys_type'] = 'D3';
                        $in_car_access_record['exception_type'] = 1;
                        $in_car_access_record['is_out'] = 1;
                        $in_car_access_record['park_id'] = $passage_info['village_id'];
                        $in_car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                        $car_access_order_id = date('YmdHis') . rand(100, 999);
                        $in_car_access_record['order_id'] = $car_access_order_id;
                        $in_car_access_record['update_time'] = $now_time;
                        if (isset($insert_id) && !empty($insert_id)) {
                            $in_car_access_record['from_id'] = $insert_id;
                        }
                        if (isset($car_access_record['user_name'])) {
                            $in_car_access_record['user_name'] = $car_access_record['user_name'];
                        }
                        if (isset($car_access_record['user_phone'])) {
                            $in_car_access_record['user_phone'] = $car_access_record['user_phone'];
                        }
                        $in_record_id = $db_house_village_car_access_record->addOne($in_car_access_record);
                        if ($isImageToJob) {
                            $imageToJob['in_record_id'] = $in_record_id;
                        }

                        $record_id = 0;
                        //写入车辆出场表
                        if ($in_record_id > 0) {
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $car_number;
                            $car_access_record['accessType'] = 2;
                            $car_access_record['accessTime'] = $now_time;
                            $car_access_record['accessMode'] = 4;
                            $car_access_record['park_sys_type'] = $park_sys_type;
                            $car_access_record['is_out'] = 1;
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                            $car_access_record['order_id'] = $car_access_order_id;
                            $car_access_record['total'] = 0;
                            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                            $car_access_record['pay_type'] = $this->pay_type[6];
                            $car_access_record['update_time'] = $now_time;
                            $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                            if (isset($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }
                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                            fdump_api(['车辆通行记录' => $car_number, 'car_access_record' => $car_access_record, 'record_id' => $record_id], 'hxPark/plateresult' . $parkTime, true);

                        }
                        //TODO:屏显和语音提示车辆通行
                        //车辆通行
                        $showscreen_data['car_type'] = 'temporary_type';
                        $showscreen_data['duration_txt'] = '';
                        $showscreen_data['duration'] = 0;
                        $showscreen_data['price'] = 0;

                        fdump_api(['屏显和语音提示车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

                        if (!empty($outRightPosition)) {
                            //释放车位
                            $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                            $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                            $this->updateParkingPositionUse($whereArr, $saveArr, $car_number);
                        }
                        $this->addParkShowScreenLog($showscreen_data);
                        if ($isImageToJob && ($record_id || $insert_id)) {
                            if (isset($car_access_order_id) && $car_access_order_id) {
                                $imageToJob['car_access_order_id'] = $car_access_order_id;
                            }
                            $imageToJob['out_record_id'] = $record_id;
                            (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                        }
                        $outArrRet['err_code'] = 0;
                        $outArrRet['err_msg'] = '正常通行';
                        $outArrRet['is_ok'] = true;
                        return $outArrRet;
                    } else {
                        $whereInOut = ['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'del_time' => 0];
                        $out_park_info = $db_in_park->getOne1($whereInOut);
                        $limit_out_time = isset($out_park_info['out_time']) && intval($out_park_info['out_time']) > 1 ? intval($out_park_info['out_time']) : 0;
                        $limit_in_time = isset($out_park_info['in_time']) && intval($out_park_info['in_time']) > 1 ? intval($out_park_info['in_time']) : 0;
                        $plateresult_time = isset($in_park_plateresult_info['add_time']) && intval($in_park_plateresult_info['add_time']) > 1 ? intval($in_park_plateresult_info['add_time']) : 0;
                        $access_in_time = 0;
                        if ($limit_out_time > 100 && $limit_out_time > $plateresult_time) {
                            $access_in_time = $limit_out_time;
                        } elseif ($limit_in_time > 100 && $limit_in_time > $plateresult_time) {
                            $access_in_time = $limit_in_time;
                        } elseif ($plateresult_time > 100) {
                            $access_in_time = $plateresult_time;
                        }
                        if ($access_in_time > 0) {
                            $in_car_access_record = [];
                            $in_park_data = [];
                            $in_park_data['is_paid'] = 0;
                            $in_park_data['car_number'] = $car_number;
                            $in_park_data['in_time'] = $access_in_time;
                            $in_park_data['order_id'] = uniqid();
                            $in_passage_info = $db_park_passage->getFind(['village_id' => $passage_info['village_id'], 'device_number' => $in_park_plateresult_info['channel_id']]);
                            $in_park_data['in_channel_id'] = $in_passage_info['id'];
                            $in_car_access_record['channel_id'] = $in_passage_info['id'];
                            $in_car_access_record['channel_number'] = $in_passage_info['channel_number'];
                            $in_car_access_record['channel_name'] = $in_passage_info['passage_name'];
                            $in_park_data['out_image_big'] = $imageBigUrlPath;
                            $in_park_data['out_image_small'] = $imageSmallUrlPath;
                            $in_park_data['park_id'] = $passage_info['village_id'];
                            $in_park_data['park_sys_type'] = $park_sys_type;
                            $in_park_data['park_name'] = $village_info['village_name'];
                            $insert_id = $db_in_park->insertOne($in_park_data);
                            $insert_id && $in_park_data['id'] = $insert_id;
                            if ($isImageToJob) {
                                $imageToJob['in_park_id'] = $insert_id;
                                $imageToJob['in_time'] = isset($in_park_data['in_time']) ? intval($in_park_data['in_time']) : 0;
                                $imageToJob['order_id'] = isset($in_park_data['order_id']) ? trim($in_park_data['order_id']) : '';
                            }
                            fdump_api(['无入场纪录，根据车牌推送信息计算停车费用' => $car_number, 'in_park_data' => $in_park_data], 'hxPark/plateresult' . $parkTime, true);

                            $in_car_access_record['accessImage'] = $imageSmallUrlPath;
                            $in_car_access_record['accessBigImage'] = $imageBigUrlPath;
                            $in_car_access_record['business_type'] = 0;
                            $in_car_access_record['business_id'] = $passage_info['village_id'];
                            $in_car_access_record['car_number'] = $car_number;
                            $in_car_access_record['accessType'] = 1;
                            $in_car_access_record['accessTime'] = $access_in_time;
                            $in_car_access_record['accessMode'] = 9;
                            $in_car_access_record['park_sys_type'] = $park_sys_type;
                            $in_car_access_record['is_out'] = 0;
                            $in_car_access_record['park_id'] = $passage_info['village_id'];
                            $in_car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                            $car_access_order_id = date('YmdHis') . rand(100, 999);
                            $in_car_access_record['order_id'] = $car_access_order_id;
                            $in_car_access_record['update_time'] = $now_time;
                            $in_car_access_record['exception_type'] = 1;
                            if (isset($insert_id) && !empty($insert_id)) {
                                $in_car_access_record['from_id'] = $insert_id;
                            }
                            if (isset($car_access_record['user_name'])) {
                                $in_car_access_record['user_name'] = $car_access_record['user_name'];
                            }
                            if (isset($car_access_record['user_phone'])) {
                                $in_car_access_record['user_phone'] = $car_access_record['user_phone'];
                            }
                            fdump_api(['车辆通行记录前' => $car_number, 'in_car_access_record' => $in_car_access_record], 'hxPark/plateresult' . $parkTime, true);

                            $in_record_id = $db_house_village_car_access_record->addOne($in_car_access_record);
                            fdump_api(['车辆通行记录' => $car_number, 'in_record_id' => $in_record_id], 'hxPark/plateresult' . $parkTime, true);
                            if ($isImageToJob) {
                                $imageToJob['in_record_id'] = $in_record_id;
                                if (isset($car_access_order_id) && $car_access_order_id) {
                                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                                }
                            }

                            $in_park_info = $db_in_park->getOne(array('id' => $insert_id), '*');
                            if ($in_park_info && isset($in_park_info['access_order_id']) && empty($in_park_info['access_order_id'])) {
                                $saveArr = array('access_record_id' => $in_record_id, 'access_order_id' => $car_access_order_id);
                                $db_in_park->saveOne(array('id' => $insert_id), $saveArr);
                            }

                            fdump_api(['添加入场记录' => $car_number, 'in_record_id' => $in_record_id], 'hxPark/plateresult' . $parkTime, true);

                            $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $in_record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 1, 'access_time' => time());
                            (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                        }
                    }
                }
            }
            $pay_data = ['car_number' => $car_number, 'village_id' => $passage_info['village_id'], 'device_number' => $passage_info['device_number'], 'channel_number' => $passage_info['channel_number'], 'garage_id' => $garage_id, 'car_end_time' => 0, 'temporary_end_time' => 0, 'park_sys_type' => $park_sys_type,];
            if ($car_info && isset($car_info['end_time'])) {
                $pay_data['car_end_time'] = $car_info['end_time'];
            }
            if ($car_info && isset($car_info['temporary_end_time'])) {
                $pay_data['temporary_end_time'] = $car_info['temporary_end_time'];
            }
            if (isset($in_park_data) && !empty($in_park_data)) {
                $pay_data['in_park_data'] = $in_park_data;
            }
            $pay_money = $this->get_temp_pay($pay_data);
            $whereInRecord = ['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0, 'del_time' => 0];
            $in_park_pay_info = $db_in_park->getOne1($whereInRecord);
            if ($in_park_pay_info && !$in_park_pay_info->isEmpty()) {
                $in_park_pay_info = $in_park_pay_info->toArray();
            }

            fdump_api(['车辆通行' => $car_number, 'in_park_pay_info' => $in_park_pay_info], 'hxPark/plateresult' . $parkTime, true);

            if (!empty($in_park_pay_info)) {
                $out_image_save = array();
                if (isset($park_data['out_image_big'])) {
                    $out_image_save['out_image_big'] = $park_data['out_image_big'];
                }
                if (isset($park_data['out_image_small'])) {
                    $out_image_save['out_image_small'] = $park_data['out_image_small'];
                }
                if ($out_image_save) {
                    $db_in_park->saveOne(['id' => $in_park_pay_info['id'], 'car_number' => $car_number, 'park_id' => $passage_info['village_id']], $out_image_save);
                }
                $car_access_record['park_time'] = $pay_money['park_time'];
                $car_access_record['coupon_id'] = $pay_money['coupon_id'];
                fdump_api([$car_number, $car_info, $pay_money], 'hxPark/plateresult' . $parkTime, 1);

                if ($pay_money['pay_money'] == 0) {
                    $db_in_park->saveOne(['id' => $in_park_pay_info['id'], 'car_number' => $car_number, 'park_id' => $passage_info['village_id']], $park_data);
                    $insert_id = $in_park_pay_info['id'];
                    if ($isImageToJob) {
                        $imageToJob['in_park_id'] = $in_park_pay_info['id'];
                        $imageToJob['out_park_id'] = 0;
                        $imageToJob['in_time'] = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                        $imageToJob['order_id'] = isset($in_park_pay_info['order_id']) ? trim($in_park_pay_info['order_id']) : '';
                    }
                    fdump_api(['车辆通行' => $car_number, 'park_data' => $park_data], 'hxPark/plateresult' . $parkTime, true);

                    $car_access_order_id = isset($in_park_pay_info['access_order_id']) && $in_park_pay_info['access_order_id'] ? $in_park_pay_info['access_order_id'] : '';
                    //写入车辆出场表
                    $park_time_tmp = 0;
                    if ($in_park_pay_info && isset($in_park_pay_info['in_time'])) {
                        $park_time_tmp = $now_time - $in_park_pay_info['in_time'];
                    }
                    $car_access_record['business_type'] = 0;
                    $car_access_record['business_id'] = $passage_info['village_id'];
                    $car_access_record['car_number'] = $car_number;
                    $car_access_record['accessType'] = 2;
                    $car_access_record['accessTime'] = $now_time;
                    $car_access_record['park_time'] = $park_time_tmp;
                    $car_access_record['accessMode'] = 3;
                    $car_access_record['is_out'] = 1;
                    $car_access_record['park_sys_type'] = $park_sys_type;
                    $car_access_record['coupon_id'] = $pay_money['coupon_id'];
                    $car_access_record['park_id'] = $passage_info['village_id'];
                    $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                    $car_access_record['order_id'] = $car_access_order_id;
                    $car_access_record['total'] = $pay_money['pay_money'];
                    // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                    // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                    //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                    $car_access_record['pay_type'] = $this->pay_type[5];
                    if (isset($pay_money['is_advance_pay']) && $pay_money['is_advance_pay'] > 0 && isset($pay_money['parkTemp_money']) && $pay_money['parkTemp_money'] > 0) {
                        $car_access_record['total'] = $pay_money['parkTemp_money'];
                        $car_access_record['pay_type'] = $this->pay_type[6];
                    }
                    $car_access_record['update_time'] = $now_time;
                    $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                    if (isset($insert_id)) {
                        $car_access_record['from_id'] = $insert_id;
                    }
                    $car_access_record['pay_order_id'] = isset($in_park_pay_info['pay_order_id']) && $in_park_pay_info['pay_order_id'] ? $in_park_pay_info['pay_order_id'] : '';
                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                    fdump_api(['车辆通行记录' => $car_number, 'car_access_record' => $car_access_record, 'record_id' => $record_id], 'hxPark/plateresult' . $parkTime, true);

                    $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 2, 'access_time' => time());
                    (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                    /*
                        if($park_info_car111 && isset($park_info_car111['record_id'])){
                            $access_record_where_near = [
                                ['record_id', '=', $park_info_car111['record_id']],
                            ];
                            $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                        }
                        */

                    //TODO:屏显和语音提示车辆通行
                    $showscreen_data['car_type'] = 'temporary_type';
                    $showscreen_data['duration_txt'] = $pay_money['time'];
                    $showscreen_data['duration'] = $pay_money['park_time'];
                    $showscreen_data['price'] = 0;

                    fdump_api(['屏显和语音提示车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

                    if (!empty($outRightPosition)) {
                        //释放车位
                        $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                        $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                        $this->updateParkingPositionUse($whereArr, $saveArr, $car_number);
                    }
                    $this->addParkShowScreenLog($showscreen_data);
                    //车辆通行
                    if ($isImageToJob && ($record_id || $insert_id)) {
                        if (isset($car_access_order_id) && $car_access_order_id) {
                            $imageToJob['car_access_order_id'] = $car_access_order_id;
                        }
                        $imageToJob['out_record_id'] = $record_id;
                        (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                    }
                    $outArrRet['err_code'] = 0;
                    $outArrRet['err_msg'] = '正常通行';
                    $outArrRet['is_ok'] = true;
                    return $outArrRet;
                } elseif ($pay_money['pay_money'] > 0 && !empty($car_info) && $car_info['stored_balance'] > $pay_money['pay_money']) {
                    $db_in_park->saveOne(['id' => $in_park_pay_info['id'], 'car_number' => $car_number, 'park_id' => $passage_info['village_id']], $park_data);
                    $insert_id = $in_park_pay_info['id'];
                    if ($isImageToJob) {
                        $imageToJob['in_park_id'] = $in_park_pay_info['id'];
                        $imageToJob['out_park_id'] = 0;
                        $imageToJob['in_time'] = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                        $imageToJob['order_id'] = isset($in_park_pay_info['order_id']) ? trim($in_park_pay_info['order_id']) : '';
                    }

                    fdump_api(['车辆通行' => $car_number, 'park_data' => $park_data], 'hxPark/plateresult' . $parkTime, true);

                    //写入车辆出场表
                    $car_access_order_id = '';
                    if ($in_park_pay_info && isset($in_park_pay_info['access_order_id']) && $in_park_pay_info['access_order_id']) {
                        $car_access_order_id = $in_park_pay_info['access_order_id'];
                    }
                    $park_time_tmp = 0;
                    if ($in_park_pay_info && isset($in_park_pay_info['in_time'])) {
                        $park_time_tmp = $now_time - $in_park_pay_info['in_time'];
                    }
                    $car_access_record['business_type'] = 0;
                    $car_access_record['business_id'] = $passage_info['village_id'];
                    $car_access_record['car_number'] = $car_number;
                    $car_access_record['accessType'] = 2;
                    $car_access_record['accessTime'] = $now_time;
                    $car_access_record['park_time'] = $park_time_tmp;
                    $car_access_record['car_type'] = 'storedCar';
                    if (!isset($car_access_record['user_name']) || !$car_access_record['user_name']) {
                        $car_access_record['user_name'] = $car_info['car_user_name'];
                    }
                    if (!isset($car_access_record['user_phone']) || !$car_access_record['user_phone']) {
                        $car_access_record['user_phone'] = $car_info['car_user_phone'];
                    }
                    $car_access_record['park_sys_type'] = $park_sys_type;
                    $car_access_record['coupon_id'] = $pay_money['coupon_id'];
                    $car_access_record['park_id'] = $passage_info['village_id'];
                    $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                    $car_access_record['order_id'] = $car_access_order_id;
                    $car_access_record['total'] = $pay_money['pay_money'];
                    $car_access_record['is_paid'] = 1;
                    $car_access_record['pay_time'] = $now_time;
                    $car_access_record['stored_balance'] = $car_info['stored_balance'] - $pay_money['pay_money'];
                    // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                    // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                    //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                    $car_access_record['pay_type'] = $this->pay_type[1];
                    $car_access_record['update_time'] = $now_time;
                    $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                    if (isset($insert_id)) {
                        $car_access_record['from_id'] = $insert_id;
                    }
                    $car_access_record['pay_order_id'] = isset($in_park_pay_info['pay_order_id']) && $in_park_pay_info['pay_order_id'] ? $in_park_pay_info['pay_order_id'] : '';
                    $car_access_record['prepayTotal'] = isset($pay_money['parkTemp_money']) && $pay_money['parkTemp_money'] > 0 ? $pay_money['parkTemp_money'] : 0;
                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                    if ($record_id > 0) {
                        $db_house_village_parking_car->editHouseVillageParkingCar(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']], ['stored_balance' => $car_access_record['stored_balance']]);
                    }

                    $stored_balance = isset($car_info['stored_balance']) ? $car_info['stored_balance'] : 0;
                    $stored_balance1 = isset($car_access_record['stored_balance']) ? $car_access_record['stored_balance'] : 0;
                    $pay_money11 = isset($pay_money['pay_money']) ? $pay_money['pay_money'] : 0;
                    fdump_api(['对应车辆储值余额变动' => $car_number, 'car_access_record' => $car_access_record, 'record_id' => $record_id, 'stored_balance' => $stored_balance, 'pay_money' => $pay_money11, 'stored_balance1' => $stored_balance1], 'hxPark/plateresult' . $parkTime, 1);

                    $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 2, 'access_time' => time());
                    (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                    /*
                        if($park_info_car111 && isset($park_info_car111['record_id'])){
                            $access_record_where_near = [
                                ['record_id', '=', $park_info_car111['record_id']],
                            ];
                            $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                        }
                        */

                    //TODO:屏显和语音提示车辆通行
                    $showscreen_data['car_type'] = 'temporary_type';
                    $showscreen_data['duration_txt'] = $pay_money['time'];
                    $showscreen_data['duration'] = $pay_money['park_time'];
                    $showscreen_data['price'] = $pay_money['pay_money'];
                    $showscreen_data['voice_content'] = 'v9';
                    $showscreen_data['content'] = '';

                    fdump_api(['屏显和语音提示车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

                    if (!empty($outRightPosition)) {
                        //释放车位
                        $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                        $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                        $this->updateParkingPositionUse($whereArr, $saveArr, $car_number);
                    }
                    $this->addParkShowScreenLog($showscreen_data);
                    //车辆通行
                    if ($isImageToJob && ($record_id || $insert_id)) {
                        if (isset($car_access_order_id) && $car_access_order_id) {
                            $imageToJob['car_access_order_id'] = $car_access_order_id;
                        }
                        $imageToJob['out_record_id'] = $record_id;
                        (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                    }
                    $outArrRet['err_code'] = 0;
                    $outArrRet['err_msg'] = '正常通行';
                    $outArrRet['is_ok'] = true;
                    return $outArrRet;
                } elseif (!empty($in_park_pay_info['pay_order_id'])) {
                    $pay_info = $db_house_new_pay_order->get_one(['summary_id' => $in_park_pay_info['pay_order_id'], 'is_paid' => 1]);

                    fdump_api(['支付订单' => $car_number, 'pay_info' => $pay_info], 'hxPark/plateresult' . $parkTime, true);

                    if (!empty($pay_info) && $pay_info['pay_time'] > $in_park_pay_info['in_time']) {
                        if ($pay_info['pay_money'] >= $pay_money['pay_money'] || $pay_money['pay_money'] == 0) {
                            $db_in_park->saveOne(['id' => $in_park_pay_info['id'], 'car_number' => $car_number, 'park_id' => $passage_info['village_id']], $park_data);
                            $insert_id = $in_park_pay_info['id'];

                            if ($isImageToJob) {
                                $imageToJob['in_park_id'] = $in_park_pay_info['id'];
                                $imageToJob['out_park_id'] = 0;
                                $imageToJob['in_time'] = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                                $imageToJob['order_id'] = isset($in_park_pay_info['order_id']) ? trim($in_park_pay_info['order_id']) : '';
                            }
                            //写入车辆出场表
                            $car_access_order_id = '';
                            if ($in_park_pay_info && isset($in_park_pay_info['access_order_id']) && $in_park_pay_info['access_order_id']) {
                                $car_access_order_id = $in_park_pay_info['access_order_id'];
                            }
                            $park_time_tmp = 0;
                            if ($in_park_pay_info && isset($in_park_pay_info['in_time'])) {
                                $park_time_tmp = $now_time - $in_park_pay_info['in_time'];
                            }
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $car_number;
                            $car_access_record['accessType'] = 2;
                            $car_access_record['accessTime'] = $now_time;
                            $car_access_record['park_time'] = $park_time_tmp;
                            $car_access_record['accessMode'] = 4;
                            $car_access_record['park_sys_type'] = $park_sys_type;
                            $car_access_record['is_out'] = 1;
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';

                            $car_access_record['order_id'] = $car_access_order_id;
                            $car_access_record['total'] = $pay_money['pay_money'];
                            $car_access_record['pay_time'] = time();
                            $car_access_record['is_paid'] = 1;
                            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                            $car_access_record['pay_type'] = $this->pay_type[6];
                            $car_access_record['update_time'] = $now_time;
                            $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                            if (isset($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }
                            $car_access_record['pay_order_id'] = $in_park_pay_info['pay_order_id'];
                            $car_access_record['prepayTotal'] = isset($pay_money['parkTemp_money']) && $pay_money['parkTemp_money'] > 0 ? $pay_money['parkTemp_money'] : 0;
                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                            fdump_api(['车辆通行记录' => $car_number, 'car_access_record' => $car_access_record, 'record_id' => $record_id], 'hxPark/plateresult' . $parkTime, true);

                            $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 2, 'access_time' => time());
                            (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);

                            //TODO:屏显和语音提示车辆通行
                            //车辆通行
                            $showscreen_data['car_type'] = 'temporary_type';
                            $showscreen_data['duration_txt'] = $pay_money['time'];
                            $showscreen_data['duration'] = $pay_money['park_time'];
                            $showscreen_data['price'] = 0;

                            fdump_api(['屏显和语音提示车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

                            if (!empty($outRightPosition)) {
                                //释放车位
                                $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                                $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                                $this->updateParkingPositionUse($whereArr, $saveArr, $car_number);
                            }
                            $this->addParkShowScreenLog($showscreen_data);
                            if ($isImageToJob && ($record_id || $insert_id)) {
                                if (isset($car_access_order_id) && $car_access_order_id) {
                                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                                }
                                $imageToJob['out_record_id'] = $record_id;
                                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                            }
                            $outArrRet['err_code'] = 0;
                            $outArrRet['err_msg'] = '正常通行';
                            $outArrRet['is_ok'] = true;
                            return $outArrRet;
                        } else {
                            //TODO:屏显和语音提示错误信息
                            $showscreen_data['car_type'] = 'temporary_type';
                            $showscreen_data['duration_txt'] = $pay_money['time'];
                            $showscreen_data['duration'] = $pay_money['park_time'];
                            $showscreen_data['price'] = $pay_money['pay_money'];

                            $showscreen_data['voice_content'] = 'v5';
                            $showscreen_data['content'] = '';

                            fdump_api(['屏显和语音提示车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

                            $this->addParkShowScreenLog($showscreen_data);

                            //企业微信群提醒
                            if (isset($pay_money['pay_money'])) {
                                $payMoney = $pay_money['pay_money'] - $pay_info['pay_money'];
                            } else {
                                $payMoney = $pay_money['pay_money'];
                            }
                            //需缴费通行

                            $access_record_where_near = [['car_number', '=', $car_number], ['park_id', '=', $passage_info['village_id']], ['accessType', '=', 1], ['is_out', '=', 0], ['del_time', '<', 1], ['accessTime', '<=', $now_time],];// 通行时间小于出场时间的最近的一条入场时间的记录
                            $park_info_car111 = $db_house_village_car_access_record->getOne($access_record_where_near);
                            $near_in_record_id = 0;
                            $car_access_order_id = 0;
                            if ($park_info_car111 && !$park_info_car111->isEmpty()) {
                                $park_info_car111 = $park_info_car111->toArray();
                                $car_access_order_id = $park_info_car111['order_id'];
                                $near_in_record_id = $park_info_car111['record_id'];
                            }
                            //特殊车辆通行
                            if ($isImageToJob && ($in_park_pay_info && $in_park_pay_info['id'])) {
                                $imageToJob['car_access_order_id'] = $car_access_order_id;
                                $imageToJob['out_record_id'] = 0;
                                $imageToJob['near_in_record_id'] = $near_in_record_id;
                                $imageToJob['out_park_id'] = 0;
                                $imageToJob['now_no_out'] = 1;
                                $imageToJob['in_park_id'] = $in_park_pay_info['id'];
                                $imageToJob['in_time'] = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                            }
                            $outArrRet['err_code'] = 1;
                            $outArrRet['err_msg'] = '请扫码缴费';
                            $outArrRet['is_ok'] = false;
                            return $outArrRet;
                        }
                    } else {
                        //TODO:屏显和语音提示错误信息
                        $showscreen_data['voice_content'] = 'v5';
                        $showscreen_data['content'] = '';
                        $showscreen_data['car_type'] = 'temporary_type';
                        $showscreen_data['duration_txt'] = $pay_money['time'];
                        $showscreen_data['duration'] = $pay_money['park_time'];
                        $showscreen_data['price'] = $pay_money['pay_money'];
                        if ($parkTime) {
                            fdump_api(['屏显和语音提示车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);
                        }
                        $this->addParkShowScreenLog($showscreen_data);
                        //企业微信群提醒
                        $payMoney = $pay_money['pay_money'];

                        $access_record_where_near = [['car_number', '=', $car_number], ['park_id', '=', $passage_info['village_id']], ['accessType', '=', 1], ['is_out', '=', 0], ['del_time', '<', 1], ['accessTime', '<=', $now_time],];// 通行时间小于出场时间的最近的一条入场时间的记录
                        $park_info_car111 = $db_house_village_car_access_record->getOne($access_record_where_near);
                        $near_in_record_id = 0;
                        $car_access_order_id = 0;
                        if ($park_info_car111 && !$park_info_car111->isEmpty()) {
                            $park_info_car111 = $park_info_car111->toArray();
                            $car_access_order_id = $park_info_car111['order_id'];
                            $near_in_record_id = $park_info_car111['record_id'];
                        }
                        //特殊车辆通行
                        if ($isImageToJob && ($in_park_pay_info && $in_park_pay_info['id'])) {
                            $imageToJob['car_access_order_id'] = $car_access_order_id;
                            $imageToJob['out_record_id'] = 0;
                            $imageToJob['near_in_record_id'] = $near_in_record_id;
                            $imageToJob['out_park_id'] = 0;
                            $imageToJob['now_no_out'] = 1;
                            $imageToJob['in_park_id'] = $in_park_pay_info['id'];
                            $imageToJob['in_time'] = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                            (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                        }
                        $outArrRet['err_code'] = 1;
                        $outArrRet['err_msg'] = '临时车，请扫码缴费';
                        $outArrRet['is_ok'] = false;
                        return $outArrRet;
                    }
                } else {
                    //TODO:屏显和语音提示错误信息
                    $showscreen_data['voice_content'] = 'v5';
                    $showscreen_data['content'] = '';
                    $showscreen_data['car_type'] = 'temporary_type';
                    $showscreen_data['duration_txt'] = $pay_money['time'];
                    $showscreen_data['duration'] = $pay_money['park_time'];
                    $showscreen_data['price'] = $pay_money['pay_money'];

                    fdump_api(['屏显和语音提示车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);

                    $this->addParkShowScreenLog($showscreen_data);
                    //企业微信群提醒
                    $payMoney = $pay_money['pay_money'];
                    $access_record_where_near = [['car_number', '=', $car_number], ['park_id', '=', $passage_info['village_id']], ['accessType', '=', 1], ['is_out', '=', 0], ['del_time', '<', 1], ['accessTime', '<=', $now_time],];// 通行时间小于出场时间的最近的一条入场时间的记录
                    $park_info_car111 = $db_house_village_car_access_record->getOne($access_record_where_near);
                    $near_in_record_id = 0;
                    $car_access_order_id = 0;
                    if ($park_info_car111 && !$park_info_car111->isEmpty()) {
                        $park_info_car111 = $park_info_car111->toArray();
                        $car_access_order_id = $park_info_car111['order_id'];
                        $near_in_record_id = $park_info_car111['record_id'];
                    }
                    //特殊车辆通行
                    if ($isImageToJob && ($in_park_pay_info && $in_park_pay_info['id'])) {
                        $imageToJob['car_access_order_id'] = $car_access_order_id;
                        $imageToJob['out_record_id'] = 0;
                        $imageToJob['near_in_record_id'] = $near_in_record_id;
                        $imageToJob['out_park_id'] = 0;
                        $imageToJob['now_no_out'] = 1;
                        $imageToJob['in_park_id'] = $in_park_pay_info['id'];
                        $imageToJob['in_time'] = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                        (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                    }
                    $outArrRet['err_code'] = 1;
                    $outArrRet['err_msg'] = '临时车，请扫码缴费';
                    $outArrRet['is_ok'] = false;
                    return $outArrRet;
                }
            } else {
                //TODO:屏显和语音提示错误信息
                $showscreen_data['voice_content'] = 'v8';
                $showscreen_data['content'] = '未入场';
                $showscreen_data['car_type'] = 'temporary_type';
                $showscreen_data['duration_txt'] = '';
                $showscreen_data['duration'] = 0;
                $showscreen_data['price'] = 0;
                if ($parkTime) {
                    fdump_api(['屏显和语音提示车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'hxPark/plateresult' . $parkTime, true);
                }
                $this->addParkShowScreenLog($showscreen_data);
                //企业微信群提醒
                $payMoney = $pay_money['pay_money'];
                $outArrRet['err_code'] = 1;
                $outArrRet['err_msg'] = '临时车，请扫码缴费';
                $outArrRet['is_ok'] = false;
                return $outArrRet;
            }
        }
        $outArrRet['err_code'] = 1;
        $outArrRet['err_msg'] = '出场异常';
        $outArrRet['is_ok'] = false;
        return $outArrRet;
    }

    public function getCarRightOutPosition($car = array(), $village_id = 0, $garage_id = 0, $car_number = '')
    {

        $whereArr = array();
        if (!empty($car_number)) {
            $whereArr[] = array('use_car_number', '=', $car_number);
        } elseif ($car && isset($car['car_id']) && !empty($car['car_id'])) {
            $whereArr[] = array('use_car_id', '=', $car['car_id']);
        }
        $parkTime = date('YmdH');
        $position = array();
        if (!empty($whereArr)) {
            $db_house_village_parking_position = new HouseVillageParkingPosition();
            $whereArr[] = array('village_id', '=', $village_id);
            if ($garage_id > 0) {
                $whereArr[] = array('garage_id', '=', $garage_id);
            }
            $positionObj = $db_house_village_parking_position->getFind($whereArr, '*', 'end_time desc');
            if ($positionObj && !$positionObj->isEmpty()) {
                $position = $positionObj->toArray();
            }
            fdump_api([__LINE__, 'car_number' => $car_number, 'whereArr' => $whereArr, 'position' => $position], 'hxPark/plateresult' . $parkTime, 1);

        }
        if (!empty($position)) {
            $position['start_time'] = isset($position['start_time']) && intval($position['start_time']) > 0 ? intval($position['start_time']) : 0;
        }
        $nowTime = time();
        if (empty($position)) {
            $bind_position_ids = array();
            if ($car && isset($car['bind_position_ids']) && !empty($car['bind_position_ids'])) {
                $bind_position_ids = explode(',', $car['bind_position_ids']);
                $bind_position_ids = array_map('intval', $bind_position_ids);
            }
            if ($car && $car['car_position_id']) {
                $bind_position_ids[] = intval($car['car_position_id']);
            }
            if (!empty($bind_position_ids)) {
                $bind_position_ids = array_unique($bind_position_ids);
                $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
                $access_record_where_near = [['car_number', '=', $car_number], ['park_id', '=', $village_id], ['accessType', '=', 1], ['del_time', '<', 1], ['accessTime', '<=', $nowTime],];
                $houseVillageParkingCarBindPositionDb = new HouseVillageParkingCarBindPosition();
                if (count($bind_position_ids) == 1) {
                    //只绑了一个车位
                    $whereArr = array(['position_id', '=', $bind_position_ids['0']]);
                    if ($garage_id > 0) {
                        $whereArr[] = array('garage_id', '=', $garage_id);
                    }
                    $positionObj = $db_house_village_parking_position->getFind($whereArr, '*');
                    if ($positionObj && !$positionObj->isEmpty()) {
                        //绑定的这个没被占用
                        $position = $positionObj->toArray();
                        fdump_api([__LINE__, 'car_number' => $car_number, 'whereArr' => $whereArr, 'position' => $position], 'hxPark/plateresult' . $parkTime, 1);
                        if ($position['use_car_id'] < 1 && empty($position['use_car_number'])) {
                            $position['start_time'] = $position['start_time'] ? $position['start_time'] : 0;
                            $whereBind = array('position_id' => $bind_position_ids['0'], 'car_id' => $car['car_id'], 'del_time' => 0);
                            $oneBindObj = $houseVillageParkingCarBindPositionDb->getOneData($whereBind, '*');
                            if ($oneBindObj && !$oneBindObj->isEmpty()) {
                                $bind_position_time = $oneBindObj['bind_position_time'];
                                // 通行时间小于出场时间的最近的一条入场时间的记录
                                $park_access_record_obj = $db_house_village_car_access_record->getOne($access_record_where_near, '*', 'record_id DESC');
                                if ($park_access_record_obj && !$park_access_record_obj->isEmpty()) {
                                    $park_access_record = $park_access_record_obj->toArray();
                                    if ($park_access_record['flag_status'] == 'temporaryCarNoPosition' || $park_access_record['park_car_type'] == 'temporaryCarNoPosition' || $park_access_record['car_type'] == 'temporaryCarNoPosition') {
                                        //无车位进场的
                                        $inAccessTime = $park_access_record['accessTime'] > 100 ? $park_access_record['accessTime'] : $nowTime;
                                        $inAccessTimeDay = date('Ymd', $inAccessTime);
                                        $inAccessTimeDay = intval($inAccessTimeDay);
                                        $bindTimeDay = date('Ymd', $bind_position_time);
                                        $bindTimeDay = intval($bindTimeDay);
                                        $chaDay = $bindTimeDay - $inAccessTimeDay;
                                        if ($chaDay > 0 && $position['end_time'] > $nowTime) {
                                            //车位有效，并且绑定时间和进场时间隔了至少一个零点
                                            $bindTimeDate = date('Y-m-d', $bind_position_time);
                                            $bindTimeDay_time = strtotime($bindTimeDate);
                                            //绑定时间日 凌晨算临时车结束时间
                                            $position['temporary_end_time'] = $bindTimeDay_time - 1;
                                        }
                                    }
                                }

                            }
                        } else {
                            $position = array();
                        }
                    }
                }
                fdump_api(['position' => $position, 'bind_position_ids' => $bind_position_ids], 'hxPark/plateresult' . $parkTime, 1);
                //还是没找到车位
                if (empty($position)) {
                    $whereBindArr = array();
                    $whereBindArr[] = array('position_id', 'in', $bind_position_ids);
                    $whereBindArr[] = array('del_time', '<', 1);
                    $whereBindArr[] = array('village_id', '=', $village_id);
                    $carPositionBindObj = $houseVillageParkingCarBindPositionDb->getListData($whereBindArr, 'position_id,car_id,bind_position_time,garage_id,village_id', 'id DESC', 0, 100);
                    $usePositionArr = array();
                    if ($carPositionBindObj && !$carPositionBindObj->isEmpty()) {
                        $carPositionBind = $carPositionBindObj->toArray();
                        $tmpCarPositionBind = array();
                        foreach ($carPositionBind as $ppv) {
                            if (isset($tmpCarPositionBind[$ppv['position_id']])) {
                                $tmpCarPositionBind[$ppv['position_id']][] = $ppv['car_id'];
                            } else {
                                $tmpCarPositionBind[$ppv['position_id']] = array($ppv['car_id']);
                            }
                        }
                        foreach ($bind_position_ids as $p_id) {
                            $tmpxPosition = isset($tmpCarPositionBind[$p_id]) && $tmpCarPositionBind[$p_id] ? array_unique($tmpCarPositionBind[$p_id]) : '';
                            if (empty($tmpxPosition)) {
                                $usePositionArr[] = $p_id;
                            } else if (is_array($tmpxPosition) && in_array($car['car_id'], $tmpxPosition) && count($tmpxPosition) == 1) {
                                $usePositionArr[] = $p_id;
                            }
                        }
                    } else {
                        $usePositionArr = $bind_position_ids;
                    }
                    fdump_api(['usePositionArr' => $usePositionArr, 'bind_position_ids' => $bind_position_ids], 'hxPark/plateresult' . $parkTime, 1);
                    if (!empty($usePositionArr)) {
                        $whereArr = array(['position_id', 'in', $usePositionArr]);
                        $whereArr[] = array('start_time', '<', $nowTime);
                        $whereArr[] = array('end_time', '>', $nowTime);
                        if ($garage_id > 0) {
                            $whereArr[] = array('garage_id', '=', $garage_id);
                        }
                        $positionObj = $db_house_village_parking_position->getFind($whereArr, '*', 'use_start_time ASC,position_id DESC');
                        if ($positionObj && !$positionObj->isEmpty()) {
                            $position = $positionObj->toArray();
                        }
                    }
                }
            }
        }
        //$this->updateHXt85WhiteList($position,$car,$village_id,'out_park');
        return $position;
    }

    /**
     * 计算临时车停车费
     * @author:zhubaodi
     * @date_time: 2021/11/3 16:15
     */
    public function get_temp_pay($data)
    {
        $car_number = isset($data['car_number']) && $data['car_number'] ? $data['car_number'] : '';
        fdump_api(['计算停车费', $data], 'hxPark/temp_pay_' . $car_number, 1);
        $db_in_park = new InPark();
        $db_house_village_park_charge = new HouseVillageParkCharge();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $db_house_village_parking_car = new HouseVillageParkingCar();

        $garage_id = 0;
        if (isset($data['garage_id']) && $data['garage_id'] > 0) {
            $garage_id = $data['garage_id'];
        }
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id, $data['village_id']);

        $where = [];
        $park_sys_type = isset($data['park_sys_type']) && $data['park_sys_type'] ? $data['park_sys_type'] : 'HXT85';
        $where['car_number'] = $data['car_number'];
        $where['park_id'] = $data['village_id'];
        //$where['park_sys_type'] = $park_sys_type;
        $where['is_paid'] = 0;
        $where['is_out'] = 0;
        $where['del_time'] = 0;
        $park_info = $db_in_park->getOne1($where);
        $db_house_village_park_temp = new HouseVillageParkingTemp();
        if (empty($park_info) || $park_info->isEmpty()) {
            $houseVillage = new HouseVillage();
            $houseVillageInfo = $houseVillage->getOne(['village_id' => $data['village_id']], 'village_name');
            $park_name = '';
            if ($houseVillageInfo && !$houseVillageInfo->isEmpty()) {
                $park_name = $houseVillageInfo['village_name'];
            }
            if ($park_config['not_inPark_money'] > 0) {
                /***无入场记录收费**/
                $data_temp = [];
                $data_temp['village_id'] = $data['village_id'];
                $data_temp['add_time'] = time();
                $data_temp['free_out_time'] = 0;
                $data_temp['duration'] = 0;
                $data_temp['derate_money'] = 0;
                $data_temp['derate_duration'] = 0;
                $order_id = '330' . date('YmdHis') . rand(100, 999);
                $data_temp['order_id'] = $order_id;
                $data_temp['query_order_no'] = build_real_orderid($data['uid']);
                $data_temp['errmsg'] = '';
                $data_temp['price'] = $park_config['not_inPark_money'];
                $data_temp['total'] = $park_config['not_inPark_money'];
                $data_temp['in_time'] = 0;
                $data_temp['car_number'] = $data['car_number'];
                $data_temp['out_channel_id'] = isset($data['channel_number']) ? $data['channel_number'] : '';
                $data_temp['is_pay_scene'] = 0;
                $data_temp['park_sys_type'] = $park_sys_type;
                $data_temp['garage_id'] = $garage_id;
                fdump_api([__LINE__, 'data_temp' => $data_temp], 'hxPark/temp_pay_' . $car_number, 1);
                $id1 = $db_house_village_park_temp->addOne($data_temp);
                fdump_api([__LINE__, 'id1' => $id1], 'hxPark/temp_pay_' . $car_number, 1);

                $list = [];
                $list['time'] = '--';
                $list['park_time'] = 0;
                $list['pay_money'] = $park_config['not_inPark_money'];
                $list['order_id'] = $order_id;
                $list['park_name'] = $park_name;
                $list['in_time'] = '--';
                $list['pay_time'] = time();
                $list['total_money'] = $park_config['not_inPark_money'];
                $list['coupon_money'] = 0;
                $list['coupon_id'] = 0;
                $list['parkTemp_money'] = 0;
                $list['park_temp_id'] = 0;
                fdump_api([__LINE__, 'list' => $list], 'hxPark/temp_pay_' . $car_number, 1);
                return $list;
            } else {
                fdump_api(['00计算停车费', 'err' => '没有停车纪录,直接开门'], 'hxPark/temp_pay_' . $car_number, 1);
                $list = [];
                $order_id = '300' . date('YmdHis') . rand(100, 999);
                $list['time'] = '--';
                $list['park_time'] = 0;
                $list['pay_money'] = 0;
                $list['order_id'] = $order_id;
                $list['park_name'] = $park_name;
                $list['in_time'] = '--';
                $list['pay_time'] = time();
                $list['total_money'] = 0;
                $list['coupon_money'] = 0;
                $list['coupon_id'] = 0;
                $list['parkTemp_money'] = 0;
                $list['park_temp_id'] = 0;
                return $list;
                //throw new \Exception('没有停车纪录');
            }
        }
        $park_info = $park_info->toArray();
        $park_count = $db_in_park->getCount(['car_number' => $data['car_number'], 'park_id' => $data['village_id']]);
        fdump_api([__LINE__, $park_info, $where, $park_count], 'hxPark/temp_pay_' . $car_number, 1);
        $where_rule = [['village_id', '=', $data['village_id']], ['status', '=', 1], ['charge_valid_time', '<=', time()], ['fees_type', '=', 3],  //3临时车 4月租车
        ];
        $rule_info = $db_house_new_charge_rule->getOne($where_rule, '*', 'charge_valid_time DESC');
        if (empty($rule_info)) {
            throw new Exception('该停车场未绑定收费规则');
        }
        $rule_id=$rule_info['id'];
        $project_id=$rule_info['charge_project_id'];
        $where_out = [];
        $where_out['car_number'] = $data['car_number'];
        $where_out['park_id'] = $data['village_id'];
        $where_out['park_sys_type'] = $park_sys_type;
        $where_out['is_out'] = 1;
        $where_out['del_time'] = 0;
        $park_info1 = $db_in_park->getOne1($where_out);
        if (!empty($park_info1) && $park_info1['out_time'] > $park_info['in_time'] && $park_info1['out_time'] < time()) {
            $park_info['in_time'] = $park_info1['out_time'];
        }
        $where_charge = [];
        $where_charge['village_id'] = $data['village_id'];
        //$where_charge['park_sys_type'] = $park_sys_type;
        $where_charge['status'] = 1;
        $where_charge['id'] = $rule_info['park_charge_id'];
        $park_charge = $db_house_village_park_charge->getFind($where_charge);
        fdump_api([$park_charge, $where_charge, $park_info['in_time']], 'hxPark/temp_pay_' . $car_number, 1);
        if (empty($park_charge)) {
            throw new Exception('该停车场未绑定收费规则');
        }
        $now_time = time();
        $park_time = $now_time - $park_info['in_time'];
        $province = mb_substr($data['car_number'], 0, 1);
        $car_no = mb_substr($data['car_number'], 1);
        if (isset($data['temporary_end_time']) && $data['temporary_end_time'] > 100) {
            $park_time = $data['temporary_end_time'] - $park_info['in_time'];
        } else if (isset($data['car_end_time']) && $data['car_end_time'] > 100 && $park_info['in_time'] < $data['car_end_time'] && $now_time > $data['car_end_time']) {
            $park_time = $now_time - $data['car_end_time'];
        } else {
            $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $data['village_id']]);
            fdump_api([$car_info], 'hxPark/temp_pay_' . $car_number, 1);
            if (!empty($car_info) && isset($car_info['end_time']) && $car_info['end_time'] > 1 && $park_info['in_time'] < $car_info['end_time'] && $now_time > $car_info['end_time']) {
                $park_time = $now_time - $car_info['end_time'];
            }
        }
        $whereTemp = [];
        $whereTemp[] = ['car_number', '=', $data['car_number']];
        $whereTemp[] = ['village_id', '=', $data['village_id']];
        $whereTemp[] = ['pay_time', '>', 0];
        $parkTemp = $db_house_village_park_temp->getOne($whereTemp, true, 'pay_time DESC,id DESC');
        $park_time_total = $park_time;

        $is_advance_pay = 0;  //付过了，识别比心跳过来的快
        fdump_api([$parkTemp, $park_info['in_time'], $park_time], 'hxPark/temp_pay_' . $car_number, 1);
        if ($parkTemp && isset($parkTemp['pay_time']) && $parkTemp['pay_time'] > $park_info['in_time']) {
            $parkTemp_money = $parkTemp['price'];
            $next_last_park_time = time() - $parkTemp['pay_time'];
            if ($next_last_park_time < 600 && $parkTemp['order_id'] == $park_info['order_id']) {
                //10分钟中内相同 入场记录如果有 支付记录 记下钱
                $is_advance_pay = 1;
            }
            if (empty($park_config['free_park_time'])) {
                if (time() - $parkTemp['pay_time'] < 900) {
                    $park_time = 0;
                }
            } else {
                if (time() - $parkTemp['pay_time'] < ($park_config['free_park_time'] * 60)) {
                    $park_time = 0;
                }
            }
        } else {
            $parkTemp_money = 0;
        }
        $park_charge_info = array();
        if ($park_charge['charge_type'] == 1) {
            $park_charge_info = unserialize($park_charge['charge_set']);
            $last_ages = array_column($park_charge_info, 'time');
            array_multisort($last_ages, SORT_DESC, $park_charge_info);
            fdump_api([__LINE__, 'park_charge_info' => $park_charge_info, 'park_time' => $park_time], 'hxPark/temp_pay_' . $car_number, 1);
        } elseif ($park_charge['charge_type'] == 4) {
            //24小时逐时收费制
            $park_charge_info = unserialize($park_charge['charge_set']);
            fdump_api([__LINE__, 'park_charge_info' => $park_charge_info, 'park_time' => $park_time], 'hxPark/temp_pay_' . $car_number, 1);
            if (empty($park_charge_info)) {
                fdump_api([__LINE__, 'err' => '费用设置数据错误', 'park_charge' => $park_charge], 'hxPark/temp_pay_' . $car_number, 1);
                throw new \Exception('费用设置数据错误！');
            }
        }

        fdump_api([__LINE__, 'charge_type' => $park_charge['charge_type'], 'park_time' => $park_time, 'park_charge' => $park_charge, 'park_charge_info' => $park_charge_info], 'hxPark/temp_pay_' . $car_number, 1);
        $park_money = 0;
        $pay_money = 0;
        $coupon_id = 0;
        $total_money = 0;
        if ($park_time > 0) {
            /***
             *新按次收费
             * 1连续多少小时只收取一次费(在原来charge_type是3 上面时间不一定是24了) 2按次收费（和 charge_type是2一样） 3当天只收取一次费(和 charge_type是3一样)
             */
            $charge_type5_set = isset($park_charge['charge_type5_set']) ? $park_charge['charge_type5_set'] : 0;

            $parkTime = ceil($park_time / 60);
            if ($parkTime <= $park_charge['free_time']) {
                $pay_money = 0;
            } else {
                if (isset($park_charge['free_time_no_count']) && $park_charge['free_time_no_count'] == 1 && $park_charge['free_time'] > 0) {
                    //1从免费时间后开始计算费用 
                    $park_time = $park_time - ($park_charge['free_time'] * 60);
                    $park_time = $park_time > 0 ? $park_time : 0;
                }
                $parkTime = ceil($park_time / 60);

                if ($park_count == 1 && !empty($park_charge['first_free_time']) && $parkTime <= $park_charge['first_free_time']) {
                    $pay_money = $park_charge['first_charge_money'];
                    fdump_api([__LINE__, 'pay_money' => $pay_money], 'hxPark/temp_pay_' . $car_number, 1);
                } else {
                    if ($park_charge['charge_type'] == 1) {
                        if (!empty($park_charge_info)) {
                            $parkTime1 = ceil($park_time / 3600);
                            fdump_api([__LINE__, 'parkTime1' => $parkTime1], 'hxPark/temp_pay_' . $car_number, 1);
                            if ($park_charge['max_charge_money'] > 0 && $parkTime1 > 24) {
                                $level = intval($park_time / 86400);
                                $park_money = $level * $park_charge['max_charge_money'];
                                $park_time = $park_time - $level * 86400;
                                $parkTime1 = $parkTime1 - 24 * $level;
                                fdump_api([__LINE__, 'level' => $level, 'park_money' => $park_money, 'park_time' => $park_time, 'parkTime1' => $parkTime1], 'hxPark/temp_pay_' . $car_number, 1);
                            }
                            if ($park_charge_info[0]['time'] < $parkTime1) {
                                $pay_money = $park_charge_info[0]['money'];
                                $parkTime2 = $park_time - $park_charge_info[0]['time'] * 3600;
                                fdump_api([__LINE__, 'pay_money' => $pay_money, 'parkTime2' => $parkTime2], 'hxPark/temp_pay_' . $car_number, 1);
                                if ($park_charge['max_charge_money'] > 0) {
                                    /* if($parkTime1>24){
                                         $level=intval($park_time / 86400);
                                         $park_money=$level*$park_charge['max_charge_money'];
                                         $parkTime2=$park_time-$level*86400;
                                     }*/
                                    $parkTime3 = ceil($parkTime2 / ($park_charge['charge_time'] * 60));
                                    $park_money1 = $parkTime3 * $park_charge['charge_money'];
                                    fdump_api([__LINE__, 'parkTime3' => $parkTime3, 'park_money1' => $park_money1], 'hxPark/temp_pay_' . $car_number, 1);
                                    if ($parkTime1 > 24 && ($pay_money + $park_money1) > $park_charge['max_charge_money']) {
                                        $park_money += $park_charge['max_charge_money'];
                                        fdump_api([__LINE__, 'park_money' => $park_money], 'hxPark/temp_pay_' . $car_number, 1);
                                    } elseif ($parkTime1 <= 24 && ($pay_money + $park_money1) > $park_charge['max_charge_money']) {
                                        $pay_money = $park_charge['max_charge_money'];
                                        fdump_api([__LINE__, 'park_money' => $park_money], 'hxPark/temp_pay_' . $car_number, 1);
                                    } else {
                                        $park_money += $park_money1;
                                        fdump_api([__LINE__, 'park_money' => $park_money], 'hxPark/temp_pay_' . $car_number, 1);
                                    }

                                } else {
                                    $parkTime3 = ceil($parkTime2 / ($park_charge['charge_time'] * 60));
                                    $park_money1 = $parkTime3 * $park_charge['charge_money'];
                                    $park_money += $park_money1;
                                    fdump_api([__LINE__, 'parkTime3' => $parkTime3, 'park_money1' => $park_money1, 'park_money' => $park_money], 'hxPark/temp_pay_' . $car_number, 1);
                                }
                            } else {
                                foreach ($park_charge_info as $k => $v) {
                                    if ($v['time'] == $parkTime1) {
                                        $pay_money = $v['money'];
                                        fdump_api([__LINE__, 'park_money' => $park_money], 'hxPark/temp_pay_' . $car_number, 1);
                                        break;
                                    } elseif ($v['time'] < $parkTime1) {
                                        $pay_money = $park_charge_info[$k - 1]['money'];
                                        fdump_api([__LINE__, 'park_money' => $park_money], 'hxPark/temp_pay_' . $car_number, 1);
                                        break;
                                    } elseif (!isset($park_charge_info[$k + 1]) && $v['time'] > $parkTime1) {
                                        $pay_money = $park_charge_info[$k]['money'];
                                        fdump_api([__LINE__, 'park_money' => $park_money], 'hxPark/temp_pay_' . $car_number, 1);
                                        break;
                                    }
                                }
                            }
                        }
                    } elseif ($park_charge['charge_type'] == 4) {
                        $parkTimeHour = ceil($park_time / 3600); //算小时
                        $tmp_pay_money = 0;
                        do {
                            foreach ($park_charge_info as $kk => $vv) {
                                $new_key = $kk + 1;
                                if ($vv['hour'] < 24 && ($parkTimeHour >= $vv['hour']) && ($parkTimeHour < $park_charge_info[$new_key]['hour'])) {
                                    $tmp_pay_money += $vv['fee'];
                                } else if ($vv['hour'] == 1 && $parkTimeHour <= $vv['hour']) {
                                    $tmp_pay_money += $vv['fee'];
                                } else if (($vv['hour'] == 24) && ($parkTimeHour >= $vv['hour'])) {
                                    $tmp_pay_money += $vv['fee'];
                                }
                            }
                            $parkTimeHour = $parkTimeHour - 24;
                        } while ($parkTimeHour > 0);
                        $pay_money = round($tmp_pay_money, 2);
                    } elseif ($park_charge['charge_type'] == 2 || ($park_charge['charge_type'] == 5 && $charge_type5_set == 2)) {
                        $parkTime_charge2 = ceil($park_time / 86400);
                        $pay_money = $park_charge['max_charge_money'] * $parkTime_charge2;
                        fdump_api([__LINE__, 'parkTime_charge2' => $parkTime_charge2, 'park_money' => $park_money], 'hxPark/temp_pay_' . $car_number, 1);
                    } else {
                        $parkTime_charge3 = $park_info['in_time'] + $park_time;
                        $oneDaySecond = 86400;  //24小时
                        if ($park_charge['charge_type'] == 5 && $charge_type5_set == 1 && $park_charge['hour_once_fee'] > 0) {
                            $oneDaySecond = $park_charge['hour_once_fee'] * 3600;  //自定义的
                        }
                        $pay_money = 0;
                        $time_charge = strtotime(date('Y-m-d 00:00:00', $park_info['in_time'])) + $oneDaySecond;
                        fdump_api([__LINE__, 'parkTime_charge3' => $parkTime_charge3, 'time_charge' => $time_charge], 'hxPark/temp_pay_' . $car_number, 1);
                        if ($time_charge < $parkTime_charge3) {
                            $parkTime_charge2 = ceil($park_time / $oneDaySecond);
                            $pay_money = $park_charge['max_charge_money'] * $parkTime_charge2;
                            fdump_api([__LINE__, 'parkTime_charge2' => $parkTime_charge2, 'pay_money' => $pay_money], 'hxPark/temp_pay_' . $car_number, 1);
                        } elseif (empty($parkTemp) || !isset($parkTemp['price']) || ($parkTemp['price'] <= 0)) {
                            //第一次
                            $pay_money = $park_charge['max_charge_money'];
                            if ($charge_type5_set == 3 || $charge_type5_set == 1) {
                                $faccessTime = strtotime(date('Y-m-d 00:00:00'));
                                $recordWhere = [['business_type', '=', '0'], ['business_id', '=', $data['village_id']], ['accessType', '=', 2], ['car_number', '=', $car_number], ['is_out', '=', 1],];
                                $recordWhere[] = ['accessTime', '>', $faccessTime];
                                $parkRecord = (new HouseVillageCarAccessRecord())->getOne($recordWhere, 'record_id,accessTime,car_number');
                                if ($parkRecord && !$parkRecord->isEmpty()) {
                                    $pay_money = 0;
                                }
                            }
                            fdump_api([__LINE__, 'park_money' => $park_money], 'hxPark/temp_pay_' . $car_number, 1);
                        }
                    }
                }
            }
            $pay_money = $pay_money + $park_money - $parkTemp_money;
            $pay_money = $pay_money > 0 ? $pay_money : 0;
            $total_money = $pay_money;
            if ($pay_money > 0) {
                $this->checkCouponUseStatus($data['village_id'], $data['car_number'], $park_sys_type);
                $db_house_village_park_coupon = new HouseVillageParkCoupon();
                $where_coupon = [];
                $where_coupon['village_id'] = $data['village_id'];
                $where_coupon['car_number'] = $data['car_number'];
                /*$where_coupon['user_id'] = $data['uid'];*/
                //$where_coupon['park_sys_type'] = $park_sys_type;
                $where_coupon['is_use'] = 0;
                $coupon_info = $db_house_village_park_coupon->getFind($where_coupon);
                if (!empty($coupon_info)) {
                    $coupon_info = $coupon_info->toArray();
                    fdump_api([$data['car_number'], 'coupon_info' => $coupon_info], 'D3Park/get_temp_pay_log', 1);
                    if (!empty($coupon_info)) {
                        $coupon_id = $coupon_info['id'];
                        $pay_money = $pay_money - $coupon_info['money'];
                        if ($pay_money <= 0) {
                            $pay_money = 0;
                        }
                        $db_house_village_park_coupon->saveOne($where_coupon, ['is_use' => 1, 'use_time' => time()]);

                    } else {
                        $coupon_info['money'] = 0;
                    }

                } else {
                    $coupon_info['money'] = 0;
                }
            }
        } elseif ($park_time_total <= 0) {
            fdump_api([__LINE__, 'err' => '没有停车时长'], 'hxPark/temp_pay_' . $car_number, 1);
            throw new Exception('没有停车时长');
        }
        $data_temp = [];
        $data_temp['village_id'] = $data['village_id'];
        $data_temp['add_time'] = time();
        $data_temp['free_out_time'] = $park_charge['free_time'];
        $data_temp['duration'] = round_number($park_time_total / 60, 2);
        $data_temp['derate_money'] = isset($coupon_info['money']) ? $coupon_info['money'] : 0;
        $data_temp['derate_duration'] = 0;
        $data_temp['order_id'] = $park_info['order_id'];
        $data_temp['query_order_no'] = build_real_orderid($data['uid']);
        $data_temp['errmsg'] = '';
        $data_temp['price'] = $pay_money;
        $data_temp['total'] = $total_money;
        $data_temp['in_time'] = $park_info['in_time'];
        $data_temp['car_number'] = $data['car_number'];
        $data_temp['out_channel_id'] = isset($data['channel_number']) ? $data['channel_number'] : '';
        $data_temp['is_pay_scene'] = 0;
        $data_temp['park_sys_type'] = $park_sys_type;
        $data_temp['coupon_id'] = $coupon_id;
        $data_temp['garage_id'] = $garage_id;
        if ($pay_money == 0) {
            $data_temp['is_paid'] = 1;
            $data_temp['pay_time'] = time();
        }
        if (isset($data['in_record_id']) && $data['in_record_id']) {
            $data_temp['in_record_id'] = $data['in_record_id'];
        }
        $data_temp['rule_id']=$rule_id;
        $data_temp['project_id']=$project_id;
        $id1 = $db_house_village_park_temp->addOne($data_temp);
        fdump_api(['计算后记录临时表中', $data_temp], 'hxPark/temp_pay_' . $car_number, 1);

        $hours = intval($park_time_total / 3600);
        $mins = intval(($park_time_total - $hours * 3600) / 60);
        $second = $park_time_total - $hours * 3600 - $mins * 60;
        $list = [];
        $list['time'] = $hours . '小时' . $mins . '分钟' . $second . '秒';
        $list['park_time'] = $park_time_total;
        $list['pay_money'] = $pay_money;
        $list['order_id'] = $park_info['order_id'];
        $list['park_name'] = $park_info['park_name'];
        $list['in_time'] = date('Y-m-d H:i:s', $park_info['in_time']);
        $list['pay_time'] = time();
        $list['total_money'] = $total_money;
        $list['coupon_money'] = $coupon_info['money'];
        $list['coupon_id'] = $coupon_id;
        $list['park_temp_id'] = $id1;
        $list['parkTemp_money'] = $parkTemp_money;
        $list['is_advance_pay'] = $is_advance_pay;
        fdump_api(['返回', $list], 'hxPark/temp_pay_' . $car_number, 1);
        return $list;
    }

    public function checkCouponUseStatus($village_id = 0, $car_number = '', $park_sys_type = '')
    {
        $db_house_village_park_temp = new HouseVillageParkingTemp();
        $whereTemp = [];
        $whereTemp[] = ['village_id', '=', $village_id];
        $whereTemp[] = ['car_number', '=', $car_number];
        $whereTemp[] = ['is_paid', '=', 0];
        if ($park_sys_type) {
            $whereTemp[] = ['park_sys_type', '=', $park_sys_type];
        }
        $nowtime = time();
        $tmptime = $nowtime - 36000;
        $whereTemp[] = array('add_time', '<', $nowtime);
        $whereTemp[] = array('add_time', '>', $tmptime);
        $whereTemp[] = ['coupon_id', '>', 0];
        $parkTemp = $db_house_village_park_temp->getOne($whereTemp, 'id,coupon_id,duration,derate_money,price,total', 'id DESC');
        fdump_api([$car_number, 'whereTemp' => $whereTemp], 'D3Park/get_temp_pay_log', 1);
        if ($parkTemp && !$parkTemp->isEmpty()) {
            fdump_api([$car_number, 'parkTemp' => $parkTemp], 'D3Park/get_temp_pay_log', 1);
            $db_house_village_park_coupon = new HouseVillageParkCoupon();
            $whereArr = array('vpc.id' => $parkTemp['coupon_id'], 'vpc.village_id' => $village_id, 'sc.village_id' => $village_id);
            $oneCoupon = $db_house_village_park_coupon->getOneCoupon($whereArr, 'sc.*');
            $is_coupon_can_use = 0;
            if ($oneCoupon && !$oneCoupon->isEmpty() && $oneCoupon['cid']) {
                $parkCouponsDb = new ParkCoupons();
                $whereCoupon = array('village_id' => $village_id, 'c_id' => $oneCoupon['cid']);
                $parkCouponsObj = $parkCouponsDb->getFind($whereCoupon, '*');
                if ($parkCouponsObj && !$parkCouponsObj->isEmpty() && $parkCouponsObj['status'] == 0) {
                    $is_coupon_can_use = 1;
                }
            }
            if ($is_coupon_can_use > 0) {
                $where_coupon = [];
                $where_coupon['village_id'] = $village_id;
                $where_coupon['car_number'] = $car_number;
                $where_coupon['id'] = $parkTemp['coupon_id'];
                $saveArr = array('is_use' => 0, 'use_time' => 0, 'use_txt' => '');
                $db_house_village_park_coupon->saveOne($where_coupon, $saveArr);
                return $parkTemp['coupon_id'];
            }
        }
        return true;
    }

    public function updateHXt85WhiteList($position = array(), $car = array(), $village_id = 0, $comfrom = '')
    {
        $now_time = time();
        $car_end_time = $car['end_time'];
        $garage_id = 0;
        if ($position && isset($position['garage_id'])) {
            $garage_id = $position['garage_id'];
        }
        if ($position && $position['start_time'] < $now_time && $position['end_time'] > $car_end_time) {
            $car_end_time = $position['end_time'];
        }
        $wData = array('village_id' => $village_id, 'garage_id' => $garage_id, 'car_end_time' => $car_end_time);
        $wData['car_number'] = $car['province'] . $car['car_number'];
        $wData['cfrom'] = $comfrom;
        $wData['car_info'] = $car;
        if ($car_end_time > $now_time) {
            //月租车，添加白名单
            $this->addWhitelist($wData);
        } else {
            //月租车，删除白名单
            $this->delWhitelist($wData);
        }
    }
    public function batchMonthCarToDev ($village_id,$car_id=0,$park_sys_type='HXT85'){
        $parkWhiteRecordDb=new ParkWhiteRecord();
            $whereArr=array(['village_id','=',$village_id],['park_sys_type','=',$park_sys_type],['need_alarm','=','0'],['enable','=',1]);
            $findDataObj=$parkWhiteRecordDb->getFind($whereArr,'*','id desc');
            $findCount=0;
            if($findDataObj && !$findDataObj->isEmpty()){
                $findCount=$parkWhiteRecordDb->get_count($whereArr);
            }
            $timeX=time()-7200;
            if($findCount>50 || ($findCount>10 && $findDataObj['add_time']>$timeX)){
                return ['status'=>true,'msg'=>'操作成功（正有命令下发中）!','data'=>[]];
            }
        
        $where=[
            ['village_id','=',$village_id],
            ['park_sys_type','=',$park_sys_type],
            ['status','=',1],
        ];
        $parkPassageDb= new ParkPassage();
        $field='id,village_id,area_type,passage_area,garage_id';
        $passageListObj= $parkPassageDb->getList($where,$field);
        if(!$passageListObj || $passageListObj->isEmpty()){
            return ['status'=>false,'msg'=>'暂无车道数据，请先添加车道！','data'=>[]];
        }
        $list=$passageListObj->toArray();
        foreach ($list as $v){
            $garage_id=$v['garage_id'];
            if($garage_id<1 && $v['area_type']==2 && $v['passage_area']){
                $garage_id=intval($v['passage_area']);
            }
            if($garage_id<1){
                continue;
            }
            $data=[
                'opt_type'=>'hxt85_park_white_passage',
                'village_id'=>$v['village_id'],
                'passage_id'=>$v['id'],
                'garage_id'=>$garage_id,
                'car_number'=>'',
                'need_alarm'=>0,
                'operate_type'=>0,
                'park_sys_type'=>$park_sys_type,
                'car_id'=>$car_id,
            ];
            $this->parkPassageQueuePushToJob($data);
            fdump_api(['_Line_' . __LINE__,'data' => $data], 'hxPark/batchMonthCarToDev', 1);
        }
        return ['status'=>true,'msg'=>'操作成功（命令下发中）','data'=>[]];
    }
    public function batchMonthCarToDevStep ($param=array()){
        $opt_type=$param && isset($param['opt_type']) ? $param['opt_type']:'';
        $dateTime=date('H');
        fdump_api(['_Line_' . __LINE__,'param' => $param], 'hxPark/batchMonthCarToDevStep' .$dateTime, 1);
        if($opt_type=='hxt85_park_white_passage' && isset($param['passage_id'])){
            $park_sys_type='HTX85';
            $parkWhiteRecordDb=new ParkWhiteRecord();
            $db_house_village_parking_car=new HouseVillageParkingCar();
            $where=[
                ['village_id','=',$param['village_id']],
                ['car_position_id','>',0],
                ['end_time','>',time()],
            ];
            if(isset($param['car_id']) && $param['car_id']>0){
                $where[]=array('car_id','=',$param['car_id']);
            }
            $whereRaw = 'FIND_IN_SET(' . $param['garage_id'] . ',garage_id)';
            $car_list_obj=$db_house_village_parking_car->getNewList($where,'car_id,village_id,province,car_number,end_time,start_time,garage_id',0,10000,'car_id DESC',$whereRaw);
            $car_list=array();
            if (!empty($car_list_obj) && !$car_list_obj->isEmpty()){
                $car_list=$car_list_obj->toArray();
            }
            if(empty($car_list)){
                return true;
            }
            $nowtime=time();
            foreach ($car_list as $cvv){
                $car_end_time=$nowtime-100;
                if($cvv['end_time']>100){
                    $car_end_time=$cvv['end_time'];
                }
                $car_number=$cvv['province'].$cvv['car_number'];
                
                $start_time=$cvv['start_time'] ? $cvv['start_time']:$nowtime-3600;
                $white_arr=[
                    'village_id'=>$param['village_id'],
                    'park_sys_type'=>$park_sys_type,
                    'channel_id'=>$param['passage_id'],
                    'car_number'=>$car_number,
                    'enable'=>1,
                    'need_alarm'=>0,
                    'operate_type'=>isset($param['operate_type']) ? $param['operate_type']:0,
                    'start_time'=>$start_time,
                    'end_time'=>$car_end_time,
                    'add_time'=>$nowtime,
                ];
                $whereArr=array(['village_id','=',$param['village_id']]);
                $whereArr[]=array('park_sys_type','=',$park_sys_type);
                $whereArr[]=array('channel_id','=',$param['passage_id']);
                $whereArr[]=array('car_number','=',$car_number);
                $whereArr[]=array('operate_type','=',$white_arr['operate_type']);
                $findDataObj=$parkWhiteRecordDb->getFind($whereArr,'*');
                if($findDataObj && !$findDataObj->isEmpty()){
                    $findData=$findDataObj->toArray();
                    $saveData=array('enable'=>1,'start_time'=>$white_arr['start_time'],'end_time'=>$car_end_time,'add_time'=>$white_arr['add_time']);
                    $parkWhiteRecordDb->save_one(array('id'=>$findData['id']),$saveData);
                    fdump_api(['_Line_' . __LINE__,'findData' => $findData, 'saveData' => $saveData], 'hxPark/batchMonthCarToDevStep' .$dateTime, 1);
                }else{
                    $result=$parkWhiteRecordDb->add($white_arr);
                    fdump_api(['_Line_' . __LINE__,'data' => $white_arr,'result'=>$result], 'hxPark/batchMonthCarToDevStep' .$dateTime, 1);
                }
            }
        }
        return true;
    }
    /**
     * 查询抬竿指令
     * @author:zhubaodi
     * @date_time: 2021/12/8 13:36
     */
    public function open_gate($data = array())
    {
        $db_park_open_log = new ParkOpenLog();
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $db_park_passage = new ParkPassage();
        $db_house_village = new HouseVillage();
        $db_house_village_pay_order = new HouseVillagePayOrder();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_park_showscreen_log = new ParkShowscreenLog();
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
        $db_house_visitor = new HouseVillageVisitor();
        $db_house_village_parking_temp = new HouseVillageParkingTemp();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $now_time = time();
        $receiveTime = date('mdH');
        $res = [];
        $res['data'] = false;
        $res['showscreen'] = [];
        $serialno = isset($data['cam_id']) && $data['cam_id'] ? $data['cam_id'] : '';
        $data['serialno'] = $serialno;
        $cache_passage_key = 'park:hx:' . $serialno . ':serialno';
        //fdump_api(['data' => $data], 'hxPark/open_gate' . date('H'), 1);
        if (!empty($serialno)) {
            //查询设备
            if (!empty(cache($cache_passage_key))) {
                $passage_info = cache($cache_passage_key);
            } else {
                $passage_info = $db_park_passage->getFind(['device_number' => $data['serialno'], 'status' => 1]);
                if ($passage_info && !$passage_info->isEmpty()) {
                    $passage_info = $passage_info->toArray();
                }
                cache($cache_passage_key, $passage_info, 86400);
            }
            $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'HXT85';
            $carHandleGetInfoService = new CarHandleGetInfoService();
            $carHandleGetInfoService->setParkPassageHeart($serialno, $now_time, ':hxt85');
            $where = ['channel_id' => $data['serialno']];
            $log_info_obj = $db_park_open_log->get_one($where);
            $log_info = array();
            if (!empty($log_info_obj) && !$log_info_obj->isEmpty() && isset($log_info_obj['id'])) {
                $log_info = $log_info_obj->toArray();
                $db_park_open_log->delOne(['id' => $log_info['id']]);
                if ($log_info['open_type'] == 4) {
                    //关闸
                    $res = array('data' => false, 'pay_info' => false, 'is_close_park_passage' => 1);
                    return $res;
                }
            }
            //查询待同步的白名单月租车
            $white = [];
            $white['device_number'] = $data['serialno'];
            $white_list = $this->getWhitelist($white, $passage_info);
            if (!empty($white_list)) {
                $res['white_list'] = $white_list;
            }

            $showscreen_info = $db_park_showscreen_log->getFind($where);
            if (!empty($showscreen_info) && isset($showscreen_info['id'])) {
                $showscreen_info = $showscreen_info->toArray();
                $db_park_showscreen_log->delOne(['id' => $showscreen_info['id']]);
                $res['showscreen'] = $showscreen_info;
            }
            if (!empty($log_info) && isset($log_info['id'])) {

                if (!empty($log_info['car_number'])) {
                    // 记录车辆进入信息
                    $car_access_record = [];
                    $car_access_record['channel_id'] = $passage_info['id'];
                    $car_access_record['channel_number'] = $passage_info['channel_number'];
                    $car_access_record['channel_name'] = $passage_info['passage_name'];
                    $car_number = $log_info['car_number'];
                    $province = mb_substr($car_number, 0, 1);
                    $car_no = mb_substr($car_number, 1);
                    $carCachekey = 'village:' . $passage_info['village_id'] . ':car:' . $car_number;
                    if (!empty(cache($carCachekey))) {
                        $car_info = cache($carCachekey);
                        if (!isset($car_info['end_time']) || $car_info['end_time'] <= $now_time) {
                            // 缓存中取不出来车辆信息或者截止时间过期了 避免出现问题 及时查询下最新的信息并缓存
                            $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
                            cache($carCachekey, $car_info);
                        }
                    } else {
                        $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
                        cache($carCachekey, $car_info);
                    }
                    $user_name = empty($car_info['car_user_name']) ? '' : $car_info['car_user_name'];
                    $user_phone = empty($car_info['car_user_phone']) ? '' : $car_info['car_user_phone'];
                    $car_visitor_cache = 'village:' . $passage_info['village_id'] . ':car_visitor:' . $car_number;

                    if (!$user_name && !$user_phone && !empty(cache($car_visitor_cache))) {
                        $visitor_info = cache($car_visitor_cache);
                        $user_name = empty($visitor_info['visitor_name']) ? '' : $visitor_info['visitor_name'];
                        $user_phone = empty($visitor_info['visitor_phone']) ? '' : $visitor_info['visitor_phone'];
                        $uid = empty($visitor_info['uid']) ? '' : $visitor_info['uid'];
                    } elseif (!$user_name && !$user_phone) {
                        $visitor_info = $db_house_visitor->get_one(['village_id' => $passage_info['village_id'], 'car_id' => $car_number], 'id, visitor_name, visitor_phone, uid');
                        cache($car_visitor_cache, $visitor_info);
                        $user_name = empty($visitor_info['visitor_name']) ? '' : $visitor_info['visitor_name'];
                        $user_phone = empty($visitor_info['visitor_phone']) ? '' : $visitor_info['visitor_phone'];
                        $uid = empty($visitor_info['uid']) ? '' : $visitor_info['uid'];
                    } else {
                        $visitor_info = [];
                    }
                    if ($user_name) {
                        $car_access_record['user_name'] = $user_name;
                    }
                    if ($user_phone) {
                        $car_access_record['user_phone'] = $user_phone;
                    }
                    if (isset($uid) && $uid) {
                        $car_access_record['uid'] = $uid;
                    }

                    if ($passage_info['passage_direction'] == 1) {
                        if (!empty(cache($passage_info['village_id']))) {
                            $village_info = cache($passage_info['village_id']);
                        } else {
                            $village_info = $db_house_village->getOne($passage_info['village_id'], 'village_name');
                            cache($passage_info['village_id'], $village_info, 86400);
                        }
                        $park_data = [];
                        $park_data['car_number'] = $log_info['car_number'];
                        $park_data['in_time'] = $now_time;
                        $park_data['order_id'] = uniqid();
                        $park_data['in_channel_id'] = $passage_info['id'];
                        $park_data['is_paid'] = 0;
                        $park_data['park_id'] = $passage_info['village_id'];
                        $park_data['park_sys_type'] = $park_sys_type;
                        $park_data['park_name'] = $village_info['village_name'];
                        $starttime = time() - 60;
                        $endtime = time() + 30;
                        $park_where = [['car_number', '=', $log_info['car_number']], ['park_id', '=', $passage_info['village_id']], ['in_time', '>=', $starttime], ['in_time', '<=', $endtime], ['del_time', '=', 0],];
                        $park_info_car = $db_in_park->getOne1($park_where);
                        if (!$park_info_car || !isset($park_info_car['id'])) {
                            $insert_id = $db_in_park->insertOne($park_data);
                        } else {
                            $insert_id = $park_info_car['id'];
                        }
                        $car_access_record['business_type'] = 0;
                        $car_access_record['business_id'] = $passage_info['village_id'];
                        $car_access_record['car_number'] = $log_info['car_number'];
                        $car_access_record['accessType'] = 1;
                        $car_access_record['accessTime'] = $now_time;
                        if ($log_info['open_type'] == 1) {
                            $car_access_record['accessMode'] = 9;
                        } elseif ($log_info['open_type'] == 3) {
                            $car_access_record['accessMode'] = 7;
                        }
                        $car_access_record['park_sys_type'] = $park_sys_type;
                        $car_access_record['is_out'] = 0;
                        $car_access_record['park_id'] = $passage_info['village_id'];
                        $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                        $car_access_order_id = date('YmdHis') . rand(100, 999);
                        $car_access_record['order_id'] = $car_access_order_id;
                        $car_access_record['update_time'] = $now_time;
                        if (isset($insert_id) && !empty($insert_id)) {
                            $car_access_record['from_id'] = $insert_id;
                        }
                        $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                        if ($record_id > 0) {
                            $in_park_info = $db_in_park->getOne(array('id' => $insert_id), '*');
                            if ($in_park_info && isset($in_park_info['access_order_id']) && empty($in_park_info['access_order_id'])) {
                                $saveArr = array('access_record_id' => $record_id, 'access_order_id' => $car_access_order_id);
                                $db_in_park->saveOne(array('id' => $insert_id), $saveArr);
                            }
                            $tmpUpdateRecordArr = array('car_number' => $log_info['car_number'], 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 1, 'access_time' => time());
                            (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                        }
                    }
                    if ($passage_info['passage_direction'] == 0) {
                        if (!empty(cache($passage_info['village_id']))) {
                            $village_info = cache($passage_info['village_id']);
                        } else {
                            $village_info = $db_house_village->getOne($passage_info['village_id'], 'village_name');
                            cache($passage_info['village_id'], $village_info, 86400);
                        }
                        $whereInRecord = ['car_number' => $log_info['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0, 'del_time' => 0];
                        $in_park_pay_info = $db_in_park->getOne1($whereInRecord);
                        if ($in_park_pay_info && !$in_park_pay_info->isEmpty()) {
                            $in_park_pay_info = $in_park_pay_info->toArray();
                        } else {
                            $in_park_pay_info = array();
                        }
                        fdump_api(['car_number' => $car_number, 'in_park_pay_info' => $in_park_pay_info], 'hxPark/open_gate' . date('H'), 1);
                        if (!empty($in_park_pay_info) && isset($in_park_pay_info['id'])) {
                            $access_record_id = isset($in_park_pay_info['access_record_id']) && $in_park_pay_info['access_record_id'] ? $in_park_pay_info['access_record_id'] : 0;
                            $access_order_id = isset($in_park_pay_info['access_order_id']) && $in_park_pay_info['access_order_id'] ? $in_park_pay_info['access_order_id'] : '';
                            if (!empty($in_park_pay_info['pay_order_id'])) {
                                $park_temp_info = $db_house_village_parking_temp->getOne(['car_number' => $log_info['car_number'], 'village_id' => $passage_info['village_id'], 'order_id' => $in_park_pay_info['order_id'], 'is_paid' => 1], '*', 'price desc');
                                $pay_info = $db_house_new_pay_order->get_one(['summary_id' => $in_park_pay_info['pay_order_id'], 'is_paid' => 1]);
                                $in_record_id = isset($park_temp_info['in_record_id']) && $park_temp_info['in_record_id'] ? $park_temp_info['in_record_id'] : 0;
                                $pay_time = isset($park_temp_info['pay_time']) && $park_temp_info['pay_time'] ? $park_temp_info['pay_time'] : 0;
                                !$pay_time && $pay_time = isset($pay_info['pay_time']) && $pay_info['pay_time'] ? $pay_info['pay_time'] : time();
                                $is_paid = isset($park_temp_info['in_record_id']) && intval($park_temp_info['is_pay_scene']) === 0 ? 2 : 1;
                                if (!empty($pay_info)) {
                                    //写入车辆入场表
                                    $park_data = [];
                                    $park_data['out_time'] = $now_time;
                                    $park_data['is_out'] = 1;
                                    $park_data['is_paid'] = 1;
                                    $res_in = $db_in_park->saveOne(['id' => $in_park_pay_info['id'], 'car_number' => $log_info['car_number'], 'park_id' => $passage_info['village_id']], $park_data);
                                    $insert_id = $in_park_pay_info['id'];
                                    $park_where = [['car_number', '=', $log_info['car_number']], ['park_id', '=', $passage_info['village_id']], ['accessType', '=', 1], ['is_out', '=', 0], ['del_time', '=', 0],];
                                    $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                                    if ($park_info_car111 && isset($park_info_car111['record_id']) && $park_info_car111['record_id']) {
                                        $car_access_record['car_id'] = $park_info_car111['car_id'];
                                        $car_access_record['position_id'] = $park_info_car111['position_id'];
                                        $car_access_record['garage_id'] = $park_info_car111['garage_id'];
                                        $car_access_record['position_num'] = $park_info_car111['position_num'];
                                        if (isset($park_info_car111['order_id']) && $park_info_car111['order_id']) {
                                            $access_order_id = $park_info_car111['order_id'];
                                        }
                                        $in_record_id = $park_info_car111['record_id'];
                                    }
                                    $car_access_record['business_type'] = 0;
                                    $car_access_record['business_id'] = $passage_info['village_id'];
                                    $car_access_record['car_number'] = $log_info['car_number'];
                                    $car_access_record['accessType'] = 2;
                                    $car_access_record['coupon_id'] = isset($park_temp_info['coupon_id']) ? $park_temp_info['coupon_id'] : 0;
                                    $car_access_record['park_time'] = $pay_info['pay_time'] - $in_park_pay_info['in_time'];
                                    $car_access_record['total'] = $park_temp_info['price'];
                                    $car_access_record['accessTime'] = $now_time;
                                    if (isset($in_park_pay_info['out_image_big']) && $in_park_pay_info['out_image_big']) {
                                        $car_access_record['accessBigImage'] = $in_park_pay_info['out_image_big'];
                                    }
                                    if (isset($in_park_pay_info['out_image_small']) && $in_park_pay_info['out_image_small']) {
                                        $car_access_record['accessImage'] = $in_park_pay_info['out_image_small'];
                                    }
                                    if ($log_info['open_type'] == 2) {
                                        $car_access_record['accessMode'] = 4;
                                    } elseif ($log_info['open_type'] == 3) {
                                        if (empty($park_info_car111)) {
                                            $car_access_record['exception_type'] = 2;
                                        } else {

                                        }
                                        $car_access_record['accessMode'] = 7;
                                    }
                                    $car_access_record['park_sys_type'] = $park_sys_type;
                                    $car_access_record['is_out'] = 1;
                                    $car_access_record['park_id'] = $passage_info['village_id'];
                                    $car_access_record['garage_id'] = $passage_info['garage_id'];
                                    $car_access_record['flag_status'] = 'in_park:' . $in_park_pay_info['id'] . ';dt:' . date('Y-m-d H:i:s') . ';temp:' . $park_temp_info['id'];
                                    $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                                    $car_access_record['order_id'] = $access_order_id;
                                    //  $car_access_record['total'] = 0;
                                    // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                                    // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                                    //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                                    $car_access_record['pay_type'] = $this->pay_type[6];
                                    $car_access_record['update_time'] = $now_time;
                                    $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                                    if (isset($insert_id)) {
                                        $car_access_record['from_id'] = $insert_id;
                                    }
                                    if (isset($in_park_pay_info['pay_order_id']) && $in_park_pay_info['pay_order_id']) {
                                        $car_access_record['pay_order_id'] = $in_park_pay_info['pay_order_id'];
                                    } elseif ($in_record_id) {
                                        $in_record_info = $db_house_village_car_access_record->getOne(['record_id' => $in_record_id], 'pay_order_id');
                                        $pay_order_id = isset($in_record_info['pay_order_id']) && $in_record_info['pay_order_id'] ? intval($in_record_info['pay_order_id']) : 0;
                                        $car_access_record['pay_order_id'] = $pay_order_id;
                                    }
                                    if (isset($pay_time) && $pay_time > 1) {
                                        $car_access_record['pay_time'] = $pay_time;
                                        $car_access_record['is_paid'] = $is_paid;
                                    }
                                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                                    if ($park_info_car111 && isset($park_info_car111['position_id']) && $park_info_car111['position_id'] && $park_info_car111['car_id']) {
                                        //释放车位
                                        $whereArr = array('position_id' => $park_info_car111['position_id'], 'use_car_id' => $park_info_car111['car_id']);
                                        $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                                        $this->updateParkingPositionUse($whereArr, $saveArr, $log_info['car_number'], 'heartbeat_out_park');
                                    }
                                    if ($park_info_car111 && isset($park_info_car111['record_id'])) {
                                        $db_house_village_car_access_record->saveOne(['record_id' => $park_info_car111['record_id']], ['is_out' => 1]);
                                    }
                                    if ($record_id > 0) {

                                        $tmpUpdateRecordArr = array('car_number' => $log_info['car_number'], 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $access_order_id, 'access_type' => 2, 'access_time' => time());
                                        (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                                    }
                                }
                            } else {
                                $park_temp_info = $db_house_village_parking_temp->getOne(['car_number' => $log_info['car_number'], 'village_id' => $passage_info['village_id'], 'order_id' => $in_park_pay_info['order_id'], 'is_paid' => 1], '*', 'price desc');
                                $pay_time = isset($park_temp_info['pay_time']) && $park_temp_info['pay_time'] ? $park_temp_info['pay_time'] : time();
                                $is_paid = isset($park_temp_info['in_record_id']) && intval($park_temp_info['is_pay_scene']) === 0 ? 2 : 1;
                                if (!empty($park_temp_info)) {
                                    $in_record_id = isset($park_temp_info['in_record_id']) && $park_temp_info['in_record_id'] ? $park_temp_info['in_record_id'] : 0;
                                    //写入车辆入场表
                                    $park_data = [];
                                    $park_data['out_time'] = $now_time;
                                    $park_data['is_out'] = 1;
                                    $park_data['is_paid'] = 1;
                                    $res_in = $db_in_park->saveOne(['id' => $in_park_pay_info['id'], 'car_number' => $log_info['car_number'], 'park_id' => $passage_info['village_id']], $park_data);
                                    $insert_id = $in_park_pay_info['id'];
                                    $park_where = [['car_number', '=', $log_info['car_number']], ['park_id', '=', $passage_info['village_id']], ['accessType', '=', 1], ['is_out', '=', 0], ['del_time', '=', 0],];
                                    $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                                    if ($park_info_car111 && isset($park_info_car111['record_id']) && $park_info_car111['record_id']) {
                                        $car_access_record['car_id'] = $park_info_car111['car_id'];
                                        $car_access_record['position_id'] = $park_info_car111['position_id'];
                                        $car_access_record['garage_id'] = $park_info_car111['garage_id'];
                                        $car_access_record['position_num'] = $park_info_car111['position_num'];
                                        if (isset($park_info_car111['order_id']) && $park_info_car111['order_id']) {
                                            $access_order_id = $park_info_car111['order_id'];
                                        }
                                        $in_record_id = $park_info_car111['record_id'];
                                    }
                                    $car_access_record['business_type'] = 0;
                                    $car_access_record['business_id'] = $passage_info['village_id'];
                                    $car_access_record['car_number'] = $log_info['car_number'];
                                    $car_access_record['accessType'] = 2;
                                    $car_access_record['coupon_id'] = isset($park_temp_info['coupon_id']) ? $park_temp_info['coupon_id'] : 0;
                                    $car_access_record['park_time'] = $park_temp_info['pay_time'] - $in_park_pay_info['in_time'];
                                    $car_access_record['total'] = $park_temp_info['price'];
                                    $car_access_record['accessTime'] = $now_time;
                                    if (isset($in_park_pay_info['out_image_big']) && $in_park_pay_info['out_image_big']) {
                                        $car_access_record['accessBigImage'] = $in_park_pay_info['out_image_big'];
                                    }
                                    if (isset($in_park_pay_info['out_image_small']) && $in_park_pay_info['out_image_small']) {
                                        $car_access_record['accessImage'] = $in_park_pay_info['out_image_small'];
                                    }
                                    if ($log_info['open_type'] == 2) {
                                        $car_access_record['accessMode'] = 4;
                                    } elseif ($log_info['open_type'] == 3) {
                                        if (empty($park_info_car111)) {
                                            $car_access_record['exception_type'] = 2;
                                        } else {
                                            $db_house_village_car_access_record->saveOne(['record_id' => $park_info_car111['record_id']], ['is_out' => 1]);
                                        }
                                        $car_access_record['accessMode'] = 7;
                                    }
                                    $car_access_record['park_sys_type'] = $park_sys_type;
                                    $car_access_record['is_out'] = 1;
                                    $car_access_record['park_id'] = $passage_info['village_id'];
                                    $car_access_record['garage_id'] = $passage_info['garage_id'];
                                    $car_access_record['flag_status'] = 'in_park:' . $in_park_pay_info['id'] . ';dt:' . date('Y-m-d H:i:s') . ';temp:' . $park_temp_info['id'];
                                    $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                                    $car_access_record['order_id'] = $access_order_id;
                                    //  $car_access_record['total'] = 0;
                                    // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                                    // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                                    //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                                    $car_access_record['pay_type'] = $this->pay_type[6];
                                    $car_access_record['update_time'] = $now_time;
                                    $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                                    if (isset($insert_id)) {
                                        $car_access_record['from_id'] = $insert_id;
                                    }
                                    if ($in_record_id) {
                                        $in_record_info = $db_house_village_car_access_record->getOne(['record_id' => $in_record_id], 'pay_order_id');
                                        $pay_order_id = isset($in_record_info['pay_order_id']) && $in_record_info['pay_order_id'] ? intval($in_record_info['pay_order_id']) : 0;
                                        $car_access_record['pay_order_id'] = $pay_order_id;
                                    }
                                    if ($pay_time > 1) {
                                        $car_access_record['pay_time'] = $pay_time;
                                        $car_access_record['is_paid'] = $is_paid;
                                    }
                                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                                    if ($park_info_car111 && isset($park_info_car111['position_id']) && $park_info_car111['position_id'] && $park_info_car111['car_id']) {
                                        //释放车位
                                        $whereArr = array('position_id' => $park_info_car111['position_id'], 'use_car_id' => $park_info_car111['car_id']);
                                        $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                                        $this->updateParkingPositionUse($whereArr, $saveArr, $log_info['car_number'], 'heartbeat_out_park');
                                    }
                                    if ($park_info_car111 && isset($park_info_car111['record_id'])) {
                                        $db_house_village_car_access_record->saveOne(['record_id' => $park_info_car111['record_id']], ['is_out' => 1]);
                                    }
                                    if ($record_id > 0) {
                                        $tmpUpdateRecordArr = array('car_number' => $log_info['car_number'], 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $access_order_id, 'access_type' => 2, 'access_time' => time());
                                        (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                                    }
                                }
                            }
                        } else {
                            $insert_id = 0;
                            $access_order_id = '';
                            $park_where = [['car_number', '=', $log_info['car_number']], ['park_id', '=', $passage_info['village_id']], ['accessType', '=', 1], ['is_out', '=', 0], ['del_time', '=', 0],];
                            $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                            if ($park_info_car111 && isset($park_info_car111['record_id']) && $park_info_car111['record_id']) {
                                $car_access_record['car_id'] = $park_info_car111['car_id'];
                                $car_access_record['position_id'] = $park_info_car111['position_id'];
                                $car_access_record['garage_id'] = $park_info_car111['garage_id'];
                                $car_access_record['position_num'] = $park_info_car111['position_num'];
                                $access_order_id = $park_info_car111['order_id'];
                            }
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $log_info['car_number'];
                            $car_access_record['accessType'] = 2;
                            $car_access_record['accessTime'] = $now_time;
                            if ($log_info['open_type'] == 2) {
                                $car_access_record['accessMode'] = 4;
                            } elseif ($log_info['open_type'] == 3) {
                                if (empty($park_info_car111)) {
                                    $car_access_record['exception_type'] = 2;
                                } else {
                                    $db_house_village_car_access_record->saveOne(['record_id' => $park_info_car111['record_id']], ['is_out' => 1]);
                                }
                                $car_access_record['accessMode'] = 7;
                            }
                            $car_access_record['park_sys_type'] = $park_sys_type;
                            $car_access_record['is_out'] = 1;
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                            $car_access_record['order_id'] = $access_order_id;

                            $car_access_record['total'] = 0;
                            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                            $car_access_record['pay_type'] = $this->pay_type[5];
                            $car_access_record['update_time'] = $now_time;
                            $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                            if (isset($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }
                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                            if ($park_info_car111 && isset($park_info_car111['position_id']) && $park_info_car111['position_id'] && $park_info_car111['car_id']) {
                                //释放车位
                                $whereArr = array('position_id' => $park_info_car111['position_id'], 'use_car_id' => $park_info_car111['car_id']);
                                $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                                $this->updateParkingPositionUse($whereArr, $saveArr, $log_info['car_number'], 'heartbeat_out_park');
                            }
                            if ($park_info_car111 && isset($park_info_car111['record_id'])) {
                                $db_house_village_car_access_record->saveOne(['record_id' => $park_info_car111['record_id']], ['is_out' => 1]);
                            }
                            if ($record_id > 0) {
                                $tmpUpdateRecordArr = array('car_number' => $log_info['car_number'], 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $access_order_id, 'access_type' => 2, 'access_time' => time());
                                (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                            }
                        }
                    }
                }

                $res['data'] = true;

            }
            //fdump_api([$res],'hxPark/open_gate' . date('H'),1);
            $res['pay_info'] = false;
        }
        return $res;
    }

    /**
     * 查询白名单列表
     * @author:zhubaodi
     * @date_time: 2022/6/24 17:37
     */
    public function getWhitelist($data, $passage_info = [])
    {
        $db_park_white_record = new ParkWhiteRecord();
        $record_info = [];
        if (!empty($data['device_number'])) {
            if (empty($passage_info)) {
                $carHandleGetInfoService = new CarHandleGetInfoService();
                $passage_info = $carHandleGetInfoService->getParkPassageInfo($data['device_number']);
            }
            if (!empty($passage_info)) {
                $record_info = $db_park_white_record->getFind(['village_id' => $passage_info['village_id'], 'channel_id' => $passage_info['id']], '*');
                if (!empty($record_info) && !$record_info->isEmpty()) {
                    $record_info = $record_info->toArray();
                    $db_park_white_record->delOne(['id' => $record_info['id']]);
                }
            }
        }
        return $record_info;
    }

    /**
     * 纪录遥控上报信息
     * @author:zhubaodi
     * @date_time: 2022/12/5 9:33
     */
    public function addSerialData($data)
    {
        $db_park_passage = new ParkPassage();
        $db_house_village_park_config = new HouseVillageParkConfig();
        $db_park_plateresult_log = new ParkPlateresultLog();
        $db_park_serial_log = new ParkSerialLog();
        $db_house_village = new HouseVillage();

        if (empty($data['serialno'])) {
            return false;
        }
        $passage_info = $db_park_passage->getFind(['device_number' => $data['serialno'], 'status' => 1]);
        if (empty($passage_info) || $passage_info->isEmpty()) {
            return false;
        }
        $passage_info = $passage_info->toArray();
        fdump_api(['data_add' => $data, 'passage_info' => $passage_info], 'hxPark/addSerialData' . $data['serialno'], 1);

        $garage_id = 0;
        if (isset($passage_info['garage_id'])) {
            $garage_id = $passage_info['garage_id'];
        }
        if ($garage_id < 1 && $passage_info['area_type'] == 2 && !empty($passage_info['passage_area'])) {
            $garage_id = intval($passage_info['passage_area']);
        }
        $garage_id = $garage_id > 0 ? $garage_id : 0;
        $device_numberArr = array();
        $serialnoArr = array();
        if ($passage_info['passage_type'] == 2) {
            $passage_info_list = $db_park_passage->getList(['village_id' => $passage_info['village_id'], 'status' => 1, 'park_sys_type' => $passage_info['park_sys_type'], 'passage_type' => 2]);
            if ($passage_info_list && !$passage_info_list->isEmpty()) {
                $passage_info_list = $passage_info_list->toArray();
                foreach ($passage_info_list as $kk => $vv) {
                    $device_numberArr[$vv['device_number']] = $vv;
                    $serialnoArr[] = $vv['device_number'];
                }
            }
        }
        $no_code = $data_code = $operation_code = $function_code = [];
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $house_village_park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id, $passage_info['village_id']);
        if (empty($house_village_park_config)) {
            return false;
        }
        $park_sys_type = $passage_info['park_sys_type'] ? $passage_info['park_sys_type'] : 'HXT85';
        $whereArr = array();
        $timeTmp = time() - 7200;  //2小时前数据
        $whereArr[] = ['channel_id', '=', $data['serialno']];
        $whereArr[] = ['village_id', '=', $passage_info['village_id']];
        $whereArr[] = ['add_time', '<=', $timeTmp];
        $db_park_serial_log->del_datas($whereArr); //删掉2小时前数据

        $whereArr = array();
        $timeTmp = time() - 3600;  //1小时内
        $whereArr[] = ['channel_id', '=', $data['serialno']];
        $whereArr[] = ['village_id', '=', $passage_info['village_id']];
        $whereArr[] = ['content_de', '=', $data['msg']];
        $whereArr[] = ['add_time', '>', $timeTmp];
        $haveResObj = $db_park_serial_log->get_one($whereArr);
        $ishaveExist = false;
        $timeTmp = time() - 10;
        if ($haveResObj && !$haveResObj->isEmpty()) {
            $haveRes = $haveResObj->toArray();
            if (!empty($haveRes) && $haveRes['add_time'] >= $timeTmp) {
                //10秒内多次提交同样的数据过滤掉
                fdump_api(['msg' => '5秒内多次提交同样的数据过滤掉', 'haveRes' => $haveRes], 'hxPark/addSerialData' . $data['serialno'], 1);
                $ishaveExist = true;
            }
        }
        $village_info = $db_house_village->getOne($passage_info['village_id'], 'village_name');
        $arr = ['channel_id' => $data['serialno'], 'content' => $data['content'], 'content_de' => $data['msg'], 'village_id' => $passage_info['village_id'], 'park_sys_type' => $house_village_park_config['park_sys_type'], 'add_time' => time()];
        $res = $db_park_serial_log->add($arr);
        /*
        if (stripos($data['msg'], 'AFAB') !== false) {
            $msg = str_replace('AFAB', 'AF,AB', $data['msg']);
            $arr_msg = explode(',', $msg);
            if (!empty($arr_msg)) {
                foreach ($arr_msg as $v) {
                    $function_code[] = substr($v, 6, 2);
                    $operation_code[] = substr($v, 8, 2);
                    $data_code[] = substr($v, 10, 2);
                    $no_code[] = substr($v, 12, 8);
                }
            }
        } else {
            $function_code[] = substr($data['msg'], 6, 2);
            $operation_code[] = substr($data['msg'], 8, 2);
            $data_code[] = substr($data['msg'], 10, 2);
            $no_code[] = substr($data['msg'], 12, 8);
        }
        if (!in_array('78', $function_code) || !in_array('01', $operation_code) || !in_array('06', $data_code)) {
            return false;
        }
        */
        if ($ishaveExist) {
            //10秒内多次提交同样的数据过滤掉
            return false;
        }
        $optcode = '';
        /*
        if (!empty($no_code)) {
            foreach ($no_code as $v) {
                if ($v != '00000000') {
                    $optcode = base_convert($v, 16, 10);
                    break;
                }
            }
        }
        */
        $where = [['channel_id', '=', $data['serialno']], ['village_id', '=', $passage_info['village_id']], ['park_sys_type', '=', $house_village_park_config['park_sys_type']], ['car_number', '<>', '_无_'],];
        if (!empty($serialnoArr)) {
            $where = [['channel_id', 'in', $serialnoArr], ['village_id', '=', $passage_info['village_id']], ['park_sys_type', '=', $house_village_park_config['park_sys_type']], ['car_number', '<>', '_无_'],];
        }

        $plateresult = $db_park_plateresult_log->get_one($where);
        if (!isset($plateresult['car_number']) || !$plateresult['car_number']) {
            return false;
        }

        if ($device_numberArr && isset($device_numberArr[$plateresult['channel_id']])) {
            $passage_info = $device_numberArr[$plateresult['channel_id']];
        }
        $data_add = ['id' => $passage_info['id'], 'village_id' => $passage_info['village_id'], 'car_number' => '无', 'accessTime' => date('Y-m-d H:i:s'), 'user_phone' => '', 'user_name' => '', 'village_name' => $village_info['village_name'], 'img' => '', 'price' => 0, 'park_time' => 0, 'optcode' => $optcode,];
        if (!empty($plateresult) && ($plateresult['add_time'] + 300) > time()) {
            if ($passage_info['passage_direction'] == 0) {
                $temp = ['car_number' => $plateresult['car_number'], 'village_id' => $passage_info['village_id'], 'device_number' => $data['serialno'], 'channel_number' => $passage_info['channel_number'], 'garage_id' => $garage_id, 'uid' => '', 'car_end_time' => 0, 'temporary_end_time' => 0, 'park_sys_type' => $park_sys_type,];
                try {
                    $pay = $this->get_temp_pay($temp);
                } catch (\Exception $e) {
                    $txt = $e->getMessage();
                    fdump_api(['msg' => '错误', '$txt' => $txt], 'hxPark/addSerialData' . $data['serialno'], 1);
                    $pay = [];
                }
            } else {
                $pay = [];
            }
            if (empty($pay)) {
                $price = 0;
                $park_time = 0;
            } else {
                $price = $pay['pay_money'];
                $park_time = $pay['park_time'];
            }
            $data_add['price'] = $price;
            $data_add['park_time'] = $park_time;
            $data_add['car_number'] = $plateresult['car_number'];
        }
        if (!$data_add['price'] || $data_add['price'] <= 0) {
            $db_in_park = new InPark();
            $db_house_village_parking_temp = new HouseVillageParkingTemp();
            $whereInPark = ['car_number' => $plateresult['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0, 'del_time' => 0];
            $in_park_pay_info = $db_in_park->getOne1($whereInPark, 'order_id');
            if (!empty($in_park_pay_info)) {
                $order_id = isset($in_park_pay_info['order_id']) && $in_park_pay_info['order_id'] ? $in_park_pay_info['order_id'] : 0;
                $park_temp_id = isset($pay) && isset($pay['park_temp_id']) && $pay['park_temp_id'] ? $pay['park_temp_id'] : 0;
                $whereTemp = [];
                $whereTemp[] = ['car_number', '=', $plateresult['car_number']];
                $whereTemp[] = ['village_id', '=', $passage_info['village_id']];
                $whereTemp[] = ['order_id', '=', $order_id];
                $whereTemp[] = ['is_paid', '=', 0];
                if ($park_temp_id) {
                    $whereTemp[] = ['id', '<>', $park_temp_id];
                }
                $park_temp_info = $db_house_village_parking_temp->getOne($whereTemp);
                $total = isset($park_temp_info['total']) && $park_temp_info['total'] ? $park_temp_info['total'] : 0;
                $data_add['price'] = $total;
            }
        }
        $data_add['funcFrom'] = 'addSerialData';
        fdump_api(['msg' => '相关信息', 'data_add' => $data_add], 'hxPark/addSerialData' . $data['serialno'], 1);
        try {
            $res1 = $this->addParkInfo($data_add);
        } catch (\Exception $e) {
            $txt = $e->getMessage();
            $res1 = 0;
            fdump_api(['msg' => '错误2', '$txt' => $txt], 'hxPark/addSerialData' . $data['serialno'], 1);
        }
        if ($res1) {
            $showscreen_data = [];
            $showscreen_data['passage'] = $passage_info;
            $showscreen_data['village_id'] = $passage_info['village_id'];
            $showscreen_data['car_number'] = $plateresult['car_number'];
            $showscreen_data['channel_id'] = $data['serialno'];
            $showscreen_data['voice_content'] = 'v11';
            $showscreen_data['content'] = '岗亭人工确认，请通行';
            $showscreen_data['car_type'] = 'temporary_type';
        }
        return true;
    }

    public function addParkInfo($data = array())
    {
        $serialTime = isset($data['serialTime']) ? $data['serialTime'] : date('YmdH');
        fdump_api(['line' => __LINE__, 'data' => $data], 'hxPark/addParkInfo' . $serialTime, true);
        $db_park_passage = new ParkPassage();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
        $db_house_village_parking_temp = new HouseVillageParkingTemp();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $passage_info = $db_park_passage->getFind(['id' => $data['id'], 'status' => 1, 'village_id' => $data['village_id']], '*');
        if (empty($passage_info) || $passage_info->isEmpty()) {
            fdump_api(['line' => __LINE__, 'data' => $data, 'errMsg' => "当前通道设备不存在"], 'hxPark/addParkInfo' . $serialTime, true);
            throw new Exception("当前通道设备不存在");
        }
        $passage_info = $passage_info->toArray();
        $db_house_village_parking_garage = new HouseVillageParkingGarage();
        $garage_id = 0;
        if (!empty($passage_info['passage_area']) && $passage_info['area_type'] == 2) {
            $garage_id = $passage_info['passage_area'];
        }
        if (isset($passage_info['garage_id']) && $passage_info['garage_id'] > 0) {
            $garage_id = $passage_info['garage_id'];
        }
        $garage_info = array();
        $is_real_garage = 1;
        if ($garage_id > 0) {
            $garage_info = $db_house_village_parking_garage->getOne(['garage_id' => $garage_id]);
            if ($garage_info && !$garage_info->isEmpty()) {
                $garage_info = $garage_info->toArray();
                if ($garage_info['fid'] > 0 && $garage_info['fid'] == $garage_info['garage_id']) {
                    $garage_info['fid'] = 0;
                }

                if ($garage_info['fid'] > 0 && $garage_info['fid'] != $garage_info['garage_id']) {
                    $is_real_garage = 0;
                }
            }
        }
        $park_sys_type = $passage_info['park_sys_type'] ? $passage_info['park_sys_type'] : 'HXT85';
        $now_time = time();
        $record_id = 0;
        //查询车辆到期时间
        $car_info = array();
        if (!empty($data['car_number']) && $data['car_number'] != '无') {
            $province = mb_substr($data['car_number'], 0, 1);
            $car_no = mb_substr($data['car_number'], 1);
            $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
            if ($car_info && !$car_info->isEmpty()) {
                $car_info = $car_info->toArray();
            }
        }
        $nowtime = time();
        //月卡车通行
        if (!empty($car_info) && $car_info['end_time'] > $nowtime) {
            $accessMode = 5;
        } else {
            $accessMode = 7;
        }
        $car_number = isset($data['car_number']) && $data['car_number'] ? $data['car_number'] : '';
        $village_id = isset($passage_info['village_id']) && $passage_info['village_id'] ? $passage_info['village_id'] : 0;
        $park_passage_id = isset($passage_info['id']) && $passage_info['id'] ? $passage_info['id'] : 0;
        $device_number = isset($passage_info['device_number']) && $passage_info['device_number'] ? $passage_info['device_number'] : 0;
        $accessTime = strtotime($data['accessTime']);
        $from_id = 0;
        $access_record_id = 0;
        $flow_other_time = 0;
        // 记录车辆进入信息
        $car_access_record = [];
        $car_access_record['channel_id'] = $passage_info['id'];
        $car_access_record['channel_number'] = $passage_info['channel_number'];
        $car_access_record['channel_name'] = $passage_info['passage_name'];
        $car_access_record['user_name'] = empty($data['user_name']) ? '' : $data['user_name'];
        $car_access_record['user_phone'] = empty($data['user_phone']) ? '' : $data['user_phone'];
        $car_access_record['business_type'] = 0;
        $car_access_record['business_id'] = $passage_info['village_id'];
        $car_access_record['car_number'] = $car_number;
        $car_access_record['accessTime'] = $accessTime;
        $car_access_record['park_id'] = $passage_info['village_id'];
        $car_access_record['park_name'] = $data['village_name'] ? $data['village_name'] : '';
        $car_access_record['park_sys_type'] = $passage_info['park_sys_type'];
        $car_access_record['accessImage'] = $data['img'];
        if (isset($data['optcode'])) {
            $car_access_record['optname'] = $data['optcode'];
        }
        fdump_api(['line' => __LINE__, 'car_access_record' => $car_access_record, 'passage_direction' => $passage_info['passage_direction']], 'hxPark/addParkInfo' . $serialTime, true);
        //入场
        if ($passage_info['passage_direction'] == 1) {
            $car_access_record['garage_id'] = $garage_id;
            $rightPosition = array();
            if ($car_info && isset($car_info['car_id'])) {
                $rightPosition = $this->getCarRightInPosition($car_info, $passage_info['village_id'], $garage_id);
                fdump_api(['car_info' => $car_info, 'rightPosition' => $rightPosition], 'hxPark/addParkInfo' . $serialTime, 1);
                if (!empty($rightPosition)) {
                    if ($rightPosition['start_time'] <= $now_time && $rightPosition['end_time'] >= $car_info['end_time']) {
                        $car_info['end_time'] = $rightPosition['end_time'];
                    }
                    if ($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids']) && $rightPosition['start_time'] > $now_time) {
                        //车位时间还没开始
                        $car_info['end_time'] = 0;
                    }
                    if (isset($rightPosition['position_id']) && $rightPosition['position_id']) {
                        $car_access_record['position_id'] = $rightPosition['position_id'];
                    }
                    if (isset($rightPosition['garage_id']) && $rightPosition['garage_id']) {
                        $car_access_record['garage_id'] = $rightPosition['garage_id'];
                    }
                    if (isset($rightPosition['position_num']) && $rightPosition['position_num']) {
                        $car_access_record['position_num'] = $rightPosition['position_num'];
                    }
                } elseif (empty($rightPosition)) {
                    //没有可用车位
                    $bind_position_ids = array();
                    if ($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])) {
                        //新的绑定车位 多车多位逻辑
                        $bind_position_ids = explode(',', $car_info['bind_position_ids']);
                        $car_info['end_time'] = 0;   //没有可用车位时
                    }
                    if ($car_info && $car_info['car_position_id']) {
                        $bind_position_ids[] = $car_info['car_position_id'];
                    }
                    if (empty($bind_position_ids)) {
                        //标记一下无车位进场临时车
                        $car_access_record['flag_status'] = 'temporaryCarNoPosition';
                        $car_access_record['park_car_type'] = 'temporaryCarNoPosition';
                        $car_access_record['car_type'] = 'temporaryCarNoPosition';
                    }
                }
                $car_access_record['car_id'] = $car_info['car_id'];
                if ($car_info['end_time'] > $now_time) {
                    $accessMode = 5;
                } else {
                    $accessMode = 7;
                }
            } else {
                $car_access_record['flag_status'] = 'temporaryCarNoPosition';
                $car_access_record['park_car_type'] = 'temporaryCarNoPosition';
                $car_access_record['car_type'] = 'temporaryCarNoPosition';
            }
            $order_id = md5($device_number . $car_number . $nowtime);
            $park_data = [];
            $park_data['car_number'] = $car_number;
            $park_data['in_time'] = $accessTime;
            $park_data['order_id'] = $order_id;
            $park_data['in_channel_id'] = $passage_info['id'];
            $park_data['is_paid'] = 0;
            $park_data['park_id'] = $passage_info['village_id'];
            $park_data['park_sys_type'] = $passage_info['park_sys_type'];
            $park_data['park_name'] = $data['village_name'];
            $starttime = time() - 60;
            $endtime = time() + 10;
            $park_where = [['car_number', '=', $data['car_number']], ['park_id', '=', $passage_info['village_id']], ['in_time', '>=', $starttime], ['in_time', '<=', $endtime], ['del_time', '=', 0],];
            $park_info_car = $db_in_park->getOne1($park_where);
            fdump_api(['line' => __LINE__, 'park_data' => $park_data, 'park_where' => $park_where], 'hxPark/addParkInfo' . $serialTime, true);
            if (empty($park_info_car)) {
                $insert_id = $db_in_park->insertOne($park_data);
                fdump_api(['line' => __LINE__, 'insert_id' => $insert_id], 'hxPark/addParkInfo' . $serialTime, true);
                $from_id = $insert_id;
            } else {
                $from_id = isset($park_info_car['id']) && intval($park_info_car['id']) > 0 ? intval($park_info_car['id']) : 0;
            }

            $order_id = date('YmdHis') . rand(100000, 999999);
            $car_access_record['accessType'] = 1;
            $car_access_record['accessMode'] = $accessMode;
            $car_access_record['exception_type'] = 1;
            $car_access_record['is_out'] = 0;
            $car_access_record['order_id'] = $order_id;
            $car_access_record['update_time'] = $now_time;
            if (isset($insert_id) && !empty($insert_id)) {
                $car_access_record['from_id'] = $insert_id;
            }
            fdump_api(['line' => __LINE__, 'car_access_record' => $car_access_record], 'hxPark/addParkInfo' . $serialTime, true);
            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
            if ($record_id > 0 && $is_real_garage > 0) {
                //将此条之前的 都改掉
                $wherexArr = [];
                $wherexArr[] = ['record_id', '<', $record_id];
                $wherexArr[] = ['car_number', '=', $data['car_number']];
                $wherexArr[] = ['is_out', '=', 0];
                $wherexArr[] = ['business_id', '=', $passage_info['village_id']];
                $db_house_village_car_access_record->saveOne($wherexArr, ['is_out' => 1, 'update_time' => $now_time]);
                fdump_api(['line' => __LINE__, 'msg' => '更改出场', 'wherexArr' => $wherexArr, 'now_time' => $now_time], 'hxPark/addParkInfo' . $serialTime, true);
            }
            $access_record_id = $record_id;
            if (!empty($rightPosition) && $accessMode == 5) {
                $whereArr = array('position_id' => $rightPosition['position_id']);
                $saveArr = array('use_car_id' => $car_info['car_id'], 'use_start_time' => $now_time, 'use_car_number' => $data['car_number'], 'access_record_id' => $record_id);
                $this->updateParkingPositionUse($whereArr, $saveArr, $data['car_number'], 'addParkInfo_in_park');

            }
        }
        //出场
        $park_time = 0;
        if ($passage_info['passage_direction'] == 0) {
            $outRightPosition = array();
            if ($car_info && isset($car_info['car_id'])) {
                $outRightPosition = $this->getCarRightOutPosition($car_info, $passage_info['village_id'], $garage_id, $data['car_number']);
                fdump_api(['car_info' => $car_info, 'rightPosition' => $outRightPosition], 'hxPark/addParkInfo' . $serialTime, 1);
                if (!empty($outRightPosition)) {
                    if ($outRightPosition['start_time'] < $now_time && $outRightPosition['end_time'] > $car_info['end_time']) {
                        $car_info['end_time'] = $outRightPosition['end_time'];
                    }
                    if (isset($outRightPosition['temporary_end_time']) && $outRightPosition['temporary_end_time'] > 0) {
                        $car_info['end_time'] = $outRightPosition['temporary_end_time'];
                    }
                    if ($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids']) && $outRightPosition['start_time'] > $now_time) {
                        //车位时间还没开始
                        $car_info['end_time'] = 0;
                    }
                    if (isset($outRightPosition['position_id']) && $outRightPosition['position_id']) {
                        $car_access_record['position_id'] = $outRightPosition['position_id'];
                    }
                    if (isset($outRightPosition['garage_id']) && $outRightPosition['garage_id']) {
                        $car_access_record['garage_id'] = $outRightPosition['garage_id'];
                    }
                    if (isset($outRightPosition['position_num']) && $outRightPosition['position_num']) {
                        $car_access_record['position_num'] = $outRightPosition['position_num'];
                    }
                } else {
                    $car_info['end_time'] = 0;   //没有可用车位时
                }
                $car_access_record['car_id'] = $car_info['car_id'];
            }
            $order_id = '';
            $whereInRecord = ['car_number' => $data['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0, 'del_time' => 0];
            $in_park_pay_info_obj = $db_in_park->getOne1($whereInRecord);
            $in_park_pay_info = array();
            if ($in_park_pay_info_obj && !$in_park_pay_info_obj->isEmpty()) {
                $in_park_pay_info = $in_park_pay_info_obj->toArray();
            }
            fdump_api(['line' => __LINE__, 'msg' => '出场', 'in_park_pay_info' => $in_park_pay_info], 'hxPark/addParkInfo' . $serialTime, true);
            if (!empty($in_park_pay_info)) {
                $in_time = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                $in_time > 0 && $in_time < $now_time && $park_time = $now_time - $in_time;
                if (!empty($in_park_pay_info['pay_order_id'])) {
                    fdump_api(['line' => __LINE__, 'msg' => '存在支付订单记录', 'in_park_pay_info' => $in_park_pay_info], 'hxPark/addParkInfo' . $serialTime, true);
                    //写入车辆入场表
                    $park_data = [];
                    $park_data['out_time'] = $accessTime;
                    $park_data['is_out'] = 1;
                    $park_data['is_paid'] = 1;
                    $park_time > 0 && $park_data['park_time'] = $park_time;
                    $whereInOut = ['car_number' => $data['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0];
                    $res_in = $db_in_park->saveOne($whereInOut, $park_data);
                    fdump_api(['line' => __LINE__, 'msg' => '写入车辆入场表', 'whereInOut' => $whereInOut, 'park_data' => $park_data, 'res_in' => $res_in], 'hxPark/addParkInfo' . $serialTime, true);
                    $from_id = 0;
                    $insert_id = 0;
                    $park_where = [['car_number', '=', $data['car_number']], ['park_id', '=', $passage_info['village_id']], ['accessType', '=', 1], ['is_out', '=', 0], ['del_time', '=', 0],];
                    $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                    $car_access_record['accessType'] = 2;
                    $car_access_record['park_time'] = $data['park_time'] > 0 ? $data['park_time'] : $park_time;
                    $car_access_record['total'] = $data['price'];
                    $car_access_record['accessMode'] = $accessMode;
                    if (empty($park_info_car111)) {
                        $car_access_record['exception_type'] = 2;
                    } else {
                        fdump_api(['line' => __LINE__, 'msg' => '查询对应出场记录', 'record_id' => $park_info_car111['record_id']], 'hxPark/addParkInfo' . $serialTime, true);
                        $db_house_village_car_access_record->saveOne(['record_id' => $park_info_car111['record_id']], ['is_out' => 1]);
                    }
                    $car_access_record['is_out'] = 1;
                    $car_access_record['is_paid'] = 1;
                    if ($park_info_car111 && $park_info_car111['order_id']) {
                        $car_access_record['order_id'] = $park_info_car111['order_id'];
                    } else {
                        $car_access_record['order_id'] = date('YmdHis') . rand(100000, 999999);
                    }
                    $order_id = $car_access_record['order_id'];
                    $car_access_record['pay_type'] = $this->pay_type[0];
                    $car_access_record['update_time'] = $now_time;
                    $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                    if (isset($insert_id)) {
                        $car_access_record['from_id'] = $insert_id;
                    }
                    $park_time > 0 && $car_access_record['park_time'] = $park_time;
                    fdump_api(['line' => __LINE__, 'msg' => '记录出场', 'car_access_record' => $car_access_record], 'hxPark/addParkInfo' . $serialTime, true);
                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                    if (!empty($outRightPosition)) {
                        //释放车位
                        $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                        $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                        $this->updateParkingPositionUse($whereArr, $saveArr, $data['car_number'], 'addParkInfo_out_park');
                    }
                    if ($record_id > 0 && $is_real_garage > 0) {
                        //将此条之前的 都改掉
                        $wherexArr = [];
                        $wherexArr[] = ['record_id', '<', $record_id];
                        $wherexArr[] = ['car_number', '=', $data['car_number']];
                        $wherexArr[] = ['is_out', '=', 0];
                        $wherexArr[] = ['business_id', '=', $passage_info['village_id']];
                        $db_house_village_car_access_record->saveOne($wherexArr, ['is_out' => 1, 'update_time' => $now_time]);
                        fdump_api(['line' => __LINE__, 'msg' => '更改之前记录为已出场', 'record_id' => $record_id, 'wherexArr' => $wherexArr, 'whenow_timerexArr' => $now_time], 'hxPark/addParkInfo' . $serialTime, true);

                    }
                    $access_record_id = $record_id;

                } else {

                    $where_park_temp = ['car_number' => $data['car_number'], 'village_id' => $passage_info['village_id'], 'order_id' => $in_park_pay_info['order_id'], 'is_paid' => 1];
                    $park_temp_info_obj = $db_house_village_parking_temp->getOne($where_park_temp);
                    $park_temp_info = array();
                    if ($park_temp_info_obj && !$park_temp_info_obj->isEmpty()) {
                        $park_temp_info = $park_temp_info_obj->toArray();
                    }
                    fdump_api(['line' => __LINE__, 'msg' => '临时中间表数据', 'where_park_temp' => $where_park_temp, 'park_temp_info' => $park_temp_info], 'hxPark/addParkInfo' . $serialTime, true);
                    if (!empty($park_temp_info)) {
                        //写入车辆入场表
                        $duration = isset($park_temp_info['duration']) ? intval($park_temp_info['duration']) : 0;
                        $park_data = [];
                        $park_data['out_time'] = $accessTime;
                        $park_data['is_out'] = 1;
                        $park_data['is_paid'] = 1;
                        $duration > 0 && $park_time = $duration * 60;
                        $park_time > 0 && $park_data['park_time'] = $park_time;
                        $whereInOut = ['car_number' => $data['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0];
                        $res_in = $db_in_park->saveOne($whereInOut, $park_data);
                        fdump_api(['line' => __LINE__, 'msg' => '写入车辆入场表', 'whereInOut' => $whereInOut, 'park_data' => $park_data, 'res_in' => $res_in], 'hxPark/addParkInfo' . $serialTime, true);
                        $from_id = 0;
                        $insert_id = 0;
                        $park_where = [['car_number', '=', $data['car_number']], ['park_id', '=', $passage_info['village_id']], ['accessType', '=', 1], ['is_out', '=', 0], ['del_time', '=', 0],];
                        $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                        $car_access_record['accessType'] = 2;
                        $car_access_record['coupon_id'] = isset($park_temp_info['coupon_id']) ? $park_temp_info['coupon_id'] : 0;
                        $car_access_record['park_time'] = $data['park_time'] > 0 ? $data['park_time'] : $park_time;
                        $car_access_record['total'] = $data['price'];
                        if (empty($park_info_car111)) {
                            $car_access_record['exception_type'] = 2;
                        } else {
                            fdump_api(['line' => __LINE__, 'msg' => '查询对应出场记录', 'record_id' => $park_info_car111['record_id']], 'hxPark/addParkInfo' . $serialTime, true);
                            $db_house_village_car_access_record->saveOne(['record_id' => $park_info_car111['record_id']], ['is_out' => 1]);
                        }
                        $car_access_record['accessMode'] = $accessMode;
                        $car_access_record['is_out'] = 1;
                        $car_access_record['is_paid'] = 1;
                        if ($park_info_car111 && $park_info_car111['order_id']) {
                            $car_access_record['order_id'] = $park_info_car111['order_id'];
                        } else {
                            $car_access_record['order_id'] = date('YmdHis') . rand(100000, 999999);
                        }
                        $order_id = $car_access_record['order_id'];
                        $car_access_record['pay_type'] = $this->pay_type[0];
                        $car_access_record['update_time'] = $now_time;
                        $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                        if (isset($insert_id)) {
                            $car_access_record['from_id'] = $insert_id;
                        }
                        $park_time > 0 && $car_access_record['park_time'] = $park_time;
                        fdump_api(['line' => __LINE__, 'msg' => '添加出场记录', 'car_access_record' => $car_access_record], 'hxPark/addParkInfo' . $serialTime, true);
                        $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                        if (!empty($outRightPosition)) {
                            //释放车位
                            $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                            $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                            $this->updateParkingPositionUse($whereArr, $saveArr, $data['car_number'], 'addParkInfo_out_park');
                        }
                        if ($record_id > 0 && $is_real_garage > 0) {
                            //将此条之前的 都改掉
                            $wherexArr = [];
                            $wherexArr[] = ['record_id', '<', $record_id];
                            $wherexArr[] = ['car_number', '=', $data['car_number']];
                            $wherexArr[] = ['is_out', '=', 0];
                            $wherexArr[] = ['business_id', '=', $passage_info['village_id']];
                            $db_house_village_car_access_record->saveOne($wherexArr, ['is_out' => 1, 'update_time' => $now_time]);
                        }
                        $access_record_id = $record_id;
                    } else {
                        //写入车辆入场表
                        $park_data = [];
                        $park_data['out_time'] = $accessTime;
                        $park_data['is_out'] = 1;
                        $park_data['is_paid'] = 1;
                        $park_time > 0 && $park_data['park_time'] = $park_time;
                        $whereInOut = ['car_number' => $data['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0];
                        $res_in = $db_in_park->saveOne($whereInOut, $park_data);
                        fdump_api(['line' => __LINE__, 'msg' => '写入车辆入场表', 'whereInOut' => $whereInOut, 'park_data' => $park_data, 'res_in' => $res_in], 'hxPark/addParkInfo' . $serialTime, true);
                        $from_id = 0;
                        $insert_id = 0;

                        $park_where = [['car_number', '=', $data['car_number']], ['park_id', '=', $passage_info['village_id']], ['accessType', '=', 1], ['is_out', '=', 0], ['del_time', '=', 0],];
                        $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                        $car_access_record['accessType'] = 2;
                        $car_access_record['accessMode'] = $accessMode;
                        $car_access_record['is_out'] = 1;
                        $car_access_record['is_paid'] = 1;
                        if (isset($park_info_car111['order_id']) && !empty($park_info_car111['order_id'])) {
                            $car_access_record['order_id'] = $park_info_car111['order_id'];
                        } else {
                            $car_access_record['order_id'] = date('YmdHis') . rand(100000, 999999);
                        }
                        $order_id = $car_access_record['order_id'];
                        if (empty($park_info_car111)) {
                            $car_access_record['exception_type'] = 2;
                        } else {
                            fdump_api(['line' => __LINE__, 'msg' => '查询对应出场记录', 'record_id' => $park_info_car111['record_id']], 'hxPark/addParkInfo' . $serialTime, true);
                            $db_house_village_car_access_record->saveOne(['record_id' => $park_info_car111['record_id']], ['is_out' => 1]);
                        }
                        $car_access_record['total'] = $data['price'];
                        $car_access_record['park_time'] = $data['park_time'] > 0 ? $data['park_time'] : $park_time;
                        $car_access_record['pay_type'] = $this->pay_type[0];
                        $car_access_record['update_time'] = $now_time;
                        $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                        if (isset($insert_id)) {
                            $car_access_record['from_id'] = $insert_id;
                        }
                        $park_time > 0 && $car_access_record['park_time'] = $park_time;
                        fdump_api(['line' => __LINE__, 'msg' => '添加出场记录', 'car_access_record' => $car_access_record], 'hxPark/addParkInfo' . $serialTime, true);
                        $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                        if (!empty($outRightPosition)) {
                            //释放车位
                            $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                            $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                            $this->updateParkingPositionUse($whereArr, $saveArr, $data['car_number'], 'addParkInfo_out_park');
                        }
                        if ($record_id > 0 && $is_real_garage > 0) {
                            //将此条之前的 都改掉
                            $wherexArr = [];
                            $wherexArr[] = ['record_id', '<', $record_id];
                            $wherexArr[] = ['car_number', '=', $data['car_number']];
                            $wherexArr[] = ['is_out', '=', 0];
                            $wherexArr[] = ['business_id', '=', $passage_info['village_id']];
                            $db_house_village_car_access_record->saveOne($wherexArr, ['is_out' => 1, 'update_time' => $now_time]);
                            fdump_api(['line' => __LINE__, 'msg' => '更改之前记录为已出场', 'record_id' => $record_id, 'wherexArr' => $wherexArr, 'whenow_timerexArr' => $now_time], 'hxPark/addParkInfo' . $serialTime, true);
                        }
                        $access_record_id = $record_id;
                    }
                }
                $flow_other_time = $in_time;

            } else {

                $from_id = 0;
                $insert_id = 0;

                $park_where = [['car_number', '=', $data['car_number']], ['park_id', '=', $passage_info['village_id']], ['accessType', '=', 1], ['is_out', '=', 0], ['del_time', '=', 0],];
                $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);

                $car_access_record['accessType'] = 2;
                $car_access_record['accessMode'] = $accessMode;
                $car_access_record['exception_type'] = 2;
                $car_access_record['is_out'] = 1;
                $car_access_record['is_paid'] = 1;
                if (isset($park_info_car111['order_id']) && !empty($park_info_car111['order_id'])) {
                    $car_access_record['order_id'] = $park_info_car111['order_id'];
                } else {
                    $car_access_record['order_id'] = date('YmdHis') . rand(100000, 999999);
                }
                $order_id = $car_access_record['order_id'];
                if (empty($park_info_car111)) {
                    $car_access_record['exception_type'] = 2;
                } else {
                    fdump_api(['line' => __LINE__, 'msg' => '查询对应出场记录', 'record_id' => $park_info_car111['record_id']], 'hxPark/addParkInfo' . $serialTime, true);
                    $db_house_village_car_access_record->saveOne(['record_id' => $park_info_car111['record_id']], ['is_out' => 1]);
                }
                $car_access_record['total'] = $data['price'];
                $car_access_record['park_time'] = $data['park_time'] > 0 ? $data['park_time'] : $park_time;
                $car_access_record['pay_type'] = $this->pay_type[0];
                $car_access_record['update_time'] = $now_time;
                $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                if (isset($insert_id)) {
                    $car_access_record['from_id'] = $insert_id;
                }
                $park_time > 0 && $car_access_record['park_time'] = $park_time;
                fdump_api(['line' => __LINE__, 'msg' => '添加出场记录', 'car_access_record' => $car_access_record], 'hxPark/addParkInfo' . $serialTime, true);
                $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                if (!empty($outRightPosition)) {
                    //释放车位
                    $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                    $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                    $this->updateParkingPositionUse($whereArr, $saveArr, $data['car_number'], 'addParkInfo_out_park');
                }
                if ($record_id > 0 && $is_real_garage > 0) {
                    //将此条之前的 都改掉
                    $wherexArr = [];
                    $wherexArr[] = ['record_id', '<', $record_id];
                    $wherexArr[] = ['car_number', '=', $data['car_number']];
                    $wherexArr[] = ['is_out', '=', 0];
                    $wherexArr[] = ['business_id', '=', $passage_info['village_id']];
                    $db_house_village_car_access_record->saveOne($wherexArr, ['is_out' => 1, 'update_time' => $now_time]);
                    fdump_api(['line' => __LINE__, 'msg' => '更改之前记录为已出场', 'record_id' => $record_id, 'wherexArr' => $wherexArr, 'whenow_timerexArr' => $now_time], 'hxPark/addParkInfo' . $serialTime, true);
                }
                $access_record_id = $record_id;
            }
        }
        //出场处理结束

        // 车辆进出方向 1-进 2-出
        if ($passage_info['passage_direction'] == 1) {
            $flow_direction = 1;
            $is_out = 0;
            $is_paid = 0;
            $pay_type = '';
        }
        if ($passage_info['passage_direction'] == 0) {
            $flow_direction = 2;
            $is_out = 1;
            $is_paid = 1;
            $pay_type = $this->pay_type[0];
        }
        $uniqueArr = ['car_number' => $car_number, 'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id, 'flow_direction' => $flow_direction, 'park_sys_type' => $park_sys_type, 'is_out' => $is_out, 'is_paid' => $is_paid];
        $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
        $whereUnique = ['unique_id' => $unique_id,];
        $db_house_village_car_flow_record = new HouseVillageCarFlowRecord();
        $flowRecord = $db_house_village_car_flow_record->getOne($whereUnique, 'id, from_id');
        $total = isset($car_access_record['total']) && floatval($car_access_record['total']) > 0 ? $car_access_record['total'] : 0;
        if ($total > 0) {
            $total = isset($data['price']) && floatval($data['price']) > 0 ? $data['price'] : 0;
        }
        $carHandleGetInfoService = new CarHandleGetInfoService();
        $village_info = $carHandleGetInfoService->getVillageInfo($village_id);
        $park_name = isset($village_info['village_name']) && trim($village_info['village_name']) ? trim($village_info['village_name']) : '';
        $flowData = ['village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id, 'flow_direction' => $flow_direction, 'device_number' => $device_number, 'park_sys_type' => $park_sys_type, 'car_number' => $car_number, 'flow_time' => $now_time, 'unique_id' => $unique_id, 'order_id' => $order_id, 'is_out' => $is_out, 'uid' => 0, 'park_name' => $park_name, 'total' => $total, 'is_paid' => $is_paid,];
        $position_id = isset($car_access_record['position_id']) && intval($car_access_record['position_id']) > 0 ? intval($car_access_record['position_id']) : 0;
        $position_id && $flowData['position_id'] = $position_id;
        $access_record_id && $flowData['access_record_id'] = $access_record_id;
        $from_id && $flowData['from_id'] = $from_id;
        $park_time > 0 && $flowData['park_time'] = $park_time;
        $flow_other_time > 0 && $flowData['flow_other_time'] = $flow_other_time;
        if (isset($flowRecord['id']) && $flowRecord['id']) {
            $flowData['update_time'] = time();
            $db_house_village_car_flow_record->updateThis($whereUnique, $flowData);
        } else {
            $flowData['add_time'] = time();
            $db_house_village_car_flow_record->add($flowData);
        }
        return $record_id;
    }

    /**
     * 纪录遥控上报信息
     * *端口触发信息推送
     * @author:Li
     * @date_time: 2023/03/09 9:33
     */
    public function addPortData($data)
    {
        $db_park_passage = new ParkPassage();
        $db_house_village_park_config = new HouseVillageParkConfig();
        $db_park_plateresult_log = new ParkPlateresultLog();
        $db_house_village = new HouseVillage();
        $datahour = date('mdH');
        fdump_api(['data' => $data], 'hxPark/00addPortData' . $datahour, 1);
        if (empty($data['serialno']) || $data['opt_source'] !== 0) {
            /***opt_source 0表示开闸***/
            return false;
        }
        $passage_info = $db_park_passage->getFind(['device_number' => $data['serialno'], 'status' => 1]);
        if (empty($passage_info) || $passage_info->isEmpty()) {
            return false;
        }
        $passage_info = $passage_info->toArray();
        $garage_id = 0;
        if (isset($passage_info['garage_id'])) {
            $garage_id = $passage_info['garage_id'];
        }
        if ($garage_id < 1 && $passage_info['area_type'] == 2 && !empty($passage_info['passage_area'])) {
            $garage_id = intval($passage_info['passage_area']);
        }
        $park_sys_type = $passage_info['park_sys_type'] ? $passage_info['park_sys_type'] : 'HXT85';
        $garage_id = $garage_id > 0 ? $garage_id : 0;
        $device_numberArr = array();
        $serialnoArr = array();
        if ($passage_info['passage_type'] == 2) {
            $passage_info_list = $db_park_passage->getList(['village_id' => $passage_info['village_id'], 'status' => 1, 'park_sys_type' => $passage_info['park_sys_type'], 'passage_type' => 2]);
            if ($passage_info_list && !$passage_info_list->isEmpty()) {
                $passage_info_list = $passage_info_list->toArray();
                foreach ($passage_info_list as $kk => $vv) {
                    $device_numberArr[$vv['device_number']] = $vv;
                    $serialnoArr[] = $vv['device_number'];
                }
            }
        }
        fdump_api(['passage_info' => $passage_info, 'device_numberArr' => $device_numberArr], 'hxPark/00addPortData' . $datahour, 1);
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $house_village_park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id, $passage_info['village_id']);
        if (empty($house_village_park_config)) {
            return false;
        }
        fdump_api(['house_village_park_config' => $house_village_park_config], 'hxPark/00addPortData' . $datahour, 1);
        $village_info = $db_house_village->getOne($passage_info['village_id'], 'village_name');
        $where = [['channel_id', '=', $data['serialno']], ['village_id', '=', $passage_info['village_id']], ['park_sys_type', '=', $house_village_park_config['park_sys_type']], ['car_number', '<>', '_无_'],];
        if (!empty($serialnoArr)) {
            $where = [['channel_id', 'in', $serialnoArr], ['village_id', '=', $passage_info['village_id']], ['park_sys_type', '=', $house_village_park_config['park_sys_type']], ['car_number', '<>', '_无_'],];
        }
        $plateresult = $db_park_plateresult_log->get_one($where);
        if ($plateresult && !$plateresult->isEmpty()) {
            $plateresult = $plateresult->toArray();
        }
        fdump_api(['where' => $where, 'plateresult' => $plateresult], 'hxPark/00addPortData' . $datahour, 1);
        if (!isset($plateresult['car_number']) || !$plateresult['car_number']) {
            return false;
        }
        if ($device_numberArr && isset($device_numberArr[$plateresult['channel_id']])) {
            $passage_info = $device_numberArr[$plateresult['channel_id']];
        }
        fdump_api(['new_passage_info' => $passage_info], 'hxPark/00addPortData' . $datahour, 1);
        $data_add = ['id' => $passage_info['id'], 'village_id' => $passage_info['village_id'], 'car_number' => '无', 'accessTime' => date('Y-m-d H:i:s'), 'user_phone' => '', 'user_name' => '', 'village_name' => $village_info['village_name'], 'img' => '', 'price' => 0, 'park_time' => 0, 'optcode' => '',];
        $nowtime = time();
        if (!empty($plateresult) && ($plateresult['add_time'] + 300) > $nowtime) {
            if ($passage_info['passage_direction'] == 0) {
                $temp = ['car_number' => $plateresult['car_number'], 'village_id' => $passage_info['village_id'], 'device_number' => $data['serialno'], 'channel_number' => $passage_info['channel_number'], 'garage_id' => $garage_id, 'uid' => '', 'car_end_time' => 0, 'temporary_end_time' => 0, 'park_sys_type' => $park_sys_type,];
                try {
                    $pay = $this->get_temp_pay($temp);
                    fdump_api(['temp' => $temp, 'pay' => $pay], 'hxPark/00addPortData' . $datahour, 1);
                } catch (\Exception $e) {
                    $txt = $e->getMessage();
                    fdump_api(['msg' => '错误', '$txt' => $txt], 'hxPark/00addPortData' . $datahour, 1);
                    $pay = [];
                }
            } else {
                $pay = [];
            }
            if (empty($pay)) {
                $price = 0;
                $park_time = 0;
            } else {
                $price = $pay['pay_money'];
                $park_time = $pay['park_time'];
            }
            $data_add['price'] = $price;
            $data_add['park_time'] = $park_time;
            $data_add['car_number'] = $plateresult['car_number'];
        }
        if (!$data_add['price'] || $data_add['price'] <= 0) {
            $db_in_park = new InPark();
            $db_house_village_parking_temp = new HouseVillageParkingTemp();
            $whereInPark = ['car_number' => $plateresult['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0, 'del_time' => 0];
            $in_park_pay_info = $db_in_park->getOne1($whereInPark, 'order_id');
            if (!empty($in_park_pay_info)) {
                $order_id = isset($in_park_pay_info['order_id']) && $in_park_pay_info['order_id'] ? $in_park_pay_info['order_id'] : 0;
                $park_temp_id = isset($pay) && isset($pay['park_temp_id']) && $pay['park_temp_id'] ? $pay['park_temp_id'] : 0;
                $whereTemp = [];
                $whereTemp[] = ['car_number', '=', $plateresult['car_number']];
                $whereTemp[] = ['village_id', '=', $passage_info['village_id']];
                $whereTemp[] = ['order_id', '=', $order_id];
                $whereTemp[] = ['is_paid', '=', 0];
                if ($park_temp_id) {
                    $whereTemp[] = ['id', '<>', $park_temp_id];
                }
                $park_temp_info = $db_house_village_parking_temp->getOne($whereTemp);
                $total = isset($park_temp_info['total']) && $park_temp_info['total'] ? $park_temp_info['total'] : 0;
                $data_add['price'] = $total;
            }
        }
        $data_add['funcFrom'] = 'addPortData';
        fdump_api(['msg' => '相关信息', 'data_add' => $data_add], 'hxPark/00addPortData' . $datahour, 1);
        try {
            $res1 = $this->addParkInfo($data_add);
        } catch (\Exception $e) {
            $txt = $e->getMessage();
            $res1 = 0;
            fdump_api(['msg' => '错误2', '$txt' => $txt], 'hxPark/00addPortData' . $datahour, 1);
        }
        if ($res1) {
            $showscreen_data = [];
            $showscreen_data['passage'] = $passage_info;
            $showscreen_data['village_id'] = $passage_info['village_id'];
            $showscreen_data['car_number'] = $plateresult['car_number'];
            $showscreen_data['channel_id'] = $passage_info['device_number'];
            $showscreen_data['voice_content'] = 'v11';
            $showscreen_data['content'] = '岗亭人工确认，请通行';
            $showscreen_data['car_type'] = 'temporary_type';
        }
        return true;
    }

    protected function setCacheTag($village_id)
    {
        $this->cacheTagKey = 'village:cache:' . $village_id;
    }
}