<?php

declare(strict_types=1);

namespace app\paotui\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\paotui\model\service\PriceTemplateService;

//跑腿商家
class MerchantController extends AuthBaseController
{
    //获取所有商家
    public function getLists()
    {
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['page_size'] = $this->request->param('page_size', 10, 'intval');
        $params['name'] = $this->request->param('name', '', 'trim');
        $params['phone'] = $this->request->param('phone', '', 'trim');
        $params['status'] = $this->request->param('status', -1, 'intval');
        $lists = (new PriceTemplateService())->getAllMerchant($params);
        return api_output(0, $lists);
    }

    //获取商家普通、专送跑腿模板
    public function getTemplateSelect()
    {
        $lists = (new PriceTemplateService())->getTemplateSelect();
        return api_output(0, $lists);
    }

    //单商家绑定跑腿模板
    public function bindTemplate()
    {
        $merId = $this->request->param('mer_id', 0, 'intval');
        $templateId = $this->request->param('template_id', 0, 'intval');
        $specialDeliveryTemplateId = $this->request->param('special_delivery_template_id', 0, 'intval');
        if (empty($merId)) {
            return api_output_error(1001, '参数错误');
        }
        (new PriceTemplateService())->bindTemplate($merId, $templateId, $specialDeliveryTemplateId);
        return api_output(0, []);
    }

    //批量商家绑定跑腿模板
    public function batchBindTemplate()
    {
        $merIds = $this->request->param('mer_ids', [], 'intval');
        $templateId = $this->request->param('template_id', 0, 'intval');
        $specialDeliveryTemplateId = $this->request->param('special_delivery_template_id', 0, 'intval');
        if (empty($merIds)) {
            return api_output_error(1001, '请选择操作商家');
        }
        (new PriceTemplateService())->batchBindTemplate($merIds, $templateId, $specialDeliveryTemplateId);
        return api_output(0, []);
    }
}
