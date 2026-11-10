<?php

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\PublicRevenueService;

class PublicRevenueController extends CommunityBaseController
{
    public function subjectList()
    {
        return $this->handle('subjectList');
    }

    public function subjectSave()
    {
        return $this->handle('saveSubject');
    }

    public function subjectStatus()
    {
        return $this->handle('subjectStatus');
    }

    public function subjectDelete()
    {
        return $this->handle('deleteSubject');
    }

    public function periodDetail()
    {
        return $this->handle('periodDetail');
    }

    public function periodSave()
    {
        return $this->handle('savePeriod');
    }

    public function operationLogList()
    {
        return $this->handle('logList');
    }

    private function handle($method)
    {
        try {
            $result = (new PublicRevenueService())->{$method}($this->adminUser, $this->request->post());
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}
