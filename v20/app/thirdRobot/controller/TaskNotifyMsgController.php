<?php
/**
 * 订阅任务推送异步通知地址
 * v20/public/index.php/thirdRobot/TaskNotifyMsg/receiveMsg
 */
/*
declare (strict_types=1);
*/
namespace app\thirdRobot\controller;

use app\thirdRobot\model\service\ThirdRobotDeviceService;
use app\thirdRobot\model\service\ThirdRobotSubscribeMessageService;
class TaskNotifyMsgController
{
    public function receiveMsg()
    {
        $input = file_get_contents("php://input");
        $post = $_POST;
        fdump_api(['receiveMsg' . __LINE__, 'input' => $input, 'post' => $post], '000TaskNotifyMsg', 1);
        if(empty($post)){
            $post=array();
        }
        $responseArr=array('code'=>0,'message'=>'success','date_time'=>date('Y-m-d H:i:s'));
        if(!empty($input)){
            $inputData = json_decode($input, true);
            if ($inputData && !empty($inputData) && is_string($input)) {
                $input = str_replace('"[{', '[{', $input);
                $input = str_replace('}]"', '}]', $input);
                $input = stripslashes($input);
            }
            if (!$inputData || empty($inputData)) {
                $inputData = is_array($input) ? $input : json_decode($input, true);
            }
            if ($inputData && !empty($inputData) && !empty($post)) {
                $inputData = array_merge($post, $inputData);
            }
            $post = $inputData;
        }
        $type = !empty($post) && isset($post['type']) ? trim($post['type']) : '';
        $nowtime = time();
        if ($type == 'task' && isset($post['params']) && !empty($post['params'])) {
            $params =$post['params'];
            if(!is_array( $params) && is_string($params)){
                $params =json_decode($params, true);
            }

            $thirdRobotDeviceService = new ThirdRobotDeviceService();
            if ($params && isset($params['taskInstanceId']) && isset($params['robotCode'])) {
                $robot_code = trim($params['robotCode']);
                $thirdRobotSubscribeMessageService=new ThirdRobotSubscribeMessageService();
                $messageArr=array();
                $messageArr['type']=$type;
                $messageArr['robot_code']=$robot_code;
                $messageArr['response_data']=json_encode($post,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                $messageArr['add_time']=$nowtime;
                $unique_key=$type.'_'.$robot_code.'_'.md5($messageArr['response_data']);
                $messageArr['unique_key']=$unique_key;
                try {
                    $thirdRobotSubscribeMessageService->addOneRobotMessage($messageArr);
                }catch (\Exception $e){
                    $responseArr['code']=1;
                    $responseArr['message']='重复通知!';
                    echo json_encode($responseArr,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    exit();
                }
                $task_id = isset($params['id']) ? trim($params['id']) : '';
                $saveData = array('task_name' => $params['taskName']);
                $saveData['robot_code'] = $robot_code;
                $saveData['task_instance_id'] = $params['taskInstanceId'];
                $saveData['status'] = $params['status'] ? trim($params['status']) : -1;
                $start_time = $params['startTimestamp'] ? trim($params['startTimestamp']) : 0;   //毫秒
                if (isset($params['robotName']) && $params['robotName']) {
                    $saveData['robot_name'] = ($params['robotName']);
                }
                if (isset($params['taskType']) && $params['taskType']) {
                    $saveData['task_type'] = trim($params['taskType']);
                }
                if (isset($params['robotType']) && $params['robotType']) {
                    $saveData['robot_type'] = trim($params['robotType']);
                }
                if (isset($params['source']) && $params['source']) {
                    $saveData['source'] = trim($params['source']);
                }
                if ($task_id) {
                    $saveData['task_id'] = $task_id;
                }
                $start_time = $start_time / 1000;
                $start_time = round($start_time);
                $saveData['start_time'] = $start_time;
                $end_time = $params['endTimestamp'] ? trim($params['endTimestamp']) : 0;   //毫秒
                $end_time = $end_time / 1000;
                $end_time = round($end_time);
                $saveData['end_time'] = $end_time;
                $saveData['response_data'] = json_encode($params, JSON_UNESCAPED_UNICODE);
                $whereArr = array('robot_code' => $robot_code);
                $oneRobotDevice = $thirdRobotDeviceService->getOneDataInfo($whereArr);
                $idd = 0;
                if (!empty($oneRobotDevice)) {
                    $whereArr['task_instance_id'] = $params['taskInstanceId'];
                    $oneRobotTask = $thirdRobotDeviceService->getOneRobotTask($whereArr);
                    if ($oneRobotTask) {
                        $idd = $oneRobotTask['id'];
                        if(empty($task_id) && $oneRobotTask['task_id']){
                            $task_id= $oneRobotTask['task_id'];
                        }
                        $saveData['update_time'] = $nowtime;
                        $thirdRobotDeviceService->saveOneRobotTask(['id' => $oneRobotTask['id']], $saveData);
                    } else {
                        $saveData['add_time'] = time();
                        $idd = $thirdRobotDeviceService->addOneRobotTask($saveData);
                    }
                    $thirdRobotDeviceService->getRobotRemoteTaskDetails($saveData['task_instance_id'], $idd, $task_id, true);
                } else {
                    //队列更新机器人列表
                }
            }
        }
        echo json_encode($responseArr,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit();
    }

}

