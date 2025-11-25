<?php
/**
 * 景区团体票旅行社接口控制器
 */

namespace app\life_tools\controller\api;

use app\life_tools\model\service\group\LifeToolsGroupSettingService;
use app\life_tools\model\service\group\LifeToolsGroupTravelAgencyService;
use think\Exception;

class LifeToolsGroupTravelAgencyController extends ApiBaseController
{
    /**
     * 获取旅行社认证页表单信息接口
     * @author nidan
     * @date 2022/3/21
     */
    public function getTravelCustomForm()
    {
        $this->checkLogin();
        $param['mer_id'] = $this->request->param('mer_id', 0, 'intval');//商家id
        $param['uid'] = $this->_uid;//用户id
        try {
            $customForm = (new LifeToolsGroupSettingService())->confirm($param);
            return api_output(0, $customForm, 'success');
        }catch (Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * 旅行社认证提交接口
     * @author nidan
     * @date 2022/3/21
     */
    public function travelAuthentication()
    {
        $this->checkLogin();
        $param['custom_form'] = $this->request->param('custom_form', '', 'trim');// 自定义表单
        $param['mer_id'] = $this->request->param('mer_id', 0, 'intval');//商家id
        $param['uid'] = $this->_uid;
        try {
            $add = (new LifeToolsGroupTravelAgencyService())->addAudit($param);
            if($add){
                return api_output(0, [], 'success');
            }else{
                throw new \think\Exception('提交认证审核失败');
            }
        }catch (Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取我的信息
     * @author nidan
     * @date 2022/3/23
     */
    public function userInfo()
    {
        $this->checkLogin();
        $param['uid'] = $this->_uid;
        try {
            $ary = (new LifeToolsGroupTravelAgencyService())->getTravelUser($param['uid']);
            if($ary){
                return api_output(0, $ary, 'success');
            }else{
                throw new \think\Exception('获取用户信息失败');
            }
        }catch (Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取热门景区列表或者我的认证列表
     * @author nidan
     * @date 2022/3/23
     * @param select_type 查询类型（0：查询热门景区（所有设置团体票的商家），1：查询我的认证（用户提交认证审核的已设置团体票的商家））
     */
    public function getMerchantList()
    {
        $this->checkLogin();
        $param['uid'] = $this->_uid;
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['select_type'] = $this->request->param('select_type', 0, 'intval');
        try {
            $ary = (new LifeToolsGroupTravelAgencyService())->getMerchantList($param);
            if($ary){
                return api_output(0, $ary, 'success');
            }else{
                throw new \think\Exception('获取商家景区列表失败');
            }
        }catch (Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
    }

}
