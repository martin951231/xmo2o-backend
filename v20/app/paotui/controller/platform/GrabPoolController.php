<?php

declare(strict_types=1);

namespace app\paotui\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\paotui\model\service\GrabPoolService;

//抢单池
class GrabPoolController extends AuthBaseController
{
    public function getGrabPoolLists()
    {
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['page_size'] = $this->request->param('page_size', 10, 'intval');
        $lists = (new GrabPoolService())->getLists($params);
        return api_output(0, $lists);
    }

    public function saveGrabPool()
    {
        $params = $this->request->param();
        try {
            (new GrabPoolService())->saveGrabPool($params);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function changeGrabPoolStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        $pool = (new \app\paotui\model\db\PaotuiGrabPool())->where('id', $id)->find();
        if (!$pool) {
            return api_output_error(1001, '数据不存在');
        }
        $pool->status = $pool->status == 0 ? 1 : 0;
        $pool->save();
        return api_output(0, ['status' => $pool->status]);
    }

    public function deleteGrabPool()
    {
        $id = $this->request->param('id', 0, 'intval');
        (new GrabPoolService())->deleteGrabPool($id);
        return api_output(0, []);
    }

    //商家
    public function merchant()
    {
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['page_size'] = $this->request->param('page_size', 10, 'intval');
        $params['pool_id'] = $this->request->param('pool_id', 0, 'intval');
        $params['name'] = $this->request->param('name', '', 'trim');
        $params['phone'] = $this->request->param('phone', '', 'trim');
        $params['status'] = $this->request->param('status', -1, 'intval');
        $lists = (new GrabPoolService())->merchant($params);
        return api_output(0, $lists);
    }

    //变更商家绑定状态
    public function changeBindMerchantStatus()
    {
        $poolId = $this->request->param('pool_id', 0, 'intval');
        $merId = $this->request->param('mer_id', 0, 'intval');

        (new GrabPoolService())->changeBindMerchantStatus($poolId, $merId);
        return api_output(0, []);
    }

    //骑手
    public function deliverUser()
    {
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['page_size'] = $this->request->param('page_size', 10, 'intval');
        $params['pool_id'] = $this->request->param('pool_id', 0, 'intval');
        $params['name'] = $this->request->param('name', '', 'trim');
        $params['phone'] = $this->request->param('phone', '', 'trim');
        $params['register_type'] = $this->request->param('register_type', -1, 'intval');
        $lists = (new GrabPoolService())->deliverUser($params);
        return api_output(0, $lists);
    }

    //修改骑手绑定状态
    public function changeBindDeliverUserStatus()
    {
        $poolId = $this->request->param('pool_id', 0, 'intval');
        $deliverUid = $this->request->param('deliver_uid', 0, 'intval');

        (new GrabPoolService())->changeBindDeliverUserStatus($poolId, $deliverUid);
        return api_output(0, []);
    }
}
