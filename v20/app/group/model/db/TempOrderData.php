<?php

/**
 * 流水号order_id
 * Created by PhpStorm.
 * Author: 汪晨
 * Date Time: 2021/05/15
 */
namespace app\group\model\db;

use think\Model;
use think\facade\Db;
class TempOrderData extends Model
{
    /**
     * 流水号
     */
    public function getOneData($temp_id) {
        $result = $this->where(['temp_id'=>$temp_id])->find()->toArray();
        return $result;
    }
} 