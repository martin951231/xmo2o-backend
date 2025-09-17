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
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserService;
use app\community\model\service\ManageAppLoginService;
use app\community\model\service\UserService;

class PowerController extends CommunityBaseController{


    /**
     * 获取已绑定小区列表
     * @author:zhubaodi
     * @date_time: 2021/4/9 11:49
     * @param 传参
     * array (
     *  'id'=>'登录id 必传',
     *  'type'=> '查询类型 0：查询已绑定小区 1：查询所有小区',
     *  'province_id'=> '省id',
     *  'city_id'=> '市id',
     *  'area_id'=> '区id',
     *  'street_id'=> '街道id',
     *  'community_id'=> '社区id',
     *  'village_id'=> '小区id',
     *  'village_name'=> '小区名称',
     * )
     */
    public function villageBindList() {
        // 获取登录信息
        $data['admin_id']=isset($this->request->log_uid) ? $this->request->log_uid : 0;
       //  $admin_id=1;
        if (!$data['admin_id']){
            return api_output(1002,[],'请先登录！');
        }
        $data['type'] = $this->request->param('type','','intval');
        $data['uid'] = $this->request->param('uid','','intval');
        $search = $this->request->param('search','','trim');
        if (isset($search['province'])){
            $data['province_id'] = $search['province'];
        }
        if (isset($search['city'])){
            $data['city_id'] = $search['city'];
        }

        if (isset($search['area'])){
            $data['area_id'] = $search['area'];
        }
        if (isset($search['street'])){
            $data['street_id'] = $search['street'];
        }
        if (isset($search['community'])){
            $data['community_id'] = $search['community'];
        }
        if (isset($search['village'])){
            $data['village_id'] = $search['village'];
        }
        if (isset($search['village_name'])){
            $data['village_name'] = $search['village_name'];
        }
        /*$data['area_id'] = $search['area'];
        $data['street_id'] = $search['street'];
        $data['community_id'] =$search['community'];
        $data['village_id'] = $search['village'];
        $data['village_name'] = $search['village_name'];*/
        $page = $this->request->param('page',0,'intval');
        $limit = 10;
        $serviceHouseMeter = new HouseMeterService();

        try {
            $villageBindLis = $serviceHouseMeter->getVillageBindList($data,$page,$limit);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        $villageBindLis['total_limit'] = $limit;
        return api_output(0,$villageBindLis);
    }


    /**
     * 绑定小区
     * @param integer $village_id
     * @param integer $uid
     * @author: zhubaodi
     * @date_time: 2021/4/8 17:40
     * @return \json
     */
    public function meterVillageAdd() {
        // 获取登录信息
        $admin_id=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id){
            return api_output(1002,[],'用户未登录或登录已失效，请先登录！！');
        }
        $uid = $this->request->param('uid','','intval');
        if (empty($uid)) {
            return api_output(1001,[],'请选择管理员！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            return api_output(1001,[],'请选择小区！');
        }

        $serviceHouseMeter = new HouseMeterService();
        try {
            $bind_id = $serviceHouseMeter->addMeterVillage($admin_id,$uid,$village_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        if ($bind_id){
            return api_output(0,['bind_id' => $bind_id],'绑定成功');
        }else{
            return api_output(1003,[],'绑定失败！');
        }
    }

    /**
     * 批量绑定小区
     * @param array $village_id[]
     * @param integer $uid
     * @author: zhubaodi
     * @date_time: 2021/4/9 15:40
     * @return \json
     */
    public function meterVillageAddll() {
        // 获取登录信息
        $admin_id=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id){
            return api_output(1002,[],'用户未登录或登录已失效，请先登录！！');
        }
        $uid = $this->request->param('uid','','intval');
        if (empty($uid)) {
            return api_output(1001,[],'请选择管理员！');
        }
        $village_id_arr = $this->request->param('village_id','','trim');
        if (empty($village_id_arr)) {
            return api_output(1001,[],'请选择小区！');
        }

       //  print_r($village_id_arr);exit;
        $count=0;
        foreach ($village_id_arr as $var){
            $serviceHouseMeter = new HouseMeterService();
            $bind_id = $serviceHouseMeter->addAllMeterVillage($admin_id,$uid,$var['village_id']);
            if (is_numeric($bind_id)){
                $count=$count+1;
            }
        }
       //  print_r($count);exit;
        if ($count>0){
            return api_output(0,[],'绑定成功');
        }else{
            return api_output(1003,[],'绑定失败！');
        }
    }

    /**
     * 移除已绑定小区
     * @author: zhubaodi
     * @date_time: 2021/4/8 17:40
     * @param integer $id
     * @return \json
     */
    public function meterVillageDelete() {
        // 获取登录信息
        $admin_id=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id){
            return api_output(1002,[],'用户未登录或登录已失效，请先登录！！');
        }

        $uid = $this->request->param('uid','','intval');
        if (empty($uid)) {
            return api_output(1001,[],'请选择管理员！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            return api_output(1001,[],'请选择小区！');
        }
        $serviceHouseMeter = new HouseMeterService();

        try {
            $bind_id = $serviceHouseMeter->deleteMeterVillage($admin_id,$uid,$village_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        if ($bind_id){
            return api_output(0,['$bind_id'=>$bind_id],'移除成功');
        }else{
            return api_output(1002,[],'移除失败');
        }
    }


    /**
     * 获取小区信息详情
     * @author: zhubaodi
     * @param integer $id
     * @date_time: 2021/4/8 16:31
     * @return \json
     */
    public function villageInfo() {
        // 获取登录信息
        $admin_id=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id){
            return api_output(1002,[],'请先登录！');
        }
        $id = $this->request->param('id','','intval');
        $serviceHouseMeter = new HouseMeterService();

        try {
            $village_info = $serviceHouseMeter->getVillageInfo($admin_id,$id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$village_info);

    }
    

}
