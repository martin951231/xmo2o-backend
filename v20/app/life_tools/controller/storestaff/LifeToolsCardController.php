<?php


namespace app\life_tools\controller\storestaff;


use app\life_tools\model\service\LifeToolsCardOrderService;
use app\storestaff\controller\storestaff\AuthBaseController;

class LifeToolsCardController extends AuthBaseController
{
    public function verifyList(){
        $params = [];
        $params['staff_id'] = $this->staffId;
        $params['fvalue'] = $this->request->param('fv','','trim,string');//搜索关键词
        $params['ftype']  = $this->request->param('ft','','trim,string');//搜索关键词类型
        $params['stime']  = $this->request->param('stime','','trim,string');//搜索开始时间
        $params['etime']  = $this->request->param('etime','','trim,string');//搜索结束时间
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['page_size'] = $this->request->param('page_size',10,'trim,intval');
        try {
            $data = (new LifeToolsCardOrderService())->cardVerifyList($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
}