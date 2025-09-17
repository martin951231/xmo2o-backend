<?php

namespace app\shop\controller\storestaff;

use app\common\model\service\MerchantService;
use app\merchant\model\service\MerchantStoreService;
use app\shop\model\service\order\ShopOrderService;
use app\shop\model\service\store\MerchantStoreShopService;
use app\storestaff\controller\storestaff\AuthBaseController;
use app\storestaff\model\service\StoreStaffService;

/**
 * 外卖店员
 * @author: 张涛
 * @date: 2020/11/9
 * @package app\shop\controller\storestaff
 */
class ConfigController extends AuthBaseController
{
    public function index()
    {
        //根据店员所在店铺配置的预订天数，如果是0则默认7天
        $storeId = $this->staffUser['store_id'] ?? 0;
        $shop = (new MerchantStoreShopService())->getStoreByStoreId($storeId);
        $days = isset($shop['advance_day']) && $shop['advance_day'] > 0 ? $shop['advance_day'] : 7;
        $last7days = [['name' => '今日', 'date' => date('Y-m-d')]];
        for ($i = 1; $i <= $days; $i++) {
            $thisUnix = strtotime('+' . $i . ' days');
            $last7days[] = ['name' => $i == 1 ? '明日' : date('d日', $thisUnix), 'date' => date('Y-m-d', $thisUnix)];
        }
        $returnArr['book_order_date_list'] = $last7days;
        $returnArr['new_order_interval'] = 5;

        $store = (new MerchantStoreService())->getOne(['store_id' => $shop['store_id']]);
        $merchant = (new MerchantService())->getInfo($store['mer_id'] ?? 0);
        if(!is_array($merchant)){
            $merchant = $merchant->toArray();
        }
        $returnArr['dining_rules'] = '出餐时间=客户期望送达时间-骑手配送时间 （系统自动算出）用于您的出餐排序';
        if ($merchant && $merchant['package_fee_percent'] == 0) {
            $returnArr['plat_service_rules'] = '【外卖商品折扣后总额 - 平台满减优惠商家补贴的钱 - 平台优惠券平台补贴的钱 - 商家会员卡余额支付的线下充值部分 - 商家满减优惠金额 - 商家优惠券金额 - 平台会员等级优惠 + 商家配送费或者快递费】* 抽成比例';
        } else {
            $returnArr['plat_service_rules'] = '【外卖商品折扣后总额 - 平台满减优惠商家补贴的钱 - 平台优惠券平台补贴的钱 - 商家会员卡余额支付的线下充值部分 - 商家满减优惠金额 - 商家优惠券金额 - 平台会员等级优惠 + 打包费 + 商家配送费或者快递费】* 抽成比例';
        }
        return api_output(0, $returnArr);
    }
}
