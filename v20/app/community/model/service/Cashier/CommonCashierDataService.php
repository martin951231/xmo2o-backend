<?php

declare(strict_types=1);
/**
 * This file is part of Kuaijing Bailing.
 *
 * @link     https://www.kuaijingai.com
 * @document https://help.kuaijingai.com
 * @contact  www.kuaijingai.com 7*12 9:00-21:00
 */

namespace app\community\model\service\Cashier;

use app\community\model\service\HouseNewChargeService;

class CommonCashierDataService
{
    public function getTypeOrderTypes($type = 'property', $isArr = 0)
    {
        if ($type == 'property') {
            return $this->getPropertyOrderTypes($isArr);
        }
        if ($type == 'park') {
            return $this->getParkOrderTypes($isArr);
        }
        return $this->getOtherTypes($isArr);
    }

    /**
     * 获取停车的一些缴费类型
     * @param $isArr
     * @return array
     */
    public function getPropertyOrderTypes($isArr = 1)
    {
        if ($isArr == 1) {
            return [
                'num' => 1,
                'data' => ['property'],
                'type' => 'array',
            ];
        }
        return [
            'num' => 1,
            'data' => 'property',
            'type' => 'string',
        ];
    }
    
    /**
     * 获取停车的一些缴费类型
     * @param $isArr
     * @return array
     */
    public function getParkOrderTypes($isArr = 1)
    {
        if ($isArr == 1) {
            return [
                'num' => 3,
                'data' => ['park', 'park_new', 'parking_management'],
                'type' => 'array',
            ];
        }
        return [
            'num' => 3,
            'data' => 'park,park_new,parking_management',
            'type' => 'string',
        ];
    }

    /**
     * 获取其他的一些缴费类型
     * @param $isArr
     * @return array
     */
    public function getOtherTypes($isArr = 1)
    {
        $serviceHouseNewPorperty = new HouseNewChargeService();
        $res = $serviceHouseNewPorperty->charge_type_arr;
        $types = array_column($res, null, 'key');
        $data1 = $this->getPropertyOrderTypes(1);
        $data2 = $this->getParkOrderTypes(1);
        $data1Arr = $data1['data'] ?? [];
        $data2Arr = $data2['data'] ?? [];
        $dataArr = array_merge($data1Arr, $data2Arr);
        $dataArr[] = 'non_motor_vehicle';

        foreach ($dataArr as $data) {
            if (isset($types[$data])) {
                unset($types[$data]);
            }
        }
        $fields = array_column($types, 'key');
        if ($isArr == 1) {
            return [
                'num' => count($fields),
                'data' => $fields,
                'type' => 'array',
            ];
        }
        return [
            'num' => count($fields),
            'data' => implode(',', $fields),
            'type' => 'string',
        ];
    }
}