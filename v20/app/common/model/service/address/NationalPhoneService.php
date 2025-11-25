<?php
namespace app\common\model\service\address;
use app\common\model\db\NationalPhone;
class NationalPhoneService{
    /**
     * 获取手机区号
     * @param
     * @return array
     */
    public function getAreaCode()
    {
        $where = [
            'status' => 1
        ];
        $areaCode = new NationalPhone();
        $code = $areaCode->getNationlCode($where);
        if (!empty($code)) {
            $arr = [];
            foreach ($code as $val) {
                $arr[] = [
                    'national' => $val['national'],
                    'code' => $val['code'],
                    'show' => $val['national']. ' +'. $val['code']
                ];
            }
            if (!empty($arr)) {
                return $arr;
            } else {
                return [];
            }
        } else {
            return [];
        }

    }
}