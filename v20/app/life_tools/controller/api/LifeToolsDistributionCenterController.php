<?php


namespace app\life_tools\controller\api;


use app\life_tools\model\service\distribution\LifeToolsDistributionOrderService;
use app\life_tools\model\service\distribution\LifeToolsDistributionUserService;

class LifeToolsDistributionCenterController extends ApiBaseController
{
    /**
     * 分销中心-查看详情
     */
    public function Detail(){
        $this->checkLogin();
        $params = $this->request->param();
        $params['uid'] = $this->_uid;
        try {
            $data = (new LifeToolsDistributionUserService())->distributionCenterDeail($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 分销中心-推广订单列表
     */
    public function spreadOrder(){
        $this->checkLogin();
        $params = $this->request->param();
        $params['uid'] = $this->_uid;
        try {
            $data = (new LifeToolsDistributionUserService())->distributionCenterOrder($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 分销中心-推广订单列表-tab状态列表
     * @return \json
     */
    public function spreadOrderTab(){
        $this->checkLogin();
        try {
            $data = (new LifeToolsDistributionUserService())->distributionCenterOrderTab();
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 分享海报接口
     * @author Nd
     * @date 2022/4/11
     * @return \json
     */
    public function share()
    {
        $this->checkLogin();
        $params['uid'] = $this->_uid;
        $params['avatar'] = $this->userInfo['avatar'];
        $params['tools_id'] = $this->request->post('tools_id', 0, 'intval');
        try {
            $data = (new LifeToolsDistributionOrderService())->getShareInfo($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }
}