<?php
/**
 * MallOrderLog.php
 * 订单日志
 * Create on 2020/9/17 18:36
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class MallOrderLog extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * @param $order_id
     * @return array
     *获取订单列表
     */
    public function getLogByOrderId($order_id)
    {
        return $this->where(['order_id' => $order_id])->order(['id'=>'DESC','addtime'=>'DESC'])->select()->toArray();
    }

    /**
     * @param $data
     * @return int|string
     * 添加一条日志
     */
    public function addOne($data)
    {
        return $this->insert($data);
    }
}