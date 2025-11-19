<?php
/**
 * 商城营销活动 -- 满包邮
 */

namespace app\mall\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\mall\model\service\activity\MallShippingActService;

class MallShippingController extends AuthBaseController
{
    /**
     * 获取满包邮-列表
     * User: 钱大双
     * Date: 2020-10-19
     */
    public function getShippingList()
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
            $mallShippingActService = new MallShippingActService();
            $result = $mallShippingActService->getShippingActList($store_id, $param);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 添加活动 和 编辑活动
     * User: 钱大双
     * Date: 2020-10-19
     * @return \json
     */
    public function addShipping()
    {
        $merId = $this->merchantUser['mer_id'] ?? 0;

        if ($merId < 0) {
            return api_output(1001, [], '商家ID不存在');
        }

        $param['mer_id'] = $merId;
        $param['type'] = 'shipping';
        $param['id'] = $this->request->param('id', 0, 'intval');
        $param['sort'] = 40;
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['full_type'] = $this->request->param('full_type', 1, 'intval');
        $param['nums'] = $this->request->param('nums', 0, 'intval');
        $param['act_type'] = $this->request->param('act_type', 0, 'intval');
        $param['goods_info'] = $this->request->param('goods_info', '');
        $param['join_max_num'] = $this->request->param('join_max_num', 0, 'intval');
        $param['is_discount_share'] = $this->request->param('is_discount_share', 2, 'intval');
        $param['discount_card'] = $this->request->param('discount_card', 0, 'intval');
        $param['discount_coupon'] = $this->request->param('discount_coupon', 0, 'intval');
        $param['desc'] = $this->request->param('desc', '', 'trim');
        $param['goods_info'] = json_decode($param['goods_info'], true);

       try {
            $res = (new MallShippingActService())->addShippingAct($param);
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
     * Date: 2020-10-19
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
            (new MallShippingActService())->changeState($param, $id);
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
            (new MallShippingActService())->del($param, $id);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取活动基本信息
     * User: 钱大双
     * Date: 2020-10-19
     */
    public function edit()
    {
        $id = $this->request->param('id', '', 'intval');

        try {
            $mallShippingActService = new MallShippingActService();
            $result = $mallShippingActService->getInfoById($id);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}