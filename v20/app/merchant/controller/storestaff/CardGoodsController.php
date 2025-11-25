<?php
/**
 * 店员后台会员卡寄存功能
 * Author: hengtingmei
 * Date Time: 2021/11/03 17:15
 */

namespace app\merchant\controller\storestaff;
use app\merchant\model\service\card\CardGoodsService;
use app\storestaff\controller\storestaff\AuthBaseController;

class CardGoodsController extends AuthBaseController
{
    /**
     * 核销记录
     */
    public function exchangeStaffList()
    {
        $params = array();
        $params['mer_id']=$this->merId;
        $params['pageSize'] = $this->request->param('pageSize');
        $params['page'] = $this->request->param('page');
        $params['key'] = $this->request->param('key');
        try {
            $data = (new CardGoodsService())->exchangeStaffList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 确定兑换(核销)
     */
    public function useOrder()
    {
        $params = array();
        $params['staff_id'] = $this->staffId;
        $params['id'] = $this->request->param('id');//记录id
        try {
            $data = (new CardGoodsService())->useOrder($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}
