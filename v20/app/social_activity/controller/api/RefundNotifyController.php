<?php
namespace app\social_activity\controller\api;

use app\BaseController;
use app\social_activity\model\service\SocialActivityRefundService;

class RefundNotifyController extends BaseController
{
    public function notify()
    {
        try {
            $params = request()->post();
            if (!$params) { parse_str(file_get_contents('php://input'), $params); }
            (new SocialActivityRefundService())->notify($params);
            return response('success', 200, [], 'html');
        } catch (\Throwable $e) {
            // 不回显签名、密钥、原支付配置。
            error_log('Social activity refund notification: ' . $e->getMessage());
            return response('fail', 200, [], 'html');
        }
    }
}
