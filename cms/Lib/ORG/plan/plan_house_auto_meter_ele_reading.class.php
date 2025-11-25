<?php


class plan_house_auto_meter_ele_reading extends plan_base
{
    public function runTask()
    {
        if(!C('config.customized_meter_reading')){
            return true;
        }
 
        invoke_v20_service('\app\community\model\service\CustomizeMeterWaterReadingService/getMeterEleReading');
        return true; 
    }


    public function encodeHexString(array $bytes)
    {
        $LOWER = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c','d','e','f'];
        $length = count($bytes);
        $charArr = [];
        foreach ($bytes as $value) {
            $value = intval($value);
            $charArr[] = $LOWER[$this->uright(0xF0 & $value, 4)];
            $charArr[] = $LOWER[0x0F & $value];
        }
        return implode("", $charArr);
    }

    /** php 无符号右移 */
    public function uright($a, $n)
    {
        $c = 2147483647 >> ($n - 1);
        return $c & ($a >> $n);
    }

    /**
     * 模拟DigestUtils.md5
     * @param    [string]                   $string 加密字符
     * @return   [array]                           加密之后的byte数组
     * @dateTime 2021-01-05T09:28:33+0800
     */
    public function md5Hex($string)
    {
        return unpack("c*", md5($string, true));
    }

    public function json_post($url, $data = NULL,$header)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if(is_array($data))
        {
            $data = json_encode($data,true);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER,$header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errorno = curl_errno($curl);
        if ($errorno) {
            return $errorno;
        }
        curl_close($curl);
        return $res;

    }
}