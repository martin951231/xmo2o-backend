<?php
/**
 * 供暖功能管理
 */

namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\AdminLoginService;
use app\community\model\service\HeatingService;
use app\traits\house\AdminLoginTraits;

class HeatingController extends BaseController
{
    use AdminLoginTraits;
    /**
     * 申请停暖审核列表
     */
    public function closeHeatingList()
    {
        $param['village_id'] = $this->request->param('village_id',0,'int');
        $param['page'] = $this->request->param('page',0,'int');
        $param['limit']=$this->request->param('pageSize',10,'int');
        $param['keyword']= $this->request->param('keyword','','trim');
        $param['status']= $this->request->param('status',-1,'trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $adminLoginService = new AdminLoginService();
        $param['adminUser'] = $adminLoginService->handleLoginData($this->request->log_uid,$this->request->log_extends);
        $param['login_role'] = $this->request->log_extends;
        $param['dismissPermissionRole'] = $adminLoginService->dismissPermissionRole;
        try{
            $list = (new HeatingService())->closeHeatingList($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    
    /**
     * 查看申请停暖详情
     */
    public function getCloseHeatingInfo()
    {
        $param['village_id'] =  $this->request->param('village_id',0,'int');
        $param['pigcms_id'] = $this->request->param('application_id',0,'trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->getCloseHeatingInfo($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    
    /**
     * 审核停暖申请
     */
    public function auditCloseHeating()
    {
        $userId = trim($this->request->log_uid);
        $login_role = trim($this->request->log_extends);
        $parseInfo = $this->traitPaseTokenRequestWhoim($userId,$login_role);
        $param['village_id'] =  $this->request->param('village_id',0,'int');
        $param['pigcms_id'] = $this->request->param('application_id',0,'trim');
        $param['audit_status'] = $this->request->param('audit_status',0,'trim');
        $param['audit_msg'] = $this->request->param('audit_msg','','trim');
        $param['user_type'] = $this->request->param('user_type',0,'trim');
        $param['operate_type'] = $parseInfo['op_type'] ?? 0;
        $param['operate_uid'] = $parseInfo['op_id'] ?? 0;
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->auditCloseHeating($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    
    /**
     * 检查停暖申请
     */
    public function inspectCloseHeating()
    {
        $userId = trim($this->request->log_uid);
        $login_role = trim($this->request->log_extends);
        $parseInfo = $this->traitPaseTokenRequestWhoim($userId,$login_role);
        $param['village_id'] =  $this->request->param('village_id',0,'int');
        $param['pigcms_id'] = $this->request->param('application_id',0,'trim');
        $param['is_measure_up'] = $this->request->param('is_measure_up',0,'trim');
        $param['measure_up_msg'] = $this->request->param('measure_up_msg','','trim');
        $param['measure_up_img'] = $this->request->param('measure_up_img','','trim');
        $param['operate_type'] = $parseInfo['op_type'] ?? 0;
        $param['operate_uid'] = $parseInfo['op_id'] ?? 0;
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->inspectCloseHeating($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 删除停暖申请
     */
    public function delCloseHeating()
    {
        $userId = trim($this->request->log_uid);
        $login_role = trim($this->request->log_extends);
        $parseInfo = $this->traitPaseTokenRequestWhoim($userId,$login_role);
        $param['operate_type'] = $parseInfo['op_type'] ?? 0;
        $param['operate_uid'] = $parseInfo['op_id'] ?? 0;
        $param['village_id'] =  $this->request->param('village_id',0,'int');
        $param['pigcms_id'] = $this->request->param('pigcms_id',0,'trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->delCloseHeating($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 申请流量计费列表
     */
    public function flowChargingList()
    {
        $param['village_id'] =  $this->request->param('village_id',0,'int');
        $param['page'] = $this->request->param('page',0,'int');
        $param['limit']=$this->request->param('pageSize',10,'int');
        $param['keyword']= $this->request->param('keyword','','trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $adminLoginService = new AdminLoginService();
        $param['adminUser'] = $adminLoginService->handleLoginData($this->request->log_uid,$this->request->log_extends);
        $param['login_role'] = $this->request->log_extends;
        $param['dismissPermissionRole'] = $adminLoginService->dismissPermissionRole;
        try{
            $list = (new HeatingService())->flowChargingList($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 查看申请流量计费详情
     */
    public function getFlowChargingInfo()
    {
        $param['village_id'] = $this->request->param('village_id',0,'int');
        $param['pigcms_id'] = $this->request->param('application_id',0,'trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->getFlowChargingInfo($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 审核流量计费申请
     */
    public function auditFlowCharging()
    {
        $userId = trim($this->request->log_uid);
        $login_role = trim($this->request->log_extends);
        $parseInfo = $this->traitPaseTokenRequestWhoim($userId,$login_role);
        $param['village_id'] =  $this->request->param('village_id',0,'int');
        $param['pigcms_id'] = $this->request->param('application_id',0,'trim');
        $param['audit_status'] = $this->request->param('audit_status',0,'trim');
        $param['audit_msg'] = $this->request->param('audit_msg','','trim');
        $param['operate_type'] = $parseInfo['op_type'] ?? 0;
        $param['operate_uid'] = $parseInfo['op_id'] ?? 0;
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->auditFlowCharging($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 删除流量计费申请
     */
    public function delFlowCharging()
    {
        $userId = trim($this->request->log_uid);
        $login_role = trim($this->request->log_extends);
        $parseInfo = $this->traitPaseTokenRequestWhoim($userId,$login_role);
        $param['operate_type'] = $parseInfo['op_type'] ?? 0;
        $param['operate_uid'] = $parseInfo['op_id'] ?? 0;
        $param['village_id'] =  $this->request->param('village_id',0,'int');
        $param['pigcms_id'] = $this->request->param('pigcms_id',0,'trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->delFlowCharging($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

}