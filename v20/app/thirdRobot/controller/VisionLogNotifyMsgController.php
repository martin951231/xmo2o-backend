<?php
/**
 * 订阅AI识别记录推送异步通知地址
 * v20/public/index.php/thirdRobot/VisionLogNotifyMsg/receiveMsg
 */
namespace app\thirdRobot\controller;
use app\thirdRobot\model\service\ThirdRobotDeviceService;
use app\thirdRobot\model\service\ThirdRobotTaskRecognitionService;
class VisionLogNotifyMsgController
{
    public function receiveMsg()
    {
        $input = file_get_contents("php://input");
        $post=$_POST;
        fdump_api(['receiveMsg'.__LINE__,'input'=>$input,'post'=>$post],'000VisionLogNotifyMsg',1);
        if (empty($post)) {
            $post = array();
        }
        $responseArr = array('code' => 0, 'message' => 'success', 'date_time' => date('Y-m-d H:i:s'));
        if (!empty($input)) {
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
        if ($type == 'visionLog' && isset($post['params']) && !empty($post['params'])) {
            $params = $post['params'];
            if (!is_array($params) && is_string($params)) {
                $params = json_decode($params, true);
            }
            $thirdRobotDeviceService = new ThirdRobotDeviceService();
            if ($params && isset($params['taskInstanceId']) && isset($params['robotCode']) &&  isset($params['taskNodeId'])) {
                $recognitionData=array();
                $robot_code=trim($params['robotCode']);
                $recognitionData['robot_code']=$robot_code;
                $recognitionData['task_instance_id']=trim($params['taskInstanceId']);
                $recognitionData['task_node_id']=trim($params['taskNodeId']);
                $recognitionData['item_code']=$params['itemCode'] ? trim($params['itemCode']):'';
                $recognitionData['point_name']=isset($params['bizPointName']) && $params['bizPointName'] ? trim($params['bizPointName']):'';
                $imageUrls=isset($params['imageUrls']) && !empty($params['imageUrls']) ? $params['imageUrls']:'';
                if ($imageUrls && !is_array($imageUrls)) {
                    $imageUrls = array($imageUrls);
                }
                $recognitionData['image_urls'] = $imageUrls ? json_encode($imageUrls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
                $videoUrls=isset($params['videoUrls']) && !empty($params['videoUrls']) ? $params['videoUrls']:'';
                if($videoUrls && !is_array($videoUrls)){
                    $videoUrls=array($videoUrls);
                }
                $recognitionData['video_urls']=$videoUrls ? json_encode($videoUrls,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):'';
                $recognitionData['rtype']=2;
                $timestamp = isset($post['timestamp']) && intval($post['timestamp']) > 100 ? intval($post['timestamp']) / 1000 : $nowtime;
                $start_time = isset($params['startTime']) ? trim($params['startTime']) : 0;
                $start_time = $start_time / 1000;
                $start_time = $start_time > 0 ? $start_time : $nowtime;
                $start_time = round($start_time);
                $end_time = isset($params['endTime']) ? trim($params['endTime']) : 0;
                $end_time = $end_time / 1000;
                $end_time = round($end_time);
                $recognitionData['start_time'] = $start_time;
                $recognitionData['end_time'] = $end_time;
                $recognitionData['remark'] = isset($params['remark']) && $params['remark'] ? trim($params['remark']) : '';
                $recognitionData['add_time'] = $timestamp;
                $recognitionData['response_data'] = json_encode($post, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $thirdRobotTaskRecognitionService = new ThirdRobotTaskRecognitionService();
                try {
                    $thirdRobotTaskRecognitionService->addRobotRecognition($recognitionData);
                }catch (\Exception $e){
                    fdump_api(['receiveMsg'.__LINE__,$e->getMessage()],'000VisionLogNotifyMsg',1);
                }
                $whereArr = array('robot_code' => $robot_code);
                $oneRobotDevice = $thirdRobotDeviceService->getOneDataInfo($whereArr);
                if (!empty($oneRobotDevice)) {
                    $recognitionData['title']='';
                    if(!empty($recognitionData['item_code'])){
                        $title_tmp=$thirdRobotDeviceService->getVisionLogCodeTitle($recognitionData['item_code']);
                        $recognitionData['title']=$title_tmp ? $title_tmp:$recognitionData['item_code'];
                    }
                    $recognitionData['image_urls']=$imageUrls;
                    $recognitionData['video_urls']=$videoUrls;
                    $ret=$thirdRobotDeviceService->saveWisdomQrcodeRecord($recognitionData,$oneRobotDevice['village_id']);
                }else{
                    //队列更新机器人列表
                }
            }
        }
        echo json_encode($responseArr,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit();
    }

}

