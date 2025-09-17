<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/4/22 9:40
 */


namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillage;
use app\community\model\service\AockpitService;
use app\community\model\service\ApDeviceService;
use app\community\model\service\AreaService;
use app\community\model\service\HardwareBrandService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageNewsService;
use think\facade\Cache;
use app\community\model\service\AdminLoginService;

class ApDeviceController extends CommunityBaseController
{
    /**
     * 查询设备列表
     * @author:zhubaodi
     * @date_time: 2022/4/22 9:41
     */
    public function getDeviceList (){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['page'] = $this->request->param('page','1','intval');
        $data['ac_num'] = $this->request->param('ac_num','','trim');
        $data['ac_name'] = $this->request->param('ac_name','','trim');
        $data['status'] = $this->request->param('status','0','intval');
        $serviceApDevice = new ApDeviceService();
        try{
            $data['limit']=10;
            $res = $serviceApDevice->getDeviceList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询设备详情
     * @author:zhubaodi
     * @date_time: 2022/4/22 9:42
     */
    public function getDeviceInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['id'] = $this->request->param('id','0','intval');
        if (empty($data['id'])){
            return api_output_error(1001, '设备id不能为空！');
        }
        $serviceApDevice = new ApDeviceService();
        try{
            $res = $serviceApDevice->getDeviceInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 编辑设备信息
     * @author:zhubaodi
     * @date_time: 2022/4/22 9:42
     */
    public function addDevice(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['ac_name'] = $this->request->param('ac_name','','trim');
        $data['ac_num'] = $this->request->param('ac_num','','trim');
        $data['ac_area'] = $this->request->param('ac_area','','trim');
        $data['area_type'] = $this->request->param('area_type','','trim');
        $data['use_time'] = $this->request->param('use_time','','trim');
        $data['remark'] = $this->request->param('remark','','trim');
        if (empty($data['ac_name'])){
            return api_output_error(1001, '设备名称不能为空！');
        }
        if (empty($data['ac_num'])){
            return api_output_error(1001, '设备编号不能为空！');
        }
        if (empty($data['ac_area'])||empty($data['area_type'])){
            return api_output_error(1001, '设备位置不能为空！');
        }
        if (empty($data['use_time'])){
            return api_output_error(1001, '设备启用时间不能为空！');
        }
        $serviceApDevice = new ApDeviceService();
        try{
            $res= $serviceApDevice->addDevice($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 编辑设备信息
     * @author:zhubaodi
     * @date_time: 2022/4/22 9:42
     */
    public function editDevice(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['id'] = $this->request->param('id','0','intval');
        $data['ac_name'] = $this->request->param('ac_name','','trim');
        $data['ac_area'] = $this->request->param('ac_area','','trim');
        $data['area_type'] = $this->request->param('area_type','','trim');
        $data['remark'] = $this->request->param('remark','','trim');
        $data['use_time'] = $this->request->param('use_time','','trim');
        // print_r($data);exit;
        if (empty($data['ac_name'])){
            return api_output_error(1001, '设备名称不能为空！');
        }
        if (empty($data['ac_area'])||empty($data['area_type'])){
            return api_output_error(1001, '设备位置不能为空！');
        }
        if (empty($data['use_time'])){
            return api_output_error(1001, '设备启用不能为空！');
        }
        $serviceApDevice = new ApDeviceService();
        try{
            $res= $serviceApDevice->editDevice($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 删除设备
     * @author:zhubaodi
     * @date_time: 2022/4/22 9:43
     */
    public function delDevice(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['id'] = $this->request->param('id','0','intval');
        if (empty($data['id'])){
            return api_output_error(1001, '设备id不能为空！');
        }
        $serviceApDevice = new ApDeviceService();
        try{
            $res = $serviceApDevice->delDevice($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 获取位置列表
     * @author:zhubaodi
     * @date_time: 2022/4/22 15:55
     */
    public function getAddressList(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['type'] = $this->request->param('type','0','intval');
        $data['id'] = $this->request->param('id','0','intval');
        $serviceApDevice = new ApDeviceService();
        try{
            $res = $serviceApDevice->getAddressList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 添加设备获取设备下拉框列表
     * @author:zhubaodi
     * @date_time: 2022/4/22 15:55
     */
    public function getAddDeviceList(){
        $data['village_id'] = $this->adminUser['village_id'];
        $serviceApDevice = new ApDeviceService();
        try{
            $res = $serviceApDevice->getAddDeviceList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function test(){
        $data['village_id'] = $this->adminUser['village_id'];
        $serviceApDevice = new ApDeviceService();
        try{
            $res = $serviceApDevice->getDeviceStatus();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
}