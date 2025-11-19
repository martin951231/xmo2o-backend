<?php
/**
 * 统计分析
 */

namespace app\community\controller\platform;

use app\community\controller\platform\AuthBaseController as BaseController;
use app\community\model\service\StatisticsService;
use app\community\model\service\VoteService;

class StatisticsController extends BaseController
{
    /**
     * 获取缴费率
     */
    public function getContributionRateList()
    {
        $param['page'] = $this->request->param('page',0,'int');
        $param['pageSize']=$this->request->param('pageSize',10,'int');
        $param['type']=$this->request->param('type','village','trim');//获取类型（village-小区排名，charge_type-收费类别）
        $statisticsService = new StatisticsService();
        try {
            $data = $statisticsService->getContributionRateList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }
    /**
     * 获取工单完成率
     */
    public function getRepairRateList()
    {
        $param['page'] = $this->request->param('page',0,'int');
        $param['pageSize']=$this->request->param('pageSize',10,'int');
        $statisticsService = new StatisticsService();
        try {
            $data = $statisticsService->getRepairRateList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }
    /**
     * 获取巡检完成率
     */
    public function getPatrolInspectionRateList()
    {
        $param['page'] = $this->request->param('page',0,'int');
        $param['pageSize']=$this->request->param('pageSize',10,'int');
        $statisticsService = new StatisticsService();
        try {
            $data = $statisticsService->getPatrolInspectionRateList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }
    
}