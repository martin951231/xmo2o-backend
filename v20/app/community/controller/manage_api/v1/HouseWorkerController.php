<?php
/**
 * 工作人员相关
**/
namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\HouseWorkerService;
class HouseWorkerController extends BaseController
{
    /**
     * 获取工作人员
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author weili
     * @daretime 2020/07/14 17:50
     * @param integer $type 人员类型(0:客服专员，1：技工)
     * @param int $villageId 社区id
     */
    public function getWorkerAll()
    {
        $type = $this->request->param('type','','trim');
        $villageId = $this->request->param('village_id','','int');

        if(empty($villageId) || is_numeric($type) === false)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceHouseWorker = new HouseWorkerService();
        $where[] = ['village_id','=',$villageId];
        $where[] = ['type','=',$type];
        $where[] = ['status','=',1];
        $where[] = ['is_del','=',0];
        $data = $serviceHouseWorker->getWorker($where);
        if(!$data || $data->isEmpty()){
            $data=[
                [
                    'wid'=>'',
                    'name'=>'暂无数据，请先添加工作人员',
                    'type'=>$type,
                    'phone'=>''
                ]
            ];
        }
        return api_output(0,$data,'成功');
    }
}
