<?php
/**
 * 智能垃圾回收设备管理
 */

namespace app\community\controller\platform\device;

use app\community\controller\platform\AuthBaseController as BaseController;
use app\community\model\service\Device\GarbageCollectionMachineService;

class GarbageCollectionMachineController extends BaseController
{
    /**
     * 设备同步
     */
    public function pullMachine()
    {
        try {
            $data = (new GarbageCollectionMachineService())->pullMachine();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 查询设备类型
     */
    public function getMachineType()
    {
        try {
            $data = (new GarbageCollectionMachineService())->getMachineType();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
    
    /**
     * 设备列表
     */
    public function machineList()
    {
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['device_no'] = $this->request->param("device_no", "", "trim");
        $param['name'] = $this->request->param("name", "", "trim");
        $param['type'] = $this->request->param("type", 0, "intval");
        $param['status'] = $this->request->param("status", 0, "intval");
        $param['village_id'] = $this->request->param("village_id", 0, "intval");
        try {
            $data = (new GarbageCollectionMachineService())->machineList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
    /**
     * 设备详情
     */
    public function machineDetail()
    {
        $param['id'] = $this->request->param("id", 0, "intval");//设备id
        try {
            $data = (new GarbageCollectionMachineService())->machineDetail($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
    /**
     * 设备绑定/解绑小区
     */
    public function machineBind()
    {
        $param['id'] = $this->request->param("id", 0, "intval");//设备id
        $param['village_id'] = $this->request->param("village_id", 0, "intval");//小区id
        $param['status'] = $this->request->param("status", -1, "intval");//关系：0：绑定，1：解绑
        try {
            $data = (new GarbageCollectionMachineService())->machineBind($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
    
    /**
     * 查询小区列表
     */
    public function villageList()
    {
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['keyword'] = $this->request->param("village_name", "", "trim");//小区名称
        $param['property_id'] = $this->request->param("property", 0, "intval");//省
        $param['city_id'] = $this->request->param("city", 0, "intval");//市
        $param['area_id'] = $this->request->param("area", 0, "intval");//区
        $param['street_id'] = $this->request->param("street", 0, "intval");//街道
        $param['community_id'] = $this->request->param("community", 0, "intval");//社区/村
        try {
            $data = (new GarbageCollectionMachineService())->villageList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
}