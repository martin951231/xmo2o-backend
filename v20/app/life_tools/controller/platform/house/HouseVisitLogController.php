<?php


namespace app\life_tools\controller\platform\house;


use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\house\HouseVisitLogService;

class HouseVisitLogController extends AuthBaseController
{
    /**
     * 浏览记录
     * @return \think\response\Json
     */
    public function getList(){
        $params['visit_type'] = $this->request->param('visit_type', '', 'trim,string');//访问途径
        $params['search_type'] = $this->request->param('search_type', 0, 'trim,intval');
        $params['keywords'] = $this->request->param('keywords', '', 'trim,string');
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $params['end_time'] = $this->request->param('end_time', '', 'trim,string');
        $params['start_time'] = $this->request->param('start_time', '', 'trim,string');
        try {
            $list=(new HouseVisitLogService())->getList($params);
            return api_output(0, $list, 'success');
        }catch (\Exception $e){
            return api_output(1003, $e->getMessage());
        }
        
    }
}