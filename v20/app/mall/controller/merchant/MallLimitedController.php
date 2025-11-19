<?php
/**
 * 商家后台营销活动 -- 限时优惠（秒杀）
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/10/23 17:48
 */

namespace app\mall\controller\merchant;


use app\mall\model\service\activity\MallLimitedActService;
use app\merchant\controller\merchant\AuthBaseController;

class MallLimitedController extends AuthBaseController
{
    /**
     * 获取限时优惠活动列表
     * User: chenxiang
     * Date: 2020/10/23 17:55
     * @return \json
     */
    public function getLimitedList()
    {
        $merId = $this->merchantUser['mer_id'] ?? 0;

        if ($merId < 0) {
            return api_output(1001, [], '商家ID不存在');
        }
        //店铺id
        $store_id = $this->request->param('store_id', '', 'intval');
        if ($store_id < 1) {
            return api_output(1001, [], '店铺ID不存在');
        }

        //每页页数
        $param['pageSize'] = $this->request->param('pageSize', '10', 'intval');
        //页码
        $param['page'] = $this->request->param('page', '1', 'intval');

        //查询条件
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['status'] = $this->request->param('status', 3, 'intval');

        $mallLimitedActService = new MallLimitedActService();
        try {
            $result = $mallLimitedActService->getLimitedList($store_id, $param);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }


    /**
     * 添加/编辑
     * User: chenxiang
     * Date: 2020/10/23 17:57
     * @return \json
     */
    public function addLimited()
    {
        $merId = $this->merchantUser['mer_id'] ?? 0;

        if ($merId < 0) {
            return api_output(1001, [], '商家ID不存在');
        }

        $param = $this->request->param();
        $param['mer_id'] = $merId;
        $param['type'] = 'limited';
        $param['sort'] = 10;
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['id'] = $this->request->param('id', 0, 'intval');
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['is_discount_share'] = $this->request->param('is_discount_share', 2, 'intval');
        $param['discount_card'] = $this->request->param('discount_card', 0, 'intval');
        $param['discount_coupon'] = $this->request->param('discount_coupon', 0, 'intval');
        $param['notice_type'] = $this->request->param('notice_type', 1, 'intval');
        $param['notice_time'] = $this->request->param('notice_time', 0, 'intval');

        if ($param['store_id'] < 1) {
            throw new \think\Exception("店铺ID不存在");
        }

        try {
            if (empty($param['id'])) {
                $res = (new MallLimitedActService())->addLimitedAct($param, 'add');
            } else {
                $res = (new MallLimitedActService())->addLimitedAct($param, 'edit');
            }

            if ($res['status'] == 0) {
                return api_output(1000, [], $res['msg']);
            } else {
                return api_output(1003, [], $res['msg']);
            }

        } catch (\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }


    /**
     * 编辑页面信息
     * User: chenxiang
     * Date: 2020/10/26 16:10
     * @return \json
     */
    public function edit()
    {
        $id = $this->request->param('id', '', 'intval'); //限时优惠表id
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }

        try {
            $mallLimitedActService = new MallLimitedActService();
            $result = $mallLimitedActService->getInfoById($id);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 失效操作
     * User: chenxiang
     * Date: 2020/10/23 17:58
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
            (new MallLimitedActService())->changeState($param, $id);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }


    /**
     * 删除操作
     * User: chenxiang
     * Date: 2020/10/23 17:59
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
            (new MallLimitedActService())->del($param, $id);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }


}