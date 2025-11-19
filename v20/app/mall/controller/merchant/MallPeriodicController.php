<?php
/**
 * 商城营销活动 -- 周期购
 */

namespace app\mall\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\mall\model\service\activity\MallNewPeriodicPurchaseService;

class MallPeriodicController extends AuthBaseController
{
    /**
     * 获取周期购-列表
     * User: 钱大双
     * Date: 2020-10-22
     */
    public function getPeriodicList()
    {
        $merId = $this->merchantUser['mer_id'] ?? 0;

        if ($merId < 0) {
            return api_output(1001, [], '商家ID不存在');
        }

        //店铺id
        $store_id = $this->request->param('store_id', '', 'intval');

        $param['goods_name'] = $this->request->param('goods_name', '', 'trim');
        $param['status'] = $this->request->param('status', 3, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['page'] = $this->request->param('page', 1, 'intval');

        try {
            $mallNewPeriodicPurchaseService = new MallNewPeriodicPurchaseService();
            $result = $mallNewPeriodicPurchaseService->getPeriodicActList($store_id, $param);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 添加活动 和 编辑活动
     * User: 钱大双
     * Date: 2020-10-22
     * @return \json
     */
    public function addPeriodic()
    {
        $merId = $this->merchantUser['mer_id'] ?? 0;

        if ($merId < 0) {
            return api_output(1001, [], '商家ID不存在');
        }

        $param['mer_id'] = $merId;
        $param['type'] = 'periodic';
        $param['id'] = $this->request->param('id', 0, 'intval');
        $param['sort'] = 10;
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['goods_id'] = $this->request->param('goods_id', 0, 'intval');
        $param['goods_name'] = $this->request->param('goods_name', '', 'trim');
        $param['periodic_type'] = $this->request->param('periodic_type', 0, 'intval');
        $param['periodic_date'] = $this->request->param('periodic_date', '', 'trim');
        $param['forward_day'] = $this->request->param('forward_day', 0, 'intval');
        $param['forward_hour'] = $this->request->param('forward_hour', 0, 'intval');
        $param['delay_limit'] = $this->request->param('delay_limit', 0, 'intval');
        $param['buy_limit'] = $this->request->param('buy_limit', 0, 'intval');
        $param['is_discount_share'] = $this->request->param('is_discount_share', 2, 'intval');
        $param['discount_card'] = $this->request->param('discount_card', 0, 'intval');
        $param['discount_coupon'] = $this->request->param('discount_coupon', 0, 'intval');
        $param['periodic_count'] = $this->request->param('periodic_count', 0, 'intval');
        $param['freight_type'] = $this->request->param('freight_type', 0, 'intval');
        $param['desc'] = $this->request->param('desc', '', 'trim');

        try {
            $res = (new MallNewPeriodicPurchaseService())->addPeriodicAct($param);
            if ($res['status'] == 0) {
                return api_output(1000, [], $res['msg']);
            } else {
                return api_output(1003, [], $res['msg']);
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 修改活动状态
     * User: 钱大双
     * Date: 2020-10-22
     * @return \json
     */
    public function changeState()
    {
        $id = $this->request->param('id', '', 'intval');
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        $param['status'] = $this->request->param('status', 2, 'intval');
        try {
            (new MallNewPeriodicPurchaseService())->changeState($param, $id);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 软删除 活动
     * User: 钱大双
     * Date: 2020-10-22
     * @return \json
     */
    public function del()
    {
        $id = $this->request->param('id', '', 'trim');
        if (empty($id)) {
            return api_output(1001, [], 'ID不存在');
        }
        $param['is_del'] = $this->request->param('is_del', 1, 'intval');
        try {
            (new MallNewPeriodicPurchaseService())->del($param, $id);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取活动基本信息
     * User: 钱大双
     * Date: 2020-10-22
     */
    public function edit()
    {
        $id = $this->request->param('id', '', 'intval');

        try {
            $mallNewPeriodicPurchaseService = new MallNewPeriodicPurchaseService();
            $result = $mallNewPeriodicPurchaseService->getInfoById($id);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}