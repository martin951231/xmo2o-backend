<?php
/**
 * 自动返回用户姓名 电话 号码等信息service
 * Author: zhumengqun
 * Date Time: 2020/8/25
 */

namespace app\mall\model\service;

use app\mall\model\db\Config;

class AutoAddressService
{
    /**
     * 格式化信息
     * @param $info
     * @return array or null
     */
    public function formatInfo($info)
    {
        //去除多余空格和其他符号处理
        $info = preg_replace('/\s{2,}|,|，|;|；|、|\/|\n/', ' ', $info);
        $info = preg_replace('/\s{2,}|,|，|;|；|、|\/|\n/', ' ', $info);
        $infos = explode(' ', $info);
        if (!empty($info)) {
            //先过滤出电话号码(直接过滤不需要循环调用findWorldPhoneNumbers)
            $number = $this->findWorldPhoneNumbers($info);
            if ($number) {
                //输入的信息包含号码
                $phone = $number->getData()[0];
                //找出号码并剔除
                foreach ($infos as $key => $val) {
                    //stripos()返回值为找到的第一个下标（可能为0）否则返回false（0==false）结果为true
                    if (stripos($val, $phone) !== false) {
                        $phoneKey = $key;
                        array_splice($infos, $phoneKey, 1);
                    }
                }
            } else {
                //输入的信息不包含密码
                $phone = '';
            }

            $infosTmp = $this->findName($infos);
            if (!empty($infosTmp) && $infosTmp->getData()) {
                $name = $infosTmp->getData()['name'];
                $addr = $infosTmp->getData()['addr'];
            } else {
                $name = '';
                $addr = '';
            }
            if (!empty($addr)) {
                $addrAll = $this->findAddressBaidu($addr);
                if ($addrAll) {
                    $addr = $addrAll->getData();
                } else {
                    $addr = '';
                }
            }
            $result = ['phone' => $phone, 'name' => $name, 'addr' => $addr];
            if (!empty($result)) {
                return $result;
            } else {
                throw new \think\Exception('无法查询到相关信息');
            }

        } else {
            throw new \think\Exception('输入参数为空');
        }
    }

    /**
     * 获取字符串中的电话号码
     * @param $info
     * @return json or null
     */
    public function findWorldPhoneNumbers($info)
    {
        //世界范围的号码正则表达式
        $phonesRegs = [
            'zh-CN' => '/(?!=(\+?0?86\-?)?)1[345789]\d{9}/',     //中国
            'zh-TW' => '/(?!=(\+?0?886\-?|0)?)9\d{8}/',           //中国台湾
            'zh-MC' => '/(?!=(\+?0?853\-?)?)[6]([8|6])\d{5,6}/',  //中国澳门
            'en-HK' => '/(?!=(\+?0?852\-?)?)6\d{7}|9[0-8]\d{6}/'   //中国香港
            //'en-US' => '/(?!=(\+?1)?)[2-9]\d{2}[2-9](?!11)\d{6}/', //美国
            //'ar-DZ' => '/(\+?213|0)(5|6|7)\d{8}/',
            //'ar-SY' => '/(?!=(!?(\+?963)|0)?)9\d{8}/',   //叙利亚
            //'ar-SA' => '/(?!=(!?(\+?966)|0)?)5\d{8}/',   //沙特阿拉伯
            //'cs-CZ' => '/(\+?420)? ?[1-9][0-9]{2} ?[0-9]{3} ?[0-9]{3}/',
            //'de-DE' => '/(\+?49[ \.\-])?([\(]{1}[0-9]{1,6}[\)])?([0-9 \.\-\/]{3,20})((x|ext|extension)[ ]?[0-9]{1,4})?/',
            //'da-DK' => '/(?!=(\+?45)?)(\d{8})/',   //丹麦
            //'el-GR' => '/(?!=(\+?30)?)(69\d{8})/',  //希腊
            //'en-AU' => '/(\+?61|0)4\d{8}/',
            //'en-GB' => '/(\+?44|0)7\d{9}/',
            //'en-IN' => '/(?!=(\+?91|0)?)[789]\d{9}/',  //印度
            //'en-NZ' => '/(\+?64|0)2\d{7,9}/',
            //'en-ZA' => '/(\+?27|0)\d{9}/',
            //'en-ZM' => '/(\+?26)?09[567]\d{7}/',
            //'es-ES' => '/(?!=(\+?34)?)(6\d{1}|7[1234])\d{7}/',  //西班牙
            //'fi-FI' => '/(\+?358|0)\s?(4(0|1|2|4|5)?|50)\s?(\d\s?){4,8}\d/',
            //'fr-FR' => '/(\+?33|0)[67]\d{8}/',
            //'he-IL' => '/(\+972|0)([23489]|5[0248]|77)[1-9]\d{6}/',
            //'hu-HU' => '/(\+?36)(20|30|70)\d{7}/',
            //'it-IT' => '/(\+?39)?\s?3\d{2} ?\d{6,7}/',
            //'ja-JP' => '/(\+?81|0)\d{1,4}[ \-]?\d{1,4}[ \-]?\d{4}/',
            //'ms-MY' => '/(\+?6?01){1}(([145]{1}(\-|\s)?\d{7,8})|([236789]{1}(\s|\-)?\d{7}))/',
            //'nb-NO' => '/(\+?47)?[49]\d{7}/',
            //'nl-BE' => '/(\+?32|0)4?\d{8}/',
            //'nn-NO' => '/(\+?47)?[49]\d{7}/',
            //'pl-PL' => '/(\+?48)? ?[5-8]\d ?\d{3} ?\d{2} ?\d{2}/',
            //'pt-BR' => '/(\+?55|0)\-?[1-9]{2}\-?[2-9]{1}\d{3,4}\-?\d{4}/',
            //'pt-PT' => '/(?!=(\+?351)?)9[1236]\d{7}/',  //葡萄牙
            //'ru-RU' => '/(?!=(\+?7|8)?)9\d{9}/',    //俄罗斯
            //'sr-RS' => '/(\+3816|06)[- \d]{5,9}/',
            //'tr-TR' => '/(?!=(\+?90|0)?)5\d{9}/',     //土耳其
            //'vi-VN' => '/(\+?84|0)?((1(2([0-9])|6([2-9])|88|99))|(9((?!5)[0-9])))([0-9]{7})/'
        ];
        foreach ($phonesRegs as $phoneReg) {
            preg_match($phoneReg, $info, $phone);
            if ($phone) {
                return json($phone);
            }
        }
        return '';
    }

    /**
     * 获取用户名
     * @param $infos
     * @return json or null
     * */
    public function findName($infos)
    {
        $result = array();
        $maxLen = 0;
        $addr = '';
        $name = '';
        foreach ($infos as $val) {
            if (preg_match_all('/\d/', $val) || preg_match_all('/^[A-Za-z0-9\x80-\xff]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/', $val)) {
                continue;
            }
            //最长做地址
            $len = strlen($val);
            if ($maxLen < $len) {
                $maxLen = $len;
                $addr = $val;
            }
        }
        foreach ($infos as $val) {
            //跳过数字和邮件地址等依次做姓名
            if (preg_match_all('/\d/', $val) || preg_match_all('/^[A-Za-z0-9\x80-\xff]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/', $val) || $val == $addr) {
                continue;
            } else {
                if (strlen($val) <= 12) {
                    $name = $val;
                    break;
                }
            }
        }
        $result['addr'] = $addr;
        $result['name'] = $name;
        return json($result);
    }

    /**
     *Baidu地理编码自动获取地址
     * @param $addr
     * @return json or null
     */
    public function findAddressBaidu($addr)
    {
        $getAk = (new Config())->getAk()->toArray();
        $ak = $getAk[0]['value'];

        $url = "http://api.map.baidu.com/geocoder/v2/";
        $params = [
            'address' => $addr,
            'output' => "json",
            'ak' => $ak,
        ];
        $url .= "?" . http_build_query($params);
        $json = json_decode(file_get_contents($url), true);
        if ($loc = @$json['result']['location']) {
            if (!is_null($loc)) {
                $url = "http://api.map.baidu.com/geocoder/v2/";
                $params = [
                    'location' => "{$loc['lat']},{$loc['lng']}",
                    'output' => "json",
                    'ak' => $ak,
                ];
                $url .= "?" . http_build_query($params);
                $json = json_decode(file_get_contents($url), true);
                $addrInfo = $json['result']['addressComponent'];
                $addrAll = ['province' => $addrInfo['province'], 'city' => $addrInfo['city'], 'area' => $addrInfo['district'], 'detail' => $addrInfo['town'] . $addrInfo['street'] . $addr];
                return json($addrAll);
            }
        }
        return null;

    }


}
