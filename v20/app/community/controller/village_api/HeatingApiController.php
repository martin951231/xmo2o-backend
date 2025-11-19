<?php
/**
 * 供暖功能管理
 */

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HeatingService;

class HeatingApiController extends CommunityBaseController
{
    /**
     * 申请停暖审核列表
     */
    public function closeHeatingList()
    {
        $param['village_id'] =  $this->request->param('village_id', 0,'intval');
        $param['pigcms_id'] = $this->request->param('pigcms_id', 0,'intval');
        $param['page'] = $this->request->param('page',0,'int');
        $param['limit']=$this->request->param('pageSize',10,'int');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        if ($param['pigcms_id']<1){
            return api_output_error(1001, '缺少必传参数');
        }
        try{
            $list = (new HeatingService())->closeHeatingList($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    
    /**
     * 新增申请停暖
     */
    public function addCloseHeating()
    {
        $param['village_id'] =  $this->request->param('village_id', 0,'intval');
        $param['room_id'] = $this->request->param('room_id',0,'trim');
        $param['name']= $this->request->param('name','','trim');
        $param['phone']= $this->request->param('phone','','trim');
        $param['application_time']=$this->request->param('application_time',time(),'trim');
        $param['house_property_evidence_img']= $this->request->param('house_property_evidence_img','','trim');
        $param['id_card_img']= $this->request->param('id_card_img','','trim');
        $param['id_card_back_img']= $this->request->param('id_card_back_img','','trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->addCloseHeating($param);
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
        $param['village_id'] =  $this->request->param('village_id', 0,'intval');
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
     * 查看停暖协议
     */
    public function getCloseHeatingProtocol()
    {
        $param['village_id'] =  $this->request->param('village_id', 0,'intval');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->getHeatingProtocol($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 取消停暖申请
     */
    public function delCloseHeating()
    {
        $userId = $this->request->param('pigcms_id',0,'trim');
        $login_role = 0;
        $param['operate_type'] = $login_role;
        $param['operate_uid'] = $userId;
        $param['village_id'] =  $this->request->param('village_id', 0,'intval');
        $param['pigcms_id'] = $this->request->param('application_id',0,'trim');
        $param['packapp'] = 1;
        if(!$param['village_id'] || !$userId){
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
        $param['village_id'] =  $this->request->param('village_id', 0,'intval');
        $param['pigcms_id'] =  $this->request->param('pigcms_id', 0,'intval');
        $param['page'] = $this->request->param('page',0,'int');
        $param['limit']=$this->request->param('pageSize',10,'int');
        if(!$param['village_id'] || !$param['pigcms_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $param['adminUser'] = $this->adminUser;
        $param['login_role'] = $this->login_role;
        $param['dismissPermissionRole'] = $this->dismissPermissionRole;
        try{
            $list = (new HeatingService())->flowChargingList($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 新增申请流量计费
     */
    public function addFlowCharging()
    {
        $param['village_id'] =  $this->request->param('village_id', 0,'intval');
        $param['room_id'] = $this->request->param('room_id',0,'trim');
        $param['name']= $this->request->param('name','','trim');
        $param['phone']= $this->request->param('phone','','trim');
        $param['application_time']=$this->request->param('application_time','','trim');
        $param['house_property_evidence_img']= $this->request->param('house_property_evidence_img','','trim');
        $param['id_card_img']= $this->request->param('id_card_img','','trim');
        $param['id_card_back_img']= $this->request->param('id_card_back_img','','trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->addFlowCharging($param);
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
        $param['village_id'] =  $this->request->param('village_id', 0,'intval');
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
     * 取消流量计费申请
     */
    public function delFlowCharging()
    {
        $userId = $this->request->param('pigcms_id',0,'trim');
        $login_role = 0;
        $param['operate_type'] = $login_role;
        $param['operate_uid'] = $userId;
        $param['village_id'] =  $this->request->param('village_id', 0,'intval');
        $param['pigcms_id'] = $this->request->param('application_id',0,'trim');
        $param['packapp'] = 1;
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

    /**
     * 查看流量计费申请协议
     */
    public function getFlowChargingProtocol()
    {
        $param['village_id'] =  $this->request->param('village_id', 0,'intval');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $param['type'] = 1;
        try{
            $list = (new HeatingService())->getHeatingProtocol($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    
    /**
     * 获取登录人信息
     */
    public function getRoomInfo()
    {
        $param['room_id'] =  $this->request->param('room_id', 0,'intval');
        $param['pigcms_id'] = $this->request->param('pigcms_id', 0,'intval');
        $village_id =  $this->request->param('village_id', 0,'intval');
        if(!$param['room_id'] && !$param['pigcms_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->getRoomInfo($param,$village_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
}