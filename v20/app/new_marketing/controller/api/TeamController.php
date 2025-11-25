<?php

namespace app\new_marketing\controller\api;

use app\new_marketing\model\service\MarketingTeamService;

class TeamController extends ApiBaseController
{
	//团队详情
    public function getTeamDetail(){
//        $this->_uid = 112358890;
        if (empty($this->_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }
//        $type = $this->request->param('type', 0, 'intval');//1=技术人员,2=技术主管,3=区域代理(无业务经理),4=区域代理(兼业务经理)
        $team_id = $this->request->param('team_id', 0, 'intval');//团队ID
        try {
            $data = (new MarketingTeamService())->getTeamDetail($team_id);
            return api_output(0, $data, '获取成功');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}