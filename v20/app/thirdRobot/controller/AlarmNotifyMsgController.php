<?php
/**
 * 订阅告警推送异步通知地址
 * v20/public/index.php/thirdRobot/AlarmNotifyMsg/receiveMsg
 */
/*
declare (strict_types=1);
*/
namespace app\thirdRobot\controller;
use app\thirdRobot\model\service\ThirdRobotDeviceService;
use app\thirdRobot\model\service\ThirdRobotSubscribeMessageService;
class AlarmNotifyMsgController
{
    public function receiveMsg()
    {
        $input = file_get_contents("php://input");
        $post=$_POST;
        fdump_api(['receiveMsg'.__LINE__,'input'=>$input,'post'=>$post],'000AlarmNotifyMsg',1);
        if(empty($post)){
            $post=array();
        }
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
        $responseArr=array('code'=>0,'message'=>'success','date_time'=>date('Y-m-d H:i:s'));
        $type = !empty($post) && isset($post['type']) ? trim($post['type']) : '';
        $nowtime = time();
        if ($type == 'alarm' && isset($post['params'])&& !empty($post['params'])) {
            $params =$post['params'];
            if(!is_array($params) && is_string($params) ){
                $params =json_decode($params, true);
            }
            if($params &&  isset($params['robotCode']) && !empty($params['robotCode'])){
                $level=isset($params['level']) ? trim($params['level']):0;
                $level=intval($level);
                $robot_code=trim($params['robotCode']);
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
                $msg_type_code=isset($params['msgTypeCode']) ? trim($params['msgTypeCode']):0;
                $start_time=isset($params['startTimestamp']) ? trim($params['startTimestamp']):0;
                $start_time = $start_time / 1000;
                $start_time=$start_time>0 ? $start_time:$nowtime;
                $start_time = round($start_time); //告警产生时间戳
                $thirdRobotSubscribeMessageService=new ThirdRobotSubscribeMessageService();
                $end_time=isset($params['endTimestamp']) ? trim($params['endTimestamp']):0;
                $end_time = $end_time / 1000;
                $end_time = round($end_time);  //告警解除时间戳
                if($end_time<100){
                    $alarm_name=isset($params['alarmName']) ? trim($params['alarmName']):'';
                    $thirdRobotDeviceService = new ThirdRobotDeviceService();
                    $whereArr = array('robot_code' => $robot_code);
                    $oneRobotDevice = $thirdRobotDeviceService->getOneDataInfo($whereArr);
                    $msgInfo=array('level'=>$level,'robot_code'=>$robot_code);
                    $msgInfo['start_time']=$start_time;
                    $msgInfo['end_time']=$end_time;
                    $msgInfo['msg_type_code']=$msg_type_code;
                    $msgInfo['alarm_name']=$alarm_name;
                    $msg_type_code_title=$thirdRobotDeviceService->getAlarmCodeTitle($msg_type_code);
                    $msgInfo['msg_type_code_title']=$msg_type_code_title;
                    if (!empty($oneRobotDevice)) {
                        if(!empty($oneRobotDevice['alarm_notice_set'])){
                            $msgInfo['alarm_notice_set']=$oneRobotDevice['alarm_notice_set'];
                            $msgInfo['robot_name']=$oneRobotDevice['name'];
                            $msgInfo['village_id']=$oneRobotDevice['village_id'];
                            $alarm_notice_set=is_array($oneRobotDevice['alarm_notice_set'])? $oneRobotDevice['alarm_notice_set']:json_decode($oneRobotDevice['alarm_notice_set'],true);
                            if($alarm_notice_set && is_array($alarm_notice_set)){
                                $person_ids=array();
                                foreach ($alarm_notice_set as $vv){
                                    if($vv['levels'] && in_array($level,$vv['levels'])){
                                        if(!isset($person_ids[$vv['person_id']])){
                                            $person_ids[$vv['person_id']]=$vv;
                                        }
                                    }
                                }
                                foreach ($person_ids as $item){
                                    $thirdRobotSubscribeMessageService->sendNoticeToAdmin($item,$msgInfo,'alarm');
                                }
                            }
                        }

                    }else{
                        //队列更新机器人列表
                    }
                }
            }
        }
        echo json_encode($responseArr,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit();
    }

}

