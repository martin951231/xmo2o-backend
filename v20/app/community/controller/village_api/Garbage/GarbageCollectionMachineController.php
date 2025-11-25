<?php
/**
 * 智能垃圾回收设备管理
 */

namespace app\community\controller\village_api\Garbage;

use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\GarbageCollectionMachineService;

class GarbageCollectionMachineController extends CommunityBaseController
{
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
        $param['village_id'] = $this->adminUser['village_id'];
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
}