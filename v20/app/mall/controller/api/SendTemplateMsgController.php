<?php
/**
 * SendTemplateMsgController.php
 * 推送消息controller
 * Create on 2021/1/22 15:18
 * Created by zhumengqun
 */

namespace app\mall\controller\api;

use app\BaseController;
use app\mall\model\service\SendTemplateMsgService;

class SendTemplateMsgController extends BaseController
{
    public function getTemplateId()
    {
        $sendService = new SendTemplateMsgService();
        $type = $this->request->param('type', '', 'trim');
        try {
            $arr = $sendService->getTemplateId($type);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}