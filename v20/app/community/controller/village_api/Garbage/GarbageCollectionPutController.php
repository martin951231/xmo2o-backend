<?php
/**
 * 智能垃圾回收投放记录管理
 */

namespace app\community\controller\village_api\Garbage;

use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\GarbageCollectionPutService;

class GarbageCollectionPutController extends CommunityBaseController
{
    /**
     * 投放记录列表
     */
    public function putList()
    {
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['device_no'] = $this->request->param("device_no", "", "trim");
        $param['name'] = $this->request->param("name", "", "trim");
        $param['nickname'] = $this->request->param("nickname", "", "trim");
        $param['clnt_type'] = $this->request->param("clnt_type", 0, "intval");
        $param['village_id'] = $this->adminUser['village_id'];
        try {
            $data = (new GarbageCollectionPutService())->putList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
    /**
     * 投放记录详情
     */
    public function putDetail()
    {
        $param['id'] = $this->request->param("id", 0, "intval");//投放记录id
        try {
            $data = (new GarbageCollectionPutService())->putDetail($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data,'success');
    }
}