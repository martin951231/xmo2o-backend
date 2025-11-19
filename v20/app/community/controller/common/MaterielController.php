<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      物料信息
 */

namespace app\community\controller\common;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseWorkerService;
use app\community\model\service\MaterielTypeMsgService;

class MaterielController extends CommunityBaseController
{
    public function getWorks()
    {
        $village_id = $this->request->post('village_id');
        $property_id = $this->request->post('property_id');
        $where = ['is_del' => 0, 'status' => 1];
        if ($village_id > 0) {
            $where['village_id'] = $village_id;
        } elseif ($property_id > 0) {
            $where['property_id'] = $property_id;
        } else {
            $village_id = $this->adminUser['village_id'];
            $where['village_id'] = $village_id;
        }
        try {
            $list = (new HouseWorkerService())->getWorker($where, 'wid,name,type,phone');
            if ($list && !is_array($list)) {
                $list = $list->toArray();
            }
            $workColumn = [];
            foreach ($list as $item) {
                $workColumn[$item['wid']] = $item;
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, ['work_list' => $list, 'work_column' => $workColumn]);
    }
    
    public function getTypeMsgList()
    {
        $village_id = $this->request->post('village_id');

        $where = ['status' => 0];
        if ($village_id > 0) {
            $where['from'] = 0;
            $where['from_id'] = $village_id;
        } else {
            $village_id = $this->adminUser['village_id'];
            $where['from'] = 0;
            $where['from_id'] = $village_id;
        }
        try {
            $list = (new MaterielTypeMsgService())->getList($where);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, ['type_msg_list' => $list]);
    }
    
    public function materielCheckoutsAdd()
    {
        $param = $this->request->param();
        $village_id = isset($param['village_id']) ? intval($param['village_id']) : 0;
        if ($village_id <= 0) {
            $param['village_id'] = $this->adminUser['village_id'];
        }
        try {
            $result = (new MaterielTypeMsgService())->materielCheckoutsAdd($param);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, ['result' => $result]);
    }
}