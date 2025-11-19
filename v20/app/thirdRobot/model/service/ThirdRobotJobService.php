<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      机器人-CROSS-队列中转
 */

namespace app\thirdRobot\model\service;

class ThirdRobotJobService
{
    public function robotToJob($type, $param) {
        switch ($type) {
            case 'villageSyncAll':
                (new ThirdRobotDeviceCmdService())->villageSyncAll($param);
                break;
            case 'addRoboterFace':
                (new ThirdRobotDeviceCmdService())->addRoboterFace($param);
                break;
            case 'deleteRoboterFace':
                (new ThirdRobotDeviceCmdService())->deleteRoboterFace($param);
                break;
            case 'personToRobots':
                (new ThirdRobotDeviceCmdService())->personToRobots($param);
                break;
            case 'getRobotsBizPoints':
                (new ThirdRobotDeviceCmdService())->getRobotsBizPoints($param);
                break;
            case 'getRobotBizPointList':
                (new ThirdRobotDeviceCmdService())->getRobotBizPointList($param);
                break;
            case 'recordUserCallRobot':
                (new ThirdRobotDeviceCmdService())->recordUserCallRobot($param);
                break;
            case 'getTaskDetail':
                (new ThirdRobotDeviceCmdService())->getTaskDetail($param);
                break;
            case 'getThirdRobotInfo':
                $robotCode = isset($param['robotCode']) ? trim($param['robotCode']) : '';
                (new ThirdRobotDeviceService())->getThirdRobotInfo($robotCode);
                break;

        }
        return true;
    }
}