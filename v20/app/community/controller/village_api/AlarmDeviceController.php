<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      智能报警设备
 */

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\AlarmDeviceService;
use app\community\model\service\Device\DeviceHkNeiBuHandleService;
use app\community\model\service\FaceDeviceService;
use app\consts\DeviceConst;

class AlarmDeviceController extends CommunityBaseController
{
    /**
     * 添加或更新告警设备.
     */
    public function addUpdatesAlarmDevice() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $device_name = $this->request->post('device_name', '', 'trim');
        $device_type = $this->request->post('device_type', 'alarm');
        $device_serial = $this->request->post('device_serial', '', 'trim');
        $validate_code = $this->request->post('validate_code', '', 'trim');
        $device_id = $this->request->post('device_id', 0, 'intval');
        $single_id = $this->request->post('single_id', 0, 'intval');
        $floor_id = $this->request->post('floor_id', 0, 'intval');
        $third_login = $this->request->post('third_login', '', 'trim');
        $third_login_password = $this->request->post('third_login_password', '', 'trim');
        $third_protocol = DeviceConst::HIK_YUNMO_NEIBU_SHEQU;
        $remark = $this->request->post('remark');
        if (!$device_name) {
            return api_output_error(1001, '请填写设备名称');
        }
        if (!$device_type) {
            return api_output_error(1001, '请选择设备类型');
        }
        if (!$device_serial) {
            return api_output_error(1001, '请填写设备序号');
        }
        if (!$validate_code) {
            return api_output_error(1001, '请填写设备验证码');
        }
        if($floor_id<1){
            return api_output_error(1001, '楼栋单元，单元没有选择！');
        }
        $alarmDeviceService = new AlarmDeviceService();
        $params = [];
        $params['device_name'] = $device_name;
        $params['device_type'] = $device_type;
        $params['device_serial'] = $device_serial;
        $params['validate_code'] = $validate_code;
        $params['village_id'] = $village_id;
        $params['single_id'] = $single_id;
        $params['floor_id'] = $floor_id;
        $params['third_login'] = $third_login;
        $params['third_login_password'] = $third_login_password;
        $params['third_protocol'] = $third_protocol;
        $params['remark'] = $remark;
        $cloud_status = 0;
        if ($device_id > 0) {
            $whereAlarmUpdate = [];
            $whereAlarmUpdate[] = ['village_id', '=', $village_id];
            $whereAlarmUpdate[] = ['device_id', '=', $device_id];
            $whereAlarmUpdate[] = ['is_del', '=', 0];
            $info = $alarmDeviceService->getAlarmDevice($whereAlarmUpdate, 'device_id, cloud_status');
            if (!$info || !isset($info['device_id'])) {
                return api_output_error(1001, '操作的设备不存在或者已经删除');
            }
            $cloud_status = isset($info['cloud_status']) ? intval($info['cloud_status']) : 0;
            $whereAlarm = [];
            $whereAlarm[] = ['device_type', '=', $device_type];
            $whereAlarm[] = ['device_id', '<>', $device_id];
            $whereAlarm[] = ['device_serial', '=', $device_serial];
            $whereAlarm[] = ['third_protocol', '=', $third_protocol];
            $whereAlarm[] = ['is_del', '=', 0];
            $other = $alarmDeviceService->getAlarmDevice($whereAlarm, 'device_id');
            if ($other && isset($other['device_id'])) {
                return api_output_error(1001, '该序列号设备已经添加过了');
            }
        } else {
            $whereAlarm = [];
            $whereAlarm[] = ['device_type', '=', $device_type];
            $whereAlarm[] = ['device_serial', '=', $device_serial];
            $whereAlarm[] = ['third_protocol', '=', $third_protocol];
            $whereAlarm[] = ['is_del', '=', 0];
            $other = $alarmDeviceService->getAlarmDevice($whereAlarm, 'device_id');
            if ($other && isset($other['device_id'])) {
                return api_output_error(1001, '该序列号设备已经添加过了');
            }
        }
        try{
            if ($device_id > 0) {
                $operation = 'updateDevice';
                $params['update_time'] = time();
                $save = $alarmDeviceService->updateAlarmDevice($whereAlarmUpdate, $params);
                if ($save === false) {
                    return api_output_error(1001, '更新失败');
                }
            } else {
                $operation = 'addDevice';
                $params['add_time'] = time();
                $device_id = $alarmDeviceService->addAlarmDevice($params);
                if (!$device_id) {
                    return api_output_error(1001, '更新失败');
                }
            }
            if ($device_id && $third_protocol) {
                $faceDeviceService = new FaceDeviceService();
                $param = [
                    'village_id'    => $village_id,
                    'device_id'     => $device_id,
                    'operation'     => $operation,
                    'deviceType'    => DeviceConst::DEVICE_TYPE_ALARM,
                    'thirdProtocol' => $third_protocol,
                ];
                $handleDevice = $faceDeviceService->handleDevice($device_serial, $param);
                if (isset($handleDevice['code']) && $handleDevice['code']>0) {
                    return api_output_error(-1, $handleDevice['msg']);
                }
                if ($cloud_status != 2) {
                    // 执行同步 更新状态为 同步中
                    $whereAlarmUpdate = [];
                    $whereAlarmUpdate[] = ['village_id', '=', $village_id];
                    $whereAlarmUpdate[] = ['device_id', '=', $device_id];
                    $whereAlarmUpdate[] = ['is_del', '=', 0];
                    $param1s = ['cloud_status' => 1, 'cloud_reason' => '', 'update_time' => time()];
                    $alarmDeviceService->updateAlarmDevice($whereAlarmUpdate, $param1s);
                }
                
                fdump_api($handleDevice,'$addUpdatesAlarmDevice');
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,['device_id' => $device_id]);
    }

    /**
     * 删除对应报警设备.
     */
    public function deleteAlarmDevice()
    {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $device_id = $this->request->post('device_id', 0, 'intval');
        if (!$device_id) {
            return api_output_error(1001, '缺少删除对象');
        }
        $alarmDeviceService = new AlarmDeviceService();
        $whereAlarmUpdate = [];
        $whereAlarmUpdate[] = ['village_id', '=', $village_id];
        $whereAlarmUpdate[] = ['device_id', '=', $device_id];
        $whereAlarmUpdate[] = ['is_del', '=', 0];
        $info = $alarmDeviceService->getAlarmDevice($whereAlarmUpdate, 'device_id, third_protocol, device_serial, cloud_device_id');
        if (!$info || !isset($info['device_id'])) {
            return api_output_error(1001, '操作的设备不存在或者已经删除');
        }
        $third_protocol = isset($info['third_protocol']) ? intval($info['third_protocol']) : 0;
        $device_serial = isset($info['device_serial']) ? trim($info['device_serial']) : '';
        $cloud_device_id = isset($info['cloud_device_id']) ? trim($info['cloud_device_id']) : '';
        if ($third_protocol > 0 && $cloud_device_id) {
            // todo 同步删除云端设备
            try{
                $orderGroupId = md5(uniqid().$_SERVER['REQUEST_TIME']);
                $param = [
                    'village_id'      => $village_id,
                    'device_id'       => $device_id,
                    'operation'       => 'deleteDevice',
                    'device_sn'       => $device_serial,
                    'orderGroupId'    => $orderGroupId,
                    'cloud_device_id' => $cloud_device_id,
                    'thirdProtocol'   => $third_protocol,
                    'deviceType'      => DeviceConst::DEVICE_TYPE_ALARM,
                ];
                $delInfo = (new FaceDeviceService())->deleteDevice($param);
                if (isset($delInfo['code']) && $delInfo['code'] > 0) {
                    return api_output_error(1001,$delInfo['msg']);
                }
            }catch (\Exception $e){
                return api_output_error(-1,$e->getMessage());
            }
        }
        $params['update_time'] = time();
        $params['is_del'] = time();
        $save = $alarmDeviceService->updateAlarmDevice($whereAlarmUpdate, $params);
        if ($save === false) {
            return api_output_error(1001, '更新失败');
        }
        return api_output(0,['device_id' => $device_id]);
    }

    /**
     * 获取设备列表.
     */
    public function getAlarmDeviceList() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $page = $this->request->post('page', 1);
        $pageSize = $this->request->post('pageSize', 15);
        $device_name = $this->request->post('device_name', '', 'trim');
        $device_serial = $this->request->post('device_serial', '', 'trim');
        $single_id = $this->request->post('single_id', 0, 'intval');
        $floor_id = $this->request->post('floor_id', 0, 'intval');
        $alarmDeviceService = new AlarmDeviceService();
        $where = [];
        if ($device_name) {
            $where[] = ['device_name', 'like', '%' . $device_name . '%'];
        }
        if ($device_serial) {
            $where[] = ['device_serial', 'like', '%' . $device_serial . '%'];
        }
        if ($single_id) {
            $where[] = ['single_id', '=', $single_id];
        }
        if ($floor_id) {
            $where[] = ['floor_id', '=', $floor_id];
        }
        $where[] = ['is_del', '=', 0];
        $where[] = ['village_id', '=', $village_id];
        $arr = [];
        try{
            $field = 'device_id, device_name, device_serial, village_id, single_id, floor_id, interaction_time, device_status, cloud_status, cloud_reason, add_time';
            $order = 'device_id DESC';
            $list = $alarmDeviceService->getAlarmDeviceList($where, $field, $order, $page, $pageSize);
            if ($list && !is_array($list)) {
                $list = $list->toArray();
            }
            $deviceStatusArr = $alarmDeviceService->getAlarmDeviceStatusArr();
            foreach ($list as &$item) {
                if (isset($item['interaction_time']) && intval($item['interaction_time']) > 1) {
                    $item['interaction_time_txt'] = date('Y-m-d H:i:s', $item['interaction_time']);
                } else {
                    $item['interaction_time_txt'] = '-';
                }
                if (isset($item['add_time']) && intval($item['add_time']) > 1) {
                    $item['add_time_txt'] = date('Y-m-d H:i:s', $item['add_time']);
                }
                $device_status = isset($item['device_status']) ? intval($item['device_status']) : 0;
                $item['device_status_arr'] = isset($deviceStatusArr[$device_status]) ? $deviceStatusArr[$device_status] : [];
                $cloud_status = isset($item['cloud_status']) ? intval($item['cloud_status']) : 0;
                $cloud_reason = isset($item['cloud_reason']) ? trim($item['cloud_reason']) : '';
                $item['cloud_arr'] = $alarmDeviceService->handleAlarmDeviceCloudStatus($cloud_status, $cloud_reason);
            }
            $count = $alarmDeviceService->getAlarmDeviceCount($where);
            $arr['list'] = $list;
            $arr['count'] = $count;
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$arr);
    }
    
    /**
     * 获取设备信息.
     */
    public function getAlarmDevice() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $device_id = $this->request->post('device_id', 0, 'intval');
        if (!$device_id) {
            return api_output_error(1001, '缺少删除对象');
        }
        $alarmDeviceService = new AlarmDeviceService();
        $whereAlarmUpdate = [];
        $whereAlarmUpdate[] = ['village_id', '=', $village_id];
        $whereAlarmUpdate[] = ['device_id', '=', $device_id];
        $whereAlarmUpdate[] = ['is_del', '=', 0];
        $field = 'device_id, device_name, device_serial,validate_code, third_login, third_login_password, third_protocol, device_ip, village_id, single_id, floor_id, interaction_time, device_status, cloud_status, cloud_reason, add_time';
        $info = $alarmDeviceService->getAlarmDevice($whereAlarmUpdate, $field);
        if (!$info || !isset($info['device_id'])) {
            return api_output_error(1001, '对应的设备不存在或者已经删除');
        }
        if (isset($info['interaction_time']) && intval($info['interaction_time']) > 1) {
            $info['interaction_time_txt'] = date('Y-m-d H:i:s', $info['interaction_time']);
        } else {
            $info['interaction_time_txt'] = '-';
        }
        if (isset($info['add_time']) && intval($info['add_time']) > 1) {
            $info['add_time_txt'] = date('Y-m-d H:i:s', $info['add_time']);
        }
        $device_status = isset($info['device_status']) ? intval($info['device_status']) : 0;
        $info['device_status_arr'] = isset($deviceStatusArr[$device_status]) ? $deviceStatusArr[$device_status] : [];
        $cloud_status = isset($info['cloud_status']) ? intval($info['cloud_status']) : 0;
        $cloud_reason = isset($info['cloud_reason']) ? trim($info['cloud_reason']) : '';
        $info['cloud_text'] = $alarmDeviceService->handleAlarmDeviceCloudStatus($cloud_status, $cloud_reason);
        return api_output(0, $info);
    }

    /**
     * 设备告警信息.
     */
    public function getDeviceAlarmEventList() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $device_id = $this->request->post('device_id', 0, 'intval');
        $page = $this->request->post('page',1);
        $pageSize = $this->request->post('pageSize',15);
        if (!$device_id) {
            return api_output_error(1001, '缺少删除对象');
        }
        $alarmDeviceService = new AlarmDeviceService();
        $whereAlarmUpdate = [];
        $whereAlarmUpdate[] = ['village_id', '=', $village_id];
        $whereAlarmUpdate[] = ['device_id', '=', $device_id];
        $whereAlarmUpdate[] = ['is_del', '=', 0];
        $info = $alarmDeviceService->getAlarmDevice($whereAlarmUpdate, true);
        if (!$info || !isset($info['device_id'])) {
            return api_output_error(1001, '对应的设备不存在或者已经删除');
        }
        $thirdProtocolArr = [DeviceConst::HIK_YUNMO_NEIBU_SHEQU]; // 支持协议类型
        $alarm_list = [];
        $alarm_count = 0;
        if (!empty($info)) {
            $third_protocol = isset($info['third_protocol']) ? intval($info['third_protocol']) : 0;
            if (!in_array($third_protocol, $thirdProtocolArr)){
                return api_output_error(1001,'当前类型设备不支持查看告警消息');
            }
            // todo 注意适配了大华其他的 这里要改
            $business_type = $third_protocol == DeviceConst::HIK_YUNMO_NEIBU_SHEQU ? 1 : 2;
            $cloud_device_id = isset($info['cloud_device_id']) ? trim($info['cloud_device_id']) : 0;
            if (!$cloud_device_id){
                return api_output_error(1001,'设备未同步添加至对应平台无法获取告警信息');
            }
            $whereAlarm = [];
            $whereAlarm[] = ['business_type', '=', $business_type];
            $whereAlarm[] = ['device_id', '=', $cloud_device_id];
            // todo 目前仅支持高空抛物
//            $whereAlarm[] = ['event_code', '=', 10213];
            $alarm_list = (new DeviceHkNeiBuHandleService())->getCameraAlarmEventList($whereAlarm, true, 'event_time DESC, id DESC', $page, $pageSize);
            if ($alarm_list && !is_array($alarm_list)) {
                $alarm_list = $alarm_list->toArray();
            }
            foreach ($alarm_list as &$alarm) {
                $local_url = isset($alarm['local_url']) ? trim($alarm['local_url']) : '';
                $local_url && $local_url = replace_file_domain($local_url);
                $alarm['local_url'] = $local_url;
                $local_url && $alarm['picture_url'] = $local_url;
                if (isset($alarm['event_time']) && intval($alarm['event_time']) > 1) {
                    $alarm['event_time_txt'] = date('Y-m-d', $alarm['event_time']);
                }
            }
            $alarm_count = (new DeviceHkNeiBuHandleService())->getCameraAlarmEventCount($whereAlarm);
        }
        $data = [
            'alarm_list' => $alarm_list,
            'count' => $alarm_count,
            'info' => $info,
        ];
        return api_output(0,$data);
    }

    /**
     * 更新设备告警工单配置.
     */
    public function setAlarmNewRepair() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $repair_cate_id = $this->request->post('repair_cate_id', 0, 'intval');
        $repair_cate_sub_id = $this->request->post('repair_cate_sub_id', 0, 'intval');
        $device_type = $this->request->post('device_type', 'alarm');
        $switch_on = $this->request->post('switch_on', 0, 'intval');
        if ($switch_on == 1 && (!$repair_cate_id || !$repair_cate_sub_id)) {
            return api_output(1001, [], '请选择工单类目和工单分类！');
        }
        try {
            $alarmDeviceService = new AlarmDeviceService();
            $param = [
                'village_id' => $village_id,
                'repair_cate_id' => $repair_cate_id,
                'repair_cate_sub_id' => $repair_cate_sub_id,
                'device_type' => $device_type,
                'switch_on' => $switch_on,
            ];
            $alarmDeviceService->setAlarmNewRepair($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,['village_id' => $village_id]);
    }

    /**
     * 获取设备告警工单配置.
     */
    public function getAlarmNewRepair() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $device_type = $this->request->post('device_type', 'alarm');
        $data = ['village_id' => $village_id];
        try {
            $alarmDeviceService = new AlarmDeviceService();
            $param = [
                'village_id' => $village_id,
                'device_type' => $device_type,
            ];
            $setInfo = $alarmDeviceService->getAlarmNewRepair($param);
            $data['setInfo'] = $setInfo;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
}