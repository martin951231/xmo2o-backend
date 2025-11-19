<?php
namespace app\appoint\controller\api;

use app\appoint\model\service\MerchantWorkService;
use app\appoint\validate\WorkUser as WorkUserValidate;

class MerchantWorkController extends ApiBaseController
{
    const STATUS_OK = 1000;//状态码 正常
    protected $validateClass = WorkUserValidate::class;
    
    public function getWorkInfo(){
        try {
            $data = (new MerchantWorkService())->getWorkInfo(input());
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 技师列表
     * @return \think\response\Json
     */
    public function workerList()
    {
        $data = (new MerchantWorkService())->workerList(input());
        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 技师关联的预约列表
     * @return \think\response\Json
     */
    public function workerAppointList()
    {
        $params = $this->validateParameter();
        $data = (new MerchantWorkService())->workerAppointList($params['worker_id']);
        return api_output(self::STATUS_OK, $data);
    }
}