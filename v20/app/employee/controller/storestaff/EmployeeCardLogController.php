<?php

namespace app\employee\controller\storestaff;

use app\employee\model\service\EmployeeCardLogService;
use app\employee\model\service\EmployeeCardService;
use app\employee\model\service\ExportService;
use app\storestaff\controller\storestaff\AuthBaseController;

class EmployeeCardLogController extends AuthBaseController
{
    public function dataStatistics()
    {
        $params['mer_id'] = $this->merId;
        $params['staff_id'] = $this->staffId;
        $params['staff'] = $this->staffUser;
        try {
            $data = (new EmployeeCardLogService)->dataStatistics($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}