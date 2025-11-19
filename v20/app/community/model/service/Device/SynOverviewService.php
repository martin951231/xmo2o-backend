<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      同步概况
 */

namespace app\community\model\service\Device;

use app\community\model\db\Device\SynOverview;
use app\community\model\db\DeviceBindInfo;
use app\consts\DeviceConst;

class SynOverviewService
{
    /**
     * 处理同步记录.
     */
    public function handleSynOverview($param) {
        fdump_api([ 'msg' => '处理同步记录-参数', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/handleSynOverviewLog', 1);
        $syn_type = isset($param['syn_type']) && $param['syn_type'] ? trim($param['syn_type']) : '';
        $syn_id = isset($param['syn_id']) && $param['syn_id'] ? trim($param['syn_id']) : 0;
        $to_id = isset($param['to_id']) && $param['to_id'] ? trim($param['to_id']) : 0;
        if (!$syn_type || !$syn_id) {
            fdump_api([ 'msg' => '处理同步记录-缺少必要参数', 'line' => __LINE__], 'hikGateWay/handleSynOverviewLog', 1);
            return false;
        }
        $nowTime = isset($param['nowTime']) && $param['nowTime'] ? intval($param['nowTime']) : time();
        $syn_status = isset($param['syn_status']) && $param['syn_status'] ? trim($param['syn_status']) : '';
        $syn_msg = isset($param['syn_msg']) && $param['syn_msg'] ? trim($param['syn_msg']) : '';
        $syn_time = isset($param['syn_time']) && $param['syn_time'] ? intval($param['syn_time']) : $nowTime;
        $extra_info = unsetParam($param, ['syn_type', 'syn_id', 'to_id', 'syn_status', 'syn_msg', 'syn_time']);
        fdump_api([ 'msg' => '处理同步记录-参数', 'line' => __LINE__, 'extra_info' => $extra_info], 'hikGateWay/handleSynOverviewLog', 1);
        $where = [];
        $where[] = ['syn_type', '=', $syn_type];
        $where[] = ['syn_id', '=', $syn_id];
        $where[] = ['to_id', '=', $to_id];
        $dbSynOverview = new SynOverview();
        $info = $dbSynOverview->getOne($where, 'id');
        $data = [
            'syn_type' => $syn_type, 'syn_id' => $syn_id, 'to_id' => $to_id, 'syn_status' => $syn_status,
            'syn_msg' => $syn_msg, 'syn_time' => $syn_time, 'extra_info' => json_encode($extra_info, JSON_UNESCAPED_UNICODE),
        ];
        $id = isset($info['id']) ? intval($info['id']) : 0;
        fdump_api([ 'msg' => '处理同步记录-参数', 'line' => __LINE__, 'info_id' => $id, 'data' => $data], 'hikGateWay/handleSynOverviewLog', 1);
        if ($id > 0) {
            $data['update_time'] = $nowTime;
            $dbSynOverview->updateThis(['id' => $id], $data);
        } else {
            $data['add_time'] = $nowTime;
            $id = $dbSynOverview->add($data);
        }
        fdump_api([ 'msg' => '处理同步记录-参数', 'line' => __LINE__, 'id' => $id], 'hikGateWay/handleSynOverviewLog', 1);
        return $id;
    }

    /**
     * 获取关联信息.
     */
    public function getSynOverview($param) {
        $syn_type = isset($param['syn_type']) && $param['syn_type'] ? trim($param['syn_type']) : '';
        $syn_id = isset($param['syn_id']) && $param['syn_id'] ? trim($param['syn_id']) : 0;
        $to_id = isset($param['to_id']) && $param['to_id'] ? trim($param['to_id']) : 0;
        $field = isset($param['field']) && $param['field'] ? trim($param['field']) : '';
        !$field && $field = 'id, syn_status, syn_msg, syn_time';
        $where = [];
        $where[] = ['syn_type', '=', $syn_type];
        $where[] = ['syn_id', '=', $syn_id];
        $where[] = ['to_id', '=', $to_id];
        $dbSynOverview = new SynOverview();
        return $dbSynOverview->getOne($where, $field);
    }
    
    /**
     * 获取关联信息.
     */
    public function getDeviceBindInfo($param) {
        $device_equipment_type = isset($param['deviceType']) ? trim($param['deviceType']) : '';
        $device_id = isset($param['device_id']) && $param['device_id'] ? intval($param['device_id']) : 0;
        $thirdProtocol = isset($param['thirdProtocol']) && $param['thirdProtocol'] ? intval($param['thirdProtocol']) : 0;
        $field = isset($param['field']) && $param['field'] ? trim($param['field']) : '';
        !$field && $field = 'id, syn_status, syn_status_txt, err_reason, add_time, update_time';
        $dbDeviceBindInfo = new DeviceBindInfo();
        switch ($device_equipment_type) {
            case DeviceConst::DEVICE_TYPE_FACE:
                $record_bind_type = DeviceConst::BIND_FACE_DEVICE;
                break;
            case DeviceConst::DEVICE_TYPE_CAMERA:
                $record_bind_type = DeviceConst::BIND_CAMERA_DEVICE;
                break;
            default:
                return [];
        }
        $whereDeviceBind = [];
        $whereDeviceBind[] = ['bind_type', '=', $record_bind_type];
        $whereDeviceBind[] = ['bind_id', '=', $device_id];
        $whereDeviceBind[] = ['third_protocol', '=', $thirdProtocol];
        return $dbDeviceBindInfo->getOne($whereDeviceBind, $field, 'id DESC');
    }
}