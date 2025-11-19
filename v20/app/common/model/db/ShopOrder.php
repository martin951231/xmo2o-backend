<?php
/**
 * 快店 订单 Model
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 11:48
 */

namespace app\common\model\db;

use app\shop\model\db\ShopOrderDetail;
use think\exception\FuncNotFoundException;
use think\Model;

class ShopOrder extends Model
{
    const IS_PICK_IN_STORE_4 = 4;//配送方式( 0：平台配送，1：商家配送，2：自提 3：快递配送（商城）4 机器人配送)
    const ORDER_STATUS_2 = 2;//配送状态（0：订单生产，1:店员接单，2：配送员接单，3：配送员取货，4：送达，5：确认收货，6，配送结束）
    /**
     * 快店订单列表信息
     * User: chenxiang
     * Date: 2020/6/1 11:52
     * @param string $field
     * @param array $where
     * @param string $group
     * @return mixed
     */
    public function getList($field = '', $where = [], $group = '') {
        $result = $this->field($field)->where($where)->group($group)->select();
        return $result;
    }

    /**
     * 获取快店订单信息
     * User: chenxiang
     * Date: 2020/6/1 19:01
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     */
    public function getOne($where = [], $field = true) {
        $result = $this->field($field)->where($where)->find();
        return $result;
    }
    public function orderDetail()
    {
        return $this->hasMany(ShopOrderDetail::class, 'order_id', 'order_id');
    }
    /**
     * 获取快店订单信息
     * User: chenxiang
     * Date: 2020/6/1 19:01
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     */
    public function updateOrder($where,$data){
        return $this->where($where)->update($data);
    }


    /**
     * 获取下单终端
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function getMobilePayArr()
    {
        return [1 => '微信端', 2 => 'APP', 3 => '小程序'];
    }

}