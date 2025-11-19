<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      用户端机器人获取接口
 */
namespace app\thirdRobot\controller\user;

use app\community\model\service\HouseVillageService;
use app\thirdRobot\model\service\ThirdRobotDeviceCmdService;
use app\thirdRobot\model\service\ThirdRobotDeviceService;

class RobotController extends ApiBaseController
{
    /**
     * 机器人列表.
     */
    public function robotList() {
        if (! $this->_uid) {
            return api_output_error(1002, "用户不存在或未登录");
        }
        $uid = $this->_uid;
        $village_id = $this->request->post('village_id', 0, 'intval');
        $name = $this->request->post('name', '', 'trim');
        $property_id = $this->request->post('property_id', 0, 'intval');
        $pigcms_id = $this->request->post('pigcms_id', 0, 'intval'); //住户身份
        try{
            $where = [];
            if ($village_id) {
                $where[] = ['village_id', '=', $village_id];
            } elseif ($property_id) {
                $whereVillageColumn = [];
                $whereVillageColumn[] = ['property_id', '=', $property_id];
                $village_id_arr = (new HouseVillageService())->getVillageColumn($whereVillageColumn, 'village_id');
                $where[] = ['village_id', 'in', $village_id_arr];
            } elseif ($uid) {
                $whereVillageColumn = [];
                $whereVillageColumn[] = ['uid', '=', $uid];
                $whereVillageColumn[] = ['status', '=', 0];
                $village_id_arr = (new HouseVillageService())->getVillageUserColumn($whereVillageColumn, 'village_id');
                $where[] = ['village_id', 'in', $village_id_arr];
            }
            if ($name) {
                $where[] = ['name', 'like', '%'. $name . '%'];
            }
            $where[] = ['del_time', '=', 0];
            $handleUserData=array('pigcms_id'=>$pigcms_id,'village_id'=>$village_id);
            $result = (new ThirdRobotDeviceService())->getRobotList($where, true, 0, 0, 'running_status ASC', $handleUserData);
            $result['title'] = '选择呼叫机器人';
            $siteUrl = cfg('site_url');
            $static = $siteUrl . '/v20/public/static/third_robot/tools/';
            $result['recordImg'] = $static . 'record.png';
            $result['recordUrl'] = get_base_url('pages/robot/call_history');
            $result['backImg'] = $static . 'back.png?t=01';
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 呼叫点位列表.
     */
    public function robotBizPointList() {
        if (! $this->_uid) {
            return api_output_error(1002, "用户不存在或未登录");
        }
        $uid = $this->_uid;
        $robot_id = $this->request->post('robot_id', 0, 'intval');
        $page = $this->request->post('page', 1, 'intval');
        $name = $this->request->post('name', '', 'trim');
        $map_code = $this->request->post('map_code', '', 'trim');
        if (! $robot_id) {
            return api_output_error(1001, "缺少机器人id");
        }
        try{
            (new ThirdRobotDeviceCmdService())->jobToGetRobotsBizPoints();
            $thirdRobotDeviceService = new ThirdRobotDeviceService();
            $whereThirdRobot = [
                ['id', '=', $robot_id]
            ];
            $robotInfo = $thirdRobotDeviceService->getOneDataInfo($whereThirdRobot, 'id, robot_code, name, robot_type');
            $robot_code = isset($robotInfo['robot_code']) && $robotInfo['robot_code'] ? $robotInfo['robot_code'] : '';
            $where = [];
            $where[] = ['robot_code', '=', $robot_code];
            $where[] = ['type', '=', 1];
            $where[] = ['delete_time', '=', 0];
            if ($name) {
                $where[] = ['name', 'like', '%'. $name . '%'];
            }
            if ($map_code) {
                $where[] = ['map_code', '=', $map_code];
            }
            $field = 'id, point_id, name, map_name, phone, floor, sort';
            $result = $thirdRobotDeviceService->getRobotBizPointList($where, $field, $page);
            $result['title'] = '呼叫点位';
            $result['robot_name'] = isset($robotInfo['name']) && $robotInfo['name'] ? $robotInfo['name'] : '';
            $robot_type = isset($robotInfo['robot_type']) && $robotInfo['robot_type'] ? trim($robotInfo['robot_type']) : '';
            $result['robot_img'] = $thirdRobotDeviceService->getRobotImg($robot_type);

            $where = [];
            $where[] = ['robot_code', '=', $robot_code];
            $where[] = ['delete_time', '=', 0];
            $maps = $thirdRobotDeviceService->getRobotBizMapsList($where, true, 0);
            $dropdownOptions = [];
            if (count($maps['list']) > 1) {
                foreach ($maps['list'] as $item) {
                    $dropdownOptions[] = [
                        'label' => $item['map_name'],
                        'value' => strval($item['map_code']),
                    ];
                }
            }
            $result['dropdownOptions'] = $dropdownOptions;
            $result['dropdownValue'] = $map_code;
            $result['dropdownTitle'] = '地图名称';
            $siteUrl = cfg('site_url');
            $static = $siteUrl . '/v20/public/static/third_robot/tools/';
            $result['recordImg'] = $static . 'record.png';
            $result['recordUrl'] = get_base_url('pages/robot/call_history?robot_id='.$robot_id);
            $result['backImg'] = $static . 'back.png?t=01';
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 呼叫机器人.
     */
    public function callRobot() {
        if (! $this->_uid) {
            return api_output_error(1002, "用户不存在或未登录");
        }
        $uid = $this->_uid;
        $robot_id = $this->request->post('robot_id', 0, 'intval');
        $id = $this->request->post('id', 0, 'intval');
        $point_id = $this->request->post('point_id', 0, 'intval');
        $call_from = $this->request->post('call_from', 'village_my', 'trim');
        $call_from_id = $this->request->post('call_from_id', 0, 'trim');
        if (!$robot_id || (!$id && !$point_id)) {
            return api_output_error(1001, "缺少呼叫对象");
        }
        try{
            $thirdRobotDeviceCmdService = new ThirdRobotDeviceCmdService();
            $param = [
                'uid' => $uid,
                'robot_id' => $robot_id,
                'robot_code' => isset($robotInfo['robot_code']) ? trim($robotInfo['robot_code']) : '',
                'id' => $id,
                'point_id' => $point_id,
                'call_from' => $call_from,
                'call_from_id' => $call_from_id,
            ];
            $result = $thirdRobotDeviceCmdService->userCallRobot($param);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 呼叫历史.
     */
    public function callHistory() {
        if (! $this->_uid) {
            return api_output_error(1002, "用户不存在或未登录");
        }
        $page = $this->request->post('page', 1, 'intval');
        $name = $this->request->post('name', '', 'trim');
        $robot_id = $this->request->post('robot_id', 1, 'intval');
        $robot_code = $this->request->post('robot_code', '', 'trim');
        $uid = $this->_uid;
        try{
            $param = ['uid' => $uid, 'name' => $name, 'robot_id' => $robot_id, 'robot_code' => $robot_code, 'page' => $page, 'isHandleUserShow' => true];
            $result = (new ThirdRobotDeviceCmdService())->callHistory($param);
            $result['title'] = '呼叫记录';
            $siteUrl = cfg('site_url');
            $static = $siteUrl . '/v20/public/static/third_robot/tools/';
            $result['backImg'] = $static . 'back.png?t=01';
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 任务详情(呼叫机器也是任务).
     */
    public function callRecordDetail() {
        if (! $this->_uid) {
            return api_output_error(1002, "用户不存在或未登录");
        }
        $record_id = $this->request->post('record_id', 1, 'intval');
        $uid = $this->_uid;
        try{
            $param = ['uid' => $uid, 'record_id' => $record_id];
            $result = (new ThirdRobotDeviceCmdService())->callRecordDetail($param);
            $result['title'] = '记录详情';
            $siteUrl = cfg('site_url');
            $static = $siteUrl . '/v20/public/static/third_robot/tools/';
            $result['backImg'] = $static . 'back.png?t=01';
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 获取机器人详情
     */
    public function robotDetail() {
        if (! $this->_uid) {
            return api_output_error(1002, "用户不存在或未登录");
        }
        $uid = $this->_uid;
        $robot_id = $this->request->post('robot_id','0','intval');
        $robot_code = $this->request->post('robot_code','','trim');
        $whereThirdRobot = [];
        if ($robot_id) {
            $whereThirdRobot[] = ['id', '=', $robot_id];
        } else {
            $whereThirdRobot[] = ['robot_code', '=', $robot_code];
        }
        $thirdRobotDeviceService = new ThirdRobotDeviceService();
        $result = [];
        try{
           $robot = $thirdRobotDeviceService->getOneDataInfo($whereThirdRobot, true, 'id DESC', true, $uid);
            $result['robot'] = $robot;
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $result);
    }
}