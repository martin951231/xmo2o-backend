<?php

namespace app\social_activity\controller\socialactivity;

use app\social_activity\model\service\SocialActivityOrderService;
use app\social_activity\model\service\SocialActivityWithdrawService;
use app\social_activity\validate\SocialActivityOrderValidate;
use think\response\Json;

class SocialActivityOrderController extends AuthBaseController
{
    /**
     * 获取订单列表
     * @return Json
     */
    public function getOrderList(): Json
    {
        $validate = new SocialActivityOrderValidate();
        $params = request()->param();
        try {
            if (!$validate->scene('list')->check($params)) {
                throw new \Exception($validate->getError());
            }
            $service = new SocialActivityOrderService();
            $result = $service->getOrderList($params);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 申请提现
     * @return Json
     */
    public function applyWithdraw(): Json
    {
        $validate = new SocialActivityOrderValidate();
        $params = request()->param();
        try {
            if (!$validate->scene('withdraw')->check($params)) {
                throw new \Exception($validate->getError());
            }
            $params['admin_uid'] = $this->_uid;
            $service = new SocialActivityWithdrawService();
            $result = $service->applyWithdraw($params);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取提现记录列表
     * @return Json
     */
    public function getWithdrawRecordList(): Json
    {
        $validate = new SocialActivityOrderValidate();
        $params = request()->param();
        try {
            if (!$validate->scene('record')->check($params)) {
                throw new \Exception($validate->getError());
            }
            $service = new SocialActivityWithdrawService();
            $result = $service->getWithdrawList($params);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}
