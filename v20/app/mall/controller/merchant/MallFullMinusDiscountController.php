<?php
/**
 * 商城营销活动 -- 满减满折
 */

namespace app\mall\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\mall\model\service\activity\MallFullMinusDiscountActService;

class MallFullMinusDiscountController extends AuthBaseController
{
    /**
     * 获取满减满折-列表
     * User: 钱大双
     * Date: 2020-10-20
     */
    public function getFullMinusDiscountList()
    {
        $merId = $this->merchantUser['mer_id'] ?? 0;

        if ($merId < 0) {
            return api_output(1001, [], '商家ID不存在');
        }

        //店铺id
        $store_id = $this->request->param('store_id', '', 'intval');

        $param['name'] = $this->request->param('name', '', 'trim');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['status'] = $this->request->param('status', 3, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['page'] = $this->request->param('page', 1, 'intval');

        try {
            $mallFullMinusDiscountActService = new MallFullMinusDiscountActService();
            $result = $mallFullMinusDiscountActService->getDiscountActList($store_id, $param);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加活动 和 编辑活动
     * User: 钱大双
     * Date: 2020-10-20
     * @return \json
     */
    public function addFullMinusDiscount()
    {
        $merId = $this->merchantUser['mer_id'] ?? 0;

        if ($merId < 0) {
            return api_output(1001, [], '商家ID不存在');
        }

        $param['mer_id'] = $merId;
        $param['type'] = 'minus_discount';
        $param['id'] = $this->request->param('id', 0, 'intval');
        $param['sort'] = 20;
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['is_discount'] = $this->request->param('is_discount', 1, 'intval');
        $param['rule'] = $this->request->param('rule', '');
        $param['max_num'] = $this->request->param('max_num', 0, 'intval');
        $param['act_type'] = $this->request->param('act_type', 0, 'intval');
        $param['goods_ids'] = $this->request->param('goods_ids', '', 'trim');
        $param['is_discount_share'] = $this->request->param('is_discount_share', 2, 'intval');
        $param['discount_card'] = $this->request->param('discount_card', 0, 'intval');
        $param['discount_coupon'] = $this->request->param('discount_coupon', 0, 'intval');
        $param['desc'] = $this->request->param('desc', '', 'trim');
        $param['rule'] = json_decode($param['rule'], true);

         try {
            $res = (new MallFullMinusDiscountActService())->addFullMinusDiscountAct($param);
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
     * Date: 2020-10-20
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
            (new MallFullMinusDiscountActService())->changeState($param, $id);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 软删除 活动
     * User: 钱大双
     * Date: 2020-10-19
     * @return \json
     */
    public function del()
    {
        $id = $this->request->param('id', '', 'intval');
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        $param['is_del'] = $this->request->param('is_del', 1, 'intval');
        try {
            (new MallFullMinusDiscountActService())->del($param, $id);
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
            $mallFullMinusDiscountActService = new MallFullMinusDiscountActService();
            $result = $mallFullMinusDiscountActService->getInfoById($id);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}