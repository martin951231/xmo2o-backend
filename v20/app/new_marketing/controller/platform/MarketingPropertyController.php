<?php


namespace app\new_marketing\controller\platform;

use app\BaseController;
use app\common\controller\platform\AuthBaseController;
use app\new_marketing\model\service\MarketingPropertyService;

class MarketingPropertyController extends AuthBaseController
{
    /**
     * 获得社区列表
     */
    public function getPropertyList()
    {
        try {
            $param['area'] = $this->request->param('area');
            $param['area_uid'] = $this->request->param('area_uid', 0, 'intval');
            $param['begin_time'] = $this->request->param('begin_time', '', 'trim');
            $param['end_time'] = $this->request->param('end_time', '', 'trim');
            $param['merchant_name'] = $this->request->param('name', '', 'trim');
            $param['user_id'] = $this->request->param('user_id', 0, 'intval');
            $param['page'] = $this->request->param('page', 1, 'intval');
            $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
            $param['team_id'] = $this->request->param('team_id', 0, 'intval');
            $param['type'] = 1;
            if (empty($param['team_id'])) {
                return api_output_error(1003, "缺少团队id");
            }
            $list = (new MarketingPropertyService())->getPropertyList($param);
            return api_output(0, $list, 'success');
        } catch (\Exception $e) {
            dd($e);
        }
    }

    /**
     *业务转移
     */
    public function transferBusiness(){
        $param['person_id'] = $this->request->param('person_id', 0, 'intval');
        $param['id'] = $this->request->param('id', 0, 'intval');
        $param['team_id'] = $this->request->param('team_id', 0, 'intval');
        if (empty($param['team_id'])) {
                return api_output_error(1003, "缺少需要转移的团队信息");
        }
        if (empty($param['person_id'])) {
            return api_output_error(1003, "缺少需要转移的业务员");
        }
        if (empty($param['id'])) {
            return api_output_error(1003, "缺少需要转移业务记录");
        }
        $list = (new MarketingPropertyService())->transferBusiness($param);
        if($list){
            return api_output(0, [], 'success');
        }else{
            return api_output_error(1003, "业务转移失败");
        }

    }

    /**
     * 选择转移的业务员
     */
    public function selectBusinessMan(){
        $param['person_id'] = $this->request->param('person_id', 0, 'intval');
        $param['team_id'] = $this->request->param('team_id', 0, 'intval');
        if (empty($param['team_id'])) {
            return api_output_error(1003, "缺少需要转移的团队信息");
        }
        if (empty($param['person_id'])) {
            return api_output_error(1003, "缺少需要转移的业务员");
        }
        $list = (new MarketingPropertyService())->selectBusinessMan($param);
        if(!empty($list)){
            return api_output(0, $list, 'success');
        }else{
            return api_output_error(1003, "业务转移失败");
        }
    }
}