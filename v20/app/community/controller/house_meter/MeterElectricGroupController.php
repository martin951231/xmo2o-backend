<?php

/**
 * 权限管理
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/4/9 11:47
 */

namespace app\community\controller\house_meter;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseMeterService;

class MeterElectricGroupController extends CommunityBaseController{


    /**
     * 获取电表分组列表
     * @author:zhubaodi
     * @date_time: 2021/4/10 9:57
     */
    public function meterElectricGroupList() {
        // 获取登录信息
        $admin_id=isset($this->request->log_uid) ? $this->request->log_uid : 0;
       //  $admin_id=1;
        if (!$admin_id){
            return api_output(1002,[],'请先登录！');
        }
        $page = $this->request->param('page',0,'intval');
        $limit = 20;
        $serviceHouseMeter = new HouseMeterService();
        $group_list = $serviceHouseMeter->getMeterElectricGroupList($admin_id,$page,$limit);
        return api_output(0,$group_list);
    }


    /**
     * 获取分组信息详情
     * @author: zhubaodi
     * @param integer $id
     * @date_time: 2021/4/8 16:31
     * @return \json
     */
    public function meterElectricGroupInfo() {
        // 获取登录信息
        $admin_id=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id){
            return api_output(1002,[],'请先登录！');
        }
        $id = $this->request->param('id','','intval');
        $serviceHouseMeter = new HouseMeterService();
        $groupInfo = $serviceHouseMeter->getElectricGroupInfo($admin_id,$id);
        return api_output(0,$groupInfo);

    }

    /**
     * 添加电表分组
     * @param 传参
     * array (
     *  'group_name'=> '分组名称 必传',
     *  'group_address'=> '逻辑地址',
     *  'address_status'=> '是否转换逻辑地址 0:否 1:是',
     *  'status'=> '分组状态 0:正常 1:关闭',
     * )
     * @author: zhubaodi
     * @date_time: 2021/4/10 10:20
     * @return \json
     */
    public function meterElectricGroupAdd() {
        // 获取登录信息
        $data['admin_id']=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$data['admin_id']){
            return api_output(1002,[],'用户未登录或登录已失效，请先登录！！');
        }

        $data['group_name'] = $this->request->param('group_name','','trim');
        if (empty( $data['group_name'])) {
            return api_output(1001,[],'请上传集中器名称！');
        }
        $data['group_address'] = $this->request->param('group_address','','trim');
        if (empty( $data['group_address'])) {
            return api_output(1001,[],'请上传逻辑地址！');
        }
        $data['status'] = $this->request->param('status','','intval');
        if (empty($data['status'])) {
            return api_output(1001,[],'请选择集中器状态！');
        }
        $serviceHouseMeter = new HouseMeterService();
        try {
            $electric_id = $serviceHouseMeter->addMeterElectricGroup($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($electric_id){
            return api_output(0,['electric_id' => $electric_id],'添加成功');
        }else{
            return api_output(1003,[],'添加失败！');
        }
    }


    /**
     * 编辑电表分组
     * @param 传参
     * array (
     * 'group_id'=> '分组名称 必传',
     *  'group_name'=> '分组名称 必传',
     *  'group_address'=> '逻辑地址',
     *  'address_status'=> '是否转换逻辑地址 0:否 1:是',
     *  'status'=> '分组状态 0:正常 1:关闭',
     * )
     * @author: zhubaodi
     * @date_time: 2021/4/10 10:20
     * @return \json
     */
    public function meterElectricGroupEdit() {
        // 获取登录信息
        $data['admin_id']=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$data['admin_id']){
            return api_output(1002,[],'用户未登录或登录已失效，请先登录！！');
        }
        $data['group_id'] = $this->request->param('id','','intval');
        if (empty( $data['group_id'])) {
            return api_output(1001,[],'请上传集中器id！');
        }

        $data['group_name'] = $this->request->param('group_name','','trim');
        if (empty( $data['group_name'])) {
            return api_output(1001,[],'请上传集中器名称！');
        }
        $data['group_address'] = $this->request->param('group_address','','trim');
        if (empty( $data['group_address'])) {
            return api_output(1001,[],'请上传逻辑地址！');
        }
        /*$data['address_status'] = $this->request->param('address_status','','intval');
        if (empty( $data['address_status'])) {
            return api_output(1001,[],'请选择是否转换逻辑地址！');
        }*/
        $data['status'] = $this->request->param('status','','intval');
        if (empty($data['status'])) {
            return api_output(1001,[],'请选择集中器状态！');
        }
        $serviceHouseMeter = new HouseMeterService();
        try {
            $group_id = $serviceHouseMeter->editMeterElectricGroup($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($group_id){
            return api_output(0,['group_id' => $group_id],'编辑成功');
        }else{
            return api_output(1003,[],'编辑失败！');
        }
    }


}
