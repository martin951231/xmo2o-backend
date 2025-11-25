<?php


namespace app\life_tools\model\service\appoint;

use app\life_tools\model\db\LifeToolsAppointList;
use app\merchant\model\service\MerchantService;
use think\facade\Db;

class LifeToolsAppointListService
{
    public function __construct()
    {
        $this->lifeToolsAppointListModel = new LifeToolsAppointList();
    }

    /**
     * 商家结算
     */
    public function addMerchantMoney($data)
    {  
        $mer_id = $data['mer_id'];
        $money = 0;
        $list = $this->lifeToolsAppointListModel;
        $list->appoint_id = $data['appoint_id'];
        $list->mer_id = $mer_id;
        $list->order_id = $data['order_id'];
        $list->pay_money = $data['pay_money'];
        $list->system_balance = $data['system_balance'];
        $list->system_score_money = $data['system_score_money'];
        $list->system_score = $data['system_score'];
        $list->coupon_id = $data['coupon_id'];
        $list->coupon_price = $data['coupon_price'];
        $list->card_id = $data['card_id'];
        $list->card_price = $data['card_price'];
        $list->merchant_balance_pay = $data['merchant_balance_pay'];
        $list->merchant_balance_give = $data['merchant_balance_give'];
        $list->add_time = $data['add_time'];
        return $list->save();
         
    }
}
