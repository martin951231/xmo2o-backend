<?php
/**
 * 智能垃圾回收预警管理
 */

namespace app\community\controller\village_api\Garbage;


use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\GarbageCollectionWarnService;

class GarbageCollectionWarnController extends CommunityBaseController
{
    /**
     * 预警类型
     */
    public function getWarnType()
    {
        try {
            $data = (new GarbageCollectionWarnService())->getWarnType();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }

    /**
     * 预警列表
     */
    public function warnList()
    {
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['device_no'] = $this->request->param("device_no", "", "trim");
        $param['name'] = $this->request->param("name", "", "trim");
        $param['type'] = $this->request->param("type", 0, "intval");
        $param['village_id'] = $this->adminUser['village_id'];
        $param['status'] = $this->request->param("status", 0, "intval");
        try {
            $data = (new GarbageCollectionWarnService())->warnList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
    /**
     * 预警详情
     */
    public function warnDetail()
    {
        $param['id'] = $this->request->param("id", 0, "intval");//预警id
        try {
            $data = (new GarbageCollectionWarnService())->warnDetail($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
}