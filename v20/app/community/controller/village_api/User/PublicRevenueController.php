<?php

namespace app\community\controller\village_api\User;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\PublicRevenueService;

class PublicRevenueController extends BaseController
{
    public function overview()
    {
        try {
            $result = (new PublicRevenueService())->publicOverview($this->request->post('village_id', 0));
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function quarterDetail()
    {
        try {
            $result = (new PublicRevenueService())->publicQuarterDetail($this->request->post('village_id', 0));
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}
