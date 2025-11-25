<?php


namespace app\life_tools\controller\merchant;
use app\BaseController;
use app\life_tools\model\service\LifeScenicLimitedActService;
use app\merchant\controller\merchant\AuthBaseController;

class LifeScenicLimitedActController extends AuthBaseController
{
    /**
     * 获取限时优惠活动列表
     * @return \json
     */
    public function getLimitedList()
    {
        $merId = $this->merchantUser['mer_id'] ?? 0;
        if ($merId < 0) {
            return api_output(1001, [], '商家ID不存在');
        }
        $param['mer_id'] =$merId;
        //查询条件
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['status'] = $this->request->param('status', 3, 'intval');
        $mallLimitedActService = new LifeScenicLimitedActService();
        try {
            $result = $mallLimitedActService->getLimitedList($param);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 添加/编辑
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
        $param['notice_type'] = $this->request->param('notice_type', 1, 'intval');
        $param['notice_time'] = $this->request->param('notice_time', 0, 'intval');
        try {
            if (empty($param['id'])) {
                $res = (new LifeScenicLimitedActService())->addLimitedAct($param, 'add');
            } else {
                $res = (new LifeScenicLimitedActService())->addLimitedAct($param, 'edit');
            }

            if ($res['status'] == 0) {
                return api_output(1000, [], $res['msg']);
            } else {
                return api_output(1003, [], $res['msg']);
            }
        } catch (\Exception $e) {
            dd($e);
            //return api_output_error(1003, [], $e->getMessage());
        }
    }


    /**
     * 编辑页面信息
     * @return \json
     */
    public function edit()
    {
        $id = $this->request->param('id', '', 'intval'); //限时优惠表id
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }

        try {
            $mallLimitedActService = new LifeScenicLimitedActService();
            $result = $mallLimitedActService->getInfoById($id);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 失效操作
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
            (new LifeScenicLimitedActService())->changeState($param, $id);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }


    /**
     * 删除操作
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
            (new LifeScenicLimitedActService())->del($param, $id);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }
}