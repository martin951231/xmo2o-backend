<?php

declare(strict_types=1);

namespace app\paotui\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\paotui\model\service\AddedFeeService;

//增值费用
class AddedFeeController extends AuthBaseController
{
    //获取列表
    public function getLists()
    {
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['page_size'] = $this->request->param('page_size', 10, 'intval');
        $lists = (new AddedFeeService())->getLists($params);
        return api_output(0, $lists);
    }

    //保存
    public function saveAddedFee()
    {
        $params = $this->request->param();
        try {
            (new AddedFeeService())->saveAddedFee($params);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //修改状态
    public function changeAddedFeeStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        $fee = (new \app\paotui\model\db\PaotuiAddedFee())->where('id', $id)->find();
        if (!$fee) {
            return api_output_error(1001, '数据不存在');
        }
        $fee->status = $fee->status == 0 ? 1 : 0;
        $fee->save();
        return api_output(0, ['status' => $fee->status]);
    }

    //删除
    public function deleteAddedFee()
    {
        $id = $this->request->param('id', 0, 'intval');
        $template = (new \app\paotui\model\db\PaotuiAddedFee())->where('id', $id)->find();
        if (!$template) {
            return api_output_error(1001, '数据不存在');
        }
        $template->delete();
        return api_output(0, []);
    }
}
