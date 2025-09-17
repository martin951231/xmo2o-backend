<?php
/**
 * 下载导出文件
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/30 16:04
 */
namespace app\common\controller\common;

use app\common\controller\CommonBaseController;
use app\common\model\service\export\ExportService;
class ExportController extends CommonBaseController{
    public function initialize()
    {
        parent::initialize();
    }


    public function downloadExportFile(){
        $param['export_id'] = $this->request->param('id', '', 'intval');
     
        try {
            $result = (new ExportService())->downloadExportFile($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0,$result);
	}
}