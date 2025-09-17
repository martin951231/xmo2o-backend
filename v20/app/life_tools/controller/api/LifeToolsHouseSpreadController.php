<?php


namespace app\life_tools\controller\api;


use app\life_tools\model\service\house\HouseSpreadService;

class LifeToolsHouseSpreadController extends ApiBaseController
{
    /**
     * 获取当前用户分销人员列表
     * @return \think\response\Json
     */
    public function mySpreadList(){
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        try {
            $res = (new HouseSpreadService())->getSpreadUserList($params);
            return api_output(1000,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}