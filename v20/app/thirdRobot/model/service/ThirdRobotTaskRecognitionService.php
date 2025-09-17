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
use app\thirdRobot\model\db\ThirdRobotTaskRecognition;
class ThirdRobotTaskRecognitionService
{

    public function saveRobotRecognition($where = array(), $saveData = array())
    {
        $thirdRobotTaskRecognitionDb = new ThirdRobotTaskRecognition();
        $ret = $thirdRobotTaskRecognitionDb->saveOneData($where, $saveData);
        return $ret;
    }

    public function addRobotRecognition($addTask = array())
    {
        $thirdRobotTaskRecognitionDb = new ThirdRobotTaskRecognition();
        $inset_id = $thirdRobotTaskRecognitionDb->addOneData($addTask);
        return $inset_id;
    }

}