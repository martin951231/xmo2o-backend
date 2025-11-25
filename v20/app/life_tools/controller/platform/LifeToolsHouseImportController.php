<?php


namespace app\life_tools\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\excel\LifeToolsHouseImportService;

class LifeToolsHouseImportController extends AuthBaseController
{
    public function importHouse(){
        $param['file_url'] = $this->request->param('file_url','','trim,string');
        $param['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new LifeToolsHouseImportService())->importHouse($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}