<?php

namespace app\internal_card\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\common\model\db\MiaomaiOrderRefundSync;
use app\common\model\db\MiaomaiOrderSync;
use app\internal_card\model\service\InternalCardMoneyBillService;
use think\response\Json;

class InternalCardController extends AuthBaseController
{
    /**
     * 账单列表
     * @return Json
     */
    public function billList(): Json
    {
        $params = input('post.');
        $data = (new InternalCardMoneyBillService())->billList($params);

        return api_output(0, $data);
    }
    /**
     * 账单汇总
     * @return Json
     */
    public function billSum(): Json
    {
        $params = input('post.');
        $params['bill_sum'] = true;
        $data = (new InternalCardMoneyBillService())->billList($params);

        return api_output(0, $data);
    }
    /**
     * 卡明细
     * @return Json
     */
    public function merCardList(): Json
    {
        $params = input('post.');
        $data = (new InternalCardMoneyBillService())->merCardList($params);

        return api_output(0, $data);
    }
    /**
     * 卡明细汇总
     * @return Json
     */
    public function merCardBillSum(): Json
    {
        $params = input('post.');
        $params['bill_sum'] = true;
        $data = (new InternalCardMoneyBillService())->merCardList($params);

        return api_output(0, $data);
    }
    /**
     * 平台结算
     * @return Json
     */
    public function merBill(): Json
    {
        $params = input('post.');
        $params['admin_uid'] = $this->_uid;
        validate([
            'mer_id|商家ID'=>'require',
            'bill_date|账单日期'=>'require',
            'bill_money|结算金额'=>'require',
            'bill_type|结算方式'=>'require|in:1,2',
            'tax_ticket_type|票务类型'=>'require|in:1,2',
            'remark|备注'=>'require',
        ])->check($params);
        $data = (new InternalCardMoneyBillService())->merBill($params);

        return api_output(0, $data);
    }
    /**
     * 结算记录
     * @return Json
     */
    public function merBillRecord(): Json
    {
        $params = input('post.');
        $data = (new InternalCardMoneyBillService())->merBillRecord($params);

        return api_output(0, $data);
    }

    /**
     * 淼迈订单列表
     * @return Json
     */
    public function miaomaiOrderList(): Json
    {
        $orderList = MiaomaiOrderSync::withSearch(['order_code', 'stores_name', 'cas_name'], input('post.'))
            ->paginate(input('page_size', 10));
        
        return api_output(0, $orderList);
    }

    /**
     * 淼迈退款订单列表
     * @return Json
     */
    public function miaomaiOrderRefundList(): Json
    {
        $orderList = MiaomaiOrderRefundSync::withSearch(['order_code', 'stores_name'], input('post.'))
            ->paginate(input('page_size', 10));

        return api_output(0, $orderList);
    }
}