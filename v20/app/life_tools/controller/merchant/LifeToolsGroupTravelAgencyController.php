<?php
/**
 * 景区团体票旅行社
 */

namespace app\life_tools\controller\merchant;

use app\life_tools\model\service\group\LifeToolsGroupTravelAgencyService;
use app\merchant\controller\merchant\AuthBaseController;

class LifeToolsGroupTravelAgencyController extends AuthBaseController
{
    /**
     * 获取旅行社审核列表
     * @author nidan
     * @date 2022/3/21
     */
    public function getTravelList()
    {
        $params = [];
        $params['keyword_name'] = $this->request->post('keyword_name', '', 'trim');//查询名称
        $params['keyword_phone'] = $this->request->post('keyword_phone', '', 'trim');//查询手机号
        $params['status'] = $this->request->post('status', 'all', 'trim');//查询状态
        $params['page_size'] = $this->request->post('page_size', 10, 'trim,intval');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsGroupTravelAgencyService())->getTravelList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 旅行社认证审核
     * @author nidan
     * @date 2022/3/22
     */
    public function audit()
    {
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['travel_id'] = $this->request->post('travel_id',0, 'int');//旅行社id
        $params['status'] = $this->request->post('status','', 'trim,int');//审核状态
        $params['note'] = $this->request->post('note','', 'trim');//备注
        try {
            $data = (new LifeToolsGroupTravelAgencyService())->updateTravelStatus($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }


}
