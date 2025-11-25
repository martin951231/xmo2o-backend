<?php
/**
 * 用户充值平台余额
 * add by hengtingmei
 */

namespace app\common\model\service\order;

use app\common\model\db\UserRechargeOrder;

class UserRechargeOrderService{

    public $userRechargeOrderModel = null;
    public function __construct()
    {
        $this->userRechargeOrderModel = new UserRechargeOrder();
    }

    /**
     * 统计某个字段
     * @param  $where array 查询条件
     * @param  $field string 需要统计的字段
     * @author hengtingmei
     * @return string
     */
    public function getTotalByCondition($where, $field){
        $res = $this->userRechargeOrderModel->getTotalByCondition($where,$field);

        if(!$res){
           return 0;
        }

        return $res['total'];
    }

    /**
     * 统计订单数量
     * @param  $where
     * @return string
     */
    public function getCount($where){
        return $this->userRechargeOrderModel->getCount($where);
    }
}