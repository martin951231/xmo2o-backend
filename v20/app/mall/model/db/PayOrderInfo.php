<?php
/**
 * PayOrderInfo.php
 * 支付信息
 * Create on 2020/11/5 15:03
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class PayOrderInfo extends model
{
    /**
     * @param $where
     * 获取一条
     */
    public function getOne($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
}