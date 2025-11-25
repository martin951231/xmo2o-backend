<?php
/**
 * 商城营销活动 -- 预售
 */

namespace app\mall\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\mall\model\service\activity\MallPrepareActService;

class MallPrepareController extends AuthBaseController
{
    /**
     * 获取预售-列表
     * User: 钱大双
     * Date: 2020-10-21
     */
    public function getPrepareList()
    {
        $merId = $this->merchantUser['mer_id'] ?? 0;

        if ($merId < 0) {
            return api_output(1001, [], '商家ID不存在');
        }

        try {
            //店铺id
            $store_id = $this->request->param('store_id', '', 'intval');

            $param['goods_name'] = $this->request->param('goods_name', '', 'trim');
            $param['start_time'] = $this->request->param('start_time', '', 'trim');
            $param['end_time'] = $this->request->param('end_time', '', 'trim');
            $param['status'] = $this->request->param('status', 3, 'intval');
            $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
            $param['page'] = $this->request->param('page', 1, 'intval');

            $mallPrepareActService = new MallPrepareActService();
            $result = $mallPrepareActService->getPrepareActList($store_id, $param);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 添加活动 和 编辑活动
     * User: 钱大双
     * Date: 2020-10-21
     * @return \json
     */
    public function addPrepare()
    {
        $merId = $this->merchantUser['mer_id'] ?? 0;

        if ($merId < 0) {
            return api_output(1001, [], '商家ID不存在');
        }
        try {
            $param['mer_id'] = $merId;
            $param['type'] = 'prepare';
            $param['id'] = $this->request->param('id', 0, 'intval');
            $param['sort'] = 10;
            $param['store_id'] = $this->request->param('store_id', 0, 'intval');
            $param['bargain_start_time'] = $this->request->param('bargain_start_time', '', 'trim');
            $param['bargain_end_time'] = $this->request->param('bargain_end_time', '', 'trim');
            $param['rest_type'] = $this->request->param('rest_type', 1, 'intval');
            $param['rest_start_time'] = $this->request->param('rest_start_time', '', 'trim');
            $param['rest_end_time'] = $this->request->param('rest_end_time', '', 'trim');
            $param['send_goods_type'] = $this->request->param('send_goods_type', 1, 'intval');
            $param['send_goods_date'] = $this->request->param('send_goods_date', '', 'trim');
            $param['send_goods_days'] = $this->request->param('send_goods_days', 1, 'intval');
            $param['limit_num'] = $this->request->param('limit_num', 0, 'intval');
            $param['is_discount_share'] = $this->request->param('is_discount_share', 2, 'intval');
            $param['discount_card'] = $this->request->param('discount_card', 0, 'intval');
            $param['discount_coupon'] = $this->request->param('discount_coupon', 0, 'intval');
            $param['goods_sku'] = $this->request->param('goods_sku', '');
            $param['desc'] = $this->request->param('desc', '', 'trim');
            $param['goods_sku'] = json_decode($param['goods_sku'], true);

            $res = (new MallPrepareActService())->addPrepareAct($param);
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
     * Date: 2020-10-21
     * @return \json
     */
    public function changeState()
    {
        $id = $this->request->param('id', '', 'intval');
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        try {
            $param['status'] = $this->request->param('status', 2, 'intval');
            (new MallPrepareActService())->changeState($param, $id);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 软删除 活动
     * User: 钱大双
     * Date: 2020-10-21
     * @return \json
     */
    public function del()
    {
        $id = $this->request->param('id', '', 'intval');
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        try {
            $param['is_del'] = $this->request->param('is_del', 1, 'intval');
            (new MallPrepareActService())->del($param, $id);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取活动基本信息
     * User: 钱大双
     * Date: 2020-10-21
     */
    public function edit()
    {
        $id = $this->request->param('id', '', 'intval');

        try {
            $mallPrepareActService = new MallPrepareActService();
            $result = $mallPrepareActService->getInfoById($id);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}