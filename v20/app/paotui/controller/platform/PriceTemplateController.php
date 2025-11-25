<?php

declare(strict_types=1);

namespace app\paotui\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\paotui\model\service\PriceTemplateService;

//跑腿计费模板
class PriceTemplateController extends AuthBaseController
{
    //获取列表
    public function getLists()
    {
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['page_size'] = $this->request->param('page_size', 10, 'intval');
        $params['bind_business'] = $this->request->param('bind_business', -1, 'intval');
        $params['status'] = $this->request->param('status', -1, 'intval');
        $lists = (new PriceTemplateService())->getLists($params);
        return api_output(0, $lists);
    }

    //保存
    public function savePriceTemplate()
    {
        $params = $this->request->param();
        try {
            (new PriceTemplateService())->savePriceTemplate($params);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //修改状态
    public function changePriceTemplateStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        $template = (new \app\paotui\model\db\PaotuiPriceTemplate())->where('id', $id)->find();
        if (!$template) {
            return api_output_error(1001, '数据不存在');
        }
        if ($template->status == 0 && $template->bind_business <> 3) {
            //如果是要开启状态，判断业务类型模板是否有其他开启的模板
            $find = (new \app\paotui\model\db\PaotuiPriceTemplate())->where(
                [
                    'bind_business' => $template->bind_business,
                    'special_delivery' => $template->special_delivery,
                    'status' => 1
                ]
            )->find();
            if ($find) {
                return api_output_error(1001, sprintf('该业务类型模板已存在开启的模板【ID:%s】', $find->id));
            }
        }

        $template->status = $template->status == 0 ? 1 : 0;
        $template->save();
        return api_output(0, ['status' => $template->status]);
    }

    //删除
    public function deletePriceTemplate()
    {
        $id = $this->request->param('id', 0, 'intval');
        $template = (new \app\paotui\model\db\PaotuiPriceTemplate())->where('id', $id)->find();
        if (!$template) {
            return api_output_error(1001, '数据不存在');
        }
        $template->delete();
        return api_output(0, []);
    }

    //商家
    public function merchant()
    {
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['page_size'] = $this->request->param('page_size', 10, 'intval');
        $params['template_id'] = $this->request->param('template_id', 0, 'intval');
        $params['name'] = $this->request->param('name', '', 'trim');
        $params['phone'] = $this->request->param('phone', '', 'trim');
        $params['status'] = $this->request->param('status', -1, 'intval');
        $lists = (new PriceTemplateService())->merchant($params);
        return api_output(0, $lists);
    }
}
