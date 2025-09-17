<?php
/**
 * 小红马农行支付对接
 */

namespace app\community\controller\platform;
use app\community\model\service\AbChinaService;
use app\BaseController;
class AbChinaController extends BaseController
{
    /**
     * 农行查询本地账单
     */
    public function getOrderList()
    {
        $AbChinaService = new AbChinaService();
        $requestContent = \request()->getInput();
        $info = $AbChinaService->getOrderList($requestContent);
        exit($info);
    }
    
//    /**
//     * 农行支付成功后通知
//     */
//    public function afterPay()
//    {
//        $AbChinaService = new AbChinaService();
//        $requestContent = \request()->getInput();
//        $info = $AbChinaService->afterPay($requestContent);
//        exit($info);
//    }

    public function runQueryOrder()
    {
        $AbChinaService = new AbChinaService();
        $info = $AbChinaService->getAbChinaOrderInfo();
        exit($info);
    }
}