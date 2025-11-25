<?php

declare(strict_types=1);

namespace app\paotui\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\paotui\model\service\RiderCommissionService;

//骑手跑腿佣金设置
class RiderCommissionSettingController extends AuthBaseController
{

    //保存骑手配置
    public function saveRiderCommissionSetting()
    {
        try {
            $params = $this->request->param();
            (new RiderCommissionService())->saveRiderCommissionSetting($params);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function getRiderCommissionSetting()
    {
        try {
            $deliverUid = $this->request->param('deliver_uid');
            $setting = (new RiderCommissionService())->getRiderCommissionSetting($deliverUid);
            return api_output(0,   $setting);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}
