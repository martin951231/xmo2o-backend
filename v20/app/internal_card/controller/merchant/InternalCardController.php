<?php

namespace app\internal_card\controller\merchant;

use app\internal_card\model\service\InternalCardMoneyBillService;
use app\internal_card\model\service\InternalCardService;
use app\merchant\controller\merchant\AuthBaseController;
use think\response\Json;

class InternalCardController extends AuthBaseController
{
    public function orderList(): Json
    {
        $params = input('post.');
        $data = (new InternalCardService())->orderList($this->merId, $params);

        return api_output(0, $data);
    }

    public function orderPayList(): Json
    {
        $params = input('post.');
        $data = (new InternalCardService())->orderPayList($this->merId, $params);

        return api_output(0, $data);
    }

    /**
     * 账单列表
     * @return Json
     */
    public function billList(): Json
    {
        $params = input('post.');
        $params['mer_id'] = $this->merId;
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
        $params['mer_id'] = $this->merId;
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
        $params['mer_id'] = $this->merId;
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
     * 结算记录
     * @return Json
     */
    public function merBillRecord(): Json
    {
        $params = input('post.');
        $params['mer_id'] = $this->merId;
        $data = (new InternalCardMoneyBillService())->merBillRecord($params);

        return api_output(0, $data);
    }

}