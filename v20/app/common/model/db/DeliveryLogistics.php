<?php

namespace app\common\model\db;

use app\common\model\service\SingleFaceService;
use think\Model;

class DeliveryLogistics extends Model
{
    protected $json = ['logistics'];
    protected $jsonAssoc = true;

    public static function getLogistics($expressCode, $expressNum, $phone)
    {
        if(empty($expressCode) || empty($expressNum)){
            return [];
        }
        $orderLogistics = self::where([
                'express_code' => $expressCode,
                'express_num'  => $expressNum,
            ])
            ->field('express_code, express_num, logistics_time, logistics')
            ->find();

        if (empty($orderLogistics['express_num'])) {
            $orderLogistics = self::create([
                'express_code' => $expressCode,
                'express_num'  => $expressNum,
            ]);
        }
        if (!empty($orderLogistics['logistics']) && ($orderLogistics['logistics_time'] + 30 * 60 > time() || $orderLogistics['status'])) {
            return $orderLogistics['logistics'];
        }

        $logistics = (new SingleFaceService())->getSynQuery($expressNum, $expressCode, $phone);
        if(!empty($logistics['msg']) && empty($logistics['data'])){
            $logistics['data'] = [
                'state' => '',
                'stateMessage' => $logistics['msg']
            ];
        }
        if (!empty($logistics['data'])) {
            if($logistics['data']['stateMessage'] == '没该功能权限'){
                $logistics['data']['stateMessage'] .= ',请联系快递100客服。';
            }
            $orderLogistics->logistics      = $logistics['data'];
            $orderLogistics->logistics_time = time();
            if($logistics['data']['state'] == 3){
                $orderLogistics->status = 1;//已签收
            }
            $orderLogistics->save();

            return $logistics['data'];
        }
        return [];
    }
}