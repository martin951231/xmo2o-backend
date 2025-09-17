<?php
/**
 * 商家对账service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/15 16:45
 */

namespace app\merchant\model\service;
use app\merchant\model\service\MerchantMoneyListService;
use app\merchant\model\service\StoreMoneyListService;
use app\merchant\model\service\VillageMoneyListService;
class SystemBillService {
    public function __construct()
    {
    }
    
   /*
     * @param type 对账类型  0 平台跟商家 1自有支付 2 平台很商家子商家 3 平台跟店铺子商家 4 平台跟社区 5 平台跟社区子商家
     * */
    public function billMethod($type,$orderInfo){
        switch($type){
            case 0:
            case 1:
            case 2:
                $res = (new MerchantMoneyListService())->addMoney($orderInfo);
                break;
            case 3:
                $res =  (new StoreMoneyListService())->addMoney($orderInfo);
                break;
            case 4:
            case 5:
                $res =  (new VillageMoneyListService())->addMoney($orderInfo);
                break;
        }

        return $res;
    }

}