<?php
/**
 * 订单绑定店铺
 * User: 衡婷妹
 * Date: 2021/8/24
 */
namespace app\new_marketing\model\service;

use app\new_marketing\model\db\NewMarketingOrderStore;

class MarketingOrderStoreService
{
	public $newMarketingOrderStoreModel = null;

    public function __construct()
    {
        $this->newMarketingOrderStoreModel = new NewMarketingOrderStore();
    }
	

}