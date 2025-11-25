<?php
/**
 * 计划任务调用
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/07/01 09:21
 */
namespace app\common\controller\common;

use app\common\controller\CommonBaseController;
use app\common\model\service\export\ExportService;
use app\common\model\service\plan\PlanService;
class PlanController extends CommonBaseController{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 文件导出计划任务
     */
    public function export(){
        $param['export_id'] = $this->request->param('export_id', '', '');
        try {
            $result = (new ExportService())->runTask($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0,$result);
    }

    /**
     * 计划任务执行
     */
    public function runTask(){
        $param = $this->request->get();
        fdump('$param','v20_plan',1);
        fdump($param,'v20_plan',1);
        try {
            $result = (new PlanService())->runTask($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0,$result);
    }

}